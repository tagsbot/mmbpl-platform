<?php
declare(strict_types=1);
require_once APP_ROOT . '/app/lib/resources.php';

function admin_view(string $name, array $data = []): void { view('admin/' . $name, $data, 'admin'); }

/* ---------- Auth ---------- */
function admin_login(): void
{
    if (admin()) redirect('admin');
    view('admin/login', ['title' => 'Admin sign in'], null);
}
function admin_login_post(): void
{
    if (!rate_limit('admin_login', 8, 900)) { flash('error', 'Too many attempts. Wait 15 minutes.'); redirect('admin/login'); }
    $u = row('SELECT * FROM users WHERE email = ? AND active = 1', [strtolower(input('email'))]);
    if (!$u || !password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
        audit('login_failed', input('email'));
        flash('error', 'Wrong email or password.');
        redirect('admin/login');
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$u['id'];
    q('UPDATE users SET last_login = NOW() WHERE id = ?', [$u['id']]);
    audit('login');
    redirect('admin');
}
function admin_logout(): void { unset($_SESSION['admin_id']); session_regenerate_id(true); redirect('admin/login'); }
function admin_password(): void { require_admin(); admin_view('password', ['title' => 'Change password']); }
function admin_password_post(): void
{
    $a = require_admin();
    if (!password_verify((string)($_POST['current'] ?? ''), $a['password_hash'])) { flash('error', 'Current password is wrong.'); redirect('admin/password'); }
    $p = (string)($_POST['password'] ?? '');
    if (strlen($p) < 10) { flash('error', 'Use at least 10 characters.'); redirect('admin/password'); }
    q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($p, PASSWORD_DEFAULT), $a['id']]);
    audit('password_changed');
    flash('success', 'Password changed.');
    redirect('admin');
}

/* ---------- Dashboard ---------- */
function admin_dashboard(): void
{
    require_admin();
    $c = fn($sql) => (int)val($sql);
    $stats = [
        'titles' => $c('SELECT COUNT(*) FROM biblios'), 'items' => $c('SELECT COUNT(*) FROM items'),
        'members' => $c('SELECT COUNT(*) FROM members'), 'online' => $c('SELECT COUNT(*) FROM members WHERE activated_at IS NOT NULL'),
        'loans' => $c('SELECT COUNT(*) FROM loans'), 'overdue' => $c('SELECT COUNT(*) FROM loans WHERE due_date < CURDATE()'),
        'holds' => $c('SELECT COUNT(*) FROM holds WHERE status IN ("requested","waiting","ready")'),
        'apps' => $c('SELECT COUNT(*) FROM applications WHERE status="pending"'), 'sugg' => $c('SELECT COUNT(*) FROM suggestions WHERE status="review"'),
        'fb' => $c('SELECT COUNT(*) FROM feedback WHERE status="new"'), 'don' => $c('SELECT COUNT(*) FROM donations WHERE status="new"'),
        'pending_actions' => $c('SELECT COUNT(*) FROM actions WHERE status IN ("pending","processing")'), 'failed_actions' => $c('SELECT COUNT(*) FROM actions WHERE status="failed"'),
        'events' => $c('SELECT COUNT(*) FROM posts WHERE type="event" AND event_start >= NOW() AND status="published"'),
    ];
    $popular = rows('SELECT id, title, author, popularity FROM biblios ORDER BY popularity DESC LIMIT 8');
    $byLang = rows('SELECT language, COUNT(*) n FROM biblios GROUP BY language ORDER BY n DESC');
    $signups = rows('SELECT DATE_FORMAT(activated_at, "%Y-%m") m, COUNT(*) n FROM members WHERE activated_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY m ORDER BY m');
    $recentActions = rows('SELECT a.*, m.cardnumber FROM actions a LEFT JOIN members m ON m.id=a.member_id ORDER BY a.id DESC LIMIT 8');
    admin_view('dashboard', ['title' => 'Dashboard', 'stats' => $stats, 'popular' => $popular, 'byLang' => $byLang, 'signups' => $signups, 'recentActions' => $recentActions]);
}

