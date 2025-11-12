<?php

namespace App\Controllers;

use App\Models\Note;
use App\Models\ListItem;

class NoteController {
    private Note $noteModel;
    private ListItem $listModel;

    public function __construct() {
        require_auth();
        $this->noteModel = new Note();
        $this->listModel = new ListItem();
    }

    /**
     * List all notes with filters
     */
    public function index(): void {
        $filters = [
            'owner' => $_GET['owner'] ?? '',
            'due_date' => $_GET['due_date'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        $notes = $this->noteModel->getAllFiltered($filters);
        $persons = $this->listModel->getByListName('persona');

        view('notes.index', [
            'notes' => $notes,
            'persons' => $persons,
            'filters' => $filters,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): void {
        $persons = $this->listModel->getByListName('persona');

        view('notes.form', [
            'note' => null,
            'persons' => $persons,
        ]);
    }

    /**
     * Store new note
     */
    public function store(): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/notes/create');
        }

        $data = $this->validateNoteData($_POST);

        $this->noteModel->create($data);

        flash('success', 'Nota creata con successo ✅');
        redirect('/notes');
    }

    /**
     * Show edit form
     */
    public function edit(int $id): void {
        $note = $this->noteModel->find($id);

        if (!$note) {
            flash('error', 'Nota non trovata');
            redirect('/notes');
        }

        $persons = $this->listModel->getByListName('persona');

        view('notes.form', [
            'note' => $note,
            'persons' => $persons,
        ]);
    }

    /**
     * Update note
     */
    public function update(int $id): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/notes/' . $id . '/edit');
        }

        $note = $this->noteModel->find($id);
        if (!$note) {
            flash('error', 'Nota non trovata');
            redirect('/notes');
        }

        $data = $this->validateNoteData($_POST);

        $this->noteModel->update($id, $data);

        flash('success', 'Nota aggiornata con successo ✅');
        redirect('/notes');
    }

    /**
     * Delete note
     */
    public function delete(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        if (!is_admin()) {
            json_response(['error' => 'Solo gli admin possono eliminare'], 403);
        }

        $this->noteModel->delete($id);

        flash('success', 'Nota eliminata');
        json_response(['success' => true]);
    }

    /**
     * Validate note data
     */
    private function validateNoteData(array $post): array {
        return [
            'date' => $post['date'] ?? date('Y-m-d'),
            'topic' => trim($post['topic'] ?? ''),
            'body' => trim($post['body'] ?? ''),
            'next_action' => trim($post['next_action'] ?? ''),
            'owner' => trim($post['owner'] ?? auth()['name']),
            'due_date' => !empty($post['due_date']) ? $post['due_date'] : null,
            'link' => trim($post['link'] ?? ''),
        ];
    }
}
