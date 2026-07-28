<?php
class Company
{
    private ?bool $moaMouSupportReady = null;
    private ?bool $photoSupportReady = null;
    private ?bool $partnerIdSupportReady = null;

    public function __construct(private PDO $db) {}

    public function ensurePartnerIdSupport(): void
    {
        if ($this->partnerIdSupportReady === true) {
            return;
        }

        $columnStmt = $this->db->prepare("SHOW COLUMNS FROM partner_companies LIKE 'partner_id'");
        $columnStmt->execute();
        if (!$columnStmt->fetch()) {
            $this->db->exec('ALTER TABLE partner_companies ADD COLUMN partner_id VARCHAR(20) NULL AFTER id');
        }

        $indexStmt = $this->db->prepare("SHOW INDEX FROM partner_companies WHERE Key_name = 'uq_partner_companies_partner_id'");
        $indexStmt->execute();
        if (!$indexStmt->fetch()) {
            $this->db->exec('ALTER TABLE partner_companies ADD UNIQUE KEY uq_partner_companies_partner_id (partner_id)');
        }

        $this->migrateLegacyPartnerIdPrefix();
        $this->backfillPartnerIds();
        $this->partnerIdSupportReady = true;
    }

    private function migrateLegacyPartnerIdPrefix(): void
    {
        $this->db->exec(
            "UPDATE partner_companies
             SET partner_id = CONCAT('HTE-', SUBSTRING(partner_id, 4))
             WHERE partner_id LIKE 'IP-%'"
        );
    }

    public function peekNextPartnerId(): string
    {
        $this->ensurePartnerIdSupport();
        return $this->generatePartnerId();
    }

