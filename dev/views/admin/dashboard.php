<div class="admin-head">
  <div>
    <h1>대시보드</h1>
    <p>라벨업 서비스 현황을 한눈에 확인합니다.</p>
  </div>
  <div class="admin-head-actions">
    <a class="admin-btn" href="<?= url('admin/users') ?>">회원 관리</a>
  </div>
</div>

<div class="admin-kpis">
  <div class="admin-kpi">
    <div class="lbl-row"><span class="lbl">활성 회원</span><span class="ic-badge">◎</span></div>
    <div class="val"><?= number_format((int) ($stats['total_users'] ?? 0)) ?></div>
  </div>
  <div class="admin-kpi">
    <div class="lbl-row"><span class="lbl">판매 상품</span><span class="ic-badge">▧</span></div>
    <div class="val"><?= number_format((int) ($stats['active_products'] ?? 0)) ?></div>
  </div>
  <div class="admin-kpi">
    <div class="lbl-row"><span class="lbl">오늘 주문</span><span class="ic-badge">+</span></div>
    <div class="val"><?= number_format((int) ($stats['orders_today'] ?? 0)) ?></div>
  </div>
  <div class="admin-kpi">
    <div class="lbl-row"><span class="lbl">오늘 매출</span><span class="ic-badge">₩</span></div>
    <div class="val"><?= number_format((int) ($stats['revenue_today'] ?? 0)) ?></div>
  </div>
</div>

<div class="admin-kpis admin-kpis--sub">
  <div class="admin-kpi admin-kpi--sm">
    <div class="lbl-row"><span class="lbl">처리 대기</span></div>
    <div class="val"><?= number_format((int) ($stats['pending_orders'] ?? 0)) ?></div>
  </div>
  <div class="admin-kpi admin-kpi--sm">
    <div class="lbl-row"><span class="lbl">배송중</span></div>
    <div class="val"><?= number_format((int) ($stats['shipping_orders'] ?? 0)) ?></div>
  </div>
  <div class="admin-kpi admin-kpi--sm">
    <div class="lbl-row"><span class="lbl">오늘 가입</span></div>
    <div class="val"><?= number_format((int) ($stats['today_signups'] ?? 0)) ?></div>
  </div>
  <div class="admin-kpi admin-kpi--sm">
    <div class="lbl-row"><span class="lbl">오늘 로그인</span></div>
    <div class="val"><?= number_format((int) ($stats['today_logins'] ?? 0)) ?></div>
  </div>
</div>

<div class="admin-grid admin-grid--2-1">
  <section class="admin-panel">
    <div class="admin-panel-head">
      <h4>최근 로그인 기록</h4>
      <a class="more" href="<?= url('admin/users') ?>">회원 관리 →</a>
    </div>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>시간</th>
            <th>이메일</th>
            <th>이름</th>
            <th>IP</th>
            <th>결과</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentLogins)): ?>
          <tr><td colspan="5" class="empty">로그인 기록이 없습니다.</td></tr>
          <?php else: ?>
          <?php foreach ($recentLogins as $log): ?>
          <tr>
            <td><?= e(substr((string) ($log['created_at'] ?? ''), 0, 16)) ?></td>
            <td><?= e($log['email'] ?? '-') ?></td>
            <td><?= e($log['name'] ?? '-') ?></td>
            <td><?= e($log['ip_address'] ?? '-') ?></td>
            <td>
              <?php if (!empty($log['success'])): ?>
              <span class="admin-badge admin-badge--ok">성공</span>
              <?php else: ?>
              <span class="admin-badge admin-badge--err">실패</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="admin-panel">
    <div class="admin-panel-head"><h4>빠른 링크</h4></div>
    <div class="admin-quick-links">
      <a href="<?= url('admin/shop/orders') ?>">주문 관리</a>
      <a href="<?= url('admin/shop/products') ?>">상품 관리</a>
      <a href="<?= url('admin/shop/shipping') ?>">배송 관리</a>
      <a href="<?= url('admin/users') ?>">회원 목록 보기</a>
      <a href="<?= url('/') ?>">메인 사이트</a>
    </div>
    <div class="admin-note">
      <b>쇼핑몰운영</b>
      <p>카테고리, 라벨 규격, 상품, 주문, 배송, 쿠폰, 배너를 관리할 수 있습니다.</p>
    </div>
  </section>
</div>
