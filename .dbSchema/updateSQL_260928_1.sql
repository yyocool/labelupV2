-- updateSQL_260928_1.sql
-- 행·열(columns_count/rows_count)이 비어 있던 규격을 채운다.
-- 값은 새로 만들지 않고, 서버 DB에 이미 들어 있는 "같은 치수·다른 재질" 형제 규격에서 그대로 복사한다.
--   updateSQL_260923_2.sql 는 (width, height, material, labels_per_sheet) 로 매칭했기 때문에
--   재질명이 다르거나(광택백색·반투명) 칸수가 NULL 인 규격이 매칭에서 빠져 비어 있었다.
-- 스키마 변경 없음. 데이터만 갱신한다.
--
-- id 13  199.9x289.05mm 백색        <- id 6  199.9x289.05mm 백색 1칸   (A101-200 상품용, 칸수가 NULL 이라 매칭 실패)
-- id 48  99.06x33.85mm 광택백색 16칸 <- id 19 99.06x33.85mm 백색 16칸   (LG208-100)
-- id 50  38x19.2mm 반투명 60칸       <- id 28 38x19.2mm 백색 60칸       (LW512-10)
-- id 54  99.06x33.85mm 반투명 16칸   : 칸수 16 인데 2x4=8 로 어긋나 있어 형제(id 19)와 같은 2x8 로 바로잡는다.
--
-- [미반영] id 62 42x107mm 투명 9칸(PC303-10)
--   형제 규격이 없고, 저장된 간격(15.5mm)으로는 A4 에 9칸이 물리적으로 들어가지 않는다.
--   3열x3행이면 세로로 3*107+2*15.5 = 352mm 라 297mm 를 넘는다. 실측값이 필요하다.

-- 199.9x289.05mm 백색 (A4 전면 1칸)
UPDATE label_specs
SET paper_size = 'A4',
    labels_per_sheet = 1,
    columns_count = 1,
    rows_count = 1,
    top_margin_mm = 4.699,
    left_margin_mm = 5.400,
    h_gap_mm = 0.000,
    v_gap_mm = 0.000,
    updated_at = NOW()
WHERE id = 13 AND width_mm = 199.90 AND height_mm = 289.05;

-- 99.06x33.85mm 광택백색 16칸 (2열 x 8행)
UPDATE label_specs
SET paper_size = 'A4',
    columns_count = 2,
    rows_count = 8,
    top_margin_mm = 13.792,
    left_margin_mm = 5.086,
    h_gap_mm = 2.540,
    v_gap_mm = 0.000,
    updated_at = NOW()
WHERE id = 48 AND width_mm = 99.06 AND height_mm = 33.85;

-- 38x19.2mm 반투명 60칸 (5열 x 12행)
UPDATE label_specs
SET paper_size = 'A4',
    columns_count = 5,
    rows_count = 12,
    top_margin_mm = 23.500,
    left_margin_mm = 6.000,
    h_gap_mm = 1.900,
    v_gap_mm = 1.778,
    updated_at = NOW()
WHERE id = 50 AND width_mm = 38.00 AND height_mm = 19.20;

-- 99.06x33.85mm 반투명 16칸 : 행수 4 -> 8 (2x8 = 16칸)
UPDATE label_specs
SET rows_count = 8,
    updated_at = NOW()
WHERE id = 54 AND width_mm = 99.06 AND height_mm = 33.85 AND labels_per_sheet = 16;

-- 확인용
SELECT id, name, paper_size, labels_per_sheet, columns_count, rows_count,
       top_margin_mm, left_margin_mm, h_gap_mm, v_gap_mm
FROM label_specs
WHERE id IN (13, 48, 50, 54);
