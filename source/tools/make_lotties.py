# Reclame Fabriek · Lottie set v1.0 ("Switch-on"). Shape layers only → plays in lottie_light (web) and Lottie Android/iOS.
# Motion rule: things don't bounce, they SWITCH ON — trace, flicker (hold keyframes), settle, then a slow hum.
import json, os, math, random
from lottie_lib import st, kf, tr, ellipse, rect, path, stroke, fill, trim, group, layer, comp, hexc, FPS
OUT = '/tmp/rfmotion/lottie'; os.makedirs(OUT, exist_ok=True)
G, P, LU, RED, STEEL, ALU = '#1C1D20', '#F3F2EF', '#FFB547', '#C8161D', '#45474D', '#9AA0A6'

def hold(frames):
    """step keyframes (instant changes) — the neon flicker"""
    return {"a": 1, "k": [{"t": t, "s": [v], "h": 1} for t, v in frames[:-1]] + [{"t": frames[-1][0], "s": [frames[-1][1]]}]}
def lin(frames):
    out = []
    for n, (t, v) in enumerate(frames):
        v = v if isinstance(v, list) else [v]; k = {"t": t, "s": v}
        if n < len(frames) - 1: d = len(v); k["o"] = {"x": [0.0] * d if d > 1 else [0.0], "y": [0.0] * d if d > 1 else [0.0]}; k["i"] = {"x": [1.0] * d if d > 1 else [1.0], "y": [1.0] * d if d > 1 else [1.0]}
        out.append(k)
    return {"a": 1, "k": out}
def poly(pts, closed=True): return path(pts, closed)
def scale_pts(pts, s, cx, cy): return [(cx + (x - cx) * s, cy + (y - cy) * s) for x, y in pts]
def flicker(on_at, val=100, off=0):
    # off → quick stutter → on; the shape a real tube makes when it strikes
    a = on_at
    return [(0, off), (a, val * .9), (a + 2, off), (a + 4, val * .6), (a + 5, off + 6), (a + 9, val), (a + 11, val * .55), (a + 13, val)]
def glow(shape, color, base_w, strength=1.0, o=None):
    """fake bloom: stacked wide, faint strokes (lottie_light has no blur)"""
    items = []
    for w, op in [(base_w * 7, 7), (base_w * 4.5, 12), (base_w * 2.6, 22)]:
        items.append(group('glow', [shape, stroke(color, w, op * strength)]))
    return items

# ---- the letter: chamfered box-letter "F" (same as the box-letter icon), 200×200 artboard
F = [(68, 40), (140, 40), (140, 70), (92, 70), (92, 92), (128, 92), (128, 120), (92, 120), (92, 152), (84, 160), (60, 160), (60, 48)]
NEON = [(74, 54), (126, 54), (126, 56), (78, 56)]  # unused helper
N_PATH = [(76, 147), (76, 56), (126, 56)]          # neon tube route inside the F (stem + top bar)
N_MID = [(76, 106), (114, 106)]                     # middle bar
OP = 150  # 5 s at 30 fps: 0–40 strike, 40–150 hum (the web player loops 60–150)
hum = lambda lo, hi: kf([(40, hi), (75, lo), (110, hi), (150, lo)], 'breath')

def wall(dark=True):
    return layer('wall', [group('bg', [rect(200, 200, 0), fill(G if dark else P)])], 99, OP, p=(100, 100))

