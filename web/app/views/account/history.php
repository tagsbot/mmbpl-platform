<?php require __DIR__ . '/_open.php'; ?>
<h1 style="font-size:30px"><?= e(t('history')) ?></h1>
<?php $grp = []; foreach ($history as $h) $grp[date('F Y', strtotime($h['returned_at'] ?: $h['issued_at'] ?: 'now'))][] = $h; ?>
<?php foreach ($grp as $mon => $list): ?>
  <h4 class="muted mt3"><?= e($mon) ?></h4>
  <div class="card"><?php foreach ($list as $h): ?><div class="loan-row"><?= book_cover($h, 'xs') ?><div class="info"><b><a href="<?= url('book/' . $h['biblio_id']) ?>" style="color:inherit"><?= e($h['title']) ?></a></b><span class="small muted"><?= e($h['author']) ?> · <?= e(fmt_date($h['issued_at'])) ?> → <?= e(fmt_date($h['returned_at'])) ?></span></div></div><?php endforeach; ?></div>
<?php endforeach; ?>
<?php if (!$history): ?><div class="card empty"><h3><?= e(t('no_history')) ?></h3></div><?php endif; ?>
<?php require __DIR__ . '/_close.php'; ?>
