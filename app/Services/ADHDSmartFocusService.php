<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TimeLog;
use App\Models\Project;

/**
 * ADHD Smart Focus Service - Il servizio definitivo per ADHD
 *
 * Filosofia:
 * - Sempre almeno 3 opzioni (no paralisi decisionale)
 * - Rotazione intelligente senza blocchi rigidi
 * - Spiegazioni chiare del perché
 * - Quick wins sempre disponibili
 * - Mai "nessun suggerimento"
 */
class ADHDSmartFocusService extends BaseAIService {
    private Task $taskModel;
    private TimeLog $timeLogModel;

    // Costanti per strategia ADHD
    private const MIN_SUGGESTIONS = 3;  // Sempre almeno 3 opzioni
    private const ROTATION_MEMORY = 5;  // Ricorda ultimi 5 suggerimenti (non blocca)
    private const QUICK_WIN_THRESHOLD = 70; // Task 70%+ sono quick wins

    public function __construct() {
        parent::__construct();
        $this->taskModel = new Task();
        $this->timeLogModel = new TimeLog();
    }

    /**
     * Entry point principale - SEMPRE ritorna suggerimenti utili
     */
    public function getSmartSuggestion(int $userId, array $userInput = []): array {
        try {
            // 1. Prendi TUTTI i task attivi
            $allTasks = $this->getAllTasks($userId);

            if (empty($allTasks)) {
                return $this->getEmptyStateResponse();
            }

            // 2. Costruisci contesto dettagliato
            $context = $this->buildRichContext($userId, $userInput);

            // 3. Categorizza i task per tipo
            $categorized = $this->categorizeTasks($allTasks, $context);

            // 4. Calcola score per ogni task
            $scoredTasks = $this->scoreTasks($allTasks, $context, $categorized);

            // 5. Ottieni suggerimenti recenti (per variety, non per bloccare)
            $recentSuggestions = $this->getRecentSuggestions($userId);

            // 6. Genera MULTIPLE strategie di suggerimento
            $suggestions = $this->generateMultipleSuggestions(
                $scoredTasks,
                $categorized,
                $context,
                $recentSuggestions
            );

            // 7. Registra il suggerimento principale (non blocca future richieste)
            if (!empty($suggestions['primary'])) {
                $this->softRecordSuggestion($userId, $suggestions['primary']['id']);
            }

            return $this->formatResponse($suggestions, $context, $categorized, $allTasks);

        } catch (\Exception $e) {
            error_log('ADHDSmartFocusService Error: ' . $e->getMessage());
            // Fallback robusto - mai lasciare l'utente senza suggerimenti
            return $this->getRobustFallback($userId);
        }
    }

    /**
     * Ottieni tutti i task non completati
     */
    private function getAllTasks(int $userId): array {
        $filters = ['assignee' => auth()['name']];
        $tasks = $this->taskModel->getAllWithProjects($filters);

        // Solo task non completati
        return array_filter($tasks, fn($t) => $t['status'] !== 'Fatto');
    }

    /**
     * Costruisci contesto ricco per decisioni migliori
     */
    private function buildRichContext(int $userId, array $userInput): array {
        $now = new \DateTime();
        $hour = (int)$now->format('H');
        $dayOfWeek = (int)$now->format('N');

        // Ore lavorate oggi
        $hoursToday = $this->getHoursWorkedToday($userId);

        // Determina periodo del giorno
        $timeOfDay = $this->getTimeOfDay($hour);

        // Energia (user input o stimata)
        $energy = $userInput['energy'] ?? $this->estimateEnergy($hour, $hoursToday);

        // Focus disponibile
        $focusMinutes = (int)($userInput['focus_time'] ?? $this->estimateFocusTime($energy));

        // Mood e contesto emotivo
        $mood = $userInput['mood'] ?? 'neutral';

        return [
            'hour' => $hour,
            'day_of_week' => $dayOfWeek,
            'time_of_day' => $timeOfDay,
            'is_weekend' => $dayOfWeek >= 6,
            'energy' => $energy,
            'focus_minutes' => $focusMinutes,
            'mood' => $mood,
            'hours_worked' => $hoursToday,
            'is_fresh' => $hoursToday < 2,
            'is_tired' => $hoursToday > 5,
            'needs_quick_win' => ($energy === 'low' || $mood === 'stressed'),
            'can_deep_work' => ($energy === 'high' && $focusMinutes >= 60),
            'preferred_strategy' => $userInput['preferred_strategy'] ?? null,
            // Fattori ADHD specifici
            'decision_fatigue' => $this->hasDecisionFatigue($userId),
            'needs_variety' => true, // Sempre true per ADHD
            'prefers_structure' => $mood !== 'stressed'
        ];
    }

