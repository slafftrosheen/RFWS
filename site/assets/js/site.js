/* Reclame Fabriek website · v2.3 (motion: "Switch-on" + "Night shift" + 2.3 FX) — no framework, no tracking. Everything works without JS; this only enhances. */
(function () {
  'use strict';
  var d = document, de = d.documentElement;
  var B = d.body, RV = false, RM = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var T = {}; try { T = JSON.parse(d.body.getAttribute('data-i18n') || '{}'); } catch (e) {}
  de.classList.add('js');

  // header shadow + mobile menu
  var hdr = d.querySelector('[data-hdr]'), btn = d.querySelector('[data-nav-btn]'), nav = d.getElementById('nav');
  var onScroll = function () { hdr && hdr.classList.toggle('is-scrolled', window.scrollY > 8); };
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  if (btn && nav) {
    var setOpen = function (o) { btn.setAttribute('aria-expanded', String(o)); nav.classList.toggle('is-open', o); };
    btn.addEventListener('click', function () { setOpen(btn.getAttribute('aria-expanded') !== 'true'); });
    d.addEventListener('keydown', function (e) { if (e.key === 'Escape' && nav.classList.contains('is-open')) { setOpen(false); btn.focus(); } });
    window.addEventListener('resize', function () { if (window.innerWidth > 1060) setOpen(false); });
  }

  // quiet reveal on scroll
  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var els = d.querySelectorAll('.sec-h, .svc, .pcard, .scard, .tech, .gal li, .split > *, .ccard');
    RV = true;
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { rootMargin: '0px 0px -8% 0px' });
    els.forEach(function (el, i) {
      if (el.getBoundingClientRect().top < window.innerHeight) { setTimeout(function () { el.classList.add('in'); }, 150); return; } // already on screen: never hide it
      el.setAttribute('data-rv', ''); el.style.transitionDelay = ((i % 4) * 60) + 'ms'; io.observe(el);
    });
  }

  if (!RV) d.querySelectorAll('.sec-h, .svc, .tech, .ccard').forEach(function (el) { el.classList.add('in'); });

  /* ================= motion "Switch-on" ================= */
  var MOTION = B.classList.contains('motion-off') ? 'off' : B.classList.contains('motion-calm') ? 'calm' : 'full';
  if (RM) MOTION = 'off';
  var FINE = matchMedia('(hover: hover) and (pointer: fine)').matches;

  // hero headline strikes letter by letter
  var ig = d.querySelector('[data-ignite]');
  if (ig && MOTION === 'full') {
    var txt = ig.textContent; ig.setAttribute('aria-label', txt); ig.textContent = '';
    var n = 0;
    txt.split(/(\s+)/).forEach(function (w) {
      if (/^\s+$/.test(w)) { ig.appendChild(d.createTextNode(' ')); return; }
      var wb = d.createElement('span'); wb.style.whiteSpace = 'nowrap'; wb.setAttribute('aria-hidden', 'true');
      Array.prototype.forEach.call(w, function (ch) { var sp = d.createElement('span'); sp.className = 'ig-l'; sp.textContent = ch; sp.style.setProperty('--d', (120 + Math.random() * 520 + n * 28).toFixed(0) + 'ms'); n++; wb.appendChild(sp); });
      ig.appendChild(wb);
    });
    var go = function () { ig.classList.add('go'); };
    (d.fonts && d.fonts.ready ? d.fonts.ready : Promise.resolve()).then(function () { setTimeout(go, 150); });
  }

  // a soft light follows the pointer over dark sections
  if (MOTION === 'full' && FINE) d.querySelectorAll('[data-spot]').forEach(function (s) {
    s.addEventListener('pointermove', function (e) { var r = s.getBoundingClientRect(); s.style.setProperty('--mx', (e.clientX - r.left) + 'px'); s.style.setProperty('--my', (e.clientY - r.top) + 'px'); });
    s.addEventListener('pointerenter', function () { s.classList.add('spot-on'); });
    s.addEventListener('pointerleave', function () { s.classList.remove('spot-on'); });
  });

  // numbers count up once, keeping their own formatting ("6 000 m²", "EU + UK" stays as is)
  if (MOTION !== 'off' && 'IntersectionObserver' in window) {
    var cio = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        if (!e.isIntersecting) return; cio.unobserve(e.target);
        var el = e.target, raw = el.textContent, m = raw.match(/\d[\d\s.,]*/); if (!m) return;
        var digits = m[0].replace(/[^\d]/g, ''), end = parseInt(digits, 10); if (!end) return;
        var start = digits.length === 4 && end > 1900 && end < 2100 ? end - 60 : 0, t0 = null, dur = 1300;
        var fmt = function (v) { var s = String(v); if (/\s/.test(m[0])) s = s.replace(/\B(?=(\d{3})+(?!\d))/g, '\u00a0'); else if (/,/.test(m[0])) s = s.replace(/\B(?=(\d{3})+(?!\d))/g, ','); return raw.replace(m[0].trim(), s); };
        var step = function (ts) { if (!t0) t0 = ts; var k = Math.min(1, (ts - t0) / dur), ease = 1 - Math.pow(1 - k, 3); el.textContent = fmt(Math.round(start + (end - start) * ease)); if (k < 1) requestAnimationFrame(step); else el.textContent = raw; };
        requestAnimationFrame(step);
      });
    }, { threshold: .6 });
    d.querySelectorAll('[data-count]').forEach(function (el) { cio.observe(el); });
  }

  // Lottie: load the small player only when an animation is about to be seen
  var LT = d.querySelectorAll('[data-lottie]'), ltLoad = null;
  function player() {
    if (window.lottie) return Promise.resolve(window.lottie);
    return ltLoad || (ltLoad = new Promise(function (res, rej) { var sc = d.createElement('script'); sc.src = '/assets/js/lottie_light.min.js'; sc.onload = function () { res(window.lottie); }; sc.onerror = rej; d.head.appendChild(sc); }));
  }
  function startLottie(el) {
    if (el._lt) return;
    el._lt = 1;
    player().then(function (L) {
      var a = L.loadAnimation({ container: el, renderer: 'svg', loop: false, autoplay: false, path: el.getAttribute('data-lottie'), rendererSettings: { preserveAspectRatio: 'xMidYMid meet', progressiveLoad: true } });
      el._a = a;
      a.addEventListener('DOMLoaded', function () {
        el.classList.add('is-on');
        var last = a.totalFrames - 1, loopAt = el.hasAttribute('data-loop') ? parseInt(el.getAttribute('data-loop'), 10) : null;
        if (MOTION === 'off') { a.goToAndStop(last, true); return; }
        a.play();
        if (loopAt !== null && MOTION === 'full') a.addEventListener('complete', function () { a.playSegments([loopAt, last], true); a.loop = true; });
        if (el.hasAttribute('data-hover')) { var host = el.closest('.svc, .proc-step') || el; host.addEventListener('mouseenter', function () { a.goToAndPlay(0, true); }); }
      });
    }).catch(function () {});
  }
  if (LT.length && 'IntersectionObserver' in window) {
    var lio = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        var el = e.target;
        if (e.isIntersecting) { startLottie(el); if (el._a && el._a.loop && MOTION === 'full') el._a.play(); }
        else if (el._a && el._a.loop) el._a.pause();   // looping animations rest when off screen
      });
    }, { rootMargin: '120px 0px' });
    LT.forEach(function (el) { lio.observe(el); });
  }

  // product profile filter
  var f = d.querySelector('[data-filter]');
  if (f) {
    var cards = f.querySelectorAll('.pcard'), cnt = f.querySelector('[data-filter-count]');
    var gBtns = f.querySelectorAll('[data-filter-group]'), kBtns = f.querySelectorAll('[data-filter-kind]');
    var state = { group: 'all', kind: null };
    var apply = function () {
      var n = 0, seen = { letters: 0, lightbox: 0 };
      cards.forEach(function (c) {
        var ok = (state.group === 'all' || c.dataset.group === state.group) && (!state.kind || c.dataset.kind === state.kind);
        c.hidden = !ok; if (ok) { n++; seen[c.dataset.group]++; }
      });
      f.querySelectorAll('[data-grp-h]').forEach(function (h) { h.hidden = !seen[h.dataset.grpH]; });
      gBtns.forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.filterGroup === state.group)); });
      kBtns.forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.filterKind === state.kind)); });
      if (cnt) cnt.textContent = n === cards.length ? '' : (T.showing || '{n} / {m}').replace('{n}', n).replace('{m}', cards.length);
    };
    gBtns.forEach(function (b) { b.addEventListener('click', function () { state.group = b.dataset.filterGroup; if (state.group === 'lightbox') state.kind = null; apply(); }); });
    kBtns.forEach(function (b) { b.addEventListener('click', function () { state.kind = state.kind === b.dataset.filterKind ? null : b.dataset.filterKind; if (state.kind) state.group = 'letters'; apply(); }); });
  }

  // click-to-load YouTube (privacy: nothing loads from Google until play)
  d.querySelectorAll('[data-yt-play]').forEach(function (b) {
    b.addEventListener('click', function () {
      var v = b.closest('[data-yt]'), id = v.dataset.yt, ifr = d.createElement('iframe');
      ifr.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(id) + '?autoplay=1&rel=0';
      ifr.title = b.getAttribute('aria-label') || 'Video';
      ifr.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen'; ifr.allowFullscreen = true;
      v.appendChild(ifr); v.classList.add('is-on'); ifr.focus();
    });
  });

  // click-to-load map
  d.querySelectorAll('[data-map-load]').forEach(function (b) {
    b.addEventListener('click', function () {
      var m = b.closest('[data-map]'), ifr = d.createElement('iframe');
      ifr.src = 'https://maps.google.com/maps?q=' + encodeURIComponent(T.mapsQ || 'Daugavpils') + '&t=m&z=14&output=embed';
      ifr.title = T.mapTitle || 'Map'; ifr.loading = 'lazy';
      m.innerHTML = ''; m.appendChild(ifr);
    });
  });

  // gallery lightbox
  var lb = d.querySelector('[data-lb]');
  if (lb && typeof lb.showModal === 'function') {
    var li = lb.querySelector('[data-lb-img]'), lc = lb.querySelector('[data-lb-cap]'), list = [], idx = 0, opener = null;
    var show = function (i) {
      idx = (i + list.length) % list.length; var a = list[idx];
      li.src = a.getAttribute('href'); li.alt = a.dataset.cap || '';
      lc.textContent = (a.dataset.cap ? a.dataset.cap + ' · ' : '') + (idx + 1) + ' / ' + list.length;
    };
    d.querySelectorAll('[data-lb-group]').forEach(function (g) {
      var items = Array.prototype.slice.call(g.querySelectorAll('[data-lb-item]'));
      items.forEach(function (a, i) {
        a.addEventListener('click', function (e) { e.preventDefault(); opener = a; list = items; show(i); lb.showModal(); });
      });
    });
    lb.querySelector('[data-lb-close]').addEventListener('click', function () { lb.close(); });
    lb.querySelector('[data-lb-prev]').addEventListener('click', function () { show(idx - 1); });
    lb.querySelector('[data-lb-next]').addEventListener('click', function () { show(idx + 1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) lb.close(); });
    lb.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') show(idx - 1); if (e.key === 'ArrowRight') show(idx + 1); });
    lb.addEventListener('close', function () { li.removeAttribute('src'); opener && opener.focus(); });
  }

  // contact: preselect profile from ?profile=
  var sel = d.querySelector('[data-profile]'), q = new URLSearchParams(location.search).get('profile');
  if (sel && q) Array.prototype.forEach.call(sel.options, function (o) { if (o.value === q) sel.value = q; });

  // forms: send with fetch; if the server has no handler, fall back to the visitor's email app
  d.querySelectorAll('[data-form]').forEach(function (form) {
    var msg = form.querySelector('[data-form-msg]');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var b = form.querySelector('[type=submit]'); b.disabled = true; msg.className = 'form-msg'; msg.textContent = T.sending || '…';
      var data = new FormData(form);
      fetch(form.action, { method: 'POST', body: data, headers: { 'Accept': 'application/json' } })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(function (j) {
          if (!j.ok) throw new Error('fail');
          msg.classList.add('ok');
          msg.textContent = data.get('type') === 'subscribe' ? T.subOk : T.ok;
          var ok = d.createElement('span'); ok.className = 'lt lt-ok'; ok.setAttribute('aria-hidden', 'true'); ok.setAttribute('data-lottie', '/assets/lottie/rf_success.json'); msg.insertBefore(ok, msg.firstChild); startLottie(ok);
          form.reset();
        })
        .catch(function () {
          var body = ['name', 'company', 'email', 'phone', 'profile', 'message'].map(function (k) { return data.get(k) ? k + ': ' + data.get(k) : ''; }).filter(Boolean).join('\n');
          var subj = data.get('type') === 'subscribe' ? 'Newsletter: subscribe' : 'Website request';
          msg.classList.add('err');
          msg.textContent = (T.err || '') + ' ';
          var a = d.createElement('a'); a.href = 'mailto:' + (T.mailTo || '') + '?subject=' + encodeURIComponent(subj) + '&body=' + encodeURIComponent(body); a.textContent = T.errLink || 'Email'; msg.appendChild(a);
        })
        .then(function () { b.disabled = false; });
    });
  });

  /* ================= 2.2 · motion "Night shift" ================= */
  var PAUSE_KEY = 'rf_motion_paused', paused = false;
  try { paused = localStorage.getItem(PAUSE_KEY) === '1'; } catch (e) {}
  var moBtns = d.querySelectorAll('[data-motion-toggle]');
  function setPaused(p) {
    paused = p; B.classList.toggle('paused', p);
    try { localStorage.setItem(PAUSE_KEY, p ? '1' : '0'); } catch (e) {}
    moBtns.forEach(function (b) { b.setAttribute('aria-pressed', String(p)); var t = b.querySelector('.mo-t'); if (t) t.textContent = p ? t.dataset.on : t.dataset.off; });
    d.querySelectorAll('[data-lottie]').forEach(function (el) { if (el._a && el._a.loop) { if (p) el._a.pause(); else el._a.play(); } });
  }
  moBtns.forEach(function (b) { b.addEventListener('click', function () { setPaused(!paused); }); });
  if (paused) setPaused(true);

  // hero: real project photos cross-fade every 7 s (full motion only; slides load after the page)
  var sl = d.querySelector('[data-slides]');
  if (sl && MOTION === 'full') {
    var slides = sl.querySelectorAll('.slide'), si = 0;
    var hydrate = function (s) {
      var ph = s.querySelector('.slide-later'); if (!ph) return;
      var im = d.createElement('img'); im.alt = ''; im.decoding = 'async'; im.width = ph.dataset.w; im.height = ph.dataset.h; im.sizes = ph.dataset.sizes; im.srcset = ph.dataset.srcset; im.src = ph.dataset.src;
      ph.parentNode.replaceChild(im, ph);
    };
    var startSlides = function () {
      hydrate(slides[1]);
      setInterval(function () {
        if (paused || d.hidden) return;
        var cur = slides[si]; si = (si + 1) % slides.length; var nx = slides[si];
        hydrate(nx); hydrate(slides[(si + 1) % slides.length]);
        var go = function () { cur.classList.remove('is-on'); nx.classList.add('is-on'); };
        var im = nx.querySelector('img'); if (im && !im.complete) im.addEventListener('load', go, { once: true }); else go();
      }, 7000);
    };
    if (d.readyState === 'complete') setTimeout(startSlides, 1200); else window.addEventListener('load', function () { setTimeout(startSlides, 1200); });
  }

  // profile rail: arrows, progress bar, group tabs
  d.querySelectorAll('[data-rail]').forEach(function (r) {
    var list = r.querySelector('[data-rail-list]'), prev = r.querySelector('[data-rail-prev]'), next = r.querySelector('[data-rail-next]'), bar = r.querySelector('[data-rail-bar]');
    var tabs = r.querySelectorAll('[data-rail-go]');
    var upd = function () {
      var max = list.scrollWidth - list.clientWidth, x = list.scrollLeft;
      prev.disabled = x < 4; next.disabled = x > max - 4;
      if (bar) { var w = Math.max(8, list.clientWidth / list.scrollWidth * 100); bar.parentNode.style.setProperty('--rw', w + '%'); bar.style.setProperty('--rx', (max ? x / max * (100 / w * 100 - 100) : 0) + '%'); }
      var first = null; Array.prototype.some.call(list.children, function (li) { if (li.offsetLeft + li.offsetWidth / 2 >= x) { first = li; return true; } });
      if (first) tabs.forEach(function (t) { t.setAttribute('aria-pressed', String(first.dataset.group === t.dataset.railGo)); });
    };
    var page = function (dir) { list.scrollBy({ left: dir * list.clientWidth * .9, behavior: MOTION === 'off' ? 'auto' : 'smooth' }); };
    prev.addEventListener('click', function () { page(-1); }); next.addEventListener('click', function () { page(1); });
    tabs.forEach(function (t) { t.addEventListener('click', function () { var li = list.querySelector('[data-group="' + t.dataset.railGo + '"]'); if (li) list.scrollTo({ left: li.offsetLeft - list.offsetLeft, behavior: MOTION === 'off' ? 'auto' : 'smooth' }); }); });
    list.addEventListener('scroll', function () { requestAnimationFrame(upd); }, { passive: true });
    window.addEventListener('resize', upd); upd();
    if ('IntersectionObserver' in window) new IntersectionObserver(function (es, o) { if (es[0].isIntersecting) { upd(); o.disconnect(); } }).observe(r);
  });

  // film strip: speed follows length (about 32 px per second), measured once it is near the screen
  d.querySelectorAll('[data-film]').forEach(function (f) {
    // a quiet copy of each row makes the loop seamless; it is made only when the strip is near the screen
    // (copied images would otherwise load at once, ignoring loading="lazy")
    var dupe = function () {
      if (MOTION !== 'full' || f._dup) return; f._dup = 1;
      f.querySelectorAll('.film-track').forEach(function (t) {
        var run = t.querySelector('.film-run'), dup = run.cloneNode(true);
        dup.classList.add('film-dup'); dup.setAttribute('aria-hidden', 'true');
        dup.querySelectorAll('a').forEach(function (a) { var im = a.querySelector('img'); im.alt = ''; a.parentNode.replaceChild(im, a); });
        t.appendChild(dup);
      });
    };
    var set = function () { dupe(); f.querySelectorAll('.film-track').forEach(function (t) { var run = t.querySelector('.film-run'); if (run && run.scrollWidth) t.style.setProperty('--dur', Math.round(run.scrollWidth / 32) + 's'); }); };
    if ('IntersectionObserver' in window) new IntersectionObserver(function (es, o) { if (es[0].isIntersecting) { set(); o.disconnect(); } }, { rootMargin: '300px 0px' }).observe(f); else set();
  });

  // anatomy of a box letter: comes apart and goes back together as you scroll, then switches on
  d.querySelectorAll('[data-anat]').forEach(function (a) {
    var btn = a.querySelector('[data-anat-toggle]'), manual = false, lit = a.hasAttribute('data-lit');
    var apply = function (ex) {
      a.style.setProperty('--ex', ex.toFixed(3));
      a.classList.toggle('apart', ex > .5);
      a.classList.toggle('is-lit', lit && ex < .02);
      btn.setAttribute('aria-pressed', String(ex > .5)); btn.textContent = ex > .5 ? btn.dataset.on : btn.dataset.off;
    };
    btn.addEventListener('click', function () { manual = true; a.classList.remove('scrub'); apply(a.classList.contains('apart') ? 0 : 1); });
    if (MOTION !== 'full') { apply(0); return; }       // calm / off: assembled and lit, the button still works
    apply(0); a.classList.add('scrub');
    var tick = function () {
      if (manual) return;
      var r = a.getBoundingClientRect(), vh = window.innerHeight;
      if (r.bottom < 0 || r.top > vh) return;
      var p = Math.min(1, Math.max(0, (vh - r.top) / (vh + r.height)));
      var ex = p < .18 ? 0 : p < .38 ? (p - .18) / .2 : p < .52 ? 1 : p < .7 ? 1 - (p - .52) / .18 : 0;
      ex = ex * ex * (3 - 2 * ex);
      apply(ex);
    };
    window.addEventListener('scroll', function () { requestAnimationFrame(tick); }, { passive: true }); tick();
  });

  // research: the line between technologies fills as you read
  var wire = d.querySelector('[data-wire]');
  if (wire && MOTION === 'full') {
    var wt = function () { var r = wire.getBoundingClientRect(), vh = window.innerHeight; var p = Math.min(1, Math.max(0, (vh * .85 - r.top) / (r.height + vh * .3))); wire.style.setProperty('--wire', (p * 100).toFixed(1) + '%'); };
    window.addEventListener('scroll', function () { requestAnimationFrame(wt); }, { passive: true }); wt();
  }

  /* ================= request basket: pick profiles on the site, take them into the client portal ================= */
  var BK = 'rf_basket', basket = [];
  try { basket = JSON.parse(localStorage.getItem(BK) || '[]').filter(function (x) { return /^[a-z0-9-]{1,90}$/.test(x); }); } catch (e) { basket = []; }
  var bn = d.querySelector('[data-basket-n]'), bl = d.querySelector('[data-basket-link]');
  function drawBasket(bump) {
    try { localStorage.setItem(BK, JSON.stringify(basket)); } catch (e) {}
    if (bn) { bn.hidden = !basket.length; bn.textContent = basket.length; if (bump && MOTION !== 'off') { bn.classList.remove('bump'); void bn.offsetWidth; bn.classList.add('bump'); } }
    if (bl && T.portal) bl.href = basket.length ? T.portal + 'requests/new/?p=' + encodeURIComponent(basket.join(',')) : T.portal;
    d.querySelectorAll('[data-basket-add]').forEach(function (b) {
      var on = basket.indexOf(b.dataset.basketAdd) > -1; b.setAttribute('aria-pressed', String(on));
      var s = b.querySelector('span'); if (s) s.textContent = on ? (T.basketIn || '✓') : (T.basketAdd || '+');
    });
  }
  d.querySelectorAll('[data-basket-add]').forEach(function (b) {
    b.addEventListener('click', function () {
      var k = b.dataset.basketAdd, i = basket.indexOf(k);
      if (i > -1) basket.splice(i, 1); else basket.push(k);
      drawBasket(i < 0);
      d.dispatchEvent(new CustomEvent('rf:basket', { detail: { added: i < 0, n: basket.length, href: bl ? bl.href : '' } }));
    });
  });
  if (d.querySelector('[data-basket-clear]')) { basket = []; }
  drawBasket(false);
})();

