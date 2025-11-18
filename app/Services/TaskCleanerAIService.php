<?php

namespace App\Services;

/**
 * Task Cleaner AI Service
 * Uses AI to clean, enhance and process raw tasks from Google Tasks
 */
class TaskCleanerAIService extends BaseAIService {

    /**
     * Clean and enhance a raw task from Google Tasks (with Italian support)
     */
    public function cleanAndEnhance(array $rawTask): array {
        // First try to extract priority from text
        $fullText = $rawTask['title'] . ' ' . ($rawTask['notes'] ?? '');
        $detectedPriority = $this->detectPriority($fullText);

        // Try to parse Italian dates from text
        $parsedDate = $this->parseItalianDate($fullText);

        // If no date parsed but Google has a due date, use it
        if (!$parsedDate && !empty($rawTask['due'])) {
            $parsedDate = date('Y-m-d H:i:s', strtotime($rawTask['due']));
        }

        $prompt = "
Sei un assistente specializzato nell'organizzazione di task per un'agenzia creativa italiana.
Ricevi un task grezzo da Google Tasks e devi:
1. Pulire il titolo (correggere typo, rimuovere emoji e punteggiatura eccessiva)
2. Estrarre informazioni rilevanti
3. Suggerire priorità basata su parole chiave (italiano e inglese)
4. Identificare eventuali scadenze nel testo (incluso italiano: domani, lunedì, prossima settimana, etc.)
5. Generare una descrizione chiara se mancante
6. Riconoscere riferimenti a progetti o clienti

Task originale:
Titolo: {$rawTask['title']}
Note: {$rawTask['notes']}
Scadenza Google: {$rawTask['due']}
Priorità rilevata: {$detectedPriority}
Data rilevata: {$parsedDate}

Rispondi SOLO in formato JSON con questa struttura:
{
    \"clean_title\": \"Titolo pulito e professionale\",
    \"description\": \"Descrizione dettagliata o note aggiuntive\",
    \"priority\": \"Alta|Media|Bassa\",
    \"suggested_deadline\": \"YYYY-MM-DD HH:mm:ss o null\",
    \"is_work_task\": true/false,
    \"task_type\": \"development|design|meeting|review|research|admin|client|other\",
    \"estimated_hours\": numero decimale o null,
    \"keywords\": [\"keyword1\", \"keyword2\"],
    \"suggested_project\": \"nome progetto se identificabile o null\"
}

IMPORTANTE:
- Se il task sembra personale (spesa, famiglia, casa, dottore), metti is_work_task = false
- Usa la priorità già rilevata come base: {$detectedPriority}
- Usa la data già parsata se disponibile: {$parsedDate}
- Riconosci date italiane: oggi, domani, lunedì, martedì, mercoledì, giovedì, venerdì, entro il 15, etc.
- Il titolo pulito deve essere professionale e chiaro, in italiano
- Se menzioni clienti o progetti specifici, includi in keywords
";

        $response = $this->callAI($prompt, ['temperature' => 0.3]); // Low temperature for consistency

        if (!$response || !isset($response['success']) || !$response['success']) {
            // Enhanced fallback if AI fails - use our local parsing
            return [
                'clean_title' => $this->basicCleanTitle($rawTask['title']),
                'description' => $rawTask['notes'] ?? '',
                'priority' => $detectedPriority,
                'suggested_deadline' => $parsedDate,
                'is_work_task' => $this->isWorkTask($fullText),
                'task_type' => $this->detectTaskType($fullText),
                'estimated_hours' => null,
                'keywords' => $this->extractKeywords($fullText),
                'suggested_project' => null
            ];
        }

        $cleanedData = json_decode($response['data']['text'], true);

        // Override with our detected values if AI missed them
        if ($parsedDate && empty($cleanedData['suggested_deadline'])) {
            $cleanedData['suggested_deadline'] = $parsedDate;
        }

        if ($detectedPriority !== 'Media' && $cleanedData['priority'] === 'Media') {
            $cleanedData['priority'] = $detectedPriority;
        }

        // Validate and sanitize the response
        return $this->validateCleanedData($cleanedData, $rawTask);
    }

