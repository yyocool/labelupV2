<?php
/**
 * 스토리보드: HOME — 로그인 후 대시보드
 * 메뉴코드: 01-04
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
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
include __DIR__ . '/_fragments/zone-data-01-04.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--hifi sb-wf--home sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-04-hifi-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-04-hifi-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--home sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-04-hifi-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : 'HOME';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-04';
?>
<style>
<?php include __DIR__ . '/_fragments/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-04-hifi-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 로그인 후 대시보드
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e($menuCode) ?></strong>
                · HOME › 로그인 후 메인 · 최근 작업 · AI 사용 현황 · 빠른 진입
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>HOME — 로그인 후 대시보드</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/home 또는 /dashboard</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>아이콘 레일 + 사이드 네비 + 상단바 + 대시보드 위젯 그리드</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>로그인 사용자 (비로그인 → 랜딩/로그인)</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>서비스 허브 · 빠른 작업 진입 · AI·최근 작업·알림 한눈에</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>Front › HOME (<?= e($menuCode) ?>)</dd></div>
    </dl>

    <section class="sb-front-spec">
        <h3>레이아웃 구조 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead>
                <tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결 메뉴</th></tr>
            </thead>
            <tbody>
                <tr><td><code>L-01</code></td><td><span class="tag tag--layout">Layout</span></td><td>아이콘 레일</td><td>8개 · HOME active</td><td>01 전역</td></tr>
                <tr><td><code>L-02</code></td><td><span class="tag tag--nav">Nav</span></td><td>사이드 네비</td><td>대시보드~설정 · AI 사용 그룹</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>L-03</code></td><td><span class="tag tag--ui">UI</span></td><td>보유 포인트</td><td>12,450 P · 충전/내역 보기</td><td>01-06</td></tr>
                <tr><td><code>L-04</code></td><td><span class="tag tag--ui">UI</span></td><td>AI 사용량 카드</td><td>320/1,000 · 프로그레스바 · 12일 후 초기화 · 업그레이드</td><td>01-06</td></tr>
                <tr><td><code>L-05</code></td><td><span class="tag tag--cta">CTA</span></td><td>프리미엄 배너</td><td>프리미엄 이용 중 · 혜택 보기</td><td>01-06</td></tr>
                <tr><td><code>M-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>상단 바</td><td>검색 · 알림 · 도움말 · 프로필(Pro)</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--ui">UI</span></td><td>인사 + 퀵 액션</td><td>환영 문구 · 4개 빠른 작업 버튼</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--cta">CTA</span></td><td>AI 히어로 배너</td><td>AI 라벨 디자인 NEW · 시작하기</td><td>01-02-05</td></tr>
                <tr><td><code>M-04</code></td><td><span class="tag tag--ui">UI</span></td><td>최근 작업</td><td>4개 카드 · 썸네일 · 수정 시간</td><td>01-02</td></tr>
                <tr><td><code>M-05</code></td><td><span class="tag tag--ui">UI</span></td><td>AI 사용 현황</td><td>도넛 75% · 항목별 사용 · 업그레이드</td><td>01-06</td></tr>
                <tr><td><code>M-06</code></td><td><span class="tag tag--ui">UI</span></td><td>빠른 AI 도구</td><td>4색 카드 · 라벨/텍스트/이미지/규격</td><td>01-02-05</td></tr>
                <tr><td><code>M-07</code></td><td><span class="tag tag--ui">UI</span></td><td>알림</td><td>템플릿·주문·포인트 3건</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-08</code></td><td><span class="tag tag--ui">UI</span></td><td>활용 팁</td><td>단축키 · 디자인 · 규격 가이드</td><td>01-02</td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>사이드바</td><td>Desktop: 아이콘 레일(L-01) + 네비 패널(L-02) 항상 노출 · 하단 L-04 AI 사용량 · L-05 프리미엄 · L-03 포인트 (로그인 전용)</td></tr>
                <tr><td>아이콘 레일</td><td>클릭 시 해당 1depth 메뉴로 이동 + 사이드 패널 메뉴 그룹 전환</td></tr>
                <tr><td>퀵 액션</td><td>4개 버튼 → 새 디자인 · 템플릿 · 규격 검색 · 데이터 연동 각 화면</td></tr>
                <tr><td>AI 배너</td><td>「AI 디자인 시작하기」→ AI 라벨 디자인(01-02-05)</td></tr>
                <tr><td>최근 작업</td><td>카드 클릭 → 라벨 편집기 · 「전체 보기」→ 내 디자인 목록</td></tr>
                <tr><td>AI 사용 현황</td><td>도넛·항목별 사용량 · 「자세히 보기」→ 마이페이지 결제/구독</td></tr>
                <tr><td>빠른 AI 도구</td><td>4카드 → 각 AI 기능 화면 직접 진입</td></tr>
                <tr><td>반응형</td><td>Tablet: 2열 그리드 · Mobile: 1열 스택 · AI 배너 상단 고정</td></tr>
            </tbody>
        </table>
    </section>

    <p class="sb-front-preview-label">
        <span>WIREFRAME</span> 아래는 실제 UI에 가까운 고해상도 와이어프레임입니다
    </p>

    <?php include __DIR__ . '/_fragments/storyboard-wf-wrap-shell.php'; ?>

    <p style="margin-top:12px;font-size:12px;color:#94a3b8;text-align:center">
        <strong>전체화면 보기</strong>에서 ☰ 메뉴로 전환 · 영역 ID 클릭 → 상세 정보<br>
        📌 영역 표시로 L/M 구역 라벨을 켜고 끌 수 있습니다
    </p>
</div>

<?php include __DIR__ . '/_fragments/storyboard-wf-runtime.js.php'; ?>
