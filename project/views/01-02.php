<?php
/**
 * 스토리보드: 로그인
 * 메뉴코드: 01-02
 * 화면: Front 로그인 (사이드바 + 프로모 + 로그인 폼) 와이어프레임
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
include __DIR__ . '/_fragments/zone-data-01-02.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--login sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-02-login-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-02-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--login sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-02-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : '로그인';
?>
<style>
<?php include __DIR__ . '/_fragments/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-02-login-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 로그인 와이어프레임
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e(isset($menu['menu_code']) ? $menu['menu_code'] : '01-02') ?></strong>
                · 사이드바 + 프로모 영역 + 로그인 폼 2분할 레이아웃
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>Front 로그인</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/login</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>좌측 사이드바 + 프로모(좌) + 로그인 폼(우)</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>비로그인 공개 · 로그인 시 HOME 리다이렉트</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>회원 인증 · 소셜 로그인 · 가입 유도</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>Front 인증 — 01-02</dd></div>
    </dl>

    <section class="sb-front-spec">
        <h3>레이아웃 구조 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead>
                <tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결 메뉴</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>L-01</code></td>
                    <td><span class="tag tag--layout">Layout</span></td>
                    <td>아이콘 레일</td>
                    <td>홈·라벨디자인·템플릿·규격검색·쇼핑몰·맞춤서비스·자료실·고객센터 (8개 아이콘)</td>
                    <td>01 하위 전역</td>
                </tr>
                <tr>
                    <td><code>L-02</code></td>
                    <td><span class="tag tag--nav">Nav</span></td>
                    <td>사이드 네비</td>
                    <td>로고 · 홈 · 라벨 디자인 · 템플릿 · 규격 검색 · 쇼핑몰 · 맞춤 서비스 · 자료실 · 고객센터 · 마이페이지</td>
                    <td>01 전역</td>
                </tr>
                <tr>
                    <td><code>M-01</code></td>
                    <td><span class="tag tag--ui">UI</span></td>
                    <td>프로모 카피</td>
                    <td>배지 · H1 "상상한 라벨, 바로 디자인하고 출력까지" · 서비스 설명</td>
                    <td>—</td>
                </tr>
                <tr>
                    <td><code>M-02</code></td>
                    <td><span class="tag tag--ui">UI</span></td>
                    <td>히어로 비주얼</td>
                    <td>라벨 편집 화면을 연상시키는 정적 일러스트/목업 이미지 1장</td>
                    <td>—</td>
                </tr>
                <tr>
                    <td><code>M-03</code></td>
                    <td><span class="tag tag--ui">UI</span></td>
                    <td>신뢰 지표</td>
                    <td>10,000+ 사용자 · 5,000+ 템플릿 · 10,000+ 규격 데이터 · 빠른 인쇄 당일 출고</td>
                    <td>—</td>
                </tr>
                <tr>
                    <td><code>R-01</code></td>
                    <td><span class="tag tag--ui">UI</span></td>
                    <td>로그인 폼</td>
                    <td>이메일 · 비밀번호 · 로그인 유지 · 비밀번호 찾기 · 로그인 · 네이버/카카오/구글 · 회원가입</td>
                    <td>01-02</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>로그인 폼</td><td>Enter 제출 · 이메일/비밀번호 유효성 검사 · 실패 시 인라인 에러</td></tr>
                <tr><td>비밀번호</td><td>👁 토글로 표시/숨김 · 비밀번호 찾기 → 복구 페이지</td></tr>
                <tr><td>소셜 로그인</td><td>OAuth 리다이렉트 (네이버 · 카카오 · 구글) · 신규 계정 자동 연동</td></tr>
                <tr><td>회원가입</td><td>하단 링크 → 가입 페이지 (01-02 하위)</td></tr>
                <tr><td>히어로 비주얼</td><td>정적 이미지 1장 (PNG/WebP). 에디터 UI 컴포넌트 아님</td></tr>
                <tr><td>반응형</td><td>Tablet: 폼 우측 유지 · Mobile: 프로모 축소/숨김, 폼 전체 너비</td></tr>
            </tbody>
        </table>
    </section>

    <p class="sb-front-preview-label">
        <span>WIREFRAME</span> 아래는 실제 UI에 가까운 고해상도 와이어프레임입니다
    </p>

    <?php include __DIR__ . '/_fragments/storyboard-wf-wrap-shell.php'; ?>

    <p style="margin-top:12px;font-size:12px;color:#94a3b8;text-align:center">
        ✕ VISUAL = 정적 이미지 영역 · 점선 테두리 = 레이아웃 구역<br>
        <strong>전체화면 보기</strong>에서 ☰ 메뉴로 전환 (전체화면 유지) · 영역 ID 클릭 → 상세 정보
    </p>
</div>

<?php include __DIR__ . '/_fragments/storyboard-wf-runtime.js.php'; ?>
