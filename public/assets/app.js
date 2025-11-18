/**
 * Beweb Tirocinio - Custom JavaScript
 * Minimal JS per toasts, keyboard shortcuts, e funzioni utility
 */

// Auto-hide toasts dopo 5 secondi
document.addEventListener('DOMContentLoaded', function() {
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(function(toastEl) {
        const toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 5000
        });
        toast.show();

        // Auto close dopo 5 secondi
        setTimeout(function() {
            toast.hide();
        }, 5000);
    });
});

// Funzione globale per mostrare toast dinamici
function showToast(message, type = 'success') {
    // Remove existing toasts
    const existingContainer = document.querySelector('.toast-container');
    if (existingContainer) {
        existingContainer.remove();
    }

    // Create new toast
    const toastHtml = `
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999">
            <div class="toast show align-items-center text-bg-${type === 'error' ? 'danger' : 'success'} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${escapeHtml(message)}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', toastHtml);

    // Auto remove after 5 seconds
    setTimeout(function() {
        const container = document.querySelector('.toast-container');
        if (container) {
            container.remove();
        }
    }, 5000);
}

// Escape HTML per prevenire XSS
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Alt+N: Nuova attività
    if (e.altKey && e.key === 'n') {
        e.preventDefault();
        const btn = document.querySelector('a[href="/tasks/create"]');
        if (btn) btn.click();
    }

    // Alt+T: Nuovo time log
    if (e.altKey && e.key === 't') {
        e.preventDefault();
        const btn = document.querySelector('a[href="/timelogs/create"]');
        if (btn) btn.click();
    }

    // Alt+S: Salva form (se in una pagina con form)
    if (e.altKey && e.key === 's') {
        e.preventDefault();
        const submitBtn = document.querySelector('button[type="submit"][accesskey="s"]');
        if (submitBtn) {
            submitBtn.click();
        }
    }

    // ESC: Chiudi modal se aperto
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(function(modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        });
    }
});

// Conferma prima di lasciare pagina con form modificati
let formChanged = false;

document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');

    forms.forEach(function(form) {
        // Skip logout form
        if (form.action.includes('/logout')) return;

        const inputs = form.querySelectorAll('input, textarea, select');

        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                formChanged = true;
            });
        });

        form.addEventListener('submit', function() {
            formChanged = false;
        });
    });
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = 'Hai modifiche non salvate. Sei sicuro di voler uscire?';
        return e.returnValue;
    }
});

// Auto-focus sul primo input in modal quando viene aperto
document.addEventListener('shown.bs.modal', function(e) {
    const firstInput = e.target.querySelector('input:not([type="hidden"]), textarea, select');
    if (firstInput) {
        firstInput.focus();
    }
});

// Utility: Formatta numeri decimali in input ore
document.addEventListener('DOMContentLoaded', function() {
    const hoursInputs = document.querySelectorAll('input[name="hours"], input[name="hours_estimated"], input[name="hours_spent"]');

    hoursInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.value) {
                // Convert comma to dot
                this.value = this.value.replace(',', '.');

                // Round to 2 decimals
                const num = parseFloat(this.value);
                if (!isNaN(num)) {
                    this.value = num.toFixed(2);
                }
            }
        });
    });
});

// Utility: Auto-completamento campi data con oggi
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];

    dateInputs.forEach(function(input) {
        if (!input.value && input.hasAttribute('data-default-today')) {
            input.value = today;
        }
    });
});

// Utility: Conferma eliminazione con doppio click (sicurezza extra)
let deleteClickCount = 0;
let deleteClickTimer = null;

function confirmDelete(id, entity, callback) {
    deleteClickCount++;

    if (deleteClickCount === 1) {
        showToast('Clicca di nuovo per confermare eliminazione', 'error');

        deleteClickTimer = setTimeout(function() {
            deleteClickCount = 0;
        }, 3000);
    } else {
        clearTimeout(deleteClickTimer);
        deleteClickCount = 0;

        if (confirm(`Eliminare definitivamente questo ${entity}?`)) {
            callback(id);
        }
    }
}

// Utility: Copia testo negli appunti
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showToast('Copiato negli appunti', 'success');
        }).catch(function() {
            fallbackCopyToClipboard(text);
        });
    } else {
        fallbackCopyToClipboard(text);
    }
}

function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-9999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        document.execCommand('copy');
        showToast('Copiato negli appunti', 'success');
    } catch (err) {
        showToast('Errore nella copia', 'error');
    }

    document.body.removeChild(textArea);
}

// Debug: log errori JavaScript (solo in dev)
if (window.location.hostname === 'localhost' || window.location.hostname === 'beweb.local') {
    window.addEventListener('error', function(e) {
        console.error('JavaScript Error:', e.message, e.filename, e.lineno);
    });

    window.addEventListener('unhandledrejection', function(e) {
        console.error('Unhandled Promise Rejection:', e.reason);
    });
}

// Init tooltips (se presenti)
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
});

// Init popovers (se presenti)
document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
});

// ========== SMART FOCUS ADHD FUNCTIONS ==========

/**
 * Get Smart Focus suggestion - Cosa fare ora?
 */
function getSmartFocus() {
    const btn = document.getElementById('smartFocusBtn');
    const resultDiv = document.getElementById('smartFocusResult');

    // Disabilita pulsante durante caricamento
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analizzo...';
    }

    // Raccogli stato utente
    const userInput = {
        energy: document.querySelector('input[name="energyLevel"]:checked')?.value || 'medium',
        focus_time: document.getElementById('focusTime')?.value || '45',
        mood: document.querySelector('input[name="moodLevel"]:checked')?.value || 'neutral'
    };

    // Prepara form data
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    formData.append('energy', userInput.energy);
    formData.append('focus_time', userInput.focus_time);
    formData.append('mood', userInput.mood);

    // Chiamata API
    fetch(url('/ai/smart-focus'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayADHDFocusResult(data.data);

            // Aggiorna contatori se disponibili
            updateTaskCounters(data.data);
        } else {
            showToast(data.error || 'Errore nel caricamento suggerimenti', 'error');
            if (resultDiv) {
                resultDiv.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        ${data.error || 'Servizio temporaneamente non disponibile. Riprova tra poco.'}
                    </div>
                `;
                resultDiv.style.display = 'block';
            }
        }
    })
    .catch(error => {
        console.error('Smart Focus Error:', error);
        showToast('Errore di connessione. Riprova.', 'error');
        if (resultDiv) {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Errore di connessione. Verifica la tua connessione e riprova.
                </div>
            `;
            resultDiv.style.display = 'block';
        }
    })
    .finally(() => {
        // Riabilita pulsante
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-flag-checkered"></i> COSA FINISCO OGGI?';
        }
    });
}

/**
 * Get Quick Win - Task veloce per energia bassa
 */
function getQuickWin() {
    const btn = document.getElementById('quickWinBtn');
    const resultDiv = document.getElementById('smartFocusResult');

    // Disabilita pulsante
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cerco...';
    }

    // Quick win = bassa energia, 15 min max
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    formData.append('energy', 'low');
    formData.append('focus_time', '15');
    formData.append('mood', document.querySelector('input[name="moodLevel"]:checked')?.value || 'tired');
    formData.append('quick_win', 'true');

    fetch(url('/ai/smart-focus'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Modifica display per quick win
            if (data.data) {
                data.data.is_quick_win = true;
                displayADHDFocusResult(data.data);
            }
        } else {
            showToast('Nessun quick win disponibile', 'warning');
        }
    })
    .catch(error => {
        console.error('Quick Win Error:', error);
        showToast('Errore nel caricamento quick win', 'error');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trophy"></i> Quick Win (5 min)';
        }
    });
}

/**
 * Display ADHD-optimized focus result
 */
function displayADHDFocusResult(data) {
    const resultDiv = document.getElementById('smartFocusResult');
    if (!resultDiv) return;

    // Check message type
    if (data.type === 'no_tasks') {
        resultDiv.innerHTML = `
            <div class="alert alert-success">
                <h5 class="alert-heading"><i class="fas fa-check-circle"></i> Tutto completato!</h5>
                <p class="mb-0">${data.message || 'Nessuna attività da fare! Prenditi una pausa.'}</p>
            </div>
        `;
        resultDiv.style.display = 'block';
        return;
    }

    // Main suggestion with alternatives
    if (data.primary_task || data.task) {
        const primary = data.primary_task || data.task;
        const isQuickWin = data.is_quick_win || false;

        let html = `
            <div class="card border-${isQuickWin ? 'success' : 'warning'}">
                <div class="card-header bg-${isQuickWin ? 'success' : 'warning'} text-${isQuickWin ? 'white' : 'dark'}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-${isQuickWin ? 'trophy' : 'flag-checkered'}"></i>
                            ${isQuickWin ? 'Quick Win!' : 'Piano d\'azione ADHD'}
                        </h5>
                        ${data.strategy ? `<span class="badge bg-dark">${getStrategyLabel(data.strategy)}</span>` : ''}
                    </div>
                </div>
                <div class="card-body">`;

        // Motivazione
        if (data.motivation) {
            html += `
                <div class="alert alert-${isQuickWin ? 'success' : 'info'} mb-3">
                    <i class="fas fa-lightbulb"></i> <strong>${data.motivation}</strong>
                </div>`;
        }

        // Task principale
        html += `
            <div class="mb-4">
                <h5 class="text-${isQuickWin ? 'success' : 'primary'} mb-2">
                    <i class="fas fa-crosshairs"></i> ${isQuickWin ? 'VITTORIA FACILE' : 'FOCUS PRIMARIO'}
                </h5>
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">${escapeHtml(primary.title || 'Task')}</h4>
                        <div class="mb-2">
                            ${primary.project_name ? `<span class="badge bg-info me-2"><i class="fas fa-project-diagram"></i> ${escapeHtml(primary.project_name)}</span>` : ''}
                            <span class="badge bg-${getPriorityColor(primary.priority)} me-2">${primary.priority || 'Media'}</span>
                            <span class="badge bg-secondary">${primary.status || 'Da fare'}</span>
                        </div>`;

        // Reason e dettagli
        if (data.reason || primary.reason) {
            html += `
                <div class="alert alert-info mb-2">
                    <strong>Perché questo:</strong> ${data.reason || primary.reason}
                </div>`;
        }

        // Tempo stimato
        if (data.estimated_focus_time || primary.estimated_time) {
            html += `
                <p class="mb-2">
                    <i class="fas fa-clock"></i> <strong>Tempo stimato:</strong>
                    ${data.estimated_focus_time || primary.estimated_time || '30 minuti'}
                </p>`;
        }

        // Pulsanti azione
        html += `
            <div class="d-grid gap-2 d-md-flex">
                <a href="${url('/tasks/' + primary.id)}" class="btn btn-${isQuickWin ? 'success' : 'warning'} btn-lg">
                    <i class="fas fa-play"></i> ${isQuickWin ? 'FAI SUBITO!' : 'INIZIA ORA'}
                </a>
                <button onclick="markAsStarted(${primary.id})" class="btn btn-outline-success">
                    <i class="fas fa-check"></i> Ho iniziato!
                </button>
            </div>
        </div>
    </div>
</div>`;

        // Alternative se disponibili
        if (data.alternatives && data.alternatives.length > 0) {
            html += `
                <div class="mb-3">
                    <h6 class="text-secondary mb-2">
                        <i class="fas fa-exchange-alt"></i> Altre opzioni disponibili
                    </h6>
                    <div class="row g-2">`;

            data.alternatives.forEach(alt => {
                html += `
                    <div class="col-md-6">
                        <div class="card bg-light h-100">
                            <div class="card-body">
                                <strong>${escapeHtml(alt.task.title)}</strong>
                                ${alt.reason ? `<br><small class="text-muted">${escapeHtml(alt.reason)}</small>` : ''}
                                <div class="mt-2">
                                    <a href="${url('/tasks/' + alt.task.id)}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-arrow-right"></i> Vai
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>`;
            });

            html += `</div></div>`;
        }

        // Pulsante refresh
        html += `
            <div class="text-center mt-3">
                <button onclick="getSmartFocus()" class="btn btn-outline-secondary">
                    <i class="fas fa-redo"></i> Dammi un'altra opzione
                </button>
            </div>
        </div>
    </div>`;

        resultDiv.innerHTML = html;
        resultDiv.style.display = 'block';

        // Scroll to result
        resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

/**
 * Mark task as started
 */
function markAsStarted(taskId) {
    showToast('Ottimo! Continua così! 💪', 'success');

    // Potrebbe aggiornare lo stato del task via API
    // Per ora solo feedback positivo

    // Nascondi risultato dopo 2 secondi
    setTimeout(() => {
        const resultDiv = document.getElementById('smartFocusResult');
        if (resultDiv) {
            resultDiv.style.display = 'none';
        }
    }, 2000);
}

/**
 * Update task counters in UI
 */
function updateTaskCounters(data) {
    if (data.stats) {
        const openCount = document.getElementById('openTasksCount');
        const urgentCount = document.getElementById('urgentCount');

        if (openCount && data.stats.open_tasks !== undefined) {
            openCount.textContent = data.stats.open_tasks;
        }
        if (urgentCount && data.stats.urgent_tasks !== undefined) {
            urgentCount.textContent = data.stats.urgent_tasks;
        }
    }
}

/**
 * Get priority color for badge
 */
function getPriorityColor(priority) {
    switch(priority) {
        case 'Alta': return 'danger';
        case 'Media': return 'warning';
        case 'Bassa': return 'info';
        default: return 'secondary';
    }
}

/**
 * Get strategy label
 */
function getStrategyLabel(strategy) {
    const labels = {
        'overdue': '⚠️ Scaduto',
        'due_today': '📅 Scade oggi',
        'quick_win': '🏆 Quick Win',
        'in_progress': '🔄 In corso',
        'important': '🎯 Importante',
        'morning_focus': '🌅 Focus mattutino',
        'afternoon_task': '☀️ Task pomeridiano',
        'easy_start': '✨ Inizio facile',
        'deep_work': '🧠 Deep Work'
    };
    return labels[strategy] || strategy;
}

// Auto-load Smart Focus on dashboard if no tasks in progress
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on dashboard
    if (window.location.pathname === '/' || window.location.pathname.includes('dashboard')) {
        const smartFocusBtn = document.getElementById('smartFocusBtn');
        if (smartFocusBtn) {
            // Opzionale: carica automaticamente suggerimenti dopo 2 secondi
            // setTimeout(() => { getSmartFocus(); }, 2000);
        }
    }
});

console.log('Beweb Tirocinio App - JS Loaded (with Smart Focus)');
