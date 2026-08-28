<?php

class AuthController extends BaseController

{

    private const MAX_LOGIN_ATTEMPTS_PER_IP = 10;

    private const LOGIN_ATTEMPT_WINDOW_MINUTES = 15;

    public function login(?string $portalRole = null): void

    {

        $portalRole = $this->normalizePortalRole($portalRole);

        if ($user = current_user()) {

            if ($portalRole && ($user['role'] ?? null) !== $portalRole) {

                flash('error', 'You are already signed in to a different portal. Please log out before switching portals.');

            }

            redirect(route_for_role($user['role'] ?? null));

        }



        if ($this->wantsLoginPortalPartial()) {

            $this->renderLoginPortalPartial($portalRole);

            return;

        }



        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            verify_csrf();

            if (!$portalRole) {

                flash('error', 'Please choose the correct login portal for your account.');

                redirect('auth.php');

            }

            $identifier = trim($_POST['email'] ?? '');

            $password = $_POST['password'] ?? '';

            $loginIp = client_ip();

            if ($this->loginIsRateLimited($loginIp)) {

                flash('error', 'Too many failed login attempts. Please wait a few minutes before trying again.');

                redirect($portalRole ? 'auth.php?portal=' . urlencode($portalRole) : 'auth.php');

            }

            $user = (new User($this->db))->findForLogin($identifier, $portalRole);

            if ($user && password_verify($password, $user['password_hash'])) {
                if ((int)$user['is_active'] !== 1) {
                    $this->recordLoginAttempt($loginIp, false);
                    $inactiveMessage = match ($portalRole) {
                        'partner' => 'Your Host Training Establishment account is inactive. Please contact the system administrator.',
                        'coordinator' => 'Your coordinator account is inactive. Please contact the system administrator.',
                        'admin' => 'Your administrator account is inactive. Please contact another system administrator.',
                        default => 'Your account is not active yet. Please wait for your OJT coordinator to enroll you.',
                    };
                    flash('error', $inactiveMessage);
                    redirect($portalRole ? 'auth.php?portal=' . urlencode($portalRole) : 'auth.php');
                }

                $this->recordLoginAttempt($loginIp, true);

                if (($user['role'] ?? '') !== $portalRole) {

                    $targetPortal = $this->portalViewData((string)$user['role']);

                    flash('error', 'Invalid login portal for this account. Please use the ' . $targetPortal['portalLabel'] . '.');

                    redirect('auth.php?portal=' . urlencode($portalRole));

                }

                session_regenerate_id(true);

                (new User($this->db))->recordLogin((int)$user['id']);

                $passwordChanged = (int)($user['password_changed'] ?? 1);
                if (($user['role'] ?? '') === 'student'
                    && $passwordChanged === 0
                    && (new StudentRegistrationRequest($this->db))->isSelfRegisteredUser((int)$user['id'])) {
                    $this->db->prepare('UPDATE users SET password_changed = 1 WHERE id = ?')->execute([(int)$user['id']]);
                    $passwordChanged = 1;
                }

                $_SESSION['user'] = [

                    'id' => (int)$user['id'],

                    'name' => $user['name'],

                    'email' => $user['email'],

                    'role' => $user['role'],

                    'password_changed' => $passwordChanged,

                ];

                $_SESSION['user_id'] = (int)$user['id'];

                $_SESSION['role'] = (string)$user['role'];

                redirect(route_for_role($user['role']));

            }

            $this->recordLoginAttempt($loginIp, false);

            flash('error', 'Invalid email/USN or password.');

            redirect($portalRole ? 'auth.php?portal=' . urlencode($portalRole) : 'auth.php');

        }

        extract($this->portalViewData($portalRole), EXTR_SKIP);

