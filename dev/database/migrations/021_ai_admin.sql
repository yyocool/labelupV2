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

INSERT INTO ai_example_prompts (label, prompt_text, surface, sort_order, is_active, created_at, updated_at)
SELECT '☆ 라벨 추천', '용도에 맞는 라벨 상품을 하나 추천해줘.', 'both', 10, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ai_example_prompts WHERE prompt_text = '용도에 맞는 라벨 상품을 하나 추천해줘.');

INSERT INTO ai_example_prompts (label, prompt_text, surface, sort_order, is_active, created_at, updated_at)
SELECT '◎ 주소 라벨', '주소 라벨용 용지를 추천해줘.', 'both', 20, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ai_example_prompts WHERE prompt_text = '주소 라벨용 용지를 추천해줘.');

INSERT INTO ai_example_prompts (label, prompt_text, surface, sort_order, is_active, created_at, updated_at)
SELECT '▦ 바코드', '바코드 라벨 상품을 추천해줘.', 'both', 30, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ai_example_prompts WHERE prompt_text = '바코드 라벨 상품을 추천해줘.');

INSERT INTO ai_example_prompts (label, prompt_text, surface, sort_order, is_active, created_at, updated_at)
SELECT '○ 원형 스티커', '원형 네임 스티커 용지를 추천해줘.', 'both', 40, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ai_example_prompts WHERE prompt_text = '원형 네임 스티커 용지를 추천해줘.');

INSERT INTO ai_example_prompts (label, prompt_text, surface, sort_order, is_active, created_at, updated_at)
SELECT '◇ 가격표', '가격표 라벨 상품을 추천해줘.', 'both', 50, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ai_example_prompts WHERE prompt_text = '가격표 라벨 상품을 추천해줘.');

INSERT INTO ai_example_prompts (label, prompt_text, surface, sort_order, is_active, created_at, updated_at)
SELECT '✦ 클립아트', '카페 원두 라벨에 넣을 커피콩 클립아트를 그려줘.', 'both', 60, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ai_example_prompts WHERE prompt_text = '카페 원두 라벨에 넣을 커피콩 클립아트를 그려줘.');

INSERT INTO ai_example_prompts (label, prompt_text, surface, sort_order, is_active, created_at, updated_at)
SELECT '♡ 일러스트', '핸드메이드 라벨용 하트와 리본 일러스트를 그려줘.', 'both', 70, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ai_example_prompts WHERE prompt_text = '핸드메이드 라벨용 하트와 리본 일러스트를 그려줘.');

INSERT INTO ai_example_prompts (label, prompt_text, surface, sort_order, is_active, created_at, updated_at)
SELECT '◆ 식품 문구', '식품 원산지·유통기한 라벨에 넣을 문구를 추천해줘.', 'both', 80, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ai_example_prompts WHERE prompt_text = '식품 원산지·유통기한 라벨에 넣을 문구를 추천해줘.');

INSERT INTO ai_example_prompts (label, prompt_text, surface, sort_order, is_active, created_at, updated_at)
SELECT '◐ 화장품', '화장품 성분표 라벨을 만들고 싶어.', 'both', 90, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ai_example_prompts WHERE prompt_text = '화장품 성분표 라벨을 만들고 싶어.');

INSERT INTO ai_example_prompts (label, prompt_text, surface, sort_order, is_active, created_at, updated_at)
SELECT '▣ 배송주의', '배송용 취급주의 라벨 디자인을 알려줘.', 'both', 100, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ai_example_prompts WHERE prompt_text = '배송용 취급주의 라벨 디자인을 알려줘.');

INSERT INTO ai_example_prompts (label, prompt_text, surface, sort_order, is_active, created_at, updated_at)
SELECT '✿ 잎사귀', '로고 옆에 둘 심플한 잎사귀 일러스트를 그려줘.', 'editor', 110, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ai_example_prompts WHERE prompt_text = '로고 옆에 둘 심플한 잎사귀 일러스트를 그려줘.');
