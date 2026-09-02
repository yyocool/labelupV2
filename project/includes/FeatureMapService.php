<?php

/**
 * 기능 구조 맵 — 슬라이드(페이지) 단위 DB CRUD
 */
class FeatureMapService
{
    public static function getSlideTypes()
    {
        return array(
            'cover'    => '표지',
            'overview' => '개요',
            'category' => '대분류',
            'group'    => '중분류',
            'feature'  => '기능 상세',
            'bridges'  => '연결점',
            'scope'    => '구축 범위',
            'end'      => '마무리',
            'custom'   => '사용자 정의',
        );
    }

    public static function getTones()
    {
        return array(
            'teal'  => '청록 (라벨편집·1차)',
            'amber' => '호박 (쇼핑몰·고도화)',
        );
    }

    public static function loadSeedMap()
    {
        $file = __DIR__ . '/data/feature_map.php';
        if (!file_exists($file)) {
            return array('meta' => array(), 'categories' => array(), 'bridges' => array(), 'scope' => array());
        }
        $map = require $file;
        return is_array($map) ? $map : array('meta' => array(), 'categories' => array(), 'bridges' => array(), 'scope' => array());
    }

    /** 문서 map_json 또는 시드에서 구축 범위 로드 */
    public static function getScopeData($doc = null)
    {
        if (is_array($doc) && !empty($doc['map_json'])) {
            $decoded = json_decode($doc['map_json'], true);
            if (is_array($decoded) && !empty($decoded['scope']['phases'])) {
                return $decoded['scope'];
            }
        }
        $seed = self::loadSeedMap();
        return isset($seed['scope']) && is_array($seed['scope']) ? $seed['scope'] : array();
    }

    /** phase 키 → phase 배열 (1 / phase-1 / enhance / phase-enhance) */
    public static function findScopePhase(array $scope, $phaseKey)
    {
        $key = strtolower(trim((string) $phaseKey));
        $aliases = array(
            '1' => 'phase-1',
            'p1' => 'phase-1',
            'phase1' => 'phase-1',
            'phase-1' => 'phase-1',
            '2' => 'phase-enhance',
            'p2' => 'phase-enhance',
            'enhance' => 'phase-enhance',
            'upgrade' => 'phase-enhance',
            'phase-enhance' => 'phase-enhance',
            'phase2' => 'phase-enhance',
        );
        $id = isset($aliases[$key]) ? $aliases[$key] : $key;
        foreach (isset($scope['phases']) ? $scope['phases'] : array() as $ph) {
            if (isset($ph['id']) && $ph['id'] === $id) {
                return $ph;
            }
        }
        return null;
    }

    /** 타 단계 링크용 요약 */
    public static function listScopePhaseSummaries(array $scope)
    {
        $out = array();
        foreach (isset($scope['phases']) ? $scope['phases'] : array() as $ph) {
            $areas = array();
            foreach (isset($ph['areas']) ? $ph['areas'] : array() as $a) {
                $areas[] = isset($a['name']) ? $a['name'] : '';
            }
            $out[] = array(
                'id' => isset($ph['id']) ? $ph['id'] : '',
                'name' => isset($ph['name']) ? $ph['name'] : '',
                'period' => isset($ph['period']) ? $ph['period'] : '',
                'goal' => isset($ph['goal']) ? $ph['goal'] : '',
                'tone' => (isset($ph['tone']) && $ph['tone'] === 'amber') ? 'amber' : 'teal',
                'areas' => $areas,
                'has_schedule' => !empty($ph['schedule']),
                'url' => 'feature-map-scope.php?phase=' . urlencode(isset($ph['id']) ? $ph['id'] : ''),
            );
        }
        return $out;
    }

    /**
     * 단일 단계 → 슬라이드 덱 페이로드
     */
    public static function buildPhaseDeckSlides(array $phase)
    {
        $mini = array(
            'title' => isset($phase['name']) ? $phase['name'] : '구축 범위',
            'summary' => trim(
                (isset($phase['period']) ? $phase['period'] : '') .
                (isset($phase['goal']) ? ' · ' . $phase['goal'] : '')
            ),
            'phases' => array($phase),
        );
        $rows = self::buildScopeSlideRows($mini, 10);
        $fake = array();
        foreach ($rows as $i => $r) {
            $fake[] = array(
                'id' => $i + 1,
                'is_visible' => 1,
                'slide_type' => $r['slide_type'],
                'tone' => $r['tone'],
                'kicker' => $r['kicker'],
                'title' => $r['title'],
                'subtitle' => $r['subtitle'],
                'lead_text' => $r['lead_text'],
                'body' => isset($r['body']) ? $r['body'] : array(),
                'body_json' => json_encode(isset($r['body']) ? $r['body'] : array(), JSON_UNESCAPED_UNICODE),
            );
        }
        return self::toDeckSlides($fake);
    }

    /* ── Doc ── */

