#!/usr/bin/env python3
"""2.2: import images the 2.1 migration missed + add a 400 px size to every photo."""
import json, os, re, subprocess, sys
from PIL import Image
SITE = '/tmp/rfcms/site/'; UP = SITE + 'uploads/'
M = json.load(open(SITE + 'data/media.json'))
NEW = {  # id: (url, alt en, note)
 'profile-1-diagram': 'https://reclamefabriek.eu/wp-content/uploads/Profile-1-1-1.png',
 'profile-2-diagram': 'https://reclamefabriek.eu/wp-content/uploads/Profile-2-NEON.png',
 'profile-4-diagram': 'https://reclamefabriek.eu/wp-content/uploads/Profile-4.png',
 'profile-5-diagram': 'https://reclamefabriek.eu/wp-content/uploads/Profile-5.png',
 'profile-7-3-diagram': 'https://reclamefabriek.eu/wp-content/uploads/Profile-7-3.png',
 'profile-7-dtf-diagram': 'https://reclamefabriek.eu/wp-content/uploads/Profile-7DFT.png',
 'profile-7-rcp-diagram': 'https://reclamefabriek.eu/wp-content/uploads/Profile-7RCP.png',
 'profile-7-tgp-diagram': 'https://reclamefabriek.eu/wp-content/uploads/Profile-7TGP.png',
 'profile-8-diagram': 'https://reclamefabriek.eu/wp-content/uploads/Profile-ACRYLIC.png',
 'night-european-cityscape-neon-signage': 'https://reclamefabriek.eu/wp-content/uploads/2024/09/night-european-cityscape-neon-signage-flares-motio-3.png',
 '29356837-776801649184212-908443522212823040-n': 'https://reclamefabriek.eu/wp-content/uploads/29356837_776801649184212_908443522212823040_n.jpg',
 '434575192-940810241383816-5151635588363468865-n-1': 'https://reclamefabriek.eu/wp-content/uploads/434575192_940810241383816_5151635588363468865_n-1.jpg',
 'monotone-photo-of-signage-digital-sign': 'https://reclamefabriek.eu/wp-content/uploads/monotone-photo-of-signage-digital-sign-positioned-1.png',
}
STOCK = {'night-european-cityscape-neon-signage', 'monotone-photo-of-signage-digital-sign'}
def save_sizes(im, fid, sizes):
    out = []
    for s in sizes:
        if s > im.width and out and out[-1] >= im.width: continue   # keep one native-size file
        w = min(s, im.width); h = round(im.height * w / im.width)
        r = im.resize((w, h), Image.LANCZOS) if w != im.width else im
        r.save(UP + f'{fid}-{s}.webp', 'WEBP', quality=80 if s <= 800 else 78, method=6)
        out.append(s)
    return out
for fid, u in NEW.items():
    if fid in M: continue
    fn = '/tmp/dl/' + u.rsplit('/', 1)[-1]
    if not os.path.exists(fn): subprocess.run(['curl', '-fsSL', '-m', '60', u, '-o', fn], check=True)
    im = Image.open(fn); im.load()
    im = im.convert('RGBA') if im.mode in ('P', 'LA', 'RGBA') else im.convert('RGB')
    sizes = save_sizes(im, fid, [400, 800, 1600, 2400])
    M[fid] = {'file': fid, 'w': im.width, 'h': im.height, 'sizes': sizes, 'name': u.rsplit('/', 1)[-1], 'alt': {}, 'added': '2026-11-21T00:00:00',
              'source': 'old site (stock photo, kept in the library, not shown)' if fid in STOCK else 'old site'}
    print('added', fid, im.size, sizes)
# 400 px for every photo (thumbnails, film strip, cards)
n = 0
for fid, m in M.items():
    if 400 in m['sizes']: continue
    src = UP + f"{m['file']}-{min(m['sizes'])}.webp"
    if not os.path.exists(src): print('MISSING', src); continue
    im = Image.open(src); w = min(400, im.width); h = round(im.height * w / im.width)
    im.resize((w, h), Image.LANCZOS).save(UP + f"{m['file']}-400.webp", 'WEBP', quality=78, method=6)
    m['sizes'] = sorted(set(m['sizes']) | {400}); n += 1
print('400px added for', n)
json.dump(M, open(SITE + 'data/media.json', 'w'), ensure_ascii=False, indent=1)
