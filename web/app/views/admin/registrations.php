<div class="page-head"><div><a class="small" href="<?= url('admin/r/events') ?>">← Events</a><h1><?= e($e['title']) ?></h1><p class="muted mb0"><?= e(fmt_date($e['event_start'], true)) ?> · <?= array_sum(array_column($regs, 'seats')) ?> seats booked<?= $e['seats'] ? ' of ' . (int)$e['seats'] : '' ?></p></div>
<a class="btn btn-outline btn-sm" href="?format=csv"><?= icon('download', 16) ?> Download CSV</a></div>
<div class="table-wrap"><table class="table"><thead><tr><th>Reg. no</th><th>Name</th><th>Mobile</th><th>Seats</th><th>Registered</th></tr></thead><tbody>
<?php foreach ($regs as $r): ?><tr><td class="mono"><?= e($r['reg_no']) ?></td><td><?= e($r['name']) ?></td><td dir="ltr"><?= e($r['phone']) ?></td><td><?= (int)$r['seats'] ?></td><td><?= e(fmt_date($r['created_at'], true)) ?></td></tr><?php endforeach; ?>
<?php if (!$regs): ?><tr><td colspan="5" class="muted" style="text-align:center;padding:32px">No registrations yet.</td></tr><?php endif; ?>
</tbody></table></div>
