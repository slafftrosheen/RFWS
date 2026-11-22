#!/usr/bin/env python3
"""Website 2.4 · WCAG 2.2 AA (axe-core) for the new parts: night preview (off and switched on, every light type on one page), both themes,
phone + desktop, full + reduced motion; portal pages with the installation card, the preview dialog open, account with extra e-mails."""
import asyncio, json, sys, datetime, os
from playwright.async_api import async_playwright
AXE = open('/tmp/axe.min.js').read()
B = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8090'
Q = "async()=>{const r=await axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa','wcag22aa']}});return r.violations.map(v=>({id:v.id,n:v.nodes.length,ex:v.nodes.slice(0,2).map(x=>x.target.join(' ')+' '+(x.failureSummary||'').slice(0,160))}))}"
Y = datetime.date.today().year
async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch(); tot = 0; n = 0
        async def check(pg, label):
            nonlocal tot, n
            await pg.add_script_tag(content=AXE); res = await pg.evaluate(Q); n += 1
            if res: tot += len(res); print(label, json.dumps(res, ensure_ascii=False)[:1200])
        for w, rm, th in [(1440, 'no-preference', 'light'), (1440, 'reduce', 'dark'), (390, 'no-preference', 'dark'), (390, 'reduce', 'light')]:
            ctx = await b.new_context(viewport={'width': w, 'height': 900}, reduced_motion=rm, color_scheme=th, bypass_csp=True); pg = await ctx.new_page()
            for path in ['/profile-7-lux/', '/lv/profile-3/', '/de/special/', '/ru/profile-2/', '/sv/profile-8/', '/nl/casette-type/']:
                await pg.goto(B + path, wait_until='networkidle'); await pg.evaluate("document.querySelector('#preview').scrollIntoView()"); await pg.wait_for_timeout(900)
                await check(pg, f'{w} {th} {rm} {path} off')
                if await pg.get_attribute('[data-pv-night]', 'aria-pressed') != 'true': await pg.click('[data-pv-night]')
                await pg.wait_for_timeout(900); await check(pg, f'{w} {th} {rm} {path} on')
            # portal
            await pg.goto(B + '/lv/portal/login/'); await pg.fill('[name=email]', 'mara@lumo.test'); await pg.fill('[name=pw]', 'pass-2-4-test'); await pg.click('form button[type=submit]'); await pg.wait_for_load_state()
            for path in ['/lv/portal/', f'/lv/portal/requests/RF-{Y}-0001/', f'/lv/portal/requests/RF-{Y}-0006/', '/lv/portal/account/', '/lv/portal/activity/', '/lv/portal/requests/new/?p=profile-4']:
                await pg.goto(B + path, wait_until='networkidle'); await pg.wait_for_timeout(500); await check(pg, f'{w} {th} {rm} {path}')
            await pg.click('[data-pv-open]'); await pg.wait_for_timeout(300); await pg.click('[data-pv-dlg] [data-pv-night]'); await pg.wait_for_timeout(900)
            await check(pg, f'{w} {th} {rm} preview dialog')
            await ctx.close()
        print('page checks', n, 'violations', tot); os._exit(0)
asyncio.run(main())
