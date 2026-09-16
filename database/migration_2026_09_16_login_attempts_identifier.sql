-- Import this migration into the currently selected database.
-- Adds per-account login throttle support previously added at runtime.
-- Safe to run more than once on MariaDB 10.3.3+.

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    identifier VARCHAR(190) NULL,
    successful TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_ip (ip_address, attempted_at),
    INDEX idx_login_attempts_identifier (identifier, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE login_attempts
  ADD COLUMN IF NOT EXISTS identifier VARCHAR(190) NULL AFTER ip_address;
