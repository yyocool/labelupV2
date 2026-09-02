const API = {
  async request(path, options = {}) {
    const res = await fetch(path, {
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      credentials: 'same-origin',
      ...options,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '요청 처리 중 오류가 발생했습니다.');
    }
    return data;
  },
  post(path, body) {
    return this.request(path, { method: 'POST', body: JSON.stringify(body) });
  },
  get(path) {
    return this.request(path);
  },
};

function showAlert(el, message, type = 'error') {
  if (!el) return;
  el.textContent = message;
  const base = el.classList.contains('login-alert') ? 'login-alert'
    : el.classList.contains('account-alert') ? 'account-alert'
    : 'auth-alert';
  el.className = `${base} show ${type}`;
}

function bindPasswordToggles() {
  document.querySelectorAll('.js-pwd-toggle, #loginPasswordToggle').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.target || 'loginPassword';
      const input = document.getElementById(id);
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-label', show ? '비밀번호 숨기기' : '비밀번호 표시');
    });
  });
}

function analyzePasswordStrength(password) {
  const checks = {
    length: password.length >= 8 && password.length <= 20,
    letter: /[A-Za-z]/.test(password),
    number: /[0-9]/.test(password),
    special: /[^A-Za-z0-9]/.test(password),
  };
  const typeCount = [checks.letter, checks.number, checks.special].filter(Boolean).length;
  const typesOk = typeCount >= 2;
  const valid = checks.length && typesOk;

  let score = 0;
  if (password.length > 0) score += 1;
  if (checks.length) score += 1;
  if (checks.letter) score += 1;
  if (checks.number) score += 1;
  if (checks.special) score += 1;
  if (password.length >= 12 && typesOk) score += 1;

  let level = 'empty';
  let label = '-';
  if (!password) {
    level = 'empty';
    label = '-';
  } else if (!valid) {
    level = 'weak';
    label = '약함';
  } else if (score <= 4) {
    level = 'fair';
    label = '보통';
  } else if (score <= 5) {
    level = 'good';
    label = '좋음';
  } else {
    level = 'strong';
    label = '강함';
  }

  return { checks, typesOk, valid, level, label, score };
}

function validatePasswordRule(password) {
  return analyzePasswordStrength(password).valid;
}

function setPasswordRuleState(el, passed) {
  if (!el) return;
  el.classList.toggle('is-pass', passed);
  el.classList.toggle('is-fail', !passed);
  const icon = el.querySelector('.ic');
  if (icon) icon.textContent = passed ? '✓' : '○';
}

function updatePasswordStrengthUI(password) {
  const wrap = document.getElementById('passwordStrength');
  const fill = document.getElementById('passwordStrengthFill');
  const label = document.getElementById('passwordStrengthLabel');
  const bar = wrap?.querySelector('.register-pwd-strength-bar');
  if (!wrap || !fill || !label || !bar) return;

  const result = analyzePasswordStrength(password);
  wrap.dataset.level = result.level;
  label.textContent = result.label;

  const widths = { empty: '0%', weak: '25%', fair: '50%', good: '75%', strong: '100%' };
  fill.style.width = widths[result.level] || '0%';
  bar.setAttribute('aria-valuenow', String(Math.max(0, result.score - 1)));

  const showRules = password.length > 0;
  wrap.classList.toggle('is-active', showRules);

  if (!showRules) {
    ['pwdRuleLength', 'pwdRuleLetter', 'pwdRuleNumber', 'pwdRuleSpecial', 'pwdRuleTypes'].forEach((id) => {
      const el = document.getElementById(id);
      if (el) {
        el.classList.remove('is-pass', 'is-fail');
        const icon = el.querySelector('.ic');
        if (icon) icon.textContent = '○';
      }
    });
    return;
  }

  setPasswordRuleState(document.getElementById('pwdRuleLength'), result.checks.length);
  setPasswordRuleState(document.getElementById('pwdRuleLetter'), result.checks.letter);
  setPasswordRuleState(document.getElementById('pwdRuleNumber'), result.checks.number);
  setPasswordRuleState(document.getElementById('pwdRuleSpecial'), result.checks.special);
  setPasswordRuleState(document.getElementById('pwdRuleTypes'), result.typesOk);
}

function bindRegisterExtras(form) {
  const pwdInput = form.querySelector('#registerPassword');
  if (pwdInput) {
    const onInput = () => updatePasswordStrengthUI(pwdInput.value);
    pwdInput.addEventListener('input', onInput);
    pwdInput.addEventListener('focus', onInput);
  }
}

const legalDocCache = {};

