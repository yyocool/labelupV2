<?php
$filters = $filters ?? [];
$statsAction = (string) ($statsAction ?? 'admin/stats');
$statsMode = (string) ($statsMode ?? 'overview');
$statsError = (string) ($statsError ?? '');
$sections = \App\Services\AccessStatsService::sections();
$ranges = [
    'today' => '오늘',
    'yesterday' => '어제',
    '7d' => '7일',
    '30d' => '30일',
    '90d' => '90일',
    'month' => '이번 달',
];
$statsQuery = [
    'range' => (string) ($filters['range'] ?? '7d'),
    'bots' => (string) ($filters['bots'] ?? '0'),
    'admin' => (string) ($filters['admin'] ?? '0'),
];
if (($filters['section'] ?? '') !== '') {
    $statsQuery['section'] = (string) $filters['section'];
}
if (($filters['range'] ?? '') === 'custom') {
    $statsQuery['from'] = (string) ($filters['from'] ?? '');
    $statsQuery['to'] = (string) ($filters['to'] ?? '');
}
foreach (['q', 'device', 'actor', 'http', 'grain'] as $extraKey) {
    if (($filters[$extraKey] ?? '') !== '') {
        $statsQuery[$extraKey] = (string) $filters[$extraKey];
    }
}
$nav = [
    'overview' => ['개요', 'admin/stats'],
    'trend' => ['기간 추이', 'admin/stats/trend'],
    'pages' => ['페이지', 'admin/stats/pages'],
    'referrers' => ['유입 경로', 'admin/stats/referrers'],
    'visitors' => ['방문자', 'admin/stats/visitors'],
    'environment' => ['접속 환경', 'admin/stats/environment'],
    'hours' => ['시간대', 'admin/stats/hours'],
    'live' => ['실시간', 'admin/stats/live'],
    'logs' => ['접속 로그', 'admin/stats/logs'],
];
?>
<div class="admin-head">
  <div>
    <h1><?= e((string) ($statsTitle ?? '접속통계')) ?></h1>
    <p><?= e((string) ($statsDesc ?? '')) ?></p>
  </div>
</div>

<nav class="stats-tabs" aria-label="접속통계 하위 메뉴">
  <?php foreach ($nav as $key => $item): ?>
  <a class="<?= $statsMode === $key ? 'is-active' : '' ?>" href="<?= e(url($item[1]) . '?' . http_build_query($statsQuery)) ?>"><?= e($item[0]) ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($statsMode !== 'live'): ?>
<div class="stats-ranges">
  <?php foreach ($ranges as $key => $label): ?>
  <a class="<?= ($filters['range'] ?? '') === $key ? 'is-active' : '' ?>" href="<?= e(url($statsAction) . '?' . http_build_query(array_merge($statsQuery, ['range' => $key]))) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
  <span class="stats-range-label"><?= e((string) ($filters['from'] ?? '')) ?> ~ <?= e((string) ($filters['to'] ?? '')) ?></span>
</div>
<?php endif; ?>

<form class="stats-filters" method="get" action="<?= url($statsAction) ?>">
  <input type="hidden" name="range" value="<?= e((string) ($statsMode === 'live' ? ($filters['range'] ?? '7d') : 'custom')) ?>">
  <?php if ($statsMode !== 'live'): ?>
  <label>시작 <input class="admin-input" type="date" name="from" value="<?= e((string) ($filters['from'] ?? '')) ?>"></label>
  <label>끝 <input class="admin-input" type="date" name="to" value="<?= e((string) ($filters['to'] ?? '')) ?>"></label>
  <?php endif; ?>
  <label>영역
    <select class="admin-input" name="section">
      <?php foreach ($sections as $key => $label): ?>
      <option value="<?= e($key) ?>"<?= ($filters['section'] ?? '') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <?php if (in_array($statsMode, ['pages', 'logs'], true)): ?>
  <label>검색 <input class="admin-input" type="search" name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="경로, 제목, IP"></label>
  <?php endif; ?>
  <?php if ($statsMode === 'logs'): ?>
  <label>기기
    <select class="admin-input" name="device">
      <option value="">전체 기기</option>
      <?php foreach (['desktop' => 'PC', 'mobile' => '모바일', 'tablet' => '태블릿', 'bot' => '봇'] as $key => $label): ?>
      <option value="<?= e($key) ?>"<?= ($filters['device'] ?? '') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>대상
    <select class="admin-input" name="actor">
      <option value="">전체</option>
      <?php foreach (['guest' => '비회원', 'member' => '회원', 'admin' => '관리자'] as $key => $label): ?>
      <option value="<?= e($key) ?>"<?= ($filters['actor'] ?? '') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>상태
    <select class="admin-input" name="http">
      <option value="">전체 상태</option>
      <option value="ok"<?= ($filters['http'] ?? '') === 'ok' ? ' selected' : '' ?>>정상</option>
      <option value="err"<?= ($filters['http'] ?? '') === 'err' ? ' selected' : '' ?>>오류 4xx·5xx</option>
    </select>
  </label>
  <?php endif; ?>
  <input type="hidden" name="bots" value="0">
  <label class="stats-check"><input type="checkbox" name="bots" value="1"<?= !empty($filters['bots']) ? ' checked' : '' ?>> 봇 포함</label>
  <input type="hidden" name="admin" value="0">
  <label class="stats-check"><input type="checkbox" name="admin" value="1"<?= !empty($filters['admin']) ? ' checked' : '' ?>> 관리자 포함</label>
  <?php if ($statsMode === 'trend'): ?>
  <input type="hidden" name="grain" value="<?= e((string) ($filters['grain'] ?? 'day')) ?>">
  <?php endif; ?>
  <button class="admin-btn admin-btn--primary" type="submit">적용</button>
</form>
<p class="stats-note">화면을 연 횟수만 집계합니다. API 호출, 이미지, 스크립트는 빼 둡니다. 봇과 관리자 접속은 체크하기 전까지 제외됩니다. 상품 번호와 QR 코드는 같은 종류의 페이지로 묶습니다.</p>
<?php if ($statsError !== ''): ?>
<div class="admin-alert is-error"><?= e($statsError) ?></div>
<?php endif; ?>
