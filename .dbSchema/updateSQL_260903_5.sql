CREATE TABLE IF NOT EXISTS admin_favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    slot TINYINT UNSIGNED NOT NULL,
    menu_key VARCHAR(64) NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_admin_fav_slot (admin_user_id, slot),
    UNIQUE KEY uk_admin_fav_menu (admin_user_id, menu_key),
    KEY idx_admin_fav_user (admin_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS admin_alert_cursors (
    admin_user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    last_seen_order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    last_seen_inquiry_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS user_inquiries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    admin_memo TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_user_inquiries_status (status),
    KEY idx_user_inquiries_created (created_at),
    KEY idx_user_inquiries_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
