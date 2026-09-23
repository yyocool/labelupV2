const PageSettingsAPI = {
  endpoints: {
    get: '/api/admin/shop/product-page-settings',
    save: '/api/admin/shop/product-page-settings/save',
    uploadImages: '/api/admin/shop/product-page-settings/upload-images',
    categoryList: '/api/admin/shop/product-page-category-settings',
    categoryOne: '/api/admin/shop/product-page-category-settings/one',
    categorySave: '/api/admin/shop/product-page-category-settings/save',
    detailGet: '/api/admin/shop/product-detail-page',
    detailSave: '/api/admin/shop/product-detail-page/save',
  },
  async post(path, body) {
    const res = await fetch(path, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '요청 처리 중 오류가 발생했습니다.');
    }
    return data;
  },
  async get(path) {
    const res = await fetch(path, { credentials: 'same-origin' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '요청 처리 중 오류가 발생했습니다.');
    }
    return data;
  },
  async uploadImages(files) {
    const fd = new FormData();
    Array.from(files).forEach((file) => fd.append('images[]', file));
    const res = await fetch(this.endpoints.uploadImages, { method: 'POST', credentials: 'same-origin', body: fd });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '이미지 업로드에 실패했습니다.');
    }
    return data;
  },
};

const PAGE_SETTINGS_EDITOR_OPTS = {
  lang: 'ko-KR',
  height: 220,
  placeholder: '내용을 입력하세요.',
  toolbar: [
    ['style', ['style']],
    ['font', ['bold', 'italic', 'underline', 'clear']],
    ['fontsize', ['fontsize']],
    ['color', ['color']],
    ['para', ['ul', 'ol', 'paragraph']],
    ['insert', ['link', 'picture', 'table', 'hr']],
    ['view', ['fullscreen', 'codeview']],
  ],
  dialogsInBody: true,
};

const pageSettingsState = {
  header_image: '',
  footer_image: '',
};

const pageCategoryState = {
  category_id: 0,
  header_image: '',
  footer_image: '',
  categories: [],
};

function pageSettingsEscHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function pageSettingsResolveUrl(path) {
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  const base = document.querySelector('base')?.href || `${window.location.origin}/`;
  return new URL(path.replace(/^\//, ''), base).href;
}

function destroyEditors(selectors) {
  selectors.forEach((sel) => {
    const ta = document.querySelector(sel);
    if (ta && window.jQuery && jQuery.fn.summernote && jQuery(ta).next('.note-editor').length) {
      jQuery(ta).summernote('destroy');
    }
  });
}

function destroyPageSettingsEditors() {
  destroyEditors(['.js-page-header-html', '.js-page-footer-html']);
}

function destroyPageCategoryEditors() {
  destroyEditors(['.js-page-cat-header-html', '.js-page-cat-footer-html']);
}

function initEditors(pairs) {
  if (!window.jQuery || !jQuery.fn.summernote) return;
  pairs.forEach(([sel, html]) => {
    const ta = document.querySelector(sel);
    if (!ta) return;
    const $el = jQuery(ta);
    if ($el.next('.note-editor').length) $el.summernote('destroy');
    $el.val(html || '');
    $el.summernote(PAGE_SETTINGS_EDITOR_OPTS);
    if (html) $el.summernote('code', html);
  });
}

function initPageSettingsEditors(headerHtml = '', footerHtml = '') {
  initEditors([
    ['.js-page-header-html', headerHtml],
    ['.js-page-footer-html', footerHtml],
  ]);
}

function initPageCategoryEditors(headerHtml = '', footerHtml = '') {
  initEditors([
    ['.js-page-cat-header-html', headerHtml],
    ['.js-page-cat-footer-html', footerHtml],
  ]);
}

function getPageSettingsEditorCode(selector) {
  const ta = document.querySelector(selector);
  if (!ta) return '';
  if (window.jQuery && jQuery.fn.summernote && jQuery(ta).next('.note-editor').length) {
    const code = jQuery(ta).summernote('code') || '';
    if (code === '<p><br></p>' || code === '<p></p>') return '';
    return code;
  }
  return ta.value || '';
}

function renderImagePreview(kind, scope) {
  const isCat = scope === 'category';
  const isHeader = kind === 'header';
  const path = isCat
    ? (isHeader ? pageCategoryState.header_image : pageCategoryState.footer_image)
    : (isHeader ? pageSettingsState.header_image : pageSettingsState.footer_image);
  const ids = isCat
    ? {
      preview: isHeader ? 'pageCatHeaderImagePreview' : 'pageCatFooterImagePreview',
      hidden: isHeader ? 'pageCatHeaderImagePath' : 'pageCatFooterImagePath',
      remove: isHeader ? 'pageCatHeaderImageRemove' : 'pageCatFooterImageRemove',
    }
    : {
      preview: isHeader ? 'pageHeaderImagePreview' : 'pageFooterImagePreview',
      hidden: isHeader ? 'pageHeaderImagePath' : 'pageFooterImagePath',
      remove: isHeader ? 'pageHeaderImageRemove' : 'pageFooterImageRemove',
    };
  const preview = document.getElementById(ids.preview);
  const hidden = document.getElementById(ids.hidden);
  const removeBtn = document.getElementById(ids.remove);
  if (hidden) hidden.value = path || '';
  if (removeBtn) removeBtn.disabled = !path;
  if (!preview) return;
  if (!path) {
    preview.innerHTML = '<span class="admin-muted">등록된 이미지가 없습니다.</span>';
    return;
  }
  const src = pageSettingsResolveUrl(path);
  preview.innerHTML = `<img src="${pageSettingsEscHtml(src)}" alt="${isHeader ? '헤더' : '푸터'} 이미지" class="admin-category-image-preview">`;
}

function bindImageControls(kind, scope) {
  const isCat = scope === 'category';
  const isHeader = kind === 'header';
  const ids = isCat
    ? {
      add: isHeader ? 'pageCatHeaderImageAdd' : 'pageCatFooterImageAdd',
      input: isHeader ? 'pageCatHeaderImageInput' : 'pageCatFooterImageInput',
      remove: isHeader ? 'pageCatHeaderImageRemove' : 'pageCatFooterImageRemove',
    }
    : {
      add: isHeader ? 'pageHeaderImageAdd' : 'pageFooterImageAdd',
      input: isHeader ? 'pageHeaderImageInput' : 'pageFooterImageInput',
      remove: isHeader ? 'pageHeaderImageRemove' : 'pageFooterImageRemove',
    };
  const addBtn = document.getElementById(ids.add);
  const fileInput = document.getElementById(ids.input);
  const removeBtn = document.getElementById(ids.remove);
  if (!addBtn || !fileInput) return;

  addBtn.onclick = () => fileInput.click();
  if (removeBtn) {
    removeBtn.onclick = () => {
      if (isCat) {
        if (isHeader) pageCategoryState.header_image = '';
        else pageCategoryState.footer_image = '';
      } else if (isHeader) pageSettingsState.header_image = '';
      else pageSettingsState.footer_image = '';
      renderImagePreview(kind, scope);
    };
  }
  fileInput.onchange = async () => {
    if (!fileInput.files || !fileInput.files.length) return;
    try {
      const data = await PageSettingsAPI.uploadImages(fileInput.files);
      const url = (data.data?.urls || data.urls || [])[0] || '';
      if (!url) throw new Error('이미지 업로드에 실패했습니다.');
      if (isCat) {
        if (isHeader) pageCategoryState.header_image = url;
        else pageCategoryState.footer_image = url;
      } else if (isHeader) pageSettingsState.header_image = url;
      else pageSettingsState.footer_image = url;
      renderImagePreview(kind, scope);
      if (typeof showAdminAlert === 'function') showAdminAlert('이미지가 업로드되었습니다.', 'success');
    } catch (err) {
      if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
    } finally {
      fileInput.value = '';
    }
  };
}

function closeProductPageSettingsModal() {
  const modal = document.getElementById('productPageSettingsModal');
  if (!modal) return;
  destroyPageSettingsEditors();
  modal.hidden = true;
}

async function openProductPageSettingsModal() {
  const modal = document.getElementById('productPageSettingsModal');
  const form = document.getElementById('productPageSettingsForm');
  if (!modal || !form) return;

  let settings = window.SHOP_PAGE_SETTINGS || {};
  try {
    const data = await PageSettingsAPI.get(PageSettingsAPI.endpoints.get);
    if (data.data) {
      settings = data.data;
      window.SHOP_PAGE_SETTINGS = settings;
    }
  } catch (_) {
    // keep embedded settings
  }

  pageSettingsState.header_image = settings.header_image || '';
  pageSettingsState.footer_image = settings.footer_image || '';
  renderImagePreview('header', 'global');
  renderImagePreview('footer', 'global');
  bindImageControls('header', 'global');
  bindImageControls('footer', 'global');
  destroyPageSettingsEditors();
  modal.hidden = false;
  setTimeout(() => initPageSettingsEditors(settings.header_html || '', settings.footer_html || ''), 30);
}

function renderCategoryList(activeId) {
  const box = document.getElementById('pageCategoryList');
  if (!box) return;
  const rows = pageCategoryState.categories || [];
  if (!rows.length) {
    box.innerHTML = '<p class="admin-muted" style="padding:12px">등록된 카테고리가 없습니다.</p>';
    return;
  }
  box.innerHTML = rows.map((cat) => {
    const depth = Number(cat.depth || 0);
    const depthClass = depth > 0 ? ' is-child' : ' is-parent';
    const active = Number(cat.id) === Number(activeId) ? ' is-active' : '';
    const inactive = cat.is_active ? '' : ' is-inactive';
    const badge = cat.has_custom ? '<span class="badge">설정됨</span>' : '';
    const level = depth > 0 ? '2차' : '1차';
    return `<button type="button" class="admin-page-category-item${depthClass}${active}${inactive}" data-category-id="${Number(cat.id)}">
      <span><em class="admin-page-category-item__level">${level}</em>${pageSettingsEscHtml(cat.name || '')}${cat.is_active ? '' : ' (비활성)'}</span>
      ${badge}
    </button>`;
  }).join('');

  box.querySelectorAll('[data-category-id]').forEach((btn) => {
    btn.addEventListener('click', () => selectCategory(Number(btn.dataset.categoryId)));
  });
}

async function selectCategory(categoryId) {
  if (!categoryId) return;
  const saveBtn = document.getElementById('pageCategorySaveBtn');
  const nameEl = document.getElementById('pageCategoryName');
  const idEl = document.getElementById('pageCategoryId');
  try {
    const data = await PageSettingsAPI.get(
      `${PageSettingsAPI.endpoints.categoryOne}?category_id=${encodeURIComponent(categoryId)}`
    );
    const settings = data.data || {};
    pageCategoryState.category_id = Number(settings.category_id || categoryId);
    pageCategoryState.header_image = settings.header_image || '';
    pageCategoryState.footer_image = settings.footer_image || '';
    if (idEl) idEl.value = String(pageCategoryState.category_id);
    if (nameEl) nameEl.textContent = settings.category_name || '카테고리';
    const hintEl = document.getElementById('pageCategoryStackHint');
    if (hintEl) hintEl.hidden = Number(settings.depth || 0) <= 0;
    if (saveBtn) saveBtn.disabled = false;
    renderCategoryList(pageCategoryState.category_id);
    renderImagePreview('header', 'category');
    renderImagePreview('footer', 'category');
    bindImageControls('header', 'category');
    bindImageControls('footer', 'category');
    destroyPageCategoryEditors();
    setTimeout(() => initPageCategoryEditors(settings.header_html || '', settings.footer_html || ''), 20);
  } catch (err) {
    if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
  }
}

function closeProductPageCategorySettingsModal() {
  const modal = document.getElementById('productPageCategorySettingsModal');
  if (!modal) return;
  destroyPageCategoryEditors();
  pageCategoryState.category_id = 0;
  modal.hidden = true;
}

async function openProductPageCategorySettingsModal() {
  const modal = document.getElementById('productPageCategorySettingsModal');
  if (!modal) return;
  try {
    const data = await PageSettingsAPI.get(PageSettingsAPI.endpoints.categoryList);
    pageCategoryState.categories = data.data?.categories || [];
  } catch (err) {
    if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
    return;
  }
  pageCategoryState.category_id = 0;
  pageCategoryState.header_image = '';
  pageCategoryState.footer_image = '';
  const nameEl = document.getElementById('pageCategoryName');
  const idEl = document.getElementById('pageCategoryId');
  const saveBtn = document.getElementById('pageCategorySaveBtn');
  const hintEl = document.getElementById('pageCategoryStackHint');
  if (nameEl) nameEl.textContent = '카테고리를 선택하세요';
  if (idEl) idEl.value = '';
  if (saveBtn) saveBtn.disabled = true;
  if (hintEl) hintEl.hidden = true;
  destroyPageCategoryEditors();
  renderCategoryList(0);
  renderImagePreview('header', 'category');
  renderImagePreview('footer', 'category');
  bindImageControls('header', 'category');
  bindImageControls('footer', 'category');
  modal.hidden = false;
  if (pageCategoryState.categories.length) {
    selectCategory(Number(pageCategoryState.categories[0].id));
  }
}

document.querySelectorAll('.js-product-page-settings').forEach((btn) => {
  btn.addEventListener('click', () => openProductPageSettingsModal());
});

document.querySelectorAll('.js-page-settings-close').forEach((el) => {
  el.addEventListener('click', () => closeProductPageSettingsModal());
});

document.querySelectorAll('.js-product-page-category-settings').forEach((btn) => {
  btn.addEventListener('click', () => openProductPageCategorySettingsModal());
});

document.querySelectorAll('.js-page-category-settings-close').forEach((el) => {
  el.addEventListener('click', () => closeProductPageCategorySettingsModal());
});

const pageSettingsForm = document.getElementById('productPageSettingsForm');
if (pageSettingsForm) {
  pageSettingsForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = document.querySelector('button[type="submit"][form="productPageSettingsForm"]');
    if (submitBtn) submitBtn.disabled = true;
    try {
      const payload = {
        header_html: getPageSettingsEditorCode('.js-page-header-html'),
        footer_html: getPageSettingsEditorCode('.js-page-footer-html'),
        header_image: pageSettingsState.header_image || '',
        footer_image: pageSettingsState.footer_image || '',
      };
      const data = await PageSettingsAPI.post(PageSettingsAPI.endpoints.save, payload);
      window.SHOP_PAGE_SETTINGS = data.data || payload;
      if (typeof showAdminAlert === 'function') showAdminAlert(data.message || '저장되었습니다.', 'success');
      closeProductPageSettingsModal();
    } catch (err) {
      if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });
}

const pageCategoryForm = document.getElementById('productPageCategorySettingsForm');
if (pageCategoryForm) {
  pageCategoryForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = document.getElementById('pageCategorySaveBtn');
    if (!pageCategoryState.category_id) {
      if (typeof showAdminAlert === 'function') showAdminAlert('카테고리를 선택해주세요.', 'error');
      return;
    }
    if (submitBtn) submitBtn.disabled = true;
    try {
      const payload = {
        category_id: pageCategoryState.category_id,
        header_html: getPageSettingsEditorCode('.js-page-cat-header-html'),
        footer_html: getPageSettingsEditorCode('.js-page-cat-footer-html'),
        header_image: pageCategoryState.header_image || '',
        footer_image: pageCategoryState.footer_image || '',
      };
      const data = await PageSettingsAPI.post(PageSettingsAPI.endpoints.categorySave, payload);
      const saved = data.data || payload;
      pageCategoryState.categories = pageCategoryState.categories.map((cat) => (
        Number(cat.id) === Number(saved.category_id)
          ? {
            ...cat,
            has_custom: !!saved.has_custom,
            header_html: saved.header_html || '',
            footer_html: saved.footer_html || '',
            header_image: saved.header_image || '',
            footer_image: saved.footer_image || '',
          }
          : cat
      ));
      renderCategoryList(pageCategoryState.category_id);
      if (typeof showAdminAlert === 'function') showAdminAlert(data.message || '저장되었습니다.', 'success');
    } catch (err) {
      if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });
}

