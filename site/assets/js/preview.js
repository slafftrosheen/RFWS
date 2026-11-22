/* Reclame Fabriek · 2.4 night preview: type a text, pick the light and colours, switch the sign on.
   Drawn on a canvas in the brand font; a photo of the visitor's own facade stays on their device (never uploaded unless they attach the preview).
   Motion follows the site setting: full = the sign flickers on like a real one (one lit moment), calm = soft fade, off / reduced motion = instant. */
(function () {
  'use strict';
  var d = document, B = d.body;
  var RM = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var MOTION = function () { return RM || B.classList.contains('motion-off') ? 'off' : (B.classList.contains('motion-calm') || B.classList.contains('paused') ? 'calm' : 'full'); };
  var W = 1600, H = 800, FONT = '"RF Condensed", "Barlow Condensed", "Arial Narrow", sans-serif';
  var WALL = {}, SEED = 7;
  function rnd() { SEED = (SEED * 16807) % 2147483647; return (SEED - 1) / 2147483646; }
  function hex(h) { h = h.replace('#', ''); return [parseInt(h.substr(0, 2), 16), parseInt(h.substr(2, 2), 16), parseInt(h.substr(4, 2), 16)]; }
  function rgb(a, al) { return 'rgba(' + Math.round(a[0]) + ',' + Math.round(a[1]) + ',' + Math.round(a[2]) + ',' + (al == null ? 1 : al) + ')'; }
  function mix(a, b, k) { return [a[0] + (b[0] - a[0]) * k, a[1] + (b[1] - a[1]) * k, a[2] + (b[2] - a[2]) * k]; }
  function shade(a, k) { return mix(a, [0, 0, 0], k); }
  function isWhite(a) { return a[0] > 225 && a[1] > 225 && a[2] > 225; }

  /* ---------- walls (drawn once, cached) ---------- */
  function wall(kind) {
    if (WALL[kind]) return WALL[kind];
    var c = d.createElement('canvas'); c.width = W; c.height = H; var g = c.getContext('2d'); SEED = 11;
    if (kind === 'brick') {
      g.fillStyle = '#b9ab9b'; g.fillRect(0, 0, W, H);
      var bw = 128, bh = 42, row = 0;
      for (var y = 0; y < H; y += bh + 6, row++) for (var x = -(row % 2) * bw / 2; x < W; x += bw + 6) {
        var base = [128 + rnd() * 34, 62 + rnd() * 20, 46 + rnd() * 14]; g.fillStyle = rgb(base); g.fillRect(x, y, bw, bh);
        g.fillStyle = 'rgba(0,0,0,' + (0.05 + rnd() * 0.08) + ')'; g.fillRect(x, y + bh - 7, bw, 7);
        g.fillStyle = 'rgba(255,255,255,0.05)'; g.fillRect(x, y, bw, 4);
      }
    } else if (kind === 'plaster') {
      g.fillStyle = '#e4dfd6'; g.fillRect(0, 0, W, H);
      for (var i = 0; i < 9000; i++) { g.fillStyle = rnd() > 0.5 ? 'rgba(0,0,0,0.045)' : 'rgba(255,255,255,0.12)'; g.fillRect(rnd() * W, rnd() * H, 2 + rnd() * 3, 2 + rnd() * 3); }
    } else if (kind === 'glass') {
      var gr = g.createLinearGradient(0, 0, 0, H); gr.addColorStop(0, '#2b3038'); gr.addColorStop(1, '#14171c'); g.fillStyle = gr; g.fillRect(0, 0, W, H);
      g.fillStyle = 'rgba(255,255,255,0.06)'; for (var k = 0; k < 4; k++) { g.beginPath(); var x0 = 120 + k * 420; g.moveTo(x0, 0); g.lineTo(x0 + 140, 0); g.lineTo(x0 - 160, H); g.lineTo(x0 - 300, H); g.fill(); }
      g.fillStyle = '#8f959b'; for (var m = 0; m <= W; m += 400) g.fillRect(m - 9, 0, 18, H); g.fillRect(0, 0, W, 22); g.fillRect(0, H - 22, W, 22);
    } else {   // graphite composite panel
      g.fillStyle = '#34363b'; g.fillRect(0, 0, W, H);
      for (var px = 0; px < W; px += 400) for (var py = 0; py < H; py += 400) { var pg = g.createLinearGradient(px, py, px + 400, py + 400); pg.addColorStop(0, 'rgba(255,255,255,0.05)'); pg.addColorStop(1, 'rgba(0,0,0,0.08)'); g.fillStyle = pg; g.fillRect(px + 3, py + 3, 394, 394); }
      g.fillStyle = '#1f2024'; for (var sx = 0; sx <= W; sx += 400) g.fillRect(sx - 3, 0, 6, H); for (var sy = 0; sy <= H; sy += 400) g.fillRect(0, sy - 3, W, 6);
    }
    WALL[kind] = c; return c;
  }

  function Preview(root) {
    var cv = root.querySelector('[data-pv-cv]'), g = cv.getContext('2d');
    var I18N = {}; try { I18N = JSON.parse(root.getAttribute('data-pv-i18n') || '{}'); } catch (e) {}
    var S = { text: '', light: root.dataset.light || 'frontlit', face: '#F4F4F2', led: '#FFD49A', ret: '#1C1D20', wall: 'brick', h: 400, size: 62, x: 0.5, y: 0.45, night: false, I: 0, photo: null };
    var self = this, timers = [];
    var $ = function (s) { return root.querySelector(s); };
    var txt = $('[data-pv-text]'), lightSel = $('[data-pv-light]'), size = $('[data-pv-size]'), hin = $('[data-pv-h]'), sw = $('[data-pv-night]'), scale = $('[data-pv-scale]'), photoIn = $('[data-pv-photo]');
    function val(name) { var r = root.querySelector('input[name="' + name + '"]:checked'); return r ? r.value : ''; }
    function lab(name) { var r = root.querySelector('input[name="' + name + '"]:checked'); return r ? (r.getAttribute('aria-label') || r.parentNode.textContent).trim() : ''; }
    function read() {
      S.text = (txt.value || '').trim() || I18N.sample || 'SIGN'; S.light = lightSel.value; S.size = +size.value; S.h = Math.max(20, Math.min(3000, parseInt(hin.value, 10) || 400));
      S.face = val(root.dataset.n + 'face') || S.face; S.led = val(root.dataset.n + 'led') || S.led; S.ret = val(root.dataset.n + 'ret') || S.ret; S.wall = val(root.dataset.n + 'wall') || S.wall;
      root.classList.toggle('pv-box', S.light === 'lightbox'); root.dataset.mode = S.light;
    }

    /* ---------- drawing ---------- */
    function fontPx() {
      g.font = '600 100px ' + FONT; var w100 = g.measureText(S.text).width || 100;
      var px = (W * S.size / 100) / w100 * 100; return Math.max(24, Math.min(px, H * (S.light === 'lightbox' ? 0.36 : 0.46)));
    }
    function background() {
      if (S.wall === 'photo' && S.photo) {
        g.fillStyle = '#1C1D20'; g.fillRect(0, 0, W, H);
        var im = S.photo, k = Math.min(W / im.naturalWidth, H / im.naturalHeight), iw = im.naturalWidth * k, ih = im.naturalHeight * k;   // whole photo, never cropped
        g.drawImage(im, (W - iw) / 2, (H - ih) / 2, iw, ih);
      } else g.drawImage(wall(S.wall === 'photo' ? 'plaster' : S.wall), 0, 0);
    }
    function person(capPx) {
      if (S.wall === 'photo') return '';
      var ph = capPx * 1800 / S.h; if (ph < 46 || ph > H * 0.92) return 'hidden';
      var x = 70, base = H - 18, u = ph / 18;
      g.save(); g.fillStyle = S.night ? 'rgba(10,11,14,0.85)' : 'rgba(28,29,32,0.55)';
      g.beginPath(); g.arc(x + 2.2 * u, base - 16.6 * u, 1.35 * u, 0, Math.PI * 2); g.fill();
      g.beginPath(); g.moveTo(x, base - 14.6 * u); g.lineTo(x + 4.4 * u, base - 14.6 * u); g.lineTo(x + 4.9 * u, base - 8 * u); g.lineTo(x + 3.9 * u, base - 8 * u); g.lineTo(x + 3.8 * u, base); g.lineTo(x + 2.6 * u, base); g.lineTo(x + 2.2 * u, base - 6.5 * u);
      g.lineTo(x + 1.8 * u, base); g.lineTo(x + 0.6 * u, base); g.lineTo(x + 0.5 * u, base - 8 * u); g.lineTo(x - 0.5 * u, base - 8 * u); g.closePath(); g.fill(); g.restore();
      return 'shown';
    }
    function draw() {
      var I = S.I, lit = I > 0.001, L = S.light, fp = fontPx(), cap = fp * 0.7;
      var face = hex(S.face), led = hex(S.led), ret = hex(S.ret);
      var emit = isWhite(face) ? led : mix(face, [255, 255, 255], 0.25);   // what a lit acrylic face shows
      g.save(); g.clearRect(0, 0, W, H); background();
      var cx = S.x * W, cy = S.y * H;
      if (S.night) { g.fillStyle = 'rgba(6,8,14,0.80)'; g.fillRect(0, 0, W, H); }
      g.font = '600 ' + fp + 'px ' + FONT; g.textAlign = 'center'; g.textBaseline = 'alphabetic';
      var tw = g.measureText(S.text).width, by = cy + cap / 2, depth = L === 'flat' ? Math.max(2, fp * 0.012) : fp * 0.075;
      var lightsUp = { frontlit: 1, front_corona: 1, front_side: 1, neon: 1, lightbox: 1 }[L], halo = L === 'corona' || L === 'front_corona';
      // light spill on the wall
      if (S.night && lit && L !== 'unlit' && L !== 'flat') {
        var sp = g.createRadialGradient(cx, cy, 10, cx, cy, Math.max(tw, fp) * 0.85); sp.addColorStop(0, rgb(halo && !lightsUp ? led : emit, 0.30 * I)); sp.addColorStop(1, rgb(emit, 0));
        g.globalCompositeOperation = 'lighter'; g.fillStyle = sp; g.fillRect(0, 0, W, H); g.globalCompositeOperation = 'source-over';
      }
      if (S.night && (L === 'unlit' || L === 'flat')) {   // gooseneck spot lights from above
        var gl = g.createRadialGradient(cx, cy - fp * 1.2, 20, cx, cy, Math.max(tw * 0.75, fp * 1.4)); gl.addColorStop(0, 'rgba(255,236,200,' + (0.55 * Math.max(I, 0.15)) + ')'); gl.addColorStop(1, 'rgba(255,236,200,0)');
        g.globalCompositeOperation = 'lighter'; g.fillStyle = gl; g.fillRect(0, 0, W, H); g.globalCompositeOperation = 'source-over';
      }
      var dim = S.night ? (L === 'unlit' || L === 'flat' ? 0.45 - 0.25 * I : 0.72) : 0;   // how dark unlit parts look at night
      if (L === 'lightbox') {
        var pad = fp * 0.42, bw = tw + pad * 2, bh = fp * 1.25, bx = cx - bw / 2, bt = cy - bh / 2;
        for (var i = Math.round(depth); i >= 1; i--) { g.fillStyle = rgb(shade(ret, dim + 0.18 * i / depth)); g.fillRect(bx + i * 0.5, bt + i * 0.8, bw, bh); }
        var boxFace = S.night ? mix([22, 22, 24], mix(led, [255, 255, 255], 0.55), I) : [242, 242, 239];
        g.save(); if (S.night && lit) { g.shadowColor = rgb(led, 0.85 * I); g.shadowBlur = fp * 0.6 * I; }
        g.fillStyle = rgb(boxFace); g.fillRect(bx, bt, bw, bh); g.restore();
        g.strokeStyle = rgb(shade(ret, dim)); g.lineWidth = fp * 0.05; g.strokeRect(bx, bt, bw, bh);
        var film = isWhite(face) ? [28, 29, 32] : face;
        g.fillStyle = rgb(S.night ? mix(shade(film, 0.7), film, I) : film); g.fillText(S.text, cx, by);
      } else {
        if (halo && S.night && lit) {   // indirect light: the wall behind the letters glows
          g.save(); g.filter = 'blur(' + Math.round(fp * 0.09) + 'px)'; g.globalAlpha = I; g.strokeStyle = rgb(led); g.fillStyle = rgb(led); g.lineWidth = fp * 0.16; g.lineJoin = 'round';
          g.strokeText(S.text, cx, by); g.fillText(S.text, cx, by); g.filter = 'blur(' + Math.round(fp * 0.03) + 'px)'; g.lineWidth = fp * 0.05; g.strokeText(S.text, cx, by); g.restore();
        }
        var retLit = L === 'front_side' && S.night ? mix(shade(ret, 0.6), mix(ret, emit, 0.65), I) : null;
        for (var j = Math.round(depth); j >= 1; j--) { g.fillStyle = rgb(retLit ? shade(retLit, 0.15 * j / depth) : shade(ret, dim + 0.22 * j / depth)); g.fillText(S.text, cx + j * 0.45, by + j * 0.75); }
        if (retLit && lit) { g.save(); g.shadowColor = rgb(emit, 0.8 * I); g.shadowBlur = fp * 0.25 * I; g.fillStyle = rgb(retLit); g.fillText(S.text, cx + depth * 0.45, by + depth * 0.75); g.restore(); }
        if (L === 'neon') {
          var tube = isWhite(face) ? led : face;
          g.fillStyle = rgb(shade([30, 31, 34], S.night ? 0.5 : 0)); g.fillText(S.text, cx, by);
          g.save(); g.lineJoin = 'round'; g.lineWidth = fp * 0.045;
          if (S.night && lit) { g.shadowColor = rgb(tube, 0.95 * I); g.shadowBlur = fp * 0.35 * I; g.strokeStyle = rgb(mix(shade(tube, 0.6), mix(tube, [255, 255, 255], 0.35), I)); g.strokeText(S.text, cx, by); g.shadowBlur = fp * 0.12 * I; }
          else g.strokeStyle = rgb(mix(tube, [235, 235, 232], 0.65));
          g.strokeText(S.text, cx, by); g.restore();
        } else {
          var faceCol = lightsUp ? (S.night ? mix(shade(face, 0.78), emit, I) : face) : (L === 'corona' ? shade(face, S.night ? 0.82 : 0) : shade(face, dim));
          g.save(); if (lightsUp && S.night && lit) { g.shadowColor = rgb(emit, 0.9 * I); g.shadowBlur = fp * 0.28 * I; }
          g.fillStyle = rgb(faceCol); g.fillText(S.text, cx, by);
          if (lightsUp && S.night && lit) { g.shadowBlur = fp * 0.08 * I; g.fillText(S.text, cx, by); }
          g.restore();
          if (!S.night && lightsUp) { g.fillStyle = 'rgba(255,255,255,0.10)'; g.fillText(S.text, cx - 1, by - 1); }
        }
      }
      var ps = person(cap); g.restore();
      if (scale) scale.textContent = (I18N.scale || '').replace('{h}', S.h).replace('{m}', Math.max(1, Math.round(S.h * 0.33))) + (ps === 'shown' ? ' · ' + (I18N.figure || '') : '');
      cv.setAttribute('aria-label', (I18N.alt || '').replace('{text}', S.text).replace('{light}', lightSel.options[lightSel.selectedIndex].text).replace('{face}', lab(root.dataset.n + 'face')).replace('{led}', lab(root.dataset.n + 'led')).replace('{wall}', lab(root.dataset.n + 'wall')).replace('{state}', S.night ? I18N.st_on : I18N.st_off));
    }
    this.draw = function () { read(); draw(); };

    /* ---------- switch on / off ---------- */
    function clear() { timers.forEach(clearTimeout); timers = []; }
    function setNight(on) {
      clear(); S.night = on; sw.setAttribute('aria-pressed', String(on)); root.classList.toggle('pv-night', on);
      sw.querySelector('[data-pv-sw-t]').textContent = on ? I18N.off : I18N.on; read();
      var mo = MOTION();
      if (!on) { S.I = 0; draw(); return; }
      if (mo === 'off') { S.I = 1; draw(); return; }
      if (mo === 'calm') { var t0 = null; var step = function (ts) { if (!S.night) return; t0 = t0 || ts; S.I = Math.min(1, (ts - t0) / 350); draw(); if (S.I < 1) requestAnimationFrame(step); }; requestAnimationFrame(step); return; }
      // full: a real sign striking — dark, flicker, catch, full (≈ 0.9 s, once)
      [[0, 0], [90, 0.55], [150, 0.06], [260, 0.8], [320, 0.25], [430, 0.95], [520, 0.7], [640, 1]].forEach(function (k) { timers.push(setTimeout(function () { if (S.night) { S.I = k[1]; draw(); } }, k[0])); });
    }
    sw.addEventListener('click', function () { setNight(!S.night); });

    /* ---------- controls ---------- */
    root.addEventListener('input', function (e) { if (e.target === photoIn) return; self.draw(); });
    root.addEventListener('change', function (e) {
      if (e.target === photoIn) return;
      if (e.target.name === root.dataset.n + 'wall' && e.target.value === 'photo' && !S.photo) { photoIn.click(); }
      self.draw();
    });
    if (photoIn) photoIn.addEventListener('change', function () {
      var f = photoIn.files && photoIn.files[0]; if (!f || !/^image\//.test(f.type)) return;
      var r = new FileReader(); r.onload = function () { var im = new Image(); im.onload = function () { S.photo = im; var pr = root.querySelector('input[name="' + root.dataset.n + 'wall"][value="photo"]'); if (pr) pr.checked = true; self.draw(); }; im.src = r.result; }; r.readAsDataURL(f);
    });
    // move the sign: drag, or arrow keys on the focused canvas
    var drag = null;
    cv.addEventListener('pointerdown', function (e) { drag = { x: e.clientX, y: e.clientY, sx: S.x, sy: S.y }; cv.setPointerCapture(e.pointerId); cv.classList.add('is-drag'); });
    cv.addEventListener('pointermove', function (e) { if (!drag) return; var r = cv.getBoundingClientRect(); S.x = Math.max(0.05, Math.min(0.95, drag.sx + (e.clientX - drag.x) / r.width)); S.y = Math.max(0.1, Math.min(0.9, drag.sy + (e.clientY - drag.y) / r.height)); draw(); });
    var end = function () { drag = null; cv.classList.remove('is-drag'); }; cv.addEventListener('pointerup', end); cv.addEventListener('pointercancel', end);
    cv.addEventListener('keydown', function (e) {
      var k = { ArrowLeft: [-0.02, 0], ArrowRight: [0.02, 0], ArrowUp: [0, -0.03], ArrowDown: [0, 0.03] }[e.key]; if (!k) return; e.preventDefault();
      S.x = Math.max(0.05, Math.min(0.95, S.x + k[0])); S.y = Math.max(0.1, Math.min(0.9, S.y + k[1])); draw();
    });

    /* ---------- export ---------- */
    this.summary = function () { read(); return (I18N.summary || '').replace('{light}', lightSel.options[lightSel.selectedIndex].text).replace('{face}', lab(root.dataset.n + 'face')).replace('{led}', lab(root.dataset.n + 'led')).replace('{ret}', lab(root.dataset.n + 'ret')).replace('{h}', S.h); };
    this.state = function () { read(); return S; };
    this.jpeg = function () { draw(); return cv.toDataURL('image/jpeg', 0.9); };
    this.fileName = function () { return 'sign-preview-' + (S.text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 30) || 'rf') + (S.night ? '-night' : '-day') + '.jpg'; };
    this.set = function (o) {
      if (o.text != null) txt.value = o.text; if (o.light && lightSel.querySelector('option[value="' + o.light + '"]')) lightSel.value = o.light; if (o.h) hin.value = o.h;
      this.draw();
    };
    this.night = setNight;
    var dl = $('[data-pv-png]');
    if (dl) dl.addEventListener('click', function () { var a = d.createElement('a'); a.href = self.jpeg(); a.download = self.fileName(); d.body.appendChild(a); a.click(); a.remove(); });
    var req = $('[data-pv-req]');
    if (req) req.addEventListener('click', function () {
      try { var s = self.state(); sessionStorage.setItem('rf_pv', JSON.stringify({ slug: root.dataset.slug || '', text: (txt.value || '').trim(), h: s.h, summary: self.summary(), img: self.jpeg(), name: self.fileName() })); } catch (e) {}
    });
    (d.fonts && d.fonts.load ? d.fonts.load('600 100px "RF Condensed"', 'ABCabc') : Promise.resolve()).then(function () { self.draw(); }, function () { self.draw(); });
    this.draw();
    // first view: switch on once when it scrolls into view (full motion only)
    if (MOTION() === 'full' && 'IntersectionObserver' in window && root.hasAttribute('data-pv-autostart')) {
      var io = new IntersectionObserver(function (es) { if (es[0].isIntersecting) { io.disconnect(); setTimeout(function () { if (!S.night) setNight(true); }, 500); } }, { threshold: 0.55 }); io.observe(cv);
    }
  }

  var all = [];
  d.querySelectorAll('[data-pv]').forEach(function (el) { el.classList.add('pv-on'); var p = new Preview(el); el._pv = p; all.push(p); });

  /* ---------- client portal: open the preview for one item, attach the picture to the request ---------- */
  var dlg = d.querySelector('[data-pv-dlg]'), form = d.querySelector('[data-reqform]');
  if (!form) return;
  function attach(dataUrl, name) {
    var fin = form.querySelector('input[type=file][data-files]'); if (!fin || !window.DataTransfer) return false;
    var bin = atob(dataUrl.split(',')[1]), u8 = new Uint8Array(bin.length); for (var i = 0; i < bin.length; i++) u8[i] = bin.charCodeAt(i);
    var dt = new DataTransfer(); Array.prototype.forEach.call(fin.files || [], function (f) { if (f.name !== name) dt.items.add(f); });
    dt.items.add(new File([u8], name, { type: 'image/jpeg' })); fin.files = dt.files; fin.dispatchEvent(new Event('change', { bubbles: true })); return true;
  }
  function fillItem(it, text, h, summary) {
    if (!it) return;
    var c = it.querySelector('[name$="[content]"]'), hh = it.querySelector('[name$="[height_mm]"]'), n = it.querySelector('[name$="[notes]"]');
    if (c && !c.value.trim() && text) c.value = text;
    if (hh && !hh.value && h) { hh.value = h; hh.dispatchEvent(new Event('input', { bubbles: true })); }
    if (n && summary && n.value.indexOf(summary) < 0) n.value = (n.value.trim() ? n.value.trim() + '\n' : '') + summary;
    form.dispatchEvent(new Event('input', { bubbles: true }));
  }
  var note = form.querySelector('[data-pv-note]');
  // coming from a profile page with "Request this sign"
  if (/[?&]pv=1/.test(location.search)) {
    try {
      var pv = JSON.parse(sessionStorage.getItem('rf_pv') || 'null');
      if (pv && pv.img && attach(pv.img, pv.name)) {
        var first = form.querySelector('[data-item]'); fillItem(first, pv.text, pv.h, pv.summary);
        if (note) { note.hidden = false; var im = note.querySelector('img'); if (im) im.src = pv.img; }
        sessionStorage.removeItem('rf_pv');
      }
    } catch (e) {}
  }
  if (!dlg) return;
  var P = dlg.querySelector('[data-pv]')._pv, cur = null, map = {}; try { map = JSON.parse(form.getAttribute('data-map') || '{}'); } catch (e) {}
  form.addEventListener('click', function (e) {
    var b = e.target.closest('[data-pv-open]'); if (!b) return;
    cur = b.closest('[data-item]'); var slug = cur.querySelector('[data-profile-pick]').value, m = map[slug];
    var c = cur.querySelector('[name$="[content]"]').value.trim(), h = parseInt(cur.querySelector('[name$="[height_mm]"]').value, 10);
    dlg.showModal(); P.set({ text: c, light: m ? (m.g === 'lightbox' ? 'lightbox' : m.l) : null, h: h || 400 });
    dlg.querySelector('[data-pv-text]').focus();
  });
  dlg.querySelector('[data-pv-close]').addEventListener('click', function () { dlg.close(); });
  dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
  dlg.querySelector('[data-pv-attach]').addEventListener('click', function () {
    var s = P.state(), ok = attach(P.jpeg(), P.fileName());
    fillItem(cur, (dlg.querySelector('[data-pv-text]').value || '').trim(), s.h, P.summary());
    dlg.close();
    var t = form.querySelector('[data-pv-done]'); if (t) { t.hidden = !ok; t.classList.remove('pv-flash'); void t.offsetWidth; t.classList.add('pv-flash'); }
    if (cur) { var bt = cur.querySelector('[data-pv-open]'); bt && bt.focus(); }
  });
})();
