<?php
$matrix = $matrix ?? ['rows' => [], 'category_count' => 0, 'group_count' => 0, 'product_count' => 0, 'generated_qr_count' => 0, 'printed_qr_count' => 0];
$rows = $matrix['rows'] ?? [];
$loadError = $loadError ?? null;
$history = $history ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$histItems = $history['items'] ?? [];
$histTotal = (int) ($history['total'] ?? 0);
$histPage = (int) ($history['page'] ?? 1);
$histPages = (int) ($history['pages'] ?? 1);
$creditSaveUrl = url('api/admin/credit/purchase-group/credit/save');
$usageUrl = url('api/admin/credit/purchase-group/usage-history');
$qrCouponsUrl = url('admin/qr-coupons');
?>
<div class="admin-head">
  <div>
    <h1>구매크레딧</h1>
    <p>QR쿠폰관리와 동일한 그룹 기준으로 지급 크레딧을 설정하고, 회원에게 지급된 이력을 확인합니다.</p>
  </div>
  <div class="admin-head-actions">
    <a class="admin-btn" href="<?= e($qrCouponsUrl) ?>">QR쿠폰관리 바로가기</a>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<?php if ($loadError): ?>
<div class="admin-alert is-error" style="display:block"><?= e($loadError) ?></div>
<?php endif; ?>

<p class="admin-meta-line">
  제품분류 <b><?= (int) ($matrix['category_count'] ?? 0) ?></b>개
  · QR그룹 <b><?= (int) ($matrix['group_count'] ?? 0) ?></b>개
  · 생성 QR <b><?= number_format((int) ($matrix['generated_qr_count'] ?? 0)) ?></b>개
  · 지급(사용) <b><?= number_format($histTotal) ?></b>건
</p>

