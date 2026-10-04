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
  async uploadImages(files, extra = {}) {
    const list = Array.from(files);
    // 호스팅이 요청 빈도로 막으면(403) 몇 초 뒤 풀린다. 파일 탓이 아니므로 다시 보낸다.
    for (let attempt = 0; ; attempt++) {
      const fd = new FormData();
      list.forEach((file) => fd.append('images[]', file));
      if (extra.fitWidth) fd.append('fit_width', String(extra.fitWidth));
      const res = await fetch(this.endpoints.uploadImages, { method: 'POST', credentials: 'same-origin', body: fd });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.success !== false) return data;

      const throttled = QUEUE_THROTTLE_STATUS.includes(res.status);
      if (throttled && attempt < QUEUE_RETRY_WAITS.length) {
        await queueSleep(QUEUE_RETRY_WAITS[attempt]);
        continue;
      }
      throw new Error(data.message || (throttled
        ? '서버가 잠시 요청을 막고 있습니다. 10초쯤 뒤에 다시 시도해주세요.'
        : `이미지 업로드에 실패했습니다. (HTTP ${res.status})`));
    }
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
    ['insert', ['link', 'picture', 'multiImage', 'table', 'hr']],
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
  hashtags: [],
  categories: [],
};

function renderCategoryHashtags() {
  const box = document.getElementById('pageCategoryHashtags');
  if (!box) return;
  const tags = pageCategoryState.hashtags || [];
  if (!tags.length) {
    box.innerHTML = '<span class="admin-muted">등록된 해시태그가 없습니다.</span>';
    return;
  }
  box.innerHTML = tags.map((tag, index) => (
    `<span class="admin-hashtag-chip">${pageSettingsEscHtml(tag)}<button type="button" data-hashtag-index="${index}" aria-label="${pageSettingsEscHtml(tag)} 삭제">×</button></span>`
  )).join('');
  box.querySelectorAll('[data-hashtag-index]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const index = Number(btn.dataset.hashtagIndex);
      pageCategoryState.hashtags = pageCategoryState.hashtags.filter((_, i) => i !== index);
      renderCategoryHashtags();
    });
  });
}

function setCategoryHashtagInputsEnabled(enabled) {
  const input = document.getElementById('pageCategoryHashtagInput');
  const addBtn = document.getElementById('pageCategoryHashtagAdd');
  if (input) input.disabled = !enabled;
  if (addBtn) addBtn.disabled = !enabled;
  if (!enabled && input) input.value = '';
}

function addCategoryHashtag() {
  const input = document.getElementById('pageCategoryHashtagInput');
  if (!input || !pageCategoryState.category_id) return;
  const tag = String(input.value || '').trim().replace(/^#+/, '').replace(/\s+/g, ' ');
  if (!tag) return;
  if (pageCategoryState.hashtags.includes(tag)) {
    input.value = '';
    return;
  }
  if (pageCategoryState.hashtags.length >= 8) {
    if (typeof showAdminAlert === 'function') showAdminAlert('해시태그는 8개까지 넣을 수 있습니다.', 'error');
    return;
  }
  pageCategoryState.hashtags = pageCategoryState.hashtags.concat(tag.slice(0, 20));
  input.value = '';
  renderCategoryHashtags();
}

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
    $el.summernote(withImageQueue(PAGE_SETTINGS_EDITOR_OPTS, sel));
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
      const data = await PageSettingsAPI.uploadImages(fileInput.files, { fitWidth: 1050 });
      const url = (data.data?.urls || data.urls || [])[0] || '';
      if (!url) {
        // 서버가 파일별 사유를 주면 그걸 보여준다.
        const why = (data.data?.results || []).find((r) => r.error)?.error;
        throw new Error(why || '이미지 업로드에 실패했습니다.');
      }
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
  closeImageQueueModal();
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
    pageCategoryState.hashtags = Array.isArray(settings.hashtags) ? settings.hashtags.slice() : [];
    if (idEl) idEl.value = String(pageCategoryState.category_id);
    if (nameEl) nameEl.textContent = settings.category_name || '카테고리';
    const hintEl = document.getElementById('pageCategoryStackHint');
    if (hintEl) hintEl.hidden = Number(settings.depth || 0) <= 0;
    if (saveBtn) saveBtn.disabled = false;
    setCategoryHashtagInputsEnabled(true);
    renderCategoryHashtags();
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
  closeImageQueueModal();
  destroyPageCategoryEditors();
  pageCategoryState.category_id = 0;
  pageCategoryState.hashtags = [];
  renderCategoryHashtags();
  setCategoryHashtagInputsEnabled(false);
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
  pageCategoryState.hashtags = [];
  const nameEl = document.getElementById('pageCategoryName');
  const idEl = document.getElementById('pageCategoryId');
  const saveBtn = document.getElementById('pageCategorySaveBtn');
  const hintEl = document.getElementById('pageCategoryStackHint');
  if (nameEl) nameEl.textContent = '카테고리를 선택하세요';
  if (idEl) idEl.value = '';
  if (saveBtn) saveBtn.disabled = true;
  if (hintEl) hintEl.hidden = true;
  setCategoryHashtagInputsEnabled(false);
  renderCategoryHashtags();
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

const pageCategoryHashtagInput = document.getElementById('pageCategoryHashtagInput');
const pageCategoryHashtagAdd = document.getElementById('pageCategoryHashtagAdd');
if (pageCategoryHashtagAdd) {
  pageCategoryHashtagAdd.addEventListener('click', () => addCategoryHashtag());
}
if (pageCategoryHashtagInput) {
  pageCategoryHashtagInput.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    addCategoryHashtag();
  });
}

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
        hashtags: pageCategoryState.hashtags || [],
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
            hashtags: Array.isArray(saved.hashtags) ? saved.hashtags : [],
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
  const queueModal = document.getElementById('imageQueueModal');
  if (queueModal && !queueModal.hidden) {
    closeImageQueueModal();
    return;
  }
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

