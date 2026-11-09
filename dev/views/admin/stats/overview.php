<?php
require view_path('admin/stats/_frame.php');
if ($statsError !== '') {
    return;
}
$report = $report ?? [];
$s = $report['summary'] ?? [];
$p = $report['previous'] ?? [];
$cards = [
    ['페이지뷰', (int) ($s['pv'] ?? 0), (int) ($p['pv'] ?? 0), '화면을 연 횟수'],
    ['순방문자', (int) ($s['uv'] ?? 0), (int) ($p['uv'] ?? 0), '쿠키가 다른 방문자'],
    ['세션', (int) ($s['sessions'] ?? 0), (int) ($p['sessions'] ?? 0), '같은 브라우저 세션'],
    ['회원', (int) ($s['members'] ?? 0), (int) ($p['members'] ?? 0), '로그인한 회원 수'],
    ['페이지/세션', (float) ($s['pages_per_session'] ?? 0), (float) ($p['pages_per_session'] ?? 0), '세션당 본 화면'],
    ['이탈률', (float) ($s['bounce_rate'] ?? 0), (float) ($p['bounce_rate'] ?? 0), '화면 하나만 보고 떠난 세션'],
    ['평균 체류', (int) ($s['avg_dwell'] ?? 0), (int) ($p['avg_dwell'] ?? 0), '페이지를 떠나기까지'],
    ['오류', (int) ($s['errors'] ?? 0), (int) ($p['errors'] ?? 0), '상태 코드 400 이상'],
];
$daily = $report['daily'] ?? [];
$dailyMax = 1;
foreach ($daily as $row) {
    $dailyMax = max($dailyMax, (int) ($row['pv'] ?? 0));
}
$visitors = $report['visitors'] ?? [];
$visitorSum = (int) ($visitors['new_visitors'] ?? 0) + (int) ($visitors['returning_visitors'] ?? 0);
?>
<div class="stats-kpis">
  <?php foreach ($cards as $card): ?>
  <div class="admin-kpi">
    <div class="lbl"><?= e($card[0]) ?></div>
    <div class="val">
      <?php if ($card[0] === '이탈률'): ?>
        <?= e(number_format((float) $card[1], 1)) ?>%
      <?php elseif ($card[0] === '평균 체류'): ?>
        <?= e(stats_duration((int) $card[1])) ?>
      <?php elseif ($card[0] === '페이지/세션'): ?>
        <?= e(number_format((float) $card[1], 2)) ?>
      <?php else: ?>
        <?= number_format((float) $card[1]) ?>
      <?php endif; ?>
      <?= stats_delta_html($card[1], $card[2]) ?>
    </div>
    <div class="stats-kpi-sub"><?= e($card[3]) ?> · 이전 <?= e((string) ($report['prev_label'] ?? '')) ?></div>
  </div>
  <?php endforeach; ?>
