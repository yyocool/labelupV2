<?php
/**
 * 내 디자인 와이어프레임 바디 (01-05-01-02)
 */
$hubActiveNav = 'mine';
$hubPageTitle = '내 디자인';
$hubPageSub = '작업중이거나 저장·공유한 디자인을 한곳에서 관리하세요';
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
            <nav class="sb-hifi-hub__tabs sb-wf-zone" data-zone-id="M-02" aria-label="디자인 상태 탭">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>
                <button type="button" class="sb-hifi-hub__tab is-active">전체 24</button>
                <button type="button" class="sb-hifi-hub__tab">작업중 6</button>
                <button type="button" class="sb-hifi-hub__tab">저장완료 12</button>
                <button type="button" class="sb-hifi-hub__tab">공유 4</button>
                <button type="button" class="sb-hifi-hub__tab">즐겨찾기 3</button>
            </nav>

            <div class="sb-hifi-hub__toolbar sb-wf-zone" data-zone-id="M-04">
                <span class="sb-wf-zone-label">M-04</span>
                <div class="sb-hifi-hub__search"><span>⌕</span> 디자인 이름·규격 검색</div>
                <div class="sb-hifi-hub__chips">
                    <span class="sb-hifi-hub__chip is-active">최근 수정순</span>
                    <span class="sb-hifi-hub__chip">이름순</span>
                    <span class="sb-hifi-hub__chip">규격순</span>
                </div>
            </div>

            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-03">
                <span class="sb-wf-zone-label">M-03</span>
                <div class="sb-hifi-hub__grid sb-hifi-hub__grid--4">
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge">작업중</span>
                        <div class="sb-hifi-hub__thumb">80×50mm</div>
                        <div class="sb-hifi-hub__meta"><strong>식품 라벨 A안</strong><span>80×50mm · 10분 전 수정</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">저장완료</span>
                        <div class="sb-hifi-hub__thumb">100×150mm</div>
                        <div class="sb-hifi-hub__meta"><strong>택배 송장 라벨</strong><span>100×150mm · 어제</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--share">공유중</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--share">40×30mm</div>
                        <div class="sb-hifi-hub__meta"><strong>화장품 원형 라벨</strong><span>40×30mm · 팀 공유 3명</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--pro">★ 즐겨찾기</span>
                        <div class="sb-hifi-hub__thumb">A4 시트</div>
                        <div class="sb-hifi-hub__meta"><strong>네임 카드 21칸</strong><span>A4 시트 · 3일 전</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge">작업중</span>
                        <div class="sb-hifi-hub__thumb">60×40mm</div>
                        <div class="sb-hifi-hub__meta"><strong>물류 바코드 라벨</strong><span>60×40mm · 1시간 전</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">저장완료</span>
                        <div class="sb-hifi-hub__thumb">지름 30mm</div>
                        <div class="sb-hifi-hub__meta"><strong>원형 씰 스티커</strong><span>지름 30mm · 5일 전</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--free">저장완료</span>
                        <div class="sb-hifi-hub__thumb">70×100mm</div>
                        <div class="sb-hifi-hub__meta"><strong>화장품 세트 박스</strong><span>70×100mm · 1주 전</span></div>
                    </div>
                    <div class="sb-hifi-hub__card">
                        <span class="sb-hifi-hub__badge sb-hifi-hub__badge--share">공유중</span>
                        <div class="sb-hifi-hub__thumb sb-hifi-hub__thumb--share">90×50mm</div>
                        <div class="sb-hifi-hub__meta"><strong>매장 가격 라벨</strong><span>90×50mm · 2주 전</span></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
