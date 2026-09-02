<?php
/**
 * 스토리보드: 사용량 통계
 * 메뉴코드: 02-07-02
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-07-02';
$adminTitle = '사용량 통계';
$adminSub = 'AI 생성 요청·비용·품질 지표 분석';
$adminActive = '02-07';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => 'AI 사용량 통계'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/ai/usage'),
    array('dt' => '연관 테이블', 'dd' => 'ai_generation_logs · ai_cost_ledger'),
    array('dt' => '접근 권한', 'dd' => '관리자 · AI 운영'),
    array('dt' => '화면 목적', 'dd' => '요청량·비용·성공률·프롬프트별 성과 분석'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › AI 관리 › 사용량 통계'),
);

$adminZones = array(
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '필터', 'el' => '기간·프롬프트·모델·회원등급', 'link' => '02-07-01'),
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '요약 KPI', 'el' => '총 요청·성공률·재생성률·총 비용', 'link' => '—'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '추이 차트', 'el' => '일별 요청/비용 이중축', 'link' => '02-09-01'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '프롬프트별 성과', 'el' => '프롬프트별 요청·성공률·평균비용 테이블', 'link' => '02-07-01'),
);

$adminUx = array(
    array('item' => '비용 배분', 'desc' => '모델별 단가 × 사용량. 예산 대비 소진율 게이지'),
    array('item' => '품질 추적', 'desc' => '성공률·재생성률·평균 만족도(별점). 이상치 하이라이트'),
    array('item' => '드릴다운', 'desc' => '프롬프트 클릭 → 해당 생성 로그 상세'),
    array('item' => '내보내기', 'desc' => '월간 AI 비용 리포트 엑셀/정산 연동'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>AI 사용량 통계</h3><p>2026-07-01 ~ 07-05</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-input sel">기간: 이달</span><span class="sb-adm-btn sb-adm-btn--primary">⤓ 비용 리포트</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">총 요청</div><div class="val">128,412</div><div class="delta up">▲ 18%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">성공률</div><div class="val">96.2%</div><div class="delta up">▲ 0.8%p</div></div>
    <div class="sb-adm-kpi"><div class="lbl">재생성률</div><div class="val">14.3%</div><div class="delta down">▼ 1.2%p</div></div>
    <div class="sb-adm-kpi"><div class="lbl">총 비용</div><div class="val">$4,280</div><div class="delta down">예산의 71%</div></div>
</div>

<div class="sb-adm-panel">
    <div class="sb-adm-panel-head"><h4>일별 요청 · 비용</h4></div>
    <div class="sb-adm-chart sb-adm-chart--line"></div>
</div>

<div class="sb-adm-table-wrap">
    <table class="sb-adm-table">
        <thead><tr><th>프롬프트</th><th>모델</th><th class="num">요청</th><th class="num">성공률</th><th class="num">재생성률</th><th class="num">평균 비용</th><th class="num">비용 합계</th></tr></thead>
        <tbody>
            <tr><td class="strong">라벨 디자인 생성 v3</td><td>GPT-4o</td><td class="num">72,140</td><td class="num">97.1%</td><td class="num">12.4%</td><td class="num">$0.021</td><td class="num strong">$1,515</td></tr>
            <tr><td class="strong">배경 이미지 생성</td><td>SDXL</td><td class="num">38,220</td><td class="num">95.8%</td><td class="num">18.9%</td><td class="num">$0.048</td><td class="num strong">$1,834</td></tr>
            <tr><td class="strong">색상 팔레트 제안</td><td>GPT-4o-mini</td><td class="num">14,880</td><td class="num">98.4%</td><td class="num">6.2%</td><td class="num">$0.004</td><td class="num strong">$59</td></tr>
            <tr><td class="strong">문구 추천 v2</td><td>GPT-4o-mini</td><td class="num">3,172</td><td class="num">94.1%</td><td class="num">21.0%</td><td class="num">$0.005</td><td class="num strong">$16</td></tr>
        </tbody>
    </table>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
