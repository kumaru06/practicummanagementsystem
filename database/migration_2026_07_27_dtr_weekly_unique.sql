-- Import this migration into the currently selected database.
-- Enforces one DTR per student per day, and one weekly report per student per
-- week, at the DATABASE level. Previously this was only guarded in PHP with a
-- check-then-insert, which is racy under a double-submit / double-click and let
-- duplicate rows through (inflating rendered hours).
--
-- Existing duplicates are removed FIRST, keeping the earliest (lowest id) row
-- of each group. Review your data if a later duplicate held the "approved" copy.

-- 1) De-duplicate daily_time_records (keep the earliest row per student + date).
DELETE d1 FROM daily_time_records d1
INNER JOIN daily_time_records d2
  ON d1.student_id = d2.student_id
 AND d1.work_date  = d2.work_date
 AND d1.id > d2.id;

-- 2) De-duplicate weekly_reports (keep the earliest row per student + week).
DELETE w1 FROM weekly_reports w1
INNER JOIN weekly_reports w2
  ON w1.student_id = w2.student_id
 AND w1.week_no    = w2.week_no
 AND w1.id > w2.id;

-- 3) Add the UNIQUE key on daily_time_records (idempotent).
SET @has_uq_dtr := (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'daily_time_records'
    AND index_name = 'uq_dtr_student_date'
);
SET @dtr_sql := IF(
  @has_uq_dtr = 0,
  'ALTER TABLE daily_time_records ADD UNIQUE KEY uq_dtr_student_date (student_id, work_date)',
  'SELECT 1'
);
PREPARE dtr_stmt FROM @dtr_sql;
EXECUTE dtr_stmt;
DEALLOCATE PREPARE dtr_stmt;

-- 4) Add the UNIQUE key on weekly_reports (idempotent).
SET @has_uq_weekly := (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'weekly_reports'
    AND index_name = 'uq_weekly_student_week'
);
SET @weekly_sql := IF(
  @has_uq_weekly = 0,
  'ALTER TABLE weekly_reports ADD UNIQUE KEY uq_weekly_student_week (student_id, week_no)',
  'SELECT 1'
);
PREPARE weekly_stmt FROM @weekly_sql;
EXECUTE weekly_stmt;
DEALLOCATE PREPARE weekly_stmt;
