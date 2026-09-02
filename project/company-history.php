<?php
/**
 * 회사 연혁 관리 (메뉴 미등록 · URL 직접 접근)
 * /project/company-history.php
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
$eventCategories = CompanyHistoryService::getEventCategories();
$achievementCategories = CompanyHistoryService::getAchievementCategories();

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$viewId = isset($_GET['view']) ? (int) $_GET['view'] : 0;
$printMode = isset($_GET['print']) && $_GET['print'] === '1';

$isAjax = (isset($_POST['ajax']) && (string) $_POST['ajax'] === '1')
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

$ch_json = function ($ok, $payload = array(), $code = 200) {
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
        $ch_json(false, array('error' => '보안 토큰이 만료되었습니다. 새로고침 후 다시 시도해 주세요.', 'code' => 'csrf'), 403);
    }
    if ($isAjax && !$canEdit) {
        $ch_json(false, array('error' => '편집 권한이 없습니다.'), 403);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && $canEdit) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    try {
        if ($action === 'reorder_companies') {
            $orderedIds = $parse_ordered_ids(isset($_POST['ordered_ids']) ? $_POST['ordered_ids'] : '');
            $result = CompanyHistoryService::reorderCompanies($project['id'], $orderedIds);
            if ($isAjax) {
                $ch_json(true, $result);
            }
            flash('success', '순서가 저장되었습니다.');
            redirect('company-history.php');
        }

        if ($action === 'reorder_events') {
            $companyId = (int) (isset($_POST['company_id']) ? $_POST['company_id'] : 0);
            $company = CompanyHistoryService::getCompany($companyId);
            if (!$company || (int) $company['project_id'] !== (int) $project['id']) {
                throw new InvalidArgumentException('회사를 찾을 수 없습니다.');
            }
            $orderedIds = $parse_ordered_ids(isset($_POST['ordered_ids']) ? $_POST['ordered_ids'] : '');
            $result = CompanyHistoryService::reorderEvents($companyId, $orderedIds);
            if ($isAjax) {
                $ch_json(true, $result);
            }
            flash('success', '순서가 저장되었습니다.');
            redirect('company-history.php?edit=' . $companyId);
        }

        if ($action === 'reorder_achievements') {
            $companyId = (int) (isset($_POST['company_id']) ? $_POST['company_id'] : 0);
            $company = CompanyHistoryService::getCompany($companyId);
            if (!$company || (int) $company['project_id'] !== (int) $project['id']) {
                throw new InvalidArgumentException('회사를 찾을 수 없습니다.');
            }
            $orderedIds = $parse_ordered_ids(isset($_POST['ordered_ids']) ? $_POST['ordered_ids'] : '');
            $result = CompanyHistoryService::reorderAchievements($companyId, $orderedIds);
            if ($isAjax) {
                $ch_json(true, $result);
            }
            flash('success', '순서가 저장되었습니다.');
            redirect('company-history.php?edit=' . $companyId);
        }

        if ($action === 'save_company') {
            $payload = array(
                'name' => isset($_POST['name']) ? $_POST['name'] : '',
                'founded_year' => isset($_POST['founded_year']) ? $_POST['founded_year'] : '',
                'industry' => isset($_POST['industry']) ? $_POST['industry'] : '',
                'website' => isset($_POST['website']) ? $_POST['website'] : '',
                'summary' => isset($_POST['summary']) ? $_POST['summary'] : '',
            );
            $id = (int) (isset($_POST['company_id']) ? $_POST['company_id'] : 0);
            if ($id > 0) {
                $existing = CompanyHistoryService::getCompany($id);
                if (!$existing || (int) $existing['project_id'] !== (int) $project['id']) {
                    throw new InvalidArgumentException('수정할 회사를 찾을 수 없습니다.');
                }
                CompanyHistoryService::updateCompany($id, $payload, $userId);
                flash('success', '회사 정보가 저장되었습니다.');
                redirect('company-history.php?edit=' . $id);
            }
            $newId = CompanyHistoryService::createCompany($project['id'], $payload, $userId);
            flash('success', '회사가 추가되었습니다.');
            redirect('company-history.php?edit=' . $newId);
        }

        if ($action === 'delete_company') {
            $id = (int) (isset($_POST['company_id']) ? $_POST['company_id'] : 0);
            $existing = CompanyHistoryService::getCompany($id);
            if (!$existing || (int) $existing['project_id'] !== (int) $project['id']) {
                throw new InvalidArgumentException('삭제할 회사를 찾을 수 없습니다.');
            }
            CompanyHistoryService::deleteCompany($id);
            flash('success', '회사와 연혁이 삭제되었습니다.');
            redirect('company-history.php');
        }

        if ($action === 'save_event') {
            $companyId = (int) (isset($_POST['company_id']) ? $_POST['company_id'] : 0);
            $company = CompanyHistoryService::getCompany($companyId);
            if (!$company || (int) $company['project_id'] !== (int) $project['id']) {
                throw new InvalidArgumentException('회사를 찾을 수 없습니다.');
            }
            $payload = array(
                'category' => isset($_POST['category']) ? $_POST['category'] : 'other',
                'event_year' => isset($_POST['event_year']) ? $_POST['event_year'] : '',
                'event_month' => isset($_POST['event_month']) ? $_POST['event_month'] : '',
                'title' => isset($_POST['title']) ? $_POST['title'] : '',
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
            );
            $eventId = (int) (isset($_POST['event_id']) ? $_POST['event_id'] : 0);
            if ($eventId > 0) {
                $event = CompanyHistoryService::getEvent($eventId);
                if (!$event || (int) $event['company_id'] !== $companyId) {
                    throw new InvalidArgumentException('연혁을 찾을 수 없습니다.');
                }
                CompanyHistoryService::updateEvent($eventId, $payload);
                flash('success', '연혁이 수정되었습니다.');
            } else {
                CompanyHistoryService::createEvent($companyId, $payload);
                flash('success', '연혁이 추가되었습니다.');
            }
            redirect('company-history.php?edit=' . $companyId);
        }

        if ($action === 'delete_event') {
            $eventId = (int) (isset($_POST['event_id']) ? $_POST['event_id'] : 0);
            $companyId = (int) (isset($_POST['company_id']) ? $_POST['company_id'] : 0);
            $event = CompanyHistoryService::getEvent($eventId);
            $company = CompanyHistoryService::getCompany($companyId);
            if (!$company || (int) $company['project_id'] !== (int) $project['id']) {
                throw new InvalidArgumentException('회사를 찾을 수 없습니다.');
            }
            if (!$event || (int) $event['company_id'] !== $companyId) {
                throw new InvalidArgumentException('연혁을 찾을 수 없습니다.');
            }
            CompanyHistoryService::deleteEvent($eventId);
            flash('success', '연혁이 삭제되었습니다.');
            redirect('company-history.php?edit=' . $companyId);
        }

        if ($action === 'save_achievement') {
            $companyId = (int) (isset($_POST['company_id']) ? $_POST['company_id'] : 0);
            $company = CompanyHistoryService::getCompany($companyId);
            if (!$company || (int) $company['project_id'] !== (int) $project['id']) {
                throw new InvalidArgumentException('회사를 찾을 수 없습니다.');
            }
            $payload = array(
                'category' => isset($_POST['category']) ? $_POST['category'] : 'project',
                'title' => isset($_POST['title']) ? $_POST['title'] : '',
                'client' => isset($_POST['client']) ? $_POST['client'] : '',
                'metric' => isset($_POST['metric']) ? $_POST['metric'] : '',
                'achieved_year' => isset($_POST['achieved_year']) ? $_POST['achieved_year'] : '',
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
            );
            $achievementId = (int) (isset($_POST['achievement_id']) ? $_POST['achievement_id'] : 0);
            if ($achievementId > 0) {
                $row = CompanyHistoryService::getAchievement($achievementId);
                if (!$row || (int) $row['company_id'] !== $companyId) {
                    throw new InvalidArgumentException('실적을 찾을 수 없습니다.');
                }
                CompanyHistoryService::updateAchievement($achievementId, $payload);
                flash('success', '실적이 수정되었습니다.');
            } else {
                CompanyHistoryService::createAchievement($companyId, $payload);
                flash('success', '실적이 추가되었습니다.');
            }
            redirect('company-history.php?edit=' . $companyId . '#achievements');
        }

        if ($action === 'delete_achievement') {
            $achievementId = (int) (isset($_POST['achievement_id']) ? $_POST['achievement_id'] : 0);
            $companyId = (int) (isset($_POST['company_id']) ? $_POST['company_id'] : 0);
            $row = CompanyHistoryService::getAchievement($achievementId);
            $company = CompanyHistoryService::getCompany($companyId);
            if (!$company || (int) $company['project_id'] !== (int) $project['id']) {
                throw new InvalidArgumentException('회사를 찾을 수 없습니다.');
            }
            if (!$row || (int) $row['company_id'] !== $companyId) {
                throw new InvalidArgumentException('실적을 찾을 수 없습니다.');
            }
            CompanyHistoryService::deleteAchievement($achievementId);
            flash('success', '실적이 삭제되었습니다.');
            redirect('company-history.php?edit=' . $companyId . '#achievements');
        }
    } catch (Exception $e) {
        if (!empty($isAjax)) {
            $ch_json(false, array('error' => $e->getMessage()), 500);
        }
        flash('error', $e->getMessage());
        if ($editId > 0) {
            redirect('company-history.php?edit=' . $editId);
        }
        if ($viewId > 0) {
            redirect('company-history.php?view=' . $viewId);
        }
        redirect('company-history.php');
    }
}

$companies = CompanyHistoryService::listCompanies($project['id'], $search);
$totalCount = CompanyHistoryService::countCompanies($project['id']);

$editCompany = null;
$editEvents = array();
$editAchievements = array();
if ($editId > 0) {
    $editCompany = CompanyHistoryService::getCompany($editId);
    if (!$editCompany || (int) $editCompany['project_id'] !== (int) $project['id']) {
        flash('error', '회사를 찾을 수 없습니다.');
        redirect('company-history.php');
    }
    $editEvents = CompanyHistoryService::getEvents($editId);
    $editAchievements = CompanyHistoryService::getAchievements($editId);
}

$viewCompany = null;
$viewEvents = array();
$viewEventsByYear = array();
$viewAchievements = array();
if ($viewId > 0) {
    $viewCompany = CompanyHistoryService::getCompany($viewId);
    if (!$viewCompany || (int) $viewCompany['project_id'] !== (int) $project['id']) {
        flash('error', '회사를 찾을 수 없습니다.');
        redirect('company-history.php');
    }
    $viewEvents = CompanyHistoryService::getEvents($viewId);
    $viewEventsByYear = CompanyHistoryService::getEventsGroupedByYear($viewId);
    $viewAchievements = CompanyHistoryService::getAchievements($viewId);
}

$pageTitle = '회사 연혁';
$currentPage = 'company-history';

if ($printMode && $viewCompany) {
    include __DIR__ . '/views/company-history-print.php';
    exit;
}

render_page(__DIR__ . '/views/company-history.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker',
    'companies', 'totalCount', 'search', 'canEdit', 'eventCategories', 'achievementCategories',
    'editCompany', 'editEvents', 'editAchievements',
    'viewCompany', 'viewEvents', 'viewEventsByYear', 'viewAchievements'
));
