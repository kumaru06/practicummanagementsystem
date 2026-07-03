-- Add optional middle_name to users and student registration requests.
-- Select your database in phpMyAdmin before importing.

ALTER TABLE users
  ADD COLUMN middle_name VARCHAR(100) NULL AFTER first_name;

ALTER TABLE student_registration_requests
  ADD COLUMN middle_name VARCHAR(100) NULL AFTER first_name;
