<?php
use App\Services\CreditService;
$products = $products ?? [];
$history = $history ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$histItems = $history['items'] ?? [];
$histTotal = (int) ($history['total'] ?? 0);
$histPage = (int) ($history['page'] ?? 1);
$histPages = (int) ($history['pages'] ?? 1);
?>
<div class="admin-head">
  <div>
    <h1>구매크레딧</h1>
    <p>제품 구매 고유번호로 크레딧을 수령하는 제품·코드·등록 이력을 관리합니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn js-credit-add" data-entity="purchase-product">+ 제품 추가</button>
    <button type="button" class="admin-btn admin-btn--primary js-credit-add" data-entity="generate-codes">+ 코드 생성</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<section class="admin-section">
  <h2 class="admin-section-title">크레딧 제품</h2>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>SKU</th>
          <th>제품명</th>
          <th>지급 크레딧</th>
          <th>설명</th>
          <th>상태</th>
          <th>관리</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($products)): ?>
        <tr><td colspan="6" class="empty">등록된 제품이 없습니다.</td></tr>
      <?php else: ?>
      <?php foreach ($products as $row): ?>
        <tr>
          <td><code><?= e($row['sku']) ?></code></td>
          <td><strong><?= e($row['name']) ?></strong></td>
          <td><strong><?= number_format((int) $row['credit_amount']) ?> C</strong></td>
          <td><small><?= e($row['description'] ?? '-') ?></small></td>
          <td><?= ($row['is_active'] ?? false) ? '<span class="admin-badge admin-badge--ok">사용</span>' : '<span class="admin-badge admin-badge--err">중지</span>' ?></td>
          <td>
            <button type="button" class="admin-btn admin-btn--sm js-credit-edit" data-entity="purchase-product" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
            <button type="button" class="admin-btn admin-btn--sm js-credit-delete" data-entity="purchase-product" data-id="<?= (int) $row['id'] ?>">삭제</button>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="admin-section">
  <div class="admin-section-head">
    <h2 class="admin-section-title">등록 이력</h2>
    <form class="admin-toolbar admin-toolbar--inline" method="get" action="<?= url('admin/ops/purchase-credits') ?>">
      <input class="admin-input" type="search" name="q" value="<?= e($search ?? '') ?>" placeholder="코드, 제품, SKU, 회원 검색">
      <button class="admin-btn admin-btn--primary" type="submit">검색</button>
      <?php if (!empty($search)): ?>
      <a class="admin-btn" href="<?= url('admin/ops/purchase-credits') ?>">초기화</a>
      <?php endif; ?>
    </form>
  </div>
  <p class="admin-muted">총 <?= number_format($histTotal) ?>건</p>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>고유번호</th>
          <th>제품명</th>
          <th>SKU</th>
          <th>지급 크레딧</th>
          <th>등록 회원</th>
          <th>등록일시</th>
          <th>배치</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($histItems)): ?>
        <tr><td colspan="7" class="empty">등록 이력이 없습니다.</td></tr>
      <?php else: ?>
      <?php foreach ($histItems as $row): ?>
        <tr>
          <td><code><?= e($row['code']) ?></code></td>
          <td><?= e($row['product_name'] ?? '') ?></td>
          <td><code><?= e($row['sku'] ?? '') ?></code></td>
          <td><strong><?= number_format((int) ($row['credit_amount'] ?? 0)) ?> C</strong></td>
          <td>
            <?php if (!empty($row['user_email'])): ?>
            <a href="<?= url('admin/users/' . (int) $row['redeemed_by_user_id']) ?>"><?= e($row['user_name'] ?? $row['user_email']) ?></a>
            <br><small class="admin-muted"><?= e($row['user_email']) ?></small>
            <?php else: ?>
            <span class="admin-muted">-</span>
            <?php endif; ?>
          </td>
          <td><?= e(substr((string) ($row['redeemed_at'] ?? ''), 0, 16)) ?></td>
          <td><small><?= e($row['batch_no'] ?? '-') ?></small></td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($histPages > 1): ?>
  <?php
    $page = $histPage;
    $pages = $histPages;
    $basePath = 'admin/ops/purchase-credits';
    $queryParams = ['q' => $search ?? ''];
    require view_path('admin/partials/pagination.php');
  ?>
  <?php endif; ?>
</section>

<div class="admin-modal" id="creditModal" hidden>
  <div class="admin-modal-backdrop" data-close="creditModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="creditModalTitle">
    <div class="admin-modal-head">
      <h2 id="creditModalTitle">구매크레딧</h2>
      <button type="button" class="admin-modal-close" data-close="creditModal" aria-label="닫기">×</button>
    </div>
    <form id="creditForm" class="admin-modal-body"></form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="creditModal">취소</button>
      <button type="submit" form="creditForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>
<script>window.CREDIT_PRODUCTS = <?= json_encode(array_map(static fn ($p) => ['id' => (int) $p['id'], 'name' => $p['name']], $products), JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= js('credit-admin.js') ?>"></script>
