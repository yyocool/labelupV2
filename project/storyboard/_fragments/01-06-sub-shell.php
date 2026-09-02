<?php
/**
 * 마이페이지 하위 화면 공용 셸 (사이드바 + 상단바 + 서브탭)
 * 01-06-01~04 등 마이페이지 하위 화면(-hifi-wireframe-body.php)에서 공용으로 include 합니다.
 *
 * 주의: 이 파일은 `.sb-hifi-mypage__content` 의 여는 태그까지만 출력합니다.
 * 호출측에서 본문(M-02 이후 zone)을 채운 뒤 아래 닫는 태그를 직접 작성해야 합니다.
 *   </div><!-- /.sb-hifi-mypage__content -->
 *   </div><!-- /.sb-hifi-mypage__center -->
 *
 * @var string $mpActive 활성 탭: profile|orders|billing|designs
 */
if (!isset($mpActive)) {
    $mpActive = '';
}
$sbMpTabs = array(
    'profile' => '내 정보',
    'orders'  => '주문·배송',
    'billing' => '결제·구독',
    'designs' => '디자인 관리',
);
?>
<aside class="sb-hifi-mypage__sidebar">
    <div class="sb-hifi-mypage__logo">
        <strong>라벨업</strong>
        <small>LABEL UP</small>
    </div>
    <nav class="sb-hifi-mypage__nav sb-wf-zone" data-zone-id="L-01">
        <span class="sb-wf-zone-label">L-01</span>
        <div class="sb-hifi-mypage__nav-item"><span class="sb-hifi-mypage__nav-icon">⌂</span> 홈</div>
        <div class="sb-hifi-mypage__nav-item"><span class="sb-hifi-mypage__nav-icon">▦</span> 템플릿</div>
        <div class="sb-hifi-mypage__nav-item"><span class="sb-hifi-mypage__nav-icon">🏷</span> 용지/라벨</div>
        <div class="sb-hifi-mypage__nav-item"><span class="sb-hifi-mypage__nav-icon">✦</span> AI 디자인 <span class="sb-hifi-mypage__nav-badge">BETA</span></div>
        <div class="sb-hifi-mypage__nav-item"><span class="sb-hifi-mypage__nav-icon">📁</span> 내 디자인</div>
        <div class="sb-hifi-mypage__nav-item"><span class="sb-hifi-mypage__nav-icon">📋</span> 주문 내역</div>
        <div class="sb-hifi-mypage__nav-item"><span class="sb-hifi-mypage__nav-icon">◎</span> 브랜드 관리</div>
        <div class="sb-hifi-mypage__nav-bottom">
            <div class="sb-hifi-mypage__nav-sm">📖 가이드</div>
            <div class="sb-hifi-mypage__nav-sm is-active">👤 마이페이지</div>
            <div class="sb-hifi-mypage__nav-sm">💬 고객센터</div>
            <div class="sb-hifi-mypage__nav-sm">⚙ 설정</div>
        </div>
    </nav>
    <div class="sb-hifi-mypage__points sb-wf-zone" data-zone-id="L-02">
        <span class="sb-wf-zone-label">L-02</span>
        <span class="sb-hifi-mypage__points-coin">🪙</span>
        <div>
            <div class="sb-hifi-mypage__points-label">보유 포인트</div>
            <div class="sb-hifi-mypage__points-value">12,450 P</div>
            <span class="sb-hifi-mypage__points-link">포인트 충전 ›</span>
        </div>
    </div>
</aside>

<div class="sb-hifi-mypage__center">
    <div class="sb-hifi-mypage__m01 sb-wf-zone" data-zone-id="M-01">
        <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-01</span>
        <header class="sb-hifi-mypage__topbar">
            <div class="sb-hifi-mypage__search">⌕ 찾고 있는 라벨 규격이나 재질을 검색해보세요</div>
            <div class="sb-hifi-mypage__top-actions">
                <div class="sb-hifi-mypage__icon-btn">🛒<span class="sb-hifi-mypage__badge">2</span></div>
                <div class="sb-hifi-mypage__icon-btn">🔔<span class="sb-hifi-mypage__badge">3</span></div>
                <div class="sb-hifi-mypage__profile">
                    <span class="sb-hifi-mypage__avatar"></span>
                    김라벨님 ▾
                </div>
            </div>
        </header>
        <nav class="sb-hifi-mypage__subtabs" aria-label="마이페이지 서브 메뉴">
            <?php foreach ($sbMpTabs as $mpKey => $mpLabel): ?>
            <span class="sb-hifi-mypage__subtab<?= $mpKey === $mpActive ? ' is-active' : '' ?>"><?= e($mpLabel) ?></span>
            <?php endforeach; ?>
        </nav>
    </div>
    <div class="sb-hifi-mypage__content">
