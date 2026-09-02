<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_admin();
extract(init_project_context());

$user = current_user();
FormatAnalysisService::ensureDefaults($project['id'], $user ? $user['id'] : null);

$viewId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'analyses';
if (!in_array($tab, array('analyses', 'profiles'), true)) {
    $tab = 'analyses';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'upload') {
        $notes = isset($_POST['analyst_notes']) ? trim($_POST['analyst_notes']) : '';
        $file = isset($_FILES['format_file']) ? $_FILES['format_file'] : array();
        $result = FormatAnalysisService::analyzeUpload($project['id'], $file, $user ? $user['id'] : null, $notes);
        if (!empty($result['ok'])) {
            if (!empty($_POST['merge_profile']) && !empty($result['profile_id']) && !empty($result['parsed'])) {
                FormatAnalysisService::mergeProfileFromAnalysis(
                    (int) $result['profile_id'],
                    $project['id'],
                    $result['parsed'],
                    $user ? $user['id'] : null
                );
            }
            $imgN = isset($result['image_count']) ? (int) $result['image_count'] : 0;
            $clipN = 0;
            if (!empty($result['images']) && is_array($result['images'])) {
                foreach ($result['images'] as $im) {
                    if (!empty($im['kind']) && $im['kind'] === 'clipart_object') {
                        $clipN++;
                    }
                }
            }
            if (!empty($result['warning'])) {
                flash('error', $result['warning']);
            } else {
                $msg = $imgN > 0
                    ? ('포맷 분석 완료 · 이미지 ' . $imgN . '개 추출')
                    : '포맷 분석이 완료되어 저장되었습니다. (임베디드 이미지 없음)';
                if ($clipN > 0) {
                    $msg .= ' (클립아트 ' . $clipN . '개 포함)';
                } elseif ($imgN > 0) {
                    $msg .= ' — 클립아트가 없으면 원본을 다시 첨부해 주세요';
                }
                flash('success', $msg);
            }
            redirect('format-analysis.php?id=' . (int) $result['id']);
        }
        flash('error', isset($result['error']) ? $result['error'] : '분석에 실패했습니다.');
        redirect('format-analysis.php');
    }

    if ($action === 'save_notes') {
        $id = (int) (isset($_POST['analysis_id']) ? $_POST['analysis_id'] : 0);
        $notes = isset($_POST['analyst_notes']) ? $_POST['analyst_notes'] : '';
        $row = FormatAnalysisService::getAnalysisById($id);
        if ($row && (int) $row['project_id'] === (int) $project['id']) {
            FormatAnalysisService::updateAnalysisNotes($id, $project['id'], $notes, $user ? $user['id'] : null);
            flash('success', '분석 메모가 저장되었습니다.');
        } else {
            flash('error', '분석 기록을 찾을 수 없습니다.');
        }
        redirect('format-analysis.php?id=' . $id);
    }

    if ($action === 'delete_analysis') {
        $id = (int) (isset($_POST['analysis_id']) ? $_POST['analysis_id'] : 0);
        if (FormatAnalysisService::deleteAnalysis($id, $project['id'])) {
            flash('success', '분석 기록이 삭제되었습니다.');
        } else {
            flash('error', '삭제에 실패했습니다.');
        }
        redirect('format-analysis.php');
    }

    if ($action === 'delete_analyses') {
        $ids = isset($_POST['analysis_ids']) && is_array($_POST['analysis_ids']) ? $_POST['analysis_ids'] : array();
        $n = FormatAnalysisService::deleteAnalyses($ids, $project['id']);
        if ($n > 0) {
            flash('success', '분석 기록 ' . $n . '건을 삭제했습니다.');
        } else {
            flash('error', '삭제할 기록을 선택하세요.');
        }
        redirect('format-analysis.php');
    }

    if ($action === 'merge_profile') {
        $id = (int) (isset($_POST['analysis_id']) ? $_POST['analysis_id'] : 0);
        $row = FormatAnalysisService::getAnalysisById($id);
        if ($row && (int) $row['project_id'] === (int) $project['id'] && !empty($row['profile_id'])) {
            $summary = json_decode($row['summary_json'], true);
            $parsed = array(
                'detected_version' => $row['detected_version'],
                'product_sku' => $row['product_sku'],
                'product_name' => $row['product_name'],
                'paper' => $row['paper'],
                'category' => $row['category'],
                'confidence' => $row['confidence'],
                'summary' => is_array($summary) ? $summary : array(),
            );
            FormatAnalysisService::mergeProfileFromAnalysis(
                (int) $row['profile_id'],
                $project['id'],
                $parsed,
                $user ? $user['id'] : null
            );
            flash('success', '포맷 프로필에 샘플 정보가 반영되었습니다.');
        } else {
            flash('error', '연결할 포맷 프로필이 없습니다. 프로필을 먼저 등록하세요.');
        }
        redirect('format-analysis.php?id=' . $id);
    }

    if ($action === 'reextract_images') {
        $id = (int) (isset($_POST['analysis_id']) ? $_POST['analysis_id'] : 0);
        $result = FormatAnalysisService::reextractImages($id, $project['id']);
        if (!empty($result['ok'])) {
            $n = (int) $result['count'];
            flash('success', $n > 0
                ? ('임베디드 이미지 ' . $n . '개를 추출·저장했습니다.')
                : '추출 가능한 이미지가 없습니다.');
        } else {
            flash('error', isset($result['error']) ? $result['error'] : '이미지 추출에 실패했습니다.');
        }
        redirect('format-analysis.php?id=' . $id);
    }

    if ($action === 'attach_source') {
        $id = (int) (isset($_POST['analysis_id']) ? $_POST['analysis_id'] : 0);
        $file = isset($_FILES['source_file']) ? $_FILES['source_file'] : array();
        $result = FormatAnalysisService::attachSourceAndExtract($id, $project['id'], $file);
        if (!empty($result['ok'])) {
            $n = (int) $result['count'];
            flash('success', $n > 0
                ? ('원본을 다시 첨부하고 이미지 ' . $n . '개를 추출했습니다.')
                : '원본을 첨부했습니다. 추출 가능한 이미지는 없었습니다.');
        } else {
            flash('error', isset($result['error']) ? $result['error'] : '원본 첨부·추출에 실패했습니다.');
        }
        redirect('format-analysis.php?id=' . $id);
    }

    if ($action === 'save_profile') {
        $id = (int) (isset($_POST['profile_id']) ? $_POST['profile_id'] : 0);
        $payload = array(
            'vendor' => isset($_POST['vendor']) ? $_POST['vendor'] : 'Other',
            'format_key' => isset($_POST['format_key']) ? $_POST['format_key'] : '',
            'format_name' => isset($_POST['format_name']) ? $_POST['format_name'] : '',
            'extensions' => isset($_POST['extensions']) ? $_POST['extensions'] : '',
            'magic_signature' => isset($_POST['magic_signature']) ? $_POST['magic_signature'] : '',
            'container_type' => isset($_POST['container_type']) ? $_POST['container_type'] : '',
            'structure_notes' => isset($_POST['structure_notes']) ? $_POST['structure_notes'] : '',
            'field_schema' => isset($_POST['field_schema']) ? $_POST['field_schema'] : '',
            'status' => isset($_POST['status']) ? $_POST['status'] : 'active',
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : '',
        );
        // validate JSON if provided
        if ($payload['field_schema'] !== '') {
            $tmp = json_decode($payload['field_schema'], true);
            if ($tmp === null && json_last_error() !== JSON_ERROR_NONE) {
                flash('error', 'field_schema JSON 형식이 올바르지 않습니다.');
                redirect('format-analysis.php?tab=profiles');
            }
        }
        $saved = FormatAnalysisService::saveProfile($project['id'], $payload, $user ? $user['id'] : null, $id);
        if ($saved) {
            flash('success', $id ? '포맷 프로필이 수정되었습니다.' : '포맷 프로필이 등록되었습니다.');
        } else {
            flash('error', 'format_key / format_name 은 필수입니다.');
        }
        redirect('format-analysis.php?tab=profiles');
    }

    if ($action === 'delete_profile') {
        $id = (int) (isset($_POST['profile_id']) ? $_POST['profile_id'] : 0);
        if (FormatAnalysisService::deleteProfile($id, $project['id'])) {
            flash('success', '포맷 프로필이 삭제되었습니다.');
        } else {
            flash('error', '삭제에 실패했습니다.');
        }
        redirect('format-analysis.php?tab=profiles');
    }
}

