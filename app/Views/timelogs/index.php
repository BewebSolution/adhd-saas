<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0"><i class="bi bi-clock-history"></i> Registro ore</h1>
    </div>
    <div class="col-auto">
        <a href="<?= url('/timelogs/create') ?>" class="btn btn-primary" accesskey="t">
            <i class="fas fa-plus-circle"></i> Nuovo registro ore
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/timelogs') ?>" class="row g-3">
            <div class="col-md-3">
                <label for="date_from" class="form-label">Dal</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?= esc($filters['date_from']) ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">Al</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="<?= esc($filters['date_to']) ?>">
            </div>
            <div class="col-md-3">
                <label for="person" class="form-label">Persona</label>
                <select name="person" id="person" class="form-select">
                    <option value="">Tutti</option>
                    <?php foreach ($persons as $person): ?>
                        <option value="<?= esc($person['value']) ?>" <?= ($filters['person'] === $person['value']) ? 'selected' : '' ?>>
                            <?= esc($person['value']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="task" class="form-label">Attività</label>
                <select name="task" id="task" class="form-select">
                    <option value="">Tutte</option>
                    <?php foreach ($tasks as $task): ?>
                        <option value="<?= $task['id'] ?>" <?= ($filters['task_id'] == $task['id']) ? 'selected' : '' ?>>
                            <?= esc($task['code'] . ' - ' . truncate($task['title'], 40)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtra</button>
                <a href="<?= url('/timelogs') ?>" class="btn btn-outline-secondary"><i class="fas fa-times-circle"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Time Logs List -->
<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($timeLogs)): ?>
            <p class="text-muted mb-0"><i class="fas fa-inbox"></i> Nessun registro ore trovato.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th style="width: 100px">Data</th>
                            <th style="width: 120px">Persona</th>
                            <th>Attività / Descrizione</th>
                            <th style="width: 100px" class="text-end">Ore</th>
                            <th style="width: 100px">Blocco</th>
                            <th style="width: 120px" class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeLogs as $log): ?>
                            <tr>
                                <td><?= format_date($log['date']) ?></td>
                                <td><?= esc($log['person']) ?></td>
                                <td>
                                    <?php if ($log['task_code']): ?>
                                        <span class="badge bg-secondary"><?= esc($log['task_code']) ?></span>
                                        <?= truncate(esc($log['task_title'] ?? ''), 30) ?><br>
                                    <?php endif; ?>
                                    <small class="text-muted"><?= esc($log['description']) ?></small>
                                </td>
                                <td class="text-end"><strong><?= $log['hours'] ?></strong></td>
                                <td>
                                    <?php if ($log['blocked'] === 'Sì'): ?>
                                        <span class="badge bg-danger">Sì</span>
                                    <?php else: ?>
                                        <span class="text-muted">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('/timelogs/' . $log['id'] . '/edit') ?>" class="btn btn-outline-secondary" title="Modifica">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <?php if (is_admin()): ?>
                                            <button onclick="deleteTimeLog(<?= $log['id'] ?>)" class="btn btn-outline-danger" title="Elimina">
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
function deleteTimeLog(id) {
    if (!confirm('Eliminare questo registro ore?')) return;

    fetch(`/timelogs/${id}/delete`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= csrf_token() ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Registro ore eliminato', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Errore', 'error');
        }
    });
}
</script>

<?php
$content = ob_get_clean();
$title = 'Registro ore - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
