window.LABELUP_LABI_SKIP_AUTO = true;

window.labelUpEditor = {
  pageQuery: function () {
    return window.location.search || '';
  },
  measureStage: function (el) {
    if (!el) return { w: 0, h: 0, x: 0, y: 0 };
    var r = el.getBoundingClientRect ? el.getBoundingClientRect() : null;
    var w = Math.round((r && r.width) || el.clientWidth || 0);
    var h = Math.round((r && r.height) || el.clientHeight || 0);
    var x = r ? r.left : 0;
    var y = r ? r.top : 0;
    return { w: w, h: h, x: x, y: y };
  },
  bindCanvasStage: function (el, dotnet) {
    if (!el || el._luStageBound) return;
    el._luStageBound = true;
    var apply = function () {
      var size = window.labelUpEditor.measureStage(el);
      if (dotnet && size.w >= 80 && size.h >= 80) {
        dotnet.invokeMethodAsync('OnStageSize', size.w, size.h, size.x, size.y);
      }
    };
    try {
      new ResizeObserver(function () { apply(); }).observe(el);
    } catch (e) { /* ignore */ }
    window.addEventListener('resize', apply);
    requestAnimationFrame(function () { requestAnimationFrame(apply); });
  },
  takePendingClipart: function () {
    try {
      var raw = sessionStorage.getItem('labelup.pendingClipart');
      if (!raw) return null;
      sessionStorage.removeItem('labelup.pendingClipart');
      var data = JSON.parse(raw);
      if (!data || !data.url) return null;
      return {
        url: String(data.url),
        title: data.title ? String(data.title) : '',
        fit: data.fit ? String(data.fit) : ''
      };
    } catch (e) {
      return null;
    }
  },
  takePendingVendorFile: async function () {
    function fromSession() {
      try {
        var raw = sessionStorage.getItem('labelup.pendingVendorFile');
        if (!raw) return null;
        sessionStorage.removeItem('labelup.pendingVendorFile');
        var data = JSON.parse(raw);
        if (!data || !data.dataUrl) return null;
        return {
          fileName: data.fileName ? String(data.fileName) : 'vendor-import',
          dataUrl: String(data.dataUrl)
        };
      } catch (e) {
        return null;
      }
    }
    try {
      var idbData = await new Promise(function (resolve) {
        var settled = false;
        var timer = 0;
        var finish = function (value) {
          if (settled) return;
          settled = true;
          if (timer) window.clearTimeout(timer);
          resolve(value || null);
        };
        timer = window.setTimeout(function () { finish(null); }, 4000);
        var req = indexedDB.open('labelup', 1);
        req.onerror = function () { finish(null); };
        req.onblocked = function () { finish(null); };
        req.onupgradeneeded = function () {
          if (!req.result.objectStoreNames.contains('pending'))
            req.result.createObjectStore('pending');
        };
        req.onsuccess = function () {
          try {
            var db = req.result;
            if (!db.objectStoreNames.contains('pending')) {
              finish(null);
              return;
            }
            var tx = db.transaction('pending', 'readwrite');
            var get = tx.objectStore('pending').get('vendorFile');
            get.onsuccess = function () {
              var row = get.result || null;
              if (row && row.dataUrl) {
                try { tx.objectStore('pending').delete('vendorFile'); } catch (e) { /* ignore */ }
              }
              finish(row);
            };
            get.onerror = function () { finish(null); };
          } catch (e) {
            finish(null);
          }
        };
      });
      if (idbData && idbData.dataUrl) {
        try { sessionStorage.removeItem('labelup.pendingVendorFile'); } catch (e) { /* ignore */ }
        return {
          fileName: idbData.fileName ? String(idbData.fileName) : 'vendor-import',
          dataUrl: String(idbData.dataUrl)
        };
      }
    } catch (e) { /* ignore */ }
    return fromSession();
  },
  takePendingDocument: function () {
    try {
      var raw = sessionStorage.getItem('labelup.pendingDocument');
      if (!raw) return null;
      sessionStorage.removeItem('labelup.pendingDocument');
      var data = JSON.parse(raw);
      if (!data || !data.document) return null;
      return {
        json: JSON.stringify(data.document),
        title: data.title ? String(data.title) : '',
        projectId: data.projectId ? String(data.projectId) : ''
      };
    } catch (e) {
      return null;
    }
  },
  fetchImageDataUrl: async function (src) {
    if (!src) return '';
    if (String(src).indexOf('data:image') === 0) return String(src);
    var url = String(src);
    if (url.charAt(0) === '/') url = window.location.origin + url;
    var res = await fetch(url, { credentials: 'same-origin' });
    if (!res.ok) return '';
    var blob = await res.blob();
    if (!blob || blob.size < 8) return '';
    return await new Promise(function (resolve, reject) {
      var reader = new FileReader();
      reader.onload = function () { resolve(String(reader.result || '')); };
      reader.onerror = function () { reject(reader.error || new Error('read failed')); };
      reader.readAsDataURL(blob);
    });
  },
  downloadBase64: function (base64, fileName, mime) {
    try {
      var bin = atob(base64);
      var len = bin.length;
      var bytes = new Uint8Array(len);
      for (var i = 0; i < len; i++) bytes[i] = bin.charCodeAt(i);
      var blob = new Blob([bytes], { type: mime || 'application/octet-stream' });
      var url = URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = fileName || 'download';
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    } catch (e) {
      console.error('[LabelUp] downloadBase64', e);
    }
  },
  saveDraft: function (key, json) {
    try { localStorage.setItem(key, json); } catch (e) { console.warn(e); }
  },
  loadDraft: function (key) {
    try { return localStorage.getItem(key); } catch (e) { return null; }
  },
  closeImport: function () {
    var el = document.querySelector('[data-ed-import-overlay]');
    if (!el) return;
    el.classList.remove('is-open');
    el.setAttribute('aria-hidden', 'true');
  },
  saveTextAs: async function (text, suggestedName, mime) {
    var name = suggestedName || 'design.lbu';
    if (window.showSaveFilePicker) {
      try {
        var handle = await window.showSaveFilePicker({
          suggestedName: name,
          types: [{
            description: 'LabelUp 디자인 (*.lbu)',
            accept: { 'application/json': ['.lbu'], 'application/octet-stream': ['.lbu'] }
          }]
        });
        var writable = await handle.createWritable();
        await writable.write(new Blob([text], { type: mime || 'application/json;charset=utf-8' }));
        await writable.close();
        return handle.name || name;
      } catch (e) {
        if (e && e.name === 'AbortError') return '';
        console.warn('[LabelUp] saveTextAs picker', e);
      }
    }
    this.downloadText(text, name, mime);
    return name;
  },
  clickSelector: function (sel) {
    var el = document.querySelector(sel);
    if (el) el.click();
  },
  blockContextMenu: function (selector) {
    var el = document.querySelector(selector);
    if (!el || el.__luCtxBlocked) return;
    el.__luCtxBlocked = true;
    el.addEventListener('contextmenu', function (e) {
      e.preventDefault();
      e.stopPropagation();
    }, true);
  },
  downloadText: function (text, fileName, mime) {
    try {
      var blob = new Blob([text], { type: mime || 'application/octet-stream;charset=utf-8' });
      var url = URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = fileName || 'download.lbu';
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    } catch (e) {
      console.error('[LabelUp] downloadText', e);
    }
  },
  printImage: function (dataUrl, title) {
    var html = '<!doctype html><html><head><title>' + (title || '인쇄') + '</title>'
      + '<style>@page{margin:8mm}html,body{margin:0;background:#fff}img{width:100%;display:block}</style></head><body>'
      + '<img id="p" src="' + dataUrl + '" alt="print" /></body></html>';
    var iframe = document.getElementById('lu-print-frame');
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.id = 'lu-print-frame';
      iframe.setAttribute('aria-hidden', 'true');
      iframe.style.cssText = 'position:fixed;left:-9999px;top:0;width:1px;height:1px;border:0;opacity:0;pointer-events:none;';
      document.body.appendChild(iframe);
    }
    var win = iframe.contentWindow;
    if (!win) {
      console.error('[LabelUp] print iframe unavailable');
      return;
    }
    win.document.open();
    win.document.write(html);
    win.document.close();
    var run = function () {
      try { win.focus(); win.print(); } catch (e) { console.error('[LabelUp] print', e); }
    };
    var img = win.document.getElementById('p');
    if (img && !img.complete) img.onload = run;
    else setTimeout(run, 200);
  },
  openImport: function (tabId) {
    var el = document.querySelector('[data-ed-import-overlay]');
    if (!el) return;
    el.classList.add('is-open');
    el.setAttribute('aria-hidden', 'false');
    if (tabId) {
      var tabBtn = el.querySelector('[data-tut="import-tab-' + tabId + '"]');
      if (tabBtn) requestAnimationFrame(function () { tabBtn.click(); });
    }
  },
  bindVendorDrop: function (selector, dotnet) {
    var el = document.querySelector(selector);
    if (!el || el.__luVendorDrop || !dotnet) return;
    el.__luVendorDrop = true;
    var exts = ['.lbl', '.idf', '.xml', '.dgz', '.dgf', '.fmt', '.fdx', '.zip'];
    var depth = 0;
    function hasFiles(e) {
      var types = e.dataTransfer && e.dataTransfer.types;
      if (!types) return false;
      if (typeof types.contains === 'function') return types.contains('Files');
      return Array.prototype.indexOf.call(types, 'Files') >= 0;
    }
    function isVendor(name) {
      var n = String(name || '').toLowerCase();
      var i = n.lastIndexOf('.');
      return i >= 0 && exts.indexOf(n.slice(i)) >= 0;
    }
    function setHover(on) {
      el.classList.toggle('is-vendor-drop', !!on);
    }
    el.addEventListener('dragenter', function (e) {
      if (!hasFiles(e)) return;
      e.preventDefault();
      depth++;
      setHover(true);
    });
    el.addEventListener('dragover', function (e) {
      if (!hasFiles(e)) return;
      e.preventDefault();
      e.dataTransfer.dropEffect = 'copy';
      setHover(true);
    });
    el.addEventListener('dragleave', function (e) {
      if (!hasFiles(e)) return;
      depth = Math.max(0, depth - 1);
      if (depth === 0) setHover(false);
    });
    el.addEventListener('drop', function (e) {
      if (!hasFiles(e)) return;
      var files = e.dataTransfer && e.dataTransfer.files;
      depth = 0;
      setHover(false);
      if (!files || !files.length) return;
      e.preventDefault();
      e.stopPropagation();
      var file = files[0];
      if (!isVendor(file.name)) {
        dotnet.invokeMethodAsync('OnVendorDropRejected', file.name);
        return;
      }
      if (file.size > 30 * 1024 * 1024) {
        dotnet.invokeMethodAsync('OnVendorDropRejected', file.name);
        return;
      }
      file.arrayBuffer().then(function (buf) {
        return dotnet.invokeMethodAsync('OnVendorFileDropped', file.name, new Uint8Array(buf));
      }).catch(function (err) {
        console.error('[LabelUp] vendor drop', err);
      });
    }, true);
  },
  bindHotkeys: function (dotnet) {
    if (window.__luEditorKeysBound) return;
    window.__luEditorKeysBound = true;
    document.addEventListener('keydown', function (e) {
      var t = e.target;
      if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable)) return;
      var key = (e.key || '').toLowerCase();
      if ((e.ctrlKey || e.metaKey) && key === 'c') {
        e.preventDefault();
        dotnet.invokeMethodAsync('OnEditorCopy');
      } else if ((e.ctrlKey || e.metaKey) && key === 'v') {
        e.preventDefault();
        dotnet.invokeMethodAsync('OnEditorPaste');
      } else if (key === 'delete' || key === 'backspace') {
        e.preventDefault();
        dotnet.invokeMethodAsync('OnEditorDelete');
      }
    });
    document.addEventListener('contextmenu', function (e) {
      var t = e.target;
      if (t && t.closest && t.closest('.canvas-stage, .ed-ctx, .skia-view')) {
        e.preventDefault();
        e.stopPropagation();
      }
    }, true);
  },
  hideErrorUi: function () {
    var el = document.getElementById('blazor-error-ui');
    if (!el) return;
    el.classList.remove('show');
    el.style.display = 'none';
  },

  bindLabi: function (dotnet) {
    this._labiDotNet = dotnet;
  },

  mountLabiChat: async function () {
    var root = document.getElementById('aiPromptPanel');
    if (!root || !window.LabelUpLabiChat) return;
    var self = this;
    var user = null;
    try { user = await this.getAuthUser(); } catch (e) { user = null; }
    window.LabelUpLabiChat.mount({
      rootEl: root,
      embedMode: 'editor',
      chatApiUrl: this.apiUrl('/api/ai/chat'),
      examplePromptsUrl: this.apiUrl('/api/ai/example-prompts?surface=editor'),
      labiIconUrl: '/assets/labi-icon.png',
      isLoggedIn: !!user,
      ensureLogin: async function () {
        var current = null;
        try { current = await self.getAuthUser(); } catch (e) { current = null; }
        if (current) return true;
        return await self.showSaveAuthPrompt();
      },
      onApplyProduct: function (product) {
        if (!self._labiDotNet) return;
        self._labiDotNet.invokeMethodAsync('ApplyLabiProduct', JSON.stringify(product || {}));
      },
      onApplyClipart: function (clipart) {
        if (!self._labiDotNet) return;
        self._labiDotNet.invokeMethodAsync('ApplyLabiClipart', JSON.stringify(clipart || {}));
      },
      onApplyTemplate: function (template) {
        if (!self._labiDotNet) return;
        if (template && template.document) {
          self._labiDotNet.invokeMethodAsync('ApplyLabiDocument', JSON.stringify(template.document));
          return;
        }
        self._labiDotNet.invokeMethodAsync('ApplyLabiTemplate', JSON.stringify(template || {}));
      },
      onApplyVendor: function (file) {
        if (!self._labiDotNet || !file) return;
        self._labiDotNet.invokeMethodAsync('OnVendorFileFromLabi', file.fileName || 'vendor-import', file.dataUrl || '');
      }
    });
  },

  apiGetJson: async function (path) {
    var res = await fetch(this.apiUrl(path), {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    });
    var json = await res.json().catch(function () { return null; });
    if (!res.ok || !json || json.success === false) {
      var err = new Error((json && json.message) || '요청에 실패했습니다.');
      err.status = res.status;
      throw err;
    }
    return JSON.stringify(json.data == null ? {} : json.data);
  },

  apiPostJson: async function (path, body) {
    var res = await fetch(this.apiUrl(path), {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(body || {})
    });
    var json = await res.json().catch(function () { return null; });
    if (!res.ok || !json || json.success === false) {
      var err = new Error((json && json.message) || '요청에 실패했습니다.');
      err.status = res.status;
      throw err;
    }
    return JSON.stringify({
      data: json.data == null ? {} : json.data,
      message: json.message || ''
    });
  },

  apiUrl: function (path) {
    var base = (document.querySelector('base') && document.querySelector('base').href) || (location.origin + '/editor/');
    try {
      return new URL(path, base.replace(/\/editor\/?$/, '/')).toString();
    } catch (e) {
      return location.origin + (path.charAt(0) === '/' ? path : '/' + path);
    }
  },

  getAuthUser: async function () {
    try {
      var res = await fetch(this.apiUrl('/api/auth/me'), {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      });
      if (!res.ok) return false;
      var json = await res.json();
      if (!json || json.success === false) return false;
      return json.data || false;
    } catch (e) {
      console.warn('[LabelUp] getAuthUser', e);
      return false;
    }
  },

  saveWorkspace: async function (payload) {
    var res = await fetch(this.apiUrl('/api/editor/workspace'), {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload || {})
    });
    var json = await res.json().catch(function () { return null; });
    if (!res.ok || !json || json.success === false) {
      throw new Error((json && json.message) || '작업공간 저장에 실패했습니다.');
    }
    return json.data || {};
  },

  listWorkspaces: async function (limit) {
    var url = this.apiUrl('/api/editor/workspaces');
    if (limit) url += (url.indexOf('?') >= 0 ? '&' : '?') + 'limit=' + encodeURIComponent(limit);
    var res = await fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    });
    if (res.status === 401) return { items: [] };
    var json = await res.json().catch(function () { return null; });
    if (!res.ok || !json || json.success === false) return { items: [] };
    return json.data || { items: [] };
  },

  loadWorkspace: async function (id) {
    var url = this.apiUrl('/api/editor/workspace');
    if (id) url += (url.indexOf('?') >= 0 ? '&' : '?') + 'id=' + encodeURIComponent(id);
    var res = await fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    });
    if (res.status === 401) return null;
    var json = await res.json().catch(function () { return null; });
    if (!res.ok || !json || json.success === false) return null;
    return json.data || null;
  },

  getTopBarPinned: function () {
    try {
      var v = localStorage.getItem('labelup.topbar.pinned');
      if (v === null) return true;
      return v === '1';
    } catch (e) {
      return true;
    }
  },
  setTopBarPinned: function (pinned) {
    try { localStorage.setItem('labelup.topbar.pinned', pinned ? '1' : '0'); } catch (e) { /* ignore */ }
  },
  getAutoSave: function () {
    try { return localStorage.getItem('labelup.autosave') === '1'; } catch (e) { return false; }
  },
  setAutoSave: function (on) {
    try { localStorage.setItem('labelup.autosave', on ? '1' : '0'); } catch (e) { /* ignore */ }
  },

  setProjectId: function (id) {
    try {
      var u = new URL(window.location.href);
      if (id) u.searchParams.set('project', String(id));
      else u.searchParams.delete('project');
      window.history.replaceState(null, '', u.toString());
    } catch (e) { /* ignore */ }
  },

  showSaveAuthPrompt: function () {
    var self = this;
    return new Promise(function (resolve) {
      var existing = document.getElementById('lu-save-auth-gate');
      if (existing) existing.remove();

      var root = document.createElement('div');
      root.id = 'lu-save-auth-gate';
      root.className = 'lu-auth-gate';
      root.innerHTML =
        '<div class="lu-auth-gate__backdrop" data-lu-auth-close></div>' +
        '<div class="lu-auth-gate__card" role="dialog" aria-modal="true" aria-labelledby="lu-auth-title">' +
        '  <button type="button" class="lu-auth-gate__close" data-lu-auth-close aria-label="닫기">×</button>' +
        '  <div class="lu-auth-gate__hero" aria-hidden="true">' +
        '    <img src="img/labi-icon.png" alt="" width="72" height="72">' +
        '  </div>' +
        '  <h3 id="lu-auth-title">작업 내역을 안전하게 보관해요</h3>' +
        '  <p class="lu-auth-gate__lead">회원가입 또는 로그인하면 디자인 내용과 툴바·속성창 위치까지 모두 저장할 수 있어요.</p>' +
        '  <ul class="lu-auth-gate__points">' +
        '    <li>편집 중인 라벨 디자인 자동 보관</li>' +
        '    <li>툴바·속성/레이어·미리보기 창 배치 저장</li>' +
        '    <li>다른 기기에서도 이어서 작업</li>' +
        '  </ul>' +
        '  <div class="lu-auth-gate__tabs">' +
        '    <button type="button" class="is-active" data-lu-auth-tab="login">로그인</button>' +
        '    <button type="button" data-lu-auth-tab="register">회원가입</button>' +
        '  </div>' +
        '  <form class="lu-auth-gate__form" data-lu-auth-form="login">' +
        '    <label>이메일<input type="email" name="email" required autocomplete="username" placeholder="you@email.com"></label>' +
        '    <label>비밀번호<input type="password" name="password" required autocomplete="current-password" placeholder="비밀번호"></label>' +
        '    <p class="lu-auth-gate__error" hidden></p>' +
        '    <button type="submit" class="lu-auth-gate__submit">로그인하고 저장하기</button>' +
        '  </form>' +
        '  <form class="lu-auth-gate__form" data-lu-auth-form="register" hidden>' +
        '    <label>이름<input type="text" name="name" required autocomplete="name" placeholder="이름"></label>' +
        '    <label>이메일<input type="email" name="email" required autocomplete="email" placeholder="you@email.com"></label>' +
        '    <label>비밀번호<input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="8자 이상"></label>' +
        '    <p class="lu-auth-gate__error" hidden></p>' +
        '    <button type="submit" class="lu-auth-gate__submit">가입하고 저장 시작하기</button>' +
        '  </form>' +
        '  <p class="lu-auth-gate__foot">지금 닫아도 화면의 작업은 유지됩니다. 저장만 로그인 후 가능해요.</p>' +
        '</div>';
      document.body.appendChild(root);
      requestAnimationFrame(function () { root.classList.add('is-open'); });

      function close(result) {
        root.classList.remove('is-open');
        setTimeout(function () { root.remove(); }, 200);
        resolve(!!result);
      }

      root.addEventListener('click', function (e) {
        if (e.target && e.target.closest && e.target.closest('[data-lu-auth-close]')) close(false);
      });

      root.querySelectorAll('[data-lu-auth-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var tab = btn.getAttribute('data-lu-auth-tab');
          root.querySelectorAll('[data-lu-auth-tab]').forEach(function (b) {
            b.classList.toggle('is-active', b === btn);
          });
          root.querySelectorAll('[data-lu-auth-form]').forEach(function (form) {
            form.hidden = form.getAttribute('data-lu-auth-form') !== tab;
          });
        });
      });

      async function submitForm(form, mode) {
        var err = form.querySelector('.lu-auth-gate__error');
        var submit = form.querySelector('.lu-auth-gate__submit');
        err.hidden = true;
        err.textContent = '';
        submit.disabled = true;
        try {
          var body = {
            email: (form.email && form.email.value || '').trim(),
            password: form.password && form.password.value || '',
            remember: true
          };
          if (mode === 'register') body.name = (form.name && form.name.value || '').trim();
          var res = await fetch(self.apiUrl(mode === 'register' ? '/api/auth/register' : '/api/auth/login'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(body)
          });
          var json = await res.json().catch(function () { return null; });
          if (!res.ok || !json || json.success === false) {
            throw new Error((json && json.message) || '처리에 실패했습니다.');
          }
          close(true);
        } catch (e) {
          err.textContent = e.message || '다시 시도해 주세요.';
          err.hidden = false;
          submit.disabled = false;
        }
      }

      root.querySelectorAll('[data-lu-auth-form]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          submitForm(form, form.getAttribute('data-lu-auth-form'));
        });
      });
    });
  }
};
