<?php
use App\Services\InquiryService;
$items = $items ?? [];
$filterStatus = $filterStatus ?? 'all';
$tabs = ['all' => '전체', 'open' => '접수', 'in_progress' => '처리중', 'answered' => '답변완료', 'closed' => '종료'];
?>
<div class="admin-head">
  <div>
    <h1>1:1 문의</h1>
    <p>회원이 마이페이지에서 남긴 문의를 확인하고 상태를 변경합니다.</p>
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
      <tr>
        <td><small><?= e(substr((string) ($row['created_at'] ?? ''), 0, 16)) ?></small></td>
        <td><?= e((string) ($row['name'] ?? '')) ?></td>
        <td><small><?= e((string) ($row['email'] ?? '')) ?></small></td>
        <td>
          <b><?= e((string) ($row['subject'] ?? '')) ?></b>
          <div class="admin-muted"><?= e(mb_substr((string) ($row['content'] ?? ''), 0, 80)) ?></div>
        </td>
        <td><?= e(InquiryService::statusLabel((string) ($row['status'] ?? ''))) ?></td>
        <td>
          <select class="admin-input js-inquiry-status" data-id="<?= (int) $row['id'] ?>">
            <?php foreach (['open','in_progress','answered','closed'] as $st): ?>
            <option value="<?= $st ?>"<?= ($row['status'] ?? '') === $st ? ' selected' : '' ?>><?= e(InquiryService::statusLabel($st)) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<script>
document.querySelectorAll('.js-inquiry-status').forEach((sel) => {
  sel.addEventListener('change', async () => {
    try {
      await AdminAPI.post('/api/admin/inquiry/update', { id: Number(sel.dataset.id), status: sel.value });
      window.location.reload();
    } catch (err) {
      alert(err.message);
    }
  });
});
</script>
