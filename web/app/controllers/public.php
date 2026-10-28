<?php
declare(strict_types=1);

function public_home(): void
{
    view('public/home', [
        'title' => '',
        'newArrivals' => book_list('new', 10),
        'popular' => book_list('popular', 8),
        'stats' => library_stats(),
        'posts' => latest_posts(3),
    ]);
}

function public_set_lang(string $code): void
{
    $code = in_array($code, ['en', 'ur'], true) ? $code : 'en';
    $_SESSION['lang'] = $code;
    setcookie('lang', $code, ['expires' => time() + 86400 * 365, 'path' => base_path() ?: '/', 'samesite' => 'Lax']);
    if ($m = member()) q('UPDATE members SET lang=? WHERE id=?', [$code, $m['id']]);
    $back = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($back && parse_url($back, PHP_URL_HOST) === $host) redirect($back);
    redirect('/');
}

function public_catalog(): void
{
    $f = [
        'q' => input('q'), 'field' => input('field', 'all'), 'sort' => input('sort', 'relevance'),
        'lang' => (array)($_GET['lang'] ?? []), 'format' => (array)($_GET['format'] ?? []),
        'avail' => input('avail'), 'new' => input('new'), 'year_from' => input('year_from'), 'year_to' => input('year_to'),
        'subject_tag' => input('subject'), 'author_tag' => input('author'), 'page' => input('page', '1'),
    ];
    [$results, $total] = catalog_search($f, 12);
    view('public/catalog', ['title' => t('nav_catalog'), 'f' => $f, 'results' => $results, 'total' => $total, 'perPage' => 12, 'viewMode' => input('view', 'list')]);
}

function public_book(string $id): void
{
    $b = row('SELECT * FROM biblios WHERE id = ?', [(int)$id]);
    if (!$b) not_found();
    $b['av'] = biblio_availability((int)$b['id']);
    $items = rows('SELECT * FROM items WHERE biblio_id = ? ORDER BY status = "available" DESC, barcode', [$b['id']]);
    $byAuthor = $b['author'] ? rows('SELECT * FROM biblios WHERE author = ? AND id <> ? ORDER BY popularity DESC LIMIT 6', [$b['author'], $b['id']]) : [];
    $firstSubject = trim(explode(';', (string)$b['subjects'])[0] ?? '');
    $similar = $firstSubject !== '' ? rows('SELECT * FROM biblios WHERE subjects LIKE ? AND id <> ? ORDER BY popularity DESC LIMIT 6', ['%' . $firstSubject . '%', $b['id']]) : [];
    foreach ($byAuthor as &$x) $x['av'] = biblio_availability((int)$x['id']);
    unset($x);
    foreach ($similar as &$x) $x['av'] = biblio_availability((int)$x['id']);
    unset($x);
    $m = member();
    $myHold = $m ? row('SELECT * FROM holds WHERE member_id=? AND biblio_id=? AND status IN ("requested","waiting","ready")', [$m['id'], $b['id']]) : null;
    $inList = $m ? (bool)val('SELECT 1 FROM wishlist WHERE member_id=? AND biblio_id=?', [$m['id'], $b['id']]) : false;
    view('public/book', ['title' => $b['title'], 'b' => $b, 'items' => $items, 'byAuthor' => $byAuthor, 'similar' => $similar, 'myHold' => $myHold, 'inList' => $inList]);
}

