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
        $role = (string)(current_user()['role'] ?? 'student');
        $accountLabel = match ($role) {
            'coordinator' => 'coordinators',
            'partner' => 'Host Training Establishments',
            'admin' => 'administrators',
            default => 'students',
        };
        $portalLabel = match ($role) {
            'coordinator' => 'coordinator portal',
            'partner' => 'Host Training Establishment portal',
            'admin' => 'admin portal',
            default => 'OJT portal',
        };
        $this->renderAppPage('student/change_password', [
            'title' => $isFirstLogin ? 'Change Temporary Password' : 'Change Password',
            'csrfToken' => csrf_token(),
            'isFirstLogin' => $isFirstLogin,
            'accountLabel' => $accountLabel,
            'portalLabel' => $portalLabel,
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

            $role = (string)(current_user()['role'] ?? 'student');
            $postChangeRedirect = match (true) {
                $wasTemporary => route_for_role($role),
                $role === 'student' => route_url('student.settings'),
                $role === 'partner' => route_url('partner.settings'),
                default => route_for_role($role),
            };

            echo json_encode([
                'ok' => true,
                'message' => $wasTemporary
                    ? 'Password changed successfully. You can now access your dashboard.'
                    : 'Password changed successfully.',
                'redirect' => $postChangeRedirect,
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
        $data = $this->studentPageData('Activity Timeline');
        $data['timelineEntries'] = build_student_timeline_entries($data['dtrs'] ?? [], $data['weeklyReports'] ?? []);
        $data['timelineStats'] = [
            'dtrCount' => count($data['dtrs'] ?? []),
            'weeklyCount' => count($data['weeklyReports'] ?? []),
            'totalHours' => array_sum(array_map(static fn(array $d): float => (float)($d['hours'] ?? 0), $data['dtrs'] ?? [])),
        ];
        $this->renderAppPage('student/timeline', $data);
    }

    public function documents(): void
    {
        $data = $this->studentPageData('Documents');
        $student = $data['student'] ?? null;
        $enrollment = $data['enrollment'] ?? null;
        $activeStage = (int)($_GET['stage'] ?? 1);
        if (!in_array($activeStage, [1, 2, 3], true)) {
            $activeStage = 1;
        }
        if ($student) {
            $studentModel = new Student($this->db);
            $sid = (int)$student['id'];
            if (!$studentModel->canAccessStage($sid, $activeStage)) {
                $fallbackStage = $studentModel->highestAccessibleDocumentStage($sid);
                flash('info', 'That comply stage is locked. Showing your current available stage.');
                redirect(route_url('student.documents', ['stage' => $fallbackStage]));
            }
        }
        $data['activeStage'] = $activeStage;
        $data['activeWorkflowStep'] = $activeStage;
        $data['stages'] = $this->buildDocumentStages($student, $enrollment);

        $eval = (string)($_GET['eval'] ?? '');
        $doc = (string)($_GET['doc'] ?? '');
        $data['activePanel'] = '';
        $data['activeKind'] = '';
        $data['studentEvaluation'] = $student ? (new StudentEvaluation($this->db))->getByStudent((int)$student['id']) : [];
        $data['evaluationSections'] = FinalRequirement::EVALUATION_SECTIONS;
        $data['finalRequirement'] = $student ? (new FinalRequirement($this->db))->getByStudent((int)$student['id']) : [];
        $formKeys = student_stage3_form_requirement_keys();

        if ($activeStage === 3 && $eval !== '') {
            if (!$data['canAccessFinalRequirements']) {
                flash('error', $data['finalRequirementsLockMessage']);
                redirect(route_url('student.documents', ['stage' => 3]));
            }
            if (array_key_exists($eval, FinalRequirement::EVALUATION_SECTIONS)) {
                $data['activePanel'] = $eval;
                $data['activeKind'] = 'eval';
                $data['title'] = FinalRequirement::EVALUATION_SECTIONS[$eval]['name'];
            }
        } elseif ($activeStage === 3 && $doc !== '') {
            $formKey = requirement_form_section_key($doc) ?? (isset($formKeys[$doc]) ? $doc : null);
            if ($formKey !== null && isset($formKeys[$formKey])) {
                $requirementKey = $formKeys[$formKey];
                $data['activePanel'] = $formKey;
                $data['activeKind'] = 'doc';
                $data['activeFormRequirementKey'] = $requirementKey;
                $data['title'] = Student::REQUIREMENTS[$requirementKey]['name'] ?? FinalRequirement::SECTIONS[$formKey]['name'] ?? 'Document';
                $reqRow = $data['stages'][3]['requirements'][$requirementKey] ?? null;
                $data['canEditForm'] = $student
                    ? (new Student($this->db))->canUploadRequirement((int)$student['id'], $requirementKey)
                    : false;
                $data['formRequirementStatus'] = (string)($reqRow['status'] ?? 'pending');
            }
        }

        $this->renderAppPage('student/documents', $data);
    }

    public function viewEndorsementLetter(): void
    {
        require_role('student');
        $student = (new Student($this->db))->findByUser((int)current_user()['id']);
        if (!$student) {
            http_response_code(403);
            exit('Forbidden');
        }

        $enrollmentId = (int)($_GET['enrollment'] ?? 0);
        if ($enrollmentId <= 0) {
            http_response_code(400);
            exit('Invalid enrollment ID.');
        }

        $enrollment = (new Enrollment($this->db))->find($enrollmentId);
        if (!$enrollment || (int)$enrollment['student_id'] !== (int)$student['id']) {
            http_response_code(403);
            exit('You do not have access to this endorsement letter.');
        }

        $studentModel = new Student($this->db);
        $predeployment = $studentModel->normalizePredeploymentStatus($enrollment['predeployment_status'] ?? null);
        $available = $studentModel->isPredeploymentPipelineAdvanced($predeployment)
            || ($enrollment['status'] ?? '') === 'completed'
            || trim((string)($enrollment['endorsement_file'] ?? '')) !== '';
        if (!$available) {
            http_response_code(403);
            exit('Endorsement letter is not available yet.');
        }

        try {
            $pdfContent = (new EndorsementLetter($this->db))->generatePdfBuffer((int)$student['id'], (int)$enrollment['id']);
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Endorsement_Letter.pdf"');
            header('Content-Length: ' . strlen($pdfContent));
            header('Cache-Control: private, max-age=300');
            echo $pdfContent;
            exit;
        } catch (Throwable $e) {
            error_log('Student endorsement letter view failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Unable to generate the endorsement letter right now. Please try again later.');
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDocumentStages(?array $student, ?array $enrollment): array
    {
        if (!$student) {
            return [];
        }
        $studentModel = new Student($this->db);
        $sid = (int)$student['id'];
        $endorsementFile = $enrollment['endorsement_file'] ?? null;
        $stages = [];
        foreach ([1, 2, 3] as $stage) {
            $rows = $studentModel->stageRequirements($sid, $stage);
            foreach ($rows as $key => $row) {
                // Stage 2 endorsement letter is provided by the coordinator on forward.
                if ($key === 'endorsement_letter' && empty($row['file_path']) && !empty($endorsementFile)) {
                    if ($endorsementFile === '(generated-pdf)' && !empty($enrollment['id'])) {
                        $row['view_url'] = route_url('student.view_endorsement', ['enrollment' => (int)$enrollment['id']]);
                        $row['file_path'] = null;
                    } else {
                        $row['file_path'] = $endorsementFile;
                    }
                    $row['status'] = 'approved';
                }
                $row['can_upload'] = $studentModel->canUploadRequirement($sid, $key);
                $row['upload_message'] = $studentModel->requirementUploadMessage($sid, $key);
                $rows[$key] = $row;
            }
            $stages[$stage] = [
                'number' => $stage,
                'label' => Student::STAGE_LABELS[$stage] ?? ('Stage ' . $stage),
                'accessible' => $studentModel->canAccessStage($sid, $stage),
                'status' => $studentModel->stageAggregateStatus($sid, $stage),
                'requirements' => $rows,
                'lock_message' => $this->stageLockMessage($stage, $enrollment),
            ];
        }

        return $stages;
    }

    private function stageLockMessage(int $stage, ?array $enrollment): string
    {
        if ($stage === 1) {
            return 'Complete your student profile to unlock 1st to Comply.';
        }
        if ($stage === 2) {
            return 'Unlocks once all of your 1st to Comply documents are approved.';
        }
        if ($stage === 3) {
            return 'Unlocks once your OJT deployment is active (after orientation).';
        }
        return 'Locked.';
    }

    public function documentsFinal(): void
    {
        require_role('student');
        $params = ['stage' => 3];
        $eval = (string)($_GET['eval'] ?? '');
        $doc = (string)($_GET['doc'] ?? '');
        $target = route_url('student.documents', $params);
        if ($eval !== '') {
            $params['eval'] = $eval;
            redirect(route_url('student.documents', $params));
        }
        if ($doc !== '' && array_key_exists($doc, FinalRequirement::EVALUATION_SECTIONS)) {
            $params['eval'] = $doc;
            redirect(route_url('student.documents', $params));
        }
        $formKeys = student_stage3_form_requirement_keys();
        $formKey = requirement_form_section_key($doc);
        if ($formKey !== null && isset($formKeys[$formKey])) {
            $params['doc'] = $formKey;
            redirect(route_url('student.documents', $params));
        }
        $legacyAliases = student_stage3_legacy_doc_aliases();
        if ($doc !== '') {
            $requirementKey = $legacyAliases[$doc] ?? $doc;
            redirect($target . '#requirement-' . rawurlencode($requirementKey));
        }
        redirect($target);
    }

    public function saveFinalJobDescription(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser((int)current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect(route_url('student.documents', ['stage' => 3]));
        }
        $requirementKey = 'job_description_doc';
        $studentModel = new Student($this->db);
        try {
            if (!$studentModel->canUploadRequirement((int)$student['id'], $requirementKey)) {
                throw new RuntimeException($studentModel->requirementUploadMessage((int)$student['id'], $requirementKey) . '.');
            }
            (new FinalRequirement($this->db))->saveJobDescription(
                (int)$student['id'],
                (string)($p['position_held'] ?? ''),
                (string)($p['job_description'] ?? '')
            );
            $studentModel->saveFormRequirement((int)$student['id'], $requirementKey);
            $this->notifyCoordinatorRequirementUpload($student, $requirementKey, $studentModel);
            flash('success', 'Job Description saved for review.');
            redirect(route_url('student.documents', ['stage' => 3]) . '#requirement-' . $requirementKey);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect(route_url('student.documents', ['stage' => 3, 'doc' => 'job_description']));
        }
    }

    public function saveFinalCompanyProfile(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser((int)current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect(route_url('student.documents', ['stage' => 3]));
        }
        $requirementKey = 'company_profile_doc';
        $studentModel = new Student($this->db);
        try {
            if (!$studentModel->canUploadRequirement((int)$student['id'], $requirementKey)) {
                throw new RuntimeException($studentModel->requirementUploadMessage((int)$student['id'], $requirementKey) . '.');
            }
            (new FinalRequirement($this->db))->saveCompanyProfile(
                (int)$student['id'],
                (string)($p['company_history'] ?? ''),
                (string)($p['company_description'] ?? ''),
                (string)($p['company_mission'] ?? ''),
                (string)($p['company_vision'] ?? '')
            );
            $studentModel->saveFormRequirement((int)$student['id'], $requirementKey);
            $this->notifyCoordinatorRequirementUpload($student, $requirementKey, $studentModel);
            flash('success', 'Company Profile saved for review.');
            redirect(route_url('student.documents', ['stage' => 3]) . '#requirement-' . $requirementKey);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect(route_url('student.documents', ['stage' => 3, 'doc' => 'company_profile']));
        }
    }

    public function saveFinalPersonalObservation(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser((int)current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect(route_url('student.documents', ['stage' => 3]));
        }
        $requirementKey = 'personal_observation_doc';
        $studentModel = new Student($this->db);
        try {
            if (!$studentModel->canUploadRequirement((int)$student['id'], $requirementKey)) {
                throw new RuntimeException($studentModel->requirementUploadMessage((int)$student['id'], $requirementKey) . '.');
            }
            $fields = [];
            foreach (array_keys(FinalRequirement::PERSONAL_OBSERVATION_FIELDS) as $column) {
                $fields[$column] = (string)($p[$column] ?? '');
            }
            (new FinalRequirement($this->db))->savePersonalObservation((int)$student['id'], $fields);
            $studentModel->saveFormRequirement((int)$student['id'], $requirementKey);
            $this->notifyCoordinatorRequirementUpload($student, $requirementKey, $studentModel);
            flash('success', 'Personal Observation saved for review.');
            redirect(route_url('student.documents', ['stage' => 3]) . '#requirement-' . $requirementKey);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect(route_url('student.documents', ['stage' => 3, 'doc' => 'personal_observation']));
        }
    }

    public function saveStudentEvaluationPartner(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser((int)current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect(route_url('student.documents', ['stage' => 3]));
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
            $studentName = (string)($student['name'] ?? 'A student');
            $title = $wasSubmitted ? 'Host training establishment evaluation updated' : 'Host training establishment evaluation received';
            $message = $wasSubmitted
                ? $studentName . ' updated their evaluation of the host training establishment and OJT supervisor.'
                : $studentName . ' submitted an evaluation of the host training establishment and OJT supervisor.';
            if ($coordinatorUserId > 0) {
                (new Notification($this->db))->create(
                    $coordinatorUserId,
                    $title,
                    $message,
                    route_url('coordinator.student_final', ['student_id' => (int)$student['id'], 'eval' => 'industry_partner'])
                );
            }
            if ($enrollment && !empty($enrollment['company_id'])) {
                $company = (new Company($this->db))->find((int)$enrollment['company_id']);
                if ($company && !empty($company['user_id'])) {
                    (new Notification($this->db))->create(
                        (int)$company['user_id'],
                        $wasSubmitted ? 'Student feedback updated' : 'New student feedback received',
                        $studentName . ' ' . ($wasSubmitted ? 'updated' : 'submitted') . ' an evaluation of your organization and OJT supervisor.',
                        route_url('partner.student_evaluation', ['student_id' => (int)$student['id']])
                    );
                }
            }
            flash('success', 'Host Training Establishment evaluation saved.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect(route_url('student.documents', ['stage' => 3, 'eval' => 'industry_partner']));
        }
        redirect(route_url('student.documents', ['stage' => 3]));
    }

    public function saveStudentEvaluationCoordinator(): void
    {
        require_role('student');
        $p = $this->post();
        $student = (new Student($this->db))->findByUser((int)current_user()['id']);
        if (!$student) {
            flash('error', 'Student record not found.');
            redirect(route_url('student.documents', ['stage' => 3]));
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
            redirect(route_url('student.documents', ['stage' => 3, 'eval' => 'coordinator']));
        }
        redirect(route_url('student.documents', ['stage' => 3]));
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
            foreach (['contact_number', 'emergency_contact_name', 'emergency_contact_number', 'year_level', 'gender'] as $field) {
                if (trim((string)($p[$field] ?? '')) === '') {
                    throw new RuntimeException('Please complete all required profile fields.');
                }
            }

            $legacyAddressOnly = student_has_legacy_address_only($student);
            $structuredSubmitted = student_address_payload_has_structured($p);

            if (!$legacyAddressOnly || $structuredSubmitted) {
                if (!student_structured_address_is_complete($p)) {
                    throw new RuntimeException('Please complete province, municipality/city, barangay, and street address.');
                }
            } elseif (trim((string)($student['address'] ?? '')) === '') {
                throw new RuntimeException('Please complete your home address.');
            }

            if (!in_array(trim((string)($p['gender'] ?? '')), ['Male', 'Female', 'Other'], true)) {
                throw new RuntimeException('Please select a valid gender.');
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
            if (!$wasCompleted) {
                $_SESSION['student_nav_reveal'] = 1;
            }
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
            $requirementKey = trim($p['requirement_key'] ?? '');
            $studentModel = new Student($this->db);
            if ((Student::REQUIREMENTS[$requirementKey]['kind'] ?? 'upload') === 'form') {
                throw new RuntimeException('Complete this requirement using the form in 3rd to Comply.');
            }
            if (!$studentModel->canUploadRequirement((int)$student['id'], $requirementKey)) {
                throw new RuntimeException($studentModel->requirementUploadMessage((int)$student['id'], $requirementKey) . '.');
            }
            $path = upload_document($_FILES['requirement_file'] ?? [], 'requirements/' . (int)$student['id']);
            $studentModel->saveRequirement((int)$student['id'], $requirementKey, $path);
            $this->notifyCoordinatorRequirementUpload($student, $requirementKey, $studentModel);
            flash('success', 'Requirement uploaded.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirectToStudentDocuments($requirementKey);
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
            $notifiedStages = [];
            foreach ($selectedFiles as $requirementKey => $singleFile) {
                $path = upload_document($singleFile, 'requirements/' . (int)$student['id']);
                $studentModel->saveRequirement((int)$student['id'], (string)$requirementKey, $path);
                $stage = $studentModel->requirementStage((string)$requirementKey);
                if (!isset($notifiedStages[$stage]) && $this->notifyCoordinatorRequirementUpload($student, (string)$requirementKey, $studentModel)) {
                    $notifiedStages[$stage] = true;
                }
                $uploadedCount++;
            }
            flash('success', $uploadedCount . ' requirement file' . ($uploadedCount === 1 ? '' : 's') . ' uploaded.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirectToStudentDocuments(null, 1);
    }

    public function submitRequirements(): void
    {
        require_role('student');
        $studentModel = new Student($this->db);
        $student = $studentModel->findByUser(current_user()['id']);
        if (!$student || !$studentModel->hasCompleteRequirements((int)$student['id'])) {
            flash('error', 'Upload all 1st to Comply requirements before submitting for review.');
            $this->redirectToStudentDocuments(null, 1);
        }
        $enrollment = (new Enrollment($this->db))->detailsByStudent((int)$student['id']);
        if ($studentModel->hasApprovedRequirements((int)$student['id'])) {
            flash('success', 'All documents have already been approved. No need to submit again.');
            $this->redirectToStudentDocuments(null, 1);
        }
        if ($studentModel->hasRejectedRequirements((int)$student['id'])) {
            flash('error', 'Replace the rejected document first. Only rejected documents are unlocked.');
            $this->redirectToStudentDocuments(null, 1);
        }
        if (!$enrollment) {
            (new Notification($this->db))->create((int)$student['coordinator_id'], 'Pre-deployment review requested', $student['name'] . ' submitted all pre-deployment requirements for review.', route_url('coordinator.students', ['focus_student' => (int)$student['id']]));
            flash('success', 'Pre-deployment requirements submitted for coordinator review.');
            $this->redirectToStudentDocuments(null, 1);
        }
        $predeploymentStatus = $enrollment['predeployment_status'] ?? 'not_submitted';
        if ($predeploymentStatus === 'submitted') {
            flash('error', 'Your documents are already under coordinator review.');
            $this->redirectToStudentDocuments(null, 1);
        }
        if ($predeploymentStatus === 'needs_revision') {
            flash('error', 'Replace the rejected document first. Only rejected documents are unlocked.');
            $this->redirectToStudentDocuments(null, 1);
        }
        if (in_array($predeploymentStatus, ['approved', 'forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed'], true)) {
            flash('success', 'Your documents are already approved or in deployment processing.');
            $this->redirectToStudentDocuments(null, 1);
        }
        (new Enrollment($this->db))->setPredeploymentStatus((int)$student['id'], 'submitted');
        (new Notification($this->db))->create((int)$student['coordinator_id'], 'Pre-deployment review requested', $student['name'] . ' submitted all pre-deployment requirements for review.', route_url('coordinator.students', ['focus_student' => (int)$student['id']]));
        flash('success', 'Pre-deployment requirements submitted for coordinator review.');
        $this->redirectToStudentDocuments(null, 1);
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
            // Validate whether the student is allowed to submit reports for the given date.
            // For draft saves we allow the check to fail so students can save drafts
            // (e.g., before official OJT start date) without being blocked. Final
            // submission still enforces validation in `addDtr()`.
            assert_student_report_submission(
                $enrollment,
                trim((string)($p['work_date'] ?? '')) ?: null
            );
        } catch (RuntimeException $e) {
            // Intentionally ignore the validation error for draft saves to allow
            // users to preserve their time entries across refreshes.
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
        $seenInRequest = [];
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            if ($files['size'][$i] > 10 * 1024 * 1024) continue;

            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($files['tmp_name'][$i]);
            if (!isset($allowed[$mime])) continue;

            $originalName = basename((string)$files['name'][$i]);
            $dedupeKey = strtolower($originalName) . '|' . (int)$files['size'][$i];
            // Guard against double-append from confirm dialog re-submit.
            if (isset($seenInRequest[$dedupeKey])) {
                continue;
            }
            $seenInRequest[$dedupeKey] = true;

            $ext = $allowed[$mime];
            $safeName = bin2hex(random_bytes(12)) . '.' . $ext;
            $safeOriginal = htmlspecialchars($originalName, ENT_QUOTES, 'UTF-8');

            move_uploaded_file($files['tmp_name'][$i], $dir . '/' . $safeName);
            $report->addWeeklyProofFile(
                $reportId,
                'uploads/proof/' . $studentId . '/' . $safeName,
                $safeOriginal,
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
        if ($student) {
            $studentModel->syncPredeploymentStatusIfComplete((int)$student['id']);
            $enrollment = $enrollmentModel->detailsByStudent((int)$student['id']);
        }
        $requirements = $student ? $studentModel->requirements((int)$student['id']) : [];
        if ($student && $enrollment) {
            $enrollment['predeployment_status'] = $studentModel->effectivePredeploymentStatus((int)$student['id'], $enrollment['predeployment_status'] ?? null, $requirements);
        }
        $reports = new Report($this->db);
        $isDashboard = ($title === 'Student Dashboard');
        $dtrTotalCount = null;
        $dtrRejectedCount = null;
        $todayDtr = null;
        $weeklyTotalCount = null;
        if ($student) {
            $studentId = (int)$student['id'];
            if ($isDashboard) {
                $dtrs = $reports->dtrByStudent($studentId, 4);
                $weeklyReports = $reports->weeklyByStudent($studentId, 1);
                $dtrTotalCount = $reports->dtrCountByStudent($studentId);
                $dtrRejectedCount = $reports->dtrRejectedCountByStudent($studentId);
                $weeklyTotalCount = $reports->weeklyCountByStudent($studentId);
                $todayDtr = $reports->dtrByStudentOnDate($studentId, date('Y-m-d'));
            } else {
                $dtrs = $reports->dtrByStudent($studentId);
                $weeklyReports = $reports->weeklyByStudent($studentId);
            }
        } else {
            $dtrs = [];
            $weeklyReports = [];
        }
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
            'predeploymentStatus' => $enrollment
                ? ($enrollment['predeployment_status'] ?? 'not_submitted')
                : ($student ? $studentModel->stageAggregateStatus((int)$student['id'], 1) : 'not_submitted'),
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
            'dtrTotalCount' => $dtrTotalCount,
            'dtrRejectedCount' => $dtrRejectedCount,
            'todayDtr' => $todayDtr,
            'weeklyTotalCount' => $weeklyTotalCount,
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

    /**
     * Notify coordinator when a student uploads a document that needs review.
     * Stage 1: only after deployment has already advanced (late replacement uploads).
     * Stage 2/3: always — there is no separate "submit for review" step.
     *
     * @return bool Whether a notification was sent
     */
    private function notifyCoordinatorRequirementUpload(array $student, string $requirementKey, Student $studentModel): bool
    {
        $stage = $studentModel->requirementStage($requirementKey);
        if ($stage < 1) {
            return false;
        }

        $coordinatorId = (int)($student['coordinator_id'] ?? 0);
        if ($coordinatorId <= 0) {
            return false;
        }

        $requirementName = Student::REQUIREMENTS[$requirementKey]['name'] ?? 'a document';
        $studentName = (string)($student['name'] ?? 'A student');

        if ($stage === 1) {
            $enrollment = (new Enrollment($this->db))->byStudent((int)$student['id']);
            if (!$enrollment) {
                if (!$studentModel->hasCompleteRequirements((int)$student['id'])) {
                    return false;
                }
                (new Notification($this->db))->create(
                    $coordinatorId,
                    'Pre-deployment review requested',
                    $studentName . ' submitted all pre-deployment requirements for review.',
                    route_url('coordinator.students', ['focus_student' => (int)$student['id']])
                );
                return true;
            }
            if (!$studentModel->isPredeploymentPipelineAdvanced($enrollment['predeployment_status'] ?? null)) {
                return false;
            }
            (new Notification($this->db))->create(
                $coordinatorId,
                'Pre-deployment document uploaded',
                $studentName . ' uploaded ' . $requirementName . ' for review.',
                route_url('coordinator.students', ['focus_student' => (int)$student['id']])
            );
            return true;
        }

        $stageLabel = Student::STAGE_LABELS[$stage] ?? 'Document';
        (new Notification($this->db))->create(
            $coordinatorId,
            $stageLabel . ' document uploaded',
            $studentName . ' uploaded ' . $requirementName . ' for review.',
            route_url('coordinator.student_final', ['student_id' => (int)$student['id']])
        );

        return true;
    }

    private function redirectToStudentDocuments(?string $requirementKey = null, int $defaultStage = 1): void
    {
        $stage = $defaultStage;
        if ($requirementKey !== null && $requirementKey !== '') {
            $resolvedStage = (new Student($this->db))->requirementStage($requirementKey);
            if ($resolvedStage > 0) {
                $stage = $resolvedStage;
            }
        }
        redirect(route_url('student.documents', ['stage' => $stage]));
    }
}
