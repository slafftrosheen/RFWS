# Reclame Fabriek · website icon set "Signage" v1.0
# Same grammar as the OMS set: 24px grid, 1.75 stroke, square caps, miter joins,
# chamfered corners (top-left + bottom-right cut) like a box-letter return, and one "lit" pixel per icon
# that switches on in Lumen when the icon animates.
import math, json
def R(x, y, w, h, c=2.5): return f"M{x+c} {y}H{x+w}V{y+h-c}L{x+w-c} {y+h}H{x}V{y+c}Z"
def Rp(x, y, w, h): return f"M{x} {y}H{x+w}V{y+h}H{x}Z"
def L(*p):
    s = f"M{p[0]} {p[1]}"
    for i in range(2, len(p), 2): s += f"L{p[i]} {p[i+1]}"
    return s
def C(cx, cy, r): return f"M{cx-r} {cy}a{r} {r} 0 1 0 {2*r} 0a{r} {r} 0 1 0 {-2*r} 0"
def LIT(x, y, s=2.4): return ('lit', f"M{x-s/2} {y-s/2}h{s}v{s}h{-s}Z")
def F(p): return ('fill', p)

I = {}
# ---- signage products
I['box-letter'] = ["M4 3H16V7H8V10H14V14H8V19H4V5.5Z", L(16, 3, 19, 6, 19, 10, 16, 7), L(14, 10, 17, 13, 17, 14), "M4 19L6 21H10L8 19", LIT(19, 17)]
I['lightbox'] = [R(3, 5, 18, 14), L(7, 10, 14, 10), L(7, 14, 17, 14), LIT(17, 10)]
I['pylon'] = [R(7, 2, 10, 15), L(12, 17, 12, 21), L(6, 21, 18, 21), L(10, 7, 14, 7), L(10, 11, 14, 11), LIT(14, 14, 2)]
I['neon'] = ["M3 15C3 8 8 8 8 12.5S12 17 12 11.5S16 6 16 11V17", L(16, 17, 21, 17), LIT(5, 20, 2), LIT(19, 20, 2)]
I['flat-letter'] = [R(4, 8, 16, 8, 2), L(4, 18.5, 20, 18.5), LIT(8, 12, 2), LIT(16, 12, 2)]
I['led-module'] = [Rp(4, 8, 16, 8), L(1.5, 12, 4, 12), L(20, 12, 22.5, 12), LIT(8, 12), LIT(12, 12), LIT(16, 12)]
I['controller'] = [R(3, 3, 18, 18), F(Rp(6.8, 6.8, 2.4, 2.4)), F(Rp(10.8, 6.8, 2.4, 2.4)), LIT(16, 8), F(Rp(6.8, 10.8, 2.4, 2.4)), LIT(12, 12), F(Rp(14.8, 10.8, 2.4, 2.4)), LIT(8, 16), F(Rp(10.8, 14.8, 2.4, 2.4)), F(Rp(14.8, 14.8, 2.4, 2.4))]
I['bulb'] = [C(12, 9.5, 5.5), L(9.5, 15, 9.5, 18.5, 14.5, 18.5, 14.5, 15), L(10, 21.5, 14, 21.5), LIT(12, 9.5, 2.6)]
# ---- light types (front view of a box letter)
I['light-frontlit'] = [R(5, 5, 14, 14), F(Rp(8.5, 8.5, 7, 7)), LIT(19.8, 4.2, 1.8)]
I['light-corona'] = [R(7.5, 7.5, 9, 9), L(3, 8, 3, 3, 8, 3), L(16, 3, 21, 3, 21, 8), L(21, 16, 21, 21, 16, 21), L(8, 21, 3, 21, 3, 16), LIT(12, 12)]
I['light-neon'] = [R(4, 4, 16, 16), "M8.5 8.5H15.5V15.5H8.5Z", LIT(12, 12, 1.8)]
I['light-side'] = [R(7, 7, 10, 10), L(2.5, 9, 4.5, 9), L(2.5, 15, 4.5, 15), L(19.5, 9, 21.5, 9), L(19.5, 15, 21.5, 15), LIT(12, 12)]
I['light-front-corona'] = [R(7.5, 7.5, 9, 9), F(Rp(10, 10, 4, 4)), L(3, 8, 3, 3, 8, 3), L(16, 3, 21, 3, 21, 8), L(21, 16, 21, 21, 16, 21), L(8, 21, 3, 21, 3, 16)]
I['light-unlit'] = [R(5, 5, 14, 14), L(9, 15, 15, 9), LIT(19.8, 4.2, 1.8)]
# ---- production
I['cnc'] = [L(2, 5, 22, 5), R(9.5, 5, 5, 7, 1.5), L(12, 12, 12, 15), L(3, 20, 21, 20), "M5 17.5H19", LIT(12, 16.5, 2)]
I['laser'] = [Rp(9, 2.5, 6, 6), L(12, 8.5, 12, 16), L(4, 20, 20, 20), L(4, 17, 9, 17), L(15, 17, 20, 17), LIT(12, 17.2)]
I['bender'] = [C(7.5, 7.5, 3), C(16.5, 7.5, 3), "M3 15H10C14 15 16.5 17.5 16.5 21.5", LIT(12, 7.5, 2)]
I['welding'] = [L(3, 21, 11, 13), L(9, 11, 13, 15), L(14, 10, 16.5, 7.5), L(15, 13.5, 18.5, 13.5), L(12, 8.5, 12, 4.5), LIT(18.5, 5.5)]
I['vacuum-form'] = ["M5 17C5 11 8 8 12 8S19 11 19 17", L(2.5, 17, 21.5, 17), L(12, 1.5, 12, 5), L(10, 3.5, 12, 5.5, 14, 3.5), L(2.5, 21, 21.5, 21), LIT(12, 12.5)]
I['3d-print'] = ["M8.5 2.5H15.5V6.5L12 10L8.5 6.5Z", L(8, 14, 14, 14), L(5, 17, 19, 17), L(5, 20.5, 19, 20.5), LIT(17, 14, 2)]
I['spray'] = [R(5, 9, 8, 12.5, 2), Rp(7, 5.5, 4, 3.5), LIT(17, 5, 1.8), LIT(20, 8, 1.8), LIT(17, 10, 1.8), LIT(20.5, 3, 1.6)]
I['swatches'] = [Rp(3, 3, 5, 18), Rp(9.5, 3, 5, 18), Rp(16, 3, 5, 18), L(3, 16, 21, 16), LIT(5.5, 18.5, 1.8), LIT(12, 18.5, 1.8), LIT(18.5, 18.5, 1.8)]
I['measure'] = ["M3 17L17 3L21 7L7 21Z", L(7.5, 12.5, 9.5, 14.5), L(10.5, 9.5, 12.5, 11.5), L(13.5, 6.5, 15.5, 8.5), LIT(17.5, 6.5, 1.8)]
I['rails'] = [R(6, 3, 5, 18, 1.5), R(13, 3, 5, 18, 1.5), L(2, 9, 6, 9), L(18, 9, 22, 9), L(2, 15, 6, 15), L(18, 15, 22, 15), LIT(15.5, 6, 1.8)]
I['template'] = [R(4, 2.5, 16, 19), "M9 7.5H15V11.5H11V16.5", LIT(9, 16.5, 2), LIT(15, 15.5, 2)]
# ---- company
I['factory'] = ["M3 21V10L8 13V10L13 13V10L18 13V3H21V21Z", L(3, 21, 21, 21), LIT(16, 17), LIT(10.5, 17)]
I['truck'] = [Rp(2, 6.5, 12, 10.5), "M14 9.5H18L21 12.5V17H14", C(7, 18.5, 2), C(17, 18.5, 2), LIT(19, 13.5, 1.8)]
I['globe'] = [C(12, 12, 9), L(3, 12, 21, 12), "M12 3C9 6 9 18 12 21", "M12 3C15 6 15 18 12 21", LIT(16.5, 7.5, 2)]
I['team'] = [C(9, 8, 3.2), C(16.5, 9, 2.5), "M3 20.5C3 15.5 5.7 13 9 13S15 15.5 15 20.5", "M15.5 13.5C18.6 13.5 21 15.8 21 19.5", LIT(19, 4, 1.8)]
I['chair'] = [L(7, 3, 7, 12), L(17, 3, 17, 12), "M5 12H19V15H5Z", L(7, 15, 7, 21), L(17, 15, 17, 21), LIT(12, 7, 2)]
I['lab'] = [L(9, 3, 15, 3), "M10 3V9L5 18A2 2 0 0 0 6.7 21H17.3A2 2 0 0 0 19 18L14 9V3", L(7.5, 15, 16.5, 15), LIT(12, 18, 2)]
I['shield'] = ["M12 2.5L20 5.5V12C20 16.5 16.5 19.5 12 21.5C7.5 19.5 4 16.5 4 12V5.5Z", L(8, 12, 11, 15, 16, 9)]
I['award'] = [C(12, 9, 6), "M9 14.3L7.5 21.5L12 19L16.5 21.5L15 14.3", LIT(12, 9, 2.6)]
I['calendar'] = [R(3, 5, 18, 16), L(3, 10, 21, 10), L(8, 3, 8, 7), L(16, 3, 16, 7), LIT(16, 15.5)]
I['clock'] = [C(12, 12, 9), L(12, 7, 12, 12, 16, 14.5), LIT(12, 12, 1.6)]
I['layers'] = ["M12 3L21 8L12 13L3 8Z", L(3, 12, 12, 17, 21, 12), L(3, 16, 12, 21, 21, 16), LIT(12, 8, 2)]
I['quote'] = [R(3, 4, 18, 13), L(8, 17, 8, 21, 12, 17), L(7, 9, 17, 9), L(7, 12.5, 13, 12.5), LIT(17, 12.5, 2)]
# ---- interface
I['arrow'] = [L(4, 12, 19, 12), L(13, 6, 19, 12, 13, 18)]
I['ext'] = [L(14, 4, 20, 4, 20, 10), L(20, 4, 11, 13), "M17 14V20H4V7H10"]
I['play'] = [F("M8 5V19L19 12Z")]
I['zoom'] = [C(10.5, 10.5, 6.5), L(15.5, 15.5, 21, 21)]
I['close'] = [L(6, 6, 18, 18), L(18, 6, 6, 18)]
I['menu'] = [L(4, 6.5, 20, 6.5), L(4, 12, 20, 12), L(4, 17.5, 14, 17.5)]
I['phone'] = ["M5 4H9L11 9L8.5 10.5A11 11 0 0 0 13.5 15.5L15 13L20 15V19A1 1 0 0 1 19 20A16 16 0 0 1 4 5A1 1 0 0 1 5 4Z"]
I['mail'] = [Rp(3, 5.5, 18, 13), L(3, 6.5, 12, 13, 21, 6.5)]
I['pin'] = ["M12 21.5S5.5 15.3 5.5 10A6.5 6.5 0 0 1 18.5 10C18.5 15.3 12 21.5 12 21.5Z", LIT(12, 10, 2.6)]
I['check'] = [L(4.5, 12.5, 9.5, 17.5, 19.5, 7)]
I['bolt'] = [F("M13 2L4 14H11L10 22L19 10H12Z")]
I['spark'] = [L(12, 2, 12, 7), L(12, 17, 12, 22), L(2, 12, 7, 12), L(17, 12, 22, 12), L(5, 5, 8.5, 8.5), L(15.5, 15.5, 19, 19), L(19, 5, 15.5, 8.5), L(8.5, 15.5, 5, 19), LIT(12, 12, 2.6)]

