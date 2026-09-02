<?php
/**
 * 일정(간트) 작업 처리 엔드포인트.
 * - update_progress: AJAX(fetch) 로 완료 % 인라인 저장, JSON 응답
 * - create / delete: 폼 POST 후 대시보드로 리다이렉트
 * 모든 쓰기 작업은 로그인 + CSRF + 관리자(is_admin) 권한을 요구한다.
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$action = isset($_POST['action']) ? $_POST['action'] : '';
$isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

/** JSON 응답 후 종료 */
function schedule_json_response($ok, $payload = array(), $httpStatus = 200)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($httpStatus);
    header('Content-Type: application/json; charset=utf-8');
    $json = json_encode(array_merge(array('ok' => $ok), $payload), JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = '{"ok":false,"error":"JSON 인코딩 실패"}';
        if ($httpStatus < 400) {
            http_response_code(500);
        }
    }
    echo $json;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

// 인증/권한 검증
if (!verify_csrf()) {
    if ($isAjax) {
        schedule_json_response(false, array('error' => '보안 토큰이 유효하지 않습니다.'), 403);
    }
    flash('error', '보안 토큰이 유효하지 않습니다.');
    redirect('index.php');
}

if (!is_admin()) {
    if ($isAjax) {
        schedule_json_response(false, array('error' => '권한이 없습니다.'), 403);
    }
    flash('error', '일정을 수정할 권한이 없습니다.');
    redirect('index.php');
}

$user = current_user();
$userId = $user ? $user['id'] : null;

try {
    if ($action === 'update_progress') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $task = ScheduleTaskService::getById($id);
        if (!$task || (int) $task['project_id'] !== (int) $project['id']) {
            if ($isAjax) {
                schedule_json_response(false, array('error' => '작업을 찾을 수 없습니다.'), 404);
            }
            flash('error', '작업을 찾을 수 없습니다.');
            redirect('index.php');
        }

        $progress = isset($_POST['progress']) ? $_POST['progress'] : 0;
        $saved = ScheduleTaskService::updateProgress($id, $project['id'], $progress, $userId);
        log_activity($project['id'], $userId, 'schedule_progress', 'schedule_task', $id, $task['title'] . ' 완료율 ' . $saved . '%');

        $gantt = ScheduleTaskService::buildGanttModel($project['id'], $project);
        if ($isAjax) {
            schedule_json_response(true, array(
                'id' => $id,
                'progress' => $saved,
                'avg_progress' => $gantt['avg_progress'],
                'phases' => $gantt['phases'],
            ));
        }
        flash('success', '완료율이 저장되었습니다.');
        redirect('index.php');
    } elseif ($action === 'update_dates') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $task = ScheduleTaskService::getById($id);
        if (!$task || (int) $task['project_id'] !== (int) $project['id']) {
            if ($isAjax) {
                schedule_json_response(false, array('error' => '작업을 찾을 수 없습니다.'), 404);
            }
            flash('error', '작업을 찾을 수 없습니다.');
            redirect('index.php');
        }

        $start = isset($_POST['start_date']) ? $_POST['start_date'] : '';
        $end = isset($_POST['end_date']) ? $_POST['end_date'] : '';
        $saved = ScheduleTaskService::updateDates($id, $project['id'], $start, $end, $userId);
        log_activity($project['id'], $userId, 'schedule_dates', 'schedule_task', $id, $task['title'] . ' 일정 ' . $saved['start'] . ' ~ ' . $saved['end']);

        $gantt = ScheduleTaskService::buildGanttModel($project['id'], $project);
        if ($isAjax) {
            schedule_json_response(true, array(
                'id' => $id,
                'start' => $saved['start'],
                'end' => $saved['end'],
                'gantt' => $gantt,
            ));
        }
        flash('success', '일정 기간이 저장되었습니다.');
        redirect('index.php');
    } elseif ($action === 'create') {
        $newId = ScheduleTaskService::create($project['id'], $_POST, $userId);
        log_activity($project['id'], $userId, 'schedule_create', 'schedule_task', $newId, isset($_POST['title']) ? $_POST['title'] : '');
        flash('success', '일정이 추가되었습니다.');
        redirect('index.php');
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if (ScheduleTaskService::delete($id, $project['id'])) {
            log_activity($project['id'], $userId, 'schedule_delete', 'schedule_task', $id, null);
            flash('success', '일정이 삭제되었습니다.');
        }
        redirect('index.php');
    } elseif ($action === 'reorder') {
        $raw = isset($_POST['order']) ? $_POST['order'] : '';
        if (is_string($raw)) {
            $raw = trim($raw);
        }
        $items = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($items)) {
            labelup_log_error('[schedule-tasks] reorder invalid payload: ' . substr((string) $raw, 0, 500));
            if ($isAjax) {
                schedule_json_response(false, array('error' => '순서 데이터가 올바르지 않습니다.'), 422);
            }
            flash('error', '순서 데이터가 올바르지 않습니다.');
            redirect('index.php');
        }

        $applied = ScheduleTaskService::reorder($project['id'], $items, $userId);
        if ($applied < 1) {
            if ($isAjax) {
                schedule_json_response(false, array('error' => '변경할 일정 항목을 찾지 못했습니다.'), 422);
            }
            flash('error', '변경할 일정 항목을 찾지 못했습니다.');
            redirect('index.php');
        }

        try {
            log_activity($project['id'], $userId, 'schedule_reorder', 'schedule_task', null, '일정 순서/단계 변경 (' . $applied . '건)');
        } catch (Exception $logEx) {
            labelup_log_error('[schedule-tasks] reorder log failed: ' . $logEx->getMessage());
        }

        // 클라이언트는 저장 후 새로고침하므로 간트 모델은 응답에 넣지 않는다.
        if ($isAjax) {
            schedule_json_response(true, array('applied' => $applied));
        }
        redirect('index.php');
    }
} catch (InvalidArgumentException $e) {
    if ($isAjax) {
        schedule_json_response(false, array('error' => $e->getMessage()), 422);
    }
    flash('error', $e->getMessage());
    redirect('index.php');
} catch (Exception $e) {
    labelup_log_error('[schedule-tasks] ' . $e->getMessage());
    if ($isAjax) {
        schedule_json_response(false, array('error' => '처리 중 오류: ' . $e->getMessage()), 500);
    }
    flash('error', '처리 중 오류가 발생했습니다.');
    redirect('index.php');
} catch (Throwable $e) {
    // PHP 7+ Error (undefined method 등)도 JSON으로 반환
    labelup_log_error('[schedule-tasks][throwable] ' . $e->getMessage());
    if ($isAjax) {
        schedule_json_response(false, array('error' => '처리 중 오류: ' . $e->getMessage()), 500);
    }
    flash('error', '처리 중 오류가 발생했습니다.');
    redirect('index.php');
}

redirect('index.php');
