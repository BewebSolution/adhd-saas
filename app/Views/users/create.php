<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0">
            <i class="fas fa-user-plus"></i> Nuovo Utente
        </h1>
        <p class="text-muted">Aggiungi un nuovo utente all'applicazione</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= url('/users') ?>" id="createUserForm">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <!-- Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control form-control-lg"
                            required
                            autofocus
                        >
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control form-control-lg"
                            required
                        >
                        <div class="form-text">Usata per il login</div>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control form-control-lg"
                                required
                                minlength="6"
                            >
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <div class="form-text">Minimo 6 caratteri</div>
                    </div>

                    <!-- Role -->
                    <div class="mb-4">
                        <label for="role" class="form-label">Ruolo <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-select form-select-lg" required>
                            <option value="intern" selected>Tirocinante</option>
                            <option value="admin">Amministratore</option>
                        </select>
                        <div class="form-text">
                            <strong>Tirocinante:</strong> Può creare/modificare task, time log, note<br>
                            <strong>Amministratore:</strong> Accesso completo incluse impostazioni e gestione utenti
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Crea Utente
                        </button>
                        <a href="<?= url('/users') ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-times"></i> Annulla
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-light">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-info-circle"></i> Info</h5>
                <p class="mb-2"><small>
                    Dopo aver creato l'utente, le credenziali dovranno essere comunicate manualmente all'utente.
                </small></p>
                <p class="mb-0"><small>
                    <strong>Suggerimento:</strong> Chiedi all'utente di cambiare la password al primo accesso dalla sezione "Il mio profilo".
                </small></p>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fas fa-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('fas fa-eye');
    }
}
</script>

<?php
$content = ob_get_clean();
$title = 'Nuovo Utente - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
