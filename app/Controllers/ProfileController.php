<?php

namespace App\Controllers;

/**
 * Profile Controller - Gestione profilo utente
 */
class ProfileController {

    public function __construct() {
        require_auth();
    }

    /**
     * Show profile page
     */
    public function index(): void {
        $db = get_db();
        $userId = auth()['id'];

        // Fetch full user data including created_at
        $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        view('profile.index', compact('user'));
    }

    /**
     * Update profile
     */
    public function update(): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/profile');
            return;
        }

        $userId = auth()['id'];
        $db = get_db();

        try {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validation
            if (empty($name)) {
                flash('error', 'Il nome è obbligatorio');
                redirect('/profile');
                return;
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Email non valida');
                redirect('/profile');
                return;
            }

            // Check if email is already used by another user
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                flash('error', 'Email già in uso da un altro utente');
                redirect('/profile');
                return;
            }

            // Update name and email
            $stmt = $db->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
            $stmt->execute([$name, $email, $userId]);

            // Update password if provided
            if (!empty($newPassword)) {
                // Verify current password
                $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
                $stmt->execute([$userId]);
                $user = $stmt->fetch();

                if (!password_verify($currentPassword, $user['password_hash'])) {
                    flash('error', 'Password attuale non corretta');
                    redirect('/profile');
                    return;
                }

                // Check new password confirmation
                if ($newPassword !== $confirmPassword) {
                    flash('error', 'Le nuove password non coincidono');
                    redirect('/profile');
                    return;
                }

                // Check password strength
                if (strlen($newPassword) < 6) {
                    flash('error', 'La nuova password deve essere di almeno 6 caratteri');
                    redirect('/profile');
                    return;
                }

                // Update password
                $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);

                flash('success', '✅ Profilo e password aggiornati con successo');
            } else {
                flash('success', '✅ Profilo aggiornato con successo');
            }

            // Update session
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;

            redirect('/profile');
        } catch (\Exception $e) {
            error_log('Profile update error: ' . $e->getMessage());
            flash('error', 'Errore aggiornamento profilo: ' . $e->getMessage());
            redirect('/profile');
        }
    }
}
