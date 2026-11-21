<?php
/* Reclame Fabriek website · front controller.
   English lives at the old addresses (/profile-7-lux/), other languages under /lv/ /ru/ /nl/ /de/ /sv/. */
declare(strict_types=1);
require __DIR__ . '/app/core.php';
require __DIR__ . '/app/views.php';

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

// Local test server: serve real files, but never the private folders.
if (PHP_SAPI === 'cli-server') {
    if (preg_match('#^/(?:data|app|tools)(?:/|$)|(?:^|/)\.|[\\\\:\x00-\x1f]#i', $path)) {
        http_response_code(403);
        exit('Forbidden');
    }
    if (in_array($path, ['/admin', '/admin/', '/admin/index.php'], true)) {
        require __DIR__ . '/admin/index.php';
        return true;
    }
    if (preg_match('#^/(?:favicon\.ico|(?:assets|admin/assets|uploads)/.+\.(?:css|js|json|woff2|webp|png|jpe?g|svg|ico))$#i', $path)
        && is_file(__DIR__ . $path)) return false;
}

// compress pages here when the host doesn't (mod_deflate skips responses that are already compressed)
if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'cli-server' && extension_loaded('zlib') && !ini_get('zlib.output_compression')) @ini_set('zlib.output_compression', '1');

/* Full-page cache for visitors: rendered pages are kept in data/_cache and served without rebuilding them.
   Every save in the admin clears it (see jwrite in app/core.php). Admins, the portal, forms and pages with a query string are never cached. */
$PAGE_CACHE = null;
if (getenv('RF_SITE_URL') === false && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && ($_SERVER['QUERY_STRING'] ?? '') === '' && !isset($_COOKIE['rfadmin'])
    && !preg_match('#^/(?:[a-z]{2}/)?(?:portal|api|admin)(?:/|$)#', $path) && !is_dir(DATA . '/_cache/.off')) {
    $PAGE_CACHE = DATA . '/_cache/' . md5($path) . '.html';
    if (is_file($PAGE_CACHE)) {
        $etag = '"' . md5_file($PAGE_CACHE) . '"';
        header('ETag: ' . $etag); header('Cache-Control: public, max-age=0, must-revalidate'); header('X-Page-Cache: hit');
        if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) { http_response_code(304); exit; }
        header('Content-Type: text/html; charset=utf-8'); header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: strict-origin-when-cross-origin');
        readfile($PAGE_CACHE); exit;
    }
}

function send(string $html, int $code = 200, string $type = 'text/html; charset=utf-8'): void {
    global $PAGE_CACHE;
    http_response_code($code);
    header('Content-Type: ' . $type);
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if ($code === 200 && $PAGE_CACHE && str_starts_with($type, 'text/html') && empty($GLOBALS['PREVIEW'])) {
        if (!is_dir(dirname($PAGE_CACHE))) @mkdir(dirname($PAGE_CACHE), 0775, true);
        $tmp = $PAGE_CACHE . '.' . bin2hex(random_bytes(3)); if (@file_put_contents($tmp, $html) !== false) @rename($tmp, $PAGE_CACHE);
        header('ETag: "' . md5($html) . '"'); header('Cache-Control: public, max-age=0, must-revalidate');
    }
    echo $html;
}