function detailImageDownloadName(header) {
  const star = String(header || '').match(/filename\*=UTF-8''([^;]+)/i);
  if (star) {
    try { return decodeURIComponent(star[1].trim()); } catch (e) { /* 아래 이름 사용 */ }
  }
  const plain = String(header || '').match(/filename="([^"]+)"/i);
  return plain ? plain[1] : 'detail-images.zip';
}

document.querySelectorAll('.js-product-detail-images').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const url = btn.dataset.downloadUrl || '';
    if (!url || btn.disabled) return;
    btn.disabled = true;
    try {
      const res = await fetch(url, { credentials: 'same-origin' });
      const type = res.headers.get('content-type') || '';
      if (!res.ok || !type.includes('zip')) {
        const text = (await res.text()).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        throw new Error(text || '이미지를 받지 못했습니다.');
      }
      const blob = await res.blob();
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = detailImageDownloadName(res.headers.get('content-disposition'));
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(link.href);
    } catch (err) {
      if (typeof showAdminAlert === 'function') showAdminAlert(err.message || '이미지를 받지 못했습니다.', 'error');
    } finally {
      btn.disabled = false;
    }
  });
});

const DETAIL_HTML_EDITOR_OPTS = {
  lang: 'ko-KR',
  height: 520,
  placeholder: '헤더, 상품규격, 촬영 이미지가 위에서부터 들어갑니다.',
  dialogsInBody: true,
  toolbar: [
    ['insert', ['picture', 'multiImage']],
    ['view', ['codeview']],
  ],
  callbacks: {
    onChange() {
      enforceDetailImagesOnly(jQuery('.js-product-detail-html'));
    },
  },
};

function escapeDetailAttr(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;');
}

