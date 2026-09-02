window.labelUpEditor = {
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
    if (typeof this.closeImportFan === 'function') this.closeImportFan();
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
    var wrap = document.querySelector('[data-ed-import-fab-wrap]');
    if (wrap) wrap.classList.remove('is-open');
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
  }
};