/* ---------- Generic resources ---------- */
function admin_res(string $res): array
{
    $all = admin_resources();
    if (!isset($all[$res])) not_found();
    $r = $all[$res];
    require_admin($r['perm']);
    $r['key'] = $res;
    return $r;
}
function admin_scope_sql(array $r, array &$p): string
{
    $w = '1=1';
    foreach ($r['scope'] ?? [] as $k => $v) { $w .= " AND `$k` = ?"; $p[] = $v; }
    return $w;
}
function admin_res_list(string $res): void
{
    $r = admin_res($res);
    $p = [];
    $w = admin_scope_sql($r, $p);
    if (($s = input('s')) !== '') {
        $w .= ' AND (' . implode(' OR ', array_map(fn($c) => "`$c` LIKE ?", $r['search'])) . ')';
        foreach ($r['search'] as $_) $p[] = "%$s%";
    }
    if (($st = input('status')) !== '' && !empty($r['statuses'])) { $w .= ' AND status = ?'; $p[] = $st; }
    $page = max(1, (int)input('page', '1'));
    $total = (int)val("SELECT COUNT(*) FROM `{$r['table']}` WHERE $w", $p);
    $list = rows("SELECT * FROM `{$r['table']}` WHERE $w ORDER BY {$r['order']} LIMIT 25 OFFSET " . (($page - 1) * 25), $p);
    if ($res === 'events') foreach ($list as &$x) $x['_regs'] = (int)val('SELECT COALESCE(SUM(seats),0) FROM event_registrations WHERE post_id=?', [$x['id']]) . ($x['seats'] ? ' / ' . $x['seats'] : '');
    admin_view('res_list', ['title' => $r['title'], 'r' => $r, 'list' => $list, 'total' => $total, 'page' => $page]);
}
function admin_res_form(string $res, ?string $id = null): void
{
    $r = admin_res($res);
    $item = [];
    if ($id !== null) {
        $p = [(int)$id];
        $item = row("SELECT * FROM `{$r['table']}` WHERE id = ?", $p);
        if (!$item) not_found();
        if (!empty($r['custom_view'])) {
            view($r['custom_view'], ['title' => $r['singular'], 'r' => $r, 'item' => $item], 'admin');
            return;
        }
    } elseif (!empty($r['no_create'])) redirect('admin/r/' . $res);
    admin_view('res_form', ['title' => ($id ? 'Edit ' : 'New ') . strtolower($r['singular']), 'r' => $r, 'item' => $item]);
}
function admin_res_save(string $res): void
{
    $r = admin_res($res);
    $id = (int)input('id', '0');
    $data = [];
    foreach ($r['fields'] as $name => $f) {
        $type = $f[1];
        if (in_array($type, ['readonly', 'readonly_file'], true)) continue;
        if ($type === 'checkbox') { $data[$name] = isset($_POST[$name]) ? 1 : 0; continue; }
        if ($type === 'image' || $type === 'file') {
            $up = save_upload($name, $type === 'image' ? ['jpg', 'jpeg', 'png', 'webp'] : ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 8);
            if ($up) $data[$name] = $up;
            elseif (!empty($_POST[$name . '_remove'])) $data[$name] = null;
            elseif (!$id && !empty($f['required'])) { flash('error', $f[0] . ' is required.'); redirect('admin/r/' . $res . '/new'); }
            continue;
        }
        if ($type === 'password') {
            $pw = (string)($_POST[$name] ?? '');
            if ($pw !== '') { if (strlen($pw) < 10) { flash('error', 'Password must be at least 10 characters.'); redirect('admin/r/' . $res . ($id ? '/' . $id : '/new')); } $data['password_hash'] = password_hash($pw, PASSWORD_DEFAULT); }
            elseif (!$id) { flash('error', 'Password is required for a new user.'); redirect('admin/r/' . $res . '/new'); }
            continue;
        }
        $v = $_POST[$name] ?? '';
        $v = is_string($v) ? trim($v) : '';
        if ($type === 'datetime') $v = $v ? date('Y-m-d H:i:s', strtotime($v)) : null;
        elseif (in_array($type, ['date', 'number'], true) && $v === '') $v = null;
        elseif ($type === 'select' && !isset($f['options'][$v])) $v = array_key_first($f['options']);
        elseif ($type === 'email') $v = strtolower($v);
        if (!empty($f['required']) && ($v === '' || $v === null)) { flash('error', $f[0] . ' is required.'); redirect('admin/r/' . $res . ($id ? '/' . $id : '/new')); }
        $data[$name] = $v;
    }
    if ($r['table'] === 'posts') {
        $data += $r['scope'];
        if (!$id) {
            $base = slugify($data['title']);
            $slug = $base; $i = 2;
            while (val('SELECT id FROM posts WHERE slug=?', [$slug])) $slug = $base . '-' . $i++;
            $data['slug'] = $slug;
        }
    }
    if ($res === 'users') {
        if ($id && $id === (int)admin()['id'] && (($data['role'] ?? '') !== 'super_admin' || empty($data['active']))) { flash('error', 'You cannot remove your own super admin access.'); redirect('admin/r/users/' . $id); }
        if (val('SELECT id FROM users WHERE email=? AND id<>?', [$data['email'], $id])) { flash('error', 'Another user already has this email.'); redirect('admin/r/users' . ($id ? '/' . $id : '/new')); }
    }
    if ($id) { update($r['table'], $data, 'id = ?', [$id]); audit("update_$res", (string)$id); }
    else { $id = insert($r['table'], $data); audit("create_$res", (string)$id); }
    flash('success', $r['singular'] . ' saved.');
    redirect('admin/r/' . $res);
}
function admin_res_delete(string $res, string $id): void
{
    $r = admin_res($res);
    if ($res === 'users' && (int)$id === (int)admin()['id']) { flash('error', 'You cannot delete yourself.'); redirect('admin/r/users'); }
    $p = [(int)$id];
    $w = admin_scope_sql($r, $p);
    q("DELETE FROM `{$r['table']}` WHERE id = ? AND $w", array_merge([(int)$id], array_slice($p, 1)));
    audit("delete_$res", $id);
    flash('success', 'Deleted.');
    redirect('admin/r/' . $res);
}
function admin_res_export(string $res): void
{
    $r = admin_res($res);
    $p = [];
    $w = admin_scope_sql($r, $p);
    $list = rows("SELECT * FROM `{$r['table']}` WHERE $w ORDER BY id DESC", $p);
    if ($res === 'users') foreach ($list as &$u) unset($u['password_hash']);
    csv_download($res . '-' . date('Ymd') . '.csv', $list);
}
function csv_download(string $name, array $list): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    if ($list) fputcsv($out, array_keys($list[0]));
    foreach ($list as $row) fputcsv($out, array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $row));
    fclose($out);
    exit;
}

