<a class="book-tile" href="<?= url('book/' . $b['id']) ?>">
  <?= book_cover($b) ?>
  <span class="t"><?= e($b['title']) ?></span>
  <span class="a"><?= e($b['author']) ?></span>
  <?= status_badge($b['av'] ?? biblio_availability((int)$b['id'])) ?>
</a>
