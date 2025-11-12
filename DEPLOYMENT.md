# 🚀 Deployment Guide - ADHD SaaS

## 📋 Prerequisiti

- Server con PHP 8.1+ e MySQL/MariaDB
- Accesso SSH al server
- Composer installato
- Git installato

## 🎯 Deployment su SiteGround (Produzione)

### 1. Clona Repository sul Server

```bash
# Connettiti via SSH
ssh root@tirocinio.clementeteodonno.it

# Vai nella directory public_html
cd /home/customer/www/tirocinio.clementeteodonno.it

# Clona il repository
git clone https://github.com/BewebSolution/adhd-saas.git app

# Entra nella directory
cd app
```

### 2. Configurazione Environment

```bash
# Copia il file di configurazione produzione
cp .env.production .env

# Modifica con le credenziali corrette
nano .env
```

**Configurazioni da aggiornare in .env:**

```env
# Database
DB_HOST=127.0.0.1
DB_DATABASE=tuo_database
DB_USERNAME=tuo_user
DB_PASSWORD=tua_password_sicura

# Google OAuth (se necessario)
GOOGLE_CLIENT_ID=xxx
GOOGLE_CLIENT_SECRET=xxx
```

### 3. Installa Dipendenze

```bash
# Install composer dependencies
composer install --no-dev --optimize-autoloader
```

### 4. Setup Permessi

```bash
# Permessi directories
chmod 755 app config public
chmod -R 755 app/Views

# Crea e configura temp/cache
mkdir -p temp/audio cache
chmod 777 temp/audio cache
```

### 5. Configura Web Server

**Per SiteGround (cPanel):**

1. Vai in cPanel → "Setup Python App" o "Select PHP Version"
2. Seleziona PHP 8.1 o superiore
3. Imposta Document Root su: `/home/customer/www/tirocinio.clementeteodonno.it/app/public`

**File .htaccess in public/:**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Redirect to index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>
```

### 6. Importa Database

```bash
# Se hai un dump SQL
mysql -u username -p database_name < database.sql

# Oppure crea manualmente le tabelle (vedi schema SQL sotto)
```

### 7. Configura API Keys

1. Vai su: `https://tirocinio.clementeteodonno.it/login`
2. Login come admin
3. Vai su: Impostazioni → AI e API Keys
4. Inserisci OpenAI API Key

### 8. Test Deployment

```bash
# Test connessione database
php -r "require 'bootstrap.php'; echo 'DB OK';"

# Test homepage
curl https://tirocinio.clementeteodonno.it
```

## 🔄 Aggiornamenti Futuri

### Deploy Update Automatico

Usa lo script di deployment:

```bash
# Da Windows (locale)
cd C:\laragon\www\tirocinio\beweb-app
bash deploy.sh production
```

### Deploy Manuale via SSH

```bash
# Connettiti al server
ssh root@tirocinio.clementeteodonno.it

# Vai nella directory app
cd /home/customer/www/tirocinio.clementeteodonno.it/app

# Pull ultimi aggiornamenti
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Clear cache
rm -rf cache/*

# Restart PHP (se necessario)
# touch tmp/restart.txt
```

## 💾 Database Schema

### Tabelle Principali

```sql
-- Users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Projects
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tasks
CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    project_id INT,
    status ENUM('Da fare', 'In corso', 'In revisione', 'Fatto') DEFAULT 'Da fare',
    priority ENUM('Alta', 'Media', 'Bassa') DEFAULT 'Media',
    assignee VARCHAR(255),
    due_at DATE,
    hours_estimated DECIMAL(5,2),
    hours_spent DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

-- AI Settings
CREATE TABLE ai_settings (
    user_id INT PRIMARY KEY,
    openai_api_key VARCHAR(255),
    claude_api_key VARCHAR(255),
    ai_provider ENUM('openai', 'claude') DEFAULT 'openai',
    smart_focus_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- AI Cache
CREATE TABLE ai_cache (
    cache_key VARCHAR(255) PRIMARY KEY,
    response TEXT NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expires (expires_at)
);

-- Suggestion History
CREATE TABLE suggestion_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    task_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Time Logs
CREATE TABLE time_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    person VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    hours DECIMAL(5,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);
```

### User di Default

```sql
-- Admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@beweb.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Demo user (password: demo123)
INSERT INTO users (name, email, password, role) VALUES
('Demo User', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');
```

## 🔐 Sicurezza

### Checklist Produzione

- [ ] `APP_DEBUG=false` in .env
- [ ] Password database sicura
- [ ] API keys in database, NON in .env
- [ ] .env NON committato su Git
- [ ] Permessi corretti (755 per directories, 644 per files)
- [ ] temp/ e cache/ con permessi 777
- [ ] HTTPS abilitato (certificato SSL)
- [ ] Firewall configurato
- [ ] Backup automatici database

### Backup Automatico

```bash
# Aggiungi a crontab per backup giornaliero
0 2 * * * /usr/bin/mysqldump -u username -ppassword database_name > /backups/db_$(date +\%Y\%m\%d).sql
```

## 🐛 Troubleshooting

### Problema: 500 Internal Server Error

**Soluzione:**
```bash
# Controlla error log
tail -f /home/customer/logs/error_log

# Verifica permessi
chmod 755 app config public
chmod -R 755 app/Views
```

### Problema: Database Connection Failed

**Soluzione:**
```bash
# Verifica credenziali in .env
cat .env | grep DB_

# Test connessione
php -r "new PDO('mysql:host=127.0.0.1;dbname=db', 'user', 'pass');"
```

### Problema: Smart Focus non funziona

**Soluzione:**
1. Verifica API key in `/ai/settings`
2. Check cache: `ls -la cache/`
3. Fallback automatico a servizio locale

### Problema: Composer non trovato

**Soluzione:**
```bash
# Installa composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

## 📞 Supporto

- **Repository:** https://github.com/BewebSolution/adhd-saas
- **Issues:** https://github.com/BewebSolution/adhd-saas/issues
- **Docs:** Vedi `/istruzioni_sonnet/` per documentazione Sonnet

## 🎉 Post-Deployment

Dopo il deployment:

1. **Test Login:** `https://tirocinio.clementeteodonno.it/login`
2. **Configura AI:** Vai su Impostazioni → AI Keys
3. **Crea Progetti:** Aggiungi almeno 2-3 progetti
4. **Crea Tasks:** Aggiungi task di test
5. **Test Smart Focus:** Prova il suggeritore AI
6. **Google OAuth:** (opzionale) Configura per import tasks

---

**Nota:** Mantieni sempre un backup prima di ogni deploy!