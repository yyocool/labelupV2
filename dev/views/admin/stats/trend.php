<?php
require view_path('admin/stats/_frame.php');
if ($statsError !== '') {
    return;
}
$report = $report ?? [];
$rows = $report['rows'] ?? [];
$s = $report['summary'] ?? [];
$p = $report['previous'] ?? [];
$grain = (string) ($filters['grain'] ?? 'day');
$max = 1;
foreach ($rows as $row) {
    $max = max($max, (int) ($row['pv'] ?? 0));
}
$grains = ['day' => '일', 'week' => '주', 'month' => '월'];
?>
<div class="stats-ranges">
  <?php foreach ($grains as $key => $label): ?>
  <a class="<?= $grain === $key ? 'is-active' : '' ?>" href="<?= e(url($statsAction) . '?' . http_build_query(array_merge($statsQuery, ['grain' => $key]))) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>
<div class="stats-kpis">
  <div class="admin-kpi"><div class="lbl">페이지뷰</div><div class="val"><?= number_format((int) ($s['pv'] ?? 0)) ?> <?= stats_delta_html((int) ($s['pv'] ?? 0), (int) ($p['pv'] ?? 0)) ?></div></div>
  <div class="admin-kpi"><div class="lbl">순방문자</div><div class="val"><?= number_format((int) ($s['uv'] ?? 0)) ?> <?= stats_delta_html((int) ($s['uv'] ?? 0), (int) ($p['uv'] ?? 0)) ?></div></div>
  <div class="admin-kpi"><div class="lbl">세션</div><div class="val"><?= number_format((int) ($s['sessions'] ?? 0)) ?> <?= stats_delta_html((int) ($s['sessions'] ?? 0), (int) ($p['sessions'] ?? 0)) ?></div></div>
  <div class="admin-kpi"><div class="lbl">회원</div><div class="val"><?= number_format((int) ($s['members'] ?? 0)) ?> <?= stats_delta_html((int) ($s['members'] ?? 0), (int) ($p['members'] ?? 0)) ?></div></div>
</div>
<section class="admin-card">
  <h2><?= e($grains[$grain] ?? '일') ?>별 추이</h2>
  <p class="stats-note">비교 막대는 바로 앞의 같은 길이 기간과 견준 값입니다.</p>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>기간</th><th>페이지뷰</th><th></th><th>순방문자</th><th>세션</th><th>회원</th><th>평균 체류</th></tr></thead>
      <tbody>
      <?php if ($rows === []): ?>
        <tr><td colspan="7" class="empty">이 기간의 접속이 없습니다.</td></tr>
      <?php else: foreach ($rows as $row): ?>
        <tr>
          <td><?= e((string) ($row['bucket'] ?? $row['day'] ?? '')) ?></td>
          <td><?= number_format((int) ($row['pv'] ?? 0)) ?></td>
          <td class="stats-bar-cell"><i style="width:<?= max(0, (int) round(((int) ($row['pv'] ?? 0)) / $max * 100)) ?>%"></i></td>
          <td><?= number_format((int) ($row['uv'] ?? 0)) ?></td>
          <td><?= number_format((int) ($row['sessions'] ?? 0)) ?></td>
          <td><?= number_format((int) ($row['members'] ?? 0)) ?></td>
          <td><?= e(stats_duration((int) round((float) ($row['avg_dwell'] ?? 0)))) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
