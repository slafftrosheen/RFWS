<?php
/* Reclame Fabriek CMS · admin (Russian). Open /admin/ */
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/core.php';
require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/portal_admin.php';
admin_session();
$a = (string)($_GET['a'] ?? 'dash');
$POST = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$GLOBALS['LANG'] = 'ru';

/* ---------------- first run: create the first administrator ---------------- */
if (!users()) {
    $code = setup_code(); $err = '';
    if ($POST && $a === 'setup') {
        check_csrf();
        $login = strtolower(trim((string)($_POST['login'] ?? ''))); $pw = (string)($_POST['pw'] ?? '');
        if (!hash_equals($code, strtoupper(trim((string)($_POST['code'] ?? ''))))) $err = 'Неверный код установки.';
        elseif (!preg_match('/^[a-z0-9._-]{3,32}$/', $login)) $err = 'Логин: 3–32 символа, латиница, цифры, точка, дефис.';
        elseif (mb_strlen($pw) < 10) $err = 'Пароль — минимум 10 символов.';
        elseif ($pw !== ($_POST['pw2'] ?? '')) $err = 'Пароли не совпадают.';
        else { jwrite('_auth/users.json', [$login => ['name' => trim((string)($_POST['name'] ?? '')) ?: $login, 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'created' => date('c')]], false); @unlink(DATA . '/_auth/setup-code.txt'); login_ok($login); flash('Готово! Администратор создан.'); go(''); }
    }
    bare_page('Первый запуск', '<h1>Первый запуск</h1><p>Создайте учётную запись администратора. Код установки лежит на сервере в файле <code>data/_auth/setup-code.txt</code> — его может открыть ваш разработчик или хостинг.</p>'
        . ($err ? '<p class="err">' . e($err) . '</p>' : '') . '<form method="post" action="' . aurl('a=setup') . '">' . csrf_field()
        . '<label class="fld">Код установки<input name="code" required autocomplete="off"></label><label class="fld">Ваше имя<input name="name" autocomplete="name"></label>'
        . '<label class="fld">Логин<input name="login" required autocomplete="username" pattern="[a-z0-9._\-]{3,32}"></label><label class="fld">Пароль (мин. 10 символов)<input type="password" name="pw" required minlength="10" autocomplete="new-password"></label>'
        . '<label class="fld">Пароль ещё раз<input type="password" name="pw2" required minlength="10" autocomplete="new-password"></label><button class="b b-red" type="submit">Создать администратора</button></form>');
    exit;
}

/* ---------------- login ---------------- */
if (!me()) {
    $err = ''; $wait = login_blocked();
    if ($POST && $a === 'login') {
        check_csrf();
        if ($wait) $err = 'Слишком много попыток. Подождите ' . ceil($wait / 60) . ' мин.';
        else {
            $login = strtolower(trim((string)($_POST['login'] ?? ''))); $u = users()[$login] ?? null;
            if ($u && password_verify((string)($_POST['pw'] ?? ''), $u['hash'])) { login_ok($login); go((string)($_POST['next'] ?? '') ?: ''); }
            login_fail(); usleep(400000); $err = 'Неверный логин или пароль.';
        }
    }
    $next = preg_match('/^[a-z0-9=&_%.-]*$/i', (string)($_SERVER['QUERY_STRING'] ?? '')) ? (string)($_SERVER['QUERY_STRING'] ?? '') : '';
    bare_page('Вход', '<h1>Вход в управление сайтом</h1>' . ($err ? '<p class="err" role="alert">' . e($err) . '</p>' : '') . '<form method="post" action="' . aurl('a=login') . '">' . csrf_field() . '<input type="hidden" name="next" value="' . e($a === 'login' ? '' : $next) . '">'
        . '<label class="fld">Логин<input name="login" required autocomplete="username" autofocus></label><label class="fld">Пароль<input type="password" name="pw" required autocomplete="current-password"></label><button class="b b-red" type="submit">Войти</button></form><p class="small"><a href="/">← На сайт</a></p>');
    exit;
}
if ($POST) check_csrf();

