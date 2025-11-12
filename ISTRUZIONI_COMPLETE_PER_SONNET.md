# 📋 RICHIESTA COMPLETA PER SONNET - SVILUPPO CONTINUATIVO APP BEWEB TIROCINIO

**Ciao Sonnet! Devi continuare lo sviluppo di un'applicazione web. Leggi TUTTO attentamente prima di iniziare.**

## 🏗️ AMBIENTE DI LAVORO

**Posizione progetto:** `C:\laragon\www\tirocinio\beweb-app\`
**URL locale:** `http://localhost/tirocinio/public_html/`
**Stack:** PHP 8.1, MySQL (MariaDB), Apache (Laragon su Windows)
**Editor:** Puoi usare gli strumenti a tua disposizione per leggere/scrivere file

## 📚 CONTESTO COMPLETO DEL PROGETTO

**Beweb Tirocinio** è un'applicazione di gestione task ottimizzata per persone con ADHD (Attention Deficit Hyperactivity Disorder).

**Caratteristiche principali:**
1. **Smart Focus AI** - Un assistente AI che suggerisce QUALE task fare ORA basandosi su:
   - Livello energia dell'utente (bassa/media/alta)
   - Tempo disponibile (15-120+ minuti)
   - Umore attuale (5 livelli emoji)
   - Usa OpenAI GPT-3.5 per suggerimenti personalizzati
   - Fallback locale se AI non disponibile

2. **Gestione Task** - Sistema completo CRUD con:
   - Priorità (Alta/Media/Bassa)
   - Scadenze e reminder
   - Assegnazione a persone
   - Tracking ore lavorate
   - Stati: Da fare, In corso, In revisione, Fatto

3. **Import Google Tasks** - Importa task da Google con:
   - OAuth 2.0 per autenticazione
   - Processing AI per arricchimento
   - Mappatura automatica progetti (DA FIXARE)

4. **UI ADHD-Friendly**:
   - Zero paralisi decisionale
   - Feedback immediato
   - Widget interattivi per stato mentale
   - Colori e badge per priorità visuale

## 🗂️ STRUTTURA PROGETTO DETTAGLIATA

```
C:\laragon\www\tirocinio\
└── beweb-app\
    ├── app\
    │   ├── Controllers\          # Controller MVC
    │   │   ├── AIController.php  # Endpoint Smart Focus: /ai/smart-focus
    │   │   ├── TaskController.php # CRUD tasks
    │   │   ├── ProjectController.php
    │   │   ├── AIImportController.php # Google Tasks import
    │   │   └── AuthController.php
    │   ├── Models\               # Model con Active Record pattern
    │   │   ├── Task.php
    │   │   ├── Project.php
    │   │   ├── User.php
    │   │   └── TimeLog.php
    │   ├── Services\             # Business logic
    │   │   ├── AISmartFocusService.php # AI con OpenAI (NUOVO)
    │   │   ├── ADHDSmartFocusService.php # Fallback locale
    │   │   ├── SimplifiedADHDFocusService.php
    │   │   ├── BaseAIService.php # ⚠️ NON TOCCARE
    │   │   └── GoogleTasksService.php
    │   └── Views\                # Template PHP
    │       ├── dashboard\
    │       │   └── index.php    # Dashboard principale con Smart Focus
    │       ├── tasks\
    │       │   ├── index.php
    │       │   └── edit.php
    │       ├── ai\
    │       │   ├── import.php
    │       │   └── settings.php
    │       └── layouts\
    │           └── base.php     # Layout Bootstrap 5.3
    ├── public_html\              # Web root
    │   ├── index.php            # Entry point
    │   ├── css\
    │   └── js\
    ├── vendor\                   # Composer dependencies
    ├── bootstrap.php            # ⚠️ Core initialization - NON TOCCARE
    ├── composer.json
    └── .env                     # Config (NO API KEYS QUI!)
```

## 💾 DATABASE SCHEMA

**Database:** `beweb_app` (NON beweb_tirocinio!)

```sql
-- Tabelle principali
tasks (
    id, code, title, description, project_id,
    status ENUM('Da fare','In corso','In revisione','Fatto'),
    priority ENUM('Alta','Media','Bassa'),
    assignee, due_at, hours_estimated, hours_spent,
    created_at, updated_at
)

projects (id, name, description, created_at)

users (id, name, email, password, role, created_at)

time_logs (id, task_id, person, date, hours, description)

ai_settings (
    user_id, openai_api_key, claude_api_key,  -- API keys vanno QUI
    ai_provider, smart_focus_enabled, ...
)

ai_cache (cache_key, response, expires_at)  -- Cache risposte AI

suggestion_history (user_id, task_id, created_at) -- Storia suggerimenti
```

