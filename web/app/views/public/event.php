<?php $isEvent = $e['type'] === 'event'; $past = $isEvent && $e['event_start'] && strtotime($e['event_start']) < time(); ?>
<div style="background:var(--ink) url('<?= e(post_image($e)) ?>') center/cover;min-height:320px;position:relative">
  <div style="position:absolute;inset:0;background:linear-gradient(0deg,rgba(15,27,45,.85),rgba(15,27,45,.2))"></div>
  <div class="container" style="position:relative;padding-top:120px;padding-bottom:32px;color:#fff">
    <div class="breadcrumb" style="color:#cfd8e4"><a style="color:#cfd8e4" href="<?= url('events') ?>"><?= e(t('news_and_events')) ?></a></div>
    <span class="badge badge-green"><?= e($isEvent ? ($e['category'] ? t('cat_' . $e['category']) : t('event')) : t('news')) ?></span>
    <h1 style="color:#fff" class="mt1"><?= e(tr($e, 'title')) ?></h1>
    <?php if ($isEvent): ?><p class="mb0"><?= icon('calendar', 18) ?> <?= e(fmt_date($e['event_start'], true)) ?><?php if ($e['event_end']): ?> – <?= e(date('g:i A', strtotime($e['event_end']))) ?><?php endif; ?> &nbsp; <?= icon('map-pin', 18) ?> <?= e($e['venue']) ?></p><?php else: ?><p class="mb0"><?= e(fmt_date($e['created_at'])) ?></p><?php endif; ?>
  </div>
</div>
<div class="container section-sm">
  <div class="<?= $isEvent ? 'two-col' : '' ?>">
    <article class="prose"<?= is_rtl() && $e['body_ur'] ? ' lang="ur"' : '' ?>>
      <?= nl2p(tr($e, 'body')) ?>
      <div class="flex wrap mt3 no-print">
        <?php if ($isEvent && $e['event_start']): $gs = gmdate('Ymd\THis\Z', strtotime($e['event_start'])); $ge = gmdate('Ymd\THis\Z', strtotime($e['event_end'] ?: $e['event_start'] . ' +2 hours')); ?>
          <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="https://calendar.google.com/calendar/render?action=TEMPLATE&text=<?= rawurlencode($e['title']) ?>&dates=<?= $gs ?>/<?= $ge ?>&location=<?= rawurlencode((string)$e['venue']) ?>"><?= icon('calendar', 16) ?> <?= e(t('add_to_calendar')) ?></a>
        <?php endif; ?>
        <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode(tr($e, 'title') . ' ' . absolute_url('events/' . $e['slug'])) ?>"><?= icon('share', 16) ?> <?= e(t('share')) ?></a>
      </div>
    </article>
    <?php if ($isEvent): ?>
    <aside class="card sticky" id="registration">
      <?php if ($reg): ?>
        <div class="alert alert-success"><?= icon('check-circle') ?><div><strong><?= e(t('registered_success')) ?></strong></div></div>
        <div class="muted small"><?= e(t('your_reg_no')) ?></div><div class="mono" style="font-size:24px;color:var(--blue)"><?= e($reg['reg_no']) ?></div>
        <p class="small muted mt1"><?= e(t('show_reg_at_desk')) ?></p>
      <?php elseif ($past): ?>
        <h3><?= e(t('event_ended')) ?></h3><p class="muted"><?= e(t('event_ended_d')) ?></p>
      <?php elseif (!$e['registration_open']): ?>
        <h3><?= e(t('free_entry')) ?></h3><p class="muted"><?= e(t('no_registration_needed')) ?></p>
      <?php else: ?>
        <h3><?= e(t('register_for_event')) ?></h3>
        <?php if ($seatsLeft !== null): ?><p><span class="badge <?= $seatsLeft > 5 ? 'badge-green' : 'badge-amber' ?>"><?= e(t('seats_left', ['n' => $seatsLeft])) ?></span></p><?php endif; ?>
        <form method="post" action="<?= url('events/' . $e['slug'] . '/register') ?>">
          <?= csrf_field() ?>
          <div class="field"><label><?= e(t('your_name')) ?></label><input name="name" required value="<?= e($m ? member_display_name($m) : '') ?>"></div>
          <div class="field"><label><?= e(t('mobile_number')) ?></label><input name="phone" type="tel" required dir="ltr" value="<?= e($m['phone'] ?? '') ?>"></div>
          <div class="field"><label><?= e(t('number_of_seats')) ?></label><select name="seats"><?php for ($i = 1; $i <= 5; $i++): ?><option><?= $i ?></option><?php endfor; ?></select></div>
          <button class="btn btn-primary btn-block" <?= $seatsLeft === 0 ? 'disabled' : '' ?>><?= e($seatsLeft === 0 ? t('fully_booked') : t('register')) ?></button>
          <?php if (!$m): ?><p class="small muted mt1"><a href="<?= url('signin') ?>"><?= e(t('sign_in_to_prefill')) ?></a></p><?php endif; ?>
        </form>
      <?php endif; ?>
    </aside>
    <?php endif; ?>
  </div>
  <?php if ($related): ?>
  <section class="section"><h2><?= e(t('related')) ?></h2><div class="grid g3"><?php foreach ($related as $p) partial('event_card', ['p' => $p]); ?></div></section>
  <?php endif; ?>
</div>
