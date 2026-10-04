<?php
use App\Services\ShopAdminService;
use App\Services\ShopProductImageService;

$list = $list ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'summary' => [], 'filters' => []];
$items = $list['items'] ?? [];
$f = $list['filters'] ?? [];
$s = $list['summary'] ?? [];
$categories = $categories ?? [];
$flash = (string) ($flash ?? '');
$hasFilter = ($f['q'] ?? '') !== '' || !empty($f['category_id']) || ($f['product_status'] ?? '') !== '';
$queryParams = array_filter([
    'q' => (string) ($f['q'] ?? ''),
    'category_id' => !empty($f['category_id']) ? (string) (int) $f['category_id'] : '',
    'product_status' => (string) ($f['product_status'] ?? ''),
], static fn (string $v): bool => $v !== '');

$thumb = static function (string $path): array {
    $path = ShopProductImageService::normalizePublicPath($path);
    $file = $path !== '' ? public_path(ltrim($path, '/')) : '';
    $ready = $file !== '' && is_file($file);
    return [$ready, $ready ? ShopProductImageService::resolveUrl($path) : ''];
};
?>
<div class="admin-head">
  <div>
    <h1>이미지DB</h1>
    <p>판매할 상품을 검색한 뒤 상세 이미지를 내려받습니다. 상품 정보는 여기서 수정할 수 없습니다.</p>
  </div>
</div>

<?php if ($flash !== ''): ?>
<div class="partner-flash" role="status"><?= e($flash) ?></div>
<?php endif; ?>

<form class="admin-filter-bar" method="get" action="<?= url('partner/images') ?>">
  <input class="admin-input admin-input--search" type="search" name="q" value="<?= e((string) ($f['q'] ?? '')) ?>" placeholder="상품명, 상품코드 검색">
  <?php
    $selectedId = (int) ($f['category_id'] ?? 0);
    require view_path('admin/partials/category-filter.php');
  ?>
  <select class="admin-select" name="product_status">
    <option value="">상품상태 전체</option>
    <option value="active"<?= (($f['product_status'] ?? '') === 'active') ? ' selected' : '' ?>>판매중</option>
    <option value="soldout"<?= (($f['product_status'] ?? '') === 'soldout') ? ' selected' : '' ?>>품절</option>
  </select>
  <button class="admin-btn admin-btn--primary" type="submit">검색</button>
  <?php if ($hasFilter): ?>
  <a class="admin-btn" href="<?= url('partner/images') ?>">초기화</a>
  <?php endif; ?>
</form>

<div class="admin-kpis admin-kpis--sub">
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">판매 상품</div><div class="val"><?= number_format((int) ($s['total'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">이미지 있는 상품</div><div class="val"><?= number_format((int) ($s['with_images'] ?? 0)) ?></div></div>
</div>
<p class="admin-meta-line">총 <b><?= number_format((int) ($list['total'] ?? 0)) ?></b>개<?php if (($list['pages'] ?? 1) > 1): ?> · <?= (int) ($list['page'] ?? 1) ?> / <?= (int) $list['pages'] ?> 페이지<?php endif; ?> · 한 번에 최대 100개까지 받을 수 있습니다.</p>

