<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShopRepository;

/**
 * 라비가 자료(엑셀·CSV)로 라벨을 짤 때 고를 용지 후보.
 *
 * 예전에는 LU-3102·LU-3230 같은 품번과 치수를 이 코드에 적어 두고 썼는데, 그 품번은
 * 쇼핑몰에서 팔지 않는 것이라 추천을 받아도 살 수가 없었다. 그래서 편집기 용지선택이
 * 쓰는 것과 같은 자리(shop_products + label_specs)에서 읽어 쓴다.
 *
 * 용도별 기준 치수만 코드에 두고, 실제로 고르는 것은 그 치수에 가장 가까운 판매 용지다.
 * 규격이 바뀌거나 상품이 늘어도 여기 고칠 것이 없다.
 */
final class AiPaperCatalogService
{
    /**
     * 용도마다 바라는 라벨 치수(mm). 쓰임새에서 온 수라 코드에 둔다.
     * 배송은 주소가 들어갈 만큼 넓어야 하고, 재고는 한 장에 많이 뽑는 쪽이 낫다.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    private const USE_TARGET_MM = [
        'shipping'  => [100.0, 50.0],
        'logistics' => [100.0, 50.0],
        'packing'   => [84.0, 58.0],
        'hangtag'   => [84.0, 58.0],
        'picking'   => [70.0, 36.0],
        'product'   => [70.0, 36.0],
        'general'   => [70.0, 36.0],
        'inventory' => [50.0, 30.0],
    ];

    /** 프롬프트에 적어 주는 용도 설명. 여기 적은 차례대로 안내문이 나간다. */
    private const USE_LABELS = [
        'shipping'  => '배송·수취·주소·택배',
        'packing'   => '패킹·내품·동봉, 필드 많음',
        'picking'   => '피킹·SKU·바코드 집품',
        'inventory' => '검수·재고·소형 다량',
        'hangtag'   => '행거·타공',
        'product'   => '일반 상품',
    ];

    /** 한 장에 이보다 적게 나오는 용지는 자료 라벨에 쓰지 않는다(한 장 한 칸짜리 전지 등). */
    private const MIN_PER_SHEET = 4;

    private ShopRepository $repo;

    /** @var array<string, array<string, mixed>>|null 품번 => 용지 규격 */
    private ?array $specs = null;

    /**
     * 고를 때만 쓰는 값. 규격에 섞어 두면 문서에 그대로 실려 나가므로 따로 둔다.
     *
     * @var array<string, array{id: int, plain: bool}>
     */
    private array $rank = [];

    public function __construct(?ShopRepository $repo = null)
    {
        $this->repo = $repo ?? new ShopRepository();
    }

    /**
     * 쓸 수 있는 용지 전체. 품번(SKU)으로 찾을 수 있게 담는다.
     * 한 번 읽어 두고 다시 쓰는 것은, 한 번 추천하는 동안 프롬프트와 배치에서 두 번 쓰기 때문이다.
     *
     * @return array<string, array<string, mixed>>
     */
    public function specs(): array
    {
        if ($this->specs !== null) {
            return $this->specs;
        }

        $specs = [];
        foreach ($this->rows() as $item) {
            $sku = trim((string) ($item['sku'] ?? ''));
            if ($sku === '' || isset($specs[$sku])) {
                continue;
            }
            $specs[$sku] = $this->toSpec($item);
            $material = trim((string) ($item['material'] ?? ''));
            $this->rank[$sku] = [
                'id' => (int) ($item['id'] ?? 0),
                // 치수가 같은 재질 변형(광택·방수·크라프트)보다 기본 백색을 먼저 집는다.
                'plain' => $material === '' || $material === '백색',
            ];
        }

        return $this->specs = $specs;
    }

    /** 고를 용지가 하나도 없는지. 서버에서 못 읽었거나 규격이 다 비어 있을 때. */
    public function isEmpty(): bool
    {
        return $this->specs() === [];
    }

    /**
     * AI 가 고를 수 있는 품번. 용도별로 집은 것만 내놓아, 받은 품번을 반드시 규격으로
     * 바꿀 수 있게 한다. 여든 몇 종을 다 적어 주면 프롬프트만 길어지고 자료 라벨에
     * 맞지 않는 것까지 고르게 된다. 치수로 고르고 싶을 때는 width_mm·height_mm 로
     * 적게 되어 있어, 이 목록 밖의 용지에도 닿을 수 있다.
     *
     * @return list<string>
     */
    public function promptPaperNos(): array
    {
        $nos = [];
        foreach (array_keys(self::USE_TARGET_MM) as $use) {
            $sku = $this->skuForUse($use);
            if ($sku !== null && !in_array($sku, $nos, true)) {
                $nos[] = $sku;
            }
        }
        return $nos;
    }

