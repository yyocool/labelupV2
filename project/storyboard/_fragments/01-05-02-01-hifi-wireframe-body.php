<?php
/**
 * 무료 템플릿 와이어프레임 바디 (01-05-02-01)
 */
$hubActiveNav = 'tpl-free';
$hubPageTitle = '무료 템플릿';
$hubPageSub = '업종별 무료 템플릿을 찾아 바로 적용해보세요';
$hubTopActionsHtml = '<span class="sb-hifi-hub__btn">▦ 템플릿 허브</span>';
?>
<div class="sb-hifi-hub">
    <?php include __DIR__ . '/01-05-hub-shell.php'; ?>
    <div class="sb-hifi-hub__main">
        <header class="sb-hifi-hub__top sb-wf-zone" data-zone-id="M-01">
            <span class="sb-wf-zone-label">M-01</span>
            <div class="sb-hifi-hub__title-wrap">
                <h1><?= e($hubPageTitle) ?></h1>
                <p><?= e($hubPageSub) ?></p>
            </div>
            <div class="sb-hifi-hub__top-actions"><?= $hubTopActionsHtml ?></div>
        </header>
        <div class="sb-hifi-hub__scroll">
            <div class="sb-hifi-hub__toolbar sb-wf-zone" data-zone-id="M-02">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>
                <div class="sb-hifi-hub__search"><span>⌕</span> 템플릿 이름 검색</div>
                <div class="sb-hifi-hub__chips">
                    <span class="sb-hifi-hub__chip is-active">전체</span>
                    <span class="sb-hifi-hub__chip">식품</span>
                    <span class="sb-hifi-hub__chip">화장품</span>
                    <span class="sb-hifi-hub__chip">물류</span>
                    <span class="sb-hifi-hub__chip">네임</span>
                </div>
            </div>

            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-03">
                <span class="sb-wf-zone-label">M-03</span>
                <div class="sb-hifi-hub__grid sb-hifi-hub__grid--4">
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">FREE</span>
                        <div class="sb-hifi-hub__thumb">식품</div>
                        <div class="sb-hifi-hub__meta"><strong>기본 식품 라벨</strong><span>80×50mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">FREE</span>
                        <div class="sb-hifi-hub__thumb">화장품</div>
                        <div class="sb-hifi-hub__meta"><strong>화장품 원형 라벨</strong><span>40×30mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">FREE</span>
                        <div class="sb-hifi-hub__thumb">물류</div>
                        <div class="sb-hifi-hub__meta"><strong>물류 바코드 라벨</strong><span>60×40mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">FREE</span>
                        <div class="sb-hifi-hub__thumb">네임</div>
                        <div class="sb-hifi-hub__meta"><strong>네임 카드 21칸</strong><span>A4 시트</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">FREE</span>
                        <div class="sb-hifi-hub__thumb">식품</div>
                        <div class="sb-hifi-hub__meta"><strong>간편식 성분 라벨</strong><span>70×40mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">FREE</span>
                        <div class="sb-hifi-hub__thumb">택배</div>
                        <div class="sb-hifi-hub__meta"><strong>기본 택배 송장</strong><span>100×150mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">FREE</span>
                        <div class="sb-hifi-hub__thumb">화장품</div>
                        <div class="sb-hifi-hub__meta"><strong>미니 샘플 라벨</strong><span>30×15mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">FREE</span>
                        <div class="sb-hifi-hub__thumb">네임</div>
                        <div class="sb-hifi-hub__meta"><strong>선물 태그</strong><span>50×80mm</span></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
