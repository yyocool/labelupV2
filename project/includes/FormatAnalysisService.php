<?php

class FormatAnalysisService
{
    public static function getVendors()
    {
        return array(
            'Formtec' => '폼텍 (Formtec)',
            'Avery' => 'Avery',
            'BarTender' => 'BarTender',
            'NiceLabel' => 'NiceLabel',
            'Other' => '기타',
        );
    }

    public static function ensureTables()
    {
        // migrate_check에서 생성. 호출 안전장치.
        migrate_format_analysis_tables(Database::getConnection());
    }

    public static function getProfiles($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM format_profiles WHERE project_id = ? ORDER BY vendor ASC, format_key ASC');
        $stmt->execute(array((int) $projectId));
        return $stmt->fetchAll();
    }

    public static function getProfileById($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM format_profiles WHERE id = ?');
        $stmt->execute(array((int) $id));
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    public static function getProfileByKey($projectId, $formatKey)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM format_profiles WHERE project_id = ? AND format_key = ?');
        $stmt->execute(array((int) $projectId, $formatKey));
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    public static function saveProfile($projectId, array $data, $userId = null, $id = 0)
    {
        $db = Database::getConnection();
        $payload = array(
            'vendor' => isset($data['vendor']) ? trim($data['vendor']) : 'Other',
            'format_key' => isset($data['format_key']) ? trim($data['format_key']) : '',
            'format_name' => isset($data['format_name']) ? trim($data['format_name']) : '',
            'extensions' => isset($data['extensions']) ? trim($data['extensions']) : '',
            'magic_signature' => isset($data['magic_signature']) ? trim($data['magic_signature']) : '',
            'container_type' => isset($data['container_type']) ? trim($data['container_type']) : '',
            'structure_notes' => isset($data['structure_notes']) ? $data['structure_notes'] : '',
            'field_schema' => isset($data['field_schema']) ? $data['field_schema'] : '',
            'status' => isset($data['status']) ? $data['status'] : 'active',
            'notes' => isset($data['notes']) ? $data['notes'] : '',
        );
        if ($payload['format_key'] === '' || $payload['format_name'] === '') {
            return false;
        }
        if (is_array($payload['field_schema'])) {
            $payload['field_schema'] = json_encode($payload['field_schema'], JSON_UNESCAPED_UNICODE);
        }

        if ($id > 0) {
            $stmt = $db->prepare('UPDATE format_profiles SET
                vendor=?, format_key=?, format_name=?, extensions=?, magic_signature=?, container_type=?,
                structure_notes=?, field_schema=?, status=?, notes=?, updated_by=?, updated_at=NOW()
                WHERE id=? AND project_id=?');
            $stmt->execute(array(
                $payload['vendor'], $payload['format_key'], $payload['format_name'], $payload['extensions'],
                $payload['magic_signature'], $payload['container_type'], $payload['structure_notes'],
                $payload['field_schema'], $payload['status'], $payload['notes'],
                $userId, $id, (int) $projectId,
            ));
            return $id;
        }

        $stmt = $db->prepare('INSERT INTO format_profiles
            (project_id, vendor, format_key, format_name, extensions, magic_signature, container_type,
             structure_notes, field_schema, status, notes, created_by, updated_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array(
            (int) $projectId, $payload['vendor'], $payload['format_key'], $payload['format_name'],
            $payload['extensions'], $payload['magic_signature'], $payload['container_type'],
            $payload['structure_notes'], $payload['field_schema'], $payload['status'], $payload['notes'],
            $userId, $userId,
        ));
        return (int) $db->lastInsertId();
    }

    public static function deleteProfile($id, $projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM format_profiles WHERE id = ? AND project_id = ?');
        return $stmt->execute(array((int) $id, (int) $projectId));
    }

    public static function getAnalyses($projectId, $limit = 50)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT a.*, p.format_name, p.vendor AS profile_vendor, u.name AS uploader_name
            FROM format_analyses a
            LEFT JOIN format_profiles p ON p.id = a.profile_id
            LEFT JOIN users u ON u.id = a.uploaded_by
            WHERE a.project_id = ?
            ORDER BY a.id DESC
            LIMIT ' . (int) $limit);
        $stmt->execute(array((int) $projectId));
        return $stmt->fetchAll();
    }

    public static function getAnalysisById($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT a.*, p.format_name, p.vendor AS profile_vendor, u.name AS uploader_name
            FROM format_analyses a
            LEFT JOIN format_profiles p ON p.id = a.profile_id
            LEFT JOIN users u ON u.id = a.uploaded_by
            WHERE a.id = ?');
        $stmt->execute(array((int) $id));
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    public static function updateAnalysisNotes($id, $projectId, $notes, $userId = null)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE format_analyses SET analyst_notes = ?, updated_at = NOW() WHERE id = ? AND project_id = ?');
        return $stmt->execute(array($notes, (int) $id, (int) $projectId));
    }

    public static function deleteAnalysis($id, $projectId)
    {
        $row = self::getAnalysisById($id);
        if (!$row || (int) $row['project_id'] !== (int) $projectId) {
            return false;
        }
        if (!empty($row['stored_name'])) {
            $path = self::getSourcePath($projectId, $row['stored_name']);
            if ($path) {
                @unlink($path);
            }
        }
        self::deleteExtractedImages($projectId, $id);
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM format_analyses WHERE id = ? AND project_id = ?');
        return $stmt->execute(array((int) $id, (int) $projectId));
    }

    /**
     * 여러 분석 기록 일괄 삭제
     * @param int[] $ids
     * @return int 삭제된 건수
     */
    public static function deleteAnalyses(array $ids, $projectId)
    {
        $deleted = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            if (self::deleteAnalysis($id, $projectId)) {
                $deleted++;
            }
        }
        return $deleted;
    }

    public static function uploadDir($projectId)
    {
        $dir = APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'format-analysis' . DIRECTORY_SEPARATOR . (int) $projectId;
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                return $dir;
            }
        }
        // 쓰기 가능 여부 확인용
        if (!is_writable($dir)) {
            @chmod($dir, 0775);
        }
        return $dir;
    }

    public static function imagesDir($projectId, $analysisId)
    {
        $dir = self::uploadDir($projectId) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . (int) $analysisId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (is_dir($dir) && !is_writable($dir)) {
            @chmod($dir, 0775);
        }
        return $dir;
    }

    public static function getSourcePath($projectId, $storedName)
    {
        if ($storedName === '' || $storedName === null) {
            return null;
        }
        $path = self::uploadDir($projectId) . DIRECTORY_SEPARATOR . basename($storedName);
        return is_file($path) ? $path : null;
    }

    /**
     * blob → 화면/DB용 메타 (디스크 없이도 base64 포함)
     */
    public static function blobsToImageMeta(array $blobs)
    {
        $saved = array();
        $i = 0;
        foreach ($blobs as $blob) {
            if (!isset($blob['data']) || !is_string($blob['data']) || strlen($blob['data']) < 100) {
                continue;
            }
            $i++;
            $ext = isset($blob['ext']) ? preg_replace('/[^a-z0-9]/', '', strtolower($blob['ext'])) : 'jpg';
            if ($ext === '' || $ext === 'jpeg') {
                $ext = 'jpg';
            }
            $item = array(
                'index' => $i,
                'filename' => sprintf('img_%02d.%s', $i, $ext),
                'ext' => $ext,
                'mime' => isset($blob['mime']) ? $blob['mime'] : 'image/jpeg',
                'size' => strlen($blob['data']),
                'span_size' => isset($blob['size']) ? (int) $blob['size'] : strlen($blob['data']),
                'offset' => isset($blob['offset']) ? (int) $blob['offset'] : 0,
                'source_entry' => isset($blob['source_entry']) ? $blob['source_entry'] : '',
                'source_format' => isset($blob['source_format']) ? $blob['source_format'] : '',
                'kind' => isset($blob['kind']) ? $blob['kind'] : 'image_object',
                'object_type' => isset($blob['object_type']) ? $blob['object_type'] : null,
                'object_type_name' => !empty($blob['object_type_name'])
                    ? $blob['object_type_name']
                    : (!empty($blob['object_type']) ? 'Formtec Image Object' : ''),
                'stored_on_disk' => false,
            );
            if (strlen($blob['data']) <= 2 * 1024 * 1024) {
                $item['data_base64'] = base64_encode($blob['data']);
            }
            $saved[] = $item;
        }
        return $saved;
    }

    public static function encodeSummaryJson(array $summary)
    {
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $json = json_encode($summary, $flags);
        if ($json === false) {
            // 최후 수단: 이미지 base64만 남기고 위험 필드 제거
            if (!empty($summary['extracted_images'])) {
                $minimal = array(
                    'extracted_images' => $summary['extracted_images'],
                    'embedded_image_count' => count($summary['extracted_images']),
                    'encode_error' => json_last_error_msg(),
                );
                $json = json_encode($minimal, $flags);
            }
            if ($json === false) {
                $json = '{}';
            }
        }
        return $json;
    }

    /**
     * 파서 blob을 디스크(+ base64 폴백)로 저장하고 메타 배열 반환
     */
    public static function saveExtractedImages($projectId, $analysisId, array $blobs)
    {
        self::deleteExtractedImages($projectId, $analysisId);
        $saved = self::blobsToImageMeta($blobs);
        if (empty($saved)) {
            return $saved;
        }
        $dir = self::imagesDir($projectId, $analysisId);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        foreach ($saved as $i => $item) {
            if (empty($item['data_base64'])) {
                continue;
            }
            $bytes = base64_decode($item['data_base64'], true);
            if ($bytes === false) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item['filename'];
            $written = @file_put_contents($path, $bytes);
            if ($written !== false && is_file($path) && filesize($path) > 0) {
                $saved[$i]['stored_on_disk'] = true;
            }
        }
        return $saved;
    }

    public static function deleteExtractedImages($projectId, $analysisId)
    {
        $dir = self::imagesDir($projectId, $analysisId);
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
        @rmdir($dir);
    }

    public static function getImagePath($projectId, $analysisId, $filename)
    {
        $filename = basename($filename);
        if (!preg_match('/^img_\d{2}\.(jpg|jpeg|png|gif|bmp)$/i', $filename)) {
            return null;
        }
        $path = self::imagesDir($projectId, $analysisId) . DIRECTORY_SEPARATOR . $filename;
        return is_file($path) ? $path : null;
    }

    /**
     * 화면 표시용 src 생성 (data URI 우선 — 별도 요청/세션/경로 이슈 회피)
     */
    public static function imageSrc($analysisId, array $img)
    {
        $mime = !empty($img['mime']) ? $img['mime'] : 'image/jpeg';
        if (!empty($img['data_base64'])) {
            return 'data:' . $mime . ';base64,' . $img['data_base64'];
        }
        if (!empty($img['filename']) && function_exists('url')) {
            return url('format-analysis-image.php?id=' . (int) $analysisId . '&file=' . rawurlencode($img['filename']));
        }
        return '';
    }

    /**
     * 상세 화면용: 디스크/원본에서 base64를 채워 바로 표시 가능하게 함
     */
    public static function hydrateImagesForDisplay($analysisId, $projectId, array $summary)
    {
        if (empty($summary['extracted_images']) || !is_array($summary['extracted_images'])) {
            return $summary;
        }

        $changed = false;
        $images = $summary['extracted_images'];
        foreach ($images as $i => $img) {
            if (!empty($img['data_base64'])) {
                continue;
            }
            $filename = isset($img['filename']) ? $img['filename'] : '';
            if ($filename === '') {
                continue;
            }
            $payload = self::getImagePayload($projectId, $analysisId, $filename);
            if ($payload) {
                $images[$i]['data_base64'] = base64_encode($payload['bytes']);
                $images[$i]['mime'] = $payload['mime'];
                $images[$i]['size'] = strlen($payload['bytes']);
                $changed = true;
            }
        }

        // extracted_images 는 있는데 전부 비어 있고 원본이 있으면 재추출
        $needExtract = true;
        foreach ($images as $img) {
            if (!empty($img['data_base64'])) {
                $needExtract = false;
                break;
            }
        }
        if ($needExtract) {
            $row = self::getAnalysisById($analysisId);
            if ($row && self::getSourcePath($projectId, $row['stored_name'])) {
                $result = self::reextractImages($analysisId, $projectId);
                if (!empty($result['ok']) && !empty($result['summary'])) {
                    return $result['summary'];
                }
            }
        }

        if ($changed) {
            $summary['extracted_images'] = $images;
            self::persistExtractedImagesMeta($analysisId, $projectId, $summary, $images);
        }
        return $summary;
    }

    /**
     * 디스크 파일이 없으면 summary_json 의 base64 폴백 반환
     * @return array|null { mime, bytes }
     */
    public static function getImagePayload($projectId, $analysisId, $filename)
    {
        $filename = basename($filename);
        $path = self::getImagePath($projectId, $analysisId, $filename);
        if ($path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mimeMap = array(
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png', 'gif' => 'image/gif', 'bmp' => 'image/bmp',
            );
            $bytes = @file_get_contents($path);
            if ($bytes !== false && $bytes !== '') {
                return array(
                    'mime' => isset($mimeMap[$ext]) ? $mimeMap[$ext] : 'application/octet-stream',
                    'bytes' => $bytes,
                );
            }
        }

        $row = self::getAnalysisById($analysisId);
        if (!$row || (int) $row['project_id'] !== (int) $projectId) {
            return null;
        }
        $summary = json_decode($row['summary_json'], true);
        if (empty($summary['extracted_images']) || !is_array($summary['extracted_images'])) {
            return null;
        }
        foreach ($summary['extracted_images'] as $img) {
            if (empty($img['filename']) || $img['filename'] !== $filename) {
                continue;
            }
            if (empty($img['data_base64'])) {
                continue;
            }
            $bytes = base64_decode($img['data_base64'], true);
            if ($bytes === false || strlen($bytes) < 100) {
                continue;
            }
            // 가능하면 디스크에도 캐시
            $dir = self::imagesDir($projectId, $analysisId);
            if (is_dir($dir)) {
                @file_put_contents($dir . DIRECTORY_SEPARATOR . $filename, $bytes);
            }
            return array(
                'mime' => !empty($img['mime']) ? $img['mime'] : 'image/jpeg',
                'bytes' => $bytes,
            );
        }
        return null;
    }

    private static function persistExtractedImagesMeta($analysisId, $projectId, array $summary, array $saved)
    {
        $summary['extracted_images'] = $saved;
        $summary['embedded_image_count'] = count($saved);
        if (!empty($saved)) {
            $summary['embedded_jpeg'] = array(
                'present' => true,
                'offset' => $saved[0]['offset'],
                'size' => $saved[0]['size'],
                'type' => $saved[0]['ext'],
                'count' => count($saved),
            );
        } else {
            $summary['embedded_jpeg'] = array('present' => false, 'count' => 0);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE format_analyses SET summary_json = ?, updated_at = NOW() WHERE id = ? AND project_id = ?');
        $stmt->execute(array(
            self::encodeSummaryJson($summary),
            (int) $analysisId,
            (int) $projectId,
        ));
        return $summary;
    }

    /**
     * 기존 분석 기록에서 이미지 재추출
     */
    public static function reextractImages($analysisId, $projectId)
    {
        $row = self::getAnalysisById($analysisId);
        if (!$row || (int) $row['project_id'] !== (int) $projectId) {
            return array('ok' => false, 'error' => '분석 기록을 찾을 수 없습니다.', 'source_missing' => false);
        }
        $src = self::getSourcePath($projectId, $row['stored_name']);
        if (!$src) {
            return array(
                'ok' => false,
                'error' => '원본 파일이 서버에 없습니다. 아래에서 같은 파일을 다시 첨부해 주세요.',
                'source_missing' => true,
            );
        }

        self::deleteExtractedImages($projectId, $analysisId);

        // detectAndParse 로 JPEG+WMF 클립아트까지 함께 추출 (layout 도 갱신)
        $parsed = FormatParser::detectAndParse($src, $row['original_name']);
        $blobs = array();
        if (!empty($parsed['ok']) && !empty($parsed['_image_blobs'])) {
            $blobs = $parsed['_image_blobs'];
        }
        if (empty($blobs)) {
            $blobs = FormatParser::extractImagesFromStoredFile($src);
        }

        $saved = self::saveExtractedImages($projectId, $analysisId, $blobs);
        if (empty($saved) && !empty($blobs)) {
            return array(
                'ok' => false,
                'error' => '이미지는 찾았지만 메타 저장에 실패했습니다.',
                'source_missing' => false,
                'blob_count' => count($blobs),
            );
        }

        $summary = json_decode($row['summary_json'], true);
        if (!is_array($summary)) {
            $summary = array();
        }
        if (!empty($parsed['ok']) && !empty($parsed['summary']['layout'])) {
            $summary['layout'] = $parsed['summary']['layout'];
        }
        if (!empty($parsed['ok']) && !empty($parsed['summary']['data_file'])) {
            $summary['data_file'] = $parsed['summary']['data_file'];
        }
        if (!empty($summary['layout']['objects']) && !empty($saved)) {
            $summary['layout']['objects'] = self::rematchImageObjectIndexes(
                $summary['layout']['objects'],
                $saved
            );
        }
        $summary = self::persistExtractedImagesMeta($analysisId, $projectId, $summary, $saved);

        return array('ok' => true, 'count' => count($saved), 'images' => $saved, 'summary' => $summary);
    }

    /**
     * 원본이 사라진 분석 기록에 파일을 다시 첨부하고 이미지 추출
     */
    public static function attachSourceAndExtract($analysisId, $projectId, array $file)
    {
        $row = self::getAnalysisById($analysisId);
        if (!$row || (int) $row['project_id'] !== (int) $projectId) {
            return array('ok' => false, 'error' => '분석 기록을 찾을 수 없습니다.');
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return array('ok' => false, 'error' => '첨부된 파일이 없습니다.');
        }
        if (!empty($file['error'])) {
            return array('ok' => false, 'error' => '업로드 오류 코드: ' . $file['error']);
        }

        $original = isset($file['name']) ? $file['name'] : $row['original_name'];
        $size = isset($file['size']) ? (int) $file['size'] : filesize($file['tmp_name']);
        if ($size <= 0 || $size > 20 * 1024 * 1024) {
            return array('ok' => false, 'error' => '파일 크기는 1B ~ 20MB 여야 합니다.');
        }

        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $hash = hash_file('sha256', $file['tmp_name']);
        $stored = date('YmdHis') . '_' . substr($hash, 0, 12) . ($ext ? '.' . $ext : '');
        $dir = self::uploadDir($projectId);
        if (!is_dir($dir) || !is_writable($dir)) {
            return array('ok' => false, 'error' => '업로드 디렉터리에 쓸 수 없습니다: ' . $dir);
        }
        $dest = $dir . DIRECTORY_SEPARATOR . $stored;
        if (!@move_uploaded_file($file['tmp_name'], $dest) && !@copy($file['tmp_name'], $dest)) {
            return array('ok' => false, 'error' => '원본 파일 저장에 실패했습니다.');
        }
        if (!is_file($dest)) {
            return array('ok' => false, 'error' => '원본 파일이 저장되지 않았습니다.');
        }

        // 이전 원본 제거
        $old = self::getSourcePath($projectId, $row['stored_name']);
        if ($old && realpath($old) !== realpath($dest)) {
            @unlink($old);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE format_analyses SET stored_name = ?, original_name = ?, file_size = ?, file_hash = ?, updated_at = NOW() WHERE id = ? AND project_id = ?');
        $stmt->execute(array($stored, $original, $size, $hash, (int) $analysisId, (int) $projectId));

        $result = self::reextractImages($analysisId, $projectId);
        if (empty($result['ok'])) {
            return $result;
        }
        $result['attached'] = true;
        $result['stored_name'] = $stored;
        return $result;
    }

    /**
     * 상세 조회 시 이미지가 없으면 원본에서 자동 추출
     * 클립아트(WMF)가 빠진 채로 JPEG만 있으면 원본에서 다시 추출
     */
    public static function ensureImagesExtracted($analysisId, $projectId)
    {
        $row = self::getAnalysisById($analysisId);
        if (!$row || (int) $row['project_id'] !== (int) $projectId) {
            return array('ok' => false, 'images' => array(), 'source_missing' => false);
        }
        $summary = json_decode($row['summary_json'], true);
        if (!is_array($summary)) {
            $summary = array();
        }

        $hasClipart = false;
        $alive = array();
        if (!empty($summary['extracted_images']) && is_array($summary['extracted_images'])) {
            foreach ($summary['extracted_images'] as $img) {
                if (!empty($img['kind']) && ($img['kind'] === 'clipart_object' || (!empty($img['source_format']) && $img['source_format'] === 'wmf'))) {
                    $hasClipart = true;
                }
                if (empty($img['filename'])) {
                    continue;
                }
                if (self::getImagePath($projectId, $analysisId, $img['filename']) || !empty($img['data_base64'])) {
                    $alive[] = $img;
                }
            }
        }

        $src = self::getSourcePath($projectId, $row['stored_name']);
        if (!$src) {
            if (!empty($alive)) {
                return array('ok' => true, 'images' => $alive, 'auto' => false, 'source_missing' => true);
            }
            return array(
                'ok' => false,
                'images' => array(),
                'source_missing' => true,
                'error' => '원본 파일이 서버에 없습니다.',
            );
        }

        // 원본에 WMF 클립아트가 있는데 추출본에 없으면 강제 재추출
        $needClipart = false;
        $raw = @file_get_contents($src);
        if ($raw !== false && strpos($raw, "\xD7\xCD\xC6\x9A") !== false) {
            $needClipart = !$hasClipart;
        }

        if (!$needClipart && !empty($alive) && count($alive) === count($summary['extracted_images'])) {
            return array('ok' => true, 'images' => $alive, 'auto' => false, 'source_missing' => false);
        }

        return self::reextractImages($analysisId, $projectId);
    }

    /**
     * 상세 화면용: 원본이 있으면 layout 을 최신 파서로 갱신
     */
    public static function ensureLayoutParsed($analysisId, $projectId, array $summary)
    {
        $row = self::getAnalysisById($analysisId);
        if (!$row || (int) $row['project_id'] !== (int) $projectId) {
            return $summary;
        }
        $src = self::getSourcePath($projectId, $row['stored_name']);
        if (!$src) {
            return $summary;
        }
        $parsed = FormatParser::detectAndParse($src, $row['original_name']);
        if (empty($parsed['ok']) || empty($parsed['summary']['layout'])) {
            return $summary;
        }
        $summary['layout'] = $parsed['summary']['layout'];
        if (!empty($parsed['summary']['data_file'])) {
            $summary['data_file'] = $parsed['summary']['data_file'];
        }

        // 원본 재파싱 시 이미지(JPEG/WMF 클립아트 등)도 다시 맞춤
        $blobs = isset($parsed['_image_blobs']) ? $parsed['_image_blobs'] : array();
        if (!empty($blobs)) {
            $saved = self::saveExtractedImages($projectId, $analysisId, $blobs);
            if (!empty($saved)) {
                $summary['extracted_images'] = $saved;
                $summary['embedded_image_count'] = count($saved);
                // layout image_index 를 사진/클립아트 kind 기준으로 재매칭 (같은 JPEG 2장 방지)
                if (!empty($summary['layout']['objects'])) {
                    $summary['layout']['objects'] = self::rematchImageObjectIndexes(
                        $summary['layout']['objects'],
                        $saved
                    );
                }
            }
        } elseif (empty($summary['extracted_images'])) {
            $summary['extracted_images'] = array();
            $summary['embedded_image_count'] = 0;
        }

        self::persistExtractedImagesMeta($analysisId, $projectId, $summary, isset($summary['extracted_images']) ? $summary['extracted_images'] : array());
        return $summary;
    }

    /**
     * 레이아웃 이미지 오브젝트 → 추출 이미지 인덱스 재매칭
     * 클립아트 슬롯에 JPEG 가 들어가는 것을 막음
     */
    public static function rematchImageObjectIndexes(array $objects, array $savedImages)
    {
        $clipIdx = null;
        $photoIdx = null;
        foreach ($savedImages as $i => $img) {
            $kind = isset($img['kind']) ? $img['kind'] : '';
            $ext = isset($img['ext']) ? strtolower($img['ext']) : '';
            $mime = isset($img['mime']) ? $img['mime'] : '';
            $isClip = ($kind === 'clipart_object')
                || (!empty($img['source_format']) && $img['source_format'] === 'wmf')
                || ($ext === 'png' && $kind !== 'image_object')
                || ($mime === 'image/png' && $kind === 'clipart_object');
            if ($isClip && $clipIdx === null) {
                $clipIdx = (int) $i;
            } elseif (!$isClip && $photoIdx === null) {
                $photoIdx = (int) $i;
            }
        }
        // png 가 있으면 클립아트 후보
        if ($clipIdx === null) {
            foreach ($savedImages as $i => $img) {
                $ext = isset($img['ext']) ? strtolower($img['ext']) : '';
                if ($ext === 'png') {
                    $clipIdx = (int) $i;
                    break;
                }
            }
        }

        $seen = array();
        foreach ($objects as &$obj) {
            if (empty($obj['kind']) || $obj['kind'] !== 'image') {
                continue;
            }
            $label = isset($obj['label']) ? $obj['label'] : '';
            $isClipLabel = (strpos($label, '클립') !== false || stripos($label, 'clip') !== false);

            if ($isClipLabel) {
                $obj['image_index'] = $clipIdx; // null 이면 미표시 (JPEG 대체 금지)
            } else {
                $obj['image_index'] = $photoIdx;
            }

            if ($obj['image_index'] !== null) {
                $ii = (int) $obj['image_index'];
                if (isset($seen[$ii])) {
                    // 동일 인덱스 중복이면 클립은 비우고 사진은 유지
                    if ($isClipLabel) {
                        $obj['image_index'] = ($clipIdx !== null && $clipIdx !== $ii) ? $clipIdx : null;
                    }
                } else {
                    $seen[$ii] = true;
                }
            }
        }
        unset($obj);
        return $objects;
    }

    public static function analyzeUpload($projectId, array $file, $userId = null, $notes = '')
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return array('ok' => false, 'error' => '업로드된 파일이 없습니다.');
        }
        if (!empty($file['error'])) {
            return array('ok' => false, 'error' => '업로드 오류 코드: ' . $file['error']);
        }

        $original = isset($file['name']) ? $file['name'] : 'upload.bin';
        $size = isset($file['size']) ? (int) $file['size'] : 0;
        if ($size <= 0 || $size > 20 * 1024 * 1024) {
            return array('ok' => false, 'error' => '파일 크기는 1B ~ 20MB 여야 합니다.');
        }

        $tmp = $file['tmp_name'];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $hash = hash_file('sha256', $tmp);

        // 임시 파일에서 먼저 파싱·이미지 추출 (move 실패 대비)
        $parsed = FormatParser::detectAndParse($tmp, $original);
        if (empty($parsed['ok'])) {
            $err = isset($parsed['summary']['error']) ? $parsed['summary']['error'] : '분석 실패';
            return array('ok' => false, 'error' => $err);
        }

        $blobs = isset($parsed['_image_blobs']) ? $parsed['_image_blobs'] : array();
        unset($parsed['_image_blobs']);
        if (empty($blobs)) {
            $blobs = FormatParser::extractImagesFromStoredFile($tmp);
        }

        $stored = date('YmdHis') . '_' . substr($hash, 0, 12) . ($ext ? '.' . $ext : '');
        $dir = self::uploadDir($projectId);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (!is_dir($dir)) {
            return array('ok' => false, 'error' => '업로드 디렉터리를 만들 수 없습니다: ' . $dir);
        }
        $dest = $dir . DIRECTORY_SEPARATOR . $stored;
        if (!@move_uploaded_file($tmp, $dest)) {
            if (!@copy($tmp, $dest)) {
                return array('ok' => false, 'error' => '파일 저장에 실패했습니다: ' . $dest);
            }
            @unlink($tmp);
        }
        if (!is_file($dest) || filesize($dest) <= 0) {
            return array('ok' => false, 'error' => '파일이 저장되지 않았습니다. 디스크/권한을 확인하세요.');
        }

        // move 후 원본에서 다시 추출 (WMF 클립아트 포함 — tmp 경로 이슈 대비)
        $parsedDest = FormatParser::detectAndParse($dest, $original);
        if (!empty($parsedDest['ok'])) {
            if (!empty($parsedDest['_image_blobs']) && count($parsedDest['_image_blobs']) >= count($blobs)) {
                $blobs = $parsedDest['_image_blobs'];
            }
            if (!empty($parsedDest['summary']['layout'])) {
                $parsed['summary']['layout'] = $parsedDest['summary']['layout'];
            }
            if (!empty($parsedDest['summary']['data_file'])) {
                $parsed['summary']['data_file'] = $parsedDest['summary']['data_file'];
            }
        }
        if (empty($blobs)) {
            $blobs = FormatParser::extractImagesFromStoredFile($dest);
        }

        $profileId = null;
        $formatKey = isset($parsed['format_key']) ? $parsed['format_key'] : '';
        if ($formatKey !== '' && $formatKey !== 'unknown') {
            $profile = self::getProfileByKey($projectId, $formatKey);
            if ($profile) {
                $profileId = (int) $profile['id'];
            }
        }

        $summary = isset($parsed['summary']) && is_array($parsed['summary']) ? $parsed['summary'] : array();

        // 분석 ID 전에 base64 메타를 만들어 INSERT에 포함 (UPDATE 실패해도 이미지가 남도록)
        $imageMeta = self::blobsToImageMeta($blobs);
        if (!empty($imageMeta)) {
            $summary['extracted_images'] = $imageMeta;
            $summary['embedded_image_count'] = count($imageMeta);
            $summary['embedded_jpeg'] = array(
                'present' => true,
                'offset' => $imageMeta[0]['offset'],
                'size' => $imageMeta[0]['size'],
                'type' => $imageMeta[0]['ext'],
                'count' => count($imageMeta),
            );
            if (!empty($summary['layout']['objects'])) {
                $summary['layout']['objects'] = self::rematchImageObjectIndexes(
                    $summary['layout']['objects'],
                    $imageMeta
                );
            }
        }
        $summary['storage_dir'] = self::uploadDir($projectId);

        $id = self::insertAnalysis($projectId, array(
            'profile_id' => $profileId,
            'original_name' => $original,
            'stored_name' => $stored,
            'file_size' => $size,
            'file_hash' => $hash,
            'detected_format' => isset($parsed['detected_format']) ? $parsed['detected_format'] : '',
            'detected_vendor' => isset($parsed['detected_vendor']) ? $parsed['detected_vendor'] : '',
            'detected_version' => isset($parsed['detected_version']) ? $parsed['detected_version'] : '',
            'product_sku' => isset($parsed['product_sku']) ? $parsed['product_sku'] : '',
            'product_name' => isset($parsed['product_name']) ? $parsed['product_name'] : '',
            'paper' => isset($parsed['paper']) ? $parsed['paper'] : '',
            'category' => isset($parsed['category']) ? $parsed['category'] : '',
            'confidence' => isset($parsed['confidence']) ? (int) $parsed['confidence'] : 0,
            'summary_json' => self::encodeSummaryJson($summary),
            'analyst_notes' => $notes,
            'uploaded_by' => $userId,
        ));

        // 디스크에도 저장 시도 (실패해도 DB base64로 표시 가능)
        $savedImages = self::saveExtractedImages($projectId, $id, $blobs);
        if (!empty($savedImages)) {
            $summary = self::persistExtractedImagesMeta($id, $projectId, $summary, $savedImages);
            $parsed['summary'] = $summary;
            $imageMeta = $savedImages;
        } else {
            $parsed['summary'] = $summary;
        }

        return array(
            'ok' => true,
            'id' => $id,
            'parsed' => $parsed,
            'profile_id' => $profileId,
            'images' => $imageMeta,
            'image_count' => count($imageMeta),
        );
    }

    public static function insertAnalysis($projectId, array $row)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('INSERT INTO format_analyses
            (project_id, profile_id, original_name, stored_name, file_size, file_hash,
             detected_format, detected_vendor, detected_version, product_sku, product_name,
             paper, category, confidence, summary_json, analyst_notes, uploaded_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array(
            (int) $projectId,
            $row['profile_id'],
            $row['original_name'],
            $row['stored_name'],
            (int) $row['file_size'],
            $row['file_hash'],
            $row['detected_format'],
            $row['detected_vendor'],
            $row['detected_version'],
            $row['product_sku'],
            $row['product_name'],
            $row['paper'],
            $row['category'],
            (int) $row['confidence'],
            $row['summary_json'],
            $row['analyst_notes'],
            $row['uploaded_by'],
        ));
        return (int) $db->lastInsertId();
    }

    public static function ensureDefaults($projectId, $userId = null)
    {
        $existing = self::getProfileByKey($projectId, 'formtec_dgz');
        if ($existing) {
            return 0;
        }

        $schema = array(
            'container' => 'ZIP',
            'entries' => array('Data/*.XLS', 'Design/*.dgf'),
            'dgf_magic' => 'Formtec Design Pro 9 Design File',
            'string_encoding' => 'CP949 length-prefixed (DWORD LE)',
            'dimensions' => 'IEEE754 double (inch 추정)',
            'known_fields' => array('version', 'paper', 'product_name', 'product_sku', 'category', 'data_path', 'sheet_ref', 'fonts', 'embedded_jpeg', 'trailer_code'),
            'sample' => array(
                'file' => 'ft9.dgz',
                'version' => '9.4.0.0',
                'sku' => '3105',
                'name' => '주소용 라벨',
                'paper' => 'A4',
                'category' => 'Labels',
                'font' => '맑은 고딕',
                'xls_columns' => array('구분', '일련번호', '기관명'),
            ),
        );

        self::saveProfile($projectId, array(
            'vendor' => 'Formtec',
            'format_key' => 'formtec_dgz',
            'format_name' => 'Formtec Design Pro 패키지 (.dgz)',
            'extensions' => '.dgz, .dgf, .xls',
            'magic_signature' => 'PK\\x03\\x04 (ZIP) / Formtec Design Pro 9 Design File',
            'container_type' => 'zip',
            'structure_notes' => "Formtec Design Pro 9 디자인 패키지.\n"
                . "- .dgz = ZIP 컨테이너\n"
                . "- Design/*.dgf = 레이아웃·객체·폰트·임베디드 이미지\n"
                . "- Data/*.XLS = 가변 데이터 (OLE Excel)\n"
                . "- 문자열: DWORD LE 길이 + CP949\n"
                . "- 치수 후보: little-endian double (인치)\n"
                . "- 레코드 마커: B8 01\n"
                . "- 샘플 ft9.dgz: SKU 3105 주소용 라벨, A4, 맑은 고딕, JPEG 임베드, 트레일러 65,18848/",
            'field_schema' => $schema,
            'status' => 'active',
            'notes' => '초기 시드: ft9.dgz 정밀 분석 기반. 추가 샘플 업로드 시 이 프로필을 계속 갱신하세요.',
        ), $userId);

        return 1;
    }

    public static function mergeProfileFromAnalysis($profileId, $projectId, array $parsed, $userId = null)
    {
        $profile = self::getProfileById($profileId);
        if (!$profile || (int) $profile['project_id'] !== (int) $projectId) {
            return false;
        }

        $schema = array();
        if (!empty($profile['field_schema'])) {
            $decoded = json_decode($profile['field_schema'], true);
            if (is_array($decoded)) {
                $schema = $decoded;
            }
        }

        $samples = isset($schema['observed_samples']) && is_array($schema['observed_samples'])
            ? $schema['observed_samples'] : array();

        $entry = array(
            'at' => date('Y-m-d H:i:s'),
            'version' => isset($parsed['detected_version']) ? $parsed['detected_version'] : '',
            'sku' => isset($parsed['product_sku']) ? $parsed['product_sku'] : '',
            'name' => isset($parsed['product_name']) ? $parsed['product_name'] : '',
            'paper' => isset($parsed['paper']) ? $parsed['paper'] : '',
            'category' => isset($parsed['category']) ? $parsed['category'] : '',
            'confidence' => isset($parsed['confidence']) ? $parsed['confidence'] : 0,
        );
        $samples[] = $entry;
        if (count($samples) > 30) {
            $samples = array_slice($samples, -30);
        }
        $schema['observed_samples'] = $samples;
        $schema['last_merged_at'] = date('Y-m-d H:i:s');

        $notes = $profile['notes'];
        $line = sprintf(
            "[%s] 샘플 반영: ver=%s sku=%s name=%s paper=%s",
            date('Y-m-d H:i'),
            $entry['version'],
            $entry['sku'],
            $entry['name'],
            $entry['paper']
        );
        $notes = trim($notes . "\n" . $line);

        return self::saveProfile($projectId, array(
            'vendor' => $profile['vendor'],
            'format_key' => $profile['format_key'],
            'format_name' => $profile['format_name'],
            'extensions' => $profile['extensions'],
            'magic_signature' => $profile['magic_signature'],
            'container_type' => $profile['container_type'],
            'structure_notes' => $profile['structure_notes'],
            'field_schema' => $schema,
            'status' => $profile['status'],
            'notes' => $notes,
        ), $userId, (int) $profileId);
    }
}
