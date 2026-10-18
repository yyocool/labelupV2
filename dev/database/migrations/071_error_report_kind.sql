ALTER TABLE error_reports
  ADD COLUMN kind VARCHAR(20) NOT NULL DEFAULT 'error' AFTER user_id;
