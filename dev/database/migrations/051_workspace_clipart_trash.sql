ALTER TABLE user_editor_workspaces
    ADD COLUMN trashed_at DATETIME NULL AFTER updated_at;

ALTER TABLE user_editor_workspaces
    ADD KEY idx_user_editor_workspaces_user_trash (user_id, trashed_at);

ALTER TABLE user_ai_cliparts
    ADD COLUMN trashed_at DATETIME NULL AFTER updated_at;

ALTER TABLE user_ai_cliparts
    ADD KEY idx_user_ai_cliparts_user_trash (user_id, trashed_at);
