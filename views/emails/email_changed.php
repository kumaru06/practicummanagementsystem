<h2>Your Login Email Has Been Updated</h2>
<p>Hello <?= e($studentName ?? 'Student') ?>,</p>
<p>Your OJT portal login email address has been changed by your coordinator.</p>
<ul>
    <li><strong>New Email / Username:</strong> <?= e($newEmail ?? '') ?></li>
    <li><strong>Password:</strong> Unchanged — use your current password to log in.</li>
</ul>
<p>If you did not request this change, please contact your OJT coordinator immediately.</p>
<p>Thank you,<br>AMA Practicum System</p>
