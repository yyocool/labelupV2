<?php
/**
 * 스토리보드: 쇼핑몰 관리 (그룹 개요)
 * 메뉴코드: 02-03
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-03';
$adminTitle = '쇼핑몰 관리';
$adminSub = '상품·카테고리·재고 운영 허브';
$adminActive = '02-03';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '쇼핑몰 관리 개요'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/shop'),
    array('dt' => '하위 화면', 'dd' => '상품관리(02-03-01) · 카테고리(02-03-02) · 재고(02-03-03)'),
    array('dt' => '접근 권한', 'dd' => '관리자 · MD(운영자)'),
    array('dt' => '화면 목적', 'dd' => '판매 상품·재고·카테고리 통합 운영'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 쇼핑몰 관리'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '쇼핑몰 KPI', 'el' => '판매 상품·품절·재고부족·신규 등록', 'link' => '02-03-01'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '판매 TOP', 'el' => '판매량 상위 상품 랭킹', 'link' => '02-03-01'),
    array('id' => 'M-03', 'kind' => 'cta', 'block' => '재고 경고', 'el' => '재고 임계 이하 상품 목록', 'link' => '02-03-03'),
    array('id' => 'M-04', 'kind' => 'nav', 'block' => '바로가기', 'el' => '상품 등록·카테고리·재고 입출고', 'link' => '각 화면'),
);

$adminUx = array(
    array('item' => '재고 경고', 'desc' => '임계 이하 상품 강조. 클릭 → 재고관리로 이동'),
    array('item' => '빠른 등록', 'desc' => '상품 등록 CTA → 등록 폼(모달/페이지)'),
    array('item' => '판매 추이', 'desc' => '기간 필터로 판매/매출 위젯 갱신'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>쇼핑몰 관리</h3><p>판매 상품 · 재고 요약</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">▧ 카테고리</span><span class="sb-adm-btn sb-adm-btn--primary">＋ 상품 등록</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">판매 중 상품</div><div class="val">1,284</div><div class="delta up">▲ 24개</div></div>
    <div class="sb-adm-kpi"><div class="lbl">품절 상품</div><div class="val">37</div><div class="delta down">확인 필요</div></div>
    <div class="sb-adm-kpi"><div class="lbl">재고 부족</div><div class="val">52</div><div class="delta down">임계 이하</div></div>
    <div class="sb-adm-kpi"><div class="lbl">이달 신규 등록</div><div class="val">86</div><div class="delta up">▲ 18%</div></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2">
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>판매 TOP 5</h4><span class="more">02-03-01 →</span></div>
        <ul class="sb-adm-list">
            <li><span class="rank">1</span><span class="grow">방수 유광 라벨 A4 24칸</span><span class="val">1,284</span></li>
            <li><span class="rank">2</span><span class="grow">투명 PET 원형 라벨 40mm</span><span class="val">982</span></li>
            <li><span class="rank">3</span><span class="grow">감열 배송 라벨 100×150</span><span class="val">871</span></li>
            <li><span class="rank">4</span><span class="grow">무광 크라프트 사각 라벨</span><span class="val">654</span></li>
            <li><span class="rank">5</span><span class="grow">바코드 프린터 소모품 세트</span><span class="val">512</span></li>
        </ul>
    </div>
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>재고 경고</h4><span class="more">02-03-03 →</span></div>
        <ul class="sb-adm-list">
            <li><span class="sb-adm-badge sb-adm-badge--red">품절</span><span class="grow">방수 라벨 A4 40칸</span><span class="val">0</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--amber">부족</span><span class="grow">감열 라벨 100×150</span><span class="val">12</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--amber">부족</span><span class="grow">프린터 리본 65mm</span><span class="val">8</span></li>
        </ul>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
