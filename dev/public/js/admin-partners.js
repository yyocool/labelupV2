(function () {
  const cfg = window.LABELUP_PARTNERS || {};
  const form = document.getElementById('partnerForm');
  const modal = document.getElementById('partnerModal');
  const issued = document.getElementById('partnerIssuedModal');
  if (!form || !modal || !issued) return;

  async function post(path, body) {
    const res = await fetch(path, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '요청 처리 중 오류가 발생했습니다.');
    }
    return data;
  }

  let reloadOnIssuedClose = false;

  function openModal(el) {
    el.hidden = false;
  }

  function closeModal(el) {
    el.hidden = true;
  }

  function field(name) {
    return form.elements.namedItem(name);
  }

  function fillForm(row) {
    field('id').value = row && row.id ? String(row.id) : '';
    field('company_name').value = row ? (row.company_name || '') : '';
    field('login_id').value = row ? (row.login_id || '') : '';
    field('password').value = '';
    field('contact_name').value = row ? (row.contact_name || '') : '';
    field('phone').value = row ? (row.phone || '') : '';
    field('email').value = row ? (row.email || '') : '';
    field('memo').value = row ? (row.memo || '') : '';
    field('status').value = row && row.status === 'inactive' ? 'inactive' : 'active';
    field('login_id').placeholder = row && row.id ? '' : '비워 두면 자동 발급';
    field('password').placeholder = row && row.id ? '비워 두면 기존 비밀번호 유지' : '비워 두면 자동 발급';
    const hint = document.getElementById('partnerPwHint');
    if (hint) {
      hint.textContent = row && row.id
        ? '변경할 때만 입력합니다. 입력한 비밀번호는 저장 후 한 번만 표시됩니다.'
        : '8자 이상, 영문과 숫자를 포함합니다. 저장 후 한 번만 표시됩니다.';
    }
    document.getElementById('partnerModalTitle').textContent = row && row.id ? '협력사 수정' : '협력사 등록';
  }

  function showIssued(partner, password) {
    document.getElementById('partnerIssuedCompany').value = partner.company_name || '';
    document.getElementById('partnerIssuedId').value = partner.login_id || '';
    document.getElementById('partnerIssuedPw').value = password || '';
    reloadOnIssuedClose = true;
    openModal(issued);
  }

  document.getElementById('partnerCreate')?.addEventListener('click', () => {
    fillForm(null);
    openModal(modal);
  });

  document.querySelectorAll('.js-partner-edit').forEach((btn) => {
    btn.addEventListener('click', () => {
      let row = {};
      try {
        row = JSON.parse(btn.getAttribute('data-partner') || '{}');
      } catch (e) {
        row = {};
      }
      fillForm(row);
      openModal(modal);
    });
  });

  document.querySelectorAll('[data-close]').forEach((el) => {
    el.addEventListener('click', () => {
      const id = el.getAttribute('data-close');
      const target = id ? document.getElementById(id) : null;
      if (!target) return;
      closeModal(target);
      if (target === issued && reloadOnIssuedClose) {
        window.location.reload();
      }
    });
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const body = {
      id: Number(field('id').value || 0),
      company_name: field('company_name').value,
      login_id: field('login_id').value,
      password: field('password').value,
      contact_name: field('contact_name').value,
      phone: field('phone').value,
      email: field('email').value,
      memo: field('memo').value,
      status: field('status').value,
    };
    try {
      const res = await post(cfg.saveUrl, body);
      const data = res.data || {};
      closeModal(modal);
      if (data.issued_password) {
        showIssued(data.partner || {}, data.issued_password);
      } else {
        window.location.reload();
      }
    } catch (err) {
      window.alert(err.message || '저장에 실패했습니다.');
    }
  });

  document.querySelectorAll('.js-partner-reset').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const name = btn.getAttribute('data-name') || '이 협력사';
      if (!window.confirm(name + '의 비밀번호를 새로 발급할까요? 기존 비밀번호는 바로 사용할 수 없게 됩니다.')) return;
      try {
        const res = await post(cfg.resetUrl, { id: Number(btn.getAttribute('data-id') || 0) });
        const data = res.data || {};
        showIssued(data.partner || {}, data.issued_password || '');
      } catch (err) {
        window.alert(err.message || '비밀번호 재발급에 실패했습니다.');
      }
    });
  });

  document.querySelectorAll('.js-partner-delete').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const name = btn.getAttribute('data-name') || '이 협력사';
      if (!window.confirm(name + ' 계정을 삭제할까요? 회원 데이터는 그대로 남고, 협력사 로그인만 제거됩니다.')) return;
      try {
        await post(cfg.deleteUrl, { id: Number(btn.getAttribute('data-id') || 0) });
        window.location.reload();
      } catch (err) {
        window.alert(err.message || '삭제에 실패했습니다.');
      }
    });
  });

  document.getElementById('partnerCopyIssued')?.addEventListener('click', async () => {
    const text = '아이디: ' + document.getElementById('partnerIssuedId').value
      + '\n비밀번호: ' + document.getElementById('partnerIssuedPw').value;
    try {
      await navigator.clipboard.writeText(text);
      window.alert('아이디와 비밀번호를 복사했습니다.');
    } catch (e) {
      window.prompt('아래 내용을 복사하세요.', text);
    }
  });
})();
