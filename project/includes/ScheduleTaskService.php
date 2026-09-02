<?php

/**
 * 일정(간트) 작업 관리 서비스.
 * schedule_tasks 테이블 CRUD 및 대시보드 간트 차트 렌더링용 모델 계산을 담당한다.
 */
class ScheduleTaskService
{
    /** 단계별 기본 색상 (신규 작업 추가 시 자동 적용) */
    public static function getPhaseColors()
    {
        return array(
            '기획'           => '#6366f1',
            'UI/UX 디자인'   => '#ec4899',
            '백엔드 개발'    => '#10b981',
            '프론트엔드 개발' => '#f59e0b',
            '마무리'         => '#8b5cf6',
        );
    }

    public static function colorForPhase($phase)
    {
        $colors = self::getPhaseColors();
        return isset($colors[$phase]) ? $colors[$phase] : '#64748b';
    }

    /**
     * PDF(0A_타임라인) 기준 AI 프린팅 서비스 프로젝트 일정.
     * 각 항목: phase, title, detail, start_date, end_date
     */
    public static function getSeedTimeline()
    {
        // 시작일 6/1 → 6/23 기준으로 전체 일정을 22일 뒤로 조정
        return array(
            array('기획', '메뉴 구조', '사이트맵', '2026-06-23', '2026-06-27'),
            array('기획', '기능 정의', '기능 명세', '2026-06-26', '2026-07-01'),
            array('기획', '기획&화면 정의(스토리보드)', '화면 기획서 작성', '2026-07-01', '2026-07-06'),

            array('UI/UX 디자인', '디자인 컨셉', '레퍼런스 조사', '2026-07-04', '2026-07-08'),
            array('UI/UX 디자인', '메인 디자인', '메인 페이지 시안', '2026-07-08', '2026-07-15'),
            array('UI/UX 디자인', '서브 디자인', '상세 페이지 디자인', '2026-07-15', '2026-07-22'),
            array('UI/UX 디자인', '반응형 설계/퍼블리싱', '모바일/태블릿 대응', '2026-07-22', '2026-07-27'),
            array('UI/UX 디자인', '디자인 검수', '고객 피드백 반영', '2026-07-27', '2026-07-30'),

            array('백엔드 개발', '서버 세팅', '개발환경 구축', '2026-07-12', '2026-07-15'),
            array('백엔드 개발', 'DB 설계', 'ERD 설계', '2026-07-15', '2026-07-20'),
            array('백엔드 개발', 'API 개발', 'REST API 개발', '2026-07-20', '2026-08-09'),
            array('백엔드 개발', '관리자 페이지', 'Admin 개발', '2026-08-09', '2026-08-19'),
            array('백엔드 개발', '알림 시스템', '이메일/SMS', '2026-08-19', '2026-08-23'),
            array('백엔드 개발', '보안 작업', '권한/암호화', '2026-08-23', '2026-08-27'),

            array('프론트엔드 개발', '프로젝트 세팅', '셋팅', '2026-07-28', '2026-07-31'),
            array('프론트엔드 개발', '공통 컴포넌트', '공통 UI 개발', '2026-07-31', '2026-08-07'),
            array('프론트엔드 개발', 'API 연동', '백엔드 API 연결', '2026-08-07', '2026-08-21'),
            array('프론트엔드 개발', '예외 처리', '에러 처리', '2026-08-21', '2026-08-28'),
            array('프론트엔드 개발', '성능 최적화', 'Lazy Loading 등', '2026-08-28', '2026-09-06'),

            array('마무리', '테스트(QA)', '기능 & 통합 테스트', '2026-09-07', '2026-09-13'),
            array('마무리', '검수 보완', '이슈 대응', '2026-09-13', '2026-09-17'),
            array('마무리', '최종 검수', '고객검수', '2026-09-17', '2026-09-20'),
            array('마무리', '운영 서버 배포', '배포', '2026-09-20', '2026-09-21'),
            array('마무리', '유지보수', '별도 논의', '2026-09-21', '2026-09-22'),
        );
    }

