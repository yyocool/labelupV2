<?php
require view_path('admin/stats/_frame.php');
if ($statsError !== '') {
    return;
}
$report = $report ?? [];
$grid = $report['grid'] ?? [];
$max = max(1, (int) ($report['max'] ?? 0));
$names = [1 => '일', 2 => '월', 3 => '화', 4 => '수', 5 => '목', 6 => '금', 7 => '토'];
$order = [2, 3, 4, 5, 6, 7, 1];
$peak = $report['peak'] ?? null;
?>
<?php if (is_array($peak)): ?>
<p class="stats-peak">가장 많이 열린 시간은 <b><?= e($names[(int) $peak['dow']] ?? '') ?>요일 <?= sprintf('%02d', (int) $peak['hour']) ?>시</b> · 페이지뷰 <?= number_format((int) $peak['pv']) ?></p>
<?php endif; ?>
<section class="admin-card">
  <h2>요일 × 시간</h2>
  <div class="stats-heat-wrap">
    <table class="stats-heat">
      <thead>
        <tr>
          <th></th>
          <?php for ($hour = 0; $hour < 24; $hour++): ?>
          <th><?= $hour % 3 === 0 ? sprintf('%02d', $hour) : '' ?></th>
          <?php endfor; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($order as $dow): ?>
        <tr>
          <th><?= e($names[$dow]) ?></th>
          <?php for ($hour = 0; $hour < 24; $hour++): ?>
          <?php
            $pv = (int) ($grid[$dow][$hour] ?? 0);
            $alpha = $pv > 0 ? max(0.12, $pv / $max) : 0;
            $ink = $alpha > 0.55 ? '#fff' : '#2E2A27';
          ?>
          <td title="<?= e($names[$dow]) ?> <?= sprintf('%02d', $hour) ?>시 <?= number_format($pv) ?>" style="<?= $pv > 0 ? 'background:rgba(123,40,64,' . round($alpha, 3) . ');color:' . $ink : '' ?>"><?= $pv > 0 ? number_format($pv) : '' ?></td>
          <?php endfor; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<div class="stats-split stats-block">
  <section class="admin-card">
    <h2>시간 합계</h2>
    <table class="admin-table">
      <thead><tr><th>시</th><th>페이지뷰</th><th>순방문자</th></tr></thead>
      <tbody>
      <?php foreach ($report['by_hour'] ?? [] as $row): ?>
        <tr>
          <td><?= sprintf('%02d', (int) $row['hour']) ?>시</td>
          <td><?= number_format((int) $row['pv']) ?></td>
          <td><?= number_format((int) ($row['uv'] ?? 0)) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
  <section class="admin-card">
    <h2>요일 합계</h2>
    <table class="admin-table">
      <thead><tr><th>요일</th><th>페이지뷰</th><th>순방문자</th></tr></thead>
      <tbody>
      <?php
        $dowMap = [];
        foreach ($report['by_dow'] ?? [] as $row) {
            $dowMap[(int) $row['dow']] = $row;
        }
      ?>
      <?php foreach ($order as $dow): ?>
        <tr>
          <td><?= e($names[$dow]) ?></td>
          <td><?= number_format((int) ($dowMap[$dow]['pv'] ?? 0)) ?></td>
          <td><?= number_format((int) ($dowMap[$dow]['uv'] ?? 0)) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>
