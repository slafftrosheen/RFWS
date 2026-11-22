#!/usr/bin/env python3
"""Website 2.4 · motion checks for the night preview and the portal status line: full motion flickers on then holds still (one lit moment),
reduced motion / motion off / the pause button switch on instantly, autostart only with full motion, status line animates only with full motion."""
import asyncio, os, sys
from playwright.async_api import async_playwright
B = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8090'
R = []
def ok(n, c, note=''): R.append(c); print(('PASS ' if c else 'FAIL ') + n + (f'  [{note}]' if note and not c else ''))
LUM = "()=>{const c=document.querySelector('[data-pv-cv]'),g=c.getContext('2d'),d=g.getImageData(500,280,600,240).data;let s=0,n=0;for(let i=0;i<d.length;i+=4*11){s+=(d[i]+d[i+1]+d[i+2])/3;n++}return Math.round(s/n)}"
async def samples(pg):
    await pg.click('[data-pv-night]'); out = []
    for t in [40, 120, 200, 300, 380, 470, 1000, 1600]:
        await pg.wait_for_timeout(t - (out[-1][0] if out else 0)); out.append((t, await pg.evaluate(LUM)))
    return [v for _, v in out]
async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch()
        for rm in ['no-preference', 'reduce']:
            ctx = await b.new_context(viewport={'width': 1440, 'height': 900}, reduced_motion=rm); pg = await ctx.new_page()
            await pg.goto(B + '/profile-7-lux/'); await pg.evaluate("document.querySelector('#preview').scrollIntoView()"); await pg.wait_for_timeout(1800)
            auto = await pg.get_attribute('[data-pv-night]', 'aria-pressed')
            if rm == 'no-preference': ok('full motion: switches on once by itself when seen', auto == 'true')
            else: ok('reduced motion: never switches on by itself', auto == 'false')
            if auto == 'true': await pg.click('[data-pv-night]'); await pg.wait_for_timeout(200)
            await pg.fill('[data-pv-text]', 'LUMO'); await pg.wait_for_timeout(200)
            v = await samples(pg)
            if rm == 'no-preference':
                ok('full motion: flickers (goes down again while striking)', any(v[i + 1] < v[i] - 3 for i in range(len(v) - 2)), str(v))
                ok('full motion: then holds still (no loop)', abs(v[-1] - v[-2]) <= 1, str(v))
            else: ok('reduced motion: full light at once, no flicker', max(v) - min(v) <= 1, str(v))
            # the status line in the portal markup carries the per-node index; animation only applies in full motion
            await ctx.close()
        ctx = await b.new_context(viewport={'width': 1440, 'height': 900}); pg = await ctx.new_page()
        await pg.goto(B + '/profile-7-lux/'); await pg.evaluate("localStorage.setItem('rf_motion_paused','1')"); await pg.reload()
        await pg.evaluate("document.querySelector('#preview').scrollIntoView()"); await pg.wait_for_timeout(1500)
        ok('pause button on: no autostart', await pg.get_attribute('[data-pv-night]', 'aria-pressed') == 'false')
        await pg.fill('[data-pv-text]', 'LUMO'); v = await samples(pg)
        ok('pause button on: soft fade, no flicker', all(v[i + 1] >= v[i] - 1 for i in range(len(v) - 1)), str(v))
        await pg.evaluate("localStorage.removeItem('rf_motion_paused')")
        css = open('/tmp/rfcms/site/assets/css/portal.css').read()
        ok('status line power-up only under .motion-full, stopped by pause', '.motion-full .pt-line::after{animation:pt-wire' in css and '.paused .pt-line::after,.paused .pt-line i{animation:none!important}' in css)
    print(f'{sum(R)}/{len(R)} passed'); os._exit(0)
asyncio.run(main())
