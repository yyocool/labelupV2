<?php
$attachDir = __DIR__ . DIRECTORY_SEPARATOR . 'attachs';
$attachments = array();
if (is_dir($attachDir)) {
    foreach (scandir($attachDir) as $file) {
        if ($file === '.' || $file === '..' || $file[0] === '.') {
            continue;
        }
        if (strcasecmp($file, 'README.md') === 0) {
            continue;
        }
        $path = $attachDir . DIRECTORY_SEPARATOR . $file;
        if (!is_file($path)) {
            continue;
        }
        $attachments[] = array(
            'name' => $file,
            'size' => filesize($path),
            'mtime' => filemtime($path),
        );
    }
    usort($attachments, function ($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
}

function meeting_h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function meeting_fmt_bytes($bytes)
{
    $bytes = (int) $bytes;
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / 1048576, 1) . ' MB';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Label-UP · 4회차 회의</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Noto+Sans+KR:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --ink: #0c1f24;
      --teal: #0d7377;
      --teal-deep: #084c4f;
      --mint: #14a3a8;
      --sand: #e8f0f0;
      --cream: #f4f7f7;
      --amber: #c4782a;
      --amber-soft: #f3e6d4;
      --ok: #1a7a4c;
      --ok-bg: #e5f5ec;
      --run: #0d7377;
      --run-bg: #dff3f4;
      --wait: #8a6a2a;
      --wait-bg: #f7efd9;
      --done: #4a5560;
      --done-bg: #e8ecf0;
      --white: #fff;
      --muted: #5a6b70;
      --line: rgba(12, 31, 36, 0.08);
      --slide-w: min(1280px, 100vw);
      --slide-h: min(720px, 100vh);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
      height: 100%;
      background: #061316;
      color: var(--ink);
      font-family: "Noto Sans KR", "Outfit", sans-serif;
      overflow: hidden;
    }

    .deck {
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      position: relative;
      background:
        radial-gradient(ellipse 80% 60% at 10% 20%, rgba(13, 115, 119, 0.35), transparent 55%),
        radial-gradient(ellipse 60% 50% at 90% 80%, rgba(196, 120, 42, 0.18), transparent 50%),
        #061316;
    }

    .stage {
      width: var(--slide-w);
      height: var(--slide-h);
      position: relative;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 30px 80px rgba(0,0,0,.45);
    }

    .slide {
      position: absolute;
      inset: 0;
      padding: 48px 56px 56px;
      display: none;
      flex-direction: column;
      background: var(--cream);
      opacity: 0;
      transform: translateX(28px);
      transition: opacity .35s ease, transform .35s ease;
    }
    .slide.active {
      display: flex;
      opacity: 1;
      transform: translateX(0);
      z-index: 1;
      animation: slideIn .4s ease;
    }
    @keyframes slideIn {
      from { opacity: 0; transform: translateX(36px); }
      to { opacity: 1; transform: translateX(0); }
    }

    .slide.cover {
      background:
        linear-gradient(145deg, rgba(8,76,79,.92) 0%, rgba(13,115,119,.85) 48%, rgba(8,50,52,.95) 100%),
        url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
      color: var(--white);
      justify-content: flex-end;
      padding-bottom: 72px;
    }
    .slide.dark {
      background: linear-gradient(160deg, #0a2a2c 0%, #0d3d40 55%, #123a3c 100%);
      color: var(--white);
    }

    .eyebrow {
      font-family: Outfit, sans-serif;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: .14em;
      text-transform: uppercase;
      color: var(--teal);
      margin-bottom: 12px;
    }
    .cover .eyebrow, .dark .eyebrow { color: rgba(255,255,255,.7); }

    h1 {
      font-family: Outfit, sans-serif;
      font-weight: 800;
      font-size: clamp(36px, 4.2vw, 56px);
      line-height: 1.15;
      letter-spacing: -0.02em;
    }
    h2 {
      font-family: Outfit, sans-serif;
      font-weight: 700;
      font-size: clamp(26px, 2.8vw, 36px);
      letter-spacing: -0.02em;
      margin-bottom: 8px;
    }
    h3 {
      font-family: Outfit, sans-serif;
      font-weight: 700;
      font-size: 18px;
      margin-bottom: 8px;
    }
    .lede {
      font-size: 17px;
      color: var(--muted);
      max-width: 720px;
      line-height: 1.55;
      margin-top: 8px;
      margin-bottom: 28px;
    }
    .cover .lede, .dark .lede { color: rgba(255,255,255,.78); }

    .slide-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 0;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 999px;
      white-space: nowrap;
    }
    .badge::before {
      content: "";
      width: 6px; height: 6px;
      border-radius: 50%;
      background: currentColor;
    }
    .badge-ok { background: var(--ok-bg); color: var(--ok); }
    .badge-run { background: var(--run-bg); color: var(--run); }
    .badge-wait { background: var(--wait-bg); color: var(--wait); }
    .badge-done { background: var(--done-bg); color: var(--done); }
    .dark .badge-run { background: rgba(20,163,168,.25); color: #7ee0e4; }
    .dark .badge-ok { background: rgba(26,122,76,.35); color: #8fe0b0; }
    .dark .badge-wait { background: rgba(196,120,42,.3); color: #f0c48a; }
    .dark .badge-done { background: rgba(255,255,255,.12); color: rgba(255,255,255,.75); }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
    .grid-ai { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    .card {
      background: var(--white);
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 18px 20px;
      transition: transform .25s ease, box-shadow .25s ease;
    }
    .slide.active .card { animation: cardUp .5s ease both; }
    .slide.active .card:nth-child(1) { animation-delay: .05s; }
    .slide.active .card:nth-child(2) { animation-delay: .1s; }
    .slide.active .card:nth-child(3) { animation-delay: .15s; }
    .slide.active .card:nth-child(4) { animation-delay: .2s; }
    .slide.active .card:nth-child(5) { animation-delay: .25s; }
    .slide.active .card:nth-child(6) { animation-delay: .3s; }
    @keyframes cardUp {
      from { opacity: 0; transform: translateY(14px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .card:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(12,31,36,.08); }
    .dark .card {
      background: rgba(255,255,255,.06);
      border-color: rgba(255,255,255,.1);
      color: var(--white);
    }
    .card .meta { font-size: 12px; color: var(--muted); margin-top: 6px; line-height: 1.45; }
    .dark .card .meta { color: rgba(255,255,255,.65); }
    .card .title-row {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 6px;
    }
    .card strong { font-size: 15px; font-weight: 700; }

    .agenda-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .agenda-list li {
      display: grid;
      grid-template-columns: 48px 1fr auto;
      align-items: center;
      gap: 14px;
      background: var(--white);
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 12px 16px;
    }
    .agenda-list .num {
      font-family: Outfit, sans-serif;
      font-weight: 800;
      font-size: 18px;
      color: var(--teal);
    }
    .agenda-list .label { font-weight: 600; font-size: 15px; }
    .agenda-list .note { font-size: 13px; color: var(--muted); }

    .prep-block {
      margin-top: 14px;
      padding: 14px 16px;
      background: var(--amber-soft);
      border: 1px solid rgba(196, 120, 42, 0.28);
      border-radius: 14px;
    }
    .prep-block h3 {
      margin: 0 0 10px;
      font-size: 14px;
      font-weight: 700;
      color: var(--amber);
      letter-spacing: -0.01em;
    }
    .prep-list {
      list-style: none;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px 18px;
    }
    .prep-list li {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 10px;
      align-items: baseline;
      font-size: 13px;
      line-height: 1.35;
    }
    .prep-list .prep-label { font-weight: 600; color: var(--ink); }
    .prep-list .prep-due {
      font-family: Outfit, sans-serif;
      font-size: 12px;
      font-weight: 700;
      color: var(--amber);
      white-space: nowrap;
    }
    .prep-list .prep-note {
      grid-column: 1 / -1;
      font-size: 11px;
      color: var(--muted);
      margin-top: -2px;
    }

    .phase-panel {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
      flex: 1;
    }
    .phase {
      border-radius: 16px;
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .phase-1 {
      background: linear-gradient(160deg, #0d7377, #0a5558);
      color: #fff;
    }
    .phase-2 {
      background: linear-gradient(160deg, #c4782a, #9a5a18);
      color: #fff;
    }
    .phase .period {
      font-family: Outfit, sans-serif;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: .08em;
      opacity: .85;
    }
    .phase h3 { font-size: 24px; margin: 0; }
    .phase ul {
      list-style: none;
      margin-top: 8px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .phase li {
      font-size: 14px;
      line-height: 1.4;
      padding-left: 14px;
      position: relative;
      opacity: .95;
    }
    .phase li::before {
      content: "";
      position: absolute;
      left: 0; top: 7px;
      width: 6px; height: 6px;
      border-radius: 50%;
      background: rgba(255,255,255,.85);
    }

    .wave-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
      margin-top: 8px;
    }
    .wave {
      background: var(--white);
      border-radius: 14px;
      padding: 18px;
      border: 1px solid var(--line);
    }
    .wave .w-tag {
      font-family: Outfit, sans-serif;
      font-size: 12px;
      font-weight: 700;
      color: var(--amber);
      letter-spacing: .06em;
      margin-bottom: 6px;
    }
    .wave h3 { font-size: 16px; }
    .wave p { font-size: 13px; color: var(--muted); line-height: 1.5; margin-top: 6px; }

    .ai-card {
      display: grid;
      grid-template-columns: 52px 1fr;
      gap: 12px;
      align-items: start;
      background: var(--white);
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 16px 18px;
    }
    .ai-icon {
      width: 44px; height: 44px;
      border-radius: 12px;
      background: var(--sand);
      color: var(--teal-deep);
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: Outfit, sans-serif;
      font-weight: 800;
      font-size: 14px;
    }
    .ai-card h3 { font-size: 15px; margin-bottom: 4px; }
    .ai-card p { font-size: 13px; color: var(--muted); line-height: 1.45; }

    .pill-row { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
    .pill {
      font-size: 13px;
      font-weight: 600;
      padding: 8px 14px;
      border-radius: 999px;
      background: var(--sand);
      color: var(--teal-deep);
    }
    .cover .pill { background: rgba(255,255,255,.15); color: #fff; }

    .highlight-box {
      margin-top: auto;
      background: var(--amber-soft);
      border-left: 4px solid var(--amber);
      border-radius: 0 12px 12px 0;
      padding: 16px 20px;
      font-size: 15px;
      font-weight: 600;
      color: var(--ink);
    }
    .dark .highlight-box {
      background: rgba(196,120,42,.2);
      color: #fff;
      border-left-color: #e0a45a;
    }

    .two-col {
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 24px;
      flex: 1;
      align-items: stretch;
    }
    .stat-big {
      font-family: Outfit, sans-serif;
      font-size: 42px;
      font-weight: 800;
      color: var(--teal);
      letter-spacing: -0.03em;
      line-height: 1;
    }
    .dark .stat-big { color: #7ee0e4; }

    .checklist {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .checklist li {
      display: flex;
      gap: 12px;
      align-items: flex-start;
      background: var(--white);
      border-radius: 12px;
      padding: 14px 16px;
      border: 1px solid var(--line);
      font-size: 14px;
      line-height: 1.45;
    }
    .checklist .mark {
      font-family: Outfit, sans-serif;
      font-weight: 800;
      color: var(--teal);
      min-width: 22px;
    }

    .footer-bar {
      position: absolute;
      left: 56px; right: 56px; bottom: 22px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 12px;
      color: var(--muted);
      pointer-events: none;
    }
    .cover .footer-bar, .dark .footer-bar { color: rgba(255,255,255,.5); }
    .brand-mark {
      font-family: Outfit, sans-serif;
      font-weight: 700;
      letter-spacing: .04em;
    }

    .controls {
      position: absolute;
      bottom: 18px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      align-items: center;
      gap: 14px;
      z-index: 10;
      color: rgba(255,255,255,.85);
      font-family: Outfit, sans-serif;
      font-size: 13px;
    }
    .controls button {
      width: 40px; height: 40px;
      border: 1px solid rgba(255,255,255,.2);
      background: rgba(255,255,255,.08);
      color: #fff;
      border-radius: 10px;
      cursor: pointer;
      font-size: 18px;
      backdrop-filter: blur(8px);
      transition: background .2s;
    }
    .controls button:hover { background: rgba(255,255,255,.2); }
    .progress {
      width: min(320px, 40vw);
      height: 3px;
      background: rgba(255,255,255,.15);
      border-radius: 999px;
      overflow: hidden;
    }
    .progress > span {
      display: block;
      height: 100%;
      background: linear-gradient(90deg, #14a3a8, #e0a45a);
      width: 0%;
      transition: width .3s ease;
    }
    .hint {
      position: absolute;
      top: 16px;
      right: 24px;
      font-size: 12px;
      color: rgba(255,255,255,.45);
      font-family: Outfit, sans-serif;
      z-index: 10;
    }

    .axis {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 20px 0 8px;
    }
    .axis-box {
      flex: 1;
      background: var(--white);
      border-radius: 14px;
      padding: 20px;
      border: 1px solid var(--line);
      text-align: center;
    }
    .axis-box h3 { color: var(--teal); margin-bottom: 4px; }
    .axis-box p { font-size: 13px; color: var(--muted); }
    .axis-arrow {
      font-family: Outfit, sans-serif;
      font-weight: 800;
      color: var(--amber);
      font-size: 22px;
    }

    .attach-toolbar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 12px 16px;
      margin-bottom: 16px;
    }
    .attach-toolbar label {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      color: var(--muted);
      cursor: pointer;
      user-select: none;
    }
    .attach-toolbar input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--teal);
    }
    .attach-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-left: auto;
    }
    .attach-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 10px 16px;
      border-radius: 10px;
      border: 1px solid var(--line);
      background: var(--white);
      color: var(--teal-deep);
      font-family: inherit;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: background .15s, border-color .15s;
    }
    .attach-btn:hover {
      border-color: var(--teal);
      background: var(--sand);
    }
    .attach-btn.primary {
      background: var(--teal);
      border-color: var(--teal);
      color: #fff;
    }
    .attach-btn.primary:hover {
      background: var(--teal-deep);
      border-color: var(--teal-deep);
    }
    .attach-btn:disabled {
      opacity: .45;
      cursor: not-allowed;
    }
    .attach-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 8px;
      max-height: 420px;
      overflow: auto;
      padding-right: 4px;
    }
    .attach-list li {
      display: grid;
      grid-template-columns: 28px 1fr auto auto;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      background: var(--white);
      border: 1px solid var(--line);
      border-radius: 12px;
    }
    .attach-list li:hover {
      border-color: rgba(13, 115, 119, 0.35);
    }
    .attach-list input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--teal);
    }
    .attach-name {
      font-weight: 600;
      font-size: 15px;
      word-break: break-all;
    }
    .attach-meta {
      font-size: 12px;
      color: var(--muted);
      font-family: Outfit, sans-serif;
      white-space: nowrap;
    }
    .attach-empty {
      padding: 28px;
      text-align: center;
      color: var(--muted);
      background: var(--sand);
      border-radius: 14px;
    }

    @media (max-width: 900px) {
      .slide { padding: 28px 24px 48px; }
      .grid-2, .grid-3, .grid-4, .grid-ai, .phase-panel, .wave-row, .two-col {
        grid-template-columns: 1fr;
      }
      .agenda-list li { grid-template-columns: 36px 1fr; }
      .agenda-list li .badge { display: none; }
      .prep-list { grid-template-columns: 1fr; }
      .hint { display: none; }
    }
  </style>
</head>
<body>
  <div class="deck">
    <p class="hint">← → · Space · F 전체화면</p>
    <div class="stage" id="stage">

      <!-- 01 Cover -->
      <section class="slide cover active" data-title="표지">
        <div class="eyebrow">Label-UP Project Meeting · Round 04</div>
        <h1>AI 라벨 플랫폼<br>4회차 회의</h1>
        <p class="lede">진행 현황 공유 · 개발범위 · 대외 기능 · 특허 · 규격·쇼핑몰·호환 과제</p>
        <div class="pill-row">
          <span class="pill">가감 코리아 × Label-UP</span>
          <span class="pill">2026</span>
        </div>
        <div class="footer-bar">
          <span class="brand-mark">LABEL-UP</span>
          <span>회의 자료 · 04회차</span>
        </div>
      </section>

      <!-- 02 Agenda -->
      <section class="slide" data-title="안건">
        <div class="eyebrow">Agenda</div>
        <h2>오늘의 안건</h2>
        <p class="lede">현재 진행 상황을 공유한 뒤, 항목별로 상태와 다음 액션을 확인합니다.</p>
        <div class="slide-body">
          <ol class="agenda-list">
            <li><span class="num">01</span><div><div class="label">가감 코리아 연혁 · 구성원 프로필</div><div class="note">별도 자료</div></div><span class="badge badge-done">제공 완료</span></li>
            <li><span class="num">02</span><div><div class="label">개발범위 설정 (1차 · 고도화) / AI 라벨 플랫폼 주요 기능</div><div class="note">대외 홍보용 정리 포함</div></div><span class="badge badge-run">오늘 공유</span></li>
            <li><span class="num">03</span><div><div class="label">핵심 기능 기술 · 특허 진행</div><div class="note">변리사 참석 일정</div></div><span class="badge badge-wait">8/5 확정</span></li>
            <li><span class="num">04</span><div><div class="label">라벨 규격 상세 스펙 · DB화</div></div><span class="badge badge-run">진행</span></li>
            <li><span class="num">05</span><div><div class="label">쇼핑몰 오픈 항목별 일정</div><div class="note">프로젝트 대시보드 참고</div></div><span class="badge badge-run">진행</span></li>
            <li><span class="num">06</span><div><div class="label">규격별 테스트용 샘플 확보</div></div><span class="badge badge-run">진행중</span></li>
            <li><span class="num">07</span><div><div class="label">주요 경쟁사 호환코드 정리 · DB 시스템</div></div><span class="badge badge-run">개발중</span></li>
          </ol>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차</span></div>
      </section>

      <!-- 03 Status -->
      <section class="slide" data-title="진행 상황">
        <div class="eyebrow">Status</div>
        <h2>현재 진행 상황</h2>
        <p class="lede">개발·설계가 병렬로 진행 중입니다. 오늘 회의에서 병목과 선행 조건을 맞춥니다.</p>
        <div class="slide-body">
          <div class="grid-2">
            <div class="card">
              <div class="title-row"><strong>폼텍 파일 분석 · 변환 기술</strong><span class="badge badge-run">진행중</span></div>
              <p class="meta">DGZ/DGF 분석 및 객체 변환 기술 개발 착수 · 편집기 반영 경로 구축</p>
            </div>
            <div class="card">
              <div class="title-row"><strong>라벨 규격관리 모듈</strong><span class="badge badge-run">진행중</span></div>
              <p class="meta">제조사·치수·호환코드 기반 규격 마스터 · 검색/관리 UI</p>
            </div>
            <div class="card">
              <div class="title-row"><strong>DB 설계</strong><span class="badge badge-run">착수</span></div>
              <p class="meta">상품·규격·호환·주문 등 핵심 스키마 설계 착수</p>
            </div>
            <div class="card">
              <div class="title-row"><strong>쇼핑몰 주요 모듈</strong><span class="badge badge-run">진행중</span></div>
              <p class="meta">카탈로그 · 장바구니 · 주문 등 오픈 핵심 모듈 개발</p>
            </div>
          </div>
          <div class="highlight-box" style="margin-top:18px">
            포인트: 규격 스펙 정리 → DB화 → 호환코드·샘플 검증이 쇼핑몰·편집기 오픈의 공통 선행 조건입니다.
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차</span></div>
      </section>

      <!-- 04 Item 1 -->
      <section class="slide dark" data-title="가감 코리아">
        <div class="eyebrow">Agenda 01</div>
        <h2>가감 코리아 연혁 · 구성원 프로필</h2>
        <p class="lede">회사 소개·조직 자료는 별도 문서로 제공이 완료되었습니다.</p>
        <div class="slide-body" style="justify-content:center">
          <div class="grid-2">
            <div class="card">
              <div class="title-row"><strong>연혁 · 회사 개요</strong><span class="badge badge-done">제공 완료</span></div>
              <p class="meta">별도 배포 자료를 참고해 주세요. 본 슬라이드에서는 요지만 확인합니다.</p>
            </div>
            <div class="card">
              <div class="title-row"><strong>구성원 프로필</strong><span class="badge badge-done">제공 완료</span></div>
              <p class="meta">역할·담당 영역은 제공 자료 기준으로 공유되었습니다.</p>
            </div>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차 · 안건 1</span></div>
      </section>

      <!-- 05 Scope overview -->
      <section class="slide" data-title="개발범위">
        <div class="eyebrow">Agenda 02 · Scope</div>
        <h2>개발범위 설정</h2>
        <p class="lede">1차 상용과 고도화를 분리해, MVP 오픈과 확장 로드맵을 명확히 합니다.</p>
        <div class="slide-body">
          <div class="phase-panel">
            <div class="phase phase-1">
              <div class="period">2026.07 — 2026.10</div>
              <h3>1차 구축</h3>
              <p style="opacity:.9;font-size:14px;line-height:1.45">라벨편집 + 쇼핑몰 핵심 End-to-End · MVP 상용(결제·주문·기본 출력)</p>
              <ul>
                <li>편집기 코어 · 규격 검색 · 미리보기/출력</li>
                <li>쇼핑몰 홈·상품·장바구니·결제·주문</li>
                <li>AI 심플 모드 · AI 전문가 모드 · 폼텍 가져오기</li>
                <li>데이터 연동(엑셀/CSV) · 라벨복사</li>
                <li>오픈마켓 상품데이터 자동 연동: 스마트스토어</li>
              </ul>
            </div>
            <div class="phase phase-2">
              <div class="period">2026.11 — 2027.06</div>
              <h3>고도화</h3>
              <p style="opacity:.9;font-size:14px;line-height:1.45">타사 포맷 완전 반입 · AI·상품 데이터 확장 · 운영 완성</p>
              <ul>
                <li>AnyLabel · iLabel 임포트</li>
                <li>AI 일괄·다국어 확장</li>
                <li>오픈마켓 상품데이터 자동 연동: 쿠팡 · 11번가</li>
                <li>출력↔주문 연계 · 맞춤제작</li>
              </ul>
            </div>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차 · 안건 2</span></div>
      </section>

      <!-- 06 Phase 1 detail -->
      <section class="slide" data-title="1차 구축">
        <div class="eyebrow">Agenda 02 · 1차 구축</div>
        <h2>1차 구축 — 네 가지 축</h2>
        <p class="lede">기능개발 · 타사 포맷 · 데이터 · 기타(인증·결제·운영)</p>
        <div class="slide-body">
          <div class="grid-2">
            <div class="card">
              <strong>기능개발</strong>
              <p class="meta">라벨편집(디자인~출력) · 쇼핑몰·주문 · AI 심플 · AI 전문가 모드 · 라벨복사·데이터 연동·다브랜드 규격</p>
            </div>
            <div class="card">
              <strong>타사 포맷</strong>
              <p class="meta">폼텍 DGZ/DGF 분석·부분 객체화 · 아이라벨 데이터 반입 · 애니라벨 호환 규격</p>
            </div>
            <div class="card">
              <strong>데이터</strong>
              <p class="meta">런칭 SKU 상품 상세 · 규격 마스터 · 오픈마켓 자동 연동(스마트스토어) · 관리자 CRUD</p>
            </div>
            <div class="card">
              <strong>기타</strong>
              <p class="meta">회원·권한 · 약관·PG · 공지/FAQ · 보안·배포 기본</p>
            </div>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차 · 안건 2</span></div>
      </section>

      <!-- 07 Enhance waves -->
      <section class="slide" data-title="고도화">
        <div class="eyebrow">Agenda 02 · 고도화</div>
        <h2>고도화 Wave 계획</h2>
        <p class="lede">2026.11 ~ 2027.06 · 단계별 마일스톤으로 확장</p>
        <div class="slide-body">
          <div class="wave-row">
            <div class="wave">
              <div class="w-tag">WAVE 1 · 26.11–27.01</div>
              <h3>출력 연계 · AI 확장</h3>
              <p>출력→장바구니/인쇄의뢰 연계 · AI 일괄·다국어 확장</p>
            </div>
            <div class="wave">
              <div class="w-tag">WAVE 2 · 27.02–27.04</div>
              <h3>AnyLabel · iLabel · 대량</h3>
              <p>AnyLabel · iLabel 파일 임포트 · 대량 매핑·배치 · 호환 매트릭스</p>
            </div>
            <div class="wave">
              <div class="w-tag">WAVE 3 · 27.05–27.06</div>
              <h3>오픈마켓 · 맞춤</h3>
              <p>쿠팡·11번가 상품데이터 연동 · 맞춤제작</p>
            </div>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차 · 안건 2</span></div>
      </section>

      <!-- 08 Platform PR -->
      <section class="slide dark" data-title="주요 기능">
        <div class="eyebrow">Agenda 02 · 대외 홍보</div>
        <h2>AI 라벨 플랫폼 주요 기능</h2>
        <p class="lede">한 줄: AI로 라벨을 만들고, 용지를 사고, 인쇄까지 한 곳에서.</p>
        <div class="slide-body">
          <div class="axis">
            <div class="axis-box" style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.12)">
              <h3 style="color:#7ee0e4">라벨편집</h3>
              <p style="color:rgba(255,255,255,.7)">디자인 · AI · 데이터 · 출력</p>
            </div>
            <div class="axis-arrow">↔</div>
            <div class="axis-box" style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.12)">
              <h3 style="color:#f0c48a">쇼핑몰</h3>
              <p style="color:rgba(255,255,255,.7)">용지 · 소모품 · 인쇄의뢰 · 주문</p>
            </div>
          </div>
          <div class="grid-3" style="margin-top:8px">
            <div class="card"><strong>규격에 맞는 편집</strong><p class="meta">다브랜드 규격 · 바코드 · 시트 미리보기</p></div>
            <div class="card"><strong>Vertical AI</strong><p class="meta">라벨 GPT — 업무 맥락에 맞춘 생성·비서</p></div>
            <div class="card"><strong>원스톱 LTV</strong><p class="meta">편집 ↔ 구매 ↔ 인쇄의뢰 연결</p></div>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차 · 안건 2</span></div>
      </section>

      <!-- 09 AI features -->
      <section class="slide" data-title="라벨 GPT">
        <div class="eyebrow">Agenda 02 · 라벨 GPT</div>
        <h2>AI 핵심 기능 (대외용)</h2>
        <p class="lede">범용 디자인 AI가 아닌, 라벨 업무 전체를 돕는 Vertical AI</p>
        <div class="slide-body">
          <div class="grid-ai">
            <div class="ai-card">
              <div class="ai-icon">01</div>
              <div>
                <h3>AI 디자인 생성</h3>
                <p>짧은 지시문·상품명·이미지로 시안 후보 생성 → 편집기에서 바로 수정</p>
              </div>
            </div>
            <div class="ai-card">
              <div class="ai-icon">02</div>
              <div>
                <h3>AI 라벨 비서</h3>
                <p>규격 추천 · 표시 가이드 · 바코드·레이아웃 조언 · 인쇄 전 체크리스트</p>
              </div>
            </div>
            <div class="ai-card">
              <div class="ai-icon">03</div>
              <div>
                <h3>엑셀·파일 자동 라벨</h3>
                <p>CSV/엑셀 → 일괄 라벨 생성 (대량·반복 업무 자동화)</p>
              </div>
            </div>
            <div class="ai-card">
              <div class="ai-icon">04</div>
              <div>
                <h3>사진 → 라벨</h3>
                <p>제품 사진 한 장으로 초안 완성</p>
              </div>
            </div>
            <div class="ai-card">
              <div class="ai-icon">05</div>
              <div>
                <h3>AI OCR</h3>
                <p>기존 라벨 스캔 → 편집·재인쇄</p>
              </div>
            </div>
            <div class="ai-card">
              <div class="ai-icon">06</div>
              <div>
                <h3>AI 다국어</h3>
                <p>수출·현지화 라벨 번역·재배치</p>
              </div>
            </div>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차 · 안건 2</span></div>
      </section>

      <!-- 10 Patent -->
      <section class="slide" data-title="특허">
        <div class="eyebrow">Agenda 03</div>
        <h2>핵심 기능 · 기술 특허 진행</h2>
        <p class="lede">핵심 AI·변환 기술에 대한 특허 진행 일정 확인 후 변리사 미팅을 진행합니다.</p>
        <div class="slide-body">
          <div class="two-col">
            <div>
              <ul class="checklist">
                <li><span class="mark">•</span><div>특허 후보: AI 라벨 생성·업무 자동화, 타사 포맷 분석·변환 등 <strong>핵심 기술</strong> 범위 확정 필요</div></li>
                <li><span class="mark">•</span><div>사전 정리: 차별 포인트 · 선행기술 이슈 · 청구 방향 초안</div></li>
                <li><span class="mark">•</span><div>미팅 목적: 출원 가능성 · 일정 · 비용·우선순위 협의</div></li>
              </ul>
            </div>
            <div class="card" style="display:flex;flex-direction:column;justify-content:center;background:linear-gradient(160deg,#0d7377,#084c4f);color:#fff;border:0">
              <div style="font-size:13px;opacity:.8;letter-spacing:.08em;font-family:Outfit,sans-serif;font-weight:600">변리사 참석 확정</div>
              <div class="stat-big" style="color:#fff;margin:12px 0 8px;font-size:36px">8월 5일 (수)</div>
              <div style="font-size:22px;font-weight:700;font-family:Outfit,sans-serif">오후 5:30</div>
              <p style="margin-top:16px;font-size:13px;opacity:.85;line-height:1.5">일정 확정 · 참석자·아젠다는 미팅 전 공유</p>
            </div>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차 · 안건 3</span></div>
      </section>

      <!-- 11 Spec -->
      <section class="slide" data-title="규격 스펙">
        <div class="eyebrow">Agenda 04</div>
        <h2>라벨 규격 상세 스펙 정리</h2>
        <p class="lede">진행 예정 상품 리스트에 스펙 파일 첨부 예정 · 정리 완료 즉시 DB화</p>
        <div class="slide-body">
          <div class="grid-3">
            <div class="card">
              <div class="title-row"><strong>스펙 정리</strong><span class="badge badge-run">진행</span></div>
              <p class="meta">용지크기 · 라벨 W×H · 여백 · 행×열 · 재질 · 접착 · 인쇄방식</p>
            </div>
            <div class="card">
              <div class="title-row"><strong>상품 리스트 첨부</strong><span class="badge badge-wait">예정</span></div>
              <p class="meta">진행 예정 SKU별 상세 스펙 파일을 리스트에 첨부</p>
            </div>
            <div class="card">
              <div class="title-row"><strong>DB화</strong><span class="badge badge-run">착수 연동</span></div>
              <p class="meta">정리 완료분부터 규격 마스터·상품-규격 연결로 적재</p>
            </div>
          </div>
          <div class="highlight-box" style="margin-top:20px">
            다음 액션: 스펙 템플릿 확정 → 상품 리스트 첨부 → DB 적재 배치/관리 화면 연동
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차 · 안건 4</span></div>
      </section>

      <!-- 12 Shop schedule -->
      <section class="slide" data-title="쇼핑몰 일정">
        <div class="eyebrow">Agenda 05</div>
        <h2>쇼핑몰 오픈 · 항목별 일정</h2>
        <p class="lede">상세 일정은 프로젝트 사이트 대시보드를 기준으로 관리·공유합니다.</p>
        <div class="slide-body">
          <div class="grid-2">
            <div class="card">
              <strong>오픈 핵심 모듈</strong>
              <p class="meta">홈 · 카탈로그 · 상품상세 · 장바구니 · 결제 · 주문/배송 · 마이페이지 · Backoffice(상품·주문)</p>
            </div>
            <div class="card">
              <strong>일정 수립 방식</strong>
              <p class="meta">대시보드 마일스톤·진행률을 SSOT로 사용 · 회의에서 리스크·의존성만 합의</p>
            </div>
            <div class="card">
              <strong>의존 과제</strong>
              <p class="meta">규격·상품 데이터 · 호환코드 · PG/약관 · 편집기「디자인 편집」CTA 연결</p>
            </div>
            <div class="card">
              <strong>오늘 확인</strong>
              <p class="meta">대시보드상 Critical Path · 담당·마감이 비어 있는 항목 채우기</p>
            </div>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차 · 안건 5</span></div>
      </section>

      <!-- 13 Samples -->
      <section class="slide" data-title="테스트 샘플">
        <div class="eyebrow">Agenda 06</div>
        <h2>라벨 규격별 테스트용 샘플 확보</h2>
        <div class="slide-body" style="justify-content:center;align-items:center">
          <span class="badge badge-run" style="font-size:22px;padding:12px 28px">진행중</span>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차 · 안건 6</span></div>
      </section>

      <!-- 14 Compat codes -->
      <section class="slide" data-title="호환코드">
        <div class="eyebrow">Agenda 07</div>
        <h2>주요 경쟁사 호환코드 정리</h2>
        <p class="lede">폼텍 · 아이라벨 · 애니라벨 등 호환 코드를 DB화하기 위한 시스템을 개발 중입니다.</p>
        <div class="slide-body">
          <div class="grid-2">
            <div class="card">
              <div class="title-row"><strong>호환코드 데이터</strong><span class="badge badge-run">정리·연동</span></div>
              <p class="meta">경쟁사 코드 ↔ 자사 규격·치수 매핑 · 검색·필터·상품 상세 배지</p>
            </div>
            <div class="card">
              <div class="title-row"><strong>DB화 시스템</strong><span class="badge badge-run">개발중</span></div>
              <p class="meta">규격관리 모듈과 연동 · 일괄 등록·검증 · 관리자 CRUD</p>
            </div>
          </div>
          <div class="highlight-box" style="margin-top:20px">
            목표: 사용자가 「쓰던 규격 코드」로 찾아도 Label-UP 상품·편집기로 바로 연결
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차 · 안건 7</span></div>
      </section>

      <!-- 15 Actions -->
      <section class="slide" data-title="액션">
        <div class="eyebrow">Next Actions</div>
        <h2>회의 후 액션 아이템</h2>
        <p class="lede">오늘 합의할 담당·마감을 이 슬라이드에 메모해 주세요.</p>
        <div class="slide-body">
          <ol class="agenda-list">
            <li><span class="num">A1</span><div><div class="label">8/5 변리사 미팅 아젠다·참석자 확정</div><div class="note">특허 후보 기술 1페이지 요약</div></div><span class="badge badge-wait">8/5</span></li>
            <li><span class="num">A2</span><div><div class="label">규격 스펙 → 상품 리스트 첨부 → DB 적재</div><div class="note">완료분 우선 반영</div></div><span class="badge badge-run">진행</span></li>
            <li><span class="num">A3</span><div><div class="label">쇼핑몰 오픈 Critical Path 대시보드 업데이트</div></div><span class="badge badge-run">진행</span></li>
            <li><span class="num">A4</span><div><div class="label">테스트 샘플 확보</div></div><span class="badge badge-run">진행중</span></li>
            <li><span class="num">A5</span><div><div class="label">호환코드 DB 시스템 · 규격관리 연동 일정</div></div><span class="badge badge-run">개발중</span></li>
          </ol>
          <div class="prep-block">
            <h3>사전 준비사항</h3>
            <ul class="prep-list">
              <li>
                <span class="prep-label">AI (Chat GPT) Key 발급</span>
                <span class="prep-due">즉시</span>
                <span class="prep-note">발급 후 제공 부탁드립니다</span>
              </li>
              <li>
                <span class="prep-label">PG 선정 &amp; 계약</span>
                <span class="prep-due">ASAP</span>
              </li>
              <li>
                <span class="prep-label">소셜로그인 KEY 발급</span>
                <span class="prep-due">ASAP</span>
              </li>
              <li>
                <span class="prep-label">알림톡 + SMS 발송 대행사 계약</span>
                <span class="prep-due">8월 중순</span>
              </li>
              <li>
                <span class="prep-label">운영서버 (AWS) 계약</span>
                <span class="prep-due">9월 중순</span>
              </li>
              <li>
                <span class="prep-label">SSL 구매, 도메인 구매</span>
                <span class="prep-due">ASAP</span>
              </li>
            </ul>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차</span></div>
      </section>

      <!-- 16 Close -->
      <section class="slide cover" data-title="마무리">
        <div class="eyebrow">Thank you</div>
        <h1>다음 일정</h1>
        <p class="lede" style="max-width:560px">
          변리사 미팅 — <strong style="color:#fff">2026.08.05 (수) 17:30</strong><br>
          상세 일정·진행률 — 프로젝트 사이트 대시보드
        </p>
        <div class="pill-row">
          <span class="pill">Label-UP</span>
          <span class="pill">4회차 회의 자료</span>
        </div>
        <div class="footer-bar">
          <span class="brand-mark">LABEL-UP</span>
          <span>Q &amp; A</span>
        </div>
      </section>

<?php if (!empty($attachments)): ?>
      <!-- 17 Attachments -->
      <section class="slide" data-title="첨부파일">
        <div class="eyebrow">Attachments</div>
        <h2>첨부파일 다운로드</h2>
        <p class="lede">체크한 파일을 선택 다운로드하거나, 개별 파일을 받을 수 있습니다. (<?= count($attachments) ?>개)</p>
        <div class="slide-body">
          <form id="attachForm" method="post" action="download.php">
            <div class="attach-toolbar">
              <label>
                <input type="checkbox" id="attachSelectAll" checked>
                전체 선택
              </label>
              <div class="attach-actions">
                <button type="submit" class="attach-btn primary" id="attachDownloadSelected">선택 다운로드 (ZIP)</button>
              </div>
            </div>
            <ul class="attach-list">
<?php foreach ($attachments as $file): ?>
              <li>
                <input type="checkbox" name="names[]" value="<?= meeting_h($file['name']) ?>" class="attach-item" checked>
                <div>
                  <div class="attach-name"><?= meeting_h($file['name']) ?></div>
                </div>
                <span class="attach-meta"><?= meeting_h(meeting_fmt_bytes($file['size'])) ?></span>
                <a class="attach-btn" href="download.php?name=<?= rawurlencode($file['name']) ?>">다운로드</a>
              </li>
<?php endforeach; ?>
            </ul>
          </form>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>04회차 · 첨부</span></div>
      </section>
<?php endif; ?>

    </div>

    <div class="controls">
      <button type="button" id="prevBtn" aria-label="이전">‹</button>
      <div class="progress"><span id="bar"></span></div>
      <span id="counter">1 / 1</span>
      <button type="button" id="nextBtn" aria-label="다음">›</button>
    </div>
  </div>

  <script>
    (function () {
      var slides = Array.prototype.slice.call(document.querySelectorAll('.slide'));
      var idx = 0;
      var bar = document.getElementById('bar');
      var counter = document.getElementById('counter');

      function show(i) {
        if (i < 0 || i >= slides.length) return;
        slides[idx].classList.remove('active');
        idx = i;
        slides[idx].classList.add('active');
        counter.textContent = (idx + 1) + ' / ' + slides.length;
        bar.style.width = ((idx + 1) / slides.length * 100) + '%';
        document.title = 'Label-UP · 4회차 — ' + (slides[idx].getAttribute('data-title') || '');
      }

      document.getElementById('prevBtn').addEventListener('click', function () { show(idx - 1); });
      document.getElementById('nextBtn').addEventListener('click', function () { show(idx + 1); });

      document.addEventListener('keydown', function (e) {
        var tag = (e.target && e.target.tagName) ? e.target.tagName.toUpperCase() : '';
        var typing = /^(INPUT|TEXTAREA|SELECT|BUTTON|A)$/.test(tag);
        if (e.key === 'ArrowRight' || e.key === 'PageDown' || (e.key === ' ' && !typing)) {
          e.preventDefault();
          show(idx + 1);
        } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
          e.preventDefault();
          show(idx - 1);
        } else if (e.key === 'Home') {
          show(0);
        } else if (e.key === 'End') {
          show(slides.length - 1);
        } else if ((e.key === 'f' || e.key === 'F') && !typing) {
          if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(function () {});
          } else {
            document.exitFullscreen();
          }
        }
      });

      var attachForm = document.getElementById('attachForm');
      if (attachForm) {
        var selectAll = document.getElementById('attachSelectAll');
        var items = Array.prototype.slice.call(attachForm.querySelectorAll('.attach-item'));
        var downloadBtn = document.getElementById('attachDownloadSelected');

        function syncAttachUi() {
          var checked = items.filter(function (el) { return el.checked; });
          if (selectAll) {
            selectAll.checked = checked.length === items.length && items.length > 0;
            selectAll.indeterminate = checked.length > 0 && checked.length < items.length;
          }
          if (downloadBtn) {
            downloadBtn.disabled = checked.length === 0;
          }
        }

        if (selectAll) {
          selectAll.addEventListener('change', function () {
            items.forEach(function (el) { el.checked = selectAll.checked; });
            syncAttachUi();
          });
        }
        items.forEach(function (el) {
          el.addEventListener('change', syncAttachUi);
        });
        attachForm.addEventListener('submit', function (e) {
          var checked = items.filter(function (el) { return el.checked; });
          if (checked.length === 0) {
            e.preventDefault();
            return;
          }
          if (checked.length === 1) {
            e.preventDefault();
            window.location.href = 'download.php?name=' + encodeURIComponent(checked[0].value);
          }
        });
        syncAttachUi();
      }

      var touchX = null;
      document.getElementById('stage').addEventListener('touchstart', function (e) {
        touchX = e.changedTouches[0].screenX;
      }, { passive: true });
      document.getElementById('stage').addEventListener('touchend', function (e) {
        if (touchX === null) return;
        var dx = e.changedTouches[0].screenX - touchX;
        if (Math.abs(dx) > 50) show(dx < 0 ? idx + 1 : idx - 1);
        touchX = null;
      }, { passive: true });

      show(0);
    })();
  </script>
</body>
</html>
