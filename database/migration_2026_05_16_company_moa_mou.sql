-- Add MOA/MOU upload support for partner companies.

ALTER TABLE partner_companies
  ADD COLUMN IF NOT EXISTS moa_mou_file VARCHAR(255) NULL AFTER contact_number;
