# ISTRUZIONI CONFIGURAZIONE NUOVO DOMINIO/INSTALLAZIONE
## Per installare il progetto su nuovo PC o dominio

---

## 📋 REQUISITI SISTEMA

- **PHP**: >= 8.0 (testato su 8.3)
- **MySQL**: >= 5.7 o MariaDB >= 10.3
- **Composer**: >= 2.0
- **Web Server**: Apache/Nginx (o PHP built-in per dev)
- **Git**: Per clonare repository

---

## 🚀 INSTALLAZIONE LOCALE (NUOVO PC)

### 1. Clona Repository
```bash
git clone https://github.com/BewebSolution/adhd-saas.git
cd adhd-saas
```

### 2. Installa Dipendenze
```bash
composer install
```

### 3. Crea Database
```sql
CREATE DATABASE tirocinio_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Configura Environment
Crea file `.env` nella root del progetto:
```env
# Database
DB_HOST=localhost
DB_NAME=tirocinio_db
DB_USERNAME=root
DB_PASSWORD=

# App Settings
APP_ENV=local
APP_DEBUG=true
APP_BASE_PATH=/tirocinio/beweb-app/public

# API Keys (opzionali, configurabili anche da UI)
OPENAI_API_KEY=sk-...
CLAUDE_API_KEY=sk-ant-...

# Google OAuth (per Import Google Tasks)
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
```

### 5. Permessi Directory
```bash
# Su Windows (Laragon/XAMPP) di solito non serve
# Su Linux/Mac:
chmod -R 755 public/
chmod -R 777 storage/  # se esiste
```

### 6. Configurazione Apache/Nginx

#### Apache (.htaccess già incluso)
```apache
<VirtualHost *:80>
    ServerName tirocinio.local
    DocumentRoot "C:/laragon/www/tirocinio/beweb-app/public"

    <Directory "C:/laragon/www/tirocinio/beweb-app/public">
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
    server_name tirocinio.local;
    root /path/to/beweb-app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 7. Hosts File (opzionale)
Aggiungi a `C:\Windows\System32\drivers\etc\hosts` (Windows) o `/etc/hosts` (Linux/Mac):
```
127.0.0.1   tirocinio.local
```

### 8. Primo Accesso
1. Naviga a `http://localhost/tirocinio/beweb-app/public/` o `http://tirocinio.local`
2. Il database verrà inizializzato automaticamente al primo accesso
3. Crea primo utente admin navigando a `/login`

---

## 🌐 DEPLOYMENT PRODUZIONE (NUOVO DOMINIO)

### 1. Upload Files
```bash
# Via Git (consigliato)
git clone https://github.com/BewebSolution/adhd-saas.git
cd adhd-saas
composer install --no-dev --optimize-autoloader

# O via FTP/SFTP
# Upload tutti i file tranne:
# - .git/
# - .env (crealo sul server)
# - node_modules/ (se presenti)
```

### 2. Configura Database Produzione
```sql
CREATE DATABASE nome_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nome_utente'@'localhost' IDENTIFIED BY 'password_sicura';
GRANT ALL PRIVILEGES ON nome_database.* TO 'nome_utente'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Environment Produzione
Crea `.env` sul server:
```env
# Database
DB_HOST=localhost
DB_NAME=nome_database
DB_USERNAME=nome_utente
DB_PASSWORD=password_sicura

# App Settings
APP_ENV=production
APP_DEBUG=false
APP_BASE_PATH=

# API Keys (inserire da UI dopo login)
# OPENAI_API_KEY=
# CLAUDE_API_KEY=

# Google OAuth
GOOGLE_CLIENT_ID=your-production-client-id
GOOGLE_CLIENT_SECRET=your-production-secret
```

### 4. Configurazione Dominio

#### Document Root
Punta il dominio a: `/path/to/beweb-app/public`

#### SSL Certificate
```bash
# Con Certbot (Let's Encrypt)
certbot --apache -d tuodominio.com -d www.tuodominio.com
```

#### Redirect HTTPS (.htaccess)
Aggiungi in `public/.htaccess`:
```apache
# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
```

### 5. Google OAuth Setup
1. Vai su [Google Cloud Console](https://console.cloud.google.com)
2. Crea nuovo progetto o seleziona esistente
3. Abilita Google Tasks API
4. Crea credenziali OAuth 2.0
5. Aggiungi Authorized redirect URIs:
   - `https://tuodominio.com/ai/import/oauth-callback`
   - `http://localhost/tirocinio/beweb-app/public/ai/import/oauth-callback` (per test locale)

### 6. Cron Jobs (opzionale)
Per notifiche scheduled (se implementate):
```bash
# Crontab
*/5 * * * * php /path/to/beweb-app/cron/process_notifications.php
```

---

## 🔧 TROUBLESHOOTING

### Errore 500
- Controlla logs: `tail -f /path/to/error_log`
- Verifica permessi directory
- Controlla `.htaccess` e mod_rewrite

### Database Connection Error
- Verifica credenziali in `.env`
- Controlla che MySQL sia in esecuzione
- Verifica firewall/porte

### Assets non caricano
- Controlla `APP_BASE_PATH` in `.env`
- Verifica che `public/assets/` sia accessibile
- Clear browser cache

### OAuth non funziona
- Verifica redirect URI in Google Console
- Controlla GOOGLE_CLIENT_ID e SECRET
- Assicurati HTTPS in produzione

### Sidebar mobile non funziona
- Clear cache browser
- Verifica che `mobile-responsive.css` sia caricato
- Controlla console JavaScript per errori

---

## 📝 CONFIGURAZIONE POST-INSTALLAZIONE

### 1. Crea Admin User
```sql
INSERT INTO users (name, email, password, role, created_at)
VALUES ('Admin', 'admin@example.com', '$2y$10$[hash_password]', 'admin', NOW());
```

### 2. Configura API Keys
1. Login come admin
2. Vai a `/ai/settings`
3. Inserisci OpenAI/Claude API keys

### 3. Configura Liste Predefinite
1. Vai a `/settings/lists`
2. Aggiungi stati task, priorità, persone

### 4. Test Funzionalità
- [ ] Login/Logout
- [ ] CRUD Task
- [ ] Timer Pomodoro
- [ ] Smart Focus AI
- [ ] Import Google Tasks
- [ ] Mobile responsive

---

## 🔄 AGGIORNAMENTO DA GIT

```bash
# Backup database prima!
mysqldump -u user -p database > backup.sql

# Pull updates
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Clear caches (se implementato)
php artisan cache:clear  # se usi Laravel
# o manualmente
rm -rf storage/cache/*
```

---

## 📞 SUPPORTO

- **Repository**: https://github.com/BewebSolution/adhd-saas
- **Issues**: https://github.com/BewebSolution/adhd-saas/issues
- **Docs Claude**: `.claude/` directory in questo progetto

---

Fine documento configurazione.