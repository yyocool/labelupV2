<?php
$matrix = $matrix ?? ['rows' => [], 'category_count' => 0, 'group_count' => 0, 'product_count' => 0, 'generated_qr_count' => 0, 'printed_qr_count' => 0, 'coupon_page_url' => ''];
$rows = $matrix['rows'] ?? [];
$loadError = $loadError ?? null;
$creditSaveUrl = url('api/admin/qr-coupons/credit/save');
$generateUrl = url('api/admin/qr-coupons/generate');
$historyUrl = url('api/admin/qr-coupons/generation-history');
$batchCodesUrl = url('api/admin/qr-coupons/batch-codes');
$groupCodesUrl = url('api/admin/qr-coupons/group-codes');
$markPrintedUrl = url('api/admin/qr-coupons/mark-printed');
$deleteCodesUrl = url('api/admin/qr-coupons/delete-codes');
$usageUrl = url('api/admin/qr-coupons/usage-history');
$couponPageUrl = (string) ($matrix['coupon_page_url'] ?? qr_public_url('qr-coupon'));
// 헤더 미리보기는 그룹(g)이 있어야 본문이 렌더됨. 첫 그룹 preview_url 사용.
$couponPreviewUrl = '';
foreach ($rows as $r) {
    $u = trim((string) ($r['preview_url'] ?? ''));
    if ($u !== '') {
        $couponPreviewUrl = $u;
        break;
    }
}
if ($couponPreviewUrl === '') {
    $couponPreviewUrl = url('qr-coupon') . '?' . http_build_query([
        'g' => 1,
        'cat' => 'sample',
        'sheets' => 20,
        'preview' => 1,
    ], '', '&', PHP_QUERY_RFC3986);
} elseif (!preg_match('/[?&]preview=1(?:&|$)/', $couponPreviewUrl)) {
    $couponPreviewUrl .= (str_contains($couponPreviewUrl, '?') ? '&' : '?') . 'preview=1';
}
$sampleCouponCode = '쿠폰코드자리(입력안됨)';
$sampleCouponUrl = qr_public_url('qr-coupon', [
    'g' => 1,
    'cat' => 'sample',
    'sheets' => 20,
    'code' => $sampleCouponCode,
]);
?>
<div class="admin-head">
  <div>
    <h1>QR쿠폰관리</h1>
    <p>무료배포 QR 그룹 체계 — 제품분류 · 매수/팩 · 정상 소비자가 기준</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn" id="qrPrintTplBtn">공통 출력템플릿</button>
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
    (출력 <?= number_format((int) ($matrix['printed_qr_count'] ?? 0)) ?>건)
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
        <th class="qr-col-credit">지급잉크</th>
        <th class="qr-col-products">상품수</th>
        <th class="qr-col-qrcount">생성QR수</th>
        <th class="qr-col-actions">관리</th>
        <th class="qr-col-mfg" title="정상 소비자가 × 20%">제조단가</th>
        <th class="qr-col-payout" title="제조단가 × 3%">최대지급액</th>
        <th class="qr-col-tokens" title="최대지급액으로 사용 가능한 ChatGPT 토큰(입력75%/출력25% 혼합)">토큰량</th>
        <th class="qr-col-credit-eq" title="최대지급액 → 잉크(1원=10C)">잉크환산</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="13" class="empty">등록된 QR 그룹이 없습니다. 마이그레이션을 실행해 주세요.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $row): ?>
      <?php
        $landingUrl = (string) ($row['coupon_page_url'] ?? '');
        $previewUrl = (string) ($row['preview_url'] ?? '');
        $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=96x96&margin=8&data=' . rawurlencode($landingUrl);
      ?>
      <tr data-group-no="<?= (int) $row['group_no'] ?>"
          data-category-no="<?= (int) $row['category_no'] ?>"
          data-category-name="<?= e((string) $row['category_name']) ?>"
          data-category-slug="<?= e((string) $row['category_slug']) ?>"
          data-sheets="<?= (int) $row['sheets_per_pack'] ?>"
          data-coupon-url="<?= e($landingUrl) ?>"
          data-preview-url="<?= e($previewUrl) ?>"
          data-generated="<?= (int) ($row['generated_qr_count'] ?? 0) ?>"
          data-printed="<?= (int) ($row['printed_qr_count'] ?? 0) ?>">
        <?php if (!empty($row['show_category'])): ?>
        <td class="qr-catno" rowspan="<?= (int) $row['category_rowspan'] ?>" style="background:<?= e((string) $row['color_hex']) ?>">
          <strong><?= (int) $row['category_no'] ?></strong>
        </td>
        <?php endif; ?>
        <td class="qr-groupno">
          <div class="qr-groupno-wrap">
            <strong><?= (int) $row['group_no'] ?></strong>
            <button
              type="button"
              class="admin-btn admin-btn--sm qr-group-tpl-btn js-qr-group-template"
              data-group-no="<?= (int) $row['group_no'] ?>"
              data-category-no="<?= (int) $row['category_no'] ?>"
              data-category-name="<?= e((string) $row['category_name']) ?>"
              title="그룹 <?= (int) $row['group_no'] ?> 출력템플릿 편집"
            >템플릿</button>
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
            aria-label="QR 그룹 <?= (int) $row['group_no'] ?> 지급잉크"
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
        <td class="qr-qrcount js-qr-generated-count"><?php
          $genCnt = (int) ($row['generated_qr_count'] ?? 0);
          $prtCnt = (int) ($row['printed_qr_count'] ?? 0);
        ?><?= number_format($genCnt) ?>개 (출력 <?= number_format($prtCnt) ?>건)</td>
        <td class="qr-actions">
          <div class="qr-actions-btns">
            <button type="button" class="admin-btn admin-btn--sm admin-btn--primary js-qr-action" data-action="generate" data-group-no="<?= (int) $row['group_no'] ?>">QR코드생성</button>
            <button type="button" class="admin-btn admin-btn--sm js-qr-action" data-action="generate-history" data-group-no="<?= (int) $row['group_no'] ?>">프린트</button>
            <button type="button" class="admin-btn admin-btn--sm js-qr-action" data-action="manage-codes" data-group-no="<?= (int) $row['group_no'] ?>">QR코드 관리</button>
            <button type="button" class="admin-btn admin-btn--sm js-qr-action" data-action="usage-history" data-group-no="<?= (int) $row['group_no'] ?>">사용이력</button>
          </div>
        </td>
        <td class="qr-mfg"><?= number_format((int) ($row['manufacturing_cost'] ?? 0)) ?>원</td>
        <td class="qr-payout"><?= number_format((int) ($row['max_payout'] ?? 0)) ?>원</td>
        <td class="qr-tokens"><?= number_format((int) ($row['token_amount'] ?? 0)) ?></td>
        <td class="qr-credit-eq"><strong><?= number_format((int) ($row['credit_equivalent'] ?? 0)) ?> C</strong></td>
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
    <li>생성 URL 형식: <code>https://www.labelup.co.kr/qr-coupon?g=&amp;cat=&amp;sheets=&amp;code=LU01-XXXX</code> — <b>code</b>가 고객 고유 쿠폰번호입니다.</li>
    <li><b>지급잉크</b>은 목록에서 바로 수정·저장할 수 있으며, <b>구매잉크</b> 메뉴에서도 동일 값이 적용됩니다.</li>
    <li><b>제조단가</b> = 정상 소비자가 × 20%, <b>최대지급액</b> = 제조단가 × 3% (원 단위 반올림).</li>
    <li><b>토큰량</b>은 최대지급액으로 쓸 수 있는 ChatGPT 토큰(현재 기본 모델, 입력 75%·출력 25% 혼합 단가·서버 환율 기준)입니다.</li>
    <li><b>잉크환산</b>은 최대지급액을 <b>1원 = 10 C</b>로 환산한 값이며, 실제 지급잉크 설정 참고용입니다.</li>
    <li><b>공통 출력템플릿</b>은 그룹별 템플릿이 없을 때 쓰는 기본 레이아웃입니다. QR 그룹No. 아래 <b>템플릿</b> 버튼으로 그룹마다 따로 편집·저장할 수 있습니다.</li>
    <li><b>프린트</b>·<b>QR코드 관리</b>에서 발급된 코드를 보고, <b>미사용만 보기</b>·선택 삭제로 재사용을 막을 수 있습니다. 사용·중지된 코드는 삭제할 수 없습니다.</li>
    <li>쿠폰 페이지 미리보기는 상단 <b>쿠폰페이지 미리보기</b> 버튼만 사용합니다.</li>
  </ul>
