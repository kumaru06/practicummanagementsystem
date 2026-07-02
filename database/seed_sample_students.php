<?php
/**
 * Seed sample students for pagination / filtering tests.
 *
 * Usage:
 *   php database/seed_sample_students.php
 *   php database/seed_sample_students.php --count=200
 *   php database/seed_sample_students.php --coordinator=coord@ama.edu.ph
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from the command line.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/init.php';

$count = 200;
$coordinatorEmail = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--count=')) {
        $count = max(1, (int)substr($arg, 8));
    } elseif (str_starts_with($arg, '--coordinator=')) {
        $coordinatorEmail = strtolower(trim(substr($arg, 14)));
    }
}

$db = db();
$userModel = new User($db);
$studentModel = new Student($db);

if ($coordinatorEmail) {
    $coordUser = $userModel->findByEmail($coordinatorEmail);
    if (!$coordUser || ($coordUser['role'] ?? '') !== 'coordinator') {
        fwrite(STDERR, "Coordinator not found: {$coordinatorEmail}\n");
        exit(1);
    }
} else {
    $coordUser = $db->query("SELECT * FROM users WHERE role = 'coordinator' AND is_active = 1 ORDER BY id ASC LIMIT 1")->fetch();
    if (!$coordUser) {
        fwrite(STDERR, "No active coordinator found. Create one first or pass --coordinator=email.\n");
        exit(1);
    }
}

$coordinatorId = (int)$coordUser['id'];
$program = $db->query('SELECT id, code FROM programs WHERE is_active = 1 ORDER BY id ASC LIMIT 1')->fetch();
$programId = $program ? (int)$program['id'] : null;
$course = $program['code'] ?? 'BSIT';

$corDir = dirname(__DIR__) . '/uploads/cor';
if (!is_dir($corDir)) {
    mkdir($corDir, 0775, true);
}
$corFile = 'uploads/cor/seed-placeholder.pdf';
$corPath = dirname(__DIR__) . '/' . $corFile;
if (!is_file($corPath)) {
    file_put_contents($corPath, '%PDF-1.4 seed placeholder');
}

$firstNames = ['Juan', 'Maria', 'Jose', 'Ana', 'Mark', 'Grace', 'Paul', 'Joy', 'Carlo', 'Kim', 'Rica', 'Miguel', 'Patricia', 'Angelo', 'Nicole', 'Rafael', 'Hannah', 'Luis', 'Bianca', 'Ethan'];
$lastNames = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Ramos', 'Aquino', 'Castillo', 'Navarro', 'Rivera', 'Dizon', 'Lopez', 'Villanueva', 'Morales', 'Fernandez', 'Gonzales', 'Pascual'];
$yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$sections = ['A', 'B', 'C', 'D'];

$existingNos = $db->query('SELECT student_no FROM students WHERE student_no LIKE "SMP-%"')->fetchAll(PDO::FETCH_COLUMN);
$startIndex = 1;
foreach ($existingNos as $no) {
    if (preg_match('/^SMP-(\d+)$/', (string)$no, $m)) {
        $startIndex = max($startIndex, (int)$m[1] + 1);
    }
}

$created = 0;
$skipped = 0;

$db->beginTransaction();
try {
    for ($i = 0; $i < $count; $i++) {
        $index = $startIndex + $i;
        $studentNo = sprintf('SMP-%05d', $index);
        $email = sprintf('sample.student.%05d@ama.edu.ph', $index);
        $first = $firstNames[$index % count($firstNames)];
        $last = $lastNames[($index * 3) % count($lastNames)];
        $name = $first . ' ' . $last . ' ' . (($index % 90) + 10);
        $yearLevel = $yearLevels[$index % count($yearLevels)];
        $section = $sections[$index % count($sections)];

        if ($studentModel->existsByStudentNo($studentNo)) {
            $skipped++;
            continue;
        }

        $checkEmail = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $checkEmail->execute([$email]);
        if ((int)$checkEmail->fetchColumn() > 0) {
            $skipped++;
            continue;
        }

        $userId = $userModel->create($name, $email, 'Student@123', 'student', $coordinatorId, 1);
        $studentModel->create($userId, $studentNo, $course, $yearLevel, $corFile, $coordinatorId, $programId, $section);
        $created++;
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, 'Seeding failed: ' . $e->getMessage() . "\n");
    exit(1);
}

$total = $studentModel->countByCoordinator($coordinatorId);
echo "Done. Created {$created} sample student(s), skipped {$skipped}.\n";
echo "Coordinator: {$coordUser['name']} <{$coordUser['email']}> (user id {$coordinatorId})\n";
echo "Total students under this coordinator: {$total}\n";
echo "Default login password for sample students: Student@123\n";
