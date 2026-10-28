<div class="page-hero"><div class="container"><div class="eyebrow"><?= e(t('nav_about')) ?></div><h1><?= e($about ? tr($about, 'title') : t('about_title')) ?></h1></div></div>
<div class="container section-sm">
  <div class="grid" style="grid-template-columns:220px 1fr;gap:40px">
    <nav class="side-nav sticky" aria-label="On this page" style="align-self:start">
      <a href="#story"><?= e(t('our_story')) ?></a><a href="#services"><?= e(t('services')) ?></a><a href="#fees"><?= e(t('membership_fees')) ?></a><a href="#rules"><?= e(t('rules_policy')) ?></a><a href="#visit"><?= e(t('visit_us')) ?></a><?php if ($gallery): ?><a href="#gallery"><?= e(t('gallery')) ?></a><?php endif; ?>
    </nav>
    <div>
      <section id="story" class="prose"><?= $about ? nl2p(tr($about, 'body')) : '<p>' . e(t('about_default')) . '</p>' ?></section>
      <section id="services" class="mt4"><h2><?= e(t('services')) ?></h2>
        <div class="grid g3">
          <?php foreach ([['books', 'svc_lending'], ['book', 'svc_reading_room'], ['child', 'svc_children'], ['graduation', 'exam_corner'], ['calendar', 'nav_events'], ['archive', 'local_history']] as [$ic, $k]): ?>
            <div class="card"><span class="service" style="padding:0;border:0"><span class="ico"><?= icon($ic, 24) ?></span></span><h3 style="font-size:17px"><?= e(t($k)) ?></h3><p class="muted small mb0"><?= e(t($k . '_d')) ?></p></div>
          <?php endforeach; ?>
        </div>
      </section>
      <section id="fees" class="mt4"><h2><?= e(t('membership_fees')) ?></h2>
        <div class="table-wrap"><table class="table"><thead><tr><th><?= e(t('membership_type')) ?></th><th><?= e(t('fee')) ?></th><th><?= e(t('books_allowed')) ?></th></tr></thead><tbody>
          <?php foreach (['student', 'general', 'child'] as $ty): ?><tr><td><?= e(t('mt_' . $ty)) ?></td><td><?= e(s('fee_' . $ty, t('fee_ask_desk'))) ?></td><td><?= e(setting('books_' . $ty, '—')) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
      </section>
      <section id="rules" class="mt4"><h2><?= e(t('rules_policy')) ?></h2>
        <div class="accordion"><?php if ($rules): ?><div class="prose"><?= nl2p(tr($rules, 'body')) ?></div><?php else: ?>
          <details><summary><?= e(t('faq_borrow_q')) ?></summary><div><?= e(t('faq_borrow_a')) ?></div></details>
          <details><summary><?= e(t('faq_renew_q')) ?></summary><div><?= e(t('faq_renew_a')) ?></div></details><?php endif; ?>
        </div>
      </section>
      <section id="visit" class="mt4"><h2><?= e(t('visit_us')) ?></h2>
        <div class="grid g2">
          <div class="card stack">
            <div><span class="open-chip <?= is_open_now() ? 'open' : 'closed' ?>"><?= e(is_open_now() ? t('open_now') : t('closed_now')) ?></span></div>
            <div><strong><?= icon('clock', 16) ?> <?= e(t('library_hours')) ?></strong><br><?= e(hours_text()) ?></div>
            <div><strong><?= icon('map-pin', 16) ?> <?= e(t('address')) ?></strong><br><?= e(s('address', 'Mirpur, Azad Jammu & Kashmir')) ?></div>
            <?php if (setting('phone')): ?><div><strong><?= icon('phone', 16) ?> <?= e(t('phone')) ?></strong><br><a href="tel:<?= e(setting('phone')) ?>" dir="ltr"><?= e(setting('phone')) ?></a></div><?php endif; ?>
            <a class="btn btn-primary" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=<?= rawurlencode(setting('map_query', 'Mirpur, Azad Kashmir')) ?>"><?= icon('map-pin', 18) ?> <?= e(t('get_directions')) ?></a>
          </div>
          <iframe class="map-frame" loading="lazy" title="Map" src="https://maps.google.com/maps?q=<?= rawurlencode(setting('map_query', 'Mirpur, Azad Kashmir')) ?>&z=15&output=embed"></iframe>
        </div>
      </section>
      <?php if ($gallery): ?>
      <section id="gallery" class="mt4"><h2><?= e(t('gallery')) ?></h2>
        <div class="gallery" data-lightbox><?php foreach ($gallery as $g): ?><a href="<?= e(upload_url($g['image'])) ?>" style="background-image:url('<?= e(upload_url($g['image'])) ?>')" title="<?= e($g['caption']) ?>"></a><?php endforeach; ?></div>
      </section>
      <?php endif; ?>
    </div>
  </div>
</div>
<div class="lightbox" id="lightbox" onclick="this.classList.remove('on')"><img alt=""></div>
<style>@media(max-width:1100px){.container>.grid[style]{grid-template-columns:1fr!important}}</style>
