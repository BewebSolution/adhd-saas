# 📸 SNAPSHOT STATO ATTUALE - 12 Novembre 2024

## ✅ COSA FUNZIONA ORA

### 1. Smart Focus ADHD
- **Status:** ✅ FUNZIONANTE
- **Endpoint:** POST `/ai/smart-focus`
- **Input:** energia (low/medium/high), tempo (minuti), umore
- **Output:** 1 task principale + 2 alternative
- **Files:**
  - `app/Services/AISmartFocusService.php` (con AI)
  - `app/Services/ADHDSmartFocusService.php` (fallback)
  - `app/Controllers/AIController.php` → metodo `smartFocus()`

### 2. Dashboard UI
- **Status:** ✅ FUNZIONANTE
- **Theme:** SB Admin 2 + Bootstrap 5.3
- **Widgets funzionanti:**
  - Selezione energia (3 pulsanti)
  - Selezione tempo (dropdown)
  - Selezione umore (5 emoji)
  - Display task suggerito con alternative
- **File:** `app/Views/dashboard/index.php`

### 3. Google Tasks Import
- **Status:** ⚠️ PARZIALE (importa ma senza auto-mapping progetti)
- **Files:**
  - `app/Controllers/AIImportController.php`
  - `app/Views/ai/import.php`

### 4. Database
- **Tables principali:**
  ```sql
  tasks (id, title, status, priority, assignee, due_at, ...)
  projects (id, name, created_at, ...)
  ai_settings (user_id, openai_api_key, ai_provider, ...)
  ai_cache (cache_key, response, expires_at)
  suggestion_history (user_id, task_id, created_at)
  ```

## 🔴 COSA NON FUNZIONA

1. **Pomodoro Timer** - Non implementato
2. **Quick Notes Widget** - Non implementato
3. **Auto-mapping progetti in import** - Da fixare
4. **Pattern Insights** - Non implementato
5. **Voice to Task** - Non implementato

## 🎯 TASK IMMEDIATI DA FARE

### TASK 1: Pomodoro Timer (PRIORITÀ ALTA)
```javascript
// Da aggiungere in dashboard/index.php

let pomodoroInterval;
let pomodoroTime = 25 * 60; // 25 minuti
let isBreak = false;

function startPomodoro(taskId) {
    // Implementare:
    // - Countdown visuale
    // - Notifica browser al termine
    // - Switch automatico lavoro/pausa
    // - Salvataggio stato in localStorage
}
```

### TASK 2: Fix Import Progetti
```php
// In AIImportController.php, riga ~150
// Modificare prompt AI per includere:
"Per ogni task, suggerisci anche un nome progetto appropriato"

// Poi creare progetto se non esiste:
$projectModel = new Project();
$project = $projectModel->findByName($suggestedName);
if (!$project) {
    $projectId = $projectModel->create(['name' => $suggestedName]);
}
```

## 🛠️ COMANDI TEST

```bash
# Test login (deve dire "Login successful")
php test_login.php

# Test Smart Focus (deve dare 3 suggerimenti)
php test_smart_focus.php

# Test AI (funziona solo con API key)
php test_ai_smart_focus.php

# Test varietà suggerimenti
php test_adhd_variety.php
```

## ⚠️ ATTENZIONE CRITICA

### NON FARE MAI:
1. ❌ Cambiare Bootstrap da 5 a 4
2. ❌ Modificare `BaseAIService.php`
3. ❌ Mettere API keys in `.env`
4. ❌ Usare `$_GET/$_POST` direttamente senza sanitize
5. ❌ Query SQL senza prepared statements

### FAI SEMPRE:
1. ✅ Test dopo ogni modifica
2. ✅ Prepared statements per DB
3. ✅ API keys dal database
4. ✅ Fallback se AI non disponibile
5. ✅ CSRF token per POST requests

## 📂 STRUTTURA FILE CRITICI

```
app/
├── Controllers/
│   ├── AIController.php          ← ENDPOINT: /ai/smart-focus
│   ├── TaskController.php        ← CRUD tasks
│   └── AIImportController.php    ← Import Google Tasks
├── Services/
│   ├── AISmartFocusService.php   ← USA OPENAI (nuovo)
│   ├── ADHDSmartFocusService.php ← FALLBACK locale
│   └── BaseAIService.php         ← NON TOCCARE!
└── Views/
    ├── dashboard/index.php       ← UI principale
    └── layouts/base.php          ← Template Bootstrap 5

## 💡 SUGGERIMENTI PER SONNET

Quando Sonnet lavora su questo progetto:

1. **USA SEMPRE questo formato per richieste:**
   ```
   FILE: [nome esatto file]
   FUNZIONE: [cosa modificare]
   NON TOCCARE: [lista file da non modificare]
   TEST CON: [comando test]
   ```

2. **Prima di modificare, SEMPRE:**
   - Leggere il file completo
   - Identificare pattern esistente
   - Usare stesso stile codice

3. **Dopo modifiche, SEMPRE:**
   - Testare con `test_smart_focus.php`
   - Verificare login funziona
   - Controllare console per errori PHP

## 🚨 RECOVERY PLAN

Se qualcosa si rompe:

1. **Smart Focus non funziona:**
   ```php
   // In AIController.php cambia:
   $service = new AISmartFocusService();
   // Con:
   $service = new ADHDSmartFocusService();
   ```

2. **Login non funziona:**
   ```php
   // Verifica in bootstrap.php:
   session_start();
   // E in test_login.php imposta:
   $_SESSION['user'] = [...];
   ```

3. **UI rotta:**
   - Controlla versione Bootstrap (DEVE essere 5.3)
   - Verifica in base.php i CDN links

## 📝 ULTIMO STATO FUNZIONANTE

- **Data:** 12 Novembre 2024
- **Ultimo test riuscito:** Smart Focus con fallback locale
- **API OpenAI:** Configurata ma chiave vuota (normale)
- **Database:** beweb_app (non beweb_tirocinio!)
- **User test:** ID=1, name="Test User"

---

**IMPORTANTE:** Questo documento rappresenta l'ULTIMO STATO FUNZIONANTE. Se rompi qualcosa, torna a questo stato!