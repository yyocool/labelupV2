<?php
/**
 * 바코드·QR 와이어프레임 바디 (01-05-04-02)
 */
$hubActiveNav = 'barcode';
$hubPageTitle = '바코드·QR';
$hubPageSub = '코드 유형을 고르고 데이터를 입력해 바로 생성하세요';
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
            <nav class="sb-hifi-hub__tabs sb-wf-zone" data-zone-id="M-02" aria-label="코드 유형 탭">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>
                <button type="button" class="sb-hifi-hub__tab is-active">CODE128</button>
                <button type="button" class="sb-hifi-hub__tab">EAN13</button>
                <button type="button" class="sb-hifi-hub__tab">QR</button>
                <button type="button" class="sb-hifi-hub__tab">WIFI</button>
            </nav>

            <div class="sb-hifi-hub__split">
                <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-03" style="margin-bottom:0">
                    <span class="sb-wf-zone-label">M-03</span>
                    <h2>데이터 입력</h2>
                    <div class="sb-hifi-hub__form-grid">
                        <div class="sb-hifi-hub__field"><label>바코드 데이터</label><div class="box">8801234567890</div></div>
                        <div class="sb-hifi-hub__field"><label>크기</label><div class="box">40mm × 15mm</div></div>
                        <div class="sb-hifi-hub__field"><label>색상</label><div class="box">■ #000000</div></div>
                        <div class="sb-hifi-hub__field"><label>여백</label><div class="box">2mm</div></div>
                    </div>
                    <span class="sb-hifi-hub__btn sb-hifi-hub__btn--primary" style="margin-top:12px">캔버스에 추가</span>
                </section>

                <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-04" style="margin-bottom:0">
                    <span class="sb-wf-zone-label">M-04</span>
                    <h2>미리보기</h2>
                    <div style="min-height:160px;border:1.5px dashed #cbd5e1;border-radius:12px;background:#f8fafc;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:#94a3b8;font-size:12px">
                        <span style="font-size:28px;letter-spacing:2px;color:#334155">|||‖|‖|||‖|</span>
                        <strong style="color:#475569;font-size:12px">8801234567890</strong>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
