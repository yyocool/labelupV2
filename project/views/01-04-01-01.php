<?php
/**
 * 스토리보드: AI 일반 모드
 * 메뉴코드: 01-04-01-01
 * 화면: AI 라벨 디자인 — 일반 모드 와이어프레임
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
include __DIR__ . '/_fragments/zone-data-01-04-01-01.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--normal sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-04-01-01-normal-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-04-01-01-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--normal sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-04-01-01-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : 'AI 일반 모드';
?>
<style>
<?php include __DIR__ . '/_fragments/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-04-01-01-normal-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 일반 모드 와이어프레임
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e(isset($menu['menu_code']) ? $menu['menu_code'] : '01-04-01-01') ?></strong>
                · AI 라벨 디자인 › 일반 모드 · 단계별 입력 + 실시간 미리보기
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>AI 라벨 디자인 — 일반 모드</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/design/ai/normal</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>사이드바 + 상단바 + 4단계 스텝퍼 + 폼·미리보기·히스토리 3열</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>로그인 사용자 · AI 포인트/크레딧 소모</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>라벨 정보·톤 단계별 입력 → AI 맞춤 디자인 생성</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>01-04-01 (AI 라벨 디자인) › 01-04-01-01</dd></div>
    </dl>

    <section class="sb-front-spec">
        <h3>레이아웃 구조 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead>
                <tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결 메뉴</th></tr>
            </thead>
            <tbody>
                <tr><td><code>L-01</code></td><td><span class="tag tag--layout">Layout</span></td><td>아이콘 레일</td><td>8개 · 라벨디자인 active</td><td>01 전역</td></tr>
                <tr><td><code>L-02</code></td><td><span class="tag tag--nav">Nav</span></td><td>사이드 네비</td><td>로고 · 홈~고객센터</td><td>01 전역</td></tr>
                <tr><td><code>L-03</code></td><td><span class="tag tag--ui">UI</span></td><td>보유 포인트</td><td>12,450 P · 충전/내역 보기</td><td>01-04</td></tr>
                <tr><td><code>M-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>상단 바</td><td>브레드크럼 › 일반 모드 · 검색 · 알림 · 프로필</td><td>01-04-01</td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--ui">UI</span></td><td>페이지 헤더</td><td>일반 모드 · "단계별로 꼼꼼하게" 배지 · 설명</td><td>01-04-01-01</td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--ui">UI</span></td><td>단계 스텝퍼</td><td>라벨 정보 입력(active) · 디자인 설정 · 미리보기 · 다운로드/인쇄</td><td>01-04-01-01</td></tr>
                <tr><td><code>M-04</code></td><td><span class="tag tag--ui">UI</span></td><td>Step1 — 라벨 정보</td><td>용도 · 규격 · 텍스트 · 소재 · 색상 · 추가정보 · 참고이미지</td><td>01-04-01-01</td></tr>
                <tr><td><code>M-05</code></td><td><span class="tag tag--ui">UI</span></td><td>실시간 미리보기</td><td>라벨 프리뷰 · 초기화 · 줌 ± · 가이드 보기</td><td>01-04-01-01</td></tr>
                <tr><td><code>M-06</code></td><td><span class="tag tag--ui">UI</span></td><td>Step1 — 디자인 톤</td><td>모던/심플 · 내추럴 · 빈티지 · 고급 · 귀여운 · 강렬/팝 (6카드)</td><td>01-04-01-01</td></tr>
                <tr><td><code>M-07</code></td><td><span class="tag tag--ui">UI</span></td><td>다음 단계 CTA</td><td>"다음 단계: 디자인 설정 →" 버튼</td><td>01-04-01-01</td></tr>
                <tr><td><code>R-01</code></td><td><span class="tag tag--ui">UI</span></td><td>최근 히스토리</td><td>썸네일·제목·규격·재질·날짜 · 전체 보기</td><td>01-04</td></tr>
                <tr><td><code>R-02</code></td><td><span class="tag tag--ui">UI</span></td><td>AI 디자인 팁</td><td>정확한 정보 · 참고 이미지 · 톤 선택 · 미리보기 확인</td><td>—</td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>규격 검색</td><td>버튼 클릭 → 규격 검색 모달(01-04-03) · 선택 시 규격 필드 자동 입력</td></tr>
                <tr><td>실시간 미리보기</td><td>폼 입력 변경 시 M-05에 즉시 반영 · 초기화 → 기본값 복원</td></tr>
                <tr><td>디자인 톤</td><td>6카드 단일 선택 · 선택 톤이 미리보기·AI 생성에 반영</td></tr>
                <tr><td>참고 이미지</td><td>드래그앤드롭 · JPG/PNG · 10MB 제한 · AI 스타일 참조</td></tr>
                <tr><td>다음 단계</td><td>필수 항목(용도·규격·텍스트·톤) 완료 시 활성 · Step 2(디자인 설정) 이동</td></tr>
                <tr><td>스텝퍼</td><td>완료된 단계 클릭 시 해당 단계로 이동 · 미완료 단계는 비활성</td></tr>
                <tr><td>반응형</td><td>Tablet: 미리보기 하단 · Mobile: 1열 스택 · 스텝퍼 축소</td></tr>
            </tbody>
        </table>
    </section>

    <p class="sb-front-preview-label">
        <span>WIREFRAME</span> 아래는 실제 UI에 가까운 고해상도 와이어프레임입니다
    </p>

    <?php include __DIR__ . '/_fragments/storyboard-wf-wrap-shell.php'; ?>

    <p style="margin-top:12px;font-size:12px;color:#94a3b8;text-align:center">
        ✕ VISUAL / ✕ THUMB / ✕ PREVIEW = 정적·썸네일·프리뷰 영역 · 점선 테두리 = 레이아웃 구역<br>
        <strong>전체화면 보기</strong>에서 ☰ 메뉴로 전환 (전체화면 유지) · 영역 ID 클릭 → 상세 정보
    </p>
</div>

<?php include __DIR__ . '/_fragments/storyboard-wf-runtime.js.php'; ?>
