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
    $i18n = json_encode(['basketAdd' => ui('basket.add'), 'basketIn' => ui('basket.in'), 'portal' => url('/portal/'), 'showing' => ui('filter.showing'), 'sending' => ui('f.sending'), 'ok' => ui('f.ok'), 'subOk' => ui('f.sub_ok'), 'err' => ui('f.err'), 'errLink' => ui('f.err_link'), 'mailTo' => $s['form_to'] ?? '', 'mapsQ' => $s['maps_query'] ?? '', 'mapTitle' => 'Map: Reclame Fabriek'], JSON_UNESCAPED_UNICODE);
    $robots = !empty($o['noindex']) ? '<meta name="robots" content="noindex">' : '';
    $preview = !empty($GLOBALS['PREVIEW']) ? '<div class="preview-bar">Предпросмотр: страница не опубликована</div>' : '';
    $pre = '';
    if (!empty($o['preload']) && ($m = media($o['preload']))) { $set = []; foreach ($m['sizes'] as $z) $set[] = '/uploads/' . $m['file'] . '-' . $z . '.webp ' . min($z, (int)$m['w']) . 'w'; $pre = '<link rel="preload" as="image" imagesrcset="' . e(implode(', ', $set)) . '" imagesizes="100vw" fetchpriority="high">'; }
    $mo = $s['motion'] ?? 'full';
    $vt = $mo === 'full' ? '<style>@media (prefers-reduced-motion:no-preference){@view-transition{navigation:auto}}</style>' : '';
    $css = is_file(ROOT . '/assets/css/site.min.css') ? 'site.min.css' : 'site.css'; $js = is_file(ROOT . '/assets/js/site.min.js') ? 'site.min.js' : 'site.js';
    return '<!doctype html><html lang="' . $l . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
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

function lang_switch(string $path): string {
    if (count(langs()) < 2) return '';
    $li = '';
    foreach (langs() as $x) $li .= '<li><a href="' . e(url($path, $x)) . '" hreflang="' . $x . '" lang="' . $x . '"' . ($x === lang() ? ' aria-current="true"' : '') . '><b>' . strtoupper($x) . '</b> ' . e(LANG_NAMES[$x]) . '</a></li>';
    return '<details class="lang" data-lang><summary aria-label="' . e(ui('lang')) . '">' . icon('globe', 'ic ic-s') . '<span>' . strtoupper(lang()) . '</span></summary><ul>' . $li . '</ul></details>';
}

function header_html(string $path, bool $cta): string {
    $s = settings();
    $nav = [[ui('nav.profiles'), url('/product-profiles/'), '/product-profiles/'], [ui('nav.projects'), url('/projects/'), '/projects/'],
            [ui('nav.research'), url('/signage-research/'), '/signage-research/'], [ui('nav.lumigrid'), $s['lumigrid_url'] ?? '', ''], [ui('nav.contact'), url('/contact/'), '/contact/']];
    $items = '';
    $cur = $GLOBALS['NAV_CUR'] ?? '';
    foreach ($nav as [$lbl, $href, $key]) {
        if (!$href) continue;
        $ext = !$key;
        $items .= '<li><a href="' . e($href) . '"' . ($key && $key === $cur ? ' aria-current="page"' : '') . ($ext ? ' target="_blank" rel="noopener"' : '') . '>' . e($lbl) . ($ext ? icon('ext', 'ic ic-s') : '') . '</a></li>';
    }
    return '<a class="skip" href="#main">' . e(ui('skip')) . '</a><header class="hdr" data-hdr><div class="wrap hdr-in">'
        . '<a class="brand" href="' . e(url('/')) . '" aria-label="Reclame Fabriek, ' . e(ui('home')) . '"><img src="/assets/brand/rf-logo-88.webp" width="44" height="44" alt="" fetchpriority="high"><span class="brand-name"><b>Reclame Fabriek</b><i>' . e(ui('brand.tag')) . '</i></span></a>'
        . '<nav class="nav" aria-label="Main" id="nav"><ul>' . $items . '</ul></nav>' . lang_switch($path)
        . '<a class="hdr-portal" href="' . e(url('/portal/')) . '" data-basket-link>' . icon('user', 'ic ic-s') . '<span class="hdr-portal-t">' . e(ui('nav.portal')) . '</span><b class="basket-n" data-basket-n hidden>0</b></a>'
        . ($cta ? '<a class="btn btn-red hdr-cta" href="' . e(url('/contact/')) . '#quote">' . e(ui('cta.quote')) . '</a>' : '')
        . '<button class="nav-btn" type="button" aria-controls="nav" aria-expanded="false" data-nav-btn><span class="sr">' . e(ui('menu')) . '</span>' . icon('menu', 'ic ic-open') . icon('close', 'ic ic-close') . '</button></div><div class="progress" aria-hidden="true"></div></header>';
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
        . '<span class="pcard-body"><' . $h . ' class="pcard-t">' . e($t) . '</' . $h . '><span class="pcard-s">' . e($sub) . '</span><span class="tag' . ($lit ? ' tag-lit' : '') . '">' . ($lit ? icon('bolt', 'ic ic-xs') : '') . e(ui('light.' . $k)) . '</span></span></a></li>';
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
      . ($gal ? '<section class="sec sec-alt"><div class="wrap"><h2 class="h3">' . e(ui('made_with', ['x' => $t])) . '</h2>' . $gal . '</div></section>' : '')
      . ($others ? '<section class="sec"><div class="wrap"><header class="sec-h sec-h-row"><h2 class="h3">' . e($box ? ui('other_lightboxes') : ui('other_letters')) . '</h2><a class="lnk" href="' . e(url('/product-profiles/')) . '">' . e(ui('all_profiles_short')) . ' ' . icon('arrow', 'ic ic-s') . '</a></header><ul class="pcards">' . implode('', array_map('card_profile', array_slice($others, 0, 4))) . '</ul></div></section>' : '')
      . cta_band();
    $desc = tr($p['seo']['description'] ?? []) ?: "$t: $sub." . (!empty($p['size']) ? ' ' . ui('letter_height') . ' ' . mm($p['size']) . '.' : '') . ' Reclame Fabriek, Daugavpils.';
    return layout($t, $desc, '/' . $p['slug'] . '/', $body, ['og' => media_big($p['cover'] ?? null) ?: null]);
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
      . ($u ? '<a class="btn btn-ghost-light" href="' . e($u) . '"' . ext_attr($u) . '>' . e(tr($lg['button'] ?? [])) . ' ' . icon('ext', 'ic ic-s') . '</a>' : '') . '</div><div><h2 class="h3">' . e(tr($lg['title2'] ?? [])) . '</h2><p>' . inline(tr($lg['text2'] ?? [])) . '</p></div></div></section>'
      . cta_band($c['cta'] ?? []);
    return layout(ui('nav.research'), tr($c['seo']['description'] ?? []), '/signage-research/', $body);
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
      . '<div><ul class="ccards">' . $cards . '</ul><div class="map" data-map><div class="map-ph"><p>' . nl2br(e(tr($s['address'] ?? [])), false) . '</p><button class="btn btn-line" type="button" data-map-load>' . icon('pin', 'ic ic-s') . e(ui('map_show')) . '</button><small>' . e(ui('map_note')) . '</small></div></div></div></div></section>';
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
