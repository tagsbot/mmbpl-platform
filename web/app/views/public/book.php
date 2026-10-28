<?php $av = $b['av']; $m = member(); ?>
<div class="container">
  <div class="breadcrumb"><a href="<?= url('/') ?>"><?= e(t('nav_home')) ?></a> / <a href="<?= url('catalog') ?>"><?= e(t('nav_catalog')) ?></a> / <?= e(mb_strimwidth($b['title'], 0, 60, '…')) ?></div>
  <div class="grid" style="grid-template-columns:minmax(200px,320px) 1fr;gap:40px">
    <div><?= book_cover($b, 'lg') ?></div>
    <div>
      <div class="flex wrap"><?= status_badge($av) ?><span class="badge badge-slate"><?= e(lang_label($b['language'])) ?></span><?php if ($b['call_number']): ?><span class="call-tag"><?= e($b['call_number']) ?></span><?php endif; ?></div>
      <h1 class="mt2" style="font-size:34px"<?= preg_match('/\p{Arabic}/u', $b['title']) ? ' dir="rtl" lang="ur"' : '' ?>><?= e($b['title']) ?></h1>
      <?php if ($b['subtitle']): ?><p class="muted" style="font-size:18px"><?= e($b['subtitle']) ?></p><?php endif; ?>
      <p style="font-size:18px"><?= e(t('by')) ?> <a href="<?= url('catalog?author=' . rawurlencode((string)$b['author'])) ?>"><strong><?= e($b['author']) ?></strong></a></p>
      <p class="muted"><?= e(t('copies_available', ['n' => $av['available'], 'total' => $av['total']])) ?><?php if ($av['next_due'] && !$av['available']): ?> · <?= e(t('expected_back', ['date' => fmt_date($av['next_due'])])) ?><?php endif; ?></p>

      <div class="flex wrap mt2">
        <?php if ($myHold): ?>
          <span class="badge badge-blue" style="padding:10px 16px"><?= icon('check', 16) ?> <?= e($myHold['status'] === 'ready' ? t('hold_ready') : t('you_reserved')) ?></span>
          <a class="btn btn-outline" href="<?= url('account/holds') ?>"><?= e(t('view_reservations')) ?></a>
        <?php elseif ($m): ?>
          <form method="post" action="<?= url('account/reserve/' . $b['id']) ?>"><?= csrf_field() ?><button class="btn btn-primary btn-lg"><?= icon('bookmark') ?> <?= e(t('reserve')) ?></button></form>
        <?php else: ?>
          <a class="btn btn-primary btn-lg" href="<?= url('signin') ?>"><?= icon('lock', 18) ?> <?= e(t('sign_in_to_reserve')) ?></a>
        <?php endif; ?>
        <?php if ($m): ?>
          <form method="post" action="<?= url('account/list/' . $b['id']) ?>"><?= csrf_field() ?><button class="btn btn-outline btn-lg"><?= icon('heart', 18) ?> <?= e($inList ? t('remove_from_list') : t('add_to_list')) ?></button></form>
        <?php endif; ?>
        <a class="btn btn-ghost" href="<?= url('suggest') ?>"><?= e(t('suggest_similar')) ?></a>
      </div>

      <div class="notice mt3"><?= icon('info', 22) ?><span><?= e(t('physical_book_notice')) ?></span></div>

      <div class="tabs mt4" data-tabs>
        <button class="on" data-tab="details"><?= e(t('tab_details')) ?></button>
        <button data-tab="copies"><?= e(t('tab_availability')) ?> (<?= count($items) ?>)</button>
        <?php if ($similar): ?><button data-tab="similar"><?= e(t('tab_similar')) ?></button><?php endif; ?>
      </div>
      <div class="tab-panel on" data-panel="details">
        <?php if ($b['summary']): ?><p><?= nl2br(e($b['summary'])) ?></p><?php endif; ?>
        <table class="table" style="border:1px solid var(--line);border-radius:12px">
          <?php foreach (['publisher' => t('publisher'), 'pub_year' => t('year'), 'edition' => t('edition'), 'isbn' => 'ISBN', 'pages' => t('pages'), 'call_number' => t('call_number')] as $k => $l): if (!$b[$k]) continue; ?>
            <tr><th style="width:180px"><?= e($l) ?></th><td><?= e($b[$k]) ?></td></tr>
          <?php endforeach; ?>
          <tr><th><?= e(t('language')) ?></th><td><?= e(lang_label($b['language'])) ?></td></tr>
        </table>
        <?php if ($b['subjects']): ?>
          <h4 class="mt3"><?= e(t('subjects')) ?></h4>
          <div class="chips"><?php foreach (array_filter(array_map('trim', explode(';', $b['subjects']))) as $sub): ?><a class="chip" href="<?= url('catalog?subject=' . rawurlencode($sub)) ?>"><?= e($sub) ?></a><?php endforeach; ?></div>
        <?php endif; ?>
      </div>
      <div class="tab-panel" data-panel="copies">
        <div class="table-wrap"><table class="table">
          <thead><tr><th><?= e(t('barcode')) ?></th><th><?= e(t('shelf_location')) ?></th><th><?= e(t('call_number')) ?></th><th><?= e(t('status')) ?></th><th><?= e(t('due_date')) ?></th></tr></thead>
          <tbody>
          <?php foreach ($items as $i): ?>
            <tr><td class="mono"><?= e($i['barcode']) ?></td><td><?= e($i['shelf_location']) ?></td><td class="mono"><?= e($i['call_number'] ?: $b['call_number']) ?></td>
              <td><?= status_badge(['state' => $i['status'] === 'available' ? 'available' : ($i['status'] === 'checked_out' ? 'checked_out' : ($i['status'] === 'on_hold' ? 'on_hold' : ($i['status'] === 'reference' ? 'reference' : 'unavailable'))), 'next_due' => $i['due_date']]) ?></td>
              <td><?= e(fmt_date($i['due_date'])) ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$items): ?><tr><td colspan="5" class="muted"><?= e(t('no_copies')) ?></td></tr><?php endif; ?>
          </tbody>
        </table></div>
      </div>
      <?php if ($similar): ?>
      <div class="tab-panel" data-panel="similar"><div class="grid g4"><?php foreach (array_slice($similar, 0, 4) as $x) partial('book_tile', ['b' => $x]); ?></div></div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($byAuthor): ?>
  <section class="section">
    <h2><?= e(t('more_by_author')) ?></h2>
    <div class="shelf"><?php foreach ($byAuthor as $x) partial('book_tile', ['b' => $x]); ?></div>
  </section>
  <?php else: ?><div class="section-sm"></div><?php endif; ?>
</div>
<style>@media(max-width:760px){.container>.grid[style]{grid-template-columns:1fr!important}.cover-lg{max-width:220px;margin:0 auto}}</style>
