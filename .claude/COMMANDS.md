# Quick reference

## Dev

```bash
# Start locale (Laragon)
cd C:\laragon\www\tirocinio\beweb-app
# URL: http://localhost/tirocinio/beweb-app/public/

# Start con PHP built-in
php -S localhost:8000 -t public

# Watch logs
tail -f storage/logs/app.log

# Database locale
mysql -u root -p tirocinio_db

# Composer
composer install
composer dump-autoload

# Clear cache (se implementato)
php -r "if(is_dir('storage/cache')) array_map('unlink', glob('storage/cache/*'));"
```

## Deploy

```bash
# 1. Commit e push locale
cd C:\laragon\www\tirocinio\beweb-app
git add -A
git commit -m "Descrizione modifiche"
git push origin main

# 2. Pull su produzione (una riga)
ssh -i /c/laragon/www/tirocinio/tirocinio_siteground_key u2214-ux3fquf7bu3v@ssh.clementeteodonno.it -p 18765 "cd ~/www/tirocinio.clementeteodonno.it/beweb-app && git pull origin main"

# 3. Verifica
curl -I https://tirocinio.clementeteodonno.it
```

## Debug

```bash
# Controlla errori PHP locale
tail -f C:\laragon\tmp\php_errors.log

# Controlla errori produzione
ssh -i /c/laragon/www/tirocinio/tirocinio_siteground_key u2214-ux3fquf7bu3v@ssh.clementeteodonno.it -p 18765 "tail -f ~/www/tirocinio.clementeteodonno.it/public_html/php_errorlog"

# Test API endpoints
curl -X POST http://localhost/tirocinio/beweb-app/public/ai/smart-focus \
  -H "Content-Type: application/json" \
  -d '{"energy":"high","focus_time":"45","mood":"positive"}'

# Database queries test
mysql -u root -p tirocinio_db -e "SELECT COUNT(*) as tasks FROM tasks WHERE status != 'Fatto';"

# Check Google OAuth token
mysql -u root -p tirocinio_db -e "SELECT * FROM oauth_tokens WHERE service='google_tasks';"

# Force clear session
rm -rf C:\Windows\Temp\sess_*
```

## SSH Produzione

```bash
# Connessione diretta
ssh -i C:\laragon\www\tirocinio\tirocinio_siteground_key u2214-ux3fquf7bu3v@ssh.clementeteodonno.it -p 18765

# Credenziali
Host: ssh.clementeteodonno.it
User: u2214-ux3fquf7bu3v
Port: 18765
Key: C:\laragon\www\tirocinio\tirocinio_siteground_key

# Path progetto su server
~/www/tirocinio.clementeteodonno.it/beweb-app/
```

## Git shortcuts

```bash
# Status veloce
git status -s

# Commit con messaggio multi-riga
git commit -m "Fix: Titolo breve" -m "- Dettaglio 1" -m "- Dettaglio 2"

# Vedi ultimi commit
git log --oneline -10

# Annulla ultimo commit (solo locale!)
git reset --soft HEAD~1

# Vedi differenze
git diff HEAD
```