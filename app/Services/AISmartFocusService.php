<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TimeLog;

/**
 * AI-Powered Smart Focus Service per ADHD
 * Usa OpenAI/Claude per suggerimenti intelligenti e personalizzati
 */
class AISmartFocusService extends BaseAIService {

    private Task $taskModel;
    private TimeLog $timeLogModel;

    // Cache per ridurre chiamate API (valida 30 minuti)
    private const CACHE_TTL = 1800;

    public function __construct() {
        parent::__construct();
        $this->taskModel = new Task();
        $this->timeLogModel = new TimeLog();
    }

    /**
     * Ottieni suggerimento intelligente via AI
     */
    public function getSmartSuggestion(int $userId, array $userInput = []): array {
        try {
            // 1. Check cache first (risparmia API calls)
            $cacheKey = $this->getCacheKey($userId, $userInput);
            $cached = $this->getFromCache($cacheKey);
            if ($cached) {
                return $cached;
            }

            // 2. Prepara dati
            $tasks = $this->getActiveTasks($userId);
            if (empty($tasks)) {
                return $this->getEmptyResponse();
            }

            $context = $this->buildContext($userId, $userInput);

            // 3. Prepara prompt ottimizzato (breve = meno token)
            $prompt = $this->buildOptimizedPrompt($tasks, $context);

            // 4. Chiama AI
            $aiResponse = $this->callAIService($prompt);

            if (!$aiResponse) {
                // Fallback a logica locale se AI fallisce
                return $this->getLocalFallback($tasks, $context);
            }

            // 5. Processa risposta
            $result = $this->processAIResponse($aiResponse, $tasks);

            // 6. Cache result
            $this->saveToCache($cacheKey, $result, self::CACHE_TTL);

            // 7. Track usage
            $this->trackUsage($prompt, $aiResponse);

            return $result;

        } catch (\Exception $e) {
            error_log('AISmartFocus Error: ' . $e->getMessage());
            return $this->getLocalFallback($tasks ?? [], $context ?? []);
        }
    }

    /**
     * Prompt ottimizzato per minori token
     */
    private function buildOptimizedPrompt(array $tasks, array $context): string {
        // Prepara lista task compatta
        $taskList = [];
        foreach (array_slice($tasks, 0, 10) as $task) { // Max 10 task per ridurre token
            $status = $task['status'] === 'In corso' ? '[IN CORSO]' :
                     ($task['status'] === 'Da fare' ? '[DA FARE]' : '[' . strtoupper($task['status']) . ']');

            $due = '';
            if ($task['due_at']) {
                $daysUntil = (strtotime($task['due_at']) - time()) / 86400;
                if ($daysUntil < 0) $due = '[SCADUTO]';
                elseif ($daysUntil < 1) $due = '[OGGI]';
                elseif ($daysUntil < 3) $due = '[URGENTE]';
            }

            $completion = '';
            if ($task['hours_estimated'] > 0 && $task['hours_spent'] > 0) {
                $pct = round(($task['hours_spent'] / $task['hours_estimated']) * 100);
                if ($pct >= 75) $completion = "[{$pct}% FATTO]";
            }

            $taskList[] = sprintf(
                "%d. %s %s %s %s - P:%s",
                $task['id'],
                $status,
                $due,
                $completion,
                $task['title'],
                $task['priority'] ?? 'Media'
            );
        }

        // Prompt conciso
        $prompt = "Sei un coach ADHD. Analizza e suggerisci.\n\n";
        $prompt .= "UTENTE:\n";
        $prompt .= "- Energia: {$context['energy']}\n";
        $prompt .= "- Tempo: {$context['focus_time']}min\n";
        $prompt .= "- Umore: {$context['mood']}\n";

        if ($context['preferred_strategy']) {
            $prompt .= "- Vuole: {$context['preferred_strategy']}\n";
        }

        $prompt .= "\nTASK:\n" . implode("\n", $taskList);

        $prompt .= "\n\nRISPONDI SOLO JSON:
{
  \"primary\": {
    \"id\": [task_id],
    \"why\": \"[max 15 parole]\",
    \"action\": \"[max 10 parole]\",
    \"minutes\": [numero]
  },
  \"alt1\": {\"id\": [task_id], \"type\": \"quick_win|easy|energy_match\"},
  \"alt2\": {\"id\": [task_id], \"type\": \"...\"},
  \"tip\": \"[max 10 parole motivazionali]\"
}";

