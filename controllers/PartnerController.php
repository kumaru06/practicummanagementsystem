<?php
class PartnerController extends BaseController
{
    public function dashboard(): void
    {
        require_role('partner');
        $company = $this->requireCompanyProfile();
        $enroll = new Enrollment($this->db);
        $students = $enroll->deployedByCompany((int)$company['id']);

        $stats = [
            'total' => count($students),
            'pending' => 0,
            'active' => 0,
            'orientation' => 0,
            'completed' => 0,
        ];
        foreach ($students as $student) {
            $status = (string)($student['status'] ?? '');
            $predeployment = (string)($student['predeployment_status'] ?? '');
            if ($status === 'completed') {
                $stats['completed']++;
            } elseif (partner_enrollment_is_active_ojt($student)) {
                $stats['active']++;
            }
            if (in_array($predeployment, ['accepted', 'orientation_scheduled'], true)) {
                $stats['orientation']++;
            }
            // Actionable "pending" = docs forwarded and waiting for partner acceptance.
            if ($predeployment === 'forwarded') {
                $stats['pending']++;
            }
        }

        $studentSummaries = $this->enrichSubmissionSummaries(
            (new Report($this->db))->submissionSummaryByCompany((int)$company['id']),
            $company
        );
        $submissionStats = $this->submissionStatsFromSummaries($studentSummaries);
        $pendingReviewStudents = array_values(array_filter(
            $studentSummaries,
            static fn(array $row): bool => !empty($row['reports_unlocked'])
                && ((int)($row['pending_dtr'] ?? 0) + (int)($row['pending_weekly'] ?? 0)) > 0
        ));

        $this->renderAppPage('partner/dashboard', [
            'title' => 'Host Training Establishment Dashboard',
            'company' => $company,
            'students' => $students,
            'stats' => $stats,
            'submissionStats' => $submissionStats,
            'pendingReviewStudents' => $pendingReviewStudents,
        ]);
    }

    /**
     * Submissions hub: lists all students for this partner with pending counts,
     * and (if ?student_id=X provided) shows the per-student detail with DTR + Weekly tabs.
     */
    public function submissions(): void
    {
        require_role('partner');
        $company = $this->requireCompanyProfile();
        $data = $this->submissionsViewData($company);

        if ($this->wantsSubmissionsPartial()) {
            header('Content-Type: text/html; charset=utf-8');
            $this->renderPartial('partner/submissions_detail', $data);
            return;
        }

        $this->renderAppPage('partner/submissions', array_merge($data, [
            'title' => 'Student Submissions',
        ]));
    }

    private function wantsSubmissionsPartial(): bool
    {
        $partial = strtolower(trim((string)($_GET['partial'] ?? '')));
        if ($partial === 'content') {
            return false;
        }
        return $this->isAjaxRequest() || in_array($partial, ['detail', '1', 'true'], true);
    }

    /** @return array<string, mixed> */
    private function submissionsViewData(array $company): array
    {
        $reportModel = new Report($this->db);
        $studentSummaries = $this->enrichSubmissionSummaries(
            $reportModel->submissionSummaryByCompany((int)$company['id']),
            $company
        );

        $selectedStudentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
        $selectedStudent = null;
        $studentDtrs = [];
        $studentWeeklies = [];

        if ($selectedStudentId > 0) {
            foreach ($studentSummaries as $row) {
                if ((int)$row['student_id'] === $selectedStudentId) {
                    $selectedStudent = $row;
                    break;
                }
            }
            if ($selectedStudent) {
                $studentDtrs = $reportModel->dtrByStudent($selectedStudentId);
                $studentWeeklies = $reportModel->weeklyByStudent($selectedStudentId);
                foreach ($studentWeeklies as &$wr) {
                    $wr['proof_files'] = $reportModel->proofFilesByReport((int)$wr['id']);
                }
                unset($wr);
            }
        }

        $activeTab = ($_GET['tab'] ?? 'dtr') === 'weekly' ? 'weekly' : 'dtr';

        return [
            'company' => $company,
            'studentSummaries' => $studentSummaries,
            'selectedStudent' => $selectedStudent,
            'studentDtrs' => $studentDtrs,
            'studentWeeklies' => $studentWeeklies,
            'activeTab' => $activeTab,
            'statusFilter' => $this->submissionsStatusFilter(),
        ];
    }

    private function submissionsStatusFilter(): string
    {
        $status = strtolower(trim((string)($_GET['status'] ?? 'pending')));
        return in_array($status, ['all', 'pending', 'approved', 'rejected'], true) ? $status : 'pending';
    }