    /**
     * Categorizza task per facilitare selezione
     */
    private function categorizeTasks(array $tasks, array $context): array {
        $categories = [
            'overdue' => [],
            'due_today' => [],
            'due_soon' => [],
            'in_progress' => [],
            'quick_wins' => [],
            'important' => [],
            'easy_starts' => [],
            'deep_work' => []
        ];

        foreach ($tasks as $task) {
            // Overdue
            if ($task['due_at'] && strtotime($task['due_at']) < time()) {
                $categories['overdue'][] = $task;
            }

            // Due today
            elseif ($task['due_at'] && date('Y-m-d', strtotime($task['due_at'])) === date('Y-m-d')) {
                $categories['due_today'][] = $task;
            }

            // Due soon (next 3 days)
            elseif ($task['due_at'] && strtotime($task['due_at']) < strtotime('+3 days')) {
                $categories['due_soon'][] = $task;
            }

            // In progress
            if ($task['status'] === 'In corso') {
                $categories['in_progress'][] = $task;
            }

            // Quick wins (>70% complete o <30 min rimanenti)
            $completion = $this->calculateCompletion($task);
            $remainingHours = ($task['hours_estimated'] ?? 2) - ($task['hours_spent'] ?? 0);
            if ($completion >= self::QUICK_WIN_THRESHOLD || $remainingHours <= 0.5) {
                $categories['quick_wins'][] = $task;
            }

            // Important
            if (($task['priority'] ?? 'Media') === 'Alta') {
                $categories['important'][] = $task;
            }

            // Easy starts (non iniziati, bassa/media priorità, <2h stimate)
            if ($task['status'] === 'Da fare' &&
                ($task['priority'] ?? 'Media') !== 'Alta' &&
                ($task['hours_estimated'] ?? 2) <= 2) {
                $categories['easy_starts'][] = $task;
            }

            // Deep work (importante + lungo)
            if (($task['priority'] ?? 'Media') === 'Alta' &&
                ($task['hours_estimated'] ?? 2) > 2) {
                $categories['deep_work'][] = $task;
            }
        }

        return $categories;
    }

    /**
     * Calcola score per ogni task
     */
    private function scoreTasks(array $tasks, array $context, array $categories): array {
        $scoredTasks = [];

        foreach ($tasks as $task) {
            $score = 0;
            $scoreBreakdown = [];

            // 1. URGENZA (0-30 punti)
            $urgencyScore = $this->calculateUrgencyScore($task);
            $score += $urgencyScore;
            $scoreBreakdown['urgency'] = $urgencyScore;

            // 2. PRIORITÀ (0-20 punti)
            $priorityScore = $this->calculatePriorityScore($task);
            $score += $priorityScore;
            $scoreBreakdown['priority'] = $priorityScore;

            // 3. MOMENTUM (0-20 punti) - privilegia task iniziati
            $momentumScore = $this->calculateMomentumScore($task);
            $score += $momentumScore;
            $scoreBreakdown['momentum'] = $momentumScore;

            // 4. COMPLETION (0-15 punti) - quick wins
            $completionScore = $this->calculateCompletionScore($task);
            $score += $completionScore;
            $scoreBreakdown['completion'] = $completionScore;

            // 5. CONTEXT MATCH (0-15 punti)
            $contextScore = $this->calculateContextMatch($task, $context);
            $score += $contextScore;
            $scoreBreakdown['context'] = $contextScore;

            // 6. BONUS/PENALTY per variety (-5 a +5)
            // Penalizza leggermente task suggeriti troppo spesso
            $varietyScore = $this->calculateVarietyScore($task, $context);
            $score += $varietyScore;
            $scoreBreakdown['variety'] = $varietyScore;

            $task['score'] = $score;
            $task['score_breakdown'] = $scoreBreakdown;
            $task['score_explanation'] = $this->explainScore($scoreBreakdown);

            $scoredTasks[] = $task;
        }

        // Ordina per score
        usort($scoredTasks, fn($a, $b) => $b['score'] <=> $a['score']);

        return $scoredTasks;
    }

