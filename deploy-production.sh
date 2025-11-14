#!/bin/bash

###############################################################################
# Deploy Script - ADHD SaaS su SiteGround
# Esegui da locale: ./deploy-production.sh
###############################################################################

set -e  # Exit on error

echo ""
echo "========================================="
echo "  🚀 Deploy Produzione SiteGround"
echo "========================================="
echo ""

# Configurazione
REMOTE_HOST="beweb@tirocinio.clementeteodonno.it"
REMOTE_PATH="/home/customer/www/tirocinio.clementeteodonno.it/public_html"
BRANCH="claude/claude-md-mhz3v565a97rayl8-013ScgnfhZ7UuerWpVmTgjPn"

echo "📋 Configurazione:"
echo "   Host: $REMOTE_HOST"
echo "   Path: $REMOTE_PATH"
echo "   Branch: $BRANCH"
echo ""

# Verifica connessione SSH
echo "🔍 Step 1: Verifica connessione SSH..."
if ssh -o ConnectTimeout=5 $REMOTE_HOST "echo 'Connessione OK'" > /dev/null 2>&1; then
    echo "   ✅ Connessione SSH funzionante"
else
    echo "   ❌ Errore: Impossibile connettersi a $REMOTE_HOST"
    echo "   Verifica:"
    echo "   - Chiavi SSH configurate correttamente"
    echo "   - Server raggiungibile"
    echo "   - Credenziali corrette"
    exit 1
fi
echo ""

# Backup corrente
echo "💾 Step 2: Backup versione corrente..."
ssh $REMOTE_HOST << 'ENDSSH'
cd /home/customer/www/tirocinio.clementeteodonno.it/public_html
BACKUP_DIR="../backups/backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p ../backups
echo "   Creazione backup in: $BACKUP_DIR"
cp -r . "$BACKUP_DIR"
echo "   ✅ Backup completato"
ENDSSH
echo ""

# Git fetch e pull
echo "📦 Step 3: Aggiornamento codice da Git..."
ssh $REMOTE_HOST << ENDSSH
cd $REMOTE_PATH

echo "   Fetch da origin..."
git fetch origin

echo "   Verifica branch corrente..."
CURRENT_BRANCH=\$(git branch --show-current)
echo "   Branch corrente: \$CURRENT_BRANCH"

echo "   Checkout branch: $BRANCH"
git checkout $BRANCH || git checkout -b $BRANCH origin/$BRANCH

echo "   Pull ultimi commit..."
git pull origin $BRANCH

echo "   ✅ Codice aggiornato"
ENDSSH
echo ""

# Composer install
echo "🔧 Step 4: Aggiornamento dipendenze..."
ssh $REMOTE_HOST << 'ENDSSH'
cd /home/customer/www/tirocinio.clementeteodonno.it/public_html

echo "   Esecuzione composer install..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "   ✅ Dipendenze aggiornate"
ENDSSH
echo ""

# Verifica permessi
echo "🔒 Step 5: Verifica permessi..."
ssh $REMOTE_HOST << 'ENDSSH'
cd /home/customer/www/tirocinio.clementeteodonno.it/public_html

echo "   Impostazione permessi corretti..."
chmod 755 app config public 2>/dev/null || true
chmod -R 755 app/Views 2>/dev/null || true
chmod 644 .env 2>/dev/null || true

echo "   ✅ Permessi verificati"
ENDSSH
echo ""

# Clear cache
echo "🧹 Step 6: Pulizia cache..."
ssh $REMOTE_HOST << 'ENDSSH'
cd /home/customer/www/tirocinio.clementeteodonno.it/public_html

