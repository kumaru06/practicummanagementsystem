<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'AMA Practicum System') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260511-partner-directory-refresh">
</head>
<body class="app-page role-<?= e($user['role'] ?? 'guest') ?>">
<div class="app-shell">
    <aside class="sidebar">
        <button class="sidebar-toggle" type="button" aria-label="Collapse sidebar"><svg viewBox="0 0 24 24"><path d="M15.4 7.4 14 6l-6 6 6 6 1.4-1.4L10.8 12l4.6-4.6ZM20 4h-2v16h2V4Z"/></svg></button>
        <div class="brand">
            <span class="brand-mark"><svg viewBox="0 0 24 24"><path d="M12 2 3 7v10l9 5 9-5V7l-9-5Zm0 3.2 5.8 3.2-5.8 3.2-5.8-3.2L12 5.2Zm-6 5.9 4.5 2.5v4.9L6 16v-4.9Zm12 0V16l-4.5 2.5v-4.9L18 11.1Z"/></svg></span>
            <div><strong>AMA Computer College</strong><small>OJT Management</small></div>
        </div>
        <nav class="nav">
            <?php
            $role = $user['role'] ?? '';
            $homeRoute = $role ?: 'admin';
            $currentRoute = $_GET['r'] ?? $homeRoute;
            $pageSubtitle = in_array($role, ['admin', 'coordinator'], true)
                ? ''
                : ucwords(str_replace('_', ' ', $role ?: 'dashboard'));
            ?>
            <a class="nav-link <?= in_array($currentRoute, ['admin', 'coordinator', 'student', 'partner'], true) ? 'active' : '' ?>" href="index.php?r=<?= e($homeRoute) ?>"><svg viewBox="0 0 24 24"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/></svg><span>Dashboard</span></a>
            <?php if ($role === 'admin'):
                $userRoutes = ['admin_users', 'admin_coordinators', 'admin_partners'];
                $userGroupOpen = in_array($currentRoute, $userRoutes, true);
            ?><div class="nav-group <?= $userGroupOpen ? 'open' : '' ?>">
                <button class="nav-group-toggle" type="button">
                    <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3Zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3Zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5Zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5Z"/></svg>
                    <span>Manage Users</span>
                    <svg class="chevron" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
                </button>
                <div class="nav-group-items">
                    <a class="nav-link nav-sub <?= $currentRoute === 'admin_users' ? 'active' : '' ?>" href="index.php?r=admin_users"><svg viewBox="0 0 24 24"><path d="M12 3 2 8l10 5 8-4v6h2V8L12 3Zm-6 9v4c2 3 10 3 12 0v-4l-6 3-6-3Z"/></svg><span>Manage Student</span></a>
                    <a class="nav-link nav-sub <?= $currentRoute === 'admin_coordinators' ? 'active' : '' ?>" href="index.php?r=admin_coordinators"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg><span>Manage Coordinators</span></a>
                    <a class="nav-link nav-sub <?= $currentRoute === 'admin_partners' ? 'active' : '' ?>" href="index.php?r=admin_partners"><svg viewBox="0 0 24 24"><path d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg><span>Manage Companies</span></a>
                </div>
            </div><?php endif; ?>
            <?php if ($role === 'admin'): ?><a class="nav-link <?= $currentRoute === 'admin_email_logs' ? 'active' : '' ?>" href="index.php?r=admin_email_logs"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z"/></svg><span>Email Logs</span></a><a class="nav-link <?= $currentRoute === 'admin_evaluations' ? 'active' : '' ?>" href="index.php?r=admin_evaluations"><svg viewBox="0 0 24 24"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"/></svg><span>Evaluations</span></a><a class="nav-link <?= $currentRoute === 'admin_programs' ? 'active' : '' ?>" href="index.php?r=admin_programs"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4V5Zm2 2v10h12V7H6Zm2 2h8v2H8V9Zm0 4h6v2H8v-2Z"/></svg><span>Programs / Courses</span></a><?php endif; ?>
            <?php if ($role === 'coordinator'): ?><a class="nav-link <?= $currentRoute === 'coordinator_manage' ? 'active' : '' ?>" href="index.php?r=coordinator_manage"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg><span>Student Enrollment</span></a><a class="nav-link <?= $currentRoute === 'coordinator_students' ? 'active' : '' ?>" href="index.php?r=coordinator_students"><svg viewBox="0 0 24 24"><path d="M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h16v2H4v-2Z"/></svg><span>My Students</span></a><a class="nav-link <?= $currentRoute === 'coordinator_evaluations' ? 'active' : '' ?>" href="index.php?r=coordinator_evaluations"><svg viewBox="0 0 24 24"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"/></svg><span>Evaluations</span></a><?php endif; ?>
            <?php if ($role === 'student'): ?>
                <?php if (!($studentProfileCompleted ?? true)): ?>
                    <a class="nav-link <?= $currentRoute === 'student_profile' ? 'active' : '' ?>" href="index.php?r=student_profile"><svg viewBox="0 0 24 24"><path d="M12 3 2 8l10 5 8-4v6h2V8L12 3Zm-6 9v4c2 3 10 3 12 0v-4l-6 3-6-3Z"/></svg><span>Complete Profile</span></a>
                <?php endif; ?>
                <a class="nav-link <?= $currentRoute === 'student_records' ? 'active' : '' ?>" href="index.php?r=student_records"><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm0 16H5V9h14v10Zm-9-8H7v3h3v3h3v-3h3v-3h-3V8h-3v3Z"/></svg><span>Submit Record</span></a>
                <a class="nav-link <?= $currentRoute === 'student_timeline' ? 'active' : '' ?>" href="index.php?r=student_timeline"><svg viewBox="0 0 24 24"><path d="M7 3a2 2 0 0 1 2 2v1h6V5a2 2 0 1 1 4 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm0 5v11h10V8H7Zm2 2h6v2H9v-2Zm0 4h4v2H9v-2Z"/></svg><span>Activity Timeline</span></a>
                <a class="nav-link <?= $currentRoute === 'student_documents' ? 'active' : '' ?>" href="index.php?r=student_documents"><svg viewBox="0 0 24 24"><path d="M7 2h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V8h4.5L14 3.5ZM9 12h6v2H9v-2Zm0 4h6v2H9v-2Z"/></svg><span>Documents</span></a>
                <a class="nav-link <?= in_array($currentRoute, ['student_settings', 'student_password'], true) ? 'active' : '' ?>" href="index.php?r=student_settings"><svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.03 7.03 0 0 0-1.63-.94l-.36-2.54A.5.5 0 0 0 13.9 2h-3.8a.5.5 0 0 0-.49.42l-.36 2.54c-.58.23-1.12.54-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.31 8.48a.5.5 0 0 0 .12.64l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58a.5.5 0 0 0-.12.64l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96c.51.4 1.05.71 1.63.94l.36 2.54a.5.5 0 0 0 .49.42h3.8a.5.5 0 0 0 .49-.42l.36-2.54c.58-.23 1.12-.54 1.63-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58ZM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5Z"/></svg><span>Settings</span></a>
            <?php endif; ?>
            <?php if ($role === 'partner'): ?><a class="nav-link <?= $currentRoute === 'partner' ? 'active' : '' ?>" href="index.php?r=partner"><svg viewBox="0 0 24 24"><path d="M3 21V7l6-4 6 4v14h-4v-5H7v5H3Zm14 0V9h4v12h-4ZM7 9h4v2H7V9Zm0 4h4v2H7v-2Z"/></svg><span>Company Portal</span></a><?php endif; ?>
        </nav>
        <div class="sidebar-user">
            <div class="sidebar-user-info">
                <span class="user-avatar"><?= e(strtoupper(substr($user['name'] ?? 'A', 0, 1))) ?></span>
                <div>
                    <strong><?= e($user['name'] ?? '') ?></strong>
                    <small><?= e(ucwords(str_replace('_', ' ', $user['role'] ?? ''))) ?></small>
                </div>
            </div>
            <a class="nav-link sidebar-logout" href="logout.php">
                <svg viewBox="0 0 24 24"><path d="M16 13v-2H7V8l-5 4 5 4v-3h9Zm1-9H9a2 2 0 0 0-2 2v3h2V6h8v12H9v-3H7v3a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Z"/></svg>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="topbar-copy"><h1><?= e($title ?? 'Dashboard') ?></h1><?php if ($pageSubtitle !== ''): ?><span><?= e($pageSubtitle) ?></span><?php endif; ?></div>
            <div class="top-actions">
                <div class="notification-menu" id="notifMenu">
                    <button class="notif-trigger" id="notifBtn" type="button" aria-label="Notifications" aria-controls="notifPanel" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <?php if (($unreadNotifications ?? 0) > 0): ?><span class="notif-badge"><?= (int)$unreadNotifications ?></span><?php endif; ?>
                    </button>
                </div>
                <div class="user-chip"><span class="user-avatar"><?= e(strtoupper(substr($user['name'] ?? 'A', 0, 1))) ?></span><div><strong><?= e($user['name'] ?? '') ?></strong><small><?= e($user['email'] ?? '') ?></small></div></div>
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
                        <a class="notif-item<?= $isUnread ? ' is-unread' : '' ?>" href="<?= e($note['link'] ?: '#') ?>">
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
