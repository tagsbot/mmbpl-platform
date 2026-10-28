<div class="page-head"><div><h1>Notifications</h1><p class="muted mb0">Messages appear in members' accounts and the mobile app. Due-date and "book ready" alerts are sent automatically after each Koha sync.</p></div></div>
<div class="grid g2" style="align-items:start">
<form class="card" method="post" action="<?= url('admin/notifications') ?>"><?= csrf_field() ?>
  <h3>Send a message</h3>
  <div class="field"><label>Send to</label><select name="target" onchange="document.getElementById('cardf').hidden=this.value!=='member'"><option value="all">All members (announcement)</option><option value="overdue">Members with overdue books</option><option value="due_soon">Members with books due in 3 days</option><option value="expired">Members with expired membership</option><option value="member">One member (card number)</option></select></div>
  <div class="field" id="cardf" hidden><label>Card number</label><input name="cardnumber"></div>
  <div class="field"><label>Type</label><select name="type"><option value="library">Library announcement</option><option value="event">Event</option><option value="membership">Membership</option></select></div>
  <div class="field"><label>Title *</label><input name="title" required maxlength="200"></div>
  <div class="field"><label>Message</label><textarea name="body" rows="4"></textarea></div>
  <div class="field"><label>Link (optional, e.g. events/book-club)</label><input name="link"></div>
  <label class="check"><input type="checkbox" name="send_sms" value="1" <?= setting('sms_url') ? '' : 'disabled' ?>> Also send by SMS <?= setting('sms_url') ? '' : '<span class="muted small">(set up SMS gateway in Settings first)</span>' ?></label>
  <button class="btn btn-primary mt2"><?= icon('bell', 18) ?> Send</button>
</form>
<div class="card"><h3>Recently sent</h3><?php foreach ($recent as $n): ?><div class="loan-row"><div class="info"><b class="small"><?= e($n['title']) ?></b><span class="tiny muted"><?= e($n['cardnumber'] ?: 'All members') ?> · <?= e($n['type']) ?> · <?= e(fmt_date($n['created_at'], true)) ?></span></div></div><?php endforeach; ?><?php if (!$recent): ?><p class="muted">Nothing sent yet.</p><?php endif; ?></div>
</div>
