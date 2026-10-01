-- 크레딧 단위 10배 상향: 원화:크레딧 = 1:10
-- (기존 1 C = 1원 → 1원 = 10 C)
-- 이 파일은 마이그레이션 테이블에 1회만 적용됩니다.

UPDATE ai_credit_costs
SET credit_cost = credit_cost * 10
WHERE credit_cost > 0;

UPDATE ai_credit_config
SET
  monthly_budget = monthly_budget * 10,
  low_balance_threshold = CASE
    WHEN low_balance_threshold <= 0 THEN 0
    ELSE GREATEST(low_balance_threshold * 10, 100)
  END;

UPDATE credit_reward_rules
SET credit_amount = credit_amount * 10
WHERE credit_amount <> 0;

UPDATE purchase_credit_products
SET credit_amount = credit_amount * 10
WHERE credit_amount > 0;

UPDATE qr_coupon_groups
SET credit_amount = credit_amount * 10
WHERE credit_amount IS NOT NULL AND credit_amount > 0;

UPDATE user_credits
SET balance = balance * 10;

UPDATE credit_transactions
SET
  amount = amount * 10,
  balance_after = balance_after * 10;

-- 환산 비율 기록 (관리·문서용)
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(80) NOT NULL,
    setting_value MEDIUMTEXT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO site_settings (setting_key, setting_value, updated_at) VALUES
('credit_krw_ratio', '10', NOW()),
('credit_ratio_note', '1 KRW = 10 C', NOW())
ON DUPLICATE KEY UPDATE
  setting_value = VALUES(setting_value),
  updated_at = VALUES(updated_at);

-- ask_translate 비용 시드(없을 때만)
INSERT IGNORE INTO ai_credit_costs
  (intent, label, credit_cost, description, is_active, sort_order, updated_at)
VALUES
  ('ask_translate', '번역·문구 다듬기', 10, '문구 번역·다듬기', 1, 35, NOW());
