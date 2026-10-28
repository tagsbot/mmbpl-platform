<?php $sChip = ['review' => 'badge-amber', 'approved' => 'badge-blue', 'purchased' => 'badge-green', 'rejected' => 'badge-slate']; ?>
<div class="page-hero"><div class="container"><div class="eyebrow"><?= e(t('help_grow')) ?></div><h1><?= e(t('suggest_donate')) ?></h1><p class="muted mb0"><?= e(t('suggest_intro')) ?></p></div></div>
<div class="container section-sm">
  <div class="tabs"><a href="<?= url('suggest') ?>" class="<?= $tab !== 'donate' ? 'on' : '' ?>"><?= icon('lightbulb', 16) ?> <?= e(t('suggest_a_book')) ?></a><a href="<?= url('suggest?tab=donate') ?>" class="<?= $tab === 'donate' ? 'on' : '' ?>"><?= icon('gift', 16) ?> <?= e(t('donate_books')) ?></a></div>
  <?php if ($thanks): ?><div class="alert alert-success"><?= icon('check-circle') ?><div><strong><?= e(t('thank_you')) ?></strong><br><?= e($thanks === 'donate' ? t('donate_thanks') : t('suggest_thanks')) ?></div></div><?php endif; ?>
  <?php if ($tab !== 'donate'): ?>
  <div class="two-col">
    <form class="card" method="post" action="<?= url('suggest') ?>">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field full"><label><?= e(t('book_title')) ?> *</label><input name="title" required value="<?= e(input('title')) ?>"></div>
        <div class="field"><label><?= e(t('f_author')) ?></label><input name="author"></div>
        <div class="field"><label>ISBN (<?= e(t('optional')) ?>)</label><input name="isbn" dir="ltr"></div>
        <div class="field"><label><?= e(t('language')) ?></label><select name="language"><option value="ur"><?= e(t('lang_urdu')) ?></option><option value="en"><?= e(t('lang_english')) ?></option><option value="ar"><?= e(t('lang_arabic')) ?></option><option value="other"><?= e(t('lang_other')) ?></option></select></div>
        <div class="field full"><label><?= e(t('why_needed')) ?></label><textarea name="reason" maxlength="500" rows="3"></textarea></div>
        <?php if (!$m): ?>
          <div class="field"><label><?= e(t('your_name')) ?></label><input name="name"></div>
          <div class="field"><label><?= e(t('mobile_number')) ?></label><input name="phone" type="tel" dir="ltr"></div>
        <?php endif; ?>
      </div>
      <p class="small muted"><?= icon('info', 14) ?> <?= e(t('staff_review_note')) ?></p>
      <button class="btn btn-primary"><?= e(t('submit_suggestion')) ?></button>
    </form>
    <aside class="card">
      <h3><?= e($mine ? t('my_requests') : t('recently_requested')) ?></h3>
      <?php foreach (($mine ?: $recent) as $r): ?>
        <div class="loan-row"><div class="info"><b><?= e($r['title']) ?></b><span class="small muted"><?= e($r['author']) ?></span></div><span class="badge <?= $sChip[$r['status']] ?? 'badge-slate' ?>"><?= e(t('sug_' . $r['status'])) ?></span></div>
      <?php endforeach; ?>
      <?php if (!$mine && !$recent): ?><p class="muted"><?= e(t('no_requests_yet')) ?></p><?php endif; ?>
    </aside>
  </div>
  <?php else: ?>
  <div class="two-col">
    <form class="card" method="post" action="<?= url('donate') ?>">
      <?= csrf_field() ?>
      <div class="prose mb2"><?= nl2p(s('donation_policy', t('donation_policy_default'))) ?></div>
      <div class="form-grid">
        <div class="field"><label><?= e(t('donor_name')) ?> *</label><input name="name" required></div>
        <div class="field"><label><?= e(t('mobile_number')) ?> *</label><input name="phone" type="tel" required dir="ltr"></div>
        <div class="field"><label><?= e(t('type_of_books')) ?></label><input name="book_type" placeholder="<?= e(t('type_of_books_ph')) ?>"></div>
        <div class="field"><label><?= e(t('approx_quantity')) ?></label><input name="quantity"></div>
        <div class="field"><label><?= e(t('dropoff_date')) ?></label><input type="date" name="dropoff_date" min="<?= date('Y-m-d') ?>"></div>
        <div class="field full"><label><?= e(t('note')) ?></label><textarea name="note" rows="3" maxlength="500"></textarea></div>
      </div>
      <button class="btn btn-green"><?= icon('gift', 18) ?> <?= e(t('submit_donation')) ?></button>
    </form>
    <aside class="stack">
      <div class="card"><h3 style="color:var(--green)"><?= icon('check-circle') ?> <?= e(t('we_accept')) ?></h3><div class="muted"><?= nl2p(s('donation_accept', t('donation_accept_default'))) ?></div></div>
      <div class="card"><h3 style="color:var(--red)"><?= icon('x') ?> <?= e(t('we_dont_accept')) ?></h3><div class="muted"><?= nl2p(s('donation_reject', t('donation_reject_default'))) ?></div></div>
    </aside>
  </div>
  <?php endif; ?>
</div>
