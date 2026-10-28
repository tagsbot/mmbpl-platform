<?php
/**
 * Public REST API v1 for the MMBPL mobile app (and any future client).
 * Auth: "Authorization: Bearer <token>" from POST /api/v1/auth/login.
 */
declare(strict_types=1);

function api_dispatch(string $path): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept-Language');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Cache-Control: no-store');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
    $al = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2));
    if ($al === 'ur') $_SESSION['lang'] = 'ur';
    $body = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($body)) $body = $_POST;
    $GLOBALS['API_BODY'] = $body;
    $m = $_SERVER['REQUEST_METHOD'];
    $p = explode('/', trim($path, '/'));
    $r = $p[0] ?? '';

    $routes = [
        ['GET', 'home', 'api_home'], ['GET', 'library', 'api_library'],
        ['POST', 'auth/login', 'api_login'], ['POST', 'auth/logout', 'api_logout'], ['POST', 'auth/activate', 'api_activate'], ['POST', 'auth/activate/verify', 'api_activate_verify'],
        ['GET', 'catalog', 'api_catalog'], ['GET', 'books/{id}', 'api_book'], ['POST', 'books/{id}/reserve', 'api_reserve'], ['POST', 'books/{id}/list', 'api_toggle_list'],
        ['GET', 'me', 'api_me'], ['POST', 'me', 'api_me_update'], ['POST', 'me/push-token', 'api_push_token'],
        ['GET', 'loans', 'api_loans'], ['POST', 'loans/{id}/renew', 'api_renew'], ['POST', 'loans/renew-all', 'api_renew_all'],
        ['GET', 'holds', 'api_holds'], ['POST', 'holds/{id}/cancel', 'api_cancel_hold'], ['GET', 'history', 'api_history'], ['GET', 'list', 'api_list'],
        ['GET', 'notifications', 'api_notifications'], ['POST', 'notifications/read', 'api_notifications_read'],
        ['GET', 'events', 'api_events'], ['GET', 'events/{slug}', 'api_event'], ['POST', 'events/{slug}/register', 'api_event_register'],
        ['POST', 'suggestions', 'api_suggest'], ['GET', 'suggestions', 'api_my_suggestions'], ['POST', 'feedback', 'api_feedback'],
    ];
    foreach ($routes as [$rm, $pattern, $fn]) {
        if ($rm !== $m) continue;
        $re = '~^' . preg_replace('~\{\w+\}~', '([^/]+)', $pattern) . '$~';
        if (preg_match($re, implode('/', $p), $mm)) { array_shift($mm); $fn(...$mm); exit; }
    }
    json_out(['error' => 'not_found'], 404);
}
function body(string $k, string $d = ''): string { $v = $GLOBALS['API_BODY'][$k] ?? ($_GET[$k] ?? $d); return is_scalar($v) ? trim((string)$v) : $d; }
function api_auth(): array { $m = api_member(); if (!$m) json_out(['error' => 'unauthorized', 'message' => t('please_sign_in')], 401); return $m; }

