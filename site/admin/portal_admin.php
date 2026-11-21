<?php
/* Reclame Fabriek · admin screens for the client portal (Russian). Included by admin/index.php. */
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/portal.php';

const ST_RU = ['new' => 'Новый', 'review' => 'На рассмотрении', 'quoted' => 'КП отправлено', 'accepted' => 'КП принято', 'production' => 'В производстве', 'ready' => 'Готово', 'installed' => 'Смонтировано', 'closed' => 'Закрыт', 'cancelled' => 'Отменён'];
const QST_RU = ['draft' => 'Черновик', 'sent' => 'Отправлено, ждёт решения', 'accepted' => 'Принято клиентом', 'declined' => 'Отклонено', 'expired' => 'Истёк срок', 'superseded' => 'Заменено новой версией'];
const CST_RU = ['pending' => 'Ждёт активации', 'active' => 'Активен', 'blocked' => 'Заблокирован'];

function padm_need_db(): void {
    if (db_ok()) return;
    admin_page('Кабинет клиентов', '<div class="card"><h2>База данных не подключена</h2><p>Кабинету клиентов нужна база PostgreSQL: локальная на сервере или Supabase. Укажите подключение в <a href="' . aurl('a=psettings') . '">настройках кабинета</a>.</p>'
        . (!empty($GLOBALS['DB_ERR']) && $GLOBALS['DB_ERR'] !== 'not_configured' ? '<p class="small">Ошибка: ' . e(mb_substr($GLOBALS['DB_ERR'], 0, 300)) . '</p>' : '') . '</div>', 'portal');
    exit;
}
function padm_counts(): array {
    try { if (!db_parse(db_url()) || !db_ok()) return [0, 0];
        return [(int)val("SELECT count(*) FROM rf_requests r WHERE r.status NOT IN ('cancelled','closed') AND (r.staff_seen_at IS NULL OR EXISTS (SELECT 1 FROM rf_messages m WHERE m.request_id = r.id AND m.author_kind = 'client' AND m.created_at > r.staff_seen_at) OR EXISTS (SELECT 1 FROM rf_quotes q WHERE q.request_id = r.id AND q.status IN ('accepted','declined') AND q.decided_at > r.staff_seen_at))"),
                (int)val("SELECT count(*) FROM rf_clients WHERE status = 'pending' AND email_verified_at IS NOT NULL")];
    } catch (Throwable $e) { return [0, 0]; }
}
function st_ru(string $s): string { return '<span class="pst pst-' . e($s) . '">' . e(ST_RU[$s] ?? $s) . '</span>'; }

