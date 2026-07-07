<!doctype html>
<html>
<body style="font-family:Arial,sans-serif;background:#f4f6fb;padding:24px;color:#1f2937">
    <div style="max-width:640px;margin:auto;background:white;border-radius:16px;padding:28px;border:1px solid #e5e7eb">
        <h2 style="color:#8B1A1A;margin-top:0">Reset Your Password</h2>
        <p>Hello <?= e($name ?? 'User') ?>,</p>
        <p>Your <?= e($roleLabel ?? 'account') ?> password reset request was approved by an administrator.</p>
        <p>Use the secure link below to create a new password. This link expires in <?= (int)($expiresHours ?? 1) ?> hour(s).</p>
        <p><a href="<?= e($resetUrl ?? '') ?>" style="background:#8B1A1A;color:white;text-decoration:none;padding:12px 18px;border-radius:8px;display:inline-block">Reset Password</a></p>
        <p><strong>Reset Link:</strong> <a href="<?= e($resetUrl ?? '') ?>"><?= e($resetUrl ?? '') ?></a></p>
        <?php if (!empty($loginUrl ?? '')): ?>
            <p>After resetting, sign in here: <a href="<?= e($loginUrl) ?>"><?= e($loginUrl) ?></a></p>
        <?php endif; ?>
        <p>If you did not request this reset, please contact your OJT administrator immediately.</p>
        <p style="margin-bottom:0">Thank you,<br>AMA Practicum System</p>
    </div>
</body>
</html>
