<?php
/**
 * 스토리보드 전체 PDF 내보내기 (인쇄 → PDF 저장)
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/StoryboardPdfService.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$scope = isset($_GET['scope']) ? $_GET['scope'] : 'files';
if (!in_array($scope, array('all', 'files', 'ready'), true)) {
    $scope = 'files';
}
$autoprint = isset($_GET['autoprint']) && $_GET['autoprint'] === '1';

$pages = StoryboardPdfService::collectPages($project['id'], $scope);
$menus = MenuService::getByProject($project['id']);
$menuTree = build_menu_tree($menus);
$linkBase = url('storyboard.php');
$generatedAt = date('Y-m-d H:i');
$projectName = isset($project['name']) ? $project['name'] : 'Label-UP';
$filename = '스토리보드_' . preg_replace('/[^\w가-힣\-]+/u', '_', $projectName) . '_' . date('Ymd');

$statusCounts = array('ready' => 0, 'stub' => 0, 'none' => 0);
foreach ($pages as $p) {
    if (isset($statusCounts[$p['status']])) {
        $statusCounts[$p['status']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($filename) ?> — PDF</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        :root { --sbpdf-ink: #0f172a; --sbpdf-muted: #64748b; --sbpdf-line: #e2e8f0; }
        * { box-sizing: border-box; }
        body.sbpdf-body {
            margin: 0;
            background: #e2e8f0;
            color: var(--sbpdf-ink);
            font-family: 'Malgun Gothic', 'Apple SD Gothic Neo', sans-serif;
        }
        .sbpdf-toolbar {
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: #0f172a;
            color: #fff;
        }
        .sbpdf-toolbar strong { font-size: 14px; margin-right: 8px; }
        .sbpdf-toolbar .meta { font-size: 12px; color: #94a3b8; margin-right: auto; }
        .sbpdf-btn {
            display: inline-flex;
            align-items: center;
            height: 32px;
            padding: 0 12px;
            border-radius: 6px;
            border: 1px solid transparent;
            background: #2563eb;
            color: #fff;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
        }
        .sbpdf-btn:hover { background: #1d4ed8; color: #fff; }
        .sbpdf-btn--ghost {
            background: transparent;
            border-color: #475569;
            color: #e2e8f0;
        }
        .sbpdf-btn--ghost:hover { background: #1e293b; }
        .sbpdf-sheet {
            max-width: 1100px;
            margin: 20px auto;
            background: #fff;
            box-shadow: 0 8px 30px rgba(15,23,42,.12);
        }
        .sbpdf-cover {
            padding: 64px 48px;
            min-height: 70vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-bottom: 1px solid var(--sbpdf-line);
        }
        .sbpdf-cover .eyebrow {
            font-size: 12px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #2563eb;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .sbpdf-cover h1 {
            margin: 0 0 12px;
            font-size: 36px;
            line-height: 1.25;
        }
        .sbpdf-cover .sub { color: var(--sbpdf-muted); font-size: 15px; margin: 0 0 28px; }
        .sbpdf-stats {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            font-size: 13px;
        }
        .sbpdf-stats span {
            background: #f8fafc;
            border: 1px solid var(--sbpdf-line);
            border-radius: 999px;
            padding: 6px 12px;
        }
        .sbpdf-toc {
            padding: 40px 48px;
            border-bottom: 1px solid var(--sbpdf-line);
        }
        .sbpdf-toc h2 { margin: 0 0 16px; font-size: 20px; }
        .sbpdf-toc table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .sbpdf-toc th, .sbpdf-toc td {
            border-bottom: 1px solid var(--sbpdf-line);
            padding: 8px 6px;
            text-align: left;
            vertical-align: top;
        }
        .sbpdf-toc th { color: var(--sbpdf-muted); font-weight: 600; font-size: 11px; }
        .sbpdf-toc .code { font-family: Consolas, monospace; color: #2563eb; white-space: nowrap; }
        .sbpdf-toc .trail { color: var(--sbpdf-muted); font-size: 11px; }
        .sbpdf-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .sbpdf-badge--ready { background: #dcfce7; color: #166534; }
        .sbpdf-badge--stub { background: #fef9c3; color: #854d0e; }
        .sbpdf-badge--none { background: #f1f5f9; color: #64748b; }

        .sbpdf-page {
            padding: 28px 36px 40px;
            border-bottom: 1px solid var(--sbpdf-line);
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .sbpdf-page-head {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0f172a;
        }
        .sbpdf-page-head h2 {
            margin: 0;
            font-size: 18px;
            flex: 1;
        }
        .sbpdf-page-head .code {
            font-family: Consolas, monospace;
            font-size: 12px;
            color: #2563eb;
            background: #eff6ff;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .sbpdf-crumb {
            margin: 0 0 12px;
            font-size: 12px;
            color: var(--sbpdf-muted);
        }
        .sbpdf-frame {
            border: 1px solid var(--sbpdf-line);
            border-radius: 10px;
            overflow: hidden;
            background: #f8fafc;
            min-height: 200px;
        }
        .sbpdf-frame .sb-wf,
        .sbpdf-frame .sb-wf-fragment-stub,
        .sbpdf-frame .sb-page {
            transform-origin: top left;
        }
        .sbpdf-frame-inner {
            padding: 8px;
            overflow: hidden;
        }
        /* 와이어프레임이 과도하게 크면 축소 */
        .sbpdf-frame-inner > .sb-wf,
        .sbpdf-frame-inner > .sb-wf-fragment-stub {
            max-width: 100%;
        }
        .sbpdf-empty {
            padding: 40px;
            text-align: center;
            color: var(--sbpdf-muted);
            font-size: 13px;
        }
        .sbpdf-footnote {
            padding: 24px 48px 40px;
            font-size: 11px;
            color: var(--sbpdf-muted);
            text-align: center;
        }

        @media print {
            body.sbpdf-body { background: #fff; }
            .sbpdf-toolbar { display: none !important; }
            .sbpdf-sheet {
                max-width: none;
                margin: 0;
                box-shadow: none;
            }
            /* 표지 다음에 목차를 바로 이어서 첫 장의 큰 공백을 없앤다. */
            .sbpdf-cover {
                min-height: auto;
                padding: 0 0 18px;
                justify-content: flex-start;
                page-break-after: auto;
                break-after: auto;
            }
            .sbpdf-toc { page-break-after: always; padding: 0 0 24px; }
            .sbpdf-page {
                padding: 12px 0 24px;
                page-break-after: always;
                border-bottom: none;
            }
            .sbpdf-page:last-of-type { page-break-after: auto; }
            .sbpdf-frame { break-inside: avoid; }
            a { color: inherit; text-decoration: none; }
            @page {
                size: A4;
                margin: 12mm;
            }
        }
    </style>
</head>
<body class="sbpdf-body">
    <div class="sbpdf-toolbar no-print">
        <strong>스토리보드 PDF</strong>
        <span class="meta"><?= e($projectName) ?> · <?= count($pages) ?>화면 · <?= e($generatedAt) ?></span>
        <a class="sbpdf-btn--ghost sbpdf-btn" href="<?= url('storyboard-pdf.php?scope=files') ?>">파일 있는 화면</a>
        <a class="sbpdf-btn--ghost sbpdf-btn" href="<?= url('storyboard-pdf.php?scope=ready') ?>">완료만</a>
        <a class="sbpdf-btn--ghost sbpdf-btn" href="<?= url('storyboard.php') ?>">← 스토리보드</a>
        <button type="button" class="sbpdf-btn" id="sbPdfPrint">⬇ PDF로 저장</button>
    </div>

    <div class="sbpdf-sheet" id="sbPdfSheet">
        <section class="sbpdf-cover">
            <div class="eyebrow">Label-UP Storyboard</div>
            <h1><?= e($projectName) ?><br>스토리보드 전체</h1>
            <p class="sub">메뉴별 와이어프레임·화면 명세 모음 · 생성 <?= e($generatedAt) ?></p>
            <div class="sbpdf-stats">
                <span>총 <?= count($pages) ?>화면</span>
                <span>완료 <?= (int) $statusCounts['ready'] ?></span>
                <span>준비중 <?= (int) $statusCounts['stub'] ?></span>
                <span>범위: <?= $scope === 'ready' ? '완료만' : ($scope === 'files' ? '파일 있는 화면' : '전체') ?></span>
            </div>
        </section>

        <section class="sbpdf-toc">
            <h2>목차</h2>
            <?php if (empty($pages)): ?>
            <p class="sbpdf-empty">내보낼 스토리보드가 없습니다.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="width:90px">코드</th>
                        <th>화면</th>
                        <th style="width:70px">상태</th>
                        <th style="width:50px">#</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $i => $p): ?>
                    <tr>
                        <td class="code"><?= e($p['code']) ?></td>
                        <td>
                            <div><?= e($p['title']) ?></div>
                            <?php if (!empty($p['trail'])): ?>
                            <div class="trail"><?= e(implode(' › ', $p['trail'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="sbpdf-badge sbpdf-badge--<?= e($p['status']) ?>"><?= e(StoryboardPdfService::statusLabel($p['status'])) ?></span></td>
                        <td><?= $i + 1 ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>

        <?php foreach ($pages as $i => $p): ?>
        <?php
            $menuRow = null;
            foreach ($menus as $m) {
                if ((int) $m['id'] === (int) $p['id']) {
                    $menuRow = $m;
                    break;
                }
            }
            $wfHtml = '';
            if ($menuRow) {
                try {
                    $wfHtml = StoryboardPdfService::renderWireframeHtml($menuRow, $menus, $menuTree, $linkBase);
                } catch (Exception $e) {
                    $wfHtml = '<div class="sbpdf-empty">렌더 오류: ' . e($e->getMessage()) . '</div>';
                }
            }
        ?>
        <section class="sbpdf-page" id="sbpdf-<?= e($p['code']) ?>">
            <div class="sbpdf-page-head">
                <span class="code"><?= e($p['code']) ?></span>
                <h2><?= e($p['title']) ?></h2>
                <span class="sbpdf-badge sbpdf-badge--<?= e($p['status']) ?>"><?= e(StoryboardPdfService::statusLabel($p['status'])) ?></span>
            </div>
            <?php if (!empty($p['trail'])): ?>
            <p class="sbpdf-crumb"><?= e(implode(' › ', $p['trail'])) ?></p>
            <?php endif; ?>
            <div class="sbpdf-frame">
                <div class="sbpdf-frame-inner">
                    <?php if ($wfHtml !== ''): ?>
                        <?= $wfHtml ?>
                    <?php else: ?>
                        <div class="sbpdf-empty">와이어프레임이 없습니다.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endforeach; ?>

        <p class="sbpdf-footnote">
            Label-UP · <?= e($projectName) ?> · 스토리보드 PDF · <?= e($generatedAt) ?><br>
            브라우저 인쇄 대화상자에서 「PDF로 저장」을 선택하세요.
        </p>
    </div>

    <script>
    (function () {
        var btn = document.getElementById('sbPdfPrint');
        function doPrint() {
            document.title = <?= json_encode($filename, JSON_UNESCAPED_UNICODE) ?>;
            window.print();
        }
        if (btn) btn.addEventListener('click', doPrint);
        <?php if ($autoprint): ?>
        window.addEventListener('load', function () {
            setTimeout(doPrint, 600);
        });
        <?php endif; ?>
    })();
    </script>
</body>
</html>
