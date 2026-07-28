<?php
$stats = $stats ?? [];
$trends = $trends ?? [];
$pendingActions = $pendingActions ?? [];
$pendingTotal = array_sum(array_column($pendingActions, 'count'));

$metricCards = [
    [
        'key' => 'coordinators',
        'value' => (int)($stats['coordinators'] ?? 0),
        'label' => 'Coordinators',
        'icon' => '<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/>',
    ],
    [
        'key' => 'companies',
        'value' => (int)($stats['companies'] ?? 0),
        'label' => 'Host Training Establishments',
        'icon' => '<path d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/>',
    ],
    [
        'key' => 'students',
        'value' => (int)($stats['students'] ?? 0),
        'label' => 'Students',
        'icon' => '<path d="M12 3 2 8l10 5 10-5-10-5Zm-6 9v4c2 3 10 3 12 0v-4l-6 3-6-3Z"/>',
    ],
    [
        'key' => 'active',
        'value' => (int)($stats['active'] ?? 0),
        'label' => 'Active OJT',
        'icon' => '<path d="m9 16.2-3.5-3.5L4 14.2 9 19l11-11-1.5-1.5L9 16.2Z"/>',
    ],
];

$pendingIcons = [
    'registration' => '<path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm-8 2h6v2h-6V5ZM7 7h10v2H7V7Zm0 4h10v2H7v-2Zm0 4h7v2H7v-2Z"/>',
    'password' => '<path d="M12 2a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h1V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v3h6V7a3 3 0 0 0-3-3Zm0 9a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>',
    'deployment_forwarded' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h5v2H8V9Z"/>',
    'orientation' => '<path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm0 16H5V8h14v11ZM8 10h3v3H8v-3Zm5 0h3v3h-3v-3Zm-5 5h3v3H8v-3Zm5 0h3v3h-3v-3Z"/>',
];
?>
<div class="admin-dash-v2">
    <div class="grid cards">
        <?php foreach ($metricCards as $card): ?>
            <?php
            $trend = $trends[$card['key']] ?? null;
            $direction = (string)($trend['direction'] ?? 'flat');
            ?>
            <div class="card metric">
                <svg viewBox="0 0 24 24" aria-hidden="true"><?= $card['icon'] ?></svg>
                <div>
                    <strong><?= $card['value'] ?></strong>
                    <span><?= e($card['label']) ?></span>
                    <?php if ($trend): ?>
                        <span class="metric-trend metric-trend--<?= e($direction) ?>">
                            <?php if ($direction === 'up'): ?>
                                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3 3 9h3v4h4V9h3L8 3Z"/></svg>
                            <?php elseif ($direction === 'down'): ?>
                                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 13 3 7h3V3h4v4h3L8 13Z"/></svg>
                            <?php else: ?>
                                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round"/></svg>
                            <?php endif; ?>
                            <?= e((string)$trend['label']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="card admin-pending-card">
        <header class="admin-pending-head">
            <div class="admin-pending-title-wrap">
                <span class="admin-pending-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2Zm6-6V11c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2Z"/></svg>
                </span>
                <div>
                    <span class="admin-pending-eyebrow">Needs attention</span>
                    <h2 class="admin-pending-title">Pending Actions</h2>
                    <p class="admin-pending-sub">Items that need your review or follow-up</p>
                </div>
            </div>
            <?php if ($pendingTotal > 0): ?>
                <span class="admin-pending-total"><?= (int)$pendingTotal ?> pending</span>
            <?php endif; ?>
        </header>

        <?php if ($pendingActions === []): ?>
            <div class="admin-pending-empty">
                <span class="admin-pending-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"/></svg>
                </span>
                <strong>All caught up</strong>
                <p>No pending items require your attention right now.</p>
            </div>
        <?php else: ?>
            <ul class="admin-pending-list">
                <?php foreach ($pendingActions as $action): ?>
                    <li>
                        <a class="admin-pending-item admin-pending-item--<?= e((string)$action['tone']) ?>" href="<?= e((string)$action['link']) ?>">
                            <span class="admin-pending-item-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><?= $pendingIcons[$action['key']] ?? $pendingIcons['registration'] ?></svg>
                            </span>
                            <span class="admin-pending-item-copy">
                                <strong><?= e((string)$action['title']) ?></strong>
                                <small><?= e((string)$action['detail']) ?></small>
                            </span>
                            <span class="admin-pending-item-count"><?= (int)$action['count'] ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <div class="grid chart-grid">
        <section class="card chart-card recent-activity-card">
            <header class="ra-panel-head">
                <div class="recent-activity-title-wrap">
                    <span class="recent-activity-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18Zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12Z"/></svg>
                    </span>
                    <div class="ra-panel-head-copy">
                        <span class="ra-eyebrow">Activity feed</span>
                        <h2 class="chart-title">Recent Activities</h2>
                        <p class="ra-panel-sub">Latest system events</p>
                    </div>
                </div>
                <a class="recent-activity-view-all" href="<?= e(route_url('admin.recent_activities')) ?>">View All</a>
            </header>

            <div class="ra-panel-body">
                <?php if (empty($recentActivities ?? [])): ?>
                    <div class="recent-activity-empty">
                        <p>No recent activity yet.</p>
                    </div>
                <?php else: ?>
                    <ul class="recent-activity-list">
                        <?php foreach ($recentActivities as $activity): ?>
                            <?php
                            $type = (string)($activity['type'] ?? 'ojt_started');
                            $iconSvgs = [
                                'registration' => '<path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm-8 2h6v2h-6V5ZM7 7h10v2H7V7Zm0 4h10v2H7v-2Zm0 4h7v2H7v-2Z"/>',
                                'password' => '<path d="M12 2a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h1V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v3h6V7a3 3 0 0 0-3-3Zm0 9a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>',
                                'login' => '<path d="M16 13v-2H7V8l-5 4 5 4v-3h9Zm1-9H9a2 2 0 0 0-2 2v3h2V6h8v12H9v-3H7v3a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Z"/>',
                                'logout' => '<path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5ZM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5Z"/>',
                                'deployment_forwarded' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h5v2H8V9Z"/>',
                                'deployment_accepted' => '<path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"/>',
                                'orientation' => '<path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm0 16H5V8h14v11ZM8 10h3v3H8v-3Zm5 0h3v3h-3v-3Zm-5 5h3v3H8v-3Zm5 0h3v3h-3v-3Z"/>',
                                'ojt_started' => '<path d="M10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 0 1 1 1v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a1 1 0 0 1 1-1h3V4a2 2 0 0 1 2-2Zm2 4V4h-2v2h2Zm-1 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>',
                            ];
                            $iconSvg = $iconSvgs[$type] ?? $iconSvgs['ojt_started'];
                            $tone = match ($type) {
                                'registration', 'login' => 'blue',
                                'logout' => 'slate',
                                'password' => 'amber',
                                'deployment_accepted', 'ojt_started' => 'green',
                                'deployment_forwarded' => 'slate',
                                'orientation' => 'violet',
                                default => 'green',
                            };
                            $link = trim((string)($activity['link'] ?? ''));
                            $isClickable = $link !== '' && $link !== '#';
                            ?>
                            <li>
                                <?php if ($isClickable): ?>
                                <a class="recent-activity-item" href="<?= e($link) ?>">
                                <?php else: ?>
                                <div class="recent-activity-item recent-activity-item--static">
                                <?php endif; ?>
                                    <span class="recent-activity-item-icon recent-activity-item-icon--<?= e($tone) ?>" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><?= $iconSvg ?></svg>
                                    </span>
                                    <span class="recent-activity-item-copy">
                                        <strong><?= e((string)($activity['title'] ?? '')) ?></strong>
                                        <small><?= e((string)($activity['detail'] ?? '')) ?></small>
                                    </span>
                                    <time class="recent-activity-item-time" datetime="<?= e((string)($activity['time'] ?? '')) ?>">
                                        <?= e(format_activity_time($activity['time'] ?? null)) ?>
                                    </time>
                                <?php if ($isClickable): ?>
                                </a>
                                <?php else: ?>
                                </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>
        <section class="card chart-card chart-card--status">
            <div class="chart-header">
                <h2 class="chart-title">OJT Status Distribution</h2>
            </div>
            <canvas id="statusChart"></canvas>
        </section>
    </div>
    <section class="card chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Completion Rate by Course</h2>
        </div>
        <canvas id="courseChart"></canvas>
    </section>
</div>
<script>window.dashboardCharts = <?= json_encode($charts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;</script>
