<?php
/**
 * menu_seed_tree.php 기준 storyboard 스텁 일괄 생성
 * 사용: php storyboard/build_stubs.php
 */
$tree = require dirname(__DIR__) . '/includes/data/menu_seed_tree.php';
$outDir = __DIR__;

function build_stub_content($menuCode, $title)
{
    $name = addslashes($title);
    return <<<PHP
<?php
/**
 * 스토리보드: {$title}
 * 메뉴코드: {$menuCode}
 *
 * @var array \$menu
 * @var array|null \$storyboard
 */
?>
<div class="sb-page sb-page--placeholder">
    <div class="sb-page-meta">
        <span class="sb-page-code"><?= e(isset(\$menu['menu_code']) ? \$menu['menu_code'] : '{$menuCode}') ?></span>
        <h2 class="sb-page-title"><?= e(isset(\$menu['title']) ? \$menu['title'] : '{$name}') ?></h2>
    </div>
    <p class="sb-page-notice">스토리보드 작업 예정입니다.</p>
    <p class="sb-page-hint">이 파일(<code>storyboard/{$menuCode}.php</code>)을 직접 편집하여 화면을 구성하세요.</p>
</div>

PHP;
}

function collect_menu_codes(array $nodes, $parentCode, array &$items)
{
    $index = 1;
    foreach ($nodes as $node) {
        $segment = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
        $code = ($parentCode === null) ? $segment : $parentCode . '-' . $segment;
        $items[$code] = $node['title'];
        if (!empty($node['children'])) {
            collect_menu_codes($node['children'], $code, $items);
        }
        $index++;
    }
}

$items = array();
collect_menu_codes($tree, null, $items);

$valid = array_keys($items);
foreach (glob($outDir . '/*.php') as $file) {
    $code = basename($file, '.php');
    if ($file === __FILE__ || basename($file) === 'build_stubs.php') {
        continue;
    }
    if (!in_array($code, $valid, true)) {
        unlink($file);
    }
}

$created = 0;
$updated = 0;
foreach ($items as $code => $title) {
    $path = $outDir . '/' . $code . '.php';
    $isNew = !file_exists($path);
    file_put_contents($path, build_stub_content($code, $title));
    if ($isNew) {
        $created++;
    } else {
        $updated++;
    }
}

echo 'Storyboard stubs: created=' . $created . ', updated=' . $updated . ', total=' . count($items) . PHP_EOL;
