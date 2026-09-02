const PopupAPI = {
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

const POPUP_EDITOR_OPTS = {
  lang: 'ko-KR',
  height: 220,
  placeholder: '팝업에 표시할 내용을 입력하세요',
  toolbar: [
    ['style', ['style']],
    ['font', ['bold', 'underline', 'clear']],
    ['fontsize', ['fontsize']],
    ['color', ['color']],
    ['para', ['ul', 'ol', 'paragraph']],
    ['insert', ['link', 'picture']],
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

function toLocalInput(value) {
  if (!value) return '';
  const d = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(d.getTime())) {
    return String(value).slice(0, 16);
  }
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function destroyPopupSummernote() {
  const ta = document.querySelector('.js-popup-content');
  if (ta && window.jQuery && jQuery.fn.summernote && jQuery(ta).next('.note-editor').length) {
    jQuery(ta).summernote('destroy');
  }
}

function initPopupSummernote() {
  const ta = document.querySelector('.js-popup-content');
  if (!ta || !window.jQuery || !jQuery.fn.summernote) return;
  const $el = jQuery(ta);
  if ($el.next('.note-editor').length) return;
  $el.summernote(POPUP_EDITOR_OPTS);
}

function getPopupContent() {
  const ta = document.querySelector('.js-popup-content');
  if (!ta) return '';
  if (window.jQuery && jQuery.fn.summernote && jQuery(ta).next('.note-editor').length) {
    const code = jQuery(ta).summernote('code') || '';
    if (code === '<p><br></p>' || code === '<p></p>') return '';
    return code;
  }
  return ta.value || '';
}

function buildPopupForm(row = {}) {
  return `
    <input type="hidden" name="id" value="${row.id || 0}">
    <div class="admin-form-grid">
      <label class="admin-field admin-field--full"><span>제목</span>
        <input class="admin-input" name="title" required value="${escapeAttr(row.title || '')}">
      </label>
      <label class="admin-field admin-field--full"><span>이미지 URL</span>
        <input class="admin-input" name="image_url" required placeholder="/assets/hero-tall-1.webp" value="${escapeAttr(row.image_url || '')}">
        <small>예: /assets/hero-tall-1.webp 또는 외부 https URL</small>
      </label>
      <label class="admin-field admin-field--full"><span>링크 URL</span>
        <input class="admin-input" name="link_url" placeholder="/shop" value="${escapeAttr(row.link_url || '')}">
        <small>이미지 클릭 시 이동 (선택)</small>
      </label>
      <div class="admin-field admin-field--full admin-field--editor">
        <span>내용</span>
        <textarea class="admin-input js-popup-content" name="content" rows="8">${escapeAttr(row.content || '')}</textarea>
      </div>
      <label class="admin-field"><span>노출 시작</span>
        <input class="admin-input" type="datetime-local" name="start_at" value="${escapeAttr(toLocalInput(row.start_at))}">
      </label>
      <label class="admin-field"><span>노출 종료</span>
        <input class="admin-input" type="datetime-local" name="end_at" value="${escapeAttr(toLocalInput(row.end_at))}">
      </label>
      <label class="admin-field"><span>다시 보지 않기 (일)</span>
        <input class="admin-input" type="number" min="0" name="hide_days" value="${Number(row.hide_days ?? 1)}">
        <small>0이면 닫기만, 1이면 오늘 하루 숨김</small>
      </label>
      <label class="admin-field"><span>정렬</span>
        <input class="admin-input" type="number" name="sort_order" value="${Number(row.sort_order ?? 0)}">
      </label>
      <label class="admin-field admin-check">
        <input type="checkbox" name="is_active" ${row.is_active === undefined || Number(row.is_active) ? 'checked' : ''}> 노출
      </label>
    </div>`;
}

const popupModal = document.getElementById('popupModal');
const popupForm = document.getElementById('popupForm');

function openPopupModal(row = {}) {
  if (!popupModal || !popupForm) return;
  destroyPopupSummernote();
  document.getElementById('popupModalTitle').textContent = row.id ? '팝업 수정' : '팝업 추가';
  popupForm.innerHTML = buildPopupForm(row);
  popupModal.hidden = false;
  window.setTimeout(initPopupSummernote, 30);
}

function closePopupModal() {
  destroyPopupSummernote();
  if (popupModal) popupModal.hidden = true;
}

document.querySelectorAll('[data-close="popupModal"]').forEach((el) => {
  el.addEventListener('click', closePopupModal);
});

document.querySelector('.js-popup-add')?.addEventListener('click', () => openPopupModal({ hide_days: 1, is_active: 1 }));

document.querySelectorAll('.js-popup-edit').forEach((btn) => {
  btn.addEventListener('click', () => {
    try {
      openPopupModal(JSON.parse(btn.dataset.row || '{}'));
    } catch (_) {
      openPopupModal({});
    }
  });
});

document.querySelectorAll('.js-popup-delete').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const id = Number(btn.dataset.id);
    if (!id || !window.confirm('삭제하시겠습니까?')) return;
    try {
      await PopupAPI.post('/api/admin/event-popup/delete', { id });
      window.location.reload();
    } catch (err) {
      alert(err.message);
    }
  });
});

popupForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(popupForm);
  const body = Object.fromEntries(fd.entries());
  body.content = getPopupContent();
  body.is_active = popupForm.querySelector('[name=is_active]')?.checked ? 1 : 0;
  body.hide_days = Number(body.hide_days || 0);
  body.sort_order = Number(body.sort_order || 0);
  body.id = Number(body.id || 0);
  try {
    await PopupAPI.post('/api/admin/event-popup/save', body);
    window.location.reload();
  } catch (err) {
    alert(err.message);
  }
});
