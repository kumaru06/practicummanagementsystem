-- Import this migration into the currently selected database.

ALTER TABLE coordinators
  ADD COLUMN IF NOT EXISTS id_number VARCHAR(60) NULL AFTER user_id;

SET @has_uq_coordinators_id_number := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'coordinators'
    AND index_name = 'uq_coordinators_id_number'
);

SET @coordinator_id_number_sql := IF(
  @has_uq_coordinators_id_number = 0,
  'ALTER TABLE coordinators ADD UNIQUE KEY uq_coordinators_id_number (id_number)',
  'SELECT 1'
);

PREPARE coordinator_id_number_stmt FROM @coordinator_id_number_sql;
EXECUTE coordinator_id_number_stmt;
DEALLOCATE PREPARE coordinator_id_number_stmt;