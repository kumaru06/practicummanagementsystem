<?php
$totalPrograms = count($programs);
$activePrograms = count(array_filter($programs, static fn ($program) => (int)($program['is_active'] ?? 0) === 1));
$inactivePrograms = $totalPrograms - $activePrograms;
?>

<div class="programs-page">

    <!-- TOP ROW: Add Form (left) + Stats (right, stacked vertically) -->
    <div class="programs-top-row">

        <!-- Add Program / Course -->
        <section class="card programs-add-card">
            <div class="programs-add-card-header">
                <div class="programs-icon-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                </div>
                <div>
                    <h2>Add Program / Course</h2>
                    <p>Programs must be prepared first before partner companies can choose them.</p>
                </div>
            </div>
            <form method="post" class="form js-validate programs-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="admin_save_program">
                <label>
                    Program Code
                    <input required name="code">
                </label>
                <label>
                    Program Name
                    <input required name="name">
                </label>
                <div class="programs-hrs-term-row">
                    <label class="programs-hours-label">
                        <span>Required OJT Hours</span>
                        <div class="programs-hours-input-wrap">
                            <input required type="number" min="1" name="required_hours">
                            <span class="programs-hrs-badge">hrs</span>
                        </div>
                    </label>
                    <label class="programs-term-label">
                        <span>Term</span>
                        <select required name="term">
                            <option value="" disabled selected>— Select —</option>
                            <?php
                            $currentYear = (int)date('Y');
                            for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++) {
                                foreach ([1, 2, 3] as $s) {
                                    $yy = $y % 100;
                                    $termCode = sprintf('%02d%d', $yy, $s);
                                    echo "<option value=\"$termCode\">{$y} &ndash; Term {$s}</option>";
                                }
                            }
                            ?>
                        </select>
                    </label>
                </div>
                <button class="btn programs-add-btn" type="submit">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    <span class="btn-text">Add Program</span>
                    <span class="spinner"></span>
                </button>
            </form>
        </section>

        <!-- Stats — stacked vertically -->
        <div class="programs-stats-col">
            <div class="programs-stat-card programs-stat-total">
                <div class="programs-stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                </div>
                <div>
                    <span>Total Programs</span>
                    <strong><?= (int)$totalPrograms ?></strong>
                </div>
            </div>
            <div class="programs-stat-card programs-stat-active">
                <div class="programs-stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <span>Active</span>
                    <strong><?= (int)$activePrograms ?></strong>
                </div>
            </div>
            <div class="programs-stat-card programs-stat-inactive">
                <div class="programs-stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                </div>
                <div>
                    <span>Inactive</span>
                    <strong><?= (int)$inactivePrograms ?></strong>
                </div>
            </div>
        </div>

    </div><!-- /programs-top-row -->

    <!-- BOTTOM ROW: Full-width Program List -->
    <section class="card programs-list-card">
        <div class="programs-list-header">
            <div>
                <h2>Program List</h2>
                <p>Edit hours, rename programs, or change their status below.</p>
            </div>
            <span class="programs-count-badge"><?= (int)$totalPrograms ?> program<?= $totalPrograms !== 1 ? 's' : '' ?></span>
        </div>

        <div class="programs-row-list">
            <?php if (empty($programs)): ?>
                <div class="programs-empty-state">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <p>No programs yet. Add one using the form above.</p>
                </div>
            <?php else: ?>
                <?php foreach ($programs as $p): ?>
                    <?php
                        $formId = 'program-form-' . (int)$p['id'];
                        $isActive = (bool)$p['is_active'];
                    ?>
                    <article class="program-row-card <?= $isActive ? 'program-row-active' : 'program-row-inactive' ?>">
                        <form method="post" id="<?= e($formId) ?>" class="program-row-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="admin_save_program">
                            <input type="hidden" name="program_id" value="<?= (int)$p['id'] ?>">
                        </form>
                        <div class="program-row-inline">
                            <input form="<?= e($formId) ?>" class="pf-input pf-code" name="code" value="<?= e($p['code']) ?>" placeholder="Code" required>
                            <input form="<?= e($formId) ?>" class="pf-input pf-name" name="name" value="<?= e($p['name']) ?>" placeholder="Program Name" required>
                            <select form="<?= e($formId) ?>" class="pf-input pf-term" name="term">
                                <?php
                                $currentYear = (int)date('Y');
                                for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++) {
                                    $yy = $y % 100;
                                    foreach ([1, 2, 3] as $s) {
                                        $termCode = sprintf('%02d%d', $yy, $s);
                                        $sel = ($p['term'] ?? '') === $termCode ? 'selected' : '';
                                        echo "<option value=\"$termCode\" $sel>$termCode</option>";
                                    }
                                }
                                ?>
                            </select>
                            <input form="<?= e($formId) ?>" class="pf-input pf-hours" type="number" min="1" name="required_hours" value="<?= (int)$p['required_hours'] ?>" placeholder="Hours" required>
                            <select form="<?= e($formId) ?>" class="pf-input pf-status" name="is_active">
                                <option value="1" <?= $isActive ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= !$isActive ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <div class="program-row-actions">
                                <button form="<?= e($formId) ?>" class="programs-save-btn" type="submit">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                    Save
                                </button>
                                <form method="post" onsubmit="return confirm('Delete this program?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="admin_delete_program">
                                    <input type="hidden" name="program_id" value="<?= (int)$p['id'] ?>">
                                    <button class="programs-delete-btn" type="submit">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

</div><!-- /programs-page -->
