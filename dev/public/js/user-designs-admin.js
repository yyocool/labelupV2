(() => {
  const cfg = window.LABELUP_USER_DESIGN_ADMIN || {};
  const urls = cfg.urls || {};
  const alertEl = document.getElementById('adminAlert');
  const rows = Array.from(document.querySelectorAll('.ud-row'));
  let current = null;

  function showAlert(msg, ok) {
    if (!alertEl) return;
    alertEl.textContent = msg;
    alertEl.className = `admin-alert ${ok ? 'is-ok' : 'is-err'}`;
    alertEl.style.display = 'block';
  }

  function parseRow(el) {
    try {
      return JSON.parse(el.getAttribute('data-row') || '{}');
    } catch (e) {
      return {};
    }
  }

  function fillPanel(row) {
    current = row;
    const empty = document.getElementById('udPanelEmpty');
    const body = document.getElementById('udPanelBody');
    if (!row || !row.id) {
      if (empty) empty.hidden = false;
      if (body) body.hidden = true;
      return;
    }
    if (empty) empty.hidden = true;
    if (body) body.hidden = false;
    const title = document.getElementById('udPanelTitle');
    const img = document.getElementById('udPreviewImg');
    const author = document.getElementById('udAuthor');
    const created = document.getElementById('udCreated');
    const prompt = document.getElementById('udPrompt');
    const note = document.getElementById('udNote');
    const userLink = document.getElementById('udUserLink');
    const editorLink = document.getElementById('udEditorLink');
    if (title) title.textContent = `검수 — ${row.title || '사용자 디자인'}`;
    if (img) {
      img.src = row.image_url || '';
      img.alt = row.title || '사용자 디자인';
    }
    if (author) author.textContent = [row.user_name, row.email ? `#${row.user_id}` : '', row.email].filter(Boolean).join(' · ');
    if (created) created.textContent = row.created_at || '-';
    if (prompt) prompt.textContent = row.prompt || '(프롬프트 없음)';
    if (note) note.value = row.review_note || '';
    if (userLink) userLink.href = row.user_url || '#';
    if (editorLink) editorLink.href = row.editor_url || '#';
    const reason = document.getElementById('udReason');
    if (reason) reason.value = '';
  }

  function selectRow(el) {
    rows.forEach((r) => r.classList.toggle('is-active', r === el));
    fillPanel(parseRow(el));
  }

  function selectedIds() {
    return Array.from(document.querySelectorAll('.js-ud-check:checked')).map((el) => Number(el.value)).filter(Boolean);
  }

  async function postJson(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    const data = await res.json();
    if (!res.ok || data.success === false) throw new Error(data.message || '요청 실패');
    return data;
  }

  rows.forEach((row) => {
    row.addEventListener('click', (ev) => {
      if (ev.target.closest('input[type=checkbox]')) return;
      selectRow(row);
    });
  });

  const first = document.querySelector('.ud-row');
  if (first) fillPanel(parseRow(first));

  const checkAll = document.getElementById('udCheckAll');
  if (checkAll) {
    checkAll.addEventListener('change', () => {
      document.querySelectorAll('.js-ud-check').forEach((el) => {
        el.checked = checkAll.checked;
      });
    });
  }

  const reason = document.getElementById('udReason');
  const note = document.getElementById('udNote');
  if (reason && note) {
    reason.addEventListener('change', () => {
      if (reason.value) note.value = reason.value;
    });
  }

  document.getElementById('udApprove')?.addEventListener('click', async () => {
    if (!current?.id) return;
    try {
      await postJson(urls.review, { id: current.id, action: 'approve', note: note?.value || '' });
      location.reload();
    } catch (err) {
      showAlert(err.message || '승인 오류', false);
    }
  });

  document.getElementById('udReject')?.addEventListener('click', async () => {
    if (!current?.id) return;
    const text = (note?.value || '').trim();
    if (!text) {
      showAlert('반려 사유를 입력하거나 템플릿을 선택해 주세요.', false);
      return;
    }
    if (!confirm('이 디자인을 반려할까요? 회원 목록에서 숨겨집니다.')) return;
    try {
      await postJson(urls.review, { id: current.id, action: 'reject', note: text });
      location.reload();
    } catch (err) {
      showAlert(err.message || '반려 오류', false);
    }
  });

  document.getElementById('udDelete')?.addEventListener('click', async () => {
    if (!current?.id) return;
    if (!confirm('이 사용자 디자인을 삭제할까요? 이미지 파일도 함께 지워집니다.')) return;
    try {
      await postJson(urls.delete, { id: current.id });
      location.reload();
    } catch (err) {
      showAlert(err.message || '삭제 오류', false);
    }
  });

  document.getElementById('udBatchApprove')?.addEventListener('click', async () => {
    const ids = selectedIds();
    if (!ids.length) {
      showAlert('승인할 디자인을 선택해 주세요.', false);
      return;
    }
    if (!confirm(`선택한 ${ids.length}건을 승인할까요?`)) return;
    try {
      await postJson(urls.approveBatch, { ids });
      location.reload();
    } catch (err) {
      showAlert(err.message || '일괄 승인 오류', false);
    }
  });
})();