/* ---------------- requests list ---------------- */
function padm_requests(): void {
    padm_need_db();
    $f = (string)($_GET['st'] ?? 'open'); $qs = trim((string)($_GET['q'] ?? ''));
    $where = match ($f) { 'open' => "r.status NOT IN ('cancelled','closed','installed')", 'all' => 'true', default => isset(ST_RU[$f]) ? 'r.status = ' . db()->quote($f) : 'true' };
    $args = [];
    if ($qs !== '') { $where .= ' AND (r.no ILIKE ? OR r.title ILIKE ? OR c.company ILIKE ? OR c.email ILIKE ? OR c.name ILIKE ?)'; $args = array_fill(0, 5, '%' . $qs . '%'); }
    $rows = all("SELECT r.*, c.company, c.name, c.email,
                   (SELECT count(*) FROM rf_messages m WHERE m.request_id = r.id AND m.author_kind = 'client' AND (r.staff_seen_at IS NULL OR m.created_at > r.staff_seen_at)) AS unread,
                   (SELECT status FROM rf_quotes q WHERE q.request_id = r.id ORDER BY version DESC LIMIT 1) AS qstatus
                 FROM rf_requests r JOIN rf_clients c ON c.id = r.client_id WHERE $where ORDER BY r.updated_at DESC LIMIT 300", $args);
    $tr = '';
    foreach ($rows as $r) {
        $isNew = $r['staff_seen_at'] === null || $r['unread'] > 0;
        $tr .= '<tr class="' . ($isNew ? 'unread' : '') . '"><td><a href="' . aurl('a=preq&no=' . rawurlencode($r['no'])) . '"><b>' . e($r['no']) . '</b></a></td><td>' . e($r['title']) . '</td><td>' . e($r['company'] ?: $r['name']) . '<br><small>' . e($r['email']) . '</small></td>'
             . '<td>' . st_ru($r['status']) . ($r['qstatus'] ? '<br><small>КП: ' . e(QST_RU[$r['qstatus']] ?? '') . '</small>' : '') . '</td><td>' . ($r['unread'] ? '<b class="badge">' . (int)$r['unread'] . '</b>' : '') . '</td><td>' . e(pdate($r['updated_at'], true)) . '</td></tr>';
    }
    $seg = '<div class="seg">'; foreach (['open' => 'В работе', 'new' => 'Новые', 'quoted' => 'КП отправлено', 'accepted' => 'КП принято', 'production' => 'В производстве', 'all' => 'Все'] as $k => $l) $seg .= '<a href="' . aurl('a=portal&st=' . $k) . '"' . ($f === $k ? ' aria-current="true"' : '') . '>' . e($l) . '</a>'; $seg .= '</div>';
    $search = '<form class="psearch" method="get" action="/admin/"><input type="hidden" name="a" value="portal"><input type="hidden" name="st" value="' . e($f) . '"><input type="search" name="q" value="' . e($qs) . '" placeholder="Номер, проект, компания, e-mail"><button class="b b-line" type="submit">Найти</button></form>';
    admin_page('Запросы клиентов', $seg . $search . '<div class="card">' . ($tr ? '<table class="tbl"><thead><tr><th>№</th><th>Проект</th><th>Клиент</th><th>Статус</th><th>Новые сообщ.</th><th>Обновлено</th></tr></thead><tbody>' . $tr . '</tbody></table>' : '<p class="small">Здесь будут запросы из кабинета клиента. Клиенты попадают в кабинет сами (регистрация на сайте) или по вашему приглашению из раздела «Клиенты».</p>') . '</div>', 'portal',
        ['sub' => '<p class="sub">Запросы, коммерческие предложения и переписка с клиентами. Жирным — новое или непрочитанное.</p>']);
}

/* ---------------- one request ---------------- */
function padm_request(bool $POST): void {
    padm_need_db();
    $no = (string)($_GET['no'] ?? ''); $r = one('SELECT * FROM rf_requests WHERE no = ?', [$no]); if (!$r) go('a=portal');
    $rid = (int)$r['id']; $c = one('SELECT * FROM rf_clients WHERE id = ?', [$r['client_id']]); $me = me()['name'] ?? 'Reclame Fabriek';
    if ($POST) {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'message') {
            $body = trim(mb_substr((string)($_POST['body'] ?? ''), 0, 8000));
            if ($body !== '' || !empty($_FILES['files']['name'][0])) {
                $mid = (int)val("INSERT INTO rf_messages (request_id, author_kind, author_name, body) VALUES (?, 'staff', ?, ?) RETURNING id", [$rid, $me, $body]);
                [$n, $err] = files_store('files', $rid, $mid, 'staff');
                q("UPDATE rf_requests SET updated_at = now(), staff_seen_at = now(), status = CASE WHEN status = 'new' THEN 'review' ELSE status END WHERE id = ?", [$rid]);
                notify_client($rid, 'p.mail.what_message');
                flash('Сообщение отправлено клиенту.' . ($err ? ' Не прикреплены: ' . implode(', ', $err) : ''));
            }
        } elseif ($do === 'status' && isset(ST_RU[$_POST['status'] ?? ''])) {
            $to = (string)$_POST['status'];
            if ($to !== $r['status']) {
                q('UPDATE rf_requests SET status = ?, updated_at = now() WHERE id = ?', [$to, $rid]);
                q("INSERT INTO rf_messages (request_id, author_kind, author_name, body) VALUES (?, 'system', ?, ?)", [$rid, $me, 'Status: ' . ul('p.st.' . $to, $c['lang'])]);
                event_add($rid, 'status', ['to' => $to, 'by' => $_SESSION['uid']]);
                if (!empty($_POST['notify'])) notify_client($rid, 'p.mail.what_status', ['status' => $to]);
                flash('Статус изменён: ' . ST_RU[$to] . '.');
            }
        } elseif ($do === 'newquote') {
            $cfg = portal_cfg(); $ver = (int)val('SELECT COALESCE(max(version), 0) + 1 FROM rf_quotes WHERE request_id = ?', [$rid]);
            $prev = one("SELECT * FROM rf_quotes WHERE request_id = ? ORDER BY version DESC LIMIT 1", [$rid]);
            $qid = tx(function () use ($rid, $r, $c, $cfg, $ver, $prev, $me) {
                $qno = next_no('RF-Q') . ($ver > 1 ? '-v' . $ver : '');
                $qid = (int)val('INSERT INTO rf_quotes (request_id, no, version, vat_rate, valid_until, lead_time, intro, terms, created_by) VALUES (?, ?, ?, ?, current_date + make_interval(days => ?), ?, ?, ?, ?) RETURNING id',
                    [$rid, $qno, $ver, $prev['vat_rate'] ?? $cfg['vat'], (int)$cfg['valid_days'], $prev['lead_time'] ?? '', $prev['intro'] ?? '', $prev['terms'] ?? tr($cfg['terms'] ?? [], $c['lang']), $me]);
                if ($prev) { foreach (all('SELECT * FROM rf_quote_lines WHERE quote_id = ? ORDER BY position, id', [$prev['id']]) as $x) q('INSERT INTO rf_quote_lines (quote_id, position, profile_slug, description, qty, unit, unit_price) VALUES (?, ?, ?, ?, ?, ?, ?)', [$qid, $x['position'], $x['profile_slug'], $x['description'], $x['qty'], $x['unit'], $x['unit_price']]); }
                else foreach (all('SELECT * FROM rf_request_items WHERE request_id = ? ORDER BY position', [$rid]) as $i => $it) {
                    $p = $it['profile_slug'] ? item('profiles', $it['profile_slug']) : null;
                    $d = trim(($p ? tr($p['title'], $c['lang']) . ' · ' . tr($p['subtitle'] ?? [], $c['lang']) : 'Sign') . ($it['content'] !== '' ? ' — “' . $it['content'] . '”' : '') . ($it['height_mm'] ? ', ' . $it['height_mm'] . ' mm' : '') . ($it['depth'] !== '' ? ', ' . $it['depth'] : ''));
                    q('INSERT INTO rf_quote_lines (quote_id, position, profile_slug, description, qty, unit, unit_price) VALUES (?, ?, ?, ?, ?, ?, 0)', [$qid, $i, $it['profile_slug'], $d, $it['qty'], 'set']);
                }
                return $qid;
            });
            go('a=pquote&id=' . $qid);
        }
        go('a=preq&no=' . rawurlencode($no) . '#thread');
    }
    q('UPDATE rf_requests SET staff_seen_at = now() WHERE id = ?', [$rid]);
    $GLOBALS['LANG'] = 'ru';
    $quotes = all('SELECT * FROM rf_quotes WHERE request_id = ? ORDER BY version DESC', [$rid]); $ql = '';
    foreach ($quotes as $qq) { $t = quote_totals(all('SELECT qty, unit_price FROM rf_quote_lines WHERE quote_id = ?', [$qq['id']]), (float)$qq['vat_rate']);
        $ql .= '<tr><td><a href="' . aurl('a=pquote&id=' . $qq['id']) . '"><b>' . e($qq['no']) . '</b></a></td><td>' . e(QST_RU[$qq['status']]) . ($qq['decided_name'] ? '<br><small>' . e($qq['decided_name']) . ' · ' . e(pdate($qq['decided_at'], true)) . '</small>' : '') . ($qq['decision_note'] ? '<br><small>«' . e($qq['decision_note']) . '»</small>' : '') . '</td><td>' . e(money($t['total'], $qq['currency'], 'ru')) . '</td><td>' . e(pdate($qq['sent_at'])) . '</td></tr>'; }
    $files = ''; foreach (all('SELECT * FROM rf_files WHERE request_id = ? ORDER BY id', [$rid]) as $f) $files .= '<li><a href="' . aurl('a=pfile&id=' . $f['id']) . '">' . e($f['original_name']) . '</a> <small>' . e(fsize((int)$f['size'])) . ' · ' . ($f['uploader_kind'] === 'client' ? 'клиент' : 'мы') . ' · ' . e(pdate($f['created_at'])) . '</small></li>';
    $sto = ''; foreach (ST_RU as $k => $l) $sto .= '<option value="' . $k . '"' . ($r['status'] === $k ? ' selected' : '') . '>' . e($l) . '</option>';
    $thread = thread_html($rid, 'staff', aurl('a=pfile&id='));
    $body = '<div class="grid2 pgrid"><div>'
        . '<section class="card" id="thread"><h2>Переписка с клиентом</h2>' . ($thread ? '<ol class="pthread">' . $thread . '</ol>' : '<p class="small">Сообщений пока нет.</p>')
        . '<form method="post" enctype="multipart/form-data" class="preply">' . csrf_field() . '<input type="hidden" name="do" value="message"><label class="fld">Ответ клиенту <small>(клиент получит письмо на ' . e(LANG_NAMES_RU[$c['lang']] ?? '') . ' языке, если включил уведомления)</small><textarea name="body" rows="4"></textarea></label>'
        . '<label class="fld">Файлы<input type="file" name="files[]" multiple></label><button class="b b-red" type="submit">Отправить</button></form></section>'
        . '<section class="card"><h2>Коммерческие предложения</h2>' . ($ql ? '<table class="tbl"><thead><tr><th>№</th><th>Статус</th><th>Сумма с НДС</th><th>Отправлено</th></tr></thead><tbody>' . $ql . '</tbody></table>' : '<p class="small">КП ещё нет. Создайте его — строки заполнятся из запроса клиента, останется вписать цены.</p>')
        . '<form method="post">' . csrf_field() . '<input type="hidden" name="do" value="newquote"><button class="b b-dark" type="submit">' . ($quotes ? '+ Новая версия КП' : '+ Создать КП') . '</button></form></section></div>'
        . '<div><section class="card"><h2>Статус</h2><form method="post" class="pstatus">' . csrf_field() . '<input type="hidden" name="do" value="status"><select name="status">' . $sto . '</select><label class="chk"><input type="checkbox" name="notify" value="1" checked> Сообщить клиенту письмом</label><button class="b b-line" type="submit">Сохранить статус</button></form></section>'
        . '<section class="card"><h2>Клиент</h2><p><b>' . e($c['company'] ?: '—') . '</b><br>' . e($c['name']) . '<br><a href="mailto:' . e($c['email']) . '">' . e($c['email']) . '</a>' . ($c['phone'] ? '<br>' . e($c['phone']) : '') . ($c['vat'] ? '<br>НДС: ' . e($c['vat']) : '') . '</p><p><a href="' . aurl('a=pclient&id=' . $c['id']) . '">Карточка клиента →</a></p></section>'
        . '<section class="card"><h2>Что просит клиент</h2>' . items_table($rid, true)
        . '<dl class="dl"><dt>Создан</dt><dd>' . e(pdate($r['created_at'], true)) . '</dd><dt>Источник</dt><dd>' . ($r['source'] === 'website' ? 'Форма на сайте' : 'Кабинет') . '</dd>' . ($r['site_address'] !== '' ? '<dt>Адрес монтажа</dt><dd>' . e($r['site_address']) . '</dd>' : '') . ($r['deadline'] ? '<dt>Нужно к</dt><dd>' . e(pdate($r['deadline'])) . '</dd>' : '') . '</dl>'
        . ($r['notes'] !== '' ? '<div class="msg">' . nl2br(e($r['notes'])) . '</div>' : '') . '</section>'
        . '<section class="card"><h2>Файлы</h2>' . ($files ? '<ul class="pfiles">' . $files . '</ul>' : '<p class="small">Файлов нет.</p>') . '</section></div></div>';
    admin_page($r['no'] . ' · ' . $r['title'], $body, 'portal', ['sub' => '<p class="sub"><a href="' . aurl('a=portal') . '">← Все запросы</a> · ' . st_ru($r['status']) . '</p>']);
}

