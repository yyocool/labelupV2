<?php
use App\Services\CreditService;
$items = $items ?? [];
?>
<div class="admin-head">
  <div>
    <h1>크레딧보상 관리</h1>
    <p>사이트 내에서 지급되는 모든 크레딧 보상 규칙을 설정합니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary js-credit-add" data-entity="reward">+ 보상 규칙 추가</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>코드</th>
        <th>보상명</th>
        <th>트리거</th>
        <th>크레딧</th>
        <th>일일한도</th>
        <th>총한도</th>
        <th>정렬</th>
        <th>상태</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="9" class="empty">등록된 보상 규칙이 없습니다.</td></tr>
    <?php else: ?>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><code><?= e($row['code']) ?></code></td>
        <td>
          <strong><?= e($row['name']) ?></strong>
          <?php if (!empty($row['description'])): ?>
          <br><small class="admin-muted"><?= e($row['description']) ?></small>
          <?php endif; ?>
        </td>
        <td><?= e(CreditService::triggerLabel((string) $row['trigger_type'])) ?></td>
        <td><strong><?= number_format((int) $row['credit_amount']) ?> C</strong></td>
        <td><?= $row['daily_limit'] !== null ? (int) $row['daily_limit'] . '회' : '-' ?></td>
        <td><?= $row['max_total_per_user'] !== null ? (int) $row['max_total_per_user'] . '회' : '-' ?></td>
        <td><?= (int) $row['sort_order'] ?></td>
        <td><?= ($row['is_active'] ?? false) ? '<span class="admin-badge admin-badge--ok">사용</span>' : '<span class="admin-badge admin-badge--err">중지</span>' ?></td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-credit-edit" data-entity="reward" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
          <button type="button" class="admin-btn admin-btn--sm js-credit-delete" data-entity="reward" data-id="<?= (int) $row['id'] ?>">삭제</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<div class="admin-modal" id="creditModal" hidden>
  <div class="admin-modal-backdrop" data-close="creditModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="creditModalTitle">
    <div class="admin-modal-head">
      <h2 id="creditModalTitle">보상 규칙</h2>
      <button type="button" class="admin-modal-close" data-close="creditModal" aria-label="닫기">×</button>
    </div>
    <form id="creditForm" class="admin-modal-body"></form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="creditModal">취소</button>
      <button type="submit" form="creditForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>
<script src="<?= js('credit-admin.js') ?>"></script>
