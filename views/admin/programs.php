<?php
$totalPrograms = count($programs);
$activePrograms = count(array_filter($programs, static fn ($program) => (int)($program['is_active'] ?? 0) === 1));
$inactivePrograms = $totalPrograms - $activePrograms;
$termSuggestions = array_values(array_unique(array_filter(array_map(static fn(array $term): string => trim((string)($term['term_label'] ?? '')), $terms ?? []))));
sort($termSuggestions, SORT_NATURAL | SORT_FLAG_CASE);
$termFormatPattern = '\d{4} \((1st|2nd|3rd) Tri\) - SY \d{4}-\d{4}';
?>

<div class="programs-page">

    <div class="programs-stats-strip">
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

    <!-- TOP ROW: Add Program (left) + Term Management (right) -->
    <div class="programs-top-row">

        <!-- Add Program / Course -->
        <section class="card programs-add-card">
            <div class="programs-add-card-header">
                <div class="programs-icon-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                </div>
                <div>
                    <h2>Add Program / Course</h2>
                    <p>Programs must be prepared first before Industry Partners can choose them.</p>
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
                        <input required type="number" min="1" name="required_hours">
                    </label>
                    <label class="programs-term-label">
                        <span>Term</span>
                        <select required name="term">
                            <option value="">— Select Term —</option>
                            <?php foreach ($termSuggestions as $ts): ?>
                                <option value="<?= e($ts) ?>"><?= e($ts) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <?php if (empty($termSuggestions)): ?><small class="muted">No terms yet. Add one in the Term panel first.</small><?php endif; ?>
                <button class="btn programs-add-btn" type="submit">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    <span class="btn-text">Add Program</span>
                    <span class="spinner"></span>
                </button>
            </form>
        </section>

        <section class="card programs-term-card">
            <div class="programs-add-card-header">
                <div class="programs-icon-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7h8M8 12h8M8 17h5"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>
                </div>
                <div>
                    <h2>Term</h2>
                    <p>Create and manage term list used by Program / Course.</p>
                </div>
            </div>

            <form method="post" class="form js-validate programs-term-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="admin_save_term">
                <label>
                    Input Term
                    <input
                        required
                        name="term_label"
                        placeholder="2523 (2nd Tri) - SY 2025-2026"
                        maxlength="120"
                    >
                </label>
                <button class="btn programs-add-btn" type="submit">
                    <span class="btn-text">Save Term</span>
                    <span class="spinner"></span>
                </button>
            </form>

            <div class="programs-term-list">
                <?php if (empty($terms)): ?>
                    <div class="programs-term-empty">No terms added yet.</div>
                <?php else: ?>
                    <?php foreach ($terms as $term): ?>
                        <div class="programs-term-row">
                            <span class="programs-term-label-text"><?= e($term['term_label']) ?></span>
                            <form method="post" class="programs-term-delete-form" onsubmit="return confirm('Delete this term? Programs currently using it will have their term cleared.')">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="admin_delete_term">
                                <input type="hidden" name="term_id" value="<?= (int)$term['id'] ?>">
                                <button class="programs-term-delete-btn" type="submit" title="Delete term">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

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
                            <select form="<?= e($formId) ?>" class="pf-input pf-term" name="term" required>
                                <option value="">— Term —</option>
                                <?php foreach ($termSuggestions as $ts): ?>
                                    <option value="<?= e($ts) ?>" <?= ($p['term'] === $ts) ? 'selected' : '' ?>><?= e($ts) ?></option>
                                <?php endforeach; ?>
                                <?php if (!in_array($p['term'], $termSuggestions, true) && $p['term'] !== ''): ?>
                                    <option value="<?= e($p['term']) ?>" selected><?= e($p['term']) ?></option>
                                <?php endif; ?>
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
