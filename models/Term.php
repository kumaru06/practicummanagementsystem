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
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );

        $this->db->exec('ALTER TABLE programs MODIFY term VARCHAR(120) NOT NULL DEFAULT ""');

        $this->storageReady = true;
    }

    public function all(): array
    {
        $this->ensureStorage();
        $this->syncFromPrograms();
        return $this->db->query('SELECT * FROM program_terms ORDER BY term_label ASC')->fetchAll();
    }

    public function create(string $label): int
    {
        $this->ensureStorage();
        $stmt = $this->db->prepare('INSERT INTO program_terms (term_label) VALUES (?)');
        $stmt->execute([trim($label)]);
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

    public function update(int $id, string $label): void
    {
        $this->ensureStorage();
        $label = trim($label);

        $find = $this->db->prepare('SELECT term_label FROM program_terms WHERE id = ?');
        $find->execute([$id]);
        $row = $find->fetch();
        if (!$row) {
            throw new RuntimeException('Term not found.');
        }

        $oldLabel = (string)$row['term_label'];
        $this->db->beginTransaction();
        try {
            $update = $this->db->prepare('UPDATE program_terms SET term_label = ? WHERE id = ?');
            $update->execute([$label, $id]);

            $syncPrograms = $this->db->prepare('UPDATE programs SET term = ? WHERE term = ?');
            $syncPrograms->execute([$label, $oldLabel]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $this->ensureStorage();

        $find = $this->db->prepare('SELECT term_label FROM program_terms WHERE id = ?');
        $find->execute([$id]);
        $row = $find->fetch();
        if (!$row) {
            throw new RuntimeException('Term not found.');
        }

        $label = (string)$row['term_label'];
        // Clear the term on any programs that were using this label
        $clear = $this->db->prepare("UPDATE programs SET term = '' WHERE term = ?");
        $clear->execute([$label]);

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
