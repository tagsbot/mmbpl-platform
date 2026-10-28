<div class="page-head"><div><a class="small" href="<?= url('admin/r/' . $r['key']) ?>">← <?= e($r['title']) ?></a><h1><?= e($title) ?></h1></div>
<?php if ($r['key'] === 'events' && !empty($item['id'])): ?><div class="flex"><a class="btn btn-outline btn-sm" href="<?= url('admin/events/' . $item['id'] . '/registrations') ?>"><?= icon('users', 16) ?> Registrations</a><a class="btn btn-ghost btn-sm" target="_blank" href="<?= url('events/' . $item['slug']) ?>"><?= icon('external', 16) ?> View</a></div><?php endif; ?></div>
<form class="card" method="post" action="<?= url('admin/r/' . $r['key'] . '/save') ?>" enctype="multipart/form-data" style="max-width:980px">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($item['id'] ?? 0) ?>">
  <div class="form-grid">
  <?php foreach ($r['fields'] as $name => $f):
    [$label, $type] = $f; $v = $item[$name] ?? ($f['default'] ?? ''); $full = in_array($type, ['textarea', 'html', 'readonly', 'image', 'readonly_file'], true) || $name === 'title';
    $dir = !empty($f['rtl']) ? ' dir="rtl" lang="ur" class="ur"' : ''; ?>
    <div class="field <?= $full ? 'full' : '' ?>">
      <label><?= e($label) ?><?= !empty($f['required']) ? ' *' : '' ?></label>
      <?php switch ($type):
        case 'textarea': ?><textarea name="<?= $name ?>" rows="3"<?= $dir ?>><?= e($v) ?></textarea><?php break;
        case 'html': ?><textarea name="<?= $name ?>" rows="10"<?= $dir ?>><?= e($v) ?></textarea><span class="hint">Plain text (empty line = new paragraph) or simple HTML: &lt;h3&gt; &lt;b&gt; &lt;ul&gt;&lt;li&gt; &lt;a href&gt;</span><?php break;
        case 'select': ?><select name="<?= $name ?>"><?php foreach ($f['options'] as $ok => $ol): ?><option value="<?= e($ok) ?>" <?= (string)$v === (string)$ok ? 'selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?></select><?php break;
        case 'checkbox': ?><label class="check"><input type="checkbox" name="<?= $name ?>" value="1" <?= $v ? 'checked' : '' ?>> Yes</label><?php break;
        case 'datetime': ?><input type="datetime-local" name="<?= $name ?>" value="<?= $v ? e(date('Y-m-d\TH:i', strtotime((string)$v))) : '' ?>" <?= !empty($f['required']) ? 'required' : '' ?>><?php break;
        case 'date': ?><input type="date" name="<?= $name ?>" value="<?= e($v) ?>"><?php break;
        case 'number': ?><input type="number" name="<?= $name ?>" value="<?= e($v) ?>"><?php break;
        case 'email': ?><input type="email" name="<?= $name ?>" value="<?= e($v) ?>" required><?php break;
        case 'password': ?><input type="password" name="<?= $name ?>" autocomplete="new-password" minlength="10"><?php break;
        case 'image': ?><?php if ($v): ?><img src="<?= e(upload_url($v)) ?>" alt="" style="max-width:280px;border-radius:8px;margin-bottom:8px"><label class="check small"><input type="checkbox" name="<?= $name ?>_remove" value="1"> Remove image</label><?php endif; ?><input type="file" name="<?= $name ?>" accept="image/*"><span class="hint">JPG, PNG or WebP, up to 8 MB. Wide images (16:9) look best.</span><?php break;
        case 'readonly': ?><div class="card-surface" style="padding:12px 14px;white-space:pre-wrap"><?= e($v) ?: '<span class="muted">—</span>' ?></div><?php break;
        case 'readonly_file': ?><?php if ($v): ?><a class="btn btn-outline btn-sm" target="_blank" href="<?= e(upload_url($v)) ?>"><?= icon('file', 16) ?> Open attachment</a><?php else: ?><span class="muted">—</span><?php endif; ?><?php break;
        default: ?><input type="text" name="<?= $name ?>" value="<?= e($v) ?>" <?= !empty($f['required']) ? 'required' : '' ?><?= $dir ?>>
      <?php endswitch; ?>
    </div>
  <?php endforeach; ?>
  </div>
  <div class="flex mt2"><button class="btn btn-primary"><?= icon('check', 18) ?> Save</button><a class="btn btn-ghost" href="<?= url('admin/r/' . $r['key']) ?>">Cancel</a></div>
</form>
