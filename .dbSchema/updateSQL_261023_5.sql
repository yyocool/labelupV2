-- 중간배너 메인/서브 텍스트 및 폰트·크기·색상
ALTER TABLE home_mid_banners
    ADD COLUMN main_text VARCHAR(500) NOT NULL DEFAULT '' AFTER alt_text,
    ADD COLUMN main_font VARCHAR(40) NOT NULL DEFAULT 'pretendard-extrabold' AFTER main_text,
    ADD COLUMN main_size SMALLINT UNSIGNED NOT NULL DEFAULT 50 AFTER main_font,
    ADD COLUMN main_color VARCHAR(7) NOT NULL DEFAULT '#FFFFFF' AFTER main_size,
    ADD COLUMN sub_text VARCHAR(500) NOT NULL DEFAULT '' AFTER main_color,
    ADD COLUMN sub_font VARCHAR(40) NOT NULL DEFAULT 'pretendard-regular' AFTER sub_text,
    ADD COLUMN sub_size SMALLINT UNSIGNED NOT NULL DEFAULT 25 AFTER sub_font,
    ADD COLUMN sub_color VARCHAR(7) NOT NULL DEFAULT '#FFFFFF' AFTER sub_size;
