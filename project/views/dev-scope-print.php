<?php
/**
 * 개발범위 PDF(인쇄) 전용 페이지
 * @var array $project
 * @var array $printSections  [{phase_key, phase, rows}, ...]
 * @var array $priorities
 * @var array $statuses
 * @var string $printScope current|all
 * @var string $phaseKey
 */
$projectName = isset($project['name']) ? $project['name'] : 'Label-UP';
$scopeLabel = ($printScope === 'all') ? '전체' : (isset($printSections[0]['phase']['label']) ? $printSections[0]['phase']['label'] : $phaseKey);
$filename = '개발범위_' . preg_replace('/[^\w가-힣\-]+/u', '_', $scopeLabel) . '_' . date('Ymd');
$autoprint = !isset($_GET['noprint']);
$backPhase = isset($phaseKey) ? $phaseKey : 'phase-1';
$totalRows = 0;
foreach ($printSections as $sec) {
    $totalRows += count($sec['rows']);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($filename) ?></title>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --accent: #217346;
            --d1: #1e3a5f;
            --d2: #334155;
        }
        * { box-sizing: border-box; }
        body.ds-print-body {
            margin: 0;
            background: #dbe3ee;
            font-family: 'Malgun Gothic', 'Apple SD Gothic Neo', 'Noto Sans KR', sans-serif;
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }
        .ds-print-bar {
            position: sticky; top: 0; z-index: 20;
            display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
            padding: 10px 16px; background: #0f172a; color: #fff;
        }
        .ds-print-bar strong { font-size: 14px; margin-right: 8px; }
        .ds-print-bar .meta { font-size: 12px; color: #94a3b8; margin-right: auto; }
        .ds-print-btn {
            height: 32px; padding: 0 12px; border-radius: 6px; border: 1px solid transparent;
            background: #217346; color: #fff; font-size: 13px; cursor: pointer;
            font-family: inherit; text-decoration: none; display: inline-flex; align-items: center;
        }
        .ds-print-btn--ghost { background: transparent; border-color: #475569; color: #e2e8f0; }

        .ds-print-sheet {
            max-width: 1100px;
            margin: 20px auto;
            background: #fff;
            padding: 28px 32px 24px;
            box-shadow: 0 12px 40px rgba(15, 23, 42, .14);
        }

        .ds-print-head {
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 2.5px solid var(--ink);
            position: relative;
        }
        .ds-print-head::after {
            content: '';
            position: absolute; left: 0; bottom: -2.5px;
            width: 64px; height: 2.5px; background: var(--accent);
        }
        .ds-print-eyebrow {
            margin: 0 0 6px;
            font-size: 10.5px; font-weight: 700;
            letter-spacing: .14em; text-transform: uppercase;
            color: var(--accent);
        }
        .ds-print-head h1 {
            margin: 0 0 8px;
            font-size: 22px; font-weight: 800;
            letter-spacing: -.02em;
        }
        .ds-print-meta {
            margin: 0;
            font-size: 12px; color: var(--muted);
            display: flex; flex-wrap: wrap; gap: 6px 16px;
        }

        .ds-print-section { margin-top: 16px; }
        .ds-print-section:first-of-type { margin-top: 0; }
        .ds-print-section + .ds-print-section { page-break-before: always; break-before: page; }
        .ds-print-section-title {
            margin: 0 0 10px;
            font-size: 14px; font-weight: 800;
            color: var(--d1);
            display: flex; align-items: baseline; gap: 10px;
        }
        .ds-print-section-title span {
            font-size: 11px; font-weight: 600; color: var(--muted);
        }

        .ds-print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            line-height: 1.4;
            page-break-inside: auto;
            break-inside: auto;
        }
        .ds-print-table thead { display: table-header-group; }
        .ds-print-table tbody { display: table-row-group; }
        .ds-print-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .ds-print-table th {
            text-align: left;
            background: #f1f5f9;
            color: #334155;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .02em;
            padding: 7px 8px;
            border: 1px solid var(--line);
            white-space: nowrap;
        }
        .ds-print-table td {
            padding: 6px 8px;
            border: 1px solid var(--line);
            vertical-align: top;
            color: #1e293b;
        }
        .ds-print-table .col-d { width: 28px; text-align: center; color: var(--muted); font-weight: 700; }
        .ds-print-table .col-d1 { width: 18%; font-weight: 700; color: var(--d1); }
        .ds-print-table .col-d2 { width: 18%; font-weight: 600; color: var(--d2); }
        .ds-print-table .col-d3 { width: 22%; }
        .ds-print-table .col-prio { width: 48px; text-align: center; white-space: nowrap; }
        .ds-print-table .col-status { width: 56px; text-align: center; white-space: nowrap; }
        .ds-print-table .col-desc { width: auto; color: #475569; }

        .ds-print-table tr.is-d1 td { background: #f8fafc; }
        .ds-print-table tr.is-d1 .col-d1 { font-size: 12px; }
        .ds-print-table tr.is-d2 .col-d2 { font-weight: 700; }
        .ds-print-table tr.is-done td { color: #64748b; }
        .ds-print-table tr.is-out td { color: #94a3b8; text-decoration: line-through; }
        .ds-print-table tr.is-out .col-desc { text-decoration: none; }

        .ds-print-badge {
            display: inline-block;
            font-size: 10px; font-weight: 700;
            padding: 1px 6px; border-radius: 3px;
            background: #eef2f7; color: #334155;
        }
        .ds-print-badge--p0 { background: #fee2e2; color: #b91c1c; }
        .ds-print-badge--p1 { background: #ffedd5; color: #c2410c; }
        .ds-print-badge--p2 { background: #e2e8f0; color: #475569; }
        .ds-print-badge--done { background: #dcfce7; color: #15803d; }
        .ds-print-badge--in_progress { background: #dbeafe; color: #1d4ed8; }
        .ds-print-badge--deferred { background: #fef3c7; color: #a16207; }
        .ds-print-badge--out { background: #f1f5f9; color: #64748b; }

        .ds-print-empty {
            padding: 24px; text-align: center;
            color: var(--muted); font-size: 13px;
            border: 1px dashed var(--line);
        }

        .ds-print-foot {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid var(--line);
            font-size: 10.5px; color: #94a3b8;
            display: flex; justify-content: space-between; gap: 12px;
        }

        @media print {
            body.ds-print-body { background: #fff; }
            .ds-print-bar { display: none !important; }
            .ds-print-sheet {
                margin: 0; box-shadow: none; max-width: none; padding: 0;
            }
            .ds-print-hint { display: none !important; }
            .ds-print-section + .ds-print-section { page-break-before: always; }
            @page { size: A4 landscape; margin: 10mm 10mm 12mm; }
        }
    </style>
</head>
<body class="ds-print-body">
    <div class="ds-print-bar">
        <strong>개발범위 PDF</strong>
        <span class="meta"><?= e($projectName) ?> · <?= e($scopeLabel) ?> · <?= (int) $totalRows ?>행</span>
        <a class="ds-print-btn ds-print-btn--ghost" href="<?= url('dev-scope.php?print=1&scope=' . urlencode($printScope) . '&phase=' . urlencode($backPhase) . '&noprint=1') ?>">미리보기</a>
        <a class="ds-print-btn ds-print-btn--ghost" href="<?= url('dev-scope.php?phase=' . urlencode($backPhase)) ?>">← 돌아가기</a>
        <button type="button" class="ds-print-btn" id="dsPdfPrint">PDF로 저장</button>
    </div>

    <div class="ds-print-sheet">
        <header class="ds-print-head">
            <p class="ds-print-eyebrow">개발범위 · Development Scope</p>
            <h1><?= e($projectName) ?> — <?= e($scopeLabel) ?></h1>
            <p class="ds-print-meta">
                <span>총 <?= (int) $totalRows ?>항목</span>
                <span><?= e(date('Y.m.d H:i')) ?></span>
                <?php if ($printScope !== 'all' && !empty($printSections[0]['phase']['period'])): ?>
                <span><?= e($printSections[0]['phase']['period']) ?></span>
                <?php endif; ?>
            </p>
        </header>

        <?php foreach ($printSections as $sec): ?>
        <?php
            $phaseLabel = isset($sec['phase']['label']) ? $sec['phase']['label'] : $sec['phase_key'];
            $phasePeriod = isset($sec['phase']['period']) ? $sec['phase']['period'] : '';
            $rows = isset($sec['rows']) ? $sec['rows'] : array();
            $prevD1Id = null;
            $prevD2Id = null;
        ?>
        <section class="ds-print-section">
            <?php if ($printScope === 'all' || count($printSections) > 1): ?>
            <h2 class="ds-print-section-title">
                <?= e($phaseLabel) ?>
                <?php if ($phasePeriod !== ''): ?><span><?= e($phasePeriod) ?></span><?php endif; ?>
                <span><?= count($rows) ?>행</span>
            </h2>
            <?php endif; ?>

            <?php if (empty($rows)): ?>
            <div class="ds-print-empty">등록된 항목이 없습니다.</div>
            <?php else: ?>
            <table class="ds-print-table">
                <thead>
                    <tr>
                        <th class="col-d">D</th>
                        <th class="col-d1">구분</th>
                        <th class="col-d2">항목</th>
                        <th class="col-d3">내용</th>
                        <th class="col-prio">우선</th>
                        <th class="col-status">상태</th>
                        <th class="col-desc">설명</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                    <?php
                        $it = $row['item'];
                        $d = (int) $row['depth'];
                        $d1Id = isset($row['d1_id']) ? (int) $row['d1_id'] : 0;
                        $d2Id = isset($row['d2_id']) ? (int) $row['d2_id'] : 0;
                        $d1Changed = ($prevD1Id === null || $d1Id !== $prevD1Id);
                        $d2Changed = ($prevD2Id === null || $d2Id !== $prevD2Id || $d1Changed);
                        $showCtxD1 = ($d > 1 && $d1Changed);
                        $showCtxD2 = ($d === 3 && $d2Changed);

                        $title = isset($it['title']) ? $it['title'] : '';
                        $desc = isset($it['description']) ? $it['description'] : '';
                        $prio = isset($it['priority']) ? $it['priority'] : 'P1';
                        $st = isset($it['status']) ? $it['status'] : 'planned';
                        $stLabel = isset($statuses[$st]) ? $statuses[$st] : $st;

                        $rowStyles = DevScopeService::parseStyle(isset($it['style_json']) ? $it['style_json'] : null);
                        $titleStyle = DevScopeService::fieldStyleAttr($rowStyles, 'title');
                        $descStyle = DevScopeService::fieldStyleAttr($rowStyles, 'description');

                        $trClass = 'is-d' . $d;
                        if ($st === 'done') $trClass .= ' is-done';
                        if ($st === 'out') $trClass .= ' is-out';

                        $cellD1 = '';
                        $cellD2 = '';
                        $cellD3 = '';
                        if ($d === 1) {
                            $cellD1 = $title;
                        } elseif ($d === 2) {
                            $cellD2 = $title;
                            if ($showCtxD1) $cellD1 = isset($row['d1']) ? $row['d1'] : '';
                        } else {
                            $cellD3 = $title;
                            if ($showCtxD1) $cellD1 = isset($row['d1']) ? $row['d1'] : '';
                            if ($showCtxD2) $cellD2 = isset($row['d2']) ? $row['d2'] : '';
                        }
                    ?>
                    <tr class="<?= e($trClass) ?>">
                        <td class="col-d"><?= $d ?></td>
                        <td class="col-d1"<?= ($d === 1 && $titleStyle !== '') ? ' style="' . e($titleStyle) . '"' : '' ?>><?= e($cellD1) ?></td>
                        <td class="col-d2"<?= ($d === 2 && $titleStyle !== '') ? ' style="' . e($titleStyle) . '"' : '' ?>><?= e($cellD2) ?></td>
                        <td class="col-d3"<?= ($d === 3 && $titleStyle !== '') ? ' style="' . e($titleStyle) . '"' : '' ?>><?= e($cellD3) ?></td>
                        <td class="col-prio"><span class="ds-print-badge ds-print-badge--<?= e(strtolower($prio)) ?>"><?= e($prio) ?></span></td>
                        <td class="col-status"><span class="ds-print-badge ds-print-badge--<?= e($st) ?>"><?= e($stLabel) ?></span></td>
                        <td class="col-desc"<?= $descStyle !== '' ? ' style="' . e($descStyle) . '"' : '' ?>><?= e($desc) ?></td>
                    </tr>
                    <?php
                        $prevD1Id = $d1Id;
                        $prevD2Id = $d2Id;
                    ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>

        <footer class="ds-print-foot">
            <span>Label-UP · 개발범위</span>
            <span><?= e($projectName) ?> · <?= e($scopeLabel) ?> · <?= e(date('Y.m.d')) ?></span>
        </footer>
        <p class="ds-print-hint" style="margin-top:14px;font-size:11px;color:#94a3b8;text-align:center">
            브라우저 인쇄에서 「PDF로 저장」을 선택하세요. (가로 A4 권장)
        </p>
    </div>

    <script>
    (function () {
        function doPrint() {
            document.title = <?= json_encode($filename, JSON_UNESCAPED_UNICODE) ?>;
            window.print();
        }
        var btn = document.getElementById('dsPdfPrint');
        if (btn) btn.addEventListener('click', doPrint);
        <?php if ($autoprint): ?>
        window.addEventListener('load', function () { setTimeout(doPrint, 400); });
        <?php endif; ?>
    })();
    </script>
</body>
</html>
