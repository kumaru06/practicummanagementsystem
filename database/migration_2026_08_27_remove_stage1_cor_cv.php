<?php
/**
 * One-shot: remove orphan stage-1 COR/CV requirement rows and reconcile predeployment statuses.
 *
 * Run once after deploy (CLI):
 *   php database/migration_2026_08_27_remove_stage1_cor_cv.php
 *
 * Safe to re-run: DELETE is idempotent; reconcile only updates when status should change.
 * Does not touch registration COR (students.cor_file) or advanced pipeline statuses.
 */
declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/bootstrap/env.php';

// CLI has no HTTP host. Hostinger sites live under ~/domains/{site}/public_html.
if (PHP_SAPI === 'cli' && empty($_SERVER['HTTP_HOST']) && empty($_SERVER['SERVER_NAME'])) {
    $domainsParent = dirname(dirname($root));
    $onHostinger = is_dir($domainsParent) && basename($domainsParent) === 'domains';
    if ($onHostinger || is_file($root . '/config/database.production.php')) {
        $_SERVER['HTTP_HOST'] = 'ama-ojtportal.com';
    } else {
        $_SERVER['HTTP_HOST'] = 'localhost';
    }
}

require_once $root . '/config/database.php';
require_once $root . '/helpers.php';

spl_autoload_register(static function (string $class) use ($root): void {
    foreach (['models', 'controllers'] as $dir) {
        $path = $root . '/' . $dir . '/' . $class . '.php';
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});

$pdo = db();

$delete = $pdo->prepare('DELETE FROM student_requirements WHERE requirement_key IN (?, ?)');
$delete->execute(['cor', 'cv']);
$deleted = $delete->rowCount();
echo "Deleted orphan cor/cv requirement rows: {$deleted}" . PHP_EOL;

$studentModel = new Student($pdo);
$result = $studentModel->reconcileAllPredeploymentAfterRequirementDefChange();
echo 'Reconciled enrollments scanned: ' . $result['scanned'] . PHP_EOL;
echo 'Predeployment statuses updated: ' . $result['updated'] . PHP_EOL;
echo 'Unchanged: ' . $result['unchanged'] . PHP_EOL;
echo 'Done.' . PHP_EOL;
