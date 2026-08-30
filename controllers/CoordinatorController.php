<?php
class CoordinatorController extends BaseController
{
    public function dashboard(): void
    {
        require_role('coordinator');
        $students = new Student($this->db);
        $enroll = new Enrollment($this->db);
        $coordId = current_user()['id'];
        $this->renderAppPage('coordinator/dashboard', [
            'title' => 'Coordinator Dashboard',
            'stats' => [
                'students'  => $students->countByCoordinator($coordId),
                'enrolled'  => $enroll->countByCoordinator($coordId, 'active'),
                'completed' => $enroll->countByCoordinator($coordId, 'completed'),
                'pending'   => $enroll->countByCoordinator($coordId, 'pending'),
                'awaiting_start' => max(0, $students->countByCoordinator($coordId) - $enroll->countByCoordinator($coordId, 'active') - $enroll->countByCoordinator($coordId, 'completed')),
            ],
            'charts' => [
                'completionRates' => $enroll->completionRatesByCourseByCoordinator($coordId),
                'courseStudents' => $enroll->studentProgressByCourseByCoordinator($coordId),
            ],
        ]);
    }

    public function manage(): void
    {
        require_role('coordinator');
        $coordId = current_user()['id'];
        $companyModel = new Company($this->db);
        $companies = array_map(static function (array $company) use ($companyModel, $coordId): array {
            $companyId = (int)($company['id'] ?? 0);
            $company['moa_document_url'] = ($companyId > 0
                && !empty($company['moa_mou_file'])
                && $companyModel->coordinatorCanAccessMoa((int)$coordId, $companyId))
                ? route_url('coordinator.partner_document', ['company_id' => $companyId])
                : '';

            return $company;
        }, $companyModel->all());
        $this->renderAppPage('coordinator/manage', [
            'title' => 'Student Enrollment',
            'students'  => (new Student($this->db))->allByCoordinator($coordId),
            'companies' => $companies,
            'programs'  => (new Program($this->db))->all(true),
            'terms'     => (new Term($this->db))->all(),
        ]);
    }

    public function myStudents(): void
    {
        require_role('coordinator');
        $coordId = current_user()['id'];
        $students = (new Student($this->db))->allByCoordinator($coordId);
        $studentModel = new Student($this->db);
        $enrollModel = new Enrollment($this->db);
        $finalModel = new FinalRequirement($this->db);
        $studentEvalModel = new StudentEvaluation($this->db);

        $studentIds = array_map(static fn ($s) => (int)$s['id'], $students);
        $requirementsByStudent = $studentModel->requirementsForStudents($studentIds);
        $finalRequirementsByStudent = $finalModel->getByStudents($studentIds);
        $studentEvaluationsByStudent = $studentEvalModel->getByStudents($studentIds);
        $pendingFinalReviewByStudent = $studentModel->countPendingFinalReviewsByStudents($studentIds);
        $companyModel = new Company($this->db);

        foreach ($students as &$student) {
            $studentId = (int)$student['id'];
            $requirementsByStudent[$studentId] ??= $studentModel->requirements($studentId);
            $finalRequirementsByStudent[$studentId] ??= [];
            $studentEvaluationsByStudent[$studentId] ??= [];
            $studentModel->syncPredeploymentStatusIfComplete($studentId);
            // Re-read raw status after sync so the effective overlay sees the persisted value.
            $rawEnrollment = $enrollModel->byStudent($studentId);
            $student['predeployment_status'] = $studentModel->effectivePredeploymentStatus(
                $studentId,
                $rawEnrollment['predeployment_status'] ?? ($student['predeployment_status'] ?? null),
                $requirementsByStudent[$studentId]
            );
            $companyId = (int)($student['company_id'] ?? 0);
            $student['moa_document_url'] = ($companyId > 0
                && !empty($student['company_moa_mou_file'])
                && $companyModel->coordinatorCanAccessMoa($coordId, $companyId))
                ? route_url('coordinator.partner_document', ['company_id' => $companyId])
                : '';
        }
        unset($student);
        $totalStudents = count($students);
        $ojtStarted = $enrollModel->countByCoordinator($coordId, 'active');
        $ojtCompleted = $enrollModel->countByCoordinator($coordId, 'completed');
        $this->renderAppPage('coordinator/my_students', [
            'title' => 'My Students',
            'students' => $students,
            'requirementsByStudent' => $requirementsByStudent,
            'finalRequirementsByStudent' => $finalRequirementsByStudent,
            'studentEvaluationsByStudent' => $studentEvaluationsByStudent,
            'pendingFinalReviewByStudent' => $pendingFinalReviewByStudent,
            'evaluations' => (new Evaluation($this->db))->byCoordinator($coordId),
            'terms' => (new Term($this->db))->all(),
            'stats' => [
                'total' => $totalStudents,
                'started' => $ojtStarted,
                'not_started' => max(0, $totalStudents - $ojtStarted - $ojtCompleted),
                'completed' => $ojtCompleted,
            ],
        ]);
    }

