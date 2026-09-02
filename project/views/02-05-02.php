<?php
/**
 * 스토리보드: 사용자 디자인
 * 메뉴코드: 02-05-02
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-05-02';
$adminTitle = '사용자 디자인';
$adminSub = '사용자 제작 디자인 검수·공유 승인·신고 처리';
$adminActive = '02-05';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '사용자 디자인 검수'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/designs/user-designs'),
    array('dt' => '연관 테이블', 'dd' => 'user_designs · design_reports · share_requests'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 디자인팀(검수)'),
    array('dt' => '화면 목적', 'dd' => '공유 요청 승인·신고 검토·저작권 위반 처리'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 디자인 관리 › 사용자 디자인'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '검수 KPI', 'el' => '전체·공유대기·신고접수·승인완료', 'link' => '—'),
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '필터', 'el' => '유형(공유/신고)·상태·기간·작성자', 'link' => '02-02-01'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '디자인 테이블', 'el' => '썸네일·제목·작성자·유형·상태·요청일', 'link' => '—'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '검수 패널', 'el' => '미리보기·메타·검수 의견·승인/반려', 'link' => '02-05-01'),
);

$adminUx = array(
    array('item' => '공유 승인', 'desc' => '승인 시 공유 템플릿(01-05-02-03)에 노출. 작성자 크레딧 표기'),
    array('item' => '신고 처리', 'desc' => '저작권/부적절 신고 검토 → 비공개/삭제·작성자 통보'),
    array('item' => '미리보기', 'desc' => '실제 캔버스 렌더 미리보기. 규격·요소 정보 표시'),
    array('item' => '반려 사유', 'desc' => '반려 시 사유 템플릿 선택 또는 직접 입력. 자동 알림'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>사용자 디자인 검수</h3><p>공유 대기 15 · 신고 3</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn sb-adm-btn--primary">선택 승인</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">전체 요청</div><div class="val">1,204</div><div class="delta">누적</div></div>
    <div class="sb-adm-kpi"><div class="lbl">공유 대기</div><div class="val">15</div><div class="delta down">검수 필요</div></div>
    <div class="sb-adm-kpi"><div class="lbl">신고 접수</div><div class="val">3</div><div class="delta down">우선 처리</div></div>
    <div class="sb-adm-kpi"><div class="lbl">이달 승인</div><div class="val">86</div><div class="delta up">▲ 9%</div></div>
</div>

<div class="sb-adm-toolbar">
    <span class="sb-adm-chip is-active">공유 요청</span><span class="sb-adm-chip">신고</span>
    <span class="sb-adm-input sel">상태: 대기</span>
    <span class="sb-adm-btn sb-adm-btn--primary sb-adm-spacer">조회</span>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-table-wrap">
        <table class="sb-adm-table">
            <thead><tr><th><span class="sb-adm-checkbox"></span></th><th>디자인</th><th>작성자</th><th>유형</th><th>상태</th></tr></thead>
            <tbody>
                <tr><td><span class="sb-adm-checkbox"></span></td><td><span class="sb-adm-avatar-name"><span class="sb-adm-thumb">IMG</span><span>홈베이킹 라벨 세트</span></span></td><td>김라벨</td><td><span class="sb-adm-badge sb-adm-badge--amber">공유</span></td><td><span class="sb-adm-badge sb-adm-badge--amber">대기</span></td></tr>
                <tr><td><span class="sb-adm-checkbox"></span></td><td><span class="sb-adm-avatar-name"><span class="sb-adm-thumb">IMG</span><span>카페 메뉴 스티커</span></span></td><td>이스티커</td><td><span class="sb-adm-badge sb-adm-badge--amber">공유</span></td><td><span class="sb-adm-badge sb-adm-badge--amber">대기</span></td></tr>
                <tr><td><span class="sb-adm-checkbox"></span></td><td><span class="sb-adm-avatar-name"><span class="sb-adm-thumb">IMG</span><span>저작권 의심 디자인</span></span></td><td>박프린트</td><td><span class="sb-adm-badge sb-adm-badge--red">신고</span></td><td><span class="sb-adm-badge sb-adm-badge--red">검토</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>검수 — 홈베이킹 라벨 세트</h4></div>
        <div class="sb-adm-thumb" style="width:100%;height:150px;border-radius:8px">PREVIEW</div>
        <div class="sb-adm-form-grid" style="margin-top:12px">
            <div class="sb-adm-form-row"><label>작성자</label><div class="box">김라벨 (#48215)</div></div>
            <div class="sb-adm-form-row"><label>규격</label><div class="box">원형 40mm</div></div>
            <div class="sb-adm-form-row full"><label>검수 의견</label><div class="box area">저작권 요소 없음 · 공유 적합</div></div>
        </div>
        <div class="sb-adm-head-actions" style="margin-top:12px"><span class="sb-adm-btn sb-adm-btn--danger">반려</span><span class="sb-adm-btn sb-adm-btn--primary">공유 승인</span></div>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
