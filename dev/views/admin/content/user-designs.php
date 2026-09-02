<?php
use App\Services\UserAiClipartService;
/** @var array{items:array,total:int,page:int,pages:int} $list */
/** @var array{total:int,pending:int,approved:int,rejected:int,approved_month:int,users:int} $stats */
/** @var array{q:string,user_id:int,status:string,date_from:string,date_to:string} $filters */
/** @var array<int,string> $rejectReasons */
$list = $list ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$stats = $stats ?? ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'approved_month' => 0, 'users' => 0];
$filters = $filters ?? ['q' => '', 'user_id' => 0, 'status' => 'pending', 'date_from' => '', 'date_to' => ''];
$rejectReasons = $rejectReasons ?? UserAiClipartService::rejectReasons();
$status = (string) ($filters['status'] ?? 'pending');
$tabs = [
    'pending' => ['검수 대기', (int) ($stats['pending'] ?? 0)],
    'approved' => ['승인', (int) ($stats['approved'] ?? 0)],
    'rejected' => ['반려', (int) ($stats['rejected'] ?? 0)],
    '' => ['전체', (int) ($stats['total'] ?? 0)],
];
$queryBase = [
    'q' => $filters['q'] ?? '',
    'user_id' => !empty($filters['user_id']) ? (int) $filters['user_id'] : '',
    'date_from' => $filters['date_from'] ?? '',
    'date_to' => $filters['date_to'] ?? '',
];
$tabUrl = static function (string $st) use ($queryBase): string {
    $q = array_filter($queryBase, static fn ($v) => $v !== '' && $v !== null);
    if ($st !== '') {
        $q['status'] = $st;
    }
    return url('admin/content/user-designs' . ($q ? ('?' . http_build_query($q)) : ''));
};
$badgeClass = static function (string $st): string {
    return match ($st) {
        'approved' => 'admin-badge--ok',
        'rejected' => 'admin-badge--err',
        default => 'admin-badge--pending',
    };
};
?>
<div class="admin-head">
  <div>
    <h1>사용자 디자인 검수</h1>
    <p>검수 대기 <?= number_format((int) ($stats['pending'] ?? 0)) ?> · 반려 <?= number_format((int) ($stats['rejected'] ?? 0)) ?></p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary" id="udBatchApprove">선택 승인</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<div class="admin-kpis">
  <div class="admin-kpi"><div class="lbl">전체 디자인</div><div class="val"><?= number_format((int) ($stats['total'] ?? 0)) ?></div><div class="delta">누적</div></div>
  <div class="admin-kpi"><div class="lbl">검수 대기</div><div class="val"><?= number_format((int) ($stats['pending'] ?? 0)) ?></div><div class="delta down">검수 필요</div></div>
  <div class="admin-kpi"><div class="lbl">반려</div><div class="val"><?= number_format((int) ($stats['rejected'] ?? 0)) ?></div><div class="delta down">우선 처리</div></div>
  <div class="admin-kpi"><div class="lbl">이달 승인</div><div class="val"><?= number_format((int) ($stats['approved_month'] ?? 0)) ?></div><div class="delta">이용 회원 <?= number_format((int) ($stats['users'] ?? 0)) ?></div></div>
</div>

<nav class="admin-order-tabs" aria-label="검수 상태">
  <?php foreach ($tabs as $key => [$label, $cnt]): ?>
  <a class="admin-order-tab<?= $status === $key ? ' is-active' : '' ?>" href="<?= e($tabUrl((string) $key)) ?>">
    <?= e($label) ?> <b><?= number_format($cnt) ?></b>
  </a>
  <?php endforeach; ?>
</nav>

<form class="admin-filter-bar" method="get" action="<?= url('admin/content/user-designs') ?>">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <?php if ((int) ($filters['user_id'] ?? 0) > 0): ?>
  <input type="hidden" name="user_id" value="<?= (int) $filters['user_id'] ?>">
  <?php endif; ?>
  <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="제목, 프롬프트, 작성자, 이메일" class="admin-input admin-input--search">
  <input class="admin-input" type="date" name="date_from" value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
  <input class="admin-input" type="date" name="date_to" value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
  <button type="submit" class="admin-btn admin-btn--primary">조회</button>
  <?php if ((int) ($filters['user_id'] ?? 0) > 0): ?>
  <span class="admin-check">회원 #<?= (int) $filters['user_id'] ?></span>
  <a class="admin-btn" href="<?= url('admin/content/user-designs') ?>">전체 보기</a>
  <?php endif; ?>
</form>

