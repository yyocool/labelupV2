-- updateSQL_260902_1.sql
-- 로그인 사용자 편집기 작업공간(문서·UI 레이아웃) 저장

CREATE TABLE IF NOT EXISTS user_editor_workspaces (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL DEFAULT '',
    document_json LONGTEXT NOT NULL,
    ui_json MEDIUMTEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_user_editor_workspace_user (user_id),
    KEY idx_user_editor_workspaces_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
