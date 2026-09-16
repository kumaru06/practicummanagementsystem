<?php
require_once __DIR__ . '/init.php';

// Logout is a state-changing action: require POST + CSRF to prevent logout-CSRF.
// A GET renders a small confirmation page with a POST form (no JS dependency).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Session may already have expired (idle timeout); still allow a clean sign-out.
    if (current_user()) {
        verify_csrf();
    }
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
}

// Nothing to confirm if there is no active session.
if (!current_user()) {
    redirect('auth.php');
}

$csrf = csrf_token();
$backUrl = route_for_role(current_user()['role'] ?? null);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Log out</title>
<style>
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc;color:#0f172a;font-family:"Segoe UI",system-ui,-apple-system,sans-serif;padding:24px}
  .box{max-width:420px;width:100%;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:28px 26px;box-shadow:0 18px 40px rgba(15,23,42,.08);text-align:center}
  h1{margin:0 0 8px;font-size:1.25rem}
  p{margin:0 0 20px;color:#475569;line-height:1.5}
  .row{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
  button,a.btn{font:inherit;font-weight:600;border-radius:10px;padding:10px 18px;cursor:pointer;text-decoration:none;display:inline-block}
  .danger{background:#9f1239;color:#fff;border:none}
  .danger:hover{background:#881337}
  .ghost{background:#fff;color:#0f172a;border:1px solid #cbd5e1}
  .ghost:hover{background:#f1f5f9}
</style>
</head>
<body>
  <div class="box">
    <h1>Log out of your account?</h1>
    <p>You will need to sign in again to continue.</p>
    <div class="row">
      <form method="post" action="logout.php" style="margin:0">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <button type="submit" class="danger">Yes, log out</button>
      </form>
      <a class="btn ghost" href="<?= e($backUrl) ?>">Stay signed in</a>
    </div>
  </div>
</body>
</html>
