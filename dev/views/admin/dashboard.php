<div class="admin-head">
  <div>
    <h1>대시보드</h1>
    <p>라벨업 서비스 현황을 한눈에 확인합니다.</p>
  </div>
  <div class="admin-head-actions">
    <a class="admin-btn" href="<?= url('admin/users') ?>">회원 관리</a>
  </div>
</div>

<?php $specIssues = $specIssues ?? []; ?>
<?php if ($specIssues !== []): ?>
  <section class="admin-specwarn" aria-label="용지 규격 이상">
    <h2 class="admin-specwarn__head">
      용지 규격에 이상이 있습니다 · <?= count($specIssues) ?>건
    </h2>
    <p class="admin-specwarn__desc">
      아래 규격은 편집기 미리보기·인쇄가 실제 용지와 어긋날 수 있습니다.
      <a href="<?= url('admin/shop/specs') ?>">규격 관리에서 수정</a>
    </p>
    <ul class="admin-specwarn__list">
      <?php foreach ($specIssues as $issue): ?>
        <li>
          <span class="admin-specwarn__id">
            #<?= (int) $issue['id'] ?><?= $issue['sku'] !== '' ? ' · ' . e($issue['sku']) : '' ?>
          </span>
          <span class="admin-specwarn__name"><?= e($issue['name']) ?></span>
          <?php if ($issue['products'] > 0): ?>
            <span class="admin-specwarn__count">상품 <?= (int) $issue['products'] ?>개</span>
          <?php endif; ?>
          <?php foreach ($issue['messages'] as $message): ?>
            <span class="admin-specwarn__msg"><?= e($message) ?></span>
          <?php endforeach; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

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

<?php if (admin_can_menu('settings-environment')): ?>
<section class="admin-panel oai-bill" id="oaiBill" data-url="<?= e(url('api/admin/openai-billing/summary')) ?>">
  <div class="admin-panel-head">
    <h4>Chat GPT API 잔액</h4>
    <div class="oai-bill__links">
      <button type="button" class="oai-bill__refresh" id="oaiBillRefresh" title="OpenAI에서 다시 불러오기">↻ 새로고침</button>
      <a class="more" href="<?= url('admin/settings/environment') ?>#ai">설정·충전 기록 →</a>
      <a class="more" href="https://platform.openai.com/settings/organization/billing/overview" target="_blank" rel="noopener">OpenAI 결제 ↗</a>
    </div>
  </div>
  <div class="oai-bill__body" id="oaiBillBody"><p class="admin-muted">불러오는 중…</p></div>
