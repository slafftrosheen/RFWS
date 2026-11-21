<?php
/* Reclame Fabriek CMS · core helpers shared by the website and the admin. No database: content lives in /data as JSON. */
declare(strict_types=1);
mb_internal_encoding('UTF-8');
define('ROOT', dirname(__DIR__));
define('DATA', ROOT . '/data');
define('UPLOADS', ROOT . '/uploads');
const LANG_NAMES = ['en' => 'English', 'lv' => 'Latviešu', 'ru' => 'Русский', 'nl' => 'Nederlands', 'de' => 'Deutsch', 'sv' => 'Svenska'];
const LANG_NAMES_RU = ['en' => 'Английский', 'lv' => 'Латышский', 'ru' => 'Русский', 'nl' => 'Нидерландский', 'de' => 'Немецкий', 'sv' => 'Шведский'];
const LIGHT_KINDS = ['frontlit', 'corona', 'neon', 'front_corona', 'front_side', 'unlit', 'flat', 'lightbox'];
const PAGE_PATHS = ['home' => '/', 'product-profiles' => '/product-profiles/', 'projects' => '/projects/', 'signage-research' => '/signage-research/', 'contact' => '/contact/', 'privacy-policy' => '/privacy-policy/'];

/* ---------- JSON storage (atomic write + history) ---------- */
function jread(string $rel, $default = null) {
    $f = DATA . '/' . $rel;
    if (!is_file($f)) return $default;
    $j = json_decode((string)file_get_contents($f), true);
    return $j ?? $default;
}
function jwrite(string $rel, $data, bool $history = true): void {
    $f = DATA . '/' . $rel;
    if (!is_dir(dirname($f))) mkdir(dirname($f), 0775, true);
    if ($history && is_file($f)) {
        $h = DATA . '/_history/' . str_replace('/', '__', preg_replace('/\.json$/', '', $rel));
        if (!is_dir($h)) mkdir($h, 0775, true);
        copy($f, $h . '/' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 4) . '.json');
        $old = glob($h . '/*.json') ?: [];
        sort($old);
        while (count($old) > 30) @unlink(array_shift($old));
    }
    $tmp = $f . '.tmp-' . bin2hex(random_bytes(4));
    file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    rename($tmp, $f);
    cache_bust();
    if ($rel[0] !== '_' || str_starts_with($rel, '_auth/db')) page_cache_clear();   // content changed (not just an inbox / login record)
}
function cache_bust(): void { $GLOBALS['__cache'] = []; }
/** Drop every cached page (called after any content change). */
function page_cache_clear(): void { foreach (glob(DATA . '/_cache/*.html') ?: [] as $f) @unlink($f); }
function cached(string $k, callable $fn) { return $GLOBALS['__cache'][$k] ??= $fn(); }

function settings(): array { return cached('settings', fn() => jread('settings.json', [])); }
function media_all(): array { return cached('media', fn() => jread('media.json', [])); }
function ui_all(): array { return cached('ui', fn() => jread('ui.json', [])); }
function page_data(string $id): array { return cached("page:$id", fn() => jread("pages/$id.json", [])); }

function items(string $type, bool $publishedOnly = true): array {
    $all = cached("items:$type", function () use ($type) {
        $out = [];
        foreach (glob(DATA . "/$type/*.json") ?: [] as $f) { $j = json_decode((string)file_get_contents($f), true); if ($j) $out[$j['slug']] = $j; }
        uasort($out, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        return $out;
    });
    return $publishedOnly ? array_filter($all, fn($x) => !empty($x['published'])) : $all;
}
function item(string $type, string $slug): ?array { return items($type, false)[$slug] ?? null; }
function valid_slug(string $s): bool { return (bool)preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $s) && strlen($s) <= 90; }

/* ---------- languages ---------- */
function langs(): array {
    $s = settings(); $out = [];
    foreach ($s['languages'] ?? [] as $l) if (!empty($l['enabled']) && isset(LANG_NAMES[$l['code']])) $out[] = $l['code'];
    return $out ?: ['en'];
}
function default_lang(): string { $d = settings()['default_lang'] ?? 'en'; return in_array($d, langs(), true) ? $d : langs()[0]; }
function lang(): string { return $GLOBALS['LANG'] ?? default_lang(); }

