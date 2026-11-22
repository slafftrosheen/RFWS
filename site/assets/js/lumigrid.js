/* Reclame Fabriek website 2.5 · LUMIGRID page.
   The effect maths is a direct port of LumigridLedNode/components/signage/EffectRenderer.cpp (firmware 1.0.0-rc2),
   so the browser shows what the controller computes. No libraries, no network.
   Motion rules: one lit moment per screen; Calm / Off / reduced motion get still frames; the pause button stops everything. */
(function () {
  'use strict';
  var d = document, B = d.body;
  var RM = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var MODE = B.classList.contains('motion-off') ? 'off' : B.classList.contains('motion-calm') ? 'calm' : 'full';
  if (RM) MODE = 'off';
  var isPaused = function () { return B.classList.contains('paused'); };

  /* ---------------- firmware renderer (integer maths kept as in C++) ---------------- */
  var u8 = function (v) { return v & 255; };
  var scale8 = function (v, s) { return ((v & 255) * (s & 255) / 255) | 0; };
  var sine8 = function (p) { return (127.5 - 127.5 * Math.cos((p & 255) * (Math.PI / 128))) | 0; };
  var mix8 = function (a, b, t) { return ((a * (255 - t) + b * t) / 255) | 0; };
  var ease8 = function (v) { var x = v & 255; return ((x * x * (765 - 2 * x) + 32512) / 65025) | 0; };
  var WAVE = [0, 2, 10, 21, 37, 57, 79, 103, 128, 152, 176, 198, 218, 234, 245, 253, 255, 253, 245, 234, 218, 198, 176, 152, 128, 103, 79, 57, 37, 21, 10, 2, 0];
  var wave16 = function (p) { p &= 65535; var i = p >> 11, f = p & 2047; return ((WAVE[i] * (2048 - f) + WAVE[i + 1] * f) / 2048) | 0; };
  var cyc = function (t, per) { return (((t % per) * 65536) / per) | 0; };
  var tri16 = function (p) { var dd = p <= 32768 ? p : 65536 - p; return ((dd * 65535) / 32768) | 0; };
  var imul = Math.imul;
  var hash32 = function (v) { v = v >>> 0; v ^= v >>> 16; v = imul(v, 0x7feb352d) >>> 0; v ^= v >>> 15; v = imul(v, 0x846ca68b) >>> 0; return (v ^ (v >>> 16)) >>> 0; };
  var xorshift = function (s) { s.v ^= s.v << 13; s.v >>>= 0; s.v ^= s.v >>> 17; s.v ^= s.v << 5; s.v >>>= 0; return s.v; };
  var noise = function (t, iv, seed) { var tick = Math.floor(t / iv), fr = ease8(((t % iv) * 255 / iv) | 0); return mix8(hash32(tick ^ seed) >>> 24, hash32((tick + 1) ^ seed) >>> 24, fr); };
  var pulse16 = function (p, s, pk, e) { if (p <= s || p >= e) return 0; if (p < pk) return ease8(((p - s) * 255 / (pk - s)) | 0); return 255 - ease8(((p - pk) * 255 / (e - pk)) | 0); };
  var shuffled = function (i) { i &= 255; i ^= i >> 4; i = (i * 45) & 255; i ^= i >> 4; i = (i * 45) & 255; return i ^ (i >> 4); };
  var PAL = [null, null, null, null, null, null,
    [[36, 8, 0], [156, 48, 2], [255, 146, 18], [255, 228, 154]], [[2, 12, 64], [0, 100, 186], [70, 214, 255], [226, 250, 255]],
    [[0, 238, 255], [178, 0, 255], [255, 0, 108], [160, 255, 0]], [[30, 6, 70], [100, 50, 164], [194, 130, 230], [255, 216, 240]],
    [[156, 0, 30], [255, 44, 94], [255, 156, 188], [255, 240, 228]], [[160, 0, 16], [255, 154, 20], [6, 142, 36], [255, 236, 190]],
    [[8, 0, 0], [116, 4, 0], [244, 54, 0], [255, 202, 72]], [[8, 4, 42], [56, 20, 108], [174, 44, 132], [255, 142, 100]]];
  function pal(id, i, c1, c2) {
    i &= 255;
    if (id === 0) return [((c1[0] * (255 - i) + c2[0] * i) / 255) | 0, ((c1[1] * (255 - i) + c2[1] * i) / 255) | 0, ((c1[2] * (255 - i) + c2[2] * i) / 255) | 0];
    if (id === 1) { if (i < 85) return [i * 3, 255 - i * 3, 0]; if (i < 170) { i -= 85; return [255 - i * 3, 0, i * 3]; } i -= 170; return [0, i * 3, 255 - i * 3]; }
    if (id === 2) return [0, 100 + ((i * 155 / 255) | 0), 155 + ((i * 100 / 255) | 0)];
    if (id === 3) return i < 128 ? [127 + i, i, 0] : [255, 127 + (i - 128), i - 128];
    if (id === 4) return [i >> 1, 128 + (i >> 1), i >> 2];
    if (id === 5) return i < 128 ? [255, i * 2, i] : [255 - (i - 128), 255, 128 + (i - 128)];
    var c = PAL[id] || PAL[6], pos = i * 3, seg = Math.min((pos / 255) | 0, 2), fr = pos - seg * 255;
    return [mix8(c[seg][0], c[seg + 1][0], fr), mix8(c[seg][1], c[seg + 1][1], fr), mix8(c[seg][2], c[seg + 1][2], fr)];
  }
  // default parameter per effect (firmware catalogue)
  var PARAM = { solid: 0, breathe: 4, chase: 3, sweep: 0, reveal: 25, sparkle: 2, gradient: 100, strobe: 50, fire: 70, aurora: 120, colorwave: 50, staticpulse: 20,
    rainbow: 1, colorfade: 10, scanner: 6, theater: 6, twinkle: 25, candle: 35, heartbeat: 4, ripple: 8, dissolve: 15, plasma: 3, lightning: 3, confetti: 15 };
  // a comfortable demo speed per effect (ms per cycle at the middle of the slider)
  var SPEED = { solid: 3000, breathe: 4000, chase: 2600, sweep: 3200, reveal: 4000, sparkle: 3000, gradient: 4200, strobe: 1600, fire: 1400, aurora: 6000, colorwave: 5000, staticpulse: 4000,
    rainbow: 5000, colorfade: 5000, scanner: 3000, theater: 2400, twinkle: 3200, candle: 2400, heartbeat: 1700, ripple: 2600, dissolve: 5000, plasma: 6000, lightning: 4000, confetti: 2200 };
  // flashing effects never go faster than this (well under three flashes a second over a large area)
  var MIN_SPEED = { strobe: 1200, lightning: 3200 };

  /** One layer for one output: fills out (Uint8 RGB, n pixels) and returns the PWM level 0-255. */
  function render(fx, n, t, speed, pid, c1, c2, out) {
    var P = PARAM[fx] || 0, sp = speed > 0 ? speed : 1, i, c, pwm = 255;
    t = Math.max(0, Math.floor(t));
    out.fill(0);
    var put = function (px, col, s) { if (px < 0 || px >= n) return; out[px * 3] = scale8(col[0], s); out[px * 3 + 1] = scale8(col[1], s); out[px * 3 + 2] = scale8(col[2], s); };
    var fill = function (col, s) { for (var k = 0; k < n; k++) put(k, col, s); };
    switch (fx) {
      case 'solid': fill(c1, 255); break;
      case 'breathe': { var ph = (((t % sp) * 255) / sp) | 0, sv = sine8(ph), mn = (255 * P / 100) | 0, b = (mn + (sv * (255 - mn)) / 255) | 0; fill(pal(pid, 255 - sv, c1, c2), b); pwm = b; break; }
      case 'chase': { var pos = (((t % sp) * n) / sp) | 0; for (var o = 0; o < P; o++) put(pos - o, pal(pid, ((o * 255) / P) | 0, c1, c2), ((255 * (P - o)) / P) | 0); break; }
      case 'sweep': { var pz = (((t % sp) * n) / sp) | 0; for (i = 0; i <= pz; i++) put(i, c1, 255); break; }
      case 'reveal': { var cp = t % sp, fd = Math.max(1, (sp * P / 100) | 0), f = cp < fd ? ((255 * cp) / fd) | 0 : 255; fill(c1, f); pwm = f; break; }
      case 'sparkle': { fill(c1, 255); var s = { v: (t / 50) | 0 || 1 }; for (i = 0; i < P; i++) { var rv = xorshift(s); put((rv >>> 3) % n, [255, 255, 255], 255); } break; }
      case 'gradient': { var sh = (((t % sp) * 255) / sp) | 0, ct = (P * 255 / 100) | 0; for (i = 0; i < n; i++) { var pp = u8(((i * 255 / n) | 0) + sh), it = 255 - (((255 - sine8(pp)) * ct) / 255 | 0); put(i, pal(pid, pp, c1, c2), it); } break; }
      case 'strobe': { pwm = (t % sp) < ((sp * P) / 100) ? 255 : 0; if (pwm) fill(c1, 255); break; }
      case 'fire': { var sf = { v: (t / ((sp / 10 | 0) + 1)) | 0 || 1 }, fh = (P * 255 / 100) | 0; for (i = 0; i < n; i++) { var heat = xorshift(sf) & 255, hg = ((i * 255) / Math.max(1, n - 1)) | 0; if (hg > fh && fh < 255) heat = ((heat * (255 - hg)) / (255 - fh)) | 0; put(i, pal(pid, heat, c1, c2), heat); } break; }
      case 'aurora': { var ap = ((t * 255) / sp) | 0, rb = 255 - (((255 - sine8(ap)) * P / 255) | 0), gb = 255 - (((255 - sine8(ap + 85)) * P / 255) | 0), bb = 255 - (((255 - sine8(ap + 170)) * P / 255) | 0);
        fill([scale8(c1[0], rb), scale8(c1[1], gb), scale8(c1[2], bb)], 255); pwm = rb; break; }
      case 'colorwave': { var wl = P || 50, cs = ((t * 255) / sp) | 0; for (i = 0; i < n; i++) { var wp = u8(((i * 255) / wl | 0) + cs); put(i, pal(pid, wp, c1, c2), sine8(wp)); } pwm = sine8(cs & 255); break; }
      case 'staticpulse': { var sp8 = (((t % sp) * 255) / sp) | 0, mn2 = (255 * P / 100) | 0, b2 = (mn2 + (sine8(sp8) * (255 - mn2)) / 255) | 0; fill(c1, b2); pwm = b2; break; }
      case 'rainbow': { var rs = ((t * 256) / sp) | 0; for (i = 0; i < n; i++) put(i, pal(1, u8(((i * P * 256) / n | 0) + rs), c1, c2), 255); break; }
      case 'colorfade': { var cph = cyc(t, sp), hold = (P * 65536 / 100) | 0, half = cph & 32767, po = half <= hold ? 0 : (((half - hold) * 255) / (32768 - hold)) | 0; po = ease8(po); if (cph >= 32768) po = 255 - po; fill(pal(pid, po, c1, c2), 255); break; }
      case 'scanner': { var ce = (tri16(cyc(t, sp)) * (n - 1) * 256 / 65535) | 0, rad = P * 128 + 128; for (i = 0; i < n; i++) { var dist = Math.abs(i * 256 - ce); if (dist >= rad) continue; var ed = ((dist * 255) / rad) | 0; put(i, pal(pid, ed, c1, c2), 255 - ease8(ed)); } break; }
      case 'theater': { var tp = cyc(t, sp), spc = P * 256, off = ((tp * spc) / 65536) | 0; for (i = 0; i < n; i++) { var q = (i * 256 + spc - off) % spc, dq = Math.min(q, spc - q); if (dq >= 256) continue; put(i, pal(pid, u8(((i * 255) / n | 0) + (tp >> 8)), c1, c2), 255 - ((dq * 255 / 256) | 0)); } break; }
      case 'twinkle': case 'confetti': {
        var tl = Math.floor(t * 65536 / sp), salt = fx === 'twinkle' ? 0x51ed270b : 0x68bc21eb;
        for (i = 0; i < n; i++) {
          var seed = hash32((i + salt) >>> 0), loc = tl + (seed & 65535), ev = hash32(Math.floor(loc / 65536) ^ seed);
          if (ev % 100 >= P) continue;
          var l16 = loc % 65536, inten = fx === 'twinkle' ? wave16(l16) : (function () { var dc = 255 - (l16 >> 8); return scale8(dc, dc); })();
          put(i, pal(pid, ev >>> 24, c1, c2), inten);
        } break; }
      case 'candle': { var iv = Math.max(1, (sp / 12) | 0), nz = ((noise(t, iv * 3, 0xa341316c) * 3 + noise(t, iv, 0xc8013ea4)) / 4) | 0, dp = (P * 255 / 100) | 0, ci = 255 - scale8(nz, dp); fill(c1, ci); pwm = ci; break; }
      case 'heartbeat': { var hp = cyc(t, sp), a1 = pulse16(hp, 0, 3277, 14418), a2 = scale8(pulse16(hp, 15729, 19661, 32768), 180), fl = (P * 255 / 100) | 0, hi = fl + scale8(Math.max(a1, a2), 255 - fl); fill(c1, hi); pwm = hi; break; }
      case 'ripple': { var rp = cyc(t, sp), cen = (n - 1) * 128, wd = P * 128 + 128, rr = ((rp * (cen + wd)) / 65536) | 0, col = pal(pid, rp >> 8, c1, c2);
        for (i = 0; i < n; i++) { var fc = Math.abs(i * 256 - cen), dr = Math.abs(fc - rr); if (dr >= wd) continue; put(i, col, 255 - ease8(((dr * 255) / wd) | 0)); } break; }
      case 'dissolve': { var so = Math.max(1, (P * 255 / 100) | 0), pr = ((tri16(cyc(t, sp)) * (255 + so)) / 65535) | 0;
        for (i = 0; i < n; i++) { var th = shuffled(i), am = pr <= th ? 0 : Math.min(255, (((pr - th) * 255) / so) | 0); put(i, pal(pid, ease8(am), c1, c2), 255); } break; }
      case 'plasma': { var mo = Math.floor(t * 65536 / sp); for (i = 0; i < n; i++) { var ps = ((i * 65536) / n) | 0, wa = wave16(ps * P + mo), wb = wave16(ps * (P + 1) - mo * 2), wc = wave16(((ps * (P + 2)) / 2 | 0) + (mo / 2 | 0)), itf = ((wa + wb + wc) / 3) | 0; put(i, pal(pid, itf, c1, c2), 96 + scale8(itf, 159)); } break; }
      case 'lightning': { var lp = cyc(t, sp); if (lp >= 32768) { pwm = 0; break; } var bu = lp * P, fl2 = (bu / 32768) | 0, lo = ((bu % 32768) * 2) % 65536,
        stg = fl2 === 0 ? 255 : 180 + scale8(hash32(((t / sp) | 0) ^ imul(fl2, 0x9e3779b9)) >>> 24, 75), li = scale8(pulse16(lo, 0, 2048, 24576), stg); fill(c1, li); pwm = li; break; }
      default: fill(c1, 255);
    }
    return pwm;
  }

  var C1 = [255, 181, 71], C2 = [200, 22, 29];   // Lumen + Fabriek Red as the "Custom" pair

  /* ---------------- drawing helpers ---------------- */
  function dot(ctx, x, y, r, R, G, Bc, a) { ctx.fillStyle = 'rgba(' + R + ',' + G + ',' + Bc + ',' + a + ')'; ctx.beginPath(); ctx.arc(x, y, r, 0, 6.2832); ctx.fill(); }
  var HAS_FILTER = (function () { try { var c = d.createElement('canvas').getContext('2d'); return c && 'filter' in c; } catch (e) { return false; } })();
  // perceived brightness for drawing (LEDs look brighter than their linear value)
  function lift(v) { return Math.round(255 * Math.pow(v / 255, 0.62)); }
  function glowPass(ctx, src, w, h, px) {
    if (!HAS_FILTER) return;
    ctx.save(); ctx.globalCompositeOperation = 'lighter'; ctx.filter = 'blur(' + Math.round(px) + 'px)'; ctx.globalAlpha = .9; ctx.drawImage(src, 0, 0, w, h); ctx.restore();
  }

  /* ---------------- 1 · hero: LUMIGRID in LED pixels (switch on once, then a slow amber flow) ---------------- */
  function hero() {
    var host = d.querySelector('[data-lg-hero]'), cv = host && host.querySelector('[data-lg-word]'), h1 = host && host.querySelector('.lg-h1');
    if (!cv || !cv.getContext) return;
    var word = (h1.textContent || 'LUMIGRID').trim().toUpperCase().slice(0, 14), mark = cv.parentNode;
    var cells = [], cols = 0, rows = 0, pitch = 10, dpr = Math.min(2, window.devicePixelRatio || 1), W = 0, H = 0, buf = null, off = null, octx = null;
    function layout() {
      var cw = mark.clientWidth || 800; if (!cw) return false;
      rows = cw < 520 ? 15 : cw < 900 ? 19 : 23;                         // dot rows across the cap height
      var probe = d.createElement('canvas'), pc = probe.getContext('2d'), fs = 200, font = '700 ' + fs + 'px "RF Condensed","Barlow Condensed","Arial Narrow",Arial,sans-serif';
      pc.font = font; var tw = Math.ceil(pc.measureText(word).width), capH = fs * .72;
      var cell = capH / rows; cols = Math.ceil(tw / cell) + 2;
      pitch = Math.min(18, cw / cols); W = Math.round(cols * pitch); H = Math.round((rows + 2) * pitch);
      probe.width = Math.ceil(tw + cell * 2); probe.height = Math.ceil(capH + cell * 2);
      pc.font = font; pc.fillStyle = '#fff'; pc.textBaseline = 'alphabetic'; pc.fillText(word, cell, cell + capH);
      var img = pc.getImageData(0, 0, probe.width, probe.height).data; cells = [];
      for (var cx = 0; cx < cols; cx++) {
        var col = [];
        for (var cy = 0; cy < rows + 2; cy++) {
          var sx = Math.min(probe.width - 1, Math.round((cx + .5) * cell)), sy = Math.min(probe.height - 1, Math.round((cy + .5) * cell));
          if (img[(sy * probe.width + sx) * 4 + 3] > 110) col.push([cx, cy]);
        }
        if (cx % 2) col.reverse();                                     // one serpentine strip runs through the letters
        cells = cells.concat(col);
      }
      cv.width = Math.round(W * dpr); cv.height = Math.round(H * dpr); cv.style.aspectRatio = W + ' / ' + H; cv.style.maxWidth = W + 'px';
      off = d.createElement('canvas'); off.width = cv.width; off.height = cv.height; octx = off.getContext('2d');
      buf = new Uint8Array(cells.length * 3);
      return cells.length > 0;
    }
    var ctx = cv.getContext('2d'), IGN = 1700, start = 0, raf = 0, visible = true;
    function frame(t, ignite) {
      var n = cells.length; if (!n) return;
      if (ignite && t < IGN) {
        var pos = (t / IGN) * n, ph = 0;
        render('gradient', n, 0, 7000, 6, C1, C2, buf);
        for (var i = 0; i < n; i++) { if (i > pos) { buf[i * 3] = buf[i * 3 + 1] = buf[i * 3 + 2] = 0; } else if (pos - i < 6) { var k = 1 - (pos - i) / 6; buf[i * 3] = Math.min(255, buf[i * 3] + 80 * k); buf[i * 3 + 1] = Math.min(255, buf[i * 3 + 1] + 120 * k); buf[i * 3 + 2] = Math.min(255, buf[i * 3 + 2] + 110 * k); } }
      } else render('gradient', n, ignite ? t - IGN : t, 7000, 6, C1, C2, buf);
      octx.setTransform(dpr, 0, 0, dpr, 0, 0); octx.clearRect(0, 0, W, H);
      var r = pitch * .36;
      for (var j = 0; j < n; j++) {
        var x = (cells[j][0] + .5) * pitch, y = (cells[j][1] + .5) * pitch, R = buf[j * 3], G = buf[j * 3 + 1], Bc = buf[j * 3 + 2];
        if (R + G + Bc < 12) { dot(octx, x, y, r, 52, 54, 60, 1); continue; }
        // a floor keeps every lit pixel readable, like a real LED behind a diffuser
        var m = Math.max(R, G, Bc), lf = Math.max(150, lift(m)) / m;
        dot(octx, x, y, r, Math.min(255, R * lf) | 0, Math.min(255, G * lf) | 0, Math.min(255, Bc * lf) | 0, 1);
      }
      ctx.setTransform(1, 0, 0, 1, 0, 0); ctx.clearRect(0, 0, cv.width, cv.height);
      glowPass(ctx, off, cv.width, cv.height, pitch * .9 * dpr);
      ctx.drawImage(off, 0, 0);
    }
    function loop(ts) {
      raf = 0; if (!start) start = ts;
      if (!isPaused() && !d.hidden && visible) frame(ts - start, true);
      if (MODE === 'full') raf = requestAnimationFrame(loop);
      else if (MODE === 'calm' && ts - start < IGN + 50) raf = requestAnimationFrame(loop);
    }
    function go() {
      if (!layout()) return;
      host.classList.add('lg-on');
      if (MODE === 'off') { frame(2600, false); return; }
      if (isPaused()) { frame(2600, false); start = performance.now() - IGN - 2600; }
      raf = requestAnimationFrame(loop);
    }
    var ready = d.fonts && d.fonts.ready ? d.fonts.ready : Promise.resolve();
    ready.then(go, go);
    if ('IntersectionObserver' in window) new IntersectionObserver(function (es) { visible = es[0].isIntersecting; }).observe(cv);
    var rz = 0; window.addEventListener('resize', function () { clearTimeout(rz); rz = setTimeout(function () { if (layout()) frame(start ? performance.now() - start : 2600, MODE !== 'off'); }, 150); });
  }

  /* ---------------- 2 · effect lab: 8 pixel strips + 8 dimmers ---------------- */
  function lab() {
    var sec = d.querySelector('[data-lg-lab]'); if (!sec) return;
    var cv = sec.querySelector('[data-lg-stage]'), ctx = cv.getContext('2d'), cfg = {};
    try { cfg = JSON.parse(sec.getAttribute('data-cfg') || '{}'); } catch (e) {}
    var byId = {}; (cfg.fx || []).forEach(function (f) { byId[f.id] = f; });
    var st = { fx: 'gradient', pal: 6, sp: 50 };
    var LANES = 8, NPX = 48, W = 960, H = 560, dpr = Math.min(2, window.devicePixelRatio || 1);
    cv.width = W * dpr; cv.height = H * dpr;
    var off = d.createElement('canvas'); off.width = cv.width; off.height = cv.height; var o = off.getContext('2d');
    var lane = new Uint8Array(NPX * 3), one = new Uint8Array(3);
    var nameEl = sec.querySelector('[data-lg-name]'), descEl = sec.querySelector('[data-lg-desc]'), pwmEl = sec.querySelector('[data-lg-pwm]');
    function speed() { var base = SPEED[st.fx] || 3000, v = base * Math.pow(2, (50 - st.sp) / 25); return Math.max(MIN_SPEED[st.fx] || 300, Math.round(v)); }
    function draw(t) {
      var sp = speed(), x0 = 70, pitch = (W - x0 - 24) / NPX, y0 = 58, rowH = 40, r = pitch * .34;
      o.setTransform(dpr, 0, 0, dpr, 0, 0); o.clearRect(0, 0, W, H);
      o.font = '600 15px "RF Condensed","Barlow Condensed",Arial,sans-serif'; o.textBaseline = 'middle';
      o.fillStyle = '#9AA0A6'; o.fillText((cfg.strips || 'A1–A8').toUpperCase(), 24, 28);
      for (var L = 0; L < LANES; L++) {
        var y = y0 + L * rowH;
        render(st.fx, NPX, t - L * sp / 16, sp, st.pal, C1, C2, lane);
        o.fillStyle = '#6B6F76'; o.fillText('A' + (L + 1), 24, y);
        for (var i = 0; i < NPX; i++) {
          var R = lane[i * 3], G = lane[i * 3 + 1], Bc = lane[i * 3 + 2], x = x0 + (i + .5) * pitch;
          if (R + G + Bc < 10) { dot(o, x, y, r, 40, 42, 47, 1); continue; }
          var m = Math.max(R, G, Bc), lf = lift(m) / m;
          dot(o, x, y, r, Math.min(255, R * lf) | 0, Math.min(255, G * lf) | 0, Math.min(255, Bc * lf) | 0, 1);
        }
      }
      // dimmers: single-channel lamps, brightness only (warm white LED)
      var yd = y0 + LANES * rowH + 54, gap = (W - x0 - 24) / 8;
      o.fillStyle = '#9AA0A6'; o.fillText((cfg.dimmers || 'P1–P8').toUpperCase(), 24, yd - 44);
      for (var p = 0; p < 8; p++) {
        var lv = render(st.fx, 1, t - p * sp / 16, sp, st.pal, C1, C2, one), cx = x0 + (p + .5) * gap, a = lift(lv) / 255;
        o.fillStyle = '#6B6F76'; o.textAlign = 'center'; o.fillText('P' + (p + 1), cx, yd + 44); o.textAlign = 'start';
        o.fillStyle = '#1F2024'; o.beginPath(); o.roundRect ? o.roundRect(cx - gap * .36, yd - 13, gap * .72, 26, 13) : o.rect(cx - gap * .36, yd - 13, gap * .72, 26); o.fill();
        if (a > .02) { o.fillStyle = 'rgba(255,214,160,' + a.toFixed(3) + ')'; o.beginPath(); o.roundRect ? o.roundRect(cx - gap * .36 + 3, yd - 10, gap * .72 - 6, 20, 10) : o.rect(cx - gap * .36 + 3, yd - 10, gap * .72 - 6, 20); o.fill(); }
      }
      ctx.setTransform(1, 0, 0, 1, 0, 0); ctx.fillStyle = '#09090A'; ctx.fillRect(0, 0, cv.width, cv.height);
      glowPass(ctx, off, cv.width, cv.height, 7 * dpr);
      ctx.drawImage(off, 0, 0);
    }
    // a still frame that shows the effect at its most telling moment (Calm / Off / paused)
    function stillT() {
      var sp = speed(), best = 0, bt = 0;
      for (var k = 0; k < 16; k++) { var t = sp * k / 16 + 7, s = 0; render(st.fx, NPX, t, sp, st.pal, C1, C2, lane); for (var i = 0; i < lane.length; i++) s += lane[i]; if (s > best) { best = s; bt = t; } }
      return bt;
    }
    var t0 = performance.now(), tFrozen = 0, raf = 0, visible = false;
    var animated = function () { return MODE === 'full' && !isPaused() && !d.hidden && visible; };
    function loop(ts) { raf = 0; if (!animated()) return; draw(ts - t0); raf = requestAnimationFrame(loop); }
    function refresh() { if (animated()) { if (!raf) raf = requestAnimationFrame(loop); } else { cancelAnimationFrame(raf); raf = 0; draw(MODE === 'full' && isPaused() ? performance.now() - t0 : (tFrozen = stillT())); } }
    function announce() { var f = byId[st.fx] || {}; nameEl.textContent = f.n || st.fx; descEl.textContent = f.t || ''; pwmEl.textContent = f.pwm ? cfg.yes : cfg.no; }
    sec.querySelectorAll('[data-fx]').forEach(function (b) { b.addEventListener('click', function () {
      st.fx = b.getAttribute('data-fx'); sec.querySelectorAll('[data-fx]').forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); }); announce(); t0 = performance.now(); refresh(); }); });
    sec.querySelectorAll('[data-pal]').forEach(function (b) { b.addEventListener('click', function () {
      st.pal = +b.getAttribute('data-pal'); sec.querySelectorAll('[data-pal]').forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); }); refresh(); }); });
    var rng = sec.querySelector('[data-lg-speed]'); if (rng) rng.addEventListener('input', function () { st.sp = +rng.value; refresh(); });
    if ('IntersectionObserver' in window) new IntersectionObserver(function (es) { visible = es[0].isIntersecting; refresh(); }, { rootMargin: '80px' }).observe(cv); else { visible = true; }
    d.addEventListener('visibilitychange', refresh);
    new MutationObserver(refresh).observe(B, { attributes: true, attributeFilter: ['class'] });
    var ready = d.fonts && d.fonts.ready ? d.fonts.ready : Promise.resolve();
    ready.then(refresh, refresh);
    sec.setAttribute('data-ready', '1');
    // test hook: read back what is on the stage (no image export)
    sec._lgStats = function () { var px = ctx.getImageData(0, 0, cv.width, cv.height).data, lit = 0, sum = 0; for (var i = 0; i < px.length; i += 16) { var v = px[i] + px[i + 1] + px[i + 2]; sum += v; if (v > 120) lit++; } return { lit: lit, sum: sum, fx: st.fx, pal: st.pal, running: !!raf }; };
  }

  /* ---------------- 3 · board LEDs light once, flow line powers up, icons draw ---------------- */
  function reveal() {
    var board = d.querySelector('[data-lg-board]'), flow = d.querySelector('[data-lg-flow]');
    var els = [].slice.call(d.querySelectorAll('.lg-step, .lg-card'));
    if (MODE === 'off' || !('IntersectionObserver' in window)) { if (board) board.classList.add('lit'); if (flow) flow.classList.add('in'); els.forEach(function (e) { e.classList.add('in'); }); return; }
    var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add(e.target === board ? 'lit' : 'in'); io.unobserve(e.target); } }); }, { threshold: .35 });
    if (board) io.observe(board); if (flow) io.observe(flow); els.forEach(function (e) { io.observe(e); });
  }

  hero(); lab(); reveal();
})();
