<?php
class User
{
    public function __construct(private PDO $db) {}

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
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

    public function countRole(string $role): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE role = ? AND is_active = 1');
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }
}
