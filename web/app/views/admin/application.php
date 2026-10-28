<?php $a = $item; $dup = $a['cnic'] ? rows('SELECT ref_no, full_name, status FROM applications WHERE cnic=? AND id<>?', [$a['cnic'], $a['id']]) : []; ?>
<div class="page-head"><div><a class="small" href="<?= url('admin/r/applications') ?>">← Applications</a><h1><?= e($a['full_name']) ?> <span class="badge badge-slate"><?= e($a['ref_no']) ?></span></h1><p class="muted mb0">Submitted <?= e(fmt_date($a['created_at'], true)) ?> · Status: <strong><?= e($a['status']) ?></strong></p></div></div>
<?php if ($dup): ?><div class="alert alert-warn"><?= icon('alert') ?><div><strong>Possible duplicate:</strong> <?php foreach ($dup as $d) echo e($d['ref_no'] . ' (' . $d['full_name'] . ', ' . $d['status'] . ') '); ?></div></div><?php endif; ?>
<div class="grid" style="grid-template-columns:1fr 1fr 360px">
  <div class="card"><h3>Applicant</h3><table class="table">
    <?php foreach (['guardian_name' => 'Father / guardian', 'cnic' => 'CNIC / B-Form', 'dob' => 'Date of birth', 'gender' => 'Gender', 'phone' => 'Mobile', 'email' => 'Email', 'address' => 'Address', 'city' => 'City', 'membership_type' => 'Membership type'] as $k => $l): ?>
      <tr><th style="width:40%"><?= e($l) ?></th><td><?= e($a[$k]) ?></td></tr>
    <?php endforeach; ?></table></div>
  <div class="card"><h3>Documents</h3>
    <?php if ($a['photo']): ?><div class="label">Photo</div><img src="<?= e(upload_url($a['photo'])) ?>" alt="" style="max-width:200px;border-radius:8px;margin:6px 0 16px"><?php endif; ?>
    <?php if ($a['document']): ?><div class="label">ID document</div><?php if (str_ends_with($a['document'], '.pdf')): ?><a class="btn btn-outline btn-sm mt1" target="_blank" href="<?= e(upload_url($a['document'])) ?>">Open PDF</a><?php else: ?><a target="_blank" href="<?= e(upload_url($a['document'])) ?>"><img src="<?= e(upload_url($a['document'])) ?>" alt="" style="max-width:100%;border-radius:8px;margin-top:6px"></a><?php endif; ?><?php endif; ?>
    <?php if (!$a['photo'] && !$a['document']): ?><p class="muted">No documents uploaded. Ask the applicant to bring them to the desk.</p><?php endif; ?></div>
  <form class="card" method="post" action="<?= url('admin/applications/' . $a['id'] . '/decide') ?>"><?= csrf_field() ?>
    <h3>Decision</h3>
    <label class="check"><input type="radio" name="decision" value="approved" checked> Approve &amp; create member in Koha</label>
    <label class="check"><input type="radio" name="decision" value="correction"> Request correction</label>
    <label class="check"><input type="radio" name="decision" value="rejected"> Reject</label>
    <div class="field mt2"><label>Note to applicant (required for correction / reject)</label><textarea name="note" rows="3"><?= e($a['staff_note']) ?></textarea></div>
    <label class="check"><input type="checkbox" name="send_sms" value="1" checked> Send SMS to applicant</label>
    <?php if ($dup): ?><label class="check"><input type="checkbox" name="confirm_duplicate" value="1"> Approve anyway (not a duplicate)</label><?php endif; ?>
    <button class="btn btn-primary btn-block mt2">Save decision</button>
    <p class="tiny muted mt1">On approval the Sync Agent creates the patron in Koha with the category set in Settings, then the new card number appears here.</p>
  </form>
</div>
<style>@media(max-width:1200px){.adm-body .grid[style]{grid-template-columns:1fr!important}}</style>
