<?php

require_once __DIR__ . '/Database.php';


$appConfig = require __DIR__ . '/../config/app.php';
$GLOBALS['APP_CONFIG'] = is_array($appConfig) ? $appConfig : array();

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

$GLOBALS['LABELUP_DEBUG'] = (!empty($GLOBALS['APP_CONFIG']['debug']))
    || (isset($_GET['debug']) && $_GET['debug'] === '1');

if ($GLOBALS['LABELUP_DEBUG']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    register_shutdown_function('labelup_shutdown_debug');
}

if (!function_exists('labelup_log_error')) {
    function labelup_log_error($message)
    {
        $logFile = APP_ROOT . '/storage/php-error.log';
        $line = date('Y-m-d H:i:s') . ' ' . $message . "\n";
        @file_put_contents($logFile, $line, FILE_APPEND);
    }
}

if (!function_exists('labelup_shutdown_debug')) {
    function labelup_shutdown_debug()
    {
        $e = error_get_last();
        if (!$e || !in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
            return;
        }
        $msg = $e['message'] . ' in ' . $e['file'] . ':' . $e['line'];
        if (function_exists('labelup_log_error')) {
            labelup_log_error('[FATAL] ' . $msg);
        }
        if (empty($GLOBALS['LABELUP_DEBUG'])) {
            return;
        }
        if (headers_sent()) {
            echo "\n\n[FATAL] " . $msg . "\n";
        } else {
            header('Content-Type: text/plain; charset=utf-8');
            echo '[FATAL] ' . $msg . "\n";
        }
    }
}

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/helpers.php';



$timezone = app_config('timezone', 'Asia/Seoul');

if ($timezone) {

    date_default_timezone_set($timezone);

}



if (function_exists('session_status')) {

    if (session_status() === PHP_SESSION_NONE) {

        session_name(app_config('session_key', 'labelup_session'));

        session_start();

    }

} elseif (!isset($_SESSION)) {

    session_name(app_config('session_key', 'labelup_session'));

    session_start();

}



require_once __DIR__ . '/auth.php';

require_once __DIR__ . '/ProjectService.php';

require_once __DIR__ . '/MenuService.php';

require_once __DIR__ . '/MenuSeedService.php';

require_once __DIR__ . '/StoryboardService.php';
require_once __DIR__ . '/StoryboardFileService.php';

require_once __DIR__ . '/IssueService.php';

require_once __DIR__ . '/ScheduleService.php';

require_once __DIR__ . '/ScheduleTaskService.php';

require_once __DIR__ . '/ArchiveService.php';

require_once __DIR__ . '/PolicyService.php';

require_once __DIR__ . '/MeetingMinutesService.php';

require_once __DIR__ . '/FormatParser.php';

require_once __DIR__ . '/FormatAnalysisService.php';

require_once __DIR__ . '/FeatureMapService.php';
require_once __DIR__ . '/DevScopeService.php';
require_once __DIR__ . '/ResumeService.php';
require_once __DIR__ . '/CompanyHistoryService.php';

require_once __DIR__ . '/view.php';

require_once __DIR__ . '/migrate.php';



if (file_exists(APP_ROOT . '/storage/installed.lock')) {

    migrate_check();

}



if (!defined('BASE_URL')) {

    define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

}

if (!defined('BASE_PATH')) {

    define('BASE_PATH', str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])));

}



function url($path = '')

{

    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

    if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {

        $base = dirname($base);

    }

    return $base . '/' . ltrim($path, '/');

}



function asset($path)
{
    $rel = 'assets/' . ltrim($path, '/');
    $file = APP_ROOT . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $v = file_exists($file) ? filemtime($file) : time();
    return url($rel . '?v=' . $v);
}



function admin_url($path = '')

{

    return url('admin/' . ltrim($path, '/'));

}



function redirect($path)

{

    header('Location: ' . url($path));

    exit;

}



function admin_redirect($path)

{

    header('Location: ' . admin_url($path));

    exit;

}

