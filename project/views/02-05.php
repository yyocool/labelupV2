<?php
/**
 * 스토리보드: 디자인 관리 (그룹 개요)
 * 메뉴코드: 02-05
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-05';
$adminTitle = '디자인 관리';
$adminSub = '템플릿·사용자 디자인 운영 허브';
$adminActive = '02-05';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '디자인 관리 개요'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/designs'),
    array('dt' => '하위 화면', 'dd' => '템플릿 관리(02-05-01) · 사용자 디자인(02-05-02)'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 디자인팀'),
    array('dt' => '화면 목적', 'dd' => '템플릿 라이브러리·사용자 디자인 검수 운영'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 디자인 관리'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '디자인 KPI', 'el' => '템플릿·무료/프리미엄·검수 대기·다운로드', 'link' => '02-05-01'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '인기 템플릿', 'el' => '사용량 상위 템플릿 랭킹', 'link' => '02-05-01'),
    array('id' => 'M-03', 'kind' => 'cta', 'block' => '검수 대기', 'el' => '공유 요청·신고 디자인 큐', 'link' => '02-05-02'),
);

$adminUx = array(
    array('item' => '검수 진입', 'desc' => '검수 대기 큐 → 사용자 디자인 검수 화면'),
    array('item' => '템플릿 관리', 'desc' => '인기 템플릿 위젯 → 템플릿 관리 목록'),
    array('item' => '사용 통계', 'desc' => '다운로드·적용 수 기간 필터'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>디자인 관리</h3><p>템플릿 라이브러리 · 사용자 디자인</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn sb-adm-btn--primary">＋ 템플릿 등록</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">총 템플릿</div><div class="val">2,140</div><div class="delta up">▲ 42</div></div>
    <div class="sb-adm-kpi"><div class="lbl">프리미엄</div><div class="val">384</div><div class="delta">유료</div></div>
    <div class="sb-adm-kpi"><div class="lbl">검수 대기</div><div class="val">18</div><div class="delta down">확인 필요</div></div>
    <div class="sb-adm-kpi"><div class="lbl">이달 다운로드</div><div class="val">54.2K</div><div class="delta up">▲ 11%</div></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2">
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>인기 템플릿 TOP</h4><span class="more">02-05-01 →</span></div>
        <ul class="sb-adm-list">
            <li><span class="rank">1</span><span class="grow">미니멀 제품 라벨</span><span class="val">8,412</span></li>
            <li><span class="rank">2</span><span class="grow">화장품 성분 라벨</span><span class="val">6,204</span></li>
            <li><span class="rank">3</span><span class="grow">식품 원산지 라벨</span><span class="val">5,180</span></li>
            <li><span class="rank">4</span><span class="grow">배송 송장 라벨</span><span class="val">4,922</span></li>
        </ul>
    </div>
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>검수 대기</h4><span class="more">02-05-02 →</span></div>
        <ul class="sb-adm-list">
            <li><span class="sb-adm-badge sb-adm-badge--amber">공유요청</span><span class="grow">홈베이킹 라벨 세트</span><span class="val">신규</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--red">신고</span><span class="grow">저작권 의심 디자인</span><span class="val">1건</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--amber">공유요청</span><span class="grow">카페 메뉴 스티커</span><span class="val">신규</span></li>
        </ul>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