    /**
     * Genera MULTIPLE suggerimenti con strategie diverse
     */
    private function generateMultipleSuggestions(
        array $scoredTasks,
        array $categories,
        array $context,
        array $recentSuggestions
    ): array {

        $suggestions = [];

        // 1. SUGGERIMENTO PRINCIPALE (basato su energia e strategia preferita)
        if ($context['preferred_strategy'] === 'quick_win' && !empty($categories['quick_wins'])) {
            // Se richiesto quick win, dai priorità a quello
            $primary = $categories['quick_wins'][0] ?? null;
        } elseif ($context['energy'] === 'low') {
            // Energia bassa: preferisci quick wins o task facili
            if (!empty($categories['quick_wins'])) {
                $primary = $categories['quick_wins'][0];
            } elseif (!empty($categories['easy_starts'])) {
                $primary = $categories['easy_starts'][0];
            } else {
                $primary = $this->selectWithVariety($scoredTasks, $recentSuggestions, 0);
            }
        } elseif ($context['energy'] === 'high') {
            // Energia alta: affronta task importanti o deep work
            if (!empty($categories['deep_work'])) {
                $primary = $categories['deep_work'][0];
            } elseif (!empty($categories['important'])) {
                $primary = $categories['important'][0];
            } else {
                $primary = $this->selectWithVariety($scoredTasks, $recentSuggestions, 0);
            }
        } else {
            // Energia media o default: usa il top score con variety
            $primary = $this->selectWithVariety($scoredTasks, $recentSuggestions, 0);
        }

        if ($primary) {
            $suggestions['primary'] = $primary;
        }

        // 2. QUICK WIN (sempre utile per ADHD)
        if (!empty($categories['quick_wins'])) {
            $quickWin = $this->selectBestFromCategory($categories['quick_wins'], $context);
            if ($quickWin && (!isset($suggestions['primary']) || $quickWin['id'] !== $suggestions['primary']['id'])) {
                $suggestions['quick_win'] = $quickWin;
            }
        }

        // 3. MOMENTUM KEEPER (continua quello che hai iniziato)
        if (!empty($categories['in_progress'])) {
            $momentum = $this->selectBestFromCategory($categories['in_progress'], $context);
            if ($momentum && $this->isUniqueSuggestion($momentum, $suggestions)) {
                $suggestions['momentum'] = $momentum;
            }
        }

        // 4. URGENT/IMPORTANT (non dimenticare le scadenze)
        $urgent = $this->selectMostUrgent($categories, $scoredTasks);
        if ($urgent && $this->isUniqueSuggestion($urgent, $suggestions)) {
            $suggestions['urgent'] = $urgent;
        }

        // 5. EASY START (quando serve iniziare qualcosa di nuovo)
        if ($context['needs_variety'] && !empty($categories['easy_starts'])) {
            $easy = $this->selectBestFromCategory($categories['easy_starts'], $context);
            if ($easy && $this->isUniqueSuggestion($easy, $suggestions)) {
                $suggestions['easy_start'] = $easy;
            }
        }

        // 6. ENERGY MATCH (task perfetto per il tuo livello energia)
        $energyMatch = $this->selectEnergyMatch($scoredTasks, $context);
        if ($energyMatch && $this->isUniqueSuggestion($energyMatch, $suggestions)) {
            $suggestions['energy_match'] = $energyMatch;
        }

        // GARANTISCI sempre almeno MIN_SUGGESTIONS opzioni
        $suggestions = $this->ensureMinimumSuggestions($suggestions, $scoredTasks);

        return $suggestions;
    }

