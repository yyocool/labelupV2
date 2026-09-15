-- LabelUp 2026-09-14 #4
-- QA review request HTML (Summernote)

ALTER TABLE qa_review_checks
  ADD COLUMN request_html MEDIUMTEXT NULL AFTER note;
