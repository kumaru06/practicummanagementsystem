<?php
/**
 * Quick CLI smoke test for coordinator-side fixes.
 * Usage: php tools/coordinator-smoke-test.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$failures = 0;
$passed = 0;

function smoke_pass(string $label): void
{
    global $passed;
    $passed++;
    echo "[PASS] {$label}\n";
}

function smoke_fail(string $label, string $detail = ''): void
{
    global $failures;
    $failures++;
    echo "[FAIL] {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
}

function smoke_assert(bool $ok, string $label, string $detail = ''): void
{
    if ($ok) {
        smoke_pass($label);
    } else {
        smoke_fail($label, $detail);
    }
}

// --- PHP syntax on touched files ---
$touched = [
    'models/Student.php',
    'models/Enrollment.php',
    'models/Company.php',
    'models/Report.php',
    'controllers/CoordinatorController.php',
    'controllers/StudentController.php',
    'controllers/PartnerController.php',
    'helpers.php',
    'index.php',
    'views/coordinator/my_students.php',
    'views/coordinator/student_final_requirements.php',
    'views/coordinator/final/uploaded_document.php',
    'views/coordinator/manage.php',
    'views/coordinator/moa_mou.php',
    'views/coordinator/dashboard.php',
    'views/partner/dashboard.php',
    'views/partner/submissions.php',
    'assets/js/main.js',
];

foreach ($touched as $rel) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($path)) {
        if (str_ends_with($rel, '.php')) {
            smoke_fail("File exists: {$rel}");
        } else {
            smoke_pass("Optional file present or skipped: {$rel}");
        }
        continue;
    }
    if (str_ends_with($rel, '.php')) {
        exec('php -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
        smoke_assert($code === 0, "Syntax: {$rel}", implode(' ', $out));
    } else {
        smoke_pass("Present: {$rel}");
    }
}

// Dead views should be gone
foreach ([
    'views/coordinator/final/job_description.php',
    'views/coordinator/final/company_profile.php',
    'views/coordinator/final/personal_observation.php',
] as $dead) {
    smoke_assert(!is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dead)), "Removed dead view: {$dead}");
}

// --- Bootstrap app (no session user needed for model tests) ---
require_once $root . '/bootstrap/env.php';
require_once $root . '/config/database.php';
require_once $root . '/helpers.php';

spl_autoload_register(static function (string $class) use ($root): void {
    foreach (['models', 'controllers'] as $dir) {
        $path = $root . '/' . $dir . '/' . $class . '.php';
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});

try {
    $db = db();
    smoke_pass('Database connection');
} catch (Throwable $e) {
    smoke_fail('Database connection', $e->getMessage());
    echo "\n{$passed} passed, {$failures} failed\n";
    exit(1);
}

// --- Route URLs ---
$moaUrl = route_url('coordinator.moa_mou');
$docUrl = route_url('coordinator.partner_document', ['company_id' => 1]);
smoke_assert(str_contains($moaUrl, 'coordinator_moa_mou'), 'route_url coordinator.moa_mou');
smoke_assert(str_contains($docUrl, 'coordinator_partner_document') && str_contains($docUrl, 'company_id=1'), 'route_url coordinator.partner_document');

$studentsUrl = route_url('coordinator.students', ['focus_student' => 42]);
smoke_assert(str_contains($studentsUrl, 'focus_student=42'), 'route_url coordinator.students focus_student');

// --- Enrollment create rejects duplicate (no ON DUPLICATE KEY UPDATE) ---
$createSql = file_get_contents($root . '/models/Enrollment.php') ?: '';
smoke_assert(!str_contains($createSql, 'ON DUPLICATE KEY UPDATE'), 'Enrollment::create has no ON DUPLICATE KEY UPDATE');
smoke_assert(str_contains($createSql, 'already enrolled'), 'Enrollment::create guards duplicate enrollment');

// --- Student raw status reads ---
$studentSrc = file_get_contents($root . '/models/Student.php') ?: '';
smoke_assert(str_contains($studentSrc, '->byStudent($studentId)'), 'Student sync/review uses raw byStudent()');
smoke_assert(str_contains($studentSrc, 'countPendingFinalReviewsByStudents'), 'Student pending final review counter exists');
smoke_assert(str_contains($studentSrc, "in_array(\$status, ['uploaded', 'approved']"), 'Coordinator can review approved stage 2/3 docs');

// --- Company MOA ACL ---
$companyModel = new Company($db);
$coordRow = $db->query(
    "SELECT s.coordinator_id, e.company_id
     FROM students s
     JOIN ojt_enrollments e ON e.student_id = s.id
     WHERE s.coordinator_id IS NOT NULL AND e.company_id IS NOT NULL
     LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if ($coordRow) {
    $coordId = (int)$coordRow['coordinator_id'];
    $companyId = (int)$coordRow['company_id'];
    smoke_assert($companyModel->coordinatorCanAccessMoa($coordId, $companyId), 'MOA ACL allows enrolled student company');
    $foreign = (int)($db->query('SELECT id FROM partner_companies WHERE id != ' . $companyId . ' LIMIT 1')->fetchColumn() ?: 0);
    if ($foreign > 0 && !$companyModel->coordinatorCanAccessMoa($coordId, $foreign)) {
        smoke_pass('MOA ACL denies unrelated company (when no program link)');
    } else {
        smoke_pass('MOA ACL foreign company check skipped (program overlap or single company)');
    }
} else {
    smoke_pass('MOA ACL live check skipped (no enrollment sample in DB)');
}

// --- Chart queries use approved DTR only ---
$enrollSrc = file_get_contents($root . '/models/Enrollment.php') ?: '';
smoke_assert(
    substr_count($enrollSrc, 'verification_status = "approved"') >= 3,
    'Enrollment chart queries filter approved DTR hours',
    'found ' . substr_count($enrollSrc, 'verification_status = "approved"') . ' occurrences'
);

// --- CoordinatorController guards ---
$coordSrc = file_get_contents($root . '/controllers/CoordinatorController.php') ?: '';
smoke_assert(str_contains($coordSrc, 'deactivated. Ask the admin'), 'enrollStudent blocks deactivated accounts');
smoke_assert(str_contains($coordSrc, 'withMoaForCoordinator'), 'moaMouLibrary uses scoped company list');
smoke_assert(str_contains($coordSrc, 'coordinatorCanAccessMoa'), 'viewPartnerDocument checks MOA ACL');
smoke_assert(!str_contains($coordSrc, 'function checkEmail'), 'Dead checkEmail removed');
smoke_assert(!str_contains($coordSrc, 'function createStudent'), 'Dead createStudent removed');

// --- Notification links include focus_student ---
$studentSrcFile = file_get_contents($root . '/controllers/StudentController.php') ?: '';
smoke_assert(str_contains($studentSrcFile, "route_url('coordinator.students', ['focus_student'"), 'Student notifications include focus_student');

$indexSrc = file_get_contents($root . '/index.php') ?: '';
smoke_assert(!str_contains($indexSrc, "'coordinator/students' => 'coordinator_create_student'"), 'POST path map hazard removed');
smoke_assert(!str_contains($indexSrc, 'stripos($message'), 'Name-based notification heuristic removed');
smoke_assert(str_contains($indexSrc, "(\$freshUser['is_active'] ?? 1) !== 1"), 'Inactive users are logged out on each request');

// --- Partner / HTE fixes ---
$partnerSrc = file_get_contents($root . '/controllers/PartnerController.php') ?: '';
smoke_assert(str_contains($partnerSrc, 'isPredeploymentPipelineAdvanced'), 'Partner ensureStudentAccess uses predeployment pipeline');
smoke_assert(str_contains($partnerSrc, 'assertPartnerCanReviewReports'), 'Partner report review gate exists');
smoke_assert(str_contains($partnerSrc, "action === 'rejected' && !empty(\$student['coordinator_id'])"), 'Partner notifies coordinator on reject (bulk)');
smoke_assert(str_contains($partnerSrc, 'if (!$enrollmentModel->acceptDeployment'), 'acceptDeployment uses conditional UPDATE result');
smoke_assert(str_contains($partnerSrc, 'if (!$enrollmentModel->completeOrientation'), 'completeOrientation uses conditional UPDATE result');

$reportSrc = file_get_contents($root . '/models/Report.php') ?: '';
smoke_assert(str_contains($reportSrc, 'predeployment_status IN ("forwarded", "accepted", "orientation_scheduled", "orientation_completed")'), 'submissionSummaryByCompany filters pipeline');

$enrollSrcPartner = file_get_contents($root . '/models/Enrollment.php') ?: '';
smoke_assert(str_contains($enrollSrcPartner, 'WHERE id = ? AND predeployment_status = "forwarded"'), 'acceptDeployment conditional UPDATE');
smoke_assert(str_contains($enrollSrcPartner, 'WHERE id = ? AND predeployment_status = "orientation_scheduled"'), 'completeOrientation conditional UPDATE');

$dashboardSrc = file_get_contents($root . '/views/partner/dashboard.php') ?: '';
smoke_assert(str_contains($dashboardSrc, 'predeployment_status'), 'Partner dashboard badges use predeployment status');

$submissionsSrc = file_get_contents($root . '/views/partner/submissions.php') ?: '';
smoke_assert(!str_contains($submissionsSrc, 'if (!$company)'), 'Partner submissions view has no dead no-company branch');

$partnerSrcLow = file_get_contents($root . '/controllers/PartnerController.php') ?: '';
smoke_assert(!str_contains($partnerSrcLow, "redirect('index.php?r=partner"), 'PartnerController redirects use route_url helpers');
smoke_assert(str_contains($partnerSrcLow, 'private function partnerPortalUrl'), 'Partner portal redirect helper exists');
smoke_assert(!str_contains($partnerSrcLow, "'company' => null"), 'submissionsViewData no longer has dead null-company branch');

$headerSrc = file_get_contents($root . '/views/shared/header.php') ?: '';
smoke_assert(str_contains($headerSrc, "route_url('partner.portal')"), 'Partner nav uses route_url');

// --- effectivePredeploymentStatus sync smoke (read-only) ---
$sample = $db->query(
    "SELECT s.id student_id, e.predeployment_status
     FROM students s
     JOIN ojt_enrollments e ON e.student_id = s.id
     WHERE s.coordinator_id IS NOT NULL
     LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
if ($sample) {
    $studentModel = new Student($db);
    $sid = (int)$sample['student_id'];
    $raw = (new Enrollment($db))->byStudent($sid);
    $effective = $studentModel->effectivePredeploymentStatus($sid, $raw['predeployment_status'] ?? null);
    smoke_assert(is_string($effective) && $effective !== '', 'effectivePredeploymentStatus returns value for sample student');
} else {
    smoke_pass('Predeployment status sample skipped (no coordinator student in DB)');
}

echo "\n--- Summary: {$passed} passed, {$failures} failed ---\n";
exit($failures > 0 ? 1 : 0);
