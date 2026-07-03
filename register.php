<?php
require_once __DIR__ . '/init.php';

$controller = new AuthController();
$action = $_GET['action'] ?? 'form';

if ($action === 'form' && trim((string)($_GET['token'] ?? '')) !== '') {
    $controller->verifyRegistrationEmail();
    exit;
}

match ($action) {
    'check_email' => $controller->checkRegistrationEmail(),
    'check_student_no' => $controller->checkRegistrationStudentNo(),
    'verify' => $controller->verifyRegistrationEmail(),
    default => $controller->registerStudent(),
};
