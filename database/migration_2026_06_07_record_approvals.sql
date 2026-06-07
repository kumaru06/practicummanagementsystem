-- Partner approval workflow for student records
-- DTR and Weekly Reports must be approved by the Industry Partner
-- before they reflect in the Coordinator's view.

ALTER TABLE daily_time_records
  ADD COLUMN IF NOT EXISTS verification_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER tasks_done,
  ADD COLUMN IF NOT EXISTS verified_by INT NULL AFTER verification_status,
  ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL AFTER verified_by,
  ADD COLUMN IF NOT EXISTS verification_notes TEXT NULL AFTER verified_at;

-- weekly_reports already had verification_status with the OLD enum ('pending','verified','rejected')
-- We need to normalize it to ('pending','approved','rejected') so partner approvals persist correctly.
ALTER TABLE weekly_reports
  ADD COLUMN IF NOT EXISTS verification_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER file_path,
  ADD COLUMN IF NOT EXISTS verified_by INT NULL AFTER verification_status,
  ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL AFTER verified_by,
  ADD COLUMN IF NOT EXISTS verification_notes TEXT NULL AFTER verified_at;

-- Force-normalize the enum in case the column already existed with the legacy 'verified' value.
ALTER TABLE weekly_reports
  MODIFY COLUMN verification_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending';
