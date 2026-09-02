<?php
/**
 * 출력·저장 와이어프레임 바디 (01-05-04-04)
 */
$hubActiveNav = 'export';
$hubPageTitle = '출력·저장';
$hubPageSub = '완성한 디자인을 원하는 형식으로 내보내거나 저장하세요';
$hubTopActionsHtml = '<span class="sb-hifi-hub__btn">✎ 편집기로 열기</span>';
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
            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-02">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>
                <h2>내보내기 방식 선택</h2>
                <div class="sb-hifi-hub__grid sb-hifi-hub__grid--3">
                    <div class="sb-hifi-hub__option">
                        <div class="sb-hifi-hub__option-icon">📄</div>
                        <strong>PDF</strong>
                        <span>인쇄용 고해상도 PDF로 내보내기</span>
                    </div>
                    <div class="sb-hifi-hub__option">
                        <div class="sb-hifi-hub__option-icon">🖼</div>
                        <strong>PNG</strong>
                        <span>웹·미리보기용 이미지로 내보내기</span>
                    </div>
                    <div class="sb-hifi-hub__option">
                        <div class="sb-hifi-hub__option-icon">⎙</div>
                        <strong>직접 출력</strong>
                        <span>연결된 프린터로 바로 인쇄</span>
                    </div>
                </div>
            </section>

            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-03">
                <span class="sb-wf-zone-label">M-03</span>
                <h2>인쇄 설정</h2>
                <div class="sb-hifi-hub__form-grid">
                    <div class="sb-hifi-hub__field"><label>용지 크기</label><div class="box">A4 (210×297mm)</div></div>
                    <div class="sb-hifi-hub__field"><label>매수</label><div class="box">1매</div></div>
                    <div class="sb-hifi-hub__field"><label>컬러 모드</label><div class="box">컬러</div></div>
                    <div class="sb-hifi-hub__field"><label>페이지당 수량</label><div class="box">21칸 (규격 기준)</div></div>
                </div>
                <span class="sb-hifi-hub__btn sb-hifi-hub__btn--primary" style="margin-top:12px">PDF로 내보내기</span>
            </section>

            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-04">
                <span class="sb-wf-zone-label">M-04</span>
                <h2>저장 위치</h2>
                <div class="sb-hifi-hub__list-row">
                    <div class="sb-hifi-hub__list-thumb">💾</div>
                    <div class="sb-hifi-hub__list-body"><strong>내 컴퓨터에 다운로드</strong><span>PDF·PNG 파일로 즉시 저장</span></div>
                </div>
                <div class="sb-hifi-hub__list-row">
                    <div class="sb-hifi-hub__list-thumb">📁</div>
                    <div class="sb-hifi-hub__list-body"><strong>내 디자인에 저장</strong><span>다시 편집할 수 있도록 저장 (01-05-01-02)</span></div>
                </div>
                <div class="sb-hifi-hub__list-row">
                    <div class="sb-hifi-hub__list-thumb">☁</div>
                    <div class="sb-hifi-hub__list-body"><strong>클라우드 연동</strong><span>Google Drive · Dropbox로 내보내기</span></div>
                </div>
                <div class="sb-hifi-hub__list-row">
                    <div class="sb-hifi-hub__list-thumb">🛒</div>
                    <div class="sb-hifi-hub__list-body"><strong>인쇄소로 바로 주문</strong><span>완성 파일로 인쇄 의뢰 바로 진행</span></div>
                </div>
            </section>
        </div>
    </div>
</div>
