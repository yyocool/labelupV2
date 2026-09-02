<?php
/**
 * 스토리보드: 프롬프트 관리
 * 메뉴코드: 02-07-01
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-07-01';
$adminTitle = '프롬프트 관리';
$adminSub = 'AI 생성 프롬프트 버전·모델·파라미터 관리';
$adminActive = '02-07';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '프롬프트 관리 (리스트 + 편집)'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/ai/prompts'),
    array('dt' => '연관 테이블', 'dd' => 'ai_prompts · ai_prompt_versions · ai_models'),
    array('dt' => '접근 권한', 'dd' => '관리자 · AI 운영'),
    array('dt' => '화면 목적', 'dd' => '프롬프트 템플릿·모델·파라미터·버전 관리 및 테스트'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › AI 관리 › 프롬프트 관리'),
);

$adminZones = array(
    array('id' => 'L-01', 'kind' => 'nav', 'block' => '프롬프트 목록', 'el' => '용도별 프롬프트·상태(활성/테스트)·모델', 'link' => '—'),
    array('id' => 'R-01', 'kind' => 'ui', 'block' => '프롬프트 편집', 'el' => '시스템/사용자 프롬프트·변수·모델·파라미터', 'link' => '—'),
    array('id' => 'R-02', 'kind' => 'cta', 'block' => '테스트 실행', 'el' => '샘플 입력 → 결과 미리보기', 'link' => '02-07-02'),
    array('id' => 'R-03', 'kind' => 'ui', 'block' => '버전 이력', 'el' => '버전별 변경·활성 롤백', 'link' => '—'),
);

$adminUx = array(
    array('item' => '변수 바인딩', 'desc' => '{{규격}}, {{키워드}} 등 변수 정의. 미매핑 변수 경고'),
    array('item' => '모델/파라미터', 'desc' => '모델·temperature·max_tokens·이미지 크기 설정'),
    array('item' => '테스트', 'desc' => '샘플 입력으로 즉시 실행. 비용/응답시간 표시'),
    array('item' => '버전/롤백', 'desc' => '저장 시 새 버전. 활성 버전 지정·이전 버전 롤백'),
    array('item' => 'A/B', 'desc' => '활성/테스트 트래픽 분배 비율 설정(옵션)'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>프롬프트 관리</h3><p>8개 프롬프트 · 3개 모델</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">▷ 테스트 실행</span><span class="sb-adm-btn sb-adm-btn--primary">＋ 프롬프트 추가</span></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>프롬프트 목록</h4></div>
        <ul class="sb-adm-list">
            <li><span class="grow strong">라벨 디자인 생성 v3</span><span class="sb-adm-badge sb-adm-badge--green">활성</span></li>
            <li><span class="grow">배경 이미지 생성</span><span class="sb-adm-badge sb-adm-badge--green">활성</span></li>
            <li><span class="grow">문구 추천 v2</span><span class="sb-adm-badge sb-adm-badge--gray">테스트</span></li>
            <li><span class="grow">색상 팔레트 제안</span><span class="sb-adm-badge sb-adm-badge--green">활성</span></li>
            <li><span class="grow">라벨 디자인 생성 v2</span><span class="sb-adm-badge sb-adm-badge--gray">보관</span></li>
        </ul>
    </div>
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>편집 — 라벨 디자인 생성 v3</h4><span class="more">버전 이력</span></div>
        <div class="sb-adm-form-grid">
            <div class="sb-adm-form-row"><label>모델</label><div class="box">GPT-4o (vision)</div></div>
            <div class="sb-adm-form-row"><label>상태</label><div class="box">활성</div></div>
            <div class="sb-adm-form-row"><label>Temperature</label><div class="box">0.7</div></div>
            <div class="sb-adm-form-row"><label>Max Tokens</label><div class="box">2,048</div></div>
            <div class="sb-adm-form-row full"><label>시스템 프롬프트</label><div class="box area">당신은 라벨 디자인 전문가입니다. 규격 {{규격}}에 맞춰 {{키워드}} 컨셉의 라벨을 생성합니다...</div></div>
            <div class="sb-adm-form-row full"><label>변수</label><div class="box">{{규격}} · {{키워드}} · {{색상}} · {{용도}}</div></div>
        </div>
        <div class="sb-adm-head-actions" style="margin-top:12px"><span class="sb-adm-btn">▷ 테스트</span><span class="sb-adm-btn sb-adm-btn--primary">새 버전 저장</span></div>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
