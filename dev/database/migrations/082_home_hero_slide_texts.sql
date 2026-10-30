ALTER TABLE home_hero_slides
    ADD COLUMN mobile_image_url VARCHAR(255) NULL AFTER image_url,
    ADD COLUMN main_text VARCHAR(500) NOT NULL DEFAULT '' AFTER alt_text,
    ADD COLUMN main_font VARCHAR(40) NOT NULL DEFAULT 'pretendard-extrabold' AFTER main_text,
    ADD COLUMN main_size SMALLINT UNSIGNED NOT NULL DEFAULT 44 AFTER main_font,
    ADD COLUMN main_size_mobile SMALLINT UNSIGNED NOT NULL DEFAULT 26 AFTER main_size,
    ADD COLUMN main_color VARCHAR(7) NOT NULL DEFAULT '#FFFFFF' AFTER main_size_mobile,
    ADD COLUMN sub_text VARCHAR(500) NOT NULL DEFAULT '' AFTER main_color,
    ADD COLUMN sub_font VARCHAR(40) NOT NULL DEFAULT 'pretendard-medium' AFTER sub_text,
    ADD COLUMN sub_size SMALLINT UNSIGNED NOT NULL DEFAULT 20 AFTER sub_font,
    ADD COLUMN sub_size_mobile SMALLINT UNSIGNED NOT NULL DEFAULT 14 AFTER sub_size,
    ADD COLUMN sub_color VARCHAR(7) NOT NULL DEFAULT '#FFFFFF' AFTER sub_size_mobile,
    ADD COLUMN text_align_h VARCHAR(10) NOT NULL DEFAULT 'left' AFTER sub_color,
    ADD COLUMN text_align_v VARCHAR(10) NOT NULL DEFAULT 'middle' AFTER text_align_h;
