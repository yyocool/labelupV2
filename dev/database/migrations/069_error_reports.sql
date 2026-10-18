CREATE TABLE IF NOT EXISTS error_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    category VARCHAR(30) NOT NULL DEFAULT 'site',
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    page_url VARCHAR(500) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_error_reports_status (status),
    KEY idx_error_reports_user (user_id),
    KEY idx_error_reports_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS error_report_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NULL,
    status VARCHAR(20) NOT NULL,
    result_note TEXT NULL,
    reward_credits INT NOT NULL DEFAULT 0,
    credit_tx_id BIGINT UNSIGNED NULL,
    created_at DATETIME NULL,
    KEY idx_error_report_actions_report (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO admin_menu_permissions (admin_user_id, menu_key, created_at)
SELECT s.admin_user_id, 'ops-error-reports', NOW()
FROM admin_menu_permissions s
LEFT JOIN admin_menu_permissions e
  ON e.admin_user_id = s.admin_user_id AND e.menu_key = 'ops-error-reports'
WHERE s.menu_key = 'ops-inquiries' AND e.admin_user_id IS NULL;
