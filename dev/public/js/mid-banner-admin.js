const MidBannerAPI = {
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
  async upload(file) {
    const fd = new FormData();
    fd.append('image', file);
    const res = await fetch('/api/admin/mid-banner/upload', {
      method: 'POST',
      credentials: 'same-origin',
      body: fd,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '이미지 업로드에 실패했습니다.');
    }
    return data;
  },
};

const MID_FONTS = {
  'pretendard-extrabold': { label: 'Pretendard ExtraBold', family: 'Pretendard', weight: 800 },
  'pretendard-bold': { label: 'Pretendard Bold', family: 'Pretendard', weight: 700 },
  'pretendard-semibold': { label: 'Pretendard SemiBold', family: 'Pretendard', weight: 600 },
  'pretendard-medium': { label: 'Pretendard Medium', family: 'Pretendard', weight: 500 },
  'pretendard-regular': { label: 'Pretendard Regular', family: 'Pretendard', weight: 400 },
  'pretendard-light': { label: 'Pretendard Light', family: 'Pretendard', weight: 300 },
};

const MID_DEFAULTS = {
  main_font: 'pretendard-extrabold',
  main_size: 50,
  main_color: '#FFFFFF',
  sub_font: 'pretendard-regular',
  sub_size: 25,
  sub_color: '#FFFFFF',
  text_align_h: 'left',
  text_align_v: 'middle',
};

