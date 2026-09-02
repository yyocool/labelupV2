const ShopAPI = {
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
  async uploadImages(path, files) {
    const fd = new FormData();
    Array.from(files).forEach((file) => fd.append('images[]', file));
    const res = await fetch(path, { method: 'POST', credentials: 'same-origin', body: fd });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '이미지 업로드에 실패했습니다.');
    }
    return data;
  },
};

const SHOP_ENDPOINTS = {
  category: {
    save: '/api/admin/shop/category/save',
    delete: '/api/admin/shop/category/delete',
    uploadImages: '/api/admin/shop/category/upload-images',
  },
  spec: {
    save: '/api/admin/shop/spec/save',
    delete: '/api/admin/shop/spec/delete',
    uploadImages: '/api/admin/shop/spec/upload-images',
  },
  product: {
    save: '/api/admin/shop/product/save',
    delete: '/api/admin/shop/product/delete',
    uploadImages: '/api/admin/shop/product/upload-images',
    compatSave: '/api/admin/shop/product/compat-save',
  },
  order: {
    save: '/api/admin/shop/order/update',
    detail: '/api/admin/shop/order/detail',
    bulk: '/api/admin/shop/order/bulk',
  },
  coupon: { save: '/api/admin/shop/coupon/save', delete: '/api/admin/shop/coupon/delete' },
  banner: { save: '/api/admin/shop/banner/save', delete: '/api/admin/shop/banner/delete' },
};

const PRODUCT_EDITOR_OPTS = {
  lang: 'ko-KR',
  height: 280,
  placeholder: '상품 설명을 입력하세요.',
  toolbar: [
    ['style', ['style']],
    ['font', ['bold', 'italic', 'underline', 'clear']],
    ['para', ['ul', 'ol', 'paragraph']],
    ['insert', ['link', 'picture', 'table', 'hr']],
    ['view', ['fullscreen', 'codeview']],
  ],
};

const PRODUCT_META_FIELDS = [
  { key: 'product_no', label: '제품번호' },
  { key: 'art_no', label: '아트No' },
  { key: 'barcode_no', label: '바코드No' },
  { key: 'material_name', label: '제품명(재질명)' },
  { key: 'barcode', label: '바코드' },
  { key: 'box_barcode', label: '박스 바코드' },
  { key: 'paper_size', label: '제품규격' },
  { key: 'labels_per_sheet', label: '라벨수(칸)', type: 'number' },
  { key: 'std_size', label: '표준치수' },
  { key: 'spec_mm', label: 'Spec(mm)' },
  { key: 'pack_size', label: '패키지' },
  { key: 'box_size', label: '박스' },
  { key: 'sheets_per_pack', label: 'Sheets/PACK', type: 'number' },
  { key: 'qty_per_box', label: '입수량', type: 'number' },
  { key: 'material', label: '재질' },
  { key: 'weight', label: '중량', type: 'number', step: '0.001' },
  { key: 'thickness', label: '두께', type: 'number', step: '0.01' },
  { key: 'origin', label: '원산지' },
  { key: 'etc', label: '비고', full: true },
];

let productImagesState = [];
let categoryImagePath = '';
let specImagePath = '';

function buildEntityImageSection(imagePath = '', entity = 'category') {
  const prefix = entity === 'spec' ? 'shopSpec' : 'shopCategory';
  const label = entity === 'spec' ? '규격 이미지' : '카테고리 이미지';
  const src = imagePath ? resolveImageUrl(imagePath) : '';
  const preview = src
    ? `<img src="${src}" alt="${label}" class="admin-category-image-preview">`
    : '<span class="admin-muted">등록된 이미지가 없습니다.</span>';
  return `
    <div class="admin-field admin-field--images admin-field--full">
      <label>${label}</label>
      <div class="admin-category-image-wrap" id="${prefix}ImagePreview">${preview}</div>
      <input type="file" id="${prefix}ImageInput" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
      <div class="admin-category-image-actions">
        <button type="button" class="admin-btn admin-btn--sm" id="${prefix}ImageAdd">+ 이미지 업로드</button>
        <button type="button" class="admin-btn admin-btn--sm" id="${prefix}ImageRemove"${src ? '' : ' disabled'}>삭제</button>
      </div>
      <input type="hidden" name="image_path" id="${prefix}ImagePath" value="${escHtml(imagePath)}">
    </div>`;
}

function buildCategoryImageSection(imagePath = '') {
  return buildEntityImageSection(imagePath, 'category');
}

