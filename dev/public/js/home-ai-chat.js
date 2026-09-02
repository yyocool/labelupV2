(() => {
  const cfg = window.LABELUP_HOME || {};
  const isLoggedIn = !!cfg.isLoggedIn;
  const loginUrl = cfg.loginUrl || '/login';
  const chatApiUrl = cfg.chatApiUrl || '/api/ai/chat';
  const labiIconUrl = cfg.labiIconUrl || '';
  const editorBaseUrl = cfg.editorUrl || '/editor/';

  const panel = document.getElementById('aiPromptPanel');
  const input = document.getElementById('promptInput');
  const send = document.getElementById('sendBtn');
  const attachBtn = document.getElementById('aiAttachBtn');
  const fileInput = document.getElementById('aiFileInput');
  const attachPreview = document.getElementById('aiAttachPreview');
  const chatLog = document.getElementById('aiChatLog');

  if (!panel || !input || !send) return;

  /** @type {{role:'user'|'assistant', content:string|Array}[]} */
  const history = [];
  /** @type {{id:string, kind:'image'|'text'|'file', name:string, dataUrl?:string, text?:string}[]} */
  let pendingAttachments = [];
  let sending = false;
  let lightboxEl = null;

  const TEXT_EXT = /\.(txt|csv|json|md)$/i;

  function redirectLogin() {
    window.location.href = loginUrl;
  }

  function requireLogin() {
    if (isLoggedIn) return true;
    redirectLogin();
    return false;
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

  function renderAttachPreview() {
    if (!attachPreview) return;
    attachPreview.innerHTML = '';
    if (!pendingAttachments.length) {
      attachPreview.hidden = true;
      return;
    }
    attachPreview.hidden = false;
    pendingAttachments.forEach((item) => {
      const chip = document.createElement('div');
      chip.className = 'ai-attach-chip';
      if (item.kind === 'image' && item.dataUrl) {
        const img = document.createElement('img');
        img.src = item.dataUrl;
        img.alt = item.name;
        chip.appendChild(img);
      } else {
        const label = document.createElement('span');
        label.textContent = item.name;
        chip.appendChild(label);
      }
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
      chip.appendChild(remove);
      attachPreview.appendChild(chip);
    });
  }

  function setChatActive(active) {
    panel.classList.toggle('is-chat-active', active);
    if (chatLog) chatLog.hidden = !active;
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

  function ensureLightbox() {
    if (lightboxEl) return lightboxEl;
    const root = document.createElement('div');
    root.className = 'ai-lightbox';
    root.hidden = true;
    root.innerHTML = `
      <div class="ai-lightbox-backdrop" data-ai-lb-close></div>
      <div class="ai-lightbox-dialog" role="dialog" aria-modal="true" aria-label="미리보기">
        <button type="button" class="ai-lightbox-close" data-ai-lb-close aria-label="닫기">×</button>
        <div class="ai-lightbox-figure">
          <img class="ai-lightbox-img" alt="">
        </div>
        <div class="ai-lightbox-meta">
          <div class="ai-lightbox-kicker"></div>
          <h3 class="ai-lightbox-title"></h3>
          <p class="ai-lightbox-desc"></p>
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
    const kicker = root.querySelector('.ai-lightbox-kicker');
    const title = root.querySelector('.ai-lightbox-title');
    const desc = root.querySelector('.ai-lightbox-desc');
    const actions = root.querySelector('.ai-lightbox-actions');

    img.src = opts.image || '';
    img.alt = opts.title || '미리보기';
    kicker.textContent = opts.kicker || '';
    title.textContent = opts.title || '';
    desc.textContent = opts.desc || '';
    desc.hidden = !opts.desc;
    kicker.hidden = !opts.kicker;
    actions.innerHTML = '';

    if (opts.editHref) {
      const edit = document.createElement('a');
      edit.className = 'ai-lightbox-btn ai-lightbox-btn--primary';
      edit.href = opts.editHref;
      edit.textContent = opts.editLabel || '바로 편집';
      actions.appendChild(edit);
    }
    if (opts.primaryHref) {
      const a = document.createElement('a');
      a.className = opts.editHref ? 'ai-lightbox-btn' : 'ai-lightbox-btn ai-lightbox-btn--primary';
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
        editHref: buildEditorUrl(product),
        editLabel: '바로 편집',
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
    const editLink = document.createElement('a');
    editLink.className = 'ai-rec-btn ai-rec-btn--edit';
    editLink.href = buildEditorUrl(product);
    editLink.textContent = '바로 편집';
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

    const openClip = () => {
      openLightbox({
        image: clipart.url,
        kicker: 'AI 클립아트',
        title: clipart.title || '라비가 그린 클립아트',
        desc: '라벨에 바로 활용할 수 있는 일러스트예요. 클릭한 이미지를 확대해 확인하세요.',
      });
    };
    media.addEventListener('click', openClip);

    const body = document.createElement('div');
    body.className = 'ai-clip-body';
    body.innerHTML = `
      <span class="ai-clip-badge">클립아트 생성</span>
      <strong>${escapeHtml(clipart.title || '라비가 그린 클립아트')}</strong>
      <span>라벨용 일러스트를 준비했어요</span>`;
    const enlarge = document.createElement('button');
    enlarge.type = 'button';
    enlarge.className = 'ai-rec-btn ai-rec-btn--solid';
    enlarge.textContent = '확대보기';
    enlarge.addEventListener('click', openClip);
    body.appendChild(enlarge);

    card.appendChild(media);
    card.appendChild(body);
    return card;
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

    item.appendChild(avatar);
    item.appendChild(body);
    chatLog.appendChild(item);
    chatLog.scrollTop = chatLog.scrollHeight;
  }

  function isClipartRequest(text) {
    return /그려|그림|클립아트|일러스트|아이콘|로고|캐릭터|스케치|드로잉/.test(text || '');
  }

  function appendTyping(hint, mode) {
    if (!chatLog) return null;
    const item = document.createElement('article');
    item.className = `ai-chat-msg ai-chat-msg--assistant ai-chat-msg--typing${
      mode === 'draw' ? ' ai-chat-msg--drawing' : ''
    }`;
    item.appendChild(createAvatar('assistant'));
    const body = document.createElement('div');
    body.className = 'ai-chat-body';

    if (mode === 'draw') {
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
            <span class="ai-draw-badge">AI DRAWING</span>
            <strong class="ai-draw-title">라비가 클립아트를 그리고 있어요</strong>
            <p class="ai-draw-status">${escapeHtml(hint || '스케치를 시작하는 중…')}</p>
            <div class="ai-draw-bar"><span></span></div>
          </div>
        </div>`;
      const statusEl = body.querySelector('.ai-draw-status');
      const lines = [
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
    if (!requireLogin()) return;

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
  }

  function typingHintForText(text) {
    if (isClipartRequest(text)) {
      return '스케치를 시작하는 중…';
    }
    if (/추천|라벨|스티커|용지|규격|주소|바코드|가격표|네임/.test(text)) {
      return '맞는 라벨 상품을 찾고 있어요…';
    }
    return '라비가 생각 중이에요…';
  }

  async function sendMessage() {
    if (!requireLogin() || sending) return;

    const text = input.value.trim();
    const attachments = pendingAttachments.slice();
    if (!text && !attachments.length) return;

    const content = buildUserContent(text, attachments);
    if (!content) return;

    const displayText = text || '첨부 파일을 확인해 주세요.';
    appendMessage('user', displayText, { attachments });
    history.push({ role: 'user', content });

    input.value = '';
    pendingAttachments = [];
    renderAttachPreview();
    syncComposer();

    sending = true;
    send.disabled = true;
    const drawMode = isClipartRequest(displayText);
    const typing = appendTyping(
      typingHintForText(displayText),
      drawMode ? 'draw' : 'chat'
    );

    try {
      const res = await fetch(chatApiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ messages: buildApiMessages() }),
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
      });
      history.push({ role: 'assistant', content: reply });
    } catch (err) {
      removeTyping(typing);
      appendMessage('assistant', err.message || '오류가 발생했습니다. 잠시 후 다시 시도해 주세요.');
    } finally {
      sending = false;
      syncComposer();
    }
  }

  input.addEventListener('focus', (e) => {
    if (!isLoggedIn) {
      e.preventDefault();
      input.blur();
      redirectLogin();
    }
  });

  input.addEventListener('click', () => {
    if (!isLoggedIn) redirectLogin();
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
    if (!requireLogin()) return;
    const items = Array.from(e.clipboardData?.items || []);
    const imageItems = items.filter((item) => item.type.startsWith('image/'));
    if (!imageItems.length) return;
    e.preventDefault();
    const files = imageItems
      .map((item) => item.getAsFile())
      .filter(Boolean);
    if (files.length) await addFiles(files);
  });

  send.addEventListener('click', () => {
    if (!requireLogin()) return;
    sendMessage();
  });

  attachBtn?.addEventListener('click', () => {
    if (!requireLogin()) return;
    fileInput?.click();
  });

  fileInput?.addEventListener('change', async () => {
    const files = Array.from(fileInput.files || []);
    fileInput.value = '';
    if (files.length) await addFiles(files);
  });

  document.querySelectorAll('.chip').forEach((chip) => {
    chip.addEventListener('click', () => {
      if (!requireLogin()) return;
      document.querySelectorAll('.chip').forEach((c) => c.classList.remove('active'));
      chip.classList.add('active');
      input.value = chip.dataset.text || '';
      syncComposer();
      input.focus();
    });
  });

  syncComposer();
})();