/* ---------------- quote editor ---------------- */
function padm_quote(bool $POST): void {
    padm_need_db();
    $qq = one('SELECT * FROM rf_quotes WHERE id = ?', [(int)($_GET['id'] ?? 0)]); if (!$qq) go('a=portal');
    $r = one('SELECT * FROM rf_requests WHERE id = ?', [$qq['request_id']]); $c = one('SELECT * FROM rf_clients WHERE id = ?', [$r['client_id']]);
    if (isset($_GET['print'])) {   // printable copy in the client's language (for email / paper)
        $GLOBALS['LANG'] = $c['lang'];
        echo '<!doctype html><html lang="' . e($c['lang']) . '"><head><meta charset="utf-8"><title>' . e($qq['no']) . '</title><link rel="stylesheet" href="/assets/css/fonts.css"><link rel="stylesheet" href="/assets/css/site.css"><link rel="stylesheet" href="/assets/css/portal.css"></head><body class="is-portal"><main class="portal"><div class="wrap pt-quote">' . quote_doc($qq, $r, $c, $c['lang'], 'staff') . '</div></main></body></html>'; exit;
    }
    if ($POST) {
        $do = (string)($_POST['do'] ?? '');
        if ($qq['status'] === 'draft' && in_array($do, ['save', 'send'], true)) {
            $vu = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_POST['valid_until'] ?? '')) ? $_POST['valid_until'] : null;
            $vat = max(0, min(50, (float)str_replace(',', '.', (string)($_POST['vat_rate'] ?? '21'))));
            tx(function () use ($qq, $vu, $vat) {
                q('UPDATE rf_quotes SET valid_until = ?, vat_rate = ?, lead_time = ?, intro = ?, terms = ? WHERE id = ?', [$vu, $vat, mb_substr(trim((string)($_POST['lead_time'] ?? '')), 0, 200), mb_substr(trim((string)($_POST['intro'] ?? '')), 0, 4000), mb_substr(trim((string)($_POST['terms'] ?? '')), 0, 6000), $qq['id']]);
                q('DELETE FROM rf_quote_lines WHERE quote_id = ?', [$qq['id']]); $i = 0;
                foreach ((array)($_POST['lines'] ?? []) as $x) {
                    if (!is_array($x) || trim((string)($x['description'] ?? '')) === '') continue;
                    $num = fn($v) => (float)str_replace([',', ' '], ['.', ''], (string)$v);
                    q('INSERT INTO rf_quote_lines (quote_id, position, profile_slug, description, qty, unit, unit_price) VALUES (?, ?, ?, ?, ?, ?, ?)',
                      [$qq['id'], $i++, item('profiles', (string)($x['profile_slug'] ?? '')) ? (string)$x['profile_slug'] : '', mb_substr(trim((string)$x['description']), 0, 1000), max(0, $num($x['qty'] ?? 1)), mb_substr(trim((string)($x['unit'] ?? 'pcs')), 0, 12) ?: 'pcs', max(0, $num($x['unit_price'] ?? 0))]);
                }
            });
            if ($do === 'send') {
                $n = (int)val('SELECT count(*) FROM rf_quote_lines WHERE quote_id = ?', [$qq['id']]);
                if (!$n) { flash('Добавьте хотя бы одну строку.', 'err'); go('a=pquote&id=' . $qq['id']); }
                tx(function () use ($qq, $r) {
                    q("UPDATE rf_quotes SET status = 'superseded' WHERE request_id = ? AND status = 'sent' AND id <> ?", [$r['id'], $qq['id']]);
                    q("UPDATE rf_quotes SET status = 'sent', sent_at = now() WHERE id = ?", [$qq['id']]);
                    q("UPDATE rf_requests SET status = CASE WHEN status IN ('new','review','quoted') THEN 'quoted' ELSE status END, updated_at = now() WHERE id = ?", [$r['id']]);
                    q("INSERT INTO rf_messages (request_id, author_kind, author_name, body) VALUES (?, 'system', ?, ?)", [$r['id'], me()['name'] ?? '', 'Quote ' . $qq['no'] . ' sent']);
                    event_add((int)$r['id'], 'quote_sent', ['quote' => $qq['no']]);
                });
                notify_client((int)$r['id'], 'p.mail.what_quote', ['q' => $qq['no']]);
                flash('КП ' . $qq['no'] . ' отправлено клиенту.'); go('a=preq&no=' . rawurlencode($r['no']));
            }
            flash('Черновик сохранён.'); go('a=pquote&id=' . $qq['id']);
        }
        if ($do === 'delete' && $qq['status'] === 'draft') { q('DELETE FROM rf_quotes WHERE id = ?', [$qq['id']]); flash('Черновик удалён.'); go('a=preq&no=' . rawurlencode($r['no'])); }
        go('a=pquote&id=' . $qq['id']);
    }
    $GLOBALS['LANG'] = 'ru';
    $lines = all('SELECT * FROM rf_quote_lines WHERE quote_id = ? ORDER BY position, id', [$qq['id']]);
    $t = quote_totals($lines, (float)$qq['vat_rate']);
    $popt = function (string $sel) { $o = '<option value="">—</option>'; foreach (items('profiles') as $s => $p) $o .= '<option value="' . e($s) . '"' . ($sel === $s ? ' selected' : '') . '>' . e(tr($p['title'], 'ru')) . '</option>'; return $o; };
    $row = fn($i, $x) => '<tr data-qline><td><textarea name="lines[' . $i . '][description]" rows="2">' . e($x['description'] ?? '') . '</textarea><select name="lines[' . $i . '][profile_slug]" title="Профиль с сайта (ссылка в КП)">' . $popt((string)($x['profile_slug'] ?? '')) . '</select></td>'
        . '<td><input name="lines[' . $i . '][qty]" value="' . e(rtrim(rtrim((string)($x['qty'] ?? '1'), '0'), '.') ?: '1') . '" inputmode="decimal" data-q></td><td><input name="lines[' . $i . '][unit]" value="' . e($x['unit'] ?? 'pcs') . '" maxlength="12"></td>'
        . '<td><input name="lines[' . $i . '][unit_price]" value="' . e((string)($x['unit_price'] ?? '0')) . '" inputmode="decimal" data-p></td><td class="num" data-sum></td><td><button type="button" class="b b-ghost b-s b-danger" data-qrm title="Удалить строку">×</button></td></tr>';
    if ($qq['status'] === 'draft') {
        $tr = ''; foreach ($lines as $i => $x) $tr .= $row($i, $x); if (!$lines) $tr = $row(0, []);
        $body = '<form method="post" class="card qedit" data-qedit>' . csrf_field() . '<div class="qgrid"><label class="fld">Действительно до<input type="date" name="valid_until" value="' . e((string)$qq['valid_until']) . '"></label><label class="fld">НДС, %<input name="vat_rate" value="' . e(rtrim(rtrim((string)$qq['vat_rate'], '0'), '.')) . '" inputmode="decimal" data-vat></label>'
            . '<label class="fld">Срок изготовления<input name="lead_time" value="' . e($qq['lead_time']) . '" placeholder="например: 15 рабочих дней после согласования чертежа"></label></div>'
            . '<label class="fld">Вступление (видит клиент)<textarea name="intro" rows="3">' . e($qq['intro']) . '</textarea></label>'
            . '<table class="tbl qlines"><thead><tr><th>Описание и профиль</th><th>Кол-во</th><th>Ед.</th><th>Цена, € без НДС</th><th class="num">Сумма</th><th></th></tr></thead><tbody data-qbody>' . $tr . '</tbody></table><template data-qtpl>' . $row(999, []) . '</template>'
            . '<p><button type="button" class="b b-line b-s" data-qadd>+ Строка</button></p><dl class="qtot"><dt>Без НДС</dt><dd data-tnet>' . e(money($t['net'], 'EUR', 'ru')) . '</dd><dt>НДС</dt><dd data-tvat>' . e(money($t['vat'], 'EUR', 'ru')) . '</dd><dt>Итого</dt><dd data-ttot><b>' . e(money($t['total'], 'EUR', 'ru')) . '</b></dd></dl>'
            . '<label class="fld">Условия (на языке клиента: ' . e(LANG_NAMES_RU[$c['lang']] ?? '') . ')<textarea name="terms" rows="4">' . e($qq['terms']) . '</textarea></label>'
            . '<div class="quick"><button class="b b-line" type="submit" name="do" value="save">Сохранить черновик</button><button class="b b-red" type="submit" name="do" value="send" data-confirm-btn="Отправить КП клиенту? Он получит письмо и сможет принять его онлайн.">Отправить клиенту</button>'
            . '<a class="b b-ghost" href="' . aurl('a=pquote&id=' . $qq['id'] . '&print=1') . '" target="_blank" rel="noopener">Как увидит клиент / печать ↗</a></div></form>'
            . '<form method="post" data-confirm="Удалить черновик КП?">' . csrf_field() . '<button class="b b-ghost b-danger" type="submit" name="do" value="delete">Удалить черновик</button></form>';
    } else {
        $body = '<div class="card"><p>' . e(QST_RU[$qq['status']]) . ($qq['sent_at'] ? ' · отправлено ' . e(pdate($qq['sent_at'], true)) : '') . ($qq['decided_name'] ? ' · ' . e($qq['decided_name']) . ', ' . e(pdate($qq['decided_at'], true)) . ($qq['decided_ip'] ? ', IP ' . e($qq['decided_ip']) : '') : '') . '</p>'
            . ($qq['decision_note'] ? '<div class="msg">Комментарий клиента: ' . nl2br(e($qq['decision_note'])) . '</div>' : '')
            . '<table class="tbl"><thead><tr><th>Описание</th><th>Кол-во</th><th>Цена</th><th class="num">Сумма</th></tr></thead><tbody>';
        foreach ($lines as $x) $body .= '<tr><td>' . nl2br(e($x['description'])) . '</td><td>' . e((string)(float)$x['qty']) . ' ' . e($x['unit']) . '</td><td>' . e(money($x['unit_price'], 'EUR', 'ru')) . '</td><td class="num">' . e(money((float)$x['qty'] * (float)$x['unit_price'], 'EUR', 'ru')) . '</td></tr>';
        $body .= '</tbody></table><dl class="qtot"><dt>Без НДС</dt><dd>' . e(money($t['net'], 'EUR', 'ru')) . '</dd><dt>НДС ' . e((string)(float)$qq['vat_rate']) . '%</dt><dd>' . e(money($t['vat'], 'EUR', 'ru')) . '</dd><dt>Итого</dt><dd><b>' . e(money($t['total'], 'EUR', 'ru')) . '</b></dd></dl>'
            . '<div class="quick"><a class="b b-line" href="' . aurl('a=pquote&id=' . $qq['id'] . '&print=1') . '" target="_blank" rel="noopener">Печать / PDF ↗</a><form method="post" action="' . aurl('a=preq&no=' . rawurlencode($r['no'])) . '">' . csrf_field() . '<input type="hidden" name="do" value="newquote"><button class="b b-dark" type="submit">+ Новая версия на основе этой</button></form></div></div>';
    }
    admin_page('КП ' . $qq['no'], $body, 'portal', ['sub' => '<p class="sub"><a href="' . aurl('a=preq&no=' . rawurlencode($r['no'])) . '">← ' . e($r['no']) . ' · ' . e($r['title']) . '</a> · ' . e($c['company'] ?: $c['name']) . '</p>']);
}

