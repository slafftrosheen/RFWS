#!/usr/bin/env python3
"""Website 2.4 · end-to-end test (Playwright + local PostgreSQL): night preview on a profile page → "Request this sign" → login → request with the
preview attached; preview dialog inside the request form; extra notification e-mails; admin: owner + reminder + internal notes (never shown to the
client), reply templates in the client's language, price list → quote line, installation date → client confirms → .ics + calendar,
production board drag & drop, CSV export, dashboard figures; client activity feed."""
import asyncio, glob, json, os, re, subprocess, datetime
from playwright.async_api import async_playwright
B = 'http://127.0.0.1:8080'; SITE = '/tmp/rfcms/site'
R = []
def ok(name, cond, note=''):
    R.append((name, bool(cond), note)); print(('PASS ' if cond else 'FAIL ') + name + (f'  [{note}]' if note and not cond else ''))
def psql(sql): return subprocess.run(['psql', '-h', '127.0.0.1', '-p', '5433', '-U', 'rf', '-d', 'reclamefabriek', '-At', '-c', sql], capture_output=True, text=True).stdout.strip()
def mkclient(email, name, company, lang='en'):
    h = subprocess.run(['php', '-r', 'echo password_hash("pass-2-4-test", PASSWORD_DEFAULT);'], capture_output=True, text=True).stdout
    psql(f"INSERT INTO rf_clients (email, pass_hash, name, company, lang, status, email_verified_at) VALUES ('{email}', '{h}', '{name}', '{company}', '{lang}', 'active', now())")

