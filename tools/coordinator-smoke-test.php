<?php
/**
 * Quick CLI smoke test for coordinator + HTE/partner fixes.
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
    'views/partner/portal.php',
    'views/partner/timeline.php',
    'views/partner/reports.php',
    'views/partner/evaluations.php',
    'views/partner/student_evaluation.php',
    'views/partner/evaluate.php',
    'views/partner/settings.php',
    'models/Evaluation.php',
    'models/StudentEvaluation.php',
    'controllers/ChatController.php',
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
smoke_assert(str_contains($headerSrc, "route_url('partner.timeline')"), 'Partner nav includes timeline route');
smoke_assert(str_contains($headerSrc, "route_url('partner.reports')"), 'Partner nav includes reports route');
smoke_assert(str_contains($headerSrc, "route_url('partner.evaluations')"), 'Partner nav includes evaluations route');
smoke_assert(str_contains($headerSrc, "route_url('chat')"), 'Partner chat nav uses route_url');
smoke_assert(!str_contains($headerSrc, 'notif-panel-gear'), 'Decorative notification settings gear removed');

$helpersSrc = file_get_contents($root . '/helpers.php') ?: '';
smoke_assert(str_contains($helpersSrc, 'function build_partner_timeline_entries'), 'build_partner_timeline_entries helper exists');
smoke_assert(str_contains($helpersSrc, 'function partner_enrollment_is_active_ojt'), 'partner_enrollment_is_active_ojt helper exists');
smoke_assert(str_contains($helpersSrc, 'function partner_enrollment_pipeline_step'), 'partner_enrollment_pipeline_step helper exists');
smoke_assert(str_contains($helpersSrc, "'partner.timeline'"), 'route_url partner.timeline registered');
smoke_assert(str_contains($helpersSrc, "'partner.reports'"), 'route_url partner.reports registered');
smoke_assert(str_contains($helpersSrc, "'partner.evaluations'"), 'route_url partner.evaluations registered');
smoke_assert(str_contains($helpersSrc, "'partner.student_evaluation'"), 'route_url partner.student_evaluation registered');

smoke_assert(str_contains($indexSrc, "'partner_timeline'"), 'index partner_timeline route registered');
smoke_assert(str_contains($indexSrc, "'partner_reports'"), 'index partner_reports route registered');
smoke_assert(str_contains($indexSrc, "'partner_evaluations'"), 'index partner_evaluations route registered');
smoke_assert(str_contains($indexSrc, "'partner_student_evaluation'"), 'index partner_student_evaluation route registered');
smoke_assert(str_contains($indexSrc, "'partner_export_reports'"), 'index partner_export_reports route registered');

smoke_assert(str_contains($partnerSrc, 'function timeline()'), 'PartnerController timeline action exists');
smoke_assert(str_contains($partnerSrc, 'function reports()'), 'PartnerController reports action exists');
smoke_assert(str_contains($partnerSrc, 'function evaluations()'), 'PartnerController evaluations action exists');
smoke_assert(str_contains($partnerSrc, 'function studentEvaluation()'), 'PartnerController studentEvaluation action exists');
smoke_assert(str_contains($partnerSrc, 'function exportReports()'), 'PartnerController exportReports action exists');
smoke_assert(str_contains($partnerSrc, 'validateReviewNotes'), 'Partner reject notes validation exists');
smoke_assert(str_contains($partnerSrc, 'submissionStatsFromSummaries'), 'Partner dashboard submission stats helper exists');
smoke_assert(str_contains($partnerSrc, 'enrichSubmissionSummaries'), 'Partner submission summaries enrichment exists');
smoke_assert(str_contains($partnerSrc, 'partner_enrollment_is_active_ojt'), 'Partner dashboard uses active OJT helper');

$reportSrcExt = file_get_contents($root . '/models/Report.php') ?: '';
smoke_assert(str_contains($reportSrcExt, 'companyStudentHoursSummary'), 'Report companyStudentHoursSummary exists');

$evalSrc = file_get_contents($root . '/models/Evaluation.php') ?: '';
smoke_assert(str_contains($evalSrc, 'function byCompany'), 'Evaluation byCompany exists');

$studentEvalSrc = file_get_contents($root . '/models/StudentEvaluation.php') ?: '';
smoke_assert(str_contains($studentEvalSrc, 'submittedPartnerEvaluationsByCompany'), 'StudentEvaluation company feedback query exists');

$companySrc = file_get_contents($root . '/models/Company.php') ?: '';
smoke_assert(str_contains($companySrc, 'findByUserWithPrograms'), 'Company findByUserWithPrograms exists');

$chatSrc = file_get_contents($root . '/controllers/ChatController.php') ?: '';
smoke_assert(str_contains($chatSrc, 'dedupeChatPartners'), 'Chat contact deduplication exists');

smoke_assert(str_contains($studentSrcFile, "route_url('partner.student_evaluation'"), 'Student eval notifies HTE partner');
smoke_assert(str_contains($studentSrcFile, "route_url('coordinator.student_final'"), 'Student eval coordinator link uses route_url');

smoke_assert(str_contains($dashboardSrc, 'submissionStats'), 'Partner dashboard shows submission stats');
smoke_assert(str_contains($dashboardSrc, 'pd-attention-banner'), 'Partner dashboard pending review banner exists');
smoke_assert(str_contains($dashboardSrc, 'pd-stat-card--completed'), 'Partner dashboard completed KPI exists');

$portalSrc = file_get_contents($root . '/views/partner/portal.php') ?: '';
smoke_assert(str_contains($portalSrc, 'partner_enrollment_pipeline_step'), 'Partner portal uses pipeline helper');
smoke_assert(str_contains($portalSrc, 'partner_enrollment_is_active_ojt'), 'Partner portal uses active OJT helper');
smoke_assert(str_contains($portalSrc, 'route_url(\'partner.submissions\''), 'Partner portal links to submissions review');

$submissionsDetailSrc = file_get_contents($root . '/views/partner/submissions_detail.php') ?: '';
smoke_assert(str_contains($submissionsDetailSrc, 'reports_unlocked'), 'Submissions detail handles locked students');
smoke_assert(str_contains($submissionsDetailSrc, 'data-ps-bulk-notes'), 'Bulk reject requires notes field');

$settingsSrc = file_get_contents($root . '/views/partner/settings.php') ?: '';
smoke_assert(str_contains($settingsSrc, 'Accepted Programs'), 'Partner settings shows accepted programs');
smoke_assert(str_contains($settingsSrc, 'hte-settings'), 'Partner settings uses hte- CSS prefix');

$evaluateSrc = file_get_contents($root . '/views/partner/evaluate.php') ?: '';
smoke_assert(str_contains($evaluateSrc, 'data-partner-eval-form'), 'Partner evaluate form uses shared JS hook');
smoke_assert(!str_contains($evaluateSrc, '<style>'), 'Partner evaluate has no inline CSS');

$mainJsSrc = file_get_contents($root . '/assets/js/main.js') ?: '';
smoke_assert(str_contains($mainJsSrc, 'initPartnerSubmissionReview'), 'main.js partner submission review init exists');
smoke_assert(str_contains($mainJsSrc, 'initPartnerEvaluationForm'), 'main.js partner evaluation form init exists');
smoke_assert(str_contains($mainJsSrc, 'partnerBulkRejectConfirm'), 'main.js bulk reject confirm helper exists');

// --- Partner helper smoke (no session) ---
$timelineEntries = build_partner_timeline_entries(null, [], [], null);
smoke_assert(is_array($timelineEntries) && count($timelineEntries) === 0, 'build_partner_timeline_entries empty input returns array');
smoke_assert(partner_enrollment_pipeline_step(['predeployment_status' => 'forwarded', 'status' => 'active'], null) >= 0, 'partner_enrollment_pipeline_step returns step index');
smoke_assert(!partner_enrollment_is_active_ojt(['predeployment_status' => 'forwarded', 'status' => 'completed']), 'partner_enrollment_is_active_ojt rejects completed');

$timelineUrl = route_url('partner.timeline');
$reportsUrl = route_url('partner.reports');
$evaluationsUrl = route_url('partner.evaluations');
$studentEvalUrl = route_url('partner.student_evaluation', ['student_id' => 7]);
smoke_assert(str_contains($timelineUrl, 'partner_timeline'), 'route_url partner.timeline');
smoke_assert(str_contains($reportsUrl, 'partner_reports'), 'route_url partner.reports');
smoke_assert(str_contains($evaluationsUrl, 'partner_evaluations'), 'route_url partner.evaluations');
smoke_assert(str_contains($studentEvalUrl, 'partner_student_evaluation') && str_contains($studentEvalUrl, 'student_id=7'), 'route_url partner.student_evaluation');

// --- Partner live DB smoke (read-only) ---
$partnerRow = $db->query(
    "SELECT pc.id company_id, pc.user_id
     FROM partner_companies pc
     WHERE pc.user_id IS NOT NULL
     LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
if ($partnerRow) {
    $companyId = (int)$partnerRow['company_id'];
    $userId = (int)$partnerRow['user_id'];

    $hoursSummary = (new Report($db))->companyStudentHoursSummary($companyId);
    smoke_assert(is_array($hoursSummary), 'Report::companyStudentHoursSummary returns array');

    $partnerEvals = (new Evaluation($db))->byCompany($companyId);
    smoke_assert(is_array($partnerEvals), 'Evaluation::byCompany returns array');

    $studentEvals = (new StudentEvaluation($db))->submittedPartnerEvaluationsByCompany($companyId);
    smoke_assert(is_array($studentEvals), 'StudentEvaluation::submittedPartnerEvaluationsByCompany returns array');

    $companyWithPrograms = (new Company($db))->findByUserWithPrograms($userId);
    smoke_assert(is_array($companyWithPrograms) && array_key_exists('accepted_programs', $companyWithPrograms), 'Company::findByUserWithPrograms includes accepted_programs');

    $submissionSummaries = (new Report($db))->submissionSummaryByCompany($companyId);
    smoke_assert(is_array($submissionSummaries), 'Report::submissionSummaryByCompany returns array');
    if ($submissionSummaries !== []) {
        $first = $submissionSummaries[0];
        smoke_assert(array_key_exists('student_id', $first) && array_key_exists('pending_dtr', $first), 'submissionSummaryByCompany row shape');
    }
} else {
    smoke_pass('Partner live DB checks skipped (no partner company in DB)');
}

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
