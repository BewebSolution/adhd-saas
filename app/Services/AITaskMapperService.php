<?php

namespace App\Services;

/**
 * AI Task Mapper Service
 * Mappa intelligentemente i task di Google Tasks ai progetti locali
 * e arricchisce i dati dei task usando AI
 */
class AITaskMapperService extends BaseAIService {

    /**
     * Mappa automaticamente liste Google Tasks a progetti locali
     */
    public function mapListsToProjects(array $googleLists, array $localProjects): array {
        if (empty($googleLists) || empty($localProjects)) {
            return $this->basicMapping($googleLists, $localProjects);
        }

        // Prepara i dati per l'AI
        $listsNames = array_map(fn($l) => $l['name'], $googleLists);
        $projectsNames = array_map(fn($p) => $p['name'], $localProjects);

        $prompt = "Sei un assistente specializzato nel mapping di task list a progetti aziendali.

Devo associare queste liste di Google Tasks ai progetti corrispondenti nell'app:

LISTE GOOGLE TASKS:
" . implode("\n", array_map(fn($i, $n) => ($i+1) . ". " . $n, array_keys($listsNames), $listsNames)) . "

PROGETTI DISPONIBILI NELL'APP:
" . implode("\n", array_map(fn($i, $n) => ($i+1) . ". " . $n, array_keys($projectsNames), $projectsNames)) . "

Analizza i nomi e trova le corrispondenze più logiche. Considera:
- Similarità nei nomi (anche parziali)
- Acronimi o abbreviazioni
- Contesto aziendale (es. 'URGENT' potrebbe non essere un progetto)
- Liste generiche come 'My Tasks', 'DA FARE', etc. potrebbero non corrispondere a progetti specifici

Rispondi SOLO in formato JSON con questa struttura:
{
    \"mappings\": [
        {
            \"google_list\": \"nome esatto della lista Google\",
            \"project\": \"nome esatto del progetto app o null se non mappabile\",
            \"confidence\": 0.0-1.0,
            \"reason\": \"breve spiegazione del matching\"
        }
    ]
}

IMPORTANTE:
- Usa null per project se non c'è una corrispondenza chiara
- confidence: 1.0 = match perfetto, 0.8+ = molto probabile, 0.5-0.7 = possibile, <0.5 = incerto";

        try {
            $response = $this->callAI($prompt, ['temperature' => 0.3]);

            if ($response && isset($response['success']) && $response['success']) {
                $aiResponse = json_decode($response['data']['text'], true);

                if ($aiResponse && isset($aiResponse['mappings'])) {
                    return $this->processAIMappings($aiResponse['mappings'], $googleLists, $localProjects);
                }
            }
        } catch (\Exception $e) {
            error_log('AI mapping error: ' . $e->getMessage());
        }

