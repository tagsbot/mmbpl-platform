<?php $items = [
  ['overview', '', 'home', t('overview')], ['loans', 'loans', 'books', t('borrowed_books'), $counts['loans']], ['holds', 'holds', 'bookmark', t('reservations'), $counts['holds']],
  ['history', 'history', 'history', t('history')], ['wishlist', 'list', 'heart', t('my_list')], ['card', 'card', 'qr', t('digital_card')],
  ['notifications', 'notifications', 'bell', t('notifications'), $counts['unread']], ['profile', 'profile', 'settings', t('profile_settings')],
]; ?>
<nav class="side-nav" aria-label="<?= e(t('my_account')) ?>">
  <?php foreach ($items as $it): ?>
    <a href="<?= url('account/' . $it[1]) ?>" class="<?= $section === $it[0] ? 'on' : '' ?>"><?= icon($it[2], 18) ?> <?= e($it[3]) ?><?php if (!empty($it[4])): ?><span class="count"><?= (int)$it[4] ?></span><?php endif; ?></a>
  <?php endforeach; ?>
  <a href="<?= url('signout') ?>" style="color:var(--red)"><?= icon('logout', 18) ?> <?= e(t('sign_out')) ?></a>
</nav>
