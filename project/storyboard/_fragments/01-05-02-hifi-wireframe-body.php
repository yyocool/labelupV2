<?php
/**
 * 템플릿 허브 와이어프레임 바디 (01-05-02)
 */
$hubActiveNav = 'tpl';
$hubPageTitle = '템플릿';
$hubPageSub = '무료부터 프리미엄까지, 바로 쓸 수 있는 템플릿을 찾아보세요';
$hubTopActionsHtml = '<span class="sb-hifi-hub__btn sb-hifi-hub__btn--primary">＋ 새 디자인 만들기</span>';
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
                    <strong>✦ 이번 주 추천 템플릿</strong>
                    <p>식품·화장품 시즌 템플릿 20종이 새로 추가됐어요</p>
                </div>
                <span class="sb-hifi-hub__btn sb-hifi-hub__btn--primary">둘러보기</span>
            </div>

            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-03">
                <span class="sb-wf-zone-label">M-03</span>
                <h2>카테고리별로 찾기</h2>
                <div class="sb-hifi-hub__grid sb-hifi-hub__grid--3">
                    <div class="sb-hifi-hub__tool">
                        <div class="sb-hifi-hub__option-icon">🆓</div>
                        <strong>무료 템플릿</strong>
                        <p>누구나 바로 사용 가능한 기본 템플릿</p>
                    </div>
                    <div class="sb-hifi-hub__tool">
                        <div class="sb-hifi-hub__option-icon">👑</div>
                        <strong>프리미엄 템플릿</strong>
                        <p>Pro 멤버십 전용 고급 디자인 템플릿</p>
                    </div>
                    <div class="sb-hifi-hub__tool">
                        <div class="sb-hifi-hub__option-icon">👥</div>
                        <strong>공유 템플릿</strong>
                        <p>다른 사용자가 만들고 공유한 템플릿</p>
                    </div>
                </div>
            </section>

            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-04">
                <span class="sb-wf-zone-label">M-04</span>
                <h2>추천 템플릿</h2>
                <div class="sb-hifi-hub__grid sb-hifi-hub__grid--4">
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">FREE</span>
                        <div class="sb-hifi-hub__thumb">식품 라벨</div>
                        <div class="sb-hifi-hub__meta"><strong>기본 식품 라벨</strong><span>80×50mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--pro">PRO</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--pro">브랜드 패키지</div>
                        <div class="sb-hifi-hub__meta"><strong>프리미엄 브랜드 세트</strong><span>70×100mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--share">공유</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--share">택배 송장</div>
                        <div class="sb-hifi-hub__meta"><strong>인기 택배 송장</strong><span>100×150mm · ↓1.2k</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">FREE</span>
                        <div class="sb-hifi-hub__thumb">화장품</div>
                        <div class="sb-hifi-hub__meta"><strong>화장품 원형 라벨</strong><span>40×30mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--pro">PRO</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--pro">시즌 한정</div>
                        <div class="sb-hifi-hub__meta"><strong>가을 시즌 세트</strong><span>90×50mm</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">FREE</span>
                        <div class="sb-hifi-hub__thumb">네임 카드</div>
                        <div class="sb-hifi-hub__meta"><strong>네임 카드 21칸</strong><span>A4 시트</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--share">공유</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--share">물류 바코드</div>
                        <div class="sb-hifi-hub__meta"><strong>물류 바코드 라벨</strong><span>60×40mm · ↓890</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--pro">PRO</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--pro">고급 씰</div>
                        <div class="sb-hifi-hub__meta"><strong>고급 씰 스티커</strong><span>지름 30mm</span></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
