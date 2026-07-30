<?php
$headerInitial = strtoupper(substr($user['name'] ?? 'A', 0, 1));
$headerPhotoSource = match ($user['role'] ?? '') {
    'student' => ($student ?? null) ?: ($studentRecord ?? null),
    'partner' => ($company ?? null) ?: ($partnerRecord ?? null),
    default => null,
};
$headerPhotoUrl = match ($user['role'] ?? '') {
    'student' => student_profile_photo_url($headerPhotoSource),
    'partner' => partner_profile_photo_url($headerPhotoSource),
    default => '',
};
$profileRoute = match ($user['role'] ?? '') {
    'student' => route_url('student.profile'),
    'partner' => route_url('partner.profile'),
    default => '',
};
$studentProfileRoute = ($user['role'] ?? '') === 'student' ? $profileRoute : '';
$studentNavLocked = false;
$studentNavReveal = false;
if (($user['role'] ?? '') === 'student') {
    $studentNavLocked = (int)($user['password_changed'] ?? 1) === 0 || empty($studentProfileCompleted);
    if (!$studentNavLocked && !empty($_SESSION['student_nav_reveal'])) {
        $studentNavReveal = true;
        unset($_SESSION['student_nav_reveal']);
    }
}
$sidebarCollapsed = !$studentNavLocked && isset($_COOKIE['sidebarCollapsed']) && $_COOKIE['sidebarCollapsed'] === '1';
?>
<!doctype html>
<html lang="en"<?= $sidebarCollapsed ? ' class="is-sidebar-collapsed-init"' : '' ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'AMA Practicum System') ?></title>
    <link rel="icon" type="image/jpeg" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
    try {
        if (localStorage.getItem('sidebarCollapsed') === '1' || document.documentElement.classList.contains('is-sidebar-collapsed-init')) {
            if (window.matchMedia('(min-width: 721px)').matches) {
                document.documentElement.classList.add('is-sidebar-collapsed-init');
            }
            localStorage.setItem('sidebarCollapsed', '1');
        }
    } catch (e) {}
    </script>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>&t=<?= time() ?>">
