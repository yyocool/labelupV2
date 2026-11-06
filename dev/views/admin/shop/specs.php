<?php use App\Services\ShopAdminService; use App\Services\ShopProductImageService; ?>
<div class="admin-head">
  <div><h1>용지 규격</h1><p>라벨·태그 용지의 크기·재질·형태 규격을 관리합니다. 왼쪽 손잡이(⠿)를 끌어 노출 순서를 바꾸면 쇼핑몰 「자주 찾는 라벨 규격」에 위에서부터 사용 중인 규격 8개가 나옵니다.</p></div>
  <div class="admin-head-actions"><button type="button" class="admin-btn admin-btn--primary js-shop-add" data-entity="spec">+ 규격 추가</button></div>
</div>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th class="admin-sort-col">순서</th><th>ID</th><th>이미지</th><th>규격명</th><th>상품 SKU</th><th>종류</th><th>용지</th><th>크기(mm)</th><th>재질</th><th>형태</th><th>칸수</th><th>배치(열×행)</th><th>상태</th><th>관리</th></tr></thead>
    <tbody id="specSortList">
    <?php if (empty($items)): ?><tr><td colspan="14" class="empty">등록된 규격이 없습니다.</td></tr><?php else: ?>
    <?php foreach ($items as $i => $row): ?>
    <tr class="js-spec-row" data-id="<?= (int) $row['id'] ?>">
      <td class="admin-sort-col"><span class="admin-sort-handle js-spec-handle" title="끌어서 순서 변경" aria-label="끌어서 순서 변경">⠿</span><span class="admin-sort-no js-spec-no"><?= $i + 1 ?></span></td>
      <td><?= (int) $row['id'] ?></td>
      <td>
        <?php if (!empty($row['image_path'])): ?>
        <img class="admin-thumb admin-thumb--spec" src="<?= e(ShopProductImageService::resolveUrl((string) $row['image_path'])) ?>" alt="<?= e($row['name']) ?>">
        <?php else: ?><span class="admin-muted">-</span><?php endif; ?>
      </td>
      <td><?= e($row['name']) ?></td>
      <td class="admin-spec-skus">
        <?php $skus = ShopAdminService::splitSkuList($row['product_skus'] ?? null); ?>
        <?php if ($skus === []): ?>
        <span class="admin-muted" title="이 규격을 쓰는 상품이 없어 편집기 용지선택에 나오지 않습니다">연결 상품 없음</span>
        <?php else: ?>
        <?php foreach ($skus as $sku): ?><span class="admin-badge admin-badge--sku"><?= e($sku) ?></span><?php endforeach; ?>
        <?php endif; ?>
      </td>
      <td><?= (($row['kind'] ?? 'label') === 'tag') ? '태그' : '라벨' ?></td>
      <td><?= e($row['paper_size'] ?? '') ?: '<span class="admin-muted">-</span>' ?></td>
      <td><?= e($row['width_mm']) ?> × <?= e($row['height_mm']) ?></td>
      <td><?= e($row['material'] ?? '-') ?></td>
      <td><?= e($row['shape'] ?? 'rect') ?></td>
      <td><?= e($row['labels_per_sheet'] ?? '-') ?></td>
      <td>
        <?php if (!empty($row['columns_count']) && !empty($row['rows_count'])): ?>
        <?= (int) $row['columns_count'] ?> × <?= (int) $row['rows_count'] ?>
        <?php else: ?><span class="admin-muted">-</span><?php endif; ?>
      </td>
      <?php $specOn = !empty($row['is_active']); ?>
      <td><button type="button" class="admin-badge admin-badge--toggle <?= $specOn ? 'admin-badge--ok' : 'admin-badge--err' ?> js-spec-active" data-id="<?= (int) $row['id'] ?>" data-active="<?= $specOn ? '1' : '0' ?>" aria-pressed="<?= $specOn ? 'true' : 'false' ?>" title="클릭하면 <?= $specOn ? '사용안함' : '사용' ?>으로 바뀝니다"><?= $specOn ? '사용' : '사용안함' ?></button></td>
      <td>
        <button type="button" class="admin-btn admin-btn--sm js-shop-edit" data-entity="spec" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
        <button type="button" class="admin-btn admin-btn--sm js-shop-delete" data-entity="spec" data-id="<?= (int) $row['id'] ?>">삭제</button>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require view_path('admin/shop/partials/modal.php'); ?>
<script src="<?= js('shop-admin.js') ?>"></script>
