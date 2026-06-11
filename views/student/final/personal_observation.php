<?php
    $finalRequirement = $finalRequirement ?? [];
    $poFields = FinalRequirement::PERSONAL_OBSERVATION_FIELDS;
    $svgAttrs = 'class="final-req-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    $observationIcon = '<svg ' . $svgAttrs . '><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/><path d="m15 5 3 3"/></svg>';
    $poIcons = [
        'po_facilities' => '<svg ' . $svgAttrs . '><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12h12"/><path d="M10 8h.01M14 8h.01M10 16h.01M14 16h.01"/></svg>',
        'po_services' => '<svg ' . $svgAttrs . '><path d="M12 2v4"/><path d="M12 18v4"/><path d="m4.93 4.93 2.83 2.83"/><path d="m16.24 16.24 2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="m4.93 19.07 2.83-2.83"/><path d="m16.24 7.76 2.83-2.83"/></svg>',
        'po_employee' => '<svg ' . $svgAttrs . '><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'po_management' => '<svg ' . $svgAttrs . '><circle cx="12" cy="8" r="4"/><path d="M6 21v-1a6 6 0 0 1 12 0v1"/></svg>',
        'po_organization' => '<svg ' . $svgAttrs . '><rect x="9" y="2" width="6" height="6" rx="1"/><rect x="2" y="16" width="6" height="6" rx="1"/><rect x="16" y="16" width="6" height="6" rx="1"/><path d="M12 8v4M12 12H5v4M12 12h7v4"/></svg>',
        'po_recommendation' => '<svg ' . $svgAttrs . '><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2h0a3.13 3.13 0 0 1 3 3.88Z"/></svg>',
    ];
?>
<section class="card final-form-card po-card">
    <div class="final-form-head">
        <span class="final-form-icon final-form-icon--observation"><?= $observationIcon ?></span>
        <div class="final-form-head-copy">
            <h2>Personal Observations</h2>
            <p class="muted">Provide your personal observations about the company.</p>
        </div>
    </div>

    <form method="post" class="form js-validate final-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="student_save_final_personal_observation">

        <div class="po-field-list">
            <?php foreach ($poFields as $column => $field): ?>
                <div class="po-field">
                    <div class="po-field-head">
                        <span class="po-field-icon"><?= $poIcons[$column] ?? '' ?></span>
                        <div>
                            <span class="po-field-label"><?= e($field[0]) ?> <span class="req-star">*</span></span>
                            <span class="po-field-desc"><?= e($field[1]) ?></span>
                        </div>
                    </div>
                    <div class="po-field-input">
                        <textarea name="<?= e($column) ?>" required maxlength="2000" rows="4" placeholder="<?= e($field[2]) ?>"><?= e($finalRequirement[$column] ?? '') ?></textarea>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button class="btn btn-primary final-form-submit" type="submit">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            <span>Save Personal Observations</span>
        </button>
    </form>
</section>
