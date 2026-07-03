<?php
class User
{
    private ?bool $coordinatorIdNumberReady = null;
    private ?bool $coordinatorSignatureReady = null;
    private ?bool $namePartsReady = null;

    public function __construct(private PDO $db) {}

    public function ensureNamePartsSupport(): void
    {
        if ($this->namePartsReady === true) {
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

        $this->namePartsReady = true;
    }

    public function ensureCoordinatorSignatureSupport(): void
    {
        if ($this->coordinatorSignatureReady === true) {
            return;
        }

        $columnStmt = $this->db->prepare("SHOW COLUMNS FROM coordinators LIKE 'signature_file'");
        $columnStmt->execute();
        $hasColumn = (bool)$columnStmt->fetch();

        if (!$hasColumn) {
            $this->db->exec('ALTER TABLE coordinators ADD COLUMN signature_file VARCHAR(255) NULL AFTER department');
        }

        $this->coordinatorSignatureReady = true;
    }

    public function ensureCoordinatorIdNumberSupport(): void
    {
        if ($this->coordinatorIdNumberReady === true) {
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

        $this->coordinatorIdNumberReady = true;
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
                'SELECT u.*, s.student_no
                 FROM users u
                 LEFT JOIN students s ON s.user_id = u.id
                 WHERE u.role = "student" AND (u.email = ? OR s.student_no = ?)
                 LIMIT 1'
            );
            $stmt->execute([strtolower($identifier), $identifier]);
            return hydrate_user_record($stmt->fetch() ?: null);
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
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
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
        $stmt = $this->db->prepare('UPDATE users SET is_active = ? WHERE id = ?');
        $stmt->execute([$active, $id]);
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
