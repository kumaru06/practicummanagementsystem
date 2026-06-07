<?php
class PartnerController extends BaseController
{
    public function dashboard(): void
    {
        require_role('partner');
        $company = (new Company($this->db))->findByUser(current_user()['id']);
        $enroll = new Enrollment($this->db);
        $students = $company ? $enroll->deployedByCompany((int)$company['id']) : [];

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
            if ($status === 'active') {
                $stats['active']++;
            }
            if ($status === 'completed') {
                $stats['completed']++;
            }
            if (in_array($predeployment, ['accepted', 'orientation_scheduled'], true)) {
                $stats['orientation']++;
            }
            if (!in_array($status, ['active', 'completed'], true)) {
                $stats['pending']++;
            }
        }

        $this->render('partner/dashboard', [
            'title' => 'Industry Partner Dashboard',
            'company' => $company,
            'students' => $students,
            'stats' => $stats,
        ]);
    }

    /**
     * Submissions hub: lists all students for this partner with pending counts,
     * and (if ?student_id=X provided) shows the per-student detail with DTR + Weekly tabs.
     */
    public function submissions(): void
    {
        require_role('partner');
        $company = (new Company($this->db))->findByUser(current_user()['id']);
        if (!$company) {
            $this->render('partner/submissions', [
                'title' => 'Student Submissions',
                'company' => null,
                'studentSummaries' => [],
                'selectedStudent' => null,
                'studentDtrs' => [],
                'studentWeeklies' => [],
                'activeTab' => 'dtr',
            ]);
            return;
        }

        $reportModel = new Report($this->db);
        $studentSummaries = $reportModel->submissionSummaryByCompany((int)$company['id']);

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

        $this->render('partner/submissions', [
            'title' => 'Student Submissions',
            'company' => $company,
            'studentSummaries' => $studentSummaries,
            'selectedStudent' => $selectedStudent,
            'studentDtrs' => $studentDtrs,
            'studentWeeklies' => $studentWeeklies,
            'activeTab' => $activeTab,
        ]);
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
            $this->ensureRecordOwnership($studentId, $dtrId, 'dtr');

            $report = new Report($this->db);
            $report->setDtrVerification($dtrId, $action, (int)current_user()['id'], $notes ?: null);

            $this->notifyStudentAndCoordinator($studentId, 'dtr', $action, $notes);

            flash('success', 'Daily Time Record ' . $action . '.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=partner_submissions&student_id=' . $studentId . '&tab=dtr');
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
            $this->ensureRecordOwnership($studentId, $weeklyId, 'weekly');

            $report = new Report($this->db);
            $report->setWeeklyVerification($weeklyId, $action, (int)current_user()['id'], $notes ?: null);

            $this->notifyStudentAndCoordinator($studentId, 'weekly', $action, $notes);

            flash('success', 'Weekly report ' . $action . '.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=partner_submissions&student_id=' . $studentId . '&tab=weekly');
    }

    private function ensureRecordOwnership(int $studentId, int $recordId, string $type): void
    {
        $company = (new Company($this->db))->findByUser(current_user()['id']);
        if (!$company) {
            throw new RuntimeException('Industry Partner profile not found.');
        }
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM ojt_enrollments WHERE student_id = ? AND company_id = ?'
        );
        $stmt->execute([$studentId, (int)$company['id']]);
        if ((int)$stmt->fetchColumn() === 0) {
            throw new RuntimeException('You do not have access to this student.');
        }
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
        $message = 'Your ' . $label . ' was ' . $action . ' by your Industry Partner' . ($notes !== '' ? ': ' . $notes : '.');

        $notifications->create((int)$student['user_id'], $title, $message, route_url('student.records'));

        if ($action === 'approved' && !empty($student['coordinator_id'])) {
            $notifications->create(
                (int)$student['coordinator_id'],
                $label . ' approved by Industry Partner',
                $student['name'] . '\'s ' . $label . ' has been approved by the Industry Partner.',
                route_url('coordinator.students')
            );
        }
    }

    public function portal(): void
    {
        require_role('partner');
        $company = (new Company($this->db))->findByUser(current_user()['id']);
        $enroll = new Enrollment($this->db);
        $students = $company ? $enroll->deployedByCompany((int)$company['id']) : [];
        $selected = isset($_GET['enrollment']) ? $enroll->find((int)$_GET['enrollment']) : null;
        if ($selected && $company && (int)$selected['company_id'] !== (int)$company['id']) {
            $selected = null; // deny cross-company access
        }
        $dtrs = [];
        $evaluation = null;
        if ($selected) {
            $dtrs = (new Report($this->db))->dtrByStudent((int)$selected['student_id']);
            $evaluation = (new Evaluation($this->db))->byEnrollment((int)$selected['id']);
        }
        $this->render('partner/portal', [
            'title' => 'Industry Partner Portal',
            'company' => $company,
            'students' => $students,
            'selected' => $selected,
            'dtrs' => $dtrs,
            'evaluation' => $evaluation,
            'requirements' => $selected ? (new Student($this->db))->requirements((int)$selected['student_id']) : [],
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
            redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
        }
        (new Enrollment($this->db))->acceptDeployment((int)$enrollment['id']);
        $studentDetails = (new Student($this->db))->find((int)$enrollment['student_id']);
        if ($studentDetails) {
            $notifications = new Notification($this->db);
            $notifications->create((int)$studentDetails['user_id'], 'Deployment accepted', $company['name'] . ' accepted your deployment documents.', route_url('student.dashboard'));
            $notifications->create((int)$studentDetails['coordinator_id'], 'Deployment accepted', $company['name'] . ' accepted ' . $studentDetails['name'] . '\'s deployment.', route_url('coordinator.students', ['focus_student' => (int)$studentDetails['id']]));
        }
        flash('success', 'Deployment accepted. You can now schedule orientation.');
        redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
    }

    public function sendOrientationEmail(): void
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
            flash('error', 'Orientation instructions unlock after deployment acceptance.');
            redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
        }
        $notes = trim($p['orientation_notes'] ?? '');
        if ($notes === '') {
            flash('error', 'Orientation instructions are required.');
            redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
        }
        $email = new Email($this->db);
        $studentDetails = (new Student($this->db))->find((int)$enrollment['student_id']);
        $email->send($enrollment['student_email'], 'OJT Orientation Instructions', 'orientation_email', 'orientation_notice', [
            'student' => $enrollment,
            'company' => $company,
            'orientationDateTime' => '',
            'notes' => $notes,
        ]);
        if (!empty($studentDetails['coordinator_email'])) {
            $email->send($studentDetails['coordinator_email'], 'OJT Orientation Instructions', 'orientation_email', 'orientation_notice', [
                'student' => $studentDetails,
                'company' => $company,
                'orientationDateTime' => '',
                'notes' => $notes,
            ]);
        }
        if ($studentDetails) {
            $notifications = new Notification($this->db);
            $notifications->create((int)$studentDetails['user_id'], 'Orientation instructions sent', $company['name'] . ' sent OJT orientation instructions.', route_url('student.timeline'));
            $notifications->create((int)$studentDetails['coordinator_id'], 'Orientation instructions sent', $company['name'] . ' sent orientation instructions for ' . $studentDetails['name'] . '.', route_url('coordinator.students', ['focus_student' => (int)$studentDetails['id']]));
        }
        flash('success', 'Orientation email sent to the student and coordinator.');
        redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
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
            redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
        }
        if (empty($p['orientation_datetime']) || strtotime((string)$p['orientation_datetime']) === false) {
            flash('error', 'Enter a valid orientation date and time.');
            redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
        }
        if (strtotime((string)$p['orientation_datetime']) < time()) {
            flash('error', 'Orientation date and time cannot be in the past.');
            redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
        }
        $orientationNotes = trim($p['orientation_notes'] ?? '');
        if ($orientationNotes === '') {
            flash('error', 'Orientation notes are required.');
            redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
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
            $notifications->create((int)$studentDetails['coordinator_id'], 'Orientation scheduled', $company['name'] . ' scheduled orientation for ' . $studentDetails['name'] . '.', route_url('coordinator.students', ['focus_student' => (int)$studentDetails['id']]));
        }
        flash('success', 'Orientation scheduled and student notified.');
        redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
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
            redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
        }
        if (empty($p['official_start_date']) || strtotime((string)$p['official_start_date']) === false) {
            flash('error', 'Enter a valid official OJT start date.');
            redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
        }
        $projectedEndDate = trim($p['projected_end_date'] ?? '') ?: projected_ojt_end_date($p['official_start_date'], (int)$enrollment['required_hours']);
        if (strtotime($projectedEndDate) < strtotime((string)$p['official_start_date'])) {
            flash('error', 'Projected end date cannot be earlier than the official start date.');
            redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
        }
        (new Enrollment($this->db))->completeOrientation((int)$enrollment['id'], $p['official_start_date'], $projectedEndDate);
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
            $notifications->create((int)$studentDetails['coordinator_id'], 'Student OJT started', $studentDetails['name'] . ' officially started OJT at ' . $company['name'] . '.', route_url('coordinator.students', ['focus_student' => (int)$studentDetails['id']]));
        }
        flash('success', 'Orientation completed and official OJT dates saved.');
        redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
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
        $renderedHours = (new Report($this->db))->totalHours((int)$enrollment['student_id']);
        $requiredHours = (float)($enrollment['required_hours'] ?? 0);
        if ($requiredHours <= 0 || $renderedHours < $requiredHours) {
            flash('error', 'Final evaluation unlocks after the student completes the required OJT hours.');
            redirect('index.php?r=partner_portal&enrollment=' . (int)$enrollment['id']);
        }
        (new Evaluation($this->db))->submit((int)$p['enrollment_id'], (int)$company['id'], (int)$p['rating'], trim($p['comments']));
        $studentDetails = (new Student($this->db))->find((int)$enrollment['student_id']);
        if ($studentDetails) {
            $notifications = new Notification($this->db);
            $notifications->create((int)$studentDetails['user_id'], 'Final evaluation submitted', $company['name'] . ' submitted your final OJT evaluation.', route_url('student.dashboard'));
            $notifications->create((int)$studentDetails['coordinator_id'], 'Final evaluation submitted', $company['name'] . ' submitted the final evaluation for ' . $studentDetails['name'] . '.', route_url('coordinator.evaluations'));
        }
        flash('success', 'Final evaluation submitted.');
        redirect('index.php?r=partner_portal&enrollment=' . (int)$p['enrollment_id']);
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
        $company = (new Company($this->db))->findByUser(current_user()['id']);
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

        // Verify the Industry Partner has access to this enrollment
        if (!$company || (int)$enrollment['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            exit('You do not have access to this enrollment.');
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
            http_response_code(500);
            exit('Error generating endorsement letter: ' . $e->getMessage());
        }
    }
}
