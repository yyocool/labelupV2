<?php

function mb_safe_substr($str, $start, $length = null)
{
    if (function_exists('mb_substr')) {
        if ($length === null) {
            return mb_substr($str, $start);
        }
        return mb_substr($str, $start, $length);
    }
    if ($length === null) {
        return substr($str, $start);
    }
    return substr($str, $start, $length);
}

function arr_get($array, $key, $default = null)
{
    if (!is_array($array)) {
        return $default;
    }
    return array_key_exists($key, $array) ? $array[$key] : $default;
}

function app_config($key = null, $default = null)
{
    $config = isset($GLOBALS['APP_CONFIG']) ? $GLOBALS['APP_CONFIG'] : array();
    if ($key === null) {
        return $config;
    }
    return isset($config[$key]) ? $config[$key] : $default;
}

function e($str)
{
    return htmlspecialchars($str !== null ? $str : '', ENT_QUOTES, 'UTF-8');
}

function sanitize_rich_html($html)
{
    $html = trim($html !== null ? $html : '');
    if ($html === '') {
        return '';
    }
    $allowed = '<p><br><strong><b><em><i><u><s><strike><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><pre><code><a><table><thead><tbody><tr><th><td><hr><span><div><img><sub><sup>';
    $html = strip_tags($html, $allowed);
    $html = preg_replace('/(<[^>]+)\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '$1', $html);
    $html = preg_replace('/\s*javascript\s*:/i', '', $html);
    return $html;
}

function rich_html_is_empty($html)
{
    $text = trim(strip_tags($html !== null ? $html : ''));
    $text = preg_replace('/&nbsp;/iu', '', $text);
    $text = preg_replace('/\s+/u', '', $text);
    return $text === '';
}

function rich_html_display($html)
{
    $html = trim($html !== null ? $html : '');
    if ($html === '') {
        return '';
    }
    if (strpos($html, '<') !== false) {
        return sanitize_rich_html($html);
    }
    return nl2br(e($html));
}

function flash($key, $message = null)
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $val = isset($_SESSION['_flash'][$key]) ? $_SESSION['_flash'][$key] : null;
    unset($_SESSION['_flash'][$key]);
    return $val;
}

function status_badge($status)
{
    $map = array(
        'pending'     => array('대기', 'badge-gray'),
        'in_progress' => array('진행중', 'badge-blue'),
        'done'        => array('완료', 'badge-green'),
        'na'          => array('해당없음', 'badge-light'),
        'open'        => array('열림', 'badge-red'),
        'resolved'    => array('해결', 'badge-green'),
        'closed'      => array('종료', 'badge-gray'),
        'draft'       => array('초안', 'badge-gray'),
        'review'      => array('검토', 'badge-yellow'),
        'approved'    => array('승인', 'badge-green'),
        'planning'    => array('기획', 'badge-gray'),
        'active'      => array('진행', 'badge-blue'),
        'completed'   => array('완료', 'badge-green'),
        'on_hold'     => array('보류', 'badge-yellow'),
        'overdue'     => array('지연', 'badge-red'),
    );
    $pair = isset($map[$status]) ? $map[$status] : array($status, 'badge-gray');
    $label = $pair[0];
    $class = $pair[1];
    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
}

function priority_badge($priority)
{
    $map = array(
        'low'    => array('낮음', 'badge-light'),
        'medium' => array('보통', 'badge-blue'),
        'high'   => array('높음', 'badge-yellow'),
        'urgent' => array('긴급', 'badge-red'),
    );
    $pair = isset($map[$priority]) ? $map[$priority] : array($priority, 'badge-gray');
    $label = $pair[0];
    $class = $pair[1];
    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
}

function role_label($role)
{
    $map = array(
        'admin'     => '관리자',
        'owner'     => '오너',
        'pm'        => 'PM',
        'designer'  => '디자이너',
        'developer' => '개발자',
        'qa'        => 'QA',
        'viewer'    => '뷰어',
    );
    return isset($map[$role]) ? $map[$role] : $role;
}

