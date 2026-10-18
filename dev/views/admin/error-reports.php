<?php
use App\Services\ErrorReportService;

$items = $items ?? [];
$filterStatus = $filterStatus ?? 'all';
$filterKind = $filterKind ?? 'all';
$statuses = $statuses ?? ErrorReportService::statuses();
$kinds = $kinds ?? ErrorReportService::kinds();
$tabs = ['all' => '전체'] + $statuses;
$kindTabs = ['all' => '모두'] + $kinds;
$listQuery = static function (string $status, string $kind): string {
    $query = [];
    if ($status !== 'all') {
        $query['status'] = $status;
    }
    if ($kind !== 'all') {
        $query['kind'] = $kind;
    }
    return $query === [] ? '' : '?' . http_build_query($query);
};
?>
<div class="admin-head">
  <div>
    <h1>오류&amp;제안관리</h1>
    <p>회원이 보낸 오류와 제안을 확인하고, 처리 결과와 포상 잉크를 등록합니다.</p>
  </div>
</div>
<div class="admin-toolbar">
  <?php foreach ($kindTabs as $key => $label): ?>
  <a class="admin-btn<?= $filterKind === $key ? ' admin-btn--primary' : '' ?>" href="<?= url('admin/ops/error-reports') ?><?= e($listQuery($filterStatus, $key)) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>
