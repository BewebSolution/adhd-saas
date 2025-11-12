<?php
$isEdit = isset($deliverable) && $deliverable;
$pageTitle = $isEdit ? 'Modifica consegna' : 'Nuova consegna';
ob_start();
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0"><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?>"></i> <?= $pageTitle ?></h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="<?= $isEdit ? url('/deliverables/' . $deliverable['id']) : url('/deliverables') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?= $deliverable['date'] ?? date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="project_id" class="form-label">Progetto <span class="text-danger">*</span></label>
                            <select class="form-select" id="project_id" name="project_id" required>
                                <option value="">Seleziona progetto...</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= $project['id'] ?>"
                                        <?= ($deliverable['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                        <?= esc($project['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Seleziona tipo...</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= esc($type['value']) ?>"
                                    <?= ($deliverable['type'] ?? '') === $type['value'] ? 'selected' : '' ?>>
                                    <?= esc($type['value']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Titolo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="title" name="title"
                               value="<?= esc($deliverable['title'] ?? '') ?>" required maxlength="255"
                               placeholder="es: Moodboard v1 - stile elegante">
                    </div>

                    <div class="mb-3">
                        <label for="link" class="form-label">Link</label>
                        <input type="url" class="form-control" id="link" name="link"
                               value="<?= esc($deliverable['link'] ?? '') ?>"
                               placeholder="https://...">
                        <div class="form-text">Link a Figma, Canva, Google Drive, etc.</div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Stato <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= esc($status['value']) ?>"
                                    <?= ($deliverable['status'] ?? 'In revisione') === $status['value'] ? 'selected' : '' ?>>
                                    <?= esc($status['value']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="notes" class="form-label">Note</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="Note, feedback, versione, etc."><?= esc($deliverable['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" accesskey="s">
                            <i class="fas fa-save"></i> <?= $isEdit ? 'Aggiorna' : 'Crea' ?> consegna
                        </button>
                        <a href="<?= url('/deliverables') ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-times-circle"></i> Annulla
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = $pageTitle . ' - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
