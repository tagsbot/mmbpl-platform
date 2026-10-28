<div class="split-auth">
  <?php require __DIR__ . '/_auth_left.php'; ?>
  <div class="right" style="text-align:center">
    <h1 style="font-size:30px"><?= e(t('verify_code')) ?></h1>
    <p class="muted"><?= e(t('otp_sent_to', ['phone' => $phoneMasked])) ?></p>
    <?php if (!empty($demoCode)): ?><div class="alert alert-warn"><?= e(t('demo_otp_note')) ?> <strong class="mono"><?= e($demoCode) ?></strong></div><?php endif; ?>
    <form method="post" action="<?= e($action) ?>" class="mt2">
      <?= csrf_field() ?><input type="hidden" name="step" value="otp">
      <div class="otp-boxes" data-otp><?php for ($i = 0; $i < 6; $i++): ?><input name="code[]" inputmode="numeric" maxlength="1" pattern="[0-9]" required aria-label="Digit <?= $i + 1 ?>"><?php endfor; ?></div>
      <button class="btn btn-primary btn-lg btn-block mt3"><?= e(t('verify')) ?></button>
    </form>
    <p class="small muted mt2"><?= e(t('otp_expiry')) ?></p>
  </div>
</div>