/* ---------- form endpoint ---------- */
if ($path === '/api/contact' || $path === '/contact.php') {
    header('Content-Type: application/json; charset=utf-8');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('{"ok":false}'); }
    if (!empty($_POST['website'])) exit('{"ok":true}');                    // honeypot
    // rate limit: 6 sends per 10 minutes per address
    $ipk = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . 'rf');
    $rl = jread('_inbox/.rate.json', []); $now = time();
    $rl = array_filter($rl, fn($t) => is_array($t) && max($t) > $now - 600);
    $rl[$ipk] = array_values(array_filter($rl[$ipk] ?? [], fn($t) => $t > $now - 600));
    if (count($rl[$ipk]) >= 6) { http_response_code(429); exit('{"ok":false,"error":"rate"}'); }
    $rl[$ipk][] = $now; jwrite('_inbox/.rate.json', $rl, false);
    $clean = fn($k, $max = 200) => trim(mb_substr(str_replace(["\r", "\n"], ' ', (string)($_POST[$k] ?? '')), 0, $max));
    $type = $clean('type', 20) === 'subscribe' ? 'subscribe' : 'quote';
    $email = filter_var($clean('email'), FILTER_VALIDATE_EMAIL);
    if (!$email) { http_response_code(422); exit('{"ok":false,"error":"email"}'); }
    $msg = ['id' => date('Ymd-His') . '-' . bin2hex(random_bytes(3)), 'type' => $type, 'date' => date('c'), 'read' => false,
            'lang' => in_array($clean('lang', 5), array_keys(LANG_NAMES), true) ? $clean('lang', 5) : '',
            'name' => $clean('name'), 'company' => $clean('company'), 'email' => $email, 'phone' => $clean('phone', 60),
            'profile' => $clean('profile', 90), 'message' => trim(mb_substr((string)($_POST['message'] ?? ''), 0, 5000))];
    jwrite('_inbox/' . $msg['id'] . '.json', $msg, false);                  // every request is kept in the admin inbox
    $s = settings();
    $lines = [];
    foreach (['name' => 'Name', 'company' => 'Company', 'email' => 'Email', 'phone' => 'Phone', 'profile' => 'Profile', 'lang' => 'Language'] as $k => $lab) if ($msg[$k] !== '') $lines[] = "$lab: " . $msg[$k];
    if ($msg['message'] !== '') $lines[] = "\nMessage:\n" . $msg['message'];
    $subject = $type === 'subscribe' ? 'Website: newsletter sign-up' : 'Website: new request' . ($msg['company'] ? ' from ' . $msg['company'] : '');
    $from = $s['form_from'] ?? 'website@reclamefabriek.eu';
    $headers = "From: Reclame Fabriek website <$from>\r\nReply-To: $email\r\nContent-Type: text/plain; charset=utf-8";
    $sent = function_exists('mail') && getenv('RF_NO_MAIL') !== '1'
        && @mail($s['form_to'] ?? 'pa@reclamefabriek.eu', '=?UTF-8?B?' . base64_encode($subject) . '?=', implode("\n", $lines) . "\n\nSent from reclamefabriek.eu", $headers);
    echo json_encode(['ok' => true, 'mailed' => (bool)$sent]);
    exit;
}

if ($path === '/sitemap.xml') { send(sitemap_xml(), 200, 'application/xml; charset=utf-8'); exit; }
if ($path === '/robots.txt') { send("User-agent: *\nDisallow: /admin/\nAllow: /\nSitemap: " . site_origin() . "/sitemap.xml\n", 200, 'text/plain; charset=utf-8'); exit; }

/* ---------- language + route ---------- */
$seg = array_values(array_filter(explode('/', $path), 'strlen'));
$GLOBALS['LANG'] = default_lang();
if ($seg && in_array($seg[0], langs(), true) && $seg[0] !== default_lang()) { $GLOBALS['LANG'] = array_shift($seg); }
elseif ($seg && $seg[0] === default_lang() && count($seg) >= 1 && isset(LANG_NAMES[$seg[0]])) { // /en/... -> /...
    header('Location: /' . implode('/', array_slice($seg, 1)) . (count($seg) > 1 ? '/' : ''), true, 301); exit;
}
// add the trailing slash the old site used
if (!str_ends_with($path, '/') && !preg_match('/\.[a-z0-9]{2,5}$/i', $path)) { header('Location: ' . $path . '/' . (($q = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)) ? '?' . $q : ''), true, 301); exit; }

$slug = $seg[0] ?? '';
// client portal: requests, quotes, messages (PostgreSQL); the only part of the public site that uses a cookie
if ($slug === 'portal') { require __DIR__ . '/app/portal.php'; portal_route(array_slice($seg, 1)); exit; }
// old WordPress category pages
if ($slug === 'category') { $to = ['story' => '/projects/', 'product-profiles' => '/product-profiles/'][$seg[1] ?? ''] ?? '/'; header('Location: ' . url($to), true, 301); exit; }
if ($slug === 'jet-popup') { header('Location: ' . url('/contact/'), true, 301); exit; }
if (count($seg) > 1) { send(page_404(), 404); exit; }

// admins may preview unpublished items (session only exists for logged-in admins; visitors get no cookie)
$isAdmin = false;
if (isset($_COOKIE['rfadmin'])) { session_name('rfadmin'); session_start(['read_and_close' => true]); $isAdmin = !empty($_SESSION['uid']); }

$routes = ['' => 'page_home', 'product-profiles' => 'page_profiles', 'projects' => 'page_projects', 'signage-research' => 'page_research', 'contact' => 'page_contact', 'privacy-policy' => 'page_privacy'];
if (isset($routes[$slug])) { send($routes[$slug]()); exit; }
foreach (['profiles' => 'page_profile', 'stories' => 'page_story'] as $type => $fn) {
    $it = item($type, $slug);
    if ($it && (!empty($it['published']) || $isAdmin)) { $GLOBALS['PREVIEW'] = empty($it['published']); send($fn($it)); exit; }
}
$red = jread('redirects.json', []);
if (isset($red[$slug])) { header('Location: ' . url('/' . $red[$slug] . '/'), true, 301); exit; }

send(page_404(), 404);
