<?php
/**
 * 이력서 PDF(인쇄) 전용 페이지 — 공식 문서 스타일
 * @var array $viewPerson
 * @var array $viewEntries
 * @var array $categories
 * @var array $project
 */
$vp = $viewPerson;
$ve = $viewEntries;
$filename = '이력서_' . preg_replace('/[^\w가-힣\-]+/u', '_', $vp['name']) . '_' . date('Ymd');
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
            --paper: #ffffff;
            --wash: #f7f9fb;
        }
        * { box-sizing: border-box; }
        body.resume-print-body {
            margin: 0;
            background: #cbd5e1;
            font-family: 'Malgun Gothic', 'Apple SD Gothic Neo', 'Noto Sans KR', sans-serif;
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }
        .resume-print-bar {
            position: sticky; top: 0; z-index: 20;
            display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
            padding: 10px 16px; background: #0f172a; color: #fff;
        }
        .resume-print-bar strong { font-size: 14px; margin-right: 8px; }
        .resume-print-bar .meta { font-size: 12px; color: #94a3b8; margin-right: auto; }
        .resume-print-btn {
            height: 32px; padding: 0 12px; border-radius: 6px; border: 1px solid transparent;
            background: #2563eb; color: #fff; font-size: 13px; cursor: pointer;
            font-family: inherit; text-decoration: none; display: inline-flex; align-items: center;
        }
        .resume-print-btn--ghost { background: transparent; border-color: #475569; color: #e2e8f0; }

        .resume-print-sheet {
            max-width: 794px;
            margin: 24px auto;
            background: var(--paper);
            padding: 44px 48px 36px;
            box-shadow: 0 16px 48px rgba(15, 23, 42, .16);
            border: 1px solid rgba(15, 23, 42, .06);
        }

        .resume-doc { font-size: 13.5px; line-height: 1.55; color: var(--ink); }

        .resume-doc-head {
            position: relative;
            margin: 0 0 28px;
            padding: 0 0 22px;
            border-bottom: 3px solid var(--accent);
        }
        .resume-doc-brand {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 14px;
        }
        .resume-doc-eyebrow {
            margin: 0;
            font-size: 10px; font-weight: 800;
            letter-spacing: .18em; text-transform: uppercase;
            color: var(--accent);
        }
        .resume-doc-docmark {
            margin: 0;
            font-size: 9px; font-weight: 700;
            letter-spacing: .12em;
            color: #9ca3af;
            border: 1px solid var(--line-strong);
            padding: 3px 8px;
        }
        .resume-doc-name {
            margin: 0 0 8px;
            font-size: 30px; font-weight: 800;
            letter-spacing: -.03em; line-height: 1.15;
            color: var(--ink);
        }
        .resume-doc-role {
            margin: 0 0 14px;
            font-size: 14px; font-weight: 600;
            color: var(--ink-2);
        }
        .resume-doc-role-sep { margin: 0 6px; color: #9ca3af; font-weight: 400; }
        .resume-doc-role-org { color: var(--muted); font-weight: 500; }
        .resume-doc-contact {
            list-style: none; margin: 0; padding: 0;
            display: flex; flex-wrap: wrap; gap: 8px 18px;
            font-size: 12.5px; color: #374151;
        }
        .resume-doc-contact-label {
            display: inline-block; margin-right: 7px;
            font-size: 9.5px; font-weight: 800;
            letter-spacing: .1em; text-transform: uppercase;
            color: #9ca3af; vertical-align: middle;
        }
        .resume-doc-summary {
            margin-top: 16px;
            display: grid;
            grid-template-columns: 44px 1fr;
            gap: 12px;
            padding: 12px 14px;
            background: var(--wash);
            border: 1px solid var(--line);
            border-left: 4px solid var(--accent);
        }
        .resume-doc-summary-label {
            font-size: 10px; font-weight: 800;
            letter-spacing: .08em; color: var(--accent);
            padding-top: 2px;
        }
        .resume-doc-summary p {
            margin: 0; font-size: 13px; line-height: 1.65; color: #374151;
        }

        .resume-doc-section {
            margin: 0 0 100px;
            padding: 0;
            page-break-inside: auto;
            break-inside: auto;
        }
        .resume-doc-section:last-of-type {
            margin-bottom: 0;
        }
        .resume-doc-section-head {
            display: flex; align-items: center; gap: 10px;
            margin: 0 0 12px;
            padding: 8px 12px;
            background: var(--accent);
            color: #fff;
            page-break-after: avoid;
            break-after: avoid;
        }
        .resume-doc-section-num {
            font-size: 11px; font-weight: 800;
            letter-spacing: .06em; opacity: .75;
            min-width: 22px;
        }
        .resume-doc-section-head h3 {
            margin: 0; flex: 1;
            font-size: 13px; font-weight: 800;
            letter-spacing: .04em;
        }
        .resume-doc-section-count {
            font-size: 11px; font-weight: 700;
            background: rgba(255,255,255,.18);
            padding: 2px 8px; border-radius: 999px;
        }

        .resume-doc-items {
            display: flex; flex-direction: column; gap: 0;
            border: 1px solid var(--line);
            border-top: none;
            page-break-inside: auto;
            break-inside: auto;
        }
        .resume-doc-item {
            padding: 14px 14px 14px 16px;
            border-bottom: 1px solid var(--line);
            border-left: 3px solid transparent;
            background: #fff;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .resume-doc-item:nth-child(even) { background: var(--wash); }
        .resume-doc-item:last-child { border-bottom: none; }
        .resume-doc-item:hover { border-left-color: var(--accent); }
        .resume-doc-item-head {
            display: flex; justify-content: space-between; gap: 16px;
            align-items: flex-start;
        }
        .resume-doc-item-main { flex: 1; min-width: 0; }
        .resume-doc-item-title {
            font-size: 14.5px; font-weight: 800;
            color: var(--ink); line-height: 1.35;
        }
        .resume-doc-item-org {
            margin-top: 3px;
            font-size: 12.5px; color: var(--muted); font-weight: 500;
        }
        .resume-doc-item-period {
            flex-shrink: 0;
            font-size: 11.5px; font-weight: 700;
            color: var(--accent);
            background: var(--accent-soft);
            padding: 4px 10px;
            white-space: nowrap;
            border: 1px solid #d5e4ef;
        }
        .resume-doc-item-desc {
            margin: 10px 0 0;
            padding-top: 10px;
            border-top: 1px dashed var(--line);
            font-size: 12.5px; line-height: 1.65; color: #4b5563;
        }

        .resume-doc-skills {
            display: flex; flex-wrap: wrap; gap: 8px;
            padding: 14px;
            border: 1px solid var(--line);
            border-top: none;
            background: var(--wash);
        }
        .resume-doc-skill {
            font-size: 12px; font-weight: 600;
            padding: 5px 11px;
            background: #fff;
            border: 1px solid var(--line-strong);
            color: var(--ink-2);
        }

        .resume-print-foot {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 2px solid var(--ink);
            display: flex; justify-content: space-between; gap: 12px;
            font-size: 10.5px; color: #9ca3af;
        }

        @media print {
            body.resume-print-body { background: #fff; }
            .resume-print-bar, .resume-print-hint { display: none !important; }
            .resume-print-sheet {
                margin: 0; box-shadow: none; border: none;
                max-width: none; padding: 0;
            }
            .resume-doc-item:hover { border-left-color: transparent; }
            @page { size: A4; margin: 14mm 14mm 16mm; }
        }
    </style>
</head>
<body class="resume-print-body">
    <div class="resume-print-bar">
        <strong>이력서 PDF</strong>
        <span class="meta"><?= e($vp['name']) ?></span>
        <a class="resume-print-btn resume-print-btn--ghost" href="<?= url('resume.php?view=' . (int) $vp['id'] . '&print=1&noprint=1') ?>">미리보기</a>
        <a class="resume-print-btn resume-print-btn--ghost" href="<?= url('resume.php?view=' . (int) $vp['id']) ?>">← 보기</a>
        <button type="button" class="resume-print-btn" id="resumePdfPrint">PDF로 저장</button>
    </div>
    <div class="resume-print-sheet">
        <?php include __DIR__ . '/resume-document.inc.php'; ?>
        <footer class="resume-print-foot">
            <span>이력서</span>
            <span><?= e(date('Y.m.d')) ?></span>
        </footer>
        <p class="resume-print-hint" style="margin-top:14px;font-size:11px;color:#94a3b8;text-align:center">
            브라우저 인쇄에서 「PDF로 저장」을 선택하세요.
        </p>
    </div>
    <script>
    (function () {
        function doPrint() {
            document.title = <?= json_encode($filename, JSON_UNESCAPED_UNICODE) ?>;
            window.print();
        }
        var btn = document.getElementById('resumePdfPrint');
        if (btn) btn.addEventListener('click', doPrint);
        <?php if ($autoprint): ?>
        window.addEventListener('load', function () { setTimeout(doPrint, 400); });
        <?php endif; ?>
    })();
    </script>
</body>
</html>
