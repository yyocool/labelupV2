ALTER TABLE shop_orders
  ADD COLUMN payment_provider VARCHAR(30) NULL COMMENT 'toss' AFTER payment_status,
  ADD COLUMN payment_method VARCHAR(80) NULL COMMENT 'card/easy-pay' AFTER payment_provider,
  ADD COLUMN payment_key VARCHAR(191) NULL COMMENT 'toss paymentKey' AFTER payment_method,
  ADD COLUMN payment_payload TEXT NULL COMMENT 'confirm response summary' AFTER payment_key,
  ADD COLUMN paid_at DATETIME NULL AFTER payment_payload;

ALTER TABLE shop_orders
  ADD UNIQUE KEY uk_shop_orders_payment_key (payment_key);
