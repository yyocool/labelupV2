ALTER TABLE label_specs
    ADD COLUMN paper_size VARCHAR(20) NULL COMMENT '용지 규격(A4/A3/A5 등)' AFTER height_mm,
    ADD COLUMN top_margin_mm DECIMAL(7,3) NULL COMMENT '위쪽 여백(mm)' AFTER labels_per_sheet,
    ADD COLUMN left_margin_mm DECIMAL(7,3) NULL COMMENT '왼쪽 여백(mm)' AFTER top_margin_mm,
    ADD COLUMN columns_count SMALLINT UNSIGNED NULL COMMENT '라벨 열수(가로 개수)' AFTER left_margin_mm,
    ADD COLUMN rows_count SMALLINT UNSIGNED NULL COMMENT '라벨 행수(세로 개수)' AFTER columns_count,
    ADD COLUMN h_gap_mm DECIMAL(7,3) NULL COMMENT '라벨 좌우 간격(mm)' AFTER rows_count,
    ADD COLUMN v_gap_mm DECIMAL(7,3) NULL COMMENT '라벨 상하 간격(mm)' AFTER h_gap_mm,
    ADD COLUMN corner_radius_x_mm DECIMAL(7,3) NULL COMMENT '모서리 가로 반경(mm)' AFTER v_gap_mm,
    ADD COLUMN corner_radius_y_mm DECIMAL(7,3) NULL COMMENT '모서리 세로 반경(mm)' AFTER corner_radius_x_mm,
    ADD COLUMN label_color VARCHAR(7) NULL COMMENT '라벨 바탕색(#RRGGBB)' AFTER corner_radius_y_mm,
    ADD COLUMN custom_path_svg MEDIUMTEXT NULL COMMENT '커스텀 외곽 Path(SVG path d 또는 svg 마크업)' AFTER label_color;
