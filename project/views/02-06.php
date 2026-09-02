<?php
/**
 * 스토리보드: 규격 관리
 * 메뉴코드: 02-06
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-06';
$adminTitle = '규격 관리';
$adminSub = '라벨 용지 규격(칸/크기/여백) 마스터 데이터 관리';
$adminActive = '02-06';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '규격 관리 (마스터)'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/specs'),
    array('dt' => '연관 테이블', 'dd' => 'label_specs · spec_brands'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 디자인팀'),
    array('dt' => '화면 목적', 'dd' => '용지 규격 등록/수정 · 에디터·상품·템플릿 공용 참조'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 규격 관리'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '규격 KPI', 'el' => '전체·브랜드·인기 규격·미사용', 'link' => '—'),
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '필터/검색', 'el' => '규격명·브랜드·용지(A4/롤)·칸 수', 'link' => '—'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '규격 테이블', 'el' => '규격명·용지·칸 배열·라벨 크기·여백·사용처', 'link' => '02-05-01'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '등록/수정 폼', 'el' => '치수(mm)·행/열·간격·모서리·미리보기', 'link' => '—'),
);

$adminUx = array(
    array('item' => '치수 검증', 'desc' => '용지 크기 대비 칸 배열·여백 합 검증. 초과 시 경고'),
    array('item' => '미리보기', 'desc' => '입력값 실시간 배치 미리보기(용지 위 칸 레이아웃)'),
    array('item' => '공용 참조', 'desc' => '에디터·상품·템플릿이 공용 참조. 사용 중 규격 삭제 불가'),
    array('item' => '단위', 'desc' => 'mm 기준 저장. inch 입력 토글 지원'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>규격 관리</h3><p>총 486개 규격 · 24개 브랜드</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">⤒ 규격 가져오기</span><span class="sb-adm-btn sb-adm-btn--primary">＋ 규격 등록</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">전체 규격</div><div class="val">486</div><div class="delta up">▲ 12</div></div>
    <div class="sb-adm-kpi"><div class="lbl">브랜드</div><div class="val">24</div><div class="delta">폼텍 외</div></div>
    <div class="sb-adm-kpi"><div class="lbl">인기 규격</div><div class="val">A4 24칸</div><div class="delta up">최다 사용</div></div>
    <div class="sb-adm-kpi"><div class="lbl">미사용 규격</div><div class="val">31</div><div class="delta">정리 검토</div></div>
</div>

<div class="sb-adm-toolbar">
    <span class="sb-adm-input" style="min-width:200px">🔍 규격명·브랜드코드</span>
    <span class="sb-adm-input sel">용지: 전체</span>
    <span class="sb-adm-input sel">브랜드: 전체</span>
    <span class="sb-adm-btn sb-adm-btn--primary sb-adm-spacer">검색</span>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-table-wrap">
        <table class="sb-adm-table">
            <thead><tr><th>규격명</th><th>용지</th><th class="num">칸(행×열)</th><th>라벨 크기</th><th class="num">사용 상품</th><th></th></tr></thead>
            <tbody>
                <tr><td class="strong">A4 24칸 (라운드)</td><td>A4</td><td class="num">8 × 3</td><td>64 × 33.9mm</td><td class="num">128</td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td></tr>
                <tr><td class="strong">A4 40칸</td><td>A4</td><td class="num">10 × 4</td><td>52.5 × 29.7mm</td><td class="num">86</td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td></tr>
                <tr><td class="strong">원형 40mm</td><td>A4</td><td class="num">6 × 4</td><td>Ø40mm</td><td class="num">54</td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td></tr>
                <tr><td class="strong">배송 100×150</td><td>롤</td><td class="num">1 × 1</td><td>100 × 150mm</td><td class="num">42</td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>규격 편집 — 규격코드 611(*아이라벨 규격 코드 항목가져옴)</h4></div>
        <div class="sb-adm-thumb" style="width:100%;height:120px;border-radius:8px">배치 미리보기</div>
        <div class="sb-adm-form-grid" style="margin-top:12px">
            <div class="sb-adm-form-row"><label>규격 코드</label><div class="box">611</div></div>
            <div class="sb-adm-form-row"><label>장당 라벨 수</label><div class="box">1 라벨/장</div></div>
            <div class="sb-adm-form-row"><label>라벨 너비 (mm)</label><div class="box">210</div></div>
            <div class="sb-adm-form-row"><label>라벨 높이 (mm)</label><div class="box">297</div></div>
            <div class="sb-adm-form-row"><label>모서리 R 값</label><div class="box">0</div></div>
            <div class="sb-adm-form-row"><label>왼쪽 여백 (mm)</label><div class="box">0</div></div>
            <div class="sb-adm-form-row"><label>오른쪽 여백 (mm)</label><div class="box">0</div></div>
            <div class="sb-adm-form-row"><label>위쪽 여백 (mm)</label><div class="box">0</div></div>
            <div class="sb-adm-form-row"><label>아래쪽 여백 (mm)</label><div class="box">0</div></div>
            <div class="sb-adm-form-row"><label>상하 간격 (mm)</label><div class="box">0</div></div>
            <div class="sb-adm-form-row"><label>좌우 간격 (mm)</label><div class="box">0</div></div>
        </div>
        <div class="sb-adm-head-actions" style="margin-top:12px"><span class="sb-adm-btn">복제</span><span class="sb-adm-btn sb-adm-btn--primary">저장</span></div>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
