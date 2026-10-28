<?php require __DIR__ . '/_open.php'; $st = $m['status']; ?>
<h1 style="font-size:30px"><?= e(t('digital_card')) ?></h1>
<div class="grid g2" style="align-items:start">
  <div class="library-card" id="libcard" style="max-width:420px">
    <div class="head"><img src="<?= asset('img/logo-96.png') ?>" alt=""><div><strong><?= e(s('site_name_short', 'Mian Muhammad Bakhsh Public Library')) ?></strong><div class="lbl"><?= e(s('site_place', 'Mirpur (AJK)')) ?></div></div></div>
    <div class="flex between mt3" style="position:relative;z-index:1;align-items:flex-start">
      <div><div class="lbl"><?= e(t('member')) ?></div><div style="font-size:20px;font-weight:700"><?= e(member_display_name($m)) ?></div>
        <div class="lbl mt1"><?= e(t('card_number')) ?></div><div class="mono" style="font-size:18px"><?= e($m['cardnumber']) ?></div>
        <div class="lbl mt1"><?= e(t('valid_until')) ?></div><div><?= e(fmt_date($m['expiry_date'])) ?></div>
        <span class="badge mt2 <?= $st === 'active' ? 'badge-green' : ($st === 'expired' ? 'badge-amber' : 'badge-red') ?>"><?= e(t('st_member_' . $st)) ?></span></div>
      <div class="qr" data-qr="<?= e($m['cardnumber']) ?>" data-size="132"></div>
    </div>
    <div class="barcode"><?= code128_svg($m['cardnumber']) ?><div class="mono tiny" style="color:#000;text-align:center"><?= e($m['cardnumber']) ?></div></div>
  </div>
  <div class="stack">
    <?php if ($st === 'expired'): ?><div class="alert alert-warn"><?= icon('alert') ?><div><strong><?= e(t('card_expired')) ?></strong><br><?= e(t('card_expired_d')) ?></div></div><?php endif; ?>
    <button class="btn btn-outline" onclick="document.getElementById('libcard').requestFullscreen&&document.getElementById('libcard').requestFullscreen()"><?= icon('sun', 18) ?> <?= e(t('show_fullscreen')) ?></button>
    <button class="btn btn-ghost" onclick="window.print()"><?= icon('download', 18) ?> <?= e(t('print_card')) ?></button>
    <div class="card"><h3><?= e(t('how_to_use_card')) ?></h3><ol class="muted" style="padding-inline-start:18px;margin:0"><li><?= e(t('card_use_1')) ?></li><li><?= e(t('card_use_2')) ?></li><li><?= e(t('card_use_3')) ?></li></ol></div>
  </div>
</div>
<style>#libcard:fullscreen{max-width:none;display:flex;flex-direction:column;justify-content:center;padding:48px}</style>
<?php require __DIR__ . '/_close.php'; ?>
