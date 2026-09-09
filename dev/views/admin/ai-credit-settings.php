<?php
$settings = $settings ?? ['is_enabled' => true, 'monthly_budget' => 0, 'low_balance_threshold' => 10, 'costs' => []];
$costs = $settings['costs'] ?? [];
?>
<div class="admin-head">
  <div>
    <h1>AI 크레딧 설정</h1>
    <p>회원이 홈/편집기에서 라비 AI를 사용할 때 차감할 크레딧을 기능별로 설정합니다.</p>
  </div>
</div>

<div id="adminAlert" class="admin-alert" hidden></div>

<form id="aiCreditForm" class="admin-form-card">
  <section class="admin-panel">
    <h2 class="admin-section-title">기본 설정</h2>
    <label class="admin-field admin-field--check">
      <input type="checkbox" name="is_enabled" value="1" <?= !empty($settings['is_enabled']) ? 'checked' : '' ?>>
      <span>AI 사용 시 크레딧 차감 사용</span>
    </label>
    <p class="admin-muted">끄면 AI는 크레딧 없이 동작합니다. 켜면 아래에서 설정한 비용만큼 회원 잔액에서 차감됩니다.</p>
    <div class="admin-form-grid">
      <label class="admin-field">
        <span>월간 사용량 표시 한도 (C)</span>
        <input type="number" min="0" class="admin-input" name="monthly_budget" value="<?= (int) ($settings['monthly_budget'] ?? 0) ?>">
        <small class="admin-muted">0이면 마이페이지에 이번 달 사용량만 표시하고 한도 바는 숨깁니다.</small>
      </label>
      <label class="admin-field">
        <span>잔액 부족 알림 기준 (C)</span>
        <input type="number" min="0" class="admin-input" name="low_balance_threshold" value="<?= (int) ($settings['low_balance_threshold'] ?? 10) ?>">
      </label>
    </div>
  </section>

  <section class="admin-panel" style="margin-top:1.25rem">
    <h2 class="admin-section-title">기능별 차감 크레딧</h2>
    <div class="admin-table-wrap">
      <table class="admin-table" id="aiCreditCostTable">
        <thead>
          <tr>
            <th>기능</th>
            <th>설명</th>
            <th>차감(C)</th>
            <th>사용</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($costs as $i => $row): ?>
          <tr data-intent="<?= e((string) $row['intent']) ?>">
            <td>
              <strong><?= e((string) $row['label']) ?></strong>
              <br><code><?= e((string) $row['intent']) ?></code>
              <input type="hidden" name="costs[<?= $i ?>][intent]" value="<?= e((string) $row['intent']) ?>">
              <input type="hidden" name="costs[<?= $i ?>][label]" value="<?= e((string) $row['label']) ?>">
              <input type="hidden" name="costs[<?= $i ?>][sort_order]" value="<?= (int) ($row['sort_order'] ?? 0) ?>">
            </td>
            <td>
              <input type="text" class="admin-input" name="costs[<?= $i ?>][description]" value="<?= e((string) ($row['description'] ?? '')) ?>">
            </td>
            <td style="width:7rem">
              <input type="number" min="0" class="admin-input" name="costs[<?= $i ?>][credit_cost]" value="<?= (int) ($row['credit_cost'] ?? 0) ?>">
            </td>
            <td>
              <label class="admin-field--check">
                <input type="checkbox" name="costs[<?= $i ?>][is_active]" value="1" <?= !empty($row['is_active']) ? 'checked' : '' ?>>
                활성
              </label>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <div class="admin-head-actions" style="margin-top:1rem">
    <button type="submit" class="admin-btn admin-btn--primary">설정 저장</button>
  </div>
</form>

<script>
(() => {
  const form = document.getElementById('aiCreditForm');
  const alertEl = document.getElementById('adminAlert');
  if (!form) return;

  function showAlert(msg, ok) {
    if (!alertEl) return;
    alertEl.hidden = false;
    alertEl.textContent = msg;
    alertEl.className = 'admin-alert ' + (ok ? 'is-ok' : 'is-err');
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    const costs = [];
    const map = {};
    for (const [key, value] of fd.entries()) {
      const m = key.match(/^costs\[(\d+)\]\[(\w+)\]$/);
      if (!m) continue;
      const idx = m[1];
      map[idx] = map[idx] || {};
      map[idx][m[2]] = value;
    }
    Object.values(map).forEach((row) => {
      costs.push({
        intent: row.intent || '',
        label: row.label || '',
        description: row.description || '',
        credit_cost: Number(row.credit_cost || 0),
        sort_order: Number(row.sort_order || 0),
        is_active: !!row.is_active,
      });
    });
    const payload = {
      is_enabled: fd.get('is_enabled') === '1',
      monthly_budget: Number(fd.get('monthly_budget') || 0),
      low_balance_threshold: Number(fd.get('low_balance_threshold') || 0),
      costs,
    };
    try {
      const res = await fetch('<?= url('api/admin/ai/credit-settings/save') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.message || '저장 실패');
      showAlert(data.message || '저장되었습니다.', true);
    } catch (err) {
      showAlert(err.message || '저장 실패', false);
    }
  });
})();
</script>
