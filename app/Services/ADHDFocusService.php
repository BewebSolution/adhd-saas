<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TimeLog;
use App\Models\Project;

/**
 * ADHD Focus Service - "Cosa FINIRE oggi"
 * Ottimizzato per persone con ADHD con focus su completamento
 */
class ADHDFocusService extends BaseAIService {
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
     * Ottieni suggerimento su cosa COMPLETARE oggi
     */
    public function getWhatToFinishToday(int $userId): ?array {
        // Ottieni task in vari stati
        $allTasks = $this->getCategorizedTasks($userId);

        if (empty($allTasks['all'])) {
            return [
                'type' => 'no_tasks',
                'message' => '🎉 Niente da fare! Prenditi una pausa o crea nuove attività.',
                'tasks' => []
            ];
        }

        // Ottieni contesto temporale e personale
        $context = $this->getEnhancedContext($userId);

        // Prova con AI se disponibile
        try {
            $aiSuggestion = $this->getAISuggestion($allTasks, $context);
            if ($aiSuggestion) {
                return $aiSuggestion;
            }
        } catch (\Exception $e) {
            error_log('AI Focus error: ' . $e->getMessage());
        }

        // Fallback intelligente ADHD-friendly
        return $this->getIntelligentFallback($allTasks, $context);
    }

    /**
     * Categorizza task per stato di completamento
     */
    private function getCategorizedTasks(int $userId): array {
        $filters = ['assignee' => auth()['name']];
        $allTasks = $this->taskModel->getAllWithProjects($filters);

        $categorized = [
            'almost_done' => [],      // 75-99% complete
            'in_progress' => [],       // 25-74% complete
            'just_started' => [],      // 1-24% complete
            'not_started' => [],       // 0% complete
            'overdue' => [],          // Past due date
            'urgent' => [],           // Due today or tomorrow
            'all' => []
        ];

        foreach ($allTasks as $task) {
            // Skip completed tasks
            if ($task['status'] === 'Fatto') {
                continue;
            }

            $categorized['all'][] = $task;

            // Calculate completion percentage
            $completion = $this->calculateCompletion($task);

            // Categorize by completion
            if ($completion >= 75) {
                $categorized['almost_done'][] = $task;
            } elseif ($completion >= 25) {
                $categorized['in_progress'][] = $task;
            } elseif ($completion > 0) {
                $categorized['just_started'][] = $task;
            } else {
                $categorized['not_started'][] = $task;
            }

            // Check if overdue
            if ($task['due_at'] && strtotime($task['due_at']) < time()) {
                $categorized['overdue'][] = $task;
            }
            // Check if urgent (due within 48 hours)
            elseif ($task['due_at'] && strtotime($task['due_at']) < strtotime('+2 days')) {
                $categorized['urgent'][] = $task;
            }
        }

        return $categorized;
    }

    /**
     * Calcola percentuale di completamento
     */
    private function calculateCompletion(array $task): float {
        // If we have hours estimate
        if (!empty($task['hours_estimated']) && $task['hours_estimated'] > 0) {
            return min(100, ($task['hours_spent'] ?? 0) / $task['hours_estimated'] * 100);
        }

        // Based on status
        return match($task['status']) {
            'In corso' => 50,
            'In revisione' => 85,
            'Fatto' => 100,
            default => 0
        };
    }

    /**
     * Contesto enhanced per ADHD
     */
    private function getEnhancedContext(int $userId): array {
        $hour = (int)date('H');
        $dayOfWeek = date('N');

        // Analizza energia basata su ora e lavoro fatto oggi
        $hoursToday = $this->getHoursWorkedToday($userId);

        $energyLevel = $this->estimateEnergyLevel($hour, $hoursToday);
        $focusCapacity = $this->estimateFocusCapacity($hour, $dayOfWeek);

        return [
            'hour' => $hour,
            'day_of_week' => $dayOfWeek,
            'energy_level' => $energyLevel,
            'focus_capacity' => $focusCapacity,
            'hours_worked_today' => $hoursToday,
            'is_fresh' => $hoursToday < 2,
            'is_tired' => $hoursToday > 6
        ];
    }

