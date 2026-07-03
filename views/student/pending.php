<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Account Pending Approval - AMA Practicum System</title>
    <link rel="icon" type="image/jpeg" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260703-pending">
    <link rel="stylesheet" href="<?= e(asset('assets/css/login.css')) ?>?v=20260703-pending">
</head>
<body class="student-pending-page">
    <main class="student-pending-shell">
        <div class="student-pending-card">
            <img src="<?= e(asset('assets/image/main/logo/amalogo.png')) ?>" alt="AMA Logo" class="student-pending-logo">
            <h1>Account Pending Approval</h1>
            <p class="student-pending-lead">You don't have access to the student dashboard yet.</p>
            <p>Your registration has been submitted and your email address is verified. An administrator is still reviewing your application.</p>
            <p class="student-pending-note">You will receive full access once your registration is approved. If you have questions, please contact the OJT office.</p>
            <?php if ($success = flash('success')): ?>
                <div class="alert success student-pending-alert"><?= e($success) ?></div>
            <?php endif; ?>
            <a class="btn btn-primary student-pending-logout" href="logout.php">Log Out</a>
        </div>
    </main>
</body>
</html>
