-- Import this migration into the currently selected database.
-- Formalizes the coordinators.signature_file column previously added at runtime by
-- User::ensureCoordinatorSignatureSupport(). Safe to run more than once.

ALTER TABLE coordinators
  ADD COLUMN IF NOT EXISTS signature_file VARCHAR(255) NULL AFTER department;
