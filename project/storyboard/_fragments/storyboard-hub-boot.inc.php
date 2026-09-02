<?php
/**
 * 허브형 스토리보드 부트스트랩
 * 호출 전: $sbHubCode, $sbHubZoneFile, $sbHubBodyFile, $sbHubStyles (optional array)
 */
$sbFsMenuJson = array();
if (!empty($sbFsMenuTree) && isset($sbFsLinkBase)) {
    $sbFsMenuJson = StoryboardFileService::buildFsMenuTree(
        $sbFsMenuTree,
        isset($sbFsMenuId) ? $sbFsMenuId : (isset($menu['id']) ? (int) $menu['id'] : 0),
        isset($sbFsContentStatusMap) ? $sbFsContentStatusMap : array(),
        $sbFsLinkBase
    );
}
if (!empty($sbHubZoneFile) && is_file($sbHubZoneFile)) {
    include $sbHubZoneFile;
}
if (!isset($sbHubStyles) || !is_array($sbHubStyles)) {
    $sbHubStyles = array(__DIR__ . '/01-05-hub-shared-styles.php');
}
if (!isset($sbWfRootClass)) {
    $sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
}
$sbWfBodyFragment = $sbHubBodyFile;

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="' . htmlspecialchars($sbWfRootClass, ENT_QUOTES, 'UTF-8') . '" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/wf-shared-styles.php';
    foreach ($sbHubStyles as $sf) {
        if (is_file($sf)) {
            include $sf;
        }
    }
    echo '</style>';
    include $sbHubBodyFile;
    include __DIR__ . '/zone-data-script.php';
    echo '</div>';
} else {
    $styleFragments = $sbHubStyles;
    include __DIR__ . '/storyboard-hub-doc.inc.php';
}
