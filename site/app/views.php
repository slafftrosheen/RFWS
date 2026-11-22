<?php
/* Reclame Fabriek CMS · public templates. Design = website 1.0 (brand book Ed. 2): Paper + Graphite, one red signal per screen, Lumen only for lit things. */
declare(strict_types=1);

require_once __DIR__ . '/icons.php';
const ICON_ALIAS = ['letters' => 'box-letter', 'star' => 'spark', 'box' => 'lightbox'];
function icon(string $n, string $cls = 'ic'): string {
    $n = ICON_ALIAS[$n] ?? $n;
    return '<svg class="' . $cls . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . (RF_ICONS[$n] ?? RF_ICONS['spark']) . '</svg>';
}
/** Lottie slot: the static icon shows first (and stays for no-JS / reduced motion), the animation replaces it when visible. */
function lottie(string $name, string $label = '', string $cls = '', string $fallback = '', array $o = []): string {
    $f = ROOT . '/assets/lottie/' . $name . '.json'; if (!is_file($f)) return $fallback;
    if (preg_match('/^rf_(svc|step)_|^rf_(sent|proof|success|loader)$/', $name)) $cls .= ' lt-ink';
    return '<div class="lt ' . e($cls) . '" data-lottie="/assets/lottie/' . e($name) . '.json?v=' . filemtime($f) . '"' . (isset($o['loop']) ? ' data-loop="' . (int)$o['loop'] . '"' : '') . (!empty($o['hover']) ? ' data-hover' : '')
        . ($label ? ' role="img" aria-label="' . e($label) . '"' : ' aria-hidden="true"') . '>' . $fallback . '</div>';
}
const SVC_LOTTIE = ['box-letter' => 'rf_svc_letters', 'letters' => 'rf_svc_letters', 'cnc' => 'rf_svc_cnc', 'lab' => 'rf_svc_lab', 'chair' => 'rf_svc_chair'];
function light_icon(string $k): string { return ['frontlit' => 'light-frontlit', 'corona' => 'light-corona', 'neon' => 'light-neon', 'front_corona' => 'light-front-corona', 'front_side' => 'light-side', 'unlit' => 'light-unlit', 'flat' => 'flat-letter', 'lightbox' => 'lightbox'][$k] ?? 'bolt'; }
function ext_attr(string $u): string { return preg_match('#^https?://#', $u) ? ' target="_blank" rel="noopener"' : ''; }
function link_url(string $u): string { return str_starts_with($u, '/') ? url($u) : $u; }
function is_lit(string $k): bool { return !in_array($k, ['unlit', 'flat'], true); }

/* ---------------- layout ---------------- */
function layout(string $title, string $desc, string $path, string $body, array $o = []): string {
    $s = settings(); $l = lang(); $full = $title ? $title . ' · Reclame Fabriek' : 'Reclame Fabriek · ' . ui('brand.tag');
    $og = $o['og'] ?? ($s['og_image'] ?? '');
    $og = $og ? (str_starts_with($og, 'http') ? $og : site_origin() . $og) : '';
    $alts = '';
    foreach (langs() as $x) $alts .= '<link rel="alternate" hreflang="' . $x . '" href="' . e(abs_url($path, $x)) . "\">\n";
    $alts .= '<link rel="alternate" hreflang="x-default" href="' . e(abs_url($path, default_lang())) . "\">\n";
    $i18n = json_encode(['basketAdd' => ui('basket.add'), 'basketIn' => ui('basket.in'), 'portal' => url('/portal/'), 'showing' => ui('filter.showing'), 'sending' => ui('f.sending'), 'ok' => ui('f.ok'), 'subOk' => ui('f.sub_ok'), 'err' => ui('f.err'), 'errLink' => ui('f.err_link'), 'mailTo' => $s['form_to'] ?? '', 'mapsQ' => $s['maps_query'] ?? '', 'mapTitle' => 'Map: Reclame Fabriek', 'lang' => $l, 'searchIdx' => url('/search.json') , 'sNone' => ui('search.none'), 'sG' => ['profile' => ui('search.g_profiles'), 'story' => ui('search.g_projects'), 'page' => ui('search.g_pages'), 'action' => ui('search.g_actions')],
        'themeNames' => ['light' => ui('theme.light'), 'dark' => ui('theme.dark'), 'auto' => ui('theme.auto')], 'toastAdded' => ui('basket.toast_add'), 'toastRemoved' => ui('basket.toast_rm'), 'toastOpen' => ui('basket.toast_open'), 'copied' => ui('copied'),
        'cmpMax' => ui('cmp.max'), 'cmpTitle' => ui('cmp.title'), 'cmpN' => ui('cmp.n'), 'cmpRows' => ['light' => ui('cmp.light'), 'size' => ui('letter_height'), 'depth' => ui('depths'), 'custom' => ui('cmp.custom'), 'yes' => ui('cmp.yes'), 'no' => ui('cmp.no')], 'cmpOpen' => ui('cmp.open_profile'), 'cmpRemove' => ui('cmp.remove'), 'hint' => lang_hint_strings()], JSON_UNESCAPED_UNICODE);
    $robots = !empty($o['noindex']) ? '<meta name="robots" content="noindex">' : '';
    $preview = !empty($GLOBALS['PREVIEW']) ? '<div class="preview-bar">Предпросмотр: страница не опубликована</div>' : '';
    $pre = '';
    if (!empty($o['preload']) && ($m = media($o['preload']))) { $set = []; foreach ($m['sizes'] as $z) $set[] = '/uploads/' . $m['file'] . '-' . $z . '.webp ' . min($z, (int)$m['w']) . 'w'; $pre = '<link rel="preload" as="image" imagesrcset="' . e(implode(', ', $set)) . '" imagesizes="100vw" fetchpriority="high">'; }
    $mo = $s['motion'] ?? 'full';
    $vt = $mo === 'full' ? '<style>@media (prefers-reduced-motion:no-preference){@view-transition{navigation:auto}}</style>' : '';
    $css = is_file(ROOT . '/assets/css/site.min.css') ? 'site.min.css' : 'site.css'; $js = is_file(ROOT . '/assets/js/site.min.js') ? 'site.min.js' : 'site.js';
    $boot = '/assets/js/boot.js?v=' . @filemtime(ROOT . '/assets/js/boot.js');
    return '<!doctype html><html lang="' . $l . '" data-theme="light" data-theme-default="' . e(in_array($s['theme_default'] ?? 'auto', ['auto', 'light', 'dark'], true) ? ($s['theme_default'] ?? 'auto') : 'auto') . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light dark"><script src="' . $boot . '"></script>'
        . '<title>' . e($full) . '</title><meta name="description" content="' . e($desc) . '">' . $robots
        . '<link rel="canonical" href="' . e(abs_url($path)) . '">' . "\n" . $alts
        . '<meta property="og:type" content="website"><meta property="og:site_name" content="Reclame Fabriek"><meta property="og:locale" content="' . e($l) . '">'
        . '<meta property="og:title" content="' . e($full) . '"><meta property="og:description" content="' . e($desc) . '"><meta property="og:url" content="' . e(abs_url($path)) . '">'
        . ($og ? '<meta property="og:image" content="' . e($og) . '">' : '') . '<meta name="twitter:card" content="summary_large_image"><meta name="theme-color" content="#1C1D20">'
        . '<link rel="icon" href="/assets/brand/favicon.svg" type="image/svg+xml"><link rel="icon" href="/favicon.ico" sizes="32x32"><link rel="apple-touch-icon" href="/assets/brand/apple-touch-icon.png">'
        . '<link rel="preload" href="/assets/fonts/' . ($l === 'ru' ? 'pt-sans-narrow-700-cyrillic' : 'barlow-condensed-600-latin') . '.woff2" as="font" type="font/woff2" crossorigin>'
        . $pre . ($css === 'site.css' ? '<link rel="stylesheet" href="/assets/css/fonts.css">' : '') . '<link rel="stylesheet" href="/assets/css/' . $css . '?v=' . @filemtime(ROOT . '/assets/css/' . $css) . '">' . ($o['css'] ?? '') . $vt
        . '<script src="/assets/js/' . $js . '?v=' . @filemtime(ROOT . '/assets/js/' . $js) . '" defer></script>' . ($o['js'] ?? '') . '</head>'
        . '<body class="motion-' . e($mo) . (!empty($o['body']) ? ' ' . e($o['body']) : '') . '" data-i18n="' . e($i18n) . '">' . $preview . header_html($path, $o['cta'] ?? true) . '<main id="main">' . $body . '</main>' . footer_html() . '</body></html>';
}

/* 2.3 · language menu: native name + English name, current page kept, choice remembered (localStorage, no cookie) */
const LANG_EN = ['en' => 'English', 'lv' => 'Latvian', 'ru' => 'Russian', 'nl' => 'Dutch', 'de' => 'German', 'sv' => 'Swedish'];
function lang_hint_strings(): array {
    $o = []; foreach (langs() as $x) $o[$x] = ['t' => tr(ui_all()['lang.hint'] ?? [], $x), 'go' => tr(ui_all()['lang.hint_go'] ?? [], $x)];
    return $o;
}
function lang_links(string $path, string $cls): string {
    $li = '';
    foreach (langs() as $x) {
        $cur = $x === lang();
        $li .= '<li><a class="' . $cls . '-a" href="' . e(url($path, $x)) . '" hreflang="' . $x . '" lang="' . $x . '" data-lang-pick="' . $x . '"' . ($cur ? ' aria-current="true"' : '') . '>'
             . '<b class="lang-code">' . strtoupper($x) . '</b><span class="lang-names"><span class="lang-native">' . e(LANG_NAMES[$x]) . '</span>' . ($x !== 'en' ? '<small lang="en">' . e(LANG_EN[$x]) . '</small>' : '') . '</span>' . ($cur ? icon('check', 'ic ic-s lang-tick') : '') . '</a></li>';
    }
    return $li;
}
function lang_switch(string $path): string {
    if (count(langs()) < 2) return '';
    return '<div class="hdr-pop lang" data-pop><button class="hdr-ctl" type="button" aria-expanded="false" aria-controls="lang-menu" data-pop-btn aria-label="' . e(ui('lang')) . ': ' . e(LANG_NAMES[lang()]) . '">'
        . icon('globe', 'ic ic-s') . '<span class="hdr-ctl-t">' . strtoupper(lang()) . '</span>' . icon('chev', 'ic ic-xs hdr-chev') . '</button>'
        . '<div class="pop pop-lang" id="lang-menu" hidden data-pop-panel><p class="pop-h">' . e(ui('lang')) . '</p><ul class="lang-list">' . lang_links($path, 'lang') . '</ul></div></div>';
}
function theme_opts(string $cls): string {
    $o = '';
    foreach (['light' => 'sun', 'dark' => 'moon', 'auto' => 'auto'] as $k => $ic) $o .= '<button type="button" class="' . $cls . '" role="menuitemradio" aria-checked="false" data-theme-set="' . $k . '">' . icon($ic, 'ic ic-s ic-theme') . '<span>' . e(ui('theme.' . $k)) . '</span></button>';
    return $o;
}
function theme_switch(): string {
    return '<div class="hdr-pop theme" data-pop><button class="hdr-ctl hdr-ctl-ic" type="button" aria-expanded="false" aria-controls="theme-menu" data-pop-btn data-theme-btn aria-label="' . e(ui('theme.label')) . '">'
        . '<span class="th-ic th-ic-light">' . icon('sun', 'ic ic-s ic-theme') . '</span><span class="th-ic th-ic-dark">' . icon('moon', 'ic ic-s ic-theme') . '</span></button>'
        . '<div class="pop pop-theme" id="theme-menu" hidden data-pop-panel role="menu" aria-label="' . e(ui('theme.label')) . '"><p class="pop-h" aria-hidden="true">' . e(ui('theme.label')) . '</p>' . theme_opts('pop-opt') . '</div></div>';
}
function search_btn(): string {
    return '<button class="hdr-ctl hdr-search" type="button" data-search-open aria-haspopup="dialog" aria-label="' . e(ui('search.open')) . '">' . icon('search', 'ic ic-s') . '<span class="hdr-ctl-t">' . e(ui('search.open')) . '</span><kbd data-kbd>Ctrl K</kbd></button>';
}

