<?php
/** @var array<string, mixed> $notify */
$notify = $notify ?? [];
$catalog = $notify['catalog'] ?? [];
$monitor = is_array($notify['monitor'] ?? null) ? $notify['monitor'] : [];
$lastRun = (string) ($monitor['last_run'] ?? '');
$cronAlive = $lastRun !== '' && time() - (int) strtotime($lastRun) < 900;
$backup = is_array($notify['backup'] ?? null) ? $notify['backup'] : [];
$backupInstalled = !empty($backup['installed']);
$backupOk = $backupInstalled && !empty($backup['ok']);
$backupAt = (string) ($backup['finished_at'] ?? '');
$backupFile = (string) ($backup['file'] ?? '');
$backupKeep = (int) ($backup['keep_days'] ?? 14);
$clientState = [
    'events' => $notify['events'] ?? [],
    'recipients' => $notify['recipients'] ?? [],
    'quiet' => $notify['quiet'] ?? ['enabled' => false, 'start' => 22, 'end' => 8],
    'catalog' => $catalog,
    'logs' => $notify['logs'] ?? [],
];
?>
<div class="admin-head">
  <div>
    <h1>문자알림 설정</h1>
    <p>사이트 장애, AI API 잔액·오류, 주문·문의 같은 운영 상황을 등록한 휴대폰으로 문자 알림합니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary" id="nsSave">저장</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<div class="ns-status">
  <div class="admin-ai-status-item<?= !empty($notify['sms_ready']) ? ' is-ready' : ' is-missing' ?>">
    <strong>문자 발송 API</strong>
    <span><?= !empty($notify['sms_ready']) ? '네이버 문자 API 사용 가능' : '네이버 문자 API 미설정 — 알림을 보낼 수 없습니다.' ?></span>
    <small><a href="<?= url('admin/settings/environment') ?>#sms">환경설정 › 문자인증</a>의 키·발신번호를 함께 사용합니다.</small>
  </div>
  <div class="admin-ai-status-item<?= $cronAlive ? ' is-ready' : ' is-missing' ?>">
    <strong>자동 점검 (5분 간격)</strong>
    <span><?= $cronAlive ? '정상 동작 중 · 최근 점검 ' . e($lastRun) : ($lastRun !== '' ? '점검이 멈췄습니다 · 최근 ' . e($lastRun) : '아직 점검 기록이 없습니다.') ?></span>
    <small><?php if (!empty($monitor['last_problem'])): ?>최근 문제: <?= e((string) $monitor['last_problem']) ?><?php elseif (!empty($monitor['down'])): ?>장애 상태<?php else: ?>사이트 장애·AI 잔액은 이 점검으로 감지합니다.<?php endif; ?></small>
  </div>
  <div class="admin-ai-status-item<?= $backupOk ? ' is-ready' : ' is-missing' ?>">
    <strong>DB 일일 백업</strong>
    <span><?php if (!$backupInstalled): ?>아직 백업 기록이 없습니다.<?php elseif ($backupOk): ?>최근 성공 <?= e($backupAt) ?><?php else: ?>실패<?= $backupAt !== '' ? ' · ' . e($backupAt) : '' ?><?php endif; ?></span>
    <small><?php if ($backupOk && $backupFile !== ''): ?><?= e($backupFile) ?> · <?= (int) $backupKeep ?>일 보관<?php elseif (!$backupOk && !empty($backup['error'])): ?><?= e((string) $backup['error']) ?><?php else: ?>매일 03:20에 서비스 DB를 압축 저장합니다. 실패 알림을 받으려면 아래 「DB 백업 실패」를 수신 항목에 넣어 주세요.<?php endif; ?></small>
  </div>
</div>

<section class="admin-card ns-card">
  <div class="ns-card__head">
    <h3>알림 항목</h3>
    <p class="admin-muted">켜 둔 항목만 발송합니다. 같은 알림은 재발송 간격 동안 한 번만 보냅니다 (0 = 매번).</p>
  </div>
  <table class="admin-table ns-events">
    <thead><tr><th style="width:70px">사용</th><th>항목</th><th style="width:150px">재발송 간격</th></tr></thead>
    <tbody>
    <?php foreach ($catalog as $key => $meta): ?>
      <tr data-event="<?= e($key) ?>">
        <td><label class="ns-switch"><input type="checkbox" class="js-ev-on"><span></span></label></td>
        <td>
          <strong><?= e($meta['label']) ?></strong>
          <?php if (!empty($meta['critical'])): ?><em class="ns-tag">긴급</em><?php endif; ?>
          <p><?= e($meta['desc']) ?></p>
        </td>
        <td><span class="ns-cooldown"><input class="admin-input js-ev-cooldown" type="number" min="0" max="1440" step="5" aria-label="재발송 간격(분)"> 분</span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="ns-quiet">
    <label class="ns-quiet__on"><input type="checkbox" id="nsQuietOn"> 야간 방해금지</label>
    <select class="admin-input" id="nsQuietStart" aria-label="시작 시각"></select> 시부터
    <select class="admin-input" id="nsQuietEnd" aria-label="종료 시각"></select> 시까지는 긴급이 아닌 알림(주문·문의·오류&제안)을 보내지 않습니다.
  </div>
