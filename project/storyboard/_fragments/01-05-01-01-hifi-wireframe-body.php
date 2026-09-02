<?php
/**
 * 새 디자인 만들기 와이어프레임 바디 (01-05-01-01)
 */
$hubActiveNav = 'new';
$hubPageTitle = '새 디자인 만들기';
$hubPageSub = '원하는 방식으로 새 라벨 디자인을 시작하세요';
$hubTopActionsHtml = '<span class="sb-hifi-hub__btn">📁 내 디자인</span>';
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
                <h2>어떻게 시작할까요?</h2>
                <p class="lead">4가지 방법 중 하나를 선택해 새 디자인을 만들어보세요</p>
                <div class="sb-hifi-hub__options">
                    <div class="sb-hifi-hub__option">
                        <div class="sb-hifi-hub__option-icon">📐</div>
                        <strong>규격 선택</strong>
                        <span>제조사·용도별 규격에서 시작</span>
                    </div>
                    <div class="sb-hifi-hub__option">
                        <div class="sb-hifi-hub__option-icon">▭</div>
                        <strong>빈 템플릿</strong>
                        <span>사이즈만 정하고 자유롭게 디자인</span>
                    </div>
                    <div class="sb-hifi-hub__option">
                        <div class="sb-hifi-hub__option-icon">✦</div>
                        <strong>AI 생성</strong>
                        <span>프롬프트·이미지로 AI 시안 생성</span>
                    </div>
                    <div class="sb-hifi-hub__option">
                        <div class="sb-hifi-hub__option-icon">⇪</div>
                        <strong>PDF 업로드</strong>
                        <span>기존 파일을 불러와 다시 편집</span>
                    </div>
                </div>
            </section>

            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-03">
                <span class="sb-wf-zone-label">M-03</span>
                <h2>최근 사용한 규격</h2>
                <p class="lead">최근에 사용했던 규격으로 빠르게 시작하세요</p>
                <div class="sb-hifi-hub__chips">
                    <span class="sb-hifi-hub__chip is-active">80×50mm 사각</span>
                    <span class="sb-hifi-hub__chip">100×150mm 택배</span>
                    <span class="sb-hifi-hub__chip">40×30mm 원형</span>
                    <span class="sb-hifi-hub__chip">A4 시트 21칸</span>
                    <span class="sb-hifi-hub__chip">⌕ 규격 더 찾아보기</span>
                </div>
            </section>
        </div>
    </div>
</div>
