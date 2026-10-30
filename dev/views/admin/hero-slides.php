<?php
$items = $items ?? [];
?>
<div class="admin-head">
  <div>
    <h1>히어로 이미지 관리</h1>
    <p>사이트 첫 화면 hero 영역에 슬라이딩되는 이미지를 등록합니다. 타이틀/서브타이틀은 이미지 위에 표시되며 폰트·PC/모바일 크기·색상·위치를 지정할 수 있습니다. 모바일(화면 폭 560px 이하)에는 모바일 이미지를 따로 등록할 수 있습니다.</p>
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
        <th>PC</th>
        <th>모바일</th>
        <th>제목</th>
        <th>타이틀</th>
        <th>서브타이틀</th>
        <th>링크</th>
        <th>정렬</th>
        <th>상태</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="9" class="empty">등록된 슬라이드가 없습니다.</td></tr>
    <?php else: ?>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><img class="admin-thumb admin-thumb--hero" src="<?= e((string) $row['image_src']) ?>" alt=""></td>
        <td>
          <?php if (($row['mobile_image_src'] ?? '') !== ''): ?>
          <img class="admin-thumb admin-thumb--hero-m" src="<?= e((string) $row['mobile_image_src']) ?>" alt="">
          <?php else: ?>
          <span class="admin-muted">PC 이미지 사용</span>
          <?php endif; ?>
        </td>
        <td><?= e($row['title'] ?? '') ?></td>
        <td><small><?= e(($row['main_text'] ?? '') !== '' ? (string) $row['main_text'] : '-') ?></small></td>
        <td><small><?= e(($row['sub_text'] ?? '') !== '' ? (string) $row['sub_text'] : '-') ?></small></td>
        <td class="admin-cell-wrap"><code><?= e($row['link_url'] ?? '-') ?></code></td>
        <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
        <td><?= ($row['is_active'] ?? false) ? '<span class="admin-badge admin-badge--ok">노출</span>' : '<span class="admin-badge admin-badge--err">숨김</span>' ?></td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-hero-edit" data-id="<?= (int) $row['id'] ?>" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
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
  <div class="admin-modal-panel admin-modal-panel--xl" role="dialog" aria-modal="true" aria-labelledby="heroModalTitle">
    <div class="admin-modal-head">
      <h2 id="heroModalTitle">히어로 슬라이드</h2>
      <button type="button" class="admin-modal-close" data-close="heroModal" aria-label="닫기">×</button>
    </div>
    <form id="heroForm" class="admin-modal-body" enctype="multipart/form-data"></form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="heroModal">취소</button>
      <button type="submit" form="heroForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>
<script src="<?= js('hero-admin.js') ?>"></script>
