<?php

class ArchiveService
{
  const MAX_FILE_SIZE = 20971520; // 20MB

  public static function getCategories()
  {
    return array(
      'contract'      => array('label' => '계약서',     'icon' => '📑'),
      'specification' => array('label' => '기획/명세',  'icon' => '📋'),
      'design'        => array('label' => '디자인',     'icon' => '🎨'),
      'reference'     => array('label' => '참고 자료',  'icon' => '📎'),
      'legal'         => array('label' => '법무/행정',  'icon' => '⚖️'),
      'other'         => array('label' => '기타',       'icon' => '📁'),
    );
  }

  public static function getAllowedExtensions()
  {
    return array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'hwp', 'hwpx', 'zip', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'csv');
  }

  public static function getByProject($projectId, $category = null)
  {
    $db = Database::getConnection();
    $sql = 'SELECT d.*, u.name AS uploader
            FROM archive_documents d
            LEFT JOIN users u ON u.id = d.uploaded_by
            WHERE d.project_id = ?';
    $params = array($projectId);
    if ($category && $category !== 'all') {
      $sql .= ' AND d.category = ?';
      $params[] = $category;
    }
    $sql .= ' ORDER BY d.category, d.created_at DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  public static function getById($id)
  {
    $db = Database::getConnection();
    $stmt = $db->prepare('SELECT d.*, u.name AS uploader
                          FROM archive_documents d
                          LEFT JOIN users u ON u.id = d.uploaded_by
                          WHERE d.id = ?');
    $stmt->execute(array($id));
    $row = $stmt->fetch();
    return $row ? $row : null;
  }

  public static function groupByCategory(array $documents)
  {
    $groups = array();
    foreach (self::getCategories() as $key => $meta) {
      $groups[$key] = array('meta' => $meta, 'items' => array());
    }
    foreach ($documents as $doc) {
      $cat = isset($doc['category']) ? $doc['category'] : 'other';
      if (!isset($groups[$cat])) {
        $groups[$cat] = array('meta' => array('label' => $cat, 'icon' => '📁'), 'items' => array());
      }
      $groups[$cat]['items'][] = $doc;
    }
    return $groups;
  }

  public static function getUploadDir($projectId)
  {
    $dir = APP_ROOT . '/storage/uploads/archive/' . (int) $projectId;
    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }
    return $dir;
  }

  public static function getFilePath(array $document)
  {
    return self::getUploadDir($document['project_id']) . '/' . $document['stored_name'];
  }

  public static function create($projectId, array $data, array $file)
  {
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
      throw new InvalidArgumentException('업로드할 파일을 선택해 주세요.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
      throw new RuntimeException('파일 업로드 중 오류가 발생했습니다.');
    }
    if ($file['size'] > self::MAX_FILE_SIZE) {
      throw new RuntimeException('파일 크기는 20MB 이하여야 합니다.');
    }

    $originalName = $file['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, self::getAllowedExtensions(), true)) {
      throw new RuntimeException('허용되지 않는 파일 형식입니다.');
    }

    $categories = self::getCategories();
    $category = isset($data['category']) ? $data['category'] : 'reference';
    if (!isset($categories[$category])) {
      $category = 'reference';
    }

    $title = trim(isset($data['title']) ? $data['title'] : '');
    if ($title === '') {
      $title = pathinfo($originalName, PATHINFO_FILENAME);
    }

    $storedName = secure_random_hex(16) . '.' . $ext;
    $destDir = self::getUploadDir($projectId);
    $destPath = $destDir . '/' . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
      throw new RuntimeException('파일 저장에 실패했습니다.');
    }

    $mime = isset($file['type']) ? $file['type'] : null;
    $userId = current_user() ? current_user()['id'] : null;

    $db = Database::getConnection();
    $stmt = $db->prepare('INSERT INTO archive_documents
      (project_id, category, title, description, original_name, stored_name, file_size, mime_type, uploaded_by)
      VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->execute(array(
      $projectId,
      $category,
      $title,
      isset($data['description']) ? $data['description'] : null,
      $originalName,
      $storedName,
      (int) $file['size'],
      $mime,
      $userId,
    ));

    $id = (int) $db->lastInsertId();
    log_activity($projectId, $userId, 'archive_upload', 'archive_document', $id, $title);
    return $id;
  }

  /** 관리자이거나 본인이 올린 자료인 경우 true */
  public static function canManage(array $doc, $userId = null)
  {
    if (is_admin()) {
      return true;
    }
    if ($userId === null) {
      $user = current_user();
      $userId = $user ? $user['id'] : null;
    }
    if (!$userId || empty($doc['uploaded_by'])) {
      return false;
    }
    return (int) $doc['uploaded_by'] === (int) $userId;
  }

  public static function update($id, array $data, array $file = array())
  {
    $doc = self::getById($id);
    if (!$doc) {
      throw new InvalidArgumentException('자료를 찾을 수 없습니다.');
    }

    $categories = self::getCategories();
    $category = isset($data['category']) ? $data['category'] : $doc['category'];
    if (!isset($categories[$category])) {
      $category = $doc['category'];
    }

    $title = trim(isset($data['title']) ? $data['title'] : '');
    if ($title === '') {
      $title = $doc['title'];
    }
    $description = isset($data['description']) ? $data['description'] : null;

    $originalName = $doc['original_name'];
    $storedName = $doc['stored_name'];
    $fileSize = (int) $doc['file_size'];
    $mime = $doc['mime_type'];
    $newFilePath = null;
    $oldPath = null;

    $hasNewFile = !empty($file['tmp_name']) && is_uploaded_file($file['tmp_name']);
    if ($hasNewFile) {
      if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('파일 업로드 중 오류가 발생했습니다.');
      }
      if ($file['size'] > self::MAX_FILE_SIZE) {
        throw new RuntimeException('파일 크기는 20MB 이하여야 합니다.');
      }
      $originalName = $file['name'];
      $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
      if (!in_array($ext, self::getAllowedExtensions(), true)) {
        throw new RuntimeException('허용되지 않는 파일 형식입니다.');
      }
      $storedName = secure_random_hex(16) . '.' . $ext;
      $newFilePath = self::getUploadDir($doc['project_id']) . '/' . $storedName;
      if (!move_uploaded_file($file['tmp_name'], $newFilePath)) {
        throw new RuntimeException('파일 저장에 실패했습니다.');
      }
      $fileSize = (int) $file['size'];
      $mime = isset($file['type']) ? $file['type'] : null;
      $oldPath = self::getFilePath($doc);
    }

    $db = Database::getConnection();
    $stmt = $db->prepare('UPDATE archive_documents
      SET category = ?, title = ?, description = ?, original_name = ?, stored_name = ?,
          file_size = ?, mime_type = ?, updated_at = NOW()
      WHERE id = ?');
    $stmt->execute(array(
      $category,
      $title,
      $description,
      $originalName,
      $storedName,
      $fileSize,
      $mime,
      $id,
    ));

    if ($oldPath && is_file($oldPath) && $oldPath !== $newFilePath) {
      @unlink($oldPath);
    }

    $userId = current_user() ? current_user()['id'] : null;
    log_activity($doc['project_id'], $userId, 'archive_update', 'archive_document', $id, $title);
    return true;
  }

  public static function delete($id)
  {
    $doc = self::getById($id);
    if (!$doc) {
      return false;
    }

    $path = self::getFilePath($doc);
    if (is_file($path)) {
      unlink($path);
    }

    $db = Database::getConnection();
    $db->prepare('DELETE FROM archive_documents WHERE id = ?')->execute(array($id));
    log_activity($doc['project_id'], current_user() ? current_user()['id'] : null, 'archive_delete', 'archive_document', $id, $doc['title']);
    return true;
  }
}
