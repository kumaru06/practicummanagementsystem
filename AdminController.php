<?php
class AdminController extends BaseController
{
    public function dashboard(): void
    {
        require_role('admin');
        $users = new User($this->db);
        $company = new Company($this->db);
        $enroll = new Enrollment($this->db);
        $this->render('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => [
                'coordinators' => $users->countRole('coordinator'),
                'companies' => $company->count(),
                'students' => $users->countRole('student'),
                'active' => $enroll->activeCount(),
            ],
            'companies' => $company->all(),
            'charts' => [
                'statusDistribution' => $enroll->statusDistribution(),
                'completionRates' => $enroll->completionRatesByCourse(),
                'monthlyTrends' => $enroll->monthlyEnrollmentTrends(),
                'courseStudents' => $enroll->studentProgressByCourse(),
            ],
        ]);
    }

    public function manageCoordinators(): void
    {
        require_role('admin');
        $this->render('admin/coordinators', [
            'title' => 'Manage Coordinators',
            'coordinators' => (new User($this->db))->byRole('coordinator'),
        ]);
    }

    public function managePartners(): void
    {
        require_role('admin');
        $this->render('admin/partners', [
            'title' => 'Manage Companies',
            'partners' => (new Company($this->db))->all(),
            'programs' => (new Program($this->db))->all(true),
        ]);
    }

    public function viewPartnerDocument(): void
    {
        require_role('admin');

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

    public function managePrograms(): void
    {
        require_role('admin');
        $this->render('admin/programs', [
            'title' => 'Programs / Courses',
            'programs' => (new Program($this->db))->all(),
            'terms' => (new Term($this->db))->all(),
        ]);
    }

    private function validateAcademicTerm(string $term): string
    {
        $term = trim($term);
        // Normalize en-dash (–) and em-dash (—) to regular hyphen, and non-breaking spaces to space
        $term = str_replace(["\u{2013}", "\u{2014}", "\u{00A0}"], ['-', '-', ' '], $term);
        $term = preg_replace('/\s+/', ' ', $term);
        if ($term === '') {
            throw new RuntimeException('Term is required.');
        }
        if (mb_strlen($term) > 120) {
            throw new RuntimeException('Term is too long. Keep it within 120 characters.');
        }
        if (!preg_match('/^\d{4}\s\((1st|2nd|3rd)\sTri\)\s-\sSY\s(\d{4})-(\d{4})$/', $term, $matches)) {
            throw new RuntimeException('Invalid term format. Use: 2523 (2nd Tri) - SY 2025-2026');
        }
        $startYear = (int)$matches[2];
        $endYear = (int)$matches[3];
        if ($endYear !== $startYear + 1) {
            throw new RuntimeException('School year must be consecutive (example: SY 2025-2026).');
        }
        return $term;
    }

    public function saveTerm(): void
    {
        require_role('admin');
        $p = $this->post();
        try {
            $termLabel = $this->validateAcademicTerm((string)($p['term_label'] ?? ''));
            $terms = new Term($this->db);
            $termId = (int)($p['term_id'] ?? 0);

            if ($termId > 0) {
                $terms->update($termId, $termLabel);
                flash('success', 'Term updated.');
            } else {
                $terms->create($termLabel);
                flash('success', 'Term added.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=admin_programs');
    }

    public function deleteTerm(): void
    {
        require_role('admin');
        $p = $this->post();
        try {
            (new Term($this->db))->delete((int)($p['term_id'] ?? 0));
            flash('success', 'Term deleted.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=admin_programs');
    }

    public function manageUsers(): void
    {
        require_role('admin');
        $this->render('admin/users', [
            'title' => 'Manage Student',
            'allUsers' => (new User($this->db))->allStudents(),
        ]);
    }

    public function evaluations(): void
    {
        require_role('admin');
        $this->render('admin/evaluations', [
            'title' => 'Evaluations',
            'evaluations' => (new Evaluation($this->db))->allWithDetails(),
        ]);
    }

    public function emailLogs(): void
    {
        require_role('admin');
        $filters = [
            'type' => trim($_GET['type'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to' => trim($_GET['date_to'] ?? ''),
        ];
        $this->render('admin/email_logs', [
            'title' => 'Email Logs',
            'logs' => (new Email($this->db))->filtered($filters),
            'filters' => $filters,
            'types' => (new Email($this->db))->types(),
        ]);
    }

    public function createCoordinator(): void
    {
        require_role('admin');
        $p = $this->post();
        try {
            $users = new User($this->db);
            $users->ensureCoordinatorIdNumberSupport();
            $users->ensureCoordinatorSignatureSupport();

            $idNumber = trim((string)($p['id_number'] ?? ''));
            if ($idNumber === '') {
                throw new RuntimeException('ID number is required.');
            }
            if (!ctype_digit($idNumber)) {
                throw new RuntimeException('ID number must contain digits only.');
            }

            $name = trim($p['name'] ?? '');
            if ($name === '') {
                throw new RuntimeException('Full name is required.');
            }
            if (preg_match('/[0-9]/', $name)) {
                throw new RuntimeException('Full name must contain letters only, no numbers.');
            }

            $email = strtolower(trim($p['email'] ?? ''));
            if ($email === '') {
                throw new RuntimeException('Email is required.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.[a-zA-Z]{2,}$/', explode('@', $email)[1] ?? '')) {
                throw new RuntimeException('Please enter a valid email address (e.g. name@example.com).');
            }

            $signaturePath = upload_signature($_FILES['signature_file'] ?? []);
            if ($signaturePath === null) {
                throw new RuntimeException('Signature image is required.');
            }

            $password = random_password();
            $this->db->beginTransaction();
            $duplicateIdStmt = $this->db->prepare('SELECT COUNT(*) FROM coordinators WHERE id_number = ?');
            $duplicateIdStmt->execute([$idNumber]);
            if ((int)$duplicateIdStmt->fetchColumn() > 0) {
                throw new RuntimeException('ID number already exists.');
            }

            $userId = $users->create($name, $email, $password, 'coordinator', current_user()['id'], 0);
            $stmt = $this->db->prepare('INSERT INTO coordinators (user_id, id_number, department, signature_file) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $idNumber, trim($p['department'] ?? 'OJT Department') ?: 'OJT Department', $signaturePath]);
            $this->db->commit();

            (new Email($this->db))->send($email, 'Your AMA Practicum Coordinator Account', 'account_credentials', 'account_credentials', [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'roleLabel' => 'OJT Coordinator',
                'loginUrl' => absolute_route_url('coordinator.login'),
            ]);
            flash('success', 'Coordinator account created and credentials email was processed.');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $msg = str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate entry')
                ? (str_contains(strtolower($e->getMessage()), 'id_number') ? 'ID number already exists.' : 'Email already exists.')
                : $e->getMessage();
            flash('error', $msg);
        }
        redirect('index.php?r=admin_coordinators');
    }

    public function updateCoordinator(): void
    {
        require_role('admin');
        $p = $this->post();
        try {
            $userId = (int)($p['user_id'] ?? 0);
            if ($userId <= 0) {
                throw new RuntimeException('Invalid coordinator.');
            }

            $users = new User($this->db);
            $users->ensureCoordinatorIdNumberSupport();
            $users->ensureCoordinatorSignatureSupport();

            $name = trim($p['name'] ?? '');
            if ($name === '') {
                throw new RuntimeException('Full name is required.');
            }
            if (preg_match('/[0-9]/', $name)) {
                throw new RuntimeException('Full name must contain letters only, no numbers.');
            }

            $email = strtolower(trim($p['email'] ?? ''));
            if ($email === '') {
                throw new RuntimeException('Email is required.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.[a-zA-Z]{2,}$/', explode('@', $email)[1] ?? '')) {
                throw new RuntimeException('Please enter a valid email address.');
            }

            $idNumber = trim((string)($p['id_number'] ?? ''));
            if ($idNumber !== '' && !ctype_digit($idNumber)) {
                throw new RuntimeException('ID number must contain digits only.');
            }

            $signaturePath = upload_signature($_FILES['signature_file'] ?? []);

            $this->db->beginTransaction();

            $stmt = $this->db->prepare('UPDATE users SET name = ?, email = ? WHERE id = ? AND role = ?');
            $stmt->execute([$name, $email, $userId, 'coordinator']);

            $updates = ['department = ?'];
            $params = [trim($p['department'] ?? 'OJT Department') ?: 'OJT Department'];

            if ($idNumber !== '') {
                $dupStmt = $this->db->prepare('SELECT COUNT(*) FROM coordinators WHERE id_number = ? AND user_id != ?');
                $dupStmt->execute([$idNumber, $userId]);
                if ((int)$dupStmt->fetchColumn() > 0) {
                    throw new RuntimeException('ID number already exists.');
                }
                $updates[] = 'id_number = ?';
                $params[] = $idNumber;
            }

            if ($signaturePath !== null) {
                $updates[] = 'signature_file = ?';
                $params[] = $signaturePath;
            }

            $params[] = $userId;
            $stmt = $this->db->prepare('UPDATE coordinators SET ' . implode(', ', $updates) . ' WHERE user_id = ?');
            $stmt->execute($params);

            $this->db->commit();
            flash('success', 'Coordinator account updated successfully.');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $msg = str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate entry')
                ? (str_contains(strtolower($e->getMessage()), 'id_number') ? 'ID number already exists.' : 'Email already exists.')
                : $e->getMessage();
            flash('error', $msg);
        }
        redirect('index.php?r=admin_coordinators');
    }

    public function createCompany(): void
    {
        require_role('admin');
        $p = $this->post();
        $moaMouFile = null;
        try {
            $password = random_password();
            $companyName = trim($p['company_name'] ?? '');
            $contactPerson = trim($p['contact_person'] ?? $p['name'] ?? '');
            $contactEmail = strtolower(trim($p['contact_email'] ?? ''));
            $address = trim($p['address'] ?? '');
            $programIds = array_values(array_unique(array_filter(array_map('intval', (array)($p['program_ids'] ?? [])))));
            $companies = new Company($this->db);
            $companies->ensureMoaMouSupport();

            if ($companyName === '' || $contactPerson === '' || $contactEmail === '' || $address === '' || trim((string)($p['contact_number'] ?? '')) === '') {
                throw new RuntimeException('Fill in all required Industry Partner details before creating the account.');
            }

            if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Enter a valid Industry Partner email address.');
            }

            $contactNumberDigits = preg_replace('/\D+/', '', (string)($p['contact_number'] ?? ''));
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
            if (!$programIds) {
                throw new RuntimeException('Select at least one accepted program/course.');
            }
            $moaMouFile = upload_document($_FILES['moa_mou_file'] ?? [], 'company_moa_mou', true);

            $this->db->beginTransaction();
            $userId = (new User($this->db))->create($companyName, $contactEmail, $password, 'partner', current_user()['id'], 0);
            $companies->create($userId, $companyName, $address, $contactPerson, $contactEmail, $contactNumber, $programIds, $moaMouFile);
            $this->db->commit();
            (new Email($this->db))->send($contactEmail, 'Your AMA Practicum Industry Partner Account', 'account_credentials', 'account_credentials', [
                'name' => $contactPerson,
                'email' => $contactEmail,
                'password' => $password,
                'roleLabel' => 'Industry Partner',
                'loginUrl' => absolute_route_url('partner.login'),
            ]);
            flash('success', 'Industry Partner account created and credentials email was processed.');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($moaMouFile && is_file(__DIR__ . '/../' . $moaMouFile)) {
                @unlink(__DIR__ . '/../' . $moaMouFile);
            }
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=admin_partners');
    }

    public function updateCompanyPrograms(): void
    {
        require_role('admin');
        $p = $this->post();
        try {
            $companyId = (int)($p['company_id'] ?? 0);
            $programIds = array_values(array_unique(array_filter(array_map('intval', (array)($p['program_ids'] ?? [])))));

            if ($companyId <= 0) {
                throw new RuntimeException('Invalid Industry Partner selected.');
            }
            if (!$programIds) {
                throw new RuntimeException('Select at least one accepted program/course.');
            }

            $companies = new Company($this->db);
            $company = $companies->find($companyId);
            if (!$company) {
                throw new RuntimeException('Industry Partner not found.');
            }

            $companies->syncPrograms($companyId, $programIds);
            flash('success', 'Accepted programs updated for ' . ($company['name'] ?? 'Industry Partner') . '.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=admin_partners');
    }

    public function saveProgram(): void
    {
        require_role('admin');
        $p = $this->post();
        try {
            $programs = new Program($this->db);
            $term = $this->validateAcademicTerm((string)($p['term'] ?? ''));
            (new Term($this->db))->createIfMissing($term);

            if (!empty($p['program_id'])) {
                $programs->update((int)$p['program_id'], trim($p['code']), trim($p['name']), $term, (int)$p['required_hours'], (int)($p['is_active'] ?? 1));
                flash('success', 'Program updated.');
            } else {
                $programs->create(trim($p['code']), trim($p['name']), $term, (int)$p['required_hours']);
                flash('success', 'Program created.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=admin_programs');
    }

    public function deleteProgram(): void
    {
        require_role('admin');
        $p = $this->post();
        try {
            (new Program($this->db))->delete((int)$p['program_id']);
            flash('success', 'Program deleted.');
        } catch (Throwable $e) {
            flash('error', 'Program is already in use. Deactivate it instead.');
        }
        redirect('index.php?r=admin_programs');
    }

    public function resendCompanyCredentials(): void
    {
        require_role('admin');
        $p = $this->post();
        try {
            $company = (new Company($this->db))->find((int)$p['company_id']);
            if (!$company) {
                throw new RuntimeException('Industry Partner not found.');
            }
            $password = random_password();
            (new User($this->db))->updatePassword((int)$company['user_id'], $password, 0);
            $sent = (new Email($this->db))->send($company['contact_email'], 'Your AMA Practicum Industry Partner Account', 'account_credentials', 'account_credentials', [
                'name' => $company['contact_person'],
                'email' => $company['contact_email'],
                'password' => $password,
                'roleLabel' => 'Industry Partner',
                'loginUrl' => absolute_route_url('partner.login'),
            ]);
            flash($sent ? 'success' : 'error', $sent ? 'Industry Partner credentials were resent to ' . $company['contact_email'] . '.' : 'Credentials were reset, but the email failed. Check Email Logs.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=admin_partners');
    }

    public function resetUserCredentials(): void
    {
        require_role('admin');
        $p = $this->post();
        $redirect = trim((string)($p['redirect'] ?? 'admin_users')) ?: 'admin_users';
        try {
            $target = (new User($this->db))->findWithDetails((int)$p['user_id']);
            if (!$target || ($target['role'] ?? '') === 'admin') {
                throw new RuntimeException('User account cannot be reset.');
            }
            $password = random_password();
            (new User($this->db))->updatePassword((int)$target['id'], $password, 0);
            $roleLabel = match ($target['role']) {
                'coordinator' => 'OJT Coordinator',
                'student' => 'Student',
                'partner' => 'Industry Partner',
                default => ucwords(str_replace('_', ' ', (string)$target['role'])),
            };
            $loginRoute = match ($target['role']) {
                'coordinator' => 'coordinator.login',
                'student' => 'student.login',
                'partner' => 'partner.login',
                default => 'login',
            };
            $sent = (new Email($this->db))->send($target['email'], 'Your AMA Practicum Account Credentials', 'account_credentials', 'account_credentials', [
                'name' => $target['name'],
                'email' => $target['email'],
                'usn' => ($target['role'] ?? '') === 'student' ? ($target['student_no'] ?? '') : '',
                'password' => $password,
                'roleLabel' => $roleLabel,
                'loginUrl' => absolute_route_url($loginRoute),
            ]);
            flash($sent ? 'success' : 'error', $sent ? 'Temporary credentials were sent to ' . $target['email'] . '.' : 'Password was reset, but the email failed. Check Email Logs.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=' . $redirect);
    }

    public function toggleUser(): void
    {
        require_role('admin');
        $p = $this->post();
        (new User($this->db))->setActive((int)$p['user_id'], (int)$p['active']);
        flash('success', 'User status updated.');
        $back = $p['redirect'] ?? 'admin_users';
        redirect('index.php?r=' . $back);
    }
}