/* ---------- Applications ---------- */
function admin_application_decide(string $id): void
{
    require_admin('applications');
    $a = row('SELECT * FROM applications WHERE id=?', [(int)$id]);
    if (!$a) not_found();
    $decision = input('decision');
    $note = mb_substr(input('note'), 0, 500);
    if (in_array($decision, ['correction', 'rejected'], true) && $note === '') { flash('error', 'Please write a reason for the applicant.'); redirect('admin/r/applications/' . $id); }
    if ($decision === 'approved') {
        if ($a['cnic'] && val('SELECT id FROM applications WHERE cnic=? AND status="approved" AND id<>?', [$a['cnic'], $a['id']]) && empty($_POST['confirm_duplicate'])) {
            flash('error', 'An approved application with this CNIC already exists. Tick "approve anyway" if this is correct.');
            redirect('admin/r/applications/' . $id);
        }
        $parts = preg_split('/\s+/', trim($a['full_name']), 2);
        insert('actions', ['type' => 'create_member', 'ref_id' => $a['id'], 'payload' => json_encode([
            'firstname' => $parts[0], 'surname' => $parts[1] ?? '', 'dob' => $a['dob'], 'gender' => $a['gender'], 'phone' => $a['phone'], 'email' => $a['email'],
            'address' => $a['address'], 'city' => $a['city'], 'membership_type' => $a['membership_type'], 'category_code' => setting('koha_cat_' . $a['membership_type'], strtoupper(substr($a['membership_type'], 0, 2))),
            'cnic' => $a['cnic'], 'guardian' => $a['guardian_name'], 'ref_no' => $a['ref_no'],
        ], JSON_UNESCAPED_UNICODE)]);
    }
    update('applications', ['status' => $decision, 'staff_note' => $note], 'id=?', [$a['id']]);
    $msg = match ($decision) {
        'approved' => "Your library membership application {$a['ref_no']} is approved. Please visit the library desk to collect your card.",
        'correction' => "Your library application {$a['ref_no']} needs a correction: $note",
        'rejected' => "Your library application {$a['ref_no']} could not be approved: $note",
        default => '',
    };
    if ($msg && !empty($_POST['send_sms'])) send_sms($a['phone'], $msg);
    audit('application_' . $decision, $a['ref_no']);
    flash('success', 'Application marked as ' . $decision . ($decision === 'approved' ? '. The member will be created in Koha by the Sync Agent.' : '.'));
    redirect('admin/r/applications');
}

