<?php
$recordCount = is_array($rows ?? null) ? count($rows) : 0;
$statusColIndex = null;
if (!empty($columns) && is_array($columns)) {
    foreach ($columns as $i => $column) {
        if (strcasecmp(trim((string)$column), 'Status') === 0) {
            $statusColIndex = $i;
            break;
        }
    }
}

$statusClass = static function (string $value): string {
    $key = strtolower(trim($value));
    return match ($key) {
        'active' => 'is-active',
        'pending' => 'is-pending',
        'completed' => 'is-completed',
        default => 'is-neutral',
    };
};

$normalizeReportRow = static function ($row): array {
    if (is_array($row) && array_key_exists('cells', $row) && is_array($row['cells'])) {
        return [
            'cells' => $row['cells'],
            'person' => is_array($row['person'] ?? null) ? $row['person'] : null,
            'detail' => is_array($row['detail'] ?? null) ? $row['detail'] : null,
        ];
    }
    return [
        'cells' => is_array($row) ? $row : [],
        'person' => null,
        'detail' => null,
    ];
};
?>
<div class="reports-detail-v2">
    <nav class="rdv-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(route_url('admin.reports')) ?>">Reports</a>
        <span class="rdv-breadcrumb-sep" aria-hidden="true">/</span>
        <span><?= e($report['category']) ?></span>
        <span class="rdv-breadcrumb-sep" aria-hidden="true">/</span>
        <span aria-current="page"><?= e($report['label']) ?></span>
    </nav>

    <section class="card rdv-card">
        <header class="rdv-head">
            <div class="rdv-copy">
                <span class="rdv-eyebrow">Report detail</span>
                <h2><?= e($report['label']) ?></h2>
                <?php if (!empty($description)): ?>
                    <p><?= e($description) ?></p>
                <?php endif; ?>
            </div>
            <div class="rdv-head-actions">
                <?php if (!empty($ready) && $recordCount > 0): ?>
                    <span class="rdv-count-badge">
                        <strong><?= (int)$recordCount ?></strong>
                        record<?= $recordCount === 1 ? '' : 's' ?>
                    </span>
                <?php endif; ?>
                <a class="btn btn-small rdv-back-btn" href="<?= e(route_url('admin.reports')) ?>">Back to Reports</a>
            </div>
        </header>

        <?php if (empty($ready)): ?>
            <div class="rdv-empty">
                <div class="rdv-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                        <rect x="9" y="3" width="6" height="4" rx="1"/>
                        <path d="M9 12h6M9 16h4"/>
                    </svg>
                </div>
                <h3>Report not available</h3>
                <p>This report is not available yet.</p>
            </div>
        <?php elseif ($recordCount === 0): ?>
            <div class="rdv-empty">
                <div class="rdv-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M20 20l-3-3"/>
                    </svg>
                </div>
                <h3>No records found</h3>
                <p>No records found for this report yet.</p>
            </div>
        <?php else: ?>
            <div class="rdv-toolbar">
                <div class="rdv-search-wrap">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    <input class="table-search rdv-table-search" type="search" placeholder="Search report..." aria-label="Search report">
                </div>
            </div>

            <div class="table-wrap rdv-table-wrap">
                <table class="data-table rdv-table" data-hide-column-toggle>
                    <thead>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <th data-sort><?= e($column) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $normalized = $normalizeReportRow($row);
                            $cells = $normalized['cells'];
                            $person = $normalized['person'];
                            $detail = $normalized['detail'];
                            $detailAttr = '';
                            if (is_array($detail) && $detail) {
                                $detailJson = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                if ($detailJson !== false) {
                                    $detailAttr = ' data-rdv-detail="' . e($detailJson) . '"';
                                }
                            }
                            ?>
                            <tr<?= $detailAttr ?>>
                                <?php foreach ($cells as $cellIndex => $cell): ?>
                                    <?php
                                    $text = (string)$cell;
                                    $isStatus = $statusColIndex !== null && (int)$cellIndex === (int)$statusColIndex;
                                    $isPerson = $cellIndex === 0 && $person !== null;
                                    ?>
                                    <td<?= ($cellIndex === 0 || $isPerson) ? ' class="rdv-name-cell"' : '' ?>>
                                        <?php if ($isPerson): ?>
                                            <?php
                                            $photoUrl = trim((string)($person['photo_url'] ?? ''));
                                            $personName = (string)($person['name'] ?? $text);
                                            $initial = (string)($person['initial'] ?? strtoupper(mb_substr($personName !== '' ? $personName : 'S', 0, 1)));
                                            $tone = max(1, min(6, (int)($person['tone'] ?? 1)));
                                            ?>
                                            <div class="rdv-student-cell">
                                                <span class="rdv-student-avatar aco-avatar-tone--<?= $tone ?><?= $photoUrl !== '' ? ' rdv-student-avatar--photo' : '' ?>">
                                                    <?php if ($photoUrl !== ''): ?>
                                                        <img src="<?= e($photoUrl) ?>" alt="">
                                                    <?php else: ?>
                                                        <?= e($initial) ?>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="rdv-student-name"><?= e($personName) ?></span>
                                            </div>
                                        <?php elseif ($isStatus): ?>
                                            <span class="rdv-status-pill <?= e($statusClass($text)) ?>"><?= e($text) ?></span>
                                        <?php else: ?>
                                            <?= e($text) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="rdv-footer">
                <div class="pagination"></div>
            </div>
        <?php endif; ?>
    </section>
</div>
