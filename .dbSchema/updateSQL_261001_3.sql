-- 054 fix: scale remaining AI credit costs x10
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
  ('ask_translate', '번역·문구 다듬기', 10, '문구 번역·다듬기', 1, 35, NOW());
