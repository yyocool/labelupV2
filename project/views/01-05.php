<?php
/**
 * 스토리보드: 디자인 편집
 * 메뉴코드: 01-05
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
include __DIR__ . '/_fragments/zone-data-01-05.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--hifi sb-wf--editor sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-05-hifi-styles.php';
    include __DIR__ . '/_fragments/01-05-layer-popups-styles.php';
    include __DIR__ . '/_fragments/01-05-import-popup-styles.php';
    include __DIR__ . '/_fragments/01-05-floating-tools-styles.php';
    include __DIR__ . '/_fragments/01-05-asset-slide-panel-styles.php';
    include __DIR__ . '/_fragments/01-05-spec-detail-popup-styles.php';
    include __DIR__ . '/_fragments/01-05-data-import-popup-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-05-hifi-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--editor sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-05-hifi-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : '디자인 편집';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05';
?>
<style>
<?php include __DIR__ . '/_fragments/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-05-hifi-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-05-layer-popups-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-05-import-popup-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-05-floating-tools-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-05-asset-slide-panel-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-05-spec-detail-popup-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-05-data-import-popup-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 라벨 편집기
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e($menuCode) ?></strong>
                · 캔버스 편집 · 템플릿 패널 · 속성 · 인쇄 미리보기 모달
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn sb-front-btn--proto" id="sbEdStartPrototype">프로토타입</button>
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>라벨 디자인 편집기</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/editor/design/{id}</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>도구 레일 + 캔버스 + 속성 패널 · 템플릿 레이어 팝업</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>로그인 사용자 · 내 디자인 또는 새 디자인</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>라벨 시안 편집 · 템플릿 적용 · 인쇄용 시트 미리보기</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>라벨 편집기 › 편집 도구 › 디자인 편집 (<?= e($menuCode) ?>)</dd></div>
    </dl>

    <section class="sb-front-spec">
        <h3>레이아웃 구조 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead>
                <tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결 메뉴</th></tr>
            </thead>
            <tbody>
                <tr><td><code>L-01</code></td><td><span class="tag tag--layout">Layout</span></td><td>도구 레일</td><td>편집 도구 · 라벨·태그·템플릿·나의디자인·AI · 도움말</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>L-02</code></td><td><span class="tag tag--ui">UI</span></td><td>레이어 팝업</td><td>라벨·태그·템플릿·나의디자인·AI (Q-01~Q-05)</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>Q-01</code></td><td><span class="tag tag--ui">UI</span></td><td>라벨 선택</td><td>A4/제브라 탭 · 규격 그리드 · 검색</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>Q-03</code></td><td><span class="tag tag--ui">UI</span></td><td>템플릿 갤러리</td><td>해시태그 · 키워드 검색 · 썸네일</td><td>01-02-02</td></tr>
                <tr><td><code>Q-05</code></td><td><span class="tag tag--ui">UI</span></td><td>AI 생성</td><td>프롬프트 · 이미지/엑셀/예시/파일</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>상단 바</td><td>파일명 · 저장 · 실행취소 · 줌 · 미리보기 · 출력</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--ui">UI</span></td><td>캔버스</td><td>눈금자 · 아트보드 · 선택 영역</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--ui">UI</span></td><td>플로팅 툴바</td><td>텍스트·이미지·배경·템플릿·클립아트·아이콘 · 드래그·스냅</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>S-00</code></td><td><span class="tag tag--ui">UI</span></td><td>에셋 슬라이드</td><td>배경·템플릿·클립아트·아이콘 검색·태그·그리드</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>R-01</code></td><td><span class="tag tag--ui">UI</span></td><td>속성 패널</td><td>속성/레이어 탭 · 사이즈 · 텍스트 · 채우기 · 테두리 · 드래그·접기</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>R-02</code></td><td><span class="tag tag--ui">UI</span></td><td>미리보기 패널</td><td>인쇄 시트 미니 프리뷰 · 줌 · 전체 미리보기 · 드래그·접기</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>P-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>프리뷰 상단</td><td>편집/미리보기 토글 · 편집기에서 계속 · 닫기</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>P-02</code></td><td><span class="tag tag--ui">UI</span></td><td>용지 설정</td><td>A4 · 3×4 · 여백 · 간격 · 재단선</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>P-03</code></td><td><span class="tag tag--ui">UI</span></td><td>인쇄 미리보기</td><td>페이지 네비 · 3×4 라벨 그리드</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>P-04</code></td><td><span class="tag tag--ui">UI</span></td><td>프리뷰 우측</td><td>레이어 · 프리뷰 옵션 · 인쇄 도움말</td><td><?= e($menuCode) ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>레이어 팝업</td><td>L-01 하단 <strong>라벨·태그·템플릿·나의디자인·AI</strong> 클릭 → Q-01~Q-05 큰 레이어 · X·ESC·배경 클릭 닫기 · 재클릭 토글</td></tr>
                <tr><td>미리보기</td><td>R-02 패널에서 인쇄 시트 미니 프리뷰 · 👁 전체 → P-01~P-04 인쇄 프리뷰 모달 · 배경 클릭·X·ESC로 닫기</td></tr>
                <tr><td>캔버스</td><td>객체 선택 → R-01 속성 패널 연동 · 드래그·리사이즈 · 더블클릭 텍스트 편집</td></tr>
                <tr><td>출력</td><td>「편집기에서 출력」→ 인쇄 주문/다운로드 플로우</td></tr>
                <tr><td>단축키</td><td>Ctrl+Z/Y 실행취소 · Ctrl+S 저장 · Space+드래그 패닝</td></tr>
                <tr><td>와이어프레임</td><td>미리보기 중앙 <strong>「프로토타입」</strong> 클릭 → 모든 버튼·메뉴·팝업 동작 시뮬레이션 (DB 미연동)</td></tr>
            </tbody>
        </table>
    </section>

    <p class="sb-front-preview-label">
        <span>WIREFRAME</span> 아래 와이어프레임 중앙 <strong>「프로토타입」</strong>을 누르면 모든 버튼·레이어 팝업·미리보기가 동작합니다
    </p>

    <?php include __DIR__ . '/_fragments/storyboard-wf-wrap-shell.php'; ?>

    <p style="margin-top:12px;font-size:12px;color:#94a3b8;text-align:center">
        <strong>프로토타입</strong>으로 레일·레이어·미리보기·속성 패널 전체 인터랙션 확인 · <strong>전체화면 보기</strong>에서도 동일<br>
        📌 영역 표시로 L/M/P/R 구역 라벨을 켜고 끌 수 있습니다
    </p>
</div>

<?php include __DIR__ . '/_fragments/storyboard-wf-runtime.js.php'; ?>
