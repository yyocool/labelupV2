<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 사무·물류·QC 60종 — 첨부 시트의 바코드/경고/상태/양식 라벨.
 * 기존 LabelTemplateSeedService 와 같은 편집기 문서 포맷.
 */
final class LabelTemplateOfficePackSeedService
{
    private int $seq = 0;
    private int $z = 0;

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $out = [];
        $order = 400;
        foreach ($this->catalog() as $row) {
            $this->seq = 0;
            $this->z = 0;
            $w = (float) $row['w'];
            $h = (float) $row['h'];
            $bg = (string) $row['bg'];
            $tone = (string) $row['tone'];
            $objects = $this->compose($row);
            $paper = $this->paper((string) $row['no'], (string) $row['name'], $w, $h, (string) $row['shape'], (float) $row['radius'], $bg);
            $envelope = $this->envelope((string) $row['name'], $paper, $bg, $objects);
            $out[] = [
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'category' => (string) $row['cat'],
                'tags' => (string) $row['tags'],
                'description' => (string) $row['desc'],
                'tone' => $tone,
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalog(): array
    {
        return [
            $this->def('office-product-code', '상품코드', 'warehouse', '바코드,상품코드', '#212121', 'LU-O70', 70, 36, 'roundrect', 1.6, '#FFFFFF',
                'barcode', '상품코드', '8801234567890', 'Product Code', '상품 바코드 라벨'),
            $this->def('office-qr-scan', 'QR 스캔', 'warehouse', 'QR,스캔', '#212121', 'LU-O50', 50, 50, 'roundrect', 2.0, '#FFFFFF',
                'qr', 'QR CODE', 'https://labelup.kr/scan', 'Scan please', 'QR 조회 라벨'),
            $this->def('office-ship-to', '배송지', 'shipping', '배송,주소', '#212121', 'LU-O80', 80, 45, 'roundrect', 1.8, '#FFFFFF',
                'address', '배송지', '서울시 강남구 테헤란로 123', '받는분 / 연락처', '배송지 주소 라벨'),
            $this->def('office-fragile', '취급주의 파손', 'warning', '파손,취급주의', '#E53935', 'LU-O60', 60, 40, 'roundrect', 1.8, '#FFFFFF',
                'warn', '취급주의', 'FRAGILE', 'HANDLE WITH CARE', '파손주의 라벨'),
            $this->def('office-this-side-up', '이 면이 위로', 'warning', '방향,위로', '#212121', 'LU-O60', 60, 40, 'roundrect', 1.8, '#FFFFFF',
                'arrows', '이 면이 위로', 'THIS SIDE UP', '↑ ↑', '방향 표시 라벨'),
            $this->def('office-keep-dry', '습기주의', 'warning', '습기,보관', '#1E88E5', 'LU-O60', 60, 40, 'roundrect', 1.8, '#FFFFFF',
                'warn', '습기주의', 'KEEP DRY', 'KEEP AWAY FROM WATER', '습기주의 라벨'),
            $this->def('office-handle-care', '취급주의 박스', 'warning', '취급,박스', '#E53935', 'LU-O60', 60, 40, 'roundrect', 1.8, '#FFFFFF',
                'warn', '취급주의', 'HANDLE WITH CARE', '파손 주의', '취급주의 라벨'),
            $this->def('office-caution', '주의', 'warning', '주의,경고', '#F9A825', 'LU-O60', 60, 40, 'roundrect', 1.8, '#FFF8E1',
                'caution', '주의', 'CAUTION', '주의하세요', '주의 경고 라벨'),
            $this->def('office-chilled', '냉장보관', 'warning', '냉장,보관', '#1E88E5', 'LU-O60', 60, 40, 'roundrect', 1.8, '#E3F2FD',
                'warn', '냉장보관', 'CHILLED', '0~10°C', '냉장보관 라벨'),
            $this->def('office-frozen', '냉동보관', 'warning', '냉동,보관', '#E53935', 'LU-O60', 60, 40, 'roundrect', 1.8, '#FFEBEE',
                'warn', '냉동보관', 'FROZEN', '-18°C 이하', '냉동보관 라벨'),

            $this->def('office-express', '특송', 'shipping', '특송,배송', '#E53935', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '특송', 'EXPRESS', '당일·익일 배송', '특송 상태 라벨'),
            $this->def('office-standard', '일반배송', 'shipping', '일반,배송', '#212121', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '일반배송', 'STANDARD', '일반 택배', '일반배송 라벨'),
            $this->def('office-return', '반품', 'shipping', '반품', '#212121', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '반품', 'RETURN', '반품 처리', '반품 라벨'),
            $this->def('office-exchange', '교환', 'shipping', '교환', '#212121', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '교환', 'EXCHANGE', '교환 처리', '교환 라벨'),
            $this->def('office-qc-pass', 'QC 합격', 'warehouse', '검수,합격', '#43A047', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', 'QC 합격', 'QC PASSED', '검수 완료', 'QC 합격 라벨'),
            $this->def('office-qc-fail', 'QC 불합격', 'warehouse', '검수,불합격', '#E53935', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', 'QC 불합격', 'QC FAILED', '재검수 필요', 'QC 불합격 라벨'),
            $this->def('office-inventory', '재고조사', 'warehouse', '재고,실사', '#E53935', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '재고조사', 'INVENTORY', '실사 대상', '재고조사 라벨'),
            $this->def('office-shipped', '출고완료', 'shipping', '출고', '#1E88E5', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '출고완료', 'SHIPPED', '배송 출발', '출고완료 라벨'),
            $this->def('office-received', '입고완료', 'warehouse', '입고', '#212121', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '입고완료', 'RECEIVED', '입고 확인', '입고완료 라벨'),
            $this->def('office-hold', '보류', 'warehouse', '보류', '#F9A825', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '보류', 'HOLD', '출고 보류', '보류 라벨'),

            $this->def('office-doc-box', '문서보관', 'office', '문서,보관함', '#212121', 'LU-O70', 70, 40, 'roundrect', 1.6, '#FFFFFF',
                'form3', '문서보관', '분류 / 기간 / 담당', 'Document Storage', '문서함 라벨'),
            $this->def('office-filename', '파일명', 'office', '파일,문서', '#212121', 'LU-O70', 70, 36, 'roundrect', 1.6, '#FFFFFF',
                'form2', '파일명', '날짜 / 담당자', 'File Name', '파일명 라벨'),
            $this->def('office-meeting', '회의자료', 'office', '회의,체크', '#212121', 'LU-O70', 70, 42, 'roundrect', 1.6, '#FFFFFF',
                'checks', '회의자료', '기획,디자인,개발,마케팅', 'Meeting Material', '회의자료 라벨'),
            $this->def('office-customer', '고객정보', 'office', '고객,연락처', '#212121', 'LU-O70', 70, 40, 'roundrect', 1.6, '#FFFFFF',
                'form3', '고객정보', '이름 / 연락처 / 메모', 'Customer Info', '고객정보 라벨'),
            $this->def('office-asset', '자산관리', 'warehouse', '자산,바코드', '#212121', 'LU-O70', 70, 36, 'roundrect', 1.6, '#FFFFFF',
                'barcode', '자산관리', 'A0001234', 'Asset No.', '자산 바코드 라벨'),
            $this->def('office-supply', '사무용품', 'office', '비품,바코드', '#212121', 'LU-O70', 70, 36, 'roundrect', 1.6, '#FFFFFF',
                'barcode', '사무용품', 'OFC-2025-001', 'Office Supply', '사무용품 바코드'),
            $this->def('office-book', '도서관리', 'office', '도서,바코드', '#212121', 'LU-O70', 70, 36, 'roundrect', 1.6, '#FFFFFF',
                'barcode', '도서관리', 'BK-000567', 'Library', '도서 바코드 라벨'),
            $this->def('office-sample', '샘플', 'event', '샘플,비매품', '#212121', 'LU-O60', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'boxed', '샘플', 'SAMPLE', 'NOT FOR SALE', '샘플 비매품 라벨'),
            $this->def('office-event-use', '행사용', 'event', '행사,내부', '#212121', 'LU-O60', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'boxed', '행사용', 'EVENT USE ONLY', '행사 전용', '행사용 라벨'),
            $this->def('office-internal', '내부용', 'office', '내부,보안', '#212121', 'LU-O60', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'boxed', '내부용', 'INTERNAL USE ONLY', '외부 유출 금지', '내부용 라벨'),

            $this->def('office-confidential', '대외비', 'office', '대외비,보안', '#E53935', 'LU-O60', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'boxed', '대외비', 'CONFIDENTIAL', '보안 문서', '대외비 라벨'),
            $this->def('office-restricted', '제한', 'office', '제한,보안', '#E53935', 'LU-O60', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'boxed', '제한', 'RESTRICTED', '열람 제한', '제한 라벨'),
            $this->def('office-pending', '승인대기', 'office', '승인,대기', '#E53935', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '승인대기', 'PENDING APPROVAL', '결재 대기', '승인대기 라벨'),
            $this->def('office-approved', '승인', 'office', '승인,완료', '#43A047', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '승인', 'APPROVED', '결재 완료', '승인 라벨'),
            $this->def('office-revision', '수정필요', 'office', '수정,반려', '#E53935', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '수정필요', 'NEEDS REVISION', '재작성 요청', '수정필요 라벨'),
            $this->def('office-discard', '폐기대상', 'office', '폐기', '#E53935', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '폐기대상', 'TO BE DISCARDED', '폐기 예정', '폐기대상 라벨'),
            $this->def('office-important', '중요', 'office', '중요', '#E53935', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '중요', 'IMPORTANT', '우선 처리', '중요 표시 라벨'),
            $this->def('office-for-customer', '고객용', 'gift', '고객,선물', '#212121', 'LU-O60', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '고객용', 'FOR CUSTOMER', '고객 전달', '고객용 라벨'),
            $this->def('office-free-gift', '사은품', 'gift', '사은품,증정', '#212121', 'LU-O60', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '사은품', 'FREE GIFT', '증정용', '사은품 라벨'),
            $this->def('office-quotation', '견적서', 'office', '견적,문서', '#212121', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '견적서', 'QUOTATION', '견적 서류', '견적서 라벨'),

            $this->def('office-tax-invoice', '세금계산서', 'office', '세금,계산서', '#212121', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '세금계산서', 'TAX INVOICE', '세금 서류', '세금계산서 라벨'),
            $this->def('office-statement', '거래명세서', 'office', '명세,거래', '#212121', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '거래명세서', 'STATEMENT', '거래 내역', '거래명세서 라벨'),
            $this->def('office-receipt', '영수증', 'office', '영수증', '#212121', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '영수증', 'RECEIPT', '수령 확인', '영수증 라벨'),
            $this->def('office-purchase', '발주서', 'office', '발주,구매', '#212121', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '발주서', 'PURCHASE ORDER', '구매 요청', '발주서 라벨'),
            $this->def('office-delivery-note', '거래명세서(납품)', 'shipping', '납품,배송', '#212121', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '납품서', 'DELIVERY NOTE', '납품 확인', '납품서 라벨'),
            $this->def('office-contract', '계약서', 'office', '계약,서명', '#212121', 'LU-O60S', 60, 32, 'roundrect', 1.4, '#FFFFFF',
                'status', '계약서', 'CONTRACT', '계약 서류', '계약서 라벨'),
            $this->def('office-qc-stamp', '검수 스탬프', 'warehouse', '검수,스탬프,원형', '#1E88E5', 'LU-OR40', 40, 40, 'ellipse', 0, '#FFFFFF',
                'stamp', '검수', 'INSPECTED', 'QC', '검수 원형 스탬프'),
            $this->def('office-pass-stamp', '합격 스탬프', 'warehouse', '합격,스탬프,원형', '#43A047', 'LU-OR40', 40, 40, 'ellipse', 0, '#FFFFFF',
                'stamp', '합격', 'PASS', 'OK', '합격 원형 스탬프'),
            $this->def('office-fail-stamp', '불합격 스탬프', 'warehouse', '불합격,스탬프,원형', '#E53935', 'LU-OR40', 40, 40, 'ellipse', 0, '#FFFFFF',
                'stamp', '불합격', 'FAIL', 'NG', '불합격 원형 스탬프'),
            $this->def('office-created', '작성일', 'office', '날짜,작성', '#212121', 'LU-O60', 60, 30, 'roundrect', 1.4, '#FFFFFF',
                'date', '작성일', '2026. 09. 10', 'Created', '작성일 라벨'),

            $this->def('office-expire', '유효기간', 'warehouse', '유통기한,날짜', '#212121', 'LU-O60', 60, 30, 'roundrect', 1.4, '#FFFFFF',
                'date', '유효기간', '2026. 12. 31', 'Exp. Date', '유효기간 라벨'),
            $this->def('office-mfg', '제조일', 'warehouse', '제조,날짜', '#212121', 'LU-O60', 60, 30, 'roundrect', 1.4, '#FFFFFF',
                'date', '제조일', '2026. 09. 10', 'Mfg. Date', '제조일 라벨'),
            $this->def('office-lot', '로트번호', 'warehouse', '로트,번호', '#212121', 'LU-O70', 70, 32, 'roundrect', 1.4, '#FFFFFF',
                'date', '로트번호', 'LOT20260910', 'Lot No.', '로트번호 라벨'),
            $this->def('office-product-name', '상품명', 'warehouse', '상품,규격', '#212121', 'LU-O70', 70, 40, 'roundrect', 1.6, '#FFFFFF',
                'form2', '상품명', '규격 / 수량', 'Product Name', '상품명 라벨'),
            $this->def('office-location', '보관위치', 'warehouse', '위치,로케이션', '#212121', 'LU-O70', 70, 40, 'roundrect', 1.6, '#FFFFFF',
                'form3', '보관위치', '구역 / 선반 / 칸', 'Storage Location', '보관위치 라벨'),
            $this->def('office-owner', '담당자', 'office', '담당,연락처', '#212121', 'LU-O70', 70, 36, 'roundrect', 1.6, '#FFFFFF',
                'form2', '담당자', '이름 / 연락처', 'Person in Charge', '담당자 라벨'),
            $this->def('office-project', '프로젝트', 'office', '프로젝트,체크', '#212121', 'LU-O70', 70, 42, 'roundrect', 1.6, '#FFFFFF',
                'checks', '프로젝트', '기획,개발,디자인,운영', 'Project', '프로젝트 라벨'),
            $this->def('office-gift-pack', '선물포장', 'gift', '선물,리본', '#E53935', 'LU-O50', 50, 40, 'roundrect', 2.0, '#FFF5F5',
                'badge', '선물포장', 'GIFT PACKAGE', 'with ribbon', '선물포장 라벨'),
            $this->def('office-handmade', '핸드메이드', 'gift', '수제,핸드메이드', '#8D6E63', 'LU-O50', 50, 40, 'roundrect', 2.0, '#EFEBE9',
                'badge', '핸드메이드', 'HAND MADE', 'with love', '핸드메이드 라벨'),
            $this->def('office-eco', '친환경', 'gift', '친환경,에코', '#43A047', 'LU-O50', 50, 40, 'roundrect', 2.0, '#E8F5E9',
                'badge', '친환경', 'ECO FRIENDLY', 'plant based', '친환경 라벨'),
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
        string $no,
        float $w,
        float $h,
        string $shape,
        float $radius,
        string $bg,
        string $layout,
        string $title,
        string $mid,
        string $sub,
        string $desc
    ): array {
        return compact('slug', 'name', 'cat', 'tags', 'tone', 'no', 'w', 'h', 'shape', 'radius', 'bg', 'layout', 'title', 'mid', 'sub', 'desc');
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
        $mid = (string) $row['mid'];
        $sub = (string) $row['sub'];

        return match ((string) $row['layout']) {
            'barcode' => $this->layoutBarcode($w, $h, $bg, $tone, $title, $mid, $sub),
            'qr' => $this->layoutQr($w, $h, $bg, $tone, $title, $mid, $sub),
            'address' => $this->layoutAddress($w, $h, $bg, $tone, $title, $mid),
            'warn' => $this->layoutWarn($w, $h, $bg, $tone, $title, $mid, $sub),
            'arrows' => $this->layoutArrows($w, $h, $bg, $tone, $title, $mid),
            'caution' => $this->layoutCaution($w, $h, $bg, $tone, $title, $mid, $sub),
            'status' => $this->layoutStatus($w, $h, $bg, $tone, $title, $mid, $sub),
            'form2' => $this->layoutForm($w, $h, $bg, $tone, $title, $this->splitFields($mid, 2)),
            'form3' => $this->layoutForm($w, $h, $bg, $tone, $title, $this->splitFields($mid, 3)),
            'checks' => $this->layoutChecks($w, $h, $bg, $tone, $title, $mid),
            'boxed' => $this->layoutBoxed($w, $h, $bg, $tone, $title, $mid, $sub),
            'stamp' => $this->layoutStamp($w, $h, $bg, $tone, $title, $mid, $sub),
            'date' => $this->layoutDate($w, $h, $bg, $tone, $title, $mid, $sub),
            'badge' => $this->layoutBadge($w, $h, $bg, $tone, $title, $mid, $sub),
            default => $this->layoutStatus($w, $h, $bg, $tone, $title, $mid, $sub),
        };
    }

    /** @return array<int, string> */
    private function splitFields(string $mid, int $n): array
    {
        $parts = array_values(array_filter(array_map('trim', explode('/', str_replace(',', '/', $mid)))));
        while (count($parts) < $n) {
            $parts[] = '항목';
        }
        return array_slice($parts, 0, $n);
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutBarcode(float $w, float $h, string $bg, string $tone, string $title, string $code, string $sub): array
    {
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->text(2.4, 1.4, $w - 4.8, 6.2, $title, $tone, 3.4, true, 'left'),
            $this->barcode(4, 8.2, $w - 8, max(12.0, $h - 18.5), $code),
            $this->text(2.4, $h - 7.2, $w - 4.8, 5.6, $code, '#424242', 2.3, false),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutQr(float $w, float $h, string $bg, string $tone, string $title, string $url, string $sub): array
    {
        $q = min($w, $h) * 0.46;
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->text(2, 1.6, $w - 4, 5.4, $title, $tone, 2.8, true),
            $this->qr(($w - $q) / 2, 8.0, $q, $url),
            $this->text(2, $h - 7.2, $w - 4, 5.6, $sub, '#616161', 2.3, false),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutAddress(float $w, float $h, string $bg, string $tone, string $title, string $sample): array
    {
        $objs = [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(0, 0, $w, 8.2, $tone),
            $this->text(2.4, 1.2, $w - 4.8, 5.8, $title, '#FFFFFF', 3.2, true, 'left'),
        ];
        $labels = ['받는분', '주소', '연락처'];
        $values = ['홍길동', $sample, '010-0000-0000'];
        $y = 10.2;
        $rowH = ($h - 13.0) / 3;
        for ($i = 0; $i < 3; $i++) {
            $objs[] = $this->text(2.6, $y, 14, $rowH * 0.7, $labels[$i], '#757575', 2.1, false, 'left');
            $objs[] = $this->text(17, $y, $w - 20, $rowH * 0.7, $values[$i], $tone, 2.4, false, 'left');
            $objs[] = $this->rect(17, $y + $rowH * 0.72, $w - 20.5, 0.28, '#BDBDBD');
            $y += $rowH;
        }
        return $objs;
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutWarn(float $w, float $h, string $bg, string $tone, string $title, string $en, string $sub): array
    {
        $bar = 8.4;
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(0, 0, $w, $bar, $tone),
            $this->text(2, 1.2, $w - 4, $bar - 2.0, $title, '#FFFFFF', 3.4, true),
            $this->text(2.4, $bar + 3.2, $w - 4.8, 12, $en, $tone, 4.2, true),
            $this->text(2.4, $h - 10, $w - 4.8, 7.2, $sub, '#616161', 2.3, false),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutArrows(float $w, float $h, string $bg, string $tone, string $title, string $en): array
    {
        $aw = 10.0;
        $ax1 = $w / 2 - $aw - 2.4;
        $ax2 = $w / 2 + 2.4;
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->text(2, 2.0, $w - 4, 6.2, $title, $tone, 3.2, true),
            $this->triangle($ax1, 10.0, $aw, 10.5, $tone),
            $this->triangle($ax2, 10.0, $aw, 10.5, $tone),
            $this->rect($ax1 + 3.4, 19.6, 3.2, 7.2, $tone),
            $this->rect($ax2 + 3.4, 19.6, 3.2, 7.2, $tone),
            $this->text(2, $h - 8.4, $w - 4, 6.4, $en, $tone, 3.0, true),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutCaution(float $w, float $h, string $bg, string $tone, string $title, string $en, string $sub): array
    {
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->triangle(($w - 16) / 2, 3.2, 16, 14, $tone),
            $this->text(($w - 16) / 2, 6.8, 16, 8, '!', '#212121', 6.2, true),
            $this->text(2, 18.4, $w - 4, 8, $title, '#212121', 4.4, true),
            $this->text(2, $h - 11, $w - 4, 8, $en, '#616161', 2.6, true),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutStatus(float $w, float $h, string $bg, string $tone, string $title, string $en, string $sub): array
    {
        $bar = max(7.2, $h * 0.30);
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(0, 0, $w, $bar, $tone),
            $this->text(2, 0.8, $w - 4, $bar - 1.4, $title, '#FFFFFF', max(3.0, $bar * 0.42), true),
            $this->text(2.2, $bar + 2.0, $w - 4.4, $h * 0.34, $en, $tone, max(3.2, $h * 0.16), true),
            $this->text(2.2, $h - 8.2, $w - 4.4, 6.4, $sub, '#757575', 2.2, false),
        ];
    }

    /**
     * @param array<int, string> $fields
     * @return array<int, array<string, mixed>>
     */
    private function layoutForm(float $w, float $h, string $bg, string $tone, string $title, array $fields): array
    {
        $objs = [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(0, 0, $w, 8.0, $tone),
            $this->text(2.4, 1.1, $w - 4.8, 5.8, $title, '#FFFFFF', 3.2, true, 'left'),
        ];
        $n = max(1, count($fields));
        $y = 10.0;
        $rowH = ($h - 12.2) / $n;
        foreach ($fields as $label) {
            $objs[] = $this->text(2.6, $y, 16, $rowH * 0.7, $label, '#757575', 2.1, false, 'left');
            $objs[] = $this->rect(19, $y + $rowH * 0.55, $w - 22.5, 0.28, '#BDBDBD');
            $y += $rowH;
        }
        return $objs;
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutChecks(float $w, float $h, string $bg, string $tone, string $title, string $itemsCsv): array
    {
        $items = array_values(array_filter(array_map('trim', explode(',', $itemsCsv))));
        $objs = [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(0, 0, $w, 8.0, $tone),
            $this->text(2.4, 1.1, $w - 4.8, 5.8, $title, '#FFFFFF', 3.2, true, 'left'),
        ];
        $cols = 2;
        $startY = 10.4;
        $box = 3.4;
        foreach ($items as $i => $label) {
            $col = $i % $cols;
            $row = intdiv($i, $cols);
            $x = 3.0 + $col * (($w - 6) / $cols);
            $y = $startY + $row * 7.2;
            $objs[] = $this->rect($x, $y, $box, $box, '#FFFFFF', $tone, 0.35);
            $objs[] = $this->text($x + $box + 1.2, $y - 0.4, 24, 4.4, $label, $tone, 2.2, false, 'left');
        }
        return $objs;
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutBoxed(float $w, float $h, string $bg, string $tone, string $title, string $en, string $sub): array
    {
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->rect(1.4, 1.4, $w - 2.8, $h - 2.8, $bg, $tone, 0.7),
            $this->text(3, 4.0, $w - 6, 9, $title, $tone, 4.0, true),
            $this->text(3, 14.5, $w - 6, 7, $en, $tone, 2.8, true),
            $this->text(3, $h - 10, $w - 6, 6, $sub, '#757575', 2.1, false),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutStamp(float $w, float $h, string $bg, string $tone, string $title, string $en, string $sub): array
    {
        $pad = 1.6;
        return [
            $this->ellipse(0, 0, $w, $h, $bg, $tone, 1.1),
            $this->ellipse($pad, $pad, $w - $pad * 2, $h - $pad * 2, $bg, $tone, 0.45),
            $this->text($w * 0.1, $h * 0.18, $w * 0.8, $h * 0.22, $title, $tone, max(3.0, $h * 0.14), true),
            $this->text($w * 0.08, $h * 0.42, $w * 0.84, $h * 0.22, $en, $tone, max(2.6, $h * 0.12), true),
            $this->text($w * 0.15, $h * 0.66, $w * 0.7, $h * 0.18, $sub, $tone, max(2.2, $h * 0.09), false),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutDate(float $w, float $h, string $bg, string $tone, string $title, string $value, string $en): array
    {
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->text(2.4, 1.6, $w - 4.8, 6.0, $title, $tone, 2.8, true, 'left'),
            $this->text(2.4, 9.0, $w - 4.8, 11, $value, $tone, 4.6, true, 'left'),
            $this->rect(2.4, $h - 8.4, $w - 4.8, 0.3, $tone),
            $this->text(2.4, $h - 7.4, $w - 4.8, 5.6, $en, '#757575', 2.0, false, 'left'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function layoutBadge(float $w, float $h, string $bg, string $tone, string $title, string $en, string $sub): array
    {
        return [
            $this->rect(0, 0, $w, $h, $bg),
            $this->ellipse(($w - 12) / 2, 3.2, 12, 12, $tone),
            $this->text(2, 16.0, $w - 4, 9, $title, $tone, 3.6, true),
            $this->text(2, 26.0, $w - 4, 6.4, $en, $tone, 2.5, true),
            $this->text(2, $h - 7.6, $w - 4, 5.6, $sub, '#757575', 2.0, false),
        ];
    }

    /** @return array<string, mixed> */
    private function triangle(float $x, float $y, float $w, float $h, string $fill): array
    {
        return $this->base('shape', $x, $y, $w, $h, $fill, 'transparent', 0) + [
            'shapeKind' => 'triangle',
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
            'id' => sprintf('off%03d', ++$this->seq),
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
