-- 261006_2: 실서버 label_specs 자료 교정 (스키마 변경 없음, 행 값만 고친다)
--
-- 실서버와 개발서버 덤프를 맞대 보고 실서버 쪽이 틀린 것만 골랐다.
-- 개발서버가 틀린 항목(id=59 의 h_gap_mm 등)은 일부러 건드리지 않는다. 아래 주석 참고.

-- 1) A208 계열: 2열 × 8행 = 16칸 인데 행수가 4로 들어가 있다.
--    편집기가 "규격 칸수 불일치: A208 2열 × 4행 = 8칸 ≠ 16칸" 경고를 내던 원인.
--    높이 확인: 13.792 + 8 × 33.85 = 284.6mm <= 297mm (A4 안에 들어간다)
UPDATE label_specs
   SET rows_count = 8, updated_at = NOW()
 WHERE id IN (19, 54, 59)
   AND columns_count = 2
   AND rows_count = 4
   AND labels_per_sheet = 16;

-- 2) 정부문서화일 라벨 2SET/4SET (AD102 / AD104): 형태가 'rect' 로 들어가 있어
--    custom_path_svg 에 든 칼선 SVG 를 편집기가 아예 쓰지 않는다. '맞춤'이어야 한다.
--    두 행 모두 SVG 를 갖고 있다(2SET 1,390자 · 4SET 2,690자).
--    칼선 사이 고리를 편집 영역으로 쓰려면 'custom' 대신 'custom_donut' 으로 바꾼다.
UPDATE label_specs
   SET shape = 'custom', updated_at = NOW()
 WHERE id IN (78, 79)
   AND shape = 'rect'
   AND custom_path_svg IS NOT NULL
   AND custom_path_svg <> '';

-- 3) 4SET 칸수: 실서버 21칸 / 개발서버 20칸. 4SET 이므로 20이 맞다고 보고 맞춘다.
--    (아니라면 이 문장만 건너뛰면 된다)
UPDATE label_specs
   SET labels_per_sheet = 20, updated_at = NOW()
 WHERE id = 79
   AND labels_per_sheet = 21;

-- 4) 원형 라벨인데 형태가 'rect': 40x40mm 와 63.5x63.5mm 정사각 규격.
--    개발서버는 'round' 로 되어 있다.
UPDATE label_specs
   SET shape = 'round', updated_at = NOW()
 WHERE id IN (33, 35)
   AND shape = 'rect';


-- 손대지 않은 것들 -----------------------------------------------------------
--
-- id=59 h_gap_mm : 실서버 2.540 / 개발서버 9.144 → 실서버가 맞다.
--   개발서버 값으로는 5.09 + 2 × 99.06 + 9.144 = 212.4mm 로 A4(210mm)를 넘는다.
--   실서버 값은 5.09 + 2 × 99.06 + 2.54 = 205.75mm 로 들어간다.
--   오히려 개발서버를 고쳐야 한다.
--
-- id=63 44x70mm 16칸 (4열 × 4행, v_gap 8.5) : 4 × 70 + 3 × 8.5 = 305.5mm > 297mm
-- id=64 33x53mm 25칸 (5열 × 5행, h_gap 16.5, v_gap 10)
--        가로 5 × 33 + 4 × 16.5 = 231mm > 210mm
--        세로 5 × 53 + 4 × 10   = 305mm > 297mm
-- id=62 42x107mm 9칸 : columns_count·rows_count 가 비어 있고, 107mm 를 3행 쌓으면
--        321mm 로 A4 를 넘는다. 2행으로는 9칸이 정수로 나누어지지 않는다.
--   이 세 건은 실측값이 없으면 바로잡을 수 없다. 실제 용지 치수를 받아서 따로 고친다.
--   실서버·개발서버가 같은 값이므로 어제오늘 생긴 문제도 아니다.
--
-- label_color 대소문자('#ffffff' vs '#FFFFFF') : 편집기가 대소문자를 가리지 않으므로 그대로 둔다.
--
-- id 1~5, 13, 50 : 배치값이 비어 있지만 연결된 상품이 없어 편집기에 나오지 않는다.
