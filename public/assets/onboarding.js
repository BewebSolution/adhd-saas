/**
 * Onboarding Tours - Intro.js
 * Tour guidati per ogni sezione dell'applicazione
 */

(function() {
    'use strict';

    // Configurazione generale Intro.js
    const introConfig = {
        exitOnOverlayClick: false,
        showProgress: true,
        showBullets: true,
        scrollToElement: true,
        scrollPadding: 80,
        nextLabel: 'Avanti',
        prevLabel: 'Indietro',
        skipLabel: 'Salta',
        doneLabel: 'Fatto!',
        hidePrev: true,
        hideNext: false,
    };

    // Tour per Dashboard
    const dashboardTour = {
        steps: [
            {
                title: 'Benvenuto nella Dashboard! 👋',
                intro: 'Questa è la tua area principale. Qui trovi una panoramica di tutto ciò che devi fare oggi e nelle prossime ore.'
            },
            {
                element: document.querySelector('[data-intro="focus"]'),
                title: 'Focus di oggi 🎯',
                intro: 'Scrivi qui la tua priorità principale per oggi. Questo ti aiuta a rimanere concentrato su ciò che conta davvero.',
                position: 'bottom'
            },
            {
                element: document.querySelector('[data-intro="smart-focus"]'),
                title: 'AI Assistant - Smart Focus 🤖✨',
                intro: 'Non sai da dove iniziare? L\'AI analizza le tue attività e il contesto (ora del giorno, energia, scadenze) per suggerirti COSA FARE ADESSO. Perfetto per combattere la paralisi decisionale!',
                position: 'bottom'
            },
            {
                element: document.querySelector('[data-intro="deadlines"]'),
                title: 'Prossime scadenze 📅',
                intro: 'Le 3 attività con scadenza più vicina appaiono qui. Così non perdi mai un deadline importante!',
                position: 'top'
            },
            {
                element: document.querySelector('[data-intro="in-progress"]'),
                title: 'Attività in corso ⚡',
                intro: 'Le attività che hai iniziato appaiono qui. Ricorda: meglio finire una cosa alla volta che iniziarne tante!',
                position: 'top'
            },
            {
                element: document.querySelector('[data-intro="recent-logs"]'),
                title: 'Ultimi time log ⏱️',
                intro: 'Gli ultimi 7 giorni di lavoro registrato. Perfetto per vedere cosa hai fatto di recente.',
                position: 'top'
            },
            {
                element: document.querySelector('[data-intro="quick-actions"]'),
                title: 'Azioni rapide 🚀',
                intro: 'Usa questi pulsanti per registrare ore o creare nuove attività velocemente, senza navigare nei menu.',
                position: 'left'
            }
        ]
    };

    // Tour per Attività
    const tasksTour = {
        steps: [
            {
                title: 'Gestione Attività 📋',
                intro: 'Qui puoi vedere, creare e gestire tutte le tue attività. Ogni attività ha un codice univoco (es: A-001) generato automaticamente.'
            },
            {
                element: document.querySelector('[data-intro="filters"]'),
                title: 'Filtri ricerca 🔍',
                intro: 'Usa questi filtri per trovare rapidamente le attività per progetto, stato, priorità o assegnatario.',
                position: 'bottom'
            },
            {
                element: document.querySelector('[data-intro="new-task"]'),
                title: 'Nuova attività ➕',
                intro: 'Clicca qui per creare una nuova attività. Il codice verrà generato automaticamente!',
                position: 'left'
            },
            {
                element: document.querySelector('[data-intro="task-row"]'),
                title: 'Righe attività 📝',
                intro: 'Ogni riga mostra titolo, progetto, scadenza, stato, priorità e ore. Clicca sul titolo per vedere i dettagli.',
                position: 'top'
            },
            {
                element: document.querySelector('[data-intro="quick-actions-task"]'),
                title: 'Azioni rapide ⚡',
                intro: 'Usa i pulsanti per visualizzare, modificare, cambiare stato o eliminare (admin) le attività rapidamente.',
                position: 'left'
            }
        ]
    };

    // Tour per Registro Ore
    const timelogsTour = {
        steps: [
            {
                title: 'Registro Ore ⏱️',
                intro: 'Qui registri tutto il tempo che dedichi alle attività. Puoi collegare le ore ad attività specifiche, indicare se ci sono blocchi e inserire link agli output prodotti. Usa i filtri per trovare rapidamente i log che cerchi.'
            }
        ]
    };

    // Tour per Consegne
    const deliverablesTour = {
        steps: [
            {
                title: 'Gestione Consegne 📤',
                intro: 'Qui tieni traccia di tutti i file e materiali che consegni: moodboard, bozze, versioni definitive, report, etc. Puoi filtrare per progetto, tipo e stato, e aggiornare lo stato quando cambia.'
            }
        ]
    };

    // Tour per Note
    const notesTour = {
        steps: [
            {
                title: 'Gestione Note 📝',
                intro: 'Usa le note per catturare decisioni, idee, domande, recap giornalieri e qualsiasi cosa che non vuoi dimenticare. Puoi aggiungere azioni successive con responsabile e scadenza.'
            }
        ]
    };

    // Tour per Import (solo admin)
    const importTour = {
        steps: [
            {
                title: 'Import CSV 📥',
                intro: 'Usa questa pagina per importare in massa attività, time log, consegne e note da file CSV esportati da Google Sheets. Il sistema mostra anteprima, propone mappatura automatica e gestisce conflitti.'
            }
        ]
    };

    // Tour per Impostazioni (solo admin)
    const settingsTour = {
        steps: [
            {
                title: 'Impostazioni Liste 🛠️',
                intro: 'Qui gestisci tutte le liste a tendina usate nell\'applicazione: priorità, stati, persone e tipi di consegna. Puoi aggiungere, modificare ed eliminare valori. Attenzione: eliminare un valore non elimina le entità che lo usano.'
            }
        ]
    };

    // Mappa delle pagine ai tour
    const basePath = '/tirocinio/beweb-app/public';
    const tours = {
        [basePath + '/']: dashboardTour,
        [basePath + '/tasks']: tasksTour,
        [basePath + '/timelogs']: timelogsTour,
        [basePath + '/deliverables']: deliverablesTour,
        [basePath + '/notes']: notesTour,
        [basePath + '/import']: importTour,
        [basePath + '/settings/lists']: settingsTour,
        // Also map root path for dashboard
        '/': dashboardTour
    };

    // Rileva la pagina corrente
    function getCurrentPage() {
        const path = window.location.pathname;
        return path;
    }

    // Ottieni il tour per la pagina corrente
    function getCurrentTour() {
        const page = getCurrentPage();
        return tours[page];
    }

    // Verifica se il tour è stato già visto
    function isTourCompleted(page) {
        const key = 'tour_completed_' + page.replace(/\//g, '_');
        return localStorage.getItem(key) === 'true';
    }

    // Marca il tour come completato
    function markTourCompleted(page) {
        const key = 'tour_completed_' + page.replace(/\//g, '_');
        localStorage.setItem(key, 'true');
    }

    // Inizializza il tour
    function initTour() {
        const page = getCurrentPage();
        const tourConfig = getCurrentTour();

        if (!tourConfig) {
            // Nessun tour per questa pagina
            const btn = document.getElementById('startTourBtn');
            if (btn) {
                btn.style.display = 'none';
            }
            return;
        }

        // Filtra gli step che hanno elementi validi (alcuni potrebbero non esistere sempre)
        const validSteps = tourConfig.steps.filter(step => {
            if (!step.element) return true; // Step introduttivi senza element
            return step.element !== null;
        });

        if (validSteps.length === 0) {
            console.warn('No valid tour steps found for page:', page);
            return;
        }

        // Configura Intro.js
        const intro = introJs();
        intro.setOptions({
            ...introConfig,
            steps: validSteps
        });

        // Quando il tour viene completato o saltato
        intro.oncomplete(function() {
            markTourCompleted(page);
        });

        intro.onexit(function() {
            markTourCompleted(page);
        });

        // Pulsante per avviare il tour manualmente
        const startBtn = document.getElementById('startTourBtn');
        if (startBtn) {
            startBtn.addEventListener('click', function() {
                intro.start();
            });
        }

        // Avvia automaticamente se non è stato ancora visto
        if (!isTourCompleted(page)) {
            // Aspetta che la pagina sia completamente caricata
            setTimeout(function() {
                intro.start();
            }, 500);
        }
    }

    // Inizializza quando il DOM è pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTour);
    } else {
        initTour();
    }

})();