    /**
     * Formatta la risposta finale
     */
    private function formatResponse(array $suggestions, array $context, array $categories, array $allTasks): array {
        // Conta statistiche
        $stats = [
            'total_tasks' => count($allTasks),
            'overdue' => count($categories['overdue']),
            'due_today' => count($categories['due_today']),
            'in_progress' => count($categories['in_progress']),
            'quick_wins' => count($categories['quick_wins'])
        ];

        // Suggerimento principale
        $primary = $suggestions['primary'] ?? $suggestions['quick_win'] ?? array_values($suggestions)[0] ?? null;

        if (!$primary) {
            return $this->getEmptyStateResponse();
        }

        // Costruisci array di alternative
        $alternatives = [];
        foreach ($suggestions as $type => $task) {
            if ($type !== 'primary' && $task['id'] !== $primary['id']) {
                $alternatives[] = [
                    'type' => $type,
                    'task' => $task,
                    'reason' => $this->getAlternativeReason($type, $task, $context)
                ];
            }
        }

        return [
            'type' => 'adhd_smart_focus',
            'primary_task' => $this->enrichTaskWithSuggestion($primary, $context, 'primary'),
            'alternatives' => array_slice($alternatives, 0, 3), // Max 3 alternative
            'motivation' => $this->getPersonalizedMotivation($context, $primary),
            'strategy_tip' => $this->getStrategyTip($context, $primary),
            'context_summary' => [
                'energy' => $context['energy'],
                'focus' => $context['focus_minutes'] . ' min',
                'mood' => $context['mood'],
                'stats' => $stats
            ],
            'quick_actions' => $this->getQuickActions($suggestions, $context)
        ];
    }

    /**
     * Arricchisci task con dettagli suggestion
     */
    private function enrichTaskWithSuggestion(array $task, array $context, string $type): array {
        $completion = $this->calculateCompletion($task);
        $remainingHours = ($task['hours_estimated'] ?? 2) - ($task['hours_spent'] ?? 0);

        $task['suggestion'] = [
            'why_this' => $this->explainWhyThisTask($task, $context, $type),
            'what_to_do' => $this->getSpecificNextAction($task, $completion),
            'time_needed' => $this->estimateSessionTime($remainingHours, $context),
            'completion_chance' => $this->calculateSuccessProbability($task, $context),
            'energy_required' => $this->getRequiredEnergy($task),
            'focus_tips' => $this->getFocusTips($task, $context)
        ];

        return $task;
    }

    // ===== METODI DI SUPPORTO =====

    private function calculateUrgencyScore(array $task): float {
        if (!$task['due_at']) return 0;

        $hoursUntilDue = (strtotime($task['due_at']) - time()) / 3600;

        if ($hoursUntilDue < 0) return 30;      // Scaduto
        if ($hoursUntilDue < 24) return 25;     // Oggi
        if ($hoursUntilDue < 72) return 20;     // 3 giorni
        if ($hoursUntilDue < 168) return 10;    // Settimana

        return 5;
    }

    private function calculatePriorityScore(array $task): float {
        return match($task['priority'] ?? 'Media') {
            'Alta' => 20,
            'Media' => 10,
            'Bassa' => 5,
            default => 10
        };
    }

