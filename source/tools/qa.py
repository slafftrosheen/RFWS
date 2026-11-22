import asyncio, re, json
from playwright.async_api import async_playwright
import os; B=os.environ.get('RF_BASE','http://127.0.0.1:8770')
async def main():
    import urllib.request
    sm=urllib.request.urlopen(B+'/sitemap.xml').read().decode()
    paths=sorted(set(u.replace('https://reclamefabriek.eu','') for u in re.findall(r'<loc>(.*?)</loc>',sm)))
    issues=[]; links=set(); n=0
    async with async_playwright() as p:
        br=await p.chromium.launch()
        import sys
        MODE=sys.argv[1] if len(sys.argv)>1 else 'all'
        runs=[(1440,900,lambda x:True),(390,844,lambda x: x.startswith(('/ru/','/de/','/lv/')) or x.count('/')==2)] if MODE=='all' else [(390,844,lambda x: x.startswith(('/de/','/nl/','/sv/','/lv/'))),(360,780,lambda x: x.startswith('/de/'))]
        for vw,vh,sel in runs:
            ctx=await br.new_context(viewport={'width':vw,'height':vh}); pg=await ctx.new_page(); errs=[]; bad=[]
            pg.on('console', lambda m: errs.append(m.text) if m.type=='error' else None)
            pg.on('response', lambda r: bad.append((r.status,r.url)) if r.status>=400 and '127.0.0.1' in r.url else None)
            for path in [x for x in paths if sel(x)]:
                errs.clear(); bad.clear(); n+=1
                await pg.goto(B+path, wait_until='networkidle')
                await pg.evaluate("document.querySelectorAll('img[loading=lazy]').forEach(i=>i.loading='eager')"); await pg.wait_for_timeout(250)
                r=await pg.evaluate("""()=>({sw:document.documentElement.scrollWidth,cw:document.documentElement.clientWidth,
                  broken:[...document.images].filter(i=>!i.hasAttribute('data-lb-img')&&i.complete&&i.naturalWidth===0).map(i=>i.src),
                  links:[...document.querySelectorAll('a[href^="/"]')].map(a=>a.getAttribute('href')),
                  reds:[...document.querySelectorAll('.btn-red')].filter(e=>e.offsetParent!==null).length,
                  h1:document.querySelectorAll('h1').length, lang:document.documentElement.lang,
                  over:[...document.querySelectorAll('h1,h2,h3,.btn,.chip,.nav a,.tag')].filter(e=>e.scrollWidth>e.clientWidth+2&&getComputedStyle(e).overflow!=='visible').length})""")
                links.update(l.split('#')[0].split('?')[0] for l in r['links'])
                if r['sw']>r['cw']+1: issues.append((vw,path,'hscroll',r['sw']))
                if r['broken']: issues.append((vw,path,'broken img',r['broken'][:2]))
                if errs: issues.append((vw,path,'console',errs[:2]))
                if bad: issues.append((vw,path,'4xx',bad[:2]))
                if r['h1']!=1: issues.append((vw,path,'h1',r['h1']))
                if r['reds']>1: issues.append((vw,path,'reds',r['reds']))
            await ctx.close()
        ctx=await br.new_context(); pg=await ctx.new_page()
        for l in sorted(links):
            if not l: continue
            resp=await pg.goto(B+l)
            if resp.status>=400: issues.append(('link',l,resp.status))
        print(n,'page loads,',len(paths),'pages,',len(links),'internal links', flush=True)
        for i in issues[:40]: print(i, flush=True)
        print('ISSUES',len(issues), flush=True)
        import os as _o; _o._exit(0)
asyncio.run(main())
