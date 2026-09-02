<?php
/**
 * @var array $menu
 * @var array $sbFsMenuJson
 * @var string $sbWfRootClass
 * @var string $sbWfBodyFragment
 */
if (empty($sbWfRootClass)) {
    $sbWfRootClass = 'sb-wf sb-wf-annotate';
}
if (empty($sbWfBodyFragment)) {
    $sbWfBodyFragment = __DIR__ . '/01-wireframe-body.php';
}
?>
    <div class="sb-wf-wrap" id="sbWfWrap"
         data-fragment-url="<?= e(url('storyboard-fragment.php')) ?>"
         data-initial-menu-id="<?= (int) (isset($sbFsMenuId) ? $sbFsMenuId : (isset($menu['id']) ? $menu['id'] : 0)) ?>">
        <div class="sb-wf-fs-bar">
            <div class="sb-wf-fs-bar-left">
                <?php if (!empty($sbFsMenuJson)): ?>
                <button type="button" class="sb-wf-fs-menu-btn" id="sbWfFsMenuBtn" title="다른 메뉴로 이동">☰ 메뉴</button>
                <?php endif; ?>
                <span class="sb-wf-fs-bar-title" id="sbWfFsBarTitle"><?= e(isset($menu['title']) ? $menu['title'] : 'Front') ?><?= !empty($menu['menu_code']) ? ' (' . e($menu['menu_code']) . ')' : '' ?> — 와이어프레임 (전체화면)<span class="sb-wf-fs-bar-hint">· ☰ 메뉴 · 영역 ID 클릭</span></span>
            </div>
            <button type="button" class="sb-wf-fs-exit" id="sbWfExitFullscreen">✕ 전체화면 종료</button>
        </div>

        <?php if (!empty($sbFsMenuJson)): ?>
        <div class="sb-wf-fs-menu-overlay" id="sbWfFsMenuOverlay" aria-hidden="true">
            <div class="sb-wf-fs-menu-panel" role="dialog" aria-label="스토리보드 메뉴">
                <div class="sb-wf-fs-menu-head">
                    <h3>스토리보드 메뉴</h3>
                    <button type="button" class="sb-wf-fs-menu-close" id="sbWfFsMenuClose" title="닫기">×</button>
                </div>
                <div class="sb-wf-fs-menu-legend">
                    <span><i class="ready"></i> SB 준비됨</span>
                    <span><i class="stub"></i> 준비중 (stub)</span>
                    <span><i class="none"></i> 미작성</span>
                </div>
                <div class="sb-wf-fs-menu-tree" id="sbWfFsMenuTree"></div>
            </div>
        </div>
        <script type="application/json" id="sbWfFsMenuData"><?= json_encode($sbFsMenuJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
        <?php endif; ?>

        <aside class="sb-wf-info-panel" id="sbWfInfoPanel" aria-live="polite">
            <div class="sb-wf-info-head">
                <div>
                    <p class="sb-wf-info-id" id="sbWfInfoId">—</p>
                    <span class="sb-wf-info-type" id="sbWfInfoType"></span>
                </div>
                <button type="button" class="sb-wf-info-close" id="sbWfInfoClose" title="닫기">×</button>
            </div>
            <div class="sb-wf-info-body" id="sbWfInfoBody"></div>
        </aside>

        <div id="sbWfViewport" class="sb-wf-viewport">
        <div class="<?= e($sbWfRootClass) ?>" id="sbWfRoot">
            <?php include $sbWfBodyFragment; ?>
        </div>
        </div><!-- /.sb-wf-viewport -->
    </div><!-- /.sb-wf-wrap -->
