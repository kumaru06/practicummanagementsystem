-- Import this file into the same database where schema.sql was imported.

-- SECURITY: no usable admin password ships in source control. The admin row is seeded
-- with a LOCKED placeholder hash, so password_verify() always fails and there is NO
-- known/default admin password to guess. Set a real password on the target database with:
--     php tools/set-admin-password.php admin@ama.edu.ph --yes
-- Run it WITHOUT --yes first to audit an existing DB (it reports whether the old public
-- hash is still in use). password_changed = 0 still forces a change on first login.
INSERT INTO users (id, name, email, password_hash, role, created_by, is_active, password_changed) VALUES
(1, 'System Administrator', 'admin@ama.edu.ph', 'LOCKED-NO-LOGIN:set-with-tools/set-admin-password.php', 'admin', NULL, 1, 0);

-- ─── Default Degree Program ──────────────────────────────────────────────
INSERT INTO programs (id, code, name, required_hours, is_active) VALUES
(1, 'BSIT', 'Bachelor of Science in Information Technology', 486, 1),
(2, 'BSBA', 'Bachelor of Science in Business Administration', 600, 1),
(3, 'BSCS', 'Bachelor of Science in Computer Science', 120, 1),
(4, 'BSCOE', 'Bachelor of Science in Computer Engineering', 240, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), required_hours = VALUES(required_hours), is_active = VALUES(is_active);