ORDER = {
 'Products': ['box-letter', 'lightbox', 'pylon', 'neon', 'flat-letter', 'led-module', 'controller', 'bulb'],
 'Light types': ['light-frontlit', 'light-corona', 'light-neon', 'light-side', 'light-front-corona', 'light-unlit'],
 'Production': ['cnc', 'laser', 'bender', 'welding', 'vacuum-form', '3d-print', 'spray', 'swatches', 'measure', 'rails', 'template'],
 'Company': ['factory', 'truck', 'globe', 'team', 'chair', 'lab', 'shield', 'award', 'calendar', 'clock', 'layers', 'quote'],
 'Interface': ['arrow', 'ext', 'play', 'zoom', 'close', 'menu', 'phone', 'mail', 'pin', 'check', 'bolt', 'spark']}
assert sorted(sum(ORDER.values(), [])) == sorted(I), set(I) ^ set(sum(ORDER.values(), []))

def inner(name):
    out = []
    for p in I[name]:
        if isinstance(p, tuple):
            kind, d = p
            out.append(f'<path class="{"i-lit" if kind == "lit" else "i-fill"}" d="{d}"/>')
        else:
            out.append(f'<path d="{p}" pathLength="1"/>')
    return ''.join(out)

def svg(name, size=24, color='#1C1D20', lit='#FFB547'):
    body = inner(name).replace('class="i-lit"', f'fill="{lit}" stroke="none"').replace('class="i-fill"', f'fill="{color}" stroke="none"').replace(' pathLength="1"', '')
    return (f'<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 24 24" fill="none" stroke="{color}" '
            f'stroke-width="1.75" stroke-linecap="square" stroke-linejoin="miter">{body}</svg>')

if __name__ == '__main__':
    import os
    os.makedirs('/tmp/rfmotion/icons/svg', exist_ok=True)
    for n in I:
        open(f'/tmp/rfmotion/icons/svg/rf-{n}.svg', 'w').write(svg(n))
    # PHP array for the site
    php = "<?php\n/* GENERATED by icons.py — Reclame Fabriek website icon set v1.0 (24px, 1.75 stroke, chamfered corners, one lit pixel). */\nconst RF_ICONS = [\n"
    for n in I: php += f"  '{n}' => '{inner(n)}',\n"
    php += "];\nconst RF_ICON_GROUPS = " + json.dumps(ORDER).replace('{', '[').replace('}', ']').replace('":', '" =>') + ";\n"
    open('/tmp/rfmotion/icons/icons.php', 'w').write(php)
    json.dump(ORDER, open('/tmp/rfmotion/icons/order.json', 'w'))
    print(len(I), 'icons')
