<?php
/**
 * Backoffice(02) 관리자 스토리보드 공용 렌더러
 *
 * 각 storyboard/02-*.php 페이지에서 아래 변수를 세팅한 뒤 include 한다.
 *   $adminCode   string  메뉴코드 (예: '02-03-01')
 *   $adminTitle  string  화면명
 *   $adminSub    string  헤더 보조 설명
 *   $adminMeta   array   [ ['dt'=>..,'dd'=>..], ... ] 메타 카드
 *   $adminZones  array   [ ['id'=>..,'kind'=>'nav|ui|cta|layout','block'=>..,'el'=>..,'link'=>..], ... ]
 *   $adminUx     array   [ ['item'=>..,'desc'=>..], ... ]
 *   $adminMockup callable 관리자 본문 목업 HTML 출력 (echo)
 *   $adminActive string  LNB active 최상위 코드 (기본: $adminCode 의 02-NN)
 *
 * @var array $menu
 */

$adminCode = isset($adminCode) ? $adminCode : (isset($menu['menu_code']) ? $menu['menu_code'] : '02');
$adminTitle = isset($adminTitle) ? $adminTitle : (isset($menu['title']) ? $menu['title'] : 'Backoffice');
$adminSub = isset($adminSub) ? $adminSub : '';
$adminMeta = isset($adminMeta) ? $adminMeta : array();
$adminZones = isset($adminZones) ? $adminZones : array();
$adminUx = isset($adminUx) ? $adminUx : array();
$adminMockup = isset($adminMockup) && is_callable($adminMockup) ? $adminMockup : null;

if (!isset($adminActive)) {
    $adminActive = preg_match('/^(02-\d{2})/', $adminCode, $m) ? $m[1] : '02-01';
}

$adminLnb = array(
    array('code' => '02-01', 'title' => '대시보드', 'ic' => '▤'),
    array('code' => '02-02', 'title' => '회원관리', 'ic' => '◕'),
    array('code' => '02-03', 'title' => '쇼핑몰 관리', 'ic' => '▣'),
    array('code' => '02-04', 'title' => '주문관리', 'ic' => '▦'),
    array('code' => '02-05', 'title' => '디자인 관리', 'ic' => '◈'),
    array('code' => '02-06', 'title' => '규격 관리', 'ic' => '▧'),
    array('code' => '02-07', 'title' => 'AI 관리', 'ic' => '✦'),
    array('code' => '02-08', 'title' => '콘텐츠 관리', 'ic' => '▤'),
    array('code' => '02-09', 'title' => '통계·정산', 'ic' => '∿'),
);

$adminCrumbParts = array('Backoffice');
if (!empty($breadcrumb) && is_array($breadcrumb)) {
    foreach ($breadcrumb as $bc) {
        if (isset($bc['title']) && $bc['title'] !== 'Backoffice (관리자)') {
            $adminCrumbParts[] = $bc['title'];
        }
    }
} else {
    $adminCrumbParts[] = $adminTitle;
}

if (!isset($sbFsMenuJson)) {
    $sbFsMenuJson = array();
}
if (empty($sbFsMenuJson) && !empty($sbFsMenuTree) && isset($sbFsLinkBase)) {
    $sbFsMenuJson = StoryboardFileService::buildFsMenuTree(
        $sbFsMenuTree,
        isset($sbFsMenuId) ? $sbFsMenuId : (isset($menu['id']) ? (int) $menu['id'] : 0),
        isset($sbFsContentStatusMap) ? $sbFsContentStatusMap : array(),
        $sbFsLinkBase
    );
}

