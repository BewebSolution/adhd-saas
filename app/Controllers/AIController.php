<?php

namespace App\Controllers;

use App\Services\SmartFocusService;
use App\Services\ADHDFocusService;
use App\Services\EnhancedADHDFocusService;
use App\Services\SimplifiedADHDFocusService;
use App\Services\ADHDSmartFocusService;
use App\Services\AISmartFocusService;

class AIController {

    public function __construct() {
        require_auth();
    }

    /**
     * Smart Focus - "Cosa fare ora?"
     */
    public function smartFocus(): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        try {
            // Raccogli input utente (se forniti)
            $userInput = [];

            // Energia (dal frontend o stimata)
            if (isset($_POST['energy'])) {
                $userInput['energy'] = $_POST['energy']; // 'high', 'medium', 'low'
            }

            // Tempo di focus disponibile
            if (isset($_POST['focus_time'])) {
                $userInput['focus_time'] = (int)$_POST['focus_time']; // minuti
            }

            // Mood/umore
            if (isset($_POST['mood'])) {
                $userInput['mood'] = $_POST['mood']; // 'great', 'good', 'neutral', 'tired', 'stressed'
            }

            // Livello distrazioni
            if (isset($_POST['distractions'])) {
                $userInput['distractions'] = $_POST['distractions']; // 'none', 'low', 'normal', 'high'
            }

            // Strategia richiesta (quick_win, deep_work, etc.)
            if (isset($_POST['strategy'])) {
                $userInput['preferred_strategy'] = $_POST['strategy'];
            }

            // USA IL SERVIZIO AI (con fallback a locale)
            $service = new AISmartFocusService();
            $result = $service->getSmartSuggestion(auth()['id'], $userInput);

            if (!$result) {
                // Fallback a servizio locale se AI fallisce
                $fallbackService = new ADHDSmartFocusService();
                $result = $fallbackService->getSmartSuggestion(auth()['id'], $userInput);

                if (!$result) {
                    json_response(['error' => 'Servizio temporaneamente non disponibile'], 503);
                    return;
                }
            }

            json_response([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            error_log('Enhanced ADHD Focus error: ' . $e->getMessage());

            // Ultimo fallback
            try {
                $fallbackService = new SmartFocusService();
                $result = $fallbackService->getSmartFocus(auth()['id']);

                if ($result) {
                    json_response([
                        'success' => true,
                        'data' => $result
                    ]);
                    return;
                }
            } catch (\Exception $fallbackError) {
                error_log('All services failed: ' . $fallbackError->getMessage());
            }

            json_response(['error' => 'Errore durante l\'analisi AI'], 500);
        }
    }

    /**
     * Feedback su suggestion
     */
    public function suggestionFeedback(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        $accepted = isset($_POST['accepted']) && $_POST['accepted'] === 'true';
        $feedback = $_POST['feedback'] ?? null;

        try {
            $service = new SmartFocusService();
            $service->recordFeedback($id, $accepted, $feedback);

            json_response([
                'success' => true,
                'message' => 'Feedback registrato'
            ]);
        } catch (\Exception $e) {
            json_response(['error' => 'Errore salvataggio feedback'], 500);
        }
    }

    /**
     * Voice to Task - Upload audio e parse
     */
    public function voiceToTask(): void {
        // TODO: Implementare dopo SmartFocus
        json_response(['error' => 'Feature in sviluppo'], 501);
    }

    /**
     * Task Breakdown - AI spezza task
     */
    public function taskBreakdown(int $taskId): void {
        // TODO: Implementare dopo Voice
        json_response(['error' => 'Feature in sviluppo'], 501);
    }

    /**
     * Pattern Insights - Dashboard analytics
     */
    public function patternInsights(): void {
        // TODO: Implementare dopo Breakdown
        json_response(['error' => 'Feature in sviluppo'], 501);
    }

    /**
     * AI Settings - User preferences (ADMIN ONLY)
     */
    public function settings(): void {
        // Check admin
        if (auth()['role'] !== 'admin') {
            flash('error', 'Accesso negato. Solo amministratori.');
            redirect('/');
            return;
        }

        $userId = auth()['id'];
        $db = get_db();

        try {
            // Get current settings
            $stmt = $db->prepare('SELECT * FROM ai_settings WHERE user_id = ?');
            $stmt->execute([$userId]);
            $settings = $stmt->fetch();

            if (!$settings) {
                // Create default settings
                $stmt = $db->prepare('
                    INSERT INTO ai_settings (user_id, recap_email)
                    VALUES (?, ?)
                ');
                $stmt->execute([$userId, auth()['email']]);

                $settings = [
                    'user_id' => $userId,
                    'smart_focus_enabled' => true,
                    'voice_enabled' => true,
                    'daily_recap_enabled' => true,
                    'recap_time' => '18:00:00',
                    'recap_email' => auth()['email'],
                    'pattern_insights_enabled' => true,
                    'auto_breakdown_enabled' => true,
                    'ai_provider' => 'openai',
                    'openai_api_key' => env('OPENAI_API_KEY', ''),
                    'claude_api_key' => env('CLAUDE_API_KEY', ''),
                    'google_client_id' => env('GOOGLE_CLIENT_ID', ''),
                    'google_client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
                    'monthly_budget' => null
                ];
            } else {
                // Merge with env fallback
                $settings['openai_api_key'] = $settings['openai_api_key'] ?: env('OPENAI_API_KEY', '');
                $settings['claude_api_key'] = $settings['claude_api_key'] ?: env('CLAUDE_API_KEY', '');
                $settings['ai_provider'] = $settings['ai_provider'] ?: env('AI_PROVIDER', 'openai');
                $settings['google_client_id'] = $settings['google_client_id'] ?: env('GOOGLE_CLIENT_ID', '');
                $settings['google_client_secret'] = $settings['google_client_secret'] ?: env('GOOGLE_CLIENT_SECRET', '');
            }

            // Get usage stats
            $stats = $this->getUsageStats($userId, $db);

            view('ai.settings', compact('settings', 'stats'));
        } catch (\Exception $e) {
            error_log('AI Settings error: ' . $e->getMessage());
            flash('error', 'Errore caricamento impostazioni AI');
            redirect('/');
        }
    }

    /**
     * Get AI usage statistics
     */
    private function getUsageStats(int $userId, \PDO $db): array {
        try {
            $firstDayOfMonth = date('Y-m-01 00:00:00');

            // Calls this month
            $stmt = $db->prepare('
                SELECT COUNT(*) as calls, SUM(tokens_used) as tokens, SUM(cost_usd) as cost
                FROM ai_api_usage
                WHERE user_id = ? AND request_time >= ?
            ');
            $stmt->execute([$userId, $firstDayOfMonth]);
            $monthStats = $stmt->fetch();

            // Last usage
            $stmt = $db->prepare('
                SELECT request_time FROM ai_api_usage
                WHERE user_id = ? ORDER BY request_time DESC LIMIT 1
            ');
            $stmt->execute([$userId]);
            $lastUsage = $stmt->fetch();

            return [
                'calls_month' => $monthStats['calls'] ?? 0,
                'tokens_month' => $monthStats['tokens'] ?? 0,
                'cost_month' => $monthStats['cost'] ?? 0,
                'last_used' => $lastUsage ? date('d/m H:i', strtotime($lastUsage['request_time'])) : 'Mai'
            ];
        } catch (\Exception $e) {
            error_log('Stats error: ' . $e->getMessage());
            return [
                'calls_month' => 0,
                'tokens_month' => 0,
                'cost_month' => 0,
                'last_used' => 'N/A'
            ];
        }
    }

    /**
     * Update AI Settings
     */
    public function updateSettings(): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/ai/settings');
            return;
        }

        // Check admin
        if (auth()['role'] !== 'admin') {
            flash('error', 'Accesso negato. Solo amministratori.');
            redirect('/');
            return;
        }

        $userId = auth()['id'];
        $db = get_db();

        try {
            // Handle API keys (only update if changed)
            $openaiKey = $_POST['openai_api_key'] ?? '';
            $claudeKey = $_POST['claude_api_key'] ?? '';
            $googleClientId = $_POST['google_client_id'] ?? '';
            $googleClientSecret = $_POST['google_client_secret'] ?? '';

            // Se inizia con •, significa che non è stata modificata
            if (strpos($openaiKey, '•') === 0) {
                // Mantieni quella esistente
                $stmt = $db->prepare('SELECT openai_api_key FROM ai_settings WHERE user_id = ?');
                $stmt->execute([$userId]);
                $current = $stmt->fetch();
                $openaiKey = $current['openai_api_key'] ?? '';
            }

            if (strpos($claudeKey, '•') === 0) {
                $stmt = $db->prepare('SELECT claude_api_key FROM ai_settings WHERE user_id = ?');
                $stmt->execute([$userId]);
                $current = $stmt->fetch();
                $claudeKey = $current['claude_api_key'] ?? '';
            }

            if (strpos($googleClientId, '•') === 0) {
                $stmt = $db->prepare('SELECT google_client_id FROM ai_settings WHERE user_id = ?');
                $stmt->execute([$userId]);
                $current = $stmt->fetch();
                $googleClientId = $current['google_client_id'] ?? '';
            }

            if (strpos($googleClientSecret, '•') === 0) {
                $stmt = $db->prepare('SELECT google_client_secret FROM ai_settings WHERE user_id = ?');
                $stmt->execute([$userId]);
                $current = $stmt->fetch();
                $googleClientSecret = $current['google_client_secret'] ?? '';
            }

            // Check if settings exist
            $stmt = $db->prepare('SELECT id FROM ai_settings WHERE user_id = ?');
            $stmt->execute([$userId]);
            $exists = $stmt->fetch();

            if ($exists) {
                // Update
                $stmt = $db->prepare('
                    UPDATE ai_settings SET
                        ai_provider = ?,
                        openai_api_key = ?,
                        claude_api_key = ?,
                        google_client_id = ?,
                        google_client_secret = ?,
                        smart_focus_enabled = ?,
                        voice_enabled = ?,
                        daily_recap_enabled = ?,
                        recap_time = ?,
                        recap_email = ?,
                        pattern_insights_enabled = ?,
                        auto_breakdown_enabled = ?,
                        monthly_budget = ?
                    WHERE user_id = ?
                ');

                $stmt->execute([
                    $_POST['ai_provider'] ?? 'openai',
                    $openaiKey,
                    $claudeKey,
                    $googleClientId,
                    $googleClientSecret,
                    isset($_POST['smart_focus_enabled']) ? 1 : 0,
                    isset($_POST['voice_enabled']) ? 1 : 0,
                    isset($_POST['daily_recap_enabled']) ? 1 : 0,
                    $_POST['recap_time'] ?? '18:00:00',
                    $_POST['recap_email'] ?? auth()['email'],
                    isset($_POST['pattern_insights_enabled']) ? 1 : 0,
                    isset($_POST['auto_breakdown_enabled']) ? 1 : 0,
                    !empty($_POST['monthly_budget']) ? (float)$_POST['monthly_budget'] : null,
                    $userId
                ]);
            } else {
                // Insert
                $stmt = $db->prepare('
                    INSERT INTO ai_settings
                    (user_id, ai_provider, openai_api_key, claude_api_key, google_client_id, google_client_secret,
                     smart_focus_enabled, voice_enabled, daily_recap_enabled, recap_time, recap_email,
                     pattern_insights_enabled, auto_breakdown_enabled, monthly_budget)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');

                $stmt->execute([
                    $userId,
                    $_POST['ai_provider'] ?? 'openai',
                    $openaiKey,
                    $claudeKey,
                    $googleClientId,
                    $googleClientSecret,
                    isset($_POST['smart_focus_enabled']) ? 1 : 0,
                    isset($_POST['voice_enabled']) ? 1 : 0,
                    isset($_POST['daily_recap_enabled']) ? 1 : 0,
                    $_POST['recap_time'] ?? '18:00:00',
                    $_POST['recap_email'] ?? auth()['email'],
                    isset($_POST['pattern_insights_enabled']) ? 1 : 0,
                    isset($_POST['auto_breakdown_enabled']) ? 1 : 0,
                    !empty($_POST['monthly_budget']) ? (float)$_POST['monthly_budget'] : null
                ]);
            }

            flash('success', '✅ Impostazioni AI aggiornate con successo');
            redirect('/ai/settings');
        } catch (\Exception $e) {
            error_log('Update AI Settings error: ' . $e->getMessage());
            flash('error', 'Errore aggiornamento impostazioni: ' . $e->getMessage());
            redirect('/ai/settings');
        }
    }
}
