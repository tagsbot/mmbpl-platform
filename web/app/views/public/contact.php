<div class="page-hero"><div class="container"><div class="eyebrow"><?= e(t('we_listen')) ?></div><h1><?= e(t('contact_feedback')) ?></h1><p class="muted mb0"><?= e(t('contact_intro')) ?></p></div></div>
<div class="container section-sm">
  <div class="grid g2" style="align-items:start">
    <div class="stack">
      <div class="grid g2">
        <div class="card"><strong><?= icon('map-pin', 18) ?> <?= e(t('address')) ?></strong><p class="muted mb0 mt1"><?= e(s('address', 'Mirpur, Azad Jammu & Kashmir')) ?></p></div>
        <div class="card"><strong><?= icon('clock', 18) ?> <?= e(t('library_hours')) ?></strong><p class="muted mb0 mt1"><?= e(hours_text()) ?></p></div>
        <?php if (setting('phone')): ?><div class="card"><strong><?= icon('phone', 18) ?> <?= e(t('phone')) ?></strong><p class="mb0 mt1"><a href="tel:<?= e(setting('phone')) ?>" dir="ltr"><?= e(setting('phone')) ?></a></p></div><?php endif; ?>
        <?php if (setting('email')): ?><div class="card"><strong><?= icon('mail', 18) ?> <?= e(t('email')) ?></strong><p class="mb0 mt1"><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p></div><?php endif; ?>
        <?php if (setting('whatsapp')): ?><div class="card"><strong><?= icon('whatsapp', 18) ?> WhatsApp</strong><p class="mb0 mt1"><a href="https://wa.me/<?= e(normalize_phone(setting('whatsapp'))) ?>" target="_blank" rel="noopener" dir="ltr"><?= e(setting('whatsapp')) ?></a></p></div><?php endif; ?>
        <?php if (setting('facebook_url')): ?><div class="card"><strong><?= icon('facebook', 18) ?> Facebook</strong><p class="mb0 mt1"><a href="<?= e(setting('facebook_url')) ?>" target="_blank" rel="noopener"><?= e(t('visit_page')) ?></a></p></div><?php endif; ?>
      </div>
      <iframe class="map-frame" loading="lazy" title="Map" src="https://maps.google.com/maps?q=<?= rawurlencode(setting('map_query', 'Mirpur, Azad Kashmir')) ?>&z=15&output=embed"></iframe>
    </div>
    <div class="card" id="sent">
      <?php if ($done): ?>
        <div class="empty"><div style="width:72px;height:72px;border-radius:50%;background:var(--green-50);color:var(--green);display:grid;place-items:center;margin:0 auto 16px"><?= icon('check', 36) ?></div>
        <h3><?= e(t('message_sent')) ?></h3><p><?= e(t('your_ref_no')) ?> <strong class="mono"><?= e($done) ?></strong></p><a class="btn btn-outline" href="<?= url('contact') ?>"><?= e(t('send_another')) ?></a></div>
      <?php else: ?>
      <h2><?= e(t('send_us_message')) ?></h2>
      <form method="post" action="<?= url('contact') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="field"><label><?= e(t('message_type')) ?></label>
          <div class="chips" role="radiogroup"><?php foreach (['feedback', 'complaint', 'suggestion', 'question'] as $ty): ?><label class="chip"><input type="radio" name="type" value="<?= $ty ?>" <?= old('type', 'feedback') === $ty ? 'checked' : '' ?> style="accent-color:var(--blue)"> <?= e(t('fb_' . $ty)) ?></label><?php endforeach; ?></div></div>
        <div class="field"><label><?= e(t('your_name')) ?> *</label><input name="name" required value="<?= e(old('name', member() ? member_display_name(member()) : '')) ?>"></div>
        <div class="form-grid">
          <div class="field"><label><?= e(t('mobile_number')) ?></label><input name="phone" type="tel" dir="ltr" value="<?= e(old('phone', member()['phone'] ?? '')) ?>"></div>
          <div class="field"><label><?= e(t('email')) ?></label><input name="email" type="email" dir="ltr" value="<?= e(old('email', member()['email'] ?? '')) ?>"></div>
        </div>
        <div class="field"><label><?= e(t('message')) ?> *</label><textarea name="message" required rows="5"><?= e(old('message')) ?></textarea></div>
        <div class="field"><label><?= e(t('attachment')) ?> (<?= e(t('optional')) ?>)</label><input type="file" name="attachment" accept="image/*,application/pdf"></div>
        <button class="btn btn-primary"><?= icon('message', 18) ?> <?= e(t('send')) ?></button>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <section class="section"><h2><?= e(t('faq')) ?></h2>
    <div class="accordion">
      <details><summary><?= e(t('faq_join_q')) ?></summary><div><?= e(t('faq_join_a')) ?></div></details>
      <details><summary><?= e(t('faq_borrow_q')) ?></summary><div><?= e(t('faq_borrow_a')) ?></div></details>
      <details><summary><?= e(t('faq_renew_q')) ?></summary><div><?= e(t('faq_renew_a')) ?></div></details>
    </div>
  </section>
</div>
