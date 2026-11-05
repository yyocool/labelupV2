const ShopAPI = {
  async request(path, options = {}) {
    const res = await fetch(path, {
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      credentials: 'same-origin',
      ...options,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      const err = new Error(data.message || '요청 처리 중 오류가 발생했습니다.');
      // 호출하는 쪽에서 401(로그인 필요)을 구분해야 한다.
      err.status = res.status;
      throw err;
    }
    return data;
  },
  post(path, body) {
    return this.request(path, { method: 'POST', body: JSON.stringify(body) });
  },
};

/** 바로구매 주문서(/shop/cart?mode=buy)에서는 장바구니 대신 바로구매 줄을 다룬다. */
const CART_MODE = new URLSearchParams(window.location.search).get('mode') === 'buy' ? 'buy' : '';

function updateCartBadges(count) {
  ['shopCartBadge', 'shopFloatBadge', 'shopFabBadge'].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    if (count > 0) {
      el.textContent = String(count);
      el.hidden = false;
    } else {
      el.hidden = true;
    }
  });
}

function showShopToast(message) {
  let toast = document.getElementById('shopToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'shopToast';
    toast.className = 'shop-toast';
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.classList.add('is-show');
  clearTimeout(showShopToast._timer);
  showShopToast._timer = setTimeout(() => toast.classList.remove('is-show'), 2200);
}

async function addToCart(productId, qty = 1, optionId = 0, toast = true) {
  const res = await ShopAPI.post('/api/shop/cart/add', { product_id: productId, qty, option_id: optionId });
  updateCartBadges(res.data?.count ?? 0);
  if (toast) showShopToast(res.message || '장바구니에 담았습니다.');
  return res;
}

async function updateCartQty(productId, qty, optionId = 0) {
  const res = await ShopAPI.post('/api/shop/cart/update', { product_id: productId, qty, option_id: optionId, mode: CART_MODE });
  return res;
}

async function removeFromCart(productId, optionId = 0) {
  const res = await ShopAPI.post('/api/shop/cart/remove', { product_id: productId, option_id: optionId, mode: CART_MODE });
  return res;
}

/* 옵션 컨트롤은 라디오 버튼 묶음(상품 상세)과 select(그 외) 두 꼴이 있다. */

/** 고른 옵션 엘리먼트. 아직 고르지 않았으면 null. */
function pickedOptionEl(root) {
  if (!root) return null;
  if (root.tagName === 'SELECT') return root.value ? root.selectedOptions[0] : null;
  return root.querySelector('input[type=radio]:checked');
}

function optionIdOf(root) {
  return Number(pickedOptionEl(root)?.value || 0);
}

function focusOptionControl(root) {
  if (!root) return;
  const el = root.tagName === 'SELECT' ? root : root.querySelector('input[type=radio]:not(:disabled)');
  el?.focus();
}

/** 옵션을 고르면 결제 금액과 수량 상한이 함께 바뀐다. */
function bindOptionSelects() {
  document.querySelectorAll('[data-option-price-target]').forEach((root) => {
    const target = document.querySelector(root.dataset.optionPriceTarget);
    const qtyInput = document.getElementById('productQty');
    const sync = () => {
      const picked = pickedOptionEl(root);
      if (!picked) return;
      const unit = Number(picked.dataset.unit || 0);
      const stock = Number(picked.dataset.stock || 0);
      if (target) target.textContent = `${unit.toLocaleString()}원`;
      if (qtyInput) {
        qtyInput.max = String(Math.max(1, stock));
        if (Number(qtyInput.value || 1) > stock) qtyInput.value = String(Math.max(1, stock));
      }
    };
    root.addEventListener('change', sync);
    sync();
  });
}

function bindQtyControls() {
  document.querySelectorAll('[data-qty-minus]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = btn.parentElement?.querySelector('input[type=number]');
      if (!input) return;
      input.value = String(Math.max(1, Number(input.value || 1) - 1));
    });
  });
  document.querySelectorAll('[data-qty-plus]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = btn.parentElement?.querySelector('input[type=number]');
      if (!input) return;
      const max = Number(input.max || 999);
      input.value = String(Math.min(max, Number(input.value || 1) + 1));
    });
  });
}

