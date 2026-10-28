<?php require __DIR__ . '/_open.php'; ?>
<h1 style="font-size:30px"><?= e(t('my_list')) ?></h1>
<?php if ($list): ?><div class="grid g4"><?php foreach ($list as $b) partial('book_tile', ['b' => $b]); ?></div>
<?php else: ?><div class="card empty"><?= icon('heart', 40) ?><h3><?= e(t('list_empty')) ?></h3><p><?= e(t('list_empty_d')) ?></p></div><?php endif; ?>
<?php require __DIR__ . '/_close.php'; ?>
