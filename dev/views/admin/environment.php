<?php
/** @var array<string, mixed> $siteMode */
/** @var array<string, mixed> $aiSettings */
$siteMode = $siteMode ?? [];
$aiSettings = $aiSettings ?? [];
$mode = (string) ($siteMode['mode'] ?? 'production');
$title = (string) ($siteMode['maintenance_title'] ?? '잠시 점검 중입니다');
$message = (string) ($siteMode['maintenance_message'] ?? '');
$eta = (string) ($siteMode['maintenance_eta'] ?? '');
$modes = $siteMode['modes'] ?? [];
$aiProvider = (string) ($aiSettings['provider'] ?? 'openai');
$aiProviders = $aiSettings['providers'] ?? [];
$saveUrl = url('api/admin/site-mode/save');
$aiSaveUrl = url('api/admin/ai-settings/save');
$modeLabels = [
    'development' => '개발 모드',
    'temp_open' => '임시 오픈',
    'production' => '운영 모드',
    'maintenance' => '유지보수 모드',
];
$aiLabels = [
    'openai' => 'Chat GPT',
    'claude' => 'Claude',
];
$naverSms = $naverSms ?? [];
$smsServiceId = (string) ($naverSms['service_id'] ?? '');
$smsAccessKey = (string) ($naverSms['access_key'] ?? '');
$smsSender = (string) ($naverSms['sender'] ?? '');
$smsSecretReady = !empty($naverSms['secret_configured']);
$smsReady = !empty($naverSms['ready']);
$smsSignupEnabled = !empty($naverSms['signup_enabled']);
$smsSaveUrl = url('api/admin/naver-sms/save');
?>
<div class="admin-head">
  <div>
    <h1>환경설정</h1>
    <p id="envHeadDesc">사이트 운영 모드를 전환합니다. 개발 모드에서만 AI 토큰·비용·디버그 정보가 사용자에게 보입니다.</p>
    <p class="admin-muted" id="envCurrent">현재: <strong><?= e($modeLabels[$mode] ?? $mode) ?></strong></p>
  </div>
  <div class="admin-head-actions">
    <a class="admin-btn" href="<?= e(url('')) ?>" target="_blank" rel="noopener">공개 사이트 확인</a>
    <button type="button" class="admin-btn admin-btn--primary" id="envSaveBtn">저장</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<div class="admin-env-tabs" role="tablist" aria-label="환경설정 구분">
  <button type="button" class="admin-env-tab is-active" role="tab" aria-selected="true" data-tab="site" id="envTabSite">사이트모드</button>
  <button type="button" class="admin-env-tab" role="tab" aria-selected="false" data-tab="ai" id="envTabAi">AI 설정</button>
  <button type="button" class="admin-env-tab" role="tab" aria-selected="false" data-tab="sms" id="envTabSms">문자인증</button>
</div>

<section class="admin-env-pane is-active" data-pane="site" id="envPaneSite">
  <form id="siteModeForm" class="admin-card admin-seo-form">
    <div class="admin-field admin-field--full">
      <span>사이트 모드</span>
      <div class="admin-mode-grid" role="radiogroup" aria-label="사이트 모드">
        <?php foreach ($modes as $item): ?>
          <?php
            $value = (string) ($item['value'] ?? '');
            $label = (string) ($item['label'] ?? $value);
            $desc = (string) ($item['desc'] ?? '');
            $checked = $mode === $value;
          ?>
          <label class="admin-mode-card<?= $checked ? ' is-active' : '' ?>" data-mode="<?= e($value) ?>">
            <input type="radio" name="mode" value="<?= e($value) ?>"<?= $checked ? ' checked' : '' ?>>
            <strong><?= e($label) ?></strong>
            <p><?= e($desc) ?></p>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="admin-form-grid" id="maintenanceFields">
      <label class="admin-field admin-field--full">
        <span>유지보수 페이지 제목</span>
        <input class="admin-input" name="maintenance_title" value="<?= e($title) ?>" maxlength="120">
      </label>
      <label class="admin-field admin-field--full">
        <span>유지보수 안내 문구</span>
        <textarea class="admin-input" name="maintenance_message" rows="4" maxlength="1000"><?= e($message) ?></textarea>
      </label>
      <label class="admin-field admin-field--full">
        <span>재오픈 예정 (선택)</span>
        <input class="admin-input" name="maintenance_eta" value="<?= e($eta) ?>" maxlength="120" placeholder="예: 오늘 오후 6시 이후 재오픈 예정">
      </label>
    </div>
  </form>
