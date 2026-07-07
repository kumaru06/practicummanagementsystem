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

    private function validateTermDate(string $value, string $fieldLabel): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new RuntimeException($fieldLabel . ' is required.');
        }
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new RuntimeException('Invalid ' . strtolower($fieldLabel) . '. Use the date picker.');
        }
        return $value;
    }

    public function saveTerm(): void
    {
        require_role('admin');
        $p = $this->post();
        try {
            $termLabel = $this->validateAcademicTerm((string)($p['term_label'] ?? ''));
            $startDate = $this->validateTermDate((string)($p['term_start_date'] ?? ''), 'Term start date');
            $endDate = $this->validateTermDate((string)($p['term_end_date'] ?? ''), 'Term end date');
            if ($endDate < $startDate) {
                throw new RuntimeException('Term end date must be on or after the start date.');
            }

            $terms = new Term($this->db);
            $termId = (int)($p['term_id'] ?? 0);

            if ($termId > 0) {
                $terms->update($termId, $termLabel, $startDate, $endDate);
                flash('success', 'Term updated.');
            } else {
                $terms->create($termLabel, $startDate, $endDate);
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
            'students' => (new Student($this->db))->allForAdmin(),
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

    public function reports(): void
    {
        require_role('admin');
        $this->render('admin/reports', [
            'title' => 'Reports',
            'categories' => admin_report_categories(),
        ]);
    }

    public function report(): void
    {
        require_role('admin');
        $slug = trim((string)($_GET['slug'] ?? ''));
        $report = admin_report_by_slug($slug);
        if (!$report) {
            flash('error', 'Report not found.');
            redirect(route_url('admin.reports'));
        }
        $payload = (new AdminReport($this->db))->generate($slug);
        $this->render('admin/report_view', [
            'title' => $report['label'],
            'report' => $report,
            'description' => $payload['description'] ?? '',
            'columns' => $payload['columns'] ?? [],
            'rows' => $payload['rows'] ?? [],
            'ready' => (bool)($payload['ready'] ?? false),
        ]);
    }

    public function ojtPlacement(): void
    {
        require_role('admin');
        $this->render('admin/ojt_placement', [
            'title' => 'OJT Placement',
            'placements' => (new Enrollment($this->db))->allPlacements(),
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

            $firstName = trim($p['first_name'] ?? '');
            $lastName = trim($p['last_name'] ?? '');
            if ($firstName === '' || $lastName === '') {
                throw new RuntimeException('First name and last name are required.');
            }
            if (preg_match('/[0-9]/', $firstName . $lastName)) {
                throw new RuntimeException('Name must contain letters only, no numbers.');
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

            $userId = $users->create($firstName, $lastName, $email, $password, 'coordinator', current_user()['id'], 0);
            $stmt = $this->db->prepare('INSERT INTO coordinators (user_id, id_number, department, signature_file) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $idNumber, trim($p['department'] ?? 'OJT Department') ?: 'OJT Department', $signaturePath]);
            $this->db->commit();

            (new Email($this->db))->send($email, 'Your AMA Practicum Coordinator Account', 'account_credentials', 'account_credentials', [
                'name' => full_name_from_parts($firstName, $lastName),
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

    public function checkCoordinatorIdNumber(): void
    {
        require_role('admin');
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $idNumber = trim((string)($_GET['id_number'] ?? ''));
        if ($idNumber === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'ID number is required.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!ctype_digit($idNumber)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'ID number must contain digits only.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        (new User($this->db))->ensureCoordinatorIdNumberSupport();
        $excludeUserId = (int)($_GET['exclude_user_id'] ?? 0);
        $sql = 'SELECT COUNT(*) FROM coordinators c JOIN users u ON u.id = c.user_id WHERE c.id_number = ?';
        $params = [$idNumber];
        if ($excludeUserId > 0) {
            $sql .= ' AND u.id <> ?';
            $params[] = $excludeUserId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $exists = (int)$stmt->fetchColumn() > 0;

        echo json_encode([
            'ok' => true,
            'exists' => $exists,
            'available' => !$exists,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function checkCoordinatorEmail(): void
    {
        require_role('admin');
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $email = strtolower(trim((string)($_GET['email'] ?? '')));
        if ($email === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Email is required.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Enter a valid email address.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $excludeUserId = (int)($_GET['exclude_user_id'] ?? 0);
        $existing = (new User($this->db))->findByEmail($email);
        $exists = $existing !== null && ($excludeUserId <= 0 || (int)$existing['id'] !== $excludeUserId);

        echo json_encode([
            'ok' => true,
            'exists' => $exists,
            'available' => !$exists,
        ], JSON_UNESCAPED_UNICODE);
        exit;
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

            $firstName = trim($p['first_name'] ?? '');
            $lastName = trim($p['last_name'] ?? '');
            if ($firstName === '' || $lastName === '') {
                throw new RuntimeException('First name and last name are required.');
            }
            if (preg_match('/[0-9]/', $firstName . $lastName)) {
                throw new RuntimeException('Name must contain letters only, no numbers.');
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

            $users->updatePersonName($userId, $firstName, $lastName);
            $users->updateEmail($userId, $email);

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
            $partnerNameParts = split_person_name($companyName);
            $userId = (new User($this->db))->create(
                $partnerNameParts['first_name'],
                $partnerNameParts['last_name'],
                $contactEmail,
                $password,
                'partner',
                current_user()['id'],
                0
            );
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

    public function checkPartnerEmail(): void
    {
        require_role('admin');
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $email = strtolower(trim((string)($_GET['email'] ?? '')));
        if ($email === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Email is required.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Enter a valid email address.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $excludeUserId = (int)($_GET['exclude_user_id'] ?? 0);
        $existing = (new User($this->db))->findByEmail($email);
        $exists = $existing !== null && ($excludeUserId <= 0 || (int)$existing['id'] !== $excludeUserId);

        echo json_encode([
            'ok' => true,
            'exists' => $exists,
            'available' => !$exists,
        ], JSON_UNESCAPED_UNICODE);
        exit;
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

            if (!empty($p['program_id'])) {
                $programs->update((int)$p['program_id'], trim($p['code']), trim($p['name']), (int)$p['required_hours'], (int)($p['is_active'] ?? 1));
                flash('success', 'Program updated.');
            } else {
                $programs->create(trim($p['code']), trim($p['name']), (int)$p['required_hours']);
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
            flash($sent ? 'success' : 'error', $sent ? 'Industry Partner credentials were resent to ' . $company['contact_email'] . (defined('APP_IS_LOCAL') && APP_IS_LOCAL ? ' Check uploads/dev-mail/ for a local copy (Gmail may filter SMTP mail on .test).' : '.') : 'Credentials were reset, but the email failed. Check Email Logs.');
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
            flash($sent ? 'success' : 'error', $sent ? 'Temporary credentials were sent to ' . $target['email'] . (defined('APP_IS_LOCAL') && APP_IS_LOCAL ? ' On local .test, also open uploads/dev-mail/ for the password if Gmail/Yahoo does not show it.' : '.') : 'Password was reset, but the email failed. Check Email Logs.');
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

    public function deactivateStudent(): void
    {
        require_role('admin');
        $p = $this->post();
        $userId = (int)($p['user_id'] ?? 0);
        $reason = trim((string)($p['reason'] ?? ''));
        $notes = trim((string)($p['notes'] ?? ''));

        try {
            if ($userId <= 0) {
                throw new RuntimeException('Invalid student account.');
            }
            if ((int)$userId === (int)current_user()['id']) {
                throw new RuntimeException('You cannot deactivate your own account.');
            }

            $target = (new User($this->db))->findWithDetails($userId);
            if (!$target || ($target['role'] ?? '') !== 'student') {
                throw new RuntimeException('Student account not found.');
            }
            if (!(int)($target['is_active'] ?? 0)) {
                throw new RuntimeException('This student account is already inactive.');
            }

            (new User($this->db))->deactivate($userId, $reason, $notes);

            $reasonLabels = [
                'dropped' => 'Dropped',
                'complete_ojt' => 'Complete OJT',
                'failed' => 'Failed',
                'other' => 'Other',
            ];
            $reasonLabel = $reasonLabels[$reason] ?? ucwords(str_replace('_', ' ', $reason));
            if ($reason === 'other' && $notes !== '') {
                $reasonLabel .= ' — ' . $notes;
            }

            $sent = (new Email($this->db))->send(
                (string)$target['email'],
                'Your AMA Practicum Portal Account Has Been Deactivated',
                'account_deactivated',
                'account_deactivated',
                [
                    'studentName' => $target['name'],
                    'reasonLabel' => $reasonLabel,
                    'supportEmail' => MAIL_FROM_EMAIL,
                ]
            );

            $message = 'Student account deactivated.';
            if ($sent) {
                $message .= ' A notification was sent to ' . $target['email'] . '.';
            } else {
                $message .= ' Email notification could not be sent — check Email Logs.';
            }
            flash($sent ? 'success' : 'error', $message);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('index.php?r=admin_users');
    }

    public function registrationRequests(): void
    {
        require_role('admin');
        $model = new StudentRegistrationRequest($this->db);
        $this->render('admin/registration_requests', [
            'title' => 'Student Account Requests',
            'requests' => $model->allPendingApproval(),
            'coordinators' => (new User($this->db))->byRole('coordinator'),
        ]);
    }

    public function reviewRegistrationRequest(): void
    {
        require_role('admin');
        $p = $this->post();
        $requestId = (int)($p['request_id'] ?? 0);
        $decision = trim((string)($p['decision'] ?? ''));
        $model = new StudentRegistrationRequest($this->db);
        $request = $model->find($requestId);

        try {
            if (!$request || !in_array($request['status'] ?? '', ['pending_approval', 'pending'], true)) {
                throw new RuntimeException('Registration request not found or already processed.');
            }

            if ($decision === 'decline') {
                $model->deleteRequest($requestId);
                flash('success', 'Student account request declined and removed.');
                redirect('index.php?r=admin_registration_requests');
            }

            if ($decision !== 'approve') {
                throw new RuntimeException('Invalid action.');
            }

            $coordinatorId = (int)($p['coordinator_id'] ?? 0);
            if ($coordinatorId <= 0) {
                throw new RuntimeException('Select a coordinator for this student.');
            }
            $coordinator = (new User($this->db))->find($coordinatorId);
            if (!$coordinator || ($coordinator['role'] ?? '') !== 'coordinator' || (int)($coordinator['is_active'] ?? 0) !== 1) {
                throw new RuntimeException('Select a valid active coordinator.');
            }

            if ((new Student($this->db))->existsByStudentNo($request['student_no'])) {
                throw new RuntimeException('This Student ID/USN is already registered.');
            }

            $program = (new Program($this->db))->find((int)($request['program_id'] ?? 0));
            if (!$program) {
                throw new RuntimeException('This registration has no valid program assigned.');
            }

            $yearLevel = trim((string)($request['year_level'] ?? ''));
            if (!in_array($yearLevel, ['3rd Year', '4th Year'], true)) {
                $yearLevel = 'TBD';
            }

            $userId = (int)($request['user_id'] ?? 0);
            $this->db->beginTransaction();

            if ($userId <= 0) {
                if ((new User($this->db))->findByEmail($request['email'])) {
                    throw new RuntimeException('This email is already registered.');
                }
                $userId = (new User($this->db))->createWithPasswordHash(
                    $request['first_name'],
                    $request['last_name'],
                    $request['email'],
                    $request['password_hash'],
                    'student',
                    (int)current_user()['id'],
                    1,
                    1,
                    $request['middle_name'] ?? null
                );
            } else {
                (new User($this->db))->updatePersonName(
                    $userId,
                    $request['first_name'],
                    $request['last_name'],
                    $request['middle_name'] ?? null
                );
            }

            (new Student($this->db))->create(
                $userId,
                $request['student_no'],
                $program['name'],
                $yearLevel,
                $request['cor_file'],
                $coordinatorId,
                (int)$program['id']
            );
            $model->markApproved($requestId, $coordinatorId, (int)current_user()['id']);
            $this->db->commit();
            flash('success', 'Registration approved. The student now has full dashboard access.');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=admin_registration_requests');
    }
}