function one_type(string $t): string { return $t === 'stories' ? 'story' : 'profile'; }
/* ---------------- shared: save typed item (profile / story) ---------------- */
function save_item(string $type, string $schemaFn): void {
    $old = (string)($_POST['orig_slug'] ?? ''); $slug = strtolower(trim((string)($_POST['slug'] ?? '')));
    $item = $old !== '' ? (item($type, $old) ?? []) : [];
    $isNew = !$item;
    if (!valid_slug($slug)) { flash('Адрес страницы: только латинские буквы, цифры и дефисы (например profile-9).', 'err'); go("a=" . one_type($type) . "&slug=" . rawurlencode($old ?: 'new')); }
    $taken = item('profiles', $slug) ?? item('stories', $slug);
    if ($slug !== $old && ($taken || isset(PAGE_PATHS[$slug]) || isset(LANG_NAMES[$slug]) || in_array($slug, ['admin', 'api', 'uploads', 'assets', 'data', 'app', 'portal', 'category', 'jet-popup'], true))) { flash('Адрес «' . $slug . '» уже занят. Выберите другой.', 'err'); go("a=" . one_type($type) . "&slug=" . rawurlencode($old ?: 'new')); }
    $item = clean_sections($schemaFn(), $_POST['c'] ?? [], $item);
    if (trim((string)($item['title']['en'] ?? '')) === '') {
        $any = ''; foreach ($item['title'] ?? [] as $v) if (trim((string)$v) !== '') { $any = $v; break; }
        if ($any === '') { flash('Укажите название хотя бы на английском — это основной язык сайта.', 'err'); go("a=" . one_type($type) . "&slug=" . rawurlencode($old ?: 'new')); }
        $item['title']['en'] = $any;
    }
    $item['slug'] = $slug; $item['published'] = !empty($_POST['published']);
    $item['order'] ??= (count(items($type, false)) + 1) * 10;
    if ($type === 'profiles') { $item['group'] ??= 'letters'; $item['light'] ??= 'frontlit'; }
    $item['updated'] = date('c'); $item['updated_by'] = $_SESSION['uid'];
    jwrite("$type/$slug.json", $item);
    if (!$isNew && $old !== $slug) {
        @unlink(DATA . "/$type/$old.json");
        $red = jread('redirects.json', []); $red[$old] = $slug; foreach ($red as $k => $v) if ($v === $old) $red[$k] = $slug; unset($red[$slug]); jwrite('redirects.json', $red, false);
        foreach (array_keys(PAGE_PATHS) as $pid) { $pd = page_data($pid); $j = json_encode($pd); $j2 = str_replace('"' . $old . '"', '"' . $slug . '"', $j); if ($j2 !== $j) jwrite("pages/$pid.json", json_decode($j2, true)); }
    }
    flash($isNew ? 'Создано.' : 'Сохранено.' . (!$item['published'] ? ' Это черновик — на сайте пока не видно.' : ''));
    go('a=' . one_type($type) . '&slug=' . rawurlencode($slug) . '&l=' . rawurlencode((string)($_POST['_l'] ?? 'ru')));
}
function item_list_page(string $type): void {
    $isP = $type === 'profiles'; $all = items($type, false); $g = (string)($_GET['g'] ?? '');
    if ($isP && $g) $all = array_filter($all, fn($x) => ($x['group'] ?? '') === $g);
    $rows = '';
    foreach ($all as $s => $x) {
        $rows .= '<tr draggable="true" data-slug="' . e($s) . '"><td class="grip">⋮⋮</td><td class="th">' . (media($x['cover'] ?? null) ? '<img src="' . e(media_src($x['cover'], 800)) . '" alt="">' : '') . '</td>'
            . '<td><a class="t" href="' . aurl('a=' . one_type($type) . '&slug=' . rawurlencode($s)) . '">' . e(tr($x['title'] ?? [], 'ru')) . '</a><small>/' . e($s) . '/</small></td>'
            . ($isP ? '<td>' . e(GROUP_RU[$x['group']] ?? '') . '<br><small>' . e(LIGHT_RU[$x['light']] ?? '') . '</small></td>' : '<td>' . e(tr($x['kicker'] ?? [], 'ru')) . '<br><small>' . e($x['date'] ?? '') . '</small></td>')
            . '<td>' . lang_dots($x) . '</td><td><form method="post" action="' . aurl('a=toggle&type=' . $type) . '">' . csrf_field() . '<input type="hidden" name="slug" value="' . e($s) . '"><button class="pill ' . (!empty($x['published']) ? 'pill-on' : 'pill-off') . '" type="submit" title="Нажмите, чтобы переключить">' . (!empty($x['published']) ? 'Опубликовано' : 'Черновик') . '</button></form></td>'
            . '<td class="acts"><a class="b b-line b-s" href="' . aurl('a=' . one_type($type) . '&slug=' . rawurlencode($s)) . '">Изменить</a><a class="b b-ghost b-s" href="/' . e($s) . '/" target="_blank" rel="noopener">На сайте ↗</a>'
            . '<form method="post" action="' . aurl('a=dup&type=' . $type) . '">' . csrf_field() . '<input type="hidden" name="slug" value="' . e($s) . '"><button class="b b-ghost b-s" type="submit">Копия</button></form>'
            . '<form method="post" action="' . aurl('a=delete&type=' . $type) . '" data-confirm="Удалить «' . e(tr($x['title'] ?? [], 'ru')) . '»? Страница исчезнет с сайта. Файл можно будет восстановить из корзины.">' . csrf_field() . '<input type="hidden" name="slug" value="' . e($s) . '"><button class="b b-ghost b-s b-danger" type="submit">Удалить</button></form></td></tr>';
    }
    $filters = $isP ? '<div class="seg"><a href="' . aurl('a=profiles') . '"' . (!$g ? ' aria-current="true"' : '') . '>Все</a><a href="' . aurl('a=profiles&g=letters') . '"' . ($g === 'letters' ? ' aria-current="true"' : '') . '>Объёмные буквы</a><a href="' . aurl('a=profiles&g=lightbox') . '"' . ($g === 'lightbox' ? ' aria-current="true"' : '') . '>Световые короба</a></div>' : '';
    $body = $filters . '<div class="card" data-orderform><table class="tbl" data-sortrows><thead><tr><th></th><th></th><th>Название</th><th>' . ($isP ? 'Группа / подсветка' : 'Подпись / дата') . '</th><th>Переводы</th><th>Статус</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table>'
        . '<form method="post" action="' . aurl('a=order&type=' . $type) . '" class="card-f">' . csrf_field() . '<span class="small">Перетащите строки за ⋮⋮, чтобы изменить порядок на сайте.</span><div data-orderinputs hidden></div><button class="b b-dark" type="submit" data-ordersave disabled>Сохранить порядок</button></form></div>';
    admin_page($isP ? 'Профили продукции' : 'Истории проектов', $body, $type, ['actions' => '<a class="b b-red" href="' . aurl('a=' . one_type($type) . '&slug=new') . '">+ ' . ($isP ? 'Новый профиль' : 'Новая история') . '</a>', 'sub' => '<p class="sub">' . count(items($type)) . ' опубликовано из ' . count(items($type, false)) . '</p>']);
}
function item_edit_page(string $type): void {
    $isP = $type === 'profiles'; $slug = (string)($_GET['slug'] ?? 'new'); $isNew = $slug === 'new';
    $it = $isNew ? ['published' => false, 'group' => (string)($_GET['g'] ?? 'letters'), 'light' => 'frontlit'] : item($type, $slug);
    if (!$it) { flash('Не найдено.', 'err'); go('a=' . $type); }
    $schema = $isP ? profile_schema() : story_schema();
    $hist = $isNew ? 0 : count(glob(DATA . '/_history/' . $type . '__' . $slug . '/*.json') ?: []);
    $side = '<aside class="card sidebox"><h2>Публикация</h2><label class="chk"><input type="checkbox" name="published" value="1"' . (!empty($it['published']) ? ' checked' : '') . '> Опубликовано на сайте</label>'
        . field_html('slug', F('slug', 'Адрес страницы', ['hint' => 'Латиница и дефисы. При смене старый адрес будет перенаправлять на новый.']), $isNew ? '' : $slug, ['slug'])
        . '<input type="hidden" name="orig_slug" value="' . e($isNew ? '' : $slug) . '"><input type="hidden" name="_l" value="ru" data-lfield>'
        . '<button class="b b-red b-wide" type="submit">Сохранить</button>'
        . (!$isNew ? '<a class="b b-line b-wide" href="/' . e($slug) . '/" target="_blank" rel="noopener">Посмотреть на сайте ↗</a><p class="small">Языки сайта: ' . implode(' ', array_map(fn($l) => '<a href="' . e(url('/' . $slug . '/', $l)) . '" target="_blank" rel="noopener">' . strtoupper($l) . '</a>', site_langs())) . '</p>' : '')
        . (!empty($it['updated']) ? '<p class="small">Изменено: ' . e(date('d.m.Y H:i', strtotime($it['updated']))) . (!empty($it['updated_by']) ? ' · ' . e($it['updated_by']) : '') . '</p>' : '')
        . ($hist ? '<p class="small"><a href="' . aurl('a=history&rel=' . rawurlencode("$type/$slug")) . '">История изменений (' . $hist . ')</a></p>' : '') . '</aside>';
    $body = '<form method="post" action="' . aurl('a=save&type=' . $type) . '" class="edit" data-dirty>' . csrf_field() . '<div class="edit-main">' . form_sections($schema, $it) . '</div>' . $side . '</form>';
    $title = $isNew ? ($isP ? 'Новый профиль' : 'Новая история') : tr($it['title'] ?? [], 'ru');
    admin_page($title, $body, $type, ['langtabs' => true, 'langdata' => $it, 'lang' => (string)($_GET['l'] ?? 'ru'), 'sub' => '<p class="sub"><a href="' . aurl('a=' . $type) . '">← ' . ($isP ? 'Все профили' : 'Все истории') . '</a></p>']);
}