</section>

<section class="admin-env-pane" data-pane="ai" id="envPaneAi" hidden>
  <form id="aiSettingsForm" class="admin-card admin-seo-form">
    <div class="admin-field admin-field--full">
      <span>AI 제공자</span>
      <p class="admin-muted" style="margin:6px 0 0">라비(사용자 페이지)가 대화·라벨 템플릿·클립아트를 만들 때 사용할 모델을 고릅니다. API 키는 서버 .env에 두고, 여기서는 제공자만 바꿉니다.</p>
      <div class="admin-mode-grid" role="radiogroup" aria-label="AI 제공자">
        <?php foreach ($aiProviders as $item): ?>
          <?php
            $value = (string) ($item['value'] ?? '');
            $label = (string) ($item['label'] ?? $value);
            $desc = (string) ($item['desc'] ?? '');
            $checked = $aiProvider === $value;
          ?>
          <label class="admin-mode-card<?= $checked ? ' is-active' : '' ?>" data-provider="<?= e($value) ?>">
            <input type="radio" name="provider" value="<?= e($value) ?>"<?= $checked ? ' checked' : '' ?>>
            <strong><?= e($label) ?></strong>
            <p><?= e($desc) ?></p>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="admin-ai-status">
      <div class="admin-ai-status-item<?= !empty($aiSettings['openai_key_ready']) ? ' is-ready' : ' is-missing' ?>">
        <strong>Chat GPT 키</strong>
        <span><?= !empty($aiSettings['openai_key_ready']) ? 'OPENAI_API_KEY 설정됨' : 'OPENAI_API_KEY 없음' ?></span>
        <small>모델: <?= e((string) ($aiSettings['openai_model'] ?? 'gpt-4o-mini')) ?></small>
      </div>
      <div class="admin-ai-status-item<?= !empty($aiSettings['claude_key_ready']) ? ' is-ready' : ' is-missing' ?>">
        <strong>Claude 키</strong>
        <span><?= !empty($aiSettings['claude_key_ready']) ? 'ANTHROPIC_API_KEY 설정됨' : 'ANTHROPIC_API_KEY / CLAUDE_API_KEY 없음' ?></span>
        <small>모델: <?= e((string) ($aiSettings['claude_model'] ?? 'claude-sonnet-4-5')) ?></small>
      </div>
    </div>
  </form>
</section>

