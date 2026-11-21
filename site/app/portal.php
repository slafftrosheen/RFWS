<?php
/* Reclame Fabriek · client portal (/portal/, /lv/portal/ …). Requests that reference the website's product profiles,
   written quotes the client accepts online, and one message thread per request. Data: PostgreSQL (see db.php).
   Visitors who never open /portal/ still get no cookies. */
declare(strict_types=1);
require_once __DIR__ . '/db.php';

const REQ_FLOW = ['new', 'review', 'quoted', 'accepted', 'production', 'ready', 'installed'];

/* ---------------- session, csrf, helpers ---------------- */
function client_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('rfclient');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
    session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    if (!empty($_SESSION['cid']) && time() - ($_SESSION['seen'] ?? 0) > 12 * 3600) { $_SESSION = ['csrf' => bin2hex(random_bytes(16))]; session_regenerate_id(true); }
    $_SESSION['seen'] = time();
    header('X-Frame-Options: DENY'); header('Cache-Control: no-store'); header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
}
function client(): ?array {
    static $c = false;
    if ($c !== false) return $c;
    $c = null;
    if (!empty($_SESSION['cid'])) $c = one("SELECT * FROM rf_clients WHERE id = ? AND status = 'active'", [(int)$_SESSION['cid']]);
    return $c;
}
function pcsrf(): string { return '<input type="hidden" name="_csrf" value="' . e($_SESSION['csrf']) . '">'; }
function pcheck(): void { $t = $_POST['_csrf'] ?? ''; if (!is_string($t) || !hash_equals($_SESSION['csrf'], $t)) { http_response_code(400); exit('Session expired. Reload the page and try again.'); } }
function purl(string $p = ''): string { return url('/portal/' . ($p !== '' ? trim($p, '/') . '/' : '')); }
function pabs(string $p = '', ?string $l = null): string { return abs_url('/portal/' . ($p !== '' ? trim($p, '/') . '/' : ''), $l); }
function pgo(string $p = '', string $q = ''): never { header('Location: ' . purl($p) . ($q ? '?' . $q : ''), true, 303); exit; }
function pflash(string $m, string $tone = 'ok'): void { $_SESSION['pflash'][] = [$tone, $m]; }
function ul(string $k, string $l, array $vars = []): string { $s = tr(ui_all()[$k] ?? $k, $l); foreach ($vars as $a => $b) $s = str_replace('{' . $a . '}', (string)$b, $s); return $s; }
function pdate(?string $ts, bool $time = false): string { if (!$ts) return '—'; $t = strtotime($ts); return date($time ? 'd.m.Y H:i' : 'd.m.Y', $t); }
function ip(): string { return (string)($_SERVER['REMOTE_ADDR'] ?? ''); }
function pin(string $k, int $max = 200): string { return trim(mb_substr(str_replace("\0", '', (string)($_POST[$k] ?? '')), 0, $max)); }
function fld(string $label, string $input, string $cls = ''): string { return '<label class="fld ' . $cls . '"><span>' . e($label) . '</span>' . $input . '</label>'; }
function inp(string $name, string $val = '', string $attrs = ''): string { return '<input name="' . e($name) . '" value="' . e($val) . '" ' . $attrs . '>'; }
function chip_status(string $st): string { return '<span class="st st-' . e($st) . '">' . e(ui('p.st.' . $st)) . '</span>'; }
function chip_quote(string $st): string { return '<span class="qst qst-' . e($st) . '">' . e(ui('p.q.' . $st)) . '</span>'; }
function token_make(int $cid, string $kind, int $hours): string {
    $t = bin2hex(random_bytes(24));
    q('INSERT INTO rf_tokens (client_id, kind, token_hash, expires_at) VALUES (?, ?, ?, now() + make_interval(hours => ?))', [$cid, $kind, hash('sha256', $t), $hours]);
    return $t;
}
function token_use(string $t, array $kinds): ?array {
    if (!preg_match('/^[a-f0-9]{48}$/', $t)) return null;
    $r = one('SELECT * FROM rf_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > now()', [hash('sha256', $t)]);
    return ($r && in_array($r['kind'], $kinds, true)) ? $r : null;
}
function mail_client(array $c, string $subjKey, string $bodyKey, array $vars): bool {
    $l = in_array($c['lang'], array_keys(LANG_NAMES), true) ? $c['lang'] : 'en';
    $vars['name'] = $c['name'] ?: $c['email'];
    return send_mail($c['email'], ul($subjKey, $l, $vars), ul($bodyKey, $l, $vars) . "\n\n— \n" . ul('p.sig', $l));
}
function mail_staff(string $subject, string $body): void {
    $s = settings(); $to = portal_cfg()['notify'] ?: ($s['form_to'] ?? '');
    if ($to) send_mail($to, $subject, $body . "\n\n" . site_origin() . '/admin/?a=portal');
}
/** Tell the client about news on a request (if they want emails). */
function notify_client(int $rid, string $whatKey, array $vars = []): void {
    $r = one('SELECT r.no, c.* FROM rf_requests r JOIN rf_clients c ON c.id = r.client_id WHERE r.id = ?', [$rid]);
    if (!$r || !$r['notify'] || $r['status'] !== 'active') return;
    $l = $r['lang'];
    if (isset($vars['status'])) $vars['status'] = ul('p.st.' . $vars['status'], $l);
    mail_client($r, 'p.mail.update_s', 'p.mail.update_b', ['no' => $r['no'], 'what' => ul($whatKey, $l, $vars), 'link' => pabs('requests/' . $r['no'], $l)]);
}

/* ---------------- page shell ---------------- */
function portal_page(string $title, string $body, string $tab = ''): void {
    $c = client(); $nav = '';
    if ($c) {
        $tabs = [['', ui('p.dash'), 'dash'], ['requests/new', ui('p.new_request'), 'new'], ['account', ui('p.account'), 'account']];
        foreach ($tabs as [$p, $l, $k]) $nav .= '<a href="' . e(purl($p)) . '"' . ($tab === $k ? ' aria-current="page"' : '') . '>' . e($l) . '</a>';
        $nav = '<nav class="pt-nav" aria-label="' . e(ui('p.title')) . '"><div class="pt-who">' . icon('user', 'ic ic-s') . '<b>' . e($c['company'] ?: $c['name']) . '</b><small>' . e($c['email']) . '</small></div><div class="pt-tabs">' . $nav . '</div>'
            . '<form method="post" action="' . e(purl('logout')) . '">' . pcsrf() . '<button class="btn btn-line btn-s" type="submit">' . icon('logout', 'ic ic-s') . e(ui('p.logout')) . '</button></form></nav>';
    }
    $fl = ''; foreach ($_SESSION['pflash'] ?? [] as [$tone, $m]) $fl .= '<p class="pt-flash pt-flash-' . e($tone) . '" role="status">' . inline($m) . '</p>'; unset($_SESSION['pflash']);
    $GLOBALS['NAV_CUR'] = '';
    $pc = is_file(ROOT . '/assets/css/portal.min.css') ? 'portal.min.css' : 'portal.css'; $pj = is_file(ROOT . '/assets/js/portal.min.js') ? 'portal.min.js' : 'portal.js';
    $v = @filemtime(ROOT . '/assets/css/' . $pc) . '-' . @filemtime(ROOT . '/assets/js/' . $pj);
    $html = '<section class="pt-head"><div class="wrap"><p class="k k-lit">' . e(ui('p.title')) . '</p><h1 class="h1">' . e($title) . '</h1></div></section>'
          . '<section class="portal"><div class="wrap">' . $nav . $fl . $body . '</div></section>';
    echo layout($title . ' · ' . ui('p.title'), ui('p.lead'), $GLOBALS['PORTAL_PATH'] ?? '/portal/', $html, ['noindex' => true, 'cta' => false, 'body' => 'is-portal',
        'css' => '<link rel="stylesheet" href="/assets/css/' . $pc . '?v=' . $v . '">', 'js' => '<script src="/assets/js/' . $pj . '?v=' . $v . '" defer></script>']);
    exit;
}
function portal_unavailable(): never {
    http_response_code(503); header('Retry-After: 3600');
    portal_page(ui('p.title'), '<div class="pt-card pt-narrow"><p>' . e(ui('p.err_db')) . '</p><a class="btn btn-dark" href="' . e(url('/contact/')) . '#quote">' . e(ui('nav.contact')) . '</a></div>');
}

