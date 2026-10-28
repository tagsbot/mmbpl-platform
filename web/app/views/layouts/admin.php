<?php
$a = admin();
$path = current_path();
$cnt = $a ? [
  'applications' => (int)val('SELECT COUNT(*) FROM applications WHERE status="pending"'),
  'suggestions' => (int)val('SELECT COUNT(*) FROM suggestions WHERE status="review"'),
  'feedback' => (int)val('SELECT COUNT(*) FROM feedback WHERE status="new"'),
  'donations' => (int)val('SELECT COUNT(*) FROM donations WHERE status="new"'),
  'sync' => (int)val('SELECT COUNT(*) FROM actions WHERE status="failed"'),
] : [];
$menu = [
  'Overview' => [['admin', 'Dashboard', 'grid', 'dashboard'], ['admin/reports', 'Reports', 'chart', 'reports']],
  'Requests' => [['admin/r/applications', 'Membership applications', 'user-plus', 'applications', 'applications'], ['admin/r/suggestions', 'Book suggestions', 'lightbulb', 'suggestions', 'suggestions'], ['admin/r/donations', 'Donations', 'gift', 'donations', 'donations'], ['admin/r/feedback', 'Feedback', 'message', 'feedback', 'feedback']],
  'Content' => [['admin/r/events', 'Events', 'calendar', 'events'], ['admin/r/news', 'News', 'file', 'news'], ['admin/pages', 'Pages', 'book', 'pages'], ['admin/r/gallery', 'Gallery', 'image', 'gallery'], ['admin/r/exam', 'Exam corner', 'graduation', 'exam'], ['admin/r/archive', 'History archive', 'archive', 'archive']],
  'Library data' => [['admin/catalog', 'Catalog', 'books', 'catalog'], ['admin/members', 'Members', 'users', 'members'], ['admin/notifications', 'Notifications', 'bell', 'notifications'], ['admin/sync', 'Koha sync', 'sync', 'sync', 'sync']],
  'System' => [['admin/r/users', 'Staff & roles', 'shield', 'users'], ['admin/settings', 'Settings', 'settings', 'settings']],
];
?><!doctype html>
<html lang="en" dir="ltr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title><?= e(($title ?? 'Admin') . ' · MMBPL Admin') ?></title>
<link rel="icon" href="<?= asset('img/favicon.png') ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>"><link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head><body class="admin">
<div class="adm">
  <aside class="adm-side" id="admside">
    <a class="brand" href="<?= url('admin') ?>"><img src="<?= asset('img/logo-96.png') ?>" alt=""><span>MMBPL Admin<small style="color:#7F8DA3">Master control panel</small></span></a>
    <?php foreach ($menu as $group => $links): $vis = array_filter($links, fn($l) => can($l[3])); if (!$vis) continue; ?>
      <h6><?= e($group) ?></h6>
      <?php foreach ($vis as $l): $on = $l[0] === 'admin' ? $path === '/admin' : strpos($path, '/' . $l[0]) === 0; ?>
        <a href="<?= url($l[0]) ?>" class="<?= $on ? 'on' : '' ?>"><?= icon($l[2], 17) ?> <?= e($l[1]) ?><?php if (!empty($l[4]) && !empty($cnt[$l[4]])): ?><span class="cnt"><?= $cnt[$l[4]] ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </aside>
  <div class="adm-main">
    <header class="adm-top">
      <button class="icon-btn adm-menu" onclick="document.getElementById('admside').classList.toggle('open')" aria-label="Menu"><?= icon('menu', 18) ?></button>
      <span class="small"><span class="status-dot" style="background:<?= agent_online() ? 'var(--green)' : 'var(--red)' ?>"></span>Koha agent <?= agent_online() ? 'online' : 'offline' ?><?= setting('agent_last_seen') ? ' · last seen ' . e(fmt_date(setting('agent_last_seen'), true)) : '' ?></span>
      <div class="who"><a href="<?= url('/') ?>" target="_blank"><?= icon('external', 16) ?> View website</a><span class="muted"><?= e($a['name'] ?? '') ?> · <?= e(str_replace('_', ' ', $a['role'] ?? '')) ?></span><a href="<?= url('admin/password') ?>">Password</a><a href="<?= url('admin/logout') ?>" style="color:var(--red)">Sign out</a></div>
    </header>
    <div class="adm-body"><?= $content ?></div>
  </div>
</div>
<div class="toasts"><?php foreach (flashes() as [$type, $msg]): ?><div class="toast <?= e($type) ?>"><?= icon($type === 'success' ? 'check-circle' : 'alert') ?><span><?= e($msg) ?></span></div><?php endforeach; ?></div>
<script>window.MMBPL={base:<?= json_encode(base_path()) ?>,csrf:<?= json_encode(csrf_token()) ?>,rtl:false};</script>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body></html>
