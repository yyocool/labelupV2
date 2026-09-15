<?php
$targetUrl = (string) ($targetUrl ?? 'https://www.labelup.co.kr/compat-codes');
$qrImg = (string) ($qrImg ?? '');
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($pageTitle ?? '호환코드표 QR — 라벨업') ?></title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <style>
    :root{
      --bi-wine:#561D2B;
      --brand:#A61B37;
      --ink:#1A1417;
      --ink2:#5B4F53;
      --ink3:#8E8085;
      --line:#E6DFE1;
      --band:#F7F4F5;
      --sans:"Pretendard", "Noto Sans KR", system-ui, -apple-system, sans-serif;
    }
    *{box-sizing:border-box}
    body{
      margin:0;min-height:100vh;font-family:var(--sans);color:var(--ink);
      background:linear-gradient(180deg,#fff 0%,var(--band) 100%);
      display:flex;flex-direction:column;-webkit-text-size-adjust:100%;
    }
    .top{
      background:var(--bi-wine);color:#fff;
      display:flex;align-items:center;justify-content:space-between;
      gap:12px;padding:14px 18px;
    }
    .top a{color:#fff;text-decoration:none;display:inline-flex;align-items:center}
    .top img{height:24px;width:auto;filter:brightness(0) invert(1)}
    .top span{font-size:13px;opacity:.78;font-weight:600}
    main{
      flex:1;display:flex;align-items:center;justify-content:center;
      padding:28px 18px 40px;
    }
    .card{
      width:100%;max-width:380px;background:#fff;border:1px solid var(--line);
      border-radius:18px;padding:28px 22px 24px;text-align:center;
      box-shadow:0 16px 40px rgba(86,29,43,.08);
    }
    .kicker{margin:0;font-size:13px;font-weight:700;color:var(--brand);letter-spacing:.02em}
    h1{margin:8px 0 0;font-size:clamp(22px,5.5vw,28px);font-weight:900;letter-spacing:-.03em;line-height:1.25}
    .lead{margin:12px 0 0;font-size:14.5px;color:var(--ink2);line-height:1.6}
    .qr-wrap{
      margin:22px auto 0;width:min(260px,72vw);aspect-ratio:1;
      padding:12px;border-radius:16px;border:1px solid var(--line);background:#fff;
    }
    .qr-wrap img{display:block;width:100%;height:100%;object-fit:contain}
    .url{
      margin:16px 0 0;font-size:13px;font-weight:700;color:var(--brand);
      word-break:break-all;line-height:1.45;
    }
    .url a{color:inherit;text-decoration:none}
    .hint{margin:10px 0 0;font-size:13px;color:var(--ink3)}
    .actions{margin-top:18px;display:flex;flex-direction:column;gap:8px}
    .btn{
      display:inline-flex;align-items:center;justify-content:center;
      min-height:44px;border-radius:12px;padding:0 16px;font-size:14px;font-weight:750;
      text-decoration:none;border:1px solid var(--line);color:var(--ink);background:#fff;
    }
    .btn-primary{background:var(--brand);border-color:var(--brand);color:#fff}
    footer{padding:0 18px 28px;text-align:center;font-size:12px;color:var(--ink3)}
    footer a{color:inherit}
  </style>
</head>
<body>
  <header class="top">
    <a href="<?= url('/') ?>" aria-label="LABEL UP 홈">
      <img src="<?= asset('logo.png') ?>" alt="LABEL UP" width="120" height="32">
    </a>
    <span>호환코드표 QR</span>
  </header>

  <main>
    <section class="card">
      <p class="kicker">SCAN TO OPEN</p>
      <h1>호환코드표<br>바로가기</h1>
      <p class="lead">스마트폰 카메라로 QR을 스캔하면<br>타사 규격 호환코드를 바로 조회할 수 있습니다.</p>
      <div class="qr-wrap">
        <img
          src="<?= e($qrImg) ?>"
          alt="호환코드표 QR 코드"
          width="360"
          height="360"
        >
      </div>
      <p class="url"><a href="<?= e($targetUrl) ?>" target="_blank" rel="noopener"><?= e($targetUrl) ?></a></p>
      <p class="hint">로그인 없이 이용 가능 · 모바일 최적화</p>
      <div class="actions">
        <a class="btn btn-primary" href="<?= e($targetUrl) ?>">호환코드표 열기</a>
        <a class="btn" href="<?= url('compat-codes') ?>">이 사이트에서 보기</a>
      </div>
    </section>
  </main>

  <footer>
    © <?= (int) ($year ?? date('Y')) ?> LABEL UP ·
    <a href="<?= url('/') ?>">홈</a>
  </footer>
</body>
</html>
