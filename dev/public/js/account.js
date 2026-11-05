(() => {
  document.querySelectorAll('.account-modal').forEach((modal) => document.body.appendChild(modal));

  const openModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    modal.dispatchEvent(new CustomEvent('account:modal-open'));
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
  if (location.hash === '#notifications') {
    openModal('notifPrefsModal');
  }
  const openInquiryFromHash = () => {
    if (location.hash !== '#inquiry') return;
    openModal('inquiryModal');
    document.querySelector('#inquiryModal [name=subject]')?.focus();
  };
  openInquiryFromHash();
  document.addEventListener('click', (e) => {
    const link = e.target.closest && e.target.closest('a[href$="#inquiry"]');
    if (!link || !document.getElementById('inquiryModal')) return;
    e.preventDefault();
    history.replaceState(null, '', '#inquiry');
    openInquiryFromHash();
  });

  const inquiriesModal = document.getElementById('inquiriesModal');
  if (inquiriesModal) {
    const list = inquiriesModal.querySelector('.js-inquiry-list');
    const empty = inquiriesModal.querySelector('.js-inquiry-empty');
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const render = (items) => {
      empty.hidden = items.length > 0;
      empty.textContent = '아직 남긴 문의가 없습니다.';
      list.innerHTML = items.map((it) => `
        <li class="account-inquiry-item">
          <details${it.answer ? ' open' : ''}>
            <summary>
              <span class="account-inquiry-status is-${esc(it.status)}">${esc(it.status_label)}</span>
              <strong>${esc(it.subject)}</strong>
              <small>${esc(it.created_at)}</small>
            </summary>
            <div class="account-inquiry-q">${esc(it.content)}</div>
            ${it.answer
              ? `<div class="account-inquiry-a"><b>라벨업 답변</b>${it.answered_at ? `<small>${esc(it.answered_at)}</small>` : ''}<p>${esc(it.answer)}</p></div>`
              : '<p class="account-inquiry-wait">답변을 준비하고 있습니다. 답변이 등록되면 알림으로 알려 드려요.</p>'}
          </details>
        </li>`).join('');
    };
    const load = async () => {
      list.innerHTML = '<li class="account-history-loading">불러오는 중…</li>';
      empty.hidden = true;
      try {
        const res = await fetch('/api/inquiry/mine', { credentials: 'same-origin' });
        const json = await res.json();
        if (!res.ok || !json.success) throw new Error(json.message || '문의 내역을 불러오지 못했습니다.');
        render(json.data?.items || []);
      } catch (err) {
        list.innerHTML = '';
        empty.hidden = false;
        empty.textContent = err.message;
      }
    };
    inquiriesModal.addEventListener('account:modal-open', load);
    if (location.hash === '#inquiries') openModal('inquiriesModal');
    window.addEventListener('hashchange', () => {
      if (location.hash === '#inquiries' && inquiriesModal.hidden) openModal('inquiriesModal');
    });
    document.addEventListener('click', (e) => {
      const link = e.target.closest && e.target.closest('a[href$="#inquiries"]');
      if (!link) return;
      const target = new URL(link.href, location.origin);
      if (target.pathname !== location.pathname) return;
      e.preventDefault();
      history.replaceState(null, '', '#inquiries');
      document.querySelectorAll('.account-modal:not([hidden])').forEach((modal) => closeModal(modal));
      openModal('inquiriesModal');
    });
  }

  const notifSummary = document.getElementById('accountNotifSummary');
  const notifForm = document.getElementById('notifPrefsForm');
  const notifFields = document.getElementById('notifPrefsFields');
  const notifAlert = document.getElementById('notifPrefsAlert');
  const notifThreshold = document.getElementById('notifLowThreshold');

  const renderNotifPrefs = (prefs) => {
    if (!prefs) return;
    if (notifSummary) {
      const meta = prefs.meta || {};
      notifSummary.innerHTML = Object.keys(meta).map((key) => {
        const m = meta[key] || {};
        const on = !!prefs[key];
        return '<li><span>' + (m.label || key) + '</span><em>' + (on ? '수신' : '꺼짐') + '</em></li>';
      }).join('') +
        '<li><span>잉크 부족 기준</span><em>' + Number(prefs.low_credit_threshold || 100).toLocaleString() + ' C</em></li>';
    }
    if (notifFields) {
      const meta = prefs.meta || {};
      notifFields.innerHTML = Object.keys(meta).map((key) => {
        const m = meta[key] || {};
        const checked = prefs[key] ? ' checked' : '';
        return (
          '<label class="account-notif-pref">' +
            '<input type="checkbox" name="' + key + '" value="1"' + checked + '>' +
            '<span><strong>' + (m.label || key) + '</strong><span>' + (m.hint || '') + '</span></span>' +
          '</label>'
        );
      }).join('');
    }
    if (notifThreshold) notifThreshold.value = String(prefs.low_credit_threshold || 100);
  };

  const loadNotifPrefs = async () => {
    if (!notifSummary && !notifForm) return;
    try {
      const res = await fetch('/api/notifications/prefs', {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      const json = await res.json();
      if (!res.ok || json.success === false) throw new Error(json.message || '불러오기 실패');
      renderNotifPrefs(json.data || {});
    } catch (err) {
      if (notifSummary) notifSummary.innerHTML = '<li>알림 설정을 불러오지 못했습니다.</li>';
    }
  };

  loadNotifPrefs();

  if (notifForm) {
    notifForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(notifForm);
      const payload = {
        pref_welcome: fd.get('pref_welcome') ? 1 : 0,
        pref_credit_low: fd.get('pref_credit_low') ? 1 : 0,
        pref_credit_change: fd.get('pref_credit_change') ? 1 : 0,
        pref_order_placed: fd.get('pref_order_placed') ? 1 : 0,
        pref_order_status: fd.get('pref_order_status') ? 1 : 0,
        pref_system: fd.get('pref_system') ? 1 : 0,
        low_credit_threshold: Number(fd.get('low_credit_threshold') || 100),
      };
      const btn = notifForm.querySelector('[type=submit]');
      if (btn) btn.disabled = true;
      try {
        const res = await fetch('/api/notifications/prefs', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (!res.ok || json.success === false) throw new Error(json.message || '저장 실패');
        renderNotifPrefs(json.data || {});
        if (typeof showAlert === 'function') showAlert(notifAlert, json.message || '저장했습니다.', 'success');
        else if (notifAlert) {
          notifAlert.textContent = json.message || '저장했습니다.';
          notifAlert.className = 'account-alert is-success';
        }
      } catch (err) {
        if (typeof showAlert === 'function') showAlert(notifAlert, err.message);
        else if (notifAlert) {
          notifAlert.textContent = err.message;
          notifAlert.className = 'account-alert is-error';
        }
      } finally {
        if (btn) btn.disabled = false;
      }
    });
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
        showAlert(alert, '문의가 접수되었습니다. 답변은 「내 문의 내역」에서 확인할 수 있어요.', 'success');
        setTimeout(() => {
          closeModal(document.getElementById('inquiryModal'));
          alert.classList.remove('show');
          openModal('inquiriesModal');
        }, 1200);
      } catch (err) {
        showAlert(alert, err.message);
      } finally {
        btn.disabled = false;
      }
    });
  }

  // 찜한 상품 — 해제하면 목록에서 바로 빠진다.
  const wishList = document.getElementById('accountWishList');
  if (wishList) {
    const wishEmpty = document.getElementById('accountWishEmpty');
    wishList.addEventListener('click', async (e) => {
      const btn = e.target.closest('.js-wish-remove');
      if (!btn) return;
      const productId = Number(btn.dataset.productId || 0);
      if (!productId) return;
      btn.disabled = true;
      try {
        const res = await fetch('/api/shop/wishlist/remove', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify({ product_id: productId }),
        });
        const json = await res.json();
        if (!res.ok || json.success === false) throw new Error(json.message || '해제 실패');
        btn.closest('[data-wish-product-id]')?.remove();
        if (wishEmpty && !wishList.querySelector('[data-wish-product-id]')) wishEmpty.hidden = false;
      } catch (err) {
        btn.disabled = false;
      }
    });
  }

  document.querySelectorAll('[data-use-coupon]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const code = String(btn.getAttribute('data-use-coupon') || '').trim();
      if (!code || btn.disabled) return;
      btn.disabled = true;
      try {
        const res = await fetch('/api/shop/coupon/select', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ code }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
          throw new Error(data.message || '쿠폰을 선택하지 못했습니다.');
        }
        window.location.href = data.data?.redirect || '/shop/products';
      } catch (err) {
        window.alert(err.message || '쿠폰을 선택하지 못했습니다.');
        btn.disabled = false;
      }
    });
  });

  document.querySelectorAll('[data-copy-coupon]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const code = String(btn.getAttribute('data-copy-coupon') || '');
      if (!code) return;
      try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(code);
        } else {
          const tmp = document.createElement('textarea');
          tmp.value = code;
          document.body.appendChild(tmp);
          tmp.select();
          document.execCommand('copy');
          tmp.remove();
        }
        const prev = btn.textContent;
        btn.textContent = '복사됨';
        setTimeout(() => { btn.textContent = prev; }, 1400);
      } catch (_) {
        window.prompt('쿠폰 코드를 복사하세요', code);
      }
    });
  });

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

  document.querySelectorAll('[data-switch-modal]').forEach((btn) => {
    btn.addEventListener('click', () => {
      closeModal(btn.closest('.account-modal'));
      openModal(btn.dataset.switchModal);
    });
  });

  const profileForm = document.getElementById('profileForm');
  if (profileForm) {
    const digits = (v) => String(v || '').replace(/\D+/g, '');
    const emailInput = document.getElementById('pfEmail');
    const pwField = document.getElementById('pfPasswordField');
    const pwInput = document.getElementById('pfCurrentPassword');
    const hasSocial = profileForm.dataset.hasSocial === '1';

    emailInput?.addEventListener('input', () => {
      const changed = emailInput.value.trim().toLowerCase() !== String(emailInput.dataset.original || '').toLowerCase();
      const needPw = changed && !hasSocial;
      pwField.hidden = !needPw;
      pwInput.required = needPw;
      if (!needPw) pwInput.value = '';
    });

    const phoneInput = document.getElementById('pfPhone');
    const sendBtn = document.getElementById('pfSendSms');
    const smsField = document.getElementById('pfSmsField');
    const codeInput = document.getElementById('pfSmsCode');
    const verifyBtn = document.getElementById('pfVerifySms');
    const tokenInput = document.getElementById('pfPhoneToken');
    const phoneHint = document.getElementById('pfPhoneHint');
    const smsHint = document.getElementById('pfSmsHint');

    if (profileForm.dataset.smsRequired === '1' && phoneInput && sendBtn && smsField && tokenInput) {
      const original = digits(phoneInput.dataset.original);
      const defaultHint = phoneHint.textContent;
      let verifiedPhone = '';
      let timer = null;
      const setHint = (el, msg, ok) => {
        el.textContent = msg;
        el.classList.toggle('is-ok', ok === true);
        el.classList.toggle('is-err', ok === false);
      };
      const resetVerify = () => {
        tokenInput.value = '';
        verifiedPhone = '';
        codeInput.value = '';
        codeInput.readOnly = false;
        verifyBtn.disabled = false;
        setHint(smsHint, '', null);
      };

      phoneInput.addEventListener('input', () => {
        const now = digits(phoneInput.value);
        const changed = now !== '' && now !== original;
        sendBtn.hidden = !changed;
        if (verifiedPhone && now !== verifiedPhone) resetVerify();
        if (!changed) {
          smsField.hidden = true;
          resetVerify();
          setHint(phoneHint, defaultHint, null);
        }
      });

      sendBtn.addEventListener('click', async () => {
        sendBtn.disabled = true;
        try {
          const res = await API.post('/api/auth/phone/send', { phone: phoneInput.value.trim() });
          resetVerify();
          smsField.hidden = false;
          codeInput.focus();
          const mask = (res.data && res.data.phone_mask) || '';
          setHint(phoneHint, (mask ? mask + ' 으로 ' : '') + '인증번호를 보냈습니다. 3분 안에 입력해 주세요.', true);
          let left = Number((res.data && res.data.cooldown) || 60);
          sendBtn.textContent = left + '초';
          clearInterval(timer);
          timer = setInterval(() => {
            left -= 1;
            if (left <= 0) {
              clearInterval(timer);
              sendBtn.disabled = false;
              sendBtn.textContent = '다시 받기';
              return;
            }
            sendBtn.textContent = left + '초';
          }, 1000);
        } catch (err) {
          sendBtn.disabled = false;
          setHint(phoneHint, err.message, false);
        }
      });

      verifyBtn.addEventListener('click', async () => {
        verifyBtn.disabled = true;
        try {
          const phone = phoneInput.value.trim();
          const res = await API.post('/api/auth/phone/verify', { phone, code: codeInput.value.trim() });
          tokenInput.value = (res.data && res.data.token) || '';
          if (!tokenInput.value) throw new Error('인증 정보를 받지 못했습니다. 다시 시도해 주세요.');
          verifiedPhone = digits(phone);
          codeInput.readOnly = true;
          setHint(smsHint, '인증되었습니다. 정보 저장을 눌러 주세요.', true);
        } catch (err) {
          verifyBtn.disabled = false;
          setHint(smsHint, err.message, false);
        }
      });

      profileForm.addEventListener('submit', (e) => {
        const now = digits(phoneInput.value);
        if (now !== '' && now !== original && !tokenInput.value) {
          e.preventDefault();
          e.stopImmediatePropagation();
          setHint(phoneHint, '바뀐 번호의 문자 인증을 먼저 완료해 주세요.', false);
          phoneInput.focus();
        }
      }, true);
    }
  }

  const historyModal = document.getElementById('historyModal');
  if (historyModal) {
    const HISTORY = {
      credit: { title: '잉크 내역', url: '/api/credit/me', empty: '잉크 사용·적립 내역이 없습니다.' },
      ai: { title: 'AI 사용 이력', url: '/api/credit/me/ai-usage', empty: '아직 AI 사용 이력이 없습니다.' },
    };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmt = (n) => Number(n || 0).toLocaleString('ko-KR');
    const list = historyModal.querySelector('.js-history-list');
    const empty = historyModal.querySelector('.js-history-empty');
    const more = historyModal.querySelector('.js-history-more');
    const summary = historyModal.querySelector('.js-history-summary');
    const total = historyModal.querySelector('.js-history-total');
    let kind = 'credit';
    let page = 1;
    let loading = false;
    let seq = 0;

    const creditRow = (tx) => {
      const amt = Number(tx.amount || 0);
      const meta = [String(tx.created_at || '').slice(0, 16), tx.tx_type_label, tx.source_label].filter(Boolean).map(esc).join(' · ');
      return '<li class="account-history-row">' +
        '<div class="account-history-main"><strong>' + esc(tx.description || tx.tx_type_label || '잉크') + '</strong><span>' + meta + '</span></div>' +
        '<div class="account-history-side">' +
          '<b class="' + (amt >= 0 ? 'is-plus' : 'is-minus') + '">' + (amt >= 0 ? '+' : '') + fmt(amt) + ' C</b>' +
          '<small>잔액 ' + fmt(tx.balance_after) + ' C</small>' +
        '</div></li>';
    };
    const aiRow = (row) => {
      const ok = String(row.status || '') === 'ok';
      const meta = [String(row.created_at || '').slice(0, 16), row.surface_label].filter(Boolean).map(esc);
      if (Number(row.total_tokens || 0) > 0) meta.push(fmt(row.total_tokens) + ' 토큰');
      return '<li class="account-history-row">' +
        '<div class="account-history-main"><strong>' + esc(row.intent_label || 'AI') + '</strong><span>' + meta.join(' · ') + '</span></div>' +
        '<div class="account-history-side"><em class="account-history-badge' + (ok ? '' : ' is-error') + '">' + (ok ? '성공' : '오류') + '</em></div></li>';
    };

    const renderSummary = (d) => {
      const bal = Number(d.balance || 0);
      const balHtml = '<span>보유 잉크</span><strong' + (bal < 0 ? ' class="is-debt"' : '') + '>' + fmt(bal) + ' C</strong>';
      summary.innerHTML = kind === 'ai'
        ? '<div><span>이번 달 AI 사용</span><strong>' + fmt(d.used) + ' C</strong></div><div>' + balHtml + '</div>'
        : '<div>' + balHtml + '</div><a class="account-btn account-btn--primary account-btn--sm" href="/shop/products?category=ink-charge">잉크 충전</a>';
    };

    const load = async (nextPage) => {
      if (loading && nextPage !== 1) return;
      loading = true;
      const mySeq = ++seq;
      more.disabled = true;
      if (nextPage === 1) {
        list.innerHTML = '<li class="account-history-loading">불러오는 중…</li>';
        empty.hidden = true;
        more.hidden = true;
        total.textContent = '';
      }
      try {
        const res = await fetch(HISTORY[kind].url + '?page=' + nextPage + '&per_page=20', { credentials: 'same-origin' });
        if (res.status === 401) {
          location.href = '/login?redirect=' + encodeURIComponent(location.pathname);
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (mySeq !== seq) return;
        if (!res.ok || data.success === false) throw new Error(data.message || '이력을 불러오지 못했습니다.');
        const d = data.data || {};
        if (nextPage === 1) {
          list.innerHTML = '';
          renderSummary(d);
        }
        list.insertAdjacentHTML('beforeend', (d.items || []).map(kind === 'ai' ? aiRow : creditRow).join(''));
        page = Number(d.page || nextPage);
        empty.textContent = HISTORY[kind].empty;
        empty.hidden = list.children.length > 0;
        more.hidden = page >= Number(d.pages || 1);
        total.textContent = d.total ? '전체 ' + fmt(d.total) + '건 중 ' + fmt(list.children.length) + '건 표시' : '';
      } catch (err) {
        if (mySeq !== seq) return;
        if (nextPage === 1) list.innerHTML = '';
        empty.textContent = err.message;
        empty.hidden = false;
      } finally {
        if (mySeq === seq) {
          loading = false;
          more.disabled = false;
        }
      }
    };

    document.querySelectorAll('[data-history-open]').forEach((btn) => {
      btn.addEventListener('click', () => {
        kind = HISTORY[btn.dataset.historyOpen] ? btn.dataset.historyOpen : 'credit';
        historyModal.querySelector('#historyModalTitle').textContent = HISTORY[kind].title;
        summary.innerHTML = '';
        openModal('historyModal');
        historyModal.querySelector('.account-modal-body').scrollTop = 0;
        load(1);
      });
    });
    more.addEventListener('click', () => load(page + 1));
  }
})();