function bindAddCartButtons() {
  document.querySelectorAll('[data-add-cart]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = Number(btn.dataset.addCart);
      let qty = 1;
      const qtySel = btn.dataset.qtyInput;
      // 목록 카드에는 옵션·수량 칸이 없으니, 옵션 상품이면 하단 시트에서 고르게 한다.
      if (!qtySel && !btn.dataset.optionSelect) {
        btn.disabled = true;
        try {
          await addFromCard(id);
        } catch (err) {
          showShopToast(err.message);
          if (err.status === 401) setTimeout(goLogin, 900);
        } finally {
          btn.disabled = false;
        }
        return;
      }
      if (qtySel) {
        const input = document.querySelector(qtySel);
        if (input) qty = Number(input.value || 1);
      }
      let optionId = 0;
      const optSel = btn.dataset.optionSelect;
      if (optSel) {
        const control = document.querySelector(optSel);
        optionId = optionIdOf(control);
        if (!optionId) {
          showShopToast('옵션을 선택해 주세요.');
          focusOptionControl(control);
          return;
        }
      }
      btn.disabled = true;
      try {
        if ('buyNow' in btn.dataset) {
          const res = await ShopAPI.post('/api/shop/buy-now', { product_id: id, qty, option_id: optionId });
          window.location.href = res.data?.redirect || '/shop/cart?mode=buy';
          return;
        }
        const confirmAfter = 'cartConfirm' in btn.dataset;
        await addToCart(id, qty, optionId, !confirmAfter);
        if (confirmAfter) openCartConfirm();
      } catch (err) {
        showShopToast(err.message);
        if (err.status === 401) setTimeout(goLogin, 900);
      } finally {
        btn.disabled = false;
      }
    });
  });
}

