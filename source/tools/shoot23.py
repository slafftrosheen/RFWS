#!/usr/bin/env python3
"""Website 2.3 · preview screenshots (both themes, phone + desktop, portal + admin) and a ~55 s motion film."""
import asyncio, os, glob, subprocess
from playwright.async_api import async_playwright
B = 'http://127.0.0.1:8080'; O = '/tmp/rfcms/previews23'; os.makedirs(O, exist_ok=True)
GLIDE = """(to,ms)=>new Promise(r=>{document.documentElement.style.scrollBehavior='auto';const y0=scrollY,t0=performance.now();function s(t){const k=Math.min(1,(t-t0)/ms);const e=k<.5?2*k*k:1-Math.pow(-2*k+2,2)/2;scrollTo(0,y0+(to-y0)*e);k<1?requestAnimationFrame(s):r()}requestAnimationFrame(s)})"""
CL = ('mara@lumo.test', 'pass-2-3-test')
async def login(pg, base):
    await pg.goto(base + '/portal/login/'); await pg.fill('[name=email]', CL[0]); await pg.fill('[name=pw]', CL[1]); await pg.click('form button[type=submit]'); await pg.wait_for_load_state()
async def theme(pg, t): await pg.evaluate(f"window.rfTheme('{t}')"); await pg.wait_for_timeout(150)
async def prep(br):
    S = await br.new_context(viewport={'width': 1440, 'height': 900}); s = await S.new_page()
    await s.goto(B + '/admin/'); await s.fill('[name=login]', 'tester'); await s.fill('[name=pw]', 'admin-test-pass-1'); await s.click('button[type=submit]'); await s.wait_for_load_state()
    await s.goto(B + '/admin/?a=preq&no=RF-2026-0002')
    if await s.locator('#proofs tbody tr').count() == 0:
        await s.set_input_files('#proofs [name=proof]', '/tmp/rf-proof-preview.png'); await s.fill('#proofs [name=note]', 'Burti 400 mm, fronte RAL 9003, sāni RAL 9005. Lūdzu, apstipriniet.'); await s.click('#proofs button[type=submit]'); await s.wait_for_load_state()
        await s.fill('#thread textarea[name=body]', 'Labdien! Makets ir gatavs, gaidām jūsu apstiprinājumu.'); await s.click('#thread button[type=submit]'); await s.wait_for_load_state()
    await s.goto(B + '/admin/?a=preq&no=RF-2026-0002'); await s.evaluate("document.querySelector('#proofs').scrollIntoView()"); await s.wait_for_timeout(300)
    await s.screenshot(path=f'{O}/admin-proofs.jpg', type='jpeg', quality=82)
    await S.close()