    /**
     * Detect if task is work-related
     */
    private function isWorkTask(string $text): bool {
        $text = strtolower($text);

        $personalKeywords = [
            'spesa', 'supermercato', 'dottore', 'medico', 'dentista',
            'famiglia', 'moglie', 'marito', 'figli', 'genitori',
            'casa', 'appartamento', 'bollette', 'palestra', 'sport',
            'compleanno', 'regalo', 'vacanza', 'ferie', 'personale'
        ];

        foreach ($personalKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Detect task type from text
     */
    private function detectTaskType(string $text): string {
        $text = strtolower($text);

        $types = [
            'development' => ['sviluppo', 'codice', 'bug', 'fix', 'develop', 'programming', 'api', 'database'],
            'design' => ['design', 'grafica', 'mockup', 'ui', 'ux', 'logo', 'layout'],
            'meeting' => ['meeting', 'riunione', 'call', 'incontro', 'appuntamento', 'videochiamata'],
            'review' => ['review', 'revisione', 'controllo', 'verifica', 'correzione', 'feedback'],
            'research' => ['ricerca', 'research', 'analisi', 'studio', 'documentazione'],
            'admin' => ['admin', 'amministrazione', 'fattura', 'contratto', 'documenti'],
            'client' => ['cliente', 'client', 'presentazione', 'proposta', 'offerta']
        ];

        foreach ($types as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $type;
                }
            }
        }

        return 'other';
    }

