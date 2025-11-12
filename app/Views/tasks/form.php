<?php
$isEdit = isset($task) && $task;
$pageTitle = $isEdit ? 'Modifica attività' : 'Nuova attività';
ob_start();
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0">
            <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?>"></i> <?= $pageTitle ?>
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="<?= $isEdit ? url('/tasks/' . $task['id']) : url('/tasks') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date" class="form-label">Data</label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?= $task['date'] ?? date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="code" class="form-label">Codice</label>
                            <input type="text" class="form-control" id="code" name="code"
                                   value="<?= esc($task['code'] ?? '') ?>"
                                   placeholder="Lascia vuoto per auto-generare (es: A-001)">
                            <div class="form-text">Opzionale - verrà generato automaticamente</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="project_id" class="form-label">Progetto <span class="text-danger">*</span></label>
                        <select class="form-select" id="project_id" name="project_id" required>
                            <option value="">Seleziona progetto...</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>"
                                    <?= ($task['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                    <?= esc($project['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Titolo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="title" name="title"
                               value="<?= esc($task['title'] ?? '') ?>" required maxlength="255"
                               placeholder="es: Setup repository progetto">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="description" name="description" rows="4"
                                  placeholder="Dettagli dell'attività..."><?= esc($task['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="priority" class="form-label">Priorità</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="">Nessuna</option>
                                <?php foreach ($priorities as $priority): ?>
                                    <option value="<?= esc($priority['value']) ?>"
                                        <?= ($task['priority'] ?? '') === $priority['value'] ? 'selected' : '' ?>>
                                        <?= esc($priority['value']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="status" class="form-label">Stato</label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= esc($status['value']) ?>"
                                        <?= ($task['status'] ?? 'Da fare') === $status['value'] ? 'selected' : '' ?>>
                                        <?= esc($status['value']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="assignee" class="form-label">Assegnatario</label>
                            <select class="form-select" id="assignee" name="assignee">
                                <?php foreach ($persons as $person): ?>
                                    <option value="<?= esc($person['value']) ?>"
                                        <?= ($task['assignee'] ?? auth()['name']) === $person['value'] ? 'selected' : '' ?>>
                                        <?= esc($person['value']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Data scadenza</label>
                            <input type="date" class="form-control" id="due_date" name="due_date"
                                   value="<?= $task ? date('Y-m-d', strtotime($task['due_at'] ?? 'now')) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="due_time" class="form-label">Ora scadenza</label>
                            <input type="time" class="form-control" id="due_time" name="due_time"
                                   value="<?= $task && $task['due_at'] ? date('H:i', strtotime($task['due_at'])) : '' ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="hours_estimated" class="form-label">Ore stimate</label>
                            <input type="number" step="0.25" min="0" max="999" class="form-control"
                                   id="hours_estimated" name="hours_estimated"
                                   value="<?= $task['hours_estimated'] ?? '' ?>"
                                   placeholder="es: 4.5">
                            <div class="form-text">In formato decimale (es: 1.5 = 1 ora e 30 min)</div>
                        </div>
                        <div class="col-md-6">
                            <label for="hours_spent" class="form-label">Ore svolte</label>
                            <input type="number" step="0.25" min="0" max="999" class="form-control"
                                   id="hours_spent" name="hours_spent"
                                   value="<?= $task['hours_spent'] ?? 0 ?>" readonly>
                            <div class="form-text">Aggiornato automaticamente dai time log</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="link" class="form-label">Link</label>
                        <input type="url" class="form-control" id="link" name="link"
                               value="<?= esc($task['link'] ?? '') ?>"
                               placeholder="https://...">
                        <div class="form-text">Es: link Canva, Google Doc, Figma, Trello...</div>
                    </div>

                    <div class="mb-4">
                        <label for="notes" class="form-label">Note</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="Note aggiuntive..."><?= esc($task['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" accesskey="s">
                            <i class="fas fa-save"></i> <?= $isEdit ? 'Aggiorna' : 'Crea' ?> attività
                        </button>
                        <a href="<?= $isEdit ? url('/tasks/' . $task['id']) : url('/tasks') ?>" class="btn btn-outline-secondary btn-lg">
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
