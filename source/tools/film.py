import asyncio, os, glob
from playwright.async_api import async_playwright
B='http://127.0.0.1:8770'
async def main():
    async with async_playwright() as p:
        br=await p.chromium.launch()
        ctx=await br.new_context(viewport={'width':1440,'height':810},record_video_dir='/tmp/rfcms/video',record_video_size={'width':1440,'height':810})
        pg=await ctx.new_page()
        await pg.goto(B+'/'); await pg.wait_for_timeout(3200)
        await pg.mouse.move(900,400);
        for x in range(900,1300,20): await pg.mouse.move(x,420-(x-900)//6); await pg.wait_for_timeout(25)
        async def glide(to,ms=1600):
            await pg.evaluate(f"(()=>{{const y0=scrollY,y1={to},t0=performance.now();function s(t){{const k=Math.min(1,(t-t0)/{ms});const e=k<.5?2*k*k:1-Math.pow(-2*k+2,2)/2;scrollTo(0,y0+(y1-y0)*e);if(k<1)requestAnimationFrame(s)}}requestAnimationFrame(s)}})()"); await pg.wait_for_timeout(ms+150)
        await glide(560); await pg.wait_for_timeout(1500)
        y=await pg.evaluate("document.querySelector('#services').offsetTop-90"); await glide(y); await pg.wait_for_timeout(3200)
        box=await pg.locator('#services .svc').nth(1).bounding_box(); await pg.mouse.move(box['x']+60,box['y']+40); await pg.wait_for_timeout(2600)
        y=await pg.evaluate("document.querySelector('#profiles').offsetTop-40"); await glide(y)
        for i in range(3):
            b=await pg.locator('#profiles .pcard').nth(i).bounding_box(); await pg.mouse.move(b['x']+b['width']/2,b['y']+80); await pg.wait_for_timeout(900)
        y=await pg.evaluate("document.querySelector('#special').offsetTop"); await glide(y)
        for x in range(200,1250,30): await pg.mouse.move(x,300+((x//30)%5)*20); await pg.wait_for_timeout(30)
        await pg.goto(B+'/ru/profile-2/'); await pg.wait_for_timeout(600)
        y=await pg.evaluate("document.querySelector('.lightcard').getBoundingClientRect().top+scrollY-200"); await glide(y,1200); await pg.wait_for_timeout(4200)
        await pg.goto(B+'/de/profile-3/'); await pg.wait_for_timeout(500)
        y=await pg.evaluate("document.querySelector('.lightcard').getBoundingClientRect().top+scrollY-200"); await glide(y,1000); await pg.wait_for_timeout(3800)
        await pg.goto(B+'/lv/signage-research/'); await pg.wait_for_timeout(500)
        y=await pg.evaluate("document.querySelector('.techs').getBoundingClientRect().top+scrollY-260"); await glide(y,1400); await pg.wait_for_timeout(1800)
        y=await pg.evaluate("document.querySelector('.lt-grid').getBoundingClientRect().top+scrollY-200"); await glide(y,1200); await pg.wait_for_timeout(4000)
        await pg.goto(B+'/contact/'); await pg.wait_for_timeout(500)
        await pg.fill('input[name=name]','Anna'); await pg.fill('input[name=email]','anna@example.com'); await pg.fill('textarea[name=message]','3D letters, 1.2 m, facade in Riga')
        await pg.click('form[data-form] button[type=submit]'); await pg.wait_for_timeout(3200)
        await pg.goto(B+'/no-such-page/'); await pg.wait_for_timeout(5200)
        await ctx.close(); await br.close()
asyncio.run(main())
