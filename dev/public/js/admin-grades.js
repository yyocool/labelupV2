function initMemberGradePage() {
  const modal = document.getElementById('memberGradeModal');
  const form = document.getElementById('memberGradeForm');
  if (!modal || !form) return;

  const title = document.getElementById('memberGradeTitle');
  const close = () => { modal.hidden = true; };

  const open = (row = {}) => {
    form.reset();
    form.querySelector('[name=id]').value = row.id || '';
    form.querySelector('[name=name]').value = row.name || '';
    form.querySelector('[name=slug]').value = row.slug || '';
    form.querySelector('[name=description]').value = row.description || '';
    form.querySelector('[name=color]').value = row.color || '#7B2D3E';
    form.querySelector('[name=sort_order]').value = row.sort_order ?? 0;
    form.querySelector('[name=is_default]').checked = !!Number(row.is_default);
    form.querySelector('[name=is_active]').checked = row.id ? !!Number(row.is_active) : true;
    if (title) title.textContent = row.id ? '회원등급 수정' : '회원등급 추가';
    modal.hidden = false;
    form.querySelector('[name=name]')?.focus();
  };

  document.getElementById('memberGradeAdd')?.addEventListener('click', () => open());
  document.querySelectorAll('[data-close="memberGradeModal"]').forEach((el) => {
    el.addEventListener('click', close);
  });
  document.querySelectorAll('.js-grade-edit').forEach((btn) => {
    btn.addEventListener('click', () => {
      try { open(JSON.parse(btn.dataset.row || '{}')); } catch (e) { showAdminAlert('데이터를 읽지 못했습니다.', 'error'); }
    });
  });
  document.querySelectorAll('.js-grade-delete').forEach((btn) => {
    btn.addEventListener('click', async () => {
      if (!window.confirm('이 회원등급을 삭제할까요? 해당 회원은 기본 등급으로 이동합니다.')) return;
      try {
        await AdminAPI.post('/api/admin/member-grades/delete', { id: Number(btn.dataset.id) });
        location.reload();
      } catch (err) {
        showAdminAlert(err.message, 'error');
      }
    });
  });
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    const btn = document.querySelector('button[form="memberGradeForm"]');
    if (btn) btn.disabled = true;
    try {
      await AdminAPI.post('/api/admin/member-grades/save', {
        id: Number(fd.get('id') || 0),
        name: fd.get('name'),
        slug: fd.get('slug'),
        description: fd.get('description'),
        color: fd.get('color'),
        sort_order: Number(fd.get('sort_order') || 0),
        is_default: form.querySelector('[name=is_default]').checked ? 1 : 0,
        is_active: form.querySelector('[name=is_active]').checked ? 1 : 0,
      });
      location.reload();
    } catch (err) {
      showAdminAlert(err.message, 'error');
    } finally {
      if (btn) btn.disabled = false;
    }
  });
}

initMemberGradePage();
