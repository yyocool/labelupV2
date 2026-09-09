const PageSettingsAPI = {
  endpoints: {
    get: '/api/admin/shop/product-page-settings',
    save: '/api/admin/shop/product-page-settings/save',
    uploadImages: '/api/admin/shop/product-page-settings/upload-images',
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

function destroyPageSettingsEditors() {
  ['.js-page-header-html', '.js-page-footer-html'].forEach((sel) => {
    const ta = document.querySelector(sel);
    if (ta && window.jQuery && jQuery.fn.summernote && jQuery(ta).next('.note-editor').length) {
      jQuery(ta).summernote('destroy');
    }
  });
}

function initPageSettingsEditors(headerHtml = '', footerHtml = '') {
  if (!window.jQuery || !jQuery.fn.summernote) return;
  const header = document.querySelector('.js-page-header-html');
  const footer = document.querySelector('.js-page-footer-html');
  if (header) {
    const $h = jQuery(header);
    if ($h.next('.note-editor').length) $h.summernote('destroy');
    $h.val(headerHtml || '');
    $h.summernote(PAGE_SETTINGS_EDITOR_OPTS);
    if (headerHtml) $h.summernote('code', headerHtml);
  }
  if (footer) {
    const $f = jQuery(footer);
    if ($f.next('.note-editor').length) $f.summernote('destroy');
    $f.val(footerHtml || '');
    $f.summernote(PAGE_SETTINGS_EDITOR_OPTS);
    if (footerHtml) $f.summernote('code', footerHtml);
  }
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

function renderPageSettingsImage(kind) {
  const isHeader = kind === 'header';
  const path = isHeader ? pageSettingsState.header_image : pageSettingsState.footer_image;
  const preview = document.getElementById(isHeader ? 'pageHeaderImagePreview' : 'pageFooterImagePreview');
  const hidden = document.getElementById(isHeader ? 'pageHeaderImagePath' : 'pageFooterImagePath');
  const removeBtn = document.getElementById(isHeader ? 'pageHeaderImageRemove' : 'pageFooterImageRemove');
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

function bindPageSettingsImage(kind) {
  const isHeader = kind === 'header';
  const addBtn = document.getElementById(isHeader ? 'pageHeaderImageAdd' : 'pageFooterImageAdd');
  const fileInput = document.getElementById(isHeader ? 'pageHeaderImageInput' : 'pageFooterImageInput');
  const removeBtn = document.getElementById(isHeader ? 'pageHeaderImageRemove' : 'pageFooterImageRemove');
  if (!addBtn || !fileInput) return;

  addBtn.onclick = () => fileInput.click();
  if (removeBtn) {
    removeBtn.onclick = () => {
      if (isHeader) pageSettingsState.header_image = '';
      else pageSettingsState.footer_image = '';
      renderPageSettingsImage(kind);
    };
  }
  fileInput.onchange = async () => {
    if (!fileInput.files || !fileInput.files.length) return;
    try {
      const data = await PageSettingsAPI.uploadImages(fileInput.files);
      const url = (data.data?.urls || data.urls || [])[0] || '';
      if (!url) throw new Error('이미지 업로드에 실패했습니다.');
      if (isHeader) pageSettingsState.header_image = url;
      else pageSettingsState.footer_image = url;
      renderPageSettingsImage(kind);
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
    const res = await fetch(PageSettingsAPI.endpoints.get, { credentials: 'same-origin' });
    const data = await res.json().catch(() => ({}));
    if (res.ok && data.success !== false && data.data) {
      settings = data.data;
      window.SHOP_PAGE_SETTINGS = settings;
    }
  } catch (_) {
    // keep embedded settings
  }

  pageSettingsState.header_image = settings.header_image || '';
  pageSettingsState.footer_image = settings.footer_image || '';
  renderPageSettingsImage('header');
  renderPageSettingsImage('footer');
  bindPageSettingsImage('header');
  bindPageSettingsImage('footer');
  destroyPageSettingsEditors();
  modal.hidden = false;
  setTimeout(() => initPageSettingsEditors(settings.header_html || '', settings.footer_html || ''), 30);
}

document.querySelectorAll('.js-product-page-settings').forEach((btn) => {
  btn.addEventListener('click', () => openProductPageSettingsModal());
});

document.querySelectorAll('.js-page-settings-close').forEach((el) => {
  el.addEventListener('click', () => closeProductPageSettingsModal());
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

document.addEventListener('keydown', (e) => {
  if (e.key !== 'Escape') return;
  const pageModal = document.getElementById('productPageSettingsModal');
  if (pageModal && !pageModal.hidden) {
    closeProductPageSettingsModal();
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
