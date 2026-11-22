<?php
/* Reclame Fabriek · admin 2.4 (Russian): production board, installation calendar, price list, reply templates,
   internal notes / owner / reminder on a request, installation appointment, CSV export, portal figures on the dashboard.
   Included by portal_admin.php. Price list and reply templates are plain files (data/pricelist.json, data/replies.json). */
declare(strict_types=1);

const BOARD_COLS = ['new', 'review', 'quoted', 'accepted', 'production', 'ready', 'installed'];
const MON_RU = [1 => 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];

function uname(string $login): string { return $login === '' ? '' : (string)(users()[$login]['name'] ?? $login); }
function initials(string $name): string { $p = preg_split('/\s+/u', trim($name)) ?: []; $o = ''; foreach (array_slice($p, 0, 2) as $w) $o .= mb_strtoupper(mb_substr($w, 0, 1)); return $o ?: '?'; }

/** Change a request's status the same way everywhere (request page, board). */
function padm_set_status(array $r, string $to, bool $notify): bool {
    if (!isset(ST_RU[$to]) || $to === $r['status']) return false;
    $c = one('SELECT lang FROM rf_clients WHERE id = ?', [$r['client_id']]);
    q('UPDATE rf_requests SET status = ?, updated_at = now() WHERE id = ?', [$to, $r['id']]);
    q("INSERT INTO rf_messages (request_id, author_kind, author_name, body) VALUES (?, 'system', ?, ?)", [$r['id'], me()['name'] ?? '', 'Status: ' . ul('p.st.' . $to, $c['lang'] ?? 'en')]);
    event_add((int)$r['id'], 'status', ['to' => $to, 'by' => $_SESSION['uid']]);
    if ($notify) notify_client((int)$r['id'], 'p.mail.what_status', ['status' => $to]);
    return true;
}

/* ---------------- request page: internal card (only staff see it) ---------------- */
function internal_card(array $r, string $no): string {
    $uo = '<option value="">— не назначен</option>'; foreach (users() as $login => $u) $uo .= '<option value="' . e($login) . '"' . ($r['assignee'] === $login ? ' selected' : '') . '>' . e($u['name'] ?? $login) . '</option>';
    $notes = '';
    foreach (all('SELECT * FROM rf_notes WHERE request_id = ? ORDER BY created_at DESC', [$r['id']]) as $n)
        $notes .= '<li><div class="msg">' . nl2br(e($n['body'])) . '</div><small>' . e($n['author']) . ' · ' . e(pdate($n['created_at'], true)) . '</small>'
                . '<form method="post" class="inl" data-confirm="Удалить заметку?">' . csrf_field() . '<input type="hidden" name="do" value="note_del"><input type="hidden" name="id" value="' . (int)$n['id'] . '"><button class="b b-ghost b-s b-danger" type="submit">Удалить</button></form></li>';
    $due = $r['follow_up'] && strtotime($r['follow_up']) <= strtotime('today');
    return '<section class="card pint" id="internal"><h2>Внутреннее <small>(клиент этого не видит)</small></h2>'
        . '<form method="post" class="pint-f">' . csrf_field() . '<input type="hidden" name="do" value="internal"><label class="fld">Ответственный<select name="assignee">' . $uo . '</select></label>'
        . '<label class="fld">Напомнить ' . ($due ? '<b class="badge">сегодня</b>' : '') . '<input type="date" name="follow_up" value="' . e((string)$r['follow_up']) . '"></label><button class="b b-line b-s" type="submit">Сохранить</button></form>'
        . '<form method="post" class="pint-n">' . csrf_field() . '<input type="hidden" name="do" value="note"><label class="fld">Заметка для коллег<textarea name="body" rows="2" maxlength="4000" placeholder="Например: клиент просил позвонить после 15:00; замер в пятницу"></textarea></label><button class="b b-dark b-s" type="submit">+ Добавить заметку</button></form>'
        . ($notes ? '<ul class="pnotes">' . $notes . '</ul>' : '') . '</section>';
}

