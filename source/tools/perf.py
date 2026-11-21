#!/usr/bin/env python3
"""Website 2.2 · page weight on a phone (390 px): first view (above the fold + idle) and full scroll. Text sizes are also gzipped (what a real server sends)."""
import asyncio, gzip, json, sys
from playwright.async_api import async_playwright
SITES = {'2.1': 'http://127.0.0.1:8081', '2.2': 'http://127.0.0.1:8080'}
PAGES = ['/', '/profile-4/', '/signage-research/']
async def measure(br, base, path, scroll):
    ctx = await br.new_context(viewport={'width': 390, 'height': 844}, device_scale_factor=2, is_mobile=True)
    pg = await ctx.new_page(); res = []
    async def onresp(r):
        try: b = await r.body()
        except Exception: return
        ct = r.headers.get('content-type', '')
        text = any(t in ct for t in ('html', 'css', 'javascript', 'json', 'svg', 'xml'))
        res.append({'u': r.url, 'raw': len(b), 'wire': len(gzip.compress(b, 6)) if text else len(b), 'ct': ct})
    pg.on('response', lambda r: asyncio.ensure_future(onresp(r)))
    await pg.goto(base + path, wait_until='load')
    lcp = await pg.evaluate('''() => new Promise(r => { let v = 0; new PerformanceObserver(l => { for (const e of l.getEntries()) v = e.startTime; }).observe({type: 'largest-contentful-paint', buffered: true}); setTimeout(() => r(Math.round(v)), 1200); })''')
    if scroll:
        h = await pg.evaluate('document.body.scrollHeight')
        for y in range(0, h, 600): await pg.evaluate(f'scrollTo(0,{y})'); await pg.wait_for_timeout(120)
        await pg.wait_for_timeout(800)
    else: await pg.wait_for_timeout(600)
    await ctx.close()
    return {'requests': len(res), 'kb': round(sum(x['wire'] for x in res) / 1024), 'lcp_ms': lcp,
            'css_js_kb': round(sum(x['wire'] for x in res if 'css' in x['ct'] or 'javascript' in x['ct']) / 1024),
            'img_kb': round(sum(x['wire'] for x in res if 'image' in x['ct']) / 1024)}
async def main():
    out = {}
    async with async_playwright() as p:
        br = await p.chromium.launch()
        for v, base in SITES.items():
            for path in PAGES:
                for mode in ('first', 'scrolled'):
                    out[f'{v} {path} {mode}'] = await measure(br, base, path, mode == 'scrolled')
                    print(f'{v:4} {path:22} {mode:9}', out[f'{v} {path} {mode}'], flush=True)
        await br.close()
    json.dump(out, open('/tmp/rfcms/perf.json', 'w'), indent=1)
asyncio.run(main())