function escapeHtml(s) {
  return String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function midField(label, name, value = '', type = 'text', opts = {}) {
  const req = opts.required ? ' required' : '';
  if (type === 'checkbox') {
    const checked = value ? ' checked' : '';
    return `<label class="admin-field admin-field--check"><input type="checkbox" name="${name}" value="1"${checked}> ${label}</label>`;
  }
  const hint = opts.hint ? `<small class="admin-muted">${opts.hint}</small>` : '';
  return `<div class="admin-field"><label>${label}</label><input type="${type}" name="${name}" value="${escapeHtml(value ?? '')}"${req}>${hint}</div>`;
}

function fontOptions(selected) {
  return Object.entries(MID_FONTS).map(([key, font]) => (
    `<option value="${key}"${key === selected ? ' selected' : ''}>${font.label}</option>`
  )).join('');
}

function alignPills(name, selected, options) {
  return `<div class="mid-align-pills" role="radiogroup">
    ${options.map(([value, label]) => `
      <label class="mid-align-pill">
        <input type="radio" name="${name}" value="${value}" tabindex="-1"${value === selected ? ' checked' : ''}>
        <span>${label}</span>
      </label>`).join('')}
  </div>`;
}

function textStyleRow(prefix, row) {
  const font = row[`${prefix}_font`] || MID_DEFAULTS[`${prefix}_font`];
  const size = row[`${prefix}_size`] || MID_DEFAULTS[`${prefix}_size`];
  const color = String(row[`${prefix}_color`] || MID_DEFAULTS[`${prefix}_color`]).toUpperCase();
  return `
    <div class="mid-text-style">
      <div class="admin-field">
        <label>폰트</label>
        <select name="${prefix}_font">${fontOptions(font)}</select>
      </div>
      <div class="admin-field">
        <label>크기(px)</label>
        <input type="number" name="${prefix}_size" min="12" max="120" value="${escapeHtml(size)}">
      </div>
      <div class="admin-field">
        <label>색상</label>
        <div class="admin-color-row">
          <input type="color" data-color-for="${prefix}_color" value="${escapeHtml(color)}">
          <input type="text" name="${prefix}_color" maxlength="7" value="${escapeHtml(color)}">
        </div>
      </div>
    </div>`;
}

function buildMidBannerForm(row = {}) {
  const id = row.id || 0;
  const imageUrl = row.image_url || '';
  const previewSrc = row.image_src || (imageUrl ? imageUrl : '');
  const previewImg = previewSrc
    ? `<img src="${escapeHtml(previewSrc)}" alt="">`
    : '';

  let html = `<input type="hidden" name="id" value="${id}">`;
  html += `<input type="hidden" name="image_url" id="midImageUrl" value="${escapeHtml(imageUrl)}">`;
  html += `<div class="mid-form-layout">`;
  html += `
    <aside class="mid-form-preview-col">
      <div class="admin-field admin-field--full">
        <label>미리보기</label>
        <div class="mid-form-preview" id="midPreviewWrap">
          ${previewImg || '<div class="mid-form-preview-empty">이미지를 업로드하세요</div>'}
          <div class="mid-banner-copy mid-banner-copy--h-left mid-banner-copy--v-middle" id="midPreviewCopy" hidden>
            <p class="mid-banner-copy-main" data-preview="main"></p>
            <p class="mid-banner-copy-sub" data-preview="sub"></p>
          </div>
        </div>
        <small class="admin-muted">텍스트·위치 변경이 바로 반영됩니다.</small>
      </div>
    </aside>
    <div class="mid-form-fields">`;
  html += midField('제목', 'title', row.title ?? '');
  html += `
    <div class="admin-field">
      <label>연출 이미지</label>
      <div class="hero-upload-row">
        <div class="hero-upload-controls">
          <input type="file" id="midFile" accept="image/png,image/jpeg,image/webp,image/gif">
          <small class="admin-muted">PNG / JPG / WEBP · 홈 용도별 라벨과 동일 비율 11:10 (예: 1100×1000)</small>
          <div id="midUploadStatus" class="admin-muted"></div>
        </div>
      </div>
    </div>`;
  html += midField('랜딩 URL', 'link_url', row.link_url ?? '', 'text', { hint: '클릭 시 이동할 경로 (예: /shop/products?cat=address)' });
  html += `<div class="mid-form-grid">`;
  html += midField('정렬', 'sort_order', row.sort_order ?? 0, 'number');
  html += midField('노출', 'is_active', row.is_active, 'checkbox');
  html += `</div>`;
  html += `
    <div class="mid-text-block mid-text-block--align">
      <div class="admin-field">
        <label>텍스트 위치 (메인·서브 공통)</label>
        <div class="mid-align-row">
          <div>
            <small class="admin-muted">가로</small>
            ${alignPills('text_align_h', row.text_align_h || MID_DEFAULTS.text_align_h, [['left', '좌'], ['center', '중'], ['right', '우']])}
          </div>
          <div>
            <small class="admin-muted">세로</small>
            ${alignPills('text_align_v', row.text_align_v || MID_DEFAULTS.text_align_v, [['top', '상'], ['middle', '중'], ['bottom', '하']])}
          </div>
        </div>
      </div>
    </div>
    <div class="mid-text-block">
      <div class="admin-field admin-field--short">
        <label>메인 텍스트</label>
        <textarea name="main_text" rows="3" placeholder="이미지 위에 크게 표시될 문구">${escapeHtml(row.main_text ?? '')}</textarea>
      </div>
      ${textStyleRow('main', row)}
    </div>
    <div class="mid-text-block">
      <div class="admin-field admin-field--short">
        <label>서브 텍스트</label>
        <textarea name="sub_text" rows="2" placeholder="메인 아래 보조 문구">${escapeHtml(row.sub_text ?? '')}</textarea>
      </div>
      ${textStyleRow('sub', row)}
    </div>`;
  html += `</div></div>`;
  return html;
}

const midModal = document.getElementById('midBannerModal');
const midForm = document.getElementById('midBannerForm');

function applyPreviewStyle(el, fontKey, size, color) {
  const font = MID_FONTS[fontKey] || MID_FONTS['pretendard-regular'];
  el.style.setProperty('--mid-size', `${size}px`);
  el.style.fontFamily = `${font.family}, sans-serif`;
  el.style.fontWeight = String(font.weight);
  el.style.fontSize = `var(--mid-size)`;
  el.style.color = color;
}

function refreshMidPreview() {
  if (!midForm) return;
  const copy = document.getElementById('midPreviewCopy');
  const mainEl = copy?.querySelector('[data-preview="main"]');
  const subEl = copy?.querySelector('[data-preview="sub"]');
  if (!copy || !mainEl || !subEl) return;
  const mainText = String(midForm.main_text?.value || '').trim();
  const subText = String(midForm.sub_text?.value || '').trim();
  mainEl.textContent = mainText;
  subEl.textContent = subText;
  mainEl.hidden = mainText === '';
  subEl.hidden = subText === '';
  copy.hidden = mainText === '' && subText === '';
  applyPreviewStyle(
    mainEl,
    midForm.main_font?.value || MID_DEFAULTS.main_font,
    Number(midForm.main_size?.value) || MID_DEFAULTS.main_size,
    (midForm.main_color?.value || MID_DEFAULTS.main_color).toUpperCase()
  );
  applyPreviewStyle(
    subEl,
    midForm.sub_font?.value || MID_DEFAULTS.sub_font,
    Number(midForm.sub_size?.value) || MID_DEFAULTS.sub_size,
    (midForm.sub_color?.value || MID_DEFAULTS.sub_color).toUpperCase()
  );
  const alignH = midForm.querySelector('[name="text_align_h"]:checked')?.value || MID_DEFAULTS.text_align_h;
  const alignV = midForm.querySelector('[name="text_align_v"]:checked')?.value || MID_DEFAULTS.text_align_v;
  copy.className = `mid-banner-copy mid-banner-copy--h-${alignH} mid-banner-copy--v-${alignV}`;
}

function bindMidColorSync() {
  midForm?.querySelectorAll('[data-color-for]').forEach((picker) => {
    const name = picker.getAttribute('data-color-for');
    const text = midForm.querySelector(`[name="${name}"]`);
    picker.addEventListener('input', () => {
      if (text) text.value = picker.value.toUpperCase();
      refreshMidPreview();
    });
    text?.addEventListener('input', () => {
      const v = text.value.trim();
      if (/^#?[0-9a-fA-F]{6}$/.test(v)) {
        picker.value = v.startsWith('#') ? v : `#${v}`;
      }
      refreshMidPreview();
    });
  });
}

function openMidModal(row = {}) {
  if (!midModal || !midForm) return;
  document.getElementById('midBannerModalTitle').textContent = row.id ? '중간배너 수정' : '중간배너 추가';
  if (row.image_url && !row.image_src) {
    const thumb = document.querySelector(`.js-mid-edit[data-id="${row.id}"]`)?.closest('tr')?.querySelector('img.admin-thumb');
    if (thumb?.src) row.image_src = thumb.src;
  }
  midForm.innerHTML = buildMidBannerForm({ ...MID_DEFAULTS, ...row });
  bindMidUpload();
  bindMidColorSync();
  refreshMidPreview();
  midModal.hidden = false;
}

function bindMidUpload() {
  const fileInput = document.getElementById('midFile');
  const status = document.getElementById('midUploadStatus');
  const wrap = document.getElementById('midPreviewWrap');
  const hidden = document.getElementById('midImageUrl');
  fileInput?.addEventListener('change', async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (status) status.textContent = '업로드 중…';
    if (wrap) {
      const localUrl = URL.createObjectURL(file);
      const copy = document.getElementById('midPreviewCopy');
      wrap.querySelector('img')?.remove();
      wrap.querySelector('.mid-form-preview-empty')?.remove();
      wrap.insertAdjacentHTML('afterbegin', `<img src="${localUrl}" alt="">`);
      if (copy) wrap.appendChild(copy);
    }
    try {
      const data = await MidBannerAPI.upload(file);
      const path = data.data?.path || '';
      const url = data.data?.url || '';
      if (hidden) hidden.value = path;
      if (wrap && url) {
        const img = wrap.querySelector('img');
        if (img) img.src = url;
      }
      if (status) status.textContent = '업로드 완료';
      refreshMidPreview();
    } catch (err) {
      if (status) status.textContent = '';
      if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
      else alert(err.message);
    }
  });
}

