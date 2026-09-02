<?php
/**
 * 스토리보드: 매출 통계
 * 메뉴코드: 02-01-02
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-01-02';
$adminTitle = '매출 통계';
$adminSub = '기간·채널·상품별 매출 분석';
$adminActive = '02-01';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '매출 통계 분석'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/dashboard/sales'),
    array('dt' => '집계 단위', 'dd' => '일 / 주 / 월 / 분기'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 운영자'),
    array('dt' => '화면 목적', 'dd' => '매출 추세·구성·목표 대비 성과 분석'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 대시보드 › 매출 통계'),
);

$adminZones = array(
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '분석 필터', 'el' => '기간·집계단위·채널·카테고리 선택', 'link' => '—'),
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '요약 KPI', 'el' => '총매출·객단가·환불액·순매출', 'link' => '—'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '매출 추이 차트', 'el' => '기간별 막대/라인 + 목표선', 'link' => '02-09-01'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '구성 분석', 'el' => '카테고리/채널 도넛 + 비중', 'link' => '—'),
    array('id' => 'M-04', 'kind' => 'ui', 'block' => '상세 테이블', 'el' => '일자별 매출·주문·환불·순매출', 'link' => '—'),
);

$adminUx = array(
    array('item' => '필터 연동', 'desc' => '필터 변경 시 KPI·차트·테이블 전부 재조회(AJAX)'),
    array('item' => '비교 모드', 'desc' => '전기간 대비 오버레이. 증감률 툴팁 표시'),
    array('item' => '드릴다운', 'desc' => '차트 막대 클릭 → 해당 일자 주문 목록'),
    array('item' => '내보내기', 'desc' => '엑셀/CSV 다운로드. 예약 리포트 메일 발송'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>매출 통계</h3><p>2026-06-06 ~ 2026-07-05 · 전 채널</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">↔ 비교</span><span class="sb-adm-btn sb-adm-btn--primary">⤓ 엑셀</span></div>
</div>

<div class="sb-adm-toolbar">
    <span class="sb-adm-input sel">기간: 최근 30일</span>
    <span class="sb-adm-input sel">단위: 일별</span>
    <span class="sb-adm-input sel">채널: 전체</span>
    <span class="sb-adm-input sel">카테고리: 전체</span>
    <span class="sb-adm-btn sb-adm-btn--ghost sb-adm-spacer">필터 적용</span>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">총 매출</div><div class="val">328.4M</div><div class="delta up">▲ 14.2%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">객단가</div><div class="val">₩36,720</div><div class="delta up">▲ 3.1%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">환불액</div><div class="val">8.2M</div><div class="delta down">▲ 1.5%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">순매출</div><div class="val">320.2M</div><div class="delta up">▲ 14.6%</div></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>일별 매출 추이 (목표 대비)</h4></div>
        <div class="sb-adm-chart">
            <div class="sb-adm-bar" style="height:50%"></div><div class="sb-adm-bar" style="height:62%"></div>
            <div class="sb-adm-bar" style="height:55%"></div><div class="sb-adm-bar" style="height:72%"></div>
            <div class="sb-adm-bar" style="height:60%"></div><div class="sb-adm-bar" style="height:78%"></div>
            <div class="sb-adm-bar" style="height:68%"></div><div class="sb-adm-bar" style="height:85%"></div>
            <div class="sb-adm-bar" style="height:64%"></div><div class="sb-adm-bar" style="height:90%"></div>
            <div class="sb-adm-bar" style="height:74%"></div><div class="sb-adm-bar" style="height:96%"></div>
        </div>
    </div>
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>카테고리 구성</h4></div>
        <div class="sb-adm-chart sb-adm-chart--donut" style="height:180px">
            <div class="sb-adm-donut"></div>
            <div class="sb-adm-legend" style="margin-left:14px">
                <span><i style="background:#6366f1"></i>라벨·스티커 45%</span>
                <span><i style="background:#22c55e"></i>프린터·소모품 25%</span>
                <span><i style="background:#f59e0b"></i>인쇄 의뢰 18%</span>
                <span><i style="background:#cbd5e1"></i>기타 12%</span>
            </div>
        </div>
    </div>
</div>

<div class="sb-adm-table-wrap">
    <table class="sb-adm-table">
        <thead><tr><th>일자</th><th class="num">주문 수</th><th class="num">매출</th><th class="num">환불</th><th class="num">순매출</th><th class="num">객단가</th></tr></thead>
        <tbody>
            <tr><td>2026-07-05</td><td class="num">327</td><td class="num strong">12,480,000</td><td class="num muted">-210,000</td><td class="num">12,270,000</td><td class="num">38,165</td></tr>
            <tr><td>2026-07-04</td><td class="num">298</td><td class="num strong">11,540,000</td><td class="num muted">-180,000</td><td class="num">11,360,000</td><td class="num">38,725</td></tr>
            <tr><td>2026-07-03</td><td class="num">312</td><td class="num strong">11,980,000</td><td class="num muted">-95,000</td><td class="num">11,885,000</td><td class="num">38,397</td></tr>
            <tr><td>2026-07-02</td><td class="num">276</td><td class="num strong">9,870,000</td><td class="num muted">-340,000</td><td class="num">9,530,000</td><td class="num">35,761</td></tr>
        </tbody>
    </table>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
