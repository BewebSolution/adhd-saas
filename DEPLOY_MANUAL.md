# 🚀 Deployment Manuale (senza SSH)

Se non hai accesso SSH, puoi fare il deployment manualmente via cPanel/FileZilla.

## Metodo 1: Via cPanel File Manager

### Step 1: Scarica Repository da GitHub

1. Vai su: https://github.com/BewebSolution/adhd-saas
2. Click su **Code** → **Download ZIP**
3. Salva `adhd-saas-main.zip` sul tuo computer

### Step 2: Carica Files sul Server

1. Login su **cPanel** di SiteGround
2. Vai in **File Manager**
3. Naviga in: `/home/customer/www/tirocinio.clementeteodonno.it/public_html`
4. Crea cartella `app` (se non esiste)
5. Entra in `app`
6. Click su **Upload** e carica il file ZIP
7. Click destro sul ZIP → **Extract**
8. Sposta tutto da `adhd-saas-main/` alla directory `app/`

### Step 3: Configurazione .env

1. Nel File Manager, copia `.env.production` come `.env`
2. Click destro su `.env` → **Edit**
3. Modifica le seguenti righe:

```env
DB_DATABASE=nome_tuo_database
DB_USERNAME=nome_tuo_user
DB_PASSWORD=tua_password
```

4. Salva il file

### Step 4: Setup Database

1. In cPanel, vai su **phpMyAdmin**
2. Seleziona il tuo database
3. Click su **SQL**
4. Copia e incolla lo schema SQL da `DEPLOYMENT.md` (sezione Database Schema)
5. Click **Go**

### Step 5: Setup Permessi

1. Nel File Manager, seleziona le seguenti cartelle:
   - `temp`
   - `cache`
2. Click destro → **Change Permissions**
3. Imposta a **777** (tutti i checkbox)
4. Clicca **Change Permissions**

### Step 6: Installa Composer Dependencies

1. In cPanel, vai su **Terminal** (se disponibile)
2. Esegui:
```bash
cd ~/public_html/app
composer install --no-dev --optimize-autoloader
```

**Se Terminal non disponibile:**
- Scarica le dependencies localmente
- Carica la cartella `vendor/` via FileZilla

### Step 7: Configura Document Root

1. In cPanel, vai su **Domains**
2. Seleziona `tirocinio.clementeteodonno.it`
3. Click **Manage**
4. Modifica **Document Root** in: `/home/customer/www/tirocinio.clementeteodonno.it/public_html/app/public`
5. Salva

## Metodo 2: Via FileZilla (FTP)

### Step 1: Scarica Repository

Come sopra, scarica ZIP da GitHub ed estrailo localmente.

### Step 2: Connetti via FTP

1. Apri **FileZilla**
2. Connetti con:
   - Host: `ftp.clementeteodonno.it`
   - Username: `u2214-ux3fquf7bu3v`
   - Password: (la tua password cPanel)
   - Porta: 21

### Step 3: Carica Files

1. Nel pannello remoto, naviga in: `/home/customer/www/tirocinio.clementeteodonno.it/public_html/`
2. Crea cartella `app`
3. Trascina tutti i file estratti da locale in `app/`
4. Attendi il completamento upload (può richiedere 5-10 minuti)

### Step 4: Configura .env e Database

Segui gli step 3, 4, 5, 6, 7 del Metodo 1.

## Metodo 3: Git direttamente sul Server (Consigliato se hai Terminal)

### Via cPanel Terminal

1. In cPanel, apri **Terminal**
2. Esegui i seguenti comandi:

```bash
# Vai nella directory
cd ~/public_html

# Clona repository
git clone https://github.com/BewebSolution/adhd-saas.git app

# Entra nella directory
cd app

# Copia configurazione produzione
cp .env.production .env

# IMPORTANTE: Modifica .env con nano
nano .env
# Premi CTRL+X per uscire, Y per salvare

# Installa dependencies
composer install --no-dev --optimize-autoloader

# Setup permessi
chmod 755 -R .
chmod 777 temp cache
mkdir -p temp/audio cache

# Verifica tutto OK
ls -la
```

3. Poi configura database come step precedenti

## 🎯 Verifica Deployment

Dopo aver completato uno dei metodi sopra:

1. **Test Homepage:**
   Apri: https://tirocinio.clementeteodonno.it

   - ✅ Dovrebbe mostrare la pagina di login
   - ❌ Se vedi errore 500: controlla permessi e .env

2. **Test Login:**
   - Email: `admin@beweb.it`
   - Password: `admin123`

3. **Test Smart Focus:**
   - Vai su Dashboard
   - Prova "Ottieni Suggerimento"

4. **Configura API Keys:**
   - Vai su: Impostazioni → AI e API Keys
   - Inserisci OpenAI API Key
   - Salva

## 🐛 Troubleshooting

### Errore: 500 Internal Server Error

**Causa:** Permessi o .env sbagliato

**Soluzione:**
1. Verifica che `temp/` e `cache/` abbiano permessi 777
2. Verifica che `.env` esista e abbia le credenziali DB corrette
3. Controlla error_log in cPanel

### Errore: Database Connection Failed

**Causa:** Credenziali DB sbagliate in .env

**Soluzione:**
1. In cPanel → phpMyAdmin
2. Verifica nome database, username, password
3. Aggiorna `.env` di conseguenza

### Errore: Composer non trovato

**Causa:** Composer non installato sul server

**Soluzione:**
1. Installa dependencies localmente:
   ```bash
   cd C:\laragon\www\tirocinio\beweb-app
   composer install --no-dev
   ```
2. Carica la cartella `vendor/` via FTP

### Pagina bianca

**Causa:** Errori PHP non mostrati

**Soluzione:**
1. Abilita temporaneamente debug:
   ```env
   APP_DEBUG=true
   ```
2. Ricarica pagina per vedere errore
3. Fix errore
4. Rimetti `APP_DEBUG=false`

## 📞 Supporto

Se hai problemi:
1. Controlla error_log in cPanel
2. Apri issue su GitHub: https://github.com/BewebSolution/adhd-saas/issues
3. Consulta la documentazione completa in `DEPLOYMENT.md`