    public static function getOrCreateDoc($projectId, $userId = null)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM feature_map_docs WHERE project_id = ? AND map_key = ? LIMIT 1');
        $stmt->execute(array((int) $projectId, 'default'));
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        $seed = self::loadSeedMap();
        $meta = isset($seed['meta']) ? $seed['meta'] : array();
        $ins = $db->prepare('INSERT INTO feature_map_docs
            (project_id, map_key, title, subtitle, version, basis, map_json, created_by, updated_by)
            VALUES (?,?,?,?,?,?,?,?,?)');
        $ins->execute(array(
            (int) $projectId,
            'default',
            isset($meta['title']) ? $meta['title'] : 'Label-UP 기능 구조 맵',
            isset($meta['subtitle']) ? $meta['subtitle'] : '',
            isset($meta['version']) ? $meta['version'] : '1.0',
            isset($meta['basis']) ? $meta['basis'] : '',
            json_encode($seed, JSON_UNESCAPED_UNICODE),
            $userId,
            $userId,
        ));
        return self::getDocById((int) $db->lastInsertId());
    }

    public static function getDocById($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM feature_map_docs WHERE id = ?');
        $stmt->execute(array((int) $id));
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    public static function updateDoc($docId, array $data, $userId = null)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE feature_map_docs SET
            title = ?, subtitle = ?, version = ?, basis = ?,
            updated_by = ?, updated_at = NOW()
            WHERE id = ?');
        $stmt->execute(array(
            trim(isset($data['title']) ? $data['title'] : ''),
            trim(isset($data['subtitle']) ? $data['subtitle'] : ''),
            trim(isset($data['version']) ? $data['version'] : '1.0'),
            trim(isset($data['basis']) ? $data['basis'] : ''),
            $userId,
            (int) $docId,
        ));
    }

    public static function syncDocMapJson($docId, array $map, $userId = null)
    {
        $db = Database::getConnection();
        $meta = isset($map['meta']) ? $map['meta'] : array();
        $stmt = $db->prepare('UPDATE feature_map_docs SET
            title = ?, subtitle = ?, version = ?, basis = ?, map_json = ?,
            updated_by = ?, updated_at = NOW()
            WHERE id = ?');
        $stmt->execute(array(
            isset($meta['title']) ? $meta['title'] : '',
            isset($meta['subtitle']) ? $meta['subtitle'] : '',
            isset($meta['version']) ? $meta['version'] : '1.0',
            isset($meta['basis']) ? $meta['basis'] : '',
            json_encode($map, JSON_UNESCAPED_UNICODE),
            $userId,
            (int) $docId,
        ));
    }

    /* ── Slides ── */

    public static function countSlides($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM feature_map_slides WHERE project_id = ?');
        $stmt->execute(array((int) $projectId));
        return (int) $stmt->fetchColumn();
    }

    public static function getSlides($projectId, $visibleOnly = false)
    {
        $db = Database::getConnection();
        $sql = 'SELECT s.*, u.name AS updater_name
                FROM feature_map_slides s
                LEFT JOIN users u ON u.id = s.updated_by
                WHERE s.project_id = ?';
        if ($visibleOnly) {
            $sql .= ' AND s.is_visible = 1';
        }
        $sql .= ' ORDER BY s.sort_order ASC, s.id ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute(array((int) $projectId));
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['body'] = self::decodeBody(isset($row['body_json']) ? $row['body_json'] : '');
        }
        unset($row);
        return $rows;
    }

    public static function getSlideById($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT s.*, u.name AS updater_name
                              FROM feature_map_slides s
                              LEFT JOIN users u ON u.id = s.updated_by
                              WHERE s.id = ?');
        $stmt->execute(array((int) $id));
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['body'] = self::decodeBody(isset($row['body_json']) ? $row['body_json'] : '');
        return $row;
    }

