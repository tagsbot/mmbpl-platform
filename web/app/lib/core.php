<?php
/**
 * MMBPL core helpers: database, settings, i18n, views, security.
 */
declare(strict_types=1);

const APP_VERSION = '1.0.0';

/* ---------------- Database ---------------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'] ?? '3306', $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function row(string $sql, array $p = []): ?array { $r = q($sql, $p)->fetch(); return $r ?: null; }
function rows(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function val(string $sql, array $p = []) { $v = q($sql, $p)->fetchColumn(); return $v === false ? null : $v; }

function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
    q($sql, array_values($data));
    return (int)db()->lastInsertId();
}
function update(string $table, array $data, string $where, array $wp = []): int
{
    $set = implode(',', array_map(fn($c) => "`$c`=?", array_keys($data)));
    return q("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $wp))->rowCount();
}

/* ---------------- Config & settings ---------------- */
function config(?string $key = null)
{
    global $CONFIG;
    return $key === null ? $CONFIG : ($CONFIG[$key] ?? null);
}

function setting(string $k, $default = '')
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try { foreach (rows('SELECT k, v FROM settings') as $r) $cache[$r['k']] = $r['v']; } catch (Throwable $e) {}
    }
    return (isset($cache[$k]) && $cache[$k] !== '') ? $cache[$k] : $default;
}
function set_setting(string $k, ?string $v): void
{
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
}

/* ---------------- URLs ---------------- */
function base_path(): string
{
    static $b = null;
    if ($b === null) {
        $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        if (substr($b, -8) === '/install') $b = substr($b, 0, -8);
    }
    return $b;
}
function url(string $path = ''): string { return base_path() . '/' . ltrim($path, '/'); }
function asset(string $path): string
{
    $f = APP_ROOT . '/assets/' . $path;
    $v = is_file($f) ? filemtime($f) : APP_VERSION;
    return url('assets/' . $path) . '?v=' . $v;
}
function upload_url(?string $file): string { return $file ? url('uploads/' . $file) : ''; }
function absolute_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}
function redirect(string $path): never
{
    header('Location: ' . (preg_match('~^https?://~', $path) ? $path : url($path)));
    exit;
}
function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $b = base_path();
    if ($b !== '' && strpos($uri, $b) === 0) $uri = substr($uri, strlen($b));
    return '/' . trim($uri, '/');
}

/* ---------------- i18n ---------------- */
function lang(): string
{
    static $l = null;
    if ($l === null) {
        $l = $_SESSION['lang'] ?? ($_COOKIE['lang'] ?? 'en');
        if (!in_array($l, ['en', 'ur'], true)) $l = 'en';
    }
    return $l;
}
function is_rtl(): bool { return lang() === 'ur'; }
function t(string $key, array $vars = []): string
{
    static $dict = [];
    $l = lang();
    if (!isset($dict[$l])) {
        $dict['en'] = require APP_ROOT . '/app/lang/en.php';
        $dict[$l] = $l === 'en' ? $dict['en'] : array_merge($dict['en'], require APP_ROOT . "/app/lang/$l.php");
    }
    $s = $dict[$l][$key] ?? $dict['en'][$key] ?? $key;
    foreach ($vars as $k => $v) $s = str_replace('{' . $k . '}', (string)$v, $s);
    return $s;
}
/** Pick the Urdu field of a row when language is Urdu and it is filled. */
function tr(array $r, string $field): string
{
    if (is_rtl() && !empty($r[$field . '_ur'])) return (string)$r[$field . '_ur'];
    return (string)($r[$field] ?? '');
}
function s(string $key, string $default = ''): string
{
    // bilingual setting: key_ur when Urdu
    if (is_rtl()) { $u = setting($key . '_ur'); if ($u !== '') return $u; }
    return (string)setting($key, $default);
}

