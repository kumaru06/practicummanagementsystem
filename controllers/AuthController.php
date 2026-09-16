<?php

class AuthController extends BaseController

{

    private const MAX_LOGIN_ATTEMPTS_PER_IP = 10;

    // Per-account cap catches targeted brute force even when the source IP is
    // rotated/unknown (defence-in-depth alongside the per-IP cap).
    private const MAX_LOGIN_ATTEMPTS_PER_ACCOUNT = 8;

    private const LOGIN_ATTEMPT_WINDOW_MINUTES = 15;

    private const RESEND_COOLDOWN_SECONDS = 60;

    // Throttle the public "is this email/USN taken?" endpoints to curb enumeration
    // while still allowing normal live form validation.
    private const SIGNUP_CHECK_MAX = 40;

    private const SIGNUP_CHECK_WINDOW_MIN = 10;

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

            if ($this->loginIsRateLimited($loginIp, $identifier)) {

                flash('error', 'Too many failed login attempts. Please wait a few minutes before trying again.');

                redirect($portalRole ? 'auth.php?portal=' . urlencode($portalRole) : 'auth.php');

            }

            $user = (new User($this->db))->findForLogin($identifier, $portalRole);

            if ($user && password_verify($password, $user['password_hash'])) {
                if ((int)$user['is_active'] !== 1) {
                    $this->recordLoginAttempt($loginIp, false, $identifier);
                    $inactiveMessage = match ($portalRole) {
                        'partner' => 'Your Host Training Establishment account is inactive. Please contact the system administrator.',
                        'coordinator' => 'Your coordinator account is inactive. Please contact the system administrator.',
                        'admin' => 'Your administrator account is inactive. Please contact another system administrator.',
                        default => 'Your account is not active yet. Please wait for your OJT coordinator to enroll you.',
                    };
                    flash('error', $inactiveMessage);
                    redirect($portalRole ? 'auth.php?portal=' . urlencode($portalRole) : 'auth.php');
                }

                $this->recordLoginAttempt($loginIp, true, $identifier);

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

                unset($_SESSION['unverified_registration_id'], $_SESSION['unverified_resend_at']);

                redirect(route_for_role($user['role']));

            }

            $this->continueUnverifiedStudentRegistration($identifier, $password, $portalRole, $loginIp);

            $this->recordLoginAttempt($loginIp, false, $identifier);

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

                if ($strengthError = password_strength_error($password)) {

                    throw new RuntimeException($strengthError);

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

                $birthdate = trim((string)($_POST['birthdate'] ?? ''));
                if ($birthdate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
                    throw new RuntimeException('Select a valid birthdate.');
                }
                $birthdateObj = new DateTime($birthdate);
                $age = (new DateTime())->diff($birthdateObj)->y;
                if ($age < 20) {
                    throw new RuntimeException('You must be at least 20 years old to be eligible for OJT.');
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

                    $middleName !== '' ? $middleName : null,

                    $birthdate

                );



                $request = $registrationModel->find($requestId);

                if (!$request || empty($request['verification_token'])) {

                    throw new RuntimeException('Unable to prepare email verification.');

                }



                if (!$this->sendRegistrationVerificationEmail($request)) {

                    throw new RuntimeException('Could not send the verification email. Please try again later.');

                }



                $this->startUnverifiedRegistrationSession($requestId, true);

                redirect(route_url('student.register.pending'));

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
        $verificationPending = false;

        $programs = (new Program($this->db))->all(true);

        require __DIR__ . '/../views/shared/register.php';

    }



    public function verifyRegistrationEmail(): void
    {
        $registrationModel = new StudentRegistrationRequest($this->db);

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
            $superseded = $registrationModel->findByPreviousVerificationToken($token);
            if ($superseded && ($superseded['status'] ?? '') === 'pending_verification') {
                $this->startUnverifiedRegistrationSession((int)$superseded['id']);
                flash('error', 'This verification link was replaced. Open the latest email, or use Resend to get a new link.');
                redirect(route_url('student.register.pending'));
            }
            $registrationModel->purgeExpiredUnverified();
            flash('error', 'This verification link is invalid or has already been used.');
            redirect(route_url('student.login'));
        }

