-- 054 fix: scale remaining AI credit costs x10 (skipped by 053 comment bug)
UPDATE ai_credit_costs
SET credit_cost = credit_cost * 10
WHERE intent IN (
  'chat',
  'recommend_product',
  'ask_image_mode',
  'generate_clipart',
  'generate_template',
  'generate_data_template'
)
AND credit_cost > 0
AND credit_cost < 50;

INSERT IGNORE INTO ai_credit_costs
  (intent, label, credit_cost, description, is_active, sort_order, updated_at)
VALUES
  ('ask_translate', 'translate', 10, 'translate polish', 1, 35, NOW());