function openLegalModal(docKey) {
  const modal = document.getElementById('legalModal');
  const titleEl = document.getElementById('legalModalTitle');
  const bodyEl = document.getElementById('legalModalBody');
  if (!modal || !titleEl || !bodyEl) return;

  modal.hidden = false;
  modal.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
  titleEl.textContent = '불러오는 중...';
  bodyEl.innerHTML = '<p style="color:var(--muted)">내용을 불러오는 중입니다.</p>';

  const render = (doc) => {
    titleEl.textContent = doc.title || '약관';
    bodyEl.innerHTML = doc.content || '';
  };

  if (legalDocCache[docKey]) {
    render(legalDocCache[docKey]);
    return;
  }

  API.get(`/api/legal/${docKey}`)
    .then((res) => {
      legalDocCache[docKey] = res.data;
      render(res.data);
    })
    .catch((err) => {
      titleEl.textContent = '오류';
      bodyEl.textContent = err.message || '약관을 불러오지 못했습니다.';
    });
}

function closeLegalModal() {
  const modal = document.getElementById('legalModal');
  if (!modal) return;
  modal.hidden = true;
  modal.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

function bindLegalModal() {
  document.querySelectorAll('.register-agree-view, .register-agree-link').forEach((btn) => {
    btn.addEventListener('click', () => {
      const key = btn.dataset.doc;
      if (key) openLegalModal(key);
    });
  });

  document.querySelectorAll('[data-close="legal"]').forEach((el) => {
    el.addEventListener('click', closeLegalModal);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeLegalModal();
      closeRecoveryModal();
    }
  });
}

function openRecoveryModal(tab = 'find-id') {
  const modal = document.getElementById('recoveryModal');
  if (!modal) return;
  modal.hidden = false;
  modal.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
  switchRecoveryTab(tab);
  resetRecoveryForms();
}

function closeRecoveryModal() {
  const modal = document.getElementById('recoveryModal');
  if (!modal || modal.hidden) return;
  modal.hidden = true;
  modal.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

function switchRecoveryTab(tab) {
  document.querySelectorAll('.recovery-tab').forEach((btn) => {
    const active = btn.dataset.tab === tab;
    btn.classList.toggle('is-active', active);
    btn.setAttribute('aria-selected', active ? 'true' : 'false');
  });
  document.querySelectorAll('.recovery-panel').forEach((panel) => {
    const active = panel.dataset.panel === tab;
    panel.classList.toggle('is-active', active);
    panel.hidden = !active;
  });
}

function resetRecoveryForms() {
  const alert = document.getElementById('recoveryAlert');
  if (alert) {
    alert.textContent = '';
    alert.className = 'login-alert';
  }
  ['findIdForm', 'findPasswordForm'].forEach((id) => {
    const form = document.getElementById(id);
    if (form) form.reset();
  });
  ['findIdResult', 'findPasswordResult'].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.hidden = true;
  });
  const link = document.getElementById('findPasswordLink');
  if (link) link.hidden = true;
}

function bindRecoveryModal() {
  document.querySelectorAll('[data-recovery]').forEach((btn) => {
    btn.addEventListener('click', () => openRecoveryModal(btn.dataset.recovery || 'find-id'));
  });

  document.querySelectorAll('[data-close="recovery"]').forEach((el) => {
    el.addEventListener('click', closeRecoveryModal);
  });

  document.querySelectorAll('.recovery-tab').forEach((btn) => {
    btn.addEventListener('click', () => switchRecoveryTab(btn.dataset.tab || 'find-id'));
  });

  const findIdForm = document.getElementById('findIdForm');
  if (findIdForm) {
    const alert = document.getElementById('recoveryAlert');
    const result = document.getElementById('findIdResult');
    const emailEl = document.getElementById('findIdEmail');
    findIdForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = findIdForm.querySelector('button[type=submit]');
      btn.disabled = true;
      if (result) result.hidden = true;
      try {
        const fd = new FormData(findIdForm);
        const res = await API.post('/api/auth/find-email', {
          name: fd.get('name'),
          phone: fd.get('phone'),
        });
        if (emailEl) emailEl.textContent = res.data.masked_email || '';
        if (result) result.hidden = false;
        showAlert(alert, res.message, 'success');
      } catch (err) {
        showAlert(alert, err.message);
      } finally {
        btn.disabled = false;
      }
    });
  }

  const findPasswordForm = document.getElementById('findPasswordForm');
  if (findPasswordForm) {
    const alert = document.getElementById('recoveryAlert');
    const result = document.getElementById('findPasswordResult');
    const msgEl = document.getElementById('findPasswordMsg');
    const linkEl = document.getElementById('findPasswordLink');
    findPasswordForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = findPasswordForm.querySelector('button[type=submit]');
      btn.disabled = true;
      if (result) result.hidden = true;
      if (linkEl) linkEl.hidden = true;
      try {
        const fd = new FormData(findPasswordForm);
        const res = await API.post('/api/auth/password-reset/request', {
          email: fd.get('email'),
          name: fd.get('name'),
        });
        if (msgEl) msgEl.textContent = res.message || '';
        if (result) result.hidden = false;
        if (linkEl && res.data?.reset_url) {
          linkEl.href = res.data.reset_url;
          linkEl.hidden = false;
        }
        showAlert(alert, res.message, 'success');
      } catch (err) {
        showAlert(alert, err.message);
      } finally {
        btn.disabled = false;
      }
    });
  }
}