function header_html(string $path, bool $cta): string {
    $s = settings();
    $nav = [[ui('nav.profiles'), url('/product-profiles/'), '/product-profiles/'], [ui('nav.projects'), url('/projects/'), '/projects/'],
            [ui('nav.research'), url('/signage-research/'), '/signage-research/'], [ui('nav.lumigrid'), $s['lumigrid_url'] ?? '', ''], [ui('nav.contact'), url('/contact/'), '/contact/']];
    $items = '';
    $cur = $GLOBALS['NAV_CUR'] ?? '';
    foreach ($nav as [$lbl, $href, $key]) {
        if (!$href) continue;
        if (!$key && str_starts_with($href, '/')) { $key = $href; $href = url($href); }   // 2.5: LUMIGRID lives on this site now
        $ext = !$key;
        $items .= '<li><a href="' . e($href) . '"' . ($key && $key === $cur ? ' aria-current="page"' : '') . ($ext ? ' target="_blank" rel="noopener"' : '') . '>' . e($lbl) . ($ext ? icon('ext', 'ic ic-s') : '') . '</a></li>';
    }
    $drawer = '<div class="nav-extra"><div class="nav-x"><p class="k">' . e(ui('lang')) . '</p><ul class="nav-langs">' . lang_links($path, 'nl') . '</ul></div>'
            . '<div class="nav-x"><p class="k">' . e(ui('theme.label')) . '</p><div class="seg-theme" role="menu" aria-label="' . e(ui('theme.label')) . '">' . theme_opts('seg-opt') . '</div></div></div>';
    return '<a class="skip" href="#main">' . e(ui('skip')) . '</a><header class="hdr" data-hdr><div class="wrap hdr-in">'
        . '<a class="brand" href="' . e(url('/')) . '" aria-label="Reclame Fabriek, ' . e(ui('home')) . '"><img src="/assets/brand/rf-logo-88.webp" width="44" height="44" alt="" fetchpriority="high"><span class="brand-name"><b>Reclame Fabriek</b><i>' . e(ui('brand.tag')) . '</i></span></a>'
        . '<nav class="nav" aria-label="Main" id="nav"><ul>' . $items . '</ul>' . $drawer . '</nav>'
        . '<div class="hdr-tools">' . search_btn() . lang_switch($path) . theme_switch() . '</div>'
        . '<a class="hdr-portal" href="' . e(url('/portal/')) . '" title="' . e(ui('nav.portal')) . '" data-basket-link>' . icon('user', 'ic ic-s') . '<span class="hdr-portal-t">' . e(ui('nav.portal')) . '</span><b class="basket-n" data-basket-n hidden>0</b></a>'
        . ($cta ? '<a class="btn btn-red hdr-cta" href="' . e(url('/contact/')) . '#quote">' . e(ui('cta.quote')) . '</a>' : '')
        . '<button class="nav-btn" type="button" aria-controls="nav" aria-expanded="false" data-nav-btn><span class="sr">' . e(ui('menu')) . '</span>' . icon('menu', 'ic ic-open') . icon('close', 'ic ic-close') . '</button></div><div class="progress" aria-hidden="true"></div></header>'
        . '<div class="lang-hint" data-lang-hint hidden><div class="wrap lang-hint-in"><span data-lang-hint-t></span><a class="btn btn-light btn-s" data-lang-hint-go href="#"></a><button type="button" class="lang-hint-x" data-lang-hint-x aria-label="' . e(ui('close')) . '">' . icon('close', 'ic ic-s') . '</button></div></div>';
}

function footer_html(): string {
    $s = settings(); $con = ''; $soc = ''; $logos = '';
    foreach ($s['contacts'] ?? [] as $c) {
        $tel = preg_replace('/[^\d+]/', '', $c['phone'] ?? '');
        $con .= '<li><span class="k">' . e(tr($c['role'] ?? [])) . '</span><b>' . e($c['name'] ?? '') . '</b>' . ($tel ? '<a href="tel:' . e($tel) . '">' . icon('phone', 'ic ic-s') . e($c['phone']) . '</a>' : '') . (!empty($c['email']) ? '<a href="mailto:' . e($c['email']) . '">' . icon('mail', 'ic ic-s') . e($c['email']) . '</a>' : '') . '</li>';
    }
    foreach ($s['social'] ?? [] as $x) $soc .= '<li><a href="' . e($x['url']) . '" target="_blank" rel="noopener">' . e($x['name']) . '</a></li>';
    foreach ($s['partner_logos'] ?? [] as $id) $logos .= img($id, 'Partner logo', '', false, '300px');
    return '<footer class="ftr"><div class="wrap"><div class="ftr-top"><div class="ftr-brand"><img src="/assets/brand/rf-logo-144.webp" width="72" height="72" alt="Reclame Fabriek" loading="lazy" decoding="async"><p>' . e(ui('footer.tag')) . '</p></div>'
        . '<div class="ftr-col"><h2 class="k">' . e(ui('find_us')) . '</h2><address>' . nl2br(e(tr($s['address'] ?? [])), false) . '</address>'
        . '<a class="lnk" href="https://www.google.com/maps/search/?api=1&amp;query=' . e(rawurlencode($s['maps_query'] ?? '')) . '" target="_blank" rel="noopener">' . e(ui('open_maps')) . ' ' . icon('ext', 'ic ic-s') . '</a></div>'
        . '<div class="ftr-col ftr-con"><h2 class="k">' . e(ui('contacts')) . '</h2><ul>' . $con . '</ul></div><div class="ftr-col"><h2 class="k">' . e(ui('follow')) . '</h2><ul class="soc">' . $soc . '</ul></div></div>'
        . ($logos ? '<div class="ftr-partners"><span class="k">' . e(ui('lsez')) . '</span><div class="logos">' . $logos . '</div></div>' : '')
        . '<div class="ftr-base"><span>© ' . e($s['founded'] ?? '1993') . '–' . date('Y') . ' ' . e($s['company'] ?? 'Reclame Fabriek') . '. ' . e(ui('rights')) . '</span><span><a href="' . e(url('/portal/')) . '">' . e(ui('nav.portal')) . '</a> · <a href="' . e(url('/privacy-policy/')) . '">' . e(ui('privacy')) . '</a> · ' . motion_btn('mo-ftr') . '</span></div></div></footer>'
        . '<dialog class="cmdk" data-cmdk aria-label="' . e(ui('search.open')) . '"><div class="cmdk-box"><div class="cmdk-top">' . icon('search', 'ic') . '<input type="text" role="combobox" aria-expanded="true" aria-controls="cmdk-list" aria-autocomplete="list" aria-label="' . e(ui('search.open')) . '" placeholder="' . e(ui('search.ph')) . '" autocomplete="off" spellcheck="false" data-cmdk-q><button type="button" class="cmdk-esc" data-cmdk-close aria-label="' . e(ui('close')) . '"><kbd>Esc</kbd></button></div>'
        . '<div class="cmdk-list" id="cmdk-list" role="listbox" aria-label="' . e(ui('search.open')) . '" data-cmdk-list></div><p class="cmdk-foot">' . icon('keyboard', 'ic ic-xs') . e(ui('search.hint')) . '</p></div></dialog>'
        . '<div class="cmp-bar" data-cmp-bar hidden><div class="wrap cmp-bar-in"><span class="cmp-bar-t">' . icon('compare', 'ic ic-s') . '<b data-cmp-n></b></span><ul class="cmp-thumbs" data-cmp-thumbs></ul>'
        . '<button type="button" class="btn btn-light btn-s" data-cmp-open>' . e(ui('cmp.title')) . '</button><button type="button" class="cmp-x" data-cmp-clear aria-label="' . e(ui('cmp.clear')) . '">' . icon('close', 'ic ic-s') . '</button></div></div>'
        . '<dialog class="cmp-dlg" data-cmp-dlg aria-labelledby="cmp-h"><div class="cmp-box"><div class="cmp-head"><h2 class="h3" id="cmp-h">' . e(ui('cmp.title')) . '</h2><button type="button" class="lb-x cmp-close" data-cmp-close aria-label="' . e(ui('close')) . '">' . icon('close') . '</button></div><div class="cmp-scroll" data-cmp-table tabindex="0"></div></div></dialog>'
        . '<div class="toasts" role="status" aria-live="polite" data-toasts></div>'
        . '<a class="totop" href="#main" data-totop aria-label="' . e(ui('totop')) . '" hidden><svg viewBox="0 0 48 48" aria-hidden="true"><circle class="totop-track" cx="24" cy="24" r="21"/><circle class="totop-led" cx="24" cy="24" r="21" pathLength="100"/></svg>' . icon('arrow-up', 'ic ic-s') . '</a>'
        . '<dialog class="lb" data-lb aria-label="' . e(ui('viewer')) . '"><button class="lb-x" type="button" data-lb-close aria-label="' . e(ui('close')) . '">' . icon('close') . '</button><img alt="" data-lb-img><p data-lb-cap></p>'
        . '<button class="lb-nav lb-prev" type="button" data-lb-prev aria-label="' . e(ui('prev')) . '">‹</button><button class="lb-nav lb-next" type="button" data-lb-next aria-label="' . e(ui('next')) . '">›</button></dialog>';
}

