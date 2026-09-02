<?php
/**
 * 스토리보드: 마이페이지 — 결제·구독
 * 메뉴코드: 01-06-03
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
include __DIR__ . '/_fragments/zone-data-01-06-03.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--hifi sb-wf--mypage sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-06-hifi-styles.php';
    include __DIR__ . '/_fragments/01-06-sub-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-06-03-hifi-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--mypage sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-06-03-hifi-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : '결제·구독';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-06-03';
?>
<style>
<?php include __DIR__ . '/_fragments/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-06-hifi-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-06-sub-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 마이페이지
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e($menuCode) ?></strong>
                · 현재 플랜·사용량 · 결제 수단 · 결제 내역 · 업그레이드
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>마이페이지 — 결제·구독</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/mypage/billing</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>좌측 LNB + 상단바/서브탭 + 플랜/결제 카드 + 업그레이드 배너</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>로그인 필수</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>구독 플랜·결제 수단·결제 내역 관리 및 업셀링</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>Front › 마이페이지 › 결제·구독 (<?= e($menuCode) ?>)</dd></div>
    </dl>

    <section class="sb-front-spec">
        <h3>레이아웃 구조 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead>
                <tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결 메뉴</th></tr>
            </thead>
            <tbody>
                <tr><td><code>L-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>글로벌 사이드바</td><td>메인 메뉴 · 하단 마이페이지(active) · 가이드·고객센터·설정</td><td>01-06</td></tr>
                <tr><td><code>L-02</code></td><td><span class="tag tag--ui">UI</span></td><td>보유 포인트</td><td>12,450 P · 포인트 충전</td><td>01-06</td></tr>
                <tr><td><code>M-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>상단 바 · 서브탭</td><td>검색·장바구니·알림·프로필 + 내 정보/주문·배송/결제·구독(active)/디자인 관리</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--ui">UI</span></td><td>현재 플랜 카드</td><td>Pro Plan · 이용기간·잔여일 · 월 사용량 프로그레스</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--ui">UI</span></td><td>결제 수단</td><td>등록 카드 · 변경 · 결제 수단 추가</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-04</code></td><td><span class="tag tag--ui">UI</span></td><td>결제 내역</td><td>월별 구독 결제 목록 · 영수증 보기</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-05</code></td><td><span class="tag tag--cta">CTA</span></td><td>업그레이드 배너</td><td>Business 플랜 안내 · 업그레이드 CTA</td><td><?= e($menuCode) ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>서브탭</td><td>내 정보 / 주문·배송 / 결제·구독(active) / 디자인 관리 → 각 01-06-0N 화면 전환</td></tr>
                <tr><td>현재 플랜</td><td>사용량 프로그레스 바 · 「플랜 비교」→ 요금제 안내 · 「업그레이드」→ 결제 진행</td></tr>
                <tr><td>결제 수단</td><td>「변경」→ 카드 등록 모달 · 「추가」→ 신규 결제수단 등록</td></tr>
                <tr><td>결제 내역</td><td>항목 클릭 → 영수증 상세/다운로드 · 「전체 보기」→ 결제 내역 목록</td></tr>
                <tr><td>업그레이드 배너</td><td>Business 플랜 소개 → 플랜 변경 화면 이동</td></tr>
                <tr><td>반응형</td><td>Tablet: 카드 1열 유지 · Mobile: 배너 세로 스택</td></tr>
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
