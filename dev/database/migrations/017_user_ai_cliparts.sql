CREATE TABLE IF NOT EXISTS user_ai_cliparts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL DEFAULT '',
    prompt TEXT NULL,
    image_url VARCHAR(500) NOT NULL,
    file_name VARCHAR(180) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_user_ai_cliparts_user (user_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
