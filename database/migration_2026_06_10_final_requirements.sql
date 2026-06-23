CREATE TABLE IF NOT EXISTS student_final_requirements (
  student_id INT NOT NULL PRIMARY KEY,
  position_held VARCHAR(255) NULL,
  job_description TEXT NULL,
  job_description_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  company_history TEXT NULL,
  company_description TEXT NULL,
  company_mission TEXT NULL,
  company_vision TEXT NULL,
  company_profile_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  personal_observation TEXT NULL,
  personal_observation_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_final_req_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;
