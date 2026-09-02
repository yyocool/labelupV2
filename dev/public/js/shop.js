const ShopAPI = {
  async request(path, options = {}) {
    const res = await fetch(path, {
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      credentials: 'same-origin',
      ...options,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '요청 처리 중 오류가 발생했습니다.');
    }
    return data;
  },
  post(path, body) {
    return this.request(path, { method: 'POST', body: JSON.stringify(body) });
  },
};

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

async function addToCart(productId, qty = 1) {
  const res = await ShopAPI.post('/api/shop/cart/add', { product_id: productId, qty });
  updateCartBadges(res.data?.count ?? 0);
  showShopToast(res.message || '장바구니에 담았습니다.');
  return res;
}

async function updateCartQty(productId, qty) {
  const res = await ShopAPI.post('/api/shop/cart/update', { product_id: productId, qty });
  return res;
}

async function removeFromCart(productId) {
  const res = await ShopAPI.post('/api/shop/cart/remove', { product_id: productId });
  return res;
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
      if (qtySel) {
        const input = document.querySelector(qtySel);
        if (input) qty = Number(input.value || 1);
      }
      btn.disabled = true;
      try {
        await addToCart(id, qty);
      } catch (err) {
        showShopToast(err.message);
      } finally {
        btn.disabled = false;
      }
    });
  });
}

function bindCartPage() {
  const refreshSummary = (data) => {
    const fmt = (n) => `${Number(n).toLocaleString()}원`;
    const sub = document.getElementById('cartSubtotal');
    const ship = document.getElementById('cartShipping');
    const total = document.getElementById('cartTotal');
    if (sub) sub.textContent = fmt(data.subtotal);
    if (ship) ship.textContent = data.shipping_fee === 0 ? '무료' : fmt(data.shipping_fee);
    if (total) total.textContent = fmt(data.total);
    updateCartBadges(data.count ?? 0);
  };

  document.querySelectorAll('[data-cart-minus]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = Number(btn.dataset.cartMinus);
      const input = document.querySelector(`[data-cart-qty="${id}"]`);
      const next = Math.max(1, Number(input?.value || 1) - 1);
      try {
        const res = await updateCartQty(id, next);
        if (input) input.value = String(next);
        location.reload();
      } catch (err) {
        showShopToast(err.message);
      }
    });
  });

  document.querySelectorAll('[data-cart-plus]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = Number(btn.dataset.cartPlus);
      const input = document.querySelector(`[data-cart-qty="${id}"]`);
      const max = Number(input?.max || 999);
      const next = Math.min(max, Number(input?.value || 1) + 1);
      try {
        await updateCartQty(id, next);
        location.reload();
      } catch (err) {
        showShopToast(err.message);
      }
    });
  });

  document.querySelectorAll('[data-cart-remove]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      if (!confirm('장바구니에서 삭제할까요?')) return;
      try {
        await removeFromCart(Number(btn.dataset.cartRemove));
        location.reload();
      } catch (err) {
        showShopToast(err.message);
      }
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  bindQtyControls();
  bindAddCartButtons();
  bindCartPage();
});
