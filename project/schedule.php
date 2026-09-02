<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$view = isset($_GET['view']) ? $_GET['view'] : 'calendar';
if (!in_array($view, array('calendar', 'week', 'list'), true)) {
    $view = 'calendar';
}

$anchorDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $anchorDate)) {
    $anchorDate = date('Y-m-d');
}

$year = (int) date('Y', strtotime($anchorDate));
$month = (int) date('m', strtotime($anchorDate));

$allTypes = array_keys(ScheduleService::getTypeMeta());
$activeTypes = $allTypes;
if (!empty($_GET['types']) && is_array($_GET['types'])) {
    $activeTypes = array_values(array_intersect($allTypes, $_GET['types']));
    if (empty($activeTypes)) {
        $activeTypes = $allTypes;
    }
}

if ($view === 'calendar') {
    $events = ScheduleService::getEventsForMonth($project['id'], $year, $month);
} elseif ($view === 'week') {
    $events = ScheduleService::getEventsForWeek($project['id'], $anchorDate);
} else {
    $events = ScheduleService::getEventsForProject($project['id']);
}

$events = array_values(array_filter($events, function ($ev) use ($activeTypes) {
    return in_array($ev['type'], $activeTypes, true);
}));

$eventsByDate = ScheduleService::groupByDate($events);
$typeMeta = ScheduleService::getTypeMeta();
$typeCounts = array();
foreach ($events as $ev) {
    if (!isset($typeCounts[$ev['type']])) {
        $typeCounts[$ev['type']] = 0;
    }
    $typeCounts[$ev['type']]++;
}

$calendarGrid = ($view === 'calendar') ? ScheduleService::getCalendarGrid($year, $month) : array();
$weekDays = ($view === 'week') ? ScheduleService::getWeekDays($anchorDate) : array();

$weekStart = !empty($weekDays) ? $weekDays[0]['date'] : $anchorDate;
$weekEnd = !empty($weekDays) ? $weekDays[6]['date'] : $anchorDate;

$prevDate = date('Y-m-d', strtotime('-1 month', mktime(0, 0, 0, $month, 1, $year)));
$nextDate = date('Y-m-d', strtotime('+1 month', mktime(0, 0, 0, $month, 1, $year)));
$prevWeekDate = date('Y-m-d', strtotime('-7 days', strtotime($anchorDate)));
$nextWeekDate = date('Y-m-d', strtotime('+7 days', strtotime($anchorDate)));

$pageTitle = '일정관리';
$currentPage = 'schedule';
$today = date('Y-m-d');

render_page(__DIR__ . '/views/schedule.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker',
    'view', 'anchorDate', 'year', 'month', 'events', 'eventsByDate',
    'typeMeta', 'typeCounts', 'activeTypes', 'calendarGrid', 'weekDays',
    'weekStart', 'weekEnd', 'prevDate', 'nextDate', 'prevWeekDate', 'nextWeekDate', 'today'
));
