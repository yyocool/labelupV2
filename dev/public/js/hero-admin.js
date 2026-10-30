const HeroAPI = {
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
    const res = await fetch('/api/admin/hero/slide/upload', {
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

const HERO_FONTS = {
  'pretendard-extrabold': { label: 'Pretendard ExtraBold', family: 'Pretendard', weight: 800 },
  'pretendard-bold': { label: 'Pretendard Bold', family: 'Pretendard', weight: 700 },
  'pretendard-semibold': { label: 'Pretendard SemiBold', family: 'Pretendard', weight: 600 },
  'pretendard-medium': { label: 'Pretendard Medium', family: 'Pretendard', weight: 500 },
  'pretendard-regular': { label: 'Pretendard Regular', family: 'Pretendard', weight: 400 },
  'pretendard-light': { label: 'Pretendard Light', family: 'Pretendard', weight: 300 },
};

const HERO_DEFAULTS = {
  main_font: 'pretendard-extrabold',
  main_size: 44,
  main_size_mobile: 26,
  main_color: '#FFFFFF',
  sub_font: 'pretendard-medium',
  sub_size: 20,
  sub_size_mobile: 14,
  sub_color: '#FFFFFF',
  text_align_h: 'left',
  text_align_v: 'middle',
};

/** 홈 화면에서 실제로 보이는 히어로 폭(px). 미리보기 글자 크기를 이 비율로 줄인다. */
const HERO_REAL_WIDTH = { pc: 1355, mobile: 366 };

function escapeHtml(s) {
  return String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function heroField(label, name, value = '', type = 'text', opts = {}) {
  const req = opts.required ? ' required' : '';
  if (type === 'checkbox') {
    const checked = value ? ' checked' : '';
    return `<label class="admin-field admin-field--check"><input type="checkbox" name="${name}" value="1"${checked}> ${label}</label>`;
  }
  const hint = opts.hint ? `<small class="admin-muted">${opts.hint}</small>` : '';
  return `<div class="admin-field"><label>${label}</label><input type="${type}" name="${name}" value="${escapeHtml(value ?? '')}"${req}>${hint}</div>`;
}

function fontOptions(selected) {
  return Object.entries(HERO_FONTS).map(([key, font]) => (
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
  const font = row[`${prefix}_font`] || HERO_DEFAULTS[`${prefix}_font`];
  const size = row[`${prefix}_size`] || HERO_DEFAULTS[`${prefix}_size`];
  const sizeMobile = row[`${prefix}_size_mobile`] || HERO_DEFAULTS[`${prefix}_size_mobile`];
  const color = String(row[`${prefix}_color`] || HERO_DEFAULTS[`${prefix}_color`]).toUpperCase();
  return `
    <div class="hero-text-style">
      <div class="admin-field hero-text-style__font">
        <label>폰트</label>
        <select name="${prefix}_font">${fontOptions(font)}</select>
      </div>
      <div class="admin-field">
        <label>PC 크기(px)</label>
        <input type="number" name="${prefix}_size" min="12" max="120" value="${escapeHtml(size)}">
      </div>
      <div class="admin-field">
        <label>모바일 크기(px)</label>
        <input type="number" name="${prefix}_size_mobile" min="12" max="120" value="${escapeHtml(sizeMobile)}">
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

function previewBox(kind, src) {
  const label = kind === 'pc' ? 'PC' : '모바일';
  const img = src ? `<img src="${escapeHtml(src)}" alt="">` : `<div class="hero-prev-empty">${label} 이미지</div>`;
  return `
    <div class="hero-prev hero-prev--${kind}" data-hero-prev="${kind}">
      ${img}
      <div class="hero-prev-copy" data-preview-copy hidden>
        <p class="hero-prev-main" data-preview="main"></p>
        <p class="hero-prev-sub" data-preview="sub"></p>
      </div>
      <span class="hero-prev-prompt">AI 입력창이 겹치는 영역</span>
    </div>`;
}

function uploadField(kind, label, hint) {
  return `
    <div class="admin-field">
      <label>${label}</label>
      <div class="hero-upload-controls">
        <input type="file" data-hero-file="${kind}" accept="image/png,image/jpeg,image/webp,image/gif">
        <small class="admin-muted">${hint}</small>
        <div class="admin-muted" data-hero-status="${kind}"></div>
        ${kind === 'mobile' ? '<button type="button" class="admin-btn admin-btn--sm" data-hero-clear-mobile>모바일 이미지 지우기</button>' : ''}
      </div>
    </div>`;
}

function buildHeroForm(row = {}) {
  const id = row.id || 0;
  let html = `<input type="hidden" name="id" value="${id}">`;
  html += `<input type="hidden" name="image_url" value="${escapeHtml(row.image_url || '')}">`;
  html += `<input type="hidden" name="mobile_image_url" value="${escapeHtml(row.mobile_image_url || '')}">`;
  html += `<div class="mid-form-layout">
    <aside class="mid-form-preview-col">
      <div class="admin-field admin-field--full">
        <label>미리보기</label>
        <div class="hero-prev-pair">
          <div><small class="admin-muted">PC</small>${previewBox('pc', row.image_src || '')}</div>
          <div><small class="admin-muted">모바일 (560px 이하)</small>${previewBox('mobile', row.mobile_image_src || '')}</div>
        </div>
        <small class="admin-muted">텍스트·크기·위치 변경이 바로 반영됩니다. 모바일 이미지가 없으면 모바일에서도 PC 이미지를 잘라서 씁니다.</small>
      </div>
    </aside>
    <div class="mid-form-fields">`;
  html += heroField('제목', 'title', row.title ?? '', 'text', { hint: '관리용 이름 (화면에 표시되지 않음)' });
  html += heroField('대체 텍스트(alt)', 'alt_text', row.alt_text ?? '', 'text', { required: true });
  html += uploadField('pc', 'PC 이미지', 'PNG / JPG / WEBP · 권장 2400×800 (3:1). 화면 폭에 따라 위아래가 잘리니 중요한 내용은 가운데에 두세요.');
  html += uploadField('mobile', '모바일 이미지 (선택)', 'PNG / JPG / WEBP · 권장 1080×1240 (약 7:8 세로형). 아래 약 9%는 AI 입력창에 가려집니다.');
  html += heroField('링크 URL', 'link_url', row.link_url ?? '', 'text', { hint: '클릭 시 이동할 경로 (선택)' });
  html += `<div class="mid-form-grid">`;
  html += heroField('정렬', 'sort_order', row.sort_order ?? 0, 'number');
  html += heroField('노출', 'is_active', row.is_active, 'checkbox');
  html += `</div>`;
  html += `
    <div class="mid-text-block mid-text-block--align">
      <div class="admin-field">
        <label>텍스트 위치 (타이틀·서브타이틀 공통)</label>
        <div class="mid-align-row">
          <div>
            <small class="admin-muted">가로</small>
            ${alignPills('text_align_h', row.text_align_h || HERO_DEFAULTS.text_align_h, [['left', '좌'], ['center', '중'], ['right', '우']])}
          </div>
          <div>
            <small class="admin-muted">세로</small>
            ${alignPills('text_align_v', row.text_align_v || HERO_DEFAULTS.text_align_v, [['top', '상'], ['middle', '중'], ['bottom', '하']])}
          </div>
        </div>
      </div>
    </div>
    <div class="mid-text-block">
      <div class="admin-field admin-field--short">
        <label>타이틀</label>
        <textarea name="main_text" rows="2" placeholder="이미지 위에 크게 표시될 문구 (줄바꿈 가능)">${escapeHtml(row.main_text ?? '')}</textarea>
      </div>
      ${textStyleRow('main', row)}
    </div>
    <div class="mid-text-block">
      <div class="admin-field admin-field--short">
        <label>서브타이틀</label>
        <textarea name="sub_text" rows="2" placeholder="타이틀 아래 보조 문구">${escapeHtml(row.sub_text ?? '')}</textarea>
      </div>
      ${textStyleRow('sub', row)}
    </div>`;
  html += `</div></div>`;
  return html;
}

const heroModal = document.getElementById('heroModal');
const heroForm = document.getElementById('heroForm');

function applyPreviewText(el, text, fontKey, size, color) {
  const font = HERO_FONTS[fontKey] || HERO_FONTS['pretendard-regular'];
  el.textContent = text;
  el.hidden = text === '';
  el.style.fontFamily = `${font.family}, sans-serif`;
  el.style.fontWeight = String(font.weight);
  el.style.fontSize = `${size}px`;
  el.style.color = color;
}

function refreshHeroPreview() {
  if (!heroForm) return;
  const mainText = String(heroForm.main_text?.value || '').trim();
  const subText = String(heroForm.sub_text?.value || '').trim();
  const alignH = heroForm.querySelector('[name="text_align_h"]:checked')?.value || HERO_DEFAULTS.text_align_h;
  const alignV = heroForm.querySelector('[name="text_align_v"]:checked')?.value || HERO_DEFAULTS.text_align_v;
  heroForm.querySelectorAll('[data-hero-prev]').forEach((box) => {
    const kind = box.getAttribute('data-hero-prev');
    const scale = (box.clientWidth || 1) / HERO_REAL_WIDTH[kind];
    const sizeKey = kind === 'pc' ? '' : '_mobile';
    const copy = box.querySelector('[data-preview-copy]');
    copy.hidden = mainText === '' && subText === '';
    copy.className = `hero-prev-copy hero-prev-copy--h-${alignH} hero-prev-copy--v-${alignV}`;
    applyPreviewText(
      copy.querySelector('[data-preview="main"]'),
      mainText,
      heroForm.main_font?.value,
      (Number(heroForm[`main_size${sizeKey}`]?.value) || HERO_DEFAULTS[`main_size${sizeKey}`]) * scale,
      (heroForm.main_color?.value || HERO_DEFAULTS.main_color).toUpperCase()
    );
    applyPreviewText(
      copy.querySelector('[data-preview="sub"]'),
      subText,
      heroForm.sub_font?.value,
      (Number(heroForm[`sub_size${sizeKey}`]?.value) || HERO_DEFAULTS[`sub_size${sizeKey}`]) * scale,
      (heroForm.sub_color?.value || HERO_DEFAULTS.sub_color).toUpperCase()
    );
  });
}

function setPreviewImage(kind, src) {
  const box = heroForm?.querySelector(`[data-hero-prev="${kind}"]`);
  if (!box) return;
  box.querySelector('img')?.remove();
  box.querySelector('.hero-prev-empty')?.remove();
  if (src) {
    box.insertAdjacentHTML('afterbegin', `<img src="${escapeHtml(src)}" alt="">`);
  } else {
    box.insertAdjacentHTML('afterbegin', `<div class="hero-prev-empty">${kind === 'pc' ? 'PC' : '모바일'} 이미지</div>`);
  }
}

function bindHeroColorSync() {
  heroForm?.querySelectorAll('[data-color-for]').forEach((picker) => {
    const text = heroForm.querySelector(`[name="${picker.getAttribute('data-color-for')}"]`);
    picker.addEventListener('input', () => {
      if (text) text.value = picker.value.toUpperCase();
      refreshHeroPreview();
    });
    text?.addEventListener('input', () => {
      const v = text.value.trim();
      if (/^#?[0-9a-fA-F]{6}$/.test(v)) picker.value = v.startsWith('#') ? v : `#${v}`;
      refreshHeroPreview();
    });
  });
}

function bindHeroUploads() {
  heroForm?.querySelectorAll('[data-hero-file]').forEach((input) => {
    const kind = input.getAttribute('data-hero-file');
    const status = heroForm.querySelector(`[data-hero-status="${kind}"]`);
    const hidden = heroForm.querySelector(kind === 'pc' ? '[name="image_url"]' : '[name="mobile_image_url"]');
    input.addEventListener('change', async (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      if (status) status.textContent = '업로드 중…';
      setPreviewImage(kind, URL.createObjectURL(file));
      refreshHeroPreview();
      try {
        const data = await HeroAPI.upload(file);
        if (hidden) hidden.value = data.data?.path || '';
        if (data.data?.url) setPreviewImage(kind, data.data.url);
        if (status) status.textContent = '업로드 완료';
      } catch (err) {
        if (status) status.textContent = '';
        showAdminAlert(err.message, 'error');
      }
      refreshHeroPreview();
    });
  });
  heroForm?.querySelector('[data-hero-clear-mobile]')?.addEventListener('click', () => {
    const hidden = heroForm.querySelector('[name="mobile_image_url"]');
    if (hidden) hidden.value = '';
    const input = heroForm.querySelector('[data-hero-file="mobile"]');
    if (input) input.value = '';
    setPreviewImage('mobile', '');
    refreshHeroPreview();
  });
}

function openHeroModal(row = {}) {
  if (!heroModal || !heroForm) return;
  document.getElementById('heroModalTitle').textContent = row.id ? '슬라이드 수정' : '슬라이드 추가';
  heroForm.innerHTML = buildHeroForm({ ...HERO_DEFAULTS, ...row });
  bindHeroUploads();
  bindHeroColorSync();
  heroModal.hidden = false;
  requestAnimationFrame(refreshHeroPreview);
}

function closeHeroModal() {
  if (heroModal) heroModal.hidden = true;
}

document.querySelectorAll('[data-close="heroModal"]').forEach((el) => {
  el.addEventListener('click', closeHeroModal);
});

document.querySelector('.js-hero-add')?.addEventListener('click', () => openHeroModal({ is_active: 1, sort_order: 0 }));

document.querySelectorAll('.js-hero-edit').forEach((btn) => {
  btn.addEventListener('click', () => {
    try {
      openHeroModal(JSON.parse(btn.dataset.row || '{}'));
    } catch (_) {
      openHeroModal({});
    }
  });
});

document.querySelectorAll('.js-hero-delete').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const id = Number(btn.dataset.id);
    if (!id || !window.confirm('삭제하시겠습니까?')) return;
    try {
      await HeroAPI.post('/api/admin/hero/slide/delete', { id });
      showAdminAlert('삭제되었습니다.', 'success');
      window.location.reload();
    } catch (err) {
      showAdminAlert(err.message, 'error');
    }
  });
});

