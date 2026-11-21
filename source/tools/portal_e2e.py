#!/usr/bin/env python3
"""Website 2.2 · client portal end-to-end test (Playwright + local PostgreSQL).
Flow: register → confirm email → basket from profile pages → request with files → messages → admin reply →
admin quote → client accepts → status → access control → password reset → website form → portal → admin delete."""
import asyncio, glob, json, os, re, subprocess, sys, time
from playwright.async_api import async_playwright
B = 'http://127.0.0.1:8080'; SITE = '/tmp/rfcms/site'; OUT = SITE + '/data/_portal/outbox/'
R = []
def ok(name, cond, note=''):
    R.append((name, bool(cond), note)); print(('PASS ' if cond else 'FAIL ') + name + (f'  [{note}]' if note and not cond else ''))
def last_mail(to, contains=''):
    for f in sorted(glob.glob(OUT + '*.json'), reverse=True):
        m = json.load(open(f))
        if m['to'] == to and contains in m['subject'] + m['body']: return m
    return None
def link_in(m, part):
    u = re.search(r'https?://\S*' + re.escape(part) + r'\S*', m['body']).group(0)
    return re.sub(r'^https?://[^/]+', B, u)
def psql(sql): return subprocess.run(['psql', '-h', '127.0.0.1', '-p', '5433', '-U', 'rf', '-d', 'reclamefabriek', '-At', '-c', sql], capture_output=True, text=True).stdout.strip()

