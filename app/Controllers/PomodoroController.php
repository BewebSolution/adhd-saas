<?php

namespace App\Controllers;

use App\Services\BaseAIService;

/**
 * Pomodoro Controller
 * Handles Pomodoro timer operations and AI suggestions
 */
class PomodoroController {

    private \PDO $db;
    private BaseAIService $ai;

    public function __construct() {
        require_auth();
        $this->db = get_db();
        $this->ai = new BaseAIService();
    }

    /**
     * Get AI suggestion for Pomodoro
     */
    public function getAISuggestion(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        $trigger = $data['trigger'] ?? 'start';
        $mode = $data['mode'] ?? 'work';
        $completedPomodoros = $data['completedPomodoros'] ?? 0;
        $currentTask = $data['currentTask'] ?? '';
        $timeOfDay = $data['timeOfDay'] ?? date('H');

        // Generate contextual suggestion
        $suggestion = $this->generateSuggestion($trigger, $mode, $completedPomodoros, $currentTask, $timeOfDay);

        // If AI is available, enhance the suggestion
        if ($this->ai->isConfigured()) {
            $suggestion = $this->enhanceWithAI($suggestion, $data);
        }

        json_response([
            'success' => true,
            'suggestion' => $suggestion
        ]);
    }

    /**
     * Generate base suggestion without AI
     */
    private function generateSuggestion($trigger, $mode, $completedPomodoros, $currentTask, $timeOfDay): string {
        $suggestions = [];

        if ($trigger === 'start' && $mode === 'work') {
            $suggestions = [
                "Concentrati solo su: {$currentTask}. Telefono in modalità aereo!",
                "25 minuti di focus totale. Niente distrazioni, solo questo task.",
                "Ricorda: meglio fatto che perfetto. Inizia subito!",
                "Respira profondamente 3 volte, poi inizia. Sei capace!"
            ];
        } elseif ($trigger === 'complete' && $mode === 'work') {
            if ($completedPomodoros >= 4) {
                $suggestions = [
                    "🎉 WOW! {$completedPomodoros} pomodori! Meriti una pausa lunga!",
                    "Incredibile focus oggi! Ora stacca completamente per 15 minuti.",
                    "Hai lavorato benissimo! Alzati, cammina, idratati."
                ];
            } else {
                $suggestions = [
                    "Ottimo pomodoro! 5 minuti di pausa: alzati e muoviti!",
                    "Ben fatto! Ora: acqua, stretching, respira.",
                    "Task completato! Pausa veloce poi riparti con energia."
                ];
            }
        } elseif ($mode === 'shortBreak') {
            $suggestions = [
                "5 minuti: NO telefono! Alzati, cammina, guarda lontano.",
                "Pausa attiva: fai 10 squat o stretching cervicale.",
                "Bevi acqua, fai 5 respiri profondi, rilassa gli occhi."
            ];
        } elseif ($mode === 'longBreak') {
            $suggestions = [
                "15 minuti tutti tuoi: snack sano, musica, o breve passeggiata.",
                "Pausa lunga: esci dalla stanza, cambia ambiente, ricaricati.",
                "Ottimo lavoro finora! Ora stacca davvero: niente schermi."
            ];
        }

        // Time-based suggestions
        if ($timeOfDay < 12 && $mode === 'work') {
            $suggestions[] = "Mattina = massima energia. Affronta il task più difficile!";
        } elseif ($timeOfDay > 14 && $timeOfDay < 16) {
            $suggestions[] = "Calo pomeridiano? Normal! Focus su task più semplici.";
        } elseif ($timeOfDay > 20) {
            $suggestions[] = "Sera tardi: ultimo sforzo! Poi stop, riposo è produttività.";
        }

        return $suggestions[array_rand($suggestions)];
    }

