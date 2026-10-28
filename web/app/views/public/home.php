<section class="hero">
  <div class="container">
    <span class="pill-note"><?= icon('book', 14) ?> <?= e(s('hero_note', 'Public Library · Mirpur (AJK)')) ?></span>
    <h1><?= e(s('hero_title', t('hero_title'))) ?></h1>
    <p class="sub"><?= e(s('hero_subtitle', t('hero_subtitle'))) ?></p>
    <form class="searchbar" action="<?= url('catalog') ?>" method="get" role="search">
      <label class="sr-only" for="field"><?= e(t('search_in')) ?></label>
      <select name="field" id="field">
        <option value="all"><?= e(t('f_all')) ?></option>
        <option value="title"><?= e(t('f_title')) ?></option>
        <option value="author"><?= e(t('f_author')) ?></option>
        <option value="subject"><?= e(t('f_subject')) ?></option>
        <option value="isbn">ISBN</option>
      </select>
      <label class="sr-only" for="q"><?= e(t('search_placeholder')) ?></label>
      <input type="search" name="q" id="q" placeholder="<?= e(t('search_placeholder')) ?>" autocomplete="off">
      <button class="btn btn-green btn-lg" type="submit"><?= icon('search') ?> <?= e(t('search')) ?></button>
    </form>
    <div class="quick-links">
      <span class="label"><?= e(t('popular')) ?>:</span>
      <a class="chip" href="<?= url('catalog?new=1&sort=newest') ?>"><?= e(t('new_arrivals')) ?></a>
      <a class="chip" href="<?= url('catalog?lang[]=ur') ?>"><?= e(t('urdu_books')) ?></a>
      <a class="chip" href="<?= url('exam-corner') ?>"><?= e(t('competitive_exams')) ?></a>
      <a class="chip" href="<?= url('catalog?subject=Children') ?>"><?= e(t('children')) ?></a>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container grid g4">
    <a class="service" href="<?= url('catalog') ?>"><span class="ico"><?= icon('search', 24) ?></span><h3><?= e(t('svc_search')) ?></h3><p><?= e(t('svc_search_d')) ?></p><span class="more"><?= e(t('svc_search_cta')) ?> →</span></a>
    <a class="service green" href="<?= url('account/loans') ?>"><span class="ico"><?= icon('refresh', 24) ?></span><h3><?= e(t('svc_renew')) ?></h3><p><?= e(t('svc_renew_d')) ?></p><span class="more"><?= e(t('svc_renew_cta')) ?> →</span></a>
    <a class="service" href="<?= url('membership') ?>"><span class="ico"><?= icon('user-plus', 24) ?></span><h3><?= e(t('become_member')) ?></h3><p><?= e(t('svc_member_d')) ?></p><span class="more"><?= e(t('apply_now')) ?> →</span></a>
    <a class="service green" href="<?= url('events') ?>"><span class="ico"><?= icon('calendar', 24) ?></span><h3><?= e(t('nav_events')) ?></h3><p><?= e(t('svc_events_d')) ?></p><span class="more"><?= e(t('explore_events')) ?> →</span></a>
  </div>
</section>

<?php if ($newArrivals): ?>
<section class="section">
  <div class="container">
    <div class="section-head">
      <div><div class="eyebrow"><?= e(t('fresh_acquisitions')) ?></div><h2 class="mb0"><?= e(t('new_arrivals')) ?></h2></div>
      <div class="flex">
        <a class="btn btn-ghost btn-sm" href="<?= url('catalog?new=1&sort=newest') ?>"><?= e(t('view_all')) ?></a>
        <div class="shelf-nav no-print"><button class="icon-btn" data-shelf="new" data-dir="-1" aria-label="Previous"><?= icon('chevron-left', 18, 'flip-rtl') ?></button><button class="icon-btn" data-shelf="new" data-dir="1" aria-label="Next"><?= icon('chevron-right', 18, 'flip-rtl') ?></button></div>
      </div>
    </div>
    <div class="shelf" id="shelf-new"><?php foreach ($newArrivals as $b) partial('book_tile', ['b' => $b]); ?></div>
  </div>
