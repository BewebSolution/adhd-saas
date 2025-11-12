<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0">
            <i class="fas fa-users"></i> Gestione Utenti
        </h1>
        <p class="text-muted">Gestisci gli utenti dell'applicazione</p>
    </div>
    <div class="col-auto">
        <a href="<?= url('/users/create') ?>" class="btn btn-primary btn-lg">
            <i class="fas fa-user-plus"></i> Nuovo Utente
        </a>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Ruolo</th>
                        <th>Registrato</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>#<?= $user['id'] ?></td>
                        <td>
                            <strong><?= esc($user['name']) ?></strong>
                            <?php if ($user['id'] === auth()['id']): ?>
                                <span class="badge bg-info">Tu</span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($user['email']) ?></td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="badge bg-danger">Amministratore</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Tirocinante</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                        <td class="text-end">
                            <?php if ($user['id'] !== auth()['id']): ?>
                                <button
                                    onclick="resetPassword(<?= $user['id'] ?>, '<?= esc($user['name']) ?>')"
                                    class="btn btn-sm btn-warning"
                                    title="Reset password"
                                >
                                    <i class="fas fa-key"></i>
                                </button>
                                <button
                                    onclick="deleteUser(<?= $user['id'] ?>, '<?= esc($user['name']) ?>')"
                                    class="btn btn-sm btn-danger"
                                    title="Elimina utente"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php else: ?>
                                <a href="<?= url('/profile') ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-pencil-alt"></i> Modifica
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($users)): ?>
            <p class="text-center text-muted my-5">Nessun utente trovato</p>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteUser(userId, userName) {
    if (!confirm(`Sei sicuro di voler eliminare l'utente "${userName}"?\n\nQuesta azione è irreversibile e cancellerà anche tutti i dati associati (task, time log, note, ecc.).`)) {
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');

    fetch(`/users/${userId}/delete`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Errore eliminazione', 'error');
        }
    })
    .catch(error => {
        showToast('Errore di connessione', 'error');
        console.error(error);
    });
}

function resetPassword(userId, userName) {
    const newPassword = prompt(`Nuova password per "${userName}":\n(minimo 6 caratteri)`);

    if (!newPassword) {
        return;
    }

    if (newPassword.length < 6) {
        alert('La password deve essere di almeno 6 caratteri');
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    formData.append('new_password', newPassword);

    fetch(`/users/${userId}/reset-password`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.error || 'Errore reset password', 'error');
        }
    })
    .catch(error => {
        showToast('Errore di connessione', 'error');
        console.error(error);
    });
}
</script>

<?php
$content = ob_get_clean();
$title = 'Gestione Utenti - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
