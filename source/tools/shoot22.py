#!/usr/bin/env python3
"""Website 2.2 · preview screenshots + a ~50 s motion film (Night shift + portal)."""
import asyncio, os, glob, subprocess
from playwright.async_api import async_playwright
B = 'http://127.0.0.1:8080'; O = '/tmp/rfcms/previews22'; os.makedirs(O, exist_ok=True)
GLIDE = """(to,ms)=>new Promise(r=>{const y0=scrollY,t0=performance.now();function s(t){const k=Math.min(1,(t-t0)/ms);const e=k<.5?2*k*k:1-Math.pow(-2*k+2,2)/2;scrollTo(0,y0+(to-y0)*e);k<1?requestAnimationFrame(s):r()}requestAnimationFrame(s)})"""
async def login(pg, base, email, pw):
    await pg.goto(base + '/portal/login/'); await pg.fill('[name=email]', email); await pg.fill('[name=pw]', pw); await pg.click('form button[type=submit]'); await pg.wait_for_load_state()
async def shots(br):
    ctx = await br.new_context(viewport={'width': 1440, 'height': 900}); pg = await ctx.new_page()
    async def snap(name, full=False): await pg.screenshot(path=f'{O}/{name}.jpg', type='jpeg', quality=82, full_page=full)
    await pg.goto(B + '/', wait_until='networkidle'); await pg.wait_for_timeout(2800); await snap('site-home-hero')
    await pg.evaluate("document.querySelector('#profiles').scrollIntoView()"); await pg.wait_for_timeout(1200); await snap('site-home-profile-rail')
    await pg.evaluate("scrollTo(0, document.querySelector('#factory').offsetTop + 300)"); await pg.wait_for_timeout(1600); await snap('site-home-factory-film')
    await pg.goto(B + '/profile-4/', wait_until='networkidle'); await pg.evaluate("scrollTo(0, document.querySelector('.specs').getBoundingClientRect().top + scrollY - 120)"); await pg.wait_for_timeout(900); await snap('site-profile-spec-ral-drawing')
    await pg.goto(B + '/profile-7-lux/', wait_until='networkidle')
    await pg.evaluate("document.querySelector('[data-anat]').scrollIntoView({block:'center'})"); await pg.wait_for_timeout(600)
    await pg.click('[data-anat-toggle]') if 'apart' not in (await pg.get_attribute('[data-anat]', 'class')) else None
    await pg.wait_for_timeout(1300); await snap('site-anatomy-apart')
    await pg.click('[data-anat-toggle]'); await pg.wait_for_timeout(1600); await snap('site-anatomy-lit')
    await pg.goto(B + '/lv/signage-research/', wait_until='networkidle'); await pg.wait_for_timeout(1200); await snap('site-lv-research-head')
    await login(pg, B + '/lv', 'anna@ozols.test', 'brand-new-pass-2')
    await pg.goto(B + '/lv/portal/'); await pg.wait_for_timeout(500); await snap('portal-lv-dashboard')
    await pg.goto(B + '/lv/portal/requests/RF-2026-0001/'); await pg.wait_for_timeout(800); await snap('portal-lv-request', True)
    await pg.goto(B + '/lv/portal/quotes/RF-Q-2026-0001/'); await pg.wait_for_timeout(600); await snap('portal-lv-quote', True)
    await pg.goto(B + '/lv/portal/requests/new/?p=profile-2,casette-type'); await pg.wait_for_timeout(600); await snap('portal-lv-new-request', True)
    await ctx.close()
    S = await br.new_context(viewport={'width': 1440, 'height': 900}); s = await S.new_page()
    await s.goto(B + '/admin/'); await s.fill('[name=login]', 'tester'); await s.fill('[name=pw]', 'admin-test-pass-1'); await s.click('button[type=submit]'); await s.wait_for_load_state()
    await s.goto(B + '/admin/?a=portal&st=all'); await s.screenshot(path=f'{O}/admin-portal-requests.jpg', type='jpeg', quality=82)
    await s.goto(B + '/admin/?a=preq&no=RF-2026-0001'); await s.screenshot(path=f'{O}/admin-portal-request.jpg', type='jpeg', quality=82, full_page=True)
    await s.goto(B + '/admin/?a=preq&no=RF-2026-0002'); await s.click('text=+ Создать КП'); await s.wait_for_load_state()
    await s.fill('[name="lines[0][unit_price]"]', '960'); await s.fill('[name=lead_time]', '12 werkdagen'); await s.wait_for_timeout(200)
    await s.screenshot(path=f'{O}/admin-quote-editor.jpg', type='jpeg', quality=82, full_page=True)
    await s.goto(B + '/admin/?a=ui&g=6'); await s.screenshot(path=f'{O}/admin-ui-portal-texts.jpg', type='jpeg', quality=82)
    await S.close()
    M = await br.new_context(viewport={'width': 390, 'height': 844}, device_scale_factor=2, is_mobile=True); m = await M.new_page()
    await m.goto(B + '/ru/', wait_until='networkidle'); await m.wait_for_timeout(2500); await m.screenshot(path=f'{O}/mobile-ru-home.jpg', type='jpeg', quality=80)
    await m.goto(B + '/de/profile-2/', wait_until='networkidle'); await m.evaluate("document.querySelector('[data-anat]').scrollIntoView()"); await m.wait_for_timeout(1500); await m.screenshot(path=f'{O}/mobile-de-anatomy.jpg', type='jpeg', quality=80)
    await login(m, B + '/sv', 'anna@ozols.test', 'brand-new-pass-2'); await m.goto(B + '/sv/portal/requests/RF-2026-0001/'); await m.wait_for_timeout(600); await m.screenshot(path=f'{O}/mobile-sv-portal-request.jpg', type='jpeg', quality=80)
    await M.close()