        // Fallback a mapping basico
        return $this->basicMapping($googleLists, $localProjects);
    }

    /**
     * Arricchisce i dati di un task usando AI
     */
    public function enrichTaskData(array $task, string $listName, ?string $projectName): array {
        $prompt = "Analizza questo task da Google Tasks e arricchisci i dati per l'inserimento nel sistema di project management.

TASK ORIGINALE:
Titolo: {$task['title']}
Note: " . ($task['notes'] ?? 'Nessuna') . "
Scadenza: " . ($task['due'] ?? 'Non definita') . "
Lista: $listName
" . ($projectName ? "Progetto assegnato: $projectName" : "Progetto: Da determinare") . "

ANALIZZA E FORNISCI:
1. Un titolo pulito e professionale (max 200 caratteri)
2. Una descrizione dettagliata basata su titolo e note
3. Priorità (Alta/Media/Bassa) basata su keywords e scadenza
4. Stato suggerito (Da fare/In corso/In revisione)
5. Ore stimate per completamento (numero decimale)
6. Tag/keywords rilevanti (array)
7. Tipo di attività (development/design/meeting/review/research/admin/other)
8. Assegnatario suggerito (Clemente per task tecnici/strategici, Caterina per task operativi/creativi)

Considera il contesto ADHD-friendly:
- Titoli chiari e actionable
- Descrizioni che includono il \"perché\" e il \"come\"
- Stime realistiche delle ore
- Priorità basata su urgenza E importanza

Rispondi SOLO in JSON:
{
    \"title\": \"titolo pulito\",
    \"description\": \"descrizione dettagliata\",
    \"priority\": \"Alta|Media|Bassa\",
    \"status\": \"Da fare|In corso|In revisione\",
    \"hours_estimated\": 0.0,
    \"tags\": [\"tag1\", \"tag2\"],
    \"task_type\": \"development|design|meeting|review|research|admin|other\",
    \"assignee\": \"Clemente|Caterina\",
    \"ai_confidence\": 0.0-1.0,
    \"ai_notes\": \"note aggiuntive dell'AI\"
}";

        try {
            $response = $this->callAI($prompt, ['temperature' => 0.4]);

            if ($response && isset($response['success']) && $response['success']) {
                $enriched = json_decode($response['data']['text'], true);

                if ($enriched) {
                    // Merge con dati originali
                    return array_merge($task, [
                        'clean_title' => $enriched['title'] ?? $task['title'],
                        'description' => $enriched['description'] ?? $task['notes'] ?? '',
                        'priority' => $enriched['priority'] ?? 'Media',
                        'status' => $enriched['status'] ?? 'Da fare',
                        'hours_estimated' => $enriched['hours_estimated'] ?? null,
                        'tags' => $enriched['tags'] ?? [],
                        'task_type' => $enriched['task_type'] ?? 'other',
                        'assignee' => $enriched['assignee'] ?? 'Caterina',
                        'ai_confidence' => $enriched['ai_confidence'] ?? 0.5,
                        'ai_notes' => $enriched['ai_notes'] ?? '',
                        'ai_enriched' => true
                    ]);
                }
            }
        } catch (\Exception $e) {
            error_log('Task enrichment error: ' . $e->getMessage());
        }

        // Fallback: ritorna task con pulizia base
        return array_merge($task, [
            'clean_title' => $this->cleanTitle($task['title']),
            'description' => $task['notes'] ?? '',
            'priority' => $this->guessPriority($task['title']),
            'status' => 'Da fare',
            'assignee' => 'Caterina',
            'ai_enriched' => false
        ]);
    }

    /**
     * Processa i mapping suggeriti dall'AI
     */
    private function processAIMappings(array $aiMappings, array $googleLists, array $localProjects): array {
        $result = [];

        // Crea lookup per progetti
        $projectLookup = [];
        foreach ($localProjects as $project) {
            $projectLookup[strtolower($project['name'])] = $project;
        }

        foreach ($googleLists as $list) {
            $mapped = false;

            // Cerca il mapping dell'AI per questa lista
            foreach ($aiMappings as $mapping) {
                if (strtolower($mapping['google_list']) === strtolower($list['name'])) {
                    $projectId = null;
                    $projectName = null;

                    if ($mapping['project'] && $mapping['project'] !== 'null') {
                        // Trova il progetto corrispondente
                        $projectKey = strtolower($mapping['project']);
                        if (isset($projectLookup[$projectKey])) {
                            $projectId = $projectLookup[$projectKey]['id'];
                            $projectName = $projectLookup[$projectKey]['name'];
                        }
                    }

                    $result[] = [
                        'google_list_id' => $list['id'],
                        'google_list_name' => $list['name'],
                        'project_id' => $projectId,
                        'project_name' => $projectName,
                        'confidence' => $mapping['confidence'] ?? 0.5,
                        'reason' => $mapping['reason'] ?? '',
                        'ai_suggested' => true
                    ];

                    $mapped = true;
                    break;
                }
            }

            // Se non mappato dall'AI, aggiungi senza progetto
            if (!$mapped) {
                $result[] = [
                    'google_list_id' => $list['id'],
                    'google_list_name' => $list['name'],
                    'project_id' => null,
                    'project_name' => null,
                    'confidence' => 0,
                    'reason' => 'Nessuna corrispondenza trovata',
                    'ai_suggested' => false
                ];
            }
        }

        return $result;
    }

    /**
     * Mapping basico senza AI (fallback)
     */
    private function basicMapping(array $googleLists, array $localProjects): array {
        $result = [];

        foreach ($googleLists as $list) {
            $bestMatch = null;
            $bestScore = 0;

            // Cerca corrispondenze esatte o parziali
            foreach ($localProjects as $project) {
                $similarity = 0;

                // Match esatto
                if (strtolower($list['name']) === strtolower($project['name'])) {
                    $similarity = 1.0;
                }
                // Match parziale
                elseif (stripos($list['name'], $project['name']) !== false ||
                        stripos($project['name'], $list['name']) !== false) {
                    $similarity = 0.7;
                }
                // Similarità stringhe
                else {
                    similar_text(strtolower($list['name']), strtolower($project['name']), $percent);
                    $similarity = $percent / 100;
                }

                if ($similarity > $bestScore) {
                    $bestScore = $similarity;
                    $bestMatch = $project;
                }
            }

            $result[] = [
                'google_list_id' => $list['id'],
                'google_list_name' => $list['name'],
                'project_id' => $bestScore > 0.5 ? $bestMatch['id'] : null,
                'project_name' => $bestScore > 0.5 ? $bestMatch['name'] : null,
                'confidence' => $bestScore,
                'reason' => $bestScore > 0.5 ? 'Match automatico' : 'Nessuna corrispondenza',
                'ai_suggested' => false
            ];
        }

        return $result;
    }

    /**
     * Pulisce il titolo del task
     */
    private function cleanTitle(string $title): string {
        // Rimuovi emoji
        $title = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $title);
        $title = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $title);
        $title = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $title);

        // Normalizza spazi e punteggiatura
        $title = preg_replace('/[!]{2,}/', '!', $title);
        $title = preg_replace('/[?]{2,}/', '?', $title);
        $title = preg_replace('/\s+/', ' ', $title);

        return trim($title);
    }

    /**
     * Indovina la priorità dal titolo
     */
    private function guessPriority(string $title): string {
        $title_lower = strtolower($title);

        $high_keywords = ['urgent', 'urgente', 'asap', 'importante', 'critico', '!!!'];
        $low_keywords = ['quando puoi', 'bassa priorità', 'nice to have'];

        foreach ($high_keywords as $keyword) {
            if (stripos($title_lower, $keyword) !== false) {
                return 'Alta';
            }
        }

        foreach ($low_keywords as $keyword) {
            if (stripos($title_lower, $keyword) !== false) {
                return 'Bassa';
            }
        }

        return 'Media';
    }
}