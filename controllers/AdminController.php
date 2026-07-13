<?php
class AdminController extends BaseController
{
    public function dashboard(): void
    {
        require_role('admin');
        $users = new User($this->db);
        $company = new Company($this->db);
        $enroll = new Enrollment($this->db);
        $this->renderAppPage('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => [
                'coordinators' => $users->countRole('coordinator'),
                'companies' => $company->count(),
                'students' => $users->countRole('student'),
                'active' => $enroll->activeCount(),
            ],
            'companies' => $company->all(),
            'recentActivities' => $this->recentActivities(6),
            'charts' => [
                'statusDistribution' => $enroll->statusDistribution(),
                'completionRates' => $enroll->completionRatesByCourse(),
                'courseStudents' => $enroll->studentProgressByCourse(),
            ],
        ]);
    }

    /**
     * Aggregate recent deployment, login, and student request events for the admin dashboard feed.
     *
     * @return list<array{type:string,title:string,detail:string,time:string,link:string}>
     */
    private function recentActivities(int $limit = 6): array
    {
        $events = [];
        $placementLink = route_url('admin.ojt_placement');
        $fetch = max(12, min(100, $limit * 3));

        try {
            $stmt = $this->db->query(
                'SELECT e.predeployment_status, e.status, e.forwarded_at, e.accepted_at,
                        e.orientation_datetime, e.official_start_date, e.created_at,
                        u.first_name, u.middle_name, u.last_name, u.name AS student_name,
                        s.course, c.name AS company_name
                 FROM ojt_enrollments e
                 JOIN students s ON s.id = e.student_id
                 JOIN users u ON u.id = s.user_id
                 LEFT JOIN partner_companies c ON c.id = e.company_id
                 WHERE e.forwarded_at IS NOT NULL
                    OR e.accepted_at IS NOT NULL
                    OR e.orientation_datetime IS NOT NULL
                    OR e.official_start_date IS NOT NULL
                 ORDER BY GREATEST(
                    COALESCE(e.accepted_at, "1970-01-01"),
                    COALESCE(e.forwarded_at, "1970-01-01"),
                    COALESCE(e.orientation_datetime, "1970-01-01"),
                    COALESCE(CONCAT(e.official_start_date, " 00:00:00"), "1970-01-01")
                 ) DESC
                 LIMIT ' . (int)$fetch
            );

            foreach ($stmt->fetchAll() as $row) {
                $studentName = full_name($row);
                if ($studentName === '') {
                    $studentName = (string)($row['student_name'] ?? 'Student');
                }
                $company = trim((string)($row['company_name'] ?? ''));
                $detail = $company !== '' ? $studentName . ' — ' . $company : $studentName;
                $status = (string)($row['predeployment_status'] ?? '');

                if ((!empty($row['official_start_date']) && $status === 'orientation_completed')
                    || ((string)($row['status'] ?? '') === 'active' && !empty($row['official_start_date']))) {
                    $events[] = [
                        'type' => 'ojt_started',
                        'title' => 'Student OJT started',
                        'detail' => $detail,
                        'time' => (string)$row['official_start_date'] . ' 08:00:00',
                        'link' => $placementLink,
                    ];
                }

                if (!empty($row['orientation_datetime']) && in_array($status, ['orientation_scheduled', 'orientation_completed'], true)) {
                    $events[] = [
                        'type' => 'orientation',
                        'title' => 'Orientation scheduled',
                        'detail' => $detail,
                        'time' => (string)$row['orientation_datetime'],
                        'link' => $placementLink,
                    ];
                }

                if (!empty($row['accepted_at'])) {
                    $events[] = [
                        'type' => 'deployment_accepted',
                        'title' => 'Deployment accepted',
                        'detail' => $detail,
                        'time' => (string)$row['accepted_at'],
                        'link' => $placementLink,
                    ];
                }

                if (!empty($row['forwarded_at'])) {
                    $events[] = [
                        'type' => 'deployment_forwarded',
                        'title' => 'Deployment documents forwarded',
                        'detail' => $detail,
                        'time' => (string)$row['forwarded_at'],
                        'link' => $placementLink,
                    ];
                }
            }
        } catch (Throwable) {
            // ignore if enrollment/deployment columns unavailable
        }

        try {
            (new User($this->db))->ensureLastLoginSupport();
            $stmt = $this->db->query(
                "SELECT first_name, middle_name, last_name, name, role, last_login_at
                 FROM users
                 WHERE last_login_at IS NOT NULL
                   AND role IN ('student', 'coordinator', 'partner', 'admin')
                 ORDER BY last_login_at DESC
                 LIMIT " . (int)$fetch
            );
            $roleLabels = [
                'student' => 'Student',
                'coordinator' => 'Coordinator',
                'partner' => 'Host Training Establishment',
                'admin' => 'Admin',
            ];
            foreach ($stmt->fetchAll() as $row) {
                $name = full_name($row);
                if ($name === '') {
                    $name = (string)($row['name'] ?? 'User');
                }
                $role = (string)($row['role'] ?? '');
                $roleLabel = $roleLabels[$role] ?? ucfirst($role);
                $events[] = [
                    'type' => 'login',
                    'title' => $roleLabel . ' logged in',
                    'detail' => $name,
                    'time' => (string)($row['last_login_at'] ?? ''),
                    'link' => match ($role) {
                        'student' => route_url('admin.users'),
                        'coordinator' => route_url('admin.coordinators'),
                        'partner' => route_url('admin.partners'),
                        default => route_url('admin.dashboard'),
                    },
                ];
            }
        } catch (Throwable) {
            // ignore if last_login_at not available yet
        }

        try {
            $stmt = $this->db->query(
                "SELECT r.first_name, r.middle_name, r.last_name, r.created_at, r.email_verified_at,
                        COALESCE(p.code, p.name, '') AS program_label
                 FROM student_registration_requests r
                 LEFT JOIN programs p ON p.id = r.program_id
                 WHERE r.status IN ('pending_approval', 'pending')
                 ORDER BY COALESCE(r.email_verified_at, r.created_at) DESC
                 LIMIT " . (int)$fetch
            );
            foreach ($stmt->fetchAll() as $row) {
                $name = full_name($row);
                if ($name === '') {
                    $name = 'Student';
                }
                $program = trim((string)($row['program_label'] ?? ''));
                $events[] = [
                    'type' => 'registration',
                    'title' => 'New student account request',
                    'detail' => $program !== '' ? $name . ' - ' . $program : $name,
                    'time' => (string)($row['email_verified_at'] ?? $row['created_at'] ?? ''),
                    'link' => route_url('admin.registration_requests'),
                ];
            }
        } catch (Throwable) {
            // ignore if registration table missing
        }

        try {
            $stmt = $this->db->query(
                "SELECT pr.created_at, pr.email, pr.identifier,
                        u.first_name, u.middle_name, u.last_name, u.name AS user_name
                 FROM password_reset_requests pr
                 JOIN users u ON u.id = pr.user_id
                 WHERE pr.status = 'pending' AND pr.role = 'student'
                 ORDER BY pr.created_at DESC
                 LIMIT " . (int)$fetch
            );
            foreach ($stmt->fetchAll() as $row) {
                $name = full_name($row);
                if ($name === '') {
                    $name = (string)($row['user_name'] ?? 'Student');
                }
                $identifier = trim((string)($row['identifier'] ?? $row['email'] ?? ''));
                $events[] = [
                    'type' => 'password',
                    'title' => 'Student password reset request',
                    'detail' => $identifier !== '' ? $name . ' - ' . $identifier : $name,
                    'time' => (string)($row['created_at'] ?? ''),
                    'link' => route_url('admin.password_reset_requests'),
                ];
            }
        } catch (Throwable) {
            // ignore if password reset table missing
        }

        usort($events, static fn (array $a, array $b): int => strcmp($b['time'], $a['time']));
        return array_slice($events, 0, max(1, $limit));
    }

    public function recentActivitiesPage(): void
    {
        require_role('admin');
        $this->renderAppPage('admin/recent_activities', [
            'title' => 'Recent Activities',
            'activities' => $this->recentActivities(100),
        ]);
    }

    public function manageCoordinators(): void
    {
        require_role('admin');
        $this->renderAppPage('admin/coordinators', [
            'title' => 'Manage Coordinators',
            'coordinators' => (new User($this->db))->byRole('coordinator'),
        ]);
    }

    public function managePartners(): void
    {
        require_role('admin');
        $this->renderAppPage('admin/partners', [
            'title' => 'Manage Companies',
            'partners' => (new Company($this->db))->all(),
            'programs' => (new Program($this->db))->all(true),
            'nextPartnerId' => (new Company($this->db))->peekNextPartnerId(),
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
        $this->renderAppPage('admin/programs', [
            'title' => 'Degree Program',
            'programs' => (new Program($this->db))->all(),
            'terms' => (new Term($this->db))->all(),
        ]);
    }

    private function validateAcademicTerm(string $term): string
    {
        $term = trim($term);
        // Normalize en-dash ( - ) and em-dash ( - ) to regular hyphen, and non-breaking spaces to space
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
        $this->renderAppPage('admin/users', [
            'title' => 'Manage Student',
            'students' => (new Student($this->db))->allForAdmin(),
            'programs' => (new Program($this->db))->all(true),
            'coordinators' => (new User($this->db))->byRole('coordinator'),
        ]);
    }

    public function checkStudentNo(): void
    {
        require_role('admin');
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $studentNo = trim((string)($_GET['student_no'] ?? ''));
        if ($studentNo === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Student ID/USN is required.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!preg_match('/^\d+$/', $studentNo)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Student ID/USN must contain numbers only.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $exists = (new Student($this->db))->existsByStudentNo($studentNo);
        echo json_encode([
            'ok' => true,
            'exists' => $exists,
            'available' => !$exists,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function checkStudentEmail(): void
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

        $exists = (new User($this->db))->findByEmail($email) !== null;
        echo json_encode([
            'ok' => true,
            'exists' => $exists,
            'available' => !$exists,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function createStudent(): void
    {
        require_role('admin');
        $p = $this->post();
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $corPath = null;
        try {
            $studentNo = trim((string)($p['student_no'] ?? ''));
            if ($studentNo === '') {
                throw new RuntimeException('Student ID/USN is required.');
            }
            if (!preg_match('/^\d+$/', $studentNo)) {
                throw new RuntimeException('Student ID/USN must contain numbers only.');
            }
            if ((new Student($this->db))->existsByStudentNo($studentNo)) {
                throw new RuntimeException('This Student ID/USN is already registered.');
            }

            $email = strtolower(trim((string)($p['email'] ?? '')));
            if ($email === '') {
                throw new RuntimeException('Email is required.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('A valid email address is required.');
            }
            if ((new User($this->db))->findByEmail($email)) {
                throw new RuntimeException('This email address is already registered.');
            }

            $coordinatorId = (int)($p['coordinator_id'] ?? 0);
            if ($coordinatorId <= 0) {
                throw new RuntimeException('Select a coordinator for this student.');
            }
            $coordinator = (new User($this->db))->find($coordinatorId);
            if (!$coordinator || ($coordinator['role'] ?? '') !== 'coordinator' || (int)($coordinator['is_active'] ?? 0) !== 1) {
                throw new RuntimeException('Select a valid active coordinator.');
            }

            $program = (new Program($this->db))->find((int)($p['program_id'] ?? 0));
            if (!$program) {
                throw new RuntimeException('Select a valid program/course.');
            }

            $firstName = trim((string)($p['first_name'] ?? ''));
            $middleName = trim((string)($p['middle_name'] ?? ''));
            $lastName = trim((string)($p['last_name'] ?? ''));
            $fullName = full_name_from_parts($firstName, $lastName, $middleName !== '' ? $middleName : null);
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

            $password = random_password();
            $corPath = upload_cor($_FILES['cor_file'] ?? []);

            $this->db->beginTransaction();
            $userId = (new User($this->db))->create(
                $firstName,
                $lastName,
                $email,
                $password,
                'student',
                (int)current_user()['id'],
                0,
                $middleName !== '' ? $middleName : null
            );
            (new Student($this->db))->create(
                $userId,
                $studentNo,
                $program['name'],
                trim((string)$p['year_level']),
                $corPath,
                $coordinatorId,
                (int)$program['id'],
                '',
                $birthdate
            );
            $this->db->commit();

            $coordName = full_name($coordinator) ?: (string)($coordinator['name'] ?? 'coordinator');
            $successMessage = 'Student profile created and assigned to ' . $coordName . '. Login credentials will be emailed when the coordinator enrolls the student.';
            if ($isAjax) {
                flash('success', $successMessage);
                header('Content-Type: application/json');
                echo json_encode([
                    'ok' => true,
                    'message' => $successMessage,
                    'redirect' => route_url('admin.users'),
                ]);
                exit;
            }
            flash('success', $successMessage);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($corPath && is_file(__DIR__ . '/../' . $corPath)) {
                @unlink(__DIR__ . '/../' . $corPath);
            }
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
                exit;
            }
            flash('error', $e->getMessage());
        }
        redirect('index.php?r=admin_users');
    }

    public function evaluations(): void
    {
        require_role('admin');
        $this->renderAppPage('admin/evaluations', [
            'title' => 'Evaluations',
            'evaluations' => (new Evaluation($this->db))->allWithDetails(),
        ]);
    }

    public function reports(): void
    {
        require_role('admin');
        $this->renderAppPage('admin/reports', [
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
        $this->renderAppPage('admin/report_view', [
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
        $this->renderAppPage('admin/ojt_placement', [
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
        $this->renderAppPage('admin/email_logs', [
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
            $middleName = trim((string)($p['middle_name'] ?? ''));
            $lastName = trim($p['last_name'] ?? '');
            if ($firstName === '' || $lastName === '') {
                throw new RuntimeException('First name and last name are required.');
            }
            if (preg_match('/[0-9]/', $firstName . $middleName . $lastName)) {
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

            $userId = $users->create(
                $firstName,
                $lastName,
                $email,
                $password,
                'coordinator',
                current_user()['id'],
                0,
                $middleName !== '' ? $middleName : null
            );
            $stmt = $this->db->prepare('INSERT INTO coordinators (user_id, id_number, department, signature_file) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $idNumber, trim($p['department'] ?? 'OJT Department') ?: 'OJT Department', $signaturePath]);
            $this->db->commit();

            (new Email($this->db))->send($email, 'Your AMA Practicum Coordinator Account', 'account_credentials', 'account_credentials', [
                'name' => full_name_from_parts($firstName, $lastName, $middleName !== '' ? $middleName : null),
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
            $middleName = trim((string)($p['middle_name'] ?? ''));
            $lastName = trim($p['last_name'] ?? '');
            if ($firstName === '' || $lastName === '') {
                throw new RuntimeException('First name and last name are required.');
            }
            if (preg_match('/[0-9]/', $firstName . $middleName . $lastName)) {
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

            $users->updatePersonName($userId, $firstName, $lastName, $middleName !== '' ? $middleName : null);
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
        $isAjax = $this->isAjaxRequest();
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
                throw new RuntimeException('Fill in all required Host Training Establishment details before creating the account.');
            }

            if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Enter a valid Host Training Establishment email address.');
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
            $companyId = $companies->create($userId, $companyName, $address, $contactPerson, $contactEmail, $contactNumber, $programIds, $moaMouFile);
            $this->db->commit();
            $company = $companies->find($companyId);
            (new Email($this->db))->send($contactEmail, 'Your AMA Practicum Host Training Establishment Account', 'account_credentials', 'account_credentials', [
                'name' => $contactPerson,
                'email' => $contactEmail,
                'password' => $password,
                'partnerId' => $company['partner_id'] ?? '',
                'roleLabel' => 'Host Training Establishment',
                'loginUrl' => absolute_route_url('partner.login'),
            ]);
            $successMessage = 'Host Training Establishment account created and credentials email was processed.';
            if ($isAjax) {
                flash('success', $successMessage);
                $this->respondJson([
                    'ok' => true,
                    'message' => $successMessage,
                    'redirect' => route_url('admin.partners'),
                ]);
            }
            flash('success', $successMessage);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($moaMouFile && is_file(__DIR__ . '/../' . $moaMouFile)) {
                @unlink(__DIR__ . '/../' . $moaMouFile);
            }
            if ($isAjax) {
                $this->respondJson(['ok' => false, 'message' => $e->getMessage()], 422);
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
                throw new RuntimeException('Invalid Host Training Establishment selected.');
            }
            if (!$programIds) {
                throw new RuntimeException('Select at least one accepted program/course.');
            }

            $companies = new Company($this->db);
            $company = $companies->find($companyId);
            if (!$company) {
                throw new RuntimeException('Host Training Establishment not found.');
            }

            $companies->syncPrograms($companyId, $programIds);
            flash('success', 'Accepted programs updated for ' . ($company['name'] ?? 'Host Training Establishment') . '.');
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
                $reasonLabel .= ' - ' . $notes;
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
                $message .= ' Email notification could not be sent - check Email Logs.';
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
        $this->renderAppPage('admin/registration_requests', [
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

    public function previewPartnerId(): void
    {
        require_role('admin');
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode([
            'ok' => true,
            'partnerId' => (new Company($this->db))->peekNextPartnerId(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function passwordResetRequests(): void
    {
        require_role('admin');
        $model = new PasswordResetRequest($this->db);
        $this->renderAppPage('admin/password_reset_requests', [
            'title' => 'Password Reset Requests',
            'requests' => $model->allPending(),
        ]);
    }

    public function reviewPasswordResetRequest(): void
    {
        require_role('admin');
        $ajax = $this->isAjaxRequest();
        $p = $this->post();
        $requestId = (int)($p['request_id'] ?? 0);
        $decision = trim((string)($p['decision'] ?? ''));
        $model = new PasswordResetRequest($this->db);
        $request = $model->find($requestId);

        try {
            if (!$request || ($request['status'] ?? '') !== 'pending') {
                throw new RuntimeException('Password reset request not found or already processed.');
            }

            $user = (new User($this->db))->find((int)$request['user_id']);
            if (!$user || (int)($user['is_active'] ?? 0) !== 1) {
                throw new RuntimeException('The account for this request is no longer active.');
            }

            if ($decision === 'reject') {
                $model->reject($requestId, (int)current_user()['id'], trim((string)($p['decline_reason'] ?? '')));
                $message = 'Password reset request rejected.';
                if ($ajax) {
                    $this->respondJson(['ok' => true, 'message' => $message, 'requestId' => $requestId]);
                }
                flash('success', $message);
                redirect('index.php?r=admin_password_reset_requests');
            }

            if ($decision !== 'approve') {
                throw new RuntimeException('Invalid action.');
            }

            $token = $model->approve($requestId, (int)current_user()['id']);
            $resetUrl = absolute_route_url('password.reset', ['token' => $token]);
            $role = (string)($request['role'] ?? '');
            $loginRoute = match ($role) {
                'coordinator' => 'coordinator.login',
                'partner' => 'partner.login',
                default => 'student.login',
            };

            $sent = (new Email($this->db))->send(
                (string)$request['email'],
                'Reset your AMA Practicum password',
                'password_reset_link',
                'password_reset_link',
                [
                    'name' => full_name($user),
                    'roleLabel' => $model->roleLabel($role),
                    'resetUrl' => $resetUrl,
                    'expiresHours' => PasswordResetRequest::RESET_LINK_HOURS,
                    'loginUrl' => absolute_route_url($loginRoute),
                ]
            );

            if (!$sent) {
                throw new RuntimeException('Request was approved, but the reset email failed to send. Check Email Logs.');
            }

            $message = 'Password reset approved and a secure reset link was sent to ' . $request['email'] . '.';
            if ($ajax) {
                $this->respondJson(['ok' => true, 'message' => $message, 'requestId' => $requestId]);
            }
            flash('success', $message);
        } catch (Throwable $e) {
            if ($ajax) {
                $this->respondJson(['ok' => false, 'message' => $e->getMessage()], 422);
            }
            flash('error', $e->getMessage());
        }

        redirect('index.php?r=admin_password_reset_requests');
    }
}