<div class="admin-toolbar">
  <?php foreach ($tabs as $key => $label): ?>
  <a class="admin-btn<?= $filterStatus === $key ? ' admin-btn--primary' : '' ?>" href="<?= url('admin/ops/error-reports') ?><?= e($listQuery($key, $filterKind)) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>접수</th>
        <th>신고자</th>
        <th>구분</th>
        <th>분류</th>
        <th>내용</th>
        <th>상태</th>
        <th>포상</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($items === []): ?>
      <tr><td colspan="8" class="empty">접수된 내용이 없습니다.</td></tr>
    <?php else: ?>
      <?php foreach ($items as $row): ?>
      <tr>
        <td><small><?= e(substr((string) ($row['created_at'] ?? ''), 0, 16)) ?></small></td>
        <td>
          <a href="<?= url('admin/users/' . (int) ($row['user_id'] ?? 0)) ?>"><?= e((string) (($row['user_name'] ?? '') !== '' ? $row['user_name'] : ($row['user_email'] ?? ''))) ?></a>
          <div class="admin-muted"><?= e((string) ($row['user_email'] ?? '')) ?></div>
        </td>
        <td><?= e((string) ($row['kind_label'] ?? '오류')) ?></td>
        <td><?= e((string) ($row['category_label'] ?? '')) ?></td>
        <td>
          <b><?= e((string) ($row['title'] ?? '')) ?></b>
          <div class="admin-muted"><?= e(mb_substr((string) ($row['content'] ?? ''), 0, 80)) ?></div>
          <?php if ((int) ($row['image_count'] ?? 0) > 0): ?>
          <div class="admin-muted">이미지 <?= (int) $row['image_count'] ?>장</div>
          <?php endif; ?>
        </td>
        <td><?= e((string) ($row['status_label'] ?? '')) ?></td>
        <td><?= (int) ($row['reward_total'] ?? 0) > 0 ? number_format((int) $row['reward_total']) . ' C' : '-' ?></td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-error-report-open" data-id="<?= (int) ($row['id'] ?? 0) ?>">처리</button>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div id="errorReportAdminModal" class="admin-modal" hidden>
  <div class="admin-modal-backdrop js-error-report-close"></div>
  <div class="admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="errorReportAdminTitle" style="width:min(760px,100%)">
    <div class="admin-modal-head">
      <h3 id="errorReportAdminTitle">오류&amp;제안 처리</h3>
      <button type="button" class="admin-modal-close js-error-report-close" aria-label="닫기">×</button>
    </div>
    <form id="errorReportAdminForm" class="admin-modal-body">
      <input type="hidden" name="id" id="errorReportAdminId" value="">
      <p class="admin-muted" id="errorReportAdminMeta"></p>
      <div class="admin-field">
        <label>신고 내용</label>
        <div id="errorReportAdminBody" style="white-space:pre-wrap;line-height:1.55"></div>
      </div>
      <p class="admin-muted" id="errorReportAdminUrl"></p>
      <div class="admin-field" id="errorReportAdminShotsWrap" hidden>
        <label>첨부 이미지</label>
        <div id="errorReportAdminShots" class="error-report-shots"></div>
      </div>
      <style>
        .error-report-shots{display:flex;flex-wrap:wrap;gap:8px}
        .error-report-shots a{display:block;width:140px;height:100px;border:1px solid #e6e1de;border-radius:8px;overflow:hidden;background:#f7f5f4}
        .error-report-shots img{width:100%;height:100%;object-fit:cover;display:block}
      </style>
      <div id="errorReportAdminHistory"></div>
      <div class="admin-field">
        <label for="errorReportAdminStatus">처리 상태</label>
        <select class="admin-select" id="errorReportAdminStatus" name="status">
          <?php foreach (['reviewing', 'resolved', 'rejected'] as $st): ?>
          <option value="<?= e($st) ?>"><?= e(ErrorReportService::statusLabel($st)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="admin-field">
        <label for="errorReportAdminNote">처리 결과</label>
        <textarea class="admin-input" id="errorReportAdminNote" name="result_note" rows="4" placeholder="확인한 내용과 조치 결과를 입력하세요."></textarea>
      </div>
      <div class="admin-field">
        <label for="errorReportAdminReward">포상 잉크</label>
        <input class="admin-input" id="errorReportAdminReward" name="reward_credits" type="number" min="0" step="1" value="0">
        <p class="admin-muted">0이면 잉크를 지급하지 않습니다. 저장할 때마다 입력한 수치만큼 추가로 지급되고, 회원 잉크 이력에 남습니다.</p>
      </div>
    </form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn js-error-report-close">취소</button>
      <button type="submit" form="errorReportAdminForm" class="admin-btn admin-btn--primary" id="errorReportAdminSave">저장</button>
    </div>
  </div>
</div>
<script>
(function () {
  const modal = document.getElementById('errorReportAdminModal');
  const form = document.getElementById('errorReportAdminForm');
  const historyEl = document.getElementById('errorReportAdminHistory');
  function closeModal() {
    if (modal) modal.hidden = true;
  }
  function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
  }
  function fill(detail) {
    const report = detail.report || {};
    document.getElementById('errorReportAdminId').value = String(report.id || '');
    document.getElementById('errorReportAdminTitle').textContent = '오류&제안 처리 · ' + (report.title || '');
    document.getElementById('errorReportAdminMeta').innerHTML =
      '<strong>' + esc(report.user_name || report.user_email || '') + '</strong> · ' +
      esc(report.user_email || '') + ' · ' + esc(report.kind_label || '오류') + ' · ' + esc(report.category_label || '') +
      ' · ' + esc(String(report.created_at || '').slice(0, 16));
    document.getElementById('errorReportAdminBody').textContent = report.content || '';
    const url = report.page_url || '';
    document.getElementById('errorReportAdminUrl').innerHTML = url
      ? '발생 화면 <a href="' + esc(url) + '" target="_blank" rel="noopener">' + esc(url) + '</a>'
      : '';
    const shots = Array.isArray(report.images) ? report.images : [];
    const shotsWrap = document.getElementById('errorReportAdminShotsWrap');
    const shotsEl = document.getElementById('errorReportAdminShots');
    if (shotsWrap && shotsEl) {
      shotsWrap.hidden = shots.length === 0;
      shotsEl.innerHTML = shots.map((img) => (
        '<a href="' + esc(img.url || '') + '" target="_blank" rel="noopener">' +
          '<img src="' + esc(img.url || '') + '" alt="첨부 이미지">' +
        '</a>'
      )).join('');
    }
    document.getElementById('errorReportAdminStatus').value = report.status && report.status !== 'open' ? report.status : 'reviewing';
    document.getElementById('errorReportAdminNote').value = '';
    document.getElementById('errorReportAdminReward').value = '0';
    const actions = detail.actions || [];
    if (!actions.length) {
      historyEl.innerHTML = '<p class="admin-muted">저장된 처리 이력이 없습니다.</p>';
    } else {
      historyEl.innerHTML = '<p><b>처리 이력</b></p><ul class="admin-muted" style="padding-left:18px">' + actions.map((row) => (
        '<li style="margin:0 0 8px"><b>' + esc(String(row.created_at || '').slice(0, 16)) + '</b> · ' +
        esc(row.status_label || '') + ' · ' + esc(row.admin_name || row.admin_email || '관리자') +
        (Number(row.reward_credits) > 0 ? ' · 포상 ' + Number(row.reward_credits).toLocaleString('ko-KR') + ' C' : '') +
        '<div style="white-space:pre-wrap">' + esc(row.result_note || '') + '</div></li>'
      )).join('') + '</ul>';
    }
    modal.hidden = false;
  }
  document.querySelectorAll('.js-error-report-open').forEach((btn) => {
    btn.addEventListener('click', async () => {
      try {
        const data = await AdminAPI.get('/api/admin/error-reports?id=' + encodeURIComponent(btn.dataset.id || '0'));
        fill(data.data || {});
      } catch (err) {
        alert(err.message);
      }
    });
  });
  document.querySelectorAll('.js-error-report-close').forEach((el) => {
    el.addEventListener('click', closeModal);
  });
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const saveBtn = document.getElementById('errorReportAdminSave');
    if (saveBtn) saveBtn.disabled = true;
    try {
      await AdminAPI.post('/api/admin/error-reports/process', {
        id: Number(document.getElementById('errorReportAdminId').value || 0),
        status: document.getElementById('errorReportAdminStatus').value,
        result_note: document.getElementById('errorReportAdminNote').value,
        reward_credits: Number(document.getElementById('errorReportAdminReward').value || 0),
      });
      window.location.reload();
    } catch (err) {
      alert(err.message);
      if (saveBtn) saveBtn.disabled = false;
    }
  });
})();
</script>
