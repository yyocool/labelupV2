<?php
$items = $list['items'] ?? [];
$total = (int) ($list['total'] ?? 0);
$page = (int) ($list['page'] ?? 1);
$pages = (int) ($list['pages'] ?? 1);
$currentUserId = (int) ($user['id'] ?? 0);
$grades = $grades ?? [];
?>
<div class="admin-head">
  <div>
    <h1>회원 관리</h1>
    <p>총 <?= number_format($total) ?>명 · 회원에게 크레딧을 지급하고 지급 사유·이력을 확인할 수 있습니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary" id="userCreateOpen">+ 회원 추가</button>
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
        <th>회원등급</th>
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
          <select class="admin-select js-grade">
            <?php foreach ($grades as $grade): ?>
            <option value="<?= (int) $grade['id'] ?>"<?= (int) ($row['grade_id'] ?? 0) === (int) $grade['id'] ? ' selected' : '' ?>><?= e((string) $grade['name']) ?></option>
            <?php endforeach; ?>
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
          <div class="admin-row-actions">
            <button
              class="admin-btn admin-btn--sm js-credit-grant"
              type="button"
              data-user-id="<?= (int) $row['id'] ?>"
              data-user-email="<?= e($row['email'] ?? '') ?>"
              data-user-name="<?= e($row['name'] ?? '') ?>"
              data-balance="<?= (int) ($row['credit_balance'] ?? 0) ?>"
            >크레딧 지급</button>
            <button
              class="admin-btn admin-btn--sm js-credit-grant-history"
              type="button"
              data-user-id="<?= (int) $row['id'] ?>"
              data-user-email="<?= e($row['email'] ?? '') ?>"
              data-user-name="<?= e($row['name'] ?? '') ?>"
            >지급 이력</button>
            <a class="admin-btn admin-btn--sm" href="<?= url('admin/users/' . (int) $row['id']) ?>">상세</a>
            <?php if (($row['status'] ?? '') !== 'withdrawn'): ?>
            <button class="admin-btn admin-btn--sm js-save-user" type="button" data-user-id="<?= (int) $row['id'] ?>">저장</button>
            <?php endif; ?>
          </div>
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

<?php
  $grantItems = $grants['items'] ?? [];
  $grantTotal = (int) ($grants['total'] ?? 0);
