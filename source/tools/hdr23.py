#!/usr/bin/env python3
"""Website 2.3 · header controls check: layout at 7 widths × 2 themes, language menu, theme switch, search palette, language hint."""
import asyncio, sys
from playwright.async_api import async_playwright
B = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8080'
R = []
def ok(n, c, note=''):
    R.append((n, bool(c))); print(('PASS ' if c else 'FAIL ') + n + ('' if c else f'  [{note}]'))
async def main():
    async with async_playwright() as p:
        br = await p.chromium.launch()
        for theme in ['light', 'dark']:
            for wdt in [1440, 1280, 1100, 1024, 768, 390, 360]:
                ctx = await br.new_context(viewport={'width': wdt, 'height': 860}, color_scheme='dark' if theme == 'dark' else 'light')
                pg = await ctx.new_page()
                for path in ['/', '/de/profile-7-lux/', '/ru/portal/login/']:
                    await pg.goto(B + path, wait_until='networkidle')
                    info = await pg.evaluate('''() => {
                      const vis = el => { if (!el) return null; const r = el.getBoundingClientRect(), cs = getComputedStyle(el); return (r.width > 0 && cs.display !== 'none' && cs.visibility !== 'hidden') ? r : null; };
                      const sel = ['.brand', '.nav', '.hdr-search', '.lang .hdr-ctl', '.theme .hdr-ctl', '.hdr-portal', '.hdr-cta', '.nav-btn'];
                      const boxes = sel.map(s => [s, vis(document.querySelector(s))]).filter(x => x[1] && !(x[0] === '.nav' && getComputedStyle(document.querySelector('.nav')).position === 'fixed'));
                      let overlap = [];
                      for (let i = 0; i < boxes.length; i++) for (let j = i + 1; j < boxes.length; j++) { const a = boxes[i][1], b = boxes[j][1]; if (a.left < b.right - 1 && b.left < a.right - 1 && a.top < b.bottom && b.top < a.bottom) overlap.push(boxes[i][0] + '×' + boxes[j][0]); }
                      const out = boxes.filter(x => x[1].right > innerWidth + 1 || x[1].left < -1).map(x => x[0]);
                      return {theme: document.documentElement.dataset.theme, overlap, out, hscroll: document.documentElement.scrollWidth > innerWidth + 1, h: document.querySelector('.hdr-in').getBoundingClientRect().height};
                    }''')
                    tag = f'{theme} {wdt} {path}'
                    ok(f'theme follows system · {tag}', info['theme'] == theme, info['theme'])
                    ok(f'header items fit, no overlap · {tag}', not info['overlap'] and not info['out'] and info['h'] <= 80, str(info))
                    ok(f'no sideways scroll · {tag}', not info['hscroll'])
                await ctx.close()
        # interactions (desktop)
        ctx = await br.new_context(viewport={'width': 1280, 'height': 860}, locale='lv-LV'); pg = await ctx.new_page()
        await pg.goto(B + '/', wait_until='networkidle')
        ok('language hint offers Latvian to a Latvian browser', await pg.is_visible('[data-lang-hint]') and 'latvi' in (await pg.inner_text('[data-lang-hint]')).lower())
        await pg.click('[data-lang-hint-x]'); await pg.reload(wait_until='networkidle')
        ok('language hint stays closed after dismiss', not await pg.is_visible('[data-lang-hint]'))
        await pg.click('.lang .hdr-ctl')
        ok('language menu opens with 6 languages', await pg.is_visible('#lang-menu') and await pg.locator('#lang-menu a').count() == 6)
        foc = await pg.evaluate('document.activeElement.getAttribute("hreflang")'); ok('focus goes to the current language', foc == 'en', foc)
        await pg.keyboard.press('ArrowDown'); foc = await pg.evaluate('document.activeElement.getAttribute("hreflang")'); ok('arrow keys move in the menu', foc == 'lv', foc)
        await pg.keyboard.press('Escape'); ok('Esc closes the menu, focus back on the button', not await pg.is_visible('#lang-menu') and await pg.evaluate('document.activeElement.matches(".lang .hdr-ctl")'))
        await pg.click('.lang .hdr-ctl'); await pg.click('#lang-menu a[hreflang="de"]'); await pg.wait_for_load_state('networkidle')
        ok('language switch keeps the page', pg.url.endswith('/de/'), pg.url)
        ok('language choice remembered', await pg.evaluate('localStorage.getItem("rf-lang")') == 'de')
        await pg.click('[data-theme-btn]'); await pg.click('#theme-menu [data-theme-set="dark"]'); await pg.wait_for_timeout(900)
        ok('theme menu → dark', await pg.evaluate('document.documentElement.dataset.theme') == 'dark')
        await pg.reload(wait_until='networkidle'); ok('dark theme survives reload', await pg.evaluate('document.documentElement.dataset.theme') == 'dark')
        bg = await pg.evaluate('getComputedStyle(document.body).backgroundColor'); ok('dark page background', bg == 'rgb(17, 18, 20)', bg)
        await pg.click('[data-theme-btn]'); ok('menu marks the current theme', await pg.get_attribute('#theme-menu [data-theme-set="dark"]', 'aria-checked') == 'true')
        await pg.click('#theme-menu [data-theme-set="light"]'); await pg.wait_for_timeout(900)
        ok('theme menu → light', await pg.evaluate('document.documentElement.dataset.theme') == 'light')
        await pg.keyboard.press('Control+k'); await pg.wait_for_timeout(300)
        ok('Ctrl+K opens search', await pg.is_visible('[data-cmdk]'))
        await pg.keyboard.type('neon'); await pg.wait_for_timeout(300)
        n = await pg.locator('.cmdk-o').count(); first = await pg.inner_text('.cmdk-o >> nth=0')
        ok('search finds neon profiles', n >= 2 and 'neon' in first.lower(), f'{n} {first}')
        await pg.keyboard.press('Enter'); await pg.wait_for_url('**/profile-*/'); await pg.wait_for_load_state('networkidle')
        ok('Enter opens the result', '/de/profile' in pg.url, pg.url)
        await pg.keyboard.press('/'); await pg.wait_for_timeout(200); await pg.keyboard.type('zzqx'); await pg.wait_for_timeout(200)
        ok('no-result message', await pg.is_visible('.cmdk-none'))
        await pg.keyboard.press('Escape'); ok('Esc closes search', not await pg.is_visible('[data-cmdk]'))
        await pg.keyboard.press('Control+k'); await pg.keyboard.type('dunkel'); await pg.keyboard.press('Enter'); await pg.wait_for_timeout(900)
        ok('search action switches theme', await pg.evaluate('document.documentElement.dataset.theme') == 'dark')
        # phone drawer
        m = await br.new_context(viewport={'width': 390, 'height': 844}); mp = await m.new_page()
        await mp.goto(B + '/profile-2/', wait_until='networkidle'); await mp.click('[data-nav-btn]')
        ok('phone drawer shows 6 languages', await mp.locator('.nav-langs a').count() == 6 and await mp.is_visible('.nav-langs'))
        await mp.click('.seg-theme [data-theme-set="dark"]'); await mp.wait_for_timeout(900)
        ok('phone drawer theme buttons work', await mp.evaluate('document.documentElement.dataset.theme') == 'dark')
        await mp.click('.nav-langs a[hreflang="sv"]'); await mp.wait_for_load_state('networkidle')
        ok('phone language switch keeps the page', mp.url.endswith('/sv/profile-2/'), mp.url)
        await br.close()
    f = [r for r in R if not r[1]]; print(f'\n{len(R) - len(f)}/{len(R)} passed')
asyncio.run(main())
