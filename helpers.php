<?php
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function client_ip(): string
{
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        $_SERVER['HTTP_X_REAL_IP'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (!is_string($candidate) || trim($candidate) === '') {
            continue;
        }
        // X-Forwarded-For may contain a comma-separated list; use the first public-looking hop.
        $parts = array_map('trim', explode(',', $candidate));
        foreach ($parts as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return 'Unknown';
}

function client_device_label(?string $userAgent = null): string
{
    $ua = trim((string)($userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? '')));
    if ($ua === '') {
        return 'Unknown device';
    }

    $os = 'Unknown OS';
    if (preg_match('/Windows NT 10\.0/i', $ua)) {
        $os = 'Windows 10/11';
    } elseif (preg_match('/Windows NT 6\.3/i', $ua)) {
        $os = 'Windows 8.1';
    } elseif (preg_match('/Windows NT 6\.1/i', $ua)) {
        $os = 'Windows 7';
    } elseif (preg_match('/Windows/i', $ua)) {
        $os = 'Windows';
    } elseif (preg_match('/Android/i', $ua)) {
        $os = 'Android';
    } elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) {
        $os = 'iOS';
    } elseif (preg_match('/Mac OS X/i', $ua)) {
        $os = 'macOS';
    } elseif (preg_match('/Linux/i', $ua)) {
        $os = 'Linux';
    } elseif (preg_match('/CrOS/i', $ua)) {
        $os = 'Chrome OS';
    }

    $browser = 'Unknown browser';
    if (preg_match('/Edg\//i', $ua)) {
        $browser = 'Microsoft Edge';
    } elseif (preg_match('/OPR\/|Opera/i', $ua)) {
        $browser = 'Opera';
    } elseif (preg_match('/SamsungBrowser/i', $ua)) {
        $browser = 'Samsung Internet';
    } elseif (preg_match('/Chrome\//i', $ua) && !preg_match('/Edg\//i', $ua)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Firefox\//i', $ua)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Safari\//i', $ua) && !preg_match('/Chrome\//i', $ua)) {
        $browser = 'Safari';
    }

    return $browser . ' on ' . $os;
}

function full_name_from_parts(string $firstName, string $lastName, ?string $middleName = null): string
{
    $parts = array_filter(
        [trim($firstName), trim((string)$middleName), trim($lastName)],
        static fn (string $part): bool => $part !== ''
    );
    return implode(' ', $parts);
}

function split_person_name(string $fullName): array
{
    $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');
    if ($fullName === '') {
        return ['first_name' => '', 'middle_name' => '', 'last_name' => ''];
    }
    $parts = explode(' ', $fullName);
    if (count($parts) === 1) {
        return ['first_name' => $parts[0], 'middle_name' => '', 'last_name' => $parts[0]];
    }
    if (count($parts) === 2) {
        return ['first_name' => $parts[0], 'middle_name' => '', 'last_name' => $parts[1]];
    }
    $lastName = (string) array_pop($parts);
    $firstName = (string) array_shift($parts);
    return [
        'first_name' => $firstName,
        'middle_name' => implode(' ', $parts),
        'last_name' => $lastName,
    ];
}

function full_name(?array $record): string
{
    if (!$record) {
        return '';
    }
    $firstName = trim((string)($record['first_name'] ?? ''));
    $middleName = trim((string)($record['middle_name'] ?? ''));
    $lastName = trim((string)($record['last_name'] ?? ''));
    if ($firstName !== '' && $lastName !== '') {
        return full_name_from_parts($firstName, $lastName, $middleName !== '' ? $middleName : null);
    }
    return trim((string)($record['name'] ?? ''));
}

function hydrate_user_record(?array $record): ?array
{
    if (!$record) {
        return null;
    }
    $record['name'] = full_name($record);
    return $record;
}

function redirect(string $route): never
{
    header('Location: ' . $route);
    exit;
}

function app_base_path(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = str_replace('\\', '/', dirname($scriptName));
    $dir = rtrim($dir, '/');
    return $dir === '' || $dir === '.' || $dir === '/' ? '' : $dir;
}

function route_url(string $route, array $params = []): string
{
    $map = [
        'dashboard' => 'index.php',
        'login' => 'auth.php',
        'forgot.password' => 'forgot-password.php',
        'password.reset' => 'reset-password.php',
        'admin.login' => 'auth.php?portal=admin',
        'admin.login.post' => 'auth.php?portal=admin',
        'coordinator.login' => 'auth.php?portal=coordinator',
        'coordinator.login.post' => 'auth.php?portal=coordinator',
        'student.login' => 'auth.php?portal=student',
        'student.login.post' => 'auth.php?portal=student',
        'partner.login' => 'auth.php?portal=partner',
        'partner.login.post' => 'auth.php?portal=partner',
        'admin.dashboard' => 'index.php?r=admin',
        'admin.users' => 'index.php?r=admin_users',
        'admin.create_student' => 'index.php?r=admin_create_student',
        'admin.check_student_no' => 'index.php?r=admin_check_student_no',
        'admin.check_student_email' => 'index.php?r=admin_check_student_email',
        'admin.registration_requests' => 'index.php?r=admin_registration_requests',
        'admin.password_reset_requests' => 'index.php?r=admin_password_reset_requests',
        'student.register' => 'register.php',
        'student.register.verify' => 'register.php',
        'student.pending' => 'index.php?r=student_pending',
        'admin.coordinators' => 'index.php?r=admin_coordinators',
        'admin.partners' => 'index.php?r=admin_partners',
        'admin.programs' => 'index.php?r=admin_programs',
        'admin.terms' => 'index.php?r=admin_terms',
        'admin.email_logs' => 'index.php?r=admin_email_logs',
        'admin.evaluations' => 'index.php?r=admin_evaluations',
        'admin.ojt_placement' => 'index.php?r=admin_ojt_placement',
        'admin.reports' => 'index.php?r=admin_reports',
        'admin.report' => 'index.php?r=admin_report',
        'admin.recent_activities' => 'index.php?r=admin_recent_activities',
        'coordinator.dashboard' => 'index.php?r=coordinator',
        'coordinator.manage' => 'index.php?r=coordinator_manage',
        'coordinator.students' => 'index.php?r=coordinator_students',
        'coordinator.preview_endorsement' => 'index.php?r=coordinator_preview_endorsement',
        'coordinator.evaluations' => 'index.php?r=coordinator_evaluations',
        'coordinator.student_final' => 'index.php?r=coordinator_student_final',
        'student.dashboard' => 'index.php?r=student',
        'student.portal' => 'index.php?r=student_documents',
        'student.records' => 'index.php?r=student_records',
        'student.reports.upload' => 'index.php?r=student_records',
        'student.timeline' => 'index.php?r=student_timeline',
        'student.documents' => 'index.php?r=student_documents',
        'student.settings' => 'index.php?r=student_settings',
        'student.evaluation' => 'index.php?r=student_evaluation',
        'student.documents.final' => 'index.php?r=student_documents_final',
        'student.profile' => 'index.php?r=student_profile',
        'student.password.edit' => 'index.php?r=student_password',
        'student.chat' => 'index.php?r=chat',
        'chat' => 'index.php?r=chat',
        'partner.dashboard' => 'index.php?r=partner',
        'partner.portal' => 'index.php?r=partner_portal',
        'partner.evaluate' => 'index.php?r=partner_evaluate',
        'partner.submissions' => 'index.php?r=partner_submissions',
        'partner.view_endorsement' => 'index.php?r=partner_view_endorsement',
        'partner.settings' => 'index.php?r=partner_settings',
        'partner.profile' => 'index.php?r=partner_profile',
        'partner.password.edit' => 'index.php?r=partner_password',
    ];
    $url = $map[$route] ?? $route;
    if ($params) {
        $separator = str_contains($url, '?') ? '&' : '?';
        $url .= $separator . http_build_query($params);
    }
    if (preg_match('#^(?:https?:)?//#', $url) || str_starts_with($url, '/') || str_starts_with($url, '#')) {
        return $url;
    }
    return app_base_path() . '/' . ltrim($url, '/');
}

