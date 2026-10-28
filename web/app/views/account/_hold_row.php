<div class="loan-row">
  <?= book_cover($h, 'sm') ?>
  <div class="info"><b><a href="<?= url('book/' . $h['biblio_id']) ?>" style="color:inherit"><?= e($h['title']) ?></a></b><span class="small muted"><?= e($h['author']) ?></span>
    <div class="small mt1">
      <?php if ($h['status'] === 'ready'): ?><span class="badge badge-green"><?= e(t('ready_for_pickup')) ?></span> <?= $h['pickup_by'] ? e(t('collect_by', ['date' => fmt_date($h['pickup_by'])])) : '' ?>
      <?php elseif ($h['status'] === 'requested'): ?><span class="badge badge-blue"><?= icon('sync', 12) ?> <?= e(t('hold_requested')) ?></span>
      <?php else: ?><span class="badge badge-amber"><?= e(t('waiting')) ?></span> <?= $h['queue_position'] ? e(t('queue_position', ['n' => $h['queue_position']])) : '' ?><?php endif; ?>
    </div>
  </div>
  <form method="post" action="<?= url('account/hold/' . $h['id'] . '/cancel') ?>" onsubmit="return confirm('<?= e(t('confirm_cancel_hold')) ?>')"><?= csrf_field() ?><button class="btn btn-danger btn-sm"><?= e(t('cancel')) ?></button></form>
</div>
