<?php
class Program
{
    public function __construct(private PDO $db) {}

    public function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM programs';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY code';
        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM programs WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $code, string $name, int $requiredHours): int
    {
        $stmt = $this->db->prepare('INSERT INTO programs (code, name, required_hours) VALUES (?, ?, ?)');
        $stmt->execute([strtoupper(trim($code)), trim($name), $requiredHours]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $code, string $name, int $requiredHours, int $active): void
    {
        $stmt = $this->db->prepare('UPDATE programs SET code = ?, name = ?, required_hours = ?, is_active = ? WHERE id = ?');
        $stmt->execute([strtoupper(trim($code)), trim($name), $requiredHours, $active, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM programs WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function acceptedByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare('SELECT p.* FROM company_programs cp JOIN programs p ON p.id = cp.program_id WHERE cp.company_id = ? ORDER BY p.code');
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }
}
