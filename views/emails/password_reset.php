<!doctype html>
<html>
<body style="font-family:Arial,sans-serif;background:#f4f6fb;padding:24px;color:#1f2937">
    <div style="max-width:640px;margin:auto;background:white;border-radius:16px;padding:28px;border:1px solid #e5e7eb">
        <h2 style="color:#8B1A1A;margin-top:0">AMA OJT Password Reset</h2>
        <p>Dear <?= e($student['name'] ?? 'Student') ?>,</p>
        <p>Your OJT portal password has been reset by your OJT Coordinator.</p>
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:16px;margin:18px 0">
            <p style="margin:0 0 8px"><strong>USN:</strong> <?= e($student['student_no'] ?? '') ?></p>
            <p style="margin:0 0 8px"><strong>Email:</strong> <?= e($student['email'] ?? '') ?></p>
            <p style="margin:0"><strong>Temporary Password:</strong> <?= e((string)($password ?? '')) ?></p>
        </div>
        <p><strong>Login Portal:</strong> <a href="<?= e($loginUrl ?? absolute_route_url('student.login')) ?>"><?= e($loginUrl ?? absolute_route_url('student.login')) ?></a></p>
        <p><a href="<?= e($loginUrl ?? absolute_route_url('student.login')) ?>" style="background:#8B1A1A;color:white;text-decoration:none;padding:12px 18px;border-radius:8px;display:inline-block">Open Student Login Portal</a></p>
        <p>Please sign in using the temporary password and change it immediately.</p>
        <p style="margin-bottom:0">Signed,<br><?= e($coordinator['name'] ?? 'OJT Coordinator') ?><br>OJT Coordinator</p>
    </div>
</body>
</html>