<?php
$isEdit = isset($timeLog) && $timeLog;
$pageTitle = $isEdit ? 'Modifica registro ore' : 'Nuovo registro ore';
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
                <form method="POST" action="<?= $isEdit ? url('/timelogs/' . $timeLog['id']) : url('/timelogs') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?= $timeLog['date'] ?? date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="person" class="form-label">Persona <span class="text-danger">*</span></label>
                            <select class="form-select" id="person" name="person" required>
                                <?php foreach ($persons as $person): ?>
                                    <option value="<?= esc($person['value']) ?>"
                                        <?= ($timeLog['person'] ?? auth()['name']) === $person['value'] ? 'selected' : '' ?>>
                                        <?= esc($person['value']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="task_id" class="form-label">Attività collegata</label>
                        <select class="form-select" id="task_id" name="task_id">
                            <option value="">Nessuna (lavoro generico)</option>
                            <?php foreach ($tasks as $task): ?>
                                <option value="<?= $task['id'] ?>"
                                    <?= ($timeLog['task_id'] ?? '') == $task['id'] ? 'selected' : '' ?>>
                                    <?= esc($task['code'] . ' - ' . $task['title'] . ' (' . $task['project_name'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Opzionale: collega a un'attività specifica</div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Cosa hai fatto in questo blocco di lavoro?"><?= esc($timeLog['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="hours" class="form-label">Ore <span class="text-danger">*</span></label>
                            <input type="number" step="0.25" min="0.25" max="24" class="form-control form-control-lg"
                                   id="hours" name="hours" value="<?= $timeLog['hours'] ?? '' ?>"
                                   required placeholder="es: 2.5">
                            <div class="form-text">Formato decimale (es: 1.5 = 1h 30min)</div>
                        </div>
                        <div class="col-md-6">
                            <label for="blocked" class="form-label">Blocco?</label>
                            <select class="form-select" id="blocked" name="blocked">
                                <option value="No" <?= ($timeLog['blocked'] ?? 'No') === 'No' ? 'selected' : '' ?>>No</option>
                                <option value="Sì" <?= ($timeLog['blocked'] ?? '') === 'Sì' ? 'selected' : '' ?>>Sì</option>
                            </select>
                            <div class="form-text">Hai incontrato blocchi?</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="output_link" class="form-label">Link output</label>
                        <input type="url" class="form-control" id="output_link" name="output_link"
                               value="<?= esc($timeLog['output_link'] ?? '') ?>"
                               placeholder="https://...">
                        <div class="form-text">Link al file/risultato prodotto (opzionale)</div>
                    </div>

                    <div class="mb-4">
                        <label for="notes" class="form-label">Note</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"
                                  placeholder="Note aggiuntive o dettagli sui blocchi..."><?= esc($timeLog['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" accesskey="s">
                            <i class="fas fa-save"></i> <?= $isEdit ? 'Aggiorna' : 'Crea' ?> registro ore
                        </button>
                        <a href="<?= url('/timelogs') ?>" class="btn btn-outline-secondary btn-lg">
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
