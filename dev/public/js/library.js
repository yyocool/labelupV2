(() => {
  const messages = {
    trash: '휴지통으로 보낼까요?',
    restore: '이 항목을 복원할까요?',
    purge: '완전히 삭제하면 되돌릴 수 없습니다. 삭제할까요?',
  };

  const post = async (action, type, id) => {
    const res = await fetch('/api/library/' + action, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ type, id }),
    });
    const json = await res.json().catch(() => null);
    if (!res.ok || !json || json.success === false) {
      throw new Error((json && json.message) || '처리에 실패했습니다.');
    }
    return json;
  };

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.js-lib-act');
    if (!btn) return;
    const action = btn.dataset.act || '';
    const type = btn.dataset.type || '';
    const id = Number(btn.dataset.id || 0);
    if (!action || !type || id <= 0) return;
    const confirmMsg = btn.dataset.confirm || messages[action] || '진행할까요?';
    if (!window.confirm(confirmMsg)) return;
    btn.disabled = true;
    try {
      await post(action, type, id);
      const card = btn.closest('.lib-card, .account-design-card, .account-clip-card');
      if (card) card.remove();
      else location.reload();
    } catch (err) {
      window.alert(err.message || '처리에 실패했습니다.');
      btn.disabled = false;
    }
  });
})();