## 🛠️ STRUMENTI E UTILITIES DISPONIBILI

**File di test pronti:**
- `test_login.php` - Verifica autenticazione
- `test_smart_focus.php` - Test Smart Focus locale
- `test_ai_smart_focus.php` - Test con OpenAI
- `test_adhd_variety.php` - Test varietà suggerimenti

**Helper functions globali (da bootstrap.php):**
```php
auth()          // Ottieni utente corrente
url($path)      // Genera URL completo
redirect($path) // Redirect
flash($type, $message) // Flash messages
csrf_token()    // Token CSRF
verify_csrf()   // Verifica token
get_db()        // PDO instance
json_response($data, $code) // JSON response
```

## ⚠️ REGOLE CRITICHE - VIOLAZIONE = ROTTURA APP

1. **NON MODIFICARE MAI:**
   - `bootstrap.php` (core del sistema)
   - `BaseAIService.php` (classe base servizi)
   - `.htaccess` files

2. **API KEYS:**
   - ❌ MAI in `.env`
   - ✅ SEMPRE in database tabella `ai_settings`
   - Si configurano da: `/ai/settings` (solo admin)

3. **BOOTSTRAP:**
   - Versione: **5.3.0** (NON 4!)
   - CDN già configurati in `base.php`
   - Theme: SB Admin 2 (già adattato)

4. **DATABASE:**
   - SEMPRE prepared statements
   - MAI query dirette con variabili

5. **AUTENTICAZIONE:**
   - Check con `require_auth()` nei controller
   - Session in `$_SESSION['user']`

## 📋 DOCUMENTAZIONE DA LEGGERE

Prima di modificare QUALSIASI cosa, leggi questi file nella root del progetto:

1. **`HANDOVER_TO_SONNET.md`** - Contiene:
   - Cosa NON toccare
   - Pattern di codice da seguire
   - Problemi comuni e soluzioni
   - Recovery plan se rompi qualcosa

2. **`CURRENT_STATE_SNAPSHOT.md`** - Contiene:
   - Stato attuale funzionante
   - Lista features complete
   - Test da eseguire
   - Comandi utili

3. **`AI_SMART_FOCUS_SETUP.md`** - Documentazione servizio AI

## 🎯 TASK DA COMPLETARE

### TASK 1: POMODORO TIMER (Priorità ALTA)
**Obiettivo:** Timer integrato per tecnica Pomodoro (25 min lavoro + 5 min pausa)

**Requirements:**
- Si attiva cliccando "INIZIA ORA" su un task suggerito
- Countdown visibile in dashboard (sopra Smart Focus)
- Notifiche browser quando termina
- Suono configurabile
- Persiste con refresh pagina (localStorage)
- Conta pomodori completati oggi
- Pausa automatica dopo work session

**File da modificare:** `app/Views/dashboard/index.php`

**Dove aggiungere il codice:**
```javascript
// Cerca la funzione displayADHDFocusResult()
// Aggiungi DOPO questa funzione il codice del timer
// NON modificare le funzioni esistenti
```

### TASK 2: FIX IMPORT PROGETTI (Priorità MEDIA)
**Problema:** Task importati da Google non hanno project_id

**File:** `app/Controllers/AIImportController.php`

**Fix richiesto:**
1. Trova il metodo `processWithAI()` (circa riga 120-150)
2. Modifica il prompt AI per includere: "Per ogni task, suggerisci anche un nome progetto appropriato basato sul contesto"
3. Nel processing della risposta, crea il progetto se non esiste:
```php
$projectModel = new Project();
$project = $projectModel->findByName($suggestedProjectName);
if (!$project) {
    $projectId = $projectModel->create(['name' => $suggestedProjectName]);
} else {
    $projectId = $project['id'];
}
$taskData['project_id'] = $projectId;
```

### TASK 3: QUICK NOTES WIDGET (Priorità MEDIA)
**Obiettivo:** Widget per catturare distrazioni ADHD senza perdere focus

