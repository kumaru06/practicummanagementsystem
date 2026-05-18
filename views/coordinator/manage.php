<div class="grid two">
    <section class="card">
        <div class="card-head"><h2>Create Student from COR</h2><p class="muted">Add a student account and securely store their uploaded registration document.</p></div>
        <form method="post" enctype="multipart/form-data" class="form js-validate">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="coordinator_create_student">
            <label><span>Student ID/USN <span class="field-required">*</span></span><input required name="student_no"></label>
            <div class="student-name-row">
                <label><span>First Name <span class="field-required">*</span></span><input required name="first_name" autocomplete="given-name" pattern="[A-Za-z\s\-\.]+" title="First name must contain letters only" oninput="this.value=this.value.replace(/[^A-Za-z\s\-\.]/g,'')"></label>
                <label><span>Last Name <span class="field-required">*</span></span><input required name="last_name" autocomplete="family-name" pattern="[A-Za-z\s\-\.]+" title="Last name must contain letters only" oninput="this.value=this.value.replace(/[^A-Za-z\s\-\.]/g,'')"></label>
            </div>
            <label><span>Email <span class="field-required">*</span></span><input required type="email" name="email"></label>
            <label><span>Course <span class="field-required">*</span></span><select required name="program_id">
                <option value="">— Select course —</option>
                <?php foreach ($programs as $program): ?><option value="<?= (int)$program['id'] ?>"><?= e($program['code'] . ' — ' . $program['name'] . ' (' . $program['required_hours'] . ' hrs)') ?></option><?php endforeach; ?>
            </select></label>
            <label><span>Year Level <span class="field-required">*</span></span><select required name="year_level">
                <option value="">— Select year level —</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>
            </select></label>
            <label><span>Birthdate <span class="field-required">*</span></span>
                <span class="filter-date-picker form-date-picker is-placeholder" data-date-required="1" data-date-max="<?= date('Y-m-d', strtotime('-20 years')) ?>">
                    <input type="hidden" name="birthdate" value="">
                    <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select birthdate">
                        <span class="filter-date-value">mm/dd/yyyy</span>
                        <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                    </button>
                    <div class="filter-date-panel" hidden></div>
                </span>
            </label>
            <label><span>COR PDF/JPG/PNG <span class="field-required">*</span></span><input required type="file" name="cor_file" accept=".pdf,.jpg,.jpeg,.png"></label>
            <button class="btn btn-primary" type="submit"><span class="btn-text">Create Student</span><span class="spinner"></span></button>
        </form>
    </section>
    <section class="card">
        <div class="card-head"><h2>Enroll Student in OJT</h2><p class="muted">Follow the step-by-step wizard to assign an Industry Partner and send deployment emails.</p></div>
        <form method="post" class="form js-validate wizard-form" data-wizard>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="coordinator_enroll_student">
            <div class="wizard-steps"><span class="active">Student</span><span>Company & Dates</span><span>Confirm</span></div>
            <div class="wizard-step active">
                <label><select required name="student_id"><option value="">— Select student —</option><?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>" data-program-id="<?= (int)($s['program_id'] ?? 0) ?>" data-required-hours="<?= (int)($s['program_required_hours'] ?? 0) ?>"><?= e($s['name'] . ' - ' . $s['student_no'] . ' (' . ($s['program_code'] ?? $s['course']) . ')') ?></option><?php endforeach; ?></select></label>
                <button class="btn btn-primary wizard-next" type="button">Next</button>
            </div>
            <div class="wizard-step">
                <label><select required name="company_id"><option value="">— Select Industry Partner —</option><?php foreach ($companies as $c): ?><option value="<?= (int)$c['id'] ?>" data-program-ids="<?= e($c['accepted_program_ids'] ?? '') ?>" data-moa-mou="<?= e(!empty($c['moa_mou_file']) ? 'index.php?r=coordinator_partner_document&company_id=' . (int)$c['id'] : '') ?>"><?= e($c['name'] . (!empty($c['accepted_programs']) ? ' — ' . $c['accepted_programs'] : '')) ?></option><?php endforeach; ?></select></label>
                <div class="company-doc-preview" data-company-doc-preview hidden>
                    <span class="muted">Industry Partner MOA/MOU:</span>
                    <a class="btn btn-small" data-company-doc-link target="_blank" href="#">View MOA/MOU</a>
                </div>
                <label>Academic Term
                    <select required name="academic_term">
                        <option value="">— Select Term —</option>
                        <?php foreach (($terms ?? []) as $t): ?>
                            <option value="<?= e($t['term_label']) ?>"><?= e($t['term_label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Term Start Date
                    <span class="filter-date-picker form-date-picker is-placeholder" data-date-required="1">
                        <input type="hidden" name="term_start_date" value="">
                        <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select term start date">
                            <span class="filter-date-value">mm/dd/yyyy</span>
                            <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                        </button>
                        <div class="filter-date-panel" hidden></div>
                    </span>
                </label>
                <label>Term End Date
                    <span class="filter-date-picker form-date-picker is-placeholder" data-date-required="1">
                        <input type="hidden" name="term_end_date" value="">
                        <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select term end date">
                            <span class="filter-date-value">mm/dd/yyyy</span>
                            <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                        </button>
                        <div class="filter-date-panel" hidden></div>
                    </span>
                </label>
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