/* ---------------- components ---------------- */
function crumbs(array $trail): string {
    $o = '<nav class="crumbs" aria-label="' . e(ui('crumbs')) . '"><a href="' . e(url('/')) . '">' . e(ui('home')) . '</a>';
    foreach ($trail as $i => [$label, $href]) $o .= '<span>/</span>' . ($href ? '<a href="' . e(url($href)) . '">' . e($label) . '</a>' : '<span aria-current="page">' . e($label) . '</span>');
    return $o . '</nav>';
}
function video_html(string $v, ?string $poster, string $label): string {
    $id = youtube_id($v); if (!$id) return '';
    return '<div class="vid" data-yt="' . e($id) . '">' . ($poster ? img($poster, '', 'vid-poster', false, '(min-width: 1100px) 60vw, 100vw') : '') . '<button class="vid-play" type="button" data-yt-play aria-label="' . e(ui('play') . ': ' . $label) . '">' . icon('play') . '<span>' . e(ui('play')) . '</span></button><p class="vid-note">' . e(ui('yt_note')) . '</p></div>';
}
function gallery_html(array $ids, string $alt, string $cls = 'gal'): string {
    $o = '';
    foreach ($ids as $id) { if (!media($id)) continue; $o .= '<li><a href="' . e(media_big($id)) . '" data-lb-item data-cap="' . e(tr(media($id)['alt'] ?? []) ?: $alt) . '">' . img($id, $alt, '', false, '(min-width: 1100px) 25vw, 50vw') . '<span class="zoom">' . icon('zoom', 'ic ic-s') . '</span></a></li>'; }
    return $o ? '<ul class="' . $cls . '" data-lb-group>' . $o . '</ul>' : '';
}
function sec_h(array $c, string $extra = '', bool $lit = false): string {
    return '<header class="sec-h">' . (tr($c['kicker'] ?? []) ? '<p class="k' . ($lit ? ' k-lit' : '') . '">' . e(tr($c['kicker'])) . '</p>' : '') . '<h2 class="h2">' . e(tr($c['title'] ?? [])) . '</h2>' . (tr($c['text'] ?? []) ? '<p class="lead-s">' . inline(tr($c['text'])) . '</p>' : '') . $extra . '</header>';
}
function card_profile(array $p, string $h = 'h3'): string {
    $k = $p['light'] ?? 'unlit'; $lit = is_lit($k); $t = tr($p['title']); $sub = tr($p['subtitle'] ?? []);
    return '<li class="pcard" data-group="' . e($p['group']) . '" data-kind="' . e($k) . '"><a href="' . e(url('/' . $p['slug'] . '/')) . '"><span class="pcard-img">' . img($p['cover'] ?? null, "$t, $sub", '', false, '(min-width: 1100px) 25vw, (min-width: 640px) 33vw, 50vw') . '</span>'
        . '<span class="pcard-body"><' . $h . ' class="pcard-t">' . e($t) . '</' . $h . '><span class="pcard-s">' . e($sub) . '</span><span class="tag' . ($lit ? ' tag-lit' : '') . '">' . ($lit ? icon('bolt', 'ic ic-xs') : '') . e(ui('light.' . $k)) . '</span></span></a>'
        . '<button type="button" class="cmp-t" data-cmp="' . e($p['slug']) . '" aria-pressed="false" aria-label="' . e(ui('cmp.add') . ': ' . $t) . '" title="' . e(ui('cmp.add')) . '">' . icon('compare', 'ic ic-s') . '</button></li>';
}
function card_story(array $s, string $h = 'h3'): string {
    return '<li class="scard"><a href="' . e(url('/' . $s['slug'] . '/')) . '"><span class="scard-img">' . img($s['cover'] ?? null, tr($s['title']), '', false, '(min-width: 1100px) 33vw, (min-width: 640px) 50vw, 100vw') . '</span>'
        . '<span class="scard-body"><span class="k">' . e(tr($s['kicker'] ?? [])) . '</span><' . $h . ' class="scard-t">' . e(tr($s['title'])) . '</' . $h . '><span class="more">' . e(ui('read_story')) . ' ' . icon('arrow', 'ic ic-s') . '</span></span></a></li>';
}
function cta_band(array $c = []): string {
    $phone = settings()['hero_phone'] ?? '';
    return '<section class="band band-dark" data-spot><div class="wrap band-in"><div><h2 class="h2">' . e(tr($c['title'] ?? []) ?: ui('cta.title')) . '</h2><p>' . e(tr($c['text'] ?? []) ?: ui('cta.text')) . '</p></div>'
        . '<div class="band-act"><a class="btn btn-light" href="' . e(url('/contact/')) . '#quote">' . e(ui('start_request')) . ' ' . icon('arrow', 'ic ic-s') . '</a>'
        . ($phone ? '<a class="btn btn-ghost-light" href="tel:' . e(preg_replace('/[^\d+]/', '', $phone)) . '">' . icon('phone', 'ic ic-s') . e($phone) . '</a>' : '') . '</div></div></section>';
}
/** Image that JS fills in later (hero slides): keeps the first paint light. */
function img_later(?string $id, string $sizes = '100vw'): string {
    $m = media($id); if (!$m) return '';
    $set = []; $w = (int)$m['w']; $h = (int)$m['h'];
    foreach ($m['sizes'] as $s) $set[] = '/uploads/' . $m['file'] . '-' . $s . '.webp ' . min($s, $w) . 'w';
    $dw = min(max($m['sizes']), $w); $dh = $w ? (int)round($h * $dw / $w) : 0;
    return '<span class="slide-later" data-src="' . e(media_src($id, 1600)) . '" data-srcset="' . e(implode(', ', $set)) . '" data-sizes="' . e($sizes) . '" data-w="' . $dw . '" data-h="' . $dh . '"></span>';
}
function phead_media(array $head): string {
    return media($head['image'] ?? null) ? '<div class="phead-media" aria-hidden="true">' . img($head['image'], '', '', true, '100vw') . '</div>' : '';
}
function motion_btn(string $cls = ''): string {
    return '<button class="mo-btn ' . e($cls) . '" type="button" data-motion-toggle aria-pressed="false"><span class="mo-p" aria-hidden="true"></span><span class="mo-t" data-on="' . e(ui('motion.play')) . '" data-off="' . e(ui('motion.pause')) . '">' . e(ui('motion.pause')) . '</span></button>';
}
/** Factory film strip: two rows drifting in opposite directions; every photo opens in the viewer. */
function film_html(array $ids, string $alt): string {
    $ids = array_values(array_filter($ids, fn($i) => (bool)media($i))); if (!$ids) return '';
    $rows = array_chunk($ids, (int)ceil(count($ids) / 2)); $o = '';
    foreach ($rows as $r => $row) {
        $a = '';
        foreach ($row as $id) {
            $cap = tr(media($id)['alt'] ?? []) ?: $alt;
            $a .= '<li><a href="' . e(media_big($id)) . '" data-lb-item data-cap="' . e($cap) . '">' . img($id, $alt, '', false, '(min-width: 900px) 300px, 45vw') . '</a></li>';
        }
        $o .= '<div class="film-row' . ($r ? ' film-rev' : '') . '"><div class="film-track"><ul class="film-run">' . $a . '</ul></div></div>';   // the seamless-loop copy is added by site.js (full motion only)
    }
    return '<div class="film" data-lb-group data-film tabindex="0" aria-label="' . e(ui('film_label')) . '">' . $o . '</div>';
}
/** Profile rail: every profile in one horizontal, snap-scrolling strip. */
function rail_html(array $profiles): string {
    if (!$profiles) return '';
    $nL = count(array_filter($profiles, fn($p) => $p['group'] === 'letters')); $nB = count($profiles) - $nL;
    return '<div class="rail" data-rail><div class="rail-top"><div class="rail-tabs" role="group" aria-label="' . e(ui('filter.label')) . '">'
        . '<button type="button" class="chip" data-rail-go="letters" aria-pressed="true">' . e(ui('box_letters')) . ' <small>' . $nL . '</small></button>'
        . ($nB ? '<button type="button" class="chip" data-rail-go="lightbox" aria-pressed="false">' . e(ui('lightboxes')) . ' <small>' . $nB . '</small></button>' : '')
        . '</div><div class="rail-nav"><button type="button" class="rail-b" data-rail-prev aria-label="' . e(ui('prev')) . '">' . icon('arrow', 'ic ic-s ic-flip') . '</button><button type="button" class="rail-b" data-rail-next aria-label="' . e(ui('next')) . '">' . icon('arrow', 'ic ic-s') . '</button></div></div>'
        . '<ul class="pcards rail-list" data-rail-list tabindex="0" aria-label="' . e(ui('nav.profiles')) . '">' . implode('', array_map(fn($p) => card_profile($p, 'h3'), $profiles)) . '</ul><div class="rail-bar" aria-hidden="true"><i data-rail-bar></i></div></div>';
}

/* ---------- Anatomy of a box letter: the layers of this profile, taken apart and put back together (scroll-driven, with a button) ---------- */
const ANAT_LAYERS = ['back' => ['Back'], 'led' => ['LED components', 'LED'], 'sides' => ['Sides'], 'trim' => ['Signtrim', 'Internal trim', 'Trim'], 'face' => ['Face panels', 'NEON imitation', 'Neon imitation', 'Face']];
function anat_specs(array $p): array {
    $out = [];
    foreach (ANAT_LAYERS as $k => $names) foreach ($p['specs'] ?? [] as $s) {
        $en = (string)($s['label']['en'] ?? '');
        foreach ($names as $n) if (strcasecmp($en, $n) === 0 || stripos($en, $n) === 0) { $out[$k] = $s; break 2; }
    }
    return $out;
}
function anatomy_html(array $p): string {
    if (($p['group'] ?? '') !== 'letters') return '';
    $sp = anat_specs($p); if (!isset($sp['sides']) && !isset($sp['face'])) return '';
    $k = $p['light'] ?? 'unlit';
    $R = 'M0,0H60C82,0 95,13 95,32C95,47 86,57 73,61L98,100H72L50,64H26V100H0Z M26,22V43H58C66,43 70,38 70,32C70,26 66,22 58,22Z';
    $order = ['back', 'led', 'sides', 'trim', 'face'];
    if (!isset($sp['trim'])) $order = array_values(array_diff($order, ['trim']));
    $g = ''; $li = ''; $n = 0;
    foreach ($order as $i => $L) {
        $n++;
        $inner = match ($L) {
            'back' => '<path class="an-back" d="' . $R . '"/>',
            'led' => '<g class="an-led">' . implode('', array_map(fn($xy) => '<rect x="' . $xy[0] . '" y="' . $xy[1] . '" width="9" height="5" rx="1.5"/>', [[6, 8], [6, 30], [6, 52], [6, 76], [34, 8], [58, 8], [78, 22], [62, 48], [74, 78], [32, 50]])) . '</g>',
            'sides' => '<g class="an-sides">' . implode('', array_map(fn($z) => '<path d="' . $R . '" transform="translate(' . (-$z * 0.9) . ' ' . (-$z * 0.45) . ')"/>', range(1, 7))) . '</g>',
            'trim' => '<path class="an-trim" d="' . $R . '"/>',
            'face' => '<path class="an-face" d="' . $R . '"/>' . ($k === 'neon' ? '<path class="an-tube" d="M13,92V12H58C74,12 83,21 83,32C83,44 74,52 58,52H38L82,92"/>' : ''),
        };
        $g .= '<g class="an-l an-' . $L . '" style="--i:' . $i . '"><g transform="matrix(1,-.22,0,1,0,0)">' . $inner . '</g><circle class="an-dot" cx="104" cy="-14" r="7"/><text class="an-num" x="104" y="-10.5">' . $n . '</text></g>';
        $s = $sp[$L] ?? null;
        $label = $s ? tr($s['label']) : ui('anat.' . $L);
        $val = $s ? inline(tr($s['value'])) : '';
        $li .= '<li style="--i:' . $i . '"><b class="an-n">' . $n . '</b><span><b>' . e($label) . '</b>' . ($val ? '<br><span class="an-v">' . $val . '</span>' : '') . '</span></li>';
    }
    $lit = is_lit($k);
    $svg = '<svg class="an-svg" viewBox="-60 -70 300 230" role="img" aria-label="' . e(ui('anat.title', ['x' => tr($p['title'])])) . '"><defs><filter id="anGlow" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="6"/></filter></defs>'
         . '<g class="an-wall"><path class="an-halo" d="' . $R . '" transform="matrix(1,-.22,0,1,0,0)"/></g><g class="an-stack">' . $g . '</g></svg>';
    return '<section class="sec anat" data-anat data-light="' . e($k) . '"' . ($lit ? ' data-lit' : '') . '><div class="wrap anat-in"><div class="anat-txt"><p class="k' . ($lit ? ' k-lit' : '') . '">' . e(ui('anat.kicker')) . '</p><h2 class="h2">' . e(ui('anat.title', ['x' => tr($p['title'])])) . '</h2><p>' . e(ui('anat.text')) . '</p>'
        . '<ol class="anat-list">' . $li . '</ol><button type="button" class="btn btn-line" data-anat-toggle aria-pressed="false" data-on="' . e(ui('anat.together')) . '" data-off="' . e(ui('anat.apart')) . '">' . e(ui('anat.apart')) . '</button></div>'
        . '<figure class="anat-fig">' . $svg . '</figure></div></section>';
}