<section class="admin-section">
  <h2 class="admin-section-title">그룹별 지급 크레딧 설정</h2>
  <p class="admin-muted" style="margin-top:-4px;margin-bottom:10px">여기서 저장한 지급 크레딧은 <b>QR쿠폰관리</b>에도 즉시 동일하게 반영됩니다.</p>
  <div class="admin-table-wrap qr-group-wrap">
    <table class="admin-table qr-group-table">
      <thead>
        <tr>
          <th class="qr-col-catno">분류No.</th>
          <th class="qr-col-groupno">그룹No.</th>
          <th class="qr-col-name">제품 분류</th>
          <th class="qr-col-sheets">매수/팩</th>
          <th class="qr-col-price">정상 소비자가</th>
          <th class="qr-col-credit">지급크레딧</th>
          <th class="qr-col-qrcount">생성QR</th>
          <th class="qr-col-actions">이력</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="empty">등록된 QR 그룹이 없습니다. 마이그레이션을 실행해 주세요.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
        <tr data-group-no="<?= (int) $row['group_no'] ?>">
          <?php if (!empty($row['show_category'])): ?>
          <td class="qr-catno" rowspan="<?= (int) $row['category_rowspan'] ?>" style="background:<?= e((string) $row['color_hex']) ?>">
            <?= (int) $row['category_no'] ?>
          </td>
          <?php endif; ?>
          <td class="qr-groupno"><strong><?= (int) $row['group_no'] ?></strong></td>
          <td class="qr-name"><?= e((string) $row['category_name']) ?></td>
          <td class="qr-sheets"><?= number_format((int) $row['sheets_per_pack']) ?>매</td>
          <td class="qr-price"><?= number_format((int) $row['list_price']) ?>원</td>
          <td class="qr-credit">
            <?php
              $creditVal = $row['credit_amount'] ?? null;
              $creditStr = ($creditVal === null || $creditVal === '') ? '' : (string) (int) $creditVal;
            ?>
            <input
              type="number"
              class="admin-input qr-credit-input js-pc-credit"
              min="0"
              step="1"
              inputmode="numeric"
              placeholder="0"
              value="<?= e($creditStr) ?>"
              data-group-no="<?= (int) $row['group_no'] ?>"
              data-prev="<?= e($creditStr) ?>"
              aria-label="QR 그룹 <?= (int) $row['group_no'] ?> 지급크레딧"
            >
          </td>
          <td class="qr-qrcount"><?= number_format((int) ($row['generated_qr_count'] ?? 0)) ?>개</td>
          <td class="qr-actions">
            <button type="button" class="admin-btn admin-btn--sm js-pc-usage" data-group-no="<?= (int) $row['group_no'] ?>">지급이력</button>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="admin-section">
  <div class="admin-section-head">
    <h2 class="admin-section-title">전체 지급 이력</h2>
    <form class="admin-toolbar admin-toolbar--inline" method="get" action="<?= url('admin/ops/purchase-credits') ?>">
      <input class="admin-input" type="search" name="q" value="<?= e($search ?? '') ?>" placeholder="쿠폰번호, 분류, 회원 검색">
      <button class="admin-btn admin-btn--primary" type="submit">검색</button>
      <?php if (!empty($search)): ?>
      <a class="admin-btn" href="<?= url('admin/ops/purchase-credits') ?>">초기화</a>
      <?php endif; ?>
    </form>
  </div>
  <p class="admin-muted">총 <?= number_format($histTotal) ?>건 · QR 쿠폰 사용(지급) 기준</p>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>쿠폰번호</th>
          <th>그룹</th>
          <th>제품 분류</th>
          <th>매수</th>
          <th>지급 크레딧</th>
          <th>회원</th>
          <th>지급일시</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($histItems)): ?>
        <tr><td colspan="7" class="empty">지급 이력이 없습니다.</td></tr>
      <?php else: ?>
        <?php foreach ($histItems as $row): ?>
        <tr>
          <td><code><?= e((string) ($row['code'] ?? '')) ?></code></td>
          <td><strong><?= (int) ($row['group_no'] ?? 0) ?></strong></td>
          <td><?= e((string) ($row['category_name'] ?? '-')) ?></td>
          <td><?= number_format((int) ($row['sheets_per_pack'] ?? 0)) ?>매</td>
          <td>
            <?php if (($row['credit_amount'] ?? null) === null): ?>
            <span class="admin-muted">미설정</span>
            <?php else: ?>
            <strong><?= number_format((int) $row['credit_amount']) ?> C</strong>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($row['used_by'])): ?>
            <a href="<?= url('admin/users/' . (int) $row['used_by']) ?>"><?= e($row['used_by_name'] ?: ($row['used_by_email'] ?: ('#' . (int) $row['used_by']))) ?></a>
            <?php if (!empty($row['used_by_email'])): ?>
            <br><small class="admin-muted"><?= e((string) $row['used_by_email']) ?></small>
            <?php endif; ?>
            <?php else: ?>
            <span class="admin-muted">-</span>
            <?php endif; ?>
          </td>
          <td><?= e(substr((string) ($row['used_at'] ?? ''), 0, 16)) ?></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($histPages > 1): ?>
  <?php
    $page = $histPage;
    $pages = $histPages;
    $basePath = 'admin/ops/purchase-credits';
    $queryParams = ['q' => $search ?? ''];
    require view_path('admin/partials/pagination.php');
  ?>
  <?php endif; ?>
</section>