def light(kind):
    L = []
    face_line = ALU
    shp = poly(F)
    if kind in ('corona', 'front_corona'):
        halo = poly(scale_pts(F, 1.0, 100, 100))
        L.append(layer('halo', glow(halo, LU, 6, 1.6), 10, OP, p=(0, 0), o=hold([(0, 0), (12, 80), (14, 0), (17, 60), (18, 0), (22, 100), (24, 70), (26, 100)])))
        L.append(layer('halo-hum', [group('h', [halo, stroke(LU, 12, 30)])], 11, OP, p=(0, 0), o=hum(60, 100)))
    if kind in ('frontlit', 'front_corona', 'lightbox'):
        L.append(layer('face-glow', glow(shp, LU, 3, 1.2), 5, OP, p=(0, 0), o=hold(flicker(14))))
    # letter body
    face_fill = {'frontlit': LU, 'front_corona': LU, 'lightbox': P}.get(kind)
    body = [shp]
    if face_fill:
        body.append(fill(face_fill, hold([(0, 0)] + flicker(14)[1:])))
    body.append(stroke(face_line if not face_fill else '#E8A03A', 3))
    L.insert(0, layer('letter', [group('F', [poly(F), fill(STEEL if kind in ('corona',) else '#2A2B2F')]), group('Fl', body)], 2, OP, p=(0, 0)))
    if kind == 'neon':
        tube = [path(N_PATH, False), path(N_MID, False)]
        L.insert(0, layer('tube', [group('t', tube + [trim(0, kf([(4, 0), (30, 100)], 'settle')), stroke(LU, 7)])], 1, OP, p=(0, 0), o=hold([(0, 100), (31, 40), (33, 100), (35, 60), (37, 100)])))
        L.insert(1, layer('tube-glow', [group('g', tube + [trim(0, kf([(4, 0), (30, 100)], 'settle')), stroke(LU, 26, 14)]), group('g2', tube + [trim(0, kf([(4, 0), (30, 100)], 'settle')), stroke(LU, 15, 22)])], 3, OP, p=(0, 0), o=hum(55, 100)))
    if kind == 'side':
        # returns glow: an offset outline (the sides) that lights, face stays dark
        side = poly(scale_pts(F, 1.045, 100, 100))
        L.append(layer('sides', glow(side, LU, 3, 1.3) + [group('s', [side, stroke(LU, 4)])], 8, OP, p=(0, 0), o=hold(flicker(14))))
    if kind == 'unlit':
        # no light: a slow daylight sweep across the face instead
        sweep = group('sweep', [rect(26, 260, 0), fill(P, 18)], tr(p=kf([(10, [-40, 100]), (70, [240, 100])], 'settle'), r=20))
        L.insert(0, layer('sweep', [sweep], 1, OP, p=(0, 0)))
    if kind == 'flat':
        plate = poly([(x + 4, y + 4) for x, y in F])
        L.append(layer('shadow', [group('sh', [plate, fill('#000000', 45)])], 9, OP, p=(0, 0), o=kf([(0, 0), (30, 100)], 'settle')))
    if kind == 'lightbox':
        box = rect(150, 110, 6)
        L = [layer('box-glow', [group('bg', [box, stroke(LU, 40, 7)], tr(p=(100, 100))), group('bg2', [box, stroke(LU, 18, 16)], tr(p=(100, 100)))], 5, OP, p=(0, 0), o=hold(flicker(14))),
             layer('box', [group('lines', [path([(60, 88), (128, 88)], False), path([(60, 112), (110, 112)], False), stroke(G, 7)]),
                           group('face', [rect(150, 110, 6), fill(P, hold([(0, 8)] + flicker(14, 100, 8)[1:])), stroke(ALU, 3)], tr(p=(100, 100)))], 2, OP, p=(0, 0))]
    L.append(wall())
    for i, l in enumerate(L): l['ind'] = i + 1
    return comp(f'rf_light_{kind}', 200, 200, OP, L, markers=[('strike', 0, 40), ('hum', 60, 90)])

# ---- services (paper background, graphite line, one Lumen moment), 160×160
def svc_letters():
    op = 120; f = poly(scale_pts(F, .72, 100, 100)); f = poly([(x - 20, y - 20) for x, y in scale_pts(F, .72, 100, 100)])
    return comp('rf_svc_letters', 160, 160, op, [
        layer('glow', glow(f, LU, 2.4, 1.2), 1, op, p=(0, 0), o=hold([(0, 0), (46, 90), (48, 0), (51, 70), (53, 100), (100, 100), (104, 0)])),
        layer('face', [group('f', [f, fill(LU, hold([(0, 0), (46, 100), (48, 0), (51, 80), (53, 100), (100, 100), (104, 0)])), trim(0, kf([(4, 0), (40, 100)], 'settle')), stroke(G, 4)])], 2, op, p=(0, 0))])

