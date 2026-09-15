<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 아이콘형 물류·상태·커머스 60종 — 첨부 시트의 아이콘+한/영 라벨.
 */
final class LabelTemplateMarkPackSeedService
{
    private int $seq = 0;
    private int $z = 0;

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $out = [];
        $order = 500;
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
            $this->def('mark-fragile', '취급주의 FRAGILE', 'warning', '파손,취급주의', '#E53935', 'wine-glass',
                'icon', '취급주의', 'FRAGILE', ''),
            $this->def('mark-this-side-up', '위로 THIS SIDE UP', 'warning', '방향,위로', '#212121', 'arrow-up',
                'icon', '위로', 'THIS SIDE UP', ''),
            $this->def('mark-keep-dry', '습기주의 KEEP DRY', 'warning', '습기', '#1E88E5', 'umbrella',
                'icon', '습기주의', 'KEEP DRY', ''),
            $this->def('mark-handle-care', '취급주의 HANDLE WITH CARE', 'warning', '취급', '#E53935', 'hands-holding',
                'icon', '취급주의', 'HANDLE WITH CARE', ''),
            $this->def('mark-do-not-drop', '파손주의 DO NOT DROP', 'warning', '낙하,파손', '#E53935', 'burst',
                'icon', '파손주의', 'DO NOT DROP', ''),
            $this->def('mark-caution', '주의 CAUTION', 'warning', '주의', '#F9A825', 'triangle-exclamation',
                'caution', '주의', 'CAUTION', ''),
            $this->def('mark-stacking-limit', '적재한도 STACKING LIMIT', 'warning', '적재', '#212121', 'cubes',
                'icon', '적재한도', 'STACKING LIMIT', ''),
            $this->def('mark-no-hook', '후크사용금지 NO HOOK', 'warning', '후크,금지', '#E53935', 'anchor',
                'icon', '후크사용금지', 'NO HOOK', ''),
            $this->def('mark-no-sun', '직사광선주의', 'warning', '햇빛,직사광선', '#E53935', 'sun',
                'icon', '직사광선주의', 'KEEP AWAY FROM SUNLIGHT', ''),
            $this->def('mark-temp-control', '온도유지', 'warning', '온도', '#1E88E5', 'temperature-half',
                'icon', '온도유지', 'TEMPERATURE CONTROL', ''),

            $this->def('mark-chilled', '냉장보관 REFRIGERATED', 'warning', '냉장', '#1E88E5', 'snowflake',
                'icon', '냉장보관', 'REFRIGERATED', '0~10°C'),
            $this->def('mark-frozen', '냉동보관 FROZEN', 'warning', '냉동', '#E53935', 'snowflake',
                'icon', '냉동보관', 'FROZEN', '-18°C 이하'),
            $this->def('mark-no-fire', '화기주의', 'warning', '화기,화재', '#E53935', 'fire',
                'icon', '화기주의', 'KEEP AWAY FROM FIRE', ''),
            $this->def('mark-no-magnet', '자석주의', 'warning', '자석', '#E53935', 'magnet',
                'icon', '자석주의', 'KEEP AWAY FROM MAGNET', ''),
            $this->def('mark-recycle', '재활용 RECYCLE', 'warning', '재활용', '#43A047', 'recycle',
                'icon', '재활용', 'RECYCLE', ''),
            $this->def('mark-eco-pack', '친환경 포장', 'gift', '친환경,포장', '#43A047', 'leaf',
                'icon', '친환경 포장', 'ECO PACKAGING', ''),
            $this->def('mark-food-safe', '식품용 FOOD SAFE', 'food', '식품,안전', '#212121', 'utensils',
                'icon', '식품용', 'FOOD SAFE', ''),
            $this->def('mark-for-pets', '반려동물용', 'gift', '반려동물', '#212121', 'paw',
                'icon', '반려동물용', 'FOR PETS', ''),
            $this->def('mark-baby', '유아용품', 'gift', '유아,아기', '#212121', 'baby',
                'icon', '유아용품', 'BABY PRODUCT', ''),
            $this->def('mark-medical', '의료기기', 'warning', '의료,주사', '#E53935', 'syringe',
                'icon', '의료기기', 'MEDICAL DEVICE', ''),

