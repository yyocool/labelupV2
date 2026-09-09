<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * 상품 설명 텍스트에서 "항목: 값" 스펙 줄을 파싱한다.
 */
final class ShopProductDescriptionHelper
{
    /**
     * @return array{specs: array<int, array{label:string,value:string}>, prose: string}
     */
    public static function parse(string $description): array
    {
        $description = str_replace(["\r\n", "\r"], "\n", trim($description));
        if ($description === '') {
            return ['specs' => [], 'prose' => ''];
        }

        $specs = [];
        $prose = [];
        foreach (explode("\n", $description) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^([^:：\n]{1,40})\s*[:：]\s*(.+)$/u', $line, $m)) {
                $label = trim($m[1]);
                $value = trim($m[2]);
                if ($label !== '' && $value !== '') {
                    $specs[] = ['label' => $label, 'value' => $value];
                    continue;
                }
            }
            $prose[] = $line;
        }

        return [
            'specs' => $specs,
            'prose' => implode("\n", $prose),
        ];
    }

    /**
     * @param array<int, array{label:string,value:string}> $specs
     * @return array<int, array{0:?array{label:string,value:string},1:?array{label:string,value:string}}>
     */
    public static function pairRows(array $specs): array
    {
        $rows = [];
        $count = count($specs);
        for ($i = 0; $i < $count; $i += 2) {
            $rows[] = [
                $specs[$i] ?? null,
                $specs[$i + 1] ?? null,
            ];
        }
        return $rows;
    }
}
