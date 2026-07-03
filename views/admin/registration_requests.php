<section class="card">
    <div class="section-head">
        <div>
            <span class="cdoc-section-eyebrow">Student Self-Registration</span>
            <h2>Pending Registration Requests</h2>
            <p>Review email-verified student applications awaiting administrator approval.</p>
        </div>
        <?php if (!empty($requests)): ?>
            <div class="cdoc-live-count" aria-live="polite">
                <strong><?= count($requests) ?></strong>
                <span>pending</span>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($requests)): ?>
        <div class="cdoc-empty">
            <div class="cdoc-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg>
            </div>
            <p class="cdoc-empty-title">No pending registrations</p>
            <p class="cdoc-empty-sub">New student registration requests will appear here for approval.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>USN</th>
                        <th>Email</th>
                        <th>Verified</th>
                        <th>COR</th>
                        <th>Submitted</th>
                        <th colspan="2">Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                    <tr>
                        <td>
                            <div class="user-name-cell">
                                <span class="table-avatar"><?= e(strtoupper(substr((string)$request['last_name'], 0, 1))) ?></span>
                                <span><?= e($request['last_name']) ?></span>
                            </div>
                        </td>
                        <td><?= e($request['first_name']) ?></td>
                        <td class="center-cell"><?= e($request['student_no']) ?></td>
                        <td class="muted-cell"><?= e($request['email']) ?></td>
                        <td class="muted-cell">
                            <?php if (!empty($request['email_verified_at'])): ?>
                                <?= e(date('M d, Y g:i A', strtotime((string)$request['email_verified_at']))) ?>
                            <?php else: ?>
                                <span class="muted">Legacy request</span>
                            <?php endif; ?>
                        </td>
                        <td class="center-cell">
                            <?php if (!empty($request['cor_file'])): ?>
                                <a class="btn btn-small" href="<?= e(asset($request['cor_file'])) ?>" target="_blank" rel="noopener noreferrer">View COR</a>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted-cell"><?= e(date('M d, Y g:i A', strtotime((string)$request['created_at']))) ?></td>
                        <td colspan="2">
                            <form method="post" class="registration-approve-row">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="admin_review_registration_request">
                                <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                                <input type="hidden" name="decision" value="approve">
                                <select name="coordinator_id" required aria-label="Coordinator for <?= e($request['student_no']) ?>">
                                    <option value="">Select coordinator</option>
                                    <?php foreach ($coordinators as $coordinator): ?>
                                        <?php if ((int)($coordinator['is_active'] ?? 0) !== 1) continue; ?>
                                        <option value="<?= (int)$coordinator['id'] ?>"><?= e($coordinator['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-small btn-success-outline" type="submit">Accept</button>
                            </form>
                            <form method="post" class="inline" data-confirm="Decline this registration request?" data-confirm-title="Decline registration" data-confirm-ok="Yes, decline" data-confirm-cancel="Cancel">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="admin_review_registration_request">
                                <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                                <input type="hidden" name="decision" value="decline">
                                <button class="btn btn-small btn-danger-outline" type="submit">Decline</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
