# 🚨 HANDOVER DOCUMENTATION - DA LEGGERE PRIMA DI TOCCARE QUALSIASI COSA

## ⛔ REGOLE FONDAMENTALI - NON VIOLARE MAI

1. **NON TOCCARE MAI questi file senza permesso esplicito:**
   - `bootstrap.php` - Core del sistema
   - `app/Services/BaseAIService.php` - Base per tutti i servizi AI
   - `app/Views/layouts/base.php` - Layout principale (Bootstrap 5)
   - `.env` - NON mettere API keys qui!

2. **API Keys vanno SEMPRE nel DATABASE, MAI in .env:**
   - Le chiavi si inseriscono da: Impostazioni → AI e API Keys
   - Tabella: `ai_settings`
   - Campo: `openai_api_key`

3. **Bootstrap Version = 5.3.0**
   - NON cambiare a Bootstrap 4
   - SB Admin 2 è già adattato per Bootstrap 5

## 📁 STRUTTURA PROGETTO

```
beweb-app/
├── app/
│   ├── Controllers/
│   │   ├── AIController.php          ← Smart Focus endpoint
│   │   ├── AIImportController.php    ← Import Google Tasks
│   │   └── TaskController.php        ← Gestione tasks
│   ├── Services/
│   │   ├── AISmartFocusService.php   ← ✅ AI con OpenAI (NUOVO)
│   │   ├── ADHDSmartFocusService.php ← Fallback locale
│   │   └── BaseAIService.php         ← NON TOCCARE
│   └── Views/
│       └── dashboard/index.php       ← UI Smart Focus
├── public_html/
│   └── index.php                     ← Entry point
└── .env                              ← Config base (NO API KEYS!)
```

## 🎯 STATO ATTUALE - COSA FUNZIONA

### ✅ COMPLETATO E FUNZIONANTE:
1. **Smart Focus ADHD** - Suggerimenti task personalizzati
   - Con AI (se configurata chiave OpenAI)
   - Fallback locale sempre disponibile
   - Mostra 1 task principale + 2 alternative
   - Considera energia, tempo, umore

2. **Import Google Tasks** - Funziona ma senza mappatura automatica progetti

3. **UI Dashboard** - Bootstrap 5 + SB Admin 2
   - Widget energia/tempo/umore
   - Sezione "Focus di oggi" RIMOSSA (integrata in Smart Focus)

### ⚠️ DA COMPLETARE:

#### 1. POMODORO TIMER (Priorità Alta)
**File da modificare:** `app/Views/dashboard/index.php`

**Requisiti:**
- Timer 25 minuti lavoro + 5 pausa
- Integrato nel task suggerito da Smart Focus
- Notifiche browser quando finisce
- Suono opzionale
- Contatore pomodori completati

**Dove aggiungere:**
```javascript
// Dopo displayADHDFocusResult() function
function startPomodoro(taskId) {
    // 25 minuti = 1500 secondi
    // Salvare in localStorage
    // Mostrare countdown
    // Notifica al termine
}
```

#### 2. FIX IMPORT AI (Priorità Media)
**File:** `app/Controllers/AIImportController.php`

**Problema:** Mappatura progetti non automatica

**Soluzione:**
```php
// Riga ~120 in processWithAI()
// Quando AI restituisce task, deve anche mappare:
// "project": "nome_progetto_suggerito"
```

#### 3. QUICK NOTES WIDGET (Priorità Bassa)
**Nuovo file:** `app/Views/widgets/quick_notes.php`

**Scopo:** Catturare distrazioni ADHD senza perdere focus

**Features:**
- Textarea sempre visibile in dashboard
- Salva automatico ogni 5 secondi
- Timestamp automatico
- Max 5 note recenti visibili

## 🔧 COME LAVORARE SENZA ROMPERE

### Prima di OGNI modifica:
```bash
# 1. Verifica che funzioni ancora
php test_smart_focus.php
php test_ai_smart_focus.php

# 2. Controlla login
php test_login.php
```

### Per aggiungere features:
1. **MAI modificare file esistenti se non necessario**
2. **Crea NUOVI file quando possibile**
3. **Usa il pattern esistente:**
   ```php
   class NuovoService extends BaseAIService {
       // Il tuo codice
   }
   ```

### Database queries:
```php
// SEMPRE usa prepared statements
$stmt = $db->prepare('SELECT * FROM tasks WHERE id = ?');
$stmt->execute([$id]);

// MAI fare questo:
$db->query("SELECT * FROM tasks WHERE id = $id"); // SQL INJECTION!
```

## 🐛 PROBLEMI COMUNI E SOLUZIONI

### "Call to undefined function auth()"
```php
// Aggiungi all'inizio del file:
require_once __DIR__ . '/../../bootstrap.php';
```

### "Smart Focus non funziona"
1. Controlla se loggato: `test_login.php`
2. Verifica API key in DB: `SELECT * FROM ai_settings`
3. Usa fallback: `ADHDSmartFocusService` invece di `AISmartFocusService`

### "Bootstrap non funziona"
- NON cambiare versione!
- Usa Bootstrap 5 classes:
  - `ml-2` → `ms-2`
  - `mr-2` → `me-2`
  - `pl-3` → `ps-3`

## 📝 PROMPT OTTIMALE PER SONNET

Quando chiedi a Sonnet di fare qualcosa, usa questo formato:

```
CONTESTO:
- App PHP gestione task per ADHD
- Bootstrap 5 + SB Admin 2
- API keys nel DATABASE, non .env
- Smart Focus usa AI o fallback locale

TASK:
[Descrivi cosa fare]

VINCOLI:
- NON modificare BaseAIService.php
- NON cambiare Bootstrap version
- NON mettere API keys in .env
- USA prepared statements per DB
- TESTA con test_smart_focus.php

FILE DA MODIFICARE:
[Lista esatta dei file]

EXPECTED OUTPUT:
[Cosa deve succedere]
```

## 🚀 PROSSIMI PASSI SUGGERITI

1. **Implementa Pomodoro Timer** (2-3 ore)
   - Più utile per utenti ADHD
   - Relativamente semplice (solo JS)

2. **Fix Import AI** (1-2 ore)
   - Migliora UX significativamente
   - Richiede solo aggiustamento prompt

3. **Quick Notes** (1 ora)
   - Nice to have
   - Può aspettare

## ⚠️ ULTIMO AVVERTIMENTO

**SE ROMPI QUALCOSA:**
1. Git restore immediate
2. Controlla test files
3. Verifica login funziona
4. Database backup disponibile in `/backups/`

**CONTATTI EMERGENZA:**
- Se Smart Focus non funziona → usa `ADHDSmartFocusService`
- Se AI non risponde → controlla API key in `ai_settings`
- Se login rotto → controlla `$_SESSION['user']`

---

## 📊 METRICHE SUCCESSO

Il tuo lavoro è riuscito se:
- ✅ `test_smart_focus.php` passa
- ✅ Login funziona
- ✅ Smart Focus suggerisce task
- ✅ Nessun errore PHP in console
- ✅ Bootstrap UI intatta

## 🎯 FOCUS PRINCIPALE

**OBIETTIVO #1:** Implementare Pomodoro Timer SENZA rompere Smart Focus

Buon lavoro e... NON TOCCARE BaseAIService.php! 😤