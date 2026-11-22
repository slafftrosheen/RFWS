#!/usr/bin/env python3
"""Website 2.3 · end-to-end test of the new client features (Playwright + local PostgreSQL):
saved sites, draft autosave, letter-height helper, order again, ZIP of files, design proofs (admin → client approve / changes),
activity feed + badge, live message refresh, feedback after installation, dashboard filters, access control,
compare profiles, contact clock, process line, dark theme on the portal."""
import asyncio, glob, json, os, re, subprocess, sys, io, zipfile
from playwright.async_api import async_playwright
from PIL import Image
B = 'http://127.0.0.1:8080'; SITE = '/tmp/rfcms/site'
R = []
def ok(name, cond, note=''):
    R.append((name, bool(cond), note)); print(('PASS ' if cond else 'FAIL ') + name + (f'  [{note}]' if note and not cond else ''))
def psql(sql): return subprocess.run(['psql', '-h', '127.0.0.1', '-p', '5433', '-U', 'rf', '-d', 'reclamefabriek', '-At', '-c', sql], capture_output=True, text=True).stdout.strip()
def mkclient(email, name, company, lang='en'):
    h = subprocess.run(['php', '-r', 'echo password_hash("pass-2-3-test", PASSWORD_DEFAULT);'], capture_output=True, text=True).stdout
    psql(f"INSERT INTO rf_clients (email, pass_hash, name, company, lang, status, email_verified_at) VALUES ('{email}', '{h}', '{name}', '{company}', '{lang}', 'active', now())")
async def login(pg, email, prefix=''):
    await pg.goto(B + prefix + '/portal/login/'); await pg.fill('[name=email]', email); await pg.fill('[name=pw]', 'pass-2-3-test'); await pg.click('form button[type=submit]'); await pg.wait_for_load_state()

