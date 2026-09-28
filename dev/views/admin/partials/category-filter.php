<?php
/** @var array<int, array<string, mixed>> $categories */
/** @var int $selectedId */
$categories = $categories ?? [];
$selectedId = (int) ($selectedId ?? 0);
$groups = \App\Services\ShopService::groupCategoryTree($categories);
$selectedParentId = 0;
$selectedChildId = 0;
$activeChildren = [];
$tree = [];
foreach ($groups as $group) {
    $parent = $group['parent'];
    $children = $group['children'];
    $parentId = (int) ($parent['id'] ?? 0);
    $childRows = [];
    $hitChild = false;
    foreach ($children as $child) {
        $childId = (int) ($child['id'] ?? 0);
        $childRows[] = ['id' => $childId, 'name' => (string) ($child['name'] ?? '')];
        if ($childId === $selectedId) {
            $hitChild = true;
            $selectedChildId = $childId;
        }
    }
    $tree[] = [
        'id' => $parentId,
        'name' => (string) ($parent['name'] ?? ''),
        'children' => $childRows,
    ];
    if ($parentId === $selectedId || $hitChild) {
        $selectedParentId = $parentId;
        $activeChildren = $children;
        if ($parentId === $selectedId) {
            $selectedChildId = 0;
        }
    }
}
$hasChildren = $activeChildren !== [];
?>
<div class="admin-cat-filter<?= $hasChildren ? ' has-children' : '' ?>" data-tree="<?= e(json_encode($tree, JSON_UNESCAPED_UNICODE)) ?>">
  <label class="admin-cat-filter-level">
    <span>1차</span>
    <select class="admin-select admin-select--category js-admin-cat-parent">
      <option value="">전체</option>
      <?php foreach ($groups as $group): ?>
      <?php $parentId = (int) ($group['parent']['id'] ?? 0); ?>
      <option value="<?= $parentId ?>"<?= $selectedParentId === $parentId ? ' selected' : '' ?>><?= e((string) ($group['parent']['name'] ?? '')) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="admin-cat-filter-level admin-cat-filter-level--sub js-admin-cat-child-wrap"<?= $hasChildren ? '' : ' hidden' ?>>
    <span>2차</span>
    <select class="admin-select admin-select--category js-admin-cat-child" name="category_id">
      <?php if ($selectedParentId <= 0): ?>
      <option value="">전체</option>
      <?php else: ?>
      <option value="<?= $selectedParentId ?>"<?= $selectedChildId <= 0 ? ' selected' : '' ?>>전체</option>
      <?php foreach ($activeChildren as $child): ?>
      <?php $childId = (int) ($child['id'] ?? 0); ?>
      <option value="<?= $childId ?>"<?= $selectedChildId === $childId ? ' selected' : '' ?>><?= e((string) ($child['name'] ?? '')) ?></option>
      <?php endforeach; ?>
      <?php endif; ?>
    </select>
  </label>
</div>
<script>
(function () {
  document.querySelectorAll('.admin-cat-filter').forEach(function (wrap) {
    if (wrap.dataset.bound === '1') return;
    wrap.dataset.bound = '1';
    var tree = [];
    try { tree = JSON.parse(wrap.getAttribute('data-tree') || '[]'); } catch (e) { tree = []; }
    var parentSel = wrap.querySelector('.js-admin-cat-parent');
    var childSel = wrap.querySelector('.js-admin-cat-child');
    var childWrap = wrap.querySelector('.js-admin-cat-child-wrap');
    if (!parentSel || !childSel) return;
    parentSel.addEventListener('change', function () {
      var parentId = parentSel.value;
      var group = tree.find(function (g) { return String(g.id) === String(parentId); });
      var children = (group && Array.isArray(group.children)) ? group.children : [];
      childSel.innerHTML = '';
      if (!parentId) {
        var empty = document.createElement('option');
        empty.value = '';
        empty.textContent = '전체';
        childSel.appendChild(empty);
        wrap.classList.remove('has-children');
        if (childWrap) childWrap.hidden = true;
        return;
      }
      var all = document.createElement('option');
      all.value = String(parentId);
      all.textContent = '전체';
      childSel.appendChild(all);
      children.forEach(function (child) {
        var opt = document.createElement('option');
        opt.value = String(child.id);
        opt.textContent = child.name || '';
        childSel.appendChild(opt);
      });
      wrap.classList.toggle('has-children', children.length > 0);
      if (childWrap) childWrap.hidden = children.length === 0;
    });
  });
})();
</script>
