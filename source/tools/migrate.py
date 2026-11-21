#!/usr/bin/env python3
"""One-off: old WordPress content (scraped) + translations -> CMS data files + media library."""
import json, os, re, shutil, sys, html, datetime
sys.path.insert(0, os.path.dirname(__file__))
from tr import T, UI, L
SC = '/tmp/rfweb/scrape/'
SITE = '/tmp/rfcms/site/'
DATA = SITE + 'data/'
C = json.load(open(SC + 'content.json'))
MAN = json.load(open(SC + 'imgman.json'))
P = json.load(open(SC + 'profiles_parsed.json'))
LANGS = ['en'] + L
missing = set()
NOW = datetime.datetime.now().replace(microsecond=0).isoformat()

def I(en, must=True):
    if en is None: return {}
    d = {'en': en}
    if en in T: d.update(T[en])
    elif must and en.strip(): missing.add(en)
    return d

def base(u): return u.split('?')[0].rsplit('/', 1)[-1]
def mid(u):
    m = MAN.get(base(u)); return m['id'] if m else None
def mids(us): return [x for x in (mid(u) for u in us) if x]

def jw(rel, obj):
    p = DATA + rel; os.makedirs(os.path.dirname(p), exist_ok=True)
    json.dump(obj, open(p, 'w'), ensure_ascii=False, indent=1)

# ---------------- media library
os.makedirs(SITE + 'uploads', exist_ok=True)
media = {}
for f, m in MAN.items():
    for s in m['sizes']:
        src = f'/tmp/rfweb/site/assets/img/{m["id"]}-{s}.webp'
        if os.path.exists(src): shutil.copy(src, SITE + f'uploads/{m["id"]}-{s}.webp')
    media[m['id']] = {'file': m['id'], 'w': m['w'], 'h': m['h'], 'sizes': m['sizes'], 'name': f, 'alt': {}, 'added': '2026-11-21T00:00:00', 'source': 'old site'}
media['clients-2']['alt'] = UI['clients_alt']
jw('media.json', media)

# ---------------- profiles
SUB = {"Aluminum Box Letters": "Aluminium box letters", "Flat letters": "Flat letters",
       "Frontlit & Side illuminated, Acrylic Box Letters": "Frontlit and side-lit acrylic box letters",
       "Frontlit & indirect (Corona), Aluminum Box Letters": "Frontlit and indirect (corona) aluminium box letters",
       "Frontlit, Aluminum Box Letters": "Frontlit aluminium box letters",
       "Indirect (Corona) Light, Aluminum Box Letters": "Indirect (corona) light aluminium box letters",
       "Lightbox": "Lightbox", "NEON Look Aluminum / Acrylic Box Letters": "Neon-look aluminium / acrylic box letters",
       "Signtrim Frontlit, Aluminum Box Letters": "Signtrim frontlit aluminium box letters"}
KIND = {'Frontlit': 'frontlit', 'Corona (halo)': 'corona', 'Neon look': 'neon', 'Frontlit + corona': 'front_corona',
        'Frontlit + side-lit': 'front_side', 'Not lit': 'unlit', 'Flat, not lit': 'flat', 'Lightbox': 'lightbox'}
LBL = {'NEON imitation': 'Neon imitation', 'Face Panels': 'Face panels', 'Signtrim': 'Signtrim', 'Internal trim': 'Internal trim',
       'Sides': 'Sides', 'Back': 'Back', 'Material': 'Material', 'Surface finish': 'Surface finish', 'LED Components': 'LED components'}
MAT = {'aluminium': {'lv': 'alumīnijs', 'ru': 'алюминий', 'nl': 'aluminium', 'de': 'Aluminium', 'sv': 'aluminium'},
       'acrylic': {'lv': 'akrils', 'ru': 'акрил', 'nl': 'acryl', 'de': 'Acryl', 'sv': 'akryl'},
       'PVC': {'lv': 'PVC', 'ru': 'ПВХ', 'nl': 'pvc', 'de': 'PVC', 'sv': 'PVC'}}
SPECIAL_V = {'LED Components': "LED modules with a 5-year warranty; LED power supplies with a 3-year warranty.",
             'Surface finish': "Tikkurila industrial two-component spray paint. Any RAL or NCS colour, or special coatings.",
             'Material': "Usually aluminium, steel, brass, PVC, acrylic, ACP, wood, plywood or any other flat material",
             'NEON imitation': "Solid acrylic, Ø 10–30 mm", 'Signtrim': "35 standard colours"}
