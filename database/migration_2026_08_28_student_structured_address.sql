-- Structured Philippine home address fields (backward-compatible with existing students.address text).
ALTER TABLE students
  ADD COLUMN address_street VARCHAR(255) NULL AFTER address,
  ADD COLUMN address_barangay VARCHAR(150) NULL AFTER address_street,
  ADD COLUMN address_municipality VARCHAR(150) NULL AFTER address_barangay,
  ADD COLUMN address_province VARCHAR(150) NULL AFTER address_municipality,
  ADD COLUMN address_barangay_code VARCHAR(20) NULL AFTER address_province,
  ADD COLUMN address_municipality_code VARCHAR(20) NULL AFTER address_barangay_code,
  ADD COLUMN address_province_code VARCHAR(20) NULL AFTER address_municipality_code;
