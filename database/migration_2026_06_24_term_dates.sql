-- Add start/end dates to academic terms (auto-filled on coordinator enrollment)
ALTER TABLE program_terms
  ADD COLUMN IF NOT EXISTS term_start_date DATE NULL AFTER term_label,
  ADD COLUMN IF NOT EXISTS term_end_date DATE NULL AFTER term_start_date;