</div>

<div class="admin-modal" id="qrPrintTplModal" hidden>
  <div class="admin-modal-backdrop" data-close="qrPrintTplModal"></div>
  <div class="admin-modal-dialog admin-modal-dialog--editor" role="dialog" aria-modal="true" aria-labelledby="qrPrintTplTitle">
    <div class="admin-modal-head">
      <h2 id="qrPrintTplTitle">출력템플릿</h2>
      <div class="admin-modal-head-actions">
        <span class="qr-tpl-status" id="qrTplStatus" aria-live="polite"></span>
        <button type="button" class="admin-btn admin-btn--sm" id="qrTplResetBtn">초기 레이아웃</button>
        <button type="button" class="admin-btn admin-btn--sm admin-btn--primary" id="qrTplSaveBtn">저장</button>
        <button type="button" class="admin-modal-close" data-close="qrPrintTplModal" aria-label="닫기">×</button>
      </div>
    </div>
    <div class="qr-tpl" id="qrTplRoot">
      <aside class="qr-tpl-left" aria-label="용지와 미리보기">
        <section class="qr-tpl-paper">
          <header class="qr-tpl-left-head">
            <span>용지선택</span>
            <em id="qrTplPaperMeta">LU-3230 · 70×36 mm</em>
          </header>
          <input class="admin-input qr-tpl-search" type="search" id="qrTplPaperSearch" placeholder="상품명, SKU, 호환코드 검색" autocomplete="off">
          <div class="qr-tpl-paper-list" id="qrTplPaperList"></div>
        </section>
        <section class="qr-tpl-preview" aria-label="미리보기">
          <header class="qr-tpl-left-head">
            <span>미리보기</span>
            <em id="qrTplSheetMeta">A4 · 2×7 칸</em>
          </header>
          <div class="qr-tpl-sheet-wrap" id="qrTplSheetWrap"></div>
        </section>
      </aside>
      <div class="qr-tpl-right">
        <div class="qr-tpl-toolbar" role="toolbar" aria-label="편집 도구">
          <button type="button" class="qr-tpl-tool is-active" data-tool="select" title="선택">↖<span>선택</span></button>
          <button type="button" class="qr-tpl-tool" data-tool="text" title="텍스트">T<span>텍스트</span></button>
          <button type="button" class="qr-tpl-tool" data-tool="qr" title="QR">▣<span>QR</span></button>
          <button type="button" class="qr-tpl-tool" data-tool="barcode" title="바코드">▮▮<span>바코드</span></button>
          <button type="button" class="qr-tpl-tool" data-tool="image" title="이미지">🖼<span>이미지</span></button>
          <div class="qr-tpl-shape">
            <button type="button" class="qr-tpl-tool" data-tool="shape" id="qrTplShapeBtn" title="도형">▭<span>도형</span></button>
            <div class="qr-tpl-shape-pop" id="qrTplShapePop" hidden>
              <button type="button" data-shape="rect">▭ 사각형</button>
              <button type="button" data-shape="roundrect">▢ 둥근 사각형</button>
              <button type="button" data-shape="ellipse">○ 원</button>
            </div>
          </div>
          <span class="qr-tpl-toolbar-div" aria-hidden="true"></span>
          <label class="qr-tpl-file" title="이미지 가져오기">
            이미지 넣기
            <input type="file" id="qrTplImageFile" accept="image/*">
          </label>
        </div>
        <div class="qr-tpl-canvas" id="qrTplCanvas">
          <div class="qr-tpl-artboard" id="qrTplArtboard"></div>
        </div>
        <div class="qr-tpl-dock">
          <div class="qr-tpl-settings" aria-label="부가 설정">
            <label class="qr-tpl-check"><input type="checkbox" id="qrTplGrid" checked> 그리드</label>
            <label class="qr-tpl-check"><input type="checkbox" id="qrTplSnap" checked> 스냅</label>
            <label class="qr-tpl-field">배경 <input type="color" id="qrTplBg" value="#ffffff"></label>
            <span class="qr-tpl-hint" id="qrTplHint">도구를 고른 뒤 라벨을 클릭하면 오브젝트가 추가됩니다.</span>
          </div>
          <div class="qr-tpl-props" id="qrTplProps" hidden>
            <div class="qr-tpl-props-row" id="qrTplPropsMain"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="admin-modal" id="qrPreviewModal" hidden>
  <div class="admin-modal-backdrop" data-close="qrPreviewModal"></div>
  <div class="admin-modal-dialog admin-modal-dialog--preview" role="dialog" aria-modal="true" aria-labelledby="qrPreviewTitle">
    <div class="admin-modal-head">
      <h2 id="qrPreviewTitle">쿠폰페이지 미리보기</h2>
      <div class="admin-modal-head-actions">
        <a class="admin-btn admin-btn--sm" id="qrPreviewOpenTab" href="<?= e($couponPreviewUrl) ?>" target="_blank" rel="noopener">새 창에서 열기</a>
        <button type="button" class="admin-modal-close" data-close="qrPreviewModal" aria-label="닫기">×</button>
      </div>
    </div>
    <div class="admin-modal-body admin-preview-frame-wrap">
      <iframe id="qrPreviewFrame" title="쿠폰페이지 미리보기" src="about:blank" referrerpolicy="no-referrer-when-downgrade"></iframe>
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
      <div class="admin-field">
        <span>고유 QR 링크 형식</span>
        <div id="qrGenUrl" class="ud-box" style="word-break:break-all">-</div>
        <small>생성 시 쿠폰마다 고유번호가 만들어지고, QR 링크의 <code>code=</code>에 들어갑니다.</small>
      </div>
      <div class="admin-field"><span>카테고리</span><div id="qrGenCat" class="ud-box">-</div></div>
      <div class="admin-field"><span>매수 (meta_sheets_per_pack)</span><div id="qrGenSheets" class="ud-box">-</div></div>
      <label class="admin-field">
        <span>생성 QR 코드 수</span>
        <input class="admin-input" type="number" name="quantity" id="qrGenQty" min="1" max="2000" step="1" value="10" required>
        <small>1~2000개까지 한 번에 생성할 수 있습니다. 각 쿠폰은 서로 다른 쿠폰번호·QR 링크를 갖습니다.</small>
      </label>
      <div class="admin-head-actions" style="margin-top:8px;justify-content:flex-end">
        <button type="button" class="admin-btn" id="qrGenPreviewBtn">페이지 미리보기</button>
        <button type="button" class="admin-btn" data-close="qrGenerateModal">취소</button>
        <button type="submit" class="admin-btn admin-btn--primary" id="qrGenSubmit">생성</button>
      </div>
    </form>
  </div>