function initEntityImage(entity, path = '') {
  const prefix = entity === 'spec' ? 'shopSpec' : 'shopCategory';
  const uploadEndpoint = SHOP_ENDPOINTS[entity]?.uploadImages;
  if (entity === 'spec') specImagePath = path || '';
  else categoryImagePath = path || '';

  const getPath = () => (entity === 'spec' ? specImagePath : categoryImagePath);
  const setPath = (value) => {
    if (entity === 'spec') specImagePath = value || '';
    else categoryImagePath = value || '';
  };

  const render = () => {
    const wrap = document.getElementById(`${prefix}ImagePreview`);
    const input = document.getElementById(`${prefix}ImagePath`);
    const removeBtn = document.getElementById(`${prefix}ImageRemove`);
    const current = getPath();
    if (!wrap || !input) return;
    input.value = current;
    if (removeBtn) removeBtn.disabled = !current;
    if (!current) {
      wrap.innerHTML = '<span class="admin-muted">등록된 이미지가 없습니다.</span>';
      return;
    }
    const src = resolveImageUrl(current);
    wrap.innerHTML = `<button type="button" class="admin-thumb-btn js-image-preview" data-src="${src}" data-title="카테고리 이미지"><img src="${src}" alt="" class="admin-category-image-preview"></button>`;
  };

  const addBtn = document.getElementById(`${prefix}ImageAdd`);
  const fileInput = document.getElementById(`${prefix}ImageInput`);
  const removeBtn = document.getElementById(`${prefix}ImageRemove`);
  if (!addBtn || !fileInput || !uploadEndpoint) return;

  addBtn.onclick = () => fileInput.click();
  fileInput.onchange = async () => {
    const files = fileInput.files;
    if (!files?.length) return;
    addBtn.disabled = true;
    try {
      const res = await ShopAPI.uploadImages(uploadEndpoint, files);
      const url = (res.data?.urls || [])[0] || '';
      if (!url) throw new Error('업로드된 이미지 URL을 받지 못했습니다.');
      setPath(url);
      render();
      if (typeof showAdminAlert === 'function') showAdminAlert('이미지가 업로드되었습니다.', 'success');
    } catch (err) {
      if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
    } finally {
      fileInput.value = '';
      addBtn.disabled = false;
    }
  };
  if (removeBtn) {
    removeBtn.onclick = () => {
      setPath('');
      render();
    };
  }
  render();
}

function initCategoryImage(path = '') {
  initEntityImage('category', path);
}

function escHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function shopField(label, name, value = '', type = 'text', opts = {}) {
  const req = opts.required ? ' required' : '';
  const fullClass = opts.full ? ' admin-field--full' : '';
  const safeValue = escHtml(value);
  if (type === 'checkbox') {
    const checked = value ? ' checked' : '';
    return `<label class="admin-field admin-field--check${fullClass}"><input type="checkbox" name="${name}" value="1"${checked}> ${label}</label>`;
  }
  if (type === 'readonly') {
    return `<div class="admin-field admin-field--readonly${fullClass}"><label>${label}</label><input type="text" value="${safeValue}" readonly tabindex="-1"></div>`;
  }
  if (type === 'textarea') {
    const cls = opts.className ? ` class="${opts.className}"` : '';
    return `<div class="admin-field admin-field--editor${fullClass}"><label>${label}</label><textarea name="${name}" rows="${opts.rows || 3}"${cls}${req}>${safeValue}</textarea></div>`;
  }
  if (type === 'select') {
    const options = (opts.options || []).map((o) => `<option value="${o.v}"${String(value) === String(o.v) ? ' selected' : ''}>${escHtml(o.t)}</option>`).join('');
    return `<div class="admin-field${fullClass}"><label>${label}</label><select name="${name}" class="admin-select"${req}>${options}</select></div>`;
  }
  const extra = opts.step ? ` step="${opts.step}"` : '';
  return `<div class="admin-field${fullClass}"><label>${label}</label><input type="${type}" name="${name}" value="${safeValue}"${req}${extra}></div>`;
}

function buildProductMetaFields(meta = {}) {
  return PRODUCT_META_FIELDS.map((field) => {
    const name = `meta_${field.key}`;
    const value = meta[field.key] ?? '';
    const type = field.type || 'text';
    return shopField(field.label, name, value, type, {
      full: !!field.full,
      step: field.step,
    });
  }).join('');
}

