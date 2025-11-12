<?php

namespace App\Controllers;

use App\Models\ListItem;

class SettingsController {
    private ListItem $listModel;

    public function __construct() {
        require_auth();
        $this->listModel = new ListItem();
    }

    /**
     * Show lists management page
     */
    public function lists(): void {
        if (!is_admin()) {
            flash('error', 'Solo gli admin possono accedere alle impostazioni');
            redirect('/');
        }

        $allLists = $this->listModel->getAllGrouped();

        view('settings.lists', [
            'allLists' => $allLists,
        ]);
    }

    /**
     * Add item to list
     */
    public function addListItem(): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        if (!is_admin()) {
            json_response(['error' => 'Solo gli admin possono modificare le liste'], 403);
        }

        $listName = trim($_POST['list_name'] ?? '');
        $value = trim($_POST['value'] ?? '');

        if (empty($listName) || empty($value)) {
            json_response(['error' => 'Lista e valore sono obbligatori'], 400);
        }

        $id = $this->listModel->addItem($listName, $value);

        if ($id === null) {
            json_response(['error' => 'Valore già esistente in questa lista'], 400);
        }

        json_response([
            'success' => true,
            'message' => 'Valore aggiunto ✅',
            'id' => $id
        ]);
    }

    /**
     * Update list item
     */
    public function updateListItem(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        if (!is_admin()) {
            json_response(['error' => 'Solo gli admin possono modificare le liste'], 403);
        }

        $newValue = trim($_POST['value'] ?? '');

        if (empty($newValue)) {
            json_response(['error' => 'Il valore non può essere vuoto'], 400);
        }

        $success = $this->listModel->updateValue($id, $newValue);

        if (!$success) {
            json_response(['error' => 'Elemento non trovato'], 404);
        }

        json_response([
            'success' => true,
            'message' => 'Valore aggiornato ✅'
        ]);
    }

    /**
     * Delete list item
     */
    public function deleteListItem(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
        }

        if (!is_admin()) {
            json_response(['error' => 'Solo gli admin possono modificare le liste'], 403);
        }

        $success = $this->listModel->deleteIfNotInUse($id);

        if (!$success) {
            json_response(['error' => 'Impossibile eliminare: elemento non trovato o in uso'], 400);
        }

        json_response([
            'success' => true,
            'message' => 'Valore eliminato'
        ]);
    }
}
