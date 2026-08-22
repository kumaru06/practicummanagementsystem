<?php
    /**
     * Read-only display of structured form requirement fields.
     * Expects: $formSection (company_profile|job_description), $finalRequirement (row).
     */
    $formSection = (string)($formSection ?? '');
    $finalRequirement = $finalRequirement ?? [];
?>
<?php if ($formSection === 'company_profile'): ?>
    <div class="final-readonly-fields">
        <div class="final-readonly-field">
            <h4>History</h4>
            <p><?= nl2br(e((string)($finalRequirement['company_history'] ?? ''))) ?></p>
        </div>
        <div class="final-readonly-field">
            <h4>Description</h4>
            <p><?= nl2br(e((string)($finalRequirement['company_description'] ?? ''))) ?></p>
        </div>
        <div class="final-readonly-field">
            <h4>Mission</h4>
            <p><?= nl2br(e((string)($finalRequirement['company_mission'] ?? ''))) ?></p>
        </div>
        <div class="final-readonly-field">
            <h4>Vision</h4>
            <p><?= nl2br(e((string)($finalRequirement['company_vision'] ?? ''))) ?></p>
        </div>
    </div>
<?php elseif ($formSection === 'job_description'): ?>
    <div class="final-readonly-fields">
        <div class="final-readonly-field">
            <h4>Position Held</h4>
            <p><?= e((string)($finalRequirement['position_held'] ?? '')) ?></p>
        </div>
        <div class="final-readonly-field">
            <h4>Job Description (Duties and Responsibilities)</h4>
            <p><?= nl2br(e((string)($finalRequirement['job_description'] ?? ''))) ?></p>
        </div>
    </div>
<?php else: ?>
    <p class="muted">No form content available.</p>
<?php endif; ?>