function buildProductForm(row = {}) {
  const productMeta = row.meta || {};
  const shopMeta = window.SHOP_META || { categories: [], specs: [] };
  let html = `<input type="hidden" name="id" value="${row.id || 0}">`;
  html += '<div class="admin-product-form">';

  html += '<section class="admin-product-section"><h4 class="admin-product-section-title">기본 정보</h4><div class="admin-product-form-grid">';
  if (row.id) html += shopField('상품 ID', 'display_id', row.id, 'readonly');
  html += shopField('상품명', 'name', row.name, 'text', { required: true });
  html += shopField('SKU', 'sku', row.sku, 'text', { required: true });
  html += shopField('카테고리', 'category_id', row.category_id, 'select', {
    required: true,
    options: shopMeta.categories.map((c) => ({ v: c.id, t: c.name })),
  });
  html += shopField('라벨 규격', 'spec_id', row.spec_id || '', 'select', {
    options: [{ v: '', t: '선택 안함' }, ...shopMeta.specs.map((s) => ({ v: s.id, t: s.name }))],
  });
  html += shopField('상태', 'status', row.status || 'draft', 'select', {
    options: [
      { v: 'draft', t: '임시저장' }, { v: 'active', t: '판매중' },
      { v: 'soldout', t: '품절' }, { v: 'hidden', t: '숨김' },
    ],
  });
  html += shopField('정렬', 'sort_order', row.sort_order ?? 0, 'number');
  html += '</div></section>';

  html += '<section class="admin-product-section"><h4 class="admin-product-section-title">\uD638\uD658\uCF54\uB4DC</h4><div class="admin-product-form-grid">';
  html += shopField('\uD3FC\uD14D', 'compat_formtec', row.compat_formtec || '');
  html += shopField('\uC544\uC774\uB77C\uBCA8', 'compat_ilabel', row.compat_ilabel || '');
  html += shopField('\uC560\uB2C8\uB77C\uBCA8', 'compat_anylabel', row.compat_anylabel || '');
  html += '</div></section>';

  html += '<section class="admin-product-section"><h4 class="admin-product-section-title">가격 · 재고</h4><div class="admin-product-form-grid">';
  html += shopField('정가(원)', 'price', row.price ?? 0, 'number', { required: true });
  html += shopField('할인가(원)', 'sale_price', row.sale_price ?? '', 'number');
  html += shopField('재고', 'stock_qty', row.stock_qty ?? 0, 'number', { required: true });
  html += '</div></section>';

  html += '<section class="admin-product-section"><h4 class="admin-product-section-title">상품 스펙 · 물류 정보</h4><div class="admin-product-form-grid">';
  html += buildProductMetaFields(productMeta);
  html += '</div></section>';

  html += '<section class="admin-product-section"><h4 class="admin-product-section-title">이미지</h4>';
  html += buildProductImagesSection(row.thumbnail || '');
  html += shopField('대표 이미지 경로', 'thumbnail_path_display', row.thumbnail || '', 'readonly', { full: true });
  html += '</section>';

  html += '<section class="admin-product-section"><h4 class="admin-product-section-title">상세 설명</h4>';
  html += shopField('설명', 'description', row.description, 'textarea', {
    rows: 8,
    full: true,
    className: 'js-product-description',
  });
  html += '</section>';

  if (row.id) {
    html += '<section class="admin-product-section"><h4 class="admin-product-section-title">시스템 정보</h4><div class="admin-product-form-grid">';
    html += shopField('등록일', 'created_at', (row.created_at || '').replace('T', ' ').slice(0, 19), 'readonly');
    html += shopField('수정일', 'updated_at', (row.updated_at || '').replace('T', ' ').slice(0, 19), 'readonly');
    html += '</div></section>';
  }

  html += '</div>';
  return html;
}

function buildProductImagesSection(thumbnail = '') {
  return `
    <div class="admin-field admin-field--images admin-field--full">
      <label>상품 이미지 (다중 등록 가능)</label>
      <div class="admin-product-images" id="shopProductImages"></div>
      <input type="file" id="shopProductImageInput" accept="image/jpeg,image/png,image/gif,image/webp" multiple hidden>
      <button type="button" class="admin-btn admin-btn--sm" id="shopProductImageAdd">+ 이미지 추가</button>
      <input type="hidden" name="thumbnail" id="shopProductThumbnail" value="${thumbnail ?? ''}">
    </div>`;
}

function orderItemSummary(row = {}) {
  const items = row.items || [];
  if (!items.length) return '-';
  const first = items[0];
  const extra = items.length > 1 ? ` \uC678 ${items.length - 1}\uAC74` : '';
  return `${first.product_name || ''} x${first.qty || 0}${extra}`;
}

