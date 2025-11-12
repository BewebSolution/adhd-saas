<?php

namespace App\Controllers;

use App\Models\Task;
use App\Models\TimeLog;
use App\Models\Deliverable;
use App\Models\Note;
use App\Models\Project;

class ImportController {
    private Task $taskModel;
    private TimeLog $timeLogModel;
    private Deliverable $deliverableModel;
    private Note $noteModel;
    private Project $projectModel;

    public function __construct() {
        require_auth();

        if (!is_admin()) {
            flash('error', 'Solo gli admin possono importare dati');
            redirect('/');
        }

        $this->taskModel = new Task();
        $this->timeLogModel = new TimeLog();
        $this->deliverableModel = new Deliverable();
        $this->noteModel = new Note();
        $this->projectModel = new Project();
    }

    /**
     * Show import page
     */
    public function index(): void {
        view('import.index');
    }

    /**
     * Handle CSV import
     */
    public function import(): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/import');
        }

        $entity = $_POST['entity'] ?? '';

        if (!in_array($entity, ['tasks', 'timelogs', 'deliverables', 'notes'])) {
            flash('error', 'Tipo di import non valido');
            redirect('/import');
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Errore nel caricamento del file CSV');
            redirect('/import');
        }

        $csvPath = $_FILES['csv_file']['tmp_name'];
        $data = $this->parseCSV($csvPath);

        if (empty($data)) {
            flash('error', 'File CSV vuoto o non valido');
            redirect('/import');
        }

        // Import based on entity type
        $result = match($entity) {
            'tasks' => $this->importTasks($data),
            'timelogs' => $this->importTimeLogs($data),
            'deliverables' => $this->importDeliverables($data),
            'notes' => $this->importNotes($data),
            default => ['success' => 0, 'errors' => 0, 'skipped' => 0]
        };

        flash('success', sprintf(
            'Import completato: %d inseriti, %d errori, %d saltati',
            $result['success'],
            $result['errors'],
            $result['skipped']
        ));

        redirect('/import');
    }

    /**
     * Parse CSV file
     */
    private function parseCSV(string $path): array {
        $rows = [];
        $handle = fopen($path, 'r');

        if (!$handle) {
            return [];
        }

        // Get headers from first row
        $headers = fgetcsv($handle, 0, ',');

        if (!$headers) {
            fclose($handle);
            return [];
        }

        // Normalize headers (trim, lowercase)
        $headers = array_map(fn($h) => trim(strtolower($h)), $headers);

        // Parse data rows
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) !== count($headers)) {
                continue; // Skip malformed rows
            }

            $rowData = [];
            foreach ($headers as $i => $header) {
                $rowData[$header] = trim($row[$i]);
            }

            $rows[] = $rowData;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Import tasks
     */
    private function importTasks(array $rows): array {
        $success = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            try {
                // Required fields
                if (empty($row['progetto']) || empty($row['titolo'])) {
                    $errors++;
                    continue;
                }

                // Get or create project
                $projectId = $this->projectModel->getOrCreate($row['progetto']);

                // Check if code exists
                $code = $row['id'] ?? $row['code'] ?? '';
                if (!empty($code)) {
                    $existing = $this->taskModel->findByCode($code);
                    if ($existing) {
                        $skipped++;
                        continue; // Skip duplicates
                    }
                }

                // Parse due date/time
                $dueAt = null;
                if (!empty($row['scadenza'])) {
                    $dueAt = date('Y-m-d H:i:s', strtotime($row['scadenza']));
                }

                // Create task
                $this->taskModel->create([
                    'code' => $code ?: $this->taskModel->generateNextCode(),
                    'date' => !empty($row['data']) ? date('Y-m-d', strtotime($row['data'])) : date('Y-m-d'),
                    'project_id' => $projectId,
                    'title' => $row['titolo'],
                    'description' => $row['descrizione'] ?? '',
                    'priority' => $row['priorita'] ?? $row['priorità'] ?? '',
                    'status' => $row['stato'] ?? 'Da fare',
                    'assignee' => $row['assegnatario'] ?? auth()['name'],
                    'due_at' => $dueAt,
                    'hours_estimated' => !empty($row['ore_stimate']) ? floatval($row['ore_stimate']) : null,
                    'hours_spent' => !empty($row['ore_svolte']) ? floatval($row['ore_svolte']) : 0,
                    'link' => $row['link'] ?? '',
                    'notes' => $row['note'] ?? '',
                ]);

                $success++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        return ['success' => $success, 'errors' => $errors, 'skipped' => $skipped];
    }

    /**
     * Import time logs
     */
    private function importTimeLogs(array $rows): array {
        $success = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            try {
                // Required fields
                if (empty($row['data']) || empty($row['ore'])) {
                    $errors++;
                    continue;
                }

                // Try to find task by code
                $taskId = null;
                if (!empty($row['attivita']) || !empty($row['code'])) {
                    $code = $row['attivita'] ?? $row['code'];
                    $task = $this->taskModel->findByCode($code);
                    if ($task) {
                        $taskId = $task['id'];
                    }
                }

                // Create time log
                $hours = floatval($row['ore']);

                $this->timeLogModel->create([
                    'date' => date('Y-m-d', strtotime($row['data'])),
                    'person' => $row['persona'] ?? auth()['name'],
                    'task_id' => $taskId,
                    'description' => $row['descrizione'] ?? '',
                    'hours' => $hours,
                    'output_link' => $row['link_output'] ?? $row['link'] ?? '',
                    'blocked' => ($row['blocco'] ?? '') === 'Sì' ? 'Sì' : 'No',
                    'notes' => $row['note'] ?? '',
                ]);

                // Update task hours
                if ($taskId) {
                    $this->taskModel->addHours($taskId, $hours);
                }

                $success++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        return ['success' => $success, 'errors' => $errors, 'skipped' => $skipped];
    }

    /**
     * Import deliverables
     */
    private function importDeliverables(array $rows): array {
        $success = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            try {
                // Required fields
                if (empty($row['progetto']) || empty($row['titolo'])) {
                    $errors++;
                    continue;
                }

                // Get or create project
                $projectId = $this->projectModel->getOrCreate($row['progetto']);

                // Create deliverable
                $this->deliverableModel->create([
                    'date' => !empty($row['data']) ? date('Y-m-d', strtotime($row['data'])) : date('Y-m-d'),
                    'project_id' => $projectId,
                    'type' => $row['tipo'] ?? '',
                    'title' => $row['titolo'],
                    'link' => $row['link'] ?? '',
                    'status' => $row['stato'] ?? 'In revisione',
                    'notes' => $row['note'] ?? '',
                ]);

                $success++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        return ['success' => $success, 'errors' => $errors, 'skipped' => $skipped];
    }

    /**
     * Import notes
     */
    private function importNotes(array $rows): array {
        $success = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            try {
                // Required fields
                if (empty($row['tema']) || empty($row['nota'])) {
                    $errors++;
                    continue;
                }

                // Create note
                $this->noteModel->create([
                    'date' => !empty($row['data']) ? date('Y-m-d', strtotime($row['data'])) : date('Y-m-d'),
                    'topic' => $row['tema'],
                    'body' => $row['nota'] ?? $row['body'] ?? '',
                    'next_action' => $row['azione_successiva'] ?? $row['next_action'] ?? '',
                    'owner' => $row['owner'] ?? $row['responsabile'] ?? auth()['name'],
                    'due_date' => !empty($row['scadenza']) ? date('Y-m-d', strtotime($row['scadenza'])) : null,
                    'link' => $row['link'] ?? '',
                ]);

                $success++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        return ['success' => $success, 'errors' => $errors, 'skipped' => $skipped];
    }
}