    private function calculateMomentumScore(array $task): float {
        if ($task['status'] === 'In corso') return 20;
        if ($task['status'] === 'In revisione') return 15;
        return 0;
    }

    private function calculateCompletionScore(array $task): float {
        $completion = $this->calculateCompletion($task);

        if ($completion >= 90) return 15;
        if ($completion >= 75) return 12;
        if ($completion >= 50) return 8;
        if ($completion >= 25) return 5;

        return 0;
    }

    private function calculateContextMatch(array $task, array $context): float {
        $score = 0;

        // Match energia
        $requiredEnergy = $this->getRequiredEnergy($task);
        if ($context['energy'] === $requiredEnergy) {
            $score += 5;
        } elseif (
            ($context['energy'] === 'high' && $requiredEnergy === 'medium') ||
            ($context['energy'] === 'medium' && $requiredEnergy === 'low')
        ) {
            $score += 3;
        }

        // Match tempo
        $remainingHours = ($task['hours_estimated'] ?? 2) - ($task['hours_spent'] ?? 0);
        $availableHours = $context['focus_minutes'] / 60;

        if ($remainingHours > 0 && $remainingHours <= $availableHours) {
            $score += 5; // Completabile nella sessione
        } elseif ($remainingHours > 0 && $remainingHours <= $availableHours * 2) {
            $score += 3; // Buoni progressi possibili
        }

        // Bonus per match mood
        if ($context['mood'] === 'stressed' && $this->isQuickWin($task)) {
            $score += 5; // Quick win per mood stressato
        }

        return $score;
    }

    private function calculateVarietyScore(array $task, array $context): float {
        // Per ora ritorna 0, ma può penalizzare task suggeriti troppo spesso
        return 0;
    }

    private function calculateCompletion(array $task): float {
        if (!empty($task['hours_estimated']) && $task['hours_estimated'] > 0) {
            return min(100, ($task['hours_spent'] ?? 0) / $task['hours_estimated'] * 100);
        }

        return match($task['status']) {
            'In corso' => 50,
            'In revisione' => 85,
            default => 0
        };
    }

    private function isQuickWin(array $task): bool {
        $completion = $this->calculateCompletion($task);
        $remainingHours = ($task['hours_estimated'] ?? 2) - ($task['hours_spent'] ?? 0);

        return $completion >= self::QUICK_WIN_THRESHOLD || $remainingHours <= 0.5;
    }

    private function getRequiredEnergy(array $task): string {
        $priority = $task['priority'] ?? 'Media';
        $remainingHours = ($task['hours_estimated'] ?? 2) - ($task['hours_spent'] ?? 0);

        if ($priority === 'Alta' && $remainingHours > 2) return 'high';
        if ($this->isQuickWin($task)) return 'low';

        return 'medium';
    }

    private function selectWithVariety(array $tasks, array $recent, int $offset): ?array {
        // Prendi task con varietà (evita ripetizioni immediate)
        $recentIds = array_column($recent, 'task_id');

        foreach ($tasks as $i => $task) {
            if ($i < $offset) continue;

            // Preferisci task non suggeriti di recente
            if (!in_array($task['id'], array_slice($recentIds, 0, 3))) {
                return $task;
            }
        }

        // Se tutti sono stati suggeriti, prendi comunque il migliore
        return $tasks[$offset] ?? null;
    }

    private function selectBestFromCategory(array $categoryTasks, array $context): ?array {
        if (empty($categoryTasks)) return null;

        // Ri-score solo per questa categoria
        foreach ($categoryTasks as &$task) {
            $task['category_score'] = $this->calculateContextMatch($task, $context);
        }

        usort($categoryTasks, fn($a, $b) => $b['category_score'] <=> $a['category_score']);

        return $categoryTasks[0];
    }

