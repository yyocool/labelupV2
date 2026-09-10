<?php
$matrix = $matrix ?? ['rows' => [], 'category_count' => 0, 'group_count' => 0, 'product_count' => 0, 'generated_qr_count' => 0, 'coupon_page_url' => ''];
$rows = $matrix['rows'] ?? [];
$loadError = $loadError ?? null;
$creditSaveUrl = url('api/admin/qr-coupons/credit/save');
$generateUrl = url('api/admin/qr-coupons/generate');
$historyUrl = url('api/admin/qr-coupons/generation-history');
$usageUrl = url('api/admin/qr-coupons/usage-history');
$couponPageUrl = (string) ($matrix['coupon_page_url'] ?? absolute_url('qr-coupon'));
$couponPreviewUrl = absolute_url('qr-coupon') . '?preview=1';
?>
<div class="admin-head">
  <div>
    <h1>QR쿠폰관리</h1>
    <p>무료배포 QR 그룹 체계 — 제품분류 · 매수/팩 · 정상 소비자가 기준</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary" id="qrPreviewBtn">쿠폰페이지 미리보기</button>
  </div>
</div>

<?php if ($loadError): ?>
<div class="admin-alert is-error" style="display:block"><?= e($loadError) ?></div>
<?php endif; ?>

<p class="admin-meta-line">
  제품분류 <b><?= (int) ($matrix['category_count'] ?? 0) ?></b>개
  · QR그룹 <b><?= (int) ($matrix['group_count'] ?? 0) ?></b>개
  · 연결 상품 <b><?= number_format((int) ($matrix['product_count'] ?? 0)) ?></b>개
  · 생성 QR <b><?= number_format((int) ($matrix['generated_qr_count'] ?? 0)) ?></b>개
</p>

<div class="admin-table-wrap qr-group-wrap">
  <div class="qr-group-title">무료배포 QR 그룹 체계</div>
  <table class="admin-table qr-group-table">
    <thead>
      <tr>
        <th class="qr-col-catno">제품 분류No.</th>
        <th class="qr-col-groupno">QR 그룹No.</th>
        <th class="qr-col-name">제품 분류</th>
        <th class="qr-col-sheets">매수/팩</th>
        <th class="qr-col-price">정상 소비자가</th>
        <th class="qr-col-credit">지급크레딧</th>
        <th class="qr-col-products">상품수</th>
        <th class="qr-col-qrcount">생성QR수</th>
        <th class="qr-col-actions">관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="9" class="empty">등록된 QR 그룹이 없습니다. 마이그레이션을 실행해 주세요.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $row): ?>
      <?php
        $landingUrl = (string) ($row['coupon_page_url'] ?? '');
        $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=96x96&margin=8&data=' . rawurlencode($landingUrl);
      ?>
      <tr data-group-no="<?= (int) $row['group_no'] ?>"
          data-category-name="<?= e((string) $row['category_name']) ?>"
          data-category-slug="<?= e((string) $row['category_slug']) ?>"
          data-sheets="<?= (int) $row['sheets_per_pack'] ?>"
          data-coupon-url="<?= e($landingUrl) ?>"
          data-generated="<?= (int) ($row['generated_qr_count'] ?? 0) ?>">
        <?php if (!empty($row['show_category'])): ?>
        <td class="qr-catno" rowspan="<?= (int) $row['category_rowspan'] ?>" style="background:<?= e((string) $row['color_hex']) ?>">
          <?= (int) $row['category_no'] ?>
        </td>
        <?php endif; ?>
        <td class="qr-groupno">
          <div class="qr-groupno-wrap">
            <strong><?= (int) $row['group_no'] ?></strong>
            <?php if ($landingUrl !== ''): ?>
            <a class="qr-group-qr" href="<?= e($landingUrl) ?>" target="_blank" rel="noopener" title="<?= e($landingUrl) ?>">
              <img src="<?= e($qrImg) ?>" alt="QR 그룹 <?= (int) $row['group_no'] ?>" width="72" height="72" loading="lazy">
            </a>
            <small class="qr-group-qr-meta"><?= (int) $row['sheets_per_pack'] ?>매 · <?= e((string) $row['category_slug']) ?></small>
            <?php endif; ?>
          </div>
        </td>
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
            class="admin-input qr-credit-input js-qr-credit"
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
        <td class="qr-products">
          <?php
            $pc = (int) ($row['product_count'] ?? 0);
            $catId = (int) ($row['shop_category_id'] ?? 0);
          ?>
          <?php if ($pc > 0 && $catId > 0): ?>
          <a class="qr-products-link" href="<?= url('admin/shop/products') ?>?category_id=<?= $catId ?>" title="상품관리에서 해당 분류 보기"><?= number_format($pc) ?>개</a>
          <?php elseif ($pc > 0): ?>
          <span><?= number_format($pc) ?>개</span>
          <?php else: ?>
          <span class="admin-muted">0개</span>
          <?php endif; ?>
        </td>
        <td class="qr-qrcount js-qr-generated-count"><?= number_format((int) ($row['generated_qr_count'] ?? 0)) ?>개</td>
        <td class="qr-actions">
          <div class="qr-actions-btns">
            <button type="button" class="admin-btn admin-btn--sm admin-btn--primary js-qr-action" data-action="generate" data-group-no="<?= (int) $row['group_no'] ?>">QR코드생성</button>
            <button type="button" class="admin-btn admin-btn--sm js-qr-action" data-action="preview" data-group-no="<?= (int) $row['group_no'] ?>">페이지미리보기</button>
            <button type="button" class="admin-btn admin-btn--sm js-qr-action" data-action="generate-history" data-group-no="<?= (int) $row['group_no'] ?>">생성이력</button>
            <button type="button" class="admin-btn admin-btn--sm js-qr-action" data-action="usage-history" data-group-no="<?= (int) $row['group_no'] ?>">사용이력</button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="admin-note qr-group-notes">
  <strong>참고</strong>
  <ul>
    <li>QR 그룹은 <b>제품 분류</b>별로 나누며, 같은 분류 안에서는 <b>정상 소비자가가 동일한 상품</b>을 하나의 그룹으로 묶습니다.</li>
    <li>그룹No. 아래 QR은 그룹 안내용(카테고리·매수) 주소입니다. <b>패키지 인쇄용 QR은 반드시 [QR코드생성]으로 만든 고유 쿠폰번호 URL</b>을 사용하세요.</li>
    <li>생성 URL 형식: <code>/qr-coupon?g=&amp;cat=&amp;sheets=&amp;code=LU01-XXXX</code> — <b>code</b>가 고객 고유 쿠폰번호입니다.</li>
    <li><b>지급크레딧</b>은 목록에서 바로 수정·저장할 수 있습니다.</li>
  </ul>
