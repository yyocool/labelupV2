<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/design_drafts.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$designDrafts = design_draft_list();

$pageTitle = '디자인';
$currentPage = 'design';

render_page(__DIR__ . '/views/design.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker', 'designDrafts'
));