/** 에디터 HTML에서 img 만 남긴다. 경로는 /assets/ 로 맞춘다. */
function detailImagesOnlyHtml(html) {
  const doc = new DOMParser().parseFromString(String(html || ''), 'text/html');
  return [...doc.querySelectorAll('img')].map((img) => {
    let src = (img.getAttribute('src') || '').trim().replace(/[?#].*$/, '');
    if (/^https?:/i.test(src)) {
      try { src = new URL(src).pathname; } catch (e) { return ''; }
    }
    if (!src.startsWith('/assets/') || src.includes('..')) return '';
    return `<img src="${escapeDetailAttr(src)}" alt="${escapeDetailAttr(img.getAttribute('alt') || '')}">`;
  }).filter(Boolean).join('');
}

function isEmptyDetailEditorHtml(html) {
  const compact = String(html || '').replace(/\s/g, '').toLowerCase();
  return compact === '' || compact === '<p><br></p>' || compact === '<p></p>' || compact === '<br>';
}

let detailImageNormalizeLock = false;

function enforceDetailImagesOnly($el) {
  if (detailImageNormalizeLock || !$el || !$el.length) return;
  const editable = $el.next('.note-editor').find('.note-editable').get(0);
  if (!editable) return;
  const raw = editable.innerHTML || '';
  const clean = detailImagesOnlyHtml(raw);
  if (clean === '' && isEmptyDetailEditorHtml(raw)) return;
  if (raw.replace(/\s/g, '') === clean.replace(/\s/g, '')) return;
  detailImageNormalizeLock = true;
  try {
    $el.summernote('code', clean);
  } finally {
    detailImageNormalizeLock = false;
  }
}

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
  $el.summernote(withImageQueue(DETAIL_HTML_EDITOR_OPTS, '.js-product-detail-html'));
  $el.summernote('code', html || '');
}

/* ── 이미지 삽입 대기열 ─────────────────────────────────
   창을 먼저 띄우고, 그 안에서 업로드한 뒤 썸네일로 순서를 정해 에디터에 넣는다. */

const imageQueue = {
  target: null, // 삽입 대상 textarea 엘리먼트
  items: [], // [{ key, file, name, size, preview, path, url, state, error }]
  seq: 0,
  dragFrom: -1,
  uploading: false,
};

// 서버 한도(upload_max_filesize=20M, post_max_size=21M, max_file_uploads=20)보다
// 여유를 둔다. 한 요청이 한도를 넘으면 PHP 가 본문째로 버려서
// 아무 이유 없이 실패한 것처럼 보인다.
const QUEUE_MAX_FILE_BYTES = 19 * 1024 * 1024;
const QUEUE_UPLOAD_MAX_FILES = 10;
const QUEUE_UPLOAD_MAX_BYTES = 18 * 1024 * 1024;
const QUEUE_IMAGE_EXT = /\.(jpe?g|png|gif|webp)$/i;

// 호스팅이 요청 빈도로 막을 때 쓰는 상태 코드. 파일 문제가 아니라 잠깐 기다리면 풀린다.
const QUEUE_THROTTLE_STATUS = [403, 429, 503];
// 측정상 2초면 풀려서 한 번만 쉬어도 대부분 통과한다. 여유를 둬 세 번까지 기다린다.
const QUEUE_RETRY_WAITS = [2500, 4000, 7000];
// 요청을 연달아 쏘면 막히므로 묶음 사이를 띄운다.
const QUEUE_CHUNK_GAP = 1200;

const queueSleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function queueFormatBytes(bytes) {
  const n = Number(bytes) || 0;
  if (n >= 1024 * 1024) return `${(n / 1024 / 1024).toFixed(1)}MB`;
  return `${Math.max(1, Math.round(n / 1024))}KB`;
}

/** 진행률을 보여줘야 해서 fetch 대신 XHR 로 올린다. fetch 는 업로드 진행 이벤트가 없다. */
function uploadQueueChunk(files, onProgress) {
  return new Promise((resolve, reject) => {
    const fd = new FormData();
    files.forEach((file) => fd.append('images[]', file));
    const xhr = new XMLHttpRequest();
    xhr.open('POST', PageSettingsAPI.endpoints.uploadImages, true);
    xhr.withCredentials = true;
    xhr.upload.addEventListener('progress', (e) => {
      if (e.lengthComputable) onProgress(e.loaded, e.total);
    });
    xhr.addEventListener('load', () => {
      let data = {};
      try {
        data = JSON.parse(xhr.responseText || '{}');
      } catch (err) {
        data = {};
      }
      if (xhr.status < 200 || xhr.status >= 300 || data.success === false) {
        // 413 은 웹서버가 본문 크기로 끊은 것, 빈 응답은 PHP 가 요청을 버린 것이다.
        const fallback = xhr.status === 413
          ? '서버가 용량 제한으로 거부했습니다. 장당 용량을 줄여주세요.'
          : `이미지 업로드에 실패했습니다. (HTTP ${xhr.status})`;
        const err = new Error(data.message || fallback);
        // PHPS 호스팅은 짧은 시간에 요청이 몰리면 403 HTML 을 돌려준다.
        // 몇 초 뒤면 풀리므로 파일 잘못이 아니다. 호출 측이 기다렸다 다시 보낸다.
        err.throttled = QUEUE_THROTTLE_STATUS.includes(xhr.status);
        reject(err);
        return;
      }
      resolve(data.data || {});
    });
    xhr.addEventListener('error', () => reject(new Error('업로드 중 연결이 끊겼습니다.')));
    xhr.addEventListener('abort', () => reject(new Error('업로드가 취소되었습니다.')));
    xhr.send(fd);
  });
}

/**
 * 호스팅 빈도 제한(403)에 걸리면 잠깐 쉬었다 다시 보낸다.
 * 파일이 잘못된 게 아니라 요청이 몰렸을 뿐이라 사용자에게 실패로 보일 이유가 없다.
 */
async function uploadChunkWithRetry(chunk, onProgress) {
  const files = chunk.map((item) => item.file);
  for (let attempt = 0; ; attempt++) {
    try {
      return await uploadQueueChunk(files, onProgress);
    } catch (err) {
      if (!err.throttled || attempt >= QUEUE_RETRY_WAITS.length) {
        if (err.throttled) {
          err.message = '서버가 잠시 요청을 막고 있습니다. 10초쯤 뒤에 「다시 시도」를 눌러주세요.';
        }
        throw err;
      }
      const wait = QUEUE_RETRY_WAITS[attempt];
      chunk.forEach((item) => { item.state = 'waiting'; });
      renderImageQueue();
      showQueueWaiting(Math.round(wait / 1000), attempt + 1);
      await queueSleep(wait);
      chunk.forEach((item) => { item.state = 'uploading'; });
      renderImageQueue();
    }
  }
}

/** 아직 못 올린 항목을 요청 한도에 맞춰 묶는다. */
function pendingQueueChunks() {
  const chunks = [];
  let current = [];
  let bytes = 0;
  imageQueue.items.filter((item) => item.state === 'pending').forEach((item) => {
    const over = current.length >= QUEUE_UPLOAD_MAX_FILES || bytes + item.size > QUEUE_UPLOAD_MAX_BYTES;
    if (current.length && over) {
      chunks.push(current);
      current = [];
      bytes = 0;
    }
    current.push(item);
    bytes += item.size;
  });
  if (current.length) chunks.push(current);
  return chunks;
}

/** 재시도 대기 중임을 알린다. 멈춘 것처럼 보이면 사용자가 창을 닫아버린다. */
function showQueueWaiting(seconds, attempt) {
  const status = document.getElementById('imageQueueStatus');
  if (!status) return;
  status.hidden = false;
  status.textContent = `서버가 잠시 막아 ${seconds}초 기다렸다 다시 보냅니다… (${attempt}/${QUEUE_RETRY_WAITS.length})`;
}

function showQueueProgress(loaded, total) {
  const bar = document.getElementById('imageQueueBar');
  const fill = document.getElementById('imageQueueBarFill');
  const status = document.getElementById('imageQueueStatus');
  const pct = total > 0 ? Math.min(100, Math.round((loaded / total) * 100)) : 0;
  if (bar) bar.hidden = false;
  if (fill) fill.style.width = `${pct}%`;
  if (status) {
    status.hidden = false;
    status.textContent = `업로드 중 ${pct}% · ${queueFormatBytes(loaded)} / ${queueFormatBytes(total)}`;
  }
}

function hideQueueProgress() {
  const bar = document.getElementById('imageQueueBar');
  const status = document.getElementById('imageQueueStatus');
  if (bar) bar.hidden = true;
  if (status) {
    status.hidden = true;
    status.textContent = '';
  }
}

/** 업로드 중에는 창을 건드리지 못하게 막는다. */
function setQueueBusy(busy) {
  ['imageQueueAdd', 'imageQueueApply', 'imageQueueClear'].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.disabled = busy;
  });
  const modal = document.getElementById('imageQueueModal');
  modal?.classList.toggle('is-busy', busy);
}