<section class="admin-env-pane" data-pane="sms" id="envPaneSms" hidden>
  <form id="naverSmsForm" class="admin-card admin-seo-form">
    <div class="admin-field admin-field--full">
      <span>문자인증 사용 여부</span>
      <p class="admin-muted" style="margin:6px 0 0">일반 가입과 네이버·카카오·구글 가입에 공통으로 적용됩니다.</p>
      <div class="admin-mode-grid" role="radiogroup" aria-label="문자인증 사용 여부">
        <label class="admin-mode-card<?= $smsSignupEnabled ? ' is-active' : '' ?>">
          <input type="radio" name="signup_enabled" value="1"<?= $smsSignupEnabled ? ' checked' : '' ?>>
          <strong>사용함</strong>
          <p>일반·SNS 가입 시 휴대폰 문자인증을 완료해야 회원가입됩니다.</p>
        </label>
        <label class="admin-mode-card<?= $smsSignupEnabled ? '' : ' is-active' ?>">
          <input type="radio" name="signup_enabled" value="0"<?= $smsSignupEnabled ? '' : ' checked' ?>>
          <strong>사용하지 않음</strong>
          <p>문자인증 없이 이름·이메일·약관만으로 회원가입할 수 있습니다.</p>
        </label>
      </div>
    </div>
    <p class="admin-muted" style="margin:18px 0 14px">문자인증을 사용할 때는 네이버 클라우드 SENS의 서비스 ID, Access Key, Secret Key, 등록된 발신번호가 필요합니다.</p>
    <div class="admin-form-grid">
      <label class="admin-field admin-field--full">
        <span>서비스 ID</span>
        <input class="admin-input" name="service_id" value="<?= e($smsServiceId) ?>" maxlength="100" placeholder="ncp:sms:kr:123456789012:labelup" autocomplete="off">
      </label>
      <label class="admin-field">
        <span>Access Key</span>
        <input class="admin-input" name="access_key" value="<?= e($smsAccessKey) ?>" maxlength="120" autocomplete="off">
      </label>
      <label class="admin-field">
        <span>Secret Key</span>
        <input class="admin-input" type="password" name="secret_key" value="" maxlength="200" autocomplete="new-password" placeholder="<?= $smsSecretReady ? '저장됨 · 바꿀 때만 입력' : 'Secret Key' ?>">
      </label>
      <label class="admin-field">
        <span>발신번호</span>
        <input class="admin-input" name="sender" value="<?= e($smsSender) ?>" maxlength="12" inputmode="numeric" placeholder="01012345678">
      </label>
    </div>
    <div class="admin-ai-status" style="margin-top:16px">
      <div class="admin-ai-status-item<?= $smsReady ? ' is-ready' : ' is-missing' ?>" id="smsReadyBox">
        <strong>발송 준비</strong>
        <span id="smsReadyText"><?= $smsReady ? '서비스 ID, 키, 발신번호가 저장되어 있습니다.' : '필수 항목이 비어 있어 인증번호를 보낼 수 없습니다.' ?></span>
        <small>Secret Key는 저장 후 화면에 다시 보이지 않습니다.</small>
      </div>
    </div>
  </form>
</section>