    public function studentFinalRequirements(): void
    {
        require_role('coordinator');
        $studentId = (int)($_GET['student_id'] ?? 0);
        $coordinatorId = (int)current_user()['id'];
        $studentModel = new Student($this->db);
        if (!$studentModel->belongsToCoordinator($studentId, $coordinatorId)) {
            flash('error', 'Student not found or not assigned to you.');
            redirect('index.php?r=coordinator_students');
        }
        $student = $studentModel->find($studentId);
        $evalModel = new StudentEvaluation($this->db);
        $stage2Requirements = array_filter(
            $studentModel->stageRequirements($studentId, 2),
            static fn (array $row): bool => ($row['owner'] ?? 'student') === 'student'
                && ($row['kind'] ?? 'upload') !== 'evaluation'
        );
        $stage3Requirements = student_stage3_upload_rows($studentId);
        $enrollment = (new Enrollment($this->db))->detailsByStudent($studentId);
        $approvedHours = (new Report($this->db))->totalHours($studentId, true);
        $data = [
            'title' => 'Final Requirements - ' . ($student['name'] ?? 'Student'),
            'student' => $student,
            'studentEvaluation' => $evalModel->getByStudent($studentId),
            'stage2Requirements' => $stage2Requirements,
            'stage3Requirements' => $stage3Requirements,
            'stage3DocProgress' => student_stage3_upload_progress($studentId),
            'evaluationSections' => FinalRequirement::EVALUATION_SECTIONS,
            'stage3FilesUnlocked' => $studentModel->canAccessStage($studentId, 3),
            'studentEvaluationsUnlocked' => enrollment_allows_final_requirements($enrollment, $approvedHours),
            'studentEvaluationsLockMessage' => enrollment_final_requirements_lock_message($enrollment, $approvedHours),
        ];

        $doc = (string)($_GET['doc'] ?? '');
        $eval = (string)($_GET['eval'] ?? '');
        $legacyAliases = student_stage3_legacy_doc_aliases();
        $requirementKey = $legacyAliases[$doc] ?? $doc;

        $allReviewableRequirements = $stage2Requirements + $stage3Requirements;
        if ($requirementKey !== '' && isset($allReviewableRequirements[$requirementKey])) {
            $requirement = $allReviewableRequirements[$requirementKey];
            if (empty($requirement['file_path'])) {
                flash('error', 'This document has not been uploaded yet.');
                redirect('index.php?r=coordinator_student_final&student_id=' . $studentId);
            }
            $data['title'] = ($requirement['requirement_name'] ?? 'Document') . ' - ' . ($student['name'] ?? 'Student');
            $data['requirement'] = $requirement;
            $data['requirementKey'] = $requirementKey;
            if (requirement_is_form_path((string)$requirement['file_path'])) {
                $data['finalRequirement'] = (new FinalRequirement($this->db))->getByStudent($studentId);
            }
            $this->renderAppPage('coordinator/final/uploaded_document', $data);
            return;
        }
        
        // Legacy form section keys now open the synced student_requirements review page.
        if (array_key_exists($doc, FinalRequirement::SECTIONS)) {
            $mappedKey = student_stage3_legacy_doc_aliases()[$doc] ?? '';
            if ($mappedKey !== '' && isset($allReviewableRequirements[$mappedKey]) && !empty($allReviewableRequirements[$mappedKey]['file_path'])) {
                redirect('index.php?r=coordinator_student_final&student_id=' . $studentId . '&doc=' . rawurlencode($mappedKey));
            }
            flash('info', 'This document has not been submitted yet.');
            redirect('index.php?r=coordinator_student_final&student_id=' . $studentId);
        }
        
        // Handle evaluation sections (coordinator-only access)
        if ($eval === 'coordinator') {
            $status = StudentEvaluation::statusFor($data['studentEvaluation'], 'coordinator');
            if ($status !== 'submitted') {
                flash('error', 'This evaluation has not been submitted yet.');
                redirect('index.php?r=coordinator_student_final&student_id=' . $studentId);
            }
            $data['title'] = 'OJT Coordinator Evaluation - ' . ($student['name'] ?? 'Student');
            $data['evalType'] = 'coordinator';
            $this->renderAppPage('coordinator/evaluations/coordinator', $data);
            return;
        }

        if ($eval === 'industry_partner') {
            $status = StudentEvaluation::statusFor($data['studentEvaluation'], 'industry_partner');
            if ($status !== 'submitted') {
                flash('error', 'This evaluation has not been submitted yet.');
                redirect('index.php?r=coordinator_student_final&student_id=' . $studentId);
            }
            $data['title'] = 'Host Training Establishment Evaluation - ' . ($student['name'] ?? 'Student');
            $data['evalType'] = 'industry_partner';
            $this->renderAppPage('coordinator/evaluations/industry_partner', $data);
            return;
        }

        $this->renderAppPage('coordinator/student_final_requirements', $data);
    }

