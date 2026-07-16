<?php
class Term
{
    private bool $storageReady = false;

    public function __construct(private PDO $db) {}

    private function ensureStorage(): void
    {
        if ($this->storageReady) {
            return;
        }

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS program_terms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                term_label VARCHAR(120) NOT NULL UNIQUE,
                term_start_date DATE NULL,
                term_end_date DATE NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );

        $this->db->exec('ALTER TABLE programs MODIFY term VARCHAR(120) NOT NULL DEFAULT ""');

        foreach (['term_start_date DATE NULL', 'term_end_date DATE NULL', 'is_active TINYINT(1) NOT NULL DEFAULT 1'] as $column) {
            try {
                $this->db->exec('ALTER TABLE program_terms ADD COLUMN ' . $column);
            } catch (Throwable) {
            }
        }

        $this->storageReady = true;
    }

    public function all(): array
    {
        $this->ensureStorage();
        $this->syncFromPrograms();
        return $this->db->query('SELECT * FROM program_terms ORDER BY term_label ASC')->fetchAll();
    }

    public function findByLabel(string $label): ?array
    {
        $this->ensureStorage();
        $stmt = $this->db->prepare('SELECT * FROM program_terms WHERE term_label = ? LIMIT 1');
        $stmt->execute([trim($label)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $label, string $startDate, string $endDate, int $isActive = 1): int
    {
        $this->ensureStorage();
        $stmt = $this->db->prepare('INSERT INTO program_terms (term_label, term_start_date, term_end_date, is_active) VALUES (?, ?, ?, ?)');
        $stmt->execute([trim($label), $startDate, $endDate, $isActive]);
        return (int)$this->db->lastInsertId();
    }

    public function createIfMissing(string $label): void
    {
        $this->ensureStorage();
        $label = trim($label);
        if ($label === '') {
            return;
        }
        $stmt = $this->db->prepare('INSERT IGNORE INTO program_terms (term_label) VALUES (?)');
        $stmt->execute([$label]);
    }

    public function update(int $id, string $label, string $startDate, string $endDate, int $isActive = 1): void
    {
        $this->ensureStorage();
        $label = trim($label);

        $find = $this->db->prepare('SELECT id FROM program_terms WHERE id = ?');
        $find->execute([$id]);
        if (!$find->fetch()) {
            throw new RuntimeException('Term not found.');
        }

        $stmt = $this->db->prepare('UPDATE program_terms SET term_label = ?, term_start_date = ?, term_end_date = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$label, $startDate, $endDate, $isActive, $id]);
    }

    public function delete(int $id): void
    {
        $this->ensureStorage();

        $find = $this->db->prepare('SELECT id FROM program_terms WHERE id = ?');
        $find->execute([$id]);
        if (!$find->fetch()) {
            throw new RuntimeException('Term not found.');
        }

        $del = $this->db->prepare('DELETE FROM program_terms WHERE id = ?');
        $del->execute([$id]);
    }

    private function syncFromPrograms(): void
    {
        $this->ensureStorage();
        $rows = $this->db->query('SELECT DISTINCT term FROM programs WHERE TRIM(term) <> ""')->fetchAll();
        $insert = $this->db->prepare('INSERT IGNORE INTO program_terms (term_label) VALUES (?)');
        foreach ($rows as $row) {
            $insert->execute([trim((string)$row['term'])]);
        }
    }
}
