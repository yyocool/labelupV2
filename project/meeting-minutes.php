<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$user = current_user();
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$viewId = isset($_GET['view']) ? (int) $_GET['view'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && is_admin()) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'save') {
        $payload = array(
            'title' => isset($_POST['title']) ? $_POST['title'] : '',
            'meeting_date' => isset($_POST['meeting_date']) ? $_POST['meeting_date'] : '',
            'meeting_time' => isset($_POST['meeting_time']) ? $_POST['meeting_time'] : '',
            'location' => isset($_POST['location']) ? $_POST['location'] : '',
            'attendees' => isset($_POST['attendees']) ? $_POST['attendees'] : '',
            'agenda' => isset($_POST['agenda']) ? $_POST['agenda'] : '',
            'content' => isset($_POST['content']) ? $_POST['content'] : '',
        );
        if (rich_html_is_empty($payload['content'])) {
            flash('error', '회의 내용을 입력하세요.');
            redirect('meeting-minutes.php' . ($search !== '' ? '?q=' . urlencode($search) : ''));
        }
        $id = (int) (isset($_POST['minute_id']) ? $_POST['minute_id'] : 0);
        if ($id > 0) {
            $existing = MeetingMinutesService::getById($id);
            if ($existing && (int) $existing['project_id'] === (int) $project['id']) {
                MeetingMinutesService::update($id, $payload, $user ? $user['id'] : null);
                flash('success', '회의록이 수정되었습니다.');
            } else {
                flash('error', '수정할 회의록을 찾을 수 없습니다.');
            }
        } else {
            MeetingMinutesService::create($project['id'], $payload, $user ? $user['id'] : null);
            flash('success', '회의록이 등록되었습니다.');
        }
        redirect('meeting-minutes.php' . ($search !== '' ? '?q=' . urlencode($search) : ''));
    }

    if ($action === 'delete') {
        $id = (int) (isset($_POST['minute_id']) ? $_POST['minute_id'] : 0);
        $existing = MeetingMinutesService::getById($id);
        if ($existing && (int) $existing['project_id'] === (int) $project['id']) {
            MeetingMinutesService::delete($id);
            flash('success', '회의록이 삭제되었습니다.');
        } else {
            flash('error', '삭제할 회의록을 찾을 수 없습니다.');
        }
        redirect('meeting-minutes.php' . ($search !== '' ? '?q=' . urlencode($search) : ''));
    }
}

$minutes = MeetingMinutesService::getByProject($project['id'], $search);
$totalCount = MeetingMinutesService::countByProject($project['id']);

$editMinute = $editId ? MeetingMinutesService::getById($editId) : null;
if ($editMinute && (int) $editMinute['project_id'] !== (int) $project['id']) {
    $editMinute = null;
}

$viewMinute = $viewId ? MeetingMinutesService::getById($viewId) : null;
if ($viewMinute && (int) $viewMinute['project_id'] !== (int) $project['id']) {
    $viewMinute = null;
}

$pageTitle = '회의록';
$currentPage = 'meeting-minutes';
$extraHead = is_admin()
    ? '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">'
    : '';

render_page(__DIR__ . '/views/meeting-minutes.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker', 'extraHead',
    'minutes', 'totalCount', 'search', 'editMinute', 'viewMinute'
));