        require __DIR__ . '/../views/shared/login.php';

    }



    private function normalizePortalRole(?string $role): ?string

    {

        $role = strtolower(trim((string)$role));

        return in_array($role, ['admin', 'coordinator', 'student', 'partner'], true) ? $role : null;

    }



    private function portalViewData(?string $role = null): array

    {

        $all = [

            'student' => ['label' => 'Student Login Portal', 'route' => route_url('student.login')],

            'admin' => ['label' => 'Admin Login Portal', 'route' => route_url('admin.login')],

            'coordinator' => ['label' => 'OJT Coordinator Login Portal', 'route' => route_url('coordinator.login')],

            'partner' => ['label' => 'Host Training Establishment Login Portal', 'route' => route_url('partner.login')],

        ];

        $visible = array_filter($all, static fn ($key) => $key !== 'admin', ARRAY_FILTER_USE_KEY);

        $loginPortals = $visible;

        if ($role === 'admin') {

            $loginPortals['admin'] = $all['admin'];

        }



        return [

            'portalRole' => $role,

            'portalLabel' => $role && isset($all[$role]) ? $all[$role]['label'] : 'Choose Login Portal',

            'portals' => $visible,

            'loginPortals' => $loginPortals,

        ];

    }



    private function wantsLoginPortalPartial(): bool

    {

        return ($_GET['partial'] ?? '') === 'portal'

            && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0

            && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';

    }



    private function renderLoginPortalPartial(?string $portalRole): void

    {

        header('Content-Type: text/html; charset=UTF-8');

        extract($this->portalViewData($portalRole), EXTR_SKIP);

        $flashError = flash('error');

        require __DIR__ . '/../views/shared/partials/login-portal-card.php';
        release_session_lock();
    }



    public function registerStudent(): void

    {

        if (current_user()) {

            redirect(route_for_role(current_user()['role'] ?? null));

        }



        $registrationModel = new StudentRegistrationRequest($this->db);

        $registrationModel->purgeExpiredUnverified();



        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            verify_csrf();

            $corPath = null;

            $requestId = 0;

            try {

                $firstName = trim((string)($_POST['first_name'] ?? ''));

                $middleName = trim((string)($_POST['middle_name'] ?? ''));

                $lastName = trim((string)($_POST['last_name'] ?? ''));

                $studentNo = trim((string)($_POST['student_no'] ?? ''));

                $email = strtolower(trim((string)($_POST['email'] ?? '')));

                $password = (string)($_POST['password'] ?? '');

                $confirmPassword = (string)($_POST['confirm_password'] ?? '');



                if ($firstName === '' || $lastName === '') {

                    throw new RuntimeException('First name and last name are required.');

                }

                if ($studentNo === '') {

                    throw new RuntimeException('Student ID/USN is required.');

                }

                if (!preg_match('/^\d+$/', $studentNo)) {

                    throw new RuntimeException('Student ID/USN must contain numbers only.');

                }

                if ($registrationModel->studentNoTaken($studentNo)) {

                    throw new RuntimeException('This Student ID/USN is already registered.');

                }

                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

                    throw new RuntimeException('A valid email address is required.');

                }

                if ($registrationModel->emailTaken($email)) {

                    throw new RuntimeException('This email address is already registered.');

                }

                if (strlen($password) < 8) {

                    throw new RuntimeException('Password must be at least 8 characters.');

                }

                if ($password !== $confirmPassword) {

                    throw new RuntimeException('Password confirmation does not match.');

                }

                $program = (new Program($this->db))->find((int)($_POST['program_id'] ?? 0));

                if (!$program || (int)($program['is_active'] ?? 0) !== 1) {

                    throw new RuntimeException('Select a valid program.');

                }

                $yearLevel = trim((string)($_POST['year_level'] ?? ''));

                if (!in_array($yearLevel, ['3rd Year', '4th Year'], true)) {

                    throw new RuntimeException('Select a valid year level.');

                }



                $corPath = upload_cor($_FILES['cor_file'] ?? []);

                $requestId = $registrationModel->create(

                    $firstName,

                    $lastName,

                    $email,

                    $studentNo,

                    password_hash($password, PASSWORD_DEFAULT),

                    $corPath,

                    (int)$program['id'],

                    $yearLevel,

                    $middleName !== '' ? $middleName : null

                );



                $request = $registrationModel->find($requestId);

                if (!$request || empty($request['verification_token'])) {

                    throw new RuntimeException('Unable to prepare email verification.');

                }



                $verifyUrl = absolute_route_url('student.register.verify', [

                    'token' => $request['verification_token'],

                ]);

                $sent = (new Email($this->db))->send(

                    $email,

                    'Verify your AMA OJT student registration',

                    'registration_verify',

                    'registration_verify',

                    [

                        'firstName' => $firstName,

                        'verifyUrl' => $verifyUrl,

                        'expiresHours' => StudentRegistrationRequest::VERIFICATION_HOURS,

                    ],

                    [],

                    'registration'

                );

                if (!$sent) {

                    throw new RuntimeException('Could not send the verification email. Please try again later.');

                }



                flash(

                    'success',

                    'Registration submitted. Please check your email and verify your address within '

                    . StudentRegistrationRequest::VERIFICATION_HOURS

                    . ' hours to activate your account.'

                );

                redirect('register.php?submitted=1');

            } catch (Throwable $e) {

                if ($requestId > 0) {

                    $registrationModel->deleteRequest($requestId);

                } elseif ($corPath && is_file(__DIR__ . '/../' . $corPath)) {

                    @unlink(__DIR__ . '/../' . $corPath);

                }

                flash('error', $e->getMessage());

                redirect('register.php');

            }

        }



        $submitted = isset($_GET['submitted']);
        $verified = isset($_GET['verified']);
        $verifiedAlready = isset($_GET['already']);

        $programs = (new Program($this->db))->all(true);

        require __DIR__ . '/../views/shared/register.php';

    }



    public function verifyRegistrationEmail(): void

    {

        $registrationModel = new StudentRegistrationRequest($this->db);

        $registrationModel->purgeExpiredUnverified();



        $token = trim((string)($_GET['token'] ?? $_GET['amp;token'] ?? ''));
        if ($token === '' && preg_match('/(?:^|[&;])token=([a-f0-9]{32,})/i', (string)($_SERVER['QUERY_STRING'] ?? ''), $matches)) {
            $token = $matches[1];
        }

        if ($token === '') {

            flash('error', 'Invalid verification link.');

            redirect('register.php');

        }



        $request = $registrationModel->findByVerificationToken($token);

        if (!$request) {

            flash('error', 'This verification link is invalid or has already been used.');

            redirect('register.php');

        }



        if (in_array($request['status'] ?? '', ['pending_approval', 'pending'], true)) {
            $userId = (int)($request['user_id'] ?? 0);
            if ($userId > 0) {
                (new User($this->db))->setActive($userId, 1);
            }

            flash('success', 'Your email is already verified. You can sign in while waiting for administrator approval.');

            redirect('register.php?verified=1&already=1');

        }



        if (($request['status'] ?? '') !== 'pending_verification') {

            flash('error', 'This verification link is no longer valid.');

            redirect('register.php');

        }



        if (

            !empty($request['verification_expires_at'])

            && strtotime((string)$request['verification_expires_at']) < time()

        ) {

            $registrationModel->deleteRequest((int)$request['id']);

            flash('error', 'This verification link has expired. Please register again.');

            redirect('register.php');

        }



        try {

            $registrationModel->completeEmailVerification((int)$request['id']);

            flash(

                'success',

                'Email verified successfully. You can now sign in. Your account is pending administrator approval.'

            );

            redirect('register.php?verified=1');

        } catch (Throwable $e) {

            flash('error', $e->getMessage());

            redirect('register.php');

        }

    }



    public function checkRegistrationEmail(): void

    {

        header('Content-Type: application/json; charset=utf-8');

        header('Cache-Control: no-store, no-cache, must-revalidate');



        $registrationModel = new StudentRegistrationRequest($this->db);

        $registrationModel->purgeExpiredUnverified();



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



        $exists = $registrationModel->emailTaken($email);

        echo json_encode(['ok' => true, 'exists' => $exists, 'available' => !$exists], JSON_UNESCAPED_UNICODE);

        exit;

    }



    public function checkRegistrationStudentNo(): void

    {

        header('Content-Type: application/json; charset=utf-8');

        header('Cache-Control: no-store, no-cache, must-revalidate');



        $registrationModel = new StudentRegistrationRequest($this->db);

        $registrationModel->purgeExpiredUnverified();



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



        $exists = $registrationModel->studentNoTaken($studentNo);

        echo json_encode(['ok' => true, 'exists' => $exists, 'available' => !$exists], JSON_UNESCAPED_UNICODE);

        exit;

    }

    public function forgotPassword(): void
    {
        if (current_user()) {
            redirect(route_for_role(current_user()['role'] ?? null));
        }

        $model = new PasswordResetRequest($this->db);
        $role = $this->normalizeForgotPasswordRole($_POST['role'] ?? $_GET['role'] ?? '');
        $submitted = isset($_GET['submitted']);
        $flashSuccess = $submitted ? flash('success') : null;
        // If we're on the "submitted" state, do not show stale errors
        // (e.g. a previous login error) alongside the success message.
        $flashError = $submitted ? null : flash('error');

        if ($role === null) {
            if ($this->wantsForgotPasswordPartial()) {
                header('Content-Type: text/html; charset=UTF-8');
                http_response_code(400);
                echo '<p class="alert danger">Please use forgot password from your login portal.</p>';
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                flash('error', 'Please open forgot password from your login portal.');
            }

            redirect(route_url('login'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $role = $this->normalizeForgotPasswordRole($_POST['role'] ?? '');
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $identifier = trim((string)($_POST['identifier'] ?? ''));

            try {
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Enter a valid registered email address.');
                }
                if ($identifier === '') {
                    throw new RuntimeException('Enter your ' . $model->identifierLabel($role) . '.');
                }

                $user = $model->validateCredentials($role, $email, $identifier);
                if (!$user) {
                    throw new RuntimeException('The email and account ID you entered do not match our records.');
                }
                if ($model->hasPendingForUser((int)$user['id'])) {
                    throw new RuntimeException('A password reset request is already pending review for this account.');
                }

                $requestId = $model->create((int)$user['id'], $role, $email, $identifier);
                $this->notifyAdminsOfPasswordResetRequest($requestId, full_name($user), $role);
                $submitted = true;
                $flashSuccess = 'Your password reset request was submitted. An administrator will review it and send a secure reset link to your registered email if approved.';
                $flashError = null;

                if ($this->wantsForgotPasswordAjax()) {
                    unset($_SESSION['flash']['error']);
                    $this->renderForgotPasswordPartial($role, $submitted, $flashSuccess, $flashError);
                    return;
                }

                flash('success', $flashSuccess);
                // Prevent any lingering error flash (e.g. from login) from showing on the submitted page.
                unset($_SESSION['flash']['error']);
                $redirectRole = $role ? '&role=' . urlencode($role) : '';
                redirect('forgot-password.php?submitted=1' . $redirectRole);
            } catch (Throwable $e) {
                $flashError = $e->getMessage();
                if ($this->wantsForgotPasswordAjax()) {
                    $this->renderForgotPasswordPartial($role, false, null, $flashError);
                    return;
                }
                flash('error', $flashError);
            }
        }

        if ($this->wantsForgotPasswordPartial()) {
            $this->renderForgotPasswordPartial($role, $submitted, $flashSuccess, $flashError);
            return;
        }

        require __DIR__ . '/../views/shared/forgot_password.php';
    }

    private function wantsForgotPasswordPartial(): bool
    {
        $partial = $_GET['partial'] ?? '';
        return in_array($partial, ['card', 'view'], true)
            && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0
            && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
    }

    private function wantsForgotPasswordAjax(): bool
    {
        return strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0
            && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    private function renderForgotPasswordPartial(?string $role, bool $submitted, ?string $flashSuccess, ?string $flashError): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        $partial = $_GET['partial'] ?? $_POST['partial'] ?? 'card';
        $embeddedInPortal = $partial === 'view';
        if ($partial === 'view') {
            require __DIR__ . '/../views/shared/partials/forgot-password-view.php';
            return;
        }
        require __DIR__ . '/../views/shared/partials/forgot-password-card.php';
    }

    public function resetPassword(): void
    {
        if (current_user()) {
            redirect(route_for_role(current_user()['role'] ?? null));
        }

        $model = new PasswordResetRequest($this->db);
        $token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
        $resolved = $model->resolveToken($token);
        $request = $resolved['request'];
        $tokenError = $resolved['error'];
        $tokenRole = $resolved['role'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $token = trim((string)($_POST['token'] ?? ''));
            $resolved = $model->resolveToken($token);
            $request = $resolved['request'];
            $tokenError = $resolved['error'];
            $tokenRole = $resolved['role'] ?? '';
            $password = (string)($_POST['password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');

            try {
                if (!$request) {
                    throw new RuntimeException($resolved['error'] ?: 'This password reset link is invalid or has already been used.');
                }
                if (strlen($password) < 8) {
                    throw new RuntimeException('Password must be at least 8 characters.');
                }
                if ($password !== $confirmPassword) {
                    throw new RuntimeException('Password confirmation does not match.');
                }

                (new User($this->db))->updatePassword((int)$request['user_id'], $password, 1);
                $model->markCompleted((int)$request['id']);

                $role = (string)($request['role'] ?? 'student');
                $redirectRole = in_array($role, ['student', 'coordinator', 'partner'], true) ? $role : 'student';
                redirect('reset-password.php?updated=1&role=' . urlencode($redirectRole));
            } catch (Throwable $e) {
                $tokenError = $e->getMessage();
            }
        }

        require __DIR__ . '/../views/shared/reset_password.php';
    }

    private function normalizeForgotPasswordRole(mixed $role): ?string
    {
        $role = strtolower(trim((string)$role));
        return in_array($role, ['student', 'coordinator', 'partner'], true) ? $role : null;
    }

    private function notifyAdminsOfPasswordResetRequest(int $requestId, string $userName, string $role): void
    {
        $notifications = new Notification($this->db);
        $roleLabel = (new PasswordResetRequest($this->db))->roleLabel($role);
        $link = route_url('admin.password_reset_requests');
        $admins = $this->db->query('SELECT id FROM users WHERE role = "admin" AND is_active = 1')->fetchAll();
        foreach ($admins as $admin) {
            $notifications->create(
                (int)$admin['id'],
                'Password Reset Request',
                $roleLabel . ' ' . $userName . ' submitted a password reset request.',
                $link
            );
        }
    }

    private function loginIsRateLimited(string $ip): bool
    {
        if ($ip === '' || $ip === 'Unknown') {
            return false;
        }
        $this->ensureLoginAttemptsTable();
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE successful = 0
               AND ip_address = ?
               AND attempted_at >= (NOW() - INTERVAL ' . self::LOGIN_ATTEMPT_WINDOW_MINUTES . ' MINUTE)'
        );
        $stmt->execute([$ip]);
        return (int)$stmt->fetchColumn() >= self::MAX_LOGIN_ATTEMPTS_PER_IP;
    }

    private function recordLoginAttempt(string $ip, bool $successful): void
    {
        if ($ip === '' || $ip === 'Unknown') {
            return;
        }
        $this->ensureLoginAttemptsTable();
        if ($successful) {
            $reset = $this->db->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
            $reset->execute([$ip]);
            return;
        }
        $stmt = $this->db->prepare('INSERT INTO login_attempts (ip_address, successful) VALUES (?, 0)');
        $stmt->execute([$ip]);
    }

    private function ensureLoginAttemptsTable(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS login_attempts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                successful TINYINT(1) NOT NULL DEFAULT 0,
                attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_login_attempts_ip (ip_address, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $ready = true;
    }

}

