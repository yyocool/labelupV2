INSERT INTO site_settings (setting_key, setting_value, updated_at)
VALUES ('signup_sms_enabled', '1', NOW())
ON DUPLICATE KEY UPDATE setting_key = setting_key;
