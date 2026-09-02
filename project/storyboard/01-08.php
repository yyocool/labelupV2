<?php
/**
 * 스토리보드: 쇼핑몰 — 메인 (첫 화면)
 * 메뉴코드: 01-08
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
include __DIR__ . '/_fragments/zone-data-01-08.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--hifi sb-wf--shop sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-08-hifi-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-08-hifi-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--shop sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-08-hifi-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : '쇼핑몰';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-08';
?>
<style>
<?php include __DIR__ . '/_fragments/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-08-hifi-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 메인 (첫 화면)
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e($menuCode) ?></strong>
                · 쇼핑몰 홈 · 라벨 용지 탐색 · 규격/재질 큐레이션
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>쇼핑몰 — 메인</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/shop 또는 /mall</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>좌측 LNB + 메인 콘텐츠 + 우측 추천/도움말</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>로그인 사용자 (비로그인 시 간소화)</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>라벨 용지·규격 탐색 · 재질별 추천 · 장바구니 진입</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>Front › 쇼핑몰 (<?= e($menuCode) ?>)</dd></div>
    </dl>

    <section class="sb-front-spec">
        <h3>레이아웃 구조 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead>
                <tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결 메뉴</th></tr>
            </thead>
            <tbody>
                <tr><td><code>L-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>쇼핑몰 사이드바</td><td>홈(active) · 템플릿 · 용지/라벨 · AI디자인 · 내디자인 · 주문내역</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>L-02</code></td><td><span class="tag tag--ui">UI</span></td><td>보유 포인트</td><td>12,450 P · 포인트 충전</td><td>01-06</td></tr>
                <tr><td><code>M-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>상단 바</td><td>검색 · 장바구니(2) · 프로필</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--cta">CTA</span></td><td>히어로 배너</td><td>헤드라인 · 라벨 검색/규격 CTA · 제품 콜라주 · 슬라이더</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--ui">UI</span></td><td>카테고리 아이콘</td><td>A4·재질별·방수·투명·원형 등 10종</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-04</code></td><td><span class="tag tag--ui">UI</span></td><td>자주 찾는 규격</td><td>가로 스크롤 카드 · A4 24칸 등</td><td>01-08-01</td></tr>
                <tr><td><code>M-05</code></td><td><span class="tag tag--ui">UI</span></td><td>재질별 추천</td><td>유광/무광/투명 PET 등</td><td>01-08-01</td></tr>
                <tr><td><code>R-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>용도별 추천</td><td>제품·포장·바코드·보안·파일 5항목</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>R-02</code></td><td><span class="tag tag--ui">UI</span></td><td>도움말</td><td>규격 가이드 · 샘플 신청 · 인쇄 가이드</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>R-03</code></td><td><span class="tag tag--cta">CTA</span></td><td>에디터 프로모</td><td>라벨 편집하기 · 에디터 일러스트</td><td>01-02</td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>사이드바</td><td>쇼핑몰 전용 LNB · 홈 active · Tablet: 접기 · Mobile: 드로어</td></tr>
                <tr><td>검색</td><td>규격·재질 키워드 · 자동완성 · Enter → 검색 결과</td></tr>
                <tr><td>히어로</td><td>자동 슬라이드 · 「라벨 검색하기」→ 통합 검색 · 「규격으로 찾기」→ 규격 필터</td></tr>
                <tr><td>카테고리</td><td>10개 아이콘 → 해당 카테고리 상품 목록</td></tr>
                <tr><td>규격/재질 섹션</td><td>가로 스크롤 · 카드 클릭 → 상품 상세 · 「전체 보기」→ 목록</td></tr>
                <tr><td>우측 패널</td><td>용도별 추천 · 도움말 링크 · 에디터 CTA → 라벨 편집기</td></tr>
                <tr><td>장바구니</td><td>배지(2) · 클릭 → 장바구니 페이지</td></tr>
            </tbody>
        </table>
    </section>

    <p class="sb-front-preview-label">
        <span>WIREFRAME</span> 아래는 실제 UI에 가까운 고해상도 와이어프레임입니다
    </p>

    <?php include __DIR__ . '/_fragments/storyboard-wf-wrap-shell.php'; ?>

    <p style="margin-top:12px;font-size:12px;color:#94a3b8;text-align:center">
        <strong>전체화면 보기</strong>에서 ☰ 메뉴로 전환 · 영역 ID 클릭 → 상세 정보<br>
        📌 영역 표시로 L/M/R 구역 라벨을 켜고 끌 수 있습니다
    </p>
</div>

<?php include __DIR__ . '/_fragments/storyboard-wf-runtime.js.php'; ?>
