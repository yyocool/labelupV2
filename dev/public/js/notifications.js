(() => {
  const wraps = Array.from(document.querySelectorAll('[data-notif-bell]'));
  if (!wraps.length) return;

  const api = {
    get: async (url) => {
      const res = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      const json = await res.json().catch(() => ({}));
      if (!res.ok || json.success === false) throw new Error(json.message || '요청 실패');
      return json.data || {};
    },
    post: async (url, body) => {
      const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(body || {}),
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok || json.success === false) throw new Error(json.message || '요청 실패');
      return json.data || {};
    },
  };

  const esc = (s) => String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

  const setBadge = (count) => {
    wraps.forEach((wrap) => {
      const badge = wrap.querySelector('[data-notif-badge]');
      if (!badge) return;
      const n = Number(count || 0);
      if (n <= 0) {
        badge.hidden = true;
        badge.textContent = '0';
      } else {
        badge.hidden = false;
        badge.textContent = n > 99 ? '99+' : String(n);
      }
    });
  };

  const renderList = (items) => {
    wraps.forEach((wrap) => {
      const list = wrap.querySelector('[data-notif-list]');
      if (!list) return;
      if (!items || !items.length) {
        list.innerHTML = '<p class="notif-panel__empty">새 알림이 없습니다.</p>';
        return;
      }
      list.innerHTML = items.map((it) => {
        const href = it.link_url ? esc(it.link_url) : '#';
        const unread = it.is_read ? '' : ' is-unread';
        return (
          '<a class="notif-item' + unread + '" href="' + href + '" data-notif-item data-id="' + esc(it.id) + '">' +
            '<em class="notif-item__type">' + esc(it.type_label || '알림') + '</em>' +
            '<strong>' + esc(it.title) + '</strong>' +
            (it.body ? '<span>' + esc(it.body) + '</span>' : '') +
            '<time>' + esc(it.created_label || '') + '</time>' +
          '</a>'
        );
      }).join('');
    });
  };

  let loading = false;
  const refresh = async () => {
    if (loading) return;
    loading = true;
    try {
      const data = await api.get('/api/notifications?limit=30');
      setBadge(data.unread || 0);
      renderList(data.items || []);
    } catch (e) {
      wraps.forEach((wrap) => {
        const list = wrap.querySelector('[data-notif-list]');
        if (list) list.innerHTML = '<p class="notif-panel__empty">알림을 불러오지 못했습니다.</p>';
      });
    } finally {
      loading = false;
    }
  };

  const closeAll = () => {
    wraps.forEach((wrap) => {
      const panel = wrap.querySelector('[data-notif-panel]');
      const btn = wrap.querySelector('[data-notif-toggle]');
      if (panel) panel.hidden = true;
      if (btn) btn.setAttribute('aria-expanded', 'false');
    });
  };

  wraps.forEach((wrap) => {
    const btn = wrap.querySelector('[data-notif-toggle]');
    const panel = wrap.querySelector('[data-notif-panel]');
    const readAll = wrap.querySelector('[data-notif-read-all]');
    if (!btn || !panel) return;

    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const open = panel.hidden;
      closeAll();
      if (!open) return;
      panel.hidden = false;
      btn.setAttribute('aria-expanded', 'true');
      await refresh();
    });

    panel.addEventListener('click', (e) => e.stopPropagation());

    readAll?.addEventListener('click', async () => {
      try {
        const data = await api.post('/api/notifications/read', {});
        setBadge(data.unread || 0);
        await refresh();
      } catch (_) { /* ignore */ }
    });

    panel.addEventListener('click', async (e) => {
      const item = e.target.closest('[data-notif-item]');
      if (!item) return;
      const id = Number(item.getAttribute('data-id') || 0);
      if (!id) return;
      try {
        const data = await api.post('/api/notifications/read', { id });
        setBadge(data.unread || 0);
        item.classList.remove('is-unread');
      } catch (_) { /* ignore */ }
    });
  });

  document.addEventListener('click', closeAll);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAll();
  });

  refresh();
  setInterval(() => {
    api.get('/api/notifications/unread')
      .then((d) => setBadge(d.unread || 0))
      .catch(() => {});
  }, 60000);
})();
