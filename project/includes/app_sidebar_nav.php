<?php
/**
 * 메인 앱 좌측 사이드 네비게이션 (2단계)
 * 사용: include APP_ROOT . '/includes/app_sidebar_nav.php';
 * 변수: $currentPage (string), $sidebarNavCompact (bool, 아이콘 없는 간단형)
 */
$cp = isset($currentPage) ? $currentPage : '';
$compact = !empty($sidebarNavCompact);
$planningPages = array(
    'competitive-analysis',
    'policies',
    'pricing-analysis',
    'format-analysis',
    'menus',
    'storyboard',
);
$planningOpen = in_array($cp, $planningPages, true);

$navIcon = function ($paths) use ($compact) {
    if ($compact) {
        return '';
    }
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' . $paths . '</svg>';
};
?>
                <a href="<?= url('index.php') ?>" class="nav-item<?= $cp === 'dashboard' ? ' active' : '' ?>">
                    <?= $navIcon('<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>') ?>
                    대시보드
                </a>

                <details class="nav-group<?= $planningOpen ? ' is-open' : '' ?>"<?= $planningOpen ? ' open' : '' ?>>
                    <summary class="nav-group-toggle">
                        <?= $navIcon('<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="14" y2="11"/>') ?>
                        <span class="nav-group-label">기획</span>
                        <span class="nav-group-chevron" aria-hidden="true"></span>
                    </summary>
                    <div class="nav-group-items">
                        <a href="<?= url('competitive-analysis.php') ?>" class="nav-item nav-item--sub<?= $cp === 'competitive-analysis' ? ' active' : '' ?>">
                            경쟁서비스 분석
                        </a>
                        <a href="<?= url('policies.php') ?>" class="nav-item nav-item--sub<?= $cp === 'policies' ? ' active' : '' ?>">
                            정책관리
                        </a>
                        <a href="<?= url('pricing-analysis.php') ?>" class="nav-item nav-item--sub<?= $cp === 'pricing-analysis' ? ' active' : '' ?>">
                            요금정책분석
                        </a>
                        <?php if (is_super_admin()): ?>
                        <a href="<?= url('format-analysis.php') ?>" class="nav-item nav-item--sub<?= $cp === 'format-analysis' ? ' active' : '' ?>">
                            포맷 분석
                        </a>
                        <?php endif; ?>
                        <a href="<?= url('menus.php') ?>" class="nav-item nav-item--sub<?= $cp === 'menus' ? ' active' : '' ?>">
                            메뉴 구성도
                        </a>
                        <a href="<?= url('storyboard.php') ?>" class="nav-item nav-item--sub<?= $cp === 'storyboard' ? ' active' : '' ?>">
                            스토리보드
                        </a>
                    </div>
                </details>

                <a href="<?= url('issues.php') ?>" class="nav-item<?= $cp === 'issues' ? ' active' : '' ?>">
                    <?= $navIcon('<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>') ?>
                    이슈 관리
                </a>
                <a href="<?= url('milestones.php') ?>" class="nav-item<?= $cp === 'milestones' ? ' active' : '' ?>">
                    <?= $navIcon('<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>') ?>
                    마일스톤
                </a>
                <a href="<?= url('schedule.php') ?>" class="nav-item<?= $cp === 'schedule' ? ' active' : '' ?>">
                    <?= $navIcon('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>') ?>
                    일정관리
                </a>
                <a href="<?= url('meeting-minutes.php') ?>" class="nav-item<?= $cp === 'meeting-minutes' ? ' active' : '' ?>">
                    <?= $navIcon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>') ?>
                    회의록
                </a>
                <a href="<?= url('notices.php') ?>" class="nav-item<?= $cp === 'notices' ? ' active' : '' ?>">
                    <?= $navIcon('<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>') ?>
                    공지사항
                </a>
                <a href="<?= url('archive.php') ?>" class="nav-item<?= $cp === 'archive' ? ' active' : '' ?>">
                    <?= $navIcon('<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>') ?>
                    자료실
                </a>
