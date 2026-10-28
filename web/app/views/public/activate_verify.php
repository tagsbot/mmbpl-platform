<?php if ($step === 'otp'): ?>
<?php $action = url('activate/verify'); require __DIR__ . '/otp.php'; ?>
<?php else: ?>
<div class="split-auth">
  <?php require __DIR__ . '/_auth_left.php'; ?>
  <div class="right">
    <div class="steps"><div class="s done"><span><?= e(t('verify')) ?></span></div><div class="s on"><span><?= e(t('set_password')) ?></span></div></div>
    <h1 style="font-size:30px"><?= e(t('set_password')) ?></h1>
    <p class="muted"><?= e(t('set_password_intro')) ?></p>
    <form method="post" action="<?= url('activate/verify') ?>">
      <?= csrf_field() ?><input type="hidden" name="step" value="password">
      <div class="field"><label for="p1"><?= e(t('new_password')) ?></label><div class="pw-wrap"><input id="p1" type="password" name="password" minlength="8" required autocomplete="new-password"><button type="button" data-pw><?= icon('eye', 18) ?></button></div><span class="hint"><?= e(t('password_rule')) ?></span></div>
      <div class="field"><label for="p2"><?= e(t('confirm_password')) ?></label><input id="p2" type="password" name="password2" minlength="8" required autocomplete="new-password"></div>
      <button class="btn btn-primary btn-lg btn-block"><?= e(t('activate_and_signin')) ?></button>
    </form>
  </div>
</div>
<?php endif; ?>