function pick(string $type, array $slugs): array { $all = items($type); $o = []; foreach ($slugs as $s) if (isset($all[$s])) $o[] = $all[$s]; return $o; }

function ticker(array $items): string {
    $w = []; foreach ($items as $x) { $t = tr($x['text'] ?? []); if ($t !== '') $w[] = $t; }
    if (!$w) return '';
    $one = ''; foreach ($w as $t) $one .= '<span>' . e($t) . '</span><i aria-hidden="true"></i>';
    return '<section class="ticker" aria-label="' . e(implode(' · ', $w)) . '"><div class="ticker-track" aria-hidden="true"><div class="ticker-run">' . $one . '</div><div class="ticker-run">' . $one . '</div></div></section>';
}

/* ---------------- pages ---------------- */
const PROC_LOTTIE = ['rf_step_design', 'rf_svc_cnc', 'rf_step_bend', 'rf_step_paint', 'rf_step_led', 'rf_step_install'];
function process_html(array $pc): string {
    $steps = '';
    foreach (array_values($pc['items'] ?? []) as $i => $x) {
        $lt = PROC_LOTTIE[$i] ?? 'rf_svc_letters';
        $steps .= '<li class="proc-step" style="--i:' . $i . '"><span class="proc-ic">' . lottie($lt, '', 'lt-step', icon(['proof', 'cnc', 'bender', 'spray', 'led-module', 'truck'][$i] ?? 'spark', 'ic'), ['hover' => 1]) . '</span>'
                . '<span class="proc-n" aria-hidden="true">' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) . '</span><h3 class="h4">' . e(tr($x['title'] ?? [])) . '</h3><p>' . inline(tr($x['text'] ?? [])) . '</p></li>';
    }
    if (!$steps) return '';
    return '<section class="sec proc" id="process"><div class="wrap"><div class="proc-head"><div>' . sec_h($pc) . '<a class="lnk" href="' . e(url('/portal/')) . '">' . e(ui('proc.portal')) . ' ' . icon('arrow', 'ic ic-s') . '</a></div>'
         . '<figure class="proc-fig" data-spot>' . lottie('rf_build_letter', ui('proc.fig'), 'lt-build', '', ['loop' => 165]) . '<figcaption>' . e(ui('proc.fig')) . '</figcaption></figure></div>'
         . '<ol class="proc-line" data-proc>' . $steps . '</ol></div></section>';
}
/* ---------------- 2.4 · night preview (type a text, choose light + colours, switch on) ---------------- */
const PV_FACE = ['#F4F4F2' => 'white', '#C8161D' => 'red', '#F2B600' => 'yellow', '#1E4FA3' => 'blue', '#1C1D20' => 'black'];
const PV_LED = ['#FFD49A' => 'warm', '#EAF2FF' => 'cool', '#FFB547' => 'amber', '#FF3B30' => 'red', '#3D7BFF' => 'blue'];
const PV_RET = ['#1C1D20' => 'black', '#EDEDEA' => 'white', '#9AA0A6' => 'silver', '#C8161D' => 'red'];
const PV_WALL = ['brick', 'plaster', 'glass', 'panel'];
function pv_js(): string { return '<script src="/assets/js/preview.min.js?v=' . @filemtime(ROOT . '/assets/js/preview.min.js') . '" defer></script>'; }
function signpv_html(array $o): string {
    $n = $o['n'] ?? 'pv1-'; $light = $o['light'] ?? 'frontlit';
    $sw = function (string $grp, array $set, string $sel) use ($n) {
        $h = '<fieldset class="pv-sw"><legend>' . e(ui('pv.' . $grp)) . '</legend><div>';
        foreach ($set as $hex => $k) $h .= '<label class="pv-dot" style="--c:' . $hex . '"><input type="radio" name="' . $n . $grp . '" value="' . $hex . '" aria-label="' . e(ui('pv.c.' . $k)) . '"' . ($hex === $sel ? ' checked' : '') . '><span aria-hidden="true"></span><em>' . e(ui('pv.c.' . $k)) . '</em></label>';
        return $h . '</div></fieldset>';
    };
    $lo = ''; foreach (['frontlit', 'corona', 'front_corona', 'front_side', 'neon', 'unlit', 'flat', 'lightbox'] as $k) $lo .= '<option value="' . $k . '"' . ($k === $light ? ' selected' : '') . '>' . e(ui('light.' . $k)) . '</option>';
    $wl = '<fieldset class="pv-walls"><legend>' . e(ui('pv.wall')) . '</legend><div>';
    foreach (PV_WALL as $i => $w) $wl .= '<label class="pv-wall pv-wall-' . $w . '"><input type="radio" name="' . $n . 'wall" value="' . $w . '"' . ($i === 0 ? ' checked' : '') . '><span>' . e(ui('pv.w.' . $w)) . '</span></label>';
    $wl .= '<label class="pv-wall pv-wall-photo"><input type="radio" name="' . $n . 'wall" value="photo"><span>' . icon('upload', 'ic ic-xs') . e(ui('pv.w.photo')) . '</span></label></div>'
         . '<input type="file" accept="image/*" data-pv-photo hidden aria-label="' . e(ui('pv.w.photo')) . '"><small>' . e(ui('pv.photo_hint')) . '</small></fieldset>';
    $i18n = ['sample' => ui('pv.sample'), 'on' => ui('pv.on'), 'off' => ui('pv.off'), 'scale' => ui('pv.scale'), 'figure' => ui('pv.figure'), 'alt' => ui('pv.alt'), 'st_on' => ui('pv.state_on'), 'st_off' => ui('pv.state_off'), 'summary' => ui('pv.summary')];
    $act = ($o['mode'] ?? 'site') === 'portal'
        ? '<button type="button" class="btn btn-red" data-pv-attach>' . icon('clip', 'ic ic-s') . e(ui('pv.attach')) . '</button><button type="button" class="btn btn-line" data-pv-png>' . icon('download', 'ic ic-s') . e(ui('pv.png')) . '</button>'
        : '<a class="btn btn-light" data-pv-req href="' . e(url('/portal/requests/new/') . '?p=' . rawurlencode($o['slug'] ?? '') . '&pv=1') . '">' . e(ui('pv.request')) . ' ' . icon('arrow', 'ic ic-s') . '</a><button type="button" class="btn btn-line" data-pv-png>' . icon('download', 'ic ic-s') . e(ui('pv.png')) . '</button>';
    return '<div class="pv" data-pv data-n="' . e($n) . '" data-light="' . e($light) . '" data-slug="' . e($o['slug'] ?? '') . '"' . (!empty($o['auto']) ? ' data-pv-autostart' : '') . ' data-pv-i18n="' . e(json_encode($i18n, JSON_UNESCAPED_UNICODE)) . '">'
        . '<div class="pv-stage"><canvas class="pv-cv" width="1600" height="800" tabindex="0" role="img" aria-label="' . e(ui('pv.title')) . '" data-pv-cv></canvas>'
        . '<button type="button" class="pv-switch" aria-pressed="false" data-pv-night><span class="pv-led" aria-hidden="true"></span><span data-pv-sw-t>' . e(ui('pv.on')) . '</span></button>'
        . '<p class="pv-meta"><span data-pv-scale></span><span class="pv-drag">' . e(ui('pv.drag')) . '</span></p></div>'
        . '<div class="pv-ctl"><div class="pv-row"><label class="fld pv-t"><span>' . e(ui('pv.text')) . '</span><input data-pv-text maxlength="28" value="' . e($o['text'] ?? '') . '" placeholder="' . e(ui('pv.sample')) . '" autocomplete="off" spellcheck="false"></label>'
        . '<label class="fld"><span>' . e(ui('pv.light')) . '</span><select data-pv-light>' . $lo . '</select></label></div>'
        . '<div class="pv-row pv-row-3">' . $sw('face', PV_FACE, '#F4F4F2') . $sw('led', PV_LED, '#FFD49A') . $sw('ret', PV_RET, '#1C1D20') . '</div>' . $wl
        . '<div class="pv-row"><label class="fld"><span>' . e(ui('pv.size')) . '</span><input type="range" min="20" max="90" value="62" data-pv-size></label><label class="fld"><span>' . e(ui('pv.height')) . '</span><input type="number" min="20" max="3000" step="10" value="' . (int)($o['h'] ?? 400) . '" inputmode="numeric" data-pv-h></label></div>'
        . '<div class="pv-act">' . $act . '</div><p class="small pv-note">' . e(ui('pv.note')) . '</p></div></div>';
}
function page_home(): string {
    $c = page_data('home'); $h = $c['hero'] ?? [];
    $st = ''; foreach ($c['stats']['items'] ?? [] as $x) $st .= '<li><b data-count>' . e($x['value'] ?? '') . '</b><span>' . e(tr($x['label'] ?? [])) . '</span></li>';
    $sv = ''; foreach ($c['services']['items'] ?? [] as $x) { $u = $x['link_url'] ?? ''; $ic = ICON_ALIAS[$x['icon'] ?? ''] ?? ($x['icon'] ?? 'lightbox'); $sv .= '<li class="svc' . (media($x['image'] ?? null) ? ' svc-photo' : '') . '">' . (media($x['image'] ?? null) ? '<figure class="svc-img">' . img($x['image'], tr($x['title'] ?? []), '', false, '(min-width: 1100px) 25vw, (min-width: 640px) 50vw, 100vw') . '</figure>' : '') . '<span class="svc-ic">' . (isset(SVC_LOTTIE[$ic]) ? lottie(SVC_LOTTIE[$ic], '', 'lt-svc', icon($ic, 'ic ic-draw'), ['hover' => 1]) : icon($ic, 'ic ic-draw')) . '</span><h3 class="h3">' . e(tr($x['title'] ?? [])) . '</h3><p>' . inline(tr($x['text'] ?? [])) . '</p>' . ($u ? '<a class="lnk" href="' . e(link_url($u)) . '"' . ext_attr($u) . '>' . e(tr($x['link_label'] ?? [])) . ' ' . icon(ext_attr($u) ? 'ext' : 'arrow', 'ic ic-s') . '</a>' : '') . '</li>'; }
    $cl = $c['clients'] ?? []; $pr = $c['profiles'] ?? []; $sp = $c['special'] ?? []; $fa = $c['factory'] ?? []; $tm = $c['team'] ?? []; $sb = $c['subscribe'] ?? [];
    $letters = pick('profiles', $pr['letters'] ?? []); $boxes = pick('profiles', $pr['lightboxes'] ?? []);
    $mach = ''; foreach ($fa['machines'] ?? [] as $m) $mach .= '<li>' . e(tr($m['text'] ?? [])) . '</li>';
    $team = ''; foreach ($tm['gallery'] ?? [] as $id) $team .= '<li>' . img($id, ui('team_alt'), '', false, '180px') . '</li>';
    $n = count(items('profiles'));
    $slides = '<div class="slide is-on">' . img($h['image'] ?? null, '', '', true, '100vw') . '</div>'; $ns = 1;
    foreach ($h['images'] ?? [] as $id) if (media($id)) { $slides .= '<div class="slide">' . img_later($id) . '</div>'; $ns++; }
    $rail = ($pr['show'] ?? 'all') === 'all' ? array_values(items('profiles')) : array_merge($letters, $boxes);
    usort($rail, fn($a, $b) => ($a['group'] === 'letters' ? 0 : 1) <=> ($b['group'] === 'letters' ? 0 : 1));
    $body = '<section class="hero" data-spot><div class="hero-media"' . ($ns > 1 ? ' data-slides' : '') . '>' . $slides . '</div><div class="wrap hero-in">'
      . '<p class="eyebrow"><span class="lit-dot" aria-hidden="true"></span>' . e(tr($h['eyebrow'] ?? [])) . '</p><h1 class="h0" data-ignite>' . e(tr($h['title'] ?? [])) . '</h1><p class="lead">' . e(tr($h['lead'] ?? [])) . '</p>'
      . '<div class="hero-act"><a class="btn btn-light" href="' . e(url('/product-profiles/')) . '">' . e(tr($h['btn1'] ?? [])) . ' ' . icon('arrow', 'ic ic-s') . '</a><a class="btn btn-ghost-light" href="' . e(url('/projects/')) . '">' . e(tr($h['btn2'] ?? [])) . '</a></div></div>' . motion_btn('mo-hero') . '</section>'
      . ticker($c['ticker']['items'] ?? [])
      . ($st ? '<section class="stats"><div class="wrap"><ul>' . $st . '</ul></div></section>' : '')
      . '<section class="sec" id="services"><div class="wrap">' . sec_h($c['services'] ?? []) . '<ul class="svcs">' . $sv . '</ul></div></section>'
      . process_html($c['process'] ?? [])
      . '<section class="sec sec-alt" id="clients"><div class="wrap split"><div><p class="k">' . e(tr($cl['kicker'] ?? [])) . '</p><h2 class="h2">' . e(tr($cl['title'] ?? [])) . '</h2><p>' . inline(tr($cl['text'] ?? [])) . '</p>'
      . '<a class="lnk" href="' . e(url('/projects/')) . '#partners">' . e(tr($cl['link_label'] ?? [])) . ' ' . icon('arrow', 'ic ic-s') . '</a></div><figure class="clients">' . img($cl['image'] ?? null, ui('clients_alt')) . '</figure></div></section>'
      . '<section class="sec" id="profiles"><div class="wrap"><header class="sec-h sec-h-row"><div><p class="k">' . e(tr($pr['kicker'] ?? [])) . '</p><h2 class="h2">' . e(tr($pr['title'] ?? [])) . '</h2></div><a class="btn btn-line" href="' . e(url('/product-profiles/')) . '">' . e(ui('all_profiles', ['n' => $n])) . ' ' . icon('arrow', 'ic ic-s') . '</a></header>'
      . rail_html($rail) . '</div></section>'
      . '<section class="sec sec-dark" id="special" data-spot><div class="wrap">' . sec_h($sp, '', true) . '<ul class="scards">' . implode('', array_map('card_story', pick('stories', $sp['stories'] ?? []))) . '</ul>'
      . '<div class="center"><a class="btn btn-ghost-light" href="' . e(url('/projects/')) . '">' . e(tr($sp['button'] ?? [])) . ' ' . icon('arrow', 'ic ic-s') . '</a></div></div></section>'
      . '<section class="sec" id="factory"><div class="wrap"><header class="sec-h"><p class="k">' . e(tr($fa['kicker'] ?? [])) . '</p><h2 class="h2">' . e(tr($fa['title'] ?? [])) . '</h2></header><div class="split split-top"><div><p>' . inline(tr($fa['text'] ?? [])) . '</p>'
      . ($mach ? '<h3 class="h4">' . e(tr($fa['machines_title'] ?? [])) . '</h3><ul class="mach">' . $mach . '</ul>' : '') . '</div><div class="factory-side">' . lottie('rf_svc_cnc', '', 'lt-factory', '') . '</div></div></div>' . film_html($fa['gallery'] ?? [], 'Reclame Fabriek') . '</section>'
      . ($team ? '<section class="sec sec-alt" id="team"><div class="wrap"><header class="sec-h"><p class="k">' . e(tr($tm['kicker'] ?? [])) . '</p><h2 class="h2">' . e(tr($tm['title'] ?? [])) . '</h2></header></div><div class="team-wrap"><ul class="team" tabindex="0" aria-label="' . e(ui('team_scroll')) . '">' . $team . '</ul></div></section>' : '')
      . '<section class="sec" id="subscribe"><div class="wrap news"><div><p class="k">' . e(tr($sb['kicker'] ?? [])) . '</p><h2 class="h2">' . e(tr($sb['title'] ?? [])) . '</h2><p>' . inline(tr($sb['text'] ?? [])) . '</p></div>'
      . '<form class="news-form" action="/api/contact" method="post" data-form><input type="hidden" name="type" value="subscribe"><input type="hidden" name="lang" value="' . lang() . '"><label class="hp" aria-hidden="true">' . e(ui('f.leave')) . '<input name="website" tabindex="-1" autocomplete="off"></label>'
      . '<label class="fld"><span>' . e(ui('f.work_email')) . '</span><input type="email" name="email" required autocomplete="email" placeholder="name@company.com"></label><button class="btn btn-dark" type="submit">' . e(ui('f.subscribe')) . '</button><p class="form-msg" role="status" data-form-msg></p></form></div></section>'
      . cta_band($c['cta'] ?? []);
    return layout(tr($c['seo']['title'] ?? []), tr($c['seo']['description'] ?? []), '/', $body, ['og' => media_big($h['image'] ?? null) ?: null, 'preload' => $h['image'] ?? null]);
}

