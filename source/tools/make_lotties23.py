# Reclame Fabriek · Lottie set 2.3 ("From drawing to switch-on"). Same rules as v1.0: shape layers only (lottie_light + mobile safe),
# graphite line on transparent + ONE Lumen moment; things don't bounce, they switch on (trace → flicker → settle → slow hum).
import json, os, math, sys
sys.path.insert(0, os.path.dirname(__file__))
from lottie_lib import st, kf, tr, ellipse, rect, path, stroke, fill, trim, group, layer, comp, FPS
OUT = sys.argv[1] if len(sys.argv) > 1 else '/tmp/rfcms/site/assets/lottie'
G, P, LU, STEEL, ALU, RED = '#1C1D20', '#F3F2EF', '#FFB547', '#45474D', '#9AA0A6', '#C8161D'

def hold(frames):
    return {"a": 1, "k": [{"t": t, "s": [v], "h": 1} for t, v in frames[:-1]] + [{"t": frames[-1][0], "s": [frames[-1][1]]}]}
def lin(frames):
    out = []
    for n, (t, v) in enumerate(frames):
        v = v if isinstance(v, list) else [v]; k = {"t": t, "s": v}
        if n < len(frames) - 1: dd = len(v); k["o"] = {"x": [0.0] * dd, "y": [0.0] * dd}; k["i"] = {"x": [1.0] * dd, "y": [1.0] * dd}
        out.append(k)
    return {"a": 1, "k": out}
def poly(pts, closed=True): return path(pts, closed)
def flick(a, val=100, off=0):
    return hold([(0, off), (a, val * .9), (a + 2, off), (a + 4, val * .6), (a + 5, off), (a + 9, val), (a + 11, val * .55), (a + 13, val)])
def glow(shape, color, w, k=1.0):
    return [group('glow', [shape, stroke(color, w * m, op * k)]) for m, op in [(7, 7), (4.5, 12), (2.6, 22)]]
def path_frames(pts, t0, t1):
    seg = [math.dist(pts[i], pts[i + 1]) for i in range(len(pts) - 1)]; tot = sum(seg); acc = 0; fr = []
    for i, p in enumerate(pts):
        fr.append((round(t0 + (t1 - t0) * acc / tot), [p[0], p[1]])); acc += seg[i] if i < len(seg) else 0
    return fr
def finish(c):
    for i, l in enumerate(c['layers']): l['ind'] = i + 1
    return c

# the factory "F" box letter, scaled into a 160 artboard
F = [(68, 40), (140, 40), (140, 70), (92, 70), (92, 92), (128, 92), (128, 120), (92, 120), (92, 152), (84, 160), (60, 160), (60, 48)]
def fit(pts, s, dx, dy): return [(round(x * s + dx, 1), round(y * s + dy, 1)) for x, y in pts]
F16 = fit(F, .72, -6, -8)

# 1 · DRAWING: a pen traces the outline, vector nodes light up one by one, then a proof stamp
def step_design():
    op = 150; nodes = []
    for i, (x, y) in enumerate(F16):
        t = 8 + i * 6
        nodes.append(layer(f'n{i}', [group('n', [rect(7, 7, 0), fill(P), stroke(G, 2)])], 10 + i, op, p=(x, y), o=hold([(0, 0), (t, 100)])))
    pen = path_frames(F16 + [F16[0]], 8, 80)
    return finish(comp('rf_step_design', 160, 160, op, [
        layer('pen', [group('p', [poly([(0, 0), (-5, -14), (-2, -26), (2, -26), (5, -14)]), fill(G)]), group('tip', [ellipse(4), fill(LU, hold([(0, 0), (8, 100), (80, 0)]))])], 1, op, p=lin(pen), o=hold([(0, 100), (84, 0)])),
        *nodes,
        layer('outline', [group('o', [poly(F16), trim(0, lin([(8, 0), (80, 100)])), stroke(G, 3)])], 40, op, p=(0, 0)),
        layer('ok', [group('c', [path([(112, 128), (120, 136), (138, 116)], False), trim(0, kf([(96, 0), (112, 100)], 'settle')), stroke(G, 4)]),
                     group('box', [rect(40, 40, 3), fill(LU, hold([(0, 0), (110, 100), (112, 30), (114, 100)])), stroke(G, 3)], tr(p=(125, 126)))], 2, op, p=(0, 0), o=hold([(0, 0), (94, 100)])),
    ]))

