<?php

namespace App\Controllers;

use App\Services\GoogleTasksService;
use App\Services\TaskCleanerAIService;
use App\Models\Task;
use App\Models\Project;

/**
 * AI Import Controller
 * Handles Google Tasks and Gmail import with AI processing
 */
class AIImportController {

    private GoogleTasksService $googleTasks;
    private TaskCleanerAIService $aiCleaner;
    private \PDO $db;

    public function __construct() {
        require_auth();

        // Check admin
        if (auth()['role'] !== 'admin') {
            flash('error', 'Accesso negato. Solo amministratori.');
            redirect('/');
            exit;
        }

        $this->googleTasks = new GoogleTasksService();
        $this->aiCleaner = new TaskCleanerAIService();
        $this->db = get_db();
    }

    /**
     * Show AI Import Center main page
     */
    public function index(): void {
        $isConnected = $this->googleTasks->isConnected();
        $authUrl = !$isConnected ? $this->googleTasks->getAuthUrl() : null;

        // Get sync stats
        $stats = $this->getSyncStats();

        // Get list mappings
        $mappings = $this->getListMappings();

        // Get import settings
        $settings = $this->getImportSettings();

        // Check for saved synced tasks (persist until next sync)
        $savedTasks = null;
        if (isset($_SESSION['google_tasks_raw'])) {
            $savedTasks = $_SESSION['google_tasks_raw'];
        }

        // Check for saved AI processed tasks (persist until next sync)
        $savedAITasks = null;
        if (isset($_SESSION['google_tasks_ai_processed'])) {
            $savedAITasks = $_SESSION['google_tasks_ai_processed'];
        }

        view('ai-import.index', compact('isConnected', 'authUrl', 'stats', 'mappings', 'settings', 'savedTasks', 'savedAITasks'));
    }

    /**
     * Handle OAuth callback from Google
     */
    public function oauthCallback(): void {
        $code = $_GET['code'] ?? null;

        if (!$code) {
            flash('error', 'Autorizzazione Google negata');
            redirect('/ai/import');
            return;
        }

        if ($this->googleTasks->handleCallback($code)) {
            flash('success', '✅ Google Tasks connesso con successo!');
        } else {
            flash('error', 'Errore durante la connessione a Google Tasks');
        }

        redirect('/ai/import');
    }