    private function selectMostUrgent(array $categories, array $allTasks): ?array {
        // Prima overdue, poi due_today, poi due_soon
        if (!empty($categories['overdue'])) {
            return $categories['overdue'][0];
        }
        if (!empty($categories['due_today'])) {
            return $categories['due_today'][0];
        }
        if (!empty($categories['due_soon'])) {
            return $categories['due_soon'][0];
        }

        // Altrimenti il task con priorità più alta
        foreach ($allTasks as $task) {
            if (($task['priority'] ?? '') === 'Alta') {
                return $task;
            }
        }

        return null;
    }

    private function selectEnergyMatch(array $tasks, array $context): ?array {
        foreach ($tasks as $task) {
            if ($this->getRequiredEnergy($task) === $context['energy']) {
                return $task;
            }
        }
        return null;
    }

    private function isUniqueSuggestion(array $task, array $suggestions): bool {
        foreach ($suggestions as $sug) {
            if ($sug['id'] === $task['id']) {
                return false;
            }
        }
        return true;
    }

    private function ensureMinimumSuggestions(array $suggestions, array $allTasks): array {
        $count = count($suggestions);
        $usedIds = array_map(fn($s) => $s['id'], $suggestions);

        $i = 0;
        while ($count < self::MIN_SUGGESTIONS && $i < count($allTasks)) {
            if (!in_array($allTasks[$i]['id'], $usedIds)) {
                $suggestions['extra_' . $count] = $allTasks[$i];
                $usedIds[] = $allTasks[$i]['id'];
                $count++;
            }
            $i++;
        }

        return $suggestions;
    }

    private function explainWhyThisTask(array $task, array $context, string $type): string {
        $reasons = [];

        // Ragioni basate sul tipo
        if ($type === 'quick_win') {
            $reasons[] = '🏆 Quick win! Completalo per una vittoria veloce';
        } elseif ($type === 'momentum') {
            $reasons[] = '🔄 Già iniziato - mantieni il momentum';
        } elseif ($type === 'urgent') {
            $reasons[] = '🚨 Urgente - meglio non rimandare';
        }

        // Ragioni basate su scadenza
        if ($task['due_at']) {
            $hoursUntil = (strtotime($task['due_at']) - time()) / 3600;
            if ($hoursUntil < 0) {
                $reasons[] = '⏰ È già scaduto!';
            } elseif ($hoursUntil < 24) {
                $reasons[] = '📅 Scade oggi';
            }
        }

        // Ragioni basate su contesto
        if ($context['energy'] === 'low' && $this->isQuickWin($task)) {
            $reasons[] = '💡 Perfetto per energia bassa';
        } elseif ($context['energy'] === 'high' && ($task['priority'] ?? '') === 'Alta') {
            $reasons[] = '⚡ Hai energia per questo task importante';
        }

        return implode('. ', array_slice($reasons, 0, 2)) ?:
               'È il task migliore per il tuo contesto attuale';
    }

    private function getSpecificNextAction(array $task, float $completion): string {
        if ($completion >= 90) {
            return 'Ultimi ritocchi e chiudi! Sei al 90%+';
        } elseif ($completion >= 70) {
            return 'Spingi per completare - sei quasi alla fine';
        } elseif ($completion >= 50) {
            return 'Continua da dove hai lasciato - sei a metà';
        } elseif ($task['status'] === 'In corso') {
            return 'Riprendi il lavoro e fai progressi concreti';
        } else {
            return 'Inizia con il primo passo piccolo e concreto';
        }
    }

    private function estimateSessionTime(float $remainingHours, array $context): string {
        $focusHours = $context['focus_minutes'] / 60;

        if ($remainingHours <= 0.25) return '15 minuti bastano';
        if ($remainingHours <= 0.5) return '30 minuti';
        if ($remainingHours <= 1 && $focusHours >= 1) return '1 ora per finire';
        if ($remainingHours <= $focusHours) return round($remainingHours * 60) . ' minuti';

        return round(min($remainingHours, $focusHours) * 60) . ' minuti di progresso';
    }