/* ================= routes ================= */
switch ($a) {
case 'logout':
    if ($POST) { $_SESSION = []; session_destroy(); setcookie('rfadmin', '', ['expires' => time() - 3600, 'path' => '/']); }
    header('Location: /admin/'); exit;

case 'dash':
    $acc = []; foreach (array_keys(PAGE_PATHS) as $p) tstats(page_data($p), $acc);
    foreach (items('profiles', false) as $x) tstats($x, $acc); foreach (items('stories', false) as $x) tstats($x, $acc); tstats(ui_all(), $acc); tstats(settings(), $acc);
    $bars = '';
    foreach (site_langs() as $l) { $pc = $acc['_total'] ? (int)floor(100 * ($acc[$l] ?? 0) / $acc['_total']) : 100; $bars .= '<li><span>' . e(LANG_NAMES_RU[$l]) . '</span><span class="bar"><i style="width:' . $pc . '%"></i></span><b>' . $pc . '%</b>' . ($pc < 100 ? '<a href="' . aurl('a=translate&l=' . $l) . '">что не переведено →</a>' : '<em>✓</em>') . '</li>'; }
    $inbox = glob(DATA . '/_inbox/*.json') ?: []; rsort($inbox); $new = 0; $recent = '';
    foreach ($inbox as $i => $f) { $m = json_decode((string)file_get_contents($f), true); if (empty($m['read'])) $new++; if ($i < 5) $recent .= '<li><a href="' . aurl('a=msg&id=' . rawurlencode($m['id'])) . '">' . (empty($m['read']) ? '<b>●</b> ' : '') . e($m['type'] === 'subscribe' ? 'Подписка' : 'Заявка') . ' · ' . e($m['company'] ?: $m['name'] ?: $m['email']) . '</a><small>' . e(date('d.m.Y H:i', strtotime($m['date']))) . '</small></li>'; }
    $stats = [['Профили', count(items('profiles')) . ' / ' . count(items('profiles', false)), aurl('a=profiles'), 'опубликовано / всего'], ['Истории', count(items('stories')) . ' / ' . count(items('stories', false)), aurl('a=stories'), 'опубликовано / всего'],
              ['Фото в медиатеке', (string)count(media_all()), aurl('a=media'), ''], ['Новые заявки', (string)$new, aurl('a=inbox'), 'из ' . count($inbox)]];
    $st = ''; foreach ($stats as [$t, $v, $u, $h]) $st .= '<a class="stat card" href="' . $u . '"><span>' . e($t) . '</span><b>' . e($v) . '</b><small>' . e($h) . '</small></a>';
    $body = '<div class="stats">' . $st . '</div><div class="grid2"><section class="card"><h2>Переводы сайта</h2><p class="small">Доля текстов, переведённых с английского. Непереведённое на сайте показывается по-английски.</p><ul class="bars">' . $bars . '</ul></section>'
        . '<section class="card"><h2>Последние заявки</h2>' . ($recent ? '<ul class="recent">' . $recent . '</ul>' : '<p class="small">Пока нет заявок с сайта.</p>') . '<p><a href="' . aurl('a=inbox') . '">Все заявки →</a></p></section></div>'
        . '<section class="card"><h2>Быстрые действия</h2><div class="quick"><a class="b b-line" href="' . aurl('a=profile&slug=new') . '">+ Новый профиль</a><a class="b b-line" href="' . aurl('a=story&slug=new') . '">+ Новая история</a><a class="b b-line" href="' . aurl('a=media') . '">Загрузить фото</a><a class="b b-line" href="' . aurl('a=page&id=home') . '">Изменить главную</a><a class="b b-line" href="' . aurl('a=backup') . '">Скачать резервную копию</a></div></section>';
    admin_page('Обзор', $body, 'dash', ['sub' => '<p class="sub">Здравствуйте, ' . e(me()['name']) . '!</p>']);
    break;

case 'page':
    $id = (string)($_GET['id'] ?? ''); $sc = page_schemas()[$id] ?? null;
    if (!$sc) go('');
    if ($POST) {
        $new = clean_sections($sc, $_POST['c'] ?? [], page_data($id)); $new['updated'] = date('c'); $new['updated_by'] = $_SESSION['uid'];
        jwrite("pages/$id.json", $new); flash('Страница сохранена. Изменения уже на сайте.'); go('a=page&id=' . $id . '&l=' . rawurlencode((string)($_POST['_l'] ?? 'ru')));
    }
    $d = page_data($id); $hist = count(glob(DATA . '/_history/pages__' . $id . '/*.json') ?: []);
    $toc = ''; foreach ($sc['sections'] as $k => $s) $toc .= '<li><a href="#s-' . e($k) . '">' . e($s['label']) . '</a></li>';
    $body = '<form method="post" class="edit" data-dirty>' . csrf_field() . '<input type="hidden" name="_l" value="ru" data-lfield><div class="edit-main">' . form_sections($sc, $d) . '</div>'
        . '<aside class="card sidebox"><h2>Разделы страницы</h2><ol class="toc">' . $toc . '</ol><button class="b b-red b-wide" type="submit">Сохранить</button>'
        . '<p class="small">Открыть на сайте: ' . implode(' ', array_map(fn($l) => '<a href="' . e(url(PAGE_PATHS[$id], $l)) . '" target="_blank" rel="noopener">' . strtoupper($l) . '</a>', site_langs())) . '</p>'
        . (!empty($d['updated']) ? '<p class="small">Изменено: ' . e(date('d.m.Y H:i', strtotime($d['updated']))) . '</p>' : '') . ($hist ? '<p class="small"><a href="' . aurl('a=history&rel=' . rawurlencode("pages/$id")) . '">История изменений (' . $hist . ')</a></p>' : '') . '</aside></form>';
    admin_page($sc['title'], $body, 'page:' . $id, ['langtabs' => true, 'langdata' => $d, 'lang' => (string)($_GET['l'] ?? 'ru'), 'sub' => '<p class="sub">Страница сайта · ' . e(PAGE_PATHS[$id]) . '</p>']);
    break;

case 'profiles': item_list_page('profiles'); break;
case 'stories': item_list_page('stories'); break;
case 'profile': item_edit_page('profiles'); break;
case 'story': item_edit_page('stories'); break;
case 'save':
    $type = (string)($_GET['type'] ?? ''); if (!$POST || !in_array($type, ['profiles', 'stories'], true)) go('');
    save_item($type, $type === 'profiles' ? 'profile_schema' : 'story_schema'); break;
case 'toggle': case 'delete': case 'dup':
    $type = (string)($_GET['type'] ?? ''); $slug = (string)($_POST['slug'] ?? ''); $it = $POST && in_array($type, ['profiles', 'stories'], true) ? item($type, $slug) : null;
    if (!$it) go('');
    if ($a === 'toggle') { $it['published'] = empty($it['published']); $it['updated'] = date('c'); jwrite("$type/$slug.json", $it); flash($it['published'] ? 'Опубликовано.' : 'Снято с публикации (черновик).'); }
    if ($a === 'delete') { if (!is_dir(DATA . '/_trash')) mkdir(DATA . '/_trash', 0775, true); rename(DATA . "/$type/$slug.json", DATA . '/_trash/' . $type . '__' . $slug . '__' . date('Ymd-His') . '.json'); cache_bust(); page_cache_clear(); flash('Удалено. Копия сохранена в корзине (папка data/_trash).'); }
    if ($a === 'dup') { $n = $slug . '-copy'; $i = 2; while (item($type, $n)) $n = $slug . '-copy-' . $i++; $it['slug'] = $n; $it['published'] = false; $it['order'] = ($it['order'] ?? 0) + 1; foreach ($it['title'] as $l => $v) $it['title'][$l] = $v . ' (копия)'; jwrite("$type/$n.json", $it); flash('Создана копия-черновик.'); go('a=' . one_type($type) . '&slug=' . $n); }
    go('a=' . $type);
case 'order':
    $type = (string)($_GET['type'] ?? ''); if (!$POST || !in_array($type, ['profiles', 'stories'], true)) go('');
    $all = items($type, false); $i = 0;
    foreach ((array)($_POST['slugs'] ?? []) as $s) if (isset($all[$s])) { $it = $all[$s]; $it['order'] = (++$i) * 10; jwrite("$type/$s.json", $it, false); }
    flash('Порядок сохранён.'); go('a=' . $type);

/* ---------------- media ---------------- */
case 'media':
    $use = media_usage(); $q = trim((string)($_GET['q'] ?? '')); $f = (string)($_GET['f'] ?? '');
    $all = media_all(); uasort($all, fn($x, $y) => strcmp($y['added'] ?? '', $x['added'] ?? ''));
    $cards = ''; $n = 0;
    foreach ($all as $id => $m) {
        $id = (string)$id;
        if ($q !== '' && stripos($id . ' ' . ($m['name'] ?? ''), $q) === false) continue;
        if ($f === 'unused' && isset($use[$id])) continue;
        $n++; $u = count($use[$id] ?? []);
        $cards .= '<li><a href="' . aurl('a=medium&id=' . rawurlencode($id)) . '"><img src="' . e(media_src($id, 800)) . '" alt="" loading="lazy"><span>' . e($m['name'] ?? $id) . '</span><small>' . (int)$m['w'] . '×' . (int)$m['h'] . ' · ' . ($u ? 'используется: ' . $u : '<em>не используется</em>') . '</small></a></li>';
    }
    $body = '<div class="card drop" data-drop><p><b>Перетащите сюда фото</b> или <label class="lnk">выберите на компьютере<input type="file" accept="image/*" multiple hidden data-upload></label>. JPG, PNG, WEBP до 25 МБ. Размеры для сайта создаются автоматически.</p><p class="small" data-upstatus></p></div>'
        . '<form class="filt" method="get"><input type="hidden" name="a" value="media"><input type="search" name="q" value="' . e($q) . '" placeholder="Поиск по имени"><select name="f"><option value="">Все фото</option><option value="unused"' . ($f === 'unused' ? ' selected' : '') . '>Только неиспользуемые</option></select><button class="b b-line" type="submit">Показать</button><span class="small">' . $n . ' шт.</span></form>'
        . '<ul class="mlib">' . $cards . '</ul>';
    admin_page('Медиатека', $body, 'media', ['sub' => '<p class="sub">Все фото сайта в одном месте. Нажмите на фото, чтобы изменить подпись, заменить файл или удалить.</p>']);
    break;
case 'medium':
    $id = (string)($_GET['id'] ?? ''); $m = media($id); if (!$m) { flash('Фото не найдено.', 'err'); go('a=media'); }
    $use = media_usage()[$id] ?? [];
    if ($POST && ($_POST['do'] ?? '') === 'save') {
        $all = media_all(); $all[$id]['alt'] = clean_val(TA('alt'), $_POST['alt'] ?? [], $all[$id]['alt'] ?? []); $all[$id]['name'] = trim(mb_substr((string)($_POST['name'] ?? ''), 0, 120)) ?: $all[$id]['name'];
        jwrite('media.json', $all); flash('Сохранено.'); go('a=medium&id=' . rawurlencode($id));
    }
    if ($POST && ($_POST['do'] ?? '') === 'delete') {
        if ($use) { flash('Это фото используется на сайте. Сначала уберите его оттуда (список ниже).', 'err'); go('a=medium&id=' . rawurlencode($id)); }
        foreach (glob(UPLOADS . '/' . $m['file'] . '-*.webp') ?: [] as $x) @unlink($x);
        $all = media_all(); unset($all[$id]); jwrite('media.json', $all); flash('Фото удалено.'); go('a=media');
    }
    if ($POST && ($_POST['do'] ?? '') === 'replace' && !empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        $r = media_ingest($_FILES['file']['tmp_name'], (string)$_FILES['file']['name'], $id);
        flash($r === $id ? 'Файл заменён везде, где используется это фото.' : (string)$r, $r === $id ? 'ok' : 'err'); go('a=medium&id=' . rawurlencode($id));
    }
    $ul = ''; foreach ($use as $w) { [$t, $u] = where_label($w); $ul .= '<li><a href="' . e($u) . '">' . e($t) . '</a></li>'; }
    $fake = ['sections' => ['x' => ['label' => 'Подпись', 'flat' => true, 'fields' => []]]];
    $body = '<div class="grid2 grid2-wide"><figure class="card mprev"><img src="' . e(media_src($id, 1600)) . '" alt=""><figcaption class="small">' . (int)$m['w'] . '×' . (int)$m['h'] . ' px · размеры для сайта: ' . e(implode(', ', $m['sizes'])) . ' px · загружено ' . e(date('d.m.Y', strtotime($m['added'] ?? 'now'))) . '</figcaption></figure>'
        . '<div><form method="post" class="card" data-dirty>' . csrf_field() . '<input type="hidden" name="do" value="save"><h2>Описание</h2><label class="fld">Название файла<input name="name" value="' . e($m['name'] ?? '') . '"></label>'
        . field_html('alt', TA('Описание для незрячих и Google (alt)', ['hint' => 'Коротко, что на фото. Если пусто — используется название профиля или проекта.']), $m['alt'] ?? [], ['alt']) . '<button class="b b-red" type="submit">Сохранить</button></form>'
        . '<section class="card"><h2>Где используется</h2>' . ($ul ? '<ul class="uses">' . $ul . '</ul>' : '<p class="small">Нигде. Фото можно удалить.</p>') . '</section>'
        . '<section class="card"><h2>Заменить файл</h2><p class="small">Новое фото появится везде, где используется это.</p><form method="post" enctype="multipart/form-data">' . csrf_field() . '<input type="hidden" name="do" value="replace"><input type="file" name="file" accept="image/*" required> <button class="b b-line" type="submit">Заменить</button></form></section>'
        . '<form method="post" class="card" data-confirm="Удалить фото навсегда?">' . csrf_field() . '<input type="hidden" name="do" value="delete"><button class="b b-ghost b-danger" type="submit"' . ($use ? ' disabled title="Фото используется"' : '') . '>Удалить фото</button></form></div></div>';
    admin_page($m['name'] ?? $id, $body, 'media', ['langtabs' => true, 'langdata' => ['alt' => $m['alt'] ?? []], 'sub' => '<p class="sub"><a href="' . aurl('a=media') . '">← Медиатека</a></p>']);
    break;
case 'media_json':
    header('Content-Type: application/json; charset=utf-8'); $o = [];
    $all = media_all(); uasort($all, fn($x, $y) => strcmp($y['added'] ?? '', $x['added'] ?? ''));
    foreach ($all as $id => $m) $o[] = media_json_item((string)$id, $m);
    echo json_encode($o, JSON_UNESCAPED_UNICODE); exit;
case 'upload':
    header('Content-Type: application/json; charset=utf-8');
    if (!$POST || empty($_FILES['files'])) { echo '{"ok":false,"items":[],"errors":["Нет файлов"]}'; exit; }
    $items = []; $errors = []; $F = $_FILES['files'];
    foreach ((array)$F['name'] as $i => $nm) {
        if (($F['error'][$i] ?? 1) !== UPLOAD_ERR_OK) { $errors[] = $nm . ': файл не загрузился' . (in_array($F['error'][$i], [1, 2], true) ? ' (слишком большой)' : '') . '.'; continue; }
        if ($F['size'][$i] > 25 * 1024 * 1024) { $errors[] = $nm . ': больше 25 МБ.'; continue; }
        if (!is_uploaded_file($F['tmp_name'][$i])) continue;
        $r = media_ingest($F['tmp_name'][$i], (string)$nm);
        if (media($r)) $items[] = media_json_item($r, media($r)); else $errors[] = $nm . ': ' . $r;
    }
    echo json_encode(['ok' => !$errors, 'items' => $items, 'errors' => $errors], JSON_UNESCAPED_UNICODE); exit;

/* ---------------- inbox ---------------- */
case 'inbox':
    $files = glob(DATA . '/_inbox/*.json') ?: []; rsort($files); $t = (string)($_GET['t'] ?? '');
    $rows = '';
    foreach ($files as $f) { $m = json_decode((string)file_get_contents($f), true); if (!$m || ($t && $m['type'] !== $t)) continue;
        $rows .= '<tr class="' . (empty($m['read']) ? 'unread' : '') . '"><td>' . e(date('d.m.Y H:i', strtotime($m['date']))) . '</td><td>' . ($m['type'] === 'subscribe' ? 'Подписка' : 'Заявка') . '</td><td><a href="' . aurl('a=msg&id=' . rawurlencode($m['id'])) . '">' . e($m['name'] ?: $m['email']) . '</a>' . ($m['company'] ? '<br><small>' . e($m['company']) . '</small>' : '') . '</td><td>' . e($m['email']) . '</td><td>' . e(strtoupper($m['lang'] ?? '')) . '</td><td>' . e(mb_substr($m['message'] ?? '', 0, 80)) . '</td></tr>'; }
    $body = '<div class="seg"><a href="' . aurl('a=inbox') . '"' . (!$t ? ' aria-current="true"' : '') . '>Все</a><a href="' . aurl('a=inbox&t=quote') . '"' . ($t === 'quote' ? ' aria-current="true"' : '') . '>Заявки</a><a href="' . aurl('a=inbox&t=subscribe') . '"' . ($t === 'subscribe' ? ' aria-current="true"' : '') . '>Подписки</a></div>'
        . '<div class="card">' . ($rows ? '<table class="tbl"><thead><tr><th>Дата</th><th>Тип</th><th>От кого</th><th>E-mail</th><th>Язык</th><th>Сообщение</th></tr></thead><tbody>' . $rows . '</tbody></table>' : '<p class="small">Здесь будут заявки и подписки с сайта. Копия каждой заявки также уходит на почту из настроек.</p>') . '</div>';
    admin_page('Заявки', $body, 'inbox', ['actions' => '<a class="b b-line" href="' . aurl('a=subscribers_csv') . '">Скачать подписчиков (CSV)</a>']);
    break;
case 'msg':
    $id = basename((string)($_GET['id'] ?? '')); $f = DATA . '/_inbox/' . $id . '.json'; $m = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    if (!$m) go('a=inbox');
    if ($POST && ($_POST['do'] ?? '') === 'delete') { @unlink($f); flash('Удалено.'); go('a=inbox'); }
    if (empty($m['read'])) { $m['read'] = true; jwrite('_inbox/' . $id . '.json', $m, false); }
    $prof = $m['profile'] ? (item('profiles', $m['profile']) ? tr(item('profiles', $m['profile'])['title'], 'ru') : $m['profile']) : '—';
    $rows = [['Дата', date('d.m.Y H:i', strtotime($m['date']))], ['Тип', $m['type'] === 'subscribe' ? 'Подписка на новости' : 'Заявка'], ['Имя', $m['name'] ?: '—'], ['Компания', $m['company'] ?: '—'], ['Телефон', $m['phone'] ?: '—'], ['Профиль', $prof], ['Язык сайта', LANG_NAMES_RU[$m['lang']] ?? '—']];
    $dl = ''; foreach ($rows as [$k, $v]) $dl .= '<dt>' . e($k) . '</dt><dd>' . e($v) . '</dd>';
    $dl .= '<dt>E-mail</dt><dd><a href="mailto:' . e($m['email']) . '">' . e($m['email']) . '</a></dd>';
    $body = '<div class="card"><dl class="dl">' . $dl . '</dl>' . ($m['message'] ? '<h2>Сообщение</h2><div class="msg">' . nl2br(e($m['message'])) . '</div>' : '') . '<div class="quick"><a class="b b-red" href="mailto:' . e($m['email']) . '?subject=' . rawurlencode('Reclame Fabriek') . '">Ответить письмом</a>'
        . '' . ($m['type'] === 'quote' ? (!empty($m['portal']) ? '<a class="b b-line" href="' . aurl('a=preq&no=' . rawurlencode($m['portal'])) . '">Открыть в кабинете: ' . e($m['portal']) . '</a>' : '<form method="post" action="' . aurl('a=ptransfer') . '">' . csrf_field() . '<input type="hidden" name="id" value="' . e($id) . '"><button class="b b-dark" type="submit">Перенести в кабинет клиента</button></form>') : '') . '<form method="post" data-confirm="Удалить эту заявку?">' . csrf_field() . '<input type="hidden" name="do" value="delete"><button class="b b-ghost b-danger" type="submit">Удалить</button></form></div></div>';
    admin_page($m['type'] === 'subscribe' ? 'Подписка' : 'Заявка от ' . ($m['company'] ?: $m['name'] ?: $m['email']), $body, 'inbox', ['sub' => '<p class="sub"><a href="' . aurl('a=inbox') . '">← Все заявки</a></p>']);
    break;
case 'subscribers_csv':
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF"); fputcsv($out, ['email', 'date', 'lang']);
    foreach (glob(DATA . '/_inbox/*.json') ?: [] as $f) { $m = json_decode((string)file_get_contents($f), true); if (($m['type'] ?? '') === 'subscribe') fputcsv($out, [$m['email'], $m['date'], $m['lang'] ?? '']); }
    exit;

/* ---------------- translations overview ---------------- */
case 'translate':
    $l = (string)($_GET['l'] ?? ''); if (!in_array($l, site_langs(), true)) { $l = ''; foreach (site_langs() as $x) if ($x !== 'en') { $l = $x; break; } }
    $rows = '';
    $add = function (string $title, string $link, $data) use (&$rows, $l) { $mi = missing_langs($data)[$l] ?? 0; if ($mi) $rows .= '<tr><td><a href="' . e($link) . '">' . e($title) . '</a></td><td>' . $mi . '</td><td>' . lang_dots($data) . '</td></tr>'; };
    foreach (page_schemas() as $id => $sc) $add('Страница: ' . $sc['title'], aurl('a=page&id=' . $id . '&l=' . $l), page_data($id));
    foreach (items('profiles', false) as $s => $x) $add('Профиль: ' . tr($x['title'], 'ru'), aurl('a=profile&slug=' . $s . '&l=' . $l), $x);
    foreach (items('stories', false) as $s => $x) $add('История: ' . tr($x['title'], 'ru'), aurl('a=story&slug=' . $s . '&l=' . $l), $x);
    $add('Тексты интерфейса', aurl('a=ui&l=' . $l), ui_all()); $add('Настройки (адрес, контакты)', aurl('a=settings&l=' . $l), settings());
    foreach (media_all() as $id => $m) if (!empty($m['alt'])) $add('Фото: ' . ($m['name'] ?? $id), aurl('a=medium&id=' . rawurlencode((string)$id) . '&l=' . $l), ['alt' => $m['alt']]);
    $seg = '<div class="seg">'; foreach (site_langs() as $x) if ($x !== 'en') $seg .= '<a href="' . aurl('a=translate&l=' . $x) . '"' . ($x === $l ? ' aria-current="true"' : '') . '>' . e(LANG_NAMES_RU[$x]) . '</a>'; $seg .= '</div>';
    $body = $seg . '<div class="card">' . ($rows ? '<table class="tbl"><thead><tr><th>Где</th><th>Не переведено полей</th><th>Все языки</th></tr></thead><tbody>' . $rows . '</tbody></table>' : '<p>Всё переведено на ' . e(mb_strtolower(LANG_NAMES_RU[$l] ?? '')) . ' язык. ✓</p>') . '</div>';
    admin_page('Переводы', $body, 'translate', ['sub' => '<p class="sub">Основной язык сайта — английский. Здесь видно, что ещё не переведено. Нажмите на строку — откроется редактор сразу на нужном языке.</p>']);
    break;

/* ---------------- interface texts ---------------- */
case 'ui':
    // Interface texts are edited one group at a time: the whole set is ~1,700 fields, more than PHP accepts in one form by default.
    $u = ui_all();
    $GROUPS = ['Меню и шапка' => ['nav.', 'cta.quote', 'brand.', 'skip', 'menu', 'lang', 'home', 'crumbs'], 'Профили' => ['all_profiles', 'box_letters', 'lightboxes', 'filter.', 'spec', 'see_made', 'made_with', 'other_', 'ask_price', 'letter_height', 'depths', 'custom', 'no_custom', 'construction', 'ral_', 'mounting', 'mount_text', 'surface', 'led', 'customisation', 'lightbox_note', 'light.'],
               'Анимация, корзина, «Устройство буквы»' => ['motion.', 'basket.', 'anat.', 'film_label'], 'Истории и видео' => ['read_story', 'video', 'photos', 'next_story', 'project_story', 'play', 'yt_note', 'story_en_only'], 'Формы' => ['f.', 'start_request'],
               'Кабинет клиента: вход и регистрация' => ['p.title', 'p.lead', 'p.login', 'p.logout', 'p.email', 'p.password', 'p.forgot', 'p.no_account', 'p.register', 'p.have_account', 'p.name', 'p.company', 'p.vat', 'p.phone', 'p.country', 'p.address', 'p.lang', 'p.consent', 'p.err', 'p.sent_', 'p.verified', 'p.pending', 'p.reset', 'p.why', 'p.login_to'],
               'Кабинет клиента: запросы и КП' => ['p.'], 'Кабинет клиента: письма' => ['p.mail.', 'p.sig'], 'Подвал и прочее' => []];
    $assign = [];
    foreach ($u as $k => $v) {   // most specific group wins: письма → вход → запросы
        foreach (['Кабинет клиента: письма', 'Кабинет клиента: вход и регистрация'] as $g0) foreach ($GROUPS[$g0] as $p0) if (!isset($assign[$k]) && ($k === $p0 || str_starts_with($k, $p0))) $assign[$k] = $g0;
        if (isset($assign[$k])) continue;
        foreach ($GROUPS as $g => $pref) { if (!$pref) continue; foreach ($pref as $p0) if ($k === $p0 || str_starts_with($k, $p0)) { $assign[$k] = $g; continue 3; } }
        $assign[$k] = 'Подвал и прочее';
    }
    $names = array_keys($GROUPS); $gi = max(0, min(count($names) - 1, (int)($_GET['g'] ?? 0))); $gname = $names[$gi];
    if ($POST) { foreach ($u as $k => $v) if (($assign[$k] ?? '') === $gname && isset($_POST['u'][$k])) $u[$k] = clean_val(TA('x'), $_POST['u'][$k], $v); jwrite('ui.json', $u); flash('Тексты сохранены: ' . $gname . '.'); go('a=ui&g=' . $gi . '&l=' . rawurlencode((string)($_POST['_l'] ?? 'ru'))); }
    $seg = '<div class="seg seg-wrap">'; foreach ($names as $i => $g) { $n = count(array_filter($assign, fn($x) => $x === $g)); if ($n) $seg .= '<a href="' . aurl('a=ui&g=' . $i) . '"' . ($i === $gi ? ' aria-current="true"' : '') . '>' . e($g) . ' <small>' . $n . '</small></a>'; } $seg .= '</div>';
    $f = ''; $mine = [];
    foreach ($u as $k => $v) { if (($assign[$k] ?? '') !== $gname) continue; $mine[$k] = $v;
        $f .= field_html($k, F(str_contains((string)($v['en'] ?? ''), '**') || str_contains((string)($v['en'] ?? ''), "\n") || mb_strlen((string)($v['en'] ?? '')) > 70 ? 'textarea' : 'text', $k, ['i18n' => true, 'hint' => '']), $v, ['u', $k]); }
    $body = $seg . '<form method="post" class="edit edit-1" data-dirty>' . csrf_field() . '<input type="hidden" name="_l" value="ru" data-lfield><div class="edit-main"><section class="card sec"><h2>' . e($gname) . '</h2><div class="sec-b uikeys">' . $f . '</div></section></div><div class="sticky-save"><button class="b b-red" type="submit">Сохранить</button></div></form>';
    admin_page('Тексты интерфейса', $body, 'ui', ['langtabs' => true, 'langdata' => $mine, 'lang' => (string)($_GET['l'] ?? 'ru'), 'sub' => '<p class="sub">Кнопки, подписи, сообщения сайта и кабинета клиента, по группам. <code>{n}</code>, <code>{x}</code>, <code>{name}</code>, <code>{link}</code> — подставляемые значения, оставляйте их как есть.</p>']);
    break;

/* ---------------- settings ---------------- */
case 'settings':
    $s = settings();
    if ($POST) {
        $s = clean_sections(settings_schema(), $_POST['c'] ?? [], $s);
        $on = (array)($_POST['langs'] ?? []); $langs = [];
        foreach (array_keys(LANG_NAMES) as $l) $langs[] = ['code' => $l, 'enabled' => in_array($l, $on, true) || $l === 'en'];
        $s['languages'] = $langs; $s['default_lang'] = 'en';
        jwrite('settings.json', $s); flash('Настройки сохранены.'); go('a=settings');
    }
    $ll = ''; foreach (LANG_NAMES as $l => $n) $ll .= '<label class="chk"><input type="checkbox" name="langs[]" value="' . $l . '"' . (in_array($l, site_langs(), true) ? ' checked' : '') . ($l === 'en' ? ' disabled checked' : '') . '> ' . e(LANG_NAMES_RU[$l]) . ' <small>/' . ($l === 'en' ? '' : $l . '/') . '</small>' . ($l === 'en' ? ' <small>— основной, всегда включён</small>' : '') . '</label>';
    $body = '<form method="post" class="edit" data-dirty>' . csrf_field() . '<input type="hidden" name="_l" value="ru" data-lfield><div class="edit-main"><details class="card sec" open><summary><h2>Языки сайта</h2></summary><div class="sec-b"><p class="small">Выключенный язык пропадает из переключателя и из Google, тексты при этом сохраняются.</p>' . $ll . '</div></details>' . form_sections(settings_schema(), $s) . '</div>'
        . '<aside class="card sidebox"><h2>Сохранение</h2><button class="b b-red b-wide" type="submit">Сохранить настройки</button><p class="small"><a href="' . aurl('a=history&rel=settings') . '">История изменений</a></p></aside></form>';
    admin_page('Настройки', $body, 'settings', ['langtabs' => true, 'langdata' => $s, 'lang' => (string)($_GET['l'] ?? 'ru')]);
    break;

/* ---------------- users ---------------- */
case 'users':
    $u = users(); $meL = $_SESSION['uid'];
    if ($POST) {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'add') {
            $l = strtolower(trim((string)($_POST['login'] ?? ''))); $pw = (string)($_POST['pw'] ?? '');
            if (!preg_match('/^[a-z0-9._-]{3,32}$/', $l)) flash('Логин: 3–32 символа, латиница, цифры, точка, дефис.', 'err');
            elseif (isset($u[$l])) flash('Такой логин уже есть.', 'err');
            elseif (mb_strlen($pw) < 10) flash('Пароль — минимум 10 символов.', 'err');
            else { $u[$l] = ['name' => trim((string)($_POST['name'] ?? '')) ?: $l, 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'created' => date('c'), 'by' => $meL]; jwrite('_auth/users.json', $u, false); flash('Пользователь добавлен.'); }
        } elseif ($do === 'pw') {
            if (!password_verify((string)($_POST['old'] ?? ''), $u[$meL]['hash'])) flash('Текущий пароль неверный.', 'err');
            elseif (mb_strlen((string)($_POST['pw'] ?? '')) < 10 || $_POST['pw'] !== ($_POST['pw2'] ?? '')) flash('Новый пароль: минимум 10 символов, оба поля должны совпадать.', 'err');
            else { $u[$meL]['hash'] = password_hash((string)$_POST['pw'], PASSWORD_DEFAULT); jwrite('_auth/users.json', $u, false); flash('Пароль изменён.'); }
        } elseif ($do === 'del') {
            $l = (string)($_POST['login'] ?? '');
            if ($l === $meL) flash('Нельзя удалить самого себя.', 'err'); elseif (isset($u[$l])) { unset($u[$l]); jwrite('_auth/users.json', $u, false); flash('Пользователь удалён.'); }
        }
        go('a=users');
    }
    $rows = ''; foreach ($u as $l => $x) $rows .= '<tr><td><b>' . e($x['name']) . '</b><br><small>' . e($l) . '</small></td><td>' . e(!empty($x['last_login']) ? date('d.m.Y H:i', strtotime($x['last_login'])) : '—') . '</td><td>' . ($l === $meL ? '<em>это вы</em>' : '<form method="post" data-confirm="Удалить пользователя ' . e($l) . '?">' . csrf_field() . '<input type="hidden" name="do" value="del"><input type="hidden" name="login" value="' . e($l) . '"><button class="b b-ghost b-s b-danger" type="submit">Удалить</button></form>') . '</td></tr>';
    $body = '<div class="grid2"><section class="card"><h2>Кто может входить</h2><table class="tbl"><thead><tr><th>Пользователь</th><th>Последний вход</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table></section><div>'
        . '<form method="post" class="card">' . csrf_field() . '<input type="hidden" name="do" value="add"><h2>Добавить пользователя</h2><label class="fld">Имя<input name="name"></label><label class="fld">Логин<input name="login" required pattern="[a-z0-9._\-]{3,32}" autocomplete="off"></label><label class="fld">Пароль (мин. 10 символов)<input type="password" name="pw" required minlength="10" autocomplete="new-password"></label><button class="b b-dark" type="submit">Добавить</button></form>'
        . '<form method="post" class="card">' . csrf_field() . '<input type="hidden" name="do" value="pw"><h2>Сменить мой пароль</h2><label class="fld">Текущий пароль<input type="password" name="old" required autocomplete="current-password"></label><label class="fld">Новый пароль<input type="password" name="pw" required minlength="10" autocomplete="new-password"></label><label class="fld">Новый пароль ещё раз<input type="password" name="pw2" required minlength="10" autocomplete="new-password"></label><button class="b b-dark" type="submit">Сменить пароль</button></form></div></div>';
    admin_page('Пользователи', $body, 'users');
    break;