/* ---------------- router ---------------- */
function portal_route(array $seg): void {
    $GLOBALS['PORTAL_PATH'] = '/portal/' . ($seg ? implode('/', $seg) . '/' : '');
    client_session();
    if (!db_ok()) portal_unavailable();
    $POST = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    if ($POST) pcheck();
    $a = $seg[0] ?? '';
    $public = ['login', 'register', 'verify', 'forgot', 'reset'];
    if ($a === 'logout') { if ($POST) { $_SESSION = ['csrf' => bin2hex(random_bytes(16))]; session_regenerate_id(true); } pgo('login'); }
    if (in_array($a, $public, true)) { ('p_' . $a)($POST); return; }
    if (!client()) {
        if ($a === 'requests' && ($seg[1] ?? '') === 'new') $_SESSION['after_login'] = 'requests/new' . (isset($_GET['p']) ? '?p=' . rawurlencode((string)$_GET['p']) : '');
        elseif ($a !== '') $_SESSION['after_login'] = implode('/', $seg);
        pgo('login');
    }
    match (true) {
        $a === '' => p_dash(),
        $a === 'account' => p_account($POST),
        $a === 'requests' && ($seg[1] ?? '') === 'new' => p_new($POST),
        $a === 'requests' && isset($seg[1]) => p_request((string)$seg[1], $POST),
        $a === 'quotes' && isset($seg[1]) => p_quote((string)$seg[1], $POST),
        $a === 'files' && isset($seg[1]) => p_file((string)$seg[1]),
        default => portal_page('404', '<div class="pt-card"><p>' . e(ui('nf.text')) . '</p></div>'),
    };
}

