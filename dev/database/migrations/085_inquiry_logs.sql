CREATE TABLE IF NOT EXISTS user_inquiry_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inquiry_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NULL,
    admin_name VARCHAR(100) NOT NULL DEFAULT '',
    action VARCHAR(20) NOT NULL DEFAULT 'note',
    status_from VARCHAR(20) NULL,
    status_to VARCHAR(20) NULL,
    note TEXT NULL,
    created_at DATETIME NULL,
    KEY idx_user_inquiry_logs_inquiry (inquiry_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO user_inquiry_logs (inquiry_id, admin_name, action, status_to, note, created_at)
SELECT i.id, '', 'answer', 'answered', i.answer, COALESCE(i.answered_at, i.updated_at)
FROM user_inquiries i
WHERE i.answer IS NOT NULL AND i.answer <> ''
  AND NOT EXISTS (SELECT 1 FROM user_inquiry_logs l WHERE l.inquiry_id = i.id);
