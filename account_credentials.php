<h2>AMA Practicum System Account</h2>
<p>Hello <?= e($name ?? 'User') ?>,</p>
<p>Your <?= e($roleLabel ?? 'account') ?> account has been created.</p>
<ul>
    <?php if (!empty($usn ?? '')): ?><li><strong>USN:</strong> <?= e($usn) ?></li><?php endif; ?>
    <li><strong>Email:</strong> <?= e($email ?? '') ?></li>
    <li><strong>Temporary Password:</strong> <?= e($password ?? '') ?></li>
</ul>
<?php if (!empty($loginUrl ?? '')): ?>
    <p><strong>Login Portal:</strong> <a href="<?= e($loginUrl) ?>"><?= e($loginUrl) ?></a></p>
<?php endif; ?>
<p>Please log in and change your temporary password on first login.</p>
<p>Thank you,<br>AMA Practicum System</p>