/* ---------- Sign in ---------- */
function public_signin(): void
{
    if (member()) redirect('account');
    view('public/signin', ['title' => t('sign_in'), 'tab' => input('tab', 'signin')]);
}
function public_signin_post(): void
{
    if (!rate_limit('signin', 10, 600)) { flash('error', t('err_too_many')); redirect('signin'); }
    $login = input('login');
    $pass = $_POST['password'] ?? '';
    $m = find_member_by_login($login);
    if (!$m || !$m['password_hash'] || !password_verify((string)$pass, $m['password_hash'])) {
        if ($m && !$m['password_hash']) { flash('info', t('err_not_activated')); redirect('activate'); }
        $_SESSION['old'] = ['login' => $login];
        flash('error', t('err_wrong_login'));
        redirect('signin');
    }
    if (password_needs_rehash($m['password_hash'], PASSWORD_DEFAULT)) q('UPDATE members SET password_hash=? WHERE id=?', [password_hash((string)$pass, PASSWORD_DEFAULT), $m['id']]);
    clear_old();
    member_login($m);
    if ($m['lang'] && $m['lang'] !== lang()) $_SESSION['lang'] = $m['lang'];
    $to = $_SESSION['after_login'] ?? url('account');
    unset($_SESSION['after_login']);
    redirect($to);
}
function public_otp_request(): void
{
    if (!rate_limit('otp', 5, 900)) { flash('error', t('err_too_many')); redirect('signin'); }
    $m = find_member_by_login(input('login'));
    if (!$m || !$m['phone']) { flash('error', t('err_no_member_phone')); redirect('signin'); }
    if (!setting('sms_url')) { flash('error', t('err_sms_not_configured')); redirect('signin'); }
    create_otp((int)$m['id'], 'login', $m['phone']);
    $_SESSION['otp_member'] = (int)$m['id'];
    $_SESSION['otp_purpose'] = 'login';
    redirect('signin/otp');
}
function public_otp_form(): void
{
    if (empty($_SESSION['otp_member'])) redirect('signin');
    $m = row('SELECT phone FROM members WHERE id=?', [$_SESSION['otp_member']]);
    view('public/otp', ['title' => t('verify_code'), 'phoneMasked' => '•••• ' . substr(preg_replace('/\D/', '', (string)($m['phone'] ?? '')), -4), 'action' => url('signin/otp'), 'demoCode' => $_SESSION['demo_otp'] ?? null]);
}
function public_otp_verify(): void
{
    $id = (int)($_SESSION['otp_member'] ?? 0);
    $code = preg_replace('/\D/', '', implode('', (array)($_POST['code'] ?? [])) ?: input('code_full'));
    if (!$id || !verify_otp($id, 'login', $code)) { flash('error', t('err_otp')); redirect('signin/otp'); }
    unset($_SESSION['otp_member'], $_SESSION['otp_purpose'], $_SESSION['demo_otp']);
    member_login(row('SELECT * FROM members WHERE id=?', [$id]));
    redirect('account');
}

