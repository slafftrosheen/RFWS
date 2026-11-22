#!/usr/bin/env python3
"""Website 2.4 · demo data + preview screenshots + motion preview video (local server on :8090)."""
import asyncio, os, subprocess, datetime, json, glob
from playwright.async_api import async_playwright
B = 'http://127.0.0.1:8090'; OUT = '/tmp/rf24shots'; os.makedirs(OUT, exist_ok=True)
SITE = '/tmp/rfcms/site'
def psql(sql): return subprocess.run(['psql', '-h', '127.0.0.1', '-p', '5433', '-U', 'rf', '-d', 'reclamefabriek', '-At', '-c', sql], capture_output=True, text=True).stdout.strip()
def seed():
    psql('TRUNCATE rf_clients, rf_counters, rf_throttle RESTART IDENTITY CASCADE')
    h = subprocess.run(['php', '-r', 'echo password_hash("pass-2-4-test", PASSWORD_DEFAULT);'], capture_output=True, text=True).stdout
    C = [('mara@lumo.test', 'Māra Lūse', 'Lumo SIA', 'lv'), ('jan@demolen.test', 'Jan de Vries', 'Bakkerij De Molen', 'nl'), ('anna@nordlicht.test', 'Anna Weber', 'Nordlicht GmbH', 'de'),
         ('ilze@ozols.test', 'Ilze Ozola', 'Kafejnīca Ozols', 'lv'), ('erik@fjord.test', 'Erik Lund', 'Fjord Optik AB', 'sv')]
    for e, n, c, l in C: psql(f"INSERT INTO rf_clients (email, pass_hash, name, company, lang, status, email_verified_at) VALUES ('{e}', '{h}', '{n}', '{c}', '{l}', 'active', now())")
    t = datetime.date.today(); D = lambda n: (t + datetime.timedelta(days=n)).isoformat()
    R = [  # client, title, status, deadline, assignee, follow, install(days, time, state, crew), items
        (1, 'Lumo, Rīgas iela', 'ready', D(6), 'tester', None, (2, '09:30', 'proposed', 'Andrejs, Sergejs · pacēlājs 18 m'), [('profile-8', 'LUMO', 600)]),
        (2, 'Gevelletters De Molen', 'production', D(4), 'tester', D(0), (5, '08:00', 'confirmed', 'Andrejs'), [('profile-3', 'DE MOLEN', 450)]),
        (3, 'Nordlicht Leuchtkasten', 'accepted', D(21), '', None, None, [('casette-type', 'NORDLICHT', 500)]),
        (4, 'Ozols, Saules iela', 'quoted', D(30), 'tester', D(-1), None, [('profile-7-lux', 'OZOLS', 350), ('profile-2', 'kafija', 200)]),
        (5, 'Fjord Optik, Kungsgatan', 'review', None, '', None, None, [('profile-4', 'FJORD OPTIK', 300)]),
        (1, 'Lumo, Daugavpils noliktava', 'new', D(40), '', None, None, [('profile-1-2', 'LUMO', 800)]),
        (3, 'Nordlicht Filiale Hamburg', 'production', D(-2), 'tester', None, (9, '10:00', 'change', 'Sergejs'), [('profile-7-3', 'NORDLICHT', 700)]),
        (2, 'De Molen bezorgpunt', 'installed', None, '', None, (-6, '09:00', 'confirmed', 'Andrejs'), [('profile-5', 'DE MOLEN', 300)]),
    ]
    for i, (cid, title, st, dl, asg, fu, ins, items) in enumerate(R, 1):
        no = f'RF-{t.year}-{i:04d}'
        ia = f"'{D(ins[0])} {ins[1]}'::timestamptz" if ins else 'NULL'
        rid = psql(f"INSERT INTO rf_requests (no, client_id, title, status, deadline, assignee, follow_up, install_at, install_hours, install_state, install_crew, site_address, staff_seen_at, created_at) VALUES ('{no}', {cid}, '{title}', '{st}', {repr(dl) if dl else 'NULL'}, '{asg}', {repr(fu) if fu else 'NULL'}, {ia}, 4, '{ins[2] if ins else ''}', '{ins[3] if ins else ''}', 'Rīgas iela 22, Daugavpils', now(), now() - interval '{(len(R) - i) * 17} days') RETURNING id").splitlines()[0]
        for j, (sl, txt, hmm) in enumerate(items): psql(f"INSERT INTO rf_request_items (request_id, position, profile_slug, content, height_mm, qty) VALUES ({rid}, {j}, '{sl}', '{txt}', {hmm}, 1)")
        psql(f"INSERT INTO rf_events (request_id, kind, data) VALUES ({rid}, 'created', '{{\"by\":\"client\"}}')")
    psql(f"INSERT INTO rf_counters (k, n) VALUES ('RF-{t.year}', {len(R)})")
    psql("INSERT INTO rf_notes (request_id, author, body) VALUES (1, 'Тестер', 'Клиент просил позвонить после 15:00. Замер фасада сделан, крепление на кирпич.')")
    psql("INSERT INTO rf_messages (request_id, author_kind, author_name, body, created_at) VALUES (4, 'client', 'Ilze Ozola', 'Vai varam neona burtus padarīt siltāk baltus?', now())")
    # a proof waiting + a quote sent for the board flags
    psql("INSERT INTO rf_quotes (request_id, no, status, sent_at, valid_until) VALUES (4, 'RF-Q-%d-0001', 'sent', now(), current_date + 30)" % t.year)
    psql("INSERT INTO rf_quote_lines (quote_id, description, qty, unit, unit_price) VALUES (1, 'Profile 7 LUX — OZOLS, 350 mm', 1, 'set', 1840), (1, 'Profile 2 — kafija, 200 mm', 1, 'set', 690)")
    json.dump([{'id': 'a1', 'name': 'Монтаж на фасад до 3 м', 'group': 'Монтаж', 'unit': 'set', 'price': 180.5, 'profile': '', 'desc': {'en': 'Installation on the facade, up to 3 m', 'lv': 'Montāža uz fasādes līdz 3 m', 'ru': 'Монтаж на фасад до 3 м', 'nl': 'Montage op de gevel, tot 3 m', 'de': 'Montage an der Fassade, bis 3 m', 'sv': 'Montering på fasad, upp till 3 m'}},
               {'id': 'a2', 'name': 'Автовышка 18 м, смена', 'group': 'Монтаж', 'unit': 'day', 'price': 320, 'profile': '', 'desc': {'en': 'Aerial platform 18 m, one shift', 'lv': 'Autopacēlājs 18 m, viena maiņa', 'ru': 'Автовышка 18 м, одна смена', 'nl': 'Hoogwerker 18 m, één dienst', 'de': 'Hubsteiger 18 m, eine Schicht', 'sv': 'Skylift 18 m, ett skift'}},
               {'id': 'a3', 'name': 'Профиль 4, за мм высоты буквы', 'group': 'Буквы', 'unit': 'mm', 'price': 1.9, 'profile': 'profile-4', 'desc': {'en': 'Profile 4 frontlit letters, per mm of letter height', 'lv': 'Profils 4, burti ar priekšējo gaismu, par mm burta augstuma', 'ru': 'Профиль 4, буквы с лицевой подсветкой, за мм высоты', 'nl': 'Profiel 4 frontverlichte letters, per mm letterhoogte', 'de': 'Profil 4 frontbeleuchtete Buchstaben, je mm Buchstabenhöhe', 'sv': 'Profil 4 framljusa bokstäver, per mm bokstavshöjd'}}],
              open(f'{SITE}/data/pricelist.json', 'w'), ensure_ascii=False, indent=1)
    json.dump([{'id': 'r1', 'title': 'Нужен логотип в векторе', 'body': {'en': 'Hello {name}, thank you for request {no}. Could you send your logo as a vector file (AI, EPS, PDF or SVG)? Then we can draw the letters to the exact shape.', 'lv': 'Labdien, {name}! Paldies par pieprasījumu {no}. Vai varat atsūtīt logo vektorformātā (AI, EPS, PDF vai SVG)? Tad varēsim uzzīmēt burtus precīzā formā.', 'ru': 'Здравствуйте, {name}! Спасибо за запрос {no}. Пришлите, пожалуйста, логотип в векторе (AI, EPS, PDF или SVG), тогда мы начертим буквы точно по форме.', 'nl': 'Hallo {name}, bedankt voor aanvraag {no}. Kunt u uw logo als vectorbestand sturen (AI, EPS, PDF of SVG)? Dan tekenen we de letters precies op maat.', 'de': 'Hallo {name}, danke für Anfrage {no}. Können Sie Ihr Logo als Vektordatei senden (AI, EPS, PDF oder SVG)? Dann zeichnen wir die Buchstaben genau nach.', 'sv': 'Hej {name}, tack för förfrågan {no}. Kan ni skicka logotypen som vektorfil (AI, EPS, PDF eller SVG)? Då ritar vi bokstäverna exakt.'}}],
              open(f'{SITE}/data/replies.json', 'w'), ensure_ascii=False, indent=1)

