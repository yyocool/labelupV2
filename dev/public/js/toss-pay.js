/**
 * 토스페이먼츠 결제위젯 공통 모듈 (쇼핑몰 / 편집기)
 *
 * 상점관리자에서 네이버페이·카카오페이·토스페이를 '커스텀/직연동'으로 넣으면
 * widgets.requestPayment()가 CUSTOM_PAYMENT_METHOD_UNABLE_TO_PAY를 낸다.
 * 그 경우는 결제창 API(payment)의 CARD + DIRECT + easyPay로 연다.
 */
window.LabelUpTossPay = (function () {
  let sdkPromise = null;

  const EASY_PAY_ALIASES = {
    TOSSPAY: 'TOSSPAY',
    TOSS: 'TOSSPAY',
    토스페이: 'TOSSPAY',
    KAKAOPAY: 'KAKAOPAY',
    KAKAO: 'KAKAOPAY',
    카카오페이: 'KAKAOPAY',
    NAVERPAY: 'NAVERPAY',
    NAVER: 'NAVERPAY',
    네이버페이: 'NAVERPAY',
    PAYCO: 'PAYCO',
    SAMSUNGPAY: 'SAMSUNGPAY',
    APPLEPAY: 'APPLEPAY',
  };

  function ensureStyles() {
    if (document.getElementById('luTossPayStyles')) return;
    const style = document.createElement('style');
    style.id = 'luTossPayStyles';
    style.textContent =
      '.lu-toss-modal{position:fixed;inset:0;z-index:200000;display:flex;align-items:center;justify-content:center;padding:16px}' +
      '.lu-toss-modal[hidden]{display:none!important}' +
      '.lu-toss-modal__backdrop{position:absolute;inset:0;background:rgba(28,24,22,.45)}' +
      '.lu-toss-modal__panel{position:relative;width:min(520px,100%);max-height:min(92vh,900px);overflow:auto;background:#fff;border-radius:20px;box-shadow:0 24px 60px rgba(0,0,0,.2);padding:16px 16px 14px}' +
      '.lu-toss-modal__head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:10px}' +
      '.lu-toss-modal__head h2{margin:0;font-size:18px}' +
      '.lu-toss-modal__head p{margin:4px 0 0;color:#7a716b;font-size:13px}' +
      '.lu-toss-modal__close{border:0;background:transparent;font-size:24px;line-height:1;cursor:pointer;color:#6b635d}' +
      '.lu-toss-widget,.lu-toss-agree{margin-top:8px}' +
      '.lu-toss-modal__foot{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}' +
      '.lu-toss-btn{border:1px solid #ddd5ce;background:#fff;border-radius:12px;padding:10px 14px;font-weight:700;cursor:pointer}' +
      '.lu-toss-btn--primary{background:#7B2840;border-color:#7B2840;color:#fff}' +
      '.lu-toss-modal__err{margin:10px 0 0;color:#b42318;font-size:13px;font-weight:600}';
    document.head.appendChild(style);
  }

  function loadSdk() {
    if (window.TossPayments) return Promise.resolve(window.TossPayments);
    if (sdkPromise) return sdkPromise;
    sdkPromise = new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = 'https://js.tosspayments.com/v2/standard';
      s.async = true;
      s.onload = () => (window.TossPayments ? resolve(window.TossPayments) : reject(new Error('토스 SDK 로드 실패')));
      s.onerror = () => reject(new Error('토스 SDK를 불러오지 못했습니다.'));
      document.head.appendChild(s);
    });
    return sdkPromise;
  }

  function ensureModal() {
    ensureStyles();
    let root = document.getElementById('luTossPayModal');
    if (root) return root;
    root = document.createElement('div');
    root.id = 'luTossPayModal';
    root.className = 'lu-toss-modal';
    root.hidden = true;
    root.innerHTML =
      '<div class="lu-toss-modal__backdrop" data-toss-close></div>' +
      '<div class="lu-toss-modal__panel" role="dialog" aria-modal="true" aria-labelledby="luTossPayTitle">' +
      '  <header class="lu-toss-modal__head">' +
      '    <div><h2 id="luTossPayTitle">토스페이먼츠 결제</h2><p data-toss-meta></p></div>' +
      '    <button type="button" class="lu-toss-modal__close" data-toss-close aria-label="닫기">×</button>' +
      '  </header>' +
      '  <div id="luTossPaymentMethod" class="lu-toss-widget"></div>' +
      '  <div id="luTossAgreement" class="lu-toss-agree"></div>' +
      '  <div class="lu-toss-modal__foot">' +
      '    <button type="button" class="lu-toss-btn" data-toss-close>취소</button>' +
      '    <button type="button" class="lu-toss-btn lu-toss-btn--primary" data-toss-pay>결제하기</button>' +
      '  </div>' +
      '  <p class="lu-toss-modal__err" data-toss-error hidden></p>' +
      '</div>';
    document.body.appendChild(root);
    root.addEventListener('click', (e) => {
      const t = e.target;
      if (t && t.closest && t.closest('[data-toss-close]')) close();
    });
    return root;
  }

  function close() {
    const root = document.getElementById('luTossPayModal');
    if (root) root.hidden = true;
  }

  function normalizeEasyPay(raw) {
    const key = String(raw || '').trim().toUpperCase().replace(/[\s_-]/g, '');
    return EASY_PAY_ALIASES[key] || EASY_PAY_ALIASES[String(raw || '').trim()] || '';
  }

  function resolveEasyPay(selected) {
    if (!selected || typeof selected !== 'object') return '';
    const candidates = [
      selected.custom && selected.custom.key,
      selected.card && selected.card.easyPay,
      selected.easyPay && selected.easyPay.provider,
      selected.easyPay,
      selected.code,
    ];
    for (let i = 0; i < candidates.length; i++) {
      const mapped = normalizeEasyPay(candidates[i]);
      if (mapped) return mapped;
    }
    return '';
  }

  function isCustomMethod(selected) {
    return String(selected && selected.method ? selected.method : '').toUpperCase() === 'CUSTOM';
  }

  function isUserCancel(err) {
    const code = String(err && err.code ? err.code : '');
    const msg = String(err && err.message ? err.message : '');
    return code === 'USER_CANCEL' || /취소되었습니다|취소했/.test(msg);
  }

  async function readSelectedMethod(widget) {
    if (!widget || typeof widget.getSelectedPaymentMethod !== 'function') {
      return {};
    }
    try {
      const selected = await widget.getSelectedPaymentMethod();
      return selected && typeof selected === 'object' ? selected : {};
    } catch (e) {
      return {};
    }
  }

  async function ensureAgreed(agreementWidget) {
    if (!agreementWidget || typeof agreementWidget.getAgreementStatus !== 'function') {
      return;
    }
    const status = await agreementWidget.getAgreementStatus();
    if (status && status.agreedRequiredTerms === false) {
      const err = new Error('필수 약관에 동의해 주세요.');
      err.code = 'NEED_AGREEMENT_WITH_REQUIRED_TERMS';
      throw err;
    }
  }

  function paymentRequestBase(payment) {
    return {
      orderId: payment.order_id,
      orderName: payment.order_name,
      successUrl: payment.success_url,
      failUrl: payment.fail_url,
      customerEmail: payment.customer_email || undefined,
      customerName: payment.customer_name || undefined,
      customerMobilePhone: payment.customer_mobile_phone || undefined,
    };
  }

  async function requestDirectEasyPay(tossPayments, payment, easyPay) {
    const paymentWindow = tossPayments.payment({ customerKey: payment.customer_key });
    await paymentWindow.requestPayment(Object.assign(paymentRequestBase(payment), {
      method: 'CARD',
      amount: {
        currency: payment.currency || 'KRW',
        value: Number(payment.amount || 0),
      },
      card: {
        flowMode: 'DIRECT',
        easyPay: easyPay,
      },
    }));
  }

  async function start(payment) {
    if (!payment || !payment.client_key) {
      throw new Error('결제 설정이 없습니다.');
    }
    const TossPayments = await loadSdk();
    const root = ensureModal();
    const errEl = root.querySelector('[data-toss-error]');
    const meta = root.querySelector('[data-toss-meta]');
    const payBtn = root.querySelector('[data-toss-pay]');
    if (errEl) {
      errEl.hidden = true;
      errEl.textContent = '';
    }
    if (meta) {
      meta.textContent = (payment.order_name || '주문') + ' · ' + Number(payment.amount || 0).toLocaleString() + '원';
    }
    document.getElementById('luTossPaymentMethod').innerHTML = '';
    document.getElementById('luTossAgreement').innerHTML = '';
    root.hidden = false;

    const tossPayments = TossPayments(payment.client_key);
    const widgets = tossPayments.widgets({ customerKey: payment.customer_key });
    await widgets.setAmount({ currency: payment.currency || 'KRW', value: Number(payment.amount || 0) });
    const paymentMethodWidget = await widgets.renderPaymentMethods({
      selector: '#luTossPaymentMethod',
      variantKey: payment.variant_key || 'DEFAULT',
    });
    const agreementWidget = await widgets.renderAgreement({
      selector: '#luTossAgreement',
      variantKey: payment.agreement_variant_key || 'AGREEMENT',
    });

    payBtn.onclick = async () => {
      payBtn.disabled = true;
      if (errEl) {
        errEl.hidden = true;
        errEl.textContent = '';
      }
      try {
        await ensureAgreed(agreementWidget);
        const selected = await readSelectedMethod(paymentMethodWidget);
        const easyPay = resolveEasyPay(selected);
        if (isCustomMethod(selected)) {
          if (!easyPay) {
            throw new Error('선택한 결제수단은 상점관리자의 커스텀(직연동) 항목이라 바로 결제할 수 없습니다. 네이버페이·카카오페이·토스페이는 결제 어드민에서 일반 간편결제로 켜거나, 직연동 키를 NAVERPAY / KAKAOPAY / TOSSPAY로 맞춰 주세요.');
          }
          await requestDirectEasyPay(tossPayments, payment, easyPay);
          return;
        }
        try {
          await widgets.requestPayment(paymentRequestBase(payment));
        } catch (err) {
          if (String(err && err.code ? err.code : '') === 'CUSTOM_PAYMENT_METHOD_UNABLE_TO_PAY' && easyPay) {
            await requestDirectEasyPay(tossPayments, payment, easyPay);
            return;
          }
          throw err;
        }
      } catch (err) {
        if (isUserCancel(err)) {
          payBtn.disabled = false;
          return;
        }
        if (errEl) {
          errEl.hidden = false;
          errEl.textContent = err.message || '결제를 시작하지 못했습니다.';
        }
        payBtn.disabled = false;
      }
    };
  }

  return { start, close, loadSdk };
})();
