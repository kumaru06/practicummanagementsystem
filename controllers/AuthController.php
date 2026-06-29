<?php
class AuthController extends BaseController
{
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
            $user = (new User($this->db))->findForLogin($identifier, $portalRole);
            if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
                if (($user['role'] ?? '') !== $portalRole) {
                    $targetPortal = $this->portalViewData((string)$user['role']);
                    flash('error', 'Invalid login portal for this account. Please use the ' . $targetPortal['portalLabel'] . '.');
                    redirect('auth.php?portal=' . urlencode($portalRole));
                }
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (int)$user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'password_changed' => (int)($user['password_changed'] ?? 1),
                ];
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['role'] = (string)$user['role'];
                redirect(route_for_role($user['role']));
            }
            flash('error', 'Invalid credentials or inactive account.');
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
            'partner' => ['label' => 'Industry Partner Login Portal', 'route' => route_url('partner.login')],
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
    }
}
