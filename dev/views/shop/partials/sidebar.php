<?php
/** @var string $shopSubNav */
/** @var array<int, array<string, mixed>> $shopCategories */
/** @var array<string, mixed>|null $category */
$shopSubNav = $shopSubNav ?? 'home';
$shopCategories = $shopCategories ?? [];
$category = is_array($category ?? null) ? $category : null;
$isActive = static fn (string ...$keys): string => in_array($shopSubNav, $keys, true) ? ' is-active' : '';
?>
<aside class="sidebar sidebar--shop">
  <div class="brand">
    <a href="<?= url('/') ?>" aria-label="labelup 홈"><img class="brand-img" src="<?= asset('logo.png') ?>" alt="labelup"></a>
    <small>라벨업 쇼핑몰</small>
  </div>
  <div class="create create--shop create-split">
    <a class="create-split__search" href="<?= url('shop/products') ?>"><span aria-hidden="true">⌕</span>상품검색</a>
    <a class="create-split__edit" href="<?= url('editor/') ?>"><svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>라벨편집</a>
  </div>

  <div class="group group--shop-primary">
    <div class="group-title">쇼핑몰</div>
    <nav class="menu">
      <a class="<?= $isActive('home') ?>" href="<?= url('shop') ?>"><span class="ico">🏠</span>쇼핑몰 홈</a>
      <a class="<?= $isActive('products', 'product') ?>" href="<?= url('shop/products') ?>"><span class="ico">▦</span>전체 상품</a>
      <a class="<?= $isActive('cart') ?>" href="<?= url('shop/cart') ?>"><span class="ico">🛒</span>장바구니</a>
    </nav>
  </div>

  <?php if ($shopCategories): ?>
  <div class="group">
    <div class="group-title">카테고리</div>
    <nav class="menu menu--shop-cats">
      <?php foreach (\App\Services\ShopService::groupCategoryTree($shopCategories) as $group): ?>
      <?php
        $parent = $group['parent'];
        $children = $group['children'];
        $currentSlug = (string) (($category['slug'] ?? '') ?: '');
        $currentParentId = (int) ($category['parent_id'] ?? 0);
        $parentId = (int) ($parent['id'] ?? 0);
        $isParentActive = $shopSubNav === 'cat-' . $parent['slug']
            || $currentSlug === (string) $parent['slug']
            || $currentParentId === $parentId;
      ?>
      <?php $hasChildren = $children !== []; ?>
      <a class="shop-cat-parent<?= $isParentActive ? ' is-active' : '' ?><?= $hasChildren ? ' has-children' . ($isParentActive ? ' is-open' : '') : '' ?>" href="<?= url('shop/products') ?>?category=<?= e($parent['slug']) ?>"<?= $hasChildren ? ' data-shop-cat-toggle role="button" aria-expanded="' . ($isParentActive ? 'true' : 'false') . '"' : '' ?>>
        <?php if (!empty($parent['image_path'])): ?>
        <span class="ico ico--img"><img src="<?= e(\App\Services\ShopProductImageService::resolveUrl((string) $parent['image_path'])) ?>" alt=""></span>
        <?php else: ?>
        <span class="ico">▧</span>
        <?php endif; ?>
        <?= e(shop_category_display_name($parent['name'] ?? '', $parent['slug'] ?? '')) ?>
        <?php if ($hasChildren): ?><span class="shop-cat-caret" aria-hidden="true"></span><?php endif; ?>
      </a>
      <?php if ($hasChildren): ?>
      <div class="shop-cat-children<?= $isParentActive ? ' is-open' : '' ?>"<?= $isParentActive ? '' : ' hidden' ?>>
        <a class="shop-cat-child<?= $currentSlug === (string) $parent['slug'] ? ' is-active' : '' ?>" href="<?= url('shop/products') ?>?category=<?= e($parent['slug']) ?>">전체</a>
        <?php foreach ($children as $child): ?>
        <a class="shop-cat-child<?= $currentSlug === (string) $child['slug'] ? ' is-active' : '' ?>" href="<?= url('shop/products') ?>?category=<?= e($child['slug']) ?>">
          <?= e(shop_category_display_name($child['name'] ?? '', $child['slug'] ?? '')) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php endforeach; ?>
    </nav>
  </div>
  <?php endif; ?>

  <div class="group group--shop-secondary">
    <div class="group-title">디자인 도구</div>
    <nav class="menu menu--compact">
      <a href="<?= url('editor/') ?>"><span class="ico">✎</span>라벨 디자인</a>
    </nav>
  </div>

  <div class="group">
    <div class="group-title">바로가기</div>
    <nav class="menu menu--compact">
      <a href="<?= url('faq') ?>"><span class="ico">?</span>자주 묻는 질문</a>
      <a href="<?= url('/') ?>"><span class="ico">←</span>메인 홈</a>
      <a href="<?= url('account') ?>"><span class="ico">◎</span>마이페이지</a>
    </nav>
  </div>

</aside>