    /**
     * Enhance suggestion with AI
     */
    private function enhanceWithAI(string $baseSuggestion, array $context): string {
        try {
            $prompt = "
Sei un coach ADHD che supporta durante le sessioni Pomodoro.

Contesto:
- Trigger: {$context['trigger']}
- Modalità: {$context['mode']}
- Pomodori completati: {$context['completedPomodoros']}
- Task corrente: {$context['currentTask']}
- Ora del giorno: {$context['timeOfDay']}:00

Suggerimento base: {$baseSuggestion}

Migliora questo suggerimento rendendolo:
1. Più specifico per ADHD
2. Ultra-breve (max 15 parole)
3. Motivante ma realistico
4. Con un'azione concreta

Rispondi SOLO con il suggerimento migliorato, niente altro.
";

            $response = $this->ai->query($prompt);
            if ($response && strlen($response) > 10) {
                return $response;
            }
        } catch (\Exception $e) {
            error_log('Pomodoro AI enhancement error: ' . $e->getMessage());
        }

        return $baseSuggestion;
    }

    /**
     * Log Pomodoro session
     */
    public function logSession(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $stmt = $this->db->prepare('
                INSERT INTO pomodoro_logs
                (user_id, task_id, duration_seconds, completed, mode, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ');

            $stmt->execute([
                auth()['id'],
                $data['task_id'] ?? null,
                $data['duration'] ?? 0,
                $data['completed'] ? 1 : 0,
                $data['mode'] ?? 'work'
            ]);

            // Update user stats
            if ($data['completed'] && $data['mode'] === 'work') {
                $this->updatePomodoroStats();
            }

            json_response(['success' => true]);

        } catch (\Exception $e) {
            error_log('Pomodoro log error: ' . $e->getMessage());
            json_response(['error' => 'Errore nel salvataggio'], 500);
        }
    }

    /**
     * Update Pomodoro statistics
     */
    private function updatePomodoroStats(): void {
        try {
            // Create table if not exists
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS pomodoro_stats (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL UNIQUE,
                    total_pomodoros INT DEFAULT 0,
                    today_pomodoros INT DEFAULT 0,
                    streak_days INT DEFAULT 0,
                    best_streak INT DEFAULT 0,
                    last_pomodoro_at TIMESTAMP NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT fk_pomo_stats_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_date (user_id, last_pomodoro_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');

            $userId = auth()['id'];

            // Check if today is a new day
            $stmt = $this->db->prepare('
                SELECT DATE(last_pomodoro_at) as last_date
                FROM pomodoro_stats
                WHERE user_id = ?
            ');
            $stmt->execute([$userId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            $isNewDay = !$result || $result['last_date'] !== date('Y-m-d');

            // Update or insert stats
            $stmt = $this->db->prepare('
                INSERT INTO pomodoro_stats
                (user_id, total_pomodoros, today_pomodoros, streak_days, last_pomodoro_at)
                VALUES (?, 1, 1, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    total_pomodoros = total_pomodoros + 1,
                    today_pomodoros = IF(? = 1, 1, today_pomodoros + 1),
                    streak_days = IF(? = 1 AND DATEDIFF(NOW(), last_pomodoro_at) <= 1, streak_days + 1, 1),
                    best_streak = GREATEST(best_streak, streak_days),
                    last_pomodoro_at = NOW()
            ');

            $stmt->execute([$userId, $isNewDay ? 1 : 0, $isNewDay ? 1 : 0]);

        } catch (\Exception $e) {
            error_log('Update pomodoro stats error: ' . $e->getMessage());
        }
    }

    /**
     * Get user's Pomodoro statistics
     */
    public function getStats(): void {
        try {
            $stmt = $this->db->prepare('
                SELECT *
                FROM pomodoro_stats
                WHERE user_id = ?
            ');
            $stmt->execute([auth()['id']]);
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$stats) {
                $stats = [
                    'total_pomodoros' => 0,
                    'today_pomodoros' => 0,
                    'streak_days' => 0,
                    'best_streak' => 0
                ];
            }

            // Add weekly stats
            $stmt = $this->db->prepare('
                SELECT COUNT(*) as weekly_total
                FROM pomodoro_logs
                WHERE user_id = ?
                AND mode = "work"
                AND completed = 1
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ');
            $stmt->execute([auth()['id']]);
            $weekly = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stats['weekly_pomodoros'] = $weekly['weekly_total'] ?? 0;

            json_response([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            error_log('Get pomodoro stats error: ' . $e->getMessage());
            json_response(['error' => 'Errore nel caricamento statistiche'], 500);
        }
    }
}