# 2 · BENDING: an aluminium strip feeds through the bender head and folds at each corner (a spark per fold)
def step_bend():
    op = 150; pts = F16 + [F16[0]]; fr = path_frames(pts, 10, 112)
    sparks = []
    for i, (t, v) in enumerate(fr[1:-1]):
        sparks.append(layer(f's{i}', [group('s', [rect(5, 5, 0), fill(LU)])], 20 + i, op, p=(v[0], v[1]), o=hold([(0, 0), (t, 100), (t + 3, 0)])))
    return finish(comp('rf_step_bend', 160, 160, op, [
        layer('head', [group('h', [rect(16, 16, 2), fill(G)]), group('roll', [ellipse(6), fill(P)])], 1, op, p=lin(fr), o=hold([(0, 100), (114, 0)])),
        *sparks,
        layer('strip', [group('st', [poly(pts, False), trim(0, lin([(10, 0), (112, 100)])), stroke(G, 5)])], 50, op, p=(0, 0)),
        layer('strip-lit', [group('sl', [poly(F16), stroke(LU, 5)])] + glow(poly(F16), LU, 1.6, .9), 51, op, p=(0, 0), o=hold([(0, 0), (118, 100), (120, 0), (122, 100), (146, 100), (150, 0)])),
        layer('feed', [group('f', [path([(4, 150), (40, 150)], False), stroke(ALU, 4)])], 60, op, p=(0, 0), o=hold([(0, 100), (112, 0)])),
    ]))

# 3 · PAINT: a nozzle passes; the face fills in RAL colour stripe by stripe, a fresh-paint sheen crosses
def step_paint():
    op = 150; face = poly(F16)
    rows = []
    for i in range(6):
        y = 34 + i * 18; t = 14 + i * 14
        rows.append(layer(f'r{i}', [group('r', [rect(0, 16, 0) if False else rect(96, 16, 0), fill(STEEL)], tr(p=(92, y)))], 20 + i, op, p=(0, 0), o=hold([(0, 0), (t, 100)])))
    noz = []
    for i in range(6): noz += [(14 + i * 14, [30 if i % 2 == 0 else 140, 30 + i * 18]), (14 + i * 14 + 12, [140 if i % 2 == 0 else 30, 30 + i * 18])]
    return finish(comp('rf_step_paint', 160, 160, op, [
        layer('nozzle', [group('n', [rect(12, 18, 2), fill(G)], tr(p=(0, -14))), group('mist', [poly([(-10, 10), (10, 10), (4, -4), (-4, -4)]), fill(ALU, 60)])], 1, op, p=lin(noz), o=hold([(0, 100), (100, 0)])),
        layer('outline', [group('o', [face, stroke(G, 3)])], 2, op, p=(0, 0)),
        layer('coat', [group('m', [face, fill(STEEL)])], 4, op, p=(0, 0), o=hold([(0, 0), (26, 17), (40, 33), (54, 50), (68, 67), (82, 83), (96, 100)])),
        layer('ral', [group('chip', [rect(26, 14, 2), fill(LU, hold([(0, 0), (106, 100), (108, 30), (110, 100)])), stroke(G, 2)], tr(p=(132, 140)))], 5, op, p=(0, 0)),
    ]))

