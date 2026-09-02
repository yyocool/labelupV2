<?php
$admins = $admins ?? [];
$actorIsSuper = !empty($actorIsSuper);
$actorId = (int) ($actorId ?? 0);
$permissionMenus = $permissionMenus ?? admin_menu_catalog();
$groups = [];
foreach ($permissionMenus as $item) {
    $groups[$item['group']][] = $item;
}
?>
<div class="admin-head">
  <div>
    <h1>관리자</h1>
    <p>신규 관리자 계정을 등록하고, 메뉴별 접근 권한을 지정합니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary" id="adminStaffCreate">관리자 등록</button>
  </div>
</div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>이름</th>
        <th>이메일</th>
        <th>구분</th>
        <th>상태</th>
        <th>권한</th>
        <th>최근 로그인</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($admins)): ?>
      <tr><td colspan="7" class="empty">등록된 관리자가 없습니다.</td></tr>
      <?php else: ?>
      <?php foreach ($admins as $row): ?>
      <?php
        $isSuper = !empty($row['is_super_admin']);
        $keys = $row['menu_keys'] ?? [];
        $canEdit = $actorIsSuper || (empty($row['is_super_admin']) && (int) $row['id'] !== $actorId);
        $canRevoke = $canEdit && (int) $row['id'] !== $actorId;
      ?>
      <tr>
        <td><b><?= e((string) ($row['name'] ?? '')) ?></b></td>
        <td><?= e((string) ($row['email'] ?? '')) ?></td>
        <td><?= $isSuper ? '<span class="admin-badge admin-badge--ok">최고관리자</span>' : '일반관리자' ?></td>
        <td><?= ($row['status'] ?? '') === 'active' ? '활성' : e((string) ($row['status'] ?? '')) ?></td>
        <td><?= $isSuper ? '전체 메뉴' : (count($keys) . '개 메뉴') ?></td>
        <td><small><?= e(substr((string) ($row['last_login_at'] ?? '-'), 0, 16)) ?></small></td>
        <td>
          <?php if ($canEdit): ?>
          <button type="button" class="admin-btn admin-btn--sm js-staff-edit"
            data-admin="<?= e(json_encode([
                'id' => (int) $row['id'],
                'name' => (string) ($row['name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'status' => (string) ($row['status'] ?? 'active'),
                'is_super_admin' => $isSuper ? 1 : 0,
                'menu_keys' => $keys,
            ], JSON_UNESCAPED_UNICODE)) ?>">권한 설정</button>
          <?php endif; ?>
          <?php if ($canRevoke): ?>
          <button type="button" class="admin-btn admin-btn--sm js-staff-revoke" data-id="<?= (int) $row['id'] ?>">권한 해제</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="admin-modal" id="adminStaffModal" hidden>
  <div class="admin-modal-backdrop" data-close="adminStaffModal"></div>
  <div class="admin-modal-panel admin-modal-panel--wide" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2 id="adminStaffTitle">관리자 등록</h2>
      <button type="button" class="admin-modal-close" data-close="adminStaffModal" aria-label="닫기">×</button>
    </div>
    <form class="admin-modal-body" id="adminStaffForm">
      <input type="hidden" name="id" value="">
      <div class="admin-product-form-grid">
        <div class="admin-field">
          <label>이름</label>
          <input class="admin-input" type="text" name="name" required>
        </div>
        <div class="admin-field">
          <label>이메일</label>
          <input class="admin-input" type="email" name="email" required>
        </div>
        <div class="admin-field">
          <label>비밀번호</label>
          <input class="admin-input" type="password" name="password" autocomplete="new-password" placeholder="8자 이상 영문+숫자">
          <small class="admin-muted" id="adminStaffPwHint">신규 등록 시 필수입니다.</small>
        </div>
        <div class="admin-field">
          <label>상태</label>
          <select class="admin-select" name="status">
            <option value="active">활성</option>
            <option value="inactive">비활성</option>
          </select>
        </div>
      </div>
      <?php if ($actorIsSuper): ?>
      <label class="admin-field--check admin-staff-super">
        <input type="checkbox" name="is_super_admin" value="1" id="adminStaffSuper">
        최고관리자 (모든 메뉴 접근, 관리자 계정 관리 가능)
      </label>
      <?php endif; ?>
      <div id="adminStaffPerms">
        <div class="admin-staff-perm-head">
          <strong>메뉴 권한</strong>
          <button type="button" class="admin-top-link" id="adminStaffPermAll">전체 선택</button>
        </div>
        <?php foreach ($groups as $group => $items): ?>
        <div class="admin-staff-perm-group">
          <div class="admin-staff-perm-group-head">
            <b><?= e($group) ?></b>
            <button type="button" class="admin-top-link js-staff-group" data-group="<?= e($group) ?>">그룹 선택</button>
          </div>
          <div class="admin-staff-perm-list">
            <?php foreach ($items as $item): ?>
            <label class="admin-field--check">
              <input type="checkbox" name="menu_keys[]" value="<?= e($item['key']) ?>" data-group="<?= e($group) ?>">
              <?= e($item['label']) ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="admin-modal-foot">
        <button type="button" class="admin-btn" data-close="adminStaffModal">취소</button>
        <button type="submit" class="admin-btn admin-btn--primary">저장</button>
      </div>
    </form>
  </div>
</div>
<script>
window.LABELUP_ADMIN_STAFF = {
  actorIsSuper: <?= $actorIsSuper ? 'true' : 'false' ?>,
};
</script>
