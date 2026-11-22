#!/usr/bin/env python3
"""Website 2.3 · WCAG 2.2 AA (axe-core) in BOTH themes, phone + desktop, reduced + full motion, with menus and search open."""
import asyncio, json, sys
from playwright.async_api import async_playwright
AXE = open('/tmp/axe.min.js').read()
B = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8080'
QUICK = '--quick' in sys.argv
X = ['/', '/product-profiles/', '/profile-2/', '/profile-7-lux/', '/special/', '/projects/', '/seamless-signage-transforming-depo-stores-across-the-baltics/', '/signage-research/', '/contact/', '/privacy-policy/', '/portal/login/', '/portal/register/', '/nope-404/']
P = [l + x for l in (['', '/de'] if QUICK else ['', '/ru', '/de', '/lv']) for x in X]
EXTRA = sys.argv[sys.argv.index('--extra') + 1].split(',') if '--extra' in sys.argv else []
async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch(); tot = 0; n = 0
        combos = [(390, 'reduce', 'dark'), (1440, 'no-preference', 'dark'), (1440, 'reduce', 'light'), (390, 'no-preference', 'light')]
        for w, rm, th in combos:
            ctx = await b.new_context(viewport={'width': w, 'height': 900}, reduced_motion=rm, color_scheme=th, bypass_csp=True); pg = await ctx.new_page()
            for path in P + EXTRA:
                await pg.goto(B + path, wait_until='networkidle')
                if rm == 'no-preference':
                    h = await pg.evaluate('document.body.scrollHeight')
                    for y in range(0, h, 500): await pg.evaluate(f'scrollTo(0,{y})'); await pg.wait_for_timeout(50)
                    await pg.evaluate('scrollTo(0,0)'); await pg.wait_for_timeout(900)
                await pg.add_script_tag(content=AXE)
                q = "async()=>{const r=await axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa','wcag22aa']}});return r.violations.map(v=>({id:v.id,n:v.nodes.length,ex:v.nodes.slice(0,3).map(x=>x.target.join(' ')+' | '+(x.any[0]?.message||x.all[0]?.message||'').slice(0,120))}))}"
                res = await pg.evaluate(q); n += 1
                if res: tot += len(res); print(w, th, rm, path, json.dumps(res, ensure_ascii=False)[:1400])
                if path in ('/', '/de/') and w == 1440:   # open the menus + search and check again
                    for opener in ['.lang .hdr-ctl', '[data-theme-btn]']:
                        await pg.click(opener); res = await pg.evaluate(q); n += 1
                        if res: tot += len(res); print('menu', opener, th, json.dumps(res, ensure_ascii=False)[:800])
                        await pg.keyboard.press('Escape')
                    await pg.keyboard.press('Control+k'); await pg.keyboard.type('profile'); await pg.wait_for_timeout(400); res = await pg.evaluate(q); n += 1
                    if res: tot += len(res); print('search', th, json.dumps(res, ensure_ascii=False)[:800])
                    await pg.keyboard.press('Escape')
            await ctx.close()
        await b.close(); print('page checks', n, 'violations', tot)
asyncio.run(main())
