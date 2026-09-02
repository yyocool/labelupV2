/**
 * LabelUp editor — floating panel drag + magnetic corner docking
 * Mirrors project/storyboard 01-05 snap behavior.
 */
(function () {
  const SNAP_PAD = 12;
  const SNAP_MAGNET = 14;
  const SNAP_RELEASE = 22;

  function cornerTargets(container, el, pad) {
    pad = typeof pad === 'number' ? pad : SNAP_PAD;
    const cw = container.clientWidth;
    const ch = container.clientHeight;
    const w = el.offsetWidth;
    const h = el.offsetHeight;
    return {
      tl: { left: pad, top: pad },
      tr: { left: Math.max(pad, cw - w - pad), top: pad },
      bl: { left: pad, top: Math.max(pad, ch - h - pad) },
      br: { left: Math.max(pad, cw - w - pad), top: Math.max(pad, ch - h - pad) }
    };
  }

  function nearest(targets, left, top, maxDist) {
    let best = null;
    let min = Infinity;
    Object.keys(targets).forEach(function (id) {
      const t = targets[id];
      const dx = left - t.left;
      const dy = top - t.top;
      const d = Math.sqrt(dx * dx + dy * dy);
      if (d < min) {
        min = d;
        best = { id: id, left: t.left, top: t.top, dist: d };
      }
    });
    if (maxDist != null && best && best.dist > maxDist) return null;
    return best;
  }

  function clamp(container, el, left, top, pad) {
    pad = typeof pad === 'number' ? pad : 8;
    const bw = container.clientWidth;
    const bh = container.clientHeight;
    const pw = el.offsetWidth;
    const ph = el.offsetHeight;
    return {
      left: Math.max(pad, Math.min(left, Math.max(pad, bw - pw - pad))),
      top: Math.max(pad, Math.min(top, Math.max(pad, bh - ph - pad)))
    };
  }

  function stackPropsTarget(root, body, props) {
    if (!body || !props || props.classList.contains('is-minimized')) return {};
    const br = body.getBoundingClientRect();
    const pr = props.getBoundingClientRect();
    return {
      'stack-props': {
        left: pr.left - br.left,
        top: pr.bottom - br.top + 12
      }
    };
  }

  function bindFloatTools(root) {
    const workspace = root.querySelector('[data-ed-workspace]');
    const wrap = root.querySelector('[data-ed-float-tools]');
    if (!workspace || !wrap || wrap.getAttribute('data-ed-bound') === '1') return;
    wrap.setAttribute('data-ed-bound', '1');

    const bar = wrap.querySelector('[data-ed-float-tools-bar]');
    const grip = wrap.querySelector('[data-ed-float-tools-grip]');
    const pad = SNAP_PAD;
    let drag = null;
    let currentCorner = 'tl';

    function applyCorner(corner) {
      if (!corner || !/^(tl|tr|bl|br)$/.test(corner)) corner = 'tl';
      currentCorner = corner;
      wrap.classList.add('is-snapping');
      wrap.style.right = '';
      wrap.style.bottom = '';
      wrap.style.width = '';
      const targets = cornerTargets(workspace, wrap, pad);
      const t = targets[corner];
      const pos = clamp(workspace, wrap, t.left, t.top, 0);
      wrap.style.left = pos.left + 'px';
      wrap.style.top = pos.top + 'px';
      wrap.setAttribute('data-ed-float-corner', corner);
      try { localStorage.setItem('lu-ed-float-corner', corner); } catch (e) { /* ignore */ }
      setTimeout(function () { wrap.classList.remove('is-snapping'); }, 280);
    }

    function startDrag(e) {
      if (e.button !== 0) return;
      e.preventDefault();
      e.stopPropagation();
      const wr = workspace.getBoundingClientRect();
      const br = wrap.getBoundingClientRect();
      drag = { ox: e.clientX - br.left, oy: e.clientY - br.top };
      wrap.classList.add('is-dragging');
      wrap.style.left = (br.left - wr.left) + 'px';
      wrap.style.top = (br.top - wr.top) + 'px';
      document.addEventListener('pointermove', onMove);
      document.addEventListener('pointerup', onUp);
    }

    function onMove(e) {
      if (!drag) return;
      e.preventDefault();
      const wr = workspace.getBoundingClientRect();
      let left = e.clientX - wr.left - drag.ox;
      let top = e.clientY - wr.top - drag.oy;
      const raw = clamp(workspace, wrap, left, top, 0);
      const targets = cornerTargets(workspace, wrap, pad);
      const magnet = nearest(targets, raw.left, raw.top, SNAP_MAGNET);
      if (magnet) {
        wrap.classList.add('is-magnet-near');
        wrap.style.left = magnet.left + 'px';
        wrap.style.top = magnet.top + 'px';
      } else {
        wrap.classList.remove('is-magnet-near');
        wrap.style.left = raw.left + 'px';
        wrap.style.top = raw.top + 'px';
      }
    }

    function onUp() {
      if (!drag) return;
      drag = null;
      wrap.classList.remove('is-dragging');
      wrap.classList.remove('is-magnet-near');
      document.removeEventListener('pointermove', onMove);
      document.removeEventListener('pointerup', onUp);
      const wr = workspace.getBoundingClientRect();
      const br = wrap.getBoundingClientRect();
      const left = br.left - wr.left;
      const top = br.top - wr.top;
      const targets = cornerTargets(workspace, wrap, pad);
      const snap = nearest(targets, left, top, SNAP_RELEASE);
      if (snap) {
        applyCorner(snap.id);
      } else {
        currentCorner = 'free';
        wrap.setAttribute('data-ed-float-corner', 'free');
        const pos = clamp(workspace, wrap, left, top, 0);
        wrap.style.left = pos.left + 'px';
        wrap.style.top = pos.top + 'px';
        try { localStorage.setItem('lu-ed-float-corner', 'free'); } catch (e) { /* ignore */ }
        try {
          var bw = Math.max(1, workspace.clientWidth);
          var bh = Math.max(1, workspace.clientHeight);
          localStorage.setItem('lu-ed-float-free', JSON.stringify({
            leftPct: pos.left / bw,
            topPct: pos.top / bh
          }));
        } catch (e2) { /* ignore */ }
      }
    }

    if (grip) grip.addEventListener('pointerdown', startDrag);
    if (bar) {
      bar.addEventListener('pointerdown', function (e) {
        if (e.target.closest('.ed-float-tools__item') ||
            e.target.closest('.ed-float-tools__select') ||
            e.target.closest('[data-ed-float-tools-grip]') ||
            e.target.closest('[data-ed-float-dock]')) return;
        startDrag(e);
      });
    }

    // dock menu
    const dockToggle = wrap.querySelector('[data-ed-float-dock-toggle]');
    const dockMenu = wrap.querySelector('[data-ed-float-dock-menu]');

    function syncOrientButtons(orient) {
      if (!dockMenu) return;
      dockMenu.querySelectorAll('[data-ed-float-orient]').forEach(function (btn) {
        btn.classList.toggle('is-active', btn.getAttribute('data-ed-float-orient') === orient);
      });
    }

    function syncCornerButtons(corner) {
      if (!dockMenu) return;
      dockMenu.querySelectorAll('[data-ed-float-corner]').forEach(function (btn) {
        btn.classList.toggle('is-active', btn.getAttribute('data-ed-float-corner') === corner);
      });
    }

    function applyOrient(orient) {
      if (orient !== 'vertical') orient = 'horizontal';
      wrap.setAttribute('data-ed-float-orient', orient);
      syncOrientButtons(orient);
      try { localStorage.setItem('lu-ed-float-orient', orient); } catch (e) { /* ignore */ }
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          if (currentCorner !== 'free') applyCorner(currentCorner);
        });
      });
    }

    if (dockToggle && dockMenu) {
      dockToggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        dockMenu.hidden = !dockMenu.hidden;
        dockToggle.classList.toggle('is-open', !dockMenu.hidden);
      });
      dockMenu.querySelectorAll('[data-ed-float-corner]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          applyCorner(btn.getAttribute('data-ed-float-corner'));
          syncCornerButtons(currentCorner);
          dockMenu.hidden = true;
          dockToggle.classList.remove('is-open');
        });
      });
      dockMenu.querySelectorAll('[data-ed-float-orient]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          applyOrient(btn.getAttribute('data-ed-float-orient'));
          dockMenu.hidden = true;
          dockToggle.classList.remove('is-open');
        });
      });
      document.addEventListener('click', function (e) {
        if (dockMenu.hidden) return;
        if (dockToggle.contains(e.target) || dockMenu.contains(e.target)) return;
        dockMenu.hidden = true;
        dockToggle.classList.remove('is-open');
      });
    }

    try {
      const saved = localStorage.getItem('lu-ed-float-corner');
      if (saved === 'free') currentCorner = 'free';
      else if (saved && /^(tl|tr|bl|br)$/.test(saved)) currentCorner = saved;
    } catch (e) { /* ignore */ }

    try {
      const savedOrient = localStorage.getItem('lu-ed-float-orient');
      if (savedOrient === 'vertical' || savedOrient === 'horizontal') {
        wrap.setAttribute('data-ed-float-orient', savedOrient);
        syncOrientButtons(savedOrient);
      } else {
        syncOrientButtons(wrap.getAttribute('data-ed-float-orient') || 'horizontal');
      }
    } catch (e) {
      syncOrientButtons(wrap.getAttribute('data-ed-float-orient') || 'horizontal');
    }
    syncCornerButtons(currentCorner);

    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        if (currentCorner === 'free') {
          wrap.setAttribute('data-ed-float-corner', 'free');
          try {
            var free = JSON.parse(localStorage.getItem('lu-ed-float-free') || 'null');
            if (free && typeof free.leftPct === 'number' && typeof free.topPct === 'number') {
              var pos = clamp(workspace, wrap, free.leftPct * workspace.clientWidth, free.topPct * workspace.clientHeight, 0);
              wrap.style.left = pos.left + 'px';
              wrap.style.top = pos.top + 'px';
              return;
            }
          } catch (e) { /* ignore */ }
        }
        applyCorner(currentCorner);
      });
    });

    window.addEventListener('resize', function () {
      if (!root.contains(wrap)) return;
      if (currentCorner !== 'free') applyCorner(currentCorner);
    });
  }

  function bindPanel(root, opts) {
    const panel = root.querySelector(opts.panelSelector);
    const body = root.querySelector('[data-ed-body]');
    if (!panel || !body || panel.getAttribute(opts.boundAttr) === '1') return null;
    panel.setAttribute(opts.boundAttr, '1');

    const handle = panel.querySelector(opts.handleSelector);
    let currentSnapId = opts.defaultSnap || 'tr';
    const drag = { pending: false, active: false, startX: 0, startY: 0, startLeft: 0, startTop: 0 };

    function getTargets() {
      const targets = cornerTargets(body, panel, SNAP_PAD);
      if (typeof opts.getExtraTargets === 'function') {
        const extra = opts.getExtraTargets() || {};
        Object.keys(extra).forEach(function (id) { targets[id] = extra[id]; });
      }
      return targets;
    }

    function applyHeight(left, top, snapId) {
      if (panel.classList.contains('is-minimized')) {
        panel.style.height = 'auto';
        panel.style.minHeight = '0';
        panel.style.bottom = 'auto';
        return;
      }
      if (opts.stretchToBottom && (snapId === 'stack-props' || snapId === 'tl' || snapId === 'tr')) {
        panel.style.bottom = SNAP_PAD + 'px';
        panel.style.height = '';
        return;
      }
      if (!opts.preserveHeight) {
        panel.style.bottom = 'auto';
        return;
      }
      panel.style.bottom = 'auto';
      var avail = Math.max(240, body.clientHeight - top - SNAP_PAD);
      var current = parseFloat(panel.style.height);
      if (!current || current < 280) current = panel.offsetHeight;
      if (!current || current < 280) current = Math.min(avail, 520);
      panel.style.height = Math.max(320, Math.min(avail, current)) + 'px';
    }

    function setPos(left, top) {
      const pos = clamp(body, panel, left, top, 8);
      panel.style.right = 'auto';
      panel.style.left = pos.left + 'px';
      panel.style.top = pos.top + 'px';
      applyHeight(pos.left, pos.top, currentSnapId);
      return pos;
    }

    function applySnap(snapId, silent) {
      const targets = getTargets();
      const t = targets[snapId];
      if (!t) return;
      panel.classList.add('is-snapping');
      setPos(t.left, t.top);
      currentSnapId = snapId;
      panel.setAttribute('data-ed-snap-id', snapId);
      panel.classList.add('is-moved');
      applyHeight(t.left, t.top, snapId);
      if (opts.storageKey) {
        try { localStorage.setItem(opts.storageKey, snapId); } catch (e) { /* ignore */ }
      }
      setTimeout(function () { panel.classList.remove('is-snapping'); }, 280);
      if (!silent && typeof opts.onSnap === 'function') opts.onSnap(snapId);
    }

    function snapFrom(left, top) {
      const targets = getTargets();
      const n = nearest(targets, left, top, SNAP_RELEASE);
      if (!n) {
        currentSnapId = 'free';
        var pos = setPos(left, top);
        panel.setAttribute('data-ed-snap-id', 'free');
        panel.classList.add('is-moved');
        if (opts.storageKey) {
          try { localStorage.setItem(opts.storageKey, 'free'); } catch (e) { /* ignore */ }
          try {
            localStorage.setItem(opts.storageKey + '-free', JSON.stringify({
              leftPct: pos.left / Math.max(1, body.clientWidth),
              topPct: pos.top / Math.max(1, body.clientHeight)
            }));
          } catch (e2) { /* ignore */ }
        }
        return;
      }
      applySnap(n.id, false);
    }

    if (handle) {
      handle.addEventListener('pointerdown', function (e) {
        if (e.target.closest(opts.minSelector || '.ed-props__min')) return;
        if (e.button !== 0) return;
        e.preventDefault();
        e.stopPropagation();
        const rect = panel.getBoundingClientRect();
        const bodyRect = body.getBoundingClientRect();
        drag.pending = true;
        drag.active = false;
        drag.startX = e.clientX;
        drag.startY = e.clientY;
        drag.startLeft = rect.left - bodyRect.left;
        drag.startTop = rect.top - bodyRect.top;
      });

      const onMove = function (e) {
        if (!drag.pending && !drag.active) return;
        const dx = e.clientX - drag.startX;
        const dy = e.clientY - drag.startY;
        if (!drag.active) {
          if (Math.abs(dx) < 3 && Math.abs(dy) < 3) return;
          drag.active = true;
          drag.pending = false;
          setPos(drag.startLeft, drag.startTop);
          panel.classList.add('is-dragging');
        }
        const rawLeft = drag.startLeft + dx;
        const rawTop = drag.startTop + dy;
        const targets = getTargets();
        const magnet = nearest(targets, rawLeft, rawTop, SNAP_MAGNET);
        if (magnet) {
          panel.classList.add('is-magnet-near');
          setPos(magnet.left, magnet.top);
        } else {
          panel.classList.remove('is-magnet-near');
          setPos(rawLeft, rawTop);
        }
      };

      const onUp = function () {
        if (!drag.pending && !drag.active) return;
        const was = drag.active;
        drag.pending = false;
        drag.active = false;
        panel.classList.remove('is-dragging');
        panel.classList.remove('is-magnet-near');
        if (was) {
          const bodyRect = body.getBoundingClientRect();
          const rect = panel.getBoundingClientRect();
          snapFrom(rect.left - bodyRect.left, rect.top - bodyRect.top);
          if (typeof opts.onDragEnd === 'function') opts.onDragEnd();
        }
      };

      document.addEventListener('pointermove', onMove);
      document.addEventListener('pointerup', onUp, true);
    }

    try {
      const saved = opts.storageKey ? localStorage.getItem(opts.storageKey) : null;
      if (saved === 'free') currentSnapId = 'free';
      else if (saved === 'stack-props' && opts.defaultSnap === 'tl') currentSnapId = 'tl';
      else if (saved) currentSnapId = saved;
    } catch (e) { /* ignore */ }

    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        if (currentSnapId === 'free') {
          panel.setAttribute('data-ed-snap-id', 'free');
          panel.classList.add('is-moved');
          try {
            var free = JSON.parse(localStorage.getItem((opts.storageKey || '') + '-free') || 'null');
            if (free && typeof free.leftPct === 'number' && typeof free.topPct === 'number') {
              setPos(free.leftPct * body.clientWidth, free.topPct * body.clientHeight);
              return;
            }
          } catch (e) { /* ignore */ }
        }
        if (currentSnapId !== 'free') applySnap(currentSnapId, true);
      });
    });

    return {
      applySnap: applySnap,
      applyHeight: function () {
        const bodyRect = body.getBoundingClientRect();
        const rect = panel.getBoundingClientRect();
        applyHeight(rect.left - bodyRect.left, rect.top - bodyRect.top, currentSnapId);
      },
      getSnapId: function () { return panel.getAttribute('data-ed-snap-id') || currentSnapId; },
      panel: panel
    };
  }

  function init(rootSelector) {
    const root = document.querySelector(rootSelector || '[data-ed-root]');
    if (!root) return;

    bindFloatTools(root);

    const propsApi = bindPanel(root, {
      panelSelector: '[data-ed-props-panel]',
      handleSelector: '[data-ed-props-handle]',
      minSelector: '.ed-props__min',
      boundAttr: 'data-ed-props-bound',
      defaultSnap: 'tr',
      storageKey: 'lu-ed-props-snap',
      stretchToBottom: true
    });

    const previewApi = bindPanel(root, {
      panelSelector: '[data-ed-preview-panel]',
      handleSelector: '[data-ed-preview-handle]',
      minSelector: '.ed-props__min',
      boundAttr: 'data-ed-preview-bound',
      defaultSnap: 'tl',
      storageKey: 'lu-ed-preview-snap',
      stretchToBottom: true,
      preserveHeight: true,
      getExtraTargets: function () {
        const body = root.querySelector('[data-ed-body]');
        const props = root.querySelector('[data-ed-props-panel]');
        return stackPropsTarget(root, body, props);
      },
      onDragEnd: function () {
        // keep stack available after move
      }
    });

    // When props moves/minimizes, restack preview if docked under props
    const restack = function () {
      if (!previewApi) return;
      if (previewApi.getSnapId() === 'stack-props') {
        previewApi.applySnap('stack-props', true);
      }
    };

    const propsPanel = root.querySelector('[data-ed-props-panel]');
    if (propsPanel) {
      const syncPropsHeight = function () {
        if (propsApi && typeof propsApi.applyHeight === 'function') propsApi.applyHeight();
        restack();
      };
      const minBtn = propsPanel.querySelector('.ed-props__min');
      if (minBtn) minBtn.addEventListener('click', function () {
        setTimeout(syncPropsHeight, 0);
        requestAnimationFrame(function () { requestAnimationFrame(syncPropsHeight); });
      });
      new MutationObserver(syncPropsHeight).observe(propsPanel, { attributes: true, attributeFilter: ['class'] });
    }

    window.addEventListener('resize', function () {
      if (propsApi && propsApi.getSnapId() !== 'free') propsApi.applySnap(propsApi.getSnapId(), true);
      restack();
    });

    // Import overlay (타사포맷 플로팅 버튼 등에서 연다)
    const overlay = root.querySelector('[data-ed-import-overlay]');
    const closeBtns = root.querySelectorAll('[data-ed-import-close]');

    function openImport(tabId) {
      if (!overlay) return;
      overlay.classList.add('is-open');
      overlay.setAttribute('aria-hidden', 'false');
      if (tabId) {
        var tabBtn = overlay.querySelector('[data-tut="import-tab-' + tabId + '"]');
        if (tabBtn) {
          requestAnimationFrame(function () { tabBtn.click(); });
        }
      }
    }

    function closeImport() {
      if (!overlay) return;
      overlay.classList.remove('is-open');
      overlay.setAttribute('aria-hidden', 'true');
    }

    closeBtns.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        closeImport();
      });
    });

    if (overlay) {
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeImport();
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      if (overlay && overlay.classList.contains('is-open')) closeImport();
    });

    window.labelUpEditor = window.labelUpEditor || {};
    window.labelUpEditor.openImport = openImport;
    window.labelUpEditor.closeImport = closeImport;
  }

  function readPct(key) {
    try {
      var raw = localStorage.getItem(key);
      if (!raw) return null;
      var o = JSON.parse(raw);
      if (o && typeof o.leftPct === 'number' && typeof o.topPct === 'number') return o;
    } catch (e) { /* ignore */ }
    return null;
  }

  function panelLayout(sel, storageKey, containerSel) {
    var panel = document.querySelector(sel);
    var container = document.querySelector(containerSel);
    if (!panel || !container) {
      return { snap: localStorage.getItem(storageKey) || null, free: readPct(storageKey + '-free') };
    }
    var snap = panel.getAttribute('data-ed-snap-id') || localStorage.getItem(storageKey) || null;
    var rect = panel.getBoundingClientRect();
    var crect = container.getBoundingClientRect();
    return {
      snap: snap,
      leftPct: (rect.left - crect.left) / Math.max(1, crect.width),
      topPct: (rect.top - crect.top) / Math.max(1, crect.height),
      free: readPct(storageKey + '-free')
    };
  }

  function getUiLayout() {
    var float = document.querySelector('[data-ed-float-tools]');
    var workspace = document.querySelector('[data-ed-workspace]');
    var floatLayout = {
      corner: (float && float.getAttribute('data-ed-float-corner')) || localStorage.getItem('lu-ed-float-corner') || 'tl',
      orient: (float && float.getAttribute('data-ed-float-orient')) || localStorage.getItem('lu-ed-float-orient') || 'horizontal',
      free: readPct('lu-ed-float-free')
    };
    if (float && workspace && floatLayout.corner === 'free') {
      var fr = float.getBoundingClientRect();
      var wr = workspace.getBoundingClientRect();
      floatLayout.leftPct = (fr.left - wr.left) / Math.max(1, wr.width);
      floatLayout.topPct = (fr.top - wr.top) / Math.max(1, wr.height);
    }
    return {
      version: 1,
      float: floatLayout,
      props: panelLayout('[data-ed-props-panel]', 'lu-ed-props-snap', '[data-ed-body]'),
      preview: panelLayout('[data-ed-preview-panel]', 'lu-ed-preview-snap', '[data-ed-body]')
    };
  }

  function applyUiLayout(layout) {
    if (!layout || typeof layout !== 'object') return false;
    try {
      if (layout.float) {
        if (layout.float.corner) localStorage.setItem('lu-ed-float-corner', layout.float.corner);
        if (layout.float.orient) localStorage.setItem('lu-ed-float-orient', layout.float.orient);
        if (layout.float.corner === 'free' && (layout.float.free || (layout.float.leftPct != null))) {
          localStorage.setItem('lu-ed-float-free', JSON.stringify(layout.float.free || {
            leftPct: layout.float.leftPct,
            topPct: layout.float.topPct
          }));
        }
      }
      if (layout.props) {
        if (layout.props.snap) localStorage.setItem('lu-ed-props-snap', layout.props.snap);
        if (layout.props.snap === 'free') {
          localStorage.setItem('lu-ed-props-snap-free', JSON.stringify(layout.props.free || {
            leftPct: layout.props.leftPct,
            topPct: layout.props.topPct
          }));
        }
      }
      if (layout.preview) {
        if (layout.preview.snap) localStorage.setItem('lu-ed-preview-snap', layout.preview.snap);
        if (layout.preview.snap === 'free') {
          localStorage.setItem('lu-ed-preview-snap-free', JSON.stringify(layout.preview.free || {
            leftPct: layout.preview.leftPct,
            topPct: layout.preview.topPct
          }));
        }
      }
    } catch (e) {
      console.warn('[LabelUp] applyUiLayout', e);
      return false;
    }
    // Re-init panels so saved layout is applied to the live DOM.
    var root = document.querySelector('[data-ed-root]');
    if (root) {
      root.querySelectorAll('[data-ed-bound],[data-ed-props-bound],[data-ed-preview-bound]').forEach(function (el) {
        el.removeAttribute('data-ed-bound');
        el.removeAttribute('data-ed-props-bound');
        el.removeAttribute('data-ed-preview-bound');
      });
      if (typeof window.labelUpEditor.initPanels === 'function') {
        window.labelUpEditor.initPanels('[data-ed-root]');
      }
    }
    return true;
  }

  window.labelUpEditor = window.labelUpEditor || {};
  window.labelUpEditor.initPanels = init;
  window.labelUpEditor.getUiLayout = getUiLayout;
  window.labelUpEditor.applyUiLayout = applyUiLayout;
})();
