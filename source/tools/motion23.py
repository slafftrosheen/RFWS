#!/usr/bin/env python3
"""Website 2.3 · new motion in full vs reduced motion and with "Pause motion": theme reveal, process line, card tilt, back-to-top ring, Lotties."""
import asyncio, sys
from playwright.async_api import async_playwright
B = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8080'; res = []
def ck(c, m): res.append(c); print(('PASS ' if c else 'FAIL ') + m)
NEW = ['rf_step_design', 'rf_step_bend', 'rf_step_paint', 'rf_step_led', 'rf_step_install', 'rf_build_letter']
async def main():
    async with async_playwright() as p:
        br = await p.chromium.launch()
        for rm in ['no-preference', 'reduce']:
            ctx = await br.new_context(viewport={'width': 1440, 'height': 900}, reduced_motion=rm); pg = await ctx.new_page(); errs = []
            pg.on('pageerror', lambda e: errs.append(str(e)))
            await pg.goto(B + '/', wait_until='networkidle')
            full = rm == 'no-preference'
            if full:
                await pg.evaluate("document.documentElement.style.scrollBehavior='auto'; scrollTo(0, document.querySelector('#process').offsetTop - 900)"); await pg.wait_for_timeout(400)
                on0 = await pg.locator('.proc-step.is-on').count()
                for y in range(0, 1600, 100): await pg.evaluate('scrollBy(0,100)'); await pg.wait_for_timeout(60)
                await pg.wait_for_timeout(800); on1 = await pg.locator('.proc-step.is-on').count()
                ck(on0 < 6 and on1 == 6, f'full motion: steps light up one by one as you scroll ({on0} → {on1})')
                w = await pg.evaluate("getComputedStyle(document.querySelector('[data-proc]'),'::after').width"); ck(w != '0px', f'full motion: the wire is powered ({w})')
                loaded = await pg.evaluate("[...document.querySelectorAll('[data-lottie]')].filter(e => /rf_(step|build)/.test(e.dataset.lottie) && e.querySelector('svg')).length")
                ck(loaded >= 6, f'full motion: new process animations loaded ({loaded})')
                await pg.evaluate("document.querySelector('#profiles').scrollIntoView()"); await pg.wait_for_timeout(500)
                card = pg.locator('.rail .pcard a >> nth=0'); box = await card.bounding_box()
                await pg.mouse.move(box['x'] + box['width'] * .8, box['y'] + box['height'] * .3); await pg.wait_for_timeout(200)
                t = await card.evaluate("e => getComputedStyle(e).transform"); ck(t not in ('none', ''), 'full motion: profile card tilts toward the pointer')
                await pg.evaluate('scrollTo(0, document.body.scrollHeight)'); await pg.wait_for_timeout(500)
                ck(await pg.evaluate("document.querySelector('[data-totop]').classList.contains('on') && +getComputedStyle(document.querySelector('[data-totop]')).getPropertyValue('--read') > 90"), 'back-to-top ring is full at the end')
                await pg.evaluate('scrollTo(0,0)'); await pg.click('[data-theme-btn]'); await pg.click('#theme-menu [data-theme-set="dark"]')
                vt = await pg.evaluate("document.documentElement.classList.contains('theme-vt')"); await pg.wait_for_timeout(1800)
                ck(vt and await pg.evaluate("document.documentElement.dataset.theme") == 'dark', 'full motion: theme spreads from the switch (view transition)')
                ck(not await pg.evaluate("document.documentElement.classList.contains('theme-vt')"), 'transition class removed afterwards')
                # pause motion → theme switch is instant, lotties paused
                await pg.click('.mo-ftr'); await pg.wait_for_timeout(200)
                await pg.click('[data-theme-btn]'); await pg.click('#theme-menu [data-theme-set="light"]')
                ck(not await pg.evaluate("document.documentElement.classList.contains('theme-vt')") and await pg.evaluate("document.documentElement.dataset.theme") == 'light', 'paused: theme switches instantly')
                await pg.click('.mo-ftr')
            else:
                ck(await pg.locator('.proc-step.is-on').count() == 6, 'reduced motion: all six steps shown lit, no scroll effect')
                card = pg.locator('.rail .pcard a >> nth=0'); await card.scroll_into_view_if_needed(); box = await card.bounding_box()
                await pg.mouse.move(box['x'] + box['width'] * .8, box['y'] + box['height'] * .3); await pg.wait_for_timeout(200)
                ck(not await card.evaluate("e => e.classList.contains('tilt')"), 'reduced motion: no card tilt')
                await pg.evaluate('scrollTo(0,0)'); await pg.click('[data-theme-btn]'); await pg.click('#theme-menu [data-theme-set="dark"]')
                ck(not await pg.evaluate("document.documentElement.classList.contains('theme-vt')") and await pg.evaluate("document.documentElement.dataset.theme") == 'dark', 'reduced motion: theme switches instantly')
            await pg.goto(B + '/contact/', wait_until='networkidle'); await pg.wait_for_timeout(600)
            ck(await pg.locator('.live-sign svg').count() == 1, f'neon OPEN sign renders ({rm})')
            ck(not errs, f'no JS errors ({rm})' + ('' if not errs else ': ' + errs[0]))
            await ctx.close()
        print(f'\n{sum(res)}/{len(res)} passed', flush=True)
        import os; os._exit(0)
asyncio.run(main())
