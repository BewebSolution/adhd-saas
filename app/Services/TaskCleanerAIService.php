<?php

namespace App\Services;

/**
 * Task Cleaner AI Service
 * Uses AI to clean, enhance and process raw tasks from Google Tasks
 */
class TaskCleanerAIService extends BaseAIService {

    /**
     * Clean and enhance a raw task from Google Tasks
     */
    public function cleanAndEnhance(array $rawTask): array {
        $prompt = "
Sei un assistente specializzato nell'organizzazione di task per un'agenzia creativa.
Ricevi un task grezzo da Google Tasks e devi:
1. Pulire il titolo (correggere typo, rimuovere emoji e punteggiatura eccessiva)
2. Estrarre informazioni rilevanti
3. Suggerire priorità basata su parole chiave
4. Identificare eventuali scadenze nel testo
5. Generare una descrizione chiara se mancante

Task originale:
Titolo: {$rawTask['title']}
Note: {$rawTask['notes']}
Scadenza Google: {$rawTask['due']}

Rispondi SOLO in formato JSON con questa struttura:
{
    \"clean_title\": \"Titolo pulito e professionale\",
    \"description\": \"Descrizione dettagliata o note aggiuntive\",
    \"priority\": \"Alta|Media|Bassa\",
    \"suggested_deadline\": \"YYYY-MM-DD HH:mm:ss o null\",
    \"is_work_task\": true/false,
    \"task_type\": \"development|design|meeting|review|research|other\",
    \"estimated_hours\": numero decimale o null,
    \"keywords\": [\"keyword1\", \"keyword2\"]
}

IMPORTANTE:
- Se il task sembra personale (spesa, famiglia, casa), metti is_work_task = false
- Estrai priorità da parole come: urgente, importante, asap, entro oggi, priorità
- Se ci sono riferimenti temporali (domani, lunedì, prossima settimana), calcola la scadenza
- Il titolo pulito deve essere professionale e chiaro
- Non aggiungere informazioni che non sono presenti nel task originale
";

        $response = $this->callAI($prompt, ['temperature' => 0.3]); // Low temperature for consistency

        if (!$response || !isset($response['success']) || !$response['success']) {
            // Fallback if AI fails
            return [
                'clean_title' => $this->basicCleanTitle($rawTask['title']),
                'description' => $rawTask['notes'] ?? '',
                'priority' => 'Media',
                'suggested_deadline' => $rawTask['due'] ? date('Y-m-d H:i:s', strtotime($rawTask['due'])) : null,
                'is_work_task' => true,
                'task_type' => 'other',
                'estimated_hours' => null,
                'keywords' => []
            ];
        }

        $cleanedData = json_decode($response['data']['text'], true);

        // Validate and sanitize the response
        return $this->validateCleanedData($cleanedData, $rawTask);
    }

    /**
     * Suggest project mapping based on task content and list name
     */
    public function suggestProject(string $taskText, string $listName): ?array {
        // Get existing projects
        $db = get_db();
        $stmt = $db->prepare('SELECT id, name FROM projects ORDER BY name');
        $stmt->execute();
        $projects = $stmt->fetchAll();

        if (empty($projects)) {
            return null;
        }

        $projectsList = array_map(fn($p) => "{$p['id']}: {$p['name']}", $projects);
        $projectsText = implode("\n", $projectsList);

        $prompt = "
Sei un assistente che deve mappare task a progetti esistenti.

Task: {$taskText}
Lista Google Tasks: {$listName}

Progetti esistenti:
{$projectsText}

Analizza il task e la lista di origine e suggerisci il progetto più appropriato.
Considera:
- Corrispondenze nel nome della lista
- Parole chiave nel task
- Contesto aziendale

Rispondi SOLO in formato JSON:
{
    \"suggested_project_id\": numero_id_progetto o null,
    \"confidence\": percentuale 0-100,
    \"reason\": \"Breve spiegazione della scelta\"
}
";

        $response = $this->callAI($prompt, ['temperature' => 0.2]);

        if (!$response || !isset($response['success']) || !$response['success']) {
            return null;
        }

        $suggestion = json_decode($response['data']['text'], true);

        return [
            'project_id' => $suggestion['suggested_project_id'] ?? null,
            'confidence' => $suggestion['confidence'] ?? 0,
            'reason' => $suggestion['reason'] ?? 'Mappatura automatica non disponibile'
        ];
    }