/* ---------- Activate online account (existing Koha members) ---------- */
function public_activate(): void
{
    view('public/activate', ['title' => t('activate_account')]);
}
function public_activate_post(): void
{
    if (!rate_limit('activate', 8, 900)) { flash('error', t('err_too_many')); redirect('activate'); }
    keep_old();
    $card = input('cardnumber');
    $phone = preg_replace('/\D+/', '', input('phone'));
    $dob = input('dob');
    $m = row('SELECT * FROM members WHERE cardnumber = ?', [$card]);
    $phoneOk = $m && $m['phone'] && substr(preg_replace('/\D+/', '', $m['phone']), -10) === substr($phone, -10);
    $dobOk = $m && (!$m['dob'] || $m['dob'] === $dob);
    if (!$m || !$phoneOk || !$dobOk) { flash('error', t('err_activation_match')); redirect('activate'); }
    if ($m['status'] === 'blocked') { flash('error', t('err_member_blocked')); redirect('activate'); }
    $demo = create_otp((int)$m['id'], 'activate', $m['phone']);
    $_SESSION['act_member'] = (int)$m['id'];
    $_SESSION['demo_otp'] = $demo && (config('debug') || setting('show_demo_otp') === '1') ? $demo : null;
    if ($demo && !$_SESSION['demo_otp'] && !setting('sms_url')) {
        // No SMS gateway yet: matching card + phone + date of birth is the verification.
        $_SESSION['act_verified'] = (int)$m['id'];
        redirect('activate/verify?step=password');
    }
    clear_old();
    redirect('activate/verify');
}
function public_activate_verify(): void
{
    if (empty($_SESSION['act_member'])) redirect('activate');
    $m = row('SELECT phone FROM members WHERE id=?', [$_SESSION['act_member']]);
    $step = (($_SESSION['act_verified'] ?? 0) === $_SESSION['act_member']) ? 'password' : 'otp';
    view('public/activate_verify', ['title' => t('activate_account'), 'step' => $step, 'phoneMasked' => '•••• ' . substr(preg_replace('/\D/', '', (string)$m['phone']), -4), 'demoCode' => $_SESSION['demo_otp'] ?? null]);
}
function public_activate_verify_post(): void
{
    $id = (int)($_SESSION['act_member'] ?? 0);
    if (!$id) redirect('activate');
    if (input('step') === 'otp') {
        $code = preg_replace('/\D/', '', implode('', (array)($_POST['code'] ?? [])));
        if (!verify_otp($id, 'activate', $code)) { flash('error', t('err_otp')); redirect('activate/verify'); }
        $_SESSION['act_verified'] = $id;
        redirect('activate/verify');
    }
    if (($_SESSION['act_verified'] ?? 0) !== $id) redirect('activate/verify');
    $p1 = (string)($_POST['password'] ?? '');
    $p2 = (string)($_POST['password2'] ?? '');
    if (mb_strlen($p1) < 8) { flash('error', t('err_password_short')); redirect('activate/verify'); }
    if ($p1 !== $p2) { flash('error', t('err_password_match')); redirect('activate/verify'); }
    q('UPDATE members SET password_hash=?, activated_at=COALESCE(activated_at, NOW()) WHERE id=?', [password_hash($p1, PASSWORD_DEFAULT), $id]);
    unset($_SESSION['act_member'], $_SESSION['act_verified'], $_SESSION['demo_otp']);
    member_login(row('SELECT * FROM members WHERE id=?', [$id]));
    flash('success', t('account_activated'));
    redirect('account');
}
function public_forgot(): void { redirect('activate'); }
function public_forgot_post(): void { redirect('activate'); }
function public_signout(): void { member_logout(); flash('success', t('signed_out')); redirect('/'); }

/* ---------- Membership application ---------- */
function public_membership(): void
{
    view('public/membership', ['title' => t('membership_application'), 'scripts' => []]);
}
function public_membership_post(): void
{
    if (!rate_limit('apply', 5, 3600)) { flash('error', t('err_too_many')); redirect('membership'); }
    keep_old();
    $req = ['full_name', 'phone', 'membership_type', 'dob'];
    foreach ($req as $r) if (input($r) === '') { flash('error', t('err_required_fields')); redirect('membership'); }
    if (empty($_POST['declaration'])) { flash('error', t('err_declaration')); redirect('membership'); }
    $type = input('membership_type');
    if (!in_array($type, ['student', 'general', 'child'], true)) $type = 'general';
    $photo = save_upload('photo', ['jpg', 'jpeg', 'png', 'webp'], 3);
    $doc = save_upload('document', ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 5);
    $ref = ref_no('APP');
    insert('applications', [
        'ref_no' => $ref, 'full_name' => input('full_name'), 'guardian_name' => input('guardian_name'), 'cnic' => input('cnic'),
        'dob' => input('dob') ?: null, 'gender' => input('gender'), 'phone' => input('phone'), 'email' => input('email'),
        'address' => input('address'), 'city' => input('city'), 'membership_type' => $type, 'photo' => $photo, 'document' => $doc,
    ]);
    clear_old();
    $_SESSION['last_app'] = $ref;
    redirect('membership/done/' . $ref);
}
function public_membership_done(string $ref): void
{
    if (($_SESSION['last_app'] ?? '') !== $ref) redirect('membership');
    view('public/membership_done', ['title' => t('application_submitted'), 'ref' => $ref]);
}