function bindEmailCheck(input, hint) {
  if (!input || !hint) return;
  let timer = null;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    const email = input.value.trim();
    hint.textContent = '';
    hint.className = 'hint';
    if (!email || !email.includes('@')) return;
    timer = setTimeout(async () => {
      try {
        const res = await API.get(`/api/auth/check-email?email=${encodeURIComponent(email)}`);
        hint.textContent = res.message;
        hint.className = `register-hint ${res.data.available ? 'ok' : 'err'}`;
      } catch (_) {}
    }, 400);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  bindPasswordToggles();
  bindLegalModal();
  bindRecoveryModal();

  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    const alert = document.getElementById('authAlert');
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = loginForm.querySelector('button[type=submit]');
      btn.disabled = true;
      try {
        const fd = new FormData(loginForm);
        await API.post('/api/auth/login', {
          email: fd.get('email'),
          password: fd.get('password'),
          remember: !!fd.get('remember'),
        });
        location.href = loginForm.dataset.redirect || '/';
      } catch (err) {
        showAlert(alert, err.message);
      } finally {
        btn.disabled = false;
      }
    });
  }

  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    const alert = document.getElementById('authAlert');
    bindRegisterExtras(registerForm);
    bindEmailCheck(
      registerForm.querySelector('input[name=email]'),
      document.getElementById('emailHint')
    );
    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = registerForm.querySelector('button[type=submit]');
      btn.disabled = true;
      try {
        const fd = new FormData(registerForm);
        if (!fd.get('terms') || !fd.get('privacy')) {
          throw new Error('필수 약관에 동의해주세요.');
        }
        if (!validatePasswordRule(String(fd.get('password') || ''))) {
          throw new Error('비밀번호 조건을 확인해주세요.');
        }
        if (fd.get('password') !== fd.get('password_confirm')) {
          throw new Error('비밀번호 확인이 일치하지 않습니다.');
        }
        await API.post('/api/auth/register', {
          email: fd.get('email'),
          password: fd.get('password'),
          name: fd.get('name'),
        });
        location.href = '/account';
      } catch (err) {
        showAlert(alert, err.message);
      } finally {
        btn.disabled = false;
      }
    });
  }

  const profileForm = document.getElementById('profileForm');
  if (profileForm) {
    const alert = document.getElementById('profileAlert');
    profileForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(profileForm);
      try {
        await API.post('/api/auth/profile', Object.fromEntries(fd.entries()));
        showAlert(alert, '회원정보가 저장되었습니다.', 'success');
      } catch (err) {
        showAlert(alert, err.message);
      }
    });
  }

  const passwordForm = document.getElementById('passwordForm');
  if (passwordForm) {
    const alert = document.getElementById('passwordAlert');
    passwordForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(passwordForm);
      try {
        await API.post('/api/auth/password', {
          current_password: fd.get('current_password'),
          new_password: fd.get('new_password'),
        });
        passwordForm.reset();
        showAlert(alert, '비밀번호가 변경되었습니다.', 'success');
      } catch (err) {
        showAlert(alert, err.message);
      }
    });
  }

  const withdrawForm = document.getElementById('withdrawForm');
  if (withdrawForm) {
    const alert = document.getElementById('withdrawAlert');
    withdrawForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!confirm('정말 탈퇴하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) return;
      const fd = new FormData(withdrawForm);
      try {
        await API.post('/api/auth/withdraw', { password: fd.get('password') });
        location.href = '/';
      } catch (err) {
        showAlert(alert, err.message);
      }
    });
  }

  const resetPasswordForm = document.getElementById('resetPasswordForm');
  if (resetPasswordForm) {
    const alert = document.getElementById('authAlert');
    resetPasswordForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = resetPasswordForm.querySelector('button[type=submit]');
      btn.disabled = true;
      try {
        const fd = new FormData(resetPasswordForm);
        const password = String(fd.get('password') || '');
        if (!validatePasswordRule(password)) {
          throw new Error('비밀번호 조건을 확인해주세요.');
        }
        if (password !== fd.get('password_confirm')) {
          throw new Error('비밀번호 확인이 일치하지 않습니다.');
        }
        await API.post('/api/auth/password-reset/confirm', {
          token: resetPasswordForm.dataset.token,
          password,
        });
        location.href = resetPasswordForm.dataset.redirect || '/login';
      } catch (err) {
        showAlert(alert, err.message);
      } finally {
        btn.disabled = false;
      }
    });
  }
});