def norm_val(k, v):
    if k in SPECIAL_V: return SPECIAL_V[k]
    v = re.sub(r'(\d)\s*[-–]\s*(\d)', r'\1–\2', v)
    v = re.sub(r'(\d)mm', r'\1 mm', v).replace('1,2', '1.2')
    v = re.sub(r'Alumin(i)?um', 'aluminium', v).replace('Acrylic', 'acrylic')
    v = v.replace(' (for XXL sizes)', ' for XXL sizes').replace('acrylic or PVC frontlit', 'acrylic, or PVC frontlit')
    return v
def tr_val(en):
    if en in T: return {'en': en, **T[en]}
    m = re.match(r'^([\d.,–]+) mm (aluminium|acrylic|PVC)$', en)
    if not m: missing.add(en); return {'en': en}
    n, mat = m.groups(); d = {'en': en}
    for l in L:
        n2 = n.replace('.', ',')
        d[l] = f"{MAT[mat]['ru'].capitalize()} {n2} мм" if l == 'ru' else f"{n2} mm {MAT[mat][l]}"
    return d
PW = {'lv': 'Profils', 'ru': 'Профиль', 'nl': 'Profiel', 'de': 'Profil', 'sv': 'Profil'}
LBW = {'lv': 'gaismas kaste', 'ru': 'световой короб', 'nl': 'lichtbak', 'de': 'Leuchtkasten', 'sv': 'ljuslåda'}
FIXED_T = {'Regular profile 100 / 140': {'lv': 'Standarta profils 100 / 140', 'ru': 'Стандартный профиль 100 / 140', 'nl': 'Standaardprofiel 100 / 140', 'de': 'Standardprofil 100 / 140', 'sv': 'Standardprofil 100 / 140'},
           'Cassette Type': {'lv': 'Kasetes tips', 'ru': 'Кассетный тип', 'nl': 'Cassettetype', 'de': 'Kassettentyp', 'sv': 'Kassettyp'},
           'Pylons / Totems': {'lv': 'Piloni / stelas', 'ru': 'Пилоны / стелы', 'nl': 'Zuilen / totems', 'de': 'Pylonen / Stelen', 'sv': 'Pyloner / totem'},
           'Special': {'lv': 'Īpašās', 'ru': 'Специальные', 'nl': 'Speciaal', 'de': 'Sonderanfertigung', 'sv': 'Special'}}
def title_i18n(t):
    if t in FIXED_T: return {'en': t, **FIXED_T[t]}
    d = {'en': t}
    for l in L:
        x = t
        if x.startswith('Profile '): x = PW[l] + ' ' + x[8:]
        if x.endswith(' lightbox'): x = x[:-9] + (', ' if l in ('lv', 'ru') else ' · ') + LBW[l]
        d[l] = x
    return d
def mmfmt(s): return s.replace(' mm', ' mm').replace('mm ', 'mm ') if s else ''
LETTERS = ['profile-0', 'profile-1-2', 'profile-2', 'profile-3', 'profile-3-1', 'profile-3-2', 'profile-4', 'profile-5',
           'profile-7-lux', 'profile-7-st', 'profile-7-tgp', 'profile-7-rcp', 'profile-7-dtf', 'profile-7-3', 'profile-8']
LIGHT = ['regular-profile-100-140', 'casette-type', 'pylonstotems', 'special', 'profile-5-2', 'profile-7st']
# galleries: from the full extracted blocks
def prof_gallery(slug, cover):
    bl = C[slug]['blocks']
    g = [b[1] for b in bl if b[0] == 'img' and base(b[1]) in MAN and 'RAL-COLOUR' not in b[1]]
    return [x for x in mids(g) if x != mid(cover or '')]