function absolute_route_url(string $route, array $params = []): string
{
    $relativeUrl = route_url($route, $params);
    if (preg_match('#^https?://#', $relativeUrl)) {
        return $relativeUrl;
    }

    $base = rtrim(defined('SYSTEM_URL') ? SYSTEM_URL : '', '/');
    $basePath = app_base_path();
    if ($base !== '' && $basePath !== '' && str_starts_with($relativeUrl, $basePath . '/')) {
        $relativeUrl = substr($relativeUrl, strlen($basePath));
    }

    return $base . '/' . ltrim($relativeUrl, '/');
}

function student_is_pending_approval(?int $userId = null): bool
{
    $userId = $userId ?? (int)(current_user()['id'] ?? 0);
    if ($userId <= 0 || (current_user()['role'] ?? '') !== 'student') {
        return false;
    }
    return !(new Student(db()))->findByUser($userId);
}

function route_for_role(?string $role = null): string
{
    return match ($role ?? (current_user()['role'] ?? '')) {
        'admin' => 'index.php?r=admin',
        'coordinator' => 'index.php?r=coordinator',
        'student' => student_is_pending_approval() ? 'index.php?r=student_pending' : 'index.php?r=student',
        'partner' => 'index.php?r=partner',
        default => 'auth.php',
    };
}

function asset(string $path): string
{
    if (preg_match('#^(?:https?:)?//#', $path) || str_starts_with($path, '/')) {
        return $path;
    }
    return app_base_path() . '/' . ltrim($path, '/');
}

function asset_version(string $path): string
{
    $relative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    $absolute = __DIR__ . DIRECTORY_SEPARATOR . $relative;
    return is_file($absolute) ? (string) filemtime($absolute) : '1';
}

function asset_url(string $path): string
{
    return asset($path) . '?v=' . asset_version($path);
}

function student_profile_photo_url(?array $student): string
{
    return profile_photo_url($student);
}

function partner_profile_photo_url(?array $company): string
{
    return profile_photo_url($company);
}

function profile_photo_url(?array $record): string
{
    if (!$record || empty($record['photo_file'])) {
        return '';
    }
    $relative = ltrim((string)$record['photo_file'], '/\\');
    if ($relative === '') {
        return '';
    }
    $absolute = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
    if (!is_file($absolute)) {
        return '';
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($absolute);
    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
        return '';
    }
    return asset($record['photo_file']);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(419);
            if (is_ajax_request()) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'ok' => false,
                    'message' => 'Your session expired. Please refresh the page and try again.',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            exit('Invalid CSRF token. Please go back and try again.');
        }
    }
}

function is_ajax_request(): bool
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }

    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    return str_contains($accept, 'application/json');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect(route_url('login'));
    }
}

