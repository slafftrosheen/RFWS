<?php
/* Reclame Fabriek CMS · admin helpers (auth, forms, media). Interface language: Russian. */
declare(strict_types=1);

/* ================= session + auth ================= */
function admin_session(): void {
    $https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('rfadmin');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
    session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    if (!empty($_SESSION['uid']) && (time() - ($_SESSION['seen'] ?? 0)) > 8 * 3600) { $_SESSION = []; session_regenerate_id(true); }
    $_SESSION['seen'] = time();
    header('X-Frame-Options: DENY'); header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none'");
    header('Cache-Control: no-store');
}
function users(): array { return jread('_auth/users.json', []); }
function me(): ?array { $u = users(); return isset($_SESSION['uid'], $u[$_SESSION['uid']]) ? ['login' => $_SESSION['uid']] + $u[$_SESSION['uid']] : null; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e($_SESSION['csrf']) . '">'; }
function check_csrf(): void {
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
    if (!is_string($t) || !hash_equals($_SESSION['csrf'], $t)) { http_response_code(400); exit('Сессия устарела. Обновите страницу и попробуйте снова.'); }
}
function throttle_key(): string { return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|login'); }
function login_blocked(): int {
    $t = jread('_auth/throttle.json', [])[throttle_key()] ?? null;
    if (!$t) return 0;
    return ($t['n'] >= 5 && $t['until'] > time()) ? $t['until'] - time() : 0;
}
function login_fail(): void {
    $all = jread('_auth/throttle.json', []); $k = throttle_key(); $t = $all[$k] ?? ['n' => 0, 'until' => 0];
    if ($t['until'] < time() && $t['n'] >= 5) $t = ['n' => 0, 'until' => 0];
    $t['n']++; if ($t['n'] >= 5) $t['until'] = time() + 600;
    $all[$k] = $t; jwrite('_auth/throttle.json', $all, false);
}
function login_ok(string $login): void {
    $all = jread('_auth/throttle.json', []); unset($all[throttle_key()]); jwrite('_auth/throttle.json', $all, false);
    session_regenerate_id(true); $_SESSION['uid'] = $login; $_SESSION['csrf'] = bin2hex(random_bytes(16));
    $u = users(); $u[$login]['last_login'] = date('c'); jwrite('_auth/users.json', $u, false);
}
function setup_code(): string {
    $f = DATA . '/_auth/setup-code.txt';
    if (!is_file($f)) { if (!is_dir(dirname($f))) mkdir(dirname($f), 0770, true); file_put_contents($f, strtoupper(bin2hex(random_bytes(5))) . "\n"); }
    return trim((string)file_get_contents($f));
}

/* ================= flash + redirects ================= */
function flash(string $msg, string $tone = 'ok'): void { $_SESSION['flash'][] = [$tone, $msg]; }
function go(string $q): never { header('Location: /admin/' . ($q ? '?' . $q : '')); exit; }
function aurl(string $q = ''): string { return '/admin/' . ($q ? '?' . $q : ''); }

/* ================= translation helpers ================= */
function is_i18n($v): bool { return is_array($v) && $v !== [] && !array_is_list($v) && !array_diff(array_keys($v), array_keys(LANG_NAMES)); }
function site_langs(): array { return langs(); }
/** Counts translatable texts (with English filled) and how many exist per language. */
function tstats($data, array &$acc): void {
    if (is_i18n($data)) {
        if (trim((string)($data['en'] ?? '')) === '') return;
        $acc['_total'] = ($acc['_total'] ?? 0) + 1;
        foreach (LANG_NAMES as $l => $_) if (trim((string)($data[$l] ?? '')) !== '') $acc[$l] = ($acc[$l] ?? 0) + 1;
        return;
    }
    if (is_array($data)) foreach ($data as $k => $v) if (!in_array($k, ['updated'], true)) tstats($v, $acc);
}
function missing_langs($data): array {
    $acc = []; tstats($data, $acc); $out = [];
    foreach (site_langs() as $l) if (($acc[$l] ?? 0) < ($acc['_total'] ?? 0)) $out[$l] = ($acc['_total'] ?? 0) - ($acc[$l] ?? 0);
    return $out;
}
function lang_dots($data): string {
    $miss = missing_langs($data); $o = '<span class="dots">';
    foreach (site_langs() as $l) $o .= '<i class="' . (isset($miss[$l]) ? 'no' : 'ok') . '" title="' . e(LANG_NAMES_RU[$l] . (isset($miss[$l]) ? ': не переведено полей — ' . $miss[$l] : ': всё переведено')) . '">' . strtoupper($l) . '</i>';
    return $o . '</span>';
}

/* ================= form rendering ================= */
function fname(array $path): string { $n = array_shift($path); foreach ($path as $p) $n .= '[' . $p . ']'; return $n; }
function field_html(string $key, array $f, $val, array $path, int $depth = 0): string {
    $name = fname($path); $id = 'f_' . substr(md5($name), 0, 10);
    $hint = !empty($f['hint']) ? '<small class="hint">' . e($f['hint']) . '</small>' : '';
    $label = '<span class="lab">' . e($f['label']) . (!empty($f['required']) ? ' <em>*</em>' : '') . '</span>';
    $t = $f['type'];
    if ($t === 'note') return '<p class="fnote">' . e($f['label']) . '</p>';
    if (!empty($f['i18n'])) {
        $val = is_array($val) ? $val : ['en' => (string)$val];
        $en = (string)($val['en'] ?? '');
        $o = '<div class="fld i18n" data-type="' . $t . '">' . $label;
        foreach (LANG_NAMES as $l => $ln) {
            $v = (string)($val[$l] ?? ''); $n = $name . '[' . $l . ']'; $active = in_array($l, site_langs(), true);
            $o .= '<div class="li' . ($active ? '' : ' li-off') . '" data-l="' . $l . '"><span class="lcode" title="' . e(LANG_NAMES_RU[$l]) . '">' . strtoupper($l) . '</span>';
            if ($t === 'text') $o .= '<input type="text" name="' . e($n) . '" value="' . e($v) . '" lang="' . $l . '"' . (!empty($f['max']) ? ' maxlength="' . (int)$f['max'] . '"' : '') . (($l === 'en' && !empty($f['required'])) ? ' required' : '') . '>';
            else $o .= ($t === 'rich' ? '<div class="rt-bar"><button type="button" data-rt="b" title="Жирный">Ж</button><button type="button" data-rt="a" title="Ссылка">Ссылка</button><span>Пустая строка — новый абзац</span></div>' : '')
                . '<textarea name="' . e($n) . '" rows="' . (int)($f['rows'] ?? ($t === 'rich' ? 5 : 3)) . '" lang="' . $l . '"' . ($t === 'rich' ? ' data-rich' : '') . (!empty($f['max']) ? ' maxlength="' . (int)$f['max'] . '"' : '') . '>' . e($v) . '</textarea>';
            if ($l !== 'en' && $en !== '') $o .= '<details class="ref"><summary>Оригинал (EN)</summary><p>' . nl2br(e($en)) . '</p></details>';
            if ($v === '' && $en !== '' && $l !== 'en') $o .= '<span class="miss">нет перевода</span>';
            $o .= '</div>';
        }
        return $o . $hint . '</div>';
    }
    switch ($t) {
        case 'text': case 'url': case 'date': case 'slug':
            $type = $t === 'date' ? 'date' : 'text';
            return '<label class="fld" for="' . $id . '">' . $label . '<input id="' . $id . '" type="' . $type . '" name="' . e($name) . '" value="' . e((string)$val) . '"' . ($t === 'url' ? ' inputmode="url" spellcheck="false"' : '') . ($t === 'slug' ? ' pattern="[a-z0-9]+(-[a-z0-9]+)*" spellcheck="false" required' : '') . '>' . $hint . '</label>';
        case 'video':
            $yid = youtube_id((string)$val);
            return '<label class="fld" for="' . $id . '">' . $label . '<input id="' . $id . '" type="text" name="' . e($name) . '" value="' . e((string)$val) . '" placeholder="Ссылка на YouTube или код видео" spellcheck="false">'
                . ($yid ? '<a class="small-link" href="https://www.youtube.com/watch?v=' . e($yid) . '" target="_blank" rel="noopener">Открыть видео на YouTube ↗</a>' : '') . $hint . '</label>';
        case 'checkbox':
            return '<label class="chk"><input type="hidden" name="' . e($name) . '" value="0"><input type="checkbox" name="' . e($name) . '" value="1"' . ($val ? ' checked' : '') . '> ' . e($f['label']) . '</label>';
        case 'icon':
            $cur = ['letters' => 'box-letter', 'star' => 'spark', 'box' => 'lightbox'][(string)$val] ?? (string)$val; $o = '';
            foreach (RF_ICON_GROUPS as $g => $names) { $o .= '<span class="ig-h">' . e(ICON_GROUPS_RU[$g] ?? $g) . '</span>';
                foreach ($names as $n) $o .= '<label class="ig-i" title="' . e($n) . '"><input type="radio" name="' . e($name) . '" value="' . e($n) . '"' . ($n === $cur ? ' checked' : '') . '><svg viewBox="0 0 24 24" aria-hidden="true">' . RF_ICONS[$n] . '</svg><span class="sr">' . e($n) . '</span></label>'; }
            return '<fieldset class="fld iconf"><legend class="lab">' . e($f['label']) . '</legend><details><summary><span class="ig-cur"><svg viewBox="0 0 24 24" aria-hidden="true">' . (RF_ICONS[$cur] ?? '') . '</svg></span> Выбрать значок</summary><div class="ig">' . $o . '</div></details>' . $hint . '</fieldset>';
        case 'select':
            $opts = $f['options']; $o = '';
            foreach ($opts as $k => $l) $o .= '<option value="' . e($k) . '"' . ((string)$val === (string)$k ? ' selected' : '') . '>' . e($l) . '</option>';
            return '<label class="fld" for="' . $id . '">' . $label . '<select id="' . $id . '" name="' . e($name) . '">' . $o . '</select>' . $hint . '</label>';
        case 'image':
            $m = media((string)$val);
            return '<div class="fld">' . $label . '<div class="imgf" data-image><input type="hidden" name="' . e($name) . '" value="' . e((string)$val) . '">'
                . '<div class="imgf-prev">' . ($m ? '<img src="' . e(media_src((string)$val, 800)) . '" alt="">' : '<span>Нет фото</span>') . '</div>'
                . '<div class="imgf-act"><button type="button" class="b b-line" data-pick="one">Выбрать</button><button type="button" class="b b-ghost" data-clear>Убрать</button></div></div>' . $hint . '</div>';
        case 'gallery':
            $ids = is_array($val) ? $val : []; $th = '';
            foreach ($ids as $mid) if (media($mid)) $th .= gal_thumb($mid);
            return '<div class="fld">' . $label . '<div class="galf" data-gallery data-name="' . e($name) . '"><input type="hidden" name="' . e($name) . '" value="' . e(implode(',', $ids)) . '">'
                . '<ul class="galf-list" data-sortable>' . $th . '</ul><div class="galf-act"><button type="button" class="b b-line" data-pick="many">+ Добавить фото</button><span class="galf-n">' . count($ids) . ' фото · перетаскивайте, чтобы менять порядок</span></div></div>' . $hint . '</div>';
        case 'profiles': case 'stories':
            $type = $t; $sel = is_array($val) ? $val : []; $all = items($type, false);
            if (!empty($f['group'])) $all = array_filter($all, fn($x) => ($x['group'] ?? '') === $f['group']);
            $li = '';
            foreach ($sel as $s) if (isset($all[$s])) $li .= pick_row($s, $all[$s], true);
            foreach ($all as $s => $x) if (!in_array($s, $sel, true)) $li .= pick_row($s, $x, false);
            return '<div class="fld">' . $label . '<ul class="pickl" data-sortable>' . str_replace('__NAME__', e($name), $li) . '</ul><small class="hint">Отметьте, что показывать, и перетащите в нужном порядке.</small></div>';
        case 'list':
            $rows = '';
            foreach (array_values(is_array($val) ? $val : []) as $i => $row) $rows .= list_row($f, $row, array_merge($path, [(string)$i]), $depth);
            $tpl = list_row($f, [], array_merge($path, ['__i' . $depth . '__']), $depth);
            return '<div class="fld listf" data-list data-depth="' . $depth . '">' . $label . '<ol class="listf-rows" data-sortable>' . $rows . '</ol><template>' . $tpl . '</template>'
                . '<button type="button" class="b b-line" data-add>+ Добавить ' . e($f['item'] ?? 'строку') . '</button>' . $hint . '</div>';
    }
    return '';
}
function gal_thumb(string $mid): string { return '<li draggable="true" data-id="' . e($mid) . '"><img src="' . e(media_src($mid, 800)) . '" alt=""><button type="button" class="x" data-remove title="Убрать из галереи">×</button></li>'; }
function pick_row(string $slug, array $x, bool $on): string {
    return '<li draggable="true"><label><input type="checkbox" name="__NAME__[]" value="' . e($slug) . '"' . ($on ? ' checked' : '') . '><span class="th">' . (media($x['cover'] ?? null) ? '<img src="' . e(media_src($x['cover'], 800)) . '" alt="">' : '') . '</span>'
        . e(tr($x['title'] ?? [], 'ru') ?: $slug) . (empty($x['published']) ? ' <em class="draft">черновик</em>' : '') . '</label><span class="grip" aria-hidden="true">⋮⋮</span></li>';
}
function list_row(array $f, $row, array $path, int $depth): string {
    $o = '<li class="lrow" draggable="true"><div class="lrow-h"><span class="grip" title="Перетащите">⋮⋮</span><span class="lrow-t"></span><button type="button" class="b-ic" data-up title="Выше">↑</button><button type="button" class="b-ic" data-down title="Ниже">↓</button><button type="button" class="b-ic b-del" data-del title="Удалить">×</button></div><div class="lrow-b">';
    foreach ($f['fields'] as $k => $sub) $o .= field_html($k, $sub, $row[$k] ?? null, array_merge($path, [$k]), $depth + 1);
    return $o . '</div></li>';
}
function form_sections(array $schema, array $data, string $root = 'c'): string {
    $o = '';
    foreach ($schema['sections'] as $sk => $sec) {
        $flat = !empty($sec['flat']); $base = $flat ? [$root] : [$root, $sk]; $vals = $flat ? $data : ($data[$sk] ?? []);
        if ($sk === 'seo' && $flat) { $base = [$root, 'seo']; $vals = $data['seo'] ?? []; }
        $body = '';
        foreach ($sec['fields'] as $k => $f) $body .= field_html($k, $f, $vals[$k] ?? null, array_merge($base, [$k]));
        $open = ($sec['open'] ?? true) ? ' open' : '';
        $o .= '<details class="card sec"' . $open . ' id="s-' . e($sk) . '"><summary><h2>' . e($sec['label']) . '</h2>' . lang_dots($vals) . '</summary><div class="sec-b">' . $body . '</div></details>';
    }
    return $o;
}

/* ================= sanitising posted values against the schema ================= */
function clean_val(array $f, $in, $old) {
    $t = $f['type'];
    if ($t === 'note') return null;
    if (!empty($f['i18n'])) {
        $out = is_array($old) ? $old : [];
        if (is_array($in)) foreach (LANG_NAMES as $l => $_) if (array_key_exists($l, $in)) {
            $v = str_replace("\r\n", "\n", (string)$in[$l]); $v = $t === 'text' ? trim(preg_replace('/\s+/u', ' ', $v)) : trim($v);
            if (!empty($f['max'])) $v = mb_substr($v, 0, (int)$f['max']);
            if ($v === '') unset($out[$l]); else $out[$l] = $v;
        }
        return $out;
    }
    switch ($t) {
        case 'checkbox': return (bool)(is_array($in) ? end($in) : $in);
        case 'select': return array_key_exists((string)$in, $f['options']) ? (string)$in : array_key_first($f['options']);
        case 'icon': return isset(RF_ICONS[(string)$in]) ? (string)$in : 'spark';
        case 'image': $v = (string)$in; return media($v) ? $v : null;
        case 'gallery': return array_values(array_unique(array_filter(explode(',', (string)$in), fn($x) => media($x) !== null)));
        case 'profiles': case 'stories': $all = items($t, false); return array_values(array_unique(array_filter(is_array($in) ? $in : [], fn($x) => isset($all[$x]))));
        case 'url': $v = trim((string)$in); return ($v === '' || preg_match('#^(https?://|/|mailto:|tel:)#i', $v)) ? $v : 'https://' . $v;
        case 'video': return trim((string)$in);
        case 'date': return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$in) ? (string)$in : '';
        case 'list':
            $out = [];
            foreach ((is_array($in) ? $in : []) as $i => $row) {
                $r = [];
                foreach ($f['fields'] as $k => $sub) { $v = clean_val($sub, $row[$k] ?? null, null); if ($v !== null) $r[$k] = $v; }
                if (array_filter($r, fn($v) => $v !== '' && $v !== [] && $v !== null)) $out[] = $r;
            }
            return $out;
        default: return trim(mb_substr((string)$in, 0, 2000));
    }
}
function clean_sections(array $schema, array $in, array $old): array {
    $out = $old;
    foreach ($schema['sections'] as $sk => $sec) {
        $flat = !empty($sec['flat']);
        $src = ($flat && $sk !== 'seo') ? $in : ($in[$sk] ?? []); $dst = ($flat && $sk !== 'seo') ? $out : ($out[$sk] ?? []);
        foreach ($sec['fields'] as $k => $f) { $v = clean_val($f, $src[$k] ?? null, $dst[$k] ?? null); if ($v !== null) $dst[$k] = $v; }
        if ($flat && $sk !== 'seo') $out = $dst; else $out[$sk] = $dst;
    }
    return $out;
}

