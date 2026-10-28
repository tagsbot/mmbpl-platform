<?php require __DIR__ . '/_open.php'; $ico = ['due' => 'clock', 'overdue' => 'alert', 'hold' => 'bookmark', 'renew' => 'refresh', 'event' => 'calendar', 'membership' => 'card', 'library' => 'info']; ?>
<div class="flex between wrap"><h1 style="font-size:30px"><?= e(t('notifications')) ?></h1>
<form method="post" action="<?= url('account/notifications/read') ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= icon('check', 16) ?> <?= e(t('mark_all_read')) ?></button></form></div>
<div class="chips mb2"><?php foreach (['' => t('all'), 'books' => t('books'), 'event' => t('nav_events'), 'library' => t('library')] as $k => $l): ?><a class="chip <?= $filter === $k ? 'on' : '' ?>" href="<?= url('account/notifications' . ($k ? '?f=' . $k : '')) ?>"><?= e($l) ?></a><?php endforeach; ?></div>
<div class="card">
<?php foreach ($list as $n): ?>
  <div class="loan-row"><span class="ico" style="width:40px;height:40px;border-radius:50%;background:var(--blue-50);color:var(--blue);display:grid;place-items:center;flex:0 0 40px"><?= icon($ico[$n['type']] ?? 'bell', 18) ?></span>
    <div class="info"><b><?= e($n['title']) ?></b><?php if ($n['body']): ?><span class="small muted"><?= e($n['body']) ?></span><?php endif; ?><div class="tiny muted"><?= e(fmt_date($n['created_at'], true)) ?></div></div>
    <?php if (!$n['is_read']): ?><span style="width:10px;height:10px;border-radius:50%;background:var(--blue)" title="Unread"></span><?php endif; ?>
    <?php if ($n['link']): ?><a class="btn btn-ghost btn-sm" href="<?= url($n['link']) ?>"><?= e(t('open')) ?></a><?php endif; ?>
  </div>
<?php endforeach; ?>
<?php if (!$list): ?><div class="empty"><?= icon('bell', 40) ?><h3><?= e(t('all_caught_up')) ?></h3><p><?= e(t('all_caught_up_d')) ?></p></div><?php endif; ?>
</div>
<?php require __DIR__ . '/_close.php'; ?>