# 4 · LIGHT: six LED modules on a wire light up one after another, then all hum together
def step_led():
    op = 150; L = []
    xs = [30, 50, 70, 90, 110, 130]
    for i, x in enumerate(xs):
        t = 22 + i * 9
        L.append(layer(f'g{i}', [group('g', [rect(12, 8, 1), stroke(LU, 18, 14)]), group('g2', [rect(12, 8, 1), stroke(LU, 10, 24)])], 10 + i, op, p=(x, 80), o=flick(t)))
        L.append(layer(f'm{i}', [group('led', [rect(5, 5, 0), fill(LU, flick(t))]), group('m', [rect(16, 10, 1), fill(G)])], 20 + i, op, p=(x, 80)))
    L.append(layer('wire', [group('w', [path([(14, 80), (146, 80)], False), trim(0, kf([(2, 0), (20, 100)], 'settle')), stroke(G, 2.5)])], 40, op, p=(0, 0)))
    L.append(layer('psu', [group('p', [rect(30, 20, 2), stroke(G, 3)], tr(p=(80, 124))), group('pl', [path([(80, 114), (80, 80)], False), stroke(G, 2.5)]), group('pled', [rect(4, 4, 0), fill(LU, hold([(0, 0), (16, 100)]))], tr(p=(90, 124)))], 41, op, p=(0, 0)))
    L.append(layer('hum', [group('h', [path([(22, 80), (138, 80)], False), stroke(LU, 26, 10)])], 5, op, p=(0, 0), o=kf([(80, 0), (95, 100), (120, 55), (150, 100)], 'breath')))
    return finish(comp('rf_step_led', 160, 160, op, L))

# 5 · MOUNTING: letters on a rail are lowered onto the facade, fixed, then switched on
def step_install():
    op = 150; f = fit(F, .46, 28, 2)
    return finish(comp('rf_step_install', 160, 160, op, [
        layer('glow', glow(poly(f), LU, 1.6, 1.1), 1, op, p=(0, 0), o=flick(104)),
        layer('letter', [group('f', [poly(f), fill(LU, flick(104)), stroke(G, 3)]), group('rail', [path([(22, 78), (98, 78)], False), stroke(G, 3)]),
                         group('cable', [path([(60, -40), (60, 22)], False), stroke(ALU, 2)]), group('hook', [path([(56, 22), (64, 22)], False), stroke(G, 3)])], 2, op,
              p=kf([(0, [20, -70]), (52, [20, 30])], 'settle')),
        layer('facade', [group('w', [path([(10, 112), (150, 112)], False), stroke(G, 3)]), group('w2', [path([(10, 126), (150, 126)], False), stroke(ALU, 2, 70)])], 3, op, p=(0, 0)),
        layer('bolt1', [group('b', [rect(6, 6, 0), fill(G)])], 4, op, p=(48, 108), o=hold([(0, 0), (62, 100)])),
        layer('bolt2', [group('b', [rect(6, 6, 0), fill(G)])], 5, op, p=(112, 108), o=hold([(0, 0), (72, 100)])),
    ]))

# 6 · DELIVERY: a crate on a truck bed slides out of frame toward Europe (a lit pin lands)
def step_ship():
    op = 150
    truck = [group('box', [rect(70, 40, 2), stroke(G, 3)], tr(p=(0, -6))), group('cab', [poly([(36, 14), (36, -12), (52, -12), (62, 2), (62, 14)]), stroke(G, 3)]),
             group('w1', [ellipse(12), fill(P), stroke(G, 3)], tr(p=(-20, 18))), group('w2', [ellipse(12), fill(P), stroke(G, 3)], tr(p=(46, 18))),
             group('crate', [rect(26, 20, 1), fill(LU, hold([(0, 0), (8, 100)])), stroke(G, 2)], tr(p=(-6, -4)))]
    return finish(comp('rf_step_ship', 160, 160, op, [
        layer('truck', truck, 1, op, p=kf([(0, [-60, 96]), (40, [60, 96]), (100, [60, 96]), (140, [240, 96])], 'settle')),
        layer('road', [group('r', [path([(0, 122), (160, 122)], False), stroke(G, 3, 100, dash=(10, 8))])], 2, op, p=(0, 0)),
        layer('pin', [group('pin', [poly([(0, 0), (-10, -14), (-10, -22), (-4, -28), (4, -28), (10, -22), (10, -14)]), fill(LU), stroke(G, 2.5)])], 3, op, p=kf([(100, [124, 0]), (118, [124, 50])], 'settle'), o=hold([(0, 0), (100, 100)])),
    ]))