function buildOrderForm(row = {}) {
  const items = row.items || [];
  const itemRows = items.length
    ? items.map((it) => `<tr><td>${escHtml(it.product_name)}</td><td>${escHtml(it.sku)}</td><td>${Number(it.qty || 0)}</td><td>${Number(it.unit_price || 0).toLocaleString()}</td><td>${Number(it.line_total || 0).toLocaleString()}</td></tr>`).join('')
    : '<tr><td colspan="5">\uC0C1\uD488 \uC815\uBCF4\uAC00 \uC5C6\uC2B5\uB2C8\uB2E4.</td></tr>';
  const carriers = (window.SHOP_ORDER_META && window.SHOP_ORDER_META.carriers) || ['CJ\uB300\uD55C\uD1B5\uC6B4', '\uC6B0\uCCB4\uAD6D\uD0DD\uBC30', '\uD55C\uC9C4\uD0DD\uBC30', '\uB86F\uB370\uD0DD\uBC30', '\uB85C\uC820\uD0DD\uBC30'];
  let html = `<input type="hidden" name="id" value="${row.id || 0}">`;
  html += `<div class="admin-order-detail">`;
  html += `<div class="admin-order-detail__grid">`;
  html += `<div class="admin-field"><label>\uC8FC\uBB38\uBC88\uD638</label><input type="text" value="${escHtml(row.order_no || '')}" readonly></div>`;
  html += `<div class="admin-field"><label>\uC8FC\uBB38\uC77C\uC2DC</label><input type="text" value="${escHtml((row.created_at || '').replace('T', ' ').slice(0, 16))}" readonly></div>`;
  html += `<div class="admin-field"><label>\uAD6C\uB9E4\uC790</label><input type="text" value="${escHtml(row.customer_name || '')}" readonly></div>`;
  html += `<div class="admin-field"><label>\uC5F0\uB77D\uCC98</label><input type="text" value="${escHtml(row.customer_phone || '')}" readonly></div>`;
  html += `<div class="admin-field admin-field--full"><label>\uC774\uBA54\uC77C</label><input type="text" value="${escHtml(row.customer_email || '')}" readonly></div>`;
  html += `<div class="admin-field admin-field--full"><label>\uC218\uB839\uC778 / \uBC30\uC1A1\uC9C0</label><textarea rows="2" readonly>${escHtml((row.shipping_name || row.customer_name || '') + ' ' + (row.shipping_phone || '') + '\n' + (row.shipping_address || ''))}</textarea></div>`;
  html += `<div class="admin-field admin-field--full"><label>\uBC30\uC1A1\uBA54\uBAA8</label><input type="text" value="${escHtml(row.shipping_memo || '')}" readonly></div>`;
  html += `<div class="admin-field"><label>\uC0C1\uD488\uAE08\uC561</label><input type="text" value="${Number(row.subtotal || 0).toLocaleString()}\uC6D0" readonly></div>`;
  html += `<div class="admin-field"><label>\uBC30\uC1A1\uBE44</label><input type="text" value="${Number(row.shipping_fee || 0).toLocaleString()}\uC6D0" readonly></div>`;
  html += `<div class="admin-field"><label>\uD560\uC778</label><input type="text" value="${Number(row.discount_amount || 0).toLocaleString()}\uC6D0" readonly></div>`;
  html += `<div class="admin-field"><label>\uACB0\uC81C\uAE08\uC561</label><input type="text" value="${Number(row.total_amount || 0).toLocaleString()}\uC6D0" readonly></div>`;
  html += `</div>`;
  html += `<table class="admin-table admin-order-items"><thead><tr><th>\uC0C1\uD488\uBA85</th><th>SKU</th><th>\uC218\uB7C9</th><th>\uB2E8\uAC00</th><th>\uAE08\uC561</th></tr></thead><tbody>${itemRows}</tbody></table>`;
  html += '<div class="admin-order-detail__grid">';
  html += shopField('\uC8FC\uBB38\uC0C1\uD0DC', 'status', row.status || 'pending', 'select', {
    options: [
      { v: 'pending', t: '\uC811\uC218\uB300\uAE30' }, { v: 'paid', t: '\uACB0\uC81C\uC644\uB8CC' }, { v: 'preparing', t: '\uC0C1\uD488\uC900\uBE44' },
      { v: 'shipping', t: '\uBC30\uC1A1\uC911' }, { v: 'delivered', t: '\uBC30\uC1A1\uC644\uB8CC' },
      { v: 'cancelled', t: '\uCDE8\uC18C' }, { v: 'refunded', t: '\uD658\uBD88' },
    ],
  });
  html += shopField('\uACB0\uC81C\uC0C1\uD0DC', 'payment_status', row.payment_status || 'pending', 'select', {
    options: [
      { v: 'pending', t: '\uACB0\uC81C\uB300\uAE30' }, { v: 'paid', t: '\uACB0\uC81C\uC644\uB8CC' },
      { v: 'failed', t: '\uACB0\uC81C\uC2E4\uD328' }, { v: 'refunded', t: '\uD658\uBD88\uC644\uB8CC' },
    ],
  });
  html += shopField('\uD0DD\uBC30\uC0AC', 'carrier', row.carrier || '', 'select', {
    options: [{ v: '', t: '\uC120\uD0DD' }, ...carriers.map((c) => ({ v: c, t: c }))],
  });
  html += shopField('\uC1A1\uC7A5\uBC88\uD638', 'tracking_no', row.tracking_no);
  html += shopField('\uAD00\uB9AC\uC790 \uBA54\uBAA8', 'admin_memo', row.admin_memo, 'textarea', { full: true });
  html += '</div></div>';
  return html;
}