/** 고른 파일을 대기열에 담고 곧바로 업로드를 시작한다. */
function addFilesToImageQueue(files) {
  const rejected = [];
  Array.from(files || []).forEach((file) => {
    if (!(file.type || '').startsWith('image/') && !QUEUE_IMAGE_EXT.test(file.name || '')) {
      rejected.push(`${file.name} — 이미지 파일이 아닙니다`);
      return;
    }
    if (file.size > QUEUE_MAX_FILE_BYTES) {
      rejected.push(`${file.name} — ${queueFormatBytes(file.size)} (한 장당 ${queueFormatBytes(QUEUE_MAX_FILE_BYTES)} 까지)`);
      return;
    }
    imageQueue.seq += 1;
    imageQueue.items.push({
      key: `q${imageQueue.seq}`,
      file,
      name: file.name || '이미지',
      size: file.size || 0,
      // 올리기 전에도 바로 보이도록 로컬 미리보기를 쓴다.
      preview: URL.createObjectURL(file),
      path: '',
      url: '',
      state: 'pending',
      error: '',
    });
  });
  renderImageQueue();
  if (rejected.length && typeof showAdminAlert === 'function') {
    showAdminAlert(`넣지 못한 파일 ${rejected.length}개 · ${rejected.join(' / ')}`, 'error');
  }
  runImageQueueUploads();
}