/* ---------------- request page: installation appointment ---------------- */
function install_card(array $r, array $c, string $no): string {
    $ST = ['proposed' => ['Предложено клиенту, ждёт подтверждения', 'quoted'], 'confirmed' => ['Подтверждено', 'accepted'], 'change' => ['Клиент просит другую дату', 'cancelled']];
    $has = $r['install_at'] && isset($ST[$r['install_state']]);
    $t0 = $has ? strtotime($r['install_at']) : 0;
    $head = $has ? '<p><b>' . e(date('d.m.Y H:i', $t0)) . '–' . e(date('H:i', $t0 + (int)round((float)$r['install_hours'] * 3600))) . '</b> <span class="pst pst-' . $ST[$r['install_state']][1] . '">' . e($ST[$r['install_state']][0]) . '</span></p>'
        . ($r['install_state'] === 'change' && $r['install_note'] !== '' ? '<div class="msg">Клиент: «' . nl2br(e($r['install_note'])) . '»</div>' : '')
        . '<p class="small"><a href="' . aurl('a=preq&no=' . rawurlencode($no) . '&ics=1') . '">Добавить в календарь (.ics)</a></p>' : '<p class="small">Предложите дату — клиент подтвердит её в кабинете одной кнопкой или попросит другую. Если дату уже согласовали по телефону, отметьте «Уже согласовано».</p>';
    $hrs = rtrim(rtrim(number_format((float)($r['install_hours'] ?: 4), 1, '.', ''), '0'), '.');
    return '<section class="card" id="install"><h2>Монтаж</h2>' . $head
        . '<form method="post" class="pinst">' . csrf_field() . '<input type="hidden" name="do" value="install"><div class="pinst-g"><label class="fld">Дата<input type="date" name="date" value="' . e($has ? date('Y-m-d', $t0) : '') . '" min="' . date('Y-m-d') . '"></label>'
        . '<label class="fld">Время<input type="time" name="time" value="' . e($has ? date('H:i', $t0) : '09:00') . '" step="900"></label><label class="fld">Часов<input name="hours" value="' . e($hrs) . '" inputmode="decimal"></label></div>'
        . '<label class="fld">Бригада <small>(видит клиент)</small><input name="crew" value="' . e($r['install_crew']) . '" maxlength="200" placeholder="Например: Андрей и Сергей, автовышка 18 м"></label>'
        . ($r['site_address'] !== '' ? '<p class="small">Адрес: ' . e($r['site_address']) . '</p>' : '')
        . '<label class="chk"><input type="checkbox" name="confirmed" value="1"> Уже согласовано с клиентом</label><label class="chk"><input type="checkbox" name="notify" value="1" checked> Сообщить клиенту письмом</label>'
        . '<div class="quick"><button class="b b-dark b-s" type="submit">' . ($has ? 'Изменить дату' : 'Предложить дату') . '</button>' . ($has ? '<button class="b b-ghost b-s b-danger" type="submit" name="clear" value="1" data-confirm-btn="Снять дату монтажа?">Снять дату</button>' : '') . '</div></form></section>';
}

/* ---------------- reply templates ---------------- */
function replies(): array { return jread('replies.json', []); }
function replies_picker(array $c, array $r): string {
    $list = replies(); if (!$list) return '<p class="small"><a href="' . aurl('a=preplies') . '">Шаблоны ответов</a> — заготовьте частые ответы на 6 языках.</p>';
    $o = '<option value="">Вставить шаблон…</option>';
    foreach ($list as $x) {
        $txt = tr($x['body'] ?? [], $c['lang']); $txt = strtr($txt, ['{name}' => $c['name'] ?: $c['company'], '{no}' => $r['no'], '{company}' => $c['company'], '{title}' => $r['title']]);
        $o .= '<option value="' . e($txt) . '">' . e($x['title'] ?? '') . (trim((string)($x['body'][$c['lang']] ?? '')) === '' ? ' (нет перевода — по-английски)' : '') . '</option>';
    }
    return '<label class="fld preplies">Шаблон ответа <small>(на языке клиента: ' . e(LANG_NAMES_RU[$c['lang']] ?? '') . ')</small><select data-reply-pick>' . $o . '</select></label>';
}
function padm_replies(bool $POST): void {
    $list = replies(); $id = (string)($_GET['id'] ?? '');
    if ($POST) {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'delete') { $list = array_values(array_filter($list, fn($x) => $x['id'] !== $_POST['id'])); jwrite('replies.json', $list); flash('Шаблон удалён.'); go('a=preplies'); }
        $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 120); $body = [];
        foreach (array_keys(LANG_NAMES) as $l) $body[$l] = mb_substr(trim((string)($_POST['body'][$l] ?? '')), 0, 4000);
        if ($title === '' || ($body['en'] === '' && $body['ru'] === '')) { flash('Нужны название и текст хотя бы на английском или русском.', 'err'); go('a=preplies&id=' . rawurlencode($id ?: 'new')); }
        if ($body['en'] === '') $body['en'] = $body['ru'];
        $row = ['id' => $id && $id !== 'new' ? $id : bin2hex(random_bytes(4)), 'title' => $title, 'body' => $body];
        $found = false; foreach ($list as $i => $x) if ($x['id'] === $row['id']) { $list[$i] = $row; $found = true; }
        if (!$found) $list[] = $row;
        jwrite('replies.json', $list); flash('Шаблон сохранён.'); go('a=preplies');
    }
    if ($id !== '') {
        $x = $id === 'new' ? ['id' => '', 'title' => '', 'body' => []] : (array_values(array_filter($list, fn($y) => $y['id'] === $id))[0] ?? null);
        if (!$x) go('a=preplies');
        $f = '';
        foreach (LANG_NAMES_RU as $l => $ln) $f .= '<label class="fld">' . e($ln) . ($l === 'en' ? ' <small>(запасной язык)</small>' : '') . '<textarea name="body[' . $l . ']" rows="4" lang="' . $l . '">' . e($x['body'][$l] ?? '') . '</textarea></label>';
        admin_page($id === 'new' ? 'Новый шаблон ответа' : 'Шаблон: ' . $x['title'], '<form method="post" class="card">' . csrf_field() . '<label class="fld">Название (видите только вы)<input name="title" value="' . e($x['title']) . '" maxlength="120" required placeholder="Например: Нужен векторный логотип"></label>'
            . '<p class="small">Подстановки: <code>{name}</code> — имя клиента, <code>{company}</code> — компания, <code>{no}</code> — номер запроса, <code>{title}</code> — проект. Клиенту вставляется текст на его языке; если перевода нет — английский.</p><div class="grid2">' . $f . '</div>'
            . '<button class="b b-red" type="submit">Сохранить</button></form>', 'preplies', ['sub' => '<p class="sub"><a href="' . aurl('a=preplies') . '">← Все шаблоны</a></p>']);
        return;
    }
    $tr = '';
    foreach ($list as $x) { $miss = array_filter(array_keys(LANG_NAMES), fn($l) => trim((string)($x['body'][$l] ?? '')) === '');
        $tr .= '<tr><td><a href="' . aurl('a=preplies&id=' . rawurlencode($x['id'])) . '"><b>' . e($x['title']) . '</b></a><br><small>' . e(mb_substr(tr($x['body'], 'ru'), 0, 110)) . '…</small></td><td>' . ($miss ? 'нет: ' . e(strtoupper(implode(', ', $miss))) : '✓ все 6') . '</td>'
             . '<td><form method="post" data-confirm="Удалить шаблон «' . e($x['title']) . '»?">' . csrf_field() . '<input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="' . e($x['id']) . '"><button class="b b-ghost b-s b-danger" type="submit">Удалить</button></form></td></tr>'; }
    admin_page('Шаблоны ответов', '<div class="card">' . ($tr ? '<table class="tbl"><thead><tr><th>Шаблон</th><th>Переводы</th><th></th></tr></thead><tbody>' . $tr . '</tbody></table>' : '<p class="small">Шаблонов пока нет. Частые ответы — «пришлите логотип в векторе», «замер на объекте», «срок изготовления» — можно заготовить один раз на 6 языках и вставлять в переписку в один клик.</p>') . '</div>', 'preplies',
        ['actions' => '<a class="b b-red" href="' . aurl('a=preplies&id=new') . '">+ Новый шаблон</a>', 'sub' => '<p class="sub">Вставляются в ответ клиенту на его языке.</p>']);
}

