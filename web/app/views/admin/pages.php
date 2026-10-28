<div class="page-head"><h1>Pages</h1></div>
<div class="table-wrap"><table class="table"><thead><tr><th>Page</th><th>Last saved</th><th></th></tr></thead><tbody>
<?php foreach ($pages as $slug => $label): ?><tr><td><strong><?= e($label) ?></strong><div class="tiny muted">/page/<?= e($slug) ?></div></td><td><?= isset($saved[$slug]) ? e(fmt_date($saved[$slug], true)) : '<span class="muted">Not written yet</span>' ?></td><td><a class="btn btn-outline btn-sm" href="<?= url('admin/pages/' . $slug) ?>"><?= icon('edit', 15) ?> Edit</a></td></tr><?php endforeach; ?>
</tbody></table></div>
<p class="muted small mt2">Hours, address, fees and home page texts are under <a href="<?= url('admin/settings') ?>">Settings</a>.</p>
