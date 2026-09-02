<?php
/**
 * 스토리보드: Backoffice (관리자) — 통합 홈
 * 메뉴코드: 02
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $breadcrumb
 */
$adminCode = '02';
$adminTitle = 'Backoffice';
$adminSub = '관리자 콘솔 통합 홈 · 회원/매출/쇼핑몰 운영 현황 대시보드';
$adminActive = '02-01';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => 'Backoffice 통합 홈'),
    array('dt' => 'URL (예상)', 'dd' => '/admin'),
    array('dt' => '레이아웃', 'dd' => '좌측 LNB + 상단바 + 대시보드 콘텐츠'),
    array('dt' => '접근 권한', 'dd' => '관리자(admin) · 운영자(staff) 롤'),
    array('dt' => '화면 목적', 'dd' => '전사 운영 지표 요약 · 각 관리 모듈 진입'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice 최상위 (02)'),
);

$adminZones = array(
    array('id' => 'L-01', 'kind' => 'nav', 'block' => '관리자 LNB', 'el' => '대시보드·회원·쇼핑몰·주문·디자인·규격·AI·콘텐츠·통계 (9개 모듈)', 'link' => '02 전역'),
    array('id' => 'T-01', 'kind' => 'nav', 'block' => '상단바', 'el' => '브레드크럼 · 통합검색 · 알림 · 관리자 프로필', 'link' => '—'),
    array('id' => 'M-01', 'kind' => 'ui', 'block' => 'KPI 요약', 'el' => '오늘 매출 · 신규 회원 · 신규 주문 · 처리 대기', 'link' => '02-01'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '매출 추이 차트', 'el' => '최근 14일 일별 매출 막대 그래프', 'link' => '02-09-01'),
    array('id' => 'M-03', 'kind' => 'nav', 'block' => '모듈 바로가기', 'el' => '회원/상품/주문/템플릿/AI/정산 카드', 'link' => '각 모듈'),
    array('id' => 'M-04', 'kind' => 'ui', 'block' => '처리 대기 큐', 'el' => '신규주문·환불요청·문의·디자인 의뢰 카운트', 'link' => '02-04'),
);

$adminUx = array(
    array('item' => '권한 제어', 'desc' => '롤(admin/staff)별 메뉴 노출 제한. 미허용 모듈은 LNB에서 숨김'),
    array('item' => 'KPI 카드', 'desc' => '전일 대비 증감률 표시. 클릭 시 해당 통계 상세로 이동'),
    array('item' => '처리 대기', 'desc' => '실시간 카운트(폴링/웹소켓). 배지 클릭 → 필터된 목록'),
    array('item' => '반응형', 'desc' => 'Desktop 우선. Tablet 이하 LNB 접힘(아이콘 전용)'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>운영 대시보드</h3><p>2026-07-05 (일) 기준 · 실시간 갱신</p></div>
    <div class="sb-adm-head-actions">
        <span class="sb-adm-btn">📅 오늘</span>
        <span class="sb-adm-btn sb-adm-btn--primary">＋ 리포트 내보내기</span>
    </div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl-row"><span class="lbl">오늘 매출</span><span class="ic-badge">₩</span></div><div class="val">12,480,000</div><div class="delta up">▲ 8.2% 전일</div></div>
    <div class="sb-adm-kpi"><div class="lbl-row"><span class="lbl">신규 회원</span><span class="ic-badge">◕</span></div><div class="val">184</div><div class="delta up">▲ 12.5%</div></div>
    <div class="sb-adm-kpi"><div class="lbl-row"><span class="lbl">신규 주문</span><span class="ic-badge">▦</span></div><div class="val">327</div><div class="delta down">▼ 3.1%</div></div>
    <div class="sb-adm-kpi"><div class="lbl-row"><span class="lbl">처리 대기</span><span class="ic-badge">⏳</span></div><div class="val">42</div><div class="delta">주문·환불·문의</div></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>최근 14일 매출 추이</h4><span class="more">통계·정산 →</span></div>
        <div class="sb-adm-chart">
            <div class="sb-adm-bar" style="height:45%"></div><div class="sb-adm-bar" style="height:60%"></div>
            <div class="sb-adm-bar" style="height:52%"></div><div class="sb-adm-bar" style="height:70%"></div>
            <div class="sb-adm-bar" style="height:48%"></div><div class="sb-adm-bar" style="height:80%"></div>
            <div class="sb-adm-bar" style="height:65%"></div><div class="sb-adm-bar" style="height:75%"></div>
            <div class="sb-adm-bar" style="height:58%"></div><div class="sb-adm-bar" style="height:88%"></div>
            <div class="sb-adm-bar" style="height:72%"></div><div class="sb-adm-bar" style="height:95%"></div>
            <div class="sb-adm-bar" style="height:68%"></div><div class="sb-adm-bar" style="height:82%"></div>
        </div>
    </div>
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>처리 대기 큐</h4></div>
        <ul class="sb-adm-list">
            <li><span class="sb-adm-badge sb-adm-badge--amber">주문</span><span class="grow">결제완료·배송준비</span><span class="val">18</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--red">환불</span><span class="grow">환불·취소 요청</span><span class="val">7</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--blue">문의</span><span class="grow">미답변 1:1 문의</span><span class="val">11</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--purple">의뢰</span><span class="grow">맞춤 디자인 의뢰</span><span class="val">6</span></li>
        </ul>
    </div>
</div>

<div class="sb-adm-panel">
    <div class="sb-adm-panel-head"><h4>관리 모듈 바로가기</h4></div>
    <div class="sb-adm-grid sb-adm-grid--3" style="margin-bottom:0">
        <span class="sb-adm-btn sb-adm-btn--ghost">◕ 회원관리</span>
        <span class="sb-adm-btn sb-adm-btn--ghost">▣ 상품관리</span>
        <span class="sb-adm-btn sb-adm-btn--ghost">▦ 주문관리</span>
        <span class="sb-adm-btn sb-adm-btn--ghost">◈ 템플릿 관리</span>
        <span class="sb-adm-btn sb-adm-btn--ghost">✦ AI 관리</span>
        <span class="sb-adm-btn sb-adm-btn--ghost">∿ 통계·정산</span>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