for i, slug in enumerate(LETTERS + LIGHT):
    p = P[slug]
    size = p['size'].replace(' mm – ', '–').replace(' mm', '') + ' mm' if p['size'] else ''
    depth = p['depth'] or ''
    if depth:
        depth = re.sub(r'\s*mm', '', depth); depth = re.sub(r'\s*[-–]\s*', '–', depth).replace(',', ', ').replace(',  ', ', ') + ' mm'
    specs = [{'label': I(LBL[k]), 'value': tr_val(norm_val(k, v))} for k, v in p['spec'].items()]
    sub_en = SUB[C[slug]['blocks'][0][1].rstrip('.')] if slug in LETTERS else 'Lightbox'
    obj = {'slug': slug, 'group': 'lightbox' if slug in LIGHT else 'letters', 'order': i * 10, 'published': True,
           'title': title_i18n(p['title']), 'subtitle': I(sub_en), 'light': KIND[p['kind']],
           'size': size, 'depth': depth, 'specs': specs, 'note': {},
           'cover': mid(p['cover']) if p['cover'] else None, 'video': p['video'] or '',
           'gallery': prof_gallery(slug, p['cover']), 'seo': {'description': {}}, 'updated': NOW}
    jw(f'profiles/{slug}.json', obj)

# ---------------- stories
SHORT = {'tanqueray-gin-sign-on-green-wall-base': 'Tanqueray · LED neon', 'ciroc-vodka-neon-sign-on-wavy-mirror-base': 'Cîroc · LED neon',
         'nature-shop-wood-textured-sign': 'NATURE · Daugavpils', 'revolutionizing-circus-style-box-letters-with-custom-mini-bulbs': 'Box letters · mini bulbs',
         'from-expo-spark-to-neon-brilliance-illuminating-swedens-centrumbiografen': 'Centrumbiografen · Sweden',
         'seamless-signage-transforming-depo-stores-across-the-baltics': 'DEPO · 15 malls, Baltics',
         'bringing-spaces-brand-to-life-on-a-challenging-urban-landscape': 'Spaces · Denmark',
         'zieglers-daugavpils-transformation-a-blend-of-light-water-and-innovation': 'Ziegler · Daugavpils'}
STORIES = list(SHORT)
FIX = [(r'Fabrie\s?k\b', 'Fabriek'), (r'  +', ' ')]
def fix(t):
    for a, b in FIX: t = re.sub(a, b, t)
    return t.strip()
pb = C['projects']['blocks']; SC_ = {}; pend = None
for b in pb:
    if b[0] == 'img': pend = b[1]
    elif b[0] == 'h3' and pend: SC_[fix(html.unescape(b[1]))] = pend; pend = None
for i, s in enumerate(STORIES):
    d = C[s]; t = fix(html.unescape(d['title']))
    paras = [fix(b[1]) for b in d['blocks'] if b[0] == 'p' and b[1] != 'Project Story']
    ims = mids([b[1] for b in d['blocks'] if b[0] == 'img'])
    vid = [b[1] for b in d['blocks'] if b[0] == 'yt']
    cover = mid(SC_.get(t, '')) or (ims[0] if ims else None)
    obj = {'slug': s, 'order': i * 10, 'published': True, 'date': d['date'][:10], 'title': I(t), 'kicker': I(SHORT[s]),
           'body': {'en': '\n\n'.join(paras)}, 'cover': cover, 'video': vid[0] if vid else '',
           'gallery': [x for x in ims if x != cover], 'seo': {'description': {}}, 'updated': NOW}
    jw(f'stories/{s}.json', obj)

# ---------------- pages
hb = C['home']['blocks']; hb_imgs = [b[1] for b in hb if b[0] == 'img']
start = next(i for i, u in enumerate(hb_imgs) if '67078905' in u)
SCALE = mids([u for u in hb_imgs if re.match(r'^(\d+[a-d]?|2-1)\.png$', base(u))])
TEAM = mids([u for u in hb_imgs if re.match(r'^Asset-\d+(-1)?\.webp$', base(u))])
GALLERY_PROJECTS = mids([b[1] for b in pb[pb.index(next(b for b in pb if b[0] == 'yt')) + 1:] if b[0] == 'img' and 'Clients' not in b[1]])
RES_IMGS = mids([b[1] for b in C['signage-research']['blocks'] if b[0] == 'img'])[:3]
def svc(icon, title, text, lbl, url): return {'icon': icon, 'title': I(title), 'text': I(text), 'link_label': I(lbl), 'link_url': url}
MACH = ["CNC 3D router / engraver, 2 × 6 m", "CNC 3D router / engraver, 2 × 4 m (two units)", "Laser cutting / engraving, 2 × 5 m",
        "CNC box-letter bending machines (FLT and RCT)", "Steel / aluminium bender, 2.5 m", "Laser box-letter welding machine",
        "Vacuum forming machine, 3.0 × 1.5 m", "Welding stations", "Rolling machine for metal tube profiles",
        "3D printing machines, from prototype to full-scale production"]
