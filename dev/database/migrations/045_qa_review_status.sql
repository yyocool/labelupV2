ALTER TABLE qa_review_checks
  ADD COLUMN dev_status VARCHAR(20) NOT NULL DEFAULT '' AFTER item_key,
  ADD COLUMN client_status VARCHAR(20) NOT NULL DEFAULT '' AFTER dev_status;

UPDATE qa_review_checks
SET
  dev_status = CASE WHEN dev_ok = 1 THEN 'done' ELSE '' END,
  client_status = CASE WHEN client_ok = 1 THEN 'done' ELSE '' END;

ALTER TABLE qa_review_checks
  DROP COLUMN dev_ok,
  DROP COLUMN client_ok;
