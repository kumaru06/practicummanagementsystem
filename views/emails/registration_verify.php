<!doctype html>
<html>
<body style="font-family:Arial,sans-serif;background:#f4f6fb;padding:24px;color:#1f2937">
    <div style="max-width:640px;margin:auto;background:white;border-radius:16px;padding:28px;border:1px solid #e5e7eb">
        <h2 style="color:#8B1A1A;margin-top:0">Verify Your Student Registration</h2>
        <p>Hello <?= e($firstName ?? 'Student') ?>,</p>
        <p>Thank you for registering with the AMA OJT Practicum Portal. Please confirm your email address to continue.</p>
        <p style="margin:24px 0">
            <a href="<?= e($verifyUrl ?? '') ?>" style="background:#8B1A1A;color:white;text-decoration:none;padding:12px 18px;border-radius:8px;display:inline-block">Verify Email Address</a>
        </p>
        <p style="word-break:break-all"><strong>Verification link:</strong><br><?= e($verifyUrl ?? '') ?></p>
        <p>This link expires in <?= (int)($expiresHours ?? 12) ?> hours. If it expires, sign in to the student portal with your registration password to open the verification page and resend a new link.</p>
        <p>After verifying your email, you can sign in while waiting for administrator approval. Full dashboard access is granted only after an administrator approves your registration.</p>
        <p style="margin-bottom:0">If you did not request this registration, you can safely ignore this email.</p>
    </div>
</body>
</html>
