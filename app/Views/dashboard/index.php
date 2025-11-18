<?php ob_start(); ?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    <div class="d-none d-sm-inline-block">
        <span class="text-muted">Benvenuto, <?= esc(auth()['name']) ?>!</span>
    </div>
</div>

<!-- Sezione Focus di oggi rimossa - ora integrata in Smart Focus ADHD -->

<!-- Smart Focus AI - ADHD Enhanced -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow border-left-warning" data-intro="smart-focus">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h5 class="card-title mb-3 text-warning">
                            <i class="fas fa-brain"></i> AI Coach - Cosa FINIRE oggi?
                        </h5>
                        <p class="text-muted mb-3">
                            <strong>NO paralisi decisionale!</strong> L'AI analizza i tuoi task e ti dice cosa completare ORA,
                            considerando energia, tempo disponibile e priorità. <span class="badge bg-info">ADHD Optimized</span>
                        </p>
                        <div class="row g-2 mb-3">
                            <div class="col-auto">
                                <button onclick="getSmartFocus()" class="btn btn-warning btn-lg" id="smartFocusBtn">
                                    <i class="fas fa-flag-checkered"></i> COSA FINISCO OGGI?
                                </button>
                            </div>
                            <div class="col-auto">
                                <button onclick="getQuickWin()" class="btn btn-outline-success" id="quickWinBtn">
                                    <i class="fas fa-trophy"></i> Quick Win (5 min)
                                </button>
                            </div>
                            <div class="col-auto">
                                <button onclick="pomodoro.toggleExpanded()" class="btn btn-danger btn-lg" id="pomodoroMainBtn">
                                    <i class="fas fa-clock"></i> 🍅 POMODORO (25 min)
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                <h6 class="card-subtitle mb-2 text-muted">Il tuo stato attuale:</h6>

                                <!-- Selettore Energia -->
                                <div class="mb-2">
                                    <label class="small text-muted mb-1">⚡ Energia:</label>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <input type="radio" class="btn-check" name="energyLevel" id="energyHigh" value="high">
                                        <label class="btn btn-outline-success" for="energyHigh">Alta</label>

                                        <input type="radio" class="btn-check" name="energyLevel" id="energyMedium" value="medium" checked>
                                        <label class="btn btn-outline-warning" for="energyMedium">Media</label>

                                        <input type="radio" class="btn-check" name="energyLevel" id="energyLow" value="low">
                                        <label class="btn btn-outline-danger" for="energyLow">Bassa</label>
                                    </div>
                                </div>

                                <!-- Selettore Tempo Focus -->
                                <div class="mb-2">
                                    <label class="small text-muted mb-1">⏱️ Tempo disponibile:</label>
                                    <select class="form-select form-select-sm" id="focusTime">
                                        <option value="15">15 minuti</option>
                                        <option value="25">25 minuti (Pomodoro)</option>
                                        <option value="45" selected>45 minuti</option>
                                        <option value="60">1 ora</option>
                                        <option value="90">1.5 ore</option>
                                        <option value="120">2+ ore</option>
                                    </select>
                                </div>

                                <!-- Selettore Mood -->
                                <div class="mb-2">
                                    <label class="small text-muted mb-1">😊 Umore:</label>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <input type="radio" class="btn-check" name="moodLevel" id="moodGreat" value="great">
                                        <label class="btn btn-outline-success" for="moodGreat">😄</label>

                                        <input type="radio" class="btn-check" name="moodLevel" id="moodGood" value="good" checked>
                                        <label class="btn btn-outline-primary" for="moodGood">🙂</label>

                                        <input type="radio" class="btn-check" name="moodLevel" id="moodNeutral" value="neutral">
                                        <label class="btn btn-outline-secondary" for="moodNeutral">😐</label>

                                        <input type="radio" class="btn-check" name="moodLevel" id="moodTired" value="tired">
                                        <label class="btn btn-outline-warning" for="moodTired">😴</label>

                                        <input type="radio" class="btn-check" name="moodLevel" id="moodStressed" value="stressed">
                                        <label class="btn btn-outline-danger" for="moodStressed">😰</label>
                                    </div>
                                </div>

                                <!-- Stats rapide -->
                                <div class="mt-2 pt-2 border-top">
                                    <small class="text-muted">
                                        <i class="fas fa-tasks"></i> Task aperti: <strong id="openTasksCount">-</strong> |
                                        <i class="fas fa-exclamation-triangle"></i> Urgenti: <strong id="urgentCount">-</strong>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="smartFocusResult" class="mt-4" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Scadenze imminenti -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100" data-intro="deadlines">
            <div class="card-header bg-gradient-warning text-white">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-clock"></i> Scadenze più vicine</h6>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingDeadlines)): ?>
                    <p class="text-muted mb-0"><i class="fas fa-check-circle"></i> Nessuna scadenza imminente</p>
                <?php else: ?>
                    <?php foreach ($upcomingDeadlines as $task): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <span class="badge bg-<?= priority_badge_color($task['priority'] ?? '') ?>">
                                        <?= esc($task['priority'] ?? 'N/A') ?>
                                    </span>
                                    <?= esc($task['project_name']) ?>
                                </h6>
                                <h5 class="card-title"><?= esc($task['title']) ?></h5>
                                <p class="card-text">
                                    <i class="fas fa-clock text-danger"></i>
                                    <strong>Scadenza:</strong> <?= format_datetime($task['due_at']) ?>
                                </p>
                                <a href="<?= url('/tasks/' . $task['id']) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-arrow-right"></i> Apri
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Attività in corso -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100" data-intro="in-progress">
            <div class="card-header bg-gradient-primary text-white">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-play-circle"></i> Attività in corso</h6>
            </div>
            <div class="card-body">
                <?php if (empty($tasksInProgress)): ?>
                    <p class="text-muted mb-0"><i class="fas fa-info-circle"></i> Nessuna attività in corso. Iniziane una!</p>
                <?php else: ?>
                    <?php foreach ($tasksInProgress as $task): ?>
                        <div class="card mb-3 border-primary">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <span class="badge bg-<?= priority_badge_color($task['priority'] ?? '') ?>">
                                        <?= esc($task['priority'] ?? 'N/A') ?>
                                    </span>
                                    <?= esc($task['project_name']) ?>
                                </h6>
                                <h5 class="card-title"><?= esc($task['title']) ?></h5>
                                <p class="card-text mb-2">
                                    <strong>Ore:</strong> <?= $task['hours_spent'] ?? 0 ?> / <?= $task['hours_estimated'] ?? '-' ?>
                                </p>
                                <a href="<?= url('/tasks/' . $task['id']) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-arrow-right"></i> Continua
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Registro ore recenti -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm" data-intro="recent-logs">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Ultimi time log (7 giorni)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentTimeLogs)): ?>
                    <p class="text-muted mb-0">Nessun time log recente</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Persona</th>
                                    <th>Attività</th>
                                    <th>Descrizione</th>
                                    <th class="text-end">Ore</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTimeLogs as $log): ?>
                                    <tr>
                                        <td><?= format_date($log['date']) ?></td>
                                        <td><?= esc($log['person']) ?></td>
                                        <td>
                                            <?php if ($log['task_code']): ?>
                                                <small class="text-muted"><?= esc($log['task_code']) ?></small><br>
                                                <?= truncate(esc($log['task_title'] ?? ''), 30) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= truncate(esc($log['description']), 50) ?></td>
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
</div>

