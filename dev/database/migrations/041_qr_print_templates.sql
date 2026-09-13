CREATE TABLE IF NOT EXISTS qr_print_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(64) NOT NULL DEFAULT 'default',
    name VARCHAR(150) NOT NULL DEFAULT 'QR 출력템플릿',
    paper_json LONGTEXT NOT NULL,
    objects_json LONGTEXT NOT NULL,
    settings_json LONGTEXT NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_qr_print_templates_key (template_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