/* ---------------- clients ---------------- */
function invite_link(array $c): string { return pabs('reset', $c['lang']) . '?t=' . token_make((int)$c['id'], 'invite', 24 * 7); }
function padm_clients(bool $POST): void {
    padm_need_db();
    if ($POST && ($_POST['do'] ?? '') === 'create') {
        $email = strtolower(trim((string)($_POST['email'] ?? ''))); $l = in_array($_POST['lang'] ?? '', array_keys(LANG_NAMES), true) ? $_POST['lang'] : 'en';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('Проверьте e-mail.', 'err'); go('a=pclients'); }
        if ($ex = one('SELECT id FROM rf_clients WHERE email = ?', [$email])) { flash('Такой клиент уже есть.', 'err'); go('a=pclient&id=' . $ex['id']); }
        $id = (int)val("INSERT INTO rf_clients (email, name, company, phone, lang, status) VALUES (?, ?, ?, ?, ?, 'pending') RETURNING id", [$email, trim((string)($_POST['name'] ?? '')), trim((string)($_POST['company'] ?? '')), trim((string)($_POST['phone'] ?? '')), $l]);
        $c = one('SELECT * FROM rf_clients WHERE id = ?', [$id]); $link = invite_link($c);
        $sent = mail_client($c, 'p.mail.invite_s', 'p.mail.invite_b', ['link' => $link]);
        $_SESSION['plink'] = $link; flash($sent ? 'Клиент создан, приглашение отправлено на ' . $email . '.' : 'Клиент создан. Письмо не ушло (почта на сервере не настроена) — отправьте ссылку-приглашение сами.', $sent ? 'ok' : 'err');
        go('a=pclient&id=' . $id);
    }
    $f = (string)($_GET['st'] ?? ''); $where = isset(CST_RU[$f]) ? 'WHERE c.status = ' . db()->quote($f) : '';
    $rows = all("SELECT c.*, (SELECT count(*) FROM rf_requests r WHERE r.client_id = c.id) AS n FROM rf_clients c $where ORDER BY c.created_at DESC LIMIT 500");
    $tr = ''; foreach ($rows as $c) $tr .= '<tr class="' . ($c['status'] === 'pending' && $c['email_verified_at'] ? 'unread' : '') . '"><td><a href="' . aurl('a=pclient&id=' . $c['id']) . '"><b>' . e($c['company'] ?: '—') . '</b></a><br><small>' . e($c['name']) . '</small></td><td>' . e($c['email']) . '</td><td>' . e(CST_RU[$c['status']]) . ($c['status'] === 'pending' ? '<br><small>' . ($c['email_verified_at'] ? 'e-mail подтверждён' : 'e-mail не подтверждён') . '</small>' : '') . '</td><td>' . (int)$c['n'] . '</td><td>' . e(strtoupper($c['lang'])) . '</td><td>' . e(pdate($c['created_at'])) . '</td><td>' . e(pdate($c['last_login_at'], true)) . '</td></tr>';
    $seg = '<div class="seg"><a href="' . aurl('a=pclients') . '"' . (!$f ? ' aria-current="true"' : '') . '>Все</a>'; foreach (CST_RU as $k => $l) $seg .= '<a href="' . aurl('a=pclients&st=' . $k) . '"' . ($f === $k ? ' aria-current="true"' : '') . '>' . e($l) . '</a>'; $seg .= '</div>';
    $lo = ''; foreach (LANG_NAMES_RU as $k => $l) $lo .= '<option value="' . $k . '"' . ($k === 'lv' ? ' selected' : '') . '>' . e($l) . '</option>';
    $body = $seg . '<div class="grid2 pgrid"><section class="card">' . ($tr ? '<table class="tbl"><thead><tr><th>Компания</th><th>E-mail</th><th>Статус</th><th>Запросов</th><th>Язык</th><th>Создан</th><th>Последний вход</th></tr></thead><tbody>' . $tr . '</tbody></table>' : '<p class="small">Клиентов пока нет.</p>') . '</section>'
        . '<form method="post" class="card">' . csrf_field() . '<input type="hidden" name="do" value="create"><h2>Пригласить клиента</h2><p class="small">Клиент получит письмо со ссылкой, задаст пароль и сразу увидит свой кабинет.</p>'
        . '<label class="fld">E-mail<input type="email" name="email" required></label><label class="fld">Имя<input name="name"></label><label class="fld">Компания<input name="company"></label><label class="fld">Телефон<input name="phone"></label><label class="fld">Язык писем<select name="lang">' . $lo . '</select></label><button class="b b-dark" type="submit">Создать и пригласить</button></form></div>';
    admin_page('Клиенты', $body, 'pclients', ['sub' => '<p class="sub">Аккаунты кабинета клиента. Жирным — подтвердили e-mail и ждут активации.</p>']);
}
function padm_client(bool $POST): void {
    padm_need_db();
    $c = one('SELECT * FROM rf_clients WHERE id = ?', [(int)($_GET['id'] ?? 0)]); if (!$c) go('a=pclients');
    if ($POST) {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'status' && isset(CST_RU[$_POST['status'] ?? ''])) {
            q('UPDATE rf_clients SET status = ?, staff_note = ? WHERE id = ?', [$_POST['status'], mb_substr((string)($_POST['staff_note'] ?? ''), 0, 4000), $c['id']]);
            if ($_POST['status'] === 'active' && $c['status'] === 'pending' && $c['pass_hash'] && $c['email_verified_at']) mail_client($c, 'p.mail.active_s', 'p.mail.active_b', ['link' => pabs('login', $c['lang'])]);
            flash('Сохранено.');
        } elseif ($do === 'invite') {
            $link = invite_link($c); $sent = mail_client($c, 'p.mail.invite_s', 'p.mail.invite_b', ['link' => $link]); $_SESSION['plink'] = $link;
            flash($sent ? 'Ссылка отправлена клиенту.' : 'Письмо не ушло — скопируйте ссылку ниже и отправьте клиенту сами.', $sent ? 'ok' : 'err');
        } elseif ($do === 'delete' && ($_POST['confirm'] ?? '') === $c['email']) {
            foreach (all('SELECT f.stored_name FROM rf_files f JOIN rf_requests r ON r.id = f.request_id WHERE r.client_id = ?', [$c['id']]) as $f) @unlink(DATA . '/_portal/files/' . $f['stored_name']);
            q('DELETE FROM rf_clients WHERE id = ?', [$c['id']]); flash('Клиент и все его данные удалены.'); go('a=pclients');
        } elseif ($do === 'delete') flash('Для удаления введите e-mail клиента точно.', 'err');
        go('a=pclient&id=' . $c['id']);
    }
    $reqs = all('SELECT * FROM rf_requests WHERE client_id = ? ORDER BY updated_at DESC', [$c['id']]); $rl = '';
    foreach ($reqs as $r) $rl .= '<tr><td><a href="' . aurl('a=preq&no=' . rawurlencode($r['no'])) . '">' . e($r['no']) . '</a></td><td>' . e($r['title']) . '</td><td>' . st_ru($r['status']) . '</td><td>' . e(pdate($r['updated_at'])) . '</td></tr>';
    $so = ''; foreach (CST_RU as $k => $l) $so .= '<option value="' . $k . '"' . ($c['status'] === $k ? ' selected' : '') . '>' . e($l) . '</option>';
    $link = $_SESSION['plink'] ?? ''; unset($_SESSION['plink']);
    $body = ($link ? '<div class="card plink"><h2>Ссылка для клиента (действует 7 дней)</h2><input readonly value="' . e($link) . '" data-select></div>' : '')
        . '<div class="grid2 pgrid"><section class="card"><h2>Данные</h2><dl class="dl"><dt>Компания</dt><dd>' . e($c['company'] ?: '—') . '</dd><dt>Имя</dt><dd>' . e($c['name'] ?: '—') . '</dd><dt>E-mail</dt><dd><a href="mailto:' . e($c['email']) . '">' . e($c['email']) . '</a> ' . ($c['email_verified_at'] ? '✓' : '<small>(не подтверждён)</small>') . '</dd>'
        . '<dt>Телефон</dt><dd>' . e($c['phone'] ?: '—') . '</dd><dt>НДС</dt><dd>' . e($c['vat'] ?: '—') . '</dd><dt>Страна</dt><dd>' . e($c['country'] ?: '—') . '</dd><dt>Адрес</dt><dd>' . e($c['address'] ?: '—') . '</dd><dt>Язык</dt><dd>' . e(LANG_NAMES_RU[$c['lang']] ?? $c['lang']) . '</dd>'
        . '<dt>Создан</dt><dd>' . e(pdate($c['created_at'], true)) . '</dd><dt>Последний вход</dt><dd>' . e(pdate($c['last_login_at'], true)) . '</dd><dt>Пароль</dt><dd>' . ($c['pass_hash'] ? 'задан' : 'ещё не задан') . '</dd></dl>'
        . '<h2>Запросы</h2>' . ($rl ? '<table class="tbl"><tbody>' . $rl . '</tbody></table>' : '<p class="small">Запросов нет.</p>') . '</section><div>'
        . '<form method="post" class="card">' . csrf_field() . '<input type="hidden" name="do" value="status"><h2>Доступ</h2><label class="fld">Статус<select name="status">' . $so . '</select></label><label class="fld">Заметка (видят только сотрудники)<textarea name="staff_note" rows="3">' . e($c['staff_note']) . '</textarea></label><button class="b b-dark" type="submit">Сохранить</button></form>'
        . '<form method="post" class="card">' . csrf_field() . '<input type="hidden" name="do" value="invite"><h2>Приглашение / сброс пароля</h2><p class="small">Клиент получит ссылку, по которой задаст новый пароль. Ссылка действует 7 дней.</p><button class="b b-line" type="submit">Отправить ссылку</button></form>'
        . '<form method="post" class="card">' . csrf_field() . '<input type="hidden" name="do" value="delete"><h2>Удалить клиента</h2><p class="small">Удаляются аккаунт, все его запросы, КП, переписка и файлы. Это нельзя отменить. Для подтверждения введите e-mail клиента.</p><label class="fld">E-mail клиента<input name="confirm" autocomplete="off"></label><button class="b b-ghost b-danger" type="submit">Удалить навсегда</button></form></div></div>';
    admin_page($c['company'] ?: $c['email'], $body, 'pclients', ['sub' => '<p class="sub"><a href="' . aurl('a=pclients') . '">← Все клиенты</a> · ' . e(CST_RU[$c['status']]) . '</p>']);
}