    /**
     * 프롬프트에 넣을 「용도→용지」 안내. 품번과 함께 치수·칸수를 적어 주어야
     * 모델이 width_mm·height_mm 를 품번에 맞춰 적는다.
     */
    public function promptGuide(): string
    {
        $lines = [];
        foreach (self::USE_LABELS as $use => $label) {
            $sku = $this->skuForUse($use);
            if ($sku === null) {
                continue;
            }
            $spec = $this->specs()[$sku];
            $lines[] = sprintf(
                '- %s(%s): %s (%s %s×%s mm %d칸)',
                $use,
                $label,
                $sku,
                $spec['category'],
                self::mm($spec['labelWidthMm']),
                self::mm($spec['labelHeightMm']),
                (int) $spec['columns'] * (int) $spec['rows']
            );
        }
        return implode("\n", $lines);
    }

    /**
     * 용도에 맞는 용지 하나. 기준 치수에 가장 가까운 판매 용지를 집는다.
     * 치수가 같은 것이 여럿이면 기본 백색을, 그래도 같으면 먼저 등록된 쪽을 집어,
     * 같은 자료를 두 번 넣어도 늘 같은 용지가 나오게 한다.
     */
    public function skuForUse(string $use): ?string
    {
        $target = self::USE_TARGET_MM[$use] ?? self::USE_TARGET_MM['general'];
        $best = null;
        $bestKey = null;

        foreach ($this->specs() as $sku => $spec) {
            $key = [
                round(abs((float) $spec['labelWidthMm'] - $target[0])
                    + abs((float) $spec['labelHeightMm'] - $target[1]), 3),
                $this->rank[$sku]['plain'] ? 0 : 1,
                $this->rank[$sku]['id'],
            ];
            if ($bestKey === null || $key < $bestKey) {
                $bestKey = $key;
                $best = (string) $sku;
            }
        }

        return $best;
    }

    /**
     * 용도에 맞는 용지 규격. 고를 것이 없으면 null.
     *
     * @return array<string, mixed>|null
     */
    public function specForUse(string $use): ?array
    {
        $sku = $this->skuForUse($use);
        return $sku === null ? null : $this->specs()[$sku];
    }

