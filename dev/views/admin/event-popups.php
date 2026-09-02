<?php
use App\Services\EventPopupService;
$items = $items ?? [];
?>
<div class="admin-head">
  <div>
    <h1>이벤트 팝업관리</h1>
    <p>사이트 접속 시 노출되는 이벤트·공지 팝업을 등록·수정합니다. 기간·다시보지않기 일수를 설정할 수 있습니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary js-popup-add">+ 팝업 추가</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>미리보기</th>
        <th>제목</th>
        <th>기간</th>
        <th>숨김일수</th>
        <th>정렬</th>
        <th>상태</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="7" class="empty">등록된 이벤트 팝업이 없습니다.</td></tr>
    <?php else: ?>
    <?php foreach ($items as $row): ?>
      <?php
        $period = '상시';
        $start = (string) ($row['start_at'] ?? '');
        $end = (string) ($row['end_at'] ?? '');
        if ($start !== '' || $end !== '') {
          $period = ($start !== '' ? substr($start, 0, 16) : '시작없음') . ' ~ ' . ($end !== '' ? substr($end, 0, 16) : '종료없음');
        }
      ?>
      <tr>
        <td>
          <?php if (!empty($row['image_src'])): ?>
          <img class="admin-thumb" src="<?= e($row['image_src']) ?>" alt="">
          <?php else: ?>—<?php endif; ?>
        </td>
        <td>
          <b><?= e($row['title'] ?? '') ?></b>
          <?php if (!empty($row['link_url'])): ?>
          <div><small class="admin-muted"><?= e($row['link_url']) ?></small></div>
          <?php endif; ?>
        </td>
        <td><small><?= e($period) ?></small></td>
        <td><?= (int) ($row['hide_days'] ?? 1) ?>일</td>
        <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
        <td><?= !empty($row['is_active']) ? '<span class="admin-badge admin-badge--ok">노출</span>' : '<span class="admin-badge admin-badge--err">숨김</span>' ?></td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-popup-edit" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
          <button type="button" class="admin-btn admin-btn--sm js-popup-delete" data-id="<?= (int) $row['id'] ?>">삭제</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="admin-modal" id="popupModal" hidden>
  <div class="admin-modal-backdrop" data-close="popupModal"></div>
  <div class="admin-modal-panel admin-modal-panel--wide" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2 id="popupModalTitle">이벤트 팝업</h2>
      <button type="button" class="admin-modal-close" data-close="popupModal" aria-label="닫기">×</button>
    </div>
    <form id="popupForm" class="admin-modal-body"></form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="popupModal">취소</button>
      <button type="submit" form="popupForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>
<script src="<?= js('event-popup-admin.js') ?>"></script>
