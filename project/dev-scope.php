<?php
/**
 * 개발범위 시트 (3depth) — 좌측 메뉴 미등록, URL 직접 접근
 * 엑셀형 인라인 편집 · 햄버거 레이아웃
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

$phases = DevScopeService::getPhases();
$priorities = DevScopeService::getPriorities();
$statuses = DevScopeService::getStatuses();

$phaseKey = isset($_GET['phase']) ? $_GET['phase'] : 'phase-1';
if (!isset($phases[$phaseKey])) {
    $phaseKey = 'phase-1';
}

try {
    DevScopeService::ensureDefaults($project['id'], $userId);
} catch (Exception $e) {
    if (function_exists('labelup_log_error')) {
        labelup_log_error('[dev-scope ensureDefaults] ' . $e->getMessage());
    }
}

// 엑셀 내려받기
if (isset($_GET['export']) && ($_GET['export'] === 'xlsx' || $_GET['export'] === 'excel' || $_GET['export'] === '1')) {
    $scope = (isset($_GET['scope']) && $_GET['scope'] === 'current') ? 'current' : 'all';
    try {
        if (!method_exists('DevScopeService', 'buildExcelExport')) {
            throw new RuntimeException('DevScopeService::buildExcelExport 가 없습니다. DevScopeService.php 를 업로드해 주세요.');
        }
        $file = DevScopeService::buildExcelExport($project['id'], $scope, $phaseKey);
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
        if (function_exists('labelup_log_error')) {
            labelup_log_error('[dev-scope export] ' . $e->getMessage());
        }
        flash('error', '엑셀 내려받기에 실패했습니다: ' . $e->getMessage());
        redirect('dev-scope.php?phase=' . urlencode($phaseKey));
    }
}

if (!function_exists('ds_json')) {
    function ds_json($ok, $payload = array(), $httpStatus = 200)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code($httpStatus);
            header('Content-Type: application/json; charset=utf-8');
        }
        $json = json_encode(array_merge(array('ok' => (bool) $ok), $payload), JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = '{"ok":false,"error":"JSON encode failed"}';
        }
        echo $json;
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $isAjax = (isset($_POST['ajax']) && (string) $_POST['ajax'] === '1')
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    $csrfOk = verify_csrf();

    // AJAX는 항상 JSON으로 응답 (CSRF 실패 시 HTML 페이지가 내려가면 프론트가 "서버 오류 HTTP 200"으로 오인)
    if ($isAjax && !$csrfOk) {
        ds_json(false, array(
            'error' => '보안 토큰이 만료되었습니다. 페이지를 새로고침한 뒤 다시 시도해 주세요.',
            'code' => 'csrf',
        ), 403);
    }
    if ($isAjax && !$canEdit) {
        ds_json(false, array('error' => '편집 권한이 없습니다.', 'code' => 'forbidden'), 403);
    }

    if ($csrfOk && $canEdit) {
        if ($isAjax) {
            register_shutdown_function(function () {
                $e = error_get_last();
                if (!$e || !in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
                    return;
                }
                if (function_exists('labelup_log_error')) {
                    labelup_log_error('[dev-scope FATAL] ' . $e['message'] . ' in ' . $e['file'] . ':' . $e['line']);
                }
                if (!headers_sent()) {
                    http_response_code(500);
                    header('Content-Type: application/json; charset=utf-8');
                }
                echo json_encode(array(
                    'ok' => false,
                    'error' => $e['message'],
                    'file' => basename($e['file']),
                    'line' => $e['line'],
                ), JSON_UNESCAPED_UNICODE);
            });
        }

        try {
        if ($action === 'inline_save') {
            $id = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);
            $existing = DevScopeService::getById($id);
            if (!$existing || (int) $existing['project_id'] !== (int) $project['id']) {
                if ($isAjax) {
                    ds_json(false, array('error' => '항목을 찾을 수 없습니다.'), 404);
                }
                flash('error', '항목을 찾을 수 없습니다.');
                redirect('dev-scope.php?phase=' . urlencode($phaseKey));
            }
            DevScopeService::update($id, array(
                'title' => isset($_POST['title']) ? $_POST['title'] : $existing['title'],
                'description' => isset($_POST['description']) ? $_POST['description'] : $existing['description'],
                'priority' => isset($_POST['priority']) ? $_POST['priority'] : $existing['priority'],
                'status' => isset($_POST['status']) ? $_POST['status'] : $existing['status'],
                'sort_order' => isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : (int) $existing['sort_order'],
            ), $userId);
            if ($isAjax) {
                $fresh = DevScopeService::getById($id);
                $styles = DevScopeService::parseStyle($fresh && isset($fresh['style_json']) ? $fresh['style_json'] : null);
                ds_json(true, array(
                    'item' => array(
                        'id' => $fresh ? (int) $fresh['id'] : $id,
                        'title' => $fresh ? $fresh['title'] : '',
                        'description' => ($fresh && isset($fresh['description'])) ? $fresh['description'] : '',
                        'priority' => $fresh ? $fresh['priority'] : '',
                        'status' => $fresh ? $fresh['status'] : '',
                        'phase_key' => $fresh ? $fresh['phase_key'] : $phaseKey,
                        'style' => $styles,
                    ),
                ));
            }
            flash('success', '저장되었습니다.');
            redirect('dev-scope.php?phase=' . urlencode(isset($existing['phase_key']) ? $existing['phase_key'] : $phaseKey));
        }

        if ($action === 'save_style') {
            $id = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);
            $field = isset($_POST['field']) ? $_POST['field'] : 'title';
            $existing = DevScopeService::getById($id);
            if (!$existing || (int) $existing['project_id'] !== (int) $project['id']) {
                if ($isAjax) {
                    ds_json(false, array('error' => '항목을 찾을 수 없습니다.'), 404);
                }
                flash('error', '항목을 찾을 수 없습니다.');
                redirect('dev-scope.php?phase=' . urlencode($phaseKey));
            }
            $fieldStyle = array(
                'bg' => isset($_POST['bg']) ? $_POST['bg'] : '',
                'color' => isset($_POST['color']) ? $_POST['color'] : '',
                'bold' => isset($_POST['bold']) ? $_POST['bold'] : 0,
            );
            $styles = DevScopeService::updateFieldStyle($id, $field, $fieldStyle, $userId);
            if ($isAjax) {
                ds_json(true, array('style' => $styles, 'field' => ($field === 'description') ? 'description' : 'title'));
            }
            flash('success', '스타일이 저장되었습니다.');
            redirect('dev-scope.php?phase=' . urlencode($phaseKey));
        }

        if ($action === 'reorder_move') {
            $id = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);
            $direction = isset($_POST['direction']) ? $_POST['direction'] : 'up';
            $existing = DevScopeService::getById($id);
            if (!$existing || (int) $existing['project_id'] !== (int) $project['id']) {
                if ($isAjax) {
                    ds_json(false, array('error' => '항목을 찾을 수 없습니다.'), 404);
                }
                flash('error', '항목을 찾을 수 없습니다.');
                redirect('dev-scope.php?phase=' . urlencode($phaseKey));
            }
            $result = DevScopeService::moveSort($id, $direction, $userId);
            if ($isAjax) {
                ds_json(true, $result);
            }
            flash('success', '순서가 변경되었습니다.');
            redirect('dev-scope.php?phase=' . urlencode($phaseKey) . '&focus=' . $id);
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
                ds_json(true, $result);
            }
            flash('success', '순서가 저장되었습니다.');
            redirect('dev-scope.php?phase=' . urlencode($phaseKey));
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
                    throw new InvalidArgumentException('상위 항목을 찾을 수 없습니다. 페이지를 새로고침해 주세요.');
                }
                $expectParentDepth = $depth - 1;
                if ((int) $parent['depth'] !== $expectParentDepth) {
                    throw new InvalidArgumentException('상위 항목 depth가 올바르지 않습니다.');
                }
                if (isset($parent['phase_key']) && $parent['phase_key'] !== (isset($_POST['phase_key']) ? $_POST['phase_key'] : $phaseKey)) {
                    // 부모 phase와 요청 phase가 다르면 부모 phase를 따름
                }
            }
            $newId = DevScopeService::create($project['id'], array(
                'depth' => $depth,
                'parent_id' => $parentId,
                'phase_key' => isset($_POST['phase_key']) ? $_POST['phase_key'] : $phaseKey,
                'title' => $title,
                'description' => '',
                'priority' => isset($_POST['priority']) ? $_POST['priority'] : (($phaseKey === 'phase-1') ? 'P0' : 'P1'),
                'status' => 'planned',
            ), $userId);
            if ($isAjax) {
                ds_json(true, array('id' => $newId));
            }
            flash('success', '행이 추가되었습니다.');
            redirect('dev-scope.php?phase=' . urlencode($phaseKey) . '&focus=' . $newId);
        }

        if ($action === 'delete') {
            $id = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);
            $existing = DevScopeService::getById($id);
            if ($existing && (int) $existing['project_id'] === (int) $project['id']) {
                DevScopeService::delete($id);
                if ($isAjax) {
                    ds_json(true, array('deleted' => $id));
                }
                flash('success', '삭제되었습니다.');
                $phaseKey = isset($existing['phase_key']) ? $existing['phase_key'] : $phaseKey;
            } else {
                if ($isAjax) {
                    ds_json(false, array('error' => '삭제할 항목을 찾을 수 없습니다.'), 404);
                }
                flash('error', '삭제할 항목을 찾을 수 없습니다.');
            }
            redirect('dev-scope.php?phase=' . urlencode($phaseKey));
        }

        if ($action === 'move_phase') {
            if (!method_exists('DevScopeService', 'moveToPhase')) {
                throw new RuntimeException('DevScopeService::moveToPhase 가 없습니다. DevScopeService.php 를 업로드해 주세요.');
            }
            $id = (int) (isset($_POST['item_id']) ? $_POST['item_id'] : 0);
            $target = isset($_POST['target_phase']) ? $_POST['target_phase'] : '';
            $existing = DevScopeService::getById($id);
            if (!$existing || (int) $existing['project_id'] !== (int) $project['id']) {
                if ($isAjax) {
                    ds_json(false, array('error' => '항목을 찾을 수 없습니다.'), 404);
                }
                flash('error', '항목을 찾을 수 없습니다.');
                redirect('dev-scope.php?phase=' . urlencode($phaseKey));
            }
            $result = DevScopeService::moveToPhase($id, $target, $userId);
            $targetLabel = isset($phases[$target]['label']) ? $phases[$target]['label'] : $target;
            $msg = $result['moved'] . '건을 「' . $targetLabel . '」로 이동했습니다.';
            if (!empty($result['created_parents'])) {
                $msg .= ' (상위 ' . (int) $result['created_parents'] . '건 자동 생성)';
            }
            if ($isAjax) {
                ds_json(true, array(
                    'moved' => (int) $result['moved'],
                    'ids' => isset($result['ids']) ? $result['ids'] : array(),
                    'target' => $target,
                    'message' => $msg,
                ));
            }
            flash('success', $msg);
            redirect('dev-scope.php?phase=' . urlencode($target) . '&focus=' . $id);
        }

        if ($action === 'reseed') {
            $count = DevScopeService::seedFromFeatureMap($project['id'], $userId, true);
            flash('success', '시드 ' . $count . '건을 다시 불러왔습니다.');
            redirect('dev-scope.php?phase=' . urlencode($phaseKey));
        }

        if ($isAjax) {
            ds_json(false, array('error' => '알 수 없는 요청입니다: ' . $action, 'code' => 'unknown_action'), 400);
        }
        } catch (Exception $e) {
            if (function_exists('labelup_log_error')) {
                labelup_log_error('[dev-scope POST] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            }
            if (!empty($isAjax)) {
                ds_json(false, array(
                    'error' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ), 500);
            }
            flash('error', $e->getMessage());
            redirect('dev-scope.php?phase=' . urlencode($phaseKey));
        }
    } // csrfOk && canEdit
}

$sheetRows = DevScopeService::buildSheetRows($project['id'], $phaseKey);
$d1Parents = DevScopeService::parentsForSelect($project['id'], $phaseKey, 2);
$d2Parents = DevScopeService::parentsForSelect($project['id'], $phaseKey, 3);

$stats = array('total' => 0, 'd1' => 0, 'd2' => 0, 'd3' => 0, 'done' => 0);
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
}

$csrfToken = csrf_token();
$focusId = isset($_GET['focus']) ? (int) $_GET['focus'] : 0;

$printMode = isset($_GET['print']) && $_GET['print'] === '1';
if ($printMode) {
    $printScope = (isset($_GET['scope']) && $_GET['scope'] === 'all') ? 'all' : 'current';
    $printSections = array();
    if ($printScope === 'all') {
        foreach ($phases as $pk => $ph) {
            $rows = DevScopeService::buildSheetRows($project['id'], $pk);
            $printSections[] = array(
                'phase_key' => $pk,
                'phase' => $ph,
                'rows' => $rows,
            );
        }
    } else {
        $printSections[] = array(
            'phase_key' => $phaseKey,
            'phase' => isset($phases[$phaseKey]) ? $phases[$phaseKey] : array('label' => $phaseKey, 'period' => ''),
            'rows' => $sheetRows,
        );
    }
    include __DIR__ . '/views/dev-scope-print.php';
    exit;
}

$pageTitle = '개발범위';
$currentPage = 'dev-scope';

render_page(__DIR__ . '/views/dev-scope.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker',
    'phases', 'phaseKey', 'priorities', 'statuses', 'sheetRows', 'stats',
    'd1Parents', 'd2Parents', 'canEdit', 'csrfToken', 'focusId'
), 'layout_sheet.php');