    /**
     * Stima livello di energia
     */
    private function estimateEnergyLevel(int $hour, float $hoursWorked): string {
        // Energy deteriora con ore lavorate
        if ($hoursWorked > 6) return 'low';
        if ($hoursWorked > 4) return 'medium';

        // Energy varia per ora del giorno
        if ($hour >= 9 && $hour <= 11) return 'high';      // Peak mattutino
        if ($hour >= 14 && $hour <= 16) return 'medium';   // Post-pranzo
        if ($hour >= 17 && $hour <= 19) return 'low';      // Fine giornata
        if ($hour >= 20) return 'very_low';                // Sera

        return 'medium';
    }

    /**
     * Stima capacità di focus
     */
    private function estimateFocusCapacity(int $hour, int $dayOfWeek): int {
        // Base capacity (minutes)
        $baseCapacity = 45;

        // Reduce for afternoon slump
        if ($hour >= 13 && $hour <= 15) {
            $baseCapacity -= 15;
        }

        // Reduce for end of week
        if ($dayOfWeek >= 5) {
            $baseCapacity -= 10;
        }

        // Reduce for evening
        if ($hour >= 18) {
            $baseCapacity -= 20;
        }

        return max(15, $baseCapacity);
    }

    /**
     * Ore lavorate oggi
     */
    private function getHoursWorkedToday(int $userId): float {
        $today = date('Y-m-d');
        $logs = $this->timeLogModel->where([
            'person' => auth()['name'],
            'date' => $today
        ]);

        return array_sum(array_column($logs, 'hours'));
    }

    /**
     * Suggerimento AI enhanced
     */
    private function getAISuggestion(array $tasks, array $context): ?array {
        $prompt = $this->buildADHDPrompt($tasks, $context);

        try {
            $response = $this->callAI($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 800
            ]);

            if (!$response) return null;

            // Parse JSON response
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}') + 1;

            if ($jsonStart === false || $jsonEnd === false) {
                return null;
            }

            $json = substr($response, $jsonStart, $jsonEnd - $jsonStart);
            $data = json_decode($json, true);

            if (!$data) return null;