/* ---------------- price list ---------------- */
function pricelist(): array { return jread('pricelist.json', []); }
function price_picker(string $lang): string {
    $list = pricelist(); if (!$list) return '<p class="small"><a href="' . aurl('a=pprices') . '">Прайс-лист</a> — заведите типовые позиции, чтобы добавлять их в КП одним кликом.</p>';
    $o = '<option value="">Позиция из прайс-листа…</option>';
    foreach ($list as $x) $o .= '<option data-desc="' . e(tr($x['desc'] ?? [], $lang) ?: $x['name']) . '" data-unit="' . e($x['unit']) . '" data-price="' . e((string)$x['price']) . '" data-slug="' . e($x['profile'] ?? '') . '">' . e($x['name'] . ' — ' . money($x['price'], 'EUR', 'ru') . ' / ' . $x['unit']) . '</option>';
    return '<div class="ppick"><select data-price-pick aria-label="Позиция из прайс-листа">' . $o . '</select><button type="button" class="b b-line b-s" data-price-add>+ Из прайса</button></div>';
}
function padm_prices(bool $POST): void {
    $list = pricelist(); $id = (string)($_GET['id'] ?? '');
    if ($POST) {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'delete') { $list = array_values(array_filter($list, fn($x) => $x['id'] !== $_POST['id'])); jwrite('pricelist.json', $list); flash('Позиция удалена.'); go('a=pprices'); }
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 160); $desc = [];
        foreach (array_keys(LANG_NAMES) as $l) $desc[$l] = mb_substr(trim((string)($_POST['desc'][$l] ?? '')), 0, 1000);
        $price = max(0, round((float)str_replace([',', ' '], ['.', ''], (string)($_POST['price'] ?? '0')), 2));
        if ($name === '') { flash('Укажите название позиции.', 'err'); go('a=pprices&id=' . rawurlencode($id ?: 'new')); }
        $slug = (string)($_POST['profile'] ?? ''); if ($slug !== '' && !item('profiles', $slug)) $slug = '';
        $row = ['id' => $id && $id !== 'new' ? $id : bin2hex(random_bytes(4)), 'name' => $name, 'desc' => $desc, 'unit' => mb_substr(trim((string)($_POST['unit'] ?? 'pcs')), 0, 12) ?: 'pcs', 'price' => $price, 'profile' => $slug, 'group' => mb_substr(trim((string)($_POST['group'] ?? '')), 0, 60)];
        $found = false; foreach ($list as $i => $x) if ($x['id'] === $row['id']) { $list[$i] = $row; $found = true; }
        if (!$found) $list[] = $row;
        usort($list, fn($a, $b) => [$a['group'], $a['name']] <=> [$b['group'], $b['name']]);
        jwrite('pricelist.json', $list); flash('Позиция сохранена.'); go('a=pprices');
    }
    if ($id !== '') {
        $x = $id === 'new' ? ['id' => '', 'name' => '', 'desc' => [], 'unit' => 'pcs', 'price' => 0, 'profile' => '', 'group' => ''] : (array_values(array_filter($list, fn($y) => $y['id'] === $id))[0] ?? null);
        if (!$x) go('a=pprices');
        $po = '<option value="">—</option>'; foreach (items('profiles') as $s => $p) $po .= '<option value="' . e($s) . '"' . ($x['profile'] === $s ? ' selected' : '') . '>' . e(tr($p['title'], 'ru')) . '</option>';
        $f = ''; foreach (LANG_NAMES_RU as $l => $ln) $f .= '<label class="fld">' . e($ln) . '<textarea name="desc[' . $l . ']" rows="2" lang="' . $l . '">' . e($x['desc'][$l] ?? '') . '</textarea></label>';
        admin_page($id === 'new' ? 'Новая позиция прайс-листа' : $x['name'], '<form method="post" class="card">' . csrf_field()
            . '<div class="qgrid"><label class="fld">Название (для вас)<input name="name" value="' . e($x['name']) . '" maxlength="160" required placeholder="Например: Монтаж на фасад, до 3 м"></label><label class="fld">Группа<input name="group" value="' . e($x['group']) . '" maxlength="60" placeholder="Буквы / Короба / Монтаж"></label>'
            . '<label class="fld">Профиль с сайта<select name="profile">' . $po . '</select></label></div><div class="qgrid"><label class="fld">Ед.<input name="unit" value="' . e($x['unit']) . '" maxlength="12"></label><label class="fld">Цена, € без НДС<input name="price" value="' . e((string)$x['price']) . '" inputmode="decimal"></label><span></span></div>'
            . '<h2>Текст строки в КП</h2><p class="small">Клиент увидит текст на своём языке; если перевода нет — по-английски, если нет и его — название.</p><div class="grid2">' . $f . '</div><button class="b b-red" type="submit">Сохранить</button></form>', 'pprices', ['sub' => '<p class="sub"><a href="' . aurl('a=pprices') . '">← Прайс-лист</a></p>']);
        return;
    }
    $tr = ''; $g = null;
    foreach ($list as $x) {
        if ($x['group'] !== $g) { $g = $x['group']; $tr .= '<tr class="grp"><th colspan="5">' . e($g ?: 'Без группы') . '</th></tr>'; }
        $tr .= '<tr><td><a href="' . aurl('a=pprices&id=' . rawurlencode($x['id'])) . '"><b>' . e($x['name']) . '</b></a>' . ($x['profile'] ? '<br><small>' . e(tr(item('profiles', $x['profile'])['title'] ?? [], 'ru')) . '</small>' : '') . '</td><td>' . e($x['unit']) . '</td><td class="num">' . e(money($x['price'], 'EUR', 'ru')) . '</td>'
             . '<td>' . (count(array_filter($x['desc'] ?? [], fn($v) => trim((string)$v) !== '')) . ' / 6') . '</td><td><form method="post" data-confirm="Удалить «' . e($x['name']) . '»?">' . csrf_field() . '<input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="' . e($x['id']) . '"><button class="b b-ghost b-s b-danger" type="submit">Удалить</button></form></td></tr>';
    }
    admin_page('Прайс-лист', '<div class="card">' . ($tr ? '<table class="tbl"><thead><tr><th>Позиция</th><th>Ед.</th><th class="num">Цена без НДС</th><th>Языки</th><th></th></tr></thead><tbody>' . $tr . '</tbody></table>' : '<p class="small">Позиций пока нет. Цены видите только вы; в КП позиция добавляется кнопкой «+ Из прайса», текст строки — на языке клиента, цену можно поправить.</p>') . '</div>', 'pprices',
        ['actions' => '<a class="b b-red" href="' . aurl('a=pprices&id=new') . '">+ Новая позиция</a>', 'sub' => '<p class="sub">Типовые позиции для коммерческих предложений.</p>']);
}

