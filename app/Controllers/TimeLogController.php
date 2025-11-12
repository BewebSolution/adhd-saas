<?php

namespace App\Controllers;

use App\Models\TimeLog;
use App\Models\Task;
use App\Models\ListItem;

class TimeLogController {
    private TimeLog $timeLogModel;
    private Task $taskModel;
    private ListItem $listModel;

    public function __construct() {
        require_auth();
        $this->timeLogModel = new TimeLog();
        $this->taskModel = new Task();
        $this->listModel = new ListItem();
    }

    /**
     * List all time logs with filters
     */
    public function index(): void {
        $filters = [
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'person' => $_GET['person'] ?? '',
            'task_id' => $_GET['task'] ?? '',
        ];

        $timeLogs = $this->timeLogModel->getAllWithTasks($filters);
        $tasks = $this->taskModel->getAllWithProjects();
        $persons = $this->listModel->getByListName('persona');

        view('timelogs.index', [
            'timeLogs' => $timeLogs,
            'tasks' => $tasks,
            'persons' => $persons,
            'filters' => $filters,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): void {
        $tasks = $this->taskModel->getAllWithProjects();
        $persons = $this->listModel->getByListName('persona');

        view('timelogs.form', [
            'timeLog' => null,
            'tasks' => $tasks,
            'persons' => $persons,
        ]);
    }

    /**
     * Store new time log
     */
    public function store(): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/timelogs/create');
        }

        $data = $this->validateTimeLogData($_POST);

        $this->timeLogModel->create($data);

        // Update task hours_spent if task is linked
        if ($data['task_id']) {
            $this->taskModel->addHours($data['task_id'], $data['hours']);
        }

        flash('success', 'Registro ore creato con successo ✅');
        redirect('/timelogs');
    }

    /**
     * Show edit form
     */
    public function edit(int $id): void {
        $timeLog = $this->timeLogModel->find($id);

        if (!$timeLog) {
            flash('error', 'Registro ore non trovato');
            redirect('/timelogs');
        }

        $tasks = $this->taskModel->getAllWithProjects();
        $persons = $this->listModel->getByListName('persona');

        view('timelogs.form', [
            'timeLog' => $timeLog,
            'tasks' => $tasks,
            'persons' => $persons,
        ]);
    }

    /**
     * Update time log
     */
    public function update(int $id): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/timelogs/' . $id . '/edit');
        }

        $timeLog = $this->timeLogModel->find($id);
        if (!$timeLog) {
            flash('error', 'Registro ore non trovato');
            redirect('/timelogs');
        }

        $oldHours = $timeLog['hours'];
        $oldTaskId = $timeLog['task_id'];

        $data = $this->validateTimeLogData($_POST);

        $this->timeLogModel->update($id, $data);

        // Update task hours: subtract old, add new
        if ($oldTaskId && $oldTaskId == $data['task_id']) {
            // Same task: adjust difference
            $diff = $data['hours'] - $oldHours;
            if ($diff != 0) {
                $task = $this->taskModel->find($oldTaskId);
                if ($task) {
                    $newSpent = max(0, ($task['hours_spent'] ?? 0) + $diff);
                    $this->taskModel->update($oldTaskId, ['hours_spent' => $newSpent]);
                }
            }
        } else {
            // Different task or changed task
            if ($oldTaskId) {
                // Subtract from old task
                $task = $this->taskModel->find($oldTaskId);
                if ($task) {
                    $newSpent = max(0, ($task['hours_spent'] ?? 0) - $oldHours);
                    $this->taskModel->update($oldTaskId, ['hours_spent' => $newSpent]);
                }
            }
            if ($data['task_id']) {
                // Add to new task
                $this->taskModel->addHours($data['task_id'], $data['hours']);
            }
        }

        flash('success', 'Registro ore aggiornato con successo ✅');
        redirect('/timelogs');
    }

    /**
     * Delete time log
     */
    public function delete(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        if (!is_admin()) {
            json_response(['error' => 'Solo gli admin possono eliminare'], 403);
        }

        $timeLog = $this->timeLogModel->find($id);
        if ($timeLog && $timeLog['task_id']) {
            // Subtract hours from task
            $task = $this->taskModel->find($timeLog['task_id']);
            if ($task) {
                $newSpent = max(0, ($task['hours_spent'] ?? 0) - $timeLog['hours']);
                $this->taskModel->update($timeLog['task_id'], ['hours_spent' => $newSpent]);
            }
        }

        $this->timeLogModel->delete($id);

        flash('success', 'Registro ore eliminato');
        json_response(['success' => true]);
    }

    /**
     * Validate time log data
     */
    private function validateTimeLogData(array $post): array {
        $taskId = !empty($post['task_id']) ? (int)$post['task_id'] : null;

        return [
            'date' => $post['date'] ?? date('Y-m-d'),
            'person' => trim($post['person'] ?? auth()['name']),
            'task_id' => $taskId,
            'description' => trim($post['description'] ?? ''),
            'hours' => floatval($post['hours'] ?? 0),
            'output_link' => trim($post['output_link'] ?? ''),
            'blocked' => ($post['blocked'] ?? 'No') === 'Sì' ? 'Sì' : 'No',
            'notes' => trim($post['notes'] ?? ''),
        ];
    }
}
