<?php
/** 라벨·태그 규격 상세 사양 팝업 */
if (!isset($sbImportSpecMap)) {
    require __DIR__ . '/01-05-import-catalog-data.php';
}
?>
<div class="sb-ed-spec-detail-overlay" data-sb-spec-detail-overlay aria-hidden="true" role="dialog" aria-label="상세 사양">
    <div class="sb-ed-spec-detail-dialog">
        <header class="sb-ed-spec-detail__head">
            <h3 class="sb-ed-spec-detail__title" data-sb-spec-detail-title>규격 상세 사양</h3>
            <button type="button" class="sb-ed-spec-detail__close" data-sb-spec-detail-close aria-label="닫기">×</button>
        </header>

        <div class="sb-ed-spec-detail__body">
            <div class="sb-ed-spec-detail__diagram-wrap">
                <div class="sb-ed-spec-detail__diagram" data-sb-spec-detail-diagram aria-hidden="true"></div>
            </div>
            <dl class="sb-ed-spec-detail__specs" data-sb-spec-detail-specs></dl>
        </div>

        <footer class="sb-ed-spec-detail__foot">
            <button type="button" class="sb-ed-spec-detail__btn sb-ed-spec-detail__btn--outline" data-sb-spec-detail-action="buy">
                <span>용지 구매하기</span>
                <span class="sb-ed-spec-detail__btn-icon sb-ed-spec-detail__btn-icon--info" aria-hidden="true">i</span>
            </button>
            <button type="button" class="sb-ed-spec-detail__btn sb-ed-spec-detail__btn--primary" data-sb-spec-detail-action="edit">
                <span>디자인 편집하기</span>
                <span class="sb-ed-spec-detail__btn-icon sb-ed-spec-detail__btn-icon--edit" aria-hidden="true">✎</span>
            </button>
        </footer>
    </div>
</div>
<script type="application/json" id="sb-import-spec-map"><?= json_encode($sbImportSpecMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