/* ---------- Suggest & donate ---------- */
function public_suggest(): void
{
    $m = member();
    $recent = rows('SELECT title, author, status, created_at FROM suggestions ORDER BY id DESC LIMIT 8');
    $mine = $m ? rows('SELECT * FROM suggestions WHERE member_id=? ORDER BY id DESC', [$m['id']]) : [];
    view('public/suggest', ['title' => t('suggest_donate'), 'tab' => input('tab', 'suggest'), 'recent' => $recent, 'mine' => $mine, 'm' => $m, 'thanks' => input('thanks')]);
}
function public_suggest_post(): void
{
    if (!rate_limit('suggest', 10, 3600)) { flash('error', t('err_too_many')); redirect('suggest'); }
    if (input('title') === '') { flash('error', t('err_required_fields')); redirect('suggest'); }
    $m = member();
    insert('suggestions', [
        'member_id' => $m['id'] ?? null, 'title' => input('title'), 'author' => input('author'), 'isbn' => input('isbn'),
        'language' => input('language'), 'reason' => mb_substr(input('reason'), 0, 500),
        'name' => $m ? member_display_name($m) : input('name'), 'phone' => $m['phone'] ?? input('phone'), 'email' => $m['email'] ?? input('email'),
    ]);
    redirect('suggest?thanks=suggest');
}
function public_donate_post(): void
{
    if (!rate_limit('donate', 5, 3600)) { flash('error', t('err_too_many')); redirect('suggest?tab=donate'); }
    if (input('name') === '' || input('phone') === '') { flash('error', t('err_required_fields')); redirect('suggest?tab=donate'); }
    insert('donations', ['name' => input('name'), 'phone' => input('phone'), 'book_type' => input('book_type'), 'quantity' => input('quantity'), 'dropoff_date' => input('dropoff_date') ?: null, 'note' => mb_substr(input('note'), 0, 500)]);
    redirect('suggest?tab=donate&thanks=donate');
}