function require_role(string|array $roles): void
{
    require_login();
    $roles = (array)$roles;
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function flash(?string $key = null, ?string $message = null): ?string
{
    if ($key && $message) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    if ($key && isset($_SESSION['flash'][$key])) {
        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $value;
    }
    return null;
}

function temporary_report_unlock_enabled(): bool
{
    // Workflow bypass — only ever honored on local/dev. A stray
    // TEMPORARY_REPORT_UNLOCK=true in a production .env is ignored, so the
    // pre-deployment / orientation gating can never be skipped on the live site.
    return (defined('APP_IS_LOCAL') && APP_IS_LOCAL)
        && (defined('TEMPORARY_REPORT_UNLOCK') && TEMPORARY_REPORT_UNLOCK);
}

function temporary_orientation_past_dates_allowed(): bool
{
    // Past-dated orientation / official-start is a local/dev testing aid only
    // and is likewise ignored in production regardless of the .env flag.
    return (defined('APP_IS_LOCAL') && APP_IS_LOCAL)
        && (defined('TEMPORARY_ORIENTATION_PAST_DATES') && TEMPORARY_ORIENTATION_PAST_DATES);
}

function temporary_official_start_past_dates_allowed(): bool
{
    return temporary_orientation_past_dates_allowed();
}

/** Single source of truth: may this enrollment submit DTR / weekly reports? */
function enrollment_allows_reports(?array $enrollment): bool
{
    if (!$enrollment) {
        return false;
    }
    if (temporary_report_unlock_enabled()) {
        return !empty($enrollment['company_id']);
    }
    if (($enrollment['status'] ?? '') !== 'active' || ($enrollment['predeployment_status'] ?? '') !== 'orientation_completed') {
        return false;
    }
    $startDate = $enrollment['official_start_date'] ?? $enrollment['start_date'] ?? null;
    if (!$startDate || strtotime((string)$startDate) === false) {
        return false;
    }

    return date('Y-m-d') >= date('Y-m-d', strtotime((string)$startDate));
}

function enrollment_report_lock_message(?array $enrollment): string
{
    if (!$enrollment) {
        return 'DTR and weekly reports are locked until you are enrolled and deployed to a company.';
    }
    if (temporary_report_unlock_enabled()) {
        if (empty($enrollment['company_id'])) {
            return 'DTR and weekly reports are locked until you are deployed to a company.';
        }
        return 'DTR and weekly reports are unlocked for testing. Normal rules resume when temporary unlock is disabled.';
    }
    if (($enrollment['predeployment_status'] ?? '') !== 'orientation_completed') {
        return 'DTR and weekly reports are locked until your documents are approved, forwarded, accepted, and the company completes your orientation.';
    }
    if (($enrollment['status'] ?? '') !== 'active') {
        return 'DTR and weekly reports are locked until your OJT deployment becomes active.';
    }
    $startDate = $enrollment['official_start_date'] ?? $enrollment['start_date'] ?? null;
    if ($startDate && strtotime((string)$startDate) !== false && date('Y-m-d') < date('Y-m-d', strtotime((string)$startDate))) {
        return 'DTR and weekly reports will unlock on your official OJT start date: ' . date('M d, Y', strtotime((string)$startDate)) . '.';
    }

    return 'DTR and weekly reports are now unlocked.';
}

function validate_orientation_datetime(string $dateTime): ?string
{
    if ($dateTime === '' || strtotime($dateTime) === false) {
        return 'Enter a valid orientation date and time.';
    }
    if (!temporary_orientation_past_dates_allowed() && strtotime($dateTime) < time()) {
        return 'Orientation date and time cannot be in the past.';
    }

    return null;
}

function validate_official_start_date(array $enrollment, string $officialStart): ?string
{
    if ($officialStart === '' || strtotime($officialStart) === false) {
        return 'Enter a valid official OJT start date.';
    }
    if (
        !temporary_official_start_past_dates_allowed()
        && !empty($enrollment['orientation_datetime'])
        && strtotime($officialStart) < strtotime(date('Y-m-d', strtotime((string)$enrollment['orientation_datetime'])))
    ) {
        return 'Official OJT start date cannot be earlier than the orientation date.';
    }

    return null;
}

function validate_projected_end_date(string $officialStart, string $projectedEnd): ?string
{
    if ($projectedEnd === '' || strtotime($projectedEnd) === false) {
        return 'Enter a valid projected end date.';
    }
    if (strtotime($projectedEnd) < strtotime($officialStart)) {
        return 'Projected end date cannot be earlier than the official start date.';
    }

    return null;
}

function validate_dtr_work_date(array $enrollment, string $workDate): ?string
{
    if ($workDate === '' || strtotime($workDate) === false) {
        return 'Invalid work date.';
    }
    $officialStart = $enrollment['official_start_date'] ?? $enrollment['start_date'] ?? null;
    if (!temporary_report_unlock_enabled() && $officialStart && strtotime($workDate) < strtotime((string)$officialStart)) {
        return 'DTR date cannot be earlier than your official OJT start date.';
    }

    return null;
}

/** Server-side gate for every DTR / weekly submit and draft save. */
function assert_student_report_submission(?array $enrollment, ?string $workDate = null): void
{
    if (!enrollment_allows_reports($enrollment)) {
        throw new RuntimeException(enrollment_report_lock_message($enrollment));
    }
    if ($workDate !== null && $workDate !== '') {
        $workDateError = validate_dtr_work_date($enrollment, $workDate);
        if ($workDateError !== null) {
            throw new RuntimeException($workDateError);
        }
    }
}

function assert_student_dtr_resubmit(?array $enrollment, array $dtrRow): void
{
    if (!$enrollment) {
        throw new RuntimeException('Enrollment record not found.');
    }
    if (strtolower((string)($dtrRow['verification_status'] ?? '')) !== 'rejected') {
        throw new RuntimeException('Only rejected daily time records can be corrected and resubmitted.');
    }
    $workDateError = validate_dtr_work_date($enrollment, (string)($dtrRow['work_date'] ?? ''));
    if ($workDateError !== null) {
        throw new RuntimeException($workDateError);
    }
}

function assert_student_weekly_resubmit(?array $enrollment, array $weeklyRow): void
{
    if (!$enrollment) {
        throw new RuntimeException('Enrollment record not found.');
    }
    if (strtolower((string)($weeklyRow['verification_status'] ?? '')) !== 'rejected') {
        throw new RuntimeException('Only rejected weekly reports can be corrected and resubmitted.');
    }
    if (($enrollment['predeployment_status'] ?? '') !== 'orientation_completed') {
        throw new RuntimeException('Weekly report resubmission is unavailable until your OJT deployment is active.');
    }
}

function student_enrollment_required_hours(?array $enrollment): float
{
    return max(0, (float)($enrollment['required_hours'] ?? 0));
}

function enrollment_hours_complete(?array $enrollment, float $approvedHours): bool
{
    $required = student_enrollment_required_hours($enrollment);
    if ($required <= 0) {
        return false;
    }

    return $approvedHours >= $required;
}

function enrollment_allows_final_requirements(?array $enrollment, float $approvedHours): bool
{
    if (!$enrollment) {
        return false;
    }
    if (enrollment_hours_complete($enrollment, $approvedHours)) {
        return true;
    }
    if (($enrollment['status'] ?? '') === 'completed') {
        return true;
    }
    $projectedEnd = $enrollment['projected_end_date'] ?? $enrollment['end_date'] ?? null;
    if ($projectedEnd && strtotime((string)$projectedEnd) !== false) {
        return date('Y-m-d') >= date('Y-m-d', strtotime((string)$projectedEnd));
    }

    return false;
}

function enrollment_final_requirements_lock_message(?array $enrollment, float $approvedHours): string
{
    if (!$enrollment) {
        return 'Final requirements unlock after you are enrolled and deployed for OJT.';
    }
    if (enrollment_allows_final_requirements($enrollment, $approvedHours)) {
        return 'Final requirements are unlocked.';
    }
    $required = student_enrollment_required_hours($enrollment);
    $remaining = max(0, $required - $approvedHours);
    $projectedEnd = $enrollment['projected_end_date'] ?? $enrollment['end_date'] ?? null;
    if ($projectedEnd && strtotime((string)$projectedEnd) !== false) {
        return 'Final requirements unlock when you complete your required OJT hours or on your projected end date (' . date('M d, Y', strtotime((string)$projectedEnd)) . '). You still need ' . number_format($remaining, 2) . ' approved hour(s).';
    }

    return 'Final requirements unlock after you complete all required OJT hours (' . number_format($required, 2) . ' approved hours). You still need ' . number_format($remaining, 2) . ' approved hour(s).';
}

function assert_student_final_requirements(?array $enrollment, float $approvedHours): void
{
    if (!enrollment_allows_final_requirements($enrollment, $approvedHours)) {
        throw new RuntimeException(enrollment_final_requirements_lock_message($enrollment, $approvedHours));
    }
}

/**
 * @return array<int, array{type:string,title:string,message:string,route:string,label:string,count:int}>
 */
function student_action_alerts(array $context): array
{
    $alerts = [];
    $requirements = $context['requirements'] ?? [];
    $dtrs = $context['dtrs'] ?? [];
    $weeklyReports = $context['weeklyReports'] ?? [];
    $predeployment = $context['predeploymentStatus'] ?? 'not_submitted';

    $rejectedRequirements = array_filter($requirements, static fn ($req) => ($req['status'] ?? '') === 'rejected');
    if ($rejectedRequirements) {
        $alerts[] = [
            'type' => 'danger',
            'title' => 'Pre-deployment document rejected',
            'message' => count($rejectedRequirements) . ' requirement file(s) need correction before coordinator review can continue.',
            'route' => route_url('student.documents'),
            'label' => 'Fix documents',
            'count' => count($rejectedRequirements),
        ];
    } elseif ($predeployment === 'needs_revision') {
        $alerts[] = [
            'type' => 'warning',
            'title' => 'Document revision required',
            'message' => 'Replace the rejected pre-deployment file and wait for coordinator review.',
            'route' => route_url('student.documents'),
            'label' => 'Review documents',
            'count' => 1,
        ];
    }

    $rejectedDtrs = array_filter($dtrs, static fn ($row) => strtolower((string)($row['verification_status'] ?? '')) === 'rejected');
    if ($rejectedDtrs) {
        $alerts[] = [
            'type' => 'danger',
            'title' => 'Rejected daily time record(s)',
            'message' => count($rejectedDtrs) . ' DTR entr' . (count($rejectedDtrs) === 1 ? 'y was' : 'ies were') . ' rejected by your Host Training Establishment. Correct and resubmit them.',
            'route' => route_url('student.records'),
            'label' => 'Fix DTR',
            'count' => count($rejectedDtrs),
        ];
    }

    $rejectedWeekly = array_filter($weeklyReports, static fn ($row) => strtolower((string)($row['verification_status'] ?? '')) === 'rejected');
    if ($rejectedWeekly) {
        $alerts[] = [
            'type' => 'danger',
            'title' => 'Rejected weekly report(s)',
            'message' => count($rejectedWeekly) . ' weekly report(s) need correction before they can be approved.',
            'route' => route_url('student.records'),
            'label' => 'Fix weekly report',
            'count' => count($rejectedWeekly),
        ];
    }

    $canSubmitReports = (bool)($context['canSubmitReports'] ?? false);
    if ($canSubmitReports) {
        $today = date('Y-m-d');
        $hasTodayDtr = false;
        foreach ($dtrs as $dtr) {
            if (($dtr['work_date'] ?? '') === $today) {
                $hasTodayDtr = true;
                break;
            }
        }
        if (!$hasTodayDtr) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Submit today\'s DTR',
                'message' => 'You have not logged attendance for today yet.',
                'route' => route_url('student.records'),
                'label' => 'Submit DTR',
                'count' => 1,
            ];
        }
    }

    $canAccessFinalRequirements = (bool)($context['canAccessFinalRequirements'] ?? false);
    $ojtCompletion = $context['ojtCompletion'] ?? [];
    if ($canAccessFinalRequirements && ($ojtCompletion['status'] ?? '') !== 'cleared') {
        $pendingFinalItems = 0;
        foreach (($ojtCompletion['checklist'] ?? []) as $item) {
            if (($item['key'] ?? '') !== 'hte_evaluation' && empty($item['done'])) {
                $pendingFinalItems++;
            }
        }
        if ($pendingFinalItems > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Complete final OJT requirements',
                'message' => 'Your practicum hours are complete or your end date has arrived. Finish your final documents and self-evaluations.',
                'route' => route_url('student.documents.final'),
                'label' => 'Open final requirements',
                'count' => $pendingFinalItems,
            ];
        }
    }

    return $alerts;
}

