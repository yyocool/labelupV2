<?php
/**
 * 스토리보드: 마이페이지 — 디자인 관리
 * 메뉴코드: 01-06-04
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
include __DIR__ . '/_fragments/zone-data-01-06-04.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--hifi sb-wf--mypage sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-06-hifi-styles.php';
    include __DIR__ . '/_fragments/01-06-sub-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-06-04-hifi-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--mypage sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-06-04-hifi-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : '디자인 관리';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-06-04';
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
                · 상태별 필터 · 디자인 그리드 · 일괄 작업(공유/복제/삭제)
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>마이페이지 — 디자인 관리</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/mypage/designs</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>좌측 LNB + 상단바/서브탭 + 필터 탭 + 디자인 그리드 + 일괄 작업 바</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>로그인 필수</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>저장된 라벨 디자인 조회·정리·일괄 관리</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>Front › 마이페이지 › 디자인 관리 (<?= e($menuCode) ?>)</dd></div>
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
                <tr><td><code>M-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>상단 바 · 서브탭</td><td>검색·장바구니·알림·프로필 + 내 정보/주문·배송/결제·구독/디자인 관리(active)</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--ui">UI</span></td><td>상태 필터 탭</td><td>전체 · 작업중 · 완료 · 공유</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--ui">UI</span></td><td>디자인 그리드</td><td>썸네일 카드 · 선택 체크박스 · 상태 태그 · 새 디자인(+)</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-04</code></td><td><span class="tag tag--ui">UI</span></td><td>일괄 작업 바</td><td>선택 개수 · 공유 · 복제 · 삭제</td><td><?= e($menuCode) ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>서브탭</td><td>내 정보 / 주문·배송 / 결제·구독 / 디자인 관리(active) → 각 01-06-0N 화면 전환</td></tr>
                <tr><td>상태 필터</td><td>탭 클릭 시 그리드 데이터 필터링</td></tr>
                <tr><td>디자인 카드</td><td>클릭 → 편집기 진입 · 체크박스 선택 시 하단 일괄 작업 바 활성화 · 「+」→ 새 디자인</td></tr>
                <tr><td>일괄 작업</td><td>1개 이상 선택 시 노출 · 공유/복제/삭제 액션 제공 (삭제는 확인 모달)</td></tr>
                <tr><td>반응형</td><td>Tablet: 그리드 2~3열 · Mobile: 1~2열, 일괄 작업 바 하단 고정</td></tr>
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
