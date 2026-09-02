<?php
/**
 * 편집 도구 허브 와이어프레임 바디 (01-05-04)
 */
$hubActiveNav = 'tools';
$hubPageTitle = '편집 도구';
$hubPageSub = '디자인 작업에 필요한 도구를 한곳에 모았어요';
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
            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-02">
                <span class="sb-wf-zone-label">M-02</span>
                <div class="sb-hifi-hub__tools">
                    <div class="sb-hifi-hub__tool">
                        <div class="sb-hifi-hub__option-icon">✎</div>
                        <strong>디자인 편집</strong>
                        <p>캔버스에서 텍스트·이미지·도형을 직접 편집합니다</p>
                    </div>
                    <div class="sb-hifi-hub__tool">
                        <div class="sb-hifi-hub__option-icon">▥</div>
                        <strong>바코드·QR</strong>
                        <p>CODE128·EAN13·QR·WIFI 코드를 생성해 삽입합니다</p>
                    </div>
                    <div class="sb-hifi-hub__tool">
                        <div class="sb-hifi-hub__option-icon">⇄</div>
                        <strong>데이터 연동</strong>
                        <p>Excel·CSV를 불러와 다량의 라벨을 한 번에 생성합니다</p>
                    </div>
                    <div class="sb-hifi-hub__tool">
                        <div class="sb-hifi-hub__option-icon">⎙</div>
                        <strong>출력·저장</strong>
                        <p>PDF·PNG로 내보내거나 인쇄소로 바로 주문합니다</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
