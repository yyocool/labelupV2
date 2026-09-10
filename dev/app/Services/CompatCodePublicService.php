<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ShopCompatHelper;
use App\Repositories\ShopRepository;

final class CompatCodePublicService
{
    private const GROUP_COLORS = [
        'var(--g1)',
        'var(--g2)',
        'var(--g3)',
        'var(--g4)',
        'var(--g5)',
        'var(--g6)',
    ];

    private ShopRepository $repo;

    public function __construct(?ShopRepository $repo = null)
    {
        $this->repo = $repo ?? new ShopRepository();
    }

    /**
     * @return array{
     *   groups: list<array{id:string,name:string,color:string,desc:string}>,
     *   items: list<array<string,mixed>>,
     *   stats: array{total:int,formtec:int,anylabel:int,ilabel:int},
     *   example: ?array<string,mixed>
     * }
     */
    public function pageData(): array
    {
        $rows = $this->repo->compatCodeCatalog();
        $colorByCat = [];
        $groups = [];
        $items = [];
        $formtec = 0;
        $anylabel = 0;
        $ilabel = 0;
        $example = null;

        foreach ($rows as $row) {
            $catName = trim((string) ($row['category_name'] ?? ''));
            if ($catName === '') {
                $catName = '기타';
            }
            $gid = 'g1';

            if (!isset($colorByCat[$catName])) {
                $idx = count($colorByCat);
                $gid = 'g' . ($idx + 1);
                $colorByCat[$catName] = [
                    'id' => $gid,
                    'color' => self::GROUP_COLORS[$idx % count(self::GROUP_COLORS)],
                ];
                $groups[] = [
                    'id' => $gid,
                    'name' => $catName,
                    'color' => $colorByCat[$catName]['color'],
                    'desc' => '',
                ];
            }
            $gid = $colorByCat[$catName]['id'];

            $fCodes = ShopCompatHelper::parse($row['compat_formtec'] ?? null);
            $aCodes = ShopCompatHelper::parse($row['compat_anylabel'] ?? null);
            $iCodes = ShopCompatHelper::parse($row['compat_ilabel'] ?? null);
            if ($fCodes !== []) {
                $formtec++;
            }
            if ($aCodes !== []) {
                $anylabel++;
            }
            if ($iCodes !== []) {
                $ilabel++;
            }

            $w = (float) ($row['width_mm'] ?? 0);
            $h = (float) ($row['height_mm'] ?? 0);
            $cn = (int) ($row['labels_per_sheet'] ?? 0);
            $sheets = $this->sheetsPerPack($row);
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }

            $item = [
                'pn' => $sku,
                'grp' => $catName,
                'gid' => $gid,
                'sub' => trim((string) ($row['material'] ?? $catName)),
                'nm' => trim((string) ($row['name'] ?? '')),
                'f' => $fCodes[0] ?? '',
                'a' => $aCodes[0] ?? '',
                'i' => $iCodes[0] ?? '',
                'f_all' => $fCodes,
                'a_all' => $aCodes,
                'i_all' => $iCodes,
                'sz' => 'A4',
                'cn' => $cn > 0 ? (string) $cn : '-',
                'd' => ($w > 0 && $h > 0) ? [$w, $h] : null,
                'dt' => ($w > 0 && $h > 0) ? $this->fmtDim($w) . 'x' . $this->fmtDim($h) : '',
                'st' => ($w > 0 && $h > 0) ? $this->fmtDim($w) . 'x' . $this->fmtDim($h) : '',
                'sh' => (string) $sheets,
                'col' => trim((string) ($row['material'] ?? '')),
                'id' => (int) ($row['id'] ?? 0),
            ];
            $items[] = $item;

            if ($example === null && $fCodes !== [] && $aCodes !== [] && $iCodes !== []) {
                $example = $item;
            }
        }

        if ($example === null && $items !== []) {
            foreach ($items as $it) {
                if (($it['f'] ?? '') !== '' || ($it['a'] ?? '') !== '' || ($it['i'] ?? '') !== '') {
                    $example = $it;
                    break;
                }
            }
        }

        return [
            'groups' => $groups,
            'items' => $items,
            'stats' => [
                'total' => count($items),
                'formtec' => $formtec,
                'anylabel' => $anylabel,
                'ilabel' => $ilabel,
            ],
            'example' => $example,
        ];
    }

    /** @param array<string,mixed> $row */
    private function sheetsPerPack(array $row): int
    {
        $metaRaw = $row['meta_json'] ?? null;
        if (is_string($metaRaw) && $metaRaw !== '') {
            $meta = json_decode($metaRaw, true);
            if (is_array($meta) && isset($meta['sheets_per_pack']) && is_numeric($meta['sheets_per_pack'])) {
                $n = (int) $meta['sheets_per_pack'];
                if ($n > 0) {
                    return $n;
                }
            }
        }

        $sku = (string) ($row['sku'] ?? '');
        if (preg_match('/-(\d+)$/', $sku, $m) === 1) {
            return max(1, (int) $m[1]);
        }

        return 20;
    }

    private function fmtDim(float $v): string
    {
        if (abs($v - round($v)) < 0.001) {
            return (string) (int) round($v);
        }
        $s = rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        return $s !== '' ? $s : '0';
    }
}
