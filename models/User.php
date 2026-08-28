<?php
class User
{
    private static ?bool $coordinatorIdNumberReady = null;
    private static ?bool $coordinatorSignatureReady = null;
    private static ?bool $namePartsReady = null;
    private static ?bool $deactivationReady = null;
    private static ?bool $lastLoginReady = null;
    private static ?bool $lastLogoutReady = null;

    public function __construct(private PDO $db) {}

    public function ensureNamePartsSupport(): void
    {
        if (self::$namePartsReady === true) {
            return;
        }

        // Production DB is migrated via deployment scripts; skip runtime ALTER/backfill.
        if (!APP_IS_LOCAL) {
            self::$namePartsReady = true;
            return;
        }

        foreach ([
            'first_name' => 'VARCHAR(100) NULL AFTER name',
            'middle_name' => 'VARCHAR(100) NULL AFTER first_name',
            'last_name' => 'VARCHAR(100) NULL AFTER middle_name',
        ] as $column => $definition) {
            $stmt = $this->db->query("SHOW COLUMNS FROM users LIKE '{$column}'");
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
            }
        }

        $rows = $this->db->query('SELECT id, name, first_name, middle_name, last_name FROM users')->fetchAll();
        $update = $this->db->prepare(
            'UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, name = ? WHERE id = ?'
        );
        foreach ($rows as $row) {
            $firstName = trim((string)($row['first_name'] ?? ''));
            $lastName = trim((string)($row['last_name'] ?? ''));
            if ($firstName !== '' && $lastName !== '') {
                continue;
            }
            $parts = split_person_name((string)($row['name'] ?? ''));
            $update->execute([
                $parts['first_name'],
                $parts['middle_name'] !== '' ? $parts['middle_name'] : null,
                $parts['last_name'],
                full_name_from_parts($parts['first_name'], $parts['last_name'], $parts['middle_name'] ?: null),
                (int)$row['id'],
            ]);
        }