document.addEventListener('keydown', (e) => {
  if (e.key !== 'Escape') return;
  const pageModal = document.getElementById('productPageSettingsModal');
  if (pageModal && !pageModal.hidden) {
    closeProductPageSettingsModal();
    return;
  }
  const catModal = document.getElementById('productPageCategorySettingsModal');
  if (catModal && !catModal.hidden) {
    closeProductPageCategorySettingsModal();
    return;
  }
  const previewModal = document.getElementById('productDetailPreviewModal');
  if (previewModal && !previewModal.hidden) {
    closeProductDetailPreviewModal();
  }
});

function closeProductDetailPreviewModal() {
  const modal = document.getElementById('productDetailPreviewModal');
  const frame = document.getElementById('productDetailPreviewFrame');
  if (!modal) return;
  modal.hidden = true;
  if (frame) frame.src = 'about:blank';
}

function openProductDetailPreviewModal(url, title) {
  const modal = document.getElementById('productDetailPreviewModal');
  const frame = document.getElementById('productDetailPreviewFrame');
  const titleEl = document.getElementById('productDetailPreviewTitle');
  const openLink = document.getElementById('productDetailPreviewOpen');
  if (!modal || !frame || !url) return;
  if (titleEl) titleEl.textContent = title ? `미리보기 · ${title}` : '상품 상세 미리보기';
  if (openLink) openLink.href = url;
  frame.src = url;
  modal.hidden = false;
}

