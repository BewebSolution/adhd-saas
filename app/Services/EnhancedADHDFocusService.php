<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TimeLog;
use App\Models\Project;

/**
 * Enhanced ADHD Focus Service - Sistema intelligente per suggerimenti task
 * Con tracking di suggerimenti, rotazione e raccolta dati estesa
 */
class EnhancedADHDFocusService extends BaseAIService {
    private Task $taskModel;
    private TimeLog $timeLogModel;
    private Project $projectModel;

    public function __construct() {
        parent::__construct();
        $this->taskModel = new Task();
        $this->timeLogModel = new TimeLog();
        $this->projectModel = new Project();
    }

    /**
     * Ottieni suggerimento intelligente su cosa fare
     */
    public function getSmartSuggestion(int $userId, array $userInput = []): ?array {
        // 1. Raccogli TUTTI i dati necessari
        $fullContext = $this->gatherFullContext($userId, $userInput);

        // 2. Ottieni tutti i task candidati
        $tasks = $this->getTasksWithScoring($userId, $fullContext);

        if (empty($tasks)) {
            return $this->getNoTasksResponse();
        }

        // 3. Filtra task già suggeriti di recente
        $tasks = $this->filterRecentlySuggested($tasks, $userId);

        if (empty($tasks)) {
            // Se tutti i task sono stati suggeriti, resetta e riparti
            $this->resetSuggestionsHistory($userId);
            $tasks = $this->getTasksWithScoring($userId, $fullContext);
        }

        // 4. Prova con AI se disponibile
        if ($this->shouldUseAI($fullContext)) {
            try {
                $aiSuggestion = $this->getAISuggestion($tasks, $fullContext);
                if ($aiSuggestion) {
                    $this->recordSuggestion($userId, $aiSuggestion);
                    return $aiSuggestion;
                }
            } catch (\Exception $e) {
                error_log('AI suggestion failed: ' . $e->getMessage());
            }
        }

        // 5. Fallback intelligente con scoring
        $suggestion = $this->getSmartFallback($tasks, $fullContext);
        $this->recordSuggestion($userId, $suggestion);
        return $suggestion;
    }

    /**
     * Raccogli contesto completo per decisione ottimale
     */
    private function gatherFullContext(int $userId, array $userInput): array {
        $now = new \DateTime();
        $hour = (int)$now->format('H');
        $dayOfWeek = (int)$now->format('N');

        // Dati base temporali
        $context = [
            'current_time' => $now->format('Y-m-d H:i:s'),
            'hour' => $hour,
            'day_of_week' => $dayOfWeek,
            'is_weekend' => $dayOfWeek >= 6,
            'time_of_day' => $this->getTimeOfDay($hour),
        ];

        // Energia e focus (da input utente o stimati)
        $context['energy_level'] = $userInput['energy'] ?? $this->estimateEnergy($hour, $userId);
        $context['focus_minutes'] = $userInput['focus_time'] ?? $this->estimateFocusTime($hour, $context['energy_level']);
        $context['mood'] = $userInput['mood'] ?? 'neutral';
        $context['distractions'] = $userInput['distractions'] ?? 'normal';

        // Analisi produttività storica
        $productivity = $this->analyzeProductivity($userId);
        $context['hours_worked_today'] = $productivity['today_hours'];
        $context['hours_worked_week'] = $productivity['week_hours'];
        $context['tasks_completed_today'] = $productivity['completed_today'];
        $context['tasks_completed_week'] = $productivity['completed_week'];
        $context['average_task_time'] = $productivity['avg_task_time'];
        $context['best_performance_hours'] = $productivity['best_hours'];

        // Statistiche task
        $taskStats = $this->getTaskStatistics($userId);
        $context['total_open_tasks'] = $taskStats['total_open'];
        $context['overdue_count'] = $taskStats['overdue'];
        $context['urgent_count'] = $taskStats['urgent'];
        $context['in_progress_count'] = $taskStats['in_progress'];
        $context['blocked_count'] = $taskStats['blocked'];

        // Pattern e preferenze
        $context['prefers_short_tasks'] = $productivity['avg_task_time'] < 60;
        $context['morning_person'] = in_array(9, $productivity['best_hours']) || in_array(10, $productivity['best_hours']);
        $context['can_deep_work'] = $context['energy_level'] === 'high' && $context['distractions'] === 'low';

        // Suggerimenti precedenti (per evitare ripetizioni)
        $context['last_suggestions'] = $this->getRecentSuggestions($userId, 5);
        $context['suggestion_count_today'] = count(array_filter($context['last_suggestions'], function($s) {
            return date('Y-m-d', strtotime($s['created_at'])) === date('Y-m-d');
        }));

        return $context;
    }

