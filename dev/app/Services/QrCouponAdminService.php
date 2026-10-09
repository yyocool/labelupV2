<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\QrCouponRepository;
use App\Repositories\ShopRepository;
use RuntimeException;
use Throwable;

final class QrCouponAdminService
{
    private QrCouponRepository $repo;
    private ?ShopRepository $shopRepo = null;

    public function __construct(?QrCouponRepository $repo = null)
    {
        $this->repo = $repo ?? new QrCouponRepository();
    }

    // 출력템플릿의 용지 배치를 규격에서 다시 읽을 때만 쓴다. 쓸 일이 없는 요청에서는
    // 연결을 만들지 않도록 처음 쓰는 순간에 만든다.
    private function shopRepo(): ShopRepository
    {
        return $this->shopRepo ??= new ShopRepository();
    }

    public function couponPagePath(): string
    {
        return 'qr-coupon';
    }

    public function couponPageAbsoluteUrl(array $query = []): string
    {
        return qr_public_url($this->couponPagePath(), $query);
    }

    public function previewGroupUrl(array $group): string
    {
        // iframe 미리보기는 동일 출처 상대경로 (APP_URL이 http여도 HTTPS 관리자에서 혼합콘텐츠 차단 안 됨)
        $url = url($this->couponPagePath());
        $query = [
            'g' => (int) ($group['group_no'] ?? 0),
            'cat' => (string) ($group['category_slug'] ?? ''),
            'sheets' => (int) ($group['sheets_per_pack'] ?? 0),
            'preview' => 1,
        ];
        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public function groupLandingUrl(array $group): string
    {
        return $this->couponPageAbsoluteUrl([
            'g' => (int) ($group['group_no'] ?? 0),
            'cat' => (string) ($group['category_slug'] ?? ''),
            'sheets' => (int) ($group['sheets_per_pack'] ?? 0),
        ]);
    }

    public function uniqueCouponUrl(array $group, string $code): string
    {
        return $this->couponPageAbsoluteUrl([
            'g' => (int) ($group['group_no'] ?? 0),
            'cat' => (string) ($group['category_slug'] ?? ''),
            'sheets' => (int) ($group['sheets_per_pack'] ?? 0),
            'code' => $code,
        ]);
    }

    /**
     * @return array{
     *   rows: list<array<string, mixed>>,
     *   category_count: int,
     *   group_count: int,
     *   product_count: int,
     *   generated_qr_count: int,
     *   printed_qr_count: int,
     *   coupon_page_url: string
     * }
     */
    public function getGroupMatrix(): array
    {
        try {
            $groups = $this->repo->allGroups();
        } catch (Throwable $e) {
            throw new RuntimeException('QR 그룹 테이블이 없습니다. 마이그레이션을 실행해 주세요.', 0, $e);
        }

        $categoryCounts = [];
        foreach ($groups as $g) {
            $no = (int) ($g['category_no'] ?? 0);
            $categoryCounts[$no] = ($categoryCounts[$no] ?? 0) + 1;
        }

        $seenCategory = [];
        $rows = [];
        $productTotal = 0;
        $qrTotal = 0;
        $printedTotal = 0;
        foreach ($groups as $g) {
            $catNo = (int) ($g['category_no'] ?? 0);
            $productCount = (int) ($g['product_count'] ?? 0);
            $generated = (int) ($g['generated_qr_count'] ?? 0);
            $printed = (int) ($g['printed_qr_count'] ?? 0);
            $productTotal += $productCount;
            $qrTotal += $generated;
            $printedTotal += $printed;
            $landingUrl = $this->groupLandingUrl($g);
            $row = [
                'group_no' => (int) ($g['group_no'] ?? 0),
                'category_no' => $catNo,
                'category_name' => (string) ($g['category_name'] ?? ''),
                'category_slug' => (string) ($g['category_slug'] ?? ''),
                'sheets_per_pack' => (int) ($g['sheets_per_pack'] ?? 0),
                'list_price' => (int) ($g['list_price'] ?? 0),
                'credit_amount' => isset($g['credit_amount']) && $g['credit_amount'] !== null
                    ? (int) $g['credit_amount']
                    : null,
                'color_hex' => (string) ($g['color_hex'] ?? '#9b1c1c'),
                'product_count' => $productCount,
                'generated_qr_count' => $generated,
                'printed_qr_count' => $printed,
                'coupon_page_url' => $landingUrl,
                'preview_url' => $this->previewGroupUrl($g),
                'shop_category_id' => isset($g['shop_category_id']) && $g['shop_category_id'] !== null
                    ? (int) $g['shop_category_id']
                    : null,
                'show_category' => !isset($seenCategory[$catNo]),
                'category_rowspan' => $categoryCounts[$catNo] ?? 1,
            ];
            $row = array_merge($row, self::payoutEconomics((int) $row['list_price']));
            $seenCategory[$catNo] = true;
            $rows[] = $row;
        }

        return [
            'rows' => $rows,
            'category_count' => count($categoryCounts),
            'group_count' => count($rows),
            'product_count' => $productTotal,
            'generated_qr_count' => $qrTotal,
            'printed_qr_count' => $printedTotal,
            'coupon_page_url' => $this->couponPageAbsoluteUrl(),
            'ai_model' => AiCostService::chatModels()['normal'],
            'usd_krw' => AiCostService::usdKrwRate(),
        ];
    }

    /**
     * 정상 소비자가 기준 제조단가·최대지급액·토큰·잉크.
     * 제조단가 = 소비자가의 20%, 최대지급액 = 제조단가의 3%.
     * 토큰량은 기본 ChatGPT 모델의 입력 75%·출력 25% 혼합 단가.
     * 잉크환산은 1원 = 10 C (CreditService::CREDITS_PER_KRW).
     *
     * @return array{manufacturing_cost:int,max_payout:int,token_amount:int,credit_equivalent:int}
     */
    public static function payoutEconomics(int $listPrice): array
    {
        $listPrice = max(0, $listPrice);
        $manufacturing = (int) round($listPrice * 0.20);
        $maxPayout = (int) round($manufacturing * 0.03);

        return [
            'manufacturing_cost' => $manufacturing,
            'max_payout' => $maxPayout,
            'token_amount' => AiCostService::tokensForKrw((float) $maxPayout),
            'credit_equivalent' => CreditService::krwToCredits($maxPayout),
        ];
    }

    public function saveCreditAmount(int $groupNo, mixed $creditAmount): array
    {
        if ($groupNo < 1) {
            throw new RuntimeException('QR 그룹 번호가 올바르지 않습니다.');
        }

        $existing = $this->repo->findByGroupNo($groupNo);
        if (!$existing) {
            throw new RuntimeException('QR 그룹을 찾을 수 없습니다.');
        }

        $normalized = null;
        if ($creditAmount !== null && $creditAmount !== '') {
            if (!is_numeric($creditAmount)) {
                throw new RuntimeException('지급 잉크는 숫자로 입력해 주세요.');
            }
            $normalized = (int) $creditAmount;
            if ($normalized < 0) {
                throw new RuntimeException('지급 잉크는 0 이상이어야 합니다.');
            }
        }

        $this->repo->updateCreditAmount($groupNo, $normalized);

        return [
            'group_no' => $groupNo,
            'credit_amount' => $normalized,
        ];
    }

    public function generateCodes(int $groupNo, int $quantity, ?int $adminId): array
    {
        $group = $this->repo->findByGroupNo($groupNo);
        if (!$group) {
            throw new RuntimeException('QR 그룹을 찾을 수 없습니다.');
        }

        $landingUrl = $this->groupLandingUrl($group);
        // 배치 기준 URL = 그룹 랜딩(쿠폰페이지+카테고리+매수)
        $result = $this->repo->createBatch($group, $landingUrl, $quantity, $adminId);
        $codes = $result['codes'];
        $first = $codes[0] ?? null;

        return [
            'batch_id' => $result['batch_id'],
            'group_no' => $groupNo,
            'quantity' => $quantity,
            'category_slug' => (string) $group['category_slug'],
            'category_name' => (string) $group['category_name'],
            'sheets_per_pack' => (int) $group['sheets_per_pack'],
            'group_landing_url' => $landingUrl,
            'coupon_page_url' => is_array($first) ? (string) ($first['coupon_page_url'] ?? '') : $this->uniqueCouponUrl($group, sprintf('LU%02d-XXXXXXXX', $groupNo)),
            'codes' => $codes,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function generationHistory(int $groupNo): array
    {
        if (!$this->repo->findByGroupNo($groupNo)) {
            throw new RuntimeException('QR 그룹을 찾을 수 없습니다.');
        }
        return $this->repo->batchesByGroup($groupNo);
    }

    /**
     * @return array{batch_id:int, quantity:int, items:list<array<string, mixed>>}
     */
    public function batchCodes(int $batchId): array
    {
        if ($batchId < 1) {
            throw new RuntimeException('배치 ID가 올바르지 않습니다.');
        }

        $items = $this->repo->codesByBatch($batchId, 2000);
        if ($items === []) {
            throw new RuntimeException('해당 배치의 쿠폰코드를 찾을 수 없습니다.');
        }

        $normalized = $this->normalizeCodeRows($items);

        return [
            'batch_id' => $batchId,
            'quantity' => count($normalized),
            'items' => $normalized,
        ];
    }

    /**
     * @return array{group_no:int, quantity:int, printed_count:int, unprinted_count:int, items:list<array<string, mixed>>}
     */
    public function groupCodes(int $groupNo): array
    {
        if ($groupNo < 1 || !$this->repo->findByGroupNo($groupNo)) {
            throw new RuntimeException('QR 그룹을 찾을 수 없습니다.');
        }

        $normalized = $this->normalizeCodeRows($this->repo->codesByGroup($groupNo, 2000));
        $printed = 0;
        foreach ($normalized as $row) {
            if (!empty($row['printed'])) {
                $printed++;
            }
        }

        $group = $this->repo->findByGroupNo($groupNo) ?? [];
        $categoryNo = (int) ($group['category_no'] ?? 0);

        return [
            'group_no' => $groupNo,
            'category_no' => $categoryNo,
            'category_name' => (string) ($group['category_name'] ?? ''),
            'quantity' => count($normalized),
            'printed_count' => $printed,
            'unprinted_count' => count($normalized) - $printed,
            'items' => $normalized,
        ];
    }

    /**
     * @param list<int> $ids
     * @return array{updated:int, ids:list<int>}
     */
    public function markPrinted(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id) => $id > 0)));
        if ($ids === []) {
            throw new RuntimeException('인쇄할 쿠폰이 없습니다.');
        }
        try {
            $updated = $this->repo->markPrinted($ids);
        } catch (Throwable $e) {
            throw new RuntimeException('인쇄 상태 컬럼이 없습니다. 마이그레이션을 실행해 주세요.', 0, $e);
        }
        return [
            'updated' => $updated,
            'ids' => $ids,
        ];
    }

    /**
     * 미사용 QR만 삭제 (사용·중지 코드는 삭제 불가).
     *
     * @param list<int> $ids
     * @return array{
     *   deleted:int,
     *   skipped:int,
     *   deleted_ids:list<int>,
     *   groups:list<array{group_no:int, generated:int, printed:int}>
     * }
     */
    public function deleteCodes(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id) => $id > 0)));
        if ($ids === []) {
            throw new RuntimeException('삭제할 쿠폰을 선택해 주세요.');
        }

        $result = $this->repo->deleteUnusedByIds($ids);
        if ((int) ($result['deleted'] ?? 0) <= 0) {
            throw new RuntimeException('삭제할 수 있는 미사용 쿠폰이 없습니다. (사용·중지 코드는 삭제할 수 없습니다)');
        }

        $groups = [];
        foreach ($result['group_nos'] as $groupNo) {
            $counts = $this->repo->groupCodeCounts((int) $groupNo);
            $groups[] = [
                'group_no' => (int) $groupNo,
                'generated' => (int) $counts['generated'],
                'printed' => (int) $counts['printed'],
            ];
        }

        return [
            'deleted' => (int) $result['deleted'],
            'skipped' => (int) $result['skipped'],
            'deleted_ids' => $result['deleted_ids'],
            'groups' => $groups,
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function normalizeCodeRows(array $items): array
    {
        $normalized = [];
        foreach ($items as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $url = $code !== '' ? $this->uniqueCouponUrl($row, $code) : trim((string) ($row['coupon_page_url'] ?? ''));
            $printedAt = $row['printed_at'] ?? null;
            $status = (string) ($row['status'] ?? 'unused');
            $normalized[] = [
                'id' => (int) ($row['id'] ?? 0),
                'batch_id' => (int) ($row['batch_id'] ?? 0),
                'group_no' => (int) ($row['group_no'] ?? 0),
                'category_no' => (int) ($row['category_no'] ?? 0),
                'code' => $code,
                'coupon_page_url' => $url,
                'status' => $status,
                'used_at' => $row['used_at'] ?? null,
                'printed' => $printedAt !== null && $printedAt !== '',
                'printed_at' => $printedAt,
                'print_count' => (int) ($row['print_count'] ?? 0),
                'created_at' => $row['created_at'] ?? null,
            ];
        }
        return $normalized;
    }

    public static function templateKeyForGroup(int $groupNo): string
    {
        return $groupNo > 0 ? ('group-' . $groupNo) : 'default';
    }

    /** @deprecated 분류 단위 템플릿 — 하위 호환(읽기 폴백)용 */
    public static function templateKeyForCategory(int $categoryNo): string
    {
        return $categoryNo > 0 ? ('cat-' . $categoryNo) : 'default';
    }

    public static function groupNoFromTemplateKey(string $key): int
    {
        if (preg_match('/^group-(\d+)$/', $key, $m) === 1) {
            return (int) $m[1];
        }
        return 0;
    }

    public static function categoryNoFromTemplateKey(string $key): int
    {
        if (preg_match('/^cat-(\d+)$/', $key, $m) === 1) {
            return (int) $m[1];
        }
        return 0;
    }

    public function normalizeTemplateKey(string $key): string
    {
        $key = trim($key);
        if ($key === '' || $key === 'default') {
            return 'default';
        }
        if (preg_match('/^group-(\d{1,3})$/', $key, $m) === 1) {
            $no = (int) $m[1];
            if ($no >= 1 && $no <= 99) {
                return 'group-' . $no;
            }
        }
        // 구버전 분류 키 — 조회만 허용
        if (preg_match('/^cat-(\d{1,3})$/', $key, $m) === 1) {
            $no = (int) $m[1];
            if ($no >= 1 && $no <= 99) {
                return 'cat-' . $no;
            }
        }
        throw new RuntimeException('출력템플릿 키가 올바르지 않습니다.');
    }

    public function resolveTemplateKeyFromRequest(?string $key, mixed $groupNo = null, mixed $categoryNo = null): string
    {
        $key = trim((string) ($key ?? ''));
        if ($key !== '') {
            return $this->normalizeTemplateKey($key);
        }
        $group = (int) ($groupNo ?? 0);
        if ($group > 0) {
            return self::templateKeyForGroup($group);
        }
        $cat = (int) ($categoryNo ?? 0);
        if ($cat > 0) {
            return self::templateKeyForCategory($cat);
        }
        return 'default';
    }

    /** @return list<array<string, mixed>> */
    public function usageHistory(int $groupNo): array
    {
        if (!$this->repo->findByGroupNo($groupNo)) {
            throw new RuntimeException('QR 그룹을 찾을 수 없습니다.');
        }
        return $this->normalizeUsageRows($this->repo->usageByGroup($groupNo));
    }

    /**
     * @return array{items: list<array<string,mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function grantHistoryAll(string $search = '', int $page = 1, int $perPage = 20): array
    {
        $result = $this->repo->usageHistoryAll($search, $page, $perPage);
        $result['items'] = $this->normalizeUsageRows($result['items'] ?? []);
        return $result;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function normalizeUsageRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $credit = $row['credit_amount'] ?? null;
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'code' => (string) ($row['code'] ?? ''),
                'group_no' => (int) ($row['group_no'] ?? 0),
                'category_name' => (string) ($row['category_name'] ?? ''),
                'category_slug' => (string) ($row['category_slug'] ?? ''),
                'sheets_per_pack' => (int) ($row['sheets_per_pack'] ?? 0),
                'credit_amount' => ($credit === null || $credit === '') ? null : (int) $credit,
                'list_price' => isset($row['list_price']) ? (int) $row['list_price'] : null,
                'used_by' => (int) ($row['used_by'] ?? $row['user_id'] ?? 0),
                'used_by_name' => (string) ($row['used_by_name'] ?? ''),
                'used_by_email' => (string) ($row['used_by_email'] ?? ''),
                'used_at' => $row['used_at'] ?? null,
                'status' => (string) ($row['status'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * @return array{
     *   key: string,
     *   name: string,
     *   paper: array<string, mixed>,
     *   objects: list<array<string, mixed>>,
     *   settings: array<string, mixed>,
     *   persisted: bool,
     *   updated_at: string|null
     * }
     */
    public function getPrintTemplate(string $key = 'default'): array
    {
        $key = $this->normalizeTemplateKey($key);
        $groupNo = self::groupNoFromTemplateKey($key);
        $categoryNo = self::categoryNoFromTemplateKey($key);
        $row = $this->repo->findPrintTemplate($key);
        $fallbackFrom = null;

        // 그룹 템플릿이 없으면 구버전 분류 템플릿 → 공통 템플릿 순으로 폴백
        if ($row === null && $groupNo > 0) {
            $group = $this->repo->findByGroupNo($groupNo);
            $legacyCat = (int) ($group['category_no'] ?? 0);
            if ($legacyCat > 0) {
                $legacyKey = self::templateKeyForCategory($legacyCat);
                $legacyRow = $this->repo->findPrintTemplate($legacyKey);
                if ($legacyRow !== null) {
                    $row = $legacyRow;
                    $fallbackFrom = $legacyKey;
                }
            }
        }
        if ($row === null && $key !== 'default') {
            $row = $this->repo->findPrintTemplate('default');
            $fallbackFrom = $row !== null ? 'default' : null;
        }

        $displayName = $this->templateDisplayName($key, $groupNo, $categoryNo);

        if ($row === null) {
            $defaults = self::defaultPrintTemplate();
            $defaults['key'] = $key;
            $defaults['group_no'] = $groupNo;
            $defaults['category_no'] = $categoryNo;
            $defaults['name'] = $displayName;
            $defaults['persisted'] = false;
            $defaults['fallback_from'] = null;
            return $defaults;
        }

        return [
            'key' => $key,
            'group_no' => $groupNo,
            'category_no' => $categoryNo,
            'name' => $fallbackFrom !== null
                ? $displayName
                : (string) ($row['name'] ?? $displayName),
            'paper' => $this->refreshPaperFromSpec($this->decodeJsonMap($row['paper_json'] ?? null)),
            'objects' => $this->decodeJsonList($row['objects_json'] ?? null),
            'settings' => $this->decodeJsonMap($row['settings_json'] ?? null),
            'persisted' => $fallbackFrom === null,
            'fallback_from' => $fallbackFrom,
            'updated_at' => $fallbackFrom === null ? ($row['updated_at'] ?? null) : null,
        ];
    }

    /**
     * 저장해 둔 용지의 배치값을 상품 규격(label_specs)에서 다시 읽어 맞춘다.
     *
     * 출력템플릿에는 용지 배치가 통째로 저장되는데, 이 값은 규격을 베껴 둔 사본일 뿐
     * 원본이 아니다. 규격이 관리자에서 바뀌면 사본은 그대로 묵고, 인쇄물이 라벨지
     * 칼선과 어긋난다. 템플릿을 내보낼 때마다 규격을 다시 읽어 덮는다.
     *
     * 예전 템플릿을 고치는 길이기도 하다. 편집 화면이 용지를 고를 때 규격의 여백을
     * 버리고 A4 복판에 다시 앉히던 시절에 저장된 것들은 위쪽 여백이 틀린 채로 남아
     * 있는데, 여기서 읽어 오면 다시 저장하지 않아도 바로잡힌다.
     *
     * 상품이 지워졌거나 규격에 열·행이 비어 있으면 저장된 값을 그대로 둔다.
     *
     * @param array<string, mixed> $paper
     * @return array<string, mixed>
     */
    private function refreshPaperFromSpec(array $paper): array
    {
        try {
            $row = $this->findPaperProduct($paper);
        } catch (Throwable) {
            return $paper;
        }
        if ($row === null) {
            return $paper;
        }

        $cols = (int) ($row['columns_count'] ?? 0);
        $rows = (int) ($row['rows_count'] ?? 0);
        $lw = (float) ($row['width_mm'] ?? 0);
        $lh = (float) ($row['height_mm'] ?? 0);
        if ($cols < 1 || $rows < 1 || $lw <= 0 || $lh <= 0) {
            return $paper;
        }

        $hGap = max(0.0, (float) ($row['h_gap_mm'] ?? 0));
        $vGap = max(0.0, (float) ($row['v_gap_mm'] ?? 0));
        $usedW = $lw * $cols + $hGap * ($cols - 1);
        $usedH = $lh * $rows + $vGap * ($rows - 1);

        // 여백 칸은 비어 있을 수 있다. 비었으면 용지 복판에 앉히고, 적혀 있으면 그 값을 쓴다.
        // 0.0 과 "값 없음"은 다르므로 isset 으로 가른다.
        $left = isset($row['left_margin_mm']) ? (float) $row['left_margin_mm'] : null;
        $top = isset($row['top_margin_mm']) ? (float) $row['top_margin_mm'] : null;

        [$pageW, $pageH] = self::resolvePageSizeMm(
            $row['paper_size'] ?? null,
            $usedW + ($left ?? 0.0),
            $usedH + ($top ?? 0.0)
        );

        return array_merge($paper, [
            'paperSize' => (string) ($row['paper_size'] ?? ''),
            'paperWidthMm' => round($pageW, 3),
            'paperHeightMm' => round($pageH, 3),
            'labelWidthMm' => round($lw, 3),
            'labelHeightMm' => round($lh, 3),
            'columns' => $cols,
            'rows' => $rows,
            'leftMarginMm' => round($left ?? max(0.0, ($pageW - $usedW) / 2), 3),
            'topMarginMm' => round($top ?? max(0.0, ($pageH - $usedH) / 2), 3),
            'hGapMm' => round($hGap, 3),
            'vGapMm' => round($vGap, 3),
            // 칸 수는 열×행이 진실이다. 규격의 labels_per_sheet 가 이와 어긋난 항목이 있고,
            // 그 값을 믿으면 쪽 수와 실제로 찍히는 칸 수가 달라져 쿠폰이 샌다.
            'labelsPerSheet' => $cols * $rows,
        ]);
    }

    /**
     * 템플릿에 적힌 용지가 가리키는 상품을 찾는다.
     *
     * 상품번호로 먼저 찾고, 없으면 용지번호(sku)로 한 번 더 찾는다. 상품번호를 함께
     * 저장하기 전에 만들어진 템플릿이 용지번호만 들고 있기 때문이다.
     *
     * @param array<string, mixed> $paper
     * @return array<string, mixed>|null
     */
    private function findPaperProduct(array $paper): ?array
    {
        $productId = (int) ($paper['productId'] ?? 0);
        if ($productId > 0) {
            return $this->shopRepo()->findActiveProduct($productId);
        }

        $sku = trim((string) ($paper['sku'] ?? $paper['paperNo'] ?? ''));
        if ($sku === '') {
            return null;
        }
        $found = $this->shopRepo()->findProductBySku($sku);
        $foundId = (int) ($found['id'] ?? 0);

        // findProductBySku 는 규격을 붙여 오지 않는다. 배치값을 받으려면 한 번 더 읽어야 한다.
        return $foundId > 0 ? $this->shopRepo()->findActiveProduct($foundId) : null;
    }

    /** 규격에 적히는 표준 용지 크기. 편집기 PaperCatalog.StandardPaperSizes 와 같은 표다. */
    private const PAGE_SIZES_MM = [
        'A3' => [297.0, 420.0],
        'A4' => [210.0, 297.0],
        'A5' => [148.0, 210.0],
        'A6' => [105.0, 148.0],
        'B4' => [257.0, 364.0],
        'B5' => [182.0, 257.0],
        'B6' => [128.0, 182.0],
        'LETTER' => [215.9, 279.4],
        'LEGAL' => [215.9, 355.6],
    ];

    /**
     * "A4", "A4 가로", "210x297" 을 mm 로 바꾼다.
     * 이름을 모르면 A4 로 두되, 배치가 A4 보다 크면 잘리지 않게 배치에 맞춰 늘린다.
     *
     * @return array{0: float, 1: float}
     */
    private static function resolvePageSizeMm(mixed $name, float $needW, float $needH): array
    {
        $key = trim((string) ($name ?? ''));
        if ($key !== '') {
            $landscape = preg_match('/가로|landscape/iu', $key) === 1;
            $key = trim((string) preg_replace('/가로|세로|landscape|portrait/iu', '', $key));
            $hit = self::PAGE_SIZES_MM[strtoupper($key)] ?? null;
            if ($hit !== null) {
                return $landscape ? [$hit[1], $hit[0]] : [$hit[0], $hit[1]];
            }
            $parts = preg_split('/[xX×*]/u', $key, 2) ?: [];
            if (count($parts) === 2) {
                $w = (float) preg_replace('/[^\d.]/', '', $parts[0]);
                $h = (float) preg_replace('/[^\d.]/', '', $parts[1]);
                if ($w > 0 && $h > 0) {
                    return [$w, $h];
                }
            }
        }

        return [max(210.0, $needW), max(297.0, $needH)];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function savePrintTemplate(array $payload, ?int $adminId = null): array
    {
        $key = $this->resolveTemplateKeyFromRequest(
            (string) ($payload['key'] ?? ''),
            $payload['group_no'] ?? null,
            $payload['category_no'] ?? null
        );
        // 신규 저장은 그룹/공통만 허용 (분류 키로 저장 요청 시 그룹으로 안내)
        if (self::categoryNoFromTemplateKey($key) > 0 && self::groupNoFromTemplateKey($key) <= 0) {
            throw new RuntimeException('출력템플릿은 QR 그룹No. 단위로 저장합니다. 그룹 템플릿 버튼을 이용해 주세요.');
        }
        $groupNo = self::groupNoFromTemplateKey($key);
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            $name = $this->templateDisplayName($key, $groupNo, 0);
        }
        $paper = $payload['paper'] ?? null;
        $objects = $payload['objects'] ?? null;
        if (!is_array($paper) || $paper === []) {
            throw new RuntimeException('용지 정보가 없습니다.');
        }
        if (!is_array($objects)) {
            throw new RuntimeException('오브젝트 정보가 없습니다.');
        }
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];

        $saved = $this->repo->upsertPrintTemplate($key, $name, $paper, $objects, $settings, $adminId);
        $saved['persisted'] = true;
        $saved['group_no'] = $groupNo;
        $saved['category_no'] = 0;
        $saved['fallback_from'] = null;
        return $saved;
    }

    private function templateDisplayName(string $key, int $groupNo, int $categoryNo = 0): string
    {
        if ($groupNo > 0) {
            return '그룹 ' . $groupNo . ' 출력템플릿';
        }
        if ($categoryNo > 0) {
            return '분류 ' . $categoryNo . ' 출력템플릿';
        }
        if ($key === 'default') {
            return '공통 출력템플릿';
        }
        return 'QR 출력템플릿';
    }

    /**
     * @return array{
     *   key: string,
     *   name: string,
     *   paper: array<string, mixed>,
     *   objects: list<array<string, mixed>>,
     *   settings: array<string, mixed>,
     *   persisted: bool,
     *   updated_at: null
     * }
     */
    public static function defaultPrintTemplate(): array
    {
        return [
            'key' => 'default',
            'name' => 'QR 출력템플릿',
            'paper' => [
                'paperNo' => 'LU-3230',
                'name' => 'A4 70×36 mm',
                'sku' => 'LU-3230',
                'paperWidthMm' => 210,
                'paperHeightMm' => 297,
                'labelWidthMm' => 70,
                'labelHeightMm' => 36,
                'columns' => 2,
                'rows' => 7,
                'leftMarginMm' => 32.5,
                'topMarginMm' => 13.5,
                'hGapMm' => 5,
                'vGapMm' => 3,
                'shape' => 'roundrect',
                'labelsPerSheet' => 14,
            ],
            'objects' => [
                [
                    'id' => 'qr1',
                    'type' => 'qr',
                    'x' => 4,
                    'y' => 6,
                    'w' => 24,
                    'h' => 24,
                    'rotation' => 0,
                    'payload' => '{{coupon_url}}',
                    'opacity' => 1,
                ],
                [
                    'id' => 'code1',
                    'type' => 'text',
                    'x' => 30,
                    'y' => 7,
                    'w' => 36,
                    'h' => 10,
                    'rotation' => 0,
                    'text' => '{{coupon_code}}',
                    'fontFamily' => 'Pretendard',
                    'fontSize' => 9,
                    'fontWeight' => 800,
                    'align' => 'left',
                    'fill' => '#2E2A27',
                    'opacity' => 1,
                ],
                [
                    'id' => 'cap1',
                    'type' => 'text',
                    'x' => 30,
                    'y' => 18,
                    'w' => 36,
                    'h' => 12,
                    'rotation' => 0,
                    'text' => "스캔하고\n잉크 받기",
                    'fontFamily' => 'Pretendard',
                    'fontSize' => 8,
                    'fontWeight' => 700,
                    'align' => 'left',
                    'fill' => '#7B2840',
                    'opacity' => 1,
                ],
            ],
            'settings' => [
                'grid' => true,
                'snap' => true,
                'bg' => '#ffffff',
            ],
            'persisted' => false,
            'updated_at' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function decodeJsonMap(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @return list<array<string, mixed>> */
    private function decodeJsonList(mixed $raw): array
    {
        $decoded = $this->decodeJsonMap($raw);
        $list = [];
        foreach (array_values($decoded) as $item) {
            if (is_array($item)) {
                $list[] = $item;
            }
        }
        return $list;
    }
}
