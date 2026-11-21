"""End-to-end test of the Russian admin in a real browser."""
import asyncio, re, json, os, sys
from playwright.async_api import async_playwright
B = 'http://127.0.0.1:8770'
SITE = '/tmp/rfcms/site'
ok = []; bad = []
def check(cond, msg):
    (ok if cond else bad).append(msg); print(('PASS ' if cond else 'FAIL ') + msg)

async def main():
    async with async_playwright() as p:
        br = await p.chromium.launch()
        ctx = await br.new_context(viewport={'width': 1440, 'height': 900}, accept_downloads=True)
        pg = await ctx.new_page()
        errs = []
        pg.on('pageerror', lambda e: errs.append(str(e)))
        pg.on('console', lambda m: errs.append(m.text) if m.type == 'error' else None)
        pg.on('dialog', lambda dlg: asyncio.ensure_future(dlg.accept()))

        # 1 setup
        code = open(SITE + '/data/_auth/setup-code.txt').read().strip()
        await pg.goto(B + '/admin/')
        await pg.fill('input[name=code]', 'WRONG'); await pg.fill('input[name=name]', 'Тест'); await pg.fill('input[name=login]', 'test')
        await pg.fill('input[name=pw]', 'correct-horse-1'); await pg.fill('input[name=pw2]', 'correct-horse-1'); await pg.click('button[type=submit]')
        check('Неверный код' in await pg.content(), 'setup rejects a wrong code')
        await pg.fill('input[name=code]', code); await pg.fill('input[name=login]', 'test'); await pg.fill('input[name=pw]', 'correct-horse-1'); await pg.fill('input[name=pw2]', 'correct-horse-1'); await pg.click('button[type=submit]')
        check(await pg.locator('h1').inner_text() == 'Обзор', 'setup creates admin and opens dashboard')
        check(not os.path.exists(SITE + '/data/_auth/setup-code.txt'), 'setup code removed after use')
        check('Переводы сайта' in await pg.content(), 'dashboard shows translation progress')

        # 2 logout / bad login / login
        await pg.click('form[action*="logout"] button')
        await pg.fill('input[name=login]', 'test'); await pg.fill('input[name=pw]', 'nope'); await pg.click('button[type=submit]')
        check('Неверный логин' in await pg.content(), 'wrong password refused')
        await pg.fill('input[name=login]', 'test'); await pg.fill('input[name=pw]', 'correct-horse-1'); await pg.click('button[type=submit]')
        check(await pg.locator('h1').inner_text() == 'Обзор', 'login works')

        # 3 every admin screen renders
        for a in ['page&id=home', 'page&id=product-profiles', 'page&id=projects', 'page&id=signage-research', 'page&id=contact', 'page&id=privacy-policy',
                  'profiles', 'profiles&g=lightbox', 'stories', 'media', 'media&f=unused', 'inbox', 'translate&l=ru', 'translate&l=de', 'ui', 'settings', 'users', 'backup',
                  'profile&slug=profile-7-lux', 'story&slug=seamless-signage-transforming-depo-stores-across-the-baltics', 'profile&slug=new', 'story&slug=new', 'medium&id=clients-2']:
            r = await pg.goto(B + '/admin/?a=' + a)
            h = await pg.locator('h1').first.inner_text()
            check(r.status == 200 and h and 'Fatal' not in await pg.content() and 'Warning' not in await pg.content(), f'admin screen {a} renders ("{h}")')

        # 4 edit home page in Russian, check the public site
        await pg.goto(B + '/admin/?a=page&id=home')
        await pg.click('[data-ltab="ru"]')
        f = pg.locator('textarea[name="c[hero][lead][ru]"]')
        check(await f.is_visible(), 'RU tab shows Russian fields')
        check(not await pg.locator('textarea[name="c[hero][lead][de]"]').is_visible(), 'RU tab hides German fields')
        old_lead = await f.input_value()
        await f.fill('ТЕСТ: новый подзаголовок главной')
        # add a stat row, remove it again later
        n0 = await pg.locator('#s-stats .lrow').count()
        await pg.click('#s-stats [data-add]'); await pg.fill('#s-stats .lrow:last-child input[name$="[value]"]', '42')
        await pg.fill('#s-stats .lrow:last-child input[name$="[label][ru]"]', 'Тестовая цифра')
        await pg.click('.sidebox button[type=submit]')
        check('сохранена' in await pg.content(), 'home page saves')
        html = await (await pg.request.get(B + '/ru/')).text()
        check('ТЕСТ: новый подзаголовок главной' in html and 'Тестовая цифра' in html, 'change visible on /ru/')
        html_en = await (await pg.request.get(B + '/')).text()
        check('ТЕСТ' not in html_en and 'Your trusted partner' in html_en, 'English page unaffected')
        check('>42<' in html_en, 'new stat shows on EN too (label falls back until translated)')
        # gallery reorder via data: move last team photo first using the hidden field, then restore via history
        await pg.goto(B + '/admin/?a=page&id=home')
        check(await pg.locator('#s-stats .lrow').count() == n0 + 1, 'list row persisted')
        await pg.locator('#s-stats .lrow:last-child [data-del]').click()
        await pg.locator('textarea[name="c[hero][lead][ru]"]').fill(old_lead)
        await pg.click('.sidebox button[type=submit]')
        html = await (await pg.request.get(B + '/ru/')).text()
        check('ТЕСТ' not in html and 'Тестовая цифра' not in html, 'home restored')

        # 5 media: upload through the library
        await pg.goto(B + '/admin/?a=media')
        before = len(json.load(open(SITE + '/data/media.json')))
        await pg.set_input_files('[data-upload]', ['/tmp/rfcms/test-upload.jpg', '/tmp/rfcms/test-logo.png'])
        await pg.wait_for_url(re.compile(r'a=media'), timeout=15000); await pg.wait_for_timeout(1500)
        m = json.load(open(SITE + '/data/media.json'))
        new_ids = [k for k in m if k.startswith('test-')]
        check(len(m) == before + 2 and len(new_ids) == 2, f'2 photos uploaded ({new_ids})')
        up = m.get(next((k for k in new_ids if 'upload' in k), ''), {})
        check(up.get('sizes') == [400, 800, 1600], f'big photo gets 400 + 800 + 1600 sizes ({up.get("sizes")})')
        for k in new_ids:
            for s in m[k]['sizes']: check(os.path.exists(f'{SITE}/uploads/{k}-{s}.webp'), f'file {k}-{s}.webp exists')
        lg = next(k for k in new_ids if 'logo' in k)
        # alt text in Russian
        await pg.goto(B + f'/admin/?a=medium&id={lg}'); await pg.click('[data-ltab="ru"]')
        await pg.fill('textarea[name="alt[ru]"]', 'Тестовый логотип'); await pg.click('form[data-dirty] button[type=submit]')
        check(json.load(open(SITE + '/data/media.json'))[lg]['alt'].get('ru') == 'Тестовый логотип', 'alt text saved per language')

        # 6 create a new profile with the picker, list row, gallery
        await pg.goto(B + '/admin/?a=profile&slug=new'); await pg.click('[data-ltab="en"]')
        await pg.fill('input[name="c[title][en]"]', 'Profile 9 TEST'); await pg.fill('input[name="c[subtitle][en]"]', 'Test letters')
        await pg.click('[data-ltab="ru"]'); await pg.fill('input[name="c[title][ru]"]', 'Профиль 9 ТЕСТ')
        await pg.select_option('select[name="c[light]"]', 'neon')
        await pg.fill('input[name="c[size]"]', '100–900 mm')
        await pg.click('#s-spec [data-add]')
        await pg.click('[data-ltab="en"]')
        await pg.fill('#s-spec .lrow:last-child input[name$="[label][en]"]', 'Face panels'); await pg.fill('#s-spec .lrow:last-child input[name$="[value][en]"]', '3 mm acrylic')
        await pg.click('#s-main [data-pick="one"]'); await pg.wait_for_selector(f'[data-mgrid] button[data-id="{lg}"]')
        await pg.click(f'[data-mgrid] button[data-id="{lg}"]'); await pg.click('[data-mok]')
        await pg.click('#s-media [data-pick="many"]'); await pg.wait_for_selector('[data-mgrid] button[data-id]')
        btns = pg.locator('[data-mgrid] button[data-id]'); await btns.nth(0).click(); await btns.nth(1).click(); await btns.nth(2).click(); await pg.click('[data-mok]')
        check(await pg.locator('#s-media .galf-list li').count() == 3, 'picker adds 3 photos to the gallery')
        await pg.fill('input[name="c[video]"]', 'https://www.youtube.com/watch?v=vlGmeczXQg4')
        await pg.fill('input[name=slug]', 'profile-9-test'); await pg.check('input[name=published]')
        await pg.click('.sidebox button[type=submit]')
        check('Создано' in await pg.content(), 'new profile created')
        pj = json.load(open(SITE + '/data/profiles/profile-9-test.json'))
        check(pj['cover'] == lg and len(pj['gallery']) == 3 and pj['light'] == 'neon' and pj['specs'][0]['value']['en'] == '3 mm acrylic', 'profile data stored correctly')
        h = await (await pg.request.get(B + '/ru/profile-9-test/')).text()
        check('Профиль 9 ТЕСТ' in h and 'Под неон' in h and '100–900 мм' in h, 'new profile live on /ru/ with Russian title, light label and мм')
        h = await (await pg.request.get(B + '/de/profile-9-test/')).text()
        check('Profile 9 TEST' in h, 'missing German title falls back to English')
        h = await (await pg.request.get(B + '/product-profiles/')).text()
        check('profile-9-test' in h, 'new profile listed on product profiles page')
        # rename slug -> redirect
        await pg.goto(B + '/admin/?a=profile&slug=profile-9-test'); await pg.fill('input[name=slug]', 'profile-9-renamed'); await pg.click('.sidebox button[type=submit]')
        r = await pg.request.get(B + '/profile-9-test/', max_redirects=0)
        check(r.status == 301 and '/profile-9-renamed/' in r.headers.get('location', ''), 'renamed slug redirects old address')
        # unpublish -> 404 for visitors, preview for admin
        await pg.goto(B + '/admin/?a=profiles')
        await pg.locator('tr[data-slug="profile-9-renamed"] .pill').click()
        anon = await br.new_context(); ap = await anon.new_page()
        r = await ap.goto(B + '/profile-9-renamed/'); check(r.status == 404, 'unpublished profile hidden from visitors')
        r = await pg.goto(B + '/profile-9-renamed/'); check(r.status == 200 and 'Предпросмотр' in await pg.content(), 'admin can preview unpublished profile')
        # duplicate + delete
        await pg.goto(B + '/admin/?a=profiles')
        await pg.locator('tr[data-slug="profile-9-renamed"] form[action*="a=dup"] button').click()
        check(os.path.exists(SITE + '/data/profiles/profile-9-renamed-copy.json'), 'duplicate creates a draft copy')
        for s in ['profile-9-renamed-copy', 'profile-9-renamed']:
            await pg.goto(B + '/admin/?a=profiles'); await pg.locator(f'tr[data-slug="{s}"] form[action*="a=delete"] button').click()
        check(not os.path.exists(SITE + '/data/profiles/profile-9-renamed.json') and any('profile-9-renamed' in f for f in os.listdir(SITE + '/data/_trash')), 'delete moves profile to trash')

        # 7 reorder profiles (drag in table)
        await pg.goto(B + '/admin/?a=profiles')
        first = await pg.locator('tbody tr').first.get_attribute('data-slug'); second = await pg.locator('tbody tr').nth(1).get_attribute('data-slug')
        await pg.locator('tbody tr').nth(1).drag_to(pg.locator('tbody tr').first, target_position={'x': 200, 'y': 5})
        check(not await pg.locator('[data-ordersave]').is_disabled(), 'dragging rows enables “save order”')
        await pg.click('[data-ordersave]')
        h = await (await pg.request.get(B + '/product-profiles/')).text()
        check(h.find('/' + second + '/') < h.find('/' + first + '/'), 'new order shows on the site')
        await pg.goto(B + '/admin/?a=profiles'); await pg.locator('tbody tr').nth(1).drag_to(pg.locator('tbody tr').first, target_position={'x': 200, 'y': 5}); await pg.click('[data-ordersave]')

        # 8 story: add Russian body
        st = 'seamless-signage-transforming-depo-stores-across-the-baltics'
        h = await (await pg.request.get(B + f'/ru/{st}/')).text()
        check('пока доступна на английском' not in h and 'В 2005 году' in h, 'story is shown in Russian (all stories translated in 2.2)')
        await pg.goto(B + f'/admin/?a=story&slug={st}'); await pg.click('[data-ltab="ru"]')
        await pg.fill('textarea[name="c[body][ru]"]', 'Первый абзац **жирный**.\n\nВторой абзац со [ссылкой](/contact/).')
        await pg.click('.sidebox button[type=submit]')
        h = await (await pg.request.get(B + f'/ru/{st}/')).text()
        check('<b>жирный</b>' in h and 'href="/contact/"' in h and 'пока доступна' not in h, 'Russian story body renders with bold + link')
        await pg.goto(B + f'/admin/?a=history&rel=stories/{st}')
        await pg.locator('main form button').first.click()
        h = await (await pg.request.get(B + f'/ru/{st}/')).text()
        check('Первый абзац' not in h, 'history restore brings back the previous version')

        # 9 contact form -> inbox
        await ap.goto(B + '/ru/contact/?profile=profile-7-lux')
        check(await ap.locator('select[name=profile]').input_value() == 'profile-7-lux', 'contact form preselects profile from link')
        await ap.fill('input[name=name]', 'Иван Тест'); await ap.fill('input[name=email]', 'ivan@example.com'); await ap.fill('textarea[name=message]', 'Нужны буквы 2 м'); await ap.click('form[data-form] button[type=submit]')
        await ap.wait_for_selector('.form-msg.ok', timeout=8000)
        check('Спасибо' in await ap.locator('.form-msg').inner_text(), 'visitor sees Russian thank-you')
        await pg.goto(B + '/admin/?a=inbox'); check('Иван Тест' in await pg.content(), 'request appears in the admin inbox')
        await pg.click('text=Иван Тест'); check('Нужны буквы 2 м' in await pg.content() and 'Профиль 7 LUX' in await pg.content(), 'request detail shows message + profile')
        await pg.click('form[data-confirm] button')
        check('Иван Тест' not in await pg.content(), 'request deleted')

        # 10 interface texts + settings (turn Swedish off and on)
        await pg.goto(B + '/admin/?a=ui'); await pg.click('[data-ltab="ru"]')
        await pg.fill('input[name="u[cta.quote][ru]"]', 'Узнать стоимость'); await pg.click('.sticky-save button')
        h = await (await pg.request.get(B + '/ru/')).text(); check('Узнать стоимость' in h, 'interface text change is live')
        await pg.goto(B + '/admin/?a=ui'); await pg.click('[data-ltab="ru"]'); await pg.fill('input[name="u[cta.quote][ru]"]', 'Запросить цену'); await pg.click('.sticky-save button')
        # 2.2: portal texts are a separate group, each group saves on its own (stays under PHP's 1,000-field limit)
        await pg.goto(B + '/admin/?a=ui&g=6'); await pg.click('[data-ltab="ru"]')
        n_inputs = await pg.evaluate("document.querySelectorAll('form.edit input, form.edit textarea').length")
        await pg.fill('input[name="u[p.new_request][ru]"]', 'Новая заявка'); await pg.click('.sticky-save button')
        uj = json.load(open(SITE + '/data/ui.json'))
        check(uj['p.new_request']['ru'] == 'Новая заявка' and n_inputs < 1000 and uj['p.login']['lv'] == 'Pieteikties', f'portal texts group saves on its own ({n_inputs} fields)')
        await pg.goto(B + '/admin/?a=ui&g=6'); await pg.click('[data-ltab="ru"]'); await pg.fill('input[name="u[p.new_request][ru]"]', 'Новый запрос'); await pg.click('.sticky-save button')
        await pg.goto(B + '/admin/?a=settings'); await pg.uncheck('input[name="langs[]"][value=sv]'); await pg.click('.sidebox button[type=submit]')
        r = await ap.goto(B + '/sv/'); h = await (await pg.request.get(B + '/')).text()
        check(r.status == 404 and 'hreflang="sv"' not in h, 'turning Swedish off removes it from the site')
        await pg.goto(B + '/admin/?a=settings'); await pg.check('input[name="langs[]"][value=sv]'); await pg.click('.sidebox button[type=submit]')
        r = await ap.goto(B + '/sv/'); check(r.status == 200, 'Swedish back on')

        # 11 users
        await pg.goto(B + '/admin/?a=users'); await pg.fill('form:has([value=add]) input[name=name]', 'Пётр'); await pg.fill('form:has([value=add]) input[name=login]', 'petr'); await pg.fill('form:has([value=add]) input[name=pw]', 'another-pass-22'); await pg.click('form:has([value=add]) button')
        check('petr' in await pg.content(), 'second user added')
        await pg.locator('form:has(input[value=petr]) button').click(); check('Пётр' not in await pg.content(), 'user deleted')

        # 12 media delete guard + delete unused
        await pg.goto(B + '/admin/?a=medium&id=clients-2'); check(await pg.locator('form[data-confirm] button').is_disabled(), 'cannot delete a photo that is in use')
        for k in new_ids:
            await pg.goto(B + f'/admin/?a=medium&id={k}'); await pg.locator('form[data-confirm] button').click()
        m = json.load(open(SITE + '/data/media.json')); check(not any(k in m for k in new_ids) and not any(f.startswith('test-') for f in os.listdir(SITE + '/uploads')), 'unused test photos deleted with files')

        # 13 backup
        await pg.goto(B + '/admin/?a=backup')
        async with pg.expect_download() as dl: await pg.click('main form button[type=submit]')
        d = await dl.value; path = await d.path(); import zipfile; z = zipfile.ZipFile(path); names = z.namelist()
        check(any(n.startswith('data/pages/') for n in names) and any(n.startswith('uploads/') for n in names) and not any('_auth' in n for n in names), f'backup zip has data + uploads, no passwords ({len(names)} files)')

        # 14 security
        r = await ap.goto(B + '/admin/?a=settings'); check('Вход в управление' in await ap.content(), 'admin pages require login')
        r = await ap.request.post(B + '/admin/?a=page&id=home', form={'c[hero][title][en]': 'HACK'}); h = await (await ap.request.get(B + '/')).text()
        check('HACK' not in h, 'anonymous POST cannot change content')
        r = await pg.request.post(B + '/admin/?a=page&id=home', form={'c[hero][title][en]': 'HACK'}); h = await (await ap.request.get(B + '/')).text()
        check('HACK' not in h, 'POST without CSRF token is rejected')
        for u in ['/data/_auth/users.json', '/data/settings.json', '/app/core.php']:
            r = await ap.request.get(B + u); check(r.status == 403, f'{u} is blocked')

        check(not errs, 'no JavaScript errors in admin' + (': ' + '; '.join(errs[:3]) if errs else ''))
        await br.close()
    print(f'\n{len(ok)} passed, {len(bad)} failed')
    for b in bad: print('  FAILED:', b)
asyncio.run(main())
