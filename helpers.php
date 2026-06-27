<?php
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
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
        'admin.coordinators' => 'index.php?r=admin_coordinators',
        'admin.partners' => 'index.php?r=admin_partners',
        'admin.programs' => 'index.php?r=admin_programs',
        'admin.email_logs' => 'index.php?r=admin_email_logs',
        'admin.evaluations' => 'index.php?r=admin_evaluations',
        'admin.ojt_placement' => 'index.php?r=admin_ojt_placement',
        'admin.reports' => 'index.php?r=admin_reports',
        'admin.report' => 'index.php?r=admin_report',
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

function route_for_role(?string $role = null): string
{
    return match ($role ?? (current_user()['role'] ?? '')) {
        'admin' => 'index.php?r=admin',
        'coordinator' => 'index.php?r=coordinator',
        'student' => 'index.php?r=student',
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

function student_profile_photo_url(?array $student): string
{
    if (!$student || empty($student['photo_file'])) {
        return '';
    }
    $relative = ltrim((string)$student['photo_file'], '/\\');
    if ($relative === '') {
        return '';
    }
    $absolute = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
    return is_file($absolute) ? asset($student['photo_file']) : '';
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
            exit('Invalid CSRF token. Please go back and try again.');
        }
    }
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
    return defined('TEMPORARY_REPORT_UNLOCK') && TEMPORARY_REPORT_UNLOCK;
}

function temporary_orientation_past_dates_allowed(): bool
{
    return (defined('APP_IS_LOCAL') && APP_IS_LOCAL)
        || (defined('TEMPORARY_ORIENTATION_PAST_DATES') && TEMPORARY_ORIENTATION_PAST_DATES);
}

function mail_error_hint(string $error): string
{
    $from = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'your mailbox';
    if (stripos($error, 'Disabled by user') !== false || stripos($error, '554') !== false || stripos($error, 'suspended') !== false) {
        return 'Hostinger mailbox ' . $from . ' is suspended or disabled. Create or re-enable a mailbox in hPanel → Emails, then update SMTP_USERNAME, SMTP_PASSWORD, and MAIL_FROM_EMAIL in .env.';
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
        $weekday = (int)$date->format('N'); // 1=Mon … 6=Sat, 7=Sun
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
        . '<p>Dear ' . $safe($company['contact_person'] ?? 'Industry Partner') . ',</p>'
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
        'full' => 'Full day',
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
        return 'Sick leave — no attendance';
    }
    if ($dayType === 'absent') {
        return 'Absent — no attendance';
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
            'Half AM %s–%s',
            format_dtr_time_display($morningIn),
            format_dtr_time_display($morningOut)
        );
    }

    if ($dayType === 'half_pm' && $afternoonIn !== '' && $afternoonOut !== '') {
        return sprintf(
            'Half PM %s–%s',
            format_dtr_time_display($afternoonIn),
            format_dtr_time_display($afternoonOut)
        );
    }

    if ($morningIn !== '' && $morningOut !== '' && $afternoonIn !== '' && $afternoonOut !== '') {
        return sprintf(
            'AM %s–%s · PM %s–%s',
            format_dtr_time_display($morningIn),
            format_dtr_time_display($morningOut),
            format_dtr_time_display($afternoonIn),
            format_dtr_time_display($afternoonOut)
        );
    }

    if ($morningIn !== '' && $morningOut !== '') {
        return sprintf(
            'AM %s–%s',
            format_dtr_time_display($morningIn),
            format_dtr_time_display($morningOut)
        );
    }

    if ($afternoonIn !== '' && $afternoonOut !== '') {
        return sprintf(
            'PM %s–%s',
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
            'title' => 'Student Reports',
            'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z',
            'items' => [
                ['label' => 'Active Students', 'slug' => 'active_students'],
                ['label' => 'Completed OJT Students', 'slug' => 'completed_ojt_students'],
                ['label' => 'Pending Students', 'slug' => 'pending_students'],
                ['label' => 'Student Attendance Summary', 'slug' => 'student_attendance_summary'],
            ],
        ],
        [
            'id' => 'company',
            'title' => 'Company Reports',
            'icon' => 'M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z',
            'items' => [
                ['label' => 'Partner Company List', 'slug' => 'partner_company_list'],
                ['label' => 'Students Per Company', 'slug' => 'students_per_company'],
                ['label' => 'Company Evaluation Results', 'slug' => 'company_evaluation_results'],
            ],
        ],
        [
            'id' => 'attendance',
            'title' => 'Attendance Reports',
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
            'title' => 'Evaluation Reports',
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
            'title' => 'Requirements Reports',
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
            'title' => 'Completion Reports',
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
