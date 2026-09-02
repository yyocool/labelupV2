<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class LabiDataTemplateService
{
    /**
     * @param array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary:string} $sheet
     * @return array{
     *   document: array<string, mixed>,
     *   title: string,
     *   width_mm: float,
     *   height_mm: float,
     *   fields: array<int, array{column:string, kind:string}>,
     *   message: string
     * }
     */
    public function build(array $sheet, string $userHint = '', ?array $ai = null): array
    {
        $columns = $sheet['columns'];
        $layout = is_array($ai) ? $ai : [];
        $title = trim((string) ($layout['title'] ?? ''));
        if ($title === '') {
            $title = $this->defaultTitle($sheet['source_name'], $columns);
        }

        $fields = $this->resolveFields($columns, $layout['fields'] ?? null);
        $size = $this->resolveSize($fields, $layout);
        $w = $size['width_mm'];
        $h = $size['height_mm'];
        $objects = $this->placeFields($fields, $w, $h, $sheet['columns'], $sheet['rows'][0] ?? []);

        $document = [
            'version' => 2,
            'format' => 'labelup',
            'name' => $title,
            'background' => '#FFFFFF',
            'paper' => [
                'version' => 1,
                'paperNo' => 'CUSTOM',
                'name' => $title,
                'category' => 'A4',
                'brand' => 'LabelUp',
                'paperWidthMm' => 210,
                'paperHeightMm' => 297,
                'labelWidthMm' => $w,
                'labelHeightMm' => $h,
                'columns' => 1,
                'rows' => 1,
                'leftMarginMm' => 10,
                'topMarginMm' => 10,
                'rightMarginMm' => 10,
                'bottomMarginMm' => 10,
                'hGapMm' => 2,
                'vGapMm' => 2,
                'labelColor' => '#FFFFFF',
                'shape' => ['kind' => 'roundrect', 'cornerRadiusMm' => 2.0],
            ],
            'pages' => [[
                'index' => 0,
                'cells' => [[
                    'index' => 0,
                    'objects' => $objects,
                ]],
            ]],
            'data' => [
                'sourceName' => $sheet['source_name'],
                'sourceKind' => $sheet['source_kind'],
                'columns' => $columns,
                'rows' => $sheet['rows'],
            ],
            'printOffsetXMm' => 0,
            'printOffsetYMm' => 0,
        ];

        $hint = trim((string) ($layout['message'] ?? ''));
        $message = $hint !== ''
            ? $hint
            : sprintf(
                '「%s」에서 열 %d개 · 행 %d개를 읽어 라벨 템플릿과 데이터셋을 만들었어요. 바로편집에서 자료 연결을 확인해 보세요.',
                $sheet['source_name'],
                count($columns),
                count($sheet['rows'])
            );
        if ($userHint !== '' && !str_contains($message, $userHint)) {
            $message = $userHint . "\n\n" . $message;
        }

        return [
            'document' => $document,
            'title' => $title,
            'width_mm' => $w,
            'height_mm' => $h,
            'fields' => $fields,
            'message' => $message,
        ];
    }

    /**
     * @param array<int, string> $columns
     * @param mixed $raw
     * @return array<int, array{column:string, kind:string}>
     */
    private function resolveFields(array $columns, mixed $raw): array
    {
        $known = [];
        foreach ($columns as $col) {
            $known[mb_strtolower($col)] = $col;
        }

        $fields = [];
        if (is_array($raw)) {
            foreach ($raw as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $col = trim((string) ($item['column'] ?? $item['name'] ?? ''));
                $match = $known[mb_strtolower($col)] ?? null;
                if ($match === null) {
                    continue;
                }
                $kind = $this->normalizeKind((string) ($item['kind'] ?? $item['type'] ?? ''), $match);
                $fields[] = ['column' => $match, 'kind' => $kind];
            }
        }
        if ($fields === []) {
            foreach ($columns as $col) {
                $fields[] = ['column' => $col, 'kind' => $this->guessKind($col)];
            }
        }

        $seen = [];
        $out = [];
        foreach ($fields as $field) {
            $key = mb_strtolower($field['column']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $field;
            if (count($out) >= 10) {
                break;
            }
        }
        return $out;
    }

    /**
     * @param array<int, array{column:string, kind:string}> $fields
     * @param array<string, mixed> $layout
     * @return array{width_mm:float, height_mm:float}
     */
    private function resolveSize(array $fields, array $layout): array
    {
        $w = (float) ($layout['width_mm'] ?? 0);
        $h = (float) ($layout['height_mm'] ?? 0);
        if ($w >= 20 && $w <= 120 && $h >= 15 && $h <= 120) {
            return ['width_mm' => $w, 'height_mm' => $h];
        }
        $count = count($fields);
        $hasCode = false;
        $hasAddr = false;
        foreach ($fields as $field) {
            if (in_array($field['kind'], ['barcode', 'qr'], true)) {
                $hasCode = true;
            }
            if (preg_match('/주소|address|addr/i', $field['column'])) {
                $hasAddr = true;
            }
        }
        if ($hasAddr || $count >= 6) {
            return ['width_mm' => 90.0, 'height_mm' => $hasCode ? 55.0 : 50.0];
        }
        if ($count <= 2 && !$hasCode) {
            return ['width_mm' => 70.0, 'height_mm' => 30.0];
        }
        return ['width_mm' => 70.0, 'height_mm' => $hasCode ? 50.0 : 36.0];
    }

    /**
     * @param array<int, array{column:string, kind:string}> $fields
     * @param array<int, string> $columns
     * @param array<int, string> $sample
     * @return array<int, array<string, mixed>>
     */
    private function placeFields(array $fields, float $w, float $h, array $columns, array $sample): array
    {
        $padX = max(2.4, $w * 0.06);
        $padY = max(2.0, $h * 0.08);
        $innerW = max(10.0, $w - $padX * 2);
        $innerH = max(8.0, $h - $padY * 2);
        $gap = 1.1;
        $colIndex = [];
        foreach ($columns as $idx => $name) {
            $colIndex[mb_strtolower((string) $name)] = $idx;
        }

        $weights = [];
        foreach ($fields as $i => $field) {
            $weights[$i] = match ($field['kind']) {
                'barcode' => 2.2,
                'qr' => 2.6,
                default => $this->isTitleColumn($field['column']) ? 1.7 : 1.0,
            };
        }
        $sum = array_sum($weights) ?: 1;
        $objects = [];
        $y = $padY;
        $z = 1;
        foreach ($fields as $i => $field) {
            $fh = max(4.0, ($innerH - $gap * (count($fields) - 1)) * ($weights[$i] / $sum));
            $kind = $field['kind'];
            $col = $field['column'];
            $si = $colIndex[mb_strtolower($col)] ?? $i;
            $sampleVal = trim((string) ($sample[$si] ?? $col));
            $id = sprintf('labi%02d', $i + 1);
            if ($kind === 'barcode') {
                $objects[] = $this->barcodeObject($id, $padX, $y, $innerW, $fh, $col, $sampleVal, $z++);
            } elseif ($kind === 'qr') {
                $size = min($fh, $innerW * 0.34);
                $objects[] = $this->qrObject($id, $padX + ($innerW - $size) / 2, $y, $size, $col, $sampleVal, $z++);
            } else {
                $title = $this->isTitleColumn($col);
                $objects[] = $this->textObject(
                    $id,
                    $padX,
                    $y,
                    $innerW,
                    $fh,
                    $col,
                    $sampleVal !== '' ? $sampleVal : $col,
                    $title ? max(4.2, min(8.0, $fh * 0.55)) : max(2.8, min(4.6, $fh * 0.42)),
                    $title,
                    $z++
                );
            }
            $y += $fh + $gap;
        }
        return $objects;
    }

    /** @return array<string, mixed> */
    private function textObject(
        string $id,
        float $x,
        float $y,
        float $w,
        float $h,
        string $column,
        string $text,
        float $fontSize,
        bool $bold,
        int $z
    ): array {
        return [
            'id' => $id,
            'type' => 'text',
            'zIndex' => $z,
            'visible' => true,
            'x' => $x,
            'y' => $y,
            'width' => $w,
            'height' => $h,
            'fill' => $bold ? '#7B2840' : '#2E2A27',
            'strokeWidth' => 0,
            'opacity' => 1,
            'dataBound' => true,
            'dataColumn' => $column,
            'text' => '[' . $column . ']',
            'fontSize' => $fontSize,
            'fontFamily' => 'Pretendard',
            'bold' => $bold,
            'textAlign' => 'left',
            'verticalAlign' => 'middle',
            'backgroundTransparent' => true,
            'textMode' => 'normal',
        ];
    }

    /** @return array<string, mixed> */
    private function barcodeObject(string $id, float $x, float $y, float $w, float $h, string $column, string $sample, int $z): array
    {
        return [
            'id' => $id,
            'type' => 'barcode',
            'zIndex' => $z,
            'visible' => true,
            'x' => $x,
            'y' => $y,
            'width' => $w,
            'height' => $h,
            'fill' => '#2E2A27',
            'strokeWidth' => 0,
            'opacity' => 1,
            'dataBound' => true,
            'dataColumn' => $column,
            'text' => '[' . $column . ']',
            'barcodeFormat' => 'CODE_128',
            'barcodeValue' => $sample !== '' ? $sample : 'LABELUP',
            'barcodeShowText' => true,
            'fontSize' => 2.2,
            'backgroundTransparent' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function qrObject(string $id, float $x, float $y, float $size, string $column, string $sample, int $z): array
    {
        return [
            'id' => $id,
            'type' => 'qr',
            'zIndex' => $z,
            'visible' => true,
            'x' => $x,
            'y' => $y,
            'width' => $size,
            'height' => $size,
            'fill' => '#2E2A27',
            'strokeWidth' => 0,
            'opacity' => 1,
            'dataBound' => true,
            'dataColumn' => $column,
            'text' => '[' . $column . ']',
            'barcodeFormat' => 'QR_CODE',
            'barcodeValue' => $sample !== '' ? $sample : 'https://labelup.kr',
            'backgroundTransparent' => true,
        ];
    }

    private function defaultTitle(string $fileName, array $columns): string
    {
        $base = pathinfo($fileName, PATHINFO_FILENAME);
        $base = trim((string) $base);
        if ($base !== '' && !preg_match('/^(book|sheet|문서|untitled)/i', $base)) {
            return mb_substr($base, 0, 40) . ' 라벨';
        }
        $first = $columns[0] ?? '데이터';
        return $first . ' 라벨';
    }

    private function normalizeKind(string $kind, string $column): string
    {
        $kind = strtolower(trim($kind));
        if (in_array($kind, ['barcode', 'code128', 'code'], true)) {
            return 'barcode';
        }
        if (in_array($kind, ['qr', 'qrcode'], true)) {
            return 'qr';
        }
        return $this->guessKind($column);
    }

    private function guessKind(string $column): string
    {
        if (preg_match('/qr|링크|url|homepage|홈페이지/i', $column)) {
            return 'qr';
        }
        if (preg_match('/바코드|barcode|sku|isbn|gtin|ean|jan|상품코드|품번/i', $column)) {
            return 'barcode';
        }
        return 'text';
    }

    private function isTitleColumn(string $column): bool
    {
        return (bool) preg_match('/이름|성명|name|상품명|제품명|품명|title|상호|회사명/i', $column);
    }

    /**
     * @param array<string, mixed> $built
     * @param array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary:string} $sheet
     * @return array<string, mixed>
     */
    public function present(array $built, array $sheet, ?int $userId): array
    {
        $title = (string) $built['title'];
        $document = $built['document'];
        $editorUrl = url('editor/') . '?labiDoc=1';
        $projectId = null;
        if ($userId !== null && $userId > 0) {
            try {
                $saved = (new EditorWorkspaceService())->save(
                    $userId,
                    0,
                    $title,
                    $document,
                    ['dataPanelVisible' => true, 'dataPanelExpanded' => true]
                );
                $projectId = (int) ($saved['id'] ?? 0);
                if ($projectId > 0 && !empty($saved['editor_url'])) {
                    $editorUrl = (string) $saved['editor_url'];
                }
            } catch (RuntimeException) {
                $projectId = null;
            }
        }

        $preview = [];
        foreach (array_slice($sheet['rows'], 0, 4) as $row) {
            $preview[] = array_slice($row, 0, 6);
        }

        return [
            'url' => '',
            'title' => $title,
            'width_mm' => $built['width_mm'],
            'height_mm' => $built['height_mm'],
            'fit' => '',
            'editor_url' => $editorUrl,
            'project_id' => $projectId,
            'document' => $document,
            'dataset' => [
                'source_name' => $sheet['source_name'],
                'source_kind' => $sheet['source_kind'],
                'columns' => $sheet['columns'],
                'row_count' => count($sheet['rows']),
                'preview_rows' => $preview,
            ],
        ];
    }
}
