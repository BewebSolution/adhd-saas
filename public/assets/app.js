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

console.log('Beweb Tirocinio App - JS Loaded');
