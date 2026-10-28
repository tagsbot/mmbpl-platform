<?php $cats = ['workshop', 'book_club', 'seminar', 'kids', 'exhibition']; ?>
<div class="page-hero"><div class="container"><div class="eyebrow"><?= e(t('public_programs')) ?></div><h1><?= e(t('news_and_events')) ?></h1><p class="muted mb0"><?= e(t('events_intro')) ?></p></div></div>
<div class="container section-sm">
  <div class="tabs">
    <?php foreach (['upcoming' => t('upcoming_events'), 'news' => t('news'), 'past' => t('past_events')] as $k => $l): ?><a href="<?= url('events?tab=' . $k) ?>" class="<?= $tab === $k ? 'on' : '' ?>"><?= e($l) ?></a><?php endforeach; ?>
  </div>
  <div class="two-col">
    <div>
      <?php if ($featured): $d = strtotime((string)$featured['event_start']); ?>
        <a class="featured-event mb3" href="<?= url('events/' . $featured['slug']) ?>" style="color:inherit;text-decoration:none">
          <div class="img" style="background-image:url('<?= e(post_image($featured)) ?>')"></div>
          <div class="body">
            <span class="badge badge-green"><?= e(t('featured')) ?></span>
            <h2 class="mt2"><?= e(tr($featured, 'title')) ?></h2>
            <p class="muted"><?= e(tr($featured, 'excerpt')) ?></p>
            <p class="small"><?= icon('calendar', 16) ?> <?= e(fmt_date($featured['event_start'], true)) ?><br><?= icon('map-pin', 16) ?> <?= e($featured['venue']) ?></p>
            <span class="btn btn-primary"><?= e(t('register')) ?></span>
          </div>
        </a>
      <?php endif; ?>
      <?php if ($tab !== 'news'): ?>
      <div class="chips mb3">
        <a class="chip <?= !$cat ? 'on' : '' ?>" href="<?= url('events?tab=' . $tab) ?>"><?= e(t('all')) ?></a>
        <?php foreach ($cats as $c): ?><a class="chip <?= $cat === $c ? 'on' : '' ?>" href="<?= url('events?tab=' . $tab . '&cat=' . $c) ?>"><?= e(t('cat_' . $c)) ?></a><?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if ($list): ?>
        <div class="grid g2"><?php foreach ($list as $p) partial('event_card', ['p' => $p]); ?></div>
        <?php partial('pagination', ['total' => $total - ($featured ? 1 : 0), 'perPage' => 9, 'page' => $page]); ?>
      <?php elseif (!$featured): ?>
        <div class="card empty"><img src="<?= asset('img/empty-books.svg') ?>" alt=""><h3><?= e(t('no_events')) ?></h3><p><?= e(t('no_events_hint')) ?></p></div>
      <?php endif; ?>
    </div>
    <aside class="stack sticky">
      <div class="card">
        <h3><?= e(date('F Y')) ?></h3>
        <div class="mini-cal">
          <?php foreach (['M', 'T', 'W', 'T', 'F', 'S', 'S'] as $dname): ?><span class="h"><?= $dname ?></span><?php endforeach; ?>
          <?php $first = (int)date('N', strtotime(date('Y-m-01'))); for ($i = 1; $i < $first; $i++) echo '<span></span>';
          for ($dd = 1; $dd <= (int)date('t'); $dd++): $ds = date('Y-m-') . str_pad((string)$dd, 2, '0', STR_PAD_LEFT); ?>
            <span class="d <?= in_array($ds, $eventDays, true) ? 'ev' : '' ?> <?= $ds === date('Y-m-d') ? 'today' : '' ?>"><?= $dd ?></span>
          <?php endfor; ?>
        </div>
      </div>
      <?php if (setting('facebook_url')): ?>
      <div class="card-surface"><h3><?= icon('facebook') ?> <?= e(t('follow_us')) ?></h3><p class="muted small"><?= e(t('follow_us_d')) ?></p><a class="btn btn-primary btn-sm" href="<?= e(setting('facebook_url')) ?>" target="_blank" rel="noopener">Facebook</a></div>
      <?php endif; ?>
    </aside>
  </div>
</div>
