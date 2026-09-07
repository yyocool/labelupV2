(function () {
  const API = {
    async request(path, options = {}) {
      const res = await fetch(path, {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', ...(options.headers || {}) },
        ...options,
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) {
        throw new Error(data.message || '요청 처리 중 오류가 발생했습니다.');
      }
      return data;
    },
    list() {
      return this.request('/api/account/addresses');
    },
    save(body) {
      return this.request('/api/account/addresses/save', { method: 'POST', body: JSON.stringify(body) });
    },
    remove(id) {
      return this.request('/api/account/addresses/delete', { method: 'POST', body: JSON.stringify({ id }) });
    },
    setDefault(id) {
      return this.request('/api/account/addresses/default', { method: 'POST', body: JSON.stringify({ id }) });
    },
  };

  const editorState = {
    shipName: '',
    shipPhone: '',
    zip: '',
    base: '',
    detail: '',
    saveAddress: false,
    addressLabel: '',
    sameAsBuyer: false,
  };

  function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[ch]));
  }

  function combine(zip, base, detail) {
    if (window.LabelUpAddress && typeof window.LabelUpAddress.combine === 'function') {
      return window.LabelUpAddress.combine(zip, base, detail);
    }
    return [zip, base, detail].filter(Boolean).join(' ').trim();
  }

  function fillGroup(root, addr) {
    if (!root || !addr) return;
    const zip = root.querySelector('[data-addr-zip]');
    const base = root.querySelector('[data-addr-base]');
    const detail = root.querySelector('[data-addr-detail]');
    const combined = root.querySelector('[data-addr-combined]');
    const name = root.querySelector('[data-ship-name], [name="shipping_name"]');
    const phone = root.querySelector('[data-ship-phone], [name="shipping_phone"]');
    if (zip) zip.value = addr.zip || '';
    if (base) base.value = addr.address_base || '';
    if (detail) detail.value = addr.address_detail || '';
    if (name) name.value = addr.recipient_name || '';
    if (phone) phone.value = addr.recipient_phone || '';
    if (combined) {
      combined.value = addr.address_line || combine(addr.zip, addr.address_base, addr.address_detail);
      combined.dispatchEvent(new Event('input', { bubbles: true }));
      combined.dispatchEvent(new Event('change', { bubbles: true }));
    }
    [zip, base, detail, name, phone].forEach((el) => {
      if (!el) return;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  function ensureLayer() {
    let layer = document.querySelector('.lu-addr-book-layer');
    if (layer) return layer;
    layer = document.createElement('div');
    layer.className = 'lu-addr-book-layer';
    layer.hidden = true;
    layer.innerHTML = `
      <div class="lu-addr-book-layer__card" role="dialog" aria-label="배송지">
        <header class="lu-addr-book-layer__head">
          <strong data-book-title>주소 불러오기</strong>
          <button type="button" class="lu-addr-book-layer__close" aria-label="닫기">×</button>
        </header>
        <div class="lu-addr-book-layer__body" data-book-body></div>
      </div>`;
    document.body.appendChild(layer);
    layer.addEventListener('click', (e) => {
      if (e.target === layer || e.target.closest('.lu-addr-book-layer__close')) closeLayer();
    });
    return layer;
  }

  function closeLayer() {
    const layer = document.querySelector('.lu-addr-book-layer');
    if (!layer) return;
    layer.hidden = true;
    document.body.classList.remove('lu-addr-book-open');
  }

  function renderList(items, onSelect) {
    if (!items.length) {
      return `
        <p class="lu-addr-book-empty">저장된 배송지가 없습니다.</p>
        <button type="button" class="lu-addr-book-add" data-book-new>새 배송지 추가</button>`;
    }
    return `
      <ul class="lu-addr-book-list">
        ${items.map((item) => `
          <li>
            <button type="button" class="lu-addr-book-item" data-book-pick="${item.id}">
              <em>${esc(item.label)}${item.is_default ? ' · 기본' : ''}</em>
              <strong>${esc(item.recipient_name)}</strong>
              <span>${esc(item.recipient_phone)}</span>
              <span>${esc(item.address_line)}</span>
            </button>
          </li>`).join('')}
      </ul>
      <button type="button" class="lu-addr-book-add" data-book-new>새 배송지 추가</button>`;
  }

  function renderForm(title) {
    return `
      <form class="lu-addr-book-form" data-book-form>
        <label>배송지 이름<input name="label" maxlength="40" placeholder="집, 회사 등"></label>
        <label>수취인 이름<input name="recipient_name" required maxlength="80"></label>
        <label>수취인 연락처<input name="recipient_phone" required maxlength="30" placeholder="010-0000-0000"></label>
        <div class="lu-addr" data-daum-address>
          <div class="lu-addr__row">
            <input type="text" name="zip" data-addr-zip readonly required maxlength="10" placeholder="우편번호">
            <button type="button" class="lu-addr__search" data-addr-search>주소 검색</button>
          </div>
          <input type="text" name="address_base" data-addr-base readonly required maxlength="300" placeholder="주소 검색으로 선택하세요">
          <input type="text" name="address_detail" data-addr-detail maxlength="200" placeholder="상세주소 (동·호수 등)">
        </div>
        <label class="lu-check"><input type="checkbox" name="is_default"> 기본 배송지로 저장</label>
        <div class="lu-addr-book-form__actions">
          <button type="button" data-book-back>목록</button>
          <button type="submit">저장하고 적용</button>
        </div>
      </form>`;
  }

  async function openPicker(onSelect) {
    const layer = ensureLayer();
    const body = layer.querySelector('[data-book-body]');
    const title = layer.querySelector('[data-book-title]');
    title.textContent = '주소 불러오기';
    body.innerHTML = '<p class="lu-addr-book-empty">불러오는 중…</p>';
    layer.hidden = false;
    document.body.classList.add('lu-addr-book-open');

    const showList = async () => {
      title.textContent = '주소 불러오기';
      try {
        const res = await API.list();
        const items = res.data?.items || [];
        body.innerHTML = renderList(items, onSelect);
        body.querySelectorAll('[data-book-pick]').forEach((btn) => {
          btn.addEventListener('click', () => {
            const item = items.find((row) => String(row.id) === String(btn.dataset.bookPick));
            if (item) {
              onSelect(item);
              closeLayer();
            }
          });
        });
        body.querySelector('[data-book-new]')?.addEventListener('click', showForm);
      } catch (err) {
        body.innerHTML = `<p class="lu-addr-book-empty">${esc(err.message)}</p>`;
      }
    };

    const showForm = () => {
      title.textContent = '새 배송지 추가';
      body.innerHTML = renderForm();
      const form = body.querySelector('[data-book-form]');
      if (window.LabelUpAddress) window.LabelUpAddress.bind(form.querySelector('[data-daum-address]'));
      form.querySelector('[data-book-back]')?.addEventListener('click', showList);
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        const payload = {
          label: String(fd.get('label') || '').trim(),
          recipient_name: String(fd.get('recipient_name') || '').trim(),
          recipient_phone: String(fd.get('recipient_phone') || '').trim(),
          zip: String(fd.get('zip') || '').trim(),
          address_base: String(fd.get('address_base') || '').trim(),
          address_detail: String(fd.get('address_detail') || '').trim(),
          is_default: fd.get('is_default') === 'on',
        };
        try {
          const res = await API.save(payload);
          const item = res.data?.item;
          if (item) {
            onSelect(item);
            closeLayer();
          }
        } catch (err) {
          window.alert(err.message);
        }
      });
    };

    await showList();
  }

  function bindShopCheckout(form) {
    if (!form || form.dataset.addrBookBound === '1') return;
    form.dataset.addrBookBound = '1';
    const same = form.querySelector('[data-same-as-buyer]');
    const shipName = form.querySelector('[name="shipping_name"]');
    const shipPhone = form.querySelector('[name="shipping_phone"]');
    const buyerName = form.querySelector('[name="customer_name"]');
    const buyerPhone = form.querySelector('[name="customer_phone"]');
    const copyBuyer = () => {
      if (!same?.checked) return;
      if (shipName && buyerName) shipName.value = buyerName.value;
      if (shipPhone && buyerPhone) shipPhone.value = buyerPhone.value;
    };
    same?.addEventListener('change', () => {
      if (shipName) shipName.readOnly = !!same.checked;
      if (shipPhone) shipPhone.readOnly = !!same.checked;
      copyBuyer();
    });
    buyerName?.addEventListener('input', copyBuyer);
    buyerPhone?.addEventListener('input', copyBuyer);
    form.querySelector('[data-addr-book-open]')?.addEventListener('click', () => {
      openPicker((addr) => {
        if (same) {
          same.checked = false;
          if (shipName) shipName.readOnly = false;
          if (shipPhone) shipPhone.readOnly = false;
        }
        fillGroup(form, addr);
      });
    });
    prefillDefault(form);
  }

  function fieldInput(form, re) {
    for (const label of form.querySelectorAll('label')) {
      const text = (label.childNodes[0]?.textContent || label.textContent || '').trim();
      if (re.test(text)) return label.querySelector('input, textarea');
    }
    return null;
  }

  function checkoutPayload(form) {
    const root = form || document.querySelector('.ed-shop-checkout');
    if (!root) {
      return {
        shipping_name: editorState.shipName,
        shipping_phone: editorState.shipPhone,
        shipping_zip: editorState.zip,
        shipping_base: editorState.base,
        shipping_detail: editorState.detail,
        shipping_address: combine(editorState.zip, editorState.base, editorState.detail),
        save_address: editorState.saveAddress,
        address_label: editorState.addressLabel,
      };
    }
    const zip = root.querySelector('[data-addr-zip]')?.value || editorState.zip;
    const base = root.querySelector('[data-addr-base]')?.value || editorState.base;
    const detail = root.querySelector('[data-addr-detail]')?.value || editorState.detail;
    return {
      shipping_name: root.querySelector('[data-ship-name]')?.value || editorState.shipName,
      shipping_phone: root.querySelector('[data-ship-phone]')?.value || editorState.shipPhone,
      shipping_zip: zip,
      shipping_base: base,
      shipping_detail: detail,
      shipping_address: combine(zip, base, detail),
      save_address: !!(root.querySelector('[data-save-address]')?.checked || editorState.saveAddress),
      address_label: root.querySelector('[data-addr-label]')?.value || editorState.addressLabel,
    };
  }

  function rememberEditor(form) {
    const payload = checkoutPayload(form);
    editorState.shipName = payload.shipping_name;
    editorState.shipPhone = payload.shipping_phone;
    editorState.zip = payload.shipping_zip;
    editorState.base = payload.shipping_base;
    editorState.detail = payload.shipping_detail;
    editorState.saveAddress = payload.save_address;
    editorState.addressLabel = payload.address_label;
    editorState.sameAsBuyer = !!form.querySelector('[data-same-as-buyer]')?.checked;
  }

  function enhanceEditorCheckout(scope) {
    const form = (scope instanceof HTMLElement && scope.matches?.('.ed-shop-checkout'))
      ? scope
      : (scope?.querySelector?.('.ed-shop-checkout') || document.querySelector('.ed-shop-checkout'));
    if (!form) return;
    const fields = form.querySelector('.ed-shop-fields');
    if (!fields || fields.querySelector('[data-editor-ship]')) {
      if (fields?.querySelector('[data-editor-ship]')) restoreEditor(form);
      return;
    }

    const buyerHead = document.createElement('div');
    buyerHead.className = 'lu-checkout-block__head';
    buyerHead.innerHTML = '<strong>구매자</strong>';
    fields.insertBefore(buyerHead, fields.firstChild);

    const ship = document.createElement('div');
    ship.className = 'lu-checkout-block lu-checkout-ship';
    ship.dataset.editorShip = '1';
    ship.innerHTML = `
      <div class="lu-checkout-block__head">
        <strong>수취인</strong>
        <label class="lu-check"><input type="checkbox" data-same-as-buyer> 구매자와 동일</label>
      </div>
      <button type="button" class="lu-addr-book-btn" data-addr-book-open>주소 불러오기</button>
      <label>수취인 이름<input data-ship-name maxlength="80" required placeholder="받는 분 이름"></label>
      <label>수취인 연락처<input data-ship-phone maxlength="30" required placeholder="010-0000-0000"></label>`;

    const addrLabel = Array.from(fields.querySelectorAll('label')).find((el) => /배송지/.test(el.textContent || ''));
    if (addrLabel) fields.insertBefore(ship, addrLabel);
    else fields.appendChild(ship);

    const saveWrap = document.createElement('div');
    saveWrap.className = 'lu-checkout-save';
    saveWrap.innerHTML = `
      <label class="lu-check"><input type="checkbox" data-save-address> 이 주소를 배송지에 저장</label>
      <input class="lu-addr-label" type="text" data-addr-label maxlength="40" placeholder="배송지 이름 (집, 회사 등)">`;
    if (addrLabel) addrLabel.after(saveWrap);
    else ship.after(saveWrap);

    const buyerName = fieldInput(form, /^이름/);
    const buyerPhone = fieldInput(form, /^연락처/);
    const same = ship.querySelector('[data-same-as-buyer]');
    const shipName = ship.querySelector('[data-ship-name]');
    const shipPhone = ship.querySelector('[data-ship-phone]');
    const copyBuyer = () => {
      if (!same.checked) return;
      if (buyerName) shipName.value = buyerName.value;
      if (buyerPhone) shipPhone.value = buyerPhone.value;
      rememberEditor(form);
    };
    same.addEventListener('change', () => {
      shipName.readOnly = !!same.checked;
      shipPhone.readOnly = !!same.checked;
      copyBuyer();
    });
    buyerName?.addEventListener('input', copyBuyer);
    buyerPhone?.addEventListener('input', copyBuyer);
    [shipName, shipPhone, saveWrap.querySelector('[data-save-address]'), saveWrap.querySelector('[data-addr-label]')]
      .forEach((el) => el?.addEventListener('input', () => rememberEditor(form)));
    saveWrap.querySelector('[data-save-address]')?.addEventListener('change', () => rememberEditor(form));
    ship.querySelector('[data-addr-book-open]')?.addEventListener('click', () => {
      openPicker((addr) => {
        same.checked = false;
        shipName.readOnly = false;
        shipPhone.readOnly = false;
        fillGroup(form, addr);
        rememberEditor(form);
      });
    });
    restoreEditor(form);
    prefillDefault(form);
    patchEditorApi();
  }

  async function prefillDefault(root) {
    if (!root) return;
    const name = root.querySelector('[data-ship-name], [name="shipping_name"]');
    const base = root.querySelector('[data-addr-base]');
    if ((name && name.value) || (base && base.value)) return;
    try {
      const res = await API.list();
      const items = res.data?.items || [];
      const item = items.find((row) => row.is_default) || items[0];
      if (item) fillGroup(root, item);
    } catch (e) { /* 비로그인·미등록은 무시 */ }
  }

  function restoreEditor(form) {
    const shipName = form.querySelector('[data-ship-name]');
    const shipPhone = form.querySelector('[data-ship-phone]');
    const zip = form.querySelector('[data-addr-zip]');
    const base = form.querySelector('[data-addr-base]');
    const detail = form.querySelector('[data-addr-detail]');
    const save = form.querySelector('[data-save-address]');
    const label = form.querySelector('[data-addr-label]');
    const same = form.querySelector('[data-same-as-buyer]');
    if (shipName && !shipName.value) shipName.value = editorState.shipName;
    if (shipPhone && !shipPhone.value) shipPhone.value = editorState.shipPhone;
    if (zip && editorState.zip) zip.value = editorState.zip;
    if (base && editorState.base) base.value = editorState.base;
    if (detail && editorState.detail) detail.value = editorState.detail;
    if (save) save.checked = editorState.saveAddress;
    if (label && editorState.addressLabel) label.value = editorState.addressLabel;
    if (same) {
      same.checked = editorState.sameAsBuyer;
      if (shipName) shipName.readOnly = editorState.sameAsBuyer;
      if (shipPhone) shipPhone.readOnly = editorState.sameAsBuyer;
    }
    if (zip && base) {
      const combined = form.querySelector('[data-addr-combined]');
      if (combined && !combined.value) {
        combined.value = combine(zip.value, base.value, detail?.value || '');
        combined.dispatchEvent(new Event('input', { bubbles: true }));
      }
    }
  }

  let apiPatched = false;
  function patchEditorApi() {
    if (apiPatched || !window.labelUpEditor || typeof window.labelUpEditor.apiPostJson !== 'function') return;
    apiPatched = true;
    const orig = window.labelUpEditor.apiPostJson.bind(window.labelUpEditor);
    window.labelUpEditor.apiPostJson = async function (path, body) {
      if (String(path).includes('/api/shop/checkout')) {
        const extra = checkoutPayload();
        if (!extra.shipping_name || !extra.shipping_phone || !extra.shipping_address) {
          throw new Error('수취인 이름, 연락처, 배송지를 모두 입력해 주세요.');
        }
        body = Object.assign({}, body || {}, extra);
      }
      return orig(path, body);
    };
  }

  function bindAccountPage() {
    const root = document.getElementById('accountAddressList');
    if (!root) return;
    const empty = document.getElementById('accountAddressEmpty');
    const addBtn = document.querySelector('[data-address-add]');

    const render = (items) => {
      root.innerHTML = items.map((item) => `
        <article class="account-address" data-address-id="${item.id}">
          <span class="account-address-label">${esc(item.label)}${item.is_default ? ' · 기본' : ''}</span>
          <strong>${esc(item.recipient_name)}</strong>
          <p>${esc(item.recipient_phone)}</p>
          <p>${esc(item.address_line)}</p>
          <div class="account-address-actions">
            ${item.is_default ? '' : `<button type="button" class="account-btn account-btn--outline" data-address-default="${item.id}">기본</button>`}
            <button type="button" class="account-btn account-btn--outline" data-address-edit="${item.id}">수정</button>
            <button type="button" class="account-btn account-btn--danger" data-address-del="${item.id}">삭제</button>
          </div>
        </article>`).join('');
      if (empty) empty.hidden = items.length > 0;
    };

    const reload = async () => {
      const res = await API.list();
      render(res.data?.items || []);
    };

    const openEditor = async (item) => {
      const layer = ensureLayer();
      const body = layer.querySelector('[data-book-body]');
      const title = layer.querySelector('[data-book-title]');
      title.textContent = item ? '배송지 수정' : '배송지 추가';
      body.innerHTML = renderForm();
      const form = body.querySelector('[data-book-form]');
      form.querySelector('[type="submit"]').textContent = '저장';
      form.querySelector('[data-book-back]')?.remove();
      if (window.LabelUpAddress) window.LabelUpAddress.bind(form.querySelector('[data-daum-address]'));
      if (item) {
        form.label.value = item.label || '';
        form.recipient_name.value = item.recipient_name || '';
        form.recipient_phone.value = item.recipient_phone || '';
        form.zip.value = item.zip || '';
        form.address_base.value = item.address_base || '';
        form.address_detail.value = item.address_detail || '';
        form.is_default.checked = !!item.is_default;
      }
      layer.hidden = false;
      document.body.classList.add('lu-addr-book-open');
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        try {
          await API.save({
            id: item?.id || 0,
            label: String(fd.get('label') || '').trim(),
            recipient_name: String(fd.get('recipient_name') || '').trim(),
            recipient_phone: String(fd.get('recipient_phone') || '').trim(),
            zip: String(fd.get('zip') || '').trim(),
            address_base: String(fd.get('address_base') || '').trim(),
            address_detail: String(fd.get('address_detail') || '').trim(),
            is_default: fd.get('is_default') === 'on',
          });
          closeLayer();
          await reload();
        } catch (err) {
          window.alert(err.message);
        }
      });
    };

    addBtn?.addEventListener('click', () => openEditor(null));
    root.addEventListener('click', async (e) => {
      const del = e.target.closest('[data-address-del]');
      const edit = e.target.closest('[data-address-edit]');
      const def = e.target.closest('[data-address-default]');
      try {
        if (del) {
          if (!confirm('이 배송지를 삭제할까요?')) return;
          await API.remove(Number(del.dataset.addressDel));
          await reload();
        } else if (def) {
          await API.setDefault(Number(def.dataset.addressDefault));
          await reload();
        } else if (edit) {
          const res = await API.list();
          const item = (res.data?.items || []).find((row) => String(row.id) === String(edit.dataset.addressEdit));
          if (item) openEditor(item);
        }
      } catch (err) {
        window.alert(err.message);
      }
    });
  }

  function start() {
    document.querySelectorAll('#shopCheckoutForm, [data-checkout-address]').forEach(bindShopCheckout);
    enhanceEditorCheckout(document);
    bindAccountPage();
    patchEditorApi();
    const mo = new MutationObserver((records) => {
      for (const rec of records) {
        rec.addedNodes.forEach((node) => {
          if (!(node instanceof HTMLElement)) return;
          if (node.matches?.('.ed-shop-checkout, #shopCheckoutForm')) {
            bindShopCheckout(node.matches('#shopCheckoutForm') ? node : node.querySelector('#shopCheckoutForm'));
            enhanceEditorCheckout(node);
          } else if (node.querySelector?.('.ed-shop-checkout, #shopCheckoutForm')) {
            bindShopCheckout(node.querySelector('#shopCheckoutForm'));
            enhanceEditorCheckout(node);
          }
        });
      }
    });
    mo.observe(document.documentElement, { childList: true, subtree: true });
    window.setInterval(() => {
      enhanceEditorCheckout(document);
      patchEditorApi();
    }, 700);
  }

  window.LabelUpAddressBook = {
    list: () => API.list(),
    save: (body) => API.save(body),
    remove: (id) => API.remove(id),
    setDefault: (id) => API.setDefault(id),
    open: openPicker,
    fill: fillGroup,
    checkoutPayload,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
