<?php
use App\Services\CreditService;
use App\Services\ShopAdminService;
$user = $detail['user'] ?? [];
$creditBalance = (int) ($detail['credit_balance'] ?? 0);
$creditTx = $detail['credit_tx']['items'] ?? [];
$csLogs = $detail['cs_logs'] ?? [];
$loginLogs = $detail['login_logs'] ?? [];
$orders = $detail['orders'] ?? [];
$userId = (int) ($user['id'] ?? 0);
?>
<div class="admin-head">
  <div>
    <h1>회원 상세</h1>
    <p><?= e($user['email'] ?? '') ?> · ID <?= $userId ?></p>
  </div>
  <div class="admin-head-actions">
    <a class="admin-btn" href="<?= url('admin/users') ?>">← 회원 목록</a>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<div class="admin-detail-grid">
  <section class="admin-card">
    <h2>기본 정보</h2>
    <dl class="admin-dl">
      <div><dt>이름</dt><dd><?= e($user['name'] ?? '-') ?></dd></div>
      <div><dt>회사</dt><dd><?= e($user['company'] ?? '-') ?></dd></div>
      <div><dt>전화</dt><dd><?= e($user['phone'] ?? '-') ?></dd></div>
      <div><dt>역할</dt><dd><?= ($user['role'] ?? '') === 'admin' ? '관리자' : '일반회원' ?></dd></div>
      <div><dt>상태</dt><dd><?= e($user['status'] ?? '-') ?></dd></div>
      <div><dt>가입일</dt><dd><?= e(substr((string) ($user['created_at'] ?? ''), 0, 16)) ?></dd></div>
      <div><dt>최근 로그인</dt><dd><?= e(substr((string) ($user['last_login_at'] ?? '-'), 0, 16)) ?></dd></div>
    </dl>
  </section>

  <section class="admin-card">
    <h2>크레딧</h2>
    <p class="admin-credit-balance"><strong><?= number_format($creditBalance) ?> C</strong></p>
    <form id="creditAdjustForm" class="admin-inline-form">
      <input type="hidden" name="user_id" value="<?= $userId ?>">
      <input class="admin-input" type="number" name="amount" placeholder="± 크레딧" required>
      <input class="admin-input" type="text" name="description" placeholder="사유" value="관리자 조정">
      <button type="submit" class="admin-btn admin-btn--primary">조정</button>
    </form>
  </section>
</div>

<section class="admin-section">
  <div class="admin-section-head">
    <h2 class="admin-section-title">크레딧 사용·적립 내역</h2>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>일시</th>
          <th>유형</th>
          <th>출처</th>
          <th>변동</th>
          <th>잔액</th>
          <th>내용</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($creditTx)): ?>
        <tr><td colspan="6" class="empty">크레딧 내역이 없습니다.</td></tr>
      <?php else: ?>
      <?php foreach ($creditTx as $tx): ?>
        <tr>
          <td><?= e(substr((string) ($tx['created_at'] ?? ''), 0, 16)) ?></td>
          <td><?= e(CreditService::txTypeLabel((string) $tx['tx_type'])) ?></td>
          <td><?= e(CreditService::sourceLabel((string) $tx['source'])) ?></td>
          <td class="<?= (int) $tx['amount'] >= 0 ? 'is-plus' : 'is-minus' ?>"><?= (int) $tx['amount'] >= 0 ? '+' : '' ?><?= number_format((int) $tx['amount']) ?> C</td>
          <td><?= number_format((int) $tx['balance_after']) ?> C</td>
          <td><?= e($tx['description'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="admin-section">
  <div class="admin-section-head">
    <h2 class="admin-section-title">CS 이력</h2>
    <button type="button" class="admin-btn admin-btn--primary js-credit-add" data-entity="cs-log" data-user-id="<?= $userId ?>">+ CS 등록</button>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>일시</th>
          <th>분류</th>
          <th>제목</th>
          <th>상태</th>
          <th>담당</th>
          <th>관리</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($csLogs)): ?>
        <tr><td colspan="6" class="empty">CS 이력이 없습니다.</td></tr>
      <?php else: ?>
      <?php foreach ($csLogs as $log): ?>
        <tr>
          <td><?= e(substr((string) ($log['created_at'] ?? ''), 0, 16)) ?></td>
          <td><?= e(CreditService::csCategoryLabel((string) $log['category'])) ?></td>
          <td>
            <strong><?= e($log['subject']) ?></strong>
            <?php if (!empty($log['content'])): ?>
            <br><small class="admin-muted"><?= e(mb_strimwidth((string) $log['content'], 0, 80, '…')) ?></small>
            <?php endif; ?>
          </td>
          <td><?= e(CreditService::csStatusLabel((string) $log['status'])) ?></td>
          <td><small><?= e($log['admin_email'] ?? '-') ?></small></td>
          <td>
            <button type="button" class="admin-btn admin-btn--sm js-credit-edit" data-entity="cs-log" data-user-id="<?= $userId ?>" data-row='<?= e(json_encode($log, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="admin-section">
  <h2 class="admin-section-title">주문 이력</h2>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>주문번호</th>
          <th>상태</th>
          <th>결제</th>
          <th>금액</th>
          <th>주문일</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($orders)): ?>
        <tr><td colspan="5" class="empty">주문 내역이 없습니다.</td></tr>
      <?php else: ?>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td><code><?= e($order['order_no'] ?? '') ?></code></td>
          <td><?= e(ShopAdminService::orderStatusLabel((string) ($order['status'] ?? ''))) ?></td>
          <td><?= e($order['payment_status'] ?? '-') ?></td>
          <td><?= number_format((int) ($order['total_amount'] ?? 0)) ?>원</td>
          <td><?= e(substr((string) ($order['created_at'] ?? ''), 0, 16)) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="admin-section">
  <h2 class="admin-section-title">로그인 이력</h2>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>일시</th>
          <th>IP</th>
          <th>결과</th>
          <th>메시지</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($loginLogs)): ?>
        <tr><td colspan="4" class="empty">로그인 이력이 없습니다.</td></tr>
      <?php else: ?>
      <?php foreach ($loginLogs as $log): ?>
        <tr>
          <td><?= e(substr((string) ($log['created_at'] ?? ''), 0, 16)) ?></td>
          <td><?= e($log['ip_address'] ?? '-') ?></td>
          <td><?= ($log['success'] ?? 0) ? '<span class="admin-badge admin-badge--ok">성공</span>' : '<span class="admin-badge admin-badge--err">실패</span>' ?></td>
          <td><small><?= e($log['message'] ?? '') ?></small></td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<div class="admin-modal" id="creditModal" hidden>
  <div class="admin-modal-backdrop" data-close="creditModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="creditModalTitle">
    <div class="admin-modal-head">
      <h2 id="creditModalTitle">CS 이력</h2>
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