/* ---------------- history + backup ---------------- */
case 'history':
    $rel = (string)($_GET['rel'] ?? ''); if (!preg_match('#^(pages|profiles|stories)/[a-z0-9-]+$|^(settings|ui)$#', $rel)) go('');
    $dir = DATA . '/_history/' . str_replace('/', '__', $rel); $files = glob($dir . '/*.json') ?: []; rsort($files);
    if ($POST && ($v = basename((string)($_POST['v'] ?? ''))) && is_file($dir . '/' . $v)) { jwrite($rel . '.json', json_decode((string)file_get_contents($dir . '/' . $v), true)); flash('Восстановлена версия от ' . substr($v, 6, 2) . '.' . substr($v, 4, 2) . '.' . substr($v, 0, 4) . '.'); go('a=history&rel=' . rawurlencode($rel)); }
    $rows = ''; foreach ($files as $f) { $b = basename($f); $d = DateTime::createFromFormat('Ymd-His', substr($b, 0, 15)); $j = json_decode((string)file_get_contents($f), true);
        $rows .= '<tr><td>' . e($d ? $d->format('d.m.Y H:i:s') : $b) . '</td><td>' . e($j['updated_by'] ?? '') . '</td><td><form method="post" data-confirm="Вернуть эту версию? Текущая тоже сохранится в истории.">' . csrf_field() . '<input type="hidden" name="v" value="' . e($b) . '"><button class="b b-line b-s" type="submit">Восстановить</button></form></td></tr>'; }
    admin_page('История изменений', '<div class="card">' . ($rows ? '<table class="tbl"><thead><tr><th>Версия до изменения</th><th>Кто сохранял</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table>' : '<p class="small">Пока нет сохранённых версий.</p>') . '</div>', '', ['sub' => '<p class="sub">' . e($rel) . ' · хранятся 30 последних версий</p>']);
    break;
