# Beweb Tirocinio App

Web app interna per gestire Attività, Registro ore, Consegne e Note durante il tirocinio.

## Requisiti

- PHP 8.2+
- MySQL 8+ o MariaDB 10.4+
- Laragon (Windows) o ambiente LAMP/MAMP

## Installazione (Laragon)

### 1. Clona il progetto
```bash
cd C:\laragon\www
# Assicurati che la cartella si chiami "beweb-app"
```

### 2. Installa dipendenze
```bash
cd beweb-app
composer install
```

### 3. Configura ambiente
```bash
# Copia il file di esempio
copy .env.example .env

# Modifica .env con le tue credenziali database
```

### 4. Crea il database
- Apri HeidiSQL o phpMyAdmin
- Crea database `beweb_app` con charset `utf8mb4_unicode_ci`

### 5. Importa schema e dati
```bash
# In HeidiSQL o phpMyAdmin:
# - Apri database/schema.sql ed esegui
# - Apri database/seed.sql ed esegui
```

### 6. Configura virtual host Laragon
- Laragon → Menu → Apache → sites-enabled → Aggiungi nuovo:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/laragon/www/beweb-app/public"
    ServerName beweb.local
    <Directory "C:/laragon/www/beweb-app/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

- Aggiungi a `C:\Windows\System32\drivers\etc\hosts`:
```
127.0.0.1 beweb.local
```

- Riavvia Apache da Laragon

### 7. Accedi all'applicazione
Apri browser: `http://beweb.local`

**Credenziali demo:**
- Admin: `admin@beweb.local` / `admin123`
- Intern: `intern@beweb.local` / `caterina123`

## Struttura Progetto

```
/beweb-app
  /public              # Document root
    index.php          # Front controller
    /assets
      style.css        # Stili custom
      app.js           # JavaScript
  /app
    /Controllers       # Logica applicazione
    /Models            # Accesso dati (PDO)
    /Views             # Template PHP
    helpers.php        # Funzioni globali
  /config
    database.php       # Connessione DB
    routes.php         # Routing
  /database
    schema.sql         # Struttura DB
    seed.sql           # Dati iniziali
  .env                 # Configurazione (gitignored)
  composer.json        # Dipendenze PHP
```

## Funzionalità Principali

### Dashboard
- Focus giornaliero
- 3 scadenze più vicine
- Attività in corso (max 1)
- Ultimi time log (7 giorni)

### Attività
- CRUD completo
- Filtri: progetto, stato, priorità, assegnatario
- Azioni rapide: Inizia, Fatto, +Ore
- Auto-generazione codice (A-001, A-002...)

### Registro Ore
- Tracciamento tempo per attività
- Marcatura blocchi
- Link a output/deliverable

### Consegne
- Gestione file/deliverable
- Stati: In revisione, Approvato, Da rifare
- Collegamento a progetti

### Note
- Decisioni e ipotesi
- Azioni successive con owner e scadenza
- Daily Recap automatico

### Import CSV
- Caricamento dati da Google Sheet
- Anteprima e mappatura colonne
- Gestione conflitti

## Sicurezza

- Password hashate con `password_hash()`
- CSRF token su tutte le POST
- XSS escape in output con `htmlspecialchars()`
- Prepared statements PDO
- Rate limiting su login

## Ruoli

- **Admin (Clemente)**: accesso completo, può eliminare
- **Intern (Caterina)**: può creare/modificare time logs, deliverables, notes; può modificare ma non eliminare attività

## UI/UX

- Bootstrap 5.3 responsive
- ADHD-friendly: font grande, focus ring evidente, spazi generosi
- Shortcuts tastiera: Alt+N (task), Alt+T (time log), Alt+S (salva)
- Toasts per feedback immediato

## Troubleshooting

### Errore "Page not found"
- Verifica che il DocumentRoot punti a `/public`
- Controlla `.htaccess` in `/public` (se usi Apache)

### Errore connessione database
- Verifica credenziali in `.env`
- Assicurati che MySQL sia avviato
- Controlla che il database `beweb_app` esista

### Session issues
- Verifica che `session.save_path` sia scrivibile
- Su Windows: `C:\laragon\tmp`

## Sviluppo Futuro (v2)

- Export CSV
- Email promemoria scadenze
- Upload allegati
- Ruoli granulari per progetto
- API REST
- Dark mode

## Licenza

Uso interno Beweb - Tutti i diritti riservati

## Supporto

Per domande: clemente@beweb.it