/* ---------- Events registrations ---------- */
function admin_event_registrations(string $id): void
{
    require_admin('events');
    $e = row('SELECT * FROM posts WHERE id=?', [(int)$id]);
    if (!$e) not_found();
    $regs = rows('SELECT reg_no, name, phone, seats, created_at FROM event_registrations WHERE post_id=? ORDER BY id', [$e['id']]);
    if (input('format') === 'csv') csv_download('registrations-' . $e['slug'] . '.csv', $regs);
    admin_view('registrations', ['title' => 'Registrations', 'e' => $e, 'regs' => $regs]);
}

/* ---------- Pages ---------- */
const EDITABLE_PAGES = ['about' => 'About / Our story', 'rules' => 'Rules & loan policy', 'exam-corner' => 'Exam corner introduction', 'privacy' => 'Privacy policy'];
function admin_pages(): void { require_admin('pages'); admin_view('pages', ['title' => 'Pages', 'pages' => EDITABLE_PAGES, 'saved' => array_column(rows('SELECT slug, updated_at FROM pages'), 'updated_at', 'slug')]); }
function admin_page_edit(string $slug): void
{
    require_admin('pages');
    if (!isset(EDITABLE_PAGES[$slug])) not_found();
    admin_view('page_edit', ['title' => EDITABLE_PAGES[$slug], 'slug' => $slug, 'pg' => row('SELECT * FROM pages WHERE slug=?', [$slug]) ?: ['title' => EDITABLE_PAGES[$slug], 'title_ur' => '', 'body' => '', 'body_ur' => '']]);
}
function admin_page_save(string $slug): void
{
    require_admin('pages');
    if (!isset(EDITABLE_PAGES[$slug])) not_found();
    q('INSERT INTO pages (slug, title, title_ur, body, body_ur) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title), title_ur=VALUES(title_ur), body=VALUES(body), body_ur=VALUES(body_ur)',
        [$slug, input('title') ?: EDITABLE_PAGES[$slug], input('title_ur'), $_POST['body'] ?? '', $_POST['body_ur'] ?? '']);
    audit('page_save', $slug);
    flash('success', 'Page saved.');
    redirect('admin/pages');
}