<!-- Quick actions -->
<div class="row" data-intro="quick-actions">
    <div class="col-md-6 mb-3">
        <a href="<?= url('/timelogs/create') ?>" class="btn btn-lg btn-success w-100" accesskey="t">
            <i class="fas fa-plus-circle"></i> Nuovo Registro ore <small class="text-white-50">(Alt+T)</small>
        </a>
    </div>
    <div class="col-md-6 mb-3">
        <a href="<?= url('/tasks/create') ?>" class="btn btn-lg btn-primary w-100" accesskey="n">
            <i class="fas fa-plus-circle"></i> Nuova Attività <small class="text-white-50">(Alt+N)</small>
        </a>
    </div>
</div>

<script>
// Funzione saveFocus rimossa - ora integrata in Smart Focus ADHD

function getSmartFocus() {
    const btn = document.getElementById('smartFocusBtn');
    const resultDiv = document.getElementById('smartFocusResult');

    // Disabilita bottone e mostra loading
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Analizzo contesto e task...';

    // Raccogli dati utente
    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');

    // Aggiungi stato energia
    const energyLevel = document.querySelector('input[name="energyLevel"]:checked')?.value || 'medium';
    formData.append('energy', energyLevel);

    // Aggiungi tempo focus
    const focusTime = document.getElementById('focusTime')?.value || '45';
    formData.append('focus_time', focusTime);

    // Aggiungi umore
    const mood = document.querySelector('input[name="moodLevel"]:checked')?.value || 'neutral';
    formData.append('mood', mood);

    // Log per debug
    console.log('Sending data:', {
        energy: energyLevel,
        focus_time: focusTime,
        mood: mood
    });

    fetch(url('/ai/smart-focus'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-flag-checkered"></i> COSA FINISCO OGGI?';

        if (data.success && data.data) {
            displayADHDFocusResult(data.data);
            // Aggiorna statistiche se presenti
            updateDashboardStats(data.data);
        } else {
            showToast(data.error || 'Errore durante l\'analisi AI', 'error');
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-flag-checkered"></i> COSA FINISCO OGGI?';
        showToast('Errore di connessione', 'error');
        console.error('ADHD Focus error:', error);
    });
}

function getQuickWin() {
    // Forza una richiesta per quick win
    const btn = document.getElementById('quickWinBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cerco quick win...';

    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    formData.append('strategy', 'quick_win');
    formData.append('energy', 'low'); // Quick win = energia bassa
    formData.append('focus_time', '15'); // Quick = poco tempo

    fetch(url('/ai/smart-focus'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trophy"></i> Quick Win (5 min)';

        if (data.success && data.data) {
            displayADHDFocusResult(data.data);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trophy"></i> Quick Win (5 min)';
    });
}

// Nuova funzione per aggiornare le statistiche
function updateDashboardStats(data) {
    if (data.context_summary) {
        // Aggiorna contatori se presenti nel response
        if (data.context_summary.tasks_open !== undefined) {
            document.getElementById('openTasksCount').textContent = data.context_summary.tasks_open;
        }
        if (data.context_summary.urgent_count !== undefined) {
            document.getElementById('urgentCount').textContent = data.context_summary.urgent_count;
        }
    }
}

function displayADHDFocusResult(data) {
    const resultDiv = document.getElementById('smartFocusResult');

    if (data.type === 'no_tasks') {
        // Nessuna task disponibile
        resultDiv.innerHTML = `
            <div class="alert alert-success">
                <h5 class="alert-heading"><i class="fas fa-check-circle"></i> Tutto completato!</h5>
                <p class="mb-0">${data.message}</p>
            </div>
        `;
    } else if (data.primary_task) {
        // Nuovo formato ADHD con primary e backup task
        const primary = data.primary_task;
        const backup = data.backup_task;
        const suggestion = primary.suggestion || {};

        resultDiv.innerHTML = `
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-flag-checkered"></i> Piano d'azione ADHD</h5>
                        ${data.strategy ? `<span class="badge bg-dark">${getStrategyLabel(data.strategy)}</span>` : ''}
                    </div>
                </div>
                <div class="card-body">
                    <!-- Motivazione -->
                    ${data.motivation ? `
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-lightbulb"></i> <strong>${data.motivation}</strong>
                    </div>
                    ` : ''}

                    <!-- Task Principale -->
                    <div class="mb-4">
                        <h5 class="text-primary mb-2">
                            <i class="fas fa-crosshairs"></i> FOCUS PRIMARIO
                        </h5>
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">${primary.title}</h4>
                                <div class="mb-2">
                                    <span class="badge bg-info me-2">
                                        <i class="fas fa-project-diagram"></i> ${primary.project_name || 'N/A'}
                                    </span>
                                    <span class="badge bg-${getPriorityColor(primary.priority)} me-2">
                                        ${primary.priority || 'Media'}
                                    </span>
                                    <span class="badge bg-secondary">
                                        ${primary.status}
                                    </span>
                                </div>

                                ${suggestion.why_this ? `
                                <div class="alert alert-info mb-2">
                                    <strong>Perché questo:</strong> ${suggestion.why_this}
                                </div>
                                ` : ''}

                                ${suggestion.what_to_do ? `
                                <p class="mb-2">
                                    <i class="fas fa-tasks"></i> <strong>Cosa fare:</strong> ${suggestion.what_to_do}
                                </p>
                                ` : ''}

                                <div class="row g-2 mb-3">
                                    ${suggestion.time_needed ? `
                                    <div class="col-auto">
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> Tempo: ${suggestion.time_needed}
                                        </small>
                                    </div>
                                    ` : ''}
                                    ${suggestion.completion_chance ? `
                                    <div class="col-auto">
                                        <small class="text-muted">
                                            <i class="fas fa-percentage"></i> Probabilità completamento: ${suggestion.completion_chance}%
                                        </small>
                                    </div>
                                    ` : ''}
                                </div>

                                <div class="d-grid gap-2 d-md-flex">
                                    <a href="${url('/tasks/' + primary.id)}" class="btn btn-warning btn-lg">
                                        <i class="fas fa-play"></i> INIZIA ORA
                                    </a>
                                    <button onclick="markAsStarted(${primary.id})" class="btn btn-outline-success">
                                        <i class="fas fa-check"></i> Ho iniziato!
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alternative Tasks -->
                    ${data.alternatives && data.alternatives.length > 0 ? `
                    <div class="mb-3">
                        <h6 class="text-secondary mb-2">
                            <i class="fas fa-exchange-alt"></i> Altre opzioni disponibili
                        </h6>
                        <div class="row g-2">
                            ${data.alternatives.map((alt, index) => `
                            <div class="col-md-6">
                                <div class="card bg-light h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <strong class="flex-grow-1">${alt.task.title}</strong>
                                            <span class="badge bg-${getStrategyColor(alt.type)}">${getStrategyLabel(alt.type)}</span>
                                        </div>
                                        <small class="text-muted d-block mb-2">${alt.reason || ''}</small>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-project-diagram"></i> ${alt.task.project_name || 'N/A'}
                                            </small>
                                            <a href="${url('/tasks/' + alt.task.id)}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-arrow-right"></i> Vai
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}

                    <!-- Backup Task (fallback per compatibilità) -->
                    ${!data.alternatives && backup ? `
                    <div class="mb-3">
                        <h6 class="text-secondary mb-2">
                            <i class="fas fa-exchange-alt"></i> Piano B (se il primo non funziona)
                        </h6>
                        <div class="card bg-light">
                            <div class="card-body">
                                <strong>${backup.title}</strong>
                                ${backup.suggestion?.why_this ? `<br><small class="text-muted">${backup.suggestion.why_this}</small>` : ''}
                                <div class="mt-2">
                                    <a href="${url('/tasks/' + backup.id)}" class="btn btn-sm btn-outline-primary">
                                        Passa al Piano B
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    ` : ''}

                    <!-- Warning -->
                    ${data.warning ? `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Attenzione:</strong> ${data.warning}
                    </div>
                    ` : ''}

                    <!-- Azioni -->
                    <div class="text-center mt-3">
                        <button onclick="getSmartFocus()" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i> Dammi un'altra opzione
                        </button>
                    </div>
                </div>
            </div>
        `;
    } else if (data.task) {
        // Fallback al formato vecchio
        displaySmartFocusResult(data);
        return;
    } else {
        resultDiv.innerHTML = `
            <div class="alert alert-warning">
                <p class="mb-0">Nessun suggerimento disponibile al momento.</p>
            </div>
        `;
    }

    resultDiv.style.display = 'block';
}

// Manteniamo la vecchia funzione per compatibilità
function displaySmartFocusResult(data) {
    const resultDiv = document.getElementById('smartFocusResult');

    if (!data.task && data.suggestion_type === 'no_tasks') {
        resultDiv.innerHTML = `
            <div class="alert alert-success">
                <h5 class="alert-heading"><i class="fas fa-check-circle"></i> Nessuna attività da fare!</h5>
                <p class="mb-0">${data.reason}</p>
            </div>
        `;
    } else if (data.task) {
        const task = data.task;
        const confidenceBadge = data.confidence >= 80 ? 'success' : (data.confidence >= 60 ? 'warning' : 'secondary');

        resultDiv.innerHTML = `
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-robot"></i> Suggerimento AI</h5>
                </div>
                <div class="card-body">
                    <h4 class="mb-3">${task.title}</h4>
                    <div class="alert alert-info">
                        <strong>💡 Perché ora:</strong><br>
                        ${data.reason}
                    </div>
                    <a href="${url('/tasks/' + task.id)}" class="btn btn-success btn-lg">
                        <i class="fas fa-play-circle"></i> Inizia Ora
                    </a>
                </div>
            </div>
        `;
    }

    resultDiv.style.display = 'block';
}

function getStrategyLabel(strategy) {
    const labels = {
        'quick_win': '🏆 Quick Win',
        'urgent_first': '🔥 Urgente',
        'deep_work': '🧠 Deep Work',
        'finish_first': '🔄 Continua',
        'maintenance': '📝 Manutenzione',
        'easy_start': '🌱 Facile',
        'energy_match': '⚡ Energia',
        'momentum': '🔄 In corso'
    };
    return labels[strategy] || strategy;
}

function getStrategyColor(type) {
    const colors = {
        'quick_win': 'success',
        'urgent_first': 'danger',
        'easy_start': 'info',
        'energy_match': 'warning',
        'momentum': 'primary',
        'deep_work': 'dark'
    };
    return colors[type] || 'secondary';
}

function getPriorityColor(priority) {
    switch(priority) {
        case 'Alta': return 'danger';
        case 'Media': return 'warning';
        case 'Bassa': return 'secondary';
        default: return 'secondary';
    }
}

function markAsStarted(taskId) {
    // Marca il task come iniziato
    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    formData.append('status', 'In corso');

    fetch(url('/tasks/' + taskId + '/toggle-status'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Task marcato come "In corso"! 🚀', 'success');
            setTimeout(() => location.href = url('/tasks/' + taskId), 1000);
        }
    });
}

// Carica statistiche iniziali al caricamento pagina
document.addEventListener('DOMContentLoaded', function() {
    // Carica conteggio task aperti e urgenti
    loadTaskStats();

    // Auto-salva preferenze utente
    document.querySelectorAll('input[name="energyLevel"], input[name="moodLevel"]').forEach(input => {
        input.addEventListener('change', function() {
            localStorage.setItem(this.name, this.value);
        });
    });

    document.getElementById('focusTime').addEventListener('change', function() {
        localStorage.setItem('focusTime', this.value);
    });

    // Ripristina preferenze salvate
    const savedEnergy = localStorage.getItem('energyLevel');
    if (savedEnergy) {
        const energyRadio = document.getElementById('energy' + savedEnergy.charAt(0).toUpperCase() + savedEnergy.slice(1));
        if (energyRadio) energyRadio.checked = true;
    }

    const savedMood = localStorage.getItem('moodLevel');
    if (savedMood) {
        const moodRadio = document.getElementById('mood' + savedMood.charAt(0).toUpperCase() + savedMood.slice(1));
        if (moodRadio) moodRadio.checked = true;
    }

    const savedFocus = localStorage.getItem('focusTime');
    if (savedFocus) {
        document.getElementById('focusTime').value = savedFocus;
    }
});

function loadTaskStats() {
    // Carica statistiche task via AJAX
    fetch(url('/api/task-stats'))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('openTasksCount').textContent = data.stats.open || '0';
                document.getElementById('urgentCount').textContent = data.stats.urgent || '0';
            }
        })
        .catch(error => {
            console.log('Stats not available');
            // Fallback: conta dai dati già presenti nella pagina
            const openTasks = <?= count(array_filter($tasksInProgress ?? [], fn($t) => $t['status'] !== 'Fatto')) ?>;
            const urgentTasks = <?= count(array_filter($upcomingDeadlines ?? [], fn($t) =>
                $t['due_at'] && strtotime($t['due_at']) < strtotime('+2 days')
            )) ?>;
            document.getElementById('openTasksCount').textContent = openTasks;
            document.getElementById('urgentCount').textContent = urgentTasks;
        });
}
</script>

<?php
$content = ob_get_clean();
$title = 'Dashboard - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
