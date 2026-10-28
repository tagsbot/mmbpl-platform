<div class="loan-row">
  <?= book_cover($l, 'sm') ?>
  <div class="info"><b><a href="<?= url('book/' . $l['biblio_id']) ?>" style="color:inherit"><?= e($l['title']) ?></a></b><span class="small muted"><?= e($l['author']) ?></span>
    <div class="small mt1"><?= e(t('due_date')) ?>: <strong><?= e(fmt_date($l['due_date'])) ?></strong> <?php partial('countdown', ['due' => $l['due_date']]); ?> <?php if ($l['renewals']): ?><span class="muted">· <?= e(t('renewed_times', ['n' => $l['renewals']])) ?></span><?php endif; ?></div>
    <?php if (!$l['renewable']): ?><div class="small mt1" style="color:var(--red)"><?= icon('alert', 14) ?> <?= e($l['not_renewable_reason'] === 'on_reserve' ? t('err_cannot_renew_reserved') : t('err_cannot_renew')) ?></div><?php endif; ?>
  </div>
  <?php if ($l['renew_pending']): ?><span class="badge badge-blue"><?= icon('sync', 14) ?> <?= e(t('renew_pending')) ?></span>
  <?php else: ?><form method="post" action="<?= url('account/renew/' . $l['id']) ?>"><?= csrf_field() ?><button class="btn btn-outline btn-sm" <?= !$l['renewable'] ? 'disabled' : '' ?>><?= icon('refresh', 16) ?> <?= e(t('renew')) ?></button></form><?php endif; ?>
</div>
