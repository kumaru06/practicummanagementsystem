<?php
class CoordinatorController extends BaseController
{
    public function dashboard(): void
    {
        require_role('coordinator');
        $students = new Student($this->db);
        $enroll = new Enrollment($this->db);
        $coordId = current_user()['id'];
        $this->render('coordinator/dashboard', [
            'title' => 'Coordinator Dashboard',
            'stats' => [
                'students'  => $students->countByCoordinator($coordId),
                'enrolled'  => $enroll->countByCoordinator($coordId, 'active'),
                'completed' => $enroll->countByCoordinator($coordId, 'completed'),
                'pending'   => $enroll->countByCoordinator($coordId, 'pending'),
            ],
            'charts' => [
                'statusDistribution' => $enroll->statusDistributionByCoordinator($coordId),
                'completionRates'    => $enroll->completionRatesByCourseByCoordinator($coordId),
                'monthlyTrends'      => $enroll->monthlyEnrollmentTrendsByCoordinator($coordId),
            ],
        ]);
    }

    public function manage(): void
    {
        require_role('coordinator');
        $coordId = current_user()['id'];
        $this->render('coordinator/manage', [
            'title' => 'Student Enrollment',
            'students'  => (new Student($this->db))->allByCoordinator($coordId),
            'companies' => (new Company($this->db))->all(),
            'programs'  => (new Program($this->db))->all(true),
            'terms'     => (new Term($this->db))->all(),
        ]);
    }

    public function myStudents(): void
    {
        require_role('coordinator');
        $students = (new Student($this->db))->allByCoordinator(current_user()['id']);
        $studentModel = new Student($this->db);
        $requirementsByStudent = [];
        foreach ($students as &$student) {
            $requirementsByStudent[(int)$student['id']] = $studentModel->requirements((int)$student['id']);
            $student['predeployment_status'] = $studentModel->effectivePredeploymentStatus((int)$student['id'], $student['predeployment_status'] ?? null, $requirementsByStudent[(int)$student['id']]);
        }
        unset($student);
        $this->render('coordinator/my_students', [
            'title' => 'My Students',
            'students' => $students,
            'requirementsByStudent' => $requirementsByStudent,
            'evaluations' => (new Evaluation($this->db))->byCoordinator(current_user()['id']),
        ]);
    }

    public function moaMouLibrary(): void
    {
        require_role('coordinator');

        $companies = array_values(array_filter(
            (new Company($this->db))->all(),
            static fn (array $company): bool => !empty($company['moa_mou_file'])
        ));

        $this->render('coordinator/moa_mou', [
            'title' => 'Industry Partner MOA/MOU',
            'companies' => $companies,
        ]);
    }

