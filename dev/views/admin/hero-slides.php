<?php
use App\Services\HomeHeroService;
$items = $items ?? [];
?>
<div class="admin-head">
  <div>
    <h1>히어로 이미지 관리</h1>
    <p>사이트 첫 화면 hero 영역에 슬라이딩되는 이미지를 등록·수정합니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary js-hero-add">+ 슬라이드 추가</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>미리보기</th>
        <th>제목</th>
        <th>대체텍스트</th>
        <th>이미지 URL</th>
        <th>링크</th>
        <th>정렬</th>
        <th>상태</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="8" class="empty">등록된 슬라이드가 없습니다.</td></tr>
    <?php else: ?>
    <?php foreach ($items as $row): ?>
      <tr>
        <td>
          <img class="admin-thumb" src="<?= e(HomeHeroService::resolveImageUrl((string) ($row['image_url'] ?? ''))) ?>" alt="">
        </td>
        <td><?= e($row['title'] ?? '') ?></td>
        <td><small><?= e($row['alt_text'] ?? '-') ?></small></td>
        <td class="admin-cell-wrap"><code><?= e($row['image_url'] ?? '') ?></code></td>
        <td class="admin-cell-wrap"><code><?= e($row['link_url'] ?? '-') ?></code></td>
        <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
        <td><?= ($row['is_active'] ?? false) ? '<span class="admin-badge admin-badge--ok">노출</span>' : '<span class="admin-badge admin-badge--err">숨김</span>' ?></td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-hero-edit" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
          <button type="button" class="admin-btn admin-btn--sm js-hero-delete" data-id="<?= (int) $row['id'] ?>">삭제</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<div class="admin-modal" id="heroModal" hidden>
  <div class="admin-modal-backdrop" data-close="heroModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="heroModalTitle">
    <div class="admin-modal-head">
      <h2 id="heroModalTitle">히어로 슬라이드</h2>
      <button type="button" class="admin-modal-close" data-close="heroModal" aria-label="닫기">×</button>
    </div>
    <form id="heroForm" class="admin-modal-body"></form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="heroModal">취소</button>
      <button type="submit" form="heroForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>
<script src="<?= js('hero-admin.js') ?>"></script>
