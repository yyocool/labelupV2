<?php
/** 데이터 가져오기 팝업 — 엑셀 · CSV · 아이라벨 · 폼택 */

$sbDataImportFormats = array(
    array(
        'id' => 'excel',
        'label' => '엑셀',
        'icon' => '📊',
        'accept' => '.xlsx,.xls',
        'ext' => 'XLSX · XLS',
        'hint' => 'Microsoft Excel 데이터 파일',
        'tip' => '첫 행을 필드명(헤더)으로 사용하면 자동으로 변수에 매핑됩니다.',
        'active' => true,
    ),
    array(
        'id' => 'csv',
        'label' => 'CSV',
        'icon' => '📄',
        'accept' => '.csv,.txt',
        'ext' => 'CSV · TXT',
        'hint' => '쉼표(,) 구분 데이터 파일',
        'tip' => 'UTF-8 인코딩 CSV를 권장합니다. 구분자는 쉼표(,)를 사용합니다.',
    ),
    array(
        'id' => 'ilabel',
        'label' => '아이라벨',
        'icon' => 'iL',
        'accept' => '.lbl,.xml,.zip',
        'ext' => 'LBL · XML · ZIP',
        'hint' => 'iLabel2 데이터 내보내기 파일',
        'tip' => 'iLabel2에서 내보낸 데이터 파일을 그대로 업로드할 수 있습니다.',
        'logo' => 'ilabel',
    ),
    array(
        'id' => 'formtec',
        'label' => '폼택',
        'icon' => 'F9',
        'accept' => '.fmt,.fdx,.zip',
        'ext' => 'FMT · FDX · ZIP',
        'hint' => '폼텍 디자인프로9 데이터 파일',
        'tip' => '폼텍 디자인프로9(고도화)에서 저장한 데이터 파일을 지원합니다.',
        'logo' => 'formtec',
    ),
);
?>
<div class="sb-ed-data-import-overlay sb-wf-zone" data-zone-id="D-01" data-sb-data-import-overlay aria-hidden="true" role="dialog" aria-label="데이터 가져오기">
    <span class="sb-wf-zone-label sb-wf-zone-label--purple">D-01</span>
    <div class="sb-ed-data-import-dialog">
        <header class="sb-ed-data-import__head">
            <h3>데이터 가져오기</h3>
            <p>엑셀, CSV, 아이라벨, 폼택 파일을 업로드하여 라벨 데이터를 연동합니다.</p>
            <button type="button" class="sb-ed-data-import__close" data-sb-data-import-close aria-label="닫기" onclick="return sbEdCloseDataImport(this);">×</button>
        </header>

        <nav class="sb-ed-data-import__tabs" role="tablist" aria-label="데이터 포맷">
            <?php foreach ($sbDataImportFormats as $fmt): ?>
            <button type="button"
                class="sb-ed-data-import__tab<?= !empty($fmt['active']) ? ' is-active' : '' ?>"
                data-sb-data-import-tab="<?= e($fmt['id']) ?>"
                role="tab"
                aria-selected="<?= !empty($fmt['active']) ? 'true' : 'false' ?>">
                <?php if (!empty($fmt['logo'])): ?>
                <span class="sb-ed-data-import__tab-logo sb-ed-data-import__tab-logo--<?= e($fmt['logo']) ?>" aria-hidden="true"><?= e($fmt['icon']) ?></span>
                <?php else: ?>
                <span class="sb-ed-data-import__tab-icon" aria-hidden="true"><?= e($fmt['icon']) ?></span>
                <?php endif; ?>
                <span><?= e($fmt['label']) ?></span>
            </button>
            <?php endforeach; ?>
        </nav>

        <div class="sb-ed-data-import__body">
            <?php foreach ($sbDataImportFormats as $fmt): ?>
            <div class="sb-ed-data-import__panel"
                data-sb-data-import-panel="<?= e($fmt['id']) ?>"
                <?= empty($fmt['active']) ? 'hidden' : '' ?>>
                <div class="sb-ed-data-import__format-head">
                    <?php if (!empty($fmt['logo'])): ?>
                    <span class="sb-ed-data-import__format-logo sb-ed-data-import__format-logo--<?= e($fmt['logo']) ?>"><?= e($fmt['icon']) ?></span>
                    <?php else: ?>
                    <span class="sb-ed-data-import__format-icon" aria-hidden="true"><?= e($fmt['icon']) ?></span>
                    <?php endif; ?>
                    <div>
                        <strong><?= e($fmt['label']) ?> 파일</strong>
                        <span><?= e($fmt['ext']) ?> · <?= e($fmt['hint']) ?></span>
                    </div>
                </div>

                <label class="sb-ed-data-import__dropzone" data-sb-data-import-drop="<?= e($fmt['id']) ?>">
                    <input type="file"
                        class="sb-ed-data-import__file-input"
                        accept="<?= e($fmt['accept']) ?>"
                        data-sb-data-import-file="<?= e($fmt['id']) ?>"
                        tabindex="-1">
                    <span class="sb-ed-data-import__drop-icon" aria-hidden="true">📁</span>
                    <span class="sb-ed-data-import__drop-title">파일을 드래그하거나 클릭하여 선택</span>
                    <span class="sb-ed-data-import__drop-hint"><?= e($fmt['hint']) ?></span>
                    <span class="sb-ed-data-import__drop-name" data-sb-data-import-filename hidden></span>
                </label>

                <p class="sb-ed-data-import__tip">💡 <?= e($fmt['tip']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <footer class="sb-ed-data-import__foot">
            <button type="button" class="sb-ed-data-import__btn sb-ed-data-import__btn--outline" data-sb-data-import-close onclick="return sbEdCloseDataImport(this);">취소</button>
            <button type="button" class="sb-ed-data-import__btn sb-ed-data-import__btn--primary" data-sb-data-import-submit>가져오기</button>
        </footer>
    </div>
</div>
