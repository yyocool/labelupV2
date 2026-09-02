<?php
/** @var array<int, array<string, mixed>> $categories */
/** @var array{items:array,total:int,page:int,pages:int} $list */
/** @var array{q:string,category_id:int,tag:string} $filters */
$categories = $categories ?? [];
$list = $list ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$filters = $filters ?? ['q' => '', 'category_id' => 0, 'tag' => ''];
?>
<div class="admin-head">
  <div>
    <h1>클립아트관리</h1>
    <p>라벨용 클립아트를 등록하고 해시태그·카테고리로 검색할 수 있게 관리합니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn js-clip-seed" title="기본 시드 반영">시드 반영</button>
    <button type="button" class="admin-btn admin-btn--primary js-clip-add">+ 클립아트 추가</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<form class="admin-filter-bar" method="get" action="<?= url('admin/content/cliparts') ?>">
  <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="제목·해시태그 검색" class="admin-input">
  <select name="category_id" class="admin-input">
    <option value="">전체 카테고리</option>
    <?php foreach ($categories as $cat): ?>
    <option value="<?= (int) $cat['id'] ?>"<?= ((int) ($filters['category_id'] ?? 0) === (int) $cat['id']) ? ' selected' : '' ?>><?= e($cat['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="tag" value="<?= e($filters['tag'] ?? '') ?>" placeholder="태그 (예: 커피)" class="admin-input">
  <button type="submit" class="admin-btn">검색</button>
</form>

<p class="admin-meta-line">총 <b><?= number_format((int) ($list['total'] ?? 0)) ?></b>개 · <?= (int) ($list['page'] ?? 1) ?> / <?= (int) ($list['pages'] ?? 1) ?> 페이지</p>

<div class="clip-grid">
<?php if (empty($list['items'])): ?>
  <div class="clip-empty">등록된 클립아트가 없습니다. 시드 반영 또는 추가 버튼으로 등록해 주세요.</div>
<?php else: ?>
  <?php foreach ($list['items'] as $row): ?>
  <article class="clip-card" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>
    <button type="button" class="clip-card-thumb js-clip-preview" title="미리보기">
      <img src="<?= e($row['image_url'] ?? '') ?>" alt="">
    </button>
    <div class="clip-card-body">
      <span class="clip-card-cat"><?= e($row['category_name'] ?? '미분류') ?></span>
      <strong><?= e($row['title'] ?? '') ?></strong>
      <div class="clip-card-tags">
        <?php foreach (array_slice($row['hashtag_list'] ?? [], 0, 4) as $tag): ?>
        <span>#<?= e($tag) ?></span>
        <?php endforeach; ?>
      </div>
      <div class="clip-card-actions">
        <button type="button" class="admin-btn admin-btn--sm js-clip-edit">수정</button>
        <button type="button" class="admin-btn admin-btn--sm js-clip-delete" data-id="<?= (int) $row['id'] ?>">삭제</button>
      </div>
    </div>
  </article>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<?php if (($list['pages'] ?? 1) > 1): ?>
<?php
  $page = (int) ($list['page'] ?? 1);
  $pages = (int) ($list['pages'] ?? 1);
  $basePath = 'admin/content/cliparts';
  $queryParams = [
    'q' => $filters['q'] ?? '',
    'category_id' => !empty($filters['category_id']) ? (int) $filters['category_id'] : '',
    'tag' => $filters['tag'] ?? '',
  ];
  require view_path('admin/partials/pagination.php');
?>
<?php endif; ?>

<div class="admin-modal" id="clipModal" hidden>
  <div class="admin-modal-backdrop" data-close="clipModal"></div>
  <div class="admin-modal-panel admin-modal-panel--wide" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2 id="clipModalTitle">클립아트</h2>
      <button type="button" class="admin-modal-close" data-close="clipModal" aria-label="닫기">×</button>
    </div>
    <form id="clipForm" class="admin-modal-body"></form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="clipModal">취소</button>
      <button type="submit" form="clipForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>

<div class="admin-modal" id="clipPreviewModal" hidden>
  <div class="admin-modal-backdrop" data-close="clipPreviewModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2 id="clipPreviewTitle">미리보기</h2>
      <button type="button" class="admin-modal-close" data-close="clipPreviewModal" aria-label="닫기">×</button>
    </div>
    <div class="admin-modal-body clip-preview-body">
      <img id="clipPreviewImg" alt="">
      <p id="clipPreviewMeta"></p>
    </div>
  </div>
</div>

<script>
window.LABELUP_CLIPART_ADMIN = {
  categories: <?= json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  urls: {
    save: <?= json_encode(url('api/admin/content/clipart/save'), JSON_UNESCAPED_SLASHES) ?>,
    delete: <?= json_encode(url('api/admin/content/clipart/delete'), JSON_UNESCAPED_SLASHES) ?>,
    upload: <?= json_encode(url('api/admin/content/clipart/upload'), JSON_UNESCAPED_SLASHES) ?>,
    seed: <?= json_encode(url('api/admin/content/clipart/seed'), JSON_UNESCAPED_SLASHES) ?>
  }
};
</script>
<script src="<?= js('clipart-admin.js') ?>"></script>
