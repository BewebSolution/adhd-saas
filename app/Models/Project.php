<?php

namespace App\Models;

class Project extends Model {
    protected string $table = 'projects';

    /**
     * Find project by name (case-insensitive)
     */
    public function findByName(string $name): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $stmt->execute([trim($name)]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get or create project by name
     */
    public function getOrCreate(string $name): int {
        $name = trim($name);
        $project = $this->findByName($name);

        if ($project) {
            return $project['id'];
        }

        return $this->create(['name' => $name]);
    }

    /**
     * Get project with task count
     */
    public function getAllWithCounts(): array {
        $sql = "SELECT p.*, COUNT(t.id) as task_count
                FROM {$this->table} p
                LEFT JOIN tasks t ON t.project_id = p.id
                GROUP BY p.id
                ORDER BY p.name ASC";

        return $this->query($sql);
    }
}
