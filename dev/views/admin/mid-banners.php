<?php
use App\Services\HomeMidBannerService;
$items = $items ?? [];
?>
<div class="admin-head">
  <div>
    <h1>중간배너 관리</h1>
    <p>홈 화면 「용도별 라벨」 영역에 슬라이딩되는 연출 이미지를 등록합니다. 메인/서브 텍스트는 이미지 위에 표시되며 폰트·크기·색상을 지정할 수 있습니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary js-mid-add">+ 배너 추가</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>미리보기</th>
        <th>제목</th>
        <th>메인 텍스트</th>
        <th>서브 텍스트</th>
        <th>이미지</th>
        <th>랜딩 URL</th>
        <th>정렬</th>
        <th>상태</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="9" class="empty">등록된 중간배너가 없습니다.</td></tr>
    <?php else: ?>
    <?php foreach ($items as $row):
      $row['image_src'] = HomeMidBannerService::resolveImageUrl((string) ($row['image_url'] ?? ''));
      ?>
      <tr>
        <td>
          <?php if ($row['image_src'] !== ''): ?>
          <img class="admin-thumb admin-thumb--hero" src="<?= e($row['image_src']) ?>" alt="">
          <?php else: ?>
          <span class="admin-muted">없음</span>
          <?php endif; ?>
        </td>
        <td><?= e($row['title'] ?? '') ?></td>
        <td><small><?= e(trim((string) ($row['main_text'] ?? '')) !== '' ? (string) $row['main_text'] : '-') ?></small></td>
        <td><small><?= e(trim((string) ($row['sub_text'] ?? '')) !== '' ? (string) $row['sub_text'] : '-') ?></small></td>
        <td class="admin-cell-wrap"><code><?= e($row['image_url'] ?? '') ?></code></td>
        <td class="admin-cell-wrap"><code><?= e($row['link_url'] ?? '-') ?></code></td>
        <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
        <td><?= ($row['is_active'] ?? false) ? '<span class="admin-badge admin-badge--ok">노출</span>' : '<span class="admin-badge admin-badge--err">숨김</span>' ?></td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-mid-edit" data-id="<?= (int) $row['id'] ?>" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
          <button type="button" class="admin-btn admin-btn--sm js-mid-delete" data-id="<?= (int) $row['id'] ?>">삭제</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<div class="admin-modal" id="midBannerModal" hidden>
  <div class="admin-modal-backdrop" data-close="midBannerModal"></div>
  <div class="admin-modal-panel admin-modal-panel--xl" role="dialog" aria-modal="true" aria-labelledby="midBannerModalTitle">
    <div class="admin-modal-head">
      <h2 id="midBannerModalTitle">중간배너</h2>
      <button type="button" class="admin-modal-close" data-close="midBannerModal" aria-label="닫기">×</button>
    </div>
    <form id="midBannerForm" class="admin-modal-body" enctype="multipart/form-data"></form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="midBannerModal">취소</button>
      <button type="submit" form="midBannerForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>
<script src="<?= js('mid-banner-admin.js') ?>"></script>