function buildShopForm(entity, row = {}) {
  const id = row.id || 0;
  let html = `<input type="hidden" name="id" value="${id}">`;
  if (entity === 'category') {
    html += buildCategoryImageSection(row.image_path || '');
    html += shopField('카테고리명', 'name', row.name, 'text', { required: true });
    html += shopField('슬러그', 'slug', row.slug, 'text', { required: true });
    html += shopField('정렬', 'sort_order', row.sort_order ?? 0, 'number');
    html += shopField('사용', 'is_active', row.id ? row.is_active : 1, 'checkbox');
  } else if (entity === 'spec') {
    html += buildEntityImageSection(row.image_path || '', 'spec');
    html += shopField('규격명', 'name', row.name, 'text', { required: true });
    html += shopField('용지 종류', 'kind', row.kind || 'label', 'select', {
      options: [{ v: 'label', t: '라벨용지' }, { v: 'tag', t: '태그용지' }],
    });
    html += shopField('가로(mm)', 'width_mm', row.width_mm ?? '', 'number', { required: true, step: '0.01' });
    html += shopField('세로(mm)', 'height_mm', row.height_mm ?? '', 'number', { required: true, step: '0.01' });
    html += shopField('재질', 'material', row.material);
    html += shopField('형태', 'shape', row.shape || 'rect', 'select', {
      options: [{ v: 'rect', t: '사각' }, { v: 'round', t: '원형' }, { v: 'custom', t: '맞춤' }],
    });
    html += shopField('시트당 칸수', 'labels_per_sheet', row.labels_per_sheet ?? '', 'number');
    html += shopField('설명', 'description', row.description, 'textarea');
    html += shopField('사용', 'is_active', row.is_active, 'checkbox');
  } else if (entity === 'product') {
    html += buildProductForm(row);
  } else if (entity === 'order') {
    html += buildOrderForm(row);
  } else if (entity === 'coupon') {
    html += shopField('쿠폰 코드', 'code', row.code, 'text', { required: true });
    html += shopField('쿠폰명', 'name', row.name, 'text', { required: true });
    html += shopField('할인 유형', 'discount_type', row.discount_type || 'fixed', 'select', {
      options: [{ v: 'fixed', t: '정액(원)' }, { v: 'percent', t: '정률(%)' }],
    });
    html += shopField('할인값', 'discount_value', row.discount_value ?? 0, 'number', { required: true });
    html += shopField('최소 주문금액', 'min_order_amount', row.min_order_amount ?? 0, 'number');
    html += shopField('최대 사용횟수', 'max_uses', row.max_uses ?? '', 'number');
    html += shopField('시작일', 'starts_at', (row.starts_at || '').slice(0, 16), 'datetime-local');
    html += shopField('종료일', 'ends_at', (row.ends_at || '').slice(0, 16), 'datetime-local');
    html += shopField('사용', 'is_active', row.is_active, 'checkbox');
  } else if (entity === 'banner') {
    html += shopField('제목', 'title', row.title, 'text', { required: true });
    html += shopField('부제', 'subtitle', row.subtitle);
    html += shopField('이미지 URL', 'image_url', row.image_url);
    html += shopField('링크 URL', 'link_url', row.link_url);
    html += shopField('정렬', 'sort_order', row.sort_order ?? 0, 'number');
    html += shopField('노출', 'is_active', row.is_active, 'checkbox');
  }
  return html;
}

