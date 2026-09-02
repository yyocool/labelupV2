<?php
/** 가져오기 카탈로그 샘플 데이터 */

$sbImportLabelCategories = array(
    array('id' => 'a4', 'label' => 'A4 라벨', 'count' => 249, 'active' => true),
    array('id' => 'jet', 'label' => '제트라벨', 'count' => 198),
    array('id' => 'theroll', 'label' => '더롤라벨', 'count' => 461),
    array('id' => 'a3', 'label' => 'A3 라벨', 'count' => 1),
);

$sbImportTagCategories = array(
    array('id' => 'a4', 'label' => 'A4 태그', 'count' => 78, 'active' => true),
    array('id' => 'jet', 'label' => '제트태그', 'count' => 7),
    array('id' => 'theroll', 'label' => '더롤태그', 'count' => 6),
);

$sbImportBlankSpecs = array(
    'label' => array(
        'a4' => array(
            array('code' => '100', 'size' => '25×20 mm', 'qty' => 84, 'shape' => 'heart'),
            array('code' => '101', 'size' => '15×15 mm', 'qty' => 15, 'shape' => 'circle'),
            array('code' => '102', 'size' => '57.5×48 mm', 'qty' => 12, 'shape' => 'star'),
            array('code' => '103', 'size' => '25×25 mm', 'qty' => 21, 'shape' => 'clover'),
            array('code' => '105', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'speech'),
            array('code' => '106', 'size' => '25×25 mm', 'qty' => 20, 'shape' => 'bone'),
            array('code' => '111', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'arrow'),
            array('code' => '112', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'cloud'),
            array('code' => '113', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'hex'),
            array('code' => '114', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'circle'),
            array('code' => '115', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'star'),
            array('code' => '116', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'heart'),
        ),
        'jet' => array(
            array('code' => 'ZJ030010', 'size' => '30×9.7 mm', 'qty' => 16, 'shape' => 'rect'),
            array('code' => 'ZJ030020', 'size' => '40×15 mm', 'qty' => 12, 'shape' => 'rect'),
            array('code' => 'ZJ030030', 'size' => '50×20 mm', 'qty' => 10, 'shape' => 'rect'),
            array('code' => 'ZJ030040', 'size' => '60×25 mm', 'qty' => 8, 'shape' => 'rect'),
            array('code' => 'ZJ030050', 'size' => '70×30 mm', 'qty' => 6, 'shape' => 'rect'),
            array('code' => 'ZJ030060', 'size' => '80×35 mm', 'qty' => 4, 'shape' => 'rect'),
        ),
        'theroll' => array(
            array('code' => 'RL010010', 'size' => '40×30 mm', 'qty' => 20, 'shape' => 'circle'),
            array('code' => 'RL010020', 'size' => '50×40 mm', 'qty' => 16, 'shape' => 'circle'),
            array('code' => 'RL010030', 'size' => '60×50 mm', 'qty' => 12, 'shape' => 'circle'),
            array('code' => 'RL010040', 'size' => '70×60 mm', 'qty' => 8, 'shape' => 'circle'),
        ),
        'a3' => array(
            array('code' => 'A301001', 'size' => '100×70 mm', 'qty' => 4, 'shape' => 'rect'),
        ),
    ),
    'tag' => array(
        'a4' => array(
            array('code' => 'TLF0021', 'size' => '210×143.5 mm', 'qty' => 2, 'shape' => 'tag-fold', 'type' => 'FOLD'),
            array('code' => 'TLF0061', 'size' => '210×143.5 mm', 'qty' => 4, 'shape' => 'tag-fold', 'type' => 'FOLD'),
            array('code' => 'TLF0101', 'size' => '210×143.5 mm', 'qty' => 6, 'shape' => 'tag-fold', 'type' => 'FOLD'),
            array('code' => 'TLH0021', 'size' => '210×143.5 mm', 'qty' => 2, 'shape' => 'tag-hang', 'type' => 'HANG'),
            array('code' => 'TLH0061', 'size' => '210×143.5 mm', 'qty' => 4, 'shape' => 'tag-hang', 'type' => 'HANG'),
            array('code' => 'TLH0101', 'size' => '210×143.5 mm', 'qty' => 6, 'shape' => 'tag-hang', 'type' => 'HANG'),
        ),
        'jet' => array(
            array('code' => 'TJ010010', 'size' => '50×30 mm', 'qty' => 8, 'shape' => 'tag-fold', 'type' => 'FOLD'),
            array('code' => 'TJ010020', 'size' => '60×40 mm', 'qty' => 6, 'shape' => 'tag-fold', 'type' => 'FOLD'),
            array('code' => 'TJ010030', 'size' => '70×50 mm', 'qty' => 4, 'shape' => 'tag-hang', 'type' => 'HANG'),
        ),
        'theroll' => array(
            array('code' => 'TR010010', 'size' => '45×35 mm', 'qty' => 10, 'shape' => 'tag-fold', 'type' => 'FOLD'),
            array('code' => 'TR010020', 'size' => '55×45 mm', 'qty' => 8, 'shape' => 'tag-fold', 'type' => 'FOLD'),
            array('code' => 'TR010030', 'size' => '65×55 mm', 'qty' => 6, 'shape' => 'tag-hang', 'type' => 'HANG'),
        ),
    ),
);

