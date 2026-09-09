<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 첨부 스티커팩 60종 — 일러스트 PNG + 편집 가능 텍스트/도형 조합.
 * 매니페스트: storage/imports/template_pack60_manifest.json
 */
final class LabelTemplatePack60SeedService
{
    private int $seq = 0;
    private int $z = 0;

    public function __construct(
        private string $manifestRel = 'imports/template_pack60_manifest.json'
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $path = storage_path($this->manifestRel);
        if (!is_file($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        $data = json_decode((string) $raw, true);
        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            return [];
        }

        $out = [];
        foreach ($data['items'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $this->seq = 0;
            $this->z = 0;
            $w = (float) ($row['paper_w_mm'] ?? 50);
            $h = (float) ($row['paper_h_mm'] ?? 50);
            $bg = (string) ($row['bg'] ?? '#FFFFFF');
            $tone = (string) ($row['tone'] ?? '#7B2840');
            $layout = (string) ($row['layout'] ?? 'art_top');
            $artUrl = trim((string) ($row['art_url'] ?? ''));
            $texts = is_array($row['texts'] ?? null) ? $row['texts'] : [];
            $objects = $this->compose($w, $h, $bg, $tone, $layout, $artUrl, $texts);
            $paper = $this->paper(
                (string) ($row['paper_no'] ?? 'LU-P60'),
                (string) ($row['name'] ?? 'Sticker'),
                $w,
                $h,
                'roundrect',
                3.0,
                $bg
            );
            $envelope = $this->envelope((string) ($row['name'] ?? 'Sticker'), $paper, $bg, $objects);
            $out[] = [
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'category' => (string) ($row['category'] ?? 'gift'),
                'tags' => (string) ($row['tags'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'tone' => $tone,
                'paper_no' => $paper['paperNo'],
                'paper_w_mm' => $paper['labelWidthMm'],
                'paper_h_mm' => $paper['labelHeightMm'],
                'paper_shape' => $paper['shape']['kind'],
                'document_json' => json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'sort_order' => (int) ($row['sort_order'] ?? 100),
            ];
        }
        return $out;
    }

    /**
     * @param array<int, mixed> $texts
     * @return array<int, array<string, mixed>>
     */
    private function compose(float $w, float $h, string $bg, string $tone, string $layout, string $artUrl, array $texts): array
    {
        $texts = $this->ensureSampleTexts($texts, $tone);
        return match ($layout) {
            'product', 'art_top', 'full_sticker' => $this->layoutOverlay($w, $h, $bg, $tone, $artUrl, $texts, 'product'),
            'stack', 'bottom' => $this->layoutOverlay($w, $h, $bg, $tone, $artUrl, $texts, 'stack'),
            'art_bottom' => $this->layoutOverlay($w, $h, $bg, $tone, $artUrl, $texts, 'art_bottom'),
            'deco_top' => $this->layoutOverlay($w, $h, $bg, $tone, $artUrl, $texts, 'deco_top'),
            'center', 'art_center' => $this->layoutOverlay($w, $h, $bg, $tone, $artUrl, $texts, 'center'),
            'icon', 'art_icon' => $this->layoutOverlay($w, $h, $bg, $tone, $artUrl, $texts, 'icon'),
            'badge' => $this->layoutOverlay($w, $h, $bg, $tone, $artUrl, $texts, 'badge'),
            'special_barcode' => $this->layoutBarcode($w, $h, $bg, $tone, $artUrl, $texts),
            'special_price' => $this->layoutPrice($w, $h, $bg, $tone, $texts),
            'special_sale' => $this->layoutSale($w, $h, $tone, $texts),
            'special_event' => $this->layoutBoxed($w, $h, $bg, $tone, $texts, 2.2),
            'special_coupon' => $this->layoutBoxed($w, $h, $bg, $tone, $texts, 2.2),
            'special_sample' => $this->layoutBoxed($w, $h, $bg, $tone, $texts, 1.6),
            'special_lot' => $this->layoutLot($w, $h, $bg, $tone, $texts),
            'special_dates' => $this->layoutDates($w, $h, $bg, $tone, $texts),
            'special_qr' => $this->layoutQr($w, $h, $bg, $tone, $artUrl, $texts),
            default => $this->layoutOverlay($w, $h, $bg, $tone, $artUrl, $texts, 'product'),
        };
    }

    /**
     * 샘플 문구가 없으면 교체용 기본 텍스트를 채운다.
     * @param array<int, mixed> $texts
     * @return array<int, array{text:string,role:string,color:string}>
     */
    private function ensureSampleTexts(array $texts, string $tone): array
    {
        $out = [];
        foreach ($texts as $t) {
            if (!is_array($t)) {
                continue;
            }
            $text = trim((string) ($t['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $out[] = [
                'text' => $text,
                'role' => (string) ($t['role'] ?? 'title'),
                'color' => (string) ($t['color'] ?? $tone),
            ];
        }
        if ($this->textByRole($out, 'title') === null) {
            array_unshift($out, ['text' => '샘플 제목', 'role' => 'title', 'color' => $tone]);
        }
        return $out;
    }

    /**
     * 이미지 배경 위에 샘플 텍스트를 올린다(텍스트는 편집·교체 가능).
     * @param array<int, mixed> $texts
     * @return array<int, array<string, mixed>>
     */
    private function layoutOverlay(float $w, float $h, string $bg, string $tone, string $artUrl, array $texts, string $mode): array
    {
        $objs = [$this->rect(0, 0, $w, $h, $bg)];
        $title = $this->textByRole($texts, 'title');
        $mid = $this->textByRole($texts, 'mid');
        $sub = $this->textByRole($texts, 'sub');

        if ($mode === 'product') {
            // 참조 시트 식품형: 상단 제목 / 중앙 이미지 / 하단 보조문 (텍스트가 이미지 위)
            if ($artUrl !== '') {
                $artY = $mid !== null ? $h * 0.28 : $h * 0.22;
                $artH = $mid !== null ? $h * 0.46 : $h * 0.52;
                $objs[] = $this->image($w * 0.14, $artY, $w * 0.72, $artH, $artUrl);
            }
            if ($title !== null) {
                $objs[] = $this->text(3, $h * 0.05, $w - 6, $h * 0.14, $title['text'], $title['color'] ?: $tone, max(3.8, $h * 0.09), true);
            }
            if ($mid !== null) {
                $objs[] = $this->text(3.5, $h * 0.17, $w - 7, $h * 0.1, $mid['text'], $mid['color'] ?: $tone, max(2.4, $h * 0.055), false);
            }
            if ($sub !== null) {
                $objs[] = $this->text(3, $h * 0.80, $w - 6, $h * 0.14, $sub['text'], $sub['color'] ?: '#6B6570', max(2.4, $h * 0.055), false);
            }
            return $objs;
        }

        if ($mode === 'stack') {
            // 상단 아이콘/일러스트, 하단 텍스트 (스마일·케이크·클로버 등)
            if ($artUrl !== '') {
                $objs[] = $this->image($w * 0.16, $h * 0.06, $w * 0.68, $h * 0.46, $artUrl);
            }
            if ($title !== null) {
                $objs[] = $this->text(3, $h * 0.56, $w - 6, $h * 0.18, $title['text'], $title['color'] ?: $tone, max(3.6, $h * 0.09), true);
            }
            if ($sub !== null) {
                $objs[] = $this->text(3.5, $h * 0.76, $w - 7, $h * 0.16, $sub['text'], $sub['color'] ?: '#6B6570', max(2.3, $h * 0.055), false);
            }
            return $objs;
        }

        if ($mode === 'art_bottom') {
            // 참조 시트 인사형: 상단·중앙 텍스트, 하단 장식 일러스트
            if ($artUrl !== '') {
                $objs[] = $this->image($w * 0.12, $h * 0.52, $w * 0.76, $h * 0.42, $artUrl);
            }
            if ($title !== null) {
                $objs[] = $this->text(3, $h * 0.10, $w - 6, $h * 0.20, $title['text'], $title['color'] ?: $tone, max(3.8, $h * 0.095), true);
            }
            if ($mid !== null) {
                $objs[] = $this->text(3.5, $h * 0.30, $w - 7, $h * 0.10, $mid['text'], $mid['color'] ?: $tone, max(2.4, $h * 0.055), false);
                if ($sub !== null) {
                    $objs[] = $this->text(3.5, $h * 0.40, $w - 7, $h * 0.10, $sub['text'], $sub['color'] ?: '#6B6570', max(2.2, $h * 0.05), false);
                }
            } elseif ($sub !== null) {
                $objs[] = $this->text(3.5, $h * 0.32, $w - 7, $h * 0.14, $sub['text'], $sub['color'] ?: '#6B6570', max(2.3, $h * 0.05), false);
            }
            return $objs;
        }

        if ($mode === 'deco_top') {
            // 리본/상단 장식 + 중앙 타이틀
            if ($artUrl !== '') {
                $objs[] = $this->image($w * 0.18, $h * 0.04, $w * 0.64, $h * 0.32, $artUrl);
            }
            if ($title !== null) {
                $objs[] = $this->text(3, $h * 0.40, $w - 6, $h * 0.22, $title['text'], $title['color'] ?: $tone, max(3.8, $h * 0.09), true);
            }
            if ($sub !== null) {
                $objs[] = $this->text(3.5, $h * 0.66, $w - 7, $h * 0.16, $sub['text'], $sub['color'] ?: '#6B6570', max(2.3, $h * 0.05), false);
            }
            return $objs;
        }

        if ($mode === 'icon') {
            // 배송/인증 아이콘: 상단 원형 아이콘 + 하단 문구
            $objs[] = $this->ellipse($w * 0.18, $h * 0.04, $w * 0.64, $h * 0.48, '#FFFFFF', $tone, 0.45);
            if ($artUrl !== '') {
                $objs[] = $this->image($w * 0.26, $h * 0.10, $w * 0.48, $h * 0.36, $artUrl);
            }
            if ($title !== null) {
                $objs[] = $this->text(2.5, $h * 0.56, $w - 5, $h * 0.18, $title['text'], $title['color'] ?: $tone, max(3.2, $h * 0.078), true);
            }
            if ($sub !== null) {
                $objs[] = $this->text(3, $h * 0.78, $w - 6, $h * 0.14, $sub['text'], $sub['color'] ?: '#6B6570', max(2.2, $h * 0.05), false);
            }
            return $objs;
        }

        if ($mode === 'badge') {
            // NEW/BEST/LOVE: 중앙 아트 위 타이틀, 하단 보조
            if ($artUrl !== '') {
                $objs[] = $this->image($w * 0.10, $h * 0.08, $w * 0.80, $h * 0.58, $artUrl);
            }
            if ($title !== null) {
                $objs[] = $this->text(3, $h * 0.26, $w - 6, $h * 0.22, $title['text'], $title['color'] ?: '#FFFFFF', max(5.0, $h * 0.12), true);
            }
            if ($sub !== null) {
                $objs[] = $this->text(3, $h * 0.74, $w - 6, $h * 0.16, $sub['text'], $sub['color'] ?: $tone, max(2.6, $h * 0.06), false);
            }
            return $objs;
        }

        // center: 리스/메시지형 — 아트 풀 + 중앙 타이틀
        if ($artUrl !== '') {
            $objs[] = $this->image($w * 0.04, $h * 0.04, $w * 0.92, $h * 0.92, $artUrl);
        }
        if ($title !== null) {
            $objs[] = $this->text(4, $h * 0.34, $w - 8, $h * 0.22, $title['text'], $title['color'] ?: $tone, max(3.8, $h * 0.095), true);
        }
        if ($sub !== null) {
            $objs[] = $this->text(4, $h * 0.70, $w - 8, $h * 0.16, $sub['text'], $sub['color'] ?: $tone, max(2.4, $h * 0.055), false);
        }
        return $objs;
    }

    private function layoutArtTop(float $w, float $h, string $bg, string $tone, string $artUrl, array $texts): array
    {
        return $this->layoutOverlay($w, $h, $bg, $tone, $artUrl, $texts, 'product');
    }

    /** @param array<int, mixed> $texts @return array<int, array<string, mixed>> */
    private function layoutArtCenter(float $w, float $h, string $bg, string $tone, string $artUrl, array $texts): array
    {
        return $this->layoutOverlay($w, $h, $bg, $tone, $artUrl, $texts, 'center');
    }

    /** @param array<int, mixed> $texts @return array<int, array<string, mixed>> */
    private function layoutArtIcon(float $w, float $h, string $bg, string $tone, string $artUrl, array $texts): array
    {
        return $this->layoutOverlay($w, $h, $bg, $tone, $artUrl, $texts, 'icon');
    }

    /** @param array<int, mixed> $texts @return array<int, array<string, mixed>> */
    private function layoutBarcode(float $w, float $h, string $bg, string $tone, string $artUrl, array $texts): array
    {
        $objs = [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(1.5, 1.5, $w - 3, $h - 3, '#FFFFFF', $tone, 0.35),
        ];
        $title = $this->textByRole($texts, 'title');
        $sub = $this->textByRole($texts, 'sub');
        if ($title !== null) {
            $objs[] = $this->text(3, 3, $w - 6, 7, $title['text'], $title['color'] ?: $tone, 3.2, true);
        }
        $objs[] = $this->barcode(5, 12, $w - 10, 24, $sub['text'] ?? '8801234567890');
        if ($sub !== null) {
            $objs[] = $this->text(3, $h - 10, $w - 6, 6, $sub['text'], '#616161', 2.2, false);
        }
        return $objs;
    }

    /** @param array<int, mixed> $texts @return array<int, array<string, mixed>> */
    private function layoutPrice(float $w, float $h, string $bg, string $tone, array $texts): array
    {
        $objs = [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(2, 2, $w - 4, $h - 4, '#FFFFFF', $tone, 1.2),
        ];
        $title = $this->textByRole($texts, 'title');
        $sub = $this->textByRole($texts, 'sub');
        if ($title !== null) {
            $objs[] = $this->text(4, 8, $w - 8, 10, $title['text'], $title['color'] ?: $tone, 3.6, true);
        }
        if ($sub !== null) {
            $objs[] = $this->text(4, 22, $w - 8, 18, $sub['text'], $sub['color'] ?: $tone, 7.5, true);
        }
        return $objs;
    }

    /** @param array<int, mixed> $texts @return array<int, array<string, mixed>> */
    private function layoutSale(float $w, float $h, string $tone, array $texts): array
    {
        $objs = [$this->rect(0, 0, $w, $h, $tone)];
        $title = $this->textByRole($texts, 'title');
        $sub = $this->textByRole($texts, 'sub');
        if ($title !== null) {
            $objs[] = $this->text(3, 10, $w - 6, 16, $title['text'], '#FFFFFF', 9.0, true);
        }
        if ($sub !== null) {
            $objs[] = $this->text(3, 30, $w - 6, 10, $sub['text'], '#FFCDD2', 3.2, true);
        }
        return $objs;
    }

    /** @param array<int, mixed> $texts @return array<int, array<string, mixed>> */
    private function layoutBoxed(float $w, float $h, string $bg, string $tone, array $texts, float $border): array
    {
        $objs = [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(2, 2, $w - 4, $h - 4, '#FFFFFF', $tone, $border),
        ];
        $title = $this->textByRole($texts, 'title');
        $sub = $this->textByRole($texts, 'sub');
        if ($title !== null) {
            $objs[] = $this->text(4, 12, $w - 8, 16, $title['text'], $title['color'] ?: $tone, max(6.0, $h * 0.16), true);
        }
        if ($sub !== null) {
            $objs[] = $this->text(4, 32, $w - 8, 10, $sub['text'], $sub['color'] ?: $tone, max(2.8, $h * 0.06), true);
        }
        return $objs;
    }

    /** @param array<int, mixed> $texts @return array<int, array<string, mixed>> */
    private function layoutLot(float $w, float $h, string $bg, string $tone, array $texts): array
    {
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(2, 2, $w - 4, $h - 4, '#FFFFFF', $tone, 0.6),
            $this->text(4, 8, $w - 8, 8, 'LOT NO.', '#616161', 2.6, true, 'left'),
            $this->text(4, 16, $w - 8, 10, '2025.05.10', $tone, 4.2, true, 'left'),
            $this->text(4, 28, $w - 8, 8, 'EXP. DATE', '#616161', 2.6, true, 'left'),
            $this->text(4, 36, $w - 8, 10, '2026.05.10', $tone, 4.2, true, 'left'),
        ];
    }

    /** @param array<int, mixed> $texts @return array<int, array<string, mixed>> */
    private function layoutDates(float $w, float $h, string $bg, string $tone, array $texts): array
    {
        $title = $this->textByRole($texts, 'title');
        $sub = $this->textByRole($texts, 'sub');
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(2, 2, $w - 4, $h - 4, '#FFFFFF', $tone, 0.6),
            $this->text(4, 10, $w - 8, 8, $title['text'] ?? '제조일자', '#616161', 2.6, true, 'left'),
            $this->text(4, 18, $w - 8, 10, '2025.05.10', $tone, 4.0, true, 'left'),
            $this->text(4, 30, $w - 8, 8, $sub['text'] ?? '유통기한', '#616161', 2.6, true, 'left'),
            $this->text(4, 38, $w - 8, 10, '2026.05.10', $tone, 4.0, true, 'left'),
        ];
    }

    /** @param array<int, mixed> $texts @return array<int, array<string, mixed>> */
    private function layoutQr(float $w, float $h, string $bg, string $tone, string $artUrl, array $texts): array
    {
        $objs = [$this->rect(0, 0, $w, $h, $bg)];
        $objs[] = $this->qr($w * 0.18, $h * 0.1, $w * 0.64, 'https://www.labelup.co.kr');
        $title = $this->textByRole($texts, 'title');
        $sub = $this->textByRole($texts, 'sub');
        if ($title !== null) {
            $objs[] = $this->text(2, $h * 0.76, $w - 4, 7, $title['text'], $title['color'] ?: $tone, 3.4, true);
        }
        if ($sub !== null) {
            $objs[] = $this->text(2, $h * 0.88, $w - 4, 5, $sub['text'], $sub['color'] ?: '#757575', 2.0, false);
        }
        return $objs;
    }

    /** @param array<int, mixed> $texts @return array{text:string,color:string}|null */
    private function textByRole(array $texts, string $role): ?array
    {
        foreach ($texts as $t) {
            if (!is_array($t)) {
                continue;
            }
            if (($t['role'] ?? '') === $role) {
                return [
                    'text' => (string) ($t['text'] ?? ''),
                    'color' => (string) ($t['color'] ?? ''),
                ];
            }
        }
        return null;
    }

    /** @param array<int, array<string, mixed>> $objects */
    private function envelope(string $name, array $paper, string $bg, array $objects): array
    {
        return [
            'format' => 'labelup',
            'version' => 2,
            'document' => [
                'version' => 2,
                'format' => 'labelup',
                'name' => $name,
                'background' => $bg,
                'paper' => $paper,
                'pages' => [[
                    'index' => 0,
                    'cells' => [[
                        'index' => 0,
                        'objects' => $objects,
                    ]],
                ]],
                'printOffsetXMm' => 0,
                'printOffsetYMm' => 0,
            ],
        ];
    }

    private function paper(string $no, string $name, float $w, float $h, string $shape, float $radius, string $bg): array
    {
        return [
            'version' => 1,
            'paperNo' => $no,
            'name' => $name,
            'labelWidthMm' => $w,
            'labelHeightMm' => $h,
            'columns' => 3,
            'rows' => 5,
            'shape' => [
                'kind' => $shape,
                'cornerRadiusMm' => $radius,
            ],
            'labelColor' => $bg,
        ];
    }

    /** @return array<string, mixed> */
    private function image(float $x, float $y, float $w, float $h, string $url): array
    {
        return $this->base('image', $x, $y, $w, $h, '#FFFFFF', 'transparent', 0) + [
            'imageData' => $url,
            'imageFit' => 'contain',
            'backgroundTransparent' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function rect(float $x, float $y, float $w, float $h, string $fill, string $stroke = 'transparent', float $sw = 0): array
    {
        return $this->base('rect', $x, $y, $w, $h, $fill, $stroke, $sw) + [
            'shapeKind' => 'rect',
            'cornerRadiusMm' => 0,
        ];
    }

    /** @return array<string, mixed> */
    private function ellipse(float $x, float $y, float $w, float $h, string $fill, string $stroke = 'transparent', float $sw = 0): array
    {
        return $this->base('ellipse', $x, $y, $w, $h, $fill, $stroke, $sw) + [
            'shapeKind' => 'ellipse',
        ];
    }

    /** @return array<string, mixed> */
    private function text(
        float $x,
        float $y,
        float $w,
        float $h,
        string $text,
        string $fill,
        float $size,
        bool $bold = true,
        string $align = 'center'
    ): array {
        return $this->base('text', $x, $y, $w, $h, $fill, 'transparent', 0) + [
            'text' => $text,
            'fontSize' => $size,
            'fontFamily' => 'Pretendard',
            'bold' => $bold,
            'textAlign' => $align,
            'verticalAlign' => 'middle',
            'lineHeight' => 1.15,
            'backgroundTransparent' => true,
            'textMode' => 'normal',
            'wordArtStyle' => 'none',
        ];
    }

    /** @return array<string, mixed> */
    private function barcode(float $x, float $y, float $w, float $h, string $value): array
    {
        return $this->base('barcode', $x, $y, $w, $h, '#2E2A27', 'transparent', 0) + [
            'barcodeFormat' => 'CODE_128',
            'barcodeValue' => $value,
            'barcodeShowText' => false,
            'fontSize' => 2.2,
            'backgroundTransparent' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function qr(float $x, float $y, float $size, string $url): array
    {
        return $this->base('qr', $x, $y, $size, $size, '#2E2A27', 'transparent', 0) + [
            'barcodeFormat' => 'QR_CODE',
            'barcodeValue' => $url,
            'barcodeShowText' => false,
            'qrEcc' => 'M',
            'qrKind' => 'url',
            'backgroundTransparent' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function base(string $type, float $x, float $y, float $w, float $h, string $fill, string $stroke, float $sw): array
    {
        return [
            'id' => sprintf('p60%03d', ++$this->seq),
            'type' => $type,
            'zIndex' => ++$this->z,
            'locked' => false,
            'visible' => true,
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round($w, 2),
            'height' => round($h, 2),
            'rotation' => 0,
            'fill' => $fill,
            'stroke' => $stroke,
            'strokeWidth' => $sw,
            'opacity' => 1,
        ];
    }
}
