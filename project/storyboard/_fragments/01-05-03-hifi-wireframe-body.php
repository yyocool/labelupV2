<?php
/**
 * 규격 검색 와이어프레임 바디 (01-05-03)
 */
$hubActiveNav = 'spec';
$hubPageTitle = '규격 검색';
$hubPageSub = '제조사·형태·용도로 원하는 규격을 빠르게 찾아보세요';
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
            <div class="sb-hifi-hub__search" style="margin-left:auto;max-width:280px"><span>⌕</span> 규격번호·치수 검색</div>
        </header>
        <div class="sb-hifi-hub__scroll">
            <div class="sb-hifi-hub__split">
                <aside class="sb-hifi-hub__filters sb-wf-zone" data-zone-id="M-02">
                    <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>
                    <h3>필터</h3>
                    <div class="sb-hifi-hub__filter-group">
                        <strong>제조사</strong>
                        <div class="sb-hifi-hub__check is-on"><i></i> 폼텍</div>
                        <div class="sb-hifi-hub__check"><i></i> 아이라벨</div>
                        <div class="sb-hifi-hub__check"><i></i> 다이소</div>
                        <div class="sb-hifi-hub__check"><i></i> 애니라벨</div>
                    </div>
                    <div class="sb-hifi-hub__filter-group">
                        <strong>형태</strong>
                        <div class="sb-hifi-hub__check is-on"><i></i> 사각형</div>
                        <div class="sb-hifi-hub__check"><i></i> 원형</div>
                        <div class="sb-hifi-hub__check"><i></i> 타원형</div>
                        <div class="sb-hifi-hub__check"><i></i> 자유형</div>
                    </div>
                    <div class="sb-hifi-hub__filter-group">
                        <strong>용도</strong>
                        <div class="sb-hifi-hub__check"><i></i> 식품</div>
                        <div class="sb-hifi-hub__check"><i></i> 화장품</div>
                        <div class="sb-hifi-hub__check"><i></i> 물류·택배</div>
                        <div class="sb-hifi-hub__check"><i></i> 오피스·네임</div>
                    </div>
                    <span class="sb-hifi-hub__btn" style="width:100%;justify-content:center">필터 초기화</span>
                </aside>

                <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-03" style="margin-bottom:0">
                    <span class="sb-wf-zone-label">M-03</span>
                    <h2>검색 결과 <span style="color:#64748b;font-weight:600;font-size:12px">128건</span></h2>
                    <table class="sb-hifi-hub__table">
                        <thead>
                            <tr><th>규격번호</th><th>치수</th><th>제조사</th><th>호환</th><th></th></tr>
                        </thead>
                        <tbody>
                            <tr><td>3106</td><td>80×50mm (24칸)</td><td>폼텍</td><td><span class="sb-hifi-hub__status sb-hifi-hub__status--ok">호환</span></td><td>이 규격으로 시작 ›</td></tr>
                            <tr><td>SL-100</td><td>100×150mm (택배)</td><td>아이라벨</td><td><span class="sb-hifi-hub__status sb-hifi-hub__status--ok">호환</span></td><td>이 규격으로 시작 ›</td></tr>
                            <tr><td>R-40</td><td>지름 40mm (원형)</td><td>애니라벨</td><td><span class="sb-hifi-hub__status sb-hifi-hub__status--warn">부분 호환</span></td><td>이 규격으로 시작 ›</td></tr>
                            <tr><td>3660</td><td>70×46.5mm (18칸)</td><td>폼텍</td><td><span class="sb-hifi-hub__status sb-hifi-hub__status--ok">호환</span></td><td>이 규격으로 시작 ›</td></tr>
                            <tr><td>DS-21</td><td>A4 시트 (21칸)</td><td>다이소</td><td><span class="sb-hifi-hub__status sb-hifi-hub__status--ship">배송지 라벨</span></td><td>이 규격으로 시작 ›</td></tr>
                            <tr><td>3108</td><td>90×34mm (16칸)</td><td>폼텍</td><td><span class="sb-hifi-hub__status sb-hifi-hub__status--ok">호환</span></td><td>이 규격으로 시작 ›</td></tr>
                        </tbody>
                    </table>
                </section>
            </div>
        </div>
    </div>
</div>