document.querySelectorAll('.js-product-detail-preview').forEach((btn) => {
  btn.addEventListener('click', () => {
    openProductDetailPreviewModal(btn.dataset.previewUrl || '', btn.dataset.previewTitle || '');
  });
});

document.querySelectorAll('.js-product-detail-preview-close').forEach((el) => {
  el.addEventListener('click', () => closeProductDetailPreviewModal());
});

const DETAIL_HTML_EDITOR_OPTS = {
  lang: 'ko-KR',
  height: 520,
  placeholder: '상품 상세페이지에 표시할 내용을 입력하세요.',
  dialogsInBody: true,
  toolbar: [
    ['style', ['style']],
    ['font', ['bold', 'italic', 'underline', 'clear']],
    ['fontsize', ['fontsize']],
    ['color', ['color']],
    ['para', ['ul', 'ol', 'paragraph']],
    ['insert', ['link', 'picture', 'table', 'hr']],
    ['view', ['fullscreen', 'codeview']],
  ],
  callbacks: {
    onImageUpload(files) {
      uploadDetailHtmlImages(files);
    },
  },
};

function destroyDetailHtmlEditor() {
  destroyEditors(['.js-product-detail-html']);
}

function initDetailHtmlEditor(html = '') {
  if (!window.jQuery || !jQuery.fn.summernote) return;
  const ta = document.querySelector('.js-product-detail-html');
  if (!ta) return;
  const $el = jQuery(ta);
  if ($el.next('.note-editor').length) $el.summernote('destroy');
  $el.val(html || '');
  $el.summernote(DETAIL_HTML_EDITOR_OPTS);
  $el.summernote('code', html || '');
}