$sbImportDesignItems = array(
    'label' => array(
        'a4' => array(
            array('title' => '여름 휴가중', 'tags' => '#여름 #휴가 #바다 #해변 #메시지', 'tone' => '#dbeafe'),
            array('title' => 'OPEN 준비중', 'tags' => '#오픈 #카페 #안내 #메시지', 'tone' => '#ffedd5'),
            array('title' => '수리완료 검수확인', 'tags' => '#품질 #관리 #제품', 'tone' => '#fef3c7'),
            array('title' => 'Green Choice', 'tags' => '#친환경 #구매 #감사', 'tone' => '#dcfce7'),
            array('title' => '한라봉청 선물세트', 'tags' => '#선물 #식품 #감사', 'tone' => '#fce7f3'),
            array('title' => '카페 네임스티커', 'tags' => '#카페 #이름 #라벨', 'tone' => '#f3e8ff'),
            array('title' => '926-증정용라벨', 'tags' => '#증정품 #여름 #이벤트', 'tone' => '#e0e7ff'),
            array('title' => '520-감사라벨', 'tags' => '#감사 #선물 #메시지', 'tone' => '#ffe4e6'),
        ),
        'jet' => array(
            array('title' => '제트 배송라벨', 'tags' => '#배송 #택배 #바코드', 'tone' => '#e0f2fe'),
            array('title' => '제트 가격표', 'tags' => '#가격 #매장 #소매', 'tone' => '#fef9c3'),
            array('title' => '제트 재고관리', 'tags' => '#재고 #물류 #관리', 'tone' => '#f1f5f9'),
            array('title' => '제트 증정스티커', 'tags' => '#증정 #이벤트 #프로모션', 'tone' => '#fce7f3'),
        ),
        'theroll' => array(
            array('title' => '더롤 원형라벨', 'tags' => '#원형 #제품 #브랜드', 'tone' => '#ecfdf5'),
            array('title' => '더롤 시즌한정', 'tags' => '#시즌 #한정 #선물', 'tone' => '#fff7ed'),
            array('title' => '더롤 수제청', 'tags' => '#수제 #식품 #선물', 'tone' => '#fdf2f8'),
        ),
        'a3' => array(
            array('title' => 'A3 대형라벨', 'tags' => '#대형 #포스터 #안내', 'tone' => '#ede9fe'),
        ),
    ),
    'tag' => array(
        'a4' => array(
            array('title' => '플라워 태그', 'tags' => '#꽃 #선물 #감사', 'tone' => '#fce7f3'),
            array('title' => '베이커리 태그', 'tags' => '#베이커리 #카페 #선물', 'tone' => '#ffedd5'),
            array('title' => '친환경 태그', 'tags' => '#친환경 #감사 #구매', 'tone' => '#dcfce7'),
            array('title' => '이벤트 태그', 'tags' => '#이벤트 #세일 #프로모션', 'tone' => '#fee2e2'),
        ),
        'jet' => array(
            array('title' => '제트 행택', 'tags' => '#의류 #패션 #가격', 'tone' => '#f8fafc'),
            array('title' => '제트 선물태그', 'tags' => '#선물 #감사 #메시지', 'tone' => '#fef3c7'),
        ),
        'theroll' => array(
            array('title' => '더롤 리본태그', 'tags' => '#리본 #선물 #포장', 'tone' => '#fdf4ff'),
            array('title' => '더롤 감사태그', 'tags' => '#감사 #답례 #선물', 'tone' => '#fff1f2'),
        ),
    ),
);

