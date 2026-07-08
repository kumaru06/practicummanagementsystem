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
        $partnerRecord = null;
        if ($user) {
            try {
                $notificationModel = new Notification($this->db);
                $notifications = $notificationModel->recentForUser((int)$user['id']);
                $unreadNotifications = $notificationModel->unreadCount((int)$user['id']);
                if (($user['role'] ?? '') === 'student') {
                    $studentRecord = (new Student($this->db))->findByUser((int)$user['id']);
                    $studentProfileCompleted = !$studentRecord || (int)($studentRecord['profile_completed'] ?? 0) === 1;
                }
                if (($user['role'] ?? '') === 'partner') {
                    $partnerRecord = (new Company($this->db))->findByUser((int)$user['id']);
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
        return is_ajax_request();
    }

    protected function respondJson(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function renderPartial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../views/' . $view . '.php';
    }

    protected function renderAppPage(string $view, array $data = []): void
    {
        if ($this->isAjaxRequest() && ($_GET['partial'] ?? '') === 'content') {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            extract($data, EXTR_SKIP);
            $ajaxRoute = (string)($_GET['r'] ?? '');
            $ajaxTitle = (string)($data['title'] ?? 'Dashboard');
            ob_start();
            require __DIR__ . '/../views/' . $view . '.php';
            $pageHtml = ob_get_clean();
            require __DIR__ . '/../views/shared/partials/ajax-app-content.php';
            return;
        }

        $this->render($view, $data);
    }
}