    /**
     * 쓸 수 있는 용지 행만 걸러 온다. 배치값(열·행)이 없으면 어디에 무엇을 놓을지 셀 수 없고,
     * 한 장에 서너 칸도 안 나오는 용지는 자료를 줄줄이 뽑는 데 쓰지 않는다.
     *
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        try {
            $catalog = $this->repo->editorPapers();
        } catch (\Throwable $e) {
            // 용지를 못 읽어도 라벨은 만들어 줘야 한다. 치수로 급조한 용지로 내려간다.
            error_log('[AiPaperCatalog] 용지 목록 읽기 실패: ' . $e->getMessage());
            return [];
        }

        $rows = [];
        foreach ($catalog['items'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (strtolower((string) ($item['kind'] ?? '')) === 'tag') {
                continue;
            }
            $cols = (int) ($item['columnsCount'] ?? 0);
            $rowCount = (int) ($item['rowsCount'] ?? 0);
            if ($cols < 1 || $rowCount < 1 || $cols * $rowCount < self::MIN_PER_SHEET) {
                continue;
            }
            if ((float) ($item['widthMm'] ?? 0) <= 0 || (float) ($item['heightMm'] ?? 0) <= 0) {
                continue;
            }
            $rows[] = $item;
        }
        return $rows;
    }

    /**
     * 용지 API 한 줄을 편집기 문서가 쓰는 규격 모양으로 바꾼다.
     * 왼쪽·위 여백은 규격값을 그대로 두고 오른쪽·아래는 남는 자리로 채운다. 칸 자리를
     * 왼쪽·위에서부터 세기 때문에, 그래야 인쇄 자리가 편집기가 그린 것과 맞는다.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function toSpec(array $item): array
    {
        $cols = max(1, (int) ($item['columnsCount'] ?? 1));
        $rows = max(1, (int) ($item['rowsCount'] ?? 1));
        $lw = max(1.0, (float) ($item['widthMm'] ?? 1));
        $lh = max(1.0, (float) ($item['heightMm'] ?? 1));
        $hGap = max(0.0, (float) ($item['hGapMm'] ?? 0));
        $vGap = max(0.0, (float) ($item['vGapMm'] ?? 0));

        $usedW = $lw * $cols + $hGap * ($cols - 1);
        $usedH = $lh * $rows + $vGap * ($rows - 1);
        [$pageW, $pageH] = self::pageSizeMm((string) ($item['paperSize'] ?? ''), $usedW, $usedH);

        $left = ($item['leftMarginMm'] ?? null) !== null
            ? max(0.0, (float) $item['leftMarginMm'])
            : max(0.0, ($pageW - $usedW) / 2);
        $top = ($item['topMarginMm'] ?? null) !== null
            ? max(0.0, (float) $item['topMarginMm'])
            : max(0.0, ($pageH - $usedH) / 2);
        // 규격이 시트를 넘치면 잘려 인쇄되므로 시트를 넓혀 둔다. 편집기도 같은 셈을 쓴다.
        $pageW = max($pageW, $usedW + $left);
        $pageH = max($pageH, $usedH + $top);

        $name = trim((string) ($item['name'] ?? ''));
        $category = trim((string) ($item['categoryName'] ?? ''));

        return [
            'version' => 1,
            'paperNo' => trim((string) ($item['sku'] ?? '')),
            'name' => $name !== '' ? $name : sprintf('%s×%s mm', self::mm($lw), self::mm($lh)),
            'category' => $category !== '' ? $category : 'A4',
            'brand' => 'LabelUp',
            'paperWidthMm' => round($pageW, 2),
            'paperHeightMm' => round($pageH, 2),
            'labelWidthMm' => round($lw, 2),
            'labelHeightMm' => round($lh, 2),
            'columns' => $cols,
            'rows' => $rows,
            'leftMarginMm' => round($left, 2),
            'topMarginMm' => round($top, 2),
            'rightMarginMm' => round(max(0.0, $pageW - $usedW - $left), 2),
            'bottomMarginMm' => round(max(0.0, $pageH - $usedH - $top), 2),
            'hGapMm' => round($hGap, 2),
            'vGapMm' => round($vGap, 2),
            'labelColor' => self::labelColor($item['labelColor'] ?? null),
            'shape' => self::shape($item, $lw, $lh),
        ];
    }

    /** @return array{0: float, 1: float} */
    private static function pageSizeMm(string $paperSize, float $needW, float $needH): array
    {
        return match (strtoupper(trim($paperSize))) {
            'LETTER' => [215.9, 279.4],
            'A3' => [297.0, 420.0],
            'A5' => [148.0, 210.0],
            default => [max(210.0, $needW), max(297.0, $needH)],
        };
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private static function shape(array $item, float $lw, float $lh): array
    {
        $raw = strtolower(trim((string) ($item['shape'] ?? '')));
        $limit = min($lw, $lh) / 2;
        $rx = ($item['cornerRadiusXMm'] ?? null) !== null ? (float) $item['cornerRadiusXMm'] : 0.0;
        $ry = ($item['cornerRadiusYMm'] ?? null) !== null ? (float) $item['cornerRadiusYMm'] : $rx;
        $rx = max(0.0, min($rx, $limit));
        $ry = max(0.0, min($ry, $limit));

        if (in_array($raw, ['round', 'circle', 'ellipse', '원형'], true)) {
            // 반경이 있으면 칸 가운데에 그 크기의 정원을, 없으면 칸에 내접하는 타원을 그린다.
            $shape = ['kind' => 'ellipse'];
            if ($ry > 0) {
                $shape['circleRadiusMm'] = round($ry, 3);
            }
            return $shape;
        }

        if ($rx <= 0) {
            return ['kind' => 'rect'];
        }

        $shape = ['kind' => 'roundrect', 'cornerRadiusMm' => round($rx, 3)];
        if (abs($ry - $rx) > 0.005) {
            $shape['cornerRadiusYMm'] = round($ry, 3);
        }
        return $shape;
    }

    private static function labelColor(mixed $raw): string
    {
        $color = strtoupper(trim((string) $raw));
        return preg_match('/^#[0-9A-F]{6}$/', $color) === 1 ? $color : '#FFFFFF';
    }

    /** 소수점 뒤 0 을 떼고 적는다. 99.06 은 그대로, 100.00 은 100 으로. */
    private static function mm(float|int|string $value): string
    {
        return rtrim(rtrim(sprintf('%.2f', (float) $value), '0'), '.');
    }
}