async function runImageQueueUploads() {
  if (imageQueue.uploading) return;
  const chunks = pendingQueueChunks();
  if (!chunks.length) return;
  const totalBytes = chunks.reduce((sum, chunk) => sum + chunk.reduce((s, item) => s + item.size, 0), 0);
  let sentBytes = 0;
  imageQueue.uploading = true;
  setQueueBusy(true);
  showQueueProgress(0, totalBytes);
  try {
    for (const chunk of chunks) {
      chunk.forEach((item) => { item.state = 'uploading'; });
      renderImageQueue();
      const chunkBytes = chunk.reduce((sum, item) => sum + item.size, 0);
      try {
        const data = await uploadChunkWithRetry(
          chunk,
          (loaded) => showQueueProgress(sentBytes + Math.min(loaded, chunkBytes), totalBytes)
        );
        // 서버는 보낸 순서대로 파일별 결과를 준다. urls 만 보고 위치로 짝지으면
        // 실패해 빠진 파일 때문에 그 뒤 경로가 한 칸씩 밀려 엉뚱한 이미지가 붙는다.
        const results = Array.isArray(data.results) && data.results.length === chunk.length
          ? data.results
          : chunk.map((_, i) => ({ path: (data.urls || [])[i] || '', error: '' }));
        chunk.forEach((item, i) => {
          const path = results[i]?.path || '';
          if (path) {
            item.path = path;
            item.url = pageSettingsResolveUrl(path);
            item.state = 'done';
            item.error = '';
          } else {
            item.state = 'error';
            item.error = results[i]?.error || '서버가 저장 경로를 돌려주지 않았습니다.';
          }
        });
      } catch (err) {
        chunk.forEach((item) => {
          item.state = 'error';
          item.error = err.message;
        });
      }
      sentBytes += chunkBytes;
      showQueueProgress(sentBytes, totalBytes);
      renderImageQueue();
      if (chunk !== chunks[chunks.length - 1]) await queueSleep(QUEUE_CHUNK_GAP);
    }
  } finally {
    imageQueue.uploading = false;
    setQueueBusy(false);
    hideQueueProgress();
    renderImageQueue();
  }
  const failed = imageQueue.items.filter((item) => item.state === 'error').length;
  if (failed && typeof showAdminAlert === 'function') {
    showAdminAlert(`${failed}장을 올리지 못했습니다. 빨갛게 표시된 항목을 확인해주세요.`, 'error');
  }
}

/** 툴바 버튼: 업로드 창을 먼저 띄운다. */
function makeMultiImageButton(selector) {
  return function multiImageButton(context) {
    const ui = (context && context.ui) || jQuery.summernote.ui;
    return ui.button({
      contents: '<i class="note-icon-picture"></i><sup>+</sup>',
      tooltip: '이미지 올리기 (여러 장 · 순서 지정)',
      click() {
        openImageQueueModal(document.querySelector(selector));
      },
    }).render();
  };
}

/** 에디터 옵션에 이 에디터 전용 업로드 콜백과 툴바 버튼을 붙여 돌려준다. */
function withImageQueue(baseOpts, selector) {
  return Object.assign({}, baseOpts, {
    callbacks: Object.assign({}, baseOpts.callbacks, {
      // 기본 「그림」 대화상자나 편집창에 끌어다 놓은 경우도 같은 창으로 모은다.
      onImageUpload(files) {
        openImageQueueModal(document.querySelector(selector));
        addFilesToImageQueue(files);
      },
    }),
    buttons: Object.assign({}, baseOpts.buttons, {
      multiImage: makeMultiImageButton(selector),
    }),
  });
}

