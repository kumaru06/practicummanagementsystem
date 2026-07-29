<?php
    $stages = $stages ?? [];
    $activeWorkflowStep = (int)($activeWorkflowStep ?? ($activeStage ?? 1));

    $statusMeta = [
        'approved'       => ['label' => 'Approved',       'cls' => 'is-approved'],
        'submitted'      => ['label' => 'Under review',   'cls' => 'is-submitted'],
        'needs_revision' => ['label' => 'Needs revision', 'cls' => 'is-rejected'],
        'in_progress'    => ['label' => 'In progress',    'cls' => 'is-progress'],
        'not_started'    => ['label' => 'Not started',    'cls' => 'is-idle'],
        'locked'         => ['label' => 'Locked',         'cls' => 'is-locked'],
    ];

    $stepIcons = [
        1 => '<svg class="docs-step-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm-3 15-3-3 1.4-1.4L11 14.2l4.6-4.6L17 11l-6 6Z"/></svg>',
        2 => '<svg class="docs-step-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm-9 4h7v2h-7V8Zm0 4h7v2h-7v-2ZM6 8h3v3H6V8Zm0 5h3v3H6v-3Z"/></svg>',
        3 => '<svg class="docs-step-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm-7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm-2 14-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8Z"/></svg>',
    ];

    $stageNav = [];
    foreach ([1, 2, 3] as $stageNo) {
        $stageData = $stages[$stageNo] ?? null;
        $stageNav[$stageNo] = [
            'label' => $stageData['label'] ?? ('Stage ' . $stageNo),
            'route' => route_url('student.documents', ['stage' => $stageNo]),
            'status' => $statusMeta[$stageData['status'] ?? 'locked'] ?? $statusMeta['locked'],
            'active' => $activeWorkflowStep === $stageNo,
            'accessible' => (bool)($stageData['accessible'] ?? false),
            'lock_message' => (string)($stageData['lock_message'] ?? 'Stage locked'),
        ];
    }
?>
<nav class="docs-stepper docs-stage-nav" aria-label="Document workflow">
    <?php foreach ($stageNav as $stageNo => $nav): ?>
        <?php if ($nav['accessible']): ?>
            <a class="docs-step<?= $nav['active'] ? ' is-current' : '' ?> <?= e($nav['status']['cls']) ?>" href="<?= e($nav['route']) ?>">
                <span class="docs-step-index" aria-hidden="true"><?= $stepIcons[$stageNo] ?? (int)$stageNo ?></span>
                <span class="docs-step-copy">
                    <strong><?= e($nav['label']) ?></strong>
                    <span class="docs-step-status"><?= e($nav['status']['label']) ?></span>
                </span>
            </a>
        <?php else: ?>
            <span class="docs-step is-locked <?= e($nav['status']['cls']) ?>" title="<?= e($nav['lock_message']) ?>" aria-disabled="true">
                <span class="docs-step-index" aria-hidden="true"><?= $stepIcons[$stageNo] ?? (int)$stageNo ?></span>
                <span class="docs-step-copy">
                    <strong><?= e($nav['label']) ?></strong>
                    <span class="docs-step-status">Locked</span>
                </span>
            </span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