async def shots(br):
    ctx = await br.new_context(viewport={'width': 1440, 'height': 900}, locale='en-GB'); pg = await ctx.new_page()
    async def snap(name, full=False): await pg.screenshot(path=f'{O}/{name}.jpg', type='jpeg', quality=82, full_page=full)
    await pg.goto(B + '/', wait_until='networkidle'); await theme(pg, 'dark'); await pg.wait_for_timeout(3000); await snap('site-home-hero-dark')
    await pg.click('[data-theme-btn]'); await pg.wait_for_timeout(300); await snap('header-theme-menu-dark'); await pg.keyboard.press('Escape')
    await theme(pg, 'light'); await pg.click('.lang .hdr-ctl'); await pg.wait_for_timeout(300); await snap('header-language-menu'); await pg.keyboard.press('Escape')
    await theme(pg, 'dark'); await pg.keyboard.press('Control+k'); await pg.keyboard.type('neon'); await pg.wait_for_timeout(700); await snap('search-palette-dark'); await pg.keyboard.press('Escape')
    for t in ['light', 'dark']:
        await theme(pg, t); await pg.evaluate("document.documentElement.style.scrollBehavior='auto'; scrollTo(0, document.querySelector('#process').offsetTop - 900)")
        for i in range(14): await pg.evaluate('scrollBy(0,100)'); await pg.wait_for_timeout(70)
        await pg.evaluate("scrollTo(0, document.querySelector('#process').offsetTop - 76)"); await pg.wait_for_timeout(5200); await snap(f'home-process-{t}')
    await theme(pg, 'light'); await pg.goto(B + '/product-profiles/', wait_until='networkidle')
    for i in [0, 2, 5]: await pg.locator('.pcards [data-cmp]').nth(i).click()
    await pg.wait_for_timeout(400); await snap('profiles-compare-bar'); await pg.click('[data-cmp-open]'); await pg.wait_for_timeout(900); await snap('profiles-compare-table')
    await pg.keyboard.press('Escape'); await pg.click('[data-cmp-clear]')
    await theme(pg, 'dark'); await pg.goto(B + '/contact/', wait_until='networkidle'); await pg.evaluate("scrollTo(0, document.querySelector('.live').getBoundingClientRect().top + scrollY - 140)"); await pg.wait_for_timeout(4000); await snap('contact-neon-clock-dark')
    await login(pg, B + '/lv')
    await theme(pg, 'dark'); await pg.goto(B + '/lv/portal/'); await pg.wait_for_timeout(700); await snap('portal-lv-dashboard-dark')
    await theme(pg, 'light'); await pg.goto(B + '/lv/portal/requests/RF-2026-0002/'); await pg.wait_for_timeout(700); await pg.evaluate("document.querySelector('#proofs').scrollIntoView()"); await pg.evaluate('scrollBy(0,-90)'); await pg.wait_for_timeout(300); await snap('portal-lv-proof-approve')
    await pg.goto(B + '/lv/portal/requests/RF-2026-0001/'); await pg.wait_for_timeout(700); await snap('portal-lv-request-installed', True)
    await pg.goto(B + '/lv/portal/sites/'); await pg.wait_for_timeout(400); await snap('portal-lv-sites')
    await theme(pg, 'dark'); await pg.goto(B + '/lv/portal/activity/'); await pg.wait_for_timeout(400); await snap('portal-lv-activity-dark')
    await theme(pg, 'light'); await pg.goto(B + '/lv/portal/requests/new/?from=RF-2026-0001'); await pg.fill('[name="items[0][height_mm]"]', '400'); await pg.wait_for_timeout(300); await pg.evaluate("document.querySelector('[data-items]').scrollIntoView()"); await pg.evaluate('scrollBy(0,-140)'); await snap('portal-lv-order-again-helper')
    await ctx.close()
    M = await br.new_context(viewport={'width': 390, 'height': 844}, device_scale_factor=2, is_mobile=True, has_touch=True); m = await M.new_page()
    await m.goto(B + '/de/', wait_until='networkidle'); await theme(m, 'dark'); await m.wait_for_timeout(2500); await m.screenshot(path=f'{O}/mobile-de-home-dark.jpg', type='jpeg', quality=80)
    await m.click('[data-nav-btn]'); await m.wait_for_timeout(400); await m.evaluate("document.getElementById('nav').scrollTop = 9999"); await m.wait_for_timeout(200); await m.screenshot(path=f'{O}/mobile-de-menu-languages-theme.jpg', type='jpeg', quality=80)
    await theme(m, 'light'); await login(m, B + '/lv'); await m.goto(B + '/lv/portal/'); await m.wait_for_timeout(600); await m.screenshot(path=f'{O}/mobile-lv-portal-dashboard.jpg', type='jpeg', quality=80)
    await M.close()