        self::$namePartsReady = true;
    }

    public function ensureCoordinatorSignatureSupport(): void
    {
        if (self::$coordinatorSignatureReady === true) {
            return;
        }

        $columnStmt = $this->db->prepare("SHOW COLUMNS FROM coordinators LIKE 'signature_file'");
        $columnStmt->execute();
        $hasColumn = (bool)$columnStmt->fetch();

        if (!$hasColumn) {
            $this->db->exec('ALTER TABLE coordinators ADD COLUMN signature_file VARCHAR(255) NULL AFTER department');
        }

        self::$coordinatorSignatureReady = true;
    }

    public function ensureCoordinatorIdNumberSupport(): void
    {
        if (self::$coordinatorIdNumberReady === true) {
            return;
        }

        $columnStmt = $this->db->prepare("SHOW COLUMNS FROM coordinators LIKE 'id_number'");
        $columnStmt->execute();
        $hasColumn = (bool)$columnStmt->fetch();

        if (!$hasColumn) {
            $this->db->exec('ALTER TABLE coordinators ADD COLUMN id_number VARCHAR(60) NULL AFTER user_id');
        }

        $indexStmt = $this->db->prepare("SHOW INDEX FROM coordinators WHERE Key_name = 'uq_coordinators_id_number'");
        $indexStmt->execute();
        $hasIndex = (bool)$indexStmt->fetch();

        if (!$hasIndex) {
            $this->db->exec('ALTER TABLE coordinators ADD UNIQUE KEY uq_coordinators_id_number (id_number)');
        }

        self::$coordinatorIdNumberReady = true;
    }

    public function findByEmail(string $email): ?array
    {
        $this->ensureNamePartsSupport();
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return hydrate_user_record($stmt->fetch() ?: null);
    }

    public function findForLogin(string $identifier, ?string $portalRole = null): ?array
    {
        $this->ensureNamePartsSupport();
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        if ($portalRole === 'student') {
            $stmt = $this->db->prepare(
                'SELECT u.*, COALESCE(s.student_no, r.student_no) AS student_no
                 FROM users u
                 LEFT JOIN students s ON s.user_id = u.id
                 LEFT JOIN student_registration_requests r
                    ON r.user_id = u.id
                   AND r.status IN ("pending_approval", "pending", "approved")
                 WHERE u.role = "student"
                   AND (u.email = ? OR s.student_no = ? OR r.student_no = ?)
                 LIMIT 1'
            );
            $stmt->execute([strtolower($identifier), $identifier, $identifier]);
            return hydrate_user_record($stmt->fetch() ?: null);
        }

        if ($portalRole === 'partner') {
            $byEmail = $this->findByEmail(strtolower($identifier));
            if ($byEmail && ($byEmail['role'] ?? '') === 'partner') {
                return $byEmail;
            }

            $company = (new Company($this->db))->findByPartnerId(strtoupper($identifier));
            if (!$company && strtoupper($identifier) !== $identifier) {
                $company = (new Company($this->db))->findByPartnerId($identifier);
            }
            if ($company && !empty($company['user_id'])) {
                $partnerUser = $this->find((int)$company['user_id']);
                if ($partnerUser && ($partnerUser['role'] ?? '') === 'partner') {
                    return $partnerUser;
                }
            }
            return null;
        }

        return $this->findByEmail(strtolower($identifier));
    }

    public function find(int $id): ?array
    {
        $this->ensureNamePartsSupport();
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return hydrate_user_record($stmt->fetch() ?: null);
    }

    public function findWithDetails(int $id): ?array
    {
        $this->ensureNamePartsSupport();
        $stmt = $this->db->prepare(
            'SELECT u.*, s.student_no
             FROM users u
             LEFT JOIN students s ON s.user_id = u.id
             WHERE u.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        return hydrate_user_record($stmt->fetch() ?: null);
    }

    public function create(
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        string $role,
        ?int $createdBy = null,
        int $passwordChanged = 1,
        ?string $middleName = null,
        int $isActive = 1
    ): int {
        $this->ensureNamePartsSupport();
        [$firstName, $middleName, $lastName, $fullName] = $this->normalizeNameParts($firstName, $lastName, $middleName);
        if (!filter_var(strtolower(trim($email)), FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid email address is required.');
        }
        if (!in_array($role, ['admin', 'coordinator', 'student', 'partner'], true)) {
            throw new RuntimeException('Invalid user role.');
        }
        $stmt = $this->db->prepare(
            'INSERT INTO users (first_name, middle_name, last_name, name, email, password_hash, role, created_by, is_active, password_changed)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $firstName,
            $middleName,
            $lastName,
            $fullName,
            strtolower(trim($email)),
            password_hash($password, PASSWORD_DEFAULT),
            $role,
            $createdBy,
            $isActive ? 1 : 0,
            $passwordChanged,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function createWithPasswordHash(
        string $firstName,
        string $lastName,
        string $email,
        string $passwordHash,
        string $role,
        ?int $createdBy = null,
        int $passwordChanged = 1,
        int $isActive = 1,
        ?string $middleName = null
    ): int {
        $this->ensureNamePartsSupport();
        [$firstName, $middleName, $lastName, $fullName] = $this->normalizeNameParts($firstName, $lastName, $middleName);
        if (!filter_var(strtolower(trim($email)), FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid email address is required.');
        }
        if (!in_array($role, ['admin', 'coordinator', 'student', 'partner'], true)) {
            throw new RuntimeException('Invalid user role.');
        }
        $stmt = $this->db->prepare(
            'INSERT INTO users (first_name, middle_name, last_name, name, email, password_hash, role, created_by, is_active, password_changed)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $firstName,
            $middleName,
            $lastName,
            $fullName,
            strtolower(trim($email)),
            $passwordHash,
            $role,
            $createdBy,
            $isActive,
            $passwordChanged,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function all(): array
    {
        $this->ensureNamePartsSupport();
        $rows = $this->db->query(
            'SELECT u.*, c.name created_by_name, s.student_no, s.course
             FROM users u
             LEFT JOIN users c ON c.id = u.created_by
             LEFT JOIN students s ON s.user_id = u.id
             ORDER BY u.last_name ASC, u.first_name ASC, u.created_at DESC'
        )->fetchAll();
        return array_map(static fn ($row) => hydrate_user_record($row), $rows);
    }

    public function allStudents(): array
    {
        $this->ensureNamePartsSupport();
        $rows = $this->db->query(
            'SELECT u.*, s.student_no, s.course
             FROM users u
             JOIN students s ON s.user_id = u.id
             ORDER BY u.last_name ASC, u.first_name ASC'
        )->fetchAll();
        return array_map(static fn ($row) => hydrate_user_record($row), $rows);
    }

    public function byRole(string $role): array
    {
        $this->ensureNamePartsSupport();
        if ($role === 'coordinator') {
            $this->ensureCoordinatorIdNumberSupport();
            $this->ensureCoordinatorSignatureSupport();
            $stmt = $this->db->prepare(
                'SELECT u.*, c.id_number, c.department, c.signature_file
                 FROM users u
                 LEFT JOIN coordinators c ON c.user_id = u.id
                 WHERE u.role = ?
                 ORDER BY u.last_name ASC, u.first_name ASC, u.id DESC'
            );
            $stmt->execute([$role]);
            return array_map(static fn ($row) => hydrate_user_record($row), $stmt->fetchAll());
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE role = ? ORDER BY last_name ASC, first_name ASC'
        );
        $stmt->execute([$role]);
        return array_map(static fn ($row) => hydrate_user_record($row), $stmt->fetchAll());
    }

    public function setActive(int $id, int $active): void
    {
        $this->ensureDeactivationSupport();
        if ($active) {
            $stmt = $this->db->prepare(
                'UPDATE users SET is_active = 1, deactivation_reason = NULL, deactivation_notes = NULL, deactivated_at = NULL WHERE id = ?'
            );
            $stmt->execute([$id]);
            return;
        }

        $stmt = $this->db->prepare('UPDATE users SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function deactivate(int $id, string $reason, ?string $notes = null): void
    {
        $this->ensureDeactivationSupport();
        $allowed = ['dropped', 'complete_ojt', 'failed', 'other'];
        if (!in_array($reason, $allowed, true)) {
            throw new RuntimeException('Invalid deactivation reason.');
        }
        if ($reason === 'other' && trim((string)$notes) === '') {
            throw new RuntimeException('Please provide details for the deactivation reason.');
        }

        $stmt = $this->db->prepare(
            'UPDATE users SET is_active = 0, deactivation_reason = ?, deactivation_notes = ?, deactivated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([
            $reason,
            $reason === 'other' ? trim((string)$notes) : null,
            $id,
        ]);
    }

    public function ensureDeactivationSupport(): void
    {
        if (self::$deactivationReady === true) {
            return;
        }

        foreach ([
            'deactivation_reason' => "VARCHAR(40) NULL AFTER is_active",
            'deactivation_notes' => 'TEXT NULL AFTER deactivation_reason',
            'deactivated_at' => 'DATETIME NULL AFTER deactivation_notes',
        ] as $column => $definition) {
            $stmt = $this->db->query("SHOW COLUMNS FROM users LIKE '{$column}'");
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
            }
        }

        self::$deactivationReady = true;
    }

    public function ensureLastLoginSupport(): void
    {
        if (self::$lastLoginReady === true) {
            return;
        }

        if (!APP_IS_LOCAL) {
            self::$lastLoginReady = true;
            return;
        }

        foreach ([
            'last_login_at' => 'DATETIME NULL AFTER password_changed',
            'last_login_ip' => 'VARCHAR(45) NULL AFTER last_login_at',
            'last_login_device' => 'VARCHAR(190) NULL AFTER last_login_ip',
        ] as $column => $definition) {
            $stmt = $this->db->query("SHOW COLUMNS FROM users LIKE '{$column}'");
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
            }
        }

        self::$lastLoginReady = true;
    }

    public function recordLogin(int $id): void
    {
        $this->ensureLastLoginSupport();
        $stmt = $this->db->prepare(
            'UPDATE users SET last_login_at = NOW(), last_login_ip = ?, last_login_device = ? WHERE id = ?'
        );
        $stmt->execute([client_ip(), client_device_label(), $id]);
    }

    public function ensureLastLogoutSupport(): void
    {
        if (self::$lastLogoutReady === true) {
            return;
        }

        $this->ensureLastLoginSupport();

        foreach ([
            'last_logout_at' => 'DATETIME NULL AFTER last_login_device',
            'last_logout_ip' => 'VARCHAR(45) NULL AFTER last_logout_at',
            'last_logout_device' => 'VARCHAR(190) NULL AFTER last_logout_ip',
        ] as $column => $definition) {
            $stmt = $this->db->query("SHOW COLUMNS FROM users LIKE '{$column}'");
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
            }
        }

        self::$lastLogoutReady = true;
    }

    public function recordLogout(int $id): void
    {
        if ($id <= 0) {
            return;
        }
        $this->ensureLastLoginSupport();
        $this->ensureLastLogoutSupport();
        $stmt = $this->db->prepare(
            'UPDATE users SET last_logout_at = NOW(), last_logout_ip = ?, last_logout_device = ? WHERE id = ?'
        );
        $stmt->execute([client_ip(), client_device_label(), $id]);
    }

    public function updatePassword(int $id, string $password, int $passwordChanged = 1): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = ?, password_changed = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $passwordChanged, $id]);
    }

    public function verifyPassword(int $id, string $password): bool
    {
        $user = $this->find($id);
        if (!$user) {
            return false;
        }
        return password_verify($password, (string)$user['password_hash']);
    }

    public function updatePersonName(int $id, string $firstName, string $lastName, ?string $middleName = null): void
    {
        $this->ensureNamePartsSupport();
        [$firstName, $middleName, $lastName, $fullName] = $this->normalizeNameParts($firstName, $lastName, $middleName);
        $stmt = $this->db->prepare(
            'UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, name = ? WHERE id = ?'
        );
        $stmt->execute([$firstName, $middleName, $lastName, $fullName, $id]);
    }

    public function updateName(int $id, string $name): void
    {
        $parts = split_person_name($name);
        $this->updatePersonName(
            $id,
            $parts['first_name'],
            $parts['last_name'],
            $parts['middle_name'] !== '' ? $parts['middle_name'] : null
        );
    }

    public function updateEmail(int $id, string $email): void
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid email address is required.');
        }
        $check = $this->db->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $check->execute([$email, $id]);
        if ($check->fetch()) {
            throw new RuntimeException('That email address is already in use by another account.');
        }
        $stmt = $this->db->prepare('UPDATE users SET email = ? WHERE id = ?');
        $stmt->execute([$email, $id]);
    }

    public function countRole(string $role): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE role = ? AND is_active = 1');
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }

    public function countRoleCreatedInLastDays(string $role, int $days): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM users
             WHERE role = ? AND is_active = 1
               AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([$role, max(1, $days)]);
        return (int)$stmt->fetchColumn();
    }

    public function countRoleCreatedBetweenDays(string $role, int $olderThanDays, int $newerThanDays): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM users
             WHERE role = ? AND is_active = 1
               AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([$role, max(1, $olderThanDays), max(0, $newerThanDays)]);
        return (int)$stmt->fetchColumn();
    }

    private function normalizeNameParts(string $firstName, string $lastName, ?string $middleName = null): array
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $middleName = trim((string)$middleName);
        if ($firstName === '' || $lastName === '') {
            throw new RuntimeException('First name and last name are required.');
        }
        $storedMiddle = $middleName !== '' ? $middleName : null;
        return [$firstName, $storedMiddle, $lastName, full_name_from_parts($firstName, $lastName, $storedMiddle)];
    }
}
