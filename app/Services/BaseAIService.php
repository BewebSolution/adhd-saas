<?php

namespace App\Services;

/**
 * Base AI Service - Gestisce chiamate API a Claude e OpenAI
 */
class BaseAIService {
    protected string $provider;
    protected string $claudeApiKey;
    protected string $openaiApiKey;
    protected \PDO $db;

    public function __construct() {
        $this->db = get_db();

        // Load API keys from database (if available) or fallback to .env
        $settings = $this->loadSettings();

        $this->provider = (!empty($settings['ai_provider'])) ? $settings['ai_provider'] : env('AI_PROVIDER', 'openai');
        $this->claudeApiKey = (!empty($settings['claude_api_key'])) ? $settings['claude_api_key'] : env('CLAUDE_API_KEY', '');
        $this->openaiApiKey = (!empty($settings['openai_api_key'])) ? $settings['openai_api_key'] : env('OPENAI_API_KEY', '');
    }

    /**
     * Load AI settings from database
     */
    private function loadSettings(): array {
        try {
            if (!isset(auth()['id'])) {
                return [];
            }

            $stmt = $this->db->prepare('
                SELECT ai_provider, openai_api_key, claude_api_key
                FROM ai_settings
                WHERE user_id = ?
            ');
            $stmt->execute([auth()['id']]);
            $settings = $stmt->fetch();

            return $settings ?: [];
        } catch (\Exception $e) {
            error_log('Failed to load AI settings from DB: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Chiamata Claude API
     */
    protected function callClaude(string $prompt, array $options = []): ?array {
        if (empty($this->claudeApiKey)) {
            error_log('Claude API key not configured');
            return null;
        }

        $model = $options['model'] ?? 'claude-3-5-sonnet-20241022';
        $maxTokens = $options['max_tokens'] ?? 1024;
        $temperature = $options['temperature'] ?? 0.7;

        $data = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        $startTime = microtime(true);

        try {
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-api-key: ' . $this->claudeApiKey,
                    'anthropic-version: 2023-06-01'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseTime = (microtime(true) - $startTime) * 1000;
            curl_close($ch);

            if ($httpCode !== 200) {
                error_log("Claude API error: HTTP $httpCode - $response");
                $this->logApiUsage(auth()['id'] ?? null, 'claude', null, 0, 0, $responseTime, false, "HTTP $httpCode");
                return null;
            }

            $result = json_decode($response, true);

            // Log usage
            $tokensUsed = ($result['usage']['input_tokens'] ?? 0) + ($result['usage']['output_tokens'] ?? 0);
            $cost = $this->calculateClaudeCost($tokensUsed, $model);
            $this->logApiUsage(auth()['id'] ?? null, 'claude', $model, $tokensUsed, $cost, $responseTime, true);

            return $result;
        } catch (\Exception $e) {
            error_log('Claude API exception: ' . $e->getMessage());
            $this->logApiUsage(auth()['id'] ?? null, 'claude', null, 0, 0, 0, false, $e->getMessage());
            return null;
        }
    }

    /**
     * Chiamata OpenAI API
     */
    protected function callOpenAI(string $prompt, array $options = []): ?array {
        // Usa API key dalle opzioni o dall'istanza
        $apiKey = $options['api_key'] ?? $this->openaiApiKey;

        if (empty($apiKey)) {
            error_log('OpenAI API key not configured');
            return null;
        }

        $model = $options['model'] ?? 'gpt-4-turbo-preview';
        $maxTokens = $options['max_tokens'] ?? 1024;
        $temperature = $options['temperature'] ?? 0.7;
        $systemPrompt = $options['system_prompt'] ?? null;

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $data = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature
        ];

        $startTime = microtime(true);

        try {
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseTime = (microtime(true) - $startTime) * 1000;
            curl_close($ch);

            if ($httpCode !== 200) {
                error_log("OpenAI API error: HTTP $httpCode - $response");
                $this->logApiUsage(auth()['id'] ?? null, 'openai', null, 0, 0, $responseTime, false, "HTTP $httpCode");
                return null;
            }

            $result = json_decode($response, true);

            // Log usage
            $tokensUsed = $result['usage']['total_tokens'] ?? 0;
            $cost = $this->calculateOpenAICost($tokensUsed, $model);
            $this->logApiUsage(auth()['id'] ?? null, 'openai', $model, $tokensUsed, $cost, $responseTime, true);

            return $result;
        } catch (\Exception $e) {
            error_log('OpenAI API exception: ' . $e->getMessage());
            $this->logApiUsage(auth()['id'] ?? null, 'openai', null, 0, 0, 0, false, $e->getMessage());
            return null;
        }
    }

    /**
     * Whisper API per speech-to-text
     */
    protected function callWhisper(string $audioPath): ?string {
        if (empty($this->openaiApiKey)) {
            return null;
        }

        $startTime = microtime(true);

        try {
            $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');

            $file = new \CURLFile($audioPath, 'audio/wav', 'audio.wav');
            $data = [
                'file' => $file,
                'model' => 'whisper-1',
                'language' => 'it'
            ];

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->openaiApiKey
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseTime = (microtime(true) - $startTime) * 1000;
            curl_close($ch);

            if ($httpCode !== 200) {
                error_log("Whisper API error: HTTP $httpCode - $response");
                $this->logApiUsage(auth()['id'] ?? null, 'whisper', null, 0, 0, $responseTime, false, "HTTP $httpCode");
                return null;
            }

            $result = json_decode($response, true);

            // Log usage (Whisper costa per minuto audio)
            $audioSize = filesize($audioPath);
            $estimatedMinutes = ceil($audioSize / 1024 / 1024); // rough estimate
            $cost = $estimatedMinutes * 0.006; // $0.006 per minute
            $this->logApiUsage(auth()['id'] ?? null, 'whisper', 'whisper-1', 0, $cost, $responseTime, true);

            return $result['text'] ?? null;
        } catch (\Exception $e) {
            error_log('Whisper API exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calcola costo Claude
     */
    private function calculateClaudeCost(int $tokens, string $model): float {
        // Claude 3.5 Sonnet pricing
        $inputCostPer1M = 3.00;
        $outputCostPer1M = 15.00;

        // Stima 50/50 input/output per semplicità
        $avgCostPer1M = ($inputCostPer1M + $outputCostPer1M) / 2;

        return ($tokens / 1000000) * $avgCostPer1M;
    }

    /**
     * Calcola costo OpenAI
     */
    private function calculateOpenAICost(int $tokens, string $model): float {
        // GPT-4 Turbo pricing
        $inputCostPer1M = 10.00;
        $outputCostPer1M = 30.00;

        // Stima 50/50 input/output per semplicità
        $avgCostPer1M = ($inputCostPer1M + $outputCostPer1M) / 2;

        return ($tokens / 1000000) * $avgCostPer1M;
    }

    /**
     * Log API usage per tracking costi
     */
    protected function logApiUsage(?int $userId, string $provider, ?string $endpoint, int $tokens, float $cost, float $responseTime, bool $success, ?string $error = null): void {
        if (!$userId) return;

        try {
            $stmt = $this->db->prepare('
                INSERT INTO ai_api_usage
                (user_id, api_provider, endpoint, tokens_used, cost_usd, response_time_ms, success, error_message)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $userId,
                $provider,
                $endpoint,
                $tokens,
                $cost,
                round($responseTime),
                $success ? 1 : 0,
                $error
            ]);
        } catch (\Exception $e) {
            error_log('Failed to log API usage: ' . $e->getMessage());
        }
    }

    /**
     * Estrai testo da risposta AI
     */
    protected function extractText($response): ?string {
        if (!$response) return null;

        // Claude format
        if (isset($response['content'][0]['text'])) {
            return $response['content'][0]['text'];
        }

        // OpenAI format
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }

        return null;
    }

    /**
     * Chiama AI (usa provider configurato)
     */
    protected function callAI(string $prompt, array $options = []): ?string {
        $response = $this->provider === 'claude'
            ? $this->callClaude($prompt, $options)
            : $this->callOpenAI($prompt, $options);

        return $this->extractText($response);
    }
}