        $registrationModel->purgeExpiredUnverified((int)$request['id']);

        if (in_array($request['status'] ?? '', ['pending_approval', 'pending'], true)) {
            $userId = (int)($request['user_id'] ?? 0);
            if ($userId > 0) {
                (new User($this->db))->setActive($userId, 1);
                $this->establishStudentSession($userId);
                flash('success', 'Your email is already verified. Your dashboard stays locked until an administrator approves your registration.');
                redirect(route_url('student.pending'));
            }
            flash('error', 'This verification link is no longer valid.');
            redirect(route_url('student.login'));
        }

        if (($request['status'] ?? '') !== 'pending_verification') {
            flash('error', 'This verification link is no longer valid.');
            redirect(route_url('student.login'));
        }

        if ($registrationModel->isVerificationExpired($request)) {
            $this->startUnverifiedRegistrationSession((int)$request['id']);
            flash('error', 'This verification link has expired. Use Resend to get a new link.');
            redirect(route_url('student.register.pending'));
        }

        try {
            $userId = $registrationModel->completeEmailVerification((int)$request['id']);
            $this->establishStudentSession($userId);
            flash('success', 'Email verified successfully. Your dashboard stays locked until an administrator approves your registration.');
            redirect(route_url('student.pending'));
        } catch (Throwable $e) {
            $this->startUnverifiedRegistrationSession((int)$request['id']);
            flash('error', $e->getMessage());
            redirect(route_url('student.register.pending'));
        }
    }

    public function showPendingVerification(): void
    {
        if (current_user()) {
            redirect(route_for_role(current_user()['role'] ?? null));
        }

        $registrationModel = new StudentRegistrationRequest($this->db);
        $requestId = (int)($_SESSION['unverified_registration_id'] ?? 0);
        $registrationModel->purgeExpiredUnverified($requestId > 0 ? $requestId : null);

        $request = $requestId > 0 ? $registrationModel->find($requestId) : null;
        if (!$request || ($request['status'] ?? '') !== 'pending_verification') {
            unset($_SESSION['unverified_registration_id'], $_SESSION['unverified_resend_at']);
            flash('error', 'Please sign in with your registration email or USN to continue email verification.');
            redirect(route_url('student.login'));
        }

        $this->renderVerificationPendingView($request);
    }

    public function resendRegistrationVerification(): void
    {
        if (current_user()) {
            redirect(route_for_role(current_user()['role'] ?? null));
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(route_url('student.register.pending'));
        }

        verify_csrf();

        $registrationModel = new StudentRegistrationRequest($this->db);
        $requestId = (int)($_SESSION['unverified_registration_id'] ?? 0);
        $request = $requestId > 0 ? $registrationModel->find($requestId) : null;
        if (!$request || ($request['status'] ?? '') !== 'pending_verification') {
            unset($_SESSION['unverified_registration_id'], $_SESSION['unverified_resend_at']);
            flash('error', 'Please sign in with your registration email or USN to continue email verification.');
            redirect(route_url('student.login'));
        }

        $wait = $this->remainingResendWait();
        if ($wait > 0) {
            redirect(route_url('student.register.pending'));
        }

        try {
            $request = $registrationModel->refreshVerificationToken($requestId);
            if (!$this->sendRegistrationVerificationEmail($request)) {
                throw new RuntimeException('Could not send the verification email. Please try again later.');
            }
            $this->markVerificationEmailSent();
            flash('success', 'A new verification email was sent to ' . mask_email((string)$request['email']) . '.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect(route_url('student.register.pending'));
    }

    private function continueUnverifiedStudentRegistration(
        string $identifier,
        string $password,
        ?string $portalRole,
        string $loginIp
    ): void {
        if ($portalRole !== 'student' || $identifier === '' || $password === '') {
            return;
        }

        $registrationModel = new StudentRegistrationRequest($this->db);
        $request = $registrationModel->findUnverifiedForLogin($identifier);
        if (!$request || !password_verify($password, (string)($request['password_hash'] ?? ''))) {
            return;
        }

        $this->recordLoginAttempt($loginIp, true, $identifier);

        $emailJustSent = false;
        if ($registrationModel->isVerificationExpired($request) || empty($request['verification_token'])) {
            try {
                $request = $registrationModel->refreshVerificationToken((int)$request['id']);
                if ($this->sendRegistrationVerificationEmail($request)) {
                    $emailJustSent = true;
                } else {
                    flash('error', 'Could not send a new verification email. Use Resend on the next page.');
                }
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
            }
        }

        $this->startUnverifiedRegistrationSession((int)$request['id'], $emailJustSent);
        redirect(route_url('student.register.pending'));
    }

    private function startUnverifiedRegistrationSession(int $requestId, bool $emailJustSent = false): void
    {
        session_regenerate_id(true);
        $_SESSION['unverified_registration_id'] = $requestId;
        unset($_SESSION['user'], $_SESSION['user_id'], $_SESSION['role']);
        if ($emailJustSent) {
            $this->markVerificationEmailSent();
        }
    }

    private function markVerificationEmailSent(): void
    {
        $_SESSION['unverified_resend_at'] = time();
    }

    private function remainingResendWait(): int
    {
        $last = (int)($_SESSION['unverified_resend_at'] ?? 0);
        if ($last <= 0) {
            return 0;
        }
        return max(0, self::RESEND_COOLDOWN_SECONDS - (time() - $last));
    }

    private function establishStudentSession(int $userId): void
    {
        $user = (new User($this->db))->find($userId);
        if (!$user || ($user['role'] ?? '') !== 'student') {
            throw new RuntimeException('Unable to sign you in after verification.');
        }

        unset($_SESSION['unverified_registration_id'], $_SESSION['unverified_resend_at']);
        session_regenerate_id(true);
        (new User($this->db))->recordLogin($userId);

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'password_changed' => (int)($user['password_changed'] ?? 1),
        ];
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role'] = 'student';
    }

    private function sendRegistrationVerificationEmail(array $request): bool
    {
        $token = trim((string)($request['verification_token'] ?? ''));
        $email = strtolower(trim((string)($request['email'] ?? '')));
        if ($token === '' || $email === '') {
            return false;
        }

        return (new Email($this->db))->send(
            $email,
            'Verify your AMA OJT student registration',
            'registration_verify',
            'registration_verify',
            [
                'firstName' => (string)($request['first_name'] ?? 'Student'),
                'verifyUrl' => absolute_route_url('student.register.verify', ['token' => $token]),
                'expiresHours' => StudentRegistrationRequest::VERIFICATION_HOURS,
            ],
            [],
            'registration'
        );
    }

    private function renderVerificationPendingView(array $request): void
    {
        $verificationPending = true;
        $verificationEmail = (string)($request['email'] ?? '');
        $verificationEmailMasked = mask_email($verificationEmail);
        $verificationFirstName = (string)($request['first_name'] ?? '');
        $resendWaitSeconds = $this->remainingResendWait();
        $resendCooldownSeconds = self::RESEND_COOLDOWN_SECONDS;
        $submitted = false;
        $verified = false;
        $verifiedAlready = false;
        $programs = [];
        require __DIR__ . '/../views/shared/register.php';
    }

    public function checkRegistrationEmail(): void

    {

        header('Content-Type: application/json; charset=utf-8');

        header('Cache-Control: no-store, no-cache, must-revalidate');



        $registrationModel = new StudentRegistrationRequest($this->db);

        $registrationModel->purgeExpiredUnverified();



        $this->assertSignupCheckWithinRate();

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



        $this->assertSignupCheckWithinRate();

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
                if ($strengthError = password_strength_error($password)) {
                    throw new RuntimeException($strengthError);
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

    private function normalizeLoginIdentifier(string $identifier): string
    {
        return mb_strtolower(trim($identifier));
    }

    private function loginIsRateLimited(string $ip, string $identifier = ''): bool
    {
        $this->ensureLoginAttemptsTable();
        $window = self::LOGIN_ATTEMPT_WINDOW_MINUTES;

        // Per-IP cap (only when we have a trustworthy IP).
        if ($ip !== '' && $ip !== 'Unknown') {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM login_attempts
                 WHERE successful = 0
                   AND ip_address = ?
                   AND attempted_at >= (NOW() - INTERVAL ' . $window . ' MINUTE)'
            );
            $stmt->execute([$ip]);
            if ((int)$stmt->fetchColumn() >= self::MAX_LOGIN_ATTEMPTS_PER_IP) {
                return true;
            }
        }

        // Per-account cap catches targeted brute force regardless of source IP.
        $identifier = $this->normalizeLoginIdentifier($identifier);
        if ($identifier !== '') {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM login_attempts
                 WHERE successful = 0
                   AND identifier = ?
                   AND attempted_at >= (NOW() - INTERVAL ' . $window . ' MINUTE)'
            );
            $stmt->execute([$identifier]);
            if ((int)$stmt->fetchColumn() >= self::MAX_LOGIN_ATTEMPTS_PER_ACCOUNT) {
                return true;
            }
        }

        return false;
    }

    private function recordLoginAttempt(string $ip, bool $successful, string $identifier = ''): void
    {
        $this->ensureLoginAttemptsTable();
        $identifier = $this->normalizeLoginIdentifier($identifier);
        $ipValue = ($ip === '' || $ip === 'Unknown') ? null : $ip;

        if ($successful) {
            // Clear failures for this IP and/or this account after a good login.
            if ($ipValue !== null) {
                $this->db->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([$ipValue]);
            }
            if ($identifier !== '') {
                $this->db->prepare('DELETE FROM login_attempts WHERE identifier = ?')->execute([$identifier]);
            }
            return;
        }

        if ($ipValue === null && $identifier === '') {
            return; // nothing we can attribute this failure to
        }
        $stmt = $this->db->prepare('INSERT INTO login_attempts (ip_address, identifier, successful) VALUES (?, ?, 0)');
        $stmt->execute([$ipValue ?? '', $identifier !== '' ? $identifier : null]);
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
                identifier VARCHAR(190) NULL,
                successful TINYINT(1) NOT NULL DEFAULT 0,
                attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_login_attempts_ip (ip_address, attempted_at),
                INDEX idx_login_attempts_identifier (identifier, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        // Upgrade pre-existing tables that predate the per-account column/index (local only).
        if (APP_IS_LOCAL) {
            try {
                $hasColumn = $this->db->query(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'login_attempts' AND COLUMN_NAME = 'identifier'"
                )->fetchColumn();
                if ((int)$hasColumn === 0) {
                    $this->db->exec('ALTER TABLE login_attempts ADD COLUMN identifier VARCHAR(190) NULL AFTER ip_address');
                    $this->db->exec('ALTER TABLE login_attempts ADD INDEX idx_login_attempts_identifier (identifier, attempted_at)');
                }
            } catch (Throwable) {
                // If information_schema is unavailable, the per-IP cap still applies.
            }
        }
        $ready = true;
    }

    private function ensureSignupCheckTable(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS signup_check_attempts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_signup_check_ip (ip_address, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $ready = true;
    }

    /**
     * Per-IP throttle for the public availability-check endpoints. Emits a 429 JSON
     * response and exits when the caller exceeds the window budget. Keeps live form
     * validation working while curbing bulk email/USN enumeration.
     */
    private function assertSignupCheckWithinRate(): void
    {
        $ip = client_ip();
        if ($ip === '' || $ip === 'Unknown') {
            return; // cannot attribute the request; avoid blocking legitimate users
        }
        $this->ensureSignupCheckTable();
        $window = self::SIGNUP_CHECK_WINDOW_MIN;
        $this->db->prepare(
            'DELETE FROM signup_check_attempts WHERE attempted_at < (NOW() - INTERVAL ' . $window . ' MINUTE)'
        )->execute();
        $count = $this->db->prepare(
            'SELECT COUNT(*) FROM signup_check_attempts
             WHERE ip_address = ? AND attempted_at >= (NOW() - INTERVAL ' . $window . ' MINUTE)'
        );
        $count->execute([$ip]);
        if ((int)$count->fetchColumn() >= self::SIGNUP_CHECK_MAX) {
            http_response_code(429);
            echo json_encode(
                ['ok' => false, 'message' => 'Too many checks. Please slow down and try again in a few minutes.'],
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }
        $this->db->prepare('INSERT INTO signup_check_attempts (ip_address) VALUES (?)')->execute([$ip]);
    }

}

