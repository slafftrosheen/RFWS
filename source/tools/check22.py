import asyncio, json, sys
from playwright.async_api import async_playwright
B = 'http://127.0.0.1:8080'
async def main():
    async with async_playwright() as p:
        br = await p.chromium.launch()
        out = {}
        for vw in (1440, 390):
            ctx = await br.new_context(viewport={'width': vw, 'height': 900})
            pg = await ctx.new_page(); errs = []
            pg.on('console', lambda m: errs.append(m.text) if m.type == 'error' else None)
            pg.on('pageerror', lambda e: errs.append(str(e)))
            for u in ['/', '/profile-4/', '/profile-2/', '/profile-7-lux/', '/ru/signage-research/', '/product-profiles/', '/contact/', '/lv/projects/', '/special/']:
                await pg.goto(B + u, wait_until='networkidle')
                r = await pg.evaluate('''() => ({
                  overflow: document.documentElement.scrollWidth - innerWidth,
                  rail: document.querySelectorAll('[data-rail-list] > li').length,
                  film: document.querySelectorAll('.film-run:not(.film-dup) li').length,
                  slides: document.querySelectorAll('.hero-media .slide').length,
                  anat: (document.querySelector('.an-svg')||{getBoundingClientRect:()=>({height:0})}).getBoundingClientRect().height|0,
                  anatN: document.querySelectorAll('.anat-list li').length,
                  ral: !!document.querySelector('.ral img'), diagram: !!document.querySelector('.diagram img'),
                  h1: document.querySelectorAll('h1').length, portal: !!document.querySelector('.hdr-portal'),
                  basketBtn: !!document.querySelector('[data-basket-add]'),
                  phead: !!document.querySelector('.phead-photo')})''')
                out[f'{vw}{u}'] = r
            # interactions on profile page
            await pg.goto(B + '/profile-4/', wait_until='networkidle')
            await pg.click('[data-basket-add]')
            n = await pg.text_content('[data-basket-n]')
            href = await pg.get_attribute('[data-basket-link]', 'href')
            await pg.evaluate("document.querySelector('[data-anat]').scrollIntoView({block:'center'})"); await pg.wait_for_timeout(500)
            ex_mid = await pg.evaluate("getComputedStyle(document.querySelector('[data-anat]')).getPropertyValue('--ex')")
            await pg.click('[data-anat-toggle]'); await pg.wait_for_timeout(300)
            cls = await pg.get_attribute('[data-anat]', 'class')
            await pg.click('[data-anat-toggle]'); await pg.wait_for_timeout(1200)
            cls2 = await pg.get_attribute('[data-anat]', 'class')
            out[f'{vw}-interact'] = {'basket': n, 'href': href, 'ex_mid': ex_mid, 'after_click': cls, 'after_2nd': cls2, 'errors': errs[:5]}
            await ctx.close()
        await br.close()
        for k, v in out.items(): print(k, v)
asyncio.run(main())
