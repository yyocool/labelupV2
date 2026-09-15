CREATE TABLE IF NOT EXISTS qa_review_checks (
    item_key VARCHAR(100) NOT NULL,
    dev_ok TINYINT(1) NOT NULL DEFAULT 0,
    client_ok TINYINT(1) NOT NULL DEFAULT 0,
    note VARCHAR(500) NULL,
    updated_by INT UNSIGNED NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (item_key),
    KEY idx_qa_review_checks_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
