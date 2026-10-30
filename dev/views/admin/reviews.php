<?php
$filters = $filters ?? [];
$list = $list ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'counts' => []];
$counts = $list['counts'] ?? [];
$products = $products ?? [];
$approvalMode = ($approvalMode ?? 'auto') === 'manual' ? 'manual' : 'auto';
$statusLabels = ['pending' => '승인대기', 'approved' => '노출', 'hidden' => '숨김'];
$statusBadge = ['pending' => 'admin-badge--pending', 'approved' => 'admin-badge--ok', 'hidden' => 'admin-badge--err'];
$statusLink = static function (string $status) use ($filters): string {
    $q = array_filter(['status' => $status, 'type' => $filters['type'] ?? '', 'q' => $filters['q'] ?? ''], static fn ($v): bool => $v !== '');
    return url('admin/ops/reviews') . ($q ? '?' . http_build_query($q) : '');
};
$curStatus = (string) ($filters['status'] ?? '');
?>
<div class="admin-head">
  <div>
    <h1>리뷰관리</h1>
    <p>상품 상세페이지의 별점 리뷰(포토·일반)를 관리합니다. 사진이 들어간 리뷰는 자동으로 포토리뷰가 됩니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary js-review-add">+ 리뷰 등록</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<div class="admin-card admin-review-settings">
  <h2>리뷰 노출 설정</h2>
  <form id="reviewSettingsForm" class="admin-review-settings__row">
    <label class="admin-review-radio">
      <input type="radio" name="approval" value="auto" <?= $approvalMode === 'auto' ? 'checked' : '' ?>>
      <span><b>등록 즉시 노출</b><small>회원이 작성하면 바로 상품 페이지에 보입니다.</small></span>
    </label>
    <label class="admin-review-radio">
      <input type="radio" name="approval" value="manual" <?= $approvalMode === 'manual' ? 'checked' : '' ?>>
      <span><b>관리자 승인 후 노출</b><small>승인대기로 등록되고, 관리자가 승인하면 보입니다.</small></span>
    </label>
    <button type="submit" class="admin-btn admin-btn--primary">설정 저장</button>
  </form>
</div>

<div class="admin-review-status">
  <a href="<?= e($statusLink('')) ?>" class="<?= $curStatus === '' ? 'is-active' : '' ?>">전체 <b><?= number_format(array_sum($counts)) ?></b></a>
  <?php foreach ($statusLabels as $key => $label): ?>
  <a href="<?= e($statusLink($key)) ?>" class="<?= $curStatus === $key ? 'is-active' : '' ?><?= $key === 'pending' && ($counts[$key] ?? 0) > 0 ? ' has-pending' : '' ?>"><?= e($label) ?> <b><?= number_format((int) ($counts[$key] ?? 0)) ?></b></a>
  <?php endforeach; ?>
</div>

<form class="admin-filter-bar" method="get" action="<?= url('admin/ops/reviews') ?>">
  <select class="admin-select" name="status">
    <option value="">전체 상태</option>
    <?php foreach ($statusLabels as $key => $label): ?>
    <option value="<?= $key ?>" <?= $curStatus === $key ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="admin-select" name="type">
    <option value="">전체 유형</option>
    <option value="photo" <?= ($filters['type'] ?? '') === 'photo' ? 'selected' : '' ?>>포토리뷰</option>
    <option value="text" <?= ($filters['type'] ?? '') === 'text' ? 'selected' : '' ?>>일반리뷰</option>
    <option value="admin" <?= ($filters['type'] ?? '') === 'admin' ? 'selected' : '' ?>>관리자 등록</option>
  </select>
  <input class="admin-input admin-input--search" type="search" name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="상품명, 작성자, 이메일, 내용 검색">
  <button type="submit" class="admin-btn">검색</button>
</form>

<div class="admin-review-bulk">
  <span>선택한 리뷰</span>
  <button type="button" class="admin-btn admin-btn--sm js-review-bulk" data-status="approved">승인(노출)</button>
  <button type="button" class="admin-btn admin-btn--sm js-review-bulk" data-status="hidden">숨김</button>
  <button type="button" class="admin-btn admin-btn--sm js-review-bulk-delete">삭제</button>
  <em>총 <?= number_format((int) $list['total']) ?>건</em>
</div>