            $this->def('mark-barcode', '바코드', 'warehouse', '바코드', '#212121', 'barcode',
                'barcode', '바코드', '8801234567890', ''),
            $this->def('mark-qr', 'QR 코드', 'warehouse', 'QR', '#212121', 'qrcode',
                'qr', 'QR 코드', 'https://labelup.kr/scan', '스캔해 주세요'),
            $this->def('mark-lot', 'LOT NO.', 'warehouse', '로트,바코드', '#212121', 'barcode',
                'barcode', 'LOT NO.', 'LOT20250909', ''),
            $this->def('mark-exp', '유통기한', 'warehouse', '유통기한', '#212121', 'calendar',
                'date', '유통기한', '2026. 12. 31', 'EXP. DATE'),
            $this->def('mark-mfg', '제조일자', 'warehouse', '제조일', '#212121', 'calendar',
                'date', '제조일자', '2025. 09. 09', 'MFG. DATE'),
            $this->def('mark-product-code', '제품코드', 'warehouse', '제품코드,바코드', '#212121', 'barcode',
                'barcode', '제품코드', 'P-20250909', ''),
            $this->def('mark-serial', '시리얼 번호', 'warehouse', '시리얼,바코드', '#212121', 'barcode',
                'barcode', '시리얼 번호', 'SN2025090901', 'S/N'),
            $this->def('mark-qc-pass', '검수완료 QC PASSED', 'warehouse', '검수,합격', '#43A047', 'circle-check',
                'banner', '검수완료', 'QC PASSED', ''),
            $this->def('mark-qc-fail', '검수불량 QC FAILED', 'warehouse', '검수,불량', '#E53935', 'circle-xmark',
                'banner', '검수불량', 'QC FAILED', ''),
            $this->def('mark-recheck', '재검수 RECHECK', 'warehouse', '재검수', '#F9A825', 'rotate',
                'banner', '재검수', 'RECHECK', ''),

            $this->def('mark-completed', '완료 COMPLETED', 'office', '완료,상태', '#43A047', 'check',
                'banner', '완료', 'COMPLETED', ''),
            $this->def('mark-in-progress', '진행중 IN PROGRESS', 'office', '진행,상태', '#1E88E5', 'circle-notch',
                'banner', '진행중', 'IN PROGRESS', ''),
            $this->def('mark-pending', '대기중 PENDING', 'office', '대기,상태', '#757575', 'clock',
                'banner', '대기중', 'PENDING', ''),
            $this->def('mark-urgent', '긴급 URGENT', 'office', '긴급', '#E53935', 'bullhorn',
                'banner', '긴급', 'URGENT', ''),
            $this->def('mark-confidential', '기밀문서 CONFIDENTIAL', 'office', '기밀,보안', '#E53935', 'lock',
                'icon', '기밀문서', 'CONFIDENTIAL', ''),
            $this->def('mark-internal', '사내 전용', 'office', '사내,내부', '#212121', 'users',
                'icon', '사내 전용', 'INTERNAL USE ONLY', ''),
            $this->def('mark-for-customer', '고객용 FOR CUSTOMER', 'gift', '고객', '#212121', 'user',
                'icon', '고객용', 'FOR CUSTOMER', ''),
            $this->def('mark-office-use', '업무용 OFFICE USE', 'office', '업무', '#212121', 'briefcase',
                'icon', '업무용', 'OFFICE USE', ''),
            $this->def('mark-sample', '샘플 SAMPLE', 'event', '샘플', '#212121', 'cube',
                'icon', '샘플', 'SAMPLE', ''),
            $this->def('mark-not-for-sale', '판매금지 NOT FOR SALE', 'event', '판매금지', '#E53935', 'ban',
                'icon', '판매금지', 'NOT FOR SALE', ''),

