<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 물류·창고·배송 아이콘 60종 — 첨부 시트의 아이콘+한/영 라벨.
 */
final class LabelTemplateLogiPackSeedService
{
    private int $seq = 0;
    private int $z = 0;

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $out = [];
        $order = 600;
        foreach ($this->catalog() as $row) {
            $this->seq = 0;
            $this->z = 0;
            $objects = $this->compose($row);
            $paper = $this->paper(
                (string) $row['no'],
                (string) $row['name'],
                (float) $row['w'],
                (float) $row['h'],
                (string) $row['shape'],
                (float) $row['radius'],
                (string) $row['bg']
            );
            $envelope = $this->envelope((string) $row['name'], $paper, (string) $row['bg'], $objects);
            $out[] = [
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'category' => (string) $row['cat'],
                'tags' => (string) $row['tags'],
                'description' => (string) $row['desc'],
                'tone' => (string) $row['tone'],
                'paper_no' => $paper['paperNo'],
                'paper_w_mm' => $paper['labelWidthMm'],
                'paper_h_mm' => $paper['labelHeightMm'],
                'paper_shape' => $paper['shape']['kind'],
                'document_json' => json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'sort_order' => $order++,
            ];
        }
        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    private function catalog(): array
    {
        return [
            $this->def('logi-this-side-up', '상단주의 THIS SIDE UP', 'warning', '방향,위로', '#212121', 'arrow-up',
                'icon', '상단주의', 'THIS SIDE UP', ''),
            $this->def('logi-fragile', '파손주의 FRAGILE', 'warning', '파손', '#E53935', 'wine-glass',
                'icon', '파손주의', 'FRAGILE', ''),
            $this->def('logi-keep-dry', '습기주의 KEEP DRY', 'warning', '습기', '#1E88E5', 'umbrella',
                'icon', '습기주의', 'KEEP DRY', ''),
            $this->def('logi-handle-care', '취급주의 HANDLE WITH CARE', 'warning', '취급', '#E53935', 'hands-holding',
                'icon', '취급주의', 'HANDLE WITH CARE', ''),
            $this->def('logi-no-stack', '적재금지 DO NOT STACK', 'warning', '적재금지', '#E53935', 'cubes',
                'icon', '적재금지', 'DO NOT STACK', ''),
            $this->def('logi-caution', '주의 CAUTION', 'warning', '주의', '#F9A825', 'triangle-exclamation',
                'caution', '주의', 'CAUTION', ''),
            $this->def('logi-heavy', '무게주의 HEAVY', 'warning', '무게,중량', '#212121', 'weight-hanging',
                'icon', '무게주의', 'HEAVY', ''),
            $this->def('logi-forklift', '지게차주의 FORKLIFT AREA', 'warning', '지게차', '#212121', 'truck',
                'icon', '지게차주의', 'FORKLIFT AREA', ''),
            $this->def('logi-no-hook', '후크사용금지 NO HOOK', 'warning', '후크', '#E53935', 'anchor',
                'icon', '후크사용금지', 'NO HOOK', ''),
            $this->def('logi-no-sun', '직사광선주의', 'warning', '직사광선', '#E53935', 'sun',
                'icon', '직사광선주의', 'KEEP AWAY FROM SUNLIGHT', ''),

            $this->def('logi-keep-heat', '고온주의 KEEP AWAY FROM HEAT', 'warning', '고온', '#E53935', 'temperature-high',
                'icon', '고온주의', 'KEEP AWAY FROM HEAT', ''),
            $this->def('logi-keep-frozen', '저온주의 KEEP FROZEN', 'warning', '저온,냉동', '#1E88E5', 'snowflake',
                'icon', '저온주의', 'KEEP FROZEN', ''),
            $this->def('logi-temp-2-8', '온도관리 2~8°C', 'warning', '온도관리', '#1E88E5', 'temperature-half',
                'icon', '온도관리', 'TEMPERATURE CONTROL', '2~8°C'),
            $this->def('logi-chilled-2-8', '냉장보관 2~8°C', 'warning', '냉장', '#1E88E5', 'snowflake',
                'icon', '냉장보관', 'REFRIGERATED', '2~8°C'),
            $this->def('logi-frozen-18', '냉동보관 -18°C', 'warning', '냉동', '#E53935', 'snowflake',
                'icon', '냉동보관', 'FROZEN', '-18°C 이하'),
            $this->def('logi-express', '긴급배송 EXPRESS', 'shipping', '긴급,특송', '#E53935', 'truck-fast',
                'banner', '긴급배송', 'EXPRESS', ''),
            $this->def('logi-same-day', '당일배송 SAME DAY', 'shipping', '당일', '#212121', 'truck',
                'icon', '당일배송', 'SAME DAY', ''),
            $this->def('logi-freight', '화물배송 FREIGHT', 'shipping', '화물', '#212121', 'truck',
                'icon', '화물배송', 'FREIGHT', ''),
            $this->def('logi-air-cargo', '항공화물 AIR CARGO', 'shipping', '항공', '#212121', 'plane',
                'icon', '항공화물', 'AIR CARGO', ''),
            $this->def('logi-sea-cargo', '해상화물 SEA CARGO', 'shipping', '해상', '#212121', 'ship',
                'icon', '해상화물', 'SEA CARGO', ''),

            $this->def('logi-inbound', '입고 INBOUND', 'warehouse', '입고', '#212121', 'warehouse',
                'icon', '입고', 'INBOUND', ''),
            $this->def('logi-outbound', '출고 OUTBOUND', 'warehouse', '출고', '#212121', 'arrow-up-from-bracket',
                'icon', '출고', 'OUTBOUND', ''),
            $this->def('logi-picking', '피킹완료 PICKING COMPLETE', 'warehouse', '피킹', '#212121', 'list-check',
                'icon', '피킹완료', 'PICKING COMPLETE', ''),
            $this->def('logi-packing', '포장완료 PACKING COMPLETE', 'warehouse', '포장', '#212121', 'box',
                'icon', '포장완료', 'PACKING COMPLETE', ''),
            $this->def('logi-qc-pass', '검수완료 QC PASSED', 'warehouse', '검수,합격', '#43A047', 'magnifying-glass',
                'icon', '검수완료', 'QC PASSED', ''),
            $this->def('logi-qc-fail', '검수불량 QC FAILED', 'warehouse', '검수,불량', '#E53935', 'circle-xmark',
                'icon', '검수불량', 'QC FAILED', ''),
            $this->def('logi-return', '반품 RETURN', 'shipping', '반품', '#212121', 'rotate-left',
                'icon', '반품', 'RETURN', ''),
            $this->def('logi-exchange', '교환 EXCHANGE', 'shipping', '교환', '#212121', 'right-left',
                'icon', '교환', 'EXCHANGE', ''),
            $this->def('logi-restock', '재보충 RESTOCK', 'warehouse', '재고,보충', '#212121', 'boxes-packing',
                'icon', '재보충', 'RESTOCK', ''),
            $this->def('logi-ready-ship', '출고준비 READY TO SHIP', 'warehouse', '출고준비', '#212121', 'clipboard-check',
                'icon', '출고준비', 'READY TO SHIP', ''),

            $this->def('logi-product-code', '제품코드', 'warehouse', '제품코드,바코드', '#212121', 'barcode',
                'barcode', '제품코드', '8801234567890', ''),
            $this->def('logi-order-no', '주문번호', 'warehouse', '주문,바코드', '#212121', 'barcode',
                'barcode', '주문번호', 'OD2025090901', ''),
            $this->def('logi-track-no', '송장번호', 'shipping', '송장,바코드', '#212121', 'barcode',
                'barcode', '송장번호', 'TRK1234567890', ''),
            $this->def('logi-lot', 'LOT NO.', 'warehouse', '로트,바코드', '#212121', 'barcode',
                'barcode', 'LOT NO.', 'LOT20250909', ''),
            $this->def('logi-mfg', '제조일자', 'warehouse', '제조일', '#212121', 'calendar',
                'date', '제조일자', '2025. 09. 09', 'MFG. DATE'),
            $this->def('logi-exp', '유통기한', 'warehouse', '유통기한', '#212121', 'calendar',
                'date', '유통기한', '2026. 09. 09', 'EXP. DATE'),
            $this->def('logi-qty', '수량 QTY', 'warehouse', '수량', '#212121', 'hashtag',
                'qty', '수량', '10/10', 'QTY'),
            $this->def('logi-box-no', '박스번호 BOX NO', 'warehouse', '박스번호', '#212121', 'box',
                'qty', '박스번호', '1/10', 'BOX NO'),
            $this->def('logi-qr', 'QR 코드', 'warehouse', 'QR', '#212121', 'qrcode',
                'qr', 'QR 코드', 'https://labelup.kr/scan', '스캔해 주세요'),
            $this->def('logi-dest-sel', '도착지 서울센터', 'shipping', '도착지,허브', '#212121', 'location-dot',
                'hub', '도착지', '서울센터', 'SEL'),

            $this->def('logi-origin-pus', '출발지 부산센터', 'shipping', '출발지,허브', '#212121', 'warehouse',
                'hub', '출발지', '부산센터', 'PUS'),
            $this->def('logi-via-djj', '경유지 대전센터', 'shipping', '경유,허브', '#212121', 'route',
                'hub', '경유지', '대전센터', 'DJJ'),
            $this->def('logi-region-cju', '배송지역 제주도', 'shipping', '제주,지역', '#212121', 'leaf',
                'hub', '배송지역', '제주도', 'CJU'),
            $this->def('logi-check-addr', '배송지확인 CHECK ADDRESS', 'shipping', '주소확인', '#E53935', 'location-dot',
                'icon', '배송지확인', 'CHECK ADDRESS', ''),
            $this->def('logi-scheduled', '예약배송 SCHEDULED', 'shipping', '예약', '#E53935', 'bell',
                'icon', '예약배송', 'SCHEDULED', ''),
            $this->def('logi-deliv-date', '배송일정 DELIVERY DATE', 'shipping', '일정', '#E53935', 'calendar-day',
                'icon', '배송일정', 'DELIVERY DATE', ''),
            $this->def('logi-pickup', '고객직접수령 PICK UP', 'shipping', '수령,픽업', '#212121', 'user',
                'icon', '고객직접수령', 'PICK UP', ''),
            $this->def('logi-locker', '무인보관함 LOCKER DELIVERY', 'shipping', '무인함', '#212121', 'box-archive',
                'icon', '무인보관함', 'LOCKER DELIVERY', ''),
            $this->def('logi-auth-only', '관계자외출입금지', 'warning', '출입,보안', '#E53935', 'ban',
                'icon', '관계자외출입금지', 'AUTHORIZED PERSONNEL ONLY', ''),
            $this->def('logi-cctv', 'CCTV촬영중', 'warning', 'CCTV', '#212121', 'video',
                'icon', 'CCTV촬영중', 'CCTV IN OPERATION', ''),

            $this->def('logi-pallet', '파렛트전용 PALLET ONLY', 'warehouse', '파렛트', '#212121', 'pallet',
                'icon', '파렛트전용', 'PALLET ONLY', ''),
            $this->def('logi-carton', '카톤단위 CARTON UNIT', 'warehouse', '카톤', '#212121', 'box',
                'icon', '카톤단위', 'CARTON UNIT', ''),
            $this->def('logi-strap', '결속필수 STRAP REQUIRED', 'warehouse', '결속', '#212121', 'box',
                'icon', '결속필수', 'STRAP REQUIRED', ''),
            $this->def('logi-no-tilt', '기울임금지 DO NOT TILT', 'warning', '기울임', '#E53935', 'ban',
                'icon', '기울임금지', 'DO NOT TILT', ''),
            $this->def('logi-stack-limit', '적재한도 STACKING LIMIT', 'warning', '적재한도', '#212121', 'layer-group',
                'icon', '적재한도', 'STACKING LIMIT', '3'),
            $this->def('logi-cog', '무게중심 CENTER OF GRAVITY', 'warning', '무게중심', '#212121', 'crosshairs',
                'icon', '무게중심', 'CENTER OF GRAVITY', ''),
            $this->def('logi-this-way', '화살표방향 THIS WAY', 'warning', '방향', '#212121', 'arrow-up',
                'icon', '화살표방향', 'THIS WAY', ''),
            $this->def('logi-reusable', '재사용포장 REUSABLE', 'gift', '재사용', '#43A047', 'recycle',
                'icon', '재사용포장', 'REUSABLE', ''),
            $this->def('logi-eco-pack', '친환경포장 ECO PACKAGING', 'gift', '친환경', '#43A047', 'leaf',
                'icon', '친환경포장', 'ECO PACKAGING', ''),
            $this->def('logi-intl', '해외배송 INTERNATIONAL', 'shipping', '해외', '#212121', 'globe',
                'icon', '해외배송', 'INTERNATIONAL SHIPPING', ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function def(
        string $slug,
        string $name,
        string $cat,
        string $tags,
        string $tone,
        string $icon,
        string $layout,
        string $title,
        string $en,
        string $sub
    ): array {
        $square = in_array($layout, ['icon', 'caution', 'banner', 'qty', 'hub'], true);
        return [
            'slug' => $slug,
            'name' => $name,
            'cat' => $cat,
            'tags' => $tags,
            'tone' => $tone,
            'icon' => $icon,
            'layout' => $layout,
            'title' => $title,
            'en' => $en,
            'sub' => $sub,
            'desc' => $name . ' 물류 라벨. 편집기에서 바로 수정할 수 있습니다.',
            'no' => $square ? 'LU-L50' : 'LU-L70',
            'w' => $square ? 50.0 : 70.0,
            'h' => $square ? 50.0 : 36.0,
            'shape' => 'roundrect',
            'radius' => 2.0,
            'bg' => '#FFFFFF',
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<int, array<string, mixed>>
     */
    private function compose(array $row): array
    {
        $w = (float) $row['w'];
        $h = (float) $row['h'];
        $bg = (string) $row['bg'];
        $tone = (string) $row['tone'];
        $title = (string) $row['title'];
        $en = (string) $row['en'];
        $sub = (string) $row['sub'];
        $icon = (string) $row['icon'];

        return match ((string) $row['layout']) {
            'barcode' => $this->layoutBarcode($w, $h, $bg, $tone, $title, $en, $sub),
            'qr' => $this->layoutQr($w, $h, $bg, $tone, $title, $en, $sub),
            'date' => $this->layoutDate($w, $h, $bg, $tone, $title, $en, $sub),
            'caution' => $this->layoutCaution($w, $h, $bg, $tone, $title, $en, $icon),
            'banner' => $this->layoutBanner($w, $h, $bg, $tone, $title, $en, $icon),
            'qty' => $this->layoutQty($w, $h, $bg, $tone, $title, $en, $sub),
            'hub' => $this->layoutHub($w, $h, $bg, $tone, $title, $en, $sub, $icon),
            default => $this->layoutIcon($w, $h, $bg, $tone, $title, $en, $sub, $icon),
        };
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutIcon(float $w, float $h, string $bg, string $tone, string $title, string $en, string $sub, string $icon): array
    {
        $iw = 16.0;
        $ix = ($w - $iw) / 2;
        $objs = [
            $this->rect(0, 0, $w, $h, $bg),
            $this->icon($ix, 4.2, $iw, $iw, $icon, $tone),
            $this->text(2.2, 22.0, $w - 4.4, 8.4, $title, $tone, 3.6, true),
            $this->text(2.2, 31.0, $w - 4.4, 7.0, $en, $tone, 2.4, true),
        ];
        if ($sub !== '') {
            $objs[] = $this->text(2.2, 39.5, $w - 4.4, 6.4, $sub, '#757575', 2.2, false);
        }
        return $objs;
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutBanner(float $w, float $h, string $bg, string $tone, string $title, string $en, string $icon): array
    {
        $bar = 9.0;
        $iw = 13.0;
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(0, 0, $w, $bar, $tone),
            $this->text(2, 1.2, $w - 4, $bar - 2.2, $title, '#FFFFFF', 3.2, true),
            $this->icon(($w - $iw) / 2, $bar + 3.2, $iw, $iw, $icon, $tone),
            $this->text(2, $h - 12.4, $w - 4, 8.8, $en, $tone, 2.6, true),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutCaution(float $w, float $h, string $bg, string $tone, string $title, string $en, string $icon): array
    {
        return [
            $this->rect(0, 0, $w, $h, '#FFF8E1'),
            $this->icon(($w - 16) / 2, 4.0, 16, 16, $icon, $tone),
            $this->text(2, 22.0, $w - 4, 9, $title, '#212121', 4.4, true),
            $this->text(2, 33.0, $w - 4, 8, $en, '#616161', 2.6, true),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutBarcode(float $w, float $h, string $bg, string $tone, string $title, string $code, string $sub): array
    {
        $objs = [
            $this->rect(0, 0, $w, $h, $bg),
            $this->text(2.4, 1.4, $w - 4.8, 6.0, $title, $tone, 3.2, true, 'left'),
            $this->barcode(4, 8.0, $w - 8, max(12.0, $h - 18.0), $code),
            $this->text(2.4, $h - 7.0, $w - 4.8, 5.4, $code, '#424242', 2.2, false),
        ];
        if ($sub !== '') {
            $objs[] = $this->text($w * 0.55, 1.4, $w * 0.42, 6.0, $sub, '#757575', 2.2, false, 'right');
        }
        return $objs;
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutQr(float $w, float $h, string $bg, string $tone, string $title, string $url, string $sub): array
    {
        $q = min($w, $h) * 0.42;
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->text(2, 1.4, $w - 4, 5.2, $title, $tone, 2.8, true),
            $this->qr(($w - $q) / 2, 7.2, $q, $url),
            $this->text(2, $h - 7.0, $w - 4, 5.4, $sub !== '' ? $sub : '스캔해 주세요', '#616161', 2.2, false),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutQty(float $w, float $h, string $bg, string $tone, string $title, string $value, string $en): array
    {
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->text(2.2, 3.2, $w - 4.4, 7.2, $title, $tone, 3.2, true),
            $this->text(2.2, 14.0, $w - 4.4, 18, $value, $tone, 8.4, true),
            $this->text(2.2, $h - 12.4, $w - 4.4, 8.0, $en, '#757575', 2.6, true),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutHub(float $w, float $h, string $bg, string $tone, string $title, string $place, string $code, string $icon): array
    {
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->icon(($w - 12) / 2, 3.2, 12, 12, $icon, $tone),
            $this->text(2.2, 16.4, $w - 4.4, 6.4, $title, '#757575', 2.4, false),
            $this->text(2.2, 23.2, $w - 4.4, 9.2, $place, $tone, 3.8, true),
            $this->text(2.2, 34.0, $w - 4.4, 8.4, $code, $tone, 4.4, true),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutDate(float $w, float $h, string $bg, string $tone, string $title, string $value, string $en): array
    {
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->text(2.4, 1.6, $w - 4.8, 6.0, $title, $tone, 2.8, true, 'left'),
            $this->text(2.4, 9.2, $w - 4.8, 12, $value, $tone, 4.8, true, 'left'),
            $this->rect(2.4, $h - 8.2, $w - 4.8, 0.3, $tone),
            $this->text(2.4, $h - 7.2, $w - 4.8, 5.6, $en, '#757575', 2.0, false, 'left'),
        ];
    }

    /** @return array<string, mixed> */
    private function icon(float $x, float $y, float $w, float $h, string $name, string $fill): array
    {
        return $this->base('icon', $x, $y, $w, $h, $fill, 'transparent', 0) + [
            'iconName' => $name,
            'backgroundTransparent' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function paper(string $no, string $name, float $w, float $h, string $shape, float $radius, string $bg): array
    {
        return [
            'version' => 1,
            'paperNo' => $no,
            'name' => $name,
            'category' => 'A4',
            'brand' => 'LabelUp',
            'paperWidthMm' => 210,
            'paperHeightMm' => 297,
            'labelWidthMm' => $w,
            'labelHeightMm' => $h,
            'columns' => $w >= 70 ? 2 : 3,
            'rows' => $h >= 40 ? 5 : 7,
            'leftMarginMm' => 10.0,
            'topMarginMm' => 10.0,
            'rightMarginMm' => 10.0,
            'bottomMarginMm' => 10.0,
            'hGapMm' => 2.0,
            'vGapMm' => 2.0,
            'labelColor' => $bg,
            'shape' => [
                'kind' => $shape,
                'cornerRadiusMm' => $radius,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $paper
     * @param array<int, array<string, mixed>> $objects
     * @return array<string, mixed>
     */
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

    /** @return array<string, mixed> */
    private function rect(float $x, float $y, float $w, float $h, string $fill, string $stroke = 'transparent', float $sw = 0): array
    {
        return $this->base('rect', $x, $y, $w, $h, $fill, $stroke, $sw) + [
            'shapeKind' => 'rect',
            'cornerRadiusMm' => 0,
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
            'id' => sprintf('lgi%03d', ++$this->seq),
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
