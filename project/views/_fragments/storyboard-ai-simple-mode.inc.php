<?php
/**
 * AI 라벨 디자인 — 심플 모드 스토리보드 (공통)
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 * @var string $sbStoryboardPageCode
 */
if (!isset($sbStoryboardPageCode)) {
    $sbStoryboardPageCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-02-05';
}
$pageTitle = isset($menu['title']) ? $menu['title'] : 'AI 디자인 생성';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : $sbStoryboardPageCode;

$sbFsMenuJson = array();
if (!empty($sbFsMenuTree) && isset($sbFsLinkBase)) {
    $sbFsMenuJson = StoryboardFileService::buildFsMenuTree(
        $sbFsMenuTree,
        isset($sbFsMenuId) ? $sbFsMenuId : (isset($menu['id']) ? (int) $menu['id'] : 0),
        isset($sbFsContentStatusMap) ? $sbFsContentStatusMap : array(),
        $sbFsLinkBase
    );
}
include __DIR__ . '/zone-data-01-02-05.php';

if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf--hifi sb-wf--simple sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/wf-shared-styles.php';
    include __DIR__ . '/01-02-05-hifi-styles.php';
    echo '</style>';
    include __DIR__ . '/01-02-05-hifi-wireframe-body.php';
    include __DIR__ . '/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--simple sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/01-02-05-hifi-wireframe-body.php';
?>
<style>
<?php include __DIR__ . '/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/01-02-05-hifi-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 심플 모드
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e($menuCode) ?></strong>
                · AI 라벨 디자인 › 심플 모드 · 대화형 AI 추천 + 라벨 프리뷰
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>AI 라벨 디자인 — 심플 모드</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/design/ai/simple</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>아이콘 레일 + 사이드 네비 + 상단바 + 중앙(AI 대화) + 우측(프리뷰·히스토리)</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>로그인 사용자 · AI 포인트/크레딧 소모</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>프롬프트/이미지/엑셀 입력 → AI 레이아웃·규격 추천 → 편집기 연동</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>라벨 편집기 › AI 디자인 생성 (<?= e($menuCode) ?>)</dd></div>
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
                <tr><td><code>M-01</code></td><td><span class="tag tag--nav">Nav</span></td><td>상단 바</td><td>브레드크럼 · 검색 · 알림 · 프로필</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-02</code></td><td><span class="tag tag--ui">UI</span></td><td>모드 선택</td><td>일반/심플 모드 탭 · 새 대화 · 사용 예시 · 도움말</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-03</code></td><td><span class="tag tag--ui">UI</span></td><td>AI 대화</td><td>안내 · 사용자 프롬프트 말풍선</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-04</code></td><td><span class="tag tag--ui">UI</span></td><td>AI 추천 결과</td><td>레이아웃 4종 · 규격 3종 · 디자인 미리보기</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-05</code></td><td><span class="tag tag--ui">UI</span></td><td>선택 옵션 요약</td><td>레이아웃 · 규격 · 용지 · 방향</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>M-06</code></td><td><span class="tag tag--ui">UI</span></td><td>입력 영역</td><td>이미지/엑셀/프롬프트 · textarea · 전송</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>R-01</code></td><td><span class="tag tag--ui">UI</span></td><td>작업 중인 라벨</td><td>프리뷰 · 줌 · 편집기로 보내기</td><td><?= e($menuCode) ?></td></tr>
                <tr><td><code>R-02</code></td><td><span class="tag tag--ui">UI</span></td><td>추천 근거</td><td>AI 선택 이유 4항목</td><td>—</td></tr>
                <tr><td><code>R-03</code></td><td><span class="tag tag--ui">UI</span></td><td>대화 히스토리</td><td>최근 대화 3건 · 더보기</td><td><?= e($menuCode) ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>사이드바</td><td>Desktop: 아이콘 레일(L-01) + 네비 패널(L-02) 항상 노출 · Tablet: 패널 접기 · Mobile: 햄버거 드로어</td></tr>
                <tr><td>아이콘 레일</td><td>클릭 시 해당 1depth 메뉴로 이동 + 사이드 패널 메뉴 그룹 전환</td></tr>
                <tr><td>모드 전환</td><td>일반 모드 → 단계별 입력 화면 · 심플 모드 → 대화형 AI 추천</td></tr>
                <tr><td>AI 추천 결과</td><td>레이아웃·규격·미리보기 카드 클릭 → 선택 옵션·우측 프리뷰 즉시 반영</td></tr>
                <tr><td>프롬프트 전송</td><td>➤ 또는 Enter · Ctrl+V 이미지 붙여넣기 · 엑셀 드래그앤드롭</td></tr>
                <tr><td>편집기 연동</td><td>「편집기로 보내기」→ 라벨 편집기(01-02) · 현재 시안 전달</td></tr>
                <tr><td>대화 히스토리</td><td>항목 클릭 → 이전 대화·시안 복원 · ⋯ 메뉴 → 삭제/이름변경</td></tr>
                <tr><td>반응형</td><td>Tablet: R 패널 하단 · Mobile: 1열 스택 · 입력창 하단 고정</td></tr>
            </tbody>
        </table>
    </section>

    <p class="sb-front-preview-label">
        <span>WIREFRAME</span> 아래는 실제 UI에 가까운 고해상도 와이어프레임입니다
    </p>

    <?php include __DIR__ . '/storyboard-wf-wrap-shell.php'; ?>

    <p style="margin-top:12px;font-size:12px;color:#94a3b8;text-align:center">
        <strong>전체화면 보기</strong>에서 ☰ 메뉴로 전환 · 영역 ID 클릭 → 상세 정보<br>
        📌 영역 표시로 L/M/R 구역 라벨을 켜고 끌 수 있습니다
    </p>
</div>

<?php include __DIR__ . '/storyboard-wf-runtime.js.php'; ?>
