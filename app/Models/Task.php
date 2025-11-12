<?php

namespace App\Models;

class Task extends Model {
    protected string $table = 'tasks';

    /**
     * Get all tasks with project names
     */
    public function getAllWithProjects(array $filters = []): array {
        $sql = "SELECT t.*, p.name as project_name
                FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE 1=1";

        $params = [];

        // Se l'utente è intern e non c'è già un filtro assignee, applica il filtro automatico
        if (auth()['role'] === 'intern' && empty($filters['assignee'])) {
            $sql .= " AND t.assignee = ?";
            $params[] = auth()['name'];
        }

        // Filter by project
        if (!empty($filters['project_id'])) {
            $sql .= " AND t.project_id = ?";
            $params[] = $filters['project_id'];
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }

        // Filter by assignee
        if (!empty($filters['assignee'])) {
            $sql .= " AND t.assignee = ?";
            $params[] = $filters['assignee'];
        }

        // Filter by due date range
        if (!empty($filters['due_from'])) {
            $sql .= " AND t.due_at >= ?";
            $params[] = $filters['due_from'] . ' 00:00:00';
        }
        if (!empty($filters['due_to'])) {
            $sql .= " AND t.due_at <= ?";
            $params[] = $filters['due_to'] . ' 23:59:59';
        }

        // Search
        if (!empty($filters['search'])) {
            $sql .= " AND (t.title LIKE ? OR t.description LIKE ? OR t.code LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY t.due_at ASC, t.id DESC";

        return $this->query($sql, $params);
    }

    /**
     * Get upcoming deadlines
     */
    public function getUpcomingDeadlines(int $limit = 3): array {
        $sql = "SELECT t.*, p.name as project_name
                FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE t.due_at >= NOW()
                AND t.status != 'Fatto'";

        $params = [];

        // Se l'utente è intern, vede solo le sue attività
        if (auth()['role'] === 'intern') {
            $sql .= " AND t.assignee = ?";
            $params[] = auth()['name'];
        }

        $sql .= " ORDER BY t.due_at ASC
                LIMIT ?";
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get tasks in progress
     */
    public function getInProgress(): array {
        $sql = "SELECT t.*, p.name as project_name
                FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE t.status = 'In corso'";

        $params = [];

        // Se l'utente è intern, vede solo le sue attività
        if (auth()['role'] === 'intern') {
            $sql .= " AND t.assignee = ?";
            $params[] = auth()['name'];
        }

        $sql .= " ORDER BY t.due_at ASC";

        return $this->query($sql, $params);
    }

    /**
     * Generate next task code
     */
    public function generateNextCode(): string {
        $sql = "SELECT code FROM {$this->table}
                WHERE code LIKE 'A-%'
                ORDER BY CAST(SUBSTRING(code, 3) AS UNSIGNED) DESC
                LIMIT 1";

        $result = $this->queryOne($sql);

        if (!$result || !$result['code']) {
            return 'A-001';
        }

        $lastNumber = (int) substr($result['code'], 2);
        $nextNumber = $lastNumber + 1;

        return 'A-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Find task by code
     */
    public function findByCode(string $code): ?array {
        return $this->findBy('code', $code);
    }

    /**
     * Update task status
     */
    public function updateStatus(int $id, string $status): bool {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Add hours to task
     */
    public function addHours(int $id, float $hours): bool {
        $task = $this->find($id);
        if (!$task) {
            return false;
        }

        $newSpent = ($task['hours_spent'] ?? 0) + $hours;
        return $this->update($id, ['hours_spent' => $newSpent]);
    }
}
