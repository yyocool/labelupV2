<?php
use App\Services\ShopAdminService;

$list = $list ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'summary' => [], 'filters' => []];
$items = $list['items'] ?? [];
$f = $list['filters'] ?? [];
$s = $list['summary'] ?? [];
$categories = $categories ?? [];
$statuses = ['active', 'soldout', 'hidden', 'draft'];
$hasFilter = ($f['q'] ?? '') !== ''
    || !empty($f['category_id'])
    || ($f['registered'] ?? '') !== ''
    || ($f['product_status'] ?? '') !== '';
$queryParams = array_filter([
    'q' => (string) ($f['q'] ?? ''),
    'registered' => (string) ($f['registered'] ?? ''),
    'category_id' => !empty($f['category_id']) ? (string) (int) $f['category_id'] : '',
    'product_status' => (string) ($f['product_status'] ?? ''),
], static fn (string $v): bool => $v !== '');
?>
<div class="admin-head">
  <div>
    <h1>상세페이지관리</h1>
    <p>등록된 상품의 상세페이지 생성 여부를 확인하고, 공통·카테고리별 헤더/푸터를 설정할 수 있습니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary js-product-page-settings">공통헤더/푸터 설정</button>
    <button type="button" class="admin-btn js-product-page-category-settings">카테고리별 헤더/푸터 설정</button>
  </div>
</div>

<form class="admin-filter-bar" method="get" action="<?= url('admin/content/product-detail-pages') ?>">
  <input class="admin-input admin-input--search" type="search" name="q" value="<?= e((string) ($f['q'] ?? '')) ?>" placeholder="상품명, 상품코드 검색">
  <select class="admin-select" name="registered">
    <option value="">상세페이지 전체</option>
    <option value="yes"<?= (($f['registered'] ?? '') === 'yes') ? ' selected' : '' ?>>등록</option>
    <option value="no"<?= (($f['registered'] ?? '') === 'no') ? ' selected' : '' ?>>미등록</option>
  </select>
  <?php
    $selectedId = (int) ($f['category_id'] ?? 0);
    require view_path('admin/partials/category-filter.php');
  ?>
  <select class="admin-select" name="product_status">
    <option value="">상품상태 전체</option>
    <?php foreach ($statuses as $code): ?>
    <option value="<?= e($code) ?>"<?= (($f['product_status'] ?? '') === $code) ? ' selected' : '' ?>><?= e(ShopAdminService::productStatusLabel($code)) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="admin-btn admin-btn--primary" type="submit">검색</button>
  <?php if ($hasFilter): ?>
  <a class="admin-btn" href="<?= url('admin/content/product-detail-pages') ?>">초기화</a>
  <?php endif; ?>
</form>

