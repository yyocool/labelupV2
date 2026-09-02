<?php
/**
 * 디자인 편집 와이어프레임 바디 (01-05-04-01)
 */
$hubActiveNav = 'edit';
$hubPageTitle = '디자인 편집';
$hubPageSub = '캔버스와 속성 패널을 미리 살펴보세요 · 실제 작업은 전체 편집기에서';
$hubTopActionsHtml = '<span class="sb-hifi-hub__btn sb-hifi-hub__btn--primary">✎ 편집기로 열기</span>';
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
                <h2>캔버스 미리보기</h2>
                <p class="lead">텍스트·이미지·바코드·표·도형을 추가하는 전체 편집기는 01-05에서 제공됩니다</p>
                <div style="display:flex;gap:14px;align-items:stretch">
                    <div class="sb-hifi-hub__tool" style="min-height:auto;width:96px;display:flex;flex-direction:column;align-items:center;gap:10px;padding:14px 8px">
                        <div class="sb-hifi-hub__option-icon" title="텍스트">T</div>
                        <div class="sb-hifi-hub__option-icon" title="이미지">▨</div>
                        <div class="sb-hifi-hub__option-icon" title="바코드">▥</div>
                        <div class="sb-hifi-hub__option-icon" title="표">▦</div>
                        <div class="sb-hifi-hub__option-icon" title="도형">◯</div>
                    </div>
                    <div style="flex:1;min-height:220px;border:1.5px dashed #cbd5e1;border-radius:12px;background:#f8fafc;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:#94a3b8;font-size:12px">
                        <strong style="color:#475569;font-size:13px">캔버스 (80×50mm)</strong>
                        <span>전체 편집 기능은 01-05 라벨 편집기에서 제공됩니다</span>
                    </div>
                </div>
            </section>

            <section class="sb-hifi-hub__panel sb-wf-zone" data-zone-id="M-03">
                <span class="sb-wf-zone-label">M-03</span>
                <h2>속성 패널 미리보기</h2>
                <p class="lead">오브젝트를 선택하면 우측 속성 패널에서 세부 값을 조정합니다</p>
                <div class="sb-hifi-hub__form-grid">
                    <div class="sb-hifi-hub__field"><label>위치 X / Y</label><div class="box">12mm / 8mm</div></div>
                    <div class="sb-hifi-hub__field"><label>크기 W / H</label><div class="box">56mm / 20mm</div></div>
                    <div class="sb-hifi-hub__field"><label>폰트</label><div class="box">Pretendard · 12pt</div></div>
                    <div class="sb-hifi-hub__field"><label>색상</label><div class="box">■ #0F172A</div></div>
                </div>
            </section>
        </div>
    </div>
</div>
