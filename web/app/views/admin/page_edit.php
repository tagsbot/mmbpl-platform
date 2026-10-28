<div class="page-head"><div><a class="small" href="<?= url('admin/pages') ?>">← Pages</a><h1><?= e($title) ?></h1></div></div>
<form class="card" method="post" action="<?= url('admin/pages/' . $slug) ?>" style="max-width:1100px"><?= csrf_field() ?>
  <div class="form-grid">
    <div class="field"><label>Title (English)</label><input name="title" value="<?= e($pg['title']) ?>"></div>
    <div class="field"><label>Title (Urdu)</label><input name="title_ur" value="<?= e($pg['title_ur']) ?>" dir="rtl" class="ur"></div>
    <div class="field"><label>Content (English)</label><textarea name="body" rows="18"><?= e($pg['body']) ?></textarea></div>
    <div class="field"><label>Content (Urdu)</label><textarea name="body_ur" rows="18" dir="rtl" class="ur"><?= e($pg['body_ur']) ?></textarea></div>
  </div>
  <p class="hint">Empty line = new paragraph. Simple HTML is allowed: &lt;h2&gt;, &lt;h3&gt;, &lt;b&gt;, &lt;ul&gt;&lt;li&gt;, &lt;a href&gt;, &lt;table&gt;. For rules, use &lt;h3&gt; for each rule heading.</p>
  <button class="btn btn-primary"><?= icon('check', 18) ?> Save page</button>
</form>
