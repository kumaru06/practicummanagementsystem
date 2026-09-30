-- Allow Holiday as a DTR day type. Zero hours, no attendance times.
ALTER TABLE daily_time_records
  MODIFY COLUMN day_type ENUM('full','half_am','half_pm','sick','absent','holiday') NOT NULL DEFAULT 'full';