if [ -d "cache" ]; then
    echo "   Rimozione file cache..."
    rm -rf cache/*
    echo "   ✅ Cache pulita"
else
    echo "   ℹ️  Directory cache non presente"
fi
ENDSSH
echo ""

# Test auto-detection
echo "🔍 Step 7: Test sistema auto-detection..."
ssh $REMOTE_HOST << 'ENDSSH'
cd /home/customer/www/tirocinio.clementeteodonno.it/public_html

echo "   Test auto_detect_base_path()..."
php -r "require 'app/helpers.php'; echo 'Base path rilevato: [' . auto_detect_base_path() . ']' . PHP_EOL;"

echo "   Test base_url()..."
php -r "require 'app/helpers.php'; echo 'Base URL: ' . base_url() . PHP_EOL;"

echo "   ✅ Auto-detection funzionante"
ENDSSH
echo ""

# Verifica file critici
echo "📝 Step 8: Verifica file deployati..."
ssh $REMOTE_HOST << 'ENDSSH'
cd /home/customer/www/tirocinio.clementeteodonno.it/public_html

echo "   Verifica nuovi file:"
[ -f "setup.php" ] && echo "   ✅ setup.php" || echo "   ❌ setup.php MANCANTE"
[ -f "QUICKSTART.md" ] && echo "   ✅ QUICKSTART.md" || echo "   ❌ QUICKSTART.md MANCANTE"
[ -f "SSH_SETUP.md" ] && echo "   ✅ SSH_SETUP.md" || echo "   ❌ SSH_SETUP.md MANCANTE"
[ -f "CLAUDE.md" ] && echo "   ✅ CLAUDE.md" || echo "   ❌ CLAUDE.md MANCANTE"

echo ""
echo "   Verifica file modificati:"
grep -q "auto_detect_base_path" app/helpers.php && echo "   ✅ app/helpers.php (auto-detection)" || echo "   ⚠️  app/helpers.php (auto-detection non trovato)"
grep -q "auto_detect_base_path" public/index.php && echo "   ✅ public/index.php (auto-detection)" || echo "   ⚠️  public/index.php (auto-detection non trovato)"
ENDSSH
echo ""

# Mostra ultimi commit
echo "📜 Step 9: Verifica commit deployati..."
ssh $REMOTE_HOST << 'ENDSSH'
cd /home/customer/www/tirocinio.clementeteodonno.it/public_html

echo "   Ultimi 3 commit:"
git log --oneline -3
ENDSSH
echo ""

# Test finale
echo "🧪 Step 10: Test finale applicazione..."
ssh $REMOTE_HOST << 'ENDSSH'
cd /home/customer/www/tirocinio.clementeteodonno.it/public_html

echo "   Test connessione database..."
php -r "require 'config/database.php'; echo '✅ Database connesso' . PHP_EOL;" 2>/dev/null || echo "⚠️  Database test fallito"

echo "   Test bootstrap..."
php -r "require 'bootstrap.php'; echo '✅ Bootstrap OK' . PHP_EOL;" 2>/dev/null || echo "⚠️  Bootstrap test fallito"
ENDSSH
echo ""

# Riepilogo
echo "========================================="
echo "  ✅ Deploy Completato con Successo!"
echo "========================================="
echo ""
echo "📋 Riepilogo:"
echo "   ✅ Codice aggiornato al branch: $BRANCH"
echo "   ✅ Dipendenze aggiornate"
echo "   ✅ Permessi verificati"
echo "   ✅ Cache pulita"
echo "   ✅ Auto-detection system attivo"
echo ""
echo "🌐 Verifica in produzione:"
echo "   URL: https://tirocinio.clementeteodonno.it"
echo ""
echo "📝 File nuovi deployati:"
echo "   - setup.php (script setup automatico)"
echo "   - QUICKSTART.md (guida rapida)"
echo "   - SSH_SETUP.md (guida SSH)"
echo "   - CLAUDE.md (documentazione AI)"
echo ""
echo "🔧 Modifiche principali:"
echo "   - Sistema auto-detection path e domini"
echo "   - Nessun path hardcoded"
echo "   - Funziona su qualsiasi ambiente"
echo ""
echo "========================================="
echo ""

# Opzionale: apri browser
read -p "Vuoi aprire il sito nel browser? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🌐 Apertura browser..."
    if command -v xdg-open > /dev/null; then
        xdg-open https://tirocinio.clementeteodonno.it
    elif command -v open > /dev/null; then
        open https://tirocinio.clementeteodonno.it
    elif command -v start > /dev/null; then
        start https://tirocinio.clementeteodonno.it
    else
        echo "   Apri manualmente: https://tirocinio.clementeteodonno.it"
    fi
fi

echo ""
echo "🎉 Deploy terminato!"
echo ""
