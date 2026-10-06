CREATE TABLE IF NOT EXISTS partners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(120) NOT NULL,
    login_id VARCHAR(60) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    contact_name VARCHAR(80) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    memo VARCHAR(500) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uk_partners_login_id (login_id),
    KEY idx_partners_status (status),
    KEY idx_partners_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
