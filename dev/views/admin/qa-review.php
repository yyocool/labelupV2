<?php
/** @var array $user */
/** @var list<array<string,mixed>> $items */
/** @var array<string,int> $summary */
/** @var array<string,string> $statuses */
/** @var string $saveUrl */
/** @var string $uploadUrl */
$summary = $summary ?? [
    'total' => 0, 'dev_done' => 0, 'client_done' => 0, 'both_done' => 0,
    'issues' => 0, 'requests' => 0, 'user' => 0, 'admin' => 0,
];
$statuses = $statuses ?? \App\Services\QaReviewService::STATUSES;
$uploadUrl = $uploadUrl ?? url('api/admin/qa-review/upload-image');

$renderOptions = static function (string $selected) use ($statuses): string {
    $html = '';
    foreach ($statuses as $value => $label) {
        $sel = ((string) $value === (string) $selected) ? ' selected' : '';
        $html .= '<option value="' . e((string) $value) . '"' . $sel . '>' . e($label) . '</option>';
    }
    return $html;
};
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($pageTitle ?? '기능 검수 시트') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">
  <link rel="stylesheet" href="<?= css('qa-review.css') ?>">
</head>
<body class="qa-body">
  <header class="qa-top">
    <div class="qa-top__brand">
      <strong>LabelUp 기능 검수</strong>
      <span>개발자 / 고객사 공동 체크시트</span>
    </div>
    <div class="qa-top__meta">
      <span><?= e((string) ($user['name'] ?? '관리자')) ?></span>
      <span id="qaSaveStatus" class="qa-status">준비됨</span>
    </div>
  </header>

  <section class="qa-summary">
    <div class="qa-kpi"><em>전체</em><strong id="qaTotal"><?= (int) $summary['total'] ?></strong></div>
    <div class="qa-kpi"><em>사용자</em><strong><?= (int) $summary['user'] ?></strong></div>
    <div class="qa-kpi"><em>관리자</em><strong><?= (int) $summary['admin'] ?></strong></div>
    <div class="qa-kpi qa-kpi--dev"><em>개발자 완료</em><strong id="qaDevDone"><?= (int) $summary['dev_done'] ?></strong></div>
    <div class="qa-kpi qa-kpi--client"><em>고객사 완료</em><strong id="qaClientDone"><?= (int) $summary['client_done'] ?></strong></div>
    <div class="qa-kpi qa-kpi--both"><em>양쪽 완료</em><strong id="qaBothDone"><?= (int) $summary['both_done'] ?></strong></div>
    <div class="qa-kpi qa-kpi--issue"><em>이슈</em><strong id="qaIssues"><?= (int) $summary['issues'] ?></strong></div>
    <div class="qa-kpi qa-kpi--req"><em>요청사항</em><strong id="qaRequests"><?= (int) ($summary['requests'] ?? 0) ?></strong></div>
  </section>

  <section class="qa-toolbar">
    <div class="qa-filters" role="group" aria-label="영역 필터">
      <button type="button" class="is-active" data-filter="all">전체</button>
      <button type="button" data-filter="user">사용자 페이지</button>
      <button type="button" data-filter="admin">관리자 페이지</button>
      <button type="button" data-filter="pending">미완료</button>
      <button type="button" data-filter="issues">이슈</button>
      <button type="button" data-filter="requests">요청사항</button>
    </div>
    <label class="qa-search">
      <span>검색</span>
      <input type="search" id="qaSearch" placeholder="페이지·검수포인트·경로">
    </label>
  </section>

  <div class="qa-sheet-wrap">
    <table class="qa-sheet" id="qaSheet">
      <thead>
        <tr>
          <th class="qa-col-no">No</th>
          <th class="qa-col-area">영역</th>
          <th class="qa-col-group">그룹</th>
          <th class="qa-col-page">기능/페이지</th>
          <th class="qa-col-path">경로</th>
          <th class="qa-col-point">검수 포인트</th>
          <th class="qa-col-status">개발자</th>
          <th class="qa-col-status">고객사</th>
          <th class="qa-col-req">요청사항</th>
          <th class="qa-col-note">비고</th>
          <th class="qa-col-link">열기</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $row): ?>
        <?php
          $dev = (string) ($row['dev_status'] ?? '');
          $client = (string) ($row['client_status'] ?? '');
          $devDone = $dev === 'done';
          $clientDone = $client === 'done';
          $pending = !($devDone && $clientDone);
          $hasIssue = in_array($dev, ['error', 'fix', 'enhance'], true)
              || in_array($client, ['error', 'fix', 'enhance'], true);
          $hasRequest = !empty($row['has_request']);
          $areaLabel = ($row['area'] ?? '') === 'admin' ? '관리자' : '사용자';
          $href = !empty($row['open']) ? url(ltrim((string) $row['path'], '/')) : '';
          $rowClass = $devDone && $clientDone ? 'is-done' : ($hasIssue ? 'is-issue' : (($dev !== '' || $client !== '') ? 'is-partial' : ''));
          $requestHtml = (string) ($row['request_html'] ?? '');
        ?>
        <tr
          data-key="<?= e((string) $row['key']) ?>"
          data-area="<?= e((string) $row['area']) ?>"
          data-pending="<?= $pending ? '1' : '0' ?>"
          data-issue="<?= $hasIssue ? '1' : '0' ?>"
          data-request="<?= $hasRequest ? '1' : '0' ?>"
          data-label="<?= e((string) $row['label']) ?>"
          class="<?= e($rowClass) ?>"
        >
          <td class="qa-col-no"><?= $i + 1 ?></td>
          <td class="qa-col-area"><span class="qa-tag qa-tag--<?= e((string) $row['area']) ?>"><?= e($areaLabel) ?></span></td>
          <td class="qa-col-group"><?= e((string) $row['group']) ?></td>
          <td class="qa-col-page"><?= e((string) $row['label']) ?></td>
          <td class="qa-col-path"><code><?= e((string) $row['path']) ?></code></td>
          <td class="qa-col-point"><?= e((string) $row['point']) ?></td>
          <td class="qa-col-status">
            <select class="qa-status-select qa-status-select--dev" data-field="dev_status" data-status="<?= e($dev) ?>">
              <?= $renderOptions($dev) ?>
            </select>
          </td>
          <td class="qa-col-status">
            <select class="qa-status-select qa-status-select--client" data-field="client_status" data-status="<?= e($client) ?>">
              <?= $renderOptions($client) ?>
            </select>
          </td>
          <td class="qa-col-req">
            <button
              type="button"
              class="qa-req-btn<?= $hasRequest ? ' has-content' : '' ?>"
              data-open-request
              aria-label="요청사항 작성"
            ><?= $hasRequest ? '보기/수정' : '작성' ?></button>
            <script type="application/json" class="qa-req-json"><?= json_encode($requestHtml, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?></script>
          </td>
          <td class="qa-col-note">
            <input type="text" data-field="note" maxlength="500" value="<?= e((string) ($row['note'] ?? '')) ?>" placeholder="짧은 메모">
          </td>
          <td class="qa-col-link">
            <?php if ($href !== ''): ?>
            <a href="<?= e($href) ?>" target="_blank" rel="noopener">열기</a>
            <?php else: ?>
            <span class="qa-muted">샘플</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="qa-modal" id="qaRequestModal" hidden>
    <div class="qa-modal__backdrop" data-close-request></div>
    <div class="qa-modal__panel" role="dialog" aria-modal="true" aria-labelledby="qaRequestTitle">
      <header class="qa-modal__head">
        <div>
          <h2 id="qaRequestTitle">요청사항</h2>
          <p id="qaRequestMeta"></p>
        </div>
        <button type="button" class="qa-modal__close" data-close-request aria-label="닫기">×</button>
      </header>
      <div class="qa-modal__body">
        <textarea id="qaRequestEditor"></textarea>
        <p class="qa-modal__hint">캡처 이미지를 붙여넣기(Ctrl+V)하면 서버에 업로드되어 바로 표시됩니다.</p>
      </div>
      <footer class="qa-modal__foot">
        <button type="button" class="qa-btn" data-close-request>취소</button>
        <button type="button" class="qa-btn qa-btn--primary" id="qaRequestSave">저장</button>
      </footer>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-ko-KR.min.js"></script>
  <script>
    window.QA_REVIEW = {
      saveUrl: <?= json_encode($saveUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
      uploadUrl: <?= json_encode($uploadUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };
  </script>
  <script src="<?= js('qa-review.js') ?>"></script>
</body>
</html>
