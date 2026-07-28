<?php
class PasswordResetRequest
{
    public const RESET_LINK_HOURS = 1;

    public function __construct(private PDO $db) {}

    public function ensureTable(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS password_reset_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                role ENUM('student','coordinator','partner') NOT NULL,
                email VARCHAR(190) NOT NULL,
                identifier VARCHAR(60) NOT NULL,
                status ENUM('pending','approved','rejected','completed','expired') NOT NULL DEFAULT 'pending',
                reset_token VARCHAR(64) NULL,
                reset_expires_at DATETIME NULL,
                reviewed_by INT NULL,
                reviewed_at DATETIME NULL,
                decline_reason TEXT NULL,
                completed_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_password_reset_status (status),
                INDEX idx_password_reset_user (user_id),
                INDEX idx_password_reset_token (reset_token),
                CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_password_reset_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );

        $ready = true;
    }

    public function validateCredentials(string $role, string $email, string $identifier): ?array
    {
        $this->ensureTable();
        $email = strtolower(trim($email));
        $identifier = trim($identifier);
        if ($email === '' || $identifier === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        if ($role === 'student') {
            $stmt = $this->db->prepare(
                'SELECT u.*, s.student_no
                 FROM users u
                 JOIN students s ON s.user_id = u.id
                 WHERE u.role = "student" AND u.email = ? AND s.student_no = ? AND u.is_active = 1
                 LIMIT 1'
            );
            $stmt->execute([$email, $identifier]);
            return hydrate_user_record($stmt->fetch() ?: null);
        }

        if ($role === 'coordinator') {
            (new User($this->db))->ensureCoordinatorIdNumberSupport();
            $stmt = $this->db->prepare(
                'SELECT u.*
                 FROM users u
                 JOIN coordinators c ON c.user_id = u.id
                 WHERE u.role = "coordinator" AND u.email = ? AND c.id_number = ? AND u.is_active = 1
                 LIMIT 1'
            );
            $stmt->execute([$email, $identifier]);
            return hydrate_user_record($stmt->fetch() ?: null);
        }

        if ($role === 'partner') {
            (new Company($this->db))->ensurePartnerIdSupport();
            $normalizedId = preg_replace('/^IP-/i', 'HTE-', trim($identifier)) ?: trim($identifier);
            $stmt = $this->db->prepare(
                'SELECT u.*
                 FROM users u
                 JOIN partner_companies pc ON pc.user_id = u.id
                 WHERE u.role = "partner" AND u.email = ? AND pc.partner_id = ? AND u.is_active = 1
                 LIMIT 1'
            );
            $stmt->execute([$email, $normalizedId]);
            return hydrate_user_record($stmt->fetch() ?: null);
        }

        return null;
    }

    public function hasPendingForUser(int $userId): bool
    {
        $this->ensureTable();
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM password_reset_requests WHERE user_id = ? AND status = 'pending'"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(int $userId, string $role, string $email, string $identifier): int
    {
        $this->ensureTable();
        $stmt = $this->db->prepare(
            'INSERT INTO password_reset_requests (user_id, role, email, identifier, status)
             VALUES (?, ?, ?, ?, "pending")'
        );
        $stmt->execute([
            $userId,
            $role,
            strtolower(trim($email)),
            trim($identifier),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT * FROM password_reset_requests WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByToken(string $token): ?array
    {
        $resolved = $this->resolveToken($token);
        return $resolved['request'];
    }

    /**
     * @return array{request: ?array, error: ?string, role: ?string}
     */
    public function resolveToken(string $token): array
    {
        $this->ensureTable();
        $token = trim($token);
        if ($token === '') {
            return ['request' => null, 'error' => null, 'role' => null];
        }

        $record = $this->findByTokenRecord($token);
        if (!$record) {
            return [
                'request' => null,
                'error' => 'This password reset link is invalid.',
                'role' => null,
            ];
        }

        $role = (string)($record['role'] ?? '');
        $status = (string)($record['status'] ?? '');

        if ($status === 'completed') {
            return [
                'request' => null,
                'error' => 'This password reset link has already been used. Please sign in or submit a new reset request.',
                'role' => $role,
            ];
        }

        if ($status !== 'approved') {
            return [
                'request' => null,
                'error' => 'This password reset link is invalid or is no longer active.',
                'role' => $role,
            ];
        }

        if ($this->isExpired($record)) {
            return [
                'request' => null,
                'error' => 'This password reset link has expired. Please submit a new password reset request.',
                'role' => $role,
            ];
        }

        return ['request' => $record, 'error' => null, 'role' => $role];
    }

    public function findByTokenRecord(string $token): ?array
    {
        $this->ensureTable();
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT pr.*, u.name AS user_name
             FROM password_reset_requests pr
             JOIN users u ON u.id = pr.user_id
             WHERE pr.reset_token = ?
             LIMIT 1"
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function isExpired(array $record): bool
    {
        if (empty($record['reset_expires_at'])) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT reset_expires_at <= NOW() FROM password_reset_requests WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int)$record['id']]);
        return (bool)$stmt->fetchColumn();
    }

    public function pendingCount(): int
    {
        $this->ensureTable();
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM password_reset_requests WHERE status = 'pending'"
        )->fetchColumn();
    }

    public function allPending(): array
    {
        $this->ensureTable();
        return $this->db->query(
            "SELECT pr.*, u.name AS user_name
             FROM password_reset_requests pr
             JOIN users u ON u.id = pr.user_id
             WHERE pr.status = 'pending'
             ORDER BY pr.created_at ASC"
        )->fetchAll();
    }

    public function approve(int $id, int $reviewedBy): string
    {
        $this->ensureTable();
        $request = $this->find($id);
        if (!$request || ($request['status'] ?? '') !== 'pending') {
            throw new RuntimeException('Password reset request not found or already processed.');
        }

        $token = bin2hex(random_bytes(32));
        $stmt = $this->db->prepare(
            "UPDATE password_reset_requests
             SET status = 'approved',
                 reset_token = ?,
                 reset_expires_at = DATE_ADD(NOW(), INTERVAL " . self::RESET_LINK_HOURS . " HOUR),
                 reviewed_by = ?,
                 reviewed_at = NOW()
             WHERE id = ? AND status = 'pending'"
        );
        $stmt->execute([$token, $reviewedBy, $id]);
        return $token;
    }

    public function reject(int $id, int $reviewedBy, ?string $reason = null): void
    {
        $this->ensureTable();
        $stmt = $this->db->prepare(
            "UPDATE password_reset_requests
             SET status = 'rejected',
                 decline_reason = ?,
                 reviewed_by = ?,
                 reviewed_at = NOW()
             WHERE id = ? AND status = 'pending'"
        );
        $stmt->execute([
            $reason !== null && trim($reason) !== '' ? trim($reason) : null,
            $reviewedBy,
            $id,
        ]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Password reset request not found or already processed.');
        }
    }

    public function markCompleted(int $id): void
    {
        $this->ensureTable();
        $stmt = $this->db->prepare(
            "UPDATE password_reset_requests
             SET status = 'completed',
                 completed_at = NOW(),
                 reset_token = NULL
             WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    public function identifierLabel(string $role): string
    {
        return match ($role) {
            'student' => 'USN (Student ID)',
            'coordinator' => 'Coordinator ID',
            'partner' => 'HTE ID',
            default => 'Account ID',
        };
    }

    public function roleLabel(string $role): string
    {
        return match ($role) {
            'student' => 'Student',
            'coordinator' => 'OJT Coordinator',
            'partner' => 'Host Training Establishment',
            default => ucfirst($role),
        };
    }
}