</head>
<body class="app-page role-<?= e($user['role'] ?? 'guest') ?><?= $sidebarCollapsed ? ' sidebar-collapsed' : '' ?><?= $studentNavLocked ? ' student-onboarding' : '' ?><?= $studentNavReveal ? ' student-nav-reveal' : '' ?>" data-app-base="<?= e(app_base_path()) ?>" data-sidebar-collapsed="<?= $sidebarCollapsed ? '1' : '0' ?>" data-student-nav-reveal="<?= $studentNavReveal ? '1' : '0' ?>">
<script>try{if((localStorage.getItem('sidebarCollapsed')==='1'||document.body.dataset.sidebarCollapsed==='1')&&window.matchMedia('(min-width: 721px)').matches){document.body.classList.add('sidebar-collapsed');localStorage.setItem('sidebarCollapsed','1');}}catch(e){}</script>
<div class="app-shell">
    <aside class="sidebar<?= $studentNavLocked ? ' sidebar--onboarding' : '' ?><?= $studentNavReveal ? ' sidebar--nav-reveal' : '' ?>">
        <button class="sidebar-toggle" type="button" aria-label="Collapse sidebar"><svg viewBox="0 0 24 24"><path d="M15.4 7.4 14 6l-6 6 6 6 1.4-1.4L10.8 12l4.6-4.6ZM20 4h-2v16h2V4Z"/></svg></button>
        <div class="brand">
            <span class="brand-mark">
                <img src="<?= e(asset('assets/image/main/image.png')) ?>" alt="AMA Computer College logo" width="36" height="36">
            </span>
            <div><strong data-marquee>AMA Computer College</strong><small data-marquee>OJT Management</small></div>
        </div>
        <nav class="nav">
            <?php
            $role = $user['role'] ?? '';
            $homeRoute = $role ?: 'admin';
            $currentRoute = $_GET['r'] ?? $homeRoute;
            $rawPageSubtitle = in_array($role, ['admin', 'coordinator'], true)
                ? ''
                : ($role === 'partner' ? 'Host Training Establishment' : ucwords(str_replace('_', ' ', $role ?: 'dashboard')));
            $pageTitle = $title ?? 'Dashboard';
            $pageSubtitle = '';
            if ($rawPageSubtitle !== '' && stripos($pageTitle, $rawPageSubtitle) === false) {
                $pageSubtitle = $rawPageSubtitle;
            }
            ?>
            <a class="nav-link <?= in_array($currentRoute, ['admin', 'coordinator', 'student', 'partner'], true) ? 'active' : '' ?>" href="index.php?r=<?= e($homeRoute) ?>"><svg viewBox="0 0 24 24"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/></svg><?php if ($role === 'student'): ?><span class="nav-link-label nav-link-label--full">Dashboard</span><span class="nav-link-label nav-link-label--short" aria-hidden="true">Home</span><?php else: ?><span>Dashboard</span><?php endif; ?></a>
            <?php if ($role === 'admin'):
                $userRoutes = ['admin_users', 'admin_registration_requests', 'admin_password_reset_requests', 'admin_coordinators', 'admin_partners'];
                $userGroupOpen = in_array($currentRoute, $userRoutes, true);
            ?><div class="nav-group <?= $userGroupOpen ? 'open' : '' ?>">
                <button class="nav-group-toggle" type="button">
                    <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3Zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3Zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5Zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5Z"/></svg>
                    <span>Manage Users</span>
                    <svg class="chevron" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
                </button>
                <div class="nav-group-items">
                    <a class="nav-link nav-sub <?= $currentRoute === 'admin_users' ? 'active' : '' ?>" href="index.php?r=admin_users"><svg viewBox="0 0 24 24"><path d="M12 3 2 8l10 5 8-4v6h2V8L12 3Zm-6 9v4c2 3 10 3 12 0v-4l-6 3-6-3Z"/></svg><span>Students</span></a>
                    <a class="nav-link nav-sub <?= $currentRoute === 'admin_coordinators' ? 'active' : '' ?>" href="index.php?r=admin_coordinators"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg><span>Coordinators</span></a>
                    <a class="nav-link nav-sub <?= $currentRoute === 'admin_partners' ? 'active' : '' ?>" href="index.php?r=admin_partners"><svg viewBox="0 0 24 24"><path d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg><span>Host Training Establishments</span></a>
                    <a class="nav-link nav-sub <?= $currentRoute === 'admin_registration_requests' ? 'active' : '' ?>" href="index.php?r=admin_registration_requests"><svg viewBox="0 0 24 24"><path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm-8 2h6v2h-6V5ZM7 7h10v2H7V7Zm0 4h10v2H7v-2Zm0 4h7v2H7v-2Z"/></svg><span>Student Account Requests</span></a>
                    <a class="nav-link nav-sub <?= $currentRoute === 'admin_password_reset_requests' ? 'active' : '' ?>" href="index.php?r=admin_password_reset_requests"><svg viewBox="0 0 24 24"><path d="M12 2a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h1V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v3h6V7a3 3 0 0 0-3-3Zm0 9a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/></svg><span>Password Reset Requests</span></a>
                </div>
            </div><?php endif; ?>
            <?php if ($role === 'admin'): ?><a class="nav-link <?= $currentRoute === 'admin_email_logs' ? 'active' : '' ?>" href="index.php?r=admin_email_logs"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z"/></svg><span>Email Logs</span></a><a class="nav-link <?= $currentRoute === 'admin_evaluations' ? 'active' : '' ?>" href="index.php?r=admin_evaluations"><svg viewBox="0 0 24 24"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"/></svg><span>Evaluations</span></a><a class="nav-link <?= $currentRoute === 'admin_ojt_placement' ? 'active' : '' ?>" href="index.php?r=admin_ojt_placement"><svg viewBox="0 0 24 24"><path d="M10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 0 1 1 1v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a1 1 0 0 1 1-1h3V4a2 2 0 0 1 2-2Zm2 4V4h-2v2h2Zm-1 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg><span>OJT Placement</span></a><a class="nav-link <?= $currentRoute === 'admin_programs' ? 'active' : '' ?>" href="index.php?r=admin_programs"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4V5Zm2 2v10h12V7H6Zm2 2h8v2H8V9Zm0 4h6v2H8v-2Z"/></svg><span>Degree Program</span></a><a class="nav-link <?= $currentRoute === 'admin_terms' ? 'active' : '' ?>" href="index.php?r=admin_terms"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg><span>Academic Term</span></a><a class="nav-link <?= in_array($currentRoute, ['admin_reports', 'admin_report'], true) ? 'active' : '' ?>" href="index.php?r=admin_reports"><svg viewBox="0 0 24 24"><path d="M5 3h9l5 5v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm8 1.5V8h3.5L13 4.5ZM8 13h2v5H8v-5Zm3.5-3h2v8h-2v-8ZM15 15h2v3h-2v-3Z"/></svg><span>Reports</span></a><a class="nav-link <?= $currentRoute === 'admin_recent_activities' ? 'active' : '' ?>" href="index.php?r=admin_recent_activities"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="fill:none"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg><span>Recent Activities</span></a><a class="nav-link <?= $currentRoute === 'chat' ? 'active' : '' ?>" href="index.php?r=chat"><svg viewBox="0 0 24 24"><path d="M4 5h16v10H7l-3 3V5Z"/></svg><span>Live Chat</span></a><?php endif; ?>
            <?php if ($role === 'coordinator'): ?><a class="nav-link <?= $currentRoute === 'coordinator_manage' ? 'active' : '' ?>" href="index.php?r=coordinator_manage"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg><span>Student Enrollment</span></a><a class="nav-link <?= $currentRoute === 'coordinator_students' ? 'active' : '' ?>" href="index.php?r=coordinator_students"><svg viewBox="0 0 24 24"><path d="M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h16v2H4v-2Z"/></svg><span>My Students</span></a><a class="nav-link <?= $currentRoute === 'coordinator_moa_mou' ? 'active' : '' ?>" href="index.php?r=coordinator_moa_mou"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h5v2H8V9Z"/></svg><span>MOA/MOU</span></a><a class="nav-link <?= $currentRoute === 'coordinator_evaluations' ? 'active' : '' ?>" href="index.php?r=coordinator_evaluations"><svg viewBox="0 0 24 24"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"/></svg><span>Evaluations</span></a><a class="nav-link <?= $currentRoute === 'chat' ? 'active' : '' ?>" href="index.php?r=chat"><svg viewBox="0 0 24 24"><path d="M4 5h16v10H7l-3 3V5Z"/></svg><span>Live Chat</span></a><?php endif; ?>
            <?php if ($role === 'student'): ?>
                <a class="nav-link <?= $currentRoute === 'student_records' ? 'active' : '' ?>" href="index.php?r=student_records"><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm0 16H5V9h14v10Zm-9-8H7v3h3v3h3v-3h3v-3h-3V8h-3v3Z"/></svg><span class="nav-link-label nav-link-label--full">Submit Record</span><span class="nav-link-label nav-link-label--short" aria-hidden="true">Record</span></a>
                <a class="nav-link <?= $currentRoute === 'student_reports' ? 'active' : '' ?>" href="index.php?r=student_reports"><svg viewBox="0 0 24 24"><path d="M5 3h9l5 5v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm8 1.5V8h3.5L13 4.5ZM8 13h2v5H8v-5Zm3.5-3h2v8h-2v-8ZM15 15h2v3h-2v-3Z"/></svg><span class="nav-link-label nav-link-label--full">Reports</span><span class="nav-link-label nav-link-label--short" aria-hidden="true">Reports</span></a>
                <a class="nav-link <?= $currentRoute === 'student_timeline' ? 'active' : '' ?>" href="index.php?r=student_timeline"><svg viewBox="0 0 24 24"><path d="M7 3a2 2 0 0 1 2 2v1h6V5a2 2 0 1 1 4 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm0 5v11h10V8H7Zm2 2h6v2H9v-2Zm0 4h4v2H9v-2Z"/></svg><span class="nav-link-label nav-link-label--full">Activity Timeline</span><span class="nav-link-label nav-link-label--short" aria-hidden="true">Timeline</span></a>