/* ---------------- Output ---------------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function nl2p(string $text): string
{
    // Body content written by admins: allow a small safe subset of HTML.
    $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><h2><h3><h4><a><blockquote><table><thead><tbody><tr><th><td>';
    $clean = strip_tags($text, $allowed);
    $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
    $clean = preg_replace('/href\s*=\s*([\'"])\s*(javascript|data|vbscript):.*?\1/is', 'href="#"', $clean);
    if ($clean === strip_tags($clean)) {
        $paras = preg_split("/\n\s*\n/", trim($clean));
        return implode('', array_map(fn($p) => '<p>' . nl2br($p) . '</p>', $paras));
    }
    return $clean;
}
function view(string $name, array $data = [], ?string $layout = 'public'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP_ROOT . "/app/views/$name.php";
    $content = ob_get_clean();
    if ($layout === null) { echo $content; return; }
    require APP_ROOT . "/app/views/layouts/$layout.php";
}
function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP_ROOT . "/app/views/partials/$name.php";
}
function json_out($data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function not_found(): never
{
    http_response_code(404);
    view('public/404', ['title' => t('not_found')]);
    exit;
}

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function old(string $k, $default = '') { return $_SESSION['old'][$k] ?? $default; }
function keep_old(): void { $_SESSION['old'] = $_POST; }
function clear_old(): void { unset($_SESSION['old']); }

function fmt_date(?string $d, bool $time = false): string
{
    if (!$d) return '';
    $ts = strtotime($d);
    return $ts ? date($time ? 'd M Y, g:i A' : 'd M Y', $ts) : '';
}
function days_until(?string $d): ?int
{
    if (!$d) return null;
    $today = new DateTime('today');
    $target = new DateTime(substr($d, 0, 10));
    return (int)$today->diff($target)->format('%r%a');
}
function money($n): string { return 'Rs ' . number_format((float)$n, 0); }
function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('~[^\pL\pN]+~u', '-', $s), '-'));
    $s = preg_replace('~[^-a-z0-9]+~', '', $s);
    return $s !== '' ? $s : 'post-' . substr(bin2hex(random_bytes(3)), 0, 6);
}
function ref_no(string $prefix): string { return $prefix . '-' . date('Y') . '-' . str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT); }

/* ---------------- Security ---------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void
{
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(419);
        exit('Session expired. Please go back, refresh the page and try again.');
    }
}
function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }

/** Simple fixed-window rate limit. Returns false when the limit is exceeded. */
function rate_limit(string $key, int $max, int $seconds): bool
{
    $k = substr($key . ':' . client_ip(), 0, 120);
    $r = row('SELECT hits, window_start FROM rate_limits WHERE k = ?', [$k]);
    if (!$r || strtotime($r['window_start']) < time() - $seconds) {
        q('REPLACE INTO rate_limits (k, hits, window_start) VALUES (?, 1, NOW())', [$k]);
        return true;
    }
    if ($r['hits'] >= $max) return false;
    q('UPDATE rate_limits SET hits = hits + 1 WHERE k = ?', [$k]);
    return true;
}

function input(string $k, string $default = ''): string
{
    $v = $_POST[$k] ?? $_GET[$k] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/** Save an uploaded file safely. Returns stored relative name or null. */
function save_upload(string $field, array $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'], int $maxMb = 5): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    $f = $_FILES[$field];
    if ($f['size'] > $maxMb * 1024 * 1024) return null;
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) return null;
    $mime = function_exists('mime_content_type') ? (string)mime_content_type($f['tmp_name']) : '';
    $okMime = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    if ($mime && !in_array($mime, $okMime, true)) return null;
    $dir = date('Y/m');
    $abs = APP_ROOT . '/uploads/' . $dir;
    if (!is_dir($abs)) mkdir($abs, 0755, true);
    $name = $dir . '/' . bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], APP_ROOT . '/uploads/' . $name)) return null;
    return $name;
}

/* ---------------- Messaging ---------------- */
/**
 * Send an SMS through a generic HTTP gateway configured in admin settings.
 * URL template example: https://api.example.pk/send?key=XXX&to={to}&text={text}
 */