    public function moaMouLibrary(): void
    {
        require_role('coordinator');

        $companies = (new Company($this->db))->withMoaForCoordinator((int)current_user()['id']);

        $this->renderAppPage('coordinator/moa_mou', [
            'title' => 'Host Training Establishment MOA/MOU',
            'companies' => $companies,
        ]);
    }

    public function viewPartnerDocument(): void
    {
        require_role('coordinator');

        $companyId = (int)($_GET['company_id'] ?? 0);
        $company = (new Company($this->db))->find($companyId);

        if (!$company || empty($company['moa_mou_file'])) {
            render_missing_upload_page(
                'MOA/MOU not on file',
                'This Host Training Establishment has no MOA/MOU document record yet.',
                route_url('coordinator.moa_mou')
            );
        }
        if (!(new Company($this->db))->coordinatorCanAccessMoa((int)current_user()['id'], $companyId)) {
            http_response_code(403);
            exit('You do not have access to this MOA/MOU file.');
        }

        $relativePath = ltrim((string)$company['moa_mou_file'], '/\\');
        $absolutePath = upload_absolute_path($relativePath);

        if ($absolutePath === null) {
            render_missing_upload_page(
                'MOA/MOU file missing on server',
                'A document record exists for "' . (string)($company['name'] ?? 'this establishment') . '", but the file is missing from the server uploads folder. Ask the administrator to re-upload the MOA/MOU.',
                route_url('coordinator.moa_mou')
            );
        }

        $mime = mime_content_type($absolutePath) ?: 'application/octet-stream';
        $fileName = basename($absolutePath);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($absolutePath));
        header('Content-Disposition: inline; filename="' . rawurlencode($fileName) . '"');
        header('X-Content-Type-Options: nosniff');

