#!/usr/bin/env python3
"""Website 2.5 · LUMIGRID page checks (Playwright). Server: php -S 127.0.0.1:8080 index.php in site/.
Checks: 6 languages render, no console errors, hero word lit (canvas pixel stats), lab switches effect + palette and the
stage changes, pause stops the loop, reduced motion = still frames, no horizontal scroll at 1440/390/360, links,
axe WCAG 2.2 AA in light + dark. Results are printed; exit code 1 on any failure."""
import json, os, sys, time
from playwright.sync_api import sync_playwright

BASE = os.environ.get('RF_BASE', 'http://127.0.0.1:8080')
AXE = open('/tmp/axe.min.js').read()
LANGS = ['', 'lv/', 'ru/', 'nl/', 'de/', 'sv/']
ok = fail = 0
def check(name, cond, info=''):
    global ok, fail
    if cond: ok += 1
    else: fail += 1; print('FAIL', name, info)

HERO = """() => { const c = document.querySelector('[data-lg-word]'); if (!c || !c.width) return {w:0};
  const x = c.getContext('2d').getImageData(0,0,c.width,c.height).data; let lit=0, warm=0;
  for (let i=0;i<x.length;i+=16){ const v=x[i]+x[i+1]+x[i+2]; if (v>200) lit++; if (x[i]>x[i+2]+40) warm++; }
  return {w:c.width, h:c.height, lit, warm, on: document.querySelector('[data-lg-hero]').classList.contains('lg-on'), shown: getComputedStyle(c).display}; }"""