async def film(br):
    vd = '/tmp/rfcms/video23'; os.makedirs(vd, exist_ok=True); [os.remove(f) for f in glob.glob(vd + '/*')]
    ctx = await br.new_context(viewport={'width': 1440, 'height': 810}, record_video_dir=vd, record_video_size={'width': 1440, 'height': 810}, locale='en-GB'); pg = await ctx.new_page()
    g = lambda to, ms=1600: pg.evaluate(f"({GLIDE})({to},{ms})")
    await pg.goto(B + '/'); await pg.evaluate("window.rfTheme('light')"); await pg.goto(B + '/'); await pg.wait_for_timeout(5200)
    await pg.click('[data-theme-btn]'); await pg.wait_for_timeout(700); await pg.click('#theme-menu [data-theme-set="dark"]'); await pg.wait_for_timeout(2600)   # lights out from the switch
    y = await pg.evaluate("document.querySelector('#process').offsetTop - 1000"); await g(y, 1400)
    y = await pg.evaluate("document.querySelector('#process').offsetTop - 76"); await g(y, 4200); await pg.wait_for_timeout(5200)     # wire powers up, letter assembles
    for i in range(6):
        b = await pg.locator('.proc-step').nth(i).bounding_box(); await pg.mouse.move(b['x'] + 50, b['y'] + 50); await pg.wait_for_timeout(500)
    y = await pg.evaluate("document.querySelector('#profiles').offsetTop - 40"); await g(y, 1800)
    for i in range(3):
        b = await pg.locator('#profiles .pcard').nth(i).bounding_box()
        for k in range(14): await pg.mouse.move(b['x'] + b['width'] * (k / 14), b['y'] + 60 + k * 6); await pg.wait_for_timeout(45)
    await pg.locator('#profiles [data-cmp]').nth(1).click(); await pg.wait_for_timeout(500); await pg.locator('#profiles [data-cmp]').nth(2).click(); await pg.wait_for_timeout(1100)
    await pg.click('[data-cmp-open]'); await pg.wait_for_timeout(3000); await pg.keyboard.press('Escape'); await pg.click('[data-cmp-clear]'); await pg.wait_for_timeout(500)
    await g(0, 1200); await pg.click('[data-search-open]'); await pg.wait_for_timeout(500)
    for ch in 'halo': await pg.keyboard.type(ch); await pg.wait_for_timeout(180)
    await pg.wait_for_timeout(900); await pg.keyboard.press('ArrowDown'); await pg.wait_for_timeout(500); await pg.keyboard.press('Enter'); await pg.wait_for_timeout(2600)
    await pg.click('.lang .hdr-ctl'); await pg.wait_for_timeout(900); await pg.click('#lang-menu a[hreflang="lv"]'); await pg.wait_for_timeout(2200)
    await pg.goto(B + '/lv/contact/'); y = await pg.evaluate("document.querySelector('.live').getBoundingClientRect().top + scrollY - 160"); await g(y, 1200); await pg.wait_for_timeout(4200)
    await login(pg, B + '/lv'); await pg.wait_for_timeout(2600)
    await pg.goto(B + '/lv/portal/requests/RF-2026-0002/'); await pg.wait_for_timeout(1000)
    y = await pg.evaluate("document.querySelector('#proofs').getBoundingClientRect().top + scrollY - 100"); await g(y, 1200); await pg.wait_for_timeout(1500)
    await pg.click('[data-theme-btn]'); await pg.wait_for_timeout(500); await pg.click('#theme-menu [data-theme-set="light"]'); await pg.wait_for_timeout(2400)
    await pg.goto(B + '/lv/portal/activity/'); await pg.wait_for_timeout(2600)
    await ctx.close()
    src = glob.glob(vd + '/*.webm')[0]
    subprocess.run(['ffmpeg', '-y', '-loglevel', 'error', '-i', src, '-c:v', 'libx264', '-preset', 'slow', '-crf', '26', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', f'{O}/rf-website-2.3-motion-preview.mp4'], check=True)
async def main():
    from PIL import Image, ImageDraw
    im = Image.new('RGB', (1200, 900), (243, 242, 239)); d = ImageDraw.Draw(im)
    d.rectangle([120, 260, 1080, 640], fill=(28, 29, 32)); d.rectangle([180, 330, 1020, 570], outline=(255, 181, 71), width=10)
    for i, x in enumerate(range(240, 960, 180)): d.rectangle([x, 380, x + 120, 520], fill=(243, 242, 239))
    d.line([120, 700, 1080, 700], fill=(200, 22, 29), width=4); d.text((120, 720), '4 000 mm', fill=(28, 29, 32))
    im.save('/tmp/rf-proof-preview.png')
    async with async_playwright() as p:
        br = await p.chromium.launch(); await prep(br); await shots(br); await film(br)
    for f in sorted(os.listdir(O)): print(f, os.path.getsize(O + '/' + f), flush=True)
    os._exit(0)
asyncio.run(main())