function resolveImageUrl(path) {
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  const base = document.querySelector('base')?.href || `${window.location.origin}/`;
  return new URL(path.replace(/^\//, ''), base).href;
}

function syncProductThumbnailInput() {
  const input = document.getElementById('shopProductThumbnail');
  const display = document.querySelector('[name="thumbnail_path_display"]');
  const primary = productImagesState.find((img) => img.is_primary) || productImagesState[0];
  const path = primary?.image_path || '';
  if (input) input.value = path;
  if (display) display.value = path;
}

function renderProductImages() {
  const wrap = document.getElementById('shopProductImages');
  if (!wrap) return;
  if (!productImagesState.length) {
    wrap.innerHTML = '<span class="admin-muted">등록된 이미지가 없습니다.</span>';
    syncProductThumbnailInput();
    return;
  }
  wrap.innerHTML = productImagesState.map((img, idx) => {
    const src = resolveImageUrl(img.image_path);
    const primaryCls = img.is_primary ? ' is-primary' : '';
    return `
      <div class="admin-product-image-item${primaryCls}" data-idx="${idx}">
        <button type="button" class="admin-thumb-btn js-image-preview" data-src="${src}" data-title="상품 이미지">
          <img src="${src}" alt="상품 이미지">
        </button>
        <div class="admin-product-image-actions">
          <button type="button" class="admin-btn admin-btn--sm js-product-img-primary"${img.is_primary ? ' disabled' : ''}>대표</button>
          <button type="button" class="admin-btn admin-btn--sm js-product-img-remove">삭제</button>
        </div>
      </div>`;
  }).join('');
  syncProductThumbnailInput();
}

function initProductImagesState(row = {}) {
  productImagesState = (row.images || []).map((img, i) => ({
    image_path: img.image_path,
    is_primary: !!Number(img.is_primary),
    sort_order: Number(img.sort_order ?? i),
  }));
  if (!productImagesState.length && row.thumbnail) {
    productImagesState = [{ image_path: row.thumbnail, is_primary: true, sort_order: 0 }];
  }
  if (productImagesState.length && !productImagesState.some((img) => img.is_primary)) {
    productImagesState[0].is_primary = true;
  }
  renderProductImages();
}

function destroyProductSummernote() {
  const ta = document.querySelector('.js-product-description');
  if (ta && window.jQuery && jQuery.fn.summernote && jQuery(ta).next('.note-editor').length) {
    jQuery(ta).summernote('destroy');
  }
}

function initProductSummernote() {
  const ta = document.querySelector('.js-product-description');
  if (!ta || !window.jQuery || !jQuery.fn.summernote) return;
  const $el = jQuery(ta);
  if ($el.next('.note-editor').length) return;
  $el.summernote(PRODUCT_EDITOR_OPTS);
}

function getProductDescription() {
  const ta = document.querySelector('.js-product-description');
  if (!ta) return '';
  if (window.jQuery && jQuery.fn.summernote && jQuery(ta).next('.note-editor').length) {
    return jQuery(ta).summernote('code') || '';
  }
  return ta.value || '';
}

function bindProductImageEvents() {
  const addBtn = document.getElementById('shopProductImageAdd');
  const fileInput = document.getElementById('shopProductImageInput');
  const wrap = document.getElementById('shopProductImages');
  if (!addBtn || !fileInput || !wrap) return;

  addBtn.onclick = () => fileInput.click();

  fileInput.onchange = async () => {
    const files = fileInput.files;
    if (!files?.length) return;
    addBtn.disabled = true;
    try {
      const res = await ShopAPI.uploadImages(SHOP_ENDPOINTS.product.uploadImages, files);
      (res.data?.urls || []).forEach((url) => {
        productImagesState.push({
          image_path: url,
          is_primary: productImagesState.length === 0,
          sort_order: productImagesState.length,
        });
      });
      renderProductImages();
      if (typeof showAdminAlert === 'function') showAdminAlert('이미지가 업로드되었습니다.', 'success');
    } catch (err) {
      if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
    } finally {
      fileInput.value = '';
      addBtn.disabled = false;
    }
  };

  wrap.onclick = (e) => {
    const item = e.target.closest('.admin-product-image-item');
    if (!item) return;
    const idx = Number(item.dataset.idx);
    if (e.target.closest('.js-product-img-primary')) {
      productImagesState = productImagesState.map((img, i) => ({ ...img, is_primary: i === idx }));
      renderProductImages();
    } else if (e.target.closest('.js-product-img-remove')) {
      productImagesState.splice(idx, 1);
      if (productImagesState.length && !productImagesState.some((img) => img.is_primary)) {
        productImagesState[0].is_primary = true;
      }
      renderProductImages();
    }
  };
}

function openShopModal(entity, row = {}) {
  const modal = document.getElementById('shopModal');
  const form = document.getElementById('shopModalForm');
  const title = document.getElementById('shopModalTitle');
  const dialog = modal?.querySelector('.admin-modal-dialog');
  if (!modal || !form || !title) return;

  destroyProductSummernote();
  dialog?.classList.toggle('admin-modal-dialog--product', entity === 'product');
  dialog?.classList.toggle('admin-modal-dialog--wide', entity === 'category' || entity === 'spec');
  dialog?.classList.toggle('admin-modal-dialog--order', entity === 'order');

  const labels = { category: '카테고리', spec: '규격', product: '상품', order: '주문', coupon: '쿠폰', banner: '배너' };
  title.textContent = entity === 'order' && row.id
    ? '주문 상세'
    : ((row.id ? '수정' : '등록') + ' — ' + (labels[entity] || entity));
  form.dataset.entity = entity;
  form.innerHTML = buildShopForm(entity, row);
  modal.hidden = false;

  if (entity === 'product') {
    initProductImagesState(row);
    bindProductImageEvents();
    initProductSummernote();
  } else if (entity === 'category') {
    initCategoryImage(row.image_path || '');
  } else if (entity === 'spec') {
    initEntityImage('spec', row.image_path || '');
  }
}

function closeShopModal() {
  destroyProductSummernote();
  const modal = document.getElementById('shopModal');
  if (modal) modal.hidden = true;
}

function collectFormData(form) {
  const data = {};
  new FormData(form).forEach((val, key) => {
    data[key] = val;
  });
  form.querySelectorAll('input[type=checkbox]').forEach((el) => {
    data[el.name] = el.checked ? 1 : 0;
  });
  return data;
}

document.querySelectorAll('.js-shop-add').forEach((btn) => {
  btn.addEventListener('click', () => openShopModal(btn.dataset.entity, {}));
});

document.querySelectorAll('.js-shop-edit').forEach((btn) => {
  btn.addEventListener('click', async () => {
    let row = {};
    try { row = JSON.parse(btn.dataset.row || '{}'); } catch (_) { row = {}; }
    if (btn.dataset.entity === 'order' && row.id && !row.items) {
      try {
        const res = await ShopAPI.post(SHOP_ENDPOINTS.order.detail, { id: Number(row.id) });
        row = res.data || row;
      } catch (_) { /* keep row */ }
    }
    openShopModal(btn.dataset.entity, row);
  });
});

document.querySelectorAll('.js-shop-delete').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const entity = btn.dataset.entity;
    const id = Number(btn.dataset.id);
    if (!id || !confirm('삭제하시겠습니까?')) return;
    try {
      await ShopAPI.post(SHOP_ENDPOINTS[entity].delete, { id });
      if (typeof showAdminAlert === 'function') showAdminAlert('삭제되었습니다.', 'success');
      setTimeout(() => location.reload(), 500);
    } catch (err) {
      if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
    }
  });
});

