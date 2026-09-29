-- 주소용 등 대표 규격: 열·행이 비어 편집기가 칸수를 잘못 추정하던 문제 보정
-- (미리보기 3열·칸수 +2 등)

UPDATE label_specs
SET columns_count = 2, rows_count = 8, h_gap_mm = COALESCE(h_gap_mm, 2.50), v_gap_mm = COALESCE(v_gap_mm, 0.00)
WHERE labels_per_sheet = 16
  AND ABS(width_mm - 99.06) < 0.2
  AND ABS(height_mm - 33.85) < 0.2
  AND (columns_count IS NULL OR rows_count IS NULL OR columns_count * rows_count <> labels_per_sheet);

UPDATE label_specs
SET columns_count = 2, rows_count = 9, h_gap_mm = COALESCE(h_gap_mm, 2.50), v_gap_mm = COALESCE(v_gap_mm, 0.00)
WHERE labels_per_sheet = 18
  AND ABS(width_mm - 100.00) < 0.2
  AND ABS(height_mm - 30.00) < 0.2
  AND (columns_count IS NULL OR rows_count IS NULL OR columns_count * rows_count <> labels_per_sheet);

UPDATE label_specs
SET columns_count = 3, rows_count = 6, h_gap_mm = COALESCE(h_gap_mm, 2.50), v_gap_mm = COALESCE(v_gap_mm, 0.00)
WHERE labels_per_sheet = 18
  AND ABS(width_mm - 63.50) < 0.2
  AND ABS(height_mm - 46.50) < 0.2
  AND (columns_count IS NULL OR rows_count IS NULL OR columns_count * rows_count <> labels_per_sheet);

UPDATE label_specs
SET columns_count = 2, rows_count = 7, h_gap_mm = COALESCE(h_gap_mm, 2.50), v_gap_mm = COALESCE(v_gap_mm, 0.00)
WHERE labels_per_sheet = 14
  AND ABS(width_mm - 99.06) < 0.2
  AND ABS(height_mm - 38.10) < 0.2
  AND (columns_count IS NULL OR rows_count IS NULL OR columns_count * rows_count <> labels_per_sheet);

UPDATE label_specs
SET columns_count = 2, rows_count = 6, h_gap_mm = COALESCE(h_gap_mm, 2.50), v_gap_mm = COALESCE(v_gap_mm, 0.00)
WHERE labels_per_sheet = 12
  AND ABS(width_mm - 100.00) < 0.2
  AND ABS(height_mm - 46.40) < 0.2
  AND (columns_count IS NULL OR rows_count IS NULL OR columns_count * rows_count <> labels_per_sheet);
