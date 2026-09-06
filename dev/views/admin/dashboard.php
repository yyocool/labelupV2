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

<?php
$dashGroups = admin_menu_groups();
$groupHints = [
    '쇼핑몰운영' => '카테고리 · 규격 · 상품 · 주문 · 배송 · 쿠폰 · 배너',
    '컨텐츠관리' => '클립아트 · 사용자디자인 · 템플릿 · 상세페이지',
    'AI 관리' => '예시 프롬프트 · 토큰로그 · 회원별 사용 · 사용량 통계',
    '운영관리' => '회원 · 설정 · 히어로 · 팝업 · FAQ · 문의 · 크레딧',
    '설정' => '관리자 · 회원등급 · SEO · 광고 스크립트',
];
?>
<section class="admin-panel admin-dash-menus-wrap">
  <div class="admin-panel-head">
    <h4>메뉴 바로가기</h4>
    <a class="more" href="<?= url('/') ?>">메인 사이트 →</a>
  </div>
  <?php if ($dashGroups === []): ?>
  <p class="admin-muted">접근 가능한 메뉴가 없습니다.</p>
  <?php else: ?>
  <div class="admin-dash-menus">
    <?php foreach ($dashGroups as $groupName => $items): ?>
    <div class="admin-dash-menu-group">
      <h5><?= e($groupName) ?></h5>
      <?php if (!empty($groupHints[$groupName])): ?>
      <p class="hint"><?= e($groupHints[$groupName]) ?></p>
      <?php endif; ?>
      <div class="admin-dash-menu-grid">
        <?php foreach ($items as $item): ?>
        <a href="<?= url((string) $item['href']) ?>">
          <span class="ic"><?= e((string) ($item['ic'] ?? '▣')) ?></span>
          <span class="lbl"><?= e((string) $item['label']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

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
    <div class="admin-panel-head"><h4>운영 안내</h4></div>
    <div class="admin-note admin-note--flush">
      <b>사이드바와 동일한 메뉴</b>
      <p>컨텐츠관리(사용자디자인·템플릿), AI 관리, 운영관리, 설정 메뉴가 권한에 따라 위에 표시됩니다.</p>
    </div>
    <div class="admin-quick-links admin-quick-links--spaced">
      <a href="<?= url('/') ?>">메인 사이트</a>
      <?php if (admin_can_menu('content-user-designs')): ?>
      <a href="<?= url('admin/content/user-designs') ?>">사용자디자인 검수</a>
      <?php endif; ?>
      <?php if (admin_can_menu('content-templates')): ?>
      <a href="<?= url('admin/content/templates') ?>">템플릿관리</a>
      <?php endif; ?>
      <?php if (admin_can_menu('ops-inquiries')): ?>
      <a href="<?= url('admin/ops/inquiries') ?>">1:1 문의</a>
      <?php endif; ?>
    </div>
  </section>
</div>
