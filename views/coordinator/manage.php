<div class="grid two">
    <section class="card">
        <div class="card-head"><h2>Create Student from COR</h2><p class="muted">Add a student account and securely store their uploaded registration document.</p></div>
        <form method="post" enctype="multipart/form-data" class="form js-validate">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="coordinator_create_student">
            <label>Student Number<input required name="student_no"></label>
            <label>Full Name<input required name="full_name"></label>
            <label>Email<input required type="email" name="email"></label>
            <label><select required name="program_id">
                <option value="">— Select course —</option>
                <?php foreach ($programs as $program): ?><option value="<?= (int)$program['id'] ?>"><?= e($program['code'] . ' — ' . $program['name'] . ' (' . $program['required_hours'] . ' hrs)') ?></option><?php endforeach; ?>
            </select></label>
            <label>Year Level<input required name="year_level"></label>
            <label>Section<input required name="section"></label>
            <label>COR PDF/JPG/PNG<input required type="file" name="cor_file" accept=".pdf,.jpg,.jpeg,.png"></label>
            <button class="btn btn-primary" type="submit"><span class="btn-text">Create Student</span><span class="spinner"></span></button>
        </form>
    </section>
    <section class="card">
        <div class="card-head"><h2>Enroll Student in OJT</h2><p class="muted">Follow the step-by-step wizard to assign a partner company and send deployment emails.</p></div>
        <form method="post" class="form js-validate wizard-form" data-wizard>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="coordinator_enroll_student">
            <div class="wizard-steps"><span class="active">Student</span><span>Company & Dates</span><span>Confirm</span></div>
            <div class="wizard-step active">
                <label><select required name="student_id"><option value="">— Select student —</option><?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>" data-program-id="<?= (int)($s['program_id'] ?? 0) ?>" data-required-hours="<?= (int)($s['program_required_hours'] ?? 0) ?>"><?= e($s['name'] . ' - ' . $s['student_no'] . ' (' . ($s['program_code'] ?? $s['course']) . ')') ?></option><?php endforeach; ?></select></label>
                <button class="btn btn-primary wizard-next" type="button">Next</button>
            </div>
            <div class="wizard-step">
                <label><select required name="company_id"><option value="">— Select company —</option><?php foreach ($companies as $c): ?><option value="<?= (int)$c['id'] ?>" data-program-ids="<?= e($c['accepted_program_ids'] ?? '') ?>"><?= e($c['name'] . (!empty($c['accepted_programs']) ? ' — ' . $c['accepted_programs'] : '')) ?></option><?php endforeach; ?></select></label>
                <label>Academic Term<input required name="academic_term" placeholder="Term 2533"></label>
                <label>Term Start Date<input required type="date" name="term_start_date"></label>
                <label>Term End Date<input required type="date" name="term_end_date"></label>
                <label>Start Date<input required type="date" name="start_date"></label>
                <label>End Date<input required type="date" name="end_date"></label>
                <label>Required Hours<input required readonly type="number" min="1" name="required_hours"></label>
                <div class="wizard-actions"><button class="btn btn-small wizard-prev" type="button">Back</button><button class="btn btn-primary wizard-next" type="button">Next</button></div>
            </div>
            <div class="wizard-step">
                <div class="confirm-box"></div>
                <div class="wizard-actions"><button class="btn btn-small wizard-prev" type="button">Back</button><button class="btn btn-primary" type="submit"><span class="btn-text">Enroll & Send Emails</span><span class="spinner"></span></button></div>
            </div>
        </form>
    </section>
</div>
