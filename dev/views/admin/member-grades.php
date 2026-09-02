<?php
$grades = $grades ?? [];
?>
<div class="admin-head">
  <div>
    <h1>회원등급 설정</h1>
    <p>회원관리에서 지정할 등급을 만들고, 기본 등급과 표시 색상을 관리합니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary" id="memberGradeAdd">+ 등급 추가</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>등급</th>
        <th>코드</th>
        <th>설명</th>
        <th>정렬</th>
        <th>기본</th>
        <th>상태</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($grades)): ?>
      <tr><td colspan="7" class="empty">등록된 회원등급이 없습니다.</td></tr>
    <?php else: ?>
    <?php foreach ($grades as $row): ?>
      <tr>
        <td>
          <span class="admin-grade-chip" style="--grade-color:<?= e((string) ($row['color'] ?? '#7B2D3E')) ?>">
            <?= e((string) $row['name']) ?>
          </span>
        </td>
        <td><code><?= e((string) $row['slug']) ?></code></td>
        <td><?= e((string) ($row['description'] ?? '')) ?></td>
        <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
        <td><?= !empty($row['is_default']) ? '<span class="admin-badge admin-badge--ok">기본</span>' : '-' ?></td>
        <td><?= !empty($row['is_active']) ? '<span class="admin-badge admin-badge--ok">사용</span>' : '<span class="admin-badge admin-badge--err">중지</span>' ?></td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-grade-edit" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
          <?php if (empty($row['is_default'])): ?>
          <button type="button" class="admin-btn admin-btn--sm js-grade-delete" data-id="<?= (int) $row['id'] ?>">삭제</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="admin-modal" id="memberGradeModal" hidden>
  <div class="admin-modal-backdrop" data-close="memberGradeModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="memberGradeTitle">
    <div class="admin-modal-head">
      <h2 id="memberGradeTitle">회원등급</h2>
      <button type="button" class="admin-modal-close" data-close="memberGradeModal" aria-label="닫기">×</button>
    </div>
    <form id="memberGradeForm" class="admin-modal-body">
      <input type="hidden" name="id" value="">
      <label class="admin-field">
        <span>등급명</span>
        <input class="admin-input" type="text" name="name" maxlength="80" required>
      </label>
      <label class="admin-field">
        <span>코드</span>
        <input class="admin-input" type="text" name="slug" maxlength="50" placeholder="비우면 자동 생성">
      </label>
      <label class="admin-field">
        <span>설명</span>
        <input class="admin-input" type="text" name="description" maxlength="255">
      </label>
      <label class="admin-field">
        <span>색상</span>
        <input class="admin-input" type="color" name="color" value="#7B2D3E">
      </label>
      <label class="admin-field">
        <span>정렬</span>
        <input class="admin-input" type="number" name="sort_order" value="0">
      </label>
      <label class="admin-field--check">
        <input type="checkbox" name="is_default" value="1"> 기본 등급 (신규 가입 시 적용)
      </label>
      <label class="admin-field--check">
        <input type="checkbox" name="is_active" value="1" checked> 사용
      </label>
    </form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="memberGradeModal">취소</button>
      <button type="submit" form="memberGradeForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>
