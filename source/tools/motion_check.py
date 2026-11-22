import asyncio
from playwright.async_api import async_playwright
import sys; B=sys.argv[1] if len(sys.argv)>1 else 'http://127.0.0.1:8770'; res=[]
def ck(c,m): res.append(c); print(('PASS ' if c else 'FAIL ')+m)
async def main():
    async with async_playwright() as p:
        br=await p.chromium.launch()
        for rm in ['no-preference','reduce']:
            ctx=await br.new_context(viewport={'width':1440,'height':900},reduced_motion=rm); pg=await ctx.new_page(); errs=[]
            pg.on('pageerror',lambda e: errs.append(str(e))); pg.on('console',lambda m: errs.append(m.text) if m.type=='error' else None)
            await pg.goto(B+'/',wait_until='networkidle'); await pg.wait_for_timeout(2600)
            h1=await pg.evaluate("document.querySelector('[data-ignite]').getAttribute('aria-label')||document.querySelector('[data-ignite]').textContent")
            ck(h1.strip()=='B2B sign supplier', f'[{rm}] headline text intact for screen readers')
            if rm=='no-preference':
                ck(await pg.evaluate("document.querySelector('[data-ignite]').classList.contains('go') && document.querySelectorAll('.ig-l').length>10"), 'headline ignites letter by letter')
                ck(await pg.evaluate("getComputedStyle(document.querySelector('.ticker-track')).animationName")=='tick','LED ticker runs')
            else:
                ck(await pg.evaluate("document.querySelectorAll('.ig-l').length")==0,'reduced motion: headline not split/animated')
                ck(await pg.evaluate("getComputedStyle(document.querySelector('.ticker-track')).animationName")=='none','reduced motion: ticker still')
            await pg.evaluate("document.querySelector('#services').scrollIntoView()"); await pg.wait_for_timeout(2500)
            n=await pg.evaluate("document.querySelectorAll('#services .lt.is-on').length"); ck(n==4, f'[{rm}] 4 service animations loaded ({n})')
            if rm=='reduce':
                f=await pg.evaluate("(()=>{const el=document.querySelector('#services .lt.is-on');return el._a? [el._a.currentFrame, el._a.totalFrames, el._a.isPaused]:null})()")
                ck(f and f[0]>=f[1]-2 and f[2], f'reduced motion: animations rest on their final frame {f}')
            await pg.evaluate("document.querySelector('.stats').scrollIntoView()"); await pg.wait_for_timeout(1800)
            st=await pg.evaluate("[...document.querySelectorAll('[data-count]')].map(e=>e.textContent)")
            ck(st==['1993','6 000 m²','22','EU + UK'], f'[{rm}] numbers end on the real values {st}')
            await pg.goto(B+'/de/profile-7-lux/',wait_until='networkidle'); await pg.evaluate("document.querySelector('.lightcard').scrollIntoView()"); await pg.wait_for_timeout(2500)
            ck(await pg.evaluate("!!document.querySelector('.lightcard .lt.is-on svg')"), f'[{rm}] profile light-type animation shows')
            await pg.goto(B+'/signage-research/',wait_until='networkidle'); await pg.evaluate("document.querySelector('.lt-grid').scrollIntoView()"); await pg.wait_for_timeout(2000)
            ck(await pg.evaluate("!!document.querySelector('.lt-grid.is-on svg')"), f'[{rm}] LUMIGRID animation shows')
            await pg.goto(B+'/nope/',wait_until='networkidle'); await pg.wait_for_timeout(1500)
            ck(await pg.evaluate("!!document.querySelector('.lt-off.is-on svg')"), f'[{rm}] 404 switched-off animation shows')
            errs[:]=[x for x in errs if 'status of 404' not in x]   # the 404 page itself is meant to be a 404
            ck(not errs, f'[{rm}] no JS errors {errs[:2]}')
            await ctx.close()
        await br.close()
    print(sum(res),'/',len(res))
asyncio.run(main())
