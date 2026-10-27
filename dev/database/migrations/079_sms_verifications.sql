CREATE TABLE IF NOT EXISTS sms_verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    purpose VARCHAR(20) NOT NULL DEFAULT 'signup',
    code_hash VARCHAR(255) NOT NULL,
    token_hash CHAR(64) NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    verified_at DATETIME NULL,
    consumed_at DATETIME NULL,
    request_ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL,
    KEY idx_sms_verifications_phone_created (phone, created_at),
    KEY idx_sms_verifications_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE user_profiles ADD INDEX idx_user_profiles_phone (phone);
