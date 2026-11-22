/* Reclame Fabriek CMS · admin behaviour. Plain JS, no libraries. */
(function () {
  'use strict';
  var d = document, B = d.body, CSRF = B.getAttribute('data-csrf') || '';
  function $(s, r) { return (r || d).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || d).querySelectorAll(s)); }

  // mobile menu
  var mb = $('[data-menu]'); if (mb) mb.addEventListener('click', function () { $('.side').classList.toggle('open'); });

  // ---------- language tabs ----------
  var tabs = $$('[data-ltab]');
  function setLang(l) {
    B.setAttribute('data-l', l);
    tabs.forEach(function (t) { t.setAttribute('aria-selected', String(t.getAttribute('data-ltab') === l)); });
    $$('[data-lfield]').forEach(function (i) { i.value = l; });
    try { localStorage.setItem('rf-admin-lang', l); } catch (e) {}
  }
  if (tabs.length) {
    var q = new URLSearchParams(location.search).get('l'), saved = null;
    try { saved = localStorage.getItem('rf-admin-lang'); } catch (e) {}
    setLang(q || saved || B.getAttribute('data-l') || 'ru');
    tabs.forEach(function (t) { t.addEventListener('click', function () { setLang(t.getAttribute('data-ltab')); }); });
  }

  // ---------- unsaved-changes guard + Ctrl/Cmd+S ----------
  $$('form[data-dirty]').forEach(function (f) {
    var dirty = false;
    f.addEventListener('input', function () { dirty = true; });
    f.addEventListener('change', function () { dirty = true; });
    f.addEventListener('submit', function () { dirty = false; });
    f._markDirty = function () { dirty = true; };
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    d.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); if (f.reportValidity()) { dirty = false; f.requestSubmit ? f.requestSubmit() : f.submit(); } } });
  });
  function dirtyOf(el) { var f = el.closest('form[data-dirty]'); if (f && f._markDirty) f._markDirty(); }

  // ---------- confirmations ----------
  d.addEventListener('submit', function (e) { var m = e.target.getAttribute && e.target.getAttribute('data-confirm'); if (m && !confirm(m)) e.preventDefault(); }, true);

  // ---------- simple formatting buttons for rich text ----------
  d.addEventListener('click', function (e) {
    var b = e.target.closest('[data-rt]'); if (!b) return;
    var ta = b.closest('.li').querySelector('textarea'); if (!ta) return;
    var s = ta.selectionStart, en = ta.selectionEnd, sel = ta.value.slice(s, en), ins;
    if (b.getAttribute('data-rt') === 'b') ins = '**' + (sel || 'жирный текст') + '**';
    else { var u = prompt('Адрес ссылки (https://… или /contact/)', 'https://'); if (!u) return; ins = '[' + (sel || 'текст ссылки') + '](' + u + ')'; }
    ta.setRangeText(ins, s, en, 'end'); ta.focus(); dirtyOf(ta);
  });

  // ---------- drag & drop sorting (lists, galleries, pick lists, table rows) ----------
  var dragEl = null;
  function sortable(box) {
    box.addEventListener('dragstart', function (e) {
      var it = e.target.closest('[draggable="true"]'); if (!it || it.parentNode !== box) return;
      if (e.target.closest('input, textarea, select') && e.target.closest('.lrow-b')) { e.preventDefault(); return; }
      dragEl = it; it.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', ''); } catch (x) {}
    });
    box.addEventListener('dragover', function (e) {
      if (!dragEl || dragEl.parentNode !== box) return; e.preventDefault();
      var over = e.target.closest('[draggable="true"]'); if (!over || over === dragEl || over.parentNode !== box) return;
      var r = over.getBoundingClientRect(), grid = getComputedStyle(box).display === 'grid' && getComputedStyle(box).gridTemplateColumns.split(' ').length > 1;
      var after = grid ? (e.clientX - r.left) > r.width / 2 : (e.clientY - r.top) > r.height / 2;
      box.insertBefore(dragEl, after ? over.nextSibling : over);
    });
    box.addEventListener('dragend', function () { if (!dragEl) return; dragEl.classList.remove('dragging'); dragEl = null; box.dispatchEvent(new Event('sorted', { bubbles: true })); dirtyOf(box); });
  }
  $$('[data-sortable]').forEach(sortable);
  // list rows: only the header is a drag handle (so text inside stays selectable)
  d.addEventListener('mousedown', function (e) { var r = e.target.closest('.lrow'); if (r) r.setAttribute('draggable', e.target.closest('.lrow-h') ? 'true' : 'false'); });

  // ---------- repeatable lists ----------
  d.addEventListener('click', function (e) {
    var t = e.target, row = t.closest('.lrow');
    if (t.matches('[data-add]')) {
      var box = t.closest('[data-list]'), depth = box.getAttribute('data-depth'), tpl = box.querySelector(':scope > template');
      var html = tpl.innerHTML.split('__i' + depth + '__').join(String(Date.now()) + Math.floor(Math.random() * 1000));
      var ol = box.querySelector(':scope > .listf-rows'); ol.insertAdjacentHTML('beforeend', html);
      var nr = ol.lastElementChild; $$('[data-sortable]', nr).forEach(sortable); applyLang(); dirtyOf(box);
      var f = nr.querySelector('input:not([type=hidden]), textarea, select'); if (f) f.focus();
    } else if (t.matches('[data-del]') && row) {
      if (confirm('Удалить этот блок?')) { dirtyOf(row); row.remove(); }
    } else if (t.matches('[data-up]') && row && row.previousElementSibling) { row.parentNode.insertBefore(row, row.previousElementSibling); dirtyOf(row); }
    else if (t.matches('[data-down]') && row && row.nextElementSibling) { row.parentNode.insertBefore(row.nextElementSibling, row); dirtyOf(row); }
  });
  function applyLang() { var l = B.getAttribute('data-l'); if (l) setLang(l); }

  // ---------- gallery + image fields ----------
  function galSync(g) {
    var ids = $$('.galf-list li', g).map(function (li) { return li.getAttribute('data-id'); });
    g.querySelector('input[type=hidden]').value = ids.join(',');
    var n = g.querySelector('.galf-n'); if (n) n.textContent = ids.length + ' фото · перетаскивайте, чтобы менять порядок';
  }
  d.addEventListener('sorted', function (e) { var g = e.target.closest('[data-gallery]'); if (g) galSync(g); var t = e.target.closest('[data-sortrows]'); if (t) orderChanged(t); });
  d.addEventListener('click', function (e) {
    var t = e.target;
    if (t.matches('[data-remove]')) { var g = t.closest('[data-gallery]'); t.closest('li').remove(); galSync(g); dirtyOf(g); }
    if (t.matches('[data-clear]')) { var f = t.closest('[data-image]'); f.querySelector('input').value = ''; f.querySelector('.imgf-prev').innerHTML = '<span>Нет фото</span>'; dirtyOf(f); }
    if (t.matches('[data-pick]')) openPicker(t);
  });

  // ---------- media picker dialog ----------
  var dlg = $('[data-mdlg]'), grid = dlg && $('[data-mgrid]', dlg), lib = null, ctx = null, chosen = [];
  function thumb(m) {
    var li = d.createElement('li'), b = d.createElement('button'), img = d.createElement('img'), nm = d.createElement('span');
    b.type = 'button'; b.setAttribute('data-id', m.id); b.setAttribute('aria-pressed', 'false'); b.title = m.name + ' · ' + m.w + '×' + m.h;
    img.src = m.src; img.alt = ''; img.loading = 'lazy'; nm.className = 'nm'; nm.textContent = m.name;
    b.appendChild(img); b.appendChild(nm); li.appendChild(b); li._m = m; return li;
  }
  function render(filter) {
    grid.innerHTML = '';
    (lib || []).forEach(function (m) {
      if (filter && (m.name + ' ' + m.id).toLowerCase().indexOf(filter.toLowerCase()) < 0) return;
      var li = thumb(m); if (chosen.indexOf(m.id) >= 0) li.firstChild.setAttribute('aria-pressed', 'true'); grid.appendChild(li);
    });
    count();
  }
  function count() { $('[data-mcount]', dlg).textContent = ctx && ctx.many ? 'Выбрано: ' + chosen.length : (chosen.length ? 'Фото выбрано' : 'Нажмите на фото'); }
  function openPicker(btn) {
    if (!dlg) return;
    ctx = { many: btn.getAttribute('data-pick') === 'many', field: btn.closest('[data-gallery], [data-image]') };
    chosen = [];
    $('[data-mstatus]', dlg).textContent = 'Загрузка…'; dlg.showModal();
    var load = lib ? Promise.resolve(lib) : fetch('/admin/?a=media_json', { credentials: 'same-origin' }).then(function (r) { return r.json(); });
    load.then(function (j) { lib = j; $('[data-mstatus]', dlg).textContent = ''; render($('[data-msearch]', dlg).value); $('[data-msearch]', dlg).focus(); });
  }
  if (dlg) {
    grid.addEventListener('click', function (e) {
      var b = e.target.closest('button[data-id]'); if (!b) return; var id = b.getAttribute('data-id');
      if (ctx.many) { var i = chosen.indexOf(id); if (i >= 0) { chosen.splice(i, 1); b.setAttribute('aria-pressed', 'false'); } else { chosen.push(id); b.setAttribute('aria-pressed', 'true'); } }
      else { chosen = [id]; $$('button[aria-pressed="true"]', grid).forEach(function (x) { x.setAttribute('aria-pressed', 'false'); }); b.setAttribute('aria-pressed', 'true'); }
      count();
    });
    grid.addEventListener('dblclick', function (e) { if (!ctx.many && e.target.closest('button[data-id]')) $('[data-mok]', dlg).click(); });
    $('[data-msearch]', dlg).addEventListener('input', function (e) { render(e.target.value); });
    $('[data-mclose]', dlg).addEventListener('click', function () { dlg.close(); });
    $('[data-mok]', dlg).addEventListener('click', function () {
      var f = ctx.field, byId = {}; (lib || []).forEach(function (m) { byId[m.id] = m; });
      if (ctx.many) {
        var ul = f.querySelector('.galf-list'), have = f.querySelector('input[type=hidden]').value.split(',');
        chosen.forEach(function (id) { if (have.indexOf(id) >= 0) return; var li = d.createElement('li'); li.draggable = true; li.setAttribute('data-id', id);
          li.innerHTML = '<img alt=""><button type="button" class="x" data-remove title="Убрать из галереи">×</button>'; li.firstChild.src = byId[id].src; ul.appendChild(li); });
        galSync(f);
      } else if (chosen[0]) {
        f.querySelector('input[type=hidden]').value = chosen[0];
        var p = f.querySelector('.imgf-prev'); p.innerHTML = '<img alt="">'; p.firstChild.src = byId[chosen[0]].src;
      }
      if (chosen.length) dirtyOf(f);
      dlg.close();
    });
    $('[data-mupload]', dlg).addEventListener('change', function (e) {
      upload(e.target.files, $('[data-mstatus]', dlg), function (items) {
        lib = items.concat(lib || []); items.forEach(function (m) { if (ctx.many) chosen.push(m.id); else chosen = [m.id]; }); render($('[data-msearch]', dlg).value);
      }); e.target.value = '';
    });
  }

  // ---------- uploads (library page + picker) ----------
  function upload(files, status, done) {
    if (!files || !files.length) return;
    var fd = new FormData(); Array.prototype.forEach.call(files, function (f) { fd.append('files[]', f); }); fd.append('_csrf', CSRF);
    var x = new XMLHttpRequest(); x.open('POST', '/admin/?a=upload'); x.setRequestHeader('X-CSRF', CSRF);
    x.upload.onprogress = function (e) { if (e.lengthComputable) status.textContent = 'Загрузка… ' + Math.round(100 * e.loaded / e.total) + '%'; };
    x.onload = function () {
      var j; try { j = JSON.parse(x.responseText); } catch (e) { status.textContent = 'Ошибка загрузки. Попробуйте файл поменьше.'; return; }
      status.textContent = (j.items.length ? 'Загружено: ' + j.items.length + '. ' : '') + (j.errors || []).join(' ');
      done && done(j.items || []);
    };
    x.onerror = function () { status.textContent = 'Нет связи с сервером.'; };
    status.textContent = 'Загрузка…'; x.send(fd);
  }
  var drop = $('[data-drop]');
  if (drop) {
    var st = $('[data-upstatus]', drop), fin = function (items) { if (items.length) setTimeout(function () { location.href = '/admin/?a=media'; }, 700); };
    $('[data-upload]', drop).addEventListener('change', function (e) { upload(e.target.files, st, fin); });
    ['dragenter', 'dragover'].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.add('drop-over'); }); });
    ['dragleave', 'drop'].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.remove('drop-over'); }); });
    drop.addEventListener('drop', function (e) { upload(e.dataTransfer.files, st, fin); });
  }

  // ---------- list ordering (profiles / stories tables) ----------
  function orderChanged(tbl) {
    var f = tbl.closest('[data-orderform]'), box = $('[data-orderinputs]', f); box.innerHTML = '';
    $$('tbody tr', tbl).forEach(function (tr) { var i = d.createElement('input'); i.type = 'hidden'; i.name = 'slugs[]'; i.value = tr.getAttribute('data-slug'); box.appendChild(i); });
    $('[data-ordersave]', f).disabled = false;
  }
  $$('[data-sortrows] tbody').forEach(function (tb) { tb.setAttribute('data-sortable', ''); sortable(tb); });
})();

