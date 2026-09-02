ALTER TABLE user_ai_cliparts
    ADD COLUMN review_status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER file_name,
    ADD COLUMN review_note VARCHAR(500) NULL AFTER review_status,
    ADD COLUMN reviewed_at DATETIME NULL AFTER review_note,
    ADD COLUMN reviewed_by BIGINT UNSIGNED NULL AFTER reviewed_at,
    ADD KEY idx_user_ai_cliparts_status (review_status, id);