/* ---------- News & events ---------- */
function public_events(): void
{
    $tab = input('tab', 'upcoming');
    $cat = input('cat');
    $month = input('month');
    $where = 'status="published"';
    $p = [];
    if ($tab === 'news') $where .= ' AND type="news"';
    elseif ($tab === 'past') $where .= ' AND type="event" AND COALESCE(event_end, event_start) < NOW()';
    else $where .= ' AND type="event" AND COALESCE(event_end, event_start) >= NOW()';
    if ($cat) { $where .= ' AND category=?'; $p[] = $cat; }
    if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) { $where .= ' AND DATE_FORMAT(COALESCE(event_start, created_at), "%Y-%m")=?'; $p[] = $month; }
    $order = $tab === 'upcoming' ? 'event_start ASC' : 'COALESCE(event_start, created_at) DESC';
    $page = max(1, (int)input('page', '1'));
    $total = (int)val("SELECT COUNT(*) FROM posts WHERE $where", $p);
    $list = rows("SELECT * FROM posts WHERE $where ORDER BY featured DESC, $order LIMIT 9 OFFSET " . (($page - 1) * 9), $p);
    $featured = $tab === 'upcoming' && $page === 1 && $list ? array_shift($list) : null;
    $eventDays = array_column(rows('SELECT DISTINCT DATE(event_start) d FROM posts WHERE type="event" AND status="published" AND event_start BETWEEN DATE_FORMAT(NOW(),"%Y-%m-01") AND LAST_DAY(NOW())'), 'd');
    view('public/events', ['title' => t('nav_events'), 'tab' => $tab, 'list' => $list, 'featured' => $featured, 'total' => $total, 'page' => $page, 'cat' => $cat, 'month' => $month, 'eventDays' => $eventDays]);
}
function public_event(string $slug): void
{
    $e = row('SELECT * FROM posts WHERE slug=? AND status="published"', [$slug]);
    if (!$e) not_found();
    $related = rows('SELECT * FROM posts WHERE status="published" AND id<>? AND type=? ORDER BY created_at DESC LIMIT 3', [$e['id'], $e['type']]);
    $reg = isset($_SESSION['last_reg']) ? row('SELECT * FROM event_registrations WHERE reg_no=? AND post_id=?', [$_SESSION['last_reg'], $e['id']]) : null;
    view('public/event', ['title' => tr($e, 'title'), 'e' => $e, 'related' => $related, 'seatsLeft' => event_seats_left($e), 'reg' => $reg, 'm' => member(), 'metaDesc' => tr($e, 'excerpt')]);
}
function public_event_register(string $slug): void
{
    $e = row('SELECT * FROM posts WHERE slug=? AND type="event" AND status="published"', [$slug]);
    if (!$e) not_found();
    if (!rate_limit('evreg', 10, 3600)) { flash('error', t('err_too_many')); redirect('events/' . $slug); }
    if (!$e['registration_open'] || ($e['event_start'] && strtotime($e['event_start']) < time())) { flash('error', t('err_registration_closed')); redirect('events/' . $slug); }
    $seats = max(1, min(5, (int)input('seats', '1')));
    $left = event_seats_left($e);
    if ($left !== null && $left < $seats) { flash('error', t('err_no_seats')); redirect('events/' . $slug); }
    $m = member();
    $name = input('name') ?: ($m ? member_display_name($m) : '');
    $phone = input('phone') ?: ($m['phone'] ?? '');
    if ($name === '' || $phone === '') { flash('error', t('err_required_fields')); redirect('events/' . $slug); }
    $reg = 'EVT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    insert('event_registrations', ['post_id' => $e['id'], 'member_id' => $m['id'] ?? null, 'reg_no' => $reg, 'name' => $name, 'phone' => $phone, 'seats' => $seats]);
    if ($m) notify_member((int)$m['id'], 'event', t('notif_event_registered', ['event' => $e['title']]), t('your_reg_no') . ': ' . $reg, 'events/' . $slug);
    $_SESSION['last_reg'] = $reg;
    flash('success', t('registered_success'));
    redirect('events/' . $slug . '#registration');
}