async def preview_shot(pg, path, text, light, wall, led=None, face=None, night=True, w=None):
    await pg.fill('[data-pv-text]', text); await pg.select_option('[data-pv-light]', light)
    await pg.check(f'input[name="pv1-wall"][value="{wall}"]', force=True)
    if led: await pg.check(f'input[name="pv1-led"][value="{led}"]', force=True)
    if face: await pg.check(f'input[name="pv1-face"][value="{face}"]', force=True)
    pressed = await pg.get_attribute('[data-pv-night]', 'aria-pressed') == 'true'
    if pressed != night: await pg.click('[data-pv-night]')
    await pg.wait_for_timeout(1000)
    await pg.locator('.pv-stage').screenshot(path=f'{OUT}/{path}', type='jpeg', quality=88)

async def main():
    seed()
    async with async_playwright() as p:
        br = await p.chromium.launch()
        ctx = await br.new_context(viewport={'width': 1440, 'height': 900}, device_scale_factor=1)
        pg = await ctx.new_page()
        await pg.goto(B + '/profile-7-lux/'); await pg.evaluate("localStorage.setItem('rf_theme','light')"); await pg.reload()
        await pg.locator('#preview').scroll_into_view_if_needed(); await pg.wait_for_timeout(1500)
        await pg.fill('[data-pv-text]', 'LUMO'); await pg.wait_for_timeout(300)
        if await pg.get_attribute('[data-pv-night]', 'aria-pressed') == 'true': await pg.click('[data-pv-night]')
        await pg.wait_for_timeout(400)
        await pg.locator('#preview').screenshot(path=f'{OUT}/pv-section-day.jpg', type='jpeg', quality=85)
        await preview_shot(pg, 'pv-frontlit-brick-night.jpg', 'LUMO', 'frontlit', 'brick')
        await preview_shot(pg, 'pv-corona-plaster-night.jpg', 'OZOLS', 'corona', 'plaster', led='#FFD49A', face='#1C1D20')
        await preview_shot(pg, 'pv-neon-glass-night.jpg', 'open', 'neon', 'glass', led='#FF3B30', face='#F4F4F2')
        await preview_shot(pg, 'pv-lightbox-panel-night.jpg', 'NORDLICHT', 'lightbox', 'panel', led='#EAF2FF', face='#1E4FA3')
        await preview_shot(pg, 'pv-front-side-brick-day.jpg', 'DE MOLEN', 'front_side', 'brick', led='#FFB547', face='#C8161D', night=False)
        await pg.click('[data-pv-night]'); await pg.wait_for_timeout(1000); await pg.locator('#preview').screenshot(path=f'{OUT}/pv-section-night.jpg', type='jpeg', quality=85)
        # phone
        ph = await br.new_context(viewport={'width': 390, 'height': 844}, device_scale_factor=2, is_mobile=True, has_touch=True); m = await ph.new_page()
        await m.goto(B + '/lv/profile-3/'); await m.locator('#preview').scroll_into_view_if_needed(); await m.wait_for_timeout(2200)
        await m.fill('[data-pv-text]', 'OZOLS'); await m.wait_for_timeout(300)
        await m.evaluate("document.querySelector('#preview').scrollIntoView()"); await m.wait_for_timeout(400)
        await m.screenshot(path=f'{OUT}/phone-lv-preview-night.jpg', type='jpeg', quality=85)
        # client portal
        c = await br.new_context(viewport={'width': 1440, 'height': 950}, locale='lv-LV'); cp = await c.new_page()
        await cp.goto(B + '/lv/portal/login/'); await cp.fill('[name=email]', 'mara@lumo.test'); await cp.fill('[name=pw]', 'pass-2-4-test'); await cp.click('form button[type=submit]'); await cp.wait_for_load_state()
        await cp.screenshot(path=f'{OUT}/portal-lv-dashboard-install.jpg', type='jpeg', quality=85)
        await cp.goto(B + '/lv/portal/requests/RF-%d-0001/' % datetime.date.today().year); await cp.wait_for_timeout(1400)
        await cp.screenshot(path=f'{OUT}/portal-lv-request-install.jpg', type='jpeg', quality=85)
        await cp.goto(B + '/lv/portal/requests/new/?p=profile-4'); await cp.fill('[name="items[0][content]"]', 'LUMO'); await cp.fill('[name="items[0][height_mm]"]', '500')
        await cp.click('[data-pv-open]'); await cp.wait_for_timeout(300); await cp.click('[data-pv-dlg] [data-pv-night]'); await cp.wait_for_timeout(1100)
        await cp.screenshot(path=f'{OUT}/portal-lv-preview-dialog.jpg', type='jpeg', quality=85)
        # admin
        a = await br.new_context(viewport={'width': 1600, 'height': 1000}); ap = await a.new_page()
        await ap.goto(B + '/admin/'); await ap.fill('[name=login]', 'tester'); await ap.fill('[name=pw]', 'admin-test-pass-1'); await ap.click('button[type=submit]'); await ap.wait_for_load_state()
        await ap.screenshot(path=f'{OUT}/admin-dashboard.jpg', type='jpeg', quality=85, full_page=True)
        await ap.goto(B + '/admin/?a=pboard'); await ap.screenshot(path=f'{OUT}/admin-board.jpg', type='jpeg', quality=85)
        await ap.goto(B + '/admin/?a=pcal'); await ap.screenshot(path=f'{OUT}/admin-calendar.jpg', type='jpeg', quality=85, full_page=True)
        await ap.goto(B + '/admin/?a=preq&no=RF-%d-0001' % datetime.date.today().year); await ap.select_option('[data-reply-pick]', index=1)
        await ap.screenshot(path=f'{OUT}/admin-request-internal-install.jpg', type='jpeg', quality=85, full_page=True)
        await ap.goto(B + '/admin/?a=preq&no=RF-%d-0004' % datetime.date.today().year); await ap.click('form:has([value=newquote]) button'); await ap.wait_for_load_state()
        await ap.select_option('[data-price-pick]', index=1); await ap.click('[data-price-add]'); await ap.select_option('[data-price-pick]', index=2); await ap.click('[data-price-add]'); await ap.wait_for_timeout(300)
        await ap.screenshot(path=f'{OUT}/admin-quote-pricelist.jpg', type='jpeg', quality=85, full_page=True)
        await ap.goto(B + '/admin/?a=pprices'); await ap.screenshot(path=f'{OUT}/admin-pricelist.jpg', type='jpeg', quality=85)
        # motion preview video
        v = await br.new_context(viewport={'width': 1280, 'height': 720}, record_video_dir='/tmp/rf24vid', record_video_size={'width': 1280, 'height': 720}); vp = await v.new_page()
        await vp.goto(B + '/profile-7-lux/'); await vp.evaluate("localStorage.setItem('rf_theme','light')"); await vp.reload()
        await vp.evaluate("document.querySelector('#preview .pv').scrollIntoView({block:'center'})"); await vp.wait_for_timeout(2600)
        await vp.click('[data-pv-night]'); await vp.wait_for_timeout(500); await vp.fill('[data-pv-text]', '')
        for ch in 'LUMO': await vp.type('[data-pv-text]', ch); await vp.wait_for_timeout(180)
        await vp.wait_for_timeout(600); await vp.click('[data-pv-night]'); await vp.wait_for_timeout(2200)
        for L, wl, led in [('corona', 'plaster', '#FFD49A'), ('neon', 'glass', '#FF3B30'), ('front_side', 'brick', '#FFB547'), ('lightbox', 'panel', '#EAF2FF')]:
            await vp.select_option('[data-pv-light]', L); await vp.check(f'input[name="pv1-wall"][value="{wl}"]', force=True); await vp.check(f'input[name="pv1-led"][value="{led}"]', force=True)
            await vp.click('[data-pv-night]'); await vp.wait_for_timeout(500); await vp.click('[data-pv-night]'); await vp.wait_for_timeout(2000)
        box = await vp.locator('[data-pv-cv]').bounding_box(); cx, cy = box['x'] + box['width'] / 2, box['y'] + box['height'] * 0.45
        await vp.mouse.move(cx, cy); await vp.mouse.down()
        for k in range(20): await vp.mouse.move(cx - k * 9, cy - k * 4); await vp.wait_for_timeout(30)
        await vp.mouse.up(); await vp.wait_for_timeout(1200)
        await vp.goto(B + '/admin/'); await vp.fill('[name=login]', 'tester'); await vp.fill('[name=pw]', 'admin-test-pass-1'); await vp.click('button[type=submit]'); await vp.wait_for_load_state()
        await vp.goto(B + '/admin/?a=pboard'); await vp.wait_for_timeout(1500)
        src = vp.locator('[data-kbdrop=review] .kb-card').first; await src.scroll_into_view_if_needed()
        sb = await src.bounding_box(); tb = await vp.locator('[data-kbdrop=quoted]').bounding_box()
        await vp.mouse.move(sb['x'] + 40, sb['y'] + 20); await vp.mouse.down()
        for k in range(1, 25): await vp.mouse.move(sb['x'] + 40 + (tb['x'] + 60 - sb['x'] - 40) * k / 24, sb['y'] + 20 + (tb['y'] + 30 - sb['y'] - 20) * k / 24); await vp.wait_for_timeout(35)
        await vp.mouse.up(); await vp.wait_for_timeout(1800)
        await vp.goto(B + '/lv/portal/login/'); await vp.wait_for_timeout(200)
        await vp.fill('[name=email]', 'mara@lumo.test'); await vp.fill('[name=pw]', 'pass-2-4-test'); await vp.click('form button[type=submit]'); await vp.wait_for_load_state()
        await vp.goto(B + '/lv/portal/requests/RF-%d-0001/' % datetime.date.today().year); await vp.wait_for_timeout(2600)
        await vp.evaluate("document.querySelector('#install').scrollIntoView({block:'center', behavior:'smooth'})"); await vp.wait_for_timeout(1800)
        await vp.click('#install form:has([value=install_ok]) button'); await vp.wait_for_load_state(); await vp.wait_for_timeout(2200)
        vpath = await vp.video.path(); await v.close()
        subprocess.run(['ffmpeg', '-y', '-loglevel', 'error', '-i', vpath, '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-crf', '24', '-movflags', '+faststart', f'{OUT}/rf-website-2.4-motion-preview.mp4'])
    print(sorted(os.listdir(OUT))); os._exit(0)
asyncio.run(main())
