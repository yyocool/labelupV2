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
    if (dotnet) this._canvasHost = dotnet;
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
    window.labelUpEditor.bindCanvasGestures(el, dotnet);
  },
  bindCanvasGestures: function (el, dotnet) {
    if (!el || el._luGestures || !dotnet) return;
    el._luGestures = true;
    var pts = new Map();
    var gesture = null;
    var pan = null;
    var spaceDown = false;
    function point(e) { return { x: e.clientX, y: e.clientY }; }
    function dist(a, b) {
      var dx = a.x - b.x, dy = a.y - b.y;
      return Math.sqrt(dx * dx + dy * dy);
    }
    function mid(a, b) { return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 }; }
    function values() { return Array.from(pts.values()); }
    function isTyping(e) {
      var t = e.target;
      if (!t) return false;
      var tag = (t.tagName || '').toLowerCase();
      return tag === 'input' || tag === 'textarea' || tag === 'select' || t.isContentEditable;
    }
    function labelBox() {
      var skia = el.querySelector('.canvas-stage__skia');
      return skia ? skia.getBoundingClientRect() : null;
    }
    function isOnGuide(e) {
      var t = e.target;
      return !!(t && t.closest && t.closest('.ed-guides__h, .ed-guides__v, .ed-guides__corner, .ed-guides__size'));
    }
    function isOutsideLabel(e) {
      var s = labelBox();
      if (!s) return true;
      return e.clientX < s.left || e.clientX > s.right || e.clientY < s.top || e.clientY > s.bottom;
    }
    function wantPan(e) {
      if (e.target && e.target.closest && e.target.closest('.ed-ctx, button, input, textarea, select, a')) return false;
      if (e.button === 1) return true;
      if (spaceDown && (e.button === 0 || e.pointerType === 'touch' || e.pointerType === 'pen')) return true;
      if (e.button !== 0 && e.pointerType !== 'touch' && e.pointerType !== 'pen') return false;
      return isOnGuide(e) || isOutsideLabel(e);
    }
    function applyPan(nextX, nextY) {
      if (!pan) return;
      var panX = pan.panX + (nextX - pan.x);
      var panY = pan.panY + (nextY - pan.y);
      dotnet.invokeMethodAsync('OnGestureZoomPan', pan.zoom, panX, panY);
    }
    function startPan(e) {
      var sx = e.clientX, sy = e.clientY, id = e.pointerId;
      try { el.setPointerCapture(id); } catch (err) { /* ignore */ }
      el.classList.add('is-panning');
      Promise.resolve(dotnet.invokeMethodAsync('GetViewState')).then(function (st) {
        if (!st || st.length < 3) return;
        pan = {
          id: id,
          x: sx,
          y: sy,
          zoom: Number(st[0]) || 1,
          panX: Number(st[1]) || 0,
          panY: Number(st[2]) || 0
        };
      }).catch(function () { /* ignore */ });
    }
    function endPan(e) {
      if (e && pan && pan.id !== e.pointerId) return;
      if (!pan && !el.classList.contains('is-panning')) return;
      pan = null;
      el.classList.remove('is-panning');
      try { if (e) el.releasePointerCapture(e.pointerId); } catch (err) { /* ignore */ }
      dotnet.invokeMethodAsync('OnGestureEnd');
    }
    el.querySelectorAll('.ed-pan-rails').forEach(function (n) { n.remove(); });
    window.addEventListener('keydown', function (e) {
      if (e.code !== 'Space' || e.repeat || isTyping(e)) return;
      spaceDown = true;
      el.classList.add('is-pan-ready');
      e.preventDefault();
    });
    window.addEventListener('keyup', function (e) {
      if (e.code !== 'Space') return;
      spaceDown = false;
      el.classList.remove('is-pan-ready');
    });
    window.addEventListener('blur', function () {
      spaceDown = false;
      el.classList.remove('is-pan-ready');
    });
    el.addEventListener('pointerdown', function (e) {
      if (e.pointerType === 'touch' || e.pointerType === 'pen') {
        pts.set(e.pointerId, point(e));
        if (pts.size === 2) {
          endPan(e);
          var pair = values();
          Promise.resolve(dotnet.invokeMethodAsync('GetViewState')).then(function (st) {
            if (!st || st.length < 3) return;
            gesture = {
              startDist: Math.max(8, dist(pair[0], pair[1])),
              startMid: mid(pair[0], pair[1]),
              zoom: Number(st[0]) || 1,
              panX: Number(st[1]) || 0,
              panY: Number(st[2]) || 0
            };
          }).catch(function () { /* ignore */ });
          try { e.stopImmediatePropagation(); } catch (err) { /* ignore */ }
          return;
        }
      }
      if (gesture || pts.size >= 2) return;
      if (!wantPan(e)) return;
      e.preventDefault();
      try { e.stopImmediatePropagation(); } catch (err) { /* ignore */ }
      startPan(e);
    }, true);
    el.addEventListener('pointermove', function (e) {
      if (pts.has(e.pointerId)) pts.set(e.pointerId, point(e));
      if (pts.size >= 2 && gesture) {
        e.preventDefault();
        try { e.stopImmediatePropagation(); } catch (err) { /* ignore */ }
        var pair = values();
        var d = Math.max(8, dist(pair[0], pair[1]));
        var m = mid(pair[0], pair[1]);
        var zoom = Math.max(0.25, Math.min(8, gesture.zoom * (d / gesture.startDist)));
        var panX = gesture.panX + (m.x - gesture.startMid.x);
        var panY = gesture.panY + (m.y - gesture.startMid.y);
        dotnet.invokeMethodAsync('OnGestureZoomPan', zoom, panX, panY);
        return;
      }
      if (pan && pan.id === e.pointerId) {
        e.preventDefault();
        try { e.stopImmediatePropagation(); } catch (err) { /* ignore */ }
        applyPan(e.clientX, e.clientY);
      }
    }, { capture: true, passive: false });
    function endPointer(e) {
      if (pts.has(e.pointerId)) pts.delete(e.pointerId);
      if (pts.size < 2 && gesture) {
        gesture = null;
        dotnet.invokeMethodAsync('OnGestureEnd');
      }
      endPan(e);
    }
    el.addEventListener('pointerup', endPointer, true);
    el.addEventListener('pointercancel', endPointer, true);
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
  takePendingDocument: async function () {
    function fromSession() {
      try {
        var raw = sessionStorage.getItem('labelup.pendingDocument');
        if (!raw) return null;
        var data = JSON.parse(raw);
        if (!data || !data.document) return null;
        sessionStorage.removeItem('labelup.pendingDocument');
        return {
          json: JSON.stringify(data.document),
          title: data.title ? String(data.title) : '',
          projectId: data.projectId ? String(data.projectId) : ''
        };
      } catch (e) {
        return null;
      }
    }
    function readIdb() {
      return new Promise(function (resolve) {
        var settled = false;
        var timer = 0;
        var finish = function (value) {
          if (settled) return;
          settled = true;
          if (timer) window.clearTimeout(timer);
          resolve(value || null);
        };
        timer = window.setTimeout(function () { finish(null); }, 4000);
        try {
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
              var store = tx.objectStore('pending');
              var get = store.get('document');
              get.onsuccess = function () {
                var row = get.result || null;
                if (row && row.document) {
                  try { store.delete('document'); } catch (e) { /* ignore */ }
                  // Resolve after transaction completes so delete is durable.
                  tx.oncomplete = function () { finish(row); };
                  tx.onerror = function () { finish(row); };
                } else {
                  finish(null);
                }
              };
              get.onerror = function () { finish(null); };
            } catch (e) {
              finish(null);
            }
          };
        } catch (e) {
          finish(null);
        }
      });
    }
    try {
      var idbData = await readIdb();
      if (idbData && idbData.document) {
        try { sessionStorage.removeItem('labelup.pendingDocument'); } catch (e) { /* ignore */ }
        return {
          json: JSON.stringify(idbData.document),
          title: idbData.title ? String(idbData.title) : '',
          projectId: idbData.projectId ? String(idbData.projectId) : ''
        };
      }
    } catch (e) { /* ignore */ }
    return fromSession();
  },
  fetchImageDataUrl: async function (src) {
    if (!src) return '';
    if (String(src).indexOf('data:image') === 0) return String(src);
    var url = String(src);
    if (url.charAt(0) === '/') url = window.location.origin + url;
    var readBlob = async function (res) {
      if (!res || !res.ok) return '';
      var blob = await res.blob();
      if (!blob || blob.size < 8) return '';
      return await new Promise(function (resolve, reject) {
        var reader = new FileReader();
        reader.onload = function () { resolve(String(reader.result || '')); };
        reader.onerror = function () { reject(reader.error || new Error('read failed')); };
        reader.readAsDataURL(blob);
      });
    };
    try {
      var direct = await readBlob(await fetch(url, { credentials: 'omit', mode: 'cors' }));
      if (direct) return direct;
    } catch (e) { /* CORS 등 */ }
    if (/^https?:\/\//i.test(url) && url.indexOf(window.location.origin) !== 0) {
      try {
        var proxied = window.location.origin + '/api/editor/remote-image?url=' + encodeURIComponent(url);
        var viaProxy = await readBlob(await fetch(proxied, { credentials: 'same-origin' }));
        if (viaProxy) return viaProxy;
      } catch (e2) { /* ignore */ }
    }
    return '';
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
  printImages: function (dataUrls, title, pageWmm, pageHmm) {
    var urls = Array.isArray(dataUrls) ? dataUrls.filter(Boolean) : [dataUrls];
    if (urls.length === 0) return;
    var w = Number(pageWmm) > 20 ? Number(pageWmm) : 210;
    var h = Number(pageHmm) > 20 ? Number(pageHmm) : 297;
    var page = w.toFixed(3) + 'mm ' + h.toFixed(3) + 'mm';
    var imgs = urls.map(function (u, i) {
      return '<img class="p" src="' + u + '" alt="print ' + (i + 1) + '" />';
    }).join('');
    // PNG는 이미 용지 전체(여백 포함) 1:1이다. @page 8mm + width:100% 하면
    // 3189처럼 칸이 많은 용지가 줄어들고 아래로 밀린다.
    var html = '<!doctype html><html><head><title>' + (title || '인쇄') + '</title>'
      + '<style>@page{size:' + page + ';margin:0}html,body{margin:0;padding:0;width:' + w.toFixed(3)
      + 'mm;height:' + h.toFixed(3) + 'mm;background:#fff}'
      + 'img.p{width:' + w.toFixed(3) + 'mm;height:' + h.toFixed(3)
      + 'mm;display:block;page-break-after:always;-webkit-print-color-adjust:exact;print-color-adjust:exact}'
      + 'img.p:last-child{page-break-after:auto}</style></head><body>'
      + imgs + '</body></html>';
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
    var pending = win.document.querySelectorAll('img.p');
    var left = pending.length;
    if (left === 0) { setTimeout(run, 200); return; }
    pending.forEach(function (img) {
      if (img.complete) { if (--left === 0) run(); }
      else img.onload = function () { if (--left === 0) run(); };
    });
  },
  printImage: function (dataUrl, title) {
    this.printImages([dataUrl], title);
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
        var msg = (err && (err.message || String(err))) || '파일을 읽지 못했습니다.';
        dotnet.invokeMethodAsync('OnVendorConvertFailed', String(msg));
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
  bindLabiDialogChrome: function () {
    if (this._labiChromeBound) return;
    this._labiChromeBound = true;
    var src = '/assets/labi-icon.png';
    var decorate = function () {
      var card = document.querySelector('.ed-modal__card--labi');
      if (!card) return;
      var head = card.querySelector('.ed-modal__head');
      if (head && !head.querySelector('.ed-modal__head-labi')) {
        var h3 = head.querySelector('h3');
        if (h3) {
          var box = h3.parentElement;
          box.classList.add('ed-modal__head-title');
          if (!box.querySelector('.ed-modal__head-copy')) {
            var copy = document.createElement('div');
            copy.className = 'ed-modal__head-copy';
            while (box.firstChild) copy.appendChild(box.firstChild);
            box.appendChild(copy);
          }
          var img = document.createElement('img');
          img.className = 'ed-modal__head-labi';
          img.src = src;
          img.alt = '라비';
          img.width = 48;
          img.height = 48;
          box.insertBefore(img, box.firstChild);
        }
      }
      var innerHead = card.querySelector('.prompt-head');
      if (innerHead) innerHead.setAttribute('hidden', '');
    };
    var mo = new MutationObserver(decorate);
    mo.observe(document.documentElement, { childList: true, subtree: true });
    decorate();
  },

  hideBoot: function () {
    var el = document.getElementById('editor-boot');
    if (!el) return;
    el.classList.add('is-done');
    el.setAttribute('hidden', '');
    el.setAttribute('aria-busy', 'false');
    try { document.documentElement.classList.add('editor-ready'); } catch (e) { /* ignore */ }
    this.applyMobileChrome();
  },
  applyMobileChrome: function () {
    var on = this.isMobileEditor();
    try {
      document.documentElement.classList.toggle('is-ed-mobile', on);
      document.body.classList.toggle('is-ed-mobile', on);
      if (on) document.documentElement.classList.remove('lu-right-stack');
    } catch (e) { /* ignore */ }
    var root = document.querySelector('[data-ed-root]');
    if (!root) return;
    var wasMobile = root.classList.contains('is-mobile');
    root.classList.toggle('is-mobile', on);
    if (on) {
      root.classList.remove('is-topbar-auto');
      root.classList.add('is-topbar-pinned');
      var dock = root.querySelector('[data-ed-topbar-dock]');
      if (dock) {
        dock.classList.remove('is-auto');
        dock.classList.add('is-pinned');
      }
      this.resetMobilePanelStyles();
      if (!wasMobile) this.closeMobileSheets();
    } else if (wasMobile) {
      this.closeMobileSheets();
    }
    this.ensureMobileChromeButtons();
    this.parkMobileDockButtons();
    this.ensureMobileDrawerExtras();
  },
  closeMobileSheets: function () {
    var root = document.querySelector('[data-ed-root]');
    var preview = document.querySelector('[data-ed-preview-panel]');
    var props = document.querySelector('[data-ed-props-panel]');
    if (preview) preview.classList.remove('is-m-open');
    if (props) props.classList.remove('is-m-open');
    if (root) {
      root.classList.remove('is-preview-open');
      root.classList.remove('is-props-open');
    }
  },
  resetMobilePanelStyles: function () {
    ['[data-ed-preview-panel]', '[data-ed-props-panel]', '[data-ed-float-tools]'].forEach(function (sel) {
      var el = document.querySelector(sel);
      if (!el) return;
      el.style.left = '';
      el.style.top = '';
      el.style.right = '';
      el.style.bottom = '';
      el.style.width = '';
      el.style.height = '';
      el.style.minHeight = '';
      el.style.maxHeight = '';
      el.style.transform = '';
    });
  },
  ensureMobileChromeButtons: function () {
    var root = document.querySelector('[data-ed-root]');
    var proxy = document.querySelector('.ed-m-preview[data-ed-preview-proxy]');
    if (!root || !root.classList.contains('is-mobile')) {
      if (proxy) proxy.remove();
      return;
    }
    var propsBtn = root.querySelector('.ed-m-props');
    if (propsBtn && /속성/.test((propsBtn.textContent || '').trim())) {
      propsBtn.textContent = '레이어';
      propsBtn.setAttribute('title', '레이어');
      propsBtn.setAttribute('aria-label', '레이어 패널');
    }
    if (root.querySelector('.ed-m-preview')) return;
    if (!propsBtn) return;
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ed-chip ed-m-preview';
    btn.setAttribute('data-ed-preview-proxy', '1');
    btn.title = '시트 미리보기';
    btn.setAttribute('aria-label', '시트 미리보기');
    btn.textContent = '시트';
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (window.labelUpEditor && typeof window.labelUpEditor.toggleMobilePreview === 'function')
        window.labelUpEditor.toggleMobilePreview();
    });
    propsBtn.parentNode.insertBefore(btn, propsBtn);
  },
  parkMobileDockButtons: function () {
    var root = document.querySelector('[data-ed-root]');
    if (!root) return;
    var header = root.querySelector('.ed-topbar');
    var more = header && header.querySelector('.ed-m-more');
    var tools = root.querySelector('[data-ed-float-tools]');
    var previewBtn = root.querySelector('.ed-m-preview');
    var propsBtn = root.querySelector('.ed-m-props');
    var end = root.querySelector('.ed-m-dock-end');
    if (!previewBtn || !propsBtn) return;
    if (root.classList.contains('is-mobile') && tools) {
      if (!end) {
        end = document.createElement('div');
        end.className = 'ed-m-dock-end';
        tools.appendChild(end);
      } else if (end.parentElement !== tools) {
        tools.appendChild(end);
      }
      if (previewBtn.parentElement !== end) end.appendChild(previewBtn);
      if (propsBtn.parentElement !== end) end.appendChild(propsBtn);
      return;
    }
    if (end) end.remove();
    if (!header || !more) return;
    if (previewBtn.parentElement !== header) more.after(previewBtn);
    if (propsBtn.parentElement !== header) previewBtn.after(propsBtn);
  },
  ensureMobileDrawerExtras: function () {
    var actions = document.querySelector('.ed-topbar__actions');
    if (!actions) return;
    var vendor = actions.querySelector('[data-ed-m-vendor]');
    var credit = actions.querySelector('[data-ed-m-credit]');
    var on = !!(document.querySelector('.ed.is-mobile'));
    if (!on) {
      if (vendor) vendor.remove();
      if (credit) credit.remove();
      return;
    }
    var after = actions.querySelector('.ed-m-drawer-close');
    if (!vendor) {
      vendor = document.createElement('button');
      vendor.type = 'button';
      vendor.className = 'ed-btn';
      vendor.setAttribute('data-ed-m-vendor', '1');
      vendor.textContent = '타사포맷';
      vendor.addEventListener('click', function (e) {
        e.preventDefault();
        var src = document.querySelector('.ed-topbar__vendor, .ed-corner-fab--vendor');
        if (src) src.click();
      });
      if (after && after.nextSibling) after.after(vendor);
      else actions.insertBefore(vendor, actions.firstChild);
    }
    if (!credit) {
      credit = document.createElement('button');
      credit.type = 'button';
      credit.className = 'ed-btn';
      credit.setAttribute('data-ed-m-credit', '1');
      credit.textContent = '남은 크레딧';
      credit.addEventListener('click', function (e) {
        e.preventDefault();
        var chip = document.getElementById('lu-credit-chip');
        if (!chip) return;
        chip.hidden = false;
        chip.click();
      });
      vendor.after(credit);
    }
  },
  toggleMobileProps: function () {
    var root = document.querySelector('[data-ed-root]');
    var props = document.querySelector('[data-ed-props-panel]');
    if (!root || !props) return;
    var open = !props.classList.contains('is-m-open');
    props.classList.toggle('is-m-open', open);
    root.classList.toggle('is-props-open', open);
    var preview = document.querySelector('[data-ed-preview-panel]');
    if (preview) preview.classList.remove('is-m-open');
    root.classList.remove('is-preview-open');
  },
  toggleMobilePreview: function () {
    var root = document.querySelector('[data-ed-root]');
    var preview = document.querySelector('[data-ed-preview-panel]');
    if (!root || !preview) return;
    var open = !preview.classList.contains('is-m-open');
    if (open && preview.classList.contains('is-minimized')) {
      var min = preview.querySelector('.ed-props__min');
      if (min) min.click();
    }
    preview.classList.toggle('is-m-open', open);
    root.classList.toggle('is-preview-open', open);
    if (open && typeof this.refreshPaperPreview === 'function') this.refreshPaperPreview();
    var props = document.querySelector('[data-ed-props-panel]');
    if (props) props.classList.remove('is-m-open');
    root.classList.remove('is-props-open');
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
      examplePrompts: [
        { label: '☆ 라벨 추천', prompt_text: '용도에 맞는 라벨 상품을 하나 추천해줘.' },
        { label: '◎ 주소 라벨', prompt_text: '주소 라벨용 용지를 추천해줘.' },
        { label: '▦ 바코드', prompt_text: '바코드 라벨 상품을 추천해줘.' },
        { label: '○ 원형 스티커', prompt_text: '원형 네임 스티커 용지를 추천해줘.' },
        { label: '◇ 가격표', prompt_text: '가격표 라벨 상품을 추천해줘.' },
        { label: '✦ 클립아트', prompt_text: '카페 원두 라벨에 넣을 커피콩 클립아트를 그려줘.' },
        { label: '♡ 일러스트', prompt_text: '핸드메이드 라벨용 하트와 리본 일러스트를 그려줘.' }
      ],
      labiIconUrl: '/assets/labi-icon.png',
      isLoggedIn: !!user,
      ensureLogin: async function () {
        var current = null;
        try { current = await self.getAuthUser(); } catch (e) { current = null; }
        if (current) return true;
        return await self.showSaveAuthPrompt();
      },
      onApplyProduct: function (product) {
        self.applyLabiProduct(product);
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
      },
    });
  },
  enrichLabiProduct: async function (product) {
    var p = Object.assign({}, product || {});
    var sku = String(p.sku || p.paperNo || p.paper_no || '').trim().toUpperCase();
    var id = parseInt(p.id || p.productId || p.product_id || 0, 10) || 0;
    var spec = [p.spec, p.size, p.name, p.title, p.paper_name].filter(Boolean).join(' ');
    var takeSize = function (text) {
      var t = String(text || '');
      var size = t.match(/([\d.]+)\s*[×xX]\s*([\d.]+)\s*mm/i);
      if (!size) {
        var loose = t.match(/([\d.]+)\s*[×xX]\s*([\d.]+)/);
        if (loose && parseFloat(loose[1]) >= 8 && parseFloat(loose[2]) >= 8) size = loose;
      }
      if (size) {
        p.width_mm = parseFloat(size[1]);
        p.height_mm = parseFloat(size[2]);
      }
    };
    var takeLabels = function (text) {
      var n = String(text || '').match(/(\d+)\s*칸/) || String(text || '').match(/장당\s*(\d+)/);
      if (n) p.labels_per_sheet = parseInt(n[1], 10);
    };
    if (p.editor_url || p.editorUrl) {
      try {
        var u = new URL(String(p.editor_url || p.editorUrl), location.origin);
        if (!(parseFloat(p.width_mm || p.widthMm || 0) > 0) && u.searchParams.get('w'))
          p.width_mm = parseFloat(u.searchParams.get('w'));
        if (!(parseFloat(p.height_mm || p.heightMm || 0) > 0) && u.searchParams.get('h'))
          p.height_mm = parseFloat(u.searchParams.get('h'));
        if (!(parseInt(p.labels_per_sheet || p.labelsPerSheet || 0, 10) > 0) && u.searchParams.get('labels'))
          p.labels_per_sheet = parseInt(u.searchParams.get('labels'), 10);
        if (!p.sku && (u.searchParams.get('sku') || u.searchParams.get('paper') || u.searchParams.get('paperNo')))
          p.sku = u.searchParams.get('sku') || u.searchParams.get('paper') || u.searchParams.get('paperNo');
        if (!p.shape && u.searchParams.get('shape'))
          p.shape = u.searchParams.get('shape');
      } catch (e) { /* ignore bad editor_url */ }
    }
    if (!(parseFloat(p.width_mm || p.widthMm || 0) > 0)) takeSize(spec);
    if (!(parseInt(p.labels_per_sheet || p.labelsPerSheet || 0, 10) > 0)) takeLabels(spec);
    if (sku) p.sku = p.sku || sku;
    try {
      var res = await fetch('/api/shop/editor-papers', { credentials: 'same-origin' });
      var json = await res.json();
      var items = (json && json.data && (json.data.items || json.data.Items)) || [];
      var hit = null;
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        var itSku = String(it.sku || it.Sku || '').trim().toUpperCase();
        var itId = parseInt(it.id || it.Id || 0, 10) || 0;
        if ((id && itId === id) || (sku && itSku === sku)) { hit = it; break; }
      }
      if (hit) {
        p.id = p.id || hit.id || hit.Id;
        p.sku = p.sku || hit.sku || hit.Sku;
        p.name = p.name || hit.name || hit.Name;
        p.shape = p.shape || hit.shape || hit.Shape;
        if (!(parseFloat(p.width_mm || p.widthMm || 0) > 0))
          p.width_mm = parseFloat(hit.widthMm || hit.WidthMm || 0);
        if (!(parseFloat(p.height_mm || p.heightMm || 0) > 0))
          p.height_mm = parseFloat(hit.heightMm || hit.HeightMm || 0);
        if (!(parseInt(p.labels_per_sheet || p.labelsPerSheet || 0, 10) > 0))
          p.labels_per_sheet = parseInt(hit.labelsPerSheet || hit.LabelsPerSheet || 0, 10);
      }
    } catch (e) { /* keep parsed spec */ }
    return p;
  },
  buildLabiPaperDocument: function (product) {
    var w = parseFloat(product.width_mm || product.widthMm || 0);
    var h = parseFloat(product.height_mm || product.heightMm || 0);
    if (!(w > 0 && h > 0)) return null;
    var n = parseInt(product.labels_per_sheet || product.labelsPerSheet || 1, 10) || 1;
    var gap = 3, pageW = 210, pageH = 297, margin = 5;
    var maxCols = Math.max(1, Math.floor((pageW - margin * 2 + gap) / (w + gap)));
    var cols = n === 1 ? 1 : Math.min(n, maxCols);
    var rows = Math.max(1, Math.ceil(n / cols));
    var per = cols * rows;
    var cells = [];
    var sample = {
      id: 'labiPaper1',
      type: 'text',
      x: w * 0.12,
      y: h * 0.28,
      width: w * 0.76,
      height: h * 0.44,
      fill: '#7B2840',
      text: '라벨업',
      bold: true,
      fontSize: Math.min(9, Math.max(3.5, h * 0.28)),
      zIndex: 1,
      visible: true
    };
    for (var i = 0; i < per; i++)
      cells.push({ index: i, objects: i === 0 ? [sample] : [] });
    var shape = String(product.shape || '').toLowerCase();
    var kind = /circle|원형|ellipse/.test(shape) ? 'ellipse' : (/heart|하트/.test(shape) ? 'svg' : 'roundrect');
    var paperNo = product.sku || product.paperNo || 'CUSTOM';
    var paperName = product.name || (w + '×' + h + ' mm');
    return {
      version: 2,
      format: 'labelup',
      name: paperName,
      widthMm: w,
      heightMm: h,
      width: w,
      height: h,
      width_mm: w,
      height_mm: h,
      paper: {
        version: 1,
        paperNo: paperNo,
        PaperNo: paperNo,
        name: paperName,
        category: product.category || 'A4',
        brand: 'LabelUp',
        paperWidthMm: pageW,
        paperHeightMm: pageH,
        labelWidthMm: w,
        labelHeightMm: h,
        LabelWidthMm: w,
        LabelHeightMm: h,
        columns: cols,
        rows: rows,
        Columns: cols,
        Rows: rows,
        leftMarginMm: margin,
        topMarginMm: margin,
        rightMarginMm: margin,
        bottomMarginMm: margin,
        hGapMm: gap,
        vGapMm: gap,
        labelColor: '#FFFFFF',
        shape: { kind: kind, Kind: kind, cornerRadiusMm: 1.2, CornerRadiusMm: 1.2 }
      },
      background: '#FFFFFF',
      pages: [{ index: 0, cells: cells }]
    };
  },
  applyLabiPaperViaPicker: function (product) {
    var sku = String(product.sku || '').trim().toUpperCase();
    var w = parseFloat(product.width_mm || product.widthMm || 0);
    var h = parseFloat(product.height_mm || product.heightMm || 0);
    var open = document.querySelector('[data-tut="presets"]');
    if (!open || document.querySelector('[data-tut="paper-picker-head"]')) return;
    open.click();
    var tries = 0;
    var timer = setInterval(function () {
      tries++;
      var cards = document.querySelectorAll('.ed-paper-card');
      var hit = null;
      for (var i = 0; i < cards.length; i++) {
        var t = (cards[i].innerText || '').replace(/\s+/g, ' ');
        if (sku && t.toUpperCase().indexOf(sku) >= 0) { hit = cards[i]; break; }
        if (!hit && w > 0 && h > 0 && t.indexOf(String(w)) >= 0 && t.indexOf(String(h)) >= 0)
          hit = cards[i];
      }
      if (hit) {
        hit.click();
        clearInterval(timer);
      } else if (tries > 20) {
        clearInterval(timer);
        var closer = document.querySelector('.ed-modal__card--papers .ed-modal__close');
        if (closer) closer.click();
      }
    }, 120);
  },
  applyLabiProduct: async function (product) {
    var self = this;
    var p = await self.enrichLabiProduct(product || {});
    var info = {
      sku: p.sku || p.paperNo || '',
      w: parseFloat(p.width_mm || p.widthMm || 0) || 0,
      h: parseFloat(p.height_mm || p.heightMm || 0) || 0,
      n: parseInt(p.labels_per_sheet || p.labelsPerSheet || 0, 10) || 0
    };
    if (typeof self.applySuggestedPaper === 'function') self.applySuggestedPaper(info);
    var applied = false;
    if (self._labiDotNet) {
      var doc = self.buildLabiPaperDocument(p);
      if (doc) {
        try {
          await self._labiDotNet.invokeMethodAsync('ApplyLabiDocument', JSON.stringify(doc));
          applied = true;
        } catch (e) { applied = false; }
      }
      if (!applied) {
        try {
          await self._labiDotNet.invokeMethodAsync('ApplyLabiProduct', JSON.stringify(p));
          applied = true;
        } catch (e2) { applied = false; }
      }
    }
    if (typeof self.applySuggestedPaper === 'function') self.applySuggestedPaper(info);
    var canvasOk = self.labiCanvasMatches(info.w, info.h);
    if (!applied || !canvasOk) self.applyLabiPaperViaPicker(p);
    setTimeout(function () {
      if (typeof self.applySuggestedPaper === 'function') self.applySuggestedPaper(info);
      if (!self.labiCanvasMatches(info.w, info.h)) self.applyLabiPaperViaPicker(p);
    }, 480);
  },
  labiCanvasMatches: function (w, h) {
    var skia = document.querySelector('.canvas-stage__skia');
    if (!skia || !(w > 0 && h > 0)) return true;
    var r = skia.getBoundingClientRect();
    if (!(r.height > 20 && r.width > 20)) return true;
    var ratio = r.width / r.height;
    var want = w / h;
    return Math.abs(ratio - want) / want < 0.08;
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

  listMyCliparts: async function () {
    var res = await fetch(this.apiUrl('/api/editor/my-cliparts'), {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    });
    var json = await res.json().catch(function () { return null; });
    if (res.status === 401) {
      return { items: [], loggedIn: false };
    }
    if (!res.ok || !json || json.success === false) {
      throw new Error((json && json.message) || ('HTTP ' + res.status));
    }
    var data = json.data || {};
    return {
      items: Array.isArray(data.items) ? data.items : [],
      loggedIn: data.loggedIn !== false
    };
  },

  bindInfiniteScroll: function (el, dotNetRef, methodName, threshold) {
    if (!el) return;
    this.unbindInfiniteScroll(el);
    var gap = typeof threshold === 'number' ? threshold : 140;
    var busy = false;
    var trigger = function () {
      if (busy) return;
      busy = true;
      Promise.resolve(dotNetRef.invokeMethodAsync(methodName))
        .catch(function (err) { console.warn('[LabelUp] infinite scroll', err); })
        .then(function () {
          setTimeout(function () { busy = false; }, 220);
        });
    };
    var onScroll = function () {
      if (el.scrollTop + el.clientHeight < el.scrollHeight - gap) return;
      trigger();
    };
    el.__luInfScroll = onScroll;
    el.__luInfTrigger = trigger;
    el.addEventListener('scroll', onScroll, { passive: true });
    // 첫 화면이 가득 차지 않으면 바로 한 번 더 시도
    setTimeout(function () {
      if (el.scrollHeight <= el.clientHeight + gap) trigger();
    }, 60);
  },

  unbindInfiniteScroll: function (el) {
    if (!el) return;
    if (el.__luInfScroll) {
      el.removeEventListener('scroll', el.__luInfScroll);
      el.__luInfScroll = null;
    }
    el.__luInfTrigger = null;
    if (el.__luInfObs && el.__luInfSentinel) {
      try { el.__luInfObs.disconnect(); } catch (e) { /* ignore */ }
      el.__luInfObs = null;
      el.__luInfSentinel = null;
    }
  },

  bindInfiniteSentinel: function (root, sentinel, dotNetRef, methodName) {
    if (!root || !dotNetRef) return;
    this.unbindInfiniteScroll(root);

    var gap = 140;
    var busy = false;
    var trigger = function () {
      if (busy) return;
      busy = true;
      Promise.resolve(dotNetRef.invokeMethodAsync(methodName))
        .catch(function (err) { console.warn('[LabelUp] infinite scroll', err); })
        .then(function () {
          setTimeout(function () { busy = false; }, 220);
        });
    };

    var onScroll = function () {
      if (root.scrollTop + root.clientHeight < root.scrollHeight - gap) return;
      trigger();
    };
    root.__luInfScroll = onScroll;
    root.__luInfTrigger = trigger;
    root.addEventListener('scroll', onScroll, { passive: true });

    if (sentinel && typeof IntersectionObserver !== 'undefined') {
      var obs = new IntersectionObserver(function (entries) {
        for (var i = 0; i < entries.length; i++) {
          if (entries[i].isIntersecting) {
            trigger();
            break;
          }
        }
      }, { root: root, rootMargin: '180px 0px', threshold: 0 });
      obs.observe(sentinel);
      root.__luInfObs = obs;
      root.__luInfSentinel = sentinel;
    }

    setTimeout(function () {
      if (root.scrollHeight <= root.clientHeight + gap) trigger();
    }, 80);
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

  isMobileEditor: function () {
    try {
      return window.matchMedia('(max-width: 900px), ((pointer: coarse) and (max-width: 1180px))').matches;
    } catch (e) {
      return Math.min(window.innerWidth || 0, window.innerHeight || 0) <= 900;
    }
  },
  getTopBarPinned: function () {
    try {
      if (window.labelUpEditor.isMobileEditor()) return true;
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
  bindZoomSlider: function () {
    if (this._zoomSliderBound) return;
    this._zoomSliderBound = true;
    var MIN = 25;
    var MAX = 800;
    var dragging = false;

    function readPct(box) {
      var span = box.querySelector('.ed-zoom__pct') || box.querySelector('span');
      var n = parseFloat(((span && span.textContent) || '').replace(/[^\d.]/g, ''));
      return isFinite(n) ? n : 100;
    }

    function applyZoom(percent, end) {
      var host = window.labelUpEditor._canvasHost;
      var zoom = Math.max(0.25, Math.min(8, percent / 100));
      if (!host) return;
      Promise.resolve(host.invokeMethodAsync('GetViewState')).then(function (st) {
        var panX = st && st.length > 1 ? Number(st[1]) || 0 : 0;
        var panY = st && st.length > 2 ? Number(st[2]) || 0 : 0;
        return host.invokeMethodAsync('OnGestureZoomPan', zoom, panX, panY).then(function () {
          if (end) return host.invokeMethodAsync('OnGestureEnd');
        });
      }).catch(function () { /* ignore */ });
    }

    function enhance(box) {
      if (!box || box.querySelector('.ed-zoom__slider')) return;
      var span = box.querySelector('span');
      if (span) span.classList.add('ed-zoom__pct');
      var wrap = document.createElement('div');
      wrap.className = 'ed-zoom__slider-wrap';
      var input = document.createElement('input');
      input.type = 'range';
      input.className = 'ed-zoom__slider';
      input.min = String(MIN);
      input.max = String(MAX);
      input.step = '1';
      input.setAttribute('aria-label', '확대/축소');
      input.value = String(Math.round(Math.max(MIN, Math.min(MAX, readPct(box)))));
      wrap.appendChild(input);
      box.insertBefore(wrap, span || null);

      input.addEventListener('pointerdown', function () { dragging = true; });
      input.addEventListener('input', function () {
        dragging = true;
        var pct = Number(input.value);
        if (span) span.textContent = Math.round(pct) + '%';
        applyZoom(pct, false);
      });
      function finish() {
        var pct = Number(input.value);
        if (span) span.textContent = Math.round(pct) + '%';
        applyZoom(pct, true);
        dragging = false;
      }
      input.addEventListener('change', finish);
      input.addEventListener('pointerup', finish);
      input.addEventListener('pointercancel', finish);
    }

    function sync() {
      var box = document.querySelector('.ed-zoom');
      if (!box) return;
      enhance(box);
      if (dragging) return;
      var input = box.querySelector('.ed-zoom__slider');
      if (!input || document.activeElement === input) return;
      input.value = String(Math.round(Math.max(MIN, Math.min(MAX, readPct(box)))));
    }

    var mo = new MutationObserver(sync);
    var start = function () {
      sync();
      var root = document.querySelector('.ed-topbar') || document.body;
      mo.observe(root, { subtree: true, childList: true, characterData: true });
    };
    if (document.querySelector('.ed-zoom')) start();
    else {
      var wait = new MutationObserver(function () {
        if (document.querySelector('.ed-zoom')) {
          wait.disconnect();
          start();
        }
      });
      wait.observe(document.documentElement, { childList: true, subtree: true });
    }
  },
  bindTutorialDock: function () {
    if (this._tutorialDockBound) return;
    this._tutorialDockBound = true;
    var pickLast = function (sel) {
      var all = document.querySelectorAll(sel);
      if (!all.length) return null;
      var keep = all[all.length - 1];
      all.forEach(function (b) { if (b !== keep) b.remove(); });
      return keep;
    };
    var parkLabi = function (labi) {
      var row = document.querySelector('.ed-topbar__title-row');
      var cloud = row && row.querySelector('.ed-autosave');
      if (!row || !cloud) return;
      var natives = row.querySelectorAll('.ed-topbar__labi:not([data-ed-labi-proxy])');
      var proxies = row.querySelectorAll('.ed-topbar__labi[data-ed-labi-proxy]');
      var header = natives[0] || proxies[0] || null;
      if (natives.length) {
        proxies.forEach(function (p) { p.remove(); });
        header = natives[0];
      } else if (proxies.length > 1) {
        for (var i = 1; i < proxies.length; i++) proxies[i].remove();
      }
      if (!header) {
        header = document.createElement('button');
        header.type = 'button';
        header.className = 'ed-topbar__labi';
        header.setAttribute('data-ed-labi-proxy', '1');
        header.setAttribute('data-tut', 'labi-fab');
        header.title = '라비와 라벨 만들기';
        header.setAttribute('aria-label', '라비AI');
        header.innerHTML = '<img src="/assets/labi-icon.png" alt="" width="18" height="18"><span>라비AI</span>';
        header.addEventListener('click', function (e) {
          e.preventDefault();
          var src = document.querySelector('.ed-corner-fab--labi');
          if (src) src.click();
        });
      }
      if (cloud.nextElementSibling !== header) cloud.after(header);
      if (labi && labi !== header) {
        labi.classList.add('is-parked');
        labi.removeAttribute('data-tut');
        labi.setAttribute('aria-hidden', 'true');
      }
    };
    var parkVendor = function (vendor) {
      var row = document.querySelector('.ed-topbar__title-row');
      var labi = row && (row.querySelector('.ed-topbar__labi:not([data-ed-labi-proxy])') || row.querySelector('.ed-topbar__labi'));
      if (!row || !labi) return;
      var natives = row.querySelectorAll('.ed-topbar__vendor:not([data-ed-vendor-proxy])');
      var proxies = row.querySelectorAll('.ed-topbar__vendor[data-ed-vendor-proxy]');
      var header = natives[0] || proxies[0] || null;
      if (natives.length) {
        proxies.forEach(function (p) { p.remove(); });
        header = natives[0];
      } else if (proxies.length > 1) {
        for (var i = 1; i < proxies.length; i++) proxies[i].remove();
      }
      if (!header) {
        header = document.createElement('button');
        header.type = 'button';
        header.className = 'ed-topbar__vendor';
        header.setAttribute('data-ed-vendor-proxy', '1');
        header.setAttribute('data-tut', 'import-fab');
        header.title = '애니라벨·아이라벨·폼텍 파일 가져오기';
        header.setAttribute('aria-label', '타사포맷');
        header.innerHTML = '<svg class="ed-topbar__vendor-ico" viewBox="0 0 16 16" aria-hidden="true"><path fill="currentColor" d="M3.3 1.8h5.6c.3 0 .6.1.8.4l2.6 2.7c.2.2.3.5.3.8v7.5c0 .7-.6 1.2-1.3 1.2H3.3c-.7 0-1.2-.5-1.2-1.2V3c0-.7.5-1.2 1.2-1.2Zm.5 1.4v9.6h7.9V6.1H9.2c-.4 0-.7-.3-.7-.7V3.2H3.8Zm5.6.5v1.7h1.7L9.4 3.7ZM4.8 9.4h2.1V8.1c0-.4.5-.6.8-.3l2.3 2c.2.2.2.6 0 .8l-2.3 2c-.3.3-.8 0-.8-.3v-1.3H4.8a.7.7 0 0 1 0-1.4Z"/></svg><span>타사포맷</span>';
        header.addEventListener('click', function (e) {
          e.preventDefault();
          var src = document.querySelector('.ed-corner-fab--vendor');
          if (src) src.click();
        });
      }
      if (labi.nextElementSibling !== header) labi.after(header);
      if (vendor && vendor !== header) {
        vendor.classList.add('is-parked');
        vendor.removeAttribute('data-tut');
        vendor.setAttribute('aria-hidden', 'true');
      }
    };
    var asItem = function (btn) {
      if (!btn) return;
      btn.classList.add('ed-float-tools__item');
      var span = btn.querySelector('span:not(.ed-float-tools__ico)');
      if (span) span.classList.add('ed-float-tools__label');
      var img = btn.querySelector('img');
      if (img && !img.closest('.ed-float-tools__ico')) {
        var wrap = document.createElement('span');
        wrap.className = 'ed-float-tools__ico';
        wrap.setAttribute('aria-hidden', 'true');
        img.parentNode.insertBefore(wrap, img);
        wrap.appendChild(img);
      }
      var svg = btn.querySelector('svg.ed-corner-fab__ico');
      if (svg) svg.classList.add('ed-float-tools__ico');
    };
    var parkFileActions = function () {
      var bar = document.querySelector('.ed-float-tools__bar');
      var paper = document.querySelector('[data-tut="presets"]');
      var paperGroup = paper && paper.closest('.ed-float-tools__group');
      if (!bar || !paperGroup) return;
      var isMobile = !!(document.querySelector('.ed.is-mobile') || document.documentElement.classList.contains('is-ed-mobile'));
      var designGroup = bar.querySelector('.ed-float-tools__group--files');
      var divider = bar.querySelector('.ed-float-tools__divider--files');
      var stackedData = paperGroup.querySelector('[data-tut="data-import"]');
      if (isMobile) return;
      var srcDesign = document.querySelector('.ed-topbar__actions [data-tut="mydesign"], .ed-topbar__actions [data-ed-file-src="mydesign"]');
      var srcData = document.querySelector('.ed-topbar__actions [data-tut="data-import"], .ed-topbar__actions [data-ed-file-src="data-import"]');
      var srcCreate = document.querySelector('.ed-topbar__actions [data-tut="data-create"], .ed-topbar__actions [data-ed-file-src="data-create"]');
      var retarget = function (el, name) {
        if (!el) return;
        if (el.getAttribute('data-tut') === name) {
          el.setAttribute('data-ed-file-src', name);
          el.removeAttribute('data-tut');
        }
      };
      if (!srcDesign || !srcData) return;
      retarget(srcDesign, 'mydesign');
      retarget(srcData, 'data-import');
      retarget(srcCreate, 'data-create');
      var mk = function (label, tut, src) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ed-float-tools__file';
        btn.setAttribute('data-tut', tut);
        btn.setAttribute('title', label);
        var svg = src && src.querySelector ? src.querySelector('svg') : null;
        btn.innerHTML = (svg ? svg.outerHTML : '') + '<span>' + label + '</span>';
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          var live = document.querySelector('.ed-topbar__actions [data-ed-file-src="' + tut + '"]');
          if (live) live.click();
        });
        return btn;
      };
      var dataBtn = stackedData || (designGroup && designGroup.querySelector('[data-tut="data-import"]'));
      if (!dataBtn) dataBtn = mk('데이터 가져오기', 'data-import', srcData);
      if (dataBtn.parentElement !== paperGroup) paperGroup.appendChild(dataBtn);
      if (!designGroup) {
        designGroup = document.createElement('div');
        designGroup.className = 'ed-float-tools__group ed-float-tools__group--files';
        designGroup.setAttribute('role', 'group');
      }
      var designBtn = designGroup.querySelector('[data-tut="mydesign"]');
      if (!designBtn) designGroup.appendChild(mk('내 디자인', 'mydesign', srcDesign));
      if (srcCreate && !designGroup.querySelector('[data-tut="data-create"]'))
        designGroup.appendChild(mk('데이터 생성하기', 'data-create', srcCreate));
      if (!divider) {
        divider = document.createElement('span');
        divider.className = 'ed-float-tools__divider ed-float-tools__divider--files';
        divider.setAttribute('aria-hidden', 'true');
      }
      if (paperGroup.nextElementSibling !== divider) paperGroup.after(divider);
      if (divider.nextElementSibling !== designGroup) divider.after(designGroup);
    };
    var relabelExport = function () {
      var btn = document.querySelector('[data-tut="export"]');
      if (!btn) return;
      var label = '미리보기/프린트';
      var current = (btn.textContent || '').replace(/\s+/g, ' ').trim();
      if (current === label) return;
      var svg = btn.querySelector('svg');
      btn.textContent = '';
      if (svg) btn.appendChild(svg);
      btn.appendChild(document.createTextNode(label));
      btn.setAttribute('title', label);
    };
    var parkTut = function (tut) {
      var bar = document.querySelector('.ed-float-tools__bar');
      if (!bar) return;
      var natives = document.querySelectorAll('.ed-tut-reopen:not([data-ed-tut-proxy])');
      var proxies = bar.querySelectorAll('.ed-tut-reopen[data-ed-tut-proxy]');
      var src = tut && !tut.getAttribute('data-ed-tut-proxy') ? tut : natives[natives.length - 1];
      if (proxies.length > 1) {
        for (var i = 1; i < proxies.length; i++) proxies[i].remove();
      }
      var proxy = proxies[0] || null;
      if (!proxy) {
        proxy = document.createElement('button');
        proxy.type = 'button';
        proxy.className = 'ed-tut-reopen ed-float-tools__item';
        proxy.setAttribute('data-ed-tut-proxy', '1');
        proxy.setAttribute('title', '튜토리얼 다시 보기');
        proxy.innerHTML = '<span class="ed-float-tools__ico" aria-hidden="true">✦</span><span class="ed-float-tools__label">튜토리얼</span>';
        proxy.addEventListener('click', function (e) {
          e.preventDefault();
          var live = document.querySelector('.ed-tut-reopen:not([data-ed-tut-proxy])');
          if (live) live.click();
        });
      }
      if (proxy.parentElement !== bar || bar.lastElementChild !== proxy) bar.appendChild(proxy);
      natives.forEach(function (btn) {
        btn.classList.add('is-parked');
        btn.setAttribute('aria-hidden', 'true');
      });
      if (src && src.parentElement && src.parentElement.closest('.ed-float-tools')) {
        var host = document.querySelector('[data-ed-root]');
        if (host) host.appendChild(src);
      }
      var trapped = document.querySelector('.ed-float-tools .lu-tut-invite');
      if (trapped) {
        var root = document.querySelector('[data-ed-root]') || document.body;
        root.appendChild(trapped);
      }
    };
    var dock = function () {
      var bar = document.querySelector('.ed-float-tools__bar');
      if (!bar) return;
      var labi = pickLast('.ed-corner-fab--labi');
      var vendor = pickLast('.ed-corner-fab--vendor');
      var tut = pickLast('.ed-tut-reopen:not([data-ed-tut-proxy])');
      parkLabi(labi);
      parkVendor(vendor);
      parkTut(tut);
      parkFileActions();
      if (window.labelUpEditor && typeof window.labelUpEditor.parkMobileDockButtons === 'function')
        window.labelUpEditor.parkMobileDockButtons();
      if (window.labelUpEditor && typeof window.labelUpEditor.ensureMobileDrawerExtras === 'function')
        window.labelUpEditor.ensureMobileDrawerExtras();
      relabelExport();
    };
    var mo = new MutationObserver(dock);
    var start = function () {
      dock();
      mo.observe(document.body, { childList: true, subtree: true });
    };
    if (document.querySelector('.ed-float-tools__bar')) start();
    else {
      var wait = new MutationObserver(function () {
        if (document.querySelector('.ed-float-tools__bar')) {
          wait.disconnect();
          start();
        }
      });
      wait.observe(document.documentElement, { childList: true, subtree: true });
    }
  },
  ensureLayerOps: function () {
    if (this._layerOps) return this._layerOps;
    var wait = function (ms) {
      return new Promise(function (resolve) { setTimeout(resolve, ms); });
    };
    var tabByName = function (name) {
      var tabs = document.querySelectorAll('.ed-props__tab');
      for (var i = 0; i < tabs.length; i++) {
        if ((tabs[i].textContent || '').replace(/\s+/g, '') === name) return tabs[i];
      }
      return null;
    };
    var showTab = async function (name) {
      var tab = tabByName(name);
      if (tab && !tab.classList.contains('is-active')) {
        tab.click();
        await wait(80);
      }
      return tab;
    };
    var expandProps = async function () {
      var panel = document.querySelector('[data-ed-props-panel]');
      if (panel && panel.classList.contains('is-minimized')) {
        var min = panel.querySelector('.ed-props__min');
        if (min) min.click();
        await wait(60);
      }
    };
    var readZ = function (el) {
      var em = el && el.querySelector ? el.querySelector('em') : null;
      var m = ((em && em.textContent) || '').match(/-?\d+/);
      return m ? parseInt(m[0], 10) : 0;
    };
    var layerRows = function () {
      return Array.prototype.slice.call(document.querySelectorAll('[data-ed-props-panel] .ed-layer-item')).map(function (el) {
        var nameEl = el.querySelector('span:nth-child(2)');
        return {
          el: el,
          z: readZ(el),
          active: el.classList.contains('is-active'),
          name: ((nameEl && nameEl.textContent) || '').trim(),
          key: (el.textContent || '').replace(/\s+/g, ' ').trim()
        };
      });
    };
    var freezeEl = null;
    var freezePanel = null;
    var startFreeze = function (order) {
      stopFreeze();
      var panel = document.querySelector('[data-ed-props-panel]');
      var inner = panel && panel.querySelector('.ed-props__inner');
      var list = panel && panel.querySelector('.ed-props__layers');
      var src = inner || panel;
      if (!src) return;
      freezePanel = panel;
      var box = src.getBoundingClientRect();
      if (panel) {
        var pb = panel.getBoundingClientRect();
        panel.style.width = pb.width + 'px';
        panel.style.height = pb.height + 'px';
        panel.style.maxHeight = pb.height + 'px';
        panel.style.overflow = 'hidden';
      }
      document.documentElement.classList.add('lu-layer-applying');
      freezeEl = document.createElement('div');
      freezeEl.id = 'lu-layer-freeze';
      freezeEl.className = 'ed-layer-freeze';
      freezeEl.setAttribute('aria-hidden', 'true');
      freezeEl.style.top = box.top + 'px';
      freezeEl.style.left = box.left + 'px';
      freezeEl.style.width = box.width + 'px';
      freezeEl.style.height = box.height + 'px';
      var tabs = panel && panel.querySelector('.ed-props__tabs');
      if (tabs) {
        var t = tabs.cloneNode(true);
        Array.prototype.forEach.call(t.querySelectorAll('.ed-props__tab'), function (b) {
          b.classList.toggle('is-active', (b.textContent || '').replace(/\s+/g, '') === '레이어');
        });
        freezeEl.appendChild(t);
      }
      var body = document.createElement('div');
      body.className = 'ed-layer-freeze__list';
      var items = list ? Array.prototype.slice.call(list.querySelectorAll('.ed-layer-item')) : [];
      var pick = function (name) {
        for (var i = 0; i < items.length; i++) {
          var n = ((items[i].querySelector('span:nth-child(2)') || {}).textContent || '').trim();
          if (n === name) return items[i];
        }
        return null;
      };
      if (order && order.length) {
        order.forEach(function (info) {
          var match = pick(info.name);
          if (match) body.appendChild(match.cloneNode(true));
        });
      } else {
        items.forEach(function (el) { body.appendChild(el.cloneNode(true)); });
      }
      freezeEl.appendChild(body);
      document.body.appendChild(freezeEl);
    };
    var stopFreeze = function () {
      document.documentElement.classList.remove('lu-layer-applying');
      if (freezeEl) {
        freezeEl.remove();
        freezeEl = null;
      }
      if (freezePanel) {
        freezePanel.style.width = '';
        freezePanel.style.height = '';
        freezePanel.style.maxHeight = '';
        freezePanel.style.overflow = '';
        freezePanel = null;
      }
    };
    var findZInput = function () {
      var labels = document.querySelectorAll('[data-ed-props-panel] .ed-field, #lu-ctx-bar .ed-field');
      for (var i = 0; i < labels.length; i++) {
        if (((labels[i].textContent || '').indexOf('Z-Index') === -1)) continue;
        var input = labels[i].querySelector('input[type="number"]');
        if (input) return input;
      }
      return null;
    };
    var setSelectedZ = async function (z) {
      var input = findZInput();
      if (!input) return false;
      var desc = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value');
      if (desc && desc.set) desc.set.call(input, String(z));
      else input.value = String(z);
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      await wait(90);
      return true;
    };
    var findRow = function (pred) {
      var rows = layerRows();
      for (var i = 0; i < rows.length; i++) if (pred(rows[i], i)) return rows[i];
      return null;
    };
    var selectRow = async function (pred) {
      await showTab('레이어');
      var hit = findRow(pred);
      if (!hit) return null;
      hit.el.click();
      await wait(430);
      return hit;
    };
    this._layerOps = {
      wait: wait,
      showTab: showTab,
      expandProps: expandProps,
      readZ: readZ,
      layerRows: layerRows,
      setSelectedZ: setSelectedZ,
      findRow: findRow,
      selectRow: selectRow,
      startFreeze: startFreeze,
      stopFreeze: stopFreeze
    };
    return this._layerOps;
  },
  bindLayerNudge: function () {
    if (this._layerNudgeBound) return;
    this._layerNudgeBound = true;
    var self = this;
    var ops = this.ensureLayerOps();
    var clickLayerByZ = async function (z, preferInactive) {
      var hit = await ops.selectRow(function (row) {
        if (row.z !== z) return false;
        if (preferInactive && row.active) return false;
        return true;
      });
      if (hit) return true;
      hit = await ops.selectRow(function (row) { return row.z === z; });
      return !!hit;
    };
    var nudge = async function (forward) {
      if (self._layerBusy) return;
      var bar = document.querySelector('.ed-float-bar');
      if (bar && bar.querySelector('button[disabled][title="' + (forward ? '앞으로' : '뒤로') + '"]')) return;
      self._layerBusy = true;
      try {
        await ops.expandProps();
        var prevTab = document.querySelector('.ed-props__tab.is-active');
        var prevName = prevTab ? (prevTab.textContent || '').replace(/\s+/g, '') : '속성';
        await ops.showTab('레이어');
        var rows = ops.layerRows();
        if (rows.length < 2) return;
        var actives = [];
        for (var i = 0; i < rows.length; i++) if (rows[i].active) actives.push(i);
        if (!actives.length) return;
        var edge = forward ? actives[0] : actives[actives.length - 1];
        var neighbor = forward ? edge - 1 : edge + 1;
        if (neighbor < 0 || neighbor >= rows.length) return;
        var selZ = rows[edge].z;
        var neiZ = rows[neighbor].z;
        var preview = rows.map(function (r) { return { name: r.name, z: r.z }; });
        var tmp = preview[edge];
        preview[edge] = preview[neighbor];
        preview[neighbor] = tmp;
        ops.startFreeze(preview);
        rows[edge].el.click();
        await ops.wait(430);
        var temp = 100000 + Math.abs(selZ) + Math.abs(neiZ);
        if (!await ops.setSelectedZ(temp)) return;
        if (!await clickLayerByZ(neiZ, true)) return;
        if (!await ops.setSelectedZ(selZ)) return;
        if (!await clickLayerByZ(temp, false)) return;
        if (!await ops.setSelectedZ(neiZ === selZ ? (selZ + (forward ? 1 : -1)) : neiZ)) return;
        await ops.showTab(prevName === '레이어' ? '레이어' : '속성');
      } finally {
        ops.stopFreeze();
        self._layerBusy = false;
      }
    };
    document.addEventListener('click', function (e) {
      var btn = e.target && e.target.closest && e.target.closest('.ed-float-bar button');
      if (!btn) return;
      var title = btn.getAttribute('title') || '';
      if (title !== '앞으로' && title !== '뒤로') return;
      if (btn.disabled) return;
      e.preventDefault();
      e.stopImmediatePropagation();
      nudge(title === '앞으로');
    }, true);
  },
  bindLayerDrag: function () {
    if (this._layerDragBound) return;
    this._layerDragBound = true;
    var self = this;
    var ops = this.ensureLayerOps();
    var drag = null;
    var line = null;
    var ghost = null;
    var THRESH = 6;

    var ensureLine = function () {
      if (line && line.isConnected) return line;
      line = document.createElement('div');
      line.className = 'ed-layer-drop';
      line.hidden = true;
      document.body.appendChild(line);
      return line;
    };
    var listEl = function () {
      return document.querySelector('.ed-props__layers');
    };
    var decorate = function () {
      var list = listEl();
      if (!list) return;
      list.setAttribute('data-lu-layer-sort', '1');
      if (!list.getAttribute('title'))
        list.setAttribute('title', '드래그해서 레이어 순서를 바꿀 수 있습니다');
    };
    var dropIndexAt = function (clientY, from) {
      var items = Array.prototype.slice.call(document.querySelectorAll('.ed-props__layers > .ed-layer-item'));
      if (!items.length) return from;
      var others = items.filter(function (_, i) { return i !== from; });
      var to = others.length;
      for (var i = 0; i < others.length; i++) {
        var r = others[i].getBoundingClientRect();
        if (clientY < r.top + r.height / 2) { to = i; break; }
      }
      return to;
    };
    var startGhost = function (item, e) {
      killGhost();
      var r = item.getBoundingClientRect();
      ghost = item.cloneNode(true);
      ghost.classList.add('ed-layer-ghost');
      ghost.classList.remove('is-dragging', 'is-active');
      ghost.setAttribute('aria-hidden', 'true');
      ghost.style.width = r.width + 'px';
      ghost.style.height = r.height + 'px';
      ghost.style.left = r.left + 'px';
      ghost.style.top = r.top + 'px';
      document.body.appendChild(ghost);
      drag.offX = e.clientX - r.left;
      drag.offY = e.clientY - r.top;
    };
    var moveGhost = function (e) {
      if (!ghost) return;
      ghost.style.left = (e.clientX - (drag.offX || 0)) + 'px';
      ghost.style.top = (e.clientY - (drag.offY || 0)) + 'px';
    };
    var killGhost = function () {
      if (ghost && ghost.parentNode) ghost.parentNode.removeChild(ghost);
      ghost = null;
    };
    var placeLine = function (to, from) {
      var list = listEl();
      var items = Array.prototype.slice.call(document.querySelectorAll('.ed-props__layers > .ed-layer-item'));
      if (!list || !items.length) return;
      var others = items.filter(function (_, i) { return i !== from; });
      var marker = ensureLine();
      var box = list.getBoundingClientRect();
      var y;
      if (!others.length) y = box.top + 8;
      else if (to <= 0) y = others[0].getBoundingClientRect().top;
      else if (to >= others.length) y = others[others.length - 1].getBoundingClientRect().bottom;
      else y = others[to].getBoundingClientRect().top;
      marker.style.top = (y - 1) + 'px';
      marker.style.left = (box.left + 10) + 'px';
      marker.style.width = Math.max(40, box.width - 20) + 'px';
      marker.hidden = false;
    };
    var applyMove = async function (from, to, snapshot) {
      if (from === to) return;
      var order = snapshot.slice();
      var moved = order.splice(from, 1)[0];
      order.splice(to, 0, moved);
      var above = to > 0 ? order[to - 1] : null;
      var below = to < order.length - 1 ? order[to + 1] : null;
      var others = order.filter(function (r) { return r !== moved; });
      var target = null;
      var useShift = false;
      if (!above) target = Math.max.apply(null, others.map(function (r) { return r.z; })) + 1;
      else if (!below) target = Math.min.apply(null, others.map(function (r) { return r.z; })) - 1;
      else if (above.z - below.z >= 2) target = below.z + 1;
      else useShift = true;
      ops.startFreeze(order);
      try {
        if (useShift) {
          var oldAbove = above.z;
          var prefix = order.slice(0, to);
          if (!await ops.selectRow(function (row) { return row.name === moved.name && row.z === moved.z; })) return;
          if (!await ops.setSelectedZ(100000)) return;
          for (var i = 0; i < prefix.length; i++) {
            var p = prefix[i];
            if (!await ops.selectRow(function (row) { return row.name === p.name && row.z === p.z; })) return;
            if (!await ops.setSelectedZ(p.z + 1)) return;
            p.z += 1;
          }
          if (!await ops.selectRow(function (row) { return row.z === 100000; })) return;
          await ops.setSelectedZ(oldAbove);
        } else if (target !== moved.z) {
          if (!await ops.selectRow(function (row) { return row.name === moved.name && row.z === moved.z; })) {
            if (!await ops.selectRow(function (row) { return row.active; })) return;
          }
          await ops.setSelectedZ(target);
        }
        await ops.showTab('레이어');
      } finally {
        ops.stopFreeze();
      }
    };

    document.addEventListener('pointerdown', function (e) {
      if (e.button !== 0 || self._layerBusy) return;
      var item = e.target && e.target.closest && e.target.closest('.ed-props__layers > .ed-layer-item');
      if (!item) return;
      var items = Array.prototype.slice.call(document.querySelectorAll('.ed-props__layers > .ed-layer-item'));
      if (items.length < 2) return;
      var from = items.indexOf(item);
      if (from < 0) return;
      drag = {
        item: item,
        from: from,
        to: from,
        startX: e.clientX,
        startY: e.clientY,
        dragging: false,
        snapshot: ops.layerRows().map(function (r) { return { name: r.name, z: r.z }; })
      };
    }, true);

    document.addEventListener('pointermove', function (e) {
      if (!drag) return;
      var dx = e.clientX - drag.startX;
      var dy = e.clientY - drag.startY;
      if (!drag.dragging) {
        if (Math.abs(dx) < THRESH && Math.abs(dy) < THRESH) return;
        drag.dragging = true;
        drag.item.classList.add('is-dragging');
        startGhost(drag.item, e);
        var list = listEl();
        if (list) list.classList.add('is-reordering');
        try { drag.item.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
      }
      e.preventDefault();
      moveGhost(e);
      drag.to = dropIndexAt(e.clientY, drag.from);
      placeLine(drag.to, drag.from);
    }, true);

    document.addEventListener('pointerup', function (e) {
      if (!drag) return;
      var s = drag;
      drag = null;
      s.item.classList.remove('is-dragging');
      killGhost();
      var list = listEl();
      if (list) list.classList.remove('is-reordering');
      if (line) line.hidden = true;
      if (!s.dragging) return;
      e.preventDefault();
      s.item.setAttribute('data-lu-dragged', '1');
      if (s.to === s.from || self._layerBusy) return;
      self._layerBusy = true;
      applyMove(s.from, s.to, s.snapshot).finally(function () {
        self._layerBusy = false;
      });
    }, true);

    document.addEventListener('click', function (e) {
      var item = e.target && e.target.closest && e.target.closest('.ed-layer-item');
      if (!item || item.getAttribute('data-lu-dragged') !== '1') return;
      item.removeAttribute('data-lu-dragged');
      e.preventDefault();
      e.stopImmediatePropagation();
    }, true);

    var mo = new MutationObserver(decorate);
    var start = function () {
      decorate();
      var root = document.querySelector('[data-ed-props-panel]') || document.body;
      mo.observe(root, { childList: true, subtree: true });
    };
    if (document.querySelector('[data-ed-props-panel]')) start();
    else {
      var waitMo = new MutationObserver(function () {
        if (document.querySelector('[data-ed-props-panel]')) {
          waitMo.disconnect();
          start();
        }
      });
      waitMo.observe(document.documentElement, { childList: true, subtree: true });
    }
  },
  bindPaperPreviewLayout: function () {
    if (this._paperPreviewBound) return;
    this._paperPreviewBound = true;
    // 미리보기 칸은 Blazor Document.Paper(DGF 격자·여백)가 그린다.
    // 상점 규격/chooseGrid로 다시 짜면 폼텍 3627(4×2·48×130, 가로간격 0)이
    // 기본 70×36 2×4로 바뀐다. 라비·용지카드는 ApplyPaper가 문서를 바꾼다.
    this.applySuggestedPaper = function () {};
  },
  bindCanvaToolbar: function () {
    if (this._canvaToolbarBound) return;
    this._canvaToolbarBound = true;
    var stripLegacy = function () {
      document.querySelectorAll('.ed-ctx-bar:not([data-blazor-ctx])').forEach(function (el) { el.remove(); });
    };
    stripLegacy();
    new MutationObserver(function () { stripLegacy(); }).observe(document.documentElement, { childList: true, subtree: true });
    return;
    var ops = this.ensureLayerOps();
    var busy = false;
    var populating = false;
    var lastKey = '';

    var hasSelection = function () {
      var bar = document.querySelector('.ed-float-bar');
      if (bar) {
        var btns = bar.querySelectorAll('button');
        for (var i = 0; i < btns.length; i++) {
          if (!btns[i].disabled) return true;
        }
      }
      return !!document.querySelector('.ed-layer-item.is-active');
    };
    var activeTab = function () {
      var t = document.querySelector('[data-ed-props-panel] .ed-props__tab.is-active');
      return t ? (t.textContent || '').replace(/\s+/g, '') : '';
    };
    var tabByName = function (name) {
      var tabs = document.querySelectorAll('.ed-props__tab');
      for (var i = 0; i < tabs.length; i++) {
        if ((tabs[i].textContent || '').replace(/\s+/g, '') === name) return tabs[i];
      }
      return null;
    };
    var showPropsFields = async function () {
      var panel = document.querySelector('[data-ed-props-panel]');
      if (!panel) return false;
      if (panel.classList.contains('is-minimized')) {
        var min = panel.querySelector('.ed-props__min');
        if (min) min.click();
        await ops.wait(40);
      }
      var tab = tabByName('속성');
      var tabs = document.querySelector('.ed-props__tabs');
      if (tab) {
        if (tabs) tabs.style.setProperty('display', 'flex', 'important');
        tab.style.setProperty('display', 'inline-block', 'important');
        if (!tab.classList.contains('is-active')) {
          tab.click();
          await ops.wait(140);
        }
        tab.style.removeProperty('display');
        if (tabs) tabs.style.removeProperty('display');
      }
      return !!document.querySelector('[data-ed-props-panel] .ed-props__body:not(.ed-props__layers)');
    };
    var groupByLabel = function (re) {
      var groups = document.querySelectorAll('[data-ed-props-panel] .ed-field-group');
      for (var i = 0; i < groups.length; i++) {
        var lab = groups[i].querySelector('.ed-field-label');
        if (re.test(((lab && lab.textContent) || '').replace(/\s+/g, ''))) return groups[i];
      }
      return null;
    };
    var fieldLabel = function (el) {
      var parts = [];
      if (!el) return '';
      for (var n = el.firstChild; n; n = n.nextSibling) {
        if (n.nodeType === 3) {
          parts.push(n.textContent);
          continue;
        }
        if (n.nodeType === 1 && n.tagName === 'SPAN') {
          parts.push(n.textContent);
          continue;
        }
        break;
      }
      return parts.join('').replace(/\s+/g, '');
    };
    var fieldByText = function (re, root) {
      var fields = root && root.querySelectorAll
        ? root.querySelectorAll('.ed-field')
        : document.querySelectorAll('[data-ed-props-panel] .ed-field');
      for (var i = 0; i < fields.length; i++) {
        var t = fieldLabel(fields[i]) || (fields[i].textContent || '').replace(/\s+/g, '');
        if (re.test(t)) return fields[i];
      }
      return null;
    };
    var setValue = function (el, value) {
      if (!el) return;
      var proto = el.tagName === 'SELECT' ? window.HTMLSelectElement.prototype : window.HTMLInputElement.prototype;
      var desc = Object.getOwnPropertyDescriptor(proto, 'value');
      if (desc && desc.set) desc.set.call(el, String(value));
      else el.value = String(value);
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    };
    var clickFloat = function (name) {
      var btns = document.querySelectorAll('.ed-float-bar button');
      for (var i = 0; i < btns.length; i++) {
        var t = (btns[i].getAttribute('title') || '') + (btns[i].textContent || '');
        if (t.indexOf(name) !== -1 && !btns[i].disabled) {
          btns[i].click();
          return;
        }
      }
    };
    var fillColor = function () {
      var g = groupByLabel(/^채우기$/);
      return g ? g.querySelector('input[type="color"]') : null;
    };
    var strokeColor = function () {
      var g = groupByLabel(/^(선|프레임)$/);
      return g ? g.querySelector('input[type="color"]') : null;
    };
    var strokeWidth = function () {
      var f = fieldByText(/^굵기/);
      return f ? f.querySelector('input[type="number"]') : null;
    };
    var fontSelect = function () {
      var f = fieldByText(/^(폰트|글꼴)(?!크기)/);
      if (f && f.querySelector('select')) return f.querySelector('select');
      var g = groupByLabel(/텍스트/);
      if (!g) return null;
      var selects = g.querySelectorAll('select');
      for (var i = 0; i < selects.length; i++) {
        var host = selects[i].closest('.ed-field') || selects[i].parentElement;
        var t = fieldLabel(host);
        if (/표현|워드아트|종류|가로정렬|세로정렬/.test(t)) continue;
        if (/폰트|글꼴/.test(t) || selects[i].options.length > 8) return selects[i];
      }
      return null;
    };
    var fontSize = function () {
      var listed = document.querySelector('[data-ed-props-panel] input[list="ed-font-pt"], [data-ed-props-panel] input[list="ed-barcode-font-pt"]');
      if (listed) return listed;
      var g = groupByLabel(/텍스트/);
      var f = fieldByText(/^(폰트크기|글자크기|크기\(pt\)|크기pt|크기)$/, g);
      return f ? f.querySelector('input[type="number"]') : null;
    };
    var alignSelect = function () {
      var f = fieldByText(/^가로정렬/);
      return f ? f.querySelector('select') : null;
    };
    var opacityInput = function () {
      var f = fieldByText(/^투명도/);
      return f ? f.querySelector('input[type="number"]') : null;
    };
    var imageFit = function () {
      var f = fieldByText(/^맞춤/);
      return f ? f.querySelector('select') : null;
    };
    var rotationInput = function () {
      var f = fieldByText(/^자유 회전/);
      if (f) return f.querySelector('input[type="number"]');
      var g = groupByLabel(/회전/);
      return g ? g.querySelector('label.ed-field input[type="number"]') : null;
    };
    var isImageSel = function () {
      return !!groupByLabel(/이미지/) || /이미지/.test(selectionKey());
    };
    var checkByText = function (re) {
      var f = fieldByText(re);
      return f ? f.querySelector('input[type="checkbox"]') : null;
    };
    var normalizeRot = function (deg) {
      var n = ((deg + 180) % 360 + 360) % 360 - 180;
      if (n === -180) n = 180;
      return Math.round(n * 10) / 10;
    };
    var nudgeRotation = function (delta) {
      withPropsFields(function () {
        var inp = rotationInput();
        if (!inp) return;
        var cur = parseFloat(String(inp.value || '0').replace(',', '.')) || 0;
        setValue(inp, String(normalizeRot(cur + delta)));
      });
    };
    var selectionKey = function () {
      var row = document.querySelector('.ed-layer-item.is-active');
      if (row) return (row.textContent || '').replace(/\s+/g, ' ').trim();
      return hasSelection() ? '*' : '';
    };
    var withPropsFields = async function (fn) {
      var onLayers = activeTab() !== '속성';
      if (onLayers) ops.startFreeze();
      try {
        await showPropsFields();
        await fn();
      } finally {
        if (onLayers) await ops.showTab('레이어');
        if (onLayers) ops.stopFreeze();
      }
    };

    var opValue = 1;
    var opOpen = false;
    var opDragging = false;
    var opApplyTimer = 0;
    var ensureOpacityPop = function () {
      var pop = document.getElementById('lu-ctx-op');
      if (pop) return pop;
      pop = document.createElement('div');
      pop.id = 'lu-ctx-op';
      pop.className = 'ed-ctx-op';
      pop.hidden = true;
      pop.innerHTML =
        '<div class="ed-ctx-op__pct">100%</div>' +
        '<div class="ed-ctx-op__track" data-op-track>' +
          '<div class="ed-ctx-op__fill"></div>' +
          '<div class="ed-ctx-op__thumb"></div>' +
        '</div>';
      document.body.appendChild(pop);
      var onMove = function (e) {
        if (!opDragging) return;
        e.preventDefault();
        setOpacityFromY(e.clientY, false);
      };
      var onUp = function () {
        if (!opDragging) return;
        opDragging = false;
        applyOpacity(opValue, true);
        document.removeEventListener('pointermove', onMove, true);
        document.removeEventListener('pointerup', onUp, true);
      };
      pop.addEventListener('pointerdown', function (e) {
        var track = pop.querySelector('[data-op-track]');
        if (!track) return;
        e.preventDefault();
        e.stopPropagation();
        opDragging = true;
        setOpacityFromY(e.clientY, false);
        document.addEventListener('pointermove', onMove, true);
        document.addEventListener('pointerup', onUp, true);
      });
      return pop;
    };
    var paintOpacity = function (value) {
      opValue = Math.max(0, Math.min(1, value));
      var pct = Math.round(opValue * 100);
      var pop = document.getElementById('lu-ctx-op');
      if (pop) {
        var label = pop.querySelector('.ed-ctx-op__pct');
        var fill = pop.querySelector('.ed-ctx-op__fill');
        var thumb = pop.querySelector('.ed-ctx-op__thumb');
        if (label) label.textContent = pct + '%';
        if (fill) fill.style.height = pct + '%';
        if (thumb) thumb.style.top = (100 - pct) + '%';
      }
      var tint = document.querySelector('#lu-ctx-bar .ed-ctx-bar__op-tint');
      if (tint) tint.setAttribute('opacity', String(0.18 + opValue * 0.62));
      var btn = document.querySelector('#lu-ctx-bar [data-act="opacity-toggle"]');
      if (btn) btn.title = '투명도 ' + pct + '%';
    };
    var applyOpacity = function (value, immediate) {
      paintOpacity(value);
      var write = function () {
        var src = opacityInput();
        var next = String(Math.round(opValue * 100) / 100);
        if (src) setValue(src, next);
        else withPropsFields(function () { setValue(opacityInput(), next); });
      };
      if (immediate) {
        if (opApplyTimer) {
          clearTimeout(opApplyTimer);
          opApplyTimer = 0;
        }
        write();
        return;
      }
      if (opApplyTimer) return;
      opApplyTimer = setTimeout(function () {
        opApplyTimer = 0;
        write();
      }, 80);
    };
    var setOpacityFromY = function (clientY, immediate) {
      var pop = ensureOpacityPop();
      var track = pop.querySelector('[data-op-track]');
      if (!track) return;
      var r = track.getBoundingClientRect();
      var t = (clientY - r.top) / Math.max(1, r.height);
      applyOpacity(1 - Math.max(0, Math.min(1, t)), immediate);
    };
    var placeOpacityPop = function (anchor) {
      var pop = ensureOpacityPop();
      if (!anchor) return;
      var r = anchor.getBoundingClientRect();
      pop.style.left = Math.round(r.left + r.width / 2 - 24) + 'px';
      pop.style.top = Math.round(r.bottom + 8) + 'px';
      pop.hidden = false;
      requestAnimationFrame(function () {
        var pr = pop.getBoundingClientRect();
        if (pr.bottom > window.innerHeight - 10) {
          pop.style.top = Math.round(r.top - 8 - pr.height) + 'px';
        }
        if (pr.left < 8) pop.style.left = '8px';
        if (pr.right > window.innerWidth - 8)
          pop.style.left = Math.round(window.innerWidth - 8 - pr.width) + 'px';
      });
    };
    var closeOpacityPop = function () {
      opOpen = false;
      opDragging = false;
      var pop = document.getElementById('lu-ctx-op');
      if (pop) pop.hidden = true;
      var btn = document.querySelector('#lu-ctx-bar [data-act="opacity-toggle"]');
      if (btn) btn.classList.remove('is-on');
    };
    var openOpacityPop = function (anchor) {
      ensureOpacityPop();
      opOpen = true;
      paintOpacity(opValue);
      placeOpacityPop(anchor);
      if (anchor) anchor.classList.add('is-on');
    };
    var mountOpacitySlot = function (bar) {
      var slot = bar.querySelector('[data-slot="opacity"]');
      if (!slot || slot.querySelector('[data-act="opacity-toggle"]')) return;
      slot.innerHTML =
        '<button type="button" class="ed-ctx-bar__op" data-act="opacity-toggle" title="투명도">' +
          '<svg class="ed-ctx-bar__op-ico" viewBox="0 0 24 24" aria-hidden="true">' +
            '<defs><pattern id="lu-op-chk2" width="4" height="4" patternUnits="userSpaceOnUse">' +
              '<rect width="4" height="4" fill="#d8cfd3"/>' +
              '<rect width="2" height="2" fill="#fff"/>' +
              '<rect x="2" y="2" width="2" height="2" fill="#fff"/>' +
            '</pattern></defs>' +
            '<rect x="3" y="3" width="18" height="18" rx="4" fill="url(#lu-op-chk2)"/>' +
            '<rect class="ed-ctx-bar__op-tint" x="3" y="3" width="18" height="18" rx="4" fill="currentColor"/>' +
          '</svg>' +
        '</button>';
    };

    var ensureBar = function () {
      var host = document.querySelector('[data-ed-workspace]') ||
        document.querySelector('[data-ed-body]') ||
        document.querySelector('[data-ed-root]');
      if (!host) return null;
      var bar = document.getElementById('lu-ctx-bar');
      if (bar) {
        mountOpacitySlot(bar);
        return bar;
      }
      bar = document.createElement('div');
      bar.id = 'lu-ctx-bar';
      bar.className = 'ed-ctx-bar';
      bar.hidden = true;
      bar.innerHTML =
        '<div class="ed-ctx-bar__row">' +
          '<span class="ed-ctx-bar__slot" data-slot="fill">' +
            '<input type="color" data-act="fill" title="채우기" />' +
          '</span>' +
          '<span class="ed-ctx-bar__slot" data-slot="stroke">' +
            '<input type="color" data-act="stroke" title="테두리 색" />' +
            '<em class="ed-ctx-bar__lab">테두리</em>' +
            '<input type="number" data-act="sw" min="0" step="0.05" title="테두리 굵기(mm)" />' +
          '</span>' +
          '<span class="ed-ctx-bar__slot" data-slot="fit">' +
            '<select data-act="fit" title="이미지 맞춤">' +
              '<option value="contain">맞춤</option>' +
              '<option value="cover">채우기</option>' +
              '<option value="stretch">늘리기</option>' +
            '</select>' +
          '</span>' +
          '<span class="ed-ctx-bar__slot" data-slot="xform">' +
            '<button type="button" data-act="rot-ccw" title="왼쪽으로 90°">⟲</button>' +
            '<button type="button" data-act="rot-cw" title="오른쪽으로 90°">⟳</button>' +
            '<button type="button" data-act="flip-h" title="좌우 반전">↔</button>' +
            '<button type="button" data-act="flip-v" title="상하 반전">↕</button>' +
          '</span>' +
          '<span class="ed-ctx-bar__div" data-slot="color-div"></span>' +
          '<span class="ed-ctx-bar__slot" data-slot="font">' +
            '<select data-act="font" title="글꼴"></select>' +
            '<input type="number" data-act="fs" min="4" max="200" step="0.5" title="크기" />' +
            '<button type="button" data-act="bold" title="굵게">B</button>' +
            '<button type="button" data-act="italic" title="기울임"><i>I</i></button>' +
            '<button type="button" data-act="underline" title="밑줄"><u>U</u></button>' +
            '<button type="button" data-act="align-left" title="왼쪽">좌</button>' +
            '<button type="button" data-act="align-center" title="가운데">중</button>' +
            '<button type="button" data-act="align-right" title="오른쪽">우</button>' +
          '</span>' +
          '<span class="ed-ctx-bar__div" data-slot="font-div"></span>' +
          '<button type="button" data-act="flip">뒤집기</button>' +
          '<span class="ed-ctx-bar__slot" data-slot="opacity">' +
            '<button type="button" class="ed-ctx-bar__op" data-act="opacity-toggle" title="투명도">' +
              '<svg class="ed-ctx-bar__op-ico" viewBox="0 0 24 24" aria-hidden="true">' +
                '<defs><pattern id="lu-op-chk" width="4" height="4" patternUnits="userSpaceOnUse">' +
                  '<rect width="4" height="4" fill="#d8cfd3"/>' +
                  '<rect width="2" height="2" fill="#fff"/>' +
                  '<rect x="2" y="2" width="2" height="2" fill="#fff"/>' +
                '</pattern></defs>' +
                '<rect x="3" y="3" width="18" height="18" rx="4" fill="url(#lu-op-chk)"/>' +
                '<rect class="ed-ctx-bar__op-tint" x="3" y="3" width="18" height="18" rx="4" fill="currentColor"/>' +
              '</svg>' +
            '</button>' +
          '</span>' +
          '<button type="button" data-act="lock" title="잠금">잠금</button>' +
          '<span class="ed-ctx-bar__div"></span>' +
          '<button type="button" data-act="fwd">앞으로</button>' +
          '<button type="button" data-act="back">뒤로</button>' +
          '<button type="button" data-act="dup" title="복제">⧉</button>' +
          '<button type="button" data-act="del" title="삭제">🗑</button>' +
        '</div>';
      host.appendChild(bar);
      mountOpacitySlot(bar);
      bar.addEventListener('pointerdown', function (e) { e.stopPropagation(); });
      bar.addEventListener('mousedown', function (e) { e.stopPropagation(); });
      bar.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-act]') : null;
        if (!btn || btn.tagName === 'INPUT' || btn.tagName === 'SELECT') return;
        var act = btn.getAttribute('data-act');
        if (act === 'opacity-toggle') {
          if (opOpen) closeOpacityPop();
          else openOpacityPop(btn);
          return;
        }
        if (act === 'fwd') return clickFloat('앞으로');
        if (act === 'back') return clickFloat('뒤로');
        if (act === 'dup') return clickFloat('복제');
        if (act === 'del') return clickFloat('삭제');
        if (act === 'rot-cw') return nudgeRotation(90);
        if (act === 'rot-ccw') return nudgeRotation(-90);
        withPropsFields(function () {
          if (act.indexOf('align-') === 0) {
            var map = { 'align-left': 'left', 'align-center': 'center', 'align-right': 'right' };
            setValue(alignSelect(), map[act]);
          } else {
            var re =
              act === 'bold' ? /^굵게$/ :
              act === 'italic' ? /^기울임$/ :
              act === 'underline' ? /^밑줄$/ :
              act === 'flip' || act === 'flip-h' ? /^좌우 반전$/ :
              act === 'flip-v' ? /^상하 반전$/ :
              /속성잠금|잠금/;
            var box = checkByText(re);
            if (box) box.click();
          }
          populate(bar);
        });
      });
      bar.addEventListener('change', function (e) {
        var el = e.target;
        if (!el || !el.getAttribute) return;
        var act = el.getAttribute('data-act');
        if (!act) return;
        withPropsFields(function () {
          if (act === 'font') setValue(fontSelect(), el.value);
          else if (act === 'fill') setValue(fillColor(), el.value);
          else if (act === 'stroke') setValue(strokeColor(), el.value);
          else if (act === 'sw') setValue(strokeWidth(), el.value);
          else if (act === 'fs') setValue(fontSize(), el.value);
          else if (act === 'fit') setValue(imageFit(), el.value);
          populate(bar);
        });
      });
      return bar;
    };
    var toggleSlot = function (bar, name, on) {
      var el = bar.querySelector('[data-slot="' + name + '"]');
      if (el) el.hidden = !on;
    };
    var populate = function (bar) {
      populating = true;
      try {
        var fc = fillColor();
        var sc = strokeColor();
        var sw = strokeWidth();
        var fsSel = fontSelect();
        var fs = fontSize();
        var al = alignSelect();
        var op = opacityInput();
        var fit = imageFit();
        var img = isImageSel();
        var fillEl = bar.querySelector('[data-act="fill"]');
        var strokeEl = bar.querySelector('[data-act="stroke"]');
        var swEl = bar.querySelector('[data-act="sw"]');
        var fontEl = bar.querySelector('[data-act="font"]');
        var sizeEl = bar.querySelector('[data-act="fs"]');
        var fitEl = bar.querySelector('[data-act="fit"]');
        toggleSlot(bar, 'fill', !!fc && !img);
        toggleSlot(bar, 'stroke', !!(sc || sw));
        toggleSlot(bar, 'fit', !!fit);
        toggleSlot(bar, 'xform', img);
        toggleSlot(bar, 'color-div', !!(fc || sc || sw || fit || img));
        var hasText = !!(fsSel || fs || checkByText(/^굵게$/));
        toggleSlot(bar, 'font', hasText);
        toggleSlot(bar, 'font-div', hasText);
        toggleSlot(bar, 'opacity', !!op);
        if (fillEl && fc) fillEl.value = fc.value;
        if (strokeEl && sc) strokeEl.value = sc.value;
        if (swEl) {
          swEl.hidden = !sw;
          if (sw) swEl.value = sw.value;
        }
        if (fontEl) {
          fontEl.hidden = !fsSel;
          if (fsSel) {
            fontEl.innerHTML = fsSel.innerHTML;
            fontEl.value = fsSel.value;
          }
        }
        if (sizeEl) {
          sizeEl.hidden = !fs;
          if (fs) sizeEl.value = fs.value;
        }
        if (op && !opDragging) paintOpacity(parseFloat(op.value || '1') || 0);
        if (fitEl) {
          fitEl.hidden = !fit;
          if (fit) fitEl.value = fit.value;
        }
        var flipBtn = bar.querySelector('[data-act="flip"]');
        var flipH = checkByText(/^좌우 반전$/);
        var flipV = checkByText(/^상하 반전$/);
        if (flipBtn) {
          flipBtn.hidden = img || !flipH;
          flipBtn.classList.toggle('is-on', !!(flipH && flipH.checked));
        }
        [['bold', /^굵게$/], ['italic', /^기울임$/], ['underline', /^밑줄$/], ['lock', /속성잠금|잠금/],
          ['flip-h', /^좌우 반전$/], ['flip-v', /^상하 반전$/]].forEach(function (pair) {
          var btn = bar.querySelector('[data-act="' + pair[0] + '"]');
          var box = checkByText(pair[1]);
          if (btn) {
            if (pair[0] === 'flip-h' || pair[0] === 'flip-v') {
              btn.classList.toggle('is-on', !!(box && box.checked));
            } else {
              btn.hidden = !box;
              btn.classList.toggle('is-on', !!(box && box.checked));
            }
          }
        });
        ['left', 'center', 'right'].forEach(function (v) {
          var btn = bar.querySelector('[data-act="align-' + v + '"]');
          if (btn) {
            btn.hidden = !al;
            btn.classList.toggle('is-on', !!(al && al.value === v));
          }
        });
      } finally {
        populating = false;
      }
    };

    var sync = async function () {
      if (document.documentElement.classList.contains('lu-layer-applying')) return;
      var bar = ensureBar();
      if (!bar) return;
      var selected = hasSelection();
      if (selected) {
        document.documentElement.classList.add('lu-ctx-on');
        bar.hidden = false;
        var key = selectionKey();
        if (busy) return;
        if (key && key === lastKey && bar.getAttribute('data-ready') === '1') return;
        busy = true;
        try {
          await withPropsFields(function () { populate(bar); });
          lastKey = key;
          bar.setAttribute('data-ready', '1');
        } finally {
          busy = false;
        }
        return;
      }
      document.documentElement.classList.remove('lu-ctx-on');
      bar.hidden = true;
      bar.removeAttribute('data-ready');
      lastKey = '';
      closeOpacityPop();
    };
    var queued = false;
    var requestSync = function () {
      if (queued || populating) return;
      queued = true;
      setTimeout(function () {
        queued = false;
        sync();
      }, 50);
    };

    document.addEventListener('pointerup', function (e) {
      if (e.target && e.target.closest && e.target.closest('[data-tut="canvas"], .canvas-stage, .ed-layer-item')) {
        setTimeout(requestSync, 30);
      }
    }, true);
    document.addEventListener('pointerdown', function (e) {
      if (!opOpen || opDragging) return;
      var t = e.target;
      if (t && t.closest && (t.closest('#lu-ctx-op') || t.closest('[data-act="opacity-toggle"]'))) return;
      closeOpacityPop();
    }, true);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && opOpen) closeOpacityPop();
    });

    var mo = new MutationObserver(function (records) {
      var meaningful = false;
      for (var i = 0; i < records.length; i++) {
        var t = records[i].target;
        if (t && t.closest && (t.closest('#lu-ctx-bar') || t.closest('[data-ed-props-panel]'))) continue;
        meaningful = true;
        break;
      }
      if (meaningful) requestSync();
    });
    var start = function () {
      ensureBar();
      requestSync();
      var root = document.querySelector('[data-ed-root]') || document.body;
      mo.observe(root, { subtree: true, childList: true, attributes: true, attributeFilter: ['disabled', 'class'] });
    };
    if (document.querySelector('[data-ed-root], .ed-float-bar, [data-ed-workspace]')) start();
    else {
      var wait = new MutationObserver(function () {
        if (document.querySelector('[data-ed-root], .ed-float-bar, [data-ed-workspace]')) {
          wait.disconnect();
          start();
        }
      });
      wait.observe(document.documentElement, { childList: true, subtree: true });
    }
  },
  bindCreditBadge: function () {
    if (this._creditBadgeBound) return;
    this._creditBadgeBound = true;
    var self = this;
    var state = { balance: 0, page: 1, pages: 1, items: [], loaded: false, guest: false };

    var fmt = function (n) {
      var v = Number(n) || 0;
      return v.toLocaleString('ko-KR') + ' C';
    };
    var when = function (s) {
      return String(s || '').replace('T', ' ').slice(0, 16);
    };

    var ensureChip = function () {
      var host = document.querySelector('[data-ed-root]') || document.querySelector('.ed');
      if (!host) return null;
      var chip = document.getElementById('lu-credit-chip');
      if (chip) return chip;
      chip = document.createElement('button');
      chip.id = 'lu-credit-chip';
      chip.type = 'button';
      chip.className = 'ed-credit-chip';
      chip.hidden = true;
      chip.innerHTML = '<span class="ed-credit-chip__ic" aria-hidden="true">C</span>' +
        '<span class="ed-credit-chip__meta"><em>남은 크레딧</em><strong>0 C</strong></span>';
      host.appendChild(chip);
      chip.addEventListener('click', function () {
        if (state.guest) {
          self.showSaveAuthPrompt().then(function (ok) {
            if (ok) load(1, true);
          });
          return;
        }
        openModal();
      });
      return chip;
    };

    var renderChip = function () {
      var chip = ensureChip();
      if (!chip) return;
      var strong = chip.querySelector('strong');
      var em = chip.querySelector('em');
      if (state.guest) {
        chip.hidden = false;
        chip.classList.add('is-guest');
        chip.title = '로그인하면 남은 크레딧을 볼 수 있습니다';
        if (em) em.textContent = '크레딧';
        if (strong) strong.textContent = '로그인';
        return;
      }
      chip.classList.remove('is-guest');
      chip.hidden = false;
      chip.title = '크레딧 사용 이력 보기';
      if (em) em.textContent = '남은 크레딧';
      if (strong) strong.textContent = fmt(state.balance);
    };

    var rowHtml = function (item) {
      var amt = Number(item.amount) || 0;
      var plus = amt >= 0;
      return '<div class="ed-credit-row">' +
        '<div><strong>' + escapeHtml(item.description || item.tx_type_label || '크레딧') + '</strong>' +
        '<span>' + escapeHtml(when(item.created_at)) + ' · ' + escapeHtml(item.tx_type_label || '') +
        (item.source_label ? ' · ' + escapeHtml(item.source_label) : '') + '</span></div>' +
        '<b class="' + (plus ? 'is-plus' : 'is-minus') + '">' + (plus ? '+' : '') + fmt(amt) + '</b></div>';
    };

    var escapeHtml = function (s) {
      return String(s).replace(/[&<>"']/g, function (ch) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
      });
    };

    var renderModal = function () {
      var box = document.getElementById('lu-credit-modal');
      if (!box) return;
      var bal = box.querySelector('[data-credit-balance]');
      var list = box.querySelector('[data-credit-list]');
      var more = box.querySelector('[data-credit-more]');
      if (bal) bal.textContent = fmt(state.balance);
      if (list) {
        if (!state.items.length) {
          list.innerHTML = '<p class="ed-credit-empty">크레딧 사용·적립 내역이 없습니다.</p>';
        } else {
          list.innerHTML = state.items.map(rowHtml).join('');
        }
      }
      if (more) more.hidden = state.page >= state.pages;
    };

    var openModal = function () {
      var existing = document.getElementById('lu-credit-modal');
      if (existing) existing.remove();
      var root = document.createElement('div');
      root.id = 'lu-credit-modal';
      root.className = 'ed-modal ed-credit-modal';
      root.innerHTML = '<div class="ed-modal__card ed-modal__card--confirm ed-credit-modal__card" role="dialog" aria-modal="true" aria-labelledby="lu-credit-title">' +
        '<div class="ed-modal__head"><div><h3 id="lu-credit-title">크레딧 사용 이력</h3>' +
        '<p>남은 크레딧 <strong data-credit-balance>0 C</strong></p></div>' +
        '<button type="button" class="ed-modal__close" data-credit-close aria-label="닫기">×</button></div>' +
        '<div class="ed-modal__body"><div class="ed-credit-list" data-credit-list></div>' +
        '<button type="button" class="ed-credit-more" data-credit-more hidden>더 보기</button></div>' +
        '<div class="ed-modal__foot"><a class="ed-btn" href="/account#credits">내 계정에서 보기</a>' +
        '<button type="button" class="ed-btn ed-btn--primary" data-credit-close>닫기</button></div></div>';
      document.body.appendChild(root);
      renderModal();
      if (!state.loaded || !state.items.length) load(1, false);
      var close = function () { root.remove(); };
      root.addEventListener('click', function (e) {
        if (e.target === root || (e.target.closest && e.target.closest('[data-credit-close]'))) close();
        if (e.target.closest && e.target.closest('[data-credit-more]')) load(state.page + 1, false);
      });
    };

    var load = async function (page, reset) {
      try {
        var raw = await self.apiGetJson('/api/credit/me?page=' + page + '&per_page=20');
        var data = JSON.parse(raw || '{}');
        state.guest = false;
        state.balance = Number(data.balance) || 0;
        state.page = Number(data.page) || page;
        state.pages = Number(data.pages) || 1;
        var next = Array.isArray(data.items) ? data.items : [];
        state.items = reset || page <= 1 ? next : state.items.concat(next);
        state.loaded = true;
        renderChip();
        renderModal();
      } catch (err) {
        if (err && err.status === 401) {
          state.guest = true;
          state.loaded = true;
          renderChip();
          return;
        }
        var chip = ensureChip();
        if (chip) {
          chip.hidden = false;
          chip.title = (err && err.message) || '크레딧을 불러오지 못했습니다';
        }
      }
    };

    var start = function () {
      ensureChip();
      load(1, true);
    };
    if (document.querySelector('[data-ed-root], .ed')) start();
    else {
      var wait = new MutationObserver(function () {
        if (document.querySelector('[data-ed-root], .ed')) {
          wait.disconnect();
          start();
        }
      });
      wait.observe(document.documentElement, { childList: true, subtree: true });
    }
  },
  bindCloudSave: function () {
    if (this._cloudSaveBound) return;
    this._cloudSaveBound = true;
    var tips = {
      saved: '클라우드에 저장됨',
      unsaved: '수정됨 · 자동저장을 기다립니다',
      saving: '클라우드에 저장하는 중…',
      error: '저장에 실패했습니다',
      off: '자동저장 꺼짐 · 클릭하면 켭니다'
    };
    var sync = function () {
      var label = document.querySelector('.ed-autosave');
      var saved = document.querySelector('.ed-topbar__saved');
      if (!label) return;
      var text = ((saved && saved.textContent) || '').replace(/\s+/g, ' ').trim();
      var current = document.documentElement.getAttribute('data-ed-save');
      var authOpen = !!document.getElementById('lu-save-auth-gate');
      var state = 'saved';
      if (/실패/.test(text)) state = 'error';
      else if (authOpen) state = (saved && saved.classList.contains('is-unsaved')) ? 'unsaved' : 'saved';
      else if (current === 'saving' && !/저장됨|자동저장됨|실패|초안/.test(text)) state = 'saving';
      else if (/수정됨/.test(text) || (saved && saved.classList.contains('is-unsaved'))) state = 'unsaved';
      else if (/저장됨|자동저장됨|초안/.test(text) || (saved && saved.classList.contains('is-saved'))) state = 'saved';
      if (state !== 'saving' && state !== 'error' && !label.classList.contains('is-on'))
        state = 'off';
      document.documentElement.setAttribute('data-ed-save', state);
      var extra = text.replace(/^●\s*(저장됨|수정됨)\s*/g, '').trim();
      var tip = tips[state];
      if (extra && extra !== tip && !/^(저장됨|수정됨|준비됨)$/.test(extra) && tip.indexOf(extra) === -1)
        tip += ' · ' + extra;
      label.title = tip;
      label.setAttribute('aria-label', tip);
    };
    document.addEventListener('click', function (e) {
      if (!e.target || !e.target.closest) return;
      if (e.target.closest('.ed-autosave')) {
        setTimeout(sync, 0);
        setTimeout(sync, 80);
        return;
      }
      if (e.target.closest('[data-tut="save"]')) {
        document.documentElement.setAttribute('data-ed-save', 'saving');
        var label = document.querySelector('.ed-autosave');
        if (label) {
          label.title = tips.saving;
          label.setAttribute('aria-label', tips.saving);
        }
        setTimeout(sync, 50);
        setTimeout(sync, 600);
        setTimeout(sync, 2000);
      }
    }, true);
    var mo = new MutationObserver(sync);
    var start = function () {
      sync();
      var root = document.querySelector('.ed-topbar') || document.body;
      mo.observe(root, { subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: ['class'] });
      var bodyWatch = new MutationObserver(function () {
        if (document.getElementById('lu-save-auth-gate')) sync();
      });
      bodyWatch.observe(document.body, { childList: true });
    };
    if (document.querySelector('.ed-autosave')) start();
    else {
      var wait = new MutationObserver(function () {
        if (document.querySelector('.ed-autosave')) {
          wait.disconnect();
          start();
        }
      });
      wait.observe(document.documentElement, { childList: true, subtree: true });
    }
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
      (async function () {
      var existing = document.getElementById('lu-save-auth-gate');
      if (existing) existing.remove();

      var oauthEnabled = { naver: true, kakao: true, google: true };
      try {
        var oauthRes = await fetch(self.apiUrl('/api/auth/oauth'), {
          method: 'GET',
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        });
        var oauthJson = await oauthRes.json().catch(function () { return null; });
        if (oauthJson && oauthJson.data) {
          oauthEnabled.naver = !!oauthJson.data.naver;
          oauthEnabled.kakao = !!oauthJson.data.kakao;
          oauthEnabled.google = !!oauthJson.data.google;
        }
      } catch (e) { /* keep defaults */ }

      var returnPath = '/editor/';
      try {
        returnPath = window.location.pathname + window.location.search;
        if (!returnPath || returnPath.charAt(0) !== '/') returnPath = '/editor/';
      } catch (e) { /* ignore */ }
      var redirectQ = '?redirect=' + encodeURIComponent(returnPath);
      var divider = '<div class="lu-auth-gate__divider"><span>또는</span></div>';
      var socialHtml = function (mode) {
        var verb = mode === 'register' ? '가입' : '로그인';
        var row = mode === 'register' ? ' lu-auth-gate__social--row' : '';
        var compact = mode === 'register' ? ' lu-auth-gate__social-btn--compact' : '';
        var providers = [
          { key: 'naver', label: '네이버로 ' + verb, icon: '/assets/icon-naver.svg' },
          { key: 'kakao', label: '카카오로 ' + verb, icon: '/assets/icon-kakao.svg' },
          { key: 'google', label: '구글로 ' + verb, icon: '/assets/icon-google.svg' }
        ];
        return '<div class="lu-auth-gate__social' + row + '">' + providers.map(function (p) {
          if (oauthEnabled[p.key]) {
            return '<a class="lu-auth-gate__social-btn' + compact + '" href="' + self.apiUrl('/auth/' + p.key) + redirectQ + '">' +
              '<img src="' + p.icon + '" alt="">' +
              '<span>' + p.label + '</span></a>';
          }
          return '<button type="button" class="lu-auth-gate__social-btn' + compact + '" disabled title="키 설정 후 이용 가능">' +
            '<img src="' + p.icon + '" alt="">' +
            '<span>' + p.label + '</span></button>';
        }).join('') + '</div>';
      };

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
        divider +
        socialHtml('login') +
        '  </form>' +
        '  <form class="lu-auth-gate__form" data-lu-auth-form="register" hidden>' +
        socialHtml('register') +
        divider +
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
      })();
    });
  },

  richText: {
    _el: null,
    _dot: null,
    _saved: null,
    bind: function (el, dotnet) {
      if (!el) return;
      this._el = el;
      this._dot = dotnet;
      if (el._luRichBound) return;
      el._luRichBound = true;
      el.setAttribute('contenteditable', 'true');
      el.setAttribute('spellcheck', 'false');
      el.setAttribute('role', 'textbox');
      var self = this;
      var emit = function () {
        if (!self._dot) return;
        try { self._dot.invokeMethodAsync('OnRichModel', JSON.stringify(self.read(el))); }
        catch (e) { /* ignore */ }
      };
      var caret = function () {
        self.saveSel(el);
        if (!self._dot) return;
        try { self._dot.invokeMethodAsync('OnRichCaret', JSON.stringify(self.query(el))); }
        catch (e) { /* ignore */ }
      };
      el.addEventListener('input', emit);
      el.addEventListener('keyup', caret);
      el.addEventListener('mouseup', caret);
      el.addEventListener('focus', function () { el._luRichFocus = true; caret(); });
      el.addEventListener('blur', function () {
        el._luRichFocus = false;
        emit();
        if (self._dot) {
          try { self._dot.invokeMethodAsync('OnRichBlur'); } catch (e) { /* ignore */ }
        }
      });
      el.addEventListener('paste', function (e) {
        e.preventDefault();
        var t = ((e.clipboardData || window.clipboardData).getData('text/plain') || '');
        document.execCommand('insertText', false, t);
      });
    },
    setHtml: function (el, html) {
      if (!el) return;
      if (el._luRichFocus) return;
      if (el.innerHTML === html) return;
      el.innerHTML = html || '';
    },
    saveSel: function (el) {
      var node = el || this._el;
      var sel = window.getSelection();
      if (!sel || sel.rangeCount === 0) return;
      var a = sel.anchorNode;
      if (!node || !a || !node.contains(a)) return;
      this._saved = sel.getRangeAt(0).cloneRange();
    },
    restoreSel: function (el) {
      if (!this._saved) return false;
      var node = el || this._el;
      try {
        node.focus();
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(this._saved);
        return true;
      } catch (e) {
        return false;
      }
    },
    hasSelection: function (el) {
      var node = el || this._el;
      if (this._saved && this._saved.toString().length > 0) return true;
      var sel = window.getSelection();
      if (!sel || sel.rangeCount === 0 || sel.isCollapsed) return false;
      var a = sel.anchorNode, f = sel.focusNode;
      return !!(node && a && f && node.contains(a) && node.contains(f));
    },
    apply: function (el, cmd, value) {
      var node = el || this._el;
      if (!node) return;
      node.focus();
      this.restoreSel(node);
      try { document.execCommand('styleWithCSS', false, true); } catch (e) { /* ignore */ }
      if (cmd === 'bold') document.execCommand('bold');
      else if (cmd === 'italic') document.execCommand('italic');
      else if (cmd === 'underline') document.execCommand('underline');
      else if (cmd === 'strikeThrough') document.execCommand('strikeThrough');
      else if (cmd === 'align') {
        var map = { left: 'justifyLeft', center: 'justifyCenter', right: 'justifyRight', justify: 'justifyFull' };
        document.execCommand(map[value] || 'justifyLeft');
      } else if (cmd === 'font') this.paint(node, { fontFamily: value });
      else if (cmd === 'size') this.paint(node, { fontSize: String(value) + 'pt' });
      else if (cmd === 'color') this.paint(node, { color: value });
      if (this._dot) {
        try { this._dot.invokeMethodAsync('OnRichModel', JSON.stringify(this.read(node))); }
        catch (e) { /* ignore */ }
      }
    },
    paint: function (el, styles) {
      document.execCommand('fontName', false, 'LU_MARK');
      var nodes = el.querySelectorAll('font, span, b, i, u, strong, em');
      for (var i = 0; i < nodes.length; i++) {
        var n = nodes[i];
        var face = (n.getAttribute && n.getAttribute('face')) || '';
        var ff = (n.style && n.style.fontFamily) || '';
        if (face.indexOf('LU_MARK') < 0 && ff.indexOf('LU_MARK') < 0) continue;
        if (n.removeAttribute) n.removeAttribute('face');
        if (n.style) {
          if ((n.style.fontFamily || '').indexOf('LU_MARK') >= 0)
            n.style.fontFamily = styles.fontFamily || '';
          Object.keys(styles).forEach(function (k) { n.style[k] = styles[k]; });
        }
      }
    },
    query: function (el) {
      var node = el || this._el;
      var sel = window.getSelection();
      var cur = (sel && sel.anchorNode) || node;
      if (cur && cur.nodeType === 3) cur = cur.parentElement;
      var st = this.styleOf(cur || node, this.styleOf(node, {
        fontFamily: 'Pretendard', fontSizePt: 12, fill: '#2e2a27',
        bold: false, italic: false, underline: false, strikeout: false
      }));
      var align = 'left';
      try {
        if (document.queryCommandState('justifyCenter')) align = 'center';
        else if (document.queryCommandState('justifyRight')) align = 'right';
        st.bold = document.queryCommandState('bold') || st.bold;
        st.italic = document.queryCommandState('italic') || st.italic;
        st.underline = document.queryCommandState('underline') || st.underline;
      } catch (e) { /* ignore */ }
      st.align = align;
      return st;
    },
    styleOf: function (node, inherit) {
      inherit = inherit || {
        fontFamily: 'Pretendard', fontSizePt: 12, fill: '#2e2a27',
        bold: false, italic: false, underline: false, strikeout: false
      };
      if (!node || node.nodeType !== 1) return inherit;
      var cs = window.getComputedStyle(node);
      var dec = ((cs.textDecorationLine || cs.textDecoration || '') + '');
      var fam = (cs.fontFamily || '').split(',')[0].replace(/['"]/g, '').trim();
      var px = parseFloat(cs.fontSize) || 16;
      return {
        fontFamily: fam || inherit.fontFamily,
        fontSizePt: +(px * 72 / 96).toFixed(2),
        fill: this.rgbToHex(cs.color) || inherit.fill,
        bold: (cs.fontWeight + '') === 'bold' || parseInt(cs.fontWeight, 10) >= 600,
        italic: (cs.fontStyle || '') === 'italic' || (cs.fontStyle || '') === 'oblique',
        underline: dec.indexOf('underline') >= 0,
        strikeout: dec.indexOf('line-through') >= 0
      };
    },
    rgbToHex: function (c) {
      if (!c) return '';
      if (c[0] === '#') {
        return c.length === 4
          ? ('#' + c[1] + c[1] + c[2] + c[2] + c[3] + c[3]).toLowerCase()
          : c.slice(0, 7).toLowerCase();
      }
      var m = String(c).match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
      if (!m) return '';
      return '#' + [m[1], m[2], m[3]].map(function (x) {
        return ('0' + parseInt(x, 10).toString(16)).slice(-2);
      }).join('');
    },
    sameStyle: function (a, b) {
      return a.fontFamily === b.fontFamily
        && Math.abs(a.fontSizePt - b.fontSizePt) < 0.25
        && a.fill === b.fill
        && a.bold === b.bold
        && a.italic === b.italic
        && a.underline === b.underline
        && a.strikeout === b.strikeout;
    },
    read: function (el) {
      var self = this;
      var paras = [];
      var inherit = this.styleOf(el, {
        fontFamily: 'Pretendard', fontSizePt: 12, fill: '#2e2a27',
        bold: false, italic: false, underline: false, strikeout: false
      });
      function addSpan(para, text, st) {
        if (!text) return;
        var last = para.spans[para.spans.length - 1];
        if (last && self.sameStyle(last, st)) last.text += text;
        else para.spans.push(Object.assign({ text: text }, st));
      }
      function newPara(align) {
        var a = align || 'left';
        if (a === 'start') a = 'left';
        if (a === 'end') a = 'right';
        return { align: a, spans: [] };
      }
      var rootAlign = (window.getComputedStyle(el).textAlign || 'left');
      var para = newPara(rootAlign);
      function walk(node, st, align) {
        if (node.nodeType === 3) {
          addSpan(para, String(node.nodeValue || '').replace(/\u00a0/g, ' '), st);
          return;
        }
        if (node.nodeType !== 1) return;
        var tag = (node.tagName || '').toLowerCase();
        if (tag === 'br') {
          paras.push(para);
          para = newPara(align);
          return;
        }
        var next = self.styleOf(node, st);
        var al = (window.getComputedStyle(node).textAlign || align || 'left');
        var block = tag === 'div' || tag === 'p' || tag === 'li';
        if (block && (para.spans.length > 0 || paras.length > 0)) {
          paras.push(para);
          para = newPara(al);
        } else if (block) {
          para.align = newPara(al).align;
        }
        for (var i = 0; i < node.childNodes.length; i++)
          walk(node.childNodes[i], next, al);
      }
      for (var i = 0; i < el.childNodes.length; i++)
        walk(el.childNodes[i], inherit, rootAlign);
      paras.push(para);
      if (paras.length === 0) paras.push(newPara(rootAlign));
      return { paragraphs: paras };
    }
  }
};

(function () {
  var apply = function () {
    if (window.labelUpEditor && typeof window.labelUpEditor.applyMobileChrome === 'function')
      window.labelUpEditor.applyMobileChrome();
    if (window.labelUpEditor && typeof window.labelUpEditor.bindCloudSave === 'function')
      window.labelUpEditor.bindCloudSave();
    if (window.labelUpEditor && typeof window.labelUpEditor.bindZoomSlider === 'function')
      window.labelUpEditor.bindZoomSlider();
    if (window.labelUpEditor && typeof window.labelUpEditor.bindTutorialDock === 'function')
      window.labelUpEditor.bindTutorialDock();
    if (window.labelUpEditor && typeof window.labelUpEditor.bindLayerNudge === 'function')
      window.labelUpEditor.bindLayerNudge();
    if (window.labelUpEditor && typeof window.labelUpEditor.bindLayerDrag === 'function')
      window.labelUpEditor.bindLayerDrag();
    if (window.labelUpEditor && typeof window.labelUpEditor.bindCreditBadge === 'function')
      window.labelUpEditor.bindCreditBadge();
    if (window.labelUpEditor && typeof window.labelUpEditor.bindCanvaToolbar === 'function')
      window.labelUpEditor.bindCanvaToolbar();
    if (window.labelUpEditor && typeof window.labelUpEditor.bindPaperPreviewLayout === 'function')
      window.labelUpEditor.bindPaperPreviewLayout();
    if (window.labelUpEditor && typeof window.labelUpEditor.bindLabiDialogChrome === 'function')
      window.labelUpEditor.bindLabiDialogChrome();
  };
  window.addEventListener('resize', apply);
  window.addEventListener('orientationchange', apply);
  document.addEventListener('click', function (e) {
    var root = document.querySelector('[data-ed-root]');
    var props = document.querySelector('[data-ed-props-panel]');
    var preview = document.querySelector('[data-ed-preview-panel]');
    var t = e.target && e.target.closest ? e.target.closest('.ed.is-mobile .ed-props__min') : null;
    if (t) {
      if (t.closest && t.closest('[data-ed-preview-panel]')) {
        if (preview) preview.classList.remove('is-m-open');
        if (root) root.classList.remove('is-preview-open');
        return;
      }
      if (props) props.classList.remove('is-m-open');
      if (root) root.classList.remove('is-props-open');
      return;
    }
    if (root && root.classList.contains('is-props-open') && props) {
      if (e.target.closest && (e.target.closest('[data-ed-props-panel]') || e.target.closest('.ed-m-props'))) return;
      props.classList.remove('is-m-open');
      root.classList.remove('is-props-open');
    }
    if (root && root.classList.contains('is-preview-open') && preview) {
      if (e.target.closest && (e.target.closest('[data-ed-preview-panel]') || e.target.closest('.ed-m-preview'))) return;
      preview.classList.remove('is-m-open');
      root.classList.remove('is-preview-open');
    }
  });
  if (document.readyState === 'loading')
    document.addEventListener('DOMContentLoaded', apply);
  else
    apply();
})();