<div class="admin-kpis admin-kpis--sub">
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">대상 상품</div><div class="val"><?= number_format((int) ($s['total'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">상세페이지 등록</div><div class="val"><?= number_format((int) ($s['registered'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">미등록</div><div class="val"><?= number_format((int) ($s['unregistered'] ?? 0)) ?></div></div>
</div>
<p class="admin-meta-line">총 <b><?= number_format((int) ($list['total'] ?? 0)) ?></b>개<?php if (($list['pages'] ?? 1) > 1): ?> · <?= (int) ($list['page'] ?? 1) ?> / <?= (int) $list['pages'] ?> 페이지<?php endif; ?> · 생성 방식은 이후 별도로 적용됩니다.</p>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>상세페이지</th>
        <th>상품명</th>
        <th>상품코드</th>
        <th>대표 이미지</th>
        <th>헤더 이미지</th>
        <th>상품규격</th>
        <th>촬영</th>
        <th>카테고리</th>
        <th>상품상태</th>
        <th>작업</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($items === []): ?>
      <tr><td colspan="10" class="empty"><?= $hasFilter ? '검색 결과가 없습니다.' : '등록된 상품이 없습니다.' ?></td></tr>
    <?php else: ?>
      <?php foreach ($items as $row): ?>
      <?php
        $registered = !empty($row['has_detail_page']);
        $previewUrl = url('admin/content/product-detail-pages/preview/' . (int) ($row['id'] ?? 0));
      ?>
      <tr data-product-id="<?= (int) ($row['id'] ?? 0) ?>">
        <td class="js-detail-status">
          <?php if ($registered): ?>
          <span class="admin-badge admin-badge--ok">등록</span>
          <?php else: ?>
          <span class="admin-badge admin-badge--pending">미등록</span>
          <?php endif; ?>
        </td>
        <td><strong><?= e((string) ($row['name'] ?? '')) ?></strong></td>
        <td><code><?= e((string) ($row['sku'] ?? '')) ?></code></td>
        <td>
          <?php
            $repPath = \App\Services\ShopProductImageService::normalizePublicPath((string) ($row['representative_image'] ?? ''));
            $repFile = $repPath !== '' ? public_path(ltrim($repPath, '/')) : '';
            $repReady = $repFile !== '' && is_file($repFile);
          ?>
          <?php if ($repReady): ?>
          <button type="button" class="admin-thumb-btn js-image-preview" data-src="<?= e(\App\Services\ShopProductImageService::resolveUrl($repPath)) ?>" data-title="<?= e((string) ($row['name'] ?? '대표 이미지')) ?>">
            <img class="admin-thumb admin-thumb--mockup" src="<?= e(\App\Services\ShopProductImageService::resolveUrl($repPath)) ?>" alt="<?= e((string) ($row['sku'] ?? '대표 이미지')) ?>">
          </button>
          <?php else: ?>
          <span class="admin-muted">없음</span>
          <?php endif; ?>
        </td>
        <td>
          <?php
            $headerPath = \App\Services\ShopProductImageService::normalizePublicPath((string) ($row['header_image'] ?? ''));
            $headerFile = $headerPath !== '' ? public_path(ltrim($headerPath, '/')) : '';
            $headerReady = $headerFile !== '' && is_file($headerFile);
          ?>
          <?php if ($headerReady): ?>
          <button type="button" class="admin-thumb-btn js-image-preview" data-src="<?= e(\App\Services\ShopProductImageService::resolveUrl($headerPath)) ?>" data-title="<?= e((string) ($row['name'] ?? '헤더 이미지')) ?>">
            <img class="admin-thumb admin-thumb--header" src="<?= e(\App\Services\ShopProductImageService::resolveUrl($headerPath)) ?>" alt="<?= e((string) ($row['sku'] ?? '헤더 이미지')) ?>">
          </button>
          <?php else: ?>
          <span class="admin-muted">없음</span>
          <?php endif; ?>
        </td>
        <td>
          <?php
            $specPath = \App\Services\ShopProductImageService::normalizePublicPath((string) ($row['spec_sheet_image'] ?? ''));
            $specFile = $specPath !== '' ? public_path(ltrim($specPath, '/')) : '';
            $specReady = $specFile !== '' && is_file($specFile);
          ?>
          <?php if ($specReady): ?>
          <button type="button" class="admin-thumb-btn js-image-preview" data-src="<?= e(\App\Services\ShopProductImageService::resolveUrl($specPath)) ?>" data-title="<?= e((string) ($row['name'] ?? '상품규격')) ?>">
            <img class="admin-thumb admin-thumb--sheet" src="<?= e(\App\Services\ShopProductImageService::resolveUrl($specPath)) ?>" alt="<?= e((string) ($row['sku'] ?? '상품규격')) ?>">
          </button>
          <?php else: ?>
          <span class="admin-muted">없음</span>
          <?php endif; ?>
        </td>
        <td>
          <?php
            $shootPath = \App\Services\ShopProductImageService::normalizePublicPath((string) ($row['shoot_image'] ?? ''));
            $shootFile = $shootPath !== '' ? public_path(ltrim($shootPath, '/')) : '';
            $shootReady = $shootFile !== '' && is_file($shootFile);
          ?>
          <?php if ($shootReady): ?>
          <button type="button" class="admin-thumb-btn js-image-preview" data-src="<?= e(\App\Services\ShopProductImageService::resolveUrl($shootPath)) ?>" data-title="<?= e((string) ($row['name'] ?? '촬영')) ?>">
            <img class="admin-thumb admin-thumb--shoot" src="<?= e(\App\Services\ShopProductImageService::resolveUrl($shootPath)) ?>" alt="<?= e((string) ($row['sku'] ?? '촬영')) ?>">
          </button>
          <?php else: ?>
          <span class="admin-muted">없음</span>
          <?php endif; ?>
        </td>
        <td><?= e((string) ($row['category_name'] ?? '-')) ?></td>
        <td><?= e(ShopAdminService::productStatusLabel((string) ($row['product_status'] ?? ''))) ?></td>
        <td>
          <div class="admin-table-actions">
            <button
              type="button"
              class="admin-btn admin-btn--sm js-product-detail-edit"
              data-product-id="<?= (int) ($row['id'] ?? 0) ?>"
              data-product-name="<?= e((string) ($row['name'] ?? '상품')) ?>"
            >수정</button>
            <button
              type="button"
              class="admin-btn admin-btn--sm js-product-detail-preview"
              data-preview-url="<?= e($previewUrl) ?>"
              data-preview-title="<?= e((string) ($row['name'] ?? '상품 상세')) ?>"
            >미리보기</button>
            <button
              type="button"
              class="admin-btn admin-btn--sm js-product-detail-images"
              data-download-url="<?= e(url('admin/content/product-detail-pages/images/' . (int) ($row['id'] ?? 0))) ?>"
            >이미지</button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php if (($list['pages'] ?? 1) > 1): ?>
<?php
  $page = (int) ($list['page'] ?? 1);
  $pages = (int) ($list['pages'] ?? 1);
  $basePath = 'admin/content/product-detail-pages';
  require view_path('admin/partials/pagination.php');
?>
<?php endif; ?>
<script>window.SHOP_PAGE_SETTINGS=<?= json_encode($pageSettings ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
<?php require view_path('admin/content/partials/page-settings-modal.php'); ?>
<?php require view_path('admin/content/partials/page-category-settings-modal.php'); ?>
<div id="productDetailPreviewModal" class="admin-modal" hidden>
  <div class="admin-modal-backdrop js-product-detail-preview-close"></div>
  <div class="admin-modal-dialog admin-modal-dialog--preview" role="dialog" aria-modal="true" aria-labelledby="productDetailPreviewTitle">
    <div class="admin-modal-head">
      <h3 id="productDetailPreviewTitle">상품 상세 미리보기</h3>
      <div class="admin-modal-head-actions">
        <a id="productDetailPreviewOpen" class="admin-btn admin-btn--sm" href="#" target="_blank" rel="noopener">새 창</a>
        <button type="button" class="admin-modal-close js-product-detail-preview-close" aria-label="닫기">×</button>
      </div>
    </div>
    <div class="admin-modal-body admin-preview-frame-wrap">
      <iframe id="productDetailPreviewFrame" title="상품 상세 미리보기" src="about:blank"></iframe>
    </div>
  </div>
</div>
<div id="productDetailHtmlModal" class="admin-modal" hidden>
  <div class="admin-modal-backdrop js-product-detail-html-close"></div>
  <div class="admin-modal-dialog admin-modal-dialog--detail-html" role="dialog" aria-modal="true" aria-labelledby="productDetailHtmlTitle">
    <div class="admin-modal-head">
      <h3 id="productDetailHtmlTitle">상품 상세 내용 수정</h3>
      <button type="button" class="admin-modal-close js-product-detail-html-close" aria-label="닫기">×</button>
    </div>
    <form id="productDetailHtmlForm" class="admin-modal-body admin-detail-html-form">
      <input type="hidden" name="product_id" id="productDetailHtmlProductId" value="">
      <p class="admin-detail-html-meta" id="productDetailHtmlMeta"></p>
      <div class="admin-field admin-field--editor">
        <label for="productDetailHtmlEditor">상품 상세 내용</label>
        <textarea id="productDetailHtmlEditor" class="js-product-detail-html" rows="16" placeholder="상품 상세페이지에 표시할 내용을 입력하세요."></textarea>
      </div>
    </form>
    <div class="admin-modal-foot">
      <p class="admin-muted admin-detail-html-hint">글·이미지·표 등 WYSIWYG 내용이 상품 상세페이지 본문에 그대로 표시됩니다. 비우면 상세 본문 등록이 해제됩니다.</p>
      <button type="button" class="admin-btn js-product-detail-html-close">취소</button>
      <button type="submit" form="productDetailHtmlForm" class="admin-btn admin-btn--primary" id="productDetailHtmlSaveBtn">저장</button>
    </div>
  </div>
</div>
<div id="imageQueueModal" class="admin-modal admin-modal--image-queue" hidden>
  <div class="admin-modal-backdrop js-image-queue-close"></div>
  <div class="admin-modal-dialog admin-modal-dialog--image-queue" role="dialog" aria-modal="true" aria-labelledby="imageQueueTitle">
    <div class="admin-modal-head">
      <h3 id="imageQueueTitle">이미지 올리고 순서 정하기</h3>
      <button type="button" class="admin-modal-close js-image-queue-close" aria-label="닫기">×</button>
    </div>
    <div class="admin-modal-body">
      <div class="admin-image-drop" id="imageQueueDrop">
        <p class="admin-image-drop__lead">여기로 이미지를 끌어다 놓으세요</p>
        <p class="admin-muted admin-image-drop__sub">
          JPG · PNG · GIF · WEBP / 한 장당 최대 <b id="imageQueueMaxLabel">20MB</b> · 여러 장 한꺼번에 가능
        </p>
        <button type="button" class="admin-btn admin-btn--primary admin-btn--sm" id="imageQueueAdd">파일 선택</button>
        <input type="file" id="imageQueueInput" accept="image/jpeg,image/png,image/gif,image/webp" multiple hidden>
      </div>
      <div class="admin-image-queue-progress" id="imageQueueBar" hidden>
        <div class="admin-image-queue-progress__fill" id="imageQueueBarFill"></div>
      </div>
      <p class="admin-image-queue-status" id="imageQueueStatus" hidden role="status"></p>
      <p class="admin-muted admin-image-queue-hint" id="imageQueueHint" hidden>
        썸네일을 끌어다 놓거나 <b>◀ ▶</b> 버튼으로 순서를 바꾼 뒤 삽입하세요. 왼쪽부터 차례로 들어갑니다.
      </p>
      <div class="admin-image-queue" id="imageQueueList" hidden></div>
    </div>
    <div class="admin-modal-foot">
      <p class="admin-muted admin-image-queue-count" id="imageQueueCount"></p>
      <button type="button" class="admin-btn admin-btn--sm" id="imageQueueClear" hidden>전체 비우기</button>
      <button type="button" class="admin-btn js-image-queue-close">취소</button>
      <button type="button" class="admin-btn admin-btn--primary" id="imageQueueApply" disabled>에디터에 삽입</button>
    </div>
  </div>
</div>
<script src="<?= js('product-page-settings.js') ?>"></script>