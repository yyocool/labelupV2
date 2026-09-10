<?php
/**
 * 검수 시트 — 1차 구축(phase-1)만
 * 개발자: 상태 변경 / 검수자: 검수 확인·코멘트
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$user = current_user();
$userId = $user ? $user['id'] : null;
$canEdit = can_review_scope_edit($user);
$canEditStatus = can_review_scope_status($user);
$canReview = can_review_scope_review($user);
$canEditUrl = $canEdit || $canEditStatus || $canReview;

$phaseKey = 'phase-1';
$phases = DevScopeService::getPhases();
$phaseMeta = isset($phases[$phaseKey]) ? $phases[$phaseKey] : array('label' => '1차 구축', 'period' => '');
$priorities = DevScopeService::getPriorities();
$statuses = DevScopeService::getStatuses();
$reviewStatuses = DevScopeService::getReviewStatuses();

try {
    DevScopeService::ensureDefaults($project['id'], $userId);
} catch (Exception $e) {
    if (function_exists('labelup_log_error')) {
        labelup_log_error('[review-scope ensureDefaults] ' . $e->getMessage());
    }
}

if (isset($_GET['export']) && ($_GET['export'] === 'xlsx' || $_GET['export'] === 'excel' || $_GET['export'] === '1')) {
    try {
        $file = DevScopeService::buildReviewExcelExport($project['id']);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $file['mime']);
        header('Content-Disposition: attachment; filename="' . rawurlencode($file['filename']) . '"; filename*=UTF-8\'\'' . rawurlencode($file['filename']));
        header('Content-Length: ' . strlen($file['body']));
        header('Cache-Control: no-cache, must-revalidate');
        echo $file['body'];
        exit;
    } catch (Exception $e) {
        flash('error', '엑셀 내려받기에 실패했습니다: ' . $e->getMessage());
        redirect('review-scope.php');
    }
}

if (!function_exists('rs_json')) {
    function rs_json($ok, $payload = array(), $httpStatus = 200)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code($httpStatus);
            header('Content-Type: application/json; charset=utf-8');
        }
        $json = json_encode(array_merge(array('ok' => (bool) $ok), $payload), JSON_UNESCAPED_UNICODE);
        echo $json === false ? '{"ok":false,"error":"JSON encode failed"}' : $json;
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $isAjax = (isset($_POST['ajax']) && (string) $_POST['ajax'] === '1')
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    $csrfOk = verify_csrf();

    if ($isAjax && !$csrfOk) {
        rs_json(false, array('error' => '보안 토큰이 만료되었습니다. 새로고침 후 다시 시도해 주세요.', 'code' => 'csrf'), 403);
    }

    $allowedWithoutEdit = ($action === 'save_status' && $canEditStatus)
        || ($action === 'save_review' && $canReview)
        || ($action === 'save_page_url' && $canEditUrl);
    if ($isAjax && !$canEdit && !$allowedWithoutEdit) {
        rs_json(false, array('error' => '권한이 없습니다.', 'code' => 'forbidden'), 403);
    }

    $assertItem = function ($id) use ($project, $phaseKey, $isAjax) {
        $existing = DevScopeService::getById($id);
        if (!$existing || (int) $existing['project_id'] !== (int) $project['id']) {
            if ($isAjax) {
                rs_json(false, array('error' => '항목을 찾을 수 없습니다.'), 404);
            }
            flash('error', '항목을 찾을 수 없습니다.');
            redirect('review-scope.php');
        }
        if (isset($existing['phase_key']) && $existing['phase_key'] !== $phaseKey) {
            if ($isAjax) {
                rs_json(false, array('error' => '1차 구축 항목만 검수할 수 있습니다.'), 400);
            }
            flash('error', '1차 구축 항목만 검수할 수 있습니다.');
            redirect('review-scope.php');
        }
        return $existing;
    };

    try {
        if ($csrfOk && $action === 'save_status' && $canEditStatus) {
            $id = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);
            $assertItem($id);
            $status = isset($_POST['status']) ? $_POST['status'] : 'planned';
            DevScopeService::updateStatus($id, $status, $userId);
            if ($isAjax) {
                $fresh = DevScopeService::getById($id);
                rs_json(true, array(
                    'item' => array(
                        'id' => $id,
                        'status' => $fresh ? $fresh['status'] : $status,
                    ),
                ));
            }
            flash('success', '개발자확인이 저장되었습니다.');
            redirect('review-scope.php');
        }

        if ($csrfOk && $action === 'save_review' && $canReview) {
            $id = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);
            $assertItem($id);
            $payload = array();
            if (array_key_exists('review_status', $_POST)) {
                $payload['review_status'] = $_POST['review_status'];
            } elseif (array_key_exists('review_confirmed', $_POST)) {
                $payload['review_confirmed'] = $_POST['review_confirmed'];
            }
            if (array_key_exists('review_comment', $_POST)) {
                $payload['review_comment'] = $_POST['review_comment'];
            }
            DevScopeService::saveReview($id, $payload, $userId);
            if ($isAjax) {
                $fresh = DevScopeService::getById($id);
                $rs = DevScopeService::normalizeReviewStatus(
                    $fresh && isset($fresh['review_status'])
                        ? $fresh['review_status']
                        : ($fresh && !empty($fresh['review_confirmed']) ? 'done' : 'in_review')
                );
                rs_json(true, array(
                    'item' => array(
                        'id' => $id,
                        'review_status' => $rs,
                        'review_comment' => ($fresh && isset($fresh['review_comment'])) ? $fresh['review_comment'] : '',
                        'review_confirmed_at' => ($fresh && !empty($fresh['review_confirmed_at'])) ? $fresh['review_confirmed_at'] : null,
                    ),
                ));
            }
            flash('success', '검수 내용이 저장되었습니다.');
            redirect('review-scope.php');
        }

        if ($csrfOk && $action === 'save_page_url' && $canEditUrl) {
            $id = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);
            $assertItem($id);
            $pageUrl = isset($_POST['page_url']) ? $_POST['page_url'] : '';
            DevScopeService::savePageUrl($id, $pageUrl, $userId);
            if ($isAjax) {
                $fresh = DevScopeService::getById($id);
                $url = ($fresh && isset($fresh['page_url'])) ? $fresh['page_url'] : '';
                rs_json(true, array(
                    'item' => array(
                        'id' => $id,
                        'page_url' => $url,
                        'page_url_href' => DevScopeService::pageUrlHref($url),
                    ),
                ));
            }
            flash('success', '페이지 URL이 저장되었습니다.');
            redirect('review-scope.php');
        }

        if ($csrfOk && $canEdit) {
            if ($action === 'inline_save') {
                $id = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);
                $existing = $assertItem($id);
                $data = array(
                    'title' => isset($_POST['title']) ? $_POST['title'] : $existing['title'],
                    'description' => isset($_POST['description']) ? $_POST['description'] : $existing['description'],
                    'priority' => isset($_POST['priority']) ? $_POST['priority'] : $existing['priority'],
                    'status' => isset($_POST['status']) ? $_POST['status'] : $existing['status'],
                );
                if (!$canEditStatus) {
                    $data['status'] = $existing['status'];
                }
                DevScopeService::update($id, $data, $userId);
                if ($isAjax) {
                    $fresh = DevScopeService::getById($id);
                    rs_json(true, array(
                        'item' => array(
                            'id' => $id,
                            'title' => $fresh ? $fresh['title'] : '',
                            'description' => ($fresh && isset($fresh['description'])) ? $fresh['description'] : '',
                            'priority' => $fresh ? $fresh['priority'] : '',
                            'status' => $fresh ? $fresh['status'] : '',
                        ),
                    ));
                }
                flash('success', '저장되었습니다.');
                redirect('review-scope.php');
            }

            if ($action === 'reorder_move') {
                $id = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);
                $assertItem($id);
                $direction = isset($_POST['direction']) ? $_POST['direction'] : 'up';
                $result = DevScopeService::moveSort($id, $direction, $userId);
                if ($isAjax) {
                    rs_json(true, $result);
                }
                flash('success', '순서가 변경되었습니다.');
                redirect('review-scope.php?focus=' . $id);
            }

            if ($action === 'reorder_siblings') {
                $raw = isset($_POST['ordered_ids']) ? $_POST['ordered_ids'] : '';
                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    $orderedIds = is_array($decoded) ? $decoded : array_filter(array_map('intval', preg_split('/[,\s]+/', $raw)));
                } elseif (is_array($raw)) {
                    $orderedIds = $raw;
                } else {
                    $orderedIds = array();
                }
                $result = DevScopeService::reorderSiblings($project['id'], $orderedIds, $userId);
                if ($isAjax) {
                    rs_json(true, $result);
                }
                flash('success', '순서가 저장되었습니다.');
                redirect('review-scope.php');
            }

            if ($action === 'quick_add') {
                $depth = (int) (isset($_POST['depth']) ? $_POST['depth'] : 3);
                $parentId = (int) (isset($_POST['parent_id']) ? $_POST['parent_id'] : 0);
                $title = isset($_POST['title']) ? trim((string) $_POST['title']) : '';
                if ($title === '') {
                    $title = $depth === 1 ? '새 영역' : ($depth === 2 ? '새 블록' : '새 항목');
                }
                if ($depth > 1) {
                    if ($parentId <= 0) {
                        throw new InvalidArgumentException($depth === 2 ? '상위 영역을 선택해 주세요.' : '상위 블록을 선택해 주세요.');
                    }
                    $parent = DevScopeService::getById($parentId);
                    if (!$parent || (int) $parent['project_id'] !== (int) $project['id']) {
                        throw new InvalidArgumentException('상위 항목을 찾을 수 없습니다.');
                    }
                }
                $newId = DevScopeService::create($project['id'], array(
                    'depth' => $depth,
                    'parent_id' => $parentId,
                    'phase_key' => $phaseKey,
                    'title' => $title,
                    'description' => '',
                    'priority' => isset($_POST['priority']) ? $_POST['priority'] : 'P0',
                    'status' => 'planned',
                ), $userId);
                if ($isAjax) {
                    rs_json(true, array('id' => $newId));
                }
                flash('success', '행이 추가되었습니다.');
                redirect('review-scope.php?focus=' . $newId);
            }

            if ($action === 'delete') {
                $id = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);
                $existing = $assertItem($id);
                DevScopeService::delete($id);
                if ($isAjax) {
                    rs_json(true, array('deleted' => $id));
                }
                flash('success', '삭제되었습니다.');
                redirect('review-scope.php');
            }

            if ($isAjax) {
                rs_json(false, array('error' => '알 수 없는 요청입니다: ' . $action), 400);
            }
        }
    } catch (Exception $e) {
        if ($isAjax) {
            rs_json(false, array('error' => $e->getMessage()), 500);
        }
        flash('error', $e->getMessage());
        redirect('review-scope.php');
    }
}

$sheetRows = DevScopeService::buildSheetRows($project['id'], $phaseKey);
$d1Parents = DevScopeService::parentsForSelect($project['id'], $phaseKey, 2);
$d2Parents = DevScopeService::parentsForSelect($project['id'], $phaseKey, 3);

$stats = array(
    'total' => 0, 'd1' => 0, 'd2' => 0, 'd3' => 0, 'done' => 0,
    'review_in_review' => 0, 'review_done' => 0, 'review_need_fix' => 0,
);
foreach ($sheetRows as $r) {
    $stats['total']++;
    if ($r['depth'] === 1) {
        $stats['d1']++;
    } elseif ($r['depth'] === 2) {
        $stats['d2']++;
    } else {
        $stats['d3']++;
    }
    if (isset($r['item']['status']) && $r['item']['status'] === 'done') {
        $stats['done']++;
    }
    $rs = DevScopeService::normalizeReviewStatus(
        isset($r['item']['review_status'])
            ? $r['item']['review_status']
            : (empty($r['item']['review_confirmed']) ? 'in_review' : 'done')
    );
    if ($rs === 'done') {
        $stats['review_done']++;
    } elseif ($rs === 'need_fix') {
        $stats['review_need_fix']++;
    } else {
        $stats['review_in_review']++;
    }
}

$csrfToken = csrf_token();
$focusId = isset($_GET['focus']) ? (int) $_GET['focus'] : 0;

if (isset($_GET['print']) && $_GET['print'] === '1') {
    $printSections = array(array(
        'phase_key' => $phaseKey,
        'phase' => $phaseMeta,
        'rows' => $sheetRows,
    ));
    $printScope = 'current';
    include __DIR__ . '/views/review-scope-print.php';
    exit;
}

$pageTitle = '검수';
$currentPage = 'review-scope';

render_page(__DIR__ . '/views/review-scope.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker',
    'phaseKey', 'phaseMeta', 'priorities', 'statuses', 'reviewStatuses', 'sheetRows', 'stats',
    'd1Parents', 'd2Parents', 'canEdit', 'canEditStatus', 'canReview', 'canEditUrl', 'csrfToken', 'focusId'
), 'layout_sheet.php');
