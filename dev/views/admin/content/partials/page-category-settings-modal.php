<div id="productPageCategorySettingsModal" class="admin-modal" hidden>
  <div class="admin-modal-backdrop js-page-category-settings-close"></div>
  <div class="admin-modal-dialog admin-modal-dialog--product admin-modal-dialog--page-category" role="dialog" aria-modal="true" aria-labelledby="productPageCategorySettingsTitle">
    <div class="admin-modal-head">
      <h3 id="productPageCategorySettingsTitle">카테고리별 헤더/푸터 설정</h3>
      <button type="button" class="admin-modal-close js-page-category-settings-close" aria-label="닫기">×</button>
    </div>
    <div class="admin-modal-body admin-page-category-layout">
      <aside class="admin-page-category-list" aria-label="쇼핑몰 카테고리">
        <div class="admin-page-category-list__head">
          <strong>카테고리</strong>
          <span class="admin-muted">1차·2차를 각각 저장하면 상품 상세에 둘 다 표시됩니다</span>
        </div>
        <div id="pageCategoryList" class="admin-page-category-list__items"></div>
      </aside>
      <form id="productPageCategorySettingsForm" class="admin-product-form admin-page-category-form">
        <input type="hidden" name="category_id" id="pageCategoryId" value="">
        <p class="admin-page-category-current">선택: <strong id="pageCategoryName">카테고리를 선택하세요</strong></p>
        <p class="admin-muted" id="pageCategoryStackHint" hidden>2차 카테고리입니다. 1차 카테고리에 저장한 헤더/푸터와 이 설정이 상품 상세·미리보기에 함께 표시됩니다.</p>
        <section class="admin-hashtag-section">
          <h4 class="admin-product-section-title">해시태그</h4>
          <p class="admin-muted">상품 상세 제목 아래에 둥근 태그로 표시됩니다. 최대 8개, 입력 후 Enter로 추가합니다.</p>
          <div id="pageCategoryHashtags" class="admin-hashtag-list" aria-live="polite"></div>
          <div class="admin-hashtag-add">
            <input type="text" id="pageCategoryHashtagInput" maxlength="20" placeholder="해시태그 입력" autocomplete="off" disabled>
            <button type="button" class="admin-btn admin-btn--sm" id="pageCategoryHashtagAdd" disabled>추가</button>
          </div>
        </section>
        <section>
          <h4 class="admin-product-section-title">카테고리 헤더</h4>
          <div class="admin-product-form-grid">
            <div class="admin-field admin-field--full admin-field--images">
              <label>헤더 이미지</label>
              <div class="admin-category-image-wrap" id="pageCatHeaderImagePreview"></div>
              <input type="file" id="pageCatHeaderImageInput" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
              <div class="admin-category-image-actions">
                <button type="button" class="admin-btn admin-btn--sm" id="pageCatHeaderImageAdd">+ 이미지 업로드</button>
                <button type="button" class="admin-btn admin-btn--sm" id="pageCatHeaderImageRemove" disabled>삭제</button>
              </div>
              <input type="hidden" name="header_image" id="pageCatHeaderImagePath" value="">
            </div>
            <div class="admin-field admin-field--full admin-field--editor">
              <label for="pageCatHeaderHtml">헤더 내용</label>
              <textarea id="pageCatHeaderHtml" class="js-page-cat-header-html" rows="8" placeholder="이 카테고리 상품 상세에만 적용할 헤더 내용"></textarea>
            </div>
          </div>
        </section>
        <section>
          <h4 class="admin-product-section-title">카테고리 푸터</h4>
          <div class="admin-product-form-grid">
            <div class="admin-field admin-field--full admin-field--images">
              <label>푸터 이미지</label>
              <div class="admin-category-image-wrap" id="pageCatFooterImagePreview"></div>
              <input type="file" id="pageCatFooterImageInput" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
              <div class="admin-category-image-actions">
                <button type="button" class="admin-btn admin-btn--sm" id="pageCatFooterImageAdd">+ 이미지 업로드</button>
                <button type="button" class="admin-btn admin-btn--sm" id="pageCatFooterImageRemove" disabled>삭제</button>
              </div>
              <input type="hidden" name="footer_image" id="pageCatFooterImagePath" value="">
            </div>
            <div class="admin-field admin-field--full admin-field--editor">
              <label for="pageCatFooterHtml">푸터 내용</label>
              <textarea id="pageCatFooterHtml" class="js-page-cat-footer-html" rows="8" placeholder="이 카테고리 상품 상세에만 적용할 푸터 내용"></textarea>
            </div>
          </div>
        </section>
        <p class="admin-muted">표시 순서: 공통 헤더 → 카테고리 헤더(1차·2차) → 상품 상세 → 카테고리 푸터(2차·1차) → 공통 푸터. 비어 있는 단계는 건너뜁니다.</p>
      </form>
    </div>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn js-page-category-settings-close">취소</button>
      <button type="submit" form="productPageCategorySettingsForm" class="admin-btn admin-btn--primary" id="pageCategorySaveBtn" disabled>저장</button>
    </div>
  </div>
</div>