</section>

<section class="admin-card ns-card">
  <div class="ns-card__head">
    <h3>알림 받을 휴대폰</h3>
    <p class="admin-muted">최대 10명. 사람마다 받을 항목을 고를 수 있습니다. 저장 후 「테스트」로 수신을 확인해 주세요.</p>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table ns-recipients">
      <thead><tr><th style="width:60px">수신</th><th style="width:130px">이름</th><th style="width:150px">휴대폰</th><th>받을 알림</th><th style="width:130px"></th></tr></thead>
      <tbody id="nsRecipients"></tbody>
    </table>
  </div>
  <button type="button" class="admin-btn" id="nsAdd">+ 휴대폰 추가</button>
</section>

<section class="admin-card ns-card">
  <div class="ns-card__head"><h3>최근 발송 기록</h3></div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th style="width:150px">시간</th><th style="width:130px">항목</th><th>내용</th><th style="width:150px">수신</th><th style="width:70px">결과</th></tr></thead>
      <tbody id="nsLogs"></tbody>
    </table>
  </div>
</section>

<style>
.ns-status{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:16px}
.ns-status .admin-ai-status-item{padding:14px;border-radius:12px;border:1px solid #e5e7eb;background:#fff;display:flex;flex-direction:column;gap:4px}
.ns-status strong{font-size:13px}
.ns-status span{font-size:13px;color:#4b5563}
.ns-status small{font-size:12px;color:#6b7280}
.ns-status small a{color:var(--accent);font-weight:700}
.ns-status .is-ready{border-color:#86efac;background:#f0fdf4}
.ns-status .is-missing{border-color:#fde68a;background:#fffbeb}
.ns-card{margin-bottom:16px}
.ns-card__head{margin-bottom:12px}
.ns-card__head h3{margin:0 0 4px;font-size:16px}
.ns-card__head p{margin:0}
.ns-events td{vertical-align:middle}
.ns-events td p{margin:4px 0 0;font-size:12px;color:#6b7280;line-height:1.5}
.ns-card .admin-input{min-width:0}
.ns-cooldown{display:inline-flex;align-items:center;gap:6px;white-space:nowrap;font-size:13px;color:#4b5563}
.ns-events .js-ev-cooldown{width:84px;height:34px}
.ns-tag{display:inline-block;margin-left:6px;padding:1px 7px;border-radius:999px;background:#fff1f2;color:#be123c;font-size:11px;font-style:normal;font-weight:800}
.ns-switch{position:relative;display:inline-block;width:40px;height:22px;cursor:pointer}
.ns-switch input{position:absolute;opacity:0;width:0;height:0}
.ns-switch span{position:absolute;inset:0;border-radius:999px;background:#d1d5db;transition:background .15s}
.ns-switch span::after{content:"";position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform .15s;box-shadow:0 1px 2px rgba(0,0,0,.2)}
.ns-switch input:checked + span{background:var(--accent,#8a1c3c)}
.ns-switch input:checked + span::after{transform:translateX(18px)}
.ns-quiet{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:14px;font-size:13px;color:#4b5563}
.ns-quiet__on{display:inline-flex;align-items:center;gap:6px;font-weight:800;color:#111827;margin-right:6px;cursor:pointer}
.ns-quiet select{width:72px;height:34px;padding:0 8px}
.ns-recipients .js-r-name,.ns-recipients .js-r-phone{width:100%}
.ns-recipients td{vertical-align:middle}
.ns-recipients .admin-input{height:34px}
.ns-evchips{display:flex;flex-wrap:wrap;gap:6px}
.ns-evchips label{display:inline-flex;align-items:center;gap:4px;padding:4px 9px;border:1px solid #e5e7eb;border-radius:999px;font-size:12px;cursor:pointer;user-select:none}
.ns-evchips label:has(input:checked){border-color:var(--accent);background:var(--accent-soft,#fbf3f5);color:var(--accent);font-weight:700}
.ns-evchips input{margin:0}
.ns-row-actions{display:flex;gap:6px;justify-content:flex-end}
.ns-row-actions .admin-btn{height:32px;padding:0 10px;font-size:13px}
#nsAdd{margin-top:10px}
.ns-log-ok{color:#067647;font-weight:700}
.ns-log-fail{color:#b42318;font-weight:700}
.ns-log-text{font-size:12px;color:#374151;white-space:pre-wrap;word-break:break-all}
@media (max-width:900px){.ns-status{grid-template-columns:1fr}}
</style>
<script>
(function () {
  var state = <?= json_encode($clientState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var urls = {
    save: <?= json_encode(url('api/admin/notify-sms/save'), JSON_UNESCAPED_SLASHES) ?>,
    test: <?= json_encode(url('api/admin/notify-sms/test'), JSON_UNESCAPED_SLASHES) ?>
  };
  var alertEl = document.getElementById('adminAlert');
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var eventKeys = Object.keys(state.catalog || {});

  function showAlert(msg, ok) {
    alertEl.textContent = msg;
    alertEl.className = 'admin-alert ' + (ok ? 'is-ok' : 'is-err');
    alertEl.style.display = 'block';
    alertEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }

  function fmtPhone(p) {
    p = String(p || '').replace(/\D+/g, '');
    if (p.length === 11) return p.slice(0, 3) + '-' + p.slice(3, 7) + '-' + p.slice(7);
    if (p.length === 10) return p.slice(0, 3) + '-' + p.slice(3, 6) + '-' + p.slice(6);
    return p;
  }

  function paintEvents() {
    document.querySelectorAll('.ns-events tr[data-event]').forEach(function (tr) {
      var ev = (state.events || {})[tr.getAttribute('data-event')] || {};
      tr.querySelector('.js-ev-on').checked = !!ev.enabled;
      tr.querySelector('.js-ev-cooldown').value = ev.cooldown == null ? 0 : ev.cooldown;
    });
    var q = state.quiet || {};
    var opts = '';
    for (var h = 0; h < 24; h++) opts += '<option value="' + h + '">' + h + '</option>';
    var s = document.getElementById('nsQuietStart');
    var e = document.getElementById('nsQuietEnd');
    s.innerHTML = opts; e.innerHTML = opts;
    s.value = String(q.start == null ? 22 : q.start);
    e.value = String(q.end == null ? 8 : q.end);
    document.getElementById('nsQuietOn').checked = !!q.enabled;
  }

  function recipientRow(r) {
    var chips = eventKeys.map(function (k) {
      var on = (r.events || []).indexOf(k) >= 0;
      return '<label><input type="checkbox" class="js-r-ev" value="' + esc(k) + '"' + (on ? ' checked' : '') + '>' + esc(state.catalog[k].label) + '</label>';
    }).join('');
    return '<tr data-id="' + esc(r.id || '') + '">' +
      '<td><label class="ns-switch"><input type="checkbox" class="js-r-on"' + (r.enabled !== false ? ' checked' : '') + '><span></span></label></td>' +
      '<td><input class="admin-input js-r-name" maxlength="30" placeholder="이름" value="' + esc(r.name || '') + '"></td>' +
      '<td><input class="admin-input js-r-phone" maxlength="13" inputmode="numeric" placeholder="010-0000-0000" value="' + esc(fmtPhone(r.phone)) + '"></td>' +
      '<td><div class="ns-evchips">' + chips + '</div></td>' +
      '<td><div class="ns-row-actions"><button type="button" class="admin-btn js-r-test">테스트</button><button type="button" class="admin-btn admin-btn--danger js-r-del">삭제</button></div></td>' +
    '</tr>';
  }

  function paintRecipients() {
    var body = document.getElementById('nsRecipients');
    body.innerHTML = (state.recipients || []).length
      ? state.recipients.map(recipientRow).join('')
      : '<tr class="js-empty"><td colspan="5" class="empty">등록된 휴대폰이 없습니다. 「+ 휴대폰 추가」를 눌러 주세요.</td></tr>';
  }

  function paintLogs() {
    var body = document.getElementById('nsLogs');
    var labels = {};
    eventKeys.forEach(function (k) { labels[k] = state.catalog[k].label; });
    labels.test = '테스트';
    body.innerHTML = (state.logs || []).length
      ? state.logs.map(function (l) {
          return '<tr><td>' + esc(l.at) + '</td><td>' + esc(labels[l.event] || l.event) + '</td>' +
            '<td class="ns-log-text">' + esc(l.text) + (l.error ? '<br><span class="ns-log-fail">' + esc(l.error) + '</span>' : '') + '</td>' +
            '<td>' + esc((l.to || []).join(', ')) + '</td>' +
            '<td class="' + (l.ok ? 'ns-log-ok">성공' : 'ns-log-fail">실패') + '</td></tr>';
        }).join('')
      : '<tr><td colspan="5" class="empty">아직 발송한 알림이 없습니다.</td></tr>';
  }

  function collect() {
    var events = {};
    document.querySelectorAll('.ns-events tr[data-event]').forEach(function (tr) {
      events[tr.getAttribute('data-event')] = {
        enabled: tr.querySelector('.js-ev-on').checked,
        cooldown: parseInt(tr.querySelector('.js-ev-cooldown').value || '0', 10) || 0
      };
    });
    var recipients = [];
    document.querySelectorAll('#nsRecipients tr[data-id]').forEach(function (tr) {
      recipients.push({
        id: tr.getAttribute('data-id'),
        enabled: tr.querySelector('.js-r-on').checked,
        name: tr.querySelector('.js-r-name').value,
        phone: tr.querySelector('.js-r-phone').value,
        events: Array.prototype.map.call(tr.querySelectorAll('.js-r-ev:checked'), function (i) { return i.value; })
      });
    });
    return {
      events: events,
      recipients: recipients,
      quiet: {
        enabled: document.getElementById('nsQuietOn').checked,
        start: parseInt(document.getElementById('nsQuietStart').value, 10),
        end: parseInt(document.getElementById('nsQuietEnd').value, 10)
      }
    };
  }

  async function post(url, body, btn) {
    if (btn) btn.disabled = true;
    try {
      var res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(body)
      });
      var data = await res.json().catch(function () { return {}; });
      if (!res.ok || data.success === false) throw new Error(data.message || '요청에 실패했습니다.');
      return data;
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  document.getElementById('nsSave').addEventListener('click', async function () {
    try {
      var data = await post(urls.save, collect(), this);
      var n = (data.data || {}).notify || {};
      state.events = n.events || state.events;
      state.recipients = n.recipients || state.recipients;
      state.quiet = n.quiet || state.quiet;
      paintEvents();
      paintRecipients();
      showAlert(data.message || '저장했습니다.', true);
    } catch (err) {
      showAlert(err.message, false);
    }
  });

  document.getElementById('nsAdd').addEventListener('click', function () {
    var body = document.getElementById('nsRecipients');
    if (body.querySelectorAll('tr[data-id]').length >= 10) {
      showAlert('알림 수신자는 최대 10명까지 등록할 수 있습니다.', false);
      return;
    }
    var empty = body.querySelector('.js-empty');
    if (empty) empty.remove();
    var critical = eventKeys.filter(function (k) { return state.catalog[k].critical; });
    body.insertAdjacentHTML('beforeend', recipientRow({ id: '', name: '', phone: '', enabled: true, events: critical }));
    body.querySelector('tr[data-id]:last-child .js-r-name').focus();
  });

  document.getElementById('nsRecipients').addEventListener('click', async function (e) {
    var tr = e.target.closest('tr[data-id]');
    if (!tr) return;
    if (e.target.closest('.js-r-del')) {
      tr.remove();
      if (!document.querySelector('#nsRecipients tr[data-id]')) paintRecipients();
      showAlert('삭제한 번호는 「저장」을 눌러야 반영됩니다.', true);
      return;
    }
    var testBtn = e.target.closest('.js-r-test');
    if (testBtn) {
      var phone = tr.querySelector('.js-r-phone').value;
      if (!confirm(fmtPhone(phone) + ' 번호로 테스트 문자를 보낼까요?')) return;
      try {
        var data = await post(urls.test, { phone: phone }, testBtn);
        state.logs = (((data.data || {}).notify) || {}).logs || state.logs;
        paintLogs();
        showAlert(data.message || '테스트 문자를 보냈습니다.', true);
      } catch (err) {
        showAlert(err.message, false);
      }
    }
  });

  document.getElementById('nsRecipients').addEventListener('input', function (e) {
    if (e.target.classList.contains('js-r-phone')) {
      var pos = e.target.value.length;
      e.target.value = fmtPhone(e.target.value);
      if (pos === e.target.value.length) e.target.setSelectionRange(pos, pos);
    }
  });

  paintEvents();
  paintRecipients();
  paintLogs();
})();
</script>
