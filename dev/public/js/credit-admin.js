const CreditAPI = {
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

const CREDIT_ENDPOINTS = {
  reward: { save: '/api/admin/credit/reward/save', delete: '/api/admin/credit/reward/delete' },
  'purchase-product': { save: '/api/admin/credit/purchase-product/save', delete: '/api/admin/credit/purchase-product/delete' },
  'generate-codes': { save: '/api/admin/credit/codes/generate' },
  'cs-log': { save: '/api/admin/credit/cs/save' },
};

function creditField(label, name, value = '', type = 'text', opts = {}) {
  const req = opts.required ? ' required' : '';
  if (type === 'checkbox') {
    const checked = value ? ' checked' : '';
    return `<label class="admin-field admin-field--check"><input type="checkbox" name="${name}" value="1"${checked}> ${label}</label>`;
  }
  if (type === 'textarea') {
    return `<div class="admin-field"><label>${label}</label><textarea name="${name}" rows="${opts.rows || 3}"${req}>${value ?? ''}</textarea></div>`;
  }
  if (type === 'select') {
    const options = (opts.options || []).map((o) => `<option value="${o.v}"${String(value) === String(o.v) ? ' selected' : ''}>${o.t}</option>`).join('');
    return `<div class="admin-field"><label>${label}</label><select name="${name}" class="admin-select"${req}>${options}</select></div>`;
  }
  const extra = opts.min !== undefined ? ` min="${opts.min}"` : '';
  return `<div class="admin-field"><label>${label}</label><input type="${type}" name="${name}" value="${value ?? ''}"${req}${extra}></div>`;
}

function buildCreditForm(entity, row = {}, userId = 0) {
  const id = row.id || 0;
  let html = `<input type="hidden" name="id" value="${id}">`;
  if (entity === 'reward') {
    html += creditField('보상 코드', 'code', row.code, 'text', { required: true });
    html += creditField('보상명', 'name', row.name, 'text', { required: true });
    html += creditField('설명', 'description', row.description, 'textarea');
    html += creditField('크레딧', 'credit_amount', row.credit_amount ?? 0, 'number', { required: true, min: 0 });
    html += creditField('트리거', 'trigger_type', row.trigger_type || 'event', 'select', {
      options: [
        { v: 'signup', t: '회원가입' }, { v: 'daily_login', t: '일일 접속' },
        { v: 'design_complete', t: '디자인 완료' }, { v: 'referral', t: '친구 추천' },
        { v: 'purchase_code', t: '구매 코드' }, { v: 'event', t: '이벤트' }, { v: 'manual', t: '수동' },
      ],
    });
    html += creditField('일일 한도(회)', 'daily_limit', row.daily_limit ?? '', 'number', { min: 0 });
    html += creditField('회원당 총 한도', 'max_total_per_user', row.max_total_per_user ?? '', 'number', { min: 0 });
    html += creditField('정렬', 'sort_order', row.sort_order ?? 0, 'number');
    html += creditField('사용', 'is_active', row.is_active, 'checkbox');
  } else if (entity === 'purchase-product') {
    html += creditField('제품명', 'name', row.name, 'text', { required: true });
    html += creditField('SKU', 'sku', row.sku, 'text', { required: true });
    html += creditField('지급 크레딧', 'credit_amount', row.credit_amount ?? 0, 'number', { required: true, min: 0 });
    html += creditField('설명', 'description', row.description, 'textarea');
    html += creditField('사용', 'is_active', row.is_active, 'checkbox');
  } else if (entity === 'generate-codes') {
    const products = window.CREDIT_PRODUCTS || [];
    html += creditField('제품', 'product_id', '', 'select', {
      required: true,
      options: [{ v: '', t: '선택' }, ...products.map((p) => ({ v: p.id, t: p.name }))],
    });
    html += creditField('생성 수량', 'count', 10, 'number', { required: true, min: 1 });
    html += creditField('코드 접두사', 'prefix', 'LU', 'text');
  } else if (entity === 'cs-log') {
    html += `<input type="hidden" name="user_id" value="${userId || row.user_id || 0}">`;
    html += creditField('분류', 'category', row.category || 'inquiry', 'select', {
      options: [
        { v: 'inquiry', t: '문의' }, { v: 'complaint', t: '불만' }, { v: 'refund', t: '환불' },
        { v: 'account', t: '계정' }, { v: 'technical', t: '기술' }, { v: 'other', t: '기타' },
      ],
    });
    html += creditField('제목', 'subject', row.subject, 'text', { required: true });
    html += creditField('내용', 'content', row.content, 'textarea', { rows: 5 });
    html += creditField('상태', 'status', row.status || 'open', 'select', {
      options: [
        { v: 'open', t: '접수' }, { v: 'in_progress', t: '처리중' }, { v: 'resolved', t: '완료' },
      ],
    });
  }
  return html;
}

const creditModal = document.getElementById('creditModal');
const creditForm = document.getElementById('creditForm');
let activeEntity = '';

function openCreditModal(entity, row = {}, userId = 0) {
  if (!creditModal || !creditForm) return;
  activeEntity = entity;
  const titles = {
    reward: row.id ? '보상 규칙 수정' : '보상 규칙 추가',
    'purchase-product': row.id ? '제품 수정' : '제품 추가',
    'generate-codes': '고유번호 일괄 생성',
    'cs-log': row.id ? 'CS 이력 수정' : 'CS 이력 등록',
  };
  document.getElementById('creditModalTitle').textContent = titles[entity] || '저장';
  creditForm.innerHTML = buildCreditForm(entity, row, userId);
  creditModal.hidden = false;
}

function closeCreditModal() {
  if (creditModal) creditModal.hidden = true;
  activeEntity = '';
}

document.querySelectorAll('[data-close="creditModal"]').forEach((el) => {
  el.addEventListener('click', closeCreditModal);
});

document.querySelectorAll('.js-credit-add').forEach((btn) => {
  btn.addEventListener('click', () => {
    openCreditModal(btn.dataset.entity, {}, Number(btn.dataset.userId || 0));
  });
});

document.querySelectorAll('.js-credit-edit').forEach((btn) => {
  btn.addEventListener('click', () => {
    let row = {};
    try { row = JSON.parse(btn.dataset.row || '{}'); } catch (_) {}
    openCreditModal(btn.dataset.entity, row, Number(btn.dataset.userId || row.user_id || 0));
  });
});

document.querySelectorAll('.js-credit-delete').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const entity = btn.dataset.entity;
    const id = Number(btn.dataset.id);
    if (!id || !window.confirm('삭제하시겠습니까?')) return;
    const ep = CREDIT_ENDPOINTS[entity];
    if (!ep?.delete) return;
    try {
      await CreditAPI.post(ep.delete, { id });
      showAdminAlert('삭제되었습니다.', 'success');
      window.location.reload();
    } catch (err) {
      showAdminAlert(err.message, 'error');
    }
  });
});

creditForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const ep = CREDIT_ENDPOINTS[activeEntity];
  if (!ep?.save) return;
  const fd = new FormData(creditForm);
  const body = Object.fromEntries(fd.entries());
  if (body.is_active !== undefined) body.is_active = 1;
  else if (activeEntity === 'reward' || activeEntity === 'purchase-product') body.is_active = 0;
  try {
    await CreditAPI.post(ep.save, body);
    showAdminAlert('저장되었습니다.', 'success');
    window.location.reload();
  } catch (err) {
    showAdminAlert(err.message, 'error');
  }
});

document.getElementById('creditAdjustForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const body = Object.fromEntries(fd.entries());
  body.amount = Number(body.amount);
  body.user_id = Number(body.user_id);
  try {
    const res = await CreditAPI.post('/api/admin/credit/adjust', body);
    showAdminAlert(`크레딧이 조정되었습니다. (잔액 ${Number(res.data?.balance || 0).toLocaleString()} C)`, 'success');
    window.location.reload();
  } catch (err) {
    showAdminAlert(err.message, 'error');
  }
});