with sync_playwright() as p:
    br = p.chromium.launch()
    # ---- every language, desktop
    for lp in LANGS:
        pg = br.new_page(viewport={'width': 1440, 'height': 900}); errs = []
        pg.on('console', lambda m: errs.append(m.text) if m.type == 'error' else None); pg.on('pageerror', lambda e: errs.append(str(e)))
        r = pg.goto(f'{BASE}/{lp}lumigrid/', wait_until='networkidle'); time.sleep(2.2)
        check(f'{lp} status', r.status == 200, r.status)
        check(f'{lp} no console errors', not errs, errs[:3])
        h = pg.evaluate(HERO)
        check(f'{lp} hero canvas lit', h.get('on') and h.get('lit', 0) > 150 and h.get('warm', 0) > 100, h)
        check(f'{lp} h1 in DOM', pg.locator('h1').count() == 1 and 'LUMIGRID' in pg.locator('h1').inner_text())
        check(f'{lp} 24 effects', pg.locator('[data-fx]').count() == 24, pg.locator('[data-fx]').count())
        check(f'{lp} 14 palettes', pg.locator('[data-pal]').count() == 14)
        check(f'{lp} nav current', pg.locator('nav.nav a[aria-current="page"]').count() == 1 and '/lumigrid/' in pg.locator('nav.nav a[aria-current="page"]').get_attribute('href'))
        check(f'{lp} nav link internal', pg.locator('nav.nav > ul a[href$="/lumigrid/"]').get_attribute('target') is None)
        check(f'{lp} no hscroll', pg.evaluate('document.documentElement.scrollWidth <= innerWidth'))
        check(f'{lp} one red', pg.locator('main .btn-red').count() == 0)
        check(f'{lp} lang attr', pg.evaluate('document.documentElement.lang') == (lp[:2] or 'en'))
        pg.close()

    # ---- lab behaviour
    pg = br.new_page(viewport={'width': 1440, 'height': 900})
    pg.goto(f'{BASE}/lumigrid/', wait_until='networkidle'); pg.locator('#lab').scroll_into_view_if_needed(); time.sleep(1.2)
    S = "() => document.querySelector('[data-lg-lab]')._lgStats()"
    a = pg.evaluate(S); check('lab running', a['running'] and a['lit'] > 200, a)
    time.sleep(.5); b = pg.evaluate(S); check('lab animates', a['sum'] != b['sum'], (a, b))
    pg.locator('[data-fx="chase"]').click(); time.sleep(.4); c = pg.evaluate(S)
    check('effect switched', c['fx'] == 'chase' and pg.locator('[data-fx="chase"]').get_attribute('aria-pressed') == 'true' and pg.locator('[data-fx="gradient"]').get_attribute('aria-pressed') == 'false', c)
    check('chase lights fewer pixels', c['lit'] < b['lit'], (b['lit'], c['lit']))
    check('live text', 'comet' in pg.locator('[data-lg-name]').inner_text().lower())
    pg.locator('[data-fx="solid"]').click(); pg.locator('[data-pal="8"]').click(); time.sleep(.3); d1 = pg.evaluate(S)
    check('palette switched', d1['pal'] == 8, d1)
    check('pwm label solid', pg.locator('[data-lg-pwm]').inner_text().strip() != '')
    for fx in ['solid','breathe','chase','sweep','reveal','sparkle','gradient','strobe','fire','aurora','colorwave','staticpulse','rainbow','colorfade','scanner','theater','twinkle','candle','heartbeat','ripple','dissolve','plasma','lightning','confetti']:
        pg.locator(f'[data-fx="{fx}"]').click(); time.sleep(.25)
    s2 = pg.evaluate(S); check('all 24 effects ran', s2['fx'] == 'confetti')
    # pause button stops the loop
    pg.locator('.mo-hero').click(); pg.locator('#lab').scroll_into_view_if_needed(); time.sleep(.4); e1 = pg.evaluate(S); time.sleep(.5); e2 = pg.evaluate(S)
    check('pause stops lab', not e2['running'] and e1['sum'] == e2['sum'], (e1, e2))
    check('pause pressed', pg.locator('.mo-hero').get_attribute('aria-pressed') == 'true')
    pg.locator('.mo-hero').click(); pg.locator('#lab').scroll_into_view_if_needed(); time.sleep(.5); check('resume', pg.evaluate(S)['running'])
    check('board lit after scroll', pg.evaluate("() => { document.querySelector('#node').scrollIntoView(); return new Promise(r => setTimeout(() => r(document.querySelector('[data-lg-board]').classList.contains('lit')), 900)); }"))
    check('flow powered', pg.evaluate("() => { document.querySelector('#system').scrollIntoView(); return new Promise(r => setTimeout(() => r(document.querySelector('[data-lg-flow]').classList.contains('in')), 900)); }"))
    errs = []
    pg.close()

    # ---- reduced motion: still frames, still interactive
    ctx = br.new_context(viewport={'width': 1440, 'height': 900}, reduced_motion='reduce'); pg = ctx.new_page(); errs = []
    pg.on('pageerror', lambda e: errs.append(str(e)))
    pg.goto(f'{BASE}/lumigrid/', wait_until='networkidle'); time.sleep(1)
    h = pg.evaluate(HERO); check('RM hero still + lit', h.get('lit', 0) > 150, h)
    pg.locator('#lab').scroll_into_view_if_needed(); time.sleep(.6)
    r1 = pg.evaluate(S); time.sleep(.5); r2 = pg.evaluate(S)
    check('RM lab still', not r1['running'] and r1['sum'] == r2['sum'] and r1['lit'] > 100, (r1, r2))
    pg.locator('[data-fx="scanner"]').click(); time.sleep(.3); r3 = pg.evaluate(S)
    check('RM lab redraws on choice', r3['fx'] == 'scanner' and r3['sum'] != r2['sum'] and r3['lit'] > 20, r3)
    check('RM board lit', pg.locator('[data-lg-board].lit').count() == 1)
    check('RM no errors', not errs, errs)
    ctx.close()

    # ---- phones
    for w in (390, 360):
        pg = br.new_page(viewport={'width': w, 'height': 800}, device_scale_factor=2)
        pg.goto(f'{BASE}/de/lumigrid/', wait_until='networkidle'); time.sleep(1.5)
        check(f'{w} no hscroll', pg.evaluate('document.documentElement.scrollWidth <= innerWidth'), pg.evaluate('document.documentElement.scrollWidth'))
        h = pg.evaluate(HERO); check(f'{w} hero lit', h.get('lit', 0) > 80, h)
        wide = pg.evaluate("() => [...document.querySelectorAll('main *')].filter(e => e.getBoundingClientRect().right > innerWidth + 1 && getComputedStyle(e).position !== 'fixed').map(e => e.className).slice(0,5)")
        check(f'{w} nothing overflows', not wide, wide)
        pg.close()

    # ---- links
    pg = br.new_page()
    pg.goto(f'{BASE}/signage-research/'); check('research button -> /lumigrid/', pg.locator('a.btn[href="/lumigrid/"]').count() == 1)
    pg.goto(f'{BASE}/ru/signage-research/'); check('ru research button', pg.locator('a.btn[href="/ru/lumigrid/"]').count() == 1)
    pg.goto(f'{BASE}/lumigrid/')
    for href in set(pg.eval_on_selector_all('main a[href^="/"]', 'els => els.map(e => e.getAttribute("href"))')):
        rr = pg.request.get(BASE + href.split('#')[0]); check(f'link {href}', rr.status == 200, rr.status)
    check('github link', pg.locator('a[href="https://github.com/slafftrosheen/lumigrid"]').count() == 1)
    sm = pg.request.get(BASE + '/sitemap.xml').text(); check('sitemap', 'reclamefabriek.eu/lumigrid/' in sm and '/sv/lumigrid/' in sm)
    sj = pg.request.get(BASE + '/search.json').text(); check('search entry', '/lumigrid/' in sj)
    pg.close()

    # ---- axe, light + dark, EN + RU
    for theme in ('light', 'dark'):
        for lp in ('', 'ru/'):
            ctx = br.new_context(viewport={'width': 1440, 'height': 900}, color_scheme=theme); pg = ctx.new_page()
            pg.add_init_script(f"try{{localStorage.setItem('rf-theme','{theme}')}}catch(e){{}}")
            pg.goto(f'{BASE}/{lp}lumigrid/', wait_until='networkidle'); time.sleep(1)
            pg.evaluate("() => { document.querySelectorAll('.lg-step,.lg-card').forEach(e=>e.classList.add('in')); }")
            pg.add_script_tag(content=AXE)
            res = pg.evaluate("() => axe.run(document, {runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21a','wcag21aa','wcag22aa']}}).then(r => r.violations.map(v => [v.id, v.nodes.length, v.nodes.slice(0,3).map(n => n.target.join(' '))]))")
            check(f'axe {theme} {lp or "en/"}', not res, res)
            ctx.close()
    print(f'lumigrid25: {ok}/{ok + fail} passed')
    os._exit(1 if fail else 0)
