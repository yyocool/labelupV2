(function () {
  var cfg = window.LABELUP_QR_PRINT_TPL || {};
  var pendingIds = [];
  var markUrl = cfg.markPrintedUrl || '';

  var modal = document.getElementById('qrLabelPrintModal');
  var frame = document.getElementById('qrLabelPrintFrame');
  var meta = document.getElementById('qrLabelPrintMeta');
  var printBtn = document.getElementById('qrLabelPrintDoBtn');
  if (!modal || !frame) {
    window.LabelUpQrLabelPrint = { open: function () {} };
    return;
  }

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/"/g, '&quot;');
  }

  function openModal() { modal.hidden = false; }
  function closeModal() { modal.hidden = true; }

  modal.querySelectorAll('[data-close="qrLabelPrintModal"]').forEach(function (btn) {
    btn.addEventListener('click', closeModal);
  });

  async function loadTemplate() {
    var res = await AdminAPI.get(cfg.loadUrl);
    return (res && res.data) || {};
  }

  function slots(paper) {
    var out = [];
    var cols = Math.max(1, Number(paper.columns) || 1);
    var rows = Math.max(1, Number(paper.rows) || 1);
    var i = 0;
    for (var r = 0; r < rows; r++) {
      for (var c = 0; c < cols; c++) {
        out.push({
          i: i++,
          x: Number(paper.leftMarginMm) + c * (Number(paper.labelWidthMm) + Number(paper.hGapMm)),
          y: Number(paper.topMarginMm) + r * (Number(paper.labelHeightMm) + Number(paper.vGapMm)),
          w: Number(paper.labelWidthMm),
          h: Number(paper.labelHeightMm),
        });
      }
    }
    return out;
  }

  function bindPayload(o, coupon) {
    var raw = String(o.payload || '{{coupon_url}}');
    if (raw === '{{coupon_url}}') return coupon.coupon_page_url || '';
    if (raw === '{{coupon_code}}') return coupon.code || '';
    return raw
      .replace(/\{\{coupon_code\}\}/g, coupon.code || '')
      .replace(/\{\{coupon_url\}\}/g, coupon.coupon_page_url || '');
  }

  function bindText(o, coupon) {
    return String(o.text || '')
      .replace(/\{\{coupon_code\}\}/g, coupon.code || '')
      .replace(/\{\{coupon_url\}\}/g, coupon.coupon_page_url || '');
  }

  function qrSrc(payload) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&margin=0&data=' +
      encodeURIComponent(payload || '');
  }

  function objHtml(o, coupon) {
    var rot = Number(o.rotation || 0);
    var op = o.opacity == null ? 1 : o.opacity;
    var style = 'left:' + Number(o.x) + 'mm;top:' + Number(o.y) + 'mm;width:' + Number(o.w) +
      'mm;height:' + Number(o.h) + 'mm;transform:rotate(' + rot + 'deg);opacity:' + op + ';';
    var inner = '';
    if (o.type === 'qr') {
      inner = '<img alt="QR" src="' + esc(qrSrc(bindPayload(o, coupon))) + '">';
    } else if (o.type === 'text') {
      var align = o.align || 'left';
      var sizeMm = (Number(o.fontSize) || 9) * 0.3528;
      inner = '<div class="txt" style="text-align:' + align + ';font-family:' +
        esc(o.fontFamily || 'Pretendard') + ';font-size:' + sizeMm +
        'mm;font-weight:' + (o.fontWeight || 700) + ';color:' + esc(o.fill || '#2E2A27') +
        '">' + esc(bindText(o, coupon)).replace(/\n/g, '<br>') + '</div>';
    } else if (o.type === 'barcode') {
      inner = '<div class="barcode" title="' + esc(bindPayload(o, coupon)) + '"></div>';
    } else if (o.type === 'image' && o.src) {
      inner = '<img alt="" src="' + esc(o.src) + '">';
    } else if (o.type === 'shape') {
      var kind = o.shapeKind || 'rect';
      inner = '<div class="shape is-' + esc(kind) + '" style="background:' + esc(o.fill || '#7B2840') +
        ';border:' + (Number(o.strokeWidth) || 0) + 'mm solid ' + esc(o.stroke || '#7B2840') + '"></div>';
    }
    return '<div class="obj" style="' + style + '">' + inner + '</div>';
  }

  function labelHtml(slot, coupon, objects, shape) {
    var radius = shape === 'ellipse' ? '50%' : (shape === 'roundrect' ? '2mm' : '0');
    var empty = !coupon;
    var body = empty ? '' : objects.map(function (o) { return objHtml(o, coupon); }).join('');
    return '<div class="label' + (empty ? ' is-empty' : '') + '" style="left:' + slot.x +
      'mm;top:' + slot.y + 'mm;width:' + slot.w + 'mm;height:' + slot.h +
      'mm;border-radius:' + radius + '">' + body + '</div>';
  }

  function sheetsHtml(coupons, paper, objects) {
    var cells = slots(paper);
    var per = Math.max(1, cells.length);
    var pages = Math.max(1, Math.ceil(coupons.length / per));
    var html = '';
    for (var p = 0; p < pages; p++) {
      html += '<section class="sheet">';
      for (var i = 0; i < per; i++) {
        var coupon = coupons[p * per + i] || null;
        html += labelHtml(cells[i], coupon, objects, paper.shape || 'roundrect');
      }
      html += '</section>';
    }
    return html;
  }

  function printDocument(coupons, paper, objects) {
    var bg = '#ffffff';
    return '<!doctype html><html lang="ko"><head><meta charset="utf-8"><title>라벨 인쇄</title>' +
      '<style>' +
      '@page{size:A4 portrait;margin:0}' +
      'html,body{margin:0;padding:0;background:#fff;font-family:Pretendard,"Malgun Gothic",sans-serif}' +
      '.sheet{width:210mm;height:297mm;position:relative;overflow:hidden;background:' + bg +
      ';page-break-after:always;box-sizing:border-box}' +
      '.sheet:last-child{page-break-after:auto}' +
      '.label{position:absolute;overflow:hidden;box-sizing:border-box}' +
      '.label.is-empty{outline:0.15mm dashed #ddd}' +
      '.obj{position:absolute;overflow:hidden;box-sizing:border-box}' +
      '.obj img{width:100%;height:100%;object-fit:contain;display:block}' +
      '.txt{width:100%;height:100%;display:flex;align-items:center;white-space:pre-wrap;line-height:1.15}' +
      '.barcode{width:100%;height:100%;background:repeating-linear-gradient(90deg,#111 0 0.35mm,#fff 0.35mm 0.7mm)}' +
      '.shape{width:100%;height:100%;box-sizing:border-box}' +
      '.shape.is-roundrect{border-radius:12%}' +
      '.shape.is-ellipse{border-radius:50%}' +
      '@media screen{body{background:#5c5854;padding:12px 0}' +
      '.sheet{margin:0 auto 12px;box-shadow:0 8px 24px rgba(0,0,0,.28)}}' +
      '@media print{body{background:#fff;padding:0}.sheet{margin:0;box-shadow:none}.label.is-empty{outline:none}}' +
      '</style></head><body>' + sheetsHtml(coupons, paper, objects) + '</body></html>';
  }

  function waitImages(doc) {
    var imgs = Array.prototype.slice.call(doc.images || []);
    return Promise.all(imgs.map(function (img) {
      if (img.complete) return Promise.resolve();
      return new Promise(function (resolve) {
        img.onload = img.onerror = function () { resolve(); };
      });
    }));
  }

  async function markPrinted(ids) {
    if (!markUrl || !ids.length) return;
    await AdminAPI.post(markUrl, { ids: ids });
    document.dispatchEvent(new CustomEvent('qr-coupons-printed', { detail: { ids: ids } }));
  }

  async function open(coupons) {
    coupons = Array.isArray(coupons) ? coupons.filter(function (c) { return c && c.code; }) : [];
    if (!coupons.length) {
      showAdminAlert('인쇄할 쿠폰이 없습니다.', 'error');
      return;
    }
    pendingIds = coupons.map(function (c) { return Number(c.id || 0); }).filter(function (id) { return id > 0; });
    var tpl = await loadTemplate();
    var paper = tpl.paper || {
      labelWidthMm: 70, labelHeightMm: 36, columns: 2, rows: 7,
      leftMarginMm: 32.5, topMarginMm: 13.5, hGapMm: 5, vGapMm: 3,
      labelsPerSheet: 14, shape: 'roundrect',
    };
    var objects = Array.isArray(tpl.objects) ? tpl.objects : [];
    var pages = Math.max(1, Math.ceil(coupons.length / Math.max(1, Number(paper.labelsPerSheet) || 14)));
    if (meta) {
      meta.textContent = coupons.length.toLocaleString() + '개 · A4 ' + pages + '장 · ' +
        (paper.name || paper.sku || '라벨지') + ' · ' +
        (Number(paper.columns) || 0) + '×' + (Number(paper.rows) || 0);
    }
    var html = printDocument(coupons, paper, objects);
    var doc = frame.contentDocument;
    doc.open();
    doc.write(html);
    doc.close();
    frame.style.height = (pages * 320) + 'mm';
    openModal();
    await waitImages(doc);
  }

  if (printBtn) {
    printBtn.addEventListener('click', async function () {
      var win = frame.contentWindow;
      if (!win) return;
      printBtn.disabled = true;
      try {
        await waitImages(frame.contentDocument);
        win.focus();
        win.print();
        try {
          await markPrinted(pendingIds);
          showAdminAlert('인쇄 대화상자를 열었습니다. 해당 쿠폰을 인쇄완료로 표시했습니다.', 'success');
        } catch (err) {
          showAdminAlert(err.message || '인쇄완료 표시에 실패했습니다.', 'error');
        }
      } finally {
        printBtn.disabled = false;
      }
    });
  }

  window.LabelUpQrLabelPrint = { open: open };
})();