function closeMidModal() {
  if (midModal) midModal.hidden = true;
}

document.querySelectorAll('[data-close="midBannerModal"]').forEach((el) => {
  el.addEventListener('click', closeMidModal);
});

document.querySelector('.js-mid-add')?.addEventListener('click', () => openMidModal({ is_active: 1, sort_order: 0 }));

document.querySelectorAll('.js-mid-edit').forEach((btn) => {
  btn.addEventListener('click', () => {
    try {
      openMidModal(JSON.parse(btn.dataset.row || '{}'));
    } catch (_) {
      openMidModal({});
    }
  });
});

document.querySelectorAll('.js-mid-delete').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const id = Number(btn.dataset.id);
    if (!id || !window.confirm('삭제하시겠습니까?')) return;
    try {
      await MidBannerAPI.post('/api/admin/mid-banner/delete', { id });
      showAdminAlert('삭제되었습니다.', 'success');
      window.location.reload();
    } catch (err) {
      showAdminAlert(err.message, 'error');
    }
  });
});

function preserveMidFormScroll(run) {
  const scroller = midForm?.querySelector('.mid-form-fields') || midForm;
  const top = scroller?.scrollTop || 0;
  run();
  if (scroller) scroller.scrollTop = top;
}

midForm?.addEventListener('input', () => preserveMidFormScroll(refreshMidPreview));
midForm?.addEventListener('change', (e) => {
  preserveMidFormScroll(() => {
    if (e.target instanceof HTMLInputElement && e.target.type === 'radio') {
      e.target.blur();
    }
    refreshMidPreview();
  });
});
midForm?.addEventListener('focusin', (e) => {
  if (e.target instanceof HTMLInputElement && e.target.type === 'radio') {
    e.target.blur();
  }
});

midForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(midForm);
  const body = Object.fromEntries(fd.entries());
  body.is_active = midForm.querySelector('[name=is_active]')?.checked ? 1 : 0;
  body.main_size = Number(body.main_size || MID_DEFAULTS.main_size);
  body.sub_size = Number(body.sub_size || MID_DEFAULTS.sub_size);
  body.main_color = String(body.main_color || MID_DEFAULTS.main_color).toUpperCase();
  body.sub_color = String(body.sub_color || MID_DEFAULTS.sub_color).toUpperCase();
  if (!String(body.image_url || '').trim()) {
    showAdminAlert('연출 이미지를 업로드해주세요.', 'error');
    return;
  }
  try {
    await MidBannerAPI.post('/api/admin/mid-banner/save', body);
    showAdminAlert('저장되었습니다.', 'success');
    window.location.reload();
  } catch (err) {
    showAdminAlert(err.message, 'error');
  }
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeMidModal();
});
