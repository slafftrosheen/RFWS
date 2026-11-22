<?php
/* Reclame Fabriek · database for the client portal (PostgreSQL: a local server or Supabase).
   The public website never needs the database; only /portal/ and the admin's portal screens do.

   Connection, first match wins:
     1. environment variable DATABASE_URL   e.g. postgresql://rf:secret@127.0.0.1:5432/reclamefabriek
     2. data/_auth/db.json  {"url": "..."}  written by Admin → Кабинет клиентов → Настройки
   Supabase: use the "Session pooler" connection string and add ?sslmode=require. */
declare(strict_types=1);

const DB_SCHEMA_VERSION = 3;

function db_url(): string {
    $env = getenv('DATABASE_URL');
    if (is_string($env) && $env !== '') return $env;
    $j = jread('_auth/db.json', []);
    return (string)($j['url'] ?? '');
}
function db_url_source(): string { $env = getenv('DATABASE_URL'); return (is_string($env) && $env !== '') ? 'env' : (db_url() ? 'file' : ''); }

/** postgresql://user:pass@host:port/db?sslmode=require  ->  [dsn, user, pass] */
function db_parse(string $url): ?array {
    $p = parse_url($url);
    if (!$p || !in_array($p['scheme'] ?? '', ['postgres', 'postgresql', 'pgsql'], true) || empty($p['host'])) return null;
    parse_str($p['query'] ?? '', $q);
    $dsn = 'pgsql:host=' . $p['host'] . ';port=' . (int)($p['port'] ?? 5432) . ';dbname=' . ltrim(rawurldecode($p['path'] ?? '/postgres'), '/');
    if (!empty($q['sslmode']) && preg_match('/^[a-z-]+$/', (string)$q['sslmode'])) $dsn .= ';sslmode=' . $q['sslmode'];
    return [$dsn, rawurldecode($p['user'] ?? ''), rawurldecode($p['pass'] ?? '')];
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $c = db_parse(db_url());
    if (!$c) throw new RuntimeException('not_configured');
    $pdo = new PDO($c[0], $c[1], $c[2], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                         PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 5]);
    $pdo->exec("SET TIME ZONE 'Europe/Riga'");
    db_migrate($pdo);
    return $pdo;
}
function db_ok(): bool { try { db(); return true; } catch (Throwable $e) { $GLOBALS['DB_ERR'] = $e->getMessage(); return false; } }

function q(string $sql, array $args = []): PDOStatement { $st = db()->prepare($sql); $st->execute($args); return $st; }
function one(string $sql, array $args = []): ?array { $r = q($sql, $args)->fetch(); return $r ?: null; }
function all(string $sql, array $args = []): array { return q($sql, $args)->fetchAll(); }
function val(string $sql, array $args = []) { $r = q($sql, $args)->fetchColumn(); return $r === false ? null : $r; }
function tx(callable $fn) { $pdo = db(); $pdo->beginTransaction(); try { $r = $fn(); $pdo->commit(); return $r; } catch (Throwable $e) { $pdo->rollBack(); throw $e; } }
/** Next number for a counter: RF-2026-0007 style. Atomic. */
function next_no(string $prefix): string {
    $k = $prefix . '-' . date('Y');
    $n = (int)val('INSERT INTO rf_counters (k, n) VALUES (?, 1) ON CONFLICT (k) DO UPDATE SET n = rf_counters.n + 1 RETURNING n', [$k]);
    return $k . '-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
}

