<div class="page-hero"><div class="container"><div class="eyebrow"><?= e(t('join_library')) ?></div><h1><?= e(t('membership_application')) ?></h1><p class="muted mb0"><?= e(t('membership_intro')) ?></p></div></div>
<div class="container section-sm">
  <div class="two-col">
    <form class="card" method="post" action="<?= url('membership') ?>" enctype="multipart/form-data" data-steps>
      <?= csrf_field() ?>
      <div class="steps"><div class="s on"><span><?= e(t('step_personal')) ?></span></div><div class="s"><span><?= e(t('step_contact')) ?></span></div><div class="s"><span><?= e(t('step_type_docs')) ?></span></div><div class="s"><span><?= e(t('step_review')) ?></span></div></div>

      <div class="form-step on">
        <h3><?= e(t('step_personal')) ?></h3>
        <div class="form-grid">
          <div class="field full"><label><?= e(t('full_name')) ?> *</label><input name="full_name" required value="<?= e(old('full_name')) ?>"></div>
          <div class="field full"><label><?= e(t('guardian_name')) ?></label><input name="guardian_name" value="<?= e(old('guardian_name')) ?>"></div>
          <div class="field"><label><?= e(t('cnic_bform')) ?></label><input name="cnic" value="<?= e(old('cnic')) ?>" placeholder="XXXXX-XXXXXXX-X" dir="ltr"></div>
          <div class="field"><label><?= e(t('date_of_birth')) ?> *</label><input type="date" name="dob" required value="<?= e(old('dob')) ?>"></div>
          <div class="field"><label><?= e(t('gender')) ?></label><select name="gender"><option value=""><?= e(t('select')) ?></option><option value="female" <?= old('gender') === 'female' ? 'selected' : '' ?>><?= e(t('female')) ?></option><option value="male" <?= old('gender') === 'male' ? 'selected' : '' ?>><?= e(t('male')) ?></option><option value="other"><?= e(t('other')) ?></option></select></div>
        </div>
      </div>

      <div class="form-step">
        <h3><?= e(t('step_contact')) ?></h3>
        <div class="form-grid">
          <div class="field"><label><?= e(t('mobile_number')) ?> *</label><input type="tel" name="phone" required value="<?= e(old('phone')) ?>" dir="ltr" placeholder="03XX-XXXXXXX"><span class="hint"><?= e(t('mobile_hint_sms')) ?></span></div>
          <div class="field"><label><?= e(t('email')) ?></label><input type="email" name="email" value="<?= e(old('email')) ?>" dir="ltr"></div>
          <div class="field full"><label><?= e(t('address')) ?></label><input name="address" value="<?= e(old('address')) ?>"></div>
          <div class="field"><label><?= e(t('city')) ?></label><input name="city" value="<?= e(old('city', 'Mirpur')) ?>"></div>
        </div>
      </div>

      <div class="form-step">
        <h3><?= e(t('step_type_docs')) ?></h3>
        <div class="radio-cards mb3">
          <?php foreach (['student', 'general', 'child'] as $ty): ?>
            <label class="radio-card"><input type="radio" name="membership_type" value="<?= $ty ?>" <?= old('membership_type', 'general') === $ty ? 'checked' : '' ?>> <strong><?= e(t('mt_' . $ty)) ?></strong>
              <div class="small muted mt1"><?= e(s('fee_' . $ty, t('fee_ask_desk'))) ?></div></label>
          <?php endforeach; ?>
        </div>
        <div class="form-grid">
          <div class="field"><label><?= e(t('photo')) ?></label><input type="file" name="photo" accept="image/*"><span class="hint"><?= e(t('photo_hint')) ?></span></div>
          <div class="field"><label><?= e(t('id_document')) ?></label><input type="file" name="document" accept="image/*,application/pdf"><span class="hint"><?= e(t('id_document_hint')) ?></span></div>
        </div>
      </div>

      <div class="form-step">
        <h3><?= e(t('step_review')) ?></h3>
        <div class="table-wrap"><table class="table" data-review></table></div>
        <label class="check mt2"><input type="checkbox" name="declaration" value="1" required> <?= e(t('declaration')) ?></label>
      </div>

      <div class="flex between mt3">
        <button type="button" class="btn btn-ghost" data-prev><?= e(t('back')) ?></button>
        <button type="button" class="btn btn-primary" data-next><?= e(t('next')) ?></button>
        <button type="submit" class="btn btn-green" data-submit hidden><?= icon('check') ?> <?= e(t('submit_application')) ?></button>
      </div>
    </form>

    <aside class="stack sticky">
      <div class="card"><h3><?= e(t('member_benefits')) ?></h3><ul class="muted" style="padding-inline-start:18px;margin:0"><li><?= e(t('benefit_1', ['n' => setting('max_loans_text', '3')])) ?></li><li><?= e(t('benefit_2')) ?></li><li><?= e(t('benefit_3')) ?></li><li><?= e(t('benefit_4')) ?></li></ul></div>
      <div class="card-surface"><h3><?= e(t('what_happens_next')) ?></h3><div class="timeline"><div class="ti"><?= e(t('next_1')) ?></div><div class="ti"><?= e(t('next_2')) ?></div><div class="ti"><?= e(t('next_3')) ?></div></div></div>
    </aside>
  </div>
</div>
