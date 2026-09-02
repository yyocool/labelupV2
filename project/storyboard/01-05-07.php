<?php
/**
 * 스토리보드: Label-UP 도움말
 * 메뉴코드: 01-05-07
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
include __DIR__ . '/_fragments/zone-data-01-05-07.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--hifi sb-wf--help sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-05-07-hifi-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-05-07-hifi-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--help sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-05-07-hifi-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : 'Label-UP 도움말';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-07';
?>
<style>
<?php include __DIR__ . '/_fragments/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-05-07-hifi-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 도움말 센터
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e($menuCode) ?></strong>
                · 편집기/? 버튼 · 검색 · 카테고리 · 단축키 · 고객센터 연계
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>Label-UP 도움말</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/help · /editor/help</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>전역 사이드 + 도움말 헤더/탭 + 본문 + 우측 빠른링크</dd></div>
        <div class="sb-front-meta-card"><dt>접근</dt><dd>편집기 ? / F1 · 사이드 고객센터</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>편집·AI·출력 가이드와 단축키를 제공하고 문의로 연결</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>라벨 편집기 › 도움말 (<?= e($menuCode) ?>)</dd></div>
    </dl>

    <section class="sb-front-spec">
        <h3>레이아웃 구조 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead>
                <tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결 메뉴</th></tr>
            </thead>
            <tbody>
                <tr><td><code>L-01</code></td><td><span class="tag tag--layout">Layout</span></td><td>아이콘 레일</td><td>전역 8개 · 라벨디자인 active</td><td>01 전역</td></tr>
                <tr><td><code>L-02</code></td><td><span class="tag tag--nav">Nav</span></td><td>사이드 네비</td><td>로고 · 메뉴</td><td>01 전역</td></tr>
                <tr><td><code>M-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>도움말 헤더</td><td>제목 · 검색 · 닫기(✕)</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--nav">Nav</span></td><td>카테고리 탭</td><td>시작하기 · 편집 · AI · 출력 · 단축키 · FAQ</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--ui">UI</span></td><td>본문 가이드</td><td>카드 3종 · 관련 링크</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-04</code></td><td><span class="tag tag--ui">UI</span></td><td>단축키 표</td><td>저장·실행취소·복제·미리보기·Esc</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>R-01</code></td><td><span class="tag tag--ui">UI</span></td><td>빠른 링크</td><td>편집기 · AI · 규격 · 템플릿</td><td>01-05 / 01-05-05</td></tr>
                <tr><td><code>R-02</code></td><td><span class="tag tag--cta">CTA</span></td><td>문의 배너</td><td>1:1 문의 · FAQ</td><td>01-07</td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>진입</td><td>편집기 하단 ? · F1 · 사이드「도움말」· 고객센터 링크</td></tr>
                <tr><td>검색</td><td>키워드 입력 시 가이드·FAQ 필터 (프로토타입: UI만)</td></tr>
                <tr><td>탭</td><td>카테고리 전환 · URL 해시로 딥링크 가능</td></tr>
                <tr><td>닫기</td><td>✕ 또는 Esc → 이전 편집기 화면 복귀 (상태 유지)</td></tr>
                <tr><td>연계</td><td>AI 디자인(01-05-05) · 고객센터(01-07) · 원격지원</td></tr>
                <tr><td>반응형</td><td>Tablet: 우측 패널 하단 · Mobile: 1열 · 탭 가로 스크롤</td></tr>
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