document.querySelectorAll('.js-shop-modal-close').forEach((el) => {
  el.addEventListener('click', closeShopModal);
});

const shopForm = document.getElementById('shopModalForm');
if (shopForm) {
  shopForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const entity = shopForm.dataset.entity;
    const endpoints = SHOP_ENDPOINTS[entity];
    if (!endpoints?.save) return;
    try {
      const data = collectFormData(shopForm);
      if (entity === 'product') {
        data.description = getProductDescription();
        data.meta = {};
        PRODUCT_META_FIELDS.forEach((field) => {
          const el = shopForm.querySelector(`[name="meta_${field.key}"]`);
          if (el && el.value !== '') {
            data.meta[field.key] = el.value;
          }
        });
        data.images = productImagesState.map((img, i) => ({
          image_path: img.image_path,
          sort_order: i,
          is_primary: img.is_primary ? 1 : 0,
        }));
        const primary = productImagesState.find((img) => img.is_primary) || productImagesState[0];
        data.thumbnail = primary?.image_path || data.thumbnail || '';
      } else if (entity === 'category') {
        data.image_path = categoryImagePath || document.getElementById('shopCategoryImagePath')?.value || '';
      } else if (entity === 'spec') {
        data.image_path = specImagePath || document.getElementById('shopSpecImagePath')?.value || '';
      }
      await ShopAPI.post(endpoints.save, data);
      if (typeof showAdminAlert === 'function') showAdminAlert('저장되었습니다.', 'success');
      setTimeout(() => location.reload(), 500);
    } catch (err) {
      if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
    }
  });
}

function selectedOrderIds() {
  return Array.from(document.querySelectorAll('.js-order-check:checked')).map((el) => Number(el.value)).filter(Boolean);
}

