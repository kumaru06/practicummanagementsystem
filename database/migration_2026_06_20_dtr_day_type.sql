-- DTR day type: full, half_am, half_pm, sick, absent
ALTER TABLE daily_time_records
  ADD COLUMN IF NOT EXISTS day_type ENUM('full','half_am','half_pm','sick','absent') NOT NULL DEFAULT 'full' AFTER work_date;

ALTER TABLE dtr_drafts
  ADD COLUMN IF NOT EXISTS day_type VARCHAR(20) NOT NULL DEFAULT 'full' AFTER work_date;
