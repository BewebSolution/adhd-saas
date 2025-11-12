# 🚀 Setup Beweb Tirocinio App su Laragon

Guida passo-passo per configurare e avviare l'applicazione.

## ✅ Prerequisiti Verificati

- [x] Struttura progetto creata (45 file PHP)
- [x] Composer autoloader generato
- [x] File .env configurato

## 📋 Passi da Completare

### 1. Crea il Database

Apri **HeidiSQL** (o phpMyAdmin) da Laragon e:

```sql
CREATE DATABASE beweb_app
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

### 2. Importa Schema e Seed

In HeidiSQL:

1. Seleziona il database `beweb_app`
2. Vai su **File → Carica file SQL**
3. Seleziona `C:\laragon\www\tirocinio\beweb-app\database\schema.sql`
4. Clicca **Esegui**
5. Ripeti per `database\seed.sql`

**Oppure via riga di comando:**

```bash
cd C:\laragon\www\tirocinio\beweb-app
"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root -p beweb_app < database/schema.sql
"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root -p beweb_app < database/seed.sql
```

### 3. Verifica Credenziali Database

Apri `C:\laragon\www\tirocinio\beweb-app\.env` e verifica:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beweb_app
DB_USERNAME=root
DB_PASSWORD=
```

(La password di MySQL in Laragon è solitamente vuota)

### 4. Configura Virtual Host

**Opzione A: Automatic (Laragon)**

1. Apri Laragon
2. Click destro → **Apache → sites-enabled → Auto create**
3. Il virtual host sarà: `http://beweb-app.test`

**Opzione B: Manual**

Crea file `C:\laragon\etc\apache2\sites-enabled\beweb-app.conf`:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/laragon/www/tirocinio/beweb-app/public"
    ServerName beweb.local
    ServerAlias www.beweb.local

    <Directory "C:/laragon/www/tirocinio/beweb-app/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Aggiungi a `C:\Windows\System32\drivers\etc\hosts` (come Admin):

```
127.0.0.1 beweb.local
```

### 5. Riavvia Apache

In Laragon:
- Click **Stop All**
- Click **Start All**

Oppure click destro → **Apache → Restart**

### 6. Testa l'Applicazione

Apri browser e vai a:

- **Opzione A:** `http://beweb-app.test`
- **Opzione B:** `http://beweb.local`

Dovresti vedere la pagina di **Login**.

### 7. Accedi con Credenziali Demo

**Admin:**
- Email: `admin@beweb.local`
- Password: `admin123`

**Intern:**
- Email: `intern@beweb.local`
- Password: `caterina123`

## 🎯 Checklist Test Funzionalità

Una volta loggato, verifica:

- [ ] **Dashboard** carica correttamente
- [ ] **Focus di oggi** si salva
- [ ] **Nuova attività** si crea (prova codice auto-generato)
- [ ] **Registro ore** si crea e collega ad attività
- [ ] **Consegne** si creano per progetto
- [ ] **Note** si salvano
- [ ] **Filtri** funzionano in ogni sezione
- [ ] **Azioni rapide** (Inizia, Fatto, +Ore) funzionano
- [ ] **Logout** funziona

## 🐛 Risoluzione Problemi

### Errore "Page Not Found"
- Verifica che DocumentRoot punti a `/public`
- Controlla che `.htaccess` esista in `/public`
- Assicurati che `mod_rewrite` sia abilitato in Apache

### Errore "Database Connection"
- Verifica credenziali in `.env`
- Assicurati che MySQL sia avviato in Laragon
- Controlla che database `beweb_app` esista

### Errore "Class not found"
- Esegui `composer dump-autoload` in `beweb-app/`

### Session issues
- Verifica che `C:\laragon\tmp` sia scrivibile
- Controlla permessi directory `beweb-app/`

### Pagina bianca
- Abilita `APP_DEBUG=true` in `.env`
- Controlla PHP error log in `C:\laragon\bin\apache\logs\error.log`

## 📦 File e Directory Importanti

```
beweb-app/
├── public/              ← DocumentRoot (punto di ingresso web)
│   ├── index.php        ← Front controller
│   ├── .htaccess        ← Routing Apache
│   └── assets/          ← CSS e JS
├── app/
│   ├── Controllers/     ← 8 controller
│   ├── Models/          ← 8 models
│   ├── Views/           ← Tutte le viste
│   └── helpers.php      ← Funzioni globali
├── config/
│   ├── database.php     ← Connessione DB
│   └── routes.php       ← Routing app
├── database/
│   ├── schema.sql       ← Struttura DB
│   └── seed.sql         ← Dati iniziali
└── .env                 ← Configurazione (NON committare!)
```

## 🔐 Sicurezza

**Per produzione:**
- Cambia password utenti demo
- Imposta `APP_DEBUG=false` in `.env`
- Abilita HTTPS (decommentare in `.htaccess`)
- Configura backup database automatici
- Aggiorna regolarmente PHP e dipendenze

## 📚 Funzionalità Extra (opzionali)

- **Export CSV:** Implementare nelle viste index
- **Dark mode:** Aggiungere toggle in navbar + localStorage
- **Email notifiche:** Configurare cron per scadenze
- **Upload file:** Aggiungere gestione allegati

## 🎉 Completato!

Il sistema è ora pronto per l'uso. Buon lavoro!

---

**Supporto:** Per problemi o domande, consulta il README.md o contatta il team di sviluppo.