async def main():
    psql('TRUNCATE rf_clients, rf_counters, rf_throttle RESTART IDENTITY CASCADE')
    mkclient('mara@lumo.test', 'Māra Lūse', 'Lumo SIA', 'lv'); mkclient('eve@other.test', 'Eve Other', 'Other AB', 'sv')
    Image.new('RGB', (800, 600), (200, 22, 29)).save('/tmp/rf-proof-v1.png'); Image.new('RGB', (800, 600), (28, 29, 32)).save('/tmp/rf-proof-v2.png')
    open('/tmp/rf-logo.pdf', 'wb').write(b'%PDF-1.4\n% logo\n' + b'0' * 1024)
    async with async_playwright() as p:
        br = await p.chromium.launch()
        A = await br.new_context(viewport={'width': 1280, 'height': 900}, locale='lv-LV'); a = await A.new_page()
        errs = []; a.on('pageerror', lambda e: errs.append(str(e))); a.on('console', lambda m: errs.append(m.text) if m.type == 'error' else None)
        a.on('dialog', lambda d: asyncio.ensure_future(d.accept()))
        await login(a, 'mara@lumo.test', '/lv')
        ok('client logged in, dashboard with 4 stats', '/lv/portal/' in a.url and await a.locator('.pt-stats li').count() == 4, a.url)
        ok('portal tabs: activity + sites', await a.locator('.pt-tabs a').count() == 5)

        # ---------- new request: helper, sites, draft
        await a.goto(B + '/lv/portal/requests/new/')
        ok('no saved-site picker yet', await a.locator('[data-site-pick]').count() == 0)
        await a.fill('[name="items[0][height_mm]"]', '300'); await a.wait_for_timeout(100)
        t = await a.inner_text('[data-readable-out] >> nth=0'); ok('letter-height helper: 300 mm reads from ~99 m, best to ~38 m', '99' in t and '38' in t, t)
        ok('site label hidden until "save" ticked', not await a.is_visible('.pt-site-label'))
        await a.check('[name=save_site]'); ok('site label shows after ticking', await a.is_visible('.pt-site-label'))
        await a.fill('[name=title]', 'Veikala izkārtne, Daugavpils'); await a.fill('[name="items[0][content]"]', 'LUMO'); await a.wait_for_timeout(900)
        dr = await a.evaluate("Object.keys(localStorage).filter(k => k.startsWith('rf_draft_')).length"); ok('draft saved on the device while typing', dr == 1)
        await a.goto(B + '/lv/portal/requests/new/')
        ok('draft offered on the next visit', await a.is_visible('[data-draft-note]'))
        await a.click('[data-draft-restore]'); ok('draft restored (title + sign text)', await a.input_value('[name=title]') == 'Veikala izkārtne, Daugavpils' and await a.input_value('[name="items[0][content]"]') == 'LUMO')
        await a.select_option('[name="items[0][profile_slug]"]', 'profile-2'); await a.fill('[name="items[0][height_mm]"]', '300')
        await a.fill('[name=site_address]', 'Rīgas iela 22, Daugavpils'); await a.check('[name=save_site]'); await a.fill('[name=site_label]', 'Daugavpils, Rīgas iela')
        await a.set_input_files('[name="files[]"]', ['/tmp/rf-logo.pdf'])
        await a.click('.pt-submit button'); await a.wait_for_load_state()
        ok('request sent: RF-2026-0001', 'RF-2026-0001' in a.url, a.url)
        ok('"sent" animation shown', await a.locator('.pt-sent [data-lottie]').count() == 1)
        await a.wait_for_timeout(300)
        ok('draft cleared after sending', await a.evaluate("Object.keys(localStorage).filter(k => k.startsWith('rf_draft_')).length") == 0)
        ok('site saved and linked to the request', psql("SELECT count(*) FROM rf_sites") == '1' and psql("SELECT site_id IS NOT NULL FROM rf_requests WHERE no='RF-2026-0001'") == 't')

        # ---------- sites page
        await a.goto(B + '/lv/portal/sites/'); ok('sites page lists the saved site', 'Daugavpils, Rīgas iela' in await a.content() and 'Rīgas iela 22' in await a.content())
        f = a.locator('.pt-side form'); await f.locator('[name=label]').fill('Rīga, t/c Spice'); await f.locator('[name=address]').fill('Lielirbes iela 29, Rīga'); await f.locator('[name=contact]').fill('Jānis'); await f.locator('button').click(); await a.wait_for_load_state()
        ok('second site added', psql("SELECT count(*) FROM rf_sites") == '2')
        await a.locator('.pt-site', has_text='Spice').locator('summary').click(); ed = a.locator('.pt-site', has_text='Spice').locator('.pt-edit form')
        await ed.locator('[name=notes]').fill('Pacēlājs vajadzīgs'); await ed.locator('button').click(); await a.wait_for_load_state()
        ok('site edited', psql("SELECT notes FROM rf_sites WHERE label LIKE '%Spice%'") == 'Pacēlājs vajadzīgs')
        await a.goto(B + '/lv/portal/requests/new/')
        ok('saved-site picker on the next request', await a.locator('[data-site-pick] option').count() == 3)
        await a.select_option('[data-site-pick]', label='Rīga, t/c Spice'); ok('picking a site fills the address', await a.input_value('[name=site_address]') == 'Lielirbes iela 29, Rīga')
        ok('"save address" hidden for a saved site', not await a.is_visible('[data-save-site]'))
        await a.fill('[name="items[0][content]"]', 'SPICE'); await a.click('.pt-submit button'); await a.wait_for_load_state()
        ok('second request RF-2026-0002 uses the saved site', 'RF-2026-0002' in a.url and psql("SELECT s.label FROM rf_requests r JOIN rf_sites s ON s.id = r.site_id WHERE r.no='RF-2026-0002'") == 'Rīga, t/c Spice')

        # ---------- order again + zip + copy link
        await a.goto(B + '/lv/portal/requests/RF-2026-0001/')
        ok('request tools: order again, ZIP, copy link', await a.locator('.pt-req-tools a, .pt-req-tools button').count() == 3)
        await a.click('.pt-req-tools a >> nth=0'); await a.wait_for_load_state()
        ok('order again pre-fills the signs', await a.input_value('[name="items[0][content]"]') == 'LUMO' and await a.input_value('[name="items[0][profile_slug]"]') == 'profile-2' and 'RF-2026-0001' in await a.input_value('[name=title]'))
        ok('order again: no draft prompt', not await a.is_visible('[data-draft-note]'))
        r = await a.request.get(B + '/lv/portal/requests/RF-2026-0001/files.zip')
        zb = await r.body(); names = zipfile.ZipFile(io.BytesIO(zb)).namelist() if r.status == 200 else []
        ok('ZIP of all files downloads', r.status == 200 and names == ['from-you/rf-logo.pdf'], f'{r.status} {names}')

        # ---------- admin posts a proof
        S = await br.new_context(viewport={'width': 1400, 'height': 900}); s = await S.new_page(); s.on('dialog', lambda d: asyncio.ensure_future(d.accept()))
        await s.goto(B + '/admin/'); await s.fill('[name=login]', 'tester'); await s.fill('[name=pw]', 'admin-test-pass-1'); await s.click('button[type=submit]'); await s.wait_for_load_state()
        await s.goto(B + '/admin/?a=preq&no=RF-2026-0001')
        ok('admin request page has the proofs section', await s.locator('#proofs').count() == 1)
        await s.set_input_files('#proofs [name=proof]', '/tmp/rf-proof-v1.png'); await s.fill('#proofs [name=note]', 'Lūdzu, pārbaudiet burtu augstumu.'); await s.click('#proofs button[type=submit]'); await s.wait_for_load_state()
        ok('admin: proof v1 sent', 'Макет v1 отправлен' in await s.content() and psql("SELECT status FROM rf_proofs WHERE version=1") == 'pending')
        ok('client email about the proof (Latvian)', any('v1' in json.load(open(f))['body'] and json.load(open(f))['to'] == 'mara@lumo.test' for f in glob.glob(SITE + '/data/_portal/outbox/*.json')))

        await a.goto(B + '/lv/portal/')
        ok('dashboard: attention panel lists the proof', await a.locator('.pt-att li').count() >= 1 and await a.locator('.pt-att a[href*="#proofs"]').count() == 1)
        ok('activity tab shows a new-items badge', await a.locator('.pt-tabs .pt-dot').count() == 1)
        await a.goto(B + '/lv/portal/activity/')
        c = await a.content(); ok('activity feed: proof posted (new)', await a.locator('.pt-act.is-new').count() >= 1 and 'v1' in c)
        await a.goto(B + '/lv/portal/'); ok('badge cleared after opening activity', await a.locator('.pt-tabs .pt-dot').count() == 0)
        await a.goto(B + '/lv/portal/requests/RF-2026-0001/')
        ok('proof shown with preview image', await a.locator('#proofs img').count() == 1)
        fid = psql("SELECT file_id FROM rf_proofs WHERE version=1")
        r = await a.request.get(B + f'/lv/portal/files/{fid}/view/'); ok('preview served inline as PNG, sandboxed', r.status == 200 and r.headers.get('content-type') == 'image/png' and 'sandbox' in r.headers.get('content-security-policy', ''), str(r.headers.get('content-type')))
        await a.locator('#proofs .pt-decline summary').click(); await a.locator('#proofs .pt-decline textarea').fill(' ');
        await a.locator('#proofs .pt-decline textarea').fill('Burtiem jābūt par 20 mm augstākiem.'); await a.locator('#proofs .pt-decline button').click(); await a.wait_for_load_state()
        ok('client asks for changes', psql("SELECT status||':'||decision_note FROM rf_proofs WHERE version=1") == 'changes:Burtiem jābūt par 20 mm augstākiem.')
        await s.goto(B + '/admin/?a=preq&no=RF-2026-0001'); ok('admin sees the requested changes', 'Burtiem jābūt' in await s.content() and 'Нужны правки' in await s.content())
        await s.set_input_files('#proofs [name=proof]', '/tmp/rf-proof-v2.png'); await s.click('#proofs button[type=submit]'); await s.wait_for_load_state()
        await a.goto(B + '/lv/portal/requests/RF-2026-0001/')
        ok('client sees v2 waiting, v1 kept as history', await a.locator('.pt-proof-pending').count() == 1 and await a.locator('.pt-proof').count() == 2)
        await a.locator('.pt-proof-pending [name=name]').fill('Māra Lūse'); await a.locator('.pt-proof-pending .pt-inline button').click(); await a.wait_for_load_state()
        ok('client approves v2 with name', psql("SELECT status||':'||decided_name FROM rf_proofs WHERE version=2") == 'approved:Māra Lūse')
        ok('approval visible with signature', 'Māra Lūse' in await a.inner_text('#proofs'))
        await s.goto(B + '/admin/?a=preq&no=RF-2026-0001'); ok('admin sees "approved"', 'Утверждён' in await s.content())

        # ---------- live thread refresh
        await a.goto(B + '/lv/portal/requests/RF-2026-0001/'); n0 = await a.locator('.pt-thread li').count()
        await s.fill('#thread textarea[name=body]', 'Ražošana sāksies pirmdien.'); await s.click('#thread button[type=submit]'); await s.wait_for_load_state()
        await a.evaluate("document.dispatchEvent(new Event('visibilitychange'))"); await a.wait_for_timeout(1500)
        ok('new message appears without reloading', await a.locator('.pt-thread li').count() == n0 + 1 and 'pirmdien' in await a.inner_text('.pt-thread'))
        ok('toast tells about the new message', await a.locator('.toast').count() >= 1)

        # ---------- feedback after installation
        await s.select_option('.pstatus select', 'installed'); await s.click('.pstatus button'); await s.wait_for_load_state()
        await a.goto(B + '/lv/portal/'); ok('dashboard asks for feedback', await a.locator('.pt-att a[href*="#feedback"]').count() == 1)
        await a.goto(B + '/lv/portal/requests/RF-2026-0001/#feedback')
        await a.click('label[for=rt5]'); await a.fill('#feedback textarea', 'Lieliski!'); await a.click('#feedback button[type=submit]'); await a.wait_for_load_state()
        ok('feedback saved (5/5)', psql("SELECT rating||':'||comment FROM rf_feedback") == '5:Lieliski!')
        await s.goto(B + '/admin/?a=preq&no=RF-2026-0001'); ok('admin sees the feedback', '★★★★★' in await s.content() and 'Lieliski!' in await s.content())

        # ---------- dashboard filters + search
        await a.goto(B + '/lv/portal/')
        await a.click('.pt-chips [data-f=done]'); vis = await a.locator('.pt-req:not([hidden])').count(); ok('filter "finished" shows 1', vis == 1, str(vis))
        await a.click('.pt-chips [data-f=all]'); await a.fill('[data-dash-q]', 'spice'); vis = await a.locator('.pt-req:not([hidden])').count(); ok('search "spice" finds request 2', vis == 1 and 'RF-2026-0002' in await a.inner_text('.pt-req:not([hidden])'))
        await a.fill('[data-dash-q]', 'zzzz'); ok('no-match message', await a.is_visible('[data-dash-none]'))
        r = await a.request.get(B + '/lv/portal/?f=done'); ok('filter works without JS too', (await r.text()).count('class="pt-req" data-grp="done"') == 1 and ' hidden><a' in await r.text())

        # ---------- access control (another client)
        E = await br.new_context(); e = await E.new_page(); await login(e, 'eve@other.test', '/sv')
        for path in [f'/sv/portal/files/{fid}/view/', '/sv/portal/requests/RF-2026-0001/files.zip', '/sv/portal/requests/RF-2026-0001/poll/?n=0']:
            r = await e.request.get(B + path); ok(f'other client gets 404: {path.split("/portal/")[1]}', r.status == 404, str(r.status))
        sid = psql("SELECT id FROM rf_sites WHERE label LIKE '%Spice%'")
        await e.goto(B + '/sv/portal/sites/'); await e.evaluate(f"""() => {{ const f = document.querySelector('.pt-side form'); f.querySelector('[name=id]').value = '{sid}'; f.querySelector('[name=label]').value = 'HACK'; f.querySelector('[name=address]').value = 'x'; f.submit(); }}"""); await e.wait_for_load_state()
        ok("other client cannot edit someone else's site", psql(f"SELECT label FROM rf_sites WHERE id={sid}") == 'Rīga, t/c Spice')
        ok('activity of another client is empty', 'RF-2026' not in await (await e.request.get(B + '/sv/portal/activity/')).text())

        # ---------- compare profiles
        P = await br.new_context(viewport={'width': 1280, 'height': 900}); c = await P.new_page()
        await c.goto(B + '/product-profiles/', wait_until='networkidle')
        tg = c.locator('.pcards [data-cmp]')
        for i in range(3): await tg.nth(i).click()
        await c.wait_for_timeout(300)
        ok('compare bar appears with 3 profiles', await c.is_visible('[data-cmp-bar]') and await c.locator('[data-cmp-thumbs] img').count() == 3)
        await tg.nth(3).click(); await c.wait_for_timeout(200); ok('a 4th profile is refused with a note', await c.locator('.toast').count() >= 1 and await c.evaluate("JSON.parse(localStorage.rf_cmp).length") == 3)
        await c.click('[data-cmp-open]'); await c.wait_for_timeout(300)
        ok('compare table: 3 columns, differences marked', await c.locator('.cmp-tbl thead th').count() == 3 and await c.locator('.cmp-tbl tr.diff').count() >= 1)
        await c.locator('.cmp-rm >> nth=0').click(); await c.wait_for_timeout(200); ok('remove from inside the table', await c.locator('.cmp-tbl thead th').count() == 2)
        await c.keyboard.press('Escape'); await c.goto(B + '/de/profile-2/', wait_until='networkidle'); ok('comparison kept across pages', await c.is_visible('[data-cmp-bar]'))

        # ---------- contact clock + process line + dark portal
        await c.goto(B + '/contact/', wait_until='networkidle'); ok('contact shows local factory time', re.match(r'^\d\d:\d\d$', await c.inner_text('[data-live-clock]')) is not None)
        await c.goto(B + '/', wait_until='networkidle')
        await c.locator('#process').scroll_into_view_if_needed(); h = await c.evaluate('document.body.scrollHeight')
        for y in range(0, 4000, 300): await c.evaluate(f'scrollTo(0,{y})'); await c.wait_for_timeout(60)
        await c.wait_for_timeout(1200)
        ok('process line: 6 steps, animations loaded', await c.locator('.proc-step').count() == 6 and await c.locator('.proc-step svg').count() >= 6)
        ok('process steps light up along the line', await c.locator('.proc-step.is-on').count() == 6)
        D = await br.new_context(color_scheme='dark'); dk = await D.new_page(); await login(dk, 'mara@lumo.test', '/lv')
        qid = psql("SELECT 1")
        await dk.goto(B + '/lv/portal/'); bg = await dk.evaluate("getComputedStyle(document.querySelector('.pt-card, .pt-req a')).backgroundColor")
        ok('portal follows the dark theme', bg == 'rgb(26, 27, 31)', bg)
        ok('no JavaScript errors in the client browser', not errs, '; '.join(errs[:3]))
        log = open('/tmp/php8080.log').read()
        bad = [l for l in log.splitlines() if re.search(r'Warning|Fatal|Notice|Deprecated', l)]
        ok('no PHP warnings or errors', not bad, '; '.join(bad[:2]))
        n = sum(1 for x in R if x[1]); print(f'\n{n} / {len(R)} passed', flush=True)
        json.dump([{'check': a, 'pass': b, 'note': c} for a, b, c in R], open('/tmp/rfcms/portal23-e2e.json', 'w'), ensure_ascii=False, indent=1)
        os._exit(0)   # closing Chromium can hang in this container; results are already written
asyncio.run(main())