/* ---------------- portal settings ---------------- */
function padm_settings(bool $POST): void {
    $s = settings(); $cfg = portal_cfg();
    if ($POST) {
        if (db_url_source() !== 'env') {
            $url = trim((string)($_POST['db_url'] ?? ''));
            if ($url === '' && empty($_POST['db_keep'])) jwrite('_auth/db.json', ['url' => ''], false);
            elseif ($url !== '') { if (!db_parse($url)) { flash('Адрес базы не похож на postgresql://…', 'err'); go('a=psettings'); } jwrite('_auth/db.json', ['url' => $url], false); }
        }
        $terms = []; foreach (array_keys(LANG_NAMES) as $l) $terms[$l] = mb_substr(trim((string)($_POST['terms'][$l] ?? '')), 0, 6000);
        $s['portal'] = ['registration' => ($_POST['registration'] ?? '') === 'approve' ? 'approve' : 'auto', 'vat' => max(0, min(50, (float)str_replace(',', '.', (string)($_POST['vat'] ?? '21')))),
                        'valid_days' => max(1, min(365, (int)($_POST['valid_days'] ?? 30))), 'notify' => filter_var(trim((string)($_POST['notify'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '', 'terms' => $terms];
        jwrite('settings.json', $s); flash('Настройки кабинета сохранены.'); go('a=psettings');
    }
    $src = db_url_source(); $ok = $src ? db_ok() : false;
    $masked = preg_replace('#(://[^:/@]+:)[^@]+@#', '$1••••••@', db_url());
    $state = !$src ? '<p class="err">База не подключена.</p>' : ($ok ? '<p class="okm">✓ Подключено · ' . e($masked) . ' · схема v' . e((string)val("SELECT v FROM rf_meta WHERE k = 'schema'")) . ' · клиентов: ' . (int)val('SELECT count(*) FROM rf_clients') . ', запросов: ' . (int)val('SELECT count(*) FROM rf_requests') . '</p>'
             : '<p class="err">Не удаётся подключиться: ' . e(mb_substr((string)($GLOBALS['DB_ERR'] ?? ''), 0, 300)) . '</p>');
    $tt = ''; foreach (LANG_NAMES_RU as $l => $n) $tt .= '<label class="fld">' . e($n) . '<textarea name="terms[' . $l . ']" rows="3">' . e((string)($cfg['terms'][$l] ?? '')) . '</textarea></label>';
    $out = ''; $mails = glob(DATA . '/_portal/outbox/*.json') ?: []; rsort($mails);
    foreach (array_slice($mails, 0, 15) as $f) { $m = json_decode((string)file_get_contents($f), true); $out .= '<tr><td>' . e(pdate($m['date'] ?? '', true)) . '</td><td>' . e($m['to'] ?? '') . '</td><td>' . e($m['subject'] ?? '') . '</td><td>' . (!empty($m['sent']) ? '✓' : '<b class="badge badge-w">не ушло</b>') . '</td></tr>'; }
    $body = '<form method="post" class="edit">' . csrf_field() . '<div class="edit-main">'
        . '<section class="card"><h2>База данных (PostgreSQL)</h2>' . $state
        . ($src === 'env' ? '<p class="small">Подключение задано на сервере (переменная DATABASE_URL) и здесь не меняется.</p>'
           : '<label class="fld">Адрес подключения<input name="db_url" placeholder="postgresql://пользователь:пароль@127.0.0.1:5432/reclamefabriek" autocomplete="off"></label><input type="hidden" name="db_keep" value="1"><p class="small">Пусто — оставить как есть. <b>Локальная база:</b> postgresql://rf:пароль@127.0.0.1:5432/reclamefabriek. <b>Supabase:</b> Project Settings → Database → Connection string → Session pooler, в конце добавьте <code>?sslmode=require</code>. Таблицы создаются автоматически при первом подключении.</p>') . '</section>'
        . '<section class="card"><h2>Регистрация клиентов</h2><label class="chk"><input type="radio" name="registration" value="auto"' . ($cfg['registration'] !== 'approve' ? ' checked' : '') . '> Сразу после подтверждения e-mail</label><label class="chk"><input type="radio" name="registration" value="approve"' . ($cfg['registration'] === 'approve' ? ' checked' : '') . '> Только после одобрения сотрудником (раздел «Клиенты»)</label>'
        . '<label class="fld">Куда присылать уведомления о кабинете<input type="email" name="notify" value="' . e($cfg['notify']) . '" placeholder="' . e($s['form_to'] ?? '') . '"></label><p class="small">Пусто — на адрес заявок из основных настроек.</p></section>'
        . '<section class="card"><h2>Коммерческие предложения</h2><div class="qgrid"><label class="fld">НДС по умолчанию, %<input name="vat" value="' . e((string)$cfg['vat']) . '" inputmode="decimal"></label><label class="fld">Срок действия КП, дней<input name="valid_days" value="' . e((string)$cfg['valid_days']) . '" inputmode="numeric"></label></div>'
        . '<h3>Условия по умолчанию (подставляются в новое КП на языке клиента)</h3>' . $tt . '</section>'
        . '<section class="card"><h2>Последние письма кабинета</h2><p class="small">Копия каждого письма сохраняется на сервере. Если почта на хостинге не настроена, здесь будет «не ушло» — тогда попросите разработчика подключить SMTP или отправляйте ссылки из карточки клиента.</p>' . ($out ? '<table class="tbl"><tbody>' . $out . '</tbody></table>' : '<p class="small">Писем пока не было.</p>') . '</section>'
        . '</div><aside class="card sidebox"><h2>Сохранение</h2><button class="b b-red b-wide" type="submit">Сохранить</button><p class="small"><a href="' . e(url('/portal/', 'en')) . '" target="_blank" rel="noopener">Открыть кабинет клиента ↗</a></p></aside></form>';
    admin_page('Настройки кабинета', $body, 'psettings');
}

/* ---------------- file download, website request → portal ---------------- */
function padm_file(): void { padm_need_db(); $f = one('SELECT * FROM rf_files WHERE id = ?', [(int)($_GET['id'] ?? 0)]); if (!$f) go('a=portal'); file_send($f); }
function padm_transfer(): void {
    padm_need_db();
    $id = basename((string)($_POST['id'] ?? '')); $file = DATA . '/_inbox/' . $id . '.json'; $m = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
    if (!$m || ($m['type'] ?? '') !== 'quote') go('a=inbox');
    if (!empty($m['portal'])) go('a=preq&no=' . rawurlencode($m['portal']));
    $email = strtolower((string)$m['email']); $l = in_array($m['lang'] ?? '', array_keys(LANG_NAMES), true) ? $m['lang'] : 'en';
    $c = one('SELECT * FROM rf_clients WHERE email = ?', [$email]);
    $newClient = !$c;
    if (!$c) { $cid = (int)val("INSERT INTO rf_clients (email, name, company, phone, lang, status) VALUES (?, ?, ?, ?, ?, 'pending') RETURNING id", [$email, (string)$m['name'], (string)$m['company'], (string)$m['phone'], $l]); $c = one('SELECT * FROM rf_clients WHERE id = ?', [$cid]); }
    $p = item('profiles', (string)($m['profile'] ?? ''));
    $no = tx(function () use ($c, $m, $p) {
        $no = next_no('RF');
        $title = ($p ? tr($p['title'], 'en') : 'Website request') . ($m['company'] ? ' · ' . $m['company'] : '');
        $rid = (int)val("INSERT INTO rf_requests (no, client_id, title, notes, source, created_at) VALUES (?, ?, ?, ?, 'website', ?) RETURNING id", [$no, $c['id'], $title, (string)($m['message'] ?? ''), $m['date']]);
        if ($p) q('INSERT INTO rf_request_items (request_id, position, profile_slug, profile_title, light, qty) VALUES (?, 0, ?, ?, ?, 1)', [$rid, $p['slug'], tr($p['title'], 'en'), $p['light'] ?? '']);
        q("INSERT INTO rf_messages (request_id, author_kind, author_name, body) VALUES (?, 'client', ?, ?)", [$rid, $m['name'] ?: $m['email'], (string)($m['message'] ?? '')]);
        event_add($rid, 'created', ['by' => 'website-form']);
        return $no;
    });
    $m['portal'] = $no; jwrite('_inbox/' . $id . '.json', $m, false);
    if ($newClient || !$c['pass_hash']) { $link = invite_link($c); $sent = mail_client($c, 'p.mail.invite_s', 'p.mail.invite_b', ['link' => $link]); $_SESSION['plink'] = $link;
        flash('Заявка перенесена в кабинет как ' . $no . '. ' . ($sent ? 'Клиенту отправлено приглашение.' : 'Письмо-приглашение не ушло — ссылка в карточке клиента.'), $sent ? 'ok' : 'err');
        go('a=pclient&id=' . $c['id']); }
    flash('Заявка перенесена в кабинет как ' . $no . '.'); go('a=preq&no=' . rawurlencode($no));
}