$profiles = FormatAnalysisService::getProfiles($project['id']);
$analyses = FormatAnalysisService::getAnalyses($project['id']);
$detail = null;
$summary = null;
$sourceMissing = false;
if ($viewId > 0) {
    $detail = FormatAnalysisService::getAnalysisById($viewId);
    if ($detail && (int) $detail['project_id'] === (int) $project['id']) {
        $ensure = FormatAnalysisService::ensureImagesExtracted($viewId, $project['id']);
        $sourceMissing = !empty($ensure['source_missing']);
        if (!$sourceMissing) {
            $sourceMissing = !FormatAnalysisService::getSourcePath($project['id'], $detail['stored_name']);
        }
        $detail = FormatAnalysisService::getAnalysisById($viewId);
        $summary = json_decode($detail['summary_json'], true);
        if (!is_array($summary)) {
            $summary = array();
        }
        // 원본이 있으면 Formtec 편집화면 기준으로 layout·이미지 재파싱
        $summary = FormatAnalysisService::ensureLayoutParsed($viewId, $project['id'], $summary);
        // 디스크/DB base64를 채워 <img>에 data URI로 바로 표시
        $summary = FormatAnalysisService::hydrateImagesForDisplay($viewId, $project['id'], $summary);
        $tab = 'analyses';
    } else {
        $detail = null;
        flash('error', '분석 기록을 찾을 수 없습니다.');
        redirect('format-analysis.php');
    }
}

$editProfileId = isset($_GET['edit_profile']) ? (int) $_GET['edit_profile'] : 0;
$editProfile = $editProfileId ? FormatAnalysisService::getProfileById($editProfileId) : null;
if ($editProfile && (int) $editProfile['project_id'] !== (int) $project['id']) {
    $editProfile = null;
}

$pageTitle = '포맷 분석';
$currentPage = 'format-analysis';

render_page(__DIR__ . '/views/format-analysis.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker',
    'profiles', 'analyses', 'detail', 'summary', 'tab', 'editProfile', 'user', 'sourceMissing'
));
