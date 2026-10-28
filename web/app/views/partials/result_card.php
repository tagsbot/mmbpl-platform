<?php $av = $b['av']; ?>
<article class="result">
  <a href="<?= url('book/' . $b['id']) ?>" aria-hidden="true" tabindex="-1"><?= book_cover($b, 'sm') ?></a>
  <div>
    <div class="flex wrap between">
      <?= status_badge($av) ?>
      <?php if ($b['call_number']): ?><span class="call-tag"><?= e($b['call_number']) ?></span><?php endif; ?>
    </div>
    <h3><a href="<?= url('book/' . $b['id']) ?>"><?= e($b['title']) ?></a></h3>
    <div class="muted"><?= e($b['author']) ?><?= $b['pub_year'] ? ' · ' . e($b['pub_year']) : '' ?></div>
    <div class="meta">
      <span class="badge badge-slate"><?= e(lang_label($b['language'])) ?></span>
      <?php if (!empty($b['shelf'])): ?><span><?= icon('map-pin', 14) ?> <?= e($b['shelf']) ?></span><?php endif; ?>
      <span><?= icon('books', 14) ?> <?= e(t('copies_available', ['n' => $av['available'], 'total' => $av['total']])) ?></span>
    </div>
    <div class="actions">
      <a class="btn btn-outline btn-sm" href="<?= url('book/' . $b['id']) ?>"><?= e(t('view_details')) ?></a>
      <?php if (member()): ?>
        <form method="post" action="<?= url('account/reserve/' . $b['id']) ?>"><?= csrf_field() ?><button class="btn btn-primary btn-sm"><?= icon('bookmark') ?><?= e(t('reserve')) ?></button></form>
      <?php else: ?>
        <a class="btn btn-primary btn-sm" href="<?= url('signin') ?>" title="<?= e(t('sign_in_to_reserve')) ?>"><?= icon('lock', 14) ?> <?= e(t('reserve')) ?></a>
      <?php endif; ?>
    </div>
  </div>
</article>