/* ---------- Static-ish pages ---------- */
function public_about(): void
{
    view('public/about', ['title' => t('nav_about'), 'about' => row('SELECT * FROM pages WHERE slug="about"'), 'rules' => row('SELECT * FROM pages WHERE slug="rules"'), 'gallery' => rows('SELECT * FROM gallery ORDER BY sort, id DESC LIMIT 12')]);
}
function public_contact(): void
{
    view('public/contact', ['title' => t('nav_contact'), 'done' => $_SESSION['fb_ref'] ?? null]);
    unset($_SESSION['fb_ref']);
}
function public_contact_post(): void
{
    if (!rate_limit('contact', 5, 3600)) { flash('error', t('err_too_many')); redirect('contact'); }
    keep_old();
    if (input('name') === '' || input('message') === '' || (input('phone') === '' && input('email') === '')) { flash('error', t('err_required_fields')); redirect('contact'); }
    $type = in_array(input('type'), ['feedback', 'complaint', 'suggestion', 'question'], true) ? input('type') : 'feedback';
    $ref = ref_no('FB');
    insert('feedback', ['ref_no' => $ref, 'type' => $type, 'name' => input('name'), 'phone' => input('phone'), 'email' => input('email'), 'message' => mb_substr(input('message'), 0, 5000), 'attachment' => save_upload('attachment')]);
    if (setting('email')) send_mail(setting('email'), "[$ref] New $type from website", input('name') . "\n" . input('phone') . ' ' . input('email') . "\n\n" . input('message'));
    clear_old();
    $_SESSION['fb_ref'] = $ref;
    redirect('contact#sent');
}
function public_exam_corner(): void
{
    $exam = input('exam');
    $p = [];
    $w = '1=1';
    if ($exam) { $w = 'exam=?'; $p[] = $exam; }
    $books = rows("SELECT * FROM biblios WHERE subjects LIKE '%Competitive%' OR subjects LIKE '%CSS%' OR subjects LIKE '%PPSC%' OR subjects LIKE '%exam%' ORDER BY popularity DESC LIMIT 8");
    foreach ($books as &$b) $b['av'] = biblio_availability((int)$b['id']);
    view('public/exam', ['title' => t('exam_corner'), 'exam' => $exam, 'intro' => row('SELECT * FROM pages WHERE slug="exam-corner"'),
        'guides' => rows("SELECT * FROM exam_resources WHERE kind IN ('guide','link') AND $w ORDER BY id DESC LIMIT 12", $p),
        'jobs' => rows("SELECT * FROM exam_resources WHERE kind='job' AND (deadline IS NULL OR deadline >= CURDATE()) AND $w ORDER BY deadline ASC LIMIT 10", $p),
        'books' => $books]);
}
function public_archive(): void
{
    $w = ['1=1']; $p = [];
    if ($q = input('q')) { $w[] = '(title LIKE ? OR title_ur LIKE ? OR place LIKE ? OR description LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%", "%$q%"); }
    if ($type = input('type')) { $w[] = 'type=?'; $p[] = $type; }
    if (($dec = input('decade')) && ctype_digit($dec)) { $w[] = 'CAST(year AS UNSIGNED) BETWEEN ? AND ?'; array_push($p, (int)$dec, (int)$dec + 9); }
    $items = rows('SELECT * FROM archive_items WHERE ' . implode(' AND ', $w) . ' ORDER BY year ASC, id DESC LIMIT 200', $p);
    view('public/archive', ['title' => t('local_history'), 'items' => $items]);
}
function public_page(string $slug): void
{
    $pg = row('SELECT * FROM pages WHERE slug=?', [$slug]);
    if (!$pg) not_found();
    view('public/page', ['title' => tr($pg, 'title'), 'pg' => $pg]);
}

function public_manifest(): void
{
    header('Content-Type: application/manifest+json');
    echo json_encode([
        'name' => setting('site_name', 'Mian Muhammad Bakhsh Public Library'),
        'short_name' => setting('short_name', 'MMB Library'),
        'start_url' => url('/'), 'scope' => url('/'), 'display' => 'standalone',
        'background_color' => '#ffffff', 'theme_color' => '#0B4DA2',
        'icons' => [['src' => url('assets/img/logo-192.png'), 'sizes' => '192x192', 'type' => 'image/png'], ['src' => url('assets/img/logo-512.png'), 'sizes' => '512x512', 'type' => 'image/png']],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
function public_service_worker(): void
{
    header('Content-Type: application/javascript');
    header('Service-Worker-Allowed: ' . (base_path() ?: '/'));
    $v = APP_VERSION;
    $css = asset('css/app.css');
    $js = asset('js/app.js');
    echo <<<JS
const C='mmbpl-$v';
self.addEventListener('install',e=>{e.waitUntil(caches.open(C).then(c=>c.addAll(['$css','$js'])));self.skipWaiting();});
self.addEventListener('activate',e=>{e.waitUntil(caches.keys().then(k=>Promise.all(k.filter(x=>x!==C).map(x=>caches.delete(x)))));});
self.addEventListener('fetch',e=>{const u=new URL(e.request.url);if(e.request.method!=='GET'||u.origin!==location.origin)return;
if(u.pathname.includes('/assets/')){e.respondWith(caches.match(e.request).then(r=>r||fetch(e.request).then(res=>{const cl=res.clone();caches.open(C).then(c=>c.put(e.request,cl));return res;})));}});
JS;
}