function db_migrate(PDO $pdo): void {
    $pdo->exec('CREATE TABLE IF NOT EXISTS rf_meta (k text PRIMARY KEY, v text NOT NULL)');
    $v = (int)($pdo->query("SELECT v FROM rf_meta WHERE k = 'schema'")->fetchColumn() ?: 0);
    if ($v >= DB_SCHEMA_VERSION) return;
    $pdo->exec('SELECT pg_advisory_lock(7310)');
    try {
        $v = (int)($pdo->query("SELECT v FROM rf_meta WHERE k = 'schema'")->fetchColumn() ?: 0);
        if ($v < 1) {
            $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS rf_counters (k text PRIMARY KEY, n integer NOT NULL);
CREATE TABLE IF NOT EXISTS rf_clients (
  id bigserial PRIMARY KEY,
  email text NOT NULL UNIQUE,
  pass_hash text,
  name text NOT NULL DEFAULT '', company text NOT NULL DEFAULT '', vat text NOT NULL DEFAULT '', phone text NOT NULL DEFAULT '',
  country text NOT NULL DEFAULT '', address text NOT NULL DEFAULT '',
  lang varchar(2) NOT NULL DEFAULT 'en',
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'blocked')),
  email_verified_at timestamptz, notify boolean NOT NULL DEFAULT true,
  staff_note text NOT NULL DEFAULT '',
  created_at timestamptz NOT NULL DEFAULT now(), last_login_at timestamptz
);
CREATE TABLE IF NOT EXISTS rf_tokens (
  id bigserial PRIMARY KEY, client_id bigint NOT NULL REFERENCES rf_clients(id) ON DELETE CASCADE,
  kind text NOT NULL CHECK (kind IN ('verify', 'reset', 'invite')), token_hash text NOT NULL UNIQUE,
  expires_at timestamptz NOT NULL, used_at timestamptz, created_at timestamptz NOT NULL DEFAULT now()
);
CREATE TABLE IF NOT EXISTS rf_requests (
  id bigserial PRIMARY KEY, no text NOT NULL UNIQUE, client_id bigint NOT NULL REFERENCES rf_clients(id) ON DELETE CASCADE,
  title text NOT NULL,
  status text NOT NULL DEFAULT 'new' CHECK (status IN ('new', 'review', 'quoted', 'accepted', 'production', 'ready', 'installed', 'closed', 'cancelled')),
  site_address text NOT NULL DEFAULT '', deadline date, notes text NOT NULL DEFAULT '', source text NOT NULL DEFAULT 'portal',
  created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now(),
  client_seen_at timestamptz NOT NULL DEFAULT now(), staff_seen_at timestamptz
);
CREATE INDEX IF NOT EXISTS rf_requests_client ON rf_requests (client_id, updated_at DESC);
CREATE TABLE IF NOT EXISTS rf_request_items (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES rf_requests(id) ON DELETE CASCADE, position integer NOT NULL DEFAULT 0,
  profile_slug text NOT NULL DEFAULT '', profile_title text NOT NULL DEFAULT '', light text NOT NULL DEFAULT '',
  content text NOT NULL DEFAULT '', height_mm integer, depth text NOT NULL DEFAULT '', qty integer NOT NULL DEFAULT 1,
  face_colour text NOT NULL DEFAULT '', side_colour text NOT NULL DEFAULT '',
  mounting text NOT NULL DEFAULT '' CHECK (mounting IN ('', 'no_frame', 'on_frame')), notes text NOT NULL DEFAULT ''
);
CREATE TABLE IF NOT EXISTS rf_messages (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES rf_requests(id) ON DELETE CASCADE,
  author_kind text NOT NULL CHECK (author_kind IN ('client', 'staff', 'system')), author_name text NOT NULL DEFAULT '',
  body text NOT NULL DEFAULT '', created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS rf_messages_req ON rf_messages (request_id, created_at);
CREATE TABLE IF NOT EXISTS rf_files (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES rf_requests(id) ON DELETE CASCADE,
  message_id bigint REFERENCES rf_messages(id) ON DELETE SET NULL, uploader_kind text NOT NULL,
  original_name text NOT NULL, stored_name text NOT NULL UNIQUE, mime text NOT NULL DEFAULT '', size bigint NOT NULL DEFAULT 0,
  created_at timestamptz NOT NULL DEFAULT now()
);
CREATE TABLE IF NOT EXISTS rf_quotes (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES rf_requests(id) ON DELETE CASCADE,
  no text NOT NULL UNIQUE, version integer NOT NULL DEFAULT 1,
  status text NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'sent', 'accepted', 'declined', 'expired', 'superseded')),
  currency text NOT NULL DEFAULT 'EUR', vat_rate numeric(5,2) NOT NULL DEFAULT 21, valid_until date,
  lead_time text NOT NULL DEFAULT '', intro text NOT NULL DEFAULT '', terms text NOT NULL DEFAULT '',
  created_by text NOT NULL DEFAULT '', created_at timestamptz NOT NULL DEFAULT now(), sent_at timestamptz,
  decided_at timestamptz, decided_name text NOT NULL DEFAULT '', decision_note text NOT NULL DEFAULT '', decided_ip text NOT NULL DEFAULT ''
);
CREATE TABLE IF NOT EXISTS rf_quote_lines (
  id bigserial PRIMARY KEY, quote_id bigint NOT NULL REFERENCES rf_quotes(id) ON DELETE CASCADE, position integer NOT NULL DEFAULT 0,
  profile_slug text NOT NULL DEFAULT '', description text NOT NULL, qty numeric(12,2) NOT NULL DEFAULT 1,
  unit text NOT NULL DEFAULT 'pcs', unit_price numeric(12,2) NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS rf_events (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES rf_requests(id) ON DELETE CASCADE,
  kind text NOT NULL, data jsonb NOT NULL DEFAULT '{}', created_at timestamptz NOT NULL DEFAULT now()
);
CREATE TABLE IF NOT EXISTS rf_throttle (k text PRIMARY KEY, n integer NOT NULL, until timestamptz NOT NULL);
SQL);
            $pdo->exec("INSERT INTO rf_meta (k, v) VALUES ('schema', '1') ON CONFLICT (k) DO UPDATE SET v = '1'");
        }
        if ($v < 2) {   // 2.3: saved sites (address book), design proofs, feedback after installation, activity feed
            $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS rf_sites (
  id bigserial PRIMARY KEY, client_id bigint NOT NULL REFERENCES rf_clients(id) ON DELETE CASCADE,
  label text NOT NULL, address text NOT NULL DEFAULT '', contact text NOT NULL DEFAULT '', phone text NOT NULL DEFAULT '', notes text NOT NULL DEFAULT '',
  created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS rf_sites_client ON rf_sites (client_id);
CREATE TABLE IF NOT EXISTS rf_proofs (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES rf_requests(id) ON DELETE CASCADE,
  file_id bigint NOT NULL REFERENCES rf_files(id) ON DELETE CASCADE, version integer NOT NULL DEFAULT 1, note text NOT NULL DEFAULT '',
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'changes', 'superseded')),
  created_by text NOT NULL DEFAULT '', created_at timestamptz NOT NULL DEFAULT now(),
  decided_at timestamptz, decided_name text NOT NULL DEFAULT '', decision_note text NOT NULL DEFAULT '', decided_ip text NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS rf_proofs_req ON rf_proofs (request_id, version DESC);
CREATE TABLE IF NOT EXISTS rf_feedback (
  request_id bigint PRIMARY KEY REFERENCES rf_requests(id) ON DELETE CASCADE,
  rating integer NOT NULL CHECK (rating BETWEEN 1 AND 5), comment text NOT NULL DEFAULT '', created_at timestamptz NOT NULL DEFAULT now()
);
ALTER TABLE rf_requests ADD COLUMN IF NOT EXISTS site_id bigint REFERENCES rf_sites(id) ON DELETE SET NULL;
ALTER TABLE rf_clients ADD COLUMN IF NOT EXISTS activity_seen_at timestamptz NOT NULL DEFAULT now();
CREATE INDEX IF NOT EXISTS rf_events_req ON rf_events (request_id, created_at DESC);
SQL);
            $pdo->exec("INSERT INTO rf_meta (k, v) VALUES ('schema', '2') ON CONFLICT (k) DO UPDATE SET v = '2'");
        }
        if ($v < 3) {   // 2.4: owner + reminder + internal notes (staff only), installation appointment, extra notification e-mails
            $pdo->exec(<<<'SQL'
ALTER TABLE rf_requests ADD COLUMN IF NOT EXISTS assignee text NOT NULL DEFAULT '';
ALTER TABLE rf_requests ADD COLUMN IF NOT EXISTS follow_up date;
ALTER TABLE rf_requests ADD COLUMN IF NOT EXISTS install_at timestamptz;
ALTER TABLE rf_requests ADD COLUMN IF NOT EXISTS install_hours numeric(4,1) NOT NULL DEFAULT 4;
ALTER TABLE rf_requests ADD COLUMN IF NOT EXISTS install_state text NOT NULL DEFAULT '';
ALTER TABLE rf_requests ADD COLUMN IF NOT EXISTS install_note text NOT NULL DEFAULT '';
ALTER TABLE rf_requests ADD COLUMN IF NOT EXISTS install_crew text NOT NULL DEFAULT '';
ALTER TABLE rf_clients ADD COLUMN IF NOT EXISTS cc_emails text NOT NULL DEFAULT '';
CREATE TABLE IF NOT EXISTS rf_notes (
  id bigserial PRIMARY KEY, request_id bigint NOT NULL REFERENCES rf_requests(id) ON DELETE CASCADE,
  author text NOT NULL DEFAULT '', body text NOT NULL, created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS rf_notes_req ON rf_notes (request_id, created_at DESC);
CREATE INDEX IF NOT EXISTS rf_requests_install ON rf_requests (install_at) WHERE install_at IS NOT NULL;
SQL);
            $pdo->exec("INSERT INTO rf_meta (k, v) VALUES ('schema', '3') ON CONFLICT (k) DO UPDATE SET v = '3'");
        }
    } finally { $pdo->exec('SELECT pg_advisory_unlock(7310)'); }
}

