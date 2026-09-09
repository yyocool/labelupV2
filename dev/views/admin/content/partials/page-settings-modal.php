<div id="productPageSettingsModal" class="admin-modal" hidden>
  <div class="admin-modal-backdrop js-page-settings-close"></div>
  <div class="admin-modal-dialog admin-modal-dialog--product" role="dialog" aria-modal="true" aria-labelledby="productPageSettingsTitle">
    <div class="admin-modal-head">
      <h3 id="productPageSettingsTitle">상품 상세 페이지설정</h3>
      <button type="button" class="admin-modal-close js-page-settings-close" aria-label="닫기">×</button>
    </div>
    <form id="productPageSettingsForm" class="admin-modal-body admin-product-form">
      <section>
        <h4 class="admin-product-section-title">공통 헤더</h4>
        <div class="admin-product-form-grid">
          <div class="admin-field admin-field--full admin-field--images">
            <label>헤더 이미지</label>
            <div class="admin-category-image-wrap" id="pageHeaderImagePreview"></div>
            <input type="file" id="pageHeaderImageInput" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
            <div class="admin-category-image-actions">
              <button type="button" class="admin-btn admin-btn--sm" id="pageHeaderImageAdd">+ 이미지 업로드</button>
              <button type="button" class="admin-btn admin-btn--sm" id="pageHeaderImageRemove" disabled>삭제</button>
            </div>
            <input type="hidden" name="header_image" id="pageHeaderImagePath" value="">
          </div>
          <div class="admin-field admin-field--full admin-field--editor">
            <label for="pageHeaderHtml">헤더 내용</label>
            <textarea id="pageHeaderHtml" class="js-page-header-html" rows="8" placeholder="상품 상세 내용 바로 위에 공통으로 표시할 내용을 입력하세요."></textarea>
          </div>
        </div>
      </section>
      <section>
        <h4 class="admin-product-section-title">공통 푸터</h4>
        <div class="admin-product-form-grid">
          <div class="admin-field admin-field--full admin-field--images">
            <label>푸터 이미지</label>
            <div class="admin-category-image-wrap" id="pageFooterImagePreview"></div>
            <input type="file" id="pageFooterImageInput" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
            <div class="admin-category-image-actions">
              <button type="button" class="admin-btn admin-btn--sm" id="pageFooterImageAdd">+ 이미지 업로드</button>
              <button type="button" class="admin-btn admin-btn--sm" id="pageFooterImageRemove" disabled>삭제</button>
            </div>
            <input type="hidden" name="footer_image" id="pageFooterImagePath" value="">
          </div>
          <div class="admin-field admin-field--full admin-field--editor">
            <label for="pageFooterHtml">푸터 내용</label>
            <textarea id="pageFooterHtml" class="js-page-footer-html" rows="8" placeholder="상품 상세 내용 바로 아래에 공통으로 표시할 내용을 입력하세요."></textarea>
          </div>
        </div>
      </section>
      <p class="admin-muted">헤더·푸터는 상품 구매 정보(이미지·가격) 아래, 상세 내용의 위·아래에 가로 100%로 공통 적용됩니다.</p>
    </form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn js-page-settings-close">취소</button>
      <button type="submit" form="productPageSettingsForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>
