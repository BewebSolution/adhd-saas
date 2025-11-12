<?php

namespace App\Controllers;

use App\Models\User;

class AuthController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Show login form
     */
    public function showLogin(): void {
        // If already logged in, redirect to dashboard
        if (is_logged_in()) {
            redirect('/');
        }

        view('auth.login');
    }

    /**
     * Handle login
     */
    public function login(): void {
        if (!verify_csrf()) {
            flash('error', 'Token CSRF non valido');
            redirect('/login');
        }

        // Rate limiting check
        $this->checkRateLimit();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            flash('error', 'Email e password sono obbligatori');
            redirect('/login');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
            $this->incrementLoginAttempts();
            flash('error', 'Credenziali non valide');
            redirect('/login');
        }

        // Login successful
        $this->resetLoginAttempts();
        $this->createSession($user);

        flash('success', 'Benvenuto/a, ' . esc($user['name']) . '!');
        redirect('/');
    }

    /**
     * Handle logout
     */
    public function logout(): void {
        session_destroy();
        flash('success', 'Logout effettuato con successo');
        redirect('/login');
    }

    /**
     * Create user session
     */
    private function createSession(array $user): void {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
    }

    /**
     * Check login rate limit
     */
    private function checkRateLimit(): void {
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttempt = $_SESSION['last_login_attempt'] ?? 0;

        // Reset if more than 15 minutes have passed
        if (time() - $lastAttempt > 900) {
            $this->resetLoginAttempts();
            return;
        }

        if ($attempts >= 5) {
            flash('error', 'Troppi tentativi falliti. Riprova tra 15 minuti.');
            redirect('/login');
            exit;
        }
    }

    /**
     * Increment login attempts
     */
    private function incrementLoginAttempts(): void {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_login_attempt'] = time();
    }

    /**
     * Reset login attempts
     */
    private function resetLoginAttempts(): void {
        unset($_SESSION['login_attempts']);
        unset($_SESSION['last_login_attempt']);
    }
}
