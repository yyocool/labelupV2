<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

// 자료 등록은 로그인 사용자 전원 가능 (역할 무관)
$canUpload = true;
$user = current_user();
$currentUserId = $user ? (int) $user['id'] : 0;

$categories = ArchiveService::getCategories();
$filter = isset($_GET['category']) ? $_GET['category'] : 'all';
$redirectUrl = 'archive.php' . ($filter !== 'all' ? '?category=' . urlencode($filter) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'upload' && $canUpload) {
        try {
            ArchiveService::create($project['id'], $_POST, isset($_FILES['document']) ? $_FILES['document'] : array());
            flash('success', '자료가 등록되었습니다.');
        } catch (Exception $e) {
            flash('error', $e->getMessage());
        }
        redirect($redirectUrl);
    }

    if ($action === 'update') {
        $docId = (int) (isset($_POST['document_id']) ? $_POST['document_id'] : 0);
        $doc = ArchiveService::getById($docId);
        if (!$doc || (int) $doc['project_id'] !== (int) $project['id']) {
            flash('error', '수정할 자료를 찾을 수 없습니다.');
        } elseif (!ArchiveService::canManage($doc, $currentUserId)) {
            flash('error', '본인이 올린 자료만 수정할 수 있습니다.');
        } else {
            try {
                ArchiveService::update($docId, $_POST, isset($_FILES['document']) ? $_FILES['document'] : array());
                flash('success', '자료가 수정되었습니다.');
            } catch (Exception $e) {
                flash('error', $e->getMessage());
            }
        }
        redirect($redirectUrl);
    }

    if ($action === 'delete') {
        $docId = (int) (isset($_POST['document_id']) ? $_POST['document_id'] : 0);
        $doc = ArchiveService::getById($docId);
        if (!$doc || (int) $doc['project_id'] !== (int) $project['id']) {
            flash('error', '삭제할 자료를 찾을 수 없습니다.');
        } elseif (!ArchiveService::canManage($doc, $currentUserId)) {
            flash('error', '본인이 올린 자료만 삭제할 수 있습니다.');
        } else {
            ArchiveService::delete($docId);
            flash('success', '자료가 삭제되었습니다.');
        }
        redirect($redirectUrl);
    }
}

$documents = ArchiveService::getByProject($project['id'], $filter === 'all' ? null : $filter);
$allDocuments = ArchiveService::getByProject($project['id']);
$categoryCounts = array('all' => count($allDocuments));
foreach ($categories as $key => $meta) {
    $categoryCounts[$key] = 0;
}
foreach ($allDocuments as $doc) {
    if (isset($categoryCounts[$doc['category']])) {
        $categoryCounts[$doc['category']]++;
    }
}
$grouped = ArchiveService::groupByCategory($documents);
$totalCount = count($allDocuments);

$pageTitle = '자료실';
$currentPage = 'archive';

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
}
if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__DIR__ . '/views/archive.php', true);
    @opcache_invalidate(__FILE__, true);
}

render_page(__DIR__ . '/views/archive.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker',
    'categories', 'filter', 'grouped', 'totalCount', 'categoryCounts',
    'canUpload', 'currentUserId'
));
