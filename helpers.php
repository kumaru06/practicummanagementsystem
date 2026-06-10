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
    $dir = rtrim(dirname($scriptName), '/');
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
        'partner.dashboard' => 'index.php?r=partner',
        'partner.portal' => 'index.php?r=partner_portal',
        'partner.evaluate' => 'index.php?r=partner_evaluate',
        'partner.submissions' => 'index.php?r=partner_submissions',
        'partner.view_endorsement' => 'index.php?r=partner_view_endorsement',
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
