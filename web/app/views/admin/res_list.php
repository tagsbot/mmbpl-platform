<?php $statusColors = ['pending' => 'badge-amber', 'review' => 'badge-amber', 'new' => 'badge-amber', 'approved' => 'badge-green', 'published' => 'badge-green', 'purchased' => 'badge-green', 'received' => 'badge-green', 'resolved' => 'badge-green', 'correction' => 'badge-blue', 'contacted' => 'badge-blue', 'in_progress' => 'badge-blue', 'rejected' => 'badge-slate', 'declined' => 'badge-slate', 'draft' => 'badge-slate']; ?>
<div class="page-head"><div><h1><?= e($r['title']) ?></h1><p class="muted mb0"><?= number_format($total) ?> records</p></div>
<div class="flex"><a class="btn btn-outline btn-sm" href="<?= url('admin/r/' . $r['key'] . '/export.csv') ?>"><?= icon('download', 16) ?> Export CSV</a><?php if (empty($r['no_create'])): ?><a class="btn btn-primary btn-sm" href="<?= url('admin/r/' . $r['key'] . '/new') ?>"><?= icon('plus', 16) ?> New <?= e(strtolower($r['singular'])) ?></a><?php endif; ?></div></div>
<form class="filters-bar" method="get">
  <input type="search" name="s" value="<?= e(input('s')) ?>" placeholder="Search…">
  <?php if (!empty($r['statuses'])): ?><select name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach ($r['statuses'] as $st): ?><option value="<?= $st ?>" <?= input('status') === $st ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $st))) ?></option><?php endforeach; ?></select><?php endif; ?>
  <button class="btn btn-outline btn-sm">Search</button>
</form>
<div class="table-wrap"><table class="table">
  <thead><tr><?php foreach ($r['list'] as $col => $label): ?><th><?= e($label) ?></th><?php endforeach; ?><th style="width:1%"></th></tr></thead>
  <tbody>
  <?php foreach ($list as $x): ?>
    <tr>
      <?php foreach ($r['list'] as $col => $label): $v = $x[$col] ?? ''; ?>
        <td>
          <?php if ($col === 'status'): ?><span class="badge <?= $statusColors[$v] ?? 'badge-slate' ?>"><?= e(str_replace('_', ' ', (string)$v)) ?></span>
          <?php elseif ($col === 'image'): ?><?php if ($v): ?><img class="thumb" src="<?= e(upload_url($v)) ?>" alt=""><?php endif; ?>
          <?php elseif ($col === 'active'): ?><?= $v ? '<span class="badge badge-green">Yes</span>' : '<span class="badge badge-slate">No</span>' ?>
          <?php elseif (in_array($col, ['created_at', 'event_start', 'last_login'], true)): ?><?= e(fmt_date((string)$v, true)) ?>
          <?php elseif ($col === '_regs'): ?><a href="<?= url('admin/events/' . $x['id'] . '/registrations') ?>"><?= e($v) ?></a>
          <?php elseif ($col === array_key_first($r['list'])): ?><a href="<?= url('admin/r/' . $r['key'] . '/' . $x['id']) ?>"><strong><?= e(mb_strimwidth((string)$v, 0, 70, '…')) ?></strong></a>
          <?php else: ?><?= e(mb_strimwidth((string)$v, 0, 60, '…')) ?><?php endif; ?>
        </td>
      <?php endforeach; ?>
      <td style="white-space:nowrap">
        <a class="btn btn-ghost btn-sm" href="<?= url('admin/r/' . $r['key'] . '/' . $x['id']) ?>"><?= icon('edit', 15) ?></a>
        <?php if (can('*') || $r['key'] !== 'users'): ?><form method="post" action="<?= url('admin/r/' . $r['key'] . '/' . $x['id'] . '/delete') ?>" style="display:inline" onsubmit="return confirm('Delete this record permanently?')"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" style="color:var(--red)"><?= icon('trash', 15) ?></button></form><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="<?= count($r['list']) + 1 ?>" class="muted" style="text-align:center;padding:40px">Nothing here yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php partial('pagination', ['total' => $total, 'perPage' => 25, 'page' => $page]); ?>