async def main():
    psql('TRUNCATE rf_clients, rf_counters, rf_throttle RESTART IDENTITY CASCADE')
    for f in ['replies.json', 'pricelist.json']:
        if os.path.exists(f'{SITE}/data/{f}'): os.remove(f'{SITE}/data/{f}')
    for f in glob.glob(f'{SITE}/data/_portal/outbox/*.json'): os.remove(f)
    mkclient('mara@lumo.test', 'Māra Lūse', 'Lumo SIA', 'lv')
    log0 = os.path.getsize('/tmp/php8080.log') if os.path.exists('/tmp/php8080.log') else 0
    async with async_playwright() as p:
        br = await p.chromium.launch()
        A = await br.new_context(viewport={'width': 1366, 'height': 900}, locale='lv-LV', accept_downloads=True); a = await A.new_page()
        errs = []; a.on('pageerror', lambda e: errs.append(str(e))); a.on('console', lambda m: errs.append(m.text) if m.type == 'error' else None)
        a.on('dialog', lambda d: asyncio.ensure_future(d.accept()))
        # ---------- night preview on a profile page
        await a.goto(B + '/lv/profile-8/'); await a.wait_for_timeout(400)
        ok('preview shown on profile page (light preset from profile: front + side)', await a.locator('.pv.pv-on').count() == 1 and await a.input_value('[data-pv-light]') == 'front_side')
        await a.fill('[data-pv-text]', 'LUMO'); await a.fill('[data-pv-h]', '600')
        await a.check('input[name="pv1-led"][value="#EAF2FF"]', force=True)
        await a.click('[data-pv-night]'); await a.wait_for_timeout(900)
        ok('switch on: pressed + night label', await a.get_attribute('[data-pv-night]', 'aria-pressed') == 'true')
        alt = await a.get_attribute('[data-pv-cv]', 'aria-label'); ok('canvas describes the sign in Latvian', 'LUMO' in alt and 'naktī' in alt and 'auksti balta' in alt, alt)
        ok('scale line uses letter height', '600 mm' in await a.inner_text('[data-pv-scale]'))
        async with a.expect_download() as dl: await a.click('[data-pv-png]')
        d = await dl.value; ok('download picture (JPEG, night)', d.suggested_filename == 'sign-preview-lumo-night.jpg', d.suggested_filename)
        await a.click('[data-pv-req]'); await a.wait_for_load_state()
        ok('request this sign → login first', '/lv/portal/login/' in a.url, a.url)
        await a.fill('[name=email]', 'mara@lumo.test'); await a.fill('[name=pw]', 'pass-2-4-test'); await a.click('form button[type=submit]'); await a.wait_for_load_state(); await a.wait_for_timeout(500)
        ok('after login: new request with the profile + pv flag', '/lv/portal/requests/new/' in a.url and 'pv=1' in a.url, a.url)
        ok('preview note shown', await a.is_visible('[data-pv-note]'))
        nf = await a.evaluate("document.querySelector('input[type=file][data-files]').files.length"); ok('preview JPEG attached to the form', nf == 1, str(nf))
        ok('item filled: text + height + notes summary', await a.input_value('[name="items[0][content]"]') == 'LUMO' and await a.input_value('[name="items[0][height_mm]"]') == '600' and 'Nakts priekšskatījums' in await a.input_value('[name="items[0][notes]"]'))
        ok('profile picked', await a.input_value('[name="items[0][profile_slug]"]') == 'profile-8')
        # preview dialog for a second item
        await a.click('[data-item-add]'); await a.select_option('[name="items[1][profile_slug]"]', 'profile-3'); await a.fill('[name="items[1][content]"]', 'Kafija')
        await a.click('[data-item]:nth-child(2) [data-pv-open]'); await a.wait_for_timeout(300)
        ok('dialog opens prefilled (text, corona from profile)', await a.is_visible('[data-pv-dlg]') and await a.input_value('[data-pv-dlg] [data-pv-text]') == 'Kafija' and await a.input_value('[data-pv-dlg] [data-pv-light]') == 'corona')
        await a.click('[data-pv-dlg] [data-pv-night]'); await a.wait_for_timeout(700); await a.click('[data-pv-attach]'); await a.wait_for_timeout(300)
        nf = await a.evaluate("document.querySelector('input[type=file][data-files]').files.length")
        ok('second preview attached, dialog closed, confirmation shown', nf == 2 and not await a.is_visible('[data-pv-dlg]') and await a.is_visible('[data-pv-done]'), str(nf))
        await a.fill('[name=title]', 'Lumo, Rīgas iela'); await a.fill('[name=site_address]', 'Rīgas iela 22, Daugavpils')
        await a.click('.pt-submit button'); await a.wait_for_load_state()
        ok('request sent', 'RF-2026-0001' in a.url, a.url)
        ok('2 preview files stored', psql("SELECT count(*) FROM rf_files WHERE original_name LIKE 'sign-preview-%'") == '2')
        ok('item notes keep the preview summary', 'Nakts priekšskatījums' in psql("SELECT notes FROM rf_request_items WHERE position = 0"))
        ok('timeline nodes carry their index (power-up order)', await a.locator('.pt-line li[style*="--i:"]').count() == 7)
        # extra notification e-mails
        await a.goto(B + '/lv/portal/account/'); await a.fill('[name=cc_emails]', 'nope'); await a.click('form:has([name=cc_emails]) button[type=submit]'); await a.wait_for_load_state()
        ok('bad extra e-mail refused', await a.locator('.pt-err').count() == 1)
        await a.fill('[name=cc_emails]', 'buh@lumo.test; Site@Lumo.test, mara@lumo.test'); await a.click('form:has([name=cc_emails]) button[type=submit]'); await a.wait_for_load_state()
        ok('extra e-mails saved (cleaned, own address dropped)', psql("SELECT cc_emails FROM rf_clients WHERE email = 'mara@lumo.test'") == 'buh@lumo.test, site@lumo.test')

        # ---------- admin
        S = await br.new_context(viewport={'width': 1440, 'height': 950}, accept_downloads=True); s = await S.new_page()
        s.on('pageerror', lambda e: errs.append('admin: ' + str(e))); s.on('dialog', lambda d: asyncio.ensure_future(d.accept()))
        await s.goto(B + '/admin/'); await s.fill('[name=login]', 'tester'); await s.fill('[name=pw]', 'admin-test-pass-1'); await s.click('button[type=submit]'); await s.wait_for_load_state()
        ok('dashboard shows portal figures', await s.locator('h2.dh').count() == 2 and await s.locator('.mbars li').count() == 6)
        # reply template + price list
        await s.goto(B + '/admin/?a=preplies&id=new'); await s.fill('[name=title]', 'Vektora logo'); await s.fill('[name="body[en]"]', 'Hello {name}, please send your logo as a vector file for {no}.')
        await s.fill('[name="body[lv]"]', 'Labdien, {name}! Lūdzu, atsūtiet logo vektorā pieprasījumam {no}.'); await s.click('form.card button[type=submit]'); await s.wait_for_load_state()
        ok('reply template saved', 'Vektora logo' in await s.content())
        await s.goto(B + '/admin/?a=pprices&id=new'); await s.fill('[name=name]', 'Montāža līdz 3 m'); await s.fill('[name=group]', 'Монтаж'); await s.fill('[name=unit]', 'set'); await s.fill('[name=price]', '180,50')
        await s.fill('[name="desc[en]"]', 'Installation on the facade, up to 3 m'); await s.fill('[name="desc[lv]"]', 'Montāža uz fasādes līdz 3 m'); await s.click('form.card button[type=submit]'); await s.wait_for_load_state()
        ok('price item saved (180,50 → 180.5)', '180,50' in await s.inner_text('.tbl'))
        await s.goto(B + '/admin/?a=preq&no=RF-2026-0001')
        ok('internal card + install card on the request', await s.locator('#internal').count() == 1 and await s.locator('#install').count() == 1)
        await s.select_option('[data-reply-pick]', index=1); body = await s.input_value('[data-reply-body]')
        ok('template inserted in Latvian with name + number', body.startswith('Labdien, Māra Lūse!') and 'RF-2026-0001' in body, body)
        await s.click('form.preply:has([data-reply-body]) button[type=submit]'); await s.wait_for_load_state()
        today = datetime.date.today().isoformat()
        await s.select_option('#internal [name=assignee]', 'tester'); await s.fill('#internal [name=follow_up]', today); await s.click('#internal .pint-f button'); await s.wait_for_load_state()
        await s.fill('#internal [name=body]', 'Zvanīt pēc 15:00 — секрет для клиента'); await s.click('#internal .pint-n button'); await s.wait_for_load_state()
        ok('owner, reminder and note saved', psql("SELECT assignee || ' ' || follow_up FROM rf_requests") == f'tester {today}' and psql('SELECT count(*) FROM rf_notes') == '1')
        await s.goto(B + '/admin/?a=portal&st=follow'); ok('"remind today" filter lists it', 'RF-2026-0001' in await s.inner_text('.tbl'))
        await s.goto(B + '/admin/?a=portal&st=mine'); ok('"mine" filter lists it', 'RF-2026-0001' in await s.inner_text('.tbl'))
        await s.goto(B + '/admin/'); ok('dashboard "today" lists the reminder', 'RF-2026-0001' in await s.inner_text('.grid2'))
        # quote with a price-list line
        await s.goto(B + '/admin/?a=preq&no=RF-2026-0001'); await s.click('form:has([value=newquote]) button'); await s.wait_for_load_state()
        await s.select_option('[data-price-pick]', index=1); await s.click('[data-price-add]')
        lines = await s.locator('[data-qline]').count()
        last = s.locator('[data-qline]').last
        ok('price line added in the client language', await last.locator('textarea').input_value() == 'Montāža uz fasādes līdz 3 m' and await last.locator('[data-p]').input_value() == '180.5', str(lines))
        await s.click('button[value=save]'); await s.wait_for_load_state()
        ok('quote draft saved with the price line', psql("SELECT count(*) FROM rf_quote_lines WHERE unit_price = 180.5 AND unit = 'set'") == '1')
        # installation date
        tm = (datetime.date.today() + datetime.timedelta(days=2)).isoformat()
        await s.goto(B + '/admin/?a=preq&no=RF-2026-0001'); await s.fill('#install [name=date]', tm); await s.fill('#install [name=time]', '09:30'); await s.fill('#install [name=hours]', '3')
        await s.fill('#install [name=crew]', 'Andrejs, pacēlājs 18 m'); await s.click('#install button[type=submit]:not([name=clear])'); await s.wait_for_load_state()
        ok('installation proposed', psql("SELECT install_state || ' ' || to_char(install_at AT TIME ZONE 'Europe/Riga', 'HH24:MI') FROM rf_requests") == 'proposed 09:30')
        out = [json.load(open(f)) for f in glob.glob(f'{SITE}/data/_portal/outbox/*.json')]
        ok('client + 2 extra addresses got the installation e-mail', {'mara@lumo.test', 'buh@lumo.test', 'site@lumo.test'} <= {m['to'] for m in out if 'montāž' in m['body'].lower() or 'uzstādīt' in m['body']}, str([m['to'] for m in out]))
        await s.goto(B + '/admin/?a=pcal&m=' + tm[:7]); ok('calendar shows the proposed installation', await s.locator('.cal .cal-e.cal-proposed').count() == 1)
        # client confirms
        await a.goto(B + '/lv/portal/'); ok('client attention: confirm installation', await a.locator('.pt-att a[href$="#install"]').count() == 1)
        await a.goto(B + '/lv/portal/requests/RF-2026-0001/'); html = await a.content()
        ok('internal note never shown to the client', 'секрет' not in html and 'Zvanīt pēc' not in html)
        ok('installation card with crew', await a.locator('#install').count() == 1 and 'Andrejs' in await a.inner_text('#install'))
        async with a.expect_download() as dl: await a.click('#install a[download]')
        ics = open(await (await dl.value).path(), encoding='utf-8').read()
        ok('.ics: tentative event with start, summary and address', 'BEGIN:VEVENT' in ics and 'STATUS:TENTATIVE' in ics and 'DTSTART:' in ics and 'Rīgas iela 22' in ics and 'RF-2026-0001' in ics)
        await a.click('#install form:has([value=install_ok]) button'); await a.wait_for_load_state()
        ok('client confirmed the date', psql('SELECT install_state FROM rf_requests') == 'confirmed' and await a.locator('.pt-install-confirmed').count() == 1)
        await a.goto(B + '/lv/portal/activity/'); act = await a.inner_text('.pt-acts')
        ok('activity feed shows installation, hides staff-only events', 'Montāža' in act and 'assigned' not in act)
        await s.goto(B + '/admin/?a=pcal&m=' + tm[:7]); ok('calendar shows it confirmed', await s.locator('.cal .cal-e.cal-confirmed').count() == 1)
        # board
        await s.goto(B + '/admin/?a=pboard'); ok('board: card in "review" column', await s.locator('[data-kbdrop=review] .kb-card').count() == 1)
        ok('card flags: installation ✓ and owner initials', '✓' in await s.inner_text('.kb-card') and await s.locator('.kb-card .kb-who').count() == 1)
        await s.drag_and_drop('.kb-card', '[data-kbdrop=production]'); await s.wait_for_timeout(900)
        ok('drag & drop → production (saved)', psql('SELECT status FROM rf_requests') == 'production')
        await s.select_option('.kb-card select', 'ready'); await s.click('.kb-card .kb-move button'); await s.wait_for_timeout(900)
        ok('keyboard path (select + →) → ready', psql('SELECT status FROM rf_requests') == 'ready' and await s.locator('[data-kbdrop=ready] .kb-card').count() == 1)
        await s.reload(); ok('board after reload keeps the column', await s.locator('[data-kbdrop=ready] .kb-card').count() == 1)
        # csv
        async with s.expect_download() as dl: await s.goto(B + '/admin/?a=pcsv') if False else await s.evaluate("location.href='/admin/?a=pcsv'")
        csv = open(await (await dl.value).path(), encoding='utf-8-sig').read()
        ok('CSV export: header + row (Excel-friendly ;)', csv.startswith('Номер;Проект;Статус') and 'RF-2026-0001;' in csv and 'Готово' in csv)
        ok('no JS errors', not errs, '; '.join(errs)[:400])
        log = open('/tmp/php8080.log', errors='ignore').read()[log0:]
        bad = [l for l in log.splitlines() if 'PHP Warning' in l or 'PHP Fatal' in l or 'PHP Deprecated' in l]
        ok('no PHP warnings', not bad, ' | '.join(bad[:3]))
        await a.goto(B + '/lv/portal/requests/new/'); await a.screenshot(path='/tmp/shot-form.png')
    n = sum(1 for r in R if r[1]); print(f'\n{n}/{len(R)} passed'); os._exit(0 if n == len(R) else 1)
asyncio.run(main())
