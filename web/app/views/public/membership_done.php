<div class="container section" style="max-width:720px;text-align:center">
  <div style="width:80px;height:80px;border-radius:50%;background:var(--green-50);color:var(--green);display:grid;place-items:center;margin:0 auto 20px"><?= icon('check', 40) ?></div>
  <h1><?= e(t('application_submitted')) ?></h1>
  <p class="muted"><?= e(t('application_submitted_d')) ?></p>
  <div class="card mt3"><div class="muted small"><?= e(t('reference_number')) ?></div><div class="mono" style="font-size:28px;color:var(--blue)"><?= e($ref) ?></div><p class="small muted mb0 mt1"><?= e(t('review_time', ['days' => setting('review_days', '2-3')])) ?></p></div>
  <div class="flex mt3" style="justify-content:center"><a class="btn btn-primary" href="<?= url('/') ?>"><?= e(t('back_home')) ?></a><a class="btn btn-outline" href="<?= url('catalog') ?>"><?= e(t('search_catalog')) ?></a></div>
</div>
