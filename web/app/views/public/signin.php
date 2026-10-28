<div class="split-auth">
  <?php require __DIR__ . '/_auth_left.php'; ?>
  <div class="right">
    <div class="tabs"><a class="on" href="<?= url('signin') ?>"><?= e(t('sign_in')) ?></a><a href="<?= url('activate') ?>"><?= e(t('register')) ?></a></div>
    <h1 style="font-size:30px"><?= e(t('welcome_back')) ?></h1>
    <p class="muted"><?= e(t('signin_intro')) ?></p>
    <form method="post" action="<?= url('signin') ?>" class="mt2" autocomplete="on">
      <?= csrf_field() ?>
      <div class="field"><label for="login"><?= e(t('card_or_mobile')) ?></label><input id="login" name="login" required value="<?= e(old('login')) ?>" placeholder="MMB-004521 / 03XX-XXXXXXX" autocomplete="username"></div>
      <div class="field"><div class="flex between"><label for="password"><?= e(t('password')) ?></label><a class="small" href="<?= url('activate') ?>"><?= e(t('forgot_password')) ?></a></div>
        <div class="pw-wrap"><input id="password" type="password" name="password" required autocomplete="current-password"><button type="button" data-pw aria-label="<?= e(t('show_password')) ?>"><?= icon('eye', 18) ?></button></div></div>
      <button class="btn btn-primary btn-lg btn-block"><?= e(t('sign_in')) ?></button>
    </form>
    <?php if (setting('sms_url')): ?>
    <form method="post" action="<?= url('signin/otp-request') ?>" class="mt2" id="otpform">
      <?= csrf_field() ?><input type="hidden" name="login" id="otp-login">
      <button class="btn btn-outline btn-block" onclick="document.getElementById('otp-login').value=document.getElementById('login').value"><?= icon('message', 18) ?> <?= e(t('signin_with_sms')) ?></button>
    </form>
    <?php endif; ?>
    <div class="card-surface mt3">
      <strong><?= e(t('first_time_online')) ?></strong>
      <p class="muted small mb2"><?= e(t('first_time_online_d')) ?></p>
      <div class="flex wrap"><a class="btn btn-primary btn-sm" href="<?= url('activate') ?>"><?= e(t('activate_account')) ?></a><a class="btn btn-ghost btn-sm" href="<?= url('membership') ?>"><?= e(t('not_member_apply')) ?></a></div>
    </div>
  </div>
</div>