    private function generatePartnerId(): string
    {
        $year = date('Y');
        $prefix = 'HTE-' . $year . '-';
        $stmt = $this->db->prepare(
            'SELECT partner_id FROM partner_companies WHERE partner_id LIKE ? ORDER BY partner_id DESC LIMIT 1'
        );
        $stmt->execute([$prefix . '%']);
        $last = (string)($stmt->fetchColumn() ?: '');
        $next = 1;
        if ($last !== '' && preg_match('/HTE-\d{4}-(\d+)$/', $last, $matches)) {
            $next = (int)$matches[1] + 1;
        }
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    private function backfillPartnerIds(): void
    {
        $rows = $this->db->query(
            'SELECT id FROM partner_companies WHERE partner_id IS NULL OR partner_id = "" ORDER BY id ASC'
        )->fetchAll();
        if (!$rows) {
            return;
        }

        $update = $this->db->prepare('UPDATE partner_companies SET partner_id = ? WHERE id = ?');
        foreach ($rows as $row) {
            $update->execute([$this->generatePartnerId(), (int)$row['id']]);
        }
    }

    public function ensureMoaMouSupport(): void
    {
        if ($this->moaMouSupportReady === true) {
            return;
        }

        $columnStmt = $this->db->prepare("SHOW COLUMNS FROM partner_companies LIKE 'moa_mou_file'");
        $columnStmt->execute();
        $hasColumn = (bool)$columnStmt->fetch();

        if (!$hasColumn) {
            $this->db->exec('ALTER TABLE partner_companies ADD COLUMN moa_mou_file VARCHAR(255) NULL AFTER contact_number');
        }

        $this->moaMouSupportReady = true;
    }

    public function ensurePhotoSupport(): void
    {
        if ($this->photoSupportReady === true) {
            return;
        }

        $this->ensureMoaMouSupport();

        $columnStmt = $this->db->prepare("SHOW COLUMNS FROM partner_companies LIKE 'photo_file'");
        $columnStmt->execute();
        $hasColumn = (bool)$columnStmt->fetch();

        if (!$hasColumn) {
            $this->db->exec('ALTER TABLE partner_companies ADD COLUMN photo_file VARCHAR(255) NULL AFTER moa_mou_file');
        }

        $this->photoSupportReady = true;
    }

    public function create(int $userId, string $name, string $address, string $contactPerson, string $contactEmail, string $contactNumber = '', array $programIds = [], ?string $moaMouFile = null): int
    {
        $this->ensurePhotoSupport();
        $this->ensurePartnerIdSupport();
        $partnerId = $this->generatePartnerId();

        $stmt = $this->db->prepare('INSERT INTO partner_companies (user_id, partner_id, name, address, contact_person, contact_email, contact_number, moa_mou_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $partnerId, $name, $address, $contactPerson, strtolower(trim($contactEmail)), $contactNumber, $moaMouFile]);
        $companyId = (int)$this->db->lastInsertId();
        $this->syncPrograms($companyId, $programIds);
        return $companyId;
    }

    public function all(): array
    {
        $this->ensurePhotoSupport();
        $this->ensurePartnerIdSupport();
        return $this->db->query('SELECT pc.*, u.id user_id_key, u.email, u.is_active, GROUP_CONCAT(p.code ORDER BY p.code SEPARATOR ", ") accepted_programs, GROUP_CONCAT(cp.program_id ORDER BY cp.program_id SEPARATOR ",") accepted_program_ids FROM partner_companies pc JOIN users u ON u.id = pc.user_id LEFT JOIN company_programs cp ON cp.company_id = pc.id LEFT JOIN programs p ON p.id = cp.program_id GROUP BY pc.id, u.id ORDER BY pc.name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $this->ensurePhotoSupport();
        $this->ensurePartnerIdSupport();
        $stmt = $this->db->prepare('SELECT pc.*, u.email user_email FROM partner_companies pc JOIN users u ON u.id = pc.user_id WHERE pc.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEnrollmentStudent(int $studentId): ?array
    {
        $this->ensurePhotoSupport();
        $stmt = $this->db->prepare('SELECT pc.* FROM partner_companies pc JOIN ojt_enrollments e ON e.company_id = pc.id WHERE e.student_id = ? LIMIT 1');
        $stmt->execute([$studentId]);
        return $stmt->fetch() ?: null;
    }

    public function findByPartnerId(string $partnerId): ?array
    {
        $this->ensurePartnerIdSupport();
        $stmt = $this->db->prepare('SELECT * FROM partner_companies WHERE partner_id = ? LIMIT 1');
        $stmt->execute([trim($partnerId)]);
        return $stmt->fetch() ?: null;
    }

    public function findByUser(int $userId): ?array
    {
        $this->ensurePhotoSupport();
        $this->ensurePartnerIdSupport();
        $stmt = $this->db->prepare('SELECT * FROM partner_companies WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function updateProfile(int $companyId, string $name, string $address, string $contactPerson, string $contactEmail, string $contactNumber, ?string $photoFile = null): void
    {
        $this->ensurePhotoSupport();
        $stmt = $this->db->prepare('UPDATE partner_companies SET name = ?, address = ?, contact_person = ?, contact_email = ?, contact_number = ?, photo_file = COALESCE(?, photo_file) WHERE id = ?');
        $stmt->execute([
            trim($name),
            trim($address),
            trim($contactPerson),
            strtolower(trim($contactEmail)),
            trim($contactNumber),
            $photoFile,
            $companyId,
        ]);
    }

    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM partner_companies')->fetchColumn();
    }

    public function countCreatedInLastDays(int $days): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM partner_companies
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([max(1, $days)]);
        return (int)$stmt->fetchColumn();
    }

    public function countCreatedBetweenDays(int $olderThanDays, int $newerThanDays): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM partner_companies
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([max(1, $olderThanDays), max(0, $newerThanDays)]);
        return (int)$stmt->fetchColumn();
    }

    public function syncPrograms(int $companyId, array $programIds): void
    {
        $this->db->prepare('DELETE FROM company_programs WHERE company_id = ?')->execute([$companyId]);
        $stmt = $this->db->prepare('INSERT INTO company_programs (company_id, program_id) VALUES (?, ?)');
        foreach (array_unique(array_map('intval', $programIds)) as $programId) {
            if ($programId > 0) {
                $stmt->execute([$companyId, $programId]);
            }
        }
    }

    public function acceptsProgram(int $companyId, int $programId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM company_programs WHERE company_id = ? AND program_id = ?');
        $stmt->execute([$companyId, $programId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
