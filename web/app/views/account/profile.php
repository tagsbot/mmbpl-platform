<?php require __DIR__ . '/_open.php'; $tg = fn($k) => $m[$k] ? 'checked' : ''; ?>
<h1 style="font-size:30px"><?= e(t('profile_settings')) ?></h1>
<form class="card" method="post" action="<?= url('account/profile') ?>">
  <?= csrf_field() ?>
  <h3><?= e(t('contact_details')) ?></h3>
  <div class="form-grid">
    <div class="field"><label><?= e(t('full_name')) ?></label><input value="<?= e(member_display_name($m)) ?>" disabled></div>
    <div class="field"><label><?= e(t('card_number')) ?></label><input value="<?= e($m['cardnumber']) ?>" disabled dir="ltr"></div>
    <div class="field"><label><?= e(t('mobile_number')) ?></label><input name="phone" type="tel" value="<?= e($m['phone']) ?>" dir="ltr"></div>
    <div class="field"><label><?= e(t('email')) ?></label><input name="email" type="email" value="<?= e($m['email']) ?>" dir="ltr"></div>
    <div class="field full"><label><?= e(t('address')) ?></label><input name="address" placeholder="<?= e(t('address_change_hint')) ?>"></div>
  </div>
  <p class="small muted"><?= icon('info', 14) ?> <?= e(t('contact_change_note')) ?></p>
  <h3 class="mt3"><?= e(t('language')) ?></h3>
  <div class="chips"><label class="chip"><input type="radio" name="lang" value="en" <?= $m['lang'] !== 'ur' ? 'checked' : '' ?>> English</label><label class="chip ur"><input type="radio" name="lang" value="ur" <?= $m['lang'] === 'ur' ? 'checked' : '' ?>> اردو</label></div>
  <h3 class="mt3"><?= e(t('notification_prefs')) ?></h3>
  <div class="grid g2">
    <div><label class="check"><input type="checkbox" name="notify_due" <?= $tg('notify_due') ?>> <?= e(t('np_due')) ?></label><label class="check"><input type="checkbox" name="notify_holds" <?= $tg('notify_holds') ?>> <?= e(t('np_holds')) ?></label><label class="check"><input type="checkbox" name="notify_events" <?= $tg('notify_events') ?>> <?= e(t('np_events')) ?></label></div>
    <div><label class="check"><input type="checkbox" name="notify_push" <?= $tg('notify_push') ?>> <?= e(t('np_push')) ?></label><label class="check"><input type="checkbox" name="notify_sms" <?= $tg('notify_sms') ?>> SMS</label><label class="check"><input type="checkbox" name="notify_whatsapp" <?= $tg('notify_whatsapp') ?>> WhatsApp</label></div>
  </div>
  <button class="btn btn-primary mt3"><?= e(t('save_changes')) ?></button>
</form>
<form class="card mt3" method="post" action="<?= url('account/password') ?>">
  <?= csrf_field() ?><h3><?= e(t('change_password')) ?></h3>
  <div class="form-grid">
    <div class="field full"><label><?= e(t('current_password')) ?></label><input type="password" name="current" required autocomplete="current-password"></div>
    <div class="field"><label><?= e(t('new_password')) ?></label><input type="password" name="password" minlength="8" required autocomplete="new-password"></div>
    <div class="field"><label><?= e(t('confirm_password')) ?></label><input type="password" name="password2" minlength="8" required autocomplete="new-password"></div>
  </div>
  <button class="btn btn-outline"><?= e(t('change_password')) ?></button>
</form>
<?php require __DIR__ . '/_close.php'; ?>
