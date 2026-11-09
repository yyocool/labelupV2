<?php
require view_path('admin/stats/_frame.php');
if ($statsError !== '') {
    return;
}
$report = $report ?? [];
$block = static function (string $title, array $rows, string $empty, bool $device = false): void {
    echo '<section class="admin-card"><h2>' . e($title) . '</h2>';
    echo '<table class="admin-table"><thead><tr><th>항목</th><th>페이지뷰</th><th>' . ($device ? '순방문자' : '순방문자') . '</th></tr></thead><tbody>';
    if ($rows === []) {
        echo '<tr><td colspan="3" class="empty">' . e($empty) . '</td></tr>';
    }
    $max = 1;
    foreach ($rows as $row) {
        $max = max($max, (int) ($row['pv'] ?? 0));
    }
    foreach ($rows as $row) {
        $label = (string) ($row['label'] ?? '');
        if ($device) {
            $label = \App\Services\AccessStatsService::deviceLabel($label);
        }
        $width = (int) round(((int) ($row['pv'] ?? 0)) / $max * 100);
        echo '<tr><td>' . e($label !== '' ? $label : '-') . '<div class="stats-inline"><i style="width:' . $width . '%"></i></div></td>';
        echo '<td>' . number_format((int) ($row['pv'] ?? 0)) . '</td>';
        echo '<td>' . number_format((int) ($row['uv'] ?? $row['ips'] ?? 0)) . '</td></tr>';
    }
    echo '</tbody></table></section>';
};
?>
<div class="stats-split">
  <?php $block('기기', $report['devices'] ?? [], '기록이 없습니다.', true); ?>
  <?php $block('운영체제', $report['os'] ?? [], '운영체제 기록이 없습니다.'); ?>
</div>
<div class="stats-split stats-block">
  <?php $block('브라우저', $report['browsers'] ?? [], '브라우저 기록이 없습니다.'); ?>
  <?php $block('화면 크기', $report['screens'] ?? [], '화면 크기는 페이지를 연 뒤 수집됩니다. 아직 없습니다.'); ?>
</div>
<div class="stats-split stats-block">
  <?php $block('창 크기', $report['viewports'] ?? [], '창 크기 기록이 없습니다.'); ?>
  <?php $block('언어', $report['langs'] ?? [], '언어 기록이 없습니다.'); ?>
</div>
<div class="stats-split stats-block">
  <?php $block('시간대', $report['timezones'] ?? [], '시간대는 브라우저에서 수집됩니다. 아직 없습니다.'); ?>
  <section class="admin-card">
    <h2>봇</h2>
    <p class="stats-note">봇은 위 기간 필터를 따르고, 봇 제외 체크와 관계없이 따로 모읍니다.</p>
    <table class="admin-table">
      <thead><tr><th>종류</th><th>횟수</th><th>IP 수</th></tr></thead>
      <tbody>
      <?php if (empty($report['bots'])): ?>
        <tr><td colspan="3" class="empty">이 기간의 봇 접속이 없습니다.</td></tr>
      <?php else: foreach ($report['bots'] as $row): ?>
        <tr>
          <td><?= e((string) ($row['label'] ?: '기타 봇')) ?></td>
          <td><?= number_format((int) $row['pv']) ?></td>
          <td><?= number_format((int) ($row['ips'] ?? 0)) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </section>
</div>