<div class="admin-modal" id="pcUsageModal" hidden>
  <div class="admin-modal-backdrop" data-close="pcUsageModal"></div>
  <div class="admin-modal-dialog admin-modal-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="pcUsageTitle">
    <div class="admin-modal-head">
      <h2 id="pcUsageTitle">그룹 지급이력</h2>
      <button type="button" class="admin-modal-close" data-close="pcUsageModal" aria-label="닫기">×</button>
    </div>
    <div class="admin-modal-body">
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>쿠폰번호</th>
              <th>지급 크레딧</th>
              <th>회원</th>
              <th>지급일시</th>
            </tr>
          </thead>
          <tbody id="pcUsageBody">
            <tr><td colspan="4" class="empty">불러오는 중…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var creditSaveUrl = <?= json_encode($creditSaveUrl, JSON_UNESCAPED_SLASHES) ?>;
  var usageUrl = <?= json_encode($usageUrl, JSON_UNESCAPED_SLASHES) ?>;

  function showAlert(msg, type) {
    if (typeof showAdminAlert === 'function') {
      showAdminAlert(msg, type || 'success');
      return;
    }
    var el = document.getElementById('adminAlert');
    if (!el) return;
    el.style.display = 'block';
    el.className = 'admin-alert ' + (type === 'error' ? 'is-error' : 'is-ok');
    el.textContent = msg;
  }
  function openModal(id) {
    var el = document.getElementById(id);
    if (el) el.hidden = false;
  }
  function closeModal(id) {
    var el = document.getElementById(id);
    if (el) el.hidden = true;
  }
  document.querySelectorAll('[data-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      closeModal(btn.getAttribute('data-close') || '');
    });
  });

  function normalizeCredit(raw) {
    var text = String(raw == null ? '' : raw).trim();
    if (text === '') return '';
    var num = Number(text);
    if (!Number.isFinite(num) || num < 0 || Math.floor(num) !== num) {
      throw new Error('지급 크레딧은 0 이상의 정수로 입력해 주세요.');
    }
    return String(num);
  }

  async function saveCredit(input) {
    var groupNo = Number(input.getAttribute('data-group-no') || 0);
    var prev = input.getAttribute('data-prev') || '';
    var next;
    try {
      next = normalizeCredit(input.value);
    } catch (err) {
      showAlert(err.message, 'error');
      input.value = prev;
      return;
    }
    if (next === prev) return;
    input.disabled = true;
    input.classList.add('is-saving');
    try {
      await AdminAPI.post(creditSaveUrl, {
        group_no: groupNo,
        credit_amount: next === '' ? null : Number(next)
      });
      input.value = next;
      input.setAttribute('data-prev', next);
      input.classList.remove('is-saving');
      input.classList.add('is-saved');
      setTimeout(function () { input.classList.remove('is-saved'); }, 900);
      showAlert('QR 그룹 ' + groupNo + ' 지급 크레딧이 저장되었습니다. (QR쿠폰관리에도 동일 적용)', 'success');
    } catch (err) {
      input.value = prev;
      showAlert(err.message || '저장 실패', 'error');
    } finally {
      input.disabled = false;
      input.classList.remove('is-saving');
    }
  }

  document.querySelectorAll('.js-pc-credit').forEach(function (input) {
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        input.blur();
      }
    });
    input.addEventListener('blur', function () { saveCredit(input); });
  });

  document.querySelectorAll('.js-pc-usage').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      var groupNo = Number(btn.getAttribute('data-group-no') || 0);
      openModal('pcUsageModal');
      document.getElementById('pcUsageTitle').textContent = '그룹 ' + groupNo + ' 지급이력';
      var body = document.getElementById('pcUsageBody');
      body.innerHTML = '<tr><td colspan="4" class="empty">불러오는 중…</td></tr>';
      try {
        var res = await AdminAPI.post(usageUrl, { group_no: groupNo });
        var items = (res.data && res.data.items) || [];
        if (!items.length) {
          body.innerHTML = '<tr><td colspan="4" class="empty">지급 이력이 없습니다.</td></tr>';
          return;
        }
        body.innerHTML = items.map(function (it) {
          var who = it.used_by_name || it.used_by_email || (it.used_by ? ('#' + it.used_by) : '-');
          var credit = (it.credit_amount == null || it.credit_amount === '')
            ? '<span class="admin-muted">미설정</span>'
            : ('<strong>' + Number(it.credit_amount).toLocaleString() + ' C</strong>');
          return '<tr><td><code>' + (it.code || '-') + '</code></td><td>' + credit + '</td><td>' + who + '</td><td>' + (it.used_at || '-') + '</td></tr>';
        }).join('');
      } catch (err) {
        body.innerHTML = '<tr><td colspan="4" class="empty">' + (err.message || '조회 실패') + '</td></tr>';
      }
    });
  });
})();
</script>
