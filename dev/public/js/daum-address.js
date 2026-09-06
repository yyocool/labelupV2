(function () {
  const SCRIPT_SRC = 'https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js';
  let scriptPromise = null;

  function loadPostcode() {
    if (window.daum && window.daum.Postcode) return Promise.resolve();
    if (scriptPromise) return scriptPromise;
    scriptPromise = new Promise((resolve, reject) => {
      const done = () => {
        if (window.daum && window.daum.Postcode) resolve();
        else reject(new Error('주소 검색을 불러오지 못했습니다.'));
      };
      const existing = document.querySelector('script[data-daum-postcode]');
      if (existing) {
        if (existing.dataset.ready === '1') return done();
        existing.addEventListener('load', () => { existing.dataset.ready = '1'; done(); });
        existing.addEventListener('error', () => reject(new Error('주소 검색을 불러오지 못했습니다.')));
        return;
      }
      const el = document.createElement('script');
      el.src = SCRIPT_SRC;
      el.async = true;
      el.dataset.daumPostcode = '1';
      el.onload = () => { el.dataset.ready = '1'; done(); };
      el.onerror = () => reject(new Error('주소 검색을 불러오지 못했습니다.'));
      document.head.appendChild(el);
    });
    return scriptPromise;
  }

  function ensureLayer() {
    let layer = document.querySelector('.lu-postcode-layer');
    if (layer) return layer;
    layer = document.createElement('div');
    layer.className = 'lu-postcode-layer';
    layer.hidden = true;
    layer.innerHTML = `
      <div class="lu-postcode-layer__card" role="dialog" aria-label="주소 검색">
        <header class="lu-postcode-layer__head">
          <strong>주소 검색</strong>
          <button type="button" class="lu-postcode-layer__close" aria-label="닫기">×</button>
        </header>
        <div class="lu-postcode-layer__body"></div>
      </div>`;
    document.body.appendChild(layer);
    layer.addEventListener('click', (e) => {
      if (e.target === layer || e.target.closest('.lu-postcode-layer__close')) closeLayer();
    });
    return layer;
  }

  function closeLayer() {
    const layer = document.querySelector('.lu-postcode-layer');
    if (!layer) return;
    layer.hidden = true;
    const body = layer.querySelector('.lu-postcode-layer__body');
    if (body) body.innerHTML = '';
    document.body.classList.remove('lu-postcode-open');
  }

  function formatAddress(data) {
    const road = data.userSelectedType === 'R' ? data.roadAddress : data.jibunAddress;
    const extra = [];
    if (data.bname && /[동로가]$/.test(data.bname)) extra.push(data.bname);
    if (data.buildingName && data.apartment === 'Y') extra.push(data.buildingName);
    const extraText = extra.length ? ` (${extra.join(', ')})` : '';
    return {
      zip: data.zonecode || '',
      base: `${road}${extraText}`.trim(),
    };
  }

  function combine(zip, base, detail) {
    const head = [zip, base].filter(Boolean).join(' ').trim();
    const extra = String(detail || '').trim();
    return extra ? `${head} ${extra}` : head;
  }

  function notify(el) {
    if (!el) return;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  async function openPicker(onSelect) {
    const layer = ensureLayer();
    const body = layer.querySelector('.lu-postcode-layer__body');
    body.innerHTML = '<p class="lu-postcode-layer__loading">주소 검색을 불러오는 중…</p>';
    layer.hidden = false;
    document.body.classList.add('lu-postcode-open');
    await loadPostcode();
    body.innerHTML = '';
    const width = Math.min(window.innerWidth - 24, 480);
    const height = Math.min(window.innerHeight - 120, 520);
    new window.daum.Postcode({
      oncomplete(data) {
        onSelect(formatAddress(data));
        closeLayer();
      },
      onclose() {
        closeLayer();
      },
      width: '100%',
      height: '100%',
    }).embed(body, { q: '', autoClose: true });
    layer.querySelector('.lu-postcode-layer__card').style.width = `${width}px`;
    body.style.height = `${height}px`;
  }

  function bindGroup(root) {
    if (!root || root.dataset.daumBound === '1') return;
    const zip = root.querySelector('[data-addr-zip]');
    const base = root.querySelector('[data-addr-base]');
    const detail = root.querySelector('[data-addr-detail]');
    const combined = root.querySelector('[data-addr-combined]');
    const searchBtns = root.querySelectorAll('[data-addr-search]');
    if (!base && !combined) return;
    root.dataset.daumBound = '1';

    const sync = () => {
      if (!combined) return;
      combined.value = combine(zip?.value || '', base?.value || '', detail?.value || '');
      notify(combined);
    };

    const open = async () => {
      try {
        await openPicker((addr) => {
          if (zip) zip.value = addr.zip;
          if (base) base.value = addr.base;
          sync();
          if (detail) detail.focus();
        });
      } catch (err) {
        window.alert(err.message || '주소 검색을 열 수 없습니다.');
      }
    };

    searchBtns.forEach((btn) => btn.addEventListener('click', (e) => {
      e.preventDefault();
      open();
    }));
    [zip, base].forEach((el) => {
      if (!el) return;
      el.addEventListener('click', open);
      el.addEventListener('focus', () => {
        if (!el.value) open();
      });
    });
    detail?.addEventListener('input', sync);
    sync();
  }

  function enhanceTextarea(label, textarea) {
    if (!label || !textarea || label.dataset.daumReady === '1') return;
    label.dataset.daumReady = '1';
    const wrap = document.createElement('div');
    wrap.className = 'lu-addr';
    wrap.innerHTML = `
      <div class="lu-addr__row">
        <input class="lu-addr__zip" type="text" data-addr-zip readonly maxlength="10" placeholder="우편번호" autocomplete="postal-code">
        <button type="button" class="lu-addr__search" data-addr-search>주소 검색</button>
      </div>
      <input class="lu-addr__base" type="text" data-addr-base readonly maxlength="300" placeholder="주소 검색으로 선택하세요">
      <input class="lu-addr__detail" type="text" data-addr-detail maxlength="200" placeholder="상세주소 (동·호수 등)">`;
    textarea.classList.add('lu-addr__combined');
    textarea.setAttribute('data-addr-combined', '1');
    textarea.setAttribute('readonly', 'readonly');
    textarea.rows = 2;
    textarea.placeholder = '주소 검색으로 입력됩니다';
    label.insertBefore(wrap, textarea);
    bindGroup(label);
  }

  function enhanceEditorCheckout(root) {
    const scope = root instanceof HTMLElement ? root : document;
    const forms = [];
    if (scope.matches?.('.ed-shop-checkout')) forms.push(scope);
    if (scope.querySelectorAll) {
      scope.querySelectorAll('.ed-shop-checkout').forEach((form) => forms.push(form));
    }
    if (!forms.length && document.querySelector('.ed-shop-checkout')) {
      forms.push(document.querySelector('.ed-shop-checkout'));
    }
    forms.forEach((form) => {
      form.querySelectorAll('label').forEach((label) => {
        if (!/배송지/.test(label.textContent || '')) return;
        const ta = label.querySelector('textarea');
        if (ta) enhanceTextarea(label, ta);
      });
    });
  }

  function bindDocument(scope) {
    (scope || document).querySelectorAll('[data-daum-address]').forEach(bindGroup);
    enhanceEditorCheckout(scope || document);
  }

  function start() {
    bindDocument(document);
    const mo = new MutationObserver((records) => {
      for (const rec of records) {
        rec.addedNodes.forEach((node) => {
          if (!(node instanceof HTMLElement)) return;
          if (node.matches?.('[data-daum-address], .ed-shop-checkout')) bindDocument(node);
          else if (node.querySelector?.('[data-daum-address], .ed-shop-checkout, textarea')) bindDocument(node);
        });
      }
    });
    mo.observe(document.documentElement, { childList: true, subtree: true });
    window.setInterval(() => enhanceEditorCheckout(document), 700);
  }

  window.LabelUpAddress = {
    open: openPicker,
    bind: bindGroup,
    bindAll: bindDocument,
    combine,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
