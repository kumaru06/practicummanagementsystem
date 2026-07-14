<?php
$typeLabels = [
    'registration' => 'Account Request',
    'password' => 'Password Reset',
    'login' => 'Login',
    'logout' => 'Logout',
    'deployment_forwarded' => 'Deployment',
    'deployment_accepted' => 'Deployment',
    'orientation' => 'Orientation',
    'ojt_started' => 'OJT Placement',
];

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

$chipCategories = [
    'Account Request',
    'Password Reset',
    'Login',
    'Logout',
    'Deployment',
    'Orientation',
    'OJT Placement',
];

$now = new DateTimeImmutable('now');
$yesterday = $now->modify('-1 day');
$grouped = [];

foreach ($activities ?? [] as $activity) {
    $when = (string)($activity['time'] ?? '');
    $dayKey = 'unknown';
    $dayLabel = 'Other';
    $sortKey = '0000-00-00';

    if ($when !== '') {
        try {
            $then = new DateTimeImmutable($when);
            $sortKey = $then->format('Y-m-d');
            if ($then->format('Y-m-d') === $now->format('Y-m-d')) {
                $dayKey = 'today';
                $dayLabel = 'Today';
            } elseif ($then->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                $dayKey = 'yesterday';
                $dayLabel = 'Yesterday';
            } else {
                $dayKey = $sortKey;
                $dayLabel = $then->format('M j, Y');
            }
        } catch (Throwable) {
            // keep defaults
        }
    }

    if (!isset($grouped[$dayKey])) {
        $grouped[$dayKey] = [
            'label' => $dayLabel,
            'sort' => $sortKey,
            'items' => [],
        ];
    }
    $grouped[$dayKey]['items'][] = $activity;
}

uasort($grouped, static fn(array $a, array $b): int => strcmp($b['sort'], $a['sort']));
?>
<div class="admin-activities-v2" data-activities-feed data-per-page="20">
    <header class="aa-header">
        <div class="aa-header-copy">
            <span class="aa-header-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18Zm-1 5v5.25l4.5 2.67.75-1.23L13.5 12.4V8H12Z"/></svg>
            </span>
            <div>
                <h2 class="aa-title">Recent Activities</h2>
                <p class="aa-subtitle">Track deployments, logins, logouts, student account requests, and password reset requests.</p>
            </div>
        </div>
        <button class="aa-export-btn" type="button" data-activities-export<?= empty($activities) ? ' disabled' : '' ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12v7H5v-7H3v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7h-2ZM13 12.67V3h-2v9.67l-2.59-2.58L7 11.5 12 16.5l5-5-1.41-1.41L13 12.67Z"/></svg>
            Export CSV
        </button>
    </header>

    <?php if (empty($activities)): ?>
        <div class="aa-empty">
            <div class="aa-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18Zm-1 5v5.25l4.5 2.67.75-1.23L13.5 12.4V8H12Z"/></svg>
            </div>
            <h3>No activity yet</h3>
            <p>No recent activities have been recorded yet.</p>
        </div>
    <?php else: ?>
        <div class="aa-toolbar">
            <div class="aa-chips" role="tablist" aria-label="Filter by category">
                <button class="aa-chip is-active" type="button" role="tab" aria-selected="true" data-activities-chip data-filter="all">All</button>
                <?php foreach ($chipCategories as $chip): ?>
                    <button class="aa-chip" type="button" role="tab" aria-selected="false" data-activities-chip data-filter="<?= e($chip) ?>"><?= e($chip) ?></button>
                <?php endforeach; ?>
            </div>
            <label class="aa-search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5Zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14Z"/></svg>
                <input type="search" placeholder="Search activities..." data-activities-search autocomplete="off">
            </label>
        </div>

        <div class="aa-feed" data-activities-list>
            <?php foreach ($grouped as $dayKey => $group): ?>
                <section class="aa-day" data-activities-day data-day="<?= e($dayKey) ?>">
                    <h3 class="aa-day-label"><?= e($group['label']) ?></h3>
                    <ul class="aa-list">
                        <?php foreach ($group['items'] as $activity): ?>
                            <?php
                            $type = (string)($activity['type'] ?? '');
                            $category = $typeLabels[$type] ?? 'Activity';
                            $title = (string)($activity['title'] ?? '');
                            $detail = (string)($activity['detail'] ?? '');
                            $when = (string)($activity['time'] ?? '');
                            $timeOfDay = $when !== '' ? date('g:i A', strtotime($when)) : '—';
                            $dateLabel = $when !== '' ? date('M j, Y', strtotime($when)) : '—';
                            $link = trim((string)($activity['link'] ?? ''));
                            $isClickable = $link !== '' && $link !== '#';
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
                            $ip = trim((string)($activity['ip'] ?? ''));
                            $device = trim((string)($activity['device'] ?? ''));
                            $searchBlob = strtolower(trim($title . ' ' . $detail . ' ' . $category . ' ' . $ip . ' ' . $device));
                            $tag = $isClickable ? 'a' : 'div';
                            $tagAttrs = $isClickable
                                ? ' class="aa-item-link" href="' . e($link) . '"'
                                : ' class="aa-item-link aa-item-link--static"';
                            ?>
                            <li
                                class="aa-item"
                                data-activities-item
                                data-type="<?= e($type) ?>"
                                data-category="<?= e($category) ?>"
                                data-title="<?= e($title) ?>"
                                data-detail="<?= e($detail) ?>"
                                data-when="<?= e($dateLabel . ' ' . $timeOfDay) ?>"
                                data-search="<?= e($searchBlob) ?>"
                            >
                                <<?= $tag ?><?= $tagAttrs ?>>
                                    <span class="aa-item-rail" aria-hidden="true">
                                        <span class="aa-item-icon aa-item-icon--<?= e($tone) ?>">
                                            <svg viewBox="0 0 24 24"><?= $iconSvg ?></svg>
                                        </span>
                                    </span>
                                    <span class="aa-item-body">
                                        <span class="aa-item-top">
                                            <strong class="aa-item-title"><?= e($title) ?></strong>
                                            <span class="aa-item-badge aa-item-badge--<?= e($tone) ?>"><?= e($category) ?></span>
                                        </span>
                                        <span class="aa-item-detail"><?= e($detail) ?></span>
                                        <?php if (in_array($type, ['login', 'logout'], true) && ($ip !== '' || $device !== '')): ?>
                                            <span class="aa-item-meta">
                                                <?php if ($ip !== '' && $ip !== '—'): ?>
                                                    <span>IP <?= e($ip) ?></span>
                                                <?php endif; ?>
                                                <?php if ($device !== '' && $device !== '—'): ?>
                                                    <span><?= e($device) ?></span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                    <time class="aa-item-time" datetime="<?= e($when) ?>" title="<?= e($dateLabel . ' · ' . $timeOfDay) ?>">
                                        <?= e(format_activity_time($when !== '' ? $when : null) ?: $timeOfDay) ?>
                                    </time>
                                </<?= $tag ?>>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>

        <div class="aa-empty aa-empty--filtered is-hidden" data-activities-empty>
            <div class="aa-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5Zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14Z"/></svg>
            </div>
            <h3>No matching activities</h3>
            <p>Try a different search or clear the category filter.</p>
            <button class="aa-reset-btn" type="button" data-activities-reset>Clear filters</button>
        </div>

        <div class="aa-pagination" data-activities-pagination></div>
    <?php endif; ?>
</div>
