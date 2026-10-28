<?php
$m = member();
$path = current_path();
$nav = [
    ['/', t('nav_home')],
    ['/catalog', t('nav_catalog')],
    ['/exam-corner', t('nav_services')],
    ['/events', t('nav_events')],
    ['/about', t('nav_about')],
    ['/contact', t('nav_contact')],
];
$siteName = s('site_name', 'Mian Muhammad Bakhsh Public Library');
$pageTitle = isset($title) && $title ? $title . ' · ' . setting('short_name', 'MMB Public Library') : $siteName;
$unread = $m ? unread_count((int)$m['id']) : 0;
?><!doctype html>
<html lang="<?= lang() ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDesc ?? s('site_tagline', 'Search the catalog, reserve and renew books, and join events at Mian Muhammad Bakhsh Public Library, Mirpur (AJK).')) ?>">
<meta name="theme-color" content="#0B4DA2">
<link rel="icon" href="<?= asset('img/favicon.png') ?>">
<link rel="apple-touch-icon" href="<?= asset('img/logo-192.png') ?>">
<link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:image" content="<?= e(absolute_url('assets/img/logo-512.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<a class="skip" href="#main"><?= e(t('skip_to_content')) ?></a>
<header class="topbar">
  <div class="container">
    <a class="brand" href="<?= url('/') ?>">
      <img src="<?= asset('img/logo-96.png') ?>" alt="" width="48" height="48">
      <span><?= e(s('site_name_short', 'Mian Muhammad Bakhsh Public Library')) ?><small><?= e(s('site_place', 'Mirpur, Azad Jammu & Kashmir')) ?></small></span>
    </a>
    <button class="menu-btn" aria-label="<?= e(t('menu')) ?>" onclick="document.querySelector('.nav').classList.toggle('open')"><?= icon('menu') ?></button>
    <nav class="nav" aria-label="Main">
      <?php foreach ($nav as [$href, $label]):
        $on = $href === '/' ? $path === '/' : strpos($path, $href) === 0; ?>
        <a href="<?= url($href) ?>" class="<?= $on ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <a class="only-sm" href="<?= url(is_rtl() ? 'lang/en' : 'lang/ur') ?>"><?= is_rtl() ? 'English' : '<span class="ur">اردو</span>' ?></a>
      <?php if (!$m): ?><a class="only-sm" href="<?= url('signin') ?>"><?= e(t('sign_in')) ?></a><?php endif; ?>
    </nav>
    <div class="header-actions">
      <span class="lang-toggle hide-sm" role="group" aria-label="Language">
        <a href="<?= url('lang/en') ?>" class="<?= !is_rtl() ? 'on' : '' ?>" lang="en">English</a>
        <a href="<?= url('lang/ur') ?>" class="<?= is_rtl() ? 'on' : '' ?> ur" lang="ur">اردو</a>
      </span>
      <?php if ($m): ?>
        <a class="bell" href="<?= url('account/notifications') ?>" aria-label="<?= e(t('notifications')) ?>"><?= icon('bell') ?><?php if ($unread): ?><span class="count"><?= $unread ?></span><?php endif; ?></a>
        <a class="btn btn-primary btn-sm" href="<?= url('account') ?>"><?= icon('user', 16) ?> <?= e(t('my_account')) ?></a>
      <?php else: ?>
        <a class="btn btn-outline btn-sm hide-sm" href="<?= url('signin') ?>"><?= e(t('sign_in')) ?></a>
        <a class="btn btn-primary btn-sm" href="<?= url('membership') ?>"><?= e(t('join_library')) ?></a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main id="main">
<?= $content ?>
</main>
<footer class="footer">
  <div class="container">
    <div class="grid g4">
      <div>
        <a class="brand" href="<?= url('/') ?>"><img src="<?= asset('img/logo-96.png') ?>" alt="" width="44" height="44"><span><?= e(setting('short_name', 'MMB Public Library')) ?></span></a>
        <p class="mt2 small"><?= e(s('footer_about', 'A public library for the people of Mirpur and Azad Jammu & Kashmir: books, reading spaces, local history and learning programs for every age.')) ?></p>
        <?php if (setting('facebook_url')): ?><a class="chip" style="background:transparent;color:#fff;border-color:rgba(255,255,255,.3)" href="<?= e(setting('facebook_url')) ?>" target="_blank" rel="noopener"><?= icon('facebook', 16) ?> Facebook</a><?php endif; ?>
      </div>
      <div>
        <h4><?= e(t('quick_links')) ?></h4>
        <ul>
          <li><a href="<?= url('catalog') ?>"><?= e(t('search_catalog')) ?></a></li>
          <li><a href="<?= url('membership') ?>"><?= e(t('become_member')) ?></a></li>
          <li><a href="<?= url('suggest') ?>"><?= e(t('suggest_a_book')) ?></a></li>
          <li><a href="<?= url('exam-corner') ?>"><?= e(t('exam_corner')) ?></a></li>
          <li><a href="<?= url('archive') ?>"><?= e(t('local_history')) ?></a></li>
        </ul>
      </div>
      <div>
        <h4><?= e(t('contact_details')) ?></h4>
        <ul>
          <li><?= icon('map-pin', 16) ?> <?= e(s('address', 'Mirpur, Azad Jammu & Kashmir')) ?></li>
          <?php if (setting('phone')): ?><li><?= icon('phone', 16) ?> <a href="tel:<?= e(setting('phone')) ?>" dir="ltr"><?= e(setting('phone')) ?></a></li><?php endif; ?>
          <?php if (setting('email')): ?><li><?= icon('mail', 16) ?> <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></li><?php endif; ?>
        </ul>
      </div>
      <div>
        <h4><?= e(t('library_hours')) ?></h4>
        <ul>
          <li><?= icon('clock', 16) ?> <?= e(hours_text()) ?></li>
          <li><span class="open-chip <?= is_open_now() ? 'open' : 'closed' ?>"><?= e(is_open_now() ? t('open_now') : t('closed_now')) ?></span></li>
        </ul>
      </div>
    </div>
    <div class="bottom">
      <span>© <?= date('Y') ?> <?= e($siteName) ?>. <?= e(t('rights')) ?></span>
      <span><a href="<?= url('page/privacy') ?>"><?= e(t('privacy')) ?></a> · <a href="<?= url('contact') ?>"><?= e(t('feedback')) ?></a></span>
    </div>
  </div>
</footer>
<div class="toasts" id="toasts">
<?php foreach (flashes() as [$type, $msg]): ?>
  <div class="toast <?= e($type) ?>" role="status"><?= icon($type === 'success' ? 'check-circle' : ($type === 'error' ? 'alert' : 'info')) ?><span><?= e($msg) ?></span></div>
<?php endforeach; ?>
</div>
<script>window.MMBPL={base:<?= json_encode(base_path()) ?>,csrf:<?= json_encode(csrf_token()) ?>,rtl:<?= is_rtl() ? 'true' : 'false' ?>};</script>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?php if (!empty($scripts)) foreach ($scripts as $sc): ?><script src="<?= asset($sc) ?>" defer></script><?php endforeach; ?>
</body>
</html>
