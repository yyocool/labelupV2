<?php
use App\Services\AiUsageService;
$result = $result ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'summary' => [], 'filters' => []];
$f = $result['filters'] ?? [];
$s = $result['summary'] ?? [];
$items = $result['items'] ?? [];
$queryParams = array_filter([
    'q' => (string) ($f['q'] ?? ''),
    'user_id' => ((int) ($f['user_id'] ?? 0) > 0) ? (string) $f['user_id'] : '',
    'intent' => (string) ($f['intent'] ?? ''),
    'surface' => (string) ($f['surface'] ?? ''),
    'status' => (string) ($f['status'] ?? ''),
    'from' => (string) ($f['from'] ?? ''),
    'to' => (string) ($f['to'] ?? ''),
], static fn (string $v): bool => $v !== '');
?>
<div class="admin-head">
  <div>
    <h1>토큰사용로그</h1>
    <p>라비 AI API 호출을 회원·유형·위치별로 검색하고, 토큰과 예상 금액(원)을 확인합니다.</p>
  </div>
  <div class="admin-head-actions">
    <a class="admin-btn" href="<?= url('admin/ai/token-logs/export') . ($queryParams ? ('?' . http_build_query($queryParams)) : '') ?>">CSV보내기</a>
    <a class="admin-btn" href="<?= url('admin/ai/member-usage') ?>">회원별 사용</a>
  </div>
</div>

<form class="admin-filter-bar" method="get" action="<?= url('admin/ai/token-logs') ?>">
  <input class="admin-input admin-input--search" type="search" name="q" value="<?= e((string) ($f['q'] ?? '')) ?>" placeholder="회원 이메일·이름·모델·오류 검색">
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
  <select class="admin-select" name="status">
    <option value=""<?= ((string) ($f['status'] ?? '')) === '' ? ' selected' : '' ?>>상태 전체</option>
    <option value="ok"<?= ((string) ($f['status'] ?? '')) === 'ok' ? ' selected' : '' ?>>성공</option>
    <option value="error"<?= ((string) ($f['status'] ?? '')) === 'error' ? ' selected' : '' ?>>실패</option>
  </select>
  <input class="admin-input" type="date" name="from" value="<?= e((string) ($f['from'] ?? '')) ?>" title="시작일">
  <input class="admin-input" type="date" name="to" value="<?= e((string) ($f['to'] ?? '')) ?>" title="종료일">
  <?php if ((int) ($f['user_id'] ?? 0) > 0): ?>
  <input type="hidden" name="user_id" value="<?= (int) $f['user_id'] ?>">
  <?php endif; ?>
  <button class="admin-btn admin-btn--primary" type="submit">검색</button>
  <a class="admin-btn" href="<?= url('admin/ai/token-logs') ?>">초기화</a>
</form>

<div class="admin-kpis admin-kpis--sub">
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">건수</div><div class="val"><?= number_format((int) ($s['total'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">사용 토큰</div><div class="val"><?= number_format((int) ($s['tokens'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">예상금액(원)</div><div class="val"><?= e(format_ai_krw($s['cost_krw'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">성공 / 실패</div><div class="val"><?= number_format((int) ($s['ok_count'] ?? 0)) ?> <small>/ <?= number_format((int) ($s['error_count'] ?? 0)) ?></small></div></div>
</div>
<p class="admin-meta-line">금액은 OpenAI 단가·환율 기준 추정값입니다. 회원 번호가 있으면 해당 회원만 표시됩니다.</p>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>시간</th>
        <th>회원</th>
        <th>위치</th>
        <th>유형</th>
        <th>모델</th>
        <th>토큰</th>
        <th>예상금액</th>
        <th>상태</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($items === []): ?>
      <tr><td colspan="8" class="empty">조건에 맞는 사용 기록이 없습니다.</td></tr>
    <?php else: ?>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><small><?= e(substr((string) ($row['created_at'] ?? ''), 0, 16)) ?></small></td>
        <td>
          <?php if ((int) ($row['user_id'] ?? 0) > 0): ?>
          <a href="<?= url('admin/ai/token-logs?' . http_build_query(['user_id' => (int) $row['user_id']])) ?>"><?= e((string) ($row['member_label'] ?? '')) ?></a>
          <?php else: ?>
          <?= e((string) ($row['member_label'] ?? '비회원')) ?>
          <?php endif; ?>
        </td>
        <td><?= e((string) ($row['surface_label'] ?? '')) ?></td>
        <td><?= e((string) ($row['intent_label'] ?? '')) ?></td>
        <td><small><?= e((string) ($row['model'] ?? '—')) ?></small></td>
        <td>
          <?= number_format((int) ($row['total_tokens'] ?? 0)) ?>
          <small class="admin-muted">(<?= number_format((int) ($row['prompt_tokens'] ?? 0)) ?>+<?= number_format((int) ($row['completion_tokens'] ?? 0)) ?>)</small>
        </td>
        <td>₩<?= e(format_ai_krw($row['cost_krw'] ?? 0)) ?></td>
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
<?php
$page = (int) ($result['page'] ?? 1);
$pages = (int) ($result['pages'] ?? 1);
$basePath = 'admin/ai/token-logs';
require view_path('admin/partials/pagination.php');
?>
