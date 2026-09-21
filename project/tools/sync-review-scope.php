<?php
/**
 * 검수 시트: 타사포맷 3종 분리 + page_url 등록 (CLI/브라우저 1회)
 * 사용: php tools/sync-review-scope.php
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$project = ProjectService::getOrCreateDefault();
$pid = (int) $project['id'];
$user = function_exists('current_user') ? current_user() : null;
$uid = $user ? $user['id'] : null;

echo "project_id={$pid}\n";

$split = DevScopeService::splitVendorImportReviewItems($pid, $uid);
echo 'split=' . json_encode($split, JSON_UNESCAPED_UNICODE) . "\n";

$urls = DevScopeService::syncReviewPageUrls($pid, true, $uid);
echo 'urls=' . json_encode($urls, JSON_UNESCAPED_UNICODE) . "\n";

$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT id, depth, parent_id, title, page_url, sort_order
    FROM dev_scope_items
    WHERE project_id = ? AND phase_key = 'phase-1'
      AND (
        title LIKE '%폼텍%' OR title LIKE '%아이라벨%' OR title LIKE '%애니라벨%'
        OR title LIKE '공통%' OR title = '타사 포맷 가져오기'
        OR parent_id IN (
          SELECT id FROM (
            SELECT id FROM dev_scope_items
            WHERE project_id = ? AND phase_key = 'phase-1' AND depth = 2
              AND (
                title LIKE '폼텍%' OR title LIKE '아이라벨%' OR title LIKE '애니라벨%'
                OR title LIKE '공통%'
              )
          ) x
        )
      )
    ORDER BY
      CASE WHEN parent_id IS NULL THEN id ELSE parent_id END,
      depth, sort_order, id
");
$stmt->execute(array($pid, $pid));
foreach ($stmt->fetchAll() as $r) {
    echo sprintf(
        "%s d%d p%s | %s | %s\n",
        $r['id'],
        $r['depth'],
        $r['parent_id'] === null ? '-' : $r['parent_id'],
        $r['title'],
        isset($r['page_url']) ? $r['page_url'] : ''
    );
}

$cnt = $db->query("SELECT COUNT(*) FROM dev_scope_items WHERE phase_key='phase-1' AND page_url IS NOT NULL AND page_url<>''")->fetchColumn();
echo "phase-1 page_url filled={$cnt}\n";
echo "done\n";
