<?php

namespace App\Models;

class TimeLog extends Model {
    protected string $table = 'time_logs';

    /**
     * Get all time logs with task info
     */
    public function getAllWithTasks(array $filters = []): array {
        $sql = "SELECT tl.*, t.code as task_code, t.title as task_title, p.name as project_name
                FROM {$this->table} tl
                LEFT JOIN tasks t ON tl.task_id = t.id
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE 1=1";

        $params = [];

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $sql .= " AND tl.date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND tl.date <= ?";
            $params[] = $filters['date_to'];
        }

        // Filter by person
        if (!empty($filters['person'])) {
            $sql .= " AND tl.person = ?";
            $params[] = $filters['person'];
        }

        // Filter by task
        if (!empty($filters['task_id'])) {
            $sql .= " AND tl.task_id = ?";
            $params[] = $filters['task_id'];
        }

        $sql .= " ORDER BY tl.date DESC, tl.id DESC";

        return $this->query($sql, $params);
    }

    /**
     * Get recent time logs (last N days)
     */
    public function getRecent(int $days = 7): array {
        $sql = "SELECT tl.*, t.code as task_code, t.title as task_title, p.name as project_name
                FROM {$this->table} tl
                LEFT JOIN tasks t ON tl.task_id = t.id
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE tl.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY tl.date DESC, tl.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get total hours by person
     */
    public function getTotalHoursByPerson(string $person, ?string $dateFrom = null, ?string $dateTo = null): float {
        $sql = "SELECT SUM(hours) as total FROM {$this->table} WHERE person = ?";
        $params = [$person];

        if ($dateFrom) {
            $sql .= " AND date >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND date <= ?";
            $params[] = $dateTo;
        }

        $result = $this->queryOne($sql, $params);
        return $result ? (float) ($result['total'] ?? 0) : 0;
    }

    /**
     * Get time logs for specific task
     */
    public function getByTask(int $taskId): array {
        return $this->where(['task_id' => $taskId], ['date' => 'DESC']);
    }
}