function book_json(array $b, bool $full = false): array
{
    $av = $b['av'] ?? biblio_availability((int)$b['id']);
    $o = ['id' => (int)$b['id'], 'title' => $b['title'], 'author' => $b['author'], 'language' => $b['language'], 'year' => $b['pub_year'], 'call_number' => $b['call_number'],
        'cover_url' => $b['cover_url'], 'availability' => $av['state'], 'available' => $av['available'], 'copies' => $av['total'], 'next_due' => $av['next_due']];
    if ($full) $o += ['subtitle' => $b['subtitle'], 'isbn' => $b['isbn'], 'publisher' => $b['publisher'], 'edition' => $b['edition'], 'pages' => $b['pages'], 'summary' => $b['summary'],
        'subjects' => array_values(array_filter(array_map('trim', explode(';', (string)$b['subjects'])))), 'format' => $b['format']];
    return $o;
}
function member_json(array $m): array
{
    return ['id' => (int)$m['id'], 'cardnumber' => $m['cardnumber'], 'name' => member_display_name($m), 'firstname' => $m['firstname'], 'category' => $m['category'],
        'email' => $m['email'], 'phone' => $m['phone'], 'expiry_date' => $m['expiry_date'], 'status' => $m['status'], 'fines_due' => (float)$m['fines_due'], 'max_loans' => (int)$m['max_loans'],
        'lang' => $m['lang'], 'prefs' => ['due' => (bool)$m['notify_due'], 'holds' => (bool)$m['notify_holds'], 'events' => (bool)$m['notify_events'], 'push' => (bool)$m['notify_push'], 'sms' => (bool)$m['notify_sms'], 'whatsapp' => (bool)$m['notify_whatsapp']]];
}
function post_json(array $p, bool $full = false): array
{
    $o = ['id' => (int)$p['id'], 'type' => $p['type'], 'slug' => $p['slug'], 'title' => tr($p, 'title'), 'excerpt' => tr($p, 'excerpt'), 'image' => $p['image'] ? absolute_url('uploads/' . $p['image']) : null,
        'category' => $p['category'], 'start' => $p['event_start'], 'end' => $p['event_end'], 'venue' => $p['venue'], 'created_at' => $p['created_at'], 'url' => absolute_url('events/' . $p['slug'])];
    if ($full) $o += ['body_html' => nl2p(tr($p, 'body')), 'seats_left' => event_seats_left($p), 'registration_open' => (bool)$p['registration_open']];
    return $o;
}

function api_home(): void
{
    json_out(['new_arrivals' => array_map('book_json', book_list('new', 12)), 'popular' => array_map('book_json', book_list('popular', 12)),
        'events' => array_map('post_json', upcoming_events(5)), 'news' => array_map('post_json', rows('SELECT * FROM posts WHERE type="news" AND status="published" ORDER BY created_at DESC LIMIT 5')),
        'stats' => library_stats(), 'open_now' => is_open_now(), 'hours' => hours_text()]);
}
function api_library(): void
{
    $about = row('SELECT * FROM pages WHERE slug="about"');
    $rules = row('SELECT * FROM pages WHERE slug="rules"');
    json_out(['name' => s('site_name', 'Mian Muhammad Bakhsh Public Library'), 'short_name' => setting('short_name', 'MMB Public Library'), 'address' => s('address', 'Mirpur, Azad Jammu & Kashmir'),
        'phone' => setting('phone'), 'whatsapp' => setting('whatsapp'), 'email' => setting('email'), 'facebook' => setting('facebook_url'), 'map_query' => setting('map_query', 'Mirpur, Azad Kashmir'),
        'hours' => hours_text(), 'open_now' => is_open_now(), 'fees' => ['student' => s('fee_student'), 'general' => s('fee_general'), 'child' => s('fee_child')],
        'about_html' => $about ? nl2p(tr($about, 'body')) : '', 'rules_html' => $rules ? nl2p(tr($rules, 'body')) : '',
        'gallery' => array_map(fn($g) => ['image' => absolute_url('uploads/' . $g['image']), 'caption' => $g['caption']], rows('SELECT * FROM gallery ORDER BY sort, id DESC LIMIT 20')),
        'website' => absolute_url(''), 'membership_url' => absolute_url('membership')]);
}