async function addFromCard(productId) {
  const res = await ShopAPI.request(`/api/shop/products/${productId}`);
  const product = res.data || {};
  const options = Array.isArray(product.options) ? product.options : [];
  if (!options.length) {
    await addToCart(productId, 1, 0);
    return;
  }
  openOptionSheet(product, options);
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function optionSheet() {
  let sheet = document.getElementById('shopOptionSheet');
  if (sheet) return sheet;
  sheet = document.createElement('div');
  sheet.id = 'shopOptionSheet';
  sheet.className = 'shop-opt-sheet';
  sheet.hidden = true;
  sheet.innerHTML = `
    <div class="shop-opt-sheet__dim" data-opt-sheet-close></div>
    <div class="shop-opt-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="shopOptSheetTitle">
      <div class="shop-opt-sheet__grip" aria-hidden="true"></div>
      <div class="shop-opt-sheet__head">
        <img class="shop-opt-sheet__thumb" alt="" data-opt-sheet-thumb>
        <div class="shop-opt-sheet__info">
          <strong id="shopOptSheetTitle" data-opt-sheet-name></strong>
          <span data-opt-sheet-spec></span>
        </div>
        <button type="button" class="shop-opt-sheet__x" data-opt-sheet-close aria-label="닫기">×</button>
      </div>
      <fieldset class="shop-option-set">
        <legend class="shop-detail-option__lab">옵션 선택 <em>필수</em></legend>
        <div class="shop-option-grid" data-opt-sheet-grid></div>
      </fieldset>
      <div class="shop-opt-sheet__row">
        <span class="shop-opt-sheet__lab">수량</span>
        <div class="shop-qty">
          <button type="button" class="shop-qty-btn" data-opt-sheet-minus aria-label="수량 감소">−</button>
          <input type="number" min="1" value="1" inputmode="numeric" aria-label="수량 입력" data-opt-sheet-qty>
          <button type="button" class="shop-qty-btn" data-opt-sheet-plus aria-label="수량 증가">+</button>
        </div>
      </div>
      <div class="shop-opt-sheet__row shop-opt-sheet__total">
        <span class="shop-opt-sheet__lab">선택 금액</span>
        <strong data-opt-sheet-total>-</strong>
      </div>
      <button type="button" class="shop-btn shop-btn--primary shop-opt-sheet__submit" data-opt-sheet-submit>장바구니 담기</button>
    </div>`;
  document.body.appendChild(sheet);

  const qtyInput = sheet.querySelector('[data-opt-sheet-qty]');
  const grid = sheet.querySelector('[data-opt-sheet-grid]');
  const picked = () => grid.querySelector('input[type=radio]:checked');
  const clampQty = () => {
    const max = Number(qtyInput.max || 999);
    const qty = Math.min(max, Math.max(1, Math.floor(Number(qtyInput.value) || 1)));
    qtyInput.value = String(qty);
    return qty;
  };
  sheet.refresh = () => {
    const opt = picked();
    qtyInput.max = String(Math.max(1, Number(opt?.dataset.stock || 999)));
    const qty = clampQty();
    const total = sheet.querySelector('[data-opt-sheet-total]');
    total.classList.toggle('is-empty', !opt);
    total.textContent = opt
      ? `${(Number(opt.dataset.unit || 0) * qty).toLocaleString()}원`
      : '옵션을 선택해 주세요';
  };

  sheet.addEventListener('click', (e) => {
    if (e.target.closest('[data-opt-sheet-close]')) closeOptionSheet();
  });
  sheet.querySelector('[data-opt-sheet-minus]').addEventListener('click', () => {
    qtyInput.value = String(Number(qtyInput.value || 1) - 1);
    sheet.refresh();
  });
  sheet.querySelector('[data-opt-sheet-plus]').addEventListener('click', () => {
    qtyInput.value = String(Number(qtyInput.value || 1) + 1);
    sheet.refresh();
  });
  qtyInput.addEventListener('change', () => sheet.refresh());
  grid.addEventListener('change', () => sheet.refresh());
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !sheet.hidden) closeOptionSheet();
  });

  const submit = sheet.querySelector('[data-opt-sheet-submit]');
  submit.addEventListener('click', async () => {
    const opt = picked();
    if (!opt) {
      showShopToast('옵션을 선택해 주세요.');
      grid.querySelector('input[type=radio]:not(:disabled)')?.focus();
      return;
    }
    submit.disabled = true;
    try {
      await addToCart(Number(sheet.dataset.productId), clampQty(), Number(opt.value), false);
      closeOptionSheet();
      openCartConfirm();
    } catch (err) {
      showShopToast(err.message);
      if (err.status === 401) setTimeout(goLogin, 900);
    } finally {
      submit.disabled = false;
    }
  });
  return sheet;
}

