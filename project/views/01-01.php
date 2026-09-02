<?php
/**
 * 스토리보드: 회원가입
 * 메뉴코드: 01-01
 * 화면: Front 회원가입 (사이드바 + 상단바 + 프로모 + 가입폼) 와이어프레임
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
include __DIR__ . '/_fragments/zone-data-01-01.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--signup sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-01-signup-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-01-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--signup sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-01-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : '회원가입';
?>
<style>
<?php include __DIR__ . '/_fragments/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-01-signup-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 와이어프레임
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e(isset($menu['menu_code']) ? $menu['menu_code'] : '01-01') ?></strong>
                · 사이드바 + 상단바 + 프로모 + 가입폼 + 혜택 목록
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>회원가입</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/signup 또는 /register</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>사이드바 + 상단바 + 3열(프로모·폼·혜택)</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>비로그인 공개 · 가입 완료 시 HOME 리다이렉트</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>이메일/소셜 회원가입 · SMS 본인인증 · 약관 동의</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>Front 인증 — 01-01 (01-02 로그인 연계)</dd></div>
    </dl>

    <section class="sb-front-spec">
        <h3>레이아웃 구조 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead>
                <tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결 메뉴</th></tr>
            </thead>
            <tbody>
                <tr><td><code>L-01</code></td><td><span class="tag tag--layout">Layout</span></td><td>아이콘 레일</td><td>8개 글로벌 아이콘</td><td>01 전역</td></tr>
                <tr><td><code>L-02</code></td><td><span class="tag tag--nav">Nav</span></td><td>사이드 네비</td><td>로고 · 홈~설정 · 고객센터(하단)</td><td>01 전역</td></tr>
                <tr><td><code>M-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>상단 바</td><td>검색 · 알림(3) · 로그인/회원가입</td><td>01-02</td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--ui">UI</span></td><td>페이지 헤더</td><td>회원가입 · 부제목</td><td>01-01</td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--ui">UI</span></td><td>프로모 + 비주얼</td><td>배지 · H1 · 정적 일러스트</td><td>—</td></tr>
                <tr><td><code>M-04</code></td><td><span class="tag tag--ui">UI</span></td><td>가입 혜택 카드</td><td>사용자·템플릿·보안·고객지원 (4카드)</td><td>—</td></tr>
                <tr><td><code>R-01</code></td><td><span class="tag tag--ui">UI</span></td><td>회원가입 폼</td><td>소셜 · 입력 6종 · 약관 · 회원가입</td><td>01-01</td></tr>
                <tr><td><code>R-02</code></td><td><span class="tag tag--ui">UI</span></td><td>혜택 목록</td><td>AI·템플릿·규격·인쇄·데이터 (5항목)</td><td>—</td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>소셜 가입</td><td>네이버·카카오·구글 OAuth · 기존 계정 연동 확인</td></tr>
                <tr><td>휴대폰 인증</td><td>인증번호 받기 → 3분 타이머 · 6자리 입력 · 재발송</td></tr>
                <tr><td>약관</td><td>필수 2종 미체크 시 제출 불가 · 약관 전문 모달</td></tr>
                <tr><td>비밀번호</td><td>실시간 규칙 검증 · 👁 표시 토글 · 확인 일치 검사</td></tr>
                <tr><td>가입 완료</td><td>성공 → 01 HOME 또는 온보딩 · 실패 → 인라인 에러</td></tr>
                <tr><td>반응형</td><td>Tablet: R-02 하단 이동 · Mobile: 1열 스택</td></tr>
            </tbody>
        </table>
    </section>

    <p class="sb-front-preview-label">
        <span>WIREFRAME</span> 아래는 실제 UI에 가까운 고해상도 와이어프레임입니다
    </p>

    <?php include __DIR__ . '/_fragments/storyboard-wf-wrap-shell.php'; ?>

    <p style="margin-top:12px;font-size:12px;color:#94a3b8;text-align:center">
        ✕ VISUAL = 정적 이미지 · 점선 테두리 = 레이아웃 구역<br>
        <strong>전체화면 보기</strong>에서 ☰ 메뉴로 전환 (전체화면 유지) · 영역 ID 클릭 → 상세 정보
    </p>
</div>

<?php include __DIR__ . '/_fragments/storyboard-wf-runtime.js.php'; ?>