async function uploadDetailHtmlImages(files) {
  try {
    const data = await PageSettingsAPI.uploadImages(files);
    const urls = data.data?.urls || [];
    const $el = window.jQuery && jQuery('.js-product-detail-html');
    if (!$el || !$el.length) return;
    urls.forEach((path) => {
      $el.summernote('insertImage', pageSettingsResolveUrl(path), function ($image) {
        $image.css({ maxWidth: '100%', height: 'auto' });
      });
    });
  } catch (err) {
    if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
  }
}

function updateDetailStatusBadge(productId, registered) {
  const row = document.querySelector(`tr[data-product-id="${Number(productId)}"]`);
  const cell = row?.querySelector('.js-detail-status');
  if (!cell) return;
  cell.innerHTML = registered
    ? '<span class="admin-badge admin-badge--ok">등록</span>'
    : '<span class="admin-badge admin-badge--pending">미등록</span>';
}

function closeProductDetailHtmlModal() {
  const modal = document.getElementById('productDetailHtmlModal');
  if (!modal) return;
  destroyDetailHtmlEditor();
  modal.hidden = true;
}

async function openProductDetailHtmlModal(productId, fallbackName) {
  const modal = document.getElementById('productDetailHtmlModal');
  const idEl = document.getElementById('productDetailHtmlProductId');
  const titleEl = document.getElementById('productDetailHtmlTitle');
  const metaEl = document.getElementById('productDetailHtmlMeta');
  const saveBtn = document.getElementById('productDetailHtmlSaveBtn');
  if (!modal || !productId) return;
  try {
    const data = await PageSettingsAPI.get(
      `${PageSettingsAPI.endpoints.detailGet}?product_id=${encodeURIComponent(productId)}`
    );
    const detail = data.data || {};
    if (idEl) idEl.value = String(detail.product_id || productId);
    const name = detail.product_name || fallbackName || '상품';
    if (titleEl) titleEl.textContent = `상품 상세 내용 수정 · ${name}`;
    if (metaEl) {
      const sku = detail.sku ? `SKU ${detail.sku}` : '';
      const cat = detail.category_name || '';
      metaEl.innerHTML = `<strong>${pageSettingsEscHtml(name)}</strong>${sku ? ` · ${pageSettingsEscHtml(sku)}` : ''}${cat ? ` · ${pageSettingsEscHtml(cat)}` : ''}`;
    }
    destroyDetailHtmlEditor();
    modal.hidden = false;
    if (saveBtn) saveBtn.disabled = false;
    setTimeout(() => initDetailHtmlEditor(detail.html_content || ''), 30);
  } catch (err) {
    if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
  }
}