    /** 프로젝트에 일정이 없을 때만 PDF 타임라인을 시드한다. */
    public static function seedDefaultTimeline($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM schedule_tasks WHERE project_id = ?');
        $stmt->execute(array($projectId));
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $insert = $db->prepare('INSERT INTO schedule_tasks
            (project_id, phase, title, detail, start_date, end_date, progress, color, sort_order)
            VALUES (?,?,?,?,?,?,?,?,?)');

        $order = 0;
        foreach (self::getSeedTimeline() as $row) {
            list($phase, $title, $detail, $start, $end) = $row;
            $insert->execute(array(
                $projectId, $phase, $title, $detail, $start, $end, 0,
                self::colorForPhase($phase), $order,
            ));
            $order += 10;
        }
    }

    public static function getByProject($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM schedule_tasks WHERE project_id = ? ORDER BY sort_order, start_date, id');
        $stmt->execute(array($projectId));
        return $stmt->fetchAll();
    }

    public static function getById($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM schedule_tasks WHERE id = ?');
        $stmt->execute(array($id));
        return $stmt->fetch();
    }

    /**
     * 신규 작업 생성. 입력값을 검증/정규화한다.
     * @return int 생성된 작업 id
     */
    public static function create($projectId, array $data, $userId = null)
    {
        $db = Database::getConnection();

        $phase = self::sanitizeText(isset($data['phase']) ? $data['phase'] : '', 100);
        $title = self::sanitizeText(isset($data['title']) ? $data['title'] : '', 200);
        $detail = self::sanitizeText(isset($data['detail']) ? $data['detail'] : '', 300);
        $start = self::normalizeDate(isset($data['start_date']) ? $data['start_date'] : '');
        $end = self::normalizeDate(isset($data['end_date']) ? $data['end_date'] : '');
        $progress = self::clampProgress(isset($data['progress']) ? $data['progress'] : 0);

        if ($phase === '' || $title === '' || $start === null || $end === null) {
            throw new InvalidArgumentException('필수 입력값이 누락되었습니다.');
        }
        if ($end < $start) {
            $end = $start;
        }

        $color = isset($data['color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $data['color'])
            ? $data['color']
            : self::colorForPhase($phase);

        $orderStmt = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM schedule_tasks WHERE project_id = ?');
        $orderStmt->execute(array($projectId));
        $sortOrder = (int) $orderStmt->fetchColumn();

        $stmt = $db->prepare('INSERT INTO schedule_tasks
            (project_id, phase, title, detail, start_date, end_date, progress, color, sort_order, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array($projectId, $phase, $title, $detail, $start, $end, $progress, $color, $sortOrder, $userId));

        return (int) $db->lastInsertId();
    }

    /** 완료 % 만 갱신 (인라인 편집). @return int 저장된 진행률 */
    public static function updateProgress($id, $projectId, $progress, $userId = null)
    {
        $db = Database::getConnection();
        $value = self::clampProgress($progress);
        $stmt = $db->prepare('UPDATE schedule_tasks SET progress = ?, updated_by = ?, updated_at = NOW() WHERE id = ? AND project_id = ?');
        $stmt->execute(array($value, $userId, $id, $projectId));
        return $value;
    }

    /**
     * 시작일/종료일만 갱신 (인라인 편집).
     * 종료일이 시작일보다 빠르면 시작일로 보정한다.
     * @return array{start:string,end:string} 정규화되어 저장된 날짜
     */
    public static function updateDates($id, $projectId, $start, $end, $userId = null)
    {
        $startNorm = self::normalizeDate($start);
        $endNorm = self::normalizeDate($end);
        if ($startNorm === null || $endNorm === null) {
            throw new InvalidArgumentException('시작일과 종료일을 올바르게 입력하세요.');
        }
        if ($endNorm < $startNorm) {
            $endNorm = $startNorm;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE schedule_tasks SET start_date = ?, end_date = ?, updated_by = ?, updated_at = NOW() WHERE id = ? AND project_id = ?');
        $stmt->execute(array($startNorm, $endNorm, $userId, $id, $projectId));

        return array('start' => $startNorm, 'end' => $endNorm);
    }

    public static function delete($id, $projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM schedule_tasks WHERE id = ? AND project_id = ?');
        $stmt->execute(array($id, $projectId));
        return $stmt->rowCount() > 0;
    }

    /**
     * 드래그앤드롭 재정렬. 클라이언트가 보낸 새 순서(id + 소속 단계)를 저장한다.
     * 단계를 넘나드는 자유 이동을 지원하며, 단계가 바뀐 항목은 색상을 새 단계
     * 기본색으로 갱신한다(사용자 지정 색은 단계가 그대로면 유지).
     *
     * @param array $items [ ['id'=>int, 'phase'=>string], ... ] (표시 순서)
     * @return int 반영된 항목 수
     */
    public static function reorder($projectId, array $items, $userId = null)
    {
        $db = Database::getConnection();
        self::ensureTable($db);

        $existing = array();
        foreach (self::getByProject($projectId) as $t) {
            $existing[(int) $t['id']] = $t;
        }
        if (empty($existing)) {
            return 0;
        }

        $cols = array();
        foreach ($db->query('SHOW COLUMNS FROM `schedule_tasks`')->fetchAll() as $col) {
            $cols[$col['Field']] = true;
        }
        if (empty($cols['sort_order'])) {
            throw new RuntimeException('schedule_tasks.sort_order 컬럼이 없습니다. 마이그레이션을 실행하세요.');
        }

        $hasPhase = !empty($cols['phase']);
        $hasColor = !empty($cols['color']);
        $hasUpdatedBy = !empty($cols['updated_by']);
        $hasUpdatedAt = !empty($cols['updated_at']);

        $setParts = array('sort_order = ?');
        if ($hasPhase) {
            $setParts[] = 'phase = ?';
        }
        if ($hasColor) {
            $setParts[] = 'color = ?';
        }
        if ($hasUpdatedBy) {
            $setParts[] = 'updated_by = ?';
        }
        if ($hasUpdatedAt) {
            $setParts[] = 'updated_at = NOW()';
        }
        $upd = $db->prepare(
            'UPDATE schedule_tasks SET ' . implode(', ', $setParts) . ' WHERE id = ? AND project_id = ?'
        );

        $order = 10;
        $applied = 0;
        $seen = array();
        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['id'])) {
                continue;
            }
            $id = (int) $item['id'];
            if (!isset($existing[$id]) || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $cur = $existing[$id];

            $phase = self::sanitizeText(isset($item['phase']) ? $item['phase'] : '', 100);
            if ($phase === '') {
                $phase = isset($cur['phase']) ? $cur['phase'] : '';
            }

            $curPhase = isset($cur['phase']) ? $cur['phase'] : '';
            if ($phase !== $curPhase) {
                $color = self::colorForPhase($phase);
            } else {
                $color = !empty($cur['color']) ? $cur['color'] : self::colorForPhase($phase);
            }

            $params = array($order);
            if ($hasPhase) {
                $params[] = $phase;
            }
            if ($hasColor) {
                $params[] = $color;
            }
            if ($hasUpdatedBy) {
                $params[] = $userId;
            }
            $params[] = $id;
            $params[] = $projectId;
            $upd->execute($params);
            $order += 10;
            $applied++;
        }

        return $applied;
    }

    /** schedule_tasks 테이블/필수 컬럼이 없으면 생성·추가한다. */
    public static function ensureTable($db = null)
    {
        if ($db === null) {
            $db = Database::getConnection();
        }
        if (function_exists('migrate_schedule_tasks_table')) {
            migrate_schedule_tasks_table($db);
            return;
        }

        $tbl = $db->query("SHOW TABLES LIKE 'schedule_tasks'")->fetch();
        if (!$tbl) {
            $db->exec("CREATE TABLE IF NOT EXISTS `schedule_tasks` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `project_id` INT UNSIGNED NOT NULL,
                `phase` VARCHAR(100) NOT NULL DEFAULT '',
                `title` VARCHAR(200) NOT NULL,
                `detail` VARCHAR(300) DEFAULT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `color` VARCHAR(7) DEFAULT NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `created_by` INT UNSIGNED DEFAULT NULL,
                `updated_by` INT UNSIGNED DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL,
                INDEX `idx_schedule_tasks_project` (`project_id`, `sort_order`, `id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }

    /**
     * 대시보드 간트 차트 렌더링용 모델을 계산한다.
     * 반환: tasks(계산된 offset/width%), months(헤더), phases(그룹 요약), 요약 통계.
     */
    public static function buildGanttModel($projectId, $project = null)
    {
        $tasks = self::getByProject($projectId);
        $model = array(
            'tasks'        => array(),
            'months'       => array(),
            'phases'       => array(),
            'range_start'  => null,
            'range_end'    => null,
            'total_days'   => 0,
            'today_offset' => null,
            'avg_progress' => 0,
            'task_count'   => count($tasks),
        );

        if (empty($tasks)) {
            return $model;
        }

        $minTs = null;
        $maxTs = null;
        foreach ($tasks as $t) {
            $s = strtotime($t['start_date']);
            $e = strtotime($t['end_date']);
            if ($minTs === null || $s < $minTs) {
                $minTs = $s;
            }
            if ($maxTs === null || $e > $maxTs) {
                $maxTs = $e;
            }
        }

        // 프로젝트 기간이 더 넓으면 포함
        if ($project) {
            if (!empty($project['start_date'])) {
                $ps = strtotime($project['start_date']);
                if ($ps < $minTs) {
                    $minTs = $ps;
                }
            }
            if (!empty($project['end_date'])) {
                $pe = strtotime($project['end_date']);
                if ($pe > $maxTs) {
                    $maxTs = $pe;
                }
            }
        }

        // 월 경계로 범위 확장 (가독성)
        $rangeStart = strtotime(date('Y-m-01', $minTs));
        $rangeEnd = strtotime(date('Y-m-t', $maxTs));

        $daySec = 86400;
        $totalDays = (int) round(($rangeEnd - $rangeStart) / $daySec) + 1;
        if ($totalDays < 1) {
            $totalDays = 1;
        }

        // 월 헤더 계산
        $months = array();
        $cursor = $rangeStart;
        while ($cursor <= $rangeEnd) {
            $monthStart = strtotime(date('Y-m-01', $cursor));
            $monthEnd = strtotime(date('Y-m-t', $cursor));
            $daysInMonth = (int) date('t', $cursor);
            $months[] = array(
                'label'  => (int) date('n', $cursor) . '월',
                'year'   => (int) date('Y', $cursor),
                'days'   => $daysInMonth,
                'width'  => ($daysInMonth / $totalDays) * 100,
            );
            $cursor = strtotime('+1 month', $monthStart);
        }

        // 작업 막대 위치 계산 + 단계 그룹 요약
        $phaseColors = self::getPhaseColors();
        $phaseAgg = array();
        $progressSum = 0;
        $computed = array();
        foreach ($tasks as $t) {
            $s = strtotime($t['start_date']);
            $e = strtotime($t['end_date']);
            $offsetDays = (int) round(($s - $rangeStart) / $daySec);
            $durationDays = (int) round(($e - $s) / $daySec) + 1;
            if ($durationDays < 1) {
                $durationDays = 1;
            }
            $progress = (int) $t['progress'];
            $progressSum += $progress;

            $color = !empty($t['color']) ? $t['color'] : self::colorForPhase($t['phase']);

            $computed[] = array(
                'id'        => (int) $t['id'],
                'phase'     => $t['phase'],
                'title'     => $t['title'],
                'detail'    => isset($t['detail']) ? $t['detail'] : '',
                'start'     => $t['start_date'],
                'end'       => $t['end_date'],
                'progress'  => $progress,
                'color'     => $color,
                'offset_pct' => ($offsetDays / $totalDays) * 100,
                'width_pct'  => ($durationDays / $totalDays) * 100,
                'days'      => $durationDays,
            );

            if (!isset($phaseAgg[$t['phase']])) {
                $phaseAgg[$t['phase']] = array(
                    'label'   => $t['phase'],
                    'color'   => isset($phaseColors[$t['phase']]) ? $phaseColors[$t['phase']] : $color,
                    'count'   => 0,
                    'sum'     => 0,
                );
            }
            $phaseAgg[$t['phase']]['count']++;
            $phaseAgg[$t['phase']]['sum'] += $progress;
        }

        foreach ($phaseAgg as $phase => $agg) {
            $phaseAgg[$phase]['progress'] = $agg['count'] > 0 ? (int) round($agg['sum'] / $agg['count']) : 0;
        }

        // 오늘 표시선
        $todayTs = strtotime(date('Y-m-d'));
        if ($todayTs >= $rangeStart && $todayTs <= $rangeEnd) {
            $model['today_offset'] = (((int) round(($todayTs - $rangeStart) / $daySec)) / $totalDays) * 100;
        }

        $model['tasks'] = $computed;
        $model['months'] = $months;
        $model['phases'] = array_values($phaseAgg);
        $model['range_start'] = date('Y-m-d', $rangeStart);
        $model['range_end'] = date('Y-m-d', $rangeEnd);
        $model['total_days'] = $totalDays;
        $model['avg_progress'] = count($tasks) > 0 ? (int) round($progressSum / count($tasks)) : 0;

        return $model;
    }

    private static function clampProgress($value)
    {
        $value = (int) $value;
        if ($value < 0) {
            return 0;
        }
        if ($value > 100) {
            return 100;
        }
        return $value;
    }

    private static function normalizeDate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }
        return date('Y-m-d', $ts);
    }

    private static function sanitizeText($value, $maxLen)
    {
        $value = trim((string) $value);
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLen, 'UTF-8');
        }
        return substr($value, 0, $maxLen);
    }
}
