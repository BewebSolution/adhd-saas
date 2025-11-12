<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0"><i class="bi bi-journal-text"></i> Note</h1>
    </div>
    <div class="col-auto">
        <a href="<?= url('/notes/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Nuova nota
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/notes') ?>" class="row g-3">
            <div class="col-md-4">
                <label for="owner" class="form-label">Responsabile</label>
                <select name="owner" id="owner" class="form-select">
                    <option value="">Tutti</option>
                    <?php foreach ($persons as $person): ?>
                        <option value="<?= esc($person['value']) ?>" <?= ($filters['owner'] === $person['value']) ? 'selected' : '' ?>>
                            <?= esc($person['value']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="search" class="form-label">Cerca</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Tema o nota..."
                       value="<?= esc($filters['search']) ?>">
            </div>
            <div class="col-md-4">
                <label for="due_date" class="form-label">Scadenza</label>
                <input type="date" name="due_date" id="due_date" class="form-control" value="<?= esc($filters['due_date']) ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtra</button>
                <a href="<?= url('/notes') ?>" class="btn btn-outline-secondary"><i class="fas fa-times-circle"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Notes List -->
<div class="row">
    <?php if (empty($notes)): ?>
        <div class="col-12">
            <p class="text-muted mb-0"><i class="fas fa-inbox"></i> Nessuna nota trovata.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notes as $note): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><?= esc($note['topic']) ?></h6>
                        <small class="text-muted"><?= format_date($note['date']) ?></small>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?= truncate(nl2br(esc($note['body'])), 150) ?></p>

                        <?php if ($note['next_action']): ?>
                            <p class="mb-2">
                                <i class="bi bi-check-square text-primary"></i>
                                <strong>Azione:</strong> <?= truncate(esc($note['next_action']), 60) ?>
                            </p>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-secondary"><?= esc($note['owner']) ?></span>
                            <?php if ($note['due_date']): ?>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> <?= format_date($note['due_date']) ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <?php if ($note['link']): ?>
                            <div class="mt-2">
                                <a href="<?= esc($note['link']) ?>" target="_blank" class="small">
                                    <i class="bi bi-link-45deg"></i> Apri link
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="btn-group btn-group-sm w-100">
                            <a href="<?= url('/notes/' . $note['id'] . '/edit') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-pencil-alt"></i> Modifica
                            </a>
                            <?php if (is_admin()): ?>
                                <button onclick="deleteNote(<?= $note['id'] ?>)" class="btn btn-outline-danger">
                                    <i class="fas fa-trash"></i> Elimina
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function deleteNote(id) {
    if (!confirm('Eliminare questa nota?')) return;

    fetch(`/notes/${id}/delete`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= csrf_token() ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Nota eliminata', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Errore', 'error');
        }
    });
}
</script>

<?php
$content = ob_get_clean();
$title = 'Note - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