function page_profiles(): string {
    $c = page_data('product-profiles'); $all = items('profiles');
    $letters = array_filter($all, fn($p) => $p['group'] === 'letters'); $boxes = array_filter($all, fn($p) => $p['group'] === 'lightbox');
    $kinds = []; foreach ($letters as $p) $kinds[$p['light']] = true;
    $chips = ''; foreach (array_keys($kinds) as $k) $chips .= '<button type="button" class="chip" data-filter-kind="' . e($k) . '" aria-pressed="false">' . icon(light_icon($k), 'ic ic-s') . e(ui('light.' . $k)) . '</button>';
    $rows = ''; foreach ($c['common']['rows'] ?? [] as $r) $rows .= '<dt>' . e(tr($r['label'] ?? [])) . '</dt><dd>' . inline(tr($r['text'] ?? [])) . '</dd>';
    $GLOBALS['NAV_CUR'] = '/product-profiles/';
    $ph = phead_media($c['head'] ?? []);
    $body = '<section class="phead' . ($ph ? ' phead-dark phead-photo' : '') . '">' . $ph . '<div class="wrap">' . crumbs([[ui('nav.profiles'), null]]) . '<h1 class="h1">' . e(tr($c['head']['title'] ?? [])) . '</h1><p class="lead-s">' . inline(tr($c['head']['lead'] ?? [])) . '</p></div></section>'
      . '<section class="sec sec-tight"><div class="wrap" data-filter><div class="filters" role="group" aria-label="' . e(ui('filter.label')) . '">'
      . '<button type="button" class="chip" data-filter-group="all" aria-pressed="true">' . e(ui('filter.all')) . ' <small>' . count($all) . '</small></button>'
      . '<button type="button" class="chip" data-filter-group="letters" aria-pressed="false">' . e(ui('box_letters')) . ' <small>' . count($letters) . '</small></button>'
      . '<button type="button" class="chip" data-filter-group="lightbox" aria-pressed="false">' . e(ui('lightboxes')) . ' <small>' . count($boxes) . '</small></button><span class="filters-sep" aria-hidden="true"></span>' . $chips . '</div>'
      . '<p class="filter-count" role="status" data-filter-count></p>'
      . ($letters ? '<h2 class="h4 sub" data-grp-h="letters">' . e(ui('box_letters')) . '</h2><ul class="pcards">' . implode('', array_map('card_profile', $letters)) . '</ul>' : '')
      . ($boxes ? '<h2 class="h4 sub" data-grp-h="lightbox">' . e(ui('lightboxes')) . '</h2><ul class="pcards">' . implode('', array_map('card_profile', $boxes)) . '</ul>' : '') . '</div></section>'
      . ($rows ? '<section class="sec sec-alt"><div class="wrap split"><div><p class="k">' . e(tr($c['common']['kicker'] ?? [])) . '</p><h2 class="h2">' . e(tr($c['common']['title'] ?? [])) . '</h2></div><dl class="specs specs-wide">' . $rows . '</dl></div></section>' : '')
      . cta_band($c['cta'] ?? []);
    return layout(ui('nav.profiles'), tr($c['seo']['description'] ?? []), '/product-profiles/', $body);
}

