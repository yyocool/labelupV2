CREATE TABLE IF NOT EXISTS user_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(40) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body VARCHAR(500) NOT NULL DEFAULT '',
    link_url VARCHAR(400) NOT NULL DEFAULT '',
    ref_type VARCHAR(40) NOT NULL DEFAULT '',
    ref_id VARCHAR(64) NOT NULL DEFAULT '',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    KEY idx_user_notif_user_read (user_id, is_read, id),
    KEY idx_user_notif_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS user_notification_prefs (
    user_id BIGINT UNSIGNED NOT NULL,
    pref_welcome TINYINT(1) NOT NULL DEFAULT 1,
    pref_credit_low TINYINT(1) NOT NULL DEFAULT 1,
    pref_credit_change TINYINT(1) NOT NULL DEFAULT 1,
    pref_order_placed TINYINT(1) NOT NULL DEFAULT 1,
    pref_order_status TINYINT(1) NOT NULL DEFAULT 1,
    pref_system TINYINT(1) NOT NULL DEFAULT 1,
    low_credit_threshold INT NOT NULL DEFAULT 100,
    updated_at DATETIME NULL,
    PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
