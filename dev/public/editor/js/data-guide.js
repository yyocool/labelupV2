/**
 * 자료(엑셀)를 들여온 뒤 「이제 무엇을 하면 되는지」를 짚어 주는 안내.
 * - 한눈에 보기: 화면을 어둡게 덮고, 눌러 볼 자리마다 번호 말풍선을 붙인다.
 * - 직접 해 보기: 한 단계씩 진짜 단추를 누르게 하고, 눌렀는지 화면을 보고 다음으로 넘어간다.
 * Blazor 가 그리는 DOM 바깥(body 끝)에 붙여 다시 그리기와 부딪히지 않는다.
 */
(function () {
  'use strict';

  var STORAGE_OFF = 'lu-ed-data-guide-off';
  var ROOT_ID = 'lu-data-guide';
  var STYLE_ID = 'lu-data-guide-style';
  var PAD = 6;
  var GAP = 14;
  var MARGIN = 10;
  var TICK_MS = 250;
  var PRACTICE_STEPS = 3;

  var SEL = {
    add: '.ed-data-colhead__add',
    bound: '.ed-data-colhead__on',
    bindCard: '.ed-modal[aria-label="열 표시 형식"] .ed-modal__card',
    bindOk: '.ed-modal[aria-label="열 표시 형식"] .ed-modal__foot .ed-btn--primary',
    applyAll: '[data-tut="preview-apply-all"]',
    preview: '[data-tut="preview"]',
    previewPage: '.ed-preview__page-wrap',
    canvas: '.canvas-stage__skia',
    help: '[data-tut="data-guide-help"]'
  };

  var ITEMS = [
    {
      sel: SEL.add,
      alt: SEL.bound,
      sides: ['top', 'right', 'left', 'bottom'],
      title: '라벨에 넣을 칸 고르기',
      text: '넣고 싶은 열의 [라벨에 추가]를 누르세요. 엑셀 한 줄이 라벨 한 장에 자동으로 들어가요.',
      altText: '[추가됨]이 붙은 열은 이미 라벨에 들어가 있어요. 다른 열도 [라벨에 추가]로 더 넣을 수 있어요.'
    },
    {
      sel: SEL.applyAll,
      fallback: SEL.preview,
      sides: ['left', 'bottom', 'top'],
      star: true,
      title: '모든 라벨에 한 번에 적용',
      text: '가운데 라벨 한 장을 꾸민 뒤 [전체라벨 적용]을 누르면 같은 모양이 모든 라벨에 똑같이 들어가요. '
        + '글자 내용은 줄마다 알아서 바뀌니, 데이터가 많아도 하나씩 고칠 필요가 없어요. '
        + '([현재 페이지적용]은 지금 보는 페이지에만 들어가요.)',
      fallbackText: '오른쪽 미리보기 창을 펼치면 [전체라벨 적용]이 있어요. 한 장만 꾸민 뒤 누르면 모든 라벨에 똑같이 들어가요.'
    },
    {
      sel: SEL.help,
      sides: ['top', 'left'],
      title: '안내 다시 보기',
      text: '이 안내는 데이터 창 오른쪽 [?]를 누르면 언제든 다시 볼 수 있어요.'
    }
  ];

  var state = null;

  function isOff() {
    try { return localStorage.getItem(STORAGE_OFF) === '1'; } catch (e) { return false; }
  }

  function setOff(on) {
    try {
      if (on) localStorage.setItem(STORAGE_OFF, '1');
      else localStorage.removeItem(STORAGE_OFF);
    } catch (e) { /* 저장소를 못 쓰면 이번 창에서만 닫는다 */ }
  }

  function isMobile() {
    var ed = window.labelUpEditor;
    if (ed && typeof ed.isMobileEditor === 'function') {
      try { return !!ed.isMobileEditor(); } catch (e) { /* 아래로 */ }
    }
    return window.innerWidth < 900;
  }

  function visibleRect(el) {
    if (!el) return null;
    var r = el.getBoundingClientRect();
    if (r.width < 2 || r.height < 2) return null;
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var left = Math.max(0, r.left);
    var top = Math.max(0, r.top);
    var right = Math.min(vw, r.right);
    var bottom = Math.min(vh, r.bottom);
    if (right - left < 2 || bottom - top < 2) return null;
    var cs = getComputedStyle(el);
    if (cs.visibility === 'hidden' || cs.display === 'none') return null;
    return { left: left, top: top, right: right, bottom: bottom, width: right - left, height: bottom - top };
  }

  function firstVisible(sel) {
    var list = document.querySelectorAll(sel);
    for (var i = 0; i < list.length; i++) {
      var r = visibleRect(list[i]);
      if (r) return { el: list[i], rect: r };
    }
    return null;
  }

  function countOf(sel) {
    return document.querySelectorAll(sel).length;
  }

  function ensureStyle() {
    var R = '#' + ROOT_ID;
    var css = ''
      + R + '{position:fixed;inset:0;z-index:10050;font-family:Pretendard,"Noto Sans KR",sans-serif;}'
      + R + '.is-practice{pointer-events:none;}'
      + R + '.is-practice .ldg-tip{pointer-events:auto;}'
      + R + ' .ldg-dim{position:absolute;inset:0;width:100%;height:100%;}'
      + R + ' .ldg-ring{position:absolute;border:2px solid #fff;border-radius:10px;'
      + 'box-shadow:0 0 0 4px rgba(255,255,255,.22),0 0 18px rgba(255,255,255,.35);pointer-events:none;}'
      + R + ' .ldg-ring.is-star{border-color:#ffd166;box-shadow:0 0 0 4px rgba(255,209,102,.35),0 0 22px rgba(255,209,102,.55);}'
      + R + ' .ldg-ring.is-pulse{animation:ldg-pulse 1.3s ease-in-out infinite;}'
      + R + ' .ldg-ring.is-soft{border-style:dashed;border-color:rgba(255,255,255,.75);box-shadow:none;}'
      + '@keyframes ldg-pulse{0%,100%{box-shadow:0 0 0 3px rgba(255,209,102,.35),0 0 14px rgba(255,209,102,.45);}'
      + '50%{box-shadow:0 0 0 9px rgba(255,209,102,.12),0 0 26px rgba(255,209,102,.7);}}'
      + R + ' .ldg-tip{position:absolute;width:250px;box-sizing:border-box;padding:11px 13px 12px;'
      + 'background:#fff;border-radius:12px;box-shadow:0 10px 28px rgba(0,0,0,.28);color:#2e2a27;}'
      + R + ' .ldg-tip.is-star{width:290px;border:2px solid #ffd166;}'
      + R + ' .ldg-tip h4{display:flex;align-items:center;gap:7px;margin:0 0 5px;font-size:14px;font-weight:800;color:#7b2840;}'
      + R + ' .ldg-tip p{margin:0;font-size:13px;line-height:1.5;word-break:keep-all;}'
      + R + ' .ldg-tip .ldg-flash{margin:0 0 6px;padding:5px 8px;border-radius:7px;background:#e9f7ee;color:#1f7a43;font-weight:700;}'
      + R + ' .ldg-tip__foot{display:flex;align-items:center;gap:6px;margin-top:10px;}'
      + R + ' .ldg-tip__step{margin-right:auto;font-size:12px;font-weight:700;color:#9a8f88;}'
      + R + ' .ldg-tip .ldg-btn{padding:5px 11px;font-size:12px;}'
      + R + ' .ldg-num{flex-shrink:0;display:inline-grid;place-items:center;width:21px;height:21px;border-radius:50%;'
      + 'background:#7b2840;color:#fff;font-size:12px;font-weight:800;}'
      + R + ' .ldg-badge{position:absolute;display:grid;place-items:center;width:24px;height:24px;border-radius:50%;'
      + 'background:#7b2840;color:#fff;font-size:12px;font-weight:800;border:2px solid #fff;pointer-events:none;}'
      + R + ' .ldg-star{margin-left:auto;white-space:nowrap;font-size:11px;font-weight:800;color:#a86a00;background:#fff4d6;border-radius:6px;padding:1px 6px;}'
      + R + ' .ldg-head{position:absolute;left:50%;transform:translateX(-50%);width:min(460px,calc(100vw - 24px));'
      + 'box-sizing:border-box;padding:14px 16px;background:#fff;border-radius:14px;box-shadow:0 12px 32px rgba(0,0,0,.3);text-align:center;}'
      + R + ' .ldg-head strong{display:block;font-size:16px;font-weight:800;color:#2e2a27;}'
      + R + ' .ldg-head span{display:block;margin-top:4px;font-size:13px;color:#6b625c;word-break:keep-all;}'
      + R + ' .ldg-head__acts{display:flex;justify-content:center;gap:8px;margin-top:11px;}'
      + R + ' .ldg-btn{border:1px solid #e3d6cf;background:#fff;color:#4a423d;border-radius:9px;padding:7px 14px;'
      + 'font:inherit;font-size:13px;font-weight:700;cursor:pointer;}'
      + R + ' .ldg-btn--primary{background:#7b2840;border-color:#7b2840;color:#fff;}';
    var style = document.getElementById(STYLE_ID);
    if (!style) {
      style = document.createElement('style');
      style.id = STYLE_ID;
      document.head.appendChild(style);
    }
    if (style.textContent !== css) style.textContent = css;
  }

  function overlap(a, b) {
    var w = Math.min(a.right, b.right) - Math.max(a.left, b.left);
    var h = Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top);
    return w > 0 && h > 0 ? w * h : 0;
  }

  function box(left, top, w, h) {
    return { left: left, top: top, right: left + w, bottom: top + h, width: w, height: h };
  }

  /** align: 0 가운데, -1 앞쪽(왼쪽·위)에 맞춤, 1 뒤쪽(오른쪽·아래)에 맞춤 */
  function candidate(side, r, w, h, align) {
    var cx = align < 0 ? r.left : align > 0 ? r.right - w : r.left + r.width / 2 - w / 2;
    var cy = align < 0 ? r.top : align > 0 ? r.bottom - h : r.top + r.height / 2 - h / 2;
    switch (side) {
      case 'top': return box(cx, r.top - PAD - GAP - h, w, h);
      case 'bottom': return box(cx, r.bottom + PAD + GAP, w, h);
      case 'left': return box(r.left - PAD - GAP - w, cy, w, h);
      case 'right': return box(r.right + PAD + GAP, cy, w, h);
      default: return box(cx, cy, w, h);
    }
  }

  function clampBox(b) {
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var left = Math.min(Math.max(MARGIN, b.left), vw - MARGIN - b.width);
    var top = Math.min(Math.max(MARGIN, b.top), vh - MARGIN - b.height);
    return box(left, top, b.width, b.height);
  }

  function offscreen(b) {
    return b.left < MARGIN || b.top < MARGIN
      || b.right > window.innerWidth - MARGIN || b.bottom > window.innerHeight - MARGIN;
  }

  /** 말풍선끼리, 그리고 짚는 자리와 겹치지 않는 쪽을 고른다. 다 겹치면 가장 덜 겹치는 쪽. */
  function place(anchor, sidesPref, w, h, taken, holes) {
    var best = null;
    var bestCost = Infinity;
    var sides = sidesPref.concat(['top', 'bottom', 'left', 'right']);
    var aligns = [0, -1, 1];
    var order = 0;
    for (var i = 0; i < sides.length; i++) {
      for (var a = 0; a < aligns.length; a++, order++) {
        var raw = candidate(sides[i], anchor, w, h, aligns[a]);
        var b = clampBox(raw);
        var cost = offscreen(raw) ? 4000 : 0;
        taken.forEach(function (t) { cost += overlap(b, t) * 3; });
        holes.forEach(function (hole) {
          if (hole !== anchor) cost += overlap(b, hole) * 2;
        });
        cost += overlap(b, anchor) * 2;
        cost += order * 4;
        if (cost < bestCost) {
          bestCost = cost;
          best = b;
        }
        if (cost === order * 4) return best;
      }
    }
    return best;
  }

  /**
   * scene = {
   *   holes: [{ rect, ring: 'normal'|'star'|'pulse'|'soft'|null, badge: n|null }],
   *   tips: [{ anchor, sides, num, title, text, flash, star, foot: [{label, action, primary}], step }],
   *   head: { title, text, acts: [{label, action, primary}] } | null,
   *   dim: 0..1
   * }
   */
  function paint(scene) {
    var root = state.root;
    root.innerHTML = '';
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var svgNs = 'http://www.w3.org/2000/svg';
    var svg = document.createElementNS(svgNs, 'svg');
    svg.setAttribute('class', 'ldg-dim');
    svg.setAttribute('viewBox', '0 0 ' + vw + ' ' + vh);
    svg.setAttribute('preserveAspectRatio', 'none');
    var mask = document.createElementNS(svgNs, 'mask');
    mask.setAttribute('id', 'ldg-mask');
    var full = document.createElementNS(svgNs, 'rect');
    full.setAttribute('width', vw);
    full.setAttribute('height', vh);
    full.setAttribute('fill', '#fff');
    mask.appendChild(full);
    scene.holes.forEach(function (h) {
      var cut = document.createElementNS(svgNs, 'rect');
      cut.setAttribute('x', h.rect.left - PAD);
      cut.setAttribute('y', h.rect.top - PAD);
      cut.setAttribute('width', h.rect.width + PAD * 2);
      cut.setAttribute('height', h.rect.height + PAD * 2);
      cut.setAttribute('rx', 10);
      cut.setAttribute('fill', '#000');
      mask.appendChild(cut);
    });
    var defs = document.createElementNS(svgNs, 'defs');
    defs.appendChild(mask);
    svg.appendChild(defs);
    var shade = document.createElementNS(svgNs, 'rect');
    shade.setAttribute('width', vw);
    shade.setAttribute('height', vh);
    shade.setAttribute('fill', 'rgba(24,18,16,' + (scene.dim == null ? 0.62 : scene.dim) + ')');
    shade.setAttribute('mask', 'url(#ldg-mask)');
    svg.appendChild(shade);
    root.appendChild(svg);

    var taken = [];
    var holeRects = scene.holes.map(function (h) { return h.rect; });

    if (scene.head) {
      var head = document.createElement('div');
      head.className = 'ldg-head';
      var strong = document.createElement('strong');
      strong.textContent = scene.head.title;
      var span = document.createElement('span');
      span.textContent = scene.head.text;
      head.appendChild(strong);
      head.appendChild(span);
      head.appendChild(buttonRow('ldg-head__acts', scene.head.acts));
      root.appendChild(head);
      head.style.top = Math.max(MARGIN, Math.min(110, vh * 0.12)) + 'px';
      var hr = head.getBoundingClientRect();
      taken.push(box(hr.left, hr.top, hr.width, hr.height));
    }

    scene.holes.forEach(function (h) {
      if (h.ring) {
        var ring = document.createElement('div');
        ring.className = 'ldg-ring'
          + (h.ring === 'star' || h.ring === 'pulse' ? ' is-star' : '')
          + (h.ring === 'pulse' ? ' is-pulse' : '')
          + (h.ring === 'soft' ? ' is-soft' : '');
        ring.style.left = (h.rect.left - PAD) + 'px';
        ring.style.top = (h.rect.top - PAD) + 'px';
        ring.style.width = (h.rect.width + PAD * 2) + 'px';
        ring.style.height = (h.rect.height + PAD * 2) + 'px';
        root.appendChild(ring);
      }
      if (h.badge) {
        var badge = document.createElement('div');
        badge.className = 'ldg-badge';
        badge.textContent = String(h.badge);
        badge.style.left = Math.max(2, h.rect.left - PAD - 12) + 'px';
        badge.style.top = Math.max(2, h.rect.top - PAD - 12) + 'px';
        root.appendChild(badge);
      }
    });

    scene.tips.forEach(function (t) {
      var tip = document.createElement('div');
      tip.className = 'ldg-tip' + (t.star ? ' is-star' : '');
      var h4 = document.createElement('h4');
      if (t.num) {
        var num = document.createElement('span');
        num.className = 'ldg-num';
        num.textContent = String(t.num);
        h4.appendChild(num);
      }
      h4.appendChild(document.createTextNode(t.title));
      if (t.star) {
        var star = document.createElement('span');
        star.className = 'ldg-star';
        star.textContent = '꼭 써 보세요';
        h4.appendChild(star);
      }
      tip.appendChild(h4);
      if (t.flash) {
        var flash = document.createElement('p');
        flash.className = 'ldg-flash';
        flash.textContent = t.flash;
        tip.appendChild(flash);
      }
      var p = document.createElement('p');
      p.textContent = t.text;
      tip.appendChild(p);
      if (t.foot) {
        var foot = buttonRow('ldg-tip__foot', t.foot);
        if (t.step) {
          var step = document.createElement('span');
          step.className = 'ldg-tip__step';
          step.textContent = t.step;
          foot.insertBefore(step, foot.firstChild);
        }
        tip.appendChild(foot);
      }
      tip.style.visibility = 'hidden';
      root.appendChild(tip);
      var tr = tip.getBoundingClientRect();
      var spot = place(t.anchor, t.sides || [], tr.width, tr.height, taken, holeRects);
      tip.style.left = spot.left + 'px';
      tip.style.top = spot.top + 'px';
      tip.style.visibility = '';
      taken.push(spot);
    });
  }

  function buttonRow(cls, acts) {
    var row = document.createElement('div');
    row.className = cls;
    (acts || []).forEach(function (a) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'ldg-btn' + (a.primary ? ' ldg-btn--primary' : '');
      b.setAttribute('data-ldg-act', a.action);
      b.textContent = a.label;
      row.appendChild(b);
    });
    return row;
  }

  /* ---------- 한눈에 보기 ---------- */

  function overviewScene() {
    var holes = [];
    var tips = [];
    ITEMS.forEach(function (item) {
      var found = firstVisible(item.sel);
      var text = item.text;
      if (!found && item.alt) {
        found = firstVisible(item.alt);
        text = item.altText || text;
      }
      if (!found && item.fallback) {
        found = firstVisible(item.fallback);
        text = item.fallbackText || text;
      }
      if (!found) return;
      var n = tips.length + 1;
      holes.push({ rect: found.rect, ring: item.star ? 'star' : 'normal', badge: n });
      tips.push({ anchor: found.rect, sides: item.sides, num: n, title: item.title, text: text, star: item.star });
    });
    if (tips.length < 2) return null;
    return {
      holes: holes,
      tips: tips,
      head: {
        title: '데이터를 불러왔어요! 이제 이렇게 해 보세요',
        text: '번호 순서대로 하면 돼요. [직접 해 보기]를 누르면 하나씩 같이 눌러 보며 익힐 수 있어요.',
        acts: [
          { label: '다시 보지 않기', action: 'off' },
          { label: '알겠어요', action: 'close' },
          { label: '직접 해 보기', action: 'practice', primary: true }
        ]
      }
    };
  }

  /* ---------- 직접 해 보기 ---------- */

  var PRACTICE_FOOT = [{ label: '그만하기', action: 'close' }];

  function practiceScene() {
    var p = state.practice;
    var flash = p.flash && Date.now() < p.flashUntil ? p.flash : null;

    if (p.step === 'add') {
      var add = firstVisible(SEL.add);
      if (!add) return null;
      return {
        dim: 0.55,
        holes: [{ rect: add.rect, ring: 'pulse', badge: 1 }],
        tips: [{
          anchor: add.rect, sides: ['top', 'right', 'left'], num: 1,
          title: '라벨에 넣을 칸 고르기',
          text: '반짝이는 [라벨에 추가]를 눌러 보세요. 이 열의 내용이 엑셀 한 줄마다 라벨 한 장씩 들어가요.',
          flash: flash, step: '1 / ' + PRACTICE_STEPS, foot: PRACTICE_FOOT
        }]
      };
    }

    if (p.step === 'bind') {
      var card = firstVisible(SEL.bindCard);
      var ok = firstVisible(SEL.bindOk);
      if (!card) return null;
      var holes = [{ rect: card.rect, ring: 'soft' }];
      if (ok) holes.push({ rect: ok.rect, ring: 'pulse', badge: 1 });
      return {
        dim: 0.35,
        holes: holes,
        tips: [{
          anchor: card.rect, sides: ['right', 'left', 'bottom'], num: 1,
          title: '넣는 방법 고르기',
          text: '처음이면 [텍스트 그대로 추가]로 두고 아래 [라벨에 넣기]를 누르면 돼요.',
          step: '1 / ' + PRACTICE_STEPS, foot: PRACTICE_FOOT
        }]
      };
    }

    if (p.step === 'apply') {
      var apply = firstVisible(SEL.applyAll);
      var canvas = firstVisible(SEL.canvas);
      if (!apply) {
        var panel = firstVisible(SEL.preview);
        if (!panel) return null;
        return {
          dim: 0.45,
          holes: [{ rect: panel.rect, ring: 'pulse', badge: 2 }],
          tips: [{
            anchor: panel.rect, sides: ['left', 'bottom'], num: 2,
            title: '미리보기 창 펼치기',
            text: '미리보기 창이 접혀 있어요. 창 위쪽 막대의 [—]를 눌러 펼치면 [전체라벨 적용]이 나와요.',
            flash: flash, step: '2 / ' + PRACTICE_STEPS, foot: PRACTICE_FOOT
          }]
        };
      }
      var applyHoles = [];
      if (canvas) applyHoles.push({ rect: canvas.rect, ring: 'soft' });
      applyHoles.push({ rect: apply.rect, ring: 'pulse', badge: 2 });
      return {
        dim: 0.45,
        holes: applyHoles,
        tips: [{
          anchor: apply.rect, sides: ['left', 'bottom', 'top'], num: 2, star: true,
          title: '모든 라벨에 한 번에 적용',
          text: '가운데 라벨에서 글자를 눌러 크기나 위치를 바꿔 봐도 좋아요. '
            + '다 꾸몄으면 반짝이는 [전체라벨 적용]을 누르세요. 모든 라벨이 같은 모양이 되고, 글자만 줄마다 바뀌어요.',
          flash: flash, step: '2 / ' + PRACTICE_STEPS, foot: PRACTICE_FOOT
        }]
      };
    }

    if (p.step === 'done') {
      var page = firstVisible(SEL.previewPage) || firstVisible(SEL.preview);
      var help = firstVisible(SEL.help);
      var doneHoles = [];
      if (page) doneHoles.push({ rect: page.rect, ring: 'star' });
      if (help) doneHoles.push({ rect: help.rect, ring: 'normal', badge: '?' });
      var anchor = page ? page.rect : help ? help.rect : box(window.innerWidth / 2 - 1, window.innerHeight / 2 - 1, 2, 2);
      return {
        dim: 0.45,
        holes: doneHoles,
        tips: [{
          anchor: anchor, sides: ['left', 'bottom'], num: 3,
          title: '다 됐어요!',
          flash: flash,
          text: '미리보기에서 라벨마다 엑셀 줄이 하나씩 들어간 걸 확인해 보세요. '
            + '라벨을 고친 뒤에는 언제든 [전체라벨 적용]을 다시 누르면 돼요. 이 안내는 데이터 창 오른쪽 [?]로 다시 볼 수 있어요.',
          step: '3 / ' + PRACTICE_STEPS,
          foot: [{ label: '끝내기', action: 'close', primary: true }]
        }]
      };
    }
    return null;
  }

  function setFlash(text) {
    state.practice.flash = text;
    state.practice.flashUntil = Date.now() + 6000;
  }

  /** 화면을 보고 사용자가 해낸 일을 알아챈다. 단추를 눌렀는지는 Blazor 가 바꾼 DOM 으로 판단한다. */
  function advance() {
    var p = state.practice;
    if (p.step === 'add' || p.step === 'bind') {
      var dialogOpen = !!document.querySelector(SEL.bindCard);
      var bound = countOf(SEL.bound);
      if (dialogOpen) {
        p.step = 'bind';
      } else if (bound > p.boundAtStart) {
        p.step = 'apply';
        setFlash('잘했어요! 가운데 라벨에 글자가 들어갔어요.');
      } else if (p.step === 'bind') {
        p.step = 'add';
      } else if (!firstVisible(SEL.add)) {
        p.step = 'apply';
      }
    }
    if (p.step === 'apply' && p.applied) {
      p.step = 'done';
      setFlash('모든 라벨에 적용됐어요!');
    }
  }

  function startPractice() {
    if (!state) return;
    state.mode = 'practice';
    state.root.classList.add('is-practice');
    state.practice = {
      step: firstVisible(SEL.add) ? 'add' : 'apply',
      boundAtStart: countOf(SEL.bound),
      applied: false,
      flash: null,
      flashUntil: 0,
      misses: 0
    };
    state.sig = '';
    state.onApplyClick = function (e) {
      var t = e.target;
      if (state && state.practice && state.practice.step === 'apply' && t && t.closest && t.closest(SEL.applyAll)) {
        state.practice.applied = true;
      }
    };
    document.addEventListener('click', state.onApplyClick, true);
    state.timer = setInterval(tick, TICK_MS);
    tick();
  }

  /* ---------- 공통 ---------- */

  function signature(scene) {
    if (!scene) return 'none';
    var parts = [window.innerWidth, window.innerHeight];
    scene.holes.forEach(function (h) {
      parts.push(Math.round(h.rect.left), Math.round(h.rect.top), Math.round(h.rect.width), Math.round(h.rect.height));
    });
    scene.tips.forEach(function (t) { parts.push(t.title, t.flash || ''); });
    return parts.join('|');
  }

  function tick() {
    if (!state || state.mode !== 'practice') return;
    advance();
    var scene = practiceScene();
    if (!scene) {
      // 판넬을 다시 그리는 사이 잠깐 자리가 비는 것은 기다린다.
      if (++state.practice.misses > 24) close();
      return;
    }
    state.practice.misses = 0;
    var sig = signature(scene);
    if (sig === state.sig) return;
    state.sig = sig;
    paint(scene);
  }

  function render() {
    if (!state) return;
    if (state.mode === 'practice') {
      state.sig = '';
      tick();
      return;
    }
    var scene = overviewScene();
    if (!scene) {
      close();
      return;
    }
    paint(scene);
  }

  function close() {
    if (!state) return;
    window.removeEventListener('resize', state.onResize);
    document.removeEventListener('keydown', state.onKey, true);
    if (state.onApplyClick) document.removeEventListener('click', state.onApplyClick, true);
    if (state.timer) clearInterval(state.timer);
    if (state.root.parentNode) state.root.parentNode.removeChild(state.root);
    state = null;
  }

  function open(practice) {
    close();
    ensureStyle();
    var root = document.createElement('div');
    root.id = ROOT_ID;
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-label', '데이터 사용 안내');
    document.body.appendChild(root);
    var resizeTimer = 0;
    state = {
      root: root,
      mode: 'overview',
      onResize: function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(render, 120);
      },
      onKey: function (e) {
        if (e.key !== 'Escape') return;
        // 직접 해 보기 중 열 형식 창이 떠 있으면 Esc 는 그 창을 닫는 데 쓴다.
        if (state && state.mode === 'practice' && document.querySelector(SEL.bindCard)) return;
        e.stopPropagation();
        close();
      }
    };
    root.addEventListener('click', function (e) {
      var t = e.target;
      var btn = t && t.closest ? t.closest('[data-ldg-act]') : null;
      var act = btn ? btn.getAttribute('data-ldg-act') : null;
      if (act === 'off') {
        setOff(true);
        close();
        return;
      }
      if (act === 'practice') {
        startPractice();
        return;
      }
      if (act === 'close') {
        close();
        return;
      }
      if (state && state.mode === 'practice') return;
      if (t && t.closest && t.closest('.ldg-tip, .ldg-head')) return;
      close();
    });
    window.addEventListener('resize', state.onResize);
    document.addEventListener('keydown', state.onKey, true);
    if (practice) startPractice();
    else render();
  }

  function waitThen(fn, delay) {
    var tries = 0;
    (function attempt() {
      tries++;
      if (!document.querySelector('[data-ed-data-grip]') && tries < 20) {
        setTimeout(attempt, 150);
        return;
      }
      setTimeout(fn, delay);
    })();
  }

  /**
   * @param {boolean} force 물음표를 눌렀을 때. 「다시 보지 않기」를 골랐어도 보여 준다.
   * 자동으로 띄울 때는 판넬·미리보기가 자리를 잡을 때까지 잠깐 기다린다.
   */
  function show(force) {
    if (!force && (isOff() || isMobile())) return;
    waitThen(function () { open(false); }, force ? 0 : 350);
  }

  window.labelUpDataGuide = {
    show: show,
    practice: function () { waitThen(function () { open(true); }, 0); },
    close: close,
    reset: function () { setOff(false); }
  };
})();