function openImageQueueModal(target) {
  const modal = document.getElementById('imageQueueModal');
  if (!modal) return;
  // 다른 에디터에서 열었으면 이전 대기열은 버린다.
  if (target && imageQueue.target && imageQueue.target !== target) resetImageQueue();
  if (target) imageQueue.target = target;
  const maxLabel = document.getElementById('imageQueueMaxLabel');
  if (maxLabel) maxLabel.textContent = queueFormatBytes(QUEUE_MAX_FILE_BYTES);
  renderImageQueue();
  modal.hidden = false;
}

function resetImageQueue() {
  imageQueue.items.forEach((item) => {
    if (item.preview) URL.revokeObjectURL(item.preview);
  });
  imageQueue.items = [];
  imageQueue.target = null;
  imageQueue.dragFrom = -1;
}

function closeImageQueueModal() {
  const modal = document.getElementById('imageQueueModal');
  if (modal) modal.hidden = true;
  hideQueueProgress();
  setQueueBusy(false);
  resetImageQueue();
  renderImageQueue();
}

const QUEUE_STATE_LABEL = {
  pending: '대기',
  uploading: '올리는 중',
  waiting: '잠시 대기',
  error: '실패',
};

function renderImageQueue() {
  const wrap = document.getElementById('imageQueueList');
  const count = document.getElementById('imageQueueCount');
  const hint = document.getElementById('imageQueueHint');
  const applyBtn = document.getElementById('imageQueueApply');
  const clearBtn = document.getElementById('imageQueueClear');
  if (!wrap) return;
  const total = imageQueue.items.length;
  const ready = imageQueue.items.filter((item) => item.state === 'done').length;

  wrap.hidden = total === 0;
  if (hint) hint.hidden = ready < 2;
  if (clearBtn) clearBtn.hidden = total === 0;

  // 번호는 실제로 삽입될 순서라서 업로드를 마친 것만 센다.
  let no = 0;
  wrap.innerHTML = imageQueue.items.map((item, i) => {
    const done = item.state === 'done';
    if (done) no += 1;
    const badge = done ? String(no) : (QUEUE_STATE_LABEL[item.state] || '');
    const title = `${item.name} · ${queueFormatBytes(item.size)}${item.error ? ` · ${item.error}` : ''}`;
    // 실패는 사유를 카드에 그대로 적는다. 툴팁에만 두면 왜 안 됐는지 알 수 없다.
    const reason = item.state === 'error'
      ? `<p class="admin-image-queue__why">${pageSettingsEscHtml(item.error)}</p>`
      : '';
    const tools = item.state === 'error'
      ? `<button type="button" class="admin-btn admin-btn--sm" data-queue-retry title="다시 시도">다시 시도</button>
         <button type="button" class="admin-btn admin-btn--sm admin-btn--danger" data-queue-remove title="빼기">삭제</button>`
      : `<button type="button" class="admin-btn admin-btn--sm" data-queue-move="-1"${i === 0 ? ' disabled' : ''} title="앞으로">◀</button>
         <button type="button" class="admin-btn admin-btn--sm" data-queue-move="1"${i === total - 1 ? ' disabled' : ''} title="뒤로">▶</button>
         <button type="button" class="admin-btn admin-btn--sm admin-btn--danger" data-queue-remove title="빼기">삭제</button>`;
    return `
      <figure class="admin-image-queue__item is-${item.state}" draggable="${done ? 'true' : 'false'}" data-queue-index="${i}" title="${pageSettingsEscHtml(title)}">
        <span class="admin-image-queue__no">${pageSettingsEscHtml(badge)}</span>
        <img src="${pageSettingsEscHtml(item.preview || item.url)}" alt="">
        <figcaption class="admin-image-queue__name">${pageSettingsEscHtml(item.name)} · ${queueFormatBytes(item.size)}</figcaption>
        ${reason}
        <div class="admin-image-queue__tools">${tools}</div>
      </figure>`;
  }).join('');

  if (count) {
    count.textContent = total === 0
      ? ''
      : (ready === total ? `${ready}장 삽입 예정` : `${ready}장 준비 · 전체 ${total}장`);
  }
  if (applyBtn) applyBtn.disabled = ready === 0 || imageQueue.uploading;
}