/* ---------------- auth ---------------- */
function why_box(): string {
    return '<aside class="pt-why"><h2 class="h4">' . e(ui('p.why')) . '</h2><ul><li>' . icon('chat', 'ic ic-s') . e(ui('p.why_1')) . '</li><li>' . icon('doc', 'ic ic-s') . e(ui('p.why_2')) . '</li><li>' . icon('factory', 'ic ic-s') . e(ui('p.why_3')) . '</li></ul></aside>';
}
function p_login(bool $POST): void {
    if (client()) pgo();
    $err = ''; $email = '';
    if ($POST) {
        $email = strtolower(pin('email', 190));
        if (!throttle('login|' . ip() . '|' . $email, 8, 600) || !throttle('login|' . ip(), 30, 600)) $err = ui('p.err_wait');
        else {
            $c = one('SELECT * FROM rf_clients WHERE email = ?', [$email]);
            if ($c && $c['pass_hash'] && password_verify((string)($_POST['pw'] ?? ''), $c['pass_hash'])) {
                if ($c['status'] === 'blocked') $err = ui('p.err_blocked');
                elseif (!$c['email_verified_at']) $err = ui('p.sent_verify');
                elseif ($c['status'] !== 'active') $err = ui('p.pending');
                else {
                    session_regenerate_id(true); $_SESSION['cid'] = (int)$c['id']; $_SESSION['csrf'] = bin2hex(random_bytes(16));
                    q('UPDATE rf_clients SET last_login_at = now() WHERE id = ?', [$c['id']]);
                    if (password_needs_rehash($c['pass_hash'], PASSWORD_DEFAULT)) q('UPDATE rf_clients SET pass_hash = ? WHERE id = ?', [password_hash((string)$_POST['pw'], PASSWORD_DEFAULT), $c['id']]);
                    $next = (string)($_SESSION['after_login'] ?? ''); unset($_SESSION['after_login']);
                    if ($next !== '' && preg_match('#^[a-zA-Z0-9/_.,?=%-]+$#', $next)) { header('Location: ' . purl(explode('?', $next)[0]) . (str_contains($next, '?') ? '?' . explode('?', $next, 2)[1] : ''), true, 303); exit; }
                    pgo();
                }
            } else { usleep(300000); $err = ui('p.err_login'); }
        }
    }
    $note = !empty($_SESSION['after_login']) && str_starts_with((string)$_SESSION['after_login'], 'requests/new') ? '<p class="pt-note">' . e(ui('p.login_to_continue')) . '</p>' : '';
    portal_page(ui('p.login'), '<div class="pt-auth"><form class="pt-card form" method="post" action="' . e(purl('login')) . '">' . pcsrf() . $note . ($err ? '<p class="pt-err" role="alert">' . e($err) . '</p>' : '')
        . fld(ui('p.email'), inp('email', $email, 'type="email" required autocomplete="email" autofocus')) . fld(ui('p.password'), '<input type="password" name="pw" required autocomplete="current-password">')
        . '<button class="btn btn-red" type="submit">' . e(ui('p.login')) . ' ' . icon('arrow', 'ic ic-s') . '</button>'
        . '<p class="small"><a href="' . e(purl('forgot')) . '">' . e(ui('p.forgot')) . '</a></p><p class="small">' . e(ui('p.no_account')) . ' <a href="' . e(purl('register')) . '">' . e(ui('p.register')) . '</a></p></form>' . why_box() . '</div>');
}
function p_register(bool $POST): void {
    if (client()) pgo();
    $err = ''; $v = ['name' => '', 'company' => '', 'email' => '', 'phone' => '', 'country' => '', 'vat' => ''];
    if ($POST) {
        foreach ($v as $k => $_) $v[$k] = pin($k, $k === 'email' ? 190 : 160);
        $v['email'] = strtolower($v['email']); $pw = (string)($_POST['pw'] ?? '');
        if (!empty($_POST['website'])) pgo('login');                                   // honeypot
        if ($v['name'] === '' || $v['company'] === '' || empty($_POST['consent'])) $err = ui('p.err_required');
        elseif (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $err = ui('p.err_email');
        elseif (mb_strlen($pw) < 10 || $pw !== ($_POST['pw2'] ?? '')) $err = ui('p.err_pw');
        elseif (!throttle('register|' . ip(), 5, 3600)) $err = ui('p.err_wait');
        elseif (one('SELECT id FROM rf_clients WHERE email = ?', [$v['email']])) $err = ui('p.err_exists');
        else {
            $id = (int)val('INSERT INTO rf_clients (email, pass_hash, name, company, phone, country, vat, lang, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'pending\') RETURNING id',
                [$v['email'], password_hash($pw, PASSWORD_DEFAULT), $v['name'], $v['company'], $v['phone'], $v['country'], $v['vat'], lang()]);
            $c = one('SELECT * FROM rf_clients WHERE id = ?', [$id]);
            mail_client($c, 'p.mail.verify_s', 'p.mail.verify_b', ['link' => pabs('verify') . '?t=' . token_make($id, 'verify', 48)]);
            mail_staff('Кабинет: новый клиент ' . $v['company'], "Зарегистрировался клиент: {$v['name']}, {$v['company']} <{$v['email']}>.");
            pflash(ui('p.sent_verify')); pgo('login');
        }
    }
    $f = '<div class="row2">' . fld(ui('p.name') . ' *', inp('name', $v['name'], 'required autocomplete="name"')) . fld(ui('p.company') . ' *', inp('company', $v['company'], 'required autocomplete="organization"')) . '</div>'
       . '<div class="row2">' . fld(ui('p.email') . ' *', inp('email', $v['email'], 'type="email" required autocomplete="email"')) . fld(ui('p.phone'), inp('phone', $v['phone'], 'type="tel" autocomplete="tel"')) . '</div>'
       . '<div class="row2">' . fld(ui('p.country'), inp('country', $v['country'], 'autocomplete="country-name"')) . fld(ui('p.vat'), inp('vat', $v['vat'])) . '</div>'
       . '<div class="row2">' . fld(ui('p.password_new') . ' *', '<input type="password" name="pw" required minlength="10" autocomplete="new-password">') . fld(ui('p.password2') . ' *', '<input type="password" name="pw2" required minlength="10" autocomplete="new-password">') . '</div>'
       . '<label class="hp" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>'
       . '<label class="chk"><input type="checkbox" name="consent" value="1" required> <span>' . inline(ui('p.consent', ['privacy' => url('/privacy-policy/')])) . '</span></label>';
    portal_page(ui('p.register'), '<div class="pt-auth"><form class="pt-card form" method="post" action="' . e(purl('register')) . '">' . pcsrf() . ($err ? '<p class="pt-err" role="alert">' . e($err) . '</p>' : '') . $f
        . '<button class="btn btn-red" type="submit">' . e(ui('p.register')) . ' ' . icon('arrow', 'ic ic-s') . '</button><p class="small">' . e(ui('p.have_account')) . ' <a href="' . e(purl('login')) . '">' . e(ui('p.login')) . '</a></p></form>' . why_box() . '</div>');
}
function p_verify(bool $POST): void {
    $t = token_use((string)($_GET['t'] ?? ''), ['verify']);
    if (!$t) { pflash(ui('p.err_token'), 'err'); pgo('login'); }
    q('UPDATE rf_tokens SET used_at = now() WHERE id = ?', [$t['id']]);
    $auto = portal_cfg()['registration'] !== 'approve';
    q("UPDATE rf_clients SET email_verified_at = COALESCE(email_verified_at, now()), status = CASE WHEN status = 'pending' AND ? THEN 'active' ELSE status END WHERE id = ?", [$auto ? 'true' : 'false', $t['client_id']]);
    $c = one('SELECT * FROM rf_clients WHERE id = ?', [$t['client_id']]);
    if ($c['status'] === 'pending') mail_staff('Кабинет: клиент ждёт одобрения — ' . $c['company'], "Клиент {$c['name']}, {$c['company']} <{$c['email']}> подтвердил e-mail и ждёт одобрения в разделе «Клиенты».");
    pflash($c['status'] === 'active' ? ui('p.verified') : ui('p.verified_pending')); pgo('login');
}
function p_forgot(bool $POST): void {
    if ($POST) {
        $email = strtolower(pin('email', 190));
        if (throttle('forgot|' . ip(), 6, 3600) && ($c = one("SELECT * FROM rf_clients WHERE email = ? AND status <> 'blocked'", [$email])))
            mail_client($c, 'p.mail.reset_s', 'p.mail.reset_b', ['link' => pabs('reset') . '?t=' . token_make((int)$c['id'], 'reset', 2)]);
        pflash(ui('p.sent_reset')); pgo('login');
    }
    portal_page(ui('p.forgot'), '<form class="pt-card form pt-narrow" method="post" action="' . e(purl('forgot')) . '">' . pcsrf() . fld(ui('p.email'), '<input type="email" name="email" required autocomplete="email" autofocus>')
        . '<button class="btn btn-dark" type="submit">' . e(ui('p.reset_send')) . '</button><p class="small"><a href="' . e(purl('login')) . '">← ' . e(ui('p.login')) . '</a></p></form>');
}
function p_reset(bool $POST): void {
    $raw = (string)($_GET['t'] ?? ''); $t = token_use($raw, ['reset', 'invite']); $err = '';
    if (!$t) { pflash(ui('p.err_token'), 'err'); pgo('login'); }
    if ($POST) {
        $pw = (string)($_POST['pw'] ?? '');
        if (mb_strlen($pw) < 10 || $pw !== ($_POST['pw2'] ?? '')) $err = ui('p.err_pw');
        else {
            q('UPDATE rf_tokens SET used_at = now() WHERE client_id = ? AND kind IN (\'reset\', \'invite\') AND used_at IS NULL', [$t['client_id']]);
            q("UPDATE rf_clients SET pass_hash = ?, email_verified_at = COALESCE(email_verified_at, now()), status = CASE WHEN status = 'pending' AND ? THEN 'active' ELSE status END WHERE id = ?",
              [password_hash($pw, PASSWORD_DEFAULT), $t['kind'] === 'invite' ? 'true' : 'false', $t['client_id']]);
            $c = one('SELECT * FROM rf_clients WHERE id = ?', [$t['client_id']]);
            if ($c['status'] === 'active') { session_regenerate_id(true); $_SESSION['cid'] = (int)$c['id']; $_SESSION['csrf'] = bin2hex(random_bytes(16)); q('UPDATE rf_clients SET last_login_at = now() WHERE id = ?', [$c['id']]); pflash(ui('p.reset_done')); pgo(); }
            pflash($c['status'] === 'blocked' ? ui('p.err_blocked') : ui('p.pending'), 'err'); pgo('login');
        }
    }
    portal_page(ui('p.reset_title'), '<form class="pt-card form pt-narrow" method="post" action="' . e(purl('reset')) . '?t=' . e($raw) . '">' . pcsrf() . ($err ? '<p class="pt-err" role="alert">' . e($err) . '</p>' : '')
        . fld(ui('p.password_new'), '<input type="password" name="pw" required minlength="10" autocomplete="new-password" autofocus>') . fld(ui('p.password2'), '<input type="password" name="pw2" required minlength="10" autocomplete="new-password">')
        . '<button class="btn btn-red" type="submit">' . e(ui('p.save')) . '</button></form>');
}

/* ---------------- dashboard ---------------- */
function p_dash(): void {
    $c = client();
    q("UPDATE rf_quotes q SET status = 'expired' FROM rf_requests r WHERE q.request_id = r.id AND r.client_id = ? AND q.status = 'sent' AND q.valid_until < current_date", [$c['id']]);
    $rows = all("SELECT r.*, (SELECT count(*) FROM rf_messages m WHERE m.request_id = r.id AND m.author_kind <> 'client' AND m.created_at > r.client_seen_at) AS unread,
                        (SELECT count(*) FROM rf_quotes qq WHERE qq.request_id = r.id AND qq.status = 'sent') AS qwait,
                        (SELECT count(*) FROM rf_request_items i WHERE i.request_id = r.id) AS n_items
                 FROM rf_requests r WHERE r.client_id = ? ORDER BY r.updated_at DESC", [$c['id']]);
    $open = count(array_filter($rows, fn($r) => !in_array($r['status'], ['closed', 'cancelled', 'installed'], true)));
    $qw = array_sum(array_column($rows, 'qwait')); $un = array_sum(array_column($rows, 'unread'));
    $stats = '<ul class="pt-stats"><li><b>' . $open . '</b><span>' . e(ui('p.open_requests')) . '</span></li><li' . ($qw ? ' class="is-lit"' : '') . '><b>' . $qw . '</b><span>' . e(ui('p.quotes_waiting')) . '</span></li><li' . ($un ? ' class="is-lit"' : '') . '><b>' . $un . '</b><span>' . e(ui('p.unread')) . '</span></li></ul>';
    $list = '';
    foreach ($rows as $r) {
        $list .= '<li class="pt-req"><a href="' . e(purl('requests/' . $r['no'])) . '"><span class="pt-req-no">' . e($r['no']) . '</span><span class="pt-req-t"><b>' . e($r['title']) . '</b><small>' . e(ui('p.updated')) . ': ' . e(pdate($r['updated_at'], true)) . '</small></span>'
              . '<span class="pt-req-s">' . chip_status($r['status']) . ($r['qwait'] ? '<span class="pt-badge">' . icon('doc', 'ic ic-xs') . e(ui('p.q.sent')) . '</span>' : '') . ($r['unread'] ? '<span class="pt-badge">' . icon('chat', 'ic ic-xs') . (int)$r['unread'] . '</span>' : '') . '</span>' . icon('arrow', 'ic ic-s') . '</a></li>';
    }
    $body = $stats . '<div class="pt-bar"><h2 class="h3">' . e(ui('p.requests')) . '</h2><a class="btn btn-red" href="' . e(purl('requests/new')) . '">' . icon('plus', 'ic ic-s') . e(ui('p.new_request')) . '</a></div>'
          . ($list ? '<ul class="pt-reqs">' . $list . '</ul>' : '<div class="pt-card pt-empty">' . lottie('rf_svc_letters', '', 'lt-empty', '') . '<p>' . e(ui('p.no_requests')) . '</p><a class="btn btn-line" href="' . e(url('/product-profiles/')) . '">' . e(ui('p.browse')) . '</a></div>');
    portal_page(ui('p.hello', ['name' => $c['name'] ?: $c['company']]), $body, 'dash');
}

/* ---------------- new request (profiles from the website) ---------------- */
function profile_map(): array {
    $m = [];
    foreach (items('profiles') as $s => $p) {
        $d = array_values(array_filter(array_map('trim', explode(',', preg_replace('/\s*mm\s*$/', '', (string)($p['depth'] ?? ''))))));
        $m[$s] = ['t' => tr($p['title']), 's' => tr($p['subtitle'] ?? []), 'img' => media_src($p['cover'] ?? null, 400), 'd' => $d, 'c' => (bool)($p['custom_depth'] ?? true), 'g' => $p['group'], 'u' => url('/' . $s . '/')];
    }
    return $m;
}
function item_row(int $i, array $it, array $map): string {
    $opts = '<option value="">' . e(ui('p.profile_any')) . '</option>';
    $grp = ['letters' => ui('box_letters'), 'lightbox' => ui('lightboxes')];
    foreach ($grp as $g => $gl) { $opts .= '<optgroup label="' . e($gl) . '">'; foreach ($map as $s => $p) if ($p['g'] === $g) $opts .= '<option value="' . e($s) . '"' . (($it['profile_slug'] ?? '') === $s ? ' selected' : '') . '>' . e($p['t'] . ' · ' . $p['s']) . '</option>'; $opts .= '</optgroup>'; }
    $sel = $map[$it['profile_slug'] ?? ''] ?? null; $n = "items[$i]";
    $mt = ['' => '—', 'no_frame' => ui('p.no_frame'), 'on_frame' => ui('p.on_frame')]; $mo = '';
    foreach ($mt as $k => $l) $mo .= '<option value="' . $k . '"' . (($it['mounting'] ?? '') === $k ? ' selected' : '') . '>' . e($l) . '</option>';
    return '<fieldset class="pt-item" data-item><legend><span data-item-n>' . ($i + 1) . '</span> · ' . e(ui('p.item')) . '</legend>'
        . '<div class="pt-item-top"><span class="pt-thumb" data-thumb>' . ($sel ? '<img src="' . e($sel['img']) . '" alt="" width="96" height="72">' : icon('box-letter', 'ic')) . '</span>'
        . fld(ui('p.profile'), '<select name="' . $n . '[profile_slug]" data-profile-pick>' . $opts . '</select>', 'pt-grow')
        . '<a class="lnk pt-plink" data-plink href="' . e($sel['u'] ?? url('/product-profiles/')) . '" target="_blank" rel="noopener"' . ($sel ? '' : ' hidden') . '>' . e(ui('p.view_profile')) . ' ' . icon('ext', 'ic ic-s') . '</a></div>'
        . '<div class="row3">' . fld(ui('p.content'), inp($n . '[content]', (string)($it['content'] ?? ''), 'maxlength="300" placeholder="' . e(ui('p.content_ph')) . '"'), 'pt-span2')
        . fld(ui('p.qty'), inp($n . '[qty]', (string)($it['qty'] ?? '1'), 'type="number" min="1" max="999" inputmode="numeric"')) . '</div>'
        . '<div class="row3">' . fld(ui('p.height'), inp($n . '[height_mm]', (string)($it['height_mm'] ?? ''), 'type="number" min="10" max="20000" inputmode="numeric"'))
        . fld(ui('p.depth'), inp($n . '[depth]', (string)($it['depth'] ?? ''), 'maxlength="40" list="dl-' . e($it['profile_slug'] ?? 'none') . '" data-depth'))
        . fld(ui('p.mounting'), '<select name="' . $n . '[mounting]">' . $mo . '</select>') . '</div>'
        . '<div class="row2">' . fld(ui('p.face'), inp($n . '[face_colour]', (string)($it['face_colour'] ?? ''), 'maxlength="80" placeholder="RAL 9003"')) . fld(ui('p.sides'), inp($n . '[side_colour]', (string)($it['side_colour'] ?? ''), 'maxlength="80" placeholder="RAL 9005"')) . '</div>'
        . fld(ui('p.notes'), '<textarea name="' . $n . '[notes]" rows="2" maxlength="2000">' . e((string)($it['notes'] ?? '')) . '</textarea>')
        . '<button type="button" class="btn btn-line btn-s pt-rm" data-item-rm>' . icon('trash', 'ic ic-s') . e(ui('p.remove')) . '</button></fieldset>';
}
function p_new(bool $POST): void {
    $c = client(); $map = profile_map(); $err = ''; $items = []; $v = ['title' => '', 'site_address' => '', 'deadline' => '', 'notes' => ''];
    if ($POST) {
        foreach ($v as $k => $_) $v[$k] = pin($k, $k === 'notes' ? 5000 : 200);
        foreach ((array)($_POST['items'] ?? []) as $raw) {
            if (!is_array($raw)) continue;
            $g = fn($k, $m = 300) => trim(mb_substr((string)($raw[$k] ?? ''), 0, $m));
            $slug = isset($map[$g('profile_slug')]) ? $g('profile_slug') : '';
            $it = ['profile_slug' => $slug, 'content' => $g('content'), 'height_mm' => $g('height_mm', 6) !== '' ? max(10, min(20000, (int)$g('height_mm', 6))) : null,
                   'depth' => $g('depth', 40), 'qty' => max(1, min(999, (int)($g('qty', 4) ?: 1))), 'face_colour' => $g('face_colour', 80), 'side_colour' => $g('side_colour', 80),
                   'mounting' => in_array($g('mounting'), ['no_frame', 'on_frame'], true) ? $g('mounting') : '', 'notes' => $g('notes', 2000)];
            if ($slug || $it['content'] !== '' || $it['notes'] !== '') $items[] = $it;
        }
        if (!$items) $err = ui('p.err_required');
        elseif (!throttle('newreq|' . $c['id'], 12, 3600)) $err = ui('p.err_wait');
        else {
            $title = $v['title'] ?: (($items[0]['profile_slug'] ? $map[$items[0]['profile_slug']]['t'] : '') . ($items[0]['content'] ? ' · ' . $items[0]['content'] : '')) ?: ui('p.new_request');
            $dl = preg_match('/^\d{4}-\d{2}-\d{2}$/', $v['deadline']) ? $v['deadline'] : null;
            [$rid, $no] = tx(function () use ($c, $title, $v, $dl, $items, $map) {
                $no = next_no('RF');
                $rid = (int)val('INSERT INTO rf_requests (no, client_id, title, site_address, deadline, notes) VALUES (?, ?, ?, ?, ?, ?) RETURNING id', [$no, $c['id'], mb_substr($title, 0, 200), $v['site_address'], $dl, $v['notes']]);
                foreach ($items as $i => $it) {
                    $p = $map[$it['profile_slug']] ?? null; $pr = $it['profile_slug'] ? item('profiles', $it['profile_slug']) : null;
                    q('INSERT INTO rf_request_items (request_id, position, profile_slug, profile_title, light, content, height_mm, depth, qty, face_colour, side_colour, mounting, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                      [$rid, $i, $it['profile_slug'], $p ? tr($pr['title'], 'en') . ' · ' . tr($pr['subtitle'] ?? [], 'en') : '', $pr['light'] ?? '', $it['content'], $it['height_mm'], $it['depth'], $it['qty'], $it['face_colour'], $it['side_colour'], $it['mounting'], $it['notes']]);
                }
                event_add($rid, 'created', ['by' => 'client']);
                return [$rid, $no];
            });
            [$okf, $ferr] = files_store('files', $rid, null, 'client');
            if ($ferr) pflash(ui('p.err_files', ['x' => implode(', ', $ferr)]), 'err');
            mail_client($c, 'p.mail.received_s', 'p.mail.received_b', ['no' => $no, 'link' => pabs('requests/' . $no, $c['lang'])]);
            $lines = []; foreach ($items as $it) $lines[] = '• ' . ($it['profile_slug'] ? $map[$it['profile_slug']]['t'] : 'без профиля') . ($it['content'] ? ' — ' . $it['content'] : '') . ($it['height_mm'] ? ', ' . $it['height_mm'] . ' мм' : '') . ', ' . $it['qty'] . ' шт.';
            mail_staff("Кабинет: новый запрос $no от " . ($c['company'] ?: $c['name']), "Клиент: {$c['name']}, {$c['company']} <{$c['email']}>\nПроект: $title\n\n" . implode("\n", $lines) . ($okf ? "\n\nФайлов: $okf" : ''));
            pflash(ui('p.request_sent', ['no' => $no])); pgo('requests/' . $no, 'sent=1');
        }
    } else {
        foreach (array_slice(array_filter(explode(',', (string)($_GET['p'] ?? '')), fn($s) => isset($map[$s])), 0, 20) as $s) $items[] = ['profile_slug' => $s, 'qty' => 1];
        if (!$items) $items[] = ['profile_slug' => '', 'qty' => 1];
    }
    $rows = ''; foreach (array_values($items) as $i => $it) $rows .= item_row($i, $it, $map);
    $dls = ''; foreach ($map as $s => $p) { $dls .= '<datalist id="dl-' . e($s) . '">'; foreach ($p['d'] as $d) $dls .= '<option value="' . e($d . ' mm') . '">'; $dls .= '</datalist>'; }
    $pre = !$POST && isset($_GET['p']) && count($items) > 0 && $items[0]['profile_slug'] ? '<p class="pt-note">' . icon('check', 'ic ic-s') . e(ui('p.basket_note', ['n' => count($items)])) . '</p>' : '';
    $tpl = '<template data-item-tpl>' . item_row(999, ['qty' => 1], $map) . '</template>';
    $body = '<form class="pt-form form" method="post" action="' . e(purl('requests/new')) . '" enctype="multipart/form-data" data-reqform data-map="' . e(json_encode($map, JSON_UNESCAPED_UNICODE)) . '">' . pcsrf() . ($err ? '<p class="pt-err" role="alert">' . e($err) . '</p>' : '') . $pre
          . '<div class="pt-card"><div class="row2">' . fld(ui('p.req_title'), inp('title', $v['title'], 'maxlength="200" placeholder="' . e(ui('p.req_title_ph')) . '"')) . fld(ui('p.site'), inp('site_address', $v['site_address'], 'maxlength="200"')) . '</div>'
          . '<div class="row2">' . fld(ui('p.deadline'), inp('deadline', $v['deadline'], 'type="date"')) . '<span></span></div></div>'
          . '<h2 class="h3 pt-h">' . e(ui('p.items')) . '</h2><div class="pt-items" data-items>' . $rows . '</div><button type="button" class="btn btn-line" data-item-add>' . icon('plus', 'ic ic-s') . e(ui('p.add_item')) . '</button>'
          . '<div class="pt-card">' . fld(ui('p.notes'), '<textarea name="notes" rows="4" maxlength="5000">' . e($v['notes']) . '</textarea>')
          . '<label class="fld pt-drop" data-drop><span>' . icon('upload', 'ic ic-s') . e(ui('p.files')) . '</span><input type="file" name="files[]" multiple accept=".' . implode(',.', PORTAL_EXT) . '" data-files><small>' . e(ui('p.files_hint')) . '</small><ul class="pt-picked" data-picked></ul></label></div>'
          . '<div class="pt-submit"><button class="btn btn-red" type="submit">' . e(ui('p.send_request')) . ' ' . icon('arrow', 'ic ic-s') . '</button></div>' . $tpl . $dls . '</form>';
    portal_page(ui('p.new_request'), $body, 'new');
}

/* ---------------- one request ---------------- */
function req_for_client(string $no): array {
    $r = one('SELECT * FROM rf_requests WHERE no = ? AND client_id = ?', [$no, client()['id']]);
    if (!$r) { http_response_code(404); portal_page('404', '<div class="pt-card"><p>' . e(ui('nf.text')) . '</p></div>'); }
    return $r;
}
function timeline(string $st): string {
    if (in_array($st, ['cancelled', 'closed'], true)) return '<div class="pt-line pt-line-off"><span class="pt-node is-now">' . e(ui('p.st.' . $st)) . '</span></div>';
    $cur = array_search($st, REQ_FLOW, true); $o = '';
    foreach (REQ_FLOW as $i => $s) $o .= '<li class="' . ($i < $cur ? 'is-done' : ($i === $cur ? 'is-now' : '')) . '"' . ($i === $cur ? ' aria-current="step"' : '') . '><i aria-hidden="true"></i><span>' . e(ui('p.st.' . $s)) . '</span></li>';
    return '<ol class="pt-line" aria-label="' . e(ui('p.timeline')) . '" style="--n:' . count(REQ_FLOW) . ';--at:' . (int)$cur . '">' . $o . '</ol>';
}
function items_table(int $rid, bool $links = true): string {
    $o = '';
    foreach (all('SELECT * FROM rf_request_items WHERE request_id = ? ORDER BY position', [$rid]) as $i => $it) {
        $p = $it['profile_slug'] ? item('profiles', $it['profile_slug']) : null;
        $spec = array_filter([$it['height_mm'] ? ui('p.height') . ': ' . $it['height_mm'] : '', $it['depth'] ? ui('p.depth') . ': ' . $it['depth'] : '', $it['face_colour'] ? ui('p.face') . ': ' . $it['face_colour'] : '',
                              $it['side_colour'] ? ui('p.sides') . ': ' . $it['side_colour'] : '', $it['mounting'] ? ui('p.' . $it['mounting']) : '']);
        $o .= '<li class="pt-it"><span class="pt-thumb">' . ($p ? img($p['cover'] ?? null, '', '', false, '96px') : icon('box-letter', 'ic')) . '</span><div><b>' . ($i + 1) . '. ' . e($p ? tr($p['title']) . ' · ' . tr($p['subtitle'] ?? []) : ($it['profile_title'] ?: ui('p.profile_any'))) . '</b>'
            . ($it['content'] !== '' ? '<p class="pt-it-c">“' . e($it['content']) . '”</p>' : '') . ($spec ? '<p class="small">' . e(implode(' · ', $spec)) . '</p>' : '') . ($it['notes'] !== '' ? '<p class="small">' . nl2br(e($it['notes'])) . '</p>' : '')
            . ($p && $links ? '<a class="lnk" href="' . e(url('/' . $p['slug'] . '/')) . '" target="_blank" rel="noopener">' . e(ui('p.view_profile')) . ' ' . icon('ext', 'ic ic-s') . '</a>' : '') . '</div><span class="pt-qty">× ' . (int)$it['qty'] . '</span></li>';
    }
    return '<ul class="pt-its">' . $o . '</ul>';
}
function thread_html(int $rid, string $viewer, string $fileUrlBase): string {
    $files = []; foreach (all('SELECT * FROM rf_files WHERE request_id = ? AND message_id IS NOT NULL ORDER BY id', [$rid]) as $f) $files[$f['message_id']][] = $f;
    $o = '';
    foreach (all('SELECT * FROM rf_messages WHERE request_id = ? ORDER BY created_at, id', [$rid]) as $m) {
        $mine = $m['author_kind'] === $viewer; $sys = $m['author_kind'] === 'system';
        $who = $sys ? ($viewer === 'staff' ? 'Система' : ui('p.system')) : ($mine ? ($viewer === 'staff' ? e($m['author_name']) . ' (вы)' : e(ui('p.you'))) : ($m['author_kind'] === 'staff' ? ($viewer === 'staff' ? e($m['author_name']) : e(ui('p.team')) . ' · ' . e($m['author_name'])) : e($m['author_name'])));
        $fl = ''; foreach ($files[$m['id']] ?? [] as $f) $fl .= '<a class="pt-file" href="' . e($fileUrlBase . $f['id']) . '">' . icon('clip', 'ic ic-s') . e($f['original_name']) . ' <small>' . e(fsize((int)$f['size'])) . '</small></a>';
        $o .= '<li class="pt-msg ' . ($sys ? 'pt-msg-sys' : ($mine ? 'pt-msg-me' : 'pt-msg-them')) . '"><div class="pt-msg-h"><b>' . $who . '</b><time datetime="' . e(date('c', strtotime($m['created_at']))) . '">' . e(pdate($m['created_at'], true)) . '</time></div>'
            . ($m['body'] !== '' ? '<div class="pt-msg-b">' . nl2br(e($m['body'])) . '</div>' : '') . ($fl ? '<div class="pt-msg-f">' . $fl . '</div>' : '') . '</li>';
    }
    return $o;
}
function p_request(string $no, bool $POST): void {
    $c = client(); $r = req_for_client($no); $rid = (int)$r['id'];
    if ($POST) {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'message') {
            $body = trim(mb_substr((string)($_POST['body'] ?? ''), 0, 8000));
            $hasFiles = !empty($_FILES['files']['name'][0]);
            if (($body !== '' || $hasFiles) && throttle('msg|' . $c['id'], 40, 600)) {
                $mid = (int)val('INSERT INTO rf_messages (request_id, author_kind, author_name, body) VALUES (?, \'client\', ?, ?) RETURNING id', [$rid, $c['name'] ?: $c['email'], $body]);
                [, $ferr] = files_store('files', $rid, $mid, 'client'); if ($ferr) pflash(ui('p.err_files', ['x' => implode(', ', $ferr)]), 'err');
                q('UPDATE rf_requests SET updated_at = now(), client_seen_at = now() WHERE id = ?', [$rid]);
                mail_staff("Кабинет: сообщение по $no от " . ($c['company'] ?: $c['name']), mb_substr($body, 0, 1500));
                pflash(ui('p.msg_sent'));
            }
            header('Location: ' . purl('requests/' . $no) . '#thread', true, 303); exit;
        }
        if ($do === 'cancel' && in_array($r['status'], ['new', 'review', 'quoted'], true)) {
            q("UPDATE rf_requests SET status = 'cancelled', updated_at = now() WHERE id = ?", [$rid]);
            q("UPDATE rf_quotes SET status = 'superseded' WHERE request_id = ? AND status = 'sent'", [$rid]);
            q("INSERT INTO rf_messages (request_id, author_kind, author_name, body) VALUES (?, 'system', '', ?)", [$rid, 'Request cancelled by the client · ' . ($c['name'] ?: $c['email'])]);
            event_add($rid, 'status', ['to' => 'cancelled', 'by' => 'client']);
            mail_staff("Кабинет: клиент отменил запрос $no", 'Клиент ' . ($c['company'] ?: $c['name']) . ' отменил запрос.');
            pflash(ui('p.cancelled_ok')); pgo('requests/' . $no);
        }
        pgo('requests/' . $no);
    }
    q("UPDATE rf_quotes SET status = 'expired' WHERE request_id = ? AND status = 'sent' AND valid_until < current_date", [$rid]);
    q('UPDATE rf_requests SET client_seen_at = now() WHERE id = ?', [$rid]);
    $quotes = all("SELECT * FROM rf_quotes WHERE request_id = ? AND status <> 'draft' ORDER BY version DESC", [$rid]);
    $ql = '';
    foreach ($quotes as $qq) {
        $t = quote_totals(all('SELECT qty, unit_price FROM rf_quote_lines WHERE quote_id = ?', [$qq['id']]), (float)$qq['vat_rate']);
        $ql .= '<li class="pt-q pt-q-' . e($qq['status']) . '"><a href="' . e(purl('quotes/' . $qq['no'])) . '">' . icon('doc', 'ic') . '<span><b>' . e(ui('p.quote', ['no' => $qq['no']])) . '</b><small>' . e(pdate($qq['sent_at'])) . ($qq['valid_until'] ? ' · ' . e(ui('p.valid_until')) . ' ' . e(pdate($qq['valid_until'])) : '') . '</small></span>'
            . '<span class="pt-q-sum">' . e(money($t['total'], $qq['currency'], lang())) . '</span>' . chip_quote($qq['status']) . '</a></li>';
    }
    $files = all('SELECT * FROM rf_files WHERE request_id = ? ORDER BY id', [$rid]); $fl = '';
    foreach ($files as $f) $fl .= '<li><a class="pt-file" href="' . e(purl('files/' . $f['id'])) . '">' . icon('clip', 'ic ic-s') . e($f['original_name']) . ' <small>' . e(fsize((int)$f['size'])) . ' · ' . e(pdate($f['created_at'])) . '</small></a></li>';
    $thread = thread_html($rid, 'client', purl('files/'));
    $meta = '<dl class="pt-meta"><dt>' . e(ui('p.created')) . '</dt><dd>' . e(pdate($r['created_at'])) . '</dd>' . ($r['site_address'] !== '' ? '<dt>' . e(ui('p.site')) . '</dt><dd>' . e($r['site_address']) . '</dd>' : '')
          . ($r['deadline'] ? '<dt>' . e(ui('p.deadline')) . '</dt><dd>' . e(pdate($r['deadline'])) . '</dd>' : '') . '</dl>' . ($r['notes'] !== '' ? '<p class="pt-notes">' . nl2br(e($r['notes'])) . '</p>' : '');
    $body = (isset($_GET['sent']) ? '<i data-basket-clear hidden></i>' : '')
        . '<div class="pt-req-head"><div><p class="k">' . e($r['no']) . '</p><h2 class="h2">' . e($r['title']) . '</h2></div>' . chip_status($r['status']) . '</div>'
        . '<div class="pt-strip" data-spot>' . timeline($r['status']) . '</div>'
        . '<div class="pt-cols"><div class="pt-main">' . ($ql ? '<section class="pt-card"><h2 class="h4">' . e(ui('p.quotes')) . '</h2><ul class="pt-qs">' . $ql . '</ul></section>' : '')
        . '<section class="pt-card" id="thread"><h2 class="h4">' . e(ui('p.messages')) . '</h2>' . ($thread ? '<ol class="pt-thread" data-thread>' . $thread . '</ol>' : '<p class="small">' . e(ui('p.no_messages')) . '</p>')
        . (!in_array($r['status'], ['cancelled', 'closed'], true) ? '<form class="pt-reply" method="post" enctype="multipart/form-data" action="' . e(purl('requests/' . $r['no'])) . '">' . pcsrf() . '<input type="hidden" name="do" value="message">'
            . '<label class="fld"><span class="sr">' . e(ui('p.write')) . '</span><textarea name="body" rows="3" maxlength="8000" placeholder="' . e(ui('p.write')) . '"></textarea></label>'
            . '<div class="pt-reply-act"><label class="btn btn-line btn-s pt-attach">' . icon('clip', 'ic ic-s') . e(ui('p.attach')) . '<input type="file" name="files[]" multiple accept=".' . implode(',.', PORTAL_EXT) . '" data-files hidden></label><ul class="pt-picked" data-picked></ul>'
            . '<button class="btn btn-dark" type="submit">' . icon('send', 'ic ic-s') . e(ui('p.send')) . '</button></div></form>' : '') . '</section></div>'
        . '<aside class="pt-side"><section class="pt-card"><h2 class="h4">' . e(ui('p.items')) . '</h2>' . items_table($rid) . $meta . '</section>'
        . '<section class="pt-card"><h2 class="h4">' . e(ui('p.all_files')) . '</h2>' . ($fl ? '<ul class="pt-files">' . $fl . '</ul>' : '<p class="small">' . e(ui('p.no_files')) . '</p>') . '</section>'
        . (in_array($r['status'], ['new', 'review', 'quoted'], true) ? '<form method="post" class="pt-cancel" data-confirm="' . e(ui('p.cancel_confirm')) . '">' . pcsrf() . '<input type="hidden" name="do" value="cancel"><button class="btn btn-line btn-s" type="submit">' . e(ui('p.cancel_request')) . '</button></form>' : '')
        . '</aside></div>';
    portal_page(ui('p.request', ['no' => $r['no']]), $body, 'dash');
}

/* ---------------- written quote ---------------- */
function quote_doc(array $qq, array $r, array $c, string $l, string $viewer = 'client'): string {
    $lines = all('SELECT * FROM rf_quote_lines WHERE quote_id = ? ORDER BY position, id', [$qq['id']]);
    $t = quote_totals($lines, (float)$qq['vat_rate']); $rows = '';
    foreach ($lines as $i => $x) {
        $p = $x['profile_slug'] ? item('profiles', $x['profile_slug']) : null;
        $rows .= '<tr><td>' . ($i + 1) . '</td><td>' . nl2br(e($x['description'])) . ($p ? '<br><small>' . e(tr($p['title'], $l)) . ' · <a href="' . e(abs_url('/' . $p['slug'] . '/', $l)) . '">' . e(rtrim(preg_replace('#^https?://#', '', abs_url('/' . $p['slug'] . '/', $l)), '/')) . '</a></small>' : '') . '</td>'
            . '<td class="num">' . e(rtrim(rtrim(number_format((float)$x['qty'], 2, '.', ''), '0'), '.')) . ' ' . e($x['unit']) . '</td><td class="num">' . e(money($x['unit_price'], $qq['currency'], $l)) . '</td><td class="num">' . e(money((float)$x['qty'] * (float)$x['unit_price'], $qq['currency'], $l)) . '</td></tr>';
    }
    $s = settings(); $vatr = rtrim(rtrim(number_format((float)$qq['vat_rate'], 2, '.', ''), '0'), '.');
    return '<article class="qdoc"><header class="qdoc-h"><div class="qdoc-brand"><img src="/assets/brand/rf-logo-144.webp" width="56" height="56" alt=""><div><b>' . e($s['company'] ?? 'Reclame Fabriek') . '</b><small>' . nl2br(e(tr($s['address'] ?? [], $l)), false) . '</small></div></div>'
        . '<dl class="qdoc-meta"><dt>' . e(ul('p.quote', $l, ['no' => ''])) . '</dt><dd><b>' . e($qq['no']) . '</b></dd><dt>' . e(ul('p.date', $l)) . '</dt><dd>' . e(pdate($qq['sent_at'] ?: $qq['created_at'])) . '</dd>'
        . ($qq['valid_until'] ? '<dt>' . e(ul('p.valid_until', $l)) . '</dt><dd>' . e(pdate($qq['valid_until'])) . '</dd>' : '') . '<dt>' . e(ul('p.request', $l, ['no' => ''])) . '</dt><dd>' . e($r['no']) . '</dd></dl></header>'
        . '<div class="qdoc-to"><span class="k">' . e(ul('p.to', $l)) . '</span><b>' . e($c['company'] ?: $c['name']) . '</b><span>' . e($c['name']) . ($c['vat'] ? ' · ' . e(ul('p.vat', $l)) . ' ' . e($c['vat']) : '') . '</span>' . ($c['address'] ? '<span>' . e($c['address']) . '</span>' : '') . '</div>'
        . '<h2 class="h3">' . e($r['title']) . '</h2>' . ($qq['intro'] !== '' ? '<p>' . nl2br(e($qq['intro'])) . '</p>' : '')
        . '<table class="qdoc-t"><thead><tr><th>#</th><th>' . e(ul('p.description', $l)) . '</th><th class="num">' . e(ul('p.qty_short', $l)) . '</th><th class="num">' . e(ul('p.unit_price', $l)) . '</th><th class="num">' . e(ul('p.amount', $l)) . '</th></tr></thead><tbody>' . $rows . '</tbody>'
        . '<tfoot><tr><td colspan="4">' . e(ul('p.net', $l)) . '</td><td class="num">' . e(money($t['net'], $qq['currency'], $l)) . '</td></tr><tr><td colspan="4">' . e(ul('p.vat_line', $l, ['r' => $vatr])) . '</td><td class="num">' . e(money($t['vat'], $qq['currency'], $l)) . '</td></tr>'
        . '<tr class="qdoc-total"><td colspan="4">' . e(ul('p.total', $l)) . '</td><td class="num">' . e(money($t['total'], $qq['currency'], $l)) . '</td></tr></tfoot></table>'
        . ($qq['lead_time'] !== '' ? '<p><b>' . e(ul('p.lead_time', $l)) . ':</b> ' . e($qq['lead_time']) . '</p>' : '') . ($qq['terms'] !== '' ? '<div class="qdoc-terms"><b>' . e(ul('p.terms', $l)) . '</b><p>' . nl2br(e($qq['terms'])) . '</p></div>' : '')
        . ($qq['status'] === 'accepted' ? '<p class="qdoc-signed">' . icon('check', 'ic ic-s') . e(ul('p.accepted_by', $l, ['name' => $qq['decided_name'], 'date' => pdate($qq['decided_at'], true)])) . '</p>' : '') . '</article>';
}
function p_quote(string $no, bool $POST): void {
    $c = client();
    $qq = one("SELECT q.* FROM rf_quotes q JOIN rf_requests r ON r.id = q.request_id WHERE q.no = ? AND r.client_id = ? AND q.status <> 'draft'", [$no, $c['id']]);
    if (!$qq) { http_response_code(404); portal_page('404', '<div class="pt-card"><p>' . e(ui('nf.text')) . '</p></div>'); }
    $r = one('SELECT * FROM rf_requests WHERE id = ?', [$qq['request_id']]); $err = '';
    if ($qq['status'] === 'sent' && $qq['valid_until'] && strtotime($qq['valid_until']) < strtotime('today')) { q("UPDATE rf_quotes SET status = 'expired' WHERE id = ?", [$qq['id']]); $qq['status'] = 'expired'; }
    if ($POST && $qq['status'] === 'sent') {
        $do = (string)($_POST['do'] ?? '');
        if ($do === 'accept') {
            $name = pin('name', 120);
            if ($name === '' || empty($_POST['agree'])) $err = ui('p.err_required');
            else {
                tx(function () use ($qq, $r, $name, $c) {
                    q("UPDATE rf_quotes SET status = 'accepted', decided_at = now(), decided_name = ?, decided_ip = ? WHERE id = ? AND status = 'sent'", [$name, ip(), $qq['id']]);
                    q("UPDATE rf_quotes SET status = 'superseded' WHERE request_id = ? AND id <> ? AND status = 'sent'", [$r['id'], $qq['id']]);
                    q("UPDATE rf_requests SET status = 'accepted', updated_at = now() WHERE id = ?", [$r['id']]);
                    q("INSERT INTO rf_messages (request_id, author_kind, author_name, body) VALUES (?, 'system', '', ?)", [$r['id'], "Quote {$qq['no']} accepted by $name"]);
                    event_add((int)$r['id'], 'quote_accepted', ['quote' => $qq['no'], 'name' => $name]);
                });
                mail_staff("Кабинет: КП {$qq['no']} ПРИНЯТО — " . ($c['company'] ?: $c['name']), "Клиент принял предложение {$qq['no']} по запросу {$r['no']}.\nПодпись: $name\nIP: " . ip());
                pflash(ui('p.accepted_ok')); pgo('quotes/' . $qq['no'], 'ok=1');
            }
        } elseif ($do === 'decline') {
            $note = trim(mb_substr((string)($_POST['note'] ?? ''), 0, 2000));
            tx(function () use ($qq, $r, $note, $c) {
                q("UPDATE rf_quotes SET status = 'declined', decided_at = now(), decided_name = ?, decision_note = ?, decided_ip = ? WHERE id = ? AND status = 'sent'", [$c['name'], $note, ip(), $qq['id']]);
                q("UPDATE rf_requests SET status = 'review', updated_at = now() WHERE id = ?", [$r['id']]);
                q("INSERT INTO rf_messages (request_id, author_kind, author_name, body) VALUES (?, 'system', '', ?)", [$r['id'], "Quote {$qq['no']} declined" . ($note !== '' ? ": $note" : '')]);
                event_add((int)$r['id'], 'quote_declined', ['quote' => $qq['no']]);
            });
            mail_staff("Кабинет: КП {$qq['no']} отклонено — " . ($c['company'] ?: $c['name']), $note !== '' ? "Причина: $note" : 'Без комментария.');
            pflash(ui('p.declined_ok')); pgo('requests/' . $r['no']);
        }
    }
    $act = '';
    if ($qq['status'] === 'sent') {
        $act = '<section class="pt-card pt-decide" data-decide><form method="post" class="form">' . pcsrf() . '<input type="hidden" name="do" value="accept">' . ($err ? '<p class="pt-err" role="alert">' . e($err) . '</p>' : '')
             . fld(ui('p.accept_name'), inp('name', $c['name'], 'required maxlength="120" autocomplete="name"')) . '<label class="chk"><input type="checkbox" name="agree" value="1" required> <span>' . e(ui('p.accept_check', ['company' => $c['company'] ?: $c['name']])) . '</span></label>'
             . '<button class="btn btn-red" type="submit">' . icon('check', 'ic ic-s') . e(ui('p.accept')) . '</button></form>'
             . '<details class="pt-decline"><summary>' . e(ui('p.decline')) . '</summary><form method="post" class="form">' . pcsrf() . '<input type="hidden" name="do" value="decline">' . fld(ui('p.decline_reason'), '<textarea name="note" rows="3" maxlength="2000"></textarea>') . '<button class="btn btn-line" type="submit">' . e(ui('p.decline')) . '</button></form></details></section>';
    }
    $body = '<div class="pt-bar pt-noprint"><a class="lnk" href="' . e(purl('requests/' . $r['no'])) . '">← ' . e(ui('p.request', ['no' => $r['no']])) . '</a><span>' . chip_quote($qq['status']) . '<button type="button" class="btn btn-line btn-s" data-print>' . icon('print', 'ic ic-s') . e(ui('p.print')) . '</button></span></div>'
          . (isset($_GET['ok']) && $qq['status'] === 'accepted' ? '<div class="pt-lit" aria-hidden="true">' . lottie('rf_success', '', 'lt-ok-big', '') . '</div>' : '')
          . '<div class="pt-quote">' . quote_doc($qq, $r, $c, lang()) . $act . '</div>';
    portal_page(ui('p.quote', ['no' => $qq['no']]), $body, 'dash');
}

/* ---------------- files, account ---------------- */
function p_file(string $id): void {
    $f = ctype_digit($id) ? one('SELECT f.* FROM rf_files f JOIN rf_requests r ON r.id = f.request_id WHERE f.id = ? AND r.client_id = ?', [(int)$id, client()['id']]) : null;
    if (!$f) { http_response_code(404); exit('Not found'); }
    file_send($f);
}
function p_account(bool $POST): void {
    $c = client(); $err = '';
    if ($POST) {
        if (($_POST['do'] ?? '') === 'pw') {
            if (!password_verify((string)($_POST['old'] ?? ''), (string)$c['pass_hash'])) $err = ui('p.err_pw_current');
            elseif (mb_strlen((string)($_POST['pw'] ?? '')) < 10 || $_POST['pw'] !== ($_POST['pw2'] ?? '')) $err = ui('p.err_pw');
            else { q('UPDATE rf_clients SET pass_hash = ? WHERE id = ?', [password_hash((string)$_POST['pw'], PASSWORD_DEFAULT), $c['id']]); session_regenerate_id(true); pflash(ui('p.pw_changed')); pgo('account'); }
        } else {
            $l = in_array($_POST['lang'] ?? '', langs(), true) ? $_POST['lang'] : $c['lang'];
            $name = pin('name', 160); $company = pin('company', 160);
            if ($name === '' || $company === '') $err = ui('p.err_required');
            else { q('UPDATE rf_clients SET name = ?, company = ?, phone = ?, country = ?, vat = ?, address = ?, lang = ?, notify = ? WHERE id = ?', [$name, $company, pin('phone', 60), pin('country', 80), pin('vat', 40), pin('address', 300), $l, !empty($_POST['notify']) ? 'true' : 'false', $c['id']]); pflash(ui('p.saved')); pgo('account'); }
        }
    }
    $lo = ''; foreach (langs() as $l) $lo .= '<option value="' . $l . '"' . ($c['lang'] === $l ? ' selected' : '') . '>' . e(LANG_NAMES[$l]) . '</option>';
    $body = ($err ? '<p class="pt-err" role="alert">' . e($err) . '</p>' : '') . '<div class="pt-cols"><form class="pt-card form pt-main" method="post">' . pcsrf() . '<input type="hidden" name="do" value="profile"><h2 class="h4">' . e(ui('p.company_details')) . '</h2>'
        . '<div class="row2">' . fld(ui('p.name') . ' *', inp('name', $c['name'], 'required autocomplete="name"')) . fld(ui('p.company') . ' *', inp('company', $c['company'], 'required autocomplete="organization"')) . '</div>'
        . '<div class="row2">' . fld(ui('p.email'), inp('email_ro', $c['email'], 'disabled')) . fld(ui('p.phone'), inp('phone', $c['phone'], 'type="tel" autocomplete="tel"')) . '</div>'
        . '<div class="row2">' . fld(ui('p.vat'), inp('vat', $c['vat'])) . fld(ui('p.country'), inp('country', $c['country'], 'autocomplete="country-name"')) . '</div>'
        . fld(ui('p.address'), inp('address', $c['address'], 'autocomplete="street-address"')) . '<div class="row2">' . fld(ui('p.lang'), '<select name="lang">' . $lo . '</select>') . '<span></span></div>'
        . '<label class="chk"><input type="checkbox" name="notify" value="1"' . ($c['notify'] ? ' checked' : '') . '> <span>' . e(ui('p.notify')) . '</span></label><button class="btn btn-dark" type="submit">' . e(ui('p.save')) . '</button></form>'
        . '<form class="pt-card form pt-side" method="post">' . pcsrf() . '<input type="hidden" name="do" value="pw"><h2 class="h4">' . e(ui('p.change_pw')) . '</h2>' . fld(ui('p.pw_current'), '<input type="password" name="old" required autocomplete="current-password">')
        . fld(ui('p.password_new'), '<input type="password" name="pw" required minlength="10" autocomplete="new-password">') . fld(ui('p.password2'), '<input type="password" name="pw2" required minlength="10" autocomplete="new-password">') . '<button class="btn btn-line" type="submit">' . e(ui('p.change_pw')) . '</button></form></div>';
    portal_page(ui('p.account'), $body, 'account');
}
