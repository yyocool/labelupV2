<?php
/** @var array<string, mixed> $siteMode */
$siteMode = $siteMode ?? [];
$mode = (string) ($siteMode['mode'] ?? 'production');
$title = (string) ($siteMode['maintenance_title'] ?? '잠시 점검 중입니다');
$message = (string) ($siteMode['maintenance_message'] ?? '');
$eta = (string) ($siteMode['maintenance_eta'] ?? '');
$modes = $siteMode['modes'] ?? [];
$saveUrl = url('api/admin/site-mode/save');
$modeLabels = [
    'development' => '개발 모드',
    'production' => '운영 모드',
    'maintenance' => '유지보수 모드',
];
?>
<div class="admin-head">
  <div>
    <h1>환경설정</h1>
    <p>사이트 운영 모드를 전환합니다. 개발 모드에서만 AI 토큰·비용·디버그 정보가 사용자에게 보입니다.</p>
    <p class="admin-muted" id="siteModeCurrent">현재: <strong><?= e($modeLabels[$mode] ?? $mode) ?></strong></p>
  </div>
  <div class="admin-head-actions">
    <a class="admin-btn" href="<?= e(url('')) ?>" target="_blank" rel="noopener">공개 사이트 확인</a>
    <button type="button" class="admin-btn admin-btn--primary" id="siteModeSaveBtn">저장</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

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

<style>
.admin-mode-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:8px}
.admin-mode-card{position:relative;display:flex;flex-direction:column;gap:8px;padding:16px 14px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;cursor:pointer;transition:border-color .15s,box-shadow .15s,background .15s;user-select:none}
.admin-mode-card input{position:absolute;opacity:0;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
.admin-mode-card strong{font-size:15px;color:#111827}
.admin-mode-card p{margin:0;font-size:12px;line-height:1.5;color:#6b7280}
.admin-mode-card.is-active{border-color:var(--accent);background:var(--accent-soft);box-shadow:0 0 0 1px var(--accent-soft-2)}
@media (max-width:1100px){.admin-mode-grid{grid-template-columns:1fr}}
</style>
<script>
(function () {
  var form = document.getElementById('siteModeForm');
  var alertEl = document.getElementById('adminAlert');
  var saveBtn = document.getElementById('siteModeSaveBtn');
  var currentEl = document.getElementById('siteModeCurrent');
  var saveUrl = <?= json_encode($saveUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var modeLabels = <?= json_encode($modeLabels, JSON_UNESCAPED_UNICODE) ?>;

  function showAlert(msg, ok) {
    if (!alertEl) return;
    alertEl.textContent = msg;
    alertEl.className = 'admin-alert ' + (ok ? 'is-ok' : 'is-err');
    alertEl.style.display = 'block';
  }

  function selectedMode() {
    var checked = form.querySelector('input[name="mode"]:checked');
    return checked ? String(checked.value || '') : '';
  }

  function syncCards() {
    form.querySelectorAll('.admin-mode-card').forEach(function (card) {
      var input = card.querySelector('input[name="mode"]');
      var on = !!(input && input.checked);
      card.classList.toggle('is-active', on);
    });
  }

  form.querySelectorAll('.admin-mode-card').forEach(function (card) {
    card.addEventListener('click', function (ev) {
      ev.preventDefault();
      var input = card.querySelector('input[name="mode"]');
      if (!input) return;
      input.checked = true;
      syncCards();
    });
  });
  form.addEventListener('change', syncCards);
  syncCards();

  saveBtn.addEventListener('click', async function () {
    var mode = selectedMode();
    if (!mode) {
      showAlert('사이트 모드를 선택해 주세요.', false);
      return;
    }
    var fd = new FormData(form);
    var payload = {
      mode: mode,
      maintenance_title: String(fd.get('maintenance_title') || ''),
      maintenance_message: String(fd.get('maintenance_message') || ''),
      maintenance_eta: String(fd.get('maintenance_eta') || '')
    };
    saveBtn.disabled = true;
    try {
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
      var savedMode = (((data.data || {}).siteMode) || {}).mode || mode;
      if (currentEl) {
        currentEl.innerHTML = '현재: <strong>' + (modeLabels[savedMode] || savedMode) + '</strong>';
      }
      var extra = savedMode === 'maintenance'
        ? ' 공개 사이트는 유지보수 페이지로 전환됩니다. (관리자 메뉴는 그대로 사용 가능)'
        : '';
      showAlert((data.message || '저장되었습니다.') + ' → ' + (modeLabels[savedMode] || savedMode) + '.' + extra, true);
    } catch (err) {
      showAlert(err.message || '저장에 실패했습니다.', false);
    } finally {
      saveBtn.disabled = false;
    }
  });
})();
</script>
