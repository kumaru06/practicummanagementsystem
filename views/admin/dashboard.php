<div class="grid cards">
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg><div><strong><?= (int)$stats['coordinators'] ?></strong><span>Coordinators</span></div></div>
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg><div><strong><?= (int)$stats['companies'] ?></strong><span>Host Training Establishments</span></div></div>
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="M12 3 2 8l10 5 10-5-10-5Zm-6 9v4c2 3 10 3 12 0v-4l-6 3-6-3Z"/></svg><div><strong><?= (int)$stats['students'] ?></strong><span>Students</span></div></div>
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="m9 16.2-3.5-3.5L4 14.2 9 19l11-11-1.5-1.5L9 16.2Z"/></svg><div><strong><?= (int)$stats['active'] ?></strong><span>Active OJT</span></div></div>
</div>

<div class="grid chart-grid">
    <section class="card chart-card recent-activity-card">
        <div class="chart-header recent-activity-header">
            <div class="recent-activity-title-wrap">
                <span class="recent-activity-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                </span>
                <h2 class="chart-title">Recent Activities</h2>
            </div>
            <a class="recent-activity-view-all" href="<?= e(route_url('admin.recent_activities')) ?>">View All</a>
        </div>

        <?php if (empty($recentActivities ?? [])): ?>
            <div class="recent-activity-empty">
                <p>No recent activity yet.</p>
            </div>
        <?php else: ?>
            <ul class="recent-activity-list">
                <?php foreach ($recentActivities as $activity): ?>
                    <?php
                    $type = (string)($activity['type'] ?? 'ojt_started');
                    // Same solid icons used across admin/sidebar navigation.
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
<script>window.dashboardCharts = <?= json_encode($charts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;</script>
