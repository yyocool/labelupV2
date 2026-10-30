(() => {
  async function post(path, body) {
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
  }

  const modal = document.getElementById('reviewModal');
  const form = document.getElementById('reviewForm');
  const contentEl = form?.querySelector('.js-review-content');
  const productSelect = form?.querySelector('[name=product_id]');
  const productSearch = form?.querySelector('.js-review-product-search');
  const hasSummernote = () => window.jQuery && jQuery.fn.summernote;
  const editorOn = () => hasSummernote() && jQuery(contentEl).next('.note-editor').length > 0;

  function pad(n) { return String(n).padStart(2, '0'); }
  function toLocalInput(value) {
    const d = value ? new Date(String(value).replace(' ', 'T')) : new Date();
    if (Number.isNaN(d.getTime())) return '';
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  async function uploadImages(files) {
    const list = Array.from(files || []).filter((f) => f.type && f.type.indexOf('image/') === 0);
    for (const file of list) {
      const fd = new FormData();
      fd.append('image', file, file.name || 'photo.jpg');
      try {
        const res = await fetch('/api/admin/reviews/upload', { method: 'POST', credentials: 'same-origin', body: fd });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) throw new Error(data.message || '이미지 업로드 실패');
        jQuery(contentEl).summernote('insertImage', data.data.url);
      } catch (err) {
        alert(err.message);
        return;
      }
    }
  }

  function initEditor(html) {
    if (!hasSummernote()) {
      contentEl.value = html;
      return;
    }
    if (!editorOn()) {
      jQuery(contentEl).summernote({
        lang: 'ko-KR',
        height: 260,
        placeholder: '리뷰 내용을 입력하세요',
        toolbar: [
          ['font', ['bold', 'underline', 'strikethrough', 'clear']],
          ['color', ['forecolor']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['insert', ['picture', 'link']],
          ['view', ['codeview']],
        ],
        dialogsInBody: true,
        callbacks: { onImageUpload: uploadImages },
      });
    }
    jQuery(contentEl).summernote('code', html || '');
  }

  function getContent() {
    if (editorOn()) {
      const code = jQuery(contentEl).summernote('code') || '';
      return code === '<p><br></p>' ? '' : code;
    }
    return contentEl.value || '';
  }

  function filterProducts(q) {
    const term = q.trim().toLowerCase();
    let first = null;
    Array.from(productSelect.options).forEach((opt) => {
      if (!opt.value) return;
      const show = !term || opt.textContent.toLowerCase().includes(term);
      opt.hidden = !show;
      if (show && !first) first = opt;
    });
    const current = productSelect.selectedOptions[0];
    if (term && first && (!productSelect.value || current?.hidden)) productSelect.value = first.value;
  }
  productSearch?.addEventListener('input', () => filterProducts(productSearch.value));

  function openModal(row = {}) {
    document.getElementById('reviewModalTitle').textContent = row.id ? '리뷰 수정' : '리뷰 등록';
    form.elements.id.value = row.id || 0;
    productSearch.value = '';
    filterProducts('');
    productSelect.value = row.product_id ? String(row.product_id) : '';
    form.elements.author_name.value = row.author_name || '';
    form.elements.rating.value = String(row.rating || 5);
    form.elements.status.value = row.status || 'approved';
    form.elements.created_at.value = toLocalInput(row.created_at);
    modal.hidden = false;
    window.setTimeout(() => initEditor(row.content || ''), 30);
  }

  function closeModal() {
    modal.hidden = true;
  }

  document.querySelectorAll('[data-close="reviewModal"]').forEach((el) => el.addEventListener('click', closeModal));
  document.querySelector('.js-review-add')?.addEventListener('click', () => openModal({}));
  document.querySelectorAll('.js-review-edit').forEach((btn) => {
    btn.addEventListener('click', () => {
      try { openModal(JSON.parse(btn.dataset.row || '{}')); } catch (_) { openModal({}); }
    });
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = {
      id: Number(form.elements.id.value || 0),
      product_id: Number(productSelect.value || 0),
      author_name: form.elements.author_name.value,
      rating: Number(form.elements.rating.value || 5),
      status: form.elements.status.value,
      created_at: form.elements.created_at.value,
      content: getContent(),
    };
    try {
      await post('/api/admin/reviews/save', body);
      window.location.reload();
    } catch (err) {
      alert(err.message);
    }
  });

  document.querySelectorAll('.js-review-status').forEach((btn) => {
    btn.addEventListener('click', async () => {
      try {
        await post('/api/admin/reviews/status', { ids: [Number(btn.dataset.id)], status: btn.dataset.status });
        window.location.reload();
      } catch (err) {
        alert(err.message);
      }
    });
  });

  document.querySelectorAll('.js-review-delete').forEach((btn) => {
    btn.addEventListener('click', async () => {
      if (!window.confirm('이 리뷰를 삭제할까요? 삭제하면 되돌릴 수 없습니다.')) return;
      try {
        await post('/api/admin/reviews/delete', { ids: [Number(btn.dataset.id)] });
        window.location.reload();
      } catch (err) {
        alert(err.message);
      }
    });
  });

  const checks = () => Array.from(document.querySelectorAll('.js-review-check'));
  const selectedIds = () => checks().filter((c) => c.checked).map((c) => Number(c.value));
  document.querySelector('.js-review-check-all')?.addEventListener('change', (e) => {
    checks().forEach((c) => { c.checked = e.target.checked; });
  });

  document.querySelectorAll('.js-review-bulk').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const ids = selectedIds();
      if (!ids.length) { alert('리뷰를 선택해 주세요.'); return; }
      try {
        await post('/api/admin/reviews/status', { ids, status: btn.dataset.status });
        window.location.reload();
      } catch (err) {
        alert(err.message);
      }
    });
  });

  document.querySelector('.js-review-bulk-delete')?.addEventListener('click', async () => {
    const ids = selectedIds();
    if (!ids.length) { alert('리뷰를 선택해 주세요.'); return; }
    if (!window.confirm(`선택한 ${ids.length}건을 삭제할까요?`)) return;
    try {
      await post('/api/admin/reviews/delete', { ids });
      window.location.reload();
    } catch (err) {
      alert(err.message);
    }
  });

  document.getElementById('reviewSettingsForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const approval = e.target.querySelector('[name=approval]:checked')?.value || 'auto';
    try {
      const res = await post('/api/admin/reviews/settings', { approval });
      alert(res.message || '저장했습니다.');
    } catch (err) {
      alert(err.message);
    }
  });
})();
