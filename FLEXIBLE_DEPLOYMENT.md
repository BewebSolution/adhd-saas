# 🚀 Sistema di Deployment Flessibile

## Panoramica

Questa applicazione ora utilizza un **sistema di configurazione dinamica** che rileva automaticamente l'ambiente e configura i percorsi di conseguenza. Non è più necessario modificare manualmente percorsi e URL quando si passa da locale a produzione!

## Come Funziona

### 1. Classe AppConfig (`app/Config/AppConfig.php`)

La classe `AppConfig` è il cuore del sistema:

- **Rileva automaticamente l'ambiente** basandosi su `$_SERVER['HTTP_HOST']`
- **Configura i percorsi dinamicamente**:
  - **Locale** (localhost): Usa `/tirocinio/beweb-app/public` come base path
  - **Produzione** (altri domini): Usa la root del dominio (base path vuoto)
- **Configura il database automaticamente**:
  - **Locale**: `root` senza password su `beweb_app` (default XAMPP/Laragon)
  - **Produzione**: Legge credenziali da `.env` o usa defaults sicuri

### 2. Helper Functions Aggiornate

Le funzioni helper in `app/helpers.php` ora utilizzano `AppConfig`:

```php
// Genera URL corretti automaticamente
url('/login')  // → http://localhost/tirocinio/beweb-app/public/login (locale)
               // → https://tirocinio.clementeteodonno.it/login (produzione)

// Percorsi per asset
asset('css/style.css')  // → /tirocinio/beweb-app/public/assets/css/style.css (locale)
                        // → /assets/css/style.css (produzione)
```

### 3. Views e JavaScript

Le views utilizzano la configurazione dinamica:

```javascript
// In base.php e sb_admin.php
const BASE_URL = '<?= \App\Config\AppConfig::getInstance()->get('base_path', '') ?>';
```

## Deployment

### Per Ambiente Locale

1. **Nessuna configurazione richiesta!** Il sistema rileva automaticamente che sei su localhost
2. (Opzionale) Copia `.env.local.example` in `.env` per personalizzare:
   ```bash
   cp .env.local.example .env
   ```

### Per Produzione

1. **Carica i file sul server** (via FTP, Git, o deploy.sh)
2. **Copia `.env.production` in `.env`** sul server:
   ```bash
   cp .env.production .env
   ```
3. **Aggiorna le credenziali del database** nel file `.env`
4. **Fatto!** Il sistema rileverà automaticamente che è in produzione

## File di Configurazione

### `.env.local.example`
Template per ambiente locale. Include:
- Database locale (root senza password)
- Debug mode attivo
- Configurazioni di sviluppo

### `.env.production`
Template per produzione. Include:
- Database di produzione
- Debug mode disattivato
- URL di produzione

## Database Flessibile

### Configurazione Automatica

Il sistema configura automaticamente anche il database:

#### Ambiente Locale (localhost)
```php
Host: 127.0.0.1
Database: beweb_app
Username: root
Password: (vuota)
```

#### Ambiente Produzione
```php
Host: 127.0.0.1 (o da .env)
Database: beweb_app_prod (o da .env)
Username: beweb_user (o da .env)
Password: (da .env)
```

### Come Funziona

1. **AppConfig rileva l'ambiente**
2. **Applica configurazioni database appropriate**:
   - Locale: usa defaults per XAMPP/Laragon
   - Produzione: richiede .env con credenziali sicure
3. **`database.php` usa automaticamente AppConfig**

### Override Manuale

Puoi sempre sovrascrivere nel `.env`:

```env
DB_HOST=custom-server.com
DB_DATABASE=my_custom_db
DB_USERNAME=my_user
DB_PASSWORD=secure_password
DB_PORT=3307
```

## Vantaggi del Sistema

✅ **Zero modifiche al codice** quando si passa da locale a produzione
✅ **Rilevamento automatico** dell'ambiente
✅ **Percorsi sempre corretti** senza hardcoding
✅ **Database auto-configurato** per ogni ambiente
✅ **Facile da estendere** per nuovi ambienti
✅ **Compatibile** con qualsiasi struttura di directory

## Testing

Usa il file `test_config.php` per verificare la configurazione:

```bash
php test_config.php
```

Mostra:
- Ambiente rilevato
- Percorsi generati
- URL di esempio
- Conferma che tutto funziona

## Personalizzazione

Se necessario, puoi sovrascrivere le impostazioni automatiche nel file `.env`:

```env
# Forza un base path specifico (raramente necessario)
APP_BASE_PATH=/my-custom-path

# Forza ambiente specifico
APP_ENV=staging

# Abilita/disabilita debug
APP_DEBUG=true
```

## Migrazione da Vecchio Sistema

Se stai aggiornando da una versione precedente:

1. **Rimuovi tutti i percorsi hardcoded** come `/tirocinio/public_html`
2. **Usa sempre le helper functions**:
   - `url()` per generare URL
   - `asset()` per percorsi agli asset
   - `current_path()` per ottenere il percorso corrente
3. **Non modificare più** i percorsi quando fai deploy!

## Troubleshooting

### Percorsi non corretti in locale?
- Verifica che `HTTP_HOST` sia `localhost` o `127.0.0.1`
- Controlla con `test_config.php`

### Percorsi non corretti in produzione?
- Verifica che il dominio non sia `localhost`
- Assicurati che `.env` non forzi `APP_BASE_PATH`

### Asset non caricati?
- Usa sempre `asset()` helper, mai percorsi hardcoded
- Verifica che la cartella `assets` sia nella posizione corretta

## Supporto Multi-Dominio

Il sistema supporta automaticamente deployment su domini diversi:

- `localhost/tirocinio/beweb-app/public` ✅
- `tirocinio.clementeteodonno.it` ✅
- `app.esempio.com` ✅
- `staging.mysite.com` ✅

Funziona ovunque senza modifiche!

---

**Nota:** Questo sistema è stato implementato per risolvere il problema dei percorsi hardcoded e rendere l'applicazione veramente portabile tra ambienti diversi.