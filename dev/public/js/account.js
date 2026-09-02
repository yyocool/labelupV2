(() => {
  const openModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeModal = (modal) => {
    if (!modal) return;
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  document.querySelectorAll('[data-open-modal]').forEach((btn) => {
    btn.addEventListener('click', () => openModal(btn.dataset.openModal));
  });

  document.querySelectorAll('[data-close-modal]').forEach((el) => {
    el.addEventListener('click', () => {
      const modal = el.closest('.account-modal');
      closeModal(modal);
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.account-modal:not([hidden])').forEach((modal) => closeModal(modal));
  });

  if (location.hash === '#profile') {
    openModal('profileModal');
  }

  const ensureClipLightbox = () => {
    let root = document.getElementById('accountClipLightbox');
    if (root) return root;
    root = document.createElement('div');
    root.id = 'accountClipLightbox';
    root.className = 'account-clip-lb';
    root.hidden = true;
    root.innerHTML = `
      <div class="account-clip-lb-backdrop" data-clip-close></div>
      <div class="account-clip-lb-dialog" role="dialog" aria-modal="true">
        <button type="button" class="account-clip-lb-close" data-clip-close aria-label="닫기">×</button>
        <div class="account-clip-lb-figure"><img alt=""></div>
        <div class="account-clip-lb-meta">
          <strong></strong>
          <a class="account-btn account-btn--primary" href="#">바로편집</a>
          <button type="button" class="account-btn account-btn--outline" data-clip-close>닫기</button>
        </div>
      </div>`;
    document.body.appendChild(root);
    root.addEventListener('click', (e) => {
      if (e.target.closest('[data-clip-close]')) {
        root.hidden = true;
      }
    });
    return root;
  };

  const openClipPreview = (btn) => {
    const src = btn.dataset.src || '';
    if (!src) return;
    const root = ensureClipLightbox();
    const img = root.querySelector('img');
    const title = root.querySelector('strong');
    const edit = root.querySelector('a.account-btn--primary');
    img.src = src;
    img.alt = btn.dataset.title || '클립아트';
    title.textContent = btn.dataset.title || '클립아트';
    edit.href = btn.dataset.edit || '/editor/';
    try {
      sessionStorage.setItem('labelup.pendingClipart', JSON.stringify({
        url: src,
        title: btn.dataset.title || '',
      }));
    } catch (e) { /* ignore */ }
    root.hidden = false;
  };

  document.querySelectorAll('.js-clip-preview').forEach((btn) => {
    btn.addEventListener('click', () => openClipPreview(btn));
  });

  const inquiryForm = document.getElementById('inquiryForm');
  if (inquiryForm && typeof API !== 'undefined') {
    const alert = document.getElementById('inquiryAlert');
    inquiryForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = inquiryForm.querySelector('button[type=submit]');
      const fd = new FormData(inquiryForm);
      btn.disabled = true;
      try {
        await API.post('/api/inquiry', {
          name: fd.get('name'),
          email: fd.get('email'),
          subject: fd.get('subject'),
          content: fd.get('content'),
        });
        inquiryForm.querySelector('[name=subject]').value = '';
        inquiryForm.querySelector('[name=content]').value = '';
        showAlert(alert, '문의가 접수되었습니다. 관리자가 확인 후 연락드립니다.', 'success');
      } catch (err) {
        showAlert(alert, err.message);
      } finally {
        btn.disabled = false;
      }
    });
  }

  document.querySelectorAll('a.account-btn--primary[href*="clipart="]').forEach((link) => {
    link.addEventListener('click', () => {
      try {
        const href = link.getAttribute('href') || '';
        const q = new URL(href, location.origin).searchParams;
        sessionStorage.setItem('labelup.pendingClipart', JSON.stringify({
          url: q.get('clipart') || '',
          title: q.get('name') || '',
        }));
      } catch (e) { /* ignore */ }
    });
  });
})();
