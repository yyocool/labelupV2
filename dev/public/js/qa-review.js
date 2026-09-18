(function () {
  const cfg = window.QA_REVIEW || {};
  const sheet = document.getElementById('qaSheet');
  const statusEl = document.getElementById('qaSaveStatus');
  const searchEl = document.getElementById('qaSearch');
  const modal = document.getElementById('qaRequestModal');
  const editorEl = document.getElementById('qaRequestEditor');
  const metaEl = document.getElementById('qaRequestMeta');
  const saveReqBtn = document.getElementById('qaRequestSave');
  const timers = new Map();
  let filter = 'all';
  let activeRow = null;
  let editorReady = false;

  const ISSUE = { error: 1, fix: 1, enhance: 1 };

  function setStatus(text, cls) {
    if (!statusEl) return;
    statusEl.textContent = text;
    statusEl.className = 'qa-status' + (cls ? ' ' + cls : '');
  }

  function paintSelect(sel) {
    if (!(sel instanceof HTMLSelectElement)) return;
    const v = sel.value || '';
    sel.dataset.status = v;
    sel.classList.remove('is-done', 'is-error', 'is-fix', 'is-enhance', 'is-empty');
    if (!v) sel.classList.add('is-empty');
    else sel.classList.add('is-' + v);
  }

  function readRequestHtml(row) {
    const node = row.querySelector('.qa-req-json');
    if (!node) return '';
    try {
      return JSON.parse(node.textContent || '""');
    } catch (_) {
      return '';
    }
  }

  function writeRequestHtml(row, html) {
    const node = row.querySelector('.qa-req-json');
    if (node) node.textContent = JSON.stringify(html || '');
    const has = hasRequest(html);
    row.dataset.request = has ? '1' : '0';
    const btn = row.querySelector('[data-open-request]');
    if (btn) {
      btn.classList.toggle('has-content', has);
      btn.textContent = has ? '보기/수정' : '작성';
    }
  }

  function hasRequest(html) {
    const raw = String(html || '');
    if (/<img[\s>]/i.test(raw)) return true;
    const tmp = document.createElement('div');
    tmp.innerHTML = raw;
    return (tmp.textContent || '').trim() !== '';
  }

  function refreshSummary() {
    const rows = Array.from(sheet.querySelectorAll('tbody tr'));
    let devDone = 0;
    let clientDone = 0;
    let bothDone = 0;
    let issues = 0;
    let requests = 0;
    rows.forEach((row) => {
      const d = row.querySelector('select[data-field="dev_status"]')?.value || '';
      const c = row.querySelector('select[data-field="client_status"]')?.value || '';
      if (d === 'done') devDone += 1;
      if (c === 'done') clientDone += 1;
      if (d === 'done' && c === 'done') bothDone += 1;
      const hasIssue = !!(ISSUE[d] || ISSUE[c]);
      if (hasIssue) issues += 1;
      if (row.dataset.request === '1') requests += 1;
      row.dataset.pending = d === 'done' && c === 'done' ? '0' : '1';
      row.dataset.issue = hasIssue ? '1' : '0';
      row.classList.toggle('is-done', d === 'done' && c === 'done');
      row.classList.toggle('is-issue', hasIssue);
      row.classList.toggle('is-partial', (d !== '' || c !== '') && !(d === 'done' && c === 'done') && !hasIssue);
    });
    const set = (id, n) => {
      const el = document.getElementById(id);
      if (el) el.textContent = String(n);
    };
    set('qaDevDone', devDone);
    set('qaClientDone', clientDone);
    set('qaBothDone', bothDone);
    set('qaIssues', issues);
    set('qaRequests', requests);
  }

  function applyFilter() {
    const q = (searchEl?.value || '').trim().toLowerCase();
    sheet.querySelectorAll('tbody tr').forEach((row) => {
      const area = row.dataset.area || '';
      const pending = row.dataset.pending === '1';
      const issue = row.dataset.issue === '1';
      const request = row.dataset.request === '1';
      let visible = true;
      if (filter === 'user') visible = area === 'user';
      if (filter === 'admin') visible = area === 'admin';
      if (filter === 'pending') visible = pending;
      if (filter === 'issues') visible = issue;
      if (filter === 'requests') visible = request;
      if (visible && q) {
        const hay = row.textContent.toLowerCase();
        visible = hay.includes(q);
      }
      row.hidden = !visible;
    });
  }

  async function saveRow(row) {
    const key = row.dataset.key;
    if (!key || !cfg.saveUrl) return;
    const payload = {
      mode: 'status',
      key,
      dev_status: row.querySelector('select[data-field="dev_status"]')?.value || '',
      client_status: row.querySelector('select[data-field="client_status"]')?.value || '',
      note: row.querySelector('input[data-field="note"]')?.value || '',
    };
    setStatus('저장 중…', 'is-saving');
    try {
      const res = await fetch(cfg.saveUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) {
        throw new Error(data.message || '저장 실패');
      }
      setStatus('저장됨 ' + new Date().toLocaleTimeString(), 'is-ok');
      refreshSummary();
      applyFilter();
    } catch (err) {
      setStatus(err.message || '저장 실패', 'is-err');
    }
  }

  function queueSave(row) {
    const key = row.dataset.key;
    if (!key) return;
    if (timers.has(key)) clearTimeout(timers.get(key));
    timers.set(key, setTimeout(() => {
      timers.delete(key);
      saveRow(row);
    }, 280));
  }

  async function uploadImageFile(file) {
    if (!cfg.uploadUrl) throw new Error('업로드 URL이 없습니다.');
    const fd = new FormData();
    fd.append('image', file, file.name || ('paste.' + (file.type.split('/')[1] || 'png')));
    const res = await fetch(cfg.uploadUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: fd,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '이미지 업로드 실패');
    }
    const url = data.data && data.data.url;
    if (!url) throw new Error('업로드 URL을 받지 못했습니다.');
    return url;
  }

  function insertImageToEditor(url) {
    if (!window.jQuery || !editorReady) return;
    window.jQuery(editorEl).summernote('insertImage', url, function ($image) {
      $image.css({ maxWidth: '100%', height: 'auto' });
      $image.attr('alt', '요청 캡처');
    });
  }

  async function handleImageFiles(files) {
    const list = Array.from(files || []).filter((f) => f && f.type && f.type.indexOf('image/') === 0);
    if (!list.length) return;
    setStatus('이미지 업로드 중…', 'is-saving');
    try {
      for (const file of list) {
        const url = await uploadImageFile(file);
        insertImageToEditor(url);
      }
      setStatus('이미지 업로드 완료', 'is-ok');
    } catch (err) {
      setStatus(err.message || '이미지 업로드 실패', 'is-err');
      alert(err.message || '이미지 업로드 실패');
    }
  }

  function destroyEditor() {
    if (!window.jQuery || !editorEl) return;
    const $el = window.jQuery(editorEl);
    if ($el.next('.note-editor').length) {
      $el.summernote('destroy');
    }
    editorReady = false;
  }

  function initEditor(html) {
    if (!window.jQuery || !window.jQuery.fn.summernote || !editorEl) {
      alert('에디터를 불러오지 못했습니다.');
      return;
    }
    destroyEditor();
    const $el = window.jQuery(editorEl);
    $el.val('');
    $el.summernote({
      lang: 'ko-KR',
      height: 360,
      placeholder: '요청사항을 자세히 적어 주세요. 캡처는 Ctrl+V로 붙여넣기 할 수 있습니다.',
      dialogsInBody: true,
      toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'underline', 'clear']],
        ['fontsize', ['fontsize']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['picture', 'link', 'hr']],
        ['view', ['fullscreen', 'codeview']],
      ],
      callbacks: {
        onImageUpload: function (files) {
          handleImageFiles(files);
        },
        onPaste: function (e) {
          const oe = e.originalEvent || e;
          const clip = oe.clipboardData || window.clipboardData;
          if (!clip || !clip.items) return;
          const files = [];
          for (let i = 0; i < clip.items.length; i += 1) {
            const item = clip.items[i];
            if (item.kind === 'file' && item.type && item.type.indexOf('image/') === 0) {
              const file = item.getAsFile();
              if (file) files.push(file);
            }
          }
          if (!files.length) return;
          e.preventDefault();
          handleImageFiles(files);
        },
      },
    });
    editorReady = true;
    $el.summernote('code', html || '');
  }

  function openRequestModal(row) {
    activeRow = row;
    if (metaEl) {
      metaEl.textContent = (row.dataset.label || row.dataset.key || '') + ' · ' + (row.dataset.key || '');
    }
    if (modal) modal.hidden = false;
    document.body.classList.add('qa-modal-open');
    initEditor(readRequestHtml(row));
  }

  function closeRequestModal() {
    destroyEditor();
    activeRow = null;
    if (modal) modal.hidden = true;
    document.body.classList.remove('qa-modal-open');
  }

  async function saveRequest() {
    if (!activeRow || !cfg.saveUrl || !window.jQuery || !editorReady) return;
    const html = window.jQuery(editorEl).summernote('code') || '';
    saveReqBtn.disabled = true;
    setStatus('요청사항 저장 중…', 'is-saving');
    try {
      const res = await fetch(cfg.saveUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          mode: 'request',
          key: activeRow.dataset.key,
          request_html: html,
        }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) {
        throw new Error(data.message || '저장 실패');
      }
      writeRequestHtml(activeRow, html);
      refreshSummary();
      applyFilter();
      setStatus('요청사항 저장됨', 'is-ok');
      closeRequestModal();
    } catch (err) {
      setStatus(err.message || '저장 실패', 'is-err');
      alert(err.message || '저장 실패');
    } finally {
      saveReqBtn.disabled = false;
    }
  }

  document.querySelectorAll('.qa-filters button').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.qa-filters button').forEach((b) => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      filter = btn.dataset.filter || 'all';
      applyFilter();
    });
  });

  searchEl?.addEventListener('input', applyFilter);
  sheet?.querySelectorAll('select[data-field]').forEach(paintSelect);

  sheet?.addEventListener('change', (e) => {
    const t = e.target;
    if (!(t instanceof HTMLSelectElement) && !(t instanceof HTMLInputElement)) return;
    if (!t.matches('[data-field]')) return;
    const row = t.closest('tr');
    if (!row) return;
    if (t instanceof HTMLSelectElement) paintSelect(t);
    refreshSummary();
    queueSave(row);
  });

  sheet?.addEventListener('input', (e) => {
    const t = e.target;
    if (!(t instanceof HTMLInputElement)) return;
    if (t.dataset.field !== 'note') return;
    const row = t.closest('tr');
    if (!row) return;
    queueSave(row);
  });

  sheet?.addEventListener('click', (e) => {
    const btn = e.target && e.target.closest && e.target.closest('[data-open-request]');
    if (!btn) return;
    const row = btn.closest('tr');
    if (!row) return;
    openRequestModal(row);
  });

  modal?.addEventListener('click', (e) => {
    const t = e.target;
    if (t && t.closest && t.closest('[data-close-request]')) {
      closeRequestModal();
    }
  });

  saveReqBtn?.addEventListener('click', saveRequest);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal && !modal.hidden) {
      closeRequestModal();
    }
  });
})();
