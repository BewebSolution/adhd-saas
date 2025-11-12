<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0">
            <i class="fas fa-tasks"></i> Attività
        </h1>
    </div>
    <div class="col-auto">
        <a href="<?= url('/tasks/create') ?>" class="btn btn-primary" accesskey="n" data-intro="new-task">
            <i class="fas fa-plus-circle"></i> Nuova Attività
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-4" data-intro="filters">
    <div class="card-body">
        <form method="GET" action="<?= url('/tasks') ?>" class="row g-3">
            <div class="col-md-3">
                <label for="project" class="form-label">Progetto</label>
                <select name="project" id="project" class="form-select">
                    <option value="">Tutti</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= $project['id'] ?>" <?= ($filters['project_id'] == $project['id']) ? 'selected' : '' ?>>
                            <?= esc($project['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="status" class="form-label">Stato</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Tutti</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= esc($status['value']) ?>" <?= ($filters['status'] === $status['value']) ? 'selected' : '' ?>>
                            <?= esc($status['value']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="priority" class="form-label">Priorità</label>
                <select name="priority" id="priority" class="form-select">
                    <option value="">Tutte</option>
                    <?php foreach ($priorities as $priority): ?>
                        <option value="<?= esc($priority['value']) ?>" <?= ($filters['priority'] === $priority['value']) ? 'selected' : '' ?>>
                            <?= esc($priority['value']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="assignee" class="form-label">Assegnatario</label>
                <select name="assignee" id="assignee" class="form-select">
                    <option value="">Tutti</option>
                    <?php foreach ($persons as $person): ?>
                        <option value="<?= esc($person['value']) ?>" <?= ($filters['assignee'] === $person['value']) ? 'selected' : '' ?>>
                            <?= esc($person['value']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="search" class="form-label">Cerca</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Titolo, codice..."
                       value="<?= esc($filters['search']) ?>">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtra
                </button>
                <a href="<?= url('/tasks') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-times-circle"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tasks List -->
<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($tasks)): ?>
            <p class="text-muted mb-0">
                <i class="fas fa-inbox"></i> Nessuna attività trovata.
                <a href="<?= url('/tasks/create') ?>">Creane una nuova</a>
            </p>
        <?php else: ?>
            <!-- Bulk actions -->
            <div class="mb-3 d-none" id="bulkActions">
                <div class="alert alert-info d-flex align-items-center justify-content-between">
                    <span>
                        <i class="fas fa-check-circle"></i>
                        <span id="selectedCount">0</span> attività selezionate
                    </span>
                    <button onclick="deleteSelected()" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i> Elimina selezionate
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th style="width: 40px" class="text-center">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th style="width: 80px">Codice</th>
                            <th>Titolo</th>
                            <th style="width: 150px">Progetto</th>
                            <th style="width: 100px">Stato</th>
                            <th style="width: 100px">Priorità</th>
                            <th style="width: 120px">Scadenza</th>
                            <th style="width: 80px" class="text-end">Ore</th>
                            <th style="width: 200px" class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $i => $task): ?>
                            <tr<?= $i === 0 ? ' data-intro="task-row"' : '' ?>>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input task-checkbox" value="<?= $task['id'] ?>">
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= esc($task['code']) ?></span>
                                </td>
                                <td>
                                    <a href="<?= url('/tasks/' . $task['id']) ?>" class="text-decoration-none">
                                        <?= esc($task['title']) ?>
                                    </a>
                                    <?php if ($task['description']): ?>
                                        <br><small class="text-muted"><?= truncate(esc($task['description']), 60) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($task['project_name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= status_badge_color($task['status']) ?>">
                                        <?= esc($task['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($task['priority']): ?>
                                        <span class="badge bg-<?= priority_badge_color($task['priority']) ?>">
                                            <?= esc($task['priority']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($task['due_at']): ?>
                                        <small><?= format_datetime($task['due_at'], 'd/m H:i') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <small><?= $task['hours_spent'] ?? 0 ?> / <?= $task['hours_estimated'] ?? '-' ?></small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm"<?= $i === 0 ? ' data-intro="quick-actions-task"' : '' ?>>
                                        <a href="<?= url('/tasks/' . $task['id']) ?>" class="btn btn-outline-primary" title="Visualizza">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= url('/tasks/' . $task['id'] . '/edit') ?>" class="btn btn-outline-secondary" title="Modifica">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <button onclick="toggleStatus(<?= $task['id'] ?>)" class="btn btn-outline-success" title="Cambia stato">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <?php if (is_admin()): ?>
                                            <button onclick="deleteTask(<?= $task['id'] ?>)" class="btn btn-outline-danger" title="Elimina">
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
        <?php endif; ?>
    </div>
</div>

<script>
// Debug: Check if functions are available
console.log('Tasks page script loading...');
console.log('showToast available:', typeof showToast !== 'undefined');

// Gestione selezione multipla
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - initializing task checkboxes');
    // Select all checkbox
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.task-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkActions();
        });
    }

    // Individual checkboxes
    const checkboxes = document.querySelectorAll('.task-checkbox');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });
});

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.task-checkbox:checked');
    const count = checkedBoxes.length;
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');

    if (count > 0) {
        bulkActions.classList.remove('d-none');
        selectedCount.textContent = count;
    } else {
        bulkActions.classList.add('d-none');
    }

    // Update select all checkbox
    const selectAll = document.getElementById('selectAll');
    const allBoxes = document.querySelectorAll('.task-checkbox');
    if (selectAll) {
        selectAll.checked = count === allBoxes.length && count > 0;
        selectAll.indeterminate = count > 0 && count < allBoxes.length;
    }
}

