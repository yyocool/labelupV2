<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShopRepository;
use RuntimeException;

final class ShopProductImportService
{
    private ShopRepository $repo;

    /** @var array<string, int> */
    private array $categoryCache = [];

    /** @var array<string, int> */
    private array $specCache = [];

    /** @var array<string, string> */
    private const GROUP_SLUGS = [
        '물류관리용 라벨' => 'logistics-label',
        '인덱스용라벨' => 'index-label',
        '주소용라벨' => 'address-label',
        '바코드용라벨' => 'barcode-label',
        '방수라벨' => 'waterproof-label',
        '광택라벨' => 'gloss-label',
        '반투명라벨' => 'translucent-label',
        '잉크젯 투명라벨' => 'inkjet-clear-label',
        '레이저 투명라벨' => 'laser-clear-label',
        '크라프트 라벨' => 'kraft-label',
        '정부문서' => 'government-doc',
        '보호용 필름' => 'protective-film',
        '파스텔 컬러 라벨' => 'pastel-color-label',
        '컬러라벨' => 'color-label',
    ];

    public function __construct()
    {
        $this->repo = new ShopRepository();
    }

    /** @param array<int, array<string, mixed>> $rows
     *  @return array{inserted:int, updated:int, categories:int, specs:int, skipped:int}
     */
    public function importRows(array $rows): array
    {
        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'categories' => 0,
            'specs' => 0,
            'skipped' => 0,
        ];

        $sort = 0;
        foreach ($rows as $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            if ($sku === '' || $name === '') {
                $stats['skipped']++;
                continue;
            }

            $group = trim((string) ($row['group'] ?? '라벨지'));
            if ($group === '') {
                $group = '라벨지';
            }

            $categoryId = $this->ensureCategory($group, $sort, $stats);
            $specId = $this->ensureSpec($row, $stats);
            $existing = $this->repo->findProductBySku($sku);

            $price = $this->resolvePrice($row);
            $salePrice = $this->resolveSalePrice($row, $price);

            $payload = [
                'id' => (int) ($existing['id'] ?? 0),
                'category_id' => $categoryId,
                'spec_id' => $specId,
                'name' => $name,
                'sku' => $sku,
                'price' => $price,
                'sale_price' => $salePrice,
                'stock_qty' => (int) ($row['stock_qty'] ?? 100),
                'status' => (string) ($row['status'] ?? 'active'),
                'description' => $this->buildDescription($row),
                'sort_order' => ++$sort,
            ];

            $this->repo->saveProduct($payload);
            if ($existing) {
                $stats['updated']++;
            } else {
                $stats['inserted']++;
            }
        }

        return $stats;
    }

    private function ensureCategory(string $group, int $sortOrder, array &$stats): int
    {
        if (isset($this->categoryCache[$group])) {
            return $this->categoryCache[$group];
        }

        $slug = self::GROUP_SLUGS[$group] ?? ('cat-' . substr(md5($group), 0, 10));
        $existing = $this->repo->findCategoryBySlug($slug);
        if ($existing) {
            $id = (int) $existing['id'];
            $this->categoryCache[$group] = $id;
            return $id;
        }

        $id = $this->repo->saveCategory([
            'name' => $group,
            'slug' => $slug,
            'sort_order' => max(1, $sortOrder),
            'is_active' => true,
        ]);
        $this->categoryCache[$group] = $id;
        $stats['categories']++;
        return $id;
    }

    /** @param array<string, mixed> $row */
    private function ensureSpec(array $row, array &$stats): ?int
    {
        [$width, $height] = $this->parseSize(
            (string) ($row['spec_mm'] ?? ''),
            (string) ($row['std_size'] ?? '')
        );
        $material = trim((string) ($row['material'] ?? $row['material_name'] ?? '라벨지'));
        if ($material === '') {
            $material = '라벨지';
        }
        $labels = $row['labels_per_sheet'] ?? null;
        $labelsInt = $labels !== null && $labels !== '' ? (int) $labels : null;

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        $key = implode('|', [
            number_format($width, 2, '.', ''),
            number_format($height, 2, '.', ''),
            $material,
            (string) ($labelsInt ?? ''),
        ]);
        if (isset($this->specCache[$key])) {
            return $this->specCache[$key];
        }

        $existing = $this->repo->findSpecMatch($width, $height, $material, $labelsInt);
        if ($existing) {
            $id = (int) $existing['id'];
            $this->specCache[$key] = $id;
            return $id;
        }

        $specName = sprintf(
            '%sx%smm %s%s',
            $this->trimNum($width),
            $this->trimNum($height),
            $material,
            $labelsInt ? " {$labelsInt}칸" : ''
        );

        $id = $this->repo->saveSpec([
            'name' => $specName,
            'width_mm' => $width,
            'height_mm' => $height,
            'material' => $material,
            'shape' => 'rect',
            'labels_per_sheet' => $labelsInt,
            'description' => trim((string) ($row['paper_size'] ?? '')),
            'is_active' => true,
        ]);
        $this->specCache[$key] = $id;
        $stats['specs']++;
        return $id;
    }

    /** @param array<string, mixed> $row */
    private function buildDescription(array $row): string
    {
        $lines = [];
        $map = [
            '품번' => $row['sku'] ?? '',
            '제품명' => $row['material_name'] ?? '',
            '제품규격' => $row['paper_size'] ?? '',
            '라벨수(칸)' => $row['labels_per_sheet'] ?? '',
            '표준치수' => $row['std_size'] ?? '',
            'Spec(mm)' => $row['spec_mm'] ?? '',
            '재질' => $row['material'] ?? '',
            '패키지' => $row['pack_size'] ?? '',
            '박스' => $row['box_size'] ?? '',
            '입수량' => $row['qty_per_box'] ?? '',
            'Sheets/PACK' => $row['sheets_per_pack'] ?? '',
            '바코드' => $row['barcode'] ?? '',
            '아트라No' => $row['art_no'] ?? '',
            '원산지' => $row['origin'] ?? '',
        ];

        foreach ($map as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $lines[] = $label . ': ' . $value;
        }

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $row */
    private function resolvePrice(array $row): int
    {
        $price = $this->toInt($row['price'] ?? null);
        if ($price > 0) {
            return $price;
        }

        $sale = $this->toInt($row['sale_price'] ?? null);
        if ($sale > 0) {
            return (int) round($sale * 1.25);
        }

        return 10000;
    }

    /** @param array<string, mixed> $row */
    private function resolveSalePrice(array $row, int $price): ?int
    {
        $sale = $this->toInt($row['sale_price'] ?? null);
        if ($sale > 0) {
            return $sale;
        }
        if ($price >= 8000) {
            return (int) round($price * 0.8);
        }
        return null;
    }

    private function parseSize(string $primary, string $fallback): array
    {
        foreach ([$primary, $fallback] as $text) {
            if ($text !== '' && preg_match('/([\d.]+)\s*[x×X]\s*([\d.]+)/u', $text, $m)) {
                return [(float) $m[1], (float) $m[2]];
            }
        }
        return [0.0, 0.0];
    }

    private function trimNum(float $num): string
    {
        if (abs($num - round($num)) < 0.001) {
            return (string) (int) round($num);
        }
        return rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
    }

    private function toInt(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            return (int) round((float) $value);
        }
        return 0;
    }
}
