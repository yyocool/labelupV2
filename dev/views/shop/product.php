<?php
/** @var App\Services\ShopService $shopService */
/** @var array<string, mixed> $product */
/** @var array<int, array<string, mixed>> $related */
/** @var array<string, mixed> $pageLayout */
$pageLayout = $pageLayout ?? [];
$unit = $shopService->unitPrice($product);
$thumb = $shopService->productThumb($product);
$gallery = $shopService->productGallery($product);
$options = $shopService->productOptions($product);
$hasOptions = $options !== [];
$sellableOptions = array_values(array_filter($options, static fn (array $o): bool => !$o['soldout']));
$isInk = $shopService->isInkProduct($product);
$inkAmount = (int) ($product['ink_amount'] ?? 0);
$isSoldout = ($product['status'] ?? '') === 'soldout'
    || (!$isInk && (int) ($product['stock_qty'] ?? 0) <= 0)
    || ($hasOptions && $sellableOptions === []);
$onSale = !empty($product['sale_price']) && (int) $product['sale_price'] < (int) $product['price'];
$headerHtml = trim((string) ($pageLayout['header_html'] ?? ''));
$footerHtml = trim((string) ($pageLayout['footer_html'] ?? ''));
$headerImageUrl = trim((string) ($pageLayout['header_image_url'] ?? ''));
$footerImageUrl = trim((string) ($pageLayout['footer_image_url'] ?? ''));
$headerBlocks = $pageLayout['header_blocks'] ?? [];
$footerBlocks = $pageLayout['footer_blocks'] ?? [];
if (!is_array($headerBlocks) || $headerBlocks === []) {
    if ($headerHtml !== '' || $headerImageUrl !== '') {
        $headerBlocks = [['html' => $headerHtml, 'image_url' => $headerImageUrl, 'kind' => 'header']];
    }
}
if (!is_array($footerBlocks) || $footerBlocks === []) {
    if ($footerHtml !== '' || $footerImageUrl !== '') {
        $footerBlocks = [['html' => $footerHtml, 'image_url' => $footerImageUrl, 'kind' => 'footer']];
    }
}
if ($isInk) {
    $headerBlocks = [];
    $footerBlocks = [];
}
$hasHeader = $headerBlocks !== [];
$hasFooter = $footerBlocks !== [];
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
$hashtags = is_array($pageLayout['hashtags'] ?? null) ? $pageLayout['hashtags'] : [];
?>
<article class="shop-detail<?= $isInk ? ' shop-detail--ink' : '' ?>">
  <div class="shop-detail-gallery">
    <div class="shop-detail-gallery__frame">
      <?php if ($isInk && trim((string) ($product['thumbnail'] ?? '')) === ''): ?>
      <div class="shop-ink-visual" id="shopDetailMainImage">
        <small>INK</small>
        <strong><?= number_format($inkAmount) ?></strong>
        <span>잉크</span>
      </div>
      <?php else: ?>
      <img id="shopDetailMainImage" src="<?= e($gallery[0]['url']) ?>" alt="<?= e((string) $product['name']) ?>">
      <?php endif; ?>
      <div class="shop-detail-social">
        <button type="button"
                class="shop-social-btn js-wishlist-toggle<?= !empty($wished) ? ' is-on' : '' ?>"
                data-product-id="<?= (int) $product['id'] ?>"
                aria-pressed="<?= !empty($wished) ? 'true' : 'false' ?>"
                title="<?= !empty($wished) ? '찜 해제' : '찜하기' ?>">
          <span class="shop-social-btn__ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 20.3 3.9 12.2a5 5 0 0 1 7.1-7.1l1 1 1-1a5 5 0 0 1 7.1 7.1Z"/>
            </svg>
          </span>
          <span class="shop-social-btn__lab"><?= !empty($wished) ? '찜 해제' : '찜하기' ?></span>
        </button>
        <button type="button"
                class="shop-social-btn js-share-product"
                data-share-title="<?= e((string) $product['name']) ?>"
                title="공유하기">
          <span class="shop-social-btn__ic" aria-hidden="true">
            <!-- 요즘 흔히 쓰는 노드 연결형 공유 아이콘 -->
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="18" cy="5" r="2.6"/>
              <circle cx="6" cy="12" r="2.6"/>
              <circle cx="18" cy="19" r="2.6"/>
              <path d="M8.3 10.8 15.7 6.5M8.3 13.2l7.4 4.3"/>
            </svg>
          </span>
          <span class="shop-social-btn__lab">공유</span>
        </button>
      </div>
      <?php if (count($gallery) > 1): ?>
      <ul class="shop-detail-thumbs" aria-label="상품 이미지 <?= count($gallery) ?>장">
        <?php foreach ($gallery as $i => $image): ?>
        <li>
          <button type="button"
                  class="shop-detail-thumb<?= $i === 0 ? ' is-active' : '' ?>"
                  data-gallery-src="<?= e($image['url']) ?>"
                  aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"
                  aria-label="<?= (int) ($i + 1) ?>번째 이미지 보기">
            <img src="<?= e($image['url']) ?>" alt="" loading="lazy">
          </button>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
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
    <?php if ($hashtags !== []): ?>
    <ul class="shop-detail-hashtags" aria-label="해시태그">
      <?php foreach ($hashtags as $tag): ?>
      <li><?= e((string) $tag) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <p class="shop-detail-sku"><span>SKU</span> <?= e((string) $product['sku']) ?></p>

    <div class="shop-detail-price">
      <?php if ($onSale): ?>
      <del><?= e($shopService->formatPrice((int) $product['price'])) ?></del>
      <?php endif; ?>
      <strong><?= e($shopService->formatPrice($unit)) ?></strong>
      <em class="shop-detail-stock <?= $isSoldout ? 'is-soldout' : '' ?>">
        <?php if ($isSoldout): ?>품절<?php elseif ($isInk): ?>결제 후 바로 지급<?php else: ?>재고 <?= number_format((int) $product['stock_qty']) ?>개<?php endif; ?>
      </em>
    </div>

    <?php if ($isInk): ?>
    <ul class="shop-detail-specs">
      <li>
        <span>지급 잉크</span>
        <strong><?= number_format($inkAmount) ?> 잉크</strong>
      </li>
    </ul>
    <p class="shop-ink-note">구독이 아닌 1회 충전 상품입니다. 결제가 완료되면 구매한 수량만큼 계정에 잉크가 지급됩니다.</p>
    <?php elseif (!empty($product['spec_name']) || !empty($product['material']) || !empty($product['labels_per_sheet'])): ?>
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
      <?php if ($hasOptions): ?>
      <div class="shop-detail-option">
        <fieldset class="shop-option-set">
          <legend class="shop-detail-option__lab">옵션 선택 <em>필수</em></legend>
          <div class="shop-option-grid" id="productOption" data-option-price-target="#productPriceLive">
            <?php foreach ($options as $opt): ?>
            <?php $optId = 'productOption' . (int) $opt['id']; ?>
            <input
              class="shop-option-radio"
              type="radio"
              name="product_option"
              id="<?= e($optId) ?>"
              value="<?= (int) $opt['id'] ?>"
              data-unit="<?= (int) $opt['unit_price'] ?>"
              data-stock="<?= (int) $opt['stock_qty'] ?>"
              <?= $opt['soldout'] ? 'disabled' : '' ?>>
            <label class="shop-option-card" for="<?= e($optId) ?>">
              <span class="shop-option-card__name"><?= e((string) $opt['name']) ?></span>
              <span class="shop-option-card__price"><?= e((string) $opt['price_label']) ?></span>
              <?php if ($opt['soldout']): ?>
              <span class="shop-option-card__flag">품절</span>
              <?php elseif ($opt['delta_label'] !== ''): ?>
              <span class="shop-option-card__delta"><?= e((string) $opt['delta_label']) ?></span>
              <?php endif; ?>
            </label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <p class="shop-detail-option__live">선택 금액 <strong id="productPriceLive"><?= e($shopService->formatPrice($unit)) ?></strong></p>
      </div>
      <?php endif; ?>
      <div class="shop-detail-buy">
        <div class="shop-qty" aria-label="수량">
          <button type="button" class="shop-qty-btn" data-qty-minus aria-label="수량 감소">−</button>
          <input type="number" id="productQty" value="1" min="1" max="<?= $isInk ? 20 : (int) $product['stock_qty'] ?>" aria-label="수량 입력">
          <button type="button" class="shop-qty-btn" data-qty-plus aria-label="수량 증가">+</button>
        </div>
        <button type="button" class="shop-btn shop-btn--primary shop-detail-buy__cart" data-add-cart="<?= (int) $product['id'] ?>" data-qty-input="#productQty"<?= $hasOptions ? ' data-option-select="#productOption"' : '' ?>>장바구니 담기</button>
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
    <?php foreach ($headerBlocks as $block): ?>
      <?php $kind = 'header'; require view_path('shop/partials/page-layout-block.php'); ?>
    <?php endforeach; ?>
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
    <?php foreach ($footerBlocks as $block): ?>
      <?php $kind = 'footer'; require view_path('shop/partials/page-layout-block.php'); ?>
    <?php endforeach; ?>
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
