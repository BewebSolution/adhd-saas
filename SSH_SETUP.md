# 🔐 SSH Setup Guide - ADHD SaaS

Guida rapida per configurare l'accesso SSH a GitHub e SiteGround.

---

## 📋 Indice

1. [Setup SSH per GitHub](#setup-ssh-per-github)
2. [Setup SSH per SiteGround](#setup-ssh-per-siteground)
3. [Troubleshooting Comune](#troubleshooting-comune)

---

## 🐙 Setup SSH per GitHub

### Step 1: Verifica chiavi esistenti

```bash
ls -la ~/.ssh
```

Se vedi file tipo `id_rsa.pub` o `id_ed25519.pub`, hai già una chiave. Salta allo Step 3.

### Step 2: Genera nuova chiave SSH

```bash
# Genera chiave moderna ed25519
ssh-keygen -t ed25519 -C "tua_email@example.com"

# Premi Enter per accettare il path predefinito
# Inserisci passphrase (raccomandato) o lascia vuoto
```

**Output:**
```
Generating public/private ed25519 key pair.
Enter file in which to save the key (/home/user/.ssh/id_ed25519):
Enter passphrase (empty for no passphrase):
Your identification has been saved in /home/user/.ssh/id_ed25519
Your public key has been saved in /home/user/.ssh/id_ed25519.pub
```

### Step 3: Copia chiave pubblica

**Linux/Mac:**
```bash
cat ~/.ssh/id_ed25519.pub
```

**Windows (PowerShell):**
```powershell
type $env:USERPROFILE\.ssh\id_ed25519.pub
```

**Windows (Git Bash):**
```bash
cat ~/.ssh/id_ed25519.pub
```

Copia tutto l'output (inizia con `ssh-ed25519 AAAA...`)

### Step 4: Aggiungi chiave a GitHub

1. Vai su [github.com/settings/keys](https://github.com/settings/keys)
2. Click **"New SSH key"**
3. **Title:** "Mio Computer Locale" (o nome descrittivo)
4. **Key:** Incolla la chiave pubblica copiata
5. Click **"Add SSH key"**

### Step 5: Testa connessione

```bash
ssh -T git@github.com
```

**Output atteso:**
```
Hi username! You've successfully authenticated, but GitHub does not provide shell access.
```

✅ **Successo!** Ora puoi clonare con SSH:

```bash
git clone git@github.com:BewebSolution/adhd-saas.git
```

### Step 6: Configura Git (prima volta)

```bash
git config --global user.name "Tuo Nome"
git config --global user.email "tua_email@example.com"

# Verifica configurazione
git config --global --list
```

---

## 🌐 Setup SSH per SiteGround

### Informazioni Server

| Parametro | Valore |
|-----------|--------|
| **Host** | `tirocinio.clementeteodonno.it` |
| **User** | `beweb` (verifica su cPanel) |
| **Port** | `22` |
| **Web Root** | `/home/customer/www/tirocinio.clementeteodonno.it/public_html` |

### Metodo 1: Chiave SSH (Raccomandato)

#### 1. Genera chiave dedicata

```bash
# Chiave separata per produzione (più sicuro)
ssh-keygen -t ed25519 -C "siteground-production" -f ~/.ssh/id_siteground

# Copia chiave pubblica
cat ~/.ssh/id_siteground.pub
```

#### 2. Aggiungi chiave su SiteGround

**Via cPanel:**

1. Login su [SiteGround Site Tools](https://my.siteground.com/)
2. Vai su **"Devs" → "SSH Keys Manager"**
3. Click **"Import Key"**
4. Incolla la chiave pubblica
5. Click **"Authorize"**

**Via SSH (se hai già accesso):**

```bash
# Connettiti con password (prima volta)
ssh beweb@tirocinio.clementeteodonno.it

# Aggiungi chiave al authorized_keys
echo "tua-chiave-pubblica-qui" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys

# Logout e riconnetti con chiave
exit
```

#### 3. Configura SSH client locale

Crea/edita file config:

```bash
# Linux/Mac
nano ~/.ssh/config

# Windows
notepad %USERPROFILE%\.ssh\config
```

Aggiungi questa configurazione:

```
# SiteGround Production Server
Host siteground-prod
    HostName tirocinio.clementeteodonno.it
    User beweb
    Port 22
    IdentityFile ~/.ssh/id_siteground
    IdentitiesOnly yes
    ServerAliveInterval 60
    ServerAliveCountMax 10
```

Salva e imposta permessi corretti:

```bash
chmod 600 ~/.ssh/config
```

#### 4. Testa connessione

```bash
# Connessione semplificata (usa config)
ssh siteground-prod

# Oppure completa
ssh -i ~/.ssh/id_siteground beweb@tirocinio.clementeteodonno.it
```

**Prima connessione:**
```
The authenticity of host 'tirocinio.clementeteodonno.it' can't be established.
Are you sure you want to continue connecting (yes/no)? yes
```

✅ **Sei dentro!** Output:
```
Welcome to SiteGround!
beweb@server123:~$
```

### Metodo 2: Password (Più Semplice)

```bash
# Connessione diretta con password
ssh beweb@tirocinio.clementeteodonno.it

# Inserisci password quando richiesto
```

**Dove trovo la password?**

1. Login su SiteGround cPanel
2. Vai su **"Devs" → "SSH Access"**
3. Vedrai username e opzione per cambiare password

---

## 🛠️ Operazioni Comuni SSH

### Connettersi al server

```bash
# Con config configurato
ssh siteground-prod

# Senza config
ssh beweb@tirocinio.clementeteodonno.it
```

### Navigare nella directory app

```bash
# Vai alla web root
cd /home/customer/www/tirocinio.clementeteodonno.it/public_html

# Lista file
ls -la

# Verifica branch git
git branch

# Verifica stato
git status
```

### Aggiornare l'app da Git

```bash
# Pull ultimi cambiamenti
git pull origin main

# Installa dipendenze (senza dev)
composer install --no-dev --optimize-autoloader

# Verifica permessi
chmod -R 755 public/
```

### Eseguire comandi PHP

```bash
# Test database connection
php -r "require 'config/database.php'; echo 'DB OK';"

# Clear cache
rm -rf cache/*

# Run setup script
php setup.php
```

### Disconnettersi

```bash
exit
```

---

## 🔧 Troubleshooting Comune

### ❌ "Permission denied (publickey)"

**Causa:** Chiave SSH non configurata o non caricata.

**Soluzione:**

```bash
# Verifica chiavi caricate
ssh-add -l

# Se vuota, aggiungi chiave
ssh-add ~/.ssh/id_ed25519
ssh-add ~/.ssh/id_siteground

# Testa con verbose per debug
ssh -vvv git@github.com
ssh -vvv beweb@tirocinio.clementeteodonno.it
```

### ❌ "Host key verification failed"

**Causa:** Chiave host cambiata (es. server migrato).

**Soluzione:**

```bash
# Rimuovi vecchia chiave
ssh-keygen -R tirocinio.clementeteodonno.it

# Riconnetti (accetterà nuova chiave)
ssh beweb@tirocinio.clementeteodonno.it
```

### ❌ "Connection timed out"

**Causa:** Firewall, porta sbagliata, o server down.

**Soluzione:**

```bash
# Verifica porta 22 aperta
telnet tirocinio.clementeteodonno.it 22

# Prova con timeout esteso
ssh -o ConnectTimeout=30 beweb@tirocinio.clementeteodonno.it

# Verifica porta alternativa (se fornita da SiteGround)
ssh -p 18765 beweb@tirocinio.clementeteodonno.it
```

### ❌ Chiave non funziona dopo creazione

**Causa:** Permessi file sbagliati.

**Soluzione:**

```bash
# Imposta permessi corretti
chmod 700 ~/.ssh
chmod 600 ~/.ssh/id_*
chmod 644 ~/.ssh/*.pub
chmod 600 ~/.ssh/config
chmod 600 ~/.ssh/authorized_keys  # sul server
```

### ❌ "Too many authentication failures"

**Causa:** Troppe chiavi nel ssh-agent.

**Soluzione:**

```bash
# Usa solo chiave specifica
ssh -o IdentitiesOnly=yes -i ~/.ssh/id_siteground beweb@tirocinio.clementeteodonno.it

# Oppure nel config
# IdentitiesOnly yes
```

### ❌ "Could not resolve hostname"

**Causa:** DNS non funziona o dominio sbagliato.

**Soluzione:**

```bash
# Verifica DNS
nslookup tirocinio.clementeteodonno.it

# Prova con IP diretto (se conosci l'IP)
ssh beweb@123.45.67.89

# Verifica spelling hostname
```

---

## 🎯 Workflow Completo Git + SSH

### Setup Iniziale (Una Volta)

```bash
# 1. Genera chiavi SSH
ssh-keygen -t ed25519 -C "tua_email@example.com"
ssh-keygen -t ed25519 -C "siteground-prod" -f ~/.ssh/id_siteground

# 2. Aggiungi a GitHub (copia chiave pubblica)
cat ~/.ssh/id_ed25519.pub
# → Incolla su github.com/settings/keys

# 3. Aggiungi a SiteGround (copia chiave pubblica)
cat ~/.ssh/id_siteground.pub
# → Incolla su SiteGround SSH Keys Manager

# 4. Configura SSH client
nano ~/.ssh/config
# Aggiungi config per siteground-prod

# 5. Testa entrambe le connessioni
ssh -T git@github.com
ssh siteground-prod
```

### Workflow Giornaliero

```bash
# 1. Clone repository (prima volta)
git clone git@github.com:BewebSolution/adhd-saas.git
cd adhd-saas

# 2. Crea feature branch
git checkout -b claude/new-feature

# 3. Lavora localmente
# ... modifica file ...

# 4. Commit e push
git add .
git commit -m "Add new feature"
git push -u origin claude/new-feature

# 5. Deploy su produzione
ssh siteground-prod

# Sul server:
cd /home/customer/www/tirocinio.clementeteodonno.it/public_html
git pull origin main
composer install --no-dev
exit
```

### Script Deploy Automatico (Opzionale)

Crea `deploy.sh` locale:

```bash
#!/bin/bash
echo "🚀 Deploying to production..."

ssh siteground-prod << 'ENDSSH'
cd /home/customer/www/tirocinio.clementeteodonno.it/public_html
echo "📦 Pulling latest changes..."
git pull origin main
echo "🔧 Installing dependencies..."
composer install --no-dev --optimize-autoloader
echo "🧹 Clearing cache..."
rm -rf cache/*
echo "✅ Deploy complete!"
ENDSSH

echo "🎉 Deployment finished!"
```

Usa così:

```bash
chmod +x deploy.sh
./deploy.sh
```

---

## 📚 Risorse Utili

### Documentazione

- **GitHub SSH:** https://docs.github.com/en/authentication/connecting-to-github-with-ssh
- **SiteGround SSH:** https://www.siteground.com/kb/how_to_use_ssh/
- **Git Guide:** https://git-scm.com/book/en/v2

### Comandi Utili SSH

```bash
# Copia file locale → server
scp file.txt siteground-prod:/path/to/destination/

# Copia file server → locale
scp siteground-prod:/path/to/file.txt ./

# Copia directory
scp -r directory/ siteground-prod:/path/

# Esegui comando remoto senza connettersi
ssh siteground-prod "ls -la /home/customer/www"

# Tunnel SSH (forward porta)
ssh -L 3306:localhost:3306 siteground-prod
```

### File Config SSH Completo

```
# ~/.ssh/config

# GitHub
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_ed25519
    IdentitiesOnly yes

# SiteGround Production
Host siteground-prod
    HostName tirocinio.clementeteodonno.it
    User beweb
    Port 22
    IdentityFile ~/.ssh/id_siteground
    IdentitiesOnly yes
    ServerAliveInterval 60
    ServerAliveCountMax 10
    Compression yes

# Opzionale: server di staging
Host siteground-staging
    HostName staging.tirocinio.clementeteodonno.it
    User beweb_staging
    IdentityFile ~/.ssh/id_siteground
    IdentitiesOnly yes
```

---

## ✅ Checklist Setup Completo

### GitHub SSH

- [ ] Chiave SSH generata
- [ ] Chiave pubblica copiata
- [ ] Chiave aggiunta a GitHub (Settings → SSH Keys)
- [ ] Test connessione riuscito (`ssh -T git@github.com`)
- [ ] Git configurato (user.name e user.email)
- [ ] Clone SSH funzionante

### SiteGround SSH

- [ ] Chiave SSH dedicata generata (`id_siteground`)
- [ ] Chiave pubblica copiata
- [ ] Chiave aggiunta a SiteGround (SSH Keys Manager)
- [ ] File `~/.ssh/config` configurato
- [ ] Permessi file corretti (600)
- [ ] Test connessione riuscito (`ssh siteground-prod`)
- [ ] Comandi git funzionanti sul server

### Sicurezza

- [ ] Passphrase impostate sulle chiavi (opzionale ma raccomandato)
- [ ] Chiavi private MAI condivise
- [ ] Backup chiavi SSH eseguito
- [ ] File `.ssh/` con permessi 700
- [ ] Chiavi private con permessi 600

---

**Pronto! 🎉** Ora puoi lavorare con GitHub e deployare su SiteGround senza problemi.

Per domande: clemente@beweb.it
