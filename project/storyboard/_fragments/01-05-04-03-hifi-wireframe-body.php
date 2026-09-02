<?php
/**
 * 데이터 연동 와이어프레임 바디 (01-05-04-03)
 */
$hubActiveNav = 'data';
$hubPageTitle = '데이터 연동';
$hubPageSub = 'Excel·CSV로 여러 건의 라벨을 한 번에 생성하세요';
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
                <h2>데이터 업로드</h2>
                <div class="sb-hifi-hub__grid sb-hifi-hub__grid--2">
                    <div style="min-height:110px;border:1.5px dashed #cbd5e1;border-radius:12px;background:#f8fafc;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:#94a3b8;font-size:12px">
                        <span style="font-size:20px">📊</span>
                        <strong style="color:#475569;font-size:13px">Excel 업로드</strong>
                        <span>.xlsx 파일을 드래그하거나 클릭</span>
                    </div>
                    <div style="min-height:110px;border:1.5px dashed #cbd5e1;border-radius:12px;background:#f8fafc;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:#94a3b8;font-size:12px">
                        <span style="font-size:20px">📄</span>
                        <strong style="color:#475569;font-size:13px">CSV 업로드</strong>
                        <span>.csv 파일을 드래그하거나 클릭</span>
                    </div>
                </div>
            </section>

            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-03">
                <span class="sb-wf-zone-label">M-03</span>
                <h2>필드 매핑</h2>
                <table class="sb-hifi-hub__table">
                    <thead><tr><th>데이터 필드</th><th>라벨 요소</th><th>예시 값</th></tr></thead>
                    <tbody>
                        <tr><td>product_name</td><td>텍스트 · 제품명</td><td>유기농 원두 200g</td></tr>
                        <tr><td>barcode</td><td>바코드 · CODE128</td><td>8801234567890</td></tr>
                        <tr><td>price</td><td>텍스트 · 가격</td><td>12,900원</td></tr>
                        <tr><td>expire_date</td><td>텍스트 · 유통기한</td><td>2026-12-31</td></tr>
                    </tbody>
                </table>
            </section>

            <div class="sb-hifi-hub__banner sb-wf-zone" data-zone-id="M-04">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-04</span>
                <div>
                    <strong>총 240건의 데이터가 감지되었습니다</strong>
                    <p>매핑을 확인한 뒤 일괄 생성을 진행하세요</p>
                </div>
                <span class="sb-hifi-hub__btn sb-hifi-hub__btn--primary">일괄 생성</span>
            </div>
        </div>
    </div>
</div>