TOOL = [(52, 112), (52, 48), (108, 48), (108, 66), (72, 66), (72, 80), (100, 80), (100, 96), (72, 96), (72, 112), (52, 112)]
def svc_cnc():
    op = 150; seg = [math.dist(TOOL[i], TOOL[i + 1]) for i in range(len(TOOL) - 1)]; tot = sum(seg)
    t0, t1 = 10, 120; frames = []; acc = 0
    for i, p in enumerate(TOOL):
        frames.append((round(t0 + (t1 - t0) * acc / tot), [p[0], p[1]])); acc += seg[i] if i < len(seg) else 0
    return comp('rf_svc_cnc', 160, 160, op, [
        layer('spindle', [group('s', [rect(14, 22, 2), fill(G)], tr(p=(0, -13))), group('bit', [path([(0, -2), (0, 6)], False), stroke(G, 3)]), group('spark', [ellipse(7), fill(LU, hold([(0, 0), (10, 100), (120, 0)]))])], 1, op, p=lin(frames)),
        layer('gantry', [group('rail', [path([(18, 30), (142, 30)], False), stroke(G, 3)])], 2, op, p=(0, 0)),
        layer('gantry-arm', [group('arm', [path([(0, 30), (0, 0)], False), stroke(ALU, 2)])], 3, op, p=lin([(t, [v[0], v[1] - 30 + 30]) for t, v in frames]) if False else (0, 0), o=0),
        layer('cut', [group('c', [path(TOOL, False), trim(0, lin([(t0, 0), (t1, 100)])), stroke(G, 4)])], 4, op, p=(0, 0)),
        layer('cut-lit', [group('cl', [path(TOOL, False), stroke(LU, 4)])], 5, op, p=(0, 0), o=hold([(0, 0), (122, 100), (124, 0), (126, 100), (146, 100), (150, 0)])),
        layer('bed', [group('b', [path([(18, 132), (142, 132)], False), stroke(G, 3)])], 6, op, p=(0, 0))])

def svc_lab():
    op = 120; flask = [(66, 30), (66, 64), (40, 116), (46, 128), (114, 128), (120, 116), (94, 64), (94, 30)]
    bubbles = []
    for i, (x, d, t) in enumerate([(70, 8, 10), (86, 6, 30), (78, 10, 55), (92, 7, 75)]):
        bubbles.append(layer(f'b{i}', [group('b', [ellipse(d), fill(LU), stroke(G, 2)])], 10 + i, op,
                              p=kf([(t, [x, 120]), (t + 40, [x, 74])], 'settle'), o=kf([(t, 0), (t + 6, 100), (t + 34, 100), (t + 42, 0)], 'standard')))
    return comp('rf_svc_lab', 160, 160, op, bubbles + [
        layer('liquid', [group('l', [path([(52, 104), (108, 104)], False), stroke(G, 3)])], 2, op, p=(0, 0)),
        layer('flask', [group('f', [path(flask, False), stroke(G, 4)]), group('rim', [path([(58, 30), (102, 30)], False), stroke(G, 4)])], 3, op, p=(0, 0)),
        layer('led', [group('led', [rect(10, 10, 0), fill(LU)], tr(p=(130, 40)))], 4, op, p=(0, 0), o=hold([(0, 0), (20, 100), (40, 0), (60, 100), (80, 0), (100, 100)]))])

def svc_chair():
    op = 120
    return comp('rf_svc_chair', 160, 160, op, [
        layer('seat', [group('s', [rect(84, 16, 0), fill(P), stroke(G, 4)], tr(p=(80, 88)))], 1, op, p=kf([(0, [0, -60]), (22, [0, 0])], 'settle'), o=kf([(0, 0), (10, 100)])),
        layer('legs', [group('l', [path([(46, 96), (46, 136)], False), path([(114, 96), (114, 136)], False), trim(0, kf([(20, 0), (40, 100)], 'settle')), stroke(G, 4)])], 2, op, p=(0, 0)),
        layer('back', [group('b', [path([(46, 80), (46, 28)], False), path([(114, 80), (114, 28)], False), path([(46, 28), (114, 28)], False), trim(0, kf([(36, 0), (62, 100)], 'settle')), stroke(G, 4)])], 3, op, p=(0, 0)),
        layer('pixel', [group('p', [rect(12, 12, 0), fill(LU)], tr(p=(80, 54)))], 4, op, p=(0, 0), o=hold([(0, 0), (64, 100), (66, 0), (68, 100), (110, 100), (116, 0)]))])

