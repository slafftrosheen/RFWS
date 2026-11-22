#!/usr/bin/env python3
"""Website 2.3 · transfer size on a phone (390 px, 2×), first view and scrolled to the end.
Text (HTML, CSS, JS, JSON, SVG) is counted gzip-compressed, as the live server sends it (Apache deflate / PHP zlib); images and fonts as they are."""
import asyncio, sys, gzip, urllib.request
from playwright.async_api import async_playwright
B = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8080'
TEXT = ('text/', 'javascript', 'json', 'svg', 'xml')
async def measure(br, path, scroll):
    ctx = await br.new_context(viewport={'width': 390, 'height': 844}, device_scale_factor=2, is_mobile=True); pg = await ctx.new_page()
    seen = []; pg.on('response', lambda r: seen.append(r))
    await pg.goto(B + path, wait_until='networkidle'); await pg.wait_for_timeout(1500)
    if scroll:
        h = await pg.evaluate('document.body.scrollHeight')
        for y in range(0, h, 400): await pg.evaluate(f'scrollTo(0,{y})'); await pg.wait_for_timeout(120)
        await pg.wait_for_timeout(1500)
    tot = 0
    for r in seen:
        try: body = await r.body()
        except Exception: continue
        ct = r.headers.get('content-type', '')
        tot += len(gzip.compress(body, 6)) if any(t in ct for t in TEXT) else len(body)
    await ctx.close(); return tot
async def main():
    async with async_playwright() as p:
        br = await p.chromium.launch()
        for path in (sys.argv[2:] or ['/', '/profile-4/', '/signage-research/', '/contact/']):
            a = await measure(br, path, False); b = await measure(br, path, True)
            print(f'{path:22} first view {a/1024:6.0f} KB   scrolled to the end {b/1024:6.0f} KB', flush=True)
        import os; os._exit(0)
asyncio.run(main())
