ALTER TABLE ai_usage_logs
    ADD COLUMN cost_usd DECIMAL(12,6) NULL AFTER total_tokens,
    ADD COLUMN cost_krw INT UNSIGNED NULL AFTER cost_usd,
    ADD COLUMN agent VARCHAR(32) NULL AFTER intent,
    ADD COLUMN difficulty VARCHAR(16) NULL AFTER agent;