            $this->def('mark-contract', '계약서 CONTRACT', 'office', '계약', '#212121', 'file-lines',
                'icon', '계약서', 'CONTRACT', ''),
            $this->def('mark-tax-invoice', '세금계산서', 'office', '세금,계산서', '#212121', 'file-invoice',
                'icon', '세금계산서', 'TAX INVOICE', ''),
            $this->def('mark-quotation', '견적서 QUOTATION', 'office', '견적', '#212121', 'calculator',
                'icon', '견적서', 'QUOTATION', ''),
            $this->def('mark-packing-list', '포장명세서', 'shipping', '포장,명세', '#212121', 'clipboard-list',
                'icon', '포장명세서', 'PACKING LIST', ''),
            $this->def('mark-delivery-note', '납품서 DELIVERY NOTE', 'shipping', '납품', '#212121', 'truck',
                'icon', '납품서', 'DELIVERY NOTE', ''),
            $this->def('mark-return', '반품 RETURN', 'shipping', '반품', '#212121', 'rotate-left',
                'icon', '반품', 'RETURN', ''),
            $this->def('mark-exchange', '교환 EXCHANGE', 'shipping', '교환', '#212121', 'right-left',
                'icon', '교환', 'EXCHANGE', ''),
            $this->def('mark-receipt', '영수증 RECEIPT', 'office', '영수증', '#212121', 'receipt',
                'icon', '영수증', 'RECEIPT', ''),
            $this->def('mark-pay-done', '결제완료 PAYMENT DONE', 'price', '결제,완료', '#43A047', 'credit-card',
                'banner', '결제완료', 'PAYMENT DONE', ''),
            $this->def('mark-pay-pending', '결제대기 PAYMENT PENDING', 'price', '결제,대기', '#FB8C00', 'credit-card',
                'banner', '결제대기', 'PAYMENT PENDING', ''),

            $this->def('mark-shipped', '출고완료 SHIPPED', 'shipping', '출고', '#1565C0', 'truck',
                'banner', '출고완료', 'SHIPPED', ''),
            $this->def('mark-on-delivery', '배송중 ON DELIVERY', 'shipping', '배송중', '#1E88E5', 'truck-fast',
                'banner', '배송중', 'ON DELIVERY', ''),
            $this->def('mark-delivered', '배송완료 DELIVERED', 'shipping', '배송완료', '#43A047', 'box',
                'banner', '배송완료', 'DELIVERED', ''),
            $this->def('mark-delayed', '지연 DELAYED', 'shipping', '지연', '#E53935', 'clock',
                'banner', '지연', 'DELAYED', ''),
            $this->def('mark-canceled', '주문취소 CANCELED', 'shipping', '취소', '#E53935', 'xmark',
                'icon', '주문취소', 'CANCELED', ''),
            $this->def('mark-return-req', '반품접수', 'shipping', '반품,접수', '#212121', 'box-open',
                'icon', '반품접수', 'RETURN REQUEST', ''),
            $this->def('mark-gift-pack', '선물포장 GIFT PACKAGE', 'gift', '선물', '#E53935', 'gift',
                'banner', '선물포장', 'GIFT PACKAGE', ''),
            $this->def('mark-special-price', '특가상품 SPECIAL PRICE', 'price', '특가', '#E53935', 'tags',
                'banner', '특가상품', 'SPECIAL PRICE', ''),
            $this->def('mark-new-arrival', '신상품 NEW ARRIVAL', 'event', '신상품', '#FB8C00', 'certificate',
                'banner', '신상품', 'NEW ARRIVAL', ''),
            $this->def('mark-best-seller', '베스트상품 BEST SELLER', 'event', '베스트', '#F9A825', 'crown',
                'banner', '베스트상품', 'BEST SELLER', ''),
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
        $square = in_array($layout, ['icon', 'caution', 'banner'], true);
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
            'desc' => $name . ' 아이콘 라벨. 편집기에서 바로 수정할 수 있습니다.',
            'no' => $square ? 'LU-M50' : 'LU-M70',
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
            'id' => sprintf('mrk%03d', ++$this->seq),
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