/* 2.2 · client portal: quote editor (rows + live totals), confirm buttons, select-on-focus */
(function () {
  'use strict';
  var d = document;
  d.addEventListener('click', function (e) { var b = e.target.closest && e.target.closest('[data-confirm-btn]'); if (b && !confirm(b.getAttribute('data-confirm-btn'))) e.preventDefault(); }, true);
  d.querySelectorAll('[data-select]').forEach(function (i) { i.addEventListener('focus', function () { i.select(); }); });
  var f = d.querySelector('[data-qedit]'); if (!f) return;
  var body = f.querySelector('[data-qbody]'), tpl = f.querySelector('[data-qtpl]');
  var num = function (v) { var n = parseFloat(String(v || '').replace(/\s/g, '').replace(',', '.')); return isNaN(n) ? 0 : n; };
  var fmt = function (n) { return n.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '\u00a0€'; };
  function calc() {
    var net = 0;
    body.querySelectorAll('[data-qline]').forEach(function (tr, i) {
      var s = Math.round(num(tr.querySelector('[data-q]').value) * num(tr.querySelector('[data-p]').value) * 100) / 100; net += s;
      tr.querySelector('[data-sum]').textContent = fmt(s);
      tr.querySelectorAll('[name^="lines["]').forEach(function (el) { el.name = el.name.replace(/^lines\[\d+\]/, 'lines[' + i + ']'); });
    });
    var vat = Math.round(net * num(f.querySelector('[data-vat]').value)) / 100;
    f.querySelector('[data-tnet]').textContent = fmt(net); f.querySelector('[data-tvat]').textContent = fmt(vat); f.querySelector('[data-ttot]').innerHTML = '<b>' + fmt(net + vat) + '</b>';
  }
  f.addEventListener('input', calc);
  f.addEventListener('click', function (e) {
    if (e.target.closest('[data-qadd]')) { var w = d.createElement('tbody'); w.innerHTML = tpl.innerHTML; body.appendChild(w.firstElementChild); calc(); }
    var rm = e.target.closest('[data-qrm]'); if (rm && body.children.length > 1) { rm.closest('tr').remove(); calc(); }
  });
  calc();
})();

