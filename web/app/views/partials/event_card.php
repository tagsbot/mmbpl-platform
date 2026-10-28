<?php $d = $p['event_start'] ? strtotime($p['event_start']) : strtotime($p['created_at']); ?>
<a class="event-card" href="<?= url('events/' . $p['slug']) ?>">
  <div class="img" style="background-image:url('<?= e(post_image($p)) ?>')">
    <span class="date-badge"><b><?= date('d', $d) ?></b><span><?= date('M', $d) ?></span></span>
  </div>
  <div class="body">
    <span class="cat"><?= e($p['type'] === 'news' ? t('news') : ($p['category'] ? t('cat_' . $p['category']) : t('event'))) ?></span>
    <h3><?= e(tr($p, 'title')) ?></h3>
    <p class="muted small mb0"><?= e(mb_strimwidth(tr($p, 'excerpt'), 0, 140, '…')) ?></p>
    <?php if ($p['type'] === 'event'): ?>
      <div class="small muted mt1"><?= icon('clock', 14) ?> <?= e(fmt_date($p['event_start'], true)) ?><?php if ($p['venue']): ?> · <?= icon('map-pin', 14) ?> <?= e($p['venue']) ?><?php endif; ?></div>
    <?php endif; ?>
    <span class="more small mt1" style="margin-top:auto;font-weight:600;color:var(--blue)"><?= e(t('details')) ?> →</span>
  </div>
</a>
