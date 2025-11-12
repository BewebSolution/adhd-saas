<?php
$isEdit = isset($note) && $note;
$pageTitle = $isEdit ? 'Modifica nota' : 'Nuova nota';
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
                <form method="POST" action="<?= $isEdit ? url('/notes/' . $note['id']) : url('/notes') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="mb-3">
                        <label for="date" class="form-label">Data <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date" name="date"
                               value="<?= $note['date'] ?? date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="topic" class="form-label">Tema <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="topic" name="topic"
                               value="<?= esc($note['topic'] ?? '') ?>" required maxlength="150"
                               placeholder="es: Daily Recap, Decisione design, Setup progetto...">
                    </div>

                    <div class="mb-3">
                        <label for="body" class="form-label">Nota / Decisione <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="body" name="body" rows="6" required
                                  placeholder="Scrivi qui la nota, decisione o recap..."><?= esc($note['body'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="next_action" class="form-label">Azione successiva</label>
                        <input type="text" class="form-control" id="next_action" name="next_action"
                               value="<?= esc($note['next_action'] ?? '') ?>" maxlength="255"
                               placeholder="es: Implementare feedback cliente, Completare task A-003...">
                        <div class="form-text">Cosa fare come follow-up (opzionale)</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="owner" class="form-label">Responsabile <span class="text-danger">*</span></label>
                            <select class="form-select" id="owner" name="owner" required>
                                <?php foreach ($persons as $person): ?>
                                    <option value="<?= esc($person['value']) ?>"
                                        <?= ($note['owner'] ?? auth()['name']) === $person['value'] ? 'selected' : '' ?>>
                                        <?= esc($person['value']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Scadenza azione</label>
                            <input type="date" class="form-control" id="due_date" name="due_date"
                                   value="<?= $note['due_date'] ?? '' ?>">
                            <div class="form-text">Opzionale</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="link" class="form-label">Link</label>
                        <input type="url" class="form-control" id="link" name="link"
                               value="<?= esc($note['link'] ?? '') ?>" placeholder="https://...">
                        <div class="form-text">Link di riferimento (opzionale)</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" accesskey="s">
                            <i class="fas fa-save"></i> <?= $isEdit ? 'Aggiorna' : 'Crea' ?> nota
                        </button>
                        <a href="<?= url('/notes') ?>" class="btn btn-outline-secondary btn-lg">
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
