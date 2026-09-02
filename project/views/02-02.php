<?php
/**
 * 스토리보드: 회원관리 (그룹 개요)
 * 메뉴코드: 02-02
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-02';
$adminTitle = '회원관리';
$adminSub = '회원 현황 요약 · 회원목록 / 회원등급 진입';
$adminActive = '02-02';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '회원관리 개요'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/members'),
    array('dt' => '하위 화면', 'dd' => '회원목록(02-02-01) · 회원등급(02-02-02)'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 운영자(열람)'),
    array('dt' => '화면 목적', 'dd' => '회원 증감·등급 분포·활동 현황 파악'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 회원관리'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '회원 KPI', 'el' => '총 회원·신규·휴면·탈퇴', 'link' => '02-02-01'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '가입 추이', 'el' => '월별 신규/탈퇴 막대 차트', 'link' => '—'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '등급 분포', 'el' => '등급별 회원 수 도넛', 'link' => '02-02-02'),
    array('id' => 'M-04', 'kind' => 'ui', 'block' => '최근 가입', 'el' => '신규 회원 최근 5명', 'link' => '02-02-01'),
);

$adminUx = array(
    array('item' => '빠른 진입', 'desc' => 'KPI 카드 클릭 → 해당 조건 필터된 회원목록'),
    array('item' => '등급 관리', 'desc' => '등급 분포 위젯 → 회원등급 설정 화면'),
    array('item' => '개인정보', 'desc' => '민감정보 마스킹. 상세 열람 시 접근 로그 기록'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>회원관리</h3><p>전체 회원 현황 요약</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn sb-adm-btn--primary">회원목록 전체보기</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">총 회원</div><div class="val">48,215</div><div class="delta up">▲ 9.1%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">이달 신규</div><div class="val">3,215</div><div class="delta up">▲ 12.5%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">휴면 회원</div><div class="val">6,120</div><div class="delta down">▲ 2.1%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">이달 탈퇴</div><div class="val">184</div><div class="delta">유지율 99.6%</div></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>월별 가입 · 탈퇴</h4></div>
        <div class="sb-adm-chart">
            <div class="sb-adm-bar" style="height:55%"></div><div class="sb-adm-bar" style="height:62%"></div>
            <div class="sb-adm-bar" style="height:70%"></div><div class="sb-adm-bar" style="height:66%"></div>
            <div class="sb-adm-bar" style="height:78%"></div><div class="sb-adm-bar" style="height:88%"></div>
        </div>
    </div>
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>등급 분포</h4><span class="more">02-02-02 →</span></div>
        <div class="sb-adm-chart sb-adm-chart--donut" style="height:180px">
            <div class="sb-adm-donut"></div>
            <div class="sb-adm-legend" style="margin-left:14px">
                <span><i style="background:#6366f1"></i>일반 62%</span>
                <span><i style="background:#22c55e"></i>실버 24%</span>
                <span><i style="background:#f59e0b"></i>골드 11%</span>
                <span><i style="background:#cbd5e1"></i>VIP 3%</span>
            </div>
        </div>
    </div>
</div>

<div class="sb-adm-panel">
    <div class="sb-adm-panel-head"><h4>최근 가입 회원</h4><span class="more">회원목록 →</span></div>
    <ul class="sb-adm-list">
        <li><span class="sb-adm-avatar-name"><span class="av">김</span><span>김라벨<br><small>hi***@gmail.com</small></span></span><span class="grow"></span><span class="sb-adm-badge sb-adm-badge--gray">일반</span></li>
        <li><span class="sb-adm-avatar-name"><span class="av">이</span><span>이스티커<br><small>st***@naver.com</small></span></span><span class="grow"></span><span class="sb-adm-badge sb-adm-badge--gray">일반</span></li>
        <li><span class="sb-adm-avatar-name"><span class="av">박</span><span>박프린트<br><small>pr***@daum.net</small></span></span><span class="grow"></span><span class="sb-adm-badge sb-adm-badge--green">실버</span></li>
    </ul>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