/* ---------------- production board ---------------- */
function padm_board(bool $POST): void {
    padm_need_db();
    if ($POST) {
        $r = one('SELECT * FROM rf_requests WHERE no = ?', [(string)($_POST['no'] ?? '')]); $to = (string)($_POST['status'] ?? '');
        $ok = $r && padm_set_status($r, $to, !empty($_POST['notify']));
        if (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') { header('Content-Type: application/json'); echo json_encode(['ok' => $ok, 'msg' => $ok ? $r['no'] . ': ' . ST_RU[$to] : 'Не изменено']); exit; }
        if ($ok) flash($r['no'] . ': ' . ST_RU[$to] . '.'); go('a=pboard');
    }
    $rows = all("SELECT r.*, c.company, c.name,
                   (SELECT count(*) FROM rf_messages m WHERE m.request_id = r.id AND m.author_kind = 'client' AND (r.staff_seen_at IS NULL OR m.created_at > r.staff_seen_at)) AS unread,
                   (SELECT q.status FROM rf_quotes q WHERE q.request_id = r.id ORDER BY version DESC LIMIT 1) AS qstatus,
                   (SELECT p.status FROM rf_proofs p WHERE p.request_id = r.id ORDER BY version DESC LIMIT 1) AS pstatus
                 FROM rf_requests r JOIN rf_clients c ON c.id = r.client_id
                 WHERE r.status IN ('new','review','quoted','accepted','production','ready') OR (r.status = 'installed' AND r.updated_at > now() - interval '30 days')
                 ORDER BY r.deadline NULLS LAST, r.updated_at DESC");
    $mine = (string)($_GET['who'] ?? '');
    $cols = array_fill_keys(BOARD_COLS, '');
    $cnt = array_fill_keys(BOARD_COLS, 0);
    foreach ($rows as $r) {
        if ($mine !== '' && $r['assignee'] !== $mine) continue;
        $cnt[$r['status']]++;
        $dl = ''; if ($r['deadline']) { $days = (int)floor((strtotime($r['deadline']) - strtotime('today')) / 86400); $dl = '<span class="kb-dl' . ($days < 0 ? ' late' : ($days <= 7 ? ' soon' : '')) . '" title="Нужно к">' . ($days < 0 ? 'просрочено ' : 'к ') . e(date('d.m', strtotime($r['deadline']))) . '</span>'; }
        $so = ''; foreach (BOARD_COLS as $s) $so .= '<option value="' . $s . '"' . ($s === $r['status'] ? ' selected' : '') . '>' . e(ST_RU[$s]) . '</option>';
        $flags = ($r['unread'] ? '<span class="kb-f" title="Новые сообщения">✉ ' . (int)$r['unread'] . '</span>' : '') . ($r['pstatus'] === 'pending' ? '<span class="kb-f" title="Макет ждёт клиента">макет…</span>' : ($r['pstatus'] === 'changes' ? '<span class="kb-f warn" title="Клиент просит правки макета">правки</span>' : ''))
               . ($r['qstatus'] === 'sent' ? '<span class="kb-f" title="КП ждёт решения">КП…</span>' : '') . ($r['install_at'] && in_array($r['install_state'], ['proposed', 'confirmed', 'change'], true) ? '<span class="kb-f' . ($r['install_state'] === 'change' ? ' warn' : '') . '" title="Монтаж">🔧 ' . e(date('d.m', strtotime($r['install_at']))) . ($r['install_state'] === 'confirmed' ? ' ✓' : '') . '</span>' : '')
               . ($r['follow_up'] && strtotime($r['follow_up']) <= strtotime('today') ? '<span class="kb-f warn" title="Напоминание">⏰</span>' : '');
        $cols[$r['status']] .= '<li class="kb-card" draggable="true" data-no="' . e($r['no']) . '" data-st="' . e($r['status']) . '"><a href="' . aurl('a=preq&no=' . rawurlencode($r['no'])) . '"><b>' . e($r['no']) . '</b><span>' . e($r['title']) . '</span></a>'
            . '<small>' . e($r['company'] ?: $r['name']) . '</small><div class="kb-meta">' . $dl . $flags . ($r['assignee'] !== '' ? '<span class="kb-who" title="' . e(uname($r['assignee'])) . '">' . e(initials(uname($r['assignee']))) . '</span>' : '') . '</div>'
            . '<form method="post" action="' . aurl('a=pboard') . '" class="kb-move">' . csrf_field() . '<input type="hidden" name="no" value="' . e($r['no']) . '"><input type="hidden" name="notify" value="1" data-kb-notify><label><span class="sr">Перенести ' . e($r['no']) . ' в</span><select name="status">' . $so . '</select></label><button class="b b-ghost b-s" type="submit" title="Перенести">→</button></form></li>';
    }
    $h = ''; foreach (BOARD_COLS as $s) $h .= '<section class="kb-col" data-col="' . $s . '"><h2><span class="pst pst-' . $s . '">' . e(ST_RU[$s]) . '</span><b>' . $cnt[$s] . '</b></h2><ul class="kb-list" data-kbdrop="' . $s . '">' . ($cols[$s] ?: '') . '</ul></section>';
    $uo = '<a href="' . aurl('a=pboard') . '"' . ($mine === '' ? ' aria-current="true"' : '') . '>Все</a>'; foreach (users() as $login => $u) $uo .= '<a href="' . aurl('a=pboard&who=' . rawurlencode($login)) . '"' . ($mine === $login ? ' aria-current="true"' : '') . '>' . e($u['name'] ?? $login) . '</a>';
    admin_page('Доска производства', '<div class="kb-bar"><div class="seg">' . $uo . '</div><label class="chk"><input type="checkbox" data-kb-notify-all checked> Сообщать клиенту о смене статуса</label></div><p class="small kb-help">Перетащите карточку в другую колонку или выберите статус в карточке и нажмите →. Смонтированные показываются 30 дней.</p><div class="kb" data-kb>' . $h . '</div><p class="sr" aria-live="polite" data-kb-live></p>', 'pboard',
        ['sub' => '<p class="sub"><a href="' . aurl('a=portal') . '">← Запросы списком</a></p>']);
}

/* ---------------- installation calendar ---------------- */
function padm_cal(): void {
    padm_need_db();
    $ym = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['m'] ?? '')) ? (string)$_GET['m'] : date('Y-m');
    $first = strtotime($ym . '-01'); $days = (int)date('t', $first); $lead = ((int)date('N', $first)) - 1;
    $from = date('Y-m-d', $first); $to = date('Y-m-d', strtotime('+1 month', $first));
    $ev = [];
    foreach (all("SELECT r.no, r.title, r.install_at, r.install_hours, r.install_state, r.site_address, r.install_crew, c.company, c.name FROM rf_requests r JOIN rf_clients c ON c.id = r.client_id
                  WHERE r.install_at >= ?::date AND r.install_at < ?::date AND r.install_state IN ('proposed','confirmed','change') AND r.status NOT IN ('cancelled','closed') ORDER BY r.install_at", [$from, $to]) as $x)
        $ev[(int)date('j', strtotime($x['install_at']))][] = '<a class="cal-e cal-' . e($x['install_state']) . '" href="' . aurl('a=preq&no=' . rawurlencode($x['no'])) . '#install" title="' . e($x['title'] . ($x['site_address'] ? ' · ' . $x['site_address'] : '') . ($x['install_crew'] ? ' · ' . $x['install_crew'] : '')) . '"><b>' . e(date('H:i', strtotime($x['install_at']))) . '</b> ' . e($x['company'] ?: $x['name']) . '<small>' . e($x['no']) . '</small></a>';
    foreach (all("SELECT r.no, r.title, r.deadline, c.company, c.name FROM rf_requests r JOIN rf_clients c ON c.id = r.client_id WHERE r.deadline >= ?::date AND r.deadline < ?::date AND r.status NOT IN ('cancelled','closed','installed') ORDER BY r.deadline", [$from, $to]) as $x)
        $ev[(int)date('j', strtotime($x['deadline']))][] = '<a class="cal-e cal-dl" href="' . aurl('a=preq&no=' . rawurlencode($x['no'])) . '" title="Клиенту нужно к этой дате: ' . e($x['title']) . '">⚑ ' . e($x['company'] ?: $x['name']) . '<small>' . e($x['no']) . '</small></a>';
    $g = ''; foreach (['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $w) $g .= '<div class="cal-w" aria-hidden="true">' . $w . '</div>';
    for ($i = 0; $i < $lead; $i++) $g .= '<div class="cal-d cal-x" aria-hidden="true"></div>';
    $today = date('Y-m-d');
    for ($dd = 1; $dd <= $days; $dd++) { $ds = $ym . '-' . str_pad((string)$dd, 2, '0', STR_PAD_LEFT); $wk = (int)date('N', strtotime($ds)) >= 6;
        $g .= '<div class="cal-d' . ($ds === $today ? ' cal-today' : '') . ($wk ? ' cal-wk' : '') . '"><span class="cal-n">' . $dd . '<span class="sr"> ' . e(MON_RU[(int)date('n', $first)]) . '</span></span>' . implode('', $ev[$dd] ?? []) . '</div>'; }
    $prev = date('Y-m', strtotime('-1 month', $first)); $next = date('Y-m', strtotime('+1 month', $first));
    $nav = '<div class="cal-nav"><a class="b b-line b-s" href="' . aurl('a=pcal&m=' . $prev) . '">← ' . e(MON_RU[(int)date('n', strtotime($prev . '-01'))]) . '</a><h2>' . e(MON_RU[(int)date('n', $first)] . ' ' . date('Y', $first)) . '</h2><a class="b b-line b-s" href="' . aurl('a=pcal&m=' . $next) . '">' . e(MON_RU[(int)date('n', strtotime($next . '-01'))]) . ' →</a>' . ($ym !== date('Y-m') ? '<a class="b b-ghost b-s" href="' . aurl('a=pcal') . '">Сегодня</a>' : '') . '</div>';
    $legend = '<p class="cal-leg small"><span class="cal-e cal-confirmed">подтверждён</span><span class="cal-e cal-proposed">предложен, ждёт клиента</span><span class="cal-e cal-change">клиент просит другую дату</span><span class="cal-e cal-dl">⚑ клиенту нужно к дате</span></p>';
    admin_page('График монтажа', '<div class="card">' . $nav . $legend . '<div class="cal" role="list">' . $g . '</div></div>', 'pcal', ['sub' => '<p class="sub"><a href="' . aurl('a=portal') . '">← Запросы</a> · Дату монтажа назначают на странице запроса.</p>']);
}

/* ---------------- CSV export ---------------- */
function padm_csv(): void {
    padm_need_db();
    $rows = all("SELECT r.no, r.title, r.status, c.company, c.name, c.email, c.lang, r.assignee, r.created_at, r.updated_at, r.deadline, r.install_at, r.install_state, r.site_address,
                   (SELECT q.no FROM rf_quotes q WHERE q.request_id = r.id ORDER BY version DESC LIMIT 1) AS quote_no,
                   (SELECT q.status FROM rf_quotes q WHERE q.request_id = r.id ORDER BY version DESC LIMIT 1) AS quote_status,
                   (SELECT round(sum(l.qty * l.unit_price), 2) FROM rf_quote_lines l WHERE l.quote_id = (SELECT q.id FROM rf_quotes q WHERE q.request_id = r.id ORDER BY version DESC LIMIT 1)) AS quote_net,
                   (SELECT f.rating FROM rf_feedback f WHERE f.request_id = r.id) AS rating
                 FROM rf_requests r JOIN rf_clients c ON c.id = r.client_id ORDER BY r.created_at DESC");
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="zaprosy-' . date('Y-m-d') . '.csv"'); header('Cache-Control: no-store');
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['Номер', 'Проект', 'Статус', 'Компания', 'Контакт', 'E-mail', 'Язык', 'Ответственный', 'Создан', 'Обновлён', 'Нужно к', 'Монтаж', 'Монтаж: статус', 'Адрес', 'КП', 'КП: статус', 'КП: сумма без НДС', 'Оценка'], ';');
    foreach ($rows as $r) fputcsv($o, [$r['no'], $r['title'], ST_RU[$r['status']] ?? $r['status'], $r['company'], $r['name'], $r['email'], $r['lang'], uname($r['assignee']), pdate($r['created_at'], true), pdate($r['updated_at'], true), $r['deadline'] ? pdate($r['deadline']) : '',
        $r['install_at'] ? pdate($r['install_at'], true) : '', $r['install_state'], $r['site_address'], $r['quote_no'] ?? '', $r['quote_status'] ? (QST_RU[$r['quote_status']] ?? '') : '', $r['quote_net'] !== null ? str_replace('.', ',', (string)$r['quote_net']) : '', $r['rating'] ?? ''], ';');
    fclose($o); exit;
}

/* ---------------- dashboard: portal figures ---------------- */
function portal_kpis(): string {
    try { if (!db_parse(db_url()) || !db_ok()) return ''; } catch (Throwable $e) { return ''; }
    $k = one("SELECT
        (SELECT count(*) FROM rf_requests WHERE status NOT IN ('cancelled','closed','installed')) AS open,
        (SELECT count(*) FROM rf_requests r WHERE r.status IN ('new','review') AND NOT EXISTS (SELECT 1 FROM rf_quotes q WHERE q.request_id = r.id AND q.status IN ('sent','accepted'))) AS need_quote,
        (SELECT count(*) FROM rf_quotes WHERE status = 'sent') AS q_wait,
        (SELECT COALESCE(sum(l.qty * l.unit_price), 0) FROM rf_quotes q JOIN rf_quote_lines l ON l.quote_id = q.id WHERE q.status = 'accepted' AND q.decided_at >= date_trunc('month', now())) AS won_month,
        (SELECT count(*) FROM rf_quotes WHERE status = 'accepted' AND decided_at > now() - interval '90 days') AS acc90,
        (SELECT count(*) FROM rf_quotes WHERE status IN ('declined','expired') AND COALESCE(decided_at, sent_at) > now() - interval '90 days') AS lost90,
        (SELECT round(avg(rating)::numeric, 1) FROM rf_feedback) AS rating, (SELECT count(*) FROM rf_feedback) AS n_rating,
        (SELECT count(*) FROM rf_requests WHERE deadline < current_date AND status NOT IN ('cancelled','closed','installed')) AS late,
        (SELECT count(*) FROM rf_requests WHERE install_at >= now() AND install_at < now() + interval '7 days' AND install_state IN ('proposed','confirmed')) AS inst7");
    $conv = ($k['acc90'] + $k['lost90']) ? (int)round(100 * $k['acc90'] / ($k['acc90'] + $k['lost90'])) . '%' : '—';
    $st = [['Запросы в работе', (string)$k['open'], aurl('a=portal'), $k['need_quote'] ? $k['need_quote'] . ' ждут нашего КП' : 'все с КП'],
           ['КП ждут клиента', (string)$k['q_wait'], aurl('a=portal&st=quoted'), 'отправлены, без решения'],
           ['Принято в этом месяце', money($k['won_month'], 'EUR', 'ru'), aurl('a=portal&st=accepted'), 'без НДС · конверсия 90 дн.: ' . $conv],
           ['Монтажей на 7 дней', (string)$k['inst7'], aurl('a=pcal'), $k['late'] ? $k['late'] . ' запросов просрочено' : ($k['n_rating'] ? 'оценка клиентов ' . str_replace('.', ',', (string)$k['rating']) . ' из 5' : 'просрочек нет')]];
    $h = ''; foreach ($st as [$t, $v, $u, $s]) $h .= '<a class="stat card" href="' . $u . '"><span>' . e($t) . '</span><b>' . e($v) . '</b><small>' . e($s) . '</small></a>';
    // requests per month, last 6 months
    $m = []; for ($i = 5; $i >= 0; $i--) $m[date('Y-m', strtotime("first day of -$i month"))] = [0, 0];
    foreach (all("SELECT to_char(created_at, 'YYYY-MM') AS ym, count(*) AS n, count(*) FILTER (WHERE status IN ('accepted','production','ready','installed','closed')) AS won FROM rf_requests WHERE created_at >= date_trunc('month', now()) - interval '5 months' GROUP BY 1") as $x) if (isset($m[$x['ym']])) $m[$x['ym']] = [(int)$x['n'], (int)$x['won']];
    $max = max(1, ...array_map(fn($v) => $v[0], array_values($m))); $bars = '';
    foreach ($m as $ym => [$n, $won]) $bars .= '<li><span class="mb"><i style="height:' . round(100 * $n / $max) . '%"><em style="height:' . ($n ? round(100 * $won / $n) : 0) . '%"></em></i></span><b>' . $n . '</b><small>' . e(mb_substr(MON_RU[(int)substr($ym, 5)], 0, 3)) . '</small></li>';
    $mine = (string)($_SESSION['uid'] ?? ''); $todo = '';
    foreach (all("SELECT r.no, r.title, r.follow_up, c.company, c.name FROM rf_requests r JOIN rf_clients c ON c.id = r.client_id WHERE r.follow_up <= current_date AND r.status NOT IN ('cancelled','closed') AND (r.assignee = ? OR r.assignee = '') ORDER BY r.follow_up LIMIT 6", [$mine]) as $x)
        $todo .= '<li><a href="' . aurl('a=preq&no=' . rawurlencode($x['no'])) . '#internal">⏰ <b>' . e($x['no']) . '</b> · ' . e($x['company'] ?: $x['name']) . '</a><small>' . e($x['title']) . ' · ' . e(pdate($x['follow_up'])) . '</small></li>';
    foreach (all("SELECT r.no, r.install_at, r.install_state, c.company, c.name FROM rf_requests r JOIN rf_clients c ON c.id = r.client_id WHERE r.install_at >= now() AND r.install_at < now() + interval '7 days' AND r.install_state IN ('proposed','confirmed','change') ORDER BY r.install_at LIMIT 6") as $x)
        $todo .= '<li><a href="' . aurl('a=preq&no=' . rawurlencode($x['no'])) . '#install">🔧 <b>' . e(pdate($x['install_at'], true)) . '</b> · ' . e($x['company'] ?: $x['name']) . '</a><small>' . e($x['no']) . ' · ' . ($x['install_state'] === 'confirmed' ? 'подтверждён' : ($x['install_state'] === 'change' ? 'клиент просит другую дату' : 'ждёт подтверждения клиента')) . '</small></li>';
    return '<h2 class="dh">Кабинет клиентов</h2><div class="stats">' . $h . '</div><div class="grid2"><section class="card"><h2>Запросы по месяцам</h2><p class="small">Высота — все запросы, тёмная часть — дошедшие до принятого КП.</p><ul class="mbars">' . $bars . '</ul></section>'
        . '<section class="card"><h2>Сегодня и на неделе</h2>' . ($todo ? '<ul class="recent">' . $todo . '</ul>' : '<p class="small">Напоминаний на сегодня и монтажей на 7 дней нет.</p>') . '<p><a href="' . aurl('a=pboard') . '">Доска производства →</a> · <a href="' . aurl('a=pcal') . '">График монтажа →</a></p></section></div><h2 class="dh">Сайт</h2>';
}

/* ---------------- dashboard: is the server's PHP still getting security fixes? ---------------- */
/** Supported branches and the first release with the October 2026 critical fixes. Update this table when PHP publishes new releases. */
const PHP_SAFE = ['8.2' => ['8.2.35', '2026-12-31'], '8.3' => ['8.3.36', '2027-12-31'], '8.4' => ['8.4.27', '2028-12-31'], '8.5' => ['8.5.12', '2029-12-31']];
function php_health(): string {
    $br = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION; $v = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
    if (version_compare($br, '8.6', '>=')) return '';
    $row = PHP_SAFE[$br] ?? null;
    if (!$row) $msg = 'PHP ' . $v . ' больше не получает исправлений безопасности. Попросите хостинг или разработчика переключить сайт на PHP 8.4 или 8.5 — сайт с ними совместим.';
    elseif (version_compare($v, $row[0], '<')) $msg = 'На сервере PHP ' . $v . ' без важных исправлений безопасности (нужна версия ' . $row[0] . ' или новее, лучше 8.4 / 8.5). Попросите хостинг обновить PHP.';
    elseif (strtotime($row[1]) - time() < 120 * 86400) $msg = 'PHP ' . $br . ' перестанет получать исправления безопасности ' . date('d.m.Y', strtotime($row[1])) . '. Заранее попросите хостинг переключить сайт на PHP 8.4 или 8.5.';
    else return '';
    return '<div class="flash flash-err phpwarn" role="status"><b>Сервер:</b> ' . e($msg) . '</div>';
}