/* ---------- small shared helpers ---------- */
/** Rate limit: at most $max hits per $sec seconds for key $k. Returns false when blocked. */
function throttle(string $k, int $max, int $sec): bool {
    $k = hash('sha256', $k);
    $r = one('SELECT n, until FROM rf_throttle WHERE k = ?', [$k]);
    if ($r && strtotime($r['until']) > time()) {
        if ((int)$r['n'] >= $max) return false;
        q('UPDATE rf_throttle SET n = n + 1 WHERE k = ?', [$k]); return true;
    }
    q('INSERT INTO rf_throttle (k, n, until) VALUES (?, 1, now() + make_interval(secs => ?)) ON CONFLICT (k) DO UPDATE SET n = 1, until = EXCLUDED.until', [$k, $sec]);
    return true;
}
function event_add(int $rid, string $kind, array $data = []): void { q('INSERT INTO rf_events (request_id, kind, data) VALUES (?, ?, ?::jsonb)', [$rid, $kind, json_encode($data, JSON_UNESCAPED_UNICODE)]); }
function money($n, string $cur = 'EUR', string $l = 'en'): string {
    $dec = in_array($l, ['en'], true) ? '.' : ','; $th = $l === 'en' ? ',' : ($l === 'de' || $l === 'nl' ? '.' : "\u{00A0}");
    return number_format((float)$n, 2, $dec, $th) . "\u{00A0}" . ($cur === 'EUR' ? '€' : $cur);
}
function quote_totals(array $lines, float $vat): array {
    $net = 0.0; foreach ($lines as $x) $net += round((float)$x['qty'] * (float)$x['unit_price'], 2);
    $tax = round($net * $vat / 100, 2);
    return ['net' => $net, 'vat' => $tax, 'total' => $net + $tax];
}