</div>

<div class="admin-modal" id="qrPreviewModal" hidden>
  <div class="admin-modal-backdrop" data-close="qrPreviewModal"></div>
  <div class="admin-modal-dialog admin-modal-dialog--preview" role="dialog" aria-modal="true" aria-labelledby="qrPreviewTitle">
    <div class="admin-modal-head">
      <h2 id="qrPreviewTitle">쿠폰페이지 미리보기</h2>
      <div class="admin-modal-head-actions">
        <a class="admin-btn admin-btn--sm" href="<?= e($couponPageUrl) ?>" target="_blank" rel="noopener">새 창에서 열기</a>
        <button type="button" class="admin-modal-close" data-close="qrPreviewModal" aria-label="닫기">×</button>
      </div>
    </div>
    <div class="admin-modal-body admin-preview-frame-wrap">
      <iframe id="qrPreviewFrame" title="쿠폰페이지 미리보기" src="about:blank"></iframe>
    </div>
  </div>
</div>

<div class="admin-modal" id="qrGenerateModal" hidden>
  <div class="admin-modal-backdrop" data-close="qrGenerateModal"></div>
  <div class="admin-modal-dialog admin-modal-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="qrGenerateTitle">
    <div class="admin-modal-head">
      <h2 id="qrGenerateTitle">QR코드생성</h2>
      <button type="button" class="admin-modal-close" data-close="qrGenerateModal" aria-label="닫기">×</button>
    </div>
    <form class="admin-modal-body" id="qrGenerateForm">
      <input type="hidden" name="group_no" id="qrGenGroupNo" value="">
      <div class="admin-field"><span>QR 그룹No.</span><div id="qrGenGroupLabel" class="ud-box">-</div></div>
      <div class="admin-field"><span>쿠폰페이지 URL</span><div id="qrGenUrl" class="ud-box" style="word-break:break-all">-</div></div>
      <div class="admin-field"><span>카테고리</span><div id="qrGenCat" class="ud-box">-</div></div>
      <div class="admin-field"><span>매수 (meta_sheets_per_pack)</span><div id="qrGenSheets" class="ud-box">-</div></div>
      <label class="admin-field">
        <span>생성 QR 코드 수</span>
        <input class="admin-input" type="number" name="quantity" id="qrGenQty" min="1" max="2000" step="1" value="10" required>
        <small>1~2000개까지 한 번에 생성할 수 있습니다.</small>
      </label>
      <div class="admin-head-actions" style="margin-top:8px;justify-content:flex-end">
        <button type="button" class="admin-btn" id="qrGenPreviewBtn">페이지 미리보기</button>
        <button type="button" class="admin-btn" data-close="qrGenerateModal">취소</button>
        <button type="submit" class="admin-btn admin-btn--primary" id="qrGenSubmit">생성</button>
      </div>
    </form>
  </div>
