<?php
/**
 * 이력서 관리 (메뉴 미등록 · URL 직접 접근)
 * /project/resume.php
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$user = current_user();
$userId = $user ? $user['id'] : null;
$canEdit = true;
$categories = ResumeService::getCategories();

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$viewId = isset($_GET['view']) ? (int) $_GET['view'] : 0;
$printMode = isset($_GET['print']) && $_GET['print'] === '1';

$isAjax = (isset($_POST['ajax']) && (string) $_POST['ajax'] === '1')
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

$resume_json = function ($ok, $payload = array(), $code = 200) {
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(array_merge(array('ok' => (bool) $ok), $payload), JSON_UNESCAPED_UNICODE);
    exit;
};

$parse_ordered_ids = function ($raw) {
    if (is_array($raw)) {
        return array_values(array_filter(array_map('intval', $raw)));
    }
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('intval', $decoded)));
        }
        return array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', $raw))));
    }
    return array();
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isAjax && !verify_csrf()) {
        $resume_json(false, array('error' => '보안 토큰이 만료되었습니다. 새로고침 후 다시 시도해 주세요.', 'code' => 'csrf'), 403);
    }
    if ($isAjax && !$canEdit) {
        $resume_json(false, array('error' => '편집 권한이 없습니다.'), 403);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && $canEdit) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    try {
        if ($action === 'reorder_people') {
            $orderedIds = $parse_ordered_ids(isset($_POST['ordered_ids']) ? $_POST['ordered_ids'] : '');
            $result = ResumeService::reorderPeople($project['id'], $orderedIds);
            if ($isAjax) {
                $resume_json(true, $result);
            }
            flash('success', '순서가 저장되었습니다.');
            redirect('resume.php');
        }

        if ($action === 'reorder_entries') {
            $personId = (int) (isset($_POST['person_id']) ? $_POST['person_id'] : 0);
            $category = isset($_POST['category']) ? $_POST['category'] : '';
            $person = ResumeService::getPerson($personId);
            if (!$person || (int) $person['project_id'] !== (int) $project['id']) {
                throw new InvalidArgumentException('인물을 찾을 수 없습니다.');
            }
            $orderedIds = $parse_ordered_ids(isset($_POST['ordered_ids']) ? $_POST['ordered_ids'] : '');
            $result = ResumeService::reorderEntries($personId, $category, $orderedIds);
            if ($isAjax) {
                $resume_json(true, $result);
            }
            flash('success', '순서가 저장되었습니다.');
            redirect('resume.php?edit=' . $personId);
        }

        if ($action === 'save_person') {
            $payload = array(
                'name' => isset($_POST['name']) ? $_POST['name'] : '',
                'job_title' => isset($_POST['job_title']) ? $_POST['job_title'] : '',
                'organization' => isset($_POST['organization']) ? $_POST['organization'] : '',
                'email' => isset($_POST['email']) ? $_POST['email'] : '',
                'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
                'summary' => isset($_POST['summary']) ? $_POST['summary'] : '',
                'skills' => isset($_POST['skills']) ? $_POST['skills'] : '',
            );
            $id = (int) (isset($_POST['person_id']) ? $_POST['person_id'] : 0);
            if ($id > 0) {
                $existing = ResumeService::getPerson($id);
                if (!$existing || (int) $existing['project_id'] !== (int) $project['id']) {
                    throw new InvalidArgumentException('수정할 인물을 찾을 수 없습니다.');
                }
                ResumeService::updatePerson($id, $payload, $userId);
                flash('success', '인물 정보가 저장되었습니다.');
                redirect('resume.php?edit=' . $id);
            }
            $newId = ResumeService::createPerson($project['id'], $payload, $userId);
            flash('success', '인물이 추가되었습니다.');
            redirect('resume.php?edit=' . $newId);
        }

        if ($action === 'delete_person') {
            $id = (int) (isset($_POST['person_id']) ? $_POST['person_id'] : 0);
            $existing = ResumeService::getPerson($id);
            if (!$existing || (int) $existing['project_id'] !== (int) $project['id']) {
                throw new InvalidArgumentException('삭제할 인물을 찾을 수 없습니다.');
            }
            ResumeService::deletePerson($id);
            flash('success', '인물과 이력이 삭제되었습니다.');
            redirect('resume.php');
        }

        if ($action === 'save_entry') {
            $personId = (int) (isset($_POST['person_id']) ? $_POST['person_id'] : 0);
            $person = ResumeService::getPerson($personId);
            if (!$person || (int) $person['project_id'] !== (int) $project['id']) {
                throw new InvalidArgumentException('인물을 찾을 수 없습니다.');
            }
            $payload = array(
                'category' => isset($_POST['category']) ? $_POST['category'] : '',
                'title' => isset($_POST['title']) ? $_POST['title'] : '',
                'organization' => isset($_POST['organization']) ? $_POST['organization'] : '',
                'period_start' => isset($_POST['period_start']) ? $_POST['period_start'] : '',
                'period_end' => isset($_POST['period_end']) ? $_POST['period_end'] : '',
                'is_current' => !empty($_POST['is_current']) ? 1 : 0,
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
            );
            $entryId = (int) (isset($_POST['entry_id']) ? $_POST['entry_id'] : 0);
            if ($entryId > 0) {
                $entry = ResumeService::getEntry($entryId);
                if (!$entry || (int) $entry['person_id'] !== $personId) {
                    throw new InvalidArgumentException('이력을 찾을 수 없습니다.');
                }
                ResumeService::updateEntry($entryId, $payload);
                flash('success', '이력이 수정되었습니다.');
            } else {
                ResumeService::createEntry($personId, $payload);
                flash('success', '이력이 추가되었습니다.');
            }
            redirect('resume.php?edit=' . $personId . '#cat-' . urlencode($payload['category']));
        }

        if ($action === 'delete_entry') {
            $entryId = (int) (isset($_POST['entry_id']) ? $_POST['entry_id'] : 0);
            $personId = (int) (isset($_POST['person_id']) ? $_POST['person_id'] : 0);
            $entry = ResumeService::getEntry($entryId);
            $person = ResumeService::getPerson($personId);
            if (!$person || (int) $person['project_id'] !== (int) $project['id']) {
                throw new InvalidArgumentException('인물을 찾을 수 없습니다.');
            }
            if (!$entry || (int) $entry['person_id'] !== $personId) {
                throw new InvalidArgumentException('이력을 찾을 수 없습니다.');
            }
            ResumeService::deleteEntry($entryId);
            flash('success', '이력이 삭제되었습니다.');
            redirect('resume.php?edit=' . $personId);
        }
    } catch (Exception $e) {
        if (!empty($isAjax)) {
            $resume_json(false, array('error' => $e->getMessage()), 500);
        }
        flash('error', $e->getMessage());
        if ($editId > 0) {
            redirect('resume.php?edit=' . $editId);
        }
        if ($viewId > 0) {
            redirect('resume.php?view=' . $viewId);
        }
        redirect('resume.php');
    }
}

$people = ResumeService::listPeople($project['id'], $search);
$totalCount = ResumeService::countPeople($project['id']);

$editPerson = null;
$editEntries = array();
if ($editId > 0) {
    $editPerson = ResumeService::getPerson($editId);
    if (!$editPerson || (int) $editPerson['project_id'] !== (int) $project['id']) {
        $editPerson = null;
        flash('error', '인물을 찾을 수 없습니다.');
        redirect('resume.php');
    }
    $editEntries = ResumeService::getEntriesGrouped($editId);
}

$viewPerson = null;
$viewEntries = array();
if ($viewId > 0) {
    $viewPerson = ResumeService::getPerson($viewId);
    if (!$viewPerson || (int) $viewPerson['project_id'] !== (int) $project['id']) {
        $viewPerson = null;
        flash('error', '인물을 찾을 수 없습니다.');
        redirect('resume.php');
    }
    $viewEntries = ResumeService::getEntriesGrouped($viewId);
}

$pageTitle = '이력서 관리';
$currentPage = 'resume';

if ($printMode && $viewPerson) {
    include __DIR__ . '/views/resume-print.php';
    exit;
}

render_page(__DIR__ . '/views/resume.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker',
    'people', 'totalCount', 'search', 'canEdit', 'categories',
    'editPerson', 'editEntries', 'viewPerson', 'viewEntries'
));