/**
 * @return array{status:string,label:string,message:string,percent:int,checklist:array<int,array{key:string,label:string,done:bool}>}
 */
function student_ojt_completion_status(array $context): array
{
    $enrollment = $context['enrollment'] ?? null;
    $approvedHours = (float)($context['approvedHours'] ?? 0);
    $requiredHours = student_enrollment_required_hours($enrollment);
    $finalRequirement = $context['finalRequirement'] ?? [];
    $studentEvaluation = $context['studentEvaluation'] ?? [];
    $hteEvaluation = $context['hteEvaluation'] ?? null;

    $hoursDone = enrollment_hours_complete($enrollment, $approvedHours);
    $finalDocsDone = true;
    foreach (array_keys(FinalRequirement::SECTIONS) as $section) {
        if ((string)($finalRequirement[$section . '_status'] ?? 'pending') !== 'submitted') {
            $finalDocsDone = false;
            break;
        }
    }
    $selfEvalDone = true;
    foreach (array_keys(FinalRequirement::EVALUATION_SECTIONS) as $section) {
        if (StudentEvaluation::statusFor($studentEvaluation, $section) !== 'submitted') {
            $selfEvalDone = false;
            break;
        }
    }
    $hteEvalDone = !empty($hteEvaluation) && (
        !empty($hteEvaluation['final_grade'])
        || !empty($hteEvaluation['criteria_ratings'])
    );

    $checklist = [
        ['key' => 'hours', 'label' => 'Required OJT hours approved', 'done' => $hoursDone],
        ['key' => 'final_docs', 'label' => 'Final documents submitted', 'done' => $finalDocsDone],
        ['key' => 'self_eval', 'label' => 'Self-evaluations completed', 'done' => $selfEvalDone],
        ['key' => 'hte_evaluation', 'label' => 'HTE final evaluation received', 'done' => $hteEvalDone],
    ];
    $doneCount = count(array_filter($checklist, static fn ($item) => !empty($item['done'])));
    $percent = count($checklist) > 0 ? (int)round(($doneCount / count($checklist)) * 100) : 0;

    if ($doneCount === count($checklist)) {
        return [
            'status' => 'cleared',
            'label' => 'OJT Cleared',
            'message' => 'Congratulations. Your OJT requirements, self-evaluations, and Host Training Establishment evaluation are complete.',
            'percent' => 100,
            'checklist' => $checklist,
        ];
    }
    if ($hoursDone && (!$finalDocsDone || !$selfEvalDone)) {
        return [
            'status' => 'finals_pending',
            'label' => 'Final Requirements Pending',
            'message' => 'Your required hours are complete. Finish your final documents and self-evaluations to close your OJT.',
            'percent' => $percent,
            'checklist' => $checklist,
        ];
    }
    if ($hoursDone) {
        return [
            'status' => 'hours_complete',
            'label' => 'Hours Complete',
            'message' => 'Your required OJT hours are complete. Complete remaining clearance items when ready.',
            'percent' => $percent,
            'checklist' => $checklist,
        ];
    }

    $remaining = max(0, $requiredHours - $approvedHours);

    return [
        'status' => 'in_progress',
        'label' => 'OJT In Progress',
        'message' => $requiredHours > 0
            ? number_format($approvedHours, 2) . ' of ' . number_format($requiredHours, 2) . ' approved hours completed. ' . number_format($remaining, 2) . ' hour(s) remaining.'
            : 'Continue submitting your DTR and weekly reports on schedule.',
        'percent' => $requiredHours > 0 ? min(100, (int)round(($approvedHours / $requiredHours) * 100)) : 0,
        'checklist' => $checklist,
    ];
}

