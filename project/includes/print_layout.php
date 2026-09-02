<?php
/**
 * 브라우저 인쇄(PDF로 저장) 공통 레이아웃
 *
 * $opts:
 *   title        툴바 제목
 *   filename     PDF 저장 시 document.title
 *   meta         툴바 부가 설명
 *   back_url     돌아가기 URL
 *   preview_url  미리보기(?noprint=1) URL
 *   stylesheet   true면 앱 style.css 로드 (분석 문서용)
 *   landscape    true면 A4 가로
 *   max_width    시트 max-width (기본 900px / landscape 1100px)
 *   body_class   추가 body class
 */
function print_layout_start(array $opts)
{
    $title = isset($opts['title']) ? $opts['title'] : 'PDF';
    $filename = isset($opts['filename']) ? $opts['filename'] : $title;
    $meta = isset($opts['meta']) ? $opts['meta'] : '';
    $backUrl = isset($opts['back_url']) ? $opts['back_url'] : url('index.php');
    $previewUrl = isset($opts['preview_url']) ? $opts['preview_url'] : '';
    $useCss = !empty($opts['stylesheet']);
    $landscape = !empty($opts['landscape']);
    $maxWidth = isset($opts['max_width'])
        ? $opts['max_width']
        : ($landscape ? '1100px' : '900px');
    $bodyClass = 'print-body' . (isset($opts['body_class']) ? ' ' . $opts['body_class'] : '');
    $pageSize = $landscape ? 'A4 landscape' : 'A4';
    $autoprint = !isset($_GET['noprint']);

    $GLOBALS['__print_layout'] = array(
        'filename' => $filename,
        'autoprint' => $autoprint,
    );
    ?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($filename) ?></title>
    <?php if ($useCss): ?>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <?php endif; ?>
    <style>
        body.print-body {
            margin: 0;
            background: #cbd5e1;
            font-family: 'Malgun Gothic', 'Apple SD Gothic Neo', 'Noto Sans KR', sans-serif;
            color: #111827;
            -webkit-font-smoothing: antialiased;
        }
        .print-bar {
            position: sticky; top: 0; z-index: 40;
            display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
            padding: 10px 16px; background: #0f172a; color: #fff;
        }
        .print-bar strong { font-size: 14px; margin-right: 8px; }
        .print-bar .meta { font-size: 12px; color: #94a3b8; margin-right: auto; }
        .print-btn {
            height: 32px; padding: 0 12px; border-radius: 6px; border: 1px solid transparent;
            background: #2563eb; color: #fff; font-size: 13px; cursor: pointer;
            font-family: inherit; text-decoration: none; display: inline-flex; align-items: center;
        }
        .print-btn--ghost { background: transparent; border-color: #475569; color: #e2e8f0; }
        .print-sheet {
            max-width: <?= e($maxWidth) ?>;
            margin: 24px auto;
            background: #fff;
            padding: 36px 40px 28px;
            box-shadow: 0 16px 48px rgba(15, 23, 42, .16);
            border: 1px solid rgba(15, 23, 42, .06);
        }
        .print-doc-head {
            margin: 0 0 24px;
            padding: 0 0 18px;
            border-bottom: 3px solid #0f3d5e;
        }
        .print-doc-head .eyebrow {
            margin: 0 0 8px;
            font-size: 10px; font-weight: 800;
            letter-spacing: .16em; text-transform: uppercase;
            color: #0f3d5e;
        }
        .print-doc-head h1 {
            margin: 0 0 8px;
            font-size: 24px; font-weight: 800; color: #0f172a;
        }
        .print-doc-head .sub {
            margin: 0;
            font-size: 13px; color: #64748b; line-height: 1.5;
        }
        .print-foot {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 2px solid #111827;
            display: flex; justify-content: space-between; gap: 12px;
            font-size: 10.5px; color: #9ca3af;
        }
        .print-hint {
            margin-top: 14px;
            font-size: 11px; color: #94a3b8; text-align: center;
        }
        /* 화면 전용 컨트롤 숨김 */
        .print-hide-ui .btn-group,
        .print-hide-ui .page-header,
        .print-hide-ui .archive-filters,
        .print-hide-ui .menu-view-tabs,
        .print-hide-ui .menu-structure-toolbar,
        .print-hide-ui .modal-overlay,
        .print-hide-ui [data-modal],
        .print-hide-ui .menu-org-toggle,
        .print-hide-ui .menu-org-actions,
        .print-hide-ui .menu-item-actions {
            display: none !important;
        }
        .print-hide-ui .card { break-inside: avoid; page-break-inside: avoid; }
        .print-hide-ui .comp-detail-card,
        .print-hide-ui .pricing-rec-card,
        .print-hide-ui .label-gpt-feature-card {
            break-inside: avoid; page-break-inside: avoid;
        }
        .print-hide-ui a { color: inherit; text-decoration: none; }
        .print-policy-item {
            break-inside: avoid; page-break-inside: avoid;
            margin: 0 0 22px; padding: 0 0 18px;
            border-bottom: 1px solid #e5e7eb;
        }
        .print-policy-item h2 {
            margin: 0 0 6px; font-size: 16px; color: #0f172a;
        }
        .print-policy-meta {
            display: flex; flex-wrap: wrap; gap: 8px 12px;
            margin: 0 0 10px; font-size: 11px; color: #64748b;
        }
        .print-policy-summary {
            margin: 0 0 10px; font-size: 13px; color: #374151; line-height: 1.55;
        }
        .print-policy-body {
            font-size: 12.5px; line-height: 1.65; color: #1f2937;
            white-space: pre-wrap; word-break: break-word;
        }
        .print-menu-table {
            width: 100%; border-collapse: collapse; font-size: 12px;
        }
        .print-menu-table th, .print-menu-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 7px 8px; text-align: left; vertical-align: top;
        }
        .print-menu-table th {
            background: #f8fafc; color: #64748b; font-size: 11px; font-weight: 700;
        }
        .print-menu-table .code {
            font-family: Consolas, monospace; color: #2563eb; white-space: nowrap;
        }
        .print-menu-table .indent { color: #94a3b8; }
        .print-menu-table tr { break-inside: avoid; page-break-inside: avoid; }

        @media print {
            body.print-body { background: #fff !important; }
            .print-bar, .print-hint { display: none !important; }
            .print-sheet {
                margin: 0 !important; box-shadow: none !important; border: none !important;
                max-width: none !important; padding: 0 !important;
            }
            @page { size: <?= e($pageSize) ?>; margin: 12mm 12mm 14mm; }
        }
    </style>
</head>
<body class="<?= e($bodyClass) ?>">
    <div class="print-bar">
        <strong><?= e($title) ?></strong>
        <?php if ($meta !== ''): ?><span class="meta"><?= e($meta) ?></span><?php else: ?><span class="meta"></span><?php endif; ?>
        <?php if ($previewUrl !== ''): ?>
        <a class="print-btn print-btn--ghost" href="<?= e($previewUrl) ?>">미리보기</a>
        <?php endif; ?>
        <a class="print-btn print-btn--ghost" href="<?= e($backUrl) ?>">← 돌아가기</a>
        <button type="button" class="print-btn" id="printPdfBtn">PDF로 저장</button>
    </div>
    <div class="print-sheet">
    <?php
}

function print_layout_end(array $opts = array())
{
    $state = isset($GLOBALS['__print_layout']) ? $GLOBALS['__print_layout'] : array();
    $filename = isset($opts['filename']) ? $opts['filename'] : (isset($state['filename']) ? $state['filename'] : 'document');
    $autoprint = array_key_exists('autoprint', $opts)
        ? (bool) $opts['autoprint']
        : (!empty($state['autoprint']));
    $footLeft = isset($opts['foot_left']) ? $opts['foot_left'] : '';
    $footRight = isset($opts['foot_right']) ? $opts['foot_right'] : date('Y.m.d');
    ?>
        <?php if ($footLeft !== '' || $footRight !== ''): ?>
        <footer class="print-foot">
            <span><?= e($footLeft) ?></span>
            <span><?= e($footRight) ?></span>
        </footer>
        <?php endif; ?>
        <p class="print-hint">브라우저 인쇄에서 「PDF로 저장」을 선택하세요.</p>
    </div>
    <script>
    (function () {
        function doPrint() {
            document.title = <?= json_encode($filename, JSON_UNESCAPED_UNICODE) ?>;
            window.print();
        }
        var btn = document.getElementById('printPdfBtn');
        if (btn) btn.addEventListener('click', doPrint);
        <?php if ($autoprint): ?>
        window.addEventListener('load', function () { setTimeout(doPrint, 400); });
        <?php endif; ?>
    })();
    </script>
</body>
</html>
    <?php
}