function send_sms(string $to, string $text): bool
{
    $tpl = setting('sms_url');
    if (!$tpl || !$to) return false;
    $to = normalize_phone($to);
    $u = str_replace(['{to}', '{text}'], [rawurlencode($to), rawurlencode($text)], $tpl);
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
    $res = @file_get_contents($u, false, $ctx);
    return $res !== false;
}
function normalize_phone(string $p): string
{
    $d = preg_replace('/\D+/', '', $p);
    if (strpos($d, '00') === 0) $d = substr($d, 2);
    if (strpos($d, '0') === 0) $d = '92' . substr($d, 1);
    return $d;
}
function send_mail(string $to, string $subject, string $body): bool
{
    if (!$to) return false;
    $from = setting('mail_from', 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $headers = "From: " . setting('site_name', 'MMB Public Library') . " <$from>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}
function notify_member(int $memberId, string $type, string $title, string $body = '', string $link = ''): void
{
    insert('notifications', ['member_id' => $memberId, 'type' => $type, 'title' => $title, 'body' => $body, 'link' => $link]);
}

/* ---------------- Library helpers ---------------- */
function lang_label(string $code): string
{
    return ['ur' => t('lang_urdu'), 'en' => t('lang_english'), 'ar' => t('lang_arabic'), 'other' => t('lang_other')][$code] ?? $code;
}
function biblio_availability(int $biblioId): array
{
    $items = rows('SELECT status, due_date, shelf_location, call_number FROM items WHERE biblio_id = ?', [$biblioId]);
    $avail = 0; $nextDue = null; $hold = 0; $ref = 0;
    foreach ($items as $i) {
        if ($i['status'] === 'available') $avail++;
        elseif ($i['status'] === 'reference') $ref++;
        elseif ($i['status'] === 'on_hold') $hold++;
        elseif ($i['status'] === 'checked_out' && $i['due_date'] && (!$nextDue || $i['due_date'] < $nextDue)) $nextDue = $i['due_date'];
    }
    if ($avail > 0) $state = 'available';
    elseif ($ref > 0) $state = 'reference';
    elseif ($hold > 0 && !$nextDue) $state = 'on_hold';
    elseif ($nextDue) $state = 'checked_out';
    else $state = count($items) ? 'unavailable' : 'unknown';
    return ['state' => $state, 'available' => $avail, 'total' => count($items), 'next_due' => $nextDue];
}
function status_badge(array $av): string
{
    switch ($av['state']) {
        case 'available': return '<span class="badge badge-green"><span class="dot"></span>' . e(t('st_available')) . '</span>';
        case 'checked_out': return '<span class="badge badge-amber">' . e(t('st_checked_out_due', ['date' => fmt_date($av['next_due'])])) . '</span>';
        case 'on_hold': return '<span class="badge badge-blue">' . e(t('st_on_hold')) . '</span>';
        case 'reference': return '<span class="badge badge-slate">' . e(t('st_reference')) . '</span>';
        default: return '<span class="badge badge-slate">' . e(t('st_unavailable')) . '</span>';
    }
}
/** CSS-drawn book cover with title when no image is available. */
function book_cover(array $b, string $size = 'md'): string
{
    $palette = ['#0B4DA2', '#1E8A2E', '#7A3E12', '#5B2C83', '#0F5E6B', '#8A1E2E', '#2D3B55'];
    $color = $palette[crc32((string)$b['title']) % count($palette)];
    $img = $b['cover_url'] ?? '';
    $dir = preg_match('/\p{Arabic}/u', (string)$b['title']) ? ' dir="rtl"' : '';
    $html = '<div class="cover cover-' . $size . '" style="--c:' . $color . '">';
    if ($img) $html .= '<img src="' . e($img) . '" alt="" loading="lazy" onerror="this.remove()">';
    $html .= '<div class="cover-art"' . $dir . '><span class="cover-title">' . e(mb_strimwidth((string)$b['title'], 0, 60, '…')) . '</span>'
        . '<span class="cover-author">' . e(mb_strimwidth((string)($b['author'] ?? ''), 0, 40, '…')) . '</span>'
        . ($b['call_number'] ? '<span class="cover-call">' . e($b['call_number']) . '</span>' : '') . '</div></div>';
    return $html;
}
function is_open_now(): bool
{
    $days = setting('open_days', '1,2,3,4,5,6');
    $from = setting('open_from', '09:00');
    $to = setting('open_to', '17:00');
    $dow = (int)date('N');
    if (!in_array((string)$dow, explode(',', $days), true)) return false;
    $now = date('H:i');
    return $now >= $from && $now < $to;
}
function hours_text(): string
{
    return s('hours_text', 'Monday to Saturday, 9:00 AM to 5:00 PM');
}
function audit(string $action, string $detail = ''): void
{
    try { insert('audit_log', ['user_id' => $_SESSION['admin_id'] ?? null, 'action' => $action, 'detail' => mb_substr($detail, 0, 500), 'ip' => client_ip()]); } catch (Throwable $e) {}
}

/** Code 128-B barcode as inline SVG (for library cards). */
function code128_svg(string $text, int $height = 60): string
{
    static $P = ['212222','222122','222221','121223','121322','131222','122213','122312','132212','221213','221312','231212','112232','122132','122231','113222','123122','123221','223211','221132','221231','213212','223112','312131','311222','321122','321221','312212','322112','322211','212123','212321','232121','111323','131123','131321','112313','132113','132311','211313','231113','231311','112133','112331','132131','113123','113321','133121','313121','211331','231131','213113','213311','213131','311123','311321','331121','312113','312311','332111','314111','221411','431111','111224','111422','121124','121421','141122','141221','112214','112412','122114','122411','142112','142211','241211','221114','413111','241112','134111','111242','121142','121241','114212','124112','124211','411212','421112','421211','212141','214121','412121','111143','111341','131141','114113','114311','411113','411311','113141','114131','311141','411131','211412','211214','211232','2331112'];
    $text = preg_replace('/[^\x20-\x7E]/', '', $text);
    $codes = [104];
    $sum = 104;
    foreach (str_split($text) as $i => $ch) { $v = ord($ch) - 32; $codes[] = $v; $sum += $v * ($i + 1); }
    $codes[] = $sum % 103;
    $codes[] = 106;
    $x = 10; $bars = '';
    foreach ($codes as $c) {
        $pat = $P[$c];
        for ($i = 0, $n = strlen($pat); $i < $n; $i++) {
            $w = (int)$pat[$i];
            if ($i % 2 === 0) $bars .= '<rect x="' . $x . '" y="0" width="' . $w . '" height="' . $height . '"/>';
            $x += $w;
        }
    }
    $width = $x + 10;
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' ' . $height . '" preserveAspectRatio="none" role="img" aria-label="Barcode ' . e($text) . '"><rect width="100%" height="100%" fill="#fff"/><g fill="#000">' . $bars . '</g></svg>';
}
