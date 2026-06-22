<!doctype html>
<html>
<body style="font-family:Arial,sans-serif;background:#f4f6fb;padding:24px;color:#1f2937">
    <div style="max-width:640px;margin:auto;background:white;border-radius:16px;padding:28px;border:1px solid #e5e7eb">
        <h2 style="color:#8B1A1A;margin-top:0">AMA Practicum System Account</h2>
        <p>Hello <?= e($name ?? 'User') ?>,</p>
        <p>Your <?= e($roleLabel ?? 'account') ?> account credentials are below.</p>
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:16px;margin:18px 0">
            <?php if (!empty($usn ?? '')): ?><p style="margin:0 0 8px"><strong>USN:</strong> <?= e($usn) ?></p><?php endif; ?>
            <p style="margin:0 0 8px"><strong>Email:</strong> <?= e($email ?? '') ?></p>
            <p style="margin:0"><strong>Temporary Password:</strong> <?= e($password ?? '') ?></p>
        </div>
        <?php if (!empty($loginUrl ?? '')): ?>
            <p><a href="<?= e($loginUrl) ?>" style="background:#8B1A1A;color:white;text-decoration:none;padding:12px 18px;border-radius:8px;display:inline-block">Open Login Portal</a></p>
            <p><strong>Login URL:</strong> <a href="<?= e($loginUrl) ?>"><?= e($loginUrl) ?></a></p>
        <?php endif; ?>
        <p>Please log in and change your temporary password on first login.</p>
        <p style="margin-bottom:0">Thank you,<br>AMA Practicum System</p>
    </div>
</body>
</html>