/* ---------- Members & catalog (read-only mirrors of Koha) ---------- */
function admin_members(): void
{
    require_admin('members');
    $p = []; $w = '1=1';
    if ($s = input('s')) { $w .= ' AND (cardnumber LIKE ? OR firstname LIKE ? OR surname LIKE ? OR phone LIKE ?)'; array_push($p, "%$s%", "%$s%", "%$s%", "%$s%"); }
    if ($st = input('status')) { $w .= ' AND status=?'; $p[] = $st; }
    if (input('online') === '1') $w .= ' AND activated_at IS NOT NULL';
    $page = max(1, (int)input('page', '1'));
    $total = (int)val("SELECT COUNT(*) FROM members WHERE $w", $p);
    $list = rows("SELECT m.*, (SELECT COUNT(*) FROM loans l WHERE l.member_id=m.id) loans FROM members m WHERE $w ORDER BY m.id DESC LIMIT 30 OFFSET " . (($page - 1) * 30), $p);
    admin_view('members', ['title' => 'Members', 'list' => $list, 'total' => $total, 'page' => $page]);
}
function admin_member_view(string $id): void
{
    require_admin('members');
    $m = row('SELECT * FROM members WHERE id=?', [(int)$id]);
    if (!$m) not_found();
    admin_view('member_view', ['title' => member_display_name($m), 'm' => $m, 'loans' => member_loans((int)$m['id']), 'holds' => member_holds((int)$m['id']),
        'actions' => rows('SELECT * FROM actions WHERE member_id=? ORDER BY id DESC LIMIT 20', [$m['id']])]);
}
function admin_catalog(): void
{
    require_admin('catalog');
    $p = []; $w = '1=1';
    if ($s = input('s')) { $w .= ' AND (title LIKE ? OR author LIKE ? OR isbn LIKE ? OR call_number LIKE ?)'; array_push($p, "%$s%", "%$s%", "%$s%", "%$s%"); }
    $page = max(1, (int)input('page', '1'));
    $total = (int)val("SELECT COUNT(*) FROM biblios WHERE $w", $p);
    $list = rows("SELECT b.*, (SELECT COUNT(*) FROM items i WHERE i.biblio_id=b.id) copies, (SELECT COUNT(*) FROM items i WHERE i.biblio_id=b.id AND i.status='available') avail FROM biblios b WHERE $w ORDER BY b.id DESC LIMIT 30 OFFSET " . (($page - 1) * 30), $p);
    admin_view('catalog', ['title' => 'Catalog (from Koha)', 'list' => $list, 'total' => $total, 'page' => $page]);
}

/* ---------- Notifications ---------- */
function admin_notifications(): void
{
    require_admin('notifications');
    admin_view('notifications', ['title' => 'Send notifications', 'recent' => rows('SELECT n.*, m.cardnumber FROM notifications n LEFT JOIN members m ON m.id=n.member_id ORDER BY n.id DESC LIMIT 30')]);
}
function admin_notifications_send(): void
{
    require_admin('notifications');
    $title = input('title');
    if ($title === '') { flash('error', 'Title is required.'); redirect('admin/notifications'); }
    $target = input('target');
    $body = input('body');
    $link = input('link');
    $type = in_array(input('type'), ['library', 'event', 'membership'], true) ? input('type') : 'library';
    $sms = !empty($_POST['send_sms']);
    $sent = 0;
    if ($target === 'all') {
        insert('notifications', ['member_id' => null, 'type' => $type, 'title' => $title, 'body' => $body, 'link' => $link]);
        $sent = 1;
        if ($sms) foreach (rows('SELECT phone FROM members WHERE status="active" AND notify_sms=1 AND phone<>""') as $r) send_sms($r['phone'], $title . ($body ? ': ' . $body : ''));
    } else {
        $groups = ['overdue' => 'SELECT DISTINCT m.* FROM members m JOIN loans l ON l.member_id=m.id WHERE l.due_date < CURDATE()',
            'due_soon' => 'SELECT DISTINCT m.* FROM members m JOIN loans l ON l.member_id=m.id WHERE l.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)',
            'expired' => 'SELECT * FROM members WHERE status="expired"'];
        $list = isset($groups[$target]) ? rows($groups[$target]) : (($m = row('SELECT * FROM members WHERE cardnumber=?', [input('cardnumber')])) ? [$m] : []);
        foreach ($list as $m) { notify_member((int)$m['id'], $type, $title, $body, $link); if ($sms && $m['phone'] && $m['notify_sms']) send_sms($m['phone'], $title . ($body ? ': ' . $body : '')); $sent++; }
    }
    audit('notification_send', "$target: $title");
    flash($sent ? 'success' : 'error', $sent ? "Notification sent ($sent)." : 'No matching members.');
    redirect('admin/notifications');
}

