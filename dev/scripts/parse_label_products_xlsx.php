<?php

/**
 * 「상품자료.xlsx」(제품 리스트 업데이트 시트)를 읽어 기본 품번 단위로 묶은 JSON을 만든다.
 *
 * 품번 뒤의 매수(A101-20 → 20)는 한 상품의 옵션이 되므로, 같은 기본 품번(A101)끼리 묶고
 * 매수 오름차순으로 정렬해 둔다. DB에는 손대지 않는다.
 *
 * 사용: php parse_label_products_xlsx.php <상품자료.xlsx> <out.json>
 */

declare(strict_types=1);

/** 시트 헤더가 2행에 걸쳐 있어 열 번호를 상수로 고정한다. (0-based) */
const COL = [
    'group' => 0,          // 구분
    'sku' => 3,            // 품번
    'formtec' => 4,        // 폼텍 No
    'anylabel' => 5,       // 애니라벨 No
    'ilabel' => 6,         // 아이라벨 No
    'material_name' => 7,  // 품명
    'barcode_pack' => 8,   // 팩바코드
    'barcode_box' => 9,    // 박스바코드
    'paper_size' => 10,    // 제품규격
    'labels' => 11,        // 라벨수(칸)
    'std_size' => 12,      // 표기치수
    'spec_size' => 13,     // Spec (Size/mm)
    'pack_size' => 14,     // 팩키지 Pack Size(mm)
    'box_size' => 15,      // 박스 Box Size(mm)
    'sheets' => 16,        // Sheets / PACK(매)
    'qty_per_box' => 17,   // 입수량
    'color' => 18,         // 색상
    'margin_top' => 19,    // 마진 상단
    'margin_left' => 20,   // 마진 좌측
    'columns' => 21,       // 행X열 → 열
    'rows' => 22,          // 행X열 → 행
    'gap_v' => 23,         // 라벨간격 상
    'gap_h' => 24,         // 라벨간격 좌
    'radius' => 25,        // 모서리 R값
    'rgb' => 26,           // 라벨 바탕색 RGB
    'origin' => 27,        // 원산지
    'hex' => 28,           // 기타(hex 색상이 들어있는 경우)
    'barcode_name' => 30,  // 바코드 등록 상품명
    'name' => 31,          // 상품명
    'price' => 32,         // 정상 소비자가
    'sale_price' => 33,    // 할인가
];

/** 데이터는 4행부터. 1~3행은 제목·2단 헤더다. */
const FIRST_DATA_ROW = 4;

/** 라벨간격 같은 수치 칸에 엑셀 날짜 일련번호가 섞여 들어온 경우를 걸러낸다. */
const MAX_PLAUSIBLE_MM = 400.0;

/**
 * xlsx 의 첫 시트를 [행번호 => [열번호 => 값]] 으로 읽는다.
 *
 * @return array<int, array<int, string>>
 */
function readSheetCells(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException("xlsx 를 열 수 없습니다: {$path}");
    }

    $shared = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        foreach (simplexml_load_string($sharedXml)->si as $si) {
            if (isset($si->t)) {
                $shared[] = (string) $si->t;
                continue;
            }
            // 서식이 섞인 문자열은 <r><t> 조각으로 쪼개져 있다.
            $text = '';
            foreach ($si->r as $run) {
                $text .= (string) $run->t;
            }
            $shared[] = $text;
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) {
        throw new RuntimeException('첫 번째 시트를 찾을 수 없습니다.');
    }

    $out = [];
    foreach (simplexml_load_string($sheetXml)->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $cell) {
            $type = (string) $cell['t'];
            if ($type === 'inlineStr') {
                $value = (string) $cell->is->t;
            } else {
                $value = isset($cell->v) ? (string) $cell->v : '';
                if ($type === 's') {
                    $value = $shared[(int) $value] ?? '';
                }
            }
            $cells[columnIndex((string) $cell['r'])] = trim(preg_replace('/\s+/u', ' ', $value));
        }
        $out[(int) $row['r']] = $cells;
    }

    return $out;
}

/** "AB12" 같은 셀 주소에서 0-based 열 번호를 얻는다. */
function columnIndex(string $ref): int
{
    $letters = (string) preg_replace('/\d+/', '', $ref);
    $index = 0;
    foreach (str_split($letters) as $char) {
        $index = $index * 26 + (ord(strtoupper($char)) - 64);
    }
    return $index - 1;
}

/** @param array<int, string> $cells */
function cellText(array $cells, string $key): string
{
    return trim((string) ($cells[COL[$key]] ?? ''));
}

