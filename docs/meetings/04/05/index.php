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
  <title>Label-UP · 5회차 회의</title>
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
  
    .progress-ring {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 10px;
    }
    .progress-track {
      flex: 1;
      height: 8px;
      background: var(--sand);
      border-radius: 999px;
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #14a3a8, #0d7377);
      border-radius: 999px;
    }
    .progress-pct {
      font-family: Outfit, sans-serif;
      font-weight: 800;
      font-size: 14px;
      color: var(--teal);
      min-width: 42px;
      text-align: right;
    }
    .mode-hero {
      display: grid;
      grid-template-columns: 140px 1fr;
      gap: 18px;
      align-items: start;
      margin-bottom: 16px;
    }
    .mode-badge-big {
      background: linear-gradient(160deg, #0d7377, #084c4f);
      color: #fff;
      border-radius: 16px;
      padding: 20px 16px;
      text-align: center;
    }
    .mode-badge-big.expert {
      background: linear-gradient(160deg, #c4782a, #9a5a18);
    }
    .mode-badge-big .mode-kicker {
      font-size: 11px;
      letter-spacing: .12em;
      text-transform: uppercase;
      opacity: .8;
      margin-bottom: 6px;
    }
    .mode-badge-big .mode-name {
      font-family: Outfit, sans-serif;
      font-weight: 800;
      font-size: 22px;
      line-height: 1.2;
    }
    .mode-badge-big .mode-tag {
      margin-top: 8px;
      font-size: 12px;
      opacity: .85;
    }
    .feature-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .feature-list li {
      display: grid;
      grid-template-columns: 22px 1fr;
      gap: 10px;
      align-items: start;
      background: var(--white);
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 11px 14px;
      font-size: 14px;
      line-height: 1.45;
    }
    .feature-list .dot {
      width: 10px; height: 10px;
      border-radius: 50%;
      background: var(--teal);
      margin-top: 5px;
    }
    .feature-list .dot.amber { background: var(--amber); }
    .feature-list strong { font-weight: 700; }
    .feature-list span { color: var(--muted); font-size: 13px; }
    .issue-callout {
      margin-top: 14px;
      padding: 14px 16px;
      border-left: 4px solid var(--amber);
      background: var(--amber-soft);
      border-radius: 0 12px 12px 0;
      font-size: 14px;
      line-height: 1.5;
    }
    .patent-grid {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .patent-item {
      background: var(--white);
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 12px 14px;
    }
    .patent-item .p-num {
      font-family: Outfit, sans-serif;
      font-weight: 800;
      font-size: 12px;
      color: var(--teal);
      margin-bottom: 4px;
    }
    .patent-item strong { font-size: 15px; display: block; margin-bottom: 6px; line-height: 1.3; }
    .patent-item p { font-size: 13px; color: var(--ink); line-height: 1.5; margin: 0; }
    .patent-principle {
      margin-bottom: 10px;
      padding: 10px 14px;
      background: var(--sand);
      border-radius: 12px;
      font-size: 13px;
      line-height: 1.45;
    }
    .patent-principle strong { color: var(--teal-deep); }
    .patent-flow {
      margin-top: 6px;
      font-size: 12px;
      color: var(--teal);
      font-weight: 600;
    }
    .patent-item .p-detail {
      margin-top: 6px;
      font-size: 12px;
      color: var(--muted);
      line-height: 1.45;
    }
    .roadmap-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
      flex: 1;
    }
    .roadmap-list li {
      display: grid;
      grid-template-columns: 52px 1fr;
      gap: 14px;
      align-items: start;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 14px;
      padding: 14px 16px;
    }
    .roadmap-list .rm-no {
      font-family: Outfit, sans-serif;
      font-weight: 800;
      font-size: 22px;
      color: #7ee0e4;
      padding-top: 2px;
    }
    .roadmap-list .rm-phase {
      min-width: 0;
    }
    .roadmap-list .rm-phase .rm-concept {
      display: inline-block;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .06em;
      color: #f0c48a;
      margin-bottom: 4px;
    }
    .roadmap-list .rm-phase strong {
      display: block;
      font-size: 16px;
      margin-bottom: 2px;
    }
    .roadmap-list .rm-phase .rm-year {
      font-size: 11px;
      color: rgba(255,255,255,.55);
      font-family: Outfit, sans-serif;
      margin-bottom: 8px;
    }
    .roadmap-list .rm-items {
      list-style: none;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4px 16px;
      margin: 0;
      padding: 0;
    }
    .roadmap-list .rm-items li {
      display: block;
      background: none;
      border: none;
      border-radius: 0;
      padding: 0;
      font-size: 12.5px;
      line-height: 1.4;
      color: rgba(255,255,255,.82);
    }
    .roadmap-list .rm-items li::before {
      content: "· ";
      color: #7ee0e4;
      font-weight: 700;
    }
  </style>
</head>
<body>
  <div class="deck">
    <p class="hint">← → · Space · F 전체화면</p>
    <div class="stage" id="stage">

      <!-- 01 Cover -->
      <section class="slide cover active" data-title="표지">
        <div class="eyebrow">Label-UP Project Meeting · Round 05</div>
        <h1>AI 라벨 플랫폼<br>5회차 회의</h1>
        <p class="lede">작업 진행현황 · AI 기능(기본/전문가) · 특허 관련</p>
        <div class="pill-row">
          <span class="pill">가감 코리아 × Label-UP</span>
          <span class="pill">2026.08.05</span>
        </div>
        <div class="footer-bar">
          <span class="brand-mark">LABEL-UP</span>
          <span>회의 자료 · 05회차</span>
        </div>
      </section>

      <!-- 02 Agenda -->
      <section class="slide" data-title="안건">
        <div class="eyebrow">Agenda</div>
        <h2>오늘 안건</h2>
        <p class="lede">진행 공유 후 AI 모드 정리·특허 관련을 합의합니다.</p>
        <div class="slide-body">
          <ol class="agenda-list">
            <li><span class="num">01</span><div><div class="label">작업 진행현황</div><div class="note">폼텍 분석 · DB · AI 라이브러리 · 백오피스</div></div><span class="badge badge-run">공유</span></li>
            <li><span class="num">02</span><div><div class="label">AI 기능 정리</div><div class="note">기본모드 · 전문가모드</div></div><span class="badge badge-run">오늘 정리</span></li>
            <li><span class="num">03</span><div><div class="label">특허 관련</div><div class="note">AI 라벨 특화 3건</div></div><span class="badge badge-wait">변리사</span></li>
          </ol>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>05회차</span></div>
      </section>

      <!-- 03 Progress -->
      <section class="slide" data-title="진행현황">
        <div class="eyebrow">Agenda 01</div>
        <h2>작업 진행현황</h2>
        <p class="lede">핵심 기술·인프라가 병렬로 진행 중입니다. 병목은 타사포맷 분석 깊이입니다.</p>
        <div class="slide-body">
          <div class="grid-2">
            <div class="card">
              <div class="title-row"><strong>폼텍 포맷 분석</strong><span class="badge badge-run">30%</span></div>
              <p class="meta">DGZ/DGF 구조·객체 경우의 수를 개별 검증하며 진행. 부분 객체화 단계.</p>
              <div class="progress-ring">
                <div class="progress-track"><div class="progress-fill" style="width:30%"></div></div>
                <span class="progress-pct">30%</span>
              </div>
            </div>
            <div class="card">
              <div class="title-row"><strong>DB 설계</strong><span class="badge badge-ok">1차 완료</span></div>
              <p class="meta">1차 스키마·도메인 설계 완료. 규격·상품·호환·정책 등 핵심 테이블 반영.</p>
            </div>
            <div class="card">
              <div class="title-row"><strong>AI 라이브러리 구축</strong><span class="badge badge-run">진행중</span></div>
              <p class="meta">프롬프트·생성 파이프라인·모드별(기본/전문가) 호출 구조 및 크레딧 연동 준비.</p>
            </div>
            <div class="card">
              <div class="title-row"><strong>백오피스 구축</strong><span class="badge badge-run">시작</span></div>
              <p class="meta">관리자 화면·운영 모듈 착수. 상품·주문·AI 사용량·규격 관리와 연동 예정.</p>
            </div>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>05회차 · 안건 1</span></div>
      </section>

      <!-- 04 AI Basic -->
      <section class="slide" data-title="AI 기본모드">
        <div class="eyebrow">Agenda 02 · AI Features 1/2</div>
        <h2>AI 기능 — 기본모드</h2>
        <p class="lede">「말로 시키면 라벨이 나온다」 — 대화형으로 빠르게 시안을 만들고 편집기로 이어갑니다.</p>
        <div class="slide-body">
          <div class="mode-hero">
            <div class="mode-badge-big">
              <div class="mode-kicker">Simple</div>
              <div class="mode-name">기본<br>모드</div>
              <div class="mode-tag">심플 · 저크레딧</div>
            </div>
            <ul class="feature-list">
              <li><span class="dot"></span><div><strong>대화형 프롬프트</strong><br><span>짧은 지시문·상품명만으로 시안 생성 · 간편 빠른 디자인</span></div></li>
              <li><span class="dot"></span><div><strong>다양한 입력</strong><br><span>텍스트 · 이미지 붙여넣기 · 엑셀 업로드 · 예시 태그 · 내 파일</span></div></li>
              <li><span class="dot"></span><div><strong>AI 추천 결과</strong><br><span>레이아웃·규격·미리보기 카드 1~3안 선택 후 즉시 반영</span></div></li>
              <li><span class="dot"></span><div><strong>편집기 착지</strong><br><span>우측 프리뷰에서 확인 → 「편집기로 보내기」로 상세 수정</span></div></li>
            </ul>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>05회차 · AI 기본</span></div>
      </section>

      <!-- 05 AI Expert -->
      <section class="slide" data-title="AI 전문가모드">
        <div class="eyebrow">Agenda 02 · AI Features 2/2</div>
        <h2>AI 기능 — 전문가모드</h2>
        <p class="lede">「항목별로 세밀하게 맞춘다」 — 각 설정에 AI를 붙여 전문가 수준으로 제어합니다.</p>
        <div class="slide-body">
          <div class="mode-hero">
            <div class="mode-badge-big expert">
              <div class="mode-kicker">Expert</div>
              <div class="mode-name">전문가<br>모드</div>
              <div class="mode-tag">상세 설정 · AI 보조</div>
            </div>
            <ul class="feature-list">
              <li><span class="dot amber"></span><div><strong>규격·용도 AI 추천</strong><br><span>부착 대상·출력 목적·수량에 맞춰 규격·용지를 점수화하고 후보를 제시</span></div></li>
              <li><span class="dot amber"></span><div><strong>텍스트·카피 AI 제어</strong><br><span>문구 길이·톤·필수표시를 항목 단위로 조정 · 줄바꿈·폰트 크기 제안</span></div></li>
              <li><span class="dot amber"></span><div><strong>레이아웃·톤 세밀 설정</strong><br><span>모던/내추럴/빈티지 등 톤 + 영역별 우선순위·충돌 해소·가독성 검증</span></div></li>
              <li><span class="dot amber"></span><div><strong>소재·색상·이미지·데이터</strong><br><span>재질·컬러·참고이미지·엑셀 필드를 각각 매핑·미리보기하며 조합 생성</span></div></li>
              <li><span class="dot amber"></span><div><strong>바코드·QR·인쇄 옵션</strong><br><span>코드 종류·크기·여백·인식률을 AI가 점검하고 출력 전 보정안 제시</span></div></li>
            </ul>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>05회차 · AI 전문가</span></div>
      </section>

      <!-- 06 Roadmap -->
      <section class="slide dark" data-title="로드맵">
        <div class="eyebrow">Agenda 02 · Roadmap</div>
        <h2>라벨업 로드맵</h2>
        <p class="lede">편집기 대체 → AI 운영체제 → 글로벌 확장 · 3단계 기술 진화</p>
        <div class="slide-body">
          <ul class="roadmap-list">
            <li>
              <span class="rm-no">01</span>
              <div class="rm-phase">
                <span class="rm-concept">AI 라벨의 대표로 자리잡기</span>
                <strong>AI Label Platform</strong>
                <div class="rm-year">2026–2027 · 기반 구축</div>
                <ul class="rm-items">
                  <li>웹 AI 제작·출력으로 설치형 프로그램 대체</li>
                  <li>규격 DB · 기본/전문가 AI 모드</li>
                  <li>Print Agent · Preflight · Excel AI</li>
                  <li>타사 포맷 가져오기(폼텍·아이라벨 등)</li>
                  <li>오픈마켓 연동</li>
                </ul>
              </div>
            </li>
            <li>
              <span class="rm-no">02</span>
              <div class="rm-phase">
                <span class="rm-concept">AI라벨 업무 자동화</span>
                <strong>AI Automation · Label OS</strong>
                <div class="rm-year">2027–2029 · 자동화 · 운영체제</div>
                <ul class="rm-items">
                  <li>Label GPT · Batch AI · 다국어·브랜드 학습</li>
                  <li>상품 1회 등록 → 라벨·POP·배송·QR 자동 생성</li>
                  <li>API · Workflow · Team Workspace</li>
                  <li>Marketplace · Print Profile Cloud</li>
                  <li>이커머스 + 마케팅 자동화</li>
                </ul>
              </div>
            </li>
            <li>
              <span class="rm-no">03</span>
              <div class="rm-phase">
                <span class="rm-concept">연결 + 확장</span>
                <strong>Commerce · Global Intelligence</strong>
                <div class="rm-year">2029–2031 · 커머스 · 글로벌</div>
                <ul class="rm-items">
                  <li>ERP·POS 연동 · Print Farm</li>
                  <li>QR Analytics · 본사–매장 출력 정책</li>
                  <li>Multi-Agent · Vision · 국가별 규정 검증</li>
                  <li>Digital Twin · Robot Print &amp; Apply</li>
                </ul>
              </div>
            </li>
          </ul>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>05회차 · 로드맵</span></div>
      </section>

      <!-- 07 Patent -->
      <section class="slide" data-title="특허 관련">
        <div class="eyebrow">Agenda 03 · Patent</div>
        <h2>특허 관련</h2>
        <p class="lede">AI 라벨에 특화된 처리 절차 3건을 중심으로 권리화를 검토합니다.</p>
        <div class="slide-body">
          <div class="patent-principle">
            <strong>Core:</strong> 입력 데이터 · 판단 기준 · 규격 DB · 출력 조건 · 자동 보정 · 검증 결과 연결
          </div>
          <div class="patent-grid">
            <div class="patent-item">
              <div class="p-num">01 · 생성 · 규격</div>
              <strong>AI 기반 라벨 자동 생성 및 규격 선택</strong>
              <p>상품 용도·부착 대상·출력 수량·프린터 조건을 분석해 제조사별 규격 DB에서 최적 용지를 점수화·선택하고, 동시에 레이아웃·텍스트·QR/바코드까지 시안을 생성합니다.</p>
              <div class="p-detail">권리화 포인트: 속성 기반 규격 점수 산정 · 다브랜드 규격 매칭 · 생성까지 이어지는 일체화 절차</div>
              <div class="patent-flow">자연어/상품정보 → 용도 분석 → 규격 추천 → 레이아웃 생성</div>
            </div>
            <div class="patent-item">
              <div class="p-num">02 · 레이아웃</div>
              <strong>라벨 특화 AI 자동 레이아웃 엔진</strong>
              <p>작은 라벨 면적에서 텍스트 길이·필수정보·브랜드·가변정보·QR/아이콘의 중요도를 판단해 폰트·줄바꿈·영역·위치를 자동 재배치하고, 충돌·가독성·잘림을 해소합니다.</p>
              <div class="p-detail">권리화 포인트: 라벨 면적 제약 최적화 · 요소 우선순위 · 충돌 해소 · 가독성 검증 루프</div>
              <div class="patent-flow">콘텐츠 분석 → 우선순위 부여 → 충돌 해소 → 가독성 검증</div>
            </div>
            <div class="patent-item">
              <div class="p-num">03 · 출력 검증</div>
              <strong>AI 인쇄 사전검증 Preflight</strong>
              <p>출력 전 QR 인식률·바코드 품질·글자 잘림·해상도·색 대비·여백·인쇄 가능성을 실제 출력 조건에 맞춰 검사하고, 실패 예측 점수와 항목별 보정안을 제시한 뒤 재검증·승인합니다.</p>
              <div class="p-detail">권리화 포인트: 출력 조건 반영 실패 예측 · 항목별 보정값 · 수정–재검증 루프</div>
              <div class="patent-flow">편집 결과 → 위험도 계산 → 자동 수정안 → 출력 승인</div>
            </div>
          </div>
        </div>
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>05회차 · 특허</span></div>
      </section>

      <!-- 10 Close -->
      <section class="slide cover" data-title="마무리">
        <div class="eyebrow">Thank you</div>
        <h1>Q &amp; A</h1>
        <p class="lede" style="max-width:560px">
          변리사 미팅 — <strong style="color:#fff">2026.08.05</strong><br>
          상세 일정·진행률 — 프로젝트 사이트 대시보드
        </p>
        <div class="pill-row">
          <span class="pill">Label-UP</span>
          <span class="pill">5회차 회의 자료</span>
        </div>
        <div class="footer-bar">
          <span class="brand-mark">LABEL-UP</span>
          <span>Round 05</span>
        </div>
      </section>

<?php if (!empty($attachments)): ?>
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
        <div class="footer-bar"><span class="brand-mark">LABEL-UP</span><span>05회차 · 첨부</span></div>
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
        document.title = 'Label-UP · 5회차 — ' + (slides[idx].getAttribute('data-title') || '');
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
