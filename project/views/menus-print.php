<?php
/**
 * 메뉴 구성도 PDF(인쇄) 전용 페이지
 */
require_once APP_ROOT . '/includes/print_layout.php';

$filename = '메뉴구성_' . date('Ymd');
$projectName = isset($project['name']) ? $project['name'] : 'Label-UP';
$tree = isset($menuTree) ? $menuTree : array();

$flat = array();
$walk = function ($nodes, $depth) use (&$walk, &$flat) {
    foreach ($nodes as $item) {
        $flat[] = array(
            'depth' => $depth,
            'code' => menu_display_code($item),
            'title' => isset($item['title']) ? $item['title'] : '',
            'status' => isset($item['storyboard_status']) ? $item['storyboard_status'] : 'pending',
            'pct' => isset($item['progress_pct']) ? (int) $item['progress_pct'] : 0,
        );
        if (!empty($item['children'])) {
            $walk($item['children'], $depth + 1);
        }
    }
};
$walk($tree, 0);

$statusLabels = array(
    'pending' => '대기',
    'in_progress' => '진행',
    'review' => '검토',
    'done' => '완료',
    'completed' => '완료',
);

$viewQs = ($menuView === 'tree') ? '?view=tree' : '';
$previewExtra = ($menuView === 'tree') ? '&view=tree' : '';

print_layout_start(array(
    'title' => '메뉴 구성도 PDF',
    'filename' => $filename,
    'meta' => $projectName . ' · ' . count($flat) . '개 메뉴',
    'back_url' => url('menus.php' . $viewQs),
    'preview_url' => url('menus.php?print=1&noprint=1' . $previewExtra),
    'stylesheet' => false,
    'landscape' => true,
    'max_width' => '1100px',
));
?>
        <header class="print-doc-head">
            <p class="eyebrow">Menu Structure</p>
            <h1>메뉴 구성도</h1>
            <p class="sub">다단계 메뉴 구조 및 진행상황 · 총 <?= count($flat) ?>개 · <?= e(date('Y.m.d H:i')) ?></p>
        </header>

        <?php if (empty($flat)): ?>
        <p style="color:#64748b;font-size:14px">등록된 메뉴가 없습니다.</p>
        <?php else: ?>
        <table class="print-menu-table">
            <thead>
                <tr>
                    <th style="width:110px">메뉴코드</th>
                    <th>메뉴명</th>
                    <th style="width:70px">깊이</th>
                    <th style="width:80px">진행률</th>
                    <th style="width:90px">상태</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($flat as $row): ?>
                <?php
                    $st = $row['status'];
                    $stLabel = isset($statusLabels[$st]) ? $statusLabels[$st] : $st;
                    $pad = str_repeat('· ', (int) $row['depth']);
                ?>
                <tr>
                    <td class="code"><?= e($row['code'] !== '' ? $row['code'] : '—') ?></td>
                    <td>
                        <?php if ($pad !== ''): ?><span class="indent"><?= e($pad) ?></span><?php endif; ?>
                        <strong><?= e($row['title']) ?></strong>
                    </td>
                    <td>D<?= (int) $row['depth'] + 1 ?></td>
                    <td><?= (int) $row['pct'] ?>%</td>
                    <td><?= e($stLabel) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
<?php
print_layout_end(array(
    'foot_left' => '메뉴 구성도',
    'foot_right' => date('Y.m.d'),
));
