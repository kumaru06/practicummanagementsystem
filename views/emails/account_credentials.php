<h2>AMA Practicum System Account</h2>
<p>Hello <?= e($name ?? 'User') ?>,</p>
<p>Your <?= e($roleLabel ?? 'account') ?> account has been created.</p>
<ul>
    <li><strong>Username:</strong> <?= e($email ?? '') ?></li>
    <li><strong>Temporary Password:</strong> <?= e($password ?? '') ?></li>
</ul>
<p>Please log in and change your temporary password on first login.</p>
<p>Thank you,<br>AMA Practicum System</p>