async def film(br):
    vd = '/tmp/rfcms/video22'; os.makedirs(vd, exist_ok=True); [os.remove(f) for f in glob.glob(vd + '/*')]
    ctx = await br.new_context(viewport={'width': 1440, 'height': 810}, record_video_dir=vd, record_video_size={'width': 1440, 'height': 810}); pg = await ctx.new_page()
    g = lambda to, ms=1600: pg.evaluate(GLIDE, [to, ms]) if False else pg.evaluate(f"({GLIDE})({to},{ms})")
    await pg.goto(B + '/'); await pg.wait_for_timeout(9500)                                # neon headline + first cross-fade
    y = await pg.evaluate("document.querySelector('#services').offsetTop-90"); await g(y); await pg.wait_for_timeout(2000)
    y = await pg.evaluate("document.querySelector('#profiles').offsetTop-40"); await g(y); await pg.wait_for_timeout(600)
    for i in range(3):
        b = await pg.locator('#profiles .pcard').nth(i).bounding_box(); await pg.mouse.move(b['x'] + b['width'] / 2, b['y'] + 90); await pg.wait_for_timeout(900)
    await pg.click('[data-rail-next]'); await pg.wait_for_timeout(1300); await pg.click('[data-rail-go=lightbox]'); await pg.wait_for_timeout(1500)
    await pg.mouse.move(20, 20)
    y = await pg.evaluate("document.querySelector('#factory').offsetTop+260"); await g(y, 2000); await pg.wait_for_timeout(4500)
    await pg.goto(B + '/profile-7-lux/'); await pg.wait_for_timeout(700)
    top = await pg.evaluate("document.querySelector('[data-anat]').getBoundingClientRect().top+scrollY")
    await g(top - 700, 1200); await g(top - 120, 2600); await pg.wait_for_timeout(900); await g(top + 520, 2600); await pg.wait_for_timeout(2600)
    await pg.goto(B + '/de/profile-2/'); await pg.wait_for_timeout(500)
    top = await pg.evaluate("document.querySelector('[data-anat]').getBoundingClientRect().top+scrollY")
    await g(top - 150, 1400); await pg.wait_for_timeout(300); await pg.click('[data-anat-toggle]'); await pg.wait_for_timeout(1800); await pg.click('[data-anat-toggle]'); await pg.wait_for_timeout(2400)
    await pg.goto(B + '/lv/signage-research/'); await pg.wait_for_timeout(600)
    y = await pg.evaluate("document.querySelector('[data-wire]').getBoundingClientRect().top+scrollY-420"); await g(y, 2600); await pg.wait_for_timeout(1500)
    await login(pg, B + '/lv', 'anna@ozols.test', 'brand-new-pass-2'); await pg.wait_for_timeout(1500)
    await pg.goto(B + '/lv/portal/requests/RF-2026-0001/'); await pg.wait_for_timeout(3500)
    await pg.goto(B + '/lv/portal/quotes/RF-Q-2026-0001/?ok=1'); await pg.wait_for_timeout(3500)
    await ctx.close()
    src = glob.glob(vd + '/*.webm')[0]
    subprocess.run(['ffmpeg', '-y', '-loglevel', 'error', '-i', src, '-c:v', 'libx264', '-preset', 'slow', '-crf', '26', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', '/tmp/rfcms/previews22/rf-website-2.2-motion-preview.mp4'], check=True)
async def main():
    async with async_playwright() as p:
        br = await p.chromium.launch(); await shots(br); await film(br); await br.close()
    for f in sorted(os.listdir(O)): print(f, os.path.getsize(O + '/' + f))
asyncio.run(main())
