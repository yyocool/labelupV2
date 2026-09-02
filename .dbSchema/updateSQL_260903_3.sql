CREATE TABLE IF NOT EXISTS ai_example_prompts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(40) NOT NULL,
    prompt_text VARCHAR(500) NOT NULL,
    surface VARCHAR(16) NOT NULL DEFAULT 'both',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_ai_example_prompts_active_sort (is_active, surface, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS ai_usage_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    surface VARCHAR(16) NOT NULL DEFAULT 'unknown',
    intent VARCHAR(32) NULL,
    model VARCHAR(80) NULL,
    prompt_tokens INT UNSIGNED NULL,
    completion_tokens INT UNSIGNED NULL,
    total_tokens INT UNSIGNED NULL,
    has_image TINYINT(1) NOT NULL DEFAULT 0,
    clipart_id BIGINT UNSIGNED NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'ok',
    error_message VARCHAR(255) NULL,
    created_at DATETIME NULL,
    KEY idx_ai_usage_created (created_at),
    KEY idx_ai_usage_user_created (user_id, created_at),
    KEY idx_ai_usage_intent (intent, created_at),
    KEY idx_ai_usage_surface (surface, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