function progress_percent(array $statuses)
{
    $weights = array('storyboard_status', 'design_status', 'publishing_status', 'coding_status', 'review_status');
    $done = 0;
    $total = 0;
    foreach ($weights as $key) {
        $val = isset($statuses[$key]) ? $statuses[$key] : 'pending';
        if ($val === 'na') continue;
        $total++;
        if ($val === 'done') $done++;
        elseif ($val === 'in_progress') $done += 0.5;
    }
    return $total > 0 ? (int) round(($done / $total) * 100) : 0;
}

function time_ago($datetime)
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return '방금 전';
    if ($diff < 3600) return floor($diff / 60) . '분 전';
    if ($diff < 86400) return floor($diff / 3600) . '시간 전';
    if ($diff < 604800) return floor($diff / 86400) . '일 전';
    return date('Y-m-d', strtotime($datetime));
}

function log_activity($projectId, $userId, $action, $entityType = null, $entityId = null, $description = null)
{
    $db = Database::getConnection();
    $stmt = $db->prepare('INSERT INTO activity_logs (project_id, user_id, action, entity_type, entity_id, description) VALUES (?,?,?,?,?,?)');
    $stmt->execute(array($projectId, $userId, $action, $entityType, $entityId, $description));
}

function get_active_project_id()
{
    return isset($_SESSION['active_project_id']) ? $_SESSION['active_project_id'] : null;
}

function set_active_project_id($id)
{
    $_SESSION['active_project_id'] = $id;
}

function avatar_initials($name)
{
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) >= 2) {
        return mb_safe_substr($parts[0], 0, 1) . mb_safe_substr($parts[1], 0, 1);
    }
    return mb_safe_substr($name, 0, 2);
}

function issue_type_icon($type)
{
    $map = array(
        'bug'         => '🐛',
        'feature'     => '✨',
        'improvement' => '💡',
        'task'        => '📋',
        'question'    => '❓',
    );
    return isset($map[$type]) ? $map[$type] : '📌';
}

function build_menu_tree(array $menus, $parentId = null)
{
    $tree = array();
    foreach ($menus as $menu) {
        $menuParentId = isset($menu['parent_id']) ? $menu['parent_id'] : null;
        if ($menuParentId == $parentId) {
            $menu['children'] = build_menu_tree($menus, $menu['id']);
            $tree[] = $menu;
        }
    }
    return $tree;
}

function menu_display_code(array $item)
{
    if (!empty($item['menu_code'])) {
        return $item['menu_code'];
    }
    return '';
}

function render_menu_tree_options(array $tree, $depth = 0, $selected = null, $exclude = null)
{
    $html = '';
    foreach ($tree as $item) {
        if ($exclude && $item['id'] == $exclude) continue;
        $code = menu_display_code($item);
        $label = ($code !== '' ? $code . ' ' : '') . str_repeat('— ', $depth) . $item['title'];
        $sel = ($selected == $item['id']) ? ' selected' : '';
        $html .= '<option value="' . $item['id'] . '"' . $sel . '>' . e($label) . '</option>';
        if (!empty($item['children'])) {
            $html .= render_menu_tree_options($item['children'], $depth + 1, $selected, $exclude);
        }
    }
    return $html;
}

function render_menu_tree_view($tree, $depth = 0)
{
    render_menu_list_view($tree, $depth);
}

function render_menu_list_view($tree, $depth = 0)
{
    foreach ($tree as $item) {
        echo menu_tree_render_item_card($item, $depth, false);
        if (!empty($item['children'])) {
            render_menu_list_view($item['children'], $depth + 1);
        }
    }
}

