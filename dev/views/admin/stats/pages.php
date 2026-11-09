<?php
require view_path('admin/stats/_frame.php');
if ($statsError !== '') {
    return;
}
$report = $report ?? [];
$render = static function (array $rows, string $empty, bool $dwell = false): void {
    echo '<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>페이지</th><th>페이지뷰</th><th>순방문자</th>';
    if ($dwell) {
        echo '<th>세션</th><th>평균 체류</th><th>오류</th><th>제목</th>';
    }
    echo '</tr></thead><tbody>';
    if ($rows === []) {
        $cols = $dwell ? 7 : 3;
        echo '<tr><td colspan="' . $cols . '" class="empty">' . e($empty) . '</td></tr>';
    }
    foreach ($rows as $row) {
        echo '<tr><td><code>' . e((string) ($row['path_key'] ?? '')) . '</code></td>';
        echo '<td>' . number_format((int) ($row['pv'] ?? 0)) . '</td>';
        echo '<td>' . number_format((int) ($row['uv'] ?? 0)) . '</td>';
        if ($dwell) {
            echo '<td>' . number_format((int) ($row['sessions'] ?? 0)) . '</td>';
            echo '<td>' . e(stats_duration((int) round((float) ($row['avg_dwell'] ?? 0)))) . '</td>';
            echo '<td>' . number_format((int) ($row['errors'] ?? 0)) . '</td>';
            echo '<td>' . e((string) (($row['title'] ?? '') !== '' ? $row['title'] : '-')) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
};
?>
<section class="admin-card">
  <h2>많이 본 페이지</h2>
  <?php $render($report['rows'] ?? [], '조건에 맞는 페이지가 없습니다.', true); ?>
</section>
<div class="stats-split stats-block">
  <section class="admin-card">
    <h2>들어온 첫 페이지</h2>
    <p class="stats-note">세션에서 가장 먼저 연 화면입니다.</p>
    <?php $render($report['entries'] ?? [], '첫 페이지 기록이 없습니다.'); ?>
  </section>
  <section class="admin-card">
    <h2>마지막으로 본 페이지</h2>
    <p class="stats-note">세션에서 마지막으로 연 화면입니다.</p>
    <?php $render($report['exits'] ?? [], '마지막 페이지 기록이 없습니다.'); ?>
  </section>
</div>
<section class="admin-card stats-block">
  <h2>영역별</h2>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>영역</th><th>페이지뷰</th><th>순방문자</th><th>평균 체류</th></tr></thead>
      <tbody>
      <?php if (empty($report['sections'])): ?>
        <tr><td colspan="4" class="empty">기록이 없습니다.</td></tr>
      <?php else: foreach ($report['sections'] as $row): ?>
        <tr>
          <td><?= e(\App\Services\AccessStatsService::sectionLabel((string) $row['section'])) ?></td>
          <td><?= number_format((int) $row['pv']) ?></td>
          <td><?= number_format((int) $row['uv']) ?></td>
          <td><?= e(stats_duration((int) round((float) ($row['avg_dwell'] ?? 0)))) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