function deleteSelected() {
    console.log('deleteSelected() function called');

    const checkedBoxes = document.querySelectorAll('.task-checkbox:checked');
    const ids = Array.from(checkedBoxes).map(cb => cb.value);

    console.log('Checked boxes:', checkedBoxes.length);
    console.log('Selected IDs:', ids);

    if (ids.length === 0) {
        console.log('No tasks selected, showing error toast');
        showToast('Nessuna attività selezionata', 'error');
        return;
    }

    if (!confirm(`Eliminare definitivamente ${ids.length} attività? Questa azione non può essere annullata.`)) {
        return;
    }

    // Elimina ogni task selezionato
    let deleted = 0;
    const promises = ids.map(id =>
        fetch(`/tirocinio/beweb-app/public/tasks/${id}/delete`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'csrf_token=<?= csrf_token() ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) deleted++;
        })
        .catch(error => {
            console.error(`Error deleting task ${id}:`, error);
        })
    );

    Promise.all(promises).then(() => {
        if (deleted > 0) {
            showToast(`${deleted} attività eliminate con successo`, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Errore durante l\'eliminazione', 'error');
        }
    }).catch(error => {
        console.error('Error in bulk delete:', error);
        showToast('Errore durante l\'eliminazione', 'error');
    });
}

function toggleStatus(id) {
    // Create a modal to select the new status
    const modalHtml = `
        <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Cambia stato attività</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Seleziona il nuovo stato per l'attività:</p>
                        <div class="d-grid gap-2">
                            <button onclick="setStatus(${id}, 'Da fare')" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-circle text-secondary"></i> Da fare
                            </button>
                            <button onclick="setStatus(${id}, 'In corso')" class="btn btn-outline-primary" data-bs-dismiss="modal">
                                <i class="fas fa-spinner text-primary"></i> In corso
                            </button>
                            <button onclick="setStatus(${id}, 'In revisione')" class="btn btn-outline-warning" data-bs-dismiss="modal">
                                <i class="fas fa-eye text-warning"></i> In revisione
                            </button>
                            <button onclick="setStatus(${id}, 'Fatto')" class="btn btn-outline-success" data-bs-dismiss="modal">
                                <i class="fas fa-check-circle text-success"></i> Fatto
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if any
    const existingModal = document.getElementById('changeStatusModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
    modal.show();
}

function setStatus(id, newStatus) {
    // Now we update the status directly to the chosen value
    fetch(`/tirocinio/beweb-app/public/tasks/${id}/toggle-status`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= csrf_token() ?>&status=' + encodeURIComponent(newStatus)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Stato aggiornato a: ' + newStatus, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Errore', 'error');
        }
    })
    .catch(error => {
        console.error('Error toggling status:', error);
        showToast('Errore durante l\'aggiornamento', 'error');
    });
}

function deleteTask(id) {
    if (!confirm('Eliminare definitivamente questa attività? Questa azione non può essere annullata.')) return;

    fetch(`/tirocinio/beweb-app/public/tasks/${id}/delete`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= csrf_token() ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Attività eliminata', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Errore', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting task:', error);
        showToast('Errore durante l\'eliminazione', 'error');
    });
}

// Final debug: Confirm all functions are loaded
console.log('Tasks page script loaded completely');
console.log('deleteSelected function available:', typeof deleteSelected !== 'undefined');
console.log('toggleStatus function available:', typeof toggleStatus !== 'undefined');
console.log('deleteTask function available:', typeof deleteTask !== 'undefined');
</script>

<?php
$content = ob_get_clean();
$title = 'Attività - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