<form method="post" action="<?= url('partner/images/download') ?>" id="partnerDownloadForm">
  <input type="hidden" name="q" value="<?= e((string) ($f['q'] ?? '')) ?>">
  <input type="hidden" name="category_id" value="<?= (int) ($f['category_id'] ?? 0) ?>">
  <input type="hidden" name="product_status" value="<?= e((string) ($f['product_status'] ?? '')) ?>">
  <div class="admin-head-actions partner-download-actions">
    <button class="admin-btn admin-btn--primary" type="submit" name="scope" value="selected">선택 상품 받기</button>
    <button class="admin-btn" type="submit" name="scope" value="filtered">검색 결과 받기</button>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th><input type="checkbox" id="partnerCheckAll" aria-label="이 페이지 전체 선택"></th>
          <th>상품명</th>
          <th>상품코드</th>
          <th>대표 이미지</th>
          <th>판매가</th>
          <th>규격</th>
          <th>카테고리</th>
          <th>상품상태</th>
          <th>다운로드</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($items === []): ?>
        <tr><td colspan="9" class="empty"><?= $hasFilter ? '검색 결과가 없습니다.' : '등록된 상품이 없습니다.' ?></td></tr>
      <?php else: ?>
        <?php foreach ($items as $row): ?>
        <?php
          [$repReady, $repUrl] = $thumb((string) ($row['representative_image'] ?? ''));
          $productId = (int) ($row['id'] ?? 0);
          $productName = (string) ($row['name'] ?? '');
          $price = (int) ($row['price'] ?? 0);
          $sale = (int) ($row['sale_price'] ?? 0);
          $onSale = $sale > 0 && $sale < $price;
          $width = $row['width_mm'] ?? null;
          $height = $row['height_mm'] ?? null;
          $size = ($width !== null && $width !== '' && $height !== null && $height !== '')
              ? rtrim(rtrim(number_format((float) $width, 2, '.', ''), '0'), '.') . '×' . rtrim(rtrim(number_format((float) $height, 2, '.', ''), '0'), '.') . 'mm'
              : '';
          $material = trim((string) ($row['material'] ?? ''));
          $labels = (int) ($row['labels_per_sheet'] ?? 0);
          $specMeta = array_filter([
              $material,
              $labels > 0 ? ($labels . '칸') : '',
          ], static fn (string $v): bool => $v !== '');
        ?>
        <tr>
          <td>
            <input type="checkbox" name="ids[]" value="<?= $productId ?>" class="js-partner-check" aria-label="<?= e($productName) ?> 선택">
          </td>
          <td><strong><?= e($productName) ?></strong></td>
          <td><code><?= e((string) ($row['sku'] ?? '')) ?></code></td>
          <td>
            <?php if ($repReady): ?>
            <button type="button" class="admin-thumb-btn js-image-preview" data-src="<?= e($repUrl) ?>" data-title="<?= e($productName) ?> 대표 이미지">
              <img class="admin-thumb admin-thumb--mockup" src="<?= e($repUrl) ?>" alt="">
            </button>
            <?php else: ?><span class="admin-muted">없음</span><?php endif; ?>
          </td>
          <td>
            <?php if ($onSale): ?>
            <strong><?= number_format($sale) ?>원</strong>
            <div class="admin-muted"><?= number_format($price) ?>원</div>
            <?php else: ?>
            <strong><?= number_format($price) ?>원</strong>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($size !== ''): ?><?= e($size) ?><?php else: ?><span class="admin-muted">-</span><?php endif; ?>
            <?php if ($specMeta !== []): ?><div class="admin-muted"><?= e(implode(' · ', $specMeta)) ?></div><?php endif; ?>
          </td>
          <td><?= e((string) ($row['category_name'] ?? '-')) ?></td>
          <td><?= e(ShopAdminService::productStatusLabel((string) ($row['product_status'] ?? ''))) ?></td>
          <td>
            <a class="admin-btn admin-btn--sm" href="<?= url('partner/images/download/' . $productId) ?>">이미지 받기</a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</form>
<?php if (($list['pages'] ?? 1) > 1): ?>
<?php
  $page = (int) ($list['page'] ?? 1);
  $pages = (int) ($list['pages'] ?? 1);
  $basePath = 'partner/images';
  require view_path('admin/partials/pagination.php');
?>
<?php endif; ?>
<div id="adminLightbox" class="admin-lightbox" hidden>
  <div class="admin-lightbox-backdrop js-lightbox-close"></div>
  <div class="admin-lightbox-panel" role="dialog" aria-modal="true" aria-labelledby="adminLightboxTitle">
    <div class="admin-lightbox-head">
      <strong id="adminLightboxTitle">이미지 미리보기</strong>
      <button type="button" class="admin-lightbox-close js-lightbox-close" aria-label="닫기">×</button>
    </div>
    <div class="admin-lightbox-body">
      <img id="adminLightboxImg" src="" alt="">
    </div>
  </div>
</div>
<script src="<?= js('partner-images.js') ?>"></script>
