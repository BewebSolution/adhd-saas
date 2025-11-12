<?php

namespace App\Models;

class User extends Model {
    protected string $table = 'users';

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array {
        return $this->findBy('email', $email);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Create user with hashed password
     */
    public function createUser(array $data): int {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        return $this->create($data);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(int $userId): bool {
        $user = $this->find($userId);
        return $user && $user['role'] === 'admin';
    }

    /**
     * Get all interns
     */
    public function getInterns(): array {
        return $this->where(['role' => 'intern'], ['name' => 'ASC']);
    }
}
