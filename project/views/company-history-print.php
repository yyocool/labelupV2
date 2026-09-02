<?php
/**
 * 회사 연혁 PDF(인쇄) 전용 페이지 — 공식 문서 스타일
 */
$vc = $viewCompany;
$ve = isset($viewEvents) ? $viewEvents : array();
$veByYear = $viewEventsByYear;
$va = isset($viewAchievements) ? $viewAchievements : array();
$filename = '회사연혁_' . preg_replace('/[^\w가-힣\-]+/u', '_', $vc['name']) . '_' . date('Ymd');
$autoprint = !isset($_GET['noprint']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($filename) ?></title>
    <style>
        :root {
            --ink: #111827;
            --ink-2: #1f2937;
            --muted: #6b7280;
            --line: #e5e7eb;
            --line-strong: #d1d5db;
            --accent: #0f3d5e;
            --accent-soft: #e8f0f6;
            --wash: #f7f9fb;
        }
        * { box-sizing: border-box; }
        body.ch-print-body {
            margin: 0;
            background: #cbd5e1;
            font-family: 'Malgun Gothic', 'Apple SD Gothic Neo', 'Noto Sans KR', sans-serif;
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }
        .ch-print-bar {
            position: sticky; top: 0; z-index: 20;
            display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
            padding: 10px 16px; background: #0f172a; color: #fff;
        }
        .ch-print-bar strong { font-size: 14px; margin-right: 8px; }
        .ch-print-bar .meta { font-size: 12px; color: #94a3b8; margin-right: auto; }
        .ch-print-btn {
            height: 32px; padding: 0 12px; border-radius: 6px; border: 1px solid transparent;
            background: #2563eb; color: #fff; font-size: 13px; cursor: pointer;
            font-family: inherit; text-decoration: none; display: inline-flex; align-items: center;
        }
        .ch-print-btn--ghost { background: transparent; border-color: #475569; color: #e2e8f0; }

        .ch-print-sheet {
            max-width: 794px;
            margin: 24px auto;
            background: #fff;
            padding: 44px 48px 36px;
            box-shadow: 0 16px 48px rgba(15, 23, 42, .16);
            border: 1px solid rgba(15, 23, 42, .06);
        }

        .ch-doc { font-size: 13.5px; line-height: 1.55; color: var(--ink); }

        .ch-doc-head {
            margin: 0 0 28px;
            padding: 0 0 22px;
            border-bottom: 3px solid var(--accent);
        }
        .ch-doc-brand {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 14px;
        }
        .ch-doc-eyebrow {
            margin: 0;
            font-size: 10px; font-weight: 800;
            letter-spacing: .18em; text-transform: uppercase;
            color: var(--accent);
        }
        .ch-doc-docmark {
            margin: 0;
            font-size: 9px; font-weight: 700;
            letter-spacing: .12em;
            color: #9ca3af;
            border: 1px solid var(--line-strong);
            padding: 3px 8px;
        }
        .ch-doc-name {
            margin: 0 0 12px;
            font-size: 28px; font-weight: 800;
            letter-spacing: -.03em; line-height: 1.15;
        }
        .ch-doc-meta {
            list-style: none; margin: 0; padding: 0;
            display: flex; flex-wrap: wrap; gap: 8px 18px;
            font-size: 12.5px; color: #374151;
        }
        .ch-doc-meta-label {
            display: inline-block; margin-right: 7px;
            font-size: 9.5px; font-weight: 800;
            letter-spacing: .1em; text-transform: uppercase;
            color: #9ca3af; vertical-align: middle;
        }
        .ch-doc-summary {
            margin-top: 16px;
            display: grid;
            grid-template-columns: 44px 1fr;
            gap: 12px;
            padding: 12px 14px;
            background: var(--wash);
            border: 1px solid var(--line);
            border-left: 4px solid var(--accent);
        }
        .ch-doc-summary-label {
            font-size: 10px; font-weight: 800;
            letter-spacing: .08em; color: var(--accent);
            padding-top: 2px;
        }
        .ch-doc-summary p { margin: 0; font-size: 13px; line-height: 1.65; color: #374151; }

        .ch-doc-section { margin: 0 0 26px; }
        .ch-doc-section-head {
            display: flex; align-items: center; gap: 10px;
            margin: 0 0 14px;
            padding: 8px 12px;
            background: var(--accent);
            color: #fff;
        }
        .ch-doc-section-num {
            font-size: 11px; font-weight: 800;
            letter-spacing: .06em; opacity: .75;
            min-width: 22px;
        }
        .ch-doc-section-head h3 {
            margin: 0; flex: 1;
            font-size: 13px; font-weight: 800;
            letter-spacing: .04em;
        }
        .ch-doc-section-count {
            font-size: 11px; font-weight: 700;
            background: rgba(255,255,255,.18);
            padding: 2px 8px; border-radius: 999px;
        }

        .ch-doc-timeline {
            border: 1px solid var(--line);
            border-top: none;
            background: #fff;
        }
        .ch-doc-item {
            display: grid;
            grid-template-columns: 78px minmax(0, 1fr);
            gap: 0 0;
            border-bottom: 1px solid var(--line);
            page-break-inside: avoid;
        }
        .ch-doc-item:last-child { border-bottom: none; }
        .ch-doc-item.is-year-start .ch-doc-side {
            background: var(--accent-soft);
        }
        .ch-doc-side {
            text-align: right;
            padding: 14px 12px 14px 8px;
            border-right: 1px solid var(--line);
            background: var(--wash);
        }
        .ch-doc-year {
            font-size: 15px; font-weight: 800;
            color: var(--accent); letter-spacing: -.02em; line-height: 1.2;
        }
        .ch-doc-date {
            margin-top: 4px;
            font-size: 11px; font-weight: 600; color: var(--muted);
        }
        .ch-doc-body { padding: 0; min-width: 0; }
        .ch-doc-item-card {
            padding: 13px 14px;
            border-left: 3px solid transparent;
        }
        .ch-doc-item:nth-child(even) .ch-doc-item-card { background: var(--wash); }
        .ch-doc-item-top {
            display: flex; flex-wrap: wrap; align-items: baseline; gap: 8px 10px;
        }
        .ch-doc-item-index {
            font-size: 10px; font-weight: 800;
            color: #9ca3af; letter-spacing: .04em;
            min-width: 18px;
        }
        .ch-doc-item-title {
            font-size: 14px; font-weight: 800;
            color: var(--ink); line-height: 1.35; flex: 1;
        }
        .ch-doc-cat {
            flex-shrink: 0;
            font-size: 10px; font-weight: 700;
            letter-spacing: .02em;
            color: var(--accent);
            background: var(--accent-soft);
            border: 1px solid #d5e4ef;
            padding: 2px 8px;
        }
        .ch-doc-item-desc {
            margin: 8px 0 0;
            padding-top: 8px;
            border-top: 1px dashed var(--line);
            font-size: 12.5px; line-height: 1.65; color: #4b5563;
        }
        .ch-doc-item-sub {
            margin-top: 6px;
            display: flex; flex-wrap: wrap; gap: 8px 14px;
            font-size: 12px; color: var(--muted);
        }
        .ch-doc-item-metric {
            font-weight: 800; color: var(--accent);
            background: #fff;
            border: 1px solid #d5e4ef;
            padding: 2px 8px;
        }

        .ch-doc-ach-list {
            list-style: none; margin: 0; padding: 0;
            border: 1px solid var(--line);
            border-top: none;
        }
        .ch-doc-ach-item {
            display: grid;
            grid-template-columns: 48px minmax(0, 1fr);
            border-bottom: 1px solid var(--line);
            page-break-inside: avoid;
        }
        .ch-doc-ach-item:last-child { border-bottom: none; }
        .ch-doc-ach-item:nth-child(even) { background: var(--wash); }
        .ch-doc-ach-num {
            display: flex; align-items: flex-start; justify-content: center;
            padding: 14px 0 0;
            font-size: 13px; font-weight: 800;
            color: var(--accent);
            background: var(--accent-soft);
            border-right: 1px solid var(--line);
        }
        .ch-doc-ach-body { padding: 13px 14px; }
        .ch-doc-ach-year {
            font-size: 11.5px; font-weight: 800;
            color: var(--accent);
            background: var(--accent-soft);
            border: 1px solid #d5e4ef;
            padding: 2px 8px;
        }
        .ch-doc-ach-client { font-weight: 600; color: #4b5563; }
        .ch-doc-empty { font-size: 13px; color: var(--muted); padding: 20px; text-align: center; border: 1px dashed var(--line); }

        .ch-print-foot {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 2px solid var(--ink);
            display: flex; justify-content: space-between; gap: 12px;
            font-size: 10.5px; color: #9ca3af;
        }

        @media print {
            body.ch-print-body { background: #fff; }
            .ch-print-bar, .ch-print-hint { display: none !important; }
            .ch-print-sheet {
                margin: 0; box-shadow: none; border: none;
                max-width: none; padding: 0;
            }
            @page { size: A4; margin: 14mm 14mm 16mm; }
        }
    </style>
</head>
<body class="ch-print-body">
    <div class="ch-print-bar">
        <strong>회사 연혁 PDF</strong>
        <span class="meta"><?= e($vc['name']) ?></span>
        <a class="ch-print-btn ch-print-btn--ghost" href="<?= url('company-history.php?view=' . (int) $vc['id'] . '&print=1&noprint=1') ?>">미리보기</a>
        <a class="ch-print-btn ch-print-btn--ghost" href="<?= url('company-history.php?view=' . (int) $vc['id']) ?>">← 보기</a>
        <button type="button" class="ch-print-btn" id="chPdfPrint">PDF로 저장</button>
    </div>
    <div class="ch-print-sheet">
        <?php include __DIR__ . '/company-history-document.inc.php'; ?>
        <footer class="ch-print-foot">
            <span>회사 연혁</span>
            <span><?= e(date('Y.m.d')) ?></span>
        </footer>
        <p class="ch-print-hint" style="margin-top:14px;font-size:11px;color:#94a3b8;text-align:center">
            브라우저 인쇄에서 「PDF로 저장」을 선택하세요.
        </p>
    </div>
    <script>
    (function () {
        function doPrint() {
            document.title = <?= json_encode($filename, JSON_UNESCAPED_UNICODE) ?>;
            window.print();
        }
        var btn = document.getElementById('chPdfPrint');
        if (btn) btn.addEventListener('click', doPrint);
        <?php if ($autoprint): ?>
        window.addEventListener('load', function () { setTimeout(doPrint, 400); });
        <?php endif; ?>
    })();
    </script>
</body>
</html>
