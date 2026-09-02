<?php
/**
 * 스토리보드: 아이디 / 비밀번호 찾기
 * 메뉴코드: 01-03
 * 화면: Front 아이디·비밀번호 찾기 (사이드바 + 스텝 플로우) 와이어프레임
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
include __DIR__ . '/_fragments/zone-data-01-03.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--find sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-03-find-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-03-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--find sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-03-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : '아이디 / 비밀번호 찾기';
?>
<style>
<?php include __DIR__ . '/_fragments/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-03-find-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 와이어프레임
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e(isset($menu['menu_code']) ? $menu['menu_code'] : '01-03') ?></strong>
                · 사이드바 + 탭 + 6단계 스텝 카드 + 우측 안내 패널
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>아이디 / 비밀번호 찾기</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/find-account 또는 /auth/recover</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>사이드바 + 헤더 + 탭 + 3×2 스텝 그리드 + 우측 안내</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>비로그인 공개</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>휴대폰 본인인증 · 아이디 확인 · 비밀번호 재설정</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>Front 인증 — 01-03 (01-02 로그인 연계)</dd></div>
    </dl>

    <section class="sb-front-spec">
        <h3>레이아웃 구조 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead>
                <tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결 메뉴</th></tr>
            </thead>
            <tbody>
                <tr><td><code>L-01</code></td><td><span class="tag tag--layout">Layout</span></td><td>아이콘 레일</td><td>8개 글로벌 아이콘</td><td>01 전역</td></tr>
                <tr><td><code>L-02</code></td><td><span class="tag tag--nav">Nav</span></td><td>사이드 네비</td><td>로고 · 홈 · AI추천 · 라벨디자인 · … · 고객센터</td><td>01 전역</td></tr>
                <tr><td><code>M-01</code></td><td><span class="tag tag--ui">UI</span></td><td>페이지 헤더</td><td>제목 · 설명 · 정적 일러스트</td><td>01-03</td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--ui">UI</span></td><td>탭</td><td>아이디 찾기 · 비밀번호 찾기</td><td>01-03</td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--ui">UI</span></td><td>Step 01</td><td>이름 · 휴대전화 · 다음</td><td>01-03</td></tr>
                <tr><td><code>M-04</code></td><td><span class="tag tag--ui">UI</span></td><td>Step 02</td><td>인증번호 발송 · 재발송</td><td>01-03</td></tr>
                <tr><td><code>M-05</code></td><td><span class="tag tag--ui">UI</span></td><td>Step 03</td><td>6자리 OTP · 타이머 · 확인</td><td>01-03</td></tr>
                <tr><td><code>M-06</code></td><td><span class="tag tag--ui">UI</span></td><td>Step 04</td><td>본인 확인 완료 · 아이디 확인</td><td>01-03</td></tr>
                <tr><td><code>M-07</code></td><td><span class="tag tag--ui">UI</span></td><td>Step 05</td><td>새 비밀번호 · 확인 · 변경</td><td>01-03</td></tr>
                <tr><td><code>M-08</code></td><td><span class="tag tag--ui">UI</span></td><td>Step 06</td><td>변경 완료 · 로그인 · 홈</td><td>01-03</td></tr>
                <tr><td><code>R-01</code></td><td><span class="tag tag--ui">UI</span></td><td>보안 안내</td><td>본인인증 · 인증번호 · 비밀번호 · 정기 변경</td><td>—</td></tr>
                <tr><td><code>R-02</code></td><td><span class="tag tag--ui">UI</span></td><td>이용 방법 + CS</td><td>아이디/비밀번호 찾기 요약 · 고객센터</td><td>01-12</td></tr>
                <tr><td><code>B-01</code></td><td><span class="tag tag--ui">UI</span></td><td>하단 TIP</td><td>휴대전화 번호 변경 안내</td><td>01-04</td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>와이어프레임 표기</td><td>6개 스텝 카드를 한 화면에 배치(플로우 전체 구조 표시). 실제 UI는 단계별 1장씩 전환</td></tr>
                <tr><td>아이디 찾기</td><td>Step 01→03 인증 → Step 04에서 마스킹 아이디 표시 → 로그인(01-02) 이동</td></tr>
                <tr><td>비밀번호 찾기</td><td>탭 전환 · 아이디 추가 입력 · Step 05~06 비밀번호 재설정</td></tr>
                <tr><td>인증번호</td><td>3분 유효 · 재발송 60s 쿨다운 · 6자리 OTP 자동 포커스</td></tr>
                <tr><td>반응형</td><td>Tablet: 우측 패널 하단 이동 · Mobile: 스텝 1열 스택</td></tr>
            </tbody>
        </table>
    </section>

    <p class="sb-front-preview-label">
        <span>WIREFRAME</span> 아래는 실제 UI에 가까운 고해상도 와이어프레임입니다 (6단계를 한눈에 표시)
    </p>

    <?php include __DIR__ . '/_fragments/storyboard-wf-wrap-shell.php'; ?>

    <p style="margin-top:12px;font-size:12px;color:#94a3b8;text-align:center">
        ✕ VISUAL = 정적 이미지 · 점선 테두리 = 레이아웃 구역 · 01~06 = 인증 플로우 단계<br>
        <strong>전체화면 보기</strong>에서 ☰ 메뉴로 전환 (전체화면 유지) · 영역 ID 클릭 → 상세 정보
    </p>
</div>

<?php include __DIR__ . '/_fragments/storyboard-wf-runtime.js.php'; ?>
