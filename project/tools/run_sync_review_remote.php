#!/usr/bin/env php
<?php
$_SERVER['HTTP_HOST'] = 'labelup.gagamkorea.kr';
require '/home/labelup/project/includes/helpers.php';
require '/home/labelup/project/includes/Database.php';
require '/home/labelup/project/includes/ProjectService.php';
require '/home/labelup/project/includes/DevScopeService.php';

$cfg = require '/home/labelup/project/config/database.local.php';
$dsn = sprintf(
    'mysql:host=127.0.0.1;port=%d;dbname=%s;charset=utf8mb4',
    (int) (isset($cfg['port']) ? $cfg['port'] : 3306),
    $cfg['dbname']
);
$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
));
$ref = new ReflectionClass('Database');
$prop = $ref->getProperty('instance');
$prop->setAccessible(true);
$prop->setValue(null, $pdo);

$project = ProjectService::getOrCreateDefault();
$pid = (int) $project['id'];
echo "project_id={$pid}\n";

$split = DevScopeService::splitVendorImportReviewItems($pid, null);
echo 'split=' . json_encode($split, JSON_UNESCAPED_UNICODE) . "\n";

$urls = DevScopeService::syncReviewPageUrls($pid, true, null);
echo 'urls=' . json_encode($urls, JSON_UNESCAPED_UNICODE) . "\n";

$sql = "
SELECT id, depth, parent_id, title, page_url
FROM dev_scope_items
WHERE phase_key = 'phase-1'
  AND (
    title LIKE '%폼텍%' OR title LIKE '%아이라벨%' OR title LIKE '%애니라벨%'
    OR title LIKE '공통%' OR title = '타사 포맷 가져오기'
    OR parent_id IN (
      SELECT id FROM (
        SELECT id FROM dev_scope_items
        WHERE phase_key = 'phase-1' AND depth = 2
          AND (title LIKE '폼텍%' OR title LIKE '아이라벨%' OR title LIKE '애니라벨%' OR title LIKE '공통%')
      ) x
    )
  )
ORDER BY COALESCE(parent_id, id), depth, sort_order, id
";
foreach ($pdo->query($sql) as $r) {
    $p = $r['parent_id'] ? $r['parent_id'] : '-';
    echo $r['id'] . " d{$r['depth']} p{$p} | {$r['title']} | {$r['page_url']}\n";
}
$cnt = $pdo->query("SELECT COUNT(*) FROM dev_scope_items WHERE phase_key='phase-1' AND page_url IS NOT NULL AND page_url<>''")->fetchColumn();
echo "filled={$cnt}\n";