    private function calculateSuccessProbability(array $task, array $context): int {
        $completion = $this->calculateCompletion($task);
        $remainingHours = ($task['hours_estimated'] ?? 2) - ($task['hours_spent'] ?? 0);
        $focusHours = $context['focus_minutes'] / 60;

        // Quick wins hanno alta probabilità
        if ($completion >= 90) return 95;
        if ($completion >= 75 && $remainingHours <= $focusHours) return 85;

        // Task in corso con tempo sufficiente
        if ($task['status'] === 'In corso' && $remainingHours <= $focusHours) {
            return 75;
        }

        // Match energia aumenta probabilità
        if ($this->getRequiredEnergy($task) === $context['energy']) {
            return 70;
        }

        return 50;
    }

    private function getFocusTips(array $task, array $context): array {
        $tips = [];

        if ($context['energy'] === 'low') {
            $tips[] = '☕ Fai una pausa caffè prima di iniziare';
            $tips[] = '⏱️ Usa timer 15-20 minuti';
        }

        if ($this->isQuickWin($task)) {
            $tips[] = '🎯 Focus: completare, non perfezionare';
            $tips[] = '✅ Obiettivo: chiudere il task oggi';
        }

        if ($task['status'] === 'In corso') {
            $tips[] = '📝 Rileggi dove eri rimasto';
            $tips[] = '🔄 Riparti dall\'ultimo checkpoint';
        }

        return array_slice($tips, 0, 2);
    }

    private function getAlternativeReason(string $type, array $task, array $context): string {
        return match($type) {
            'quick_win' => '🏆 Vittoria veloce in ' . $this->estimateSessionTime(
                ($task['hours_estimated'] ?? 2) - ($task['hours_spent'] ?? 0),
                $context
            ),
            'momentum' => '🔄 Continua quello che hai iniziato',
            'urgent' => '⚠️ Ha una scadenza vicina',
            'easy_start' => '🌱 Facile da iniziare',
            'energy_match' => '⚡ Perfetto per il tuo livello energia',
            default => '📌 Alternativa valida'
        };
    }

    private function getPersonalizedMotivation(array $context, array $task): string {
        $motivations = [];

        if ($context['energy'] === 'low') {
            $motivations[] = '🌟 Anche piccoli passi sono progressi!';
            $motivations[] = '☘️ Piano piano, senza fretta';
        } elseif ($context['energy'] === 'high') {
            $motivations[] = '🚀 Momento perfetto per spaccare!';
            $motivations[] = '⚡ Sfrutta questa energia!';
        }

        if ($this->isQuickWin($task)) {
            $motivations[] = '🏁 Puoi chiudere questo oggi!';
        }

        if ($context['mood'] === 'stressed') {
            $motivations[] = '💚 Respira. Un passo alla volta.';
        }

        return $motivations[array_rand($motivations)] ?? '💪 Ce la puoi fare!';
    }

    private function getStrategyTip(array $context, array $task): string {
        if ($context['needs_quick_win']) {
            return '💡 Tip: Inizia con qualcosa di piccolo per costruire momentum';
        }

        if ($context['can_deep_work']) {
            return '🧠 Tip: Hai energia per deep work - elimina distrazioni';
        }

        if ($task['status'] === 'In corso') {
            return '📌 Tip: Riparti da dove hai lasciato, non ricominciare da capo';
        }

        return '🎯 Tip: Una cosa alla volta, senza multitasking';
    }

    private function getQuickActions(array $suggestions, array $context): array {
        $actions = [];

        if (isset($suggestions['quick_win'])) {
            $actions[] = [
                'label' => '🏆 Quick Win',
                'task_id' => $suggestions['quick_win']['id']
            ];
        }

        if ($context['energy'] === 'low' && isset($suggestions['easy_start'])) {
            $actions[] = [
                'label' => '🌱 Inizia Facile',
                'task_id' => $suggestions['easy_start']['id']
            ];
        }

        if (isset($suggestions['urgent'])) {
            $actions[] = [
                'label' => '🚨 Più Urgente',
                'task_id' => $suggestions['urgent']['id']
            ];
        }

        return array_slice($actions, 0, 3);
    }

