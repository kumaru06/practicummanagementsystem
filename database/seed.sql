-- Import this file into the same database where schema.sql was imported.

INSERT INTO users (id, name, email, password_hash, role, created_by, is_active, password_changed) VALUES
(1, 'System Administrator', 'admin@ama.edu.ph', '$2y$10$9CHq.Pz4X5vuhbXpv5DE6O6FOtawSqC7eoj/kXj6UBJ3jgHH8Rp/O', 'admin', NULL, 1, 1);

-- ─── Default Programs / Courses ──────────────────────────────────────────────
INSERT INTO programs (id, code, name, required_hours, is_active) VALUES
(1, 'BSIT', 'Bachelor of Science in Information Technology', 486, 1),
(2, 'BSBA', 'Bachelor of Science in Business Administration', 600, 1),
(3, 'BSCS', 'Bachelor of Science in Computer Science', 120, 1),
(4, 'BSCOE', 'Bachelor of Science in Computer Engineering', 240, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), required_hours = VALUES(required_hours), is_active = VALUES(is_active);
