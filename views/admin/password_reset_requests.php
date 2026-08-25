<?php
$pendingCount = count($requests ?? []);
$model = new PasswordResetRequest(db());
?>
<div class="reg-req-v2" data-pwd-reset-requests>
    <nav class="reg-req-breadcrumb" aria-label="Breadcrumb">
        <a href="index.php?r=admin">Dashboard</a>
        <span class="reg-req-breadcrumb-sep" aria-hidden="true">&rsaquo;</span>
        <span aria-current="page">Password Reset Requests</span>
    </nav>

    <section class="card reg-req-pending-card">
        <div class="reg-req-card-head">
            <div class="reg-req-card-copy">
                <span class="reg-req-eyebrow">Account Recovery</span>
                <h2>Pending Password Reset Requests</h2>
            </div>
            <div class="reg-req-pending-badge<?= $pendingCount > 0 ? '' : ' is-hidden' ?>" data-pwd-reset-badge aria-live="polite">
                <strong data-pwd-reset-count><?= $pendingCount ?></strong>
                <span>Pending</span>
            </div>
        </div>

        <div class="reg-req-empty<?= $pendingCount > 0 ? ' is-hidden' : '' ?>" data-pwd-reset-empty>
            <div class="reg-req-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 2a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h1V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v3h6V7a3 3 0 0 0-3-3Zm0 9a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/></svg>
            </div>
            <p class="reg-req-empty-title">No pending reset requests</p>
            <p class="reg-req-empty-sub">New password reset requests will appear here for administrator review.</p>
        </div>

        <div class="table-wrap reg-req-table-wrap<?= $pendingCount > 0 ? '' : ' is-hidden' ?>" data-pwd-reset-table-wrap>
            <table class="data-table reg-req-table no-row-details" data-no-tools data-no-enhance>
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Account ID</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody data-pwd-reset-tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr data-pwd-reset-row data-request-id="<?= (int)$request['id'] ?>">
                            <td><strong><?= e($request['user_name'] ?? 'User') ?></strong></td>
                            <td><?= e($model->roleLabel((string)($request['role'] ?? ''))) ?></td>
                            <td><?= e($request['email'] ?? '') ?></td>
                            <td><span class="asu-usn-badge"><?= e($request['identifier'] ?? '') ?></span></td>
                            <td><?= e(date('M d, Y g:i A', strtotime((string)$request['created_at']))) ?></td>
                            <td>
                                <div class="reg-req-actions">
                                    <form method="post" action="index.php?r=admin_password_reset_requests" class="inline" data-pwd-reset-form data-pwd-reset-decision="approve">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="admin_review_password_reset_request">
                                        <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                                        <input type="hidden" name="decision" value="approve">
                                        <button class="btn btn-small btn-primary" type="submit" data-pwd-reset-submit>
                                            <span class="btn-text">Approve &amp; Send Link</span>
                                            <span class="spinner" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                    <details class="reg-req-decline">
                                        <summary class="btn btn-small btn-ghost">Reject</summary>
                                        <form method="post" action="index.php?r=admin_password_reset_requests" class="reg-req-decline-form" data-pwd-reset-form data-pwd-reset-decision="reject">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="admin_review_password_reset_request">
                                            <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                                            <input type="hidden" name="decision" value="reject">
                                            <label>
                                                <span>Reason (optional)</span>
                                                <textarea name="decline_reason" rows="2" placeholder="Optional note for internal reference"></textarea>
                                            </label>
                                            <button class="btn btn-small btn-danger-outline" type="submit" data-pwd-reset-submit>
                                                <span class="btn-text">Confirm Reject</span>
                                                <span class="spinner" aria-hidden="true"></span>
                                            </button>
                                        </form>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
