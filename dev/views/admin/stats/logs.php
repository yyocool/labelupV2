<?php
require view_path('admin/stats/_frame.php');
if ($statsError !== '') {
    return;
}
$report = $report ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$page = (int) ($report['page'] ?? 1);
$pages = (int) ($report['pages'] ?? 1);
$exportQuery = $statsQuery;
?>
<div class="admin-head-actions stats-export">
  <a class="admin-btn" href="<?= e(url('admin/stats/logs/export') . '?' . http_build_query($exportQuery)) ?>">CSV 내려받기</a>
  <span class="stats-note">총 <?= number_format((int) ($report['total'] ?? 0)) ?>건 · CSV는 최대 5,000건</span>
</div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>시간</th>
        <th>영역</th>
        <th>페이지</th>
        <th>대상</th>
        <th>기기</th>
        <th>환경</th>
        <th>유입</th>
        <th>체류</th>
        <th>상태</th>
        <th>IP</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($report['items'])): ?>
      <tr><td colspan="10" class="empty">조건에 맞는 접속 로그가 없습니다.</td></tr>
    <?php else: foreach ($report['items'] as $row): ?>
      <?php
        $who = match ((string) ($row['actor_role'] ?? '')) {
            'member' => mask_person_name((string) ($row['name'] ?? '')) . ' · ' . mask_email_id((string) ($row['email'] ?? '')),
            'admin' => '관리자',
            default => '비회원',
        };
        $status = (int) ($row['status_code'] ?? 0);
      ?>
      <tr>
        <td><small><?= e(substr((string) ($row['visited_at'] ?? ''), 0, 16)) ?></small></td>
        <td><?= e(\App\Services\AccessStatsService::sectionLabel((string) ($row['section'] ?? ''))) ?></td>
        <td>
          <code><?= e((string) ($row['path'] ?? '')) ?></code>
          <?php if (!empty($row['title'])): ?><div class="stats-title"><?= e((string) $row['title']) ?></div><?php endif; ?>
          <?php if (!empty($row['query_string'])): ?><div class="stats-title">?<?= e((string) $row['query_string']) ?></div><?php endif; ?>
        </td>
        <td>
          <?php if ((string) ($row['actor_role'] ?? '') === 'member' && (int) ($row['user_id'] ?? 0) > 0): ?>
          <a href="<?= url('admin/users/' . (int) $row['user_id']) ?>"><?= e($who) ?></a>
          <?php else: ?>
          <?= e($who) ?>
          <?php endif; ?>
          <?php if (!empty($row['is_bot'])): ?><div class="stats-title">봇</div><?php endif; ?>
        </td>
        <td><?= e(\App\Services\AccessStatsService::deviceLabel((string) ($row['device'] ?? ''))) ?></td>
        <td><?= e((string) ($row['browser_name'] ?? '')) ?> · <?= e((string) ($row['os_name'] ?? '')) ?><?php if (!empty($row['screen'])): ?><div class="stats-title"><?= e((string) $row['screen']) ?><?= !empty($row['viewport']) ? ' / ' . e((string) $row['viewport']) : '' ?></div><?php endif; ?></td>
        <td>
          <?= e((string) (($row['referer_host'] ?? '') !== '' ? $row['referer_host'] : '직접')) ?>
          <?php if (!empty($row['utm_source'])): ?><div class="stats-title"><?= e((string) $row['utm_source']) ?> / <?= e((string) ($row['utm_medium'] ?? '')) ?></div><?php endif; ?>
        </td>
        <td><?= e(stats_duration((int) ($row['dwell_ms'] ?? 0))) ?></td>
        <td><?= $status >= 400 ? '<span class="admin-badge admin-badge--err">' . $status . '</span>' : ($status > 0 ? (string) $status : '-') ?></td>
        <td><?= e((string) ($row['ip'] ?? '')) ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php
$basePath = 'admin/stats/logs';
$queryParams = $statsQuery;
require view_path('admin/partials/pagination.php');
?>
