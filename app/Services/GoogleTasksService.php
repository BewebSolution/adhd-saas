<?php

namespace App\Services;

use Google\Client;
use Google\Service\Tasks;
use Exception;

/**
 * Google Tasks Service
 * Handles all interactions with Google Tasks API
 */
class GoogleTasksService {

    private \PDO $db;
    private ?Client $client = null;
    private ?Tasks $service = null;
    private int $userId;

    // Google OAuth configuration
    const SCOPES = [
        'https://www.googleapis.com/auth/tasks',
        'https://www.googleapis.com/auth/tasks.readonly'
    ];

    public function __construct() {
        $this->db = get_db();
        $this->userId = auth()['id'];
    }

    /**
     * Get dynamic redirect URI based on environment
     */
    private function getRedirectUri(): string {
        // Use AppConfig to get the correct URL
        $config = \App\Config\AppConfig::getInstance();
        return $config->url('ai/import/oauth-callback');
    }

    /**
     * Initialize Google Client
     */
    private function initClient(): void {
        if ($this->client) return;

        $this->client = new Client();
        $this->client->setApplicationName('Beweb Tirocinio');

        // Load Google OAuth credentials from database or env
        $googleClientId = '';
        $googleClientSecret = '';

        try {
            $stmt = $this->db->prepare('
                SELECT google_client_id, google_client_secret
                FROM ai_settings
                WHERE user_id = ?
            ');
            $stmt->execute([$this->userId]);
            $settings = $stmt->fetch();

            if ($settings) {
                $googleClientId = $settings['google_client_id'] ?: env('GOOGLE_CLIENT_ID', '');
                $googleClientSecret = $settings['google_client_secret'] ?: env('GOOGLE_CLIENT_SECRET', '');
            } else {
                $googleClientId = env('GOOGLE_CLIENT_ID', '');
                $googleClientSecret = env('GOOGLE_CLIENT_SECRET', '');
            }
        } catch (\Exception $e) {
            // Fallback to env if database fails
            $googleClientId = env('GOOGLE_CLIENT_ID', '');
            $googleClientSecret = env('GOOGLE_CLIENT_SECRET', '');
        }

        $this->client->setClientId($googleClientId);
        $this->client->setClientSecret($googleClientSecret);
        $this->client->setRedirectUri($this->getRedirectUri());
        $this->client->setScopes(self::SCOPES);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        // Check if we have saved tokens
        $tokens = $this->getSavedTokens();
        if ($tokens) {
            $this->client->setAccessToken($tokens);

            // Refresh token if expired
            if ($this->client->isAccessTokenExpired() && $this->client->getRefreshToken()) {
                try {
                    $newTokens = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());

                    // Verifica che il refresh abbia restituito un token valido
                    if (!isset($newTokens['access_token'])) {
                        error_log('Failed to refresh Google token: Invalid token format');
                        throw new Exception('Invalid token format');
                    }

                    if ($this->saveTokens($newTokens)) {
                        $this->client->setAccessToken($newTokens); // Importante: imposta i nuovi token nel client
                    } else {
                        throw new Exception('Failed to save refreshed tokens');
                    }
                } catch (\Exception $e) {
                    error_log('Failed to refresh Google token: ' . $e->getMessage());
                    throw new Exception('Failed to refresh Google token: Invalid token format');
                }
            }

            $this->service = new Tasks($this->client);
        }
    }

    /**
     * Get OAuth authorization URL
     */
    public function getAuthUrl(): string {
        $this->initClient();
        return $this->client->createAuthUrl();
    }

