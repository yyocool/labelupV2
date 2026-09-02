<?php
/**
 * 스토리보드: FAQ
 * 메뉴코드: 02-08-02
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-08-02';
$adminTitle = 'FAQ';
$adminSub = '자주 묻는 질문 분류·순서·노출 관리';
$adminActive = '02-08';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => 'FAQ 관리'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/content/faq'),
    array('dt' => '연관 테이블', 'dd' => 'faqs · faq_categories'),
    array('dt' => '접근 권한', 'dd' => '관리자 · CS'),
    array('dt' => '화면 목적', 'dd' => 'FAQ 항목 CRUD·분류·정렬·도움 통계'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 콘텐츠 관리 › FAQ'),
);

$adminZones = array(
    array('id' => 'L-01', 'kind' => 'nav', 'block' => '분류 사이드', 'el' => '카테고리 목록·항목 수·순서', 'link' => '—'),
    array('id' => 'M-01', 'kind' => 'ui', 'block' => 'FAQ 테이블', 'el' => '질문·분류·순서·노출·도움됨/조회', 'link' => '01-09-02'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '작성/수정', 'el' => '질문·답변(에디터)·분류·노출·관련 링크', 'link' => '—'),
);

$adminUx = array(
    array('item' => '분류/정렬', 'desc' => '카테고리별 그룹 + 드래그 정렬. 노출/숨김 토글'),
    array('item' => '도움 통계', 'desc' => '"도움됨/안됨" 집계로 낮은 항목 개선 유도'),
    array('item' => '연관', 'desc' => '관련 FAQ·공지·상품 링크 연결'),
    array('item' => '검색 노출', 'desc' => '검색 키워드 태그로 프론트 검색 매칭 향상'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>FAQ</h3><p>총 86개 · 12개 분류</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">분류 관리</span><span class="sb-adm-btn sb-adm-btn--primary">＋ FAQ 추가</span></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-table-wrap">
        <table class="sb-adm-table">
            <thead><tr><th>순서</th><th>질문</th><th>분류</th><th class="num">조회</th><th class="num">도움됨</th><th>상태</th></tr></thead>
            <tbody>
                <tr><td>⋮⋮ 1</td><td class="strong">주문 취소는 언제까지 가능한가요?</td><td>주문/결제</td><td class="num">8,204</td><td class="num">92%</td><td><span class="sb-adm-badge sb-adm-badge--green">노출</span></td></tr>
                <tr><td>⋮⋮ 2</td><td class="strong">배송은 며칠 걸리나요?</td><td>배송/반품</td><td class="num">7,110</td><td class="num">88%</td><td><span class="sb-adm-badge sb-adm-badge--green">노출</span></td></tr>
                <tr><td>⋮⋮ 3</td><td class="strong">규격에 맞는 라벨지 찾는 법</td><td>디자인</td><td class="num">5,320</td><td class="num">78%</td><td><span class="sb-adm-badge sb-adm-badge--green">노출</span></td></tr>
                <tr><td>⋮⋮ 4</td><td>AI 생성 횟수 제한이 있나요?</td><td>AI</td><td class="num">2,041</td><td class="num">61%</td><td><span class="sb-adm-badge sb-adm-badge--gray">숨김</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>분류</h4></div>
        <ul class="sb-adm-list">
            <li><span class="grow">주문/결제</span><span class="sb-adm-badge sb-adm-badge--gray">24</span></li>
            <li><span class="grow">배송/반품</span><span class="sb-adm-badge sb-adm-badge--gray">18</span></li>
            <li><span class="grow">디자인/편집</span><span class="sb-adm-badge sb-adm-badge--gray">16</span></li>
            <li><span class="grow">AI 생성</span><span class="sb-adm-badge sb-adm-badge--gray">12</span></li>
            <li><span class="grow">회원/포인트</span><span class="sb-adm-badge sb-adm-badge--gray">10</span></li>
            <li><span class="grow">기타</span><span class="sb-adm-badge sb-adm-badge--gray">6</span></li>
        </ul>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
