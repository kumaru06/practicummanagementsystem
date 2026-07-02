<?php
/**
 * Remove sample students created by seed_sample_students.php.
 *
 * Usage:
 *   php database/remove_sample_students.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from the command line.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/init.php';

$db = db();

$stmt = $db->query(
    "SELECT u.id, u.email, s.student_no
     FROM users u
     JOIN students s ON s.user_id = u.id
     WHERE u.role = 'student'
       AND (s.student_no LIKE 'SMP-%' OR u.email LIKE 'sample.student.%@ama.edu.ph')
     ORDER BY u.id"
);
$rows = $stmt->fetchAll();

if (!$rows) {
    echo "No sample students found.\n";
    exit(0);
}

$db->beginTransaction();
try {
    $delete = $db->prepare('DELETE FROM users WHERE id = ?');
    foreach ($rows as $row) {
        $delete->execute([(int)$row['id']]);
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, 'Cleanup failed: ' . $e->getMessage() . "\n");
    exit(1);
}

echo 'Removed ' . count($rows) . " sample student(s).\n";