<div class="admin-table-wrap">
  <table class="admin-table admin-review-table">
    <thead>
      <tr>
        <th style="width:34px"><input type="checkbox" class="js-review-check-all" aria-label="전체 선택"></th>
        <th>상품</th>
        <th>별점</th>
        <th>유형</th>
        <th>내용</th>
        <th>작성자</th>
        <th>상태</th>
        <th>작성일</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($list['items'])): ?>
      <tr><td colspan="9" class="empty">등록된 리뷰가 없습니다.</td></tr>
    <?php else: ?>
    <?php foreach ($list['items'] as $row): ?>
      <?php
        $status = (string) $row['status'];
        $excerpt = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $row['content']), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        $editRow = [
            'id' => (int) $row['id'],
            'product_id' => (int) $row['product_id'],
            'author_name' => (string) $row['author_name'],
            'rating' => (int) $row['rating'],
            'content' => (string) $row['content'],
            'status' => $status,
            'created_at' => (string) $row['created_at'],
        ];
      ?>
      <tr>
        <td><input type="checkbox" class="js-review-check" value="<?= (int) $row['id'] ?>" aria-label="선택"></td>
        <td>
          <a href="<?= url('shop/products/' . (int) $row['product_id']) ?>#reviews" target="_blank" class="admin-review-product"><?= e((string) ($row['product_name'] ?? '삭제된 상품')) ?></a>
        </td>
        <td><span class="admin-review-stars"><?= str_repeat('★', (int) $row['rating']) ?><i><?= str_repeat('★', 5 - (int) $row['rating']) ?></i></span></td>
        <td><?= $row['review_type'] === 'photo' ? '<span class="admin-badge admin-badge--paid">포토 ' . (int) $row['photo_count'] . '</span>' : '<span class="admin-badge">일반</span>' ?></td>
        <td class="admin-review-excerpt">
          <?php if (!empty($row['thumb_url'])): ?><img src="<?= e((string) $row['thumb_url']) ?>" alt="" loading="lazy"><?php endif; ?>
          <span><?= e(mb_strimwidth($excerpt, 0, 90, '…')) ?></span>
        </td>
        <td>
          <b><?= e((string) $row['author_name']) ?></b>
          <?php if (!empty($row['is_admin_created'])): ?>
          <br><small class="admin-muted">관리자 등록</small>
          <?php elseif (!empty($row['user_email'])): ?>
          <br><small class="admin-muted"><?= e((string) $row['user_email']) ?></small>
          <?php endif; ?>
          <?php if (!empty($row['is_purchased'])): ?><br><small class="admin-review-buyer">구매인증</small><?php endif; ?>
        </td>
        <td><span class="admin-badge <?= $statusBadge[$status] ?? '' ?>"><?= e($statusLabels[$status] ?? $status) ?></span></td>
        <td><small><?= e(substr((string) $row['created_at'], 0, 16)) ?></small></td>
        <td class="admin-review-actions">
          <?php if ($status !== 'approved'): ?>
          <button type="button" class="admin-btn admin-btn--sm js-review-status" data-id="<?= (int) $row['id'] ?>" data-status="approved">승인</button>
          <?php else: ?>
          <button type="button" class="admin-btn admin-btn--sm js-review-status" data-id="<?= (int) $row['id'] ?>" data-status="hidden">숨김</button>
          <?php endif; ?>
          <button type="button" class="admin-btn admin-btn--sm js-review-edit" data-row='<?= e(json_encode($editRow, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
          <button type="button" class="admin-btn admin-btn--sm js-review-delete" data-id="<?= (int) $row['id'] ?>">삭제</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php
$page = (int) $list['page'];
$pages = (int) $list['pages'];
$basePath = 'admin/ops/reviews';
$queryParams = array_filter(['status' => $curStatus, 'type' => $filters['type'] ?? '', 'q' => $filters['q'] ?? ''], static fn ($v): bool => $v !== '');
require view_path('admin/partials/pagination.php');
?>

<div class="admin-modal" id="reviewModal" hidden>
  <div class="admin-modal-backdrop" data-close="reviewModal"></div>
  <div class="admin-modal-panel admin-modal-panel--wide" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2 id="reviewModalTitle">리뷰 등록</h2>
      <button type="button" class="admin-modal-close" data-close="reviewModal" aria-label="닫기">×</button>
    </div>
    <form id="reviewForm" class="admin-modal-body">
      <input type="hidden" name="id" value="0">
      <div class="admin-form-grid">
        <label class="admin-field admin-field--full"><span>상품</span>
          <input class="admin-input js-review-product-search" type="search" placeholder="상품명·SKU로 찾기" autocomplete="off">
          <select class="admin-input" name="product_id" required size="1">
            <option value="">상품을 선택하세요</option>
            <?php foreach ($products as $p): ?>
            <option value="<?= (int) $p['id'] ?>"><?= e((string) $p['name']) ?> (<?= e((string) $p['sku']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="admin-field"><span>작성자명</span>
          <input class="admin-input" name="author_name" maxlength="50" required placeholder="예: 라벨업 고객">
          <small>관리자가 등록한 리뷰는 이름을 가리지 않고 그대로 보여 줍니다.</small>
        </label>
        <label class="admin-field"><span>별점</span>
          <select class="admin-input" name="rating">
            <?php for ($s = 5; $s >= 1; $s--): ?>
            <option value="<?= $s ?>"><?= str_repeat('★', $s) . str_repeat('☆', 5 - $s) ?> (<?= $s ?>점)</option>
            <?php endfor; ?>
          </select>
        </label>
        <label class="admin-field"><span>작성일</span>
          <input class="admin-input" type="datetime-local" name="created_at">
        </label>
        <label class="admin-field"><span>상태</span>
          <select class="admin-input" name="status">
            <option value="approved">노출</option>
            <option value="pending">승인대기</option>
            <option value="hidden">숨김</option>
          </select>
        </label>
        <div class="admin-field admin-field--full admin-field--editor">
          <span>내용 <small class="admin-muted">사진을 넣으면 포토리뷰로 등록됩니다 (최대 10장)</small></span>
          <textarea class="admin-input js-review-content" name="content" rows="8"></textarea>
        </div>
      </div>
    </form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="reviewModal">취소</button>
      <button type="submit" form="reviewForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>
<script src="<?= js('review-admin.js') ?>"></script>
