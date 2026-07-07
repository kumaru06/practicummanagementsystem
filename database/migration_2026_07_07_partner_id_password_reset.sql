-- Partner ID for industry partners + password reset request workflow.

ALTER TABLE partner_companies
  ADD COLUMN IF NOT EXISTS partner_id VARCHAR(20) NULL AFTER id;

SET @has_uq_partner_id := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'partner_companies'
    AND index_name = 'uq_partner_companies_partner_id'
);

SET @partner_id_index_sql := IF(
  @has_uq_partner_id = 0,
  'ALTER TABLE partner_companies ADD UNIQUE KEY uq_partner_companies_partner_id (partner_id)',
  'SELECT 1'
);

PREPARE partner_id_index_stmt FROM @partner_id_index_sql;
EXECUTE partner_id_index_stmt;
DEALLOCATE PREPARE partner_id_index_stmt;

CREATE TABLE IF NOT EXISTS password_reset_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  role ENUM('student','coordinator','partner') NOT NULL,
  email VARCHAR(190) NOT NULL,
  identifier VARCHAR(60) NOT NULL,
  status ENUM('pending','approved','rejected','completed','expired') NOT NULL DEFAULT 'pending',
  reset_token VARCHAR(64) NULL,
  reset_expires_at DATETIME NULL,
  reviewed_by INT NULL,
  reviewed_at DATETIME NULL,
  decline_reason TEXT NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_password_reset_status (status),
  INDEX idx_password_reset_user (user_id),
  INDEX idx_password_reset_token (reset_token),
  CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_password_reset_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