        return $prompt;
    }

    /**
     * Chiama AI (OpenAI o fallback)
     */
    protected function callAIService(string $prompt): ?array {
        // Prendi API key dal database (non da .env!)
        $apiKey = $this->getOpenAIKeyFromDB();
        if (!$apiKey) {
            error_log('OpenAI API key not configured in settings');
            return null;
        }

        // Usa il metodo del BaseAIService con opzioni ottimizzate
        $options = [
            'model' => 'gpt-3.5-turbo',
            'max_tokens' => 200,
            'temperature' => 0.7,
            'system_prompt' => 'Rispondi SOLO in formato JSON valido. Niente altro testo.',
            'api_key' => $apiKey // Passa la chiave dal DB
        ];

        $response = $this->callOpenAI($prompt, $options);

        if ($response) {
            // Il response è già decodificato
            return $response;
        }

        return null;
    }

    /**
     * Prendi OpenAI key dal database settings
     */
    private function getOpenAIKeyFromDB(): ?string {
        try {
            $stmt = $this->db->prepare('
                SELECT openai_api_key
                FROM ai_settings
                WHERE user_id = ?
                LIMIT 1
            ');
            $stmt->execute([auth()['id']]);
            $result = $stmt->fetch();

            if ($result && !empty($result['openai_api_key'])) {
                return $result['openai_api_key'];
            }
        } catch (\Exception $e) {
            error_log('Error getting OpenAI key from DB: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Processa risposta AI nel formato finale
     */
    private function processAIResponse($aiResponse, array $tasks): array {
        // Se aiResponse è una stringa, decodificala
        if (is_string($aiResponse)) {
            $aiResponse = json_decode($aiResponse, true);
        }

        if (!is_array($aiResponse)) {
            return $this->getLocalFallback($tasks, []);
        }
        // Mappa task per ID
        $taskMap = [];
        foreach ($tasks as $task) {
            $taskMap[$task['id']] = $task;
        }

        $result = [
            'type' => 'ai_suggestion',
            'source' => 'openai'
        ];

        // Primary task
        if (isset($aiResponse['primary']) && isset($taskMap[$aiResponse['primary']['id']])) {
            $primaryTask = $taskMap[$aiResponse['primary']['id']];
            $primaryTask['suggestion'] = [
                'why_this' => $aiResponse['primary']['why'] ?? 'Task prioritario',
                'what_to_do' => $aiResponse['primary']['action'] ?? 'Inizia subito',
                'time_needed' => ($aiResponse['primary']['minutes'] ?? 30) . ' minuti',
                'completion_chance' => $this->estimateCompletion($primaryTask)
            ];
            $result['primary_task'] = $primaryTask;
        }

        // Alternatives
        $result['alternatives'] = [];

        if (isset($aiResponse['alt1']) && isset($taskMap[$aiResponse['alt1']['id']])) {
            $result['alternatives'][] = [
                'task' => $taskMap[$aiResponse['alt1']['id']],
                'type' => $aiResponse['alt1']['type'] ?? 'alternative',
                'reason' => $this->getReasonForType($aiResponse['alt1']['type'] ?? '')
            ];
        }

        if (isset($aiResponse['alt2']) && isset($taskMap[$aiResponse['alt2']['id']])) {
            $result['alternatives'][] = [
                'task' => $taskMap[$aiResponse['alt2']['id']],
                'type' => $aiResponse['alt2']['type'] ?? 'alternative',
                'reason' => $this->getReasonForType($aiResponse['alt2']['type'] ?? '')
            ];
        }

        // Motivation
        $result['motivation'] = $aiResponse['tip'] ?? 'Puoi farcela!';

        // Context summary
        $result['context_summary'] = [
            'tasks_open' => count($tasks),
            'ai_confidence' => 95
        ];

        return $result;
    }

    /**
     * Fallback locale (quando AI non disponibile)
     */
    private function getLocalFallback(array $tasks, array $context): array {
        if (empty($tasks)) {
            return $this->getEmptyResponse();
        }

        // Logica semplice: prendi task più urgente o in corso
        $primary = null;
        foreach ($tasks as $task) {
            if ($task['status'] === 'In corso') {
                $primary = $task;
                break;
            }
        }

        if (!$primary) {
            // Ordina per urgenza
            usort($tasks, function($a, $b) {
                $aUrgent = $a['due_at'] ? strtotime($a['due_at']) : PHP_INT_MAX;
                $bUrgent = $b['due_at'] ? strtotime($b['due_at']) : PHP_INT_MAX;
                return $aUrgent <=> $bUrgent;
            });
            $primary = $tasks[0];
        }

        $primary['suggestion'] = [
            'why_this' => $primary['status'] === 'In corso' ? 'Già iniziato, continua!' : 'Task più urgente',
            'what_to_do' => 'Concentrati su questo',
            'time_needed' => '45 minuti',
            'completion_chance' => 70
        ];

        return [
            'type' => 'local_fallback',
            'primary_task' => $primary,
            'alternatives' => array_slice($tasks, 1, 2),
            'motivation' => 'Un passo alla volta!',
            'context_summary' => [
                'tasks_open' => count($tasks)
            ]
        ];
    }

    /**
     * Get active tasks
     */
    private function getActiveTasks(int $userId): array {
        $filters = ['assignee' => auth()['name']];
        $tasks = $this->taskModel->getAllWithProjects($filters);

        return array_filter($tasks, fn($t) => $t['status'] !== 'Fatto');
    }

    /**
     * Build context
     */
    private function buildContext(int $userId, array $input): array {
        return [
            'energy' => $input['energy'] ?? 'medium',
            'focus_time' => (int)($input['focus_time'] ?? 45),
            'mood' => $input['mood'] ?? 'neutral',
            'preferred_strategy' => $input['strategy'] ?? null
        ];
    }

    /**
     * Cache management
     */
    private function getCacheKey(int $userId, array $input): string {
        return 'smart_focus_' . $userId . '_' . md5(json_encode($input));
    }

    private function getFromCache(string $key): ?array {
        try {
            $stmt = $this->db->prepare('
                SELECT response FROM ai_cache
                WHERE cache_key = ? AND expires_at > NOW()
            ');
            $stmt->execute([$key]);
            $result = $stmt->fetch();

            if ($result) {
                return json_decode($result['response'], true);
            }
        } catch (\Exception $e) {
            // Cache miss
        }
        return null;
    }

    private function saveToCache(string $key, array $data, int $ttl): void {
        try {
            // Create table if not exists
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS ai_cache (
                    cache_key VARCHAR(255) PRIMARY KEY,
                    response TEXT,
                    expires_at DATETIME,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ');

            $stmt = $this->db->prepare('
                REPLACE INTO ai_cache (cache_key, response, expires_at)
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
            ');
            $stmt->execute([$key, json_encode($data), $ttl]);
        } catch (\Exception $e) {
            // Cache save failed, not critical
        }
    }

    /**
     * Track API usage for cost monitoring
     */
    private function trackUsage(string $prompt, array $response): void {
        try {
            $tokens = (strlen($prompt) / 4) + 200; // Rough estimate
            $cost = $tokens * 0.000002; // GPT-3.5 pricing

            $stmt = $this->db->prepare('
                INSERT INTO ai_api_usage
                (user_id, endpoint, tokens_used, cost_usd, request_time)
                VALUES (?, ?, ?, ?, NOW())
            ');
            $stmt->execute([
                auth()['id'],
                'smart_focus',
                $tokens,
                $cost
            ]);
        } catch (\Exception $e) {
            // Usage tracking failed, not critical
        }
    }

    private function estimateCompletion(array $task): int {
        if ($task['hours_estimated'] > 0 && $task['hours_spent'] > 0) {
            $pct = ($task['hours_spent'] / $task['hours_estimated']) * 100;
            if ($pct >= 80) return 95;
            if ($pct >= 50) return 70;
        }
        return 50;
    }

    private function getReasonForType(string $type): string {
        return match($type) {
            'quick_win' => '🏆 Vittoria facile',
            'easy' => '🌱 Facile da iniziare',
            'energy_match' => '⚡ Adatto alla tua energia',
            default => '🎯 Alternativa valida'
        };
    }

    private function getEmptyResponse(): array {
        return [
            'type' => 'no_tasks',
            'message' => 'Nessun task da fare! Prenditi una pausa.',
            'tasks' => []
        ];
    }
}