</div>

<div class="admin-modal" id="qrHistoryModal" hidden>
  <div class="admin-modal-backdrop" data-close="qrHistoryModal"></div>
  <div class="admin-modal-dialog admin-modal-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="qrHistoryTitle">
    <div class="admin-modal-head">
      <h2 id="qrHistoryTitle">생성이력</h2>
      <button type="button" class="admin-modal-close" data-close="qrHistoryModal" aria-label="닫기">×</button>
    </div>
    <div class="admin-modal-body">
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>배치ID</th>
              <th>수량</th>
              <th>카테고리</th>
              <th>매수</th>
              <th>사용</th>
              <th>생성일시</th>
            </tr>
          </thead>
          <tbody id="qrHistoryBody">
            <tr><td colspan="6" class="empty">불러오는 중…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="admin-modal" id="qrUsageModal" hidden>
  <div class="admin-modal-backdrop" data-close="qrUsageModal"></div>
  <div class="admin-modal-dialog admin-modal-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="qrUsageTitle">
    <div class="admin-modal-head">
      <h2 id="qrUsageTitle">사용이력</h2>
      <button type="button" class="admin-modal-close" data-close="qrUsageModal" aria-label="닫기">×</button>
    </div>
    <div class="admin-modal-body">
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>코드</th>
              <th>사용자</th>
              <th>사용일시</th>
            </tr>
          </thead>
          <tbody id="qrUsageBody">
            <tr><td colspan="3" class="empty">불러오는 중…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var creditSaveUrl = <?= json_encode($creditSaveUrl, JSON_UNESCAPED_SLASHES) ?>;
  var generateUrl = <?= json_encode($generateUrl, JSON_UNESCAPED_SLASHES) ?>;
  var historyUrl = <?= json_encode($historyUrl, JSON_UNESCAPED_SLASHES) ?>;
  var usageUrl = <?= json_encode($usageUrl, JSON_UNESCAPED_SLASHES) ?>;
  var previewUrl = <?= json_encode($couponPreviewUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

  function openModal(id) {
    var el = document.getElementById(id);
    if (el) el.hidden = false;
  }
  function closeModal(id) {
    var el = document.getElementById(id);
    if (el) el.hidden = true;
  }
  function withPreview(url) {
    if (!url) return previewUrl;
    return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'preview=1';
  }
  function openPreview(url) {
    var frame = document.getElementById('qrPreviewFrame');
    if (frame) frame.src = withPreview(url || previewUrl);
    openModal('qrPreviewModal');
  }
  document.querySelectorAll('[data-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      closeModal(btn.getAttribute('data-close'));
    });
  });

  var previewBtn = document.getElementById('qrPreviewBtn');
  if (previewBtn) {
    previewBtn.addEventListener('click', function () {
      openPreview(previewUrl);
    });
  }

  function rowByGroup(groupNo) {
    return document.querySelector('tr[data-group-no="' + groupNo + '"]');
  }

  document.querySelectorAll('.js-qr-action').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      var action = btn.getAttribute('data-action') || '';
      var groupNo = btn.getAttribute('data-group-no') || '';
      var row = rowByGroup(groupNo);
      if (!row) return;

      if (action === 'generate') {
        var landingUrl = row.getAttribute('data-coupon-url') || '';
        document.getElementById('qrGenGroupNo').value = groupNo;
        document.getElementById('qrGenGroupLabel').textContent = groupNo;
        document.getElementById('qrGenUrl').textContent = landingUrl || '-';
        document.getElementById('qrGenUrl').setAttribute('data-url', landingUrl);
        document.getElementById('qrGenCat').textContent =
          (row.getAttribute('data-category-name') || '-') + ' (' + (row.getAttribute('data-category-slug') || '-') + ')';
        document.getElementById('qrGenSheets').textContent = (row.getAttribute('data-sheets') || '-') + '매';
        document.getElementById('qrGenQty').value = '10';
        openModal('qrGenerateModal');
        return;
      }

      if (action === 'preview') {
        openPreview(row.getAttribute('data-coupon-url') || previewUrl);
        return;
      }

      if (action === 'generate-history') {
        openModal('qrHistoryModal');
        var body = document.getElementById('qrHistoryBody');
        body.innerHTML = '<tr><td colspan="6" class="empty">불러오는 중…</td></tr>';
        try {
          var res = await AdminAPI.post(historyUrl, { group_no: Number(groupNo) });
          var items = (res.data && res.data.items) || [];
          if (!items.length) {
            body.innerHTML = '<tr><td colspan="6" class="empty">생성이력이 없습니다.</td></tr>';
            return;
          }
          body.innerHTML = items.map(function (it) {
            return '<tr>' +
              '<td>' + (it.id || '-') + '</td>' +
              '<td>' + Number(it.quantity || it.code_count || 0).toLocaleString() + '개</td>' +
              '<td>' + (it.category_name || it.category_slug || '-') + '</td>' +
              '<td>' + Number(it.sheets_per_pack || 0).toLocaleString() + '매</td>' +
              '<td>' + Number(it.used_count || 0).toLocaleString() + ' / ' + Number(it.code_count || it.quantity || 0).toLocaleString() + '</td>' +
              '<td>' + (it.created_at || '-') + '</td>' +
              '</tr>';
          }).join('');
        } catch (err) {
          body.innerHTML = '<tr><td colspan="6" class="empty">' + (err.message || '조회 실패') + '</td></tr>';
        }
        return;
      }

      if (action === 'usage-history') {
        openModal('qrUsageModal');
        var ubody = document.getElementById('qrUsageBody');
        ubody.innerHTML = '<tr><td colspan="3" class="empty">불러오는 중…</td></tr>';
        try {
          var ures = await AdminAPI.post(usageUrl, { group_no: Number(groupNo) });
          var uitems = (ures.data && ures.data.items) || [];
          if (!uitems.length) {
            ubody.innerHTML = '<tr><td colspan="3" class="empty">사용이력이 없습니다.</td></tr>';
            return;
          }
          ubody.innerHTML = uitems.map(function (it) {
            var who = it.used_by_name || it.used_by_email || ('#' + (it.used_by || '-'));
            return '<tr><td><code>' + (it.code || '-') + '</code></td><td>' + who + '</td><td>' + (it.used_at || '-') + '</td></tr>';
          }).join('');
        } catch (err) {
          ubody.innerHTML = '<tr><td colspan="3" class="empty">' + (err.message || '조회 실패') + '</td></tr>';
        }
      }
    });
  });

  var genForm = document.getElementById('qrGenerateForm');
  var genPreviewBtn = document.getElementById('qrGenPreviewBtn');
  if (genPreviewBtn) {
    genPreviewBtn.addEventListener('click', function () {
      var urlEl = document.getElementById('qrGenUrl');
      openPreview((urlEl && urlEl.getAttribute('data-url')) || '');
    });
  }
  if (genForm) {
    genForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      var groupNo = Number(document.getElementById('qrGenGroupNo').value || 0);
      var quantity = Number(document.getElementById('qrGenQty').value || 0);
      var submit = document.getElementById('qrGenSubmit');
      submit.disabled = true;
      try {
        var res = await AdminAPI.post(generateUrl, { group_no: groupNo, quantity: quantity });
        var data = res.data || {};
        var first = (data.codes && data.codes[0]) || null;
        var sampleCode = first && first.code ? first.code : '';
        var sampleUrl = (first && first.coupon_page_url) || data.coupon_page_url || '';
        var msg = (res.message || '생성 완료');
        if (sampleCode) {
          msg += '\n고유 쿠폰번호 예시: ' + sampleCode;
        }
        if (sampleUrl) {
          msg += '\n샘플 URL: ' + sampleUrl;
        }
        showAdminAlert(msg, 'success');
        closeModal('qrGenerateModal');
        var row = rowByGroup(String(groupNo));
        if (row) {
          var cell = row.querySelector('.js-qr-generated-count');
          var prev = Number(row.getAttribute('data-generated') || 0);
          var next = prev + quantity;
          row.setAttribute('data-generated', String(next));
          if (cell) cell.textContent = next.toLocaleString() + '개';
        }
      } catch (err) {
        showAdminAlert(err.message || '생성 실패', 'error');
      } finally {
        submit.disabled = false;
      }
    });
  }

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
      showAdminAlert(err.message, 'error');
      input.value = prev;
      return;
    }
    if (next === prev) return;
    input.disabled = true;
    try {
      await AdminAPI.post(creditSaveUrl, {
        group_no: groupNo,
        credit_amount: next === '' ? null : Number(next)
      });
      input.value = next;
      input.setAttribute('data-prev', next);
      showAdminAlert('QR 그룹 ' + groupNo + ' 지급 크레딧이 저장되었습니다.', 'success');
    } catch (err) {
      input.value = prev;
      showAdminAlert(err.message || '저장 실패', 'error');
    } finally {
      input.disabled = false;
    }
  }

  document.querySelectorAll('.js-qr-credit').forEach(function (input) {
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        input.blur();
      }
    });
    input.addEventListener('blur', function () { saveCredit(input); });
  });
})();
</script>
