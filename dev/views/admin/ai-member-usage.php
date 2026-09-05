<?php
use App\Services\AiUsageService;
$result = $result ?? ['items' => [], 'filters' => [], 'summary' => []];
$f = $result['filters'] ?? [];
$s = $result['summary'] ?? [];
$items = $result['items'] ?? [];
?>
<div class="admin-head">
  <div>
    <h1>회원별 사용</h1>
    <p>회원마다 쌓인 AI 요청 수, 토큰, 예상 금액을 비교합니다. 행을 누르면 해당 회원 로그로 이동합니다.</p>
  </div>
  <div class="admin-head-actions">
    <a class="admin-btn" href="<?= url('admin/ai/token-logs') ?>">토큰사용로그</a>
    <a class="admin-btn" href="<?= url('admin/ai/usage') ?>">사용량 통계</a>
  </div>
</div>

<form class="admin-filter-bar" method="get" action="<?= url('admin/ai/member-usage') ?>">
  <input class="admin-input admin-input--search" type="search" name="q" value="<?= e((string) ($f['q'] ?? '')) ?>" placeholder="회원 이메일·이름 검색">
  <select class="admin-select" name="intent">
    <?php foreach (AiUsageService::intentOptions() as $key => $label): ?>
    <option value="<?= e($key) ?>"<?= ((string) ($f['intent'] ?? '')) === $key ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="admin-select" name="surface">
    <?php foreach (AiUsageService::surfaceOptions() as $key => $label): ?>
    <option value="<?= e($key) ?>"<?= ((string) ($f['surface'] ?? '')) === $key ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <input class="admin-input" type="date" name="from" value="<?= e((string) ($f['from'] ?? '')) ?>" title="시작일">
  <input class="admin-input" type="date" name="to" value="<?= e((string) ($f['to'] ?? '')) ?>" title="종료일">
  <button class="admin-btn admin-btn--primary" type="submit">검색</button>
  <a class="admin-btn" href="<?= url('admin/ai/member-usage') ?>">초기화</a>
</form>

<div class="admin-kpis admin-kpis--sub">
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">회원 수</div><div class="val"><?= number_format((int) ($s['members'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">요청</div><div class="val"><?= number_format((int) ($s['requests'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">사용 토큰</div><div class="val"><?= number_format((int) ($s['tokens'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">예상금액(원)</div><div class="val"><?= e(format_ai_krw($s['cost_krw'] ?? 0)) ?></div></div>
</div>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>순위</th>
        <th>회원</th>
        <th>요청</th>
        <th>성공 / 실패</th>
        <th>토큰</th>
        <th>예상금액</th>
        <th>최근 사용</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($items === []): ?>
      <tr><td colspan="7" class="empty">조건에 맞는 사용 회원이 없습니다.</td></tr>
    <?php else: ?>
    <?php foreach ($items as $i => $row): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td>
          <a href="<?= url('admin/ai/token-logs?' . http_build_query(['user_id' => (int) ($row['user_id'] ?? 0)])) ?>">
            <?= e((string) ($row['member_label'] ?? ('#' . (int) ($row['user_id'] ?? 0)))) ?>
          </a>
        </td>
        <td><?= number_format((int) ($row['requests'] ?? 0)) ?></td>
        <td><?= number_format((int) ($row['ok_count'] ?? 0)) ?> / <?= number_format((int) ($row['error_count'] ?? 0)) ?></td>
        <td><?= number_format((int) ($row['tokens'] ?? 0)) ?></td>
        <td>₩<?= e(format_ai_krw($row['cost_krw'] ?? 0)) ?></td>
        <td><small><?= e(substr((string) ($row['last_at'] ?? ''), 0, 16)) ?></small></td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
