<?php
class User
{
    private ?bool $coordinatorIdNumberReady = null;
    private ?bool $coordinatorSignatureReady = null;

    public function __construct(private PDO $db) {}

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
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findForLogin(string $identifier, ?string $portalRole = null): ?array
    {
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
            return $stmt->fetch() ?: null;
        }

        return $this->findByEmail(strtolower($identifier));
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, s.student_no
             FROM users u
             LEFT JOIN students s ON s.user_id = u.id
             WHERE u.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $email, string $password, string $role, ?int $createdBy = null, int $passwordChanged = 1): int
    {
        if (!trim($name)) {
            throw new RuntimeException('Name is required.');
        }
        if (!filter_var(strtolower(trim($email)), FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid email address is required.');
        }
        if (!in_array($role, ['admin', 'coordinator', 'student', 'partner'], true)) {
            throw new RuntimeException('Invalid user role.');
        }
        $stmt = $this->db->prepare('INSERT INTO users (name, email, password_hash, role, created_by, is_active, password_changed) VALUES (?, ?, ?, ?, ?, 1, ?)');
        $stmt->execute([trim($name), strtolower(trim($email)), password_hash($password, PASSWORD_DEFAULT), $role, $createdBy, $passwordChanged]);
        return (int)$this->db->lastInsertId();
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT u.*, c.name created_by_name, s.student_no, s.course
             FROM users u
             LEFT JOIN users c ON c.id = u.created_by
             LEFT JOIN students s ON s.user_id = u.id
             ORDER BY u.created_at DESC'
        )->fetchAll();
    }

    public function allStudents(): array
    {
        return $this->db->query(
            'SELECT u.*, s.student_no, s.course
             FROM users u
             JOIN students s ON s.user_id = u.id
             ORDER BY u.name ASC'
        )->fetchAll();
    }

    public function byRole(string $role): array
    {
        if ($role === 'coordinator') {
            $this->ensureCoordinatorIdNumberSupport();
            $this->ensureCoordinatorSignatureSupport();
            $stmt = $this->db->prepare(
                'SELECT u.*, c.id_number, c.department, c.signature_file
                 FROM users u
                 LEFT JOIN coordinators c ON c.user_id = u.id
                 WHERE u.role = ?
                 ORDER BY u.id DESC'
            );
            $stmt->execute([$role]);
            return $stmt->fetchAll();
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE role = ? ORDER BY name');
        $stmt->execute([$role]);
        return $stmt->fetchAll();
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

    public function updateName(int $id, string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Company name is required.');
        }
        $stmt = $this->db->prepare('UPDATE users SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);
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
}
