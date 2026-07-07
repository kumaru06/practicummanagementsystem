<h2>Your Portal Account Has Been Deactivated</h2>
<p>Hello <?= e($studentName ?? 'Student') ?>,</p>
<p>Your AMA Practicum portal account has been deactivated by the system administrator.</p>
<ul>
    <li><strong>Reason:</strong> <?= e($reasonLabel ?? 'Not specified') ?></li>
</ul>
<p>You will no longer be able to sign in until your account is reactivated by an administrator.</p>
<p>If you believe this was done in error, please contact your OJT coordinator or email <?= e($supportEmail ?? 'the school administrator') ?>.</p>
<p>Thank you,<br>AMA Practicum System</p>
