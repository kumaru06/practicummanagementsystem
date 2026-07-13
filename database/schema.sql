-- Import this file into your already selected database.
-- Shared hosts like InfinityFree do not allow CREATE DATABASE/USE statements.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS evaluations;
DROP TABLE IF EXISTS weekly_reports;
DROP TABLE IF EXISTS dtr_drafts;
DROP TABLE IF EXISTS daily_time_records;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS email_logs;
DROP TABLE IF EXISTS ojt_enrollments;
DROP TABLE IF EXISTS student_requirements;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS company_programs;
DROP TABLE IF EXISTS program_terms;
DROP TABLE IF EXISTS programs;
DROP TABLE IF EXISTS coordinators;
DROP TABLE IF EXISTS partner_companies;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  first_name VARCHAR(100) NULL,
  middle_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','coordinator','student','partner') NOT NULL,
  created_by INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  password_changed TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  last_login_ip VARCHAR(45) NULL,
  last_login_device VARCHAR(190) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE coordinators (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  id_number VARCHAR(60) NULL,
  department VARCHAR(120) DEFAULT 'OJT Department',
  signature_file VARCHAR(255) NULL,
  UNIQUE KEY uq_coordinators_id_number (id_number),
  CONSTRAINT fk_coordinators_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE programs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(190) NOT NULL,
  term VARCHAR(120) NOT NULL DEFAULT '',
  required_hours INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE program_terms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  term_label VARCHAR(120) NOT NULL UNIQUE,
  term_start_date DATE NULL,
  term_end_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE partner_companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  partner_id VARCHAR(20) NULL UNIQUE,
  user_id INT NOT NULL UNIQUE,
  name VARCHAR(190) NOT NULL,
  address TEXT NOT NULL,
  contact_person VARCHAR(150) NOT NULL,
  contact_email VARCHAR(190) NOT NULL,
  contact_number VARCHAR(60) NULL,
  moa_mou_file VARCHAR(255) NULL,
  photo_file VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_companies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE company_programs (
  company_id INT NOT NULL,
  program_id INT NOT NULL,
  PRIMARY KEY (company_id, program_id),
  CONSTRAINT fk_company_program_company FOREIGN KEY (company_id) REFERENCES partner_companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_company_program_program FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  student_no VARCHAR(60) NOT NULL UNIQUE,
  program_id INT NULL,
  course VARCHAR(120) NOT NULL,
  year_level VARCHAR(60) NOT NULL,
  section VARCHAR(80) NULL,
  birthdate DATE NULL,
  cor_file VARCHAR(255) NOT NULL,
  photo_file VARCHAR(255) NULL,
  address TEXT NULL,
  contact_number VARCHAR(60) NULL,
  emergency_contact_name VARCHAR(150) NULL,
  emergency_contact_number VARCHAR(60) NULL,
  guardian_name VARCHAR(150) NULL,
  guardian_contact VARCHAR(60) NULL,
  profile_completed TINYINT(1) NOT NULL DEFAULT 0,
  coordinator_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_students_program FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL,
  CONSTRAINT fk_students_coord FOREIGN KEY (coordinator_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE student_requirements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  requirement_key VARCHAR(80) NOT NULL,
  requirement_name VARCHAR(150) NOT NULL,
  file_path VARCHAR(255) NULL,
  status ENUM('pending','uploaded','approved','rejected') NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  uploaded_at DATETIME NULL,
  reviewed_at DATETIME NULL,
  UNIQUE KEY uq_student_requirement (student_id, requirement_key),
  CONSTRAINT fk_requirements_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ojt_enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL UNIQUE,
  company_id INT NOT NULL,
  academic_term VARCHAR(40) NULL,
  term_start_date DATE NULL,
  term_end_date DATE NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  required_hours INT NOT NULL,
  status ENUM('pending','active','completed') NOT NULL DEFAULT 'pending',
  predeployment_status ENUM('not_submitted','submitted','needs_revision','approved','forwarded','accepted','orientation_scheduled','orientation_completed') NOT NULL DEFAULT 'not_submitted',
  endorsement_file VARCHAR(255) NULL,
  forwarded_at DATETIME NULL,
  accepted_at DATETIME NULL,
  orientation_datetime DATETIME NULL,
  orientation_notes TEXT NULL,
  official_start_date DATE NULL,
  projected_end_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_enroll_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_enroll_company FOREIGN KEY (company_id) REFERENCES partner_companies(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE daily_time_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  work_date DATE NOT NULL,
  day_type ENUM('full','half_am','half_pm','sick','absent') NOT NULL DEFAULT 'full',
  time_in TIME NOT NULL,
  time_out TIME NOT NULL,
  morning_time_in TIME NULL,
  morning_time_out TIME NULL,
  afternoon_time_in TIME NULL,
  afternoon_time_out TIME NULL,
  hours DECIMAL(6,2) NOT NULL,
  tasks_done TEXT NOT NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dtr_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE dtr_drafts (
  student_id INT NOT NULL PRIMARY KEY,
  work_date DATE NULL,
  time_in VARCHAR(5) NULL,
  time_out VARCHAR(5) NULL,
  time_in_locked TINYINT(1) NOT NULL DEFAULT 0,
  time_out_locked TINYINT(1) NOT NULL DEFAULT 0,
  morning_time_in VARCHAR(5) NULL,
  morning_time_out VARCHAR(5) NULL,
  afternoon_time_in VARCHAR(5) NULL,
  afternoon_time_out VARCHAR(5) NULL,
  morning_time_in_locked TINYINT(1) NOT NULL DEFAULT 0,
  morning_time_out_locked TINYINT(1) NOT NULL DEFAULT 0,
  afternoon_time_in_locked TINYINT(1) NOT NULL DEFAULT 0,
  afternoon_time_out_locked TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_dtr_drafts_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE weekly_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  week_no INT NOT NULL,
  report_text TEXT NULL,
  file_path VARCHAR(255) NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reports_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE evaluations (
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

CREATE TABLE email_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  recipient_email VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  type VARCHAR(80) NOT NULL,
  sent_at DATETIME NOT NULL,
  status ENUM('sent','failed') NOT NULL,
  error_message TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  link VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
