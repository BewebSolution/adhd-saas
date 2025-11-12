<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TimeLog;

/**
 * Smart Focus Service - "Cosa fare ADESSO" (Versione Migliorata)
 * Con caching e fallback intelligente
 */
class SmartFocusService extends BaseAIService {
    private Task $taskModel;
    private TimeLog $timeLogModel;
    private const CACHE_DURATION = 3600; // 1 ora di cache

    public function __construct() {
        parent::__construct();
        $this->taskModel = new Task();
        $this->timeLogModel = new TimeLog();
    }

    /**
     * Ottieni suggerimento "cosa fare ora"
     */
    public function getSmartFocus(int $userId): ?array {
        // Check if enabled
        if (!env('AI_SMART_FOCUS_ENABLED', true)) {
            return null;
        }

        // Ottieni task attivi
        $tasks = $this->getActiveTasks($userId);

        if (empty($tasks)) {
            return [
                'task' => null,
                'reason' => 'Nessuna attività da fare! 🎉 Prenditi una pausa o crea nuove attività.',
                'suggestion_type' => 'no_tasks'
            ];
        }

        // Ottieni contesto utente
        $context = $this->getUserContext($userId);

        // Check cache first
        $cachedSuggestion = $this->getCachedSuggestion($userId, $context, $tasks);
        if ($cachedSuggestion) {
            return $cachedSuggestion;
        }

        // Prova con AI con gestione errori migliorata
        try {
            $aiDecision = $this->analyzeAndDecide($tasks, $context);

            if ($aiDecision) {
                // Salva in cache
                $this->cacheSuggestion($userId, $context, $tasks, $aiDecision);
                // Salva suggestion
                $this->saveSuggestion($userId, 'smart_focus', $context, $aiDecision);
                return $aiDecision;
            }
        } catch (\Exception $e) {
            error_log('AI Smart Focus error: ' . $e->getMessage());

            // Se è un errore 429 (rate limit), usa cache o fallback
            if (strpos($e->getMessage(), '429') !== false) {
                error_log('API rate limit reached, using intelligent fallback');
            }
        }

        // Fallback intelligente con rotazione
        return $this->intelligentFallback($tasks, $userId);
    }

