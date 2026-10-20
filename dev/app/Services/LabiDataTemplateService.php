<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class LabiDataTemplateService
{
    private AiPaperCatalogService $papers;

    /**
     * @param ?AiPaperCatalogService $papers 고를 용지 목록. 한 번 추천하는 동안 프롬프트 쪽과
     *        같은 것을 써야 AI 가 받은 품번을 여기서 그대로 찾을 수 있어, 바깥에서 넣어 준다.
     */
    public function __construct(?AiPaperCatalogService $papers = null)
    {
        $this->papers = $papers ?? new AiPaperCatalogService();
    }

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

        $kind = $this->detectLabelKind($sheet, $columns, $userHint);
        $aiUse = trim((string) ($layout['use_case'] ?? $layout['useCase'] ?? ''));
        if (in_array($aiUse, ['shipping', 'packing', 'picking', 'inventory', 'hangtag', 'product', 'general'], true)) {
            $use = $aiUse;
        } elseif ($kind === 'logistics') {
            $use = $this->detectLogisticsUse($sheet, $columns, $userHint);
        } else {
            $use = $kind;
        }
        $fields = $this->resolveFields($columns, $layout['fields'] ?? null, $use);
        $sheet = $this->normalizeSheetForFields($sheet, $fields);
        $paper = $this->resolvePaper($use, $fields, $layout, count($sheet['rows']));
        $w = (float) $paper['labelWidthMm'];
        $h = (float) $paper['labelHeightMm'];
        $objects = $this->placeFields($fields, $w, $h, $sheet['columns'], $sheet['rows'][0] ?? [], $use);

        $per = max(1, (int) $paper['columns'] * (int) $paper['rows']);
        $cells = [];
        for ($i = 0; $i < $per; $i++) {
            $cellObjects = [];
            foreach ($objects as $obj) {
                $copy = $obj;
                $copy['id'] = sprintf('labi%02d_%02d', $i + 1, $obj['zIndex'] ?? ($i + 1));
                $cellObjects[] = $copy;
            }
            $cells[] = ['index' => $i, 'objects' => $cellObjects];
        }

        $document = [
            'version' => 2,
            'format' => 'labelup',
            'name' => $title,
            'background' => '#FFFFFF',
            'paper' => $paper,
            'pages' => [[
                'index' => 0,
                'cells' => $cells,
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
        $paperLabel = (string) ($paper['name'] ?? sprintf('%s×%s mm', $w, $h));
        $useLabel = $this->useCaseLabel($use);
        $message = $hint !== ''
            ? $hint
            : sprintf(
                '「%s」에서 열 %d개 · 행 %d개를 읽어 %s용으로 %s 템플릿을 만들었어요. 한 장에 %d건씩 출력됩니다. 바로편집에서 자료 연결을 확인해 보세요.',
                $sheet['source_name'],
                count($columns),
                count($sheet['rows']),
                $useLabel,
                $paperLabel,
                $per
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
            'paper_name' => $paperLabel,
            'labels_per_page' => $per,
            'use_case' => $use,
            'use_case_label' => $useLabel,
        ];
    }

    /**
     * @param array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary?:string} $sheet
     * @param array<int, string> $columns
     */
    private function detectLabelKind(array $sheet, array $columns, string $userHint): string
    {
        $blob = mb_strtolower($userHint . ' ' . ($sheet['source_name'] ?? '') . ' ' . implode(' ', $columns));
        if (preg_match('/물류|택배|배송|출고|운송장|피킹|패킹|검수|재고|행거|타공|shipping|logistics|fulfillment|inventory|stock|주문서|수취|주소|우편|recipient|address|order|pick|pack|hang/u', $blob)) {
            return 'logistics';
        }
        $hits = 0;
        foreach ($columns as $col) {
            if (preg_match('/주소|address|수취|recipient|우편|zip|전화|phone|주문번호|order\s*no|sku|바코드/iu', $col)) {
                $hits++;
            }
        }
        if ($hits >= 3 || (count($sheet['rows']) >= 30 && $hits >= 2)) {
            return 'logistics';
        }
        if (preg_match('/상품|product|품명|sku|재고/u', $blob)) {
            return 'product';
        }
        return 'general';
    }

    /**
     * 물류 세부 용도: shipping | packing | picking | inventory | hangtag
     *
     * @param array{source_name?:string, columns?:array<int,string>, rows?:array<int,array<int,string>>} $sheet
     * @param array<int, string> $columns
     */
    private function detectLogisticsUse(array $sheet, array $columns, string $userHint): string
    {
        $blob = mb_strtolower($userHint . ' ' . ($sheet['source_name'] ?? '') . ' ' . implode(' ', $columns));

        if (preg_match('/행거|타공|hang\s*tag|옷걸이/u', $blob)) {
            return 'hangtag';
        }
        if (preg_match('/피킹|집품|로케이션|bin|pick\s*list|창고\s*피킹/u', $blob)) {
            return 'picking';
        }
        if (preg_match('/검수|재고|inventory|stock|입출고\s*표/u', $blob)
            && !preg_match('/수취|주소|address|배송/u', $blob)) {
            return 'inventory';
        }
        if (preg_match('/패킹|동봉|내품|packing|pack\s*list|송장\s*동봉/u', $blob)) {
            return 'packing';
        }

        $hasAddr = false;
        $hasRecipient = false;
        $hasSku = false;
        $hasOrder = false;
        $hasQty = false;
        foreach ($columns as $col) {
            if (preg_match('/주소|address|addr|우편|zip|postal|city|state/i', $col)) {
                $hasAddr = true;
            }
            if (preg_match('/수취|recipient|받는|주문자|고객명/i', $col)) {
                $hasRecipient = true;
            }
            if (preg_match('/sku|바코드|barcode|상품코드|품번/i', $col)) {
                $hasSku = true;
            }
            if (preg_match('/주문번호|order\s*(no|id|number)|운송장/i', $col)) {
                $hasOrder = true;
            }
            if (preg_match('/수량|qty|quantity/i', $col)) {
                $hasQty = true;
            }
        }

        // 배송지 라벨: 수취+주소
        if ($hasAddr && $hasRecipient) {
            return 'shipping';
        }
        if ($hasAddr) {
            return 'shipping';
        }
        // 패킹: 주문+상품(+수량) 정보가 많고 주소는 약함
        if ($hasOrder && $hasSku && ($hasQty || count($columns) >= 8)) {
            return 'packing';
        }
        // 피킹/SKU: 바코드·SKU 중심
        if ($hasSku && !$hasAddr && !$hasRecipient) {
            return count($sheet['rows'] ?? []) >= 100 ? 'inventory' : 'picking';
        }
        if ($hasOrder || $hasRecipient) {
            return 'shipping';
        }
        return 'picking';
    }

    private function useCaseLabel(string $use): string
    {
        return match ($use) {
            'shipping' => '배송·수취',
            'packing' => '패킹·내품',
            'picking' => '피킹·SKU',
            'inventory' => '검수·재고',
            'hangtag' => '행거·타공',
            'product' => '상품',
            default => '일반',
        };
    }

    /**
     * @param array<int, string> $columns
     * @param mixed $raw
     * @return array<int, array{column:string, kind:string}>
     */
    private function resolveFields(array $columns, mixed $raw, string $labelKind = 'general'): array
    {
        $known = [];
        foreach ($columns as $col) {
            $known[mb_strtolower($col)] = $col;
        }

        $isLogistics = in_array($labelKind, ['shipping', 'packing', 'picking', 'inventory', 'hangtag', 'logistics'], true);

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
                if ($this->isNoiseColumn($match)) {
                    continue;
                }
                $kind = $this->normalizeKind((string) ($item['kind'] ?? $item['type'] ?? ''), $match);
                $fields[] = ['column' => $match, 'kind' => $kind];
            }
        }
        if ($fields === []) {
            $ordered = $isLogistics
                ? $this->orderLogisticsColumns($columns, $labelKind)
                : $columns;
            foreach ($ordered as $col) {
                if ($this->isNoiseColumn($col)) {
                    continue;
                }
                $fields[] = ['column' => $col, 'kind' => $this->guessKind($col)];
            }
        }

        $max = match ($labelKind) {
            'shipping', 'packing', 'hangtag' => 7,
            'picking' => 5,
            'inventory' => 4,
            default => $isLogistics ? 7 : 10,
        };
        $seen = [];
        $out = [];
        foreach ($fields as $field) {
            $key = mb_strtolower($field['column']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $field;
            if (count($out) >= $max) {
                break;
            }
        }
        return $out;
    }

    /**
     * @param array<int, string> $columns
     * @return array<int, string>
     */
    private function orderLogisticsColumns(array $columns, string $use = 'shipping'): array
    {
        $rank = static function (string $col) use ($use): int {
            $base = 500;
            if (preg_match('/수취|recipient|받는|수령/i', $col)) {
                $base = 10;
            } elseif (preg_match('/주문자|고객명|성명|(^|[^a-z])name([^a-z]|$)/i', $col)) {
                $base = 20;
            } elseif (preg_match('/주소|address|addr/i', $col)) {
                $base = 30;
            } elseif (preg_match('/우편|zip|postal/i', $col)) {
                $base = 40;
            } elseif (preg_match('/city|state|도시|시\/도/i', $col)) {
                $base = 50;
            } elseif (preg_match('/전화|phone|mobile|연락/i', $col)) {
                $base = 60;
            } elseif (preg_match('/주문번호|order\s*(no|id|number)|운송장/i', $col)) {
                $base = 70;
            } elseif (preg_match('/sku|바코드|barcode|상품코드/i', $col)) {
                $base = 80;
            } elseif (preg_match('/상품|제품|품명|product|title/i', $col)) {
                $base = 90;
            } elseif (preg_match('/수량|qty|quantity|로케이션|location|bin/i', $col)) {
                $base = 100;
            }

            // 용도별 가중: 피킹·검수는 SKU/수량 우선, 배송은 수취·주소 우선
            if (in_array($use, ['picking', 'inventory'], true)) {
                if (preg_match('/sku|바코드|barcode|상품코드|품번/i', $col)) {
                    $base -= 60;
                }
                if (preg_match('/수량|qty|로케이션|location|bin/i', $col)) {
                    $base -= 40;
                }
                if (preg_match('/주소|address|수취/i', $col)) {
                    $base += 80;
                }
            }
            if ($use === 'packing') {
                if (preg_match('/상품|품명|sku|수량|주문번호/i', $col)) {
                    $base -= 20;
                }
            }
            return $base;
        };

        $cols = $columns;
        usort($cols, static function (string $a, string $b) use ($rank): int {
            return $rank($a) <=> $rank($b) ?: strcmp($a, $b);
        });
        return $cols;
    }

    private function isNoiseColumn(string $column): bool
    {
        return (bool) preg_match(
            '/url|homepage|링크|source\s*url|cost\s*price|단가|원가|금액|amount|price|status|상태|email|이메일|채널\s*id|판매채널\s*id/i',
            $column
        );
    }

    /**
     * @param array<int, array{column:string, kind:string}> $fields
     * @param array<string, mixed> $layout
     * @return array<string, mixed>
     */
    private function resolvePaper(string $labelKind, array $fields, array $layout, int $rowCount): array
    {
        $catalog = $this->papers->specs();
        $aiUse = trim((string) ($layout['use_case'] ?? $layout['useCase'] ?? ''));
        if ($aiUse !== '' && in_array($aiUse, ['shipping', 'packing', 'picking', 'inventory', 'hangtag', 'product', 'general'], true)) {
            // 사용자/AI가 명시한 용도를 우선하되, 이미 labelKind가 세부 용도면 유지
            if (in_array($labelKind, ['general', 'product', 'logistics'], true)) {
                $labelKind = $aiUse;
            }
        }
        $aiW = (float) ($layout['width_mm'] ?? 0);
        $aiH = (float) ($layout['height_mm'] ?? 0);
        $aiPaperNo = trim((string) ($layout['paper_no'] ?? $layout['paperNo'] ?? ''));

        if ($aiPaperNo !== '' && isset($catalog[$aiPaperNo])) {
            $picked = $catalog[$aiPaperNo];
            $per = max(1, (int) $picked['columns'] * (int) $picked['rows']);
            if ($per >= 4 || !in_array($labelKind, ['shipping', 'packing', 'picking', 'inventory', 'hangtag', 'logistics'], true)) {
                return $picked;
            }
        }

        if ($aiW >= 20 && $aiW <= 150 && $aiH >= 15 && $aiH <= 160) {
            $matched = $this->matchCatalogPaper($catalog, $aiW, $aiH, $labelKind);
            if ($matched !== null) {
                return $matched;
            }
            // 파는 용지 중에 그만한 것이 없으면 치수를 살려 급조한다. 목록이 비어 있을 때도
            // 이 길로 와서, 용지를 못 읽었다고 라벨 만들기가 멈추지는 않는다.
            if ($this->papers->isEmpty()) {
                return $this->improvisedPaper($aiW, $aiH);
            }
        }

        return $this->defaultPaperForUse($labelKind, $fields, $rowCount);
    }

    /**
     * 용도에 맞는 기본 용지. 품번을 적어 두는 대신 「어떤 용도에는 어느 정도 크기」라는
     * 뜻만 남기고, 그 크기에 가장 가까운 판매 용지를 AiPaperCatalogService 가 집는다.
     *
     * 자료 생김새를 보고 용도를 한 칸 옮기기도 한다. 패킹인데 넣을 것이 많거나 주소가 있으면
     * 좁은 칸에 다 안 들어가므로 배송 쪽 큰 칸으로, 피킹인데 줄 수가 아주 많고 넣을 것이
     * 적으면 한 장에 많이 뽑는 재고 쪽 작은 칸으로 간다.
     *
     * @param array<int, array{column:string, kind:string}> $fields
     * @return array<string, mixed>
     */
    private function defaultPaperForUse(string $use, array $fields, int $rowCount): array
    {
        $fieldCount = count($fields);
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

        $target = match ($use) {
            'shipping', 'logistics' => 'shipping',
            'packing' => ($fieldCount >= 6 || $hasAddr) ? 'shipping' : 'packing',
            'picking' => ($rowCount >= 200 && $fieldCount <= 3) ? 'inventory' : 'picking',
            'inventory' => 'inventory',
            'hangtag' => 'hangtag',
            'product' => $hasCode ? 'picking' : 'inventory',
            default => ($hasAddr || $fieldCount >= 6)
                ? 'shipping'
                : ($hasCode ? 'picking' : 'inventory'),
        };

        return $this->papers->specForUse($target) ?? $this->papers->specForUse('general') ?? $this->improvisedPaper(70.0, 36.0);
    }

    /**
     * 용지 목록을 끝내 못 읽었을 때 치수만 보고 급조하는 용지. 품번은 비워 둔다.
     * 팔지 않는 품번을 지어내 보여 주면 사용자가 그 품번으로 용지를 찾게 되기 때문이다.
     *
     * @return array<string, mixed>
     */
    private function improvisedPaper(float $w, float $h): array
    {
        $lw = max(10.0, min(200.0, $w));
        $lh = max(10.0, min(280.0, $h));
        $gap = 2.5;
        $minMargin = 5.0;
        $cols = max(1, (int) floor((210.0 - $minMargin * 2 + $gap) / ($lw + $gap)));
        $rows = max(1, (int) floor((297.0 - $minMargin * 2 + $gap) / ($lh + $gap)));

        $usedW = $lw * $cols + $gap * ($cols - 1);
        $usedH = $lh * $rows + $gap * ($rows - 1);
        $wText = rtrim(rtrim(sprintf('%.2f', $lw), '0'), '.');
        $hText = rtrim(rtrim(sprintf('%.2f', $lh), '0'), '.');

        return [
            'version' => 1,
            'paperNo' => '',
            'name' => sprintf('%s×%s mm %d칸', $wText, $hText, $cols * $rows),
            'category' => 'A4',
            'brand' => 'LabelUp',
            'paperWidthMm' => 210,
            'paperHeightMm' => 297,
            'labelWidthMm' => round($lw, 2),
            'labelHeightMm' => round($lh, 2),
            'columns' => $cols,
            'rows' => $rows,
            'leftMarginMm' => round((210.0 - $usedW) / 2, 2),
            'topMarginMm' => round((297.0 - $usedH) / 2, 2),
            'rightMarginMm' => round((210.0 - $usedW) / 2, 2),
            'bottomMarginMm' => round((297.0 - $usedH) / 2, 2),
            'hGapMm' => $gap,
            'vGapMm' => $gap,
            'labelColor' => '#FFFFFF',
            'shape' => ['kind' => 'rect'],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $catalog
     * @return ?array<string, mixed>
     */
    private function matchCatalogPaper(array $catalog, float $w, float $h, string $labelKind): ?array
    {
        $best = null;
        $bestScore = PHP_FLOAT_MAX;
        $preferMulti = in_array($labelKind, ['shipping', 'packing', 'picking', 'inventory', 'hangtag', 'logistics', 'product'], true);
        foreach ($catalog as $paper) {
            $pw = (float) $paper['labelWidthMm'];
            $ph = (float) $paper['labelHeightMm'];
            $per = max(1, (int) $paper['columns'] * (int) $paper['rows']);
            $score = abs($pw - $w) + abs($ph - $h);
            if ($preferMulti && $per < 4) {
                $score += 40;
            }
            if ($preferMulti && $per >= 8) {
                $score -= 5;
            }
            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $paper;
            }
        }
        return $bestScore <= 25 ? $best : null;
    }

    /**
     * @param array<int, array{column:string, kind:string}> $fields
     * @param array<int, string> $columns
     * @param array<int, string> $sample
     * @return array<int, array<string, mixed>>
     */
    private function placeFields(array $fields, float $w, float $h, array $columns, array $sample, string $use = 'general'): array
    {
        $colIndex = [];
        foreach ($columns as $idx => $name) {
            $colIndex[mb_strtolower((string) $name)] = $idx;
        }

        $roles = [];
        foreach ($fields as $field) {
            $si = $colIndex[mb_strtolower($field['column'])] ?? null;
            $val = $si === null ? '' : trim((string) ($sample[$si] ?? ''));
            if ($field['kind'] === 'date') {
                $val = $this->normalizeDateValue($val);
            }
            $roles[] = [
                'column' => $field['column'],
                'kind' => $field['kind'],
                'role' => $this->fieldRole($field['column'], $field['kind']),
                'sample' => $val !== '' ? $val : $field['column'],
            ];
        }

        return match ($use) {
            'shipping', 'logistics' => $this->layoutShipping($roles, $w, $h),
            'packing' => $this->layoutPacking($roles, $w, $h),
            'picking' => $this->layoutPicking($roles, $w, $h),
            'inventory' => $this->layoutInventory($roles, $w, $h),
            'hangtag' => $this->layoutHangtag($roles, $w, $h),
            default => $this->layoutStacked($roles, $w, $h),
        };
    }

    /** @param array<int, array{column:string, kind:string, role:string, sample:string}> $roles */
    private function layoutShipping(array $roles, float $w, float $h): array
    {
        $padX = max(2.8, $w * 0.05);
        $padY = max(2.2, $h * 0.06);
        $innerW = max(12.0, $w - $padX * 2);
        $objects = [];
        $z = 1;

        // 좌측 포인트 바
        $objects[] = $this->accentBar('labiAccent', 1.2, $padY, 1.4, max(8.0, $h - $padY * 2), $z++);

        $recipient = $this->takeRole($roles, ['recipient', 'name']);
        $address = $this->takeRole($roles, ['address']);
        $phone = $this->takeRole($roles, ['phone']);
        $order = $this->takeRole($roles, ['order']);
        $code = $this->takeRole($roles, ['sku', 'barcode', 'qr']);
        $rest = $roles;

        $y = $padY;
        if ($recipient) {
            $fh = min(9.0, max(6.5, $h * 0.16));
            $objects[] = $this->textObject(
                'labiName',
                $padX,
                $y,
                $innerW,
                $fh,
                $recipient['column'],
                $recipient['sample'],
                max(4.6, min(7.2, $fh * 0.62)),
                true,
                $z++,
                'left'
            );
            $y += $fh + 0.8;
        }

        if ($address) {
            $fh = min(16.0, max(10.0, $h * 0.28));
            $objects[] = $this->textObject(
                'labiAddr',
                $padX,
                $y,
                $innerW,
                $fh,
                $address['column'],
                $address['sample'],
                max(2.8, min(3.8, $fh * 0.28)),
                false,
                $z++,
                'left'
            );
            $y += $fh + 0.8;
        }

        $metaH = min(6.0, max(4.5, $h * 0.1));
        $half = ($innerW - 2.0) / 2;
        if ($phone || $order) {
            if ($phone) {
                $objects[] = $this->textObject('labiPhone', $padX, $y, $half, $metaH, $phone['column'], $phone['sample'], 2.8, false, $z++, 'left');
            }
            if ($order) {
                $objects[] = $this->textObject('labiOrder', $padX + $half + 2.0, $y, $half, $metaH, $order['column'], $order['sample'], 2.6, false, $z++, 'right');
            }
            $y += $metaH + 1.0;
        }

        foreach (array_slice($rest, 0, 2) as $i => $item) {
            if ($y > $h - $padY - 10) {
                break;
            }
            $fh = 4.2;
            $objects[] = $this->textObject('labiExtra' . $i, $padX, $y, $innerW, $fh, $item['column'], $item['sample'], 2.5, false, $z++, 'left');
            $y += $fh + 0.5;
        }

        if ($code) {
            $bh = min(14.0, max(9.0, $h - $y - $padY));
            if ($bh >= 7.0) {
                if ($code['kind'] === 'qr') {
                    $size = min($bh, $innerW * 0.28);
                    $objects[] = $this->qrObject('labiCode', $padX + ($innerW - $size) / 2, $y, $size, $code['column'], $code['sample'], $z++);
                } else {
                    $objects[] = $this->barcodeObject('labiCode', $padX, $y, $innerW, $bh, $code['column'], $code['sample'], $z++);
                }
            }
        }

        return $objects !== [] ? $objects : $this->layoutStacked($roles, $w, $h);
    }

    /** @param array<int, array{column:string, kind:string, role:string, sample:string}> $roles */
    private function layoutPacking(array $roles, float $w, float $h): array
    {
        $padX = max(3.0, $w * 0.05);
        $padY = max(2.5, $h * 0.06);
        $innerW = max(12.0, $w - $padX * 2);
        $objects = [];
        $z = 1;
        $objects[] = $this->accentBar('labiAccent', 1.4, $padY, 1.6, max(10.0, $h - $padY * 2), $z++);

        $order = $this->takeRole($roles, ['order']);
        $product = $this->takeRole($roles, ['product', 'name']);
        $code = $this->takeRole($roles, ['sku', 'barcode', 'qr']);
        $qty = $this->takeRole($roles, ['qty']);
        $y = $padY;

        if ($order) {
            $objects[] = $this->textObject('labiOrder', $padX, $y, $innerW * 0.7, 5.5, $order['column'], $order['sample'], 2.8, false, $z++, 'left');
            if ($qty) {
                $objects[] = $this->textObject('labiQty', $padX + $innerW * 0.72, $y, $innerW * 0.28, 5.5, $qty['column'], '×' . $qty['sample'], 3.4, true, $z++, 'right');
            }
            $y += 6.5;
        }
        if ($product) {
            $fh = min(12.0, max(8.0, $h * 0.22));
            $objects[] = $this->textObject('labiProd', $padX, $y, $innerW, $fh, $product['column'], $product['sample'], max(3.2, min(4.8, $fh * 0.4)), true, $z++, 'left');
            $y += $fh + 1.0;
        }
        foreach (array_slice($roles, 0, 2) as $i => $item) {
            $objects[] = $this->textObject('labiMeta' . $i, $padX, $y, $innerW, 4.5, $item['column'], $item['sample'], 2.5, false, $z++, 'left');
            $y += 5.0;
        }
        if ($code) {
            $bh = min(13.0, max(8.0, $h - $y - $padY));
            if ($bh >= 7.0) {
                $objects[] = $this->barcodeObject('labiCode', $padX, $y, $innerW, $bh, $code['column'], $code['sample'], $z++);
            }
        }
        return $objects !== [] ? $objects : $this->layoutStacked($roles, $w, $h);
    }

    /** @param array<int, array{column:string, kind:string, role:string, sample:string}> $roles */
    private function layoutPicking(array $roles, float $w, float $h): array
    {
        $padX = max(2.4, $w * 0.05);
        $padY = max(2.0, $h * 0.07);
        $innerW = max(10.0, $w - $padX * 2);
        $objects = [];
        $z = 1;
        $sku = $this->takeRole($roles, ['sku', 'barcode']);
        $product = $this->takeRole($roles, ['product', 'name']);
        $qty = $this->takeRole($roles, ['qty']);
        $code = $this->takeRole($roles, ['barcode', 'qr', 'sku']);
        $y = $padY;

        $title = $sku ?: $product;
        if ($title) {
            $objects[] = $this->textObject('labiTitle', $padX, $y, $innerW * ($qty ? 0.72 : 1), 7.0, $title['column'], $title['sample'], 4.2, true, $z++, 'left');
            if ($qty) {
                $objects[] = $this->textObject('labiQty', $padX + $innerW * 0.74, $y, $innerW * 0.26, 7.0, $qty['column'], $qty['sample'], 4.5, true, $z++, 'right');
            }
            $y += 8.0;
        }
        if ($product && $title !== $product) {
            $objects[] = $this->textObject('labiProd', $padX, $y, $innerW, 6.0, $product['column'], $product['sample'], 2.7, false, $z++, 'left');
            $y += 6.5;
        }
        if ($code) {
            $bh = max(9.0, $h - $y - $padY);
            if ($code['kind'] === 'qr') {
                $size = min($bh, $innerW * 0.36);
                $objects[] = $this->qrObject('labiCode', $padX + ($innerW - $size) / 2, $y, $size, $code['column'], $code['sample'], $z++);
            } else {
                $objects[] = $this->barcodeObject('labiCode', $padX, $y, $innerW, $bh, $code['column'], $code['sample'], $z++);
            }
        }
        return $objects !== [] ? $objects : $this->layoutStacked($roles, $w, $h);
    }

    /** @param array<int, array{column:string, kind:string, role:string, sample:string}> $roles */
    private function layoutInventory(array $roles, float $w, float $h): array
    {
        $padX = max(2.0, $w * 0.06);
        $padY = max(1.8, $h * 0.08);
        $innerW = max(8.0, $w - $padX * 2);
        $objects = [];
        $z = 1;
        $sku = $this->takeRole($roles, ['sku', 'barcode', 'product', 'name']);
        $qty = $this->takeRole($roles, ['qty']);
        $code = $this->takeRole($roles, ['barcode', 'qr', 'sku']);
        $y = $padY;
        if ($sku) {
            $objects[] = $this->textObject('labiSku', $padX, $y, $innerW, 6.0, $sku['column'], $sku['sample'], 3.4, true, $z++, 'center');
            $y += 6.5;
        }
        if ($qty) {
            $objects[] = $this->textObject('labiQty', $padX, $y, $innerW, 4.5, $qty['column'], $qty['sample'], 2.8, false, $z++, 'center');
            $y += 5.0;
        }
        if ($code) {
            $bh = max(7.0, $h - $y - $padY);
            $objects[] = $this->barcodeObject('labiCode', $padX, $y, $innerW, $bh, $code['column'], $code['sample'], $z++);
        }
        return $objects !== [] ? $objects : $this->layoutStacked($roles, $w, $h);
    }

    /** @param array<int, array{column:string, kind:string, role:string, sample:string}> $roles */
    private function layoutHangtag(array $roles, float $w, float $h): array
    {
        // 타공 영역(우측 상단)을 비우고 텍스트·바코드를 배치
        $padX = max(3.0, $w * 0.05);
        $padY = max(3.0, $h * 0.07);
        $holeReserve = min(28.0, $w * 0.34);
        $textW = max(20.0, $w - $padX * 2 - $holeReserve);
        $objects = [];
        $z = 1;
        $product = $this->takeRole($roles, ['product', 'name', 'recipient']);
        $sku = $this->takeRole($roles, ['sku', 'order']);
        $code = $this->takeRole($roles, ['barcode', 'qr', 'sku']);
        $y = $padY + 2;
        if ($product) {
            $objects[] = $this->textObject('labiProd', $padX, $y, $textW, 10.0, $product['column'], $product['sample'], 4.0, true, $z++, 'left');
            $y += 11.0;
        }
        if ($sku) {
            $objects[] = $this->textObject('labiSku', $padX, $y, $textW, 6.0, $sku['column'], $sku['sample'], 2.8, false, $z++, 'left');
            $y += 7.0;
        }
        foreach (array_slice($roles, 0, 2) as $i => $item) {
            $objects[] = $this->textObject('labiMeta' . $i, $padX, $y, $textW, 5.0, $item['column'], $item['sample'], 2.4, false, $z++, 'left');
            $y += 5.5;
        }
        if ($code) {
            $bh = min(12.0, max(8.0, $h - $y - $padY));
            $objects[] = $this->barcodeObject('labiCode', $padX, $h - $padY - $bh, max(24.0, $w - $padX * 2), $bh, $code['column'], $code['sample'], $z++);
        }
        return $objects !== [] ? $objects : $this->layoutStacked($roles, $w, $h);
    }

    /** @param array<int, array{column:string, kind:string, role:string, sample:string}> $roles */
    private function layoutStacked(array $roles, float $w, float $h): array
    {
        $padX = max(2.4, $w * 0.06);
        $padY = max(2.0, $h * 0.08);
        $innerW = max(10.0, $w - $padX * 2);
        $innerH = max(8.0, $h - $padY * 2);
        $gap = 1.0;
        $weights = [];
        foreach ($roles as $i => $field) {
            $weights[$i] = match ($field['kind']) {
                'barcode' => 2.2,
                'qr' => 2.6,
                'date' => 1.1,
                'serial' => 1.2,
                default => in_array($field['role'], ['recipient', 'name', 'product', 'serial'], true) ? 1.7 : 1.0,
            };
        }
        $sum = array_sum($weights) ?: 1;
        $objects = [];
        $y = $padY;
        $z = 1;
        foreach ($roles as $i => $field) {
            $fh = max(4.0, ($innerH - $gap * (count($roles) - 1)) * ($weights[$i] / $sum));
            $id = sprintf('labi%02d', $i + 1);
            if ($field['kind'] === 'barcode') {
                $objects[] = $this->barcodeObject($id, $padX, $y, $innerW, $fh, $field['column'], $field['sample'], $z++);
            } elseif ($field['kind'] === 'qr') {
                $size = min($fh, $innerW * 0.34);
                $objects[] = $this->qrObject($id, $padX + ($innerW - $size) / 2, $y, $size, $field['column'], $field['sample'], $z++);
            } else {
                $title = in_array($field['role'], ['recipient', 'name', 'product', 'serial'], true)
                    || $field['kind'] === 'serial';
                $objects[] = $this->textObject(
                    $id,
                    $padX,
                    $y,
                    $innerW,
                    $fh,
                    $field['column'],
                    $field['sample'],
                    $title ? max(4.0, min(7.0, $fh * 0.5)) : max(2.6, min(4.2, $fh * 0.4)),
                    $title,
                    $z++,
                    'left',
                    $field['kind']
                );
            }
            $y += $fh + $gap;
        }
        return $objects;
    }

    /**
     * @param array<int, array{column:string, kind:string, role:string, sample:string}> $roles
     * @param array<int, string> $want
     * @return ?array{column:string, kind:string, role:string, sample:string}
     */
    private function takeRole(array &$roles, array $want): ?array
    {
        foreach ($roles as $i => $item) {
            if (in_array($item['role'], $want, true) || ($item['kind'] !== 'text' && in_array($item['kind'], $want, true))) {
                $found = $item;
                array_splice($roles, $i, 1);
                return $found;
            }
        }
        return null;
    }

    private function fieldRole(string $column, string $kind): string
    {
        if ($kind === 'qr') {
            return 'qr';
        }
        if ($kind === 'date') {
            return 'date';
        }
        if ($kind === 'serial') {
            return 'serial';
        }
        if ($kind === 'barcode' || preg_match('/sku|바코드|barcode|상품코드|품번/i', $column)) {
            return 'sku';
        }
        if (preg_match('/시리얼|일련|serial|lot\b|로트|제조번호/i', $column)) {
            return 'serial';
        }
        if (preg_match('/날짜|일자|date|납기|출고일|입고일|주문일|제조일|유통기한|유효|만료|배송일/i', $column)) {
            return 'date';
        }
        if (preg_match('/수취|recipient|받는|수령/i', $column)) {
            return 'recipient';
        }
        if (preg_match('/주문자|고객명|성명|(^|[^a-z])name([^a-z]|$)/i', $column)) {
            return 'name';
        }
        if (preg_match('/주소|address|addr|city|state|우편|zip|postal/i', $column)) {
            return 'address';
        }
        if (preg_match('/전화|phone|mobile|연락/i', $column)) {
            return 'phone';
        }
        if (preg_match('/주문번호|order\s*(no|id|number)|운송장/i', $column)) {
            return 'order';
        }
        if (preg_match('/수량|qty|quantity/i', $column)) {
            return 'qty';
        }
        if (preg_match('/상품|제품|품명|product|title/i', $column)) {
            return 'product';
        }
        return 'other';
    }

    /** @return array<string, mixed> */
    private function accentBar(string $id, float $x, float $y, float $w, float $h, int $z): array
    {
        return [
            'id' => $id,
            'type' => 'rect',
            'zIndex' => $z,
            'locked' => false,
            'visible' => true,
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round($w, 2),
            'height' => round($h, 2),
            'rotation' => 0,
            'fill' => '#7B2840',
            'stroke' => 'transparent',
            'strokeWidth' => 0,
            'opacity' => 1,
            'shapeKind' => 'rect',
            'cornerRadiusMm' => 0.6,
            'backgroundTransparent' => false,
        ];
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
        int $z,
        string $align = 'left',
        string $kind = 'text'
    ): array {
        if ($kind === 'text') {
            $detected = $this->guessKind($column);
            if (in_array($detected, ['date', 'serial'], true)) {
                $kind = $detected;
            }
        }

        $raw = trim($text);
        $dateFormat = 'yyyy-MM-dd';
        if ($kind === 'date') {
            $raw = $this->normalizeDateValue($raw);
            $dateFormat = $this->inferDateFormat($column, $raw);
            $formatted = $this->formatDateValue($raw, $dateFormat);
            $display = $formatted !== '' ? $formatted : ($raw !== '' ? $raw : '[' . $column . ']');
        } else {
            $display = $raw;
            if ($display === '') {
                $display = '[' . $column . ']';
            } elseif (mb_strlen($display) > 48) {
                $display = mb_substr($display, 0, 47) . '…';
            }
        }

        $obj = [
            'id' => $id,
            'type' => 'text',
            'zIndex' => $z,
            'locked' => false,
            'visible' => true,
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round($w, 2),
            'height' => round($h, 2),
            'rotation' => 0,
            'fill' => $bold ? '#7B2840' : '#2E2A27',
            'stroke' => 'transparent',
            'strokeWidth' => 0,
            'opacity' => 1,
            'dataBound' => true,
            'dataColumn' => $column,
            'dataDisplayKind' => $kind === 'date' ? 'date' : 'text',
            'text' => $display,
            'fontSize' => $fontSize,
            'fontFamily' => 'Pretendard',
            'bold' => $bold,
            'italic' => false,
            'underline' => false,
            'textAlign' => $align,
            'verticalAlign' => 'middle',
            'lineHeight' => 1.15,
            'letterSpacing' => 0,
            'textDirection' => 'horizontal',
            'textWrap' => 'char',
            'backgroundTransparent' => true,
            'backgroundFill' => 'transparent',
            'textMode' => 'normal',
            'wordArtStyle' => 'none',
            'customKind' => 'none',
        ];
        if ($kind === 'date') {
            $obj['dataDateFormat'] = $dateFormat;
        }
        if ($kind === 'serial') {
            // 시리얼·일련번호도 자료 연결 텍스트(행마다 Excel/CSV 값 사용)
            $obj['dataDisplayKind'] = 'text';
        }
        return $obj;
    }

    /** @return array<string, mixed> */
    private function barcodeObject(string $id, float $x, float $y, float $w, float $h, string $column, string $sample, int $z): array
    {
        return [
            'id' => $id,
            'type' => 'barcode',
            'zIndex' => $z,
            'locked' => false,
            'visible' => true,
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round($w, 2),
            'height' => round($h, 2),
            'rotation' => 0,
            'fill' => '#2E2A27',
            'stroke' => 'transparent',
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
            'locked' => false,
            'visible' => true,
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round($size, 2),
            'height' => round($size, 2),
            'rotation' => 0,
            'fill' => '#2E2A27',
            'stroke' => 'transparent',
            'strokeWidth' => 0,
            'opacity' => 1,
            'dataBound' => true,
            'dataColumn' => $column,
            'text' => '[' . $column . ']',
            'barcodeFormat' => 'QR_CODE',
            'barcodeValue' => $sample !== '' ? $sample : 'https://labelup.kr',
            'barcodeShowText' => false,
            'qrEcc' => 'M',
            'qrKind' => 'url',
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
        if (in_array($kind, ['date', 'datetime', 'day'], true)) {
            return 'date';
        }
        if (in_array($kind, ['serial', 'serialno', 'sequence', 'seq', 'lot'], true)) {
            return 'serial';
        }
        return $this->guessKind($column);
    }

    private function guessKind(string $column): string
    {
        if (preg_match('/날짜|일자|date|datetime|납기|출고일|입고일|주문일|제조일|유통기한|유효기간|만료|배송일|생산일/i', $column)) {
            return 'date';
        }
        if (preg_match('/시리얼|일련번호|일련\s*번호|serial\s*(no|number|#)?|s\/?n\b|lot\s*(no|number)?|로트\s*번호|제조번호/i', $column)) {
            return 'serial';
        }
        if (preg_match('/qr|링크|url|homepage|홈페이지/i', $column)) {
            return 'qr';
        }
        if (preg_match('/바코드|barcode|sku|isbn|gtin|ean|jan|상품코드|품번/i', $column)) {
            return 'barcode';
        }
        return 'text';
    }

    /**
     * 날짜·시리얼 열 값을 편집기 자료연결에 맞게 정규화한다.
     *
     * @param array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary?:string} $sheet
     * @param array<int, array{column:string, kind:string}> $fields
     * @return array{source_name:string, source_kind:string, columns:array<int,string>, rows:array<int,array<int,string>>, summary?:string}
     */
    private function normalizeSheetForFields(array $sheet, array $fields): array
    {
        $colIndex = [];
        foreach ($sheet['columns'] as $i => $name) {
            $colIndex[mb_strtolower((string) $name)] = $i;
        }
        $dateCols = [];
        $serialCols = [];
        foreach ($fields as $field) {
            $idx = $colIndex[mb_strtolower($field['column'])] ?? null;
            if ($idx === null) {
                continue;
            }
            if ($field['kind'] === 'date') {
                $dateCols[$idx] = true;
            } elseif ($field['kind'] === 'serial') {
                $serialCols[$idx] = true;
            }
        }
        // 필드에 없어도 열 이름으로 날짜/시리얼 후보 보강
        foreach ($sheet['columns'] as $i => $name) {
            $kind = $this->guessKind((string) $name);
            if ($kind === 'date') {
                $dateCols[$i] = true;
            } elseif ($kind === 'serial') {
                $serialCols[$i] = true;
            }
        }
        if ($dateCols === [] && $serialCols === []) {
            return $sheet;
        }

        $rows = [];
        foreach ($sheet['rows'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            foreach ($dateCols as $i => $_) {
                if (!array_key_exists($i, $row)) {
                    continue;
                }
                $row[$i] = $this->normalizeDateValue((string) $row[$i]);
            }
            foreach ($serialCols as $i => $_) {
                if (!array_key_exists($i, $row)) {
                    continue;
                }
                $row[$i] = trim((string) $row[$i]);
            }
            $rows[] = $row;
        }
        $sheet['rows'] = $rows;
        return $sheet;
    }

    private function normalizeDateValue(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        // Excel 날짜 시리얼(대략 1954~2064)
        if (preg_match('/^\d{5}(?:\.\d+)?$/', $raw)) {
            $n = (float) $raw;
            if ($n >= 20000 && $n <= 60000) {
                $unix = (int) round(($n - 25569) * 86400);
                if ($unix > 0) {
                    return gmdate('Y-m-d', $unix);
                }
            }
        }
        if (preg_match('/^(\d{4})[.\-\/](\d{1,2})[.\-\/](\d{1,2})/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }
        if (preg_match('/^(\d{4})년\s*(\d{1,2})월\s*(\d{1,2})일/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }
        $ts = strtotime($raw);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }
        return $raw;
    }

    private function inferDateFormat(string $column, string $sample): string
    {
        if (preg_match('/년|월|일/u', $column) || preg_match('/년|월|일/u', $sample)) {
            return 'yyyy년 M월 d일';
        }
        if (preg_match('/\d{4}\.\d{1,2}\.\d{1,2}/', $sample)) {
            return 'yyyy.MM.dd';
        }
        return 'yyyy-MM-dd';
    }

    private function formatDateValue(string $raw, string $format): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        $ts = strtotime($raw);
        if ($ts === false) {
            return '';
        }
        return match ($format) {
            'yyyy년 M월 d일' => date('Y년 n월 j일', $ts),
            'yyyy.MM.dd' => date('Y.m.d', $ts),
            'yy-MM-dd' => date('y-m-d', $ts),
            'MM/dd/yyyy' => date('m/d/Y', $ts),
            'dd/MM/yyyy' => date('d/m/Y', $ts),
            default => date('Y-m-d', $ts),
        };
    }

    private function isTitleColumn(string $column): bool
    {
        return (bool) preg_match('/이름|성명|name|상품명|제품명|품명|title|상호|회사명|수취|recipient/i', $column);
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
                if ($projectId > 0) {
                    $editorUrl = url('editor/') . '?labiDoc=1&project=' . $projectId;
                }
            } catch (RuntimeException) {
                $projectId = null;
            }
        }

        $preview = [];
        foreach (array_slice($sheet['rows'], 0, 4) as $row) {
            $preview[] = array_slice($row, 0, 6);
        }

        $previewSvg = '';
        try {
            $previewSvg = LabelTemplatePreview::svgFromRow([
                'name' => $title,
                'widthMm' => $built['width_mm'],
                'heightMm' => $built['height_mm'],
            ], is_array($document) ? $document : []);
        } catch (\Throwable) {
            $previewSvg = '';
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
            'preview_svg' => $previewSvg,
            'paper_name' => (string) ($built['paper_name'] ?? ''),
            'labels_per_page' => (int) ($built['labels_per_page'] ?? 1),
            'use_case' => (string) ($built['use_case'] ?? ''),
            'use_case_label' => (string) ($built['use_case_label'] ?? ''),
            'dataset' => [
                'source_name' => $sheet['source_name'],
                'source_kind' => $sheet['source_kind'],
                'columns' => $sheet['columns'],
                'row_count' => count($sheet['rows']),
                'preview_rows' => $preview,
                'paper_name' => (string) ($built['paper_name'] ?? ''),
                'labels_per_page' => (int) ($built['labels_per_page'] ?? 1),
                'use_case' => (string) ($built['use_case'] ?? ''),
                'use_case_label' => (string) ($built['use_case_label'] ?? ''),
            ],
        ];
    }
}
