# Minimal Lottie (bodymovin 5.7) builder for halo learning motion. Shape layers only (lottie_light + Android Lottie safe).
import json, math

FPS = 30
EASE = {"standard": ([0.42], [0], [0.58], [1]), "settle": ([0.3], [0], [0.7], [1]), "breath": ([0.45], [0], [0.55], [1]), "out": ([0.2], [0], [0], [1])}

def hexc(h, a=1):
    h = h.lstrip('#'); return [int(h[i:i+2], 16) / 255 for i in (0, 2, 4)] + [a]

def st(v):  # static
    return {"a": 0, "k": v}

def kf(frames, ease="standard"):
    """frames: [(t, value), ...] value scalar or list"""
    ox, oy, ix, iy = EASE[ease]
    out = []
    for n, (t, v) in enumerate(frames):
        v = v if isinstance(v, list) else [v]
        k = {"t": t, "s": v}
        if n < len(frames) - 1:
            dim = len(v)
            k["o"] = {"x": ox * dim if dim > 1 else ox, "y": oy * dim if dim > 1 else oy}
            k["i"] = {"x": ix * dim if dim > 1 else ix, "y": iy * dim if dim > 1 else iy}
        out.append(k)
    return {"a": 1, "k": out}

def tr(p=(0, 0), s=(100, 100), r=0, o=100, a=(0, 0)):
    def v(x): return x if isinstance(x, dict) else st(list(x) if isinstance(x, tuple) else x)
    return {"ty": "tr", "p": v(p), "a": v(a), "s": v(s), "r": v(r), "o": v(o), "sk": st(0), "sa": st(0)}

def ellipse(d):
    return {"ty": "el", "d": 1, "p": st([0, 0]), "s": st([d, d]) if not isinstance(d, dict) else d}

def rect(w, h, r):
    sz = w if isinstance(w, dict) else st([w, h])
    return {"ty": "rc", "d": 1, "p": st([0, 0]), "s": sz, "r": st(r) if not isinstance(r, dict) else r}

def path(pts, closed=False):
    return {"ty": "sh", "ks": st({"i": [[0, 0]] * len(pts), "o": [[0, 0]] * len(pts), "v": [list(p) for p in pts], "c": closed})}

def curve(p0, c0, c1, p1):
    # one cubic segment p0 -> p1 with absolute control points
    return {"ty": "sh", "ks": st({"v": [list(p0), list(p1)], "o": [[c0[0] - p0[0], c0[1] - p0[1]], [0, 0]],
                                  "i": [[0, 0], [c1[0] - p1[0], c1[1] - p1[1]]], "c": False})}

def stroke(color, w, o=100, dash=None):
    s = {"ty": "st", "c": st(hexc(color)), "o": st(o) if not isinstance(o, dict) else o, "w": st(w) if not isinstance(w, dict) else w, "lc": 2, "lj": 2, "ml": 4}
    if dash:
        s["d"] = [{"n": "d", "nm": "dash", "v": st(dash[0])}, {"n": "g", "nm": "gap", "v": st(dash[1])}, {"n": "o", "nm": "offset", "v": st(0)}]
    return s

def fill(color, o=100):
    return {"ty": "fl", "c": st(hexc(color)), "o": st(o) if not isinstance(o, dict) else o, "r": 1}

def trim(s=0, e=100, o=0):
    def v(x): return x if isinstance(x, dict) else st(x)
    return {"ty": "tm", "s": v(s), "e": v(e), "o": v(o), "m": 1}

def group(name, items, t=None):
    return {"ty": "gr", "nm": name, "it": items + [t or tr()]}

def layer(name, shapes, ind, op, p=(120, 120), s=(100, 100), r=0, o=100, ip=0):
    def v(x): return x if isinstance(x, dict) else st(list(x) if isinstance(x, tuple) else x)
    return {"ddd": 0, "ind": ind, "ty": 4, "nm": name, "sr": 1,
            "ks": {"o": v(o), "r": v(r), "p": v(p), "a": st([0, 0, 0]), "s": v(s)},
            "ao": 0, "shapes": shapes, "ip": ip, "op": op, "st": 0, "bm": 0}

def comp(name, w, h, op, layers, markers=()):
    # bodymovin draws the first layer on top
    return {"v": "5.7.4", "fr": FPS, "ip": 0, "op": op, "w": w, "h": h, "nm": name, "ddd": 0, "assets": [],
            "layers": layers, "markers": [{"tm": a, "cm": c, "dr": d} for c, a, d in markers]}

def open_ring(d, color, w, extra_trim_e=None, name="ring"):
    # 316° arc, gap centred at 45° (upper right). Lottie ellipse starts at 12 o'clock, clockwise.
    e = extra_trim_e if extra_trim_e is not None else 87.78
    return group(name, [ellipse(d), trim(0, e, 67), stroke(color, w)])
