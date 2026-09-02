<?php
/**
 * 스토리보드: 마이페이지 — 대시보드 (메인)
 * 메뉴코드: 01-06
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
include __DIR__ . '/_fragments/zone-data-01-06.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--hifi sb-wf--mypage sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-06-hifi-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-06-hifi-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--mypage sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-06-hifi-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : '마이페이지';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-06';
?>
<style>
<?php include __DIR__ . '/_fragments/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-06-hifi-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 대시보드
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e($menuCode) ?></strong>
                · 회원 정보 · 주문/디자인 요약 · 바로가기 · 계정 관리 허브
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>마이페이지 — 대시보드</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/mypage 또는 /account</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>좌측 LNB + 메인 대시보드 그리드</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>로그인 필수</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>회원·플랜·주문·디자인 통합 허브 · 각 서브 메뉴 진입점</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>Front › 마이페이지 (<?= e($menuCode) ?>)</dd></div>
    </dl>

    <section class="sb-front-spec">
        <h3>레이아웃 구조 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead>
                <tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결 메뉴</th></tr>
            </thead>
            <tbody>
                <tr><td><code>L-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>글로벌 사이드바</td><td>메인 메뉴 · 하단 마이페이지(active) · 가이드·고객센터·설정</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>L-02</code></td><td><span class="tag tag--ui">UI</span></td><td>보유 포인트</td><td>12,450 P · 포인트 충전</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>상단 바</td><td>검색 · 장바구니(2) · 알림(3) · 프로필</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--ui">UI</span></td><td>회원·플랜 카드</td><td>프로필 · Pro Plan · 이용기간·사용량 · 퀵 아이콘 4종</td><td>01-06-01</td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--ui">UI</span></td><td>요약 통계</td><td>포인트 · 쿠폰 · 최근 주문 · 배송중</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-04</code></td><td><span class="tag tag--nav">Nav</span></td><td>바로가기</td><td>라벨 편집 · 새 라벨 · AI · 엑셀 · 주문 · 샘플</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-05</code></td><td><span class="tag tag--ui">UI</span></td><td>최근 디자인</td><td>가로 스크롤 카드 · 상태 태그 · 새 디자인(+)</td><td>01-06-04</td></tr>
                <tr><td><code>M-06</code></td><td><span class="tag tag--ui">UI</span></td><td>최근 주문 내역</td><td>상품·규격 · 주문일 · 배송 상태</td><td>01-06-02</td></tr>
                <tr><td><code>M-07</code></td><td><span class="tag tag--ui">UI</span></td><td>라벨 편집 도구</td><td>에디터 · 엑셀 · AI · 내 템플릿</td><td>01-02</td></tr>
                <tr><td><code>M-08</code></td><td><span class="tag tag--ui">UI</span></td><td>내 템플릿</td><td>저장 템플릿 목록</td><td>01-06-04</td></tr>
                <tr><td><code>M-09</code></td><td><span class="tag tag--ui">UI</span></td><td>브랜드 관리</td><td>브랜드 목록 · 새 브랜드 추가</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-10</code></td><td><span class="tag tag--ui">UI</span></td><td>배송지 관리</td><td>기본 배송지 · 배송지 추가</td><td>01-06-02</td></tr>
                <tr><td><code>M-11</code></td><td><span class="tag tag--ui">UI</span></td><td>계정·설정</td><td>회원정보 · 알림 · 결제/구독 · 보안</td><td>01-06-01</td></tr>
                <tr><td><code>M-12</code></td><td><span class="tag tag--cta">CTA</span></td><td>도움말 배너</td><td>1:1 문의 · FAQ</td><td>01-07</td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>사이드바</td><td>하단 「마이페이지」active · Tablet: 접기 · Mobile: 드로어</td></tr>
                <tr><td>회원·플랜 카드</td><td>「회원정보 수정」→ 01-06-01 · 사용량 바 → 결제/구독 · 퀵 아이콘 → 각 관리 화면</td></tr>
                <tr><td>요약 통계</td><td>4카드 각각 → 포인트·쿠폰·주문·배송 상세</td></tr>
                <tr><td>바로가기</td><td>6아이콘 → 편집기·AI·엑셀·주문·샘플 신청</td></tr>
                <tr><td>최근 디자인</td><td>카드 클릭 → 편집기 · 「+」→ 새 디자인 · 「전체 보기」→ 디자인 관리</td></tr>
                <tr><td>최근 주문</td><td>항목 클릭 → 주문 상세 · 상태 배지 색상 구분</td></tr>
                <tr><td>하단 그리드</td><td>5패널 → 도구·템플릿·브랜드·배송지·설정 각 화면</td></tr>
                <tr><td>도움말 배너</td><td>1:1 문의 · FAQ → 고객센터</td></tr>
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
