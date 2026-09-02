<?php
/**
 * 라벨 편집기 허브 공통 셸 (사이드바)
 * @var string $hubActiveNav  active key: new|mine|tpl|tpl-free|tpl-pro|tpl-share|spec|tools|edit|barcode|data|export|ai|help
 * @var string $hubPageTitle
 * @var string $hubPageSub
 * @var string $hubTopActionsHtml optional
 */
if (!isset($hubActiveNav)) {
    $hubActiveNav = 'new';
}
if (!isset($hubPageTitle)) {
    $hubPageTitle = '라벨 편집기';
}
if (!isset($hubPageSub)) {
    $hubPageSub = '';
}
if (!isset($hubTopActionsHtml)) {
    $hubTopActionsHtml = '<span class="sb-hifi-hub__btn sb-hifi-hub__btn--primary">새 디자인</span>';
}
$navIs = function ($key) use ($hubActiveNav) {
    return $hubActiveNav === $key ? ' is-active' : '';
};
$childActive = in_array($hubActiveNav, array('tpl-free', 'tpl-pro', 'tpl-share'), true);
$toolChild = in_array($hubActiveNav, array('edit', 'barcode', 'data', 'export'), true);
?>
<aside class="sb-hifi-hub__sidebar">
    <nav class="sb-hifi-hub__icon-rail sb-wf-zone" data-zone-id="L-01" aria-label="글로벌 아이콘 네비">
        <span class="sb-wf-zone-label">L-01</span>
        <div class="sb-hifi-hub__icon-btn" title="HOME">⌂</div>
        <div class="sb-hifi-hub__icon-btn is-active" title="라벨 디자인">✎</div>
        <div class="sb-hifi-hub__icon-btn" title="템플릿">▦</div>
        <div class="sb-hifi-hub__icon-btn" title="규격 검색">⌕</div>
        <div class="sb-hifi-hub__icon-btn" title="인쇄">⎙</div>
        <div class="sb-hifi-hub__icon-btn" title="맞춤 제작">⚙</div>
        <div class="sb-hifi-hub__icon-btn" title="자료실">📁</div>
        <div class="sb-hifi-hub__icon-btn" title="고객센터">?</div>
    </nav>
    <div class="sb-hifi-hub__nav-panel sb-wf-zone" data-zone-id="L-02">
        <span class="sb-wf-zone-label">L-02</span>
        <div class="sb-hifi-hub__logo"><strong>라벨업</strong><small>LABEL UP</small></div>
        <div class="sb-hifi-hub__nav-section">디자인</div>
        <div class="sb-hifi-hub__nav-item<?= $navIs('new') ?>"><span>＋</span> 새 디자인 만들기</div>
        <div class="sb-hifi-hub__nav-item<?= $navIs('mine') ?>"><span>📁</span> 내 디자인</div>
        <div class="sb-hifi-hub__nav-section">템플릿</div>
        <div class="sb-hifi-hub__nav-item<?= ($hubActiveNav === 'tpl' || $childActive) ? ' is-active' : '' ?>"><span>▦</span> 템플릿</div>
        <div class="sb-hifi-hub__nav-item is-child<?= $navIs('tpl-free') ?>">무료 템플릿</div>
        <div class="sb-hifi-hub__nav-item is-child<?= $navIs('tpl-pro') ?>">프리미엄 템플릿</div>
        <div class="sb-hifi-hub__nav-item is-child<?= $navIs('tpl-share') ?>">공유 템플릿</div>
        <div class="sb-hifi-hub__nav-item<?= $navIs('spec') ?>"><span>⌕</span> 규격 검색</div>
        <div class="sb-hifi-hub__nav-section">편집 도구</div>
        <div class="sb-hifi-hub__nav-item<?= ($hubActiveNav === 'tools' || $toolChild) ? ' is-active' : '' ?>"><span>⚒</span> 편집 도구</div>
        <div class="sb-hifi-hub__nav-item is-child<?= $navIs('edit') ?>">디자인 편집</div>
        <div class="sb-hifi-hub__nav-item is-child<?= $navIs('barcode') ?>">바코드·QR</div>
        <div class="sb-hifi-hub__nav-item is-child<?= $navIs('data') ?>">데이터 연동</div>
        <div class="sb-hifi-hub__nav-item is-child<?= $navIs('export') ?>">출력·저장</div>
        <div class="sb-hifi-hub__nav-item<?= $navIs('ai') ?>"><span>✦</span> AI 디자인 생성</div>
        <div class="sb-hifi-hub__nav-item<?= $navIs('help') ?>"><span>?</span> Label-UP 도움말</div>
    </div>
</aside>
