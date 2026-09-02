const AdminAuthAPI = {
  async post(path, body) {
    const res = await fetch(path, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '로그인에 실패했습니다.');
    }
    return data;
  },
};

function showAdminLoginAlert(message, type = 'error') {
  const el = document.getElementById('adminLoginAlert');
  if (!el) return;
  el.textContent = message;
  el.className = `admin-login-alert show ${type}`;
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('adminLoginForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    try {
      const fd = new FormData(form);
      await AdminAuthAPI.post('/api/admin/login', {
        email: fd.get('email'),
        password: fd.get('password'),
        remember: !!fd.get('remember'),
      });
      location.href = form.dataset.redirect || '/admin';
    } catch (err) {
      showAdminLoginAlert(err.message);
    } finally {
      btn.disabled = false;
    }
  });
});