    /**
     * Handle OAuth callback
     */
    public function handleCallback(string $code): bool {
        try {
            $this->initClient();
            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new Exception('Error fetching access token: ' . $token['error_description']);
            }

            $this->saveTokens($token);
            return true;
        } catch (Exception $e) {
            error_log('OAuth callback error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is connected to Google Tasks
     */
    public function isConnected(): bool {
        $this->initClient();
        return $this->service !== null && !$this->client->isAccessTokenExpired();
    }

    /**
     * Disconnect Google Tasks
     */
    public function disconnect(): bool {
        try {
            $stmt = $this->db->prepare('DELETE FROM oauth_tokens WHERE user_id = ? AND service = ?');
            $stmt->execute([$this->userId, 'google_tasks']);
            return true;
        } catch (Exception $e) {
            error_log('Disconnect error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all task lists from Google Tasks
     */
    public function fetchAllLists(): array {
        if (!$this->isConnected()) {
            throw new Exception('Not connected to Google Tasks');
        }

        try {
            $lists = $this->service->tasklists->listTasklists();
            $result = [];

            foreach ($lists->getItems() as $list) {
                $result[] = [
                    'id' => $list->getId(),
                    'name' => $list->getTitle(),
                    'updated' => $list->getUpdated()
                ];
            }

            return $result;
        } catch (Exception $e) {
            error_log('Error fetching lists: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch all tasks from a specific list
     */
    public function fetchTasksFromList(string $listId): array {
        if (!$this->isConnected()) {
            throw new Exception('Not connected to Google Tasks');
        }

        try {
            $tasks = $this->service->tasks->listTasks($listId, [
                'showCompleted' => false, // Only get incomplete tasks
                'showHidden' => false,
                'maxResults' => 100
            ]);

            $result = [];
            foreach ($tasks->getItems() as $task) {
                $result[] = [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'notes' => $task->getNotes(),
                    'due' => $task->getDue(),
                    'status' => $task->getStatus(),
                    'updated' => $task->getUpdated(),
                    'position' => $task->getPosition()
                ];
            }

            return $result;
        } catch (Exception $e) {
            error_log('Error fetching tasks: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark a task as completed in Google Tasks
     */
    public function markTaskCompleted(string $listId, string $taskId): bool {
        if (!$this->isConnected()) {
            throw new Exception('Not connected to Google Tasks');
        }

        try {
            $task = $this->service->tasks->get($listId, $taskId);
            $task->setStatus('completed');
            $this->service->tasks->update($listId, $task->getId(), $task);
            return true;
        } catch (Exception $e) {
            error_log('Error marking task completed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a task from Google Tasks
     */
    public function deleteTask(string $listId, string $taskId): bool {
        if (!$this->isConnected()) {
            throw new Exception('Not connected to Google Tasks');
        }

        try {
            $this->service->tasks->delete($listId, $taskId);
            return true;
        } catch (Exception $e) {
            error_log('Error deleting task: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get saved OAuth tokens from database
     */
    private function getSavedTokens(): ?array {
        try {
            $stmt = $this->db->prepare('
                SELECT access_token, refresh_token, token_type, expires_at
                FROM oauth_tokens
                WHERE user_id = ? AND service = ?
            ');
            $stmt->execute([$this->userId, 'google_tasks']);
            $row = $stmt->fetch();

            if ($row) {
                return [
                    'access_token' => $row['access_token'],
                    'refresh_token' => $row['refresh_token'],
                    'token_type' => $row['token_type'],
                    'expires_in' => strtotime($row['expires_at']) - time()
                ];
            }

            return null;
        } catch (Exception $e) {
            error_log('Error fetching saved tokens: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Save OAuth tokens to database
     */
    private function saveTokens(array $tokens): bool {
        try {
            // Verifica che access_token esista
            if (!isset($tokens['access_token'])) {
                error_log('Invalid token format: missing access_token');
                return false;
            }

            $expiresAt = isset($tokens['expires_in'])
                ? date('Y-m-d H:i:s', time() + $tokens['expires_in'])
                : null;

            $stmt = $this->db->prepare('
                INSERT INTO oauth_tokens (user_id, service, access_token, refresh_token, token_type, expires_at)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    access_token = VALUES(access_token),
                    refresh_token = IF(VALUES(refresh_token) IS NOT NULL, VALUES(refresh_token), refresh_token),
                    token_type = VALUES(token_type),
                    expires_at = VALUES(expires_at),
                    updated_at = NOW()
            ');

            $stmt->execute([
                $this->userId,
                'google_tasks',
                $tokens['access_token'],
                $tokens['refresh_token'] ?? null,
                $tokens['token_type'] ?? 'Bearer',
                $expiresAt
            ]);

            return true;
        } catch (Exception $e) {
            error_log('Error saving tokens: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get list mapping for a specific Google list
     */
    public function getListMapping(string $listId): ?int {
        try {
            $stmt = $this->db->prepare('
                SELECT project_id
                FROM google_lists_mapping
                WHERE user_id = ? AND google_list_id = ? AND action = "import"
            ');
            $stmt->execute([$this->userId, $listId]);
            $row = $stmt->fetch();

            return $row ? $row['project_id'] : null;
        } catch (Exception $e) {
            error_log('Error fetching list mapping: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Save list mapping
     */
    public function saveListMapping(string $listId, string $listName, ?int $projectId, string $action = 'import'): bool {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO google_lists_mapping (user_id, google_list_id, google_list_name, project_id, action)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    google_list_name = VALUES(google_list_name),
                    project_id = VALUES(project_id),
                    action = VALUES(action),
                    updated_at = NOW()
            ');

            $stmt->execute([$this->userId, $listId, $listName, $projectId, $action]);
            return true;
        } catch (Exception $e) {
            error_log('Error saving list mapping: ' . $e->getMessage());
            return false;
        }
    }
}