/**
 * @return array<int, array{label:string,date:?string,note:string,type:string}>
 */
function student_upcoming_deadlines(array $context): array
{
    $deadlines = [];
    $enrollment = $context['enrollment'] ?? null;
    if (!$enrollment) {
        return $deadlines;
    }

    $projectedEnd = $enrollment['projected_end_date'] ?? $enrollment['end_date'] ?? null;
    if ($projectedEnd && strtotime((string)$projectedEnd) !== false) {
        $deadlines[] = [
            'label' => 'Projected OJT end date',
            'date' => date('Y-m-d', strtotime((string)$projectedEnd)),
            'note' => 'Target completion date for your deployment.',
            'type' => 'end',
        ];
    }

    $orientation = $enrollment['orientation_datetime'] ?? null;
    if ($orientation && strtotime((string)$orientation) !== false && ($enrollment['predeployment_status'] ?? '') === 'orientation_scheduled') {
        $deadlines[] = [
            'label' => 'Orientation schedule',
            'date' => date('Y-m-d H:i', strtotime((string)$orientation)),
            'note' => trim((string)($enrollment['orientation_notes'] ?? '')) ?: 'Attend your company orientation on time.',
            'type' => 'orientation',
        ];
    }

    if (!empty($context['canSubmitReports'])) {
        $deadlines[] = [
            'label' => 'Daily DTR',
            'date' => date('Y-m-d'),
            'note' => 'Submit your attendance record before end of day.',
            'type' => 'daily',
        ];
        $deadlines[] = [
            'label' => 'Weekly narrative report',
            'date' => null,
            'note' => 'Submit one weekly report for each practicum week.',
            'type' => 'weekly',
        ];
    }

    return $deadlines;
}

function render_form_date_picker(string $name, string $value = '', array $attributes = []): void
{
    $value = trim($value);
    $isPlaceholder = $value === '' || strtotime($value) === false;
    $display = $isPlaceholder ? 'mm/dd/yyyy' : date('m/d/Y', strtotime($value));
    $storedValue = $isPlaceholder ? '' : date('Y-m-d', strtotime($value));
    $attrPairs = '';
    foreach ($attributes as $key => $attrValue) {
        $attrPairs .= ' ' . $key . '="' . e((string)$attrValue) . '"';
    }
    ?>
    <span class="filter-date-picker form-date-picker wr-date-picker<?= $isPlaceholder ? ' is-placeholder' : '' ?>" data-date-required="1"<?= $attrPairs ?>>
        <input type="hidden" name="<?= e($name) ?>" value="<?= e($storedValue) ?>">
        <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select date">
            <span class="filter-date-value"><?= e($display) ?></span>
            <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
        </button>
        <div class="filter-date-panel" hidden></div>
    </span>
    <?php
}

function assert_orientation_datetime(string $dateTime): void
{
    $error = validate_orientation_datetime($dateTime);
    if ($error !== null) {
        throw new RuntimeException($error);
    }
}

function assert_official_start_date(array $enrollment, string $officialStart, string $projectedEnd): void
{
    $startError = validate_official_start_date($enrollment, $officialStart);
    if ($startError !== null) {
        throw new RuntimeException($startError);
    }
    $endError = validate_projected_end_date($officialStart, $projectedEnd);
    if ($endError !== null) {
        throw new RuntimeException($endError);
    }
}

function mail_error_hint(string $error): string
{
    $from = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'your mailbox';
    if (stripos($error, 'Disabled by user') !== false || stripos($error, '554') !== false || stripos($error, 'suspended') !== false) {
        return 'Hostinger mailbox ' . $from . ' is suspended or disabled. Create or re-enable a mailbox in hPanel -> Emails, then update SMTP_USERNAME, SMTP_PASSWORD, and MAIL_FROM_EMAIL in .env.';
    }
    if (stripos($error, 'Could not authenticate') !== false) {
        return 'SMTP authentication failed for ' . $from . '. Check SMTP_USERNAME and SMTP_PASSWORD in .env (use the full email address and mailbox password).';
    }
    return $error;
}