/* ---------- Settings ---------- */
const SETTING_GROUPS = [
    'Library identity' => ['site_name' => 'Library name (English)', 'site_name_ur' => 'Library name (Urdu)', 'site_name_short' => 'Name in header', 'site_name_short_ur' => 'Name in header (Urdu)', 'short_name' => 'Short name (e.g. MMB Public Library)', 'site_place' => 'Place line (English)', 'site_place_ur' => 'Place line (Urdu)', 'site_tagline' => 'Search engine description', 'footer_about' => 'Footer about text', 'footer_about_ur' => 'Footer about text (Urdu)'],
    'Home page' => ['hero_title' => 'Hero headline (English)', 'hero_title_ur' => 'Hero headline (Urdu)', 'hero_subtitle' => 'Hero subtitle (English)', 'hero_subtitle_ur' => 'Hero subtitle (Urdu)', 'hero_note' => 'Small note above headline', 'visit_text' => 'Visit us text', 'visit_text_ur' => 'Visit us text (Urdu)', 'stat_members_override' => 'Members count to show (empty = automatic)', 'stat_books_override' => 'Books count to show (empty = automatic)', 'stat_events_override' => 'Events count to show (empty = automatic)'],
    'Contact & hours' => ['address' => 'Address (English)', 'address_ur' => 'Address (Urdu)', 'map_query' => 'Google Maps search text or "lat,lng"', 'phone' => 'Phone', 'whatsapp' => 'WhatsApp number', 'email' => 'Email', 'facebook_url' => 'Facebook page URL', 'hours_text' => 'Opening hours text (English)', 'hours_text_ur' => 'Opening hours text (Urdu)', 'open_days' => 'Open days (1=Mon … 7=Sun, comma separated)', 'open_from' => 'Opens at (HH:MM)', 'open_to' => 'Closes at (HH:MM)'],
    'Membership & rules' => ['fee_student' => 'Student fee text', 'fee_general' => 'General fee text', 'fee_child' => 'Child fee text', 'books_student' => 'Books allowed (student)', 'books_general' => 'Books allowed (general)', 'books_child' => 'Books allowed (child)', 'max_loans_text' => 'Books per member (shown in benefits)', 'max_holds' => 'Max reservations per member', 'review_days' => 'Application review time (days)', 'koha_cat_student' => 'Koha category code: Student', 'koha_cat_general' => 'Koha category code: General', 'koha_cat_child' => 'Koha category code: Child', 'koha_branch' => 'Koha library (branch) code', 'donation_policy' => 'Donation policy', 'donation_accept' => 'Donations we accept', 'donation_reject' => 'Donations we do not accept'],
    'Messaging' => ['sms_url' => 'SMS gateway URL template ({to} and {text})', 'mail_from' => 'Email "from" address', 'fcm_server_key' => 'Firebase server key (push for mobile app)'],
];
function admin_settings(): void { require_admin('settings'); admin_view('settings', ['title' => 'Settings', 'groups' => SETTING_GROUPS]); }
function admin_settings_save(): void
{
    require_admin('settings');
    foreach (SETTING_GROUPS as $fields) foreach ($fields as $k => $_) if (isset($_POST[$k])) set_setting($k, trim((string)$_POST[$k]));
    set_setting('show_demo_otp', isset($_POST['show_demo_otp']) ? '1' : '0');
    audit('settings_save');
    flash('success', 'Settings saved.');
    redirect('admin/settings');
}

