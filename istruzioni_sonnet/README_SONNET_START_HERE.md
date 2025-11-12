# 🚀 QUICK START PER SONNET

**Ciao Sonnet!** Inizia da qui per orientarti velocemente nel progetto Beweb Tirocinio.

## ⚡ SETUP RAPIDO (5 minuti)

### 1. Verifica ambiente
```bash
cd C:\laragon\www\tirocinio\beweb-app
php test_login.php         # Deve dire "Login successful"
php test_smart_focus.php   # Deve mostrare JSON con 3 task
```

### 2. Test browser
- Apri: `http://localhost/tirocinio/public_html/`
- Login: `mario@example.com` / `password123`
- Verifica Smart Focus widget visibile

### 3. Leggi documentazione essenziale
1. `ISTRUZIONI_COMPLETE_PER_SONNET.md` - Contesto completo
2. `HANDOVER_TO_SONNET.md` - Cosa NON toccare

## 📋 TASK PRIORITARI

### 🔴 ALTA PRIORITÀ: Voice to Task
**File:** `01_VOICE_TO_TASK.md`
**Tempo:** 3-4 ore
**Perché ora:** Feature più richiesta dagli utenti ADHD

### 🟡 MEDIA PRIORITÀ: Task Breakdown AI
**File:** `02_TASK_BREAKDOWN_AI.md`
**Tempo:** 4-5 ore
**Perché dopo:** Complementa Voice to Task

### 🟢 BASSA PRIORITÀ: Daily Recap & Insights
**File:** `03_DAILY_RECAP_EMAIL.md`, `04_PATTERN_INSIGHTS.md`
**Tempo:** 8-10 ore totali
**Perché ultimo:** Nice to have, non critico

## ⚠️ REGOLE D'ORO

### MAI fare:
❌ Modificare `bootstrap.php`
❌ Toccare `BaseAIService.php`
❌ Mettere API keys in `.env`
❌ Cambiare Bootstrap da 5 a 4

### SEMPRE fare:
✅ Test dopo ogni modifica
✅ Prepared statements per DB
✅ Commenti in italiano
✅ Fallback se AI fallisce

## 🛠️ COMANDI UTILI

```bash
# Test rapido tutto
php test_login.php && php test_smart_focus.php

# Vedere log errori
tail -f C:/laragon/www/tirocinio/beweb-app/error.log

# Check database
mysql -u root beweb_app -e "SELECT COUNT(*) FROM tasks;"

# Backup veloce
cp -r app app_backup_$(date +%Y%m%d)
```

## 🔧 PATTERN DA SEGUIRE

### PHP
```php
// GIUSTO
if ($condition) {
    $result = $this->method($param);
}

// SBAGLIATO
if($condition){$result=$this->method($param);}
```

### JavaScript
```javascript
// USA vanilla JS, no jQuery
fetch('/ai/smart-focus', {
    method: 'POST',
    body: JSON.stringify(data)
});
```

### Database
```php
// SEMPRE prepared statements
$stmt = $db->prepare('SELECT * FROM tasks WHERE id = ?');
$stmt->execute([$id]);
```

## 📱 CONTATTI RAPIDI

### Se Smart Focus non funziona:
```php
// In AIController.php cambia:
$service = new ADHDSmartFocusService(); // Fallback
```

### Se login non funziona:
```bash
php test_login.php
```

### Se UI rotta:
Verifica Bootstrap sia 5.3 in `base.php`

## 🎯 OBIETTIVO FINALE

L'app deve aiutare persone con ADHD a:
- ✨ Superare paralisi decisionale
- 🎯 Mantenere focus
- 🎤 Catturare idee vocalmente
- 📊 Analizzare produttività
- ⏱️ Usare tecnica Pomodoro

## 💪 INIZIA SUBITO!

1. Leggi `01_VOICE_TO_TASK.md`
2. Implementa Voice to Task
3. Testa con `test_smart_focus.php`
4. Procedi con task successivo

---

**REMEMBER:** Codice funzionante > Codice perfetto. L'utente vuole features che funzionano, non teoria!

Buon lavoro! 🚀