/* ---------- files (stored outside the web: data/_portal/files) ---------- */
const PORTAL_EXT = ['pdf', 'ai', 'eps', 'svg', 'dxf', 'dwg', 'cdr', 'png', 'jpg', 'jpeg', 'webp', 'tif', 'tiff', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
const PORTAL_MAX_FILE = 25 * 1024 * 1024;
/** Store uploaded files ($_FILES[$field]) for a request. Returns [ok count, error messages]. */
function files_store(string $field, int $rid, ?int $mid, string $who): array {
    $ok = 0; $err = [];
    $f = $_FILES[$field] ?? null; if (!$f || !is_array($f['name'])) return [0, []];
    $dir = DATA . '/_portal/files/' . date('Y/m');
    if (!is_dir($dir)) mkdir($dir, 0770, true);
    foreach ($f['name'] as $i => $name) {
        if (($f['error'][$i] ?? 4) === UPLOAD_ERR_NO_FILE || $name === '') continue;
        $name = mb_substr(preg_replace('/[^\p{L}\p{N}._ ()-]+/u', '_', basename((string)$name)), 0, 120);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($f['error'][$i] !== UPLOAD_ERR_OK) { $err[] = $name . ': upload'; continue; }
        if (!in_array($ext, PORTAL_EXT, true)) { $err[] = $name . ': type'; continue; }
        if ($f['size'][$i] > PORTAL_MAX_FILE) { $err[] = $name . ': size'; continue; }
        $stored = date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.bin';
        if (!move_uploaded_file($f['tmp_name'][$i], DATA . '/_portal/files/' . $stored)) { $err[] = $name . ': save'; continue; }
        q('INSERT INTO rf_files (request_id, message_id, uploader_kind, original_name, stored_name, mime, size) VALUES (?, ?, ?, ?, ?, ?, ?)',
          [$rid, $mid, $who, $name, $stored, $ext, (int)$f['size'][$i]]);
        $ok++;
    }
    return [$ok, $err];
}
/** One uploaded file (admin proofs). Returns [file id or null, error]. */
function file_store_one(string $field, int $rid, string $who, array $only = []): array {
    $f = $_FILES[$field] ?? null; if (!$f || is_array($f['name']) || $f['error'] === UPLOAD_ERR_NO_FILE) return [null, 'none'];
    $name = mb_substr(preg_replace('/[^\p{L}\p{N}._ ()-]+/u', '_', basename((string)$f['name'])), 0, 120); $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($f['error'] !== UPLOAD_ERR_OK) return [null, 'upload'];
    if (!in_array($ext, $only ?: PORTAL_EXT, true)) return [null, 'type'];
    if ($f['size'] > PORTAL_MAX_FILE) return [null, 'size'];
    $dir = DATA . '/_portal/files/' . date('Y/m'); if (!is_dir($dir)) mkdir($dir, 0770, true);
    $stored = date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.bin';
    if (!move_uploaded_file($f['tmp_name'], DATA . '/_portal/files/' . $stored)) return [null, 'save'];
    $id = (int)val('INSERT INTO rf_files (request_id, message_id, uploader_kind, original_name, stored_name, mime, size) VALUES (?, NULL, ?, ?, ?, ?, ?) RETURNING id', [$rid, $who, $name, $stored, $ext, (int)$f['size']]);
    return [$id, ''];
}
const PREVIEW_MIME = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'];
/** Raster images may be shown inline (proof previews); everything else is always downloaded. */
function file_view(array $file): never {
    $p = DATA . '/_portal/files/' . $file['stored_name']; $mime = PREVIEW_MIME[strtolower((string)$file['mime'])] ?? null;
    if (!$mime || !is_file($p) || str_contains($file['stored_name'], '..')) file_send($file);
    header('Content-Type: ' . $mime); header('Content-Disposition: inline'); header('Content-Length: ' . filesize($p));
    header('X-Content-Type-Options: nosniff'); header("Content-Security-Policy: default-src 'none'; sandbox"); header('Cache-Control: private, max-age=3600');
    readfile($p); exit;
}
function file_send(array $file): never {
    $p = DATA . '/_portal/files/' . $file['stored_name'];
    if (!is_file($p) || str_contains($file['stored_name'], '..')) { http_response_code(404); exit('Not found'); }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $file['original_name']) . '"; filename*=UTF-8\'\'' . rawurlencode($file['original_name']));
    header('Content-Length: ' . filesize($p)); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
    readfile($p); exit;
}
function fsize(int $b): string { return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, (int)round($b / 1024)) . ' KB'; }