/** Pick the current language from a translatable field; falls back to the default language, then any. */
function tr($v, ?string $l = null, ?bool &$fallback = null): string {
    $fallback = false;
    if (is_string($v)) return $v;
    if (!is_array($v)) return '';
    $l ??= lang();
    if (isset($v[$l]) && trim((string)$v[$l]) !== '') return (string)$v[$l];
    $fallback = true;
    $d = default_lang();
    if (isset($v[$d]) && trim((string)$v[$d]) !== '') return (string)$v[$d];
    foreach ($v as $x) if (is_string($x) && trim($x) !== '') return $x;
    return '';
}
function ui(string $k, array $vars = []): string {
    $s = tr(ui_all()[$k] ?? $k);
    foreach ($vars as $a => $b) $s = str_replace('{' . $a . '}', (string)$b, $s);
    return $s;
}
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Small, safe text format: blank line = new paragraph, **bold**, [text](link). Everything else is escaped. */
function inline(string $t): string {
    $t = e($t);
    $t = preg_replace('/\*\*(.+?)\*\*/u', '<b>$1</b>', $t);
    $t = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/u', function ($m) {
        $u = html_entity_decode($m[2], ENT_QUOTES);
        if (!preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $u)) return $m[1];
        $ext = preg_match('#^https?://#i', $u) && !str_contains($u, 'reclamefabriek.eu');
        return '<a href="' . e($u) . '"' . ($ext ? ' target="_blank" rel="noopener"' : '') . '>' . $m[1] . '</a>';
    }, $t);
    return nl2br($t, false);
}
function rich(string $t): string {
    $out = '';
    foreach (preg_split('/\R{2,}/u', trim($t)) as $p) if (trim($p) !== '') $out .= '<p>' . inline(trim($p)) . '</p>';
    return $out;
}

/* ---------- URLs ---------- */
function url(string $path = '/', ?string $l = null): string {
    $l ??= lang();
    if ($l === default_lang()) return $path;
    return '/' . $l . $path;
}
function site_origin(): string {
    $origin = getenv('RF_SITE_URL');
    return rtrim($origin !== false && $origin !== '' ? $origin : (settings()['site_url'] ?? ''), '/');
}
function abs_url(string $path, ?string $l = null): string { return site_origin() . url($path, $l); }
function mm(string $s): string { return lang() === 'ru' ? str_replace('mm', 'мм', $s) : $s; }

/* ---------- media ---------- */
function media(?string $id): ?array { return $id ? (media_all()[$id] ?? null) : null; }
function media_src(?string $id, int $want = 800): string {
    $m = media($id); if (!$m) return '';
    $s = $m['sizes']; sort($s);
    $pick = $s[0];
    foreach ($s as $x) { $pick = $x; if ($x >= $want) break; }
    return '/uploads/' . $m['file'] . '-' . $pick . '.webp';
}
function media_big(?string $id): string { $m = media($id); return $m ? '/uploads/' . $m['file'] . '-' . max($m['sizes']) . '.webp' : ''; }
function img(?string $id, string $alt = '', string $cls = '', bool $eager = false, string $sizes = '(min-width: 1100px) 50vw, 100vw'): string {
    $m = media($id); if (!$m) return '';
    $alt = tr($m['alt'] ?? []) ?: $alt;
    $set = []; $w = (int)$m['w']; $h = (int)$m['h'];
    foreach ($m['sizes'] as $s) $set[] = '/uploads/' . $m['file'] . '-' . $s . '.webp ' . min($s, $w) . 'w';
    $dw = min(max($m['sizes']), $w); $dh = $w ? (int)round($h * $dw / $w) : 0;
    return '<img src="' . e(media_src($id)) . '" srcset="' . e(implode(', ', $set)) . '" sizes="' . e($sizes) . '" width="' . $dw . '" height="' . $dh . '" alt="' . e($alt) . '"'
        . ($cls ? ' class="' . e($cls) . '"' : '') . ($eager ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async">';
}
function youtube_id(string $v): string {
    $v = trim($v);
    if (preg_match('~(?:v=|youtu\.be/|embed/|shorts/)([A-Za-z0-9_-]{6,})~', $v, $m)) return $m[1];
    return preg_match('/^[A-Za-z0-9_-]{6,20}$/', $v) ? $v : '';
}

/* ---------- where is a media file used (for the library) ---------- */
function media_usage(): array {
    $use = [];
    $walk = function ($v, string $where) use (&$walk, &$use) {
        if (is_string($v)) { if (isset(media_all()[$v])) $use[$v][$where] = true; return; }
        if (is_array($v)) foreach ($v as $x) $walk($x, $where);
    };
    foreach (array_keys(PAGE_PATHS) as $p) $walk(page_data($p), 'page:' . $p);
    foreach (items('profiles', false) as $s => $x) $walk($x, 'profiles:' . $s);
    foreach (items('stories', false) as $s => $x) $walk($x, 'stories:' . $s);
    $walk(settings()['partner_logos'] ?? [], 'settings:logos');
    return array_map('array_keys', $use);
}