function moveImageQueueItem(from, to) {
  const total = imageQueue.items.length;
  if (from < 0 || to < 0 || from >= total || to >= total || from === to) return;
  const [moved] = imageQueue.items.splice(from, 1);
  imageQueue.items.splice(to, 0, moved);
  renderImageQueue();
}

function removeImageQueueItem(index) {
  const item = imageQueue.items[index];
  if (!item) return;
  if (item.preview) URL.revokeObjectURL(item.preview);
  imageQueue.items.splice(index, 1);
  renderImageQueue();
}

async function applyImageQueue() {
  const target = imageQueue.target;
  // 올리기에 성공한 것만, 화면에 보이는 순서대로 넣는다.
  const urls = imageQueue.items.filter((item) => item.state === 'done').map((item) => item.url);
  closeImageQueueModal();
  if (!target || !urls.length || !window.jQuery) return;
  const $el = jQuery(target);
  if (target.classList.contains('js-product-detail-html')) {
    const current = detailImagesOnlyHtml($el.summernote('code'));
    const added = detailImagesOnlyHtml(urls.map((url) => `<img src="${escapeDetailAttr(url)}" alt="">`).join(''));
    $el.summernote('code', current + added);
    return;
  }
  // 창을 띄우는 동안 선택 영역을 잃으므로 되돌려 놓고 넣는다.
  $el.summernote('focus');
  // insertImage 는 이미지 로딩이 끝나야 완료되는 Promise 다.
  // 한꺼번에 호출하면 먼저 로딩된 것부터 박혀 순서가 뒤섞이므로 하나씩 기다린다.
  for (const url of urls) {
    try {
      await $el.summernote('insertImage', url, ($image) => {
        $image.css({ maxWidth: '100%', height: 'auto' });
      });
    } catch (err) {
      if (typeof showAdminAlert === 'function') {
        showAdminAlert(`이미지를 넣지 못했습니다: ${url}`, 'error');
      }
    }
  }
}

