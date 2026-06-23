ALTER TABLE weekly_reports
  ADD COLUMN IF NOT EXISTS accomplishments TEXT NULL AFTER report_text,
  ADD COLUMN IF NOT EXISTS date_covered_start DATE NULL AFTER week_no,
  ADD COLUMN IF NOT EXISTS date_covered_end DATE NULL AFTER date_covered_start;

CREATE TABLE IF NOT EXISTS weekly_report_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  weekly_report_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_type VARCHAR(20) NOT NULL,
  file_size INT NOT NULL DEFAULT 0,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wrf_report FOREIGN KEY (weekly_report_id) REFERENCES weekly_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB;
