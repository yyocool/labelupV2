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
};

function heroField(label, name, value = '', type = 'text', opts = {}) {
  const req = opts.required ? ' required' : '';
  if (type === 'checkbox') {
    const checked = value ? ' checked' : '';
    return `<label class="admin-field admin-field--check"><input type="checkbox" name="${name}" value="1"${checked}> ${label}</label>`;
  }
  if (type === 'textarea') {
    return `<div class="admin-field"><label>${label}</label><textarea name="${name}" rows="${opts.rows || 2}"${req}>${value ?? ''}</textarea></div>`;
  }
  const hint = opts.hint ? `<small class="admin-muted">${opts.hint}</small>` : '';
  return `<div class="admin-field"><label>${label}</label><input type="${type}" name="${name}" value="${value ?? ''}"${req}>${hint}</div>`;
}

function buildHeroForm(row = {}) {
  const id = row.id || 0;
  let html = `<input type="hidden" name="id" value="${id}">`;
  html += heroField('제목', 'title', row.title ?? '');
  html += heroField('대체 텍스트(alt)', 'alt_text', row.alt_text ?? '', 'text', { required: true });
  html += heroField('이미지 URL', 'image_url', row.image_url ?? '/assets/hero-tall-1.webp', 'text', {
    required: true,
    hint: '예: /assets/hero-tall-1.webp',
  });
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
  heroForm.innerHTML = buildHeroForm(row);
  heroModal.hidden = false;
}

function closeHeroModal() {
  if (heroModal) heroModal.hidden = true;
}

document.querySelectorAll('[data-close="heroModal"]').forEach((el) => {
  el.addEventListener('click', closeHeroModal);
});

document.querySelector('.js-hero-add')?.addEventListener('click', () => openHeroModal({}));

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