function openOptionSheet(product, options) {
  const sheet = optionSheet();
  sheet.dataset.productId = String(product.id);
  const thumb = sheet.querySelector('[data-opt-sheet-thumb]');
  thumb.hidden = !product.thumbnail;
  if (product.thumbnail) thumb.src = product.thumbnail;
  sheet.querySelector('[data-opt-sheet-name]').textContent = product.name || '';
  const size = product.width_mm && product.height_mm ? `${product.width_mm}×${product.height_mm}mm` : '';
  sheet.querySelector('[data-opt-sheet-spec]').textContent = [
    product.material,
    size,
    product.labels_per_sheet ? `${product.labels_per_sheet}칸` : '',
  ].filter(Boolean).join(' · ');
  const sellable = options.filter((o) => !o.soldout);
  sheet.querySelector('[data-opt-sheet-grid]').innerHTML = options.map((o) => {
    const id = `shopOptSheet${Number(o.id)}`;
    const extra = o.soldout
      ? '<span class="shop-option-card__flag">품절</span>'
      : (o.delta_label ? `<span class="shop-option-card__delta">${escapeHtml(o.delta_label)}</span>` : '');
    return `<input class="shop-option-radio" type="radio" name="shop_sheet_option" id="${id}" value="${Number(o.id)}"
        data-unit="${Number(o.unit_price) || 0}" data-stock="${Number(o.stock_qty) || 0}"${o.soldout ? ' disabled' : ''}${sellable.length === 1 && !o.soldout ? ' checked' : ''}>
      <label class="shop-option-card" for="${id}">
        <span class="shop-option-card__name">${escapeHtml(o.name)}</span>
        <span class="shop-option-card__price">${escapeHtml(o.price_label)}</span>${extra}
      </label>`;
  }).join('');
  sheet.querySelector('[data-opt-sheet-qty]').value = '1';
  const submit = sheet.querySelector('[data-opt-sheet-submit]');
  submit.disabled = sellable.length === 0;
  submit.textContent = sellable.length ? '장바구니 담기' : '모든 옵션이 품절되었습니다';
  sheet.refresh();
  sheet.hidden = false;
  document.documentElement.classList.add('shop-opt-sheet-open');
  requestAnimationFrame(() => sheet.classList.add('is-open'));
  (sheet.querySelector('.shop-option-radio:checked') || sheet.querySelector('.shop-option-radio:not(:disabled)') || sheet.querySelector('.shop-opt-sheet__x'))?.focus();
}

function closeOptionSheet() {
  const sheet = document.getElementById('shopOptionSheet');
  if (!sheet || sheet.hidden) return;
  sheet.classList.remove('is-open');
  document.documentElement.classList.remove('shop-opt-sheet-open');
  setTimeout(() => { sheet.hidden = true; }, 220);
}

