(function () {
  var cfg = window.__LAUNCH_CHECKLIST__ || {};
  var items = Array.isArray(cfg.items) ? cfg.items.slice() : [];
  var categories = Array.isArray(cfg.categories) ? cfg.categories.slice() : [];
  var apiUrl = cfg.apiUrl || '/api/launch-checklist';
  var resetUrl = cfg.resetUrl || '/api/launch-checklist/reset';
  var BY_KEY = 'labelup.launchChecklist.by';

  var elList = document.getElementById('lc-list');
  var elBy = document.getElementById('lc-by');
  var elQ = document.getElementById('lc-q');
  var elCat = document.getElementById('lc-cat');
  var elPri = document.getElementById('lc-pri');
  var elOpenOnly = document.getElementById('lc-open-only');
  var elReset = document.getElementById('lc-reset');
  var toastTimer = null;

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function toast(msg) {
    var t = document.querySelector('.lc-toast');
    if (!t) {
      t = document.createElement('div');
      t.className = 'lc-toast';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.classList.add('is-on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.classList.remove('is-on'); }, 1800);
  }

  function updateProgress() {
    var total = items.length;
    var done = 0;
    var p0Total = 0;
    var p0Done = 0;
    items.forEach(function (it) {
      if (it.done) done++;
      if (it.priority === 'P0') {
        p0Total++;
        if (it.done) p0Done++;
      }
    });
    var pct = total ? Math.round(100 * done / total) : 0;
    var p0pct = p0Total ? Math.round(100 * p0Done / p0Total) : 0;
    var set = function (id, text) {
      var n = document.getElementById(id);
      if (n) n.textContent = text;
    };
    set('lc-done-label', done + ' / ' + total);
    set('lc-p0-label', p0Done + ' / ' + p0Total);
    set('lc-pct-all', pct + '%');
    set('lc-pct-p0', p0pct + '%');
    var b1 = document.getElementById('lc-bar-all');
    var b2 = document.getElementById('lc-bar-p0');
    if (b1) b1.style.width = pct + '%';
    if (b2) b2.style.width = p0pct + '%';
  }

  function filtered() {
    var q = ((elQ && elQ.value) || '').trim().toLowerCase();
    var cat = (elCat && elCat.value) || '';
    var pri = (elPri && elPri.value) || '';
    var openOnly = !!(elOpenOnly && elOpenOnly.checked);
    return items.filter(function (it) {
      if (cat && it.category !== cat) return false;
      if (pri && it.priority !== pri) return false;
      if (openOnly && it.done) return false;
      if (!q) return true;
      var hay = [it.category, it.title, it.detail, it.owner, it.key].join(' ').toLowerCase();
      return hay.indexOf(q) !== -1;
    });
  }

  function render() {
    if (!elList) return;
    var rows = filtered();
    if (!rows.length) {
      elList.innerHTML = '<div class="lc-empty">조건에 맞는 항목이 없습니다.</div>';
      return;
    }
    var byCat = {};
    rows.forEach(function (it) {
      if (!byCat[it.category]) byCat[it.category] = [];
      byCat[it.category].push(it);
    });
    var html = '';
    Object.keys(byCat).forEach(function (cat) {
      var list = byCat[cat];
      var done = list.filter(function (x) { return x.done; }).length;
      html += '<section class="lc-cat">'
        + '<header class="lc-cat__head"><h2>' + esc(cat) + '</h2><span>' + done + ' / ' + list.length + '</span></header>';
      list.forEach(function (it) {
        html += '<article class="lc-item' + (it.done ? ' is-done' : '') + '" data-key="' + esc(it.key) + '">'
          + '<div class="lc-item__check"><input type="checkbox" ' + (it.done ? 'checked' : '') + ' aria-label="완료"></div>'
          + '<div class="lc-item__body">'
          + '<div class="lc-item__title"><span class="lc-pri lc-pri--' + esc(it.priority) + '">' + esc(it.priority) + '</span>'
          + '<strong>' + esc(it.title) + '</strong></div>'
          + '<p class="lc-item__detail">' + esc(it.detail) + '</p>'
          + '<div class="lc-item__meta">'
          + (it.owner ? '<span>담당 힌트: ' + esc(it.owner) + '</span>' : '')
          + (it.link ? '<a href="' + esc(it.link) + '" target="_blank" rel="noopener">관련 화면</a>' : '')
          + (it.by ? '<span>체크: ' + esc(it.by) + (it.at ? ' · ' + esc(it.at) : '') + '</span>' : '')
          + '</div>'
          + '<textarea class="lc-note" placeholder="메모 (선택)">' + esc(it.note || '') + '</textarea>'
          + '</div></article>';
      });
      html += '</section>';
    });
    elList.innerHTML = html;
  }

  function findItem(key) {
    for (var i = 0; i < items.length; i++) {
      if (items[i].key === key) return items[i];
    }
    return null;
  }

  function save(key, patch) {
    var item = findItem(key);
    if (!item) return Promise.resolve();
    var body = Object.assign({
      key: key,
      done: item.done,
      note: item.note || '',
      by: (elBy && elBy.value.trim()) || item.by || ''
    }, patch || {});
    var card = elList && elList.querySelector('[data-key="' + key + '"]');
    if (card) card.classList.add('is-saving');
    return fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(body)
    }).then(function (r) { return r.json(); }).then(function (res) {
      if (!res || !res.success) throw new Error((res && res.message) || '저장 실패');
      var saved = res.data && res.data.item ? res.data.item : body;
      item.done = !!saved.done;
      item.note = saved.note || '';
      item.by = saved.by || '';
      item.at = saved.at || '';
      if (res.data && res.data.updated_at) {
        var u = document.getElementById('lc-updated-at');
        if (u) u.textContent = res.data.updated_at;
      }
      updateProgress();
      render();
      toast('저장했습니다');
    }).catch(function (err) {
      toast(err.message || '저장 실패');
      render();
    });
  }

  function bindList() {
    if (!elList) return;
    elList.addEventListener('change', function (e) {
      var t = e.target;
      if (!t || t.type !== 'checkbox') return;
      var card = t.closest('[data-key]');
      if (!card) return;
      save(card.getAttribute('data-key'), { done: !!t.checked });
    });
    var noteTimer = null;
    elList.addEventListener('input', function (e) {
      var t = e.target;
      if (!t || !t.classList.contains('lc-note')) return;
      var card = t.closest('[data-key]');
      if (!card) return;
      var key = card.getAttribute('data-key');
      var item = findItem(key);
      if (item) item.note = t.value;
      clearTimeout(noteTimer);
      noteTimer = setTimeout(function () {
        save(key, { note: t.value });
      }, 600);
    });
  }

  function fillCats() {
    if (!elCat) return;
    categories.forEach(function (c) {
      var opt = document.createElement('option');
      opt.value = c;
      opt.textContent = c;
      elCat.appendChild(opt);
    });
  }

  if (elBy) {
    try { elBy.value = localStorage.getItem(BY_KEY) || ''; } catch (e) {}
    elBy.addEventListener('change', function () {
      try { localStorage.setItem(BY_KEY, elBy.value.trim()); } catch (e) {}
    });
  }
  [elQ, elCat, elPri, elOpenOnly].forEach(function (el) {
    if (!el) return;
    el.addEventListener('input', render);
    el.addEventListener('change', render);
  });
  if (elReset) {
    elReset.addEventListener('click', function () {
      if (!confirm('모든 체크·메모를 초기화할까요?')) return;
      var word = prompt('확인을 위해 RESET 을 입력하세요');
      if (word !== 'RESET') {
        toast('취소되었습니다');
        return;
      }
      fetch(resetUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ confirm: 'RESET' })
      }).then(function (r) { return r.json(); }).then(function (res) {
        if (!res || !res.success) throw new Error((res && res.message) || '초기화 실패');
        var next = (res.data && res.data.items) || [];
        items = next.slice();
        updateProgress();
        render();
        toast('초기화했습니다');
      }).catch(function (err) {
        toast(err.message || '초기화 실패');
      });
    });
  }

  fillCats();
  bindList();
  updateProgress();
  render();
})();