    public static function createSlide($projectId, $docId, array $data, $userId = null)
    {
        $db = Database::getConnection();
        $body = self::normalizeBodyInput($data);
        $sort = isset($data['sort_order']) ? (int) $data['sort_order'] : self::nextSortOrder($projectId);
        $stmt = $db->prepare('INSERT INTO feature_map_slides
            (project_id, doc_id, slide_key, sort_order, slide_type, tone, kicker, title, subtitle, lead_text, body_json, is_visible, created_by, updated_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array(
            (int) $projectId,
            (int) $docId,
            self::normalizeKey(isset($data['slide_key']) ? $data['slide_key'] : ''),
            $sort,
            self::normalizeType(isset($data['slide_type']) ? $data['slide_type'] : 'custom'),
            self::normalizeTone(isset($data['tone']) ? $data['tone'] : 'teal'),
            trim(isset($data['kicker']) ? $data['kicker'] : ''),
            trim(isset($data['title']) ? $data['title'] : ''),
            trim(isset($data['subtitle']) ? $data['subtitle'] : ''),
            trim(isset($data['lead_text']) ? $data['lead_text'] : ''),
            json_encode($body, JSON_UNESCAPED_UNICODE),
            !empty($data['is_visible']) ? 1 : 0,
            $userId,
            $userId,
        ));
        return (int) $db->lastInsertId();
    }

    public static function updateSlide($id, array $data, $userId = null)
    {
        $db = Database::getConnection();
        $body = self::normalizeBodyInput($data);
        $stmt = $db->prepare('UPDATE feature_map_slides SET
            slide_key = ?, sort_order = ?, slide_type = ?, tone = ?,
            kicker = ?, title = ?, subtitle = ?, lead_text = ?, body_json = ?,
            is_visible = ?, updated_by = ?, updated_at = NOW()
            WHERE id = ?');
        $stmt->execute(array(
            self::normalizeKey(isset($data['slide_key']) ? $data['slide_key'] : ''),
            (int) (isset($data['sort_order']) ? $data['sort_order'] : 0),
            self::normalizeType(isset($data['slide_type']) ? $data['slide_type'] : 'custom'),
            self::normalizeTone(isset($data['tone']) ? $data['tone'] : 'teal'),
            trim(isset($data['kicker']) ? $data['kicker'] : ''),
            trim(isset($data['title']) ? $data['title'] : ''),
            trim(isset($data['subtitle']) ? $data['subtitle'] : ''),
            trim(isset($data['lead_text']) ? $data['lead_text'] : ''),
            json_encode($body, JSON_UNESCAPED_UNICODE),
            !empty($data['is_visible']) ? 1 : 0,
            $userId,
            (int) $id,
        ));
    }

    public static function deleteSlide($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM feature_map_slides WHERE id = ?');
        $stmt->execute(array((int) $id));
    }

    public static function nextSortOrder($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM feature_map_slides WHERE project_id = ?');
        $stmt->execute(array((int) $projectId));
        return (int) $stmt->fetchColumn();
    }

    /* ── Seed / Rebuild ── */

    public static function ensureDefaults($projectId, $userId = null)
    {
        $doc = self::getOrCreateDoc($projectId, $userId);
        if (self::countSlides($projectId) === 0) {
            self::seedFromMapFile($projectId, (int) $doc['id'], $userId, false);
        }
        return $doc;
    }

    public static function seedFromMapFile($projectId, $docId, $userId = null, $replace = true)
    {
        $map = self::loadSeedMap();
        return self::seedFromMap($projectId, $docId, $map, $userId, $replace);
    }

    public static function seedFromMap($projectId, $docId, array $map, $userId = null, $replace = true)
    {
        $db = Database::getConnection();
        if ($replace) {
            $db->prepare('DELETE FROM feature_map_slides WHERE project_id = ?')->execute(array((int) $projectId));
        } elseif (self::countSlides($projectId) > 0) {
            return 0;
        }

        self::syncDocMapJson($docId, $map, $userId);
        $rows = self::buildSlideRowsFromMap($map);
        $count = 0;
        foreach ($rows as $row) {
            self::createSlide($projectId, $docId, $row, $userId);
            $count++;
        }
        return $count;
    }

    /**
     * hierarchical map → DB slide rows
     */
    public static function buildSlideRowsFromMap(array $map)
    {
        $meta = isset($map['meta']) ? $map['meta'] : array();
        $categories = isset($map['categories']) ? $map['categories'] : array();
        $bridges = isset($map['bridges']) ? $map['bridges'] : array();
        $rows = array();
        $order = 10;

        $rows[] = array(
            'slide_key' => 'cover',
            'sort_order' => $order,
            'slide_type' => 'cover',
            'tone' => 'teal',
            'kicker' => 'LABEL-UP',
            'title' => isset($meta['title']) ? $meta['title'] : '기능 구조 맵',
            'subtitle' => isset($meta['subtitle']) ? $meta['subtitle'] : '',
            'lead_text' => '',
            'is_visible' => 1,
            'body' => array(
                'meta' => (isset($meta['version']) ? 'v' . $meta['version'] : '') . ' · ' . (isset($meta['updated']) ? $meta['updated'] : ''),
                'basis' => isset($meta['basis']) ? $meta['basis'] : '',
            ),
        );
        $order += 10;

        $ovCards = array();
        foreach ($categories as $cat) {
            $names = array();
            foreach (isset($cat['groups']) ? $cat['groups'] : array() as $g) {
                $names[] = isset($g['name']) ? $g['name'] : '';
            }
            $ovCards[] = array(
                'name' => isset($cat['name']) ? $cat['name'] : '',
                'tagline' => isset($cat['tagline']) ? $cat['tagline'] : '',
                'color' => isset($cat['color']) ? $cat['color'] : 'teal',
                'items' => $names,
            );
        }
        $rows[] = array(
            'slide_key' => 'overview',
            'sort_order' => $order,
            'slide_type' => 'overview',
            'tone' => 'teal',
            'kicker' => 'OVERVIEW',
            'title' => '두 가지 축',
            'subtitle' => 'Label-UP 제품 구성: 라벨편집 · 쇼핑몰 두 대분류.',
            'lead_text' => '',
            'is_visible' => 1,
            'body' => array('cards' => $ovCards),
        );
        $order += 10;

        foreach ($categories as $cat) {
            $catId = isset($cat['id']) ? $cat['id'] : ('cat-' . $order);
            $tone = (isset($cat['color']) && $cat['color'] === 'amber') ? 'amber' : 'teal';
            $groupLines = array();
            foreach (isset($cat['groups']) ? $cat['groups'] : array() as $g) {
                $groupLines[] = array(
                    'name' => isset($g['name']) ? $g['name'] : '',
                    'desc' => isset($g['summary']) ? $g['summary'] : '',
                );
            }
            $rows[] = array(
                'slide_key' => $catId,
                'sort_order' => $order,
                'slide_type' => 'category',
                'tone' => $tone,
                'kicker' => (isset($cat['code']) ? $cat['code'] : '') . ' · 대분류',
                'title' => isset($cat['name']) ? $cat['name'] : '',
                'subtitle' => isset($cat['summary']) ? $cat['summary'] : '',
                'lead_text' => isset($cat['tagline']) ? $cat['tagline'] : '',
                'is_visible' => 1,
                'body' => array(
                    'code' => isset($cat['code']) ? $cat['code'] : '',
                    'cat_id' => $catId,
                    'color' => $tone,
                    'lines' => $groupLines,
                ),
            );
            $order += 10;

            foreach (isset($cat['groups']) ? $cat['groups'] : array() as $g) {
                $gid = isset($g['id']) ? $g['id'] : ('group-' . $order);
                $featLines = array();
                foreach (isset($g['features']) ? $g['features'] : array() as $f) {
                    $featLines[] = array(
                        'name' => isset($f['name']) ? $f['name'] : '',
                        'desc' => isset($f['desc']) ? $f['desc'] : '',
                    );
                }
                $rows[] = array(
                    'slide_key' => $gid,
                    'sort_order' => $order,
                    'slide_type' => 'group',
                    'tone' => $tone,
                    'kicker' => (isset($cat['name']) ? $cat['name'] : '') . ' · 중분류',
                    'title' => isset($g['name']) ? $g['name'] : '',
                    'subtitle' => trim(
                        (isset($g['screen']) ? '화면 ' . $g['screen'] : '') .
                        (isset($g['priority']) ? ' · ' . $g['priority'] : '')
                    ),
                    'lead_text' => isset($g['summary']) ? $g['summary'] : '',
                    'is_visible' => 1,
                    'body' => array(
                        'group_id' => $gid,
                        'cat_name' => isset($cat['name']) ? $cat['name'] : '',
                        'screen' => isset($g['screen']) ? $g['screen'] : '',
                        'priority' => isset($g['priority']) ? $g['priority'] : '',
                        'lines' => $featLines,
                    ),
                );
                $order += 10;

                foreach (isset($g['features']) ? $g['features'] : array() as $fi => $f) {
                    $fkey = $gid . '-f' . ($fi + 1);
                    $rows[] = array(
                        'slide_key' => $fkey,
                        'sort_order' => $order,
                        'slide_type' => 'feature',
                        'tone' => $tone,
                        'kicker' => (isset($cat['name']) ? $cat['name'] : '') . ' › ' . (isset($g['name']) ? $g['name'] : ''),
                        'title' => isset($f['name']) ? $f['name'] : '',
                        'subtitle' => '',
                        'lead_text' => isset($f['desc']) ? $f['desc'] : '',
                        'is_visible' => 1,
                        'body' => array(
                            'rules' => isset($f['rules']) && is_array($f['rules']) ? $f['rules'] : array(),
                        ),
                    );
                    $order += 10;
                }
            }
        }

        if (!empty($bridges)) {
            $rows[] = array(
                'slide_key' => 'bridges',
                'sort_order' => $order,
                'slide_type' => 'bridges',
                'tone' => 'teal',
                'kicker' => 'CONNECTIONS',
                'title' => '편집 ↔ 쇼핑 연결점',
                'subtitle' => '',
                'lead_text' => '',
                'is_visible' => 1,
                'body' => array('bridges' => $bridges),
            );
            $order += 10;
        }

        $scopeRows = self::buildScopeSlideRows(isset($map['scope']) ? $map['scope'] : array(), $order);
        foreach ($scopeRows as $sr) {
            $rows[] = $sr;
            $order = (int) $sr['sort_order'] + 10;
        }

        $rows[] = array(
            'slide_key' => 'end',
            'sort_order' => $order,
            'slide_type' => 'end',
            'tone' => 'teal',
            'kicker' => 'DONE',
            'title' => '끝',
            'subtitle' => '',
            'lead_text' => '슬라이드 모드 종료 · 상세 섹션에서 이어서 검토.',
            'is_visible' => 1,
            'body' => array(),
        );

        return $rows;
    }

    /**
     * 구축 범위 → 슬라이드 행
     */
    public static function buildScopeSlideRows(array $scope, $startOrder = 10)
    {
        if (empty($scope) || empty($scope['phases'])) {
            return array();
        }
        $rows = array();
        $order = (int) $startOrder;

        $phaseLines = array();
        foreach ($scope['phases'] as $ph) {
            $phaseLines[] = array(
                'name' => isset($ph['name']) ? $ph['name'] : '',
                'desc' => trim(
                    (isset($ph['period']) ? $ph['period'] : '') .
                    (isset($ph['goal']) ? ' · ' . $ph['goal'] : '')
                ),
            );
        }

        $rows[] = array(
            'slide_key' => 'scope-cover',
            'sort_order' => $order,
            'slide_type' => 'scope',
            'tone' => 'teal',
            'kicker' => 'SCOPE',
            'title' => isset($scope['title']) ? $scope['title'] : '구축 범위',
            'subtitle' => '',
            'lead_text' => isset($scope['summary']) ? $scope['summary'] : '',
            'is_visible' => 1,
            'body' => array('lines' => $phaseLines),
        );
        $order += 10;

        foreach ($scope['phases'] as $ph) {
            $phaseId = isset($ph['id']) ? $ph['id'] : ('phase-' . $order);
            $tone = (isset($ph['tone']) && $ph['tone'] === 'amber') ? 'amber' : 'teal';
            $areaLines = array();
            foreach (isset($ph['areas']) ? $ph['areas'] : array() as $area) {
                $areaLines[] = array(
                    'name' => isset($area['name']) ? $area['name'] : '',
                    'desc' => isset($area['subtitle']) ? $area['subtitle'] : '',
                );
            }

            $rows[] = array(
                'slide_key' => $phaseId,
                'sort_order' => $order,
                'slide_type' => 'category',
                'tone' => $tone,
                'kicker' => '구축 범위 · ' . (isset($ph['name']) ? $ph['name'] : ''),
                'title' => isset($ph['name']) ? $ph['name'] : '',
                'subtitle' => isset($ph['goal']) ? $ph['goal'] : '',
                'lead_text' => isset($ph['period']) ? $ph['period'] : '',
                'is_visible' => 1,
                'body' => array(
                    'code' => ($tone === 'amber') ? 'EN' : 'P1',
                    'cat_id' => $phaseId,
                    'color' => $tone,
                    'lines' => $areaLines,
                    'scope_phase' => 1,
                ),
            );
            $order += 10;

            // 고도화 일정 슬라이드
            if (!empty($ph['schedule'])) {
                $schedLines = array();
                $rules = array();
                foreach ($ph['schedule'] as $w) {
                    $schedLines[] = array(
                        'name' => (isset($w['wave']) ? $w['wave'] : '') . ' · ' . (isset($w['period']) ? $w['period'] : ''),
                        'desc' => isset($w['focus']) ? $w['focus'] : '',
                    );
                    $rule = (isset($w['wave']) ? $w['wave'] : '') . ' (' . (isset($w['period']) ? $w['period'] : '') . ') · ' . (isset($w['focus']) ? $w['focus'] : '');
                    $rules[] = $rule;
                    foreach (isset($w['deliverables']) ? $w['deliverables'] : array() as $d) {
                        $rules[] = '  · ' . $d;
                    }
                }
                $rows[] = array(
                    'slide_key' => $phaseId . '-schedule',
                    'sort_order' => $order,
                    'slide_type' => 'feature',
                    'tone' => $tone,
                    'kicker' => (isset($ph['name']) ? $ph['name'] : '') . ' · 일정',
                    'title' => '고도화 일정',
                    'subtitle' => isset($ph['period']) ? $ph['period'] : '',
                    'lead_text' => 'Wave별 기간 · 초점 · 산출물',
                    'is_visible' => 1,
                    'body' => array(
                        'schedule' => $ph['schedule'],
                        'lines' => $schedLines,
                        'rules' => $rules,
                    ),
                );
                $order += 10;
            }

            foreach (isset($ph['areas']) ? $ph['areas'] : array() as $area) {
                $aid = isset($area['id']) ? $area['id'] : ('area-' . $order);
                $blockLines = array();
                foreach (isset($area['blocks']) ? $area['blocks'] : array() as $b) {
                    $blockLines[] = array(
                        'name' => isset($b['name']) ? $b['name'] : '',
                        'desc' => isset($b['desc']) ? $b['desc'] : '',
                    );
                }
                $rows[] = array(
                    'slide_key' => $aid,
                    'sort_order' => $order,
                    'slide_type' => 'group',
                    'tone' => $tone,
                    'kicker' => (isset($ph['name']) ? $ph['name'] : '') . ' · 중분류',
                    'title' => isset($area['name']) ? $area['name'] : '',
                    'subtitle' => isset($area['subtitle']) ? $area['subtitle'] : '',
                    'lead_text' => isset($area['subtitle']) ? $area['subtitle'] : '',
                    'is_visible' => 1,
                    'body' => array(
                        'group_id' => $aid,
                        'cat_name' => isset($ph['name']) ? $ph['name'] : '',
                        'lines' => $blockLines,
                        'scope_area' => 1,
                    ),
                );
                $order += 10;

                foreach (isset($area['blocks']) ? $area['blocks'] : array() as $bi => $b) {
                    $rows[] = array(
                        'slide_key' => $aid . '-b' . ($bi + 1),
                        'sort_order' => $order,
                        'slide_type' => 'feature',
                        'tone' => $tone,
                        'kicker' => (isset($ph['name']) ? $ph['name'] : '') . ' › ' . (isset($area['name']) ? $area['name'] : ''),
                        'title' => isset($b['name']) ? $b['name'] : '',
                        'subtitle' => '',
                        'lead_text' => isset($b['desc']) ? $b['desc'] : '',
                        'is_visible' => 1,
                        'body' => array(
                            'rules' => isset($b['items']) && is_array($b['items']) ? $b['items'] : array(),
                        ),
                    );
                    $order += 10;
                }
            }
        }

        return $rows;
    }

    /**
     * DB slides → 화면 렌더용 deck payload
     */
    public static function toDeckSlides(array $dbSlides)
    {
        $out = array();
        $catTitle = '';
        $groupTitle = '';
        foreach ($dbSlides as $s) {
            if (isset($s['is_visible']) && !(int) $s['is_visible']) {
                continue;
            }
            $body = isset($s['body']) ? $s['body'] : self::decodeBody(isset($s['body_json']) ? $s['body_json'] : '');
            $type = isset($s['slide_type']) ? $s['slide_type'] : 'custom';
            $tone = isset($s['tone']) ? $s['tone'] : 'teal';
            $title = isset($s['title']) ? $s['title'] : '';

            if ($type === 'category') {
                $catTitle = $title;
                $groupTitle = '';
            } elseif ($type === 'group') {
                $groupTitle = $title;
                if ($catTitle === '' && !empty($body['cat_name'])) {
                    $catTitle = $body['cat_name'];
                }
            } elseif ($type === 'cover' || $type === 'overview' || $type === 'bridges' || $type === 'end') {
                // 독립 섹션 — 경로 컨텍스트 유지(트리에서 별도 루트)
            }

            $path = array();
            if ($type === 'category') {
                $path = array($title);
            } elseif ($type === 'group') {
                if ($catTitle !== '') {
                    $path[] = $catTitle;
                }
                $path[] = $title;
            } elseif ($type === 'feature') {
                if ($catTitle !== '') {
                    $path[] = $catTitle;
                }
                if ($groupTitle !== '') {
                    $path[] = $groupTitle;
                }
                $path[] = $title;
            } else {
                $path = array($title);
            }

            $item = array(
                'id' => isset($s['id']) ? (int) $s['id'] : 0,
                'type' => $type,
                'tone' => $tone,
                'kicker' => isset($s['kicker']) ? $s['kicker'] : '',
                'title' => $title,
                'subtitle' => isset($s['subtitle']) ? $s['subtitle'] : '',
                'lead' => isset($s['lead_text']) ? $s['lead_text'] : '',
                'body' => $body,
                'path' => $path,
                'cat' => $catTitle,
                'group' => $groupTitle,
            );
            // 하위 JS 호환 필드
            if ($type === 'cover') {
                $item['meta'] = isset($body['meta']) ? $body['meta'] : '';
                $item['basis'] = isset($body['basis']) ? $body['basis'] : '';
            } elseif ($type === 'overview') {
                $item['cards'] = isset($body['cards']) ? $body['cards'] : array();
            } elseif ($type === 'category') {
                $item['lines'] = isset($body['lines']) ? $body['lines'] : array();
            } elseif ($type === 'group') {
                $item['lines'] = isset($body['lines']) ? $body['lines'] : array();
            } elseif ($type === 'feature') {
                $item['rules'] = isset($body['rules']) ? $body['rules'] : array();
                $item['schedule'] = isset($body['schedule']) ? $body['schedule'] : array();
            } elseif ($type === 'bridges') {
                $item['bridges'] = isset($body['bridges']) ? $body['bridges'] : array();
            } elseif ($type === 'scope') {
                $item['lines'] = isset($body['lines']) ? $body['lines'] : array();
                $item['rules'] = isset($body['rules']) ? $body['rules'] : array();
                $item['schedule'] = isset($body['schedule']) ? $body['schedule'] : array();
            } elseif ($type === 'custom') {
                $item['rules'] = isset($body['rules']) ? $body['rules'] : array();
                $item['lines'] = isset($body['lines']) ? $body['lines'] : array();
            }
            $out[] = $item;
        }
        return $out;
    }

    /**
     * DB slides → 문서형 browse map (대/중분류 카드)
     */
    public static function buildBrowseMap(array $doc, array $dbSlides)
    {
        $meta = array(
            'title' => isset($doc['title']) ? $doc['title'] : '기능 구조 맵',
            'subtitle' => isset($doc['subtitle']) ? $doc['subtitle'] : '',
            'version' => isset($doc['version']) ? $doc['version'] : '1.0',
            'basis' => isset($doc['basis']) ? $doc['basis'] : '',
            'updated' => !empty($doc['updated_at']) ? substr($doc['updated_at'], 0, 10) : date('Y-m-d'),
        );

        // map_json이 있으면 우선 (시드·동기화 상태 유지)
        if (!empty($doc['map_json'])) {
            $decoded = json_decode($doc['map_json'], true);
            if (is_array($decoded) && !empty($decoded['categories'])) {
                if (!isset($decoded['meta']) || !is_array($decoded['meta'])) {
                    $decoded['meta'] = $meta;
                } else {
                    $decoded['meta'] = array_merge($decoded['meta'], $meta);
                }
                return $decoded;
            }
        }

        $categories = array();
        $bridges = array();
        $curCat = null;
        $curGroup = null;
        $catIdx = -1;
        $groupIdx = -1;
        $inScope = false;

        foreach ($dbSlides as $s) {
            if (isset($s['is_visible']) && !(int) $s['is_visible']) {
                continue;
            }
            $type = $s['slide_type'];
            $body = isset($s['body']) ? $s['body'] : array();

            if ($type === 'category') {
                // 구축 범위 단계는 product 대분류와 분리
                if (!empty($body['scope_phase'])) {
                    $inScope = true;
                    continue;
                }
                $inScope = false;
                $catIdx++;
                $groupIdx = -1;
                $categories[$catIdx] = array(
                    'id' => isset($body['cat_id']) ? $body['cat_id'] : ('cat-' . $catIdx),
                    'code' => isset($body['code']) ? $body['code'] : '',
                    'name' => $s['title'],
                    'tagline' => $s['lead_text'],
                    'summary' => $s['subtitle'],
                    'color' => ($s['tone'] === 'amber') ? 'amber' : 'teal',
                    'groups' => array(),
                );
                $curCat = &$categories[$catIdx];
            } elseif ($inScope) {
                continue;
            } elseif ($type === 'group' && $catIdx >= 0) {
                $groupIdx++;
                $categories[$catIdx]['groups'][$groupIdx] = array(
                    'id' => isset($body['group_id']) ? $body['group_id'] : ('g-' . $groupIdx),
                    'name' => $s['title'],
                    'summary' => $s['lead_text'],
                    'priority' => isset($body['priority']) ? $body['priority'] : '',
                    'screen' => isset($body['screen']) ? $body['screen'] : '',
                    'features' => array(),
                );
            } elseif ($type === 'feature' && $catIdx >= 0 && $groupIdx >= 0) {
                $categories[$catIdx]['groups'][$groupIdx]['features'][] = array(
                    'name' => $s['title'],
                    'desc' => $s['lead_text'],
                    'rules' => isset($body['rules']) ? $body['rules'] : array(),
                );
            } elseif ($type === 'bridges') {
                $bridges = isset($body['bridges']) ? $body['bridges'] : array();
            }
        }
        unset($curCat);

        return array(
            'meta' => $meta,
            'categories' => $categories,
            'bridges' => $bridges,
        );
    }

    /**
     * feature/group 슬라이드 수정 후 map_json 재구성 (browse 동기화)
     */
    public static function rebuildMapJsonFromSlides($projectId, $docId, $userId = null)
    {
        $doc = self::getDocById($docId);
        if (!$doc) {
            return;
        }
        $prevScope = null;
        if (!empty($doc['map_json'])) {
            $prev = json_decode($doc['map_json'], true);
            if (is_array($prev) && isset($prev['scope'])) {
                $prevScope = $prev['scope'];
            }
        }
        $slides = self::getSlides($projectId, false);
        // map_json 무시하고 슬라이드만으로 재구성 (scope는 시드/기존 값 유지)
        $doc['map_json'] = null;
        $map = self::buildBrowseMap($doc, $slides);
        if ($prevScope !== null) {
            $map['scope'] = $prevScope;
        }
        self::syncDocMapJson($docId, $map, $userId);
    }

    /* ── helpers ── */

    public static function decodeBody($json)
    {
        if ($json === null || $json === '') {
            return array();
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : array();
    }

    public static function normalizeBodyInput(array $data)
    {
        if (isset($data['body']) && is_array($data['body'])) {
            return $data['body'];
        }

        // 고급 JSON (컨트롤러에서 prefer_body_json=1일 때만)
        if (!empty($data['prefer_body_json']) && isset($data['body_json']) && trim($data['body_json']) !== '') {
            $decoded = json_decode($data['body_json'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $type = self::normalizeType(isset($data['slide_type']) ? $data['slide_type'] : 'custom');
        $body = array();

        if ($type === 'cover') {
            $body['meta'] = trim(isset($data['body_meta']) ? $data['body_meta'] : '');
            $body['basis'] = trim(isset($data['body_basis']) ? $data['body_basis'] : '');
        } elseif ($type === 'overview' || $type === 'category' || $type === 'group' || $type === 'custom') {
            $linesRaw = isset($data['body_lines']) ? $data['body_lines'] : '';
            $body['lines'] = self::parseLinesText($linesRaw);
            if ($type === 'overview') {
                $cards = array();
                foreach ($body['lines'] as $line) {
                    $cards[] = array(
                        'name' => $line['name'],
                        'tagline' => $line['desc'],
                        'color' => 'teal',
                        'items' => array(),
                    );
                }
                $body = array('cards' => $cards);
            }
            if ($type === 'category') {
                $body['code'] = trim(isset($data['body_code']) ? $data['body_code'] : '');
                $body['cat_id'] = self::normalizeKey(isset($data['slide_key']) ? $data['slide_key'] : '');
                $body['color'] = self::normalizeTone(isset($data['tone']) ? $data['tone'] : 'teal');
            }
            if ($type === 'group') {
                $body['group_id'] = self::normalizeKey(isset($data['slide_key']) ? $data['slide_key'] : '');
                $body['screen'] = trim(isset($data['body_screen']) ? $data['body_screen'] : '');
                $body['priority'] = trim(isset($data['body_priority']) ? $data['body_priority'] : '');
                $body['cat_name'] = trim(isset($data['body_cat_name']) ? $data['body_cat_name'] : '');
            }
        }

        if ($type === 'feature' || $type === 'custom' || $type === 'end' || $type === 'scope') {
            $rulesText = isset($data['body_rules']) ? $data['body_rules'] : '';
            $rules = self::parseRulesText($rulesText);
            if ($type === 'feature' || $type === 'scope' || ($type === 'custom' && $rules)) {
                $body['rules'] = $rules;
            }
            if ($type === 'scope') {
                $linesRaw = isset($data['body_lines']) ? $data['body_lines'] : '';
                $body['lines'] = self::parseLinesText($linesRaw);
            }
        }

        if ($type === 'bridges') {
            $body['bridges'] = self::parseBridgesText(isset($data['body_bridges']) ? $data['body_bridges'] : '');
        }

        return $body;
    }

    /** name | desc 한 줄씩 */
    public static function parseLinesText($text)
    {
        $lines = array();
        foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line, 2));
            $lines[] = array(
                'name' => $parts[0],
                'desc' => isset($parts[1]) ? $parts[1] : '',
            );
        }
        return $lines;
    }

    public static function formatLinesText(array $lines)
    {
        $out = array();
        foreach ($lines as $line) {
            $name = isset($line['name']) ? $line['name'] : '';
            $desc = isset($line['desc']) ? $line['desc'] : '';
            $out[] = $desc !== '' ? ($name . ' | ' . $desc) : $name;
        }
        return implode("\n", $out);
    }

    public static function parseRulesText($text)
    {
        $rules = array();
        foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $rules[] = $line;
            }
        }
        return $rules;
    }

    /** from => to | note */
    public static function parseBridgesText($text)
    {
        $bridges = array();
        foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '=>') === false) {
                continue;
            }
            list($left, $right) = array_map('trim', explode('=>', $line, 2));
            $note = '';
            if (strpos($right, '|') !== false) {
                list($right, $note) = array_map('trim', explode('|', $right, 2));
            }
            $bridges[] = array('from' => $left, 'to' => $right, 'note' => $note);
        }
        return $bridges;
    }

    public static function formatBridgesText(array $bridges)
    {
        $out = array();
        foreach ($bridges as $b) {
            $line = (isset($b['from']) ? $b['from'] : '') . ' => ' . (isset($b['to']) ? $b['to'] : '');
            if (!empty($b['note'])) {
                $line .= ' | ' . $b['note'];
            }
            $out[] = $line;
        }
        return implode("\n", $out);
    }

    public static function formatRulesText(array $rules)
    {
        return implode("\n", $rules);
    }

    public static function normalizeKey($key)
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_\-]+/', '-', $key);
        $key = trim($key, '-_');
        return $key !== '' ? $key : ('slide-' . substr(md5(uniqid('', true)), 0, 8));
    }

    public static function normalizeType($type)
    {
        $types = self::getSlideTypes();
        return isset($types[$type]) ? $type : 'custom';
    }

    public static function normalizeTone($tone)
    {
        return ($tone === 'amber') ? 'amber' : 'teal';
    }

    public static function slideFormExtras(array $slide)
    {
        $body = isset($slide['body']) ? $slide['body'] : array();
        $type = isset($slide['slide_type']) ? $slide['slide_type'] : 'custom';
        $extras = array(
            'body_meta' => '',
            'body_basis' => '',
            'body_lines' => '',
            'body_rules' => '',
            'body_bridges' => '',
            'body_code' => '',
            'body_screen' => '',
            'body_priority' => '',
            'body_cat_name' => '',
            'body_json' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );
        if ($type === 'cover') {
            $extras['body_meta'] = isset($body['meta']) ? $body['meta'] : '';
            $extras['body_basis'] = isset($body['basis']) ? $body['basis'] : '';
        } elseif ($type === 'overview') {
            $lines = array();
            foreach (isset($body['cards']) ? $body['cards'] : array() as $c) {
                $lines[] = array(
                    'name' => isset($c['name']) ? $c['name'] : '',
                    'desc' => isset($c['tagline']) ? $c['tagline'] : '',
                );
            }
            $extras['body_lines'] = self::formatLinesText($lines);
        } elseif ($type === 'scope') {
            $extras['body_lines'] = self::formatLinesText(isset($body['lines']) ? $body['lines'] : array());
            $extras['body_rules'] = self::formatRulesText(isset($body['rules']) ? $body['rules'] : array());
        } elseif ($type === 'category' || $type === 'group' || $type === 'custom') {
            $extras['body_lines'] = self::formatLinesText(isset($body['lines']) ? $body['lines'] : array());
            $extras['body_code'] = isset($body['code']) ? $body['code'] : '';
            $extras['body_screen'] = isset($body['screen']) ? $body['screen'] : '';
            $extras['body_priority'] = isset($body['priority']) ? $body['priority'] : '';
            $extras['body_cat_name'] = isset($body['cat_name']) ? $body['cat_name'] : '';
            $extras['body_rules'] = self::formatRulesText(isset($body['rules']) ? $body['rules'] : array());
        } elseif ($type === 'feature') {
            $extras['body_rules'] = self::formatRulesText(isset($body['rules']) ? $body['rules'] : array());
        } elseif ($type === 'bridges') {
            $extras['body_bridges'] = self::formatBridgesText(isset($body['bridges']) ? $body['bridges'] : array());
        }
        return $extras;
    }
}
