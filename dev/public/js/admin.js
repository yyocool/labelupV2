const AdminAPI = {
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

const ADMIN_LNB_KEY = 'labelup_admin_lnb_collapsed';

function initAdminLnbToggle() {
  const app = document.getElementById('adminApp');
  const toggle = document.getElementById('adminLnbToggle');
  if (!app || !toggle) return;

  const applyState = (collapsed) => {
    app.classList.toggle('is-lnb-collapsed', collapsed);
    toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    toggle.setAttribute('aria-label', collapsed ? '사이드바 펼치기' : '사이드바 접기');
    toggle.title = collapsed ? '사이드바 펼치기' : '사이드바 접기';
  };

  applyState(localStorage.getItem(ADMIN_LNB_KEY) === '1');

  toggle.addEventListener('click', () => {
    const collapsed = !app.classList.contains('is-lnb-collapsed');
    applyState(collapsed);
    localStorage.setItem(ADMIN_LNB_KEY, collapsed ? '1' : '0');
  });
}

function showAdminAlert(message, type = 'success') {
  const el = document.getElementById('adminAlert');
  if (!el) return;
  el.textContent = message;
  el.className = `admin-alert show ${type}`;
  window.clearTimeout(showAdminAlert._timer);
  showAdminAlert._timer = window.setTimeout(() => {
    el.className = 'admin-alert';
  }, 4000);
}

document.querySelectorAll('.js-save-user').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const userId = Number(btn.dataset.userId);
    const row = btn.closest('[data-user-row]');
    if (!row || !userId) return;

    const role = row.querySelector('.js-role')?.value;
    const status = row.querySelector('.js-status')?.value;
    btn.disabled = true;

    try {
      await AdminAPI.post('/api/admin/users/update', { user_id: userId, role, status });
      showAdminAlert('회원 정보가 저장되었습니다.', 'success');
    } catch (err) {
      showAdminAlert(err.message, 'error');
    } finally {
      btn.disabled = false;
    }
  });
});

initAdminLnbToggle();

document.querySelectorAll('.admin-lnb-group-toggle').forEach((btn) => {
  btn.addEventListener('click', () => {
    btn.closest('.admin-lnb-group')?.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', btn.closest('.admin-lnb-group')?.classList.contains('is-open') ? 'true' : 'false');
  });
});

const LEGAL_EDITOR_OPTS = {
  lang: 'ko-KR',
  height: 360,
  placeholder: '약관 내용을 입력하세요.',
  toolbar: [
    ['style', ['style']],
    ['font', ['bold', 'italic', 'underline', 'clear']],
    ['para', ['ul', 'ol', 'paragraph']],
    ['insert', ['link', 'table', 'hr']],
    ['view', ['fullscreen', 'codeview']],
  ],
};

function isLegalEditorActive(textarea) {
  return window.jQuery && jQuery(textarea).next('.note-editor').length > 0;
}

function initLegalEditor(textarea) {
  if (!window.jQuery || !jQuery.fn.summernote || !textarea) return;
  const $el = jQuery(textarea);
  if ($el.next('.note-editor').length) return;
  $el.summernote(LEGAL_EDITOR_OPTS);
}

function getLegalEditorContent(textarea) {
  if (!textarea) return '';
  if (isLegalEditorActive(textarea)) {
    return jQuery(textarea).summernote('code') || '';
  }
  return textarea.value || '';
}

function initActiveLegalEditors() {
  document.querySelectorAll('.admin-legal-panel.is-active .js-legal-editor').forEach(initLegalEditor);
}

function switchLegalTab(key) {
  document.querySelectorAll('.admin-legal-tab').forEach((tab) => {
    tab.classList.toggle('is-active', tab.dataset.tab === key);
  });
  document.querySelectorAll('.admin-legal-panel').forEach((panel) => {
    panel.classList.toggle('is-active', panel.dataset.panel === key);
  });
  window.setTimeout(initActiveLegalEditors, 0);
}

document.querySelectorAll('.admin-legal-tab').forEach((tab) => {
  tab.addEventListener('click', () => {
    switchLegalTab(tab.dataset.tab);
  });
});

if (document.querySelector('.js-legal-editor')) {
  initActiveLegalEditors();
}

document.querySelectorAll('.admin-legal-form').forEach((form) => {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type=submit]');
    const contentEl = form.querySelector('.js-legal-editor');
    btn.disabled = true;
    try {
      const fd = new FormData(form);
      await AdminAPI.post('/api/admin/legal/update', {
        doc_key: form.dataset.docKey,
        title: fd.get('title'),
        content: getLegalEditorContent(contentEl),
      });
      showAdminAlert('약관이 저장되었습니다.', 'success');
      setTimeout(() => location.reload(), 600);
    } catch (err) {
      showAdminAlert(err.message, 'error');
    } finally {
      btn.disabled = false;
    }
  });
});