def lumigrid():
    # 8×5 addressable pixels, a wave runs across; on graphite
    op = 120; L = []; i = 1
    for r in range(5):
        for c in range(8):
            x, y = 30 + c * 20, 40 + r * 20; t = (c * 6 + r * 3) % 60
            L.append(layer(f'p{r}{c}', [group('p', [rect(12, 12, 1), fill(LU)])], i, op, p=(x, y), o=kf([(t, 12), (t + 8, 100), (t + 30, 12), (t + 60, 12)], 'breath'))); i += 1
            L.append(layer(f'q{r}{c}', [group('q', [rect(12, 12, 1), fill('#2A2B2F')])], i + 400, op, p=(x, y))); i += 1
    L.sort(key=lambda l: l['nm'].startswith('q'))
    L.append(layer('bg', [group('bg', [rect(200, 160, 0), fill(G)])], 999, op, p=(100, 80)))
    for n, l in enumerate(L): l['ind'] = n + 1
    return comp('rf_lumigrid', 200, 160, op, L)

def success():
    op = 60; box = rect(76, 76, 0); chk = path([(42, 62), (54, 74), (80, 46)], False)
    return comp('rf_success', 120, 120, op, [
        layer('glow', [group('g', [box, stroke(LU, 24, 14)], tr(p=(60, 60)))], 1, op, p=(0, 0), o=hold([(0, 0), (28, 100), (30, 30), (32, 100)])),
        layer('check', [group('c', [chk, trim(0, kf([(14, 0), (30, 100)], 'settle')), stroke(G, 7)])], 2, op, p=(0, 0)),
        layer('box', [group('b', [box, fill(LU, hold([(0, 0), (28, 100), (30, 40), (32, 100)])), trim(0, kf([(0, 0), (16, 100)], 'settle')), stroke(G, 4)], tr(p=(60, 60)))], 3, op, p=(0, 0))])

def switched_off():
    # 404: the letter is on, sputters, dies, then one last tiny blink
    op = 150; shp = poly(F)
    o = hold([(0, 100), (40, 100), (42, 20), (44, 100), (50, 0), (53, 70), (55, 0), (80, 0), (82, 30), (83, 0), (150, 0)])
    return comp('rf_switched_off', 200, 200, op, [
        layer('glow', glow(shp, LU, 3, 1.2), 1, op, p=(0, 0), o=o),
        layer('face', [group('f', [shp, fill(LU, o)])], 2, op, p=(0, 0)),
        layer('letter', [group('F', [poly(F), fill('#2A2B2F'), stroke(ALU, 3)])], 3, op, p=(0, 0)),
        wall()])

ALL = {f'rf_light_{k}': light(k) for k in ['frontlit', 'corona', 'neon', 'side', 'front_corona', 'unlit', 'flat', 'lightbox']}
ALL.update({'rf_svc_letters': svc_letters(), 'rf_svc_cnc': svc_cnc(), 'rf_svc_lab': svc_lab(), 'rf_svc_chair': svc_chair(),
            'rf_lumigrid': lumigrid(), 'rf_success': success(), 'rf_switched_off': switched_off()})
for n, c in ALL.items():
    # drop helper layers with o=0 static
    c['layers'] = [l for l in c['layers'] if not (l['ks']['o'].get('a') == 0 and l['ks']['o'].get('k') == 0)]
    json.dump(c, open(f'{OUT}/{n}.json', 'w'), separators=(',', ':'))
print({n: os.path.getsize(f'{OUT}/{n}.json') for n in ALL})