function bindOrderDesk() {
  const table = document.getElementById('orderTable');
  if (!table) return;

  const selectAll = document.getElementById('orderSelectAll');
  if (selectAll) {
    selectAll.addEventListener('change', () => {
      document.querySelectorAll('.js-order-check').forEach((el) => { el.checked = selectAll.checked; });
    });
  }

  document.querySelectorAll('.js-order-bulk').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const ids = selectedOrderIds();
      if (!ids.length) {
        if (typeof showAdminAlert === 'function') showAdminAlert('\uC8FC\uBB38\uC744 \uC120\uD0DD\uD574 \uC8FC\uC138\uC694.', 'error');
        return;
      }
      const status = btn.dataset.status;
      const payload = { ids, status };
      if (status === 'shipping') {
        const carrier = document.getElementById('bulkCarrier')?.value || '';
        const tracking = document.getElementById('bulkTracking')?.value || '';
        if (!tracking) {
          if (typeof showAdminAlert === 'function') showAdminAlert('\uC1A1\uC7A5\uBC88\uD638\uB97C \uC785\uB825\uD574 \uC8FC\uC138\uC694.', 'error');
          return;
        }
        payload.carrier = carrier;
        payload.tracking_no = tracking;
        payload.payment_status = 'paid';
      } else if (status === 'preparing' || status === 'delivered') {
        payload.payment_status = 'paid';
      } else if (status === 'cancelled') {
        if (!confirm('\uC120\uD0DD\uD55C \uC8FC\uBB38\uC744 \uCDE8\uC18C \uCC98\uB9AC\uD560\uAE4C\uC694?')) return;
      }
      try {
        await ShopAPI.post(SHOP_ENDPOINTS.order.bulk, payload);
        if (typeof showAdminAlert === 'function') showAdminAlert('\uCC98\uB9AC\uB418\uC5C8\uC2B5\uB2C8\uB2E4.', 'success');
        setTimeout(() => location.reload(), 400);
      } catch (err) {
        if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
      }
    });
  });

  document.querySelectorAll('.js-order-ship').forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = Number(form.dataset.id);
      if (!id) return;
      try {
        await ShopAPI.post(SHOP_ENDPOINTS.order.save, {
          id,
          status: 'shipping',
          payment_status: 'paid',
          carrier: form.carrier?.value || '',
          tracking_no: form.tracking_no?.value || '',
          admin_memo: form.dataset.memo || '',
        });
        if (typeof showAdminAlert === 'function') showAdminAlert('\uBC30\uC1A1\uC815\uBCF4\uAC00 \uC800\uC7A5\uB418\uC5C8\uC2B5\uB2C8\uB2E4.', 'success');
        setTimeout(() => location.reload(), 400);
      } catch (err) {
        if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
      }
    });
  });
}

bindOrderDesk();

document.querySelectorAll('.js-compat-form').forEach((form) => {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = Number(form.dataset.id);
    if (!id) return;
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    try {
      await ShopAPI.post(SHOP_ENDPOINTS.product.compatSave, {
        id,
        compat_formtec: form.compat_formtec?.value || '',
        compat_ilabel: form.compat_ilabel?.value || '',
        compat_anylabel: form.compat_anylabel?.value || '',
      });
      if (typeof showAdminAlert === 'function') showAdminAlert('\uD638\uD658\uCF54\uB4DC\uAC00 \uC800\uC7A5\uB418\uC5C8\uC2B5\uB2C8\uB2E4.', 'success');
    } catch (err) {
      if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
    } finally {
      if (btn) btn.disabled = false;
    }
  });
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const lightbox = document.getElementById('adminLightbox');
    if (lightbox && !lightbox.hidden) {
      closeAdminLightbox();
      return;
    }
    closeShopModal();
  }
});

function ensureAdminLightbox() {
  let lightbox = document.getElementById('adminLightbox');
  if (lightbox) return lightbox;
  lightbox = document.createElement('div');
  lightbox.id = 'adminLightbox';
  lightbox.className = 'admin-lightbox';
  lightbox.hidden = true;
  lightbox.innerHTML = `
    <div class="admin-lightbox-backdrop js-lightbox-close"></div>
    <div class="admin-lightbox-panel" role="dialog" aria-modal="true">
      <div class="admin-lightbox-head">
        <strong id="adminLightboxTitle">이미지 미리보기</strong>
        <button type="button" class="admin-lightbox-close js-lightbox-close" aria-label="닫기">×</button>
      </div>
      <div class="admin-lightbox-body"><img id="adminLightboxImg" src="" alt=""></div>
    </div>`;
  document.body.appendChild(lightbox);
  return lightbox;
}

function openAdminLightbox(src, title = '이미지 미리보기') {
  if (!src) return;
  const lightbox = ensureAdminLightbox();
  const img = document.getElementById('adminLightboxImg');
  const titleEl = document.getElementById('adminLightboxTitle');
  if (img) {
    img.src = src;
    img.alt = title;
  }
  if (titleEl) titleEl.textContent = title || '이미지 미리보기';
  lightbox.hidden = false;
}

function closeAdminLightbox() {
  const lightbox = document.getElementById('adminLightbox');
  if (!lightbox) return;
  lightbox.hidden = true;
  const img = document.getElementById('adminLightboxImg');
  if (img) img.src = '';
}

document.addEventListener('click', (e) => {
  const preview = e.target.closest('.js-image-preview');
  if (preview) {
    e.preventDefault();
    openAdminLightbox(preview.dataset.src || preview.querySelector('img')?.src || '', preview.dataset.title || '');
    return;
  }
  if (e.target.closest('.js-lightbox-close')) {
    closeAdminLightbox();
  }
});
