<?php
/** @var string $currentHref */
$infoLinks = require view_path('partials/site-info-links.php');
$currentHref = (string) ($currentHref ?? '');
?>
<nav class="legal-nav" aria-label="사이트 안내 메뉴">
  <?php foreach ($infoLinks as $link): ?>
  <a href="<?= e($link['href']) ?>"<?= $link['href'] === $currentHref ? ' aria-current="page"' : '' ?>><?= e($link['label']) ?></a>
  <?php endforeach; ?>
</nav>
