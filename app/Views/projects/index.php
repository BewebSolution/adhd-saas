<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0">
            <i class="fas fa-folder"></i> Gestione Progetti
        </h1>
        <p class="text-muted">Crea, modifica ed elimina i progetti</p>
    </div>
</div>

<!-- Add Project Form -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Aggiungi Nuovo Progetto</h5>
    </div>
    <div class="card-body">
        <form id="addProjectForm" onsubmit="return addProject(event)" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="col-md-8">
                <label for="projectName" class="form-label">Nome Progetto</label>
                <input
                    type="text"
                    class="form-control form-control-lg"
                    id="projectName"
                    name="name"
                    placeholder="es: NOA Wedding, Amevista, Davino Cerimonia"
                    required
                    autofocus
                >
                <div class="form-text">Il nome deve essere univoco</div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-plus-circle"></i> Aggiungi Progetto
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Projects List -->
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list"></i> Progetti Esistenti (<?= count($projects) ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($projects)): ?>
            <p class="text-muted mb-0">
                <i class="fas fa-inbox"></i> Nessun progetto creato. Aggiungine uno sopra!
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px">ID</th>
                            <th>Nome Progetto</th>
                            <th style="width: 120px" class="text-center">Attività</th>
                            <th style="width: 200px">Data Creazione</th>
                            <th style="width: 200px" class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody id="projectsTableBody">
                        <?php foreach ($projects as $project): ?>
                            <tr id="project-row-<?= $project['id'] ?>">
                                <td><strong>#<?= $project['id'] ?></strong></td>
                                <td>
                                    <span class="project-name-display" id="name-display-<?= $project['id'] ?>">
                                        <i class="fas fa-folder text-primary"></i>
                                        <strong><?= esc($project['name']) ?></strong>
                                    </span>
                                    <div class="project-name-edit" id="name-edit-<?= $project['id'] ?>" style="display: none;">
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="name-input-<?= $project['id'] ?>"
                                            value="<?= esc($project['name']) ?>"
                                        >
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($project['tasks_count'] > 0): ?>
                                        <a href="<?= url('/tasks?project=' . $project['id']) ?>" class="badge bg-info text-decoration-none">
                                            <?= $project['tasks_count'] ?> attività
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">0 attività</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i>
                                        <?= format_date($project['created_at'] ?? '') ?>
                                    </small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button
                                            onclick="editProject(<?= $project['id'] ?>)"
                                            class="btn btn-outline-secondary edit-btn-<?= $project['id'] ?>"
                                            title="Modifica nome"
                                        >
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button
                                            onclick="saveProject(<?= $project['id'] ?>)"
                                            class="btn btn-success save-btn-<?= $project['id'] ?>"
                                            style="display: none;"
                                            title="Salva modifiche"
                                        >
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button
                                            onclick="cancelEdit(<?= $project['id'] ?>)"
                                            class="btn btn-secondary cancel-btn-<?= $project['id'] ?>"
                                            style="display: none;"
                                            title="Annulla"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php if (is_admin()): ?>
                                            <button
                                                onclick="deleteProject(<?= $project['id'] ?>, '<?= esc($project['name']) ?>', <?= $project['tasks_count'] ?>)"
                                                class="btn btn-outline-danger delete-btn-<?= $project['id'] ?>"
                                                title="Elimina progetto"
                                                <?= $project['tasks_count'] > 0 ? 'disabled' : '' ?>
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (is_admin()): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Nota:</strong> Non puoi eliminare progetti con attività collegate. Sposta o elimina prima le attività.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
const csrfToken = '<?= csrf_token() ?>';

// Add project
function addProject(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    fetch('/tirocinio/beweb-app/public/projects', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // Reload page to show new project
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Errore durante la creazione', 'error');
        }
    })
    .catch(() => {
        showToast('Errore di connessione', 'error');
    });

    return false;
}

// Edit project
function editProject(id) {
    document.getElementById(`name-display-${id}`).style.display = 'none';
    document.getElementById(`name-edit-${id}`).style.display = 'block';

    document.querySelector(`.edit-btn-${id}`).style.display = 'none';
    document.querySelector(`.save-btn-${id}`).style.display = 'inline-block';
    document.querySelector(`.cancel-btn-${id}`).style.display = 'inline-block';
    document.querySelector(`.delete-btn-${id}`)?.style.setProperty('display', 'none');

    document.getElementById(`name-input-${id}`).focus();
}

// Save project
function saveProject(id) {
    const newName = document.getElementById(`name-input-${id}`).value.trim();

    if (!newName) {
        showToast('Il nome non può essere vuoto', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('name', newName);

    fetch(`/tirocinio/beweb-app/public/projects/${id}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // Update display
            document.getElementById(`name-display-${id}`).innerHTML =
                `<i class="fas fa-folder text-primary"></i> <strong>${escapeHtml(newName)}</strong>`;
            cancelEdit(id);
        } else {
            showToast(data.error || 'Errore durante l\'aggiornamento', 'error');
        }
    })
    .catch(() => {
        showToast('Errore di connessione', 'error');
    });
}

// Cancel edit
function cancelEdit(id) {
    document.getElementById(`name-display-${id}`).style.display = 'block';
    document.getElementById(`name-edit-${id}`).style.display = 'none';

    document.querySelector(`.edit-btn-${id}`).style.display = 'inline-block';
    document.querySelector(`.save-btn-${id}`).style.display = 'none';
    document.querySelector(`.cancel-btn-${id}`).style.display = 'none';
    document.querySelector(`.delete-btn-${id}`)?.style.removeProperty('display');
}

// Delete project
function deleteProject(id, name, tasksCount) {
    if (tasksCount > 0) {
        showToast(`Impossibile eliminare: il progetto "${name}" ha ${tasksCount} attività collegate`, 'error');
        return;
    }

    if (!confirm(`Eliminare definitivamente il progetto "${name}"?\n\nQuesta azione non può essere annullata.`)) {
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);

    fetch(`/tirocinio/beweb-app/public/projects/${id}/delete`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // Remove row
            document.getElementById(`project-row-${id}`).remove();

            // Check if no more projects
            const tbody = document.getElementById('projectsTableBody');
            if (tbody.children.length === 0) {
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            showToast(data.error || 'Errore durante l\'eliminazione', 'error');
        }
    })
    .catch(() => {
        showToast('Errore di connessione', 'error');
    });
}

// Escape HTML helper
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php
$content = ob_get_clean();
$title = 'Gestione Progetti - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
