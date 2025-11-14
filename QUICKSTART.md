# 🚀 QUICKSTART - Setup Rapido ADHD SaaS

> **Nuovo sistema di configurazione automatica!**
> Non serve più modificare manualmente path e domini - tutto viene auto-rilevato.

---

## ⚡ Setup in 3 Step (Qualsiasi Ambiente)

### 1️⃣ Clona e installa

```bash
# Clona repository
git clone https://github.com/BewebSolution/adhd-saas.git
cd adhd-saas

# Esegui setup automatico (interattivo)
php setup.php
```

Lo script configurerà automaticamente:
- ✅ Environment file (`.env`)
- ✅ Auto-detection di path e domini
- ✅ Connessione database
- ✅ Creazione database (opzionale)
- ✅ Verifica permessi

### 2️⃣ Configura web server

**Importante:** Document Root deve puntare a `/public`

#### Apache (Virtual Host)

```apache
<VirtualHost *:80>
    DocumentRoot "/path/to/adhd-saas/public"
    ServerName beweb.local

    <Directory "/path/to/adhd-saas/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name beweb.local;
    root /path/to/adhd-saas/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### PHP Built-in Server (solo sviluppo)

```bash
php -S localhost:8000 -t public
```

### 3️⃣ Accedi all'app

```
http://localhost    (o il tuo dominio)
```

**Credenziali default:**
- Email: `admin@beweb.local`
- Password: `admin123`

---

## 🔄 Spostare tra Ambienti

### Da Locale → Produzione

```bash
# 1. Sul server, clona repository
git clone https://github.com/BewebSolution/adhd-saas.git app
cd app

# 2. Esegui setup e scegli "Produzione"
php setup.php

# 3. Configura web server (DocumentRoot → public/)

# 4. Importa database (se necessario)
mysql -u user -p database_name < backup.sql
```

### Da Produzione → Locale

```bash
# 1. Sul locale, clona repository
git clone https://github.com/BewebSolution/adhd-saas.git
cd adhd-saas

# 2. Esegui setup e scegli "Locale"
php setup.php

# 3. Scarica backup database da produzione
mysqldump -h remote -u user -p database_name > local_backup.sql

# 4. Importa localmente
mysql -u root -p beweb_app < local_backup.sql
```

**Fatto!** Il sistema si adatta automaticamente al nuovo ambiente.

---

## 🎯 Come Funziona l'Auto-Detection

### Path Auto-Detection

Il sistema **rileva automaticamente** il base path confrontando:
- `$_SERVER['DOCUMENT_ROOT']` (es. `/var/www/html`)
- `$_SERVER['SCRIPT_FILENAME']` (es. `/var/www/html/app/public/index.php`)

**Esempi:**

| Scenario | Document Root | Script | Base Path rilevato |
|----------|---------------|--------|-------------------|
| Root | `/var/www/html/public` | `/var/www/html/public/index.php` | `` (vuoto) |
| Subdirectory | `/var/www/html` | `/var/www/html/app/public/index.php` | `/app` |
| Laragon | `C:/laragon/www` | `C:/laragon/www/beweb/public/index.php` | `/beweb` |

### Domain Auto-Detection

Rileva automaticamente:
- **Protocol**: HTTP o HTTPS (anche dietro proxy)
- **Host**: `$_SERVER['HTTP_HOST']`
- **Port**: Se non standard (80/443)

---

## ⚙️ Configurazione Manuale (Opzionale)

Se l'auto-detection non funziona, puoi forzare i valori in `.env`:

```env
# Forza base path specifico
APP_BASE_PATH=/tirocinio

# Forza URL completo
APP_URL=https://miodominio.it/app

# Forza asset path (se diverso da base path)
APP_ASSET_PATH=/tirocinio/public
```

**Nota:** Se lasci vuoto `APP_BASE_PATH`, l'auto-detection si attiva automaticamente.

---

## 🔍 Verificare la Configurazione

### Test path rilevati

Crea `test_paths.php` nella root:

```php
<?php
require 'vendor/autoload.php';
require 'app/helpers.php';

echo "Base Path auto-rilevato: " . auto_detect_base_path() . "\n";
echo "Base URL: " . base_url() . "\n";
echo "URL /tasks: " . url('/tasks') . "\n";
echo "Asset style.css: " . asset('assets/style.css') . "\n";
```

Esegui:

```bash
php test_paths.php
```

Output atteso:

```
Base Path auto-rilevato: /app
Base URL: http://localhost/app
URL /tasks: /app/tasks
Asset style.css: /app/assets/style.css
```

---

## 🐛 Troubleshooting

### Problema: 404 su tutte le pagine

**Causa:** Document Root non punta a `/public`

**Soluzione:**
1. Verifica virtual host punta a `path/to/adhd-saas/public`
2. Riavvia web server

### Problema: Assets non caricano (404)

**Causa:** Base path non rilevato correttamente

**Soluzione:**
1. Verifica `echo auto_detect_base_path();`
2. Se sbagliato, forza in `.env`: `APP_BASE_PATH=/percorso/corretto`

### Problema: Redirect infinito dopo login

**Causa:** Session path non scrivibile

**Soluzione:**

```bash
# Linux
chmod 777 /var/lib/php/sessions

# Windows (Laragon)
# Verifica C:\laragon\tmp sia scrivibile
```

### Problema: Database connection failed

**Causa:** Credenziali errate in `.env`

**Soluzione:**

```bash
# Ri-esegui setup
php setup.php

# Oppure modifica manualmente .env
nano .env  # verifica DB_HOST, DB_USERNAME, DB_PASSWORD
```

---

## 📚 Risorse

- **Setup completo:** [SETUP.md](SETUP.md)
- **Deployment produzione:** [DEPLOYMENT.md](DEPLOYMENT.md)
- **Guida AI assistants:** [CLAUDE.md](CLAUDE.md)
- **Documentazione completa:** [istruzioni_sonnet/](istruzioni_sonnet/)

---

## ✅ Checklist Pre-Deploy

Prima di mettere in produzione:

- [ ] `php setup.php` eseguito con opzione "Produzione"
- [ ] `APP_DEBUG=false` in `.env`
- [ ] Document Root punta a `/public`
- [ ] Database creato e importato
- [ ] API Keys configurate in `/ai/settings` (non in `.env`)
- [ ] Permessi corretti (`chmod 755 app config public`)
- [ ] SSL/HTTPS abilitato
- [ ] Backup automatici configurati

---

**Fatto! 🎉** L'app ora funziona su qualsiasi ambiente senza modifiche manuali.

Per domande: clemente@beweb.it