    /**
     * Extract keywords from text
     */
    private function extractKeywords(string $text): array {
        $keywords = [];
        $text = strtolower($text);

        // Common project/client indicators
        $patterns = [
            '/\b[A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*\b/', // Proper nouns (client names)
            '/\b(?:progetto|project)\s+(\w+)\b/i',
            '/\b(?:cliente|client)\s+(\w+)\b/i',
            '/\b(?:sito|website|app)\s+(\w+)\b/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] ?? $matches[0] as $match) {
                    if (strlen($match) > 2) {
                        $keywords[] = trim($match);
                    }
                }
            }
        }

        return array_unique(array_slice($keywords, 0, 5));
    }

    /**
     * Suggest project mapping based on task content and list name (Enhanced)
     */
    public function suggestProject(string $taskText, string $listName): ?array {
        // Get existing projects
        $db = get_db();
        $stmt = $db->prepare('SELECT id, name, description FROM projects ORDER BY name');
        $stmt->execute();
        $projects = $stmt->fetchAll();

        if (empty($projects)) {
            return null;
        }

        // First try exact or similar name matching
        $listNameLower = strtolower($listName);
        $taskTextLower = strtolower($taskText);

        foreach ($projects as $project) {
            $projectNameLower = strtolower($project['name']);

            // Exact match with list name
            if ($listNameLower === $projectNameLower) {
                return [
                    'project_id' => $project['id'],
                    'confidence' => 95,
                    'reason' => 'Nome lista corrisponde esattamente al progetto'
                ];
            }

            // List name contains project name or vice versa
            if (strpos($listNameLower, $projectNameLower) !== false ||
                strpos($projectNameLower, $listNameLower) !== false) {
                return [
                    'project_id' => $project['id'],
                    'confidence' => 85,
                    'reason' => 'Nome lista molto simile al progetto'
                ];
            }

            // Task text mentions project name
            if (strpos($taskTextLower, $projectNameLower) !== false) {
                return [
                    'project_id' => $project['id'],
                    'confidence' => 80,
                    'reason' => 'Task menziona il nome del progetto'
                ];
            }
        }

        // If no direct match, use AI for smarter mapping
        $projectsList = array_map(fn($p) => "{$p['id']}: {$p['name']} - {$p['description']}", $projects);
        $projectsText = implode("\n", $projectsList);

        $prompt = "
Sei un assistente che deve mappare task a progetti esistenti per un'agenzia creativa italiana.

Task: {$taskText}
Lista Google Tasks: {$listName}

Progetti esistenti:
{$projectsText}

Analizza il task e la lista di origine e suggerisci il progetto più appropriato.
Considera:
- Corrispondenze nel nome della lista o del task
- Parole chiave nel task (cliente, tecnologia, tipo di lavoro)
- Contesto aziendale (sviluppo web, design, marketing)
- Se la lista si chiama 'My Tasks' o 'Tasks', cerca indizi nel testo del task

Rispondi SOLO in formato JSON:
{
    \"suggested_project_id\": numero_id_progetto o null,
    \"confidence\": percentuale 0-100,
    \"reason\": \"Breve spiegazione in italiano\"
}

IMPORTANTE: Se non c'è una corrispondenza chiara (confidence < 50), ritorna null come project_id.
";

        $response = $this->callAI($prompt, ['temperature' => 0.2]);

        if (!$response || !isset($response['success']) || !$response['success']) {
            // Fallback: try to match by common keywords
            return $this->fallbackProjectMatching($taskText, $listName, $projects);
        }

        $suggestion = json_decode($response['data']['text'], true);

        // Validate confidence threshold
        if (($suggestion['confidence'] ?? 0) < 50) {
            $suggestion['suggested_project_id'] = null;
            $suggestion['reason'] = 'Nessuna corrispondenza affidabile trovata';
        }

        return [
            'project_id' => $suggestion['suggested_project_id'] ?? null,
            'confidence' => $suggestion['confidence'] ?? 0,
            'reason' => $suggestion['reason'] ?? 'Mappatura automatica non disponibile'
        ];
    }

    /**
     * Fallback project matching using keywords
     */
    private function fallbackProjectMatching(string $taskText, string $listName, array $projects): ?array {
        $bestMatch = null;
        $bestScore = 0;

        $taskWords = array_merge(
            explode(' ', strtolower($taskText)),
            explode(' ', strtolower($listName))
        );

        foreach ($projects as $project) {
            $projectWords = explode(' ', strtolower($project['name'] . ' ' . $project['description']));
            $commonWords = array_intersect($taskWords, $projectWords);
            $score = count($commonWords);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $project;
            }
        }

        if ($bestMatch && $bestScore > 2) {
            return [
                'project_id' => $bestMatch['id'],
                'confidence' => min(70, $bestScore * 15),
                'reason' => 'Corrispondenza basata su parole chiave comuni'
            ];
        }

        return [
            'project_id' => null,
            'confidence' => 0,
            'reason' => 'Nessuna corrispondenza trovata'
        ];
    }

    /**
     * Detect priority from task text (Enhanced for Italian)
     */
    public function detectPriority(string $taskText): string {
        $taskText = strtolower($taskText);

        // High priority keywords (Italian + English)
        $highKeywords = [
            'urgente', 'urgenza', 'urgent', 'asap', 'critico', 'importante',
            'priorità alta', 'prioritario', 'entro oggi', 'subito', 'immediatamente',
            '!!!', 'bloccante', 'deadline', 'scade oggi', 'entro domani',
            'al più presto', 'prima possibile', 'essenziale', 'fondamentale',
            'entro le', 'entro il', 'tassativo', 'improrogabile'
        ];

        foreach ($highKeywords as $keyword) {
            if (strpos($taskText, $keyword) !== false) {
                return 'Alta';
            }
        }

        // Low priority keywords (Italian + English)
        $lowKeywords = [
            'quando hai tempo', 'quando puoi', 'nice to have', 'opzionale',
            'eventualmente', 'se possibile', 'bassa priorità', 'non urgente',
            'può aspettare', 'quando capita', 'se avanza tempo', 'secondario',
            'in futuro', 'prossimamente', 'un giorno', 'prima o poi'
        ];

        foreach ($lowKeywords as $keyword) {
            if (strpos($taskText, $keyword) !== false) {
                return 'Bassa';
            }
        }

        return 'Media';
    }

    /**
     * Parse Italian date references from text
     */
    public function parseItalianDate(string $text): ?string {
        $text = strtolower($text);
        $now = new \DateTime();

        // Mapping giorni italiani
        $weekDays = [
            'lunedì' => 'monday', 'lunedi' => 'monday',
            'martedì' => 'tuesday', 'martedi' => 'tuesday',
            'mercoledì' => 'wednesday', 'mercoledi' => 'wednesday',
            'giovedì' => 'thursday', 'giovedi' => 'thursday',
            'venerdì' => 'friday', 'venerdi' => 'friday',
            'sabato' => 'saturday',
            'domenica' => 'sunday'
        ];

        // Mapping mesi italiani
        $months = [
            'gennaio' => '01', 'febbraio' => '02', 'marzo' => '03',
            'aprile' => '04', 'maggio' => '05', 'giugno' => '06',
            'luglio' => '07', 'agosto' => '08', 'settembre' => '09',
            'ottobre' => '10', 'novembre' => '11', 'dicembre' => '12'
        ];

        // Pattern comuni italiani
        $patterns = [
            // "oggi" / "oggi pomeriggio" / "stasera"
            '/\boggi\b/' => function() {
                return (new \DateTime())->format('Y-m-d 18:00:00');
            },
            '/\bstasera\b/' => function() {
                return (new \DateTime())->format('Y-m-d 21:00:00');
            },

            // "domani" / "domani mattina" / "domani pomeriggio"
            '/\bdomani\s*(mattina)?\b/' => function() {
                return (new \DateTime('+1 day'))->format('Y-m-d 09:00:00');
            },
            '/\bdomani\s*pomeriggio\b/' => function() {
                return (new \DateTime('+1 day'))->format('Y-m-d 15:00:00');
            },
            '/\bdomani\b/' => function() {
                return (new \DateTime('+1 day'))->format('Y-m-d 12:00:00');
            },

            // "dopodomani"
            '/\bdopodomani\b/' => function() {
                return (new \DateTime('+2 days'))->format('Y-m-d 12:00:00');
            },

            // "entro [numero] giorni"
            '/\bentro\s+(\d+)\s+giorn[io]?\b/' => function($matches) {
                $days = intval($matches[1]);
                return (new \DateTime("+{$days} days"))->format('Y-m-d 18:00:00');
            },

            // "prossima settimana" / "settimana prossima"
            '/\b(prossima\s+settimana|settimana\s+prossima)\b/' => function() {
                return (new \DateTime('next monday'))->format('Y-m-d 09:00:00');
            },

            // "fine settimana"
            '/\bfine\s+settimana\b/' => function() {
                return (new \DateTime('friday'))->format('Y-m-d 18:00:00');
            },

            // "entro il [giorno]" es: "entro il 15"
            '/\bentro\s+il\s+(\d{1,2})\b/' => function($matches) {
                $day = intval($matches[1]);
                $date = new \DateTime();
                $date->setDate($date->format('Y'), $date->format('m'), $day);
                if ($date < new \DateTime()) {
                    $date->modify('+1 month');
                }
                return $date->format('Y-m-d 18:00:00');
            },

            // "[giorno] [mese]" es: "15 marzo"
            '/\b(\d{1,2})\s+(gennaio|febbraio|marzo|aprile|maggio|giugno|luglio|agosto|settembre|ottobre|novembre|dicembre)\b/'
                => function($matches) use ($months) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = $months[$matches[2]];
                $year = date('Y');
                $date = new \DateTime("$year-$month-$day");
                if ($date < new \DateTime()) {
                    $date->modify('+1 year');
                }
                return $date->format('Y-m-d 12:00:00');
            }
        ];

        // Check giorno della settimana (es: "lunedì", "prossimo martedì")
        foreach ($weekDays as $italian => $english) {
            if (strpos($text, $italian) !== false) {
                $prefix = strpos($text, 'prossim') !== false ? 'next ' : '';
                $date = new \DateTime($prefix . $english);
                return $date->format('Y-m-d 12:00:00');
            }
        }

        // Apply patterns
        foreach ($patterns as $pattern => $callback) {
            if (preg_match($pattern, $text, $matches)) {
                return $callback($matches);
            }
        }

        // Check for time references (es: "alle 15", "alle 3 del pomeriggio")
        if (preg_match('/\balle?\s+(\d{1,2})(?::(\d{2}))?\b/', $text, $matches)) {
            $hour = intval($matches[1]);
            $minute = isset($matches[2]) ? intval($matches[2]) : 0;

            // Adjust for PM if mentioned
            if (strpos($text, 'pomeriggio') !== false && $hour < 12) {
                $hour += 12;
            }

            $date = new \DateTime();
            $date->setTime($hour, $minute);

            // If time has passed today, assume tomorrow
            if ($date < new \DateTime()) {
                $date->modify('+1 day');
            }

            return $date->format('Y-m-d H:i:00');
        }

        return null;
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