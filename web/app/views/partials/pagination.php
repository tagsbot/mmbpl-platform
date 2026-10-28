<?php
$pages = (int)ceil($total / max(1, $perPage));
if ($pages > 1):
  $qs = $_GET; ?>
<nav class="pagination" aria-label="Pagination">
  <?php if ($page > 1): $qs['page'] = $page - 1; ?><a href="?<?= e(http_build_query($qs)) ?>"><?= icon('chevron-left', 16, 'flip-rtl') ?></a><?php endif; ?>
  <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): $qs['page'] = $i; ?>
    <?php if ($i === $page): ?><span class="on"><?= $i ?></span><?php else: ?><a href="?<?= e(http_build_query($qs)) ?>"><?= $i ?></a><?php endif; ?>
  <?php endfor; ?>
  <?php if ($page < $pages): $qs['page'] = $page + 1; ?><a href="?<?= e(http_build_query($qs)) ?>"><?= icon('chevron-right', 16, 'flip-rtl') ?></a><?php endif; ?>
</nav>
<?php endif; ?>