async def main():
    psql('TRUNCATE rf_clients, rf_counters, rf_throttle RESTART IDENTITY CASCADE')
    for f in glob.glob(OUT + '*.json'): os.remove(f)
    for f in glob.glob(SITE + '/data/_inbox/*.json'): os.remove(f)
    open('/tmp/rf-drawing.pdf', 'wb').write(b'%PDF-1.4\n% test drawing\n' + b'0' * 2048)
    open('/tmp/rf-photo.jpg', 'wb').write(b'\xff\xd8\xff' + b'1' * 4096)
    open('/tmp/rf-virus.exe', 'wb').write(b'MZ' + b'0' * 100)
    async with async_playwright() as p:
        br = await p.chromium.launch()
        A = await br.new_context(viewport={'width': 1280, 'height': 900}); a = await A.new_page()
        errs = []; a.on('pageerror', lambda e: errs.append(str(e))); a.on('console', lambda m: errs.append(m.text) if m.type == 'error' else None)
        a.on('dialog', lambda d: asyncio.ensure_future(d.accept()))

        # ---------- register (Latvian site) + confirm email
        await a.goto(B + '/lv/portal/register/')
        ok('register page in Latvian', 'Izveidot kontu' in await a.content())
        await a.fill('[name=name]', 'Anna Ozola'); await a.fill('[name=company]', 'Ozols SIA'); await a.fill('[name=email]', 'anna@ozols.test')
        await a.fill('[name=phone]', '+371 20000000'); await a.fill('[name=pw]', 'very-secret-1'); await a.fill('[name=pw2]', 'very-secret-1'); await a.check('[name=consent]')
        await a.click('form button[type=submit]'); await a.wait_for_load_state()
        ok('register → back on login with notice', '/lv/portal/login/' in a.url and 'apstiprināšanai' in await a.content())
        await a.fill('[name=email]', 'anna@ozols.test'); await a.fill('[name=pw]', 'very-secret-1'); await a.click('form button[type=submit]'); await a.wait_for_load_state()
        ok('cannot log in before confirming email', 'apstiprin' in await a.content() and '/login/' in a.url)
        m = last_mail('anna@ozols.test', 'verify'); ok('verification email written in Latvian', m and 'Apstipriniet' in m['subject'])
        await a.goto(link_in(m, '/portal/verify/')); await a.wait_for_load_state()
        ok('email confirmed', 'apstiprināts' in await a.content())
        await a.goto(link_in(m, '/portal/verify/')); ok('verify link works only once', 'novecojusi' in await a.content())

        # ---------- basket on the website → portal (login redirect keeps the basket)
        await a.goto(B + '/lv/profile-4/'); await a.click('[data-basket-add]')
        await a.goto(B + '/lv/profile-7-lux/'); await a.click('[data-basket-add]')
        n = await a.text_content('[data-basket-n]'); ok('basket counts 2 profiles', n == '2', n)
        await a.click('[data-basket-link]'); await a.wait_for_load_state()
        ok('portal asks to log in first, keeps the request', '/lv/portal/login/' in a.url and 'pieprasījumu' in await a.content())
        await a.fill('[name=email]', 'anna@ozols.test'); await a.fill('[name=pw]', 'very-secret-1'); await a.click('form button[type=submit]'); await a.wait_for_load_state()
        ok('after login lands on new request', '/portal/requests/new/' in a.url, a.url)
        items = await a.locator('[data-item]').count(); ok('two signs pre-filled from the basket', items == 2, str(items))
        sel = await a.eval_on_selector_all('[data-profile-pick]', 'els => els.map(e => e.value)'); ok('pre-filled with the right profiles', sel == ['profile-4', 'profile-7-lux'], str(sel))
        await a.click('[data-item-add]'); ok('add a third sign', await a.locator('[data-item]').count() == 3)
        await a.locator('[data-item]').nth(2).locator('[data-item-rm]').click(); ok('remove it again', await a.locator('[data-item]').count() == 2)
        await a.fill('[name=title]', 'Fasādes izkārtne, Rīga'); await a.fill('[name=site_address]', 'Brīvības iela 1, Rīga')
        await a.fill('[name="items[0][content]"]', 'OZOLS'); await a.fill('[name="items[0][height_mm]"]', '600'); await a.fill('[name="items[0][depth]"]', '100 mm')
        await a.select_option('[name="items[0][mounting]"]', 'on_frame'); await a.fill('[name="items[0][face_colour]"]', 'RAL 9003')
        await a.fill('[name="items[1][content]"]', 'logo'); await a.fill('[name="items[1][qty]"]', '2')
        await a.set_input_files('[name="files[]"]', ['/tmp/rf-drawing.pdf', '/tmp/rf-virus.exe'])
        await a.click('.pt-submit button'); await a.wait_for_load_state()
        ok('request created with number', 'RF-2026-0001' in a.url, a.url)
        c = await a.content()
        ok('wrong file type refused, PDF kept', 'rf-virus.exe' in c and 'rf-drawing.pdf' in c and psql("SELECT count(*) FROM rf_files") == '1')
        ok('items saved', psql("SELECT string_agg(profile_slug||':'||content||':'||coalesce(height_mm,0)||':'||qty, ',' ORDER BY position) FROM rf_request_items") == 'profile-4:OZOLS:600:1,profile-7-lux:logo:0:2')
        await a.wait_for_timeout(300)
        bk = await a.evaluate("localStorage.getItem('rf_basket')"); ok('basket emptied after sending', bk == '[]', bk)
        ok('timeline shows "received" lit', await a.locator('.pt-line li.is-now').count() == 1)
        ok('client got a confirmation email', last_mail('anna@ozols.test', 'RF-2026-0001') is not None)
        ok('staff got a notification', any('RF-2026-0001' in json.load(open(f))['subject'] and json.load(open(f))['to'] != 'anna@ozols.test' for f in glob.glob(OUT + '*.json')))

        # ---------- client message with attachment
        await a.fill('[name=body]', 'Lūdzu, piedāvājiet arī montāžu.'); await a.set_input_files('.pt-reply [name="files[]"]', ['/tmp/rf-photo.jpg'])
        await a.click('.pt-reply button[type=submit]'); await a.wait_for_load_state()
        ok('client message posted with photo', 'piedāvājiet arī montāžu' in await a.content() and 'rf-photo.jpg' in await a.content())
        # download own file
        fid = psql("SELECT id FROM rf_files WHERE original_name = 'rf-photo.jpg'")
        r = await a.request.get(B + f'/lv/portal/files/{fid}/'); ok('client can download own file', r.status == 200 and (await r.body())[:3] == b'\xff\xd8\xff')

        # ---------- admin: request list, reply, quote
        S = await br.new_context(viewport={'width': 1400, 'height': 900}); s = await S.new_page()
        s.on('dialog', lambda d: asyncio.ensure_future(d.accept()))
        await s.goto(B + '/admin/'); await s.fill('[name=login]', 'tester'); await s.fill('[name=pw]', 'admin-test-pass-1'); await s.click('button[type=submit]'); await s.wait_for_load_state()
        ok('admin nav shows new request badge', 'Запросы клиентов <b class="badge">1</b>' in await s.content())
        await s.goto(B + '/admin/?a=portal'); ok('admin list shows the request in bold', await s.locator('tr.unread', has_text='RF-2026-0001').count() == 1)
        await s.click('text=RF-2026-0001'); await s.wait_for_load_state()
        c = await s.content(); ok('admin sees items in Russian with profile names', 'Профиль 4' in c and 'OZOLS' in c and 'На раме' in c)
        await s.fill('#thread textarea[name=body]', 'Добрый день! Монтаж включим в КП.'); await s.click('#thread button[type=submit]'); await s.wait_for_load_state()
        ok('staff reply moves status to "in review"', psql("SELECT status FROM rf_requests") == 'review')
        ok('client notified in Latvian about the message', (last_mail('anna@ozols.test', 'jaunumi') or {}).get('body', '').find('Reclame Fabriek jums nosūtīja ziņu') > -1)
        await s.click('text=+ Создать КП'); await s.wait_for_load_state()
        ok('quote draft pre-filled from the request', await s.locator('[data-qline]').count() == 2)
        await s.fill('[name="lines[0][unit_price]"]', '1450'); await s.fill('[name="lines[1][unit_price]"]', '320,50')
        await s.click('[data-qadd]'); await s.fill('[name="lines[2][description]"]', 'Montāža / Installation'); await s.fill('[name="lines[2][unit_price]"]', '380')
        await s.fill('[name=lead_time]', '15 darba dienas'); await s.wait_for_timeout(200)
        tot = await s.text_content('[data-ttot]'); ok('live total with 21% VAT', tot.replace('\xa0', ' ').startswith('2 989,91'), tot)
        await s.click('button[value=send]'); await s.wait_for_load_state()
        ok('quote sent, request status "quote sent"', psql("SELECT q.status||'/'||r.status FROM rf_quotes q JOIN rf_requests r ON r.id=q.request_id") == 'sent/quoted')
        ok('quote total stored correctly', psql("SELECT round(sum(qty*unit_price),2) FROM rf_quote_lines") == '2471.00')
        qno = psql("SELECT no FROM rf_quotes"); ok('quote number', qno == 'RF-Q-2026-0001', qno)
        pr = await s.request.get(B + '/admin/?a=pquote&id=1&print=1'); ok('printable quote in client language', pr.status == 200 and 'Kopā' in await pr.text())

        # ---------- client: quote waiting (lit) → accept
        await a.goto(B + '/lv/portal/'); ok('dashboard: quote waiting is lit', await a.locator('.pt-stats li.is-lit').count() == 2)
        await a.goto(B + '/lv/portal/quotes/RF-Q-2026-0001/'); c = (await a.text_content('.qdoc')).replace('\xa0', ' ')
        ok('quote page in Latvian with totals', 'Kopā' in c and '2 989,91' in c and 'PVN 21%' in c)
        await a.click('.pt-decide button[type=submit]')       # without the checkbox: browser blocks (required)
        ok('accept needs the checkbox', psql("SELECT status FROM rf_quotes") == 'sent')
        await a.fill('.pt-decide [name=name]', 'Anna Ozola'); await a.check('.pt-decide [name=agree]'); await a.click('.pt-decide button[type=submit]'); await a.wait_for_load_state()
        ok('quote accepted, signed', psql("SELECT status||'/'||decided_name FROM rf_quotes") == 'accepted/Anna Ozola' and 'Pieņēma Anna Ozola' in await a.content())
        ok('request status accepted', psql("SELECT status FROM rf_requests") == 'accepted')
        ok('staff notified of acceptance', any('ПРИНЯТО' in json.load(open(f))['subject'] for f in glob.glob(OUT + '*.json')))
        r = await a.request.post(B + '/lv/portal/quotes/RF-Q-2026-0001/', form={'do': 'accept', 'name': 'x', 'agree': '1'}); ok('POST without CSRF token refused', r.status == 400)

        # ---------- admin moves to production; client sees it
        await s.goto(B + '/admin/?a=preq&no=RF-2026-0001'); await s.select_option('[name=status]', 'production'); await s.click('.pstatus button'); await s.wait_for_load_state()
        await a.goto(B + '/lv/portal/requests/RF-2026-0001/')
        ok('client timeline: production is the current step', 'Ražošanā' in (await a.text_content('.pt-line li.is-now') or ''))

        # ---------- access control with a second client
        psql("INSERT INTO rf_clients (email, pass_hash, name, company, status, email_verified_at) VALUES ('bob@other.test', '" + subprocess.run(['php', '-r', 'echo password_hash("bob-password-1", PASSWORD_DEFAULT);'], capture_output=True, text=True).stdout + "', 'Bob', 'Other AB', 'active', now())")
        Bc = await br.new_context(); b = await Bc.new_page()
        await b.goto(B + '/sv/portal/login/'); await b.fill('[name=email]', 'bob@other.test'); await b.fill('[name=pw]', 'bob-password-1'); await b.click('form button[type=submit]'); await b.wait_for_load_state()
        ok('second client logged in (Swedish)', 'Hej Bob' in await b.content())
        r = await b.request.get(B + '/sv/portal/requests/RF-2026-0001/'); ok("other client's request is hidden", r.status == 404)
        r = await b.request.get(B + '/sv/portal/quotes/RF-Q-2026-0001/'); ok("other client's quote is hidden", r.status == 404)
        r = await b.request.get(B + f'/sv/portal/files/{fid}/'); ok("other client's file is hidden", r.status == 404)
        r = await Bc.request.get(B + '/data/_portal/files/'); ok('stored files not reachable directly', r.status in (403, 404))
        anon = await (await br.new_context()).new_page(); r = await anon.request.get(B + '/portal/requests/RF-2026-0001/', max_redirects=0); ok('anonymous visitor redirected to login', r.status == 303)
        # brute force
        for i in range(9):
            r = await anon.request.post(B + '/portal/login/', form={'email': 'anna@ozols.test', 'pw': 'wrong' + str(i), '_csrf': 'x'})
        ok('login form protected by CSRF', r.status == 400)

        # ---------- password reset
        await anon.goto(B + '/de/portal/forgot/'); await anon.fill('[name=email]', 'anna@ozols.test'); await anon.click('form button'); await anon.wait_for_load_state()
        m = last_mail('anna@ozols.test', 'reset'); ok('reset email sent (in Anna’s language)', m and 'Jaunas paroles' in m['subject'])
        await anon.goto(link_in(m, '/portal/reset/')); await anon.fill('[name=pw]', 'brand-new-pass-2'); await anon.fill('[name=pw2]', 'brand-new-pass-2'); await anon.click('form button'); await anon.wait_for_load_state()
        ok('new password set and logged in', 'Hallo, Anna' in await anon.content() or 'Anna' in await anon.content())

        # ---------- website contact form → portal
        await a.goto(B + '/nl/contact/?profile=profile-2'); await a.fill('#quote [name=name]', 'Jan de Vries'); await a.fill('#quote [name=company]', 'Vries BV'); await a.fill('#quote [name=email]', 'jan@vries.test')
        await a.fill('#quote [name=message]', 'Neon letters 40 cm, 3 stuks.'); await a.click('#quote button[type=submit]'); await a.wait_for_timeout(800)
        mid = os.path.basename(sorted(glob.glob(SITE + '/data/_inbox/2*.json'))[-1])[:-5]
        await s.goto(B + '/admin/?a=msg&id=' + mid); await s.click('text=Перенести в кабинет клиента'); await s.wait_for_load_state()
        ok('website request moved to the portal', psql("SELECT count(*) FROM rf_requests WHERE source='website'") == '1' and 'Ссылка для клиента' in await s.content())
        m = last_mail('jan@vries.test', 'portal'); ok('invite in Dutch', m and 'klantportaal' in m['subject'].lower())
        J = await br.new_context(); j = await J.new_page(); await j.goto(link_in(m, '/portal/reset/'))
        await j.fill('[name=pw]', 'jan-password-12'); await j.fill('[name=pw2]', 'jan-password-12'); await j.click('form button'); await j.wait_for_load_state()
        c = await j.content(); ok('invited client sees the request from the form', 'RF-2026-0002' in c)

        # ---------- account page
        await a.goto(B + '/lv/portal/account/'); await a.select_option('[name=lang]', 'ru'); await a.click('form:has([name=do][value=profile]) button[type=submit]'); await a.wait_for_load_state()
        ok('client changes email language', psql("SELECT lang FROM rf_clients WHERE email='anna@ozols.test'") == 'ru')

        # ---------- admin: approve mode + delete client
        await s.goto(B + '/admin/?a=pclients'); ok('admin client list', 'Ozols SIA' in await s.content() and 'Vries BV' in await s.content())
        bid = psql("SELECT id FROM rf_clients WHERE email='bob@other.test'")
        await s.goto(B + f'/admin/?a=pclient&id={bid}'); await s.fill('[name=confirm]', 'bob@other.test'); await s.click('form:has([name=do][value=delete]) button'); await s.wait_for_load_state()
        ok('client deleted with all data', psql("SELECT count(*) FROM rf_clients WHERE email='bob@other.test'") == '0')
        await s.goto(B + '/admin/?a=psettings'); ok('portal settings show connection', 'Подключено' in await s.content())

        ok('no JavaScript errors in the client browser', not errs, '; '.join(errs[:3]))
        await br.close()
    log = open('/tmp/php.log').read()
    ok('no PHP warnings or errors', not re.search(r'(PHP )?(Warning|Fatal|Notice|Deprecated)', log), re.findall(r'.*(?:Warning|Fatal|Notice|Deprecated).*', log)[:2])
    n = sum(1 for x in R if x[1]); print(f'\n{n} / {len(R)} passed')
    json.dump([{'check': a, 'pass': b, 'note': c} for a, b, c in R], open('/tmp/rfcms/portal-e2e.json', 'w'), ensure_ascii=False, indent=1)
asyncio.run(main())
