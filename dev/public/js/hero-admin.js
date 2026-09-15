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

function buildHeroForm(row = {}) {
  const id = row.id || 0;
  const imageUrl = row.image_url || '';
  const previewSrc = row.image_src || (imageUrl ? imageUrl : '');
  const preview = previewSrc
    ? `<img class="hero-form-preview" src="${escapeHtml(previewSrc)}" alt="">`
    : `<div class="hero-form-preview hero-form-preview--empty">이미지를 업로드하세요</div>`;

  let html = `<input type="hidden" name="id" value="${id}">`;
  html += `<input type="hidden" name="image_url" id="heroImageUrl" value="${escapeHtml(imageUrl)}">`;
  html += heroField('제목', 'title', row.title ?? '');
  html += heroField('대체 텍스트(alt)', 'alt_text', row.alt_text ?? '', 'text', { required: true });
  html += `
    <div class="admin-field">
      <label>히어로 이미지</label>
      <div class="hero-upload-row">
        <div id="heroPreviewWrap">${preview}</div>
        <div class="hero-upload-controls">
          <input type="file" id="heroFile" accept="image/png,image/jpeg,image/webp,image/gif">
          <small class="admin-muted">PNG / JPG / WEBP · 권장 비율 16:9 (예: 1536×864)</small>
          <div id="heroUploadStatus" class="admin-muted"></div>
        </div>
      </div>
    </div>`;
  html += heroField('링크 URL', 'link_url', row.link_url ?? '', 'text', { hint: '클릭 시 이동할 경로 (선택)' });
  html += heroField('정렬', 'sort_order', row.sort_order ?? 0, 'number');
  html += heroField('노출', 'is_active', row.is_active, 'checkbox');
  return html;
}

const heroModal = document.getElementById('heroModal');
const heroForm = document.getElementById('heroForm');

function openHeroModal(row = {}) {
  if (!heroModal || !heroForm) return;
  document.getElementById('heroModalTitle').textContent = row.id ? '슬라이드 수정' : '슬라이드 추가';
  // ensure image_src for preview when editing from table data
  if (row.image_url && !row.image_src) {
    const thumb = document.querySelector(`.js-hero-edit[data-id="${row.id}"]`)?.closest('tr')?.querySelector('img.admin-thumb');
    if (thumb?.src) row.image_src = thumb.src;
  }
  heroForm.innerHTML = buildHeroForm(row);
  bindHeroUpload();
  heroModal.hidden = false;
}

function bindHeroUpload() {
  const fileInput = document.getElementById('heroFile');
  const status = document.getElementById('heroUploadStatus');
  const wrap = document.getElementById('heroPreviewWrap');
  const hidden = document.getElementById('heroImageUrl');
  fileInput?.addEventListener('change', async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (status) status.textContent = '업로드 중…';
    // local preview first
    if (wrap) {
      const localUrl = URL.createObjectURL(file);
      wrap.innerHTML = `<img class="hero-form-preview" src="${localUrl}" alt="">`;
    }
    try {
      const data = await HeroAPI.upload(file);
      const path = data.data?.path || '';
      const url = data.data?.url || '';
      if (hidden) hidden.value = path;
      if (wrap && url) wrap.innerHTML = `<img class="hero-form-preview" src="${escapeHtml(url)}" alt="">`;
      if (status) status.textContent = '업로드 완료';
    } catch (err) {
      if (status) status.textContent = '';
      if (typeof showAdminAlert === 'function') showAdminAlert(err.message, 'error');
      else alert(err.message);
    }
  });
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

heroForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(heroForm);
  const body = Object.fromEntries(fd.entries());
  body.is_active = heroForm.querySelector('[name=is_active]')?.checked ? 1 : 0;
  if (!String(body.image_url || '').trim()) {
    showAdminAlert('히어로 이미지를 업로드해주세요.', 'error');
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