</div>
<div class="stats-kpis stats-kpis--sub">
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">신규 방문자</div><div class="val"><?= number_format((int) ($visitors['new_visitors'] ?? 0)) ?> <small><?= e(stats_share((int) ($visitors['new_visitors'] ?? 0), $visitorSum)) ?></small></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">재방문</div><div class="val"><?= number_format((int) ($visitors['returning_visitors'] ?? 0)) ?> <small><?= e(stats_share((int) ($visitors['returning_visitors'] ?? 0), $visitorSum)) ?></small></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">회원 페이지뷰</div><div class="val"><?= number_format((int) ($s['member_pv'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">비회원 페이지뷰</div><div class="val"><?= number_format((int) ($s['guest_pv'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">봇 접속</div><div class="val"><?= number_format((int) ($report['bots'] ?? 0)) ?> <small><?= empty($filters['bots']) ? '집계 제외' : '포함 중' ?></small></div></div>
</div>

<div class="stats-split">
  <section class="admin-card">
    <h2>날짜별 페이지뷰</h2>
    <div class="stats-cols" aria-label="날짜별 페이지뷰">
      <?php foreach ($daily as $row): ?>
      <?php $h = max(2, (int) round(((int) $row['pv']) / $dailyMax * 100)); ?>
      <div class="stats-col" title="<?= e((string) $row['day']) ?> 페이지뷰 <?= number_format((int) $row['pv']) ?> / 순방문자 <?= number_format((int) ($row['uv'] ?? 0)) ?>">
        <i style="height:<?= $h ?>%"></i>
        <b><?= e(substr((string) $row['day'], 5)) ?></b>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="admin-card">
    <h2>시간대 분포</h2>
    <?php
      $hourMax = 1;
      foreach ($report['hourly'] ?? [] as $row) {
          $hourMax = max($hourMax, (int) ($row['pv'] ?? 0));
      }
    ?>
    <div class="stats-hours">
      <?php foreach ($report['hourly'] ?? [] as $row): ?>
      <div class="stats-hour">
        <span><?= sprintf('%02d', (int) $row['hour']) ?></span>
        <i style="width:<?= max(0, (int) round(((int) $row['pv']) / $hourMax * 100)) ?>%"></i>
        <em><?= number_format((int) $row['pv']) ?></em>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<div class="stats-split">
  <section class="admin-card">
    <h2>영역</h2>
    <?php $secMax = 1; foreach ($report['sections'] ?? [] as $row) { $secMax = max($secMax, (int) $row['pv']); } ?>
    <table class="admin-table">
      <thead><tr><th>영역</th><th>페이지뷰</th><th>순방문자</th><th>비중</th></tr></thead>
      <tbody>
      <?php if (empty($report['sections'])): ?>
        <tr><td colspan="4" class="empty">이 기간의 접속이 없습니다. 사이트를 열면 여기에 쌓입니다.</td></tr>
      <?php else: foreach ($report['sections'] as $row): ?>
        <tr>
          <td><?= e(\App\Services\AccessStatsService::sectionLabel((string) $row['section'])) ?></td>
          <td><?= number_format((int) $row['pv']) ?><div class="stats-inline"><i style="width:<?= (int) round(((int) $row['pv']) / $secMax * 100) ?>%"></i></div></td>
          <td><?= number_format((int) $row['uv']) ?></td>
          <td><?= e(stats_share((int) $row['pv'], (int) ($s['pv'] ?? 0))) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </section>
  <section class="admin-card">
    <h2>유입</h2>
    <table class="admin-table">
      <thead><tr><th>경로</th><th>페이지뷰</th><th>순방문자</th><th>비중</th></tr></thead>
      <tbody>
      <?php if (empty($report['channels'])): ?>
        <tr><td colspan="4" class="empty">유입 기록이 없습니다.</td></tr>
      <?php else: foreach ($report['channels'] as $row): ?>
        <tr>
          <td><?= e(\App\Services\AccessStatsService::channelLabel((string) $row['channel'])) ?></td>
          <td><?= number_format((int) $row['pv']) ?></td>
          <td><?= number_format((int) $row['uv']) ?></td>
          <td><?= e(stats_share((int) $row['pv'], (int) ($s['pv'] ?? 0))) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <h2 class="stats-subhead">기기</h2>
    <table class="admin-table">
      <thead><tr><th>기기</th><th>페이지뷰</th><th>순방문자</th></tr></thead>
      <tbody>
      <?php if (empty($report['devices'])): ?>
        <tr><td colspan="3" class="empty">기록이 없습니다.</td></tr>
      <?php else: foreach ($report['devices'] as $row): ?>
        <tr>
          <td><?= e(\App\Services\AccessStatsService::deviceLabel((string) $row['device'])) ?></td>
          <td><?= number_format((int) $row['pv']) ?></td>
          <td><?= number_format((int) $row['uv']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </section>
</div>

<section class="admin-card stats-block">
  <h2>많이 본 페이지</h2>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>페이지</th><th>제목</th><th>페이지뷰</th><th>순방문자</th><th>평균 체류</th></tr></thead>
      <tbody>
      <?php if (empty($report['pages'])): ?>
        <tr><td colspan="5" class="empty">아직 페이지 기록이 없습니다.</td></tr>
      <?php else: foreach ($report['pages'] as $row): ?>
        <tr>
          <td><code><?= e((string) $row['path_key']) ?></code></td>
          <td><?= e((string) ($row['title'] ?: '-')) ?></td>
          <td><?= number_format((int) $row['pv']) ?></td>
          <td><?= number_format((int) $row['uv']) ?></td>
          <td><?= e(stats_duration((int) round((float) ($row['avg_dwell'] ?? 0)))) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="admin-card stats-block">
  <h2>오류 페이지</h2>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>페이지</th><th>상태</th><th>횟수</th></tr></thead>
      <tbody>
      <?php if (empty($report['errors'])): ?>
        <tr><td colspan="3" class="empty">이 기간에 4xx·5xx 화면은 없습니다.</td></tr>
      <?php else: foreach ($report['errors'] as $row): ?>
        <tr>
          <td><code><?= e((string) $row['path_key']) ?></code></td>
          <td><span class="admin-badge admin-badge--err"><?= (int) $row['status_code'] ?></span></td>
          <td><?= number_format((int) $row['pv']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
