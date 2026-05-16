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
            'title' => 'Partner MOA/MOU',
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
            (new Email($this->db))->send(trim($p['email']), 'Your AMA Practicum Student Account', 'account_credentials', 'account_credentials', [
                'name'      => $fullName,
                'email'     => trim($p['email']),
                'password'  => $password,
                'roleLabel' => 'Student',
            ]);
            flash('success', 'Student account created and login credentials have been sent to ' . trim($p['email']) . '.');
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
            $program = !empty($student['program_id']) ? (new Program($this->db))->find((int)$student['program_id']) : null;
            if (!$program) {
                throw new RuntimeException('Student has no valid program/course assigned.');
            }
            if (!(new Company($this->db))->acceptsProgram($companyId, (int)$program['id'])) {
                throw new RuntimeException('Selected partner company does not accept the student\'s program/course.');
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
            ]);
            flash('success', 'Student enrolled and credentials email was processed. Partner deployment email will be sent after approved documents are forwarded.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=coordinator');
    }

    public function reviewRequirement(): void
    {
        require_role('coordinator');
        $p = $this->post();
        try {
            $studentId = (int)$p['student_id'];
            $studentModel = new Student($this->db);
            $student = $studentModel->find($studentId);
            if (!$student || (int)$student['coordinator_id'] !== (int)current_user()['id']) {
                throw new RuntimeException('Student does not belong to your coordination.');
            }
            $status = trim($p['status'] ?? '');
            $studentModel->reviewRequirement($studentId, trim($p['requirement_key'] ?? ''), $status, trim($p['notes'] ?? ''));
            $enrollmentModel = new Enrollment($this->db);
            if ($status === 'rejected') {
                $enrollmentModel->setPredeploymentStatus($studentId, 'needs_revision');
                (new Notification($this->db))->create((int)$student['user_id'], 'Requirement needs revision', 'One of your pre-deployment requirements was rejected. Only the rejected file needs to be corrected and re-uploaded.', route_url('student.dashboard'));
            } elseif ($studentModel->hasApprovedRequirements($studentId)) {
                $enrollmentModel->setPredeploymentStatus($studentId, 'approved');
                (new Notification($this->db))->create((int)$student['user_id'], 'Requirements approved', 'All of your pre-deployment requirements have been approved by your coordinator.', route_url('student.dashboard'));
            } else {
                $enrollmentModel->setPredeploymentStatus($studentId, $studentModel->hasRejectedRequirements($studentId) ? 'needs_revision' : 'submitted');
            }
            flash('success', 'Requirement review saved.');
        } catch (Throwable $e) {
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
                throw new RuntimeException('Partner company not found.');
            }
            $endorsement = !empty($_FILES['endorsement_file']['name'])
                ? upload_document($_FILES['endorsement_file'] ?? [], 'endorsements')
                : generate_endorsement_letter($student, $company, current_user(), $enrollment);
            (new Enrollment($this->db))->approveAndForward((int)$enrollment['id'], $endorsement);
            if ($company) {
                $attachments = array_map(static fn ($path) => ['path' => $path], $studentModel->requirementFilePaths((int)$student['id']));
                $attachments[] = ['path' => $endorsement, 'name' => 'Endorsement Letter.' . pathinfo($endorsement, PATHINFO_EXTENSION)];
                (new Email($this->db))->send($company['contact_email'], 'Student Deployment Documents Forwarded', 'deployment_forwarded', 'company_deployment', [
                    'student' => $student,
                    'company' => $company,
                    'academicTerm' => $enrollment['academic_term'] ?? '',
                    'termStartDate' => $enrollment['term_start_date'] ?? '',
                    'termEndDate' => $enrollment['term_end_date'] ?? '',
                    'requiredHours' => (int)$enrollment['required_hours'],
                    'coordinator' => current_user(),
                ], $attachments);
                (new Notification($this->db))->create((int)$company['user_id'], 'Student deployment forwarded', $student['name'] . ' has been forwarded to your company for review.', route_url('partner.dashboard', ['enrollment' => (int)$enrollment['id']]));
            }
            flash('success', 'Documents approved and forwarded to partner company.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=coordinator_students');
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