/* 2.4 · reply templates, price list → quote line, production board (drag & drop + keyboard form) */
(function () {
  'use strict';
  var d = document;
  // reply template: insert in the client's language at the cursor (or replace an empty box)
  d.querySelectorAll('[data-reply-pick]').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var ta = sel.form && sel.form.querySelector('[data-reply-body]'); if (!ta || !sel.value) return;
      var v = ta.value, s = ta.selectionStart || v.length;
      ta.value = v.trim() === '' ? sel.value : v.slice(0, s) + (s && v[s - 1] !== '\n' ? '\n\n' : '') + sel.value + v.slice(s);
      sel.selectedIndex = 0; ta.focus(); ta.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });
  // price list: add a filled quote line
  var pa = d.querySelector('[data-price-add]');
  if (pa) pa.addEventListener('click', function () {
    var sel = d.querySelector('[data-price-pick]'), o = sel && sel.options[sel.selectedIndex]; if (!o || !o.value && !o.dataset.desc) { sel && sel.focus(); return; }
    var f = pa.closest('form'), body = f.querySelector('[data-qbody]'), tpl = f.querySelector('[data-qtpl]');
    var last = body.querySelector('[data-qline]:last-child'), tr;
    if (last && !last.querySelector('textarea').value.trim()) tr = last;
    else { var w = d.createElement('tbody'); w.innerHTML = tpl.innerHTML; tr = w.firstElementChild; body.appendChild(tr); }
    tr.querySelector('textarea').value = o.dataset.desc; tr.querySelector('[data-p]').value = o.dataset.price;
    tr.querySelector('[name$="[unit]"]').value = o.dataset.unit; var ps = tr.querySelector('select'); if (ps) ps.value = o.dataset.slug || '';
    sel.selectedIndex = 0; f.dispatchEvent(new Event('input', { bubbles: true }));
    tr.classList.add('just-added'); setTimeout(function () { tr.classList.remove('just-added'); }, 1200);
  });
  if (d.querySelector('[data-price-pick]')) d.querySelector('[data-price-pick]').value = '';
  // board
  var kb = d.querySelector('[data-kb]'); if (!kb) return;
  var all = d.querySelector('[data-kb-notify-all]'), live = d.querySelector('[data-kb-live]'), drag = null;
  var setNotify = function () { kb.querySelectorAll('[data-kb-notify]').forEach(function (i) { i.value = all.checked ? '1' : ''; }); };
  all.addEventListener('change', setNotify); setNotify();
  kb.querySelectorAll('.kb-move button').forEach(function (b) { b.classList.add('kb-go'); });
  var count = function () { kb.querySelectorAll('.kb-col').forEach(function (c) { c.querySelector('h2 b').textContent = c.querySelectorAll('.kb-card').length; }); };
  var move = function (card, st) {
    var f = card.querySelector('form'), old = card.parentNode, from = card.dataset.st; if (st === from) return;
    f.querySelector('select').value = st;
    var col = kb.querySelector('[data-kbdrop="' + st + '"]'); col.insertBefore(card, col.firstChild); card.dataset.st = st; card.classList.add('kb-lit'); count();
    fetch(f.action, { method: 'POST', body: new FormData(f), headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) { live.textContent = j.msg; if (!j.ok) throw 0; setTimeout(function () { card.classList.remove('kb-lit'); }, 1400); })
      .catch(function () { old.insertBefore(card, old.firstChild); card.dataset.st = from; f.querySelector('select').value = from; card.classList.remove('kb-lit'); count(); live.textContent = 'Не удалось перенести'; });
  };
  kb.addEventListener('dragstart', function (e) { var c = e.target.closest('.kb-card'); if (!c) return; drag = c; c.classList.add('kb-drag'); e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', c.dataset.no); });
  kb.addEventListener('dragend', function () { if (drag) drag.classList.remove('kb-drag'); drag = null; kb.querySelectorAll('.kb-over').forEach(function (x) { x.classList.remove('kb-over'); }); });
  kb.addEventListener('dragover', function (e) { var l = e.target.closest('[data-kbdrop]'); if (!l || !drag) return; e.preventDefault(); kb.querySelectorAll('.kb-over').forEach(function (x) { if (x !== l) x.classList.remove('kb-over'); }); l.classList.add('kb-over'); });
  kb.addEventListener('drop', function (e) { var l = e.target.closest('[data-kbdrop]'); if (!l || !drag) return; e.preventDefault(); l.classList.remove('kb-over'); move(drag, l.dataset.kbdrop); });
  kb.addEventListener('submit', function (e) { var c = e.target.closest('.kb-card'); if (!c) return; e.preventDefault(); move(c, e.target.querySelector('select').value); });
})();
