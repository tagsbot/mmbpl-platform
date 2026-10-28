<div class="split-auth">
  <?php require __DIR__ . '/_auth_left.php'; ?>
  <div class="right">
    <div class="tabs"><a href="<?= url('signin') ?>"><?= e(t('sign_in')) ?></a><a class="on" href="<?= url('activate') ?>"><?= e(t('register')) ?></a></div>
    <h1 style="font-size:30px"><?= e(t('activate_account')) ?></h1>
    <p class="muted"><?= e(t('activate_intro')) ?></p>
    <form method="post" action="<?= url('activate') ?>" class="mt2">
      <?= csrf_field() ?>
      <div class="field"><label for="cardnumber"><?= e(t('card_number')) ?></label><input id="cardnumber" name="cardnumber" required value="<?= e(old('cardnumber')) ?>" placeholder="MMB-004521"></div>
      <div class="field"><label for="phone"><?= e(t('mobile_number')) ?></label><input id="phone" type="tel" name="phone" required value="<?= e(old('phone')) ?>" placeholder="03XX-XXXXXXX" dir="ltr"><span class="hint"><?= e(t('mobile_on_record')) ?></span></div>
      <div class="field"><label for="dob"><?= e(t('date_of_birth')) ?></label><input id="dob" type="date" name="dob" value="<?= e(old('dob')) ?>"></div>
      <button class="btn btn-primary btn-lg btn-block"><?= e(t('continue')) ?></button>
    </form>
    <div class="card-surface mt3 flex between wrap">
      <div><strong><?= e(t('not_member_yet')) ?></strong><div class="muted small"><?= e(t('not_member_yet_d')) ?></div></div>
      <a class="btn btn-green btn-sm" href="<?= url('membership') ?>"><?= e(t('apply_for_membership')) ?></a>
    </div>
  </div>
</div>
