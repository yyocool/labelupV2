<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$user = current_user();
$categories = PolicyService::getCategories();
$statuses = PolicyService::getStatuses();
$audiences = PolicyService::getAudiences();

PolicyService::ensureDefaults($project['id']);

$filterCategory = isset($_GET['category']) ? $_GET['category'] : 'all';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && is_admin()) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'save') {
        $payload = array(
            'policy_key' => isset($_POST['policy_key']) ? $_POST['policy_key'] : '',
            'category' => isset($_POST['category']) ? $_POST['category'] : 'service',
            'title' => isset($_POST['title']) ? $_POST['title'] : '',
            'summary' => isset($_POST['summary']) ? $_POST['summary'] : '',
            'content' => isset($_POST['content']) ? $_POST['content'] : '',
            'version' => isset($_POST['version']) ? $_POST['version'] : '1.0',
            'status' => isset($_POST['status']) ? $_POST['status'] : 'draft',
            'audience' => isset($_POST['audience']) ? $_POST['audience'] : 'customer',
            'related_menu_code' => isset($_POST['related_menu_code']) ? $_POST['related_menu_code'] : '',
            'sort_order' => isset($_POST['sort_order']) ? $_POST['sort_order'] : 0,
        );
        $id = (int) (isset($_POST['policy_id']) ? $_POST['policy_id'] : 0);
        if ($id > 0) {
            $existing = PolicyService::getById($id);
            if ($existing && (int) $existing['project_id'] === (int) $project['id']) {
                PolicyService::update($id, $payload, $user ? $user['id'] : null);
                flash('success', '정책이 수정되었습니다.');
            } else {
                flash('error', '수정할 정책을 찾을 수 없습니다.');
            }
        } else {
            PolicyService::create($project['id'], $payload, $user ? $user['id'] : null);
            flash('success', '정책이 등록되었습니다.');
        }
        redirect('policies.php' . self_build_policy_query($filterCategory, $filterStatus));
    }

    if ($action === 'delete') {
        $id = (int) (isset($_POST['policy_id']) ? $_POST['policy_id'] : 0);
        $existing = PolicyService::getById($id);
        if ($existing && (int) $existing['project_id'] === (int) $project['id']) {
            PolicyService::delete($id);
            flash('success', '정책이 삭제되었습니다.');
        } else {
            flash('error', '삭제할 정책을 찾을 수 없습니다.');
        }
        redirect('policies.php' . self_build_policy_query($filterCategory, $filterStatus));
    }

    if ($action === 'reseed') {
        $count = PolicyService::seedDefaults($project['id'], $user ? $user['id'] : null, true);
        flash('success', '기본 정책 ' . $count . '건이 다시 등록되었습니다.');
        redirect('policies.php');
    }
}

function self_build_policy_query($category, $status)
{
    $q = array();
    if ($category && $category !== 'all') {
        $q['category'] = $category;
    }
    if ($status && $status !== 'all') {
        $q['status'] = $status;
    }
    return $q ? '?' . http_build_query($q) : '';
}

$policies = PolicyService::getByProject($project['id'], $filterCategory, $filterStatus);
$editPolicy = $editId ? PolicyService::getById($editId) : null;
if ($editPolicy && (int) $editPolicy['project_id'] !== (int) $project['id']) {
    $editPolicy = null;
}

$categoryCounts = array('all' => PolicyService::countByProject($project['id']));
foreach ($categories as $key => $meta) {
    $categoryCounts[$key] = 0;
}
$allPolicies = PolicyService::getByProject($project['id']);
foreach ($allPolicies as $p) {
    if (isset($categoryCounts[$p['category']])) {
        $categoryCounts[$p['category']]++;
    }
}

$pageTitle = '정책관리';
$currentPage = 'policies';

$printMode = isset($_GET['print']) && $_GET['print'] === '1';
if ($printMode) {
    include __DIR__ . '/views/policies-print.php';
    exit;
}

render_page(__DIR__ . '/views/policies.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker',
    'categories', 'statuses', 'audiences', 'policies', 'allPolicies',
    'filterCategory', 'filterStatus', 'categoryCounts', 'editPolicy'
));
