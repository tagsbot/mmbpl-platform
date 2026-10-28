<?php $online = agent_online(); ?>
<div class="page-head"><div><h1>Koha sync</h1><p class="muted mb0">The Sync Agent runs on the library computer next to Koha. It sends the catalog, members and loans here, and carries members' renew / reserve requests back into Koha.</p></div></div>
<div class="grid g3 mb3">
  <div class="kpi <?= $online ? 'ok' : 'bad' ?>"><div class="n"><?= $online ? 'Online' : 'Offline' ?></div><div class="l">Agent status · last seen <?= setting('agent_last_seen') ? e(fmt_date(setting('agent_last_seen'), true)) : 'never' ?></div></div>
  <div class="kpi"><div class="n"><?= e(setting('agent_version', '—')) ?></div><div class="l">Agent version · Koha <?= e(setting('koha_version', '—')) ?></div></div>
  <div class="kpi"><div class="n"><?= e(setting('last_full_sync') ? fmt_date(setting('last_full_sync'), true) : '—') ?></div><div class="l">Last full catalog sync</div></div>
</div>
<div class="grid g2 mb3" style="align-items:start">
  <div class="card">
    <h3>Agent connection</h3>
    <?php if ($newKey): ?>
      <div class="alert alert-success"><?= icon('check-circle') ?><div><strong>New agent key created.</strong> Copy it now; it will not be shown again.</div></div>
      <div class="code-box" id="k"><?= e($newKey) ?></div>
      <button class="btn btn-outline btn-sm mt1" onclick="navigator.clipboard.writeText(document.getElementById('k').textContent)">Copy key</button>
    <?php else: ?>
      <p>Current key: <span class="mono"><?= e(setting('agent_key_hint', 'not created')) ?></span></p>
    <?php endif; ?>
    <table class="table mt2"><tr><th>Website address for the agent</th><td class="mono" style="word-break:break-all"><?= e(absolute_url('')) ?></td></tr></table>
    <?php if (can('settings')): ?><form method="post" action="<?= url('admin/sync/key') ?>" class="mt2" onsubmit="return <?= setting('agent_key_hash') ? "confirm('A new key will disconnect the current agent until you paste the new key into it. Continue?')" : 'true' ?>"><?= csrf_field() ?><button class="btn btn-primary"><?= icon('shield', 18) ?> <?= setting('agent_key_hash') ? 'Generate new key' : 'Create agent key' ?></button></form><?php endif; ?>
  </div>
  <div class="card"><h3>Setup steps</h3><ol style="padding-inline-start:18px;margin:0" class="muted">
    <li>Create the agent key here.</li><li>On the Koha computer, install <strong>MMBPL Sync Agent</strong> (from the delivery folder).</li>
    <li>Open the agent, paste the website address and key, then the Koha address and API client ID / secret.</li>
    <li>Press <em>Test connection</em>, then <em>Start</em>. The first full sync can take a few minutes.</li></ol></div>
</div>
<div class="card mb3"><h3>Requests queue (website / app → Koha)</h3>
<div class="table-wrap"><table class="table"><thead><tr><th>#</th><th>Type</th><th>Member</th><th>Created</th><th>Status</th><th>Result</th><th></th></tr></thead><tbody>
<?php foreach ($actions as $a): ?><tr><td><?= (int)$a['id'] ?></td><td><?= e(str_replace('_', ' ', $a['type'])) ?></td><td class="mono"><?= e($a['cardnumber']) ?></td><td><?= e(fmt_date($a['created_at'], true)) ?></td>
<td><span class="badge <?= ['done' => 'badge-green', 'failed' => 'badge-red', 'pending' => 'badge-amber', 'processing' => 'badge-blue'][$a['status']] ?>"><?= e($a['status']) ?></span></td><td class="small"><?= e($a['result']) ?></td>
<td><?php if ($a['status'] === 'failed'): ?><form method="post" action="<?= url('admin/sync/retry/' . $a['id']) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm">Retry</button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$actions): ?><tr><td colspan="7" class="muted" style="text-align:center;padding:24px">No requests yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="card"><h3>Sync log</h3><div class="table-wrap"><table class="table"><thead><tr><th>Time</th><th>What</th><th>Records</th><th>Message</th></tr></thead><tbody>
<?php foreach ($logs as $l): ?><tr><td><?= e(fmt_date($l['created_at'], true)) ?></td><td><?= e($l['kind']) ?></td><td><?= (int)$l['records'] ?></td><td class="small"><?= e($l['message']) ?></td></tr><?php endforeach; ?>
<?php if (!$logs): ?><tr><td colspan="4" class="muted" style="text-align:center;padding:24px">The agent has not connected yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