?>
<section class="admin-section">
  <div class="admin-section-head">
    <h2 class="admin-section-title">최근 크레딧 지급 이력</h2>
    <p class="admin-muted">총 <?= number_format($grantTotal) ?>건 · 관리자가 임의 지급한 내역입니다.</p>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>일시</th>
          <th>회원</th>
          <th>지급</th>
          <th>지급 후 잔액</th>
          <th>지급 사유</th>
          <th>처리자</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($grantItems)): ?>
        <tr><td colspan="6" class="empty">지급 이력이 없습니다.</td></tr>
        <?php else: ?>
        <?php foreach ($grantItems as $grant): ?>
        <tr>
          <td><?= e(substr((string) ($grant['created_at'] ?? ''), 0, 16)) ?></td>
          <td>
            <a href="<?= url('admin/users/' . (int) ($grant['user_id'] ?? 0)) ?>"><?= e($grant['user_email'] ?? '') ?></a>
            <?php if (!empty($grant['user_name'])): ?>
            <br><small class="admin-muted"><?= e($grant['user_name']) ?></small>
            <?php endif; ?>
          </td>
          <td class="is-plus">+<?= number_format((int) ($grant['amount'] ?? 0)) ?> C</td>
          <td><?= number_format((int) ($grant['balance_after'] ?? 0)) ?> C</td>
          <td><?= e($grant['description'] ?? '') ?></td>
          <td><small><?= e($grant['admin_email'] ?? $grant['admin_name'] ?? '-') ?></small></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<div class="admin-modal" id="userCreateModal" hidden>
  <div class="admin-modal-backdrop" data-close="userCreateModal"></div>
  <div class="admin-modal-panel admin-modal-panel--wide" role="dialog" aria-modal="true" aria-labelledby="userCreateTitle">
    <div class="admin-modal-head">
      <h2 id="userCreateTitle">회원 추가</h2>
      <button type="button" class="admin-modal-close" data-close="userCreateModal" aria-label="닫기">×</button>
    </div>
    <form id="userCreateForm" class="admin-modal-body">
      <div class="admin-form-grid">
        <label class="admin-field">
          <span>이메일 <em>필수</em></span>
          <input class="admin-input" type="email" name="email" maxlength="190" required autocomplete="off" placeholder="member@example.com">
          <small>로그인 아이디로 쓰입니다. 이미 가입된 이메일은 쓸 수 없습니다.</small>
        </label>
        <label class="admin-field">
          <span>이름 <em>필수</em></span>
          <input class="admin-input" type="text" name="name" maxlength="100" required autocomplete="off" placeholder="홍길동">
        </label>
        <div class="admin-field">
          <span>비밀번호 <em>필수</em></span>
          <div class="admin-input-row">
            <input class="admin-input" type="text" name="password" id="userCreatePassword" maxlength="72" required autocomplete="new-password" placeholder="영문+숫자 8자 이상">
            <button type="button" class="admin-btn admin-btn--sm" id="userCreatePasswordGen">자동 생성</button>
          </div>
          <small>회원에게 따로 알려주세요. 나중에 회원이 직접 변경할 수 있습니다.</small>
        </div>
        <label class="admin-field">
          <span>연락처</span>
          <input class="admin-input" type="tel" name="phone" maxlength="30" autocomplete="off" placeholder="010-0000-0000">
        </label>
        <label class="admin-field">
          <span>회사명</span>
          <input class="admin-input" type="text" name="company" maxlength="150" autocomplete="off" placeholder="(주)라벨업">
        </label>
        <label class="admin-field">
          <span>회원등급</span>
          <select class="admin-input" name="grade_id">
            <?php foreach ($grades as $grade): ?>
            <option value="<?= (int) $grade['id'] ?>"<?= !empty($grade['is_default']) ? ' selected' : '' ?>><?= e((string) $grade['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="admin-field">
          <span>상태</span>
          <select class="admin-input" name="status">
            <option value="active" selected>활성</option>
            <option value="inactive">비활성</option>
          </select>
        </label>
        <label class="admin-field">
          <span>가입 크레딧</span>
          <input class="admin-input" type="number" name="credit_amount" min="0" max="10000000" step="1" value="0">
          <small>0이면 지급하지 않습니다. <?= e(\App\Services\CreditService::ratioLabel()) ?></small>
        </label>
        <label class="admin-field admin-field--full">
          <span>크레딧 지급 사유</span>
          <input class="admin-input" type="text" name="credit_reason" maxlength="255" placeholder="예: 관리자 직접 등록 지급">
        </label>
        <label class="admin-field admin-field--full admin-field--inline-check">
          <input type="checkbox" name="send_welcome" value="1">
          <span>가입 환영 알림 보내기</span>
        </label>
      </div>
    </form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="userCreateModal">취소</button>
      <button type="submit" form="userCreateForm" class="admin-btn admin-btn--primary">회원 추가</button>
    </div>
  </div>
</div>

<div class="admin-modal" id="creditGrantModal" hidden>
  <div class="admin-modal-backdrop" data-close="creditGrantModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="creditGrantTitle">
    <div class="admin-modal-head">
      <h2 id="creditGrantTitle">크레딧 지급</h2>
      <button type="button" class="admin-modal-close" data-close="creditGrantModal" aria-label="닫기">×</button>
    </div>
    <form id="creditGrantForm" class="admin-modal-body">
      <input type="hidden" name="user_id" id="creditGrantUserId">
      <p class="admin-grant-target" id="creditGrantTarget">회원을 선택하세요.</p>
      <label class="admin-field">
        <span>지급 크레딧</span>
        <input class="admin-input" type="number" name="amount" min="1" max="10000000" step="1" required placeholder="예: 5000">
        <small class="admin-muted"><?= e(\App\Services\CreditService::ratioLabel()) ?></small>
      </label>
      <label class="admin-field">
        <span>지급 사유</span>
        <input class="admin-input" type="text" name="reason" maxlength="255" required placeholder="예: 이벤트 보상, CS 보상">
      </label>
    </form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="creditGrantModal">취소</button>
      <button type="submit" form="creditGrantForm" class="admin-btn admin-btn--primary">지급하기</button>
    </div>
  </div>
</div>

<div class="admin-modal" id="creditGrantHistoryModal" hidden>
  <div class="admin-modal-backdrop" data-close="creditGrantHistoryModal"></div>
  <div class="admin-modal-panel admin-modal-panel--wide" role="dialog" aria-modal="true" aria-labelledby="creditGrantHistoryTitle">
    <div class="admin-modal-head">
      <h2 id="creditGrantHistoryTitle">지급 이력</h2>
      <button type="button" class="admin-modal-close" data-close="creditGrantHistoryModal" aria-label="닫기">×</button>
    </div>
    <div class="admin-modal-body" id="creditGrantHistoryBody">
      <p class="admin-muted">이력을 불러오는 중…</p>
    </div>
  </div>
</div>
<script src="<?= js('credit-admin.js') ?>"></script>
