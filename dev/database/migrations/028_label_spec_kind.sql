ALTER TABLE label_specs
    ADD COLUMN kind ENUM('label','tag') NOT NULL DEFAULT 'label' AFTER name;

UPDATE label_specs
SET kind = 'tag'
WHERE kind = 'label'
  AND (
      name LIKE '%태그%' OR name LIKE '%tag%' OR name LIKE '%Tag%' OR name LIKE '%행택%'
      OR IFNULL(description, '') LIKE '%태그%' OR IFNULL(description, '') LIKE '%행택%'
  );
