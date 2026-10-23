-- 중간배너 텍스트 공통 정렬(좌중우 / 상중하)
ALTER TABLE home_mid_banners
    ADD COLUMN text_align_h VARCHAR(10) NOT NULL DEFAULT 'left' AFTER sub_color,
    ADD COLUMN text_align_v VARCHAR(10) NOT NULL DEFAULT 'middle' AFTER text_align_h;
