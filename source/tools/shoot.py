import asyncio, re, os, json
from playwright.async_api import async_playwright
B='http://127.0.0.1:8770'; O='/tmp/rfcms/shots'; os.makedirs(O,exist_ok=True)
SITE=[('site-ru-home','/ru/',1440),('site-de-profile','/de/profile-7-lux/',1440),('site-lv-profiles','/lv/product-profiles/',1440),('site-ru-mobile','/ru/',390)]
async def main():
    async with async_playwright() as p:
        br=await p.chromium.launch()
        for n,u,w in SITE:
            c=await br.new_context(viewport={'width':w,'height':900 if w>500 else 844},device_scale_factor=1 if w>500 else 2,reduced_motion='reduce'); pg=await c.new_page()
            await pg.goto(B+u,wait_until='networkidle'); await pg.wait_for_timeout(400)
            if n=='site-ru-home': await pg.click('[data-lang] summary'); await pg.wait_for_timeout(200)
            await pg.screenshot(path=f'{O}/{n}.png'); await c.close()
        # admin: log in with a temporary user created directly
        c=await br.new_context(viewport={'width':1440,'height':900},reduced_motion='reduce'); pg=await c.new_page()
        await pg.goto(B+'/admin/'); await pg.fill('input[name=login]','demo'); await pg.fill('input[name=pw]','demo-password-123'); await pg.click('button[type=submit]')
        shots=[('admin-dashboard','',None,None),('admin-home-editor','a=page&id=home','ru','#s-services'),('admin-profile-editor','a=profile&slug=profile-7-lux','de',None),
               ('admin-profiles-list','a=profiles',None,None),('admin-media','a=media',None,None),('admin-translations','a=translate&l=ru',None,None)]
        for n,q,l,anchor in shots:
            await pg.goto(B+'/admin/'+('?'+q if q else '')+(('&l='+l) if l else ''))
            if l: await pg.click(f'[data-ltab="{l}"]')
            if anchor: await pg.evaluate(f"document.querySelector('{anchor}').scrollIntoView()"); await pg.evaluate("window.scrollBy(0,-130)")
            await pg.wait_for_timeout(300); await pg.screenshot(path=f'{O}/{n}.png')
        await pg.goto(B+'/admin/?a=profile&slug=profile-7-lux'); await pg.click('#s-media [data-pick="many"]'); await pg.wait_for_selector('[data-mgrid] button'); await pg.wait_for_timeout(800)
        b=pg.locator('[data-mgrid] button'); await b.nth(2).click(); await b.nth(5).click(); await pg.screenshot(path=f'{O}/admin-picker.png')
        await br.close()
asyncio.run(main())