# BIG · the whole letter assembles on dark: back → returns → LEDs → face → switch-on → hum (loop from "hum")
def build_letter():
    op = 210; f = fit(F, 1.0, 20, 10); fb = [(x + 10, y + 10) for x, y in f]
    L = []
    L.append(layer('halo', glow(poly(f), LU, 4, 1.4), 1, op, p=(0, 0), o=flick(150)))
    L.append(layer('face', [group('face', [poly(f), fill(LU, flick(150)), stroke('#E8A03A', 3)])], 2, op, p=kf([(110, [-260, 0]), (138, [0, 0])], 'settle'), o=hold([(0, 0), (110, 100)])))
    leds = [(98, 72), (98, 104), (98, 136), (126, 72), (130, 120), (82, 160)]
    for i, (x, y) in enumerate(leds):
        t = 70 + i * 5
        L.append(layer(f'led{i}', [group('l', [rect(10, 7, 1), fill(LU, hold([(0, 0), (t, 100), (t + 2, 30), (t + 4, 100)]))]), group('lg', [rect(10, 7, 1), stroke(LU, 12, 18)])], 10 + i, op, p=(x, y), o=hold([(0, 0), (t, 100), (138, 0)])))
    sides = []
    for (x0, y0), (x1, y1) in zip(f, fb): sides.append(path([(x0, y0), (x1, y1)], False))
    L.append(layer('returns', [group('ret', sides + [trim(0, kf([(40, 0), (64, 100)], 'settle')), stroke(ALU, 2.5)]), group('rb', [poly(fb), trim(0, kf([(40, 0), (64, 100)], 'settle')), stroke(ALU, 3)])], 30, op, p=(0, 0)))
    L.append(layer('back', [group('b', [poly(f), trim(0, kf([(4, 0), (38, 100)], 'settle')), stroke(P, 3)]), group('bf', [poly(f), fill('#2A2B2F', kf([(30, 0), (40, 100)]))])], 31, op, p=(0, 0)))
    L.append(layer('hum', [group('h', [poly(f), stroke(LU, 16, 22)])], 0, op, p=(0, 0), o=kf([(165, 100), (188, 45), (210, 100)], 'breath')))
    L.append(layer('wall', [group('bg', [rect(240, 200, 0), fill(G)])], 99, op, p=(120, 100)))
    c = comp('rf_build_letter', 240, 200, op, L, markers=[('build', 0, 150), ('hum', 165, 45)])
    c['layers'].sort(key=lambda l: l['ind'] if l['nm'] != 'hum' else 3)
    return finish(c)

# NEON "OPEN": four tube letters strike one by one, one stutters, then hum (contact + portal)
def neon_open():
    op = 180
    O = [(0, 10), (0, 40), (10, 50), (22, 50), (32, 40), (32, 10), (22, 0), (10, 0), (0, 10)]
    Pp = [(0, 50), (0, 0), (22, 0), (30, 8), (30, 18), (22, 26), (0, 26)]
    E = [(30, 0), (0, 0), (0, 50), (30, 50)]; E2 = [(0, 25), (22, 25)]
    N = [(0, 50), (0, 0), (30, 50), (30, 0)]
    xs = [14, 58, 100, 140]; letters = [[O], [Pp], [E, E2], [N]]
    L = []
    for i, (x, shapes) in enumerate(zip(xs, letters)):
        t = 10 + i * 16
        sh = [path(s, False) for s in shapes]
        o = flick(t) if i != 2 else hold([(0, 0), (t, 90), (t + 2, 0), (t + 4, 60), (t + 5, 0), (t + 9, 100), (t + 11, 55), (t + 13, 100), (120, 100), (122, 15), (124, 100), (126, 40), (128, 100)])
        L.append(layer(f'tube{i}', [group('t', sh + [stroke(LU, 5)])], 10 + i, op, p=(x, 45), o=o))
        L.append(layer(f'glow{i}', [group('g', sh + [stroke(LU, 22, 12)]), group('g2', sh + [stroke(LU, 12, 22)])], 20 + i, op, p=(x, 45), o=o))
        L.append(layer(f'off{i}', [group('t', sh + [stroke('#5A5C62', 5)])], 30 + i, op, p=(x, 45)))
    L.append(layer('frame', [group('f', [rect(196, 92, 10), stroke('#3A3B40', 3)], tr(p=(100, 70)))], 40, op, p=(0, 0)))
    L.append(layer('hum', [group('h', [rect(196, 92, 10), stroke(LU, 30, 5)], tr(p=(100, 70)))], 41, op, p=(0, 0), o=kf([(80, 0), (100, 100), (140, 50), (180, 100)], 'breath')))
    L.append(layer('wall', [group('bg', [rect(200, 140, 0), fill(G)])], 99, op, p=(100, 70)))
    return finish(comp('rf_neon_open', 200, 140, op, L, markers=[('strike', 0, 80), ('hum', 90, 90)]))

