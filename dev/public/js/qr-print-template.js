(function () {
  var cfg = window.LABELUP_QR_PRINT_TPL || {};
  if (!document.getElementById('qrPrintTplModal')) return;

  var PX = 0;
  var state = {
    papers: [],
    paper: defaultPaper(),
    objects: defaultObjects(),
    settings: { grid: true, snap: true, bg: '#ffffff' },
    selectedId: null,
    tool: 'select',
    shapeKind: 'rect',
    dirty: false,
    drag: null,
    templateKey: 'default',
    groupNo: 0,
    categoryNo: 0,
    templateName: '공통 출력템플릿',
  };

  var els = {
    modal: document.getElementById('qrPrintTplModal'),
    title: document.getElementById('qrPrintTplTitle'),
    openBtn: document.getElementById('qrPrintTplBtn'),
    saveBtn: document.getElementById('qrTplSaveBtn'),
    resetBtn: document.getElementById('qrTplResetBtn'),
    status: document.getElementById('qrTplStatus'),
    search: document.getElementById('qrTplPaperSearch'),
    paperList: document.getElementById('qrTplPaperList'),
    paperMeta: document.getElementById('qrTplPaperMeta'),
    sheetWrap: document.getElementById('qrTplSheetWrap'),
    sheetMeta: document.getElementById('qrTplSheetMeta'),
    canvas: document.getElementById('qrTplCanvas'),
    artboard: document.getElementById('qrTplArtboard'),
    props: document.getElementById('qrTplProps'),
    propsMain: document.getElementById('qrTplPropsMain'),
    grid: document.getElementById('qrTplGrid'),
    snap: document.getElementById('qrTplSnap'),
    bg: document.getElementById('qrTplBg'),
    hint: document.getElementById('qrTplHint'),
    imageFile: document.getElementById('qrTplImageFile'),
    shapePop: document.getElementById('qrTplShapePop'),
    shapeBtn: document.getElementById('qrTplShapeBtn'),
  };

  function defaultPaper() {
    return {
      paperNo: 'LU-3230',
      name: 'A4 70×36 mm',
      sku: 'LU-3230',
      paperWidthMm: 210,
      paperHeightMm: 297,
      labelWidthMm: 70,
      labelHeightMm: 36,
      columns: 2,
      rows: 7,
      leftMarginMm: 32.5,
      topMarginMm: 13.5,
      hGapMm: 5,
      vGapMm: 3,
      shape: 'roundrect',
      labelsPerSheet: 14,
    };
  }

  function defaultObjects() {
    return [
      obj('qr', { x: 4, y: 6, w: 24, h: 24, payload: '{{coupon_url}}' }),
      obj('text', {
        x: 30, y: 7, w: 36, h: 10, text: '{{coupon_code}}',
        fontFamily: 'Pretendard', fontSize: 9, fontWeight: 800, align: 'left', fill: '#2E2A27',
      }),
      obj('text', {
        x: 30, y: 18, w: 36, h: 12, text: '스캔하고\n잉크 받기',
        fontFamily: 'Pretendard', fontSize: 8, fontWeight: 700, align: 'left', fill: '#7B2840',
      }),
    ];
  }

  function uid(prefix) {
    return (prefix || 'o') + '_' + Math.random().toString(36).slice(2, 9);
  }

  function obj(type, extra) {
    return Object.assign({
      id: uid(type),
      type: type,
      x: 8, y: 8, w: 24, h: 12,
      rotation: 0,
      opacity: 1,
      locked: false,
      fill: '#7B2840',
      stroke: '#7B2840',
      strokeWidth: 0.4,
    }, extra || {});
  }

  function selected() {
    return state.objects.find(function (o) { return o.id === state.selectedId; }) || null;
  }

  function setDirty(v) {
    state.dirty = !!v;
    if (els.status) {
      els.status.textContent = state.dirty ? '수정됨' : (els.status.dataset.saved || '');
    }
  }

  function snap(v) {
    if (!state.settings.snap) return v;
    return Math.round(v * 2) / 2;
  }

  function clampObj(o) {
    var lw = Number(state.paper.labelWidthMm) || 70;
    var lh = Number(state.paper.labelHeightMm) || 36;
    o.w = Math.max(2, Math.min(lw, Number(o.w) || 2));
    o.h = Math.max(2, Math.min(lh, Number(o.h) || 2));
    o.x = Math.max(0, Math.min(lw - o.w, Number(o.x) || 0));
    o.y = Math.max(0, Math.min(lh - o.h, Number(o.y) || 0));
    return o;
  }

  function sampleCouponUrl() {
    var code = cfg.sampleCode || '쿠폰코드자리(입력안됨)';
    var url = String(cfg.sampleUrl || 'https://labelup.kr/qr-coupon');
    if (/[?&]code=/.test(url)) return url;
    return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'code=' + encodeURIComponent(code);
  }

  function payloadText(o) {
    var raw = String(o.payload || '{{coupon_url}}');
    if (raw === '{{coupon_url}}') return sampleCouponUrl();
    if (raw === '{{coupon_code}}') return cfg.sampleCode || '쿠폰코드자리(입력안됨)';
    return raw;
  }

  function displayText(o) {
    return String(o.text || '').replace('{{coupon_code}}', cfg.sampleCode || '쿠폰코드자리(입력안됨)');
  }

  function qrSrc(o) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&margin=0&data=' +
      encodeURIComponent(payloadText(o));
  }

  // 규격에 적힌 용지 크기 이름을 mm 로 바꾸는 표.
  // 라벨 편집기(PaperCatalog.ResolvePaperSizeMm)와 같은 표를 써야 두 화면의 결과가 같다.
  var PAGE_SIZES = {
    A3: [297, 420], A4: [210, 297], A5: [148, 210], A6: [105, 148],
    B4: [257, 364], B5: [182, 257], B6: [128, 182],
    LETTER: [215.9, 279.4], LEGAL: [215.9, 355.6],
  };

  // "A4", "A4 가로", "210x297" 을 받는다. 이름을 모르면 A4 로 두되,
  // 배치가 A4 보다 크면 잘리지 않게 배치에 맞춰 늘린다.
  function pageSizeMm(name, needW, needH) {
    var key = String(name || '').trim();
    if (key) {
      var landscape = /가로|landscape/i.test(key);
      key = key.replace(/가로|세로|landscape|portrait/gi, '').trim();
      var hit = PAGE_SIZES[key.toUpperCase()];
      if (hit) return landscape ? { w: hit[1], h: hit[0] } : { w: hit[0], h: hit[1] };
      var parts = key.split(/[xX×*]/);
      if (parts.length === 2) {
        var w = parseFloat(parts[0].replace(/[^\d.]/g, ''));
        var h = parseFloat(parts[1].replace(/[^\d.]/g, ''));
        if (w > 0 && h > 0) return { w: w, h: h };
      }
    }
    return { w: Math.max(210, Number(needW) || 0), h: Math.max(297, Number(needH) || 0) };
  }

  // 규격의 여백 칸은 비어 있을 수 있다. 비었으면 null 로 돌려줘야 "값이 없다"와
  // "0mm 로 적혀 있다"를 구분할 수 있다. Number(null) 은 0 이라 그냥 쓰면 안 된다.
  function mmOrNull(v) {
    if (v === null || v === undefined || v === '') return null;
    var n = Number(v);
    return Number.isFinite(n) && n >= 0 ? n : null;
  }

  /**
   * 상점 규격(label_specs)에 적힌 배치값으로 용지를 만든다.
   *
   * 예전에는 라벨 크기와 한 장당 칸 수만 보고 A4 복판에 다시 앉혔다. 그래서 규격에
   * 적어 둔 위쪽·왼쪽 여백이 통째로 버려졌고, 인쇄물이 라벨지 칼선과 어긋났다.
   * 라벨 편집기는 같은 규격을 PaperCatalog 에서 그대로 읽어 쓰므로, 편집기 인쇄는
   * 맞는데 이 창의 인쇄만 밀리던 까닭도 여기였다.
   *
   * 열·행이 적혀 있으면 규격을 그대로 따르고, 없는 옛 항목만 예전처럼 복판에 앉힌다.
   */
  function paperFromProduct(item) {
    var lw = Number(item.widthMm) > 0 ? Number(item.widthMm) : 70;
    var lh = Number(item.heightMm) > 0 ? Number(item.heightMm) : 36;
    var cols = Number(item.columnsCount) > 0 ? Math.round(Number(item.columnsCount)) : 0;
    var rows = Number(item.rowsCount) > 0 ? Math.round(Number(item.rowsCount)) : 0;

    var layout = cols > 0 && rows > 0
      ? layoutFromSpec(item, lw, lh, cols, rows)
      : layoutCentered(lw, lh, Number(item.labelsPerSheet) || 0, pageSizeMm(item.paperSize, 0, 0));

    var shape = String(item.shape || '').toLowerCase();
    if (shape === 'circle' || shape === '원형' || shape === 'ellipse') layout.shape = 'ellipse';
    else if (shape === 'round' || shape === 'roundrect' || shape === '라운드') layout.shape = 'roundrect';
    else if (shape) layout.shape = 'rect';
    layout.paperSize = item.paperSize || '';
    layout.paperNo = item.sku || ('P' + item.id);
    layout.sku = item.sku || '';
    layout.name = item.name || layout.paperNo;
    layout.categoryName = item.categoryName || '';
    layout.thumbnailUrl = item.thumbnailUrl || '';
    layout.productId = item.id || 0;
    return layout;
  }

  // 규격에 적힌 열·행·여백·간격을 그대로 쓴다. 여백 칸이 비어 있을 때만 복판에 앉힌다.
  function layoutFromSpec(item, lw, lh, cols, rows) {
    var hGap = Math.max(0, Number(item.hGapMm) || 0);
    var vGap = Math.max(0, Number(item.vGapMm) || 0);
    var usedW = lw * cols + hGap * (cols - 1);
    var usedH = lh * rows + vGap * (rows - 1);
    var left = mmOrNull(item.leftMarginMm);
    var top = mmOrNull(item.topMarginMm);
    var page = pageSizeMm(item.paperSize, usedW + (left || 0), usedH + (top || 0));
    return {
      paperWidthMm: page.w,
      paperHeightMm: page.h,
      labelWidthMm: lw,
      labelHeightMm: lh,
      columns: cols,
      rows: rows,
      leftMarginMm: left === null ? Math.max(0, (page.w - usedW) / 2) : left,
      topMarginMm: top === null ? Math.max(0, (page.h - usedH) / 2) : top,
      hGapMm: hGap,
      vGapMm: vGap,
      labelsPerSheet: cols * rows,
      shape: 'roundrect',
    };
  }

  /**
   * 저장해 둔 용지값에서 빠진 칸만 메운다.
   *
   * 예전에는 여기서 배치를 처음부터 다시 셈해 저장값 위에 덮어썼다. 그래서 템플릿에
   * 어떤 용지를 골라 저장해도, 다시 불러오는 순간 열·행과 여백이 복판 정렬값으로
   * 되돌아갔다. 규격의 위쪽 여백이 인쇄에 반영되지 않던 두 번째 원인이다. 덮어쓰지 않는다.
   */
  function normalizePaper(paper) {
    if (!paper || typeof paper !== 'object') return defaultPaper();
    var lw = Number(paper.labelWidthMm) > 0 ? Number(paper.labelWidthMm) : 70;
    var lh = Number(paper.labelHeightMm) > 0 ? Number(paper.labelHeightMm) : 36;
    var cols = Number(paper.columns) > 0 ? Math.round(Number(paper.columns)) : 0;
    var rows = Number(paper.rows) > 0 ? Math.round(Number(paper.rows)) : 0;

    // 열·행이 없는 옛 저장본만 추정한다. 들어 있으면 그대로 믿는다.
    if (cols < 1 || rows < 1) {
      var guess = layoutCentered(lw, lh, Number(paper.labelsPerSheet) || 0,
        pageSizeMm(paper.paperSize, 0, 0));
      paper = Object.assign({}, guess, paper, { columns: guess.columns, rows: guess.rows });
      cols = guess.columns;
      rows = guess.rows;
    }

    var hGap = Math.max(0, Number(paper.hGapMm) || 0);
    var vGap = Math.max(0, Number(paper.vGapMm) || 0);
    var usedW = lw * cols + hGap * (cols - 1);
    var usedH = lh * rows + vGap * (rows - 1);
    var left = mmOrNull(paper.leftMarginMm);
    var top = mmOrNull(paper.topMarginMm);
    var pw = Number(paper.paperWidthMm);
    var ph = Number(paper.paperHeightMm);
    var page = pw > 0 && ph > 0
      ? { w: pw, h: ph }
      : pageSizeMm(paper.paperSize, usedW + (left || 0), usedH + (top || 0));

    return Object.assign({}, paper, {
      paperWidthMm: page.w,
      paperHeightMm: page.h,
      labelWidthMm: lw,
      labelHeightMm: lh,
      columns: cols,
      rows: rows,
      hGapMm: hGap,
      vGapMm: vGap,
      leftMarginMm: left === null ? Math.max(0, (page.w - usedW) / 2) : left,
      topMarginMm: top === null ? Math.max(0, (page.h - usedH) / 2) : top,
      // 칸 수는 열×행이 진실이다. 규격의 labels_per_sheet 가 이와 어긋난 항목이 있고,
      // 그 값을 믿으면 쪽 수 계산과 실제로 찍히는 칸 수가 달라져 쿠폰이 샌다.
      labelsPerSheet: cols * rows,
      shape: paper.shape || 'roundrect',
      paperNo: paper.paperNo || paper.sku,
      sku: paper.sku || paper.paperNo,
      name: paper.name || paper.sku,
      productId: paper.productId || 0,
    });
  }

  function pageFit(page, lw, lh, gap) {
    var cols = Math.max(1, Math.floor((page.w + gap) / (lw + gap)));
    var rows = Math.max(1, Math.floor((page.h + gap) / (lh + gap)));
    if (cols * lw - 0.05 > page.w) cols = Math.max(1, Math.floor(page.w / lw));
    if (rows * lh - 0.05 > page.h) rows = Math.max(1, Math.floor(page.h / lh));
    return { cols: cols, rows: rows };
  }

  // 규격에 배치값이 없는 용지를 위한 대비책. 들어가는 만큼 칸을 잡고 복판에 앉힌다.
  function layoutCentered(lw, lh, labels, page) {
    var pageW = page.w;
    var pageH = page.h;
    lw = Math.max(1, Number(lw) || 70);
    lh = Math.max(1, Number(lh) || 36);
    var fit = pageFit(page, lw, lh, 2);
    if (labels > 0 && labels > fit.cols * fit.rows) {
      fit = pageFit(page, lw, lh, 0);
    }
    if (!(labels > 0)) {
      labels = fit.cols * fit.rows;
    }

    var cols = 1;
    var rows = 1;
    var best = null;
    for (var c = 1; c <= Math.min(labels, fit.cols); c++) {
      if (labels % c !== 0) continue;
      var r = labels / c;
      if (r <= fit.rows) best = { cols: c, rows: r };
    }
    if (best) {
      cols = best.cols;
      rows = best.rows;
    } else {
      cols = Math.min(fit.cols, labels);
      rows = Math.min(fit.rows, Math.max(1, Math.ceil(labels / cols)));
    }

    var remainW = Math.max(0, pageW - cols * lw);
    var remainH = Math.max(0, pageH - rows * lh);
    var hGap = cols > 1 ? Math.min(5, remainW / (cols + 1)) : 0;
    var vGap = rows > 1 ? Math.min(5, remainH / (rows + 1)) : 0;
    var left = (pageW - cols * lw - hGap * Math.max(0, cols - 1)) / 2;
    var top = (pageH - rows * lh - vGap * Math.max(0, rows - 1)) / 2;
    return {
      paperWidthMm: pageW,
      paperHeightMm: pageH,
      labelWidthMm: lw,
      labelHeightMm: lh,
      columns: cols,
      rows: rows,
      leftMarginMm: Math.max(0, left),
      topMarginMm: Math.max(0, top),
      hGapMm: hGap,
      vGapMm: vGap,
      labelsPerSheet: cols * rows,
      shape: 'roundrect',
    };
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

  function fitArtboard() {
    if (!els.canvas || !els.artboard) return;
    var paper = state.paper;
    var pad = 28;
    var availW = Math.max(80, els.canvas.clientWidth - pad * 2);
    var availH = Math.max(80, els.canvas.clientHeight - pad * 2);
    var ar = (Number(paper.labelWidthMm) || 70) / (Number(paper.labelHeightMm) || 36);
    var w = availW;
    var h = w / ar;
    if (h > availH) {
      h = availH;
      w = h * ar;
    }
    PX = w / (Number(paper.labelWidthMm) || 70);
    els.artboard.style.width = w + 'px';
    els.artboard.style.height = h + 'px';
    var step = PX;
    els.artboard.classList.toggle('is-grid', !!state.settings.grid);
    els.artboard.style.backgroundColor = state.settings.bg || '#ffffff';
    els.artboard.style.backgroundSize = state.settings.grid ? (step + 'px ' + step + 'px') : '';
    if (paper.shape === 'ellipse') els.artboard.style.borderRadius = '50%';
    else if (paper.shape === 'roundrect') els.artboard.style.borderRadius = Math.max(4, PX * 1.2) + 'px';
    else els.artboard.style.borderRadius = '2px';
  }

  function render() {
    fitArtboard();
    renderPaperMeta();
    renderPaperList();
    renderSheet();
    renderObjects();
    renderProps();
    document.querySelectorAll('.qr-tpl-tool[data-tool]').forEach(function (btn) {
      btn.classList.toggle('is-active', btn.getAttribute('data-tool') === state.tool);
    });
    if (els.grid) els.grid.checked = !!state.settings.grid;
    if (els.snap) els.snap.checked = !!state.settings.snap;
    if (els.bg) els.bg.value = state.settings.bg || '#ffffff';
  }

  function renderPaperMeta() {
    var p = state.paper;
    if (els.paperMeta) {
      els.paperMeta.textContent = (p.paperNo || p.sku || '용지') + ' · ' +
        fmt(p.labelWidthMm) + '×' + fmt(p.labelHeightMm) + ' mm';
    }
    if (els.sheetMeta) {
      // 용지가 A4 라고 단정하면 안 된다. 규격에 A3·B5 같은 다른 크기가 적힌 것이 있다.
      els.sheetMeta.textContent = (p.paperSize || (fmt(p.paperWidthMm) + '×' + fmt(p.paperHeightMm) + ' mm')) +
        ' · ' + p.columns + '열 × ' + p.rows + '행 · 위 여백 ' + fmt(p.topMarginMm) + ' mm';
    }
  }

  function fmt(n) {
    var v = Number(n);
    if (!Number.isFinite(v)) return '-';
    return (Math.round(v * 10) / 10).toString();
  }

  function filteredPapers() {
    var q = (els.search && els.search.value || '').trim().toLowerCase();
    if (!q) return state.papers.slice(0, 80);
    return state.papers.filter(function (it) {
      var blob = [it.name, it.sku, it.categoryName, it.compatFormtec, it.compatIlabel, it.compatAnylabel]
        .join(' ').toLowerCase();
      return blob.indexOf(q) >= 0;
    }).slice(0, 80);
  }

  function renderPaperList() {
    if (!els.paperList) return;
    var items = filteredPapers();
    if (!items.length) {
      els.paperList.innerHTML = '<div class="admin-muted" style="padding:10px 4px">등록된 용지가 없습니다.</div>';
      return;
    }
    els.paperList.innerHTML = items.map(function (it) {
      var active = String(state.paper.paperNo || '') === String(it.sku || ('P' + it.id)) ||
        Number(state.paper.productId || 0) === Number(it.id || 0);
      var layout = paperFromProduct(it);
      var size = (it.widthMm ? fmt(it.widthMm) + '×' + fmt(it.heightMm) + ' mm' : '');
      var cells = ' · ' + layout.columns + '열×' + layout.rows + '행';
      var thumb = miniSheetSvg(layout);
      return '<button type="button" class="qr-tpl-paper-card' + (active ? ' is-active' : '') + '" data-paper-id="' + it.id + '">' +
        '<span class="qr-tpl-paper-card__thumb">' + thumb + '</span>' +
        '<span><strong>' + escapeHtml(it.name || it.sku || '') + '</strong>' +
        '<span>' + escapeHtml((it.sku || '') + (size ? ' · ' + size : '') + cells) + '</span></span></button>';
    }).join('');
  }

  function sheetCellSvg(paper, slot, withContent) {
    var rx = paper.shape === 'ellipse' ? slot.w / 2 : 1.2;
    var ry = paper.shape === 'ellipse' ? slot.h / 2 : rx;
    var cell = '<rect x="' + slot.x + '" y="' + slot.y + '" width="' + slot.w + '" height="' + slot.h +
      '" fill="#fff" stroke="#d4c6bc" stroke-width="0.35" rx="' + rx + '" ry="' + ry + '"/>';
    if (!withContent) return cell;
    var inner = state.objects.map(function (o) {
      var x = slot.x + (Number(o.x) || 0);
      var y = slot.y + (Number(o.y) || 0);
      var w = Number(o.w) || 2;
      var h = Number(o.h) || 2;
      if (o.type === 'qr') {
        return '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" fill="#2E2A27"/>';
      }
      if (o.type === 'barcode') {
        return '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" fill="#2E2A27" opacity="0.55"/>';
      }
      return '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" fill="' +
        (o.fill || '#7B2840') + '" opacity="0.32"/>';
    }).join('');
    return cell + inner;
  }

  // 미리보기 좌표계는 mm 그대로다. 용지 크기를 210×297 로 박아 두면 A4 가 아닌 규격에서
  // 칸 자리가 통째로 어긋나 보이므로 용지에서 받아 쓴다.
  function sheetViewBox(paper) {
    var w = Number(paper.paperWidthMm) > 0 ? Number(paper.paperWidthMm) : 210;
    var h = Number(paper.paperHeightMm) > 0 ? Number(paper.paperHeightMm) : 297;
    return { w: w, h: h };
  }

  function miniSheetSvg(paper) {
    var box = sheetViewBox(paper);
    var cells = slots(paper).map(function (s) { return sheetCellSvg(paper, s, false); }).join('');
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + box.w + ' ' + box.h +
      '" class="qr-tpl-thumb-svg" aria-hidden="true">' +
      '<rect width="' + box.w + '" height="' + box.h + '" fill="#fff" stroke="#d9cfc0" stroke-width="1.2"/>' +
      cells + '</svg>';
  }

  function renderSheet() {
    if (!els.sheetWrap) return;
    var paper = state.paper;
    var box = sheetViewBox(paper);
    var cells = slots(paper).map(function (s) { return sheetCellSvg(paper, s, true); }).join('');
    els.sheetWrap.innerHTML =
      '<svg class="qr-tpl-sheet-svg" viewBox="0 0 ' + box.w + ' ' + box.h +
      '" preserveAspectRatio="xMidYMid meet" role="img" aria-label="용지 미리보기" data-cols="' +
      paper.columns + '" data-rows="' + paper.rows + '">' +
      '<rect width="' + box.w + '" height="' + box.h + '" fill="#fff" stroke="#cfc4b8" stroke-width="1.2"/>' +
      cells + '</svg>';
  }

  function renderObjects() {
    if (!els.artboard) return;
    els.artboard.innerHTML = state.objects.map(function (o) {
      var sel = o.id === state.selectedId;
      var html = innerHtml(o);
      var handles = sel && !o.locked
        ? '<i class="qr-tpl-handle nw" data-handle="nw"></i><i class="qr-tpl-handle ne" data-handle="ne"></i>' +
          '<i class="qr-tpl-handle sw" data-handle="sw"></i><i class="qr-tpl-handle se" data-handle="se"></i>'
        : '';
      return '<div class="qr-tpl-obj' + (sel ? ' is-selected' : '') + (o.locked ? ' is-locked' : '') +
        '" data-id="' + o.id + '" style="' + objStyle(o) + '">' + html + handles + '</div>';
    }).join('');
  }

  function objStyle(o) {
    return 'left:' + (o.x * PX) + 'px;top:' + (o.y * PX) + 'px;width:' + (o.w * PX) + 'px;height:' + (o.h * PX) +
      'px;transform:rotate(' + (o.rotation || 0) + 'deg);opacity:' + (o.opacity == null ? 1 : o.opacity) + ';';
  }

  function innerHtml(o) {
    if (o.type === 'qr') {
      return '<img class="qr-tpl-obj__qr" alt="QR" src="' + escapeAttr(qrSrc(o)) + '">';
    }
    if (o.type === 'text') {
      var align = o.align || 'left';
      var justify = align === 'center' ? 'center' : (align === 'right' ? 'flex-end' : 'flex-start');
      var sizePx = (Number(o.fontSize) || 9) * 0.3528 * PX;
      return '<div class="qr-tpl-obj__text" style="justify-content:' + justify + ';text-align:' + align +
        ';font-family:' + escapeAttr(o.fontFamily || 'Pretendard') + ';font-size:' + sizePx +
        'px;font-weight:' + (o.fontWeight || 700) + ';color:' + (o.fill || '#2E2A27') + '">' +
        escapeHtml(displayText(o)) + '</div>';
    }
    if (o.type === 'barcode') {
      return '<div class="qr-tpl-obj__barcode"></div>';
    }
    if (o.type === 'image') {
      if (o.src) return '<img class="qr-tpl-obj__img" alt="" src="' + escapeAttr(o.src) + '">';
      return '<div class="qr-tpl-obj__text" style="justify-content:center;color:#9b1c1c;font-size:11px">이미지</div>';
    }
    var kind = o.shapeKind || 'rect';
    return '<div class="qr-tpl-obj__shape is-' + kind + '" style="background:' + (o.fill || '#7B2840') +
      ';border:' + ((o.strokeWidth || 0) * PX) + 'px solid ' + (o.stroke || '#7B2840') + '"></div>';
  }

  function renderProps() {
    var o = selected();
    if (!els.props || !els.propsMain) return;
    if (!o) {
      els.props.hidden = true;
      if (els.hint) els.hint.textContent = '도구를 고른 뒤 라벨을 클릭하면 오브젝트가 추가됩니다.';
      return;
    }
    els.props.hidden = false;
    if (els.hint) els.hint.textContent = typeLabel(o) + ' 선택됨';
    var html = geomFields(o);
    if (o.type === 'text') html += textFields(o);
    if (o.type === 'qr' || o.type === 'barcode') html += payloadFields(o);
    if (o.type === 'shape') html += shapeFields(o);
    html += '<span class="qr-tpl-div"></span><span class="qr-tpl-slot">' +
      '<button type="button" data-act="front">앞으로</button>' +
      '<button type="button" data-act="back">뒤로</button>' +
      '<button type="button" data-act="dup">복제</button>' +
      '<button type="button" data-act="del">삭제</button>' +
      '<button type="button" data-act="lock" class="' + (o.locked ? 'is-on' : '') + '">' + (o.locked ? '잠금됨' : '잠금') + '</button>' +
      '</span>';
    els.propsMain.innerHTML = html;
  }

  function geomFields(o) {
    return '<span class="qr-tpl-slot"><em>X</em><input type="number" step="0.5" data-prop="x" value="' + fmt(o.x) + '"></span>' +
      '<span class="qr-tpl-slot"><em>Y</em><input type="number" step="0.5" data-prop="y" value="' + fmt(o.y) + '"></span>' +
      '<span class="qr-tpl-slot"><em>W</em><input type="number" step="0.5" min="2" data-prop="w" value="' + fmt(o.w) + '"></span>' +
      '<span class="qr-tpl-slot"><em>H</em><input type="number" step="0.5" min="2" data-prop="h" value="' + fmt(o.h) + '"></span>' +
      '<span class="qr-tpl-slot"><em>회전</em><input type="number" step="1" data-prop="rotation" value="' + fmt(o.rotation || 0) + '"></span>';
  }

  function textFields(o) {
    return '<span class="qr-tpl-slot"><em>글꼴</em><select data-prop="fontFamily">' +
      opt('Pretendard', o.fontFamily) + opt('Malgun Gothic', o.fontFamily) + opt('Noto Sans KR', o.fontFamily) +
      opt('serif', o.fontFamily) + opt('monospace', o.fontFamily) + '</select>' +
      '<input type="number" min="4" max="72" step="0.5" data-prop="fontSize" value="' + fmt(o.fontSize || 9) + '">' +
      '<button type="button" data-act="bold" class="' + ((o.fontWeight || 0) >= 700 ? 'is-on' : '') + '">B</button>' +
      '<button type="button" data-act="align" data-align="left" class="' + ((o.align || 'left') === 'left' ? 'is-on' : '') + '">좌</button>' +
      '<button type="button" data-act="align" data-align="center" class="' + (o.align === 'center' ? 'is-on' : '') + '">중</button>' +
      '<button type="button" data-act="align" data-align="right" class="' + (o.align === 'right' ? 'is-on' : '') + '">우</button>' +
      '<input type="color" data-prop="fill" value="' + (o.fill || '#2E2A27') + '">' +
      '</span>' +
      '<span class="qr-tpl-slot"><em>내용</em><input type="text" data-prop="text" value="' + escapeAttr(String(o.text || '').replace(/\n/g, '\\n')) + '"></span>';
  }

  function payloadFields(o) {
    var payload = o.payload || '{{coupon_url}}';
    var custom = String(payload).indexOf('{{') === 0 ? '' : payload;
    return '<span class="qr-tpl-slot"><em>데이터</em><select data-prop="payload">' +
      opt('{{coupon_url}}', payload) +
      opt('{{coupon_code}}', payload) +
      (custom ? opt(custom, payload) : '') +
      '</select>' +
      '<input type="text" data-prop="payloadCustom" placeholder="직접 입력" value="' + escapeAttr(custom) + '"></span>';
  }

  function shapeFields(o) {
    return '<span class="qr-tpl-slot"><em>채우기</em><input type="color" data-prop="fill" value="' + (o.fill || '#7B2840') + '">' +
      '<em>테두리</em><input type="color" data-prop="stroke" value="' + (o.stroke || '#7B2840') + '">' +
      '<input type="number" min="0" step="0.1" data-prop="strokeWidth" value="' + fmt(o.strokeWidth || 0) + '"></span>';
  }

  function opt(value, current) {
    var label = value === '{{coupon_url}}' ? '쿠폰 URL' : (value === '{{coupon_code}}' ? '쿠폰번호' : value);
    return '<option value="' + escapeAttr(value) + '"' + (current === value ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
  }

  function typeLabel(o) {
    return ({ qr: 'QR', text: '텍스트', barcode: '바코드', image: '이미지', shape: '도형' })[o.type] || o.type;
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
  }

  function escapeAttr(s) {
    return escapeHtml(s);
  }

  function addObject(type, at, extra) {
    var lw = Number(state.paper.labelWidthMm) || 70;
    var lh = Number(state.paper.labelHeightMm) || 36;
    var size = type === 'qr' ? { w: Math.min(24, lw * 0.4), h: Math.min(24, lh * 0.7) }
      : type === 'barcode' ? { w: Math.min(40, lw * 0.7), h: Math.min(12, lh * 0.35) }
        : type === 'text' ? { w: Math.min(36, lw * 0.55), h: Math.min(10, lh * 0.3) }
          : { w: Math.min(20, lw * 0.4), h: Math.min(12, lh * 0.35) };
    var o = obj(type, Object.assign({
      x: snap((at && at.x) || (lw - size.w) / 2),
      y: snap((at && at.y) || (lh - size.h) / 2),
      w: size.w,
      h: type === 'qr' ? size.w : size.h,
      text: type === 'text' ? '텍스트' : undefined,
      payload: (type === 'qr' || type === 'barcode') ? '{{coupon_url}}' : undefined,
      shapeKind: extra && extra.shapeKind,
    }, extra || {}));
    clampObj(o);
    state.objects.push(o);
    state.selectedId = o.id;
    state.tool = 'select';
    setDirty(true);
    render();
  }

  function pointOnArtboard(ev) {
    var rect = els.artboard.getBoundingClientRect();
    return {
      x: snap((ev.clientX - rect.left) / PX),
      y: snap((ev.clientY - rect.top) / PX),
    };
  }

  function onCanvasPointerDown(ev) {
    if (ev.button != null && ev.button !== 0) return;
    var handle = ev.target.closest && ev.target.closest('[data-handle]');
    var node = ev.target.closest && ev.target.closest('.qr-tpl-obj');
    if (state.tool !== 'select') {
      if (state.tool === 'image') {
        els.imageFile.click();
        return;
      }
      var at = pointOnArtboard(ev);
      addObject(state.tool === 'shape' ? 'shape' : state.tool, at, state.tool === 'shape' ? { shapeKind: state.shapeKind } : null);
      return;
    }
    if (handle && node) {
      var o = state.objects.find(function (x) { return x.id === node.getAttribute('data-id'); });
      if (!o || o.locked) return;
      state.selectedId = o.id;
      state.drag = {
        kind: 'resize',
        handle: handle.getAttribute('data-handle'),
        startX: ev.clientX,
        startY: ev.clientY,
        orig: { x: o.x, y: o.y, w: o.w, h: o.h },
      };
      ev.preventDefault();
      render();
      return;
    }
    if (node) {
      var objId = node.getAttribute('data-id');
      var found = state.objects.find(function (x) { return x.id === objId; });
      state.selectedId = objId;
      if (found && !found.locked) {
        state.drag = {
          kind: 'move',
          startX: ev.clientX,
          startY: ev.clientY,
          orig: { x: found.x, y: found.y },
        };
      }
      ev.preventDefault();
      render();
      return;
    }
    state.selectedId = null;
    render();
  }

  function onPointerMove(ev) {
    if (!state.drag) return;
    var o = selected();
    if (!o) return;
    var dx = (ev.clientX - state.drag.startX) / PX;
    var dy = (ev.clientY - state.drag.startY) / PX;
    if (state.drag.kind === 'move') {
      o.x = snap(state.drag.orig.x + dx);
      o.y = snap(state.drag.orig.y + dy);
    } else {
      var h = state.drag.handle;
      var orig = state.drag.orig;
      var nx = orig.x, ny = orig.y, nw = orig.w, nh = orig.h;
      if (h.indexOf('e') >= 0) nw = orig.w + dx;
      if (h.indexOf('s') >= 0) nh = orig.h + dy;
      if (h.indexOf('w') >= 0) { nx = orig.x + dx; nw = orig.w - dx; }
      if (h.indexOf('n') >= 0) { ny = orig.y + dy; nh = orig.h - dy; }
      if (o.type === 'qr') {
        var side = Math.max(nw, nh);
        nw = side; nh = side;
      }
      o.x = snap(nx); o.y = snap(ny); o.w = snap(nw); o.h = snap(nh);
    }
    clampObj(o);
    setDirty(true);
    renderObjects();
    renderSheet();
  }

  function onPointerUp() {
    if (state.drag) {
      state.drag = null;
      renderProps();
    }
  }

  function templateKeyForGroup(groupNo) {
    groupNo = Number(groupNo || 0);
    return groupNo > 0 ? ('group-' + groupNo) : 'default';
  }

  function templateTitle(groupNo, categoryName) {
    groupNo = Number(groupNo || 0);
    if (groupNo > 0) {
      var name = String(categoryName || '').trim();
      return name
        ? ('그룹 ' + groupNo + ' 출력템플릿 · ' + name)
        : ('그룹 ' + groupNo + ' 출력템플릿');
    }
    return '공통 출력템플릿';
  }

  function setTemplateContext(opts) {
    opts = opts || {};
    var groupNo = Number(opts.groupNo || 0);
    state.groupNo = groupNo;
    state.categoryNo = Number(opts.categoryNo || 0);
    state.templateKey = opts.key || templateKeyForGroup(groupNo);
    state.templateName = opts.name || (
      groupNo > 0 ? ('그룹 ' + groupNo + ' 출력템플릿') : '공통 출력템플릿'
    );
    if (els.title) {
      els.title.textContent = templateTitle(groupNo, opts.categoryName || '');
    }
  }

  function loadUrlForKey(key) {
    var base = cfg.loadUrl || '/api/admin/qr-coupons/print-template';
    var sep = base.indexOf('?') >= 0 ? '&' : '?';
    return base + sep + 'key=' + encodeURIComponent(key || 'default');
  }

  async function loadPapers() {
    try {
      var res = await fetch(cfg.papersUrl || '/api/shop/editor-papers', { credentials: 'same-origin' });
      var data = await res.json();
      state.papers = (data.data && data.data.items) || data.items || [];
    } catch (err) {
      state.papers = [];
    }
    renderPaperList();
  }

  async function loadTemplate() {
    try {
      var res = await AdminAPI.get(loadUrlForKey(state.templateKey));
      var data = (res && res.data) || {};
      if (data.key) state.templateKey = data.key;
      if (data.group_no != null) state.groupNo = Number(data.group_no || 0);
      if (data.category_no != null) state.categoryNo = Number(data.category_no || 0);
      if (data.name) state.templateName = data.name;
      applyTemplate(data, false);
      if (data.persisted && data.updated_at) {
        els.status.dataset.saved = '저장 ' + data.updated_at;
        els.status.textContent = els.status.dataset.saved;
      } else if (data.fallback_from) {
        els.status.dataset.saved = '';
        els.status.textContent = data.fallback_from === 'default'
          ? '공통 템플릿 기준 · 저장 전'
          : '이전 분류 템플릿 기준 · 저장 전';
      } else {
        els.status.dataset.saved = '';
        els.status.textContent = '기본 레이아웃';
      }
    } catch (err) {
      applyTemplate({ paper: defaultPaper(), objects: defaultObjects(), settings: state.settings }, false);
      els.status.textContent = '기본 레이아웃';
    }
  }

  function applyTemplate(data, dirty) {
    if (data.paper && typeof data.paper === 'object') state.paper = normalizePaper(Object.assign(defaultPaper(), data.paper));
    else state.paper = defaultPaper();
    if (Array.isArray(data.objects) && data.objects.length) {
      state.objects = data.objects.map(function (o) { return Object.assign(obj(o.type || 'text'), o); });
    } else {
      state.objects = defaultObjects();
    }
    if (data.settings && typeof data.settings === 'object') {
      state.settings = Object.assign({ grid: true, snap: true, bg: '#ffffff' }, data.settings);
    }
    state.selectedId = null;
    setDirty(!!dirty);
    render();
  }

  async function saveTemplate() {
    els.saveBtn.disabled = true;
    try {
      var res = await AdminAPI.post(cfg.saveUrl, {
        key: state.templateKey || 'default',
        group_no: state.groupNo || 0,
        name: state.templateName || 'QR 출력템플릿',
        paper: state.paper,
        objects: state.objects,
        settings: state.settings,
      });
      setDirty(false);
      var at = (res.data && res.data.updated_at) || '';
      els.status.dataset.saved = at ? ('저장 ' + at) : '저장됨';
      els.status.textContent = els.status.dataset.saved;
      showAdminAlert(res.message || '출력템플릿이 저장되었습니다.', 'success');
    } catch (err) {
      showAdminAlert(err.message || '저장 실패', 'error');
    } finally {
      els.saveBtn.disabled = false;
    }
  }

  function openModal(opts) {
    setTemplateContext(opts || { key: 'default', groupNo: 0 });
    els.modal.hidden = false;
    loadPapers();
    loadTemplate().then(function () {
      requestAnimationFrame(function () {
        fitArtboard();
        render();
      });
    });
  }

  if (els.openBtn) {
    els.openBtn.addEventListener('click', function () {
      openModal({ key: 'default', groupNo: 0, name: '공통 출력템플릿' });
    });
  }
  document.querySelectorAll('.js-qr-group-template').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openModal({
        groupNo: Number(btn.getAttribute('data-group-no') || 0),
        categoryNo: Number(btn.getAttribute('data-category-no') || 0),
        categoryName: btn.getAttribute('data-category-name') || '',
      });
    });
  });
  if (els.saveBtn) els.saveBtn.addEventListener('click', saveTemplate);
  if (els.resetBtn) {
    els.resetBtn.addEventListener('click', function () {
      applyTemplate({ paper: defaultPaper(), objects: defaultObjects(), settings: { grid: true, snap: true, bg: '#ffffff' } }, true);
    });
  }

  window.LabelUpQrPrintTemplate = {
    open: openModal,
  };

  document.querySelectorAll('.qr-tpl-tool[data-tool]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var tool = btn.getAttribute('data-tool');
      if (tool === 'shape') {
        els.shapePop.hidden = !els.shapePop.hidden;
        state.tool = 'shape';
        render();
        return;
      }
      els.shapePop.hidden = true;
      if (tool === 'image') {
        state.tool = 'image';
        els.imageFile.click();
        render();
        return;
      }
      state.tool = tool;
      render();
    });
  });

  if (els.shapePop) {
    els.shapePop.addEventListener('click', function (ev) {
      var btn = ev.target.closest('[data-shape]');
      if (!btn) return;
      state.shapeKind = btn.getAttribute('data-shape') || 'rect';
      state.tool = 'shape';
      els.shapePop.hidden = true;
      render();
    });
  }

  if (els.search) {
    els.search.addEventListener('input', function () { renderPaperList(); });
  }
  if (els.paperList) {
    els.paperList.addEventListener('click', function (ev) {
      var card = ev.target.closest('[data-paper-id]');
      if (!card) return;
      var id = Number(card.getAttribute('data-paper-id'));
      var item = state.papers.find(function (it) { return Number(it.id) === id; });
      if (!item) return;
      state.paper = paperFromProduct(item);
      state.objects.forEach(clampObj);
      setDirty(true);
      render();
    });
  }

  if (els.grid) {
    els.grid.addEventListener('change', function () {
      state.settings.grid = els.grid.checked;
      setDirty(true);
      render();
    });
  }
  if (els.snap) {
    els.snap.addEventListener('change', function () {
      state.settings.snap = els.snap.checked;
      setDirty(true);
    });
  }
  if (els.bg) {
    els.bg.addEventListener('input', function () {
      state.settings.bg = els.bg.value;
      setDirty(true);
      render();
    });
  }

  if (els.imageFile) {
    els.imageFile.addEventListener('change', function () {
      var file = els.imageFile.files && els.imageFile.files[0];
      els.imageFile.value = '';
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function () {
        addObject('image', null, { src: String(reader.result || ''), w: 28, h: 20 });
      };
      reader.readAsDataURL(file);
    });
  }

  els.artboard.addEventListener('pointerdown', onCanvasPointerDown);
  window.addEventListener('pointermove', onPointerMove);
  window.addEventListener('pointerup', onPointerUp);
  window.addEventListener('resize', function () {
    if (!els.modal.hidden) render();
  });

  els.propsMain.addEventListener('change', function (ev) {
    var o = selected();
    if (!o) return;
    var t = ev.target;
    var prop = t.getAttribute('data-prop');
    if (!prop) return;
    if (prop === 'payloadCustom') {
      if (t.value.trim()) o.payload = t.value.trim();
    } else if (t.type === 'number') {
      o[prop] = Number(t.value);
    } else if (prop === 'text') {
      o.text = String(t.value).replace(/\\n/g, '\n');
    } else {
      o[prop] = t.value;
    }
    clampObj(o);
    setDirty(true);
    render();
  });

  els.propsMain.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-act]');
    var o = selected();
    if (!btn || !o) return;
    var act = btn.getAttribute('data-act');
    var idx = state.objects.indexOf(o);
    if (act === 'del') {
      state.objects.splice(idx, 1);
      state.selectedId = null;
    } else if (act === 'dup') {
      var copy = Object.assign({}, o, { id: uid(o.type), x: snap(o.x + 2), y: snap(o.y + 2) });
      state.objects.push(copy);
      state.selectedId = copy.id;
    } else if (act === 'front' && idx < state.objects.length - 1) {
      state.objects.splice(idx, 1);
      state.objects.push(o);
    } else if (act === 'back' && idx > 0) {
      state.objects.splice(idx, 1);
      state.objects.unshift(o);
    } else if (act === 'lock') {
      o.locked = !o.locked;
    } else if (act === 'bold') {
      o.fontWeight = (o.fontWeight || 0) >= 700 ? 400 : 800;
    } else if (act === 'align') {
      o.align = btn.getAttribute('data-align') || 'left';
    }
    setDirty(true);
    render();
  });

  document.addEventListener('keydown', function (ev) {
    if (els.modal.hidden) return;
    var tag = (ev.target && ev.target.tagName) || '';
    if (tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA') return;
    if (ev.key === 'Delete' || ev.key === 'Backspace') {
      var o = selected();
      if (!o || o.locked) return;
      state.objects = state.objects.filter(function (x) { return x.id !== o.id; });
      state.selectedId = null;
      setDirty(true);
      render();
      ev.preventDefault();
    } else if (ev.key === 'Escape') {
      state.selectedId = null;
      state.tool = 'select';
      els.shapePop.hidden = true;
      render();
    }
  });
})();
