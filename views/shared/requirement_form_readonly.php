<?php
    /**
     * Read-only display of structured form requirement fields.
     * Expects: $formSection (company_profile|job_description|personal_observation), $finalRequirement (row).
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
<?php elseif ($formSection === 'personal_observation'): ?>
    <div class="final-readonly-fields">
        <?php foreach (FinalRequirement::PERSONAL_OBSERVATION_FIELDS as $column => $field): ?>
            <div class="final-readonly-field">
                <h4><?= e($field[0]) ?></h4>
                <p class="muted"><?= e($field[1]) ?></p>
                <p><?= nl2br(e((string)($finalRequirement[$column] ?? ''))) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="muted">No form content available.</p>
<?php endif; ?>