            return $this->formatAIResponse($data, $tasks);

        } catch (\Exception $e) {
            error_log('AI suggestion failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build ADHD-optimized prompt
     */
    private function buildADHDPrompt(array $tasks, array $context): string {
        $tasksJson = json_encode([
            'quasi_finiti' => array_map([$this, 'taskToPromptFormat'], $tasks['almost_done']),
            'in_corso' => array_map([$this, 'taskToPromptFormat'], $tasks['in_progress']),
            'urgenti' => array_map([$this, 'taskToPromptFormat'], $tasks['urgent']),
            'scaduti' => array_map([$this, 'taskToPromptFormat'], $tasks['overdue'])
        ]);

        $contextJson = json_encode($context);

        return <<<PROMPT
Tu sei un coach specializzato in ADHD. Il tuo obiettivo è aiutarmi a FINIRE qualcosa oggi, non iniziare nuove cose.

PRINCIPI ADHD DA SEGUIRE:
1. Completare > Iniziare (meglio finire una cosa che iniziarne tre)
2. Momentum > Perfezione (piccoli progressi sono vittorie)
3. Energia corrisponde al task (task difficili quando energia alta)
4. Una cosa alla volta (no multitasking)
5. Quick wins quando energia bassa (completare cose quasi finite)

TASKS DISPONIBILI:
$tasksJson

CONTESTO ATTUALE:
$contextJson

ANALIZZA E SUGGERISCI:
1. Quale task posso COMPLETARE o fare progressi significativi OGGI?
2. Considera il mio livello di energia ({$context['energy_level']})
3. Considera che posso concentrarmi per circa {$context['focus_capacity']} minuti
4. Se sono stanco, suggerisci quick wins (task quasi completi)
5. Se sono fresco, suggerisci task importanti ma fattibili

Rispondi in JSON:
{
    "primary_task": {
        "id": <task_id>,
        "why_this": "<perché questo task ORA (max 2 frasi motivanti)>",
        "what_to_do": "<azione specifica da fare (es: 'Completa sezione header', non generico)>",
        "time_needed": "<tempo realistico in minuti>",
        "completion_chance": <0-100% probabilità di completarlo oggi>
    },
    "backup_task": {
        "id": <task_id alternativo se il primo non va>,
        "why_this": "<perché questo come backup>"
    },
    "motivation": "<frase motivazionale personalizzata ADHD-friendly>",
    "warning": "<cosa evitare oggi (es: 'Non iniziare nuovi progetti')>"
}
PROMPT;
    }

    /**
     * Convert task to prompt format
     */
    private function taskToPromptFormat(array $task): array {
        return [
            'id' => $task['id'],
            'titolo' => $task['title'],
            'stato' => $task['status'],
            'priorita' => $task['priority'] ?? 'Media',
            'scadenza' => $task['due_at'],
            'ore_spese' => $task['hours_spent'] ?? 0,
            'ore_stimate' => $task['hours_estimated'] ?? 0,
            'completamento' => $this->calculateCompletion($task)
        ];
    }

    /**
     * Format AI response
     */
    private function formatAIResponse(array $data, array $tasks): array {
        // Find the actual task objects
        $primaryTask = null;
        $backupTask = null;

        foreach ($tasks['all'] as $task) {
            if ($task['id'] == ($data['primary_task']['id'] ?? 0)) {
                $primaryTask = $task;
            }
            if ($task['id'] == ($data['backup_task']['id'] ?? 0)) {
                $backupTask = $task;
            }
        }

        if (!$primaryTask) {
            return null;
        }

        return [
            'type' => 'ai_suggestion',
            'primary_task' => array_merge($primaryTask, [
                'suggestion' => $data['primary_task']
            ]),
            'backup_task' => $backupTask ? array_merge($backupTask, [
                'suggestion' => $data['backup_task']
            ]) : null,
            'motivation' => $data['motivation'] ?? '💪 Puoi farcela!',
            'warning' => $data['warning'] ?? null
        ];
    }

    /**
     * Fallback intelligente senza AI
     */
    private function getIntelligentFallback(array $tasks, array $context): array {
        $strategy = $this->chooseStrategy($tasks, $context);
        $selectedTasks = $this->selectTasksByStrategy($tasks, $strategy, $context);

        return [
            'type' => 'smart_fallback',
            'strategy' => $strategy,
            'primary_task' => $selectedTasks['primary'] ?? null,
            'backup_task' => $selectedTasks['backup'] ?? null,
            'motivation' => $this->getMotivation($strategy, $context),
            'warning' => $this->getWarning($strategy, $context)
        ];
    }

    /**
     * Scegli strategia basata su contesto
     */
    private function chooseStrategy(array $tasks, array $context): string {
        // Se energia bassa e ci sono task quasi completi
        if ($context['energy_level'] === 'low' && !empty($tasks['almost_done'])) {
            return 'quick_win';
        }

        // Se ci sono task urgenti/scaduti
        if (!empty($tasks['overdue']) || !empty($tasks['urgent'])) {
            return 'urgent_first';
        }

        // Se energia alta e mattina
        if ($context['energy_level'] === 'high' && $context['hour'] < 12) {
            return 'deep_work';
        }

        // Se ci sono task in progress
        if (!empty($tasks['in_progress'])) {
            return 'finish_first';
        }

        // Default
        return 'maintenance';
    }

    /**
     * Seleziona task basato su strategia
     */
    private function selectTasksByStrategy(array $tasks, string $strategy, array $context): array {
        $selected = ['primary' => null, 'backup' => null];

        switch ($strategy) {
            case 'quick_win':
                // Prendi il task più vicino al completamento
                if (!empty($tasks['almost_done'])) {
                    $selected['primary'] = $this->addSuggestionDetails(
                        $tasks['almost_done'][0],
                        '🏁 Quasi fatto! Completalo e sentiti soddisfatto.',
                        '30 minuti per finire'
                    );
                }
                break;

            case 'urgent_first':
                // Priorità a scaduti/urgenti
                $urgent = array_merge($tasks['overdue'], $tasks['urgent']);
                if (!empty($urgent)) {
                    $selected['primary'] = $this->addSuggestionDetails(
                        $urgent[0],
                        '⚠️ Urgente! Meglio toglierlo di mezzo.',
                        '1-2 ore'
                    );
                }
                break;

            case 'deep_work':
                // Task importante che richiede focus
                $important = array_filter($tasks['all'], function($t) {
                    return ($t['priority'] ?? 'Media') === 'Alta' &&
                           $t['status'] !== 'Fatto';
                });

                if (!empty($important)) {
                    $selected['primary'] = $this->addSuggestionDetails(
                        reset($important),
                        '🧠 Energia alta = momento perfetto per task importante!',
                        '90 minuti di focus profondo'
                    );
                }
                break;

            case 'finish_first':
                // Continua quello che hai iniziato
                if (!empty($tasks['in_progress'])) {
                    $selected['primary'] = $this->addSuggestionDetails(
                        $tasks['in_progress'][0],
                        '🔄 Continua quello che hai iniziato. No context switching!',
                        '45-60 minuti'
                    );
                }
                break;

            default:
                // Maintenance: qualsiasi task gestibile
                if (!empty($tasks['all'])) {
                    $selected['primary'] = $this->addSuggestionDetails(
                        $tasks['all'][0],
                        '📝 Un passo alla volta verso il progresso.',
                        '45 minuti'
                    );
                }
        }

        return $selected;
    }

    /**
     * Aggiungi dettagli suggestion al task
     */
    private function addSuggestionDetails(array $task, string $why, string $time): array {
        $task['suggestion'] = [
            'why_this' => $why,
            'time_needed' => $time,
            'what_to_do' => $this->getSpecificAction($task),
            'completion_chance' => $this->estimateCompletionChance($task)
        ];
        return $task;
    }

    /**
     * Ottieni azione specifica
     */
    private function getSpecificAction(array $task): string {
        $completion = $this->calculateCompletion($task);

        if ($completion >= 75) {
            return 'Finisci gli ultimi dettagli e marca come completato';
        } elseif ($completion >= 50) {
            return 'Continua da dove hai lasciato, sei a metà strada!';
        } elseif ($completion > 0) {
            return 'Riprendi il lavoro iniziato, costruisci momentum';
        } else {
            return 'Inizia con il primo passo, anche piccolo';
        }
    }

    /**
     * Stima probabilità di completamento oggi
     */
    private function estimateCompletionChance(array $task): int {
        $completion = $this->calculateCompletion($task);

        if ($completion >= 90) return 95;
        if ($completion >= 75) return 85;
        if ($completion >= 50) return 70;
        if ($completion >= 25) return 60;

        return 40;
    }

    /**
     * Ottieni motivazione basata su strategia
     */
    private function getMotivation(string $strategy, array $context): string {
        $motivations = [
            'quick_win' => [
                '🏆 Quick win = dopamina istantanea! Perfetto per ADHD.',
                '✨ Completare qualcosa > iniziare mille cose.',
                '🎯 Piccole vittorie costruiscono momentum!'
            ],
            'urgent_first' => [
                '🔥 Togliamoci il pensiero! Poi relax.',
                '⚡ Affronta l\'urgenza ora che hai energia.',
                '💪 Dopo questo, tutto sarà più leggero.'
            ],
            'deep_work' => [
                '🧠 Momento di focus ottimale! Sfruttalo.',
                '🚀 Energia alta = progressi importanti.',
                '⭐ Questo è il tuo momento produttivo!'
            ],
            'finish_first' => [
                '🔄 Continua = no context switching = meno fatica.',
                '📈 Sei già nel flow, mantienilo!',
                '🎪 Momentum > ricominciare da capo.'
            ],
            'maintenance' => [
                '🌱 Ogni piccolo passo conta.',
                '🐢 Lento ma costante vince la gara.',
                '☘️ Progress > perfezione.'
            ]
        ];

        $options = $motivations[$strategy] ?? $motivations['maintenance'];
        return $options[array_rand($options)];
    }

    /**
     * Ottieni warning basato su contesto
     */
    private function getWarning(string $strategy, array $context): string {
        if ($context['energy_level'] === 'low') {
            return '⚠️ Energia bassa: evita nuovi progetti complessi';
        }

        if ($context['hours_worked_today'] > 6) {
            return '😴 Hai lavorato molto: evita task che richiedono creatività';
        }

        if ($context['is_tired']) {
            return '🛑 Stanchezza rilevata: NO multitasking!';
        }

        if ($strategy === 'quick_win') {
            return '📝 Focus: FINIRE, non perfezionare';
        }

        return '🎯 Ricorda: una cosa alla volta!';
    }
}