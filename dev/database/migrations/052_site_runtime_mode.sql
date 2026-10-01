CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(80) NOT NULL,
    setting_value MEDIUMTEXT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO site_settings (setting_key, setting_value, updated_at) VALUES
('runtime_mode', 'production', NOW()),
('maintenance_title', '잠시 점검 중입니다', NOW()),
('maintenance_message', '더 안정적인 라벨업을 위해 시스템을 정비하고 있어요. 잠시 후 다시 찾아와 주세요.', NOW()),
('maintenance_eta', '', NOW());