/** 장바구니에 담은 뒤 바로 갈지, 계속 둘러볼지 묻는다. */
function openCartConfirm() {
  let modal = document.getElementById('shopCartConfirm');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'shopCartConfirm';
    modal.className = 'shop-cart-confirm';
    modal.hidden = true;
    modal.innerHTML = `
      <div class="shop-cart-confirm__dim" data-cart-confirm-close></div>
      <div class="shop-cart-confirm__box" role="dialog" aria-modal="true" aria-labelledby="shopCartConfirmTitle">
        <button type="button" class="shop-cart-confirm__x" data-cart-confirm-close aria-label="닫기">×</button>
        <div class="shop-cart-confirm__ic" aria-hidden="true">
          <svg viewBox="0 0 48 48" width="44" height="44">
            <circle cx="24" cy="24" r="22" fill="#fdf0f3"/>
            <path d="M12 15h4l3.2 14.2a2 2 0 0 0 2 1.6h11.4a2 2 0 0 0 2-1.5L37 20H18" fill="none" stroke="#7b2d3e" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="22" cy="35.5" r="2" fill="#7b2d3e"/><circle cx="32" cy="35.5" r="2" fill="#7b2d3e"/>
            <circle cx="36" cy="13" r="6" fill="#26a960"/>
            <path d="M33.3 13l1.9 1.9 3.6-3.7" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <strong id="shopCartConfirmTitle">장바구니에 담았어요</strong>
        <p data-cart-confirm-msg></p>
        <div class="shop-cart-confirm__actions">
          <button type="button" class="shop-btn shop-btn--ghost" data-cart-confirm-close>계속 쇼핑하기</button>
          <a class="shop-btn shop-btn--primary" href="/shop/cart">장바구니로 이동</a>
        </div>
      </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', (e) => {
      if (e.target.closest('[data-cart-confirm-close]')) closeCartConfirm();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !modal.hidden) closeCartConfirm();
    });
  }
  modal.querySelector('[data-cart-confirm-msg]').textContent = '장바구니로 이동해 주문을 계속하시겠어요?';
  modal.hidden = false;
  requestAnimationFrame(() => modal.classList.add('is-open'));
  modal.querySelector('.shop-btn--primary')?.focus();
}

function closeCartConfirm() {
  const modal = document.getElementById('shopCartConfirm');
  if (!modal) return;
  modal.classList.remove('is-open');
  setTimeout(() => { modal.hidden = true; }, 180);
}

function bindCartPage() {
  const refreshSummary = (data) => {
    const fmt = (n) => `${Number(n).toLocaleString()}원`;
    const sub = document.getElementById('cartSubtotal');
    const ship = document.getElementById('cartShipping');
    const total = document.getElementById('cartTotal');
    const disc = document.getElementById('cartDiscount');
    const discRow = document.getElementById('cartDiscountRow');
    if (sub) sub.textContent = data.subtotal_label || fmt(data.subtotal);
    if (ship) ship.textContent = data.shipping_label || (data.shipping_fee === 0 ? '무료' : fmt(data.shipping_fee));
    if (total) total.textContent = data.total_label || fmt(data.total);
    if (disc) disc.textContent = data.discount_label || fmt(data.discount_amount || 0);
    if (discRow) discRow.hidden = !(data.discount_amount > 0);
    updateCartBadges(data.count ?? 0);
  };

  /** 같은 상품이라도 옵션이 다르면 다른 줄이므로 줄 단위로 읽는다. */
  const cartLine = (el) => {
    const row = el.closest('[data-cart-line]');
    if (!row) return null;
    return {
      productId: Number(row.dataset.productId || 0),
      optionId: Number(row.dataset.optionId || 0),
      input: row.querySelector('[data-cart-qty]'),
    };
  };

  document.querySelectorAll('[data-cart-minus]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const line = cartLine(btn);
      if (!line) return;
      const next = Math.max(1, Number(line.input?.value || 1) - 1);
      try {
        await updateCartQty(line.productId, next, line.optionId);
        location.reload();
      } catch (err) {
        showShopToast(err.message);
      }
    });
  });

  document.querySelectorAll('[data-cart-plus]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const line = cartLine(btn);
      if (!line) return;
      const max = Number(line.input?.max || 999);
      const next = Math.min(max, Number(line.input?.value || 1) + 1);
      try {
        await updateCartQty(line.productId, next, line.optionId);
        location.reload();
      } catch (err) {
        showShopToast(err.message);
      }
    });
  });

  document.querySelectorAll('[data-cart-remove]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const line = cartLine(btn);
      if (!line) return;
      if (!confirm('장바구니에서 삭제할까요?')) return;
      try {
        await removeFromCart(line.productId, line.optionId);
        location.reload();
      } catch (err) {
        showShopToast(err.message);
      }
    });
  });

  const checkoutForm = document.getElementById('shopCheckoutForm');
  checkoutForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(checkoutForm);
    const zip = String(fd.get('shipping_zip') || '').trim();
    const base = String(fd.get('shipping_base') || '').trim();
    const detail = String(fd.get('shipping_detail') || '').trim();
    const address = (window.LabelUpAddress && typeof window.LabelUpAddress.combine === 'function')
      ? window.LabelUpAddress.combine(zip, base, detail)
      : [zip, base, detail].filter(Boolean).join(' ');
    const inkOnly = checkoutForm.dataset.inkOnly === '1';
    const shipName = inkOnly
      ? String(fd.get('customer_name') || '').trim()
      : String(fd.get('shipping_name') || '').trim();
    const shipPhone = inkOnly
      ? String(fd.get('customer_phone') || '').trim()
      : String(fd.get('shipping_phone') || '').trim();
    if (!inkOnly && (!shipName || !shipPhone)) {
      showShopToast('수취인 이름과 연락처를 입력해 주세요.');
      return;
    }
    if (!inkOnly && (!zip || !base)) {
      showShopToast('주소 검색으로 배송지를 선택해 주세요.');
      return;
    }
    const submit = checkoutForm.querySelector('button[type="submit"]')
      || document.querySelector('button[type="submit"][form="shopCheckoutForm"]');
    if (submit) submit.disabled = true;
    try {
      const couponInput = document.getElementById('shopCouponInput');
      const res = await ShopAPI.post('/api/shop/checkout', {
        customer_name: String(fd.get('customer_name') || ''),
        customer_email: String(fd.get('customer_email') || ''),
        customer_phone: String(fd.get('customer_phone') || ''),
        shipping_name: shipName,
        shipping_phone: shipPhone,
        shipping_zip: zip,
        shipping_base: base,
        shipping_detail: detail,
        shipping_address: address,
        shipping_memo: String(fd.get('shipping_memo') || ''),
        save_address: fd.get('save_address') === '1',
        address_label: String(fd.get('address_label') || ''),
        coupon_code: String(couponInput?.value || '').trim(),
        source: 'shop',
        mode: CART_MODE,
      });
      const orderNo = res.data?.order_no || '';
      const payment = res.data?.payment;
      if (res.data?.requires_payment && payment && window.LabelUpTossPay) {
        await window.LabelUpTossPay.start(payment);
        return;
      }
      window.location.href = orderNo ? `/shop/complete?order=${encodeURIComponent(orderNo)}` : '/shop/complete';
    } catch (err) {
      showShopToast(err.message);
      if (String(err.message || '').includes('로그인')) {
        window.location.href = `/login?redirect=${encodeURIComponent(location.pathname + location.search)}`;
      }
      if (submit) submit.disabled = false;
    }
  });

  const couponInput = document.getElementById('shopCouponInput');
  const couponBtn = document.getElementById('shopCouponApply');
  couponBtn?.addEventListener('click', async () => {
    const applied = couponBtn.dataset.applied === '1';
    couponBtn.disabled = true;
    try {
      if (applied) {
        await ShopAPI.post('/api/shop/coupon/remove', { mode: CART_MODE });
      } else {
        await ShopAPI.post('/api/shop/coupon/apply', { code: String(couponInput?.value || '').trim(), mode: CART_MODE });
      }
      location.reload();
    } catch (err) {
      showShopToast(err.message);
      couponBtn.disabled = false;
    }
  });
  couponInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      couponBtn?.click();
    }
  });
  const preset = new URLSearchParams(location.search).get('coupon');
  if (preset && couponInput && couponBtn?.dataset.applied !== '1') {
    couponInput.value = preset;
    ShopAPI.post('/api/shop/coupon/apply', { code: preset, mode: CART_MODE })
      .then(() => {
        history.replaceState({}, '', CART_MODE ? `?mode=${CART_MODE}` : location.pathname);
        location.reload();
      })
      .catch((err) => {
        history.replaceState({}, '', CART_MODE ? `?mode=${CART_MODE}` : location.pathname);
        showShopToast(err.message);
      });
  }

  document.getElementById('shopPayRetryBtn')?.addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const orderNo = btn.getAttribute('data-order-no') || '';
    const source = btn.getAttribute('data-source') || 'shop';
    btn.disabled = true;
    try {
      const res = await ShopAPI.post('/api/shop/pay/prepare', { order_no: orderNo, source });
      if (!res.data?.payment || !window.LabelUpTossPay) {
        throw new Error('결제 정보를 불러오지 못했습니다.');
      }
      await window.LabelUpTossPay.start(res.data.payment);
    } catch (err) {
      showShopToast(err.message || '결제 재시도에 실패했습니다.');
      btn.disabled = false;
    }
  });
}

/** 상품 상세 갤러리 — 썸네일을 누르면 큰 이미지를 바꾼다. */
function bindDetailGallery() {
  const main = document.getElementById('shopDetailMainImage');
  const thumbs = Array.from(document.querySelectorAll('.shop-detail-thumb'));
  if (!main || thumbs.length < 2) return;

  const show = (btn) => {
    const src = btn.dataset.gallerySrc;
    if (!src || main.src === src) return;
    main.src = src;
    thumbs.forEach((el) => {
      const on = el === btn;
      el.classList.toggle('is-active', on);
      el.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  };

  thumbs.forEach((btn) => {
    btn.addEventListener('click', () => show(btn));
    // 좌우 방향키로도 넘긴다.
    btn.addEventListener('keydown', (e) => {
      const step = e.key === 'ArrowRight' ? 1 : (e.key === 'ArrowLeft' ? -1 : 0);
      if (!step) return;
      e.preventDefault();
      const next = thumbs[(thumbs.indexOf(btn) + step + thumbs.length) % thumbs.length];
      next.focus();
      show(next);
    });
  });
}

function goLogin() {
  const back = window.location.pathname + window.location.search;
  window.location.href = `/login?redirect=${encodeURIComponent(back)}`;
}

/** 찜하기 — 누를 때마다 담기/빼기가 번갈아 일어난다. */
function bindWishlistButtons() {
  document.querySelectorAll('.js-wishlist-toggle').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const productId = Number(btn.dataset.productId || 0);
      if (!productId || btn.disabled) return;
      btn.disabled = true;
      try {
        const res = await ShopAPI.post('/api/shop/wishlist/toggle', { product_id: productId });
        const on = !!res.data?.wished;
        btn.classList.toggle('is-on', on);
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        // 이미지 위 레이어에서는 아이콘만 보이므로 툴팁도 함께 맞춘다.
        const text = on ? '찜 해제' : '찜하기';
        const label = btn.querySelector('.shop-social-btn__lab');
        if (label) label.textContent = text;
        btn.title = text;
        showShopToast(res.message || (on ? '찜 목록에 담았습니다.' : '찜 목록에서 뺐습니다.'));
      } catch (err) {
        showShopToast(err.message);
        if (err.status === 401) setTimeout(goLogin, 900);
      } finally {
        btn.disabled = false;
      }
    });
  });
}

/** 현재 주소를 클립보드로 복사한다. 보안 컨텍스트가 아니면 execCommand 로 넘어간다. */
async function copyCurrentUrl() {
  const url = window.location.href;
  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(url);
    return;
  }
  const ta = document.createElement('textarea');
  ta.value = url;
  ta.setAttribute('readonly', '');
  ta.style.position = 'fixed';
  ta.style.left = '-9999px';
  document.body.appendChild(ta);
  ta.select();
  const ok = document.execCommand('copy');
  document.body.removeChild(ta);
  if (!ok) throw new Error('주소를 복사하지 못했습니다.');
}

/** 공유하기 — 기기가 공유 시트를 지원하면 그걸 쓰고, 없으면 주소를 복사한다. */
function bindShareButtons() {
  document.querySelectorAll('.js-share-product').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const title = btn.dataset.shareTitle || document.title;
      if (navigator.share) {
        try {
          await navigator.share({ title, url: window.location.href });
          return;
        } catch (err) {
          // 사용자가 공유 시트를 닫은 경우는 알림을 띄우지 않는다.
          if (err?.name === 'AbortError') return;
        }
      }
      try {
        await copyCurrentUrl();
        showShopToast('상품 주소를 복사했습니다.');
      } catch (err) {
        showShopToast(err.message);
      }
    });
  });
}

function bindCategoryToggles() {
  document.querySelectorAll('[data-shop-cat-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', (e) => {
      const panel = toggle.nextElementSibling;
      if (!panel || !panel.classList.contains('shop-cat-children')) return;
      e.preventDefault();
      const open = panel.hidden;
      panel.hidden = !open;
      panel.classList.toggle('is-open', open);
      toggle.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  bindCategoryToggles();
  bindQtyControls();
  bindDetailGallery();
  bindWishlistButtons();
  bindShareButtons();
  bindOptionSelects();
  bindAddCartButtons();
  bindCartPage();
  document.getElementById('shopOrderPrint')?.addEventListener('click', () => window.print());
});
