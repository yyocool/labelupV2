<?php
$filters = $filters ?? ['q' => '', 'source' => '', 'date_from' => '', 'date_to' => ''];
$stats = $stats ?? [
    'grant_count' => 0,
    'grant_amount' => 0,
    'admin_count' => 0,
    'admin_amount' => 0,
    'reward_count' => 0,
    'reward_amount' => 0,
    'user_count' => 0,
];
$items = $history['items'] ?? [];
$total = (int) ($history['total'] ?? 0);
$page = (int) ($history['page'] ?? 1);
$pages = (int) ($history['pages'] ?? 1);
$hasFilter = ($filters['q'] ?? '') !== '' || ($filters['source'] ?? '') !== ''
    || ($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '';
$queryParams = [
    'q' => (string) ($filters['q'] ?? ''),
    'source' => (string) ($filters['source'] ?? ''),
    'date_from' => (string) ($filters['date_from'] ?? ''),
    'date_to' => (string) ($filters['date_to'] ?? ''),
];
?>
<div class="admin-head">
  <div>
    <h1>잉크지급이력</h1>
    <p>관리자 지급과 회원가입 웰컴 등 자동 보상 내역입니다. 회원, 휴대전화, 사유, 처리자, 기간으로 찾을 수 있습니다.</p>
  </div>
</div>

<div class="admin-kpis">
  <div class="admin-kpi">
    <div class="lbl-row"><span class="lbl">지급 건수</span><span class="ic-badge">건</span></div>
    <div class="val"><?= number_format((int) $stats['grant_count']) ?></div>
    <div class="delta">회원 <?= number_format((int) $stats['user_count']) ?>명</div>
  </div>
  <div class="admin-kpi">
    <div class="lbl-row"><span class="lbl">지급 잉크</span><span class="ic-badge">C</span></div>
    <div class="val"><?= number_format((int) $stats['grant_amount']) ?></div>
    <div class="delta">합계</div>
  </div>
  <div class="admin-kpi">
    <div class="lbl-row"><span class="lbl">관리자 지급</span><span class="ic-badge">관</span></div>
    <div class="val"><?= number_format((int) $stats['admin_amount']) ?></div>
    <div class="delta"><?= number_format((int) $stats['admin_count']) ?>건</div>
  </div>
  <div class="admin-kpi">
    <div class="lbl-row"><span class="lbl">보상 지급</span><span class="ic-badge">보</span></div>
    <div class="val"><?= number_format((int) $stats['reward_amount']) ?></div>
    <div class="delta"><?= number_format((int) $stats['reward_count']) ?>건</div>
  </div>
</div>

<form class="admin-filter-bar" method="get" action="<?= url('admin/ops/credit-grants') ?>">
  <input class="admin-input admin-input--search" type="search" name="q" value="<?= e((string) $filters['q']) ?>" placeholder="이메일, 이름, 휴대전화, 지급 사유, 처리자">
  <select class="admin-select" name="source">
    <option value=""<?= $filters['source'] === '' ? ' selected' : '' ?>>전체 구분</option>
    <option value="admin"<?= $filters['source'] === 'admin' ? ' selected' : '' ?>>관리자</option>
    <option value="reward"<?= $filters['source'] === 'reward' ? ' selected' : '' ?>>보상</option>
    <option value="qr_coupon"<?= $filters['source'] === 'qr_coupon' ? ' selected' : '' ?>>QR 쿠폰</option>
  </select>
  <input class="admin-input" type="date" name="date_from" value="<?= e((string) $filters['date_from']) ?>" title="시작일">
  <input class="admin-input" type="date" name="date_to" value="<?= e((string) $filters['date_to']) ?>" title="종료일">
  <button class="admin-btn admin-btn--primary" type="submit">검색</button>
  <?php if ($hasFilter): ?>
  <a class="admin-btn" href="<?= url('admin/ops/credit-grants') ?>">초기화</a>
  <?php endif; ?>
</form>

<p class="admin-meta-line">총 <b><?= number_format($total) ?></b>건<?php if ($pages > 1): ?> · <?= $page ?> / <?= $pages ?> 페이지<?php endif; ?></p>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>일시</th>
        <th>회원</th>
        <th>휴대전화</th>
        <th>구분</th>
        <th>지급</th>
        <th>지급 후 잔액</th>
        <th>지급 사유</th>
        <th>처리자</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($items === []): ?>
      <tr><td colspan="8" class="empty">조건에 맞는 지급 이력이 없습니다.</td></tr>
      <?php else: ?>
      <?php foreach ($items as $grant): ?>
      <tr>
        <td><?= e(substr((string) ($grant['created_at'] ?? ''), 0, 16)) ?></td>
        <td>
          <?= admin_masked_contact((int) ($grant['user_id'] ?? 0), 'email', (string) ($grant['user_email'] ?? '')) ?>
          <a href="<?= url('admin/users/' . (int) ($grant['user_id'] ?? 0)) ?>">#<?= (int) ($grant['user_id'] ?? 0) ?></a>
          <?php if (trim((string) ($grant['user_name'] ?? '')) !== ''): ?>
          <br><small class="admin-muted"><?= admin_masked_contact((int) ($grant['user_id'] ?? 0), 'name', (string) $grant['user_name']) ?></small>
          <?php endif; ?>
        </td>
        <td><?= admin_masked_contact((int) ($grant['user_id'] ?? 0), 'phone', (string) ($grant['user_phone'] ?? '')) ?></td>
        <td><?= e((string) ($grant['source_label'] ?? '')) ?></td>
        <td class="is-plus">+<?= number_format((int) ($grant['amount'] ?? 0)) ?> C</td>
        <td><?= number_format((int) ($grant['balance_after'] ?? 0)) ?> C</td>
        <td><?= e((string) ($grant['description'] ?? '')) ?></td>
        <td><small><?= e((string) ($grant['actor_label'] ?? '-')) ?></small></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($pages > 1): ?>
<?php
  $basePath = 'admin/ops/credit-grants';
  require view_path('admin/partials/pagination.php');
?>
<?php endif; ?>
