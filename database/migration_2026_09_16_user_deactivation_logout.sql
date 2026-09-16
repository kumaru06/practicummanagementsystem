-- Import this migration into the currently selected database.
-- Formalizes columns previously added at runtime by User::ensure* helpers so that
-- no ALTER TABLE runs on live during normal requests. Safe to run more than once.

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS deactivation_reason VARCHAR(40) NULL AFTER password_changed,
  ADD COLUMN IF NOT EXISTS deactivation_notes TEXT NULL AFTER deactivation_reason,
  ADD COLUMN IF NOT EXISTS deactivated_at DATETIME NULL AFTER deactivation_notes,
  ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL AFTER password_changed,
  ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45) NULL AFTER last_login_at,
  ADD COLUMN IF NOT EXISTS last_login_device VARCHAR(190) NULL AFTER last_login_ip,
  ADD COLUMN IF NOT EXISTS last_logout_at DATETIME NULL AFTER last_login_device,
  ADD COLUMN IF NOT EXISTS last_logout_ip VARCHAR(45) NULL AFTER last_logout_at,
  ADD COLUMN IF NOT EXISTS last_logout_device VARCHAR(190) NULL AFTER last_logout_ip;
