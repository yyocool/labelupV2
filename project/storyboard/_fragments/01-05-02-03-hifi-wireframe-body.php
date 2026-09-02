<?php
/**
 * 공유 템플릿 와이어프레임 바디 (01-05-02-03)
 */
$hubActiveNav = 'tpl-share';
$hubPageTitle = '공유 템플릿';
$hubPageSub = '다른 사용자가 만들고 공유한 템플릿을 둘러보세요';
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
            <nav class="sb-hifi-hub__tabs sb-wf-zone" data-zone-id="M-02" aria-label="정렬 탭">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>
                <button type="button" class="sb-hifi-hub__tab is-active">인기</button>
                <button type="button" class="sb-hifi-hub__tab">최신</button>
                <button type="button" class="sb-hifi-hub__tab">다운로드</button>
            </nav>

            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-03">
                <span class="sb-wf-zone-label">M-03</span>
                <div class="sb-hifi-hub__grid sb-hifi-hub__grid--4">
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--share">공유</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--share">택배 송장</div>
                        <div class="sb-hifi-hub__meta"><strong>인기 택배 송장</strong><span>@라벨장인 · ↓1.2k</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--share">공유</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--share">물류 바코드</div>
                        <div class="sb-hifi-hub__meta"><strong>물류 바코드 라벨</strong><span>@박스마스터 · ↓890</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--share">공유</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--share">카페 원두</div>
                        <div class="sb-hifi-hub__meta"><strong>카페 원두 라벨</strong><span>@카페온 · ↓654</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--share">공유</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--share">화장품</div>
                        <div class="sb-hifi-hub__meta"><strong>화장품 심플 라벨</strong><span>@뷰티랩 · ↓512</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--share">공유</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--share">매장 가격표</div>
                        <div class="sb-hifi-hub__meta"><strong>매장 가격표 라벨</strong><span>@마켓운영자 · ↓430</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--share">공유</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--share">선물 태그</div>
                        <div class="sb-hifi-hub__meta"><strong>감성 선물 태그</strong><span>@디자인러버 · ↓388</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--share">공유</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--share">농산물</div>
                        <div class="sb-hifi-hub__meta"><strong>농산물 직거래 라벨</strong><span>@농부장터 · ↓301</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--share">공유</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--share">공방 씰</div>
                        <div class="sb-hifi-hub__meta"><strong>수공예 공방 씰</strong><span>@핸드메이드 · ↓276</span></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
