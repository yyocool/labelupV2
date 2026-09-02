<?php
/**
 * 스토리보드: 사용자 페이지 (Front) — HOME 대시보드
 * 메뉴코드: 01
 * 화면: Front HOME (사이드바 + 대시보드 메인) 와이어프레임
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
include __DIR__ . '/_fragments/zone-data-01.php';
if (!empty($sbRenderMode) && $sbRenderMode === 'wireframe') {
    echo '<div class="sb-wf sb-wf-annotate" id="sbWfRoot">';
    echo '<style>';
    include __DIR__ . '/_fragments/wf-shared-styles.php';
    include __DIR__ . '/_fragments/01-home-styles.php';
    echo '</style>';
    include __DIR__ . '/_fragments/01-wireframe-body.php';
    include __DIR__ . '/_fragments/zone-data-script.php';
    echo '</div>';
    return;
}

$sbWfRootClass = 'sb-wf sb-wf-annotate';
$sbWfBodyFragment = __DIR__ . '/_fragments/01-wireframe-body.php';
$pageTitle = isset($menu['title']) ? $menu['title'] : '사용자 페이지 (Front)';
?>
<style>
<?php include __DIR__ . '/_fragments/wf-shared-styles.php'; ?>
<?php include __DIR__ . '/_fragments/01-home-styles.php'; ?>
</style>

<div class="sb-page sb-front-doc">

    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title">
                <?= e($pageTitle) ?> — 랜딩 (로그인 전)
            </h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e(isset($menu['menu_code']) ? $menu['menu_code'] : '01') ?></strong>
                · 사이드바 + 메인 콘텐츠 + 우측 유틸리티 3단 레이아웃
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <dl class="sb-front-meta-grid">
        <div class="sb-front-meta-card"><dt>화면명</dt><dd>Front HOME / 대시보드</dd></div>
        <div class="sb-front-meta-card"><dt>URL (예상)</dt><dd>/ 또는 /home</dd></div>
        <div class="sb-front-meta-card"><dt>레이아웃</dt><dd>좌측 사이드바(고정) + 메인 + 우측 컬럼</dd></div>
        <div class="sb-front-meta-card"><dt>접근 권한</dt><dd>비로그인 (공개 랜딩) · 로그인 후 → 01-04</dd></div>
        <div class="sb-front-meta-card"><dt>화면 목적</dt><dd>서비스 소개 · 회원가입/로그인 유도 · 템플릿 탐색</dd></div>
        <div class="sb-front-meta-card"><dt>IA 레벨</dt><dd>Front 최상위 — 하위 HOME(01-01) 진입점</dd></div>
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
                    <td>홈·라벨디자인·템플릿·규격검색·인쇄·맞춤제작·자료실·고객센터 (8개 아이콘, 현재 홈 active)</td>
                    <td>01 하위 전역</td>
                </tr>
                <tr>
                    <td><code>L-02</code></td>
                    <td><span class="tag tag--nav">Nav</span></td>
                    <td>사이드 네비 패널</td>
                    <td>로고 · 대시보드 · AI 추천 <em>NEW</em> · 최근 작업 · 즐겨찾기 · 공지 · 이벤트</td>
                    <td>01-01 (HOME)</td>
                </tr>
                <tr>
                    <td><code>L-03</code></td>
                    <td><span class="tag tag--nav">Nav</span></td>
                    <td>바로가기 (사이드)</td>
                    <td>새 디자인 · 템플릿 검색 · 규격 검색 · 주문 내역 · 이용 가이드</td>
                    <td>01-02 하위</td>
                </tr>
                <tr>
                    <td><code>M-01</code></td>
                    <td><span class="tag tag--nav">Nav</span></td>
                    <td>상단 헤더</td>
                    <td>통합 검색 · AI 추천 받기 · 알림(3) · 프로필(김라벨님 ▼)</td>
                    <td>—</td>
                </tr>
                <tr>
                    <td><code>M-02</code></td>
                    <td><span class="tag tag--ui">UI</span></td>
                    <td>히어로 (좌)</td>
                    <td>배지 · H1 "상상한 라벨, 바로 디자인하고 출력까지" · 설명 · CTA 2개 · 소셜 프루프</td>
                    <td>—</td>
                </tr>
                <tr>
                    <td><code>M-03</code></td>
                    <td><span class="tag tag--ui">UI</span></td>
                    <td>히어로 비주얼 (중)</td>
                    <td>마케팅용 정적 이미지 (에디터 UI 아님). 라벨 편집 화면을 연상시키는 일러스트/목업 이미지 1장</td>
                    <td>01-02</td>
                </tr>
                <tr>
                    <td><code>R-01</code></td>
                    <td><span class="tag tag--nav">Nav</span></td>
                    <td>바로가기 (우측)</td>
                    <td>새 디자인 · AI 생성 · 규격 · 바코드 · QR · 엑셀 연동 (6항목)</td>
                    <td>01-02 하위</td>
                </tr>
                <tr>
                    <td><code>R-02</code></td>
                    <td><span class="tag tag--cta">CTA</span></td>
                    <td>AI 프로모 카드</td>
                    <td>파일 업로드 → AI 라벨·템플릿 추천 · 로봇 캐릭터 · 지금 시작하기</td>
                    <td>01-02-05</td>
                </tr>
                <tr>
                    <td><code>M-04</code></td>
                    <td><span class="tag tag--ui">UI</span></td>
                    <td>기능 하이라이트</td>
                    <td>AI 라벨 생성 · 템플릿 · 규격 검색 · 프리미엄 라벨지 · 인쇄 서비스 (5카드)</td>
                    <td>각 섹션</td>
                </tr>
                <tr>
                    <td><code>M-05</code></td>
                    <td><span class="tag tag--ui">UI</span></td>
                    <td>추천 템플릿</td>
                    <td>제목 + 더보기 · 카테고리 필터 8종 · 가로 스크롤 템플릿 카드 6+ · 좋아요 수</td>
                    <td>01-02-02</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
                <tr><td>사이드바</td><td>Desktop: 항상 노출(아이콘 레일 + 패널). Tablet: 패널 접기. Mobile: 햄버거 드로어</td></tr>
                <tr><td>아이콘 레일</td><td>클릭 시 해당 1depth 메뉴로 이동 + 사이드 패널 메뉴 그룹 전환</td></tr>
                <tr><td>검색</td><td>Enter 또는 자동완성 드롭다운 → 통합 검색 결과</td></tr>
                <tr><td>템플릿 갤러리</td><td>필터 탭 클릭 시 AJAX 필터링. 좌우 화살표 또는 드래그 스크롤</td></tr>
                <tr><td>히어로 비주얼</td><td>정적 이미지 1장 (PNG/WebP). 에디터 UI 컴포넌트 아님. 클릭 동작 없음 (optional: 편집기 링크)</td></tr>
                <tr><td>AI 프로모</td><td>Desktop: 우측 컬럼 고정. Mobile: 하단 배너 또는 숨김</td></tr>
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
