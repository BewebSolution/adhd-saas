<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0"><i class="bi bi-file-earmark-arrow-up"></i> Consegne</h1>
    </div>
    <div class="col-auto">
        <a href="<?= url('/deliverables/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Nuova consegna
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/deliverables') ?>" class="row g-3">
            <div class="col-md-4">
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
            <div class="col-md-4">
                <label for="type" class="form-label">Tipo</label>
                <select name="type" id="type" class="form-select">
                    <option value="">Tutti</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= esc($type['value']) ?>" <?= ($filters['type'] === $type['value']) ? 'selected' : '' ?>>
                            <?= esc($type['value']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
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
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtra</button>
                <a href="<?= url('/deliverables') ?>" class="btn btn-outline-secondary"><i class="fas fa-times-circle"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Deliverables List -->
<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($deliverables)): ?>
            <p class="text-muted mb-0"><i class="fas fa-inbox"></i> Nessuna consegna trovata.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>Data</th>
                            <th>Progetto</th>
                            <th>Tipo</th>
                            <th>Titolo</th>
                            <th>Stato</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deliverables as $deliverable): ?>
                            <tr>
                                <td><?= format_date($deliverable['date']) ?></td>
                                <td><?= esc($deliverable['project_name']) ?></td>
                                <td><span class="badge bg-info"><?= esc($deliverable['type']) ?></span></td>
                                <td>
                                    <?= esc($deliverable['title']) ?>
                                    <?php if ($deliverable['link']): ?>
                                        <br><a href="<?= esc($deliverable['link']) ?>" target="_blank" class="small">
                                            <i class="bi bi-link-45deg"></i> Apri link
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= status_badge_color($deliverable['status']) ?>">
                                        <?= esc($deliverable['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('/deliverables/' . $deliverable['id'] . '/edit') ?>" class="btn btn-outline-secondary">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <?php if (is_admin()): ?>
                                            <button onclick="deleteDeliverable(<?= $deliverable['id'] ?>)" class="btn btn-outline-danger">
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
function deleteDeliverable(id) {
    if (!confirm('Eliminare questa consegna?')) return;

    fetch(`/deliverables/${id}/delete`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= csrf_token() ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Consegna eliminata', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Errore', 'error');
        }
    });
}
</script>

<?php
$content = ob_get_clean();
$title = 'Consegne - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
