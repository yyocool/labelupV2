(function () {
  if (window.__labelupCreditHistory) return;
  window.__labelupCreditHistory = true;

  const PER_PAGE = 20;
  let modal = null;
  let page = 1;
  let loading = false;

  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fmt = (n) => Number(n || 0).toLocaleString('ko-KR');

  function ensureModal() {
    if (modal) return modal;
    modal = document.createElement('div');
    modal.className = 'credit-history-modal';
    modal.hidden = true;
    modal.innerHTML =
      '<div class="credit-history-modal__backdrop" data-credit-close></div>' +
      '<div class="credit-history-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="creditHistoryTitle">' +
        '<div class="credit-history-modal__head">' +
          '<h2 id="creditHistoryTitle">내 잉크 이력</h2>' +
          '<button type="button" class="credit-history-modal__close" data-credit-close aria-label="닫기">×</button>' +
        '</div>' +
        '<div class="credit-history-balance">' +
          '<span>보유 잉크</span>' +
          '<strong class="js-credit-balance">-</strong>' +
          '<a class="credit-history-charge" href="/shop/products?category=ink-charge">잉크 충전</a>' +
        '</div>' +
        '<p class="credit-history-debt js-credit-debt" hidden></p>' +
        '<div class="credit-history-body">' +
          '<ul class="credit-history-list js-credit-list"></ul>' +
          '<p class="credit-history-empty js-credit-empty" hidden>잉크 사용·적립 내역이 없습니다.</p>' +
          '<button type="button" class="credit-history-more js-credit-more" hidden>더보기</button>' +
        '</div>' +
        '<div class="credit-history-modal__foot">' +
          '<span class="js-credit-total"></span>' +
          '<a href="/account#credits">마이페이지에서 보기</a>' +
        '</div>' +
      '</div>';
    document.body.appendChild(modal);
    modal.addEventListener('click', (e) => {
      if (e.target.closest('[data-credit-close]')) closeModal();
    });
    modal.querySelector('.js-credit-more').addEventListener('click', () => load(page + 1));
    return modal;
  }

  function row(tx) {
    const amt = Number(tx.amount || 0);
    const meta = [String(tx.created_at || '').slice(0, 16), tx.tx_type_label, tx.source_label].filter(Boolean).map(esc).join(' · ');
    return '<li class="credit-history-row">' +
      '<div><strong>' + esc(tx.description || tx.tx_type_label || '잉크') + '</strong><span>' + meta + '</span></div>' +
      '<div class="credit-history-amt">' +
        '<b class="' + (amt >= 0 ? 'is-plus' : 'is-minus') + '">' + (amt >= 0 ? '+' : '') + fmt(amt) + ' C</b>' +
        '<small>잔액 ' + fmt(tx.balance_after) + ' C</small>' +
      '</div>' +
    '</li>';
  }

  async function load(nextPage) {
    if (loading) return;
    loading = true;
    const m = ensureModal();
    const list = m.querySelector('.js-credit-list');
    const more = m.querySelector('.js-credit-more');
    const empty = m.querySelector('.js-credit-empty');
    more.disabled = true;
    if (nextPage === 1) {
      list.innerHTML = '<li class="credit-history-loading">불러오는 중…</li>';
      empty.hidden = true;
      more.hidden = true;
    }
    try {
      const res = await fetch('/api/credit/me?page=' + nextPage + '&per_page=' + PER_PAGE, { credentials: 'same-origin' });
      if (res.status === 401) {
        window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
        return;
      }
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) throw new Error(data.message || '잉크 이력을 불러오지 못했습니다.');
      const d = data.data || {};
      const bal = Number(d.balance || 0);
      const balEl = m.querySelector('.js-credit-balance');
      balEl.textContent = fmt(bal) + ' C';
      balEl.classList.toggle('is-debt', bal < 0);
      const debt = m.querySelector('.js-credit-debt');
      debt.hidden = bal >= 0;
      debt.textContent = bal < 0 ? 'AI 사용으로 마이너스 잔액이 생겼습니다. 다음 충전 시 ' + fmt(Math.abs(bal)) + ' C이(가) 먼저 차감됩니다.' : '';
      if (nextPage === 1) list.innerHTML = '';
      list.insertAdjacentHTML('beforeend', (d.items || []).map(row).join(''));
      page = Number(d.page || nextPage);
      empty.hidden = list.children.length > 0;
      more.hidden = page >= Number(d.pages || 1);
      m.querySelector('.js-credit-total').textContent = d.total ? '총 ' + fmt(d.total) + '건' : '';
    } catch (err) {
      if (nextPage === 1) list.innerHTML = '';
      empty.hidden = false;
      empty.textContent = err.message;
    } finally {
      loading = false;
      more.disabled = false;
    }
  }

  function place(anchor) {
    const m = ensureModal();
    const dialog = m.querySelector('.credit-history-modal__dialog');
    const vw = document.documentElement.clientWidth;
    const vh = window.innerHeight;
    const margin = 12;
    const width = Math.min(440, vw - margin * 2);
    const r = anchor && anchor.getClientRects().length ? anchor.getBoundingClientRect() : null;
    const top = r ? Math.min(r.bottom + 12, vh - 240) : 72;
    const centerX = r ? r.left + r.width / 2 : vw - margin - width / 2;
    let left = r ? r.right - width : vw - margin - width;
    if (r && left < margin) left = Math.max(margin, Math.min(centerX - width / 2, vw - margin - width));
    left = Math.max(margin, Math.min(left, vw - margin - width));
    dialog.style.top = top + 'px';
    dialog.style.left = left + 'px';
    dialog.style.width = width + 'px';
    dialog.style.maxHeight = Math.min(620, Math.max(240, vh - top - 16)) + 'px';
    dialog.style.setProperty('--arrow-x', Math.max(22, Math.min(centerX - left, width - 22)) + 'px');
  }

  let anchorEl = null;

  function openModal(anchor) {
    const m = ensureModal();
    anchorEl = anchor || null;
    document.body.classList.add('is-credit-history-open');
    m.hidden = false;
    place(anchorEl);
    m.querySelector('.credit-history-modal__close').focus();
    load(1);
  }

  function closeModal() {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('is-credit-history-open');
  }

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest && event.target.closest('[data-credit-history-open]');
    if (!trigger) return;
    event.preventDefault();
    let anchor = trigger;
    const dropdown = trigger.closest('.profile-dropdown');
    if (dropdown) {
      dropdown.hidden = true;
      const profileTrigger = document.getElementById('profileTrigger');
      profileTrigger?.setAttribute('aria-expanded', 'false');
      if (profileTrigger) anchor = profileTrigger;
    }
    openModal(anchor);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal && !modal.hidden) closeModal();
  });

  window.addEventListener('resize', () => {
    if (modal && !modal.hidden) place(anchorEl);
  });
})();
