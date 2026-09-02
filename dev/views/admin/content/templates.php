<?php
/** @var array<string, string> $categories */
/** @var array{items:array,total:int,page:int,pages:int} $list */
/** @var array{q:string,category:string} $filters */
$categories = $categories ?? [];
$list = $list ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$filters = $filters ?? ['q' => '', 'category' => ''];
?>
<div class="admin-head">
  <div>
    <h1>템플릿관리</h1>
    <p>편집기에서 바로 불러 수정할 수 있는 라벨 디자인 테마를 관리합니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn js-tpl-seed" title="기본 테마 50종 반영">시드 반영</button>
    <button type="button" class="admin-btn admin-btn--primary js-tpl-add">+ 템플릿 추가</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<form class="admin-filter-bar" method="get" action="<?= url('admin/content/templates') ?>">
  <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="이름·태그·설명 검색" class="admin-input">
  <select name="category" class="admin-input">
    <option value="">전체 카테고리</option>
    <?php foreach ($categories as $key => $label): ?>
    <option value="<?= e($key) ?>"<?= (($filters['category'] ?? '') === $key) ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="admin-btn">검색</button>
</form>

<p class="admin-meta-line">총 <b><?= number_format((int) ($list['total'] ?? 0)) ?></b>개 · <?= (int) ($list['page'] ?? 1) ?> / <?= (int) ($list['pages'] ?? 1) ?> 페이지</p>

<div class="clip-grid tpl-grid">
<?php if (empty($list['items'])): ?>
  <div class="clip-empty">등록된 템플릿이 없습니다. 시드 반영 또는 추가 버튼으로 등록해 주세요.</div>
<?php else: ?>
  <?php foreach ($list['items'] as $row): ?>
  <?php
    $card = $row;
    unset($card['document'], $card['document_json']);
  ?>
  <article class="clip-card tpl-card" data-row='<?= e(json_encode($card, JSON_UNESCAPED_UNICODE)) ?>'>
    <button type="button" class="clip-card-thumb tpl-card-thumb js-tpl-preview" title="미리보기"
            style="--tpl-tone: <?= e($row['tone'] ?? '#7B2840') ?>">
      <?php if (!empty($row['previewSvg'])): ?>
      <span class="tpl-card-preview"><?= $row['previewSvg'] ?></span>
      <?php else: ?>
      <span class="tpl-card-swatch"><?= e(mb_substr((string) ($row['name'] ?? ''), 0, 6)) ?></span>
      <?php endif; ?>
    </button>
    <div class="clip-card-body">
      <span class="clip-card-cat"><?= e($row['categoryName'] ?? $row['category'] ?? '') ?></span>
      <strong><?= e($row['name'] ?? '') ?></strong>
      <div class="clip-card-tags">
        <span><?= e(($row['paper_no'] ?? '') . ' · ' . rtrim(rtrim(number_format((float) ($row['paper_w_mm'] ?? 0), 1), '0'), '.') . '×' . rtrim(rtrim(number_format((float) ($row['paper_h_mm'] ?? 0), 1), '0'), '.') . 'mm') ?></span>
      </div>
      <div class="clip-card-actions">
        <a class="admin-btn admin-btn--sm" href="<?= e(url('editor/?template=' . (int) ($row['id'] ?? 0))) ?>" target="_blank" rel="noopener">편집</a>
        <button type="button" class="admin-btn admin-btn--sm js-tpl-edit">수정</button>
        <button type="button" class="admin-btn admin-btn--sm js-tpl-delete" data-id="<?= (int) $row['id'] ?>">삭제</button>
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
  $basePath = 'admin/content/templates';
  $queryParams = [
    'q' => $filters['q'] ?? '',
    'category' => $filters['category'] ?? '',
  ];
  require view_path('admin/partials/pagination.php');
?>
<?php endif; ?>

<div class="admin-modal" id="tplPreviewModal" hidden>
  <div class="admin-modal-backdrop" data-close="tplPreviewModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2 id="tplPreviewTitle">템플릿 미리보기</h2>
      <button type="button" class="admin-modal-close" data-close="tplPreviewModal" aria-label="닫기">×</button>
    </div>
    <div class="admin-modal-body clip-preview-body">
      <div class="tpl-preview-svg" id="tplPreviewSvg"></div>
      <p id="tplPreviewMeta"></p>
    </div>
  </div>
</div>

<div class="admin-modal" id="tplModal" hidden>
  <div class="admin-modal-backdrop" data-close="tplModal"></div>
  <div class="admin-modal-panel admin-modal-panel--wide" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2 id="tplModalTitle">템플릿</h2>
      <button type="button" class="admin-modal-close" data-close="tplModal" aria-label="닫기">×</button>
    </div>
    <form id="tplForm" class="admin-modal-body"></form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="tplModal">취소</button>
      <button type="submit" form="tplForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>

<script>
window.LABELUP_TEMPLATE_ADMIN = {
  categories: <?= json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  items: <?= json_encode($list['items'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  urls: {
    save: <?= json_encode(url('api/admin/content/template/save'), JSON_UNESCAPED_SLASHES) ?>,
    delete: <?= json_encode(url('api/admin/content/template/delete'), JSON_UNESCAPED_SLASHES) ?>,
    seed: <?= json_encode(url('api/admin/content/template/seed'), JSON_UNESCAPED_SLASHES) ?>
  }
};
</script>
<script src="<?= js('template-admin.js') ?>"></script>