function render_menu_tree_structure($tree, $depth = 0)
{
    if (empty($tree)) {
        return;
    }
    $levelClass = $depth === 0 ? ' menu-org-level--root' : '';
    echo '<ul class="menu-org-level' . $levelClass . '">';
    foreach ($tree as $item) {
        $hasChildren = !empty($item['children']);
        $depthClass = 'depth-' . min((int) $depth, 3);
        $stateClass = $hasChildren ? ' has-children expanded' : '';
        echo '<li class="menu-org-node ' . $depthClass . $stateClass . '">';
        echo menu_org_render_node($item, $depth, $hasChildren);
        if ($hasChildren) {
            render_menu_tree_structure($item['children'], $depth + 1);
        }
        echo '</li>';
    }
    echo '</ul>';
}

function menu_org_render_node(array $item, $depth, $hasChildren = false)
{
    $depthClass = 'depth-' . min((int) $depth, 3);
    $pct = isset($item['progress_pct']) ? $item['progress_pct'] : 0;
    $status = isset($item['storyboard_status']) ? $item['storyboard_status'] : 'pending';

    $html = '<div class="menu-org-box ' . $depthClass . '">';
    if ($hasChildren) {
        $html .= '<button type="button" class="menu-org-toggle" aria-label="펼치기/접기" aria-expanded="true">▾</button>';
    }
    $html .= '<span class="menu-org-code" title="메뉴코드">' . e(menu_display_code($item)) . '</span>';
    $html .= '<strong class="menu-org-title">' . e($item['title']) . '</strong>';
    $html .= '<span class="menu-org-pct">' . (int) $pct . '%</span>';
    $html .= '<span class="menu-org-status">' . status_badge($status) . '</span>';
    $html .= '<div class="menu-org-actions">';
    $html .= '<a href="' . url('menu-detail.php?id=' . $item['id']) . '" class="menu-org-action" title="상세">상세</a>';
    $html .= '<a href="' . url('storyboard.php?menu_id=' . $item['id']) . '" class="menu-org-action" title="스토리보드">보드</a>';
    if (is_admin()) {
        $html .= '<button type="button" class="menu-org-action menu-org-action-btn" title="수정" onclick="editMenu(' . htmlspecialchars(json_encode($item), ENT_QUOTES) . ')">수정</button>';
        $html .= '<form method="post" class="menu-org-delete-form" onsubmit="return confirm(\'「' . e($item['title']) . '」 메뉴를 삭제할까요?\\n하위 메뉴가 있으면 함께 삭제됩니다.\')">';
        $html .= csrf_field();
        $returnView = isset($GLOBALS['LABELUP_MENU_VIEW']) ? $GLOBALS['LABELUP_MENU_VIEW'] : 'list';
        $html .= '<input type="hidden" name="return_view" value="' . e($returnView) . '">';
        $html .= '<input type="hidden" name="action" value="delete">';
        $html .= '<input type="hidden" name="id" value="' . (int) $item['id'] . '">';
        $html .= '<button type="submit" class="menu-org-action menu-org-action-btn menu-org-action-danger" title="삭제">삭제</button>';
        $html .= '</form>';
    }
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

function menu_tree_render_item_card(array $item, $depth, $withToggle = false)
{
    $depthClass = 'depth-' . min((int) $depth, 3);
    $menuCode = menu_display_code($item);
    $pct = isset($item['progress_pct']) ? $item['progress_pct'] : 0;
    $hasChildren = !empty($item['children']);

    $html = '<div class="menu-tree-item ' . $depthClass . '">';
    $html .= '<div class="menu-tree-item-header">';
    if ($withToggle && $hasChildren) {
        $html .= '<button type="button" class="menu-structure-toggle" aria-label="펼치기/접기" aria-expanded="true">▾</button>';
    } elseif ($withToggle) {
        $html .= '<span class="menu-structure-toggle-spacer"></span>';
    }
    if ($menuCode !== '') {
        $html .= '<span class="menu-tree-code" title="메뉴코드">' . e($menuCode) . '</span>';
    }
    $html .= '<span class="menu-tree-title">' . e($item['title']) . '</span>';
    $html .= '<div class="menu-tree-meta">';
    $html .= '<div class="menu-tree-progress ' . $depthClass . '">';
    $html .= '<div class="progress-bar-fill" style="width:' . (int) $pct . '%"></div>';
    $html .= '</div>';
    $html .= '<span class="menu-tree-pct">' . (int) $pct . '%</span>';
    $html .= status_badge(isset($item['storyboard_status']) ? $item['storyboard_status'] : 'pending');
    $html .= '<a href="' . url('menu-detail.php?id=' . $item['id']) . '" class="btn btn-secondary btn-sm">상세</a>';
    $html .= '<a href="' . url('storyboard.php?menu_id=' . $item['id']) . '" class="btn btn-secondary btn-sm">스토리보드</a>';
    if (is_admin()) {
        $html .= '<button class="btn btn-secondary btn-sm" onclick="editMenu(' . htmlspecialchars(json_encode($item), ENT_QUOTES) . ')">수정</button>';
        $html .= '<form method="post" style="display:inline" onsubmit="return confirm(\'「' . e($item['title']) . '」 메뉴를 삭제할까요?\\n하위 메뉴가 있으면 함께 삭제됩니다.\')">';
        $html .= csrf_field();
        $returnView = isset($GLOBALS['LABELUP_MENU_VIEW']) ? $GLOBALS['LABELUP_MENU_VIEW'] : 'list';
        $html .= '<input type="hidden" name="return_view" value="' . e($returnView) . '">';
        $html .= '<input type="hidden" name="action" value="delete">';
        $html .= '<input type="hidden" name="id" value="' . (int) $item['id'] . '">';
        $html .= '<button type="submit" class="btn btn-danger btn-sm">삭제</button>';
        $html .= '</form>';
    }
    $html .= '</div></div></div>';
    return $html;
}

function get_first_menu_id_from_tree(array $tree)
{
    if (empty($tree)) {
        return null;
    }
    return $tree[0]['id'];
}

function storyboard_active_frame_number($frames, $activeFrameId)
{
    if (!is_array($frames)) {
        return 1;
    }
    foreach ($frames as $i => $frame) {
        if ((int) $frame['id'] === (int) $activeFrameId) {
            return $i + 1;
        }
    }
    return 1;
}

function storyboard_json_frame($frame)
{
    return json_html_attr($frame);
}

function json_html_attr($data)
{
    $flags = defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0;
    $json = json_encode($data, $flags);
    if ($json === false) {
        $json = '{}';
    }
    return htmlspecialchars($json, ENT_QUOTES, 'UTF-8');
}

function storyboard_visibility_badge($visibility)
{
    $options = StoryboardService::getVisibilityOptions();
    $meta = isset($options[$visibility]) ? $options[$visibility] : array('label' => $visibility, 'badge' => 'badge-gray');
    return '<span class="badge ' . e($meta['badge']) . '">' . e($meta['label']) . '</span>';
}

function render_storyboard_menu_tree(array $tree, $activeMenuId, array $frameCounts, $depth = 0, $linkBase = null, array $contentStatusMap = array())
{
    $base = $linkBase ? $linkBase : url('storyboard.php');
    $html = '';
    foreach ($tree as $item) {
        $hasChildren = !empty($item['children']);
        $isActive = ($activeMenuId == $item['id']);
        $isAncestor = !empty($item['children']) && storyboard_tree_has_active($item['children'], $activeMenuId);
        $frameCount = isset($frameCounts[$item['id']]) ? $frameCounts[$item['id']] : 0;
        $status = isset($contentStatusMap[$item['id']]) ? $contentStatusMap[$item['id']] : 'none';
        if (!in_array($status, array('ready', 'stub', 'none'), true)) {
            $status = 'none';
        }
        $expanded = $isActive || $isAncestor ? ' expanded' : '';

        $html .= '<li class="sb-tree-node depth-' . $depth . $expanded . '">';
        $html .= '<div class="sb-tree-row sb-tree-row--' . $status . ($isActive ? ' active' : '') . '">';
        if ($hasChildren) {
            $html .= '<button type="button" class="sb-tree-toggle" aria-label="펼치기">▾</button>';
        } else {
            $html .= '<span class="sb-tree-dot sb-tree-dot--' . $status . '"></span>';
        }
        $html .= '<a href="' . $base . '?menu_id=' . $item['id'] . '" class="sb-tree-link sb-tree-link--' . $status . '">';
        if (!empty($item['menu_code'])) {
            $html .= '<span class="sb-tree-code">' . e($item['menu_code']) . '</span>';
        }
        $html .= '<span class="sb-tree-label">' . e($item['title']) . '</span>';
        if ($status === 'ready') {
            $html .= '<span class="sb-tree-badge sb-tree-badge--ready" title="스토리보드 완료">✓</span>';
        } elseif ($status === 'stub') {
            $html .= '<span class="sb-tree-badge sb-tree-badge--stub" title="준비중 (stub)">···</span>';
        } elseif ($frameCount > 0) {
            $html .= '<span class="sb-tree-badge">' . $frameCount . '</span>';
        }
        $html .= '</a></div>';

        if ($hasChildren) {
            $html .= '<ul class="sb-tree-children">' . render_storyboard_menu_tree($item['children'], $activeMenuId, $frameCounts, $depth + 1, $linkBase, $contentStatusMap) . '</ul>';
        }
        $html .= '</li>';
    }
    return $html;
}

function storyboard_tree_has_active(array $tree, $activeMenuId)
{
    foreach ($tree as $item) {
        if ($item['id'] == $activeMenuId) {
            return true;
        }
        if (!empty($item['children']) && storyboard_tree_has_active($item['children'], $activeMenuId)) {
            return true;
        }
    }
    return false;
}

function storyboard_history_action_label($action)
{
    $map = array(
        'frame_create'  => '화면 추가',
        'frame_update'  => '화면 수정',
        'frame_delete'  => '화면 삭제',
        'comment'       => '의견 등록',
        'status_change' => '상태 변경',
    );
    return isset($map[$action]) ? $map[$action] : $action;
}

function storyboard_history_action_icon($action)
{
    $map = array(
        'frame_create'  => '➕',
        'frame_update'  => '✏️',
        'frame_delete'  => '🗑️',
        'comment'       => '💬',
        'status_change' => '🔄',
    );
    return isset($map[$action]) ? $map[$action] : '📌';
}

function render_phase_tracker($tracker)
{
    if (empty($tracker) || empty($tracker['phases'])) {
        return '';
    }
    $html = '<div class="sidebar-phase-tracker">';
    $html .= '<div class="phase-tracker-header">';
    $html .= '<span class="phase-tracker-title">프로젝트 진행</span>';
    $html .= '<span class="phase-tracker-pct">' . (int) $tracker['overall_percent'] . '%</span>';
    $html .= '</div>';
    $html .= '<div class="phase-tracker-bar"><div class="phase-tracker-bar-fill" style="width:' . (int) $tracker['overall_percent'] . '%"></div></div>';
    $html .= '<div class="phase-tracker-current">';
    $html .= '<span class="phase-current-dot"></span>';
    $html .= '<strong>' . e($tracker['current_label']) . '</strong> 단계 진행 중';
    $html .= '</div>';
    $html .= '<ul class="phase-stepper">';

    $count = count($tracker['phases']);
    foreach ($tracker['phases'] as $i => $phase) {
        $state = isset($phase['state']) ? $phase['state'] : 'upcoming';
        $isLast = ($i === $count - 1);
        $html .= '<li class="phase-step phase-step--' . e($state) . ($isLast ? ' phase-step--last' : '') . '">';
        $html .= '<div class="phase-step-marker">';
        if ($state === 'done') {
            $html .= '<span class="phase-step-icon phase-step-icon--done">✓</span>';
        } elseif ($state === 'current') {
            $html .= '<span class="phase-step-icon phase-step-icon--current">' . e($phase['icon']) . '</span>';
        } else {
            $html .= '<span class="phase-step-icon">' . ($i + 1) . '</span>';
        }
        if (!$isLast) {
            $html .= '<span class="phase-step-line"></span>';
        }
        $html .= '</div>';
        $html .= '<div class="phase-step-body">';
        $html .= '<span class="phase-step-label">' . e($phase['label']) . '</span>';
        if ($state === 'current') {
            $html .= '<span class="phase-step-badge">NOW</span>';
        } elseif ($state === 'done') {
            $html .= '<span class="phase-step-status">완료</span>';
        } elseif ($phase['percent'] > 0) {
            $weightHint = isset($phase['weight']) ? ' · ' . (int) $phase['weight'] . '%' : '';
            $html .= '<span class="phase-step-status">' . (int) $phase['percent'] . '%' . e($weightHint) . '</span>';
        }
        $html .= '</div></li>';
    }

    $html .= '</ul></div>';
    return $html;
}

function archive_category_label($category)
{
    $map = ArchiveService::getCategories();
    return isset($map[$category]['label']) ? $map[$category]['label'] : $category;
}

function format_file_size($bytes)
{
    $bytes = (int) $bytes;
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / 1048576, 1) . ' MB';
}

