<div class="page-head"><h1>Change password</h1></div>
<form class="card" method="post" action="<?= url('admin/password') ?>" style="max-width:480px"><?= csrf_field() ?>
<div class="field"><label>Current password</label><input type="password" name="current" required></div>
<div class="field"><label>New password (10+ characters)</label><input type="password" name="password" minlength="10" required></div>
<button class="btn btn-primary">Change password</button></form>
