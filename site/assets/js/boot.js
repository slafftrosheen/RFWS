/* Reclame Fabriek 2.3 · runs before the page paints: theme (light / dark / auto) from the visitor's choice, no flash. No cookies. */
(function (d, w) {
  var h = d.documentElement, p = null, m = w.matchMedia ? w.matchMedia('(prefers-color-scheme: dark)') : null;
  try { p = localStorage.getItem('rf-theme'); } catch (e) {}
  if (p !== 'light' && p !== 'dark' && p !== 'auto') p = h.getAttribute('data-theme-default') || 'auto';
  var set = function () { var t = p === 'auto' ? (m && m.matches ? 'dark' : 'light') : p; h.setAttribute('data-theme', t); h.setAttribute('data-theme-pref', p); };
  set(); h.classList.add('js');
  if (m && m.addEventListener) m.addEventListener('change', function () { if (p === 'auto') set(); });
  w.rfTheme = function (np) { p = np; try { localStorage.setItem('rf-theme', np); } catch (e) {} set(); };
})(document, window);
