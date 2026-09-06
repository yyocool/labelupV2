CREATE TABLE IF NOT EXISTS user_shipping_addresses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(40) NOT NULL DEFAULT '배송지',
    recipient_name VARCHAR(100) NOT NULL,
    recipient_phone VARCHAR(30) NOT NULL,
    zip VARCHAR(10) NOT NULL DEFAULT '',
    address_base VARCHAR(300) NOT NULL,
    address_detail VARCHAR(200) NOT NULL DEFAULT '',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_user_shipping_user (user_id),
    KEY idx_user_shipping_default (user_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
