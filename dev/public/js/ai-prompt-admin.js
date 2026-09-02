const AiPromptAPI = {
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

function escapeAttr(s) {
  return String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;');
}

function buildForm(row = {}) {
  const surface = row.surface || 'both';
  return `
    <input type="hidden" name="id" value="${row.id || 0}">
    <div class="admin-form-grid">
      <label class="admin-field"><span>버튼 이름</span>
        <input class="admin-input" name="label" required maxlength="40" placeholder="☆ 라벨 추천" value="${escapeAttr(row.label || '')}">
      </label>
      <label class="admin-field"><span>노출 위치</span>
        <select class="admin-input" name="surface">
          <option value="both"${surface === 'both' ? ' selected' : ''}>홈 + 편집기</option>
          <option value="home"${surface === 'home' ? ' selected' : ''}>홈만</option>
          <option value="editor"${surface === 'editor' ? ' selected' : ''}>편집기만</option>
        </select>
      </label>
      <label class="admin-field admin-field--full"><span>예시 프롬프트</span>
        <textarea class="admin-input" name="prompt_text" required maxlength="500" rows="3">${escapeAttr(row.prompt_text || '')}</textarea>
        <small>칩을 누르면 입력창에 들어가는 문장입니다.</small>
      </label>
      <label class="admin-field"><span>정렬</span>
        <input class="admin-input" type="number" name="sort_order" value="${Number(row.sort_order ?? 0)}">
      </label>
      <label class="admin-field admin-check">
        <input type="checkbox" name="is_active" ${row.is_active === undefined || Number(row.is_active) ? 'checked' : ''}> 노출
      </label>
    </div>`;
}

const modal = document.getElementById('aiPromptModal');
const form = document.getElementById('aiPromptForm');

function openModal(row = {}) {
  if (!modal || !form) return;
  document.getElementById('aiPromptModalTitle').textContent = row.id ? '예시 수정' : '예시 추가';
  form.innerHTML = buildForm(row);
  modal.hidden = false;
}

function closeModal() {
  if (modal) modal.hidden = true;
}

document.querySelectorAll('[data-close="aiPromptModal"]').forEach((el) => el.addEventListener('click', closeModal));
document.querySelector('.js-ai-prompt-add')?.addEventListener('click', () => openModal({ is_active: 1, surface: 'both' }));

document.querySelectorAll('.js-ai-prompt-edit').forEach((btn) => {
  btn.addEventListener('click', () => {
    try { openModal(JSON.parse(btn.dataset.row || '{}')); } catch (_) { openModal({}); }
  });
});

document.querySelectorAll('.js-ai-prompt-delete').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const id = Number(btn.dataset.id);
    if (!id || !window.confirm('이 예시 프롬프트를 삭제할까요?')) return;
    try {
      await AiPromptAPI.post('/api/admin/ai/example-prompt/delete', { id });
      window.location.reload();
    } catch (err) {
      alert(err.message);
    }
  });
});

form?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(form);
  const body = Object.fromEntries(fd.entries());
  body.is_active = form.querySelector('[name=is_active]')?.checked ? 1 : 0;
  body.sort_order = Number(body.sort_order || 0);
  body.id = Number(body.id || 0);
  try {
    await AiPromptAPI.post('/api/admin/ai/example-prompt/save', body);
    window.location.reload();
  } catch (err) {
    alert(err.message);
  }
});
