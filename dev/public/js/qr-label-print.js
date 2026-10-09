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

  async function loadTemplate(groupNo) {
    var base = cfg.loadUrl || '/api/admin/qr-coupons/print-template';
    var key = Number(groupNo || 0) > 0 ? ('group-' + Number(groupNo)) : 'default';
    var sep = base.indexOf('?') >= 0 ? '&' : '?';
    var res = await AdminAPI.get(base + sep + 'key=' + encodeURIComponent(key));
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
      // 칸을 .shift 로 한 번 감싼다. 위치·배율 보정은 이 껍데기 하나만 움직이면 되고,
      // 쪽을 이루는 .sheet 는 종이 그대로 남아 있어야 쪽 넘김과 인쇄 영역이 흔들리지 않는다.
      html += '<section class="sheet"><div class="shift">';
      for (var i = 0; i < per; i++) {
        var coupon = coupons[p * per + i] || null;
        html += labelHtml(cells[i], coupon, objects, paper.shape || 'roundrect');
      }
      html += '</div></section>';
    }
    return html;
  }

  // 용지 크기를 A4 로 박아 두면 규격이 A3·B5 인 라벨지에서 칸 자리가 통째로 어긋난다.
  // 저장된 템플릿에 크기가 없던 옛 항목만 A4 로 본다.
  function pageMm(paper) {
    var w = Number(paper.paperWidthMm);
    var h = Number(paper.paperHeightMm);
    return { w: w > 0 ? w : 210, h: h > 0 ? h : 297 };
  }

  function printDocument(coupons, paper, objects) {
    var bg = '#ffffff';
    var page = pageMm(paper);
    // @page 에도 같은 크기를 준다. 여기가 A4 로 남아 있으면 브라우저가 용지에 맞춰
    // 통째로 축소해 버려서, 아래에서 mm 로 잡은 자리가 전부 조금씩 줄어든다.
    return '<!doctype html><html lang="ko"><head><meta charset="utf-8"><title>라벨 인쇄</title>' +
      '<style>' +
      '@page{size:' + page.w + 'mm ' + page.h + 'mm;margin:0}' +
      'html,body{margin:0;padding:0;background:#fff;font-family:Pretendard,"Malgun Gothic",sans-serif}' +
      '.sheet{width:' + page.w + 'mm;height:' + page.h + 'mm;position:relative;overflow:hidden;background:' + bg +
      ';page-break-after:always;box-sizing:border-box}' +
      '.sheet:last-child{page-break-after:auto}' +
      // 보정은 왼쪽 위를 기준으로 건다. 프린터 이송 오차는 종이가 물리기 시작하는
      // 그 지점부터 쌓이므로, 기준을 가운데에 두면 윗부분이 도리어 어긋난다.
      '.shift{position:absolute;inset:0;transform-origin:0 0}' +
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
      '</style>' +
      // 보정은 이 빈 덩이에만 쓴다. 화살표를 누를 때마다 문서를 통째로 다시 쓰면
      // QR 그림을 바깥 서비스에서 전부 새로 받아야 해서 한 번에 수십 번 요청이 나간다.
      '<style id="qrPrintCalibStyle"></style>' +
      '</head><body>' + sheetsHtml(coupons, paper, objects) + '</body></html>';
  }

  // ── 인쇄 위치·배율 보정 ──────────────────────────────────────────────
  //
  // 배율 보정은 라벨 편집기와 같은 자리에 담는다. 보정값은 디자인의 성질이 아니라 그
  // 프린터의 성질이라, 같은 프린터로 뽑는 한 편집기에서 맞춘 값이 여기에도 그대로 맞다.
  // 편집기(PrintCalibration.cs)가 System.Text.Json 으로 읽고 쓰므로 글쇠 이름은 X·Y 여야 한다.
  var CALIB_KEY = 'labelup.print.calibration.v1';
  // 위치는 용지마다 다르므로 템플릿별로 담는다. 프린터가 종이를 무는 자리에서 생기는
  // 값이라 서버에 올리지 않고 이 브라우저에만 둔다.
  var OFFSET_KEY = 'labelup.qrprint.offset.v1';
  // 오타 한 번에 인쇄물을 버리지 않게 막는 울타리. 편집기와 같은 범위다.
  var CALIB_MIN = 95;
  var CALIB_MAX = 105;

  var view = { paper: null, templateKey: 'default', ox: 0, oy: 0, sx: 100, sy: 100 };

  var ctl = {
    xy: document.getElementById('qrPrintXY'),
    left: document.getElementById('qrPrintLeft'),
    right: document.getElementById('qrPrintRight'),
    up: document.getElementById('qrPrintUp'),
    down: document.getElementById('qrPrintDown'),
    designX: document.getElementById('qrCalibDesignX'),
    designY: document.getElementById('qrCalibDesignY'),
    measuredX: document.getElementById('qrCalibMeasuredX'),
    measuredY: document.getElementById('qrCalibMeasuredY'),
    now: document.getElementById('qrCalibNow'),
    reset: document.getElementById('qrCalibReset'),
  };

  function readJson(key) {
    try { return JSON.parse(localStorage.getItem(key) || 'null'); } catch (e) { return null; }
  }

  function writeJson(key, value) {
    // 시크릿 창이나 저장용량이 찬 브라우저에서는 쓰기가 막힌다. 보정은 없어도 인쇄는
    // 되어야 하므로 삼킨다.
    try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* ignore */ }
  }

  function clampPct(v) {
    var n = Number(v);
    if (!Number.isFinite(n)) return 100;
    return Math.min(CALIB_MAX, Math.max(CALIB_MIN, n));
  }

  function loadCalibration() {
    var saved = readJson(CALIB_KEY);
    view.sx = saved ? clampPct(saved.X) : 100;
    view.sy = saved ? clampPct(saved.Y) : 100;
  }

  function saveCalibration() {
    writeJson(CALIB_KEY, { X: view.sx, Y: view.sy });
  }

  function loadOffset(key) {
    var hit = (readJson(OFFSET_KEY) || {})[key];
    view.ox = hit && Number.isFinite(Number(hit.x)) ? Number(hit.x) : 0;
    view.oy = hit && Number.isFinite(Number(hit.y)) ? Number(hit.y) : 0;
  }

  function saveOffset() {
    var all = readJson(OFFSET_KEY) || {};
    all[view.templateKey] = { x: view.ox, y: view.oy };
    writeJson(OFFSET_KEY, all);
  }

  // 첫 칼선부터 마지막 칼선까지의 길이. 사용자가 자를 대는 구간과 같아야 하므로
  // 열·행으로 셈하지 않고 실제 칸 자리에서 뽑는다.
  function designSpan(paper) {
    var cells = slots(paper || {});
    if (!cells.length) return { x: 0, y: 0 };
    var minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
    cells.forEach(function (s) {
      minX = Math.min(minX, s.x);
      maxX = Math.max(maxX, s.x + s.w);
      minY = Math.min(minY, s.y);
      maxY = Math.max(maxY, s.y + s.h);
    });
    return { x: maxX - minX, y: maxY - minY };
  }

  function applyTransform() {
    var doc = frame.contentDocument;
    var el = doc && doc.getElementById('qrPrintCalibStyle');
    if (!el) return;
    var move = 'translate(' + view.ox + 'mm,' + view.oy + 'mm)';
    var zoom = 'scale(' + (view.sx / 100) + ',' + (view.sy / 100) + ')';
    // 화면에는 위치만 건다. 0.4% 쯤의 배율은 눈에 보이지도 않으면서 쪽 크기만 어긋나게
    // 만든다. 편집기 미리보기도 같은 까닭으로 배율을 빼고 그린다.
    //
    // 인쇄에서는 배율을 먼저 건다. '아래로 2mm' 는 종이 위에서 2mm 라야 하므로 그 값도
    // 배율과 함께 눌려 나가야 한다. 순서를 바꾸면 보정을 걸수록 위치가 조금씩 틀어진다.
    el.textContent = '.shift{transform:' + move + '}' +
      '@media print{.shift{transform:' + zoom + ' ' + move + '}}';
  }

  function fmt(n) {
    var v = Number(n);
    return Number.isFinite(v) ? String(Math.round(v * 1000) / 1000) : '0';
  }

  function setVal(input, v) {
    if (input) input.value = fmt(v);
  }

  function syncControls() {
    if (ctl.xy) ctl.xy.textContent = fmt(view.ox) + ', ' + fmt(view.oy);
    setVal(ctl.left, -view.ox);
    setVal(ctl.right, view.ox);
    setVal(ctl.up, -view.oy);
    setVal(ctl.down, view.oy);

    // 저장된 배율을 실측 칸으로 되돌려 보여 준다. 배율만 적어 두면 다음에 열었을 때
    // 그 값이 어디서 나온 것인지 알 수 없어 고치기가 겁난다.
    var span = designSpan(view.paper);
    setVal(ctl.designX, span.x);
    setVal(ctl.designY, span.y);
    setVal(ctl.measuredX, view.sx > 0.01 ? span.x * 100 / view.sx : span.x);
    setVal(ctl.measuredY, view.sy > 0.01 ? span.y * 100 / view.sy : span.y);

    if (ctl.now) ctl.now.textContent = '지금 보정: 가로 ' + fmt(view.sx) + '% · 세로 ' + fmt(view.sy) + '%.';
    if (ctl.reset) {
      ctl.reset.hidden = Math.abs(view.sx - 100) < 0.0005 && Math.abs(view.sy - 100) < 0.0005;
    }
  }

  function setOffset(x, y) {
    view.ox = Number.isFinite(Number(x)) ? Number(x) : 0;
    view.oy = Number.isFinite(Number(y)) ? Number(y) : 0;
    saveOffset();
    applyTransform();
    syncControls();
  }

  // 설계 길이가 실제로 몇 mm 로 찍혔는지로 배율을 낸다. 271mm 로 나온 270mm 는
  // 270/271 = 99.631% 로 줄여 보내야 270mm 가 된다. 나눗셈 방향을 거꾸로 잡는 일이
  // 없도록 자를 댄 값을 그대로 받는다.
  function pctFromMeasurement(designMm, measuredMm) {
    return designMm > 0.01 && measuredMm > 0.01
      ? clampPct(designMm / measuredMm * 100)
      : 100;
  }

  function bindControls() {
    document.querySelectorAll('.qr-print-pad [data-nudge]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var d = btn.getAttribute('data-nudge').split(',');
        setOffset(view.ox + Number(d[0]), view.oy + Number(d[1]));
      });
    });

    [ctl.left, ctl.right, ctl.up, ctl.down].forEach(function (input) {
      if (!input) return;
      input.addEventListener('change', function () {
        var v = Number(input.value);
        if (!Number.isFinite(v)) return;
        v *= Number(input.getAttribute('data-sign'));
        if (input.getAttribute('data-axis') === 'y') setOffset(view.ox, v);
        else setOffset(v, view.oy);
      });
    });

    [ctl.measuredX, ctl.measuredY].forEach(function (input) {
      if (!input) return;
      input.addEventListener('change', function () {
        var v = Number(input.value);
        if (!Number.isFinite(v)) return;
        var span = designSpan(view.paper);
        if (input.getAttribute('data-axis') === 'y') view.sy = pctFromMeasurement(span.y, v);
        else view.sx = pctFromMeasurement(span.x, v);
        saveCalibration();
        applyTransform();
        syncControls();
      });
    });

    if (ctl.reset) {
      ctl.reset.addEventListener('click', function () {
        view.sx = 100;
        view.sy = 100;
        saveCalibration();
        applyTransform();
        syncControls();
      });
    }
  }

  bindControls();

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

  async function open(coupons, opts) {
    opts = opts || {};
    coupons = Array.isArray(coupons) ? coupons.filter(function (c) { return c && c.code; }) : [];
    if (!coupons.length) {
      showAdminAlert('인쇄할 쿠폰이 없습니다.', 'error');
      return;
    }
    pendingIds = coupons.map(function (c) { return Number(c.id || 0); }).filter(function (id) { return id > 0; });
    var groupNo = Number(opts.groupNo || 0);
    if (!groupNo && coupons[0] && coupons[0].group_no) {
      groupNo = Number(coupons[0].group_no || 0);
    }
    var tpl = await loadTemplate(groupNo);
    var paper = tpl.paper || {
      paperWidthMm: 210, paperHeightMm: 297,
      labelWidthMm: 70, labelHeightMm: 36, columns: 2, rows: 7,
      leftMarginMm: 32.5, topMarginMm: 13.5, hGapMm: 5, vGapMm: 3,
      labelsPerSheet: 14, shape: 'roundrect',
    };
    var objects = Array.isArray(tpl.objects) ? tpl.objects : [];
    // 쪽 수는 실제로 찍는 칸 수(열×행)로 센다. 규격의 labels_per_sheet 가 이와 어긋난
    // 항목이 있어서 그 값을 믿으면 쪽 수가 모자라 뒤쪽 쿠폰이 조용히 빠진다.
    var perSheet = Math.max(1, slots(paper).length);
    var pages = Math.max(1, Math.ceil(coupons.length / perSheet));
    var page = pageMm(paper);
    if (meta) {
      meta.textContent = coupons.length.toLocaleString() + '개 · ' +
        (paper.paperSize || (page.w + '×' + page.h + ' mm')) + ' ' + pages + '장 · ' +
        (paper.name || paper.sku || '라벨지') + ' · ' +
        (Number(paper.columns) || 0) + '×' + (Number(paper.rows) || 0) +
        (groupNo ? (' · 그룹 ' + groupNo) : '');
    }
    // 위치는 이 템플릿의 값을, 배율은 이 프린터의 값을 되살린다.
    view.paper = paper;
    view.templateKey = tpl.key || (groupNo > 0 ? 'group-' + groupNo : 'default');
    loadOffset(view.templateKey);
    loadCalibration();

    var html = printDocument(coupons, paper, objects);
    var doc = frame.contentDocument;
    doc.open();
    doc.write(html);
    doc.close();
    // 미리보기 틀 높이. 쪽 높이에 화면용 바깥 여백(위아래 12px + 쪽 사이 12px)을 더한 값.
    frame.style.height = (pages * (page.h + 23)) + 'mm';
    applyTransform();
    syncControls();
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
          // 보정이 걸린 채로 뽑으면 그 사실을 알려 준다. 모르고 뽑았다가 왜 어긋나는지
          // 찾는 일이 없도록 한다.
          var note = '';
          if (view.ox || view.oy) note += ' · 위치 ' + fmt(view.ox) + ', ' + fmt(view.oy) + 'mm';
          if (Math.abs(view.sx - 100) > 0.0005 || Math.abs(view.sy - 100) > 0.0005) {
            note += ' · 배율 가로 ' + fmt(view.sx) + '% 세로 ' + fmt(view.sy) + '%';
          }
          showAdminAlert('인쇄 대화상자를 열었습니다. 해당 쿠폰을 인쇄완료로 표시했습니다.' + note, 'success');
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