function page_profile(array $p): string {
    $t = tr($p['title']); $sub = tr($p['subtitle'] ?? []); $box = $p['group'] === 'lightbox'; $grp = $box ? ui('lightboxes') : ui('box_letters');
    $rows = '';
    if (!empty($p['size'])) $rows .= '<dt>' . e(ui('letter_height')) . '</dt><dd>' . e(mm($p['size'])) . '</dd>';
    if (!empty($p['depth'])) $rows .= '<dt>' . e(ui('depths')) . '</dt><dd>' . e(mm($p['depth'])) . '<br><small>' . e(ui(($p['custom_depth'] ?? true) ? 'custom_depth' : 'no_custom_depth')) . '</small></dd>';
    foreach ($p['specs'] ?? [] as $s) $rows .= '<dt>' . e(tr($s['label'] ?? [])) . '</dt><dd>' . inline(tr($s['value'] ?? [])) . '</dd>';
    if ($rows) $rows .= '<dt>' . e(ui('mounting')) . '</dt><dd>' . inline(ui('mount_text')) . '</dd>';
    $note = tr($p['note'] ?? []);
    $ral = settings()['ral_image'] ?? '';
    $spec = ($rows ? '<dl class="specs">' . $rows . '</dl><h3 class="h4">' . e(ui('customisation')) . '</h3><p class="small">' . inline(ui('custom_text')) . '</p>' : '')
          . (media($ral) ? '<figure class="ral">' . img($ral, ui('ral_alt'), '', false, '320px') . '<figcaption>' . e(ui('ral_title')) . '</figcaption></figure>' : '')
          . (($note || !$rows) ? '<div class="note">' . rich($note ?: ui('lightbox_note')) . '</div>' : '');
    $others = array_values(array_filter(items('profiles'), fn($o) => $o['group'] === $p['group'] && $o['slug'] !== $p['slug']));
    usort($others, fn($a, $b) => (int)($a['light'] !== $p['light']) <=> (int)($b['light'] !== $p['light']));
    $vid = !empty($p['video']) ? video_html($p['video'], $p['cover'] ?? null, $t) : '';
    $gal = gallery_html($p['gallery'] ?? [], $t);
    $lit = is_lit($p['light'] ?? 'unlit');
    $GLOBALS['NAV_CUR'] = '/product-profiles/';
    $body = '<section class="phead phead-prod"><div class="wrap prod"><div class="prod-txt">' . crumbs([[ui('nav.profiles'), '/product-profiles/'], [$t, null]])
      . '<p class="k">' . e($grp) . '</p><h1 class="h1">' . e($t) . '</h1><p class="lead-s">' . e($sub) . '</p><p><span class="tag' . ($lit ? ' tag-lit' : '') . '">' . ($lit ? icon('bolt', 'ic ic-xs') : '') . e(ui('light.' . ($p['light'] ?? 'unlit'))) . '</span></p>'
      . '<div class="hero-act"><a class="btn btn-dark" href="' . e(url('/contact/')) . '?profile=' . e(rawurlencode($p['slug'])) . '#quote">' . e(ui('ask_price')) . ' ' . icon('arrow', 'ic ic-s') . '</a>'
      . '<button type="button" class="btn btn-line" data-basket-add="' . e($p['slug']) . '" aria-pressed="false">' . icon('plus', 'ic ic-s') . '<span>' . e(ui('basket.add')) . '</span></button></div></div>'
      . '<figure class="prod-img">' . img($p['cover'] ?? null, "$t, $sub", '', true) . '</figure></div></section>'
      . '<section class="sec sec-tight"><div class="wrap split split-top"><div><h2 class="h3">' . e(ui('spec')) . '</h2>' . $spec . '</div><div>'
      . '<figure class="lightcard">' . lottie('rf_light_' . ($p['light'] ?? 'unlit'), ui('light.' . ($p['light'] ?? 'unlit')), 'lt-light', '<span class="lt-fb">' . icon(light_icon($p['light'] ?? 'unlit'), 'ic ic-xl') . '</span>', ['loop' => 60]) . '<figcaption><span class="k k-lit">' . e(ui('how_lights')) . '</span><b>' . e(ui('light.' . ($p['light'] ?? 'unlit'))) . '</b></figcaption></figure>'
      . (media($p['diagram'] ?? null) ? '<figure class="diagram">' . img($p['diagram'], ui('construction') . ': ' . $t, '', false, '(min-width: 1100px) 40vw, 100vw') . '<figcaption><span class="k">' . e(ui('construction')) . '</span></figcaption></figure>' : '')
      . ($vid ? '<h2 class="h3">' . e(ui('see_made')) . '</h2>' . $vid : '') . '</div></div></section>' . anatomy_html($p)
      . '<section class="sec sec-dark pv-sec" id="preview" data-spot><div class="wrap"><header class="sec-h"><p class="k k-lit">' . e(ui('pv.kicker')) . '</p><h2 class="h2">' . e(ui('pv.title')) . '</h2><p class="lead-s">' . e(ui('pv.lead')) . '</p></header>' . signpv_html(['light' => $box ? 'lightbox' : ($p['light'] ?? 'frontlit'), 'slug' => $p['slug'], 'auto' => true]) . '</div></section>'
      . ($gal ? '<section class="sec sec-alt"><div class="wrap"><h2 class="h3">' . e(ui('made_with', ['x' => $t])) . '</h2>' . $gal . '</div></section>' : '')
      . ($others ? '<section class="sec"><div class="wrap"><header class="sec-h sec-h-row"><h2 class="h3">' . e($box ? ui('other_lightboxes') : ui('other_letters')) . '</h2><a class="lnk" href="' . e(url('/product-profiles/')) . '">' . e(ui('all_profiles_short')) . ' ' . icon('arrow', 'ic ic-s') . '</a></header><ul class="pcards">' . implode('', array_map('card_profile', array_slice($others, 0, 4))) . '</ul></div></section>' : '')
      . cta_band();
    $desc = tr($p['seo']['description'] ?? []) ?: "$t: $sub." . (!empty($p['size']) ? ' ' . ui('letter_height') . ' ' . mm($p['size']) . '.' : '') . ' Reclame Fabriek, Daugavpils.';
    return layout($t, $desc, '/' . $p['slug'] . '/', $body, ['og' => media_big($p['cover'] ?? null) ?: null, 'js' => pv_js()]);
}

function page_projects(): string {
    $c = page_data('projects'); $f = $c['film'] ?? []; $g = $c['gallery'] ?? []; $pa = $c['partners'] ?? [];
    $GLOBALS['NAV_CUR'] = '/projects/';
    $ph = phead_media($c['head'] ?? []);
    $body = '<section class="phead phead-dark' . ($ph ? ' phead-photo' : '') . '">' . $ph . '<div class="wrap">' . crumbs([[ui('nav.projects'), null]]) . '<p class="k k-lit">' . e(tr($c['head']['kicker'] ?? [])) . '</p><h1 class="h1">' . e(tr($c['head']['title'] ?? [])) . '</h1><p class="lead-s">' . inline(tr($c['head']['lead'] ?? [])) . '</p></div></section>'
      . '<section class="sec"><div class="wrap">' . sec_h($c['stories'] ?? []) . '<ul class="scards scards-light">' . implode('', array_map('card_story', items('stories'))) . '</ul></div></section>'
      . (!empty($f['video']) ? '<section class="sec sec-alt"><div class="wrap split split-top"><div><p class="k">' . e(tr($f['kicker'] ?? [])) . '</p><h2 class="h2">' . e(tr($f['title'] ?? [])) . '</h2><p>' . inline(tr($f['text'] ?? [])) . '</p></div>' . video_html($f['video'], $f['poster'] ?? null, 'Reclame Fabriek') . '</div></section>' : '')
      . (!empty($g['images']) ? '<section class="sec"><div class="wrap">' . sec_h($g) . gallery_html($g['images'], 'Reclame Fabriek') . '</div></section>' : '')
      . '<section class="sec sec-alt" id="partners"><div class="wrap split"><div><p class="k">' . e(tr($pa['kicker'] ?? [])) . '</p><h2 class="h2">' . e(tr($pa['title'] ?? [])) . '</h2><p>' . inline(tr($pa['text'] ?? [])) . '</p></div><figure class="clients">' . img($pa['image'] ?? null, ui('clients_alt')) . '</figure></div></section>'
      . cta_band($c['cta'] ?? []);
    return layout(ui('nav.projects'), tr($c['seo']['description'] ?? []), '/projects/', $body);
}

function page_story(array $s): string {
    $fb = false; $bodyText = tr($s['body'] ?? [], null, $fb);
    $fbNote = ($fb && $bodyText) ? '<p class="note-lang">' . e(ui('story_en_only')) . '</p>' : '';
    $ims = array_values(array_filter($s['gallery'] ?? [], fn($x) => $x !== ($s['cover'] ?? '')));
    $all = array_values(items('stories')); $i = 0; foreach ($all as $k => $x) if ($x['slug'] === $s['slug']) $i = $k;
    $nxt = $all ? $all[($i + 1) % count($all)] : null;
    $GLOBALS['NAV_CUR'] = '/projects/';
    $t = tr($s['title']);
    $body = '<article><section class="phead phead-story"><div class="wrap">' . crumbs([[ui('nav.projects'), '/projects/'], [ui('project_story'), null]]) . '<p class="k">' . e(tr($s['kicker'] ?? [])) . '</p><h1 class="h1">' . e($t) . '</h1></div></section>'
      . '<figure class="story-cover wrap">' . img($s['cover'] ?? null, $t, '', true, '(min-width: 1200px) 1200px, 100vw') . '</figure>'
      . '<section class="sec sec-tight"><div class="wrap story"><div class="prose"' . ($fb ? ' lang="' . e(default_lang()) . '"' : '') . '>' . $fbNote . rich($bodyText) . '</div><aside class="story-side">'
      . (!empty($s['video']) ? '<h2 class="h4">' . e(ui('video')) . '</h2>' . video_html($s['video'], $ims[0] ?? ($s['cover'] ?? null), $t) : '')
      . ($ims ? '<h2 class="h4">' . e(ui('photos')) . '</h2>' . gallery_html($ims, $t, 'gal gal-2') : '') . '</aside></div></section></article>'
      . ($nxt && $nxt['slug'] !== $s['slug'] ? '<section class="sec sec-alt"><div class="wrap nextp"><p class="k">' . e(ui('next_story')) . '</p><a class="nextp-a" href="' . e(url('/' . $nxt['slug'] . '/')) . '"><span class="h3">' . e(tr($nxt['title'])) . '</span>' . icon('arrow') . '</a></div></section>' : '')
      . cta_band(page_data('projects')['cta'] ?? []);
    $desc = tr($s['seo']['description'] ?? []) ?: mb_substr(strip_tags(preg_replace('/\*\*/', '', explode("\n", $bodyText)[0])), 0, 155);
    return layout($t, $desc, '/' . $s['slug'] . '/', $body, ['og' => media_big($s['cover'] ?? null) ?: null]);
}

function page_research(): string {
    $c = page_data('signage-research'); $in = $c['intro'] ?? []; $m = $c['materials'] ?? []; $lg = $c['lumigrid'] ?? [];
    $tl = ''; foreach ($m['items'] ?? [] as $i => $x) $tl .= '<li class="tech"><span class="tech-h"><span class="tech-n">' . sprintf('%02d', $i + 1) . '</span>' . icon($x['icon'] ?? 'spark', 'ic ic-l ic-draw') . '</span><h3 class="h3">' . e(tr($x['title'] ?? [])) . '</h3><p>' . inline(tr($x['text'] ?? [])) . '</p></li>';
    $u = $lg['url'] ?? '';
    $GLOBALS['NAV_CUR'] = '/signage-research/';
    $ph = phead_media($c['head'] ?? []);
    $body = '<section class="phead phead-dark' . ($ph ? ' phead-photo' : '') . '">' . $ph . '<div class="wrap">' . crumbs([[ui('nav.research'), null]]) . '<p class="k k-lit">' . e(tr($c['head']['kicker'] ?? [])) . '</p><h1 class="h1">' . e(tr($c['head']['title'] ?? [])) . '</h1><p class="lead-s">' . inline(tr($c['head']['lead'] ?? [])) . '</p></div></section>'
      . '<section class="sec"><div class="wrap split split-top"><div class="prose"><h2 class="h2">' . e(tr($in['title'] ?? [])) . '</h2>' . rich(tr($in['body'] ?? [])) . '</div>' . gallery_html($in['gallery'] ?? [], 'Signage Research', 'gal gal-2') . '</div></section>'
      . '<section class="sec sec-alt"><div class="wrap">' . sec_h($m) . (tr($m['tech_title'] ?? []) ? '<div class="tech-intro"><h3 class="h3">' . e(tr($m['tech_title'])) . '</h3><p>' . inline(tr($m['tech_text'] ?? [])) . '</p></div>' : '') . '<ul class="techs" data-wire>' . $tl . '</ul></div></section>'
      . '<section class="sec sec-dark" data-spot><div class="wrap split"><div>' . lottie('rf_lumigrid', 'LUMIGRID', 'lt-grid', '', ['loop' => 0]) . '<p class="k k-lit">' . e(tr($lg['kicker'] ?? [])) . '</p><h2 class="h2">' . e(tr($lg['title'] ?? [])) . '</h2><p>' . inline(tr($lg['text'] ?? [])) . '</p>'
      . ($u ? '<a class="btn btn-ghost-light" href="' . e(link_url($u)) . '"' . ext_attr($u) . '>' . e(tr($lg['button'] ?? [])) . ' ' . icon(str_starts_with($u, '/') ? 'arrow' : 'ext', 'ic ic-s') . '</a>' : '') . '</div><div><h2 class="h3">' . e(tr($lg['title2'] ?? [])) . '</h2><p>' . inline(tr($lg['text2'] ?? [])) . '</p></div></div></section>'
      . cta_band($c['cta'] ?? []);
    return layout(ui('nav.research'), tr($c['seo']['description'] ?? []), '/signage-research/', $body);
}

