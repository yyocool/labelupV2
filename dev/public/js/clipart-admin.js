(() => {
  const cfg = window.LABELUP_CLIPART_ADMIN || {};
  const urls = cfg.urls || {};
  const categories = cfg.categories || [];
  const alertEl = document.getElementById('adminAlert');
  const modal = document.getElementById('clipModal');
  const form = document.getElementById('clipForm');
  const previewModal = document.getElementById('clipPreviewModal');
  let currentImagePath = '';

  function showAlert(msg, ok) {
    if (!alertEl) return;
    alertEl.textContent = msg;
    alertEl.className = `admin-alert ${ok ? 'is-ok' : 'is-err'}`;
    alertEl.style.display = 'block';
  }

  function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.hidden = false;
  }
  function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.hidden = true;
  }

  document.querySelectorAll('[data-close]').forEach((btn) => {
    btn.addEventListener('click', () => closeModal(btn.getAttribute('data-close')));
  });

  function categoryOptions(selected) {
    return ['<option value="">카테고리 선택</option>']
      .concat(
        categories.map(
          (c) =>
            `<option value="${c.id}"${String(c.id) === String(selected || '') ? ' selected' : ''}>${escapeHtml(
              c.name
            )}</option>`
        )
      )
      .join('');
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function buildForm(row) {
    row = row || {};
    currentImagePath = row.image_path || '';
    const preview = row.image_url
      ? `<img class="clip-form-preview" src="${escapeHtml(row.image_url)}" alt="">`
      : `<div class="clip-form-preview clip-form-preview--empty">이미지 없음</div>`;
    form.innerHTML = `
      <input type="hidden" name="id" value="${row.id || ''}">
      <div class="admin-form-grid">
        <label class="admin-field"><span>제목</span><input name="title" class="admin-input" required value="${escapeHtml(
          row.title || ''
        )}"></label>
        <label class="admin-field"><span>카테고리</span><select name="category_id" class="admin-input">${categoryOptions(
          row.category_id
        )}</select></label>
        <label class="admin-field admin-field--full"><span>해시태그</span>
          <input name="hashtags" class="admin-input" placeholder="#커피 #원두 #카페" value="${escapeHtml(
            row.hashtags || ''
          )}">
          <small>공백 또는 쉼표로 구분. #는 자동 정리됩니다.</small>
        </label>
        <label class="admin-field admin-field--full"><span>설명</span>
          <textarea name="description" class="admin-input" rows="2">${escapeHtml(row.description || '')}</textarea>
        </label>
        <label class="admin-field"><span>정렬</span><input type="number" name="sort_order" class="admin-input" value="${
          row.sort_order ?? 0
        }"></label>
        <label class="admin-field admin-check"><input type="checkbox" name="is_active" ${
          row.is_active === undefined || Number(row.is_active) ? 'checked' : ''
        }> 노출</label>
        <div class="admin-field admin-field--full">
          <span>이미지</span>
          <div class="clip-upload-row">
            <div id="clipPreviewWrap">${preview}</div>
            <div>
              <input type="file" id="clipFile" accept="image/*">
              <small>PNG/JPG/WEBP · 흰 배경 권장</small>
            </div>
          </div>
        </div>
      </div>`;

    document.getElementById('clipFile')?.addEventListener('change', async (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      const fd = new FormData();
      fd.append('images[]', file);
      try {
        const res = await fetch(urls.upload, { method: 'POST', credentials: 'same-origin', body: fd });
        const data = await res.json();
        if (!res.ok || data.success === false) throw new Error(data.message || '업로드 실패');
        currentImagePath = (data.data?.paths || [])[0] || '';
        const url = (data.data?.urls || [])[0] || '';
        const wrap = document.getElementById('clipPreviewWrap');
        if (wrap && url) wrap.innerHTML = `<img class="clip-form-preview" src="${escapeHtml(url)}" alt="">`;
      } catch (err) {
        showAlert(err.message || '업로드 오류', false);
      }
    });
  }

  document.querySelector('.js-clip-add')?.addEventListener('click', () => {
    document.getElementById('clipModalTitle').textContent = '클립아트 추가';
    buildForm({ is_active: 1, sort_order: 0 });
    openModal('clipModal');
  });

  document.querySelectorAll('.js-clip-edit').forEach((btn) => {
    btn.addEventListener('click', () => {
      const card = btn.closest('.clip-card');
      const row = JSON.parse(card.getAttribute('data-row') || '{}');
      document.getElementById('clipModalTitle').textContent = '클립아트 수정';
      buildForm(row);
      openModal('clipModal');
    });
  });

  document.querySelectorAll('.js-clip-preview').forEach((btn) => {
    btn.addEventListener('click', () => {
      const card = btn.closest('.clip-card');
      const row = JSON.parse(card.getAttribute('data-row') || '{}');
      document.getElementById('clipPreviewTitle').textContent = row.title || '미리보기';
      document.getElementById('clipPreviewImg').src = row.image_url || '';
      document.getElementById('clipPreviewMeta').textContent = [
        row.category_name,
        row.hashtags,
      ]
        .filter(Boolean)
        .join(' · ');
      openModal('clipPreviewModal');
    });
  });

  document.querySelectorAll('.js-clip-delete').forEach((btn) => {
    btn.addEventListener('click', async () => {
      if (!confirm('이 클립아트를 삭제할까요?')) return;
      try {
        const res = await fetch(urls.delete, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: Number(btn.dataset.id) }),
        });
        const data = await res.json();
        if (!res.ok || data.success === false) throw new Error(data.message || '삭제 실패');
        location.reload();
      } catch (err) {
        showAlert(err.message || '삭제 오류', false);
      }
    });
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    const payload = {
      id: Number(fd.get('id') || 0) || 0,
      title: String(fd.get('title') || '').trim(),
      category_id: Number(fd.get('category_id') || 0) || null,
      hashtags: String(fd.get('hashtags') || ''),
      description: String(fd.get('description') || ''),
      sort_order: Number(fd.get('sort_order') || 0),
      is_active: form.querySelector('[name="is_active"]')?.checked ? 1 : 0,
      image_path: currentImagePath,
      source: 'upload',
    };
    try {
      const res = await fetch(urls.save, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (!res.ok || data.success === false) throw new Error(data.message || '저장 실패');
      location.reload();
    } catch (err) {
      showAlert(err.message || '저장 오류', false);
    }
  });

  document.querySelector('.js-clip-seed')?.addEventListener('click', async () => {
    if (!confirm('시드 매니페스트의 클립아트를 DB에 반영할까요? (이미 있는 파일은 건너뜁니다)')) return;
    try {
      showAlert('시드 반영 중…', true);
      const res = await fetch(urls.seed, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: '{}',
      });
      const data = await res.json();
      if (!res.ok || data.success === false) throw new Error(data.message || '시드 실패');
      showAlert(`시드 완료: 추가 ${data.data?.inserted || 0} · 건너뜀 ${data.data?.skipped || 0}`, true);
      setTimeout(() => location.reload(), 800);
    } catch (err) {
      showAlert(err.message || '시드 오류', false);
    }
  });
})();