        readfile($absolutePath);
        exit;
    }

    public function evaluations(): void
    {
        require_role('coordinator');
        $this->renderAppPage('coordinator/evaluations', [
            'title' => 'Evaluations',
            'evaluations' => (new Evaluation($this->db))->byCoordinator(current_user()['id']),
        ]);
    }

    public function enrollStudent(): void
    {
        require_role('coordinator');
        $p = $this->post();
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        try {
            $studentId = (int)$p['student_id'];
            $companyId = (int)$p['company_id'];
            $student = (new Student($this->db))->find($studentId);
            if (!$student || (int)$student['coordinator_id'] !== current_user()['id']) {
                throw new RuntimeException('Student does not belong to your coordination.');
            }
            if ((new Enrollment($this->db))->byStudent($studentId)) {
                throw new RuntimeException('This student is already enrolled. Please try again.');
            }
            if ((int)($student['is_active'] ?? 1) === 0) {
                throw new RuntimeException('This student account is deactivated. Ask the admin to reactivate the account before enrollment.');
            }
            $program = !empty($student['program_id']) ? (new Program($this->db))->find((int)$student['program_id']) : null;
            if (!$program) {
                throw new RuntimeException('Student has no valid program/course assigned.');
            }
            $company = (new Company($this->db))->find($companyId);
            if (!$company) {
                throw new RuntimeException('Selected Host Training Establishment was not found.');
            }
            if (!(new Company($this->db))->acceptsProgram($companyId, (int)$program['id'])) {
                throw new RuntimeException('Selected Host Training Establishment does not accept this student\'s program/course.');
            }
            $requiredHours = (int)$program['required_hours'];
            $academicTerm = trim($p['academic_term'] ?? '');
            $termStartDate = trim($p['term_start_date'] ?? '');
            $termEndDate = trim($p['term_end_date'] ?? '');
            $termRow = $academicTerm !== '' ? (new Term($this->db))->findByLabel($academicTerm) : null;
            if (!$termRow) {
                throw new RuntimeException('Please select a valid academic term.');
            }
            if (empty($termRow['term_start_date']) || empty($termRow['term_end_date'])) {
                throw new RuntimeException('The selected academic term is missing start/end dates. Ask the admin to complete it in Academic Term.');
            }
            if ($termStartDate === '') {
                $termStartDate = (string)$termRow['term_start_date'];
            }
            if ($termEndDate === '') {
                $termEndDate = (string)$termRow['term_end_date'];
            }
            (new Enrollment($this->db))->create($studentId, $companyId, null, null, $requiredHours, $academicTerm, $termStartDate, $termEndDate);
            $userId = (int)$student['user_id'];
            $userModel = new User($this->db);
            $isSelfRegistered = (new StudentRegistrationRequest($this->db))->isSelfRegisteredUser($userId);
            $userRow = $userModel->find($userId);
            $userModel->setActive($userId, 1);

            $passwordAlreadyChanged = (int)($userRow['password_changed'] ?? 1) === 1;
            $accountAlreadyActive = (int)($userRow['is_active'] ?? 0) === 1;
            $tempPassword = null;
            $usesExistingPassword = $isSelfRegistered || $passwordAlreadyChanged || $accountAlreadyActive;
            if (!$usesExistingPassword) {
                $tempPassword = random_password();
                $userModel->updatePassword($userId, $tempPassword, 0);
            } elseif ($isSelfRegistered && (int)($userRow['password_changed'] ?? 1) === 0) {
                $stmt = $this->db->prepare('UPDATE users SET password_changed = 1 WHERE id = ?');
                $stmt->execute([$userId]);
            }

            (new Student($this->db))->reconcilePredeploymentAfterRequirementDefChange($studentId);

            $email = new Email($this->db);
            $emailSent = $email->send($student['email'], 'You are now enrolled in OJT - AMA Computer College', 'student_enrollment', 'student_enrollment', [
                'student' => $student,
                'company' => $company,
                'academicTerm' => $academicTerm,
                'termStartDate' => $termStartDate,
                'termEndDate' => $termEndDate,
                'requiredHours' => $requiredHours,
                'password' => $tempPassword,
                'usesExistingPassword' => $usesExistingPassword,
                'coordinator' => current_user(),
                'loginUrl' => absolute_route_url('student.login'),
            ]);
            (new Notification($this->db))->create((int)$student['user_id'], 'OJT enrollment created', 'You have been enrolled for OJT deployment at ' . ($company['name'] ?? 'your Host Training Establishment') . '.', route_url('student.documents', ['stage' => 1]));
            $successMessage = $emailSent
                ? 'Student enrolled with the Host Training Establishment. The endorsement letter will be generated automatically when you forward approved 1st to Comply documents.'
                : 'Student enrolled, but the enrollment email failed to send. Check Email Logs. Forward approved documents after the Host Training Establishment email is working.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'ok' => true,
                    'message' => $successMessage,
                    'redirect' => route_url('coordinator.manage'),
                ]);
                exit;
            }
            flash($emailSent ? 'success' : 'error', $successMessage);
        } catch (Throwable $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
                exit;
            }
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=coordinator_manage');
    }

    public function reviewRequirement(): void
    {
        require_role('coordinator');
        $p = $this->post();
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        try {
            $studentId = (int)$p['student_id'];
            $studentModel = new Student($this->db);
            $student = $studentModel->find($studentId);
            if (!$student || (int)$student['coordinator_id'] !== (int)current_user()['id']) {
                throw new RuntimeException('Student does not belong to your coordination.');
            }
            $status = trim($p['status'] ?? '');
            $requirementKey = trim($p['requirement_key'] ?? '');
            $notes = trim($p['notes'] ?? '');
            if ($status === 'rejected' && $notes === '') {
                throw new RuntimeException('Please provide a reason for rejection.');
            }
            $studentModel->reviewRequirement($studentId, $requirementKey, $status, $notes);
            $enrollmentModel = new Enrollment($this->db);
            $reviewedStage = $studentModel->requirementStage($requirementKey);
            // Raw DB status for write decisions; detailsByStudent overlays effective status.
            $rawEnrollment = $enrollmentModel->byStudent($studentId);
            $newPredeploymentStatus = $studentModel->normalizePredeploymentStatus(
                is_array($rawEnrollment) ? ($rawEnrollment['predeployment_status'] ?? 'not_submitted') : 'not_submitted'
            );
            if ($reviewedStage !== 1) {
                // Stage 2/3 reviews do NOT touch the pre-deployment pipeline status.
                $stageLabel = Student::STAGE_LABELS[$reviewedStage] ?? 'Document';
                $noticeMessage = $status === 'rejected'
                    ? 'A ' . $stageLabel . ' document was rejected. Please replace the rejected file.'
                    : 'A ' . $stageLabel . ' document was approved by your coordinator.';
                (new Notification($this->db))->create((int)$student['user_id'], 'Document review update', $noticeMessage, route_url('student.documents', ['stage' => $reviewedStage]));
            } elseif ($reviewedStage === 1) {
                $currentPredeployment = $newPredeploymentStatus;
                $isLatePipelineReview = $rawEnrollment && $studentModel->isPredeploymentPipelineAdvanced($currentPredeployment);
                $newPredeploymentStatus = $studentModel->predeploymentStatusAfterStage1Review($studentId, $status);
                if ($isLatePipelineReview) {
                    $newPredeploymentStatus = $currentPredeployment;
                } elseif ($rawEnrollment && $newPredeploymentStatus !== $currentPredeployment) {
                    $enrollmentModel->setPredeploymentStatus($studentId, $newPredeploymentStatus);
                }
                if ($status === 'rejected') {
                    $noticeMessage = $isLatePipelineReview
                        ? 'A pre-deployment document was rejected. Please upload a corrected file in 1st to Comply.'
                        : 'One of your pre-deployment requirements was rejected. Only the rejected file needs to be corrected and re-uploaded.';
                    (new Notification($this->db))->create((int)$student['user_id'], 'Requirement needs revision', $noticeMessage, route_url('student.documents', ['stage' => 1]));
                } elseif ($studentModel->hasApprovedRequirements($studentId)) {
                    if ($isLatePipelineReview) {
                        (new Notification($this->db))->create((int)$student['user_id'], 'Document approved', 'Your uploaded pre-deployment document was approved by your coordinator.', route_url('student.documents', ['stage' => 1]));
                    } elseif ($rawEnrollment) {
                        (new Notification($this->db))->create((int)$student['user_id'], 'Requirements approved', 'All of your pre-deployment requirements have been approved by your coordinator.', route_url('student.dashboard'));
                    } else {
                        (new Notification($this->db))->create((int)$student['user_id'], 'Requirements approved', 'All of your 1st to Comply documents are approved. Your coordinator will assign a company and generate your endorsement letter when they forward your documents.', route_url('student.documents', ['stage' => 2]));
                    }
                }
            }
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'ok' => true,
                    'requirement_key' => $requirementKey,
                    'requirement_status' => $status,
                    'predeployment_status' => $newPredeploymentStatus,
                    'message' => 'Requirement review saved.',
                ]);
                exit;
            }
            flash('success', 'Requirement review saved.');
            if ($reviewedStage >= 2) {
                $redirect = 'index.php?r=coordinator_student_final&student_id=' . $studentId;
                if ($requirementKey !== '') {
                    $redirect .= '&doc=' . rawurlencode($requirementKey);
                }
                redirect($redirect);
            }
        } catch (Throwable $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
                exit;
            }
            flash('error', $e->getMessage());
            $fallbackStudentId = (int)($p['student_id'] ?? 0);
            $fallbackKey = trim((string)($p['requirement_key'] ?? ''));
            if ($fallbackStudentId > 0 && $fallbackKey !== '' && (new Student($this->db))->requirementStage($fallbackKey) >= 2) {
                redirect('index.php?r=coordinator_student_final&student_id=' . $fallbackStudentId . '&doc=' . rawurlencode($fallbackKey));
            }
        }
        redirect('index.php?r=coordinator_students');
    }

    public function forwardDeployment(): void
    {
        require_role('coordinator');
        $p = $this->post();
        try {
            $enrollment = (new Enrollment($this->db))->find((int)$p['enrollment_id']);
            if (!$enrollment) {
                throw new RuntimeException('Enrollment not found.');
            }
            $student = (new Student($this->db))->find((int)$enrollment['student_id']);
            if (!$student || (int)$student['coordinator_id'] !== (int)current_user()['id']) {
                throw new RuntimeException('Student does not belong to your coordination.');
            }
            $studentModel = new Student($this->db);
            $enrollmentModel = new Enrollment($this->db);
            $studentModel->syncPredeploymentStatusIfComplete((int)$student['id']);
            $rawEnrollment = $enrollmentModel->byStudent((int)$student['id']);
            if (!$rawEnrollment) {
                throw new RuntimeException('Enrollment not found.');
            }
            $predeployment = $studentModel->normalizePredeploymentStatus($rawEnrollment['predeployment_status'] ?? 'not_submitted');
            if ($studentModel->isPredeploymentPipelineAdvanced($predeployment)) {
                flash('success', 'Deployment documents were already forwarded for this student.');
                redirect('index.php?r=coordinator_students');
            }
            if (!$studentModel->hasApprovedRequirements((int)$student['id'])) {
                throw new RuntimeException('Approve all 1st to Comply requirements before forwarding deployment documents.');
            }
            if ($predeployment !== 'approved') {
                // Persist approved state when all stage-1 files are already approved.
                $enrollmentModel->setPredeploymentStatus((int)$student['id'], 'approved');
                $predeployment = 'approved';
            }
            $enrollment = array_merge($enrollment, $rawEnrollment);
            $company = (new Company($this->db))->find((int)$enrollment['company_id']);
            if (!$company) {
                throw new RuntimeException('Host Training Establishment not found.');
            }
            $companyEmail = trim((string)($company['contact_email'] ?? ''));
            if ($companyEmail === '' || !filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Host Training Establishment email is missing or invalid. Update the partner profile before forwarding.');
            }

            /**
             * Generate Endorsement Letter PDF dynamically (in-memory, not saved to disk)
             */
            $endorsementLetter = new EndorsementLetter($this->db);
            $pdfBuffer = $endorsementLetter->generatePdfBuffer((int)$student['id'], (int)$enrollment['id']);

            $attachments = [];
            foreach ($studentModel->requirements((int)$student['id']) as $req) {
                if (($req['status'] ?? '') !== 'approved' || empty($req['file_path'])) {
                    continue;
                }
                if (requirement_is_form_path((string)$req['file_path'])) {
                    continue;
                }
                $attachments[] = ['path' => $req['file_path']];
            }
            $attachments[] = [
                'string' => $pdfBuffer,
                'name' => 'Endorsement_Letter.pdf',
                'type' => 'application/pdf',
            ];

            $emailData = [
                'student' => $student,
                'company' => $company,
                'academicTerm' => $enrollment['academic_term'] ?? '',
                'termStartDate' => $enrollment['term_start_date'] ?? '',
                'termEndDate' => $enrollment['term_end_date'] ?? '',
                'requiredHours' => (int)$enrollment['required_hours'],
                'coordinator' => current_user(),
            ];

            // Email first; only mark forwarded after both sends succeed.
            $email = new Email($this->db);
            $companySent = $email->send($companyEmail, 'Student Deployment Documents Forwarded', 'deployment_forwarded', 'company_deployment', $emailData, $attachments);
            $studentSent = $email->send($student['email'], 'Your OJT Documents Have Been Forwarded', 'student_deployment_forwarded', 'student_deployment_forwarded', $emailData);
            if (!$companySent || !$studentSent) {
                $failed = [];
                if (!$companySent) {
                    $failed[] = 'Host Training Establishment';
                }
                if (!$studentSent) {
                    $failed[] = 'student';
                }
                throw new RuntimeException(
                    'Documents were not forwarded because email failed for: '
                    . implode(' and ', $failed)
                    . '. Check Email Logs and try again.'
                );
            }

            $stmt = $this->db->prepare(
                'UPDATE ojt_enrollments
                 SET predeployment_status = "forwarded", endorsement_file = ?, forwarded_at = NOW()
                 WHERE id = ?
                   AND predeployment_status NOT IN ("forwarded", "accepted", "orientation_scheduled", "orientation_completed")'
            );
            $stmt->execute(['(generated-pdf)', (int)$enrollment['id']]);
            if ($stmt->rowCount() === 0) {
                $rawAfter = $enrollmentModel->byStudent((int)$student['id']);
                $afterStatus = $studentModel->normalizePredeploymentStatus($rawAfter['predeployment_status'] ?? 'not_submitted');
                if ($rawAfter && $studentModel->isPredeploymentPipelineAdvanced($afterStatus)) {
                    flash('success', 'Deployment documents were already forwarded for this student.');
                    redirect('index.php?r=coordinator_students');
                }
                throw new RuntimeException('Deployment status changed before forwarding completed. Refresh and try again.');
            }

            (new Notification($this->db))->create((int)$company['user_id'], 'Student deployment forwarded', $student['name'] . ' has been forwarded to your Host Training Establishment Portal for review.', route_url('partner.portal', ['enrollment' => (int)$enrollment['id']]));
            (new Notification($this->db))->create((int)$student['user_id'], 'Documents forwarded to Host Training Establishment', 'Your approved pre-deployment documents and endorsement letter were sent to ' . ($company['name'] ?? 'your Host Training Establishment') . '. They will review and schedule your orientation.', route_url('student.documents', ['stage' => 2]));

            flash('success', 'Documents approved and Endorsement Letter generated and forwarded to the Host Training Establishment.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=coordinator_students');
    }

    /**
     * Preview the auto-generated endorsement letter before forwarding
     * 
     * This allows coordinators to review the PDF before clicking "Approve & Forward"
     */
    public function previewEndorsementLetter(): void
    {
        require_role('coordinator');
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

        // Verify the student belongs to this coordinator
        $student = (new Student($this->db))->find((int)$enrollment['student_id']);
        if (!$student || (int)$student['coordinator_id'] !== current_user()['id']) {
            http_response_code(403);
            exit('You do not have access to this enrollment.');
        }

        try {
            // Generate the PDF on-demand
            $endorsementLetter = new EndorsementLetter($this->db);
            $pdfContent = $endorsementLetter->generatePdfBuffer((int)$enrollment['student_id'], (int)$enrollment['id']);

            // Stream the PDF to the browser
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Endorsement_Letter_Preview.pdf"');
            header('Content-Length: ' . strlen($pdfContent));
            header('Cache-Control: public, max-age=3600');
            echo $pdfContent;
            exit;

        } catch (Throwable $e) {
            error_log('Endorsement letter preview failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Unable to generate the endorsement letter right now. Please try again later.');
        }
    }

    public function updateStudentEmail(): void
    {
        require_role('coordinator');
        $p = $this->post();
        $userId = (int)($p['user_id'] ?? 0);
        $newEmail = strtolower(trim((string)($p['email'] ?? '')));
        try {
            if (!$userId || $newEmail === '') {
                throw new RuntimeException('Invalid request.');
            }
            // Verify this student belongs to this coordinator
            $stmt = $this->db->prepare(
                'SELECT u.name, u.email AS current_email
                 FROM students s
                 JOIN users u ON u.id = s.user_id
                 WHERE s.user_id = ? AND s.coordinator_id = ?
                 LIMIT 1'
            );
            $stmt->execute([$userId, current_user()['id']]);
            $studentUser = $stmt->fetch();
            if (!$studentUser) {
                throw new RuntimeException('You do not have permission to edit this student.');
            }
            $oldEmail = $studentUser['current_email'];
            if ($oldEmail === $newEmail) {
                throw new RuntimeException('The new email is the same as the current email.');
            }
            // Update the email in DB
            (new User($this->db))->updateEmail($userId, $newEmail);
            // Notify the student at their OLD email address
            (new Email($this->db))->send(
                $oldEmail,
                'Your AMA OJT Portal Email Has Been Updated',
                'email_changed',
                'email_changed',
                [
                    'studentName' => $studentUser['name'],
                    'newEmail'    => $newEmail,
                ]
            );
            flash('success', 'Email updated. A notification was sent to the student\'s previous email address.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=coordinator_students');
    }
}
