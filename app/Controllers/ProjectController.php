<?php

namespace App\Controllers;

use App\Models\Project;
use App\Models\Task;

class ProjectController {
    private Project $projectModel;
    private Task $taskModel;

    public function __construct() {
        require_auth();
        $this->projectModel = new Project();
        $this->taskModel = new Task();
    }

    /**
     * Lista progetti
     */
    public function index(): void {
        $projects = $this->projectModel->all(['name' => 'ASC']);

        // Conta attività per progetto
        foreach ($projects as &$project) {
            $tasks = $this->taskModel->where(['project_id' => $project['id']]);
            $project['tasks_count'] = count($tasks);
        }

        view('projects.index', compact('projects'));
    }

    /**
     * Crea progetto
     */
    public function store(): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        $name = trim($_POST['name'] ?? '');

        // Validation
        if (empty($name)) {
            json_response(['error' => 'Il nome del progetto è obbligatorio'], 400);
        }

        // Check if project exists
        $existing = $this->projectModel->findBy('name', $name);
        if ($existing) {
            json_response(['error' => 'Esiste già un progetto con questo nome'], 409);
        }

        try {
            $id = $this->projectModel->create([
                'name' => $name
            ]);

            json_response([
                'success' => true,
                'message' => 'Progetto creato con successo',
                'project' => [
                    'id' => $id,
                    'name' => $name,
                    'tasks_count' => 0
                ]
            ]);
        } catch (\Exception $e) {
            json_response(['error' => 'Errore durante la creazione: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Modifica progetto
     */
    public function update(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        $project = $this->projectModel->find($id);
        if (!$project) {
            json_response(['error' => 'Progetto non trovato'], 404);
        }

        $name = trim($_POST['name'] ?? '');

        // Validation
        if (empty($name)) {
            json_response(['error' => 'Il nome del progetto è obbligatorio'], 400);
        }

        // Check if another project with same name exists
        $existing = $this->projectModel->findBy('name', $name);
        if ($existing && $existing['id'] != $id) {
            json_response(['error' => 'Esiste già un altro progetto con questo nome'], 409);
        }

        try {
            $this->projectModel->update($id, [
                'name' => $name
            ]);

            json_response([
                'success' => true,
                'message' => 'Progetto aggiornato con successo',
                'project' => [
                    'id' => $id,
                    'name' => $name
                ]
            ]);
        } catch (\Exception $e) {
            json_response(['error' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina progetto
     */
    public function delete(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        // Only admin can delete
        if (!is_admin()) {
            json_response(['error' => 'Non hai i permessi per eliminare progetti'], 403);
        }

        $project = $this->projectModel->find($id);
        if (!$project) {
            json_response(['error' => 'Progetto non trovato'], 404);
        }

        // Check if project has tasks
        $tasks = $this->taskModel->where(['project_id' => $id]);
        if (!empty($tasks)) {
            json_response([
                'error' => 'Impossibile eliminare: il progetto ha ' . count($tasks) . ' attività collegate. Elimina prima le attività o spostale ad altro progetto.'
            ], 409);
        }

        try {
            $this->projectModel->delete($id);

            json_response([
                'success' => true,
                'message' => 'Progetto eliminato con successo'
            ]);
        } catch (\Exception $e) {
            json_response(['error' => 'Errore durante l\'eliminazione: ' . $e->getMessage()], 500);
        }
    }
}