**Requirements:**
- Textarea sempre visibile in dashboard
- Salvataggio automatico ogni 5 secondi
- Timestamp automatico per ogni nota
- Mostra max 5 note recenti
- Storage in localStorage (non database per semplicità)

**Nuovo file da creare:** `app/Views/widgets/quick_notes.php`

**Includere in dashboard:**
```php
// In app/Views/dashboard/index.php
// Dopo la sezione Smart Focus, aggiungi:
<?php include __DIR__ . '/../widgets/quick_notes.php'; ?>
```

### TASK 4: FOCUS MODE (Priorità BASSA)
**Obiettivo:** Modalità immersiva per deep work

**Requirements:**
- Pulsante "Entra in Focus Mode" nel task suggerito
- Nasconde tutto tranne: task title, timer, pulsante exit
- Background scuro/minimale
- Timer grande al centro
- ESC per uscire

**File:** `app/Views/dashboard/index.php`

## 🔄 WORKFLOW DI SVILUPPO

Per ogni task:
1. **Leggi** i file coinvolti completamente
2. **Identifica** pattern esistente nel codice
3. **Scrivi** codice seguendo stesso stile
4. **Testa** con `test_smart_focus.php`
5. **Verifica** che login funzioni ancora
6. **Commenta** in italiano (utente italiano)

## ✅ CRITERI DI SUCCESSO

Il tuo lavoro è riuscito se:
- `php test_smart_focus.php` passa senza errori
- `php test_login.php` dice "Login successful"
- Smart Focus mostra 1 task + 2 alternative
- Nessun errore PHP in console browser (F12)
- UI Bootstrap rimane intatta
- Le nuove features funzionano

## 🚨 RECOVERY SE ROMPI QUALCOSA

### Smart Focus non funziona più:
In `app/Controllers/AIController.php` riga ~56:
```php
// Cambia da:
$service = new AISmartFocusService();
// A:
$service = new ADHDSmartFocusService();
```

### Login rotto:
Esegui: `php test_login.php` per ricreare sessione

### UI rotta:
Verifica in `app/Views/layouts/base.php` che Bootstrap sia 5.3:
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
```

### Database error:
- Verifica nome DB sia `beweb_app` (non beweb_tirocinio)
- User: root, Password: (vuota)

## 🎬 AZIONI DA ESEGUIRE

1. **STEP 1:** Leggi i 3 file documentazione nella root:
   - `HANDOVER_TO_SONNET.md`
   - `CURRENT_STATE_SNAPSHOT.md`
   - `AI_SMART_FOCUS_SETUP.md`

2. **STEP 2:** Esegui i test per verificare stato attuale:
   ```bash
   cd C:\laragon\www\tirocinio\beweb-app
   php test_smart_focus.php
   php test_login.php
   ```

3. **STEP 3:** Implementa TASK 1 (Pomodoro Timer)
   - Modifica `app/Views/dashboard/index.php`
   - Aggiungi JavaScript per timer
   - Testa che Smart Focus funzioni ancora

4. **STEP 4:** Procedi con TASK 2, 3, 4 in ordine

5. **STEP 5:** Per ogni task completato, esegui:
   ```bash
   php test_smart_focus.php  # Deve continuare a funzionare
   ```

## 💡 SUGGERIMENTI IMPORTANTI

1. **Stile codice PHP:**
   ```php
   // USA questo stile (già presente nel progetto)
   if ($condition) {
       // codice
   }

   // NON questo
   if($condition){
       // codice
   }
   ```

2. **Query database:**
   ```php
   // SEMPRE così
   $stmt = $db->prepare('SELECT * FROM tasks WHERE id = ?');
   $stmt->execute([$id]);

   // MAI così
   $db->query("SELECT * FROM tasks WHERE id = $id");
   ```

3. **JavaScript:**
   - USA vanilla JS, no jQuery (non caricato)
   - USA fetch() per AJAX, no axios
   - USA localStorage per persistenza client

4. **Commenti:**
   ```php
   // Commenta in italiano
   // L'utente è italiano
   ```

## 🚀 INIZIA SUBITO

**Non chiedermi conferme o chiarimenti. Hai tutto ciò che serve. Procedi autonomamente seguendo gli step. Mostrami il codice completo che scrivi e dove lo metti. Testa sempre dopo ogni modifica.**

Se hai problemi, consulta prima la documentazione fornita, poi il recovery plan.

Buon lavoro! 💪