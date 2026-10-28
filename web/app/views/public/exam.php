<?php $exams = ['CSS', 'PPSC', 'AJKPSC', 'NTS', 'Board']; ?>
<div class="page-hero"><div class="container"><div class="eyebrow"><?= e(t('exam_corner')) ?></div><h1><?= e(t('exam_hero')) ?></h1><p class="muted mb0"><?= e($intro ? strip_tags(tr($intro, 'body')) : t('exam_intro')) ?></p></div></div>
<div class="container section-sm">
  <div class="grid g5 mb3">
    <?php foreach ($exams as $x): ?><a class="service <?= $exam === $x ? 'green' : '' ?>" href="<?= url('exam-corner?exam=' . $x) ?>" style="<?= $exam === $x ? 'border-color:var(--green)' : '' ?>"><span class="ico"><?= icon('graduation', 24) ?></span><h3><?= e($x === 'Board' ? t('board_university') : $x) ?></h3></a><?php endforeach; ?>
  </div>
  <?php if ($exam): ?><p><a href="<?= url('exam-corner') ?>">← <?= e(t('all_exams')) ?></a></p><?php endif; ?>
  <div class="two-col">
    <div>
      <h2><?= e(t('preparation_guides')) ?></h2>
      <div class="grid g2">
        <?php foreach ($guides as $g): ?>
          <div class="card"><span class="badge badge-blue"><?= e($g['exam']) ?></span><h3 class="mt1" style="font-size:17px"><?= e($g['title']) ?></h3><div class="muted small"><?= nl2p((string)$g['body']) ?></div>
          <?php if ($g['url']): ?><a class="small" href="<?= e($g['url']) ?>" target="_blank" rel="noopener"><?= e(t('open_link')) ?> <?= icon('external', 14) ?></a><?php endif; ?></div>
        <?php endforeach; ?>
        <?php if (!$guides): ?><p class="muted"><?= e(t('coming_soon')) ?></p><?php endif; ?>
      </div>
      <?php if ($books): ?><h2 class="mt4"><?= e(t('recommended_books')) ?></h2><div class="grid g4"><?php foreach ($books as $b) partial('book_tile', ['b' => $b]); ?></div><?php endif; ?>
    </div>
    <aside class="stack">
      <div class="card"><h3><?= icon('briefcase') ?> <?= e(t('job_announcements')) ?></h3>
        <?php foreach ($jobs as $j): ?><div class="loan-row"><div class="info"><b><?= e($j['title']) ?></b><span class="small muted"><?= e($j['exam']) ?></span><?php if ($j['url']): ?> · <a class="small" href="<?= e($j['url']) ?>" target="_blank" rel="noopener"><?= e(t('details')) ?></a><?php endif; ?></div><?php if ($j['deadline']): ?><span class="badge <?= days_until($j['deadline']) <= 7 ? 'badge-red' : 'badge-amber' ?>"><?= e(fmt_date($j['deadline'])) ?></span><?php endif; ?></div><?php endforeach; ?>
        <?php if (!$jobs): ?><p class="muted small"><?= e(t('no_jobs')) ?></p><?php endif; ?>
      </div>
      <div class="card-surface"><h3><?= e(t('study_in_library')) ?></h3><p class="muted small mb0"><?= e(t('study_in_library_d')) ?></p></div>
    </aside>
  </div>
</div>