</div>

<div class="admin-modal" id="qrCodesModal" hidden>
  <div class="admin-modal-backdrop" data-close="qrCodesModal"></div>
  <div class="admin-modal-dialog admin-modal-dialog--preview qr-codes-dialog" role="dialog" aria-modal="true" aria-labelledby="qrCodesTitle">
    <div class="admin-modal-head">
      <h2 id="qrCodesTitle">프린트</h2>
      <div class="admin-modal-head-actions">
        <button type="button" class="admin-btn admin-btn--sm admin-btn--primary" id="qrCodesPrintUnprintedBtn">미인쇄 인쇄</button>
        <button type="button" class="admin-btn admin-btn--sm admin-btn--danger" id="qrCodesDeleteBtn">선택 삭제</button>
        <button type="button" class="admin-btn admin-btn--sm" id="qrCodesCopyBtn">URL 복사</button>
        <button type="button" class="admin-btn admin-btn--sm" id="qrCodesCsvBtn">CSV 다운로드</button>
        <button type="button" class="admin-modal-close" data-close="qrCodesModal" aria-label="닫기">×</button>
      </div>
    </div>
    <div class="admin-modal-body">
      <div class="qr-codes-toolbar">
        <label class="qr-print-filter" title="사용·중지된 코드는 숨기고 미사용만 표시">
          <input type="checkbox" id="qrCodesUnusedOnly" checked> 미사용만 보기
        </label>
        <label class="qr-print-filter">
          <input type="checkbox" id="qrCodesUnprintedOnly"> 미인쇄만 보기
        </label>
        <span class="qr-codes-toolbar-hint" id="qrCodesFilterHint"></span>
      </div>
      <p class="admin-meta-line" id="qrCodesMeta"></p>
      <p class="qr-codes-msg" id="qrCodesMsg" hidden></p>
      <div class="admin-table-wrap qr-codes-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th class="qr-codes-check">
                <input type="checkbox" id="qrCodesSelectAll" title="표시 중인 미사용 QR 전체 선택">
              </th>
              <th>QR</th>
              <th>쿠폰번호</th>
              <th>고유 QR 링크</th>
              <th>상태</th>
              <th>프린트</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="qrCodesBody">
            <tr><td colspan="7" class="empty">생성된 코드가 없습니다.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="admin-modal" id="qrLabelPrintModal" hidden>
  <div class="admin-modal-backdrop" data-close="qrLabelPrintModal"></div>
  <div class="admin-modal-dialog admin-modal-dialog--preview qr-label-print-dialog" role="dialog" aria-modal="true" aria-labelledby="qrLabelPrintTitle">
    <div class="admin-modal-head">
      <h2 id="qrLabelPrintTitle">라벨 미리보기 · 인쇄</h2>
      <div class="admin-modal-head-actions">
        <span class="qr-tpl-status" id="qrLabelPrintMeta"></span>
        <button type="button" class="admin-btn admin-btn--sm admin-btn--primary" id="qrLabelPrintDoBtn">인쇄</button>
        <button type="button" class="admin-modal-close" data-close="qrLabelPrintModal" aria-label="닫기">×</button>
      </div>
    </div>
    <div class="qr-label-print-stage">
      <iframe id="qrLabelPrintFrame" title="라벨 인쇄 미리보기" src="about:blank"></iframe>
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
              <th>지급 잉크</th>
              <th>사용자</th>
              <th>사용일시</th>
            </tr>
          </thead>
          <tbody id="qrUsageBody">
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
  var generateUrl = <?= json_encode($generateUrl, JSON_UNESCAPED_SLASHES) ?>;
  var groupCodesUrl = <?= json_encode($groupCodesUrl, JSON_UNESCAPED_SLASHES) ?>;
  var usageUrl = <?= json_encode($usageUrl, JSON_UNESCAPED_SLASHES) ?>;
  var deleteCodesUrl = <?= json_encode($deleteCodesUrl, JSON_UNESCAPED_SLASHES) ?>;
  var previewUrl = <?= json_encode($couponPreviewUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  var codesAll = [];
  var codesTitle = '프린트';
  var codesMetaText = '';
  var codesGroupNo = '';
  var codesCategoryNo = 0;

  function formatQrCount(generated, printed) {
    return Number(generated || 0).toLocaleString() + '개 (출력 ' + Number(printed || 0).toLocaleString() + '건)';
  }
  function paintGeneratedCell(row) {
    if (!row) return;
    var cell = row.querySelector('.js-qr-generated-count');
    if (cell) {
      cell.textContent = formatQrCount(row.getAttribute('data-generated'), row.getAttribute('data-printed'));
    }
  }

  function openModal(id) {
    var el = document.getElementById(id);
    if (el) el.hidden = false;
  }
  function closeModal(id) {
    var el = document.getElementById(id);
    if (el) el.hidden = true;
  }
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/"/g, '&quot;');
  }
  function padGroup(n) {
    var s = String(n || 0);
    return s.length >= 2 ? s : ('0' + s);
  }
  function uniqueUrlExample(landingUrl, groupNo) {
    var base = landingUrl || '';
    var placeholder = 'LU' + padGroup(groupNo) + '-XXXXXXXX';
    if (!base) return '/qr-coupon?code=' + placeholder;
    if (/[?&]code=/.test(base)) return base;
    return base + (base.indexOf('?') >= 0 ? '&' : '?') + 'code=' + placeholder;
  }
  function qrImgSrc(url) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=96x96&margin=8&data=' + encodeURIComponent(url || '');
  }
  function withPreview(url) {
    if (!url) return previewUrl;
    if (/[?&]preview=1(?:&|$)/.test(url)) return sameOriginUrl(url);
    return sameOriginUrl(url + (url.indexOf('?') >= 0 ? '&' : '?') + 'preview=1');
  }
  /** HTTPS 관리자에서 http:// 절대 URL iframe이 차단되지 않도록 동일 출처로 맞춤 */
  function sameOriginUrl(url) {
    if (!url) return url;
    try {
      var u = new URL(url, location.href);
      if (u.hostname === location.hostname) {
        return u.pathname + u.search + u.hash;
      }
    } catch (e) {}
    return url;
  }
  function unprintedOnly() {
    var el = document.getElementById('qrCodesUnprintedOnly');
    return !!(el && el.checked);
  }
  function unusedOnly() {
    var el = document.getElementById('qrCodesUnusedOnly');
    return !!(el && el.checked);
  }
  function codeStatus(it) {
    var s = String((it && it.status) || '').trim();
    return s || 'unused';
  }
  function codePrinted(it) {
    if (!it) return false;
    if (it.printed === true || it.printed === 1 || it.printed === '1') return true;
    return !!(it.printed_at);
  }
  function normalizeCodesList(codes) {
    return (Array.isArray(codes) ? codes : []).map(function (it) {
      var row = Object.assign({}, it || {});
      row.id = Number(row.id || 0);
      row.status = codeStatus(row);
      row.printed = codePrinted(row);
      return row;
    });
  }
  function visibleCodes() {
    return codesAll.filter(function (it) {
      if (unusedOnly() && codeStatus(it) !== 'unused') return false;
      if (unprintedOnly() && codePrinted(it)) return false;
      return true;
    });
  }
  function isSelectable(it) {
    return it && codeStatus(it) === 'unused' && Number(it.id || 0) > 0;
  }
  function selectedCodeIds() {
    var body = document.getElementById('qrCodesBody');
    if (!body) return [];
    return Array.prototype.map.call(body.querySelectorAll('.js-qr-code-check:checked'), function (el) {
      return Number(el.value || 0);
    }).filter(function (id) { return id > 0; });
  }
  function syncSelectAll() {
    var all = document.getElementById('qrCodesSelectAll');
    if (!all) return;
    var boxes = document.querySelectorAll('#qrCodesBody .js-qr-code-check:not(:disabled)');
    if (!boxes.length) {
      all.checked = false;
      all.indeterminate = false;
      return;
    }
    var checked = 0;
    boxes.forEach(function (el) { if (el.checked) checked++; });
    all.checked = checked === boxes.length;
    all.indeterminate = checked > 0 && checked < boxes.length;
  }
  function statusLabel(it) {
    var s = codeStatus(it);
    if (s === 'used') return '사용';
    if (s === 'disabled') return '중지';
    return '미사용';
  }
  function printLabel(it) {
    if (codePrinted(it)) {
      return '인쇄완료' + (it.printed_at ? '<small class="qr-print-when">' + esc(it.printed_at) + '</small>' : '');
    }
    return '미인쇄';
  }
  function emptyCodesMessage() {
    if (!codesAll.length) return '생성된 코드가 없습니다.';
    if (unusedOnly() && unprintedOnly()) return '조건에 맞는 미사용·미인쇄 쿠폰이 없습니다.';
    if (unusedOnly()) return '미사용 쿠폰이 없습니다. (필터를 끄면 사용·중지 코드도 표시됩니다)';
    if (unprintedOnly()) return '미인쇄 쿠폰이 없습니다.';
    return '표시할 쿠폰이 없습니다.';
  }
  function showCodesMsg(text, type) {
    var el = document.getElementById('qrCodesMsg');
    if (!el) return;
    if (!text) {
      el.hidden = true;
      el.textContent = '';
      el.className = 'qr-codes-msg';
      return;
    }
    el.hidden = false;
    el.textContent = text;
    el.className = 'qr-codes-msg ' + (type === 'error' ? 'is-error' : 'is-ok');
  }
  function renderCodesModal(title, codes, meta) {
    codesAll = normalizeCodesList(codes);
    codesTitle = title || '프린트';
    codesMetaText = meta || '';
    var unusedChk = document.getElementById('qrCodesUnusedOnly');
    if (unusedChk) unusedChk.checked = true;
    var unprintedChk = document.getElementById('qrCodesUnprintedOnly');
    if (unprintedChk) unprintedChk.checked = false;
    showCodesMsg('');
    paintCodesTable();
    openModal('qrCodesModal');
  }
  function paintCodesTable() {
    var titleEl = document.getElementById('qrCodesTitle');
    var metaEl = document.getElementById('qrCodesMeta');
    var hintEl = document.getElementById('qrCodesFilterHint');
    var body = document.getElementById('qrCodesBody');
    var list = visibleCodes();
    var printed = codesAll.filter(function (it) { return codePrinted(it); }).length;
    var unused = codesAll.filter(function (it) { return codeStatus(it) === 'unused'; }).length;
    var hidden = Math.max(0, codesAll.length - list.length);
    var filters = [];
    if (unusedOnly()) filters.push('미사용');
    if (unprintedOnly()) filters.push('미인쇄');
    if (titleEl) titleEl.textContent = codesTitle;
    if (metaEl) {
      metaEl.textContent = (codesMetaText ? codesMetaText + ' · ' : '') +
        '전체 ' + codesAll.length.toLocaleString() + '개 · 미사용 ' + unused.toLocaleString() +
        '개 · 미인쇄 ' + (codesAll.length - printed).toLocaleString() +
        '개 · 표시 ' + list.length.toLocaleString() + '개' +
        (hidden ? (' · 숨김 ' + hidden.toLocaleString() + '개') : '');
    }
    if (hintEl) {
      hintEl.textContent = filters.length
        ? ('필터: ' + filters.join(' + ') + (hidden ? (' (숨김 ' + hidden.toLocaleString() + '개)') : ' (숨김 없음)'))
        : '필터 없음 · 전체 표시';
    }
    if (!body) return;
    if (!list.length) {
      body.innerHTML = '<tr><td colspan="7" class="empty">' + emptyCodesMessage() + '</td></tr>';
      syncSelectAll();
      return;
    }
    // lazy 로딩으로 대량 목록도 QR 미리보기 표시 (예전 80개 초과 시 CSV 참고만 보이던 제한 제거)
    body.innerHTML = list.map(function (it) {
      var code = it.code || '-';
      var url = it.coupon_page_url || '';
      var id = Number(it.id || 0);
      var canSelect = isSelectable(it);
      var printed = codePrinted(it);
      var qr = url
        ? '<img src="' + esc(qrImgSrc(url)) + '" alt="' + esc(code) + '" width="56" height="56" loading="lazy">'
        : '<span class="admin-muted">-</span>';
      var printBtnLabel = printed ? '재출력' : '프린트';
      var printBtnClass = printed
        ? 'admin-btn admin-btn--sm js-qr-code-print is-reprint'
        : 'admin-btn admin-btn--sm admin-btn--primary js-qr-code-print';
      var check = '<input type="checkbox" class="js-qr-code-check" value="' + esc(String(id)) + '"' +
        (canSelect ? '' : ' disabled title="미사용 쿠폰만 선택·삭제할 수 있습니다"') + '>';
      return '<tr data-code-id="' + esc(String(id)) + '" data-status="' + esc(codeStatus(it)) + '">' +
        '<td class="qr-codes-check">' + check + '</td>' +
        '<td class="qr-codes-qr">' + qr + '</td>' +
        '<td><code>' + esc(code) + '</code></td>' +
        '<td class="qr-codes-url"><a href="' + esc(url) + '" target="_blank" rel="noopener">' + esc(url) + '</a></td>' +
        '<td>' + esc(statusLabel(it)) + '</td>' +
        '<td class="' + (printed ? 'qr-print-yes' : 'qr-print-no') + '">' + printLabel(it) + '</td>' +
        '<td><button type="button" class="' + printBtnClass + '" data-code-id="' +
          esc(String(id)) + '">' + printBtnLabel + '</button></td>' +
        '</tr>';
    }).join('');
    syncSelectAll();
  }
  function downloadCodesCsv(codes) {
    var lines = ['쿠폰번호,QR링크,상태,프린트'];
    (codes || []).forEach(function (it) {
      var code = String(it.code || '').replace(/"/g, '""');
      var url = String(it.coupon_page_url || '').replace(/"/g, '""');
      lines.push('"' + code + '","' + url + '","' + statusLabel(it) + '","' + (it.printed ? '인쇄완료' : '미인쇄') + '"');
    });
    var blob = new Blob(['\uFEFF' + lines.join('\n')], { type: 'text/csv;charset=utf-8' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'qr-coupons.csv';
    document.body.appendChild(a);
    a.click();
    setTimeout(function () {
      URL.revokeObjectURL(a.href);
      a.remove();
    }, 500);
  }
  async function copyCodesUrls(codes) {
    var text = (codes || []).map(function (it) {
      return (it.code || '') + '\t' + (it.coupon_page_url || '');
    }).join('\n');
    if (!text) return;
    try {
      await navigator.clipboard.writeText(text);
      showAdminAlert('쿠폰번호와 QR 링크를 복사했습니다.', 'success');
    } catch (err) {
      showAdminAlert('복사에 실패했습니다.', 'error');
    }
  }
  function openPreview(url) {
    var src = withPreview(url || previewUrl);
    var frame = document.getElementById('qrPreviewFrame');
    if (frame) frame.src = src;
    var tab = document.getElementById('qrPreviewOpenTab');
    if (tab) tab.href = src;
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

  async function openGroupCodesModal(groupNo, row, titlePrefix) {
    try {
      var res = await AdminAPI.post(groupCodesUrl, { group_no: Number(groupNo) });
      var data = res.data || {};
      codesGroupNo = String(groupNo);
      codesCategoryNo = Number(data.category_no || (row && row.getAttribute('data-category-no')) || 0);
      if (row) {
        row.setAttribute('data-generated', String(Number(data.quantity || 0)));
        row.setAttribute('data-printed', String(Number(data.printed_count || 0)));
        paintGeneratedCell(row);
      }
      renderCodesModal(
        (titlePrefix || 'QR 그룹 ') + groupNo,
        data.items || [],
        '그룹 ' + groupNo + (codesCategoryNo ? (' · 분류 ' + codesCategoryNo) : '')
      );
    } catch (err) {
      showAdminAlert(err.message || '쿠폰 목록을 불러오지 못했습니다.', 'error');
    }
  }

  document.querySelectorAll('.js-qr-action').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      var action = btn.getAttribute('data-action') || '';
      var groupNo = btn.getAttribute('data-group-no') || '';
      var row = rowByGroup(groupNo);
      if (!row) return;

      if (action === 'generate') {
        var landingUrl = row.getAttribute('data-coupon-url') || '';
        var exampleUrl = uniqueUrlExample(landingUrl, groupNo);
        document.getElementById('qrGenGroupNo').value = groupNo;
        document.getElementById('qrGenGroupLabel').textContent = groupNo;
        document.getElementById('qrGenUrl').textContent = exampleUrl || '-';
        document.getElementById('qrGenUrl').setAttribute('data-url', row.getAttribute('data-preview-url') || landingUrl);
        document.getElementById('qrGenCat').textContent =
          (row.getAttribute('data-category-name') || '-') + ' (' + (row.getAttribute('data-category-slug') || '-') + ')';
        document.getElementById('qrGenSheets').textContent = (row.getAttribute('data-sheets') || '-') + '매';
        document.getElementById('qrGenQty').value = '10';
        openModal('qrGenerateModal');
        return;
      }

      if (action === 'generate-history') {
        await openGroupCodesModal(groupNo, row, 'QR 그룹 ' + groupNo + ' 프린트');
        return;
      }

      if (action === 'manage-codes') {
        await openGroupCodesModal(groupNo, row, 'QR 그룹 ' + groupNo + ' 코드 관리');
        return;
      }

      if (action === 'usage-history') {
        openModal('qrUsageModal');
        var ubody = document.getElementById('qrUsageBody');
        ubody.innerHTML = '<tr><td colspan="4" class="empty">불러오는 중…</td></tr>';
        try {
          var ures = await AdminAPI.post(usageUrl, { group_no: Number(groupNo) });
          var uitems = (ures.data && ures.data.items) || [];
          if (!uitems.length) {
            ubody.innerHTML = '<tr><td colspan="4" class="empty">사용이력이 없습니다.</td></tr>';
            return;
          }
          ubody.innerHTML = uitems.map(function (it) {
            var who = it.used_by_name || it.used_by_email || ('#' + (it.used_by || '-'));
            var credit = (it.credit_amount == null || it.credit_amount === '')
              ? '<span class="admin-muted">미설정</span>'
              : ('<strong>' + Number(it.credit_amount).toLocaleString() + ' C</strong>');
            return '<tr><td><code>' + (it.code || '-') + '</code></td><td>' + credit + '</td><td>' + who + '</td><td>' + (it.used_at || '-') + '</td></tr>';
          }).join('');
        } catch (err) {
          ubody.innerHTML = '<tr><td colspan="4" class="empty">' + (err.message || '조회 실패') + '</td></tr>';
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
        var codes = data.codes || [];
        showAdminAlert(res.message || '생성 완료', 'success');
        closeModal('qrGenerateModal');
        var row = rowByGroup(String(groupNo));
        if (row) {
          var prev = Number(row.getAttribute('data-generated') || 0);
          var next = prev + quantity;
          row.setAttribute('data-generated', String(next));
          paintGeneratedCell(row);
        }
        codesGroupNo = String(groupNo);
        codesCategoryNo = Number((row && row.getAttribute('data-category-no')) || 0);
        renderCodesModal(
          quantity.toLocaleString() + '개 쿠폰 생성 완료',
          codes,
          '배치 #' + (data.batch_id || '-')
        );
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
      throw new Error('지급 잉크는 0 이상의 정수로 입력해 주세요.');
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
      showAdminAlert('QR 그룹 ' + groupNo + ' 지급 잉크가 저장되었습니다.', 'success');
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

  function findCodeById(id) {
    id = Number(id || 0);
    for (var i = 0; i < codesAll.length; i++) {
      if (Number(codesAll[i].id || 0) === id) return codesAll[i];
    }
    return null;
  }
  function openLabelPrint(list) {
    if (!window.LabelUpQrLabelPrint || typeof window.LabelUpQrLabelPrint.open !== 'function') {
      showAdminAlert('인쇄 미리보기를 불러오지 못했습니다.', 'error');
      return;
    }
    var groupNo = Number(codesGroupNo || 0);
    if (!groupNo && list && list[0] && list[0].group_no) {
      groupNo = Number(list[0].group_no || 0);
    }
    window.LabelUpQrLabelPrint.open(list, { groupNo: groupNo || 0 });
  }

  var codesBody = document.getElementById('qrCodesBody');
  if (codesBody) {
    codesBody.addEventListener('click', function (e) {
      var btn = e.target.closest('.js-qr-code-print');
      if (!btn) return;
      var item = findCodeById(btn.getAttribute('data-code-id'));
      if (!item) {
        showAdminAlert('쿠폰을 찾을 수 없습니다.', 'error');
        return;
      }
      openLabelPrint([item]);
    });
    codesBody.addEventListener('change', function (e) {
      if (e.target && e.target.classList && e.target.classList.contains('js-qr-code-check')) {
        syncSelectAll();
      }
    });
  }
  var selectAllChk = document.getElementById('qrCodesSelectAll');
  if (selectAllChk) {
    selectAllChk.addEventListener('change', function () {
      var on = !!selectAllChk.checked;
      document.querySelectorAll('#qrCodesBody .js-qr-code-check:not(:disabled)').forEach(function (el) {
        el.checked = on;
      });
      selectAllChk.indeterminate = false;
    });
  }
  var unusedChk = document.getElementById('qrCodesUnusedOnly');
  if (unusedChk) {
    unusedChk.addEventListener('change', paintCodesTable);
  }
  var unprintedChk = document.getElementById('qrCodesUnprintedOnly');
  if (unprintedChk) {
    unprintedChk.addEventListener('change', paintCodesTable);
  }
  var printUnprintedBtn = document.getElementById('qrCodesPrintUnprintedBtn');
  if (printUnprintedBtn) {
    printUnprintedBtn.addEventListener('click', function () {
      var list = codesAll.filter(function (it) {
        if (codePrinted(it)) return false;
        if (unusedOnly() && codeStatus(it) !== 'unused') return false;
        return true;
      });
      if (!list.length) {
        showCodesMsg('미인쇄 쿠폰이 없습니다.', 'error');
        showAdminAlert('미인쇄 쿠폰이 없습니다.', 'error');
        return;
      }
      showCodesMsg('');
      openLabelPrint(list);
    });
  }
  var deleteBtn = document.getElementById('qrCodesDeleteBtn');
  if (deleteBtn) {
    deleteBtn.addEventListener('click', async function () {
      var ids = selectedCodeIds();
      if (!ids.length) {
        showCodesMsg('삭제할 미사용 QR을 먼저 체크해 주세요.', 'error');
        showAdminAlert('삭제할 미사용 QR을 선택해 주세요.', 'error');
        return;
      }
      if (!window.confirm('선택한 미사용 QR ' + ids.length.toLocaleString() + '개를 삭제할까요?\n삭제된 코드는 목록에서 제거되며 더 이상 인쇄·사용할 수 없습니다.')) {
        return;
      }
      deleteBtn.disabled = true;
      showCodesMsg('삭제 중…', 'ok');
      try {
        var res = await AdminAPI.post(deleteCodesUrl, { ids: ids });
        var data = res.data || {};
        var deletedIds = (data.deleted_ids || ids).map(Number);
        var idSet = {};
        deletedIds.forEach(function (id) { idSet[id] = true; });
        codesAll = codesAll.filter(function (it) { return !idSet[Number(it.id || 0)]; });
        paintCodesTable();
        (data.groups || []).forEach(function (g) {
          var listRow = rowByGroup(String(g.group_no || ''));
          if (!listRow) return;
          listRow.setAttribute('data-generated', String(g.generated || 0));
          listRow.setAttribute('data-printed', String(g.printed || 0));
          paintGeneratedCell(listRow);
        });
        if (codesGroupNo && !(data.groups || []).length) {
          var row = rowByGroup(String(codesGroupNo));
          if (row) {
            row.setAttribute('data-generated', String(codesAll.length));
            row.setAttribute('data-printed', String(codesAll.filter(function (it) { return codePrinted(it); }).length));
            paintGeneratedCell(row);
          }
        }
        var msg = res.message || ((data.deleted || deletedIds.length) + '개 삭제되었습니다.');
        showCodesMsg(msg, 'ok');
        showAdminAlert(msg, 'success');
      } catch (err) {
        var errMsg = (err && err.message) || '삭제 실패';
        showCodesMsg(errMsg, 'error');
        showAdminAlert(errMsg, 'error');
      } finally {
        deleteBtn.disabled = false;
      }
    });
  }
  var csvBtn = document.getElementById('qrCodesCsvBtn');
  if (csvBtn) csvBtn.addEventListener('click', function () { downloadCodesCsv(visibleCodes()); });
  var copyBtn = document.getElementById('qrCodesCopyBtn');
  if (copyBtn) copyBtn.addEventListener('click', function () { copyCodesUrls(visibleCodes()); });
  document.addEventListener('qr-coupons-printed', function (ev) {
    var ids = ((ev.detail && ev.detail.ids) || []).map(Number);
    var now = new Date();
    var stamp = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' +
      String(now.getDate()).padStart(2, '0') + ' ' + String(now.getHours()).padStart(2, '0') + ':' +
      String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0');
    codesAll.forEach(function (it) {
      if (ids.indexOf(Number(it.id || 0)) >= 0) {
        it.printed = true;
        it.printed_at = it.printed_at || stamp;
        it.print_count = Number(it.print_count || 0) + 1;
      }
    });
    paintCodesTable();
    if (codesGroupNo) {
      var listRow = rowByGroup(String(codesGroupNo));
      if (listRow) {
        var printedNow = codesAll.filter(function (it) { return it.printed; }).length;
        listRow.setAttribute('data-printed', String(printedNow));
        paintGeneratedCell(listRow);
      }
    }
  });
})();
</script>
<script>
window.LABELUP_QR_PRINT_TPL = {
  loadUrl: <?= json_encode(url('api/admin/qr-coupons/print-template'), JSON_UNESCAPED_SLASHES) ?>,
  saveUrl: <?= json_encode(url('api/admin/qr-coupons/print-template/save'), JSON_UNESCAPED_SLASHES) ?>,
  markPrintedUrl: <?= json_encode($markPrintedUrl, JSON_UNESCAPED_SLASHES) ?>,
  papersUrl: <?= json_encode(url('api/shop/editor-papers'), JSON_UNESCAPED_SLASHES) ?>,
  sampleUrl: <?= json_encode($sampleCouponUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
  sampleCode: <?= json_encode($sampleCouponCode, JSON_UNESCAPED_UNICODE) ?>
};
</script>
<script src="<?= js('qr-print-template.js') ?>"></script>
<script src="<?= js('qr-label-print.js') ?>"></script>
