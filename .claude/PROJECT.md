# PROGETTO: ADHD Task Manager (Beweb Tirocinio)
Tipo: webapp
Stack: PHP 8.3, MySQL, JavaScript (Vanilla), Bootstrap 5, SB Admin 2
Obiettivo: Sistema di gestione task ottimizzato per persone con ADHD, con AI integrata per suggerimenti smart

## Struttura
```
beweb-app/
├── app/
│   ├── Config/         # Configurazione app
│   ├── Controllers/    # Controller MVC
│   ├── Services/       # Servizi (AI, Google Tasks, etc)
│   └── Views/          # Template PHP
├── config/             # Route e configurazioni
├── public/             # Entry point e assets
│   ├── assets/         # CSS, JS, immagini
│   └── index.php       # Front controller
├── vendor/             # Dipendenze Composer
└── .claude/            # Documentazione progetto
```

## Convenzioni
- Naming: PascalCase per classi, camelCase per metodi, snake_case per database
- Style: PSR-12 per PHP, vanilla JS con commenti JSDoc
- Database: Prefisso tabelle non usato, charset utf8mb4_unicode_ci
- Views: PHP puro con ob_start/ob_get_clean pattern

## Setup
```bash
# Locale (Laragon)
cd C:\laragon\www\tirocinio\beweb-app
composer install
# Configura .env con credenziali DB e API keys

# Database
mysql -u root -p
CREATE DATABASE tirocinio_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
# Le tabelle vengono create automaticamente dai controller

# Avvia con Laragon o:
php -S localhost:8000 -t public
```

## Note critiche
- **AUTENTICAZIONE**: Tutte le pagine richiedono login (require_auth())
- **API KEYS**: Gestite da DB tramite /ai/settings (admin only)
- **GOOGLE OAUTH**: Redirect URI deve essere configurato in Google Console
- **ADHD FOCUS**: Ogni feature deve considerare distrazioni e executive dysfunction
- **AI SERVICES**: OpenAI/Claude API con fallback per funzionamento offline
- **SSH PRODUZIONE**: Chiave in C:\laragon\www\tirocinio\tirocinio_siteground_key