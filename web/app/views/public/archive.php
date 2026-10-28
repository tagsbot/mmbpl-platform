<div class="page-hero" style="background:linear-gradient(180deg,#f6efe3,#fff)"><div class="container"><div class="eyebrow" style="color:#7A3E12"><?= e(t('heritage')) ?></div><h1><?= e(t('archive_title')) ?></h1><p class="muted mb0"><?= e(t('archive_intro')) ?></p></div></div>
<div class="container section-sm">
  <form class="flex wrap mb3" method="get">
    <input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="<?= e(t('search_archive')) ?>" style="flex:1;min-width:220px">
    <select name="type" style="width:auto"><option value=""><?= e(t('all_types')) ?></option><?php foreach (['photograph', 'newspaper', 'rare_book', 'document'] as $ty): ?><option value="<?= $ty ?>" <?= input('type') === $ty ? 'selected' : '' ?>><?= e(t('arch_' . $ty)) ?></option><?php endforeach; ?></select>
    <select name="decade" style="width:auto"><option value=""><?= e(t('any_decade')) ?></option><?php for ($d = 1900; $d <= 2020; $d += 10): ?><option value="<?= $d ?>" <?= input('decade') === (string)$d ? 'selected' : '' ?>><?= $d ?>s</option><?php endfor; ?></select>
    <button class="btn btn-primary"><?= icon('search', 18) ?> <?= e(t('search')) ?></button>
  </form>
  <?php if ($items): ?>
  <div class="masonry">
    <?php foreach ($items as $a): ?>
      <div class="arch" tabindex="0" data-arch='<?= e(json_encode(['title' => tr($a, 'title'), 'year' => $a['year'], 'place' => $a['place'], 'source' => $a['source'], 'rights' => $a['rights'], 'desc' => $a['description'], 'img' => $a['image'] ? upload_url($a['image']) : asset('img/event-placeholder.svg'), 'type' => t('arch_' . $a['type'])], JSON_UNESCAPED_UNICODE)) ?>'>
        <img src="<?= e($a['image'] ? upload_url($a['image']) : asset('img/event-placeholder.svg')) ?>" alt="" loading="lazy">
        <div class="b"><strong><?= e(tr($a, 'title')) ?></strong><div class="small muted"><?= e($a['year']) ?><?= $a['place'] ? ' · ' . e($a['place']) : '' ?></div><span class="badge <?= $a['digitised'] ? 'badge-green' : 'badge-slate' ?> mt1"><?= e($a['digitised'] ? t('digitised') : t('in_the_library')) ?></span></div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?><div class="card empty"><img src="<?= asset('img/empty-books.svg') ?>" alt=""><h3><?= e(t('archive_empty')) ?></h3></div><?php endif; ?>
  <div class="card-surface mt3 flex wrap between"><div><strong><?= e(t('have_old_photos')) ?></strong><div class="muted small"><?= e(t('have_old_photos_d')) ?></div></div><a class="btn btn-primary btn-sm" href="<?= url('contact') ?>"><?= e(t('nav_contact')) ?></a></div>
</div>
<div class="lightbox" id="archbox"><div class="card" style="max-width:960px;width:100%;max-height:90vh;overflow:auto;display:grid;grid-template-columns:1.3fr 1fr;gap:24px" onclick="event.stopPropagation()">
  <img data-f="img" alt="" style="width:100%;border-radius:8px;filter:sepia(.2)">
  <div><button class="icon-btn" style="float:inline-end" onclick="document.getElementById('archbox').classList.remove('on')"><?= icon('x', 18) ?></button><h3 data-f="title"></h3>
    <table class="table"><tr><th><?= e(t('type')) ?></th><td data-f="type"></td></tr><tr><th><?= e(t('year')) ?></th><td data-f="year"></td></tr><tr><th><?= e(t('place')) ?></th><td data-f="place"></td></tr><tr><th><?= e(t('source')) ?></th><td data-f="source"></td></tr><tr><th><?= e(t('rights_label')) ?></th><td data-f="rights"></td></tr></table>
    <p class="muted small mt2" data-f="desc"></p><a class="btn btn-primary btn-sm" href="<?= url('contact') ?>"><?= e(t('request_access')) ?></a></div>
</div></div>
