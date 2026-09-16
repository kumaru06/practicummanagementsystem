<?php
declare(strict_types=1);

/**
 * Admin password AUDIT + ROTATION tool (CLI only).
 *
 * Usage:
 *   php tools/set-admin-password.php [email] [--local] [--yes] [--password=SECRET]
 *
 * Default is a DRY RUN (report only): shows the target database, whether the admin
 * row exists, whether the legacy public hash from the old seed.sql is still in use,
 * and the is_active / password_changed flags. This answers "are the seeded
 * credentials still in use on the deployed database?" without changing anything.
 *
 * Flags:
 *   --yes              Actually write. Sets a NEW strong password and unlocks login.
 *   --local            Target the Laragon local DB (practicum_system) instead of the
 *                      deployed config. Run WITHOUT this on the server to hit production.
 *   --password=SECRET  Use this exact password (min 8) instead of a generated one.
 *
 * Behaviour on --yes:
 *   - Generated random password  -> password_changed = 0 (admin must set their own on first login).
 *   - Explicit --password=...     -> password_changed = 1 (used as-is).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

$root = dirname(__DIR__);
$args = array_slice($argv, 1);
$flags = array_values(array_filter($args, static fn ($a) => str_starts_with((string)$a, '--')));
$positional = array_values(array_filter($args, static fn ($a) => !str_starts_with((string)$a, '--')));

$hasFlag = static fn (string $name): bool => in_array('--' . $name, $flags, true);
$flagValue = static function (string $name) use ($flags): ?string {
    foreach ($flags as $f) {
        if (str_starts_with((string)$f, '--' . $name . '=')) {
            return substr((string)$f, strlen('--' . $name . '='));
        }
    }
    return null;
};

$email = strtolower(trim((string)($positional[0] ?? 'admin@ama.edu.ph')));
$doWrite = $hasFlag('yes');

if ($hasFlag('local')) {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['SERVER_NAME'] = 'localhost';
}

require $root . '/bootstrap/env.php';
require $root . '/config/database.php';
require $root . '/helpers.php';

// The public password hash shipped in database/seed.sql before this hardening.
const LEGACY_ADMIN_HASH = '$2y$10$9CHq.Pz4X5vuhbXpv5DE6O6FOtawSqC7eoj/kXj6UBJ3jgHH8Rp/O';

fwrite(STDOUT, 'Target database: host=' . _DB_HOST . ' name=' . _DB_NAME . ' (source: ' . _DB_CONFIG_SOURCE . ")\n");

try {
    $db = db();
} catch (Throwable $e) {
    fwrite(STDERR, 'DB connection failed: ' . $e->getMessage() . "\n");
    exit(1);
}

$stmt = $db->prepare("SELECT id, email, password_hash, is_active, password_changed FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
$stmt->execute([$email]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    fwrite(STDOUT, "No admin user found with email {$email}.\n");
    if (!$doWrite) {
        fwrite(STDOUT, "Re-run with --yes to CREATE this admin with a new password.\n");
        exit(0);
    }
    $explicit = $flagValue('password');
    if ($explicit !== null && strlen($explicit) < 8) {
        fwrite(STDERR, "Provided password is too short (min 8). Aborting.\n");
        exit(1);
    }
    $password = $explicit ?? random_password(16);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $passwordChanged = $explicit !== null ? 1 : 0;
    $ins = $db->prepare("INSERT INTO users (name, email, password_hash, role, is_active, password_changed) VALUES (?, ?, ?, 'admin', 1, ?)");
    $ins->execute(['System Administrator', $email, $hash, $passwordChanged]);
    fwrite(STDOUT, "Created admin {$email}.\n");
    if ($explicit === null) {
        fwrite(STDOUT, "Temporary password (change on first login): {$password}\nStore it now; it will not be shown again.\n");
    }
    exit(0);
}

$currentHash = (string)$admin['password_hash'];
$legacyInUse = hash_equals(LEGACY_ADMIN_HASH, $currentHash);
$isValidBcrypt = (bool)preg_match('/^\$2[aby]\$/', $currentHash);

fwrite(STDOUT, "Admin found: id={$admin['id']} active=" . (int)$admin['is_active'] . ' password_changed=' . (int)$admin['password_changed'] . "\n");
fwrite(STDOUT, 'Legacy public hash in use: ' . ($legacyInUse ? 'YES  <-- INSECURE, rotate immediately' : 'no') . "\n");
if (!$isValidBcrypt && !$legacyInUse) {
    fwrite(STDOUT, "Current hash is a locked placeholder (no login possible until a password is set).\n");
}

if (!$doWrite) {
    fwrite(STDOUT, "\nDry run only. Re-run with --yes to set a new strong password.\n");
    exit(0);
}

$explicit = $flagValue('password');
if ($explicit !== null && strlen($explicit) < 8) {
    fwrite(STDERR, "Provided password is too short (min 8). Aborting.\n");
    exit(1);
}
$password = $explicit ?? random_password(16);
$passwordChanged = $explicit !== null ? 1 : 0;
$hash = password_hash($password, PASSWORD_DEFAULT);
$upd = $db->prepare('UPDATE users SET password_hash = ?, password_changed = ?, is_active = 1 WHERE id = ?');
$upd->execute([$hash, $passwordChanged, (int)$admin['id']]);
fwrite(STDOUT, "\nAdmin password updated for {$email}.\n");
if ($explicit === null) {
    fwrite(STDOUT, "Temporary password (change on first login): {$password}\nStore it now; it will not be shown again.\n");
}
exit(0);
