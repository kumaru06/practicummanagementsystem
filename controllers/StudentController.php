<?php
class StudentController extends BaseController
{
    public function pendingApproval(): void
    {
        require_role('student');
        if (!student_is_pending_approval()) {
            redirect(route_url('student.dashboard'));
        }
        require __DIR__ . '/../views/student/pending.php';
    }

    public function changePasswordForm(): void
    {
        require_role(['student', 'coordinator', 'partner', 'admin']);
        $isFirstLogin = (int)(current_user()['password_changed'] ?? 1) === 0;
        $this->renderAppPage('student/change_password', [
            'title' => $isFirstLogin ? 'Change Temporary Password' : 'Change Password',
            'csrfToken' => csrf_token(),
            'isFirstLogin' => $isFirstLogin,
        ]);
    }

    public function verifyCurrentPassword(): void
    {
        require_role(['student', 'coordinator', 'partner', 'admin']);
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

        $verified = (new User($this->db))->verifyPassword((int)current_user()['id'], $currentPassword);
        if (!$verified) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => ucfirst($passwordLabel) . ' is incorrect.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function changePassword(): void
    {
        require_role(['student', 'coordinator', 'partner', 'admin']);
        header('Content-Type: application/json; charset=utf-8');

        try {
            $p = $this->post();
            $currentPassword = (string)($p['current_password'] ?? '');
            $password = (string)($p['password'] ?? '');
            $confirm = (string)($p['confirm_password'] ?? '');
            $userId = (int)current_user()['id'];
            $wasTemporary = (int)(current_user()['password_changed'] ?? 1) === 0;
            $passwordLabel = $wasTemporary ? 'temporary password' : 'current password';

            if ($currentPassword === '') {
                throw new RuntimeException(ucfirst($passwordLabel) . ' is required.');
            }
            if (!(new User($this->db))->verifyPassword($userId, $currentPassword)) {
                http_response_code(401);
                throw new RuntimeException(ucfirst($passwordLabel) . ' is incorrect.');
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
                    ? route_for_role(current_user()['role'])
                    : route_url('student.settings'),
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if (http_response_code() === 200) {
                http_response_code(400);
            }
            echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function dashboard(): void
    {
        $this->renderAppPage('student/dashboard', $this->studentPageData('Student Dashboard'));
    }

    public function records(): void
    {
        $this->renderAppPage('student/records', $this->studentPageData('Submit Record'));
    }

    public function reports(): void
    {
        $this->renderAppPage('student/reports', $this->studentPageData('Reports'));
    }

    public function exportReportPdf(): void
    {
        require_role('student');
        $type = ($_GET['type'] ?? 'dtr') === 'weekly' ? 'weekly' : 'dtr';
        $data = $this->studentPageData('Reports');
        $student = $data['student'] ?? [];
        $studentName = trim((string)($student['name'] ?? current_user()['name'] ?? 'Student'));

        $statusLabel = static function ($status): string {
            $status = strtolower(trim((string)$status));
            if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
                $status = 'pending';
            }
            return ucfirst($status);
        };

        if ($type === 'weekly') {
            $heading = 'Weekly Reports';
            $headers = ['Week', 'Date Covered', 'Accomplishments', 'Approval Status'];
            $rows = [];
            foreach (($data['weeklyReports'] ?? []) as $w) {
                $dateRange = '-';
                if (!empty($w['date_covered_start']) && !empty($w['date_covered_end'])) {
                    $dateRange = date('M d', strtotime((string)$w['date_covered_start'])) . ' - ' . date('M d, Y', strtotime((string)$w['date_covered_end']));
                }
                $accomplishments = trim((string)($w['accomplishments'] ?? $w['report_text'] ?? '')) ?: '-';
                $rows[] = [
                    'Week ' . (int)$w['week_no'],
                    $dateRange,
                    $accomplishments,
                    $statusLabel($w['verification_status'] ?? 'pending'),
                ];
            }
        } else {
            $heading = 'Daily Time Records';
            $headers = ['Date', 'Time', 'Hours', 'Tasks', 'Approval Status'];
            $rows = [];
            foreach (($data['dtrs'] ?? []) as $d) {
                $rows[] = [
                    (string)($d['work_date'] ?? '-'),
                    trim(format_dtr_schedule($d)),
                    (string)($d['hours'] ?? '-'),
                    (string)($d['tasks_done'] ?? '-'),
                    $statusLabel($d['verification_status'] ?? 'pending'),
                ];
            }
        }

        $generatedAt = date('M d, Y g:i A');
        $headHtml = '';
        foreach ($headers as $h) {
            $headHtml .= '<th>' . htmlspecialchars($h, ENT_QUOTES) . '</th>';
        }
        $bodyHtml = '';
        if (!$rows) {
            $bodyHtml = '<tr><td colspan="' . count($headers) . '" class="empty">No records submitted yet.</td></tr>';
        } else {
            foreach ($rows as $row) {
                $bodyHtml .= '<tr>';
                foreach ($row as $cell) {
                    $bodyHtml .= '<td>' . nl2br(htmlspecialchars((string)$cell, ENT_QUOTES)) . '</td>';
                }
                $bodyHtml .= '</tr>';
            }
        }

        $html = '<!doctype html><html><head><meta charset="utf-8"><style>'
            . 'body{font-family:DejaVu Sans,Arial,sans-serif;color:#1f2937;font-size:11px;}'
            . 'h1{font-size:18px;margin:0 0 2px;color:#7f1d1d;}'
            . '.sub{color:#6b7280;font-size:11px;margin:0 0 14px;}'
            . '.meta{margin:0 0 16px;font-size:11px;}'
            . '.meta strong{color:#111827;}'
            . 'table{width:100%;border-collapse:collapse;}'
            . 'th{background:#fdecec;color:#7f1d1d;text-align:left;padding:8px;border:1px solid #f3c7c7;font-size:10px;text-transform:uppercase;}'
            . 'td{padding:8px;border:1px solid #e5e7eb;vertical-align:top;}'
            . 'tr:nth-child(even) td{background:#fafafa;}'
            . '.empty{text-align:center;color:#6b7280;padding:18px;}'
            . '</style></head><body>'
            . '<h1>' . htmlspecialchars($heading, ENT_QUOTES) . '</h1>'
            . '<p class="sub">AMA Computer College &mdash; OJT Management</p>'
            . '<p class="meta"><strong>Student:</strong> ' . htmlspecialchars($studentName, ENT_QUOTES) . '<br>'
            . '<strong>Generated:</strong> ' . htmlspecialchars($generatedAt, ENT_QUOTES) . '</p>'
            . '<table><thead><tr>' . $headHtml . '</tr></thead><tbody>' . $bodyHtml . '</tbody></table>'
            . '</body></html>';

        $projectRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
        $tempDir = $projectRoot . '/uploads/dompdf_temp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('tempDir', $tempDir);
        $options->set('fontDir', $tempDir);
        $options->set('fontCache', $tempDir);
        $options->set('chroot', $projectRoot);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $safeName = preg_replace('/[^A-Za-z0-9]+/', '_', strtolower($studentName)) ?: 'student';
        $fileName = $safeName . '_' . ($type === 'weekly' ? 'weekly_reports' : 'daily_time_records') . '_' . date('Ymd') . '.pdf';
        $dompdf->stream($fileName, ['Attachment' => true]);
        exit;
    }

    public function timeline(): void
    {
        $this->renderAppPage('student/timeline', $this->studentPageData('Activity Timeline'));
    }

    public function documents(): void
    {
        $this->renderAppPage('student/documents', $this->studentPageData('Pre-Deployment Requirements'));
    }

    public function documentsFinal(): void
    {
        require_role('student');
        $data = $this->studentPageData('Final Requirements');
        $student = $data['student'] ?? null;
        $finalModel = new FinalRequirement($this->db);
        $evalModel = new StudentEvaluation($this->db);
        $data['finalRequirement'] = $student ? $finalModel->getByStudent((int)$student['id']) : [];
        $data['studentEvaluation'] = $student ? $evalModel->getByStudent((int)$student['id']) : [];
        $data['finalSections'] = FinalRequirement::SECTIONS;
        $data['evaluationSections'] = FinalRequirement::EVALUATION_SECTIONS;

        $doc = (string)($_GET['doc'] ?? '');
        $eval = (string)($_GET['eval'] ?? '');
        $data['activePanel'] = '';
        $data['activeKind'] = '';

        if (!$data['canAccessFinalRequirements']) {
            if ($doc !== '' || $eval !== '') {
                flash('error', $data['finalRequirementsLockMessage']);
                redirect('index.php?r=student_documents_final');
            }
        } elseif (array_key_exists($doc, FinalRequirement::SECTIONS)) {
            $data['activePanel'] = $doc;
            $data['activeKind'] = 'doc';
            $data['title'] = FinalRequirement::SECTIONS[$doc]['name'];
        } elseif (array_key_exists($eval, FinalRequirement::EVALUATION_SECTIONS)) {
            $data['activePanel'] = $eval;
            $data['activeKind'] = 'eval';
            $data['title'] = FinalRequirement::EVALUATION_SECTIONS[$eval]['name'];
        }

        $this->renderAppPage('student/documents_final', $data);
    }

    public function saveFinalJobDescription(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser((int)current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('index.php?r=student_documents_final');
        }
        $enrollment = (new Enrollment($this->db))->detailsByStudent((int)$student['id']);
        $approvedHours = (new Report($this->db))->totalHours((int)$student['id'], true);
        try {
            assert_student_final_requirements($enrollment, $approvedHours);
            (new FinalRequirement($this->db))->saveJobDescription(
                (int)$student['id'],
                (string)($p['position_held'] ?? ''),
                (string)($p['job_description'] ?? '')
            );
            flash('success', 'Job description saved.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('index.php?r=student_documents_final&doc=job_description');
        }
        redirect('index.php?r=student_documents_final');
    }

    public function saveFinalCompanyProfile(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser((int)current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('index.php?r=student_documents_final');
        }
        $enrollment = (new Enrollment($this->db))->detailsByStudent((int)$student['id']);
        $approvedHours = (new Report($this->db))->totalHours((int)$student['id'], true);
        try {
            assert_student_final_requirements($enrollment, $approvedHours);
            (new FinalRequirement($this->db))->saveCompanyProfile(
                (int)$student['id'],
                (string)($p['company_history'] ?? ''),
                (string)($p['company_description'] ?? ''),
                (string)($p['company_mission'] ?? ''),
                (string)($p['company_vision'] ?? '')
            );
            flash('success', 'Company profile saved.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('index.php?r=student_documents_final&doc=company_profile');
        }
        redirect('index.php?r=student_documents_final');
    }

    public function saveFinalPersonalObservation(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser((int)current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('index.php?r=student_documents_final');
        }
        $enrollment = (new Enrollment($this->db))->detailsByStudent((int)$student['id']);
        $approvedHours = (new Report($this->db))->totalHours((int)$student['id'], true);
        try {
            assert_student_final_requirements($enrollment, $approvedHours);
            $fields = [];
            foreach (array_keys(FinalRequirement::PERSONAL_OBSERVATION_FIELDS) as $column) {
                $fields[$column] = (string)($p[$column] ?? '');
            }
            (new FinalRequirement($this->db))->savePersonalObservation((int)$student['id'], $fields);
            flash('success', 'Personal observations saved.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('index.php?r=student_documents_final&doc=personal_observation');
        }
        redirect('index.php?r=student_documents_final');
    }

    public function saveStudentEvaluationPartner(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser((int)current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('index.php?r=student_documents_final');
        }
        try {
            $enrollment = (new Enrollment($this->db))->detailsByStudent((int)$student['id']);
            $approvedHours = (new Report($this->db))->totalHours((int)$student['id'], true);
            assert_student_final_requirements($enrollment, $approvedHours);
            $evalModel = new StudentEvaluation($this->db);
            $wasSubmitted = StudentEvaluation::statusFor($evalModel->getByStudent((int)$student['id']), 'industry_partner') === 'submitted';
            $ratings = [];
            foreach (StudentEvaluation::industryPartnerCriteria() as $section => $criteria) {
                foreach (array_keys($criteria) as $key) {
                    $ratings[$key] = (int)($p[$key] ?? 0);
                }
            }
            $evalModel->saveIndustryPartnerEvaluation(
                (int)$student['id'],
                $ratings,
                (string)($p['partner_comments'] ?? '')
            );
            $coordinatorUserId = (int)($student['coordinator_id'] ?? 0);
            if ($coordinatorUserId > 0) {
                $studentName = (string)($student['name'] ?? 'A student');
                $title = $wasSubmitted ? 'Host training establishment evaluation updated' : 'Host training establishment evaluation received';
                $message = $wasSubmitted
                    ? $studentName . ' updated their evaluation of the host training establishment and OJT supervisor.'
                    : $studentName . ' submitted an evaluation of the host training establishment and OJT supervisor.';
                (new Notification($this->db))->create(
                    $coordinatorUserId,
                    $title,
                    $message,
                    'index.php?r=coordinator_student_final&student_id=' . (int)$student['id'] . '&eval=industry_partner'
                );
            }
            flash('success', 'Host Training Establishment evaluation saved.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('index.php?r=student_documents_final&eval=industry_partner');
        }
        redirect('index.php?r=student_documents_final');
    }

    public function saveStudentEvaluationCoordinator(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser((int)current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('index.php?r=student_documents_final');
        }
        try {
            $enrollment = (new Enrollment($this->db))->detailsByStudent((int)$student['id']);
            $approvedHours = (new Report($this->db))->totalHours((int)$student['id'], true);
            assert_student_final_requirements($enrollment, $approvedHours);
            $evalModel = new StudentEvaluation($this->db);
            $wasSubmitted = StudentEvaluation::statusFor($evalModel->getByStudent((int)$student['id']), 'coordinator') === 'submitted';
            $ratings = [];
            foreach (StudentEvaluation::coordinatorCriteria() as $section => $criteria) {
                foreach (array_keys($criteria) as $key) {
                    $ratings[$key] = (int)($p[$key] ?? 0);
                }
            }
            $evalModel->saveCoordinatorEvaluation(
                (int)$student['id'],
                $ratings,
                (string)($p['coordinator_comments'] ?? '')
            );
            $coordinatorUserId = (int)($student['coordinator_id'] ?? 0);
            if ($coordinatorUserId > 0) {
                $studentName = (string)($student['name'] ?? 'A student');
                $title = $wasSubmitted ? 'Coordinator evaluation updated' : 'Coordinator evaluation received';
                $message = $wasSubmitted
                    ? $studentName . ' updated their evaluation of your OJT coordination.'
                    : $studentName . ' submitted an evaluation of your OJT coordination.';
                (new Notification($this->db))->create(
                    $coordinatorUserId,
                    $title,
                    $message,
                    'index.php?r=coordinator_student_final&student_id=' . (int)$student['id'] . '&eval=coordinator'
                );
            }
            flash('success', 'OJT Coordinator evaluation saved.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('index.php?r=student_documents_final&eval=coordinator');
        }
        redirect('index.php?r=student_documents_final');
    }

    public function documentsOther(): void
    {
        $this->renderAppPage('student/documents_other', $this->studentPageData('Other Documents'));
    }

    public function settings(): void
    {
        $this->renderAppPage('student/settings', $this->studentPageData('Settings'));
    }

    public function evaluation(): void
    {
        $data = $this->studentPageData('My Evaluation');
        $evaluation = null;
        if (!empty($data['enrollment']['id'])) {
            $evaluation = (new Evaluation($this->db))->byEnrollment((int)$data['enrollment']['id']);
        }
        $data['evaluation'] = $evaluation;
        $this->renderAppPage('student/evaluation', $data);
    }

    public function profileForm(): void
    {
        require_role('student');
        $student = (new Student($this->db))->findByUser(current_user()['id']);
        $this->renderAppPage('student/profile', [
            'title' => !empty($student['profile_completed']) ? 'Edit Student Profile' : 'Complete Student Profile',
            'student' => $student,
            'profileCompleted' => !empty($student['profile_completed']),
        ]);
    }

    public function saveProfile(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser(current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('index.php');
        }
        try {
            foreach (['address', 'contact_number', 'emergency_contact_name', 'emergency_contact_number', 'year_level', 'section'] as $field) {
                if (trim((string)($p[$field] ?? '')) === '') {
                    throw new RuntimeException('Please complete all required profile fields.');
                }
            }
            if (empty($student['photo_file']) && empty($_FILES['photo_file']['name'])) {
                throw new RuntimeException('Profile photo is required.');
            }
            $wasCompleted = !empty($student['profile_completed']);
            $photo = null;
            if (!empty($_FILES['photo_file']['name'])) {
                $photo = upload_profile_photo($_FILES['photo_file'], false);
            }
            (new Student($this->db))->updateProfile((int)$student['id'], $p, $photo);
            flash('success', $wasCompleted ? 'Profile updated successfully.' : 'Profile completed. Your dashboard is now unlocked.');
            redirect('index.php?r=' . ($wasCompleted ? 'student_profile' : 'student'));
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('index.php?r=student_profile');
        }
    }

    public function uploadRequirement(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser(current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('index.php?r=student');
        }
        try {
            if (!(new Enrollment($this->db))->detailsByStudent((int)$student['id'])) {
                throw new RuntimeException('You must be enrolled in OJT before uploading pre-deployment requirements.');
            }
            $requirementKey = trim($p['requirement_key'] ?? '');
            $studentModel = new Student($this->db);
            if (!$studentModel->canUploadRequirement((int)$student['id'], $requirementKey)) {
                throw new RuntimeException($studentModel->requirementUploadMessage((int)$student['id'], $requirementKey) . '.');
            }
            $path = upload_document($_FILES['requirement_file'] ?? [], 'requirements/' . (int)$student['id']);
            $studentModel->saveRequirement((int)$student['id'], $requirementKey, $path);
            flash('success', 'Requirement uploaded.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=student_documents');
    }

    public function uploadRequirementsBulk(): void
    {
        require_role('student');
        $this->post();
        $student = (new Student($this->db))->findByUser(current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('index.php?r=student');
        }
        try {
            if (!(new Enrollment($this->db))->detailsByStudent((int)$student['id'])) {
                throw new RuntimeException('You must be enrolled in OJT before uploading pre-deployment requirements.');
            }

            $studentModel = new Student($this->db);
            $selectedFiles = [];
            foreach ($studentModel->requirementDefinitions() as $requirementKey => $definition) {
                $file = $_FILES['requirements']['name'][$requirementKey] ?? '';
                if ($file === '') {
                    continue;
                }
                if (!$studentModel->canUploadRequirement((int)$student['id'], (string)$requirementKey)) {
                    throw new RuntimeException($definition['name'] . ': ' . $studentModel->requirementUploadMessage((int)$student['id'], (string)$requirementKey) . '.');
                }
                $selectedFiles[$requirementKey] = [
                    'name' => $_FILES['requirements']['name'][$requirementKey] ?? '',
                    'type' => $_FILES['requirements']['type'][$requirementKey] ?? '',
                    'tmp_name' => $_FILES['requirements']['tmp_name'][$requirementKey] ?? '',
                    'error' => $_FILES['requirements']['error'][$requirementKey] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $_FILES['requirements']['size'][$requirementKey] ?? 0,
                ];
            }

            if (empty($selectedFiles)) {
                throw new RuntimeException('Choose at least one requirement file to upload.');
            }

            $uploadedCount = 0;
            foreach ($selectedFiles as $requirementKey => $singleFile) {
                $path = upload_document($singleFile, 'requirements/' . (int)$student['id']);
                $studentModel->saveRequirement((int)$student['id'], (string)$requirementKey, $path);
                $uploadedCount++;
            }
            flash('success', $uploadedCount . ' requirement file' . ($uploadedCount === 1 ? '' : 's') . ' uploaded.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=student_documents');
    }

    public function submitRequirements(): void
    {
        require_role('student');
        $studentModel = new Student($this->db);
        $student = $studentModel->findByUser(current_user()['id']);
        if (!$student || !$studentModel->hasCompleteRequirements((int)$student['id'])) {
            flash('error', 'Upload all five requirements before submitting for review.');
            redirect('index.php?r=student_documents');
        }
        $enrollment = (new Enrollment($this->db))->detailsByStudent((int)$student['id']);
        if (!$enrollment) {
            flash('error', 'You must be enrolled in OJT before submitting pre-deployment requirements.');
            redirect('index.php?r=student_documents');
        }
        $predeploymentStatus = $enrollment['predeployment_status'] ?? 'not_submitted';
        if ($studentModel->hasApprovedRequirements((int)$student['id'])) {
            flash('success', 'All documents have already been approved. No need to submit again.');
            redirect('index.php?r=student_documents');
        }
        if ($predeploymentStatus === 'submitted') {
            flash('error', 'Your documents are already under coordinator review.');
            redirect('index.php?r=student_documents');
        }
        if ($predeploymentStatus === 'needs_revision') {
            flash('error', 'Replace the rejected document first. Only rejected documents are unlocked.');
            redirect('index.php?r=student_documents');
        }
        if (in_array($predeploymentStatus, ['approved', 'forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed'], true)) {
            flash('success', 'Your documents are already approved or in deployment processing.');
            redirect('index.php?r=student_documents');
        }
        (new Enrollment($this->db))->setPredeploymentStatus((int)$student['id'], 'submitted');
        (new Notification($this->db))->create((int)$student['coordinator_id'], 'Pre-deployment review requested', $student['name'] . ' submitted all pre-deployment requirements for review.', route_url('coordinator.students'));
        flash('success', 'Pre-deployment requirements submitted for coordinator review.');
        redirect('index.php?r=student_documents');
    }

    public function addDtr(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser(current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('index.php?r=student');
        }
        $enrollments = new Enrollment($this->db);
        $enrollment = $enrollments->detailsByStudent((int)$student['id']);
        try {
            assert_student_report_submission($enrollment, (string)($p['work_date'] ?? ''));
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('index.php?r=student_records');
        }
        try {
            (new Report($this->db))->addDtr(
                (int)$student['id'],
                (string)($p['work_date'] ?? ''),
                (string)($p['day_type'] ?? 'full'),
                $p['morning_time_in'] ?? '',
                $p['morning_time_out'] ?? '',
                $p['afternoon_time_in'] ?? '',
                $p['afternoon_time_out'] ?? '',
                trim((string)($p['tasks_done'] ?? ''))
            );
            (new Report($this->db))->clearDtrDraft((int)$student['id']);
            $enrollments->syncCompletion((int)$student['id']);
            $company = (new Company($this->db))->findByEnrollmentStudent((int)$student['id']);
            if ($company) {
                (new Notification($this->db))->create((int)$company['user_id'], 'New DTR pending approval', $student['name'] . ' submitted a DTR for ' . date('M d, Y', strtotime((string)($p['work_date'] ?? ''))) . '. Please review.', route_url('partner.submissions', ['student_id' => (int)$student['id'], 'tab' => 'dtr']));
            }
            flash('success', 'Daily time record submitted. Awaiting Host Training Establishment approval.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=student_records');
    }

    public function addWeekly(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser(current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('index.php?r=student');
        }
        $enrollments = new Enrollment($this->db);
        $enrollment = $enrollments->detailsByStudent((int)$student['id']);
        try {
            assert_student_report_submission($enrollment);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('index.php?r=student_records');
        }
        try {
            $pdfPath = null;
            $accomplishments = trim($p['accomplishments'] ?? '');
            if (mb_strlen($accomplishments) > 2000) {
                $accomplishments = mb_substr($accomplishments, 0, 2000);
            }
            $dateStart = !empty($p['date_covered_start']) ? date('Y-m-d', strtotime($p['date_covered_start'])) : null;
            $dateEnd = !empty($p['date_covered_end']) ? date('Y-m-d', strtotime($p['date_covered_end'])) : null;

            $report = new Report($this->db);
            $reportId = $report->addWeekly(
                (int)$student['id'],
                (int)($p['week_no'] ?? 0),
                trim($p['report_text'] ?? ''),
                $pdfPath,
                $accomplishments ?: null,
                $dateStart,
                $dateEnd
            );

            $this->uploadProofFiles($report, $reportId, (int)$student['id']);

            $company = (new Company($this->db))->findByEnrollmentStudent((int)$student['id']);
            if ($company) {
                (new Notification($this->db))->create((int)$company['user_id'], 'New Weekly Report pending approval', $student['name'] . ' submitted weekly report #' . (int)($p['week_no'] ?? 0) . '. Please review.', route_url('partner.submissions', ['student_id' => (int)$student['id'], 'tab' => 'weekly']));
            }
            flash('success', 'Weekly report submitted. Awaiting Host Training Establishment approval.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=student_records');
    }

    public function saveDtrDraft(): void
    {
        require_role('student');
        verify_csrf();
        $p = $this->post();
        $student = (new Student($this->db))->findByUser(current_user()['id']);
        header('Content-Type: application/json');
        if (!$student) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Student record not found.']);
            return;
        }
        $enrollment = (new Enrollment($this->db))->detailsByStudent((int)$student['id']);
        try {
            assert_student_report_submission(
                $enrollment,
                trim((string)($p['work_date'] ?? '')) ?: null
            );
        } catch (RuntimeException $e) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
            return;
        }
        (new Report($this->db))->saveDtrDraft(
            (int)$student['id'],
            trim((string)($p['work_date'] ?? '')) ?: null,
            (string)($p['day_type'] ?? 'full'),
            trim((string)($p['morning_time_in'] ?? '')) ?: null,
            trim((string)($p['morning_time_out'] ?? '')) ?: null,
            trim((string)($p['afternoon_time_in'] ?? '')) ?: null,
            trim((string)($p['afternoon_time_out'] ?? '')) ?: null,
            !empty($p['morning_time_in_locked']),
            !empty($p['morning_time_out_locked']),
            !empty($p['afternoon_time_in_locked']),
            !empty($p['afternoon_time_out_locked'])
        );
        echo json_encode(['ok' => true]);
    }

    private function uploadReport(array $file, bool $required = true): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            if ($required) {
                throw new RuntimeException('Report file is required.');
            }
            return null;
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new RuntimeException('Report file must not exceed 10MB.');
        }
        $allowed = ['application/pdf' => 'pdf'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Weekly report upload must be PDF.');
        }
        $dir = __DIR__ . '/../uploads/reports';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = bin2hex(random_bytes(16)) . '.pdf';
        move_uploaded_file($file['tmp_name'], $dir . '/' . $name);
        return 'uploads/reports/' . $name;
    }

    private function uploadProofFiles(Report $report, int $reportId, int $studentId): void
    {
        $files = $_FILES['proof_files'] ?? null;
        if (!$files || !is_array($files['name'] ?? null)) return;

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
        ];
        $dir = __DIR__ . '/../uploads/proof/' . $studentId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            if ($files['size'][$i] > 10 * 1024 * 1024) continue;

            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($files['tmp_name'][$i]);
            if (!isset($allowed[$mime])) continue;

            $ext = $allowed[$mime];
            $safeName = bin2hex(random_bytes(12)) . '.' . $ext;
            $originalName = htmlspecialchars(basename($files['name'][$i]), ENT_QUOTES, 'UTF-8');

            move_uploaded_file($files['tmp_name'][$i], $dir . '/' . $safeName);
            $report->addWeeklyProofFile(
                $reportId,
                'uploads/proof/' . $studentId . '/' . $safeName,
                $originalName,
                $ext,
                (int)$files['size'][$i]
            );
        }
    }

    public function resubmitDtr(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser(current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('index.php?r=student_records');
        }
        $dtrId = (int)($p['dtr_id'] ?? 0);
        $enrollments = new Enrollment($this->db);
        $enrollment = $enrollments->detailsByStudent((int)$student['id']);
        $reportModel = new Report($this->db);
        $existing = $reportModel->findDtr($dtrId);
        try {
            if (!$existing || (int)$existing['student_id'] !== (int)$student['id']) {
                throw new RuntimeException('Daily time record not found.');
            }
            assert_student_dtr_resubmit($enrollment, $existing);
            $reportModel->resubmitDtr(
                $dtrId,
                (int)$student['id'],
                (string)($p['day_type'] ?? 'full'),
                $p['morning_time_in'] ?? '',
                $p['morning_time_out'] ?? '',
                $p['afternoon_time_in'] ?? '',
                $p['afternoon_time_out'] ?? '',
                trim((string)($p['tasks_done'] ?? ''))
            );
            $enrollments->syncCompletion((int)$student['id']);
            $company = (new Company($this->db))->findByEnrollmentStudent((int)$student['id']);
            if ($company) {
                (new Notification($this->db))->create(
                    (int)$company['user_id'],
                    'Corrected DTR pending approval',
                    $student['name'] . ' resubmitted a corrected DTR for ' . date('M d, Y', strtotime((string)$existing['work_date'])) . '. Please review.',
                    route_url('partner.submissions', ['student_id' => (int)$student['id'], 'tab' => 'dtr'])
                );
            }
            flash('success', 'Corrected daily time record resubmitted. Awaiting Host Training Establishment approval.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=student_records&resubmit_dtr=' . $dtrId);
    }

    public function resubmitWeekly(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser(current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect('index.php?r=student_records');
        }
        $weeklyId = (int)($p['weekly_id'] ?? 0);
        $enrollments = new Enrollment($this->db);
        $enrollment = $enrollments->detailsByStudent((int)$student['id']);
        $reportModel = new Report($this->db);
        $existing = $reportModel->findWeekly($weeklyId);
        try {
            if (!$existing || (int)$existing['student_id'] !== (int)$student['id']) {
                throw new RuntimeException('Weekly report not found.');
            }
            assert_student_weekly_resubmit($enrollment, $existing);
            $reportModel->resubmitWeekly(
                $weeklyId,
                (int)$student['id'],
                (string)($p['accomplishments'] ?? ''),
                (string)($p['date_covered_start'] ?? ''),
                (string)($p['date_covered_end'] ?? ''),
                (string)($p['report_text'] ?? '')
            );
            if (!empty($_FILES['proof_files']['name'][0] ?? '')) {
                $reportModel->clearWeeklyProofFiles($weeklyId);
                $this->uploadProofFiles($reportModel, $weeklyId, (int)$student['id']);
            }
            $company = (new Company($this->db))->findByEnrollmentStudent((int)$student['id']);
            if ($company) {
                (new Notification($this->db))->create(
                    (int)$company['user_id'],
                    'Corrected weekly report pending approval',
                    $student['name'] . ' resubmitted corrected weekly report #' . (int)$existing['week_no'] . '. Please review.',
                    route_url('partner.submissions', ['student_id' => (int)$student['id'], 'tab' => 'weekly'])
                );
            }
            flash('success', 'Corrected weekly report resubmitted. Awaiting Host Training Establishment approval.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=student_records&resubmit_weekly=' . $weeklyId);
    }

    private function studentPageData(string $title): array
    {
        require_role('student');
        $studentModel = new Student($this->db);
        $student = $studentModel->findByUser(current_user()['id']);
        $enrollmentModel = new Enrollment($this->db);
        $enrollment = $student ? $enrollmentModel->detailsByStudent((int)$student['id']) : null;
        $requirements = $student ? $studentModel->requirements((int)$student['id']) : [];
        if ($student && $enrollment) {
            $enrollment['predeployment_status'] = $studentModel->effectivePredeploymentStatus((int)$student['id'], $enrollment['predeployment_status'] ?? null, $requirements);
        }
        $reports = new Report($this->db);
        $dtrs = $student ? $reports->dtrByStudent((int)$student['id']) : [];
        $weeklyReports = $student ? $reports->weeklyByStudent((int)$student['id']) : [];
        $approvedHours = $student ? $reports->totalHours((int)$student['id'], true) : 0.0;
        $finalRequirement = $student ? (new FinalRequirement($this->db))->getByStudent((int)$student['id']) : [];
        $studentEvaluation = $student ? (new StudentEvaluation($this->db))->getByStudent((int)$student['id']) : [];
        $hteEvaluation = null;
        if (!empty($enrollment['id'])) {
            $hteEvaluation = (new Evaluation($this->db))->byEnrollment((int)$enrollment['id']);
        }
        $canSubmitReports = $enrollmentModel->allowsReports($enrollment);
        $canAccessFinalRequirements = enrollment_allows_final_requirements($enrollment, $approvedHours);
        $ojtCompletion = student_ojt_completion_status([
            'enrollment' => $enrollment,
            'approvedHours' => $approvedHours,
            'finalRequirement' => $finalRequirement,
            'studentEvaluation' => $studentEvaluation,
            'hteEvaluation' => $hteEvaluation,
        ]);
        $pageContext = [
            'requirements' => $requirements,
            'dtrs' => $dtrs,
            'weeklyReports' => $weeklyReports,
            'predeploymentStatus' => $enrollment ? ($enrollment['predeployment_status'] ?? 'not_submitted') : 'not_submitted',
            'canSubmitReports' => $canSubmitReports,
            'canAccessFinalRequirements' => $canAccessFinalRequirements,
            'ojtCompletion' => $ojtCompletion,
        ];

        return [
            'title' => $title,
            'student' => $student,
            'enrollment' => $enrollment,
            'canSubmitReports' => $canSubmitReports,
            'reportLockMessage' => $enrollmentModel->reportLockMessage($enrollment),
            'canAccessFinalRequirements' => $canAccessFinalRequirements,
            'finalRequirementsLockMessage' => enrollment_final_requirements_lock_message($enrollment, $approvedHours),
            'approvedHours' => $approvedHours,
            'dtrs' => $dtrs,
            'dtrDraft' => $student ? $reports->dtrDraftByStudent((int)$student['id']) : [],
            'weeklyReports' => $weeklyReports,
            'hours' => $student ? $reports->totalHours((int)$student['id']) : 0,
            'requirements' => $requirements,
            'finalRequirement' => $finalRequirement,
            'studentEvaluation' => $studentEvaluation,
            'hteEvaluation' => $hteEvaluation,
            'ojtCompletion' => $ojtCompletion,
            'actionAlerts' => student_action_alerts($pageContext),
            'upcomingDeadlines' => student_upcoming_deadlines(array_merge($pageContext, ['enrollment' => $enrollment])),
            'recentNotifications' => (new Notification($this->db))->recentForUser((int)current_user()['id'], 5),
        ];
    }
}
