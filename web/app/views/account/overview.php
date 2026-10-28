<?php require __DIR__ . '/_open.php'; $name = member_display_name($m); ?>
<h1 style="font-size:30px"><?= e(t('greeting', ['name' => $m['firstname'] ?: $name])) ?></h1>
<div class="grid" style="grid-template-columns:1fr 340px">
  <div class="grid g4" style="align-content:start">
    <div class="stat-tile"><div class="n"><?= count($loans) ?></div><div class="l"><?= e(t('borrowed')) ?></div></div>
    <div class="stat-tile <?= $dueSoon ? 'amber' : '' ?>"><div class="n"><?= count($dueSoon) ?></div><div class="l"><?= e(t('due_soon')) ?></div></div>
    <div class="stat-tile"><div class="n"><?= count($holds) ?></div><div class="l"><?= e(t('reservations')) ?></div></div>
    <div class="stat-tile <?= $m['fines_due'] > 0 ? 'red' : 'green' ?>"><div class="n"><?= e(money($m['fines_due'])) ?></div><div class="l"><?= e(t('fines')) ?></div></div>
  </div>
  <a class="library-card" href="<?= url('account/card') ?>" style="text-decoration:none;padding:18px">
    <div class="head"><img src="<?= asset('img/logo-96.png') ?>" alt=""><div><strong><?= e(setting('short_name', 'MMB Public Library')) ?></strong><div class="lbl"><?= e(t('library_card')) ?></div></div></div>
    <div class="flex between mt2" style="position:relative;z-index:1"><div><div style="font-weight:700;font-size:18px"><?= e($name) ?></div><div class="mono"><?= e($m['cardnumber']) ?></div><div class="small" style="opacity:.8"><?= e(t('valid_until')) ?> <?= e(fmt_date($m['expiry_date'])) ?></div></div><span class="small"><?= e(t('view_full_card')) ?> →</span></div>
  </a>
</div>
<div class="grid g2 mt3">
  <div class="card"><div class="flex between"><h3 class="mb0"><?= e(t('due_soon')) ?></h3><a class="small" href="<?= url('account/loans') ?>"><?= e(t('view_all')) ?></a></div>
    <?php foreach (array_slice($loans, 0, 4) as $l) require __DIR__ . '/_loan_row.php'; ?>
    <?php if (!$loans): ?><div class="empty" style="padding:24px"><p><?= e(t('no_loans')) ?></p><a class="btn btn-primary btn-sm" href="<?= url('catalog') ?>"><?= e(t('search_catalog')) ?></a></div><?php endif; ?>
  </div>
  <div class="stack">
    <div class="card"><div class="flex between"><h3 class="mb0"><?= e(t('reservations')) ?></h3><a class="small" href="<?= url('account/holds') ?>"><?= e(t('view_all')) ?></a></div>
      <?php foreach (array_slice($holds, 0, 3) as $h) require __DIR__ . '/_hold_row.php'; ?>
      <?php if (!$holds): ?><p class="muted mt2 mb0"><?= e(t('no_holds')) ?></p><?php endif; ?></div>
    <div class="card"><div class="flex between"><h3 class="mb0"><?= e(t('notifications')) ?></h3><a class="small" href="<?= url('account/notifications') ?>"><?= e(t('view_all')) ?></a></div>
      <?php foreach ($notifs as $n): ?><div class="loan-row"><span style="color:<?= $n['is_read'] ? 'var(--muted)' : 'var(--blue)' ?>"><?= icon('bell', 18) ?></span><div class="info"><b class="small"><?= e($n['title']) ?></b><span class="tiny muted"><?= e(fmt_date($n['created_at'], true)) ?></span></div></div><?php endforeach; ?>
      <?php if (!$notifs): ?><p class="muted mt2 mb0"><?= e(t('all_caught_up')) ?></p><?php endif; ?></div>
  </div>
</div>
<style>@media(max-width:1100px){.account-layout .grid[style]{grid-template-columns:1fr!important}}</style>
<?php require __DIR__ . '/_close.php'; ?>