$sbImportTemplateTags = array(
    array('#전체', 665, true),
    array('#감사', 336),
    array('#선물', 206),
    array('#인사', 170),
    array('#증정품', 137),
    array('#추석', 107),
    array('#구매', 98),
    array('#반가워', 85),
    array('#답례품', 83),
    array('#이벤트', 82),
    array('#여름', 75),
    array('#겨울', 68),
    array('#크리스마스', 61),
    array('#생일', 58),
    array('#카페', 52),
);

$sbImportTemplateItems = array(
    array('title' => '젖은 우산은 우산꽂이에 보관해주세요', 'tags' => '#안내 #카페', 'tone' => '#dbeafe'),
    array('title' => '한라봉청', 'tags' => '#식품 #선물', 'tone' => '#ffedd5'),
    array('title' => 'Green Choice - 친환경 제품', 'tags' => '#친환경 #구매', 'tone' => '#dcfce7'),
    array('title' => '지구를 위한 작은 실천 감사합니다', 'tags' => '#감사 #친환경', 'tone' => '#d1fae5'),
    array('title' => '926-증정용라벨', 'tags' => '#증정품 #여름', 'tone' => '#fce7f3'),
    array('title' => 'INCHEON KOREA', 'tags' => '#여행 #스탬프', 'tone' => '#e0e7ff'),
    array('title' => '961-증정품라벨', 'tags' => '#증정품 #여름', 'tone' => '#fef3c7'),
    array('title' => '634-세일라벨', 'tags' => '#이벤트 #세일', 'tone' => '#fee2e2'),
    array('title' => '823-미끄럼주의안내', 'tags' => '#안내 #주의', 'tone' => '#fef9c3'),
    array('title' => '536-네임스티커', 'tags' => '#카페 #이름', 'tone' => '#f3e8ff'),
    array('title' => '844-안내라벨', 'tags' => '#안내 #카페', 'tone' => '#cffafe'),
    array('title' => '520-감사라벨', 'tags' => '#감사 #선물', 'tone' => '#ffe4e6'),
    array('title' => '수리완료 검수확인', 'tags' => '#품질 #관리 #제품', 'tone' => '#fef3c7'),
    array('title' => 'OPEN 준비중', 'tags' => '#오픈 #카페 #안내', 'tone' => '#fff7ed'),
    array('title' => '여름 휴가중', 'tags' => '#여름 #휴가 #바다', 'tone' => '#dbeafe'),
    array('title' => '베이커리 감사카드', 'tags' => '#베이커리 #감사', 'tone' => '#fde68a'),
    array('title' => '플라워 태그', 'tags' => '#꽃 #선물', 'tone' => '#fbcfe8'),
    array('title' => '친환경 포장', 'tags' => '#친환경 #포장', 'tone' => '#bbf7d0'),
);

$sbImportMyDesigns = array(
    array('title' => '올리브오일 라벨', 'spec' => '100 · 93×93 mm', 'date' => '2026.03.12', 'tone' => '#dcfce7', 'badge' => '최근'),
    array('title' => '유기농 꿀 라벨', 'spec' => '105 · 25×25 mm', 'date' => '2026.02.28', 'tone' => '#fef3c7'),
    array('title' => '블루베리 수제청', 'spec' => '111 · 25×25 mm', 'date' => '2026.02.15', 'tone' => '#dbeafe'),
    array('title' => '카페 로고 스티커', 'spec' => '536 · 50×30 mm', 'date' => '2026.01.20', 'tone' => '#f3e8ff'),
    array('title' => '제주 감귤 증정품', 'spec' => '926 · 70×50 mm', 'date' => '2025.12.08', 'tone' => '#ffedd5'),
    array('title' => 'Green Choice 친환경', 'spec' => '102 · 57.5×48 mm', 'date' => '2025.11.22', 'tone' => '#d1fae5'),
);

/** 상세 사양 프리셋 (규격코드별) */
$sbImportSpecPresets = array(
    'TLF0061' => array(
        'paperW' => 210, 'paperH' => 297,
        'labelW' => 86.5, 'labelH' => 86.5,
        'cols' => 3, 'rows' => 2,
        'gapH' => 9.68, 'gapV' => 10,
        'marginH' => 13.5, 'marginT' => 8.5,
        'orientation' => 'portrait', 'rotation' => 45,
    ),
    'TLF0021' => array(
        'paperW' => 210, 'paperH' => 297,
        'labelW' => 95, 'labelH' => 130,
        'cols' => 1, 'rows' => 2,
        'gapH' => 0, 'gapV' => 12,
        'marginH' => 57.5, 'marginT' => 14,
        'orientation' => 'portrait', 'rotation' => 0,
    ),
    'TLF0101' => array(
        'paperW' => 210, 'paperH' => 297,
        'labelW' => 86.5, 'labelH' => 86.5,
        'cols' => 3, 'rows' => 2,
        'gapH' => 9.68, 'gapV' => 10,
        'marginH' => 13.5, 'marginT' => 8.5,
        'orientation' => 'portrait', 'rotation' => 45,
    ),
);