function random_password(int $length = 12): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $password;
}

function upload_cor(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('COR upload is required.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('COR file must not exceed 5MB.');
    }

    $allowed = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('COR must be a PDF, JPG, or PNG file.');
    }

    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $targetDir = __DIR__ . '/uploads/cor';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $target = $targetDir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Unable to save uploaded COR.');
    }
    return 'uploads/cor/' . $name;
}

function upload_document(array $file, string $folder = 'documents', bool $required = true): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            throw new RuntimeException('Document upload is required.');
        }
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Unable to read uploaded file.');
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        throw new RuntimeException('Uploaded file must not exceed 8MB.');
    }

    $allowed = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Upload must be a PDF, JPG, or PNG file.');
    }

    $safeFolder = preg_replace('/[^a-z0-9_\/-]/i', '', $folder) ?: 'documents';
    $targetDir = __DIR__ . '/uploads/' . $safeFolder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $targetDir . '/' . $name)) {
        throw new RuntimeException('Unable to save uploaded file.');
    }
    return 'uploads/' . $safeFolder . '/' . $name;
}

function upload_profile_photo(array $file, bool $required = false): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            throw new RuntimeException('Profile photo is required.');
        }
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Unable to read uploaded profile photo.');
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        throw new RuntimeException('Profile photo must not exceed 8MB.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Profile photo must be a JPG or PNG image.');
    }

    $targetDir = __DIR__ . '/uploads/profiles';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $targetDir . '/' . $name)) {
        throw new RuntimeException('Unable to save uploaded profile photo.');
    }
    return 'uploads/profiles/' . $name;
}

function upload_signature(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Unable to read uploaded signature.');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Signature image must not exceed 2MB.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Signature must be a PNG or JPG image.');
    }

    $targetDir = __DIR__ . '/uploads/signatures';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $targetDir . '/' . $name)) {
        throw new RuntimeException('Unable to save uploaded signature.');
    }
    return 'uploads/signatures/' . $name;
}

function projected_ojt_end_date(string $startDate, int $requiredHours, int $hoursPerDay = 8): string
{
    $daysNeeded = max(1, (int)ceil($requiredHours / max(1, $hoursPerDay)));
    $date = new DateTimeImmutable($startDate);
    $workedDays = 0;
    while ($workedDays < $daysNeeded) {
        $weekday = (int)$date->format('N'); // 1=Mon ... 6=Sat, 7=Sun
        if ($weekday <= 6) {
            $workedDays++;
        }
        if ($workedDays < $daysNeeded) {
            $date = $date->modify('+1 day');
        }
    }
    return $date->format('Y-m-d');
}

