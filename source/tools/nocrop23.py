#!/usr/bin/env python3
"""Website 2.3.1 · no original photo is cropped: on every page (desktop + phone, both themes) each /uploads/ image must be shown whole:
the drawn picture keeps its own proportions inside its box (object-fit contain, or a box with the same ratio), it is not scaled up
(no zoom), and no parent with overflow hidden cuts it. Also checked while hovering cards and during the hero slideshow."""
import asyncio, re, sys, urllib.request, os
from playwright.async_api import async_playwright
B = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8080'
JS = r"""() => {
  const out = [];
  for (const im of document.querySelectorAll('img')) {
    const src = im.currentSrc || im.src; if (!/\/uploads\//.test(src) || !im.naturalWidth) continue;
    const cs = getComputedStyle(im); if (cs.display === 'none' || im.closest('[hidden],.film-dup,dialog:not([open])')) continue;
    const r = im.getBoundingClientRect(); if (r.width < 2 || r.height < 2) continue;
    const nat = im.naturalWidth / im.naturalHeight, box = r.width / r.height;
    const fit = cs.objectFit, sameRatio = Math.abs(nat - box) / nat < 0.025;
    let why = '';
    if (!sameRatio && fit !== 'contain' && fit !== 'scale-down') why = `fit=${fit} ratio ${nat.toFixed(2)} vs box ${box.toFixed(2)}`;
    const tf = cs.transform; if (tf && tf !== 'none') { const m = new DOMMatrix(tf); if (m.a > 1.001 || m.d > 1.001) why += ' scaled ' + m.a.toFixed(3); }
    for (let a = im.parentElement; a && a !== document.body; a = a.parentElement) {
      const s = getComputedStyle(a); if (s.overflow === 'visible' && s.overflowX === 'visible' && s.overflowY === 'visible' && s.clipPath === 'none') continue;
      if (/film-row|film|team|rail|pcards|scroll|cmp-scroll|cmdk-list/.test(a.className) && (s.overflowX !== 'hidden' || /film/.test(a.className))) continue;  // scrolling strips: photo is whole, just scrolled
      const p = a.getBoundingClientRect();
      if (r.left < p.left - 1 || r.right > p.right + 1 || r.top < p.top - 1 || r.bottom > p.bottom + 1) { if (!/film|team|rail|pcards/.test(a.className)) why += ' clipped by .' + String(a.className).split(' ')[0]; }
      break;
    }
    if (why) out.push(src.split('/uploads/')[1] + ' · ' + why.trim());
  }
  return out;
}"""
async def main():
    sm = urllib.request.urlopen(B + '/sitemap.xml').read().decode()
    paths = sorted(set(u.replace('https://reclamefabriek.eu', '') for u in re.findall(r'<loc>(.*?)</loc>', sm)))
    paths = [x for x in paths if x.count('/') <= 2 or x.startswith(('/de/', '/lv/'))]
    bad = []; n = 0; imgs = 0
    async with async_playwright() as p:
        br = await p.chromium.launch()
        for w, h, th, sel in [(1440, 900, 'light', lambda x: True), (390, 844, 'dark', lambda x: not x.startswith('/lv/'))]:
            ctx = await br.new_context(viewport={'width': w, 'height': h}, color_scheme=th); pg = await ctx.new_page()
            for path in [x for x in paths if sel(x)]:
                await pg.goto(B + path, wait_until='networkidle')
                await pg.evaluate("document.querySelectorAll('img[loading=lazy]').forEach(i=>i.loading='eager')"); await pg.wait_for_timeout(300)
                imgs += await pg.evaluate("[...document.images].filter(i=>/\\/uploads\\//.test(i.currentSrc||i.src)).length")
                res = await pg.evaluate(JS); n += 1
                for r in res: bad.append((w, path, r))
                if path in ('/', '/product-profiles/') and w == 1440:      # hover every card: nothing may zoom
                    for c in (await pg.query_selector_all('.pcard a, .scard a, .gal a, .svc'))[:12]:
                        try: await c.hover(timeout=2000); await pg.wait_for_timeout(700)
                        except Exception: continue
                        for r in await pg.evaluate(JS): bad.append((w, path + ' (hover)', r))
                if path == '/' and w == 1440:
                    await pg.wait_for_timeout(9000)                         # next hero slide
                    for r in await pg.evaluate(JS): bad.append((w, '/ (slideshow)', r))
            await ctx.close()
        print(f'{n} page views, {imgs} photo placements checked', flush=True)
        open('/tmp/nocrop-all.txt','w').write('\n'.join(map(str,bad)))
        for b in bad[:30]: print(b, flush=True)
        print('CROPPED', len(bad), flush=True)
        os._exit(0)
asyncio.run(main())
