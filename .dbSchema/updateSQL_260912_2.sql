ALTER TABLE qr_coupon_codes
    ADD COLUMN printed_at DATETIME NULL AFTER used_by,
    ADD COLUMN print_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER printed_at,
    ADD KEY idx_qr_coupon_codes_printed (printed_at);