    /**
     * Get cached suggestion if valid
     */
    private function getCachedSuggestion(int $userId, array $context, array $tasks): ?array {
        try {
            // Genera hash del contesto per identificare cache valida
            $contextHash = $this->generateContextHash($context, $tasks);

            $stmt = $this->db->prepare('
                SELECT suggestion_data
                FROM ai_suggestions_cache
                WHERE user_id = ?
                AND context_hash = ?
                AND expires_at > NOW()
                ORDER BY created_at DESC
                LIMIT 1
            ');
            $stmt->execute([$userId, $contextHash]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result && $result['suggestion_data']) {
                $suggestion = json_decode($result['suggestion_data'], true);
                if ($suggestion) {
                    $suggestion['from_cache'] = true;
                    return $suggestion;
                }
            }
        } catch (\Exception $e) {
            error_log('Cache retrieval error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Cache the suggestion
     */
    private function cacheSuggestion(int $userId, array $context, array $tasks, array $suggestion): void {
        try {
            $contextHash = $this->generateContextHash($context, $tasks);
            $expiresAt = date('Y-m-d H:i:s', time() + self::CACHE_DURATION);

            $stmt = $this->db->prepare('
                INSERT INTO ai_suggestions_cache
                (user_id, context_hash, suggestion_data, expires_at)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                suggestion_data = VALUES(suggestion_data),
                expires_at = VALUES(expires_at),
                created_at = NOW()
            ');
            $stmt->execute([
                $userId,
                $contextHash,
                json_encode($suggestion),
                $expiresAt
            ]);
        } catch (\Exception $e) {
            error_log('Failed to cache suggestion: ' . $e->getMessage());
        }
    }

    /**
     * Generate hash for context to identify similar situations
     */
    private function generateContextHash(array $context, array $tasks): string {
        // Include hour, day of week, and top 3 task IDs
        $hashData = [
            'hour' => $context['current_hour'],
            'dow' => $context['day_of_week'],
            'task_ids' => array_slice(array_column($tasks, 'id'), 0, 3)
        ];
        return md5(json_encode($hashData));
    }

    /**
     * Fallback intelligente con rotazione basata su sessione
     */
    private function intelligentFallback(array $tasks, int $userId): array {
        // Ottieni ultimo task suggerito dalla sessione per evitare ripetizioni
        $lastSuggestedId = $_SESSION['last_suggested_task_id'] ?? null;

        // Filtra task già suggeriti recentemente
        $availableTasks = array_filter($tasks, function($task) use ($lastSuggestedId) {
            return $task['id'] != $lastSuggestedId;
        });

        // Se tutti i task sono stati suggeriti, resetta
        if (empty($availableTasks)) {
            $availableTasks = $tasks;
            unset($_SESSION['last_suggested_task_id']);
        }

        // Strategia di selezione basata su ora del giorno
        $hour = (int)date('H');
        $selectedTask = null;
        $reason = '';

        if ($hour < 12) {
            // Mattina: task più complessi o alta priorità
            $highPriorityTasks = array_filter($availableTasks, function($t) {
                return ($t['priority'] ?? 'Media') === 'Alta';
            });

            if (!empty($highPriorityTasks)) {
                $selectedTask = $this->selectRandomWeighted($highPriorityTasks);
                $reason = '🌅 Mattina produttiva! Affronta questa priorità alta mentre hai energia.';
            }
        } elseif ($hour < 15) {
            // Primo pomeriggio: task con scadenze vicine
            $urgentTasks = array_filter($availableTasks, function($t) {
                return $t['due_at'] && strtotime($t['due_at']) < strtotime('+3 days');
            });

            if (!empty($urgentTasks)) {
                $selectedTask = $this->selectRandomWeighted($urgentTasks);
                $daysLeft = round((strtotime($selectedTask['due_at']) - time()) / 86400);
                $reason = "⏰ Scade tra {$daysLeft} giorni. Meglio portarsi avanti!";
            }
        } else {
            // Tardo pomeriggio/sera: task più leggeri o in corso
            $inProgressTasks = array_filter($availableTasks, function($t) {
                return $t['status'] === 'In corso';
            });

            if (!empty($inProgressTasks)) {
                $selectedTask = $this->selectRandomWeighted($inProgressTasks);
                $reason = '🔄 Continua quello che hai già iniziato. Piccoli progressi sono comunque progressi!';
            }
        }

        // Se nessuna strategia ha funzionato, prendi random con peso
        if (!$selectedTask) {
            $selectedTask = $this->selectRandomWeighted($availableTasks);

            // Genera reason basato su caratteristiche del task
            if (($selectedTask['priority'] ?? 'Media') === 'Alta') {
                $reason = '🎯 Priorità alta - meglio non rimandare!';
            } elseif ($selectedTask['status'] === 'In corso') {
                $reason = '🚀 Già iniziato - continua il momentum!';
            } elseif ($selectedTask['due_at']) {
                $reason = '📅 Ha una scadenza - tienila sotto controllo.';
            } else {
                $reason = '✨ Un passo alla volta verso il completamento!';
            }
        }

        // Salva ID per evitare ripetizioni immediate
        $_SESSION['last_suggested_task_id'] = $selectedTask['id'];

        return [
            'task' => $selectedTask,
            'reason' => $reason,
            'suggestion_type' => 'intelligent_fallback',
            'fallback_strategy' => 'time_based_rotation'
        ];
    }

    /**
     * Seleziona task random con peso basato su priorità e urgenza
     */
    private function selectRandomWeighted(array $tasks): array {
        if (count($tasks) === 1) {
            return reset($tasks);
        }

        // Calcola pesi
        $weights = [];
        foreach ($tasks as $index => $task) {
            $weight = 1;

            // Peso per priorità
            if (($task['priority'] ?? 'Media') === 'Alta') {
                $weight += 3;
            } elseif ($task['priority'] === 'Media') {
                $weight += 2;
            }

            // Peso per scadenza
            if ($task['due_at']) {
                $daysUntilDue = (strtotime($task['due_at']) - time()) / 86400;
                if ($daysUntilDue < 1) {
                    $weight += 5;
                } elseif ($daysUntilDue < 3) {
                    $weight += 3;
                } elseif ($daysUntilDue < 7) {
                    $weight += 1;
                }
            }

            // Peso per status in corso
            if ($task['status'] === 'In corso') {
                $weight += 2;
            }

            $weights[$index] = $weight;
        }

        // Selezione random pesata
        $totalWeight = array_sum($weights);
        $random = mt_rand(1, $totalWeight);

        $currentWeight = 0;
        foreach ($weights as $index => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $tasks[$index];
            }
        }

        // Fallback (non dovrebbe mai arrivare qui)
        return reset($tasks);
    }

    // ... resto dei metodi rimane uguale (getActiveTasks, getUserContext, etc.)

    /**
     * Ottieni task attivi (non completati)
     */
    private function getActiveTasks(int $userId): array {
        $tasks = $this->taskModel->where([
            'assignee' => auth()['name']
        ]);

        // Filtra solo task non "Fatto"
        $tasks = array_filter($tasks, function($task) {
            return $task['status'] !== 'Fatto';
        });

        // Ordina per urgenza
        usort($tasks, function($a, $b) {
            $priorityOrder = ['Alta' => 1, 'Media' => 2, 'Bassa' => 3];
            $prioA = $priorityOrder[$a['priority'] ?? 'Media'] ?? 2;
            $prioB = $priorityOrder[$b['priority'] ?? 'Media'] ?? 2;

            if ($prioA != $prioB) {
                return $prioA <=> $prioB;
            }

            if ($a['due_at'] && $b['due_at']) {
                return $a['due_at'] <=> $b['due_at'];
            }

            return 0;
        });

        return array_slice($tasks, 0, 10);
    }

    /**
     * Ottieni contesto utente
     */
    private function getUserContext(int $userId): array {
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $hourNow = (int)date('H');

        $recentLogs = $this->timeLogModel->where([
            'person' => auth()['name']
        ]);
        $recentLogs = array_filter($recentLogs, function($log) use ($today) {
            return $log['date'] >= date('Y-m-d', strtotime('-7 days'));
        });

        $productivityByHour = $this->analyzeProductivityByHour($recentLogs);

        $hoursToday = array_sum(array_map(function($log) use ($today) {
            return $log['date'] === $today ? (float)$log['hours'] : 0;
        }, $recentLogs));

        return [
            'current_hour' => $hourNow,
            'day_of_week' => date('N'),
            'hours_worked_today' => $hoursToday,
            'productivity_by_hour' => $productivityByHour,
            'is_morning' => $hourNow >= 8 && $hourNow < 12,
            'is_afternoon' => $hourNow >= 12 && $hourNow < 18,
            'is_evening' => $hourNow >= 18,
            'recent_logs_count' => count($recentLogs)
        ];
    }

    /**
     * Analizza produttività per orario
     */
    private function analyzeProductivityByHour(array $logs): array {
        $hourlyData = [];

        foreach ($logs as $log) {
            $hour = (int)date('H', strtotime($log['created_at']));
            if (!isset($hourlyData[$hour])) {
                $hourlyData[$hour] = ['total_hours' => 0, 'count' => 0];
            }
            $hourlyData[$hour]['total_hours'] += (float)$log['hours'];
            $hourlyData[$hour]['count']++;
        }

        $avgByHour = [];
        foreach ($hourlyData as $hour => $data) {
            $avgByHour[$hour] = $data['total_hours'] / $data['count'];
        }

        return $avgByHour;
    }

    /**
     * Analizza e decidi con AI (con gestione errori migliorata)
     */
    private function analyzeAndDecide(array $tasks, array $context): ?array {
        $tasksText = $this->formatTasksForAI($tasks);
        $contextText = $this->formatContextForAI($context);

        $prompt = <<<PROMPT
Sei un assistente AI per persone con ADHD. Devi aiutarmi a decidere QUALE ATTIVITÀ fare ADESSO.

IMPORTANTE:
- Considera paralisi decisionale ADHD: suggerisci UNA SOLA attività
- Spiega il "perché" in modo motivante e chiaro
- Considera ora del giorno ed energy level
- Prioritizza task con scadenza vicina ma non troppo stressanti
- Suggerisci task completabili in tempi brevi se energia bassa

ATTIVITÀ DISPONIBILI:
$tasksText

CONTESTO ATTUALE:
$contextText

Rispondi in formato JSON:
{
    "task_id": <id della task scelta>,
    "reason": "<spiegazione breve e motivante del PERCHÉ questa task ora (max 2 frasi)>",
    "estimated_focus_time": "<tempo stimato in minuti>",
    "energy_needed": "<high/medium/low>",
    "confidence": <0-100>
}

PROMPT;

        try {
            $response = $this->callAI($prompt, ['temperature' => 0.7, 'max_tokens' => 500]);

            if (!$response) {
                return null;
            }

            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}') + 1;
            if ($jsonStart === false || $jsonEnd === false) {
                error_log('AI response not valid JSON: ' . $response);
                return null;
            }

            $json = substr($response, $jsonStart, $jsonEnd - $jsonStart);
            $data = json_decode($json, true);

            if (!$data || !isset($data['task_id'])) {
                return null;
            }

            $selectedTask = null;
            foreach ($tasks as $task) {
                if ($task['id'] == $data['task_id']) {
                    $selectedTask = $task;
                    break;
                }
            }

            if (!$selectedTask) {
                return null;
            }

            return [
                'task' => $selectedTask,
                'reason' => $data['reason'] ?? 'Questa è la task migliore per ora',
                'estimated_focus_time' => $data['estimated_focus_time'] ?? '30 minuti',
                'energy_needed' => $data['energy_needed'] ?? 'medium',
                'confidence' => $data['confidence'] ?? 75,
                'suggestion_type' => 'ai_recommended'
            ];
        } catch (\Exception $e) {
            error_log('AI call failed: ' . $e->getMessage());
            throw $e; // Re-throw per gestione a livello superiore
        }
    }

    /**
     * Formatta task per AI
     */
    private function formatTasksForAI(array $tasks): string {
        $lines = [];
        foreach ($tasks as $task) {
            $dueText = $task['due_at'] ?
                date('d/m/Y H:i', strtotime($task['due_at'])) :
                'Nessuna scadenza';

            $hoursText = sprintf('%.1fh / %.1fh',
                $task['hours_spent'] ?? 0,
                $task['hours_estimated'] ?? 0
            );

            $lines[] = sprintf(
                "- [ID: %d] %s [%s]\n  Progetto: %s | Priorità: %s | Scadenza: %s | Ore: %s\n  Descrizione: %s",
                $task['id'],
                $task['title'],
                $task['status'],
                $task['project_name'] ?? 'N/A',
                $task['priority'] ?? 'Media',
                $dueText,
                $hoursText,
                substr($task['description'] ?? '', 0, 100)
            );
        }

        return implode("\n\n", $lines);
    }

    /**
     * Formatta contesto per AI
     */
    private function formatContextForAI(array $context): string {
        $timeOfDay = $context['is_morning'] ? 'Mattina' :
            ($context['is_afternoon'] ? 'Pomeriggio' : 'Sera');

        $dayNames = ['', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
        $dayName = $dayNames[$context['day_of_week']] ?? 'N/A';

        return sprintf(
            "- Ora: %s (ore %d)\n- Giorno: %s\n- Ore lavorate oggi: %.1fh\n- Energy level stimato: %s",
            $timeOfDay,
            $context['current_hour'],
            $dayName,
            $context['hours_worked_today'],
            $context['hours_worked_today'] > 5 ? 'Basso (stanco)' : 'Alto'
        );
    }

    /**
     * Salva suggestion nel database
     */
    private function saveSuggestion(int $userId, string $type, array $context, array $suggestion): void {
        try {
            // Prima crea la tabella se non esiste
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS ai_suggestions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    type VARCHAR(50),
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
                (user_id, type, context_json, suggestion_json)
                VALUES (?, ?, ?, ?)
            ');
            $stmt->execute([
                $userId,
                $type,
                json_encode($context),
                json_encode($suggestion)
            ]);
        } catch (\Exception $e) {
            error_log('Failed to save AI suggestion: ' . $e->getMessage());
        }
    }

    /**
     * Feedback su suggestion (accettata/rifiutata)
     */
    public function recordFeedback(int $suggestionId, bool $accepted, ?string $feedback = null): void {
        try {
            $stmt = $this->db->prepare('
                UPDATE ai_suggestions
                SET accepted = ?, feedback = ?
                WHERE id = ?
            ');
            $stmt->execute([$accepted ? 1 : 0, $feedback, $suggestionId]);
        } catch (\Exception $e) {
            error_log('Failed to record suggestion feedback: ' . $e->getMessage());
        }
    }
}