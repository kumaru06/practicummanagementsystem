<?php
declare(strict_types=1);

/**
 * Isolated runtime verification harness (CLI ONLY).
 *
 * Seeds synthetic accounts into an ISOLATED test database and exercises the REAL
 * authorization / workflow / data-integrity code paths (FileAccess, ChatController,
 * Student, Enrollment, Report). It never sends email and refuses to run against any
 * database other than the dedicated test DB.
 *
 *   php tools/audit-verify.php
 *
 * Prereq: create the test DB first (see the audit notes):
 *   DROP/CREATE DATABASE practicum_audit_test; import schema.sql + migrations + seed.sql.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

const AUDIT_DB_NAME = 'practicum_audit_test';

$root = dirname(__DIR__);

// --- Safe, self-contained bootstrap (does NOT touch the app's real DB config) ---
define('APP_IS_LOCAL', true);
define('MAIL_DRIVER', 'file');                       // guarantee no real SMTP
define('MAIL_FILE_DIR', $root . '/uploads/dev-mail');
date_default_timezone_set('Asia/Manila');

require $root . '/bootstrap/env.php';
require $root . '/helpers.php';

$__auditPdo = new PDO(
    'mysql:host=127.0.0.1;dbname=' . AUDIT_DB_NAME . ';charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
$__auditPdo->exec("SET time_zone = '+08:00'");

/** Global db() used by FileAccess and any code that resolves the shared connection. */
function db(): PDO
{
    global $__auditPdo;
    return $__auditPdo;
}

// Hard safety gate: never run against anything but the dedicated test DB.
$activeDb = (string)db()->query('SELECT DATABASE()')->fetchColumn();
if ($activeDb !== AUDIT_DB_NAME) {
    fwrite(STDERR, "REFUSING TO RUN: connected to '{$activeDb}', expected '" . AUDIT_DB_NAME . "'.\n");
    exit(2);
}

