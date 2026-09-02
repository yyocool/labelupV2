<?php

class ProjectService
{
    public static function getAll()
    {
        $db = Database::getConnection();
        return $db->query('SELECT * FROM projects ORDER BY updated_at DESC')->fetchAll();
    }

    public static function getById($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM projects WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getOrCreateDefault()
    {
        $projects = self::getAll();
        if (!empty($projects)) {
            return $projects[0];
        }
        $db = Database::getConnection();
        $db->prepare('INSERT INTO projects (name, description, status) VALUES (?,?,?)')
           ->execute(['Label-UP 메인 프로젝트', '웹 개발 프로젝트 관리', 'active']);
        return self::getById((int) $db->lastInsertId());
    }

    public static function getProgressPhaseDefinitions()
    {
        static $definitions = null;
        if ($definitions === null) {
            $file = __DIR__ . '/data/progress_phases.php';
            $definitions = file_exists($file) ? require $file : array();
            if (!is_array($definitions)) {
                $definitions = array();
            }
        }
        return $definitions;
    }

    public static function updateProgress($projectId)
    {
        $breakdown = self::getProgressBreakdown($projectId);
        $overall = self::calculateWeightedProgress($breakdown);

        $db = Database::getConnection();
        $db->prepare('UPDATE projects SET progress = ? WHERE id = ?')->execute(array($overall, $projectId));
    }

    public static function calculateWeightedProgress(array $breakdown)
    {
        $weighted = 0.0;
        foreach (self::getProgressPhaseDefinitions() as $phase) {
            $label = $phase['label'];
            $weight = isset($phase['weight']) ? (float) $phase['weight'] : 0;
            $percent = isset($breakdown[$label]) ? (float) $breakdown[$label] : 0;
            $weighted += $percent * $weight / 100;
        }
        return (int) round($weighted);
    }

    public static function getProgressBreakdown($projectId)
    {
        $metrics = self::getMenuProgressMetrics($projectId);
        $menuTotal = max(1, (int) $metrics['total']);

        $policyPct = self::getPolicySetupPercent($projectId);
        $menuSetupPct = self::getMenuSetupPercent($projectId);
        $storyboardPct = self::phaseRatioPercent((int) $metrics['sb_done'], (int) $metrics['sb_ip'], $menuTotal);
        $designPct = self::phaseRatioPercent((int) $metrics['design_done'], (int) $metrics['design_ip'], $menuTotal);
        $publishingPct = self::phaseRatioPercent((int) $metrics['pub_done'], (int) $metrics['pub_ip'], $menuTotal);
        $dbDesignPct = self::getDbDesignPercent($projectId);
        $devFrontPct = self::phaseRatioPercent(
            (int) $metrics['front_code_done'],
            (int) $metrics['front_code_ip'],
            max(1, (int) $metrics['front_total'])
        );
        $devAdminPct = self::phaseRatioPercent(
            (int) $metrics['admin_code_done'],
            (int) $metrics['admin_code_ip'],
            max(1, (int) $metrics['admin_total'])
        );
        $testPct = self::getTestPercent($metrics, $menuTotal);
        $reviewPct = self::phaseRatioPercent((int) $metrics['rev_done'], (int) $metrics['rev_ip'], $menuTotal);
        $project = self::getById($projectId);
        $launchPct = self::getLaunchPercent($project, $reviewPct);

        return array(
            '정책수립'     => $policyPct,
            '메뉴구성'     => $menuSetupPct,
            '스토리보드'   => $storyboardPct,
            '디자인'       => $designPct,
            '퍼블리싱'     => $publishingPct,
            'DB설계'       => $dbDesignPct,
            '개발(사용자)' => $devFrontPct,
            '개발(관리자)' => $devAdminPct,
            '테스트'       => $testPct,
            '보완검수'     => $reviewPct,
            '배포'         => $launchPct,
        );
    }

    public static function getPolicySetupPercent($projectId)
    {
        $seedFile = __DIR__ . '/data/policy_seed.php';
        if (!file_exists($seedFile)) {
            return 0;
        }
        $seedItems = require $seedFile;
        $expected = is_array($seedItems) ? count($seedItems) : 0;
        if ($expected === 0) {
            return 0;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM policies WHERE project_id = ?');
        $stmt->execute(array((int) $projectId));
        $current = (int) $stmt->fetchColumn();

        return min(100, (int) round($current / $expected * 100));
    }

    public static function getMenuSetupPercent($projectId)
    {
        $expected = MenuSeedService::countNodes(MenuSeedService::getTreeData());
        if ($expected === 0) {
            return 0;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM menus WHERE project_id = ? AND is_active = 1');
        $stmt->execute(array((int) $projectId));
        $current = (int) $stmt->fetchColumn();

        return min(100, (int) round($current / $expected * 100));
    }

    public static function getMenuProgressMetrics($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN mp.storyboard_status = "done" THEN 1 ELSE 0 END) as sb_done,
                SUM(CASE WHEN mp.storyboard_status = "in_progress" THEN 1 ELSE 0 END) as sb_ip,
                SUM(CASE WHEN mp.design_status = "done" THEN 1 ELSE 0 END) as design_done,
                SUM(CASE WHEN mp.design_status = "in_progress" THEN 1 ELSE 0 END) as design_ip,
                SUM(CASE WHEN mp.publishing_status = "done" THEN 1 ELSE 0 END) as pub_done,
                SUM(CASE WHEN mp.publishing_status = "in_progress" THEN 1 ELSE 0 END) as pub_ip,
                SUM(CASE WHEN mp.coding_status = "done" THEN 1 ELSE 0 END) as code_done,
                SUM(CASE WHEN mp.coding_status = "in_progress" THEN 1 ELSE 0 END) as code_ip,
                SUM(CASE WHEN mp.review_status = "done" THEN 1 ELSE 0 END) as rev_done,
                SUM(CASE WHEN mp.review_status = "in_progress" THEN 1 ELSE 0 END) as rev_ip,
                SUM(CASE WHEN m.menu_code LIKE "01%" AND mp.coding_status != "na" THEN 1 ELSE 0 END) as front_total,
                SUM(CASE WHEN m.menu_code LIKE "01%" AND mp.coding_status = "done" THEN 1 ELSE 0 END) as front_code_done,
                SUM(CASE WHEN m.menu_code LIKE "01%" AND mp.coding_status = "in_progress" THEN 1 ELSE 0 END) as front_code_ip,
                SUM(CASE WHEN m.menu_code LIKE "02%" AND mp.coding_status != "na" THEN 1 ELSE 0 END) as admin_total,
                SUM(CASE WHEN m.menu_code LIKE "02%" AND mp.coding_status = "done" THEN 1 ELSE 0 END) as admin_code_done,
                SUM(CASE WHEN m.menu_code LIKE "02%" AND mp.coding_status = "in_progress" THEN 1 ELSE 0 END) as admin_code_ip,
                SUM(CASE WHEN mp.coding_status = "done" THEN 1 ELSE 0 END) as code_done_for_test,
                SUM(CASE WHEN mp.coding_status = "done" AND mp.review_status IN ("in_progress", "done") THEN 1 ELSE 0 END) as test_progress
            FROM menu_progress mp
            JOIN menus m ON m.id = mp.menu_id
            WHERE m.project_id = ? AND m.is_active = 1
        ');
        $stmt->execute(array((int) $projectId));
        $row = $stmt->fetch();
        return $row ? $row : array('total' => 0);
    }

    public static function phaseRatioPercent($done, $inProgress, $total)
    {
        if ($total <= 0) {
            return 0;
        }
        $score = (float) $done + ((float) $inProgress * 0.5);
        return min(100, (int) round($score / $total * 100));
    }

    public static function singleStatusPercent($status)
    {
        switch ($status) {
            case 'done':
                return 100;
            case 'in_progress':
                return 50;
            case 'na':
                return 100;
            default:
                return 0;
        }
    }

    public static function getDbDesignPercent($projectId)
    {
        $project = self::getById($projectId);
        if (!$project) {
            return 0;
        }
        $status = isset($project['db_design_status']) ? $project['db_design_status'] : 'pending';
        return self::singleStatusPercent($status);
    }

    public static function updateProjectSetupProgress($projectId, array $data)
    {
        $allowed = array('pending', 'in_progress', 'done', 'na');
        $status = isset($data['db_design_status']) ? $data['db_design_status'] : 'pending';
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }
        $note = trim(isset($data['db_design_note']) ? $data['db_design_note'] : '');

        $db = Database::getConnection();
        $db->prepare('UPDATE projects SET db_design_status = ?, db_design_note = ?, updated_at = NOW() WHERE id = ?')
           ->execute(array($status, $note !== '' ? $note : null, (int) $projectId));
    }

    public static function getTestPercent(array $metrics, $menuTotal)
    {
        $codeDoneForTest = (int) (isset($metrics['code_done_for_test']) ? $metrics['code_done_for_test'] : 0);
        if ($codeDoneForTest > 0) {
            return self::phaseRatioPercent(
                (int) (isset($metrics['test_progress']) ? $metrics['test_progress'] : 0),
                (int) (isset($metrics['rev_ip']) ? $metrics['rev_ip'] : 0),
                $codeDoneForTest
            );
        }

        $codingPct = self::phaseRatioPercent(
            (int) (isset($metrics['code_done']) ? $metrics['code_done'] : 0),
            (int) (isset($metrics['code_ip']) ? $metrics['code_ip'] : 0),
            max(1, (int) $menuTotal)
        );
        return min(20, (int) round($codingPct * 0.2));
    }

    public static function getLaunchPercent($project, $reviewPct)
    {
        if ($project && isset($project['status']) && $project['status'] === 'completed') {
            return 100;
        }
        if ($reviewPct >= 100) {
            return 80;
        }
        if ($reviewPct >= 70) {
            return 50;
        }
        return (int) round($reviewPct * 0.25);
    }

    public static function getDashboardStats($projectId)
    {
        $db = Database::getConnection();

        $menuCount = $db->prepare('SELECT COUNT(*) FROM menus WHERE project_id = ? AND is_active = 1');
        $menuCount->execute([$projectId]);
        $totalMenus = (int) $menuCount->fetchColumn();

        $issueOpen = $db->prepare("SELECT COUNT(*) FROM issues WHERE project_id = ? AND status IN ('open','in_progress')");
        $issueOpen->execute([$projectId]);
        $openIssues = (int) $issueOpen->fetchColumn();

        $issueTotal = $db->prepare('SELECT COUNT(*) FROM issues WHERE project_id = ?');
        $issueTotal->execute([$projectId]);
        $totalIssues = (int) $issueTotal->fetchColumn();

        $memberCount = $db->prepare('SELECT COUNT(*) FROM project_members WHERE project_id = ?');
        $memberCount->execute([$projectId]);
        $members = (int) $memberCount->fetchColumn();

        $milestoneStmt = $db->prepare("SELECT COUNT(*) FROM milestones WHERE project_id = ? AND status != 'completed'");
        $milestoneStmt->execute([$projectId]);
        $pendingMilestones = (int) $milestoneStmt->fetchColumn();

        $phaseStats = self::getMenuProgressMetrics($projectId);

        return compact('totalMenus', 'openIssues', 'totalIssues', 'members', 'pendingMilestones', 'phaseStats');
    }

    public static function getRecentActivities($projectId, $limit = 10)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT al.*, u.name as user_name
            FROM activity_logs al
            LEFT JOIN users u ON u.id = al.user_id
            WHERE al.project_id = ?
            ORDER BY al.created_at DESC LIMIT ?
        ');
        $stmt->execute([$projectId, $limit]);
        return $stmt->fetchAll();
    }

