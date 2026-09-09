<?php
/** @var App\Services\ShopService $shopService */
/** @var array<string, mixed> $product */
/** @var array<int, array<string, mixed>> $related */
/** @var array<string, mixed> $pageLayout */
$pageLayout = $pageLayout ?? [];
$unit = $shopService->unitPrice($product);
$thumb = $shopService->productThumb($product);
$isSoldout = ($product['status'] ?? '') === 'soldout' || (int) ($product['stock_qty'] ?? 0) <= 0;
$onSale = !empty($product['sale_price']) && (int) $product['sale_price'] < (int) $product['price'];
$headerHtml = trim((string) ($pageLayout['header_html'] ?? ''));
$footerHtml = trim((string) ($pageLayout['footer_html'] ?? ''));
$headerImageUrl = trim((string) ($pageLayout['header_image_url'] ?? ''));
$footerImageUrl = trim((string) ($pageLayout['footer_image_url'] ?? ''));
$hasHeader = !empty($pageLayout['has_header']);
$hasFooter = !empty($pageLayout['has_footer']);
$hasEditable = $shopService->hasEditableSpec($product);
$compatFormtec = \App\Helpers\ShopCompatHelper::parse($product['compat_formtec'] ?? null);
$compatIlabel = \App\Helpers\ShopCompatHelper::parse($product['compat_ilabel'] ?? null);
$compatAnylabel = \App\Helpers\ShopCompatHelper::parse($product['compat_anylabel'] ?? null);
$hasCompat = $compatFormtec !== [] || $compatIlabel !== [] || $compatAnylabel !== [];
$detailHtml = trim((string) ($product['detail_html'] ?? ''));
$description = trim((string) ($product['description'] ?? ''));
$descParsed = \App\Helpers\ShopProductDescriptionHelper::parse($description);
$descSpecs = $descParsed['specs'];
$descProse = $descParsed['prose'];
$descSpecRows = \App\Helpers\ShopProductDescriptionHelper::pairRows($descSpecs);
$hasDetailBody = $detailHtml !== '' || $description !== '';
?>
<article class="shop-detail">
  <div class="shop-detail-gallery">
    <div class="shop-detail-gallery__frame">
      <img src="<?= e($thumb) ?>" alt="<?= e((string) $product['name']) ?>">
    </div>
  </div>
  <div class="shop-detail-info">
    <div class="shop-detail-topline">
      <?php if (!empty($product['category_name'])): ?>
      <span class="shop-product-cat"><?= e((string) $product['category_name']) ?></span>
      <?php endif; ?>
      <?php if ($isSoldout): ?>
      <span class="shop-detail-badge shop-detail-badge--soldout">품절</span>
      <?php elseif ($onSale): ?>
      <span class="shop-detail-badge shop-detail-badge--sale">할인</span>
      <?php endif; ?>
    </div>

    <h1><?= e((string) $product['name']) ?></h1>
    <p class="shop-detail-sku"><span>SKU</span> <?= e((string) $product['sku']) ?></p>

    <div class="shop-detail-price">
      <?php if ($onSale): ?>
      <del><?= e($shopService->formatPrice((int) $product['price'])) ?></del>
      <?php endif; ?>
      <strong><?= e($shopService->formatPrice($unit)) ?></strong>
      <em class="shop-detail-stock <?= $isSoldout ? 'is-soldout' : '' ?>">
        <?= $isSoldout ? '품절' : '재고 ' . number_format((int) $product['stock_qty']) . '개' ?>
      </em>
    </div>

    <?php if (!empty($product['spec_name']) || !empty($product['material']) || !empty($product['labels_per_sheet'])): ?>
    <ul class="shop-detail-specs">
      <?php if ($product['width_mm'] !== null && $product['width_mm'] !== '' && $product['height_mm'] !== null && $product['height_mm'] !== ''): ?>
      <li>
        <span>규격</span>
        <strong><?= e((string) ($product['width_mm'] ?? '')) ?> × <?= e((string) ($product['height_mm'] ?? '')) ?> mm</strong>
      </li>
      <?php endif; ?>
      <?php if (!empty($product['material'])): ?>
      <li>
        <span>재질</span>
        <strong><?= e((string) $product['material']) ?></strong>
      </li>
      <?php endif; ?>
      <?php if (!empty($product['labels_per_sheet'])): ?>
      <li>
        <span>칸수</span>
        <strong><?= e((string) $product['labels_per_sheet']) ?>칸 / 시트</strong>
      </li>
      <?php endif; ?>
    </ul>
    <?php endif; ?>

    <?php if ($hasCompat): ?>
    <div class="shop-detail-compat">
      <span class="shop-detail-compat-label">호환 코드</span>
      <div class="shop-detail-compat-list">
        <?php foreach ($compatFormtec as $code): ?>
        <span class="shop-detail-compat-chip">폼텍 <?= e($code) ?></span>
        <?php endforeach; ?>
        <?php foreach ($compatIlabel as $code): ?>
        <span class="shop-detail-compat-chip">아이라벨 <?= e($code) ?></span>
        <?php endforeach; ?>
        <?php foreach ($compatAnylabel as $code): ?>
        <span class="shop-detail-compat-chip">애니라벨 <?= e($code) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="shop-detail-actions">
      <?php if (!$isSoldout): ?>
      <div class="shop-detail-buy">
        <div class="shop-qty" aria-label="수량">
          <button type="button" class="shop-qty-btn" data-qty-minus aria-label="수량 감소">−</button>
          <input type="number" id="productQty" value="1" min="1" max="<?= (int) $product['stock_qty'] ?>" aria-label="수량 입력">
          <button type="button" class="shop-qty-btn" data-qty-plus aria-label="수량 증가">+</button>
        </div>
        <button type="button" class="shop-btn shop-btn--primary shop-detail-buy__cart" data-add-cart="<?= (int) $product['id'] ?>" data-qty-input="#productQty">장바구니 담기</button>
      </div>
      <?php endif; ?>

      <?php if ($hasEditable || !$isSoldout): ?>
      <div class="shop-detail-secondary">
        <?php if ($hasEditable): ?>
        <a class="shop-btn shop-btn--outline" href="<?= e($shopService->editorUrlForProduct($product)) ?>">이 규격으로 편집</a>
        <?php endif; ?>
        <?php if (!$isSoldout): ?>
        <a class="shop-btn shop-btn--ghost" href="<?= url('shop/cart') ?>">장바구니 보기</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</article>