/* ---------- mail: PHP mail(), and a copy in data/_portal/outbox so nothing is lost if the host blocks mail ---------- */
function send_mail(string $to, string $subject, string $body, string $replyTo = ''): bool {
    $s = settings(); $from = $s['form_from'] ?? 'website@reclamefabriek.eu';
    $clean = fn($x) => trim(str_replace(["\r", "\n"], ' ', $x));
    $to = $clean($to); $replyTo = $clean($replyTo);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    $dir = DATA . '/_portal/outbox'; if (!is_dir($dir)) mkdir($dir, 0770, true);
    $sent = function_exists('mail') && getenv('RF_NO_MAIL') !== '1'
        && @mail($to, '=?UTF-8?B?' . base64_encode($clean($subject)) . '?=', $body,
                 "From: Reclame Fabriek <$from>\r\n" . ($replyTo ? "Reply-To: $replyTo\r\n" : '') . "Content-Type: text/plain; charset=utf-8\r\nMIME-Version: 1.0");
    file_put_contents($dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.json',
        json_encode(['to' => $to, 'subject' => $subject, 'body' => $body, 'sent' => $sent, 'date' => date('c')], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $old = glob($dir . '/*.json') ?: []; sort($old); while (count($old) > 300) @unlink(array_shift($old));
    return $sent;
}
function portal_cfg(): array {
    $d = ['registration' => 'auto', 'vat' => 21, 'valid_days' => 30, 'notify' => '', 'terms' => []];
    return array_merge($d, settings()['portal'] ?? []);
}

/* ---------- calendar file (.ics) for an installation appointment ---------- */
function ics_install(array $r, string $summary, string $desc, string $loc): string {
    $esc = fn($x) => str_replace(["\\", ';', ',', "\r", "\n"], ['\\\\', '\\;', '\\,', '', '\\n'], $x);
    $t0 = strtotime((string)$r['install_at']); $t1 = $t0 + (int)round((float)$r['install_hours'] * 3600);
    $u = fn($t) => gmdate('Ymd\THis\Z', $t);
    $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Reclame Fabriek//Portal//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'BEGIN:VEVENT',
              'UID:install-' . $r['no'] . '@reclamefabriek.eu', 'DTSTAMP:' . $u(time()), 'DTSTART:' . $u($t0), 'DTEND:' . $u($t1),
              'SUMMARY:' . $esc($summary), 'DESCRIPTION:' . $esc($desc), 'LOCATION:' . $esc($loc), 'STATUS:' . ($r['install_state'] === 'confirmed' ? 'CONFIRMED' : 'TENTATIVE'), 'END:VEVENT', 'END:VCALENDAR'];
    $out = '';
    foreach ($lines as $ln) { while (strlen($ln) > 74) { $cut = 74; while ($cut > 1 && (ord($ln[$cut]) & 0xC0) === 0x80) $cut--; $out .= substr($ln, 0, $cut) . "\r\n "; $ln = substr($ln, $cut); } $out .= $ln . "\r\n"; }
    return $out;
}
function ics_send(string $ics, string $name): never {
    header('Content-Type: text/calendar; charset=utf-8'); header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $name) . '"');
    header('Cache-Control: private, no-store'); echo $ics; exit;
}
/** "a@x.lv, b@y.lv" -> clean unique list (max 3), never the main address. */
function cc_list(string $raw, string $main = ''): array {
    $o = [];
    foreach (preg_split('/[\s,;]+/', strtolower($raw)) ?: [] as $e) if ($e !== '' && $e !== strtolower($main) && filter_var($e, FILTER_VALIDATE_EMAIL) && !in_array($e, $o, true)) $o[] = $e;
    return array_slice($o, 0, 3);
}
