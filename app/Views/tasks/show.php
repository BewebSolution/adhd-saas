<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0">
            <span class="badge bg-secondary"><?= esc($task['code']) ?></span>
            <?= esc($task['title']) ?>
        </h1>
        <p class="text-muted mb-0">
            <i class="fas fa-folder"></i> <?= esc($project['name']) ?>
        </p>
    </div>
    <div class="col-auto">
        <a href="<?= url('/tasks/' . $task['id'] . '/edit') ?>" class="btn btn-outline-primary">
            <i class="fas fa-pencil-alt"></i> Modifica
        </a>
        <a href="<?= url('/tasks') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Torna all'elenco
        </a>
    </div>
</div>

<div class="row">
    <!-- Task Details -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Dettagli attività</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Data creazione:</strong>
                    </div>
                    <div class="col-md-9">
                        <?= format_date($task['date']) ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Stato:</strong>
                    </div>
                    <div class="col-md-9">
                        <span class="badge bg-<?= status_badge_color($task['status']) ?>">
                            <?= esc($task['status']) ?>
                        </span>
                    </div>
                </div>

                <?php if ($task['priority']): ?>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Priorità:</strong>
                        </div>
                        <div class="col-md-9">
                            <span class="badge bg-<?= priority_badge_color($task['priority']) ?>">
                                <?= esc($task['priority']) ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Assegnatario:</strong>
                    </div>
                    <div class="col-md-9">
                        <?= esc($task['assignee']) ?>
                    </div>
                </div>

                <?php if ($task['due_at']): ?>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Scadenza:</strong>
                        </div>
                        <div class="col-md-9">
                            <i class="fas fa-clock"></i> <?= format_datetime($task['due_at']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Ore:</strong>
                    </div>
                    <div class="col-md-9">
                        <strong><?= $task['hours_spent'] ?? 0 ?></strong> svolte
                        <?php if ($task['hours_estimated']): ?>
                            / <?= $task['hours_estimated'] ?> stimate
                            <?php
                            $percent = $task['hours_estimated'] > 0
                                ? min(100, ($task['hours_spent'] / $task['hours_estimated']) * 100)
                                : 0;
                            ?>
                            <div class="progress mt-2" style="height: 20px;">
                                <div class="progress-bar bg-<?= $percent >= 100 ? 'danger' : 'primary' ?>"
                                     style="width: <?= $percent ?>%">
                                    <?= round($percent) ?>%
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($task['description']): ?>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Descrizione:</strong>
                        </div>
                        <div class="col-md-9">
                            <?= nl2br(esc($task['description'])) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($task['link']): ?>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Link:</strong>
                        </div>
                        <div class="col-md-9">
                            <a href="<?= esc($task['link']) ?>" target="_blank" rel="noopener">
                                <?= truncate(esc($task['link']), 60) ?>
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($task['notes']): ?>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Note:</strong>
                        </div>
                        <div class="col-md-9">
                            <?= nl2br(esc($task['notes'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Time Logs -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Registro ore associato</h5>
            </div>
            <div class="card-body">
                <?php if (empty($timeLogs)): ?>
                    <p class="text-muted mb-0">Nessun time log registrato per questa attività</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Persona</th>
                                    <th>Descrizione</th>
                                    <th class="text-end">Ore</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($timeLogs as $log): ?>
                                    <tr>
                                        <td><?= format_date($log['date']) ?></td>
                                        <td><?= esc($log['person']) ?></td>
                                        <td><?= esc($log['description']) ?></td>
                                        <td class="text-end"><strong><?= $log['hours'] ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Azioni rapide</h5>
            </div>
            <div class="card-body">
                <button onclick="toggleStatus(<?= $task['id'] ?>)" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-sync"></i> Cambia stato
                </button>

                <button onclick="showAddHoursModal()" class="btn btn-success w-100 mb-2">
                    <i class="fas fa-plus-circle"></i> Aggiungi ore
                </button>

                <a href="<?= url('/timelogs/create?task=' . $task['id']) ?>" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-clock"></i> Nuovo time log
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Hours -->
<div class="modal fade" id="addHoursModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form onsubmit="return addHours(event)">
                <div class="modal-header">
                    <h5 class="modal-title">Aggiungi ore</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="mb-3">
                        <label for="hours" class="form-label">Ore <span class="text-danger">*</span></label>
                        <input type="number" step="0.25" min="0.25" max="24" class="form-control"
                               id="hours" name="hours" required placeholder="es: 2.5">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrizione breve</label>
                        <input type="text" class="form-control" id="description" name="description"
                               placeholder="Cosa hai fatto in queste ore?">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Aggiungi ore</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let addHoursModalInstance;

function showAddHoursModal() {
    const modal = document.getElementById('addHoursModal');
    addHoursModalInstance = new bootstrap.Modal(modal);
    addHoursModalInstance.show();
}

function addHours(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    fetch('/tasks/<?= $task['id'] ?>/add-hours', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            addHoursModalInstance.hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Errore', 'error');
        }
    });

    return false;
}

function toggleStatus(id) {
    if (!confirm('Cambiare lo stato di questa attività?')) return;

    fetch(`/tasks/${id}/toggle-status`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= csrf_token() ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Errore', 'error');
        }
    });
}
</script>

<?php
$content = ob_get_clean();
$title = esc($task['code']) . ' - ' . esc($task['title']) . ' - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