function bindImageQueueEvents() {
  const modal = document.getElementById('imageQueueModal');
  if (!modal) return;
  const wrap = document.getElementById('imageQueueList');
  const addBtn = document.getElementById('imageQueueAdd');
  const clearBtn = document.getElementById('imageQueueClear');
  const fileInput = document.getElementById('imageQueueInput');
  const drop = document.getElementById('imageQueueDrop');

  modal.querySelectorAll('.js-image-queue-close').forEach((el) => {
    el.addEventListener('click', () => {
      if (imageQueue.uploading) return;
      closeImageQueueModal();
    });
  });
  document.getElementById('imageQueueApply')?.addEventListener('click', () => applyImageQueue());
  clearBtn?.addEventListener('click', () => {
    const target = imageQueue.target;
    resetImageQueue();
    imageQueue.target = target;
    renderImageQueue();
  });

  addBtn?.addEventListener('click', () => fileInput?.click());
  fileInput?.addEventListener('change', () => {
    if (fileInput.files?.length) addFilesToImageQueue(fileInput.files);
    fileInput.value = '';
  });

  // 끌어다 놓기. 썸네일 순서 변경 드래그와 섞이지 않게 파일 드래그만 받는다.
  const isFileDrag = (e) => Array.from(e.dataTransfer?.types || []).includes('Files');
  ['dragenter', 'dragover'].forEach((type) => {
    drop?.addEventListener(type, (e) => {
      if (!isFileDrag(e)) return;
      e.preventDefault();
      e.dataTransfer.dropEffect = 'copy';
      drop.classList.add('is-over');
    });
  });
  ['dragleave', 'dragend'].forEach((type) => {
    drop?.addEventListener(type, () => drop.classList.remove('is-over'));
  });
  drop?.addEventListener('drop', (e) => {
    if (!isFileDrag(e)) return;
    e.preventDefault();
    drop.classList.remove('is-over');
    if (e.dataTransfer.files?.length) addFilesToImageQueue(e.dataTransfer.files);
  });

  wrap?.addEventListener('click', (e) => {
    const item = e.target.closest('[data-queue-index]');
    if (!item || imageQueue.uploading) return;
    const index = Number(item.dataset.queueIndex);
    const moveBtn = e.target.closest('[data-queue-move]');
    if (moveBtn) {
      moveImageQueueItem(index, index + Number(moveBtn.dataset.queueMove));
      return;
    }
    if (e.target.closest('[data-queue-retry]')) {
      const item = imageQueue.items[index];
      if (item) {
        item.state = 'pending';
        item.error = '';
        renderImageQueue();
        runImageQueueUploads();
      }
      return;
    }
    if (e.target.closest('[data-queue-remove]')) {
      removeImageQueueItem(index);
    }
  });

  wrap?.addEventListener('dragstart', (e) => {
    const item = e.target.closest('[data-queue-index]');
    if (!item || item.getAttribute('draggable') !== 'true') return;
    imageQueue.dragFrom = Number(item.dataset.queueIndex);
    item.classList.add('is-dragging');
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', String(imageQueue.dragFrom));
    }
  });

  wrap?.addEventListener('dragover', (e) => {
    if (imageQueue.dragFrom < 0) return;
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    const over = e.target.closest('[data-queue-index]');
    wrap.querySelectorAll('.is-drop-target').forEach((el) => el.classList.remove('is-drop-target'));
    if (over && Number(over.dataset.queueIndex) !== imageQueue.dragFrom) over.classList.add('is-drop-target');
  });

  wrap?.addEventListener('drop', (e) => {
    if (imageQueue.dragFrom < 0) return;
    e.preventDefault();
    const over = e.target.closest('[data-queue-index]');
    const from = imageQueue.dragFrom;
    imageQueue.dragFrom = -1;
    if (over) moveImageQueueItem(from, Number(over.dataset.queueIndex));
  });

  wrap?.addEventListener('dragend', () => {
    imageQueue.dragFrom = -1;
    wrap.querySelectorAll('.is-dragging, .is-drop-target').forEach((el) => {
      el.classList.remove('is-dragging', 'is-drop-target');
    });
  });
}

bindImageQueueEvents();

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
  closeImageQueueModal();
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
        html_content: detailImagesOnlyHtml(getPageSettingsEditorCode('.js-product-detail-html')),
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

function openSpecSheetPreview(src, title) {
  if (!src) return;
  let lightbox = document.getElementById('adminLightbox');
  if (!lightbox) {
    lightbox = document.createElement('div');
    lightbox.id = 'adminLightbox';
    lightbox.className = 'admin-lightbox';
    lightbox.hidden = true;
    lightbox.innerHTML = `
      <div class="admin-lightbox-backdrop js-lightbox-close"></div>
      <div class="admin-lightbox-panel" role="dialog" aria-modal="true">
        <div class="admin-lightbox-head">
          <strong id="adminLightboxTitle">상품규격</strong>
          <button type="button" class="admin-lightbox-close js-lightbox-close" aria-label="닫기">×</button>
        </div>
        <div class="admin-lightbox-body"><img id="adminLightboxImg" src="" alt=""></div>
      </div>`;
    document.body.appendChild(lightbox);
  }
  const img = document.getElementById('adminLightboxImg');
  const titleEl = document.getElementById('adminLightboxTitle');
  if (img) {
    img.src = src;
    img.alt = title || '상품규격';
  }
  if (titleEl) titleEl.textContent = title || '상품규격';
  lightbox.hidden = false;
}

document.addEventListener('click', (e) => {
  const preview = e.target.closest('.js-image-preview');
  if (preview) {
    e.preventDefault();
    openSpecSheetPreview(preview.dataset.src || '', preview.dataset.title || '상품규격');
    return;
  }
  if (e.target.closest('.js-lightbox-close')) {
    const lightbox = document.getElementById('adminLightbox');
    if (!lightbox) return;
    lightbox.hidden = true;
    const img = document.getElementById('adminLightboxImg');
    if (img) img.src = '';
  }
});

document.addEventListener('keydown', (e) => {
  if (e.key !== 'Escape') return;
  const lightbox = document.getElementById('adminLightbox');
  if (!lightbox || lightbox.hidden) return;
  lightbox.hidden = true;
  const img = document.getElementById('adminLightboxImg');
  if (img) img.src = '';
});