/**
 * @param array  $item
 * @param string $catId
 * @param string $kind label|tag
 * @return array
 */
if (!function_exists('sb_import_build_spec_detail')) {
function sb_import_build_spec_detail($item, $catId, $kind)
{
    global $sbImportSpecPresets;

    $code = isset($item['code']) ? $item['code'] : '';
    if ($code !== '' && isset($sbImportSpecPresets[$code])) {
        $detail = $sbImportSpecPresets[$code];
        $detail['code'] = $code;
        $detail['kind'] = $kind;
        $detail['cat'] = $catId;
        $detail['qty'] = isset($item['qty']) ? (int) $item['qty'] : ($detail['cols'] * $detail['rows']);
        if (isset($item['type'])) {
            $detail['tagType'] = $item['type'];
        }
        return $detail;
    }

    $labelW = 25.0;
    $labelH = 25.0;
    if (!empty($item['size']) && preg_match('/([\d.]+)\s*[×x]\s*([\d.]+)/u', $item['size'], $m)) {
        $labelW = (float) $m[1];
        $labelH = (float) $m[2];
    }

    $qty = isset($item['qty']) ? (int) $item['qty'] : 1;
    $paperW = 210.0;
    $paperH = 297.0;
    $marginH = 4.0;
    $marginT = 10.0;
    $gapH = 2.5;
    $gapV = 0.0;
    $rotation = 0;

    if ($catId === 'a3') {
        $paperW = 297.0;
        $paperH = 420.0;
    } elseif ($catId === 'jet') {
        $paperW = 100.0;
        $paperH = 150.0;
        $marginH = 5.0;
        $marginT = 8.0;
        $gapH = 2.0;
        $gapV = 2.0;
    } elseif ($catId === 'theroll') {
        $paperW = 80.0;
        $paperH = 200.0;
        $marginH = 4.0;
        $marginT = 6.0;
        $gapH = 2.0;
        $gapV = 3.0;
    }

    if ($kind === 'tag') {
        $marginH = 13.5;
        $marginT = 8.5;
        $gapH = 9.68;
        $gapV = 10.0;
        if ($labelW > 80) {
            $labelW = 86.5;
            $labelH = 86.5;
        }
    }

    $cols = 1;
    $rows = max(1, $qty);
    for ($c = 1; $c <= (int) ceil(sqrt($qty)); $c++) {
        if ($qty % $c === 0) {
            $r = (int) ($qty / $c);
            if (abs($c / $r - 1) < abs($cols / $rows - 1) || ($cols === 1 && $rows === $qty)) {
                $cols = $c;
                $rows = $r;
            }
        }
    }

    $detail = array(
        'code' => $code,
        'kind' => $kind,
        'cat' => $catId,
        'paperW' => $paperW,
        'paperH' => $paperH,
        'labelW' => $labelW,
        'labelH' => $labelH,
        'cols' => $cols,
        'rows' => $rows,
        'gapH' => $gapH,
        'gapV' => $gapV,
        'marginH' => $marginH,
        'marginT' => $marginT,
        'qty' => $qty,
        'orientation' => 'portrait',
        'rotation' => $rotation,
    );
    if (isset($item['type'])) {
        $detail['tagType'] = $item['type'];
    }
    if (isset($item['shape'])) {
        $detail['shape'] = $item['shape'];
    }

    return $detail;
}
}

/** JS lookup용 상세 사양 맵 */
if (!isset($sbImportSpecMap)) {
$sbImportSpecMap = array();
foreach ($sbImportBlankSpecs as $kind => $byCat) {
    foreach ($byCat as $catId => $items) {
        foreach ($items as $item) {
            if (empty($item['code'])) {
                continue;
            }
            $sbImportSpecMap[$item['code']] = sb_import_build_spec_detail($item, $catId, $kind);
        }
    }
}
}
