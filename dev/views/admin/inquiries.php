<?php
use App\Services\InquiryService;
$items = $items ?? [];
$filterStatus = $filterStatus ?? 'all';
$tabs = ['all' => '전체', 'open' => '접수', 'in_progress' => '처리중', 'answered' => '답변완료', 'closed' => '종료'];
$statusLabels = [];
foreach (['open', 'in_progress', 'answered', 'closed'] as $st) {
    $statusLabels[$st] = InquiryService::statusLabel($st);
}
$payload = [];
foreach ($items as $row) {
    $payload[(int) $row['id']] = [
        'id' => (int) $row['id'],
        'is_member' => !empty($row['user_id']),
        'name' => (string) ($row['name'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'subject' => (string) ($row['subject'] ?? ''),
        'content' => (string) ($row['content'] ?? ''),
        'status' => (string) ($row['status'] ?? ''),
        'answer' => (string) ($row['answer'] ?? ''),
        'answered_at' => substr((string) ($row['answered_at'] ?? ''), 0, 16),
        'created_at' => substr((string) ($row['created_at'] ?? ''), 0, 16),
        'logs' => array_map(static fn (array $log): array => [
            'action' => (string) $log['action'],
            'action_label' => InquiryService::logActionLabel((string) $log['action']),
            'status_from' => (string) ($log['status_from'] ?? ''),
            'status_to' => (string) ($log['status_to'] ?? ''),
            'note' => (string) ($log['note'] ?? ''),
            'admin_name' => (string) ($log['admin_name'] ?? ''),
            'created_at' => substr((string) ($log['created_at'] ?? ''), 0, 16),
        ], $row['logs'] ?? []),
    ];
}
?>
<div class="admin-head">
  <div>
    <h1>1:1 문의</h1>
    <p>회원 문의에 답변하고 처리 이력을 남깁니다. 답변은 회원의 「내 문의 내역」에 보이고, 처리 이력은 관리자만 봅니다.</p>
  </div>
</div>
<div class="admin-toolbar">
  <?php foreach ($tabs as $key => $label): ?>
  <a class="admin-btn<?= $filterStatus === $key ? ' admin-btn--primary' : '' ?>" href="<?= url('admin/ops/inquiries') ?><?= $key !== 'all' ? '?status=' . urlencode($key) : '' ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>접수</th>
        <th>이름</th>
        <th>이메일</th>
        <th>제목 / 내용</th>
        <th>상태</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="6" class="empty">문의가 없습니다.</td></tr>
    <?php else: ?>
    <?php foreach ($items as $row): ?>
      <?php $logs = $row['logs'] ?? []; ?>
      <tr>
        <td><small><?= e(substr((string) ($row['created_at'] ?? ''), 0, 16)) ?></small></td>
        <td><?= e((string) ($row['name'] ?? '')) ?><?= empty($row['user_id']) ? ' <small class="admin-muted">(비회원)</small>' : '' ?></td>
        <td><small><?= e((string) ($row['email'] ?? '')) ?></small></td>
        <td>
          <b><?= e((string) ($row['subject'] ?? '')) ?></b>
          <div class="admin-muted"><?= e(mb_substr((string) ($row['content'] ?? ''), 0, 80)) ?></div>
        </td>
        <td>
          <?= e(InquiryService::statusLabel((string) ($row['status'] ?? ''))) ?>
          <?php if ($logs): $last = end($logs); ?>
          <div class="admin-muted"><small>이력 <?= count($logs) ?>건 · <?= e(substr((string) ($last['created_at'] ?? ''), 5, 11)) ?></small></div>
          <?php endif; ?>
        </td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-inquiry-open" data-id="<?= (int) $row['id'] ?>">처리</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div id="inquiryAdminModal" class="admin-modal" hidden>
  <div class="admin-modal-backdrop js-inquiry-close"></div>
  <div class="admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="inquiryAdminTitle" style="width:min(760px,100%)">
    <div class="admin-modal-head">
      <h3 id="inquiryAdminTitle">1:1 문의 처리</h3>
      <button type="button" class="admin-modal-close js-inquiry-close" aria-label="닫기">×</button>
    </div>
    <div class="admin-modal-body">
      <p class="admin-muted" id="inquiryAdminMeta"></p>
      <div class="admin-field">
        <label>문의 내용</label>
        <div class="inq-content" id="inquiryAdminContent"></div>
      </div>

      <p class="inq-title" id="inquiryAdminLogTitle">처리 이력</p>
      <ol class="inq-log" id="inquiryAdminLogs"></ol>

      <div class="inq-box">
        <p class="inq-box__title">처리 이력 등록 <small class="admin-muted">관리자만 보는 기록입니다</small></p>
        <textarea class="admin-input inq-textarea" id="inquiryAdminNote" rows="3" placeholder="처리 내용을 기록하세요 (예: 전화 안내 완료, 재발송 요청 등)"></textarea>
        <div class="inq-foot">
          <select class="admin-select" id="inquiryAdminNoteStatus"></select>
          <button type="button" class="admin-btn" id="inquiryAdminNoteSave">처리 이력 등록</button>
        </div>
      </div>

      <div class="inq-box" id="inquiryAdminAnswerBox">
        <p class="inq-box__title">회원 답변 <small class="admin-muted">회원의 「내 문의 내역」에 표시됩니다</small></p>
        <textarea class="admin-input inq-textarea" id="inquiryAdminAnswer" rows="5" placeholder="회원에게 보낼 답변을 입력하세요"></textarea>
        <div class="inq-foot">
          <small class="admin-muted" id="inquiryAdminAnswerHint"></small>
          <button type="button" class="admin-btn admin-btn--primary" id="inquiryAdminAnswerSave">답변 저장</button>
        </div>
      </div>
      <p class="admin-muted" id="inquiryAdminGuest" hidden>비회원 문의입니다. 이메일로 답변한 뒤 처리 이력에 기록해 주세요.</p>
    </div>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn js-inquiry-close">닫기</button>
    </div>
  </div>
</div>
<style>
.inq-content{padding:10px 12px;border-radius:8px;background:#f7f6f8;white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.55}
.inq-title{margin:16px 0 8px;font-size:14px;font-weight:800}
.inq-log{list-style:none;margin:0 0 16px;padding:0 0 0 14px;border-left:2px solid #ece9ee;display:grid;gap:10px}
.inq-log li{position:relative}
.inq-log li::before{content:"";position:absolute;left:-20px;top:5px;width:10px;height:10px;border-radius:50%;background:#c9c4cc;box-shadow:0 0 0 2px #fff}
.inq-log li.is-note::before{background:#5b6bd6}
.inq-log li.is-status::before{background:#e0a100}
.inq-log li.is-answer::before{background:#1a7f4b}
.inq-log__meta{display:flex;flex-wrap:wrap;align-items:baseline;gap:4px 8px;font-size:13px}
.inq-log__meta small{color:#8a8590}
.inq-log__chip{padding:1px 8px;border-radius:999px;background:#f4f2f6;font-size:12px;font-weight:700;color:#5a5560}
.inq-log__note{margin-top:4px;padding:8px 10px;border-radius:8px;background:#f7f6f8;font-size:13px;line-height:1.55;white-space:pre-wrap;overflow-wrap:anywhere}
.inq-box{margin-top:12px;padding:12px;border:1px solid #ece9ee;border-radius:10px}
.inq-box__title{margin:0 0 8px;font-size:14px;font-weight:800}
.inq-box__title small{margin-left:6px;font-weight:400}
.inq-textarea{width:100%;box-sizing:border-box;resize:vertical}
.inq-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:8px}
.inq-foot select{width:auto;max-width:60%}
</style>
<script>
(function () {
  const ITEMS = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const STATUS = <?= json_encode($statusLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const OPEN_KEY = 'labelup.inquiryOpen';
  const modal = document.getElementById('inquiryAdminModal');
  const $ = (id) => document.getElementById(id);
  let current = null;

  function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
  }
  function statusLabel(st) {
    return STATUS[st] || st;
  }

  function open(id) {
    const item = ITEMS[id];
    if (!item) return;
    current = item;
    $('inquiryAdminTitle').textContent = '1:1 문의 처리 · ' + item.subject;
    $('inquiryAdminMeta').innerHTML = '<strong>' + esc(item.name) + '</strong> · ' + esc(item.email) +
      ' · ' + (item.is_member ? '회원' : '비회원') + ' · 접수 ' + esc(item.created_at) + ' · 현재 상태 <b>' + esc(statusLabel(item.status)) + '</b>';
    $('inquiryAdminContent').textContent = item.content;

    const logs = ['<li><div class="inq-log__meta"><b>문의 접수</b><small>' + esc(item.created_at) + ' · ' + esc(item.name) + '</small></div></li>'];
    item.logs.forEach((log) => {
      logs.push(
        '<li class="is-' + esc(log.action) + '"><div class="inq-log__meta"><b>' + esc(log.action_label) + '</b>' +
          (log.status_to ? '<span class="inq-log__chip">' + (log.status_from ? esc(statusLabel(log.status_from)) + ' → ' : '') + esc(statusLabel(log.status_to)) + '</span>' : '') +
          '<small>' + esc(log.created_at) + (log.admin_name ? ' · ' + esc(log.admin_name) : '') + '</small></div>' +
          (log.note ? '<div class="inq-log__note">' + esc(log.note) + '</div>' : '') +
        '</li>'
      );
    });
    $('inquiryAdminLogTitle').textContent = '처리 이력 ' + item.logs.length + '건';
    $('inquiryAdminLogs').innerHTML = logs.join('');

    const sel = $('inquiryAdminNoteStatus');
    sel.innerHTML = '<option value="">상태 유지 (' + esc(statusLabel(item.status)) + ')</option>' +
      Object.keys(STATUS).filter((st) => st !== item.status).map((st) => '<option value="' + esc(st) + '">' + esc(STATUS[st]) + '(으)로 변경</option>').join('');
    $('inquiryAdminNote').value = '';

    $('inquiryAdminAnswerBox').hidden = !item.is_member;
    $('inquiryAdminGuest').hidden = item.is_member;
    $('inquiryAdminAnswer').value = item.answer;
    $('inquiryAdminAnswerHint').textContent = item.answered_at
      ? '답변 ' + item.answered_at + ' · 다시 저장하면 수정되고 회원에게 알림이 갑니다.'
      : '저장하면 답변완료로 바뀌고 회원에게 알림이 갑니다.';

    modal.hidden = false;
    modal.querySelector('.admin-modal-body').scrollTop = 0;
  }

  function close() {
    modal.hidden = true;
    current = null;
  }

  async function save(btn, path, body) {
    btn.disabled = true;
    try {
      await AdminAPI.post(path, body);
      sessionStorage.setItem(OPEN_KEY, String(current.id));
      window.location.reload();
    } catch (err) {
      alert(err.message);
      btn.disabled = false;
    }
  }

  document.querySelectorAll('.js-inquiry-open').forEach((btn) => {
    btn.addEventListener('click', () => open(Number(btn.dataset.id)));
  });
  document.querySelectorAll('.js-inquiry-close').forEach((el) => el.addEventListener('click', close));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.hidden) close();
  });

  $('inquiryAdminNoteSave').addEventListener('click', (e) => {
    const note = $('inquiryAdminNote').value.trim();
    const status = $('inquiryAdminNoteStatus').value;
    if (!note && !status) {
      alert('처리 내용을 입력하거나 상태를 선택해 주세요.');
      return;
    }
    save(e.currentTarget, '/api/admin/inquiry/note', { id: current.id, note, status });
  });
  $('inquiryAdminAnswerSave').addEventListener('click', (e) => {
    const answer = $('inquiryAdminAnswer').value.trim();
    if (!answer) {
      alert('답변 내용을 입력해 주세요.');
      return;
    }
    if (!confirm('회원에게 답변 알림을 보냅니다. 저장할까요?')) return;
    save(e.currentTarget, '/api/admin/inquiry/answer', { id: current.id, answer });
  });

  const reopen = sessionStorage.getItem(OPEN_KEY);
  if (reopen) {
    sessionStorage.removeItem(OPEN_KEY);
    open(Number(reopen));
  }
})();
</script>