    /**
     * Ottieni task con scoring basato sul contesto
     */
    private function getTasksWithScoring(int $userId, array $context): array {
        $filters = ['assignee' => auth()['name']];
        $allTasks = $this->taskModel->getAllWithProjects($filters);

        // Filtra solo task non completati
        $activeTasks = array_filter($allTasks, function($task) {
            return $task['status'] !== 'Fatto';
        });

        // Calcola score per ogni task
        foreach ($activeTasks as &$task) {
            $task['score'] = $this->calculateTaskScore($task, $context);
            $task['score_breakdown'] = $this->getScoreBreakdown($task, $context);
        }

        // Ordina per score decrescente
        usort($activeTasks, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $activeTasks;
    }

    /**
     * Calcola score del task basato su molteplici fattori
     */
    private function calculateTaskScore(array $task, array $context): float {
        $score = 0;

        // 1. Urgenza (max 30 punti)
        if ($task['due_at']) {
            $daysUntilDue = (strtotime($task['due_at']) - time()) / 86400;
            if ($daysUntilDue < 0) {
                $score += 30; // Scaduto
            } elseif ($daysUntilDue < 1) {
                $score += 25; // Scade oggi
            } elseif ($daysUntilDue < 3) {
                $score += 20; // Prossimi 3 giorni
            } elseif ($daysUntilDue < 7) {
                $score += 10; // Questa settimana
            }
        }

        // 2. Priorità (max 20 punti)
        $priority = $task['priority'] ?? 'Media';
        $score += match($priority) {
            'Alta' => 20,
            'Media' => 10,
            'Bassa' => 5,
            default => 10
        };

        // 3. Stato di completamento (max 25 punti)
        $completion = $this->calculateCompletion($task);
        if ($completion >= 75) {
            $score += 25; // Quasi finito, alta priorità
        } elseif ($completion >= 50) {
            $score += 15; // A metà
        } elseif ($completion > 0) {
            $score += 10; // Iniziato
        }

        // 4. Match con energia/tempo disponibile (max 15 punti)
        $estimatedTime = ($task['hours_estimated'] ?? 2) - ($task['hours_spent'] ?? 0);
        $availableTime = $context['focus_minutes'] / 60;

        if ($estimatedTime > 0 && $estimatedTime <= $availableTime) {
            $score += 15; // Task completabile nel tempo disponibile
        } elseif ($estimatedTime > 0 && $estimatedTime <= $availableTime * 1.5) {
            $score += 10; // Forse completabile
        }

        // 5. Match con livello energia (max 10 punti)
        if ($context['energy_level'] === 'high' && $priority === 'Alta') {
            $score += 10;
        } elseif ($context['energy_level'] === 'low' && $completion >= 75) {
            $score += 10; // Quick win per energia bassa
        } elseif ($context['energy_level'] === 'medium') {
            $score += 5;
        }

        // 6. Penalità per task già suggeriti oggi (-10 punti)
        if (in_array($task['id'], array_column($context['last_suggestions'], 'task_id'))) {
            $score -= 10;
        }

        // 7. Bonus per task bloccati che si sono sbloccati (+5 punti)
        // (implementare logica per tracciare blocchi)

        // 8. Bonus per task nel "best performance hours" (+5 punti)
        if (in_array($context['hour'], $context['best_performance_hours'])) {
            $score += 5;
        }

        return $score;
    }

    /**
     * Breakdown dettagliato dello score
     */
    private function getScoreBreakdown(array $task, array $context): array {
        return [
            'urgency' => $this->getUrgencyScore($task),
            'priority' => $this->getPriorityScore($task),
            'completion' => $this->getCompletionScore($task),
            'energy_match' => $this->getEnergyMatchScore($task, $context),
            'time_match' => $this->getTimeMatchScore($task, $context),
            'context_bonus' => $this->getContextBonus($task, $context)
        ];
    }

    /**
     * Filtra task suggeriti di recente
     */
    private function filterRecentlySuggested(array $tasks, int $userId): array {
        $recentIds = $this->getRecentSuggestionIds($userId, 3); // Ultimi 3 suggerimenti

        return array_filter($tasks, function($task) use ($recentIds) {
            return !in_array($task['id'], $recentIds);
        });
    }

    /**
     * Ottieni ID dei task suggeriti di recente
     */
    private function getRecentSuggestionIds(int $userId, int $limit = 5): array {
        try {
            $stmt = $this->db->prepare('
                SELECT DISTINCT task_id
                FROM ai_suggestion_history
                WHERE user_id = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)
                ORDER BY created_at DESC
                LIMIT ?
            ');
            $stmt->execute([$userId, $limit]);
            return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'task_id');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Registra suggerimento
     */
    private function recordSuggestion(int $userId, array $suggestion): void {
        try {
            // Crea tabella se non esiste
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS ai_suggestion_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    task_id INT,
                    suggestion_type VARCHAR(50),
                    context_json JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_task (user_id, task_id),
                    INDEX idx_created (created_at)
                )
            ');

            $taskId = null;
            if (isset($suggestion['primary_task']['id'])) {
                $taskId = $suggestion['primary_task']['id'];
            } elseif (isset($suggestion['task']['id'])) {
                $taskId = $suggestion['task']['id'];
            }

            if ($taskId) {
                $stmt = $this->db->prepare('
                    INSERT INTO ai_suggestion_history
                    (user_id, task_id, suggestion_type, context_json)
                    VALUES (?, ?, ?, ?)
                ');
                $stmt->execute([
                    $userId,
                    $taskId,
                    $suggestion['type'] ?? 'unknown',
                    json_encode($suggestion)
                ]);
            }
        } catch (\Exception $e) {
            error_log('Failed to record suggestion: ' . $e->getMessage());
        }
    }

    /**
     * Reset storia suggerimenti (quando tutti i task sono stati suggeriti)
     */
    private function resetSuggestionsHistory(int $userId): void {
        try {
            $stmt = $this->db->prepare('
                DELETE FROM ai_suggestion_history
                WHERE user_id = ?
                AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ');
            $stmt->execute([$userId]);
        } catch (\Exception $e) {
            error_log('Failed to reset suggestions: ' . $e->getMessage());
        }
    }

    /**
     * Suggerimento AI migliorato
     */
    private function getAISuggestion(array $tasks, array $context): ?array {
        // Prendi solo top 10 task per non sovraccaricare l'AI
        $topTasks = array_slice($tasks, 0, 10);

        $prompt = $this->buildEnhancedPrompt($topTasks, $context);

        try {
            $response = $this->callAI($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 1000
            ]);

            if (!$response) return null;

            $data = $this->parseAIResponse($response);
            if (!$data) return null;

            return $this->formatAIDecision($data, $tasks, $context);

        } catch (\Exception $e) {
            error_log('AI call failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Prompt AI migliorato con tutti i dati
     */
    private function buildEnhancedPrompt(array $tasks, array $context): string {
        $tasksJson = json_encode(array_map(function($task) {
            return [
                'id' => $task['id'],
                'title' => $task['title'],
                'project' => $task['project_name'] ?? 'N/A',
                'status' => $task['status'],
                'priority' => $task['priority'] ?? 'Media',
                'due_at' => $task['due_at'],
                'hours_left' => ($task['hours_estimated'] ?? 2) - ($task['hours_spent'] ?? 0),
                'completion' => $this->calculateCompletion($task),
                'score' => $task['score'],
                'score_breakdown' => $task['score_breakdown'] ?? []
            ];
        }, $tasks), JSON_PRETTY_PRINT);

        $contextJson = json_encode([
            'time' => $context['current_time'],
            'energy' => $context['energy_level'],
            'focus_available' => $context['focus_minutes'] . ' minuti',
            'mood' => $context['mood'],
            'hours_worked_today' => $context['hours_worked_today'],
            'tasks_done_today' => $context['tasks_completed_today'],
            'open_tasks' => $context['total_open_tasks'],
            'urgent_tasks' => $context['urgent_count'],
            'best_hours' => $context['best_performance_hours']
        ], JSON_PRETTY_PRINT);

        return <<<PROMPT
Sei un coach esperto in produttività e ADHD. Analizza i task e il contesto per suggerire IL task migliore da fare ORA.

CONTESTO UTENTE:
$contextJson

TOP 10 TASK (ordinati per score):
$tasksJson

CRITERI DI DECISIONE:
1. Urgenza e scadenze (ma non solo questo)
2. Match energia/focus disponibile con difficoltà task
3. Probabilità di completamento nel tempo disponibile
4. Momentum (privilegia task già iniziati)
5. Quick wins se energia bassa
6. Evita context switching inutile
7. Considera l'umore e le distrazioni

ANALIZZA E RISPONDI IN JSON:
{
    "primary_task_id": <ID del task principale>,
    "reasoning": "<Spiegazione dettagliata del PERCHÉ questo task ora (3-4 frasi)>",
    "specific_action": "<COSA fare esattamente nei prossimi {$context['focus_minutes']} minuti>",
    "expected_progress": "<Quanto progresso realistico aspettarsi>",
    "backup_task_id": <ID task alternativo se il primo non va>,
    "backup_reason": "<Perché questo come piano B>",
    "energy_match": <true/false>,
    "completion_probability": <0-100>,
    "motivation": "<Frase motivazionale personalizzata>",
    "warning": "<Cosa evitare in base al contesto>",
    "after_this": "<Cosa fare dopo aver finito questa sessione>"
}

IMPORTANTE:
- Se l'utente ha poca energia, privilegia task quasi completi o facili
- Se ha molta energia, suggerisci task importanti/difficili
- Considera sempre il tempo disponibile reale
- Non suggerire task già suggeriti nelle ultime 2 ore
PROMPT;
    }

    /**
     * Fallback intelligente migliorato
     */
    private function getSmartFallback(array $tasks, array $context): array {
        // Prendi il top task basato su score
        $primaryTask = $tasks[0] ?? null;
        $backupTask = $tasks[1] ?? null;

        if (!$primaryTask) {
            return $this->getNoTasksResponse();
        }

        // Determina strategia basata su contesto
        $strategy = $this->determineStrategy($primaryTask, $context);

        return [
            'type' => 'smart_fallback',
            'strategy' => $strategy,
            'primary_task' => array_merge($primaryTask, [
                'suggestion' => [
                    'why_this' => $this->explainChoice($primaryTask, $context),
                    'what_to_do' => $this->getSpecificAction($primaryTask, $context),
                    'time_needed' => $this->estimateTimeNeeded($primaryTask),
                    'completion_chance' => $this->estimateCompletionChance($primaryTask, $context)
                ]
            ]),
            'backup_task' => $backupTask ? array_merge($backupTask, [
                'suggestion' => [
                    'why_this' => 'Alternativa se il primo non funziona',
                    'time_needed' => $this->estimateTimeNeeded($backupTask)
                ]
            ]) : null,
            'motivation' => $this->getContextualMotivation($strategy, $context),
            'warning' => $this->getContextualWarning($context),
            'context_summary' => [
                'energy' => $context['energy_level'],
                'focus' => $context['focus_minutes'] . ' min',
                'tasks_open' => $context['total_open_tasks']
            ]
        ];
    }

    /**
     * Helper functions
     */
    private function getTimeOfDay(int $hour): string {
        if ($hour < 6) return 'night';
        if ($hour < 12) return 'morning';
        if ($hour < 17) return 'afternoon';
        if ($hour < 21) return 'evening';
        return 'night';
    }

    private function estimateEnergy(int $hour, int $userId): string {
        $hoursWorked = $this->getHoursWorkedToday($userId);

        if ($hoursWorked > 6) return 'low';
        if ($hoursWorked > 4) return 'medium';

        if ($hour >= 9 && $hour <= 11) return 'high';
        if ($hour >= 14 && $hour <= 16) return 'medium';
        if ($hour >= 18) return 'low';

        return 'medium';
    }

    private function estimateFocusTime(int $hour, string $energy): int {
        $base = match($energy) {
            'high' => 90,
            'medium' => 45,
            'low' => 25,
            default => 30
        };

        // Riduci nel pomeriggio
        if ($hour >= 13 && $hour <= 15) {
            $base *= 0.7;
        }

        return (int)$base;
    }

    private function calculateCompletion(array $task): float {
        if (!empty($task['hours_estimated']) && $task['hours_estimated'] > 0) {
            return min(100, ($task['hours_spent'] ?? 0) / $task['hours_estimated'] * 100);
        }

        return match($task['status']) {
            'In corso' => 50,
            'In revisione' => 85,
            'Fatto' => 100,
            default => 0
        };
    }

    private function getHoursWorkedToday(int $userId): float {
        $logs = $this->timeLogModel->where([
            'person' => auth()['name'],
            'date' => date('Y-m-d')
        ]);
        return array_sum(array_column($logs, 'hours'));
    }

    private function analyzeProductivity(int $userId): array {
        // Implementazione base
        return [
            'today_hours' => $this->getHoursWorkedToday($userId),
            'week_hours' => 20, // TODO: calcolare reale
            'completed_today' => 2, // TODO: calcolare reale
            'completed_week' => 8, // TODO: calcolare reale
            'avg_task_time' => 45, // TODO: calcolare reale
            'best_hours' => [9, 10, 11, 15, 16] // TODO: calcolare reale
        ];
    }

    private function getTaskStatistics(int $userId): array {
        $tasks = $this->taskModel->where(['assignee' => auth()['name']]);

        return [
            'total_open' => count(array_filter($tasks, fn($t) => $t['status'] !== 'Fatto')),
            'overdue' => count(array_filter($tasks, fn($t) =>
                $t['due_at'] && strtotime($t['due_at']) < time()
            )),
            'urgent' => count(array_filter($tasks, fn($t) =>
                $t['due_at'] && strtotime($t['due_at']) < strtotime('+2 days')
            )),
            'in_progress' => count(array_filter($tasks, fn($t) => $t['status'] === 'In corso')),
            'blocked' => 0 // TODO: implementare logica blocchi
        ];
    }

    private function getRecentSuggestions(int $userId, int $limit): array {
        try {
            $stmt = $this->db->prepare('
                SELECT * FROM ai_suggestion_history
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ');
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    // ... Altri metodi helper ...

    private function getNoTasksResponse(): array {
        return [
            'type' => 'no_tasks',
            'message' => '🎉 Nessun task aperto! Tempo per una pausa o per pianificare nuovi obiettivi.',
            'suggestion' => 'Prenditi una pausa o crea nuove attività per domani.'
        ];
    }
}