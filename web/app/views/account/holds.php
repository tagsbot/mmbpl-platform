<?php require __DIR__ . '/_open.php'; ?>
<h1 style="font-size:30px"><?= e(t('reservations')) ?></h1>
<div class="card"><?php foreach ($holds as $h) require __DIR__ . '/_hold_row.php'; ?>
<?php if (!$holds): ?><div class="empty"><img src="<?= asset('img/empty-books.svg') ?>" alt=""><h3><?= e(t('no_holds')) ?></h3><p><?= e(t('no_holds_d')) ?></p><a class="btn btn-primary" href="<?= url('catalog') ?>"><?= e(t('search_catalog')) ?></a></div><?php endif; ?></div>
<?php require __DIR__ . '/_close.php'; ?>
