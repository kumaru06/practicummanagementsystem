-- Student registration email verification (12h link expiry)
-- Compatible with MySQL 5.7 / MariaDB (no ADD COLUMN IF NOT EXISTS).
-- Safe to re-run: skip any statement that errors with "Duplicate column name".

ALTER TABLE student_registration_requests
  MODIFY COLUMN status ENUM('pending_verification','pending_approval','pending','approved','rejected')
  NOT NULL DEFAULT 'pending_verification';

ALTER TABLE student_registration_requests
  ADD COLUMN verification_token VARCHAR(64) NULL AFTER cor_file;

ALTER TABLE student_registration_requests
  ADD COLUMN verification_expires_at DATETIME NULL AFTER verification_token;

ALTER TABLE student_registration_requests
  ADD COLUMN email_verified_at DATETIME NULL AFTER verification_expires_at;

ALTER TABLE student_registration_requests
  ADD COLUMN user_id INT NULL AFTER email_verified_at;

ALTER TABLE student_registration_requests
  ADD INDEX idx_reg_verification_token (verification_token);

ALTER TABLE student_registration_requests
  ADD INDEX idx_reg_user_id (user_id);

-- Legacy rows submitted before email verification existed.
UPDATE student_registration_requests
SET status = 'pending_approval'
WHERE status = 'pending';
