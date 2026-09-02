<?php
/**
 * 스토리보드: 템플릿 관리
 * 메뉴코드: 02-05-01
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-05-01';
$adminTitle = '템플릿 관리';
$adminSub = '공식 템플릿 등록·분류·노출·가격 관리';
$adminActive = '02-05';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '템플릿 관리 (갤러리 + 편집)'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/designs/templates'),
    array('dt' => '연관 테이블', 'dd' => 'templates · template_categories · template_tags'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 디자인팀'),
    array('dt' => '화면 목적', 'dd' => '공식 템플릿 CRUD·무료/프리미엄·태그·노출'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 디자인 관리 › 템플릿 관리'),
);

$adminZones = array(
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '필터/검색', 'el' => '카테고리·유형(무료/프리미엄)·태그·상태', 'link' => '—'),
    array('id' => 'A-01', 'kind' => 'cta', 'block' => '일괄 작업', 'el' => '노출/숨김·카테고리 이동·태그·삭제', 'link' => '—'),
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '템플릿 갤러리', 'el' => '썸네일·이름·유형·규격·사용수·상태(그리드)', 'link' => '02-06'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '등록/수정', 'el' => '이름·카테고리·규격·에디터 데이터·가격·태그', 'link' => '—'),
);

$adminUx = array(
    array('item' => '그리드/리스트', 'desc' => '썸네일 갤러리 ↔ 상세 리스트 뷰 전환'),
    array('item' => '유형', 'desc' => '무료/프리미엄. 프리미엄은 판매가·포함 등급 지정'),
    array('item' => '규격 연동', 'desc' => '규격 관리(02-06) 규격 선택. 신규 규격 등록 링크'),
    array('item' => '버전 관리', 'desc' => '템플릿 수정 시 버전 스냅샷. 기존 사용자 영향 없음'),
    array('item' => '노출 예약', 'desc' => '노출 시작/종료 일시 예약. 시즌 템플릿 관리'),
);

$adminMockup = function () {
    $cards = array(
        array('n' => '미니멀 제품 라벨', 't' => 'green', 'tl' => '무료', 'u' => '8,412'),
        array('n' => '화장품 성분 라벨', 't' => 'amber', 'tl' => '프리미엄', 'u' => '6,204'),
        array('n' => '식품 원산지 라벨', 't' => 'green', 'tl' => '무료', 'u' => '5,180'),
        array('n' => '배송 송장 라벨', 't' => 'green', 'tl' => '무료', 'u' => '4,922'),
        array('n' => '카페 메뉴 스티커', 't' => 'amber', 'tl' => '프리미엄', 'u' => '3,880'),
        array('n' => '홈베이킹 라벨', 't' => 'gray', 'tl' => '숨김', 'u' => '0'),
    );
?>
<div class="sb-adm-head">
    <div><h3>템플릿 관리</h3><p>총 2,140개 · 노출 2,101</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">▤ 리스트</span><span class="sb-adm-btn sb-adm-btn--primary">＋ 템플릿 등록</span></div>
</div>

<div class="sb-adm-toolbar">
    <span class="sb-adm-input" style="min-width:200px">🔍 템플릿명·태그</span>
    <span class="sb-adm-input sel">카테고리: 전체</span>
    <span class="sb-adm-chip is-active">전체</span><span class="sb-adm-chip">무료</span><span class="sb-adm-chip">프리미엄</span>
    <span class="sb-adm-btn sb-adm-btn--primary sb-adm-spacer">검색</span>
</div>

<div class="sb-adm-grid sb-adm-grid--3" style="gap:12px">
    <?php foreach ($cards as $c): ?>
    <div class="sb-adm-panel" style="padding:0;overflow:hidden">
        <div class="sb-adm-thumb" style="width:100%;height:120px;border-radius:0;border:none;border-bottom:1px solid #f1f5f9">THUMB</div>
        <div style="padding:10px 12px">
            <div class="strong" style="font-size:12px"><?= e($c['n']) ?></div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px">
                <span class="sb-adm-badge sb-adm-badge--<?= $c['t'] ?>"><?= e($c['tl']) ?></span>
                <small class="muted">사용 <?= e($c['u']) ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="sb-adm-pager"><span>총 2,140개</span><div class="sb-adm-pager-nums"><span>‹</span><span class="is-active">1</span><span>2</span><span>3</span><span>›</span></div></div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
