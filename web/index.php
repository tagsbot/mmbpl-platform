<?php
/**
 * Mian Muhammad Bakhsh Public Library — Digital Platform
 * Front controller: every request is routed from here.
 */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$routes = [
    // Public website
    ['GET', '/', 'public', 'home'],
    ['GET', '/catalog', 'public', 'catalog'],
    ['GET', '/book/{id}', 'public', 'book'],
    ['GET', '/lang/{code}', 'public', 'set_lang'],
    ['GET', '/signin', 'public', 'signin'],
    ['POST', '/signin', 'public', 'signin_post'],
    ['POST', '/signin/otp-request', 'public', 'otp_request'],
    ['GET', '/signin/otp', 'public', 'otp_form'],
    ['POST', '/signin/otp', 'public', 'otp_verify'],
    ['GET', '/activate', 'public', 'activate'],
    ['POST', '/activate', 'public', 'activate_post'],
    ['GET', '/activate/verify', 'public', 'activate_verify'],
    ['POST', '/activate/verify', 'public', 'activate_verify_post'],
    ['GET', '/forgot', 'public', 'forgot'],
    ['POST', '/forgot', 'public', 'forgot_post'],
    ['GET', '/signout', 'public', 'signout'],
    ['GET', '/membership', 'public', 'membership'],
    ['POST', '/membership', 'public', 'membership_post'],
    ['GET', '/membership/done/{ref}', 'public', 'membership_done'],
    ['GET', '/suggest', 'public', 'suggest'],
    ['POST', '/suggest', 'public', 'suggest_post'],
    ['POST', '/donate', 'public', 'donate_post'],
    ['GET', '/events', 'public', 'events'],
    ['GET', '/events/{slug}', 'public', 'event'],
    ['POST', '/events/{slug}/register', 'public', 'event_register'],
    ['GET', '/about', 'public', 'about'],
    ['GET', '/contact', 'public', 'contact'],
    ['POST', '/contact', 'public', 'contact_post'],
    ['GET', '/exam-corner', 'public', 'exam_corner'],
    ['GET', '/archive', 'public', 'archive'],
    ['GET', '/page/{slug}', 'public', 'page'],
    ['GET', '/manifest.webmanifest', 'public', 'manifest'],
    ['GET', '/sw.js', 'public', 'service_worker'],

    // Member account
    ['GET', '/account', 'account', 'overview'],
    ['GET', '/account/loans', 'account', 'loans'],
    ['GET', '/account/holds', 'account', 'holds'],
    ['GET', '/account/history', 'account', 'history'],
    ['GET', '/account/list', 'account', 'wishlist'],
    ['GET', '/account/card', 'account', 'card'],
    ['GET', '/account/notifications', 'account', 'notifications'],
    ['POST', '/account/notifications/read', 'account', 'notifications_read'],
    ['GET', '/account/profile', 'account', 'profile'],
    ['POST', '/account/profile', 'account', 'profile_post'],
    ['POST', '/account/password', 'account', 'password_post'],
    ['POST', '/account/renew/{id}', 'account', 'renew'],
    ['POST', '/account/renew-all', 'account', 'renew_all'],
    ['POST', '/account/reserve/{id}', 'account', 'reserve'],
    ['POST', '/account/hold/{id}/cancel', 'account', 'cancel_hold'],
    ['POST', '/account/list/{id}', 'account', 'toggle_list'],

    // Admin panel
    ['GET', '/admin', 'admin', 'dashboard'],
    ['GET', '/admin/login', 'admin', 'login'],
    ['POST', '/admin/login', 'admin', 'login_post'],
    ['GET', '/admin/logout', 'admin', 'logout'],
    ['GET', '/admin/r/{res}', 'admin', 'res_list'],
    ['GET', '/admin/r/{res}/export.csv', 'admin', 'res_export'],
    ['GET', '/admin/r/{res}/new', 'admin', 'res_form'],
    ['GET', '/admin/r/{res}/{id}', 'admin', 'res_form'],
    ['POST', '/admin/r/{res}/save', 'admin', 'res_save'],
    ['POST', '/admin/r/{res}/{id}/delete', 'admin', 'res_delete'],
    ['POST', '/admin/applications/{id}/decide', 'admin', 'application_decide'],
    ['GET', '/admin/events/{id}/registrations', 'admin', 'event_registrations'],
    ['GET', '/admin/pages', 'admin', 'pages'],
    ['GET', '/admin/pages/{slug}', 'admin', 'page_edit'],
    ['POST', '/admin/pages/{slug}', 'admin', 'page_save'],
    ['GET', '/admin/members', 'admin', 'members'],
    ['GET', '/admin/members/{id}', 'admin', 'member_view'],
    ['GET', '/admin/catalog', 'admin', 'catalog'],
    ['GET', '/admin/notifications', 'admin', 'notifications'],
    ['POST', '/admin/notifications', 'admin', 'notifications_send'],
    ['GET', '/admin/settings', 'admin', 'settings'],
    ['POST', '/admin/settings', 'admin', 'settings_save'],
    ['GET', '/admin/sync', 'admin', 'sync'],
    ['POST', '/admin/sync/key', 'admin', 'sync_key'],
    ['POST', '/admin/sync/retry/{id}', 'admin', 'sync_retry'],
    ['POST', '/admin/demo/remove', 'admin', 'demo_remove'],
    ['GET', '/admin/reports', 'admin', 'reports'],
    ['GET', '/admin/password', 'admin', 'password'],
    ['POST', '/admin/password', 'admin', 'password_post'],

    // Mobile app API
    ['ANY', '/api/v1/{path*}', 'api', 'dispatch'],
    // Koha Sync Agent API
    ['ANY', '/api/sync/{path*}', 'sync', 'dispatch'],
];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'HEAD') $method = 'GET';
$path = current_path();

foreach ($routes as [$m, $pattern, $ctrl, $fn]) {
    if ($m !== 'ANY' && $m !== $method) continue;
    $re = preg_replace(['~\{(\w+)\*\}~', '~\{(\w+)\}~'], ['(?P<$1>.*)', '(?P<$1>[^/]+)'], $pattern);
    if (!preg_match('~^' . $re . '$~u', $path, $mm)) continue;
    $params = array_filter($mm, 'is_string', ARRAY_FILTER_USE_KEY);
    require_once APP_ROOT . "/app/controllers/$ctrl.php";
    $func = $ctrl . '_' . $fn;
    if ($method === 'POST' && !in_array($ctrl, ['api', 'sync'], true)) verify_csrf();
    $func(...array_values($params));
    exit;
}
not_found();
