<?php
/**
 * 스토리보드: 회원등급
 * 메뉴코드: 02-02-02
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-02-02';
$adminTitle = '회원등급';
$adminSub = '등급 정책·혜택·승급 기준 설정';
$adminActive = '02-02';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '회원등급 관리'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/members/grades'),
    array('dt' => '연관 테이블', 'dd' => 'user_grades · grade_benefits'),
    array('dt' => '접근 권한', 'dd' => '관리자'),
    array('dt' => '화면 목적', 'dd' => '등급 정의·혜택·자동 승급 기준 관리'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 회원관리 › 회원등급'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '등급 카드', 'el' => '등급별 회원 수·비중·색상', 'link' => '—'),
    array('id' => 'T-01', 'kind' => 'ui', 'block' => '등급 테이블', 'el' => '등급명·승급기준·적립률·혜택·회원 수', 'link' => '—'),
    array('id' => 'C-01', 'kind' => 'cta', 'block' => '등급 편집', 'el' => '등급 추가/수정 모달', 'link' => '—'),
    array('id' => 'S-01', 'kind' => 'nav', 'block' => '승급 정책', 'el' => '자동 승급 주기·평가 기준(금액/횟수)', 'link' => '—'),
);

$adminUx = array(
    array('item' => '승급 기준', 'desc' => '누적 구매액 또는 주문 횟수 기준. 평가 주기(월/분기) 설정'),
    array('item' => '혜택 설정', 'desc' => '등급별 적립률·할인·무료배송 임계·전용 쿠폰'),
    array('item' => '순서/색상', 'desc' => '드래그로 등급 순서 조정. 배지 색상 지정'),
    array('item' => '적용 방식', 'desc' => '정책 변경 시 다음 평가부터 반영. 강등 유예 설정'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>회원등급</h3><p>4개 등급 · 자동 승급(월 1회 평가)</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">⚙ 승급 정책</span><span class="sb-adm-btn sb-adm-btn--primary">＋ 등급 추가</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl"><span class="sb-adm-badge sb-adm-badge--gray">일반</span></div><div class="val">29,893</div><div class="delta">62%</div></div>
    <div class="sb-adm-kpi"><div class="lbl"><span class="sb-adm-badge sb-adm-badge--green">실버</span></div><div class="val">11,572</div><div class="delta">24%</div></div>
    <div class="sb-adm-kpi"><div class="lbl"><span class="sb-adm-badge sb-adm-badge--amber">골드</span></div><div class="val">5,304</div><div class="delta">11%</div></div>
    <div class="sb-adm-kpi"><div class="lbl"><span class="sb-adm-badge sb-adm-badge--purple">VIP</span></div><div class="val">1,446</div><div class="delta">3%</div></div>
</div>

<div class="sb-adm-table-wrap">
    <table class="sb-adm-table">
        <thead><tr><th>순서</th><th>등급</th><th>승급 기준</th><th class="num">적립률</th><th>주요 혜택</th><th class="num">회원 수</th><th></th></tr></thead>
        <tbody>
            <tr><td>1</td><td><span class="sb-adm-badge sb-adm-badge--gray">일반</span></td><td>가입 시 기본</td><td class="num">1.0%</td><td>기본 적립</td><td class="num">29,893</td><td><span class="sb-adm-btn sb-adm-btn--sm">편집</span></td></tr>
            <tr><td>2</td><td><span class="sb-adm-badge sb-adm-badge--green">실버</span></td><td>누적 30만원↑</td><td class="num">1.5%</td><td>3만원↑ 무료배송</td><td class="num">11,572</td><td><span class="sb-adm-btn sb-adm-btn--sm">편집</span></td></tr>
            <tr><td>3</td><td><span class="sb-adm-badge sb-adm-badge--amber">골드</span></td><td>누적 150만원↑</td><td class="num">2.5%</td><td>무료배송·5% 쿠폰</td><td class="num">5,304</td><td><span class="sb-adm-btn sb-adm-btn--sm">편집</span></td></tr>
            <tr><td>4</td><td><span class="sb-adm-badge sb-adm-badge--purple">VIP</span></td><td>누적 500만원↑</td><td class="num">4.0%</td><td>전담 CS·10% 쿠폰·우선제작</td><td class="num">1,446</td><td><span class="sb-adm-btn sb-adm-btn--sm">편집</span></td></tr>
        </tbody>
    </table>
</div>

<div class="sb-adm-panel" style="margin-top:14px">
    <div class="sb-adm-panel-head"><h4>자동 승급 정책</h4></div>
    <div class="sb-adm-form-grid">
        <div class="sb-adm-form-row"><label>평가 기준</label><div class="box">최근 12개월 누적 구매액</div></div>
        <div class="sb-adm-form-row"><label>평가 주기</label><div class="box">매월 1일 자동 실행</div></div>
        <div class="sb-adm-form-row"><label>강등 유예</label><div class="box">2회 연속 미달 시 강등</div></div>
        <div class="sb-adm-form-row"><label>승급 알림</label><div class="box">승급 시 알림톡 + 축하 쿠폰 발송</div></div>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
