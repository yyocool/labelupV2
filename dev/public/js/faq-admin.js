const FaqAPI = {
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

const FAQ_EDITOR_OPTS = {
  lang: 'ko-KR',
  height: 240,
  placeholder: '답변을 입력하세요',
  toolbar: [
    ['style', ['style']],
    ['font', ['bold', 'underline', 'clear']],
    ['fontsize', ['fontsize']],
    ['color', ['color']],
    ['para', ['ul', 'ol', 'paragraph']],
    ['insert', ['link']],
    ['view', ['fullscreen', 'codeview']],
  ],
  dialogsInBody: true,
};

function escapeAttr(s) {
  return String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;');
}

function destroyFaqSummernote() {
  const ta = document.querySelector('.js-faq-answer');
  if (ta && window.jQuery && jQuery.fn.summernote && jQuery(ta).next('.note-editor').length) {
    jQuery(ta).summernote('destroy');
  }
}

function initFaqSummernote() {
  const ta = document.querySelector('.js-faq-answer');
  if (!ta || !window.jQuery || !jQuery.fn.summernote) return;
  const $el = jQuery(ta);
  if ($el.next('.note-editor').length) return;
  $el.summernote(FAQ_EDITOR_OPTS);
}

function getFaqAnswer() {
  const ta = document.querySelector('.js-faq-answer');
  if (!ta) return '';
  if (window.jQuery && jQuery.fn.summernote && jQuery(ta).next('.note-editor').length) {
    const code = jQuery(ta).summernote('code') || '';
    if (code === '<p><br></p>' || code === '<p></p>') return '';
    return code;
  }
  return ta.value || '';
}

function categoryOptions(selected) {
  const cats = Array.isArray(window.LABELUP_FAQ_CATEGORIES) ? window.LABELUP_FAQ_CATEGORIES : [];
  if (!cats.length) return '<option value="">카테고리를 먼저 추가하세요</option>';
  return cats.map((c) => {
    const sel = Number(c.id) === Number(selected) ? ' selected' : '';
    return `<option value="${c.id}"${sel}>${escapeAttr(c.name)}</option>`;
  }).join('');
}

function buildCatForm(row = {}) {
  return `
    <input type="hidden" name="id" value="${row.id || 0}">
    <div class="admin-form-grid">
      <label class="admin-field admin-field--full"><span>이름</span>
        <input class="admin-input" name="name" required value="${escapeAttr(row.name || '')}">
      </label>
      <label class="admin-field"><span>슬러그</span>
        <input class="admin-input" name="slug" placeholder="start" value="${escapeAttr(row.slug || '')}">
        <small>영문·숫자·하이픈. 비우면 자동 생성</small>
      </label>
      <label class="admin-field"><span>정렬</span>
        <input class="admin-input" type="number" name="sort_order" value="${Number(row.sort_order ?? 0)}">
      </label>
      <label class="admin-field admin-check">
        <input type="checkbox" name="is_active" ${row.is_active === undefined || Number(row.is_active) ? 'checked' : ''}> 노출
      </label>
    </div>`;
}

function buildFaqForm(row = {}) {
  return `
    <input type="hidden" name="id" value="${row.id || 0}">
    <div class="admin-form-grid">
      <label class="admin-field"><span>카테고리</span>
        <select class="admin-input" name="category_id" required>${categoryOptions(row.category_id)}</select>
      </label>
      <label class="admin-field"><span>정렬</span>
        <input class="admin-input" type="number" name="sort_order" value="${Number(row.sort_order ?? 0)}">
      </label>
      <label class="admin-field admin-field--full"><span>질문</span>
        <input class="admin-input" name="question" required value="${escapeAttr(row.question || '')}">
      </label>
      <div class="admin-field admin-field--full admin-field--editor">
        <span>답변</span>
        <textarea class="admin-input js-faq-answer" name="answer" rows="8">${escapeAttr(row.answer || '')}</textarea>
      </div>
      <label class="admin-field admin-check">
        <input type="checkbox" name="is_active" ${row.is_active === undefined || Number(row.is_active) ? 'checked' : ''}> 노출
      </label>
    </div>`;
}

const catModal = document.getElementById('faqCatModal');
const catForm = document.getElementById('faqCatForm');
const faqModal = document.getElementById('faqModal');
const faqForm = document.getElementById('faqForm');

function openCatModal(row = {}) {
  if (!catModal || !catForm) return;
  document.getElementById('faqCatModalTitle').textContent = row.id ? '카테고리 수정' : '카테고리 추가';
  catForm.innerHTML = buildCatForm(row);
  catModal.hidden = false;
}

function closeCatModal() {
  if (catModal) catModal.hidden = true;
}

function openFaqModal(row = {}) {
  if (!faqModal || !faqForm) return;
  destroyFaqSummernote();
  document.getElementById('faqModalTitle').textContent = row.id ? 'FAQ 수정' : 'FAQ 추가';
  faqForm.innerHTML = buildFaqForm(row);
  faqModal.hidden = false;
  window.setTimeout(initFaqSummernote, 30);
}

function closeFaqModal() {
  destroyFaqSummernote();
  if (faqModal) faqModal.hidden = true;
}

document.querySelectorAll('[data-close="faqCatModal"]').forEach((el) => el.addEventListener('click', closeCatModal));
document.querySelectorAll('[data-close="faqModal"]').forEach((el) => el.addEventListener('click', closeFaqModal));

document.querySelector('.js-faq-cat-add')?.addEventListener('click', () => openCatModal({ is_active: 1 }));
document.querySelector('.js-faq-add')?.addEventListener('click', () => openFaqModal({ is_active: 1 }));

document.querySelectorAll('.js-faq-cat-edit').forEach((btn) => {
  btn.addEventListener('click', () => {
    try { openCatModal(JSON.parse(btn.dataset.row || '{}')); } catch (_) { openCatModal({}); }
  });
});

document.querySelectorAll('.js-faq-edit').forEach((btn) => {
  btn.addEventListener('click', () => {
    try { openFaqModal(JSON.parse(btn.dataset.row || '{}')); } catch (_) { openFaqModal({}); }
  });
});

document.querySelectorAll('.js-faq-cat-delete').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const id = Number(btn.dataset.id);
    if (!id || !window.confirm('이 카테고리를 삭제할까요?')) return;
    try {
      await FaqAPI.post('/api/admin/faq/category/delete', { id });
      window.location.reload();
    } catch (err) {
      alert(err.message);
    }
  });
});

document.querySelectorAll('.js-faq-delete').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const id = Number(btn.dataset.id);
    if (!id || !window.confirm('이 FAQ를 삭제할까요?')) return;
    try {
      await FaqAPI.post('/api/admin/faq/delete', { id });
      window.location.reload();
    } catch (err) {
      alert(err.message);
    }
  });
});

catForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(catForm);
  const body = Object.fromEntries(fd.entries());
  body.is_active = catForm.querySelector('[name=is_active]')?.checked ? 1 : 0;
  body.sort_order = Number(body.sort_order || 0);
  body.id = Number(body.id || 0);
  try {
    await FaqAPI.post('/api/admin/faq/category/save', body);
    window.location.reload();
  } catch (err) {
    alert(err.message);
  }
});

faqForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(faqForm);
  const body = Object.fromEntries(fd.entries());
  body.answer = getFaqAnswer();
  body.is_active = faqForm.querySelector('[name=is_active]')?.checked ? 1 : 0;
  body.sort_order = Number(body.sort_order || 0);
  body.category_id = Number(body.category_id || 0);
  body.id = Number(body.id || 0);
  try {
    await FaqAPI.post('/api/admin/faq/save', body);
    window.location.reload();
  } catch (err) {
    alert(err.message);
  }
});