<style>
.admin-env-tabs{display:flex;gap:8px;margin:0 0 16px}
.admin-env-tab{appearance:none;border:1px solid #e5e7eb;background:#fff;color:#374151;border-radius:999px;padding:8px 16px;font-size:14px;font-weight:700;cursor:pointer}
.admin-env-tab.is-active{border-color:var(--accent);background:var(--accent);color:#fff}
.admin-env-pane[hidden]{display:none}
.admin-mode-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:8px}
.admin-mode-card{position:relative;display:flex;flex-direction:column;gap:8px;padding:16px 14px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;cursor:pointer;transition:border-color .15s,box-shadow .15s,background .15s;user-select:none}
.admin-mode-card input{position:absolute;opacity:0;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
.admin-mode-card strong{font-size:15px;color:#111827}
.admin-mode-card p{margin:0;font-size:12px;line-height:1.5;color:#6b7280}
.admin-mode-card.is-active{border-color:var(--accent);background:var(--accent-soft);box-shadow:0 0 0 1px var(--accent-soft-2)}
.admin-ai-status{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:16px}
.admin-ai-status-item{padding:14px;border-radius:12px;border:1px solid #e5e7eb;background:#fff;display:flex;flex-direction:column;gap:4px}
.admin-ai-status-item strong{font-size:13px}
.admin-ai-status-item span{font-size:13px;color:#4b5563}
.admin-ai-status-item small{font-size:12px;color:#6b7280}
.admin-ai-status-item.is-ready{border-color:#86efac;background:#f0fdf4}
.admin-ai-status-item.is-missing{border-color:#fde68a;background:#fffbeb}
@media (max-width:1100px){.admin-mode-grid,.admin-ai-status{grid-template-columns:1fr}}
</style>
<script>
(function () {
  var siteForm = document.getElementById('siteModeForm');
  var aiForm = document.getElementById('aiSettingsForm');
  var smsForm = document.getElementById('naverSmsForm');
  var alertEl = document.getElementById('adminAlert');
  var saveBtn = document.getElementById('envSaveBtn');
  var currentEl = document.getElementById('envCurrent');
  var headDesc = document.getElementById('envHeadDesc');
  var saveUrl = <?= json_encode($saveUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var aiSaveUrl = <?= json_encode($aiSaveUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var smsSaveUrl = <?= json_encode($smsSaveUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var modeLabels = <?= json_encode($modeLabels, JSON_UNESCAPED_UNICODE) ?>;
  var aiLabels = <?= json_encode($aiLabels, JSON_UNESCAPED_UNICODE) ?>;
  var currentMode = <?= json_encode($mode, JSON_UNESCAPED_UNICODE) ?>;
  var currentProvider = <?= json_encode($aiProvider, JSON_UNESCAPED_UNICODE) ?>;
  var smsReady = <?= $smsReady ? 'true' : 'false' ?>;
  var smsSignupEnabled = <?= $smsSignupEnabled ? 'true' : 'false' ?>;
  var activeTab = 'site';

  function showAlert(msg, ok) {
    if (!alertEl) return;
    alertEl.textContent = msg;
    alertEl.className = 'admin-alert ' + (ok ? 'is-ok' : 'is-err');
    alertEl.style.display = 'block';
  }

  function selectedValue(form, name) {
    var checked = form.querySelector('input[name="' + name + '"]:checked');
    return checked ? String(checked.value || '') : '';
  }

  function syncCards(form, name) {
    form.querySelectorAll('.admin-mode-card').forEach(function (card) {
      var input = card.querySelector('input[name="' + name + '"]');
      card.classList.toggle('is-active', !!(input && input.checked));
    });
  }

  function bindCards(form, name) {
    form.querySelectorAll('.admin-mode-card').forEach(function (card) {
      card.addEventListener('click', function (ev) {
        ev.preventDefault();
        var input = card.querySelector('input[name="' + name + '"]');
        if (!input) return;
        input.checked = true;
        syncCards(form, name);
      });
    });
    form.addEventListener('change', function () { syncCards(form, name); });
    syncCards(form, name);
  }

  function setTab(tab) {
    activeTab = (tab === 'ai' || tab === 'sms') ? tab : 'site';
    document.querySelectorAll('.admin-env-tab').forEach(function (btn) {
      var on = btn.getAttribute('data-tab') === activeTab;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    document.querySelectorAll('.admin-env-pane').forEach(function (pane) {
      var on = pane.getAttribute('data-pane') === activeTab;
      pane.classList.toggle('is-active', on);
      pane.hidden = !on;
    });
    if (headDesc) {
      headDesc.textContent = activeTab === 'ai'
        ? '사용자 페이지 라비가 사용할 AI를 고릅니다. Chat GPT 또는 Claude를 선택하면 같은 생성 흐름에 해당 API가 쓰입니다.'
        : (activeTab === 'sms'
          ? '회원가입 문자인증 사용 여부와 네이버 클라우드 SENS API를 저장합니다.'
          : '사이트 운영 모드를 전환합니다. 개발 모드에서만 AI 토큰·비용·디버그 정보가 사용자에게 보입니다.');
    }
    updateCurrent();
    if (history.replaceState) {
      history.replaceState(null, '', '#' + activeTab);
    }
  }

  function updateCurrent() {
    if (!currentEl) return;
    if (activeTab === 'ai') {
      currentEl.innerHTML = '현재: <strong>' + (aiLabels[currentProvider] || currentProvider) + '</strong>';
      return;
    }
    if (activeTab === 'sms') {
      currentEl.innerHTML = '현재: <strong>' + (smsSignupEnabled ? '문자인증 사용함' : '문자인증 사용하지 않음') + '</strong>'
        + (smsSignupEnabled ? ' · ' + (smsReady ? '발송 가능' : 'API 입력 필요') : '');
      return;
    }
    currentEl.innerHTML = '현재: <strong>' + (modeLabels[currentMode] || currentMode) + '</strong>';
  }

  bindCards(siteForm, 'mode');
  bindCards(aiForm, 'provider');
  bindCards(smsForm, 'signup_enabled');

  document.querySelectorAll('.admin-env-tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
      setTab(btn.getAttribute('data-tab') || 'site');
    });
  });

  async function saveSite() {
    var mode = selectedValue(siteForm, 'mode');
    if (!mode) {
      showAlert('사이트 모드를 선택해 주세요.', false);
      return;
    }
    var fd = new FormData(siteForm);
    var payload = {
      mode: mode,
      maintenance_title: String(fd.get('maintenance_title') || ''),
      maintenance_message: String(fd.get('maintenance_message') || ''),
      maintenance_eta: String(fd.get('maintenance_eta') || '')
    };
    var res = await fetch(saveUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload)
    });
    var data = await res.json().catch(function () { return {}; });
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '저장에 실패했습니다.');
    }
    currentMode = (((data.data || {}).siteMode) || {}).mode || mode;
    updateCurrent();
    var extra = currentMode === 'maintenance'
      ? ' 공개 사이트는 유지보수 페이지로 전환됩니다. (관리자 메뉴는 그대로 사용 가능)'
      : (currentMode === 'temp_open'
        ? ' SNS 로그인·회원가입은 준비중 안내만 표시됩니다.'
        : '');
    showAlert((data.message || '저장되었습니다.') + ' → ' + (modeLabels[currentMode] || currentMode) + '.' + extra, true);
  }

  async function saveAi() {
    var provider = selectedValue(aiForm, 'provider');
    if (!provider) {
      showAlert('AI 제공자를 선택해 주세요.', false);
      return;
    }
    var res = await fetch(aiSaveUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ provider: provider })
    });
    var data = await res.json().catch(function () { return {}; });
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '저장에 실패했습니다.');
    }
    currentProvider = (((data.data || {}).aiSettings) || {}).provider || provider;
    updateCurrent();
    showAlert((data.message || '저장되었습니다.') + ' → ' + (aiLabels[currentProvider] || currentProvider) + '. 사용자 페이지 라비에 바로 적용됩니다.', true);
  }

  function paintSmsReady(ready) {
    smsReady = !!ready;
    var box = document.getElementById('smsReadyBox');
    var text = document.getElementById('smsReadyText');
    if (box) {
      box.classList.toggle('is-ready', smsReady);
      box.classList.toggle('is-missing', !smsReady);
    }
    if (text) {
      text.textContent = smsReady
        ? '서비스 ID, 키, 발신번호가 저장되어 있습니다.'
        : '필수 항목이 비어 있어 인증번호를 보낼 수 없습니다.';
    }
    var secret = smsForm ? smsForm.querySelector('input[name="secret_key"]') : null;
    if (secret && smsReady) {
      secret.value = '';
      secret.placeholder = '저장됨 · 바꿀 때만 입력';
    }
  }

  async function saveSms() {
    if (!smsForm) return;
    var fd = new FormData(smsForm);
    var res = await fetch(smsSaveUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({
        signup_enabled: selectedValue(smsForm, 'signup_enabled') || '1',
        service_id: String(fd.get('service_id') || ''),
        access_key: String(fd.get('access_key') || ''),
        secret_key: String(fd.get('secret_key') || ''),
        sender: String(fd.get('sender') || '')
      })
    });
    var data = await res.json().catch(function () { return {}; });
    if (!res.ok || data.success === false) {
      throw new Error(data.message || '저장에 실패했습니다.');
    }
    var saved = ((data.data || {}).naverSms) || {};
    smsSignupEnabled = !!saved.signup_enabled;
    paintSmsReady(saved.ready);
    updateCurrent();
    showAlert(data.message || '문자인증 설정을 저장했습니다.', true);
  }

  saveBtn.addEventListener('click', async function () {
    saveBtn.disabled = true;
    try {
      if (activeTab === 'ai') {
        await saveAi();
      } else if (activeTab === 'sms') {
        await saveSms();
      } else {
        await saveSite();
      }
    } catch (err) {
      showAlert(err.message || '저장에 실패했습니다.', false);
    } finally {
      saveBtn.disabled = false;
    }
  });

  var hash = String(location.hash || '').replace('#', '');
  setTab(hash === 'ai' || hash === 'sms' ? hash : 'site');
})();
</script>
