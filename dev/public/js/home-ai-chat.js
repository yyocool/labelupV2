window.LabelUpLabiChat = {
  async mount(options) {
    const cfg = options || window.LABELUP_HOME || {};
    let isLoggedIn = !!cfg.isLoggedIn;
    const loginUrl = cfg.loginUrl || '/login';
    const chatApiUrl = cfg.chatApiUrl || '/api/ai/chat';
    const labiIconUrl = cfg.labiIconUrl || '';
    const editorBaseUrl = cfg.editorUrl || '/editor/';
    const embedMode = cfg.embedMode === 'editor';
    const surface = embedMode ? 'editor' : 'home';
    const root = cfg.rootEl || document.getElementById('aiPromptPanel');

    const panel = root;
    const input = root && (root.querySelector('#promptInput') || root.querySelector('[data-labi-input]'));
    const send = root && (root.querySelector('#sendBtn') || root.querySelector('[data-labi-send]'));
    const attachBtn = root && (root.querySelector('#aiAttachBtn') || root.querySelector('[data-labi-attach]'));
    const fileInput = root && (root.querySelector('#aiFileInput') || root.querySelector('[data-labi-file]'));
    const attachPreview = root && (root.querySelector('#aiAttachPreview') || root.querySelector('[data-labi-attach-preview]'));
    const chatLog = root && (root.querySelector('#aiChatLog') || root.querySelector('[data-labi-log]'));

    if (!panel || !input || !send) return null;
    if (panel.dataset.labiMounted === '1') return panel;
    panel.dataset.labiMounted = '1';
    if (isLoggedIn) input.removeAttribute('readonly');

  /** @type {{role:'user'|'assistant', content:string|Array}[]} */
  const history = [];
  /** @type {{id:string, kind:'image'|'text'|'file', name:string, dataUrl?:string, text?:string}[]} */
  let pendingAttachments = [];
  let sending = false;
  let lightboxEl = null;

  const TEXT_EXT = /\.(txt|json|md)$/i;
  const OFFICE_EXT = /\.(xlsx|xls|csv|tsv|docx|doc)$/i;
  const VENDOR_EXT = /\.(lbl|idf|xml|dgz|dgf|fmt|fdx|zip)$/i;
  const MAX_OFFICE_BYTES = 3_500_000;
  const MAX_VENDOR_BYTES = 15_000_000;

  function redirectLogin() {
    window.location.href = loginUrl;
  }

  async function requireLogin() {
    if (isLoggedIn) return true;
    if (typeof cfg.ensureLogin === 'function') {
      const ok = await cfg.ensureLogin();
      if (ok) {
        isLoggedIn = true;
        if (input) input.removeAttribute('readonly');
        return true;
      }
      return false;
    }
    redirectLogin();
    return false;
  }

  function applyProduct(product) {
    closeLightbox();
    if (embedMode && typeof cfg.onApplyProduct === 'function') {
      cfg.onApplyProduct(product);
      return;
    }
    window.location.href = buildEditorUrl(product);
  }

  function applyClipart(clipart) {
    closeLightbox();
    if (embedMode && typeof cfg.onApplyClipart === 'function') {
      cfg.onApplyClipart(clipart);
      return;
    }
    window.location.href = buildClipartEditorUrl(clipart);
  }

  async function applyTemplate(template) {
    closeLightbox();
    if (embedMode && typeof cfg.onApplyTemplate === 'function') {
      cfg.onApplyTemplate(template);
      return;
    }
    if (template && template.document) {
      const ok = await stashPendingDocument(template);
      if (!ok) {
        appendMessage('assistant', '데이터량이 커서 브라우저에 임시 저장하지 못했어요. 행 수를 줄이거나 CSV로 다시 첨부해 주세요.');
        return;
      }
    }
    window.location.href = buildTemplateEditorUrl(template);
  }

  function guessVendor(name) {
    const n = String(name || '').toLowerCase();
    if (/\.(dgz|dgf|fmt|fdx)$/.test(n)) return { id: 'formtec', title: '폼텍 디자인프로' };
    if (/\.idf$/.test(n)) return { id: 'ilabel', title: '아이라벨' };
    if (/\.lbl$/.test(n)) return { id: 'anylabel', title: '애니라벨' };
    if (/\.xml$/.test(n)) return { id: 'ilabel', title: '아이라벨' };
    if (/\.zip$/.test(n)) return { id: 'auto', title: '타사포맷(압축)' };
    return { id: 'auto', title: '타사포맷' };
  }

  function buildVendorEditorUrl() {
    return `${editorBaseUrl}${editorBaseUrl.includes('?') ? '&' : '?'}vendor=1`;
  }

  function stashPendingVendorFile(fileName, dataUrl) {
    const payload = { fileName: fileName || 'vendor-import', dataUrl: dataUrl || '', ts: Date.now() };
    return new Promise((resolve) => {
      const finishSession = () => {
        try {
          sessionStorage.setItem('labelup.pendingVendorFile', JSON.stringify(payload));
          resolve(true);
        } catch (e) {
          resolve(false);
        }
      };
      try {
        const req = indexedDB.open('labelup', 1);
        req.onerror = finishSession;
        req.onupgradeneeded = () => {
          if (!req.result.objectStoreNames.contains('pending'))
            req.result.createObjectStore('pending');
        };
        req.onsuccess = () => {
          try {
            const db = req.result;
            const tx = db.transaction('pending', 'readwrite');
            tx.objectStore('pending').put(payload, 'vendorFile');
            tx.oncomplete = () => {
              try { sessionStorage.setItem('labelup.pendingVendorFile', JSON.stringify({ fileName: payload.fileName, ts: payload.ts })); } catch (e) { /* ignore */ }
              resolve(true);
            };
            tx.onerror = finishSession;
          } catch (e) {
            finishSession();
          }
        };
      } catch (e) {
        finishSession();
      }
    });
  }

  async function openVendorInEditor(att) {
    const vendor = guessVendor(att && att.name);
    const ok = await stashPendingVendorFile(att.name, att.dataUrl);
    if (!ok) {
      appendMessage('assistant', `「${att.name}」이 커서 브라우저에 임시 저장하지 못했어요. 15MB 이하 파일로 다시 올려 주세요.`);
      return;
    }
    appendMessage('assistant', `「${att.name}」은(는) ${vendor.title} 파일로 보여요. 편집기 타사포맷 변환으로 열게요.`, {
      vendor: { name: att.name, title: vendor.title, href: buildVendorEditorUrl() },
    });
    if (embedMode && typeof cfg.onApplyVendor === 'function') {
      cfg.onApplyVendor({ fileName: att.name, dataUrl: att.dataUrl });
      return;
    }
    window.setTimeout(() => {
      window.location.href = buildVendorEditorUrl();
    }, 700);
  }

  function buildVendorCard(vendor) {
    const card = document.createElement('div');
    card.className = 'ai-vendor-card';
    card.innerHTML = `
      <span class="ai-rec-badge">타사포맷</span>
      <strong>${escapeHtml((vendor && vendor.title) || '타사포맷')}</strong>
      <span>${escapeHtml((vendor && vendor.name) || '')}</span>
      <p>폼텍은 바로 변환되고, 아이라벨·애니라벨도 같은 모듈로 시도합니다.</p>`;
    const actions = document.createElement('div');
    actions.className = 'ai-rec-actions';
    const editLink = document.createElement(embedMode ? 'button' : 'a');
    editLink.type = embedMode ? 'button' : undefined;
    editLink.className = 'ai-rec-btn ai-rec-btn--solid';
    if (!embedMode) editLink.href = (vendor && vendor.href) || buildVendorEditorUrl();
    editLink.textContent = embedMode ? '변환 중' : '편집기에서 열기';
    if (embedMode) editLink.disabled = true;
    bindApply(editLink, () => {
      window.location.href = (vendor && vendor.href) || buildVendorEditorUrl();
    });
    actions.appendChild(editLink);
    card.appendChild(actions);
    return card;
  }

  function bindApply(el, handler) {
    if (!el) return;
    el.addEventListener('click', (e) => {
      e.preventDefault();
      handler();
    });
  }

  function syncComposer() {
    input.style.height = 'auto';
    input.style.height = `${Math.min(input.scrollHeight, 160)}px`;
    const hasText = !!input.value.trim();
    const hasAttach = pendingAttachments.length > 0;
    send.disabled = sending || (!hasText && !hasAttach);
  }

  function uid() {
    return `att-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  }

  function fileFormatBadge(name, kind) {
    const n = String(name || '').toLowerCase();
    const m = n.match(/\.([a-z0-9]{1,8})$/i);
    if (m) return m[1].toUpperCase();
    if (kind === 'image') return 'IMG';
    if (kind === 'vendor') return 'VENDOR';
    if (kind === 'text') return 'TXT';
    return 'FILE';
  }

  function fileFormatTone(ext) {
    const e = String(ext || '').toLowerCase();
    if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'img'].includes(e)) return 'image';
    if (['xlsx', 'xls', 'csv', 'tsv'].includes(e)) return 'sheet';
    if (['docx', 'doc', 'txt', 'md', 'json'].includes(e)) return 'doc';
    if (['lbl', 'idf', 'xml', 'dgz', 'dgf', 'fmt', 'fdx', 'zip'].includes(e)) return 'vendor';
    return 'file';
  }

  function renderAttachPreview() {
    if (!attachPreview) return;
    attachPreview.innerHTML = '';
    if (!pendingAttachments.length) {
      attachPreview.hidden = true;
      return;
    }
    attachPreview.hidden = false;
    pendingAttachments.forEach((item) => {
      const ext = fileFormatBadge(item.name, item.kind);
      const tone = fileFormatTone(ext);
      const chip = document.createElement('div');
      chip.className = `ai-attach-chip ai-attach-chip--${tone}`;
      chip.title = item.name || '';

      const tile = document.createElement('div');
      tile.className = 'ai-attach-tile';
      if (item.kind === 'image' && item.dataUrl) {
        const img = document.createElement('img');
        img.src = item.dataUrl;
        img.alt = item.name || '첨부 이미지';
        tile.appendChild(img);
      } else {
        const glyph = document.createElement('strong');
        glyph.className = 'ai-attach-glyph';
        glyph.textContent = ext.slice(0, 4);
        tile.appendChild(glyph);
      }

      const badge = document.createElement('em');
      badge.className = 'ai-attach-badge';
      badge.textContent = ext;
      tile.appendChild(badge);

      const name = document.createElement('span');
      name.className = 'ai-attach-name';
      name.textContent = item.name || '첨부파일';

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'ai-attach-remove';
      remove.setAttribute('aria-label', '첨부 삭제');
      remove.textContent = '×';
      remove.addEventListener('click', () => {
        pendingAttachments = pendingAttachments.filter((a) => a.id !== item.id);
        renderAttachPreview();
        syncComposer();
      });

      chip.appendChild(tile);
      chip.appendChild(name);
      chip.appendChild(remove);
      attachPreview.appendChild(chip);
    });
  }

  function setChatActive(active) {
    panel.classList.toggle('is-chat-active', active);
    if (chatLog) chatLog.hidden = !active;
  }

  function formatAiKrw(amount) {
    const n = Number(amount || 0);
    if (!(n > 0)) return '0';
    if (n < 1) {
      return String(n.toFixed(4)).replace(/\.?0+$/, '') || '0';
    }
    if (Math.abs(n - Math.round(n)) < 0.005) {
      return Math.round(n).toLocaleString('ko-KR');
    }
    return n.toLocaleString('ko-KR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatMessage(text) {
    return escapeHtml(text).replace(/\n/g, '<br>');
  }

  function buildEditorUrl(product) {
    if (product && product.editor_url) return product.editor_url;
    const params = new URLSearchParams();
    if (product && product.width_mm != null && product.height_mm != null) {
      params.set('w', String(product.width_mm));
      params.set('h', String(product.height_mm));
    }
    if (product && product.labels_per_sheet) params.set('labels', String(product.labels_per_sheet));
    if (product && product.shape) params.set('shape', String(product.shape));
    if (product && product.material) params.set('material', String(product.material));
    if (product && product.name) params.set('name', String(product.name));
    const qs = params.toString();
    return qs ? `${editorBaseUrl}${editorBaseUrl.includes('?') ? '&' : '?'}${qs}` : editorBaseUrl;
  }

  function stashPendingClipart(clipart) {
    try {
      sessionStorage.setItem('labelup.pendingClipart', JSON.stringify({
        url: clipart && clipart.url ? String(clipart.url) : '',
        title: clipart && clipart.title ? String(clipart.title) : '',
        fit: clipart && clipart.fit ? String(clipart.fit) : '',
      }));
    } catch (e) { /* ignore */ }
  }

  function buildClipartEditorUrl(clipart) {
    stashPendingClipart(clipart);
    const params = new URLSearchParams();
    if (clipart && clipart.url) params.set('clipart', String(clipart.url));
    if (clipart && clipart.title) params.set('name', String(clipart.title));
    if (clipart && clipart.fit) params.set('fit', String(clipart.fit));
    const qs = params.toString();
    return qs ? `${editorBaseUrl}${editorBaseUrl.includes('?') ? '&' : '?'}${qs}` : editorBaseUrl;
  }

  function ensureLabiDocUrl(url) {
    const base = String(url || editorBaseUrl || '/editor/');
    try {
      const u = new URL(base, window.location.origin);
      if (!u.searchParams.has('labiDoc') && !u.searchParams.has('labidoc')) {
        u.searchParams.set('labiDoc', '1');
      }
      return u.pathname + u.search + u.hash;
    } catch (e) {
      if (/[?&]labiDoc=/i.test(base)) return base;
      return `${base}${base.includes('?') ? '&' : '?'}labiDoc=1`;
    }
  }

  function stashPendingDocument(template) {
    if (!template || !template.document) return Promise.resolve(false);
    const payload = {
      document: template.document,
      title: template.title || '',
      projectId: template.project_id || 0,
      ts: Date.now(),
    };
    let sessionOk = false;
    try {
      sessionStorage.setItem('labelup.pendingDocument', JSON.stringify(payload));
      sessionOk = true;
    } catch (e) {
      sessionOk = false;
    }
    return new Promise((resolve) => {
      const done = (idbOk) => resolve(sessionOk || idbOk);
      try {
        const req = indexedDB.open('labelup', 1);
        req.onerror = () => done(false);
        req.onupgradeneeded = () => {
          if (!req.result.objectStoreNames.contains('pending'))
            req.result.createObjectStore('pending');
        };
        req.onsuccess = () => {
          try {
            const db = req.result;
            if (!db.objectStoreNames.contains('pending')) {
              done(false);
              return;
            }
            const tx = db.transaction('pending', 'readwrite');
            tx.objectStore('pending').put(payload, 'document');
            tx.oncomplete = () => done(true);
            tx.onerror = () => done(false);
          } catch (e) {
            done(false);
          }
        };
      } catch (e) {
        done(false);
      }
    });
  }

  function buildTemplateEditorUrl(template) {
    if (template && template.document && !template.url) {
      if (template.editor_url) return ensureLabiDocUrl(template.editor_url);
      const projectId = Number(template.project_id || 0);
      if (projectId > 0) {
        return ensureLabiDocUrl(`${editorBaseUrl}${editorBaseUrl.includes('?') ? '&' : '?'}project=${projectId}`);
      }
      return `${editorBaseUrl}${editorBaseUrl.includes('?') ? '&' : '?'}labiDoc=1`;
    }
    stashPendingClipart({
      url: template && template.url,
      title: template && template.title,
      fit: (template && template.fit) || 'cover',
    });
    if (template && template.editor_url) return template.editor_url;
    const params = new URLSearchParams();
    if (template && template.url) params.set('clipart', String(template.url));
    if (template && template.title) params.set('name', String(template.title));
    if (template && template.width_mm != null) params.set('w', String(template.width_mm));
    if (template && template.height_mm != null) params.set('h', String(template.height_mm));
    params.set('fit', (template && template.fit) || 'cover');
    const qs = params.toString();
    return qs ? `${editorBaseUrl}${editorBaseUrl.includes('?') ? '&' : '?'}${qs}` : editorBaseUrl;
  }

  function ensureLightbox() {
    if (lightboxEl) return lightboxEl;
    const existing = document.querySelector('.ai-lightbox');
    if (existing) {
      lightboxEl = existing;
      return existing;
    }
    const root = document.createElement('div');
    root.className = 'ai-lightbox';
    root.hidden = true;
    root.innerHTML = `
      <div class="ai-lightbox-backdrop" data-ai-lb-close></div>
      <div class="ai-lightbox-dialog" role="dialog" aria-modal="true" aria-label="미리보기">
        <button type="button" class="ai-lightbox-close" data-ai-lb-close aria-label="닫기">×</button>
        <div class="ai-lightbox-figure">
          <img class="ai-lightbox-img" alt="">
          <div class="ai-lightbox-html" hidden></div>
        </div>
        <div class="ai-lightbox-meta">
          <div class="ai-lightbox-kicker"></div>
          <h3 class="ai-lightbox-title"></h3>
          <p class="ai-lightbox-desc"></p>
          <div class="ai-lightbox-extra" hidden></div>
          <div class="ai-lightbox-actions"></div>
        </div>
      </div>`;
    document.body.appendChild(root);
    root.addEventListener('click', (e) => {
      const t = e.target;
      if (t && t.closest && t.closest('[data-ai-lb-close]')) {
        closeLightbox();
      }
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && lightboxEl && !lightboxEl.hidden) {
        closeLightbox();
      }
    });
    lightboxEl = root;
    return root;
  }

  function openLightbox(opts) {
    const root = ensureLightbox();
    const img = root.querySelector('.ai-lightbox-img');
    const htmlBox = root.querySelector('.ai-lightbox-html');
    const kicker = root.querySelector('.ai-lightbox-kicker');
    const title = root.querySelector('.ai-lightbox-title');
    const desc = root.querySelector('.ai-lightbox-desc');
    const extra = root.querySelector('.ai-lightbox-extra');
    const actions = root.querySelector('.ai-lightbox-actions');

    const hasHtml = !!(opts.html || opts.svg);
    if (hasHtml) {
      img.hidden = true;
      img.removeAttribute('src');
      htmlBox.hidden = false;
      htmlBox.innerHTML = opts.html || opts.svg || '';
    } else {
      htmlBox.hidden = true;
      htmlBox.innerHTML = '';
      img.hidden = false;
      img.src = opts.image || '';
      img.alt = opts.title || '미리보기';
    }
    kicker.textContent = opts.kicker || '';
    title.textContent = opts.title || '';
    desc.textContent = opts.desc || '';
    desc.hidden = !opts.desc;
    kicker.hidden = !opts.kicker;
    if (extra) {
      if (opts.extraHtml) {
        extra.hidden = false;
        extra.innerHTML = opts.extraHtml;
      } else {
        extra.hidden = true;
        extra.innerHTML = '';
      }
    }
    actions.innerHTML = '';

    if (opts.editHref || opts.onEdit) {
      const edit = document.createElement(opts.onEdit ? 'button' : 'a');
      edit.type = opts.onEdit ? 'button' : undefined;
      edit.className = 'ai-lightbox-btn ai-lightbox-btn--primary';
      if (!opts.onEdit) edit.href = opts.editHref;
      edit.textContent = opts.editLabel || '바로 편집';
      if (opts.onEdit) edit.addEventListener('click', (e) => { e.preventDefault(); opts.onEdit(); });
      actions.appendChild(edit);
    }
    if (opts.primaryHref) {
      const a = document.createElement('a');
      a.className = opts.editHref || opts.onEdit ? 'ai-lightbox-btn' : 'ai-lightbox-btn ai-lightbox-btn--primary';
      a.href = opts.primaryHref;
      a.textContent = opts.primaryLabel || '자세히 보기';
      actions.appendChild(a);
    }
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'ai-lightbox-btn';
    closeBtn.setAttribute('data-ai-lb-close', '');
    closeBtn.textContent = '닫기';
    actions.appendChild(closeBtn);

    root.hidden = false;
    requestAnimationFrame(() => root.classList.add('is-open'));
    document.body.classList.add('ai-lb-open');
  }

  function closeLightbox() {
    if (!lightboxEl) return;
    lightboxEl.classList.remove('is-open');
    document.body.classList.remove('ai-lb-open');
    window.setTimeout(() => {
      if (lightboxEl) lightboxEl.hidden = true;
    }, 180);
  }

  function createAvatar(role) {
    const avatar = document.createElement('div');
    avatar.className = 'ai-chat-avatar';
    if (role === 'user') {
      avatar.textContent = '나';
      return avatar;
    }
    if (labiIconUrl) {
      const img = document.createElement('img');
      img.src = labiIconUrl;
      img.alt = '라비';
      avatar.classList.add('ai-chat-avatar--labi');
      avatar.appendChild(img);
    } else {
      avatar.textContent = '라비';
    }
    return avatar;
  }

  function buildProductCard(product) {
    const card = document.createElement('div');
    card.className = 'ai-rec-card';

    const media = document.createElement('button');
    media.type = 'button';
    media.className = 'ai-rec-media';
    media.setAttribute('aria-label', '상품 미리보기');
    const img = document.createElement('img');
    img.src = product.thumbnail || '';
    img.alt = product.name || '추천 라벨';
    media.appendChild(img);
    media.addEventListener('click', () => {
      openLightbox({
        image: product.thumbnail,
        kicker: '추천 라벨 상품',
        title: product.name,
        desc: [product.category, product.spec, product.price_label].filter(Boolean).join(' · '),
        editHref: embedMode ? '#' : buildEditorUrl(product),
        editLabel: embedMode ? '편집기에 적용' : '바로 편집',
        onEdit: embedMode ? () => applyProduct(product) : null,
        primaryHref: product.url,
        primaryLabel: '상품 상세 보기',
      });
    });

    const body = document.createElement('div');
    body.className = 'ai-rec-body';
    body.innerHTML = `
      <span class="ai-rec-badge">상품 추천</span>
      <strong class="ai-rec-name">${escapeHtml(product.name || '')}</strong>
      <span class="ai-rec-spec">${escapeHtml(product.spec || product.category || '')}</span>
      <div class="ai-rec-price">
        ${product.on_sale ? `<del>${escapeHtml(product.list_price_label || '')}</del>` : ''}
        <em>${escapeHtml(product.price_label || '')}</em>
      </div>`;

    const actions = document.createElement('div');
    actions.className = 'ai-rec-actions';
    const previewBtn = document.createElement('button');
    previewBtn.type = 'button';
    previewBtn.className = 'ai-rec-btn ai-rec-btn--ghost';
    previewBtn.textContent = '미리보기';
    previewBtn.addEventListener('click', () => media.click());
    const editLink = document.createElement(embedMode ? 'button' : 'a');
    editLink.type = embedMode ? 'button' : undefined;
    editLink.className = 'ai-rec-btn ai-rec-btn--edit';
    if (!embedMode) editLink.href = buildEditorUrl(product);
    editLink.textContent = embedMode ? '편집기에 적용' : '바로 편집';
    bindApply(editLink, () => applyProduct(product));
    const detailLink = document.createElement('a');
    detailLink.className = 'ai-rec-btn ai-rec-btn--solid';
    detailLink.href = product.url || '#';
    detailLink.textContent = '상품 보기';
    actions.appendChild(previewBtn);
    actions.appendChild(editLink);
    actions.appendChild(detailLink);
    body.appendChild(actions);

    card.appendChild(media);
    card.appendChild(body);
    return card;
  }

  function buildClipartCard(clipart) {
    const card = document.createElement('div');
    card.className = 'ai-clip-card';

    const media = document.createElement('button');
    media.type = 'button';
    media.className = 'ai-clip-media';
    media.setAttribute('aria-label', '클립아트 확대보기');
    const img = document.createElement('img');
    img.src = clipart.url || '';
    img.alt = clipart.title || '클립아트';
    media.appendChild(img);
    const zoomHint = document.createElement('span');
    zoomHint.className = 'ai-clip-zoom';
    zoomHint.textContent = '확대';
    media.appendChild(zoomHint);

    const editHref = buildClipartEditorUrl(clipart);
    const openClip = () => {
      openLightbox({
        image: clipart.url,
        kicker: 'AI 클립아트',
        title: clipart.title || '라비가 그린 클립아트',
        desc: '라벨에 바로 활용할 수 있는 일러스트예요. 클릭한 이미지를 확대해 확인하세요.',
        editHref: embedMode ? '#' : editHref,
        editLabel: embedMode ? '편집기에 적용' : '바로편집',
        onEdit: embedMode ? () => applyClipart(clipart) : null,
      });
    };
    media.addEventListener('click', openClip);

    const body = document.createElement('div');
    body.className = 'ai-clip-body';
    body.innerHTML = `
      <span class="ai-clip-badge">클립아트 생성</span>
      <strong>${escapeHtml(clipart.title || '라비가 그린 클립아트')}</strong>
      <span>라벨용 일러스트를 준비했어요</span>`;
    const actions = document.createElement('div');
    actions.className = 'ai-rec-actions';
    const enlarge = document.createElement('button');
    enlarge.type = 'button';
    enlarge.className = 'ai-rec-btn ai-rec-btn--ghost';
    enlarge.textContent = '확대보기';
    enlarge.addEventListener('click', openClip);
    const editLink = document.createElement(embedMode ? 'button' : 'a');
    editLink.type = embedMode ? 'button' : undefined;
    editLink.className = 'ai-rec-btn ai-rec-btn--edit';
    if (!embedMode) editLink.href = editHref;
    editLink.textContent = embedMode ? '편집기에 적용' : '바로편집';
    bindApply(editLink, () => applyClipart(clipart));
    actions.appendChild(enlarge);
    actions.appendChild(editLink);
    body.appendChild(actions);

    card.appendChild(media);
    card.appendChild(body);
    return card;
  }

  function buildTemplateCard(template) {
    const dataset = template.dataset || null;
    const card = document.createElement('div');
    card.className = dataset ? 'ai-clip-card ai-tpl-card ai-data-card' : 'ai-clip-card ai-tpl-card';

    const editHref = buildTemplateEditorUrl(template);
    const spec = [template.width_mm, template.height_mm].every((n) => n != null)
      ? `${template.width_mm}×${template.height_mm} mm`
      : '';
    const cols = dataset && Array.isArray(dataset.columns) ? dataset.columns : [];
    const rowCount = dataset ? Number(dataset.row_count || 0) : 0;
    const paperName = (template.paper_name || (dataset && dataset.paper_name) || '').trim();
    const perPage = Number(template.labels_per_page || (dataset && dataset.labels_per_page) || 0);
    const useLabel = (template.use_case_label || (dataset && dataset.use_case_label) || '').trim();
    const paperLine = [
      useLabel ? `${useLabel}용` : '',
      paperName,
      perPage > 1 ? `장당 ${perPage}건` : '',
    ].filter(Boolean).join(' · ') || (spec || '');
    const previewSvg = String(template.preview_svg || '').trim();
    const previewRows = dataset && Array.isArray(dataset.preview_rows) ? dataset.preview_rows : [];

    const media = document.createElement('button');
    media.type = 'button';
    media.className = dataset && !template.url
      ? (previewSvg ? 'ai-data-media ai-data-media--preview' : 'ai-data-media')
      : 'ai-clip-media ai-tpl-media';
    media.setAttribute('aria-label', '라벨 미리보기');
    if (dataset && !template.url) {
      if (previewSvg) {
        media.innerHTML = `<div class="ai-data-preview">${previewSvg}</div><span class="ai-clip-zoom">미리보기</span>`;
      } else {
        media.innerHTML = `
          <strong>${rowCount.toLocaleString('ko-KR')}행</strong>
          <span>${cols.length}개 열</span>`;
      }
    } else {
      const img = document.createElement('img');
      img.src = template.url || '';
      img.alt = template.title || '라벨 템플릿';
      media.appendChild(img);
      const zoomHint = document.createElement('span');
      zoomHint.className = 'ai-clip-zoom';
      zoomHint.textContent = '확대';
      media.appendChild(zoomHint);
    }

    const openDataPreview = () => {
      const tableHtml = previewRows.length
        ? `<div class="ai-data-preview-table"><table><thead><tr>${cols.slice(0, 5).map((c) => `<th>${escapeHtml(c)}</th>`).join('')}</tr></thead><tbody>${
          previewRows.slice(0, 4).map((row) => `<tr>${(row || []).slice(0, 5).map((c) => `<td>${escapeHtml(String(c ?? ''))}</td>`).join('')}</tr>`).join('')
        }</tbody></table><p>${rowCount.toLocaleString('ko-KR')}행 중 미리보기 · 편집기에서 전체 자료를 확인할 수 있어요.</p></div>`
        : '';
      openLightbox({
        svg: previewSvg
          ? `<div class="ai-data-preview ai-data-preview--lg">${previewSvg}</div>`
          : '',
        html: !previewSvg && tableHtml ? tableHtml : undefined,
        kicker: useLabel ? `${useLabel} 라벨 미리보기` : '데이터 라벨 미리보기',
        title: template.title || '라비가 만든 데이터 템플릿',
        desc: paperLine
          ? `${paperLine}. 샘플 1건으로 배치한 미리보기예요. 확인 후 바로편집으로 이어가세요.`
          : '샘플 데이터로 배치한 미리보기예요. 확인 후 바로편집으로 이어가세요.',
        extraHtml: previewSvg ? tableHtml : '',
        editHref: embedMode ? '#' : editHref,
        editLabel: embedMode ? '편집기에 적용' : '바로편집',
        onEdit: () => applyTemplate(template),
      });
    };

    const openTpl = () => {
      if (dataset && !template.url) {
        openDataPreview();
        return;
      }
      openLightbox({
        image: template.url,
        kicker: 'AI 라벨 템플릿',
        title: template.title || '라비가 만든 라벨 템플릿',
        desc: spec
          ? `${spec} 규격으로 편집기를 열어 바로 다듬을 수 있어요.`
          : '완성된 라벨 디자인이에요. 바로편집으로 이어가 보세요.',
        editHref: embedMode ? '#' : editHref,
        editLabel: embedMode ? '편집기에 적용' : '바로편집',
        onEdit: () => applyTemplate(template),
      });
    };
    media.addEventListener('click', openTpl);

    const body = document.createElement('div');
    body.className = 'ai-clip-body';
    const chips = cols.slice(0, 6).map((c) => `<em>${escapeHtml(c)}</em>`).join('');
    const extra = cols.length > 6 ? `<em>+${cols.length - 6}</em>` : '';
    body.innerHTML = `
      <span class="ai-clip-badge">${dataset ? '데이터 템플릿' : '템플릿 생성'}</span>
      <strong>${escapeHtml(template.title || '라비가 만든 라벨 템플릿')}</strong>
      <span>${escapeHtml(dataset
        ? `${paperLine ? `${paperLine} · ` : ''}${rowCount}행 · 미리보기 후 편집`
        : (paperLine ? `${paperLine} 라벨 템플릿을 준비했어요` : (spec ? `${spec} 라벨 템플릿을 준비했어요` : '편집기에서 바로 이어갈 수 있어요')))}</span>
      ${dataset ? `<div class="ai-data-cols">${chips}${extra}</div>` : ''}`;
    const actions = document.createElement('div');
    actions.className = 'ai-rec-actions';
    const enlarge = document.createElement('button');
    enlarge.type = 'button';
    enlarge.className = 'ai-rec-btn ai-rec-btn--ghost';
    enlarge.textContent = dataset && !template.url ? '미리보기' : '확대보기';
    enlarge.addEventListener('click', openTpl);
    actions.appendChild(enlarge);
    const editLink = document.createElement(embedMode ? 'button' : 'a');
    editLink.type = embedMode ? 'button' : undefined;
    editLink.className = 'ai-rec-btn ai-rec-btn--edit';
    if (!embedMode) editLink.href = editHref;
    editLink.textContent = embedMode ? '편집기에 적용' : '바로편집';
    bindApply(editLink, () => applyTemplate(template));
    actions.appendChild(editLink);
    body.appendChild(actions);

    card.appendChild(media);
    card.appendChild(body);
    return card;
  }

  function defaultImageChoices() {
    return [
      { id: 'generate_clipart', title: '클립아트 그리기', desc: '라벨에 올릴 일러스트만 그려 드려요' },
      { id: 'generate_template', title: '템플릿 만들기', desc: '완성된 라벨 디자인으로 편집기를 열어 드려요' },
    ];
  }

  function buildChoiceCard(choices, onChoice) {
    const list = Array.isArray(choices) && choices.length ? choices : defaultImageChoices();
    const wrap = document.createElement('div');
    wrap.className = 'ai-choice-row';
    list.forEach((choice) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = `ai-choice-btn${choice.id === 'generate_template' ? ' ai-choice-btn--tpl' : ''}`;
      btn.innerHTML = `
        <strong>${escapeHtml(choice.title || '')}</strong>
        <span>${escapeHtml(choice.desc || '')}</span>`;
      btn.addEventListener('click', () => {
        if (wrap.dataset.locked === '1') return;
        wrap.dataset.locked = '1';
        wrap.querySelectorAll('button').forEach((el) => {
          el.disabled = true;
          el.classList.toggle('is-picked', el === btn);
        });
        if (typeof onChoice === 'function') onChoice(choice.id);
        else chooseImageMode(choice.id);
      });
      wrap.appendChild(btn);
    });
    return wrap;
  }

  function appendMessage(role, content, extras = {}) {
    if (!chatLog) return;
    setChatActive(true);

    const item = document.createElement('article');
    item.className = `ai-chat-msg ai-chat-msg--${role}`;

    const avatar = createAvatar(role);
    const body = document.createElement('div');
    body.className = 'ai-chat-body';

    const attachments = extras.attachments || [];
    if (attachments.length) {
      const media = document.createElement('div');
      media.className = 'ai-chat-media';
      attachments.forEach((att) => {
        if (att.kind === 'image' && att.dataUrl) {
          const img = document.createElement('img');
          img.src = att.dataUrl;
          img.alt = att.name;
          media.appendChild(img);
        } else if (att.name) {
          const tag = document.createElement('span');
          tag.className = 'ai-chat-file-tag';
          tag.textContent = att.name;
          media.appendChild(tag);
        }
      });
      body.appendChild(media);
    }

    const bubble = document.createElement('div');
    bubble.className = 'ai-chat-bubble';
    bubble.innerHTML = formatMessage(content);
    body.appendChild(bubble);

    if (extras.product) {
      body.appendChild(buildProductCard(extras.product));
    }
    if (extras.clipart) {
      body.appendChild(buildClipartCard(extras.clipart));
    }
    if (extras.template) {
      body.appendChild(buildTemplateCard(extras.template));
    }
    if (extras.choices) {
      body.appendChild(buildChoiceCard(extras.choices, extras.onChoice));
    }
    if (extras.vendor) {
      body.appendChild(buildVendorCard(extras.vendor));
    }
    if (extras.usage) {
      const meta = buildUsageMeta(extras.usage);
      if (meta) body.appendChild(meta);
    }

    item.appendChild(avatar);
    item.appendChild(body);
    chatLog.appendChild(item);
    chatLog.scrollTop = chatLog.scrollHeight;
  }

  function buildUsageMeta(usage) {
    if (!usage || typeof usage !== 'object') return null;
    const tokens = Number(usage.total_tokens || 0);
    const krw = Number(usage.krw || 0);
    const agent = String(usage.agent_label || usage.agent || '').trim();
    const model = String(usage.model || (Array.isArray(usage.models) ? usage.models[0] : '') || '').trim();
    const images = Number(usage.image_count || 0);
    const diff = String(usage.difficulty_label || '').trim();
    if (tokens <= 0 && images <= 0 && krw <= 0 && !agent && !model) return null;

    const el = document.createElement('div');
    el.className = 'ai-chat-meta';
    el.setAttribute('title', usage.currency_note || '추정 비용');

    const bits = [];
    if (agent) bits.push(`<span>${escapeHtml(agent)}</span>`);
    if (model) bits.push(`<span>${escapeHtml(model)}</span>`);
    if (diff) bits.push(`<span>${escapeHtml(diff)}</span>`);
    if (tokens > 0) bits.push(`<span>토큰 ${tokens.toLocaleString('ko-KR')}</span>`);
    if (images > 0) bits.push(`<span>이미지 ${images}</span>`);
    bits.push(`<span class="ai-chat-meta__cost">약 ${formatAiKrw(krw)}원</span>`);

    el.innerHTML = `
      <span class="ai-chat-meta__head">AI 사용</span>
      <span class="ai-chat-meta__sep" aria-hidden="true">·</span>
      ${bits.join('<span class="ai-chat-meta__sep" aria-hidden="true">·</span>')}
    `;
    return el;
  }

  function isClipartRequest(text) {
    return /그려|그림|클립아트|일러스트|아이콘|로고|캐릭터|스케치|드로잉/.test(text || '');
  }

  function isTemplateRequest(text) {
    return /템플릿|라벨\s*디자인|전체\s*라벨|라벨지\s*만들|디자인으로\s*만들/.test(text || '');
  }

  function attachmentsHaveImage(items) {
    return (items || []).some((att) => att && att.kind === 'image' && att.dataUrl);
  }

  function attachmentsHaveOffice(items) {
    return (items || []).some((att) => att && att.kind === 'file' && att.dataUrl && OFFICE_EXT.test(att.name || ''));
  }

  function compactOfficeHistory() {
    history.forEach((msg) => {
      if (!Array.isArray(msg.content)) return;
      msg.content = msg.content.map((part) => {
        if (part && part.type === 'file') {
          return { type: 'text', text: `[첨부 데이터: ${part.name || '파일'}]` };
        }
        return part;
      });
      if (msg.content.length === 1 && msg.content[0].type === 'text') {
        msg.content = msg.content[0].text;
      }
    });
  }

  function historyHasImage() {
    return history.some((msg) => {
      if (!Array.isArray(msg.content)) return false;
      return msg.content.some((part) => part && part.type === 'image_url');
    });
  }

  function appendTyping(hint, mode) {
    if (!chatLog) return null;
    const item = document.createElement('article');
    item.className = `ai-chat-msg ai-chat-msg--assistant ai-chat-msg--typing${
      mode === 'draw' || mode === 'template' ? ' ai-chat-msg--drawing' : ''
    }`;
    item.appendChild(createAvatar('assistant'));
    const body = document.createElement('div');
    body.className = 'ai-chat-body';

    if (mode === 'draw' || mode === 'template') {
      const title = mode === 'template' ? '라비가 라벨 템플릿을 만들고 있어요' : '라비가 클립아트를 그리고 있어요';
      const badge = mode === 'template' ? 'AI TEMPLATE' : 'AI DRAWING';
      body.innerHTML = `
        <div class="ai-draw-stage" aria-live="polite">
          <div class="ai-draw-canvas" aria-hidden="true">
            <span class="ai-draw-glow"></span>
            <span class="ai-draw-ring"></span>
            <span class="ai-draw-shape ai-draw-shape--a"></span>
            <span class="ai-draw-shape ai-draw-shape--b"></span>
            <span class="ai-draw-shape ai-draw-shape--c"></span>
            <span class="ai-draw-spark s1"></span>
            <span class="ai-draw-spark s2"></span>
            <span class="ai-draw-spark s3"></span>
            <span class="ai-draw-brush">
              <i></i>
            </span>
            <span class="ai-draw-stroke"></span>
          </div>
          <div class="ai-draw-copy">
            <span class="ai-draw-badge">${badge}</span>
            <strong class="ai-draw-title">${title}</strong>
            <p class="ai-draw-status">${escapeHtml(hint || '스케치를 시작하는 중…')}</p>
            <div class="ai-draw-bar"><span></span></div>
          </div>
        </div>`;
      const statusEl = body.querySelector('.ai-draw-status');
      const lines = mode === 'template'
        ? [
            '첨부 이미지의 구도를 읽는 중…',
            '라벨 규격에 맞춰 배치하는 중…',
            '색감과 여백을 다듬는 중…',
            '인쇄용 템플릿으로 정리하는 중…',
            '거의 다 됐어요, 조금만 기다려 주세요…',
          ]
        : [
            '스케치 선을 잡는 중…',
            '색감과 분위기를 고르는 중…',
            '라벨에 어울리게 다듬는 중…',
            '디테일을 살짝 더하는 중…',
            '거의 다 그렸어요, 조금만 기다려 주세요…',
          ];
      let idx = 0;
      item._statusTimer = window.setInterval(() => {
        idx = (idx + 1) % lines.length;
        if (statusEl) statusEl.textContent = lines[idx];
      }, 2800);
    } else {
      body.innerHTML = `<div class="ai-chat-bubble"><span class="ai-typing"><i></i><i></i><i></i></span>${
        hint ? `<span class="ai-typing-hint">${escapeHtml(hint)}</span>` : ''
      }</div>`;
    }

    item.appendChild(body);
    chatLog.appendChild(item);
    chatLog.scrollTop = chatLog.scrollHeight;
    return item;
  }

  function removeTyping(el) {
    if (!el) return;
    if (el._statusTimer) {
      window.clearInterval(el._statusTimer);
      el._statusTimer = null;
    }
    el.remove();
  }

  function buildUserContent(text, attachments) {
    const parts = [];
    let mergedText = text.trim();

    attachments.forEach((att) => {
      if (att.kind === 'image' && att.dataUrl) {
        parts.push({ type: 'image_url', image_url: { url: att.dataUrl } });
      } else if (att.kind === 'file' && att.dataUrl) {
        parts.push({ type: 'file', name: att.name, file: { name: att.name, url: att.dataUrl } });
      } else if (att.kind === 'text' && att.text) {
        mergedText += (mergedText ? '\n\n' : '') + `[첨부: ${att.name}]\n${att.text}`;
      } else if (att.name) {
        mergedText += (mergedText ? '\n\n' : '') + `[첨부 파일: ${att.name}]`;
      }
    });

    if (mergedText) {
      parts.unshift({ type: 'text', text: mergedText });
    }

    if (parts.length === 0) return '';
    if (parts.length === 1 && parts[0].type === 'text') return parts[0].text;
    return parts;
  }

  function buildApiMessages() {
    return history.map((msg) => ({ role: msg.role, content: msg.content }));
  }

  async function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result || ''));
      reader.onerror = () => reject(new Error('파일을 읽을 수 없습니다.'));
      reader.readAsDataURL(file);
    });
  }

  async function readFileAsText(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result || ''));
      reader.onerror = () => reject(new Error('파일을 읽을 수 없습니다.'));
      reader.readAsText(file, 'utf-8');
    });
  }

  async function addFiles(files) {
    if (!(await requireLogin())) return;

    for (const file of files) {
      if (file.type.startsWith('image/')) {
        const dataUrl = await readFileAsDataUrl(file);
        pendingAttachments.push({
          id: uid(),
          kind: 'image',
          name: file.name,
          dataUrl,
        });
        continue;
      }

      if (VENDOR_EXT.test(file.name)) {
        if (file.size > MAX_VENDOR_BYTES) {
          appendMessage('assistant', `「${file.name}」은(는) 15MB를 넘습니다. 더 작은 파일로 올려 주세요.`);
          continue;
        }
        const dataUrl = await readFileAsDataUrl(file);
        pendingAttachments.push({
          id: uid(),
          kind: 'vendor',
          name: file.name,
          dataUrl,
        });
        continue;
      }

      if (OFFICE_EXT.test(file.name)) {
        if (file.size > MAX_OFFICE_BYTES) {
          appendMessage('assistant', `「${file.name}」은(는) 3MB를 넘습니다. 더 작은 파일로 올려 주세요.`);
          continue;
        }
        if (/\.xls$/i.test(file.name) && !/\.xlsx$/i.test(file.name)) {
          appendMessage('assistant', '구버전 Excel(.xls)은 .xlsx 또는 .csv로 저장해 다시 첨부해 주세요.');
          continue;
        }
        if (/\.doc$/i.test(file.name) && !/\.docx$/i.test(file.name)) {
          appendMessage('assistant', '구버전 Word(.doc)는 .docx로 저장해 다시 첨부해 주세요.');
          continue;
        }
        const dataUrl = await readFileAsDataUrl(file);
        pendingAttachments.push({
          id: uid(),
          kind: 'file',
          name: file.name,
          dataUrl,
        });
        continue;
      }

      if (TEXT_EXT.test(file.name) || file.type.startsWith('text/') || file.type === 'application/json') {
        const text = await readFileAsText(file);
        const clipped = text.length > 8000 ? text.slice(0, 8000) + '\n...(생략)' : text;
        pendingAttachments.push({
          id: uid(),
          kind: 'text',
          name: file.name,
          text: clipped,
        });
        continue;
      }

      pendingAttachments.push({
        id: uid(),
        kind: 'file',
        name: file.name,
      });
    }

    renderAttachPreview();
    syncComposer();

    const vendors = pendingAttachments.filter((a) => a.kind === 'vendor' && a.dataUrl);
    if (vendors.length) {
      const first = vendors[0];
      pendingAttachments = pendingAttachments.filter((a) => a.id !== first.id);
      renderAttachPreview();
      syncComposer();
      await openVendorInEditor(first);
    }
  }

  function typingHintForText(text) {
    if (/첨부 파일|분석|엑셀|워드|xlsx|docx|csv/.test(text || '')) {
      return '첨부 표를 읽고 데이터 라벨을 구성하는 중…';
    }
    if (isTemplateRequest(text)) {
      return '라벨 템플릿 구성을 잡는 중…';
    }
    if (isClipartRequest(text)) {
      return '스케치를 시작하는 중…';
    }
    if (/추천|라벨|스티커|용지|규격|주소|바코드|가격표|네임/.test(text)) {
      return '맞는 라벨 상품을 찾고 있어요…';
    }
    return '라비가 생각 중이에요…';
  }

  function typingModeFor(text, forceIntent) {
    if (forceIntent === 'generate_data_template') return 'template';
    if (forceIntent === 'generate_template' || isTemplateRequest(text)) return 'template';
    if (forceIntent === 'generate_clipart' || isClipartRequest(text)) return 'draw';
    return 'chat';
  }

  async function requestLabi(forceIntent) {
    if (sending) return;
    sending = true;
    send.disabled = true;
    const lastUser = history.filter((m) => m.role === 'user').slice(-1)[0];
    const lastText = typeof lastUser?.content === 'string'
      ? lastUser.content
      : (Array.isArray(lastUser?.content)
        ? String((lastUser.content.find((p) => p.type === 'text') || {}).text || '')
        : '');
    const mode = typingModeFor(lastText, forceIntent);
    const typing = appendTyping(typingHintForText(lastText), mode);

    try {
      const body = { messages: buildApiMessages(), surface };
      if (forceIntent) body.force_intent = forceIntent;
      const res = await fetch(chatApiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) {
        throw new Error(data.message || 'AI 응답을 받지 못했습니다.');
      }

      const payload = data.data || {};
      const reply = String(payload.reply || '').trim();
      removeTyping(typing);
      appendMessage('assistant', reply || '응답이 비어 있습니다.', {
        product: payload.product || null,
        clipart: payload.clipart || null,
        template: payload.template || null,
        choices: payload.choices || null,
        usage: payload.usage || null,
      });
      history.push({ role: 'assistant', content: reply });
      if (payload.template && payload.template.dataset) {
        compactOfficeHistory();
        void stashPendingDocument(payload.template);
      }
    } catch (err) {
      removeTyping(typing);
      appendMessage('assistant', err.message || '오류가 발생했습니다. 잠시 후 다시 시도해 주세요.');
    } finally {
      sending = false;
      syncComposer();
    }
  }

  async function chooseImageMode(intent) {
    if (!(await requireLogin()) || sending) return;
    const label = intent === 'generate_template' ? '템플릿 만들어 주세요' : '클립아트로 그려 주세요';
    appendMessage('user', label);
    history.push({ role: 'user', content: label });
    await requestLabi(intent);
  }

  async function sendMessage() {
    if (!(await requireLogin()) || sending) return;

    const text = input.value.trim();
    const attachments = pendingAttachments.slice();
    if (!text && !attachments.length) return;

    const content = buildUserContent(text, attachments);
    if (!content) return;

    const displayText = text
      || (attachmentsHaveOffice(attachments) ? '첨부 파일을 분석해 주세요.' : '')
      || (attachmentsHaveImage(attachments) ? '이미지를 보냈어요.' : '첨부 파일을 확인해 주세요.');
    appendMessage('user', displayText, { attachments });
    history.push({ role: 'user', content });

    input.value = '';
    pendingAttachments = [];
    renderAttachPreview();
    syncComposer();

    const hasImage = attachmentsHaveImage(attachments) || historyHasImage();
    const explicitClipart = isClipartRequest(displayText);
    const explicitTemplate = isTemplateRequest(displayText);
    const vendorAtt = attachments.find((att) => att && att.kind === 'vendor' && att.dataUrl);
    if (vendorAtt) {
      await openVendorInEditor(vendorAtt);
      return;
    }
    if (attachmentsHaveOffice(attachments) && !explicitClipart) {
      await requestLabi('generate_data_template');
      return;
    }
    if (hasImage && attachmentsHaveImage(attachments) && !explicitClipart && !explicitTemplate) {
      appendMessage('assistant', '첨부하신 이미지를 봤어요. 어떤 걸 만들어 드릴까요?', {
        choices: defaultImageChoices(),
      });
      history.push({
        role: 'assistant',
        content: '첨부하신 이미지를 봤어요. 클립아트를 그릴지, 라벨 템플릿을 만들지 알려 주세요.',
      });
      return;
    }

    await requestLabi(explicitTemplate ? 'generate_template' : (explicitClipart ? 'generate_clipart' : ''));
  }

  input.addEventListener('focus', async (e) => {
    if (isLoggedIn) return;
    e.preventDefault();
    input.blur();
    if (await requireLogin()) input.focus();
  });

  input.addEventListener('click', async () => {
    if (isLoggedIn) return;
    await requireLogin();
  });

  input.addEventListener('input', syncComposer);

  input.addEventListener('keydown', (e) => {
    if (!isLoggedIn) return;
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (!send.disabled) sendMessage();
    }
  });

  input.addEventListener('paste', async (e) => {
    if (!(await requireLogin())) return;
    const items = Array.from(e.clipboardData?.items || []);
    const imageItems = items.filter((item) => item.type.startsWith('image/'));
    if (!imageItems.length) return;
    e.preventDefault();
    const files = imageItems
      .map((item) => item.getAsFile())
      .filter(Boolean);
    if (files.length) await addFiles(files);
  });

  send.addEventListener('click', async () => {
    if (!(await requireLogin())) return;
    sendMessage();
  });

  attachBtn?.addEventListener('click', async () => {
    if (!(await requireLogin())) return;
    fileInput?.click();
  });

  fileInput?.addEventListener('change', async () => {
    const files = Array.from(fileInput.files || []);
    fileInput.value = '';
    if (files.length) await addFiles(files);
  });

  if (fileInput) {
    const accept = fileInput.getAttribute('accept') || '';
    if (!/dgz|lbl|idf/.test(accept)) {
      fileInput.setAttribute('accept', `${accept ? `${accept},` : ''}.lbl,.idf,.xml,.dgz,.dgf,.fmt,.fdx,.zip`);
    }
  }

  (function bindPanelDrop() {
    let depth = 0;
    function hasFiles(e) {
      const types = e.dataTransfer && e.dataTransfer.types;
      if (!types) return false;
      if (typeof types.contains === 'function') return types.contains('Files');
      return Array.prototype.indexOf.call(types, 'Files') >= 0;
    }
    panel.addEventListener('dragenter', (e) => {
      if (!hasFiles(e)) return;
      e.preventDefault();
      depth += 1;
      panel.classList.add('is-vendor-drop');
    });
    panel.addEventListener('dragover', (e) => {
      if (!hasFiles(e)) return;
      e.preventDefault();
      e.dataTransfer.dropEffect = 'copy';
      panel.classList.add('is-vendor-drop');
    });
    panel.addEventListener('dragleave', (e) => {
      if (!hasFiles(e)) return;
      depth = Math.max(0, depth - 1);
      if (depth === 0) panel.classList.remove('is-vendor-drop');
    });
    panel.addEventListener('drop', async (e) => {
      if (!hasFiles(e)) return;
      e.preventDefault();
      depth = 0;
      panel.classList.remove('is-vendor-drop');
      const files = Array.from((e.dataTransfer && e.dataTransfer.files) || []);
      if (files.length) await addFiles(files);
    });
  }());

  function bindExampleChips() {
    root.querySelectorAll('.prompt-actions .chip').forEach((chip) => {
      chip.addEventListener('click', async () => {
        if (!(await requireLogin())) return;
        root.querySelectorAll('.prompt-actions .chip').forEach((c) => c.classList.remove('active'));
        chip.classList.add('active');
        input.value = chip.dataset.text || '';
        syncComposer();
        input.focus();
      });
    });
  }

  function renderExamplePrompts(items) {
    const box = root.querySelector('.prompt-actions');
    if (!box || !Array.isArray(items) || !items.length) return;
    box.innerHTML = items.map((item, i) => {
      const label = String(item.label || item.prompt_text || '예시');
      const text = String(item.prompt_text || '');
      const active = i === 0 ? ' active' : '';
      const esc = (s) => String(s)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;');
      return `<button class="chip${active}" type="button" data-text="${esc(text)}">${esc(label)}</button>`;
    }).join('');
  }

  async function loadExamplePrompts() {
    if (Array.isArray(cfg.examplePrompts) && cfg.examplePrompts.length) {
      renderExamplePrompts(cfg.examplePrompts);
    }
    const url = cfg.examplePromptsUrl || `/api/ai/example-prompts?surface=${encodeURIComponent(surface)}`;
    try {
      const res = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      const data = await res.json().catch(() => ({}));
      const items = data && data.data && Array.isArray(data.data.items) ? data.data.items : [];
      if (items.length) renderExamplePrompts(items);
    } catch (e) { /* keep SSR / fallback chips */ }
  }

  await loadExamplePrompts();
  bindExampleChips();

  syncComposer();
  return panel;
  }
};

(() => {
  const home = document.getElementById('aiPromptPanel');
  if (!home || window.LABELUP_LABI_SKIP_AUTO) return;
  window.LabelUpLabiChat.mount(window.LABELUP_HOME || {});
})();