/* =====================================================================================================
   2.3 · header controls (theme, language), language hint, search palette, toasts, back-to-top
   ===================================================================================================== */
(function () {
  'use strict';
  var d = document, w = window, de = d.documentElement, B = d.body;
  var T = {}; try { T = JSON.parse(B.getAttribute('data-i18n') || '{}'); } catch (e) {}
  var RM = w.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var MOTION = B.classList.contains('motion-off') ? 'off' : (B.classList.contains('motion-calm') ? 'calm' : 'full');
  var store = function (k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } };

  /* ---------- popovers (language, theme): one open at a time, Esc / outside click closes, arrows move ---------- */
  var pops = [].slice.call(d.querySelectorAll('[data-pop]'));
  function closeAll(except, focusBtn) {
    pops.forEach(function (p) {
      if (p === except) return;
      var b = p.querySelector('[data-pop-btn]'), pn = p.querySelector('[data-pop-panel]');
      if (b.getAttribute('aria-expanded') === 'true') { b.setAttribute('aria-expanded', 'false'); pn.hidden = true; if (focusBtn) b.focus(); }
    });
  }
  pops.forEach(function (p) {
    var b = p.querySelector('[data-pop-btn]'), pn = p.querySelector('[data-pop-panel]');
    var items = function () { return [].slice.call(pn.querySelectorAll('a,button')); };
    b.addEventListener('click', function () {
      var open = b.getAttribute('aria-expanded') !== 'true'; closeAll(p);
      b.setAttribute('aria-expanded', String(open)); pn.hidden = !open;
      if (open) { var cur = pn.querySelector('[aria-current="true"],[aria-checked="true"]') || items()[0]; cur && cur.focus(); }
    });
    pn.addEventListener('keydown', function (e) {
      var it = items(), i = it.indexOf(d.activeElement);
      if (e.key === 'ArrowDown') { e.preventDefault(); it[(i + 1) % it.length].focus(); }
      if (e.key === 'ArrowUp') { e.preventDefault(); it[(i - 1 + it.length) % it.length].focus(); }
      if (e.key === 'Home') { e.preventDefault(); it[0].focus(); }
      if (e.key === 'End') { e.preventDefault(); it[it.length - 1].focus(); }
      if (e.key === 'Tab') closeAll();
    });
  });
  d.addEventListener('click', function (e) { if (!e.target.closest('[data-pop]')) closeAll(); });
  d.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var open = pops.filter(function (p) { return p.querySelector('[data-pop-btn]').getAttribute('aria-expanded') === 'true'; })[0]; if (open) closeAll(null, true); } });

  /* ---------- theme: light / dark / auto ---------- */
  var themeBtns = [].slice.call(d.querySelectorAll('[data-theme-set]'));
  function drawTheme() {
    var pref = de.getAttribute('data-theme-pref') || 'auto';
    themeBtns.forEach(function (b) { b.setAttribute('aria-checked', String(b.dataset.themeSet === pref)); });
    var tb = d.querySelector('[data-theme-btn]');
    if (tb && T.themeNames) tb.setAttribute('aria-label', (tb.getAttribute('aria-label') || '').split(':')[0] + ': ' + T.themeNames[pref]);
    var m = d.querySelector('meta[name="theme-color"]'); if (m) m.setAttribute('content', de.getAttribute('data-theme') === 'dark' ? '#111214' : '#1C1D20');
  }
  function setTheme(pref, from) {
    if (!w.rfTheme) return;
    var before = de.getAttribute('data-theme');
    var apply = function () { w.rfTheme(pref); drawTheme(); };
    var changes = function () { var t = pref === 'auto' ? (w.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : pref; return t !== before; };
    if (!changes()) { apply(); return; }
    if (d.startViewTransition && !RM && MOTION !== 'off' && !B.classList.contains('paused')) {
      var r = from && from.getBoundingClientRect ? from.getBoundingClientRect() : { left: w.innerWidth - 60, top: 20, width: 0, height: 0 };
      var x = r.left + r.width / 2, y = r.top + r.height / 2;
      de.style.setProperty('--vx', x + 'px'); de.style.setProperty('--vy', y + 'px');
      de.style.setProperty('--vr', Math.hypot(Math.max(x, w.innerWidth - x), Math.max(y, w.innerHeight - y)) + 'px');
      de.classList.add('theme-vt');
      var vt = d.startViewTransition(apply);
      var done = function () { de.classList.remove('theme-vt'); };
      vt.finished.then(done, done); setTimeout(done, 1600);
    } else { apply(); }
  }
  themeBtns.forEach(function (b) {
    b.addEventListener('click', function () {
      var hb = d.querySelector('[data-theme-btn]'); var from = b.closest('.pop') ? hb : b;
      closeAll(null, !!b.closest('.pop')); setTheme(b.dataset.themeSet, from);
    });
  });
  if (w.matchMedia) w.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', drawTheme);
  drawTheme();

  /* ---------- language: remember the choice; on a first visit offer the visitor's own language ---------- */
  d.querySelectorAll('[data-lang-pick]').forEach(function (a) { a.addEventListener('click', function () { store('rf-lang', a.dataset.langPick); }); });
  (function hint() {
    var bar = d.querySelector('[data-lang-hint]'); if (!bar || !T.hint || B.classList.contains('is-admin')) return;
    var saved = store('rf-lang'), cur = T.lang;
    if (saved || store('rf-lang-hint') === 'x') return;
    var langs = (navigator.languages || [navigator.language || '']).map(function (l) { return String(l).slice(0, 2).toLowerCase(); });
    var want = null; for (var i = 0; i < langs.length; i++) { if (T.hint[langs[i]]) { want = langs[i]; break; } }
    if (!want || want === cur) return;
    var alt = d.querySelector('link[rel="alternate"][hreflang="' + want + '"]'); if (!alt) return;
    bar.setAttribute('lang', want);
    bar.querySelector('[data-lang-hint-t]').textContent = T.hint[want].t;
    var go = bar.querySelector('[data-lang-hint-go]'); go.textContent = T.hint[want].go; go.href = new URL(alt.href).pathname + location.hash; go.setAttribute('hreflang', want);
    go.addEventListener('click', function () { store('rf-lang', want); });
    bar.querySelector('[data-lang-hint-x]').addEventListener('click', function () { store('rf-lang-hint', 'x'); store('rf-lang', cur); bar.hidden = true; });
    bar.hidden = false;
  })();

  /* ---------- toasts ---------- */
  var tz = d.querySelector('[data-toasts]');
  function toast(html, ms) {
    if (!tz) return; var t = d.createElement('div'); t.className = 'toast'; t.innerHTML = html; tz.appendChild(t);
    while (tz.children.length > 3) tz.removeChild(tz.firstChild);
    setTimeout(function () { t.classList.add('out'); setTimeout(function () { t.remove(); }, 320); }, ms || 3800);
  }
  w.rfToast = toast;
  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  d.addEventListener('rf:basket', function (e) {
    var x = e.detail; if (!T.toastAdded) return;
    toast(esc((x.added ? T.toastAdded : T.toastRemoved).replace('{n}', x.n)) + (x.n ? ' <a href="' + esc(x.href) + '">' + esc(T.toastOpen) + '</a>' : ''));
  });

  /* ---------- back to top: an LED ring that fills as you read ---------- */
  var tt = d.querySelector('[data-totop]');
  if (tt) {
    tt.hidden = false; var ticking = false;
    var upd = function () { ticking = false; var h = de.scrollHeight - w.innerHeight, y = w.scrollY; tt.classList.toggle('on', y > w.innerHeight * 1.2); tt.style.setProperty('--read', h > 0 ? Math.round(y / h * 100) : 0); };
    w.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(upd); } }, { passive: true }); upd();
    tt.addEventListener('click', function (e) { e.preventDefault(); w.scrollTo({ top: 0, behavior: RM ? 'auto' : 'smooth' }); var m = d.getElementById('main'); if (m) { m.setAttribute('tabindex', '-1'); m.focus({ preventScroll: true }); } });
  }


  /* ---------- process line: the wire powers up as you scroll, each step lights when the current reaches it ---------- */
  var proc = d.querySelector('[data-proc]');
  if (proc) {
    var steps = [].slice.call(proc.children), wide = function () { return w.innerWidth > 1100; };
    if (MOTION !== 'full' || RM) steps.forEach(function (s) { s.classList.add('is-on'); });
    else {
      var pt = false;
      var pupd = function () {
        pt = false; var r = proc.getBoundingClientRect(), vh = w.innerHeight;
        var k = Math.min(1, Math.max(0, (vh * .8 - r.top) / (vh * .55)));
        proc.style.setProperty('--wire', wide() ? (k * 100).toFixed(1) + '%' : '0%');
        steps.forEach(function (s, i) {
          var on = wide() ? k >= (i + .2) / steps.length : s.getBoundingClientRect().top < vh * .8;
          if (on && !s.classList.contains('is-on')) { s.classList.add('is-on'); var lt = s.querySelector('[data-lottie]'); if (lt && lt._a) lt._a.goToAndPlay(0, true); }
        });
      };
      w.addEventListener('scroll', function () { if (!pt) { pt = true; requestAnimationFrame(pupd); } }, { passive: true }); w.addEventListener('resize', pupd); pupd();
    }
  }

  /* ---------- profile cards: tilt + Lumen edge that follows the pointer ---------- */
  if (MOTION === 'full' && !RM && w.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    d.querySelectorAll('.pcard a').forEach(function (a) {
      var lit = !!a.querySelector('.tag-lit');
      a.addEventListener('pointermove', function (e) {
        var r = a.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
        a.classList.add('tilt'); if (lit) a.setAttribute('data-lit', '');
        a.style.setProperty('--ry', ((x - .5) * 6).toFixed(2) + 'deg'); a.style.setProperty('--rx', ((.5 - y) * 5).toFixed(2) + 'deg');
        a.style.setProperty('--gx', (x * 100).toFixed(1) + '%'); a.style.setProperty('--gy', (y * 100).toFixed(1) + '%');
      });
      a.addEventListener('pointerleave', function () { a.classList.remove('tilt'); a.removeAttribute('data-lit'); a.style.removeProperty('--rx'); a.style.removeProperty('--ry'); });
    });
  }

  /* ---------- contact: local time in Daugavpils (+ open now, when the hours are set) ---------- */
  var live = d.querySelector('[data-live]');
  if (live && w.Intl) {
    var clk = live.querySelector('[data-live-clock]'), stl = live.querySelector('[data-live-st]'), hrs = null;
    try { hrs = JSON.parse(live.getAttribute('data-hours') || 'null'); } catch (e) {}
    var tick = function () {
      var now = new Date(), parts = {};
      new Intl.DateTimeFormat('en-GB', { timeZone: live.dataset.tz, hour: '2-digit', minute: '2-digit', weekday: 'short', hourCycle: 'h23' }).formatToParts(now).forEach(function (p) { parts[p.type] = p.value; });
      clk.textContent = parts.hour + ':' + parts.minute;
      if (hrs && stl) {
        var wd = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].indexOf(parts.weekday) + 1, last = +hrs.days.split('-')[1];
        var hm = parts.hour + ':' + parts.minute, open = wd >= 1 && wd <= last && hm >= hrs.from && hm < hrs.to;
        stl.textContent = open ? live.dataset.open : live.dataset.closed; stl.classList.toggle('is-open', open);
      }
    };
    tick(); setInterval(tick, 20000);
  }

  /* ---------- compare up to 3 profiles (saved on this device) ---------- */
  var cbar = d.querySelector('[data-cmp-bar]'), cdlg = d.querySelector('[data-cmp-dlg]');
  if (cbar && cdlg) {
    var CK = 'rf_cmp', cmp = []; try { cmp = JSON.parse(store(CK) || '[]').filter(function (x) { return /^[a-z0-9-]{1,90}$/.test(x); }).slice(0, 3); } catch (e) { cmp = []; }
    var cidx = null, loadIdx = function () { return cidx ? Promise.resolve(cidx) : fetch(T.searchIdx, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) { cidx = {}; j.forEach(function (o) { if (o.k === 'profile') cidx[o.slug] = o; }); return cidx; }); };
    var drawCmp = function () {
      store(CK, JSON.stringify(cmp));
      d.querySelectorAll('[data-cmp]').forEach(function (b) { b.setAttribute('aria-pressed', String(cmp.indexOf(b.dataset.cmp) > -1)); });
      cbar.hidden = !cmp.length; B.classList.toggle('has-cmp', !!cmp.length);
      cbar.querySelector('[data-cmp-n]').textContent = (T.cmpN || '{n}').replace('{n}', cmp.length);
      cbar.querySelector('[data-cmp-open]').disabled = cmp.length < 2;
      loadIdx().then(function (ix) {
        cbar.querySelector('[data-cmp-thumbs]').innerHTML = cmp.map(function (s) { var o = ix[s]; return o ? '<li><img src="' + esc(o.i) + '" alt="' + esc(o.t) + '" width="48" height="36"></li>' : ''; }).join('');
      }).catch(function () {});
    };
    d.addEventListener('click', function (e) {
      var b = e.target.closest('[data-cmp]'); if (!b) return;
      var k = b.dataset.cmp, i = cmp.indexOf(k);
      if (i > -1) cmp.splice(i, 1); else { if (cmp.length >= 3) { toast(esc(T.cmpMax || 'Max 3')); return; } cmp.push(k); }
      drawCmp();
    });
    cbar.querySelector('[data-cmp-clear]').addEventListener('click', function () { cmp = []; drawCmp(); });
    var table = function (ix) {
      var P = cmp.map(function (s) { return ix[s]; }).filter(Boolean), R = T.cmpRows || {};
      var labels = []; P.forEach(function (o) { (o.sp || []).forEach(function (x) { if (labels.indexOf(x[0]) < 0) labels.push(x[0]); }); });
      var row = function (lab, f) { var vals = P.map(f), same = vals.every(function (v) { return v === vals[0]; }); return '<tr' + (same ? '' : ' class="diff"') + '><th scope="row">' + esc(lab) + '</th>' + vals.map(function (v) { return '<td>' + esc(v || '—') + '</td>'; }).join('') + '</tr>'; };
      var h = '<table class="cmp-tbl"><thead><tr><td></td>' + P.map(function (o) { return '<th scope="col"><a href="' + esc(o.u) + '"><img src="' + esc(o.i) + '" alt="" width="200" height="150" loading="lazy"><b>' + esc(o.t) + '</b><span>' + esc(o.s) + '</span></a><button type="button" class="cmp-rm" data-cmp="' + esc(o.slug) + '">' + esc(T.cmpRemove || '×') + '</button></th>'; }).join('') + '</tr></thead><tbody>';
      h += row(R.light, function (o) { return o.ln; }) + row(R.size, function (o) { return o.z; }) + row(R.depth, function (o) { return o.d; }) + row(R.custom, function (o) { return o.cd ? R.yes : R.no; });
      labels.forEach(function (lab) { h += row(lab, function (o) { var f = (o.sp || []).filter(function (x) { return x[0] === lab; })[0]; return f ? f[1] : ''; }); });
      h += '<tr class="cmp-go"><td></td>' + P.map(function (o) { return '<td><a class="lnk" href="' + esc(o.u) + '">' + esc(T.cmpOpen || '→') + '</a></td>'; }).join('') + '</tr></tbody></table>';
      return h;
    };
    var cOpener = null;
    cbar.querySelector('[data-cmp-open]').addEventListener('click', function () { cOpener = d.activeElement; loadIdx().then(function (ix) { cdlg.querySelector('[data-cmp-table]').innerHTML = table(ix); cdlg.showModal(); }); });
    cdlg.querySelector('[data-cmp-close]').addEventListener('click', function () { cdlg.close(); });
    cdlg.addEventListener('click', function (e) { if (e.target === cdlg) cdlg.close(); if (e.target.closest('.cmp-rm')) { setTimeout(function () { if (cmp.length < 2) cdlg.close(); else cdlg.querySelector('[data-cmp-table]').innerHTML = table(cidx); }, 0); } });
    cdlg.addEventListener('close', function () { cOpener && cOpener.focus && cOpener.focus(); });
    if (cmp.length) drawCmp(); else d.querySelectorAll('[data-cmp]').forEach(function (b) { b.setAttribute('aria-pressed', 'false'); });
  }
  /* ---------- search palette (Ctrl/⌘ K, "/" or the header button) ---------- */
  var dlg = d.querySelector('[data-cmdk]');
  if (dlg && dlg.showModal) {
    var q = dlg.querySelector('[data-cmdk-q]'), list = dlg.querySelector('[data-cmdk-list]'), idx = null, sel = 0, opts = [], opener = null;
    var isMac = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);
    d.querySelectorAll('[data-kbd]').forEach(function (k) { k.textContent = isMac ? '⌘K' : 'Ctrl K'; });
    var hb = d.querySelector('[data-search-open]'); if (hb) hb.title = hb.getAttribute('aria-label') + ' (' + (isMac ? '⌘K' : 'Ctrl+K') + ')';
    var fold = function (s) { return String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); };
    var load = function () { return idx ? Promise.resolve(idx) : fetch(T.searchIdx, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) { idx = j; j.forEach(function (o) { o._f = fold(o.t + ' ' + (o.s || '') + ' ' + (o.x || '')); o._t = fold(o.t); }); return j; }); };
    var hl = function (text, terms) {
      var out = esc(text); if (!terms.length) return out;
      var f = fold(text), marks = [];
      terms.forEach(function (t) { var i = f.indexOf(t); if (i > -1) marks.push([i, i + t.length]); });
      if (!marks.length) return out; marks.sort(function (a, b) { return a[0] - b[0]; });
      var res = '', at = 0; marks.forEach(function (m) { if (m[0] < at) return; res += esc(text.slice(at, m[0])) + '<mark>' + esc(text.slice(m[0], m[1])) + '</mark>'; at = m[1]; });
      return res + esc(text.slice(at));
    };
    var ICONS = {};
    // borrow the site's own icons from the page (no extra file)
    [['search', '[data-search-open] svg'], ['globe', '.lang svg'], ['user', '.hdr-portal svg']].forEach(function (p) { var el = d.querySelector(p[1]); if (el) ICONS[p[0]] = el.outerHTML; });
    d.querySelectorAll('[data-theme-set] svg').forEach(function (s) { ICONS[s.closest('[data-theme-set]').dataset.themeSet === 'auto' ? 'auto' : (s.closest('[data-theme-set]').dataset.themeSet === 'dark' ? 'moon' : 'sun')] = s.outerHTML; });
    ICONS.spark = ICONS.search;
    function render() {
      var terms = fold(q.value).split(/\s+/).filter(Boolean), res = [];
      (idx || []).forEach(function (o) {
        if (!terms.length) { if (o.k !== 'story') res.push([o.k === 'action' ? 1 : (o.k === 'page' ? 2 : 3), o]); return; }
        var score = 0; for (var i = 0; i < terms.length; i++) { var p = o._f.indexOf(terms[i]); if (p < 0) return; score += (o._t.indexOf(terms[i]) === 0 ? 0 : (o._t.indexOf(terms[i]) > -1 ? 1 : 3)) + p / 400; }
        res.push([score + ({ profile: 0, story: .5, page: .8, action: 1 }[o.k]), o]);
      });
      res.sort(function (a, b) { return a[0] - b[0]; });
      res = res.slice(0, terms.length ? 24 : 14).map(function (r) { return r[1]; });
      var groups = {}; res.forEach(function (o) { (groups[o.k] = groups[o.k] || []).push(o); });
      var html = '', n = 0; opts = [];
      ['profile', 'story', 'page', 'action'].forEach(function (k) {
        if (!groups[k]) return;
        html += '<div role="group" aria-labelledby="cg-' + k + '"><p class="cmdk-g" id="cg-' + k + '">' + esc((T.sG || {})[k] || k) + '</p>';
        groups[k].forEach(function (o) {
          var th = o.i ? '<img class="cmdk-th" src="' + esc(o.i) + '" alt="" width="52" height="40" loading="lazy">' : '<span class="cmdk-th" aria-hidden="true">' + (o.svg || ICONS[o.theme === 'dark' ? 'moon' : o.theme === 'light' ? 'sun' : o.theme] || (o.lang ? ICONS.globe : '') || ICONS.spark || '') + '</span>';
          var lt = o.k === 'profile' && o.l && o.l !== 'unlit' && o.l !== 'flat' ? '<span class="cmdk-lt">' + esc(o.l.replace('_', ' ')) + '</span>' : '';
          html += '<a class="cmdk-o" role="option" id="co-' + n + '" aria-selected="' + (n === 0) + '" href="' + esc(o.u || '#') + '" data-n="' + n + '">' + th + '<span class="cmdk-t"><b>' + hl(o.t, terms) + '</b>' + (o.s ? '<span class="cmdk-s">' + hl(o.s, terms) + '</span>' : '') + '</span>' + lt + '</a>';
          opts.push(o); n++;
        });
        html += '</div>';
      });
      list.innerHTML = html || '<p class="cmdk-none">' + esc((T.sNone || 'Nothing found for “{q}”').replace('{q}', q.value)) + '</p>';
      sel = 0; mark();
    }
    function mark() {
      list.querySelectorAll('.cmdk-o').forEach(function (a) { var on = +a.dataset.n === sel; a.setAttribute('aria-selected', String(on)); if (on) { q.setAttribute('aria-activedescendant', a.id); a.scrollIntoView({ block: 'nearest' }); } });
      if (!opts.length) q.removeAttribute('aria-activedescendant');
    }
    function pick(n, e) {
      var o = opts[n]; if (!o) return;
      if (o.theme) { e && e.preventDefault(); dlg.close(); setTheme(o.theme, d.querySelector('[data-theme-btn]')); return; }
      if (o.lang) { e && e.preventDefault(); store('rf-lang', o.lang); var alt = d.querySelector('link[rel="alternate"][hreflang="' + o.lang + '"]'); location.href = alt ? new URL(alt.href).pathname : '/' + o.lang + '/'; return; }
      if (!e) location.href = o.u;
    }
    function open() { opener = d.activeElement; closeAll(); dlg.showModal(); q.value = ''; q.focus(); load().then(render).catch(function () { list.innerHTML = ''; }); }
    d.querySelectorAll('[data-search-open]').forEach(function (b) { b.addEventListener('click', open); });
    dlg.querySelector('[data-cmdk-close]').addEventListener('click', function () { dlg.close(); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); var a = e.target.closest('.cmdk-o'); if (a) pick(+a.dataset.n, e); });
    dlg.addEventListener('close', function () { opener && opener.focus && opener.focus(); });
    q.addEventListener('input', render);
    q.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); if (opts.length) { sel = (sel + 1) % opts.length; mark(); } }
      if (e.key === 'ArrowUp') { e.preventDefault(); if (opts.length) { sel = (sel - 1 + opts.length) % opts.length; mark(); } }
      if (e.key === 'Enter') { e.preventDefault(); pick(sel); }
    });
    d.addEventListener('keydown', function (e) {
      var typing = /^(INPUT|TEXTAREA|SELECT)$/.test((e.target || {}).tagName || '') || (e.target && e.target.isContentEditable);
      if ((e.key === 'k' || e.key === 'K') && (e.metaKey || e.ctrlKey)) { e.preventDefault(); dlg.open ? dlg.close() : open(); }
      else if (e.key === '/' && !typing && !dlg.open) { e.preventDefault(); open(); }
    });
  }
})();