if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array();
}
if (!empty($adminZones)) {
    foreach ($adminZones as $z) {
        if (empty($z['id'])) {
            continue;
        }
        $kind = isset($z['kind']) ? $z['kind'] : 'ui';
        $sbZoneDataMap[$z['id']] = array(
            'type' => strtoupper($kind),
            'typeKey' => $kind,
            'block' => isset($z['block']) ? $z['block'] : '',
            'elements' => isset($z['el']) ? $z['el'] : '',
            'menu' => isset($z['link']) ? $z['link'] : '—',
            'ux' => '',
        );
    }
}
$sbZoneDataMap['L-01'] = array(
    'type' => 'NAV', 'typeKey' => 'nav', 'block' => '관리자 LNB',
    'elements' => '대시보드·회원·쇼핑몰·주문·디자인·규격·AI·콘텐츠·통계',
    'menu' => '02 전역', 'ux' => '롤별 메뉴 노출 제한',
);
$sbZoneDataMap['T-01'] = array(
    'type' => 'NAV', 'typeKey' => 'nav', 'block' => '상단바',
    'elements' => '브레드크럼 · 통합검색 · 알림 · 관리자 프로필',
    'menu' => '—', 'ux' => '',
);

$sbWfRootClass = 'sb-wf sb-wf--admin sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/admin-mockup-inner.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    if (!$adminMockup) {
        echo '<div class="sb-wf-fragment-empty"><p><strong>' . e($adminTitle) . '</strong></p><p>관리자 목업이 없습니다.</p></div>';
        return;
    }
    echo '<div class="' . e($sbWfRootClass) . '" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/wf-shared-styles.php';
    include __DIR__ . '/02-admin-styles.php';
    include __DIR__ . '/wf-fullscreen-fill.php';
    echo '</style>';
    include __DIR__ . '/admin-mockup-inner.php';
    echo '<script type="application/json" class="sb-wf-zone-data">';
    echo json_encode($sbZoneDataMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    echo '</script>';
    echo '</div>';
    return;
}
?>
<style>
<?php include __DIR__ . '/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/02-admin-styles.php'; ?>
<?php include __DIR__ . '/wf-fullscreen-fill.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title"><?= e($adminTitle) ?> — 관리자</h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e($adminCode) ?></strong>
                <?= $adminSub !== '' ? ' · ' . e($adminSub) : '' ?>
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <?php if (!empty($adminMeta)): ?>
    <dl class="sb-front-meta-grid">
        <?php foreach ($adminMeta as $mc): ?>
        <div class="sb-front-meta-card"><dt><?= e($mc['dt']) ?></dt><dd><?= $mc['dd'] ?></dd></div>
        <?php endforeach; ?>
    </dl>
    <?php endif; ?>

    <?php if (!empty($adminZones)): ?>
    <section class="sb-front-spec">
        <h3>화면 구성 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead>
                <tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결</th></tr>
            </thead>
            <tbody>
                <?php foreach ($adminZones as $z): ?>
                <?php $kind = isset($z['kind']) ? $z['kind'] : 'ui'; ?>
                <tr>
                    <td><code><?= e($z['id']) ?></code></td>
                    <td><span class="tag tag--<?= e($kind) ?>"><?= e(strtoupper($kind)) ?></span></td>
                    <td><?= e($z['block']) ?></td>
                    <td><?= e($z['el']) ?></td>
                    <td><?= isset($z['link']) ? e($z['link']) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <?php if (!empty($adminUx)): ?>
    <section class="sb-front-spec">
        <h3>기능 · 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <?php foreach ($adminUx as $u): ?>
                <tr><td><?= e($u['item']) ?></td><td><?= e($u['desc']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <?php if ($adminMockup): ?>
    <p class="sb-front-preview-label">
        <span>MOCKUP</span> 아래는 실제 관리자 화면에 가까운 목업입니다
    </p>

    <?php include __DIR__ . '/storyboard-wf-wrap-shell.php'; ?>

    <p class="sb-adm-note">
        ※ 본 화면은 스토리보드용 목업입니다. 수치·데이터는 예시이며 실제 UI 컴포넌트/권한은 개발 단계에서 확정됩니다.<br>
        <strong>전체화면 보기</strong>에서 ☰ 메뉴로 전환 (전체화면 유지) · 영역 ID 클릭 → 상세 정보
    </p>
    <?php endif; ?>
</div>

<?php if ($adminMockup): ?>
<?php include __DIR__ . '/storyboard-wf-runtime.js.php'; ?>
<?php endif; ?>
