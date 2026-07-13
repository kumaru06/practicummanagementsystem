-- Store IP address and device label for latest successful login
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45) NULL AFTER last_login_at,
  ADD COLUMN IF NOT EXISTS last_login_device VARCHAR(190) NULL AFTER last_login_ip;
