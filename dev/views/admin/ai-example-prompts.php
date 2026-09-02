<?php
$items = $items ?? [];
?>
<div class="admin-head">
  <div>
    <h1>예시프롬프트 관리</h1>
    <p>홈과 편집기 라비AI 입력창 아래에 보이는 예시 칩입니다. 노출 위치와 순서를 지정할 수 있습니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary js-ai-prompt-add">+ 예시 추가</button>
  </div>
</div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>버튼</th>
        <th>프롬프트</th>
        <th>노출</th>
        <th>정렬</th>
        <th>상태</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="6" class="empty">등록된 예시 프롬프트가 없습니다.</td></tr>
    <?php else: ?>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><b><?= e($row['label'] ?? '') ?></b></td>
        <td><?= e($row['prompt_text'] ?? '') ?></td>
        <td><?= e($row['surface_label'] ?? '') ?></td>
        <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
        <td><?= !empty($row['is_active']) ? '<span class="admin-badge admin-badge--ok">노출</span>' : '<span class="admin-badge admin-badge--err">숨김</span>' ?></td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-ai-prompt-edit" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
          <button type="button" class="admin-btn admin-btn--sm js-ai-prompt-delete" data-id="<?= (int) $row['id'] ?>">삭제</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="admin-modal" id="aiPromptModal" hidden>
  <div class="admin-modal-backdrop" data-close="aiPromptModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2 id="aiPromptModalTitle">예시 프롬프트</h2>
      <button type="button" class="admin-modal-close" data-close="aiPromptModal" aria-label="닫기">×</button>
    </div>
    <form id="aiPromptForm" class="admin-modal-body"></form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="aiPromptModal">취소</button>
      <button type="submit" form="aiPromptForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>
<script src="<?= js('ai-prompt-admin.js') ?>"></script>
