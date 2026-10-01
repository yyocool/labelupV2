<?php
/** @var array<string, mixed> $siteMode */
$siteMode = $siteMode ?? (new \App\Services\SiteModeService())->adminPayload();
$title = (string) ($siteMode['maintenance_title'] ?? '잠시 점검 중입니다');
$message = (string) ($siteMode['maintenance_message'] ?? '더 안정적인 라벨업을 위해 시스템을 정비하고 있어요.');
$eta = trim((string) ($siteMode['maintenance_eta'] ?? ''));
$logo = asset('logo.png');
$labi = asset('labi-icon.png');
http_response_code(503);
header('Retry-After: 3600');
header('Cache-Control: no-store, no-cache, must-revalidate');
?><!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($title) ?> — 라벨업</title>
  <link rel="stylesheet" href="<?= e(css('maintenance.css')) ?>">
</head>
<body class="lu-maint">
  <div class="lu-maint__glow" aria-hidden="true"></div>
  <div class="lu-maint__grid" aria-hidden="true"></div>
  <main class="lu-maint__stage">
    <p class="lu-maint__brand">
      <?php if ($logo !== ''): ?>
        <img src="<?= e($logo) ?>" alt="라벨업" width="148" height="40">
      <?php else: ?>
        LABEL UP
      <?php endif; ?>
    </p>
    <div class="lu-maint__hero">
      <?php if ($labi !== ''): ?>
        <img class="lu-maint__labi" src="<?= e($labi) ?>" alt="" width="96" height="96">
      <?php endif; ?>
      <p class="lu-maint__eyebrow">Maintenance</p>
      <h1><?= e($title) ?></h1>
      <p class="lu-maint__lead"><?= e($message) ?></p>
      <?php if ($eta !== ''): ?>
        <p class="lu-maint__eta"><?= e($eta) ?></p>
      <?php endif; ?>
    </div>
    <p class="lu-maint__foot">관리자는 <a href="<?= e(url('admin/login')) ?>">관리자 로그인</a>으로 진입할 수 있습니다.</p>
  </main>
</body>
</html>
