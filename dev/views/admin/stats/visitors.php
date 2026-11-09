<?php
require view_path('admin/stats/_frame.php');
if ($statsError !== '') {
    return;
}
$report = $report ?? [];
$split = $report['split'] ?? [];
$splitSum = (int) ($split['new_visitors'] ?? 0) + (int) ($split['returning_visitors'] ?? 0);
$order = ['1회' => 1, '2~3회' => 2, '4~10회' => 3, '11회 이상' => 4];
$freq = $report['freq'] ?? [];
usort($freq, static function (array $a, array $b) use ($order): int {
    return ($order[(string) ($a['bucket'] ?? '')] ?? 9) <=> ($order[(string) ($b['bucket'] ?? '')] ?? 9);
});
$freqSum = 0;
foreach ($freq as $row) {
    $freqSum += (int) $row['visitors'];
}
?>
<div class="stats-kpis">
  <div class="admin-kpi"><div class="lbl">신규</div><div class="val"><?= number_format((int) ($split['new_visitors'] ?? 0)) ?></div><div class="stats-kpi-sub">이 기간에 처음 온 브라우저 · <?= e(stats_share((int) ($split['new_visitors'] ?? 0), $splitSum)) ?></div></div>
  <div class="admin-kpi"><div class="lbl">재방문</div><div class="val"><?= number_format((int) ($split['returning_visitors'] ?? 0)) ?></div><div class="stats-kpi-sub">예전에도 온 적 있는 브라우저 · <?= e(stats_share((int) ($split['returning_visitors'] ?? 0), $splitSum)) ?></div></div>
  <?php foreach ($report['actors'] ?? [] as $row): ?>
  <div class="admin-kpi">
    <div class="lbl"><?= e(match ((string) $row['actor_role']) { 'member' => '회원 페이지뷰', 'admin' => '관리자 페이지뷰', default => '비회원 페이지뷰' }) ?></div>
    <div class="val"><?= number_format((int) $row['pv']) ?></div>
    <div class="stats-kpi-sub">순방문자 <?= number_format((int) $row['uv']) ?></div>
  </div>
  <?php endforeach; ?>
</div>
<section class="admin-card">
  <h2>방문 횟수</h2>
  <p class="stats-note">선택한 기간 동안 한 브라우저가 연 화면 수입니다.</p>
  <table class="admin-table">
    <thead><tr><th>구간</th><th>방문자</th><th>비중</th></tr></thead>
    <tbody>
    <?php if ($freq === []): ?>
      <tr><td colspan="3" class="empty">기록이 없습니다.</td></tr>
    <?php else: foreach ($freq as $row): ?>
      <tr>
        <td><?= e((string) $row['bucket']) ?></td>
        <td><?= number_format((int) $row['visitors']) ?></td>
        <td><?= e(stats_share((int) $row['visitors'], $freqSum)) ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</section>
<section class="admin-card stats-block">
  <h2>회원 방문</h2>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>회원</th><th>이름</th><th>페이지뷰</th><th>방문 일수</th><th>마지막</th></tr></thead>
      <tbody>
      <?php if (empty($report['members'])): ?>
        <tr><td colspan="5" class="empty">로그인한 회원 접속이 없습니다.</td></tr>
      <?php else: foreach ($report['members'] as $row): ?>
        <tr>
          <td><a href="<?= url('admin/users/' . (int) $row['user_id']) ?>"><?= e(mask_email_id((string) ($row['email'] ?? ''))) ?></a></td>
          <td><?= e(mask_person_name((string) ($row['name'] ?? ''))) ?></td>
          <td><?= number_format((int) $row['pv']) ?></td>
          <td><?= number_format((int) $row['days']) ?></td>
          <td><small><?= e(substr((string) ($row['last_at'] ?? ''), 0, 16)) ?></small></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
<section class="admin-card stats-block">
  <h2>비회원 방문</h2>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>IP</th><th>기기</th><th>브라우저</th><th>OS</th><th>페이지뷰</th><th>처음</th><th>마지막</th></tr></thead>
      <tbody>
      <?php if (empty($report['anonymous'])): ?>
        <tr><td colspan="7" class="empty">비회원 접속이 없습니다.</td></tr>
      <?php else: foreach ($report['anonymous'] as $row): ?>
        <tr>
          <td><?= e((string) $row['ip']) ?></td>
          <td><?= e(\App\Services\AccessStatsService::deviceLabel((string) $row['device'])) ?></td>
          <td><?= e((string) $row['browser_name']) ?></td>
          <td><?= e((string) $row['os_name']) ?></td>
          <td><?= number_format((int) $row['pv']) ?></td>
          <td><small><?= e(substr((string) $row['first_at'], 0, 16)) ?></small></td>
          <td><small><?= e(substr((string) $row['last_at'], 0, 16)) ?></small></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
