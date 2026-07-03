-- Add separate first/last name columns to users (MySQL 5.7 compatible)
ALTER TABLE users
  ADD COLUMN first_name VARCHAR(100) NULL AFTER name;

ALTER TABLE users
  ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name;

-- Migrate existing full names: last word = last_name, remainder = first_name
UPDATE users
SET
  last_name = TRIM(SUBSTRING_INDEX(name, ' ', -1)),
  first_name = TRIM(
    CASE
      WHEN LOCATE(' ', TRIM(name)) > 0 THEN SUBSTRING(name, 1, LENGTH(name) - LENGTH(SUBSTRING_INDEX(name, ' ', -1)) - 1)
      ELSE TRIM(name)
    END
  )
WHERE (first_name IS NULL OR first_name = '')
   OR (last_name IS NULL OR last_name = '');

-- Single-word names: use same value for both fields
UPDATE users
SET last_name = first_name
WHERE TRIM(COALESCE(first_name, '')) <> ''
  AND TRIM(COALESCE(last_name, '')) = '';

-- Keep name synced as full display name
UPDATE users
SET name = TRIM(CONCAT(first_name, ' ', last_name))
WHERE first_name IS NOT NULL AND last_name IS NOT NULL;
