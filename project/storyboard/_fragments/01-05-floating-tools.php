<?php
/** 편집 캔버스 플로팅 도구바 — 드래그 · 모서리 스냅 */
$sbFloatTools = array(
    array(
        array('id' => 'text', 'label' => '텍스트', 'proto' => 'text'),
        array('id' => 'image', 'label' => '이미지', 'proto' => 'image'),
        array('id' => 'background', 'label' => '배경', 'proto' => 'background', 'asset' => 'background'),
        array('id' => 'template', 'label' => '템플릿', 'proto' => 'template', 'asset' => 'template'),
        array('id' => 'clipart', 'label' => '클립아트', 'proto' => 'clipart', 'asset' => 'clipart'),
        array('id' => 'icon', 'label' => '아이콘', 'proto' => 'icon', 'asset' => 'icon'),
        array('id' => 'shape', 'label' => '도형', 'proto' => 'shape'),
        array('id' => 'table', 'label' => '표', 'proto' => 'table'),
        array('id' => 'barcode', 'label' => '바·QR코드', 'proto' => 'barcode'),
    ),
    array(
        array('id' => 'master', 'label' => '마스터', 'proto' => 'master'),
        array('id' => 'data', 'label' => '데이터', 'proto' => 'data'),
    ),
    array(
        array('id' => 'layer', 'label' => '레이어', 'proto' => 'layers'),
    ),
);
?>
<div class="sb-ed-float-tools sb-wf-zone" data-zone-id="M-03" data-sb-float-tools data-sb-float-corner="tl" data-sb-float-orient="horizontal" aria-label="편집 도구">
    <span class="sb-wf-zone-label">M-03</span>
    <div class="sb-ed-float-tools__bar" data-sb-float-tools-bar>
        <button type="button" class="sb-ed-float-tools__grip" data-sb-float-tools-grip aria-label="도구바 이동" title="드래그하여 이동 · 놓으면 모서리에 자동 정렬">
            <span aria-hidden="true">⋮⋮</span>
        </button>
        <?php foreach ($sbFloatTools as $gi => $group): ?>
            <?php if ($gi > 0): ?><span class="sb-ed-float-tools__divider" aria-hidden="true"></span><?php endif; ?>
            <div class="sb-ed-float-tools__group" role="group">
                <?php foreach ($group as $tool): ?>
                <button type="button"
                    class="sb-ed-float-tools__item"
                    data-sb-proto="<?= e($tool['proto']) ?>"
                    <?php if (!empty($tool['asset'])): ?>data-sb-asset-tool="<?= e($tool['asset']) ?>"<?php endif; ?>
                    <?php if (!empty($tool['layer'])): ?>data-sb-layer="<?= e($tool['layer']) ?>"<?php endif; ?>
                    title="<?= e($tool['label']) ?>">
                    <span class="sb-ed-float-tools__icon sb-ed-float-tools__icon--<?= e($tool['id']) ?>" aria-hidden="true"></span>
                    <span class="sb-ed-float-tools__label"><?= e($tool['label']) ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <span class="sb-ed-float-tools__divider" aria-hidden="true"></span>
        <div class="sb-ed-float-tools__dock-wrap">
            <button type="button" class="sb-ed-float-tools__dock-btn" data-sb-float-tools-dock-toggle aria-label="도구바 설정" title="모서리 · 배치 설정">⌖</button>
            <div class="sb-ed-float-tools__dock-menu" data-sb-float-tools-dock-menu hidden>
                <p class="sb-ed-float-tools__dock-label">모서리 위치</p>
                <div class="sb-ed-float-tools__dock-grid sb-ed-float-tools__dock-grid--corner">
                    <button type="button" data-sb-float-corner="tl" title="좌측 상단">↖</button>
                    <button type="button" data-sb-float-corner="tr" title="우측 상단">↗</button>
                    <button type="button" data-sb-float-corner="bl" title="좌측 하단">↙</button>
                    <button type="button" data-sb-float-corner="br" title="우측 하단">↘</button>
                </div>
                <p class="sb-ed-float-tools__dock-label">아이콘 배치</p>
                <div class="sb-ed-float-tools__dock-grid sb-ed-float-tools__dock-grid--orient">
                    <button type="button" data-sb-float-orient="horizontal" title="가로 배치">⇔ 가로</button>
                    <button type="button" data-sb-float-orient="vertical" title="세로 배치">⇕ 세로</button>
                </div>
            </div>
        </div>
    </div>
</div>
