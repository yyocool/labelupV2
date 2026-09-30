<?php
/** @var array $snapshot */
/** @var string $apiUrl */
/** @var string $resetUrl */
$progress = $snapshot['progress'] ?? ['total' => 0, 'done' => 0, 'p0_total' => 0, 'p0_done' => 0];
$pct = ($progress['total'] ?? 0) > 0
    ? (int) round(100 * ($progress['done'] ?? 0) / $progress['total'])
    : 0;
$p0pct = ($progress['p0_total'] ?? 0) > 0
    ? (int) round(100 * ($progress['p0_done'] ?? 0) / $progress['p0_total'])
    : 0;
$itemsJson = json_encode($snapshot['items'] ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
$catsJson = json_encode($snapshot['categories'] ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($pageTitle ?? '서비스 오픈 전 체크리스트') ?></title>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('launch-checklist.css') ?>">
</head>
<body class="lc-page">
<header class="lc-top">
  <a class="lc-brand" href="<?= url('/') ?>">
    <img src="<?= asset('logo.png') ?>" alt="labelup" width="120" height="32">
  </a>
  <div class="lc-top__meta">
    <span class="lc-badge">오픈 전 · 로그인 불필요</span>
    <a href="<?= url('/') ?>">홈</a>
    <a href="<?= url('about') ?>">소개</a>
  </div>
</header>

<main class="lc-main">
  <section class="lc-hero">
    <p class="lc-eyebrow">LabelUp Launch Checklist</p>
    <h1>서비스 오픈 전 체크리스트</h1>
    <p class="lc-lead">
      실제 사용자에게 서비스를 열기 전에 확인해야 할 항목입니다.
      <b>로그인 없이</b> 누구나 체크할 수 있으며, 체크 상태는 팀 전체에 공유됩니다.
    </p>
    <div class="lc-progress-grid">
      <div class="lc-progress-card">
        <div class="lc-progress-card__head">
          <strong>전체</strong>
          <span id="lc-done-label"><?= (int) $progress['done'] ?> / <?= (int) $progress['total'] ?></span>
        </div>
        <div class="lc-bar" aria-hidden="true"><i id="lc-bar-all" style="width:<?= $pct ?>%"></i></div>
        <em id="lc-pct-all"><?= $pct ?>%</em>
      </div>
      <div class="lc-progress-card lc-progress-card--p0">
        <div class="lc-progress-card__head">
          <strong>필수 P0</strong>
          <span id="lc-p0-label"><?= (int) $progress['p0_done'] ?> / <?= (int) $progress['p0_total'] ?></span>
        </div>
        <div class="lc-bar" aria-hidden="true"><i id="lc-bar-p0" style="width:<?= $p0pct ?>%"></i></div>
        <em id="lc-pct-p0"><?= $p0pct ?>%</em>
      </div>
    </div>
    <p class="lc-updated">마지막 저장 <time id="lc-updated-at"><?= e($snapshot['updated_at'] ?? '—') ?></time></p>
  </section>

  <section class="lc-toolbar">
    <label class="lc-field">
      <span>내 이름 (체크 기록용)</span>
      <input type="text" id="lc-by" maxlength="40" placeholder="예: 김라벨" autocomplete="nickname">
    </label>
    <label class="lc-field lc-field--grow">
      <span>검색</span>
      <input type="search" id="lc-q" placeholder="제목·상세·카테고리 검색">
    </label>
    <label class="lc-field">
      <span>카테고리</span>
      <select id="lc-cat">
        <option value="">전체</option>
      </select>
    </label>
    <label class="lc-field">
      <span>우선순위</span>
      <select id="lc-pri">
        <option value="">전체</option>
        <option value="P0">P0 필수</option>
        <option value="P1">P1</option>
        <option value="P2">P2</option>
      </select>
    </label>
    <label class="lc-check">
      <input type="checkbox" id="lc-open-only">
      <span>미완료만</span>
    </label>
  </section>

  <div id="lc-list" class="lc-list" aria-live="polite"></div>

  <section class="lc-foot-actions">
    <p>상세 기능 화면 검수는 <a href="<?= url('admin/qa-review') ?>">/admin/qa-review</a> 를 함께 사용하세요.</p>
    <button type="button" class="lc-reset" id="lc-reset">전체 초기화…</button>
  </section>
</main>

<script>
window.__LAUNCH_CHECKLIST__ = {
  apiUrl: <?= json_encode($apiUrl, JSON_UNESCAPED_UNICODE) ?>,
  resetUrl: <?= json_encode($resetUrl, JSON_UNESCAPED_UNICODE) ?>,
  items: <?= $itemsJson ?: '[]' ?>,
  categories: <?= $catsJson ?: '[]' ?>
};
</script>
<script src="<?= js('launch-checklist.js') ?>" defer></script>
</body>
</html>
