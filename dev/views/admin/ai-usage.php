<?php
use App\Services\AiUsageService;
$stats = $stats ?? ['period' => '7d', 'summary' => [], 'daily' => [], 'recent' => []];
$s = $stats['summary'] ?? [];
$period = (string) ($stats['period'] ?? '7d');
$periods = ['today' => '오늘', '7d' => '최근 7일', '30d' => '최근 30일', 'all' => '전체'];
?>
<div class="admin-head">
  <div>
    <h1>사용량 통계</h1>
    <p>홈·편집기 라비AI 요청 수, 의도, 토큰 사용량을 확인합니다.</p>
  </div>
  <form class="admin-head-actions" method="get" action="<?= url('admin/ai/usage') ?>">
    <select class="admin-input" name="period" onchange="this.form.submit()">
      <?php foreach ($periods as $key => $label): ?>
      <option value="<?= e($key) ?>"<?= $period === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="admin-kpis">
  <div class="admin-kpi"><div class="lbl">총 요청</div><div class="val"><?= number_format((int) ($s['total'] ?? 0)) ?></div></div>
  <div class="admin-kpi"><div class="lbl">성공 / 실패</div><div class="val"><?= number_format((int) ($s['ok_count'] ?? 0)) ?> <small>/ <?= number_format((int) ($s['error_count'] ?? 0)) ?></small></div></div>
  <div class="admin-kpi"><div class="lbl">사용 토큰</div><div class="val"><?= number_format((int) ($s['tokens'] ?? 0)) ?></div></div>
  <div class="admin-kpi"><div class="lbl">이용 회원</div><div class="val"><?= number_format((int) ($s['users'] ?? 0)) ?></div></div>
</div>
<div class="admin-kpis admin-kpis--sub">
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">대화</div><div class="val"><?= number_format((int) ($s['chat_count'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">상품 추천</div><div class="val"><?= number_format((int) ($s['product_count'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">클립아트</div><div class="val"><?= number_format((int) ($s['clipart_count'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">홈 / 편집기</div><div class="val"><?= number_format((int) ($s['home_count'] ?? 0)) ?> <small>/ <?= number_format((int) ($s['editor_count'] ?? 0)) ?></small></div></div>
</div>

<div class="admin-card" style="margin-bottom:18px">
  <h2>일별 요청</h2>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr><th>날짜</th><th>요청</th><th>성공</th><th>실패</th><th>토큰</th></tr>
      </thead>
      <tbody>
      <?php if (empty($stats['daily'])): ?>
        <tr><td colspan="5" class="empty">해당 기간 사용 기록이 없습니다.</td></tr>
      <?php else: ?>
      <?php foreach ($stats['daily'] as $row): ?>
        <tr>
          <td><?= e((string) ($row['day'] ?? '')) ?></td>
          <td><?= number_format((int) ($row['total'] ?? 0)) ?></td>
          <td><?= number_format((int) ($row['ok_count'] ?? 0)) ?></td>
          <td><?= number_format((int) ($row['error_count'] ?? 0)) ?></td>
          <td><?= number_format((int) ($row['tokens'] ?? 0)) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="admin-card">
  <h2>최근 요청</h2>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>시간</th>
          <th>회원</th>
          <th>위치</th>
          <th>의도</th>
          <th>토큰</th>
          <th>상태</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($stats['recent'])): ?>
        <tr><td colspan="6" class="empty">아직 사용 기록이 없습니다. 홈이나 편집기에서 라비AI를 사용하면 여기에 쌓입니다.</td></tr>
      <?php else: ?>
      <?php foreach ($stats['recent'] as $row): ?>
        <tr>
          <td><small><?= e(substr((string) ($row['created_at'] ?? ''), 0, 16)) ?></small></td>
          <td><?= e((string) ($row['email'] ?? ('#' . (int) ($row['user_id'] ?? 0)))) ?></td>
          <td><?= e(AiUsageService::surfaceLabel((string) ($row['surface'] ?? ''))) ?></td>
          <td><?= e(AiUsageService::intentLabel((string) ($row['intent'] ?? ''))) ?></td>
          <td><?= number_format((int) ($row['total_tokens'] ?? 0)) ?></td>
          <td>
            <?php if (($row['status'] ?? '') === 'ok'): ?>
              <span class="admin-badge admin-badge--ok">성공</span>
            <?php else: ?>
              <span class="admin-badge admin-badge--err" title="<?= e((string) ($row['error_message'] ?? '')) ?>">실패</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