/* 2.3 · contact: neon sign + local time in Daugavpils + "open now" (only when the admin has set working hours) */
function contact_live(): string {
    $s = settings(); $from = (string)($s['hours_from'] ?? ''); $to = (string)($s['hours_to'] ?? ''); $days = (string)($s['hours_days'] ?? '1-5');
    $cfg = preg_match('/^\d{2}:\d{2}$/', $from) && preg_match('/^\d{2}:\d{2}$/', $to) ? ['from' => $from, 'to' => $to, 'days' => $days] : null;
    return '<div class="live" data-live data-tz="Europe/Riga"' . ($cfg ? ' data-hours="' . e(json_encode($cfg)) . '"' : '') . ' data-open="' . e(ui('live.open')) . '" data-closed="' . e(ui('live.closed')) . '">'
        . '<div class="live-sign">' . lottie('rf_neon_open', '', 'lt-open', '', ['loop' => 90]) . '</div>'
        . '<div class="live-t"><p class="k">' . e(ui('live.local')) . '</p><p class="live-clock"><time data-live-clock>--:--</time> <span>Daugavpils · UTC+2/+3</span></p>'
        . ($cfg ? '<p class="live-st" data-live-st></p><p class="small">' . e(ui('live.hours', ['from' => $from, 'to' => $to, 'days' => ui('live.days_' . str_replace('-', '_', $days))])) . '</p>' : '') . '</div></div>';
}
function page_contact(): string {
    $c = page_data('contact'); $s = settings();
    $cards = ''; foreach ($s['contacts'] ?? [] as $x) { $tel = preg_replace('/[^\d+]/', '', $x['phone'] ?? ''); $cards .= '<li class="ccard">' . icon('quote', 'ic ic-l ic-draw') . '<p class="k">' . e(tr($x['role'] ?? [])) . '</p><h3 class="h3">' . e($x['name'] ?? '') . '</h3>' . ($tel ? '<a href="tel:' . e($tel) . '">' . icon('phone', 'ic ic-s') . e($x['phone']) . '</a>' : '') . (!empty($x['email']) ? '<a href="mailto:' . e($x['email']) . '">' . icon('mail', 'ic ic-s') . e($x['email']) . '</a>' : '') . '</li>'; }
    $opts = ''; foreach (items('profiles') as $p) $opts .= '<option value="' . e($p['slug']) . '">' . e(tr($p['title'])) . '</option>';
    $opts .= '<option value="cubemobel">' . e(ui('f.furniture')) . '</option><option value="cubecnc">' . e(ui('f.cnc')) . '</option><option value="research">' . e(ui('f.special')) . '</option>';
    $GLOBALS['NAV_CUR'] = '/contact/';
    $ph = phead_media($c['head'] ?? []);
    $body = '<section class="phead' . ($ph ? ' phead-dark phead-photo' : '') . '">' . $ph . '<div class="wrap">' . crumbs([[ui('nav.contact'), null]]) . '<h1 class="h1">' . e(tr($c['head']['title'] ?? [])) . '</h1><p class="lead-s">' . inline(tr($c['head']['lead'] ?? [])) . '</p></div></section>'
      . '<section class="sec sec-tight"><div class="wrap split split-top"><form class="form" id="quote" action="/api/contact" method="post" data-form><input type="hidden" name="type" value="quote"><input type="hidden" name="lang" value="' . lang() . '">'
      . '<label class="hp" aria-hidden="true">' . e(ui('f.leave')) . '<input name="website" tabindex="-1" autocomplete="off"></label>'
      . '<div class="row2"><label class="fld"><span>' . e(ui('f.name')) . ' *</span><input name="name" required autocomplete="name"></label><label class="fld"><span>' . e(ui('f.company')) . '</span><input name="company" autocomplete="organization"></label></div>'
      . '<div class="row2"><label class="fld"><span>' . e(ui('f.email')) . ' *</span><input type="email" name="email" required autocomplete="email"></label><label class="fld"><span>' . e(ui('f.phone')) . '</span><input type="tel" name="phone" autocomplete="tel"></label></div>'
      . '<label class="fld"><span>' . e(ui('f.profile')) . '</span><select name="profile" data-profile><option value="">' . e(ui('f.unsure')) . '</option>' . $opts . '</select></label>'
      . '<label class="fld"><span>' . e(ui('f.message')) . '</span><textarea name="message" rows="6" placeholder="' . e(ui('f.placeholder')) . '"></textarea></label>'
      . '<p class="small">' . inline(ui('f.consent', ['privacy' => url('/privacy-policy/')])) . '</p><button class="btn btn-red" type="submit">' . e(ui('f.send')) . ' ' . icon('arrow', 'ic ic-s') . '</button><p class="form-msg" role="status" data-form-msg></p></form>'
      . '<div>' . contact_live() . '<ul class="ccards">' . $cards . '</ul><div class="map" data-map><div class="map-ph"><p>' . nl2br(e(tr($s['address'] ?? [])), false) . '</p><button class="btn btn-line" type="button" data-map-load>' . icon('pin', 'ic ic-s') . e(ui('map_show')) . '</button><small>' . e(ui('map_note')) . '</small></div></div></div></div></section>';
    return layout(ui('nav.contact'), tr($c['seo']['description'] ?? []), '/contact/', $body, ['cta' => false]);
}

function page_privacy(): string {
    $c = page_data('privacy-policy');
    $body = '<section class="phead"><div class="wrap">' . crumbs([[ui('privacy'), null]]) . '<h1 class="h1">' . e(tr($c['head']['title'] ?? []) ?: ui('privacy')) . '</h1></div></section><section class="sec sec-tight"><div class="wrap prose prose-narrow">' . rich(tr($c['body']['text'] ?? [])) . '</div></section>';
    return layout(ui('privacy'), tr($c['seo']['description'] ?? []), '/privacy-policy/', $body);
}

function page_404(): string {
    $body = '<section class="phead nf"><div class="wrap nf-in"><div>' . lottie('rf_switched_off', '', 'lt-off', '') . '</div><div><p class="k">404</p><h1 class="h1">' . e(ui('nf.title')) . '</h1><p class="lead-s">' . e(ui('nf.text')) . '</p><div class="hero-act"><a class="btn btn-dark" href="' . e(url('/')) . '">' . e(ui('nf.home')) . '</a><a class="btn btn-line" href="' . e(url('/product-profiles/')) . '">' . e(ui('nav.profiles')) . '</a></div></div></section>';
    return layout('404', ui('nf.text'), '/', $body, ['noindex' => true]);
}

