-- Morning (8 AM – 12 NN) and afternoon (1 PM – 5 PM) session times for DTR

ALTER TABLE daily_time_records
  ADD COLUMN morning_time_in TIME NULL AFTER time_out,
  ADD COLUMN morning_time_out TIME NULL AFTER morning_time_in,
  ADD COLUMN afternoon_time_in TIME NULL AFTER morning_time_out,
  ADD COLUMN afternoon_time_out TIME NULL AFTER afternoon_time_in;

UPDATE daily_time_records
SET morning_time_in = time_in,
    morning_time_out = time_out
WHERE morning_time_in IS NULL;

ALTER TABLE dtr_drafts
  ADD COLUMN morning_time_in VARCHAR(5) NULL AFTER time_out_locked,
  ADD COLUMN morning_time_out VARCHAR(5) NULL AFTER morning_time_in,
  ADD COLUMN afternoon_time_in VARCHAR(5) NULL AFTER morning_time_out,
  ADD COLUMN afternoon_time_out VARCHAR(5) NULL AFTER afternoon_time_in,
  ADD COLUMN morning_time_in_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER afternoon_time_out,
  ADD COLUMN morning_time_out_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER morning_time_in_locked,
  ADD COLUMN afternoon_time_in_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER morning_time_out_locked,
  ADD COLUMN afternoon_time_out_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER afternoon_time_in_locked;

UPDATE dtr_drafts
SET morning_time_in = time_in,
    morning_time_out = time_out,
    morning_time_in_locked = time_in_locked,
    morning_time_out_locked = time_out_locked
WHERE morning_time_in IS NULL AND (time_in IS NOT NULL OR time_in_locked = 1);
