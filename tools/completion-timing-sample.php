<?php
/**
 * Sample placements that prove Overall Completion buckets.
 * Usage: php tools/completion-timing-sample.php
 * Uses the isolated practicum_audit_test database and rolls back.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

$root = dirname(__DIR__);
require $root . '/helpers.php';
require $root . '/models/Enrollment.php';

$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=practicum_audit_test;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$activeDb = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
if ($activeDb !== 'practicum_audit_test') {
    fwrite(STDERR, "Refusing to run on {$activeDb}\n");
    exit(2);
}

$term = '2523 (2nd Tri) - SY 2025-2026';
$termDash = "2523 (2nd Tri) \u{2013} SY 2025-2026";
$fails = 0;
$check = static function (string $label, bool $ok, string $detail = '') use (&$fails): void {
    if ($ok) {
        echo "[PASS] {$label}\n";
        return;
    }
    $fails++;
    echo "[FAIL] {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
};

$pdo->beginTransaction();
try {
    $user = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, is_active, password_changed) VALUES (?, ?, "x", ?, 1, 1)');
    $user->execute(['OC Coord', 'oc-coord@oc-sample.test', 'coordinator']);
    $coordId = (int)$pdo->lastInsertId();
    $user->execute(['OC Partner', 'oc-partner@oc-sample.test', 'partner']);
    $partnerId = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO partner_companies (user_id, name, address, contact_person, contact_email) VALUES (?, "OC Company", "Addr", "Contact", "oc-partner@oc-sample.test")')->execute([$partnerId]);
    $companyId = (int)$pdo->lastInsertId();

    $mkStudent = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, is_active, password_changed) VALUES (?, ?, "x", "student", 1, 1)');
    $mkRow = $pdo->prepare('INSERT INTO students (user_id, student_no, program_id, course, year_level, cor_file, coordinator_id) VALUES (?, ?, 1, "BSIT", "4th Year", "uploads/cor/x.pdf", ?)');
    $mkEnroll = $pdo->prepare('INSERT INTO ojt_enrollments (student_id, company_id, academic_term, required_hours, status, projected_end_date, end_date, official_start_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $mkDtr = $pdo->prepare('INSERT INTO daily_time_records (student_id, work_date, day_type, time_in, time_out, hours, tasks_done, verification_status) VALUES (?, ?, "full", "08:00:00", "17:00:00", ?, "work", "approved")');

    $add = static function (string $name, string $email, string $no, string $enrollTerm, int $required, string $status, string $projected, string $endDate, array $days) use ($pdo, $mkStudent, $mkRow, $mkEnroll, $mkDtr, $coordId, $companyId): void {
        $mkStudent->execute([$name, $email]);
        $userId = (int)$pdo->lastInsertId();
        $mkRow->execute([$userId, $no, $coordId]);
        $studentId = (int)$pdo->lastInsertId();
        $mkEnroll->execute([$studentId, $companyId, $enrollTerm, $required, $status, $projected, $endDate, $days[0][0] ?? $projected]);
        foreach ($days as [$date, $hours]) {
            $mkDtr->execute([$studentId, $date, $hours]);
        }
    };

    $add('Early Finisher', 'oc-early@oc-sample.test', 'OC-1', $term, 24, 'completed', '2026-10-29', '2026-10-29', [
        ['2026-10-01', 8], ['2026-10-02', 8], ['2026-10-03', 8],
    ]);
    $add('On Time', 'oc-ontime@oc-sample.test', 'OC-2', $term, 16, 'completed', '2026-10-10', '2026-10-10', [
        ['2026-10-09', 8], ['2026-10-10', 8],
    ]);
    $add('Beyond', 'oc-beyond@oc-sample.test', 'OC-3', $term, 16, 'completed', '2026-09-01', '2026-09-01', [
        ['2026-09-01', 8], ['2026-09-02', 8],
    ]);
    $add('Still Active', 'oc-active@oc-sample.test', 'OC-4', $term, 480, 'active', '2026-12-01', '2026-12-01', [
        ['2026-08-01', 8],
    ]);
    $add('Fake Completed', 'oc-fake@oc-sample.test', 'OC-5', $term, 40, 'completed', '2026-08-01', '2026-08-01', [
        ['2026-07-20', 8],
    ]);
    $decimalDays = [];
    for ($day = 1; $day <= 10; $day++) {
        $decimalDays[] = [sprintf('2026-03-%02d', $day), 8.10];
    }
    $add('Decimal Hours', 'oc-decimal@oc-sample.test', 'OC-6', $term, 81, 'completed', '2026-03-20', '2026-03-20', $decimalDays);
    $add('Dash Term', 'oc-dash@oc-sample.test', 'OC-7', $termDash, 8, 'completed', '2026-07-01', '2026-07-01', [
        ['2026-07-01', 8],
    ]);

    $enrollment = new Enrollment($pdo);
    $report = $enrollment->completionTimingByTerm($term);
    $report = $enrollment->fillCompletionPrograms($report, [
        ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology'],
        ['code' => 'BSBA', 'name' => 'Bachelor of Science in Business Administration'],
    ]);

    $bsitRows = array_values(array_filter(
        $report['programs'],
        static fn ($row) => strtoupper((string)($row['code'] ?? '')) === 'BSIT'
    ));
    $bsit = $bsitRows[0] ?? ['before' => -1, 'ontime' => -1, 'beyond' => -1, 'total' => -1];

    echo "total={$report['total']} before={$report['before']['count']} ontime={$report['ontime']['count']} beyond={$report['beyond']['count']}\n";
    echo 'bsit_rows=' . count($bsitRows) . " bsit_total={$bsit['total']} before={$bsit['before']} ontime={$bsit['ontime']} beyond={$bsit['beyond']}\n";

    $check('Five finished students are counted', $report['total'] === 5, 'total=' . $report['total']);
    $check('Two finished before the projected end', $report['before']['count'] === 2, 'before=' . $report['before']['count']);
    $check('Two finished on the projected end', $report['ontime']['count'] === 2, 'ontime=' . $report['ontime']['count']);
    $check('One finished after the projected end', $report['beyond']['count'] === 1, 'beyond=' . $report['beyond']['count']);
    $check('BSIT appears once', count($bsitRows) === 1, 'rows=' . count($bsitRows));
    $check('BSIT total matches the finished students', (int)$bsit['total'] === 5, 'bsit_total=' . $bsit['total']);
    $check('Percents add to 100', ((int)$report['before']['pct'] + (int)$report['ontime']['pct'] + (int)$report['beyond']['pct']) === 100);
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

exit($fails > 0 ? 1 : 0);