/* ================= media ingest ================= */
function media_ingest(string $tmp, string $orig, ?string $replaceId = null) {
    @ini_set('memory_limit', '768M');
    $info = @getimagesize($tmp);
    if (!$info) return 'Это не изображение (поддерживаются JPG, PNG, WEBP, GIF).';
    [$w, $h, $type] = $info;
    if ($w * $h > 60000000) return 'Изображение слишком большое (больше 60 мегапикселей).';
    $im = match ($type) { IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp), IMAGETYPE_PNG => @imagecreatefrompng($tmp), IMAGETYPE_WEBP => @imagecreatefromwebp($tmp), IMAGETYPE_GIF => @imagecreatefromgif($tmp), default => false };
    if (!$im) return 'Не удалось прочитать файл. Сохраните его как JPG или PNG и попробуйте ещё раз.';
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $ex = @exif_read_data($tmp);
        $rot = [3 => 180, 6 => -90, 8 => 90][(int)($ex['Orientation'] ?? 1)] ?? 0;
        if ($rot) { $im = imagerotate($im, $rot, 0); $w = imagesx($im); $h = imagesy($im); }
    }
    imagepalettetotruecolor($im); imagealphablending($im, false); imagesavealpha($im, true);
    $media = media_all();
    // a replaced photo gets a new file name, so browsers that cached the old one (for a year) fetch the new one
    if ($replaceId && isset($media[$replaceId])) { $id = $replaceId; foreach (glob(UPLOADS . '/' . $media[$id]['file'] . '-*.webp') ?: [] as $old) @unlink($old); $file = preg_replace('/-r[0-9a-f]{6}$/', '', $id) . '-r' . bin2hex(random_bytes(3)); }
    else {
        $b = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', pathinfo($orig, PATHINFO_FILENAME)) ?: 'foto')), '-') ?: 'foto';
        $b = substr($b, 0, 50); $id = $b; while (isset($media[$id]) || glob(UPLOADS . '/' . $id . '-*.webp')) $id = $b . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        $file = $id;
    }
    $sizes = [];
    foreach ([400, 800, 1600, 2400] as $s) {
        if ($s > $w && $s > 800) continue;
        $r = $w > $s ? imagescale($im, $s, (int)round($h * $s / $w), IMG_BICUBIC) : $im;
        imagealphablending($r, false); imagesavealpha($r, true);
        imagewebp($r, UPLOADS . "/$file-$s.webp", $s <= 400 ? 78 : 82);
        if ($r !== $im) imagedestroy($r);
        $sizes[] = $s;
    }
    imagedestroy($im);
    $media[$id] = ['file' => $file, 'w' => $w, 'h' => $h, 'sizes' => $sizes, 'name' => mb_substr($orig, 0, 120), 'alt' => $media[$id]['alt'] ?? [], 'added' => date('c'), 'by' => $_SESSION['uid'] ?? ''];
    jwrite('media.json', $media, false);
    return $id;
}
function media_json_item(string $id, array $m): array { return ['id' => $id, 'src' => media_src($id, 800), 'name' => $m['name'] ?? $id, 'w' => $m['w'], 'h' => $m['h'], 'added' => $m['added'] ?? '']; }

