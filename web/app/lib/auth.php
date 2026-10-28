<?php
declare(strict_types=1);

/* ---------------- Members (website session) ---------------- */
function member(): ?array
{
    static $m = false;
    if ($m === false) {
        $m = null;
        if (!empty($_SESSION['member_id'])) {
            $m = row('SELECT * FROM members WHERE id = ?', [$_SESSION['member_id']]);
        }
    }
    return $m;
}
function require_member(): array
{
    $m = member();
    if (!$m) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? url('account');
        flash('info', t('please_sign_in'));
        redirect('signin');
    }
    return $m;
}
function member_login(array $m): void
{
    session_regenerate_id(true);
    $_SESSION['member_id'] = (int)$m['id'];
}
function member_logout(): void
{
    unset($_SESSION['member_id']);
    session_regenerate_id(true);
}
function find_member_by_login(string $login): ?array
{
    $login = trim($login);
    if ($login === '') return null;
    $m = row('SELECT * FROM members WHERE cardnumber = ?', [$login]);
    if ($m) return $m;
    $digits = preg_replace('/\D+/', '', $login);
    if (strlen($digits) >= 10) {
        $tail = substr($digits, -10);
        $list = rows("SELECT * FROM members WHERE REPLACE(REPLACE(REPLACE(phone,'-',''),' ',''),'+','') LIKE ?", ['%' . $tail]);
        if (count($list) === 1) return $list[0];
    }
    return null;
}
function member_display_name(array $m): string
{
    return trim(($m['firstname'] ?? '') . ' ' . ($m['surname'] ?? '')) ?: $m['cardnumber'];
}

/** Create and send a 6-digit OTP. Returns the code when SMS is not configured (dev/demo display). */
function create_otp(int $memberId, string $purpose, string $phone): ?string
{
    q('DELETE FROM otps WHERE member_id = ? AND purpose = ?', [$memberId, $purpose]);
    $code = (string)random_int(100000, 999999);
    insert('otps', ['member_id' => $memberId, 'code_hash' => password_hash($code, PASSWORD_DEFAULT), 'purpose' => $purpose, 'expires_at' => date('Y-m-d H:i:s', time() + 600)]);
    $sent = send_sms($phone, t('otp_sms', ['code' => $code, 'site' => setting('short_name', 'MMBPL')]));
    return $sent ? null : $code;
}
function verify_otp(int $memberId, string $purpose, string $code): bool
{
    $o = row('SELECT * FROM otps WHERE member_id = ? AND purpose = ? ORDER BY id DESC LIMIT 1', [$memberId, $purpose]);
    if (!$o || strtotime($o['expires_at']) < time() || $o['attempts'] >= 5) return false;
    q('UPDATE otps SET attempts = attempts + 1 WHERE id = ?', [$o['id']]);
    if (!password_verify($code, $o['code_hash'])) return false;
    q('DELETE FROM otps WHERE id = ?', [$o['id']]);
    return true;
}

/* ---------------- Admin users ---------------- */
function admin(): ?array
{
    static $a = false;
    if ($a === false) {
        $a = null;
        if (!empty($_SESSION['admin_id'])) $a = row('SELECT * FROM users WHERE id = ? AND active = 1', [$_SESSION['admin_id']]);
    }
    return $a;
}
const ROLE_PERMS = [
    'super_admin' => ['*'],
    'librarian' => ['dashboard', 'members', 'applications', 'suggestions', 'donations', 'feedback', 'events', 'news', 'notifications', 'catalog', 'sync', 'reports', 'exam', 'archive'],
    'editor' => ['dashboard', 'events', 'news', 'pages', 'gallery', 'exam', 'archive', 'notifications'],
];
function can(string $perm): bool
{
    $a = admin();
    if (!$a) return false;
    $p = ROLE_PERMS[$a['role']] ?? [];
    return in_array('*', $p, true) || in_array($perm, $p, true);
}
function require_admin(string $perm = 'dashboard'): array
{
    $a = admin();
    if (!$a) redirect('admin/login');
    if (!can($perm)) { http_response_code(403); view('admin/forbidden', ['title' => 'Not allowed'], 'admin'); exit; }
    return $a;
}

/* ---------------- API tokens (mobile app) ---------------- */
function api_issue_token(int $memberId, string $device = ''): string
{
    $token = bin2hex(random_bytes(32));
    insert('member_tokens', ['member_id' => $memberId, 'token_hash' => hash('sha256', $token), 'device' => mb_substr($device, 0, 120), 'expires_at' => date('Y-m-d H:i:s', time() + 86400 * 90)]);
    return $token;
}
function api_member(): ?array
{
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!$h && function_exists('apache_request_headers')) {
        $hs = array_change_key_case(apache_request_headers(), CASE_LOWER);
        $h = $hs['authorization'] ?? '';
    }
    if (!preg_match('/Bearer\s+([a-f0-9]{64})/i', $h, $m)) return null;
    $t = row('SELECT member_id FROM member_tokens WHERE token_hash = ? AND expires_at > NOW()', [hash('sha256', $m[1])]);
    return $t ? row('SELECT * FROM members WHERE id = ?', [$t['member_id']]) : null;
}
