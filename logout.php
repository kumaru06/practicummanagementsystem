<?php
require_once __DIR__ . '/init.php';

$userId = (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
if ($userId > 0) {
    try {
        (new User(db()))->recordLogout($userId);
    } catch (Throwable) {
        // Still allow logout even if activity logging fails.
    }
}

$_SESSION = [];
session_destroy();
redirect('auth.php');
