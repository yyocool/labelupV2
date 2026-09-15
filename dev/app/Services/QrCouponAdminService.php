<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\QrCouponRepository;
use RuntimeException;
use Throwable;

final class QrCouponAdminService
{
    private QrCouponRepository $repo;

    public function __construct(?QrCouponRepository $repo = null)
    {
        $this->repo = $repo ?? new QrCouponRepository();
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
        $url = absolute_url($this->couponPagePath());
        $query = [
            'g' => (int) ($group['group_no'] ?? 0),
            'cat' => (string) ($group['category_slug'] ?? ''),
            'sheets' => (int) ($group['sheets_per_pack'] ?? 0),
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
                throw new RuntimeException('지급 크레딧은 숫자로 입력해 주세요.');
            }
            $normalized = (int) $creditAmount;
            if ($normalized < 0) {
                throw new RuntimeException('지급 크레딧은 0 이상이어야 합니다.');
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

        return [
            'group_no' => $groupNo,
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
        $row = $this->repo->findPrintTemplate($key);
        if ($row === null) {
            $defaults = self::defaultPrintTemplate();
            $defaults['persisted'] = false;
            return $defaults;
        }

        return [
            'key' => (string) ($row['template_key'] ?? $key),
            'name' => (string) ($row['name'] ?? 'QR 출력템플릿'),
            'paper' => $this->decodeJsonMap($row['paper_json'] ?? null),
            'objects' => $this->decodeJsonList($row['objects_json'] ?? null),
            'settings' => $this->decodeJsonMap($row['settings_json'] ?? null),
            'persisted' => true,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function savePrintTemplate(array $payload, ?int $adminId = null): array
    {
        $key = trim((string) ($payload['key'] ?? 'default'));
        if ($key === '') {
            $key = 'default';
        }
        $name = trim((string) ($payload['name'] ?? 'QR 출력템플릿'));
        if ($name === '') {
            $name = 'QR 출력템플릿';
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
        return $saved;
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
                    'text' => "스캔하고\n크레딧 받기",
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
