CREATE TABLE IF NOT EXISTS evaluations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  enrollment_id INT NOT NULL UNIQUE,
  company_id INT NOT NULL,
  rating TINYINT NOT NULL,
  criteria_ratings TEXT NULL,
  final_grade DECIMAL(5,2) NULL,
  comments TEXT NOT NULL,
  certificate_file VARCHAR(255) NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_eval_enrollment FOREIGN KEY (enrollment_id) REFERENCES ojt_enrollments(id) ON DELETE CASCADE,
  CONSTRAINT fk_eval_company FOREIGN KEY (company_id) REFERENCES partner_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;
