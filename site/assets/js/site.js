/* Reclame Fabriek website · v2.2 (motion: "Switch-on" + "Night shift") — no framework, no tracking. Everything works without JS; this only enhances. */
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

  // language menu: close on outside click / Esc
  var lg = d.querySelector('[data-lang]');
  if (lg) {
    d.addEventListener('click', function (e) { if (lg.open && !lg.contains(e.target)) lg.open = false; });
    d.addEventListener('keydown', function (e) { if (e.key === 'Escape' && lg.open) { lg.open = false; lg.querySelector('summary').focus(); } });
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
        if (el.hasAttribute('data-hover')) { var host = el.closest('.svc') || el; host.addEventListener('mouseenter', function () { a.goToAndPlay(0, true); }); }
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
    });
  });
  if (d.querySelector('[data-basket-clear]')) { basket = []; }
  drawBasket(false);
})();