    // ===== UTILITY METHODS =====

    private function getHoursWorkedToday(int $userId): float {
        try {
            $logs = $this->timeLogModel->where([
                'person' => auth()['name'],
                'date' => date('Y-m-d')
            ]);
            return array_sum(array_column($logs, 'hours'));
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTimeOfDay(int $hour): string {
        if ($hour < 6) return 'night';
        if ($hour < 12) return 'morning';
        if ($hour < 17) return 'afternoon';
        if ($hour < 21) return 'evening';
        return 'night';
    }

    private function estimateEnergy(int $hour, float $hoursWorked): string {
        if ($hoursWorked > 6) return 'low';
        if ($hoursWorked > 4) return 'medium';

        if ($hour >= 9 && $hour <= 11) return 'high';
        if ($hour >= 14 && $hour <= 16) return 'medium';
        if ($hour >= 18) return 'low';

        return 'medium';
    }

    private function estimateFocusTime(string $energy): int {
        return match($energy) {
            'high' => 90,
            'medium' => 45,
            'low' => 25,
            default => 30
        };
    }

    private function hasDecisionFatigue(int $userId): bool {
        // Controlla se ci sono state troppe richieste di suggerimenti
        // Per ora ritorna false, ma può essere implementato
        return false;
    }

    private function getRecentSuggestions(int $userId): array {
        try {
            $stmt = $this->db->prepare('
                SELECT task_id, COUNT(*) as count
                FROM suggestion_history
                WHERE user_id = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY task_id
                ORDER BY created_at DESC
                LIMIT ' . self::ROTATION_MEMORY
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function softRecordSuggestion(int $userId, int $taskId): void {
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

            $stmt = $this->db->prepare('
                INSERT INTO suggestion_history (user_id, task_id)
                VALUES (?, ?)
            ');
            $stmt->execute([$userId, $taskId]);

            // Pulizia vecchi record (>24h)
            $stmt = $this->db->prepare('
                DELETE FROM suggestion_history
                WHERE user_id = ?
                AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ');
            $stmt->execute([$userId]);

        } catch (\Exception $e) {
            // Log ma non bloccare
            error_log('Soft record failed: ' . $e->getMessage());
        }
    }

    private function explainScore(array $breakdown): string {
        $parts = [];

        if ($breakdown['urgency'] > 20) {
            $parts[] = 'molto urgente';
        }
        if ($breakdown['priority'] >= 15) {
            $parts[] = 'priorità alta';
        }
        if ($breakdown['momentum'] > 10) {
            $parts[] = 'già iniziato';
        }
        if ($breakdown['completion'] > 10) {
            $parts[] = 'quasi finito';
        }

        return implode(', ', $parts) ?: 'task normale';
    }

    private function getEmptyStateResponse(): array {
        return [
            'type' => 'empty_state',
            'message' => '🎉 Wow! Nessun task aperto!',
            'suggestions' => [
                '🌟 Prenditi una pausa meritata',
                '📝 Pianifica i task di domani',
                '🎯 Rivedi gli obiettivi a lungo termine'
            ]
        ];
    }

    private function getRobustFallback(int $userId): array {
        try {
            $tasks = $this->getAllTasks($userId);

            if (empty($tasks)) {
                return $this->getEmptyStateResponse();
            }

            // Prendi primi 3 task qualsiasi
            $suggestions = array_slice($tasks, 0, 3);

            return [
                'type' => 'fallback',
                'primary_task' => $suggestions[0],
                'alternatives' => array_slice($suggestions, 1),
                'motivation' => '💪 Un passo alla volta!',
                'strategy_tip' => 'Inizia con qualcosa, il momentum arriverà'
            ];

        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Ricarica la pagina e riprova'
            ];
        }
    }
}