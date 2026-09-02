<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
extract(init_project_context());

$issueId = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$issue = IssueService::getById($issueId);
if (!$issue || $issue['project_id'] != $project['id']) {
    flash('error', '이슈를 찾을 수 없습니다.');
    redirect('issues.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = isset($_POST['action']) ? $_POST['action'] : 'update';
    if ($action === 'update') {
        IssueService::update($issueId, $_POST);
        log_activity($project['id'], current_user()['id'], 'issue_update', 'issue', $issueId, '이슈 수정: ' . $_POST['title']);
        flash('success', '이슈가 수정되었습니다.');
    } elseif ($action === 'comment') {
        IssueService::addComment($issueId, current_user()['id'], trim($_POST['content']));
        flash('success', '댓글이 등록되었습니다.');
    }
    redirect('issue-detail.php?id=' . $issueId);
}

$comments = IssueService::getComments($issueId);
$users = get_all_users();
$menus = MenuService::getByProject($project['id']);
$menuTree = build_menu_tree($menus);

$pageTitle = $issue['title'];
$currentPage = 'issues';

render_page(__DIR__ . '/views/issue_detail.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'issue', 'comments', 'users', 'menus'
));
