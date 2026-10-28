<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Admin sign in · MMBPL</title>
<link rel="icon" href="<?= asset('img/favicon.png') ?>"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>"><link rel="stylesheet" href="<?= asset('css/admin.css') ?>"></head>
<body><div class="admin-login"><div class="card">
  <div style="text-align:center"><img src="<?= asset('img/logo-192.png') ?>" alt="" style="width:88px;margin:0 auto 12px"><h2 class="mb0">Admin panel</h2><p class="muted">Mian Muhammad Bakhsh Public Library</p></div>
  <?php foreach (flashes() as [$t, $msg]): ?><div class="alert alert-<?= $t === 'success' ? 'success' : 'error' ?>"><?= e($msg) ?></div><?php endforeach; ?>
  <form method="post" action="<?= url('admin/login') ?>"><?= csrf_field() ?>
    <div class="field"><label>Email</label><input type="email" name="email" required autofocus autocomplete="username"></div>
    <div class="field"><label>Password</label><div class="pw-wrap"><input type="password" name="password" required autocomplete="current-password"><button type="button" data-pw><?= icon('eye', 18) ?></button></div></div>
    <button class="btn btn-primary btn-block btn-lg">Sign in</button>
  </form>
  <p class="small muted mt2" style="text-align:center"><a href="<?= url('/') ?>">← Back to website</a></p>
</div></div><script src="<?= asset('js/app.js') ?>" defer></script></body></html>