<?php
                $docRoutes = ['student_documents', 'student_documents_other'];
                $docGroupOpen = in_array($currentRoute, $docRoutes, true);
                $docStage = $currentRoute === 'student_documents' ? (string)($_GET['stage'] ?? '1') : '';
                $headerStudent = $student ?? $studentRecord ?? null;
                $docStageAccess = $headerStudent
                    ? student_document_stage_access((int)$headerStudent['id'])
                    : [1 => true, 2 => false, 3 => false];
                $docStageLinks = [
                    1 => ['label' => '1st to Comply', 'href' => 'index.php?r=student_documents&stage=1', 'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm-3 15-3-3 1.4-1.4L11 14.2l4.6-4.6L17 11l-6 6Z"/></svg>'],
                    2 => ['label' => '2nd to Comply', 'href' => 'index.php?r=student_documents&stage=2', 'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm-9 4h7v2h-7V8Zm0 4h7v2h-7v-2ZM6 8h3v3H6V8Zm0 5h3v3H6v-3Z"/></svg>'],
                    3 => ['label' => '3rd to Comply', 'href' => 'index.php?r=student_documents&stage=3', 'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm-7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm-2 14-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8Z"/></svg>'],
                ];
                ?>
                <div class="nav-group nav-group--student-docs <?= $docGroupOpen ? 'nav-group--active' : '' ?>">
                    <button class="nav-group-toggle" type="button" aria-expanded="false" aria-haspopup="true">
                        <svg viewBox="0 0 24 24"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V8h4.5L14 3.5ZM9 12h6v2H9v-2Zm0 4h6v2H9v-2Z"/></svg>
                        <span class="nav-link-label nav-link-label--full">Documents</span>
                        <span class="nav-link-label nav-link-label--short" aria-hidden="true">Docs</span>
                        <svg class="chevron" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
                    </button>
                    <div class="nav-group-items" role="menu">
                        <?php foreach ($docStageLinks as $stageNo => $docLink): ?>
                            <?php
                                $stageOpen = !empty($docStageAccess[$stageNo]);
                                $stageActive = $currentRoute === 'student_documents' && $docStage === (string)$stageNo;
                            ?>
                            <?php if ($stageOpen): ?>
                                <a class="nav-link nav-sub student-docs-sheet-item<?= $stageActive ? ' active' : '' ?>" href="<?= e($docLink['href']) ?>" role="menuitem"><?= $docLink['icon'] ?><span><?= e($docLink['label']) ?></span></a>
                            <?php else: ?>
                                <span class="nav-link nav-sub student-docs-sheet-item is-locked" role="menuitem" aria-disabled="true" title="Stage locked"><?= $docLink['icon'] ?><span><?= e($docLink['label']) ?></span></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <a class="nav-link nav-sub student-docs-sheet-item <?= $currentRoute === 'student_documents_other' ? 'active' : '' ?>" href="index.php?r=student_documents_other" role="menuitem"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 4h7l2 2h7v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg><span>Other Documents</span></a>
                    </div>
                </div>
                <a class="nav-link <?= $currentRoute === 'student_evaluation' ? 'active' : '' ?>" href="index.php?r=student_evaluation"><svg viewBox="0 0 24 24"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"/></svg><span class="nav-link-label nav-link-label--full">Evaluation</span><span class="nav-link-label nav-link-label--short" aria-hidden="true">Eval</span></a>
                <a class="nav-link <?= $currentRoute === 'chat' ? 'active' : '' ?>" href="index.php?r=chat"><svg viewBox="0 0 24 24"><path d="M4 5h16v10H7l-3 3V5Z"/></svg><span class="nav-link-label nav-link-label--full">Live Chat</span><span class="nav-link-label nav-link-label--short" aria-hidden="true">Chat</span></a>
                <a class="nav-link <?= in_array($currentRoute, ['student_settings', 'student_password', 'student_profile'], true) ? 'active' : '' ?>" href="index.php?r=student_settings"><svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.03 7.03 0 0 0-1.63-.94l-.36-2.54A.5.5 0 0 0 13.9 2h-3.8a.5.5 0 0 0-.49.42l-.36 2.54c-.58.23-1.12.54-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.31 8.48a.5.5 0 0 0 .12.64l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58a.5.5 0 0 0-.12.64l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96c.51.4 1.05.71 1.63.94l.36 2.54a.5.5 0 0 0 .49.42h3.8a.5.5 0 0 0 .49-.42l.36-2.54c.58-.23 1.12-.54 1.63-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58ZM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5Z"/></svg><span class="nav-link-label nav-link-label--full">Settings</span><span class="nav-link-label nav-link-label--short" aria-hidden="true">Setup</span></a>
            <?php endif; ?>
            <?php if ($role === 'partner'): ?>
                <a class="nav-link <?= $currentRoute === 'partner_portal' ? 'active' : '' ?>" href="index.php?r=partner_portal"><svg viewBox="0 0 24 24"><path d="M3 21V7l6-4 6 4v14h-4v-5H7v5H3Zm14 0V9h4v12h-4ZM7 9h4v2H7V9Zm0 4h4v2H7v-2Z"/></svg><span>Host Training Establishment Portal</span></a>
                <a class="nav-link <?= $currentRoute === 'partner_submissions' ? 'active' : '' ?>" href="index.php?r=partner_submissions"><svg viewBox="0 0 24 24"><path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm-9 14-4-4 1.4-1.4 2.6 2.6 5.6-5.6L17 10l-7 7Z"/></svg><span>Student Submissions</span></a>
                <a class="nav-link <?= $currentRoute === 'chat' ? 'active' : '' ?>" href="index.php?r=chat"><svg viewBox="0 0 24 24"><path d="M4 5h16v10H7l-3 3V5Z"/></svg><span>Live Chat</span></a>
                <a class="nav-link <?= in_array($currentRoute, ['partner_settings', 'partner_password', 'partner_profile'], true) ? 'active' : '' ?>" href="index.php?r=partner_settings"><svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.03 7.03 0 0 0-1.63-.94l-.36-2.54A.5.5 0 0 0 13.9 2h-3.8a.5.5 0 0 0-.49.42l-.36 2.54c-.58.23-1.12.54-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.31 8.48a.5.5 0 0 0 .12.64l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58a.5.5 0 0 0-.12.64l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96c.51.4 1.05.71 1.63.94l.36 2.54a.5.5 0 0 0 .49.42h3.8a.5.5 0 0 0 .49-.42l.36-2.54c.58-.23 1.12-.54 1.63-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58ZM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5Z"/></svg><span>Settings</span></a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-user">
            <?php if ($profileRoute !== ''): ?>
                <a class="sidebar-user-info app-user-identity app-user-identity--sidebar sidebar-user-link" href="<?= e($profileRoute) ?>" aria-label="Open my profile">
            <?php else: ?>
                <div class="sidebar-user-info app-user-identity app-user-identity--sidebar">
            <?php endif; ?>
                <?php if ($headerPhotoUrl !== ''): ?>
                    <span class="app-user-identity__avatar app-user-identity__avatar--photo"><img src="<?= e($headerPhotoUrl) ?>" alt="<?= e($user['name'] ?? 'User') ?> profile photo"></span>
                <?php else: ?>
                    <span class="app-user-identity__avatar"><?= e($headerInitial) ?></span>
                <?php endif; ?>
                <div class="app-user-identity__meta">
                    <strong data-marquee><?= e($user['name'] ?? '') ?></strong>
                    <small data-marquee><?= e(($user['role'] ?? '') === 'student' ? ($user['email'] ?? '') : (($user['role'] ?? '') === 'partner' ? 'Host Training Establishment' : ucwords(str_replace('_', ' ', $user['role'] ?? '')))) ?></small>
                </div>
            <?php if ($profileRoute !== ''): ?>
                </a>
            <?php else: ?>
                </div>
            <?php endif; ?>
            <a class="nav-link sidebar-logout" href="logout.php" data-confirm="Are you sure you want to log out?" data-confirm-title="Log out of your account" data-confirm-ok="Yes, log out" data-confirm-cancel="Stay signed in">
                <svg viewBox="0 0 24 24"><path d="M16 13v-2H7V8l-5 4 5 4v-3h9Zm1-9H9a2 2 0 0 0-2 2v3h2V6h8v12H9v-3H7v3a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Z"/></svg>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="topbar-leading">
                <div class="topbar-copy">
                    <?php if ($pageSubtitle !== ''): ?><span class="topbar-eyebrow"><?= e($pageSubtitle) ?></span><?php endif; ?>
                    <h1><?= e($pageTitle) ?></h1>
                </div>
            </div>
            <div class="top-actions">
                <div class="topbar-toolbar">
                    <div class="notification-menu" id="notifMenu">
                        <button class="notif-trigger" id="notifBtn" type="button" aria-label="Notifications" aria-controls="notifPanel" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            <?php if (($unreadNotifications ?? 0) > 0): ?><span class="notif-badge"><?= (int)$unreadNotifications ?></span><?php endif; ?>
                        </button>
                    </div>
                    <span class="topbar-toolbar-divider" aria-hidden="true"></span>
                    <?php if ($profileRoute !== ''): ?>
                        <a class="user-chip app-user-identity app-user-identity--chip user-chip-link" href="<?= e($profileRoute) ?>" aria-label="Open my profile">
                    <?php else: ?>
                        <div class="user-chip app-user-identity app-user-identity--chip">
                    <?php endif; ?>
                        <?php if ($headerPhotoUrl !== ''): ?>
                            <span class="app-user-identity__avatar app-user-identity__avatar--photo"><img src="<?= e($headerPhotoUrl) ?>" alt="<?= e($user['name'] ?? 'User') ?> profile photo"></span>
                        <?php else: ?>
                            <span class="app-user-identity__avatar"><?= e($headerInitial) ?></span>
                        <?php endif; ?>
                        <div class="app-user-identity__meta">
                            <strong data-marquee><?= e($user['name'] ?? '') ?></strong>
                            <small data-marquee><?= e($user['email'] ?? '') ?></small>
                        </div>
                        <?php if ($profileRoute !== ''): ?>
                            <svg class="user-chip-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                        <?php endif; ?>
                    <?php if ($profileRoute !== ''): ?>
                        </a>
                    <?php else: ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <div class="notif-panel" id="notifPanel" role="dialog" aria-label="Notifications" hidden>
            <div class="notif-panel-header">
                <span class="notif-panel-title">Notifications</span>
                <span class="notif-panel-gear" title="Notification settings">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
            </div>
            <?php if (empty($notifications ?? [])): ?>
                <div class="notif-empty">
                    <div class="notif-empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <p>You're all caught up!</p>
                    <small>No new notifications.</small>
                </div>
            <?php else: ?>
                <div class="notif-list">
                    <?php foreach ($notifications as $note): ?>
                        <?php $isUnread = (int)$note['is_read'] === 0; ?>
                        <?php $initials = strtoupper(substr(strip_tags($note['title']), 0, 1)); ?>
                        <?php $noteLink = $note['link'] ?: 'index.php'; ?>
                        <a class="notif-item<?= $isUnread ? ' is-unread' : '' ?>" href="<?= e(route_url('dashboard', ['action' => 'read_notification', 'id' => (int)$note['id'], 'redirect' => $noteLink])) ?>">
                            <div class="notif-avatar" aria-hidden="true"><?= e($initials) ?></div>
                            <div class="notif-body">
                                <span class="notif-title"><?= e($note['title']) ?></span>
                                <span class="notif-msg"><?= e($note['message']) ?></span>
                                <time class="notif-time"><?= e($note['created_at']) ?></time>
                            </div>
                            <?php if ($isUnread): ?><span class="notif-dot" aria-label="Unread"></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="notif-footer">
                    <form method="post" action="index.php" class="notif-mark-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="mark_all_notifications_read">
                        <button type="submit" class="notif-mark-all">Mark all as read</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <section class="content">
            <div class="toast-stack" aria-live="polite">
                <?php if ($m = flash('success')): ?><div class="toast success"><?= e($m) ?></div><?php endif; ?>
                <?php if ($m = flash('error')): ?><div class="toast danger"><?= e($m) ?></div><?php endif; ?>
            </div>
