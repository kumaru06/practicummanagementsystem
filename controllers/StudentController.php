<?php
class StudentController extends BaseController
{
    public function changePasswordForm(): void
    {
        require_role(['student', 'coordinator', 'partner']);
        $this->render('student/change_password', [
            'title' => 'Change Temporary Password',
        ]);
    }

    public function changePassword(): void
    {
        require_role(['student', 'coordinator', 'partner']);
        $p = $this->post();
        $password = (string)($p['password'] ?? '');
        $confirm = (string)($p['confirm_password'] ?? '');
        if (strlen($password) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            redirect('index.php');
        }
        if ($password !== $confirm) {
            flash('error', 'Passwords do not match.');
            redirect('index.php');
        }
        (new User($this->db))->updatePassword((int)current_user()['id'], $password, 1);
        $_SESSION['user']['password_changed'] = 1;
        flash('success', 'Password changed successfully. You can now access your dashboard.');
        redirect('index.php?r=' . current_user()['role']);
    }

    public function dashboard(): void
    {
        $this->render('student/dashboard', $this->studentPageData('Student Dashboard'));
    }

    public function records(): void
    {
        $this->render('student/records', $this->studentPageData('Submit Record'));
    }

    public function timeline(): void
    {
        $this->render('student/timeline', $this->studentPageData('Activity Timeline'));
    }

    public function documents(): void
    {
        $this->render('student/documents', $this->studentPageData('Documents'));
    }

    public function settings(): void
    {
        $this->render('student/settings', $this->studentPageData('Settings'));
    }

    public function profileForm(): void
    {
        require_role('student');
        $student = (new Student($this->db))->findByUser(current_user()['id']);
        $this->render('student/profile', [
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
                $photo = upload_document($_FILES['photo_file'], 'profiles', false);
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
        if (!$enrollments->allowsReports($enrollment)) {
            flash('error', $enrollments->reportLockMessage($enrollment));
            redirect('index.php?r=student_records');
        }
        $officialStart = $enrollment['official_start_date'] ?? $enrollment['start_date'] ?? null;
        if ($officialStart && strtotime((string)$p['work_date']) < strtotime((string)$officialStart)) {
            flash('error', 'DTR date cannot be earlier than your official OJT start date.');
            redirect('index.php?r=student_records');
        }
        try {
            (new Report($this->db))->addDtr((int)$student['id'], $p['work_date'], $p['time_in'], $p['time_out'], trim($p['tasks_done']));
            $enrollments->syncCompletion((int)$student['id']);
            if (!empty($student['coordinator_id'])) {
                (new Notification($this->db))->create((int)$student['coordinator_id'], 'DTR submitted', $student['name'] . ' submitted a daily time record for ' . date('M d, Y', strtotime((string)$p['work_date'])) . '.', route_url('coordinator.students'));
            }
            flash('success', 'Daily time record submitted.');
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
        if (!$enrollments->allowsReports($enrollment)) {
            flash('error', $enrollments->reportLockMessage($enrollment));
            redirect('index.php?r=student_records');
        }
        try {
            $path = $this->uploadReport($_FILES['report_file'] ?? []);
            (new Report($this->db))->addWeekly((int)$student['id'], (int)$p['week_no'], trim($p['report_text'] ?? ''), $path);
            if (!empty($student['coordinator_id'])) {
                (new Notification($this->db))->create((int)$student['coordinator_id'], 'Weekly report submitted', $student['name'] . ' submitted weekly report #' . (int)$p['week_no'] . '.', route_url('coordinator.students'));
            }
            flash('success', 'Weekly report submitted.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=student_records');
    }

    private function uploadReport(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Report file is required.');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Report file must not exceed 5MB.');
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
        return [
            'title' => $title,
            'student' => $student,
            'enrollment' => $enrollment,
            'canSubmitReports' => $enrollmentModel->allowsReports($enrollment),
            'reportLockMessage' => $enrollmentModel->reportLockMessage($enrollment),
            'dtrs' => $student ? $reports->dtrByStudent((int)$student['id']) : [],
            'weeklyReports' => $student ? $reports->weeklyByStudent((int)$student['id']) : [],
            'hours' => $student ? $reports->totalHours((int)$student['id']) : 0,
            'requirements' => $requirements,
        ];
    }
}
