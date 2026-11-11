/**
 * 토스페이먼츠 결제위젯 공통 모듈 (쇼핑몰 / 편집기)
 *
 * 상점관리자에서 네이버페이·카카오페이·토스페이를 '커스텀/직연동'으로 넣으면
 * widgets.requestPayment()가 CUSTOM_PAYMENT_METHOD_UNABLE_TO_PAY를 낸다.
 * 그 경우는 결제창 API(payment)의 CARD + DIRECT + easyPay로 연다.
 * 결제창은 API 개별 연동 키(api_client_key)로만 열리고, 승인 시 서버가 API 시크릿 키를 쓰도록 successUrl에 via=api를 붙인다.
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
      '.lu-toss-method-wrap{position:relative}' +
      '.lu-toss-naver{position:absolute;z-index:2;display:flex;align-items:center;justify-content:center;margin:0;padding:0 8px;border:1px solid #e5e8eb;border-radius:8px;background:#fff;cursor:pointer;box-sizing:border-box}' +
      '.lu-toss-naver[hidden]{display:none!important}' +
      '.lu-toss-naver:hover{background:#f8f9fa}' +
      '.lu-toss-naver.is-selected{border:2px solid #191f28}' +
      '.lu-toss-naver svg{display:block;height:22px;width:auto}' +
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
      '  <div class="lu-toss-method-wrap">' +
      '    <div id="luTossPaymentMethod" class="lu-toss-widget"></div>' +
      '    <button type="button" class="lu-toss-naver" data-toss-naver hidden aria-label="네이버페이" aria-pressed="false">' +
      '      <svg viewBox="0 0 92 28" aria-hidden="true">' +
      '        <rect width="28" height="28" rx="6" fill="#03C75A"/>' +
      '        <path fill="#fff" d="M8.1 7.1h3.4l5.2 8.3V7.1h3.2v13.8h-3.4l-5.2-8.3v8.3H8.1V7.1z"/>' +
      '        <text x="36" y="20.2" fill="#191919" font-family="Arial,Helvetica,sans-serif" font-size="18" font-weight="700">pay</text>' +
      '      </svg>' +
      '    </button>' +
      '  </div>' +
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

  function collectStrings(value, out, depth) {
    if (depth > 3 || value == null) return out;
    if (typeof value === 'string') {
      out.push(value);
    } else if (typeof value === 'object') {
      Object.keys(value).forEach((k) => collectStrings(value[k], out, depth + 1));
    }
    return out;
  }

  /** 상점관리자 직연동 항목의 키·이름은 정해진 형식이 없어, 선택 정보 안의 문자열을 모두 훑는다. */
  function resolveEasyPay(selected) {
    if (!selected || typeof selected !== 'object') return '';
    const strings = collectStrings(selected, [], 0);
    for (let i = 0; i < strings.length; i++) {
      const mapped = normalizeEasyPay(strings[i]);
      if (mapped) return mapped;
    }
    const joined = strings.join(' ').toUpperCase();
    if (/NAVER|네이버/.test(joined)) return 'NAVERPAY';
    if (/KAKAO|카카오/.test(joined)) return 'KAKAOPAY';
    if (/TOSS\s*PAY|토스페이/.test(joined)) return 'TOSSPAY';
    return '';
  }

  function isCustomMethod(selected) {
    return String(selected && selected.method ? selected.method : '').toUpperCase() === 'CUSTOM';
  }

  function isCustomUnableError(err) {
    const code = String(err && err.code ? err.code : '');
    const msg = String(err && err.message ? err.message : '');
    return code.indexOf('CUSTOM') === 0 || /커스텀 결제수단/.test(msg);
  }

  const EASY_PAY_LABELS = { NAVERPAY: '네이버페이', KAKAOPAY: '카카오페이', TOSSPAY: '토스페이' };

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

  function withApiMarker(url) {
    if (!url) return url;
    return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'via=api';
  }

  /**
   * 위젯 결제수단은 토스 어드민 구성이라 네이버페이가 칸에 없다.
   * 계좌이체 오른쪽 빈칸에 같은 크기 선택 칸을 올려, 고른 뒤 결제하기로 연다.
   */
  function placeNaverTile(root) {
    const widget = document.getElementById('luTossPaymentMethod');
    const btn = root.querySelector('[data-toss-naver]');
    const iframe = widget && widget.querySelector('iframe');
    if (!widget || !btn || btn.hidden || !iframe) return;
    const h = iframe.offsetHeight;
    const w = iframe.offsetWidth;
    if (h < 80 || w < 80) return;
    // 위젯 버튼은 iframe 안쪽 여백(좌우 24px, 칸 간격 8px) 안에 있다.
    // 왼쪽 끝은 가운데 기준과 같고, 폭만 그 여백만큼 줄여야 오른쪽이 맞는다.
    const sidePad = 24;
    const gap = 8;
    const tileW = Math.round((w - sidePad * 2 - gap) / 2);
    btn.style.left = (Math.round(w / 2) + gap / 2) + 'px';
    btn.style.width = tileW + 'px';
    if (w < 340) {
      btn.style.top = (h + 8) + 'px';
      btn.style.left = '0px';
      btn.style.width = w + 'px';
      btn.style.height = '52px';
      return;
    }
    // 결제수단 칸은 위젯 상단에 고정 높이다. 계좌이체 오른쪽 빈칸.
    btn.style.top = '200px';
    btn.style.height = '54px';
  }

  function setNaverSelected(btn, on) {
    if (!btn) return;
    btn.classList.toggle('is-selected', !!on);
    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
  }

  function naverIsSelected(root) {
    const btn = root.querySelector('[data-toss-naver]');
    return !!(btn && !btn.hidden && btn.classList.contains('is-selected'));
  }

  function selectionKey(selected) {
    return JSON.stringify({
      method: selected && selected.method ? String(selected.method) : '',
      easy: resolveEasyPay(selected) || '',
    });
  }

  /** 위젯 키(gck)로는 결제창을 열 수 없어(NOT_SUPPORTED_WIDGET_KEY) API 개별 연동 키로 따로 연다. */
  async function requestDirectEasyPay(TossPayments, payment, easyPay) {
    const allowed = Array.isArray(payment.direct_easy_pays) ? payment.direct_easy_pays : [];
    const label = EASY_PAY_LABELS[easyPay] || '선택한 간편결제';
    if (!payment.api_client_key || allowed.indexOf(easyPay) < 0) {
      throw new Error(label + '는 현재 결제할 수 없습니다. 다른 결제수단(카드·네이버페이·카카오페이·토스페이)을 선택해 주세요.');
    }
    const paymentWindow = TossPayments(payment.api_client_key).payment({ customerKey: payment.customer_key });
    const base = paymentRequestBase(payment);
    base.successUrl = withApiMarker(base.successUrl);
    await paymentWindow.requestPayment(Object.assign(base, {
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
    const naverBtn = root.querySelector('[data-toss-naver]');
    const allowedEasy = Array.isArray(payment.direct_easy_pays) ? payment.direct_easy_pays : [];
    const naverReady = !!payment.api_client_key && allowedEasy.indexOf('NAVERPAY') >= 0;
    if (naverBtn) {
      naverBtn.hidden = !naverReady;
      naverBtn.disabled = false;
      setNaverSelected(naverBtn, false);
      naverBtn.onclick = async () => {
        setNaverSelected(naverBtn, true);
        naverBtn.dataset.widgetKey = selectionKey(await readSelectedMethod(paymentMethodWidget));
        if (errEl) {
          errEl.hidden = true;
          errEl.textContent = '';
        }
      };
      placeNaverTile(root);
      window.requestAnimationFrame(() => placeNaverTile(root));
      if (window.ResizeObserver) {
        if (root._naverObserver) root._naverObserver.disconnect();
        const observer = new ResizeObserver(() => placeNaverTile(root));
        const iframe = document.querySelector('#luTossPaymentMethod iframe');
        if (iframe) observer.observe(iframe);
        root._naverObserver = observer;
      }
    }
    if (paymentMethodWidget && typeof paymentMethodWidget.on === 'function') {
      paymentMethodWidget.on('paymentMethodSelect', () => setNaverSelected(naverBtn, false));
    }

    payBtn.onclick = async () => {
      payBtn.disabled = true;
      if (errEl) {
        errEl.hidden = true;
        errEl.textContent = '';
      }
      try {
        await ensureAgreed(agreementWidget);
        const selected = await readSelectedMethod(paymentMethodWidget);
        if (naverIsSelected(root)) {
          const naverBtnNow = root.querySelector('[data-toss-naver]');
          const stillNaver = !naverBtnNow.dataset.widgetKey || naverBtnNow.dataset.widgetKey === selectionKey(selected);
          if (stillNaver) {
            await requestDirectEasyPay(TossPayments, payment, 'NAVERPAY');
            return;
          }
          setNaverSelected(naverBtnNow, false);
        }
        const easyPay = resolveEasyPay(selected);
        if (isCustomMethod(selected) && easyPay) {
          await requestDirectEasyPay(TossPayments, payment, easyPay);
          return;
        }
        try {
          await widgets.requestPayment(paymentRequestBase(payment));
        } catch (err) {
          if (!isCustomUnableError(err)) throw err;
          if (!easyPay) {
            throw new Error('선택한 결제수단으로는 바로 결제할 수 없습니다. 카드·네이버페이·카카오페이·토스페이 중 다른 결제수단을 선택해 주세요.');
          }
          await requestDirectEasyPay(TossPayments, payment, easyPay);
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
