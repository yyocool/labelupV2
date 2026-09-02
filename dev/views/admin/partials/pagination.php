<?php
/**
 * Unified admin pagination.
 *
 * Expected vars:
 * - int $page
 * - int $pages
 * - string $basePath   e.g. 'admin/users'
 * - array $queryParams optional extra query (without page)
 * - int $window optional (default 7)
 */
$page = max(1, (int) ($page ?? 1));
$pages = max(1, (int) ($pages ?? 1));
$basePath = (string) ($basePath ?? '');
$queryParams = is_array($queryParams ?? null) ? $queryParams : [];
$window = max(3, (int) ($window ?? 7));

if ($pages <= 1 || $basePath === '') {
    return;
}

$numbers = admin_pagination_window($page, $pages, $window);
$prev = max(1, $page - 1);
$next = min($pages, $page + 1);
?>
<nav class="admin-pagination" aria-label="페이지 탐색">
  <?php if ($page <= 1): ?>
  <span class="admin-page is-disabled" aria-disabled="true">시작</span>
  <span class="admin-page is-disabled" aria-disabled="true">이전</span>
  <?php else: ?>
  <a class="admin-page admin-page--nav" href="<?= e(admin_pagination_href($basePath, $queryParams, 1)) ?>">시작</a>
  <a class="admin-page admin-page--nav" href="<?= e(admin_pagination_href($basePath, $queryParams, $prev)) ?>">이전</a>
  <?php endif; ?>

  <?php foreach ($numbers as $i): ?>
    <?php if ($i === $page): ?>
    <span class="admin-page is-active" aria-current="page"><?= $i ?></span>
    <?php else: ?>
    <a class="admin-page" href="<?= e(admin_pagination_href($basePath, $queryParams, $i)) ?>"><?= $i ?></a>
    <?php endif; ?>
  <?php endforeach; ?>

  <?php if ($page >= $pages): ?>
  <span class="admin-page is-disabled" aria-disabled="true">다음</span>
  <span class="admin-page is-disabled" aria-disabled="true">마지막</span>
  <?php else: ?>
  <a class="admin-page admin-page--nav" href="<?= e(admin_pagination_href($basePath, $queryParams, $next)) ?>">다음</a>
  <a class="admin-page admin-page--nav" href="<?= e(admin_pagination_href($basePath, $queryParams, $pages)) ?>">마지막</a>
  <?php endif; ?>
</nav>
