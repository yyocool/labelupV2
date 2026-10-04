<?php
$partners = $partners ?? [];
$search = (string) ($search ?? '');
?>
<div class="admin-head">
  <div>
    <h1>협력사 관리</h1>
    <p>회원 계정과 분리된 협력사 계정을 등록하고, 전용 페이지용 아이디와 비밀번호를 발급합니다. 비밀번호는 발급 직후에만 확인할 수 있습니다. 협력사 로그인 주소는 <a href="<?= url('partner/login') ?>" target="_blank" rel="noopener"><?= e(url('partner/login')) ?></a> 입니다.</p>
  </div>
  <div class="admin-head-actions">
    <form method="get" action="<?= url('admin/partners') ?>" class="admin-head-actions">
      <input class="admin-input" type="search" name="q" value="<?= e($search) ?>" placeholder="상호, 아이디, 담당자">
      <button type="submit" class="admin-btn">검색</button>
    </form>
    <button type="button" class="admin-btn admin-btn--primary" id="partnerCreate">협력사 등록</button>
  </div>
</div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>상호</th>
        <th>아이디</th>
        <th>담당자</th>
        <th>연락처</th>
        <th>이메일</th>
        <th>상태</th>
        <th>등록일</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($partners === []): ?>
      <tr><td colspan="8" class="empty"><?= $search !== '' ? '검색 결과가 없습니다.' : '등록된 협력사가 없습니다.' ?></td></tr>
      <?php else: ?>
      <?php foreach ($partners as $row): ?>
      <?php
        $payload = [
            'id' => (int) $row['id'],
            'company_name' => (string) ($row['company_name'] ?? ''),
            'login_id' => (string) ($row['login_id'] ?? ''),
            'contact_name' => (string) ($row['contact_name'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'memo' => (string) ($row['memo'] ?? ''),
            'status' => (string) ($row['status'] ?? 'active'),
        ];
      ?>
      <tr>
        <td><b><?= e($payload['company_name']) ?></b></td>
        <td><?= e($payload['login_id']) ?></td>
        <td><?= e($payload['contact_name'] !== '' ? $payload['contact_name'] : '-') ?></td>
        <td><?= e($payload['phone'] !== '' ? $payload['phone'] : '-') ?></td>
        <td><?= e($payload['email'] !== '' ? $payload['email'] : '-') ?></td>
        <td><?= $payload['status'] === 'active' ? '<span class="admin-badge admin-badge--ok">활성</span>' : '비활성' ?></td>
        <td><small><?= e(substr((string) ($row['created_at'] ?? '-'), 0, 16)) ?></small></td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-partner-edit" data-partner="<?= e(json_encode($payload, JSON_UNESCAPED_UNICODE)) ?>">수정</button>
          <button type="button" class="admin-btn admin-btn--sm js-partner-reset" data-id="<?= (int) $row['id'] ?>" data-name="<?= e($payload['company_name']) ?>">비밀번호 재발급</button>
          <button type="button" class="admin-btn admin-btn--sm js-partner-delete" data-id="<?= (int) $row['id'] ?>" data-name="<?= e($payload['company_name']) ?>">삭제</button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="admin-modal" id="partnerModal" hidden>
  <div class="admin-modal-backdrop" data-close="partnerModal"></div>
  <div class="admin-modal-panel admin-modal-panel--wide" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2 id="partnerModalTitle">협력사 등록</h2>
      <button type="button" class="admin-modal-close" data-close="partnerModal" aria-label="닫기">×</button>
    </div>
    <form class="admin-modal-body" id="partnerForm">
      <input type="hidden" name="id" value="">
      <div class="admin-product-form-grid">
        <div class="admin-field">
          <label>상호</label>
          <input class="admin-input" type="text" name="company_name" required maxlength="120">
        </div>
        <div class="admin-field">
          <label>아이디</label>
          <input class="admin-input" type="text" name="login_id" maxlength="40" autocomplete="off" placeholder="비워 두면 자동 발급">
          <small class="admin-muted">영문 또는 숫자로 시작, 3~40자. 회원 아이디와 별도입니다.</small>
        </div>
        <div class="admin-field">
          <label>비밀번호</label>
          <input class="admin-input" type="text" name="password" autocomplete="new-password" placeholder="비워 두면 자동 발급">
          <small class="admin-muted" id="partnerPwHint">8자 이상, 영문과 숫자를 포함합니다. 저장 후 한 번만 표시됩니다.</small>
        </div>
        <div class="admin-field">
          <label>상태</label>
          <select class="admin-select" name="status">
            <option value="active">활성</option>
            <option value="inactive">비활성</option>
          </select>
        </div>
        <div class="admin-field">
          <label>담당자</label>
          <input class="admin-input" type="text" name="contact_name" maxlength="80">
        </div>
        <div class="admin-field">
          <label>연락처</label>
          <input class="admin-input" type="text" name="phone" maxlength="30">
        </div>
        <div class="admin-field">
          <label>이메일</label>
          <input class="admin-input" type="email" name="email" maxlength="190">
        </div>
        <div class="admin-field">
          <label>메모</label>
          <input class="admin-input" type="text" name="memo" maxlength="500">
        </div>
      </div>
      <div class="admin-modal-foot">
        <button type="button" class="admin-btn" data-close="partnerModal">취소</button>
        <button type="submit" class="admin-btn admin-btn--primary">저장</button>
      </div>
    </form>
  </div>
</div>

<div class="admin-modal" id="partnerIssuedModal" hidden>
  <div class="admin-modal-backdrop" data-close="partnerIssuedModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2>발급된 협력사 계정</h2>
      <button type="button" class="admin-modal-close" data-close="partnerIssuedModal" aria-label="닫기">×</button>
    </div>
    <div class="admin-modal-body">
      <p class="admin-muted">이 창을 닫으면 비밀번호는 다시 볼 수 없습니다. 협력사에 전달할 내용을 지금 복사해 두세요.</p>
      <div class="admin-field">
        <label>상호</label>
        <input class="admin-input" id="partnerIssuedCompany" type="text" readonly>
      </div>
      <div class="admin-field">
        <label>아이디</label>
        <input class="admin-input" id="partnerIssuedId" type="text" readonly>
      </div>
      <div class="admin-field">
        <label>비밀번호</label>
        <input class="admin-input" id="partnerIssuedPw" type="text" readonly>
      </div>
      <div class="admin-modal-foot">
        <button type="button" class="admin-btn" id="partnerCopyIssued">아이디·비밀번호 복사</button>
        <button type="button" class="admin-btn admin-btn--primary" data-close="partnerIssuedModal">확인</button>
      </div>
    </div>
  </div>
</div>
<script>
window.LABELUP_PARTNERS = {
  saveUrl: <?= json_encode(url('api/admin/partners/save'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  resetUrl: <?= json_encode(url('api/admin/partners/reset-password'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  deleteUrl: <?= json_encode(url('api/admin/partners/delete'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
};
</script>