heroForm?.addEventListener('input', refreshHeroPreview);
heroForm?.addEventListener('change', (e) => {
  if (e.target instanceof HTMLInputElement && e.target.type === 'radio') e.target.blur();
  refreshHeroPreview();
});
window.addEventListener('resize', () => {
  if (heroModal && !heroModal.hidden) refreshHeroPreview();
});

heroForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const body = Object.fromEntries(new FormData(heroForm).entries());
  body.is_active = heroForm.querySelector('[name=is_active]')?.checked ? 1 : 0;
  ['main_size', 'main_size_mobile', 'sub_size', 'sub_size_mobile'].forEach((key) => {
    body[key] = Number(body[key] || HERO_DEFAULTS[key]);
  });
  body.main_color = String(body.main_color || HERO_DEFAULTS.main_color).toUpperCase();
  body.sub_color = String(body.sub_color || HERO_DEFAULTS.sub_color).toUpperCase();
  if (!String(body.image_url || '').trim()) {
    showAdminAlert('PC 이미지를 업로드해주세요.', 'error');
    return;
  }
  try {
    await HeroAPI.post('/api/admin/hero/slide/save', body);
    showAdminAlert('저장되었습니다.', 'success');
    window.location.reload();
  } catch (err) {
    showAdminAlert(err.message, 'error');
  }
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeHeroModal();
});
