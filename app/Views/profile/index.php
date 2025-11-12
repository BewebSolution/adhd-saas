<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0">
            <i class="fas fa-user-circle"></i> Il mio profilo
        </h1>
        <p class="text-muted">Gestisci le tue informazioni personali</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Profile Info Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informazioni Account</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/profile/update') ?>" id="profileForm">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <!-- Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome Completo</label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control form-control-lg"
                            value="<?= esc($user['name']) ?>"
                            required
                        >
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control form-control-lg"
                            value="<?= esc($user['email']) ?>"
                            required
                        >
                        <div class="form-text">Usata per login e notifiche</div>
                    </div>

                    <!-- Role (readonly) -->
                    <div class="mb-3">
                        <label class="form-label">Ruolo</label>
                        <input
                            type="text"
                            class="form-control"
                            value="<?= $user['role'] === 'admin' ? 'Amministratore' : 'Tirocinante' ?>"
                            readonly
                        >
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3"><i class="bi bi-shield-lock"></i> Cambia Password</h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Compila solo se vuoi cambiare la password
                    </div>

                    <!-- Current Password -->
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Attuale</label>
                        <input
                            type="password"
                            name="current_password"
                            id="current_password"
                            class="form-control"
                            autocomplete="current-password"
                        >
                    </div>

                    <!-- New Password -->
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nuova Password</label>
                        <input
                            type="password"
                            name="new_password"
                            id="new_password"
                            class="form-control"
                            autocomplete="new-password"
                        >
                        <div class="form-text">Minimo 6 caratteri</div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Conferma Nuova Password</label>
                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            class="form-control"
                            autocomplete="new-password"
                        >
                    </div>

                    <!-- Submit -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Salva Modifiche
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Account Info -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-calendar"></i> Info Account</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Registrato il:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                </p>
                <p class="mb-0">
                    <strong>ID Utente:</strong><br>
                    #<?= $user['id'] ?>
                </p>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Link Rapidi</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= url('/') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="<?= url('/tasks') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-list-task"></i> Le mie attività
                </a>
                <a href="<?= url('/timelogs') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-clock-history"></i> Registro ore
                </a>
                <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= url('/ai/settings') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-robot"></i> Impostazioni AI
                </a>
                <a href="<?= url('/users') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-users"></i> Gestione Utenti
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Password validation
document.getElementById('profileForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const currentPassword = document.getElementById('current_password').value;

    // If trying to change password
    if (newPassword || confirmPassword) {
        if (!currentPassword) {
            e.preventDefault();
            alert('Inserisci la password attuale per cambiarla');
            document.getElementById('current_password').focus();
            return;
        }

        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Le nuove password non coincidono');
            document.getElementById('confirm_password').focus();
            return;
        }

        if (newPassword.length < 6) {
            e.preventDefault();
            alert('La nuova password deve essere di almeno 6 caratteri');
            document.getElementById('new_password').focus();
            return;
        }
    }
});
</script>

<?php
$content = ob_get_clean();
$title = 'Il mio profilo - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
