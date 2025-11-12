<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TimeLog;
use App\Models\Project;

/**
 * Simplified ADHD Focus Service - Versione funzionante e ottimizzata
 */
class SimplifiedADHDFocusService extends BaseAIService {
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
     * Ottieni suggerimento intelligente
     */
    public function getSmartSuggestion(int $userId, array $userInput = []): ?array {
        try {
            // 1. Raccogli tutti i task non completati
            $tasks = $this->getAllActiveTasks($userId);

            if (empty($tasks)) {
                return [
                    'type' => 'no_tasks',
                    'message' => '🎉 Nessun task da fare! Prenditi una pausa o crea nuove attività.',
                    'tasks' => []
                ];
            }

            // 2. Raccogli contesto
            $context = $this->buildContext($userId, $userInput);

            // 3. Calcola score per ogni task
            foreach ($tasks as &$task) {
                $task['score'] = $this->calculateScore($task, $context);
            }

            // 4. Ordina per score
            usort($tasks, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // 5. Evita ripetizioni
            $tasks = $this->filterRecentSuggestions($tasks, $userId);

            if (empty($tasks)) {
                // Reset e riprendi tutti i task
                $this->resetSuggestionHistory($userId);
                $tasks = $this->getAllActiveTasks($userId);
                foreach ($tasks as &$task) {
                    $task['score'] = $this->calculateScore($task, $context);
                }
                usort($tasks, function($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
            }

            // 6. Seleziona task principale e backup
            $primaryTask = $tasks[0] ?? null;
            $backupTask = $tasks[1] ?? null;

            if (!$primaryTask) {
                return [
                    'type' => 'no_tasks',
                    'message' => 'Nessun task disponibile al momento'
                ];
            }

            // 7. Costruisci risposta
            $response = $this->buildResponse($primaryTask, $backupTask, $context);

            // 8. Registra suggerimento
            $this->recordSuggestion($userId, $primaryTask['id']);

            return $response;

        } catch (\Exception $e) {
            error_log('SimplifiedADHDFocusService error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            // Fallback base
            return $this->getFallbackSuggestion($userId);
        }
    }

    /**
     * Ottieni tutti i task attivi
     */
    private function getAllActiveTasks(int $userId): array {
        try {
            $filters = ['assignee' => auth()['name']];
            $allTasks = $this->taskModel->getAllWithProjects($filters);

            // Filtra solo non completati
            return array_filter($allTasks, function($task) {
                return $task['status'] !== 'Fatto';
            });
        } catch (\Exception $e) {
            error_log('Error getting tasks: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Costruisci contesto
     */
    private function buildContext(int $userId, array $userInput): array {
        $hour = (int)date('H');
        $dayOfWeek = (int)date('N');

        // Ore lavorate oggi
        $hoursToday = 0;
        try {
            $logs = $this->timeLogModel->where([
                'person' => auth()['name'],
                'date' => date('Y-m-d')
            ]);
            $hoursToday = array_sum(array_column($logs, 'hours'));
        } catch (\Exception $e) {
            error_log('Error getting hours: ' . $e->getMessage());
        }

        return [
            'hour' => $hour,
            'day_of_week' => $dayOfWeek,
            'is_weekend' => $dayOfWeek >= 6,
            'energy_level' => $userInput['energy'] ?? $this->estimateEnergy($hour, $hoursToday),
            'focus_time' => (int)($userInput['focus_time'] ?? 45),
            'mood' => $userInput['mood'] ?? 'neutral',
            'hours_worked_today' => $hoursToday,
            'preferred_strategy' => $userInput['preferred_strategy'] ?? null
        ];
    }

    /**
     * Calcola score del task
     */
    private function calculateScore(array $task, array $context): float {
        $score = 0;

        // Urgenza (max 40 punti)
        if ($task['due_at']) {
            $daysUntilDue = (strtotime($task['due_at']) - time()) / 86400;
            if ($daysUntilDue < 0) {
                $score += 40; // Scaduto
            } elseif ($daysUntilDue < 1) {
                $score += 35; // Oggi
            } elseif ($daysUntilDue < 3) {
                $score += 25; // Prossimi 3 giorni
            } elseif ($daysUntilDue < 7) {
                $score += 15; // Questa settimana
            }
        }

        // Priorità (max 20 punti)
        $priority = $task['priority'] ?? 'Media';
        $score += match($priority) {
            'Alta' => 20,
            'Media' => 10,
            'Bassa' => 5,
            default => 10
        };

        // Stato (max 20 punti)
        if ($task['status'] === 'In corso') {
            $score += 20; // Continua quello che hai iniziato
        } elseif ($task['status'] === 'In revisione') {
            $score += 15;
        }

        // Completamento (max 20 punti)
        $completion = $this->calculateCompletion($task);
        if ($completion >= 75) {
            $score += 20; // Quick win!
        } elseif ($completion >= 50) {
            $score += 10;
        }

        // Match con energia
        if ($context['energy_level'] === 'low' && $completion >= 75) {
            $score += 10; // Quick win per energia bassa
        } elseif ($context['energy_level'] === 'high' && $priority === 'Alta') {
            $score += 10; // Task importante per energia alta
        }

        // Strategia preferita
        if ($context['preferred_strategy'] === 'quick_win' && $completion >= 75) {
            $score += 15;
        }

        return $score;
    }

    /**
     * Calcola percentuale completamento
     */
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

    /**
     * Filtra suggerimenti recenti
     */
    private function filterRecentSuggestions(array $tasks, int $userId): array {
        try {
            // Crea tabella se non esiste
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS suggestion_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    task_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_created (user_id, created_at)
                )
            ');

            // Ottieni ID suggeriti nelle ultime 2 ore
            $stmt = $this->db->prepare('
                SELECT DISTINCT task_id
                FROM suggestion_history
                WHERE user_id = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)
            ');
            $stmt->execute([$userId]);
            $recentIds = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'task_id');

            // Filtra
            return array_filter($tasks, function($task) use ($recentIds) {
                return !in_array($task['id'], $recentIds);
            });

        } catch (\Exception $e) {
            error_log('Error filtering suggestions: ' . $e->getMessage());
            return $tasks;
        }
    }

    /**
     * Registra suggerimento
     */
    private function recordSuggestion(int $userId, int $taskId): void {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO suggestion_history (user_id, task_id)
                VALUES (?, ?)
            ');
            $stmt->execute([$userId, $taskId]);
        } catch (\Exception $e) {
            error_log('Error recording suggestion: ' . $e->getMessage());
        }
    }

    /**
     * Reset storia suggerimenti
     */
    private function resetSuggestionHistory(int $userId): void {
        try {
            $stmt = $this->db->prepare('
                DELETE FROM suggestion_history
                WHERE user_id = ?
                AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ');
            $stmt->execute([$userId]);
        } catch (\Exception $e) {
            error_log('Error resetting history: ' . $e->getMessage());
        }
    }

    /**
     * Costruisci risposta
     */
    private function buildResponse(array $primaryTask, ?array $backupTask, array $context): array {
        $response = [
            'type' => 'smart_suggestion',
            'primary_task' => array_merge($primaryTask, [
                'suggestion' => [
                    'why_this' => $this->explainChoice($primaryTask, $context),
                    'what_to_do' => $this->getSpecificAction($primaryTask),
                    'time_needed' => $this->estimateTime($primaryTask),
                    'completion_chance' => $this->estimateCompletionChance($primaryTask, $context)
                ]
            ]),
            'backup_task' => null,
            'motivation' => $this->getMotivation($context),
            'warning' => $this->getWarning($context),
            'context_summary' => [
                'energy' => $context['energy_level'],
                'focus' => $context['focus_time'] . ' min',
                'tasks_open' => count($this->getAllActiveTasks(auth()['id']))
            ]
        ];

        if ($backupTask) {
            $response['backup_task'] = array_merge($backupTask, [
                'suggestion' => [
                    'why_this' => 'Piano B se il primo non funziona',
                    'time_needed' => $this->estimateTime($backupTask)
                ]
            ]);
        }

        return $response;
    }

    /**
     * Spiega la scelta
     */
    private function explainChoice(array $task, array $context): string {
        $reasons = [];

        if ($task['due_at'] && strtotime($task['due_at']) < time()) {
            $reasons[] = '🚨 È scaduto! Meglio completarlo subito';
        } elseif ($task['due_at'] && strtotime($task['due_at']) < strtotime('+1 day')) {
            $reasons[] = '⏰ Scade oggi/domani';
        }

        if (($task['priority'] ?? '') === 'Alta') {
            $reasons[] = '🎯 Priorità alta';
        }

        if ($task['status'] === 'In corso') {
            $reasons[] = '🔄 Già iniziato, mantieni il momentum';
        }

        $completion = $this->calculateCompletion($task);
        if ($completion >= 75) {
            $reasons[] = '🏁 Quasi finito! Quick win';
        }

        if ($context['energy_level'] === 'low' && $completion >= 75) {
            $reasons[] = '💡 Perfetto per energia bassa';
        } elseif ($context['energy_level'] === 'high' && ($task['priority'] ?? '') === 'Alta') {
            $reasons[] = '⚡ Hai energia per un task importante';
        }

        return empty($reasons) ?
            'È il task con il punteggio più alto basato sul contesto attuale' :
            implode('. ', array_slice($reasons, 0, 2));
    }

    /**
     * Azione specifica
     */
    private function getSpecificAction(array $task): string {
        $completion = $this->calculateCompletion($task);

        if ($completion >= 75) {
            return 'Completa gli ultimi dettagli e chiudi il task';
        } elseif ($completion >= 50) {
            return 'Continua da dove hai lasciato';
        } elseif ($task['status'] === 'In corso') {
            return 'Riprendi il lavoro e fai progressi';
        } else {
            return 'Inizia con il primo passo concreto';
        }
    }

    /**
     * Stima tempo
     */
    private function estimateTime(array $task): string {
        $remaining = ($task['hours_estimated'] ?? 2) - ($task['hours_spent'] ?? 0);

        if ($remaining <= 0.5) return '30 minuti';
        if ($remaining <= 1) return '1 ora';
        if ($remaining <= 2) return '1-2 ore';
        return '2+ ore';
    }

    /**
     * Stima probabilità completamento
     */
    private function estimateCompletionChance(array $task, array $context): int {
        $completion = $this->calculateCompletion($task);
        $focusHours = $context['focus_time'] / 60;
        $remaining = ($task['hours_estimated'] ?? 2) - ($task['hours_spent'] ?? 0);

        if ($completion >= 90) return 95;
        if ($completion >= 75) return 85;
        if ($completion >= 50 && $remaining <= $focusHours) return 70;
        if ($remaining <= $focusHours) return 60;

        return 40;
    }

    /**
     * Motivazione
     */
    private function getMotivation(array $context): string {
        $motivations = [
            'high' => [
                '🚀 Energia alta! È il momento di spaccare!',
                '⚡ Sei carico! Affronta quella cosa importante!',
                '💪 Momento perfetto per task impegnativi!'
            ],
            'medium' => [
                '📈 Progressi costanti portano lontano!',
                '🎯 Un passo alla volta verso l\'obiettivo!',
                '✨ Mantieni il ritmo, stai andando bene!'
            ],
            'low' => [
                '🏆 Anche piccoli progressi sono vittorie!',
                '☕ Piano piano, senza stress!',
                '🌱 Ogni piccolo passo conta!'
            ]
        ];

        $energy = $context['energy_level'] ?? 'medium';
        $options = $motivations[$energy] ?? $motivations['medium'];
        return $options[array_rand($options)];
    }

    /**
     * Warning
     */
    private function getWarning(array $context): string {
        if ($context['energy_level'] === 'low') {
            return '⚠️ Energia bassa: evita task complessi';
        }

        if ($context['hours_worked_today'] > 6) {
            return '😴 Hai lavorato molto: considera una pausa';
        }

        if ($context['focus_time'] < 30) {
            return '⏱️ Poco tempo: focus su una cosa sola';
        }

        return '🎯 Ricorda: una cosa alla volta!';
    }

    /**
     * Stima energia
     */
    private function estimateEnergy(int $hour, float $hoursWorked): string {
        if ($hoursWorked > 6) return 'low';
        if ($hoursWorked > 4) return 'medium';

        if ($hour >= 9 && $hour <= 11) return 'high';
        if ($hour >= 14 && $hour <= 16) return 'medium';
        if ($hour >= 18) return 'low';

        return 'medium';
    }

    /**
     * Fallback suggestion
     */
    private function getFallbackSuggestion(int $userId): array {
        try {
            $tasks = $this->getAllActiveTasks($userId);
            if (empty($tasks)) {
                return [
                    'type' => 'no_tasks',
                    'message' => 'Nessun task disponibile'
                ];
            }

            // Prendi il primo task
            $task = $tasks[0];

            return [
                'type' => 'fallback',
                'primary_task' => array_merge($task, [
                    'suggestion' => [
                        'why_this' => 'È un task che devi fare',
                        'what_to_do' => 'Inizia o continua questo task',
                        'time_needed' => '45 minuti',
                        'completion_chance' => 50
                    ]
                ]),
                'motivation' => '💪 Puoi farcela!',
                'warning' => 'Una cosa alla volta'
            ];

        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Errore nel recupero dei task'
            ];
        }
    }
}