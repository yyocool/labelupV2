<?php
/** @var App\Services\AccountService $accountService */
/** @var array{items: array<int, array<string, mixed>>, page: int, pages: int, total: int} $orderList */
$items = $orderList['items'] ?? [];
$page = (int) ($orderList['page'] ?? 1);
$pages = (int) ($orderList['pages'] ?? 1);
$total = (int) ($orderList['total'] ?? 0);
?>
<section class="account-panel card account-orders">
  <div class="account-panel-head">
    <div>
      <p class="account-orders__crumb"><a href="<?= url('account') ?>">마이페이지</a> · 주문 내역</p>
      <h2>주문 내역</h2>
    </div>
    <span class="account-orders__count">총 <?= number_format($total) ?>건</span>
  </div>

  <?php if ($items === []): ?>
  <p class="account-empty">아직 주문한 내역이 없습니다. 라벨 용지를 담아 주문해 보세요.</p>
  <a class="account-btn account-btn--primary" href="<?= url('shop/products') ?>">상품 보러 가기</a>
  <?php else: ?>
  <ul class="account-orders-list">
    <?php foreach ($items as $order): ?>
    <li class="account-orders-card">
      <div class="account-orders-card__head">
        <div>
          <strong><?= e((string) $order['order_no']) ?></strong>
          <span><?= e((string) $order['date']) ?></span>
        </div>
        <span class="account-order-status <?= e((string) $order['status_class']) ?>"><?= e((string) $order['status_label']) ?></span>
      </div>
      <div class="account-orders-card__body">
        <img src="<?= e((string) $order['thumb']) ?>" alt="">
        <div class="account-order-info">
          <strong><?= e((string) $order['name']) ?></strong>
          <span><?= number_format((int) $order['total']) ?>원</span>
        </div>
      </div>
      <div class="account-orders-card__actions">
        <a class="account-btn account-btn--primary" href="<?= e((string) $order['href']) ?>">상세보기</a>
        <?php if (!empty($order['can_track']) && !empty($order['tracking_url'])): ?>
        <a class="account-btn account-btn--outline" href="<?= e((string) $order['tracking_url']) ?>" target="_blank" rel="noopener noreferrer">배송조회</a>
        <?php elseif (($order['status'] ?? '') === 'shipping'): ?>
        <span class="account-orders-card__hint">송장번호 준비 중</span>
        <?php endif; ?>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php if ($pages > 1): ?>
  <nav class="account-orders-pager" aria-label="주문 목록 페이지">
    <?php if ($page > 1): ?>
    <a href="<?= url('account/orders') ?>?page=<?= $page - 1 ?>">이전</a>
    <?php endif; ?>
    <span><?= $page ?> / <?= $pages ?></span>
    <?php if ($page < $pages): ?>
    <a href="<?= url('account/orders') ?>?page=<?= $page + 1 ?>">다음</a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</section>
