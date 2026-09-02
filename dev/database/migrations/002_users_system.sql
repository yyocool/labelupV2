-- Phase 2: 회원 시스템 (MySQL 5.5 compatible)

CREATE TABLE IF NOT EXISTS `users` (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('member','admin') NOT NULL DEFAULT 'member',
    status ENUM('active','inactive','withdrawn') NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uk_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS user_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL DEFAULT '',
    phone VARCHAR(30) NULL,
    company VARCHAR(150) NULL,
    avatar VARCHAR(255) NULL,
    locale VARCHAR(10) NOT NULL DEFAULT 'ko',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uk_user_profiles_user_id (user_id),
    KEY idx_user_profiles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS user_login_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    email VARCHAR(190) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    message VARCHAR(255) NULL,
    created_at DATETIME NULL,
    KEY idx_login_logs_user_id (user_id),
    KEY idx_login_logs_email (email),
    KEY idx_login_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS user_oauth_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider ENUM('kakao','naver','google') NOT NULL,
    provider_user_id VARCHAR(190) NOT NULL,
    provider_email VARCHAR(190) NULL,
    access_token TEXT NULL,
    refresh_token TEXT NULL,
    token_expires_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uk_oauth_provider_user (provider, provider_user_id),
    KEY idx_oauth_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS user_remember_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NULL,
    UNIQUE KEY uk_remember_token_hash (token_hash),
    KEY idx_remember_user_id (user_id),
    KEY idx_remember_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