function archive_file_icon($filename)
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $map = array(
        'pdf' => '📕',
        'doc' => '📘', 'docx' => '📘',
        'xls' => '📗', 'xlsx' => '📗',
        'ppt' => '📙', 'pptx' => '📙',
        'hwp' => '📄', 'hwpx' => '📄',
        'zip' => '🗜️',
        'png' => '🖼️', 'jpg' => '🖼️', 'jpeg' => '🖼️', 'gif' => '🖼️',
        'txt' => '📝', 'csv' => '📊',
    );
    return isset($map[$ext]) ? $map[$ext] : '📎';
}

function schedule_query($params = array())
{
    $view = isset($params['view']) ? $params['view'] : (isset($_GET['view']) ? $_GET['view'] : 'calendar');
    $date = isset($params['date']) ? $params['date'] : (isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'));
    $types = array();
    if (isset($params['types']) && is_array($params['types'])) {
        $types = $params['types'];
    } elseif (!empty($_GET['types']) && is_array($_GET['types'])) {
        $types = $_GET['types'];
    }
    $q = 'view=' . urlencode($view) . '&date=' . urlencode($date);
    foreach ($types as $t) {
        $q .= '&types[]=' . urlencode($t);
    }
    return url('schedule.php?' . $q);
}

function render_sidebar_menu(array $tree, $depth = 0)
{
    $html = '';
    foreach ($tree as $item) {
        $hasChildren = !empty($item['children']);
        $active = (isset($_GET['menu_id']) && $_GET['menu_id'] == $item['id']) ? ' active' : '';
        $html .= '<li class="menu-item depth-' . $depth . $active . '">';
        $html .= '<a href="' . url('menu-detail.php?id=' . $item['id']) . '" class="menu-link">';
        if ($hasChildren) $html .= '<span class="menu-toggle">▸</span>';
        $html .= e($item['title']);
        $pct = isset($item['progress_pct']) ? $item['progress_pct'] : 0;
        $html .= '<span class="menu-progress-mini">' . $pct . '%</span>';
        $html .= '</a>';
        if ($hasChildren) {
            $html .= '<ul class="submenu">' . render_sidebar_menu($item['children'], $depth + 1) . '</ul>';
        }
        $html .= '</li>';
    }
    return $html;
}
