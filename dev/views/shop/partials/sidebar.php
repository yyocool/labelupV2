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
    <strong class="brand-name">labelup</strong>
    <small>라벨업 쇼핑몰</small>
  </div>
  <a class="create create--shop" href="<?= url('shop/products') ?>">⌕ &nbsp;상품 검색하기</a>

  <div class="group group--shop-primary">
    <div class="group-title">쇼핑몰</div>
    <nav class="menu">
      <a class="<?= $isActive('home') ?>" href="<?= url('shop') ?>"><span class="ico">🏠</span>쇼핑몰 홈</a>
      <a class="<?= $isActive('products', 'product') ?>" href="<?= url('shop/products') ?>"><span class="ico">▦</span>전체 상품</a>
      <a class="<?= $isActive('cart') ?>" href="<?= url('shop/cart') ?>"><span class="ico">🛒</span>장바구니</a>
      <a href="<?= url('shop/products') ?>?shape=rect"><span class="ico">▣</span>규격으로 찾기</a>
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
      <a class="shop-cat-parent<?= $isParentActive ? ' is-active' : '' ?>" href="<?= url('shop/products') ?>?category=<?= e($parent['slug']) ?>">
        <?php if (!empty($parent['image_path'])): ?>
        <span class="ico ico--img"><img src="<?= e(\App\Services\ShopProductImageService::resolveUrl((string) $parent['image_path'])) ?>" alt=""></span>
        <?php else: ?>
        <span class="ico">▧</span>
        <?php endif; ?>
        <?= e($parent['name']) ?>
      </a>
      <?php if ($children !== []): ?>
      <div class="shop-cat-children<?= $isParentActive ? ' is-open' : '' ?>">
        <?php foreach ($children as $child): ?>
        <a class="shop-cat-child<?= $currentSlug === (string) $child['slug'] ? ' is-active' : '' ?>" href="<?= url('shop/products') ?>?category=<?= e($child['slug']) ?>">
          <?= e($child['name']) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php endforeach; ?>
    </nav>
  </div>
  <?php endif; ?>

  <div class="group">
    <div class="group-title">쇼핑 가이드</div>
    <nav class="menu">
      <a href="<?= url('shop/products') ?>"><span class="ico">📐</span>규격 가이드</a>
      <a href="<?= url('shop/products') ?>?category=label-paper"><span class="ico">📦</span>용지 샘플 안내</a>
      <a href="#"><span class="ico">🖨</span>인쇄 가이드</a>
      <a href="<?= url('faq') ?>"><span class="ico">?</span>자주 묻는 질문</a>
    </nav>
  </div>

  <div class="group group--shop-secondary">
    <div class="group-title">디자인 도구</div>
    <nav class="menu menu--compact">
      <a href="<?= url('editor/') ?>"><span class="ico">✎</span>라벨 디자인</a>
      <a href="#"><span class="ico">▦</span>템플릿</a>
      <a href="<?= url('shop/products') ?>?q="><span class="ico">⌕</span>규격 검색</a>
      <a href="#"><span class="ico">⌘</span>바코드 / QR</a>
    </nav>
  </div>

  <div class="group">
    <div class="group-title">바로가기</div>
    <nav class="menu menu--compact">
      <a href="<?= url('/') ?>"><span class="ico">←</span>메인 홈</a>
      <a href="<?= url('account') ?>"><span class="ico">◎</span>마이페이지</a>
    </nav>
  </div>

  <div class="sidebar-bottom">
    <div class="premium premium--shop">
      <b>✎ 라벨업 에디터</b>
      <p>디자인하고 바로<br>주문까지 한 번에!</p>
      <a href="<?= url('editor/') ?>">디자인 시작 →</a>
    </div>
  </div>
</aside>
