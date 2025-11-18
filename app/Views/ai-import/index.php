<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0">
            <i class="fas fa-robot"></i> AI Import Center
        </h1>
        <p class="text-muted">Importa e organizza automaticamente task da Google Tasks e Gmail</p>
    </div>
</div>

<!-- Nav tabs -->
<ul class="nav nav-tabs mb-4" id="importTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="google-tasks-tab" data-bs-toggle="tab" data-bs-target="#google-tasks" type="button">
            <i class="bi bi-check2-square"></i> Google Tasks
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button">
            <i class="fas fa-envelope"></i> Email Inbox
            <span class="badge bg-secondary ms-1">Prossimamente</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button">
            <i class="fas fa-cog"></i> Impostazioni
        </button>
    </li>
</ul>

<!-- Tab content -->
<div class="tab-content" id="importTabContent">

    <!-- Google Tasks Tab -->
    <div class="tab-pane fade show active" id="google-tasks" role="tabpanel">
        <!-- Connection Status -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <?php if ($isConnected): ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="bi bi-check-circle-fill text-success"></i> Google Tasks Connesso
                            </h5>
                            <p class="text-muted mb-0">Account: <?= esc(auth()['email']) ?></p>
                        </div>
                        <div>
                            <button onclick="syncGoogleTasks()" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-sync"></i> 🔄 SINCRONIZZA ORA
                            </button>
                            <button onclick="importAllWithAI()" class="btn btn-primary btn-lg me-2" id="syncWithAIBtn">
                                <i class="fas fa-magic"></i> 🤖 Importa con AI
                            </button>
                            <button onclick="disconnectGoogle()" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-times-circle"></i> Disconnetti
                            </button>
                        </div>
                    </div>

                    <!-- Info text for AI button -->
                    <p class="text-muted mt-3">
                        <small><i class="fas fa-info-circle"></i> L'AI mapperà automaticamente le liste ai progetti e arricchirà i dati dei task</small>
                    </p>
                <?php else: ?>
                    <div class="text-center py-4">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-link-45deg"></i> Connetti Google Tasks
                        </h5>
                        <p class="text-muted mb-4">
                            Collega il tuo account Google per importare automaticamente i task
                        </p>
                        <a href="<?= esc($authUrl) ?>" class="btn btn-primary btn-lg">
                            <i class="fab fa-google"></i> 🔗 Connetti Google Tasks
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isConnected): ?>
            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h3 class="text-primary"><?= $stats['total_imported'] ?></h3>
                            <p class="text-muted mb-0">Task Totali Importati</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h3 class="text-success"><?= $stats['today_imported'] ?></h3>
                            <p class="text-muted mb-0">Importati Oggi</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <h5 class="text-info">
                                <?php if ($stats['last_sync']): ?>
                                    <?= format_datetime($stats['last_sync'], 'd/m H:i') ?>
                                <?php else: ?>
                                    Mai sincronizzato
                                <?php endif; ?>
                            </h5>
                            <p class="text-muted mb-0">Ultima Sincronizzazione</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sync Results (hidden by default) -->
            <div id="syncResults" class="card shadow-sm mb-4" style="display: none;">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-list-check"></i> Task Processati</h5>
                </div>
                <div class="card-body">
                    <div id="syncResultsContent">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
                <!-- Footer rimosso - i pulsanti vengono aggiunti dinamicamente via JavaScript -->
            </div>

            <!-- Auto-mapping info (replaced manual mapping) -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-magic"></i> Mappatura Automatica</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Mappatura automatica attiva!</strong><br>
                        Il sistema crea automaticamente i progetti basandosi sui nomi delle liste Google Tasks:
                        <ul class="mb-0 mt-2">
                            <li>Lista "AMEVISTA" → Progetto "AMEVISTA"</li>
                            <li>Lista "BEWEB AGENCY" → Progetto "BEWEB AGENCY"</li>
                            <li>Se il progetto esiste già, i task vengono aggiunti</li>
                            <li>Se non esiste, viene creato automaticamente</li>
                        </ul>
                    </div>

                    <!-- Show existing mappings for reference only -->
                    <?php if (!empty($mappings)): ?>
                    <div style="display: none;">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Lista Google Tasks</th>
                                        <th>→</th>
                                        <th>Progetto App</th>
                                        <th>Azione</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mappings as $mapping): ?>
                                        <tr>
                                            <td><?= esc($mapping['google_list_name']) ?></td>
                                            <td>→</td>
                                            <td>
                                                <?php if ($mapping['action'] === 'ignore'): ?>
                                                    <span class="badge bg-secondary">🚫 Ignora sempre</span>
                                                <?php elseif ($mapping['project_name']): ?>
                                                    <span class="badge bg-success"><?= esc($mapping['project_name']) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">⚠️ Non mappato</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button onclick="editMapping('<?= esc($mapping['google_list_id']) ?>', '<?= esc($mapping['google_list_name']) ?>', <?= $mapping['project_id'] ?? 'null' ?>)" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-pencil-alt"></i> Modifica
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Email Tab -->
    <div class="tab-pane fade" id="email" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-envelope" style="font-size: 3rem;" class="text-muted"></i>
                <h4 class="mt-3">Email Import</h4>
                <p class="text-muted">
                    Funzionalità in arrivo! Presto potrai importare task direttamente dalle tue email Gmail.
                </p>
                <button class="btn btn-secondary" disabled>
                    <i class="fas fa-clock"></i> Prossimamente
                </button>
            </div>
        </div>
    </div>

    <!-- Settings Tab -->
    <div class="tab-pane fade" id="settings" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-4">
                    <i class="fas fa-cog"></i> Impostazioni Import
                </h5>

                <form method="POST" action="<?= url('/ai/import/settings') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="auto_sync" id="auto_sync"
                                   <?= $settings['auto_sync'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="auto_sync">
                                <strong>Sincronizzazione automatica giornaliera</strong>
                                <br><small class="text-muted">Importa automaticamente i task ogni giorno</small>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="sync_time" class="form-label">Orario sincronizzazione</label>
                        <input type="time" name="sync_time" id="sync_time" class="form-control"
                               value="<?= esc($settings['sync_time']) ?>">
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="delete_after_import" id="delete_after_import"
                                   <?= $settings['delete_after_import'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="delete_after_import">
                                <strong>Elimina da Google dopo import</strong>
                                <br><small class="text-muted">Rimuove i task da Google Tasks dopo averli importati</small>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ignore_personal" id="ignore_personal"
                                   <?= $settings['ignore_personal'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ignore_personal">
                                <strong>Ignora task personali</strong>
                                <br><small class="text-muted">Non importa task che sembrano personali (spesa, famiglia, etc.)</small>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salva Impostazioni
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Mapping Modal -->
<div class="modal fade" id="mappingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifica Mappatura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="mappingForm">
                    <input type="hidden" id="mapping_list_id">
                    <input type="hidden" id="mapping_list_name">

                    <div class="mb-3">
                        <label class="form-label">Lista Google Tasks</label>
                        <input type="text" id="mapping_list_display" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Azione</label>
                        <select id="mapping_action" class="form-select" onchange="toggleProjectSelect()">
                            <option value="import">Importa in progetto</option>
                            <option value="ignore">Ignora sempre</option>
                        </select>
                    </div>

                    <div class="mb-3" id="project_select_div">
                        <label class="form-label">Progetto destinazione</label>
                        <select id="mapping_project_id" class="form-select">
                            <option value="">Seleziona progetto...</option>
                            <?php
                            $projects = (new \App\Models\Project())->all();
                            foreach ($projects as $project):
                            ?>
                                <option value="<?= $project['id'] ?>"><?= esc($project['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" onclick="saveMapping()" class="btn btn-primary">Salva</button>
            </div>
        </div>
    </div>
</div>

<!-- AI Mapping Modal -->
<div class="modal fade" id="aiMappingModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-magic"></i> Anteprima Mapping AI
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Loader -->
                <div id="aiMappingLoader" class="text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-3">L'AI sta analizzando le liste e i progetti...</p>
                    <small class="text-muted">Questo richiederà alcuni secondi</small>
                </div>

                <!-- Contenuto Mapping -->
                <div id="aiMappingContent" style="display: none;">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Verifica i mapping suggeriti dall'AI</strong><br>
                        Puoi modificare l'assegnazione dei progetti prima di confermare l'importazione.
                    </div>

                    <div id="mappingsList"></div>

                    <!-- Opzioni avanzate -->
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">⚙️ Opzioni Avanzate</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="enrichWithAI"
                                    checked
                                >
                                <label class="form-check-label" for="enrichWithAI">
                                    <strong>Arricchisci task con AI</strong>
                                    <br>
                                    <small class="text-muted">
                                        L'AI analizzerà ogni task per:
                                        <ul class="mb-0">
                                            <li>Pulire e migliorare i titoli</li>
                                            <li>Aggiungere descrizioni dettagliate</li>
                                            <li>Assegnare priorità intelligenti</li>
                                            <li>Stimare le ore necessarie</li>
                                            <li>Assegnare al membro team più adatto</li>
                                        </ul>
                                    </small>
                                </label>
                            </div>

                            <div class="form-check form-switch mt-3">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="deleteAfterImportAI"
                                >
                                <label class="form-check-label" for="deleteAfterImportAI">
                                    <strong>Elimina da Google Tasks dopo import</strong>
                                    <br>
                                    <small class="text-muted">
                                        Rimuove i task importati da Google Tasks
                                    </small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Riepilogo -->
                    <div class="alert alert-success mt-4">
                        <h6>📊 Riepilogo Import</h6>
                        <div id="importSummary"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Annulla
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    id="confirmImportBtn"
                    onclick="confirmAIImport()"
                    style="display: none;"
                >
                    <i class="bi bi-check-circle"></i> Conferma e Importa
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// On page load, check for saved AI tasks
window.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($savedAITasks)): ?>
        // Show saved AI-processed tasks
        const savedTasks = <?= json_encode($savedAITasks) ?>;
        displaySyncResults(savedTasks);
        showAIImportButton();
        showToast('Task processati con AI caricati dalla sessione', 'info');
    <?php endif; ?>
});

// Phase 1: Fast sync (RAW data only)
function syncGoogleTasks() {
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sincronizzazione veloce...';

    fetch(url('/ai/import/sync'), {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= csrf_token() ?>'
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;

        if (data.success) {
            // Show RAW data with AI processing button
            displayRawSyncResults(data.data);
            showToast(data.message + ' (Pronto per processing AI)', 'success');

            // Show AI processing button if needed
            if (data.ai_processing_required) {
                showAIProcessingButton();
            }
        } else {
            showToast(data.error || 'Errore durante la sincronizzazione', 'error');
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        showToast('Errore di connessione', 'error');
        console.error('Sync error:', error);
    });
}

// Import ALL tasks with AI (no selection needed)
async function importAllWithAI() {
    // First sync to get latest tasks
    const syncBtn = event.target;
    const originalHtml = syncBtn.innerHTML;
    syncBtn.disabled = true;
    syncBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sincronizzando...';

    try {
        // Step 1: Sync tasks from Google
        const syncResponse = await fetch(url('/ai/import/sync'), {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'csrf_token=<?= csrf_token() ?>'
        });

        const syncData = await syncResponse.json();

        if (!syncData.success) {
            throw new Error(syncData.error || 'Errore durante la sincronizzazione');
        }

        showToast(`Sincronizzati ${syncData.data.total_tasks} task. Processamento AI in corso...`, 'info');

        // Step 2: Process ALL with AI
        syncBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>🤖 Processando con AI...';

        const aiResponse = await fetch(url('/ai/import/process-with-ai'), {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'csrf_token=<?= csrf_token() ?>'
        });

        const aiData = await aiResponse.json();

        if (aiData.success) {
            displaySyncResults(aiData.data);
            showToast(`✅ ${aiData.data.processed} task processati con AI`, 'success');
        } else {
            throw new Error(aiData.error || 'Errore durante il processamento AI');
        }

    } catch (error) {
        console.error('Error:', error);
        showToast(error.message, 'error');
    } finally {
        syncBtn.disabled = false;
        syncBtn.innerHTML = originalHtml;
    }
}

// NEW: Phase 2 - Process SELECTED tasks with AI
function processWithAI() {
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;

    // Get selected tasks
    const checkboxes = document.querySelectorAll('#syncResultsContent input[type="checkbox"]:checked:not(#delete_after_import)');

    if (checkboxes.length === 0) {
        showToast('Seleziona almeno un task da processare con AI', 'warning');
        return;
    }

    const selectedTasks = [];
    checkboxes.forEach(cb => {
        try {
            const taskData = JSON.parse(cb.dataset.task || '{}');
            if (taskData.id) {
                selectedTasks.push(taskData);
            }
        } catch (e) {
            console.error('Error parsing task data:', e);
        }
    });

    if (selectedTasks.length === 0) {
        showToast('Nessun task valido selezionato', 'error');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>🤖 Processing ${selectedTasks.length} task con AI...`;

    showToast(`Processamento AI di ${selectedTasks.length} task in corso...`, 'info');

    fetch(url('/ai/import/process-selected-with-ai'), {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= csrf_token() ?>&tasks=' + encodeURIComponent(JSON.stringify(selectedTasks))
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;

        if (data.success) {
            // Show processed data with import options
            displaySyncResults(data.data);
            showToast(data.message, 'success');
            // Hide AI processing button
            hideAIProcessingButton();
            // Show import button for AI-processed tasks
            showAIImportButton();
        } else {
            showToast(data.error || 'Errore durante il processamento AI', 'error');
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        showToast('Errore di connessione', 'error');
        console.error('AI Processing error:', error);
    });
}

// NEW: Display RAW sync results (without AI processing)
function displayRawSyncResults(data) {
    const resultsDiv = document.getElementById('syncResults');
    const contentDiv = document.getElementById('syncResultsContent');

    let html = '<div class="alert alert-info mb-3">';
    html += '<i class="fas fa-info-circle"></i> <strong>Sincronizzazione completata!</strong><br>';
    html += `Trovati ${data.total_tasks} task in ${data.total_lists} liste.<br>`;
    html += '<strong>Opzioni:</strong> Puoi importarli direttamente o processarli prima con AI per arricchirli.';
    html += '</div>';

    // Selection controls at the top
    html += `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="btn-group" role="group">
                <button class="btn btn-sm btn-outline-primary" onclick="selectAll(true)">
                    <i class="bi bi-check-square"></i> Seleziona tutti
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="selectAll(false)">
                    <i class="far fa-square"></i> Deseleziona tutti
                </button>
            </div>
            <div>
                <small class="text-muted">
                    <span id="selectedCount">0</span> task selezionati
                </small>
            </div>
        </div>
    `;

    html += '<div class="accordion" id="syncAccordion">';

    data.lists.forEach((list, index) => {
        if (list.status === 'ignored' || list.task_count === 0) return;

        html += `
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button ${index > 0 ? 'collapsed' : ''}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#list${index}">
                        <strong>${list.name}</strong> &nbsp;
                        <span class="badge bg-primary ms-2">${list.task_count} task</span>
                    </button>
                </h2>
                <div id="list${index}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}"
                     data-bs-parent="#syncAccordion">
                    <div class="accordion-body">
        `;

        // Show all tasks with checkboxes
        list.tasks.forEach((task, taskIndex) => {
            const taskId = `task_${index}_${taskIndex}`;
            // For direct import, send simple task data
            const taskData = {
                id: task.id,
                title: task.title || 'Senza titolo',
                notes: task.notes || '',
                due: task.due || null,
                status: task.status,
                list_id: list.id,
                list_name: list.name
            };

            html += `
                <div class="card mb-2">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input task-checkbox" type="checkbox" id="${taskId}"
                                   value='${JSON.stringify(taskData)}' checked
                                   onchange="updateSelectedCount()">
                            <label class="form-check-label" for="${taskId}">
                                <strong>${task.title || 'Task senza titolo'}</strong>
                            </label>
                        </div>
                        ${task.notes ? `
                            <div class="ms-4 mt-1">
                                <small class="text-muted">Note: ${task.notes}</small>
                            </div>
                        ` : ''}
                        ${task.due ? `
                            <div class="ms-4">
                                <small class="text-muted">Scadenza: ${new Date(task.due).toLocaleDateString('it-IT')}</small>
                            </div>
                        ` : ''}
                        ${task.status === 'completed' ? `
                            <span class="badge bg-success ms-4">Completato in Google</span>
                        ` : ''}
                    </div>
                </div>
            `;
        });

        html += '</div></div></div>';
    });

    html += '</div>';

    contentDiv.innerHTML = html;
    resultsDiv.style.display = 'block';

    // Initialize selected count
    updateSelectedCount();
}

// Helper: Show import options buttons
function showAIProcessingButton() {
    const existingBtn = document.getElementById('aiProcessBtn');
    if (!existingBtn) {
        const syncResultsContent = document.getElementById('syncResultsContent');
        if (!syncResultsContent) return;

        const btnHtml = `
            <div id="aiProcessBtn" class="card-footer">
                <div class="row align-items-center mb-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="delete_after_import">
                            <label class="form-check-label" for="delete_after_import">
                                <i class="fas fa-trash"></i> Elimina da Google Tasks dopo l'import
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="text-muted me-3">
                            <small><span id="selectedCountBottom">0</span> task selezionati</small>
                        </span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 text-center mb-3">
                        <button onclick="importSelected(event); return false;" class="btn btn-success btn-lg w-100" type="button">
                            <i class="fas fa-download"></i> Importa Direttamente
                        </button>
                        <p class="text-muted mt-2 mb-0">
                            <small>Importa i task così come sono (veloce)</small>
                        </p>
                    </div>
                    <div class="col-md-6 text-center mb-3">
                        <button onclick="processWithAI()" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-magic"></i> Processa con AI
                        </button>
                        <p class="text-muted mt-2 mb-0">
                            <small>Arricchisci con priorità e stime (30-60 sec)</small>
                        </p>
                    </div>
                </div>
            </div>
        `;

        // Insert after the card-body that contains syncResultsContent
        const cardBody = syncResultsContent.closest('.card-body');
        if (cardBody) {
            cardBody.insertAdjacentHTML('afterend', btnHtml);
        }
    }

    // Update selected count
    updateSelectedCount();
}

// Helper: Hide AI processing button
function hideAIProcessingButton() {
    const btn = document.getElementById('aiProcessBtn');
    if (btn) {
        btn.remove();
    }
}

// Helper: Show import button for AI-processed tasks
function showAIImportButton() {
    // Remove any existing action buttons first
    const existingBtn = document.getElementById('aiProcessBtn');
    if (existingBtn) {
        existingBtn.remove();
    }

    const syncResultsContent = document.getElementById('syncResultsContent');
    if (!syncResultsContent) return;

    const btnHtml = `
        <div id="aiProcessBtn" class="card-footer">
            <div class="row align-items-center mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="delete_after_import">
                        <label class="form-check-label" for="delete_after_import">
                            <i class="fas fa-trash"></i> Elimina da Google Tasks dopo l'import
                        </label>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-info">
                        <i class="fas fa-magic"></i> Task processati con AI
                    </span>
                </div>
            </div>
            <div class="text-center">
                <button class="btn btn-success btn-lg px-5" onclick="importAIProcessed(event); return false;" type="button">
                    <i class="bi bi-check-circle"></i> Importa Task Arricchiti
                </button>
                <p class="text-muted mt-2 mb-0">
                    <small>Importa i task con le modifiche suggerite dall'AI</small>
                </p>
            </div>
        </div>
    `;

    // Insert after the card-body that contains syncResultsContent
    const cardBody = syncResultsContent.closest('.card-body');
    if (cardBody) {
        cardBody.insertAdjacentHTML('afterend', btnHtml);
    }

    // Update selected count
    updateSelectedCount();
}

// NEW: Sync with AI
async function syncWithAI() {
    const modal = new bootstrap.Modal(document.getElementById('aiMappingModal'));
    modal.show();

    // Reset UI
    document.getElementById('aiMappingLoader').style.display = 'block';
    document.getElementById('aiMappingContent').style.display = 'none';
    document.getElementById('confirmImportBtn').style.display = 'none';

    try {
        const response = await fetch('/ai/import/sync-with-ai', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'csrf_token=' + document.querySelector('input[name="csrf_token"]').value
        });

        const data = await response.json();

        if (data.success) {
            displayAIMapping(data.data);
            document.getElementById('aiMappingLoader').style.display = 'none';
            document.getElementById('aiMappingContent').style.display = 'block';
            document.getElementById('confirmImportBtn').style.display = 'inline-block';
        } else {
            showToast(data.error || 'Errore durante il mapping AI', 'error');
            modal.hide();
        }
    } catch (error) {
        console.error('Sync error:', error);
        showToast('Errore di connessione', 'error');
        modal.hide();
    }
}

// Display AI mapping
function displayAIMapping(data) {
    const container = document.getElementById('mappingsList');
    let html = '';

    data.lists.forEach((list, index) => {
        const confidence = Math.round(list.mapping_confidence * 100);
        const confidenceClass = confidence > 70 ? 'success' : (confidence > 40 ? 'warning' : 'danger');

        html += `
        <div class="card mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h6 class="mb-1">${list.name}</h6>
                        <small class="text-muted">
                            ${list.task_count} task
                            ${list.tasks_preview.length > 0 ? '<br>Preview: ' + list.tasks_preview[0].title.substring(0, 50) + '...' : ''}
                        </small>
                    </div>
                    <div class="col-md-1 text-center">
                        <i class="bi fas fa-arrow-right fs-4"></i>
                    </div>
                    <div class="col-md-4">
                        <select
                            class="form-select project-mapping"
                            data-list-id="${list.id}"
                            data-list-name="${list.name}"
                        >
                            <option value="">-- Non importare --</option>
                            ${data.projects.map(p => `
                                <option
                                    value="${p.id}"
                                    ${list.mapped_project_id == p.id ? 'selected' : ''}
                                >
                                    ${p.name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="col-md-3">
                        ${list.ai_suggested ? `
                            <span class="badge bg-${confidenceClass}">
                                AI: ${confidence}% sicuro
                            </span>
                            <br>
                            <small class="text-muted">${list.mapping_reason}</small>
                        ` : `
                            <span class="badge bg-secondary">
                                Mapping manuale
                            </span>
                        `}
                    </div>
                </div>
            </div>
        </div>
        `;
    });

    container.innerHTML = html;

    // Update summary
    updateImportSummary(data);
}

// Update import summary
function updateImportSummary(data) {
    const mapped = data.lists.filter(l => l.mapped_project_id).length;
    const total = data.lists.length;

    document.getElementById('importSummary').innerHTML = `
        <ul class="mb-0">
            <li><strong>${data.total_tasks}</strong> task totali da importare</li>
            <li><strong>${mapped}/${total}</strong> liste mappate a progetti</li>
            <li>L'AI ha suggerito mapping con confidenza media del <strong>${
                Math.round(data.lists.reduce((acc, l) => acc + l.mapping_confidence, 0) / total * 100)
            }%</strong></li>
        </ul>
    `;
}

// Confirm AI import
async function confirmAIImport() {
    const btn = document.getElementById('confirmImportBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Importazione in corso...';

    // Collect confirmed mappings
    const mappings = [];
    document.querySelectorAll('.project-mapping').forEach(select => {
        if (select.value) {
            mappings.push({
                list_id: select.dataset.listId,
                list_name: select.dataset.listName,
                project_id: select.value,
                project_name: select.options[select.selectedIndex].text.trim()
            });
        }
    });

    if (mappings.length === 0) {
        showToast('Seleziona almeno una lista da importare', 'warning');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Conferma e Importa';
        return;
    }

    const enrichWithAI = document.getElementById('enrichWithAI').checked;
    const deleteAfterImport = document.getElementById('deleteAfterImportAI').checked;

    try {
        const response = await fetch('/ai/import/confirm-ai-import', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                csrf_token: document.querySelector('input[name="csrf_token"]').value,
                confirmed_mappings: JSON.stringify(mappings),
                enrich_with_ai: enrichWithAI ? '1' : '0',
                delete_after_import: deleteAfterImport ? '1' : '0'
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast(`✅ ${data.data.imported} task importati con successo!`, 'success');

            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('aiMappingModal')).hide();

            // Reload page
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showToast(data.error || 'Errore durante l\'importazione', 'error');
        }
    } catch (error) {
        console.error('Import error:', error);
        showToast('Errore durante l\'importazione', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Conferma e Importa';
    }
}

// Other existing functions...
function displaySyncResults(data) {
    const resultsDiv = document.getElementById('syncResults');
    const contentDiv = document.getElementById('syncResultsContent');

    // Add selection controls at the top for AI-processed results
    let html = `
        <div class="alert alert-success mb-3">
            <i class="fas fa-magic"></i> <strong>Task processati con AI!</strong><br>
            L'AI ha arricchito i task con priorità, stime e descrizioni migliorate.
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="btn-group" role="group">
                <button class="btn btn-sm btn-outline-primary" onclick="selectAll(true)">
                    <i class="bi bi-check-square"></i> Seleziona tutti
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="selectAll(false)">
                    <i class="far fa-square"></i> Deseleziona tutti
                </button>
            </div>
            <div>
                <small class="text-muted">
                    <span id="selectedCount">0</span> task selezionati
                </small>
            </div>
        </div>
    `;

    html += '<div class="accordion" id="syncAccordion">';

    data.lists.forEach((list, index) => {
        if (list.status === 'ignored' || list.tasks.length === 0) return;

        html += `
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button ${index > 0 ? 'collapsed' : ''}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#list${index}">
                        <strong>${list.name}</strong> &nbsp;
                        <span class="badge bg-primary ms-2">${list.tasks.length} task</span>
                    </button>
                </h2>
                <div id="list${index}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}"
                     data-bs-parent="#syncAccordion">
                    <div class="accordion-body">
        `;

        list.tasks.forEach((task, taskIndex) => {
            const taskId = `task_${index}_${taskIndex}`;
            const isDuplicate = task.status === 'duplicate';

            html += `
                <div class="card mb-3 ${isDuplicate ? 'border-warning' : ''}">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input task-checkbox" type="checkbox" id="${taskId}"
                                   value='${JSON.stringify(task)}'
                                   ${isDuplicate ? 'disabled' : 'checked'}
                                   onchange="updateSelectedCount()">
                            <label class="form-check-label" for="${taskId}">
                                <strong>${task.processed.clean_title}</strong>
                                ${isDuplicate ? '<span class="badge bg-warning ms-2">Duplicato</span>' : ''}
                            </label>
                        </div>

                        <div class="mt-2 ms-4">
                            <small class="text-muted">
                                Originale: "${task.original.title}"
                            </small><br>
                            ${task.processed.description ? `<small>📝 ${task.processed.description}</small><br>` : ''}
                            <span class="badge bg-${task.processed.priority === 'Alta' ? 'danger' :
                                                   task.processed.priority === 'Media' ? 'warning' : 'secondary'}">
                                ${task.processed.priority}
                            </span>
                            ${task.processed.suggested_deadline ?
                              `<span class="badge bg-info ms-1">📅 ${task.processed.suggested_deadline}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div></div></div>';
    });

    html += '</div>';

    contentDiv.innerHTML = html;
    resultsDiv.style.display = 'block';

    // Initialize selected count
    updateSelectedCount();
}

function importSelected(event) {
    // Prevent any default action
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    console.log('ImportSelected - Starting import process');

    // Get only task checkboxes, not the delete_after_import checkbox
    const checkboxes = document.querySelectorAll('#syncResultsContent input[type="checkbox"]:checked:not(#delete_after_import)');
    console.log('ImportSelected - Found checked checkboxes:', checkboxes.length);

    const tasks = [];

    checkboxes.forEach((cb, index) => {
        try {
            const taskData = JSON.parse(cb.value);
            console.log(`ImportSelected - Task ${index}:`, taskData);
            tasks.push(taskData);
        } catch (e) {
            console.error('Error parsing task data for checkbox:', cb, e);
        }
    });

    console.log('ImportSelected - Total tasks to import:', tasks.length);
    console.log('ImportSelected - Tasks data:', tasks);

    if (tasks.length === 0) {
        showToast('Seleziona almeno un task da importare', 'warning');
        return;
    }

    const deleteAfterImport = document.getElementById('delete_after_import')?.checked || false;
    const btn = event.target?.closest('button');
    let originalHtml = '';

    if (btn) {
        originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importazione in corso...';
    }

    // Use the new import-direct endpoint for direct import without AI
    fetch(url('/ai/import/import-direct'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `csrf_token=<?= csrf_token() ?>&tasks=${encodeURIComponent(JSON.stringify(tasks))}&delete_after_import=${deleteAfterImport ? '1' : '0'}`
    })
    .then(response => {
        console.log('ImportSelected - Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('ImportSelected - Response data:', data);

        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }

        if (data.success) {
            console.log('ImportSelected - Import successful:', data.imported, 'tasks');
            showToast(`✅ ${data.message || data.imported + ' task importati con successo!'}`, 'success');

            // Hide sync results after successful import (only if actually imported something)
            if (data.imported > 0) {
                const syncResultsCard = document.getElementById('syncResultsCard');
                if (syncResultsCard) {
                    setTimeout(() => {
                        syncResultsCard.style.display = 'none';
                        document.getElementById('syncResultsContent').innerHTML = '';
                    }, 3000);
                }
            }

            // Don't reload the page - let user see the results
        } else {
            showToast(data.error || 'Errore durante l\'importazione', 'error');
        }
    })
    .catch(error => {
        console.error('ImportSelected - Fetch error:', error);
        console.error('ImportSelected - Error details:', {
            message: error.message,
            stack: error.stack
        });

        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }

        showToast('Errore di connessione: ' + error.message, 'error');
    });
}

function selectAll(checked) {
    document.querySelectorAll('#syncResultsContent input[type="checkbox"]:not(:disabled):not(#delete_after_import)').forEach(cb => {
        cb.checked = checked;
    });
    updateSelectedCount();
}

// Update the count of selected tasks
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('#syncResultsContent input[type="checkbox"]:not(#delete_after_import)');
    const checkedBoxes = document.querySelectorAll('#syncResultsContent input[type="checkbox"]:checked:not(#delete_after_import)');

    const count = checkedBoxes.length;
    const total = checkboxes.length;

    // Update all counters
    const countElement = document.getElementById('selectedCount');
    if (countElement) {
        countElement.textContent = `${count}/${total}`;
    }

    const countElementBottom = document.getElementById('selectedCountBottom');
    if (countElementBottom) {
        countElementBottom.textContent = `${count}/${total}`;
    }
}

// Import AI-processed tasks
function importAIProcessed(event) {
    // Prevent any default action
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    // Get selected task checkboxes (exclude the delete_after_import checkbox)
    const checkboxes = document.querySelectorAll('#syncResultsContent input[type="checkbox"]:checked:not(#delete_after_import)');
    const tasks = [];
    let missingProjectCount = 0;

    checkboxes.forEach(cb => {
        try {
            const taskData = JSON.parse(cb.value);
            // Check if project_id is missing
            if (!taskData.project_id) {
                missingProjectCount++;
                console.warn('Task senza progetto:', taskData);
            }
            // These tasks are already processed with AI
            tasks.push(taskData);
        } catch (e) {
            console.error('Error parsing task data:', e);
        }
    });

    if (tasks.length === 0) {
        showToast('Seleziona almeno un task da importare', 'warning');
        return;
    }

    // Warn if some tasks don't have projects
    if (missingProjectCount > 0) {
        if (!confirm(`⚠️ ATTENZIONE: ${missingProjectCount} task non hanno un progetto associato e verranno saltati.\n\nVuoi continuare comunque?\n\nSuggerimento: Configura la mappatura Liste → Progetti prima di processare con AI.`)) {
            return;
        }
    }

    const deleteAfterImport = document.getElementById('delete_after_import')?.checked || false;
    const btn = event.target?.closest('button');
    let originalHtml = '';

    if (btn) {
        originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importazione in corso...';
    }

    // Use the standard import endpoint (tasks are already AI-processed)
    fetch(url('/ai/import/import'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `csrf_token=<?= csrf_token() ?>&tasks=${encodeURIComponent(JSON.stringify(tasks))}&delete_after_import=${deleteAfterImport ? '1' : '0'}`
    })
    .then(response => response.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }

        if (data.success) {
            showToast(`✅ ${data.message || data.imported + ' task importati con successo (con AI)!'}`, 'success');

            // Hide sync results after successful import
            const syncResultsCard = document.getElementById('syncResultsCard');
            if (syncResultsCard) {
                setTimeout(() => {
                    syncResultsCard.style.display = 'none';
                    document.getElementById('syncResultsContent').innerHTML = '';
                }, 2000);
            }

            // Don't reload - let user see the results
        } else {
            showToast(data.error || 'Errore durante l\'importazione', 'error');
            if (data.errors && data.errors.length > 0) {
                console.error('Import errors:', data.errors);
            }
        }
    })
    .catch(error => {
        console.error('Import error:', error);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        showToast('Errore di connessione durante l\'importazione', 'error');
    });
}

function disconnectGoogle() {
    if (!confirm('Sei sicuro di voler disconnettere Google Tasks?')) return;

    fetch('/ai/import/disconnect', {
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

function editMapping(listId, listName, projectId) {
    document.getElementById('mapping_list_id').value = listId;
    document.getElementById('mapping_list_name').value = listName;
    document.getElementById('mapping_list_display').value = listName;

    if (projectId === null) {
        document.getElementById('mapping_action').value = 'import';
        document.getElementById('mapping_project_id').value = '';
    } else if (projectId === 0) {
        document.getElementById('mapping_action').value = 'ignore';
    } else {
        document.getElementById('mapping_action').value = 'import';
        document.getElementById('mapping_project_id').value = projectId;
    }

    toggleProjectSelect();
    new bootstrap.Modal(document.getElementById('mappingModal')).show();
}

function toggleProjectSelect() {
    const action = document.getElementById('mapping_action').value;
    document.getElementById('project_select_div').style.display =
        action === 'import' ? 'block' : 'none';
}

function saveMapping() {
    const listId = document.getElementById('mapping_list_id').value;
    const listName = document.getElementById('mapping_list_name').value;
    const action = document.getElementById('mapping_action').value;
    const projectId = action === 'import' ?
                      document.getElementById('mapping_project_id').value : null;

    if (action === 'import' && !projectId) {
        alert('Seleziona un progetto');
        return;
    }

    fetch('/ai/import/mapping', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `csrf_token=<?= csrf_token() ?>&list_id=${listId}&list_name=${encodeURIComponent(listName)}&action=${action}&project_id=${projectId || ''}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('mappingModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Errore', 'error');
        }
    });
}
</script>

<style>
/* Additional styles for AI modal */
#aiMappingModal .modal-dialog {
    max-width: 1200px;
}

.project-mapping {
    font-weight: 500;
}

#aiMappingContent .card {
    border: 1px solid #dee2e6;
    transition: all 0.2s;
}

#aiMappingContent .card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.spinner-border {
    animation: spinner-border .75s linear infinite;
}
</style>

<?php
$content = ob_get_clean();
$title = 'AI Import Center - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>