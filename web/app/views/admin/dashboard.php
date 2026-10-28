<div class="page-head"><div><h1>Dashboard</h1><p class="muted mb0">Overview of the website, apps and Koha sync.</p></div>
<div class="flex"><a class="btn btn-primary btn-sm" href="<?= url('admin/r/events/new') ?>"><?= icon('plus', 16) ?> New event</a><a class="btn btn-outline btn-sm" href="<?= url('admin/notifications') ?>"><?= icon('bell', 16) ?> Send notification</a></div></div>
<?php if (setting('has_demo') === '1' && can('settings')): ?>
<div class="alert alert-warn"><?= icon('alert') ?><div style="flex:1"><strong>Demo data is loaded.</strong> Sample books, members and events are shown so you can try the site. Remove them before the Koha data arrives.</div>
<form method="post" action="<?= url('admin/demo/remove') ?>" onsubmit="return confirm('Remove all demo books, members, events and archive items?')"><?= csrf_field() ?><button class="btn btn-danger btn-sm">Remove demo data</button></form></div>
<?php endif; ?>
<?php if (!setting('agent_key_hash') && can('settings')): ?><div class="alert alert-info"><?= icon('info') ?><div>Koha is not connected yet. Go to <a href="<?= url('admin/sync') ?>">Koha sync</a> to create the agent key, then install the Sync Agent on the library computer.</div></div><?php endif; ?>
<div class="grid g6 mb3">
  <a class="kpi" href="<?= url('admin/catalog') ?>"><div class="n"><?= number_format($stats['titles']) ?></div><div class="l">Titles · <?= number_format($stats['items']) ?> copies</div></a>
  <a class="kpi" href="<?= url('admin/members') ?>"><div class="n"><?= number_format($stats['members']) ?></div><div class="l">Members · <?= number_format($stats['online']) ?> online</div></a>
  <div class="kpi"><div class="n"><?= number_format($stats['loans']) ?></div><div class="l">Books on loan</div></div>
  <div class="kpi <?= $stats['overdue'] ? 'bad' : 'ok' ?>"><div class="n"><?= number_format($stats['overdue']) ?></div><div class="l">Overdue loans</div></div>
  <div class="kpi"><div class="n"><?= number_format($stats['holds']) ?></div><div class="l">Active reservations</div></div>
  <a class="kpi" href="<?= url('admin/r/events') ?>"><div class="n"><?= number_format($stats['events']) ?></div><div class="l">Upcoming events</div></a>
</div>
<div class="grid" style="grid-template-columns:1fr 1fr 1fr">
  <div class="card"><h3>Needs attention</h3>
    <?php foreach ([['admin/r/applications?status=pending', 'Membership applications to review', $stats['apps']], ['admin/r/suggestions?status=review', 'Book suggestions to review', $stats['sugg']], ['admin/r/feedback?status=new', 'New feedback & complaints', $stats['fb']], ['admin/r/donations?status=new', 'New donation offers', $stats['don']], ['admin/sync', 'Requests waiting for Koha', $stats['pending_actions']], ['admin/sync', 'Failed Koha requests', $stats['failed_actions']]] as [$u, $l, $n]): ?>
      <div class="loan-row"><div class="info"><?= e($l) ?></div><span class="badge <?= $n ? 'badge-amber' : 'badge-green' ?>"><?= $n ?></span><a class="btn btn-ghost btn-sm" href="<?= url($u) ?>">Open</a></div>
    <?php endforeach; ?>
  </div>
  <div class="card"><h3>Most borrowed</h3>
    <?php $max = max(1, (int)($popular[0]['popularity'] ?? 1)); foreach ($popular as $p): ?><div class="bar"><span class="t" title="<?= e($p['title']) ?>"><?= e($p['title']) ?></span><span class="track"><span class="fill" style="width:<?= round($p['popularity'] / $max * 100) ?>%"></span></span><span class="v"><?= (int)$p['popularity'] ?></span></div><?php endforeach; ?>
    <?php if (!$popular): ?><p class="muted">No data yet.</p><?php endif; ?>
    <h3 class="mt3">Catalog by language</h3>
    <?php $tot = max(1, array_sum(array_column($byLang, 'n'))); foreach ($byLang as $l): ?><div class="bar"><span class="t"><?= e(strtoupper($l['language'])) ?></span><span class="track"><span class="fill" style="width:<?= round($l['n'] / $tot * 100) ?>%;background:var(--green)"></span></span><span class="v"><?= (int)$l['n'] ?></span></div><?php endforeach; ?>
  </div>
  <div class="card"><h3>Latest requests from members</h3>
    <?php foreach ($recentActions as $ac): ?><div class="loan-row"><div class="info"><b class="small"><?= e(str_replace('_', ' ', $ac['type'])) ?></b><span class="tiny muted"><?= e($ac['cardnumber'] ?? '') ?> · <?= e(fmt_date($ac['created_at'], true)) ?></span></div><span class="badge <?= ['done' => 'badge-green', 'failed' => 'badge-red', 'pending' => 'badge-amber', 'processing' => 'badge-blue'][$ac['status']] ?>"><?= e($ac['status']) ?></span></div><?php endforeach; ?>
    <?php if (!$recentActions): ?><p class="muted">No requests yet.</p><?php endif; ?>
    <h3 class="mt3">Online account activations</h3>
    <?php $mx = max(1, ...array_map('intval', array_column($signups, 'n') ?: [1])); foreach ($signups as $s): ?><div class="bar"><span class="t"><?= e($s['m']) ?></span><span class="track"><span class="fill" style="width:<?= round($s['n'] / $mx * 100) ?>%"></span></span><span class="v"><?= (int)$s['n'] ?></span></div><?php endforeach; ?>
  </div>
</div>
<style>@media(max-width:1200px){.adm-body .grid[style]{grid-template-columns:1fr!important}}</style>
