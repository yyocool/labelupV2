<?php
/**
 * 스토리보드: AI 관리 (그룹 개요)
 * 메뉴코드: 02-07
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-07';
$adminTitle = 'AI 관리';
$adminSub = 'AI 라벨 생성 프롬프트·사용량·비용 운영 허브';
$adminActive = '02-07';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => 'AI 관리 개요'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/ai'),
    array('dt' => '하위 화면', 'dd' => '프롬프트 관리(02-07-01) · 사용량 통계(02-07-02)'),
    array('dt' => '접근 권한', 'dd' => '관리자 · AI 운영'),
    array('dt' => '화면 목적', 'dd' => 'AI 생성 프롬프트·모델·사용량·비용 모니터링'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › AI 관리'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => 'AI KPI', 'el' => '이달 생성 수·성공률·평균 응답·비용', 'link' => '02-07-02'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '사용량 추이', 'el' => '일별 생성 요청 차트', 'link' => '02-07-02'),
    array('id' => 'M-03', 'kind' => 'nav', 'block' => '프롬프트 요약', 'el' => '활성 프롬프트·모델 목록', 'link' => '02-07-01'),
);

$adminUx = array(
    array('item' => '비용 모니터', 'desc' => '토큰/이미지 비용 실시간 집계. 예산 임계 경고'),
    array('item' => '프롬프트', 'desc' => '활성 프롬프트 위젯 → 프롬프트 관리'),
    array('item' => '품질 지표', 'desc' => '성공률·재생성률·사용자 만족도 추적'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>AI 관리</h3><p>AI 라벨 생성 서비스 현황</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn sb-adm-btn--primary">＋ 프롬프트 추가</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">이달 생성</div><div class="val">128.4K</div><div class="delta up">▲ 18%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">성공률</div><div class="val">96.2%</div><div class="delta up">▲ 0.8%p</div></div>
    <div class="sb-adm-kpi"><div class="lbl">평균 응답</div><div class="val">3.4s</div><div class="delta up">▼ 0.3s</div></div>
    <div class="sb-adm-kpi"><div class="lbl">이달 비용</div><div class="val">$4,280</div><div class="delta down">예산의 71%</div></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>일별 생성 요청</h4><span class="more">02-07-02 →</span></div>
        <div class="sb-adm-chart">
            <div class="sb-adm-bar" style="height:48%"></div><div class="sb-adm-bar" style="height:60%"></div>
            <div class="sb-adm-bar" style="height:55%"></div><div class="sb-adm-bar" style="height:72%"></div>
            <div class="sb-adm-bar" style="height:68%"></div><div class="sb-adm-bar" style="height:84%"></div>
            <div class="sb-adm-bar" style="height:90%"></div>
        </div>
    </div>
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>활성 프롬프트</h4><span class="more">02-07-01 →</span></div>
        <ul class="sb-adm-list">
            <li><span class="sb-adm-badge sb-adm-badge--green">활성</span><span class="grow">라벨 디자인 생성 v3</span><span class="val">GPT-4o</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--green">활성</span><span class="grow">배경 이미지 생성</span><span class="val">SDXL</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--gray">테스트</span><span class="grow">문구 추천 v2</span><span class="val">GPT-4o-mini</span></li>
        </ul>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
