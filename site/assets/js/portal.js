/* Reclame Fabriek · client portal helpers. Every form works without this file; it only makes them nicer. */
(function () {
  'use strict';
  var d = document;

  // confirm dangerous actions
  d.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
  });
  // print the written quote
  d.querySelectorAll('[data-print]').forEach(function (b) { b.addEventListener('click', function () { window.print(); }); });
  // newest message in view
  var th = d.querySelector('[data-thread]'); if (th) th.scrollTop = th.scrollHeight;

  // picked files: show their names
  function wireFiles(root) {
    root.querySelectorAll('[data-files]').forEach(function (inp) {
      var box = inp.closest('form').querySelector('[data-picked]');
      inp.addEventListener('change', function () {
        if (!box) return; box.innerHTML = '';
        Array.prototype.forEach.call(inp.files, function (f) { var li = d.createElement('li'); li.textContent = f.name + ' · ' + Math.max(1, Math.round(f.size / 1024)) + ' KB'; box.appendChild(li); });
      });
    });
  }
  wireFiles(d);
  d.querySelectorAll('[data-drop]').forEach(function (z) {
    ['dragenter', 'dragover'].forEach(function (ev) { z.addEventListener(ev, function () { z.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { z.addEventListener(ev, function () { z.classList.remove('is-over'); }); });
  });


  /* ===== 2.3 ===== */
  var T = {}; try { T = JSON.parse(d.body.getAttribute('data-i18n') || '{}'); } catch (e) {}
  var toast = function (m) { if (window.rfToast) window.rfToast(String(m).replace(/[&<>]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]; })); };
  // copy a link
  d.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var v = b.getAttribute('data-copy');
      (navigator.clipboard ? navigator.clipboard.writeText(v) : Promise.reject()).then(function () { toast(T.copied || '✓'); }, function () { window.prompt('', v); });
    });
  });
  // dashboard: instant filter + search (the same links/inputs work without JS)
  var dash = d.querySelector('[data-dash]');
  if (dash) {
    var rows = [].slice.call(d.querySelectorAll('.pt-req[data-grp]')), none = d.querySelector('[data-dash-none]'), qi = dash.querySelector('[data-dash-q]'), fi = dash.querySelector('input[name="f"]');
    var apply = function () {
      var f = fi.value, q = qi.value.trim().toLowerCase(), n = 0;
      rows.forEach(function (r) { var vis = (f === 'all' || r.dataset.grp === f || (f === 'action' && r.hasAttribute('data-act'))) && (!q || r.dataset.hay.indexOf(q) > -1); r.hidden = !vis; if (vis) n++; });
      if (none) none.hidden = n > 0;
      dash.querySelectorAll('[data-f]').forEach(function (c) { if (c.dataset.f === f) c.setAttribute('aria-current', 'true'); else c.removeAttribute('aria-current'); });
      var u = new URL(location.href); u.searchParams.set('f', f); if (q) u.searchParams.set('q', q); else u.searchParams.delete('q'); history.replaceState(null, '', u);
    };
    dash.querySelectorAll('[data-f]').forEach(function (c) { c.addEventListener('click', function (e) { e.preventDefault(); fi.value = c.dataset.f; apply(); }); });
    qi.addEventListener('input', apply); dash.addEventListener('submit', function (e) { e.preventDefault(); apply(); });
  }
  // request page: the thread refreshes itself while the tab is open
  var pollBox = d.querySelector('[data-poll]');
  if (pollBox && window.fetch) {
    var pn = +pollBox.getAttribute('data-poll-n'), busy = false;
    var poll = function () {
      if (busy || d.hidden) return; busy = true;
      fetch(pollBox.getAttribute('data-poll') + '?n=' + pn, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (j) {
        if (j.n > pn && j.html) {
          var ol = pollBox.querySelector('[data-thread]'), em = pollBox.querySelector('[data-thread-empty]');
          ol.innerHTML = j.html; ol.hidden = false; if (em) em.remove(); ol.scrollTop = ol.scrollHeight;
          var last = ol.lastElementChild; if (last) last.classList.add('pt-msg-in');
          toast(pollBox.getAttribute('data-poll-msg'));
        }
        pn = j.n;
      }).catch(function () {}).then(function () { busy = false; });
    };
    setInterval(poll, 20000); d.addEventListener('visibilitychange', function () { if (!d.hidden) poll(); });
  }

  if (d.querySelector('[data-draft-clear]')) { try { Object.keys(localStorage).forEach(function (k) { if (k.indexOf('rf_draft_') === 0) localStorage.removeItem(k); }); } catch (e) {} }

  // new request: add / remove signs, profile thumbnails and standard depths
  var form = d.querySelector('[data-reqform]');
  if (!form) return;
  var map = {}; try { map = JSON.parse(form.getAttribute('data-map') || '{}'); } catch (e) {}
  var box = form.querySelector('[data-items]'), tpl = form.querySelector('[data-item-tpl]');
  function renumber() {
    box.querySelectorAll('[data-item]').forEach(function (it, i) {
      it.querySelector('[data-item-n]').textContent = i + 1;
      it.querySelectorAll('[name^="items["]').forEach(function (el) { el.name = el.name.replace(/^items\[\d+\]/, 'items[' + i + ']'); });
    });
  }
  function wireItem(it) {
    var sel = it.querySelector('[data-profile-pick]'), th = it.querySelector('[data-thumb]'), ln = it.querySelector('[data-plink]'), dp = it.querySelector('[data-depth]');
    var thumb0 = th.innerHTML;
    sel.addEventListener('change', function () {
      var p = map[sel.value];
      if (p) { th.innerHTML = ''; var im = d.createElement('img'); im.src = p.img; im.alt = ''; im.width = 96; im.height = 72; th.appendChild(im); ln.href = p.u; ln.hidden = false; dp.setAttribute('list', 'dl-' + sel.value); }
      else { th.innerHTML = thumb0; ln.hidden = true; dp.removeAttribute('list'); }
      if (p && p.d.length && !dp.value) dp.placeholder = p.d[0] + ' mm';
    });
    it.querySelector('[data-item-rm]').addEventListener('click', function () { if (box.children.length > 1) { it.remove(); renumber(); } });
  }
  box.querySelectorAll('[data-item]').forEach(wireItem);
  form.querySelector('[data-item-add]').addEventListener('click', function () {
    var n = box.querySelectorAll('[data-item]').length;
    var wrap = d.createElement('div'); wrap.innerHTML = tpl.innerHTML.replace(/items\[999\]/g, 'items[' + n + ']');
    var it = wrap.firstElementChild; it.classList.add('pt-new'); box.appendChild(it); wireItem(it); renumber();
    var s = it.querySelector('select'); if (s) s.focus();
  });

  // 2.3 · letter height → how far away it reads (rule of thumb for good contrast: ~1 m per 3 mm for reading, ~1 m per 8 mm for best impact)
  var rtpl = form.getAttribute('data-readable') || '';
  function readable(inp) {
    var out = inp.closest('.fld').querySelector('[data-readable-out]'); if (!out) return;
    var h = parseInt(inp.value, 10);
    out.textContent = h >= 10 ? rtpl.replace('{m}', String(Math.max(1, Math.round(h * 0.33)))).replace('{b}', String(Math.max(1, Math.round(h * 0.125)))) : '';
  }
  form.addEventListener('input', function (e) { if (e.target.matches('[data-height]')) readable(e.target); });
  form.querySelectorAll('[data-height]').forEach(readable);
  // saved sites: picking one fills the address; "save this address" only for new ones
  var sp = form.querySelector('[data-site-pick]'), sa = form.querySelector('[data-site-addr]'), ss = form.querySelector('[data-save-site]');
  var syncSite = function () { if (!ss) return; ss.hidden = !!(sp && sp.value); var cb = ss.querySelector('input[type=checkbox]'), lb = ss.querySelector('.pt-site-label'); if (lb) lb.hidden = !cb.checked; };
  if (sp) sp.addEventListener('change', function () { var o = sp.options[sp.selectedIndex]; if (o && o.dataset.addr) sa.value = o.dataset.addr; syncSite(); });
  if (ss) ss.querySelector('input[type=checkbox]').addEventListener('change', syncSite); syncSite();
  // draft: saved on this device while typing; offered back next time (files are not kept)
  var DK = form.getAttribute('data-draft-key'), note = form.querySelector('[data-draft-note]');
  var snapshot = function () { var o = {}; new FormData(form).forEach(function (v, k) { if (k !== '_csrf' && typeof v === 'string') o[k] = v; }); o._n = box.querySelectorAll('[data-item]').length; o._t = Date.now(); return o; };
  var save = function () { try { var s = snapshot(), any = Object.keys(s).some(function (k) { return k.charAt(0) !== '_' && s[k] && !/qty\]$/.test(k); }); if (any) localStorage.setItem(DK, JSON.stringify(s)); } catch (e) {} };
  var timer = null; form.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(save, 600); });
  form.addEventListener('submit', function () { save(); });
  var saved = null; try { saved = JSON.parse(localStorage.getItem(DK) || 'null'); } catch (e) {}
  if (note && saved && Date.now() - saved._t < 30 * 864e5) {
    note.hidden = false;
    note.querySelector('[data-draft-restore]').addEventListener('click', function () {
      while (box.querySelectorAll('[data-item]').length < saved._n) form.querySelector('[data-item-add]').click();
      Object.keys(saved).forEach(function (k) { if (k.charAt(0) === '_') return; var el = form.elements[k]; if (!el) return; if (el.type === 'checkbox') el.checked = !!saved[k]; else el.value = saved[k]; if (el.matches && el.matches('[data-profile-pick]')) el.dispatchEvent(new Event('change')); });
      form.querySelectorAll('[data-height]').forEach(readable); syncSite(); note.hidden = true;
    });
    note.querySelector('[data-draft-drop]').addEventListener('click', function () { try { localStorage.removeItem(DK); } catch (e) {} note.hidden = true; });
  }
})();