    public static function getMembers($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT pm.*, u.name, u.email, u.avatar_color, u.role as user_role
            FROM project_members pm
            JOIN users u ON u.id = pm.user_id
            WHERE pm.project_id = ?
            ORDER BY pm.role, u.name
        ');
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    public static function getPhaseOptions()
    {
        $options = array();
        foreach (self::getProgressPhaseDefinitions() as $phase) {
            $options[$phase['key']] = array(
                'label' => $phase['label'],
                'icon'  => $phase['icon'],
            );
        }
        $options['planning'] = array('label' => '기획(레거시)', 'icon' => '📋');
        $options['coding'] = array('label' => '개발(레거시)', 'icon' => '💻');
        return $options;
    }

    public static function normalizePhaseKey($phaseKey)
    {
        $aliases = array(
            'planning' => 'policy_setup',
            'coding'   => 'dev_front',
        );
        if (isset($aliases[$phaseKey])) {
            return $aliases[$phaseKey];
        }
        $valid = array();
        foreach (self::getProgressPhaseDefinitions() as $phase) {
            $valid[] = $phase['key'];
        }
        return in_array($phaseKey, $valid, true) ? $phaseKey : 'policy_setup';
    }

    public static function updatePhaseSettings($projectId, $mode, $phaseKey = null)
    {
        $validModes = array('auto', 'manual');
        if (!in_array($mode, $validModes, true)) {
            $mode = 'auto';
        }
        if ($mode === 'manual') {
            $phaseKey = self::normalizePhaseKey($phaseKey);
        } else {
            $phaseKey = null;
        }
        $db = Database::getConnection();
        $db->prepare('UPDATE projects SET phase_mode = ?, current_phase = ? WHERE id = ?')
           ->execute(array($mode, $phaseKey, $projectId));
    }

    public static function getPhaseTracker($projectId)
    {
        $project = self::getById($projectId);
        $breakdown = self::getProgressBreakdown($projectId);
        $metrics = self::getMenuProgressMetrics($projectId);
        $definitions = self::getProgressPhaseDefinitions();

        $phases = array();
        foreach ($definitions as $def) {
            $percent = isset($breakdown[$def['label']]) ? (int) $breakdown[$def['label']] : 0;
            $state = 'upcoming';
            if ($percent >= 100) {
                $state = 'done';
            }
            $phases[] = array(
                'key'     => $def['key'],
                'label'   => $def['label'],
                'icon'    => $def['icon'],
                'weight'  => $def['weight'],
                'percent' => $percent,
                'state'   => $state,
            );
        }

        $inProgressMap = array(
            'policy_setup' => ($breakdown['정책수립'] > 0 && $breakdown['정책수립'] < 100),
            'menu_setup'   => ($breakdown['메뉴구성'] > 0 && $breakdown['메뉴구성'] < 100),
            'storyboard'   => ((int) $metrics['sb_ip'] > 0),
            'design'       => ((int) $metrics['design_ip'] > 0),
            'publishing'   => ((int) $metrics['pub_ip'] > 0),
            'db_design'    => ($project && isset($project['db_design_status']) && $project['db_design_status'] === 'in_progress'),
            'dev_front'    => ((int) $metrics['front_code_ip'] > 0),
            'dev_admin'    => ((int) $metrics['admin_code_ip'] > 0),
            'test'         => ((int) $metrics['rev_ip'] > 0 && (int) $metrics['code_done_for_test'] > 0),
            'review'       => ((int) $metrics['rev_ip'] > 0),
        );

        $currentKey = 'policy_setup';
        $foundIp = false;
        foreach ($definitions as $def) {
            if (!empty($inProgressMap[$def['key']])) {
                $currentKey = $def['key'];
                $foundIp = true;
                break;
            }
        }

        if (!$foundIp) {
            foreach ($phases as $phase) {
                if ($phase['percent'] < 100) {
                    $currentKey = $phase['key'];
                    break;
                }
            }
            if ($project && isset($project['status']) && $project['status'] === 'completed') {
                $currentKey = 'launch';
            }
        }

        $phaseMode = isset($project['phase_mode']) ? $project['phase_mode'] : 'auto';
        $manualPhase = isset($project['current_phase']) ? $project['current_phase'] : null;
        $isManual = ($phaseMode === 'manual' && !empty($manualPhase));

        if ($isManual) {
            $currentKey = self::normalizePhaseKey($manualPhase);
            $order = array();
            foreach ($definitions as $def) {
                $order[] = $def['key'];
            }
            $curIdx = array_search($currentKey, $order, true);
            if ($curIdx === false) {
                $curIdx = 0;
                $currentKey = $order[0];
            }

            foreach ($phases as $i => &$p) {
                $idx = array_search($p['key'], $order, true);
                if ($idx === $curIdx) {
                    $p['state'] = 'current';
                } elseif ($idx !== false && $idx < $curIdx) {
                    $p['state'] = 'done';
                } else {
                    $p['state'] = 'upcoming';
                }
            }
            unset($p);
        } else {
            foreach ($phases as $i => &$p) {
                if ($p['key'] === $currentKey && $p['state'] !== 'done') {
                    $p['state'] = 'current';
                }
            }
            unset($p);
        }

        $options = self::getPhaseOptions();
        $currentLabel = isset($options[$currentKey]['label']) ? $options[$currentKey]['label'] : '정책수립';

        return array(
            'phases'          => $phases,
            'current_key'     => $currentKey,
            'current_label'   => $currentLabel,
            'overall_percent' => self::calculateWeightedProgress($breakdown),
            'phase_mode'      => $phaseMode,
            'is_manual'       => $isManual,
            'breakdown'       => $breakdown,
        );
    }
}
