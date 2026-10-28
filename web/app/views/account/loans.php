<?php require __DIR__ . '/_open.php'; ?>
<div class="flex between wrap"><h1 style="font-size:30px"><?= e(t('borrowed_books')) ?></h1>
<?php if ($loans): ?><form method="post" action="<?= url('account/renew-all') ?>"><?= csrf_field() ?><button class="btn btn-primary"><?= icon('refresh', 18) ?> <?= e(t('renew_all')) ?></button></form><?php endif; ?></div>
<div class="card"><?php foreach ($loans as $l) require __DIR__ . '/_loan_row.php'; ?>
<?php if (!$loans): ?><div class="empty"><img src="<?= asset('img/empty-books.svg') ?>" alt=""><h3><?= e(t('no_loans')) ?></h3><a class="btn btn-primary" href="<?= url('catalog') ?>"><?= e(t('search_catalog')) ?></a></div><?php endif; ?></div>
<p class="small muted mt2"><?= icon('info', 14) ?> <?= e(t('renew_note')) ?></p>
<?php require __DIR__ . '/_close.php'; ?>
