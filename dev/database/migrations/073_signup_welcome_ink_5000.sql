INSERT INTO credit_reward_rules
    (code, name, description, credit_amount, trigger_type, daily_limit, max_total_per_user, is_active, sort_order, created_at, updated_at)
VALUES
    ('SIGNUP_WELCOME', '회원가입 웰컴 잉크', '신규 가입 시 1회 지급', 5000, 'signup', NULL, 1, 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    credit_amount = 5000,
    trigger_type = 'signup',
    max_total_per_user = 1,
    is_active = 1,
    updated_at = NOW();