</section>
<?php endif; ?>

<?php if ($popular): ?>
<section class="section bg-surface">
  <div class="container">
    <div class="section-head">
      <div><div class="eyebrow"><?= e(t('reader_favourites')) ?></div><h2 class="mb0"><?= e(t('popular_this_month')) ?></h2></div>
      <a class="btn btn-ghost btn-sm" href="<?= url('catalog?sort=popular') ?>"><?= e(t('view_all')) ?></a>
    </div>
    <div class="results-grid"><?php foreach (array_slice($popular, 0, 4) as $b) partial('result_card', ['b' => $b + ['shelf' => null]]); ?></div>
  </div>
</section>
<?php endif; ?>

<?php if (array_sum($stats) > 0): ?>
<section class="stats-strip">
  <div class="container grid g4">
    <div class="stat-big"><div class="n" data-count="<?= (int)$stats['members'] ?>"><?= number_format($stats['members']) ?></div><div class="l"><?= e(t('stat_members')) ?></div></div>
    <div class="stat-big"><div class="n" data-count="<?= (int)$stats['books'] ?>"><?= number_format($stats['books']) ?></div><div class="l"><?= e(t('stat_books')) ?></div></div>
    <div class="stat-big"><div class="n" data-count="<?= (int)$stats['titles'] ?>"><?= number_format($stats['titles']) ?></div><div class="l"><?= e(t('stat_titles')) ?></div></div>
    <div class="stat-big"><div class="n" data-count="<?= (int)$stats['events'] ?>"><?= number_format($stats['events']) ?></div><div class="l"><?= e(t('stat_events')) ?></div></div>
  </div>
</section>
<?php endif; ?>

<?php if ($posts): ?>
<section class="section">
  <div class="container">
    <div class="section-head">
      <div><div class="eyebrow"><?= e(t('public_programs')) ?></div><h2 class="mb0"><?= e(t('latest_news_events')) ?></h2></div>
      <a class="btn btn-ghost btn-sm" href="<?= url('events') ?>"><?= e(t('view_all')) ?></a>
    </div>
    <div class="grid g3"><?php foreach ($posts as $p) partial('event_card', ['p' => $p]); ?></div>
  </div>
</section>
<?php endif; ?>

<section class="section bg-surface">
  <div class="container grid g2" style="align-items:center">
    <div>
      <div class="eyebrow"><?= e(t('visit_us')) ?></div>
      <h2><?= e(t('opening_hours_visit')) ?></h2>
      <p class="muted"><?= e(s('visit_text', t('visit_text_default'))) ?></p>
      <div class="grid g2 mt2">
        <div class="card"><div class="label"><?= icon('clock', 16) ?> <?= e(t('library_hours')) ?></div><p class="mb0 mt1"><?= e(hours_text()) ?></p><span class="open-chip mt1 <?= is_open_now() ? 'open' : 'closed' ?>"><?= e(is_open_now() ? t('open_now') : t('closed_now')) ?></span></div>
        <div class="card"><div class="label"><?= icon('map-pin', 16) ?> <?= e(t('address')) ?></div><p class="mb0 mt1"><?= e(s('address', 'Mirpur, Azad Jammu & Kashmir')) ?></p></div>
      </div>
      <div class="flex wrap mt3">
        <?php if (setting('phone')): ?><a class="btn btn-primary" href="tel:<?= e(setting('phone')) ?>"><?= icon('phone', 18) ?> <?= e(t('call')) ?> <span dir="ltr"><?= e(setting('phone')) ?></span></a><?php endif; ?>
        <a class="btn btn-outline" href="<?= url('about#visit') ?>"><?= icon('map-pin', 18) ?> <?= e(t('get_directions')) ?></a>
      </div>
    </div>
    <div>
      <iframe class="map-frame" loading="lazy" title="Map" src="https://maps.google.com/maps?q=<?= rawurlencode(setting('map_query', 'Mirpur, Azad Kashmir')) ?>&z=15&output=embed"></iframe>
    </div>
  </div>
</section>
