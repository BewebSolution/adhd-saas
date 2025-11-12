<?php

namespace App\Models;

class Note extends Model {
    protected string $table = 'notes';

    /**
     * Get all notes with filters
     */
    public function getAllFiltered(array $filters = []): array {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        // Filter by owner
        if (!empty($filters['owner'])) {
            $sql .= " AND owner = ?";
            $params[] = $filters['owner'];
        }

        // Filter by due date
        if (!empty($filters['due_date'])) {
            $sql .= " AND due_date = ?";
            $params[] = $filters['due_date'];
        }

        // Search in topic and body
        if (!empty($filters['search'])) {
            $sql .= " AND (topic LIKE ? OR body LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY date DESC, id DESC";

        return $this->query($sql, $params);
    }

    /**
     * Get notes with upcoming actions
     */
    public function getUpcomingActions(int $days = 7): array {
        $sql = "SELECT * FROM {$this->table}
                WHERE due_date IS NOT NULL
                AND due_date >= CURDATE()
                AND due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY due_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recent notes (last N days)
     */
    public function getRecent(int $days = 7): array {
        $sql = "SELECT * FROM {$this->table}
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
