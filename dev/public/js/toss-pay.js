/**
 * 토스페이먼츠 결제위젯 공통 모듈 (쇼핑몰 / 편집기)
 */
window.LabelUpTossPay = (function () {
  let sdkPromise = null;

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
    await widgets.renderPaymentMethods({
      selector: '#luTossPaymentMethod',
      variantKey: payment.variant_key || 'DEFAULT',
    });
    await widgets.renderAgreement({
      selector: '#luTossAgreement',
      variantKey: payment.agreement_variant_key || 'AGREEMENT',
    });

    payBtn.onclick = async () => {
      payBtn.disabled = true;
      try {
        await widgets.requestPayment({
          orderId: payment.order_id,
          orderName: payment.order_name,
          successUrl: payment.success_url,
          failUrl: payment.fail_url,
          customerEmail: payment.customer_email || undefined,
          customerName: payment.customer_name || undefined,
          customerMobilePhone: payment.customer_mobile_phone || undefined,
        });
      } catch (err) {
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
