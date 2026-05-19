<?php
require_once __DIR__ . '/init.php';
require_login();

$path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$base = trim(basename(__DIR__), '/');
if ($base && str_starts_with($path, $base . '/')) {
    $path = substr($path, strlen($base) + 1);
} elseif ($path === $base) {
    $path = '';
}
$pathRoutes = [
    'admin' => 'admin',
    'admin/users' => 'admin_users',
    'admin/coordinators' => 'admin_coordinators',
    'admin/partners' => 'admin_partners',
    'admin/partners/document' => 'admin_partner_document',
    'admin/programs' => 'admin_programs',
    'admin/email-logs' => 'admin_email_logs',
    'admin/evaluations' => 'admin_evaluations',
    'coordinator' => 'coordinator',
    'coordinator/manage' => 'coordinator_manage',
    'coordinator/students' => 'coordinator_students',
    'coordinator/moa-mou' => 'coordinator_moa_mou',
    'coordinator/partners/document' => 'coordinator_partner_document',
    'coordinator/evaluations' => 'coordinator_evaluations',
    'student' => 'student',
    'student/dashboard' => 'student',
    'student/records' => 'student_records',
    'student/reports/upload' => 'student_records',
    'student/timeline' => 'student_timeline',
    'student/documents' => 'student_documents',
    'student/settings' => 'student_settings',
    'student/profile' => 'student_profile',
    'student/password' => 'student_password',
    'partner' => 'partner',
    'partner/portal' => 'partner_portal',
];
$route = $_GET['r'] ?? ($pathRoutes[$path] ?? current_user()['role']);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && ($_POST['action'] ?? '') === 'mark_all_notifications_read') {
    verify_csrf();
    (new Notification(db()))->markAllRead((int)current_user()['id']);
    flash('success', 'Notifications marked as read.');
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

if (($_GET['action'] ?? '') === 'mark_all_notifications_read') {
    (new Notification(db()))->markAllRead((int)current_user()['id']);
    flash('success', 'Notifications marked as read.');
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

if (($_GET['action'] ?? '') === 'read_notification') {
    $notificationId = (int)($_GET['id'] ?? 0);
    $userId = (int)current_user()['id'];
    $notificationModel = new Notification(db());
    $notification = $notificationModel->findForUser($notificationId, $userId);
    $notificationModel->markRead($notificationId, $userId);
    $redirectTo = (string)($_GET['redirect'] ?? 'index.php');
    if (preg_match('#^(?:[a-z][a-z0-9+.-]*:|//)#i', $redirectTo)) {
        $redirectTo = 'index.php';
    }
    if ((current_user()['role'] ?? '') === 'coordinator' && str_contains($redirectTo, 'r=coordinator_students') && !str_contains($redirectTo, 'focus_student=')) {
        $message = (string)($notification['message'] ?? '');
        foreach ((new Student(db()))->allByCoordinator($userId) as $student) {
            if (!empty($student['name']) && stripos($message, (string)$student['name']) !== false) {
                $redirectTo = route_url('coordinator.students', ['focus_student' => (int)$student['id']]);
                break;
            }
        }
    }
    redirect($redirectTo !== '' ? $redirectTo : 'index.php');
}

$freshUser = (new User(db()))->find((int)current_user()['id']);
$_SESSION['user']['password_changed'] = (int)($freshUser['password_changed'] ?? 1);

if ((int)current_user()['password_changed'] === 0) {
    if ($method === 'POST' && ($_POST['action'] ?? '') === 'student_change_password') {
        (new StudentController())->changePassword();
    }
    (new StudentController())->changePasswordForm();
    exit;
}

if (current_user()['role'] === 'student') {
    $studentRecord = (new Student(db()))->findByUser((int)current_user()['id']);
    if ($studentRecord && (int)($studentRecord['profile_completed'] ?? 0) === 0) {
        if ($method === 'POST' && ($_POST['action'] ?? '') === 'student_save_profile') {
            (new StudentController())->saveProfile();
        }
        (new StudentController())->profileForm();
        exit;
    }
}

if ($method === 'POST') {
    $pathActionMap = [
        'notifications/mark-all-read' => 'mark_all_notifications_read',
        'admin/coordinators' => 'admin_create_coordinator',
        'admin/partners' => 'admin_create_company',
        'admin/partners/programs' => 'admin_update_company_programs',
        'admin/partners/resend-credentials' => 'admin_resend_company_credentials',
        'admin/users/reset-credentials' => 'admin_reset_user_credentials',
        'admin/users/toggle' => 'admin_toggle_user',
        'admin/programs' => 'admin_save_program',
        'admin/programs/terms' => 'admin_save_term',
        'admin/programs/terms/delete' => 'admin_delete_term',
        'admin/programs/delete' => 'admin_delete_program',
        'coordinator/students' => 'coordinator_create_student',
        'coordinator/enrollments' => 'coordinator_enroll_student',
        'coordinator/requirements/review' => 'coordinator_review_requirement',
        'coordinator/students/reset-password' => 'coordinator_reset_password',
        'coordinator/students/update-email' => 'coordinator_update_student_email',
        'coordinator/deployments/forward' => 'coordinator_forward_deployment',
        'student/profile' => 'student_save_profile',
        'student/change-password' => 'student_change_password',
        'student/requirements/upload' => 'student_upload_requirement',
        'student/requirements/upload-bulk' => 'student_upload_requirements_bulk',
        'student/requirements/submit' => 'student_submit_requirements',
        'student/dtr-draft' => 'student_save_dtr_draft',
        'student/dtr' => 'student_add_dtr',
        'student/weekly-reports' => 'student_add_weekly',
        'student/reports/upload' => 'student_upload_report',
        'partner/deployments/accept' => 'partner_accept_deployment',
        'partner/orientation/email' => 'partner_send_orientation_email',
        'partner/orientation/schedule' => 'partner_schedule_orientation',
        'partner/orientation/complete' => 'partner_complete_orientation',
        'partner/evaluations' => 'partner_submit_evaluation',
    ];
    $action = $_POST['action'] ?? ($pathActionMap[$path] ?? '');
    match ($action) {
        'mark_all_notifications_read' => (function (): void {
            verify_csrf();
            (new Notification(db()))->markAllRead((int)current_user()['id']);
            flash('success', 'Notifications marked as read.');
            redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
        })(),
        'admin_create_coordinator' => (new AdminController())->createCoordinator(),
        'admin_create_company' => (new AdminController())->createCompany(),
        'admin_update_company_programs' => (new AdminController())->updateCompanyPrograms(),
        'admin_resend_company_credentials' => (new AdminController())->resendCompanyCredentials(),
        'admin_reset_user_credentials' => (new AdminController())->resetUserCredentials(),
        'admin_save_program' => (new AdminController())->saveProgram(),
        'admin_save_term' => (new AdminController())->saveTerm(),
        'admin_delete_term' => (new AdminController())->deleteTerm(),
        'admin_delete_program' => (new AdminController())->deleteProgram(),
        'admin_toggle_user' => (new AdminController())->toggleUser(),
        'coordinator_create_student' => (new CoordinatorController())->createStudent(),
        'coordinator_enroll_student' => (new CoordinatorController())->enrollStudent(),
        'coordinator_review_requirement' => (new CoordinatorController())->reviewRequirement(),
        'coordinator_reset_password' => (new CoordinatorController())->resetStudentPassword(),
        'coordinator_update_student_email' => (new CoordinatorController())->updateStudentEmail(),
        'student_change_password' => (new StudentController())->changePassword(),
        'student_save_profile' => (new StudentController())->saveProfile(),
        'student_upload_requirement' => (new StudentController())->uploadRequirement(),
        'student_upload_requirements_bulk' => (new StudentController())->uploadRequirementsBulk(),
        'student_submit_requirements' => (new StudentController())->submitRequirements(),
        'student_save_dtr_draft' => (new StudentController())->saveDtrDraft(),
        'coordinator_forward_deployment' => (new CoordinatorController())->forwardDeployment(),
        'partner_accept_deployment' => (new PartnerController())->acceptDeployment(),
        'partner_send_orientation_email' => (new PartnerController())->sendOrientationEmail(),
        'partner_schedule_orientation' => (new PartnerController())->scheduleOrientation(),
        'partner_complete_orientation' => (new PartnerController())->completeOrientation(),
        'student_add_dtr' => (new StudentController())->addDtr(),
        'student_add_weekly' => (new StudentController())->addWeekly(),
        'student_upload_report' => (new StudentController())->addWeekly(),
        'partner_submit_evaluation' => (new PartnerController())->submitEvaluation(),
        default => exit('Unknown action'),
    };
}

match ($route) {
    'admin' => (new AdminController())->dashboard(),
    'admin_users' => (new AdminController())->manageUsers(),
    'admin_coordinators' => (new AdminController())->manageCoordinators(),
    'admin_partners' => (new AdminController())->managePartners(),
    'admin_partner_document' => (new AdminController())->viewPartnerDocument(),
    'admin_programs' => (new AdminController())->managePrograms(),
    'admin_email_logs' => (new AdminController())->emailLogs(),
    'admin_evaluations' => (new AdminController())->evaluations(),
    'coordinator' => (new CoordinatorController())->dashboard(),
    'coordinator_manage' => (new CoordinatorController())->manage(),
    'coordinator_students' => (new CoordinatorController())->myStudents(),
    'coordinator_moa_mou' => (new CoordinatorController())->moaMouLibrary(),
    'coordinator_partner_document' => (new CoordinatorController())->viewPartnerDocument(),
    'coordinator_evaluations' => (new CoordinatorController())->evaluations(),
    'student' => (new StudentController())->dashboard(),
    'student_portal' => (new StudentController())->documents(),
    'student_records' => (new StudentController())->records(),
    'student_timeline' => (new StudentController())->timeline(),
    'student_documents' => (new StudentController())->documents(),
    'student_settings' => (new StudentController())->settings(),
    'student_profile' => (new StudentController())->profileForm(),
    'student_password' => (new StudentController())->changePasswordForm(),
    'partner' => (new PartnerController())->dashboard(),
    'partner_portal' => (new PartnerController())->portal(),
    default => redirect('index.php?r=' . current_user()['role']),
};
