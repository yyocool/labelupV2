(function () {
  var page = window.COMPAT_PAGE || { groups: [], items: [], stats: {} };
  var DATA = page.items || [];
  var GROUPS = page.groups || [];
  var SHEET_W = 210, SHEET_H = 297;
  var query = '';
  var activeGid = 'all';
  var hits = {};

  function layout(n, d) {
    if (!d || !n) return null;
    var w = d[0], h = d[1];
    var maxc = Math.max(1, Math.floor(SHEET_W / w)), c;
    for (c = maxc; c >= 1; c--) {
      if (n % c) continue;
      if ((n / c) * h <= SHEET_H + 2) return { c: c, r: n / c, w: w, h: h };
    }
    for (c = maxc; c >= 1; c--) if (n % c === 0) return { c: c, r: n / c, w: w, h: h };
    return { c: 1, r: n, w: w, h: h };
  }

  function diagram(row, color) {
    var n = parseInt(row.cn, 10);
    var L = layout(n, row.d);
    var s = '<svg class="diag" viewBox="0 0 210 297" role="img" aria-label="'
      + (L ? L.c + '열 ' + L.r + '행 배치' : '세트 구성') + '">';
    s += '<rect x="1.5" y="1.5" width="207" height="294" rx="7" fill="#fff"'
      + ' stroke="var(--line)" stroke-width="3"/>';
    if (!L) {
      for (var k = 0; k < 3; k++) {
        s += '<rect x="34" y="' + (74 + k * 62) + '" width="142" height="40" rx="4" fill="' + color
          + '" fill-opacity=".15" stroke="' + color + '" stroke-width="3"/>';
      }
      return s + '</svg>';
    }
    var c = L.c, r = L.r, w = L.w, h = L.h, scale = 1;
    if (r * h > SHEET_H) scale = SHEET_H * 0.94 / (r * h);
    var cw = w * scale, ch = h * scale;
    var gx = (SHEET_W - c * cw) / (c + 1), gy = (SHEET_H - r * ch) / (r + 1);
    var i, j;
    if (n > 40) {
      var wall = SHEET_W - 2 * gx;
      for (i = 0; i < r; i++) {
        s += '<rect x="' + gx.toFixed(1) + '" y="' + (gy + i * (ch + gy)).toFixed(1)
          + '" width="' + wall.toFixed(1) + '" height="' + ch.toFixed(1) + '" fill="' + color
          + '" fill-opacity=".15" stroke="' + color + '" stroke-width="1.8"/>';
      }
      for (j = 1; j < c; j++) {
        var vx = (gx * (j + 0.5) + cw * j).toFixed(1);
        s += '<line x1="' + vx + '" y1="' + gy.toFixed(1) + '" x2="' + vx + '" y2="'
          + (SHEET_H - gy).toFixed(1) + '" stroke="' + color
          + '" stroke-width="1.8" stroke-opacity=".55"/>';
      }
      return s + '</svg>';
    }
    var sw = n > 24 ? 2.3 : 3;
    var rad = Math.min(5, cw / 6, ch / 6);
    for (i = 0; i < r; i++) for (j = 0; j < c; j++) {
      s += '<rect x="' + (gx + j * (cw + gx)).toFixed(1) + '" y="' + (gy + i * (ch + gy)).toFixed(1)
        + '" width="' + cw.toFixed(1) + '" height="' + ch.toFixed(1) + '" rx="' + rad.toFixed(1)
        + '" fill="' + color + '" fill-opacity=".15" stroke="' + color
        + '" stroke-width="' + sw + '"/>';
    }
    return s + '</svg>';
  }

  function esc(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function displayCode(primary, all) {
    var list = (all && all.length) ? all : (primary ? [primary] : []);
    if (!list.length) return '';
    return list.join(' · ');
  }

  function codeCell(v, label, area) {
    return '<div class="c-' + area + '"><span class="klabel">' + label + '</span>'
      + (v ? '<span class="code">' + esc(v) + '</span>' : '<span class="code none">—</span>')
      + '</div>';
  }

  function renderRow(x, color) {
    var cells = /set$/i.test(String(x.cn)) ? String(x.cn).replace(/set/i, '세트') : (x.cn + '칸');
    var size = x.sz === '-' ? '세트' : (x.sz || 'A4');
    var f = displayCode(x.f, x.f_all);
    var a = displayCode(x.a, x.a_all);
    var i = displayCode(x.i, x.i_all);
    return '<div class="row" id="p-' + esc(x.pn) + '" data-pn="' + esc(x.pn) + '">'
      + '<div class="c-diag">' + diagram(x, color) + '</div>'
      + codeCell(f, '폼텍', 'f') + codeCell(a, '애니라벨', 'a')
      + codeCell(i, '아이라벨', 'i')
      + '<div class="c-up"><button class="pn" type="button" title="품번 복사">'
      + esc(x.pn) + '</button><span class="sub">' + esc(x.sub || '') + ' · ' + esc(x.sh || '') + '매</span></div>'
      + '<div class="c-sz"><span class="szmain">' + esc(size) + ' ' + esc(cells) + '</span>'
      + '<span class="szfine">' + (x.dt ? esc(String(x.dt).replace('x', ' × ')) + ' mm' : '—') + '</span></div>'
      + '</div>';
  }

  var COLHEAD = '<div class="colhead"><span class="c-diag">칸</span>'
    + '<span class="c-f">폼텍</span><span class="c-a">애니라벨</span>'
    + '<span class="c-i">아이라벨</span>'
    + '<span class="c-up">라벨업 품번</span>'
    + '<span class="c-sz">규격 · 표기치수</span></div>';

  function norm(s) {
    return String(s).toUpperCase().replace(/[\s·\-]/g, '');
  }

  function codeList(x, key, allKey) {
    if (x[allKey] && x[allKey].length) return x[allKey];
    return x[key] ? [x[key]] : [];
  }

  function match(x, q) {
    if (!q) return true;
    var n = norm(q);
    var fields = [x.pn, x.nm, x.sub, x.dt, x.st, x.col, x.grp]
      .concat(codeList(x, 'f', 'f_all'))
      .concat(codeList(x, 'a', 'a_all'))
      .concat(codeList(x, 'i', 'i_all'));
    for (var i = 0; i < fields.length; i++) {
      if (fields[i] && norm(fields[i]).indexOf(n) >= 0) return true;
    }
    return false;
  }

  function findExact(q) {
    var n = norm(q);
    if (!n) return null;
    var keys = [
      ['f', 'f_all', '폼텍'],
      ['a', 'a_all', '애니라벨'],
      ['i', 'i_all', '아이라벨']
    ];
    for (var k = 0; k < keys.length; k++) {
      var m = [];
      var matchedCode = '';
      for (var i = 0; i < DATA.length; i++) {
        var codes = codeList(DATA[i], keys[k][0], keys[k][1]);
        for (var ci = 0; ci < codes.length; ci++) {
          var v = codes[ci];
          var nv = norm(v);
          if (nv === n || nv === 'V' + n || ('V' + nv) === n) {
            m.push(DATA[i]);
            if (!matchedCode) matchedCode = v;
            break;
          }
        }
      }
      if (m.length) return { label: keys[k][2], code: matchedCode, rows: m };
    }
    return null;
  }

  function render() {
    var list = document.getElementById('list');
    var q = query.replace(/^\s+|\s+$/g, '');
    var out = '';
    var total = 0;
    for (var gi = 0; gi < GROUPS.length; gi++) {
      var g = GROUPS[gi];
      if (activeGid !== 'all' && activeGid !== g.id) continue;
      var rs = [];
      for (var i = 0; i < DATA.length; i++) {
        if ((DATA[i].gid === g.id || DATA[i].grp === g.name) && match(DATA[i], q)) rs.push(DATA[i]);
      }
      if (!rs.length) continue;
      total += rs.length;
      out += '<section class="group" data-g="' + esc(g.id) + '" style="--gc:' + g.color + '">'
        + '<div class="ghead"><span class="gdot"></span><h2>' + esc(g.name) + '</h2>'
        + '<span class="gsub">' + esc(g.desc || '') + '</span><span class="gn">' + rs.length + '</span></div>'
        + '<div class="tbl">' + COLHEAD;
      for (i = 0; i < rs.length; i++) out += renderRow(rs[i], g.color);
      out += '</div></section>';
    }
    list.innerHTML = out || '<div class="empty"><strong>일치하는 규격이 없습니다</strong>'
      + '품번 전체 대신 숫자만 넣어보세요. 예: '
      + '<span class="num">3114</span> · <span class="num">V3220</span> · <span class="num">224</span></div>';

    document.getElementById('count').innerHTML = q
      ? '<b>' + total + '</b>개 품목 표시 중 · 전체 <b>' + DATA.length + '</b>개'
      : '전체 <b>' + DATA.length + '</b>개 품목 · 3사 규격 대조';

    var ans = document.getElementById('answer');
    var ex = findExact(q);
    if (ex) {
      var pns = [], seen = {}, r0 = ex.rows[0];
      for (var pi = 0; pi < ex.rows.length; pi++) {
        var pv = ex.rows[pi].pn;
        if (!seen[pv]) { seen[pv] = 1; pns.push(pv); }
      }
      document.getElementById('ans-from').textContent = ex.label + ' ' + ex.code + ' 를 쓰셨다면';
      var htm = '';
      for (var pj = 0; pj < pns.length; pj++) htm += '<code>' + esc(pns[pj]) + '</code>';
      document.getElementById('ans-to').innerHTML = htm + ' 를 그대로 쓰실 수 있습니다.';
      document.getElementById('ans-meta').textContent = '같은 규격 — '
        + (/set$/i.test(String(r0.cn)) ? String(r0.cn).replace(/set/i, '세트') : r0.cn + '칸')
        + (r0.dt ? ' · ' + String(r0.dt).replace('x', ' × ') + ' mm' : '')
        + ' · ' + pns.length + '개 품목';
      ans.hidden = false;
    } else {
      ans.hidden = true;
    }

    var rows = list.getElementsByClassName('row');
    for (var ri = 0; ri < rows.length; ri++) {
      if (hits[rows[ri].getAttribute('data-pn')]) rows[ri].className += ' hit';
    }
  }

  var qEl = document.getElementById('q');
  var clearBtn = document.getElementById('clear');
  qEl.addEventListener('input', function () {
    query = qEl.value;
    clearBtn.hidden = !query;
    hits = {};
    render();
  });
  clearBtn.addEventListener('click', function () {
    qEl.value = '';
    query = '';
    clearBtn.hidden = true;
    hits = {};
    render();
    qEl.focus();
  });

  document.getElementById('chips').addEventListener('click', function (e) {
    var t = e.target;
    while (t && t !== this && !(t.classList && t.classList.contains('chip'))) t = t.parentNode;
    if (!t || t === this) return;
    activeGid = t.getAttribute('data-gid') || 'all';
    var chips = this.querySelectorAll('.chip');
    for (var i = 0; i < chips.length; i++) {
      chips[i].classList.toggle('is-on', chips[i] === t);
    }
    render();
  });

  document.getElementById('list').addEventListener('click', function (e) {
    var t = e.target;
    while (t && t !== this) {
      if (t.classList && t.classList.contains('pn')) break;
      t = t.parentNode;
    }
    if (!t || t === this || !t.classList.contains('pn')) return;
    var txt = t.textContent;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(txt).then(function () {
        t.textContent = '복사됨';
        setTimeout(function () { t.textContent = txt; }, 900);
      }).catch(function () {});
    }
  });

  (function deepLink() {
    var p = '';
    var m = location.search.match(/[?&](?:p|q)=([^&]+)/);
    if (m) p = decodeURIComponent(m[1].replace(/\+/g, ' '));
    else if (location.hash) p = decodeURIComponent(location.hash.replace(/^#p?-?/, ''));
    if (!p) {
      render();
      return;
    }
    var target = null;
    for (var i = 0; i < DATA.length; i++) {
      if (norm(DATA[i].pn) === norm(p)) { target = DATA[i]; break; }
    }
    if (target) {
      hits[target.pn] = 1;
      qEl.value = target.pn;
      query = target.pn;
      clearBtn.hidden = false;
      render();
      var el = document.getElementById('p-' + target.pn);
      if (el && el.scrollIntoView) setTimeout(function () { el.scrollIntoView({ block: 'center' }); }, 80);
    } else {
      qEl.value = p;
      query = p;
      clearBtn.hidden = false;
      render();
    }
  })();
})();