spl_autoload_register(static function (string $class) use ($root): void {
    foreach (['models', 'controllers'] as $dir) {
        $path = $root . '/' . $dir . '/' . $class . '.php';
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});

// --- Tiny assertion framework ---
$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;
function check(string $label, bool $ok): void
{
    if ($ok) {
        $GLOBALS['pass']++;
        fwrite(STDOUT, "[PASS] {$label}\n");
    } else {
        $GLOBALS['fail']++;
        fwrite(STDOUT, "[FAIL] {$label}\n");
    }
}
function check_throws(string $label, callable $fn): void
{
    try {
        $fn();
        check($label . ' (expected rejection)', false);
    } catch (Throwable $e) {
        check($label, true);
    }
}

$pdo = db();

/**
 * Remove all synthetic fixture data. Safe because this runs ONLY against the
 * dedicated test DB (guarded above). FK checks are relaxed to avoid ordering
 * issues (e.g. students.coordinator_id is ON DELETE RESTRICT).
 */
function audit_cleanup(PDO $pdo): void
{
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $tables = [
        'chat_attachments', 'chat_message_reactions', 'chat_typing', 'messages',
        'daily_time_records', 'dtr_drafts', 'weekly_report_files', 'weekly_reports',
        'student_requirements', 'student_final_requirements', 'student_evaluations',
        'notifications', 'evaluations', 'ojt_enrollments', 'company_programs',
        'students', 'coordinators', 'partner_companies',
    ];
    foreach ($tables as $t) {
        try {
            $pdo->exec("DELETE FROM {$t}");
        } catch (Throwable) {
            // table may not exist in this schema build; ignore
        }
    }
    $pdo->exec("DELETE FROM users WHERE email LIKE '%@audit.test'");
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

// --- Clean slate (idempotent) ---
audit_cleanup($pdo);

// --- Seed synthetic fixtures ---
$insUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, is_active, password_changed) VALUES (?, ?, 'x', ?, 1, 1)");
$mkUser = static function (string $name, string $email, string $role) use ($pdo, $insUser): int {
    $insUser->execute([$name, $email, $role]);
    return (int)$pdo->lastInsertId();
};

$adminUserId = (int)$pdo->query("SELECT id FROM users WHERE role='admin' ORDER BY id LIMIT 1")->fetchColumn();
if ($adminUserId <= 0) {
    $adminUserId = $mkUser('Audit Admin', 'admin@audit.test', 'admin');
}

$c1 = $mkUser('Coordinator One', 'c1@audit.test', 'coordinator');
$c2 = $mkUser('Coordinator Two', 'c2@audit.test', 'coordinator');
$pdo->prepare("INSERT INTO coordinators (user_id, id_number, department) VALUES (?, ?, 'OJT')")->execute([$c1, '1001']);
$pdo->prepare("INSERT INTO coordinators (user_id, id_number, department) VALUES (?, ?, 'OJT')")->execute([$c2, '1002']);

$p1u = $mkUser('Partner One', 'p1@audit.test', 'partner');
$p2u = $mkUser('Partner Two', 'p2@audit.test', 'partner');
$insCo = $pdo->prepare("INSERT INTO partner_companies (user_id, name, address, contact_person, contact_email, contact_number) VALUES (?, ?, 'Addr', 'Contact', ?, '+63 900 000 0000')");
$insCo->execute([$p1u, 'Company One', 'p1@audit.test']);
$p1co = (int)$pdo->lastInsertId();
$insCo->execute([$p2u, 'Company Two', 'p2@audit.test']);
$p2co = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO company_programs (company_id, program_id) VALUES (?, 1)")->execute([$p1co]);
$pdo->prepare("INSERT INTO company_programs (company_id, program_id) VALUES (?, 2)")->execute([$p2co]); // different program
// Give both companies an MOA file so the coordinator-access checks are meaningful.
$pdo->prepare("UPDATE partner_companies SET moa_mou_file = 'uploads/company_moa_mou/p1.pdf' WHERE id = ?")->execute([$p1co]);
$pdo->prepare("UPDATE partner_companies SET moa_mou_file = 'uploads/company_moa_mou/p2.pdf' WHERE id = ?")->execute([$p2co]);

$s1u = $mkUser('Student One', 's1@audit.test', 'student');
$s2u = $mkUser('Student Two', 's2@audit.test', 'student');
$insStu = $pdo->prepare("INSERT INTO students (user_id, student_no, program_id, course, year_level, cor_file, coordinator_id, profile_completed) VALUES (?, ?, 1, 'BSIT', '4th Year', 'uploads/cor/x.pdf', ?, 1)");
$insStu->execute([$s1u, '20250001', $c1]);
$s1 = (int)$pdo->lastInsertId();
$insStu->execute([$s2u, '20250002', $c2]);
$s2 = (int)$pdo->lastInsertId();

// S1 deployed to P1, documents forwarded (partner visibility + chat unlock).
$pdo->prepare("INSERT INTO ojt_enrollments (student_id, company_id, required_hours, status, predeployment_status) VALUES (?, ?, 486, 'pending', 'forwarded')")
    ->execute([$s1, $p1co]);

// S1 has one approved requirement file (path embeds the student id).
$s1ReqPath = 'uploads/requirements/' . $s1 . '/a.pdf';
$pdo->prepare("INSERT INTO student_requirements (student_id, requirement_key, requirement_name, file_path, status, uploaded_at) VALUES (?, 'resume', 'Resume', ?, 'approved', NOW())")
    ->execute([$s1, $s1ReqPath]);

// S1 DTR: two approved (8h each) + one pending (8h).
$insDtr = $pdo->prepare("INSERT INTO daily_time_records (student_id, work_date, day_type, time_in, time_out, hours, tasks_done, verification_status) VALUES (?, ?, 'full', '08:00:00', '17:00:00', 8.00, 'work', ?)");
$insDtr->execute([$s1, '2026-01-05', 'approved']);
$insDtr->execute([$s1, '2026-01-06', 'approved']);
$insDtr->execute([$s1, '2026-01-07', 'pending']);

// Trigger chat schema creation (creates chat_attachments/reactions if missing).
$_SESSION['user_id'] = $c1;
$_SESSION['role'] = 'coordinator';
(new ChatController($pdo))->getUnreadTotal();

// A chat message S1 (student) -> C1 (coordinator) with an attachment.
$pdo->prepare("INSERT INTO messages (sender_id, sender_role, receiver_id, receiver_role, message_text) VALUES (?, 'student', ?, 'coordinator', 'hello')")
    ->execute([$s1u, $c1]);
$msgId = (int)$pdo->lastInsertId();
$s1ChatPath = 'uploads/chat/2026/x.jpg';
$pdo->prepare("INSERT INTO chat_attachments (message_id, file_path, original_name, mime, byte_size) VALUES (?, ?, 'x.jpg', 'image/jpeg', 1000)")
    ->execute([$msgId, $s1ChatPath]);

// User arrays as passed by serve.php ($user['id'] is the users.id).
$U = static fn (int $id, string $role): array => ['id' => $id, 'role' => $role];
$adminU = $U($adminUserId, 'admin');
$c1U = $U($c1, 'coordinator');
$c2U = $U($c2, 'coordinator');
$s1U = $U($s1u, 'student');
$s2U = $U($s2u, 'student');
$p1U = $U($p1u, 'partner');
$p2U = $U($p2u, 'partner');

fwrite(STDOUT, "\n=== B. Authorization & data ownership: private document access (FileAccess) ===\n");
check('Student can view OWN requirement file', FileAccess::canView($s1ReqPath, $s1U) === true);
check('Other student CANNOT view another student file', FileAccess::canView($s1ReqPath, $s2U) === false);
check('Assigned coordinator CAN view student file', FileAccess::canView($s1ReqPath, $c1U) === true);
check('Unrelated coordinator CANNOT view student file', FileAccess::canView($s1ReqPath, $c2U) === false);
check('Assigned partner (docs forwarded) CAN view student file', FileAccess::canView($s1ReqPath, $p1U) === true);
check('Unrelated partner CANNOT view student file', FileAccess::canView($s1ReqPath, $p2U) === false);
check('Admin CAN view non-chat student file', FileAccess::canView($s1ReqPath, $adminU) === true);
check('Any authenticated user can view profile avatars', FileAccess::canView('uploads/profiles/p.png', $s2U) === true);

fwrite(STDOUT, "\n=== F. Live chat: attachment access is participant-only ===\n");
check('Chat attachment: sender (student) can view', FileAccess::canView($s1ChatPath, $s1U) === true);
check('Chat attachment: receiver (coordinator) can view', FileAccess::canView($s1ChatPath, $c1U) === true);
check('Chat attachment: unrelated student CANNOT view', FileAccess::canView($s1ChatPath, $s2U) === false);
check('Chat attachment: linked partner (non-participant) CANNOT view', FileAccess::canView($s1ChatPath, $p1U) === false);
check('Chat attachment: admin (non-participant) CANNOT view', FileAccess::canView($s1ChatPath, $adminU) === false);

fwrite(STDOUT, "\n=== B. Coordinator ownership scope ===\n");
$studentModel = new Student($pdo);
check('belongsToCoordinator(S1, C1) is true', $studentModel->belongsToCoordinator($s1, $c1) === true);
check('belongsToCoordinator(S1, C2) is false', $studentModel->belongsToCoordinator($s1, $c2) === false);
check('belongsToCoordinator(S2, C1) is false', $studentModel->belongsToCoordinator($s2, $c1) === false);

fwrite(STDOUT, "\n=== F. Chat contact restrictions (server-side) ===\n");
$_SESSION['user_id'] = $c1; $_SESSION['role'] = 'coordinator';
$chatC1 = new ChatController($pdo);
check('Coordinator can chat OWN student', $chatC1->canChatWith($s1u, 'student') === true);
check('Coordinator CANNOT chat another coordinator\'s student', $chatC1->canChatWith($s2u, 'student') === false);

$_SESSION['user_id'] = $s1u; $_SESSION['role'] = 'student';
$chatS1 = new ChatController($pdo);
check('Student CANNOT chat admin (intentional rule)', $chatS1->canChatWith($adminUserId, 'admin') === false);
check('Student CAN see/chat assigned HTE after forward', $chatS1->canChatWith($p1u, 'partner') === true);
check('Student CAN send to HTE once documents forwarded', $chatS1->canSendTo($p1u, 'partner') === true);

$_SESSION['user_id'] = $p1u; $_SESSION['role'] = 'partner';
$chatP1 = new ChatController($pdo);
check('Partner can chat OWN student', $chatP1->canChatWith($s1u, 'student') === true);
check('Partner CANNOT chat unrelated student', $chatP1->canChatWith($s2u, 'student') === false);

fwrite(STDOUT, "\n=== E. Data integrity: report hours count approved DTR only ===\n");
$report = new Report($pdo);
check('totalHours(approved only) === 16.0', abs($report->totalHours($s1, true) - 16.0) < 0.001);
check('totalHours(all) === 24.0', abs($report->totalHours($s1, false) - 24.0) < 0.001);
check_throws('Duplicate DTR for same (student, work_date) is rejected', function () use ($insDtr, $s1) {
    $insDtr->execute([$s1, '2026-01-05', 'pending']); // same date as an existing row
});

fwrite(STDOUT, "\n=== C. Workflow transition guards (optimistic concurrency) ===\n");
$enroll = new Enrollment($pdo);
$e1 = $enroll->byStudent($s1);
$e1Id = (int)$e1['id'];
check('acceptDeployment succeeds from "forwarded"', $enroll->acceptDeployment($e1Id) === true);
check('acceptDeployment is idempotent (replay returns false)', $enroll->acceptDeployment($e1Id) === false);
check_throws('Duplicate enrollment for same student is rejected', function () use ($enroll, $s1, $p1co) {
    $enroll->create($s1, $p1co, null, null, 486);
});

fwrite(STDOUT, "\n=== M-7. N+1 batch methods return identical results (regression) ===\n");
// Enrollment::byStudents matches per-student byStudent().
$batchEnroll = $enroll->byStudents([$s1, $s2]);
$singleS1 = $enroll->byStudent($s1);
check('byStudents contains enrolled student S1', isset($batchEnroll[$s1]));
check('byStudents omits non-enrolled student S2', !isset($batchEnroll[$s2]));
check('byStudents row matches byStudent (company_id)', (int)($batchEnroll[$s1]['company_id'] ?? 0) === (int)($singleS1['company_id'] ?? -1));

// Student::stageRequirementsForStudents matches per-student stageRequirements().
$batchReq = $studentModel->stageRequirementsForStudents([$s1], 1)[$s1] ?? [];
$singleReq = $studentModel->stageRequirements($s1, 1);
$shape = static function (array $rows): array {
    $out = [];
    foreach ($rows as $k => $r) {
        $out[$k] = [(string)($r['file_path'] ?? ''), (string)($r['status'] ?? '')];
    }
    ksort($out);
    return $out;
};
check('stageRequirementsForStudents equals per-student stageRequirements (stage 1)', $shape($batchReq) === $shape($singleReq));

// Company::coordinatorAccessibleCompanyIds matches per-company coordinatorCanAccessMoa.
$companyModel = new Company($pdo);
$accessibleC1 = array_flip($companyModel->coordinatorAccessibleCompanyIds($c1));
$canP1 = $companyModel->coordinatorCanAccessMoa($c1, $p1co);
$canP2 = $companyModel->coordinatorCanAccessMoa($c1, $p2co);
check('MOA batch matches single check for P1 (enrolled -> allowed)', $canP1 === isset($accessibleC1[$p1co]));
check('MOA batch matches single check for P2 (no link -> denied)', $canP2 === isset($accessibleC1[$p2co]));
check('Coordinator C1 CAN access own enrolled company MOA', $canP1 === true);
check('Coordinator C1 CANNOT access unrelated company MOA', $canP2 === false);

// Enrollment::deployedByCompany returns only the partner's own students.
$deployed = $enroll->deployedByCompany($p1co);
$deployedIds = array_map(static fn ($r) => (int)$r['student_id'], $deployed);
check('deployedByCompany(P1) includes S1 (forwarded)', in_array($s1, $deployedIds, true));
check('deployedByCompany(P1) excludes S2 (other company/none)', !in_array($s2, $deployedIds, true));

fwrite(STDOUT, "\n=== L-7. Password policy helper ===\n");
check('password_strength_error rejects short passwords', password_strength_error('Ab1') !== null);
check('password_strength_error rejects missing uppercase', password_strength_error('abcdefg1') !== null);
check('password_strength_error accepts mixed case + number', password_strength_error('Abcdefg1') === null);

// --- Summary ---
$pass = (int)$GLOBALS['pass'];
$fail = (int)$GLOBALS['fail'];
fwrite(STDOUT, "\n==================== SUMMARY ====================\n");
fwrite(STDOUT, "PASS: {$pass}   FAIL: {$fail}\n");

// Cleanup synthetic accounts (leave the schema for re-runs).
audit_cleanup($pdo);

exit($fail > 0 ? 1 : 0);