    public function reviewDtr(): void
    {
        require_role('partner');
        $p = $this->post();
        $action = $p['decision'] ?? '';
        $dtrId = (int)($p['dtr_id'] ?? 0);
        $studentId = (int)($p['student_id'] ?? 0);
        $notes = trim($p['notes'] ?? '');

        try {
            if (!in_array($action, ['approved', 'rejected'], true)) {
                throw new RuntimeException('Invalid decision.');
            }
            $this->validateReviewNotes($action, $notes);
            $this->ensureRecordOwnership($studentId, $dtrId, 'dtr');

            $report = new Report($this->db);
            $report->setDtrVerification($dtrId, $action, (int)current_user()['id'], $notes ?: null);

            if ($action === 'approved') {
                (new Enrollment($this->db))->syncCompletion($studentId);
            }

            $this->notifyStudentAndCoordinator($studentId, 'dtr', $action, $notes);

            flash('success', 'Daily Time Record ' . $action . '.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect($this->partnerSubmissionsUrl($studentId, 'dtr'));
    }

    public function reviewWeekly(): void
    {
        require_role('partner');
        $p = $this->post();
        $action = $p['decision'] ?? '';
        $weeklyId = (int)($p['weekly_id'] ?? 0);
        $studentId = (int)($p['student_id'] ?? 0);
        $notes = trim($p['notes'] ?? '');

        try {
            if (!in_array($action, ['approved', 'rejected'], true)) {
                throw new RuntimeException('Invalid decision.');
            }
            $this->validateReviewNotes($action, $notes);
            $this->ensureRecordOwnership($studentId, $weeklyId, 'weekly');

            $report = new Report($this->db);
            $report->setWeeklyVerification($weeklyId, $action, (int)current_user()['id'], $notes ?: null);

            $this->notifyStudentAndCoordinator($studentId, 'weekly', $action, $notes);

            flash('success', 'Weekly report ' . $action . '.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect($this->partnerSubmissionsUrl($studentId, 'weekly'));
    }

    public function bulkReviewDtr(): void
    {
        require_role('partner');
        $p = $this->post();
        $action = $p['decision'] ?? '';
        $studentId = (int)($p['student_id'] ?? 0);
        $notes = trim($p['notes'] ?? '');

        try {
            if (!in_array($action, ['approved', 'rejected'], true)) {
                throw new RuntimeException('Invalid decision.');
            }
            $this->validateReviewNotes($action, $notes);
            $this->ensureStudentAccess($studentId);
            $this->assertPartnerCanReviewReports($studentId);

            $report = new Report($this->db);
            $count = 0;
            foreach ($report->dtrByStudent($studentId) as $d) {
                if (($d['verification_status'] ?? '') !== 'pending') {
                    continue;
                }
                $report->setDtrVerification((int)$d['id'], $action, (int)current_user()['id'], $notes ?: null);
                $count++;
            }
            if ($count === 0) {
                throw new RuntimeException('No pending daily time records to review.');
            }
            if ($action === 'approved') {
                (new Enrollment($this->db))->syncCompletion($studentId);
            }
            $this->notifyStudentBulkReview($studentId, 'dtr', $action, $count, $notes);
            flash('success', $count . ' daily time record(s) ' . $action . '.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect($this->partnerSubmissionsUrl($studentId, 'dtr'));
    }

    public function bulkReviewWeekly(): void
    {
        require_role('partner');
        $p = $this->post();
        $action = $p['decision'] ?? '';
        $studentId = (int)($p['student_id'] ?? 0);
        $notes = trim($p['notes'] ?? '');

        try {
            if (!in_array($action, ['approved', 'rejected'], true)) {
                throw new RuntimeException('Invalid decision.');
            }
            $this->validateReviewNotes($action, $notes);
            $this->ensureStudentAccess($studentId);
            $this->assertPartnerCanReviewReports($studentId);

            $report = new Report($this->db);
            $count = 0;
            foreach ($report->weeklyByStudent($studentId) as $w) {
                if (($w['verification_status'] ?? '') !== 'pending') {
                    continue;
                }
                $report->setWeeklyVerification((int)$w['id'], $action, (int)current_user()['id'], $notes ?: null);
                $count++;
            }
            if ($count === 0) {
                throw new RuntimeException('No pending weekly reports to review.');
            }
            $this->notifyStudentBulkReview($studentId, 'weekly', $action, $count, $notes);
            flash('success', $count . ' weekly report(s) ' . $action . '.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect($this->partnerSubmissionsUrl($studentId, 'weekly'));
    }

    private function canSubmitFinalEvaluation(array $enrollment): bool
    {
        $status = (string)($enrollment['status'] ?? '');
        $predeployment = (string)($enrollment['predeployment_status'] ?? '');
        return in_array($status, ['active', 'completed'], true)
            && ($predeployment === 'orientation_completed' || $status === 'completed');
    }

    private function ensureStudentAccess(int $studentId): void
    {
        $company = (new Company($this->db))->findByUser(current_user()['id']);
        if (!$company) {
            throw new RuntimeException('Host Training Establishment profile not found.');
        }
        $enrollment = (new Enrollment($this->db))->byStudent($studentId);
        if (!$enrollment || (int)$enrollment['company_id'] !== (int)$company['id']) {
            throw new RuntimeException('You do not have access to this student.');
        }
        $studentModel = new Student($this->db);
        $predeployment = $studentModel->effectivePredeploymentStatus(
            $studentId,
            $enrollment['predeployment_status'] ?? null
        );
        if (!$studentModel->isPredeploymentPipelineAdvanced($predeployment)
            && ($enrollment['status'] ?? '') !== 'completed') {
            throw new RuntimeException('This student is not available yet. Documents must be forwarded by the coordinator first.');
        }
    }

    private function assertPartnerCanReviewReports(int $studentId): void
    {
        $enrollment = (new Enrollment($this->db))->detailsByStudent($studentId);
        if (!$enrollment || !enrollment_allows_reports($enrollment)) {
            throw new RuntimeException(enrollment_report_lock_message($enrollment));
        }
    }

    private function notifyStudentBulkReview(int $studentId, string $type, string $action, int $count, string $notes = ''): void
    {
        $student = (new Student($this->db))->find($studentId);
        if (!$student) {
            return;
        }
        $notifications = new Notification($this->db);
        $label = $type === 'dtr' ? 'Daily Time Records' : 'Weekly Reports';
        $title = $count . ' ' . $label . ' ' . ($action === 'approved' ? 'approved' : 'rejected');
        $message = $count . ' ' . strtolower($label) . ' were ' . $action . ' by your Host Training Establishment.';
        if ($action === 'rejected' && $notes !== '') {
            $message .= ' Notes: ' . $notes;
        }
        $notifications->create((int)$student['user_id'], $title, $message, route_url('student.records'));

        if ($action === 'approved' && !empty($student['coordinator_id'])) {
            $notifications->create(
                (int)$student['coordinator_id'],
                $label . ' approved by Host Training Establishment',
                $count . ' ' . strtolower($label) . ' for ' . $student['name'] . ' were approved by the Host Training Establishment.',
                route_url('coordinator.students', ['focus_student' => $studentId])
            );
        } elseif ($action === 'rejected' && !empty($student['coordinator_id'])) {
            $notifications->create(
                (int)$student['coordinator_id'],
                $label . ' rejected by Host Training Establishment',
                $count . ' ' . strtolower($label) . ' for ' . $student['name'] . ' were rejected by the Host Training Establishment.'
                    . ($notes !== '' ? ' Notes: ' . $notes : ''),
                route_url('coordinator.students', ['focus_student' => $studentId])
            );
        }
    }

    private function validateReviewNotes(string $action, string $notes): void
    {
        if ($action === 'rejected' && trim($notes) === '') {
            throw new RuntimeException('Rejection notes are required. Explain what the student needs to correct.');
        }
    }

    private function ensureRecordOwnership(int $studentId, int $recordId, string $type): void
    {
        $this->ensureStudentAccess($studentId);
        $this->assertPartnerCanReviewReports($studentId);
        $report = new Report($this->db);
        $record = $type === 'dtr' ? $report->findDtr($recordId) : $report->findWeekly($recordId);
        if (!$record || (int)$record['student_id'] !== $studentId) {
            throw new RuntimeException('Record not found for this student.');
        }
    }

    private function notifyStudentAndCoordinator(int $studentId, string $type, string $action, string $notes): void
    {
        $student = (new Student($this->db))->find($studentId);
        if (!$student) return;
        $notifications = new Notification($this->db);
        $label = $type === 'dtr' ? 'Daily Time Record' : 'Weekly Report';
        $title = $label . ' ' . ($action === 'approved' ? 'approved' : 'rejected');
        $message = 'Your ' . $label . ' was ' . $action . ' by your Host Training Establishment' . ($notes !== '' ? ': ' . $notes : '.');

        $notifications->create((int)$student['user_id'], $title, $message, route_url('student.records'));

        if (!empty($student['coordinator_id'])) {
            $coordTitle = $label . ' ' . $action . ' by Host Training Establishment';
            $coordMessage = $student['name'] . '\'s ' . $label . ' has been ' . $action . ' by the Host Training Establishment.';
            if ($action === 'rejected' && $notes !== '') {
                $coordMessage .= ' Notes: ' . $notes;
            }
            $notifications->create(
                (int)$student['coordinator_id'],
                $coordTitle,
                $coordMessage,
                route_url('coordinator.students', ['focus_student' => $studentId])
            );
        }
    }

    public function portal(): void
    {
        require_role('partner');
        $company = $this->requireCompanyProfile();
        $enroll = new Enrollment($this->db);
        $students = $enroll->deployedByCompany((int)$company['id']);
        $selected = isset($_GET['enrollment']) ? $enroll->find((int)$_GET['enrollment']) : null;
        if ($selected && (int)$selected['company_id'] !== (int)$company['id']) {
            $selected = null; // deny cross-company access
        }
        if ($selected) {
            $studentModel = new Student($this->db);
            $visible = $studentModel->isPredeploymentPipelineAdvanced($selected['predeployment_status'] ?? null)
                || ($selected['status'] ?? '') === 'completed';
            if (!$visible) {
                flash('error', 'This student is not available yet. Documents must be forwarded by the coordinator first.');
                redirect(route_url('partner.portal'));
            }
        }
        $dtrs = [];
        $weeklies = [];
        $evaluation = null;
        $studentEvaluation = [];
        $reportsUnlocked = false;
        $pendingDtrCount = 0;
        $pendingWeeklyCount = 0;
        if ($selected) {
            $reportModel = new Report($this->db);
            $dtrs = $reportModel->dtrByStudent((int)$selected['student_id']);
            $weeklies = $reportModel->weeklyByStudent((int)$selected['student_id']);
            $evaluation = (new Evaluation($this->db))->byEnrollment((int)$selected['id']);
            $studentEvaluation = (new StudentEvaluation($this->db))->getByStudent((int)$selected['student_id']);
            $reportsUnlocked = enrollment_allows_reports($selected);
            foreach ($dtrs as $dtr) {
                if (($dtr['verification_status'] ?? '') === 'pending') {
                    $pendingDtrCount++;
                }
            }
            foreach ($weeklies as $weekly) {
                if (($weekly['verification_status'] ?? '') === 'pending') {
                    $pendingWeeklyCount++;
                }
            }
        }
        $this->renderAppPage('partner/portal', [
            'title' => 'Host Training Establishment Portal',
            'company' => $company,
            'students' => $students,
            'selected' => $selected,
            'dtrs' => $dtrs,
            'weeklies' => $weeklies,
            'evaluation' => $evaluation,
            'studentEvaluation' => $studentEvaluation,
            'studentEvalSubmitted' => StudentEvaluation::statusFor($studentEvaluation, 'industry_partner') === 'submitted',
            'reportsUnlocked' => $reportsUnlocked,
            'pendingDtrCount' => $pendingDtrCount,
            'pendingWeeklyCount' => $pendingWeeklyCount,
            'requirements' => $selected ? (new Student($this->db))->requirements((int)$selected['student_id']) : [],
        ]);
    }

    public function studentEvaluation(): void
    {
        require_role('partner');
        $company = $this->requireCompanyProfile();
        $studentId = (int)($_GET['student_id'] ?? 0);
        if ($studentId <= 0) {
            flash('error', 'Select a student to view their evaluation.');
            redirect(route_url('partner.portal'));
        }

        $student = null;
        foreach ((new Enrollment($this->db))->deployedByCompany((int)$company['id']) as $row) {
            if ((int)($row['student_id'] ?? 0) === $studentId) {
                $student = $row;
                break;
            }
        }
        if (!$student) {
            flash('error', 'Student not found or not assigned to your organization.');
            redirect(route_url('partner.portal'));
        }

        $studentEvaluation = (new StudentEvaluation($this->db))->getByStudent($studentId);
        if (StudentEvaluation::statusFor($studentEvaluation, 'industry_partner') !== 'submitted') {
            flash('error', 'This student has not submitted an evaluation of your organization yet.');
            redirect(route_url('partner.portal', ['enrollment' => (int)$student['id']]));
        }

        $this->renderAppPage('partner/student_evaluation', [
            'title' => 'Student Evaluation - ' . ($student['student_name'] ?? 'Student'),
            'company' => $company,
            'student' => $student,
            'studentEvaluation' => $studentEvaluation,
        ]);
    }

    public function timeline(): void
    {
        require_role('partner');
        $company = $this->requireCompanyProfile();
        $students = (new Enrollment($this->db))->deployedByCompany((int)$company['id']);
        $selectedEnrollmentId = (int)($_GET['enrollment'] ?? 0);
        $selected = null;
        $timelineEntries = [];
        $timelineStats = [
            'entryCount' => 0,
            'milestoneCount' => 0,
            'dtrCount' => 0,
            'weeklyCount' => 0,
            'approvedHours' => 0.0,
        ];

        if ($selectedEnrollmentId > 0) {
            $selected = (new Enrollment($this->db))->find($selectedEnrollmentId);
            if (!$selected || (int)$selected['company_id'] !== (int)$company['id']) {
                flash('error', 'Student not found or not assigned to your organization.');
                redirect(route_url('partner.timeline'));
            }
        } elseif (!empty($students)) {
            $selected = (new Enrollment($this->db))->find((int)$students[0]['id']);
        }

        if ($selected) {
            $reportModel = new Report($this->db);
            $dtrs = $reportModel->dtrByStudent((int)$selected['student_id']);
            $weeklies = $reportModel->weeklyByStudent((int)$selected['student_id']);
            $evaluation = (new Evaluation($this->db))->byEnrollment((int)$selected['id']);
            $timelineEntries = build_partner_timeline_entries($selected, $dtrs, $weeklies, $evaluation);
            $timelineStats = [
                'entryCount' => count($timelineEntries),
                'milestoneCount' => count(array_filter($timelineEntries, static fn(array $e): bool => ($e['type'] ?? '') === 'milestone')),
                'dtrCount' => count($dtrs),
                'weeklyCount' => count($weeklies),
                'approvedHours' => array_sum(array_map(
                    static fn(array $d): float => (($d['verification_status'] ?? '') === 'approved') ? (float)($d['hours'] ?? 0) : 0.0,
                    $dtrs
                )),
            ];
        }

        $this->renderAppPage('partner/timeline', [
            'title' => 'Activity Timeline',
            'company' => $company,
            'students' => $students,
            'selected' => $selected,
            'timelineEntries' => $timelineEntries,
            'timelineStats' => $timelineStats,
        ]);
    }

    public function reports(): void
    {
        require_role('partner');
        $company = $this->requireCompanyProfile();
        $rows = (new Report($this->db))->companyStudentHoursSummary((int)$company['id']);

        $this->renderAppPage('partner/reports', [
            'title' => 'OJT Reports',
            'company' => $company,
            'rows' => $rows,
        ]);
    }

    public function exportReports(): void
    {
        require_role('partner');
        $company = $this->requireCompanyProfile();
        $rows = (new Report($this->db))->companyStudentHoursSummary((int)$company['id']);

        $filename = 'hte-ojt-report-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Student Name',
            'Student No',
            'Program',
            'Year Level',
            'Deployment Status',
            'Required Hours',
            'Approved Hours',
            'Pending DTR',
            'Pending Weekly',
            'Total DTR',
            'Total Weekly',
        ]);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['student_name'] ?? '',
                $row['student_no'] ?? '',
                $row['course'] ?? '',
                $row['year_level'] ?? '',
                $row['enrollment_status'] ?? '',
                $row['required_hours'] ?? '',
                number_format((float)($row['approved_hours'] ?? 0), 2, '.', ''),
                (int)($row['pending_dtr'] ?? 0),
                (int)($row['pending_weekly'] ?? 0),
                (int)($row['total_dtr'] ?? 0),
                (int)($row['total_weekly'] ?? 0),
            ]);
        }
        fclose($out);
        exit;
    }

    public function evaluations(): void
    {
        require_role('partner');
        $company = $this->requireCompanyProfile();
        $companyId = (int)$company['id'];

        $this->renderAppPage('partner/evaluations', [
            'title' => 'Evaluations History',
            'company' => $company,
            'finalEvaluations' => (new Evaluation($this->db))->byCompany($companyId),
            'studentFeedback' => (new StudentEvaluation($this->db))->submittedPartnerEvaluationsByCompany($companyId),
        ]);
    }

    public function acceptDeployment(): void
    {
        require_role('partner');
        $p = $this->post();
        $company = (new Company($this->db))->findByUser(current_user()['id']);
        $enrollment = (new Enrollment($this->db))->find((int)$p['enrollment_id']);
        if (!$company || !$enrollment || (int)$enrollment['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            exit('Forbidden');
        }
        if (($enrollment['predeployment_status'] ?? '') !== 'forwarded') {
            flash('error', 'Deployment can only be accepted after the coordinator forwards approved documents.');
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }
        $enrollmentModel = new Enrollment($this->db);
        if (!$enrollmentModel->acceptDeployment((int)$enrollment['id'])) {
            $refreshed = $enrollmentModel->find((int)$enrollment['id']);
            if (in_array($refreshed['predeployment_status'] ?? '', ['accepted', 'orientation_scheduled', 'orientation_completed'], true)) {
                flash('success', 'Deployment was already accepted.');
            } else {
                flash('error', 'Deployment could not be accepted. It may no longer be awaiting acceptance.');
            }
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }
        $studentDetails = (new Student($this->db))->find((int)$enrollment['student_id']);
        if ($studentDetails) {
            $notifications = new Notification($this->db);
            $notifications->create((int)$studentDetails['user_id'], 'Deployment accepted', $company['name'] . ' accepted your deployment documents.', route_url('student.dashboard'));
            if (!empty($studentDetails['coordinator_id'])) {
                $notifications->create((int)$studentDetails['coordinator_id'], 'Deployment accepted', $company['name'] . ' accepted ' . $studentDetails['name'] . '\'s deployment.', route_url('coordinator.students', ['focus_student' => (int)$studentDetails['id']]));
            }
        }
        flash('success', 'Deployment accepted. You can now schedule orientation.');
        redirect($this->partnerPortalUrl((int)$enrollment['id']));
    }

    public function scheduleOrientation(): void
    {
        require_role('partner');
        $p = $this->post();
        $company = (new Company($this->db))->findByUser(current_user()['id']);
        $enrollment = (new Enrollment($this->db))->find((int)$p['enrollment_id']);
        if (!$company || !$enrollment || (int)$enrollment['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            exit('Forbidden');
        }
        if (!in_array($enrollment['predeployment_status'] ?? '', ['accepted', 'orientation_scheduled'], true)) {
            flash('error', 'Orientation scheduling unlocks after deployment acceptance.');
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }
        if (empty($p['orientation_datetime']) || strtotime((string)$p['orientation_datetime']) === false) {
            flash('error', 'Enter a valid orientation date and time.');
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }
        $orientationError = validate_orientation_datetime((string)$p['orientation_datetime']);
        if ($orientationError !== null) {
            flash('error', $orientationError);
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }
        $orientationNotes = trim($p['orientation_notes'] ?? '');
        if ($orientationNotes === '') {
            flash('error', 'Orientation notes are required.');
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }
        (new Enrollment($this->db))->scheduleOrientation((int)$enrollment['id'], $p['orientation_datetime'], $orientationNotes);
        $email = new Email($this->db);
        $studentDetails = (new Student($this->db))->find((int)$enrollment['student_id']);
        $email->send($enrollment['student_email'], 'OJT Orientation Schedule', 'orientation_notice', 'orientation_notice', [
            'student' => $enrollment,
            'company' => $company,
            'orientationDateTime' => $p['orientation_datetime'],
            'notes' => $orientationNotes,
        ]);
        if (!empty($studentDetails['coordinator_email'])) {
            $email->send($studentDetails['coordinator_email'], 'OJT Orientation Schedule', 'orientation_notice', 'orientation_notice', [
                'student' => $studentDetails,
                'company' => $company,
                'orientationDateTime' => $p['orientation_datetime'],
                'notes' => $orientationNotes,
            ]);
        }
        if ($studentDetails) {
            $notifications = new Notification($this->db);
            $notifications->create((int)$studentDetails['user_id'], 'Orientation scheduled', $company['name'] . ' scheduled your OJT orientation.', route_url('student.timeline'));
            if (!empty($studentDetails['coordinator_id'])) {
                $notifications->create((int)$studentDetails['coordinator_id'], 'Orientation scheduled', $company['name'] . ' scheduled orientation for ' . $studentDetails['name'] . '.', route_url('coordinator.students', ['focus_student' => (int)$studentDetails['id']]));
            }
        }
        flash('success', 'Orientation scheduled and student notified.');
        redirect($this->partnerPortalUrl((int)$enrollment['id']));
    }

    public function completeOrientation(): void
    {
        require_role('partner');
        $p = $this->post();
        $company = (new Company($this->db))->findByUser(current_user()['id']);
        $enrollment = (new Enrollment($this->db))->find((int)$p['enrollment_id']);
        if (!$company || !$enrollment || (int)$enrollment['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            exit('Forbidden');
        }
        if (($enrollment['predeployment_status'] ?? '') !== 'orientation_scheduled') {
            flash('error', 'Complete orientation only after an orientation schedule is saved.');
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }
        if (empty($p['official_start_date']) || strtotime((string)$p['official_start_date']) === false) {
            flash('error', 'Enter a valid official OJT start date.');
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }
        $projectedEndDate = trim($p['projected_end_date'] ?? '') ?: projected_ojt_end_date($p['official_start_date'], (int)$enrollment['required_hours']);
        $officialStartError = validate_official_start_date($enrollment, (string)$p['official_start_date']);
        if ($officialStartError !== null) {
            flash('error', $officialStartError);
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }
        $projectedEndError = validate_projected_end_date((string)$p['official_start_date'], $projectedEndDate);
        if ($projectedEndError !== null) {
            flash('error', $projectedEndError);
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }
        $enrollmentModel = new Enrollment($this->db);
        if (!$enrollmentModel->completeOrientation((int)$enrollment['id'], $p['official_start_date'], $projectedEndDate)) {
            $refreshed = $enrollmentModel->find((int)$enrollment['id']);
            if (($refreshed['predeployment_status'] ?? '') === 'orientation_completed') {
                flash('success', 'Orientation was already completed.');
            } else {
                flash('error', 'Orientation could not be completed. Confirm an orientation schedule is saved first.');
            }
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }
        $email = new Email($this->db);
        $studentDetails = (new Student($this->db))->find((int)$enrollment['student_id']);
        $email->send($enrollment['student_email'], 'Your OJT Has Officially Started', 'ojt_started', 'ojt_started', [
            'student' => $enrollment,
            'company' => $company,
            'officialStartDate' => $p['official_start_date'],
            'projectedEndDate' => $projectedEndDate,
            'requiredHours' => (int)$enrollment['required_hours'],
        ]);
        if (!empty($studentDetails['coordinator_email'])) {
            $email->send($studentDetails['coordinator_email'], 'Student OJT Has Officially Started', 'ojt_started', 'ojt_started', [
                'student' => $studentDetails,
                'company' => $company,
                'officialStartDate' => $p['official_start_date'],
                'projectedEndDate' => $projectedEndDate,
                'requiredHours' => (int)$enrollment['required_hours'],
            ]);
        }
        if ($studentDetails) {
            $notifications = new Notification($this->db);
            $notifications->create((int)$studentDetails['user_id'], 'OJT officially started', 'Your official OJT start date is ' . $p['official_start_date'] . '.', route_url('student.dashboard'));
            if (!empty($studentDetails['coordinator_id'])) {
                $notifications->create((int)$studentDetails['coordinator_id'], 'Student OJT started', $studentDetails['name'] . ' officially started OJT at ' . $company['name'] . '.', route_url('coordinator.students', ['focus_student' => (int)$studentDetails['id']]));
            }
        }
        flash('success', 'Orientation completed and official OJT dates saved.');
        redirect($this->partnerPortalUrl((int)$enrollment['id']));
    }

    /**
     * Renders the detailed final evaluation form (reached via a button on the portal).
     */
    public function evaluateForm(): void
    {
        require_role('partner');
        $company = $this->requireCompanyProfile();
        $enroll = new Enrollment($this->db);
        $selected = isset($_GET['enrollment']) ? $enroll->find((int)$_GET['enrollment']) : null;
        if (!$selected || (int)$selected['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            exit('Forbidden');
        }

        $renderedHours = (new Report($this->db))->totalHours((int)$selected['student_id'], true);
        $requiredHours = (float)($selected['required_hours'] ?? 0);
        if ($requiredHours <= 0 || $renderedHours < $requiredHours) {
            flash('error', 'Final evaluation unlocks after the student completes the required approved OJT hours.');
            redirect($this->partnerPortalUrl((int)$selected['id']));
        }
        if (!$this->canSubmitFinalEvaluation($selected)) {
            flash('error', 'Final evaluation unlocks after orientation is completed and OJT is active.');
            redirect($this->partnerPortalUrl((int)$selected['id']));
        }

        $this->renderAppPage('partner/evaluate', [
            'title' => 'Final Evaluation',
            'company' => $company,
            'selected' => $selected,
            'evaluation' => (new Evaluation($this->db))->byEnrollment((int)$selected['id']),
        ]);
    }

    public function submitEvaluation(): void
    {
        require_role('partner');
        $p = $this->post();
        $company = (new Company($this->db))->findByUser(current_user()['id']);
        $enroll = new Enrollment($this->db);
        $enrollment = $enroll->find((int)$p['enrollment_id']);
        if (!$enrollment || !$company || (int)$enrollment['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            exit('Forbidden');
        }
        $renderedHours = (new Report($this->db))->totalHours((int)$enrollment['student_id'], true);
        $requiredHours = (float)($enrollment['required_hours'] ?? 0);
        if ($requiredHours <= 0 || $renderedHours < $requiredHours) {
            flash('error', 'Final evaluation unlocks after the student completes the required approved OJT hours.');
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }
        if (!$this->canSubmitFinalEvaluation($enrollment)) {
            flash('error', 'Final evaluation unlocks after orientation is completed and OJT is active.');
            redirect($this->partnerPortalUrl((int)$enrollment['id']));
        }

        try {
            $ratings = [];
            foreach (Evaluation::criteriaFlat() as $key => $def) {
                $ratings[$key] = (int)($p['criteria'][$key] ?? 0);
            }
            $certificateFile = upload_document($_FILES['certificate_file'] ?? [], 'certificates', false);
            (new Evaluation($this->db))->submitDetailed(
                (int)$p['enrollment_id'],
                (int)$company['id'],
                $ratings,
                trim($p['comments'] ?? ''),
                $certificateFile
            );
            // Hours already met for unlock; keep enrollment completion in sync after evaluation.
            $enroll->syncCompletion((int)$enrollment['student_id']);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect(route_url('partner.evaluate', ['enrollment' => (int)$enrollment['id']]));
        }

        $studentDetails = (new Student($this->db))->find((int)$enrollment['student_id']);
        if ($studentDetails) {
            $notifications = new Notification($this->db);
            $notifications->create((int)$studentDetails['user_id'], 'Final evaluation submitted', $company['name'] . ' submitted your final OJT evaluation.', route_url('student.evaluation'));
            if (!empty($studentDetails['coordinator_id'])) {
                $notifications->create((int)$studentDetails['coordinator_id'], 'Final evaluation submitted', $company['name'] . ' submitted the final evaluation for ' . $studentDetails['name'] . '.', route_url('coordinator.evaluations'));
            }
        }
        flash('success', 'Final evaluation submitted.');
        redirect($this->partnerPortalUrl((int)$p['enrollment_id']));
    }

    /**
     * Display the auto-generated endorsement letter as a PDF in the browser
     * 
     * This method handles viewing the dynamically generated endorsement letter
     * for a specific enrollment. The PDF is generated on-demand and streamed directly.
     */
    public function viewEndorsementLetter(): void
    {
        require_role('partner');
        $company = $this->requireCompanyProfile();
        $enrollmentId = (int)($_GET['enrollment'] ?? 0);
        
        if (!$enrollmentId) {
            http_response_code(400);
            exit('Invalid enrollment ID.');
        }

        $enrollment = (new Enrollment($this->db))->find($enrollmentId);
        
        if (!$enrollment) {
            http_response_code(404);
            exit('Enrollment not found.');
        }

        // Verify the Host Training Establishment has access to this enrollment
        if ((int)$enrollment['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            exit('You do not have access to this enrollment.');
        }

        $studentModel = new Student($this->db);
        $docsAvailable = $studentModel->isPredeploymentPipelineAdvanced($enrollment['predeployment_status'] ?? null)
            || ($enrollment['status'] ?? '') === 'completed';
        if (!$docsAvailable) {
            http_response_code(403);
            exit('Endorsement letter is available after the coordinator forwards approved documents.');
        }

        try {
            // Generate the PDF on-demand
            $endorsementLetter = new EndorsementLetter($this->db);
            $pdfContent = $endorsementLetter->generatePdfBuffer((int)$enrollment['student_id'], (int)$enrollment['id']);

            // Stream the PDF to the browser
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Endorsement_Letter.pdf"');
            header('Content-Length: ' . strlen($pdfContent));
            header('Cache-Control: public, max-age=3600');
            echo $pdfContent;
            exit;

        } catch (Throwable $e) {
            error_log('Endorsement letter view failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Unable to generate the endorsement letter right now. Please try again later.');
        }
    }

    public function viewRequirementForm(): void
    {
        require_role('partner');
        $company = $this->requireCompanyProfile();
        $studentId = (int)($_GET['student_id'] ?? 0);
        $requirementKey = trim((string)($_GET['key'] ?? ''));
        if ($studentId <= 0 || $requirementKey === '') {
            flash('error', 'Invalid request.');
            redirect(route_url('partner.portal'));
        }

        $selected = null;
        foreach ((new Enrollment($this->db))->deployedByCompany((int)$company['id']) as $row) {
            if ((int)($row['student_id'] ?? 0) === $studentId) {
                $selected = $row;
                break;
            }
        }
        if (!$selected) {
            flash('error', 'Student not found for your Host Training Establishment.');
            redirect(route_url('partner.portal'));
        }

        $studentModel = new Student($this->db);
        $stage = $studentModel->requirementStage($requirementKey);
        $requirement = $stage > 0
            ? ($studentModel->stageRequirements($studentId, $stage)[$requirementKey] ?? null)
            : null;
        if (!$requirement || ($requirement['status'] ?? '') !== 'approved' || empty($requirement['file_path'])) {
            flash('error', 'This document is not available.');
            redirect(route_url('partner.portal', ['enrollment' => (int)($selected['id'] ?? 0)]));
        }
        if (!requirement_is_form_path((string)$requirement['file_path'])) {
            flash('error', 'This document is a file upload.');
            redirect(route_url('partner.portal', ['enrollment' => (int)($selected['id'] ?? 0)]));
        }

        $formSection = requirement_form_section_key($requirementKey);
        $this->renderAppPage('partner/requirement_form_view', [
            'title' => ($requirement['requirement_name'] ?? 'Document') . ' - ' . ($selected['name'] ?? 'Student'),
            'company' => $company,
            'student' => $selected,
            'requirement' => $requirement,
            'requirementKey' => $requirementKey,
            'formSection' => $formSection,
            'finalRequirement' => (new FinalRequirement($this->db))->getByStudent($studentId),
        ]);
    }

    public function settings(): void
    {
        require_role('partner');
        $company = (new Company($this->db))->findByUserWithPrograms((int)current_user()['id']);
        if (!$company) {
            $this->requireCompanyProfile();
        }
        $this->renderAppPage('partner/settings', [
            'title' => 'Settings',
            'company' => $company,
        ]);
    }

    public function profileForm(): void
    {
        require_role('partner');
        $company = (new Company($this->db))->findByUserWithPrograms((int)current_user()['id']);
        if (!$company) {
            $this->requireCompanyProfile();
        }
        $this->renderAppPage('partner/profile', [
            'title' => 'Edit Profile',
            'company' => $company,
        ]);
    }

    public function saveProfile(): void
    {
        require_role('partner');
        $p = $this->post();
        $company = (new Company($this->db))->findByUser((int)current_user()['id']);
        if (!$company) {
            flash('error', 'Host Training Establishment profile not found.');
            redirect(route_url('partner.settings'));
        }
        try {
            $companyName = trim((string)($p['company_name'] ?? ''));
            $contactPerson = trim((string)($p['contact_person'] ?? ''));
            $contactEmail = strtolower(trim((string)($p['contact_email'] ?? '')));
            $address = trim((string)($p['address'] ?? ''));
            $contactNumberRaw = trim((string)($p['contact_number'] ?? ''));

            if ($companyName === '' || $contactPerson === '' || $contactEmail === '' || $address === '' || $contactNumberRaw === '') {
                throw new RuntimeException('Please complete all required profile fields.');
            }
            if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Enter a valid email address.');
            }

            $contactNumberDigits = preg_replace('/\D+/', '', $contactNumberRaw);
            if (str_starts_with($contactNumberDigits, '63')) {
                $contactNumberDigits = substr($contactNumberDigits, 2);
            }
            if (str_starts_with($contactNumberDigits, '0')) {
                $contactNumberDigits = substr($contactNumberDigits, 1);
            }
            if (!preg_match('/^9\d{9}$/', $contactNumberDigits)) {
                throw new RuntimeException('Contact number must be a valid Philippine mobile number.');
            }
            $contactNumber = '+63 ' . substr($contactNumberDigits, 0, 3) . ' ' . substr($contactNumberDigits, 3, 3) . ' ' . substr($contactNumberDigits, 6, 4);

            $photo = null;
            if (!empty($_FILES['photo_file']['name'])) {
                $photo = upload_profile_photo($_FILES['photo_file'], false);
                $oldPhoto = trim((string)($company['photo_file'] ?? ''));
                if ($oldPhoto !== '') {
                    $oldPath = __DIR__ . '/../' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($oldPhoto, '/\\'));
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            }

            $userId = (int)current_user()['id'];
            $this->db->beginTransaction();
            (new User($this->db))->updateName($userId, $companyName);
            (new User($this->db))->updateEmail($userId, $contactEmail);
            (new Company($this->db))->updateProfile(
                (int)$company['id'],
                $companyName,
                $address,
                $contactPerson,
                $contactEmail,
                $contactNumber,
                $photo
            );
            $this->db->commit();
            $_SESSION['user']['name'] = $companyName;
            $_SESSION['user']['email'] = $contactEmail;
            flash('success', 'Profile updated successfully.');
            redirect(route_url('partner.profile'));
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $msg = str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate entry')
                ? 'That email address is already in use by another account.'
                : $e->getMessage();
            flash('error', $msg);
            redirect(route_url('partner.profile'));
        }
    }

    public function changePasswordForm(): void
    {
        require_role('partner');
        $isFirstLogin = (int)(current_user()['password_changed'] ?? 1) === 0;
        $this->renderAppPage('partner/change_password', [
            'title' => $isFirstLogin ? 'Change Temporary Password' : 'Change Password',
            'csrfToken' => csrf_token(),
            'isFirstLogin' => $isFirstLogin,
        ]);
    }

    public function verifyCurrentPassword(): void
    {
        require_role('partner');
        verify_csrf();
        header('Content-Type: application/json; charset=utf-8');

        $currentPassword = (string)($_POST['current_password'] ?? '');
        $isFirstLogin = (int)(current_user()['password_changed'] ?? 1) === 0;
        $passwordLabel = $isFirstLogin ? 'temporary password' : 'current password';
        if ($currentPassword === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Enter your ' . $passwordLabel . '.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $userId = (int)current_user()['id'];
        $verified = (new User($this->db))->verifyPassword($userId, $currentPassword);
        if (!$verified) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => ucfirst($passwordLabel) . ' is incorrect.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $reauthToken = bin2hex(random_bytes(32));
        $_SESSION['partner_password_reauth'] = [
            'token_hash' => hash('sha256', $reauthToken),
            'user_id' => $userId,
            'expires_at' => time() + 600,
        ];

        echo json_encode(['ok' => true, 'reauth_token' => $reauthToken], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function changePassword(): void
    {
        require_role('partner');
        header('Content-Type: application/json; charset=utf-8');

        try {
            $p = $this->post();
            $reauthToken = (string)($p['reauth_token'] ?? '');
            $password = (string)($p['password'] ?? '');
            $confirm = (string)($p['confirm_password'] ?? '');
            $userId = (int)current_user()['id'];
            $wasTemporary = (int)(current_user()['password_changed'] ?? 1) === 0;
            $passwordLabel = $wasTemporary ? 'temporary password' : 'current password';

            $sessionReauth = $_SESSION['partner_password_reauth'] ?? null;
            unset($_SESSION['partner_password_reauth']);
            $reauthValid = is_array($sessionReauth)
                && (int)($sessionReauth['user_id'] ?? 0) === $userId
                && (int)($sessionReauth['expires_at'] ?? 0) >= time()
                && is_string($sessionReauth['token_hash'] ?? null)
                && $reauthToken !== ''
                && hash_equals((string)$sessionReauth['token_hash'], hash('sha256', $reauthToken));

            if (!$reauthValid) {
                http_response_code(401);
                throw new RuntimeException('Please verify your ' . $passwordLabel . ' again before changing it.');
            }
            if (strlen($password) < 8) {
                throw new RuntimeException('Password must be at least 8 characters.');
            }
            if ($password !== $confirm) {
                throw new RuntimeException('Passwords do not match.');
            }
            if (password_verify($password, (string)((new User($this->db))->find($userId)['password_hash'] ?? ''))) {
                throw new RuntimeException('New password must be different from your current password.');
            }

            (new User($this->db))->updatePassword($userId, $password, 1);
            $_SESSION['user']['password_changed'] = 1;

            echo json_encode([
                'ok' => true,
                'message' => $wasTemporary
                    ? 'Password changed successfully. You can now access your dashboard.'
                    : 'Password changed successfully.',
                'redirect' => $wasTemporary
                    ? route_for_role('partner')
                    : route_url('partner.settings'),
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if (http_response_code() === 200) {
                http_response_code(400);
            }
            echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /** @param array<int, array<string, mixed>> $studentSummaries */
    private function enrichSubmissionSummaries(array $studentSummaries, array $company): array
    {
        foreach ($studentSummaries as &$row) {
            $enrollment = [
                'company_id' => (int)($row['company_id'] ?? $company['id']),
                'status' => $row['enrollment_status'] ?? '',
                'predeployment_status' => $row['predeployment_status'] ?? '',
                'official_start_date' => $row['official_start_date'] ?? null,
                'start_date' => $row['start_date'] ?? null,
            ];
            $row['reports_unlocked'] = enrollment_allows_reports($enrollment);
            $row['reports_lock_message'] = enrollment_report_lock_message($enrollment);
        }
        unset($row);

        return $studentSummaries;
    }

    /** @param array<int, array<string, mixed>> $studentSummaries */
    private function submissionStatsFromSummaries(array $studentSummaries): array
    {
        $pendingDtr = 0;
        $pendingWeekly = 0;
        $studentsWithPending = 0;
        foreach ($studentSummaries as $row) {
            if (empty($row['reports_unlocked'])) {
                continue;
            }
            $dtr = (int)($row['pending_dtr'] ?? 0);
            $weekly = (int)($row['pending_weekly'] ?? 0);
            $pendingDtr += $dtr;
            $pendingWeekly += $weekly;
            if ($dtr + $weekly > 0) {
                $studentsWithPending++;
            }
        }

        return [
            'pending_dtr' => $pendingDtr,
            'pending_weekly' => $pendingWeekly,
            'pending_total' => $pendingDtr + $pendingWeekly,
            'students_with_pending' => $studentsWithPending,
        ];
    }

    private function requireCompanyProfile(): ?array
    {
        $company = (new Company($this->db))->findByUser((int)current_user()['id']);
        if ($company) {
            return $company;
        }
        if ($this->isAjaxRequest()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => 'Host Training Establishment profile not found. Please contact the system administrator.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $this->renderAppPage('partner/no_company', [
            'title' => 'Profile Unavailable',
        ]);
        exit;
    }

    private function partnerPortalUrl(int $enrollmentId = 0): string
    {
        return route_url('partner.portal', $enrollmentId > 0 ? ['enrollment' => $enrollmentId] : []);
    }

    private function partnerSubmissionsUrl(int $studentId, string $tab): string
    {
        return route_url('partner.submissions', ['student_id' => $studentId, 'tab' => $tab]);
    }
}
