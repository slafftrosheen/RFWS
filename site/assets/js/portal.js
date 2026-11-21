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
})();