<div class="admin-grid admin-grid--2-1 ud-layout">
  <div>
    <div class="admin-table-wrap">
      <table class="admin-table" id="udTable">
        <thead>
          <tr>
            <th class="ud-check"><input type="checkbox" id="udCheckAll" aria-label="전체 선택"></th>
            <th>디자인</th>
            <th>작성자</th>
            <th>유형</th>
            <th>상태</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($list['items'])): ?>
          <tr><td colspan="5" class="empty">해당 조건의 사용자 디자인이 없습니다.</td></tr>
        <?php else: ?>
          <?php foreach ($list['items'] as $i => $row): ?>
          <?php $st = (string) ($row['review_status'] ?? 'pending'); ?>
          <tr class="ud-row<?= $i === 0 ? ' is-active' : '' ?>" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>
            <td class="ud-check"><input type="checkbox" class="js-ud-check" value="<?= (int) $row['id'] ?>"></td>
            <td>
              <button type="button" class="ud-name js-ud-select">
                <img class="admin-thumb admin-thumb--square" src="<?= e((string) ($row['image_url'] ?? '')) ?>" alt="">
                <span>
                  <strong><?= e((string) ($row['title'] ?? '라비가 그린 디자인')) ?></strong>
                  <small><?= e(substr((string) ($row['created_at'] ?? ''), 0, 16)) ?></small>
                </span>
              </button>
            </td>
            <td><?= e((string) ($row['user_name'] ?: $row['email'] ?: '회원')) ?></td>
            <td><span class="admin-badge admin-badge--pending"><?= e((string) ($row['kind_label'] ?? 'AI 생성')) ?></span></td>
            <td><span class="admin-badge <?= e($badgeClass($st)) ?>"><?= e((string) ($row['review_status_label'] ?? '대기')) ?></span></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (($list['pages'] ?? 1) > 1): ?>
    <?php
      $page = (int) ($list['page'] ?? 1);
      $pages = (int) ($list['pages'] ?? 1);
      $basePath = 'admin/content/user-designs';
      $queryParams = [
        'q' => $filters['q'] ?? '',
        'user_id' => !empty($filters['user_id']) ? (int) $filters['user_id'] : '',
        'status' => $status,
        'date_from' => $filters['date_from'] ?? '',
        'date_to' => $filters['date_to'] ?? '',
      ];
      require view_path('admin/partials/pagination.php');
    ?>
    <?php endif; ?>
  </div>

  <aside class="admin-panel ud-panel" id="udPanel">
    <div class="admin-panel-head"><h4 id="udPanelTitle">검수</h4></div>
    <div class="ud-empty" id="udPanelEmpty"<?= empty($list['items']) ? '' : ' hidden' ?>>왼쪽 목록에서 디자인을 선택하세요.</div>
    <div id="udPanelBody"<?= empty($list['items']) ? ' hidden' : '' ?>>
      <img id="udPreviewImg" class="ud-preview" alt="">
      <div class="admin-form-grid" style="margin-top:12px">
        <div class="admin-field"><label>작성자</label><div class="ud-box" id="udAuthor"></div></div>
        <div class="admin-field"><label>생성일</label><div class="ud-box" id="udCreated"></div></div>
        <div class="admin-field" style="grid-column:1/-1"><label>프롬프트</label><div class="ud-box ud-box--area" id="udPrompt"></div></div>
        <div class="admin-field" style="grid-column:1/-1">
          <label for="udReason">반려 사유 템플릿</label>
          <select id="udReason" class="admin-input">
            <option value="">선택 또는 직접 입력</option>
            <?php foreach ($rejectReasons as $reason): ?>
            <option value="<?= e($reason) ?>"><?= e($reason) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="admin-field" style="grid-column:1/-1">
          <label for="udNote">검수 의견</label>
          <textarea id="udNote" class="admin-input" rows="3" placeholder="승인·반려 의견을 남깁니다."></textarea>
        </div>
      </div>
      <div class="admin-head-actions ud-panel-actions">
        <button type="button" class="admin-btn admin-btn--danger" id="udReject">반려</button>
        <button type="button" class="admin-btn admin-btn--primary" id="udApprove">승인</button>
      </div>
      <div class="admin-head-actions ud-panel-actions">
        <a class="admin-btn" id="udUserLink" href="#">회원</a>
        <a class="admin-btn" id="udEditorLink" href="#" target="_blank" rel="noopener">편집기</a>
        <button type="button" class="admin-btn admin-btn--danger" id="udDelete">삭제</button>
      </div>
    </div>
  </aside>
</div>

<script>
window.LABELUP_USER_DESIGN_ADMIN = {
  urls: {
    review: <?= json_encode(url('api/admin/content/user-design/review'), JSON_UNESCAPED_SLASHES) ?>,
    approveBatch: <?= json_encode(url('api/admin/content/user-design/approve-batch'), JSON_UNESCAPED_SLASHES) ?>,
    delete: <?= json_encode(url('api/admin/content/user-design/delete'), JSON_UNESCAPED_SLASHES) ?>
  }
};
</script>
<script src="<?= js('user-designs-admin.js') ?>"></script>
