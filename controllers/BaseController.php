<?php
abstract class BaseController
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $user = current_user();
        $notifications = [];
        $unreadNotifications = 0;
        $studentProfileCompleted = true;
        $studentRecord = null;
        if ($user) {
            try {
                $notificationModel = new Notification($this->db);
                $notifications = $notificationModel->recentForUser((int)$user['id']);
                $unreadNotifications = $notificationModel->unreadCount((int)$user['id']);
                if (($user['role'] ?? '') === 'student') {
                    $studentRecord = (new Student($this->db))->findByUser((int)$user['id']);
                    $studentProfileCompleted = !$studentRecord || (int)($studentRecord['profile_completed'] ?? 0) === 1;
                }
            } catch (Throwable) {
                $notifications = [];
                $unreadNotifications = 0;
            }
        }
        require __DIR__ . '/../views/shared/header.php';
        require __DIR__ . '/../views/' . $view . '.php';
        require __DIR__ . '/../views/shared/footer.php';
    }

    protected function post(): array
    {
        verify_csrf();
        return $_POST;
    }

    protected function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function renderPartial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../views/' . $view . '.php';
    }
}