function generate_endorsement_letter(array $student, array $company, array $coordinator, array $enrollment): string
{
    $targetDir = __DIR__ . '/uploads/endorsements';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $safe = static fn ($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    $fileName = 'endorsement_' . (int)($student['id'] ?? 0) . '_' . date('YmdHis') . '.html';
    $content = '<!doctype html><html><head><meta charset="utf-8"><title>Endorsement Letter</title><style>body{font-family:Arial,sans-serif;line-height:1.6;color:#111827;padding:42px;max-width:820px;margin:auto}.head{text-align:center;margin-bottom:34px}.date{text-align:right}.signature{margin-top:54px}</style></head><body>'
        . '<div class="head"><h2>AMA Computer College</h2><h3>Recommendation / Endorsement Letter</h3></div>'
        . '<p class="date">' . date('F d, Y') . '</p>'
        . '<p>Dear ' . $safe($company['contact_person'] ?? 'Host Training Establishment') . ',</p>'
        . '<p>This is to formally endorse <strong>' . $safe($student['name'] ?? $student['student_name'] ?? 'Student') . '</strong>, Student ID <strong>' . $safe($student['student_no'] ?? '') . '</strong>, from <strong>' . $safe($student['course'] ?? '') . '</strong>, for On-the-Job Training deployment at <strong>' . $safe($company['name'] ?? '') . '</strong>.</p>'
        . '<p>The student is enrolled for <strong>' . $safe($enrollment['academic_term'] ?? '') . '</strong> and is required to complete <strong>' . $safe($enrollment['required_hours'] ?? '') . ' hours</strong>. The official OJT start date and projected end date will be confirmed by your company after orientation.</p>'
        . '<p>Attached with this endorsement are the student pre-deployment requirements for your review and acceptance.</p>'
        . '<div class="signature"><p>Respectfully,</p><p><strong>' . $safe($coordinator['name'] ?? 'OJT Coordinator') . '</strong><br>OJT Coordinator<br>' . $safe($coordinator['email'] ?? '') . '</p></div>'
        . '</body></html>';
    file_put_contents($targetDir . DIRECTORY_SEPARATOR . $fileName, $content);
    return 'uploads/endorsements/' . $fileName;
}

function dtr_day_types(): array
{
    return [
        'full' => 'Whole day',
        'half_am' => 'Half day (Morning)',
        'half_pm' => 'Half day (Afternoon)',
        'sick' => 'Sick leave',
        'absent' => 'Absent',
    ];
}

function normalize_dtr_day_type(?string $dayType): string
{
    $dayType = strtolower(trim((string)$dayType));
    return array_key_exists($dayType, dtr_day_types()) ? $dayType : 'full';
}

function format_dtr_day_type_label(?string $dayType): string
{
    $dayType = normalize_dtr_day_type($dayType);
    return dtr_day_types()[$dayType];
}

function dtr_time_has_value(?string $time): bool
{
    return (bool) preg_match('/^\d{1,2}:\d{2}/', trim((string)$time));
}

function dtr_field_is_locked(array $draft, string $field): bool
{
    return !empty($draft[$field . '_locked']) && dtr_time_has_value($draft[$field] ?? null);
}

function format_dtr_time_display(?string $time): string
{
    $time = trim((string)$time);
    if ($time === '') {
        return '--:--';
    }
    $timestamp = strtotime($time);
    if ($timestamp === false) {
        return $time;
    }
    return date('g:i A', $timestamp);
}

function format_dtr_schedule(array $dtr): string
{
    $dayType = normalize_dtr_day_type($dtr['day_type'] ?? 'full');

    if ($dayType === 'sick') {
        return 'Sick leave - no attendance';
    }
    if ($dayType === 'absent') {
        return 'Absent - no attendance';
    }

    $morningIn = trim((string)($dtr['morning_time_in'] ?? ''));
    if ($morningIn === '' && $dayType !== 'half_pm') {
        $morningIn = trim((string)($dtr['time_in'] ?? ''));
    }
    $morningOut = trim((string)($dtr['morning_time_out'] ?? ''));
    $afternoonIn = trim((string)($dtr['afternoon_time_in'] ?? ''));
    $afternoonOut = trim((string)($dtr['afternoon_time_out'] ?? ''));
    if ($afternoonOut === '' && $dayType !== 'half_am') {
        $afternoonOut = trim((string)($dtr['time_out'] ?? ''));
    }

    if ($dayType === 'half_am' && $morningIn !== '' && $morningOut !== '') {
        return sprintf(
            'Half AM %s - %s',
            format_dtr_time_display($morningIn),
            format_dtr_time_display($morningOut)
        );
    }

    if ($dayType === 'half_pm' && $afternoonIn !== '' && $afternoonOut !== '') {
        return sprintf(
            'Half PM %s - %s',
            format_dtr_time_display($afternoonIn),
            format_dtr_time_display($afternoonOut)
        );
    }

    if ($morningIn !== '' && $morningOut !== '' && $afternoonIn !== '' && $afternoonOut !== '') {
        return sprintf(
            'AM %s - %s  ·  PM %s - %s',
            format_dtr_time_display($morningIn),
            format_dtr_time_display($morningOut),
            format_dtr_time_display($afternoonIn),
            format_dtr_time_display($afternoonOut)
        );
    }

    if ($morningIn !== '' && $morningOut !== '') {
        return sprintf(
            'AM %s - %s',
            format_dtr_time_display($morningIn),
            format_dtr_time_display($morningOut)
        );
    }

    if ($afternoonIn !== '' && $afternoonOut !== '') {
        return sprintf(
            'PM %s - %s',
            format_dtr_time_display($afternoonIn),
            format_dtr_time_display($afternoonOut)
        );
    }

    if ($morningIn !== '' && $afternoonOut !== '') {
        return format_dtr_time_display($morningIn) . ' - ' . format_dtr_time_display($afternoonOut);
    }

    return '-';
}

function admin_report_categories(): array
{
    return [
        [
            'id' => 'student',
            'title' => 'Students',
            'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z',
            'items' => [
                ['label' => 'Active Students', 'slug' => 'active_students'],
                ['label' => 'Completed OJT Students', 'slug' => 'completed_ojt_students'],
                ['label' => 'Pending Students', 'slug' => 'pending_students'],
            ],
        ],
        [
            'id' => 'company',
            'title' => 'Companies',
            'icon' => 'M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z',
            'items' => [
                ['label' => 'Partner Company List', 'slug' => 'partner_company_list'],
                ['label' => 'Students Per Company', 'slug' => 'students_per_company'],
            ],
        ],
        [
            'id' => 'attendance',
            'title' => 'Attendance',
            'icon' => 'M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm0 16H5V9h14v10Zm-9-8H7v3h3v3h3v-3h3v-3h-3V8h-3v3Z',
            'items' => [
                ['label' => 'Daily Attendance', 'slug' => 'daily_attendance'],
                ['label' => 'Weekly Attendance', 'slug' => 'weekly_attendance'],
                ['label' => 'Monthly Attendance', 'slug' => 'monthly_attendance'],
                ['label' => 'Hours Rendered', 'slug' => 'hours_rendered'],
            ],
        ],
        [
            'id' => 'evaluation',
            'title' => 'Evaluations',
            'icon' => 'M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z',
            'items' => [
                ['label' => 'Industry Supervisor Evaluation', 'slug' => 'industry_supervisor_evaluation'],
                ['label' => 'Student Self-Evaluation', 'slug' => 'student_self_evaluation'],
                ['label' => 'Coordinator Evaluation', 'slug' => 'coordinator_evaluation'],
                ['label' => 'Final Evaluation Summary', 'slug' => 'final_evaluation_summary'],
            ],
        ],
        [
            'id' => 'requirements',
            'title' => 'Requirements',
            'icon' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Z',
            'items' => [
                ['label' => 'Submitted Requirements', 'slug' => 'submitted_requirements'],
                ['label' => 'Missing Requirements', 'slug' => 'missing_requirements'],
                ['label' => 'Approved Documents', 'slug' => 'approved_documents'],
                ['label' => 'Rejected Documents', 'slug' => 'rejected_documents'],
            ],
        ],
        [
            'id' => 'completion',
            'title' => 'Completion',
            'icon' => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1 14-4-4 1.4-1.4 2.6 2.6 5.6-5.6L18 9l-7 7Z',
            'items' => [
                ['label' => 'Completion Rate by Course', 'slug' => 'completion_rate_by_course'],
                ['label' => 'Completion Rate by Company', 'slug' => 'completion_rate_by_company'],
                ['label' => 'Graduated OJT Students', 'slug' => 'graduated_ojt_students'],
            ],
        ],
    ];
}

function admin_report_by_slug(string $slug): ?array
{
    foreach (admin_report_categories() as $category) {
        foreach ($category['items'] as $item) {
            if ($item['slug'] === $slug) {
                return [
                    'category' => $category['title'],
                    'label' => $item['label'],
                    'slug' => $item['slug'],
                ];
            }
        }
    }

    return null;
}

function coordinator_avatar_tone(int $id): int
{
    return (abs($id) % 6) + 1;
}

function format_activity_time(?string $datetime): string
{
    if ($datetime === null || trim($datetime) === '') {
        return '';
    }

    try {
        $then = new DateTimeImmutable($datetime);
    } catch (Throwable) {
        return '';
    }

    $now = new DateTimeImmutable('now');
    $diffSeconds = $now->getTimestamp() - $then->getTimestamp();

    if ($diffSeconds < 0) {
        return $then->format('g:i A');
    }

    if ($diffSeconds < 60) {
        return 'Just now';
    }

    if ($then->format('Y-m-d') === $now->format('Y-m-d')) {
        return $then->format('g:i A');
    }

    $yesterday = $now->modify('-1 day');
    if ($then->format('Y-m-d') === $yesterday->format('Y-m-d')) {
        return 'Yesterday';
    }

    if ($diffSeconds < 7 * 24 * 60 * 60) {
        return $then->format('D');
    }

    return $then->format('M j');
}

/**
 * @return array{recent:int,previous:int,delta:int,percent:?float,direction:string,label:string}
 */
function build_metric_trend(int $recent, int $previous): array
{
    $delta = $recent - $previous;

    if ($previous === 0) {
        $percent = $recent > 0 ? null : 0.0;
        $direction = $recent > 0 ? 'up' : 'flat';
    } else {
        $percent = round(($delta / $previous) * 100, 1);
        $direction = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
    }

    $label = match (true) {
        $recent === 0 && $previous === 0 => 'No change this month',
        $previous === 0 && $recent > 0 => '+' . $recent . ' this month',
        $delta === 0 => 'Same as last month',
        $delta > 0 => '+' . $delta . ' vs last month',
        default => (string)$delta . ' vs last month',
    };

    return [
        'recent' => $recent,
        'previous' => $previous,
        'delta' => $delta,
        'percent' => $percent,
        'direction' => $direction,
        'label' => $label,
    ];
}

function format_timeline_date(?string $date): string
{
    if ($date === null || trim($date) === '') {
        return '';
    }

    try {
        $dt = new DateTimeImmutable($date);
    } catch (Throwable) {
        return trim($date);
    }

    return $dt->format('M j, Y') . ' · ' . $dt->format('l');
}

/**
 * @param array<int, array<string, mixed>> $dtrs
 * @param array<int, array<string, mixed>> $weeklyReports
 * @return array<int, array{type: string, sort_date: string, data: array<string, mixed>}>
 */
function build_student_timeline_entries(array $dtrs, array $weeklyReports): array
{
    $entries = [];

    foreach ($dtrs as $d) {
        $entries[] = [
            'type' => 'dtr',
            'sort_date' => (string)($d['work_date'] ?? ''),
            'data' => $d,
        ];
    }

    foreach ($weeklyReports as $r) {
        $createdAt = (string)($r['created_at'] ?? '');
        $sortDate = $createdAt !== '' ? substr($createdAt, 0, 10) : '1970-01-01';
        $entries[] = [
            'type' => 'weekly',
            'sort_date' => $sortDate,
            'data' => $r,
        ];
    }

    usort($entries, static function (array $a, array $b): int {
        return strcmp($b['sort_date'], $a['sort_date']);
    });

    return $entries;
}

/**
 * Sample timeline entries for UI preview (Activity Timeline page).
 *
 * @return array{dtrs: array<int, array<string, mixed>>, weeklyReports: array<int, array<string, mixed>>}
 */
function timeline_preview_data(): array
{
    $dtrs = [
        ['work_date' => '2026-07-25', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Assisted in troubleshooting network connectivity issues and documented ticket resolutions.'],
        ['work_date' => '2026-07-24', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Configured workstation setups for new interns and updated asset inventory spreadsheet.'],
        ['work_date' => '2026-07-23', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Shadowed senior staff during client support calls and prepared end-of-day activity summary.'],
        ['work_date' => '2026-07-22', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Performed routine PC maintenance, disk cleanup, and antivirus scans across the IT lab.'],
        ['work_date' => '2026-07-21', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Helped prepare presentation materials for the weekly team meeting and updated shared drive folders.'],
        ['work_date' => '2026-07-18', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Tested backup restoration procedure and logged results in the operations checklist.'],
        ['work_date' => '2026-07-17', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Drafted user guides for common helpdesk requests and reviewed formatting with supervisor.'],
        ['work_date' => '2026-07-16', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Participated in code review session and updated project task board with completed items.'],
        ['work_date' => '2026-07-15', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Installed and configured software packages on department laptops per deployment list.'],
        ['work_date' => '2026-07-14', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Attended OJT orientation briefing and completed workplace safety acknowledgment forms.'],
        ['work_date' => '2026-07-11', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Mapped office network layout and labeled patch panels for easier maintenance access.'],
        ['work_date' => '2026-07-10', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Supported data encoding tasks and verified entries against source documents.'],
        ['work_date' => '2026-07-09', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Observed server room procedures and recorded environmental monitoring readings.'],
        ['work_date' => '2026-07-08', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'Created wireframes for the intern onboarding portal mockup assigned by the team lead.'],
        ['work_date' => '2026-07-07', 'morning_time_in' => '08:00', 'morning_time_out' => '12:00', 'afternoon_time_in' => '13:00', 'afternoon_time_out' => '17:00', 'day_type' => 'full', 'hours' => 8, 'tasks_done' => 'First day on-site: department tour, account provisioning, and initial task briefing with HTE supervisor.'],
    ];

    $weeklyReports = [
        ['week_no' => 5, 'created_at' => '2026-07-26 16:30:00', 'file_path' => null, 'report_text' => 'Submitted summary of network troubleshooting tasks, backup testing, and helpdesk shadowing for Week 5.'],
        ['week_no' => 4, 'created_at' => '2026-07-19 17:00:00', 'file_path' => null, 'report_text' => 'Documented software deployment progress, user guide drafts, and code review participation for Week 4.'],
        ['week_no' => 3, 'created_at' => '2026-07-12 16:45:00', 'file_path' => null, 'report_text' => 'Reported on PC maintenance routines, presentation prep, and shared drive organization completed during Week 3.'],
        ['week_no' => 2, 'created_at' => '2026-07-05 17:15:00', 'file_path' => null, 'report_text' => 'Outlined data encoding support, server room observation, and onboarding portal wireframe work for Week 2.'],
        ['week_no' => 1, 'created_at' => '2026-06-28 16:00:00', 'file_path' => null, 'report_text' => 'Initial weekly report covering OJT orientation, first-week assignments, and learning objectives for the practicum period.'],
    ];

    return ['dtrs' => $dtrs, 'weeklyReports' => $weeklyReports];
}
