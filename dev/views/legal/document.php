<?php
/** @var array<string, mixed> $doc */
$doc = $doc ?? [];
$title = (string) ($doc['title'] ?? '약관');
$content = (string) ($doc['content'] ?? '');
$updatedAt = (string) ($doc['updated_at'] ?? '');
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php marketing_render_head(); ?>
  <?php seo_render_head($seoPage ?? null, array_merge($seoOverride ?? [], ['fallback_title' => $pageTitle ?? $title])); ?>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('home.css') ?>">
  <link rel="stylesheet" href="<?= css('legal.css') ?>">
</head>
<body class="legal-page">
<?php marketing_render_body_start(); ?>
<header class="legal-top">
  <a class="legal-brand" href="<?= url('/') ?>">
    <img src="<?= asset('logo.png') ?>" alt="labelup" width="120" height="32">
  </a>
  <nav class="legal-nav" aria-label="약관 메뉴">
    <a href="<?= url('about') ?>">서비스 소개</a>
    <a href="<?= url('terms') ?>"<?= (($doc['doc_key'] ?? '') === 'terms') ? ' aria-current="page"' : '' ?>>이용약관</a>
    <a href="<?= url('privacy') ?>"<?= (($doc['doc_key'] ?? '') === 'privacy') ? ' aria-current="page"' : '' ?>>개인정보 처리방침</a>
    <a href="<?= url('login') ?>">로그인</a>
  </nav>
</header>

<main class="legal-main">
  <article class="legal-card">
    <h1><?= e($title) ?></h1>
    <?php if ($updatedAt !== ''): ?>
    <p class="legal-updated">최종 업데이트 <?= e($updatedAt) ?></p>
    <?php endif; ?>
    <div class="legal-body"><?= $content ?></div>
  </article>
  <?php render_site_footer(['year' => $year ?? date('Y')]); ?>
</main>
<?php marketing_render_body_end(); ?>
</body>
</html>