document.querySelectorAll('.js-product-detail-edit').forEach((btn) => {
  btn.addEventListener('click', () => {
    openProductDetailHtmlModal(Number(btn.dataset.productId || 0), btn.dataset.productName || '');
  });
});

document.querySelectorAll('.js-product-detail-html-close').forEach((el) => {
  el.addEventListener('click', () => closeProductDetailHtmlModal());
});

const detailHtmlForm = document.getElementById('productDetailHtmlForm');
if (detailHtmlForm) {
  detailHtmlForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const idEl = document.getElementById('productDetailHtmlProductId');
    const saveBtn = document.getElementById('productDetailHtmlSaveBtn');
    const productId = Number(idEl?.value || 0);
    if (!productId) return;
    if (saveBtn) saveBtn.disabled = true;
    try {
      const saved = await PageSettingsAPI.post(PageSettingsAPI.endpoints.detailSave, {
        product_id: productId,
        html_content: getPageSettingsEditorCode('.js-product-detail-html'),
      });
      const detail = saved.data || {};
      updateDetailStatusBadge(productId, !!detail.has_detail_page);
      if (typeof showAdminAlert === 'function') showAdminAlert(saved.message || '저장되었습니다.', 'success');
      closeProductDetailHtmlModal();
    } catch (err) {
      if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
    } finally {
      if (saveBtn) saveBtn.disabled = false;
    }
  });
}
