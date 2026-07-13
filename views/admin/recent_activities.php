<?php
$typeLabels = [
    'registration' => 'Account Request',
    'password' => 'Password Reset',
    'login' => 'Login',
    'deployment_forwarded' => 'Deployment',
    'deployment_accepted' => 'Deployment',
    'orientation' => 'Orientation',
    'ojt_started' => 'OJT Placement',
];
$typeBadges = [
    'registration' => 'pending',
    'password' => 'pending',
    'login' => 'active',
    'deployment_forwarded' => 'pending',
    'deployment_accepted' => 'completed',
    'orientation' => 'active',
    'ojt_started' => 'completed',
];
?>
<section class="card">
    <div class="section-head section-head-split">
        <div>
            <h2>Recent Activities</h2>
            <p class="muted">Track deployments, logins, student account requests, and password reset requests.</p>
        </div>
        <input class="table-search table-search-wide" placeholder="Search activities...">
    </div>
    <?php if (empty($activities)): ?>
        <p class="muted" style="padding:24px 0">No recent activities have been recorded yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="data-table" data-hide-column-toggle><thead><tr>
        <th data-sort>Activity</th>
        <th data-sort>Category</th>
        <th data-sort>Detail</th>
        <th data-sort>Date</th>
        <th data-sort>Time</th>
    </tr></thead><tbody>
        <?php foreach ($activities as $activity): ?>
        <?php
            $type = (string)($activity['type'] ?? '');
            $category = $typeLabels[$type] ?? 'Activity';
            $badgeClass = $typeBadges[$type] ?? 'pending';
            $when = (string)($activity['time'] ?? '');
            $dateLabel = $when !== '' ? date('M j, Y', strtotime($when)) : '—';
            $timeLabel = $when !== '' ? date('g:i A', strtotime($when)) : '—';
            $detailFields = [];
            if ($type === 'login') {
                $detailFields[] = [
                    'label' => 'IP Address',
                    'value' => (string)($activity['ip'] ?? '—'),
                ];
                $detailFields[] = [
                    'label' => 'Device',
                    'value' => (string)($activity['device'] ?? '—'),
                ];
            }
        ?>
        <tr<?= $detailFields !== [] ? ' data-detail-fields="' . e(json_encode($detailFields, JSON_UNESCAPED_UNICODE)) . '"' : '' ?>>
            <td><?= e((string)($activity['title'] ?? '')) ?></td>
            <td><span class="badge <?= e($badgeClass) ?>"><?= e($category) ?></span></td>
            <td><?= e((string)($activity['detail'] ?? '')) ?></td>
            <td><?= e($dateLabel) ?></td>
            <td><?= e($timeLabel) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <div class="pagination"></div>
    <?php endif; ?>
</section>
