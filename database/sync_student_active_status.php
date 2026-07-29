<?php
require __DIR__ . '/../config/database.php';

$stmt = db()->prepare(
    'UPDATE users u
     JOIN students s ON s.user_id = u.id
     LEFT JOIN ojt_enrollments e ON e.student_id = s.id
     SET u.is_active = 0
     WHERE u.role = ?
       AND e.id IS NULL
       AND u.is_active = 1'
);
$stmt->execute(['student']);
echo 'Updated unenrolled students to inactive: ' . $stmt->rowCount() . PHP_EOL;
