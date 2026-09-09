ALTER TABLE credit_transactions
  MODIFY COLUMN source ENUM('reward','purchase_code','admin','order','system','ai') NOT NULL DEFAULT 'system';

CREATE TABLE IF NOT EXISTS ai_credit_config (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    monthly_budget INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0이면 한도 표시 없음',
    low_balance_threshold INT UNSIGNED NOT NULL DEFAULT 10,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_credit_costs (
    intent VARCHAR(64) NOT NULL PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    credit_cost INT UNSIGNED NOT NULL DEFAULT 1,
    description VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ai_credit_config (id, is_enabled, monthly_budget, low_balance_threshold, updated_at)
VALUES (1, 1, 0, 10, NOW())
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO ai_credit_costs (intent, label, credit_cost, description, is_active, sort_order, updated_at) VALUES
('chat', '일반 대화', 1, '라비 AI 일반 채팅/질문', 1, 10, NOW()),
('recommend_product', '상품 추천', 2, '라벨지·상품 추천', 1, 20, NOW()),
('ask_image_mode', '이미지 모드 안내', 1, '이미지 첨부 후 모드 선택 안내', 1, 30, NOW()),
('generate_clipart', '클립아트 생성', 15, 'AI 클립아트/일러스트 생성', 1, 40, NOW()),
('generate_template', '템플릿 생성', 10, '라벨 템플릿 초안 생성', 1, 50, NOW()),
('generate_data_template', '데이터 라벨 생성', 20, '엑셀·문서 기반 데이터 라벨 생성', 1, 60, NOW())
ON DUPLICATE KEY UPDATE label = VALUES(label);
