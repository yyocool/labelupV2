<?php
/**
 * 스토리보드: 서비스 소개
 * 메뉴코드: 01-04-02
 * 화면: HOME › 서비스 소개 — 특징 · 사용방법 · 요금 · 기업 안내
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
include __DIR__ . '/_fragments/zone-data-01-04-02.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--hifi sb-wf--about sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-04-02-hifi-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-04-02-hifi-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--about sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-04-02-hifi-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : '서비스 소개';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-04-02';
?>
<style>
<?php include __DIR__ . '/_fragments/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-04-02-hifi-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 서비스 소개
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e($menuCode) ?></strong>
                · HOME › 서비스 소개 · 특징 · 사용 방법 · 요금 · 기업 고객
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>서비스 소개</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/about · /service</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>전역 사이드 + 히어로 + 앵커 섹션 + 요금/기업 CTA</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>공개 (비로그인 가능) · CTA로 가입/로그인 유도</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>서비스 가치 전달 · 플랜 안내 · 기업·가입 전환</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>Front › HOME › 서비스 소개 (<?= e($menuCode) ?>)</dd></div>
    </dl>

    <section class="sb-front-spec">
        <h3>레이아웃 구조 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead>
                <tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결 메뉴</th></tr>
            </thead>
            <tbody>
                <tr><td><code>L-01</code></td><td><span class="tag tag--layout">Layout</span></td><td>아이콘 레일</td><td>8개 · HOME active</td><td>01 전역</td></tr>
                <tr><td><code>L-02</code></td><td><span class="tag tag--nav">Nav</span></td><td>사이드 네비</td><td>메인 · 서비스 소개(active) · 하위 4항목</td><td>01-04</td></tr>
                <tr><td><code>M-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>상단 바</td><td>브레드크럼 · 로그인 · 무료로 시작</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--cta">CTA</span></td><td>히어로</td><td>카피 · 시작하기 · 사용 방법 보기</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--nav">Nav</span></td><td>섹션 앵커</td><td>특징 · 사용방법 · 요금 · 기업</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-04</code></td><td><span class="tag tag--ui">UI</span></td><td>서비스 특징</td><td>카드 5종 (편집·AI·템플릿·쇼핑·인쇄)</td><td>01-04-02-01</td></tr>
                <tr><td><code>M-05</code></td><td><span class="tag tag--ui">UI</span></td><td>사용 방법</td><td>4단계 스텝</td><td>01-04-02-02</td></tr>
                <tr><td><code>M-06</code></td><td><span class="tag tag--ui">UI</span></td><td>요금 안내</td><td>Free / Pro 비교 카드</td><td>01-04-02-03 · 01-06</td></tr>
                <tr><td><code>M-07</code></td><td><span class="tag tag--cta">CTA</span></td><td>기업 고객</td><td>B2B 카피 · 문의하기</td><td>01-04-02-04 · 01-07</td></tr>
                <tr><td><code>M-08</code></td><td><span class="tag tag--cta">CTA</span></td><td>하단 CTA</td><td>무료 시작 · 템플릿 둘러보기</td><td>01-01 · 템플릿</td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>진입</td><td>HOME 사이드「서비스 소개」· 랜딩 푸터 · 요금/기업 배너</td></tr>
                <tr><td>앵커</td><td>상단 칩 클릭 시 해당 섹션으로 스크롤 · 하위 상세 메뉴와 대응</td></tr>
                <tr><td>히어로 CTA</td><td>「지금 시작하기」→ 회원가입/편집기 · 「사용 방법 보기」→ M-05</td></tr>
                <tr><td>요금</td><td>Free/Pro 요약 · 「Pro 시작하기」→ 결제/구독(01-06) · 상세는 요금 안내</td></tr>
                <tr><td>기업</td><td>「문의하기」→ 고객센터 문의 · 「자세히」→ 기업 고객 안내</td></tr>
                <tr><td>반응형</td><td>Tablet: 특징 3열 · Mobile: 1열 스택 · 사이드 패널 숨김</td></tr>
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
