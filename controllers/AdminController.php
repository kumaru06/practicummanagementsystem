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

    public function managePrograms(): void
    {
        require_role('admin');
        $this->render('admin/programs', [
            'title' => 'Programs / Courses',
            'programs' => (new Program($this->db))->all(),
        ]);
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
            $password = random_password();
            $userId = (new User($this->db))->create(trim($p['name']), trim($p['email']), $password, 'coordinator', current_user()['id'], 0);
            $stmt = $this->db->prepare('INSERT INTO coordinators (user_id, department) VALUES (?, ?)');
            $stmt->execute([$userId, trim($p['department'] ?? 'OJT Department') ?: 'OJT Department']);
            (new Email($this->db))->send(trim($p['email']), 'Your AMA Practicum Coordinator Account', 'account_credentials', 'account_credentials', [
                'name' => trim($p['name']),
                'email' => trim($p['email']),
                'password' => $password,
                'roleLabel' => 'OJT Coordinator',
            ]);
            flash('success', 'Coordinator account created and credentials email was processed.');
        } catch (Throwable $e) {
            $msg = str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate entry')
                ? 'Email already exists.'
                : $e->getMessage();
            flash('error', $msg);
        }
        redirect('index.php?r=admin_coordinators');
    }

    public function createCompany(): void
    {
        require_role('admin');
        $p = $this->post();
        try {
            $password = random_password();
            $companyName = trim($p['company_name'] ?? '');
            $contactPerson = trim($p['contact_person'] ?? $p['name'] ?? '');
            $contactEmail = strtolower(trim($p['contact_email'] ?? ''));
            $address = trim($p['address'] ?? '');
            $programIds = array_values(array_unique(array_filter(array_map('intval', (array)($p['program_ids'] ?? [])))));

            if ($companyName === '' || $contactPerson === '' || $contactEmail === '' || $address === '' || trim((string)($p['contact_number'] ?? '')) === '') {
                throw new RuntimeException('Fill in all required partner company details before creating the account.');
            }

            if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Enter a valid partner email address.');
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
            $userId = (new User($this->db))->create($companyName, $contactEmail, $password, 'partner', current_user()['id'], 0);
            (new Company($this->db))->create($userId, $companyName, $address, $contactPerson, $contactEmail, $contactNumber, $programIds);
            (new Email($this->db))->send($contactEmail, 'Your AMA Practicum Partner Account', 'account_credentials', 'account_credentials', [
                'name' => $contactPerson,
                'email' => $contactEmail,
                'password' => $password,
                'roleLabel' => 'Industry Partner',
            ]);
            flash('success', 'Partner company account created and credentials email was processed.');
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
                $programs->update((int)$p['program_id'], trim($p['code']), trim($p['name']), trim($p['term'] ?? ''), (int)$p['required_hours'], (int)($p['is_active'] ?? 1));
                flash('success', 'Program updated.');
            } else {
                $programs->create(trim($p['code']), trim($p['name']), trim($p['term'] ?? ''), (int)$p['required_hours']);
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
                throw new RuntimeException('Partner company not found.');
            }
            $password = random_password();
            (new User($this->db))->updatePassword((int)$company['user_id'], $password, 0);
            $sent = (new Email($this->db))->send($company['contact_email'], 'Your AMA Practicum Partner Account', 'account_credentials', 'account_credentials', [
                'name' => $company['contact_person'],
                'email' => $company['contact_email'],
                'password' => $password,
                'roleLabel' => 'Industry Partner',
            ]);
            flash($sent ? 'success' : 'error', $sent ? 'Partner credentials were resent to ' . $company['contact_email'] . '.' : 'Credentials were reset, but the email failed. Check Email Logs.');
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
            $target = (new User($this->db))->find((int)$p['user_id']);
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
            $sent = (new Email($this->db))->send($target['email'], 'Your AMA Practicum Account Credentials', 'account_credentials', 'account_credentials', [
                'name' => $target['name'],
                'email' => $target['email'],
                'password' => $password,
                'roleLabel' => $roleLabel,
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