<?php if ($hasHeader || $hasDetailBody || $hasFooter): ?>
<section class="shop-detail-longform" aria-label="상품 상세 내용">
  <?php if ($hasHeader): ?>
  <div class="shop-detail-page-block shop-detail-page-block--header">
    <?php if ($headerImageUrl !== ''): ?>
    <div class="shop-detail-page-media">
      <img src="<?= e($headerImageUrl) ?>" alt="상품 상세 헤더 이미지">
    </div>
    <?php endif; ?>
    <?php if ($headerHtml !== ''): ?>
    <div class="shop-detail-page-html"><?= $headerHtml ?></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($hasDetailBody): ?>
  <div class="shop-detail-longform__body">
    <?php if ($detailHtml !== ''): ?>
    <div class="shop-detail-page-html shop-detail-page-html--full"><?= $detailHtml ?></div>
    <?php else: ?>
    <div class="shop-detail-desc shop-detail-desc--full">
      <h2>상품 설명</h2>
      <?php if ($descSpecRows !== []): ?>
      <div class="shop-spec-table-wrap">
        <table class="shop-spec-table">
          <tbody>
          <?php foreach ($descSpecRows as $pair): ?>
            <tr>
              <?php for ($c = 0; $c < 2; $c++): ?>
                <?php $cell = $pair[$c] ?? null; ?>
                <?php if ($cell !== null): ?>
                <th scope="row"><?= e($cell['label']) ?></th>
                <td><?= e($cell['value']) ?></td>
                <?php else: ?>
                <th class="is-empty" scope="row"></th>
                <td class="is-empty"></td>
                <?php endif; ?>
              <?php endfor; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
      <?php if ($descProse !== ''): ?>
      <div class="shop-detail-desc__body<?= $descSpecRows !== [] ? ' shop-detail-desc__body--after-table' : '' ?>"><?= nl2br(e($descProse)) ?></div>
      <?php elseif ($descSpecRows === [] && $description !== ''): ?>
      <div class="shop-detail-desc__body"><?= nl2br(e($description)) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($hasFooter): ?>
  <div class="shop-detail-page-block shop-detail-page-block--footer">
    <?php if ($footerHtml !== ''): ?>
    <div class="shop-detail-page-html"><?= $footerHtml ?></div>
    <?php endif; ?>
    <?php if ($footerImageUrl !== ''): ?>
    <div class="shop-detail-page-media">
      <img src="<?= e($footerImageUrl) ?>" alt="상품 상세 푸터 이미지">
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($related): ?>
<section class="shop-section">
  <div class="shop-section-head"><h2>같은 카테고리 상품</h2></div>
  <div class="shop-product-grid">
    <?php foreach (array_slice($related, 0, 4) as $product): ?>
    <?php require view_path('shop/partials/product-card.php'); ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
