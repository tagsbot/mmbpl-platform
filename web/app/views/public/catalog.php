<?php
$page = max(1, (int)$f['page']);
$chk = fn($k, $v) => in_array($v, (array)$f[$k], true) ? 'checked' : '';
?>
<div class="bg-surface" style="border-bottom:1px solid var(--line)">
  <div class="container" style="padding-top:8px;padding-bottom:24px">
    <div class="breadcrumb"><a href="<?= url('/') ?>"><?= e(t('nav_home')) ?></a> / <?= e(t('nav_catalog')) ?></div>
    <form class="searchbar" style="max-width:none" action="<?= url('catalog') ?>" method="get" role="search">
      <select name="field" aria-label="<?= e(t('search_in')) ?>">
        <?php foreach (['all' => t('f_all'), 'title' => t('f_title'), 'author' => t('f_author'), 'subject' => t('f_subject'), 'isbn' => 'ISBN'] as $k => $l): ?>
          <option value="<?= $k ?>" <?= $f['field'] === $k ? 'selected' : '' ?>><?= e($l) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('search_placeholder')) ?>" aria-label="<?= e(t('search_placeholder')) ?>">
      <button class="btn btn-green btn-lg"><?= icon('search') ?> <?= e(t('search')) ?></button>
    </form>
    <p class="mt2 mb0"><strong><?= e(t('results_count', ['n' => number_format($total)])) ?></strong><?php if ($f['q'] !== ''): ?> <?= e(t('for_query', ['q' => $f['q']])) ?><?php endif; ?></p>
  </div>
</div>

<div class="container section-sm">
  <div class="catalog-layout">
    <form class="filters" method="get" action="<?= url('catalog') ?>" id="filters">
      <input type="hidden" name="q" value="<?= e($f['q']) ?>"><input type="hidden" name="field" value="<?= e($f['field']) ?>">
      <div class="flex between"><strong><?= icon('filter', 16) ?> <?= e(t('filters')) ?></strong><a class="small" href="<?= url('catalog?q=' . rawurlencode($f['q'])) ?>"><?= e(t('clear_all')) ?></a></div>
      <h4 class="mt2"><?= e(t('availability')) ?></h4>
      <label class="check"><input type="checkbox" name="avail" value="1" <?= $f['avail'] ? 'checked' : '' ?> onchange="this.form.submit()"> <?= e(t('available_now')) ?></label>
      <label class="check"><input type="checkbox" name="new" value="1" <?= $f['new'] ? 'checked' : '' ?> onchange="this.form.submit()"> <?= e(t('new_arrivals')) ?></label>
      <h4><?= e(t('language')) ?></h4>
      <?php foreach (['ur', 'en', 'ar', 'other'] as $l): ?>
        <label class="check"><input type="checkbox" name="lang[]" value="<?= $l ?>" <?= $chk('lang', $l) ?> onchange="this.form.submit()"> <?= e(lang_label($l)) ?></label>
      <?php endforeach; ?>
      <h4><?= e(t('format')) ?></h4>
      <?php foreach (['book', 'magazine', 'newspaper', 'manuscript'] as $fm): ?>
        <label class="check"><input type="checkbox" name="format[]" value="<?= $fm ?>" <?= $chk('format', $fm) ?> onchange="this.form.submit()"> <?= e(t('fmt_' . $fm)) ?></label>
      <?php endforeach; ?>
      <h4><?= e(t('publication_year')) ?></h4>
      <div class="flex"><input type="number" name="year_from" placeholder="<?= e(t('from')) ?>" value="<?= e($f['year_from']) ?>" min="1500" max="2100"><input type="number" name="year_to" placeholder="<?= e(t('to')) ?>" value="<?= e($f['year_to']) ?>" min="1500" max="2100"></div>
      <?php if ($f['subject_tag']): ?><input type="hidden" name="subject" value="<?= e($f['subject_tag']) ?>"><h4><?= e(t('f_subject')) ?></h4><span class="chip on"><?= e($f['subject_tag']) ?></span><?php endif; ?>
      <?php if ($f['author_tag']): ?><input type="hidden" name="author" value="<?= e($f['author_tag']) ?>"><h4><?= e(t('f_author')) ?></h4><span class="chip on"><?= e($f['author_tag']) ?></span><?php endif; ?>
      <button class="btn btn-outline btn-block mt2"><?= e(t('apply_filters')) ?></button>
    </form>

    <div>
      <div class="flex between wrap mb2">
        <form method="get" class="flex" action="<?= url('catalog') ?>">
          <?php foreach ($_GET as $k => $v) if (!in_array($k, ['sort', 'page'], true)) { foreach ((array)$v as $vv) echo '<input type="hidden" name="' . e($k) . (is_array($v) ? '[]' : '') . '" value="' . e($vv) . '">'; } ?>
          <label class="small muted" for="sort"><?= e(t('sort_by')) ?></label>
          <select id="sort" name="sort" style="min-height:40px;width:auto" onchange="this.form.submit()">
            <?php foreach (['relevance' => t('sort_relevance'), 'newest' => t('sort_newest'), 'title' => t('sort_title'), 'popular' => t('sort_popular')] as $k => $l): ?>
              <option value="<?= $k ?>" <?= $f['sort'] === $k ? 'selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <div class="flex">
          <?php $qs = $_GET; $qs['view'] = 'list'; ?><a class="icon-btn" href="?<?= e(http_build_query($qs)) ?>" aria-label="List" style="<?= $viewMode !== 'grid' ? 'border-color:var(--blue);color:var(--blue)' : '' ?>"><?= icon('list', 18) ?></a>
          <?php $qs['view'] = 'grid'; ?><a class="icon-btn" href="?<?= e(http_build_query($qs)) ?>" aria-label="Grid" style="<?= $viewMode === 'grid' ? 'border-color:var(--blue);color:var(--blue)' : '' ?>"><?= icon('grid', 18) ?></a>
        </div>
      </div>

      <?php if ($results): ?>
        <?php if ($viewMode === 'grid'): ?>
          <div class="grid g4"><?php foreach ($results as $b) partial('book_tile', ['b' => $b]); ?></div>
        <?php else: ?>
          <div class="results-grid"><?php foreach ($results as $b) partial('result_card', ['b' => $b]); ?></div>
        <?php endif; ?>
        <?php partial('pagination', ['total' => $total, 'perPage' => $perPage, 'page' => $page]); ?>
      <?php else: ?>
        <div class="card empty">
          <img src="<?= asset('img/empty-books.svg') ?>" alt="">
          <h3><?= e(t('no_results')) ?></h3>
          <p><?= e(t('no_results_hint')) ?></p>
          <a class="btn btn-primary" href="<?= url('suggest?title=' . rawurlencode($f['q'])) ?>"><?= icon('lightbulb', 18) ?> <?= e(t('suggest_this_book')) ?></a>
        </div>
      <?php endif; ?>

      <?php if ($results): ?>
      <div class="card-surface mt3 flex wrap between">
        <div class="flex"><?= icon('book', 28) ?><div><strong><?= e(t('didnt_find')) ?></strong><div class="muted small"><?= e(t('didnt_find_hint')) ?></div></div></div>
        <a class="btn btn-primary btn-sm" href="<?= url('suggest?title=' . rawurlencode($f['q'])) ?>"><?= e(t('suggest_a_book')) ?></a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
