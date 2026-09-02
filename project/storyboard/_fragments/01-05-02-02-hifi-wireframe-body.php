<?php
/**
 * 프리미엄 템플릿 와이어프레임 바디 (01-05-02-02)
 */
$hubActiveNav = 'tpl-pro';
$hubPageTitle = '프리미엄 템플릿';
$hubPageSub = 'Pro 멤버십으로 고급 디자인 템플릿을 무제한 이용하세요';
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
            <div class="sb-hifi-hub__banner sb-wf-zone" data-zone-id="M-02">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>
                <div>
                    <strong>👑 프리미엄 템플릿은 Pro 전용입니다</strong>
                    <p>브랜드 패키지·고급·시즌 템플릿 120종을 워터마크 없이 이용해보세요</p>
                </div>
                <span class="sb-hifi-hub__btn sb-hifi-hub__btn--accent">Pro 업그레이드</span>
            </div>

            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-03">
                <span class="sb-wf-zone-label">M-03</span>
                <div class="sb-hifi-hub__chips">
                    <span class="sb-hifi-hub__chip is-active">전체</span>
                    <span class="sb-hifi-hub__chip sb-hifi-hub__chip--pro">브랜드 패키지</span>
                    <span class="sb-hifi-hub__chip sb-hifi-hub__chip--pro">고급</span>
                    <span class="sb-hifi-hub__chip sb-hifi-hub__chip--pro">시즌</span>
                </div>
            </section>

            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-04">
                <span class="sb-wf-zone-label">M-04</span>
                <div class="sb-hifi-hub__grid sb-hifi-hub__grid--4">
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--pro">PRO 🔒</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--pro">브랜드 패키지</div>
                        <div class="sb-hifi-hub__meta"><strong>프리미엄 브랜드 세트</strong><span>70×100mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--pro">PRO 🔒</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--pro">고급 씰</div>
                        <div class="sb-hifi-hub__meta"><strong>고급 씰 스티커</strong><span>지름 30mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--pro">PRO 🔒</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--pro">시즌 한정</div>
                        <div class="sb-hifi-hub__meta"><strong>가을 시즌 세트</strong><span>90×50mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--pro">PRO 🔒</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--pro">브랜드 패키지</div>
                        <div class="sb-hifi-hub__meta"><strong>미니멀 브랜드 라벨</strong><span>60×40mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--pro">PRO 🔒</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--pro">고급 금박</div>
                        <div class="sb-hifi-hub__meta"><strong>금박 효과 라벨</strong><span>80×50mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--pro">PRO 🔒</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--pro">시즌 한정</div>
                        <div class="sb-hifi-hub__meta"><strong>겨울 선물 세트</strong><span>100×70mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--pro">PRO 🔒</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--pro">브랜드 패키지</div>
                        <div class="sb-hifi-hub__meta"><strong>프리미엄 박스 라벨</strong><span>70×100mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--pro">PRO 🔒</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--pro">고급</div>
                        <div class="sb-hifi-hub__meta"><strong>엠보 텍스처 라벨</strong><span>50×80mm</span></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
