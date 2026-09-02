<?php
$items = $list['items'] ?? [];
$total = (int) ($list['total'] ?? 0);
$page = (int) ($list['page'] ?? 1);
$pages = (int) ($list['pages'] ?? 1);
$currentUserId = (int) ($user['id'] ?? 0);
?>
<div class="admin-head">
  <div>
    <h1>회원 관리</h1>
    <p>총 <?= number_format($total) ?>명 · 크레딧, CS, 주문 이력을 회원 상세에서 확인할 수 있습니다.</p>
  </div>
</div>

<form class="admin-toolbar" method="get" action="<?= url('admin/users') ?>">
  <div class="admin-field">
    <input class="admin-input" type="search" name="q" value="<?= e($search ?? '') ?>" placeholder="이메일, 이름, 회사명 검색">
  </div>
  <button class="admin-btn admin-btn--primary" type="submit">검색</button>
  <?php if (!empty($search)): ?>
  <a class="admin-btn" href="<?= url('admin/users') ?>">초기화</a>
  <?php endif; ?>
</form>

<div id="adminAlert" class="admin-alert"></div>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>이메일</th>
        <th>이름</th>
        <th>회사</th>
        <th>크레딧</th>
        <th>역할</th>
        <th>상태</th>
        <th>가입일</th>
        <th>최근 로그인</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
      <tr><td colspan="10" class="empty">검색 결과가 없습니다.</td></tr>
      <?php else: ?>
      <?php foreach ($items as $row): ?>
      <?php $isSelf = (int) $row['id'] === $currentUserId; ?>
      <tr data-user-row="<?= (int) $row['id'] ?>">
        <td><a href="<?= url('admin/users/' . (int) $row['id']) ?>"><?= (int) $row['id'] ?></a></td>
        <td><a href="<?= url('admin/users/' . (int) $row['id']) ?>"><?= e($row['email'] ?? '') ?></a></td>
        <td><?= e($row['name'] ?? '-') ?></td>
        <td><?= e($row['company'] ?? '-') ?></td>
        <td><strong><?= number_format((int) ($row['credit_balance'] ?? 0)) ?> C</strong></td>
        <td>
          <select class="admin-select js-role" <?= $isSelf ? 'disabled' : '' ?>>
            <option value="member"<?= ($row['role'] ?? '') === 'member' ? ' selected' : '' ?>>일반회원</option>
            <option value="admin"<?= ($row['role'] ?? '') === 'admin' ? ' selected' : '' ?>>관리자</option>
          </select>
        </td>
        <td>
          <select class="admin-select js-status" <?= $isSelf ? 'disabled' : '' ?>>
            <option value="active"<?= ($row['status'] ?? '') === 'active' ? ' selected' : '' ?>>활성</option>
            <option value="inactive"<?= ($row['status'] ?? '') === 'inactive' ? ' selected' : '' ?>>비활성</option>
            <?php if (($row['status'] ?? '') === 'withdrawn'): ?>
            <option value="withdrawn" selected>탈퇴</option>
            <?php endif; ?>
          </select>
        </td>
        <td><?= e(substr((string) ($row['created_at'] ?? ''), 0, 10)) ?></td>
        <td><?= e(substr((string) ($row['last_login_at'] ?? '-'), 0, 16)) ?></td>
        <td>
          <a class="admin-btn admin-btn--sm" href="<?= url('admin/users/' . (int) $row['id']) ?>">상세</a>
          <?php if (!$isSelf && ($row['status'] ?? '') !== 'withdrawn'): ?>
          <button class="admin-btn admin-btn--sm js-save-user" type="button" data-user-id="<?= (int) $row['id'] ?>">저장</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($pages > 1): ?>
<?php
  $page = $page;
  $pages = $pages;
  $basePath = 'admin/users';
  $queryParams = ['q' => $search ?? ''];
  require view_path('admin/partials/pagination.php');
?>
<?php endif; ?>
