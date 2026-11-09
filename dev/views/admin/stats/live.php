<?php
require view_path('admin/stats/_frame.php');
if ($statsError !== '') {
    return;
}
$report = $report ?? [];
$active = $report['active'] ?? [];
?>
<div class="stats-livebar">
  <label class="stats-check"><input type="checkbox" id="statsLiveReload" checked> 20초마다 새로고침</label>
  <span class="stats-note">실시간은 기간 버튼과 상관없이 최근 1시간을 봅니다. 관리자 접속을 보려면 ‘관리자 포함’을 켜세요.</span>
</div>
<div class="stats-kpis">
  <div class="admin-kpi"><div class="lbl">최근 5분 방문자</div><div class="val"><?= number_format((int) ($active['m5'] ?? 0)) ?></div></div>
  <div class="admin-kpi"><div class="lbl">최근 15분 방문자</div><div class="val"><?= number_format((int) ($active['m15'] ?? 0)) ?></div></div>
  <div class="admin-kpi"><div class="lbl">최근 1시간 방문자</div><div class="val"><?= number_format((int) ($active['m60'] ?? 0)) ?></div></div>
  <div class="admin-kpi"><div class="lbl">최근 1시간 페이지뷰</div><div class="val"><?= number_format((int) ($active['pv60'] ?? 0)) ?></div></div>
</div>
<div class="stats-split">
  <section class="admin-card">
    <h2>방금 열린 화면</h2>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>시간</th><th>페이지</th><th>영역</th><th>대상</th><th>기기</th><th>유입</th><th>상태</th></tr></thead>
        <tbody>
        <?php if (empty($report['recent'])): ?>
          <tr><td colspan="7" class="empty">최근 1시간 접속이 없습니다.</td></tr>
        <?php else: foreach ($report['recent'] as $row): ?>
          <tr>
            <td><small><?= e(substr((string) $row['visited_at'], 11, 8)) ?></small></td>
            <td><code><?= e((string) $row['path']) ?></code><?php if (!empty($row['title'])): ?><div class="stats-title"><?= e((string) $row['title']) ?></div><?php endif; ?></td>
            <td><?= e(\App\Services\AccessStatsService::sectionLabel((string) $row['section'])) ?></td>
            <td><?= e(match ((string) $row['actor_role']) { 'member' => '회원', 'admin' => '관리자', default => '비회원' }) ?><?= !empty($row['is_bot']) ? ' · 봇' : '' ?></td>
            <td><?= e(\App\Services\AccessStatsService::deviceLabel((string) $row['device'])) ?></td>
            <td><?= e((string) ($row['referer_host'] !== '' ? $row['referer_host'] : '직접')) ?></td>
            <td><?= (int) ($row['status_code'] ?? 0) >= 400 ? '<span class="admin-badge admin-badge--err">' . (int) $row['status_code'] . '</span>' : (int) ($row['status_code'] ?? 200) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </section>
  <section class="admin-card">
    <h2>1시간 인기 페이지</h2>
    <table class="admin-table">
      <thead><tr><th>페이지</th><th>페이지뷰</th><th>방문자</th></tr></thead>
      <tbody>
      <?php if (empty($report['top'])): ?>
        <tr><td colspan="3" class="empty">기록이 없습니다.</td></tr>
      <?php else: foreach ($report['top'] as $row): ?>
        <tr>
          <td><code><?= e((string) $row['path_key']) ?></code></td>
          <td><?= number_format((int) $row['pv']) ?></td>
          <td><?= number_format((int) $row['uv']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </section>
</div>
<script>
(function () {
  var box = document.getElementById('statsLiveReload');
  if (!box) return;
  var timer = 0;
  function arm() {
    if (timer) clearInterval(timer);
    if (box.checked) timer = setInterval(function () { location.reload(); }, 20000);
  }
  box.addEventListener('change', arm);
  arm();
})();
</script>
