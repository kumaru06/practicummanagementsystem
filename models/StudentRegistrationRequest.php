<?php
class StudentRegistrationRequest
{
    public const VERIFICATION_HOURS = 12;

    public function __construct(private PDO $db) {}

    public function ensureTable(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS student_registration_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                first_name VARCHAR(100) NOT NULL,
                middle_name VARCHAR(100) NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(190) NOT NULL,
                student_no VARCHAR(60) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                cor_file VARCHAR(255) NOT NULL,
                verification_token VARCHAR(64) NULL,
                verification_expires_at DATETIME NULL,
                email_verified_at DATETIME NULL,
                user_id INT NULL,
                status ENUM('pending_verification','pending_approval','pending','approved','rejected') NOT NULL DEFAULT 'pending_verification',
                coordinator_id INT NULL,
                reviewed_by INT NULL,
                decline_reason TEXT NULL,
                reviewed_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_reg_status (status),
                INDEX idx_reg_email (email),
                INDEX idx_reg_student_no (student_no),
                INDEX idx_reg_verification_token (verification_token),
                INDEX idx_reg_user_id (user_id)
            ) ENGINE=InnoDB"
        );
        $this->ensureColumns();
        $this->ensureStatusEnum();
        $ready = true;
    }

    private function ensureStatusEnum(): void
    {
        $stmt = $this->db->query("SHOW COLUMNS FROM student_registration_requests LIKE 'status'");
        $column = $stmt->fetch();
        $type = (string)($column['Type'] ?? '');
        if ($type === '' || str_contains($type, 'pending_verification')) {
            return;
        }
        $this->db->exec(
            "ALTER TABLE student_registration_requests
             MODIFY COLUMN status ENUM('pending_verification','pending_approval','pending','approved','rejected')
             NOT NULL DEFAULT 'pending_verification'"
        );
        $this->db->exec(
            "UPDATE student_registration_requests SET status = 'pending_approval' WHERE status = 'pending'"
        );
    }

    private function ensureColumns(): void
    {
        $columns = [
            'middle_name' => 'VARCHAR(100) NULL AFTER first_name',
            'program_id' => 'INT NULL AFTER student_no',
            'year_level' => "VARCHAR(40) NULL AFTER program_id",
            'verification_token' => 'VARCHAR(64) NULL AFTER cor_file',
            'verification_expires_at' => 'DATETIME NULL AFTER verification_token',
            'email_verified_at' => 'DATETIME NULL AFTER verification_expires_at',
            'user_id' => 'INT NULL AFTER email_verified_at',
        ];
        foreach ($columns as $column => $definition) {
            $stmt = $this->db->query("SHOW COLUMNS FROM student_registration_requests LIKE '{$column}'");
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE student_registration_requests ADD COLUMN {$column} {$definition}");
            }
        }
    }

    public function purgeExpiredUnverified(): void
    {
        $this->ensureTable();
        $stmt = $this->db->query(
            "SELECT * FROM student_registration_requests
             WHERE status = 'pending_verification'
               AND verification_expires_at IS NOT NULL
               AND verification_expires_at < NOW()"
        );
        foreach ($stmt->fetchAll() as $row) {
            $this->deleteRequestData($row);
        }
    }

    public function emailTaken(string $email): bool
    {
        $this->ensureTable();
        $this->purgeExpiredUnverified();
        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }
        if ((new User($this->db))->findByEmail($email)) {
            return true;
        }
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM student_registration_requests
             WHERE email = ?
               AND (
                    status = 'pending_approval'
                    OR status = 'pending'
                    OR (status = 'pending_verification' AND verification_expires_at > NOW())
               )"
        );
        $stmt->execute([$email]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function studentNoTaken(string $studentNo): bool
    {
        $this->ensureTable();
        $this->purgeExpiredUnverified();
        $studentNo = trim($studentNo);
        if ($studentNo === '') {
            return false;
        }
        if ((new Student($this->db))->existsByStudentNo($studentNo)) {
            return true;
        }
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM student_registration_requests
             WHERE student_no = ?
               AND (
                    status = 'pending_approval'
                    OR status = 'pending'
                    OR (status = 'pending_verification' AND verification_expires_at > NOW())
               )"
        );
        $stmt->execute([$studentNo]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(
        string $firstName,
        string $lastName,
        string $email,
        string $studentNo,
        string $passwordHash,
        string $corFile,
        int $programId,
        string $yearLevel,
        ?string $middleName = null
    ): int {
        $this->ensureTable();
        $yearLevel = trim($yearLevel);
        if (!in_array($yearLevel, ['3rd Year', '4th Year'], true)) {
            throw new RuntimeException('Select a valid year level.');
        }
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (self::VERIFICATION_HOURS * 3600));
        $middleName = trim((string)$middleName);
        $stmt = $this->db->prepare(
            'INSERT INTO student_registration_requests
                (first_name, middle_name, last_name, email, student_no, program_id, year_level, password_hash, cor_file, verification_token, verification_expires_at, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            trim($firstName),
            $middleName !== '' ? $middleName : null,
            trim($lastName),
            strtolower(trim($email)),
            trim($studentNo),
            $programId,
            $yearLevel,
            $passwordHash,
            $corFile,
            $token,
            $expiresAt,
            'pending_verification',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT * FROM student_registration_requests WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByVerificationToken(string $token): ?array
    {
        $this->ensureTable();
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $stmt = $this->db->prepare(
            'SELECT * FROM student_registration_requests WHERE verification_token = ? LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function completeEmailVerification(int $id): int
    {
        $this->ensureTable();
        $request = $this->find($id);
        if (!$request) {
            throw new RuntimeException('Registration request not found.');
        }
        if (($request['status'] ?? '') === 'pending_approval' && !empty($request['user_id'])) {
            return (int)$request['user_id'];
        }
        if (($request['status'] ?? '') !== 'pending_verification') {
            throw new RuntimeException('This registration can no longer be verified.');
        }
        if (!empty($request['verification_expires_at']) && strtotime((string)$request['verification_expires_at']) < time()) {
            $this->deleteRequest($id);
            throw new RuntimeException('This verification link has expired. Please register again.');
        }
        if ((new User($this->db))->findByEmail($request['email'])) {
            throw new RuntimeException('This email address is already registered.');
        }
        if ((new Student($this->db))->existsByStudentNo($request['student_no'])) {
            throw new RuntimeException('This Student ID/USN is already registered.');
        }

        $this->db->beginTransaction();
        try {
            $userId = (new User($this->db))->createWithPasswordHash(
                $request['first_name'],
                $request['last_name'],
                $request['email'],
                $request['password_hash'],
                'student',
                null,
                1,
                1,
                $request['middle_name'] ?? null
            );
            $stmt = $this->db->prepare(
                "UPDATE student_registration_requests
                 SET status = 'pending_approval',
                     user_id = ?,
                     email_verified_at = NOW(),
                     verification_token = NULL,
                     verification_expires_at = NULL
                 WHERE id = ? AND status = 'pending_verification'"
            );
            $stmt->execute([$userId, $id]);
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Unable to verify this registration. Please try again.');
            }
            $this->db->commit();
            return $userId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function allPendingApproval(): array
    {
        $this->ensureTable();
        $this->purgeExpiredUnverified();
        $stmt = $this->db->query(
            "SELECT r.*, p.code AS program_code, p.name AS program_name
             FROM student_registration_requests r
             LEFT JOIN programs p ON p.id = r.program_id
             WHERE r.status IN ('pending_approval', 'pending')
             ORDER BY COALESCE(r.email_verified_at, r.created_at) DESC"
        );
        return $stmt->fetchAll();
    }

    public function pendingCount(): int
    {
        $this->ensureTable();
        $this->purgeExpiredUnverified();
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM student_registration_requests WHERE status IN ('pending_approval', 'pending')"
        )->fetchColumn();
    }

    public function markApproved(int $id, int $coordinatorId, int $reviewedBy): void
    {
        $this->ensureTable();
        $stmt = $this->db->prepare(
            "UPDATE student_registration_requests
             SET status = 'approved', coordinator_id = ?, reviewed_by = ?, reviewed_at = NOW()
             WHERE id = ? AND status IN ('pending_approval', 'pending')"
        );
        $stmt->execute([$coordinatorId, $reviewedBy, $id]);
    }

    public function deleteRequest(int $id): void
    {
        $request = $this->find($id);
        if (!$request) {
            return;
        }
        $this->deleteRequestData($request);
    }

    private function deleteRequestData(array $request): void
    {
        if (!empty($request['cor_file'])) {
            $path = dirname(__DIR__) . '/' . ltrim((string)$request['cor_file'], '/\\');
            if (is_file($path)) {
                @unlink($path);
            }
        }
        if (!empty($request['user_id'])) {
            $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([(int)$request['user_id']]);
        }
        $stmt = $this->db->prepare('DELETE FROM student_registration_requests WHERE id = ?');
        $stmt->execute([(int)$request['id']]);
    }

    /** @deprecated Use allPendingApproval() */
    public function allByStatus(string $status = 'pending'): array
    {
        $this->ensureTable();
        if ($status === 'pending') {
            return $this->allPendingApproval();
        }
        $stmt = $this->db->prepare(
            'SELECT * FROM student_registration_requests WHERE status = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    /** @deprecated Use deleteRequest() on decline */
    public function markRejected(int $id, int $reviewedBy, string $reason = ''): void
    {
        $this->deleteRequest($id);
    }
}
