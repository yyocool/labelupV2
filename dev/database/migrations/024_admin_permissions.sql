ALTER TABLE users
    ADD COLUMN is_super_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER role;

CREATE TABLE IF NOT EXISTS admin_menu_permissions (
    admin_user_id BIGINT UNSIGNED NOT NULL,
    menu_key VARCHAR(64) NOT NULL,
    created_at DATETIME NULL,
    PRIMARY KEY (admin_user_id, menu_key),
    KEY idx_admin_menu_perm_key (menu_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

UPDATE users
SET is_super_admin = 1
WHERE role = 'admin' AND deleted_at IS NULL;
