#!/usr/bin/env python3
"""Website 2.2 · checks for the new motion (Night shift) in full and reduced motion."""
import asyncio, sys
from playwright.async_api import async_playwright
B = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8770'; res = []
def ck(c, m): res.append(c); print(('PASS ' if c else 'FAIL ') + m)
async def main():
    async with async_playwright() as p:
        br = await p.chromium.launch()
        for rm in ['no-preference', 'reduce']:
            ctx = await br.new_context(viewport={'width': 1440, 'height': 900}, reduced_motion=rm); pg = await ctx.new_page(); errs = []
            pg.on('pageerror', lambda e: errs.append(str(e)))
            await pg.goto(B + '/', wait_until='networkidle')
            if rm == 'no-preference':
                await pg.wait_for_timeout(9800)
                ck(await pg.evaluate("[...document.querySelectorAll('.hero-media .slide')].findIndex(s=>s.classList.contains('is-on'))") == 1, 'hero cross-fades to the next real photo after 7 s')
                ck(await pg.evaluate("document.querySelectorAll('.hero-media img').length") >= 2, 'next slides load only after the page')
            else:
                await pg.wait_for_timeout(8000)
                ck(await pg.evaluate("document.querySelectorAll('.hero-media img').length") == 1, 'reduced motion: one hero photo, no slideshow')
            await pg.evaluate("document.querySelector('[data-film]').scrollIntoView({block:'center'})"); await pg.wait_for_timeout(600)
            t1 = await pg.evaluate("getComputedStyle(document.querySelector('.film-track')).transform"); await pg.wait_for_timeout(1200)
            t2 = await pg.evaluate("getComputedStyle(document.querySelector('.film-track')).transform")
            dups = await pg.evaluate("document.querySelectorAll('.film-dup').length")
            if rm == 'no-preference':
                ck(t1 != t2 and dups == 2, f'film strip drifts, seamless copies added ({dups})')
                await pg.click('.mo-hero') if await pg.is_visible('.mo-hero') else await pg.evaluate("document.querySelector('[data-motion-toggle]').click()")
                await pg.wait_for_timeout(300); a1 = await pg.evaluate("getComputedStyle(document.querySelector('.film-track')).transform"); await pg.wait_for_timeout(900)
                a2 = await pg.evaluate("getComputedStyle(document.querySelector('.film-track')).transform")
                ck(a1 == a2 and await pg.evaluate("document.body.classList.contains('paused')"), 'pause button stops the moving parts')
                await pg.reload(wait_until='networkidle')
                ck(await pg.evaluate("document.body.classList.contains('paused')"), 'pause is remembered on the next page')
                await pg.evaluate("document.querySelector('[data-motion-toggle]').click()")
            else:
                ck(t1 == t2 and dups == 0, 'reduced motion: film strip still and scrollable')
            n = await pg.evaluate("document.querySelectorAll('[data-rail-list] > li').length")
            await pg.evaluate("document.querySelector('[data-rail]').scrollIntoView({block:'center'})")
            x0 = await pg.evaluate("document.querySelector('[data-rail-list]').scrollLeft")
            await pg.click('[data-rail-next]'); await pg.wait_for_timeout(900)
            x1 = await pg.evaluate("document.querySelector('[data-rail-list]').scrollLeft")
            ck(n == 21 and x1 > x0, f'[{rm}] profile rail: 21 profiles, arrow scrolls ({x0}→{int(x1)})')
            await pg.click('[data-rail-go=lightbox]'); await pg.wait_for_timeout(900)
            ck(await pg.evaluate("document.querySelector('[data-rail-go=lightbox]').getAttribute('aria-pressed')") == 'true', f'[{rm}] rail tab jumps to lightboxes')
            await pg.goto(B + '/profile-7-lux/', wait_until='networkidle')
            if rm == 'no-preference':
                h = await pg.evaluate("(()=>{const a=document.querySelector('[data-anat]');return a.getBoundingClientRect().top+scrollY})()")
                await pg.evaluate(f"scrollTo(0,{h}-innerHeight*0.15)"); await pg.wait_for_timeout(700)
                ex = float(await pg.evaluate("getComputedStyle(document.querySelector('[data-anat]')).getPropertyValue('--ex')"))
                ck(ex > 0.8, f'anatomy comes apart while scrolling (ex={ex})')
                await pg.evaluate(f"scrollTo(0,{h}+700)"); await pg.wait_for_timeout(900)
                cls = await pg.get_attribute('[data-anat]', 'class')
                ck('is-lit' in cls and 'apart' not in cls, 'anatomy goes back together and switches on')
            else:
                await pg.evaluate("document.querySelector('[data-anat]').scrollIntoView()"); await pg.wait_for_timeout(500)
                ck('is-lit' in await pg.get_attribute('[data-anat]', 'class'), 'reduced motion: anatomy shown assembled and lit')
            await pg.click('[data-anat-toggle]'); await pg.wait_for_timeout(300)
            ck('apart' in await pg.get_attribute('[data-anat]', 'class'), f'[{rm}] "Take it apart" button works')
            await pg.goto(B + '/ru/signage-research/', wait_until='networkidle')
            if rm == 'no-preference':
                await pg.evaluate("document.querySelector('[data-wire]').scrollIntoView({block:'center'})"); await pg.wait_for_timeout(400)
                w = await pg.evaluate("getComputedStyle(document.querySelector('[data-wire]')).getPropertyValue('--wire')")
                ck(w.strip() not in ('', '0%', '0.0%'), f'research line fills while reading ({w.strip()})')
            ck(not errs, f'[{rm}] no JS errors {errs[:2]}')
            await ctx.close()
        await br.close(); print(f'{sum(res)} / {len(res)}')
asyncio.run(main())
