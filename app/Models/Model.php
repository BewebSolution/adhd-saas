<?php

namespace App\Models;

use PDO;
use PDOException;

abstract class Model {
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct() {
        $this->db = get_db();
    }

    /**
     * Get all records
     */
    public function all(array $orderBy = []): array {
        $sql = "SELECT * FROM {$this->table}";

        if (!empty($orderBy)) {
            $orders = [];
            foreach ($orderBy as $col => $dir) {
                $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
                $orders[] = "{$col} {$dir}";
            }
            $sql .= " ORDER BY " . implode(', ', $orders);
        }

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find record by primary key
     */
    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find record by column
     */
    public function findBy(string $column, mixed $value): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$value]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get records matching conditions
     */
    public function where(array $conditions, array $orderBy = []): array {
        $whereClauses = [];
        $params = [];

        foreach ($conditions as $col => $val) {
            if ($val === null) {
                $whereClauses[] = "{$col} IS NULL";
            } else {
                $whereClauses[] = "{$col} = ?";
                $params[] = $val;
            }
        }

        $sql = "SELECT * FROM {$this->table}";
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        if (!empty($orderBy)) {
            $orders = [];
            foreach ($orderBy as $col => $dir) {
                $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
                $orders[] = "{$col} {$dir}";
            }
            $sql .= " ORDER BY " . implode(', ', $orders);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new record
     */
    public function create(array $data): int {
        try {
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ")
                    VALUES (" . implode(', ', $placeholders) . ")";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(array_values($data));

            if (!$result) {
                error_log("Model::create failed - SQL: $sql");
                error_log("Model::create failed - Data: " . json_encode($data));
                error_log("Model::create failed - Error: " . json_encode($stmt->errorInfo()));
                return 0;
            }

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Model::create exception: " . $e->getMessage());
            error_log("Model::create SQL: $sql");
            error_log("Model::create Data: " . json_encode($data));
            return 0;
        }
    }

    /**
     * Update record
     */
    public function update(int $id, array $data): bool {
        $sets = [];
        $params = [];

        foreach ($data as $col => $val) {
            $sets[] = "{$col} = ?";
            $params[] = $val;
        }
        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE {$this->primaryKey} = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete record
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Count records
     */
    public function count(array $conditions = []): int {
        $whereClauses = [];
        $params = [];

        foreach ($conditions as $col => $val) {
            if ($val === null) {
                $whereClauses[] = "{$col} IS NULL";
            } else {
                $whereClauses[] = "{$col} = ?";
                $params[] = $val;
            }
        }

        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Execute raw query
     */
    protected function query(string $sql, array $params = []): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute raw query and return single row
     */
    protected function queryOne(string $sql, array $params = []): ?array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