case 'backup':
    if ($POST) {
        @set_time_limit(300); $tmp = tempnam(sys_get_temp_dir(), 'rfbk'); $z = new ZipArchive(); $z->open($tmp, ZipArchive::OVERWRITE);
        $add = function (string $dir, string $pre) use ($z) { $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)); foreach ($it as $f) { $p = $pre . substr($f->getPathname(), strlen($dir)); if (!str_contains($p, '/_auth/')) $z->addFile($f->getPathname(), $p); } };
        $add(DATA, 'data'); if (!empty($_POST['uploads'])) $add(UPLOADS, 'uploads'); $z->close();
        header('Content-Type: application/zip'); header('Content-Disposition: attachment; filename="reclamefabriek-backup-' . date('Y-m-d-His') . '.zip"'); header('Content-Length: ' . filesize($tmp)); readfile($tmp); @unlink($tmp); exit;
    }
    $body = '<form method="post" class="card">' . csrf_field() . '<h2>Скачать копию сайта</h2><p>В архив попадают все тексты, переводы, настройки, история изменений и заявки. Пароли не включаются.</p><label class="chk"><input type="checkbox" name="uploads" value="1" checked> Включить все фото (архив будет больше)</label><button class="b b-red" type="submit">Скачать архив</button></form>'
        . '<section class="card"><h2>Как восстановить</h2><p class="small">Распакуйте архив и загрузите папки <code>data</code> и <code>uploads</code> на сервер поверх существующих. Это может сделать ваш разработчик.</p></section>';
    admin_page('Резервная копия', $body, 'backup');
    break;

/* ---------------- client portal ---------------- */
case 'portal': padm_requests(); break;
case 'preq': padm_request($POST); break;
case 'pquote': padm_quote($POST); break;
case 'pclients': padm_clients($POST); break;
case 'pclient': padm_client($POST); break;
case 'psettings': padm_settings($POST); break;
case 'pfile': padm_file(); break;
case 'ptransfer': if ($POST) padm_transfer(); go('a=inbox');

default: go('');
}
