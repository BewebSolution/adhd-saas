<?php

namespace App\Controllers;

use App\Services\SmartFocusService;

/**
 * Smart Focus Controller
 * Handles ADHD-optimized task suggestions and feedback
 */
class SmartFocusController {

    private SmartFocusService $smartFocus;
    private \PDO $db;

    public function __construct() {
        require_auth();
        $this->smartFocus = new SmartFocusService();
        $this->db = get_db();
    }

    /**
     * Get smart focus suggestion
     */
    public function getSmartFocus(): void {
        $userInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        // Store user context in session for better suggestions
        $_SESSION['last_energy_level'] = $userInput['energy'] ?? 'medium';
        $_SESSION['last_focus_time'] = $userInput['focus_time'] ?? 45;
        $_SESSION['last_mood'] = $userInput['mood'] ?? 'neutral';

        // Get suggestion
        $suggestion = $this->smartFocus->getSmartFocus(auth()['id']);

        if ($suggestion) {
            // Add suggestion ID for tracking
            if (isset($suggestion['task']['id'])) {
                $suggestionId = $this->recordSuggestion($suggestion['task']['id'], 'smart_focus', $userInput);
                $suggestion['suggestion_id'] = $suggestionId;
            }

            json_response([
                'success' => true,
                'data' => $suggestion
            ]);
        } else {
            json_response([
                'success' => false,
                'error' => 'Nessun suggerimento disponibile'
            ]);
        }
    }

    /**
     * Get quick win suggestion
     */
    public function getQuickWin(): void {
        $suggestion = $this->smartFocus->getQuickWin(auth()['id']);

        if ($suggestion) {
            // Add suggestion ID for tracking
            if (isset($suggestion['task']['id'])) {
                $suggestionId = $this->recordSuggestion($suggestion['task']['id'], 'quick_win', []);
                $suggestion['suggestion_id'] = $suggestionId;
            }

            json_response([
                'success' => true,
                'data' => $suggestion
            ]);
        } else {
            json_response([
                'success' => false,
                'error' => 'Nessun quick win disponibile'
            ]);
        }
    }

    /**
     * Record feedback for a suggestion
     */
    public function recordFeedback(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!verify_csrf($data['csrf_token'] ?? '')) {
            json_response(['error' => 'Token CSRF non valido'], 403);
            return;
        }

        $userId = auth()['id'];
        $taskId = $data['task_id'] ?? null;
        $suggestionId = $data['suggestion_id'] ?? null;
        $wasHelpful = $data['was_helpful'] ?? false;

        try {
            // Update suggestion feedback if ID provided
            if ($suggestionId) {
                $this->smartFocus->recordFeedback($suggestionId, $wasHelpful);
            }

            // Record in feedback table
            $stmt = $this->db->prepare('
                INSERT INTO smart_focus_feedback
                (user_id, suggestion_id, task_id, was_helpful, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ');
            $stmt->execute([$userId, $suggestionId, $taskId, $wasHelpful ? 1 : 0]);

            // Update stats
            $this->updateUserStats($userId, $wasHelpful);

            json_response(['success' => true]);

        } catch (\Exception $e) {
            error_log('Smart Focus feedback error: ' . $e->getMessage());
            json_response(['error' => 'Errore nel salvataggio feedback'], 500);
        }
    }

    /**
     * Record detailed feedback
     */
    public function recordDetailedFeedback(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!verify_csrf($data['csrf_token'] ?? '')) {
            json_response(['error' => 'Token CSRF non valido'], 403);
            return;
        }

        $userId = auth()['id'];

        try {
            $stmt = $this->db->prepare('
                INSERT INTO smart_focus_feedback
                (user_id, suggestion_id, task_id, was_helpful, completed_task,
                 time_spent_minutes, user_comment, energy_before, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');

            $stmt->execute([
                $userId,
                $data['suggestion_id'] ?? null,
                $data['task_id'] ?? null,
                $data['was_helpful'] ? 1 : 0,
                $data['completed_task'] ? 1 : 0,
                $data['time_spent_minutes'] ?? null,
                $data['user_comment'] ?? null,
                $_SESSION['last_energy_level'] ?? null
            ]);

            // Update suggestion record if ID provided
            if (!empty($data['suggestion_id'])) {
                $this->smartFocus->recordFeedback(
                    $data['suggestion_id'],
                    $data['was_helpful'],
                    $data['user_comment'] ?? null
                );
            }

            // Update user stats
            $this->updateUserStats($userId, $data['was_helpful'], $data['completed_task'] ?? false);

            json_response(['success' => true]);

        } catch (\Exception $e) {
            error_log('Detailed feedback error: ' . $e->getMessage());
            json_response(['error' => 'Errore nel salvataggio feedback dettagliato'], 500);
        }
    }

    /**
     * Record a suggestion for tracking
     */
    private function recordSuggestion(int $taskId, string $type, array $context): ?int {
        try {
            // First ensure table exists
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS ai_suggestions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    type VARCHAR(50),
                    task_id INT,
                    context_json JSON,
                    suggestion_json JSON,
                    accepted TINYINT DEFAULT 0,
                    feedback TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_type (user_id, type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');

            $stmt = $this->db->prepare('
                INSERT INTO ai_suggestions
                (user_id, type, task_id, context_json, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ');

            $stmt->execute([
                auth()['id'],
                $type,
                $taskId,
                json_encode($context)
            ]);

            return $this->db->lastInsertId();

        } catch (\Exception $e) {
            error_log('Failed to record suggestion: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update user statistics
     */
    private function updateUserStats(int $userId, bool $wasHelpful, bool $completed = false): void {
        try {
            // Ensure stats table exists
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS smart_focus_stats (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL UNIQUE,
                    total_suggestions INT DEFAULT 0,
                    accepted_suggestions INT DEFAULT 0,
                    completed_from_suggestions INT DEFAULT 0,
                    avg_helpfulness_score FLOAT DEFAULT 0,
                    last_suggestion_at TIMESTAMP NULL,
                    streak_days INT DEFAULT 0,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT fk_stats_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');

            // Update or insert stats
            $stmt = $this->db->prepare('
                INSERT INTO smart_focus_stats
                (user_id, total_suggestions, accepted_suggestions, completed_from_suggestions, last_suggestion_at)
                VALUES (?, 1, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    total_suggestions = total_suggestions + 1,
                    accepted_suggestions = accepted_suggestions + ?,
                    completed_from_suggestions = completed_from_suggestions + ?,
                    last_suggestion_at = NOW(),
                    updated_at = NOW()
            ');

            $accepted = $wasHelpful ? 1 : 0;
            $completedCount = $completed ? 1 : 0;

            $stmt->execute([
                $userId,
                $accepted,
                $completedCount,
                $accepted,
                $completedCount
            ]);

        } catch (\Exception $e) {
            error_log('Failed to update user stats: ' . $e->getMessage());
        }
    }
}