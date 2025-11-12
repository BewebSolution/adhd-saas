<?php

namespace App\Models;

class Deliverable extends Model {
    protected string $table = 'deliverables';

    /**
     * Get all deliverables with project info
     */
    public function getAllWithProjects(array $filters = []): array {
        $sql = "SELECT d.*, p.name as project_name
                FROM {$this->table} d
                LEFT JOIN projects p ON d.project_id = p.id
                WHERE 1=1";

        $params = [];

        // Filter by project
        if (!empty($filters['project_id'])) {
            $sql .= " AND d.project_id = ?";
            $params[] = $filters['project_id'];
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $sql .= " AND d.type = ?";
            $params[] = $filters['type'];
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $sql .= " AND d.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY d.date DESC, d.id DESC";

        return $this->query($sql, $params);
    }

    /**
     * Get deliverables in review
     */
    public function getInReview(): array {
        $sql = "SELECT d.*, p.name as project_name
                FROM {$this->table} d
                LEFT JOIN projects p ON d.project_id = p.id
                WHERE d.status = 'In revisione'
                ORDER BY d.date DESC";

        return $this->query($sql);
    }

    /**
     * Get deliverables by project
     */
    public function getByProject(int $projectId): array {
        $sql = "SELECT d.*, p.name as project_name
                FROM {$this->table} d
                LEFT JOIN projects p ON d.project_id = p.id
                WHERE d.project_id = ?
                ORDER BY d.date DESC";

        return $this->query($sql, [$projectId]);
    }
}