</section>
<style>
.oai-bill{margin-bottom:16px}
.oai-bill__links{display:flex;align-items:center;gap:14px}
.oai-bill__refresh{border:0;background:none;color:var(--accent);font-size:13px;font-weight:700;cursor:pointer;padding:0}
.oai-bill__refresh:disabled{opacity:.5;cursor:default}
.oai-bill__grid{display:grid;grid-template-columns:1.3fr 1fr 1fr 1.6fr;gap:12px;align-items:stretch}
.oai-bill__cell{padding:14px 16px;border:1px solid var(--line,#e5e7eb);border-radius:14px;background:#fff;display:flex;flex-direction:column;gap:4px}
.oai-bill__cell .lbl{font-size:13px;color:#6b7280;font-weight:700}
.oai-bill__cell .val{font-size:24px;font-weight:800;color:#111827}
.oai-bill__cell small{font-size:12px;color:#9ca3af}
.oai-bill__cell--main{background:linear-gradient(120deg,#fbf3f5,#f7f1ec);border-color:transparent}
.oai-bill__cell--main .val{font-size:28px;color:var(--accent)}
.oai-bill__cell--main.is-low{background:#fff4f2}
.oai-bill__cell--main.is-low .val{color:#b42318}
.oai-bill__bars{display:flex;align-items:flex-end;gap:6px;height:64px;margin-top:4px}
.oai-bill__bar{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:3px;height:100%}
.oai-bill__bar i{display:block;width:100%;min-height:2px;border-radius:4px 4px 0 0;background:linear-gradient(180deg,var(--accent-2,#c06),var(--accent,#8a1c3c))}
.oai-bill__bar span{font-size:10px;color:#9ca3af}
.oai-bill__warn{margin:12px 0 0;padding:10px 12px;border-radius:10px;background:#fff4f2;color:#b42318;font-size:13px}
.oai-bill__note{margin:10px 0 0;font-size:12px;color:#9ca3af}
@media(max-width:1100px){.oai-bill__grid{grid-template-columns:1fr 1fr}}
@media(max-width:640px){.oai-bill__grid{grid-template-columns:1fr}.oai-bill__links{flex-wrap:wrap;gap:8px}}
</style>
<script>
(function () {
  var root = document.getElementById('oaiBill');
  var body = document.getElementById('oaiBillBody');
  var btn = document.getElementById('oaiBillRefresh');
  var envUrl = <?= json_encode(url('admin/settings/environment') . '#ai', JSON_UNESCAPED_SLASHES) ?>;
  var usd = function (n, d) { return '$' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: d == null ? 2 : d, maximumFractionDigits: d == null ? 2 : d }); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };

  function render(b) {
    if (!b.configured) {
      body.innerHTML = '<p class="admin-muted">OpenAI Admin API 키가 없어 사용량을 불러올 수 없습니다. <a href="' + envUrl + '">환경설정 › AI</a>에서 키와 충전 기록을 입력해 주세요.</p>';
      return;
    }
    if (!b.ok) {
      body.innerHTML = '<p class="oai-bill__warn">' + esc(b.error || '사용량을 불러오지 못했습니다.') + '</p>';
      return;
    }
    var max = Math.max.apply(null, (b.last7 || []).map(function (d) { return d.cost; }).concat([0.0001]));
    var bars = (b.last7 || []).map(function (d) {
      var h = Math.max(2, Math.round(d.cost / max * 46));
      return '<div class="oai-bill__bar" title="' + esc(d.date) + ' · ' + usd(d.cost, 4) + '"><i style="height:' + h + 'px"></i><span>' + esc(d.date.slice(5).replace('-', '/')) + '</span></div>';
    }).join('');
    var hasTopup = b.remaining !== null && b.remaining !== undefined;
    var main = hasTopup
      ? '<div class="val">' + usd(b.remaining) + '</div><small>충전 ' + usd(b.topup_total) + ' − 사용 ' + usd(b.spent_since_topup) + ' (' + esc(b.first_topup_date) + '부터)</small>'
      : '<div class="val">—</div><small><a href="' + envUrl + '">충전 기록을 입력</a>하면 잔액이 계산됩니다.</small>';
    body.innerHTML =
      '<div class="oai-bill__grid">' +
        '<div class="oai-bill__cell oai-bill__cell--main' + (b.is_low ? ' is-low' : '') + '"><span class="lbl">추정 잔액</span>' + main + '</div>' +
        '<div class="oai-bill__cell"><span class="lbl">이번 달 사용</span><div class="val">' + usd(b.month_cost) + '</div><small>' + esc(String(b.today || '').slice(0, 7)) + ' (UTC)</small></div>' +
        '<div class="oai-bill__cell"><span class="lbl">오늘 사용</span><div class="val">' + usd(b.today_cost) + '</div><small>집계가 몇 시간 늦을 수 있음</small></div>' +
        '<div class="oai-bill__cell"><span class="lbl">최근 7일</span><div class="oai-bill__bars">' + bars + '</div></div>' +
      '</div>' +
      (b.is_low ? '<p class="oai-bill__warn">추정 잔액이 경고 기준(' + usd(b.low_threshold) + ') 이하입니다. OpenAI 크레딧 충전이 필요합니다.</p>' : '') +
      '<p class="oai-bill__note">OpenAI는 잔액 조회 API를 제공하지 않아 추정값입니다. 실제 잔액은 OpenAI 결제 페이지에서 확인하세요. 조회 ' + esc(b.fetched_at || '') + '</p>';
  }

  async function load(refresh) {
    btn.disabled = true;
    try {
      var res = await fetch(root.getAttribute('data-url') + (refresh ? '?refresh=1' : ''), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
      var data = await res.json().catch(function () { return {}; });
      if (!res.ok || data.success === false) throw new Error(data.message || '사용량을 불러오지 못했습니다.');
      render((data.data || {}).billing || {});
    } catch (err) {
      body.innerHTML = '<p class="oai-bill__warn">' + esc(err.message) + '</p>';
    } finally {
      btn.disabled = false;
    }
  }

  btn.addEventListener('click', function () { load(true); });
  load(false);
})();
</script>
<?php endif; ?>

<?php
$dashGroups = admin_menu_groups();
$groupHints = [
    '쇼핑몰운영' => '카테고리 · 규격 · 상품 · 주문 · 배송 · 쿠폰 · 배너',
    '컨텐츠관리' => '클립아트 · 사용자디자인 · 템플릿 · 상세페이지',
    'AI 관리' => '예시 프롬프트 · 토큰로그 · 회원별 사용 · 사용량 통계',
    '운영관리' => '회원 · 설정 · 히어로 · 팝업 · FAQ · 문의 · 오류&제안 · 잉크',
    '설정' => '관리자 · 회원등급 · SEO · 광고 스크립트',
    '협력사' => '협력사 계정 발급 · 등록 관리',
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
