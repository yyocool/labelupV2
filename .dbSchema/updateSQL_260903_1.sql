ALTER TABLE user_editor_workspaces
    ADD COLUMN preview_path VARCHAR(500) NULL AFTER ui_json;

ALTER TABLE user_editor_workspaces
    DROP INDEX uk_user_editor_workspace_user;

ALTER TABLE user_editor_workspaces
    ADD KEY idx_user_editor_workspaces_user_updated (user_id, updated_at);
