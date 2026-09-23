<?php
/** @var array<int, array<string, mixed>> $categories */
/** @var int $selectedId */
/** @var string $emptyLabel */
/** @var bool $showEmpty */
$categories = $categories ?? [];
$selectedId = (int) ($selectedId ?? 0);
$emptyLabel = (string) ($emptyLabel ?? '전체 카테고리');
$showEmpty = $showEmpty ?? true;
$groups = \App\Services\ShopService::groupCategoryTree($categories);
?>
<?php if ($showEmpty): ?>
<option value=""><?= e($emptyLabel) ?></option>
<?php endif; ?>
<?php foreach ($groups as $group): ?>
<?php
  $parent = $group['parent'];
  $children = $group['children'];
  $parentId = (int) ($parent['id'] ?? 0);
  $parentName = (string) ($parent['name'] ?? '');
?>
<option value="<?= $parentId ?>"<?= $selectedId === $parentId ? ' selected' : '' ?>><?= e($parentName) ?><?= $children !== [] ? '  · 1차' : '' ?></option>
<?php if ($children !== []): ?>
<optgroup label="<?= e($parentName) ?> · 2차">
  <?php foreach ($children as $child): ?>
  <?php $childId = (int) ($child['id'] ?? 0); ?>
  <option value="<?= $childId ?>"<?= $selectedId === $childId ? ' selected' : '' ?>>└ <?= e((string) ($child['name'] ?? '')) ?></option>
  <?php endforeach; ?>
</optgroup>
<?php endif; ?>
<?php endforeach; ?>