function api_login(): void
{
    if (!rate_limit('api_login', 10, 600)) json_out(['error' => 'too_many', 'message' => t('err_too_many')], 429);
    $m = find_member_by_login(body('login'));
    if ($m && !$m['password_hash']) json_out(['error' => 'not_activated', 'message' => t('err_not_activated')], 403);
    if (!$m || !password_verify(body('password'), (string)$m['password_hash'])) json_out(['error' => 'invalid', 'message' => t('err_wrong_login')], 401);
    json_out(['token' => api_issue_token((int)$m['id'], body('device')), 'member' => member_json($m)]);
}
function api_logout(): void
{
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+([a-f0-9]{64})/i', $h, $mm)) q('DELETE FROM member_tokens WHERE token_hash=?', [hash('sha256', $mm[1])]);
    json_out(['ok' => true]);
}
/** Step 1: card + mobile + DOB. Returns a short-lived activation ticket. */
function api_activate(): void
{
    if (!rate_limit('api_activate', 8, 900)) json_out(['error' => 'too_many', 'message' => t('err_too_many')], 429);
    $m = row('SELECT * FROM members WHERE cardnumber=?', [body('cardnumber')]);
    $phone = preg_replace('/\D+/', '', body('phone'));
    $ok = $m && $m['phone'] && substr(preg_replace('/\D+/', '', $m['phone']), -10) === substr($phone, -10) && (!$m['dob'] || $m['dob'] === body('dob'));
    if (!$ok) json_out(['error' => 'no_match', 'message' => t('err_activation_match')], 400);
    if ($m['status'] === 'blocked') json_out(['error' => 'blocked', 'message' => t('err_member_blocked')], 403);
    $demo = create_otp((int)$m['id'], 'activate', $m['phone']);
    $needsOtp = (bool)setting('sms_url') || (bool)config('debug') || setting('show_demo_otp') === '1';
    $ticket = bin2hex(random_bytes(16));
    q('DELETE FROM act_tickets WHERE expires_at < NOW() OR member_id = ?', [$m['id']]);
    insert('act_tickets', ['ticket_hash' => hash('sha256', $ticket), 'member_id' => $m['id'], 'verified' => $needsOtp ? 0 : 1, 'expires_at' => date('Y-m-d H:i:s', time() + 900)]);
    json_out(['ticket' => $ticket, 'otp_required' => $needsOtp, 'phone_hint' => substr(preg_replace('/\D/', '', $m['phone']), -4), 'demo_code' => $needsOtp && $demo && (config('debug') || setting('show_demo_otp') === '1') ? $demo : null]);
}
/** Step 2: OTP (when required) + new password → token. */
function api_activate_verify(): void
{
    $th = hash('sha256', body('ticket'));
    $t = row('SELECT * FROM act_tickets WHERE ticket_hash=? AND expires_at > NOW()', [$th]);
    if (!$t) json_out(['error' => 'expired', 'message' => t('err_otp')], 400);
    if (!$t['verified']) {
        if (!verify_otp((int)$t['member_id'], 'activate', preg_replace('/\D/', '', body('code')))) json_out(['error' => 'otp', 'message' => t('err_otp')], 400);
        q('UPDATE act_tickets SET verified=1 WHERE ticket_hash=?', [$th]);
    }
    $pw = body('password');
    if (mb_strlen($pw) < 8) json_out(['error' => 'weak', 'message' => t('err_password_short')], 400);
    q('UPDATE members SET password_hash=?, activated_at=COALESCE(activated_at, NOW()) WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $t['member_id']]);
    q('DELETE FROM act_tickets WHERE ticket_hash=?', [$th]);
    $m = row('SELECT * FROM members WHERE id=?', [$t['member_id']]);
    json_out(['token' => api_issue_token((int)$m['id'], body('device')), 'member' => member_json($m)]);
}

function api_catalog(): void
{
    $f = ['q' => body('q'), 'field' => body('field', 'all'), 'sort' => body('sort', 'relevance'), 'lang' => array_filter(explode(',', body('lang'))), 'format' => array_filter(explode(',', body('format'))),
        'avail' => body('avail'), 'new' => body('new'), 'year_from' => body('year_from'), 'year_to' => body('year_to'), 'subject_tag' => body('subject'), 'author_tag' => body('author'), 'page' => body('page', '1')];
    [$list, $total] = catalog_search($f, 20);
    json_out(['total' => $total, 'page' => (int)$f['page'], 'per_page' => 20, 'results' => array_map(fn($b) => book_json($b) + ['shelf' => $b['shelf']], $list)]);
}
function api_book(string $id): void
{
    $b = row('SELECT * FROM biblios WHERE id=?', [(int)$id]);
    if (!$b) json_out(['error' => 'not_found'], 404);
    $items = array_map(fn($i) => ['barcode' => $i['barcode'], 'shelf' => $i['shelf_location'], 'call_number' => $i['call_number'] ?: $b['call_number'], 'status' => $i['status'], 'due_date' => $i['due_date']], rows('SELECT * FROM items WHERE biblio_id=?', [$b['id']]));
    $more = $b['author'] ? rows('SELECT * FROM biblios WHERE author=? AND id<>? LIMIT 8', [$b['author'], $b['id']]) : [];
    $m = api_member();
    $state = null;
    if ($m) {
        $h = row('SELECT status FROM holds WHERE member_id=? AND biblio_id=? AND status IN ("requested","waiting","ready")', [$m['id'], $b['id']]);
        $state = ['reserved' => $h ? $h['status'] : null, 'in_list' => (bool)val('SELECT 1 FROM wishlist WHERE member_id=? AND biblio_id=?', [$m['id'], $b['id']])];
    }
    json_out(book_json($b, true) + ['items' => $items, 'more_by_author' => array_map('book_json', $more), 'me' => $state, 'url' => absolute_url('book/' . $b['id'])]);
}
function api_reserve(string $id): void { $m = api_auth(); [$ok, $k] = request_reserve($m, (int)$id); json_out(['ok' => $ok, 'message' => t($k), 'code' => $k], $ok ? 200 : 400); }
function api_toggle_list(string $id): void
{
    $m = api_auth();
    if (val('SELECT 1 FROM wishlist WHERE member_id=? AND biblio_id=?', [$m['id'], (int)$id])) { q('DELETE FROM wishlist WHERE member_id=? AND biblio_id=?', [$m['id'], (int)$id]); json_out(['in_list' => false]); }
    if (!val('SELECT id FROM biblios WHERE id=?', [(int)$id])) json_out(['error' => 'not_found'], 404);
    insert('wishlist', ['member_id' => $m['id'], 'biblio_id' => (int)$id]);
    json_out(['in_list' => true]);
}

function api_me(): void
{
    $m = api_auth();
    $id = (int)$m['id'];
    json_out(['member' => member_json($m), 'counts' => ['loans' => (int)val('SELECT COUNT(*) FROM loans WHERE member_id=?', [$id]), 'due_soon' => (int)val('SELECT COUNT(*) FROM loans WHERE member_id=? AND due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)', [$id]),
        'holds' => (int)val('SELECT COUNT(*) FROM holds WHERE member_id=? AND status IN ("requested","waiting","ready")', [$id]), 'ready' => (int)val('SELECT COUNT(*) FROM holds WHERE member_id=? AND status="ready"', [$id]), 'unread' => unread_count($id)],
        'synced_at' => setting('agent_last_seen'), 'agent_online' => agent_online()]);
}
function api_me_update(): void
{
    $m = api_auth();
    $data = [];
    if (in_array(body('lang'), ['en', 'ur'], true)) $data['lang'] = body('lang');
    foreach (['due', 'holds', 'events', 'push', 'sms', 'whatsapp'] as $k) if (isset($GLOBALS['API_BODY']['prefs'][$k])) $data['notify_' . $k] = $GLOBALS['API_BODY']['prefs'][$k] ? 1 : 0;
    if ($data) update('members', $data, 'id=?', [$m['id']]);
    if (body('new_password') !== '') {
        if (!password_verify(body('current_password'), (string)$m['password_hash'])) json_out(['error' => 'current_password', 'message' => t('err_current_password')], 400);
        if (mb_strlen(body('new_password')) < 8) json_out(['error' => 'weak', 'message' => t('err_password_short')], 400);
        q('UPDATE members SET password_hash=? WHERE id=?', [password_hash(body('new_password'), PASSWORD_DEFAULT), $m['id']]);
    }
    json_out(['ok' => true, 'member' => member_json(row('SELECT * FROM members WHERE id=?', [$m['id']]))]);
}
function api_push_token(): void { $m = api_auth(); q('UPDATE members SET push_token=? WHERE id=?', [mb_substr(body('token'), 0, 255), $m['id']]); json_out(['ok' => true]); }

function api_loans(): void
{
    $m = api_auth();
    json_out(['loans' => array_map(fn($l) => ['id' => (int)$l['id'], 'book' => ['id' => (int)$l['biblio_id'], 'title' => $l['title'], 'author' => $l['author'], 'cover_url' => $l['cover_url'], 'call_number' => $l['call_number']],
        'barcode' => $l['barcode'], 'issued_at' => $l['issued_at'], 'due_date' => $l['due_date'], 'days_left' => days_until($l['due_date']), 'renewals' => (int)$l['renewals'],
        'renewable' => (bool)$l['renewable'], 'not_renewable_reason' => $l['not_renewable_reason'], 'renew_pending' => (bool)$l['renew_pending']], member_loans((int)$m['id']))]);
}
function api_renew(string $id): void { $m = api_auth(); [$ok, $k] = request_renew($m, (int)$id); json_out(['ok' => $ok, 'message' => t($k), 'code' => $k], $ok ? 200 : 400); }
function api_renew_all(): void
{
    $m = api_auth(); $n = 0; $f = 0;
    foreach (rows('SELECT id FROM loans WHERE member_id=?', [$m['id']]) as $l) { [$ok] = request_renew($m, (int)$l['id']); $ok ? $n++ : $f++; }
    json_out(['ok' => $n > 0, 'requested' => $n, 'failed' => $f, 'message' => t('renew_all_result', ['n' => $n, 'f' => $f])]);
}
function api_holds(): void
{
    $m = api_auth();
    json_out(['holds' => array_map(fn($h) => ['id' => (int)$h['id'], 'book' => ['id' => (int)$h['biblio_id'], 'title' => $h['title'], 'author' => $h['author'], 'cover_url' => $h['cover_url']],
        'status' => $h['status'], 'queue_position' => $h['queue_position'] ? (int)$h['queue_position'] : null, 'pickup_by' => $h['pickup_by'], 'created_at' => $h['created_at']], member_holds((int)$m['id']))]);
}
function api_cancel_hold(string $id): void { $m = api_auth(); [$ok, $k] = request_cancel_hold($m, (int)$id); json_out(['ok' => $ok, 'message' => t($k)], $ok ? 200 : 400); }
function api_history(): void
{
    $m = api_auth();
    json_out(['history' => array_map(fn($h) => ['book' => ['id' => (int)$h['biblio_id'], 'title' => $h['title'], 'author' => $h['author'], 'cover_url' => $h['cover_url']], 'issued_at' => $h['issued_at'], 'returned_at' => $h['returned_at']], member_history((int)$m['id'], 200))]);
}
function api_list(): void
{
    $m = api_auth();
    json_out(['list' => array_map('book_json', rows('SELECT b.* FROM wishlist w JOIN biblios b ON b.id=w.biblio_id WHERE w.member_id=? ORDER BY w.created_at DESC', [$m['id']]))]);
}
function api_notifications(): void
{
    $m = api_auth();
    json_out(['unread' => unread_count((int)$m['id']), 'notifications' => array_map(fn($n) => ['id' => (int)$n['id'], 'type' => $n['type'], 'title' => $n['title'], 'body' => $n['body'], 'link' => $n['link'], 'read' => (bool)$n['is_read'], 'created_at' => $n['created_at']], member_notifications((int)$m['id'], 100))]);
}
function api_notifications_read(): void { $m = api_auth(); mark_all_read((int)$m['id']); json_out(['ok' => true]); }

function api_events(): void
{
    $type = body('type', 'event');
    $list = $type === 'news' ? rows('SELECT * FROM posts WHERE type="news" AND status="published" ORDER BY created_at DESC LIMIT 30')
        : ($type === 'past' ? rows('SELECT * FROM posts WHERE type="event" AND status="published" AND COALESCE(event_end, event_start) < NOW() ORDER BY event_start DESC LIMIT 30') : upcoming_events(30));
    json_out(['items' => array_map('post_json', $list)]);
}
function api_event(string $slug): void
{
    $e = row('SELECT * FROM posts WHERE slug=? AND status="published"', [$slug]);
    if (!$e) json_out(['error' => 'not_found'], 404);
    json_out(post_json($e, true));
}
function api_event_register(string $slug): void
{
    $e = row('SELECT * FROM posts WHERE slug=? AND type="event" AND status="published"', [$slug]);
    if (!$e) json_out(['error' => 'not_found'], 404);
    if (!$e['registration_open'] || ($e['event_start'] && strtotime($e['event_start']) < time())) json_out(['error' => 'closed', 'message' => t('err_registration_closed')], 400);
    $m = api_member();
    $seats = max(1, min(5, (int)body('seats', '1')));
    $left = event_seats_left($e);
    if ($left !== null && $left < $seats) json_out(['error' => 'full', 'message' => t('err_no_seats')], 400);
    $name = body('name') ?: ($m ? member_display_name($m) : '');
    $phone = body('phone') ?: ($m['phone'] ?? '');
    if (!$name || !$phone) json_out(['error' => 'required', 'message' => t('err_required_fields')], 400);
    $reg = 'EVT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    insert('event_registrations', ['post_id' => $e['id'], 'member_id' => $m['id'] ?? null, 'reg_no' => $reg, 'name' => $name, 'phone' => $phone, 'seats' => $seats]);
    if ($m) notify_member((int)$m['id'], 'event', t('notif_event_registered', ['event' => $e['title']]), t('your_reg_no') . ': ' . $reg, 'events/' . $slug);
    json_out(['ok' => true, 'reg_no' => $reg, 'event' => post_json($e), 'seats' => $seats, 'name' => $name]);
}

function api_suggest(): void
{
    if (!rate_limit('api_suggest', 10, 3600)) json_out(['error' => 'too_many'], 429);
    if (body('title') === '') json_out(['error' => 'required', 'message' => t('err_required_fields')], 400);
    $m = api_member();
    insert('suggestions', ['member_id' => $m['id'] ?? null, 'title' => mb_substr(body('title'), 0, 300), 'author' => mb_substr(body('author'), 0, 200), 'isbn' => mb_substr(body('isbn'), 0, 40), 'language' => mb_substr(body('language'), 0, 20),
        'reason' => mb_substr(body('reason'), 0, 500), 'name' => $m ? member_display_name($m) : body('name'), 'phone' => $m['phone'] ?? body('phone'), 'email' => $m['email'] ?? body('email')]);
    json_out(['ok' => true, 'message' => t('suggest_thanks')]);
}
function api_my_suggestions(): void
{
    $m = api_auth();
    json_out(['items' => rows('SELECT id, title, author, status, created_at FROM suggestions WHERE member_id=? ORDER BY id DESC', [$m['id']])]);
}
function api_feedback(): void
{
    if (!rate_limit('api_feedback', 5, 3600)) json_out(['error' => 'too_many'], 429);
    $m = api_member();
    $name = body('name') ?: ($m ? member_display_name($m) : '');
    if (!$name || body('message') === '') json_out(['error' => 'required', 'message' => t('err_required_fields')], 400);
    $ref = ref_no('FB');
    $type = in_array(body('type'), ['feedback', 'complaint', 'suggestion', 'question'], true) ? body('type') : 'feedback';
    insert('feedback', ['ref_no' => $ref, 'type' => $type, 'name' => $name, 'phone' => body('phone') ?: ($m['phone'] ?? ''), 'email' => body('email') ?: ($m['email'] ?? ''), 'message' => mb_substr(body('message'), 0, 5000)]);
    json_out(['ok' => true, 'ref_no' => $ref]);
}
