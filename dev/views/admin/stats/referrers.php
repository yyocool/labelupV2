<?php
require view_path('admin/stats/_frame.php');
if ($statsError !== '') {
    return;
}
$report = $report ?? [];
$channelPv = 0;
foreach ($report['channels'] ?? [] as $row) {
    $channelPv += (int) $row['pv'];
}
?>
<section class="admin-card">
  <h2>유입 종류</h2>
  <div class="stats-kpis">
    <?php if (empty($report['channels'])): ?>
    <p class="stats-note">이 기간의 유입이 없습니다.</p>
    <?php else: foreach ($report['channels'] as $row): ?>
    <div class="admin-kpi">
      <div class="lbl"><?= e(\App\Services\AccessStatsService::channelLabel((string) $row['channel'])) ?></div>
      <div class="val"><?= number_format((int) $row['pv']) ?></div>
      <div class="stats-kpi-sub">순방문자 <?= number_format((int) $row['uv']) ?> · <?= e(stats_share((int) $row['pv'], $channelPv)) ?></div>
    </div>
    <?php endforeach; endif; ?>
  </div>
</section>
<div class="stats-split stats-block">
  <section class="admin-card">
    <h2>검색엔진</h2>
    <table class="admin-table">
      <thead><tr><th>엔진</th><th>페이지뷰</th><th>순방문자</th></tr></thead>
      <tbody>
      <?php if (empty($report['engines'])): ?>
        <tr><td colspan="3" class="empty">검색 유입이 없습니다.</td></tr>
      <?php else: foreach ($report['engines'] as $row): ?>
        <tr>
          <td><?= e((string) $row['engine']) ?></td>
          <td><?= number_format((int) $row['pv']) ?></td>
          <td><?= number_format((int) $row['uv']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </section>
  <section class="admin-card">
    <h2>외부에서 도착한 페이지</h2>
    <table class="admin-table">
      <thead><tr><th>랜딩</th><th>페이지뷰</th><th>순방문자</th></tr></thead>
      <tbody>
      <?php if (empty($report['landings'])): ?>
        <tr><td colspan="3" class="empty">외부 유입 랜딩이 없습니다. 주소창에 직접 친 방문은 여기 들어가지 않습니다.</td></tr>
      <?php else: foreach ($report['landings'] as $row): ?>
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
<section class="admin-card stats-block">
  <h2>유입 사이트</h2>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>사이트</th><th>페이지뷰</th><th>순방문자</th><th>세션</th></tr></thead>
      <tbody>
      <?php if (empty($report['hosts'])): ?>
        <tr><td colspan="4" class="empty">기록이 없습니다.</td></tr>
      <?php else: foreach ($report['hosts'] as $row): ?>
        <tr>
          <td><?= e((string) ($row['referer_host'] !== '' ? $row['referer_host'] : '직접 방문 · 사이트 안 이동')) ?></td>
          <td><?= number_format((int) $row['pv']) ?></td>
          <td><?= number_format((int) $row['uv']) ?></td>
          <td><?= number_format((int) $row['sessions']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
<section class="admin-card stats-block">
  <h2>광고·캠페인 파라미터</h2>
  <p class="stats-note">주소의 utm_source, utm_medium, utm_campaign 과 구글 gclid, 페이스북 fbclid 를 모았습니다.</p>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>소스</th><th>매체</th><th>캠페인</th><th>페이지뷰</th><th>순방문자</th></tr></thead>
      <tbody>
      <?php if (empty($report['utm'])): ?>
        <tr><td colspan="5" class="empty">캠페인 파라미터가 있는 접속이 없습니다.</td></tr>
      <?php else: foreach ($report['utm'] as $row): ?>
        <tr>
          <td><?= e((string) ($row['utm_source'] ?: '-')) ?></td>
          <td><?= e((string) ($row['utm_medium'] ?: '-')) ?></td>
          <td><?= e((string) ($row['utm_campaign'] ?: '-')) ?></td>
          <td><?= number_format((int) $row['pv']) ?></td>
          <td><?= number_format((int) $row['uv']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
