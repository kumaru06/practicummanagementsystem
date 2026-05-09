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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            if (!$portalRole) {
                flash('error', 'Please choose the correct login portal for your account.');
                redirect('auth.php');
            }
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $user = (new User($this->db))->findByEmail($email);
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
            'partner' => ['label' => 'Partner Company Login Portal', 'route' => route_url('partner.login')],
        ];
        $visible = array_filter($all, static fn ($key) => $key !== 'admin', ARRAY_FILTER_USE_KEY);

        return [
            'portalRole' => $role,
            'portalLabel' => $role && isset($all[$role]) ? $all[$role]['label'] : 'Choose Login Portal',
            'portals' => $visible,
        ];
    }
}
