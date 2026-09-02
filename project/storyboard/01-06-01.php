<?php
/**
 * 스토리보드: 마이페이지 — 내 정보
 * 메뉴코드: 01-06-01
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
include __DIR__ . '/_fragments/zone-data-01-06-01.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--hifi sb-wf--mypage sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-06-hifi-styles.php';
    include __DIR__ . '/_fragments/01-06-sub-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-06-01-hifi-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--mypage sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-06-01-hifi-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : '내 정보';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-06-01';
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
                · 회원 기본정보 수정 · 비밀번호 변경 · 회원 탈퇴
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>마이페이지 — 내 정보</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/mypage/profile</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>좌측 LNB + 상단바/서브탭 + 폼 카드 3단</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>로그인 필수</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>회원 기본정보·비밀번호 관리 및 탈퇴 처리</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>Front › 마이페이지 › 내 정보 (<?= e($menuCode) ?>)</dd></div>
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
                <tr><td><code>M-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>상단 바 · 서브탭</td><td>검색·장바구니·알림·프로필 + 내 정보(active)/주문·배송/결제·구독/디자인 관리</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--ui">UI</span></td><td>기본 정보 폼</td><td>이름 · 이메일 · 연락처 · 회사명 · 저장/취소</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--ui">UI</span></td><td>비밀번호 변경</td><td>현재/새 비밀번호 · 확인 · 변경 CTA</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-04</code></td><td><span class="tag tag--ui">UI</span></td><td>회원 탈퇴 안내</td><td>유의사항 · 탈퇴 신청 버튼</td><td><?= e($menuCode) ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>서브탭</td><td>내 정보 / 주문·배송 / 결제·구독 / 디자인 관리 → 각 01-06-0N 화면 전환</td></tr>
                <tr><td>기본 정보</td><td>이메일은 수정 불가(회원 식별자) · 나머지 항목 인라인 편집 후 저장</td></tr>
                <tr><td>비밀번호 변경</td><td>소셜 로그인 계정은 비활성 처리 · 실패 시 인라인 오류 메시지</td></tr>
                <tr><td>회원 탈퇴</td><td>버튼 클릭 → 확인 모달 → 사유 선택 → 최종 확인</td></tr>
                <tr><td>반응형</td><td>Tablet: 폼 2열 유지 · Mobile: 폼 1열 · 사이드바 드로어</td></tr>
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
