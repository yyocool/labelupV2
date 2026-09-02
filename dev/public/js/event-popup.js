(() => {
  const items = Array.isArray(window.LABELUP_EVENT_POPUPS) ? window.LABELUP_EVENT_POPUPS : [];
  const root = document.getElementById('eventPopupRoot');
  if (!root || !items.length) return;

  const STORAGE_KEY = 'labelup_event_popup_hide';
  const SESSION_KEY = 'labelup_event_popup_session_hide';

  function readHideMap() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') || {};
    } catch (_) {
      return {};
    }
  }

  function writeHideMap(map) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(map));
    } catch (_) {}
  }

  function readSessionHide() {
    try {
      return JSON.parse(sessionStorage.getItem(SESSION_KEY) || '{}') || {};
    } catch (_) {
      return {};
    }
  }

  function writeSessionHide(map) {
    try {
      sessionStorage.setItem(SESSION_KEY, JSON.stringify(map));
    } catch (_) {}
  }

  function isHidden(id) {
    const map = readHideMap();
    const until = Number(map[String(id)] || 0);
    if (until > Date.now()) return true;
    const session = readSessionHide();
    return !!session[String(id)];
  }

  function hideForDays(id, days) {
    const d = Number(days);
    if (!d || d <= 0) {
      const session = readSessionHide();
      session[String(id)] = 1;
      writeSessionHide(session);
      return;
    }
    const map = readHideMap();
    map[String(id)] = Date.now() + d * 24 * 60 * 60 * 1000;
    writeHideMap(map);
  }

  const queue = items.filter((p) => p && p.id && p.image && !isHidden(p.id));
  if (!queue.length) return;

  let index = 0;

  function escapeHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function closeCurrent(hideDays) {
    const cur = queue[index];
    if (cur) hideForDays(cur.id, hideDays ?? cur.hide_days ?? 1);
    index += 1;
    if (index < queue.length) {
      render();
      return;
    }
    root.hidden = true;
    root.innerHTML = '';
    document.body.classList.remove('event-popup-open');
  }

  function render() {
    const p = queue[index];
    if (!p) {
      root.hidden = true;
      return;
    }
    const media = p.link
      ? `<a class="event-popup-media" href="${escapeHtml(p.link)}"><img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.title)}"></a>`
      : `<div class="event-popup-media"><img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.title)}"></div>`;

    root.innerHTML = `
      <div class="event-popup-backdrop" data-ep-close></div>
      <div class="event-popup-dialog" role="dialog" aria-modal="true" aria-label="${escapeHtml(p.title || '이벤트 팝업')}">
        <button type="button" class="event-popup-x" data-ep-close aria-label="닫기">×</button>
        ${media}
        <div class="event-popup-body">
          ${p.title ? `<strong class="event-popup-title">${escapeHtml(p.title)}</strong>` : ''}
          ${p.content ? `<div class="event-popup-text">${p.content}</div>` : ''}
          <div class="event-popup-actions">
            <button type="button" class="event-popup-btn event-popup-btn--ghost" data-ep-hide>오늘 하루 보지 않기</button>
            <button type="button" class="event-popup-btn event-popup-btn--solid" data-ep-close>닫기</button>
          </div>
        </div>
      </div>`;
    root.hidden = false;
    document.body.classList.add('event-popup-open');

    root.querySelectorAll('[data-ep-close]').forEach((el) => {
      el.addEventListener('click', () => closeCurrent(0));
    });
    root.querySelector('[data-ep-hide]')?.addEventListener('click', () => {
      closeCurrent(Math.max(1, Number(p.hide_days) || 1));
    });
  }

  // slight delay so page paints first
  window.setTimeout(render, 350);
})();
