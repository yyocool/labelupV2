const AdminAPI = {
  async request(path, options = {}) {
    const res = await fetch(path, {
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '요청 처리 중 오류가 발생했습니다.');
    }
    return data;
  },
  get(path) {
    return this.request(path);
  },
  post(path, body) {
    return this.request(path, { method: 'POST', body: JSON.stringify(body) });
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
  const el = document.getElementById('adminToast') || document.getElementById('adminAlert');
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

    const gradeId = row.querySelector('.js-grade')?.value;
    const statusEl = row.querySelector('.js-status');
    const payload = { user_id: userId, grade_id: Number(gradeId || 0) };
    if (statusEl && !statusEl.disabled) {
      payload.status = statusEl.value;
    }
    btn.disabled = true;

    try {
      await AdminAPI.post('/api/admin/users/update', payload);
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

const ADMIN_FS_KEY = 'labelup_admin_fullscreen';
const ADMIN_ALERT_MS = 25000;

function adminMenuHref(item) {
  if (!item || !item.href) return '#';
  if (/^https?:\/\//.test(item.href) || item.href.startsWith('/')) return item.href;
  return '/' + String(item.href).replace(/^\//, '');
}

function currentFavSlots() {
  return Array.isArray(window.LABELUP_ADMIN_FAVS) ? window.LABELUP_ADMIN_FAVS.slice() : [];
}

function renderAdminFavs(slots) {
  const root = document.getElementById('adminFavs');
  const editBtn = root?.querySelector('.js-fav-edit');
  if (!root || !editBtn) return;
  root.querySelectorAll('.admin-fav-slot').forEach((el) => el.remove());
  slots.forEach((slot) => {
    const n = Number(slot.slot);
    if (slot.href) {
      const a = document.createElement('a');
      a.className = 'admin-fav-slot is-filled';
      a.href = slot.href;
      a.title = slot.label || '';
      a.dataset.slot = String(n);
      a.dataset.key = slot.menu_key || '';
      a.innerHTML = '<span class="ic"></span><span class="lbl"></span><button type="button" class="admin-fav-clear js-fav-clear" aria-label="즐겨찾기 제거">×</button>';
      a.querySelector('.ic').textContent = slot.ic || '★';
      a.querySelector('.lbl').textContent = slot.label || '';
      a.querySelector('.js-fav-clear').dataset.slot = String(n);
      root.insertBefore(a, editBtn);
    } else {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'admin-fav-slot js-fav-pick';
      btn.dataset.slot = String(n);
      btn.title = '즐겨찾기 추가';
      btn.textContent = '+';
      root.insertBefore(btn, editBtn);
    }
  });
}

function openAdminFavModal(slotNo) {
  const modal = document.getElementById('adminFavModal');
  const body = document.getElementById('adminFavModalBody');
  const title = document.getElementById('adminFavModalTitle');
  if (!modal || !body) return;
  const slots = currentFavSlots();
  const current = slots.find((s) => Number(s.slot) === slotNo);
  const used = new Set(slots.filter((s) => s.menu_key).map((s) => s.menu_key));
  const menus = Array.isArray(window.LABELUP_ADMIN_MENUS) ? window.LABELUP_ADMIN_MENUS : [];
  const groups = {};
  menus.forEach((item) => {
    const g = item.group || '메뉴';
    if (!groups[g]) groups[g] = [];
    groups[g].push(item);
  });
  if (title) title.textContent = `즐겨찾기 ${slotNo}번 메뉴 선택`;
  body.innerHTML = '';
  Object.keys(groups).forEach((group) => {
    const wrap = document.createElement('div');
    wrap.className = 'admin-fav-pick-group';
    const heading = document.createElement('h3');
    heading.textContent = group;
    wrap.appendChild(heading);
    const list = document.createElement('div');
    list.className = 'admin-fav-pick-list';
    groups[group].forEach((item) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'admin-fav-pick-item';
      if (current?.menu_key === item.key) btn.classList.add('is-current');
      else if (used.has(item.key)) btn.classList.add('is-used');
      const ic = document.createElement('span');
      ic.textContent = item.ic || '★';
      const lb = document.createElement('span');
      lb.textContent = item.label;
      btn.append(ic, lb);
      btn.addEventListener('click', () => assignAdminFav(slotNo, item.key));
      list.appendChild(btn);
    });
    wrap.appendChild(list);
    body.appendChild(wrap);
  });
  if (current?.menu_key) {
    const clear = document.createElement('button');
    clear.type = 'button';
    clear.className = 'admin-btn admin-fav-pick-clear';
    clear.textContent = '이 칸 비우기';
    clear.addEventListener('click', () => assignAdminFav(slotNo, ''));
    body.appendChild(clear);
  }
  modal.hidden = false;
}

function closeAdminFavModal() {
  const modal = document.getElementById('adminFavModal');
  if (modal) modal.hidden = true;
}

async function persistAdminFavs(slots) {
  const payload = slots
    .filter((s) => s.menu_key)
    .map((s) => ({ slot: Number(s.slot), menu_key: s.menu_key }));
  const data = await AdminAPI.post('/api/admin/favorites', { slots: payload });
  window.LABELUP_ADMIN_FAVS = data.data?.slots || slots;
  renderAdminFavs(window.LABELUP_ADMIN_FAVS);
}

async function assignAdminFav(slotNo, menuKey) {
  try {
    const next = currentFavSlots().map((s) => ({ ...s }));
    next.forEach((s) => {
      if (menuKey && s.menu_key === menuKey && Number(s.slot) !== slotNo) {
        s.menu_key = null;
        s.label = null;
        s.href = null;
        s.ic = null;
      }
      if (Number(s.slot) === slotNo) {
        if (!menuKey) {
          s.menu_key = null;
          s.label = null;
          s.href = null;
          s.ic = null;
        } else {
          const menu = (window.LABELUP_ADMIN_MENUS || []).find((m) => m.key === menuKey);
          s.menu_key = menuKey;
          s.label = menu?.label || menuKey;
          s.href = adminMenuHref(menu);
          s.ic = menu?.ic || '★';
        }
      }
    });
    await persistAdminFavs(next);
    closeAdminFavModal();
    showAdminAlert('즐겨찾기를 저장했습니다.', 'success');
  } catch (err) {
    showAdminAlert(err.message, 'error');
  }
}

function initAdminFavorites() {
  const root = document.getElementById('adminFavs');
  if (!root) return;
  const editBtn = root.querySelector('.js-fav-edit');
  editBtn?.addEventListener('click', () => {
    root.classList.toggle('is-editing');
    editBtn.textContent = root.classList.contains('is-editing') ? '완료' : '편집';
  });
  root.addEventListener('click', (e) => {
    const clear = e.target.closest('.js-fav-clear');
    if (clear) {
      e.preventDefault();
      e.stopPropagation();
      assignAdminFav(Number(clear.dataset.slot), '');
      return;
    }
    const pick = e.target.closest('.js-fav-pick');
    if (pick) {
      openAdminFavModal(Number(pick.dataset.slot));
      return;
    }
    const filled = e.target.closest('.admin-fav-slot.is-filled');
    if (filled && root.classList.contains('is-editing')) {
      e.preventDefault();
      openAdminFavModal(Number(filled.dataset.slot));
    }
  });
  document.querySelectorAll('[data-close="adminFavModal"]').forEach((el) => {
    el.addEventListener('click', closeAdminFavModal);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAdminFavModal();
  });
}

function fullscreenElement() {
  return document.fullscreenElement || document.webkitFullscreenElement || null;
}

async function enterAdminFullscreen() {
  const el = document.documentElement;
  if (el.requestFullscreen) await el.requestFullscreen();
  else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
}

async function exitAdminFullscreen() {
  if (document.exitFullscreen) await document.exitFullscreen();
  else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
}

function syncAdminFullscreenUi() {
  const on = !!fullscreenElement();
  document.body.classList.toggle('is-admin-fullscreen', on);
  const btn = document.getElementById('adminFullscreenBtn');
  if (btn) {
    btn.classList.toggle('is-on', on);
    btn.title = on ? '전체화면 종료' : '전체화면';
    btn.setAttribute('aria-label', btn.title);
  }
}

function initAdminFullscreen() {
  const btn = document.getElementById('adminFullscreenBtn');
  if (!btn) return;
  btn.addEventListener('click', async () => {
    try {
      if (fullscreenElement()) {
        await exitAdminFullscreen();
        localStorage.setItem(ADMIN_FS_KEY, '0');
      } else {
        await enterAdminFullscreen();
        localStorage.setItem(ADMIN_FS_KEY, '1');
      }
    } catch (err) {
      showAdminAlert('브라우저에서 전체화면을 허용해 주세요.', 'error');
    }
  });
  document.addEventListener('fullscreenchange', () => {
    const on = !!fullscreenElement();
    localStorage.setItem(ADMIN_FS_KEY, on ? '1' : '0');
    syncAdminFullscreenUi();
  });
  document.addEventListener('webkitfullscreenchange', syncAdminFullscreenUi);
  syncAdminFullscreenUi();
}

function formatAdminMoney(n) {
  return Number(n || 0).toLocaleString('ko-KR') + '원';
}

function renderAdminAlerts(snap) {
  const badge = document.getElementById('adminBellBadge');
  const list = document.getElementById('adminBellList');
  if (!badge || !list) return;
  const total = Number(snap?.unread_total || 0);
  badge.hidden = total <= 0;
  badge.textContent = total > 99 ? '99+' : String(total);
  const items = [];
  (snap?.orders?.latest || []).forEach((row) => {
    items.push({
      href: window.LABELUP_ADMIN_ORDERS_URL || '/admin/shop/orders',
      title: `새 주문 ${row.order_no || ''}`.trim(),
      meta: `${row.customer_name || ''} · ${formatAdminMoney(row.total_amount)}`,
    });
  });
  (snap?.inquiries?.latest || []).forEach((row) => {
    items.push({
      href: window.LABELUP_ADMIN_INQUIRIES_URL || '/admin/ops/inquiries',
      title: `새 문의 ${row.subject || ''}`.trim(),
      meta: `${row.name || ''} · ${String(row.created_at || '').slice(0, 16)}`,
    });
  });
  if (!items.length) {
    list.innerHTML = '<p class="admin-bell-empty">새 주문·문의가 없습니다.</p>';
    return;
  }
  list.innerHTML = '';
  items.forEach((item) => {
    const a = document.createElement('a');
    a.className = 'admin-bell-item';
    a.href = item.href;
    const b = document.createElement('b');
    b.textContent = item.title;
    const span = document.createElement('span');
    span.textContent = item.meta;
    a.append(b, span);
    list.appendChild(a);
  });
}

async function ackAdminAlerts(snap) {
  return AdminAPI.post('/api/admin/alerts/ack', {
    last_seen_order_id: Number(snap?.orders?.latest_id || 0),
    last_seen_inquiry_id: Number(snap?.inquiries?.latest_id || 0),
  });
}

function notifyAdminDesktop(snap, prevTotal) {
  const total = Number(snap?.unread_total || 0);
  if (total <= prevTotal || typeof Notification === 'undefined' || Notification.permission !== 'granted') return;
  const orderN = Number(snap?.orders?.unread || 0);
  const inqN = Number(snap?.inquiries?.unread || 0);
  const parts = [];
  if (orderN) parts.push(`주문 ${orderN}건`);
  if (inqN) parts.push(`문의 ${inqN}건`);
  try {
    new Notification('LabelUp 관리자', { body: `새 ${parts.join(', ') || '알림'}이 있습니다.`, silent: true });
  } catch (e) { /* ignore */ }
}

function initAdminAlerts() {
  const wrap = document.getElementById('adminBellWrap');
  const btn = document.getElementById('adminBellBtn');
  const panel = document.getElementById('adminBellPanel');
  const ackBtn = document.getElementById('adminBellAck');
  if (!wrap || !btn || !panel) return;
  let lastSnap = null;
  let prevTotal = 0;

  const refresh = async (silent) => {
    try {
      const data = await AdminAPI.get('/api/admin/alerts');
      const snap = data.data || {};
      renderAdminAlerts(snap);
      if (!silent) notifyAdminDesktop(snap, prevTotal);
      prevTotal = Number(snap.unread_total || 0);
      lastSnap = snap;
    } catch (e) { /* 세션 만료 등은 무시 */ }
  };

  btn.addEventListener('click', async () => {
    const willOpen = panel.hidden;
    panel.hidden = !willOpen;
    const profile = document.getElementById('adminProfileMenu');
    if (willOpen && profile) {
      profile.hidden = true;
      document.getElementById('adminProfileWrap')?.classList.remove('is-open');
      document.getElementById('adminProfileBtn')?.setAttribute('aria-expanded', 'false');
    }
    if (!willOpen) return;
    if (typeof Notification !== 'undefined' && Notification.permission === 'default') {
      Notification.requestPermission().catch(() => {});
    }
    await refresh(true);
    if (lastSnap && Number(lastSnap.unread_total || 0) > 0) {
      try {
        await ackAdminAlerts(lastSnap);
        const badge = document.getElementById('adminBellBadge');
        if (badge) {
          badge.hidden = true;
          badge.textContent = '0';
        }
        prevTotal = 0;
      } catch (e) { /* ignore */ }
    }
  });
  ackBtn?.addEventListener('click', async () => {
    if (!lastSnap) return;
    try {
      await ackAdminAlerts(lastSnap);
      lastSnap = {
        ...lastSnap,
        unread_total: 0,
        orders: { ...lastSnap.orders, unread: 0, latest: [] },
        inquiries: { ...lastSnap.inquiries, unread: 0, latest: [] },
      };
      renderAdminAlerts(lastSnap);
      prevTotal = 0;
    } catch (err) {
      showAdminAlert(err.message, 'error');
    }
  });
  document.addEventListener('click', (e) => {
    if (!wrap.contains(e.target)) panel.hidden = true;
  });
  refresh(true);
  window.setInterval(() => refresh(false), ADMIN_ALERT_MS);
}

function initAdminProfileMenu() {
  const wrap = document.getElementById('adminProfileWrap');
  const btn = document.getElementById('adminProfileBtn');
  const menu = document.getElementById('adminProfileMenu');
  const pwBtn = document.getElementById('adminPasswordBtn');
  const pwModal = document.getElementById('adminPasswordModal');
  const pwForm = document.getElementById('adminPasswordForm');
  if (!wrap || !btn || !menu) return;

  const setOpen = (open) => {
    menu.hidden = !open;
    wrap.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      const bell = document.getElementById('adminBellPanel');
      if (bell) bell.hidden = true;
    }
  };

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    setOpen(menu.hidden);
  });
  document.addEventListener('click', (e) => {
    if (!wrap.contains(e.target)) setOpen(false);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setOpen(false);
  });

  const openPwModal = () => {
    setOpen(false);
    if (pwModal) pwModal.hidden = false;
    pwForm?.querySelector('[name=current_password]')?.focus();
  };
  const closePwModal = () => {
    if (pwModal) pwModal.hidden = true;
    pwForm?.reset();
  };
  pwBtn?.addEventListener('click', openPwModal);
  pwModal?.querySelectorAll('[data-close="adminPasswordModal"]').forEach((el) => {
    el.addEventListener('click', closePwModal);
  });
  pwForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(pwForm);
    const next = String(fd.get('new_password') || '');
    const confirm = String(fd.get('new_password_confirm') || '');
    if (next !== confirm) {
      showAdminAlert('새 비밀번호가 일치하지 않습니다.', 'error');
      return;
    }
    const submit = pwForm.querySelector('[type=submit]');
    if (submit) submit.disabled = true;
    try {
      await AdminAPI.post('/api/admin/password', {
        current_password: fd.get('current_password'),
        new_password: next,
        new_password_confirm: confirm,
      });
      closePwModal();
      showAdminAlert('비밀번호가 변경되었습니다.');
    } catch (err) {
      showAdminAlert(err.message, 'error');
    } finally {
      if (submit) submit.disabled = false;
    }
  });
}

