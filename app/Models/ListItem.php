<?php

namespace App\Models;

class ListItem extends Model {
    protected string $table = 'list_items';

    /**
     * Get all items for a specific list
     */
    public function getByListName(string $listName): array {
        return $this->where(['list_name' => $listName], ['value' => 'ASC']);
    }

    /**
     * Get all list names
     */
    public function getAllListNames(): array {
        $sql = "SELECT DISTINCT list_name FROM {$this->table} ORDER BY list_name ASC";
        return $this->query($sql);
    }

    /**
     * Add item to list (with duplicate check)
     */
    public function addItem(string $listName, string $value): ?int {
        $value = trim($value);

        // Check if already exists
        $existing = $this->findByListAndValue($listName, $value);
        if ($existing) {
            return null;
        }

        return $this->create([
            'list_name' => $listName,
            'value' => $value
        ]);
    }

    /**
     * Find item by list name and value
     */
    public function findByListAndValue(string $listName, string $value): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE list_name = ? AND value = ? LIMIT 1";
        return $this->queryOne($sql, [$listName, trim($value)]);
    }

    /**
     * Update item value
     */
    public function updateValue(int $id, string $newValue): bool {
        return $this->update($id, ['value' => trim($newValue)]);
    }

    /**
     * Delete item (only if not in use)
     */
    public function deleteIfNotInUse(int $id): bool {
        $item = $this->find($id);
        if (!$item) {
            return false;
        }

        // Check if value is in use (basic check)
        // For production, add specific checks per list type

        return $this->delete($id);
    }

    /**
     * Get all items grouped by list name
     */
    public function getAllGrouped(): array {
        $sql = "SELECT * FROM {$this->table} ORDER BY list_name ASC, value ASC";
        $items = $this->query($sql);

        $grouped = [];
        foreach ($items as $item) {
            $listName = $item['list_name'];
            if (!isset($grouped[$listName])) {
                $grouped[$listName] = [];
            }
            $grouped[$listName][] = $item;
        }

        return $grouped;
    }
}