function cellFloat(array $cells, string $key): ?float
{
    $raw = cellText($cells, $key);
    return $raw !== '' && is_numeric($raw) ? (float) $raw : null;
}

function cellInt(array $cells, string $key): ?int
{
    $value = cellFloat($cells, $key);
    return $value === null ? null : (int) round($value);
}

/** 길이 칸에 엉뚱한 값(엑셀 날짜 일련번호 등)이 들어온 경우 버린다. */
function cellMm(array $cells, string $key): ?float
{
    $value = cellFloat($cells, $key);
    if ($value === null || $value < 0 || $value > MAX_PLAUSIBLE_MM) {
        return null;
    }
    return $value;
}

/** 바코드가 지수표기(8.8E+12)로 들어오므로 정수 문자열로 되돌린다. */
function cellBarcode(array $cells, string $key): string
{
    $raw = cellText($cells, $key);
    if ($raw === '' || !is_numeric($raw)) {
        return $raw;
    }
    return number_format((float) $raw, 0, '.', '');
}

/** "200 x 289" → [200.0, 289.0] */
function parseSize(string ...$candidates): array
{
    foreach ($candidates as $text) {
        if ($text !== '' && preg_match('/([\d.]+)\s*[x×X]\s*([\d.]+)/u', $text, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }
    }
    return [0.0, 0.0];
}

/** 「기타」 칸에는 hex 색상 대신 메모가 들어오기도 해서 색상 꼴일 때만 받는다. */
function normalizeHexColor(string $text): ?string
{
    return preg_match('/^#?([0-9a-f]{6}|[0-9a-f]{3})$/i', $text, $m) ? '#' . strtolower($m[1]) : null;
}

/** "R224 G193 B128" → "#e0c180" */
function rgbToHex(string $text): ?string
{
    if (!preg_match_all('/\d+/', $text, $m) || count($m[0]) < 3) {
        return null;
    }
    return sprintf(
        '#%02x%02x%02x',
        min(255, (int) $m[0][0]),
        min(255, (int) $m[0][1]),
        min(255, (int) $m[0][2])
    );
}

// ----------------------------------------------------------------------------

$xlsxPath = $argv[1] ?? '';
$outPath = $argv[2] ?? '';
if ($xlsxPath === '' || $outPath === '') {
    fwrite(STDERR, "사용: php parse_label_products_xlsx.php <상품자료.xlsx> <out.json>\n");
    exit(1);
}
if (!is_readable($xlsxPath)) {
    fwrite(STDERR, "파일을 읽을 수 없습니다: {$xlsxPath}\n");
    exit(1);
}

$rows = readSheetCells($xlsxPath);
$warnings = [];
$items = [];
$currentGroup = '';

foreach ($rows as $rowNo => $cells) {
    if ($rowNo < FIRST_DATA_ROW) {
        continue;
    }

    $sku = cellText($cells, 'sku');
    if ($sku === '') {
        continue;
    }
    // 품번은 반드시 「문자+숫자(+문자)-매수」 꼴이다. 시트 끝의 메모 행을 걸러낸다.
    if (!preg_match('/^([A-Z]+\d+[A-Z]*)-(\d+)$/i', $sku, $skuParts)) {
        $warnings[] = "r{$rowNo}: 품번 형식이 아니라 건너뜀 — '{$sku}'";
        continue;
    }
    [, $base, $suffix] = $skuParts;

    // 구분은 분류가 바뀌는 첫 행에만 적혀 있어 아래로 이어받는다.
    $group = cellText($cells, 'group');
    if ($group !== '') {
        $currentGroup = $group;
    }
    if ($currentGroup === '') {
        $warnings[] = "r{$rowNo} {$sku}: 구분(분류)을 알 수 없음";
    }

    $sheets = cellInt($cells, 'sheets');
    if ($sheets === null) {
        $warnings[] = "r{$rowNo} {$sku}: Sheets/PACK 이 비어 있어 품번 뒤 숫자({$suffix})를 매수로 씀";
    } elseif ($sheets !== (int) $suffix) {
        $warnings[] = "r{$rowNo} {$sku}: 품번 뒤 숫자({$suffix})와 Sheets/PACK({$sheets}) 가 다름";
    }

    $price = cellInt($cells, 'price');
    $salePrice = cellInt($cells, 'sale_price');
    $name = cellText($cells, 'name');
    foreach (['정상 소비자가' => $price, '할인가' => $salePrice] as $label => $value) {
        if (!$value) {
            $warnings[] = "r{$rowNo} {$sku}: {$label} 가 비어 있음";
        }
    }
    if ($name === '') {
        $warnings[] = "r{$rowNo} {$sku}: 상품명이 비어 있음";
    }

    [$width, $height] = parseSize(cellText($cells, 'spec_size'), cellText($cells, 'std_size'));
    if ($width <= 0 || $height <= 0) {
        $warnings[] = "r{$rowNo} {$sku}: 라벨 치수를 읽을 수 없어 규격 없이 등록됨";
    }

    $items[] = [
        'row' => $rowNo,
        'group' => $currentGroup,
        'sku' => $sku,
        'base' => $base,
        'pack' => $sheets ?? (int) $suffix,
        'name' => $name,
        'barcode_name' => cellText($cells, 'barcode_name'),
        'material_name' => cellText($cells, 'material_name'),
        'paper_size' => cellText($cells, 'paper_size'),
        'labels_per_sheet' => cellInt($cells, 'labels'),
        'std_size' => cellText($cells, 'std_size'),
        'spec_size' => cellText($cells, 'spec_size'),
        'width_mm' => $width,
        'height_mm' => $height,
        'pack_size' => cellText($cells, 'pack_size'),
        'box_size' => cellText($cells, 'box_size'),
        'qty_per_box' => cellInt($cells, 'qty_per_box'),
        'color' => cellText($cells, 'color'),
        'top_margin_mm' => cellMm($cells, 'margin_top'),
        'left_margin_mm' => cellMm($cells, 'margin_left'),
        'columns_count' => cellInt($cells, 'columns'),
        'rows_count' => cellInt($cells, 'rows'),
        'v_gap_mm' => cellMm($cells, 'gap_v'),
        'h_gap_mm' => cellMm($cells, 'gap_h'),
        'corner_radius_mm' => cellMm($cells, 'radius'),
        'label_color' => normalizeHexColor(cellText($cells, 'hex')) ?? rgbToHex(cellText($cells, 'rgb')),
        'note' => cellText($cells, 'hex'),
        'origin' => cellText($cells, 'origin'),
        'compat_formtec' => cellText($cells, 'formtec'),
        'compat_anylabel' => cellText($cells, 'anylabel'),
        'compat_ilabel' => cellText($cells, 'ilabel'),
        'barcode_pack' => cellBarcode($cells, 'barcode_pack'),
        'barcode_box' => cellBarcode($cells, 'barcode_box'),
        'price' => $price,
        'sale_price' => $salePrice,
    ];
}

/** @var array<string, array<int, array<string, mixed>>> $grouped */
$grouped = [];
foreach ($items as $item) {
    $grouped[$item['base']][] = $item;
}

$plan = [];
foreach ($grouped as $base => $variants) {
    usort($variants, static fn (array $a, array $b): int => $a['pack'] <=> $b['pack']);

    // 규격은 그룹 대표(가장 작은 매수) 한 줄로 만든다. 그룹 안에서 값이 다르면 알린다.
    $reference = $variants[0];
    foreach ($variants as $variant) {
        foreach (['paper_size', 'labels_per_sheet', 'width_mm', 'height_mm', 'columns_count', 'rows_count',
            'top_margin_mm', 'left_margin_mm', 'v_gap_mm', 'h_gap_mm', 'corner_radius_mm', 'color'] as $field) {
            if ((string) $reference[$field] !== (string) $variant[$field]) {
                $warnings[] = sprintf(
                    '%s: 그룹 안에서 %s 가 다름 — %s=「%s」 vs %s=「%s」 (대표 %s 값을 씀)',
                    $base,
                    $field,
                    $reference['sku'],
                    (string) $reference[$field],
                    $variant['sku'],
                    (string) $variant[$field],
                    $reference['sku']
                );
            }
        }
    }

    $plan[] = [
        'base' => $base,
        'group' => $reference['group'],
        'variants' => $variants,
    ];
}

$payload = [
    'source' => basename($xlsxPath),
    'parsed_at' => date('c'),
    'row_count' => count($items),
    'group_count' => count($plan),
    'warnings' => array_values(array_unique($warnings)),
    'groups' => $plan,
];

if (file_put_contents($outPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
    fwrite(STDERR, "JSON 저장 실패: {$outPath}\n");
    exit(1);
}

printf("데이터 %d행 → 기본 품번 %d그룹, 경고 %d건\n", count($items), count($plan), count($payload['warnings']));
foreach ($payload['warnings'] as $warning) {
    echo "  - {$warning}\n";
}
echo "저장: {$outPath}\n";