# ENVELOPE into a lit slot: message / request sent
def sent():
    op = 90; env = [group('e', [rect(54, 36, 2), fill(P), stroke(G, 3)]), group('flap', [path([(-27, -18), (0, 4), (27, -18)], False), stroke(G, 3)])]
    return finish(comp('rf_sent', 120, 120, op, [
        layer('env', env, 1, op, p=kf([(0, [60, 30]), (30, [60, 78])], 'settle'), o=hold([(0, 100), (34, 0)])),
        layer('slot-glow', [group('g', [rect(70, 8, 4), stroke(LU, 18, 18)])], 2, op, p=(60, 86), o=flick(32)),
        layer('slot', [group('s', [rect(70, 8, 4), fill(G)])], 3, op, p=(60, 86)),
        layer('check', [group('c', [path([(46, 54), (56, 64), (76, 42)], False), trim(0, kf([(40, 0), (56, 100)], 'settle')), stroke(G, 5)])], 4, op, p=(0, 0)),
    ]))

# PROOF sheet: lines draw, a lit APPROVED tick lands (portal: design approval)
def proof():
    op = 120; L = []
    for i, (w, t) in enumerate([(60, 8), (44, 16), (52, 24)]):
        L.append(layer(f'l{i}', [group('l', [path([(40, 52 + i * 14), (40 + w, 52 + i * 14)], False), trim(0, kf([(t, 0), (t + 14, 100)], 'settle')), stroke(G, 3)])], 10 + i, op, p=(0, 0)))
    L.append(layer('letter', [group('f', [poly(fit(F, .3, 22, 82)), trim(0, kf([(30, 0), (56, 100)], 'settle')), stroke(G, 3)])], 20, op, p=(0, 0)))
    L.append(layer('stamp', [group('s', [rect(40, 40, 20), fill(LU, flick(70)), stroke(G, 3)]), group('c', [path([(-9, 0), (-2, 7), (10, -7)], False), stroke(G, 4)])], 1, op, p=kf([(64, [104, 104]), (70, [104, 100])], 'settle'), s=kf([(64, [130, 130]), (72, [100, 100])], 'settle'), o=hold([(0, 0), (64, 100)])))
    L.append(layer('sheet', [group('p', [poly([(28, 22), (106, 22), (124, 40), (124, 134), (28, 134)]), fill(P), stroke(G, 3)])], 30, op, p=(0, 0)))
    return finish(comp('rf_proof', 150, 150, op, L))

# LOADER: three LED pixels chase (buttons, search)
def loader():
    op = 36; L = []
    for i in range(3):
        t = i * 6
        L.append(layer(f'p{i}', [group('p', [rect(8, 8, 1), fill(LU)])], i + 1, op, p=(12 + i * 14, 12), o=kf([(t, 20), (t + 6, 100), (t + 18, 20), (36, 20)], 'standard') if t + 18 < 36 else kf([(0, 20), (t, 20), (t + 6, 100), (36, 30)], 'standard')))
    return finish(comp('rf_loader', 52, 24, op, L))

ALL = {'rf_step_design': step_design(), 'rf_step_bend': step_bend(), 'rf_step_paint': step_paint(), 'rf_step_led': step_led(), 'rf_step_install': step_install(),
       'rf_step_ship': step_ship(), 'rf_build_letter': build_letter(), 'rf_neon_open': neon_open(), 'rf_sent': sent(), 'rf_proof': proof(), 'rf_loader': loader()}
os.makedirs(OUT, exist_ok=True)
for n, c in ALL.items(): json.dump(c, open(f'{OUT}/{n}.json', 'w'), separators=(',', ':'))
print({n: os.path.getsize(f'{OUT}/{n}.json') for n in ALL})
