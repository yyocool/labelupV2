<?php
/**
 * 스토리보드: 회원목록
 * 메뉴코드: 02-02-01
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-02-01';
$adminTitle = '회원목록';
$adminSub = '회원 검색·필터·상세·상태 관리';
$adminActive = '02-02';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '회원목록 (리스트 + 상세)'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/members/list'),
    array('dt' => '연관 테이블', 'dd' => 'users · user_grades · point_ledger'),
    array('dt' => '접근 권한', 'dd' => '관리자(수정) · 운영자(열람)'),
    array('dt' => '화면 목적', 'dd' => '회원 검색·상세 확인·상태/등급 변경'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 회원관리 › 회원목록'),
);

$adminZones = array(
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '검색/필터바', 'el' => '통합검색·가입기간·등급·상태·정렬', 'link' => '—'),
    array('id' => 'A-01', 'kind' => 'cta', 'block' => '일괄 작업', 'el' => '메일/포인트 지급·등급변경·엑셀 내보내기', 'link' => '02-01-03'),
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '회원 테이블', 'el' => '체크·회원·연락처·등급·주문/누적금액·상태·가입일', 'link' => '—'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '상세 드로어', 'el' => '기본정보·주문내역·포인트·활동로그·메모', 'link' => '02-04-01'),
    array('id' => 'P-01', 'kind' => 'nav', 'block' => '페이지네이션', 'el' => '페이지 이동·페이지당 개수', 'link' => '—'),
);

$adminUx = array(
    array('item' => '검색', 'desc' => '이름/이메일/휴대폰/회원번호 통합 검색. 디바운스 적용'),
    array('item' => '상세 열람', 'desc' => '행 클릭 → 우측 드로어. 개인정보 열람 로그 기록'),
    array('item' => '상태 변경', 'desc' => '활성/휴면/정지/탈퇴. 정지 시 사유 필수 입력'),
    array('item' => '일괄 처리', 'desc' => '체크박스 다중선택 → 메일/포인트/등급 일괄 반영(확인 모달)'),
    array('item' => '보안', 'desc' => '연락처/이메일 마스킹. 상세 열람 시 부분 노출 + 감사 로그'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>회원목록</h3><p>총 48,215명</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">✉ 메일 발송</span><span class="sb-adm-btn">⤓ 엑셀</span><span class="sb-adm-btn sb-adm-btn--primary">＋ 회원 등록</span></div>
</div>

<div class="sb-adm-toolbar">
    <span class="sb-adm-input" style="min-width:240px">🔍 이름·이메일·휴대폰·회원번호</span>
    <span class="sb-adm-input sel">등급: 전체</span>
    <span class="sb-adm-input sel">상태: 전체</span>
    <span class="sb-adm-input sel">가입기간: 전체</span>
    <span class="sb-adm-btn sb-adm-btn--ghost">초기화</span>
    <span class="sb-adm-btn sb-adm-btn--primary sb-adm-spacer">검색</span>
</div>

<div class="sb-adm-table-wrap">
    <table class="sb-adm-table">
        <thead><tr>
            <th><span class="sb-adm-checkbox"></span></th><th>회원</th><th>연락처</th><th>등급</th>
            <th class="num">주문</th><th class="num">누적 구매액</th><th class="num">보유 포인트</th><th>상태</th><th>가입일</th><th></th>
        </tr></thead>
        <tbody>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td><span class="sb-adm-avatar-name"><span class="av">김</span><span>김라벨<br><small>#48215</small></span></span></td>
                <td>hi***@gmail.com<br><small class="muted">010-****-1234</small></td>
                <td><span class="sb-adm-badge sb-adm-badge--gray">일반</span></td>
                <td class="num">12</td><td class="num strong">1,240,000</td><td class="num">12,830</td>
                <td><span class="sb-adm-badge sb-adm-badge--green">활성</span></td><td>2026-07-05</td>
                <td><span class="sb-adm-btn sb-adm-btn--sm">상세</span></td>
            </tr>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td><span class="sb-adm-avatar-name"><span class="av">박</span><span>박프린트<br><small>#48180</small></span></span></td>
                <td>pr***@daum.net<br><small class="muted">010-****-9821</small></td>
                <td><span class="sb-adm-badge sb-adm-badge--green">실버</span></td>
                <td class="num">38</td><td class="num strong">4,820,000</td><td class="num">7,540</td>
                <td><span class="sb-adm-badge sb-adm-badge--green">활성</span></td><td>2026-06-28</td>
                <td><span class="sb-adm-btn sb-adm-btn--sm">상세</span></td>
            </tr>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td><span class="sb-adm-avatar-name"><span class="av">최</span><span>최라벨업<br><small>#47950</small></span></span></td>
                <td>ce***@company.co.kr<br><small class="muted">010-****-3355</small></td>
                <td><span class="sb-adm-badge sb-adm-badge--amber">골드</span></td>
                <td class="num">126</td><td class="num strong">18,340,000</td><td class="num">42,100</td>
                <td><span class="sb-adm-badge sb-adm-badge--gray">휴면</span></td><td>2026-05-11</td>
                <td><span class="sb-adm-btn sb-adm-btn--sm">상세</span></td>
            </tr>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td><span class="sb-adm-avatar-name"><span class="av">정</span><span>정디자인<br><small>#47712</small></span></span></td>
                <td>de***@gmail.com<br><small class="muted">010-****-7788</small></td>
                <td><span class="sb-adm-badge sb-adm-badge--purple">VIP</span></td>
                <td class="num">312</td><td class="num strong">52,900,000</td><td class="num">128,400</td>
                <td><span class="sb-adm-badge sb-adm-badge--red">정지</span></td><td>2025-12-02</td>
                <td><span class="sb-adm-btn sb-adm-btn--sm">상세</span></td>
            </tr>
        </tbody>
    </table>
    <div class="sb-adm-pager"><span>페이지당 20건 · 총 2,411페이지</span><div class="sb-adm-pager-nums"><span>‹</span><span class="is-active">1</span><span>2</span><span>3</span><span>4</span><span>›</span></div></div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