initAdminFavorites();
initAdminFullscreen();
initAdminAlerts();
initAdminProfileMenu();

function initAdminStaffPage() {
  const form = document.getElementById('adminStaffForm');
  const modal = document.getElementById('adminStaffModal');
  if (!form || !modal) return;

  const title = document.getElementById('adminStaffTitle');
  const lookupBtn = document.getElementById('adminStaffLookup');
  const modeWrap = document.getElementById('adminStaffModeWrap');
  const pwHint = document.getElementById('adminStaffPwHint');
  const superBox = document.getElementById('adminStaffSuper');
  const perms = document.getElementById('adminStaffPerms');
  const emailInput = form.querySelector('[name=email]');
  const idInput = form.querySelector('[name=id]');
  const pwInput = form.querySelector('[name=password]');

  const close = () => { modal.hidden = true; };
  const boxes = () => [...form.querySelectorAll('input[name="menu_keys[]"]')];
  const setPermsDisabled = (off) => {
    if (perms) perms.style.opacity = off ? '0.45' : '1';
    boxes().forEach((el) => { el.disabled = off; });
  };
  const fillPerms = (keys) => {
    const set = new Set(keys || []);
    boxes().forEach((el) => { el.checked = set.has(el.value); });
  };

  const openCreate = () => {
    form.reset();
    idInput.value = '';
    emailInput.readOnly = false;
    if (modeWrap) modeWrap.hidden = true;
    if (lookupBtn) lookupBtn.hidden = true;
    if (pwHint) pwHint.textContent = '신규 등록 시 필수입니다.';
    if (title) title.textContent = '관리자 등록';
    fillPerms([]);
    setPermsDisabled(false);
    modal.hidden = false;
  };

  const openEdit = (row) => {
    form.reset();
    idInput.value = String(row.id || '');
    form.querySelector('[name=name]').value = row.name || '';
    emailInput.value = row.email || '';
    emailInput.readOnly = true;
    form.querySelector('[name=status]').value = row.status || 'active';
    if (superBox) superBox.checked = !!Number(row.is_super_admin);
    if (modeWrap) modeWrap.hidden = true;
    if (lookupBtn) lookupBtn.hidden = true;
    if (pwHint) pwHint.textContent = '변경할 때만 입력하세요.';
    if (title) title.textContent = '관리자 권한 설정';
    fillPerms(row.menu_keys || []);
    setPermsDisabled(superBox ? superBox.checked : false);
    modal.hidden = false;
  };

  document.getElementById('adminStaffCreate')?.addEventListener('click', openCreate);
  document.querySelectorAll('[data-close="adminStaffModal"]').forEach((el) => {
    el.addEventListener('click', close);
  });
  document.querySelectorAll('.js-staff-edit').forEach((btn) => {
    btn.addEventListener('click', () => {
      try { openEdit(JSON.parse(btn.dataset.admin || '{}')); } catch (e) { showAdminAlert('데이터를 읽지 못했습니다.', 'error'); }
    });
  });
  document.querySelectorAll('.js-staff-revoke').forEach((btn) => {
    btn.addEventListener('click', async () => {
      if (!confirm('이 계정의 관리자 권한을 해제할까요?')) return;
      try {
        await AdminAPI.post('/api/admin/admins/revoke', { id: Number(btn.dataset.id) });
        location.reload();
      } catch (err) {
        showAdminAlert(err.message, 'error');
      }
    });
  });
  form.querySelectorAll('input[name=mode]').forEach((radio) => {
    radio.addEventListener('change', () => {
      const existing = form.querySelector('input[name=mode]:checked')?.value === 'existing';
      if (lookupBtn) lookupBtn.hidden = !existing;
      if (pwHint) pwHint.textContent = existing ? '기존 회원은 비밀번호를 비워두면 유지됩니다.' : '신규 등록 시 필수입니다.';
      emailInput.readOnly = false;
    });
  });
  lookupBtn?.addEventListener('click', async () => {
    try {
      const email = encodeURIComponent(emailInput.value || '');
      const data = await AdminAPI.get('/api/admin/admins/lookup?email=' + email);
      const row = data.data || {};
      if (row.is_admin) {
        showAdminAlert('이미 관리자로 등록된 계정입니다.', 'error');
        return;
      }
      form.querySelector('[name=name]').value = row.name || '';
      emailInput.value = row.email || '';
      showAdminAlert('회원을 찾았습니다. 권한을 지정한 뒤 저장하세요.', 'success');
    } catch (err) {
      showAdminAlert(err.message, 'error');
    }
  });
  superBox?.addEventListener('change', () => setPermsDisabled(superBox.checked));
  document.getElementById('adminStaffPermAll')?.addEventListener('click', () => {
    const allOn = boxes().every((el) => el.checked);
    boxes().forEach((el) => { if (!el.disabled) el.checked = !allOn; });
  });
  document.querySelectorAll('.js-staff-group').forEach((btn) => {
    btn.addEventListener('click', () => {
      const group = btn.dataset.group;
      const items = boxes().filter((el) => el.dataset.group === group && !el.disabled);
      const allOn = items.every((el) => el.checked);
      items.forEach((el) => { el.checked = !allOn; });
    });
  });
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type=submit]');
    const fd = new FormData(form);
    btn.disabled = true;
    try {
      await AdminAPI.post('/api/admin/admins/save', {
        id: Number(fd.get('id') || 0),
        name: fd.get('name'),
        email: fd.get('email'),
        password: fd.get('password') || '',
        status: fd.get('status'),
        is_super_admin: superBox ? (superBox.checked ? 1 : 0) : 0,
        menu_keys: boxes().filter((el) => el.checked).map((el) => el.value),
      });
      location.reload();
    } catch (err) {
      showAdminAlert(err.message, 'error');
    } finally {
      btn.disabled = false;
    }
  });
}

initAdminStaffPage();
