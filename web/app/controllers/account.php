<?php
declare(strict_types=1);

function account_render(string $section, array $data = []): void
{
    $m = require_member();
    $id = (int)$m['id'];
    $data += ['m' => $m, 'section' => $section, 'title' => t('my_account'),
        'counts' => ['loans' => (int)val('SELECT COUNT(*) FROM loans WHERE member_id=?', [$id]), 'holds' => (int)val('SELECT COUNT(*) FROM holds WHERE member_id=? AND status IN ("requested","waiting","ready")', [$id]), 'unread' => unread_count($id)]];
    view('account/' . $section, $data);
}

function account_overview(): void
{
    $m = require_member();
    $loans = member_loans((int)$m['id']);
    $dueSoon = array_filter($loans, fn($l) => days_until($l['due_date']) !== null && days_until($l['due_date']) <= 7);
    account_render('overview', ['loans' => $loans, 'dueSoon' => $dueSoon, 'holds' => member_holds((int)$m['id']), 'notifs' => array_slice(member_notifications((int)$m['id']), 0, 5)]);
}
function account_loans(): void { $m = require_member(); account_render('loans', ['loans' => member_loans((int)$m['id'])]); }
function account_holds(): void { $m = require_member(); account_render('holds', ['holds' => member_holds((int)$m['id'])]); }
function account_history(): void { $m = require_member(); account_render('history', ['history' => member_history((int)$m['id'])]); }
function account_wishlist(): void
{
    $m = require_member();
    $list = rows('SELECT b.* FROM wishlist w JOIN biblios b ON b.id=w.biblio_id WHERE w.member_id=? ORDER BY w.created_at DESC', [$m['id']]);
    foreach ($list as &$b) $b['av'] = biblio_availability((int)$b['id']);
    account_render('wishlist', ['list' => $list]);
}
function account_card(): void { account_render('card', ['scripts' => ['js/qr.js']]); }
function account_notifications(): void
{
    $m = require_member();
    $filter = input('f');
    $list = member_notifications((int)$m['id'], 100);
    if ($filter) $list = array_values(array_filter($list, fn($n) => ($filter === 'books' && in_array($n['type'], ['due', 'hold', 'renew', 'overdue'], true)) || $n['type'] === $filter));
    account_render('notifications', ['list' => $list, 'filter' => $filter]);
}
function account_notifications_read(): void { $m = require_member(); mark_all_read((int)$m['id']); redirect('account/notifications'); }
function account_profile(): void { account_render('profile'); }
function account_profile_post(): void
{
    $m = require_member();
    $data = [
        'lang' => in_array(input('lang'), ['en', 'ur'], true) ? input('lang') : 'en',
        'notify_due' => isset($_POST['notify_due']) ? 1 : 0, 'notify_holds' => isset($_POST['notify_holds']) ? 1 : 0,
        'notify_events' => isset($_POST['notify_events']) ? 1 : 0, 'notify_sms' => isset($_POST['notify_sms']) ? 1 : 0,
        'notify_whatsapp' => isset($_POST['notify_whatsapp']) ? 1 : 0, 'notify_push' => isset($_POST['notify_push']) ? 1 : 0,
    ];
    update('members', $data, 'id=?', [$m['id']]);
    $_SESSION['lang'] = $data['lang'];
    // Contact changes go to Koha through the agent so Koha stays the master record.
    $changes = [];
    foreach (['phone', 'email'] as $k) if (input($k) !== '' && input($k) !== (string)$m[$k]) $changes[$k] = input($k);
    if (input('address') !== '') $changes['address'] = input('address');
    if ($changes) {
        insert('actions', ['type' => 'update_member', 'member_id' => $m['id'], 'ref_id' => $m['id'], 'payload' => json_encode(['koha_borrowernumber' => $m['koha_borrowernumber'], 'changes' => $changes], JSON_UNESCAPED_UNICODE)]);
        flash('success', t('profile_change_requested'));
    } else {
        flash('success', t('saved'));
    }
    redirect('account/profile');
}
function account_password_post(): void
{
    $m = require_member();
    if (!password_verify((string)($_POST['current'] ?? ''), (string)$m['password_hash'])) { flash('error', t('err_current_password')); redirect('account/profile'); }
    $p = (string)($_POST['password'] ?? '');
    if (mb_strlen($p) < 8) { flash('error', t('err_password_short')); redirect('account/profile'); }
    if ($p !== ($_POST['password2'] ?? '')) { flash('error', t('err_password_match')); redirect('account/profile'); }
    q('UPDATE members SET password_hash=? WHERE id=?', [password_hash($p, PASSWORD_DEFAULT), $m['id']]);
    q('DELETE FROM member_tokens WHERE member_id=?', [$m['id']]);
    flash('success', t('password_changed'));
    redirect('account/profile');
}
function account_back(string $default): never
{
    $r = $_SERVER['HTTP_REFERER'] ?? '';
    if ($r && parse_url($r, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) redirect($r);
    redirect($default);
}
function account_renew(string $id): void
{
    $m = require_member();
    [$ok, $msg] = request_renew($m, (int)$id);
    flash($ok ? 'success' : 'error', t($msg));
    account_back('account/loans');
}
function account_renew_all(): void
{
    $m = require_member();
    $n = 0; $fail = 0;
    foreach (rows('SELECT id FROM loans WHERE member_id=?', [$m['id']]) as $l) { [$ok] = request_renew($m, (int)$l['id']); $ok ? $n++ : $fail++; }
    flash($n ? 'success' : 'error', t('renew_all_result', ['n' => $n, 'f' => $fail]));
    account_back('account/loans');
}
function account_reserve(string $id): void
{
    $m = require_member();
    [$ok, $msg] = request_reserve($m, (int)$id);
    flash($ok ? 'success' : 'error', t($msg));
    account_back('book/' . (int)$id);
}
function account_cancel_hold(string $id): void
{
    $m = require_member();
    [$ok, $msg] = request_cancel_hold($m, (int)$id);
    flash($ok ? 'success' : 'error', t($msg));
    redirect('account/holds');
}
function account_toggle_list(string $id): void
{
    $m = require_member();
    if (val('SELECT 1 FROM wishlist WHERE member_id=? AND biblio_id=?', [$m['id'], (int)$id])) {
        q('DELETE FROM wishlist WHERE member_id=? AND biblio_id=?', [$m['id'], (int)$id]);
        flash('success', t('removed_from_list'));
    } elseif (val('SELECT id FROM biblios WHERE id=?', [(int)$id])) {
        insert('wishlist', ['member_id' => $m['id'], 'biblio_id' => (int)$id]);
        flash('success', t('added_to_list'));
    }
    account_back('account/list');
}
