<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$project = ProjectService::getOrCreateDefault();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $db = Database::getConnection();
    $db->prepare('UPDATE projects SET name=?, description=?, client_name=?, start_date=?, end_date=?, status=? WHERE id=?')
       ->execute([
           $_POST['name'],
           isset($_POST['description']) ? $_POST['description'] : null,
           isset($_POST['client_name']) ? $_POST['client_name'] : null,
           $_POST['start_date'] ?: null,
           $_POST['end_date'] ?: null,
           isset($_POST['status']) ? $_POST['status'] : 'active',
           $project['id'],
       ]);
    flash('success', '프로젝트 설정이 저장되었습니다.');
    admin_redirect('projects.php');
}

$project = ProjectService::getById($project['id']);
$env = app_config('environment', 'local');

$pageTitle = '프로젝트 설정';
$currentPage = 'projects';

render_admin_page(__DIR__ . '/views/projects.php', compact('pageTitle', 'currentPage', 'project', 'env'));
