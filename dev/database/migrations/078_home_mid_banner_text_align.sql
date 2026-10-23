ALTER TABLE home_mid_banners
    ADD COLUMN text_align_h VARCHAR(10) NOT NULL DEFAULT 'left' AFTER sub_color,
    ADD COLUMN text_align_v VARCHAR(10) NOT NULL DEFAULT 'middle' AFTER text_align_h;