    public function viewPartnerDocument(): void
    {
        require_role('coordinator');

        $companyId = (int)($_GET['company_id'] ?? 0);
        $company = (new Company($this->db))->find($companyId);

        if (!$company || empty($company['moa_mou_file'])) {
            http_response_code(404);
            exit('MOA/MOU file not found.');
        }

        $relativePath = ltrim((string)$company['moa_mou_file'], '/\\');
        $absolutePath = realpath(__DIR__ . '/../' . $relativePath);
        $uploadsRoot = realpath(__DIR__ . '/../uploads');

        if (!$absolutePath || !$uploadsRoot || !str_starts_with($absolutePath, $uploadsRoot) || !is_file($absolutePath)) {
            http_response_code(404);
            exit('MOA/MOU file not found.');
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
        $this->render('coordinator/evaluations', [
            'title' => 'Evaluations',
            'evaluations' => (new Evaluation($this->db))->byCoordinator(current_user()['id']),
        ]);
    }

    public function createStudent(): void
    {
        require_role('coordinator');
        $p = $this->post();
        try {
            $password = random_password();
            $corPath = upload_cor($_FILES['cor_file'] ?? []);
            $program = (new Program($this->db))->find((int)$p['program_id']);
            if (!$program) {
                throw new RuntimeException('Select a valid program/course.');
            }
            $firstName = trim((string)($p['first_name'] ?? ''));
            $lastName = trim((string)($p['last_name'] ?? ''));
            $fullName = trim($firstName . ' ' . $lastName);
            if ($fullName === '') {
                throw new RuntimeException('First name and last name are required.');
            }
            if (!in_array(trim((string)($p['year_level'] ?? '')), ['3rd Year', '4th Year'], true)) {
                throw new RuntimeException('Select a valid year level.');
            }
            $birthdate = trim((string)($p['birthdate'] ?? ''));
            if ($birthdate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
                throw new RuntimeException('Select a valid birthdate.');
            }
            $birthdateObj = new DateTime($birthdate);
            $age = (new DateTime())->diff($birthdateObj)->y;
            if ($age < 20) {
                throw new RuntimeException('Student must be at least 20 years old to be eligible for OJT.');
            }
            $userId = (new User($this->db))->create($fullName, trim($p['email']), $password, 'student', current_user()['id'], 0);
            (new Student($this->db))->create($userId, trim($p['student_no']), $program['name'], trim($p['year_level']), $corPath, current_user()['id'], (int)$program['id'], '', $birthdate);
            flash('success', 'Student profile created. Login credentials will be emailed when you enroll the student and click Enroll & Send Emails.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=coordinator_manage');
    }

    public function enrollStudent(): void
    {
        require_role('coordinator');
        $p = $this->post();
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
            $program = !empty($student['program_id']) ? (new Program($this->db))->find((int)$student['program_id']) : null;
            if (!$program) {
                throw new RuntimeException('Student has no valid program/course assigned.');
            }
            if (!(new Company($this->db))->acceptsProgram($companyId, (int)$program['id'])) {
                throw new RuntimeException('Selected Industry Partner does not accept the student\'s program/course.');
            }
            $requiredHours = (int)$program['required_hours'];
            (new Enrollment($this->db))->create($studentId, $companyId, null, null, $requiredHours, trim($p['academic_term'] ?? ''), $p['term_start_date'] ?? '', $p['term_end_date'] ?? '');
            $company = (new Company($this->db))->find($companyId);
            $tempPassword = random_password();
            (new User($this->db))->updatePassword((int)$student['user_id'], $tempPassword, 0);
            $email = new Email($this->db);
            $email->send($student['email'], 'You are now enrolled in OJT – AMA Computer College', 'student_enrollment', 'student_enrollment', [
                'student' => $student,
                'company' => $company,
                'academicTerm' => trim($p['academic_term'] ?? ''),
                'termStartDate' => $p['term_start_date'] ?? '',
                'termEndDate' => $p['term_end_date'] ?? '',
                'requiredHours' => $requiredHours,
                'password' => $tempPassword,
                'coordinator' => current_user(),
                'loginUrl' => absolute_route_url('student.login'),
            ]);
            (new Notification($this->db))->create((int)$student['user_id'], 'OJT enrollment created', 'You have been enrolled for OJT deployment at ' . ($company['name'] ?? 'your Industry Partner') . '.', route_url('student.documents'));
            flash('success', 'Student enrolled and credentials email was processed. Industry Partner deployment email will be sent after approved documents are forwarded.');
        } catch (Throwable $e) {
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
            $studentModel->reviewRequirement($studentId, $requirementKey, $status, trim($p['notes'] ?? ''));
            $enrollmentModel = new Enrollment($this->db);
            if ($status === 'rejected') {
                $newPredeploymentStatus = 'needs_revision';
                $enrollmentModel->setPredeploymentStatus($studentId, $newPredeploymentStatus);
                (new Notification($this->db))->create((int)$student['user_id'], 'Requirement needs revision', 'One of your pre-deployment requirements was rejected. Only the rejected file needs to be corrected and re-uploaded.', route_url('student.dashboard'));
            } elseif ($studentModel->hasApprovedRequirements($studentId)) {
                $newPredeploymentStatus = 'approved';
                $enrollmentModel->setPredeploymentStatus($studentId, $newPredeploymentStatus);
                (new Notification($this->db))->create((int)$student['user_id'], 'Requirements approved', 'All of your pre-deployment requirements have been approved by your coordinator.', route_url('student.dashboard'));
            } else {
                $newPredeploymentStatus = $studentModel->hasRejectedRequirements($studentId) ? 'needs_revision' : 'submitted';
                $enrollmentModel->setPredeploymentStatus($studentId, $newPredeploymentStatus);
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
        } catch (Throwable $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
                exit;
            }
            flash('error', $e->getMessage());
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
            if (!$studentModel->hasApprovedRequirements((int)$student['id'])) {
                throw new RuntimeException('Approve all five requirements before forwarding deployment documents.');
            }
            $company = (new Company($this->db))->find((int)$enrollment['company_id']);
            if (!$company) {
                throw new RuntimeException('Industry Partner not found.');
            }

            /**
             * Generate Endorsement Letter PDF dynamically (in-memory, not saved to disk)
             */
            $endorsementLetter = new EndorsementLetter($this->db);
            $pdfBuffer = $endorsementLetter->generatePdfBuffer((int)$student['id'], (int)$enrollment['id']);

            // Update enrollment status to 'forwarded' with a virtual endorsement reference
            $stmt = $this->db->prepare('UPDATE ojt_enrollments SET predeployment_status = "forwarded", endorsement_file = ?, forwarded_at = NOW() WHERE id = ?');
            $stmt->execute(['(generated-pdf)', (int)$enrollment['id']]);

            if ($company) {
                // Fetch requirement files for attachment
                $attachments = array_map(static fn ($path) => ['path' => $path], $studentModel->requirementFilePaths((int)$student['id']));
                
                /**
                 * Add the generated PDF as a string attachment
                 * The Email class will use addStringAttachment for this
                 */
                $attachments[] = [
                    'string' => $pdfBuffer,
                    'name' => 'Endorsement_Letter.pdf',
                    'type' => 'application/pdf'
                ];

                (new Email($this->db))->send($company['contact_email'], 'Student Deployment Documents Forwarded', 'deployment_forwarded', 'company_deployment', [
                    'student' => $student,
                    'company' => $company,
                    'academicTerm' => $enrollment['academic_term'] ?? '',
                    'termStartDate' => $enrollment['term_start_date'] ?? '',
                    'termEndDate' => $enrollment['term_end_date'] ?? '',
                    'requiredHours' => (int)$enrollment['required_hours'],
                    'coordinator' => current_user(),
                ], $attachments);
                (new Notification($this->db))->create((int)$company['user_id'], 'Student deployment forwarded', $student['name'] . ' has been forwarded to your Industry Partner Portal for review.', route_url('partner.portal', ['enrollment' => (int)$enrollment['id']]));
            }
            flash('success', 'Documents approved and Endorsement Letter generated and forwarded to the Industry Partner.');
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
            http_response_code(500);
            exit('Error generating endorsement letter: ' . $e->getMessage());
        }
    }

    public function resetStudentPassword(): void
    {
        require_role('coordinator');
        $p = $this->post();
        $student = (new Student($this->db))->find((int)$p['student_id']);
        if (!$student || (int)$student['coordinator_id'] !== current_user()['id']) {
            flash('error', 'Invalid student.');
            redirect('index.php?r=coordinator_students');
        }
        $password = random_password();
        (new User($this->db))->updatePassword((int)$student['user_id'], $password, 0);
        (new Email($this->db))->send($student['email'], 'Your AMA OJT password has been reset', 'password_reset', 'password_reset', [
            'student' => $student,
            'password' => $password,
            'coordinator' => current_user(),
            'loginUrl' => absolute_route_url('student.login'),
        ]);
        flash('success', 'Student password reset and emailed.');
        redirect('index.php?r=coordinator_students');
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