SEO = {
 'home': ("", "Reclame Fabriek is a B2B sign supplier in Daugavpils, Latvia, since 1993: custom 3D box letters, lightboxes and LED neon, CNC machinery and custom furniture, delivered across Europe."),
}
jw('pages/home.json', {
 'seo': {'title': {}, 'description': {'en': SEO['home'][1]}},
 'hero': {'eyebrow': I("Daugavpils, Latvia · since 1993"), 'title': I("B2B sign supplier"), 'lead': I("Your trusted partner for innovative signage since 1993. Custom signage, advanced CNC technology and creative LED neon, delivered across Europe. Where quality meets precision."),
          'image': mid('IMG_20231224_182033.jpg'), 'btn1': I("See product profiles"), 'btn2': I("Our projects")},
 'stats': {'items': [{'value': '1993', 'label': I("Making signs since")}, {'value': '6 000 m²', 'label': I("Factory in Daugavpils")},
                     {'value': '22', 'label': I("Standard sign profiles")}, {'value': 'EU + UK', 'label': I("Delivery and a UK department")}]},
 'services': {'kicker': I("Our services"), 'title': I("We provide high-quality services"), 'items': [
    svc('letters', "Custom signage", "Our core division designs, manufactures and installs custom signage: 3D box letters, LED displays and lightboxes, architectural signage. Every project gets a personal service, and our logistics reach customers across Europe and overseas.", "Explore our projects", '/projects/'),
    svc('cnc', "CNC machinery supplier · CubeCNC", "Through our partnership with BCAMCNC, a global leader in CNC technology, we supply high-performance routers and laser cutting systems for woodworking, metal fabrication and sign making, with installation, training and ongoing support.", "View the catalogue", 'https://cubecnc.eu/index.php/catalogue/'),
    svc('lab', "Signage Research Department", "Our in-house research team develops advanced products such as fully customisable, waterproof LED Neon NEW GEN tubes and special signage, powered by LED controllers we build ourselves.", "Discover more", '/signage-research/'),
    svc('chair', "Custom furniture · CubeMöbel", "CubeMöbel makes high-quality custom furniture for offices, hotels, restaurants and other HORECA spaces: tailor-made designs in premium materials that carry your brand into the room.", "Ask about furniture", '/contact/#quote')]},
 'clients': {'kicker': I("Why choose us"), 'title': I("Join the brands who trust Reclame Fabriek to bring their vision to life."),
             'text': I("We are the trusted signage partner for leading brands across many industries. From multinational retail groups to innovative tech companies, our clients rely on us for custom, high-quality signage that makes their brand visible and draws customers in. On every project we aim to exceed expectations and build a lasting relationship through precision, creativity and service."),
             'link_label': I("See our largest partnerships"), 'image': 'clients-2'},
 'profiles': {'kicker': I("Our product profiles"), 'title': I("Explore our basic sign types"),
              'letters': ['profile-7-lux', 'profile-3', 'profile-2', 'profile-8', 'profile-4', 'profile-0'], 'lightboxes': ['casette-type', 'pylonstotems', 'special']},
 'special': {'kicker': I("Our special projects"), 'title': I("Your ideas, our development: challenges, expertise, passion."),
             'text': I("Whether it’s a bold new sign, an architectural element or a large branding roll-out, we take on the challenge with creative solutions and work with you at every step. Let’s create something extraordinary together."),
             'stories': STORIES[:6], 'button': I("All projects and stories")},
 'factory': {'kicker': I("We’re signage"), 'title': I("We have the capability to handle projects of all scales."),
             'text': I("Founded in 1993, Reclame Fabriek works from a modern 6,000 m² production facility in Daugavpils, Latvia. Our machine park lets us deliver precision, quality and new ideas across the whole range of custom signage and products."),
             'machines_title': I("Our machine park"), 'machines': [{'text': I(m)} for m in MACH], 'gallery': SCALE[:8]},
 'team': {'kicker': I("Our team"), 'title': I("A strong team, ready for every request"), 'gallery': TEAM},
 'subscribe': {'kicker': I("Subscribe to stay in touch"), 'title': I("Stay ahead with Reclame Fabriek"), 'text': I("New profiles, finished projects and what our research team is working on. A few emails a year, no spam.")},
 'cta': {'title': I("Have a sign in mind?"), 'text': I("Send us a sketch, a photo of the facade or just the size and the address. We reply with a solution and a price.")},
})
jw('pages/product-profiles.json', {
 'seo': {'title': {}, 'description': {'en': 'All Reclame Fabriek sign profiles: aluminium and acrylic box letters (frontlit, corona, neon look, flat) and lightboxes, with sizes, depths and materials.'}},
 'head': {'title': I("Explore our range of 3D box letters, lightboxes, LED signs and pylons"), 'lead': I("Each product is designed with modern technology and premium materials for durability, function and a lasting impression in any setting. From bold exterior signage to sleek interior displays, we bring your brand to life.")},
 'common': {'kicker': I("Same on every profile"), 'title': I("Built the same careful way"), 'rows': [
     {'label': I("Surface finish"), 'text': I("Tikkurila industrial two-component spray paint. Any RAL or NCS colour, or special coatings.")},
     {'label': I("LED components"), 'text': I("LED modules with a 5-year warranty; LED power supplies with a 3-year warranty.")},
     {'label': I("Mounting"), 'text': I("**No frame:** supplied with a paper template for accurate installation. **On frame:** letters pre-installed on rails behind the letters.")},
     {'label': I("Customisation"), 'text': I("Other materials, components and finishes on request, subject to price and availability. Construction can be adapted to your design or use, agreed with you.")}]},
 'cta': {'title': I("Not sure which profile fits?"), 'text': I("Tell us where the sign goes and how it should light. We’ll recommend the profile and send a price.")},
})
jw('pages/projects.json', {
 'seo': {'title': {}, 'description': {'en': 'Signage projects by Reclame Fabriek: LED neon for Tanqueray and Cîroc, DEPO stores across the Baltics, Spaces in Denmark, Centrumbiografen in Sweden, Ziegler in Daugavpils and more.'}},
 'head': {'kicker': I("Projects"), 'title': I("Your ideas, our development: challenges, expertise, passion."), 'lead': I("Whether it’s a bold new sign, an architectural element or a large branding roll-out, we take on the challenge with creative solutions and work with you at every step. Let’s create something extraordinary together.")},
 'stories': {'kicker': I("Project stories"), 'title': I("How we solved them")},
 'film': {'kicker': I("On film"), 'title': I("Inside the factory"), 'text': I("A short look at how our signs are made in Daugavpils."), 'video': 'S6XYwUqeADg', 'poster': mid('Asset-1-1.webp')},
 'gallery': {'kicker': I("Gallery"), 'title': I("Signage for retail, food and leisure brands"), 'images': GALLERY_PROJECTS},
 'partners': {'kicker': I("Our largest partnerships"), 'title': I("Trusted by recognised brands across Europe"), 'text': I("We are proud to work with some of the best-known brands and organisations in Europe. Our focus on quality, precision and new ideas has earned the trust of leaders in retail, hospitality, entertainment and corporate sectors. These partnerships show that we deliver high-impact signage that makes brands visible and improves the customer experience."), 'image': 'clients-2'},
 'cta': {'title': I("Planning something similar?"), 'text': I("Tell us about the place, the brand and the date. We’ll come back with a solution and a price.")},
})
jw('pages/signage-research.json', {
 'seo': {'title': {}, 'description': {'en': 'The Reclame Fabriek Signage Research Department: full-scale 3D printing, decorative coatings and material imitation, next-gen LED neon tubes and in-house LED controllers.'}},
 'head': {'kicker': I("Signage Research Department"), 'title': I("Shaping the future of custom signage"), 'lead': I("We’re not just making signs. We’re shaping the future of visual communication, one innovation at a time.")},
 'intro': {'title': I("A new era of custom signage"), 'body': I(next(k for k in T if k.startswith("Over the past five years"))), 'gallery': RES_IMGS},
 'materials': {'kicker': I("From idea to reality"), 'title': I("Mastering material design"), 'text': I("Our material imitation lets us reproduce the grain of wood, the sheen of polished metal or the texture of stone, in signs that look authentic and stand up to the weather. For corporate, retail and high-end hospitality spaces that need the look of premium materials without the cost or upkeep."),
               'items': [{'title': I(a), 'text': I(b)} for a, b in [
                   ("3D printing for full-scale production", "Our 3D printing started with prototypes and now runs full-scale production of highly detailed, complex parts: intricate logos, lettering and decorative elements for interior and exterior signs."),
                   ("Custom painting and decorative coatings", "Any finish: matte, gloss or textured, with protective, weather-resistant coatings in custom colours. We also imitate wood, metal and stone, so you get the look without the cost or the weight."),
                   ("Next-gen LED neon tubes", "The glow and flexibility of classic neon, with more durability, lower energy use and more design freedom. Ideal for intricate designs and long-lasting installations."),
                   ("CUBE LED DIGI controllers", "Developed in-house. Full control over LED lighting: colour changes, fades and animations, so a sign can adapt to its place and its audience in real time.")]]},
 'lumigrid': {'kicker': I("Made in-house"), 'title': I("LUMIGRID controller"), 'text': I("Our own LED controller for animated, addressable light: the next step after CUBE LED DIGI. See what it can do on its own page."),
              'button': I("Open the LUMIGRID page"), 'url': 'https://algorhythmicdev.github.io/lumigrid-lander/',
              'title2': I("Leading the future of signage"), 'text2': I("We combine 3D printing, decorative coatings, custom painting and our own LED technology to set new standards for design, durability and interaction, and we keep exploring new materials, fabrication techniques and custom solutions.")},
 'cta': {'title': I("Have a sign nobody else can make?"), 'text': I("That’s the kind of brief our research team is for. Tell us the idea; we’ll tell you how we’d build it.")},
})
jw('pages/contact.json', {
 'seo': {'title': {}, 'description': {'en': 'Contact Reclame Fabriek: Komunala iela 100B, Daugavpils, Latvia. General +371 29 433 637, factory +371 26 389 364, UK department +44 7447 048948.'}},
 'head': {'title': I("We’re here to answer any questions you may have"), 'lead': I("Tell us about your project and we’ll be glad to help. Fill out the form, or email or call the right person directly.")},
})
jw('pages/privacy-policy.json', {
 'seo': {'title': {}, 'description': {'en': 'How Reclame Fabriek handles the details you send through this website.'}},
 'head': {'title': UI['privacy']},
 'body': {'text': I(next(k for k in T if k.startswith("**Who we are.**")))},
})
# ---------------- settings + ui
jw('settings.json', {
 'site_url': 'https://reclamefabriek.eu', 'company': 'Reclame Fabriek SIA', 'founded': '1993',
 'languages': [{'code': c, 'enabled': True} for c in LANGS], 'default_lang': 'en',
 'form_to': 'pa@reclamefabriek.eu', 'form_from': 'website@reclamefabriek.eu',
 'address': I("Komunala iela 100B\nLV-5449 Daugavpils\nLatvia, EU"),
 'maps_query': 'Komunala iela 100B, LV-5449 Daugavpils',
 'contacts': [{'role': I("General"), 'name': 'Petr Abramov', 'phone': '+371 29 433 637', 'email': 'pa@reclamefabriek.eu'},
              {'role': I("Factory"), 'name': 'Vitaly Danilevics', 'phone': '+371 26 389 364', 'email': 'factory@reclamefabriek.eu'},
              {'role': I("UK department"), 'name': 'Nikita Nikolaevs', 'phone': '+44 7447 048948', 'email': 'uk@reclamefabriek.eu'}],
 'social': [{'name': 'YouTube', 'url': 'https://www.youtube.com/@reclamefabriekLatvia'}, {'name': 'Instagram', 'url': 'https://www.instagram.com/reclame_fabriek/'},
            {'name': 'Facebook', 'url': 'https://www.facebook.com/RA.REKLAMAS.FABRIKA'}, {'name': 'LinkedIn', 'url': 'https://www.linkedin.com/company/reclame-fabriek'}],
 'lumigrid_url': 'https://algorhythmicdev.github.io/lumigrid-lander/',
 'partner_logos': mids(['LIAA_logo_ansamblis-1.png', '20231220-1208-bilde3.png', 'AN.png']),
 'og_image': '/assets/brand/og-image.jpg', 'hero_phone': '+371 29 433 637',
})
jw('ui.json', UI)
jw('redirects.json', {})
print('media', len(media), 'missing translations:', len(missing))
for m in sorted(missing): print('  MISSING:', m[:90])
