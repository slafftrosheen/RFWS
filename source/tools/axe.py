import asyncio,json
from playwright.async_api import async_playwright
AXE=open('/tmp/axe.min.js').read()
P=[l+x for l in ['','/ru','/de'] for x in ['/','/product-profiles/','/profile-7-lux/','/special/','/projects/','/seamless-signage-transforming-depo-stores-across-the-baltics/','/signage-research/','/contact/','/privacy-policy/']]
async def main():
    async with async_playwright() as p:
        b=await p.chromium.launch(); tot=0
        for w in (390,1440):
            ctx=await b.new_context(viewport={'width':w,'height':900},reduced_motion='reduce'); pg=await ctx.new_page()
            for path in P:
                await pg.goto('http://127.0.0.1:8770'+path,wait_until='networkidle')
                await pg.add_script_tag(content=AXE)
                res=await pg.evaluate("async()=>{const r=await axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa','wcag22aa']}});return r.violations.map(v=>({id:v.id,n:v.nodes.length,ex:v.nodes.slice(0,3).map(x=>x.target.join(' ')+' | '+(x.any[0]?.message||x.all[0]?.message||'').slice(0,110))}))}")
                if res: tot+=len(res); print(w,path,json.dumps(res)[:1200])
            await ctx.close()
        await b.close(); print('violations',tot)
asyncio.run(main())
