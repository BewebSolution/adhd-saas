<?php

namespace App\Controllers;

/**
 * User Management Controller - Solo Admin
 */
class UserManagementController {

    public function __construct() {
        require_auth();

        // Check admin
        if (auth()['role'] !== 'admin') {
            flash('error', 'Accesso negato. Solo amministratori.');
            redirect('/');
            exit;
        }
    }

    /**
     * List all users
     */
    public function index(): void {
        $db = get_db();

        try {
            $stmt = $db->query('SELECT * FROM users ORDER BY created_at DESC');
            $users = $stmt->fetchAll();

            view('users.index', compact('users'));
        } catch (\Exception $e) {
            error_log('User list error: ' . $e->getMessage());
            flash('error', 'Errore caricamento utenti');
            redirect('/');
        }
    }

    /**
     * Show create user form
     */
    public function create(): void {
        view('users.create');
    }

    /**
     * Store new user
     */
    public function store(): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/users/create');
            return;
        }

        $db = get_db();

        try {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'intern';

            // Validation
            if (empty($name)) {
                flash('error', 'Il nome è obbligatorio');
                redirect('/users/create');
                return;
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Email non valida');
                redirect('/users/create');
                return;
            }

            if (empty($password) || strlen($password) < 6) {
                flash('error', 'La password deve essere di almeno 6 caratteri');
                redirect('/users/create');
                return;
            }

            if (!in_array($role, ['admin', 'intern'])) {
                flash('error', 'Ruolo non valido');
                redirect('/users/create');
                return;
            }

            // Check if email already exists
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                flash('error', 'Email già registrata');
                redirect('/users/create');
                return;
            }

            // Create user
            $stmt = $db->prepare('
                INSERT INTO users (name, email, password_hash, role)
                VALUES (?, ?, ?, ?)
            ');

            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role
            ]);

            flash('success', "✅ Utente '$name' creato con successo");
            redirect('/users');
        } catch (\Exception $e) {
            error_log('User create error: ' . $e->getMessage());
            flash('error', 'Errore creazione utente: ' . $e->getMessage());
            redirect('/users/create');
        }
    }

    /**
     * Delete user
     */
    public function delete(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
            return;
        }

        // Cannot delete yourself
        if ($id === auth()['id']) {
            json_response(['error' => 'Non puoi eliminare te stesso'], 400);
            return;
        }

        $db = get_db();

        try {
            // Check if user exists
            $stmt = $db->prepare('SELECT name FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $user = $stmt->fetch();

            if (!$user) {
                json_response(['error' => 'Utente non trovato'], 404);
                return;
            }

            // Delete user (CASCADE will handle related data)
            $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);

            json_response([
                'success' => true,
                'message' => "Utente '{$user['name']}' eliminato"
            ]);
        } catch (\Exception $e) {
            error_log('User delete error: ' . $e->getMessage());
            json_response(['error' => 'Errore eliminazione utente'], 500);
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(int $id): void {
        if (!verify_csrf()) {
            json_response(['error' => 'Token CSRF non valido'], 403);
            return;
        }

        $db = get_db();

        try {
            $newPassword = $_POST['new_password'] ?? '';

            if (strlen($newPassword) < 6) {
                json_response(['error' => 'Password troppo corta (min 6 caratteri)'], 400);
                return;
            }

            $stmt = $db->prepare('SELECT name FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $user = $stmt->fetch();

            if (!$user) {
                json_response(['error' => 'Utente non trovato'], 404);
                return;
            }

            // Update password
            $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $id]);

            json_response([
                'success' => true,
                'message' => "Password di '{$user['name']}' aggiornata"
            ]);
        } catch (\Exception $e) {
            error_log('Password reset error: ' . $e->getMessage());
            json_response(['error' => 'Errore reset password'], 500);
        }
    }
}