/* ================= layout ================= */
function where_label(string $w): array {
    [$t, $k] = array_pad(explode(':', $w, 2), 2, '');
    return match ($t) {
        'page' => [page_schemas()[$k]['title'] ?? $k, aurl('a=page&id=' . $k)],
        'profiles' => ['Профиль: ' . tr(item('profiles', $k)['title'] ?? [], 'ru'), aurl('a=profile&slug=' . $k)],
        'stories' => ['История: ' . tr(item('stories', $k)['title'] ?? [], 'ru'), aurl('a=story&slug=' . $k)],
        'settings' => ['Настройки: логотипы партнёров', aurl('a=settings')], default => [$w, '#'] };
}
function admin_page(string $title, string $body, string $cur = '', array $o = []): void {
    $me = me(); $new = count(array_filter(glob(DATA . '/_inbox/*.json') ?: [], fn($f) => empty(json_decode((string)file_get_contents($f), true)['read'])));
    $pc = function_exists('padm_counts') ? padm_counts() : [0, 0];
    $nav = [['dash', 'Обзор', aurl()], ['-', 'Страницы'], ['page:home', 'Главная', aurl('a=page&id=home')], ['page:product-profiles', 'Продукция (общая)', aurl('a=page&id=product-profiles')],
            ['page:projects', 'Проекты', aurl('a=page&id=projects')], ['page:signage-research', 'Исследования', aurl('a=page&id=signage-research')], ['page:contact', 'Контакты', aurl('a=page&id=contact')],
            ['page:privacy-policy', 'Конфиденциальность', aurl('a=page&id=privacy-policy')], ['-', 'Каталог'], ['profiles', 'Профили продукции', aurl('a=profiles')], ['stories', 'Истории проектов', aurl('a=stories')],
            ['media', 'Медиатека', aurl('a=media')], ['-', 'Кабинет клиентов'], ['portal', 'Запросы клиентов' . ($pc[0] ? ' <b class="badge">' . $pc[0] . '</b>' : ''), aurl('a=portal')], ['pclients', 'Клиенты' . ($pc[1] ? ' <b class="badge">' . $pc[1] . '</b>' : ''), aurl('a=pclients')], ['psettings', 'Настройки кабинета', aurl('a=psettings')], ['-', 'Работа'], ['inbox', 'Заявки с сайта' . ($new ? ' <b class="badge">' . $new . '</b>' : ''), aurl('a=inbox')], ['translate', 'Переводы', aurl('a=translate')],
            ['-', 'Система'], ['ui', 'Тексты интерфейса', aurl('a=ui')], ['settings', 'Настройки', aurl('a=settings')], ['users', 'Пользователи', aurl('a=users')], ['backup', 'Резервная копия', aurl('a=backup')]];
    $n = '';
    foreach ($nav as $x) $n .= $x[0] === '-' ? '<li class="nh">' . e($x[1]) . '</li>' : '<li><a href="' . e($x[2]) . '"' . ($x[0] === $cur ? ' aria-current="page"' : '') . '>' . $x[1] . '</a></li>';
    $fl = ''; foreach ($_SESSION['flash'] ?? [] as [$tone, $m]) $fl .= '<div class="flash flash-' . e($tone) . '" role="status">' . e($m) . '</div>'; unset($_SESSION['flash']);
    $tabs = !empty($o['langtabs']) ? lang_tabs($o['langdata'] ?? []) : '';
    $v = @filemtime(__DIR__ . '/assets/admin.css') . '-' . @filemtime(__DIR__ . '/assets/admin.js');
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . e($title) . ' · Админка Reclame Fabriek</title>'
       . '<link rel="icon" href="/assets/brand/favicon.svg"><link rel="stylesheet" href="/assets/css/fonts.css"><link rel="stylesheet" href="/admin/assets/admin.css?v=' . $v . '"><script src="/admin/assets/admin.js?v=' . $v . '" defer></script></head>'
       . '<body data-csrf="' . e($_SESSION['csrf']) . '" data-l="' . e($o['lang'] ?? 'ru') . '"><header class="top"><a class="logo" href="' . aurl() . '"><img src="/assets/brand/rf-logo-original-160.png" alt=""><span><b>Reclame Fabriek</b><i>Управление сайтом</i></span></a>'
       . '<button class="b-ic menu-btn" type="button" data-menu aria-label="Меню">☰</button><div class="top-r"><a class="b b-ghost" href="/" target="_blank" rel="noopener">Открыть сайт ↗</a><span class="who">' . e($me['name'] ?? '') . '</span>'
       . '<form method="post" action="' . aurl('a=logout') . '">' . csrf_field() . '<button class="b b-ghost" type="submit">Выйти</button></form></div></header>'
       . '<div class="shell"><nav class="side" aria-label="Разделы"><ul>' . $n . '</ul></nav><main class="main">' . $fl . '<div class="ph"><div><h1>' . e($title) . '</h1>' . ($o['sub'] ?? '') . '</div><div class="ph-act">' . ($o['actions'] ?? '') . '</div></div>' . $tabs . $body . '</main></div>'
       . media_dialog() . '</body></html>';
}
function lang_tabs(array $data): string {
    $miss = missing_langs($data); $o = '<div class="ltabs" role="tablist" aria-label="Язык редактирования"><span>Язык:</span>';
    foreach (LANG_NAMES as $l => $_) {
        $on = in_array($l, site_langs(), true);
        $o .= '<button type="button" role="tab" data-ltab="' . $l . '"' . ($on ? '' : ' class="off" title="Язык выключен в настройках"') . '>' . e(LANG_NAMES_RU[$l]) . (isset($miss[$l]) ? ' <b class="badge badge-w" title="Не переведено полей">' . $miss[$l] . '</b>' : ($on ? ' <i class="okm">✓</i>' : '')) . '</button>';
    }
    return $o . '<button type="button" data-ltab="all" class="all">Все сразу</button></div>';
}
function media_dialog(): string {
    return '<dialog class="mdlg" data-mdlg aria-label="Медиатека"><div class="mdlg-h"><h2>Выберите фото</h2><input type="search" placeholder="Поиск по имени файла" data-msearch><label class="b b-line up">Загрузить новые<input type="file" accept="image/*" multiple data-mupload hidden></label><button type="button" class="b-ic" data-mclose aria-label="Закрыть">×</button></div>'
         . '<p class="mdlg-st" data-mstatus></p><ul class="mgrid" data-mgrid></ul><div class="mdlg-f"><span data-mcount></span><button type="button" class="b b-dark" data-mok>Готово</button></div></dialog>';
}
function bare_page(string $title, string $body): void {
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . e($title) . ' · Reclame Fabriek</title>'
       . '<link rel="stylesheet" href="/assets/css/fonts.css"><link rel="stylesheet" href="/admin/assets/admin.css"></head><body class="bare"><main class="login"><img src="/assets/brand/rf-logo-original-160.png" alt="Reclame Fabriek" width="72" height="72">' . $body . '</main></body></html>';
}
