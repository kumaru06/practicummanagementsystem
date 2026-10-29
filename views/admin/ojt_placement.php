<?php
$placements = $placements ?? [];
$oplTotal = count($placements);
$oplActive = 0;
$oplCompleted = 0;
$oplPending = 0;
foreach ($placements as $placementCount) {
    $countStatus = (string)($placementCount['status'] ?? 'pending');
    if ($countStatus === 'active') {
        $oplActive++;
    } elseif ($countStatus === 'completed') {
        $oplCompleted++;
    } else {
        $oplPending++;
    }
}
$oplInitials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $letters .= strtoupper(substr($part, 0, 1));
        if (strlen($letters) >= 2) {
            break;
        }
    }
    return $letters !== '' ? $letters : 'S';
};
?>
<section class="opl" data-ojt-placement-directory>
    <header class="opl-hero">
        <div>
            <p class="opl-kicker">Host training</p>
            <h2>Placement directory</h2>
        </div>
        <p class="opl-hero-count"><strong><?= (int)$oplTotal ?></strong><span>on record</span></p>
    </header>

    <div class="opl-stats" role="group" aria-label="Filter placements">
        <button class="opl-stat is-on" type="button" data-opl-filter="active">
            <span>Active</span>
            <strong><?= (int)$oplActive ?></strong>
        </button>
        <button class="opl-stat" type="button" data-opl-filter="completed">
            <span>Completed</span>
            <strong><?= (int)$oplCompleted ?></strong>
        </button>
        <button class="opl-stat" type="button" data-opl-filter="pending">
            <span>In pipeline</span>
            <strong><?= (int)$oplPending ?></strong>
        </button>
        <button class="opl-stat" type="button" data-opl-filter="all">
            <span>All placements</span>
            <strong><?= (int)$oplTotal ?></strong>
        </button>
    </div>

    <div class="opl-panel">
        <div class="opl-toolbar">
            <label class="filter-select-wrap asu-filter-select">
                <select data-ojt-placement-status-filter data-select-label="Status" aria-label="Filter by status">
                    <option value="active" selected>Active</option>
                    <option value="pending">Pending</option>
                    <option value="awaiting_hte">Awaiting HTE</option>
                    <option value="awaiting_orientation">Awaiting Orientation</option>
                    <option value="completed">Completed</option>
                    <option value="all">All Status</option>
                </select>
            </label>
            <input class="table-search table-search-wide" placeholder="Search a student, course, or HTE">
        </div>

        <?php if ($placements === []): ?>
            <p class="opl-empty">No OJT placements have been recorded yet.</p>
        <?php else: ?>
        <div class="table-wrap opl-table-wrap"><table class="data-table opl-table" data-hide-column-toggle><thead><tr>
            <th data-sort>Student</th>
            <th data-sort>Course</th>
            <th data-sort>HTE</th>
            <th data-sort data-placement-col="start">Start Date</th>
            <th data-sort data-placement-col="end" style="display:none">End Date</th>
            <th data-sort>Status</th>
        </tr></thead><tbody>
            <?php foreach ($placements as $placement): ?>
            <?php
                $status = (string)($placement['status'] ?? 'pending');
                $statusClass = in_array($status, ['active', 'pending', 'completed'], true) ? $status : 'pending';
                $isCompleted = $status === 'completed';
                $predeployment = (string)($placement['predeployment_status'] ?? '');
                $statusLabel = ucfirst($status);
                $statusTone = $statusClass;
                if (!$isCompleted && $status !== 'active') {
                    if ($predeployment === 'forwarded') {
                        $statusLabel = 'Awaiting HTE';
                        $statusTone = 'waiting';
                    } elseif ($predeployment === 'accepted') {
                        $statusLabel = 'Awaiting Orientation';
                        $statusTone = 'waiting';
                    }
                }
                $studentName = (string)($placement['student_name'] ?? '');
                $studentNo = trim((string)($placement['student_no'] ?? ''));
                $position = trim((string)($placement['position_held'] ?? ''));
                $searchHaystack = strtolower(trim(
                    $studentName . ' ' .
                    $studentNo . ' ' .
                    ($placement['course'] ?? '') . ' ' .
                    ($placement['company_name'] ?? '') . ' ' .
                    $position . ' ' .
                    $status . ' ' .
                    $statusLabel . ' ' .
                    $predeployment
                ));
            ?>
            <tr data-placement-status="<?= e($statusClass) ?>" data-predeployment="<?= e($predeployment) ?>" data-search="<?= e($searchHaystack) ?>">
                <td>
                    <div class="opl-person">
                        <span class="opl-avatar" aria-hidden="true"><?= e($oplInitials($studentName)) ?></span>
                        <span class="opl-person-copy">
                            <strong><?= e($studentName) ?></strong>
                            <?php if ($studentNo !== ''): ?><small><?= e($studentNo) ?></small><?php endif; ?>
                        </span>
                    </div>
                </td>
                <td><span class="opl-course"><?= e($placement['course']) ?></span></td>
                <td>
                    <div class="opl-hte">
                        <strong><?= e($placement['company_name']) ?></strong>
                        <?php if ($position !== ''): ?><small><?= e($position) ?></small><?php endif; ?>
                    </div>
                </td>
                <td class="opl-date"><?= !$isCompleted && !empty($placement['placement_start']) ? e(date('M j, Y', strtotime((string)$placement['placement_start']))) : '<span class="muted">&mdash;</span>' ?></td>
                <td class="opl-date" style="display:none"><?= $isCompleted && !empty($placement['placement_end']) ? e(date('M j, Y', strtotime((string)$placement['placement_end']))) : '<span class="muted">&mdash;</span>' ?></td>
                <td><span class="opl-status opl-status--<?= e($statusTone) ?>"><?= e($statusLabel) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table></div>
        <div class="pagination"></div>
        <?php endif; ?>
    </div>
</section>
