<?php

function render_page($layoutFile, $vars = array(), $layoutTemplate = 'layout.php')
{
    if (!isset($vars['phaseTracker']) && isset($GLOBALS['phaseTracker'])) {
        $vars['phaseTracker'] = $GLOBALS['phaseTracker'];
    }
    extract($vars);
    ob_start();
    try {
        include $layoutFile;
        $content = ob_get_clean();
    } catch (Exception $e) {
        ob_end_clean();
        labelup_render_fail('view', $e);
    }
    try {
        include __DIR__ . '/' . $layoutTemplate;
    } catch (Exception $e) {
        labelup_render_fail('layout', $e);
    }
}

function labelup_render_fail($stage, Exception $e)
{
    $msg = ucfirst($stage) . ' error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    if (function_exists('labelup_log_error')) {
        labelup_log_error($msg);
    }
    if (!empty($GLOBALS['LABELUP_DEBUG'])) {
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
            header('HTTP/1.1 500 Internal Server Error');
        }
        echo $msg;
        exit;
    }
    throw $e;
}

function render_admin_page($layoutFile, $vars = array())
{
    if (!isset($vars['phaseTracker']) && isset($GLOBALS['phaseTracker'])) {
        $vars['phaseTracker'] = $GLOBALS['phaseTracker'];
    }
    extract($vars);
    ob_start();
    try {
        include $layoutFile;
        $content = ob_get_clean();
    } catch (Exception $e) {
        ob_end_clean();
        labelup_render_fail('admin view', $e);
    }
    try {
        include __DIR__ . '/admin_layout.php';
    } catch (Exception $e) {
        labelup_render_fail('admin layout', $e);
    }
}

function init_project_context()
{
    $project = ProjectService::getOrCreateDefault();
    set_active_project_id($project['id']);
    $menus = MenuService::getByProject($project['id']);
    $menuTree = build_menu_tree($menus);
    $phaseTracker = ProjectService::getPhaseTracker($project['id']);
    $GLOBALS['phaseTracker'] = $phaseTracker;
    return compact('project', 'menus', 'menuTree', 'phaseTracker');
}
