import asyncio,json
from playwright.async_api import async_playwright
AXE=open('/tmp/axe.min.js').read()
P=[l+x for l in ['','/ru','/de','/lv'] for x in ['/portal/login/','/portal/register/','/profile-4/','/profile-2/','/','/product-profiles/','/profile-7-lux/','/special/','/projects/','/seamless-signage-transforming-depo-stores-across-the-baltics/','/signage-research/','/contact/','/privacy-policy/']]
async def main():
    async with async_playwright() as p:
        b=await p.chromium.launch(); tot=0
        for w,rm in ((390,'reduce'),(1440,'reduce'),(1440,'no-preference')):
            ctx=await b.new_context(viewport={'width':w,'height':900},reduced_motion=rm,bypass_csp=True); pg=await ctx.new_page()
            for path in P:
                await pg.goto('http://127.0.0.1:8770'+path,wait_until='networkidle')
                if rm=='no-preference':   # visitors see sections after scrolling to them: let the reveals finish first
                    h=await pg.evaluate('document.body.scrollHeight')
                    for y in range(0,h,500): await pg.evaluate(f'scrollTo(0,{y})'); await pg.wait_for_timeout(60)
                    await pg.evaluate('scrollTo(0,0)'); await pg.wait_for_timeout(900)
                await pg.add_script_tag(content=AXE)
                res=await pg.evaluate("async()=>{const r=await axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa','wcag22aa']}});return r.violations.map(v=>({id:v.id,n:v.nodes.length,ex:v.nodes.slice(0,3).map(x=>x.target.join(' ')+' | '+(x.any[0]?.message||x.all[0]?.message||'').slice(0,110))}))}")
                if res: tot+=len(res); print(w,path,json.dumps(res)[:1200])
            await ctx.close()
        await b.close(); print('pages checked',len(P)*3,'violations',tot)
asyncio.run(main())
