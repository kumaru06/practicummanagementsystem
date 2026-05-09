<?php
class Company
{
    public function __construct(private PDO $db) {}

    public function create(int $userId, string $name, string $address, string $contactPerson, string $contactEmail, string $contactNumber = '', array $programIds = []): int
    {
        $stmt = $this->db->prepare('INSERT INTO partner_companies (user_id, name, address, contact_person, contact_email, contact_number) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $name, $address, $contactPerson, strtolower(trim($contactEmail)), $contactNumber]);
        $companyId = (int)$this->db->lastInsertId();
        $this->syncPrograms($companyId, $programIds);
        return $companyId;
    }

    public function all(): array
    {
        return $this->db->query('SELECT pc.*, u.id user_id_key, u.email, u.is_active, GROUP_CONCAT(p.code ORDER BY p.code SEPARATOR ", ") accepted_programs, GROUP_CONCAT(cp.program_id ORDER BY cp.program_id SEPARATOR ",") accepted_program_ids FROM partner_companies pc JOIN users u ON u.id = pc.user_id LEFT JOIN company_programs cp ON cp.company_id = pc.id LEFT JOIN programs p ON p.id = cp.program_id GROUP BY pc.id, u.id ORDER BY pc.name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT pc.*, u.email user_email FROM partner_companies pc JOIN users u ON u.id = pc.user_id WHERE pc.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM partner_companies WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM partner_companies')->fetchColumn();
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
