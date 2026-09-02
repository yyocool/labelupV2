<?php
/**
 * 스토리보드: 카테고리 관리
 * 메뉴코드: 02-03-02
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-03-02';
$adminTitle = '카테고리 관리';
$adminSub = '상품 카테고리 트리·정렬·노출 관리';
$adminActive = '02-03';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '카테고리 관리 (트리 편집)'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/shop/categories'),
    array('dt' => '연관 테이블', 'dd' => 'categories (self-ref tree)'),
    array('dt' => '접근 권한', 'dd' => '관리자 · MD'),
    array('dt' => '화면 목적', 'dd' => '카테고리 계층 구성·정렬·노출/아이콘 관리'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 쇼핑몰 관리 › 카테고리 관리'),
);

$adminZones = array(
    array('id' => 'L-01', 'kind' => 'nav', 'block' => '카테고리 트리', 'el' => '드래그 정렬·접기/펼치기·상품 수', 'link' => '02-03-01'),
    array('id' => 'R-01', 'kind' => 'ui', 'block' => '상세/편집 폼', 'el' => '이름·슬러그·부모·노출·아이콘·설명', 'link' => '—'),
    array('id' => 'R-02', 'kind' => 'ui', 'block' => 'SEO 설정', 'el' => 'meta title/description·대표 이미지', 'link' => '—'),
    array('id' => 'C-01', 'kind' => 'cta', 'block' => '카테고리 추가', 'el' => '루트/하위 카테고리 생성', 'link' => '—'),
);

$adminUx = array(
    array('item' => '트리 편집', 'desc' => '드래그&드롭으로 순서·부모 변경. 최대 3뎁스 제한'),
    array('item' => '노출 제어', 'desc' => '노출/숨김 토글. 숨김 시 하위 자동 숨김 안내'),
    array('item' => '삭제 규칙', 'desc' => '상품/하위가 있으면 삭제 불가 → 이동 후 삭제 유도'),
    array('item' => '슬러그', 'desc' => '이름 입력 시 자동 슬러그 생성. 중복 검사'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>카테고리 관리</h3><p>3개 대분류 · 12개 중분류</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn sb-adm-btn--primary">＋ 카테고리 추가</span></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2">
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>카테고리 트리</h4><span class="more">드래그 정렬</span></div>
        <ul class="sb-adm-list">
            <li><span>⋮⋮</span><span class="grow strong">라벨·스티커</span><span class="sb-adm-badge sb-adm-badge--gray">842</span><span class="sb-adm-badge sb-adm-badge--green">노출</span></li>
            <li style="padding-left:24px"><span>⋮⋮</span><span class="grow">└ A4 라벨</span><span class="sb-adm-badge sb-adm-badge--gray">312</span></li>
            <li style="padding-left:24px"><span>⋮⋮</span><span class="grow">└ 원형/특수 라벨</span><span class="sb-adm-badge sb-adm-badge--gray">198</span></li>
            <li style="padding-left:24px"><span>⋮⋮</span><span class="grow">└ 배송/감열 라벨</span><span class="sb-adm-badge sb-adm-badge--gray">332</span></li>
            <li><span>⋮⋮</span><span class="grow strong">프린터·소모품</span><span class="sb-adm-badge sb-adm-badge--gray">356</span><span class="sb-adm-badge sb-adm-badge--green">노출</span></li>
            <li><span>⋮⋮</span><span class="grow strong">인쇄 의뢰</span><span class="sb-adm-badge sb-adm-badge--gray">86</span><span class="sb-adm-badge sb-adm-badge--gray">숨김</span></li>
        </ul>
    </div>
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>카테고리 편집 — 라벨·스티커</h4></div>
        <div class="sb-adm-form-grid">
            <div class="sb-adm-form-row"><label>카테고리명</label><div class="box">라벨·스티커</div></div>
            <div class="sb-adm-form-row"><label>슬러그</label><div class="box">label-sticker</div></div>
            <div class="sb-adm-form-row"><label>상위 카테고리</label><div class="box">(최상위)</div></div>
            <div class="sb-adm-form-row"><label>노출 여부</label><div class="box">노출</div></div>
            <div class="sb-adm-form-row full"><label>대표 아이콘/이미지</label><div class="box">🏷 label-sticker.svg</div></div>
            <div class="sb-adm-form-row full"><label>SEO 설명</label><div class="box area">라벨·스티커 전 상품 · 방수/투명/감열 등 재질별 규격</div></div>
        </div>
        <div class="sb-adm-head-actions" style="margin-top:12px"><span class="sb-adm-btn sb-adm-btn--danger">삭제</span><span class="sb-adm-btn sb-adm-btn--primary">저장</span></div>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
