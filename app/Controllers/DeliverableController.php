<?php

namespace App\Controllers;

use App\Models\Deliverable;
use App\Models\Project;
use App\Models\ListItem;

class DeliverableController {
    private Deliverable $deliverableModel;
    private Project $projectModel;
    private ListItem $listModel;

    public function __construct() {
        require_auth();
        $this->deliverableModel = new Deliverable();
        $this->projectModel = new Project();
        $this->listModel = new ListItem();
    }

    /**
     * List all deliverables with filters
     */
    public function index(): void {
        $filters = [
            'project_id' => $_GET['project'] ?? '',
            'type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        $deliverables = $this->deliverableModel->getAllWithProjects($filters);
        $projects = $this->projectModel->all(['name' => 'ASC']);
        $types = $this->listModel->getByListName('tipo_consegna');
        $statuses = $this->listModel->getByListName('stato');

        view('deliverables.index', [
            'deliverables' => $deliverables,
            'projects' => $projects,
            'types' => $types,
            'statuses' => $statuses,
            'filters' => $filters,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): void {
        $projects = $this->projectModel->all(['name' => 'ASC']);
        $types = $this->listModel->getByListName('tipo_consegna');
        $statuses = $this->listModel->getByListName('stato');

        view('deliverables.form', [
            'deliverable' => null,
            'projects' => $projects,
            'types' => $types,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Store new deliverable
     */
    public function store(): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/deliverables/create');
        }

        $data = $this->validateDeliverableData($_POST);

        $this->deliverableModel->create($data);

        flash('success', 'Consegna creata con successo ✅');
        redirect('/deliverables');
    }

    /**
     * Show edit form
     */
    public function edit(int $id): void {
        $deliverable = $this->deliverableModel->find($id);

        if (!$deliverable) {
            flash('error', 'Consegna non trovata');
            redirect('/deliverables');
        }

        $projects = $this->projectModel->all(['name' => 'ASC']);
        $types = $this->listModel->getByListName('tipo_consegna');
        $statuses = $this->listModel->getByListName('stato');

        view('deliverables.form', [
            'deliverable' => $deliverable,
            'projects' => $projects,
            'types' => $types,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Update deliverable
     */
    public function update(int $id): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/deliverables/' . $id . '/edit');
        }

        $deliverable = $this->deliverableModel->find($id);
        if (!$deliverable) {
            flash('error', 'Consegna non trovata');
            redirect('/deliverables');
        }

        $data = $this->validateDeliverableData($_POST);

        $this->deliverableModel->update($id, $data);

        flash('success', 'Consegna aggiornata con successo ✅');
        redirect('/deliverables');
    }

    /**
     * Delete deliverable
     */
    public function delete(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        if (!is_admin()) {
            json_response(['error' => 'Solo gli admin possono eliminare'], 403);
        }

        $this->deliverableModel->delete($id);

        flash('success', 'Consegna eliminata');
        json_response(['success' => true]);
    }

    /**
     * Validate deliverable data
     */
    private function validateDeliverableData(array $post): array {
        $projectId = (int) ($post['project_id'] ?? 0);

        if ($projectId <= 0) {
            flash('error', 'Progetto non valido');
            redirect('/deliverables');
        }

        return [
            'date' => $post['date'] ?? date('Y-m-d'),
            'project_id' => $projectId,
            'type' => trim($post['type'] ?? ''),
            'title' => trim($post['title'] ?? ''),
            'link' => trim($post['link'] ?? ''),
            'status' => trim($post['status'] ?? 'In revisione'),
            'notes' => trim($post['notes'] ?? ''),
        ];
    }
}