    /**
     * Detect priority from task text
     */
    public function detectPriority(string $taskText): string {
        $taskText = strtolower($taskText);

        // High priority keywords
        $highKeywords = ['urgente', 'urgent', 'asap', 'critico', 'importante', 'priorità alta',
                        'entro oggi', 'subito', 'immediatamente', '!!!', 'bloccante'];
        foreach ($highKeywords as $keyword) {
            if (strpos($taskText, $keyword) !== false) {
                return 'Alta';
            }
        }

        // Low priority keywords
        $lowKeywords = ['quando hai tempo', 'quando puoi', 'nice to have', 'opzionale',
                       'eventualmente', 'se possibile', 'bassa priorità'];
        foreach ($lowKeywords as $keyword) {
            if (strpos($taskText, $keyword) !== false) {
                return 'Bassa';
            }
        }

        return 'Media';
    }

    /**
     * Check for duplicates in existing tasks
     */
    public function checkDuplicate(string $title, int $projectId): ?array {
        $db = get_db();

        // Direct simple comparison (LEVENSHTEIN not available in standard MySQL)
        // Check for exact or similar titles in the same project
        $stmt = $db->prepare('
            SELECT id, title, status
            FROM tasks
            WHERE project_id = ?
            AND status != "Fatto"
            AND (
                title = ?
                OR LOWER(title) = LOWER(?)
                OR title LIKE ?
            )
            LIMIT 1
        ');

        // Search for exact match or partial match
        $searchPattern = '%' . substr($title, 0, min(strlen($title), 20)) . '%';
        $stmt->execute([$projectId, $title, $title, $searchPattern]);
        $duplicate = $stmt->fetch();

        return $duplicate ?: null;
    }

    /**
     * Basic title cleaning without AI
     */
    private function basicCleanTitle(string $title): string {
        // Remove excessive punctuation
        $title = preg_replace('/[!]{2,}/', '!', $title);
        $title = preg_replace('/[.]{2,}/', '.', $title);

        // Remove common emojis
        $title = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $title);
        $title = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $title);

        // Trim and capitalize first letter
        $title = trim($title);
        $title = ucfirst(strtolower($title));

        // Fix common typos (basic)
        $title = str_replace(['x ', 'cn ', 'cmq'], ['per ', 'con ', 'comunque'], $title);

        return $title;
    }

    /**
     * Validate and sanitize AI cleaned data
     */
    private function validateCleanedData(?array $data, array $rawTask): array {
        if (!$data) {
            return [
                'clean_title' => $this->basicCleanTitle($rawTask['title']),
                'description' => $rawTask['notes'] ?? '',
                'priority' => 'Media',
                'suggested_deadline' => $rawTask['due'] ? date('Y-m-d H:i:s', strtotime($rawTask['due'])) : null,
                'is_work_task' => true,
                'task_type' => 'other',
                'estimated_hours' => null,
                'keywords' => []
            ];
        }

        // Ensure all required fields exist
        return [
            'clean_title' => $data['clean_title'] ?? $this->basicCleanTitle($rawTask['title']),
            'description' => $data['description'] ?? ($rawTask['notes'] ?? ''),
            'priority' => in_array($data['priority'] ?? '', ['Alta', 'Media', 'Bassa'])
                          ? $data['priority'] : 'Media',
            'suggested_deadline' => $data['suggested_deadline'] ??
                                  ($rawTask['due'] ? date('Y-m-d H:i:s', strtotime($rawTask['due'])) : null),
            'is_work_task' => $data['is_work_task'] ?? true,
            'task_type' => $data['task_type'] ?? 'other',
            'estimated_hours' => is_numeric($data['estimated_hours'] ?? null)
                               ? $data['estimated_hours'] : null,
            'keywords' => is_array($data['keywords'] ?? null) ? $data['keywords'] : []
        ];
    }

    /**
     * Process batch of tasks
     */
    public function processBatch(array $tasks, string $listName): array {
        $results = [];

        foreach ($tasks as $task) {
            try {
                $cleaned = $this->cleanAndEnhance($task);
                $cleaned['original'] = $task;
                $cleaned['list_name'] = $listName;
                $results[] = $cleaned;
            } catch (\Exception $e) {
                error_log("Error processing task {$task['id']}: " . $e->getMessage());
                // Add with basic processing
                $results[] = [
                    'original' => $task,
                    'list_name' => $listName,
                    'clean_title' => $this->basicCleanTitle($task['title']),
                    'description' => $task['notes'] ?? '',
                    'priority' => 'Media',
                    'suggested_deadline' => null,
                    'is_work_task' => true,
                    'task_type' => 'other',
                    'estimated_hours' => null,
                    'keywords' => [],
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}