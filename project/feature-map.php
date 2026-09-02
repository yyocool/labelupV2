<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$user = current_user();
$userId = $user ? $user['id'] : null;
$slideTypes = FeatureMapService::getSlideTypes();
$tones = FeatureMapService::getTones();

$doc = FeatureMapService::ensureDefaults($project['id'], $userId);
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$manage = isset($_GET['manage']) && $_GET['manage'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && is_admin()) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'save_doc') {
        FeatureMapService::updateDoc((int) $doc['id'], array(
            'title' => isset($_POST['title']) ? $_POST['title'] : '',
            'subtitle' => isset($_POST['subtitle']) ? $_POST['subtitle'] : '',
            'version' => isset($_POST['version']) ? $_POST['version'] : '1.0',
            'basis' => isset($_POST['basis']) ? $_POST['basis'] : '',
        ), $userId);
        flash('success', '문서 정보가 저장되었습니다.');
        redirect('feature-map.php?manage=1');
    }

    if ($action === 'save_slide') {
        $payload = array(
            'slide_key' => isset($_POST['slide_key']) ? $_POST['slide_key'] : '',
            'sort_order' => isset($_POST['sort_order']) ? $_POST['sort_order'] : 0,
            'slide_type' => isset($_POST['slide_type']) ? $_POST['slide_type'] : 'custom',
            'tone' => isset($_POST['tone']) ? $_POST['tone'] : 'teal',
            'kicker' => isset($_POST['kicker']) ? $_POST['kicker'] : '',
            'title' => isset($_POST['title']) ? $_POST['title'] : '',
            'subtitle' => isset($_POST['subtitle']) ? $_POST['subtitle'] : '',
            'lead_text' => isset($_POST['lead_text']) ? $_POST['lead_text'] : '',
            'is_visible' => !empty($_POST['is_visible']) ? 1 : 0,
            'body_meta' => isset($_POST['body_meta']) ? $_POST['body_meta'] : '',
            'body_basis' => isset($_POST['body_basis']) ? $_POST['body_basis'] : '',
            'body_lines' => isset($_POST['body_lines']) ? $_POST['body_lines'] : '',
            'body_rules' => isset($_POST['body_rules']) ? $_POST['body_rules'] : '',
            'body_bridges' => isset($_POST['body_bridges']) ? $_POST['body_bridges'] : '',
            'body_code' => isset($_POST['body_code']) ? $_POST['body_code'] : '',
            'body_screen' => isset($_POST['body_screen']) ? $_POST['body_screen'] : '',
            'body_priority' => isset($_POST['body_priority']) ? $_POST['body_priority'] : '',
            'body_cat_name' => isset($_POST['body_cat_name']) ? $_POST['body_cat_name'] : '',
            'body_json' => isset($_POST['body_json']) ? $_POST['body_json'] : '',
        );

        // 고급 JSON 체크 시 body로 직접 주입
        if (!empty($_POST['use_body_json'])) {
            $decoded = json_decode($payload['body_json'], true);
            if (!is_array($decoded)) {
                flash('error', '본문 JSON 형식이 올바르지 않습니다.');
                redirect('feature-map.php?manage=1' . ($editId ? '&edit=' . $editId : ''));
            }
            $payload['body'] = $decoded;
            $payload['prefer_body_json'] = 1;
        }

        $id = (int) (isset($_POST['slide_id']) ? $_POST['slide_id'] : 0);
        try {
            if ($id > 0) {
                $existing = FeatureMapService::getSlideById($id);
                if ($existing && (int) $existing['project_id'] === (int) $project['id']) {
                    FeatureMapService::updateSlide($id, $payload, $userId);
                    FeatureMapService::rebuildMapJsonFromSlides($project['id'], (int) $doc['id'], $userId);
                    flash('success', '슬라이드가 수정되었습니다.');
                } else {
                    flash('error', '수정할 슬라이드를 찾을 수 없습니다.');
                }
            } else {
                FeatureMapService::createSlide($project['id'], (int) $doc['id'], $payload, $userId);
                FeatureMapService::rebuildMapJsonFromSlides($project['id'], (int) $doc['id'], $userId);
                flash('success', '슬라이드가 등록되었습니다.');
            }
        } catch (Exception $e) {
            flash('error', '저장 실패: 슬라이드 키가 중복되었거나 입력이 올바르지 않습니다.');
            redirect('feature-map.php?manage=1' . ($id ? '&edit=' . $id : ''));
        }
        redirect('feature-map.php?manage=1');
    }

    if ($action === 'delete_slide') {
        $id = (int) (isset($_POST['slide_id']) ? $_POST['slide_id'] : 0);
        $existing = FeatureMapService::getSlideById($id);
        if ($existing && (int) $existing['project_id'] === (int) $project['id']) {
            FeatureMapService::deleteSlide($id);
            FeatureMapService::rebuildMapJsonFromSlides($project['id'], (int) $doc['id'], $userId);
            flash('success', '슬라이드가 삭제되었습니다.');
        } else {
            flash('error', '삭제할 슬라이드를 찾을 수 없습니다.');
        }
        redirect('feature-map.php?manage=1');
    }

    if ($action === 'reseed') {
        $count = FeatureMapService::seedFromMapFile($project['id'], (int) $doc['id'], $userId, true);
        flash('success', '시드 기준으로 슬라이드 ' . $count . '페이지를 다시 등록했습니다.');
        redirect('feature-map.php?manage=1');
    }
}

$doc = FeatureMapService::getOrCreateDoc($project['id'], $userId);
$dbSlides = FeatureMapService::getSlides($project['id'], false);
$deckSlides = FeatureMapService::toDeckSlides($dbSlides);
$map = FeatureMapService::buildBrowseMap($doc, $dbSlides);
if (empty($map['scope']) || empty($map['scope']['phases'])) {
    $map['scope'] = FeatureMapService::getScopeData($doc);
}
$scopeSummaries = FeatureMapService::listScopePhaseSummaries(isset($map['scope']) ? $map['scope'] : array());

$editSlide = null;
$editExtras = FeatureMapService::slideFormExtras(array(
    'slide_type' => 'feature',
    'body' => array(),
));
if ($editId > 0) {
    $editSlide = FeatureMapService::getSlideById($editId);
    if ($editSlide && (int) $editSlide['project_id'] === (int) $project['id']) {
        $editExtras = FeatureMapService::slideFormExtras($editSlide);
    } else {
        $editSlide = null;
    }
}

$pageTitle = '기능 구조 맵';
$currentPage = 'feature-map';

render_page(__DIR__ . '/views/feature-map.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker',
    'map', 'doc', 'dbSlides', 'deckSlides', 'slideTypes', 'tones',
    'manage', 'editSlide', 'editExtras', 'scopeSummaries'
));
