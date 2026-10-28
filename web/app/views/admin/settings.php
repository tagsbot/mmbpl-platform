<div class="page-head"><div><h1>Settings</h1><p class="muted mb0">Library information shown on the website and the apps.</p></div></div>
<form method="post" action="<?= url('admin/settings') ?>"><?= csrf_field() ?>
<?php foreach ($groups as $g => $fields): ?>
  <div class="card mb3"><h3><?= e($g) ?></h3><div class="form-grid">
  <?php foreach ($fields as $k => $label): $long = in_array($k, ['donation_policy', 'donation_accept', 'donation_reject', 'footer_about', 'footer_about_ur', 'site_tagline', 'hero_subtitle', 'hero_subtitle_ur', 'visit_text', 'visit_text_ur'], true); $ur = str_ends_with($k, '_ur'); ?>
    <div class="field <?= $long || $k === 'sms_url' ? 'full' : '' ?>"><label><?= e($label) ?></label>
    <?php if ($long): ?><textarea name="<?= $k ?>" rows="3"<?= $ur ? ' dir="rtl" class="ur"' : '' ?>><?= e(setting($k)) ?></textarea>
    <?php else: ?><input name="<?= $k ?>" value="<?= e(setting($k)) ?>"<?= $ur ? ' dir="rtl" class="ur"' : '' ?><?= $k === 'fcm_server_key' ? ' type="password" autocomplete="off"' : '' ?>><?php endif; ?>
    <?php if ($k === 'sms_url'): ?><span class="hint">Ask your SMS provider for an HTTP API link. Put {to} where the number goes and {text} where the message goes. Example: https://sms.example.pk/api?user=XX&amp;pass=XX&amp;to={to}&amp;msg={text}</span><?php endif; ?>
    </div>
  <?php endforeach; ?></div></div>
<?php endforeach; ?>
<div class="card mb3"><h3>Testing</h3><label class="check"><input type="checkbox" name="show_demo_otp" value="1" <?= setting('show_demo_otp') === '1' ? 'checked' : '' ?>> Show one-time codes on screen when SMS is not set up (testing only, turn off before launch)</label></div>
<button class="btn btn-primary btn-lg"><?= icon('check', 18) ?> Save settings</button>
</form>