/* ---------- Sync ---------- */
function admin_sync(): void
{
    require_admin('sync');
    admin_view('sync', ['title' => 'Koha sync', 'actions' => rows('SELECT a.*, m.cardnumber FROM actions a LEFT JOIN members m ON m.id=a.member_id ORDER BY FIELD(a.status,"failed","pending","processing","done"), a.id DESC LIMIT 50'),
        'logs' => rows('SELECT * FROM sync_log ORDER BY id DESC LIMIT 30'), 'newKey' => $_SESSION['new_agent_key'] ?? null]);
    unset($_SESSION['new_agent_key']);
}
function admin_sync_key(): void
{
    require_admin('settings');
    $key = 'mmb_' . bin2hex(random_bytes(24));
    set_setting('agent_key_hash', hash('sha256', $key));
    set_setting('agent_key_hint', substr($key, 0, 8) . '…' . substr($key, -4));
    $_SESSION['new_agent_key'] = $key;
    audit('agent_key_generated');
    redirect('admin/sync');
}
function admin_sync_retry(string $id): void
{
    require_admin('sync');
    q('UPDATE actions SET status="pending", result=NULL WHERE id=? AND status="failed"', [(int)$id]);
    flash('success', 'Request queued again.');
    redirect('admin/sync');
}
function admin_demo_remove(): void
{
    require_admin('settings');
    q('DELETE FROM biblios WHERE is_demo=1');
    q('DELETE FROM members WHERE is_demo=1');
    q('DELETE FROM posts WHERE is_demo=1');
    q('DELETE FROM archive_items WHERE is_demo=1');
    q('DELETE FROM exam_resources WHERE is_demo=1');
    set_setting('has_demo', '0');
    audit('demo_removed');
    flash('success', 'Demo data removed.');
    redirect('admin');
}

/* ---------- Reports ---------- */
function admin_reports(): void
{
    require_admin('reports');
    $kind = input('export');
    $reports = [
        'overdue' => 'SELECT m.cardnumber, CONCAT(m.firstname," ",m.surname) member, m.phone, b.title, i.barcode, l.due_date, DATEDIFF(CURDATE(), l.due_date) days_overdue FROM loans l JOIN members m ON m.id=l.member_id JOIN items i ON i.id=l.item_id JOIN biblios b ON b.id=i.biblio_id WHERE l.due_date < CURDATE() ORDER BY l.due_date',
        'popular' => 'SELECT title, author, language, popularity issues FROM biblios ORDER BY popularity DESC LIMIT 200',
        'online_members' => 'SELECT cardnumber, firstname, surname, category, activated_at FROM members WHERE activated_at IS NOT NULL ORDER BY activated_at DESC',
        'suggestions' => 'SELECT title, author, language, status, created_at FROM suggestions ORDER BY created_at DESC',
        'event_registrations' => 'SELECT p.title event, p.event_start, r.reg_no, r.name, r.phone, r.seats FROM event_registrations r JOIN posts p ON p.id=r.post_id ORDER BY p.event_start DESC',
    ];
    if ($kind && isset($reports[$kind])) csv_download($kind . '-' . date('Ymd') . '.csv', rows($reports[$kind]));
    $monthly = rows('SELECT DATE_FORMAT(created_at,"%Y-%m") m, SUM(type="renew") renew, SUM(type="reserve") reserve FROM actions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY m ORDER BY m');
    admin_view('reports', ['title' => 'Reports', 'reports' => array_keys($reports), 'monthly' => $monthly,
        'cats' => rows('SELECT COALESCE(category,"—") category, COUNT(*) n FROM members GROUP BY category ORDER BY n DESC'),
        'apps' => rows('SELECT status, COUNT(*) n FROM applications GROUP BY status')]);
}
