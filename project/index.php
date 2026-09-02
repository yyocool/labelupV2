<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}
require_login();

extract(init_project_context());
ProjectService::updateProgress($project['id']);
$project = ProjectService::getById($project['id']);
$phaseTracker = ProjectService::getPhaseTracker($project['id']);
$stats = ProjectService::getDashboardStats($project['id']);
$activities = ProjectService::getRecentActivities($project['id']);
$issues = IssueService::getByProject($project['id']);
$openIssues = array_filter($issues, function ($i) {
    return in_array($i['status'], array('open', 'in_progress'));
});
$recentIssues = array_slice($openIssues, 0, 5);

$db = Database::getConnection();
$milestones = $db->prepare('SELECT * FROM milestones WHERE project_id = ? ORDER BY due_date LIMIT 5');
$milestones->execute(array($project['id']));
$milestones = $milestones->fetchAll();

$notices = $db->prepare('SELECT n.*, u.name as author FROM notices n LEFT JOIN users u ON u.id = n.created_by WHERE n.project_id = ? ORDER BY n.is_pinned DESC, n.created_at DESC LIMIT 3');
$notices->execute(array($project['id']));
$notices = $notices->fetchAll();

$phaseStats = $stats['phaseStats'];
$phases = ProjectService::getProgressBreakdown($project['id']);

ScheduleTaskService::seedDefaultTimeline($project['id']);
$gantt = ScheduleTaskService::buildGanttModel($project['id'], $project);

$pageTitle = '대시보드';
$currentPage = 'dashboard';

render_page(__DIR__ . '/views/dashboard.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker', 'stats', 'activities',
    'recentIssues', 'milestones', 'notices', 'phases', 'gantt'
));