/* 2.3 · search index for the command palette (per language, small, cached by the browser for an hour) */
function search_json(): string {
    $o = []; $lightName = fn($k) => ui('light.' . $k);
    foreach (items('profiles') as $sl => $p) {
        $o[] = ['k' => 'profile', 't' => tr($p['title']), 's' => tr($p['subtitle'] ?? []), 'u' => url('/' . $sl . '/'), 'i' => media_src($p['cover'] ?? null, 400),
                'x' => trim(($p['light'] ?? '') . ' ' . $lightName($p['light'] ?? '') . ' ' . ($p['depth'] ?? '') . ' ' . ($p['group'] === 'lightbox' ? ui('lightboxes') : ui('box_letters')) . ' ' . tr($p['title'], 'en')), 'l' => $p['light'] ?? '', 'ln' => $lightName($p['light'] ?? ''), 'g' => $p['group'] ?? '', 'd' => mm((string)($p['depth'] ?? '')), 'z' => mm((string)($p['size'] ?? '')), 'slug' => $sl,
                'cd' => (bool)($p['custom_depth'] ?? true), 'sp' => array_values(array_map(fn($x) => [tr($x['label'] ?? []), tr($x['value'] ?? [])], array_filter($p['specs'] ?? [], fn($x) => tr($x['label'] ?? []) !== '')))];
    }
    foreach (items('stories') as $sl => $st) $o[] = ['k' => 'story', 't' => tr($st['title']), 's' => tr($st['kicker'] ?? []), 'u' => url('/' . $sl . '/'), 'i' => media_src($st['cover'] ?? null, 400), 'x' => tr($st['title'], 'en')];
    foreach ([['/', ui('home'), 'factory'], ['/product-profiles/', ui('nav.profiles'), 'box-letter'], ['/projects/', ui('nav.projects'), 'award'], ['/signage-research/', ui('nav.research'), 'lab'], ['/lumigrid/', ui('nav.lumigrid'), 'controller'], ['/contact/', ui('nav.contact'), 'phone'], ['/privacy-policy/', ui('privacy'), 'shield']] as [$u, $t, $ic])
        $o[] = ['k' => 'page', 't' => $t, 'u' => url($u), 'svg' => icon($ic, 'ic ic-s')];
    $o[] = ['k' => 'action', 't' => ui('cta.quote'), 'u' => url('/contact/') . '#quote', 'svg' => icon('send', 'ic ic-s')];
    $o[] = ['k' => 'action', 't' => ui('nav.portal'), 'u' => url('/portal/'), 'svg' => icon('user', 'ic ic-s')];
    $o[] = ['k' => 'action', 't' => ui('p.new_request'), 'u' => url('/portal/requests/new/'), 'svg' => icon('plus', 'ic ic-s')];
    foreach (['light' => 'sun', 'dark' => 'moon', 'auto' => 'auto'] as $k => $ic) $o[] = ['k' => 'action', 't' => ui('theme.label') . ': ' . ui('theme.' . $k), 'theme' => $k, 'svg' => icon($ic, 'ic ic-s')];
    foreach (langs() as $x) if ($x !== lang()) $o[] = ['k' => 'action', 't' => LANG_NAMES[$x] . ' · ' . strtoupper($x), 'lang' => $x, 'svg' => icon('globe', 'ic ic-s')];
    return json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function sitemap_xml(): string {
    $paths = array_values(PAGE_PATHS);
    foreach (items('profiles') as $p) $paths[] = '/' . $p['slug'] . '/';
    foreach (items('stories') as $s) $paths[] = '/' . $s['slug'] . '/';
    $o = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
    foreach ($paths as $p) foreach (langs() as $l) {
        $o .= '<url><loc>' . e(abs_url($p, $l)) . '</loc>';
        foreach (langs() as $x) $o .= '<xhtml:link rel="alternate" hreflang="' . $x . '" href="' . e(abs_url($p, $x)) . '"/>';
        $o .= "</url>\n";
    }
    return $o . '</urlset>';
}

/* ---------------- 2.5 · LUMIGRID: the system + the LEDNode controller (facts from the LUMIGRID firmware 1.0.0-rc2) ---------------- */
const LG_PWM_OK = ['solid', 'breathe', 'reveal', 'strobe', 'aurora', 'staticpulse', 'candle', 'heartbeat', 'lightning'];   // firmware: pwm_recommended
function lg_board_svg(array $n): string {
    // LEDNode, drawn after the firmware pin map: A1–A8 addressable (RMT), P1–P8 PWM (PCA9685), ESP32 module, Wi-Fi.
    $a = ''; $p = '';
    for ($i = 0; $i < 8; $i++) {
        $x = 92 + $i * 54;
        $a .= '<g class="lgb-t" style="--i:' . $i . '"><rect x="' . ($x - 18) . '" y="40" width="36" height="30" rx="3" class="lgb-term"/><circle cx="' . $x . '" cy="88" r="5" class="lgb-led lgb-led-a"/><text x="' . $x . '" y="28" class="lgb-l">A' . ($i + 1) . '</text></g>';
        $p .= '<g class="lgb-t" style="--i:' . ($i + 8) . '"><rect x="' . ($x - 18) . '" y="330" width="36" height="30" rx="3" class="lgb-term"/><circle cx="' . $x . '" cy="312" r="5" class="lgb-led lgb-led-p"/><text x="' . $x . '" y="384" class="lgb-l">P' . ($i + 1) . '</text></g>';
    }
    return '<figure class="lg-board" data-lg-board><svg viewBox="0 0 560 400" role="img" aria-label="' . e(tr($n['figure'] ?? [])) . '">'
        . '<rect x="40" y="56" width="480" height="288" rx="14" class="lgb-pcb"/>'
        . '<g class="lgb-traces">' . implode('', array_map(fn($i) => '<path pathLength="1" d="M' . (92 + $i * 54) . ' 94 V' . (150 + ($i % 4) * 6) . ' H' . (226 + $i * 14) . ' V170"/><path pathLength="1" d="M' . (92 + $i * 54) . ' 306 V' . (262 - ($i % 4) * 6) . ' H' . (226 + $i * 14) . ' V246"/>', range(0, 7))) . '</g>'
        . '<rect x="214" y="166" width="132" height="84" rx="4" class="lgb-chip"/><text x="280" y="203" class="lgb-c">ESP32</text><text x="280" y="224" class="lgb-cs">8 MB</text>'
        . '<rect x="388" y="176" width="72" height="56" rx="3" class="lgb-pca"/><text x="424" y="209" class="lgb-cs">PCA9685</text>'
        . '<g class="lgb-wifi" transform="translate(110 205)"><path d="M-22 -6 a31 31 0 0 1 44 0" /><path d="M-14 2 a20 20 0 0 1 28 0"/><circle cx="0" cy="10" r="3.5"/></g>'
        . '<text x="64" y="118" class="lgb-g">' . e(tr($n['strips'] ?? [])) . '</text><text x="64" y="292" class="lgb-g">' . e(tr($n['dimmers'] ?? [])) . '</text>'
        . '<g class="lgb-terms">' . $a . $p . '</g></svg></figure>';
}
function page_lumigrid(): string {
    $c = page_data('lumigrid'); $h = $c['head'] ?? []; $sy = $c['system'] ?? []; $n = $c['node'] ?? []; $lb = $c['lab'] ?? []; $sh = $c['show'] ?? []; $su = $c['setup'] ?? []; $lo = $c['local'] ?? []; $ct = $c['cta'] ?? [];
    $GLOBALS['NAV_CUR'] = '/lumigrid/';
    $st = ''; foreach ($c['stats']['items'] ?? [] as $x) $st .= '<li><b data-count>' . e($x['value'] ?? '') . '</b><span>' . e(tr($x['label'] ?? [])) . '</span></li>';
    $flow = ''; foreach (array_values($sy['items'] ?? []) as $i => $x) $flow .= '<li class="lg-step" style="--i:' . $i . '"><span class="lg-step-ic">' . icon($x['icon'] ?? 'spark', 'ic ic-l ic-draw') . '</span><span class="lg-step-n" aria-hidden="true">' . sprintf('%02d', $i + 1) . '</span><h3 class="h4">' . e(tr($x['title'] ?? [])) . '</h3><p>' . inline(tr($x['text'] ?? [])) . '</p></li>';
    $specs = ''; foreach ($n['specs'] ?? [] as $x) $specs .= '<div><dt>' . e(tr($x['label'] ?? [])) . '</dt><dd>' . e(tr($x['value'] ?? [])) . '</dd></div>';
    $cards = fn(array $items) => implode('', array_map(fn($x) => '<li class="lg-card">' . icon($x['icon'] ?? 'spark', 'ic ic-l ic-draw') . '<h3 class="h4">' . e(tr($x['title'] ?? [])) . '</h3><p>' . inline(tr($x['text'] ?? [])) . '</p></li>', $items));
    $steps = ''; foreach (array_values($su['items'] ?? []) as $i => $x) $steps .= '<li><span class="lg-num" aria-hidden="true">' . ($i + 1) . '</span><div><h3 class="h4">' . e(tr($x['title'] ?? [])) . '</h3><p>' . inline(tr($x['text'] ?? [])) . '</p></div></li>';
    // effect lab: names from the admin, behaviour from the firmware
    $fx = []; $chips = '';
    foreach (array_values($lb['effects'] ?? []) as $i => $x) {
        $id = (string)($x['id'] ?? ''); if ($id === '') continue;
        $fx[] = ['id' => $id, 'n' => tr($x['name'] ?? []), 't' => tr($x['text'] ?? []), 'pwm' => in_array($id, LG_PWM_OK, true)];
        $chips .= '<button type="button" class="lg-fx" data-fx="' . e($id) . '" aria-pressed="' . ($id === 'gradient' ? 'true' : 'false') . '">' . e(tr($x['name'] ?? [])) . (in_array($id, LG_PWM_OK, true) ? '<i class="lg-pwm" aria-hidden="true"></i>' : '') . '</button>';
    }
    $pn = array_map('trim', explode(',', tr($lb['palette_names'] ?? []))); $pe = array_map('trim', explode(',', tr($lb['palette_names'] ?? [], 'en')));
    $pals = ''; for ($i = 0; $i < 14; $i++) $pals .= '<button type="button" class="lg-pal lg-pal-' . $i . '" data-pal="' . $i . '" aria-pressed="' . ($i === 6 ? 'true' : 'false') . '"><span class="lg-sw" aria-hidden="true"></span>' . e(($pn[$i] ?? '') ?: (($pe[$i] ?? '') ?: '#' . $i)) . '</button>';
    $cfg = json_encode(['fx' => $fx, 'yes' => tr($lb['l_pwm_yes'] ?? []), 'no' => tr($lb['l_pwm_no'] ?? []), 'strips' => tr($lb['l_strips'] ?? []), 'dimmers' => tr($lb['l_dimmers'] ?? [])], JSON_UNESCAPED_UNICODE);
    $first = $fx[array_search('gradient', array_column($fx, 'id')) ?: 0] ?? ['n' => '', 't' => '', 'pwm' => false];
    $lab = '<section class="sec lg-lab" id="lab" data-lg-lab data-cfg="' . e($cfg) . '"><div class="wrap">' . sec_h($lb, '', true)
        . '<div class="lg-lab-in"><div class="lg-stage"><canvas data-lg-stage width="960" height="560" role="img" aria-label="' . e(tr($lb['l_canvas'] ?? [])) . '"></canvas>'
        . '<p class="lg-now" aria-live="polite" data-lg-now><b data-lg-name>' . e($first['n']) . '</b> <span data-lg-desc>' . e($first['t']) . '</span> <em class="lg-tagpwm" data-lg-pwm>' . e(tr($first['pwm'] ? ($lb['l_pwm_yes'] ?? []) : ($lb['l_pwm_no'] ?? []))) . '</em></p></div>'
        . '<div class="lg-ctl"><fieldset><legend class="k">' . e(tr($lb['l_effect'] ?? [])) . '</legend><div class="lg-fxs">' . $chips . '</div><p class="small lg-key"><i class="lg-pwm" aria-hidden="true"></i> ' . e(tr($lb['l_pwm_yes'] ?? [])) . '</p></fieldset>'
        . '<fieldset><legend class="k">' . e(tr($lb['l_palette'] ?? [])) . '</legend><div class="lg-pals">' . $pals . '</div></fieldset>'
        . '<label class="lg-speed"><span class="k">' . e(tr($lb['l_speed'] ?? [])) . '</span><input type="range" min="0" max="100" value="50" data-lg-speed></label></div></div>'
        . '<p class="small lg-note">' . inline(tr($lb['note'] ?? [])) . '</p></div></section>';
    $gh = (string)($ct['docs_url'] ?? '');
    $body = '<section class="lg-hero" data-lg-hero><div class="wrap lg-hero-in">' . crumbs([[ui('nav.research'), '/signage-research/'], ['LUMIGRID', null]])
        . '<p class="k k-lit">' . e(tr($h['kicker'] ?? [])) . '</p><div class="lg-mark"><h1 class="h0 lg-h1">' . e(tr($h['title'] ?? []) ?: 'LUMIGRID') . '</h1><canvas class="lg-word" data-lg-word aria-hidden="true"></canvas></div>'
        . '<p class="lg-tag">' . e(tr($h['tagline'] ?? [])) . '</p><p class="lead">' . inline(tr($h['lead'] ?? [])) . '</p>'
        . '<div class="hero-act"><a class="btn btn-light" href="' . e(url('/contact/')) . '?profile=research#quote">' . e(tr($h['button'] ?? [])) . ' ' . icon('arrow', 'ic ic-s') . '</a><a class="btn btn-ghost-light" href="#node">' . e(tr($h['button2'] ?? [])) . '</a></div></div>' . motion_btn('mo-hero') . '</section>'
        . ($st ? '<section class="stats"><div class="wrap"><ul>' . $st . '</ul></div></section>' : '')
        . '<section class="sec" id="system"><div class="wrap">' . sec_h($sy) . '<ol class="lg-flow" data-lg-flow>' . $flow . '</ol></div></section>'
        . '<section class="sec sec-dark" id="node" data-spot><div class="wrap split lg-node"><div>' . lg_board_svg($n) . '</div><div><p class="k k-lit">' . e(tr($n['kicker'] ?? [])) . '</p><h2 class="h2">' . e(tr($n['title'] ?? [])) . '</h2><p>' . inline(tr($n['text'] ?? [])) . '</p>'
        . '<dl class="lg-specs">' . $specs . '</dl><p class="small lg-onote">' . inline(tr($n['note'] ?? [])) . '</p></div></div></section>'
        . $lab
        . '<section class="sec" id="show"><div class="wrap">' . sec_h($sh) . '<ul class="lg-cards">' . $cards($sh['items'] ?? []) . '</ul></div></section>'
        . '<section class="sec sec-alt" id="setup"><div class="wrap split split-top"><div>' . sec_h($su) . '</div><ol class="lg-setup">' . $steps . '</ol></div></section>'
        . '<section class="sec" id="local"><div class="wrap">' . sec_h($lo) . '<ul class="lg-cards lg-cards-4">' . $cards($lo['items'] ?? []) . '</ul></div></section>'
        . '<section class="band band-dark" data-spot><div class="wrap band-in"><div><h2 class="h2">' . e(tr($ct['title'] ?? [])) . '</h2><p>' . e(tr($ct['text'] ?? [])) . '</p></div>'
        . '<div class="band-act"><a class="btn btn-light" href="' . e(url('/contact/')) . '?profile=research#quote">' . e(tr($ct['button'] ?? [])) . ' ' . icon('arrow', 'ic ic-s') . '</a>'
        . ($gh ? '<a class="btn btn-ghost-light" href="' . e($gh) . '"' . ext_attr($gh) . '>' . e(tr($ct['docs'] ?? [])) . ' ' . icon('ext', 'ic ic-s') . '</a>' : '') . '</div></div></section>';
    $v = fn($f) => @filemtime(ROOT . '/assets/' . $f);
    $css = is_file(ROOT . '/assets/css/lumigrid.min.css') ? 'lumigrid.min.css' : 'lumigrid.css'; $js = is_file(ROOT . '/assets/js/lumigrid.min.js') ? 'lumigrid.min.js' : 'lumigrid.js';
    return layout(tr($c['seo']['title'] ?? []) ?: 'LUMIGRID', tr($c['seo']['description'] ?? []), '/lumigrid/', $body,
        ['css' => '<link rel="stylesheet" href="/assets/css/' . $css . '?v=' . $v('css/' . $css) . '">', 'js' => '<script src="/assets/js/' . $js . '?v=' . $v('js/' . $js) . '" defer></script>', 'body' => 'pg-lumigrid']);
}
