<?php

namespace App\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\ListItem;
use App\Models\TimeLog;

class TaskController {
    private Task $taskModel;
    private Project $projectModel;
    private ListItem $listModel;
    private TimeLog $timeLogModel;

    public function __construct() {
        require_auth();
        $this->taskModel = new Task();
        $this->projectModel = new Project();
        $this->listModel = new ListItem();
        $this->timeLogModel = new TimeLog();
    }

    /**
     * List all tasks with filters
     */
    public function index(): void {
        $filters = [
            'project_id' => $_GET['project'] ?? '',
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'assignee' => $_GET['assignee'] ?? '',
            'due_from' => $_GET['due_from'] ?? '',
            'due_to' => $_GET['due_to'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        // Se l'utente è intern, forza il filtro per vedere solo le sue attività
        if (auth()['role'] === 'intern') {
            $filters['assignee'] = auth()['name'];
        }

        $tasks = $this->taskModel->getAllWithProjects($filters);
        $projects = $this->projectModel->all(['name' => 'ASC']);
        $statuses = $this->listModel->getByListName('stato');
        $priorities = $this->listModel->getByListName('priorita');
        $persons = $this->listModel->getByListName('persona');

        view('tasks.index', [
            'tasks' => $tasks,
            'projects' => $projects,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'persons' => $persons,
            'filters' => $filters,
        ]);
    }

    /**
     * Show single task
     */
    public function show(int $id): void {
        $task = $this->taskModel->find($id);

        if (!$task) {
            flash('error', 'Attività non trovata');
            redirect('/tasks');
        }

        // Get task time logs
        $timeLogs = $this->timeLogModel->getByTask($id);

        // Get project
        $project = $this->projectModel->find($task['project_id']);

        view('tasks.show', [
            'task' => $task,
            'project' => $project,
            'timeLogs' => $timeLogs,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): void {
        $projects = $this->projectModel->all(['name' => 'ASC']);
        $statuses = $this->listModel->getByListName('stato');
        $priorities = $this->listModel->getByListName('priorita');
        $persons = $this->listModel->getByListName('persona');

        view('tasks.form', [
            'task' => null,
            'projects' => $projects,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'persons' => $persons,
        ]);
    }

    /**
     * Store new task
     */
    public function store(): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/tasks/create');
        }

        $data = $this->validateTaskData($_POST);

        // Generate code if empty
        if (empty($data['code'])) {
            $data['code'] = $this->taskModel->generateNextCode();
        }

        $id = $this->taskModel->create($data);

        flash('success', 'Attività creata con successo ✅ Codice: ' . $data['code']);
        redirect('/tasks/' . $id);
    }

    /**
     * Show edit form
     */
    public function edit(int $id): void {
        $task = $this->taskModel->find($id);

        if (!$task) {
            flash('error', 'Attività non trovata');
            redirect('/tasks');
        }

        $projects = $this->projectModel->all(['name' => 'ASC']);
        $statuses = $this->listModel->getByListName('stato');
        $priorities = $this->listModel->getByListName('priorita');
        $persons = $this->listModel->getByListName('persona');

        view('tasks.form', [
            'task' => $task,
            'projects' => $projects,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'persons' => $persons,
        ]);
    }

    /**
     * Update task
     */
    public function update(int $id): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/tasks/' . $id . '/edit');
        }

        $task = $this->taskModel->find($id);
        if (!$task) {
            flash('error', 'Attività non trovata');
            redirect('/tasks');
        }

        $data = $this->validateTaskData($_POST);

        // Keep existing code if not provided
        if (empty($data['code'])) {
            $data['code'] = $task['code'];
        }

        $this->taskModel->update($id, $data);

        flash('success', 'Attività aggiornata con successo ✅');
        redirect('/tasks/' . $id);
    }

    /**
     * Delete task
     */
    public function delete(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        if (!is_admin()) {
            json_response(['error' => 'Solo gli admin possono eliminare attività'], 403);
        }

        $this->taskModel->delete($id);

        flash('success', 'Attività eliminata');
        json_response(['success' => true]);
    }

    /**
     * Toggle task status (quick action)
     */
    public function toggleStatus(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        $task = $this->taskModel->find($id);
        if (!$task) {
            json_response(['error' => 'Attività non trovata'], 404);
        }

        // Check if a specific status was provided
        $newStatus = $_POST['status'] ?? null;

        if (!$newStatus) {
            // If no status provided, do the toggle as before
            $currentStatus = $task['status'];
            $newStatus = match($currentStatus) {
                'Da fare' => 'In corso',
                'In corso' => 'Fatto',
                'In revisione' => 'Fatto',
                'Fatto' => 'Da fare',
                default => 'In corso',
            };
        } else {
            // Validate the provided status
            $validStatuses = ['Da fare', 'In corso', 'In revisione', 'Fatto'];
            if (!in_array($newStatus, $validStatuses)) {
                json_response(['error' => 'Stato non valido'], 400);
            }
        }

        $this->taskModel->updateStatus($id, $newStatus);

        json_response([
            'success' => true,
            'new_status' => $newStatus,
            'message' => 'Stato aggiornato a: ' . $newStatus
        ]);
    }

    /**
     * Add hours to task (quick action)
     */
    public function addHours(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        $task = $this->taskModel->find($id);
        if (!$task) {
            json_response(['error' => 'Attività non trovata'], 404);
        }

        $hours = floatval($_POST['hours'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if ($hours <= 0) {
            json_response(['error' => 'Le ore devono essere maggiori di zero'], 400);
        }

        // Add hours to task
        $this->taskModel->addHours($id, $hours);

        // Create time log entry
        $this->timeLogModel->create([
            'date' => date('Y-m-d'),
            'person' => auth()['name'],
            'task_id' => $id,
            'description' => $description ?: 'Ore aggiunte da attività',
            'hours' => $hours,
            'output_link' => '',
            'blocked' => 'No',
            'notes' => '',
        ]);

        json_response([
            'success' => true,
            'message' => 'Aggiunte ' . $hours . ' ore ✅'
        ]);
    }

    /**
     * Validate task data
     */
    private function validateTaskData(array $post): array {
        $projectId = (int) ($post['project_id'] ?? 0);

        if ($projectId <= 0) {
            flash('error', 'Progetto non valido');
            redirect('/tasks');
        }

        $dueAt = null;
        if (!empty($post['due_date']) && !empty($post['due_time'])) {
            $dueAt = $post['due_date'] . ' ' . $post['due_time'] . ':00';
        } elseif (!empty($post['due_date'])) {
            $dueAt = $post['due_date'] . ' 23:59:59';
        }

        return [
            'code' => trim($post['code'] ?? ''),
            'date' => $post['date'] ?? date('Y-m-d'),
            'project_id' => $projectId,
            'title' => trim($post['title'] ?? ''),
            'description' => trim($post['description'] ?? ''),
            'priority' => trim($post['priority'] ?? ''),
            'status' => trim($post['status'] ?? 'Da fare'),
            'assignee' => trim($post['assignee'] ?? auth()['name']),
            'due_at' => $dueAt,
            'hours_estimated' => !empty($post['hours_estimated']) ? floatval($post['hours_estimated']) : null,
            'hours_spent' => !empty($post['hours_spent']) ? floatval($post['hours_spent']) : 0,
            'link' => trim($post['link'] ?? ''),
            'notes' => trim($post['notes'] ?? ''),
        ];
    }
}
