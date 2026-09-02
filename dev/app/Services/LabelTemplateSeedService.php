<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 편집기 LabelDocument(camelCase JSON)와 호환되는 라벨 테마 시드.
 */
final class LabelTemplateSeedService
{
    private int $seq = 0;
    private int $z = 0;

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $out = [];
        $order = 0;
        foreach ($this->catalog() as $row) {
            $order++;
            $this->seq = 0;
            $this->z = 0;
            $paper = $this->paper(
                (string) $row['no'],
                (string) $row['name'],
                (float) $row['w'],
                (float) $row['h'],
                (int) $row['cols'],
                (int) $row['rows'],
                (string) $row['shape'],
                (float) ($row['radius'] ?? 1.5)
            );
            $bg = (string) ($row['bg'] ?? '#FFFFFF');
            $paper['labelColor'] = $bg;
            $envelope = $this->envelope((string) $row['name'], $paper, $bg, $row['objects']);
            $out[] = [
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'category' => (string) $row['cat'],
                'tags' => (string) $row['tags'],
                'description' => (string) ($row['desc'] ?? ''),
                'tone' => (string) $row['tone'],
                'paper_no' => $paper['paperNo'],
                'paper_w_mm' => $paper['labelWidthMm'],
                'paper_h_mm' => $paper['labelHeightMm'],
                'paper_shape' => $paper['shape']['kind'],
                'document_json' => json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'sort_order' => $order,
            ];
        }
        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    private function catalog(): array
    {
        return array_merge(
            $this->food(),
            $this->shipping(),
            $this->beauty(),
            $this->price(),
            $this->round(),
            $this->gift(),
            $this->cafe(),
            $this->warning(),
            $this->warehouse(),
            $this->event()
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function food(): array
    {
        return [
            $this->item('food-honey', '유기농 꿀', 'food', '꿀,식품,원형', '#C9A227', 'LU-R40', 40, 40, 4, 6, 'ellipse', 0, '#FFF8E7',
                $this->circleBadge(40, '#C9A227', '#FFF8E7', 'HONEY', '유기농 꿀', '250g')),
            $this->item('food-jam', '수제 딸기잼', 'food', '잼,식품', '#C23B4A', 'LU-6040', 60, 40, 3, 6, 'roundrect', 2.4, '#FFF5F5',
                $this->banner(60, 40, '#C23B4A', '#FFF5F5', 'HOME JAM', '수제 딸기잼', '무첨가 · 280g')),
            $this->item('food-olive', '엑스트라 올리브오일', 'food', '오일,식품', '#5B7A3A', 'LU-7050', 70, 50, 2, 5, 'roundrect', 2, '#F4F7EE',
                $this->banner(70, 50, '#5B7A3A', '#F4F7EE', 'OLIVE OIL', '엑스트라버진', '500ml · Italy')),
            $this->item('food-kimchi', '포기김치', 'food', '김치,반찬', '#C45C26', 'LU-8050', 80, 50, 2, 5, 'roundrect', 2, '#FFF6EE',
                $this->tableLabel(80, 50, '#C45C26', '포기김치', [['용량', '1kg'], ['제조', '2026-09-02'], ['보관', '냉장']])),
            $this->item('food-egg', '농장 달걀', 'food', '달걀,농장', '#D4A017', 'LU-5030', 50, 30, 3, 8, 'roundrect', 1.6, '#FFFBEA',
                $this->banner(50, 30, '#D4A017', '#FFFBEA', 'FARM EGG', '신선한 달걀 10구', '')),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function shipping(): array
    {
        return [
            $this->item('ship-waybill', '택배 운송장', 'shipping', '배송,바코드', '#1F3A5F', 'LU-10070', 100, 70, 2, 4, 'rect', 0, '#FFFFFF',
                $this->barcodeCard(100, 70, '#1F3A5F', '택배 운송장', '받는분 · 서울 강남구', '8801234567890')),
            $this->item('ship-fragile', '취급주의 파손', 'shipping', '파손,주의', '#C0392B', 'LU-8050F', 80, 50, 2, 5, 'roundrect', 2, '#FFF4F2',
                $this->banner(80, 50, '#C0392B', '#FFF4F2', 'FRAGILE', '취급주의 · 파손주의', '이 면이 위로')),
            $this->item('ship-return', '반품 주소', 'shipping', '반품,주소', '#2C3E50', 'LU-7036', 70, 36, 2, 7, 'rect', 0, '#F7F8FA',
                $this->banner(70, 36, '#2C3E50', '#F7F8FA', 'RETURN', '반품 주소 라벨', '고객센터 1588-0000')),
            $this->item('ship-invoice', '송장번호', 'shipping', '송장,바코드', '#0E7490', 'LU-9040', 90, 40, 2, 6, 'rect', 0, '#FFFFFF',
                $this->barcodeCard(90, 40, '#0E7490', '송장번호', 'CJ대한통운', '1234-5678-9012')),
            $this->item('ship-qc', '출고 검수', 'shipping', '검수,출고', '#166534', 'LU-6030', 60, 30, 3, 8, 'roundrect', 1.4, '#F0FDF4',
                $this->banner(60, 30, '#166534', '#F0FDF4', 'QC PASS', '출고 검수 완료', '')),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function beauty(): array
    {
        return [
            $this->item('beauty-serum', '비타민 세럼', 'beauty', '세럼,화장품,원형', '#B76E79', 'LU-R38', 38, 38, 4, 6, 'ellipse', 0, '#FFF6F7',
                $this->circleBadge(38, '#B76E79', '#FFF6F7', 'SERUM', '비타민 C', '30ml')),
            $this->item('beauty-cream', '핸드크림', 'beauty', '크림,뷰티', '#8B5E3C', 'LU-6040B', 60, 40, 3, 6, 'roundrect', 3, '#FFF8F1',
                $this->banner(60, 40, '#8B5E3C', '#FFF8F1', 'HAND CREAM', '시어버터 핸드크림', '50ml')),
            $this->item('beauty-soap', '크래프트 비누', 'beauty', '비누,천연', '#A67C52', 'LU-5050', 50, 50, 3, 5, 'roundrect', 4, '#FBF4EA',
                $this->circleBadge(50, '#A67C52', '#FBF4EA', 'SOAP', '오트밀 비누', '수제')),
            $this->item('beauty-perfume', '미니 향수', 'beauty', '향수,세로', '#4A3F55', 'LU-3050', 30, 50, 6, 5, 'roundrect', 2, '#F7F3FA',
                $this->vertical(30, 50, '#4A3F55', '#F7F3FA', 'SCENT', '블랑 오드뚜왈렛', '10ml')),
            $this->item('beauty-shampoo', '허브 샴푸', 'beauty', '샴푸,헤어', '#3D6B5A', 'LU-7040', 70, 40, 2, 6, 'roundrect', 2, '#F1F7F4',
                $this->banner(70, 40, '#3D6B5A', '#F1F7F4', 'SHAMPOO', '로즈마리 샴푸', '300ml')),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function price(): array
    {
        return [
            $this->item('price-sale30', '세일 30%', 'price', '세일,할인', '#E11D48', 'LU-5030P', 50, 30, 3, 8, 'roundrect', 1.6, '#FFF1F2',
                $this->priceTag(50, 30, '#E11D48', 'SALE', '30%', '한정특가')),
            $this->item('price-retail', '정가·할인가', 'price', '가격,할인', '#7B2840', 'LU-7036P', 70, 36, 2, 7, 'rect', 0, '#FFFFFF',
                $this->pricePair(70, 36, '#7B2840', '아몬드 쿠키', '4,900', '3,500')),
            $this->item('price-origin', '원산지 가격', 'price', '원산지,가격', '#1D4ED8', 'LU-6040P', 60, 40, 3, 6, 'roundrect', 1.8, '#EFF6FF',
                $this->banner(60, 40, '#1D4ED8', '#EFF6FF', 'ORIGIN', '국내산 한우', '100g 12,800원')),
            $this->item('price-barcode', '바코드 가격표', 'price', '바코드,가격', '#111827', 'LU-8040', 80, 40, 2, 6, 'rect', 0, '#FFFFFF',
                $this->barcodeCard(80, 40, '#111827', '12,800원', 'SKU-88421', '8809876543210')),
            $this->item('price-timesale', '타임세일', 'price', '타임세일', '#F59E0B', 'LU-5050P', 50, 50, 3, 5, 'roundrect', 3, '#FFFBEB',
                $this->priceTag(50, 50, '#F59E0B', 'TIME', 'SALE', '오늘만')),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function round(): array
    {
        return [
            $this->item('round-thankyou', 'Thank You', 'round', '감사,원형', '#BE185D', 'LU-R42', 42, 42, 4, 6, 'ellipse', 0, '#FDF2F8',
                $this->circleBadge(42, '#BE185D', '#FDF2F8', 'THANK', 'YOU', '♥')),
            $this->item('round-thanks-kr', '감사합니다', 'round', '감사,스티커', '#9F1239', 'LU-R50', 50, 50, 3, 5, 'ellipse', 0, '#FFF1F2',
                $this->circleBadge(50, '#9F1239', '#FFF1F2', '감사', '합니다', '고마워요')),
            $this->item('round-handmade', '핸드메이드', 'round', '수제,원형', '#92400E', 'LU-R45', 45, 45, 4, 5, 'ellipse', 0, '#FFF7ED',
                $this->circleBadge(45, '#92400E', '#FFF7ED', 'MADE', 'HAND', 'with love')),
            $this->item('round-new', 'NEW 스티커', 'round', '신상,원형', '#DC2626', 'LU-R35', 35, 35, 5, 7, 'ellipse', 0, '#FEF2F2',
                $this->circleBadge(35, '#DC2626', '#FEF2F2', 'NEW', '신상품', '')),
            $this->item('round-best', 'BEST 스티커', 'round', '베스트,원형', '#B45309', 'LU-R36', 36, 36, 5, 7, 'ellipse', 0, '#FFFBEB',
                $this->circleBadge(36, '#B45309', '#FFFBEB', 'BEST', '인기', '')),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function gift(): array
    {
        return [
            $this->item('gift-ribbon', '리본 선물', 'gift', '선물,리본', '#9D174D', 'LU-6040G', 60, 40, 3, 6, 'roundrect', 2.4, '#FDF2F8',
                $this->banner(60, 40, '#9D174D', '#FDF2F8', 'FOR YOU', '소중한 선물', '리본을 풀어보세요')),
            $this->item('gift-wedding', '웨딩 답례', 'gift', '웨딩,답례', '#6B21A8', 'LU-5030G', 50, 30, 3, 8, 'roundrect', 8, '#FAF5FF',
                $this->banner(50, 30, '#6B21A8', '#FAF5FF', 'WEDDING', '감사한 마음', '')),
            $this->item('gift-birthday', '생일 축하', 'gift', '생일', '#DB2777', 'LU-7050G', 70, 50, 2, 5, 'roundrect', 3, '#FDF2F8',
                $this->banner(70, 50, '#DB2777', '#FDF2F8', 'HAPPY', '생일 축하해요', 'BIRTHDAY')),
            $this->item('gift-thanks-card', '감사 카드형', 'gift', '감사,카드', '#7B2840', 'LU-8040G', 80, 40, 2, 6, 'roundrect', 2, '#FFF7F8',
                $this->banner(80, 40, '#7B2840', '#FFF7F8', 'THANK YOU', '마음 담아 보냅니다', 'LabelUp')),
            $this->item('gift-name', '선물 이름표', 'gift', '이름표', '#BE185D', 'LU-7030', 70, 30, 2, 8, 'roundrect', 2, '#FFFFFF',
                $this->banner(70, 30, '#BE185D', '#FFFFFF', 'TO.', '받는 분 성함', 'FROM. 라벨업')),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function cafe(): array
    {
        return [
            $this->item('cafe-latte', '라떼 컵라벨', 'cafe', '카페,커피', '#4B2E20', 'LU-7040C', 70, 40, 2, 6, 'roundrect', 2, '#F8F1EA',
                $this->banner(70, 40, '#4B2E20', '#F8F1EA', 'LATTE', '오트 라떼', 'ICE · Regular')),
            $this->item('cafe-bean', '원두 로스팅', 'cafe', '원두,로스팅', '#3F2A1D', 'LU-8050C', 80, 50, 2, 5, 'roundrect', 2, '#F6EFE7',
                $this->tableLabel(80, 50, '#3F2A1D', '에티오피아 예가체프', [['로스팅', '미디엄'], ['무게', '200g'], ['도정', '2026-09-01']])),
            $this->item('cafe-dessert', '디저트', 'cafe', '디저트,케이크', '#B45309', 'LU-5050C', 50, 50, 3, 5, 'roundrect', 4, '#FFF7ED',
                $this->circleBadge(50, '#B45309', '#FFF7ED', 'SWEET', '바스크 치즈', '조각')),
            $this->item('cafe-coldbrew', '콜드브루', 'cafe', '콜드브루,세로', '#1C1917', 'LU-4070', 40, 70, 4, 4, 'roundrect', 2, '#FAF7F2',
                $this->vertical(40, 70, '#1C1917', '#FAF7F2', 'COLD', '콜드브루 원액', '500ml')),
            $this->item('cafe-stamp', '스탬프 원형', 'cafe', '스탬프,원형', '#78350F', 'LU-R34', 34, 34, 5, 7, 'ellipse', 0, '#FFFBEB',
                $this->circleBadge(34, '#78350F', '#FFFBEB', 'CAFE', '스탬프', '')),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function warning(): array
    {
        return [
            $this->item('warn-hot', '고온주의', 'warning', '고온,주의', '#B45309', 'LU-6040W', 60, 40, 3, 6, 'roundrect', 1.6, '#FFFBEB',
                $this->banner(60, 40, '#B45309', '#FFFBEB', 'HOT', '고온 주의', '만지지 마세요')),
            $this->item('warn-kids', '어린이 보호', 'warning', '어린이,안전', '#0369A1', 'LU-5050W', 50, 50, 3, 5, 'roundrect', 3, '#F0F9FF',
                $this->circleBadge(50, '#0369A1', '#F0F9FF', 'KIDS', '어린이 보호', '주의')),
            $this->item('warn-allergy', '알레르기', 'warning', '알레르기', '#B91C1C', 'LU-7036W', 70, 36, 2, 7, 'rect', 0, '#FEF2F2',
                $this->banner(70, 36, '#B91C1C', '#FEF2F2', 'ALLERGY', '우유 · 견과 · 대두', '함유 주의')),
            $this->item('warn-expire', '유효기간', 'warning', '유통기한', '#334155', 'LU-5025', 50, 25, 3, 10, 'rect', 0, '#FFFFFF',
                $this->banner(50, 25, '#334155', '#FFFFFF', 'EXP', '유통기한 2026.12.31', '')),
            $this->item('warn-recycle', '분리배출', 'warning', '재활용,원형', '#15803D', 'LU-R40W', 40, 40, 4, 6, 'ellipse', 0, '#F0FDF4',
                $this->circleBadge(40, '#15803D', '#F0FDF4', 'RE', '분리배출', 'PET')),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function warehouse(): array
    {
        return [
            $this->item('wh-sku', 'SKU 바코드', 'warehouse', 'SKU,바코드', '#0F172A', 'LU-8030', 80, 30, 2, 8, 'rect', 0, '#FFFFFF',
                $this->barcodeCard(80, 30, '#0F172A', 'SKU-A1024', '로케이션 A-12-03', 'A1024-0099')),
            $this->item('wh-location', '로케이션', 'warehouse', '로케이션', '#1E3A5F', 'LU-7036W2', 70, 36, 2, 7, 'rect', 0, '#F8FAFC',
                $this->banner(70, 36, '#1E3A5F', '#F8FAFC', 'LOC', 'A-12-03', '2층 냉동')),
            $this->item('wh-qr', 'QR 재고', 'warehouse', 'QR,재고', '#0F766E', 'LU-5050Q', 50, 50, 3, 5, 'roundrect', 2, '#F0FDFA',
                $this->qrCard(50, 50, '#0F766E', '재고조회', 'https://labelup.kr/stock/A1024')),
            $this->item('wh-lot', '로트번호', 'warehouse', '로트,바코드', '#334155', 'LU-9040W', 90, 40, 2, 6, 'rect', 0, '#FFFFFF',
                $this->barcodeCard(90, 40, '#334155', 'LOT 20260902-A', '제조로트', '20260902A01')),
            $this->item('wh-checked', '검수완료', 'warehouse', '검수', '#166534', 'LU-6030W', 60, 30, 3, 8, 'roundrect', 1.4, '#F0FDF4',
                $this->banner(60, 30, '#166534', '#F0FDF4', 'CHECKED', '검수 완료', '')),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function event(): array
    {
        return [
            $this->item('event-popup', '팝업스토어', 'event', '팝업,행사', '#7C3AED', 'LU-7050E', 70, 50, 2, 5, 'roundrect', 3, '#F5F3FF',
                $this->banner(70, 50, '#7C3AED', '#F5F3FF', 'POP-UP', '주말 팝업스토어', '9.5–9.7')),
            $this->item('event-ticket', '미니 입장권', 'event', '입장권,바코드', '#111827', 'LU-9040E', 90, 40, 2, 6, 'rect', 0, '#FFFFFF',
                $this->barcodeCard(90, 40, '#111827', '입장권 A-012', '9.5 14:00', 'TKT20260905')),
            $this->item('event-name', '네임택', 'event', '네임택', '#1D4ED8', 'LU-7030E', 70, 30, 2, 8, 'roundrect', 2, '#EFF6FF',
                $this->banner(70, 30, '#1D4ED8', '#EFF6FF', 'HELLO', '이름 / 소속', '라벨업')),
            $this->item('event-prize', '경품', 'event', '경품', '#C2410C', 'LU-5050E', 50, 50, 3, 5, 'roundrect', 4, '#FFF7ED',
                $this->circleBadge(50, '#C2410C', '#FFF7ED', 'WIN', '경품교환', '1등')),
            $this->item('event-coupon', '할인쿠폰', 'event', '쿠폰,할인', '#BE123C', 'LU-8040E', 80, 40, 2, 6, 'roundrect', 2, '#FFF1F2',
                $this->priceTag(80, 40, '#BE123C', 'COUPON', '2,000원', '3만원 이상')),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $objects
     * @return array<string, mixed>
     */
    private function item(
        string $slug,
        string $name,
        string $cat,
        string $tags,
        string $tone,
        string $no,
        float $w,
        float $h,
        int $cols,
        int $rows,
        string $shape,
        float $radius,
        string $bg,
        array $objects
    ): array {
        return compact('slug', 'name', 'cat', 'tags', 'tone', 'no', 'w', 'h', 'cols', 'rows', 'shape', 'radius', 'bg', 'objects') + [
            'desc' => $name . ' 라벨 테마. 편집기에서 바로 수정할 수 있습니다.',
        ];
    }

    /** @return array<string, mixed> */
    private function paper(string $no, string $name, float $w, float $h, int $cols, int $rows, string $shape, float $radius): array
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
            'columns' => $cols,
            'rows' => $rows,
            'leftMarginMm' => 10.0,
            'topMarginMm' => 10.0,
            'rightMarginMm' => 10.0,
            'bottomMarginMm' => 10.0,
            'hGapMm' => 2.0,
            'vGapMm' => 2.0,
            'labelColor' => '#FFFFFF',
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

    /** @return array<int, array<string, mixed>> */
    private function circleBadge(float $d, string $accent, string $inner, string $t1, string $t2, string $t3): array
    {
        $pad = $d * 0.06;
        $innerD = $d - $pad * 2;
        $objs = [
            $this->ellipse(0, 0, $d, $d, $accent),
            $this->ellipse($pad, $pad, $innerD, $innerD, $inner, $accent, 0.35),
            $this->text($d * 0.12, $d * 0.18, $d * 0.76, $d * 0.22, $t1, $accent, max(3.2, $d * 0.16), true),
            $this->text($d * 0.1, $d * 0.42, $d * 0.8, $d * 0.22, $t2, $accent, max(2.6, $d * 0.11), true),
        ];
        if ($t3 !== '') {
            $objs[] = $this->text($d * 0.15, $d * 0.66, $d * 0.7, $d * 0.18, $t3, $accent, max(2.2, $d * 0.08), false);
        }
        return $objs;
    }

    /** @return array<int, array<string, mixed>> */
    private function banner(float $w, float $h, string $accent, string $bg, string $kicker, string $title, string $sub): array
    {
        $barH = max(6.0, $h * 0.28);
        $objs = [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(0, 0, $w, $barH, $accent),
            $this->text(1.2, 0.4, $w - 2.4, $barH - 0.6, $kicker, '#FFFFFF', max(2.8, $barH * 0.42), true),
            $this->text(2, $barH + 1.2, $w - 4, $h * 0.36, $title, $accent, max(3.2, $h * 0.16), true),
        ];
        if ($sub !== '') {
            $objs[] = $this->text(2, $h * 0.72, $w - 4, $h * 0.22, $sub, '#5B5560', max(2.2, $h * 0.09), false);
        }
        return $objs;
    }

    /** @return array<int, array<string, mixed>> */
    private function vertical(float $w, float $h, string $accent, string $bg, string $kicker, string $title, string $sub): array
    {
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(0, 0, $w, $h * 0.22, $accent),
            $this->text(1, $h * 0.03, $w - 2, $h * 0.16, $kicker, '#FFFFFF', max(2.6, $w * 0.14), true),
            $this->text(2, $h * 0.32, $w - 4, $h * 0.32, $title, $accent, max(3.0, $w * 0.16), true),
            $this->text(2, $h * 0.72, $w - 4, $h * 0.16, $sub, '#5B5560', max(2.2, $w * 0.1), false),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function barcodeCard(float $w, float $h, string $accent, string $title, string $sub, string $code): array
    {
        $barH = max(10.0, $h * 0.38);
        return [
            $this->rect(0, 0, $w, $h, '#FFFFFF'),
            $this->rect(0, 0, 2.2, $h, $accent),
            $this->text(4, 1.2, $w - 6, $h * 0.22, $title, $accent, max(2.8, $h * 0.14), true, 'left'),
            $this->text(4, $h * 0.22, $w - 6, $h * 0.16, $sub, '#6B7280', max(2.0, $h * 0.08), false, 'left'),
            $this->barcode(5, $h - $barH - 1.4, $w - 10, $barH, $code),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function qrCard(float $w, float $h, string $accent, string $title, string $url): array
    {
        $q = min($w, $h) * 0.48;
        return [
            $this->rect(0, 0, $w, $h, '#FFFFFF'),
            $this->qr(($w - $q) / 2, 3, $q, $url),
            $this->text(2, $h - 12, $w - 4, 5, $title, $accent, 3.2, true),
            $this->text(2, $h - 7, $w - 4, 5, 'QR로 조회', '#6B7280', 2.2, false),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function priceTag(float $w, float $h, string $accent, string $kicker, string $price, string $sub): array
    {
        return [
            $this->rect(0, 0, $w, $h, $accent),
            $this->text(2, $h * 0.06, $w - 4, $h * 0.2, $kicker, '#FFFFFF', max(2.6, $h * 0.12), true),
            $this->text(2, $h * 0.28, $w - 4, $h * 0.42, $price, '#FFFFFF', max(5.0, $h * 0.28), true),
            $this->text(2, $h * 0.74, $w - 4, $h * 0.18, $sub, '#FFE4E6', max(2.2, $h * 0.09), false),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function pricePair(float $w, float $h, string $accent, string $name, string $origin, string $sale): array
    {
        return [
            $this->rect(0, 0, $w, $h, '#FFFFFF'),
            $this->rect(0, 0, $w, $h * 0.36, $accent),
            $this->text(2, 0.6, $w - 4, $h * 0.3, $name, '#FFFFFF', max(3.0, $h * 0.16), true),
            $this->text(3, $h * 0.42, $w * 0.42, $h * 0.22, $origin, '#9CA3AF', max(2.4, $h * 0.1), false, 'left'),
            $this->text($w * 0.42, $h * 0.5, $w * 0.54, $h * 0.38, $sale, $accent, max(4.4, $h * 0.22), true, 'right'),
        ];
    }

    /**
     * @param array<int, array{0:string,1:string}> $rows
     * @return array<int, array<string, mixed>>
     */
    private function tableLabel(float $w, float $h, string $accent, string $title, array $rows): array
    {
        $cells = [];
        foreach ($rows as $row) {
            $cells[] = $row[0];
            $cells[] = $row[1];
        }
        return [
            $this->rect(0, 0, $w, $h, '#FFFFFF'),
            $this->rect(0, 0, $w, $h * 0.28, $accent),
            $this->text(2, 0.6, $w - 4, $h * 0.24, $title, '#FFFFFF', max(3.0, $h * 0.12), true),
            $this->table(3, $h * 0.34, $w - 6, $h * 0.58, 3, 2, $cells, $accent),
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
            'barcodeShowText' => true,
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

    /**
     * @param array<int, string> $cells
     * @return array<string, mixed>
     */
    private function table(float $x, float $y, float $w, float $h, int $rows, int $cols, array $cells, string $stroke): array
    {
        return $this->base('table', $x, $y, $w, $h, '#2E2A27', $stroke, 0.2) + [
            'tableRows' => $rows,
            'tableCols' => $cols,
            'tableCells' => $cells,
            'tableBorderWidth' => 0.2,
            'fontSize' => 2.4,
        ];
    }

    /** @return array<string, mixed> */
    private function base(string $type, float $x, float $y, float $w, float $h, string $fill, string $stroke, float $sw): array
    {
        return [
            'id' => sprintf('tpl%03d', ++$this->seq),
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