    /**
     * Disconnect Google Tasks
     */
    public function disconnect(): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
            return;
        }

        if ($this->googleTasks->disconnect()) {
            json_response(['success' => true, 'message' => 'Google Tasks disconnesso']);
        } else {
            json_response(['error' => 'Errore durante la disconnessione'], 500);
        }
    }

    /**
     * Sync Google Tasks (Phase 1 - RAW data only, no AI processing)
     * Fast sync that only downloads tasks from Google
     */
    public function sync(): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
            return;
        }

        if (!$this->googleTasks->isConnected()) {
            json_response(['error' => 'Non connesso a Google Tasks'], 401);
            return;
        }

        try {
            // Get all lists
            $lists = $this->googleTasks->fetchAllLists();
            $results = [
                'lists' => [],
                'total_tasks' => 0,
                'total_lists' => count($lists),
                'errors' => []
            ];

            foreach ($lists as $list) {
                $listResult = [
                    'id' => $list['id'],
                    'name' => $list['name'],
                    'tasks' => [],
                    'task_count' => 0
                ];

                // Check mapping for this list
                $mapping = $this->getListMappingInfo($list['id']);

                // Skip if list is set to ignore
                if ($mapping && $mapping['action'] === 'ignore') {
                    $listResult['status'] = 'ignored';
                    $results['lists'][] = $listResult;
                    continue;
                }

                // Fetch tasks from this list (RAW data)
                $tasks = $this->googleTasks->fetchTasksFromList($list['id']);
                $listResult['task_count'] = count($tasks);
                $results['total_tasks'] += count($tasks);

                // Store RAW tasks (no AI processing)
                foreach ($tasks as $task) {
                    // Add list info to task
                    $task['list_id'] = $list['id'];
                    $task['list_name'] = $list['name'];
                    $listResult['tasks'][] = $task;
                }

                $results['lists'][] = $listResult;
            }

            // Store RAW data in session (persist until next sync)
            $_SESSION['google_tasks_raw'] = $results;
            $_SESSION['google_tasks_sync_time'] = time();

            // NOTE: Do NOT clear previous processed data - keep it available

            json_response([
                'success' => true,
                'data' => $results,
                'message' => "Sincronizzati {$results['total_tasks']} task da {$results['total_lists']} liste",
                'ai_processing_required' => true  // Signal frontend that AI processing is needed
            ]);

        } catch (\Exception $e) {
            error_log('Sync error: ' . $e->getMessage());
            json_response(['error' => 'Errore durante la sincronizzazione: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process SELECTED tasks with AI
     * Called by "Processa con AI" button with selected tasks
     */
    public function processSelectedWithAI(): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
            return;
        }

        $selectedTasks = json_decode($_POST['tasks'] ?? '[]', true);

        if (empty($selectedTasks)) {
            json_response(['error' => 'Nessun task selezionato'], 400);
            return;
        }

        try {
            $results = [
                'tasks' => [],
                'total_tasks' => count($selectedTasks),
                'processed' => 0,
                'duplicates' => 0,
                'errors' => []
            ];

            // Process only selected tasks with AI
            $processedTasks = $this->aiCleaner->processBatch($selectedTasks);

            foreach ($processedTasks as $processed) {
                // Auto-assign project based on list name
                $projectId = null;
                $listName = $processed['original']['list_name'] ?? '';

                if ($listName) {
                    $projectModel = new Project();
                    $project = $projectModel->findByName($listName);

                    if ($project) {
                        $projectId = $project['id'];
                    } else {
                        // Create new project
                        $projectId = $projectModel->create([
                            'name' => $listName,
                            'description' => "Progetto creato automaticamente da Google Tasks",
                            'status' => 'active'
                        ]);
                    }
                }

                // Check for duplicates
                $duplicate = null;
                if ($projectId) {
                    $duplicate = $this->aiCleaner->checkDuplicate($processed['clean_title'], $projectId);
                    if ($duplicate) {
                        $results['duplicates']++;
                    }
                }

                // Add to results
                $results['tasks'][] = [
                    'original' => $processed['original'],
                    'processed' => $processed,
                    'project_id' => $projectId,
                    'status' => $duplicate ? 'duplicate' : 'ready',
                    'duplicate_id' => $duplicate ? $duplicate['id'] : null
                ];

                if (!$duplicate) {
                    $results['processed']++;
                }
            }

            // Store for import phase
            $_SESSION['google_tasks_selected_ai'] = $results;

            json_response([
                'success' => true,
                'data' => $results,
                'message' => "Processati {$results['processed']} task selezionati con AI"
            ]);

        } catch (\Exception $e) {
            error_log('Selected AI Processing error: ' . $e->getMessage());
            json_response(['error' => 'Errore durante il processamento AI: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process ALL raw tasks with AI (Phase 2 - separated from sync)
     * Called by "Importa con AI" button after sync
     */
    public function processWithAI(): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
            return;
        }

        // Get raw tasks from session
        $rawData = $_SESSION['google_tasks_raw'] ?? null;

        if (!$rawData || empty($rawData['lists'])) {
            json_response(['error' => 'Nessun task da processare. Esegui prima la sincronizzazione.'], 400);
            return;
        }

        try {
            $results = [
                'lists' => [],
                'total_tasks' => $rawData['total_tasks'] ?? 0,
                'processed' => 0,
                'duplicates' => 0,
                'personal' => 0,
                'errors' => []
            ];

            foreach ($rawData['lists'] as $list) {
                $listResult = [
                    'id' => $list['id'],
                    'name' => $list['name'],
                    'tasks' => []
                ];

                // Skip ignored lists
                if (isset($list['status']) && $list['status'] === 'ignored') {
                    $listResult['status'] = 'ignored';
                    $results['lists'][] = $listResult;
                    continue;
                }

                // Auto-map list to project by name
                $projectModel = new Project();
                $project = $projectModel->findByName($list['name']);

                if ($project) {
                    // Project exists, use it
                    $projectId = $project['id'];
                    error_log("List '{$list['name']}' auto-mapped to existing project ID: {$projectId}");
                } else {
                    // Create new project with same name as list
                    $projectId = $projectModel->create([
                        'name' => $list['name']
                    ]);
                    error_log("Created new project '{$list['name']}' with ID: {$projectId}");
                }

                // Process tasks with AI
                if (!empty($list['tasks'])) {
                    $processedTasks = $this->aiCleaner->processBatch($list['tasks'], $list['name']);

                    foreach ($processedTasks as $processed) {
                        // Skip personal tasks if configured
                        if (!$processed['is_work_task'] && $this->shouldIgnorePersonal()) {
                            $results['personal']++;
                            continue;
                        }

                        // Check for duplicates
                        $duplicate = null;
                        if ($projectId) {
                            $duplicate = $this->aiCleaner->checkDuplicate($processed['clean_title'], $projectId);
                            if ($duplicate) {
                                $results['duplicates']++;
                            }
                        }

                        // Store task with processed data INCLUDING project_id for import
                        $listResult['tasks'][] = [
                            'original' => $processed['original'],
                            'processed' => $processed,
                            'project_id' => $projectId,  // CRITICAL: This is needed for import!
                            'status' => $duplicate ? 'duplicate' : 'ready',
                            'duplicate_id' => $duplicate ? $duplicate['id'] : null
                        ];

                        if (!$duplicate) {
                            $results['processed']++;
                        }
                    }
                }

                $results['lists'][] = $listResult;
            }

            // Store processed results for import AND persistence
            $_SESSION['google_tasks_preview'] = $results;
            $_SESSION['google_tasks_ai_processed'] = $results;
            $_SESSION['google_tasks_ai_timestamp'] = time();

            json_response([
                'success' => true,
                'data' => $results,
                'message' => "Processati {$results['processed']} task con AI. Trovati {$results['duplicates']} duplicati."
            ]);

        } catch (\Exception $e) {
            error_log('AI Processing error: ' . $e->getMessage());
            json_response(['error' => 'Errore durante il processamento AI: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import selected tasks
     */
    public function import(): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
            return;
        }

        $tasksToImport = json_decode($_POST['tasks'] ?? '[]', true);
        $deleteAfterImport = $_POST['delete_after_import'] ?? false;

        if (empty($tasksToImport)) {
            json_response(['error' => 'Nessun task selezionato'], 400);
            return;
        }

        $imported = 0;
        $errors = [];

        foreach ($tasksToImport as $taskData) {
            try {
                error_log('Import AI - Processing task: ' . json_encode($taskData));

                // Check if project_id is set
                if (empty($taskData['project_id'])) {
                    $errors[] = "Task '{$taskData['processed']['clean_title']}' non ha un progetto associato";
                    continue;
                }

                // Generate unique code for task
                $taskCode = 'AI' . date('ymd') . strtoupper(substr(uniqid(), -4));

                // Process date if present
                $dueDate = null;
                if (!empty($taskData['processed']['suggested_deadline'])) {
                    $deadline = $taskData['processed']['suggested_deadline'];
                    // Handle various date formats
                    if (strpos($deadline, 'T') !== false) {
                        // Google format: 2025-11-11T00:00:00.000Z
                        $dueDate = str_replace('T', ' ', $deadline);
                        $dueDate = str_replace('.000Z', '', $dueDate);
                    } else {
                        // Already in MySQL format or other format
                        $dueDate = $deadline;
                    }
                }

                // Prepare task data with required fields
                $createData = [
                    'project_id' => $taskData['project_id'],
                    'code' => $taskCode,
                    'title' => $taskData['processed']['clean_title'],
                    'status' => 'Da fare',
                    'assignee' => auth()['name'],
                    'date' => date('Y-m-d'),
                    'notes' => "Importato da Google Tasks con AI: " . $taskData['original']['list_name']
                ];

                // Add optional fields only if they have values
                if (!empty($taskData['processed']['description'])) {
                    $createData['description'] = $taskData['processed']['description'];
                }
                if (!empty($taskData['processed']['priority'])) {
                    $createData['priority'] = $taskData['processed']['priority'];
                }
                if ($dueDate) {
                    $createData['due_at'] = $dueDate;
                }
                if (!empty($taskData['processed']['estimated_hours'])) {
                    $createData['hours_estimated'] = $taskData['processed']['estimated_hours'];
                }

                error_log('Import AI - Creating task with data: ' . json_encode($createData));

                // Create task in database
                $taskModel = new Task();
                $taskId = $taskModel->create($createData);

                if ($taskId) {
                    // Record sync
                    $this->recordTaskSync(
                        $taskData['original']['id'],
                        $taskData['original']['list_id'],
                        $taskData['original']['list_name'],
                        $taskData['project_id'],
                        $taskId,
                        $taskData['original']['title'],
                        $taskData['processed']['clean_title'],
                        $taskData['processed']['description']
                    );

                    // Delete from Google if requested
                    if ($deleteAfterImport) {
                        $this->googleTasks->deleteTask(
                            $taskData['original']['list_id'],
                            $taskData['original']['id']
                        );
                    }

                    $imported++;
                }
            } catch (\Exception $e) {
                $errors[] = "Errore importando '{$taskData['processed']['clean_title']}': " . $e->getMessage();
                error_log('Import error: ' . $e->getMessage());
            }
        }

        // Update last sync time
        $this->updateLastSync();

        json_response([
            'success' => true,
            'imported' => $imported,
            'errors' => $errors,
            'message' => "$imported task importati con successo"
        ]);
    }

    /**
     * Import selected tasks directly without AI processing
     */
    public function importDirect(): void {
        error_log('ImportDirect - Method called');
        error_log('ImportDirect - POST data: ' . json_encode($_POST));

        if (!verify_csrf()) {
            error_log('ImportDirect - CSRF verification failed');
            json_response(['error' => 'Token CSRF non valido'], 403);
            return;
        }

        $selectedTasks = json_decode($_POST['tasks'] ?? '[]', true);
        $deleteAfterImport = filter_var($_POST['delete_after_import'] ?? false, FILTER_VALIDATE_BOOLEAN);

        error_log('ImportDirect - Decoded tasks: ' . json_encode($selectedTasks));

        if (empty($selectedTasks)) {
            error_log('ImportDirect - No tasks received');
            json_response(['error' => 'Nessun task selezionato'], 400);
            return;
        }

        $imported = 0;
        $errors = [];
        $taskModel = new Task();

        // Debug: log received tasks
        error_log('ImportDirect - Processing ' . count($selectedTasks) . ' tasks');
        error_log('ImportDirect - First task sample: ' . json_encode($selectedTasks[0] ?? 'none'));

        foreach ($selectedTasks as $index => $taskData) {
            error_log("ImportDirect - Starting processing task $index");
            error_log("ImportDirect - Task data: " . json_encode($taskData));

            try {
                // Extract basic task information
                $title = $taskData['title'] ?? 'Task senza titolo';
                $description = $taskData['notes'] ?? '';
                $listId = $taskData['list_id'] ?? '';
                $listName = $taskData['list_name'] ?? '';
                $taskId = $taskData['id'] ?? '';

                error_log("ImportDirect - Extracted: title='$title', listName='$listName', taskId='$taskId'");

                // Get or create project based on list name
                $projectId = null;

                if ($listName) {
                    error_log("ImportDirect - Looking for project with name: '$listName'");
                    // First, check if a project with the same name exists
                    $stmt = $this->db->prepare("SELECT id FROM projects WHERE LOWER(name) = LOWER(?)");
                    $stmt->execute([$listName]);
                    $existingProject = $stmt->fetch(\PDO::FETCH_ASSOC);
                    error_log("ImportDirect - Query result: " . json_encode($existingProject));

                    if ($existingProject) {
                        $projectId = $existingProject['id'];
                        error_log("ImportDirect - Found existing project '$listName' with ID: $projectId");
                    } else {
                        // Create new project with the list name
                        try {
                            $stmt = $this->db->prepare("INSERT INTO projects (name, description, status) VALUES (?, ?, 'active')");
                            $stmt->execute([$listName, "Progetto creato automaticamente da Google Tasks"]);
                            $projectId = $this->db->lastInsertId();
                            error_log("ImportDirect - Created new project '$listName' with ID: $projectId");
                        } catch (\Exception $e) {
                            error_log("ImportDirect - Failed to create project: " . $e->getMessage());
                        }
                    }
                }

                // Fallback to default project if needed
                if (!$projectId) {
                    $stmt = $this->db->query("SELECT id FROM projects ORDER BY id LIMIT 1");
                    $defaultProject = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $projectId = $defaultProject ? $defaultProject['id'] : null;

                    if (!$projectId) {
                        // Create a default project if none exists
                        $stmt = $this->db->prepare("INSERT INTO projects (name, description, status) VALUES ('Progetto Default', 'Progetto di default', 'active')");
                        $stmt->execute();
                        $projectId = $this->db->lastInsertId();
                        error_log("ImportDirect - Created default project with ID: $projectId");
                    }
                }

                // Generate unique code for task
                $taskCode = 'GT' . date('ymd') . strtoupper(substr(uniqid(), -4));

                // Prepare task data (only essential fields)
                $taskCreateData = [
                    'code' => $taskCode,
                    'project_id' => $projectId,
                    'title' => $title,
                    'status' => 'Da fare',
                    'assignee' => auth()['name']
                ];

                // Add optional fields if available
                if (!empty($description)) {
                    $taskCreateData['description'] = $description;
                }

                if (!empty($taskData['due'])) {
                    // Convert Google date format to MySQL format
                    // From: 2025-11-11T00:00:00.000Z
                    // To: 2025-11-11 00:00:00
                    $dueDate = str_replace('T', ' ', $taskData['due']);
                    $dueDate = str_replace('.000Z', '', $dueDate);
                    $dueDate = substr($dueDate, 0, 19); // Get only YYYY-MM-DD HH:MM:SS
                    $taskCreateData['due_at'] = $dueDate;
                    error_log("ImportDirect - Converted due date from '{$taskData['due']}' to '$dueDate'");
                }

                // Add import note
                $taskCreateData['notes'] = "Importato da Google Tasks" . ($listName ? ": $listName" : "");

                error_log('ImportDirect - Creating task with data: ' . json_encode($taskCreateData));

                // Create task in database
                $newTaskId = $taskModel->create($taskCreateData);

                error_log('ImportDirect - Task creation result: ' . ($newTaskId ? "ID $newTaskId" : 'FAILED'));

                if ($newTaskId) {
                    // Record sync
                    if ($taskId && $listId) {
                        $this->recordTaskSync(
                            $taskId,
                            $listId,
                            $listName,
                            $projectId,
                            $newTaskId,
                            $title,
                            $title,
                            $description
                        );
                    }

                    // Delete from Google if requested
                    if ($deleteAfterImport && $taskId && $listId) {
                        try {
                            $this->googleTasks->deleteTask($listId, $taskId);
                        } catch (\Exception $e) {
                            error_log("Failed to delete Google task: " . $e->getMessage());
                        }
                    }

                    $imported++;
                    error_log("ImportDirect - Task imported successfully, ID: $newTaskId");
                } else {
                    error_log("ImportDirect - Task creation failed, returned: " . var_export($newTaskId, true));
                    $errors[] = "Creazione fallita per: $title";
                }
            } catch (\PDOException $e) {
                $errorMsg = "Database error for task '$title': " . $e->getMessage();
                $errors[] = $errorMsg;
                error_log("ImportDirect - PDO Exception: " . $e->getMessage());
                error_log("ImportDirect - PDO Trace: " . $e->getTraceAsString());
            } catch (\Exception $e) {
                $errorMsg = "Error importing task '$title': " . $e->getMessage();
                $errors[] = $errorMsg;
                error_log("ImportDirect - General Exception: " . $e->getMessage());
                error_log("ImportDirect - Exception Trace: " . $e->getTraceAsString());
            }
        }

        // Update last sync time
        $this->updateLastSync();

        error_log("ImportDirect - Completed. Imported: $imported, Errors: " . count($errors));
        if (!empty($errors)) {
            error_log("ImportDirect - Errors detail: " . json_encode($errors));
        }

        json_response([
            'success' => true,
            'imported' => $imported,
            'errors' => $errors,
            'message' => "$imported task importati con successo (senza AI)"
        ]);
    }

    /**
     * Save list mapping
     */
    public function saveMapping(): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
            return;
        }

        $listId = $_POST['list_id'] ?? '';
        $listName = $_POST['list_name'] ?? '';
        $projectId = $_POST['project_id'] ?? null;
        $action = $_POST['action'] ?? 'import';

        if (empty($listId)) {
            json_response(['error' => 'ID lista mancante'], 400);
            return;
        }

        if ($this->googleTasks->saveListMapping($listId, $listName, $projectId, $action)) {
            json_response(['success' => true, 'message' => 'Mappatura salvata']);
        } else {
            json_response(['error' => 'Errore nel salvataggio'], 500);
        }
    }

    /**
     * Update import settings
     */
    public function updateSettings(): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/ai/import');
            return;
        }

        $userId = auth()['id'];

        try {
            $stmt = $this->db->prepare('
                INSERT INTO import_settings (user_id, service, auto_sync, sync_time, delete_after_import, ignore_personal)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    auto_sync = VALUES(auto_sync),
                    sync_time = VALUES(sync_time),
                    delete_after_import = VALUES(delete_after_import),
                    ignore_personal = VALUES(ignore_personal),
                    updated_at = NOW()
            ');

            $stmt->execute([
                $userId,
                'google_tasks',
                isset($_POST['auto_sync']) ? 1 : 0,
                $_POST['sync_time'] ?? '08:00:00',
                isset($_POST['delete_after_import']) ? 1 : 0,
                isset($_POST['ignore_personal']) ? 1 : 0
            ]);

            flash('success', '✅ Impostazioni aggiornate');
        } catch (\Exception $e) {
            error_log('Settings update error: ' . $e->getMessage());
            flash('error', 'Errore aggiornamento impostazioni');
        }

        redirect('/ai/import');
    }

    /**
     * Get sync statistics
     */
    private function getSyncStats(): array {
        try {
            $userId = auth()['id'];

            // Total imported
            $stmt = $this->db->prepare('
                SELECT COUNT(*) as total
                FROM google_tasks_sync
                WHERE user_id = ? AND status = "imported"
            ');
            $stmt->execute([$userId]);
            $totalImported = $stmt->fetchColumn();

            // Today imported
            $stmt = $this->db->prepare('
                SELECT COUNT(*) as today
                FROM google_tasks_sync
                WHERE user_id = ? AND status = "imported" AND DATE(imported_at) = CURDATE()
            ');
            $stmt->execute([$userId]);
            $todayImported = $stmt->fetchColumn();

            // Last sync
            $stmt = $this->db->prepare('
                SELECT last_sync_at
                FROM import_settings
                WHERE user_id = ? AND service = "google_tasks"
            ');
            $stmt->execute([$userId]);
            $lastSync = $stmt->fetchColumn();

            return [
                'total_imported' => $totalImported,
                'today_imported' => $todayImported,
                'last_sync' => $lastSync
            ];
        } catch (\Exception $e) {
            error_log('Stats error: ' . $e->getMessage());
            return [
                'total_imported' => 0,
                'today_imported' => 0,
                'last_sync' => null
            ];
        }
    }

    /**
     * Get list mappings
     */
    private function getListMappings(): array {
        try {
            $userId = auth()['id'];
            $stmt = $this->db->prepare('
                SELECT glm.*, p.name as project_name
                FROM google_lists_mapping glm
                LEFT JOIN projects p ON glm.project_id = p.id
                WHERE glm.user_id = ?
                ORDER BY glm.google_list_name
            ');
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log('Mappings error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get import settings
     */
    private function getImportSettings(): array {
        try {
            $userId = auth()['id'];
            $stmt = $this->db->prepare('
                SELECT *
                FROM import_settings
                WHERE user_id = ? AND service = "google_tasks"
            ');
            $stmt->execute([$userId]);
            $settings = $stmt->fetch();

            return $settings ?: [
                'auto_sync' => false,
                'sync_time' => '08:00:00',
                'delete_after_import' => false,
                'ignore_personal' => true
            ];
        } catch (\Exception $e) {
            error_log('Settings error: ' . $e->getMessage());
            return [
                'auto_sync' => false,
                'sync_time' => '08:00:00',
                'delete_after_import' => false,
                'ignore_personal' => true
            ];
        }
    }

    /**
     * Check if should ignore personal tasks
     */
    private function shouldIgnorePersonal(): bool {
        $settings = $this->getImportSettings();
        return $settings['ignore_personal'] ?? true;
    }

    /**
     * Record task sync in database
     */
    private function recordTaskSync($googleTaskId, $googleListId, $googleListName, $projectId, $taskId, $originalTitle, $processedTitle, $processedDescription): void {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO google_tasks_sync
                (user_id, google_task_id, google_list_id, google_list_name, project_id, task_id,
                 original_title, processed_title, processed_description, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "imported")
            ');
            $stmt->execute([
                auth()['id'],
                $googleTaskId,
                $googleListId,
                $googleListName,
                $projectId,
                $taskId,
                $originalTitle,
                $processedTitle,
                $processedDescription
            ]);
        } catch (\Exception $e) {
            error_log('Record sync error: ' . $e->getMessage());
        }
    }

    /**
     * Get list mapping info
     */
    private function getListMappingInfo(string $listId): ?array {
        try {
            $stmt = $this->db->prepare('
                SELECT *
                FROM google_lists_mapping
                WHERE user_id = ? AND google_list_id = ?
            ');
            $stmt->execute([auth()['id'], $listId]);
            return $stmt->fetch() ?: null;
        } catch (\Exception $e) {
            error_log('Mapping info error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update last sync time
     */
    private function updateLastSync(): void {
        try {
            $stmt = $this->db->prepare('
                UPDATE import_settings
                SET last_sync_at = NOW()
                WHERE user_id = ? AND service = "google_tasks"
            ');
            $stmt->execute([auth()['id']]);
        } catch (\Exception $e) {
            error_log('Update last sync error: ' . $e->getMessage());
        }
    }
}