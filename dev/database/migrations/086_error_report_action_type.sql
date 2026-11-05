ALTER TABLE error_report_actions
    ADD COLUMN action_type VARCHAR(20) NOT NULL DEFAULT 'result' AFTER admin_id,
    ADD COLUMN status_from VARCHAR(20) NULL AFTER action_type;
